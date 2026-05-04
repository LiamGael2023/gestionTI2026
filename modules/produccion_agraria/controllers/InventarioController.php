<?php
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
    
    require_once __DIR__ . '/../models/InventarioModel.php';
    
    $model = new InventarioModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'listar';
    
    // ========================================
    // ACCIONES AJAX/JSON
    // ========================================
    
    if ($action == 'guardar_producto') {
        header('Content-Type: application/json; charset=utf-8');
        $data = $_POST;
        $result = $model->guardarProducto($data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'eliminar_producto') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_POST['id_producto'] ?? 0);
        $result = $model->eliminarProducto($id);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'obtener_producto') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        $producto = $model->obtenerProducto($id);
        echo json_encode($producto);
        exit;
    }
    
    if ($action == 'obtener_lotes') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id_producto'] ?? 0);
        $lotes = $model->listarLotesPorProducto($id);
        $stockTotal = $model->obtenerStockTotal($id);
        echo json_encode(['lotes' => $lotes, 'stock_total' => $stockTotal]);
        exit;
    }
    
    if ($action == 'obtener_kardex') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id_producto'] ?? 0);
        $movimientos = $model->listarMovimientosKardex($id);
        echo json_encode(['movimientos' => $movimientos]);
        exit;
    }
    
    if ($action == 'guardar_lote') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $model->guardarLote($data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'guardar_merma') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $model->guardarMerma($data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'obtener_precio_actual') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id_producto'] ?? 0);
        $precio = $model->obtenerPrecioActual($id);
        echo json_encode($precio ?: ['precio_oficial' => null]);
        exit;
    }
    
    if ($action == 'guardar_precio') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $model->guardarPrecio($data);
        echo json_encode($result);
        exit;
    }
    
    // ========================================
    // VISTA
    // ========================================
    
    $productos = $model->listarProductos();
    $clases = $model->listarClasesSelect();
    $centros = $model->listarCentrosSelect();
    $uitActual = $model->obtenerUITActual();
    
    include __DIR__ . '/../views/inventario/index.php';
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
