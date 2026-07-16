<?php
// 1. Iniciar output buffering inmediatamente para evitar envío prematuro de headers
ob_start();

// 1. Configuración y Errores (deshabilitado en producción)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'core/Auth.php';

// =================================================================================
// 2. ROUTER HÍBRIDO
// =================================================================================

$module = 'dashboard'; 
$action = 'index';    

if (isset($_GET['route'])) {
    $ruta = rtrim($_GET['route'], '/');
    $partes = explode('/', $ruta);
    $module = $partes[0];
    if (isset($partes[1])) {
        $action = $partes[1];
    }
} 
elseif (isset($_GET['module'])) {
    $module = $_GET['module'];
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
    }
}

// =================================================================================
// 3. CONTROLADOR DE ACCESO
// =================================================================================

if ($module == 'login') { $module = 'auth'; $action = 'login'; }
if ($module == 'logout') { $module = 'auth'; $action = 'logout'; }
if ($module == 'autenticar') { $module = 'auth'; $action = 'autenticar'; }

if ($module == 'auth' && $action == 'login') {
    include 'modules/auth/views/login.php';
    exit();
}

if ($module == 'auth' && ($action == 'autenticar' || $action == 'logout')) {
    include 'modules/auth/controllers/AuthController.php';
    exit();
}

// =================================================================================
// 4. SISTEMA PRINCIPAL (Requiere estar logueado)
// =================================================================================

Auth::check(); 

// Detectar si es petición AJAX (acciones que retornan JSON)
$acciones_ajax = [
    'obtener_clase', 'guardar_clase', 'eliminar_clase',
    'obtener_centro', 'guardar_centro', 'eliminar_centro',
    'obtener_uit', 'guardar_uit', 'eliminar_uit',
    'obtener_cliente', 'guardar_cliente', 'eliminar_cliente',
    'obtener_vinculacion', 'guardar_vinculaciones',
    'obtener_producto', 'guardar_producto', 'eliminar_producto',
    'obtener_lotes', 'obtener_kardex', 'guardar_lote', 'guardar_merma',
    'agregar_stock_masivo',
    'obtener_precio_actual', 'guardar_precio',
    'buscar_producto', 'buscar_clientes', 'guardar_venta', 'crear_cliente_rapido',
    'obtener_proforma', 'procesar_proforma', 'anular_proforma', 'siguiente_correlativo',
    'listar_vouchers', 'guardar_voucher', 'listar_proformas_disponibles', 'asignar_voucher_proformas', 'descargar_voucher',
    'ver_imagen_producto',
    // Acciones AJAX - REPORTES
    'ventas_data', 'inventario_data', 'mermas_data', 'dashboard_data',
    'vouchers_report_data', 'clientes_report_data', 'consolidado_report_data', 'precios_report_data', 'planilla_data',
    // Acciones AJAX - VOUCHERS
    'desasignar_voucher', 'eliminar_voucher', 'actualizar_voucher',
    // Acciones AJAX - DASHBOARD CMS
    'dash_load', 'dash_save', 'dash_reset', 'dash_widget',
    // Acciones AJAX - CHATBOT (desde archivo centralizado core/ChatActions.php)
    ...require 'core/ChatActions.php'
];
$es_ajax = in_array($action, $acciones_ajax);

// DEBUG: Log para verificar detección AJAX
error_log("[Router] module=$module, action=$action, es_ajax=" . ($es_ajax ? 'true' : 'false'));

if (!$es_ajax) {
    include 'public/header.php'; 
}

// Módulos que siempre deben estar presentes o tienen lógica manual
$modulos_estaticos = ['dashboard', 'usuarios', 'sistemas'];

if (in_array($module, $modulos_estaticos)) {
    // Lógica para módulos base
    switch ($module) {
        case 'dashboard':
            include 'modules/dashboard/views/index.php';
            break;
        case 'sistemas':
            if($_SESSION['usuario_rol'] != 'ADMIN'){ echo "Acceso Denegado"; }
            else { include 'modules/sistemas/controllers/SistemasController.php'; }
            break;
        case 'usuarios':
            if($_SESSION['usuario_rol'] != 'ADMIN'){ echo "Acceso Denegado"; }
            else { include 'modules/usuarios/controllers/UsuariosController.php'; }
            break;
    }
} else {
    // --- LÓGICA DINÁMICA PARA MÓDULOS GENERADOS ---
    // Construimos la ruta: modules/nombre/controllers/NombreController.php
    $nombreControlador = ucfirst($module) . "Controller.php";
    $pathFull = "modules/$module/controllers/$nombreControlador";

    if (file_exists($pathFull)) {
        include $pathFull;
        // Si era AJAX, terminar aquí para no incluir footer
        if ($es_ajax) {
            error_log("[Router] AJAX detected, terminating before footer");
            exit;
        }
    } else {
        // Fallback para los módulos que aún son solo un "echo" (Soporte, Certificados, etc.)
        switch ($module) {
            case 'soporte':
                echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Soporte (José)</div></div></div>';
                break;
            case 'certificados':
                echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Certificados (Franklin)</div></div></div>';
                break;
            case 'adquisiciones':
                echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Adquisiciones (Cristian)</div></div></div>';
                break;
            default:
                echo '<div class="container-xl">
                        <div class="alert alert-danger">
                            <h3 class="alert-title">Error 404</h3>
                            <div class="text-secondary">El sistema "'.htmlspecialchars($module).'" no tiene un controlador configurado en: <code>'.$pathFull.'</code></div>
                        </div>
                      </div>';
                break;
        }
    }
}

include 'public/footer.php';
?>