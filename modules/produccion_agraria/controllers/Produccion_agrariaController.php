<?php
require_once 'modules/produccion_agraria/models/Produccion_agrariaModel.php';

$model = new Produccion_agrariaModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'inventario':
        include 'modules/produccion_agraria/controllers/InventarioController.php';
        break;
    case 'punto_venta':
        include 'modules/produccion_agraria/controllers/PuntoVentaController.php';
        break;
    case 'bandeja':
        include 'modules/produccion_agraria/controllers/BandejaController.php';
        break;
    case 'tablas':
        include 'modules/produccion_agraria/controllers/TablasController.php';
        break;
    case 'reportes':
        include 'modules/produccion_agraria/controllers/ReportesController.php';
        break;
    case 'dashboard':
        include 'modules/produccion_agraria/controllers/DashboardController.php';
        break;
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/produccion_agraria/views/index.php';
        break;
}
?>