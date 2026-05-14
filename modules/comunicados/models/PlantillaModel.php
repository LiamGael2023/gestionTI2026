<?php
class PlantillaModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listar($soloActivas = true)
	{
		$sql = "
			SELECT IdPlantilla, NombrePlantilla, DescripcionPlantilla, ContenidoJson, HtmlBase,
				Activo, FechaRegistro, FechaModificacion
			FROM comunicados.Plantilla
			" . ($soloActivas ? "WHERE Activo = 1" : "") . "
			ORDER BY FechaRegistro DESC, IdPlantilla DESC
		";
		$stmt = sqlsrv_query($this->db, $sql);
		return $this->fetchAll($stmt);
	}

	public function obtener($idPlantilla)
	{
		$sql = "
			SELECT IdPlantilla, NombrePlantilla, DescripcionPlantilla, ContenidoJson, HtmlBase,
				Activo, FechaRegistro, FechaModificacion
			FROM comunicados.Plantilla
			WHERE IdPlantilla = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [(int) $idPlantilla]);
		if (!$stmt) {
			return null;
		}
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		sqlsrv_free_stmt($stmt);
		return $row ?: null;
	}

	public function guardar(array $datos)
	{
		$sql = "
			INSERT INTO comunicados.Plantilla
				(NombrePlantilla, DescripcionPlantilla, ContenidoJson, HtmlBase, IdUsuarioRegistro)
			OUTPUT INSERTED.IdPlantilla
			VALUES (?, ?, ?, ?, ?)
		";
		$params = [
			trim((string) ($datos['NombrePlantilla'] ?? '')),
			$this->nullSiVacio($datos['DescripcionPlantilla'] ?? null),
			(string) ($datos['ContenidoJson'] ?? '[]'),
			$this->nullSiVacio($datos['HtmlBase'] ?? null),
			$datos['IdUsuarioRegistro'] ?? null,
		];
		$stmt = sqlsrv_query($this->db, $sql, $params);
		if (!$stmt) {
			return false;
		}
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
		sqlsrv_free_stmt($stmt);
		return $row ? (int) $row[0] : true;
	}

	public function actualizar($idPlantilla, array $datos)
	{
		$sql = "
			UPDATE comunicados.Plantilla
			SET NombrePlantilla = ?,
				DescripcionPlantilla = ?,
				ContenidoJson = ?,
				HtmlBase = ?,
				FechaModificacion = SYSDATETIME(),
				IdUsuarioModificacion = ?
			WHERE IdPlantilla = ?
		";
		$params = [
			trim((string) ($datos['NombrePlantilla'] ?? '')),
			$this->nullSiVacio($datos['DescripcionPlantilla'] ?? null),
			(string) ($datos['ContenidoJson'] ?? '[]'),
			$this->nullSiVacio($datos['HtmlBase'] ?? null),
			$datos['IdUsuarioModificacion'] ?? null,
			(int) $idPlantilla,
		];
		$stmt = sqlsrv_query($this->db, $sql, $params);
		$ok = $stmt !== false;
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	public function cambiarEstado($idPlantilla, $activo, $idUsuario)
	{
		$sql = "
			UPDATE comunicados.Plantilla
			SET Activo = ?, FechaModificacion = SYSDATETIME(), IdUsuarioModificacion = ?
			WHERE IdPlantilla = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [(int) $activo, $idUsuario, (int) $idPlantilla]);
		$ok = $stmt !== false;
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	private function fetchAll($stmt)
	{
		if (!$stmt) {
			return [];
		}
		$rows = [];
		while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$rows[] = $row;
		}
		sqlsrv_free_stmt($stmt);
		return $rows;
	}

	private function nullSiVacio($valor)
	{
		$texto = trim((string) $valor);
		return $texto === '' ? null : $texto;
	}
}
