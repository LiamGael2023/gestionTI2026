<?php
class CatalogoTecnologicoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	// Lista tecnologías activas con estado de ficha técnica y presupuesto para un año
	public function listarConEstadoFicha($anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');

		$sql = "
			SELECT
				ct.Codigo AS Tecnologia,
				ct.NombreGenerico,
				ct.Id AS IdCatalogoTecnologico,
				MIN(d.CodigoSiga) AS CodigoSiga,
				COUNT(DISTINCT d.CodigoSiga) AS TotalCodigosSiga,
				STUFF((
					SELECT DISTINCT ', ' + d2.CodigoSiga
					FROM adquisiciones.DetalleRequerimiento d2
					INNER JOIN adquisiciones.Requerimiento r2 ON r2.Id = d2.IdRequerimiento
					WHERE d2.IdCatalogoTecnologico = ct.Id AND r2.Anio = ?
					FOR XML PATH(''), TYPE
				).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS CodigosSiga,
				CASE
					WHEN EXISTS (
						SELECT 1
						FROM adquisiciones.PresupuestoTecnologia pt
						WHERE pt.IdCatalogoTecnologico = ct.Id
						  AND pt.Anio = ?
						  AND pt.Monto IS NOT NULL
					) THEN 1
					ELSE 0
				END AS TienePresupuesto,
				CASE 
					WHEN ca.Id IS NOT NULL AND ca.Estado = 1 THEN 1
					ELSE 0
				END AS EstadoCompleto
			FROM adquisiciones.DetalleRequerimiento d
			INNER JOIN adquisiciones.Requerimiento r ON r.Id = d.IdRequerimiento
			INNER JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = d.IdCatalogoTecnologico
			LEFT JOIN adquisiciones.CierreAdquisicion ca
				ON ca.IdCatalogoTecnologico = ct.Id AND ca.Anio = ?
			WHERE r.Anio = ? AND ct.Activo = 1
			GROUP BY ct.Codigo, ct.NombreGenerico, ct.Id, ca.Id, ca.Estado
			ORDER BY
				CASE
					WHEN PATINDEX('%[0-9]%', ct.Codigo) > 0 THEN LEFT(ct.Codigo, PATINDEX('%[0-9]%', ct.Codigo) - 1)
					ELSE ct.Codigo
				END,
				CASE
					WHEN PATINDEX('%[0-9]%', ct.Codigo) > 0 THEN TRY_CAST(
						LEFT(
							SUBSTRING(ct.Codigo, PATINDEX('%[0-9]%', ct.Codigo), LEN(ct.Codigo)),
							PATINDEX('%[^0-9]%', SUBSTRING(ct.Codigo, PATINDEX('%[0-9]%', ct.Codigo), LEN(ct.Codigo)) + 'X') - 1
						) AS INT
					)
					ELSE 0
				END,
				ct.NombreGenerico
		";

		$stmt = sqlsrv_query($this->db, $sql, [$anioConsulta, $anioConsulta, $anioConsulta, $anioConsulta]);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Obtiene todos los años disponibles que tienen requerimientos
	public function obtenerAniosDisponibles()
	{
		$sql = "
			SELECT DISTINCT r.Anio
			FROM adquisiciones.Requerimiento r
			INNER JOIN adquisiciones.DetalleRequerimiento d ON d.IdRequerimiento = r.Id
			ORDER BY Anio DESC
		";

		$stmt = sqlsrv_query($this->db, $sql);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = (int) $row['Anio'];
		}

		return $data;
	}

	// Obtiene todos los años disponibles para una tecnología específica
	public function obtenerAniosDisponiblesPorTecnologia($idCatalogoTecnologico)
	{
		$sql = "
			SELECT DISTINCT r.Anio
			FROM adquisiciones.Requerimiento r
			INNER JOIN adquisiciones.DetalleRequerimiento d ON d.IdRequerimiento = r.Id
			WHERE d.IdCatalogoTecnologico = ?
			ORDER BY r.Anio DESC
		";

		$stmt = sqlsrv_query($this->db, $sql, [(int) $idCatalogoTecnologico]);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = (int) $row['Anio'];
		}

		return $data;
	}

	// Verifica si existen pedidos/requerimientos para una tecnología en un año
	public function tienePedidosPorTecnologiaEnAnio($idCatalogoTecnologico, $anio)
	{
		$sql = "
			SELECT TOP 1 1 AS Existe
			FROM adquisiciones.DetalleRequerimiento d
			INNER JOIN adquisiciones.Requerimiento r ON r.Id = d.IdRequerimiento
			WHERE d.IdCatalogoTecnologico = ? AND r.Anio = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [(int) $idCatalogoTecnologico, (int) $anio]);
		if ($stmt === false) {
			return false;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return !empty($row);
	}

	// Obtiene una tecnología por ID
	public function obtenerPorId($id)
	{
		$sql = "
			SELECT Id, Codigo, NombreGenerico, Activo
			FROM adquisiciones.CatalogoTecnologico
			WHERE Id = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			return null;
		}

		return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: null;
	}

	// Verifica si existe una tecnología duplicada por código y nombre
	public function existeDuplicado($codigo, $nombreGenerico, $idExcluir = null)
	{
		$sql = "
			SELECT TOP 1 Id, Codigo, NombreGenerico
			FROM adquisiciones.CatalogoTecnologico
			WHERE Activo = 1
			  AND UPPER(LTRIM(RTRIM(Codigo))) = UPPER(LTRIM(RTRIM(?)))
			  AND UPPER(LTRIM(RTRIM(NombreGenerico))) = UPPER(LTRIM(RTRIM(?)))
		";

		$params = [$codigo, $nombreGenerico];
		if ((int) $idExcluir > 0) {
			$sql .= " AND Id <> ?";
			$params[] = (int) $idExcluir;
		}

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return null;
		}

		return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: null;
	}

	// Lista todas las tecnologías activas del catálogo ordenadas alfabéticamente
	public function listarTecnologiasActivas()
	{
		$sql = "
			SELECT Id, Codigo, NombreGenerico, Activo
			FROM adquisiciones.CatalogoTecnologico
			ORDER BY
				CASE
					WHEN PATINDEX('%[0-9]%', Codigo) > 0 THEN LEFT(Codigo, PATINDEX('%[0-9]%', Codigo) - 1)
					ELSE Codigo
				END,
				CASE
					WHEN PATINDEX('%[0-9]%', Codigo) > 0 THEN TRY_CAST(
						LEFT(
							SUBSTRING(Codigo, PATINDEX('%[0-9]%', Codigo), LEN(Codigo)),
							PATINDEX('%[^0-9]%', SUBSTRING(Codigo, PATINDEX('%[0-9]%', Codigo), LEN(Codigo)) + 'X') - 1
						) AS INT
					)
					ELSE 0
				END,
				NombreGenerico
		";

		$stmt = sqlsrv_query($this->db, $sql);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Actualiza código y nombre de una tecnología validando duplicados
	public function actualizarTecnologia($id, $codigo, $nombreGenerico)
	{
		$id = (int) $id;
		$codigoLimpio = trim((string) $codigo);
		$nombreLimpio = trim((string) $nombreGenerico);

		if ($id <= 0 || $codigoLimpio === '' || $nombreLimpio === '') {
			return [
				'success' => false,
				'message' => 'Debe ingresar codigo y nombre generico válidos.',
			];
		}

		$duplicado = $this->existeDuplicado($codigoLimpio, $nombreLimpio, $id);
		if (!empty($duplicado)) {
			return [
				'success' => false,
				'message' => 'Ya existe otra tecnologia con el mismo codigo y nombre generico.',
			];
		}

		$sql = "
			UPDATE adquisiciones.CatalogoTecnologico
			SET Codigo = ?, NombreGenerico = ?
			WHERE Id = ? AND Activo = 1
		";

		$stmt = sqlsrv_query($this->db, $sql, [$codigoLimpio, $nombreLimpio, $id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo actualizar la tecnologia' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tecnologia actualizada correctamente.',
		];
	}

	// Inactiva una tecnología del catálogo
	public function eliminarTecnologia($id)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return [
				'success' => false,
				'message' => 'Tecnologia inválida.',
			];
		}

		$sql = "UPDATE adquisiciones.CatalogoTecnologico SET Activo = 0 WHERE Id = ? AND Activo = 1";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo inactivar la tecnologia' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tecnologia inactivada correctamente.',
		];
	}

	// Activa una tecnología inactiva validando duplicados
	public function activarTecnologia($id)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return [
				'success' => false,
				'message' => 'Tecnologia inválida.',
			];
		}

		$sqlBuscar = "SELECT TOP 1 Codigo, NombreGenerico FROM adquisiciones.CatalogoTecnologico WHERE Id = ?";
		$stmtBuscar = sqlsrv_query($this->db, $sqlBuscar, [$id]);
		if ($stmtBuscar === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo validar la tecnologia' . $detalle,
			];
		}

		$fila = sqlsrv_fetch_array($stmtBuscar, SQLSRV_FETCH_ASSOC);
		if (!$fila) {
			return [
				'success' => false,
				'message' => 'No se encontró la tecnologia.',
			];
		}

		$codigo = trim((string) ($fila['Codigo'] ?? ''));
		$nombreGenerico = trim((string) ($fila['NombreGenerico'] ?? ''));
		$duplicado = $this->existeDuplicado($codigo, $nombreGenerico, $id);
		if (!empty($duplicado)) {
			return [
				'success' => false,
				'message' => 'No se puede activar porque ya existe otra tecnologia activa con el mismo codigo y nombre generico.',
			];
		}

		$sql = "UPDATE adquisiciones.CatalogoTecnologico SET Activo = 1 WHERE Id = ? AND Activo = 0";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo activar la tecnologia' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tecnologia activada correctamente.',
		];
	}

	// Agrega una nueva tecnología al catálogo validando duplicados
	public function agregarTecnologia($codigo, $nombreGenerico)
	{
		$codigoLimpio = trim((string) $codigo);
		$nombreLimpio = trim((string) $nombreGenerico);

		if ($codigoLimpio === '' || $nombreLimpio === '') {
			return [
				'success' => false,
				'message' => 'Debe ingresar codigo y nombre generico.',
			];
		}

		$duplicado = $this->existeDuplicado($codigoLimpio, $nombreLimpio);
		if (!empty($duplicado)) {
			return [
				'success' => false,
				'duplicado' => true,
				'tipoConflicto' => 'exacto',
				'message' => 'La tecnologia ya existe en el catalogo con el mismo codigo y nombre generico.',
				'existente' => [
					'Id' => (int) ($duplicado['Id'] ?? 0),
					'Codigo' => (string) ($duplicado['Codigo'] ?? ''),
					'NombreGenerico' => (string) ($duplicado['NombreGenerico'] ?? ''),
				],
			];
		}

		$sql = "
			INSERT INTO adquisiciones.CatalogoTecnologico (Codigo, NombreGenerico, Activo)
			VALUES (?, ?, 1)
		";

		$stmt = sqlsrv_query($this->db, $sql, [$codigoLimpio, $nombreLimpio]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo registrar la tecnologia' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tecnologia registrada correctamente.',
		];
	}

	// Lista todos los tipos de solicitud para gestión
	public function listarTiposSolicitudGestion()
	{
		$sql = "
			SELECT Id, Nombre, Activo
			FROM adquisiciones.TipoSolicitud
			ORDER BY Nombre
		";

		$stmt = sqlsrv_query($this->db, $sql);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Verifica si existe un tipo de solicitud duplicado por nombre (privado)
	private function existeTipoSolicitudDuplicado($nombre, $idExcluir = null)
	{
		$sql = "
			SELECT TOP 1 Id, Nombre
			FROM adquisiciones.TipoSolicitud
			WHERE UPPER(LTRIM(RTRIM(Nombre))) = UPPER(LTRIM(RTRIM(?)))
		";

		$params = [$nombre];
		if ((int) $idExcluir > 0) {
			$sql .= " AND Id <> ?";
			$params[] = (int) $idExcluir;
		}

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return null;
		}

		return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: null;
	}

	// Agrega un nuevo tipo de solicitud validando duplicados
	public function agregarTipoSolicitud($nombre, $idUsuarioRegistro = null)
	{
		$nombreLimpio = trim((string) $nombre);
		if ($nombreLimpio === '') {
			return [
				'success' => false,
				'message' => 'Debe ingresar el nombre del tipo de solicitud.',
			];
		}

		$duplicado = $this->existeTipoSolicitudDuplicado($nombreLimpio);
		if (!empty($duplicado)) {
			return [
				'success' => false,
				'message' => 'Ya existe un tipo de solicitud con ese nombre.',
			];
		}

		$sql = "
			INSERT INTO adquisiciones.TipoSolicitud (Nombre, Activo, FechaRegistro)
			VALUES (?, 1, GETDATE())
		";

		$stmt = sqlsrv_query($this->db, $sql, [$nombreLimpio]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo registrar el tipo de solicitud' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tipo de solicitud registrado correctamente.',
		];
	}

	// Actualiza el nombre de un tipo de solicitud validando duplicados
	public function actualizarTipoSolicitud($id, $nombre, $idUsuarioModifica = null)
	{
		$id = (int) $id;
		$nombreLimpio = trim((string) $nombre);

		if ($id <= 0 || $nombreLimpio === '') {
			return [
				'success' => false,
				'message' => 'Datos inválidos para actualizar tipo de solicitud.',
			];
		}

		$duplicado = $this->existeTipoSolicitudDuplicado($nombreLimpio, $id);
		if (!empty($duplicado)) {
			return [
				'success' => false,
				'message' => 'Ya existe otro tipo de solicitud con ese nombre.',
			];
		}

		$sql = "
			UPDATE adquisiciones.TipoSolicitud
			SET Nombre = ?, FechaModifica = GETDATE()
			WHERE Id = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [$nombreLimpio, $id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo actualizar el tipo de solicitud' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tipo de solicitud actualizado correctamente.',
		];
	}

	// Inactiva un tipo de solicitud
	public function eliminarTipoSolicitud($id, $idUsuarioModifica = null)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return [
				'success' => false,
				'message' => 'Tipo de solicitud inválido.',
			];
		}

		$sql = "
			UPDATE adquisiciones.TipoSolicitud
			SET Activo = 0, FechaModifica = GETDATE()
			WHERE Id = ? AND Activo = 1
		";

		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo inactivar el tipo de solicitud' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tipo de solicitud inactivado correctamente.',
		];
	}

	// Activa un tipo de solicitud inactivo validando duplicados
	public function activarTipoSolicitud($id, $idUsuarioModifica = null)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return [
				'success' => false,
				'message' => 'Tipo de solicitud inválido.',
			];
		}

		$sqlBuscar = "SELECT TOP 1 Nombre FROM adquisiciones.TipoSolicitud WHERE Id = ?";
		$stmtBuscar = sqlsrv_query($this->db, $sqlBuscar, [$id]);
		if ($stmtBuscar === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo validar el tipo de solicitud' . $detalle,
			];
		}

		$fila = sqlsrv_fetch_array($stmtBuscar, SQLSRV_FETCH_ASSOC);
		if (!$fila) {
			return [
				'success' => false,
				'message' => 'No se encontró el tipo de solicitud.',
			];
		}

		$nombre = trim((string) ($fila['Nombre'] ?? ''));
		$duplicado = $this->existeTipoSolicitudDuplicado($nombre, $id);
		if (!empty($duplicado)) {
			return [
				'success' => false,
				'message' => 'No se puede activar porque ya existe otro tipo de solicitud con el mismo nombre.',
			];
		}

		$sql = "
			UPDATE adquisiciones.TipoSolicitud
			SET Activo = 1, FechaModifica = GETDATE()
			WHERE Id = ? AND Activo = 0
		";

		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo activar el tipo de solicitud' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Tipo de solicitud activado correctamente.',
		];
	}

	// Lista todos los tipos de solicitud activos
	public function listarTiposSolicitudActivos()
	{
		$sql = "
			SELECT Id, Nombre
			FROM adquisiciones.TipoSolicitud
			WHERE Activo = 1
			ORDER BY Nombre
		";

		$stmt = sqlsrv_query($this->db, $sql);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Lista asociaciones entre tecnologías y tipos de solicitud para un año
	public function listarAsociacionesTecnologiaTipoSolicitud($anio)
	{
		$anioConsulta = (int) $anio;
		if ($anioConsulta <= 0) {
			$anioConsulta = (int) date('Y');
		}

		$sql = "
			SELECT
				r.Id,
				r.IdCatalogoTecnologico,
				r.IdTipoSolicitud,
				r.Anio,
				r.Activo,
				ct.Codigo,
				ct.NombreGenerico,
				ts.Nombre AS NombreTipoSolicitud
			FROM adquisiciones.CatalogoTecnologicoTipoSolicitud r
			INNER JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = r.IdCatalogoTecnologico
			INNER JOIN adquisiciones.TipoSolicitud ts ON ts.Id = r.IdTipoSolicitud
			WHERE r.Anio = ?
			ORDER BY
				CASE
					WHEN PATINDEX('%[0-9]%', ct.Codigo) > 0 THEN LEFT(ct.Codigo, PATINDEX('%[0-9]%', ct.Codigo) - 1)
					ELSE ct.Codigo
				END,
				CASE
					WHEN PATINDEX('%[0-9]%', ct.Codigo) > 0 THEN TRY_CAST(
						LEFT(
							SUBSTRING(ct.Codigo, PATINDEX('%[0-9]%', ct.Codigo), LEN(ct.Codigo)),
							PATINDEX('%[^0-9]%', SUBSTRING(ct.Codigo, PATINDEX('%[0-9]%', ct.Codigo), LEN(ct.Codigo)) + 'X') - 1
						) AS INT
					)
					ELSE 0
				END,
				ct.NombreGenerico
		";

		$stmt = sqlsrv_query($this->db, $sql, [$anioConsulta]);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Guarda o actualiza asociación entre tecnología y tipo de solicitud validando existencia
	public function guardarAsociacionTecnologiaTipoSolicitud($idCatalogoTecnologico, $idTipoSolicitud, $anio, $idUsuario = null)
	{
		$idCatalogo = (int) $idCatalogoTecnologico;
		$idSolicitud = (int) $idTipoSolicitud;
		$anio = (int) $anio;

		if ($idCatalogo <= 0 || $idSolicitud <= 0 || $anio <= 0) {
			return [
				'success' => false,
				'message' => 'Debe seleccionar tecnología, tipo de solicitud y año válidos.',
			];
		}

		$sqlValTec = "SELECT TOP 1 Id, Activo FROM adquisiciones.CatalogoTecnologico WHERE Id = ?";
		$stmtValTec = sqlsrv_query($this->db, $sqlValTec, [$idCatalogo]);
		$tecnologia = $stmtValTec ? sqlsrv_fetch_array($stmtValTec, SQLSRV_FETCH_ASSOC) : null;
		if (!$tecnologia) {
			return [
				'success' => false,
				'message' => 'No se encontró la tecnología seleccionada.',
			];
		}

		$sqlValSolicitud = "SELECT TOP 1 Id, Activo FROM adquisiciones.TipoSolicitud WHERE Id = ?";
		$stmtValSolicitud = sqlsrv_query($this->db, $sqlValSolicitud, [$idSolicitud]);
		$solicitud = $stmtValSolicitud ? sqlsrv_fetch_array($stmtValSolicitud, SQLSRV_FETCH_ASSOC) : null;
		if (!$solicitud) {
			return [
				'success' => false,
				'message' => 'No se encontró el tipo de solicitud seleccionado.',
			];
		}

		if ((int) ($solicitud['Activo'] ?? 0) !== 1) {
			return [
				'success' => false,
				'message' => 'El tipo de solicitud seleccionado está inactivo.',
			];
		}

		$sqlExistente = "
			SELECT TOP 1 Id
			FROM adquisiciones.CatalogoTecnologicoTipoSolicitud
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
		";
		$stmtExistente = sqlsrv_query($this->db, $sqlExistente, [$idCatalogo, $anio]);
		$existente = $stmtExistente ? sqlsrv_fetch_array($stmtExistente, SQLSRV_FETCH_ASSOC) : null;

		if ($existente) {
			$sqlUpdate = "
				UPDATE adquisiciones.CatalogoTecnologicoTipoSolicitud
				SET IdTipoSolicitud = ?,
					Activo = 1,
					idUsuarioModifica = ?,
					FechaModifica = GETDATE()
				WHERE Id = ?
			";
			$stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, [$idSolicitud, $idUsuario, (int) $existente['Id']]);
			if ($stmtUpdate === false) {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
				return [
					'success' => false,
					'message' => 'No se pudo actualizar la asociación' . $detalle,
				];
			}

			return [
				'success' => true,
				'message' => 'Asociación actualizada correctamente.',
			];
		}

		$sqlInsert = "
			INSERT INTO adquisiciones.CatalogoTecnologicoTipoSolicitud
				(IdCatalogoTecnologico, IdTipoSolicitud, Anio, Activo, idUsuarioRegistro, FechaRegistro)
			VALUES (?, ?, ?, 1, ?, GETDATE())
		";
		$stmtInsert = sqlsrv_query($this->db, $sqlInsert, [$idCatalogo, $idSolicitud, $anio, $idUsuario]);
		if ($stmtInsert === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return [
				'success' => false,
				'message' => 'No se pudo registrar la asociación' . $detalle,
			];
		}

		return [
			'success' => true,
			'message' => 'Asociación registrada correctamente.',
		];
	}

	// Obtiene todos los pedidos/requerimientos de una tecnología en un año
	public function obtenerPedidosPorTecnologia($idCatalogoTecnologico, $anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');

		$sql = "
			SELECT
				d.Id AS IdDetalle,
				d.IdRequerimiento,
				d.IdCatalogoTecnologico,
				r.NroPedidoCompra,
				r.IdCentroCosto,
				cc.Siglas,
				cc.NombreCentroCosto,
				cc.NombreCentroCosto AS DireccionSolicitante,
				d.CodigoSiga,
				d.DescripcionDetallada,
				d.Cantidad,
				d.UnidadMedida,
				r.Anio
			FROM adquisiciones.DetalleRequerimiento d
			INNER JOIN adquisiciones.Requerimiento r ON r.Id = d.IdRequerimiento
			INNER JOIN adquisiciones.CentroCosto cc ON cc.Id = r.IdCentroCosto
			WHERE d.IdCatalogoTecnologico = ? AND r.Anio = ?
			ORDER BY r.NroPedidoCompra, d.CodigoSiga, d.Id
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anioConsulta]);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Sincroniza homologaciones SIGA insertando nuevas y actualizando las existentes
	public function sincronizarHomologacion(): array
	{
		// 1. Insertar códigos nuevos que no están en HomologacionSiga
		$sqlInsert = "
            INSERT INTO adquisiciones.HomologacionSiga (CodigoSiga, IdCatalogoTecnologico)
            SELECT DISTINCT
                dr.CodigoSiga,
                dr.IdCatalogoTecnologico
            FROM adquisiciones.DetalleRequerimiento dr
						WHERE dr.IdCatalogoTecnologico IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM adquisiciones.HomologacionSiga h
                WHERE h.CodigoSiga = dr.CodigoSiga
              )
        ";

		$stmtInsert = sqlsrv_query($this->db, $sqlInsert);
		if ($stmtInsert === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			throw new Exception('Error insertando homologaciones: ' . $errors[0]['message']);
		}
		$nuevos = sqlsrv_rows_affected($stmtInsert);

		// 2. Actualizar los que cambiaron de tipo
		$sqlUpdate = "
            UPDATE h
            SET h.IdCatalogoTecnologico = dr.IdCatalogoTecnologico
            FROM adquisiciones.HomologacionSiga h
            INNER JOIN (
                SELECT DISTINCT CodigoSiga, IdCatalogoTecnologico
                FROM adquisiciones.DetalleRequerimiento
				WHERE IdCatalogoTecnologico IS NOT NULL
            ) dr ON dr.CodigoSiga = h.CodigoSiga
            WHERE h.IdCatalogoTecnologico <> dr.IdCatalogoTecnologico
        ";

		$stmtUpdate = sqlsrv_query($this->db, $sqlUpdate);
		if ($stmtUpdate === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			throw new Exception('Error actualizando homologaciones: ' . $errors[0]['message']);
		}
		$actualizados = sqlsrv_rows_affected($stmtUpdate);

		return [
			'nuevos'       => (int) $nuevos,
			'actualizados' => (int) $actualizados,
		];
	}
}
