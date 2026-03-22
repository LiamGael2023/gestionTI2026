<?php
class OrdenCompraModel
{
	public const NUMERO_ORDEN_MAX_LENGTH = 25;

	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function obtenerPorTecnologia($idCatalogoTecnologico, $anio = null)
	{
		$anioConsulta = $anio ?? (int) date('Y');

		$sql = "
			SELECT TOP 1 Id, IdCatalogoTecnologico, NumeroOrden, Anio, FechaEntrega, Documento, FechaRegistro
			FROM adquisiciones.OrdenCompra
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
		if ($row && $row['FechaEntrega'] instanceof DateTime) {
			$row['FechaEntrega'] = $row['FechaEntrega']->format('Y-m-d');
		}

		return $row ?: null;
	}

	public function guardar($datos)
	{
		$sqlCheck = "SELECT Id FROM adquisiciones.OrdenCompra WHERE IdCatalogoTecnologico = ? AND Anio = ?";
		$stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$datos['IdCatalogoTecnologico'], $datos['Anio']]);
		if ($stmtCheck !== false && sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
			return false;
		}

		$sql = "
			INSERT INTO adquisiciones.OrdenCompra (IdCatalogoTecnologico, NumeroOrden, Anio, FechaEntrega, Documento, FechaRegistro)
			VALUES (?, ?, ?, ?, ?, GETDATE());
			SELECT SCOPE_IDENTITY() AS Id;
		";

		$params = [
			$datos['IdCatalogoTecnologico'],
			$datos['NumeroOrden'],
			$datos['Anio'],
			$datos['FechaEntrega'],
			[$datos['Documento'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')]
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
			UPDATE adquisiciones.OrdenCompra
			SET NumeroOrden = ?, FechaEntrega = ?, Documento = ?, FechaRegistro = GETDATE()
			WHERE Id = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [
			$datos['NumeroOrden'],
			$datos['FechaEntrega'],
			[$datos['Documento'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')],
			$id
		]);

		return $stmt !== false;
	}

	public function eliminar($id)
	{
		$sql = "DELETE FROM adquisiciones.OrdenCompra WHERE Id = ?";
		$stmt = sqlsrv_query($this->db, $sql, [$id]);
		return $stmt !== false;
	}
}