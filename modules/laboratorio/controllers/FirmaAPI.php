<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '256M');

session_start();
require_once '../../../config/db.php';
require_once '../models/LaboratorioModel.php';
require_once '../../../core/Auth.php';

Auth::check();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$usuario_id = intval($_SESSION['usuario_id'] ?? 0);

if ($usuario_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$conn = Conexion::conectar();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$model = new LaboratorioModel($conn);

if ($action === 'obtener_firma') {
    try {
        $firma = $model->obtenerFirmaUsuario($usuario_id);
        echo json_encode([
            'success' => true,
            'tiene_firma' => ($firma !== null && !empty($firma['Img_Firma'])),
            'img_firma' => ($firma['Img_Firma'] ?? null)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'guardar_firma') {
    // Solo usuarios con permiso de firma (Pueden_Firmar en Muestras) o admins
    if (!$model->puedeUsarFirma($usuario_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tiene permiso para gestionar firma digital.']);
        exit;
    }
    try {
        $datos = json_decode(file_get_contents('php://input'), true);
        $img_firma = trim((string)($datos['img_firma'] ?? ''));

        if ($img_firma === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Imagen requerida']);
            exit;
        }

        // Validar que sea una imagen base64 válida (PNG/JPEG)
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/i', $img_firma)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Solo se aceptan imágenes PNG o JPEG']);
            exit;
        }

        // Verificar tamaño (máx 2MB en base64)
        if (strlen($img_firma) > 2 * 1024 * 1024 * 1.4) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La imagen no debe superar 2 MB']);
            exit;
        }

        $model->guardarFirmaUsuario($usuario_id, $img_firma, $usuario_id);
        echo json_encode(['success' => true, 'message' => 'Firma guardada correctamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'eliminar_firma') {
    if (!$model->puedeUsarFirma($usuario_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tiene permiso para gestionar firma digital.']);
        exit;
    }
    try {
        $sql = "UPDATE laboratorio.Usuario_Lab_Firma SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Usuario = ?";
        $stmt = sqlsrv_query($conn, $sql, [$usuario_id]);
        if ($stmt === false) throw new Exception('Error al eliminar firma');
        echo json_encode(['success' => true, 'message' => 'Firma eliminada']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
