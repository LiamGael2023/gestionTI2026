<?php
class CierreAquisicionModel
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	// Obtiene el registro de cierre para una tecnología y año específico.
	public function obtenerPorTecnologiaYAnio($idCatalogoTecnologico, $anio)
	{
		$sql = "
			SELECT TOP 1
				Id, IdCatalogoTecnologico, Anio, FechaFinalizacion,
				idUsuarioRegistro, idUsuarioModifica, FechaModifica, Estado
			FROM adquisiciones.CierreAquisicion
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
		";

		$stmt = sqlsrv_query($this->db, $sql, [(int) $idCatalogoTecnologico, (int) $anio]);
		if ($stmt === false) {
			return null;
		}

		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		if (!$row) {
			return null;
		}

		if ($row['FechaFinalizacion'] instanceof DateTime) {
			$row['FechaFinalizacion'] = $row['FechaFinalizacion']->format('d/m/Y H:i');
		}
		if ($row['FechaModifica'] instanceof DateTime) {
			$row['FechaModifica'] = $row['FechaModifica']->format('d/m/Y H:i');
		}

		return $row;
	}

	// Finaliza la adquisición: inserta o reactiva el registro con Estado = 1.
	public function finalizar($idCatalogoTecnologico, $anio, $idUsuario)
	{
		$idCat  = (int) $idCatalogoTecnologico;
		$anio   = (int) $anio;
		$idUser = $idUsuario !== null ? (int) $idUsuario : null;

		// ¿Ya existe un registro?
		$existente = $this->obtenerPorTecnologiaYAnio($idCat, $anio);

		if ($existente) {
			// Reactiva si estaba desactivado, o responde ok si ya estaba activo
			$sql = "
				UPDATE adquisiciones.CierreAquisicion
				SET Estado = 1,
				    FechaFinalizacion = GETDATE(),
				    idUsuarioModifica = ?,
				    FechaModifica = GETDATE()
				WHERE IdCatalogoTecnologico = ? AND Anio = ?
			";
			$stmt = sqlsrv_query($this->db, $sql, [$idUser, $idCat, $anio]);
			return $stmt !== false;
		}

		// Insertar nuevo registro
		$sql = "
			INSERT INTO adquisiciones.CierreAquisicion
				(IdCatalogoTecnologico, Anio, FechaFinalizacion, idUsuarioRegistro, Estado)
			VALUES (?, ?, GETDATE(), ?, 1)
		";
		$stmt = sqlsrv_query($this->db, $sql, [$idCat, $anio, $idUser]);
		return $stmt !== false;
	}

	//Apertura la adquisición: marca Estado=0 en el registro existente.
	public function aperturar($idCatalogoTecnologico, $anio, $idUsuario)
	{
		$idCat  = (int) $idCatalogoTecnologico;
		$anio   = (int) $anio;
		$idUser = $idUsuario !== null ? (int) $idUsuario : null;

		$sql = "
			UPDATE adquisiciones.CierreAquisicion
			SET Estado = 0,
			    idUsuarioModifica = ?,
			    FechaModifica = GETDATE()
			WHERE IdCatalogoTecnologico = ? AND Anio = ?
		";
		$stmt = sqlsrv_query($this->db, $sql, [$idUser, $idCat, $anio]);
		return $stmt !== false && sqlsrv_rows_affected($stmt) > 0;
	}

	// Indica si la adquisición está finalizada (Estado=1).
	public function estaFinalizada($idCatalogoTecnologico, $anio)
	{
		$registro = $this->obtenerPorTecnologiaYAnio($idCatalogoTecnologico, $anio);
		return !empty($registro) && (int) $registro['Estado'] === 1;
	}
}
