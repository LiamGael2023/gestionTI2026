<?php
require_once 'modules/vigilantes/models/VigilantesModel.php';

$model = new VigilantesModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/vigilantes/views/index.php';
        break;
}