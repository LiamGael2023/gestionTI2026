<?php
/**
 * VentaAPI.php
 * API Handler - Maneja acciones AJAX para Ventas de Servicios
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

header('Content-Type: application/json; charset=utf-8');

// Capturar errores PHP y devolverlos como JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});

try {
    require_once '../../../../config/db.php';
    require_once '../../../../core/Auth.php';
    require_once '../models/VentaModel.php';
    
    Auth::check();
    
    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }
    
    $venta_model = new VentaModel($conn);

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }

    switch ($action) {
        case 'listar':
            $scope = $_GET['scope'] ?? $_POST['scope'] ?? 'interno_general';
            $ventas = $venta_model->obtenerTodos($scope);
            echo json_encode(['success' => true, 'data' => $ventas]);
            exit;

        case 'guardar':
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (!$datos) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Error al procesar JSON: ' . json_last_error_msg()]);
                exit;
            }
            
            if (empty($datos['Nombre_Comercial'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre comercial del producto es obligatorio']);
                exit;
            }

            if (empty($datos['Precio_Venta']) || $datos['Precio_Venta'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El precio debe ser mayor a 0']);
                exit;
            }

            if (empty($datos['Tipo'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe seleccionar un tipo de producto']);
                exit;
            }

            if (empty($datos['Tipo_Vista'])) {
                $datos['Tipo_Vista'] = 'GENERAL';
            }

            if ($datos['Tipo_Vista'] !== 'GENERAL' && $datos['Tipo_Vista'] !== 'INTERNO') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo de vista no válido']);
                exit;
            }

            if (empty($datos['Servicios']) || !is_array($datos['Servicios'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un servicio']);
                exit;
            }

            // Validar cantidad de servicios según tipo
            if ($datos['Tipo'] === 'Individual' && count($datos['Servicios']) !== 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Un producto Individual debe contener exactamente 1 servicio']);
                exit;
            }

            if ($datos['Tipo'] === 'Paquete' && count($datos['Servicios']) < 2) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Un paquete debe contener al menos 2 servicios']);
                exit;
            }

            // Guardar producto base
            $id = $venta_model->guardar($datos);
            
            // Guardar servicios (Producto_Servicio)
            foreach ($datos['Servicios'] as $servicio) {
                $venta_model->guardarProductoServicio([
                    'Id_Producto' => $id,
                    'Id_Servicio' => $servicio['Id_Servicio']
                ]);
            }
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Producto guardado correctamente']);
            exit;

        case 'obtener':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            
            $venta = $venta_model->obtenerPorId($id);
            $servicios = $venta_model->obtenerServicios($id);
            
            echo json_encode([
                'success' => true, 
                'data' => $venta,
                'servicios' => $servicios
            ]);
            exit;

        case 'actualizar':
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (!$datos || empty($datos['Id_Producto'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID del producto no válido']);
                exit;
            }

            // Validaciones
            if (empty($datos['Nombre_Comercial'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre comercial del producto es obligatorio']);
                exit;
            }

            if (empty($datos['Precio_Venta']) || $datos['Precio_Venta'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El precio debe ser mayor a 0']);
                exit;
            }

            if (empty($datos['Tipo'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe seleccionar un tipo de producto']);
                exit;
            }

            if (empty($datos['Tipo_Vista'])) {
                $datos['Tipo_Vista'] = 'GENERAL';
            }

            if ($datos['Tipo_Vista'] !== 'GENERAL' && $datos['Tipo_Vista'] !== 'INTERNO') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo de vista no válido']);
                exit;
            }

            if (empty($datos['Servicios']) || !is_array($datos['Servicios'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un servicio']);
                exit;
            }

            // Validar cantidad de servicios según tipo
            if ($datos['Tipo'] === 'Individual' && count($datos['Servicios']) !== 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Un producto Individual debe contener exactamente 1 servicio']);
                exit;
            }

            if ($datos['Tipo'] === 'Paquete' && count($datos['Servicios']) < 2) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Un paquete debe contener al menos 2 servicios']);
                exit;
            }

            // Actualizar producto base
            $venta_model->guardar($datos);
            
            // Eliminar servicios antiguos
            $serviciosAntiguos = $venta_model->obtenerServicios($datos['Id_Producto']);
            foreach ($serviciosAntiguos as $servicio) {
                $venta_model->eliminarProductoServicio($datos['Id_Producto'], $servicio['Id_Servicio']);
            }
            
            // Agregar servicios nuevos
            foreach ($datos['Servicios'] as $servicio) {
                $venta_model->guardarProductoServicio([
                    'Id_Producto' => $datos['Id_Producto'],
                    'Id_Servicio' => $servicio['Id_Servicio']
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente']);
            exit;

        case 'eliminar':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no válido']);
                exit;
            }

            $venta_model->eliminar($id);
            echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente']);
            exit;

        case 'reactivar':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no válido']);
                exit;
            }

            $venta_model->reactivar($id);
            echo json_encode(['success' => true, 'message' => 'Producto reactivado correctamente']);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
