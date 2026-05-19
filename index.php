<?php
// 1. Configuración y Errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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

// Evita contaminar respuestas JSON/PDF de adquisiciones con el layout global.
if ($module === 'adquisiciones' && preg_match('/Ajax$/', (string) $action)) {
    include 'modules/adquisiciones/controllers/AdquisicionesController.php';
    exit();
}

include 'public/header.php'; 

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
    } else {
        // Fallback para los módulos que aún son solo un "echo" (Soporte, Certificados, etc.)
        switch ($module) {
            case 'soporte':
                echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Soporte (José)</div></div></div>';
                break;
            case 'certificados':
                echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Certificados (Franklin)</div></div></div>';
                break;
            case 'inventario':
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