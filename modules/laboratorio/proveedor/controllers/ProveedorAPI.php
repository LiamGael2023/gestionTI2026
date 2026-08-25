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

        // Validar RUC duplicado (UNIQUE KEY en laboratorio.Proveedor.Ruc)
        // antes del INSERT, para devolver un mensaje claro en lugar del error crudo de SQL Server.
        $rucIngresado = trim((string)($datos['Ruc'] ?? ''));
        if ($rucIngresado !== '') {
            $stmtRuc = sqlsrv_query($conn, "SELECT Id_Proveedor FROM laboratorio.Proveedor WHERE Ruc = ?", [$rucIngresado]);
            if ($stmtRuc === false) {
                throw new Exception('Error al validar RUC: ' . print_r(sqlsrv_errors(), true));
            }
            $rowRuc = sqlsrv_fetch_array($stmtRuc, SQLSRV_FETCH_ASSOC);
            $idEditando = intval($datos['Id_Proveedor'] ?? 0);
            if ($rowRuc && intval($rowRuc['Id_Proveedor']) !== $idEditando) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Ya existe un proveedor con el RUC ' . $rucIngresado . '. Verifique el dato o seleccione el proveedor existente.',
                    'errors'  => ['Ruc' => 'El RUC ya está registrado en otro proveedor']
                ]);
                exit;
            }
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
