<?php
class PuntoVentaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ========================================
    // CLIENTES (para select)
    // ========================================
    
    public function listarClientes() {
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, tipo_cliente 
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente 
                ORDER BY nombre_rs";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function buscarClientes($query) {
        // Búsqueda solo por nombre
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, 
                       CASE WHEN tipo_cliente = 1 THEN 'Persona Natural' ELSE 'Empresa' END as tipo_cliente 
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente 
                WHERE nombre_rs COLLATE SQL_Latin1_General_CP1_CI_AS LIKE ?
                ORDER BY nombre_rs";
        $searchTerm = '%' . $query . '%';
        $stmt = sqlsrv_query($this->db, $sql, [$searchTerm]);
        if ($stmt === false) {
            error_log('SQL Error buscarClientes: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ========================================
    // PRODUCTOS (para venta - solo los que manejan stock)
    // ========================================
    
    public function listarProductosVenta() {
        $sql = "SELECT p.id_producto, p.nombre, p.unidad_medida, p.tipo_precio, p.porcentaje_uit,
                       p.id_clase, p.id_centro, c.nombre_clase, cp.nombre_centro,
                       u.valor as valor_uit,
                       hp.precio_oficial as precio_variable
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit u ON u.anio = YEAR(GETDATE())
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (
                        SELECT MAX(hp2.fecha_registro)
                        FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                        WHERE hp2.id_producto = hp1.id_producto
                    )
                ) hp ON p.id_producto = hp.id_producto
                WHERE p.maneja_stock = 1
                ORDER BY p.nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            error_log('SQL Error listarProductosVenta: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Calcular precio según tipo
            $row['precio_venta'] = $this->calcularPrecio($row);
            $result[] = $row;
        }
        return $result;
    }

    public function buscarProducto($id) {
        $sql = "SELECT p.id_producto, p.nombre, p.unidad_medida, p.tipo_precio, p.porcentaje_uit,
                       p.id_clase, p.id_centro, c.nombre_clase, cp.nombre_centro,
                       u.valor as valor_uit,
                       hp.precio_oficial as precio_variable
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit u ON u.anio = YEAR(GETDATE())
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (
                        SELECT MAX(hp2.fecha_registro)
                        FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                        WHERE hp2.id_producto = hp1.id_producto
                    )
                ) hp ON p.id_producto = hp.id_producto
                WHERE p.id_producto = ? AND p.maneja_stock = 1";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['precio_venta'] = $this->calcularPrecio($row);
            return $row;
        }
        return null;
    }

    private function calcularPrecio($producto) {
        if ($producto['tipo_precio'] === 'UIT' && !empty($producto['valor_uit']) && !empty($producto['porcentaje_uit'])) {
            return floatval($producto['valor_uit']) * floatval($producto['porcentaje_uit']);
        }
        if ($producto['tipo_precio'] === 'Variable' && !empty($producto['precio_variable'])) {
            return floatval($producto['precio_variable']);
        }
        // Sin precio definido
        return 0;
    }

    // ========================================
    // TRANSACCIONES (ventas)
    // ========================================
    
    public function guardarVenta($data) {
        sqlsrv_begin_transaction($this->db);
        
        try {
            // Obtener id_centro del primer producto
            $idCentro = null;
            if (!empty($data['items'])) {
                $primerItem = $data['items'][0];
                $sqlCentro = "SELECT id_centro FROM BD_PRODUCCIONDESARROLLO.dbo.producto WHERE id_producto = ?";
                $stmtCentro = sqlsrv_query($this->db, $sqlCentro, [$primerItem['id_producto']]);
                if ($stmtCentro && $rowCentro = sqlsrv_fetch_array($stmtCentro, SQLSRV_FETCH_ASSOC)) {
                    $idCentro = $rowCentro['id_centro'];
                }
            }
            
            // Insertar encabezado de transacción y obtener ID
            $sqlTransaccion = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                        (id_cliente, id_centro, id_voucher, responsable_venta, tipo_op, metodo_pago, estado, fecha_creacion, total, serie_comprobante, correlativo_comprobante, doc_justificante)
                        OUTPUT INSERTED.id_transaccion
                        VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?, ?, ?)";
            $paramsTransaccion = [
                $data['id_cliente'],
                $idCentro ?? 1, // id_centro del producto o valor por defecto
                null, // id_voucher - se asigna en proformas
                $_SESSION['usuario_nombre'] ?? 'Sistema', // responsable_venta
                'VENTA', // tipo_op
                null, // metodo_pago - se asigna en proformas
                'PENDIENTE', // estado - en proceso para bandeja de proformas
                $data['total'],
                null, // serie_comprobante - se asigna en proformas
                null, // correlativo_comprobante - se asigna en proformas
                null  // doc_justificante
            ];
            $stmtTransaccion = sqlsrv_query($this->db, $sqlTransaccion, $paramsTransaccion);
            if ($stmtTransaccion === false) {
                throw new Exception('Error al insertar transacción: ' . print_r(sqlsrv_errors(), true));
            }
            
            $row = sqlsrv_fetch_array($stmtTransaccion, SQLSRV_FETCH_ASSOC);
            $idTransaccion = $row ? $row['id_transaccion'] : null;
            
            if (!$idTransaccion) {
                throw new Exception('Error: No se pudo obtener el ID de transacción');
            }
            
            // Insertar detalles y descontar de lotes (FIFO)
            foreach ($data['items'] as $item) {
                $cantidadPendiente = $item['cantidad'];
                $idProducto = $item['id_producto'];
                
                // Buscar lotes del producto ordenados por fecha de creación (FIFO)
                $sqlLotes = "SELECT id_lote, stock_actual, codigo_lote 
                            FROM BD_PRODUCCIONDESARROLLO.dbo.lote 
                            WHERE id_producto = ? AND stock_actual > 0
                            ORDER BY fecha_creacion ASC, id_lote ASC";
                $stmtLotes = sqlsrv_query($this->db, $sqlLotes, [$idProducto]);
                if ($stmtLotes === false) {
                    throw new Exception('Error al buscar lotes: ' . print_r(sqlsrv_errors(), true));
                }
                
                $lotes = [];
                while ($rowLote = sqlsrv_fetch_array($stmtLotes, SQLSRV_FETCH_ASSOC)) {
                    $lotes[] = $rowLote;
                }
                
                if (empty($lotes)) {
                    throw new Exception("No hay stock disponible para el producto: {$item['nombre']}");
                }
                
                $idLoteUsado = null;
                $saldoKardex = 0;
                
                // Descontar de los lotes más antiguos primero
                foreach ($lotes as $lote) {
                    if ($cantidadPendiente <= 0) break;
                    
                    $idLote = $lote['id_lote'];
                    $stockDisponible = $lote['stock_actual'];
                    $cantidadDescontar = min($cantidadPendiente, $stockDisponible);
                    $nuevoStock = $stockDisponible - $cantidadDescontar;
                    
                    // Actualizar stock del lote
                    $sqlUpdateLote = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.lote 
                                     SET stock_actual = ? 
                                     WHERE id_lote = ?";
                    $stmtUpdateLote = sqlsrv_query($this->db, $sqlUpdateLote, [$nuevoStock, $idLote]);
                    if ($stmtUpdateLote === false) {
                        throw new Exception('Error al actualizar stock del lote: ' . print_r(sqlsrv_errors(), true));
                    }
                    
                    // Obtener saldo actual del kardex para este producto
                    $sqlSaldo = "SELECT TOP 1 saldo_final 
                               FROM BD_PRODUCCIONDESARROLLO.dbo.kardex 
                               WHERE id_lote = ? 
                               ORDER BY fecha DESC, id_kardex DESC";
                    $stmtSaldo = sqlsrv_query($this->db, $sqlSaldo, [$idLote]);
                    $saldoActual = 0;
                    if ($stmtSaldo && $rowSaldo = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                        $saldoActual = $rowSaldo['saldo_final'];
                    }
                    $nuevoSaldo = $saldoActual - $cantidadDescontar;
                    
                    // Registrar en kardex
                    $sqlKardex = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                                (id_lote, id_transaccion, tipo_movimiento, cantidad, saldo_final, fecha)
                                VALUES (?, ?, 'VENTA', ?, ?, GETDATE())";
                    $stmtKardex = sqlsrv_query($this->db, $sqlKardex, [$idLote, $idTransaccion, $cantidadDescontar, $nuevoSaldo]);
                    if ($stmtKardex === false) {
                        throw new Exception('Error al registrar kardex: ' . print_r(sqlsrv_errors(), true));
                    }
                    
                    $cantidadPendiente -= $cantidadDescontar;
                    $idLoteUsado = $idLote;
                    $saldoKardex = $nuevoSaldo;
                }
                
                if ($cantidadPendiente > 0) {
                    throw new Exception("Stock insuficiente para el producto: {$item['nombre']}. Faltan {$cantidadPendiente} unidades.");
                }
                
                // Insertar detalle de transacción con el lote usado (el más antiguo)
                $sqlDetalle = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle 
                               (id_transaccion, id_producto, id_lote, cantidad, precio_unitario, subtotal)
                               OUTPUT INSERTED.id_detalle
                               VALUES (?, ?, ?, ?, ?, ?)";
                $paramsDetalle = [
                    $idTransaccion,
                    $idProducto,
                    $idLoteUsado,
                    $item['cantidad'],
                    $item['precio'],
                    $item['subtotal']
                ];
                $stmtDetalle = sqlsrv_query($this->db, $sqlDetalle, $paramsDetalle);
                if ($stmtDetalle === false) {
                    throw new Exception('Error al insertar detalle: ' . print_r(sqlsrv_errors(), true));
                }
                
                // Obtener el ID del detalle insertado
                $rowDetalle = sqlsrv_fetch_array($stmtDetalle, SQLSRV_FETCH_ASSOC);
                $idDetalle = $rowDetalle ? $rowDetalle['id_detalle'] : null;
            }
            
            sqlsrv_commit($this->db);
            return ['success' => true, 'id_transaccion' => $idTransaccion];
            
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listarVentasHoy() {
        $sql = "SELECT t.id_transaccion, t.fecha_creacion as fecha, t.metodo_pago, t.total, t.estado,
                       c.nombre_rs as cliente
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                WHERE CAST(t.fecha_creacion as DATE) = CAST(GETDATE() as DATE)
                AND t.tipo_op = 'VENTA'
                ORDER BY t.fecha_creacion DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    private function getLastInsertId() {
        $sql = "SELECT SCOPE_IDENTITY() as id";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return $row['id'];
        }
        return null;
    }
}
?>
