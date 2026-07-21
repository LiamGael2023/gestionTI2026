<?php

class SolicitudAnalisisModel {
    
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // ===== OBTENER SOLICITUDES =====
    
    public function obtenerPorProyecto($id_proyecto) {
        $sql = "
            SELECT DISTINCT
                sa.Id_Solicitud_Analisis,
                sa.Id_Muestra,
                sa.Id_Servicio,
                ml.Id_Proyecto,
                st.Nombre AS Servicio_Nombre,
                sa.Estado,
                COUNT(ml.Id_Muestra) AS Total_Muestras
            FROM laboratorio.Solicitud_Analisis sa
            INNER JOIN laboratorio.Muestra_Lab ml ON sa.Id_Muestra = ml.Id_Muestra
            INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
            WHERE ml.Id_Proyecto = ? 
            AND sa.Activo = 1
            AND st.Activo = 1
            GROUP BY sa.Id_Solicitud_Analisis, sa.Id_Muestra, sa.Id_Servicio, ml.Id_Proyecto, st.Nombre, sa.Estado
            ORDER BY st.Nombre
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto));
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorProyecto: ' . print_r(sqlsrv_errors(), true));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorServicio($id_proyecto, $id_servicio) {
        $sql = "
            SELECT
                sa.Id_Solicitud_Analisis,
                sa.Id_Muestra,
                sa.Id_Servicio,
                sa.Estado,
                ml.Id_Proyecto,
                st.Nombre AS Servicio_Nombre,
                COUNT(sa.Id_Solicitud_Analisis) AS Cantidad
            FROM laboratorio.Solicitud_Analisis sa
            INNER JOIN laboratorio.Muestra_Lab ml ON sa.Id_Muestra = ml.Id_Muestra
            INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
            WHERE ml.Id_Proyecto = ? 
            AND sa.Id_Servicio = ?
            AND sa.Activo = 1
            GROUP BY sa.Id_Solicitud_Analisis, sa.Id_Muestra, sa.Id_Servicio, sa.Estado, ml.Id_Proyecto, st.Nombre
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto, $id_servicio));
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorServicio: ' . print_r(sqlsrv_errors(), true));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerDetalles($id_solicitud) {
        $sql = "
            SELECT
                sa.Id_Solicitud_Analisis,
                sa.Id_Muestra,
                sa.Id_Servicio,
                sa.Estado,
                st.Nombre AS Servicio_Nombre,
                ml.Observacion_Muestra,
                ml.Fecha_Toma
            FROM laboratorio.Solicitud_Analisis sa
            INNER JOIN laboratorio.Muestra_Lab ml ON sa.Id_Muestra = ml.Id_Muestra
            INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
            WHERE sa.Id_Solicitud_Analisis = ?
            AND sa.Activo = 1
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_solicitud));
        if ($stmt === false) {
            throw new Exception('Error en obtenerDetalles: ' . print_r(sqlsrv_errors(), true));
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    // ===== ACTUALIZAR ESTADO =====
    
    public function actualizarEstado($id_solicitud, $nuevo_estado) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        
        $sql = "
            UPDATE laboratorio.Solicitud_Analisis 
            SET Estado = ?, Fecha_Modificacion = GETDATE()
            WHERE Id_Solicitud_Analisis = ?
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($nuevo_estado, $id_solicitud));
        if ($stmt === false) {
            throw new Exception('Error al actualizar estado: ' . print_r(sqlsrv_errors(), true));
        }
        
        return true;
    }

    // ===== OBTENER RECUENTO POR PROYECTO Y SERVICIO =====
    
    public function obtenerResumenProyecto($id_proyecto) {
        $sql = "
            SELECT
                st.Id_Servicio,
                st.Nombre AS Servicio_Nombre,
                COUNT(sa.Id_Solicitud_Analisis) AS Total_Solicitudes,
                SUM(CASE WHEN sa.Estado = 'En Análisis' THEN 1 ELSE 0 END) AS En_Analisis,
                SUM(CASE WHEN sa.Estado = 'Terminado' THEN 1 ELSE 0 END) AS Terminadas,
                SUM(CASE WHEN sa.Estado = 'Pendiente' THEN 1 ELSE 0 END) AS Pendientes
            FROM laboratorio.Solicitud_Analisis sa
            INNER JOIN laboratorio.Muestra_Lab ml ON sa.Id_Muestra = ml.Id_Muestra
            INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
            WHERE ml.Id_Proyecto = ?
            AND sa.Activo = 1
            GROUP BY st.Id_Servicio, st.Nombre
            ORDER BY st.Nombre
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto));
        if ($stmt === false) {
            throw new Exception('Error en obtenerResumenProyecto: ' . print_r(sqlsrv_errors(), true));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ===== OBTENER SOLICITUDES POR MUESTRA =====
    
    public function obtenerPorMuestra($id_muestra) {
        $sql = "
            SELECT
                sa.Id_Solicitud_Analisis,
                sa.Id_Muestra,
                sa.Id_Servicio,
                st.Nombre AS Servicio_Nombre,
                sa.Estado
            FROM laboratorio.Solicitud_Analisis sa
            INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
            WHERE sa.Id_Muestra = ?
            AND sa.Activo = 1
            AND st.Activo = 1
            ORDER BY st.Nombre
        ";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_muestra));
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorMuestra: ' . print_r(sqlsrv_errors(), true));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
}
?>
