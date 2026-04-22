<?php
class EquipoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT e.*, es.Nombre AS Estado_Nombre FROM laboratorio.Equipo_Lab e JOIN laboratorio.Equipo_Estado es ON e.Id_Estado = es.Id_Estado WHERE e.Activo = 1 ORDER BY e.Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Equipos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT e.*, es.Nombre AS Estado_Nombre FROM laboratorio.Equipo_Lab e JOIN laboratorio.Equipo_Estado es ON e.Id_Estado = es.Id_Estado WHERE e.Id_Equipo = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Equipo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Equipo'])) {
            // INSERT
            $sql = "INSERT INTO laboratorio.Equipo_Lab (Id_Estado, Nombre, Proveedor, Fecha_Ultima_Calibracion, Fecha_Proxima_Calibracion, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
            $params = array(
                $datos['Id_Estado'],
                $datos['Nombre'],
                $datos['Proveedor'],
                $datos['Fecha_Ultima_Calibracion'] ? $datos['Fecha_Ultima_Calibracion'] : null,
                $datos['Fecha_Proxima_Calibracion'] ? $datos['Fecha_Proxima_Calibracion'] : null,
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
            // UPDATE - Solo actualizar fechas si vienen con valor
            $sql = "UPDATE laboratorio.Equipo_Lab SET Id_Estado=?, Nombre=?, Proveedor=?";
            $params = array(
                $datos['Id_Estado'],
                $datos['Nombre'],
                $datos['Proveedor']
            );
            
            // Solo agregar fechas al UPDATE si tienen valor
            if (!empty($datos['Fecha_Ultima_Calibracion'])) {
                $sql .= ", Fecha_Ultima_Calibracion=?";
                array_splice($params, 3, 0, array($datos['Fecha_Ultima_Calibracion']));
            }
            
            if (!empty($datos['Fecha_Proxima_Calibracion'])) {
                if (empty($datos['Fecha_Ultima_Calibracion'])) {
                    $sql .= ", Fecha_Proxima_Calibracion=?";
                    array_splice($params, 3, 0, array($datos['Fecha_Proxima_Calibracion']));
                } else {
                    $sql .= ", Fecha_Proxima_Calibracion=?";
                    array_push($params, $datos['Fecha_Proxima_Calibracion']);
                }
            }
            
            $sql .= ", Fecha_Modificacion=GETDATE() WHERE Id_Equipo=?";
            array_push($params, $datos['Id_Equipo']);
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Equipo'];
        }
    }

    public function eliminar($id) {
        // Verificar si el equipo está ligado a servicios activos
        $sql_check = "SELECT COUNT(*) AS count FROM laboratorio.Requisito_Equipo WHERE Id_Equipo = ? AND Activo = 1";
        $stmt_check = sqlsrv_query($this->db, $sql_check, array($id));
        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        
        if ($row_check['count'] > 0) {
            throw new Exception('No se puede eliminar este equipo porque está ligado a ' . $row_check['count'] . ' servicio(s). Primero debes eliminarlo de los servicios.');
        }
        
        $sql = "UPDATE laboratorio.Equipo_Lab SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Equipo = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function obtenerEstados() {
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

    public function obtenerRequisitosPorServicio($idServicio) {
        $sql = "SELECT re.*, e.Nombre AS laboratorio.Equipo_Nombre FROM laboratorio.Requisito_Equipo re JOIN laboratorio.Equipo_Lab e ON re.Id_Equipo = e.Id_Equipo WHERE re.Id_Servicio = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Equipo_Lab SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Equipo = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }
}
