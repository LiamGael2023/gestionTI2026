<?php
class ArchivoModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listar($soloActivos = true)
	{
		$where = [];
		$params = [];

		if ($soloActivos) {
			$where[] = "Activo = 1";
		}

		$sql = "
			SELECT IdArchivo, IdComunicado, NombreOriginal, NombreServidor, RutaRelativa,
				UrlPublica, TipoMime, ExtensionArchivo, TamanoBytes, TipoArchivo, Activo, FechaRegistro,
				IdUsuarioRegistro
			FROM comunicados.Archivo
			" . (!empty($where) ? "WHERE " . implode(" AND ", $where) : "") . "
			ORDER BY FechaRegistro DESC, IdArchivo DESC
		";
		$stmt = sqlsrv_query($this->db, $sql, $params);
		return $this->fetchAll($stmt);
	}

	public function guardar(array $datos)
	{
		$sql = "
			INSERT INTO comunicados.Archivo
				(IdComunicado, NombreOriginal, NombreServidor, RutaRelativa, UrlPublica, TipoMime,
				ExtensionArchivo, TamanoBytes, TipoArchivo, IdUsuarioRegistro)
			OUTPUT INSERTED.IdArchivo
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		";
		$params = [
			$this->intONull($datos['IdComunicado'] ?? null),
			(string) ($datos['NombreOriginal'] ?? ''),
			(string) ($datos['NombreServidor'] ?? ''),
			(string) ($datos['RutaRelativa'] ?? ''),
			(string) ($datos['UrlPublica'] ?? ''),
			$datos['TipoMime'] ?? null,
			$datos['ExtensionArchivo'] ?? null,
			(int) ($datos['TamanoBytes'] ?? 0),
			$this->tipoArchivo($datos['TipoArchivo'] ?? 'DOCUMENTO'),
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

	public function cambiarEstado($idArchivo, $activo)
	{
		$sql = "
			UPDATE comunicados.Archivo
			SET Activo = ?
			WHERE IdArchivo = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [(int) $activo, (int) $idArchivo]);
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

	private function intONull($valor)
	{
		$int = (int) $valor;
		return $int > 0 ? $int : null;
	}

	private function tipoArchivo($tipo)
	{
		$tipo = strtoupper(trim((string) $tipo));
		return in_array($tipo, ['IMAGEN', 'DOCUMENTO'], true) ? $tipo : 'DOCUMENTO';
	}
}
