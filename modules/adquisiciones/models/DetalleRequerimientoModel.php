<?php
class DetalleRequerimientoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	// Lista todos los detalles de un requerimiento con información de tecnología
	public function listarDetallesPorRequerimiento($idRequerimiento)
	{
		$sql = "
			SELECT
				d.Id,
				d.IdRequerimiento,
				d.IdCatalogoTecnologico,
				-- Sin homologar
				ISNULL(ct.Codigo, '') AS CodigoTecnologia,
				d.CodigoSiga,
				d.Clasificador,
				d.DescripcionDetallada,
				d.Cantidad,
				d.UnidadMedida
			FROM adquisiciones.DetalleRequerimiento d
			LEFT JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = d.IdCatalogoTecnologico
			WHERE d.IdRequerimiento = ?
			ORDER BY d.Id
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idRequerimiento]);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	// Guarda un nuevo detalle de requerimiento retornando su ID generado
	public function guardarDetalle($datos)
	{
		$sql = "INSERT INTO adquisiciones.DetalleRequerimiento 
		        (IdRequerimiento, IdCatalogoTecnologico, CodigoSiga, Clasificador, DescripcionDetallada, Cantidad, UnidadMedida, idUsuarioRegistro) 
		        VALUES (?, ?, ?, ?, ?, ?, ?, ?); 
		        SELECT SCOPE_IDENTITY() AS Id;";
		
		$params = [
			$datos['IdRequerimiento'],
			$datos['IdCatalogoTecnologico'],
			$datos['CodigoSiga'],
			$datos['Clasificador'] ?? null,
			$datos['DescripcionDetallada'],
			$datos['Cantidad'],
			$datos['UnidadMedida'],
			$datos['idUsuarioRegistro'] ?? null
		];

		$stmt = sqlsrv_query($this->db, $sql, $params);
		
		if ($stmt === false) {
			return false;
		}

		sqlsrv_next_result($stmt);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		
		return $row ? $row['Id'] : false;
	}

	// Actualiza los datos de un detalle de requerimiento existente
	public function actualizarDetalle($id, $datos)
	{
		$sql = "UPDATE adquisiciones.DetalleRequerimiento 
		        SET IdCatalogoTecnologico = ?,
		            CodigoSiga = ?, 
		            Clasificador = ?,
		            DescripcionDetallada = ?, 
		            Cantidad = ?, 
		            UnidadMedida = ?,
		            idUsuarioModifica = ?,
		            FechaModifica = GETDATE()
		        WHERE Id = ?";
		
		$params = [
			$datos['IdCatalogoTecnologico'],
			$datos['CodigoSiga'],
			$datos['Clasificador'] ?? null,
			$datos['DescripcionDetallada'],
			$datos['Cantidad'],
			$datos['UnidadMedida'],
			$datos['idUsuarioModifica'] ?? null,
			$id
		];

		$stmt = sqlsrv_query($this->db, $sql, $params);
		return $stmt !== false;
	}

	// Elimina un detalle de requerimiento por ID
	public function eliminarDetalle($id)
	{
		$sql = "DELETE FROM adquisiciones.DetalleRequerimiento WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		return $stmt !== false;
	}

	// Busca datos usados previamente para autocompletar un item por Codigo SIGA
	public function obtenerDatosPorCodigoSiga($codigoSiga, $idRequerimientoExcluir = null)
	{
		$codigoSiga = preg_replace('/\D/', '', trim((string) $codigoSiga));
		if ($codigoSiga === '') {
			return null;
		}

		$params = [$codigoSiga];
		$filtroRequerimiento = '';
		if ((int) $idRequerimientoExcluir > 0) {
			$filtroRequerimiento = ' AND d.IdRequerimiento <> ?';
			$params[] = (int) $idRequerimientoExcluir;
		}

		$sql = "
			SELECT TOP 1
				d.CodigoSiga,
				d.Clasificador,
				d.DescripcionDetallada,
				d.Cantidad,
				d.UnidadMedida,
				d.IdCatalogoTecnologico,
				ISNULL(ct.Codigo, '') AS CodigoTecnologia
			FROM adquisiciones.DetalleRequerimiento d
			LEFT JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = d.IdCatalogoTecnologico
			WHERE d.CodigoSiga = ?
			  AND ISNULL(LTRIM(RTRIM(d.DescripcionDetallada)), '') <> ''
			  $filtroRequerimiento
			ORDER BY d.Id DESC
		";

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt !== false) {
			$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
			if ($row) {
				return [
					'CodigoSiga' => (string) ($row['CodigoSiga'] ?? ''),
					'Clasificador' => (string) ($row['Clasificador'] ?? ''),
					'DescripcionDetallada' => (string) ($row['DescripcionDetallada'] ?? ''),
					'Cantidad' => isset($row['Cantidad']) ? (int) $row['Cantidad'] : 0,
					'UnidadMedida' => (string) ($row['UnidadMedida'] ?? 'UND'),
					'IdCatalogoTecnologico' => isset($row['IdCatalogoTecnologico']) ? (int) $row['IdCatalogoTecnologico'] : 0,
					'CodigoTecnologia' => (string) ($row['CodigoTecnologia'] ?? ''),
				];
			}
		}

		return $this->obtenerDatosSigaPorCodigo($codigoSiga);
	}

	private function obtenerHomologacionPorCodigoSiga($codigoSiga)
	{
		$sql = "
			SELECT TOP 1
				h.IdCatalogoTecnologico,
				ISNULL(ct.Codigo, '') AS CodigoTecnologia
			FROM adquisiciones.HomologacionSiga h
			LEFT JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = h.IdCatalogoTecnologico
			WHERE h.CodigoSiga = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [$codigoSiga]);
		if ($stmt === false) {
			return ['IdCatalogoTecnologico' => 0, 'CodigoTecnologia' => ''];
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		if (!$row) {
			return ['IdCatalogoTecnologico' => 0, 'CodigoTecnologia' => ''];
		}

		return [
			'IdCatalogoTecnologico' => isset($row['IdCatalogoTecnologico']) ? (int) $row['IdCatalogoTecnologico'] : 0,
			'CodigoTecnologia' => (string) ($row['CodigoTecnologia'] ?? ''),
		];
	}

	private function obtenerDatosSigaPorCodigo($codigoSiga)
	{
		$homologacion = $this->obtenerHomologacionPorCodigoSiga($codigoSiga);
		if (strlen($codigoSiga) !== 12) {
			return $homologacion['IdCatalogoTecnologico'] > 0 ? [
				'CodigoSiga' => $codigoSiga,
				'Clasificador' => '',
				'DescripcionDetallada' => '',
				'Cantidad' => 1,
				'UnidadMedida' => 'UND',
				'IdCatalogoTecnologico' => $homologacion['IdCatalogoTecnologico'],
				'CodigoTecnologia' => $homologacion['CodigoTecnologia'],
			] : null;
		}

		$grupoBien = substr($codigoSiga, 0, 2);
		$claseBien = substr($codigoSiga, 2, 2);
		$familiaBien = substr($codigoSiga, 4, 4);
		$itemBien = substr($codigoSiga, 8, 4);
		$sql = "
			SELECT TOP 1
				c.NOMBRE_ITEM AS DESCRIPCION,
				REPLACE(
					REPLACE(
						REPLACE(ISNULL(d.CLASIFICADOR, ''), '  ', '.'),
					' ', ''),
				'..', '.') AS CLASIFICADOR,
				CAST(ISNULL(d.CANT_SOLICITADA, 1) AS INT) AS CANTIDAD,
				LEFT(ISNULL(um.NOMBRE, 'UND'), 10) AS UNIDAD_MEDIDA
			FROM BD_SIGA.dbo.CATALOGO_BIEN_SERV c
			LEFT JOIN BD_SIGA.dbo.SIG_DETALLE_PEDIDOS d
				ON  d.SEC_EJEC     = c.SEC_EJEC
				AND d.TIPO_BIEN    = c.TIPO_BIEN
				AND d.GRUPO_BIEN   = c.GRUPO_BIEN
				AND d.CLASE_BIEN   = c.CLASE_BIEN
				AND d.FAMILIA_BIEN = c.FAMILIA_BIEN
				AND d.ITEM_BIEN    = c.ITEM_BIEN
			LEFT JOIN BD_SIGA.dbo.UNIDAD_MEDIDA um
				ON um.UNIDAD_MEDIDA = d.UNIDAD_MEDIDA
			WHERE RIGHT('00' + LTRIM(RTRIM(CAST(c.GRUPO_BIEN AS VARCHAR(10)))), 2) = ?
			  AND RIGHT('00' + LTRIM(RTRIM(CAST(c.CLASE_BIEN AS VARCHAR(10)))), 2) = ?
			  AND RIGHT('0000' + LTRIM(RTRIM(CAST(c.FAMILIA_BIEN AS VARCHAR(10)))), 4) = ?
			  AND RIGHT('0000' + LTRIM(RTRIM(CAST(c.ITEM_BIEN AS VARCHAR(10)))), 4) = ?
			ORDER BY d.ANO_EJE DESC, d.NRO_PEDIDO DESC
		";

		$stmt = sqlsrv_query($this->db, $sql, [$grupoBien, $claseBien, $familiaBien, $itemBien]);
		if ($stmt === false) {
			return $homologacion['IdCatalogoTecnologico'] > 0 ? [
				'CodigoSiga' => $codigoSiga,
				'Clasificador' => '',
				'DescripcionDetallada' => '',
				'Cantidad' => 1,
				'UnidadMedida' => 'UND',
				'IdCatalogoTecnologico' => $homologacion['IdCatalogoTecnologico'],
				'CodigoTecnologia' => $homologacion['CodigoTecnologia'],
			] : null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		if (!$row && $homologacion['IdCatalogoTecnologico'] <= 0) {
			return null;
		}

		return [
			'CodigoSiga' => $codigoSiga,
			'Clasificador' => (string) ($row['CLASIFICADOR'] ?? ''),
			'DescripcionDetallada' => (string) ($row['DESCRIPCION'] ?? ''),
			'Cantidad' => isset($row['CANTIDAD']) ? (int) $row['CANTIDAD'] : 1,
			'UnidadMedida' => (string) ($row['UNIDAD_MEDIDA'] ?? 'UND'),
			'IdCatalogoTecnologico' => $homologacion['IdCatalogoTecnologico'],
			'CodigoTecnologia' => $homologacion['CodigoTecnologia'],
		];
	}

	// Lista todas las tecnologÃ­as activas del catÃ¡logo como opciones de selecciÃ³n
	public function listarOpcionesCatalogoTecnologico()
	{
		$sql = "SELECT Id, Codigo, NombreGenerico FROM adquisiciones.CatalogoTecnologico WHERE Activo = 1";
		$stmt = sqlsrv_query($this->db, $sql);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = [
				'Id' => (int) $row['Id'],
				'Codigo' => (string) $row['Codigo'],
				'NombreGenerico' => (string) $row['NombreGenerico'],
			];
		}

		usort($data, static function ($a, $b) {
			$comparacionCodigo = strnatcasecmp($a['Codigo'], $b['Codigo']);
			if ($comparacionCodigo !== 0) {
				return $comparacionCodigo;
			}

			return strcasecmp($a['NombreGenerico'], $b['NombreGenerico']);
		});

		return $data;
	}
}
