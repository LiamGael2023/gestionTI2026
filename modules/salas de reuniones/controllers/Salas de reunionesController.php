<?php
require_once 'modules/salas de reuniones/models/Salas de reunionesModel.php';

$model = new Salas de reunionesModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/salas de reuniones/views/index.php';
        break;
}