<?php
class FichaTecnicaModel
{
	private $db;

	// Inicializa el modelo con la conexion a la base de datos.
	public function __construct($db)
	{
		$this->db = $db;
	}

	// Lista las fichas tecnicas de una tecnologia para el anio indicado.
	public function listarPorTecnologia($idCatalogoTecnologico, $anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');
		$this->normalizarRangos($idCatalogoTecnologico, $anioConsulta);

		$sql = "
			SELECT Id, IdCatalogoTecnologico, Marca, Modelo, Anio, Estado, Documento, FechaRegistro, Rango
			FROM adquisiciones.FichaTecnica
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
			ORDER BY Rango ASC, Id ASC
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

	// Cuenta las fichas tecnicas de una tecnologia para el anio indicado.
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

	// Registra una nueva ficha tecnica y devuelve su identificador.
	public function guardar($datos)
	{
		$idCatalogoTecnologico = (int) $datos['IdCatalogoTecnologico'];
		$anio = (int) $datos['Anio'];

		$this->normalizarRangos($idCatalogoTecnologico, $anio);
		$proximoRango = $this->obtenerSiguienteRango($idCatalogoTecnologico, $anio);

		$sql = "
			INSERT INTO adquisiciones.FichaTecnica (IdCatalogoTecnologico, Marca, Modelo, Anio, Estado, Documento, Rango, FechaRegistro, idUsuarioRegistro)
			VALUES (?, ?, ?, ?, 0, ?, ?, GETDATE(), ?);
			SELECT SCOPE_IDENTITY() AS Id;
		";

		$params = [
			$idCatalogoTecnologico,
			$datos['Marca'],
			$datos['Modelo'],
			$anio,
			[$datos['Documento'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')],
			$proximoRango,
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

	// Actualiza el estado de una ficha tecnica y registra la modificacion.
	public function cambiarEstado($id, $estado, $idUsuarioModifica = null)
	{
		$sql = "UPDATE adquisiciones.FichaTecnica SET Estado = ?, idUsuarioModifica = ?, FechaModifica = GETDATE() WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$estado, $idUsuarioModifica, $id]);
		return $stmt !== false;
	}

	// Elimina una ficha tecnica y normaliza los rangos restantes.
	public function eliminar($id)
	{
		$contexto = $this->obtenerContextoPorId($id);
		if ($contexto === null) {
			return false;
		}

		$sql = "DELETE FROM adquisiciones.FichaTecnica WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			return false;
		}

		$this->normalizarRangos((int) $contexto['IdCatalogoTecnologico'], (int) $contexto['Anio']);
		return true;
	}

	// Mueve una ficha tecnica hacia arriba o abajo intercambiando su rango.
	public function moverRango($id, $direccion, $idUsuarioModifica = null)
	{
		$contexto = $this->obtenerContextoPorId($id);
		if ($contexto === null) {
			return false;
		}

		$idCatalogoTecnologico = (int) $contexto['IdCatalogoTecnologico'];
		$anio = (int) $contexto['Anio'];
		$this->normalizarRangos($idCatalogoTecnologico, $anio);

		$fichaActual = $this->obtenerPorId($id);
		if ($fichaActual === null) {
			return false;
		}

		$rangoActual = (int) $fichaActual['Rango'];
		$rangoObjetivo = $direccion === 'up' ? $rangoActual - 1 : $rangoActual + 1;
		if ($rangoObjetivo <= 0) {
			return true;
		}

		$vecina = $this->obtenerPorRango($idCatalogoTecnologico, $anio, $rangoObjetivo);
		if ($vecina === null) {
			return true;
		}

		$rangoTemporal = $this->obtenerSiguienteRango($idCatalogoTecnologico, $anio);

		if (!sqlsrv_begin_transaction($this->db)) {
			return false;
		}

		$okTemporal = $this->actualizarRango((int) $fichaActual['Id'], $rangoTemporal, $idUsuarioModifica);
		$okVecina = $okTemporal && $this->actualizarRango((int) $vecina['Id'], $rangoActual, $idUsuarioModifica);
		$okActual = $okVecina && $this->actualizarRango((int) $fichaActual['Id'], $rangoObjetivo, $idUsuarioModifica);

		if ($okActual) {
			sqlsrv_commit($this->db);
			return true;
		}

		sqlsrv_rollback($this->db);
		return false;
	}

	// Obtiene el siguiente rango disponible para una tecnologia y anio.
	private function obtenerSiguienteRango($idCatalogoTecnologico, $anio)
	{
		$sql = "
			SELECT ISNULL(MAX(Rango), 0) + 1 AS SiguienteRango
			FROM adquisiciones.FichaTecnica
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anio]);
		if ($stmt === false) {
			return 1;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ? (int) $row['SiguienteRango'] : 1;
	}

	// Reordena los rangos de las fichas para que queden consecutivos.
	private function normalizarRangos($idCatalogoTecnologico, $anio)
	{
		$sql = "
			;WITH Ordenados AS (
				SELECT
					Id,
					ROW_NUMBER() OVER (
						ORDER BY
							CASE WHEN ISNULL(Rango, 0) > 0 THEN Rango ELSE 2147483647 END,
							FechaRegistro ASC,
							Id ASC
					) AS NuevoRango
				FROM adquisiciones.FichaTecnica
				WHERE IdCatalogoTecnologico = ? AND Anio = ?
			)
			UPDATE FT
			SET FT.Rango = O.NuevoRango
			FROM adquisiciones.FichaTecnica FT
			INNER JOIN Ordenados O ON O.Id = FT.Id
			WHERE FT.IdCatalogoTecnologico = ? AND FT.Anio = ?
		";

		sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anio, $idCatalogoTecnologico, $anio]);
	}

	// Obtiene la tecnologia y el anio asociados a una ficha tecnica.
	private function obtenerContextoPorId($id)
	{
		$sql = "SELECT IdCatalogoTecnologico, Anio FROM adquisiciones.FichaTecnica WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			return null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ?: null;
	}

	// Busca una ficha tecnica por su identificador.
	private function obtenerPorId($id)
	{
		$sql = "SELECT Id, IdCatalogoTecnologico, Anio, Rango FROM adquisiciones.FichaTecnica WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		if ($stmt === false) {
			return null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ?: null;
	}

	// Busca una ficha tecnica por tecnologia, anio y rango.
	private function obtenerPorRango($idCatalogoTecnologico, $anio, $rango)
	{
		$sql = "
			SELECT TOP 1 Id, IdCatalogoTecnologico, Anio, Rango
			FROM adquisiciones.FichaTecnica
			WHERE IdCatalogoTecnologico = ? AND Anio = ? AND Rango = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [$idCatalogoTecnologico, $anio, $rango]);
		if ($stmt === false) {
			return null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return $row ?: null;
	}

	// Actualiza el rango de una ficha tecnica y registra la modificacion.
	private function actualizarRango($id, $rango, $idUsuarioModifica = null)
	{
		$sql = "UPDATE adquisiciones.FichaTecnica SET Rango = ?, idUsuarioModifica = ?, FechaModifica = GETDATE() WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$rango, $idUsuarioModifica, $id]);
		return $stmt !== false;
	}
}
