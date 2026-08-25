<?php
class ReportesModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ============================================================
    // AUXILIARES: Catálogos para filtros
    // ============================================================

    public function getCentros() {
        $sql = "SELECT id_centro, nombre_centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion WHERE activo = 1
                ORDER BY nombre_centro";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function getClases() {
        $sql = "SELECT id_clase, nombre_clase
                FROM BD_PRODUCCIONDESARROLLO.dbo.clase WHERE activo = 1
                ORDER BY nombre_clase";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function getClientes() {
        $sql = "SELECT id_cliente, nombre_rs, dni_ruc
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente WHERE activo = 1
                ORDER BY nombre_rs";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // REPORTE 1: VENTAS Y FACTURACIÓN
    // ============================================================

    /**
     * Lista transacciones con filtros combinados
     */
    public function getVentas($filtros = []) {
        $params = [];

        $sql = "SELECT
                    t.id_transaccion,
                    t.fecha_creacion,
                    t.estado,
                    t.metodo_pago,
                    t.total,
                    t.serie_comprobante,
                    t.correlativo_comprobante,
                    t.responsable_venta,
                    c.nombre_rs   AS nombre_cliente,
                    c.dni_ruc     AS documento_cliente,
                    CASE WHEN LEN(c.dni_ruc) = 8 THEN 'DNI' ELSE 'RUC' END AS tipo_doc,
                    cp.nombre_centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c
                       ON t.id_cliente = c.id_cliente AND c.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp
                       ON t.id_centro = cp.id_centro AND cp.activo = 1
                WHERE t.tipo_op = 'VENTA'";

        // Filtros dinámicos
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['id_centro'])) {
            $sql .= " AND t.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }
        if (!empty($filtros['id_cliente'])) {
            $sql .= " AND t.id_cliente = ?";
            $params[] = intval($filtros['id_cliente']);
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['metodo_pago'])) {
            $sql .= " AND t.metodo_pago = ?";
            $params[] = $filtros['metodo_pago'];
        }

        $sql .= " ORDER BY t.fecha_creacion DESC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getVentas] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_creacion']) && $row['fecha_creacion'] instanceof DateTime) {
                $row['fecha_creacion'] = $row['fecha_creacion']->format('Y-m-d H:i:s');
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * KPIs de ventas: total facturado, n° transacciones, ticket promedio
     */
    public function getKpisVentas($filtros = []) {
        $params = [];

        $sql = "SELECT
                    COUNT(*) AS total_transacciones,
                    ISNULL(SUM(t.total), 0) AS monto_total,
                    ISNULL(AVG(t.total), 0) AS ticket_promedio
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                WHERE t.tipo_op = 'VENTA'";

        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        } else {
            $sql .= " AND t.estado = 'PROCESADO'";
        }

        if (!empty($filtros['metodo_pago'])) {
            $sql .= " AND t.metodo_pago = ?";
            $params[] = $filtros['metodo_pago'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['id_centro'])) {
            $sql .= " AND t.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }
        if (!empty($filtros['id_cliente'])) {
            $sql .= " AND t.id_cliente = ?";
            $params[] = intval($filtros['id_cliente']);
        }

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) return ['total_transacciones' => 0, 'monto_total' => 0, 'ticket_promedio' => 0];
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: ['total_transacciones' => 0, 'monto_total' => 0, 'ticket_promedio' => 0];
    }

    // ============================================================
    // REPORTE 2: VALORIZACIÓN DE INVENTARIO
    // ============================================================

    /**
     * Calcula el valor monetario actual del inventario por lote,
     * cruzando stock con precio vigente (variable o UIT)
     */
    public function getValorizacionInventario($filtros = []) {
        $params = [];

        $sql = "SELECT
                    p.id_producto,
                    p.nombre            AS nombre_producto,
                    p.nombre_cientifico,
                    p.unidad_medida,
                    p.tipo_precio,
                    p.porcentaje_uit,
                    c.nombre_clase,
                    cp.nombre_centro,
                    l.id_lote,
                    l.codigo_lote,
                    l.fecha_creacion    AS fecha_lote,
                    l.stock_actual,
                    DATEDIFF(day, l.fecha_creacion, GETDATE()) AS antiguedad_dias,
                    -- Precio variable: último registrado
                    hp_last.precio_oficial AS precio_variable,
                    -- Valor UIT del año actual
                    uit_actual.valor       AS valor_uit,
                    -- Precio calculado según tipo
                    CASE
                        WHEN p.tipo_precio = 'UIT'
                             AND p.porcentaje_uit IS NOT NULL
                             AND uit_actual.valor IS NOT NULL
                            THEN uit_actual.valor * p.porcentaje_uit
                        WHEN p.tipo_precio = 'Variable'
                             AND hp_last.precio_oficial IS NOT NULL
                            THEN hp_last.precio_oficial
                        ELSE 0
                    END AS precio_unitario,
                    -- Valor total de este lote
                    CASE
                        WHEN p.tipo_precio = 'UIT'
                             AND p.porcentaje_uit IS NOT NULL
                             AND uit_actual.valor IS NOT NULL
                            THEN l.stock_actual * (uit_actual.valor * p.porcentaje_uit)
                        WHEN p.tipo_precio = 'Variable'
                             AND hp_last.precio_oficial IS NOT NULL
                            THEN l.stock_actual * hp_last.precio_oficial
                        ELSE 0
                    END AS valor_total_lote
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p
                        ON l.id_producto = p.id_producto AND p.activo = 1
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c
               ON p.id_clase = c.id_clase AND c.activo = 1
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp
               ON p.id_centro = cp.id_centro AND cp.activo = 1
        -- Último precio registrado (subconsulta correlacionada)
        LEFT JOIN (
            SELECT hp1.id_producto, hp1.precio_oficial
            FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
            WHERE hp1.fecha_registro = (
                SELECT MAX(hp2.fecha_registro)
                FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                WHERE hp2.id_producto = hp1.id_producto
            )
        ) hp_last ON p.id_producto = hp_last.id_producto
        -- UIT del año actual
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual
               ON uit_actual.anio = YEAR(GETDATE()) AND uit_actual.activo = 1
        WHERE l.stock_actual > 0
          AND p.maneja_stock = 1";

        if (!empty($filtros['id_centro'])) {
            $sql .= " AND p.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }
        if (!empty($filtros['id_clase'])) {
            $sql .= " AND p.id_clase = ?";
            $params[] = intval($filtros['id_clase']);
        }

        $sql .= " ORDER BY cp.nombre_centro, p.nombre, l.fecha_creacion ASC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getValorizacionInventario] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
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
    // REPORTE 3: MERMAS Y PÉRDIDAS
    // ============================================================

    /**
     * Extrae movimientos de kardex tipo MERMA con valor estimado de pérdida
     */
    public function getMermas($filtros = []) {
        $params = [];

        $sql = "SELECT
                    k.id_kardex,
                    k.fecha,
                    k.cantidad          AS cantidad_merma,
                    k.saldo_final,
                    k.tipo_movimiento,
                    l.codigo_lote,
                    l.id_producto,
                    p.nombre            AS nombre_producto,
                    p.tipo_precio,
                    p.porcentaje_uit,
                    c.nombre_clase,
                    cp.nombre_centro,
                    hp_last.precio_oficial AS precio_variable,
                    uit_actual.valor       AS valor_uit,
                    -- Precio unitario al momento del movimiento
                    CASE
                        WHEN p.tipo_precio = 'UIT'
                             AND p.porcentaje_uit IS NOT NULL
                             AND uit_actual.valor IS NOT NULL
                            THEN uit_actual.valor * p.porcentaje_uit
                        WHEN p.tipo_precio = 'Variable'
                             AND hp_last.precio_oficial IS NOT NULL
                            THEN hp_last.precio_oficial
                        ELSE 0
                    END AS precio_unitario,
                    -- Valor estimado de la pérdida
                    CASE
                        WHEN p.tipo_precio = 'UIT'
                             AND p.porcentaje_uit IS NOT NULL
                             AND uit_actual.valor IS NOT NULL
                            THEN k.cantidad * (uit_actual.valor * p.porcentaje_uit)
                        WHEN p.tipo_precio = 'Variable'
                             AND hp_last.precio_oficial IS NOT NULL
                            THEN k.cantidad * hp_last.precio_oficial
                        ELSE 0
                    END AS valor_perdida
                FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l
                        ON k.id_lote = l.id_lote
        INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p
                ON l.id_producto = p.id_producto AND p.activo = 1
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c
               ON p.id_clase = c.id_clase AND c.activo = 1
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp
               ON p.id_centro = cp.id_centro AND cp.activo = 1
        LEFT JOIN (
            SELECT hp1.id_producto, hp1.precio_oficial
            FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
            WHERE hp1.fecha_registro = (
                SELECT MAX(hp2.fecha_registro)
                FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                WHERE hp2.id_producto = hp1.id_producto
            )
        ) hp_last ON p.id_producto = hp_last.id_producto
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual
               ON uit_actual.anio = YEAR(GETDATE()) AND uit_actual.activo = 1
        WHERE k.tipo_movimiento = 'MERMA'";

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND CAST(k.fecha AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND CAST(k.fecha AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['id_centro'])) {
            $sql .= " AND p.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }
        if (!empty($filtros['id_clase'])) {
            $sql .= " AND p.id_clase = ?";
            $params[] = intval($filtros['id_clase']);
        }

        $sql .= " ORDER BY k.fecha DESC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getMermas] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha']) && $row['fecha'] instanceof DateTime) {
                $row['fecha'] = $row['fecha']->format('Y-m-d H:i:s');
            }
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // DATOS PARA DASHBOARD (Gráficos)
    // ============================================================

    /**
     * Ventas agrupadas por mes (últimos 6 meses) para gráfico de barras
     */
    public function getVentasPorMes($filtros = []) {
        $params = [];

        $sql = "SELECT
                    FORMAT(t.fecha_creacion, 'yyyy-MM') AS mes,
                    FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE') AS mes_label,
                    COUNT(*) AS total_transacciones,
                    ISNULL(SUM(t.total), 0) AS monto_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                WHERE t.tipo_op = 'VENTA'
                  AND t.estado = 'PROCESADO'
                  AND t.fecha_creacion >= DATEADD(month, -5, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1))";

        if (!empty($filtros['id_centro'])) {
            $sql .= " AND t.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }

        $sql .= " GROUP BY FORMAT(t.fecha_creacion, 'yyyy-MM'), FORMAT(t.fecha_creacion, 'MMM yyyy', 'es-PE')
                  ORDER BY FORMAT(t.fecha_creacion, 'yyyy-MM') ASC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getVentasPorMes] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Distribución por método de pago para gráfico de dona
     */
    public function getVentasPorMetodoPago($filtros = []) {
        $params = [];

        $sql = "SELECT
                    ISNULL(t.metodo_pago, 'Sin especificar') AS metodo_pago,
                    COUNT(*) AS cantidad,
                    ISNULL(SUM(t.total), 0) AS monto_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                WHERE t.tipo_op = 'VENTA'
                  AND t.estado = 'PROCESADO'";

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['id_centro'])) {
            $sql .= " AND t.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }

        $sql .= " GROUP BY t.metodo_pago ORDER BY monto_total DESC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getVentasPorMetodoPago] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Valor de inventario agrupado por clase de producto para gráfico de barras
     */
    public function getInventarioPorClase($filtros = []) {
        $params = [];

        $sql = "SELECT
                    c.nombre_clase,
                    COUNT(DISTINCT p.id_producto) AS total_productos,
                    SUM(l.stock_actual) AS stock_total,
                    SUM(
                        CASE
                            WHEN p.tipo_precio = 'UIT'
                                 AND p.porcentaje_uit IS NOT NULL
                                 AND uit_actual.valor IS NOT NULL
                                THEN l.stock_actual * (uit_actual.valor * p.porcentaje_uit)
                            WHEN p.tipo_precio = 'Variable'
                                 AND hp_last.precio_oficial IS NOT NULL
                                THEN l.stock_actual * hp_last.precio_oficial
                            ELSE 0
                        END
                    ) AS valor_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
        INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p
                ON l.id_producto = p.id_producto AND p.activo = 1
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c
               ON p.id_clase = c.id_clase AND c.activo = 1
        LEFT JOIN (
            SELECT hp1.id_producto, hp1.precio_oficial
            FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
            WHERE hp1.fecha_registro = (
                SELECT MAX(hp2.fecha_registro)
                FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                WHERE hp2.id_producto = hp1.id_producto
            )
        ) hp_last ON p.id_producto = hp_last.id_producto
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual
               ON uit_actual.anio = YEAR(GETDATE()) AND uit_actual.activo = 1
        WHERE l.stock_actual > 0
          AND p.maneja_stock = 1";

        if (!empty($filtros['id_centro'])) {
            $sql .= " AND p.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }

        $sql .= " GROUP BY c.nombre_clase ORDER BY valor_total DESC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getInventarioPorClase] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Reporte: Directorio de Clientes y Recaudación Acumulada
     */
    public function getClientesReport($filtros = []) {
        $params = [];
        $sql = "SELECT c.id_cliente, c.dni_ruc, c.nombre_rs,
                       CASE WHEN c.tipo_cliente = 0 THEN 'Planilla' ELSE 'Externo' END as tipo_cliente,
                       COUNT(t.id_transaccion) as total_transacciones,
                       ISNULL(SUM(CASE WHEN t.metodo_pago = 'VENTA' THEN t.total ELSE 0 END), 0) as total_ventas,
                       ISNULL(SUM(CASE WHEN t.metodo_pago = 'DONACION' THEN t.total ELSE 0 END), 0) as total_donaciones,
                       ISNULL(SUM(t.total), 0) as total_acumulado
                 FROM BD_PRODUCCIONDESARROLLO.dbo.cliente c
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON c.id_cliente = t.id_cliente AND t.estado = 'PROCESADO'
                WHERE c.activo = 1";

        if (!empty($filtros['cliente'])) {
            // Acepta un ID numérico (desde el select de la vista) o texto de búsqueda
            if (ctype_digit($filtros['cliente'])) {
                $sql .= " AND c.id_cliente = ?";
                $params[] = intval($filtros['cliente']);
            } else {
                $sql .= " AND (c.nombre_rs LIKE ? OR c.dni_ruc LIKE ?)";
                $params[] = '%' . $filtros['cliente'] . '%';
                $params[] = '%' . $filtros['cliente'] . '%';
            }
        }

        $sql .= " GROUP BY c.id_cliente, c.dni_ruc, c.nombre_rs, c.tipo_cliente
                  ORDER BY total_acumulado DESC, c.nombre_rs ASC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getClientesReport] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Reporte: Consolidado por Centro de Producción
     */
    public function getConsolidadoReport($filtros = []) {
        $params = [];
        $whereMain = ["cp.activo = 1"];
        $whereVentas = ["t.estado = 'PROCESADO'"];
        $whereMermas = ["k.tipo_movimiento = 'MERMA'"];

        if (!empty($filtros['id_centro'])) {
            $whereMain[] = "cp.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }
        // IMPORTANTE: el orden de $params debe coincidir con el orden de los
        // placeholders en el SQL final: principal → subquery ventas → subquery mermas
        if (!empty($filtros['fecha_desde'])) {
            $whereVentas[] = "CAST(t.fecha_creacion AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereVentas[] = "CAST(t.fecha_creacion AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $whereMermas[] = "CAST(k.fecha AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereMermas[] = "CAST(k.fecha AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql = "SELECT cp.id_centro, cp.nombre_centro, cp.encargado,
                       ISNULL(v.total_ventas, 0) as total_ventas,
                       ISNULL(v.total_donaciones, 0) as total_donaciones,
                       ISNULL(i.valor_inventario, 0) as valor_inventario,
                       ISNULL(m.valor_mermas, 0) as valor_mermas
                FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp
                WHERE " . implode(" AND ", $whereMain) . "
                LEFT JOIN (
                    SELECT id_centro,
                           SUM(CASE WHEN metodo_pago = 'VENTA' THEN total ELSE 0 END) as total_ventas,
                           SUM(CASE WHEN metodo_pago = 'DONACION' THEN total ELSE 0 END) as total_donaciones
                    FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                    WHERE " . implode(" AND ", $whereVentas) . "
                    GROUP BY id_centro
                ) v ON cp.id_centro = v.id_centro
                LEFT JOIN (
                    SELECT p.id_centro,
                           SUM(
                               CASE
                                   WHEN p.tipo_precio = 'UIT' AND p.porcentaje_uit IS NOT NULL AND uit_actual.valor IS NOT NULL
                                       THEN l.stock_actual * (uit_actual.valor * p.porcentaje_uit)
                                   WHEN p.tipo_precio = 'Variable' AND hp_last.precio_oficial IS NOT NULL
                                       THEN l.stock_actual * hp_last.precio_oficial
                                   ELSE 0
                               END
                           ) AS valor_inventario
                    FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                    INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p ON l.id_producto = p.id_producto AND p.activo = 1
                    LEFT JOIN (
                        SELECT hp1.id_producto, hp1.precio_oficial
                        FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                        WHERE hp1.fecha_registro = (
                            SELECT MAX(hp2.fecha_registro)
                            FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                            WHERE hp2.id_producto = hp1.id_producto
                        )
                    ) hp_last ON p.id_producto = hp_last.id_producto
                    LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual ON uit_actual.anio = YEAR(GETDATE()) AND uit_actual.activo = 1
                    WHERE l.stock_actual > 0 AND p.maneja_stock = 1
                    GROUP BY p.id_centro
                ) i ON cp.id_centro = i.id_centro
                LEFT JOIN (
                    SELECT p.id_centro,
                           SUM(k.cantidad *
                               CASE
                                   WHEN p2.tipo_precio = 'UIT' AND p2.porcentaje_uit IS NOT NULL AND uit2.valor IS NOT NULL
                                       THEN uit2.valor * p2.porcentaje_uit
                                   WHEN p2.tipo_precio = 'Variable' AND hp2.precio_oficial IS NOT NULL
                                       THEN hp2.precio_oficial
                                   ELSE 0
                               END
                           ) as valor_mermas
                    FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                    INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l2 ON k.id_lote = l2.id_lote
                    INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p2 ON l2.id_producto = p2.id_producto AND p2.activo = 1
                    LEFT JOIN (
                        SELECT hp3.id_producto, hp3.precio_oficial
                        FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp3
                        WHERE hp3.fecha_registro = (
                            SELECT MAX(hp4.fecha_registro)
                            FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp4
                            WHERE hp4.id_producto = hp3.id_producto
                        )
                    ) hp2 ON p2.id_producto = hp2.id_producto
                    LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit2 ON uit2.anio = YEAR(GETDATE()) AND uit2.activo = 1
                    WHERE " . implode(" AND ", $whereMermas) . "
                    GROUP BY p2.id_centro
                ) m ON cp.id_centro = m.id_centro
                ORDER BY cp.nombre_centro ASC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getConsolidadoReport] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Lista precios vigentes de productos con su tipo de precio y clase
     */
    public function getPreciosReport($filtros = []) {
        $params = [];

        $sql = "SELECT
                    p.id_producto,
                    p.nombre            AS nombre_producto,
                    p.nombre_cientifico,
                    p.unidad_medida,
                    p.tipo_precio,
                    p.porcentaje_uit,
                    c.nombre_clase,
                    cp.nombre_centro,
                    hp_last.precio_oficial AS precio_variable,
                    hp_last.fecha_registro AS fecha_cambio_precio,
                    uit_actual.valor       AS valor_uit,
                    CASE
                        WHEN p.tipo_precio = 'UIT'
                             AND p.porcentaje_uit IS NOT NULL
                             AND uit_actual.valor IS NOT NULL
                            THEN uit_actual.valor * p.porcentaje_uit
                        WHEN p.tipo_precio = 'Variable'
                             AND hp_last.precio_oficial IS NOT NULL
                            THEN hp_last.precio_oficial
                        ELSE 0
                    END AS precio_unitario
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c
               ON p.id_clase = c.id_clase AND c.activo = 1
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp
               ON p.id_centro = cp.id_centro AND cp.activo = 1
        LEFT JOIN (
            SELECT hp1.id_producto, hp1.precio_oficial, hp1.fecha_registro
            FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
            WHERE hp1.fecha_registro = (
                SELECT MAX(hp2.fecha_registro)
                FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                WHERE hp2.id_producto = hp1.id_producto
            )
        ) hp_last ON p.id_producto = hp_last.id_producto
        LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit uit_actual
               ON uit_actual.anio = YEAR(GETDATE()) AND uit_actual.activo = 1
                WHERE 1=1 AND p.activo = 1";

        if (!empty($filtros['id_centro'])) {
            $sql .= " AND p.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }
        if (!empty($filtros['id_clase'])) {
            $sql .= " AND p.id_clase = ?";
            $params[] = intval($filtros['id_clase']);
        }
        if (!empty($filtros['tipo_precio'])) {
            $sql .= " AND p.tipo_precio = ?";
            $params[] = $filtros['tipo_precio'];
        }

        $sql .= " ORDER BY cp.nombre_centro, c.nombre_clase, p.nombre ASC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getPreciosReport] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ============================================================
    // REPORTE 8: RELACIÓN DEL PERSONAL DE PLANILLA
    // ============================================================
    public function getReportePlanilla($filtros = []) {
        $params = [];

        $sql = "SELECT
                    t.id_transaccion,
                    t.fecha_creacion,
                    c.id_cliente,
                    c.dni_ruc,
                    c.nombre_rs AS nombre_cliente,
                    cp.id_centro,
                    cp.nombre_centro,
                    p.id_producto,
                    p.nombre AS nombre_producto,
                    p.unidad_medida,
                    td.cantidad,
                    td.precio_unitario,
                    td.subtotal
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c
                       ON t.id_cliente = c.id_cliente AND c.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp
                       ON t.id_centro = cp.id_centro AND cp.activo = 1
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle td
                       ON t.id_transaccion = td.id_transaccion
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.producto p
                       ON td.id_producto = p.id_producto AND p.activo = 1
                WHERE t.metodo_pago = 'PLANILLA'
                  AND t.descuento_planilla = 1
                  AND t.tipo_op = 'VENTA'";

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND CAST(t.fecha_creacion AS DATE) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['id_centro'])) {
            $sql .= " AND t.id_centro = ?";
            $params[] = intval($filtros['id_centro']);
        }

        $sql .= " ORDER BY c.nombre_rs, t.fecha_creacion, p.nombre";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('[ReportesModel::getReportePlanilla] Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_creacion']) && $row['fecha_creacion'] instanceof DateTime) {
                $row['fecha_creacion'] = $row['fecha_creacion']->format('Y-m-d H:i:s');
            }
            $result[] = $row;
        }
        return $result;
    }
}
?>
