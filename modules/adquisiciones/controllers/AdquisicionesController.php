<?php
require_once 'modules/adquisiciones/models/AdquisicionesModel.php';

$model = new AdquisicionesModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/adquisiciones/views/index.php';
        break;
}
