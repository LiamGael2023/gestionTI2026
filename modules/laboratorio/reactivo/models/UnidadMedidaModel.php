<?php
/**
 * UnidadMedidaModel.php
 * CRUD para laboratorio.Unidad_Medida
 */
class UnidadMedidaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $stmt = sqlsrv_query($this->db,
            "SELECT Id_Unidad_Medida, Nombre, Abreviatura FROM laboratorio.Unidad_Medida WHERE Activo = 1 ORDER BY Nombre"
        );
        if ($stmt === false) {
            throw new Exception('Error al obtener unidades: ' . (sqlsrv_errors()[0]['message'] ?? ''));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $stmt = sqlsrv_query($this->db,
            "SELECT Id_Unidad_Medida, Nombre, Abreviatura FROM laboratorio.Unidad_Medida WHERE Id_Unidad_Medida = ?",
            [intval($id)]
        );
        if ($stmt === false) {
            throw new Exception('Error al obtener unidad');
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        $nombre    = trim($datos['Nombre'] ?? '');
        $abrev     = trim($datos['Abreviatura'] ?? '');
        $id        = !empty($datos['Id_Unidad_Medida']) ? intval($datos['Id_Unidad_Medida']) : null;
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        if (empty($nombre)) throw new Exception('El nombre es obligatorio');
        if (empty($abrev))  throw new Exception('La abreviatura es obligatoria');

        if (empty($id)) {
            $sql = "INSERT INTO laboratorio.Unidad_Medida (Nombre, Abreviatura, Activo, Fecha_Creacion, Usuario_Creacion)
                    VALUES (?, ?, 1, GETDATE(), ?);
                    SELECT SCOPE_IDENTITY() AS id;";
            $stmt = sqlsrv_query($this->db, $sql, [$nombre, $abrev, $usuarioId]);
            if ($stmt === false) {
                throw new Exception('Error al crear unidad: ' . (sqlsrv_errors()[0]['message'] ?? ''));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return intval($row['id']);
        } else {
            $sql = "UPDATE laboratorio.Unidad_Medida SET Nombre = ?, Abreviatura = ? WHERE Id_Unidad_Medida = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$nombre, $abrev, $id]);
            if ($stmt === false) {
                throw new Exception('Error al actualizar unidad: ' . (sqlsrv_errors()[0]['message'] ?? ''));
            }
            return $id;
        }
    }

    public function eliminar($id) {
        $check = sqlsrv_query($this->db,
            "SELECT COUNT(*) AS cnt FROM laboratorio.Reactivo_Lab WHERE Id_Unidad_Medida = ? AND Activo = 1",
            [intval($id)]
        );
        $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
        if ($row && intval($row['cnt']) > 0) {
            throw new Exception('La unidad está en uso por ' . $row['cnt'] . ' reactivo(s). Cambie la unidad de esos reactivos antes de eliminar.');
        }
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Unidad_Medida SET Activo = 0 WHERE Id_Unidad_Medida = ?",
            [intval($id)]
        );
    }
}
