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
					WHEN et.Id IS NOT NULL AND vt.Id IS NOT NULL AND cf.Id IS NOT NULL AND ft.TotalFichas >= 4 THEN 1
					ELSE 0
				END AS EstadoCompleto
			FROM adquisiciones.DetalleRequerimiento d
			INNER JOIN adquisiciones.Requerimiento r ON r.Id = d.IdRequerimiento
			INNER JOIN adquisiciones.CatalogoTecnologico ct ON ct.Id = d.IdCatalogoTecnologico
			LEFT JOIN adquisiciones.EspecificacionTecnica et 
				ON et.IdCatalogoTecnologico = ct.Id AND et.Anio = ?
			LEFT JOIN adquisiciones.VerificacionTecnica vt
				ON vt.IdCatalogoTecnologico = ct.Id AND vt.Anio = ?
			LEFT JOIN adquisiciones.Conformidad cf
				ON cf.IdCatalogoTecnologico = ct.Id AND cf.Anio = ?
			LEFT JOIN (
				SELECT IdCatalogoTecnologico, Anio, COUNT(*) AS TotalFichas
				FROM adquisiciones.FichaTecnica
				WHERE Anio = ?
				GROUP BY IdCatalogoTecnologico, Anio
			) ft ON ft.IdCatalogoTecnologico = ct.Id AND ft.Anio = ?
			WHERE r.Anio = ? AND ct.Activo = 1
			GROUP BY ct.Codigo, ct.NombreGenerico, ct.Id, et.Id, vt.Id, cf.Id, ft.TotalFichas
			ORDER BY ct.Codigo, ct.NombreGenerico
		";

		$stmt = sqlsrv_query($this->db, $sql, [$anioConsulta, $anioConsulta, $anioConsulta, $anioConsulta, $anioConsulta, $anioConsulta, $anioConsulta]);
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
			UNION
			SELECT YEAR(GETDATE()) AS Anio
			ORDER BY Anio DESC
		";

		$stmt = sqlsrv_query($this->db, $sql);
		if ($stmt === false) {
			return [(int) date('Y')];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$data[] = (int) $row['Anio'];
		}

		return $data;
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
}
