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
        
        // Obtener todas las solicitudes activas de análisis para la muestra
        $sql_solicitudes = "
            SELECT sa.Id_Solicitud_Analisis, sa.Id_Servicio
            FROM laboratorio.Solicitud_Analisis sa
            WHERE sa.Id_Muestra = ? 
            AND sa.Activo = 1
        ";
        
        $stmt_solicitudes = sqlsrv_query($this->db, $sql_solicitudes, array($id_muestra));
        if ($stmt_solicitudes === false) {
            throw new Exception('Error al obtener solicitudes: ' . print_r(sqlsrv_errors(), true));
        }
        
        $ids_creados = [];
        
        while ($solicitud = sqlsrv_fetch_array($stmt_solicitudes, SQLSRV_FETCH_ASSOC)) {
            $id_solicitud = $solicitud['Id_Solicitud_Analisis'];
            $id_servicio = $solicitud['Id_Servicio'];
            
            // Obtener los parámetros del servicio
            $sql_params = "
                SELECT Id_Parametro 
                FROM laboratorio.Parametro_Analisis 
                WHERE Id_Servicio = ?
                AND Activo = 1
                ORDER BY Id_Parametro
            ";
            
            $stmt_params = sqlsrv_query($this->db, $sql_params, array($id_servicio));
            if ($stmt_params === false) {
                throw new Exception('Error al obtener parámetros: ' . print_r(sqlsrv_errors(), true));
            }
            
            // Crear un Resultado_Analisis vacío para cada parámetro
            while ($param = sqlsrv_fetch_array($stmt_params, SQLSRV_FETCH_ASSOC)) {
                $id_parametro = $param['Id_Parametro'];
                
                // Verificar que no exista ya
                $sql_check = "
                    SELECT COUNT(*) as total 
                    FROM laboratorio.Resultado_Analisis 
                    WHERE Id_Solicitud_Analisis = ? 
                    AND Id_Parametro = ? 
                    AND Activo = 1
                ";
                
                $stmt_check = sqlsrv_query($this->db, $sql_check, array($id_solicitud, $id_parametro));
                $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                
                if (intval($row_check['total']) === 0) {
                    // Crear nuevo resultado en blanco
                    $sql_insert = "
                        INSERT INTO laboratorio.Resultado_Analisis 
                        (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, NULL, ?, 1, GETDATE());
                        SELECT SCOPE_IDENTITY() AS id;
                    ";
                    
                    $stmt_insert = sqlsrv_query($this->db, $sql_insert, array($id_solicitud, $id_parametro, $usuario_id));
                    if ($stmt_insert === false) {
                        throw new Exception('Error al crear resultado en blanco: ' . print_r(sqlsrv_errors(), true));
                    }
                    
                    sqlsrv_next_result($stmt_insert);
                    $row = sqlsrv_fetch_array($stmt_insert, SQLSRV_FETCH_ASSOC);
                    $ids_creados[] = intval($row['id']);
                }
            }
        }
        
        return $ids_creados;
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
