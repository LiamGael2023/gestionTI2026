<?php
class NormativaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM laboratorio.Normativa_Legal WHERE Activo = 1 ORDER BY Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Normativas: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Normativa_Legal WHERE Id_Normativa = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Normativa: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Normativa'])) {
            // INSERT
            $sql = "INSERT INTO laboratorio.Normativa_Legal (Nombre, Descripcion, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
            $params = array(
                $datos['Nombre'],
                $datos['Descripcion'] ?? null,
                $_SESSION['usuario_id'] ?? 1
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // UPDATE
            $sql = "UPDATE laboratorio.Normativa_Legal SET Nombre=?, Descripcion=?, Fecha_Modificacion=GETDATE() WHERE Id_Normativa=?";
            $params = array(
                $datos['Nombre'],
                $datos['Descripcion'] ?? null,
                $datos['Id_Normativa']
            );
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Normativa'];
        }
    }

    public function eliminar($id) {
        // Verificar si la normativa esta ligada a limites activos
        $sql_check = "SELECT COUNT(*) AS count FROM laboratorio.Limite_Legal WHERE Id_Normativa = ? AND Activo = 1";
        $stmt_check = sqlsrv_query($this->db, $sql_check, array($id));
        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        
        if ($row_check['count'] > 0) {
            throw new Exception('No se puede eliminar esta normativa porque esta ligada a ' . $row_check['count'] . ' limite(s). Primero debes eliminarlo de los limites.');
        }
        
        $sql = "UPDATE laboratorio.Normativa_Legal SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Normativa = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Normativa_Legal SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Normativa = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }
}
?>
