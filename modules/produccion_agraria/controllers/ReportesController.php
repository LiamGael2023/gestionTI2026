<?php
try {
    $base_path = dirname(dirname(dirname(__DIR__)));

    if (!isset($conn) || !$conn) {
        require_once $base_path . '/config/db.php';
        require_once $base_path . '/core/Auth.php';
        Auth::check();
        $conn = Conexion::conectar();
    }

    require_once __DIR__ . '/../models/ReportesModel.php';

    $model  = new ReportesModel($conn);
    $action = $_GET['action'] ?? 'reportes';

    // ============================================================
    // ENDPOINTS AJAX — responden JSON puro
    // ============================================================

    if ($action === 'dashboard_data') {
        header('Content-Type: application/json; charset=utf-8');
        $filtros = [
            'id_centro'    => $_GET['id_centro']    ?? '',
            'fecha_desde'  => $_GET['fecha_desde']  ?? '',
            'fecha_hasta'  => $_GET['fecha_hasta']  ?? '',
        ];
        echo json_encode([
            'success'          => true,
            'ventas_por_mes'   => $model->getVentasPorMes($filtros),
            'metodos_pago'     => $model->getVentasPorMetodoPago($filtros),
            'inventario_clase' => $model->getInventarioPorClase($filtros),
        ]);
        exit;
    }

    if ($action === 'ventas_data') {
        header('Content-Type: application/json; charset=utf-8');
        $filtros = [
            'fecha_desde'  => $_GET['fecha_desde']  ?? '',
            'fecha_hasta'  => $_GET['fecha_hasta']  ?? '',
            'id_centro'    => $_GET['id_centro']    ?? '',
            'id_cliente'   => $_GET['id_cliente']   ?? '',
            'estado'       => $_GET['estado']       ?? '',
            'metodo_pago'  => $_GET['metodo_pago']  ?? '',
        ];
        echo json_encode([
            'success' => true,
            'data'    => $model->getVentas($filtros),
            'kpis'    => $model->getKpisVentas($filtros),
        ]);
        exit;
    }

    if ($action === 'inventario_data') {
        header('Content-Type: application/json; charset=utf-8');
        $filtros = [
            'id_centro' => $_GET['id_centro'] ?? '',
            'id_clase'  => $_GET['id_clase']  ?? '',
        ];
        echo json_encode([
            'success' => true,
            'data'    => $model->getValorizacionInventario($filtros),
        ]);
        exit;
    }

    if ($action === 'mermas_data') {
        header('Content-Type: application/json; charset=utf-8');
        $filtros = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'id_centro'   => $_GET['id_centro']   ?? '',
            'id_clase'    => $_GET['id_clase']    ?? '',
        ];
        echo json_encode([
            'success' => true,
            'data'    => $model->getMermas($filtros),
        ]);
        exit;
    }

    if ($action === 'vouchers_report_data') {
        header('Content-Type: application/json; charset=utf-8');
        $filtros = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        ];
        echo json_encode([
            'success' => true,
            'data'    => $model->getVouchersReport($filtros),
        ]);
        exit;
    }

    if ($action === 'clientes_report_data') {
        header('Content-Type: application/json; charset=utf-8');
        $filtros = [
            'cliente' => $_GET['id_cliente'] ?? $_GET['cliente'] ?? '',
        ];
        echo json_encode([
            'success' => true,
            'data'    => $model->getClientesReport($filtros),
        ]);
        exit;
    }

    if ($action === 'consolidado_report_data') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data'    => $model->getConsolidadoReport(),
        ]);
        exit;
    }

    if ($action === 'precios_report_data') {
        header('Content-Type: application/json; charset=utf-8');
        $filtros = [
            'id_centro'   => $_GET['id_centro']   ?? '',
            'id_clase'    => $_GET['id_clase']    ?? '',
            'tipo_precio' => $_GET['tipo_precio'] ?? '',
        ];
        echo json_encode([
            'success' => true,
            'data'    => $model->getPreciosReport($filtros),
        ]);
        exit;
    }

    // ============================================================
    // CARGA INICIAL DE VISTA
    // ============================================================

    // Fecha por defecto: mes actual
    $fecha_desde_default = date('Y-m-01');
    $fecha_hasta_default = date('Y-m-t');

    // Catálogos para los filtros
    $centros  = $model->getCentros();
    $clases   = $model->getClases();
    $clientes = $model->getClientes();

    // Datos iniciales vacíos para optimización de carga instantánea
    $ventas_init      = [];
    $kpis_init        = [
        'monto_total' => 0,
        'total_transacciones' => 0,
        'ticket_promedio' => 0
    ];
    $inventario_init  = [];
    $mermas_init      = [];
    
    // Carga inicial vacía para los reportes
    $vouchers_init    = [];
    $clientes_init    = [];
    $consolidado_init = [];
    $precios_init     = [];

    // Dashboard (removido gráficos, pero mantenemos variables básicas por compatibilidad)
    $ventas_mes_init       = [];
    $metodos_pago_init     = [];
    $inventario_clase_init = [];

    include __DIR__ . '/../views/reportes/index.php';

} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
