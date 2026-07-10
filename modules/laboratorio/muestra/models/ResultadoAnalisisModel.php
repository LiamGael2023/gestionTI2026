<?php

class ResultadoAnalisisModel {
    
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // ===== GUARDAR RESULTADO =====
    
    public function guardar($datos) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        
        $sql = "
            INSERT INTO laboratorio.Resultado_Analisis 
            (Id_Solicitud_Analisis, Id_Parametro, Id_Normativa, Valor_Hallado, Observacion, Interpretacion, Usuario_Creacion, Activo, Fecha_Creacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, GETDATE());
            SELECT SCOPE_IDENTITY() AS id;
        ";
        
        $params = array(
            $datos['Id_Solicitud_Analisis'] ?? null,
            $datos['Id_Parametro'] ?? null,
            $datos['Id_Normativa'] ?? null,
            !empty($datos['Valor_Hallado']) ? floatval($datos['Valor_Hallado']) : null,
            $datos['Observacion'] ?? null,
            $datos['Interpretacion'] ?? null,
            $usuario_id
        );
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error al guardar resultado: ' . print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_next_result($stmt);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['id'] ?? 0);
    }

    // ===== OBTENER RESULTADOS =====
    
    public function obtenerPorSolicitud($id_solicitud) {
        $sql = "
            SELECT
                ra.Id_Resultado,
                ra.Id_Solicitud_Analisis,
                ra.Id_Parametro,
                pa.Nombre AS Parametro_Nombre,
                pa.Unidad_Medida,
                ra.Valor_Hallado,
                ra.Id_Normativa,
                nl.Nombre AS Normativa_Nombre,
                ra.Observacion,
                ra.Interpretacion
            FROM laboratorio.Resultado_Analisis ra
            INNER JOIN laboratorio.Parametro_Analisis pa ON ra.Id_Parametro = pa.Id_Parametro
            LEFT JOIN laboratorio.Normativa_Legal nl ON ra.Id_Normativa = nl.Id_Normativa
            WHERE ra.Id_Solicitud_Analisis = ?
            AND ra.Activo = 1
            ORDER BY pa.Nombre
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_solicitud));
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorSolicitud: ' . print_r(sqlsrv_errors(), true));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ===== ACTUALIZAR RESULTADO =====
    
    public function actualizar($id_resultado, $datos) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        
        $sql = "
            UPDATE laboratorio.Resultado_Analisis 
            SET Valor_Hallado = ?, 
                Observacion = ?,
                Interpretacion = ?,
                Fecha_Modificacion = GETDATE()
            WHERE Id_Resultado = ?
        ";
        
        $params = array(
            !empty($datos['Valor_Hallado']) ? floatval($datos['Valor_Hallado']) : null,
            $datos['Observacion'] ?? null,
            $datos['Interpretacion'] ?? null,
            $id_resultado
        );
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error al actualizar resultado: ' . print_r(sqlsrv_errors(), true));
        }
        
        return true;
    }

    // ===== ELIMINAR RESULTADO =====
    
    public function eliminar($id_resultado) {
        $sql = "
            UPDATE laboratorio.Resultado_Analisis 
            SET Activo = 0, Fecha_Modificacion = GETDATE()
            WHERE Id_Resultado = ?
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_resultado));
        if ($stmt === false) {
            throw new Exception('Error al eliminar resultado: ' . print_r(sqlsrv_errors(), true));
        }
        
        return true;
    }

    // ===== VERIFICAR SI SOLICITUD TIENE RESULTADOS =====
    
    public function tieneSolicitudResultados($id_solicitud) {
        $sql = "
            SELECT COUNT(*) as total 
            FROM laboratorio.Resultado_Analisis 
            WHERE Id_Solicitud_Analisis = ? AND Activo = 1
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_solicitud));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        return intval($row['total']) > 0;
    }

    // ===== CREAR RESULTADOS EN BLANCO (PARA LLENAR) =====
    
    public function crearBlancosPorMuestra($id_muestra) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        
        // Crear resultados en blanco en bloque (Bulk Insert) para mejorar drásticamente el rendimiento
        $sql_bulk = "
            INSERT INTO laboratorio.Resultado_Analisis 
            (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
            SELECT 
                sa.Id_Solicitud_Analisis, 
                pa.Id_Parametro, 
                NULL AS Valor_Hallado, 
                ? AS Usuario_Creacion, 
                1 AS Activo, 
                GETDATE() AS Fecha_Creacion
            FROM laboratorio.Solicitud_Analisis sa
            INNER JOIN laboratorio.Parametro_Analisis pa ON sa.Id_Servicio = pa.Id_Servicio
            WHERE sa.Id_Muestra = ? 
            AND sa.Activo = 1 
            AND pa.Activo = 1
            AND NOT EXISTS (
                SELECT 1 
                FROM laboratorio.Resultado_Analisis ra 
                WHERE ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis 
                AND ra.Id_Parametro = pa.Id_Parametro
                AND ra.Activo = 1
            );
        ";
        
        $stmt = sqlsrv_query($this->db, $sql_bulk, array($usuario_id, $id_muestra));
        if ($stmt === false) {
            throw new Exception('Error al crear resultados en blanco masivamente: ' . print_r(sqlsrv_errors(), true));
        }
        
        return []; // Ya no es necesario retornar los IDs porque se consultan directamente en la vista
    }

    // ===== OBTENER RESULTADOS EDITABLES POR MUESTRA =====
    
    public function obtenerResultadosEditables($id_muestra) {
        $sql = "
            SELECT
                ra.Id_Resultado,
                ra.Id_Solicitud_Analisis,
                ra.Id_Parametro,
                pa.Nombre AS Parametro_Nombre,
                pa.Unidad_Medida,
                sa.Id_Servicio,
                st.Nombre AS Servicio_Nombre,
                ra.Valor_Hallado,
                ra.Observacion,
                ra.Interpretacion
            FROM laboratorio.Resultado_Analisis ra
            INNER JOIN laboratorio.Parametro_Analisis pa ON ra.Id_Parametro = pa.Id_Parametro
            INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
            INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
            WHERE sa.Id_Muestra = ?
            AND ra.Activo = 1
            AND sa.Activo = 1
            ORDER BY st.Nombre, pa.Nombre
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_muestra));
        if ($stmt === false) {
            throw new Exception('Error en obtenerResultadosEditables: ' . print_r(sqlsrv_errors(), true));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
}
?>
