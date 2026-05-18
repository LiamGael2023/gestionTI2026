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
		$where = [];
		$params = [];

		if ($soloActivas) {
			$where[] = "Activo = 1";
		}

		$sql = "
			SELECT IdPlantilla, NombrePlantilla, DescripcionPlantilla, ContenidoJson, HtmlBase,
				Activo, FechaRegistro, FechaModificacion, IdUsuarioRegistro
			FROM comunicados.Plantilla
			" . (!empty($where) ? "WHERE " . implode(" AND ", $where) : "") . "
			ORDER BY FechaRegistro DESC, IdPlantilla DESC
		";
		$stmt = sqlsrv_query($this->db, $sql, $params);
		return $this->fetchAll($stmt);
	}

	public function obtener($idPlantilla)
	{
		$params = [(int) $idPlantilla];

		$sql = "
			SELECT IdPlantilla, NombrePlantilla, DescripcionPlantilla, ContenidoJson, HtmlBase,
				Activo, FechaRegistro, FechaModificacion, IdUsuarioRegistro
			FROM comunicados.Plantilla
			WHERE IdPlantilla = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, $params);
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
		$ok = $this->cambioRealizado($stmt);
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	public function cambiarEstado($idPlantilla, $activo, $idUsuarioModificacion = null)
	{
		$sql = "
			UPDATE comunicados.Plantilla
			SET Activo = ?, FechaModificacion = SYSDATETIME(), IdUsuarioModificacion = ?
			WHERE IdPlantilla = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [(int) $activo, $idUsuarioModificacion, (int) $idPlantilla]);
		$ok = $this->cambioRealizado($stmt);
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	private function cambioRealizado($stmt)
	{
		if ($stmt === false) {
			return false;
		}

		$filas = sqlsrv_rows_affected($stmt);
		return $filas === false ? false : $filas > 0;
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
