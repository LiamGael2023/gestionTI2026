<?php
/**
 * PrecioService - Servicio centralizado para cálculo de precios de productos
 */
class PrecioService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Calcula el precio vigente actual de un producto según su tipo de precio (UIT o Variable)
     */
    public function calcularPrecioProducto($producto) {
        if (!$producto) {
            return 0.0;
        }

        $tipoPrecio = $producto['tipo_precio'] ?? 'Variable';

        if ($tipoPrecio === 'UIT' && isset($producto['porcentaje_uit']) && $producto['porcentaje_uit'] !== null) {
            $valUIT = $this->obtenerValorUITActual();
            return round(floatval($valUIT) * (floatval($producto['porcentaje_uit']) / 100.0), 2);
        }

        // Si es Variable o fallback
        $idProducto = $producto['id_producto'] ?? 0;
        return $this->obtenerUltimoPrecioVariable($idProducto);
    }

    /**
     * Obtiene el valor de la UIT para el año en curso
     */
    public function obtenerValorUITActual() {
        $anio = date('Y');
        $sql = "SELECT TOP 1 valor FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE anio = ? AND activo = 1 ORDER BY anio DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$anio]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return floatval($row['valor']);
        }
        // Fallback al valor más reciente registrado
        $sqlFallback = "SELECT TOP 1 valor FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE activo = 1 ORDER BY anio DESC";
        $stmtF = sqlsrv_query($this->db, $sqlFallback);
        if ($stmtF && $rowF = sqlsrv_fetch_array($stmtF, SQLSRV_FETCH_ASSOC)) {
            return floatval($rowF['valor']);
        }
        return 5350.0; // Fallback por defecto UIT 2026
    }

    /**
     * Obtiene el último precio variable registrado para un producto
     */
    public function obtenerUltimoPrecioVariable($idProducto) {
        if (!$idProducto) return 0.0;
        $sql = "SELECT TOP 1 precio FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio WHERE id_producto = ? ORDER BY fecha_registro DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return floatval($row['precio']);
        }
        return 0.0;
    }
}
