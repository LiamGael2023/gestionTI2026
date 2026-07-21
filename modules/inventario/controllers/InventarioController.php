<?php
require_once 'modules/inventario/models/InventarioModel.php';

$model = new InventarioModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/inventario/views/index.php';
        break;
}
