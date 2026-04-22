<?php

require_once 'modules/laboratorio/models/LaboratorioModel.php';
require_once 'core/Auth.php';

Auth::check();

// Instanciar modelo
$labModel = new LaboratorioModel($conn);

// Obtener datos del usuario logueado
$usuarioData = $labModel->obtenerUsuario($_SESSION['usuario_id']);

// Obtener responsabilidades/submodelos disponibles en laboratorio
$responsabilidades = $labModel->obtenerResponsabilidades($_SESSION['usuario_id']);

// Obtener acción del usuario
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'index':
    default:
        include 'modules/laboratorio/views/index.php';
        break;
    
    case 'equipo':
        include 'modules/laboratorio/equipo/controllers/EquipoController.php';
        break;
    
    case 'reactivo':
        include 'modules/laboratorio/reactivo/controllers/ReactivoController.php';
        break;
    
    case 'parametro':
        include 'modules/laboratorio/parametro/controllers/ParametroController.php';
        break;
    
    case 'servicio':
        include 'modules/laboratorio/servicio/controllers/ServicioController.php';
        break;
    
    case 'venta':
        include 'modules/laboratorio/venta/controllers/VentaController.php';
        break;
    
    case 'muestra':
        include 'modules/laboratorio/muestra/controllers/MuestraController.php';
        break;
    
    case 'residuo':
        include 'modules/laboratorio/residuo/controllers/ResiduoController.php';
        break;

    case 'reportes':
        include 'modules/laboratorio/views/reportes.php';
        break;
}