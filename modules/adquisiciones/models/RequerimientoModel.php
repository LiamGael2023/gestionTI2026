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
		$sql = "
			INSERT INTO adquisiciones.Requerimiento
				(IdCentroCosto, NroPedidoCompra, Anio, FechaRegistro, Estado)
			OUTPUT INSERTED.Id
			VALUES (?, ?, ?, GETDATE(), 0)
		";

		$params = [
			$datos['IdCentroCosto'],
			$datos['NroPedidoCompra'],
			$datos['Anio']
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

	public function actualizarEstado($id, $estado)
	{
		$sql = "UPDATE adquisiciones.Requerimiento SET Estado = ? WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$estado, $id]);
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
			LEFT JOIN BD_GESTION_TI.adquisiciones.Requerimiento r
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

	public function importarPedidoSiga(string $nroPedido, int $anio): array
	{
		// 1. Traer ítems del pedido desde SIGA
		$sql = "
			SELECT 
				cc.NOMBRE_DEPEND                                            AS NOMBRE_CENTRO_COSTO,
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
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$nombreCentro = $row['NOMBRE_CENTRO_COSTO'];
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
					(IdCentroCosto, NroPedidoCompra, Anio, FechaRegistro, Estado)
				OUTPUT INSERTED.Id
				VALUES (?, ?, ?, GETDATE(), 0)
			", [$idCentro, $nroPedido, $anio]);

			if ($stmtIns === false) {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				throw new Exception('Error insertando requerimiento: ' . $errors[0]['message']);
			}

			$rowId = sqlsrv_fetch_array($stmtIns, SQLSRV_FETCH_ASSOC);
			$idReq = $rowId['Id'];
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
						 DescripcionDetallada, Cantidad, UnidadMedida)
					VALUES (?, ?, ?, ?, ?, ?)
				", [
					$idReq,
					$idCatalogo,
					$item['CODIGO_SIGA'],
					$item['DESCRIPCION'],
					$item['CANTIDAD'],
					$item['UNIDAD_MEDIDA']
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
}
