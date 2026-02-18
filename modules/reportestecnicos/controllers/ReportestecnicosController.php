<?php
require_once 'modules/reportestecnicos/models/ReportestecnicosModel.php';

$model = new ReportestecnicosModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/reportestecnicos/views/index.php';
        break;
}