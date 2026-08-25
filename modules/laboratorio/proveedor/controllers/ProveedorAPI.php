<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../../config/db.php';
    require_once '../../../../core/Auth.php';
    require_once '../../Validaciones.php';
    require_once '../models/ProveedorModel.php';

    Auth::check();

    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    $model  = new ProveedorModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? null;

    // ── Control de permisos (roles de laboratorio) ─────────────────────
    require_once '../../models/LaboratorioModel.php';
    $labAuth        = new LaboratorioModel($conn);
    $urlSubmodulo   = '?module=laboratorio&action=proveedor';
    $permActionMap  = [
        'guardar'    => 'crear',
        'actualizar' => 'editar',
        'eliminar'   => 'eliminar',
        'reactivar'  => 'editar',
    ];
    if (isset($permActionMap[$action])) {
        $labAuth->denegarSiSinPermiso($_SESSION['usuario_id'], $urlSubmodulo, $permActionMap[$action]);
    }

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

    // ==================== OBTENER ====================
    if ($action === 'obtener') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        $proveedor = $model->obtenerPorId($id);
        echo json_encode(['success' => true, 'data' => $proveedor]);
        exit;
    }

    // ==================== GUARDAR / ACTUALIZAR ====================
    if ($action === 'guardar' || $action === 'actualizar') {
        $datos = json_decode(file_get_contents('php://input'), true);

        $errores = [];
        $errores['Razon_Social']    = Validaciones::validarNombre($datos['Razon_Social'] ?? '', 2, 150);
        $errores['Ruc']             = Validaciones::validarTexto($datos['Ruc'] ?? '', false, 20);
        $errores['Nombre_Contacto'] = Validaciones::validarTexto($datos['Nombre_Contacto'] ?? '', false, 100);
        $errores['Telefono']        = Validaciones::validarTexto($datos['Telefono'] ?? '', false, 20);
        $errores['Email']           = Validaciones::validarTexto($datos['Email'] ?? '', false, 100);
        $errores['Direccion']       = Validaciones::validarTexto($datos['Direccion'] ?? '', false, 255);

        if (Validaciones::hayErrores($errores)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errores en los campos',
                'errors'  => Validaciones::obtenerErrores($errores)
            ]);
            exit;
        }

        $id = $model->guardar($datos);
        $proveedor = $model->obtenerPorId($id);
        echo json_encode([
            'success'   => true,
            'id'        => $id,
            'proveedor' => $proveedor,
            'message'   => 'Proveedor guardado correctamente'
        ]);
        exit;
    }

    // ==================== ELIMINAR ====================
    if ($action === 'eliminar') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        $model->eliminar($id);
        echo json_encode(['success' => true, 'message' => 'Proveedor desactivado correctamente']);
        exit;
    }

    // ==================== REACTIVAR ====================
    if ($action === 'reactivar') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        $model->reactivar($id);
        echo json_encode(['success' => true, 'message' => 'Proveedor reactivado correctamente']);
        exit;
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Acción no encontrada: {$action}"]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
