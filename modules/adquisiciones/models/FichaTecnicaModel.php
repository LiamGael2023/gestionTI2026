<?php
class FichaTecnicaModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listarPorTecnologia($idCatalogoTecnologico, $anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');

		$sql = "
			SELECT Id, IdCatalogoTecnologico, Marca, Modelo, Anio, Estado, Documento, FechaRegistro
			FROM adquisiciones.FichaTecnica
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
			ORDER BY FechaRegistro DESC
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anioConsulta]);
		if ($stmt === false) {
			return [];
		}

		$data = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			if ($row['FechaRegistro'] instanceof DateTime) {
				$row['FechaRegistro'] = $row['FechaRegistro']->format('d/m/Y H:i');
			}
			$data[] = $row;
		}

		return $data;
	}

	public function contarPorTecnologia($idCatalogoTecnologico, $anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');

		$sql = "
			SELECT COUNT(1) AS Total
			FROM adquisiciones.FichaTecnica
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anioConsulta]);
		if ($stmt === false) {
			return 0;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

		return $row ? (int) $row['Total'] : 0;
	}

	public function guardar($datos)
	{
		$sql = "
			INSERT INTO adquisiciones.FichaTecnica (IdCatalogoTecnologico, Marca, Modelo, Anio, Estado, Documento, FechaRegistro)
			VALUES (?, ?, ?, ?, 0, ?, GETDATE());
			SELECT SCOPE_IDENTITY() AS Id;
		";

		$params = [
			$datos['IdCatalogoTecnologico'],
			$datos['Marca'],
			$datos['Modelo'],
			$datos['Anio'],
			[$datos['Documento'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')]
		];

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return false;
		}

		sqlsrv_next_result($stmt);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ? (int) $row['Id'] : false;
	}

	public function cambiarEstado($id, $estado)
	{
		$sql = "UPDATE adquisiciones.FichaTecnica SET Estado = ? WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$estado, $id]);
		return $stmt !== false;
	}

	public function eliminar($id)
	{
		$sql = "DELETE FROM adquisiciones.FichaTecnica WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		return $stmt !== false;
	}
}
