<?php
class EquipoEstadoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM laboratorio.Equipo_Estado WHERE Activo = 1 ORDER BY Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Estados: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerTodosConInactivos() {
        $sql = "SELECT * FROM laboratorio.Equipo_Estado ORDER BY Activo DESC, Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Estados: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Equipo_Estado WHERE Id_Estado = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Estado: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Estado'])) {
            // INSERT
            $nombre = trim($datos['Nombre'] ?? '');
            $descripcion = trim($datos['Descripcion'] ?? '');
            $usuario_id = $_SESSION['usuario_id'] ?? 1;
            
            $sql = "INSERT INTO laboratorio.Equipo_Estado (Nombre, Descripcion, Usuario_Creacion, Activo, Fecha_Creacion) 
                    VALUES (?, ?, ?, 1, GETDATE()); 
                    SELECT SCOPE_IDENTITY() AS id;";
            
            $params = array($nombre, $descripcion, $usuario_id);
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Estado: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'] ?? null;
        } else {
            // UPDATE
            $nombre = trim($datos['Nombre'] ?? '');
            $descripcion = trim($datos['Descripcion'] ?? '');
            $id_estado = $datos['Id_Estado'];
            
            $sql = "UPDATE laboratorio.Equipo_Estado 
                    SET Nombre=?, Descripcion=?, Fecha_Modificacion=GETDATE() 
                    WHERE Id_Estado=?";
            
            $params = array($nombre, $descripcion, $id_estado);
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Estado: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            
            return $id_estado;
        }
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Equipo_Estado SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Estado = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    /**
     * Verifica si un estado está siendo usado por equipos activos
     */
    public function estaEnUso($id_estado) {
        $sql = "SELECT COUNT(*) as cantidad FROM laboratorio.Equipo_Lab WHERE Id_Estado = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id_estado));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en validación: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['cantidad'] > 0;
    }

    /**
     * Reativa un estado desactivado
     */
    public function reactivar($id_estado) {
        $sql = "UPDATE laboratorio.Equipo_Estado SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Estado = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id_estado));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al reactivar: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return true;
    }
}
