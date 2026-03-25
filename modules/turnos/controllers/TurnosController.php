<?php
require_once 'modules/turnos/models/TurnosModel.php';

$model = new TurnosModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/turnos/views/index.php';
        break;
}