<?php
class ParametroModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        
        // Auto-migración: asegurar columna Es_Exportable en laboratorio.Parametro_Analisis
        $sqlCheck = "IF NOT EXISTS (
            SELECT 1 FROM sys.columns 
            WHERE object_id = OBJECT_ID('laboratorio.Parametro_Analisis') AND name = 'Es_Exportable'
        )
        BEGIN
            ALTER TABLE laboratorio.Parametro_Analisis ADD Es_Exportable BIT NOT NULL DEFAULT 1;
        END";
        @sqlsrv_query($this->db, $sqlCheck);
    }

    public function obtenerTodos() {
        $sql = "SELECT p.*, ISNULL(p.Es_Exportable, 1) AS Es_Exportable, st.Nombre AS Servicio_Nombre, ISNULL(um.Abreviatura, p.Unidad_Medida) AS Unidad_Abreviatura
                FROM laboratorio.Parametro_Analisis p 
                LEFT JOIN laboratorio.Servicio_Tecnico st ON p.Id_Servicio = st.Id_Servicio 
                LEFT JOIN laboratorio.Unidad_Medida um ON p.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                WHERE p.Activo = 1 ORDER BY p.Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Parametros: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT p.*, ISNULL(p.Es_Exportable, 1) AS Es_Exportable, st.Nombre AS Servicio_Nombre, ISNULL(um.Abreviatura, p.Unidad_Medida) AS Unidad_Abreviatura
                FROM laboratorio.Parametro_Analisis p 
                LEFT JOIN laboratorio.Servicio_Tecnico st ON p.Id_Servicio = st.Id_Servicio 
                LEFT JOIN laboratorio.Unidad_Medida um ON p.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                WHERE p.Id_Parametro = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Parametro: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        $idUnidad = !empty($datos['Id_Unidad_Medida']) ? intval($datos['Id_Unidad_Medida']) : null;
        $unidadTexto = $datos['Unidad_Medida'] ?? null;
        $esExportable = isset($datos['Es_Exportable']) ? (empty($datos['Es_Exportable']) ? 0 : 1) : 1;
        
        if (empty($datos['Id_Parametro'])) {
            // INSERT
            $sql = "INSERT INTO laboratorio.Parametro_Analisis 
                    (Id_Servicio, Nombre, Unidad_Medida, Id_Unidad_Medida, Tipo_Parametro, Categoria, Metodo_Utilizado, Posgre_Nombre, Posgre_Tabla, Es_Exportable, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
            $params = array(
                $datos['Id_Servicio'] ?? null,
                $datos['Nombre'],
                $unidadTexto,
                $idUnidad,
                $datos['Tipo_Parametro'] ?? 'Ambos',
                $datos['Categoria'] ?? null,
                $datos['Metodo_Utilizado'] ?? null,
                $datos['Posgre_Nombre'] ?? null,
                $datos['Posgre_Tabla'] ?? null,
                $esExportable,
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
            $sql = "UPDATE laboratorio.Parametro_Analisis 
                    SET Id_Servicio=?, Nombre=?, Unidad_Medida=?, Id_Unidad_Medida=?, Tipo_Parametro=?, Categoria=?, Metodo_Utilizado=?, Posgre_Nombre=?, Posgre_Tabla=?, Es_Exportable=?, Fecha_Modificacion=GETDATE() 
                    WHERE Id_Parametro=?";
            $params = array(
                $datos['Id_Servicio'] ?? null,
                $datos['Nombre'],
                $unidadTexto,
                $idUnidad,
                $datos['Tipo_Parametro'] ?? 'Ambos',
                $datos['Categoria'] ?? null,
                $datos['Metodo_Utilizado'] ?? null,
                $datos['Posgre_Nombre'] ?? null,
                $datos['Posgre_Tabla'] ?? null,
                $esExportable,
                $datos['Id_Parametro']
            );
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Parametro'];
        }
    }

    public function eliminar($id) {
        // Verificar si el parametro esta ligado a limites activos
        $sql_check = "SELECT COUNT(*) AS count FROM laboratorio.Limite_Legal WHERE Id_Parametro = ? AND Activo = 1";
        $stmt_check = sqlsrv_query($this->db, $sql_check, array($id));
        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        
        if ($row_check['count'] > 0) {
            throw new Exception('No se puede eliminar este parametro porque esta ligado a ' . $row_check['count'] . ' limite(s) activo(s). Primero debes eliminarlo de los limites.');
        }

        // Verificar si el parametro esta ligado a un servicio activo
        $sql_servicio = "SELECT s.Id_Servicio FROM laboratorio.Parametro_Analisis p 
                         LEFT JOIN laboratorio.Servicio_Tecnico s ON p.Id_Servicio = s.Id_Servicio 
                         WHERE p.Id_Parametro = ? AND s.Activo = 1";
        $stmt_servicio = sqlsrv_query($this->db, $sql_servicio, array($id));
        $row_servicio = sqlsrv_fetch_array($stmt_servicio, SQLSRV_FETCH_ASSOC);
        
        if ($row_servicio && $row_servicio['Id_Servicio']) {
            throw new Exception('No se puede eliminar este parametro porque esta ligado a un servicio activo. Primero debes desvincular el servicio.');
        }
        
        $sql = "UPDATE laboratorio.Parametro_Analisis SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Parametro = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Parametro_Analisis SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Parametro = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }
    
    /**
     * Obtiene todas las unidades de medida activas para el dropdown
     */
    public function obtenerUnidades() {
        $sql = "SELECT Id_Unidad_Medida, Nombre, Abreviatura FROM laboratorio.Unidad_Medida WHERE Activo = 1 ORDER BY Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
        }
        return $result;
    }
}
?>
