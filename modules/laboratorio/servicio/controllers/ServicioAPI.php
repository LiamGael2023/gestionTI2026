<?php
/**
 * ServicioAPI.php
 * API Handler - Maneja acciones AJAX para Servicios Técnicos
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
    require_once '../models/ServicioModel.php';
    
    Auth::check();
    
    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }
    
    $servicio_model = new ServicioModel($conn);

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    // ── Control de permisos (roles de laboratorio) ─────────────────────
    require_once '../../models/LaboratorioModel.php';
    $labAuth        = new LaboratorioModel($conn);
    $urlSubmodulo   = '?module=laboratorio&action=servicio';
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

    switch ($action) {
        case 'listar':
            $servicios = $servicio_model->obtenerTodos();
            echo json_encode(['success' => true, 'data' => $servicios]);
            exit;

        case 'guardar':
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (!$datos) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Error al procesar JSON: ' . json_last_error_msg()]);
                exit;
            }
            
            if (empty($datos['Nombre'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre del servicio es obligatorio']);
                exit;
            }

            // Validar que los parámetros no estén asignados a otros servicios
            if (!empty($datos['Parametros']) && is_array($datos['Parametros'])) {
                require_once '../../parametro/models/ParametroModel.php';
                $param_model = new ParametroModel($conn);
                foreach ($datos['Parametros'] as $parametro) {
                    $param = $param_model->obtenerPorId($parametro['Id_Parametro']);
                    if ($param && !empty($param['Id_Servicio']) && $param['Id_Servicio'] > 0) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'El parámetro "' . ($param['Nombre'] ?? 'ID ' . $parametro['Id_Parametro']) . '" ya está asignado a otro servicio y no puede reutilizarse']);
                        exit;
                    }
                }
            }

            // Guardar servicio base
            $id = $servicio_model->guardar($datos);
            
            // Guardar equipos (Requisito_Equipo)
            if (!empty($datos['Equipos']) && is_array($datos['Equipos'])) {
                foreach ($datos['Equipos'] as $equipo) {
                    $servicio_model->guardarRequisito([
                        'Id_Equipo' => $equipo['Id_Equipo'],
                        'Id_Servicio' => $id,
                        'Es_Bloqueante' => $equipo['Es_Bloqueante'] ?? 1
                    ]);
                }
            }
            
            // Guardar reactivos (Receta_Servicio)
            if (!empty($datos['Reactivos']) && is_array($datos['Reactivos'])) {
                foreach ($datos['Reactivos'] as $reactivo) {
                    $servicio_model->guardarReceta([
                        'Id_Reactivo' => $reactivo['Id_Reactivo'],
                        'Id_Servicio' => $id,
                        'Cantidad_Necesaria' => $reactivo['Cantidad_Necesaria'] ?? 0
                    ]);
                }
            }

            // Guardar parámetros (asignar Id_Servicio en laboratorio.Parametro)
            if (!empty($datos['Parametros']) && is_array($datos['Parametros'])) {
                require_once '../../parametro/models/ParametroModel.php';
                $param_model = new ParametroModel($conn);
                foreach ($datos['Parametros'] as $parametro) {
                    $servicio_model->guardarParametro([
                        'Id_Parametro' => $parametro['Id_Parametro'],
                        'Id_Servicio'  => $id
                    ]);
                }
            }
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Servicio guardado correctamente']);
            exit;

        case 'obtener':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            
            $servicio = $servicio_model->obtenerPorId($id);
            $equipos = $servicio_model->obtenerRequisitos($id);
            $reactivos = $servicio_model->obtenerRecetas($id);
            $parametros = $servicio_model->obtenerParametros($id);
            
            echo json_encode([
                'success' => true, 
                'data' => $servicio,
                'equipos' => $equipos,
                'reactivos' => $reactivos,
                'parametros' => $parametros
            ]);
            exit;

        case 'actualizar':
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (!$datos || empty($datos['Id_Servicio'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID del servicio no válido']);
                exit;
            }

            if (empty($datos['Nombre'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El nombre del servicio es obligatorio']);
                exit;
            }

            // Validar que los parámetros no estén asignados a otros servicios
            if (!empty($datos['Parametros']) && is_array($datos['Parametros'])) {
                require_once '../../parametro/models/ParametroModel.php';
                $param_model = new ParametroModel($conn);
                foreach ($datos['Parametros'] as $parametro) {
                    $param = $param_model->obtenerPorId($parametro['Id_Parametro']);
                    if ($param && !empty($param['Id_Servicio']) && $param['Id_Servicio'] > 0 && $param['Id_Servicio'] != $datos['Id_Servicio']) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'El parámetro "' . ($param['Nombre'] ?? 'ID ' . $parametro['Id_Parametro']) . '" ya está asignado a otro servicio y no puede reutilizarse']);
                        exit;
                    }
                }
            }

            // Actualizar servicio base
            $servicio_model->guardar($datos);
            
            // Eliminar equipos antiguos y agregar nuevos
            $equiposAntiguos = $servicio_model->obtenerRequisitos($datos['Id_Servicio']);
            foreach ($equiposAntiguos as $equipo) {
                $servicio_model->eliminarRequisito($datos['Id_Servicio'], $equipo['Id_Equipo']);
            }
            
            if (!empty($datos['Equipos']) && is_array($datos['Equipos'])) {
                foreach ($datos['Equipos'] as $equipo) {
                    $servicio_model->guardarRequisito([
                        'Id_Equipo' => $equipo['Id_Equipo'],
                        'Id_Servicio' => $datos['Id_Servicio'],
                        'Es_Bloqueante' => $equipo['Es_Bloqueante'] ?? 1
                    ]);
                }
            }
            
            // Eliminar reactivos antiguos y agregar nuevos
            $reactivosAntiguos = $servicio_model->obtenerRecetas($datos['Id_Servicio']);
            foreach ($reactivosAntiguos as $reactivo) {
                $servicio_model->eliminarReceta($datos['Id_Servicio'], $reactivo['Id_Reactivo']);
            }
            
            if (!empty($datos['Reactivos']) && is_array($datos['Reactivos'])) {
                foreach ($datos['Reactivos'] as $reactivo) {
                    $servicio_model->guardarReceta([
                        'Id_Reactivo' => $reactivo['Id_Reactivo'],
                        'Id_Servicio' => $datos['Id_Servicio'],
                        'Cantidad_Necesaria' => $reactivo['Cantidad_Necesaria'] ?? 0
                    ]);
                }
            }

            // Eliminar parámetros antiguos y agregar nuevos
            $parametrosAntiguos = $servicio_model->obtenerParametros($datos['Id_Servicio']);
            $parametrosNuevos = !empty($datos['Parametros']) && is_array($datos['Parametros']) ? $datos['Parametros'] : [];
            
            // IDs de parámetros nuevos
            $idsNuevos = array_map(fn($p) => $p['Id_Parametro'], $parametrosNuevos);
            
            // Eliminar parámetros que estaban pero ya no están
            foreach ($parametrosAntiguos as $parametroAntiguo) {
                if (!in_array($parametroAntiguo['Id_Parametro'], $idsNuevos)) {
                    $servicio_model->eliminarParametro($datos['Id_Servicio'], $parametroAntiguo['Id_Parametro']);
                }
            }
            
            // Agregar solo los parámetros nuevos
            foreach ($parametrosNuevos as $parametro) {
                $servicio_model->guardarParametro([
                    'Id_Parametro' => $parametro['Id_Parametro'],
                    'Id_Servicio' => $datos['Id_Servicio']
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Servicio actualizado correctamente']);
            exit;

        case 'eliminar':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no válido']);
                exit;
            }

            $servicio_model->eliminar($id);
            echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente']);
            exit;

        case 'reactivar':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no válido']);
                exit;
            }

            $servicio_model->reactivar($id);
            echo json_encode(['success' => true, 'message' => 'Servicio reactivado correctamente']);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
