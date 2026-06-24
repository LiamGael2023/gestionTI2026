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
    
    require_once __DIR__ . '/../models/TablasModel.php';
    
    $model = new TablasModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'listar';
    $tabla = $_GET['tabla'] ?? 'clase';
    
    // ========================================
    // ACCIONES AJAX/JSON - Solo estas envían JSON headers
    // ========================================
    
    if ($action == 'guardar_clase') {
        header('Content-Type: application/json; charset=utf-8');
        $result = $model->guardarClase($_POST);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'eliminar_clase') {
        header('Content-Type: application/json; charset=utf-8');
        $id = $_POST['id_clase'] ?? 0;
        $success = $model->eliminarClase($id);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($action == 'obtener_clase') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }
        $clase = $model->obtenerClase($id);
        echo json_encode($clase);
        exit;
    }
    
    // --- CENTRO_PRODUCCION ---
    if ($action == 'guardar_centro') {
        header('Content-Type: application/json; charset=utf-8');
        $result = $model->guardarCentro($_POST);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'eliminar_centro') {
        header('Content-Type: application/json; charset=utf-8');
        $id = $_POST['id_centro'] ?? 0;
        $success = $model->eliminarCentro($id);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($action == 'obtener_centro') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }
        $centro = $model->obtenerCentro($id);
        echo json_encode($centro);
        exit;
    }
    
    // --- VINCULACION CLASE-CENTRO ---
    if ($action == 'obtener_vinculacion') {
        header('Content-Type: application/json; charset=utf-8');
        $idClase = intval($_GET['id_clase'] ?? 0);
        $ids = $model->obtenerVinculacion($idClase);
        echo json_encode($ids);
        exit;
    }
    
    if ($action == 'guardar_vinculaciones') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $idClase = intval($data['id_clase'] ?? 0);
        $centrosIds = $data['centros'] ?? [];
        $result = $model->guardarVinculaciones($idClase, $centrosIds);
        echo json_encode($result);
        exit;
    }
    
    // --- UIT ---
    if ($action == 'guardar_uit') {
        header('Content-Type: application/json; charset=utf-8');
        $result = $model->guardarUit($_POST);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'eliminar_uit') {
        header('Content-Type: application/json; charset=utf-8');
        $anio = $_POST['anio'] ?? 0;
        $success = $model->eliminarUit($anio);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($action == 'obtener_uit') {
        header('Content-Type: application/json; charset=utf-8');
        $anio = intval($_GET['anio'] ?? 0);
        if ($anio <= 0) {
            echo json_encode(['error' => 'Año inválido']);
            exit;
        }
        $uit = $model->obtenerUit($anio);
        echo json_encode($uit);
        exit;
    }
    
    // --- CLIENTE ---
    if ($action == 'guardar_cliente') {
        header('Content-Type: application/json; charset=utf-8');
        $result = $model->guardarCliente($_POST);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'eliminar_cliente') {
        header('Content-Type: application/json; charset=utf-8');
        $id = $_POST['id_cliente'] ?? 0;
        $success = $model->eliminarCliente($id);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($action == 'obtener_cliente') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }
        $cliente = $model->obtenerCliente($id);
        echo json_encode($cliente);
        exit;
    }
    
    // ========================================
    // VISTAS (action=listar u otras)
    // ========================================
    
    // Cargar todos los datos para tabs client-side
    $clases = $model->listarClases();
    $centros = $model->listarCentros();
    $uits = $model->listarUits();
    $clientes = $model->listarClientes();
    
    include __DIR__ . '/../views/tablas/index.php';
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
