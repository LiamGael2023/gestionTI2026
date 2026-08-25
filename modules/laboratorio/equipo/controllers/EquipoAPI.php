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
    require_once '../../proveedor/models/ProveedorModel.php';
    
    Auth::check();
    
    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }
    
    $equipo_model    = new EquipoModel($conn);
    $estado_model    = new EquipoEstadoModel($conn);
    $proveedor_model = new ProveedorModel($conn);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    // ── Control de permisos (roles de laboratorio) ─────────────────────
    require_once '../../models/LaboratorioModel.php';
    $labAuth        = new LaboratorioModel($conn);
    $urlSubmodulo   = '?module=laboratorio&action=equipo';
    $permActionMap  = [
        'guardar'                => 'crear',
        'actualizar'             => 'editar',
        'eliminar'               => 'eliminar',
        'reactivar'              => 'editar',
        'guardar_estado'         => 'crear',
        'actualizar_estado'      => 'editar',
        'eliminar_estado'        => 'eliminar',
        'reactivar_estado'       => 'editar',
        'registrar_calibracion'  => 'editar',
        'finalizar_calibracion'  => 'editar',
    ];
    if (isset($permActionMap[$action])) {
        $labAuth->denegarSiSinPermiso($_SESSION['usuario_id'], $urlSubmodulo, $permActionMap[$action]);
    }
    
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
        
        // Validar Fecha Adquisicion (opcional)
        $errores['Fecha_Adquisicion'] = Validaciones::validarFecha(
            $datos['Fecha_Adquisicion'] ?? '',
            false
        );

        // Validar Fechas calibración
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
                'errors'  => Validaciones::obtenerErrores($errores)
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
        // Convertir Fecha_Adquisicion
        if ($equipo && isset($equipo['Fecha_Adquisicion'])) {
            if ($equipo['Fecha_Adquisicion'] instanceof DateTime) {
                $equipo['Fecha_Adquisicion'] = $equipo['Fecha_Adquisicion']->format('Y-m-d');
            }
        }

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
        
        // Validar Fecha Adquisicion (opcional)
        $errores['Fecha_Adquisicion'] = Validaciones::validarFecha(
            $datos['Fecha_Adquisicion'] ?? '',
            false
        );

        // Validar Fechas calibración
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
                'errors'  => Validaciones::obtenerErrores($errores)
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
    
    // ==================== HISTORIAL CALIBRACIONES ====================
    if ($action === 'historial_calibracion') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de equipo requerido']);
            exit;
        }
        $historial = $equipo_model->obtenerHistorialCalibracion($id);
        echo json_encode(['success' => true, 'data' => $historial]);
        exit;
    }

    // ==================== LISTAR PROVEEDORES ====================
    if ($action === 'listar_proveedores') {
        $proveedores = $proveedor_model->obtenerTodos();
        echo json_encode(['success' => true, 'data' => $proveedores]);
        exit;
    }

    // ==================== REGISTRAR CALIBRACIÓN ====================
    if ($action === 'registrar_calibracion') {
        $datos = json_decode(file_get_contents('php://input'), true);

        $idEquipo = intval($datos['Id_Equipo'] ?? 0);
        $idEstado = intval($datos['Id_Estado_Nuevo'] ?? 0);

        if (!$idEquipo || !$idEstado) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Equipo y estado son obligatorios']);
            exit;
        }

        // Verificar que el estado NO sea Disponible
        $idDisponible = $equipo_model->obtenerIdEstadoDisponible();
        if ($idDisponible && $idEstado == $idDisponible) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Durante la calibración debe seleccionar un estado diferente a Disponible']);
            exit;
        }

        // Verificar que el estado existe
        $estadoExiste = Validaciones::validarIdExiste($idEstado, 'laboratorio.Equipo_Estado', $conn, 'Id_Estado');
        if ($estadoExiste) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $estadoExiste]);
            exit;
        }

        $equipo_model->actualizarEstado($idEquipo, $idEstado, false);
        echo json_encode(['success' => true, 'message' => 'Calibración iniciada correctamente']);
        exit;
    }

    // ==================== FINALIZAR CALIBRACIÓN ====================
    if ($action === 'finalizar_calibracion') {
        $datos = json_decode(file_get_contents('php://input'), true);

        $idEquipo      = intval($datos['Id_Equipo'] ?? 0);
        $observacion   = trim($datos['Observacion'] ?? '');
        $fechaProxima  = !empty($datos['Fecha_Proxima_Calibracion']) ? trim($datos['Fecha_Proxima_Calibracion']) : null;

        if (!$idEquipo) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de equipo requerido']);
            exit;
        }
        if (empty($observacion)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La observación de calibración es obligatoria']);
            exit;
        }
        if (strlen($observacion) > 2000) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La observación no puede superar 2000 caracteres']);
            exit;
        }
        if ($fechaProxima !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaProxima)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Formato de fecha próxima inválido']);
            exit;
        }

        $idDisponible = $equipo_model->obtenerIdEstadoDisponible();
        if (!$idDisponible) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No se encontró el estado "Disponible" en el sistema']);
            exit;
        }

        // Guardar observación + actualizar estado a Disponible + actualizar Fecha_Ultima_Calibracion (y opcionalmente Fecha_Proxima)
        $equipo_model->registrarObservacionCalibracion($idEquipo, $observacion);
        $equipo_model->actualizarEstado($idEquipo, $idDisponible, true, $fechaProxima);

        echo json_encode(['success' => true, 'message' => 'Calibración finalizada. Equipo devuelto a estado Disponible.']);
        exit;
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Acción no encontrada: {$action}"]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
