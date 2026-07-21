<?php
class LimiteModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT l.*, p.Nombre AS Parametro_Nombre, n.Nombre AS Normativa_Nombre FROM laboratorio.Limite_Legal l 
                JOIN laboratorio.Parametro_Analisis p ON l.Id_Parametro = p.Id_Parametro 
                JOIN laboratorio.Normativa_Legal n ON l.Id_Normativa = n.Id_Normativa 
                WHERE l.Activo = 1 ORDER BY n.Nombre, p.Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Limites: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT l.*, p.Nombre AS Parametro_Nombre, n.Nombre AS Normativa_Nombre FROM laboratorio.Limite_Legal l 
                JOIN laboratorio.Parametro_Analisis p ON l.Id_Parametro = p.Id_Parametro 
                JOIN laboratorio.Normativa_Legal n ON l.Id_Normativa = n.Id_Normativa 
                WHERE l.Id_Limite_Legal = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Limite: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        $sqlCheckDesc = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Limite_Legal' AND COLUMN_NAME = 'Descripcion'";
        $stmtCheckDesc = sqlsrv_query($this->db, $sqlCheckDesc);
        $tieneDescripcion = $stmtCheckDesc && sqlsrv_fetch_array($stmtCheckDesc, SQLSRV_FETCH_ASSOC) !== null;

        if (empty($datos['Id_Limite_Legal'])) {
            // INSERT
            if ($tieneDescripcion) {
                $sql = "INSERT INTO laboratorio.Limite_Legal (Id_Parametro, Id_Normativa, Valor_Max, Valor_Min, Unidad_Medida, Descripcion, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                $params = array(
                    $datos['Id_Parametro'],
                    $datos['Id_Normativa'],
                    $datos['Valor_Max'] ?? null,
                    $datos['Valor_Min'] ?? null,
                    $datos['Unidad_Medida'] ?? null,
                    $datos['Descripcion'] ?? null,
                    $_SESSION['usuario_id'] ?? 1
                );
            } else {
                $sql = "INSERT INTO laboratorio.Limite_Legal (Id_Parametro, Id_Normativa, Valor_Max, Valor_Min, Unidad_Medida, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                $params = array(
                    $datos['Id_Parametro'],
                    $datos['Id_Normativa'],
                    $datos['Valor_Max'] ?? null,
                    $datos['Valor_Min'] ?? null,
                    $datos['Unidad_Medida'] ?? null,
                    $_SESSION['usuario_id'] ?? 1
                );
            }
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
            if ($tieneDescripcion) {
                $sql = "UPDATE laboratorio.Limite_Legal SET Id_Parametro=?, Id_Normativa=?, Valor_Max=?, Valor_Min=?, Unidad_Medida=?, Descripcion=?, Fecha_Modificacion=GETDATE() WHERE Id_Limite_Legal=?";
                $params = array(
                    $datos['Id_Parametro'],
                    $datos['Id_Normativa'],
                    $datos['Valor_Max'] ?? null,
                    $datos['Valor_Min'] ?? null,
                    $datos['Unidad_Medida'] ?? null,
                    $datos['Descripcion'] ?? null,
                    $datos['Id_Limite_Legal']
                );
            } else {
                $sql = "UPDATE laboratorio.Limite_Legal SET Id_Parametro=?, Id_Normativa=?, Valor_Max=?, Valor_Min=?, Unidad_Medida=?, Fecha_Modificacion=GETDATE() WHERE Id_Limite_Legal=?";
                $params = array(
                    $datos['Id_Parametro'],
                    $datos['Id_Normativa'],
                    $datos['Valor_Max'] ?? null,
                    $datos['Valor_Min'] ?? null,
                    $datos['Unidad_Medida'] ?? null,
                    $datos['Id_Limite_Legal']
                );
            }
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Limite_Legal'];
        }
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Limite_Legal SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Limite_Legal = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en DELETE: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Limite_Legal SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Limite_Legal = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en UPDATE: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function obtenerPorParametro($idParametro) {
        $sql = "SELECT l.* FROM laboratorio.Limite_Legal l WHERE l.Id_Parametro = ? AND l.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idParametro));
        if ($stmt === false) {
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorNormativa($idNormativa) {
        $sql = "SELECT l.* FROM laboratorio.Limite_Legal l WHERE l.Id_Normativa = ? AND l.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idNormativa));
        if ($stmt === false) {
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
}
?>
