<?php
require_once 'modules/inventario/models/InventarioModel.php';

$model = new InventarioModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'activos':
        include 'modules/inventario/views/activos.php';
        break;
    case 'caracteristicas':
        include 'modules/inventario/views/caracteristicas.php';
        break;
    case 'tipoCaracteristicas':
        include 'modules/inventario/views/tipoCaracteristicas.php';
        break;
    case 'ubicaciones':
        include 'modules/inventario/views/ubicaciones.php';
        break;
    case 'equipos':
        include 'modules/inventario/views/equipos.php';
        break;
    default:
        include 'modules/inventario/views/index.php';
        break;

        // kkk
}