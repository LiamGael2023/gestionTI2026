<?php
// Cargamos la conexión global si es necesaria
require_once 'config/db.php'; 
require_once 'modules/inventario/models/InventarioModel.php';


$model = new InventarioModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'activos':
        // IMPORTANTE: Cargar el controlador y el modelo de activos antes de la vista
        require_once 'modules/inventario/models/ActivosModel.php';
        require_once 'modules/inventario/controllers/ActivosController.php';
        include 'modules/inventario/views/activos.php';
        break;
        
    case 'caracteristicas':
        include 'modules/inventario/views/caracteristicas.php';
        break;
    
    // ... resto de tus casos ...

    default:
        include 'modules/inventario/views/index.php';
        break;
}