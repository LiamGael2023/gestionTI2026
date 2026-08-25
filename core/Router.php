<?php
/**
 * Router - Despachador MVC desacoplado y dinámico para CHAVIsystems
 */
class Router {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dispatch() {
        // 1. Iniciar output buffering
        if (!ob_get_level()) {
            ob_start();
        }

        // 2. Determinar módulo y acción
        $module = 'dashboard';
        $action = 'index';

        if (isset($_GET['route'])) {
            $ruta = rtrim($_GET['route'], '/');
            $partes = explode('/', $ruta);
            $module = $partes[0] ?: 'dashboard';
            if (isset($partes[1])) {
                $action = $partes[1];
            }
        } elseif (isset($_GET['module'])) {
            $module = $_GET['module'];
            if (isset($_GET['action'])) {
                $action = $_GET['action'];
            }
        } elseif (isset($_POST['module'])) {
            $module = $_POST['module'];
            if (isset($_POST['action'])) {
                $action = $_POST['action'];
            }
        }

        // 3. Aliases de autenticación
        if ($module == 'login') { $module = 'auth'; $action = 'login'; }
        if ($module == 'logout') { $module = 'auth'; $action = 'logout'; }
        if ($module == 'autenticar') { $module = 'auth'; $action = 'autenticar'; }

        // Módulo Auth directo
        if ($module == 'auth' && $action == 'login') {
            include 'modules/auth/views/login.php';
            exit();
        }
        if ($module == 'auth' && ($action == 'autenticar' || $action == 'logout')) {
            include 'modules/auth/controllers/AuthController.php';
            exit();
        }

        // 4. Validar sesión
        Auth::check();

        // 5. Conexión global a la base de datos
        $conn = Conexion::conectar();

        // 6. Lista centralizada de acciones AJAX / API
        $acciones_ajax = $this->obtenerAccionesAjax();
        $esAjax = in_array($action, $acciones_ajax) 
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        // 7. Si es render HTML, cargar header
        if (!$esAjax) {
            include 'public/header.php';
        }

        // 8. Despachar al módulo correspondiente
        try {
            $this->despacharModulo($module, $action, $conn, $esAjax);
        } catch (Throwable $e) {
            error_log("[Router Error] " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            if ($esAjax) {
                Response::json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
            } else {
                echo "<div class='container-xl mt-3'><div class='alert alert-danger'><h3 class='alert-title'>Error del Sistema</h3>" . htmlspecialchars($e->getMessage()) . "</div></div>";
            }
        }

        // 9. Si es render HTML, cargar footer
        if (!$esAjax) {
            include 'public/footer.php';
        }
    }

    private function despacharModulo($module, $action, $conn, $esAjax) {
        $modulosEstaticos = ['dashboard', 'usuarios', 'sistemas'];

        if (in_array($module, $modulosEstaticos)) {
            switch ($module) {
                case 'dashboard':
                    include 'modules/dashboard/views/index.php';
                    break;
                case 'sistemas':
                    if ($_SESSION['usuario_rol'] != 'ADMIN') {
                        echo "Acceso Denegado";
                    } else {
                        include 'modules/sistemas/controllers/SistemasController.php';
                    }
                    break;
                case 'usuarios':
                    if ($_SESSION['usuario_rol'] != 'ADMIN') {
                        echo "Acceso Denegado";
                    } else {
                        include 'modules/usuarios/controllers/UsuariosController.php';
                    }
                    break;
            }
            return;
        }

        // Búsqueda dinámica de controlador
        $nombreControlador = ucfirst($module) . "Controller.php";
        $pathFull = "modules/$module/controllers/$nombreControlador";

        if (file_exists($pathFull)) {
            include $pathFull;
            if ($esAjax) {
                exit;
            }
        } else {
            // Fallbacks de módulos simples
            switch ($module) {
                case 'soporte':
                    echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Soporte</div></div></div>';
                    break;
                case 'certificados':
                    echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Certificados</div></div></div>';
                    break;
                case 'adquisiciones':
                    echo '<div class="container-xl"><div class="card"><div class="card-body">Módulo Adquisiciones</div></div></div>';
                    break;
                default:
                    http_response_code(404);
                    echo '<div class="container-xl mt-3">
                            <div class="alert alert-danger">
                                <h3 class="alert-title">Error 404</h3>
                                <div class="text-secondary">El módulo "'.htmlspecialchars($module).'" no tiene un controlador configurado en: <code>'.$pathFull.'</code></div>
                            </div>
                          </div>';
                    break;
            }
        }
    }

    /**
     * Obtiene la lista completa de acciones AJAX reconocidas
     */
    public function obtenerAccionesAjax() {
        return [
            'obtener_clase', 'guardar_clase', 'eliminar_clase',
            'obtener_centro', 'guardar_centro', 'eliminar_centro',
            'obtener_uit', 'guardar_uit', 'eliminar_uit',
            'obtener_cliente', 'guardar_cliente', 'eliminar_cliente', 'listar_clientes',
            'obtener_vinculacion', 'guardar_vinculaciones',
            'obtener_producto', 'guardar_producto', 'eliminar_producto',
            'obtener_lotes', 'obtener_kardex', 'guardar_lote', 'guardar_merma',
            'agregar_stock_masivo',
            'obtener_precio_actual', 'guardar_precio',
            'buscar_producto', 'buscar_clientes', 'buscar_cliente_api', 'guardar_venta', 'crear_cliente_rapido',
            'obtener_proforma', 'procesar_proforma', 'anular_proforma', 'siguiente_correlativo',
            'listar_vouchers', 'guardar_voucher', 'listar_proformas_disponibles', 'asignar_voucher_proformas', 'descargar_voucher',
            'ver_imagen_producto',
            'ventas_data', 'inventario_data', 'mermas_data', 'dashboard_data',
            'vouchers_report_data', 'clientes_report_data', 'consolidado_report_data', 'precios_report_data', 'planilla_data',
            'desasignar_voucher', 'eliminar_voucher', 'actualizar_voucher',
            'dash_load', 'dash_save', 'dash_reset', 'dash_widget',
            ...require __DIR__ . '/ChatActions.php'
        ];
    }
}
