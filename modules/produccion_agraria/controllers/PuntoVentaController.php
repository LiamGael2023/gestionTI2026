<?php
// Prevenir cualquier output antes de headers
ob_start();

try {
    // Calcular ruta base
    $base_path = dirname(dirname(dirname(__DIR__)));

    // Si no hay conexión, cargarla
    if (!isset($conn) || !$conn) {
        require_once $base_path . '/config/db.php';
        require_once $base_path . '/core/Auth.php';
        Auth::check();
        $conn = Conexion::conectar();
    }

    require_once __DIR__ . '/../models/PuntoVentaModel.php';

    $model  = new PuntoVentaModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'index';

    // ── Token CSRF para operaciones de escritura ──
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $validarCsrf = function ($token) {
        return is_string($token) && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    };

    // ========================================
    // ACCIONES AJAX/JSON
    // ========================================

    if ($action == 'guardar_venta') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!$validarCsrf($data['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.']);
            exit;
        }
        $result = $model->guardarVenta($data);
        echo json_encode($result);
        exit;
    }

    if ($action == 'crear_cliente_rapido') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!$validarCsrf($data['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.']);
            exit;
        }
        $nombre = trim($data['nombre'] ?? '');
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre no puede estar vacío']);
            exit;
        }
        $result = $model->crearClienteRapido($nombre);
        echo json_encode($result);
        exit;
    }

    // Consulta de cliente por documento (puede registrar el cliente si no existe)
    if ($action == 'buscar_cliente_api') {
        $documento = trim($_GET['documento'] ?? '');
        try {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            if (!$validarCsrf($_GET['csrf_token'] ?? null)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.']);
                exit;
            }
            if (empty($documento)) {
                echo json_encode(['success' => false, 'message' => 'Documento vacío']);
                exit;
            }
            $result = $model->buscarClientePorAPI($documento);
            echo json_encode([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            error_log('[PuntoVentaController::buscar_cliente_api] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al consultar la información del cliente.']);
        }
        exit;
    }

    // ========================================
    // VISTA
    // ========================================

    ob_clean();
    $clientes  = $model->listarClientes();
    $productos = $model->listarProductosVenta();
    $csrfToken = $_SESSION['csrf_token'];

    include __DIR__ . '/../views/punto_venta/index.php';

} catch (\Throwable $e) {
    ob_clean();
    error_log('[PuntoVentaController] Error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor. Por favor, intente nuevamente.']);
}
?>
