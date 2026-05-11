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

$datos = NotificacionModel::obtenerPendientesConTokens($codigoUnico);

foreach ($datos as $row) {

    FCMService::enviar(
        $row['token'],
        "Orden de riego",
        "Catastral: " . $row['AmbOpe_CodigoCatastral']
    );
}

echo json_encode($datos);