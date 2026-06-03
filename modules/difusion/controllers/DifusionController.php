<?php
require_once 'modules/difusion/models/DifusionModel.php';

$model = new DifusionModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/difusion/views/index.php';
        break;
}