<?php
require_once 'modules/adquisición de equipos/models/Adquisición de equiposModel.php';

$model = new Adquisición de equiposModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/adquisición de equipos/views/index.php';
        break;
}