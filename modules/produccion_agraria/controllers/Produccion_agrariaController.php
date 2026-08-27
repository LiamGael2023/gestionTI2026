<?php
$action = $_GET['action'] ?? 'index';

// En peticiones AJAX el router no incluye header.php, por lo que $conn puede
// no existir. Se asegura conexión antes de evaluar permisos.
if (!isset($conn) || !$conn) {
    require_once 'config/db.php';
    $conn = Conexion::conectar();
}

// ── Control de permisos por rol de Producción Agraria ─────────────
// Mapa: acción (vista) -> url del submódulo registrado en submodulo_pa
$mapa_submodulo_pa = [
    'inventario'  => '?module=produccion_agraria&action=inventario',
    'punto_venta' => '?module=produccion_agraria&action=punto_venta',
    'bandeja'     => '?module=produccion_agraria&action=bandeja',
    'tablas'      => '?module=produccion_agraria&action=tablas',
    'reportes'    => '?module=produccion_agraria&action=reportes',
    'dashboard'   => '?module=produccion_agraria&action=dashboard',
    'consultas'   => '?module=produccion_agraria&action=consultas',
    // Acciones AJAX de cada submódulo (la vista del permiso "ver" da acceso)
    'obtener_clase' => '?module=produccion_agraria&action=tablas', 'guardar_clase' => '?module=produccion_agraria&action=tablas', 'eliminar_clase' => '?module=produccion_agraria&action=tablas',
    'obtener_centro' => '?module=produccion_agraria&action=tablas', 'guardar_centro' => '?module=produccion_agraria&action=tablas', 'eliminar_centro' => '?module=produccion_agraria&action=tablas',
    'obtener_ui' => '?module=produccion_agraria&action=tablas', 'obtener_uit' => '?module=produccion_agraria&action=tablas', 'guardar_uit' => '?module=produccion_agraria&action=tablas', 'eliminar_uit' => '?module=produccion_agraria&action=tablas',
    'obtener_cliente' => '?module=produccion_agraria&action=tablas', 'guardar_cliente' => '?module=produccion_agraria&action=tablas', 'eliminar_cliente' => '?module=produccion_agraria&action=tablas', 'listar_clientes' => '?module=produccion_agraria&action=tablas',
    'obtener_vinculacion' => '?module=produccion_agraria&action=tablas', 'guardar_vinculaciones' => '?module=produccion_agraria&action=tablas',
    'obtener_producto' => '?module=produccion_agraria&action=inventario', 'guardar_producto' => '?module=produccion_agraria&action=inventario', 'eliminar_producto' => '?module=produccion_agraria&action=inventario',
    'obtener_lotes' => '?module=produccion_agraria&action=inventario', 'obtener_kardex' => '?module=produccion_agraria&action=inventario', 'guardar_lote' => '?module=produccion_agraria&action=inventario', 'guardar_merma' => '?module=produccion_agraria&action=inventario',
    'obtener_precio_actual' => '?module=produccion_agraria&action=inventario', 'guardar_precio' => '?module=produccion_agraria&action=inventario', 'ver_imagen_producto' => '?module=produccion_agraria&action=inventario',
    'buscar_cliente_api' => '?module=produccion_agraria&action=punto_venta', 'guardar_venta' => '?module=produccion_agraria&action=punto_venta', 'crear_cliente_rapido' => '?module=produccion_agraria&action=punto_venta',
    'obtener_proforma' => '?module=produccion_agraria&action=bandeja', 'procesar_proforma' => '?module=produccion_agraria&action=bandeja', 'anular_proforma' => '?module=produccion_agraria&action=bandeja', 'siguiente_correlativo' => '?module=produccion_agraria&action=bandeja',
    'listar_vouchers' => '?module=produccion_agraria&action=bandeja', 'guardar_voucher' => '?module=produccion_agraria&action=bandeja', 'descargar_voucher' => '?module=produccion_agraria&action=bandeja', 'listar_proformas_disponibles' => '?module=produccion_agraria&action=bandeja',
    'asignar_voucher_proformas' => '?module=produccion_agraria&action=bandeja', 'desasignar_voucher' => '?module=produccion_agraria&action=bandeja', 'eliminar_voucher' => '?module=produccion_agraria&action=bandeja', 'actualizar_voucher' => '?module=produccion_agraria&action=bandeja',
    'ventas_data' => '?module=produccion_agraria&action=reportes', 'inventario_data' => '?module=produccion_agraria&action=reportes', 'mermas_data' => '?module=produccion_agraria&action=reportes', 'clientes_report_data' => '?module=produccion_agraria&action=reportes',
    'consolidado_report_data' => '?module=produccion_agraria&action=reportes', 'precios_report_data' => '?module=produccion_agraria&action=reportes', 'planilla_data' => '?module=produccion_agraria&action=reportes', 'dashboard_data' => '?module=produccion_agraria&action=reportes',
    'dash_load' => '?module=produccion_agraria&action=dashboard', 'dash_save' => '?module=produccion_agraria&action=dashboard', 'dash_reset' => '?module=produccion_agraria&action=dashboard', 'dash_widget' => '?module=produccion_agraria&action=dashboard',
    'chat_enviar' => '?module=produccion_agraria&action=consultas',
    'tool_stock' => '?module=produccion_agraria&action=consultas', 'tool_ventas' => '?module=produccion_agraria&action=consultas', 'tool_proformas' => '?module=produccion_agraria&action=consultas', 'tool_vouchers' => '?module=produccion_agraria&action=consultas',
    'tool_productos' => '?module=produccion_agraria&action=consultas', 'tool_clientes' => '?module=produccion_agraria&action=consultas', 'tool_mermas' => '?module=produccion_agraria&action=consultas', 'tool_kardex' => '?module=produccion_agraria&action=consultas',
    'tool_top_productos' => '?module=produccion_agraria&action=consultas', 'tool_valorizacion' => '?module=produccion_agraria&action=consultas', 'tool_ventas_mes' => '?module=produccion_agraria&action=consultas', 'tool_vouchers_saldo' => '?module=produccion_agraria&action=consultas',
    'tool_grafico' => '?module=produccion_agraria&action=consultas', 'tool_resumen' => '?module=produccion_agraria&action=consultas', 'tool_comparativa' => '?module=produccion_agraria&action=consultas', 'tool_buscar' => '?module=produccion_agraria&action=consultas',
    'tool_recomendaciones' => '?module=produccion_agraria&action=consultas', 'tool_metricas' => '?module=produccion_agraria&action=consultas', 'tool_detalle_producto' => '?module=produccion_agraria&action=consultas',
];
if (isset($mapa_submodulo_pa[$action])) {
    // El guard de permisos es "best effort": si falla por cualquier motivo
    // (tablas de roles ausentes, error de conexión, etc.) NO debe bloquear
    // las operaciones de datos. Fallback: acceso permitido.
    $permisosPA = null;
    try {
        require_once 'modules/produccion_agraria/models/PermisosModel.php';
        $permisosPAModel = new PermisosModel($conn);
        $permisosPA      = $permisosPAModel->obtenerPermisosSubmodulo($_SESSION['usuario_id'], $mapa_submodulo_pa[$action]);
    } catch (Throwable $e) {
        error_log('[Produccion_agrariaController] Permisos no disponibles: ' . $e->getMessage());
        $permisosPA = null;
    }
    // Retrocompatibilidad: si el usuario NO tiene rol de PA asignado (o el
    // sistema de permisos no está disponible), mantiene acceso actual.
    // Si tiene rol, se respeta la matriz de permisos (ver).
    if ($permisosPA !== null && !$permisosPA['ver']) {
        // Acciones AJAX (json): denegar con 403 JSON. Vistas: mostrar sin_acceso.
        $acciones_json_pa = [
            'obtener_clase','guardar_clase','eliminar_clase','obtener_centro','guardar_centro','eliminar_centro',
            'obtener_uit','guardar_uit','eliminar_uit','obtener_cliente','guardar_cliente','eliminar_cliente','listar_clientes',
            'obtener_vinculacion','guardar_vinculaciones','obtener_producto','guardar_producto','eliminar_producto',
            'obtener_lotes','obtener_kardex','guardar_lote','guardar_merma','obtener_precio_actual','guardar_precio','ver_imagen_producto',
            'buscar_cliente_api','guardar_venta','crear_cliente_rapido',
            'obtener_proforma','procesar_proforma','anular_proforma','siguiente_correlativo',
            'listar_vouchers','guardar_voucher','descargar_voucher','listar_proformas_disponibles','asignar_voucher_proformas',
            'desasignar_voucher','eliminar_voucher','actualizar_voucher',
            'ventas_data','inventario_data','mermas_data','clientes_report_data','consolidado_report_data','precios_report_data','planilla_data','dashboard_data',
            'dash_load','dash_save','dash_reset','dash_widget',
            'chat_enviar','tool_stock','tool_ventas','tool_proformas','tool_vouchers','tool_productos','tool_clientes','tool_mermas',
            'tool_kardex','tool_top_productos','tool_valorizacion','tool_ventas_mes','tool_vouchers_saldo','tool_grafico','tool_resumen',
            'tool_comparativa','tool_buscar','tool_recomendaciones','tool_metricas','tool_detalle_producto',
        ];
        if (in_array($action, $acciones_json_pa)) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado: no tiene permiso para este submódulo.']);
            exit;
        } else {
            // Vista sin acceso
            http_response_code(403);
            include 'modules/produccion_agraria/views/sin_acceso.php';
            exit;
        }
    }
}

// Solo cargar modelo si no es acción AJAX de bandeja
$acciones_ajax_bandeja = [
    'obtener_proforma', 'procesar_proforma', 'anular_proforma', 'siguiente_correlativo',
    'listar_vouchers', 'guardar_voucher', 'descargar_voucher', 'listar_proformas_disponibles', 'asignar_voucher_proformas',
    'desasignar_voucher', 'eliminar_voucher', 'actualizar_voucher',
    ...require 'core/ChatActions.php'
];
if (!in_array($action, $acciones_ajax_bandeja)) {
    require_once 'modules/produccion_agraria/models/Produccion_agrariaModel.php';
    $model = new Produccion_agrariaModel($conn);
}

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
    // Acciones AJAX del submódulo PERMISOS (roles de producción)
    case 'permisos':
    case 'listar_roles_pa':
    case 'listar_usuarios_pa':
    case 'permisos_rol_pa':
    case 'asignar_rol_pa':
    case 'crear_rol_pa':
    case 'eliminar_rol_pa':
    case 'guardar_permisos_pa':
        include 'modules/produccion_agraria/controllers/PermisosController.php';
        break;
    // Acciones AJAX del submódulo tablas - CLASE
    case 'obtener_clase':
    case 'guardar_clase':
    case 'eliminar_clase':
    // Acciones AJAX - CENTRO_PRODUCCION
    case 'obtener_centro':
    case 'guardar_centro':
    case 'eliminar_centro':
    // Acciones AJAX - VINCULACION
    case 'obtener_vinculacion':
    case 'guardar_vinculaciones':
    // Acciones AJAX - UIT
    case 'obtener_uit':
    case 'guardar_uit':
    case 'eliminar_uit':
    // Acciones AJAX - CLIENTE
    case 'obtener_cliente':
    case 'guardar_cliente':
    case 'eliminar_cliente':
    case 'listar_clientes':
        include 'modules/produccion_agraria/controllers/TablasController.php';
        break;
    // Acciones AJAX - INVENTARIO/PRODUCTO
    case 'obtener_producto':
    case 'guardar_producto':
    case 'eliminar_producto':
    case 'obtener_lotes':
    case 'obtener_kardex':
    case 'guardar_lote':
    case 'guardar_merma':
    case 'obtener_precio_actual':
    case 'guardar_precio':
    case 'ver_imagen_producto':
        include 'modules/produccion_agraria/controllers/InventarioController.php';
        break;
    // Acciones AJAX - PUNTO DE VENTA
    case 'buscar_cliente_api':
    case 'guardar_venta':
    case 'crear_cliente_rapido':
        include 'modules/produccion_agraria/controllers/PuntoVentaController.php';
        break;
    // Acciones AJAX - BANDEJA DE PROFORMAS
    case 'obtener_proforma':
    case 'procesar_proforma':
    case 'anular_proforma':
    case 'siguiente_correlativo':
    case 'listar_vouchers':
    case 'guardar_voucher':
    case 'descargar_voucher':
    case 'listar_proformas_disponibles':
    case 'asignar_voucher_proformas':
    case 'desasignar_voucher':
    case 'eliminar_voucher':
    case 'actualizar_voucher':
        include 'modules/produccion_agraria/controllers/BandejaController.php';
        break;
    case 'reportes':
        include 'modules/produccion_agraria/controllers/ReportesController.php';
        break;
    // Acciones AJAX - REPORTES
    case 'ventas_data':
    case 'inventario_data':
    case 'mermas_data':
    case 'clientes_report_data':
    case 'consolidado_report_data':
    case 'precios_report_data':
    case 'planilla_data':
    case 'dashboard_data':
        include 'modules/produccion_agraria/controllers/ReportesController.php';
        break;
    case 'dashboard':
    case 'dash_load':
    case 'dash_save':
    case 'dash_reset':
    case 'dash_widget':
        include 'modules/produccion_agraria/controllers/DashboardController.php';
        break;
    case 'consultas':
    case 'chat_enviar':
    case 'tool_stock':
    case 'tool_ventas':
    case 'tool_proformas':
    case 'tool_vouchers':
    case 'tool_productos':
    case 'tool_clientes':
    case 'tool_mermas':
    case 'tool_kardex':
    case 'tool_top_productos':
    case 'tool_valorizacion':
    case 'tool_ventas_mes':
    case 'tool_vouchers_saldo':
    case 'tool_grafico':
    case 'tool_resumen':
    case 'tool_comparativa':
    case 'tool_buscar':
    case 'tool_recomendaciones':
    case 'tool_metricas':
    case 'tool_detalle_producto':
        include 'modules/consultas/controllers/ConsultasController.php';
        break;
    case 'guardar':
        break;
    default:
        include 'modules/produccion_agraria/views/index.php';
        break;
}