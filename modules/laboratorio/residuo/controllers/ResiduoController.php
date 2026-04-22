<?php
/**
 * ResiduoController.php
 * MVC Controller - Gestiona las acciones del módulo de residuos
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';
require_once __DIR__ . '/../models/ResiduoModel.php';
require_once __DIR__ . '/../models/NormativaSST.php';

Auth::check();

$action = $_GET['subaction'] ?? 'index';
$view = $_GET['view'] ?? 'index';
$conn = Conexion::conectar();

// Determinar la acción a ejecutar
switch ($action) {
    case 'crear_residuo':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            manejarCrearResiduo($conn);
        }
        break;
    
    case 'crear_normativa':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            manejarCrearNormativa($conn);
        }
        break;
    
    case 'crear_informe':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            manejarCrearInforme($conn);
        }
        break;
    
    case 'normativas':
        obtenerNormativas($conn);
        break;
    
    default:
        // Cargar la vista según parámetro
        if ($view === 'informe_residuos') {
            include __DIR__ . '/../views/informe_residuos.php';
        } elseif ($view === 'ver_informe') {
            include __DIR__ . '/../views/ver_informe.php';
        } else {
            include __DIR__ . '/../views/index.php';
        }
        break;
}

// ==================== FUNCIONES ====================

function manejarCrearResiduo($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    $modelo = new ResiduoModel($conn);
    $datos = [
        'Codigo_Item' => $data['Codigo_Item'] ?? null,
        'Nombre_Item' => $data['Nombre_Item'] ?? null,
        'Tipo_Principal' => $data['Tipo_Principal'] ?? null,
        'Subcategoria' => $data['Subcategoria'] ?? null,
        'Unidad_Referencia' => $data['Unidad_Referencia'] ?? null,
        'Usuario_Creacion' => $_SESSION['id_usuario'] ?? 0
    ];
    
    $resultado = $modelo->crearResiduo($datos);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Residuo creado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear residuo']);
    }
    exit;
}

function manejarCrearNormativa($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    $modelo = new NormativaSST($conn);
    $datos = [
        'Nombre_Ley' => $data['Nombre_Ley'] ?? null,
        'Descripcion' => $data['Descripcion'] ?? null,
        'Usuario_Creacion' => $_SESSION['id_usuario'] ?? 0
    ];
    
    $resultado = $modelo->crearNormativa($datos);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Normativa creada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear normativa']);
    }
    exit;
}

function manejarCrearInforme($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    $modelo = new ResiduoModel($conn);
    $datos = [
        'Mes' => $data['Mes'] ?? null,
        'Anio' => $data['Anio'] ?? null,
        'Ubicacion' => $data['Ubicacion'] ?? null,
        'Id_Responsable' => $_SESSION['id_usuario'] ?? 0,
        'Id_Normativa_Aplicable' => $data['Id_Normativa_Aplicable'] ?? null,
        'Usuario_Creacion' => $_SESSION['id_usuario'] ?? 0
    ];
    
    $resultado = $modelo->crearRegistroResiduo($datos);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Informe registrado exitosamente', 'id_registro' => $resultado]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear informe']);
    }
    exit;
}

function obtenerNormativas($conn) {
    $modelo = new NormativaSST($conn);
    $normativas = $modelo->obtenerTodos();
    
    header('Content-Type: application/json');
    echo json_encode($normativas);
    exit;
}

