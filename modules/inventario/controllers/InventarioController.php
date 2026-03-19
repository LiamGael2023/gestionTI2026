<?php
require_once 'config/db.php';
require_once 'modules/inventario/models/InventarioModel.php';

$model  = new InventarioModel($conn);
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

    case 'ubicaciones':
        require_once 'modules/inventario/models/UbicacionModel.php';
        require_once 'modules/inventario/models/AmbienteModel.php';
        require_once 'modules/inventario/controllers/UbicacionController.php';
        require_once 'modules/inventario/controllers/AmbienteController.php';
        include 'modules/inventario/views/ubicacion.php';
        break;

    case 'ips':
        require_once 'modules/inventario/models/IpModel.php';
        require_once 'modules/inventario/controllers/IpController.php';
        include 'modules/inventario/views/ips.php';
        break;

    /* ── ESTACIONES ── */
    case 'estaciones':
        require_once 'modules/inventario/models/EstacionModel.php';
        require_once 'modules/inventario/controllers/EstacionController.php';
        include 'modules/inventario/views/estaciones.php';
        break;

    case 'agregarEstacion':
        require_once 'modules/inventario/models/EstacionModel.php';
        require_once 'modules/inventario/controllers/EstacionController.php';
        include 'modules/inventario/views/estacion_agregar.php';
        break;

    case 'editarEstacion':
        require_once 'modules/inventario/models/EstacionModel.php';
        require_once 'modules/inventario/controllers/EstacionController.php';
        include 'modules/inventario/views/estacion_editar.php';
        break;

    case 'asignaciones':
        require_once 'modules/inventario/models/AsignacionModel.php';
        require_once 'modules/inventario/controllers/AsignacionController.php';
        include 'modules/inventario/views/asignaciones.php';
        break;

    default:
        include 'modules/inventario/views/index.php';
        break;
}
