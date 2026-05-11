<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/NotificacionModel.php";
require_once __DIR__ . "/../services/FCMService.php";

$codigoUnico = $_POST['codigoUnico'] ?? $_GET['codigoUnico'] ?? '';

if ($codigoUnico == '') {
    echo json_encode([]);
    exit;
}

// 1. Enviar notificaciones pendientes y marcarlas
$pendientes = NotificacionModel::obtenerPendientesConTokens($codigoUnico);

foreach ($pendientes as $row) {
    FCMService::enviar(
        $row['token'],
        "Orden de riego",
        "Catastral: " . trim($row['AmbOpe_CodigoCatastral'])
    );
    NotificacionModel::marcarEnviado($row['Id']); 
}


$todos = NotificacionModel::obtenerTodos($codigoUnico);

echo json_encode($todos);