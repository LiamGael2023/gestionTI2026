<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../../config/db.php';
    require_once '../../../../core/Auth.php';
    require_once '../../Validaciones.php';
    require_once '../models/EquipoModel.php';
    require_once '../models/EquipoEstadoModel.php';
    
    Auth::check();
    
    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }
    
    $equipo_model = new EquipoModel($conn);
    $estado_model = new EquipoEstadoModel($conn);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }
    
    // ==================== EQUIPOS ====================
    
    if ($action === 'listar') {
        $equipos = $equipo_model->obtenerTodos();
        echo json_encode(['success' => true, 'data' => $equipos]);
        exit;
    }
    
    if ($action === 'guardar') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        // Validaciones del equipo
        $errores = [];
        
        // Validar Nombre (obligatorio, 3-100 chars, debe tener letras)
        $errores['Nombre'] = Validaciones::validarNombre(
            $datos['Nombre'] ?? '',
            3,
            100
        );
        
        // Validar Estado (obligatorio, debe existir)
        $errores['Id_Estado'] = Validaciones::validarId(
            $datos['Id_Estado'] ?? null,
            true
        );
        if (!$errores['Id_Estado']) {
            $errores['Id_Estado'] = Validaciones::validarIdExiste(
                $datos['Id_Estado'],
                'laboratorio.Equipo_Estado',
                $conn,
                'Id_Estado'
            );
        }
        
        // Validar Proveedor (opcional, máx 100 chars)
        $errores['Proveedor'] = Validaciones::validarTexto(
            $datos['Proveedor'] ?? '',
            false,
            100
        );
        
        // Validar Fechas
        $errores['Fecha_Ultima_Calibracion'] = Validaciones::validarFecha(
            $datos['Fecha_Ultima_Calibracion'] ?? '',
            false
        );
        $errores['Fecha_Proxima_Calibracion'] = Validaciones::validarFecha(
            $datos['Fecha_Proxima_Calibracion'] ?? '',
            false
        );
        
        // Validar rango de fechas (próxima >= última)
        if (!$errores['Fecha_Ultima_Calibracion'] && !$errores['Fecha_Proxima_Calibracion']) {
            $errores['fechas'] = Validaciones::validarRangoFechas(
                $datos['Fecha_Ultima_Calibracion'] ?? '',
                $datos['Fecha_Proxima_Calibracion'] ?? '',
                'Fecha Última Calibración',
                'Fecha Próxima Calibración'
            );
        }
        
        // Si hay errores, devolverlos
        if (Validaciones::hayErrores($errores)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errores en los campos',
                'errors' => Validaciones::obtenerErrores($errores)
            ]);
            exit;
        }
        
        $id = $equipo_model->guardar($datos);
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Equipo guardado correctamente']);
        exit;
    }
    
    if ($action === 'obtener') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $equipo = $equipo_model->obtenerPorId($id);
        
        // Convertir fechas de DateTime a YYYY-MM-DD para JSON
        if ($equipo && isset($equipo['Fecha_Ultima_Calibracion'])) {
            if ($equipo['Fecha_Ultima_Calibracion'] instanceof DateTime) {
                $equipo['Fecha_Ultima_Calibracion'] = $equipo['Fecha_Ultima_Calibracion']->format('Y-m-d');
            } elseif (is_string($equipo['Fecha_Ultima_Calibracion']) && !preg_match('/^\d{4}-\d{2}-\d{2}/', $equipo['Fecha_Ultima_Calibracion'])) {
                // Si está en otro formato, convertir
                $fecha = DateTime::createFromFormat('m/d/Y H:i:s', $equipo['Fecha_Ultima_Calibracion']);
                if ($fecha) {
                    $equipo['Fecha_Ultima_Calibracion'] = $fecha->format('Y-m-d');
                }
            }
        }
        
        if ($equipo && isset($equipo['Fecha_Proxima_Calibracion'])) {
            if ($equipo['Fecha_Proxima_Calibracion'] instanceof DateTime) {
                $equipo['Fecha_Proxima_Calibracion'] = $equipo['Fecha_Proxima_Calibracion']->format('Y-m-d');
            } elseif (is_string($equipo['Fecha_Proxima_Calibracion']) && !preg_match('/^\d{4}-\d{2}-\d{2}/', $equipo['Fecha_Proxima_Calibracion'])) {
                // Si está en otro formato, convertir
                $fecha = DateTime::createFromFormat('m/d/Y H:i:s', $equipo['Fecha_Proxima_Calibracion']);
                if ($fecha) {
                    $equipo['Fecha_Proxima_Calibracion'] = $fecha->format('Y-m-d');
                }
            }
        }
        
        echo json_encode(['success' => true, 'data' => $equipo]);
        exit;
    }
    
    if ($action === 'actualizar') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        $errores = [];
        
        // Validar ID del equipo (obligatorio)
        $errores['Id_Equipo'] = Validaciones::validarId(
            $datos['Id_Equipo'] ?? null,
            true
        );
        
        // Validar Nombre (obligatorio, 3-100 chars, debe tener letras)
        $errores['Nombre'] = Validaciones::validarNombre(
            $datos['Nombre'] ?? '',
            3,
            100
        );
        
        // Validar Estado (obligatorio, debe existir)
        $errores['Id_Estado'] = Validaciones::validarId(
            $datos['Id_Estado'] ?? null,
            true
        );
        if (!$errores['Id_Estado']) {
            $errores['Id_Estado'] = Validaciones::validarIdExiste(
                $datos['Id_Estado'],
                'laboratorio.Equipo_Estado',
                $conn,
                'Id_Estado'
            );
        }
        
        // Validar Proveedor (opcional, máx 100 chars)
        $errores['Proveedor'] = Validaciones::validarTexto(
            $datos['Proveedor'] ?? '',
            false,
            100
        );
        
        // Validar Fechas
        $errores['Fecha_Ultima_Calibracion'] = Validaciones::validarFecha(
            $datos['Fecha_Ultima_Calibracion'] ?? '',
            false
        );
        $errores['Fecha_Proxima_Calibracion'] = Validaciones::validarFecha(
            $datos['Fecha_Proxima_Calibracion'] ?? '',
            false
        );
        
        // Validar rango de fechas (próxima >= última)
        if (!$errores['Fecha_Ultima_Calibracion'] && !$errores['Fecha_Proxima_Calibracion']) {
            $errores['fechas'] = Validaciones::validarRangoFechas(
                $datos['Fecha_Ultima_Calibracion'] ?? '',
                $datos['Fecha_Proxima_Calibracion'] ?? '',
                'Fecha Última Calibración',
                'Fecha Próxima Calibración'
            );
        }
        
        // Si hay errores, devolverlos
        if (Validaciones::hayErrores($errores)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errores en los campos',
                'errors' => Validaciones::obtenerErrores($errores)
            ]);
            exit;
        }
        
        $equipo_model->guardar($datos);
        echo json_encode(['success' => true, 'message' => 'Equipo actualizado correctamente']);
        exit;
    }
    
    if ($action === 'eliminar') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $equipo_model->eliminar($id);
        echo json_encode(['success' => true, 'message' => 'Equipo eliminado correctamente']);
        exit;
    }
    
    if ($action === 'reactivar') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $equipo_model->reactivar($id);
        echo json_encode(['success' => true, 'message' => 'Equipo reactivado correctamente']);
        exit;
    }
    
    // ==================== ESTADOS ====================
    
    if ($action === 'listar_estados') {
        $estados = $estado_model->obtenerTodos();
        echo json_encode(['success' => true, 'data' => $estados]);
        exit;
    }
    
    if ($action === 'obtener_estado') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $estado = $estado_model->obtenerPorId($id);
        echo json_encode(['success' => true, 'data' => $estado]);
        exit;
    }
    
    if ($action === 'guardar_estado') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        // Validaciones del estado
        $errores = [];
        
        // Validar Nombre (obligatorio, 2-50 chars, debe tener letras)
        $errores['Nombre'] = Validaciones::validarNombre(
            $datos['Nombre'] ?? '',
            2,
            50
        );
        
        // Validar Descripción (opcional, máx 250 chars)
        $errores['Descripcion'] = Validaciones::validarTexto(
            $datos['Descripcion'] ?? '',
            false,
            250
        );
        
        // Si hay errores, devolverlos
        if (Validaciones::hayErrores($errores)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errores en los campos',
                'errors' => Validaciones::obtenerErrores($errores)
            ]);
            exit;
        }
        
        // Asegurar que Descripcion existe
        if (!isset($datos['Descripcion'])) {
            $datos['Descripcion'] = '';
        }
        
        $id = $estado_model->guardar($datos);
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Estado guardado correctamente']);
        exit;
    }
    
    if ($action === 'actualizar_estado') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        $errores = [];
        
        // Validar ID del estado (obligatorio)
        $errores['Id_Estado'] = Validaciones::validarId(
            $datos['Id_Estado'] ?? null,
            true
        );
        
        // Validar Nombre (obligatorio, 2-50 chars, debe tener letras)
        $errores['Nombre'] = Validaciones::validarNombre(
            $datos['Nombre'] ?? '',
            2,
            50
        );
        
        // Validar Descripción (opcional, máx 250 chars)
        $errores['Descripcion'] = Validaciones::validarTexto(
            $datos['Descripcion'] ?? '',
            false,
            250
        );
        
        // Si hay errores, devolverlos
        if (Validaciones::hayErrores($errores)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errores en los campos',
                'errors' => Validaciones::obtenerErrores($errores)
            ]);
            exit;
        }
        
        if (!isset($datos['Descripcion'])) {
            $datos['Descripcion'] = '';
        }
        
        $estado_model->guardar($datos);
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
        exit;
    }
    
    if ($action === 'eliminar_estado') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        // Verificar si el estado está en uso
        if ($estado_model->estaEnUso($id)) {
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'No se puede desactivar este estado porque hay equipos activos usando este estado'
            ]);
            exit;
        }
        
        $estado_model->eliminar($id);
        echo json_encode(['success' => true, 'message' => 'Estado desactivado correctamente']);
        exit;
    }
    
    if ($action === 'reactivar_estado') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $estado_model->reactivar($id);
        echo json_encode(['success' => true, 'message' => 'Estado reactivado correctamente']);
        exit;
    }
    
    // Acción no encontrada
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Acción no encontrada: {$action}"]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
