<?php
class ComunicadoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listar($soloActivos = true, $idUsuarioRegistro = null)
	{
		$where = [];
		$params = [];

		if ($soloActivos) {
			$where[] = "c.Activo = 1";
		}

		if ($idUsuarioRegistro !== null) {
			$where[] = "c.IdUsuarioRegistro = ?";
			$params[] = $idUsuarioRegistro;
		}

		$sql = "
			SELECT c.IdComunicado, c.IdPlantilla, c.TituloComunicado,
				c.EstadoComunicado, c.Activo, c.FechaRegistro, c.FechaModificacion,
				c.IdUsuarioRegistro, p.NombrePlantilla
			FROM comunicados.Comunicado c
			LEFT JOIN comunicados.Plantilla p
				ON p.IdPlantilla = c.IdPlantilla
			" . (!empty($where) ? "WHERE " . implode(" AND ", $where) : "") . "
			ORDER BY c.FechaRegistro DESC, c.IdComunicado DESC
		";
		$stmt = sqlsrv_query($this->db, $sql, $params);
		return $this->fetchAll($stmt);
	}

	public function obtener($idComunicado, $idUsuarioRegistro = null)
	{
		$where = ["IdComunicado = ?"];
		$params = [(int) $idComunicado];

		if ($idUsuarioRegistro !== null) {
			$where[] = "IdUsuarioRegistro = ?";
			$params[] = $idUsuarioRegistro;
		}

		$sql = "
			SELECT IdComunicado, IdPlantilla, TituloComunicado, ContenidoJson,
				HtmlFinal, EstadoComunicado, Activo, FechaRegistro, FechaModificacion, IdUsuarioRegistro
			FROM comunicados.Comunicado
			WHERE " . implode(" AND ", $where) . "
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
			INSERT INTO comunicados.Comunicado
				(IdPlantilla, TituloComunicado, ContenidoJson, HtmlFinal, EstadoComunicado, IdUsuarioRegistro)
			OUTPUT INSERTED.IdComunicado
			VALUES (?, ?, ?, ?, ?, ?)
		";
		$params = [
			$this->intONull($datos['IdPlantilla'] ?? null),
			trim((string) ($datos['TituloComunicado'] ?? '')),
			(string) ($datos['ContenidoJson'] ?? '[]'),
			$this->nullSiVacio($datos['HtmlFinal'] ?? null),
			$this->estado($datos['EstadoComunicado'] ?? 'BORRADOR'),
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

	public function actualizar($idComunicado, array $datos, $idUsuarioRegistro = null)
	{
		$where = "IdComunicado = ?";
		$params = [
			$this->intONull($datos['IdPlantilla'] ?? null),
			trim((string) ($datos['TituloComunicado'] ?? '')),
			(string) ($datos['ContenidoJson'] ?? '[]'),
			$this->nullSiVacio($datos['HtmlFinal'] ?? null),
			$this->estado($datos['EstadoComunicado'] ?? 'BORRADOR'),
			$datos['IdUsuarioModificacion'] ?? null,
			(int) $idComunicado,
		];

		if ($idUsuarioRegistro !== null) {
			$where .= " AND IdUsuarioRegistro = ?";
			$params[] = $idUsuarioRegistro;
		}

		$sql = "
			UPDATE comunicados.Comunicado
			SET IdPlantilla = ?,
				TituloComunicado = ?,
				ContenidoJson = ?,
				HtmlFinal = ?,
				EstadoComunicado = ?,
				FechaModificacion = SYSDATETIME(),
				IdUsuarioModificacion = ?
			WHERE " . $where . "
		";
		$stmt = sqlsrv_query($this->db, $sql, $params);
		$ok = $this->cambioRealizado($stmt);
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	public function cambiarEstadoActivo($idComunicado, $activo, $idUsuarioModificacion = null, $idUsuarioRegistro = null)
	{
		$where = "IdComunicado = ?";
		$params = [(int) $activo, $idUsuarioModificacion, (int) $idComunicado];

		if ($idUsuarioRegistro !== null) {
			$where .= " AND IdUsuarioRegistro = ?";
			$params[] = $idUsuarioRegistro;
		}

		$sql = "
			UPDATE comunicados.Comunicado
			SET Activo = ?, FechaModificacion = SYSDATETIME(), IdUsuarioModificacion = ?
			WHERE " . $where . "
		";
		$stmt = sqlsrv_query($this->db, $sql, $params);
		$ok = $this->cambioRealizado($stmt);
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	public function obtenerResumen($idUsuarioRegistro = null)
	{
		$where = [];
		$params = [];

		if ($idUsuarioRegistro !== null) {
			$where[] = "IdUsuarioRegistro = ?";
			$params[] = $idUsuarioRegistro;
		}

		$sql = "
			SELECT
				COUNT(1) AS TotalComunicados,
				SUM(CASE WHEN EstadoComunicado = N'BORRADOR' AND Activo = 1 THEN 1 ELSE 0 END) AS Borradores,
				SUM(CASE WHEN EstadoComunicado = N'LISTO' AND Activo = 1 THEN 1 ELSE 0 END) AS Listos,
				SUM(CASE WHEN Activo = 0 THEN 1 ELSE 0 END) AS Inactivos
			FROM comunicados.Comunicado
			" . (!empty($where) ? "WHERE " . implode(" AND ", $where) : "") . "
		";
		$stmt = sqlsrv_query($this->db, $sql, $params);
		if (!$stmt) {
			return ['TotalComunicados' => 0, 'Borradores' => 0, 'Listos' => 0, 'Inactivos' => 0];
		}
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		sqlsrv_free_stmt($stmt);
		return $row ?: ['TotalComunicados' => 0, 'Borradores' => 0, 'Listos' => 0, 'Inactivos' => 0];
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

	private function intONull($valor)
	{
		$int = (int) $valor;
		return $int > 0 ? $int : null;
	}

	private function nullSiVacio($valor)
	{
		$texto = trim((string) $valor);
		return $texto === '' ? null : $texto;
	}

	private function estado($valor)
	{
		$estado = strtoupper(trim((string) $valor));
		return in_array($estado, ['BORRADOR', 'LISTO'], true) ? $estado : 'BORRADOR';
	}
}
