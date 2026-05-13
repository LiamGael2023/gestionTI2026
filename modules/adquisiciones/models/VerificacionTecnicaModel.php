<?php
class VerificacionTecnicaModel
{
	private $db;

	// Inicializa el modelo con la conexion a la base de datos.
	public function __construct($db)
	{
		$this->db = $db;
	}

	// Obtiene la verificacion tecnica de una tecnologia para el anio indicado.
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

	// Registra una nueva verificacion tecnica si no existe para la tecnologia y anio.
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

	// Actualiza la observacion y opcionalmente el documento de una verificacion tecnica.
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

	// Elimina una verificacion tecnica por su identificador.
	public function eliminar($id)
	{
		$sql = "DELETE FROM adquisiciones.VerificacionTecnica WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		return $stmt !== false;
	}
}
