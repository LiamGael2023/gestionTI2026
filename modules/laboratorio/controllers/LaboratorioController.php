<?php

require_once 'modules/laboratorio/models/LaboratorioModel.php';
require_once 'core/Auth.php';

Auth::check();

// Instanciar modelo
$labModel = new LaboratorioModel($conn);

// Obtener datos del usuario logueado
$usuarioData = $labModel->obtenerUsuario($_SESSION['usuario_id']);

// Verificar si es administrador (accede a gestión de roles)
$esAdmin = $labModel->esAdministrador($_SESSION['usuario_id']);

// Obtener firma digital del usuario
$firmaActual = $labModel->obtenerFirmaUsuario($_SESSION['usuario_id']);

// Verificar si el usuario puede subir/ver firma digital (requiere permiso firmar en Muestras)
$puedeSubirFirma = $labModel->puedeUsarFirma($_SESSION['usuario_id']);

// Obtener responsabilidades/submodelos disponibles en laboratorio
$responsabilidades = $labModel->obtenerResponsabilidades($_SESSION['usuario_id']);

// Obtener acción del usuario
$action = $_GET['action'] ?? 'index';

// ── Permisos por submódulo (según rol de laboratorio) ──────────────────
// Para admins comun: todos los permisos habilitados.
// Para usuarios con rol lab: permisos de laboratorio.Permiso_Rol.
// Para usuarios sin rol lab accediendo a un submódulo: se bloquea acceso.
$_mapaUrlSubmodulo = [
    'equipo'    => '?module=laboratorio&action=equipo',
    'reactivo'  => '?module=laboratorio&action=reactivo',
    'parametro' => '?module=laboratorio&action=parametro',
    'servicio'  => '?module=laboratorio&action=servicio',
    'venta'     => '?module=laboratorio&action=venta',
    'muestra'   => '?module=laboratorio&action=muestra',
    'residuo'   => '?module=laboratorio&action=residuo',
    'proveedor' => '?module=laboratorio&action=proveedor',
    'reportes'  => '?module=laboratorio&action=reportes',
    'pozos'     => '?module=laboratorio&action=pozos',
];

if (isset($_mapaUrlSubmodulo[$action])) {
    $permisos = $labModel->obtenerPermisosSubmodulo($_SESSION['usuario_id'], $_mapaUrlSubmodulo[$action]);
    if ($permisos === null) {
        // Sin rol asignado en este submódulo → negar acceso
        $permisos = ['ver' => false, 'crear' => false, 'editar' => false,
                     'eliminar' => false, 'exportar' => false, 'firmar' => false];
    }
} else {
    $permisos = null; // index, roles: no aplica restricción por submódulo
}

switch($action) {
    case 'index':
    default:
        include 'modules/laboratorio/views/index.php';
        break;

    case 'roles_api':
        include 'modules/laboratorio/controllers/RolesAPI.php';
        break;

    case 'roles':
        if (!$esAdmin) {
            http_response_code(403);
            include 'modules/laboratorio/views/index.php';
            break;
        }
        include 'modules/laboratorio/controllers/RolesController.php';
        break;
    
    case 'equipo':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/equipo/controllers/EquipoController.php';
        break;
    
    case 'reactivo':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/reactivo/controllers/ReactivoController.php';
        break;
    
    case 'parametro':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/parametro/controllers/ParametroController.php';
        break;
    
    case 'servicio':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/servicio/controllers/ServicioController.php';
        break;
    
    case 'venta':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/venta/controllers/VentaController.php';
        break;
    
    case 'muestra':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/muestra/controllers/MuestraController.php';
        break;
    
    case 'residuo':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/residuo/controllers/ResiduoController.php';
        break;

    case 'proveedor':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/proveedor/controllers/ProveedorController.php';
        break;

    case 'reportes':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/views/reportes.php';
        break;

    case 'pozos':
        if (!$permisos['ver']) { include 'modules/laboratorio/views/sin_acceso.php'; break; }
        include 'modules/laboratorio/pozos/controllers/PozoController.php';
        break;
}
