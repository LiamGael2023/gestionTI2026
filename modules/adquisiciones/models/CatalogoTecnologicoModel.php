<?php
class CatalogoTecnologicoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

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

		$stmt = sqlsrv_query($this->db, $sql, [$anioConsulta, $anioConsulta, $anioConsulta]);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

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

	// Sincronizar HomologacionSiga con lo homologado
	// Inserta nuevos y actualiza los que cambiaron de tipo

	public function sincronizarHomologacion(): array
	{
		// 1. Insertar códigos nuevos que no están en HomologacionSiga
		$sqlInsert = "
            INSERT INTO adquisiciones.HomologacionSiga (CodigoSiga, IdCatalogoTecnologico)
            SELECT DISTINCT
                dr.CodigoSiga,
                dr.IdCatalogoTecnologico
            FROM adquisiciones.DetalleRequerimiento dr
            WHERE dr.IdCatalogoTecnologico <> 1000010
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
                WHERE IdCatalogoTecnologico <> 1000010
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
