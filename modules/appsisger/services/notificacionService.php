<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/NotificacionModel.php";
require_once __DIR__ . "/../services/FCMService.php";

$codigoUnico = $_POST['codigoUnico'] ?? $_GET['codigoUnico'] ?? '';

if ($codigoUnico == '') {
    echo json_encode([]);
    exit;
}

try {
    // 1. Buscar pendientes (Estado=0) y enviar push
    $pendientes = NotificacionModel::obtenerPendientesConTokens($codigoUnico);

    foreach ($pendientes as $row) {
        $enviado = FCMService::enviar(
            $row['token'],
            "Orden de riego",
            "Catastral: " . trim($row['AmbOpe_CodigoCatastral'])
        );
        if ($enviado) {
            NotificacionModel::marcarEnviado($row['Id']); // Estado 0 → 1
        }
    }

    // 2. Devolver historial (solo Estado=1, sin duplicados)
    $historial = NotificacionModel::obtenerHistorial($codigoUnico);

    echo json_encode($historial);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>