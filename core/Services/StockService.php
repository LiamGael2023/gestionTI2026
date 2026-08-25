<?php
/**
 * StockService - Servicio centralizado para gestión de inventario y regla FIFO
 */
class StockService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Valida si existe stock suficiente para un producto
     */
    public function obtenerStockTotalProducto($idProducto) {
        $sql = "SELECT ISNULL(SUM(stock_actual), 0) AS stock_total 
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote 
                WHERE id_producto = ? AND stock_actual > 0";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return floatval($row['stock_total']);
        }
        return 0.0;
    }

    /**
     * Obtiene lotes activos ordenados por principio FIFO (más antiguos primero)
     */
    public function obtenerLotesFIFO($idProducto) {
        $sql = "SELECT id_lote, codigo_lote, stock_actual, fecha_creacion 
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote 
                WHERE id_producto = ? AND stock_actual > 0 
                ORDER BY fecha_creacion ASC, id_lote ASC";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        $lotes = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $lotes[] = $row;
            }
        }
        return $lotes;
    }

    /**
     * Descuenta stock aplicando el algoritmo FIFO y registra los movimientos en Kardex
     * Retorna array con los detalles asignados por lote
     */
    public function descontarStockFIFO($idTransaccion, $idProducto, $cantidadSolicitada, $tipoMovimiento = 'VENTA') {
        $stockDisponible = $this->obtenerStockTotalProducto($idProducto);
        if ($stockDisponible < $cantidadSolicitada) {
            throw new Exception("Stock insuficiente para el producto ID $idProducto. Requerido: $cantidadSolicitada, Disponible: $stockDisponible");
        }

        $lotes = $this->obtenerLotesFIFO($idProducto);
        $cantidadPendiente = $cantidadSolicitada;
        $asignaciones = [];

        foreach ($lotes as $lote) {
            if ($cantidadPendiente <= 0) break;

            $stockLote = floatval($lote['stock_actual']);
            $descuento = min($stockLote, $cantidadPendiente);

            // 1. Actualizar stock del lote
            $nuevoStock = $stockLote - $descuento;
            $sqlUpd = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.lote SET stock_actual = ? WHERE id_lote = ?";
            $stmtUpd = sqlsrv_query($this->db, $sqlUpd, [$nuevoStock, $lote['id_lote']]);
            if (!$stmtUpd) {
                throw new Exception("Error al actualizar stock del lote ID " . $lote['id_lote']);
            }

            // 2. Insertar movimiento en Kardex
            $sqlKardex = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                          (id_lote, id_transaccion, tipo_movimiento, cantidad, fecha) 
                          VALUES (?, ?, ?, ?, GETDATE())";
            $stmtK = sqlsrv_query($this->db, $sqlKardex, [$lote['id_lote'], $idTransaccion, $tipoMovimiento, $descuento]);
            if (!$stmtK) {
                throw new Exception("Error al registrar Kardex para el lote ID " . $lote['id_lote']);
            }

            $asignaciones[] = [
                'id_lote' => $lote['id_lote'],
                'cantidad' => $descuento
            ];

            $cantidadPendiente -= $descuento;
        }

        return $asignaciones;
    }

    /**
     * Revierte un movimiento de stock reintegrando la cantidad a los lotes de origen
     */
    public function reintegrarStockLote($idLote, $cantidad, $idTransaccion = null, $motivo = 'REINTEGRO') {
        $sqlUpd = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.lote SET stock_actual = stock_actual + ? WHERE id_lote = ?";
        $stmtUpd = sqlsrv_query($this->db, $sqlUpd, [$cantidad, $idLote]);
        if (!$stmtUpd) {
            throw new Exception("Error al reintegrar stock al lote ID $idLote");
        }

        $sqlK = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                  (id_lote, id_transaccion, tipo_movimiento, cantidad, fecha) 
                  VALUES (?, ?, ?, ?, GETDATE())";
        $stmtK = sqlsrv_query($this->db, $sqlK, [$idLote, $idTransaccion, $motivo, $cantidad]);
        if (!$stmtK) {
            throw new Exception("Error al registrar reintegración en Kardex");
        }
    }
}
