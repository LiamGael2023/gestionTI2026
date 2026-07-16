<?php
/**
 * ChatToolsModel - Herramientas de consulta seguras para el chatbot IA
 * Todas las queries son READ-ONLY y usan prepared statements
 */
class ChatToolsModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ============================================================
    // TOOL 1: Consultar stock de productos
    // ============================================================
    public function consultarStock($params = []) {
        $sqlParams = [];
        $where = ["l.stock_actual > 0", "p.maneja_stock = 1"];

        if (!empty($params['producto'])) {
            $where[] = "(p.nombre LIKE ? OR p.nombre_cientifico LIKE ?)";
            $like = '%' . $params['producto'] . '%';
            $sqlParams[] = $like;
            $sqlParams[] = $like;
        }
        if (!empty($params['clase'])) {
            $where[] = "c.nombre_clase LIKE ?";
            $sqlParams[] = '%' . $params['clase'] . '%';
        }
        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }

        $sql = "SELECT TOP 20
                    p.nombre AS producto,
                    p.nombre_cientifico,
                    c.nombre_clase AS clase,
                    cp.nombre_centro AS centro,
                    l.codigo_lote AS lote,
                    l.stock_actual AS stock,
                    p.unidad_medida
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                WHERE " . implode(" AND ", $where) . "
                ORDER BY cp.nombre_centro, p.nombre, l.fecha_creacion ASC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar stock'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 2: Consultar ventas por período
    // ============================================================
    public function consultarVentas($params = []) {
        $sqlParams = ["VENTA"];
        $where = ["t.tipo_op = ?"];

        if (!empty($params['metodo_pago'])) {
            $where[] = "t.metodo_pago = ?";
            $sqlParams[] = $params['metodo_pago'];
        }
        if (!empty($params['fecha_desde'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }
        if (!empty($params['estado'])) {
            $where[] = "t.estado = ?";
            $sqlParams[] = $params['estado'];
        }
        if (!empty($params['cliente'])) {
            $where[] = "(c.nombre_rs LIKE ? OR c.dni_ruc LIKE ?)";
            $like = '%' . $params['cliente'] . '%';
            $sqlParams[] = $like;
            $sqlParams[] = $like;
        }

        $sql = "SELECT TOP 20
                    t.id_transaccion,
                    t.fecha_creacion,
                    t.estado,
                    t.metodo_pago,
                    t.total,
                    c.nombre_rs AS cliente,
                    cp.nombre_centro AS centro,
                    t.serie_comprobante + '-' + t.correlativo_comprobante AS comprobante
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente AND c.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro AND cp.activo = 1
                WHERE " . implode(" AND ", $where) . "
                ORDER BY t.fecha_creacion DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar ventas'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_creacion']) && $row['fecha_creacion'] instanceof DateTime) {
                $row['fecha_creacion'] = $row['fecha_creacion']->format('Y-m-d H:i');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 3: Consultar proformas
    // ============================================================
    public function consultarProformas($params = []) {
        $sqlParams = ["VENTA"];
        $where = ["t.tipo_op = ?"];

        if (!empty($params['estado'])) {
            $where[] = "t.estado = ?";
            $sqlParams[] = $params['estado'];
        }
        if (!empty($params['cliente'])) {
            $where[] = "(c.nombre_rs LIKE ? OR c.dni_ruc LIKE ?)";
            $like = '%' . $params['cliente'] . '%';
            $sqlParams[] = $like;
            $sqlParams[] = $like;
        }
        if (!empty($params['fecha_desde'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }

        $sql = "SELECT TOP 20
                    t.id_transaccion,
                    t.fecha_creacion,
                    t.estado,
                    t.total,
                    c.nombre_rs AS cliente,
                    cp.nombre_centro AS centro,
                    t.responsable_venta
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente AND c.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro AND cp.activo = 1
                WHERE " . implode(" AND ", $where) . "
                ORDER BY t.fecha_creacion DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar proformas'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_creacion']) && $row['fecha_creacion'] instanceof DateTime) {
                $row['fecha_creacion'] = $row['fecha_creacion']->format('Y-m-d H:i');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 4: Consultar vouchers
    // ============================================================
    public function consultarVouchers($params = []) {
        $sqlParams = [];
        $where = ["v.activo = 1"];

        if (!empty($params['fecha_desde'])) {
            $where[] = "v.fecha_deposito >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "v.fecha_deposito <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }
        if (isset($params['asignado'])) {
            if ($params['asignado'] === 'si') {
                $where[] = "EXISTS (SELECT 1 FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t2 WHERE t2.id_voucher = v.id_voucher)";
            } elseif ($params['asignado'] === 'no') {
                $where[] = "NOT EXISTS (SELECT 1 FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t2 WHERE t2.id_voucher = v.id_voucher)";
            }
        }

        $sql = "SELECT TOP 20
                    v.id_voucher,
                    v.num_operacion,
                    v.monto_total,
                    v.fecha_deposito,
                    COUNT(t.id_transaccion) AS total_proformas,
                    ISNULL(SUM(t.total), 0) AS monto_asignado
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito v
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON v.id_voucher = t.id_voucher
                WHERE " . implode(" AND ", $where) . "
                GROUP BY v.id_voucher, v.num_operacion, v.monto_total, v.fecha_deposito
                ORDER BY v.id_voucher DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar vouchers'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_deposito']) && $row['fecha_deposito'] instanceof DateTime) {
                $row['fecha_deposito'] = $row['fecha_deposito']->format('Y-m-d');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 5: Consultar catálogo de productos con precios
    // ============================================================
    public function consultarProductos($params = []) {
        $sqlParams = [];
        $where = ["p.activo = 1"];

        if (!empty($params['clase'])) {
            $where[] = "c.nombre_clase LIKE ?";
            $sqlParams[] = '%' . $params['clase'] . '%';
        }
        if (!empty($params['centro'])) {
            $where[] = "EXISTS (SELECT 1 FROM BD_PRODUCCIONDESARROLLO.dbo.producto_centro pc_f 
                        JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp_f ON pc_f.id_centro = cp_f.id_centro
                        WHERE pc_f.id_producto = p.id_producto AND cp_f.nombre_centro LIKE ?)";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }
        if (!empty($params['tipo_precio'])) {
            $where[] = "p.tipo_precio = ?";
            $sqlParams[] = $params['tipo_precio'];
        }
        if (!empty($params['nombre'])) {
            $where[] = "(p.nombre LIKE ? OR p.nombre_cientifico LIKE ?)";
            $like = '%' . $params['nombre'] . '%';
            $sqlParams[] = $like;
            $sqlParams[] = $like;
        }

        $sql = "SELECT TOP 20
                    p.id_producto,
                    p.nombre AS producto,
                    p.nombre_cientifico,
                    p.unidad_medida,
                    p.tipo_precio,
                    p.porcentaje_uit,
                    c.nombre_clase AS clase,
                    STUFF((SELECT ', ' + cp3.nombre_centro 
                           FROM BD_PRODUCCIONDESARROLLO.dbo.producto_centro pc3
                           JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp3 ON pc3.id_centro = cp3.id_centro
                           WHERE pc3.id_producto = p.id_producto
                           ORDER BY cp3.nombre_centro
                           FOR XML PATH('')), 1, 2, '') AS centro,
                    hp_last.precio_oficial AS precio_variable,
                    uit_actual.valor AS valor_uit,
                    CASE
                        WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                            THEN uit_actual.valor * p.porcentaje_uit
                        WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                            THEN hp_last.precio_oficial
                        ELSE NULL
                    END AS precio_vigente
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)
                ) hp_last ON p.id_producto = hp_last.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE())
                WHERE " . implode(" AND ", $where) . "
                ORDER BY c.nombre_clase, p.nombre";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar productos'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 6: Consultar clientes
    // ============================================================
    public function consultarClientes($params = []) {
        $sqlParams = [];
        $where = ["c.activo = 1"];

        if (!empty($params['nombre'])) {
            $where[] = "(c.nombre_rs LIKE ? OR c.dni_ruc LIKE ?)";
            $like = '%' . $params['nombre'] . '%';
            $sqlParams[] = $like;
            $sqlParams[] = $like;
        }
        if (!empty($params['tipo'])) {
            $tipoValor = ($params['tipo'] === 'Planilla') ? 1 : 0;
            $where[] = "c.tipo_cliente = ?";
            $sqlParams[] = $tipoValor;
        }

        $sql = "SELECT TOP 20
                    c.id_cliente,
                    c.dni_ruc,
                    c.nombre_rs AS nombre,
                    CASE WHEN c.tipo_cliente = 1 THEN 'Planilla' ELSE 'Externo' END AS tipo,
                    COUNT(t.id_transaccion) AS total_transacciones,
                    ISNULL(SUM(t.total), 0) AS total_acumulado
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente c
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON c.id_cliente = t.id_cliente AND t.estado = 'PROCESADO'
                WHERE " . implode(" AND ", $where) . "
                GROUP BY c.id_cliente, c.dni_ruc, c.nombre_rs, c.tipo_cliente
                ORDER BY total_acumulado DESC, c.nombre_rs ASC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar clientes'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 7: Consultar mermas por período
    // ============================================================
    public function consultarMermas($params = []) {
        $sqlParams = [];
        $where = ["k.tipo_movimiento = 'MERMA'"];

        if (!empty($params['fecha_desde'])) {
            $where[] = "CAST(k.fecha AS DATE) >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "CAST(k.fecha AS DATE) <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }
        if (!empty($params['producto'])) {
            $where[] = "p.nombre LIKE ?";
            $sqlParams[] = '%' . $params['producto'] . '%';
        }

        $sql = "SELECT TOP 20
                    k.id_kardex,
                    k.fecha,
                    k.cantidad AS cantidad_merma,
                    l.codigo_lote AS lote,
                    p.nombre AS producto,
                    c.nombre_clase AS clase,
                    cp.nombre_centro AS centro,
                    hp_last.precio_oficial AS precio_variable,
                    uit_actual.valor AS valor_uit,
                    CASE
                        WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                            THEN k.cantidad * (uit_actual.valor * p.porcentaje_uit)
                        WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                            THEN k.cantidad * hp_last.precio_oficial
                        ELSE 0
                    END AS valor_perdida
                FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l ON k.id_lote = l.id_lote
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)
                ) hp_last ON p.id_producto = hp_last.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE())
                WHERE " . implode(" AND ", $where) . "
                ORDER BY k.fecha DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar mermas'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha']) && $row['fecha'] instanceof DateTime) {
                $row['fecha'] = $row['fecha']->format('Y-m-d H:i');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // HELPER: Formatear resultado como texto para el modelo
    // ============================================================
    public function formatearResultado($nombreTool, $data) {
        if (isset($data['error'])) {
            return "Error: " . $data['error'];
        }
        if (empty($data)) {
            return "No se encontraron resultados para esta consulta.";
        }

        // Si viene estructurado con columns/rows (ej: consultarGrafico), iterar solo los rows
        $rows = $data;
        if (isset($data['rows']) && isset($data['columns'])) {
            $rows = $data['rows'];
        }

        $lineas = [];
        $lineas[] = "Resultados de la consulta '" . $nombreTool . "' (" . count($rows) . " registros):";
        if (isset($data['grafico'])) {
            $lineas[] = "(Se genero un grafico de tipo " . $data['grafico']['tipo'] . " para visualizar estos datos)";
        }
        $lineas[] = "";

        foreach ($rows as $i => $row) {
            $lineas[] = "--- Registro " . ($i + 1) . " ---";
            foreach ($row as $key => $val) {
                if ($val === null) continue;
                $lineas[] = ucfirst(str_replace('_', ' ', $key)) . ": " . $val;
            }
            $lineas[] = "";
        }

        return implode("\n", $lineas);
    }

    // ============================================================
    // HELPER: Formatear resultado como array estructurado para el frontend
    // Devuelve: ['columns' => [...], 'rows' => [...], 'grafico' => [...] (opcional)]
    // ============================================================
    public function formatearResultadoRaw($data) {
        if (isset($data['error']) || empty($data) || !is_array($data)) {
            return null;
        }

        // Si ya viene estructurado con columns/rows (ej: consultarGrafico)
        if (isset($data['columns']) && isset($data['rows'])) {
            $result = $data;
            $result['total'] = count($data['rows']);
            return $result;
        }

        $columns = [];
        $rows = [];

        // Obtener nombres de columnas del primer registro
        $firstRow = $data[0];
        foreach ($firstRow as $key => $val) {
            $columns[] = [
                'key' => $key,
                'label' => ucfirst(str_replace('_', ' ', $key))
            ];
        }

        // Obtener filas de datos (limpiar nulls)
        foreach ($data as $row) {
            $cleanRow = [];
            foreach ($row as $key => $val) {
                if ($val === null) {
                    $cleanRow[$key] = '-';
                } elseif (is_numeric($val) && strpos($key, 'precio') !== false || strpos($key, 'monto') !== false || strpos($key, 'total') !== false || strpos($key, 'valor') !== false) {
                    $cleanRow[$key] = 'S/ ' . number_format((float)$val, 2);
                } elseif (is_numeric($val) && (strpos($key, 'stock') !== false || strpos($key, 'cantidad') !== false)) {
                    $cleanRow[$key] = number_format((float)$val, 0);
                } else {
                    $cleanRow[$key] = $val;
                }
            }
            $rows[] = $cleanRow;
        }

        return ['columns' => $columns, 'rows' => $rows, 'total' => count($rows)];
    }

    // ============================================================
    // TOOL 8: Consultar kardex (todos los movimientos de inventario)
    // ============================================================
    public function consultarKardex($params = []) {
        $sqlParams = [];
        $where = ["1=1"];

        if (!empty($params['producto'])) {
            $where[] = "p.nombre LIKE ?";
            $sqlParams[] = '%' . $params['producto'] . '%';
        }
        if (!empty($params['tipo_movimiento'])) {
            $where[] = "k.tipo_movimiento = ?";
            $sqlParams[] = $params['tipo_movimiento'];
        }
        if (!empty($params['fecha_desde'])) {
            $where[] = "CAST(k.fecha AS DATE) >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "CAST(k.fecha AS DATE) <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }

        $sql = "SELECT TOP 30
                    k.id_kardex,
                    k.fecha,
                    k.tipo_movimiento,
                    k.cantidad,
                    k.saldo_final,
                    l.codigo_lote AS lote,
                    p.nombre AS producto,
                    c.nombre_clase AS clase,
                    cp.nombre_centro AS centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l ON k.id_lote = l.id_lote
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                WHERE " . implode(" AND ", $where) . "
                ORDER BY k.fecha DESC, k.id_kardex DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar kardex'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha']) && $row['fecha'] instanceof DateTime) {
                $row['fecha'] = $row['fecha']->format('Y-m-d H:i');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 9: Top productos más vendidos
    // ============================================================
    public function consultarTopProductosVendidos($params = []) {
        $sqlParams = ["PROCESADO", "VENTA"];
        $where = ["t.estado = ?", "t.tipo_op = ?"];

        if (!empty($params['fecha_desde'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }
        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }

        $limite = isset($params['limite']) ? intval($params['limite']) : 10;
        if ($limite < 1 || $limite > 20) $limite = 10;

        $orden = (!empty($params['orden']) && $params['orden'] === 'monto') ? 'ingresos' : 'unidades';
        $orderBy = ($orden === 'ingresos') ? 'ingresos DESC' : 'unidades_vendidas DESC';

        $sql = "SELECT TOP $limite
                    p.id_producto,
                    p.nombre AS producto,
                    p.unidad_medida,
                    c.nombre_clase AS clase,
                    cp.nombre_centro AS centro,
                    SUM(td.cantidad) AS unidades_vendidas,
                    SUM(td.subtotal) AS ingresos,
                    COUNT(DISTINCT t.id_transaccion) AS total_transacciones
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle td
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON td.id_producto = p.id_producto AND p.activo = 1
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON td.id_transaccion = t.id_transaccion
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
                WHERE " . implode(" AND ", $where) . "
                GROUP BY p.id_producto, p.nombre, p.unidad_medida, c.nombre_clase, cp.nombre_centro
                ORDER BY $orderBy";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar top productos'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 10: Valorización de inventario
    // ============================================================
    public function consultarValorizacionInventario($params = []) {
        $sqlParams = [];
        $where = ["l.stock_actual > 0", "p.maneja_stock = 1"];

        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }
        if (!empty($params['clase'])) {
            $where[] = "c.nombre_clase LIKE ?";
            $sqlParams[] = '%' . $params['clase'] . '%';
        }
        if (!empty($params['producto'])) {
            $where[] = "p.nombre LIKE ?";
            $sqlParams[] = '%' . $params['producto'] . '%';
        }

        $sql = "SELECT TOP 20
                    p.nombre AS producto,
                    p.unidad_medida,
                    p.tipo_precio,
                    c.nombre_clase AS clase,
                    cp.nombre_centro AS centro,
                    l.codigo_lote AS lote,
                    l.stock_actual AS stock,
                    hp_last.precio_oficial AS precio_variable,
                    uit_actual.valor AS valor_uit,
                    CASE
                        WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                            THEN uit_actual.valor * p.porcentaje_uit
                        WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                            THEN hp_last.precio_oficial
                        ELSE 0
                    END AS precio_unitario,
                    CASE
                        WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                            THEN l.stock_actual * (uit_actual.valor * p.porcentaje_uit)
                        WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                            THEN l.stock_actual * hp_last.precio_oficial
                        ELSE 0
                    END AS valor_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)
                ) hp_last ON p.id_producto = hp_last.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE())
                WHERE " . implode(" AND ", $where) . "
                ORDER BY valor_total DESC, cp.nombre_centro, p.nombre";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar valorización'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_lote']) && $row['fecha_lote'] instanceof DateTime) {
                $row['fecha_lote'] = $row['fecha_lote']->format('Y-m-d');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 11: Ventas por mes (tendencia)
    // ============================================================
    public function consultarVentasPorMes($params = []) {
        $sqlParams = [];
        $where = ["t.tipo_op = 'VENTA'", "t.estado = 'PROCESADO'"];

        $meses = isset($params['meses']) ? intval($params['meses']) : 6;
        if ($meses < 1 || $meses > 24) $meses = 6;

        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }
        if (!empty($params['metodo_pago'])) {
            $where[] = "t.metodo_pago = ?";
            $sqlParams[] = $params['metodo_pago'];
        }

        $sql = "SELECT
                    FORMAT(t.fecha_creacion, 'yyyy-MM') AS mes,
                    FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE') AS mes_label,
                    COUNT(*) AS transacciones,
                    ISNULL(SUM(t.total), 0) AS monto_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
                WHERE " . implode(" AND ", $where) . "
                  AND t.fecha_creacion >= DATEADD(month, -" . ($meses - 1) . ", DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
                GROUP BY FORMAT(t.fecha_creacion, 'yyyy-MM'), FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE')
                ORDER BY FORMAT(t.fecha_creacion, 'yyyy-MM') ASC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar ventas por mes'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 12: Vouchers con saldo restante
    // ============================================================
    public function consultarVouchersSaldo($params = []) {
        $sqlParams = [];
        $where = ["v.activo = 1"];

        if (!empty($params['fecha_desde'])) {
            $where[] = "v.fecha_deposito >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "v.fecha_deposito <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }
        if (isset($params['saldo_estado'])) {
            if ($params['saldo_estado'] === 'positivo') {
                $where[] = "v.monto_total > ISNULL((SELECT SUM(t2.total) FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t2 WHERE t2.id_voucher = v.id_voucher), 0)";
            } elseif ($params['saldo_estado'] === 'cero') {
                $where[] = "v.monto_total <= ISNULL((SELECT SUM(t2.total) FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t2 WHERE t2.id_voucher = v.id_voucher), 0)";
            }
        }

        $sql = "SELECT TOP 20
                    v.id_voucher,
                    v.num_operacion,
                    v.monto_total,
                    ISNULL((SELECT SUM(t2.total) FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t2 WHERE t2.id_voucher = v.id_voucher), 0) AS monto_asignado,
                    (v.monto_total - ISNULL((SELECT SUM(t2.total) FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t2 WHERE t2.id_voucher = v.id_voucher), 0)) AS saldo_restante,
                    v.fecha_deposito
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito v
                WHERE " . implode(" AND ", $where) . "
                ORDER BY saldo_restante DESC, v.id_voucher DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar vouchers saldo'];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_deposito']) && $row['fecha_deposito'] instanceof DateTime) {
                $row['fecha_deposito'] = $row['fecha_deposito']->format('Y-m-d');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // TOOL 13: Generar datos para gráficos
    // ============================================================
    public function consultarGrafico($params = []) {
        $tipo = $params['tipo'] ?? 'ventas_mes';

        switch ($tipo) {
            // ---------- Ventas mensuales (bar/line) ----------
            case 'ventas_mes':
                return $this->_graficoVentasMes($params);

            // ---------- Top productos vendidos (horizontal bar) ----------
            case 'top_productos':
                return $this->_graficoTopProductos($params);

            // ---------- Stock por centro (pie/donut) ----------
            case 'stock_centro':
                return $this->_graficoStockCentro($params);

            // ---------- Ventas por metodo pago (pie/donut) ----------
            case 'ventas_metodo_pago':
                return $this->_graficoVentasMetodoPago($params);

            // ---------- Valorización por clase (bar) ----------
            case 'valorizacion_clase':
                return $this->_graficoValorizacionClase($params);

            // ---------- Mermas mensuales (bar/line) ----------
            case 'mermas_mes':
                return $this->_graficoMermasMes($params);

            // ---------- Tendencia ventas vs donaciones (line) ----------
            case 'ventas_vs_donaciones':
                return $this->_graficoVentasVsDonaciones($params);

            default:
                return ['error' => 'Tipo de gráfico no soportado: ' . $tipo . '. Tipos disponibles: ventas_mes, top_productos, stock_centro, ventas_metodo_pago, valorizacion_clase, mermas_mes, ventas_vs_donaciones'];
        }
    }

    // --- Gráfico: Ventas por mes ---
    private function _graficoVentasMes($params) {
        $sqlParams = ["PROCESADO"];
        $where = ["t.estado = ?"];

        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }

                $sql = "SELECT
                    FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE') AS mes,
                    ISNULL(SUM(t.total), 0) AS monto_total,
                    COUNT(*) AS transacciones
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro AND cp.activo = 1
                WHERE " . implode(" AND ", $where) . "
                  AND t.fecha_creacion >= DATEADD(month, -11, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
                GROUP BY FORMAT(t.fecha_creacion, 'yyyy-MM'), FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE')
                ORDER BY FORMAT(t.fecha_creacion, 'yyyy-MM') ASC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar gráfico ventas_mes'];
        }

        $categorias = [];
        $datosMonto = [];
        $datosCant = [];
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $categorias[] = $row['mes'];
            $datosMonto[] = round((float)$row['monto_total'], 2);
            $datosCant[] = (int)$row['transacciones'];
            $rows[] = $row;
        }

        $centroLabel = !empty($params['centro']) ? ' - ' . $params['centro'] : '';

        return [
            'columns' => [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'monto_total', 'label' => 'Monto Total'],
                ['key' => 'transacciones', 'label' => 'Transacciones']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'bar',
                'titulo' => 'Ventas mensuales' . $centroLabel,
                'categorias' => $categorias,
                'series' => [
                    ['nombre' => 'Monto (S/)', 'datos' => $datosMonto],
                    ['nombre' => 'Transacciones', 'datos' => $datosCant]
                ],
                'formato' => 'moneda',
                'height' => 320
            ]
        ];
    }

    // --- Gráfico: Top productos vendidos ---
    private function _graficoTopProductos($params) {
        $sqlParams = ["PROCESADO"];
        $where = ["t.estado = ?"];

        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }
        if (!empty($params['fecha_desde'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }

        $limite = isset($params['limite']) ? min(intval($params['limite']), 15) : 8;

        $sql = "SELECT TOP $limite
                    p.nombre AS producto,
                    SUM(td.cantidad) AS unidades,
                    SUM(td.subtotal) AS ingresos
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle td
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON td.id_transaccion = t.id_transaccion
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON td.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
                WHERE " . implode(" AND ", $where) . "
                GROUP BY p.nombre
                ORDER BY unidades DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar gráfico top_productos'];
        }

        $categorias = [];
        $datosUnidades = [];
        $datosIngresos = [];
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $categorias[] = $row['producto'];
            $datosUnidades[] = (int)$row['unidades'];
            $datosIngresos[] = round((float)$row['ingresos'], 2);
            $rows[] = $row;
        }

        return [
            'columns' => [
                ['key' => 'producto', 'label' => 'Producto'],
                ['key' => 'unidades', 'label' => 'Unidades'],
                ['key' => 'ingresos', 'label' => 'Ingresos']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'horizontalBar',
                'titulo' => 'Top ' . $limite . ' productos más vendidos',
                'categorias' => $categorias,
                'series' => [['nombre' => 'Unidades vendidas', 'datos' => $datosUnidades]],
                'formato' => 'entero',
                'height' => $limite * 40 + 60
            ]
        ];
    }

    // --- Gráfico: Stock por centro (pie/donut) ---
    private function _graficoStockCentro($params) {
        $sqlParams = [];
        $where = ["l.stock_actual > 0", "p.maneja_stock = 1"];

        $sql = "SELECT
                    cp.nombre_centro AS centro,
                    SUM(l.stock_actual) AS stock_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                WHERE " . implode(" AND ", $where) . "
                GROUP BY cp.nombre_centro
                ORDER BY stock_total DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar gráfico stock_centro'];
        }

        $categorias = [];
        $datos = [];
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $label = $row['centro'] ?? 'Sin centro';
            $categorias[] = $label;
            $datos[] = (int)$row['stock_total'];
            $rows[] = ['centro' => $label, 'stock_total' => (int)$row['stock_total']];
        }

        return [
            'columns' => [
                ['key' => 'centro', 'label' => 'Centro'],
                ['key' => 'stock_total', 'label' => 'Stock Total']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'donut',
                'titulo' => 'Distribución de stock por centro',
                'categorias' => $categorias,
                'series' => [['nombre' => 'Stock', 'datos' => $datos]],
                'formato' => 'entero'
            ]
        ];
    }

    // --- Gráfico: Ventas por método de pago ---
    private function _graficoVentasMetodoPago($params) {
        $sqlParams = ["PROCESADO"];
        $where = ["t.estado = ?"];

        if (!empty($params['fecha_desde'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) >= ?";
            $sqlParams[] = $params['fecha_desde'];
        }
        if (!empty($params['fecha_hasta'])) {
            $where[] = "CAST(t.fecha_creacion AS DATE) <= ?";
            $sqlParams[] = $params['fecha_hasta'];
        }

        $sql = "SELECT
                    ISNULL(t.metodo_pago, 'Sin especificar') AS metodo_pago,
                    COUNT(*) AS cantidad,
                    ISNULL(SUM(t.total), 0) AS monto_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                WHERE " . implode(" AND ", $where) . "
                GROUP BY t.metodo_pago
                ORDER BY monto_total DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar gráfico ventas_metodo_pago'];
        }

        $categorias = [];
        $datos = [];
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $categorias[] = $row['metodo_pago'];
            $datos[] = round((float)$row['monto_total'], 2);
            $rows[] = $row;
        }

        return [
            'columns' => [
                ['key' => 'metodo_pago', 'label' => 'Método de Pago'],
                ['key' => 'cantidad', 'label' => 'Cantidad'],
                ['key' => 'monto_total', 'label' => 'Monto Total']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'pie',
                'titulo' => 'Distribución de ventas por método de pago',
                'categorias' => $categorias,
                'series' => [['nombre' => 'Monto', 'datos' => $datos]],
                'formato' => 'moneda'
            ]
        ];
    }

    // --- Gráfico: Valorización de inventario por clase ---
    private function _graficoValorizacionClase($params) {
        $sqlParams = [];
        $where = ["l.stock_actual > 0", "p.maneja_stock = 1"];

        $sql = "SELECT
                    c.nombre_clase AS clase,
                    SUM(
                        CASE
                            WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                                THEN l.stock_actual * (uit_actual.valor * p.porcentaje_uit)
                            WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                                THEN l.stock_actual * hp_last.precio_oficial
                            ELSE 0
                        END
                    ) AS valor_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)
                ) hp_last ON p.id_producto = hp_last.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE())
                WHERE " . implode(" AND ", $where) . "
                GROUP BY c.nombre_clase
                ORDER BY valor_total DESC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar gráfico valorizacion_clase'];
        }

        $categorias = [];
        $datos = [];
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $label = $row['clase'] ?? 'Sin clase';
            $categorias[] = $label;
            $datos[] = round((float)$row['valor_total'], 2);
            $rows[] = ['clase' => $label, 'valor_total' => round((float)$row['valor_total'], 2)];
        }

        return [
            'columns' => [
                ['key' => 'clase', 'label' => 'Clase'],
                ['key' => 'valor_total', 'label' => 'Valor Total']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'bar',
                'titulo' => 'Valorización de inventario por clase',
                'categorias' => $categorias,
                'series' => [['nombre' => 'Valor total (S/)', 'datos' => $datos]],
                'formato' => 'moneda',
                'height' => 320
            ]
        ];
    }

    // --- Gráfico: Mermas mensuales ---
    private function _graficoMermasMes($params) {
        $sqlParams = [];
        $where = ["k.tipo_movimiento = 'MERMA'"];

        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }

        $sql = "SELECT
                    FORMAT(k.fecha, 'MMM yyyy', 'es-PE') AS mes,
                    SUM(k.cantidad) AS cantidad_total,
                    COUNT(*) AS movimientos,
                    ISNULL(SUM(
                        CASE
                            WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                                THEN k.cantidad * (uit_actual.valor * p.porcentaje_uit)
                            WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                                THEN k.cantidad * hp_last.precio_oficial
                            ELSE 0
                        END
                    ), 0) AS valor_perdida
                FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l ON k.id_lote = l.id_lote
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)
                ) hp_last ON p.id_producto = hp_last.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE())
                WHERE " . implode(" AND ", $where) . "
                  AND k.fecha >= DATEADD(month, -11, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
                GROUP BY FORMAT(k.fecha, 'yyyy-MM'), FORMAT(k.fecha, 'MMM yyyy', 'es-PE')
                ORDER BY FORMAT(k.fecha, 'yyyy-MM') ASC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar gráfico mermas_mes'];
        }

        $categorias = [];
        $datosCantidad = [];
        $datosValor = [];
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $categorias[] = $row['mes'];
            $datosCantidad[] = (int)$row['cantidad_total'];
            $datosValor[] = round((float)$row['valor_perdida'], 2);
            $rows[] = $row;
        }

        return [
            'columns' => [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'cantidad_total', 'label' => 'Cantidad Total'],
                ['key' => 'movimientos', 'label' => 'Movimientos'],
                ['key' => 'valor_perdida', 'label' => 'Valor Pérdida']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'bar',
                'titulo' => 'Mermas mensuales',
                'categorias' => $categorias,
                'series' => [
                    ['nombre' => 'Cantidad', 'datos' => $datosCantidad],
                    ['nombre' => 'Valor pérdida (S/)', 'datos' => $datosValor]
                ],
                'formato' => 'moneda',
                'height' => 320
            ]
        ];
    }

    // --- Gráfico: Ventas vs Donaciones (tendencia) ---
    private function _graficoVentasVsDonaciones($params) {
        $sqlParams = ["PROCESADO"];
        $where = ["t.estado = ?"];

        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
            $sqlParams[] = '%' . $params['centro'] . '%';
        }

        $sql = "SELECT
                    FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE') AS mes,
                    ISNULL(SUM(CASE WHEN t.metodo_pago = 'VENTA' THEN t.total ELSE 0 END), 0) AS monto_ventas,
                    ISNULL(SUM(CASE WHEN t.metodo_pago = 'DONACION' THEN t.total ELSE 0 END), 0) AS monto_donaciones
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
                WHERE " . implode(" AND ", $where) . "
                  AND t.fecha_creacion >= DATEADD(month, -11, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))
                GROUP BY FORMAT(t.fecha_creacion, 'yyyy-MM'), FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE')
                ORDER BY FORMAT(t.fecha_creacion, 'yyyy-MM') ASC";

        $stmt = sqlsrv_query($this->db, $sql, $sqlParams);
        if ($stmt === false) {
            return ['error' => 'Error al consultar gráfico ventas_vs_donaciones'];
        }

        $categorias = [];
        $datosVentas = [];
        $datosDonaciones = [];
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $categorias[] = $row['mes'];
            $datosVentas[] = round((float)$row['monto_ventas'], 2);
            $datosDonaciones[] = round((float)$row['monto_donaciones'], 2);
            $rows[] = $row;
        }

        return [
            'columns' => [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'monto_ventas', 'label' => 'Monto Ventas'],
                ['key' => 'monto_donaciones', 'label' => 'Monto Donaciones']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'area',
                'titulo' => 'Tendencia: Ventas vs Donaciones',
                'categorias' => $categorias,
                'series' => [
                    ['nombre' => 'Ventas (S/)', 'datos' => $datosVentas],
                    ['nombre' => 'Donaciones (S/)', 'datos' => $datosDonaciones]
                ],
                'formato' => 'moneda',
                'height' => 320
            ]
        ];
    }

    // ============================================================
    // TOOL 14: Resumen ejecutivo diario
    // ============================================================
    public function consultarResumen($params = []) {
        $hoy = date('Y-m-d');
        $resumen = [];

        // Ventas de hoy
        $sql = "SELECT COUNT(*) AS cantidad, ISNULL(SUM(total), 0) AS monto_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion
                WHERE CAST(fecha_creacion AS DATE) = ? AND estado = 'PROCESADO'";
        $stmt = sqlsrv_query($this->db, $sql, [$hoy]);
        $ventasHoy = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: ['cantidad' => 0, 'monto_total' => 0];

        // Proformas pendientes
        $sql = "SELECT COUNT(*) AS pendientes
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion
                WHERE estado = 'PENDIENTE' AND tipo_op = 'VENTA'";
        $stmt = sqlsrv_query($this->db, $sql);
        $proformas = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: ['pendientes' => 0];

        // Stock critico (< 10)
        $sql = "SELECT COUNT(DISTINCT p.id_producto) AS productos_criticos
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                WHERE l.stock_actual > 0 AND l.stock_actual < 10 AND p.maneja_stock = 1";
        $stmt = sqlsrv_query($this->db, $sql);
        $criticos = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: ['productos_criticos' => 0];

        // Vouchers sin asignar
        $sql = "SELECT COUNT(*) AS sin_asignar, ISNULL(SUM(v.monto_total), 0) AS monto_disponible
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito v
                WHERE NOT EXISTS (SELECT 1 FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t WHERE t.id_voucher = v.id_voucher)";
        $stmt = sqlsrv_query($this->db, $sql);
        $vouchers = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: ['sin_asignar' => 0, 'monto_disponible' => 0];

        // Mermas de hoy
        $sql = "SELECT ISNULL(SUM(k.cantidad), 0) AS cantidad_merma
                FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                WHERE k.tipo_movimiento = 'MERMA' AND CAST(k.fecha AS DATE) = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$hoy]);
        $mermas = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: ['cantidad_merma' => 0];

        // Valorizacion total del inventario
        $sql = "SELECT ISNULL(SUM(
                    CASE
                        WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                            THEN l.stock_actual * (uit_actual.valor * p.porcentaje_uit)
                        WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                            THEN l.stock_actual * hp_last.precio_oficial
                        ELSE 0
                    END), 0) AS valor_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)
                ) hp_last ON p.id_producto = hp_last.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE())
                WHERE l.stock_actual > 0 AND p.maneja_stock = 1";
        $stmt = sqlsrv_query($this->db, $sql);
        $valorizacion = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: ['valor_total' => 0];

        $resumen = [
            ['indicador' => 'Ventas hoy', 'valor' => 'S/ ' . number_format((float)$ventasHoy['monto_total'], 2), 'detalle' => $ventasHoy['cantidad'] . ' transacciones'],
            ['indicador' => 'Proformas pendientes', 'valor' => (int)$proformas['pendientes'], 'detalle' => 'Esperando procesamiento'],
            ['indicador' => 'Stock critico', 'valor' => (int)$criticos['productos_criticos'], 'detalle' => 'Productos con < 10 unidades'],
            ['indicador' => 'Vouchers sin asignar', 'valor' => (int)$vouchers['sin_asignar'], 'detalle' => 'Monto disponible: S/ ' . number_format((float)$vouchers['monto_disponible'], 2)],
            ['indicador' => 'Mermas hoy', 'valor' => (int)$mermas['cantidad_merma'], 'detalle' => 'Unidades perdidas'],
            ['indicador' => 'Valor inventario', 'valor' => 'S/ ' . number_format((float)$valorizacion['valor_total'], 2), 'detalle' => 'Valor total del stock actual'],
        ];

        return [
            'columns' => [
                ['key' => 'indicador', 'label' => 'Indicador'],
                ['key' => 'valor', 'label' => 'Valor'],
                ['key' => 'detalle', 'label' => 'Detalle']
            ],
            'rows' => $resumen
        ];
    }

    // ============================================================
    // TOOL 15: Comparativa entre periodos
    // ============================================================
    public function consultarComparativa($params = []) {
        $tipo = $params['tipo'] ?? 'ventas';
        $desde1 = $params['periodo1_desde'] ?? date('Y-m-01');
        $hasta1 = $params['periodo1_hasta'] ?? date('Y-m-d');
        $desde2 = $params['periodo2_desde'] ?? date('Y-m-d', strtotime('-1 month', strtotime($desde1)));
        $hasta2 = $params['periodo2_hasta'] ?? date('Y-m-d', strtotime('-1 month', strtotime($hasta1)));

        $fn = null;
        switch ($tipo) {
            case 'ventas':
                $fn = function($desde, $hasta) {
                    $sql = "SELECT ISNULL(SUM(total), 0) AS monto, COUNT(*) AS cantidad
                            FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion
                            WHERE estado = 'PROCESADO' AND CAST(fecha_creacion AS DATE) BETWEEN ? AND ?";
                    $stmt = sqlsrv_query($this->db, $sql, [$desde, $hasta]);
                    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                };
                break;
            case 'mermas':
                $fn = function($desde, $hasta) {
                    $sql = "SELECT ISNULL(SUM(k.cantidad), 0) AS cantidad, COUNT(*) AS movimientos
                            FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                            WHERE k.tipo_movimiento = 'MERMA' AND CAST(k.fecha AS DATE) BETWEEN ? AND ?";
                    $stmt = sqlsrv_query($this->db, $sql, [$desde, $hasta]);
                    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                };
                break;
            case 'ingresos':
                $fn = function($desde, $hasta) {
                    $sql = "SELECT ISNULL(SUM(k.cantidad), 0) AS cantidad, COUNT(*) AS movimientos
                            FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                            WHERE k.tipo_movimiento = 'INGRESO' AND CAST(k.fecha AS DATE) BETWEEN ? AND ?";
                    $stmt = sqlsrv_query($this->db, $sql, [$desde, $hasta]);
                    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                };
                break;
            default:
                return ['error' => 'Tipo no soportado: ' . $tipo . '. Usar: ventas, mermas, ingresos'];
        }

        $p1 = $fn($desde1, $hasta1);
        $p2 = $fn($desde2, $hasta2);

        $monto1 = (float)($p1['monto'] ?? $p1['cantidad']);
        $monto2 = (float)($p2['monto'] ?? $p2['cantidad']);
        $variacion = $monto2 > 0 ? round((($monto1 - $monto2) / $monto2) * 100, 1) : ($monto1 > 0 ? 100 : 0);

        $label1 = "Periodo 1: $desde1 a $hasta1";
        $label2 = "Periodo 2: $desde2 a $hasta2";

        $rows = [
            ['concepto' => $label1, 'valor' => $monto1, 'detalle' => ($p1['cantidad'] ?? $p1['movimientos'] ?? 0) . ' registros'],
            ['concepto' => $label2, 'valor' => $monto2, 'detalle' => ($p2['cantidad'] ?? $p2['movimientos'] ?? 0) . ' registros'],
            ['concepto' => 'Variacion', 'valor' => ($variacion >= 0 ? '+' : '') . $variacion . '%', 'detalle' => $variacion >= 0 ? 'Aumento' : 'Disminucion']
        ];

        return [
            'columns' => [
                ['key' => 'concepto', 'label' => 'Concepto'],
                ['key' => 'valor', 'label' => 'Valor'],
                ['key' => 'detalle', 'label' => 'Detalle']
            ],
            'rows' => $rows,
            'grafico' => [
                'tipo' => 'bar',
                'titulo' => 'Comparativa de ' . $tipo,
                'categorias' => ['Periodo 1', 'Periodo 2'],
                'series' => [['nombre' => ucfirst($tipo), 'datos' => [$monto1, $monto2]]],
                'formato' => ($tipo === 'ventas') ? 'moneda' : 'entero',
                'height' => 280
            ]
        ];
    }

    // ============================================================
    // TOOL 16: Busqueda global
    // ============================================================
    public function consultarBuscar($params = []) {
        $q = $params['q'] ?? '';
        if (empty($q)) {
            return ['error' => 'Debes proporcionar un termino de busqueda (q)'];
        }
        $like = '%' . $q . '%';
        $resultados = [];

        // Buscar productos
        $sql = "SELECT TOP 5 'Producto' AS tipo, p.nombre AS resultado, p.unidad_medida AS extra, c.nombre_clase AS contexto
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                WHERE (p.nombre LIKE ? OR p.nombre_cientifico LIKE ?) AND p.activo = 1
                ORDER BY p.nombre";
        $stmt = sqlsrv_query($this->db, $sql, [$like, $like]);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        // Buscar clientes
        $sql = "SELECT TOP 5 'Cliente' AS tipo, c.nombre_rs AS resultado, c.dni_ruc AS extra, CASE WHEN c.tipo_cliente = 1 THEN 'Planilla' ELSE 'Externo' END AS contexto
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente c
                WHERE (c.nombre_rs LIKE ? OR c.dni_ruc LIKE ?) AND c.activo = 1
                ORDER BY c.nombre_rs";
        $stmt = sqlsrv_query($this->db, $sql, [$like, $like]);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        // Buscar vouchers
        $sql = "SELECT TOP 5 'Voucher' AS tipo, v.num_operacion AS resultado, CAST(v.monto_total AS VARCHAR) AS extra, CONVERT(VARCHAR, v.fecha_deposito, 23) AS contexto
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito v
                WHERE v.num_operacion LIKE ?
                ORDER BY v.fecha_deposito DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$like]);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        // Buscar lotes
        $sql = "SELECT TOP 5 'Lote' AS tipo, l.codigo_lote AS resultado, CAST(l.stock_actual AS VARCHAR) + ' uds' AS extra, p.nombre AS contexto
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto
                WHERE l.codigo_lote LIKE ?
                ORDER BY l.fecha_creacion DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$like]);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        if (empty($resultados)) {
            return ['columns' => [['key' => 'mensaje', 'label' => 'Mensaje']], 'rows' => [['mensaje' => 'No se encontraron resultados para "' . $q . '"']]];
        }

        return [
            'columns' => [
                ['key' => 'tipo', 'label' => 'Tipo'],
                ['key' => 'resultado', 'label' => 'Resultado'],
                ['key' => 'extra', 'label' => 'Detalle'],
                ['key' => 'contexto', 'label' => 'Contexto']
            ],
            'rows' => $resultados
        ];
    }

    // ============================================================
    // TOOL 17: Recomendaciones inteligentes
    // ============================================================
    public function consultarRecomendaciones($params = []) {
        $recomendaciones = [];

        // Stock critico
        $sql = "SELECT TOP 5 p.nombre AS producto, SUM(l.stock_actual) AS stock, cp.nombre_centro AS centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                WHERE l.stock_actual > 0 AND l.stock_actual < 10 AND p.maneja_stock = 1
                GROUP BY p.nombre, cp.nombre_centro
                ORDER BY stock ASC";
        $stmt = sqlsrv_query($this->db, $sql);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $recomendaciones[] = ['tipo' => 'Reponer stock', 'recomendacion' => $row['producto'] . ' (' . $row['centro'] . ')', 'motivo' => 'Solo quedan ' . $row['stock'] . ' unidades'];
        }

        // Clientes inactivos (sin transaccion en 30 dias)
        $sql = "SELECT TOP 5 c.nombre_rs AS cliente, MAX(t.fecha_creacion) AS ultima_compra
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente c
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON c.id_cliente = t.id_cliente
                WHERE c.tipo_cliente = 0
                GROUP BY c.id_cliente, c.nombre_rs
                HAVING MAX(t.fecha_creacion) IS NULL OR MAX(t.fecha_creacion) < DATEADD(day, -30, GETDATE())
                ORDER BY ultima_compra ASC";
        $stmt = sqlsrv_query($this->db, $sql);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rec = $row['ultima_compra'] ? 'Ultima compra: ' . (new DateTime($row['ultima_compra']))->format('Y-m-d') : 'Nunca ha comprado';
            $recomendaciones[] = ['tipo' => 'Cliente inactivo', 'recomendacion' => $row['cliente'], 'motivo' => $rec];
        }

        // Top mermas por producto
        $sql = "SELECT TOP 5 p.nombre AS producto, SUM(k.cantidad) AS total_merma
                FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l ON k.id_lote = l.id_lote
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto
                WHERE k.tipo_movimiento = 'MERMA' AND k.fecha >= DATEADD(day, -60, GETDATE())
                GROUP BY p.nombre
                HAVING SUM(k.cantidad) > 0
                ORDER BY total_merma DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $recomendaciones[] = ['tipo' => 'Alta merma', 'recomendacion' => $row['producto'], 'motivo' => $row['total_merma'] . ' unidades perdidas (60 dias)'];
        }

        // Proformas pendientes antiguas (> 7 dias)
        $sql = "SELECT TOP 5 t.id_transaccion, c.nombre_rs AS cliente, t.total, CAST(t.fecha_creacion AS DATE) AS fecha
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                WHERE t.estado = 'PENDIENTE' AND t.fecha_creacion < DATEADD(day, -7, GETDATE())
                ORDER BY t.fecha_creacion ASC";
        $stmt = sqlsrv_query($this->db, $sql);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $recomendaciones[] = ['tipo' => 'Proforma antigua', 'recomendacion' => '#' . $row['id_transaccion'] . ' - ' . $row['cliente'], 'motivo' => 'Pendiente desde ' . ($row['fecha'] instanceof DateTime ? $row['fecha']->format('Y-m-d') : $row['fecha']) . ' (S/ ' . number_format((float)$row['total'], 2) . ')'];
        }

        if (empty($recomendaciones)) {
            return ['columns' => [['key' => 'mensaje', 'label' => 'Mensaje']], 'rows' => [['mensaje' => 'Todo en orden. No se encontraron situaciones que requieran atencion inmediata.']]];
        }

        return [
            'columns' => [
                ['key' => 'tipo', 'label' => 'Alerta'],
                ['key' => 'recomendacion', 'label' => 'Elemento'],
                ['key' => 'motivo', 'label' => 'Motivo']
            ],
            'rows' => $recomendaciones
        ];
    }

    // ============================================================
    // TOOL 18: Metricas consolidadas (KPIs rapidos)
    // ============================================================
    public function consultarMetricas($params = []) {
        $resultado = [];
        
        // Total productos
        $sql = "SELECT COUNT(*) as total FROM BD_PRODUCCIONDESARROLLO.dbo.producto WHERE maneja_stock = 1";
        $stmt = sqlsrv_query($this->db, $sql);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado['total_productos'] = (int)($r['total'] ?? 0);
        
        // Stock total
        $sql = "SELECT ISNULL(SUM(stock_actual), 0) as total FROM BD_PRODUCCIONDESARROLLO.dbo.lote WHERE stock_actual > 0";
        $stmt = sqlsrv_query($this->db, $sql);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado['stock_total'] = (int)($r['total'] ?? 0);
        
        // Ventas del mes
        $mesInicio = date('Y-m-01');
        $sql = "SELECT ISNULL(SUM(total), 0) as total, COUNT(*) as cant FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion WHERE metodo_pago = 'VENTA' AND fecha_creacion >= ? AND fecha_creacion < DATEADD(month, 1, ?)";
        $stmt = sqlsrv_query($this->db, $sql, [$mesInicio, $mesInicio]);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado['ventas_mes'] = (float)($r['total'] ?? 0);
        $resultado['ventas_mes_cantidad'] = (int)($r['cant'] ?? 0);
        
        // Donaciones del mes  
        $sql = "SELECT ISNULL(SUM(total), 0) as total, COUNT(*) as cant FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion WHERE metodo_pago = 'DONACION' AND fecha_creacion >= ? AND fecha_creacion < DATEADD(month, 1, ?)";
        $stmt = sqlsrv_query($this->db, $sql, [$mesInicio, $mesInicio]);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado['donaciones_mes'] = (float)($r['total'] ?? 0);
        $resultado['donaciones_mes_cantidad'] = (int)($r['cant'] ?? 0);
        
        // Proformas pendientes
        $sql = "SELECT COUNT(*) as cant FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion WHERE estado = 'PENDIENTE'";
        $stmt = sqlsrv_query($this->db, $sql);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado['proformas_pendientes'] = (int)($r['cant'] ?? 0);
        
        // Mermas del mes
        $sql = "SELECT ISNULL(SUM(k.cantidad), 0) as total FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k WHERE k.tipo_movimiento = 'MERMA' AND k.fecha >= ?";
        $stmt = sqlsrv_query($this->db, $sql, [$mesInicio]);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado['mermas_mes'] = (int)($r['total'] ?? 0);
        
        // Valor inventario
        $sql = "SELECT ISNULL(SUM(
                    CASE 
                        WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND u.valor IS NOT NULL THEN p.porcentaje_uit * u.valor * l.stock_actual
                        WHEN p.tipo_precio = 'Variable' THEN ISNULL(hp.precio_oficial, 0) * l.stock_actual
                        ELSE 0
                    END
                ), 0) as total
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit u ON u.anio = YEAR(GETDATE())
                LEFT JOIN (SELECT hp1.id_producto, hp1.precio_oficial FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1 WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)) hp ON p.id_producto = hp.id_producto
                WHERE l.stock_actual > 0 AND p.maneja_stock = 1";
        $stmt = sqlsrv_query($this->db, $sql);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado['valor_inventario'] = round((float)($r['total'] ?? 0), 2);
        
        return [
            'columns' => [
                ['key' => 'metrica', 'label' => 'Indicador'],
                ['key' => 'valor', 'label' => 'Valor'],
            ],
            'rows' => [
                ['metrica' => 'Productos activos', 'valor' => $resultado['total_productos']],
                ['metrica' => 'Stock total (unidades)', 'valor' => number_format($resultado['stock_total'], 0)],
                ['metrica' => 'Valor inventario', 'valor' => 'S/ ' . number_format($resultado['valor_inventario'], 2)],
                ['metrica' => 'Ventas del mes', 'valor' => 'S/ ' . number_format($resultado['ventas_mes'], 2) . ' (' . $resultado['ventas_mes_cantidad'] . ' transacc.)'],
                ['metrica' => 'Donaciones del mes', 'valor' => 'S/ ' . number_format($resultado['donaciones_mes'], 2) . ' (' . $resultado['donaciones_mes_cantidad'] . ' transacc.)'],
                ['metrica' => 'Proformas pendientes', 'valor' => $resultado['proformas_pendientes']],
                ['metrica' => 'Mermas del mes (unid.)', 'valor' => $resultado['mermas_mes']],
            ]
        ];
    }

    // ============================================================
    // TOOL 19: Detalle completo de un producto
    // ============================================================
    public function consultarDetalleProducto($params = []) {
        $nombre = $params['producto'] ?? '';
        if (empty($nombre)) {
            return ['error' => 'Debe especificar el nombre o ID del producto'];
        }
        
        $idProducto = is_numeric($nombre) ? intval($nombre) : null;
        
        // Buscar el producto
        if ($idProducto) {
            $sql = "SELECT TOP 1 p.id_producto, p.nombre, p.nombre_cientifico, p.unidad_medida, p.tipo_precio, p.porcentaje_uit, p.maneja_stock, c.nombre_clase
                    FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                    LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                    WHERE p.id_producto = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        } else {
            $sql = "SELECT TOP 1 p.id_producto, p.nombre, p.nombre_cientifico, p.unidad_medida, p.tipo_precio, p.porcentaje_uit, p.maneja_stock, c.nombre_clase
                    FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                    LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                    WHERE p.nombre LIKE ?";
            $stmt = sqlsrv_query($this->db, $sql, ['%' . $nombre . '%']);
        }
        
        $prod = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$prod) return ['error' => 'Producto no encontrado'];
        
        $idProd = $prod['id_producto'];
        
        // Centros vinculados
        $sqlCentros = "SELECT cp.nombre_centro FROM BD_PRODUCCIONDESARROLLO.dbo.producto_centro pc JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON pc.id_centro = cp.id_centro WHERE pc.id_producto = ?";
        $stmtCentros = sqlsrv_query($this->db, $sqlCentros, [$idProd]);
        $centros = [];
        while ($c = sqlsrv_fetch_array($stmtCentros, SQLSRV_FETCH_ASSOC)) { $centros[] = $c['nombre_centro']; }
        
        // Stock por centro
        $sqlStock = "SELECT cp.nombre_centro, SUM(l.stock_actual) as stock FROM BD_PRODUCCIONDESARROLLO.dbo.lote l LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro WHERE l.id_producto = ? AND l.stock_actual > 0 GROUP BY cp.nombre_centro";
        $stmtStock = sqlsrv_query($this->db, $sqlStock, [$idProd]);
        $stockPorCentro = [];
        while ($s = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC)) { $stockPorCentro[] = $s; }
        
        // Precio vigente
        $sqlPrecio = "SELECT hp.precio_oficial FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp WHERE hp.id_producto = ? ORDER BY hp.fecha_registro DESC";
        $stmtPrecio = sqlsrv_query($this->db, $sqlPrecio, [$idProd]);
        $ultimoPrecio = sqlsrv_fetch_array($stmtPrecio, SQLSRV_FETCH_ASSOC);
        
        // Lotes activos
        $sqlLotes = "SELECT codigo_lote, stock_actual, DATEDIFF(day, fecha_creacion, GETDATE()) as dias FROM BD_PRODUCCIONDESARROLLO.dbo.lote WHERE id_producto = ? AND stock_actual > 0 ORDER BY fecha_creacion";
        $stmtLotes = sqlsrv_query($this->db, $sqlLotes, [$idProd]);
        $lotes = [];
        while ($l = sqlsrv_fetch_array($stmtLotes, SQLSRV_FETCH_ASSOC)) { $lotes[] = $l; }
        
        // Ultimos movimientos
        $sqlKdx = "SELECT TOP 5 k.tipo_movimiento, k.cantidad, k.fecha, k.observacion FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l ON k.id_lote = l.id_lote WHERE l.id_producto = ? ORDER BY k.fecha DESC, k.id_kardex DESC";
        $stmtKdx = sqlsrv_query($this->db, $sqlKdx, [$idProd]);
        $movimientos = [];
        while ($m = sqlsrv_fetch_array($stmtKdx, SQLSRV_FETCH_ASSOC)) { $movimientos[] = $m; }
        
        return [
            'columns' => [['key' => 'campo', 'label' => 'Campo'], ['key' => 'valor', 'label' => 'Valor']],
            'rows' => [
                ['campo' => 'Producto', 'valor' => $prod['nombre']],
                ['campo' => 'Nombre Cientifico', 'valor' => $prod['nombre_cientifico'] ?: '-'],
                ['campo' => 'Clase', 'valor' => $prod['nombre_clase'] ?: '-'],
                ['campo' => 'Unidad', 'valor' => $prod['unidad_medida']],
                ['campo' => 'Centros', 'valor' => implode(', ', $centros) ?: '-'],
                ['campo' => 'Tipo Precio', 'valor' => $prod['tipo_precio'] ?: 'Fijo'],
                ['campo' => 'Maneja Stock', 'valor' => $prod['maneja_stock'] ? 'Si' : 'No'],
                ['campo' => 'Precio Vigente', 'valor' => $ultimoPrecio ? 'S/ ' . number_format($ultimoPrecio['precio_oficial'], 4) : 'No registrado'],
                ['campo' => 'Stock Total', 'valor' => array_sum(array_column($stockPorCentro, 'stock')) . ' ' . $prod['unidad_medida']],
                ['campo' => 'Lotes Activos', 'valor' => count($lotes)],
                ['campo' => 'Stock por Centro', 'valor' => implode(' | ', array_map(function($s) { return $s['nombre_centro'] . ': ' . $s['stock']; }, $stockPorCentro)) ?: '-'],
            ]
        ];
    }

}
