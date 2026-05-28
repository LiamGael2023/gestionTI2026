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
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
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
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
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
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON t.id_centro = cp.id_centro
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
        $where = ["1=1"];

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
        $where = ["1=1"];

        if (!empty($params['clase'])) {
            $where[] = "c.nombre_clase LIKE ?";
            $sqlParams[] = '%' . $params['clase'] . '%';
        }
        if (!empty($params['centro'])) {
            $where[] = "cp.nombre_centro LIKE ?";
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
                    cp.nombre_centro AS centro,
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
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (SELECT MAX(hp2.fecha_registro) FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2 WHERE hp2.id_producto = hp1.id_producto)
                ) hp_last ON p.id_producto = hp_last.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE())
                WHERE " . implode(" AND ", $where) . "
                ORDER BY cp.nombre_centro, c.nombre_clase, p.nombre";

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
        $where = ["1=1"];

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
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
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

        $lineas = [];
        $lineas[] = "Resultados de la consulta '" . $nombreTool . "' (" . count($data) . " registros):";
        $lineas[] = "";

        foreach ($data as $i => $row) {
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
    // Devuelve: ['columns' => [...], 'rows' => [...]]
    // ============================================================
    public function formatearResultadoRaw($data) {
        if (isset($data['error']) || empty($data) || !is_array($data)) {
            return null;
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
}
