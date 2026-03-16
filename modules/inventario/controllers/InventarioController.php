<?php
// Cargamos la conexión global si es necesaria
require_once 'config/db.php';
require_once 'modules/inventario/models/InventarioModel.php';


$model = new InventarioModel($conn);
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'activos':
        require_once 'modules/inventario/models/ActivosModel.php';
        require_once 'modules/inventario/controllers/ActivosController.php';
        include 'modules/inventario/views/activos.php';
        break;

    case 'tipoCaracteristicas':
        require_once 'modules/inventario/models/tipoCaracteristicasModel.php';
        require_once 'modules/inventario/controllers/tipoCaracteristicasController.php';
        include 'modules/inventario/views/tipoCaracteristicas.php';
        break;

    case 'caracteristicas':
        require_once 'modules/inventario/models/CaracteristicasModel.php';
        require_once 'modules/inventario/controllers/CaracteristicasController.php';
        include 'modules/inventario/views/caracteristicas.php';
        break;

    case 'equipos':
        require_once 'modules/inventario/models/EquipoModel.php';
        require_once 'modules/inventario/controllers/EquipoController.php';
        include 'modules/inventario/views/equipos.php';
        break;


    default:
        include 'modules/inventario/views/index.php';
        break;
}
