<?php
require_once 'modules/adquisiciones/helpers.php';

class RequerimientoModel
{
	private $db;

	// Inicializa el modelo con la conexion a la base de datos.
	public function __construct($db)
	{
		$this->db = $db;
	}

	// Lista los requerimientos registrados, opcionalmente filtrados por anio.
	public function listarRequerimientos($anio = null)
	{
		$sql = "
			SELECT
				r.Id,
				r.IdCentroCosto,
				r.IdMetaSIAF,
				r.NroPedidoCompra,
				r.CodigoMeta,
				c.NombreCentroCosto,
				c.Siglas,
				r.Anio,
				r.Estado,
				COUNT(d.Id) AS TotalItems
			FROM adquisiciones.Requerimiento r
			INNER JOIN adquisiciones.CentroCosto c ON c.Id = r.IdCentroCosto
			LEFT JOIN adquisiciones.DetalleRequerimiento d ON d.IdRequerimiento = r.Id
		";

		$params = [];
		if ($anio !== null) {
			$sql .= " WHERE r.Anio = ?";
			$params[] = $anio;
		}

		$sql .= "
			GROUP BY
				r.Id,
				r.IdCentroCosto,
				r.IdMetaSIAF,
				r.NroPedidoCompra,
				r.CodigoMeta,
				c.NombreCentroCosto,
				c.Siglas,
				r.Anio,
				r.Estado
			ORDER BY r.Anio DESC, r.NroPedidoCompra DESC
		";

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Obtiene los centros de costo activos ordenados por nombre.
	public function obtenerCentrosCosto()
	{
		$sql = "SELECT Id, NombreCentroCosto, Siglas FROM adquisiciones.CentroCosto WHERE Activo = 1 ORDER BY NombreCentroCosto";
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

	// Obtiene los sub-centros de costo activos, opcionalmente filtrados por centro.
	public function obtenerSubCentrosCostoActivos($idCentroCosto = null)
	{
		$sql = "
			SELECT
				Id,
				IdCentroCosto,
				NombreSubCentroCosto,
				Siglas
			FROM adquisiciones.SubCentroCosto
			WHERE Activo = 1
		";

		$params = [];
		if ((int) $idCentroCosto > 0) {
			$sql .= " AND IdCentroCosto = ?";
			$params[] = (int) $idCentroCosto;
		}

		$sql .= " ORDER BY IdCentroCosto, NombreSubCentroCosto";

		return $this->fetchAll($sql, $params);
	}

	// Lista todos los centros de costo para su gestion.
	public function listarCentrosCostoGestion()
	{
		$sql = "
			SELECT Id, Siglas, NombreCentroCosto, Activo
			FROM adquisiciones.CentroCosto
			ORDER BY NombreCentroCosto
		";

		return $this->fetchAll($sql);
	}

	// Registra un nuevo centro de costo activo.
	public function agregarCentroCosto($siglas, $nombreCentroCosto)
	{
		$siglasLimpio = strtoupper(trim((string) $siglas));
		$nombreLimpio = trim((string) $nombreCentroCosto);

		if ($siglasLimpio === '' || $nombreLimpio === '') {
			return ['success' => false, 'message' => 'Debe completar siglas y nombre del centro de costo.'];
		}

		if ($this->existeCentroCostoDuplicado($siglasLimpio, $nombreLimpio)) {
			return ['success' => false, 'message' => 'Ya existe un centro de costo con las mismas siglas o nombre.'];
		}

		$sql = "
			INSERT INTO adquisiciones.CentroCosto (Siglas, NombreCentroCosto, Activo)
			VALUES (?, ?, 1)
		";

		$stmt = sqlsrv_query($this->db, $sql, [$siglasLimpio, $nombreLimpio]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo registrar el centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Centro de costo registrado correctamente.'];
	}

	// Actualiza las siglas y el nombre de un centro de costo activo.
	public function actualizarCentroCosto($id, $siglas, $nombreCentroCosto)
	{
		$id = (int) $id;
		$siglasLimpio = strtoupper(trim((string) $siglas));
		$nombreLimpio = trim((string) $nombreCentroCosto);

		if ($id <= 0 || $siglasLimpio === '' || $nombreLimpio === '') {
			return ['success' => false, 'message' => 'Datos inválidos para actualizar el centro de costo.'];
		}

		if ($this->existeCentroCostoDuplicado($siglasLimpio, $nombreLimpio, $id)) {
			return ['success' => false, 'message' => 'Ya existe otro centro de costo con las mismas siglas o nombre.'];
		}

		$sql = "
			UPDATE adquisiciones.CentroCosto
			SET Siglas = ?, NombreCentroCosto = ?
			WHERE Id = ? AND Activo = 1
		";

		$stmt = sqlsrv_query($this->db, $sql, [$siglasLimpio, $nombreLimpio, $id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo actualizar el centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Centro de costo actualizado correctamente.'];
	}

	// Inactiva un centro de costo por su identificador.
	public function eliminarCentroCosto($id)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Centro de costo inválido.'];
		}

		$sql = "UPDATE adquisiciones.CentroCosto SET Activo = 0 WHERE Id = ? AND Activo = 1";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo inactivar el centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Centro de costo inactivado correctamente.'];
	}

	// Reactiva un centro de costo validando que no exista duplicado activo.
	public function activarCentroCosto($id)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Centro de costo inválido.'];
		}

		$sqlBuscar = "SELECT TOP 1 Siglas, NombreCentroCosto FROM adquisiciones.CentroCosto WHERE Id = ?";
		$stmtBuscar = sqlsrv_query($this->db, $sqlBuscar, [$id]);
		if ($stmtBuscar === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo validar el centro de costo' . $detalle];
		}

		$fila = sqlsrv_fetch_array($stmtBuscar, SQLSRV_FETCH_ASSOC);
		if (!$fila) {
			return ['success' => false, 'message' => 'No se encontró el centro de costo.'];
		}

		$siglas = trim((string) ($fila['Siglas'] ?? ''));
		$nombreCentroCosto = trim((string) ($fila['NombreCentroCosto'] ?? ''));
		if ($this->existeCentroCostoDuplicado($siglas, $nombreCentroCosto, $id)) {
			return ['success' => false, 'message' => 'No se puede activar porque ya existe otro centro de costo activo con las mismas siglas o nombre.'];
		}

		$sql = "UPDATE adquisiciones.CentroCosto SET Activo = 1 WHERE Id = ? AND Activo = 0";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo activar el centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Centro de costo activado correctamente.'];
	}

	// Verifica si existe un centro de costo activo con las mismas siglas o nombre.
	private function existeCentroCostoDuplicado($siglas, $nombreCentroCosto, $idExcluir = null)
	{
		$sql = "
			SELECT TOP 1 Id
			FROM adquisiciones.CentroCosto
			WHERE Activo = 1
			  AND (
				UPPER(LTRIM(RTRIM(Siglas))) = UPPER(LTRIM(RTRIM(?)))
				OR UPPER(LTRIM(RTRIM(NombreCentroCosto))) = UPPER(LTRIM(RTRIM(?)))
			  )
		";

		$params = [$siglas, $nombreCentroCosto];
		if ((int) $idExcluir > 0) {
			$sql .= " AND Id <> ?";
			$params[] = (int) $idExcluir;
		}

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return false;
		}

		return (bool) sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
	}

	// Obtiene los anios disponibles segun los requerimientos registrados.
	public function obtenerAniosDisponibles()
	{
		$sql = "SELECT DISTINCT Anio FROM adquisiciones.Requerimiento ORDER BY Anio DESC";
		$stmt = sqlsrv_query($this->db, $sql);

		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row['Anio'];
		}

		return $data;
	}

	// Registra un nuevo requerimiento con su centro de costo y meta asociada.
	public function guardarRequerimiento($datos)
	{
		$codigoMeta = adqNormalizarCodigoMeta($datos['CodigoMeta'] ?? null) ?? '000';
		$idMetaSiaf = isset($datos['IdMetaSIAF']) ? (int) $datos['IdMetaSIAF'] : 0;
		$idMetaSiaf = $idMetaSiaf > 0 ? $idMetaSiaf : null;

		if ($idMetaSiaf !== null && !$this->existeMetaSiafActivaPorId($idMetaSiaf)) {
			return false;
		}

		$sql = "
			INSERT INTO adquisiciones.Requerimiento
				(IdCentroCosto, IdMetaSIAF, NroPedidoCompra, CodigoMeta, Anio, FechaRegistro, Estado, idUsuarioRegistro)
			OUTPUT INSERTED.Id
			VALUES (?, ?, ?, ?, ?, GETDATE(), 0, ?)
		";

		$params = [
			$datos['IdCentroCosto'],
			$idMetaSiaf,
			$datos['NroPedidoCompra'],
			$codigoMeta,
			$datos['Anio'],
			$datos['idUsuarioRegistro'] ?? null
		];

		$stmt = sqlsrv_query($this->db, $sql, $params);

		if ($stmt === false) {
			return false;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

		return $row ? $row['Id'] : false;
	}

	// Obtiene los datos completos de un requerimiento por su identificador.
	public function obtenerRequerimientoPorId($id)
	{
		$sql = "
			SELECT
				r.Id,
				r.NroPedidoCompra,
				r.IdCentroCosto,
				r.IdMetaSIAF,
				r.CodigoMeta,
				c.NombreCentroCosto,
				c.Siglas,
				r.Anio,
				r.Estado,
				r.FechaRegistro
			FROM adquisiciones.Requerimiento r
			INNER JOIN adquisiciones.CentroCosto c ON c.Id = r.IdCentroCosto
			WHERE r.Id = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			return null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ? $row : null;
	}

	// Actualiza los datos principales de un requerimiento existente.
	public function actualizarRequerimiento($id, $datos)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return false;
		}

		$codigoMeta = adqNormalizarCodigoMeta($datos['CodigoMeta'] ?? null) ?? '000';
		$idMetaSiaf = isset($datos['IdMetaSIAF']) ? (int) $datos['IdMetaSIAF'] : 0;
		$idMetaSiaf = $idMetaSiaf > 0 ? $idMetaSiaf : null;

		if ($idMetaSiaf !== null && !$this->existeMetaSiafActivaPorId($idMetaSiaf)) {
			return false;
		}

		$sql = "
			UPDATE adquisiciones.Requerimiento
			SET IdCentroCosto = ?,
				IdMetaSIAF = ?,
				NroPedidoCompra = ?,
				CodigoMeta = ?,
				Anio = ?,
				idUsuarioModifica = ?,
				FechaModifica = GETDATE()
			WHERE Id = ?
		";

		$params = [
			$datos['IdCentroCosto'],
			$idMetaSiaf,
			$datos['NroPedidoCompra'],
			$codigoMeta,
			$datos['Anio'],
			$datos['idUsuarioModifica'] ?? null,
			$id,
		];

		$stmt = sqlsrv_query($this->db, $sql, $params);
		return $stmt !== false;
	}

	// Actualiza el estado de un requerimiento y registra la modificacion.
	public function actualizarEstado($id, $estado, $idUsuarioModifica = null)
	{
		$sql = "UPDATE adquisiciones.Requerimiento SET Estado = ?, idUsuarioModifica = ?, FechaModifica = GETDATE() WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$estado, $idUsuarioModifica, $id]);
		return $stmt !== false;
	}

	// Elimina un requerimiento junto con sus detalles asociados.
	public function eliminarRequerimiento($id)
	{
		if ((int) $id <= 0) {
			return false;
		}

		$sqlDetalle = "DELETE FROM adquisiciones.DetalleRequerimiento WHERE IdRequerimiento = ?";
		$sqlReq = "DELETE FROM adquisiciones.Requerimiento WHERE Id = ?";

		$inicioTransaccion = sqlsrv_begin_transaction($this->db);

		// Fallback: en algunos entornos SQLSRV begin_transaction puede fallar
		// por estado de conexión; intentamos eliminar sin transacción para
		// no bloquear el flujo del usuario.
		if ($inicioTransaccion === false) {
			$stmtDetalle = sqlsrv_query($this->db, $sqlDetalle, [$id]);
			if ($stmtDetalle === false) {
				return false;
			}

			$stmtReq = sqlsrv_query($this->db, $sqlReq, [$id]);
			if ($stmtReq === false) {
				return false;
			}

			return sqlsrv_rows_affected($stmtReq) > 0;
		}

		$stmtDetalle = sqlsrv_query($this->db, $sqlDetalle, [$id]);
		if ($stmtDetalle === false) {
			sqlsrv_rollback($this->db);
			return false;
		}

		$stmtReq = sqlsrv_query($this->db, $sqlReq, [$id]);
		if ($stmtReq === false) {
			sqlsrv_rollback($this->db);
			return false;
		}

		if (sqlsrv_rows_affected($stmtReq) <= 0) {
			sqlsrv_rollback($this->db);
			return false;
		}

		return sqlsrv_commit($this->db);
	}

	// Obtiene el consolidado de cantidades por equipo y centro de costo.
	public function obtenerConsolidado($anio = null)
	{
		// Consulta que obtiene equipos agrupados por centro/sub-centro de costo.
		// Si existe distribución por detalle, se toma el centro y subcentro de la tabla DistribucionDetalle.
		$sql = "
			SELECT 
				UPPER(
					CASE
						WHEN LTRIM(RTRIM(ISNULL(ct.Codigo, ''))) <> '' THEN LTRIM(RTRIM(ISNULL(ct.NombreGenerico, 'SIN CLASIFICAR'))) + ' (' + LTRIM(RTRIM(ct.Codigo)) + ')'
						ELSE LTRIM(RTRIM(ISNULL(ct.NombreGenerico, 'SIN CLASIFICAR')))
					END
				) AS Equipo,
				'' AS TipoSolicitud,
				c.Id AS IdCentroCosto,
				c.Siglas AS CentroCosto,
				dist.IdSubCentroCosto AS IdSubCentroCosto,
				sc.Siglas AS SubCentroCosto,
				SUM(dist.Cantidad) AS Cantidad
			FROM adquisiciones.DistribucionDetalle dist
			INNER JOIN adquisiciones.DetalleRequerimiento d ON d.Id = dist.IdDetalleRequerimiento
			INNER JOIN adquisiciones.Requerimiento r ON r.Id = d.IdRequerimiento
			INNER JOIN adquisiciones.CentroCosto c ON c.Id = dist.IdCentroCosto
			LEFT JOIN adquisiciones.SubCentroCosto sc ON sc.Id = dist.IdSubCentroCosto
			LEFT JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = d.IdCatalogoTecnologico AND ct.Activo = 1
		";

		$params = [];
		if ($anio !== null) {
			$sql .= " WHERE r.Anio = ?";
			$params[] = $anio;
		}

			$sql .= "
				GROUP BY ct.Codigo, ct.NombreGenerico, c.Id, c.Siglas, dist.IdSubCentroCosto, sc.Siglas
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
					Equipo, c.Siglas, sc.Siglas
			";

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			$errores = sqlsrv_errors();
			error_log('Error al obtener consolidado de adquisiciones: ' . print_r($errores, true));
			return [
				'equipos' => [],
				'centrosCosto' => [],
				'cabeceraCentros' => [],
				'matriz' => [],
				'tiposSolicitudPorEquipo' => [],
				'diagnostico' => [
					'estado' => 'error',
					'mensaje' => 'No se pudo consultar el consolidado. Revise el log de PHP/SQL Server para ver el detalle.',
				],
			];
		}

		$matriz = [];
		$equiposSet = [];
		$tiposSolicitudPorEquipo = [];
		$centrosUsados = [];

		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$equipo = $row['Equipo'];
			$tipoSolicitud = trim((string) ($row['TipoSolicitud'] ?? ''));
			$idCentroCosto = (int) ($row['IdCentroCosto'] ?? 0);
			$centroCosto = trim((string) ($row['CentroCosto'] ?? ''));
			$idSubCentroCosto = (int) ($row['IdSubCentroCosto'] ?? 0);
			$subCentroCosto = trim((string) ($row['SubCentroCosto'] ?? ''));
			$cantidad = (int)$row['Cantidad'];
			$claveColumna = $idSubCentroCosto > 0 ? 'SC_' . $idSubCentroCosto : 'CC_' . $idCentroCosto;

			if (!isset($matriz[$equipo])) {
				$matriz[$equipo] = [];
			}

			$matriz[$equipo][$claveColumna] = $cantidad;
			$centrosUsados[$idCentroCosto] = [
				'id' => $idCentroCosto,
				'siglas' => $centroCosto,
			];
			if (!isset($tiposSolicitudPorEquipo[$equipo]) || ($tiposSolicitudPorEquipo[$equipo] === '' && $tipoSolicitud !== '')) {
				$tiposSolicitudPorEquipo[$equipo] = $tipoSolicitud;
			}
			$equiposSet[$equipo] = true;
		}

		$diagnostico = ['estado' => 'ok'];
		if (empty($equiposSet)) {
			$diagnostico = $this->obtenerDiagnosticoConsolidado($anio);
		}

		$centrosOrdenados = array_values($centrosUsados);
		usort($centrosOrdenados, function ($a, $b) {
			return strcmp((string) ($a['siglas'] ?? ''), (string) ($b['siglas'] ?? ''));
		});

		$sqlSubCentros = "
			SELECT
				c.Id AS IdCentroCosto,
				c.Siglas AS CentroCosto,
				sc.Id AS IdSubCentroCosto,
				sc.Siglas AS SubCentroCosto
			FROM adquisiciones.CentroCosto c
			INNER JOIN adquisiciones.SubCentroCosto sc ON sc.IdCentroCosto = c.Id AND sc.Activo = 1
			WHERE c.Activo = 1
			ORDER BY c.Siglas, sc.Siglas
		";

		$subCentrosPorCentro = [];
		$stmtSub = sqlsrv_query($this->db, $sqlSubCentros);
		if ($stmtSub !== false) {
			while ($rowSub = sqlsrv_fetch_array($stmtSub, SQLSRV_FETCH_ASSOC)) {
				$idCentro = (int) ($rowSub['IdCentroCosto'] ?? 0);
				if ($idCentro <= 0) {
					continue;
				}
				if (!isset($subCentrosPorCentro[$idCentro])) {
					$subCentrosPorCentro[$idCentro] = [];
				}
				$subCentrosPorCentro[$idCentro][] = [
					'id' => (int) ($rowSub['IdSubCentroCosto'] ?? 0),
					'siglas' => trim((string) ($rowSub['SubCentroCosto'] ?? '')),
				];
			}
		}

		$equipos = array_keys($equiposSet);

		$cabeceraCentros = [];
		$centrosCosto = [];
		foreach ($centrosOrdenados as $centro) {
			$idCentro = (int) ($centro['id'] ?? 0);
			$siglasCentro = (string) ($centro['siglas'] ?? '');
			$subCentros = $subCentrosPorCentro[$idCentro] ?? [];

			if (!empty($subCentros)) {
				$columnasGrupo = [
					[
						'key' => 'CC_' . $idCentro,
						'label' => $siglasCentro,
					],
				];
				foreach ($subCentros as $subCentro) {
					$columnasGrupo[] = [
						'key' => 'SC_' . (int) $subCentro['id'],
						'label' => (string) $subCentro['siglas'],
					];
				}

				$cabeceraCentros[] = [
					'label' => $siglasCentro,
					'columnas' => $columnasGrupo,
				];

				foreach ($columnasGrupo as $columna) {
					$centrosCosto[] = $columna['key'];
				}
				continue;
			}

			$cabeceraCentros[] = [
				'label' => $siglasCentro,
				'columnas' => [
					[
						'key' => 'CC_' . $idCentro,
						'label' => $siglasCentro,
					],
				],
			];
			$centrosCosto[] = 'CC_' . $idCentro;
		}

		foreach ($equipos as $equipo) {
			foreach ($centrosCosto as $cc) {
				if (!isset($matriz[$equipo][$cc])) {
					$matriz[$equipo][$cc] = 0;
				}
			}
		}

		return [
			'equipos' => $equipos,
			'centrosCosto' => $centrosCosto,
			'cabeceraCentros' => $cabeceraCentros,
			'matriz' => $matriz,
			'tiposSolicitudPorEquipo' => $tiposSolicitudPorEquipo,
			'diagnostico' => $diagnostico,
		];
	}

	private function obtenerDiagnosticoConsolidado($anio = null)
	{
		$sql = "
			SELECT
				COUNT(DISTINCT r.Id) AS TotalRequerimientos,
				COUNT(DISTINCT d.Id) AS TotalDetalles,
				COUNT(dist.Id) AS TotalDistribuciones
			FROM adquisiciones.Requerimiento r
			LEFT JOIN adquisiciones.DetalleRequerimiento d ON d.IdRequerimiento = r.Id
			LEFT JOIN adquisiciones.DistribucionDetalle dist ON dist.IdDetalleRequerimiento = d.Id
		";

		$params = [];
		if ($anio !== null) {
			$sql .= " WHERE r.Anio = ?";
			$params[] = (int) $anio;
		}

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			error_log('Error al diagnosticar consolidado de adquisiciones: ' . print_r(sqlsrv_errors(), true));
			return [
				'estado' => 'error',
				'mensaje' => 'No se pudo diagnosticar el consolidado. Revise el log de PHP/SQL Server.',
			];
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		$totalRequerimientos = (int) ($row['TotalRequerimientos'] ?? 0);
		$totalDetalles = (int) ($row['TotalDetalles'] ?? 0);
		$totalDistribuciones = (int) ($row['TotalDistribuciones'] ?? 0);

		if ($totalRequerimientos <= 0) {
			return [
				'estado' => 'sin_requerimientos',
				'mensaje' => 'No hay requerimientos registrados para el año seleccionado.',
			];
		}

		if ($totalDetalles <= 0) {
			return [
				'estado' => 'sin_detalles',
				'mensaje' => 'Hay requerimientos para el año seleccionado, pero no tienen items registrados.',
			];
		}

		if ($totalDistribuciones <= 0) {
			return [
				'estado' => 'sin_distribuciones',
				'mensaje' => 'Hay items registrados para el año seleccionado, pero todavía no tienen distribución por centro de costo.',
			];
		}

		return [
			'estado' => 'sin_filas',
			'mensaje' => 'Hay distribuciones registradas, pero no se pudo armar la matriz del consolidado.',
		];
	}

	// Obtiene la cabecera completa de centros y sub-centros para el consolidado.
	public function obtenerCabeceraConsolidadoCompleta()
	{
		$sqlCentros = "
			SELECT
				c.Id,
				c.Siglas
			FROM adquisiciones.CentroCosto c
			WHERE c.Activo = 1
			ORDER BY c.Id
		";

		$centrosOrdenados = [];
		$stmtCentros = sqlsrv_query($this->db, $sqlCentros);
		if ($stmtCentros !== false) {
			while ($rowCentro = sqlsrv_fetch_array($stmtCentros, SQLSRV_FETCH_ASSOC)) {
				$idCentro = (int) ($rowCentro['Id'] ?? 0);
				if ($idCentro <= 0) {
					continue;
				}

				$centrosOrdenados[] = [
					'id' => $idCentro,
					'siglas' => trim((string) ($rowCentro['Siglas'] ?? '')),
				];
			}
			sqlsrv_free_stmt($stmtCentros);
		}

		$sqlSubCentros = "
			SELECT
				sc.Id AS IdSubCentroCosto,
				sc.IdCentroCosto,
				sc.Siglas AS SubCentroCosto
			FROM adquisiciones.SubCentroCosto sc
			INNER JOIN adquisiciones.CentroCosto c ON c.Id = sc.IdCentroCosto
			WHERE sc.Activo = 1 AND c.Activo = 1
			ORDER BY sc.IdCentroCosto, sc.Id
		";

		$subCentrosPorCentro = [];
		$stmtSub = sqlsrv_query($this->db, $sqlSubCentros);
		if ($stmtSub !== false) {
			while ($rowSub = sqlsrv_fetch_array($stmtSub, SQLSRV_FETCH_ASSOC)) {
				$idCentro = (int) ($rowSub['IdCentroCosto'] ?? 0);
				if ($idCentro <= 0) {
					continue;
				}
				if (!isset($subCentrosPorCentro[$idCentro])) {
					$subCentrosPorCentro[$idCentro] = [];
				}
				$subCentrosPorCentro[$idCentro][] = [
					'id' => (int) ($rowSub['IdSubCentroCosto'] ?? 0),
					'siglas' => trim((string) ($rowSub['SubCentroCosto'] ?? '')),
				];
			}
			sqlsrv_free_stmt($stmtSub);
		}

		$cabeceraCentros = [];
		$centrosCosto = [];
		foreach ($centrosOrdenados as $centro) {
			$idCentro = (int) ($centro['id'] ?? 0);
			$siglasCentro = (string) ($centro['siglas'] ?? '');
			$subCentros = $subCentrosPorCentro[$idCentro] ?? [];

			$columnasGrupo = [];
			if (!empty($subCentros)) {
				foreach ($subCentros as $subCentro) {
					$columnasGrupo[] = [
						'key' => 'SC_' . (int) $subCentro['id'],
						'label' => (string) $subCentro['siglas'],
					];
				}
			} else {
				$columnasGrupo[] = [
					'key' => 'CC_' . $idCentro,
					'label' => $siglasCentro,
				];
			}

			$cabeceraCentros[] = [
				'label' => $siglasCentro,
				'columnas' => $columnasGrupo,
			];

			foreach ($columnasGrupo as $columna) {
				$centrosCosto[] = $columna['key'];
			}
		}

		return [
			'cabeceraCentros' => $cabeceraCentros,
			'centrosCosto' => $centrosCosto,
		];
	}

	// Obtiene el consolidado en formato oficial agrupado por metas presupuestales.
	public function obtenerConsolidadoFormatoOficial($anio, $metasCabecera = [])
	{
		$metasNormalizadas = $this->normalizarMetasCabeceraOficial($metasCabecera);
		$selectMetas = [];
		$params = [];

		foreach ($metasNormalizadas as $meta) {
			$idMetaSiaf = (int) ($meta['IdMetaSIAF'] ?? 0);
			$codigoMeta = $meta['CodigoMeta'];
			$aliasMeta = $this->obtenerAliasMetaOficial($codigoMeta);
			$selectMetas[] = "
				SUM(
					CASE
						WHEN (
							(? > 0 AND r.IdMetaSIAF = ?)
							OR RIGHT('0000' + LTRIM(RTRIM(ISNULL(ms.CodigoMeta, ISNULL(r.CodigoMeta, '')))), 4) = RIGHT('0000' + ?, 4)
						)
						THEN dist.Cantidad
						ELSE 0
					END
				) AS [{$aliasMeta}]
			";
			$params[] = $idMetaSiaf;
			$params[] = $idMetaSiaf;
			$params[] = $codigoMeta;
		}

		$sqlSelectMetas = '';
		if (!empty($selectMetas)) {
			$sqlSelectMetas = implode(",\n", $selectMetas) . ",\n";
		}

		$sql = "
			SELECT
				UPPER(LTRIM(RTRIM(ISNULL(ct.Codigo, '')))) AS TipoCodigo,
				UPPER(LTRIM(RTRIM(ISNULL(ct.NombreGenerico, 'SIN TIPO')))) AS TipoNombre,
				'' AS Componente,
				'' AS Referencia,
				UPPER(LTRIM(RTRIM(ISNULL(MAX(d.UnidadMedida), '')))) AS UnidadMedida,
				MAX(pt.Monto) AS PrecioUnitario,
				{$sqlSelectMetas}
				SUM(dist.Cantidad) AS TotalInicial
			FROM adquisiciones.DistribucionDetalle dist
			INNER JOIN adquisiciones.DetalleRequerimiento d ON d.Id = dist.IdDetalleRequerimiento
			INNER JOIN adquisiciones.Requerimiento r ON r.Id = d.IdRequerimiento
				LEFT JOIN adquisiciones.MetaSIAF ms ON ms.Id = r.IdMetaSIAF
			LEFT JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = d.IdCatalogoTecnologico AND ct.Activo = 1
			LEFT JOIN adquisiciones.PresupuestoTecnologia pt
				ON pt.IdCatalogoTecnologico = d.IdCatalogoTecnologico
				AND pt.Anio = r.Anio
			WHERE r.Anio = ?
			GROUP BY
				ct.Id,
				ct.Codigo,
				ct.NombreGenerico
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

		$params[] = (int) $anio;
		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			error_log('Error al obtener consolidado oficial de adquisiciones: ' . print_r(sqlsrv_errors(), true));
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$fila = [
				'TipoCodigo' => (string) ($row['TipoCodigo'] ?? ''),
				'TipoNombre' => (string) ($row['TipoNombre'] ?? ''),
				'Componente' => (string) ($row['Componente'] ?? ''),
				'Referencia' => (string) ($row['Referencia'] ?? ''),
				'UnidadMedida' => (string) ($row['UnidadMedida'] ?? ''),
				'PrecioUnitario' => $row['PrecioUnitario'] !== null ? (float) $row['PrecioUnitario'] : null,
				'TotalInicial' => (int) ($row['TotalInicial'] ?? 0),
			];

			foreach ($metasNormalizadas as $meta) {
				$aliasMeta = $this->obtenerAliasMetaOficial($meta['CodigoMeta']);
				$fila[$aliasMeta] = (int) $this->obtenerValorAliasFila($row, $aliasMeta, 0);
			}

			$data[] = $fila;
		}

		sqlsrv_free_stmt($stmt);
		return $data;
	}

	// Obtiene el valor de una fila buscando posibles variantes de alias.
	private function obtenerValorAliasFila(array $row, $alias, $valorDefault = null)
	{
		if (array_key_exists($alias, $row)) {
			return $row[$alias];
		}

		foreach ($row as $clave => $valor) {
			if (strcasecmp((string) $clave, (string) $alias) === 0) {
				return $valor;
			}
		}

		return $valorDefault;
	}

	// Normaliza las metas usadas como cabecera del consolidado oficial.
	private function normalizarMetasCabeceraOficial($metasCabecera)
	{
		$salida = [];
		$vistos = [];

		if (!is_array($metasCabecera)) {
			return $salida;
		}

		foreach ($metasCabecera as $meta) {
			if (!is_array($meta)) {
				continue;
			}

			$codigo = $this->normalizarCodigoMetaSiaf($meta['CodigoMeta'] ?? null);
			if ($codigo === null) {
				continue;
			}

			$idMetaSiaf = isset($meta['IdMetaSIAF']) ? (int) $meta['IdMetaSIAF'] : 0;
			if ($idMetaSiaf <= 0 && isset($meta['Id'])) {
				$idMetaSiaf = (int) $meta['Id'];
			}

			if (isset($vistos[$codigo])) {
				continue;
			}

			$vistos[$codigo] = true;
			$salida[] = [
				'CodigoMeta' => $codigo,
				'IdMetaSIAF' => $idMetaSiaf,
			];
		}

		return $salida;
	}

	// Genera el alias de columna usado para una meta en el consolidado oficial.
	private function obtenerAliasMetaOficial($codigoMeta)
	{
		$codigo = preg_replace('/[^0-9]/', '', (string) $codigoMeta);
		$codigo = str_pad($codigo, 4, '0', STR_PAD_LEFT);
		return 'Meta' . $codigo;
	}

	// Ejecuta una consulta y devuelve todas sus filas como arreglo.
	private function fetchAll($sql, $params = [])
	{
		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		sqlsrv_free_stmt($stmt);
		return $data;
	}

	// Ejecuta una consulta y devuelve la primera fila encontrada.
	private function fetchOne($sql, $params = [])
	{
		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return [];
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		sqlsrv_free_stmt($stmt);

		return $row ? $row : [];
	}

	// Obtiene el resumen general de indicadores del dashboard para un anio.
	public function obtenerDashboardResumenGeneral($anio)
	{
		$sql = "
			SELECT
				COUNT(DISTINCT r.Id)                                          AS TotalRequerimientos,
				COUNT(DISTINCT CASE WHEN r.Estado = 1 THEN r.Id END)         AS Completos,
				COUNT(DISTINCT CASE WHEN r.Estado = 0 THEN r.Id END)         AS Pendientes,
				COUNT(DISTINCT dr.Id)                                         AS TotalItems,
				COUNT(DISTINCT CASE WHEN dr.IdCatalogoTecnologico IS NULL
										THEN dr.Id END)           AS SinHomologar
			FROM adquisiciones.Requerimiento r
			LEFT JOIN adquisiciones.DetalleRequerimiento dr
				ON dr.IdRequerimiento = r.Id
			WHERE r.Anio = ?
		";

		$resumen = $this->fetchOne($sql, [$anio]);

		return [
			'TotalRequerimientos' => (int) ($resumen['TotalRequerimientos'] ?? 0),
			'Completos' => (int) ($resumen['Completos'] ?? 0),
			'Pendientes' => (int) ($resumen['Pendientes'] ?? 0),
			'TotalItems' => (int) ($resumen['TotalItems'] ?? 0),
			'SinHomologar' => (int) ($resumen['SinHomologar'] ?? 0),
		];
	}

	// Obtiene el total de items del dashboard agrupado por tipo de solicitud.
	public function obtenerDashboardItemsPorTipo($anio)
	{
		$sql = "
			SELECT
				ct.Codigo        AS Tipo,
				ct.NombreGenerico,
				SUM(dr.Cantidad) AS TotalCantidad,
				COUNT(dr.Id)     AS TotalItems
			FROM adquisiciones.DetalleRequerimiento dr
			INNER JOIN adquisiciones.Requerimiento r
				ON r.Id = dr.IdRequerimiento
			INNER JOIN adquisiciones.CatalogoTecnologico ct
				ON ct.Id = dr.IdCatalogoTecnologico
			WHERE r.Anio = ?
			  AND dr.IdCatalogoTecnologico IS NOT NULL
			GROUP BY ct.Codigo, ct.NombreGenerico
			ORDER BY TotalCantidad DESC
		";

		$filas = $this->fetchAll($sql, [$anio]);
		foreach ($filas as &$fila) {
			$fila['TotalCantidad'] = (int) ($fila['TotalCantidad'] ?? 0);
			$fila['TotalItems'] = (int) ($fila['TotalItems'] ?? 0);
		}

		return $filas;
	}

	// Obtiene el resumen del dashboard agrupado por centro de costo.
	public function obtenerDashboardCentroCosto($anio)
	{
		$sql = "
			SELECT
				cc.Siglas,
				cc.NombreCentroCosto,
				COUNT(DISTINCT r.Id)  AS TotalRequerimientos,
				COUNT(DISTINCT dr.Id) AS TotalItems
			FROM adquisiciones.CentroCosto cc
			LEFT JOIN adquisiciones.Requerimiento r
				ON r.IdCentroCosto = cc.Id AND r.Anio = ?
			LEFT JOIN adquisiciones.DetalleRequerimiento dr
				ON dr.IdRequerimiento = r.Id
			GROUP BY cc.Siglas, cc.NombreCentroCosto
			ORDER BY TotalRequerimientos DESC
		";

		$filas = $this->fetchAll($sql, [$anio]);
		foreach ($filas as &$fila) {
			$fila['TotalRequerimientos'] = (int) ($fila['TotalRequerimientos'] ?? 0);
			$fila['TotalItems'] = (int) ($fila['TotalItems'] ?? 0);
		}

		return $filas;
	}

	// Obtiene el estado documental de las tecnologias para el dashboard.
	public function obtenerDashboardEstadoDocumental($anio)
	{
		$sql = "
			SELECT
				COUNT(DISTINCT ct.Id)                                             AS TotalTecnologias,
				COUNT(DISTINCT CASE WHEN ft.TotalFichas >= 2 THEN ct.Id END)      AS ConFichas,
				COUNT(DISTINCT CASE WHEN et.Id IS NOT NULL THEN ct.Id END)        AS ConEspecificacion,
				COUNT(DISTINCT CASE WHEN oc.Id IS NOT NULL THEN ct.Id END)        AS ConOrdenCompra,
				COUNT(DISTINCT CASE WHEN vt.Id IS NOT NULL THEN ct.Id END)        AS ConVerificacion,
				COUNT(DISTINCT CASE WHEN dr.IdCatalogoTecnologico IS NOT NULL THEN ct.Id END) AS ConRequerimiento,
				COUNT(DISTINCT CASE WHEN et.Id IS NOT NULL
												 AND oc.Id IS NOT NULL
												 AND vt.Id IS NOT NULL
												 AND ft.TotalFichas >= 2
								 THEN ct.Id END)              AS Completas
			FROM adquisiciones.CatalogoTecnologico ct
			LEFT JOIN adquisiciones.EspecificacionTecnica et
				ON et.IdCatalogoTecnologico = ct.Id AND et.Anio = ?
			LEFT JOIN adquisiciones.OrdenCompra oc
				ON oc.IdCatalogoTecnologico = ct.Id AND oc.Anio = ?
			LEFT JOIN adquisiciones.VerificacionTecnica vt
				ON vt.IdCatalogoTecnologico = ct.Id AND vt.Anio = ?
			LEFT JOIN (
				SELECT IdCatalogoTecnologico, COUNT(*) AS TotalFichas
				FROM adquisiciones.FichaTecnica
				WHERE Anio = ?
				GROUP BY IdCatalogoTecnologico
			) ft ON ft.IdCatalogoTecnologico = ct.Id
			LEFT JOIN (
				SELECT DISTINCT dr2.IdCatalogoTecnologico
				FROM adquisiciones.DetalleRequerimiento dr2
				INNER JOIN adquisiciones.Requerimiento r2 ON r2.Id = dr2.IdRequerimiento AND r2.Anio = ?
				WHERE dr2.IdCatalogoTecnologico IS NOT NULL
			) dr ON dr.IdCatalogoTecnologico = ct.Id
			WHERE ct.Activo = 1
		";

		$resumen = $this->fetchOne($sql, [$anio, $anio, $anio, $anio, $anio]);

		return [
			'TotalTecnologias'  => (int) ($resumen['TotalTecnologias'] ?? 0),
			'ConFichas'         => (int) ($resumen['ConFichas'] ?? 0),
			'ConEspecificacion' => (int) ($resumen['ConEspecificacion'] ?? 0),
			'ConOrdenCompra'    => (int) ($resumen['ConOrdenCompra'] ?? 0),
			'ConVerificacion'   => (int) ($resumen['ConVerificacion'] ?? 0),
			'ConRequerimiento'  => (int) ($resumen['ConRequerimiento'] ?? 0),
			'Completas'         => (int) ($resumen['Completas'] ?? 0),
		];
	}

	// Obtiene las ordenes de compra proximas a vencer para el dashboard.
	public function obtenerDashboardOrdenesProximas($anio, $diasVentana = 30, $limite = 5)
	{
		$diasVentana = max(1, (int) $diasVentana);
		$limite = max(1, (int) $limite);

		$sql = "
			SELECT TOP {$limite}
				oc.Id,
				oc.NumeroOrden,
				oc.FechaEntrega,
				ct.Codigo,
				ct.NombreGenerico,
				DATEDIFF(DAY, CAST(GETDATE() AS DATE), CAST(oc.FechaEntrega AS DATE)) AS DiasRestantes
			FROM adquisiciones.OrdenCompra oc
			INNER JOIN adquisiciones.CatalogoTecnologico ct
				ON ct.Id = oc.IdCatalogoTecnologico
			WHERE oc.Anio = ?
			  AND oc.FechaEntrega IS NOT NULL
			  AND CAST(oc.FechaEntrega AS DATE) >= CAST(GETDATE() AS DATE)
			  AND CAST(oc.FechaEntrega AS DATE) <= DATEADD(DAY, ?, CAST(GETDATE() AS DATE))
			ORDER BY oc.FechaEntrega ASC
		";

		$filas = $this->fetchAll($sql, [$anio, $diasVentana]);
		foreach ($filas as &$fila) {
			$fila['DiasRestantes'] = (int) ($fila['DiasRestantes'] ?? 0);
			if (isset($fila['FechaEntrega']) && $fila['FechaEntrega'] instanceof DateTime) {
				$fila['FechaEntrega'] = $fila['FechaEntrega']->format('Y-m-d');
			}
		}

		$sqlTotal = "
			SELECT COUNT(*) AS Total
			FROM adquisiciones.OrdenCompra oc
			WHERE oc.Anio = ?
			  AND oc.FechaEntrega IS NOT NULL
			  AND CAST(oc.FechaEntrega AS DATE) >= CAST(GETDATE() AS DATE)
			  AND CAST(oc.FechaEntrega AS DATE) <= DATEADD(DAY, ?, CAST(GETDATE() AS DATE))
		";

		$resumen = $this->fetchOne($sqlTotal, [$anio, $diasVentana]);

		return [
			'total' => (int) ($resumen['Total'] ?? 0),
			'diasVentana' => $diasVentana,
			'ordenes' => $filas,
		];
	}

	// Obtiene el resumen de metas SIAF activas e inactivas para el dashboard.
	public function obtenerDashboardMetaSiafResumen()
	{
		$sql = "
			SELECT
				COUNT(*) AS Total,
				SUM(CASE WHEN Activo = 1 THEN 1 ELSE 0 END) AS Activos,
				SUM(CASE WHEN Activo = 0 THEN 1 ELSE 0 END) AS Inactivos
			FROM adquisiciones.MetaSIAF
		";

		$resumen = $this->fetchOne($sql);

		return [
			'Total' => (int) ($resumen['Total'] ?? 0),
			'Activos' => (int) ($resumen['Activos'] ?? 0),
			'Inactivos' => (int) ($resumen['Inactivos'] ?? 0),
		];
	}

	// Obtiene el resumen de tipos de solicitud activos e inactivos para el dashboard.
	public function obtenerDashboardTipoSolicitudResumen()
	{
		$sql = "
			SELECT
				COUNT(*) AS Total,
				SUM(CASE WHEN Activo = 1 THEN 1 ELSE 0 END) AS Activos,
				SUM(CASE WHEN Activo = 0 THEN 1 ELSE 0 END) AS Inactivos
			FROM adquisiciones.TipoSolicitud
		";

		$resumen = $this->fetchOne($sql);

		return [
			'Total' => (int) ($resumen['Total'] ?? 0),
			'Activos' => (int) ($resumen['Activos'] ?? 0),
			'Inactivos' => (int) ($resumen['Inactivos'] ?? 0),
		];
	}

	// Lista todas las metas SIAF para su gestion.
	public function listarMetasSiafGestion()
	{
		$sql = "
			SELECT Id, CodigoMeta, Descripcion, Activo
			FROM adquisiciones.MetaSIAF
			ORDER BY CodigoMeta
		";

		return $this->fetchAll($sql);
	}

	// Obtiene las metas SIAF activas ordenadas por codigo.
	public function obtenerMetasSiafActivas()
	{
		$sql = "
			SELECT Id, CodigoMeta, Descripcion
			FROM adquisiciones.MetaSIAF
			WHERE Activo = 1
			ORDER BY CodigoMeta
		";

		return $this->fetchAll($sql);
	}

	// Obtiene las metas SIAF que forman la cabecera del consolidado por anio.
	public function obtenerMetasCabeceraConsolidado($anio)
	{
		$metas = $this->obtenerMetasSiafActivas();
		$mapa = [];

		foreach ($metas as $meta) {
			$codigo = $this->normalizarCodigoMetaSiaf($meta['CodigoMeta'] ?? null);
			if ($codigo === null) {
				continue;
			}

			$mapa[$codigo] = [
				'Id' => (int) ($meta['Id'] ?? 0),
				'CodigoMeta' => $codigo,
				'Descripcion' => trim((string) ($meta['Descripcion'] ?? '')),
			];
		}

		$sqlUsadas = "
			SELECT DISTINCT LTRIM(RTRIM(CodigoMeta)) AS CodigoMeta
			FROM adquisiciones.Requerimiento
			WHERE Anio = ?
			  AND NULLIF(LTRIM(RTRIM(ISNULL(CodigoMeta, ''))), '') IS NOT NULL
		";

		$usadas = $this->fetchAll($sqlUsadas, [(int) $anio]);
		foreach ($usadas as $fila) {
			$codigo = $this->normalizarCodigoMetaSiaf($fila['CodigoMeta'] ?? null);
			if ($codigo === null || isset($mapa[$codigo])) {
				continue;
			}

			$mapa[$codigo] = [
				'Id' => 0,
				'CodigoMeta' => $codigo,
				'Descripcion' => 'META ' . $codigo,
			];
		}

		$resultado = array_values($mapa);
		usort($resultado, function ($a, $b) {
			return strcmp((string) ($a['CodigoMeta'] ?? ''), (string) ($b['CodigoMeta'] ?? ''));
		});

		return $resultado;
	}

	// Registra una nueva meta SIAF activa.
	public function agregarMetaSiaf($codigoMeta, $descripcion, $idUsuarioRegistro = null)
	{
		$codigoMetaLimpio = $this->normalizarCodigoMetaSiaf($codigoMeta);
		$descripcionLimpia = trim((string) $descripcion);

		if ($codigoMetaLimpio === null) {
			return ['success' => false, 'message' => 'El código meta debe tener 3 o 4 dígitos numéricos.'];
		}

		if ($descripcionLimpia === '') {
			return ['success' => false, 'message' => 'Debe ingresar la descripción de la meta.'];
		}

		$metaExistente = $this->fetchOne(
			"SELECT TOP 1 Id, Activo FROM adquisiciones.MetaSIAF WHERE UPPER(LTRIM(RTRIM(CodigoMeta))) = UPPER(LTRIM(RTRIM(?)))",
			[$codigoMetaLimpio]
		);

		if (!empty($metaExistente)) {
			if ((int) ($metaExistente['Activo'] ?? 0) === 0) {
				return ['success' => false, 'message' => 'Ese código meta ya existe y está inactivo. Puede activarlo desde la lista.'];
			}

			return ['success' => false, 'message' => 'Ya existe una meta registrada con ese código.'];
		}

		$sql = "
			INSERT INTO adquisiciones.MetaSIAF (CodigoMeta, Descripcion, Activo, idUsuarioRegistro)
			VALUES (?, ?, 1, ?)
		";

		$stmt = sqlsrv_query($this->db, $sql, [$codigoMetaLimpio, $descripcionLimpia, $idUsuarioRegistro]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo registrar la meta SIAF' . $detalle];
		}

		return ['success' => true, 'message' => 'Meta SIAF registrada correctamente.'];
	}

	// Actualiza el codigo y la descripcion de una meta SIAF activa.
	public function actualizarMetaSiaf($id, $codigoMeta, $descripcion, $idUsuarioModifica = null)
	{
		$id = (int) $id;
		$codigoMetaLimpio = $this->normalizarCodigoMetaSiaf($codigoMeta);
		$descripcionLimpia = trim((string) $descripcion);

		if ($id <= 0) {
			return ['success' => false, 'message' => 'Meta SIAF inválida.'];
		}

		if ($codigoMetaLimpio === null) {
			return ['success' => false, 'message' => 'El código meta debe tener 3 o 4 dígitos numéricos.'];
		}

		if ($descripcionLimpia === '') {
			return ['success' => false, 'message' => 'Debe ingresar la descripción de la meta.'];
		}

		$metaDuplicada = $this->fetchOne(
			"SELECT TOP 1 Id FROM adquisiciones.MetaSIAF WHERE UPPER(LTRIM(RTRIM(CodigoMeta))) = UPPER(LTRIM(RTRIM(?))) AND Id <> ?",
			[$codigoMetaLimpio, $id]
		);

		if (!empty($metaDuplicada)) {
			return ['success' => false, 'message' => 'Ya existe otra meta registrada con ese código.'];
		}

		$sql = "
			UPDATE adquisiciones.MetaSIAF
			SET CodigoMeta = ?,
				Descripcion = ?,
				idUsuarioModifica = ?,
				FechaModifica = GETDATE()
			WHERE Id = ? AND Activo = 1
		";

		$stmt = sqlsrv_query($this->db, $sql, [$codigoMetaLimpio, $descripcionLimpia, $idUsuarioModifica, $id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo actualizar la meta SIAF' . $detalle];
		}

		return ['success' => true, 'message' => 'Meta SIAF actualizada correctamente.'];
	}

	// Inactiva una meta SIAF por su identificador.
	public function eliminarMetaSiaf($id, $idUsuarioModifica = null)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Meta SIAF inválida.'];
		}

		$sql = "
			UPDATE adquisiciones.MetaSIAF
			SET Activo = 0,
				idUsuarioModifica = ?,
				FechaModifica = GETDATE()
			WHERE Id = ? AND Activo = 1
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idUsuarioModifica, $id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo inactivar la meta SIAF' . $detalle];
		}

		return ['success' => true, 'message' => 'Meta SIAF inactivada correctamente.'];
	}

	// Reactiva una meta SIAF por su identificador.
	public function activarMetaSiaf($id, $idUsuarioModifica = null)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Meta SIAF inválida.'];
		}

		$sql = "
			UPDATE adquisiciones.MetaSIAF
			SET Activo = 1,
				idUsuarioModifica = ?,
				FechaModifica = GETDATE()
			WHERE Id = ? AND Activo = 0
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idUsuarioModifica, $id]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo activar la meta SIAF' . $detalle];
		}

		return ['success' => true, 'message' => 'Meta SIAF activada correctamente.'];
	}

	// Busca pedidos SIGA importables para el anio indicado.
	public function buscarPedidosSiga(int $anio): array
	{
		$sql = "
			SELECT 
				p.NRO_PEDIDO,
				cc.NOMBRE_DEPEND                              AS CENTRO_COSTO,
				p.FECHA_PEDIDO,
				COUNT(d.SECUENCIA)                            AS TOTAL_ITEMS,
				CASE WHEN r.Id IS NOT NULL THEN 1 ELSE 0 END  AS YA_IMPORTADO
			FROM BD_SIGA.dbo.SIG_PEDIDOS p
			JOIN BD_SIGA.dbo.SIG_CENTRO_COSTO cc
				ON  cc.ANO_EJE      = p.ANO_EJE
				AND cc.SEC_EJEC     = p.SEC_EJEC
				AND cc.CENTRO_COSTO = p.CENTRO_COSTO
			JOIN BD_SIGA.dbo.SIG_DETALLE_PEDIDOS d
				ON  d.ANO_EJE       = p.ANO_EJE
				AND d.sec_ejec      = p.SEC_EJEC
				AND d.TIPO_BIEN     = p.TIPO_BIEN
				AND d.TIPO_PEDIDO   = p.TIPO_PEDIDO
				AND d.NRO_PEDIDO    = p.NRO_PEDIDO
			LEFT JOIN adquisiciones.Requerimiento r
				ON  r.NroPedidoCompra = p.NRO_PEDIDO
				AND r.Anio            = p.ANO_EJE
			WHERE p.ANO_EJE     = ?
			  AND p.SEC_EJEC    = 1134
			  AND p.TIPO_BIEN   = 'B'
			  AND p.TIPO_PEDIDO = '2'
			  AND p.MOTIVO_PEDIDO LIKE 'EQUIPOS INFORMATICOS'
			GROUP BY
				p.NRO_PEDIDO,
				cc.NOMBRE_DEPEND,
				p.FECHA_PEDIDO,
				r.Id
			ORDER BY p.NRO_PEDIDO
		";

		$stmt = sqlsrv_query($this->db, $sql, [$anio]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			throw new Exception('Error consultando SIGA: ' . $errors[0]['message']);
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$fecha  = $row['FECHA_PEDIDO'];
			$data[] = [
				'NRO_PEDIDO'   => trim($row['NRO_PEDIDO']),
				'CENTRO_COSTO' => $row['CENTRO_COSTO'],
				'FECHA_PEDIDO' => $fecha instanceof DateTime
					? $fecha->format('d/m/Y')
					: substr($fecha, 0, 10),
				'TOTAL_ITEMS'  => (int) $row['TOTAL_ITEMS'],
				'YA_IMPORTADO' => (int) $row['YA_IMPORTADO'],
			];
		}

		return $data;
	}

	// Importa un pedido SIGA y registra sus items como requerimiento.
	public function importarPedidoSiga(string $nroPedido, int $anio, ?int $idUsuarioRegistro = null): array
	{
		// 1. Traer ítems del pedido desde SIGA
		$sql = "
			SELECT 
				cc.NOMBRE_DEPEND                                            AS NOMBRE_CENTRO_COSTO,
				RIGHT('0000' + CAST(ISNULL(p.sec_func, 0) AS VARCHAR(4)), 4) AS CODIGO_META,
				d.GRUPO_BIEN + d.CLASE_BIEN + d.FAMILIA_BIEN + d.ITEM_BIEN AS CODIGO_SIGA,
				REPLACE(
					REPLACE(
						REPLACE(d.CLASIFICADOR, '  ', '.'),
					' ', ''),
				'..', '.')                                                   AS CLASIFICADOR,
				c.NOMBRE_ITEM                                               AS DESCRIPCION,
				CAST(d.CANT_SOLICITADA AS INT)                              AS CANTIDAD,
				LEFT(um.NOMBRE, 5)                                          AS UNIDAD_MEDIDA
			FROM BD_SIGA.dbo.SIG_PEDIDOS p
			JOIN BD_SIGA.dbo.SIG_CENTRO_COSTO cc
				ON  cc.ANO_EJE      = p.ANO_EJE
				AND cc.SEC_EJEC     = p.SEC_EJEC
				AND cc.CENTRO_COSTO = p.CENTRO_COSTO
			JOIN BD_SIGA.dbo.SIG_DETALLE_PEDIDOS d
				ON  d.ANO_EJE       = p.ANO_EJE
				AND d.sec_ejec      = p.SEC_EJEC
				AND d.TIPO_BIEN     = p.TIPO_BIEN
				AND d.TIPO_PEDIDO   = p.TIPO_PEDIDO
				AND d.NRO_PEDIDO    = p.NRO_PEDIDO
			JOIN BD_SIGA.dbo.CATALOGO_BIEN_SERV c
				ON  c.SEC_EJEC      = p.SEC_EJEC
				AND c.TIPO_BIEN     = d.TIPO_BIEN
				AND c.GRUPO_BIEN    = d.GRUPO_BIEN
				AND c.CLASE_BIEN    = d.CLASE_BIEN
				AND c.FAMILIA_BIEN  = d.FAMILIA_BIEN
				AND c.ITEM_BIEN     = d.ITEM_BIEN
			JOIN BD_SIGA.dbo.UNIDAD_MEDIDA um
				ON  um.UNIDAD_MEDIDA = d.UNIDAD_MEDIDA
			WHERE p.ANO_EJE     = ?
			  AND p.SEC_EJEC    = 1134
			  AND p.TIPO_BIEN   = 'B'
			  AND p.TIPO_PEDIDO = '2'
			  AND p.NRO_PEDIDO  = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [$anio, $nroPedido]);
		if ($stmt === false) {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			throw new Exception('Error consultando SIGA: ' . $errors[0]['message']);
		}

		$items        = [];
		$nombreCentro = '';
		$codigoMeta   = null;
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$nombreCentro = $row['NOMBRE_CENTRO_COSTO'];
			if ($codigoMeta === null && isset($row['CODIGO_META'])) {
				$codigoMeta = adqNormalizarCodigoMeta((string) $row['CODIGO_META']);
			}
			$items[]      = $row;
		}
		$codigoMeta = $codigoMeta ?? '000';

		if (empty($items)) {
			throw new Exception('No se encontraron ítems para el pedido ' . $nroPedido);
		}

		// 2. Buscar IdCentroCosto
		$stmtCC  = sqlsrv_query($this->db, "
			SELECT Id FROM adquisiciones.CentroCosto
			WHERE NombreCentroCosto = ? AND Activo = 1
		", [$nombreCentro]);
		$rowCC   = sqlsrv_fetch_array($stmtCC, SQLSRV_FETCH_ASSOC);

		if (!$rowCC) {
			throw new Exception('Centro de costo no encontrado: ' . $nombreCentro);
		}
		$idCentro = $rowCC['Id'];
		$idMetaSiaf = null;

		// 3. Cargar homologaciones
		$stmtHom = sqlsrv_query($this->db, "
			SELECT CodigoSiga, IdCatalogoTecnologico
			FROM adquisiciones.HomologacionSiga
		");
		$homologaciones = [];
		while ($row = sqlsrv_fetch_array($stmtHom, SQLSRV_FETCH_ASSOC)) {
			$homologaciones[$row['CodigoSiga']] = $row['IdCatalogoTecnologico'];
		}

		// 4. Insertar requerimiento si no existe
		$stmtExiste = sqlsrv_query($this->db, "
			SELECT Id FROM adquisiciones.Requerimiento
			WHERE NroPedidoCompra = ? AND Anio = ?
		", [$nroPedido, $anio]);

		$rowExiste = sqlsrv_fetch_array($stmtExiste, SQLSRV_FETCH_ASSOC);
		$idReq     = $rowExiste ? $rowExiste['Id'] : null;

		$totalItems = 0;

		if (!$idReq) {
			$stmtIns = sqlsrv_query($this->db, "
				INSERT INTO adquisiciones.Requerimiento
					(IdCentroCosto, IdMetaSIAF, NroPedidoCompra, CodigoMeta, Anio, FechaRegistro, Estado, idUsuarioRegistro)
				OUTPUT INSERTED.Id
				VALUES (?, ?, ?, ?, ?, GETDATE(), 0, ?)
			", [$idCentro, $idMetaSiaf, $nroPedido, $codigoMeta, $anio, $idUsuarioRegistro]);

			if ($stmtIns === false) {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				throw new Exception('Error insertando requerimiento: ' . $errors[0]['message']);
			}

			$rowId = sqlsrv_fetch_array($stmtIns, SQLSRV_FETCH_ASSOC);
			$idReq = $rowId['Id'];
		} else {
			sqlsrv_query($this->db, "
				UPDATE adquisiciones.Requerimiento
				SET IdMetaSIAF = COALESCE(IdMetaSIAF, ?),
				    CodigoMeta = ?
				WHERE Id = ?
				  AND (
					(CodigoMeta IS NULL OR LTRIM(RTRIM(CodigoMeta)) = '')
					OR IdMetaSIAF IS NULL
				  )
			", [$idMetaSiaf, $codigoMeta, $idReq]);
		}

		// 5. Insertar ítems
		foreach ($items as $item) {
			$stmtExisteItem = sqlsrv_query($this->db, "
				SELECT Id FROM adquisiciones.DetalleRequerimiento
				WHERE IdRequerimiento = ? AND CodigoSiga = ?
			", [$idReq, $item['CODIGO_SIGA']]);

			$existeItem = sqlsrv_fetch_array($stmtExisteItem, SQLSRV_FETCH_ASSOC);

			if (!$existeItem) {
				$idCatalogo  = $homologaciones[$item['CODIGO_SIGA']] ?? null;
				$clasificador = isset($item['CLASIFICADOR']) ? trim((string) $item['CLASIFICADOR']) : '';
				$clasificador = $clasificador !== '' ? substr($clasificador, 0, 12) : null;

				$stmtInsItem = sqlsrv_query($this->db, "
					INSERT INTO adquisiciones.DetalleRequerimiento
						(IdRequerimiento, IdCatalogoTecnologico, CodigoSiga, Clasificador,
						 DescripcionDetallada, Cantidad, UnidadMedida, idUsuarioRegistro)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)
				", [
					$idReq,
					$idCatalogo,
					$item['CODIGO_SIGA'],
					$clasificador,
					$item['DESCRIPCION'],
					$item['CANTIDAD'],
					$item['UNIDAD_MEDIDA'],
					$idUsuarioRegistro
				]);

				if ($stmtInsItem === false) {
					$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
					throw new Exception('Error insertando ítem ' . $item['CODIGO_SIGA'] . ': ' . $errors[0]['message']);
				}

				$totalItems++;
			} else {
				$clasificador = isset($item['CLASIFICADOR']) ? trim((string) $item['CLASIFICADOR']) : '';
				$clasificador = $clasificador !== '' ? substr($clasificador, 0, 12) : null;

				if ($clasificador !== null) {
					sqlsrv_query($this->db, "
						UPDATE adquisiciones.DetalleRequerimiento
						SET Clasificador = ?
						WHERE Id = ?
						  AND (Clasificador IS NULL OR LTRIM(RTRIM(Clasificador)) = '')
					", [$clasificador, $existeItem['Id']]);
				}
			}
		}

		return ['items' => $totalItems];
	}

	// Normaliza un codigo de meta SIAF y valida su longitud.
	private function normalizarCodigoMetaSiaf($codigoMeta)
	{
		$valor = trim((string) $codigoMeta);
		if ($valor === '') {
			return null;
		}

		$valor = preg_replace('/\D/', '', $valor);
		$longitud = strlen($valor);
		if ($longitud < 3 || $longitud > 4) {
			return null;
		}

		return $valor;
	}

	// Obtiene el identificador de una meta SIAF activa por codigo.
	private function obtenerIdMetaSiafPorCodigo($codigoMeta = '000')
	{
		$codigo = trim((string) $codigoMeta);
		if ($codigo === '') {
			return null;
		}

		$sql = "
			SELECT TOP 1 Id
			FROM adquisiciones.MetaSIAF
			WHERE UPPER(LTRIM(RTRIM(CodigoMeta))) = UPPER(LTRIM(RTRIM(?)))
			  AND Activo = 1
		";

		$stmt = sqlsrv_query($this->db, $sql, [$codigo]);
		if ($stmt === false) {
			return null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ? (int) $row['Id'] : null;
	}

	// Verifica si existe una meta SIAF activa por identificador.
	private function existeMetaSiafActivaPorId($idMetaSiaf)
	{
		$idMetaSiaf = (int) $idMetaSiaf;
		if ($idMetaSiaf <= 0) {
			return false;
		}

		$sql = "
			SELECT TOP 1 Id
			FROM adquisiciones.MetaSIAF
			WHERE Id = ?
			  AND Activo = 1
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idMetaSiaf]);
		if ($stmt === false) {
			return false;
		}

		return (bool) sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
	}

	// ─── SubCentroCosto ───────────────────────────────────────────────────────

	// Obtiene el resumen de sub-centros de costo para el dashboard.
	public function obtenerDashboardSubCentrosCostoResumen()
	{
		$sql = "
			SELECT
				COUNT(*) AS Total,
				SUM(CASE WHEN Activo = 1 THEN 1 ELSE 0 END) AS Activos,
				SUM(CASE WHEN Activo = 0 THEN 1 ELSE 0 END) AS Inactivos
			FROM adquisiciones.SubCentroCosto
		";

		$resumen = $this->fetchOne($sql);

		return [
			'Total'    => (int) ($resumen['Total']    ?? 0),
			'Activos'  => (int) ($resumen['Activos']  ?? 0),
			'Inactivos'=> (int) ($resumen['Inactivos'] ?? 0),
		];
	}

	// Lista todos los sub-centros de costo para su gestion.
	public function listarSubCentrosCostoGestion()
	{
		$sql = "
			SELECT
				s.Id,
				s.IdCentroCosto,
				c.NombreCentroCosto,
				s.Siglas,
				s.NombreSubCentroCosto,
				s.Activo
			FROM adquisiciones.SubCentroCosto s
			INNER JOIN adquisiciones.CentroCosto c ON c.Id = s.IdCentroCosto
			ORDER BY c.NombreCentroCosto, s.NombreSubCentroCosto
		";

		return $this->fetchAll($sql);
	}

	// Registra un nuevo sub-centro de costo activo.
	public function agregarSubCentroCosto($idCentroCosto, $siglas, $nombreSubCentroCosto, $idUsuario = null)
	{
		$idCentroCosto = (int) $idCentroCosto;
		$siglasLimpio  = strtoupper(trim((string) $siglas));
		$nombreLimpio  = trim((string) $nombreSubCentroCosto);

		if ($idCentroCosto <= 0 || $siglasLimpio === '' || $nombreLimpio === '') {
			return ['success' => false, 'message' => 'Debe completar centro de costo, siglas y nombre.'];
		}

		if ($this->existeSubCentroCostoDuplicado($idCentroCosto, $siglasLimpio, $nombreLimpio)) {
			return ['success' => false, 'message' => 'Ya existe un sub-centro de costo con las mismas siglas o nombre en ese centro de costo.'];
		}

		$sql = "
			INSERT INTO adquisiciones.SubCentroCosto
				(IdCentroCosto, NombreSubCentroCosto, Siglas, Activo, idUsuarioRegistro, FechaRegistro)
			VALUES (?, ?, ?, 1, ?, GETDATE())
		";

		$params = [$idCentroCosto, $nombreLimpio, $siglasLimpio, $idUsuario];
		$stmt   = sqlsrv_query($this->db, $sql, $params);

		if ($stmt === false) {
			$errors  = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo registrar el sub-centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Sub-centro de costo registrado correctamente.'];
	}

	// Actualiza los datos de un sub-centro de costo activo.
	public function actualizarSubCentroCosto($id, $idCentroCosto, $siglas, $nombreSubCentroCosto, $idUsuario = null)
	{
		$id            = (int) $id;
		$idCentroCosto = (int) $idCentroCosto;
		$siglasLimpio  = strtoupper(trim((string) $siglas));
		$nombreLimpio  = trim((string) $nombreSubCentroCosto);

		if ($id <= 0 || $idCentroCosto <= 0 || $siglasLimpio === '' || $nombreLimpio === '') {
			return ['success' => false, 'message' => 'Datos inválidos para actualizar el sub-centro de costo.'];
		}

		if ($this->existeSubCentroCostoDuplicado($idCentroCosto, $siglasLimpio, $nombreLimpio, $id)) {
			return ['success' => false, 'message' => 'Ya existe otro sub-centro de costo con las mismas siglas o nombre en ese centro de costo.'];
		}

		$sql = "
			UPDATE adquisiciones.SubCentroCosto
			SET IdCentroCosto = ?, NombreSubCentroCosto = ?, Siglas = ?,
			    idUsuarioModifica = ?, FechaModifica = GETDATE()
			WHERE Id = ? AND Activo = 1
		";

		$params = [$idCentroCosto, $nombreLimpio, $siglasLimpio, $idUsuario, $id];
		$stmt   = sqlsrv_query($this->db, $sql, $params);

		if ($stmt === false) {
			$errors  = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo actualizar el sub-centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Sub-centro de costo actualizado correctamente.'];
	}

	// Inactiva un sub-centro de costo por su identificador.
	public function eliminarSubCentroCosto($id)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Sub-centro de costo inválido.'];
		}

		$sql  = "UPDATE adquisiciones.SubCentroCosto SET Activo = 0 WHERE Id = ? AND Activo = 1";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);

		if ($stmt === false) {
			$errors  = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo inactivar el sub-centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Sub-centro de costo eliminado correctamente.'];
	}

	// Reactiva un sub-centro de costo validando que no exista duplicado activo.
	public function activarSubCentroCosto($id)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return ['success' => false, 'message' => 'Sub-centro de costo inválido.'];
		}

		$sqlBuscar = "SELECT TOP 1 IdCentroCosto, Siglas, NombreSubCentroCosto FROM adquisiciones.SubCentroCosto WHERE Id = ?";
		$stmtB     = sqlsrv_query($this->db, $sqlBuscar, [$id]);

		if ($stmtB === false) {
			$errors  = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo validar el sub-centro de costo' . $detalle];
		}

		$fila = sqlsrv_fetch_array($stmtB, SQLSRV_FETCH_ASSOC);
		if (!$fila) {
			return ['success' => false, 'message' => 'No se encontró el sub-centro de costo.'];
		}

		$idCC  = (int) ($fila['IdCentroCosto'] ?? 0);
		$sig   = trim((string) ($fila['Siglas'] ?? ''));
		$nom   = trim((string) ($fila['NombreSubCentroCosto'] ?? ''));

		if ($this->existeSubCentroCostoDuplicado($idCC, $sig, $nom, $id)) {
			return ['success' => false, 'message' => 'No se puede activar porque ya existe otro sub-centro de costo activo con las mismas siglas o nombre.'];
		}

		$sql  = "UPDATE adquisiciones.SubCentroCosto SET Activo = 1 WHERE Id = ? AND Activo = 0";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);

		if ($stmt === false) {
			$errors  = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = is_array($errors) && count($errors) > 0 ? ' - ' . $errors[0]['message'] : '';
			return ['success' => false, 'message' => 'No se pudo activar el sub-centro de costo' . $detalle];
		}

		return ['success' => true, 'message' => 'Sub-centro de costo activado correctamente.'];
	}

	// Verifica si existe un sub-centro activo duplicado en el centro indicado.
	private function existeSubCentroCostoDuplicado($idCentroCosto, $siglas, $nombreSubCentroCosto, $idExcluir = null)
	{
		$sql = "
			SELECT TOP 1 Id
			FROM adquisiciones.SubCentroCosto
			WHERE Activo = 1
			  AND IdCentroCosto = ?
			  AND (
				UPPER(LTRIM(RTRIM(Siglas))) = UPPER(LTRIM(RTRIM(?)))
				OR UPPER(LTRIM(RTRIM(NombreSubCentroCosto))) = UPPER(LTRIM(RTRIM(?)))
			  )
		";

		$params = [(int) $idCentroCosto, $siglas, $nombreSubCentroCosto];
		if ((int) $idExcluir > 0) {
			$sql    .= " AND Id <> ?";
			$params[] = (int) $idExcluir;
		}

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return false;
		}

		return (bool) sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
	}
}
