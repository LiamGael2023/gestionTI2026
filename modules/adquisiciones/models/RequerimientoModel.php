<?php
class RequerimientoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listarRequerimientos($anio = null)
	{
		$sql = "
			SELECT
				r.Id,
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

	public function listarCentrosCostoGestion()
	{
		$sql = "
			SELECT Id, Siglas, NombreCentroCosto, Activo
			FROM adquisiciones.CentroCosto
			ORDER BY NombreCentroCosto
		";

		return $this->fetchAll($sql);
	}

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

	public function guardarRequerimiento($datos)
	{
		$codigoMeta = $this->normalizarCodigoMeta($datos['CodigoMeta'] ?? null);

		$sql = "
			INSERT INTO adquisiciones.Requerimiento
				(IdCentroCosto, NroPedidoCompra, CodigoMeta, Anio, FechaRegistro, Estado, idUsuarioRegistro)
			OUTPUT INSERTED.Id
			VALUES (?, ?, ?, ?, GETDATE(), 0, ?)
		";

		$params = [
			$datos['IdCentroCosto'],
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

	public function obtenerRequerimientoPorId($id)
	{
		$sql = "
			SELECT
				r.Id,
				r.NroPedidoCompra,
				r.IdCentroCosto,
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

	public function actualizarEstado($id, $estado, $idUsuarioModifica = null)
	{
		$sql = "UPDATE adquisiciones.Requerimiento SET Estado = ?, idUsuarioModifica = ?, FechaModifica = GETDATE() WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$estado, $idUsuarioModifica, $id]);
		return $stmt !== false;
	}

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

	public function obtenerConsolidado($anio = null)
	{
		// Consulta que obtiene equipos agrupados por centro de costo
		$sql = "
			SELECT 
				UPPER(LTRIM(RTRIM(ISNULL(ct.NombreGenerico, 'SIN CLASIFICAR')))) AS Equipo,
				c.Siglas AS CentroCosto,
				SUM(d.Cantidad) AS Cantidad
			FROM adquisiciones.DetalleRequerimiento d
			INNER JOIN adquisiciones.Requerimiento r ON r.Id = d.IdRequerimiento
			INNER JOIN adquisiciones.CentroCosto c ON c.Id = r.IdCentroCosto
			LEFT JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = d.IdCatalogoTecnologico
		";

		$params = [];
		if ($anio !== null) {
			$sql .= " WHERE r.Anio = ?";
			$params[] = $anio;
		}

		$sql .= "
			GROUP BY ct.NombreGenerico, c.Siglas
			ORDER BY Equipo, c.Siglas
		";

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return ['equipos' => [], 'centrosCosto' => [], 'matriz' => []];
		}

		// Procesar resultados en una estructura matricial
		$matriz = [];
		$centrosCostoSet = [];
		$equiposSet = [];

		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$equipo = $row['Equipo'];
			$centroCosto = $row['CentroCosto'];
			$cantidad = (int)$row['Cantidad'];

			if (!isset($matriz[$equipo])) {
				$matriz[$equipo] = [];
			}

			$matriz[$equipo][$centroCosto] = $cantidad;
			$centrosCostoSet[$centroCosto] = true;
			$equiposSet[$equipo] = true;
		}

		// Obtener lista ordenada de centros de costo
		$centrosCosto = array_keys($centrosCostoSet);
		sort($centrosCosto);

		// Obtener lista ordenada de equipos
		$equipos = array_keys($equiposSet);
		sort($equipos);

		// Asegurar que cada equipo tenga todas las columnas de centros de costo
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
			'matriz' => $matriz
		];
	}

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

	public function obtenerDashboardResumenGeneral($anio)
	{
		$sql = "
			SELECT
				COUNT(DISTINCT r.Id)                                          AS TotalRequerimientos,
				COUNT(DISTINCT CASE WHEN r.Estado = 1 THEN r.Id END)         AS Completos,
				COUNT(DISTINCT CASE WHEN r.Estado = 0 THEN r.Id END)         AS Pendientes,
				COUNT(DISTINCT dr.Id)                                         AS TotalItems,
				COUNT(DISTINCT CASE WHEN dr.IdCatalogoTecnologico = 1000010
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
			  AND dr.IdCatalogoTecnologico <> 1000010
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
				WHERE dr2.IdCatalogoTecnologico <> 1000010
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

	public function importarPedidoSiga(string $nroPedido, int $anio, ?int $idUsuarioRegistro = null): array
	{
		// 1. Traer ítems del pedido desde SIGA
		$sql = "
			SELECT 
				cc.NOMBRE_DEPEND                                            AS NOMBRE_CENTRO_COSTO,
				RIGHT('0000' + CAST(ISNULL(p.sec_func, 0) AS VARCHAR(4)), 4) AS CODIGO_META,
				d.GRUPO_BIEN + d.CLASE_BIEN + d.FAMILIA_BIEN + d.ITEM_BIEN AS CODIGO_SIGA,
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
				$codigoMeta = $this->normalizarCodigoMeta((string) $row['CODIGO_META']);
			}
			$items[]      = $row;
		}

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
					(IdCentroCosto, NroPedidoCompra, CodigoMeta, Anio, FechaRegistro, Estado, idUsuarioRegistro)
				OUTPUT INSERTED.Id
				VALUES (?, ?, ?, ?, GETDATE(), 0, ?)
			", [$idCentro, $nroPedido, $codigoMeta, $anio, $idUsuarioRegistro]);

			if ($stmtIns === false) {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				throw new Exception('Error insertando requerimiento: ' . $errors[0]['message']);
			}

			$rowId = sqlsrv_fetch_array($stmtIns, SQLSRV_FETCH_ASSOC);
			$idReq = $rowId['Id'];
		} elseif ($codigoMeta !== null) {
			sqlsrv_query($this->db, "
				UPDATE adquisiciones.Requerimiento
				SET CodigoMeta = ?
				WHERE Id = ?
				  AND (CodigoMeta IS NULL OR LTRIM(RTRIM(CodigoMeta)) = '')
			", [$codigoMeta, $idReq]);
		}

		// 5. Insertar ítems
		foreach ($items as $item) {
			$stmtExisteItem = sqlsrv_query($this->db, "
				SELECT Id FROM adquisiciones.DetalleRequerimiento
				WHERE IdRequerimiento = ? AND CodigoSiga = ?
			", [$idReq, $item['CODIGO_SIGA']]);

			$existeItem = sqlsrv_fetch_array($stmtExisteItem, SQLSRV_FETCH_ASSOC);

			if (!$existeItem) {
				$idCatalogo  = $homologaciones[$item['CODIGO_SIGA']] ?? 1000010;

				$stmtInsItem = sqlsrv_query($this->db, "
					INSERT INTO adquisiciones.DetalleRequerimiento
						(IdRequerimiento, IdCatalogoTecnologico, CodigoSiga,
						 DescripcionDetallada, Cantidad, UnidadMedida, idUsuarioRegistro)
					VALUES (?, ?, ?, ?, ?, ?, ?)
				", [
					$idReq,
					$idCatalogo,
					$item['CODIGO_SIGA'],
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
			}
		}

		return ['items' => $totalItems];
	}

	private function normalizarCodigoMeta($codigoMeta)
	{
		$valor = strtoupper(trim((string) $codigoMeta));
		if ($valor === '') {
			return null;
		}

		$valor = preg_replace('/[^A-Z0-9]/', '', $valor);
		if ($valor === '') {
			return null;
		}

		return substr($valor, 0, 4);
	}
}
