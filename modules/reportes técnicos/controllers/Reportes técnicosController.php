<?php
require_once 'modules/reportes técnicos/models/Reportes técnicosModel.php';

$model = new Reportes técnicosModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/reportes técnicos/views/index.php';
        break;
}