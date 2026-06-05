<?php
/**
 * DashboardModel — Persistencia y datos para el Dashboard CMS
 * Reutiliza ChatToolsModel para consultas de datos
 */
class DashboardModel {
    private $db;
    private $toolsModel;

    public function __construct($db) {
        $this->db = $db;
        require_once __DIR__ . '/../../consultas/models/ChatToolsModel.php';
        $this->toolsModel = new ChatToolsModel($db);
    }

    // ============================================================
    // CONFIGURACION DEL USUARIO
    // ============================================================
    public function getConfig($usuarioId) {
        $sql = "SELECT id_config, posicion, widget_tipo, widget_titulo, widget_tamano, widget_config
                FROM BD_PRODUCCIONDESARROLLO.dbo.dashboard_config
                WHERE usuario_id = ? AND activo = 1
                ORDER BY posicion ASC";
        $stmt = sqlsrv_query($this->db, $sql, [$usuarioId]);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['widget_config'] = json_decode($row['widget_config'] ?? '{}', true) ?: [];
            $result[] = $row;
        }
        return $result;
    }

    public function saveConfig($usuarioId, $widgets) {
        if (sqlsrv_begin_transaction($this->db) === false) {
            return ['success' => false, 'message' => 'Error al iniciar transacción'];
        }

        // Eliminar configuración actual
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.dashboard_config WHERE usuario_id = ?";
        sqlsrv_query($this->db, $sql, [$usuarioId]);

        // Insertar nueva configuración
        foreach ($widgets as $i => $w) {
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.dashboard_config
                    (usuario_id, posicion, widget_tipo, widget_titulo, widget_tamano, widget_config)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [
                $usuarioId,
                $i,
                $w['widget_tipo'],
                $w['widget_titulo'] ?? null,
                $w['widget_tamano'] ?? 'medium',
                isset($w['widget_config']) ? json_encode($w['widget_config']) : '{}'
            ];
            sqlsrv_query($this->db, $sql, $params);
        }

        sqlsrv_commit($this->db);
        return ['success' => true, 'message' => 'Dashboard guardado'];
    }

    public function resetConfig($usuarioId) {
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.dashboard_config WHERE usuario_id = ?";
        sqlsrv_query($this->db, $sql, [$usuarioId]);
        return ['success' => true, 'message' => 'Dashboard restaurado al layout por defecto'];
    }

    // ============================================================
    // CATALOGO DE WIDGETS
    // ============================================================
    public function getWidgetCatalog() {
        return [
            [
                'categoria' => 'KPI',
                'icono' => 'ti ti-chart-bar',
                'widgets' => [
                    ['tipo' => 'kpi_ventas_hoy', 'nombre' => 'Ventas Hoy', 'desc' => 'Monto y cantidad de ventas del día', 'tamano' => 'small', 'icono' => 'ti ti-cash'],
                    ['tipo' => 'kpi_proformas_pendientes', 'nombre' => 'Proformas Pendientes', 'desc' => 'Cantidad de proformas sin procesar', 'tamano' => 'small', 'icono' => 'ti ti-file-invoice'],
                    ['tipo' => 'kpi_stock_critico', 'nombre' => 'Stock Crítico', 'desc' => 'Productos con menos de 10 unidades', 'tamano' => 'small', 'icono' => 'ti ti-alert-triangle'],
                    ['tipo' => 'kpi_vouchers_sin_asignar', 'nombre' => 'Vouchers Sin Asignar', 'desc' => 'Vouchers disponibles sin asignar', 'tamano' => 'small', 'icono' => 'ti ti-credit-card'],
                    ['tipo' => 'kpi_mermas_hoy', 'nombre' => 'Mermas Hoy', 'desc' => 'Unidades perdidas en el día', 'tamano' => 'small', 'icono' => 'ti ti-trash'],
                    ['tipo' => 'kpi_valor_inventario', 'nombre' => 'Valor Inventario', 'desc' => 'Valor monetario total del stock', 'tamano' => 'small', 'icono' => 'ti ti-coin'],
                    ['tipo' => 'resumen_ejecutivo', 'nombre' => 'Resumen Ejecutivo', 'desc' => '6 KPIs en un solo widget', 'tamano' => 'medium', 'icono' => 'ti ti-clipboard-check'],
                ]
            ],
            [
                'categoria' => 'Gráficos',
                'icono' => 'ti ti-chart-line',
                'widgets' => [
                    ['tipo' => 'grafico_ventas_mes', 'nombre' => 'Ventas Mensuales', 'desc' => 'Barras: monto y transacciones por mes', 'tamano' => 'medium', 'icono' => 'ti ti-chart-bar'],
                    ['tipo' => 'grafico_top_productos', 'nombre' => 'Top Productos', 'desc' => 'Barras horizontal: productos más vendidos', 'tamano' => 'medium', 'icono' => 'ti ti-trophy'],
                    ['tipo' => 'grafico_stock_centro', 'nombre' => 'Stock por Centro', 'desc' => 'Donut: distribución de stock', 'tamano' => 'small', 'icono' => 'ti ti-chart-donut'],
                    ['tipo' => 'grafico_metodo_pago', 'nombre' => 'Método de Pago', 'desc' => 'Pastel: ventas por método de pago', 'tamano' => 'small', 'icono' => 'ti ti-chart-pie'],
                    ['tipo' => 'grafico_valorizacion_clase', 'nombre' => 'Valor por Clase', 'desc' => 'Barras: valor del inventario por clase', 'tamano' => 'medium', 'icono' => 'ti ti-chart-bar'],
                    ['tipo' => 'grafico_mermas_mes', 'nombre' => 'Mermas Mensuales', 'desc' => 'Barras: cantidad y valor de mermas', 'tamano' => 'medium', 'icono' => 'ti ti-chart-bar'],
                    ['tipo' => 'grafico_vs_donaciones', 'nombre' => 'Ventas vs Donaciones', 'desc' => 'Área: comparativa de tendencias', 'tamano' => 'large', 'icono' => 'ti ti-chart-area-line'],
                ]
            ],
            [
                'categoria' => 'Tablas',
                'icono' => 'ti ti-table',
                'widgets' => [
                    ['tipo' => 'tabla_stock', 'nombre' => 'Stock Actual', 'desc' => 'Tabla con stock por producto y lote', 'tamano' => 'medium', 'icono' => 'ti ti-packages'],
                    ['tipo' => 'tabla_ventas_recientes', 'nombre' => 'Últimas Ventas', 'desc' => 'Transacciones más recientes', 'tamano' => 'medium', 'icono' => 'ti ti-receipt'],
                    ['tipo' => 'tabla_proformas', 'nombre' => 'Proformas', 'desc' => 'Proformas pendientes de procesar', 'tamano' => 'medium', 'icono' => 'ti ti-file-invoice'],
                    ['tipo' => 'tabla_recomendaciones', 'nombre' => 'Recomendaciones', 'desc' => 'Alertas: stock bajo, clientes inactivos, mermas', 'tamano' => 'medium', 'icono' => 'ti ti-bell'],
                ]
            ]
        ];
    }

    // ============================================================
    // DATOS DE WIDGETS (delega a ChatToolsModel)
    // ============================================================
    public function getWidgetData($tipo, $config = []) {
        switch ($tipo) {
            // KPIs — todos vienen de consultarResumen
            case 'kpi_ventas_hoy':
            case 'kpi_proformas_pendientes':
            case 'kpi_stock_critico':
            case 'kpi_vouchers_sin_asignar':
            case 'kpi_mermas_hoy':
            case 'kpi_valor_inventario':
            case 'resumen_ejecutivo':
                return $this->_getKpiData($tipo);

            // Gráficos — delegar a consultarGrafico
            case 'grafico_ventas_mes':
                return $this->toolsModel->consultarGrafico(['tipo' => 'ventas_mes'] + $config);
            case 'grafico_top_productos':
                return $this->toolsModel->consultarGrafico(['tipo' => 'top_productos'] + $config);
            case 'grafico_stock_centro':
                return $this->toolsModel->consultarGrafico(['tipo' => 'stock_centro'] + $config);
            case 'grafico_metodo_pago':
                return $this->toolsModel->consultarGrafico(['tipo' => 'ventas_metodo_pago'] + $config);
            case 'grafico_valorizacion_clase':
                return $this->toolsModel->consultarGrafico(['tipo' => 'valorizacion_clase'] + $config);
            case 'grafico_mermas_mes':
                return $this->toolsModel->consultarGrafico(['tipo' => 'mermas_mes'] + $config);
            case 'grafico_vs_donaciones':
                return $this->toolsModel->consultarGrafico(['tipo' => 'ventas_vs_donaciones'] + $config);

            // Tablas
            case 'tabla_stock':
                return $this->toolsModel->consultarStock($config);
            case 'tabla_ventas_recientes':
                return $this->toolsModel->consultarVentas($config);
            case 'tabla_proformas':
                return $this->toolsModel->consultarProformas($config);
            case 'tabla_recomendaciones':
                return $this->toolsModel->consultarRecomendaciones($config);

            default:
                return ['error' => 'Widget no soportado: ' . $tipo];
        }
    }

    // ============================================================
    // HELPER: Extraer KPI individual del Resumen
    // ============================================================
    private function _getKpiData($tipo) {
        $resumen = $this->toolsModel->consultarResumen([]);

        if (isset($resumen['error'])) return $resumen;

        $rows = $resumen['rows'] ?? [];

        $kpiMap = [
            'kpi_ventas_hoy' => 0,
            'kpi_proformas_pendientes' => 1,
            'kpi_stock_critico' => 2,
            'kpi_vouchers_sin_asignar' => 3,
            'kpi_mermas_hoy' => 4,
            'kpi_valor_inventario' => 5,
        ];

        if (isset($kpiMap[$tipo])) {
            $idx = $kpiMap[$tipo];
            $item = $rows[$idx] ?? null;
            if ($item) {
                $item['tipo_kpi'] = $tipo;
                return [
                    'columns' => [
                        ['key' => 'indicador', 'label' => 'Indicador'],
                        ['key' => 'valor', 'label' => 'Valor'],
                        ['key' => 'detalle', 'label' => 'Detalle']
                    ],
                    'rows' => [$item],
                    'kpi' => [
                        'tipo' => $tipo,
                        'valor' => $item['valor'],
                        'detalle' => $item['detalle'],
                        'indicador' => $item['indicador']
                    ]
                ];
            }
        }

        // Resumen ejecutivo: devolver todo
        return $resumen;
    }

    // ============================================================
    // LAYOUT POR DEFECTO PARA NUEVOS USUARIOS
    // ============================================================
    public function getDefaultLayout() {
        return [
            ['widget_tipo' => 'kpi_ventas_hoy',       'widget_tamano' => 'small',  'widget_config' => []],
            ['widget_tipo' => 'kpi_stock_critico',     'widget_tamano' => 'small',  'widget_config' => []],
            ['widget_tipo' => 'kpi_valor_inventario',   'widget_tamano' => 'small',  'widget_config' => []],
            ['widget_tipo' => 'kpi_mermas_hoy',         'widget_tamano' => 'small',  'widget_config' => []],
            ['widget_tipo' => 'grafico_ventas_mes',     'widget_tamano' => 'medium', 'widget_config' => []],
            ['widget_tipo' => 'grafico_stock_centro',   'widget_tamano' => 'small',  'widget_config' => []],
            ['widget_tipo' => 'grafico_metodo_pago',    'widget_tamano' => 'small',  'widget_config' => []],
            ['widget_tipo' => 'tabla_proformas',        'widget_tamano' => 'medium', 'widget_config' => []],
            ['widget_tipo' => 'tabla_recomendaciones',   'widget_tamano' => 'medium', 'widget_config' => []],
        ];
    }
}
