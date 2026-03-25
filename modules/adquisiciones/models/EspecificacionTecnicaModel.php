<?php
class EspecificacionTecnicaModel
{
	public const CODIGO_MAX_LENGTH = 50;

	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function obtenerPorTecnologia($idCatalogoTecnologico, $anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');

		$sql = "
			SELECT TOP 1 Id, IdCatalogoTecnologico, Codigo, Anio, Documento, FechaRegistro
			FROM adquisiciones.EspecificacionTecnica
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
		$sqlCheck = "SELECT Id FROM adquisiciones.EspecificacionTecnica WHERE IdCatalogoTecnologico = ? AND Anio = ?";
		$stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$datos['IdCatalogoTecnologico'], $datos['Anio']]);
		if ($stmtCheck !== false && sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
			return false;
		}

		$sql = "
			INSERT INTO adquisiciones.EspecificacionTecnica (IdCatalogoTecnologico, Codigo, Anio, Documento, FechaRegistro, idUsuarioRegistro)
			VALUES (?, ?, ?, ?, GETDATE(), ?);
			SELECT SCOPE_IDENTITY() AS Id;
		";

		$params = [
			$datos['IdCatalogoTecnologico'],
			$datos['Codigo'],
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
		$sql = "
			UPDATE adquisiciones.EspecificacionTecnica
			SET Codigo = ?, Documento = ?, idUsuarioModifica = ?, FechaModifica = GETDATE()
			WHERE Id = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [
			$datos['Codigo'],
			[$datos['Documento'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')],
			$datos['idUsuarioModifica'] ?? null,
			$id
		]);
		return $stmt !== false;
	}

	public function eliminar($id)
	{
		$sql = "DELETE FROM adquisiciones.EspecificacionTecnica WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		return $stmt !== false;
	}
}