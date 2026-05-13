<?php
// Modelo de distribuciones por detalle de requerimiento.
class DistribucionDetalleModel
{
    private $db;

    // Inicializa el modelo con la conexión de base de datos
    public function __construct($db)
    {
        $this->db = $db;
    }

    // Lista las distribuciones para un detalle de requerimiento.
    public function listarPorDetalle($idDetalleRequerimiento)
    {
        $sql = "
            SELECT
                d.Id,
                d.IdDetalleRequerimiento,
                d.IdCentroCosto,
                d.IdSubCentroCosto,
                c.NombreCentroCosto,
                c.Siglas AS SiglasCentroCosto,
                sc.NombreSubCentroCosto,
                sc.Siglas AS SiglasSubCentroCosto,
                d.Cantidad,
                d.FechaRegistro
            FROM adquisiciones.DistribucionDetalle d
            INNER JOIN adquisiciones.CentroCosto c ON c.Id = d.IdCentroCosto
            LEFT JOIN adquisiciones.SubCentroCosto sc ON sc.Id = d.IdSubCentroCosto
            WHERE d.IdDetalleRequerimiento = ?
            ORDER BY d.Id
        ";

        $stmt = sqlsrv_query($this->db, $sql, [(int) $idDetalleRequerimiento]);
        if ($stmt === false) {
            return [];
        }

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    // Obtiene una distribución por su id.
    public function obtenerPorId($id)
    {
        $sql = "
            SELECT
                Id,
                IdDetalleRequerimiento,
                IdCentroCosto,
                IdSubCentroCosto,
                Cantidad,
                FechaRegistro
            FROM adquisiciones.DistribucionDetalle
            WHERE Id = ?
        ";

        $stmt = sqlsrv_query($this->db, $sql, [(int) $id]);
        if ($stmt === false) {
            return null;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? $row : null;
    }

    // Inserta una nueva distribución para un detalle y devuelve su ID generado
    public function guardar($datos)
    {
        $sql = "
            INSERT INTO adquisiciones.DistribucionDetalle
                (IdDetalleRequerimiento, IdCentroCosto, IdSubCentroCosto, Cantidad, IdUsuarioRegistro, FechaRegistro)
            OUTPUT INSERTED.Id
            VALUES (?, ?, ?, ?, ?, GETDATE())
        ";

        $params = [
            (int) $datos['IdDetalleRequerimiento'],
            (int) $datos['IdCentroCosto'],
            isset($datos['IdSubCentroCosto']) && (int) $datos['IdSubCentroCosto'] > 0 ? (int) $datos['IdSubCentroCosto'] : null,
            (int) $datos['Cantidad'],
            $datos['IdUsuarioRegistro'] ?? null,
        ];

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            return false;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? $row['Id'] : false;
    }

    // Actualiza una distribución existente.
    public function actualizar($id, $datos)
    {
        $sql = "
            UPDATE adquisiciones.DistribucionDetalle
            SET IdCentroCosto = ?,
                IdSubCentroCosto = ?,
                Cantidad = ?,
                IdUsuarioModifica = ?,
                FechaModifica = GETDATE()
            WHERE Id = ?
        ";

        $params = [
            (int) $datos['IdCentroCosto'],
            isset($datos['IdSubCentroCosto']) && (int) $datos['IdSubCentroCosto'] > 0 ? (int) $datos['IdSubCentroCosto'] : null,
            (int) $datos['Cantidad'],
            $datos['IdUsuarioModifica'] ?? null,
            (int) $id,
        ];

        $stmt = sqlsrv_query($this->db, $sql, $params);
        return $stmt !== false;
    }

    // Verifica si ya existe una distribución con el mismo centro y subcentro.
    public function existeDistribucionDuplicada($idDetalleRequerimiento, $idCentroCosto, $idSubCentroCosto, $idExcluir = null)
    {
        $sql = "
            SELECT TOP 1 Id
            FROM adquisiciones.DistribucionDetalle
            WHERE IdDetalleRequerimiento = ?
              AND IdCentroCosto = ?
              AND (
                    (IdSubCentroCosto IS NULL AND ? IS NULL)
                    OR IdSubCentroCosto = ?
                  )
        ";

        $params = [
            (int) $idDetalleRequerimiento,
            (int) $idCentroCosto,
            $idSubCentroCosto,
            $idSubCentroCosto,
        ];

        if ((int) $idExcluir > 0) {
            $sql .= " AND Id <> ?";
            $params[] = (int) $idExcluir;
        }

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            return false;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? true : false;
    }

    // Elimina una distribución por su ID
    public function eliminar($id)
    {
        $sql = "DELETE FROM adquisiciones.DistribucionDetalle WHERE Id = ?";
        $stmt = sqlsrv_query($this->db, $sql, [(int) $id]);
        return $stmt !== false;
    }

    // Calcula el total distribuido para un detalle de requerimiento
    public function obtenerTotalDistribuidoPorDetalle($idDetalleRequerimiento)
    {
        $sql = "
            SELECT SUM(Cantidad) AS TotalDistribuido
            FROM adquisiciones.DistribucionDetalle
            WHERE IdDetalleRequerimiento = ?
        ";

        $stmt = sqlsrv_query($this->db, $sql, [(int) $idDetalleRequerimiento]);
        if ($stmt === false) {
            return 0;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? (int) ($row['TotalDistribuido'] ?? 0) : 0;
    }
}
