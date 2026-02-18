<?php
require_once 'modules/salas/models/SalasModel.php';

$model = new SalasModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/salas/views/index.php';
        break;
}