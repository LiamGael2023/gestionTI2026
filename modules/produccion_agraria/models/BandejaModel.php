<?php
class BandejaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Listar proformas pendientes con filtros opcionales
     */
    public function listarProformas($filtros = []) {
        $sql = "SELECT t.id_transaccion, t.id_cliente, t.fecha_creacion, t.total, 
                       t.estado, t.metodo_pago, t.serie_comprobante, t.correlativo_comprobante,
                       t.responsable_venta,
                       c.nombre_rs as nombre_cliente, c.dni_ruc as documento_cliente, 
                       CASE WHEN LEN(c.dni_ruc) = 8 THEN 'DNI' ELSE 'RUC' END as tipo_documento,
                       cp.nombre_centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
                WHERE t.tipo_op = 'VENTA'";
        
        $params = [];
        
        // Filtro por estado - por defecto mostrar todas las proformas no completadas
        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        } elseif (empty($filtros['ver_todas'])) {
            // Excluir las procesadas y rechazadas para ver proformas pendientes
            $sql .= " AND (t.estado IS NULL OR t.estado NOT IN ('PROCESADO', 'RECHAZADO'))";
        }
        
        // Filtro por fecha
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        // Filtro por cliente
        if (!empty($filtros['cliente'])) {
            $sql .= " AND (c.nombre_rs LIKE ? OR c.dni_ruc LIKE ?)";
            $params[] = '%' . $filtros['cliente'] . '%';
            $params[] = '%' . $filtros['cliente'] . '%';
        }
        
        $sql .= " ORDER BY t.fecha_creacion DESC";
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('SQL Error listarProformas: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Convertir fecha a string
            if (isset($row['fecha_creacion']) && $row['fecha_creacion'] instanceof DateTime) {
                $row['fecha_creacion'] = $row['fecha_creacion']->format('Y-m-d H:i:s');
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Obtener detalle completo de una proforma
     */
    public function obtenerProforma($idTransaccion) {
        error_log("[BandejaModel] obtenerProforma - Buscando ID: $idTransaccion");
        
        // Encabezado
        $sql = "SELECT t.id_transaccion, t.id_cliente, t.fecha_creacion, t.total, 
                       t.estado, t.metodo_pago, t.tipo_op, t.responsable_venta,
                       t.serie_comprobante, t.correlativo_comprobante, t.doc_justificante, t.id_voucher,
                       c.nombre_rs as nombre_cliente, c.dni_ruc as documento_cliente, 
                       CASE WHEN LEN(c.dni_ruc) = 8 THEN 'DNI' ELSE 'RUC' END as tipo_documento,
                       cp.nombre_centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
                WHERE t.id_transaccion = ?";
        
        $stmt = sqlsrv_query($this->db, $sql, [$idTransaccion]);
        if ($stmt === false) {
            $errors = print_r(sqlsrv_errors(), true);
            error_log("[BandejaModel] SQL Error: $errors");
            return null;
        }
        
        if (!sqlsrv_has_rows($stmt)) {
            error_log("[BandejaModel] No se encontró proforma con ID: $idTransaccion");
            return null;
        }
        
        $proforma = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (isset($proforma['fecha_creacion']) && $proforma['fecha_creacion'] instanceof DateTime) {
            $proforma['fecha_creacion'] = $proforma['fecha_creacion']->format('Y-m-d H:i:s');
        }
        
        // Detalles
        $sqlDet = "SELECT td.id_detalle, td.id_producto, td.id_lote, td.cantidad, 
                          td.precio_unitario, td.subtotal,
                          p.nombre as nombre_producto, p.unidad_medida,
                          l.codigo_lote
                   FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle td
                   LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON td.id_producto = p.id_producto
                   LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l ON td.id_lote = l.id_lote
                   WHERE td.id_transaccion = ?";
        
        $stmtDet = sqlsrv_query($this->db, $sqlDet, [$idTransaccion]);
        $detalles = [];
        while ($row = sqlsrv_fetch_array($stmtDet, SQLSRV_FETCH_ASSOC)) {
            $detalles[] = $row;
        }
        
        $proforma['detalles'] = $detalles;
        error_log("[BandejaModel] Proforma encontrada ID: $idTransaccion, Cliente: " . ($proforma['nombre_cliente'] ?? 'N/A'));
        return $proforma;
    }

    /**
     * Procesar proforma - Completar la venta
     */
    public function procesarProforma($idTransaccion, $data) {
        try {
            sqlsrv_begin_transaction($this->db);
            
            // Validar que la proforma existe y está pendiente
            $sqlCheck = "SELECT estado FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                        WHERE id_transaccion = ?";
            $stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$idTransaccion]);
            if ($stmtCheck === false) {
                throw new Exception('Error al verificar proforma: ' . print_r(sqlsrv_errors(), true));
            }
            $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
            if (!$row) {
                throw new Exception('Proforma no encontrada');
            }
            // Estados que permiten procesar: NULL o PENDIENTE (PROCESADO y RECHAZADO ya están finalizadas)
            $estadoActual = $row['estado'];
            if (in_array($estadoActual, ['PROCESADO', 'RECHAZADO'])) {
                throw new Exception('La proforma ya fue procesada o rechazada (estado: ' . $estadoActual . ')');
            }
            
            // Actualizar proforma con datos de pago y comprobante
            $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                    SET estado = 'PROCESADO',
                        metodo_pago = ?,
                        serie_comprobante = ?,
                        correlativo_comprobante = ?,
                        doc_justificante = ?,
                        id_voucher = ?
                    WHERE id_transaccion = ?";
            
            $params = [
                $data['metodo_pago'],
                $data['serie_comprobante'] ?? null,
                $data['correlativo_comprobante'] ?? null,
                $data['doc_justificante'] ?? null,
                $data['id_voucher'] ?? null,
                $idTransaccion
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error al procesar proforma: ' . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_commit($this->db);
            return ['success' => true, 'message' => 'Proforma procesada correctamente'];
            
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Anular proforma - Revertir stock si es necesario
     */
    public function anularProforma($idTransaccion, $motivo = '') {
        try {
            error_log("[BandejaModel] anularProforma - Iniciando ID: $idTransaccion");
            sqlsrv_begin_transaction($this->db);
            
            // Obtener detalles para revertir stock si es necesario
            $sqlDet = "SELECT id_lote, cantidad FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle 
                      WHERE id_transaccion = ?";
            $stmtDet = sqlsrv_query($this->db, $sqlDet, [$idTransaccion]);
            if ($stmtDet === false) {
                $errors = print_r(sqlsrv_errors(), true);
                error_log("[BandejaModel] Error al obtener detalles: $errors");
                throw new Exception('Error al obtener detalles de la proforma');
            }
            
            error_log("[BandejaModel] Procesando detalles para revertir stock...");
            while ($row = sqlsrv_fetch_array($stmtDet, SQLSRV_FETCH_ASSOC)) {
                error_log("[BandejaModel] Revirtiendo lote {$row['id_lote']}, cantidad {$row['cantidad']}");
                
                // Revertir stock del lote
                $sqlRevert = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.lote 
                            SET stock_actual = stock_actual + ?
                            WHERE id_lote = ?";
                $stmtRevert = sqlsrv_query($this->db, $sqlRevert, [$row['cantidad'], $row['id_lote']]);
                if ($stmtRevert === false) {
                    $errors = print_r(sqlsrv_errors(), true);
                    error_log("[BandejaModel] Error al revertir stock: $errors");
                    throw new Exception('Error al revertir stock: ' . $errors);
                }
                
                // Registrar movimiento de reintegro en kardex (revertir stock por anulación)
                $sqlKardex = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                            (id_lote, id_transaccion, tipo_movimiento, cantidad, saldo_final, fecha) 
                            SELECT ?, ?, 'REINTEGRO', ?, stock_actual, GETDATE()
                            FROM BD_PRODUCCIONDESARROLLO.dbo.lote WHERE id_lote = ?";
                $stmtKardex = sqlsrv_query($this->db, $sqlKardex, [
                    $row['id_lote'], $idTransaccion, $row['cantidad'], $row['id_lote']
                ]);
                if ($stmtKardex === false) {
                    $errors = print_r(sqlsrv_errors(), true);
                    error_log("[BandejaModel] Error al registrar en kardex: $errors");
                    throw new Exception('Error al registrar en kardex: ' . $errors);
                }
            }
            
            // Actualizar estado de la transacción a RECHAZADO según constraint de BD
            error_log("[BandejaModel] Actualizando estado a RECHAZADO...");
            $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                    SET estado = 'RECHAZADO', doc_justificante = ?
                    WHERE id_transaccion = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$motivo, $idTransaccion]);
            if ($stmt === false) {
                $errors = print_r(sqlsrv_errors(), true);
                error_log("[BandejaModel] Error al actualizar transaccion: $errors");
                throw new Exception('Error al anular proforma: ' . $errors);
            }
            
            sqlsrv_commit($this->db);
            error_log("[BandejaModel] Proforma rechazada exitosamente");
            return ['success' => true, 'message' => 'Proforma rechazada correctamente'];
            
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            error_log("[BandejaModel] Error en anularProforma: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listarMetodosPago() {
        return [
            ['codigo' => 'VENTA', 'nombre' => 'Venta', 'icono' => 'ti-shopping-cart'],
            ['codigo' => 'DONACION', 'nombre' => 'Donación', 'icono' => 'ti-gift'],
        ];
    }

    /**
     * Obtener siguiente correlativo para comprobante
     */
    public function obtenerSiguienteCorrelativo($serie) {
        $sql = "SELECT ISNULL(MAX(CAST(correlativo_comprobante AS INT)), 0) + 1 as siguiente
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion
                WHERE serie_comprobante = ? AND correlativo_comprobante IS NOT NULL";
        $stmt = sqlsrv_query($this->db, $sql, [$serie]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return str_pad($row['siguiente'], 8, '0', STR_PAD_LEFT);
        }
        return '00000001';
    }
}
?>
