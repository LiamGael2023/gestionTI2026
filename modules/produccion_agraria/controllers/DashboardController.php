<?php
/**
 * DashboardController — CMS No-Code del Dashboard
 *
 * Acciones:
 *   GET  /produccion_agraria/dashboard        → Vista (renderiza el CMS)
 *   AJAX dash_load                            → Config + datos de todos los widgets del usuario
 *   AJAX dash_save                            → Guardar layout del usuario
 *   AJAX dash_reset                           → Resetear a layout por defecto
 *   AJAX dash_widget                          → Datos de un widget individual (refresh)
 */

try {
    $base_path = dirname(dirname(dirname(__DIR__)));

    if (!isset($conn) || !$conn) {
        require_once $base_path . '/config/db.php';
        require_once $base_path . '/core/Auth.php';
        Auth::check();
        $conn = Conexion::conectar();
    }

    require_once __DIR__ . '/../models/DashboardModel.php';
    $dashModel = new DashboardModel($conn);

    $action     = $_GET['action'] ?? 'index';
    $usuarioId  = $_SESSION['usuario_id'] ?? 0;
    $permisos   = Auth::permisosModulo('produccion_agraria');

// ============================================================
// VISTA PRINCIPAL
// ============================================================
if ($action === 'dashboard' || $action === 'index') {
    $widgetCatalog = $dashModel->getWidgetCatalog();
    include __DIR__ . '/../views/dashboard/index.php';
    // Lanzar return fuera del try (después del catch) para no ejecutar el resto
} else {

// ============================================================
// AJAX: Cargar todos los widgets del usuario
// ============================================================
if ($action === 'dash_load') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    $config = $dashModel->getConfig($usuarioId);
    
    // Si no tiene config, cargar layout por defecto
    if (empty($config)) {
        $defaultWidgets = $dashModel->getDefaultLayout();
        $widgetsData = [];
        foreach ($defaultWidgets as $i => $w) {
            $data = $dashModel->getWidgetData($w['widget_tipo'], $w['widget_config'] ?? []);
            $widgetsData[] = [
                'posicion' => $i,
                'widget_tipo' => $w['widget_tipo'],
                'widget_titulo' => $w['widget_titulo'] ?? null,
                'widget_tamano' => $w['widget_tamano'],
                'widget_config' => $w['widget_config'] ?? [],
                'data' => $data
            ];
        }
        echo json_encode(['success' => true, 'is_default' => true, 'widgets' => $widgetsData]);
        exit;
    }

    // Cargar datos de cada widget configurado
    $widgetsData = [];
    foreach ($config as $w) {
        $data = $dashModel->getWidgetData($w['widget_tipo'], $w['widget_config'] ?? []);
        $widgetsData[] = [
            'id_config' => $w['id_config'],
            'posicion' => $w['posicion'],
            'widget_tipo' => $w['widget_tipo'],
            'widget_titulo' => $w['widget_titulo'],
            'widget_tamano' => $w['widget_tamano'],
            'widget_config' => $w['widget_config'] ?? [],
            'data' => $data
        ];
    }

    echo json_encode(['success' => true, 'is_default' => false, 'widgets' => $widgetsData]);
    exit;
}

// ============================================================
// AJAX: Guardar layout
// ============================================================
if ($action === 'dash_save') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    $widgets = $input['widgets'] ?? [];

    if (empty($widgets)) {
        echo json_encode(['success' => false, 'message' => 'No hay widgets para guardar']);
        exit;
    }

    $result = $dashModel->saveConfig($usuarioId, $widgets);
    echo json_encode($result);
    exit;
}

// ============================================================
// AJAX: Resetear a layout por defecto
// ============================================================
if ($action === 'dash_reset') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    $result = $dashModel->resetConfig($usuarioId);
    echo json_encode($result);
    exit;
}

// ============================================================
// AJAX: Datos de un widget individual (para refresh)
// ============================================================
if ($action === 'dash_widget') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    $tipo       = $_GET['tipo'] ?? '';
    $configJson = $_GET['config'] ?? '{}';
    $config     = json_decode($configJson, true) ?: [];

    if (empty($tipo)) {
        echo json_encode(['success' => false, 'message' => 'Falta tipo de widget']);
        exit;
    }

    $data = $dashModel->getWidgetData($tipo, $config);
    if (isset($data['error'])) {
        echo json_encode(['success' => false, 'message' => $data['error']]);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

} // fin else del bloque de acciones AJAX

} catch (Throwable $e) {
    error_log('[DashboardController] Error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor. Por favor, intente nuevamente.']);
    exit;
}
