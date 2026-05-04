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
    
    $model = new PuntoVentaModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
    
    // ========================================
    // ACCIONES AJAX/JSON
    // ========================================
    
    if ($action == 'buscar_producto') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        $producto = $model->buscarProducto($id);
        echo json_encode($producto);
        exit;
    }

    if ($action == 'buscar_clientes') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $query = $_GET['q'] ?? '';
        error_log('buscar_clientes llamado con query: ' . $query);
        $clientes = $model->buscarClientes($query);
        error_log('Resultados: ' . count($clientes));
        echo json_encode($clientes);
        exit;
    }
    
    if ($action == 'guardar_venta') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $model->guardarVenta($data);
        echo json_encode($result);
        exit;
    }
    
    // ========================================
    // VISTA
    // ========================================
    
    ob_clean();
    $clientes = $model->listarClientes();
    $productos = $model->listarProductosVenta();
    $ventasHoy = $model->listarVentasHoy();
    
    include __DIR__ . '/../views/punto_venta/index.php';
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
