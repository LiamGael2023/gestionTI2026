<?php
class ComunicadoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listar($soloActivos = true)
	{
		$sql = "
			SELECT c.IdComunicado, c.IdPlantilla, c.TituloComunicado, c.AsuntoCorreo,
				c.EstadoComunicado, c.Activo, c.FechaRegistro, c.FechaModificacion,
				p.NombrePlantilla
			FROM comunicados.Comunicado c
			LEFT JOIN comunicados.Plantilla p ON p.IdPlantilla = c.IdPlantilla
			" . ($soloActivos ? "WHERE c.Activo = 1" : "") . "
			ORDER BY c.FechaRegistro DESC, c.IdComunicado DESC
		";
		$stmt = sqlsrv_query($this->db, $sql);
		return $this->fetchAll($stmt);
	}

	public function obtener($idComunicado)
	{
		$sql = "
			SELECT IdComunicado, IdPlantilla, TituloComunicado, AsuntoCorreo, ContenidoJson,
				HtmlFinal, EstadoComunicado, Activo, FechaRegistro, FechaModificacion
			FROM comunicados.Comunicado
			WHERE IdComunicado = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [(int) $idComunicado]);
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
				(IdPlantilla, TituloComunicado, AsuntoCorreo, ContenidoJson, HtmlFinal, EstadoComunicado, IdUsuarioRegistro)
			OUTPUT INSERTED.IdComunicado
			VALUES (?, ?, ?, ?, ?, ?, ?)
		";
		$params = [
			$this->intONull($datos['IdPlantilla'] ?? null),
			trim((string) ($datos['TituloComunicado'] ?? '')),
			$this->nullSiVacio($datos['AsuntoCorreo'] ?? null),
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

	public function actualizar($idComunicado, array $datos)
	{
		$sql = "
			UPDATE comunicados.Comunicado
			SET IdPlantilla = ?,
				TituloComunicado = ?,
				AsuntoCorreo = ?,
				ContenidoJson = ?,
				HtmlFinal = ?,
				EstadoComunicado = ?,
				FechaModificacion = SYSDATETIME(),
				IdUsuarioModificacion = ?
			WHERE IdComunicado = ?
		";
		$params = [
			$this->intONull($datos['IdPlantilla'] ?? null),
			trim((string) ($datos['TituloComunicado'] ?? '')),
			$this->nullSiVacio($datos['AsuntoCorreo'] ?? null),
			(string) ($datos['ContenidoJson'] ?? '[]'),
			$this->nullSiVacio($datos['HtmlFinal'] ?? null),
			$this->estado($datos['EstadoComunicado'] ?? 'BORRADOR'),
			$datos['IdUsuarioModificacion'] ?? null,
			(int) $idComunicado,
		];
		$stmt = sqlsrv_query($this->db, $sql, $params);
		$ok = $stmt !== false;
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	public function cambiarEstadoActivo($idComunicado, $activo, $idUsuario)
	{
		$sql = "
			UPDATE comunicados.Comunicado
			SET Activo = ?, FechaModificacion = SYSDATETIME(), IdUsuarioModificacion = ?
			WHERE IdComunicado = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [(int) $activo, $idUsuario, (int) $idComunicado]);
		$ok = $stmt !== false;
		if ($stmt) {
			sqlsrv_free_stmt($stmt);
		}
		return $ok;
	}

	public function obtenerResumen()
	{
		$sql = "
			SELECT
				COUNT(1) AS TotalComunicados,
				SUM(CASE WHEN EstadoComunicado = N'BORRADOR' AND Activo = 1 THEN 1 ELSE 0 END) AS Borradores,
				SUM(CASE WHEN EstadoComunicado = N'LISTO' AND Activo = 1 THEN 1 ELSE 0 END) AS Listos,
				SUM(CASE WHEN Activo = 0 THEN 1 ELSE 0 END) AS Inactivos
			FROM comunicados.Comunicado
		";
		$stmt = sqlsrv_query($this->db, $sql);
		if (!$stmt) {
			return ['TotalComunicados' => 0, 'Borradores' => 0, 'Listos' => 0, 'Inactivos' => 0];
		}
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		sqlsrv_free_stmt($stmt);
		return $row ?: ['TotalComunicados' => 0, 'Borradores' => 0, 'Listos' => 0, 'Inactivos' => 0];
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
