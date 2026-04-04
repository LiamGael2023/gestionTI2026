<?php
class PresupuestoTecnologicoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function obtenerPorTecnologiaYAnio($idCatalogoTecnologico, $anio)
	{
		$sql = "
			SELECT Id, IdCatalogoTecnologico, Anio, Monto
			FROM adquisiciones.PresupuestoTecnologia
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anio]);
		if (!$stmt) {
			return null;
		}
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		sqlsrv_free_stmt($stmt);
		return $row ?: null;
	}

	public function guardar($datos)
	{
		$sql = "
			INSERT INTO adquisiciones.PresupuestoTecnologia
				(IdCatalogoTecnologico, Anio, Monto, idUsuarioRegistro)
			VALUES (?, ?, ?, ?)
		";
		$params = [
			(int) $datos['IdCatalogoTecnologico'],
			(int) $datos['Anio'],
			isset($datos['Monto']) && $datos['Monto'] !== null && $datos['Monto'] !== '' ? (float) $datos['Monto'] : null,
			isset($datos['idUsuarioRegistro']) ? $datos['idUsuarioRegistro'] : null,
		];
		$stmt = sqlsrv_query($this->db, $sql, $params);
		if (!$stmt) {
			return false;
		}
		sqlsrv_free_stmt($stmt);

		$stmtId = sqlsrv_query($this->db, 'SELECT SCOPE_IDENTITY() AS Id');
		$rowId  = $stmtId ? sqlsrv_fetch_array($stmtId, SQLSRV_FETCH_ASSOC) : null;
		sqlsrv_free_stmt($stmtId);

		return $rowId ? (int) $rowId['Id'] : true;
	}

	public function actualizar($id, $datos)
	{
		$sql = "
			UPDATE adquisiciones.PresupuestoTecnologia
			SET Monto = ?
			WHERE Id = ?
		";
		$params = [
			isset($datos['Monto']) && $datos['Monto'] !== null && $datos['Monto'] !== '' ? (float) $datos['Monto'] : null,
			(int) $id,
		];
		$stmt = sqlsrv_query($this->db, $sql, $params);
		$ok   = $stmt !== false;
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}
}
