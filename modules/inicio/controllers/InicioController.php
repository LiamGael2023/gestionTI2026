<?php
require_once 'modules/inicio/models/InicioModel.php';

$model = new InicioModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/inicio/views/index.php';
        break;
}