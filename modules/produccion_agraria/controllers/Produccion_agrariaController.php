<?php
$action = $_GET['action'] ?? 'index';

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
    case 'agregar_stock_masivo':
    case 'obtener_precio_actual':
    case 'guardar_precio':
    case 'ver_imagen_producto':
        include 'modules/produccion_agraria/controllers/InventarioController.php';
        break;
    // Acciones AJAX - PUNTO DE VENTA
    case 'buscar_cliente_api':
    case 'buscar_producto':
    case 'buscar_clientes':
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
    case 'vouchers_report_data':
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