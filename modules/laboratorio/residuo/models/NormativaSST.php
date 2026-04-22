<?php
class NormativaSST {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM laboratorio.Normativa_SST WHERE Activo = 1 ORDER BY Nombre_Ley";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Normativas SST: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Normativa_SST WHERE Id_Normativa_SST = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Normativa SST: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Normativa_SST'])) {
            // INSERT
            $sql = "INSERT INTO laboratorio.Normativa_SST (Nombre_Ley, Descripcion, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
            $params = array(
                $datos['Nombre_Ley'],
                $datos['Descripcion'],
                $_SESSION['usuario_id'] ?? 1
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Normativa SST: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // UPDATE
            $sql = "UPDATE laboratorio.Normativa_SST SET Nombre_Ley=?, Descripcion=?, Fecha_Modificacion=GETDATE() WHERE Id_Normativa_SST=?";
            $params = array(
                $datos['Nombre_Ley'],
                $datos['Descripcion'],
                $datos['Id_Normativa_SST']
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Normativa SST: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Normativa_SST'];
        }
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Normativa_SST SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Normativa_SST = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    // ==================== MÉTODOS ADICIONALES ====================

    public function crearNormativa($datos) {
        $sql = "INSERT INTO laboratorio.Normativa_SST 
                (Nombre_Ley, Descripcion, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, 1, GETDATE())";
        
        $params = [
            $datos['Nombre_Ley'],
            $datos['Descripcion'],
            intval($datos['Usuario_Creacion'])
        ];
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Normativa SST: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return true;
    }

    public function obtenerNormativas() {
        return $this->obtenerTodos();
    }
}