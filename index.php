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
$subaction = null;

if (isset($_GET['route'])) {
    $ruta = rtrim($_GET['route'], '/');
    $partes = explode('/', $ruta);
    $module = $partes[0];
    if (isset($partes[1])) {
        $action = $partes[1];
    }
    if (isset($partes[2])) {
        $subaction = $partes[2];
    }
} 
elseif (isset($_GET['module'])) {
    $module = $_GET['module'];
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
    }
    if (isset($_GET['subaction'])) {
        $subaction = $_GET['subaction'];
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

// ── TEST API (borrar despues) ──
if ($action === 'test_api_directo') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    $doc = $_GET['documento'] ?? '75720362';
    echo "<h2>Test API Directo</h2>";
    echo "curl: " . (function_exists('curl_init')?'SI':'NO') . "<br>";
    echo "fopen: " . ini_get('allow_url_fopen') . "<br>";
    echo "PHP: " . phpversion() . "<br>";
    echo "doc: " . htmlspecialchars($doc) . "<hr>";
    
    $start = microtime(true);
    $ch = curl_init("https://api.apis.net.pe/v1/dni?numero=$doc");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>0]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $elapsed = round((microtime(true) - $start) * 1000);
    
    echo "<b>RENIEC:</b> HTTP=$code Error=" . ($err?:'OK') . " Time={$elapsed}ms<br>";
    $json = json_decode($resp, true);
    echo "nombre: " . htmlspecialchars($json['nombre'] ?? 'NO') . "<br>";
    echo "<pre>" . htmlspecialchars(substr($resp?:'(vacio)', 0, 800)) . "</pre><hr>";
    
    $start2 = microtime(true);
    $ch2 = curl_init("https://www.chavimochic.gob.pe/api_incidencias/api_personal.php?documento=$doc");
    curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>0]);
    $resp2 = curl_exec($ch2);
    $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $err2 = curl_error($ch2);
    curl_close($ch2);
    $elapsed2 = round((microtime(true) - $start2) * 1000);
    
    echo "<b>Personal PECH:</b> HTTP=$code2 Error=" . ($err2?:'OK') . " Time={$elapsed2}ms<br>";
    $json2 = json_decode($resp2, true);
    echo "empleado: " . (($json2 && !empty($json2['data'])) ? 'SI' : 'NO') . "<br>";
    echo "<pre>" . htmlspecialchars(substr($resp2?:'(vacio)', 0, 800)) . "</pre><hr>";
    
    echo "TOTAL: " . ($elapsed + $elapsed2) . "ms";
    exit;
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
    'obtener_cliente', 'guardar_cliente', 'eliminar_cliente', 'listar_clientes',
    'obtener_vinculacion', 'guardar_vinculaciones',
    'obtener_producto', 'guardar_producto', 'eliminar_producto',
    'obtener_lotes', 'obtener_kardex', 'guardar_lote', 'guardar_merma',
    'obtener_precio_actual', 'guardar_precio',
    'buscar_cliente_api', 'guardar_venta', 'crear_cliente_rapido',
    'obtener_proforma', 'procesar_proforma', 'anular_proforma', 'siguiente_correlativo',
    'listar_vouchers', 'guardar_voucher', 'listar_proformas_disponibles', 'asignar_voucher_proformas', 'descargar_voucher',
    'ver_imagen_producto',
    // Acciones AJAX - REPORTES
    'ventas_data', 'inventario_data', 'mermas_data', 'dashboard_data',
    'clientes_report_data', 'consolidado_report_data', 'precios_report_data', 'planilla_data',
    // Acciones AJAX - VOUCHERS
    'desasignar_voucher', 'eliminar_voucher', 'actualizar_voucher',
    // Acciones AJAX - DASHBOARD CMS
    'dash_load', 'dash_save', 'dash_reset', 'dash_widget',
    // Acciones AJAX - PERMISOS (roles producción agraria)
    'listar_roles_pa', 'listar_usuarios_pa', 'permisos_rol_pa', 'asignar_rol_pa', 'crear_rol_pa', 'eliminar_rol_pa', 'guardar_permisos_pa',
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
