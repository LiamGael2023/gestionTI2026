<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/NotificacionModel.php";


$codigoUnico = $_POST['codigoUnico'] ?? '';
$accion      = $_POST['accion']      ?? 'historial';
$id          = $_POST['id']          ?? null;

if ($codigoUnico == '') {
    echo json_encode([]);
    exit;
}

try {
   

  
    $historial = NotificacionModel::obtenerHistorial($codigoUnico);
    echo json_encode($historial);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>