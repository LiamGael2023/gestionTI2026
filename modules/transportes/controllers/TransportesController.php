<?php
require_once 'modules/transportes/models/TransportesModel.php';

$model = new TransportesModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/transportes/views/index.php';
        break;
}