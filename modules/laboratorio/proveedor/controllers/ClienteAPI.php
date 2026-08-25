<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../../config/db.php';
    require_once '../../../../core/Auth.php';
    require_once '../../Validaciones.php';
    require_once '../models/ClienteModel.php';

    Auth::check();

    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    $model  = new ClienteModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? null;

    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }

    // ==================== LISTAR ====================
    if ($action === 'listar') {
        echo json_encode(['success' => true, 'data' => $model->obtenerTodos()]);
        exit;
    }

    // ==================== LISTAR ACTIVOS (para selectores) ====================
    if ($action === 'listar_activos') {
        echo json_encode(['success' => true, 'data' => $model->obtenerActivos()]);
        exit;
    }

    // ==================== OBTENER ====================
    if ($action === 'obtener') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        $cliente = $model->obtenerPorId($id);
        echo json_encode(['success' => true, 'data' => $cliente]);
        exit;
    }

    // ==================== GUARDAR / ACTUALIZAR ====================
    if ($action === 'guardar' || $action === 'actualizar') {
        // ── Control de permisos (roles de laboratorio) ────────────────────
        require_once '../../models/LaboratorioModel.php';
        $labModelCli  = new LaboratorioModel($conn);
        $urlSubCli    = '?module=laboratorio&action=proveedor';
        $permNecesito = ($action === 'guardar') ? 'crear' : 'editar';
        $labModelCli->denegarSiSinPermiso($_SESSION['usuario_id'], $urlSubCli, $permNecesito);

        $datos = json_decode(file_get_contents('php://input'), true);

        if (empty(trim($datos['Nombres'] ?? ''))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
            exit;
        }
        if (empty(trim($datos['Apellido_Paterno'] ?? ''))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El apellido paterno es obligatorio']);
            exit;
        }
        if (!empty($datos['Dni']) && strlen(trim($datos['Dni'])) > 12) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El DNI no puede exceder 12 caracteres']);
            exit;
        }

        $id = $model->guardar($datos);
        $cliente = $model->obtenerPorId($id);
        echo json_encode([
            'success' => true,
            'id'      => $id,
            'cliente' => $cliente,
            'message' => 'Cliente guardado correctamente'
        ]);
        exit;
    }

    // ==================== ELIMINAR ====================
    if ($action === 'eliminar') {
        $labModelCli->denegarSiSinPermiso($_SESSION['usuario_id'], $urlSubCli, 'eliminar');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        $model->eliminar($id);
        echo json_encode(['success' => true, 'message' => 'Cliente desactivado correctamente']);
        exit;
    }

    // ==================== REACTIVAR ====================
    if ($action === 'reactivar') {
        $labModelCli->denegarSiSinPermiso($_SESSION['usuario_id'], $urlSubCli, 'editar');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        $model->reactivar($id);
        echo json_encode(['success' => true, 'message' => 'Cliente reactivado correctamente']);
        exit;
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida: ' . $action]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
