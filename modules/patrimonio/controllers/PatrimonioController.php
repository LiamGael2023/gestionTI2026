<?php
require_once 'modules/patrimonio/models/PatrimonioModel.php';

$model = new PatrimonioModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/patrimonio/views/index.php';
        break;
}