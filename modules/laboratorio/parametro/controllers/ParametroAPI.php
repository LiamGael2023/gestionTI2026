<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../../config/db.php';
    require_once '../../../../core/Auth.php';
    require_once '../../Validaciones.php';
    require_once '../models/ParametroModel.php';
    require_once '../models/NormativaModel.php';
    require_once '../models/LimiteModel.php';
    
    Auth::check();
    
    $conn = Conexion::conectar();
    if (!$conn) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Error de conexion a BD']));
    }
    
    $parametro_model = new ParametroModel($conn);
    $normativa_model = new NormativaModel($conn);
    $limite_model = new LimiteModel($conn);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Accion no especificada']));
    }
    
    // ==================== PARAMETROS ====================
    
    if ($action === 'listar') {
        $parametros = $parametro_model->obtenerTodos();
        die(json_encode(['success' => true, 'data' => $parametros]));
    }

    if ($action === 'listar_servicios') {
        $scope = strtoupper(trim((string)($_GET['scope'] ?? $_POST['scope'] ?? 'INTERNO_GENERAL')));

        $sqlCheck = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Servicio_Tecnico' AND COLUMN_NAME = 'Tipo_Vista'";
        $stmtCheck = sqlsrv_query($conn, $sqlCheck);
        $tieneTipoVista = $stmtCheck && sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) !== null;

        $sql = "SELECT Id_Servicio, Nombre" . ($tieneTipoVista ? ", Tipo_Vista" : "") . " FROM laboratorio.Servicio_Tecnico WHERE Activo = 1";
        $params = [];

        if ($tieneTipoVista) {
            if ($scope === 'GENERAL' || $scope === 'EXTERNO') {
                $sql .= " AND (Tipo_Vista = ? OR Tipo_Vista IS NULL)";
                $params[] = 'GENERAL';
            } elseif ($scope === 'INTERNO') {
                $sql .= " AND Tipo_Vista = ?";
                $params[] = 'INTERNO';
            } else {
                $sql .= " AND (Tipo_Vista IN (?, ?) OR Tipo_Vista IS NULL)";
                $params[] = 'INTERNO';
                $params[] = 'GENERAL';
            }
        }

        $sql .= " ORDER BY Nombre";

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Error al obtener servicios']));
        }
        $servicios = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $servicios[] = $row;
        }
        die(json_encode(['success' => true, 'data' => $servicios]));
    }

    if ($action === 'guardar') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (empty($datos['Nombre']) || strlen(trim($datos['Nombre'])) < 2) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'El nombre es obligatorio']));
        }
        
        if (empty($datos['Id_Servicio']) || $datos['Id_Servicio'] === '') {
            $datos['Id_Servicio'] = null;
        }
        
        $id = $parametro_model->guardar($datos);
        die(json_encode(['success' => true, 'id' => $id, 'message' => 'Parametro guardado correctamente']));
    }
    
    if ($action === 'obtener') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $parametro = $parametro_model->obtenerPorId($id);
        die(json_encode(['success' => true, 'data' => $parametro]));
    }
    
    if ($action === 'actualizar') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (empty($datos['Id_Parametro'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        if (empty($datos['Nombre']) || strlen(trim($datos['Nombre'])) < 2) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'El nombre es obligatorio']));
        }
        
        if (empty($datos['Id_Servicio']) || $datos['Id_Servicio'] === '') {
            $datos['Id_Servicio'] = null;
        }
        
        $parametro_model->guardar($datos);
        die(json_encode(['success' => true, 'message' => 'Parametro actualizado correctamente']));
    }
    
    if ($action === 'eliminar') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $parametro_model->eliminar($id);
        die(json_encode(['success' => true, 'message' => 'Parametro eliminado correctamente']));
    }
    
    if ($action === 'reactivar') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $parametro_model->reactivar($id);
        die(json_encode(['success' => true, 'message' => 'Parametro reactivado correctamente']));
    }
    
    // ==================== NORMATIVAS ====================
    
    if ($action === 'listar_normativas') {
        $normativas = $normativa_model->obtenerTodos();
        die(json_encode(['success' => true, 'data' => $normativas]));
    }
    
    if ($action === 'guardar_normativa') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (empty($datos['Nombre']) || strlen(trim($datos['Nombre'])) < 2) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'El nombre es obligatorio']));
        }
        
        $id = $normativa_model->guardar($datos);
        die(json_encode(['success' => true, 'id' => $id, 'message' => 'Normativa guardada correctamente']));
    }
    
    if ($action === 'obtener_normativa') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $normativa = $normativa_model->obtenerPorId($id);
        die(json_encode(['success' => true, 'data' => $normativa]));
    }
    
    if ($action === 'actualizar_normativa') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (empty($datos['Id_Normativa'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        if (empty($datos['Nombre']) || strlen(trim($datos['Nombre'])) < 2) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'El nombre es obligatorio']));
        }
        
        $normativa_model->guardar($datos);
        die(json_encode(['success' => true, 'message' => 'Normativa actualizada correctamente']));
    }
    
    if ($action === 'eliminar_normativa') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $normativa_model->eliminar($id);
        die(json_encode(['success' => true, 'message' => 'Normativa eliminada correctamente']));
    }
    
    if ($action === 'reactivar_normativa') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $normativa_model->reactivar($id);
        die(json_encode(['success' => true, 'message' => 'Normativa reactivada correctamente']));
    }
    
    // ==================== LIMITES ====================
    
    if ($action === 'listar_limites') {
        $limites = $limite_model->obtenerTodos();
        die(json_encode(['success' => true, 'data' => $limites]));
    }
    
    if ($action === 'guardar_limite') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (empty($datos['Id_Parametro'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Parametro es obligatorio']));
        }
        
        if (empty($datos['Id_Normativa'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Normativa es obligatoria']));
        }
        
        $id = $limite_model->guardar($datos);
        die(json_encode(['success' => true, 'id' => $id, 'message' => 'Limite guardado correctamente']));
    }
    
    if ($action === 'obtener_limite') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $limite = $limite_model->obtenerPorId($id);
        die(json_encode(['success' => true, 'data' => $limite]));
    }
    
    if ($action === 'actualizar_limite') {
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (empty($datos['Id_Limite_Legal'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        if (empty($datos['Id_Parametro']) || empty($datos['Id_Normativa'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Parametro y Normativa son obligatorios']));
        }
        
        $limite_model->guardar($datos);
        die(json_encode(['success' => true, 'message' => 'Limite actualizado correctamente']));
    }
    
    if ($action === 'eliminar_limite') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $limite_model->eliminar($id);
        die(json_encode(['success' => true, 'message' => 'Limite eliminado correctamente']));
    }
    
    if ($action === 'reactivar_limite') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $limite_model->reactivar($id);
        die(json_encode(['success' => true, 'message' => 'Limite reactivado correctamente']));
    }
    
    if ($action === 'obtener_limites_por_parametro') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'ID requerido']));
        }
        
        $limites = $limite_model->obtenerPorParametro($id);
        die(json_encode(['success' => true, 'data' => $limites]));
    }
    
    // ==================== UNIDADES DE MEDIDA (para dropdown de parametros) ====================
    
    if ($action === 'listar_unidades') {
        $unidades = $parametro_model->obtenerUnidades();
        die(json_encode(['success' => true, 'data' => $unidades]));
    }
    
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Accion no valida']));
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
