<?php
class VerificacionTecnicaModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function obtenerPorTecnologia($idCatalogoTecnologico, $anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');

		$sql = "
			SELECT TOP 1 Id, IdCatalogoTecnologico, Observacion, Anio, Documento, FechaRegistro
			FROM adquisiciones.VerificacionTecnica
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anioConsulta]);
		if ($stmt === false) {
			return null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		if ($row && $row['FechaRegistro'] instanceof DateTime) {
			$row['FechaRegistro'] = $row['FechaRegistro']->format('d/m/Y H:i');
		}

		return $row ?: null;
	}

	public function guardar($datos)
	{
		$sqlCheck = "SELECT Id FROM adquisiciones.VerificacionTecnica WHERE IdCatalogoTecnologico = ? AND Anio = ?";
		$stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$datos['IdCatalogoTecnologico'], $datos['Anio']]);
		if ($stmtCheck !== false && sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
			return false;
		}

		$sql = "
			INSERT INTO adquisiciones.VerificacionTecnica (IdCatalogoTecnologico, Observacion, Anio, Documento, FechaRegistro, idUsuarioRegistro)
			VALUES (?, ?, ?, ?, GETDATE(), ?);
			SELECT SCOPE_IDENTITY() AS Id;
		";

		$params = [
			$datos['IdCatalogoTecnologico'],
			$datos['Observacion'],
			$datos['Anio'],
			[$datos['Documento'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')],
			$datos['idUsuarioRegistro'] ?? null
		];

		$stmt = sqlsrv_query($this->db, $sql, $params);
		if ($stmt === false) {
			return false;
		}

		sqlsrv_next_result($stmt);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ? (int) $row['Id'] : false;
	}

	public function actualizar($id, $datos)
	{
		$setClauses = ['Observacion = ?'];
		$params = [$datos['Observacion']];

		if (array_key_exists('Documento', $datos)) {
			$setClauses[] = 'Documento = ?';
			$params[] = [$datos['Documento'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')];
		}

		$setClauses[] = 'idUsuarioModifica = ?';
		$setClauses[] = 'FechaModifica = GETDATE()';
		$params[] = $datos['idUsuarioModifica'] ?? null;
		$params[] = $id;

		$sql = "
			UPDATE adquisiciones.VerificacionTecnica
			SET " . implode(', ', $setClauses) . "
			WHERE Id = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, $params);

		return $stmt !== false;
	}

	public function eliminar($id)
	{
		$sql = "DELETE FROM adquisiciones.VerificacionTecnica WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		return $stmt !== false;
	}
}
