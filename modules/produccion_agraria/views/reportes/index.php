<?php
// Colores para métodos de pago (consistentes entre chart y tabla)
$colores_metodo = [
    'EFECTIVO'     => '#2fb344',
    'TRANSFERENCIA'=> '#4299e1',
    'YAPE'         => '#7c3aed',
    'PLIN'         => '#06b6d4',
    'TARJETA'      => '#f59e0b',
    'DEPOSITO'     => '#ef4444',
    'VOUCHER'      => '#8b5cf6',
];

// Preparar series para ApexCharts (ventas por mes)
$meses_labels  = array_column($ventas_mes_init, 'mes_label');
$meses_montos  = array_column($ventas_mes_init, 'monto_total');
$meses_counts  = array_column($ventas_mes_init, 'total_transacciones');

// Preparar series para dona (métodos de pago)
$metodos_labels = array_column($metodos_pago_init, 'metodo_pago');
$metodos_montos = array_column($metodos_pago_init, 'monto_total');

// Preparar series para inventario por clase
$clases_labels = array_column($inventario_clase_init, 'nombre_clase');
$clases_valores= array_column($inventario_clase_init, 'valor_total');

// Totales para valorización
$total_stock_valor = array_sum(array_column($inventario_init, 'valor_total_lote'));
$total_mermas_valor = array_sum(array_column($mermas_init, 'valor_perdida'));
?>

<!-- CDNs -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<!-- Estilos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">

<style>
.reporte-header { background: linear-gradient(135deg, #004d99 0%, #0070cc 100%); }
.tab-btn-reporte { border-radius: 8px 8px 0 0; font-weight: 600; }
.kpi-card { border-left: 4px solid; transition: transform .2s; }
.kpi-card:hover { transform: translateY(-2px); }
.kpi-ventas  { border-color: #2fb344; }
.kpi-count   { border-color: #4299e1; }
.kpi-ticket  { border-color: #f59e0b; }
.kpi-stock   { border-color: #7c3aed; }
.kpi-mermas  { border-color: #ef4444; }
.export-bar  { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 0; }
.badge-PROCESADO  { background: #d1fae5; color: #065f46; }
.badge-PENDIENTE  { background: #fef3c7; color: #92400e; }
.badge-RECHAZADO  { background: #fee2e2; color: #991b1b; }
#spinner-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.35); z-index: 9999;
    align-items: center; justify-content: center;
}
    /* Mejora de contraste para textos y tablas */
    .form-label { color: #1e293b !important; font-weight: 600 !important; }
    .table td { color: #334155 !important; font-weight: 500; }
    .table th { color: #0f172a !important; font-weight: 700 !important; }
    .text-muted { color: #475569 !important; }
    .reporte-paginacion { border-top: 1px solid #e2e8f0; background: #f8fafc; }
    .reporte-paginacion .page-link { cursor: pointer; user-select: none; }
</style>

<!-- Spinner overlay -->
<div id="spinner-overlay">
    <div class="text-center text-white">
        <div class="spinner-border spinner-border-lg mb-2" style="width:3rem;height:3rem;"></div>
        <div class="fw-semibold">Cargando datos...</div>
    </div>
</div>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <div class="container-xl">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria">Prod. Agraria</a>
            </li>
            <li class="breadcrumb-item active">Reportes</li>
        </ol>
    </div>
</div>

<div class="page-body">
<div class="container-xl">

<!-- Logo local oculto para exportación PDF -->
<img id="logo-pech-local" src="<?php echo BASE_URL; ?>/Logo Pech.png" alt="PECH" style="display:none;" crossorigin="anonymous">

    <!-- ENCABEZADO -->
    <div class="card reporte-header text-white mb-4 border-0">
        <div class="card-body py-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-1 fw-bold"><i class="ti ti-chart-bar me-2"></i>Reportes de Producción Agraria</h3>
                    <p class="mb-0 opacity-75">Análisis de ventas, valorización de inventario y control de mermas — PECH</p>
                </div>
                <div class="col-auto">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">
                        <i class="ti ti-calendar me-1"></i>
                        <?php echo date('F Y', strtotime($fecha_desde_default)); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
<style>
    .report-card {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: 1px solid rgba(0, 77, 153, 0.08);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
        border-bottom: 4px solid transparent;
        height: 100%;
    }
    .report-card-ventas:hover { border-bottom-color: #206bc4; }
    .report-card-inventario:hover { border-bottom-color: #2fb344; }
    .report-card-mermas:hover { border-bottom-color: #d63939; }
    .report-card-clientes:hover { border-bottom-color: #7c3aed; }
    .report-card-consolidado:hover { border-bottom-color: #0ca678; }
    .report-card-precios:hover { border-bottom-color: #3f51b5; }
    .report-card-planilla:hover { border-bottom-color: #e91e63; }

    .report-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 28px rgba(0, 77, 153, 0.1);
        border-color: rgba(0, 77, 153, 0.15);
    }
    .report-card:hover .btn-abrir-rep {
        background: #004d99 !important;
        border-color: #004d99 !important;
        color: #ffffff !important;
        transform: scale(1.02);
    }
    .btn-abrir-rep {
        transition: all 0.2s ease-in-out;
        font-weight: 600;
        border-radius: 20px;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .avatar-md {
        width: 3.8rem;
        height: 3.8rem;
        line-height: 3.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: transform 0.3s ease;
    }
    .report-card:hover .avatar-md {
        transform: scale(1.1);
    }
    </style>

    <!-- =========================================================
         MENÚ PRINCIPAL DE REPORTES
    ========================================================= -->
    <div id="panel-menu-reportes" class="row g-3 mb-4">
        <!-- Card 1: Ventas -->
        <div class="col-md-4">
            <div class="card report-card report-card-ventas text-center p-4 cursor-pointer" onclick="abrirReporte('ventas')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-primary-lt"><i class="ti ti-currency-dollar fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Ventas y Facturación</h3>
                    <p class="text-muted small mb-0">Detalle de comprobantes, recaudación y transacciones en caja.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-primary btn-abrir-rep w-100 py-2">
                        <i class="ti ti-table-alias me-1"></i>Ver Reporte
                    </button>
                </div>
            </div>
        </div>
        <!-- Card 2: Inventario -->
        <div class="col-md-4">
            <div class="card report-card report-card-inventario text-center p-4 cursor-pointer" onclick="abrirReporte('inventario')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-success-lt"><i class="ti ti-box fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Valorización de Inventario</h3>
                    <p class="text-muted small mb-0">Stock actual valorizado por lotes y precios vigentes.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-success btn-abrir-rep w-100 py-2">
                        <i class="ti ti-table-alias me-1"></i>Ver Reporte
                    </button>
                </div>
            </div>
        </div>
        <!-- Card 3: Mermas -->
        <div class="col-md-4">
            <div class="card report-card report-card-mermas text-center p-4 cursor-pointer" onclick="abrirReporte('mermas')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-danger-lt"><i class="ti ti-leaf-off fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Mermas y Pérdidas</h3>
                    <p class="text-muted small mb-0">Auditoría de bajas, descartes y pérdidas de stock.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-danger btn-abrir-rep w-100 py-2">
                        <i class="ti ti-table-alias me-1"></i>Ver Reporte
                    </button>
                </div>
            </div>
        </div>
        <!-- Card 5: Clientes -->
        <div class="col-md-4">
            <div class="card report-card report-card-clientes text-center p-4 cursor-pointer" onclick="abrirReporte('clientes-rep')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-purple-lt"><i class="ti ti-users fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Clientes y Recaudación</h3>
                    <p class="text-muted small mb-0">Historial de aportes de clientes de Planilla y Externos.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-purple btn-abrir-rep w-100 py-2">
                        <i class="ti ti-table-alias me-1"></i>Ver Reporte
                    </button>
                </div>
            </div>
        </div>
        <!-- Card 6: Consolidado -->
        <div class="col-md-4">
            <div class="card report-card report-card-consolidado text-center p-4 cursor-pointer" onclick="abrirReporte('consolidado')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-teal-lt"><i class="ti ti-building fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Consolidado por Centro</h3>
                    <p class="text-muted small mb-0">Resumen gerencial de ingresos, stock y mermas por centro.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-teal btn-abrir-rep w-100 py-2">
                        <i class="ti ti-table-alias me-1"></i>Ver Reporte
                    </button>
                </div>
            </div>
        </div>
        <!-- Card 7: Precios -->
        <div class="col-md-4">
            <div class="card report-card report-card-precios text-center p-4 cursor-pointer" onclick="abrirReporte('precios')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-indigo-lt"><i class="ti ti-tags fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Catálogo de Precios</h3>
                    <p class="text-muted small mb-0">Tarifas oficiales, precios variables y porcentajes UIT vigentes.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-indigo btn-abrir-rep w-100 py-2">
                        <i class="ti ti-table-alias me-1"></i>Ver Reporte
                    </button>
                </div>
            </div>
        </div>
        <!-- Card 8: Relación Planilla -->
        <div class="col-md-4">
            <div class="card report-card report-card-planilla text-center p-4 cursor-pointer" onclick="abrirReporte('planilla')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-pink-lt"><i class="ti ti-file-invoice fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Relación de Planilla</h3>
                    <p class="text-muted small mb-0">Compras de personal con descuento por planilla para nómina.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-pink btn-abrir-rep w-100 py-2">
                        <i class="ti ti-table-alias me-1"></i>Ver Reporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         PANEL DE FILTROS GLOBALES
    ========================================================= -->
    <div class="card mb-4" id="panel-filtros-reportes" style="display:none;">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary me-3 px-3" onclick="volverAlMenu()">
                    <i class="ti ti-arrow-left me-1"></i>Volver
                </button>
                <h4 class="card-title mb-0" id="titulo-reporte-activo">Filtros</h4>
            </div>
            <span class="badge bg-primary-lt" id="badge-tipo-reporte">Reporte Activo</span>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end" id="row-filtros-inputs">
                <div class="col-md-2" id="col-f-fecha-desde">
                    <label class="form-label fw-semibold">Fecha desde</label>
                    <input type="date" class="form-control" id="f_fecha_desde"
                           value="<?php echo $fecha_desde_default; ?>">
                </div>
                <div class="col-md-2" id="col-f-fecha-hasta">
                    <label class="form-label fw-semibold">Fecha hasta</label>
                    <input type="date" class="form-control" id="f_fecha_hasta"
                           value="<?php echo $fecha_hasta_default; ?>">
                </div>
                <div class="col-md-2" id="col-f-centro">
                    <label class="form-label fw-semibold">Centro</label>
                    <select class="form-select" id="f_centro">
                        <option value="">Todos</option>
                        <?php foreach ($centros as $c): ?>
                        <option value="<?php echo $c['id_centro']; ?>"><?php echo htmlspecialchars($c['nombre_centro']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2" id="col-f-clase">
                    <label class="form-label fw-semibold">Clase</label>
                    <select class="form-select" id="f_clase">
                        <option value="">Todas</option>
                        <?php foreach ($clases as $cl): ?>
                        <option value="<?php echo $cl['id_clase']; ?>"><?php echo htmlspecialchars($cl['nombre_clase']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2" id="col-f-estado">
                    <label class="form-label fw-semibold">Estado</label>
                    <select class="form-select" id="f_estado">
                        <option value="">Todos</option>
                        <option value="PROCESADO">Procesado</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="RECHAZADO">Rechazado</option>
                    </select>
                </div>
                <div class="col-md-2" id="col-f-metodo-pago">
                    <label class="form-label fw-semibold">Tipo Operación</label>
                    <select class="form-select" id="f_metodo_pago">
                        <option value="">Todos</option>
                        <option value="VENTA">Ventas</option>
                        <option value="DONACION">Donaciones</option>
                    </select>
                </div>
                <div class="col-md-2" id="col-f-cliente">
                    <label class="form-label fw-semibold">Cliente</label>
                    <select class="form-select" id="f_cliente">
                        <option value="">Todos</option>
                        <?php foreach ($clientes as $cl): ?>
                        <option value="<?php echo $cl['id_cliente']; ?>"><?php echo htmlspecialchars($cl['nombre_rs']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2" id="col-f-tipo-precio">
                    <label class="form-label fw-semibold">Tipo Precio</label>
                    <select class="form-select" id="f_tipo_precio">
                        <option value="">Todos</option>
                        <option value="Variable">Variable</option>
                        <option value="UIT">UIT</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3" id="row-filtros-buttons">
                <div class="col-12">
                    <button class="btn btn-primary px-4" onclick="aplicarFiltros()">
                        <i class="ti ti-search me-1"></i>Aplicar filtros
                    </button>
                    <button class="btn btn-outline-secondary ms-2" onclick="limpiarFiltros()">
                        <i class="ti ti-refresh me-1"></i>Limpiar
                    </button>
                    <span class="ms-3 text-muted small" id="resultado-label"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         PESTAÑAS DE REPORTES
    ========================================================= -->
    <!-- Pestañas estáticas removidas - navegación por botones activada -->

    <div class="tab-content border border-top-0 rounded bg-white mb-4" id="tab-content-reportes" style="display:none;">

        <!-- =====================================================
             TAB 1: VENTAS Y FACTURACIÓN (ACTIVA POR DEFECTO)
        ===================================================== -->
        <div class="tab-pane fade show active p-4" id="tab-ventas">
            <!-- KPIs -->
            <div class="row g-3 mb-4" id="kpis-ventas">
                <div class="col-md-4">
                    <div class="card kpi-card kpi-ventas">
                        <div class="card-body">
                            <div class="text-muted small">Total Facturado (Procesado)</div>
                            <div class="h3 mb-0 fw-bold text-success" id="kpi-monto">
                                S/ <?php echo number_format($kpis_init['monto_total'] ?? 0, 2, '.', ','); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card kpi-count">
                        <div class="card-body">
                            <div class="text-muted small">N° Transacciones Procesadas</div>
                            <div class="h3 mb-0 fw-bold text-primary" id="kpi-count">
                                <?php echo number_format($kpis_init['total_transacciones'] ?? 0); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card kpi-ticket">
                        <div class="card-body">
                            <div class="text-muted small">Ticket Promedio</div>
                            <div class="h3 mb-0 fw-bold text-warning" id="kpi-ticket">
                                S/ <?php echo number_format($kpis_init['ticket_promedio'] ?? 0, 2, '.', ','); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tabla de ventas -->
            <div class="table-responsive">
                <table class="table table-vcenter table-hover" id="tabla-ventas">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Centro</th>
                            <th>Método</th>
                            <th>Comprobante</th>
                            <th>Estado</th>
                            <th class="text-end">Total (S/)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-ventas">
                        <?php foreach ($ventas_init as $v): ?>
                        <tr>
                            <td><code><?php echo $v['id_transaccion']; ?></code></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['fecha_creacion'])); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($v['nombre_cliente'] ?? '-'); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($v['documento_cliente'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($v['nombre_centro'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($v['metodo_pago'] ?? '-'); ?></td>
                            <td>
                                <?php if ($v['serie_comprobante']): ?>
                                    <small><?php echo $v['serie_comprobante'].'-'.$v['correlativo_comprobante']; ?></small>
                                <?php else: ?>-
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $v['estado']; ?>">
                                    <?php echo $v['estado']; ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold"><?php echo number_format($v['total'], 2, '.', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventas_init)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No hay ventas en el período seleccionado</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="7" class="fw-bold text-end">SUBTOTAL MOSTRADO:</td>
                            <td class="text-end fw-bold text-success" id="subtotal-ventas">
                                S/ <?php echo number_format(array_sum(array_column($ventas_init, 'total')), 2, '.', ','); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- =====================================================
             TAB 3: VALORIZACIÓN DE INVENTARIO
        ===================================================== -->
        <div class="tab-pane fade p-4" id="tab-inventario">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card kpi-card kpi-stock">
                        <div class="card-body">
                            <div class="text-muted small">Valor Total en Almacén</div>
                            <div class="h3 mb-0 fw-bold text-purple" id="kpi-valor-inventario">
                                S/ <?php echo number_format($total_stock_valor, 2, '.', ','); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card kpi-count">
                        <div class="card-body">
                            <div class="text-muted small">Lotes Activos con Stock</div>
                            <div class="h3 mb-0 fw-bold text-primary" id="kpi-lotes-activos">
                                <?php echo count($inventario_init); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body">
                            <div class="text-muted small"><i class="ti ti-info-circle me-1"></i>Nota sobre precios</div>
                            <div class="small text-info mt-1">
                                Los productos tipo <strong>UIT</strong> usan el valor vigente del año actual. Los <strong>Variable</strong> usan el último precio registrado.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover" id="tabla-inventario">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>Clase</th>
                            <th>Centro</th>
                            <th>Lote</th>
                            <th>Antigüedad</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Tipo Precio</th>
                            <th class="text-end">Precio Unit. (S/)</th>
                            <th class="text-end">Valor Total (S/)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-inventario">
                        <?php foreach ($inventario_init as $i): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($i['nombre_producto']); ?></div>
                                <?php if ($i['nombre_cientifico']): ?>
                                <small class="text-muted fst-italic"><?php echo htmlspecialchars($i['nombre_cientifico']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($i['nombre_clase'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($i['nombre_centro'] ?? '-'); ?></td>
                            <td><code><?php echo htmlspecialchars($i['codigo_lote']); ?></code></td>
                            <td>
                                <span class="<?php echo $i['antiguedad_dias'] > 20 ? 'text-danger fw-bold' : ($i['antiguedad_dias'] > 7 ? 'text-warning' : 'text-success'); ?>">
                                    <i class="ti ti-clock me-1"></i><?php echo $i['antiguedad_dias']; ?> días
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $i['stock_actual'] < 10 ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo number_format($i['stock_actual']); ?> <?php echo htmlspecialchars($i['unidad_medida']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark"><?php echo $i['tipo_precio']; ?></span>
                            </td>
                            <td class="text-end"><?php echo number_format($i['precio_unitario'], 4, '.', ','); ?></td>
                            <td class="text-end fw-bold text-success"><?php echo number_format($i['valor_total_lote'], 2, '.', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inventario_init)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No hay inventario con stock disponible</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="8" class="fw-bold text-end">VALOR TOTAL INVENTARIO:</td>
                            <td class="text-end fw-bold text-success" id="total-inventario">
                                S/ <?php echo number_format($total_stock_valor, 2, '.', ','); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- =====================================================
             TAB 4: MERMAS Y PÉRDIDAS
        ===================================================== -->
        <div class="tab-pane fade p-4" id="tab-mermas">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card kpi-card kpi-mermas">
                        <div class="card-body">
                            <div class="text-muted small">Valor Estimado de Pérdidas</div>
                            <div class="h3 mb-0 fw-bold text-danger" id="kpi-valor-mermas">
                                S/ <?php echo number_format($total_mermas_valor, 2, '.', ','); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card kpi-count">
                        <div class="card-body">
                            <div class="text-muted small">N° Registros de Merma</div>
                            <div class="h3 mb-0 fw-bold text-primary" id="kpi-count-mermas">
                                <?php echo count($mermas_init); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover" id="tabla-mermas">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Clase</th>
                            <th>Centro</th>
                            <th>Lote</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Precio Unit. (S/)</th>
                            <th class="text-end">Valor Pérdida (S/)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-mermas">
                        <?php foreach ($mermas_init as $m): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($m['fecha'])); ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($m['nombre_producto']); ?></td>
                            <td><?php echo htmlspecialchars($m['nombre_clase'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($m['nombre_centro'] ?? '-'); ?></td>
                            <td><code><?php echo htmlspecialchars($m['codigo_lote']); ?></code></td>
                            <td class="text-center">
                                <span class="badge bg-danger"><?php echo number_format($m['cantidad_merma']); ?></span>
                            </td>
                            <td class="text-end"><?php echo number_format($m['precio_unitario'], 4, '.', ','); ?></td>
                            <td class="text-end fw-bold text-danger"><?php echo number_format($m['valor_perdida'], 2, '.', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mermas_init)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No hay registros de merma en el período seleccionado</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="7" class="fw-bold text-end">TOTAL PÉRDIDAS:</td>
                            <td class="text-end fw-bold text-danger" id="total-mermas">
                                S/ <?php echo number_format($total_mermas_valor, 2, '.', ','); ?>
                            </td>
                        </tr>
                    </tfoot>
            </div>
        </div>

        <!-- =====================================================
             TAB 5: CLIENTES Y RECAUDACIÓN
        ===================================================== -->
        <div class="tab-pane fade p-4" id="tab-clientes-rep">
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="tabla-clientes-rep">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>DNI / RUC</th>
                            <th>Cliente / Razón Social</th>
                            <th>Tipo de Cliente</th>
                            <th class="text-center">Total Transacciones</th>
                            <th class="text-end">Total Ventas (S/)</th>
                            <th class="text-end">Total Donaciones (S/)</th>
                            <th class="text-end">Total Acumulado (S/)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-clientes-rep">
                        <?php foreach ($clientes_init as $c): ?>
                        <tr>
                            <td><?php echo $c['id_cliente']; ?></td>
                            <td><?php echo htmlspecialchars($c['dni_ruc']); ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($c['nombre_rs']); ?></td>
                            <td>
                                <span class="badge <?php echo ($c['tipo_cliente'] == 'Planilla') ? 'bg-purple-lt' : 'bg-blue-lt'; ?>">
                                    <?php echo htmlspecialchars($c['tipo_cliente']); ?>
                                </span>
                            </td>
                            <td class="text-center"><span class="badge bg-secondary"><?php echo htmlspecialchars($c['total_transacciones']); ?></span></td>
                            <td class="text-end text-success"><?php echo number_format($c['total_ventas'], 2, '.', ','); ?></td>
                            <td class="text-end text-warning"><?php echo number_format($c['total_donaciones'], 2, '.', ','); ?></td>
                            <td class="text-end fw-bold text-primary">S/ <?php echo number_format($c['total_acumulado'], 2, '.', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clientes_init)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No hay clientes registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =====================================================
             TAB 6: CONSOLIDADO POR CENTRO
        ===================================================== -->
        <div class="tab-pane fade p-4" id="tab-consolidado">
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="tabla-consolidado">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>Centro de Producción</th>
                            <th>Encargado</th>
                            <th class="text-end">Ventas Totales (S/)</th>
                            <th class="text-end">Donaciones Totales (S/)</th>
                            <th class="text-end">Valor de Inventario (S/)</th>
                            <th class="text-end text-danger">Mermas / Pérdidas (S/)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-consolidado">
                        <?php foreach ($consolidado_init as $con): ?>
                        <tr>
                            <td><?php echo $con['id_centro']; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($con['nombre_centro']); ?></td>
                            <td><?php echo htmlspecialchars($con['encargado']); ?></td>
                            <td class="text-end text-success fw-semibold">S/ <?php echo number_format($con['total_ventas'], 2, '.', ','); ?></td>
                            <td class="text-end text-warning fw-semibold">S/ <?php echo number_format($con['total_donaciones'], 2, '.', ','); ?></td>
                            <td class="text-end text-primary fw-semibold">S/ <?php echo number_format($con['valor_inventario'], 2, '.', ','); ?></td>
                            <td class="text-end text-danger fw-bold">S/ <?php echo number_format($con['valor_mermas'], 2, '.', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($consolidado_init)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay centros de producción registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
        </div>

        <!-- =====================================================
             TAB 7: CATÁLOGO DE PRECIOS
        ===================================================== -->
        <div class="tab-pane fade p-4" id="tab-precios">
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="tabla-precios">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>Producto</th>
                            <th>Clase</th>
                            <th>Centro</th>
                            <th class="text-center">Tipo Precio</th>
                            <th class="text-end">Vigencia UIT / Histórico</th>
                            <th class="text-end">Precio Vigente (S/)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-precios">
                        <?php foreach ($precios_init as $p): ?>
                        <tr>
                            <td><?php echo $p['id_producto']; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['nombre_producto']); ?></div>
                                <?php if ($p['nombre_cientifico']): ?>
                                <small class="text-muted fst-italic"><?php echo htmlspecialchars($p['nombre_cientifico']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($p['nombre_clase'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($p['nombre_centro'] ?? '-'); ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo ($p['tipo_precio'] == 'UIT') ? 'bg-indigo-lt' : 'bg-green-lt'; ?>">
                                    <?php echo htmlspecialchars($p['tipo_precio']); ?>
                                </span>
                            </td>
                            <td class="text-end text-muted small">
                                <?php if ($p['tipo_precio'] == 'UIT'): ?>
                                    UIT x <?php echo number_format($p['porcentaje_uit'], 4, '.', ','); ?>
                                <?php else: ?>
                                    Variable
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold text-success">S/ <?php echo number_format($p['precio_unitario'], 2, '.', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($precios_init)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay productos registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Planilla -->
        <div class="tab-pane fade p-4" id="tab-planilla">
            <div class="alert alert-info d-flex align-items-center mb-3">
                <i class="ti ti-info-circle me-2"></i>
                <div>
                    <strong>Relación del Personal de Planilla</strong><br>
                    <small class="mb-0">Compras registradas con descuento por planilla. Cada producto muestra cantidad, costo unitario y subtotal. Use los filtros de fecha para seleccionar el mes.</small>
                </div>
            </div>
            <div class="table-responsive" id="contenedor-tabla-planilla">
                <table class="table table-vcenter card-table table-bordered table-sm" id="tabla-planilla">
                    <thead id="thead-planilla">
                        <tr><th class="text-center text-muted" colspan="3">Aplique filtros y haga clic en Generar</th></tr>
                    </thead>
                    <tbody id="tbody-planilla">
                        <tr><td colspan="3" class="text-center py-4 text-muted">No hay datos</td></tr>
                    </tbody>
                    <tfoot id="tfoot-planilla" class="table-light fw-bold"></tfoot>
                </table>
            </div>
        </div>

    </div><!-- /tab-content -->

    <!-- BARRA DE EXPORTACIÓN -->
    <div class="export-bar mt-0 mb-4" id="panel-export-bar" style="display:none;">
        <div class="d-flex gap-2 justify-content-end px-1 pt-3">
            <button class="btn btn-success" onclick="exportarExcel()">
                <i class="ti ti-file-type-xls me-1"></i>Exportar Excel
            </button>
            <button class="btn btn-danger" onclick="exportarPDF()">
                <i class="ti ti-file-type-pdf me-1"></i>Exportar PDF
            </button>
        </div>
    </div>

</div><!-- /container-xl -->
</div><!-- /page-body -->


<script>
// =============================================================
// CONFIGURACIÓN BASE
// =============================================================
const BASE_URL = '<?php echo BASE_URL; ?>';
let tabActiva = 'ventas';

// Configuración de Reportes y Filtros Pertinentes
const reportConfigs = {
    ventas: {
        titulo: 'Reporte de Ventas y Facturación',
        filters: ['fecha-desde', 'fecha-hasta', 'centro', 'estado', 'metodo-pago', 'cliente'],
        tabId: 'tab-ventas'
    },
    inventario: {
        titulo: 'Reporte de Valorización de Inventario',
        filters: ['centro', 'clase'],
        tabId: 'tab-inventario'
    },
    mermas: {
        titulo: 'Reporte de Mermas y Pérdidas de Stock',
        filters: ['fecha-desde', 'fecha-hasta', 'centro', 'clase'],
        tabId: 'tab-mermas'
    },
    'clientes-rep': {
        titulo: 'Reporte de Clientes y Recaudación Acumulada',
        filters: ['cliente'],
        tabId: 'tab-clientes-rep'
    },
    consolidado: {
        titulo: 'Reporte Consolidado por Centro de Producción',
        filters: [],
        tabId: 'tab-consolidado'
    },
    precios: {
        titulo: 'Catálogo de Precios Vigentes',
        filters: ['centro', 'clase', 'tipo-precio'],
        tabId: 'tab-precios'
    },
    planilla: {
        titulo: 'Relación del Personal de Planilla',
        filters: ['fecha-desde', 'fecha-hasta', 'centro'],
        tabId: 'tab-planilla'
    }
};

function abrirReporte(tipo) {
    const config = reportConfigs[tipo];
    if (!config) return;
    
    tabActiva = tipo;
    
    // Cambiar título del panel
    document.getElementById('titulo-reporte-activo').textContent = config.titulo;
    
    // Ocultar todos los filtros
    const allFilters = ['fecha-desde', 'fecha-hasta', 'centro', 'clase', 'estado', 'metodo-pago', 'cliente', 'tipo-precio'];
    allFilters.forEach(f => {
        const el = document.getElementById(`col-f-${f}`);
        if (el) el.style.display = 'none';
    });
    
    // Mostrar filtros pertinentes
    config.filters.forEach(f => {
        document.getElementById(`col-f-${f}`).style.display = 'block';
    });
    
    // Si no tiene filtros, ocultar el row o achicar el panel
    if (config.filters.length === 0) {
        document.getElementById('row-filtros-inputs').style.display = 'none';
        document.getElementById('row-filtros-buttons').style.display = 'none';
    } else {
        document.getElementById('row-filtros-inputs').style.display = 'flex';
        document.getElementById('row-filtros-buttons').style.display = 'block';
    }
    
    // Ocultar el menú de tarjetas
    document.getElementById('panel-menu-reportes').style.display = 'none';
    
    // Mostrar filtros y contenedor de tablas
    document.getElementById('panel-filtros-reportes').style.display = 'block';
    document.getElementById('tab-content-reportes').style.display = 'block';
    document.getElementById('panel-export-bar').style.display = 'block';
    
    // Desactivar todas las pestañas
    const allTabs = ['ventas', 'inventario', 'mermas', 'clientes-rep', 'consolidado', 'precios', 'planilla'];
    allTabs.forEach(t => {
        const el = document.getElementById(`tab-${t}`);
        if (el) el.classList.remove('show', 'active');
    });
    
    // Activar pestaña del reporte seleccionado
    const activeEl = document.getElementById(config.tabId);
    if (activeEl) activeEl.classList.add('show', 'active');
    
    // Ejecutar consulta y renderizado
    aplicarFiltros();
}

function volverAlMenu() {
    // Ocultar filtros, tabla y barra de exportación
    document.getElementById('panel-filtros-reportes').style.display = 'none';
    document.getElementById('tab-content-reportes').style.display = 'none';
    document.getElementById('panel-export-bar').style.display = 'none';
    
    // Mostrar menú de tarjetas
    document.getElementById('panel-menu-reportes').style.display = 'flex';
}


// =============================================================
// FILTROS Y CARGA DINÁMICA DE DATOS
// =============================================================
function getFiltros() {
    return {
        fecha_desde: document.getElementById('f_fecha_desde').value,
        fecha_hasta: document.getElementById('f_fecha_hasta').value,
        id_centro:   document.getElementById('f_centro').value,
        id_clase:    document.getElementById('f_clase').value,
        estado:      document.getElementById('f_estado').value,
        metodo_pago: document.getElementById('f_metodo_pago').value,
        id_cliente:  document.getElementById('f_cliente').value,
        tipo_precio: document.getElementById('f_tipo_precio').value,
    };
}

function buildQueryString(filtros) {
    return Object.entries(filtros)
        .filter(([, v]) => v !== '')
        .map(([k, v]) => `${k}=${encodeURIComponent(v)}`)
        .join('&');
}

function mostrarSpinner(show) {
    document.getElementById('spinner-overlay').style.display = show ? 'flex' : 'none';
}

function aplicarFiltros() {
    mostrarSpinner(true);
    const f = getFiltros();
    const qs = buildQueryString(f);
    const url = `${BASE_URL}/index.php?module=produccion_agraria&`;

    let promise;
    if (tabActiva === 'ventas') {
        promise = fetch(url + 'action=ventas_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderVentas(res.data, res.kpis);
                }
            });
    } else if (tabActiva === 'inventario') {
        promise = fetch(url + 'action=inventario_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderInventario(res.data);
                }
            });
    } else if (tabActiva === 'mermas') {
        promise = fetch(url + 'action=mermas_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderMermas(res.data);
                }
            });
    } else if (tabActiva === 'clientes-rep') {
        promise = fetch(url + 'action=clientes_report_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderClientes(res.data);
                }
            });
    } else if (tabActiva === 'consolidado') {
        promise = fetch(url + 'action=consolidado_report_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderConsolidado(res.data);
                }
            });
    } else if (tabActiva === 'precios') {
        promise = fetch(url + 'action=precios_report_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderPrecios(res.data);
                }
            });
    } else if (tabActiva === 'planilla') {
        promise = fetch(url + 'action=planilla_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderPlanilla(res.data);
                }
            });
    }

    if (promise) {
        promise.catch(err => {
            console.error('Error al cargar reporte:', err);
        }).finally(() => {
            mostrarSpinner(false);
        });
    } else {
        mostrarSpinner(false);
    }
}

function limpiarFiltros() {
    const hoy = new Date();
    const primerDia = hoy.getFullYear() + '-' + String(hoy.getMonth()+1).padStart(2,'0') + '-01';
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth()+1, 0);
    const ultimoDiaStr = ultimoDia.getFullYear() + '-' + String(ultimoDia.getMonth()+1).padStart(2,'0') + '-' + String(ultimoDia.getDate()).padStart(2,'0');

    document.getElementById('f_fecha_desde').value = primerDia;
    document.getElementById('f_fecha_hasta').value = ultimoDiaStr;
    document.getElementById('f_centro').value  = '';
    document.getElementById('f_clase').value   = '';
    document.getElementById('f_estado').value  = '';
    document.getElementById('f_metodo_pago').value = '';
    document.getElementById('f_cliente').value = '';
    document.getElementById('f_tipo_precio').value = '';
    aplicarFiltros();
}

// =============================================================
// RENDER DE TABLAS
// =============================================================
function escapeHtml(str) {
    if (str == null) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Mapa seguro de clases de badge por estado (evita inyección en la clase CSS)
const ESTADO_BADGE = {
    'PROCESADO': 'badge-PROCESADO',
    'PENDIENTE': 'badge-PENDIENTE',
    'RECHAZADO': 'badge-RECHAZADO',
    'ANULADA':   'badge-RECHAZADO',
    'PLANILLA':  'badge-PROCESADO'
};

// =============================================================
// PAGINACIÓN CLIENT-SIDE (10 registros por página)
// =============================================================
const PAGE_SIZE = 10;
const paginationState = {};

function paginationContainer(table) {
    const parent = table ? table.parentElement : null;
    let cont = parent ? parent.querySelector('.reporte-paginacion') : null;
    if (!parent) return null;
    if (!cont) {
        cont = document.createElement('div');
        cont.className = 'reporte-paginacion';
        parent.appendChild(cont);
    }
    return cont;
}

function renderPaginated(tbodyId, rowHtmlArray) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const table = tbody.closest('table');
    const cont = paginationContainer(table);

    if (!rowHtmlArray || rowHtmlArray.length === 0) {
        if (cont) cont.innerHTML = '';
        return;
    }

    paginationState[tbodyId] = { rows: rowHtmlArray.slice(), page: 1 };
    drawPage(tbodyId);
}

function drawPage(tbodyId) {
    const st = paginationState[tbodyId];
    const tbody = document.getElementById(tbodyId);
    if (!st || !tbody) return;

    const totalPages = Math.max(1, Math.ceil(st.rows.length / PAGE_SIZE));
    if (st.page > totalPages) st.page = totalPages;
    const start = (st.page - 1) * PAGE_SIZE;
    tbody.innerHTML = st.rows.slice(start, start + PAGE_SIZE).join('');
    drawPaginationControls(tbodyId, st, totalPages);
}

function drawPaginationControls(tbodyId, st, totalPages) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const table = tbody.closest('table');
    const cont = paginationContainer(table);
    if (!cont) return;

    if (totalPages <= 1) { cont.innerHTML = ''; return; }

    const desde = (st.page - 1) * PAGE_SIZE + 1;
    const hasta = Math.min(st.page * PAGE_SIZE, st.rows.length);

    let html = '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted px-3 py-2">';
    html += `<span>Mostrando ${desde}-${hasta} de ${st.rows.length} registros</span>`;
    html += '<ul class="pagination pagination-sm mb-0">';
    html += `<li class="page-item ${st.page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-pag-tbody="${tbodyId}" data-pag-page="${st.page - 1}">&laquo;</a></li>`;
    for (let p = 1; p <= totalPages; p++) {
        html += `<li class="page-item ${p === st.page ? 'active' : ''}"><a class="page-link" href="#" data-pag-tbody="${tbodyId}" data-pag-page="${p}">${p}</a></li>`;
    }
    html += `<li class="page-item ${st.page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-pag-tbody="${tbodyId}" data-pag-page="${st.page + 1}">&raquo;</a></li>`;
    html += '</ul></div>';

    cont.innerHTML = html;
}

document.addEventListener('click', function (e) {
    const link = e.target.closest('a.page-link[data-pag-tbody]');
    if (!link) return;
    e.preventDefault();
    const tbodyId = link.getAttribute('data-pag-tbody');
    const page = parseInt(link.getAttribute('data-pag-page'), 10);
    const st = paginationState[tbodyId];
    if (!st) return;
    const totalPages = Math.ceil(st.rows.length / PAGE_SIZE);
    if (page >= 1 && page <= totalPages) {
        st.page = page;
        drawPage(tbodyId);
    }
});

function renderVentas(data, kpis) {
    // KPIs
    document.getElementById('kpi-monto').textContent  = 'S/ ' + parseFloat(kpis.monto_total || 0).toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-count').textContent  = parseInt(kpis.total_transacciones || 0).toLocaleString();
    document.getElementById('kpi-ticket').textContent = 'S/ ' + parseFloat(kpis.ticket_promedio || 0).toLocaleString('es-PE', {minimumFractionDigits:2});

    let totalMostrado = 0;
    const tbody = document.getElementById('tbody-ventas');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay ventas con los filtros seleccionados</td></tr>';
        renderPaginated('tbody-ventas', []);
        document.getElementById('subtotal-ventas').textContent = 'S/ 0.00';
        return;
    }
    const filas = data.map(v => {
        totalMostrado += parseFloat(v.total || 0);
        const fecha = new Date(v.fecha_creacion).toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
        const comp  = v.serie_comprobante ? `${escapeHtml(v.serie_comprobante)}-${escapeHtml(v.correlativo_comprobante)}` : '-';
        const badgeEstado = ESTADO_BADGE[v.estado] || 'badge-PENDIENTE';
        return `<tr>
            <td><code>${escapeHtml(v.id_transaccion)}</code></td>
            <td>${fecha}</td>
            <td><div>${escapeHtml(v.nombre_cliente || '-')}</div><small class="text-muted">${escapeHtml(v.documento_cliente || '')}</small></td>
            <td>${escapeHtml(v.nombre_centro || '-')}</td>
            <td>${escapeHtml(v.metodo_pago || '-')}</td>
            <td><small>${comp}</small></td>
            <td><span class="badge ${badgeEstado}">${escapeHtml(v.estado)}</span></td>
            <td class="text-end fw-bold">${parseFloat(v.total).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    });
    renderPaginated('tbody-ventas', filas);
    document.getElementById('subtotal-ventas').textContent = 'S/ ' + totalMostrado.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('resultado-label').textContent = `${data.length} registro(s) encontrado(s)`;
}

function renderInventario(data) {
    let totalValor = 0;
    const tbody = document.getElementById('tbody-inventario');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No hay inventario con stock disponible</td></tr>';
        renderPaginated('tbody-inventario', []);
        document.getElementById('total-inventario').textContent = 'S/ 0.00';
        document.getElementById('kpi-valor-inventario').textContent = 'S/ 0.00';
        document.getElementById('kpi-lotes-activos').textContent = '0';
        return;
    }
    const filas = data.map(i => {
        totalValor += parseFloat(i.valor_total_lote || 0);
        const diasClass = i.antiguedad_dias > 20 ? 'text-danger fw-bold' : (i.antiguedad_dias > 7 ? 'text-warning' : 'text-success');
        const stockBadge = i.stock_actual < 10 ? 'bg-danger' : 'bg-success';
        const fecLote = i.fecha_lote ? new Date(i.fecha_lote).toLocaleDateString('es-PE') : '-';
        return `<tr>
            <td><div class="fw-semibold">${escapeHtml(i.nombre_producto)}</div>${i.nombre_cientifico ? `<small class="text-muted fst-italic">${escapeHtml(i.nombre_cientifico)}</small>` : ''}</td>
            <td>${escapeHtml(i.nombre_clase || '-')}</td>
            <td>${escapeHtml(i.nombre_centro || '-')}</td>
            <td><code>${escapeHtml(i.codigo_lote)}</code></td>
            <td><span class="${diasClass}"><i class="ti ti-clock me-1"></i>${i.antiguedad_dias} días</span></td>
            <td class="text-center"><span class="badge ${stockBadge}">${parseInt(i.stock_actual).toLocaleString()} ${escapeHtml(i.unidad_medida)}</span></td>
            <td class="text-center"><span class="badge bg-light text-dark">${escapeHtml(i.tipo_precio)}</span></td>
            <td class="text-end">${parseFloat(i.precio_unitario).toFixed(4)}</td>
            <td class="text-end fw-bold text-success">${parseFloat(i.valor_total_lote).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    });
    renderPaginated('tbody-inventario', filas);
    document.getElementById('total-inventario').textContent = 'S/ ' + totalValor.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-valor-inventario').textContent = 'S/ ' + totalValor.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-lotes-activos').textContent = data.length.toLocaleString();
}

function renderMermas(data) {
    let totalPerdida = 0;
    const tbody = document.getElementById('tbody-mermas');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay registros de merma en el período seleccionado</td></tr>';
        renderPaginated('tbody-mermas', []);
        document.getElementById('total-mermas').textContent = 'S/ 0.00';
        document.getElementById('kpi-valor-mermas').textContent = 'S/ 0.00';
        document.getElementById('kpi-count-mermas').textContent = '0';
        return;
    }
    const filas = data.map(m => {
        totalPerdida += parseFloat(m.valor_perdida || 0);
        const fecha = new Date(m.fecha).toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
        return `<tr>
            <td>${fecha}</td>
            <td class="fw-semibold">${escapeHtml(m.nombre_producto)}</td>
            <td>${escapeHtml(m.nombre_clase || '-')}</td>
            <td>${escapeHtml(m.nombre_centro || '-')}</td>
            <td><code>${escapeHtml(m.codigo_lote)}</code></td>
            <td class="text-center"><span class="badge bg-danger">${parseInt(m.cantidad_merma).toLocaleString()}</span></td>
            <td class="text-end">${parseFloat(m.precio_unitario).toFixed(4)}</td>
            <td class="text-end fw-bold text-danger">${parseFloat(m.valor_perdida).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    });
    renderPaginated('tbody-mermas', filas);
    document.getElementById('total-mermas').textContent = 'S/ ' + totalPerdida.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-valor-mermas').textContent = 'S/ ' + totalPerdida.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-count-mermas').textContent = data.length.toLocaleString();
}

// Gráficos removidos - migración planeada para el Dashboard principal

function renderClientes(data) {
    const tbody = document.getElementById('tbody-clientes-rep');
    if (!tbody) return;
    
    const filas = data.map(c => {
        const transacciones = parseInt(c.total_transacciones);
        const ventas = parseFloat(c.total_ventas);
        const donaciones = parseFloat(c.total_donaciones);
        const acumulado = parseFloat(c.total_acumulado);
        
        const badgeClass = c.tipo_cliente === 'Planilla' ? 'bg-purple-lt' : 'bg-blue-lt';
        
        return `<tr>
            <td>${c.id_cliente}</td>
            <td>${escapeHtml(c.dni_ruc || '-')}</td>
            <td class="fw-semibold">${escapeHtml(c.nombre_rs)}</td>
            <td>
                <span class="badge ${badgeClass}">${escapeHtml(c.tipo_cliente)}</span>
            </td>
            <td class="text-center"><span class="badge bg-secondary">${transacciones}</span></td>
            <td class="text-end text-success">${ventas.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-warning">${donaciones.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end fw-bold text-primary">S/ ${acumulado.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    });
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay clientes registrados</td></tr>';
        renderPaginated('tbody-clientes-rep', []);
    } else {
        renderPaginated('tbody-clientes-rep', filas);
    }
}

function renderConsolidado(data) {
    const tbody = document.getElementById('tbody-consolidado');
    if (!tbody) return;
    
    const filas = data.map(con => {
        const ventas = parseFloat(con.total_ventas);
        const donaciones = parseFloat(con.total_donaciones);
        const inventario = parseFloat(con.valor_inventario);
        const mermas = parseFloat(con.valor_mermas);
        
        return `<tr>
            <td>${con.id_centro}</td>
            <td class="fw-bold text-dark">${escapeHtml(con.nombre_centro)}</td>
            <td>${escapeHtml(con.encargado || '-')}</td>
            <td class="text-end text-success fw-semibold">S/ ${ventas.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-warning fw-semibold">S/ ${donaciones.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-primary fw-semibold">S/ ${inventario.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-danger fw-bold">S/ ${mermas.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    });
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No hay centros de producción registrados</td></tr>';
        renderPaginated('tbody-consolidado', []);
    } else {
        renderPaginated('tbody-consolidado', filas);
    }
}

function renderPrecios(data) {
    const tbody = document.getElementById('tbody-precios');
    if (!tbody) return;

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No hay productos con los filtros seleccionados</td></tr>';
        renderPaginated('tbody-precios', []);
        document.getElementById('resultado-label').textContent = '0 registro(s) encontrado(s)';
        return;
    }

    const filas = data.map(p => {
        const tipoBadge = p.tipo_precio === 'UIT' ? 'bg-indigo-lt' : 'bg-green-lt';
        const vigencia = p.tipo_precio === 'UIT'
            ? `UIT x ${parseFloat(p.porcentaje_uit || 0).toFixed(4)}`
            : (p.fecha_cambio_precio ? `Últ. cambio: ${new Date(p.fecha_cambio_precio).toLocaleDateString('es-PE')}` : 'Variable');

        return `<tr>
            <td>${p.id_producto}</td>
            <td>
                <div class="fw-bold text-dark">${escapeHtml(p.nombre_producto)}</div>
                ${p.nombre_cientifico ? `<small class="text-muted fst-italic">${escapeHtml(p.nombre_cientifico)}</small>` : ''}
            </td>
            <td>${escapeHtml(p.nombre_clase || '-')}</td>
            <td>${escapeHtml(p.nombre_centro || '-')}</td>
            <td class="text-center">
                <span class="badge ${tipoBadge}">${escapeHtml(p.tipo_precio)}</span>
            </td>
            <td class="text-end text-muted small">${vigencia}</td>
            <td class="text-end fw-bold text-success">S/ ${parseFloat(p.precio_unitario || 0).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    });
    renderPaginated('tbody-precios', filas);

    document.getElementById('resultado-label').textContent = `${data.length} registro(s) encontrado(s)`;
}

function renderPlanilla(data) {
    const thead = document.getElementById('thead-planilla');
    const tbody = document.getElementById('tbody-planilla');
    const tfoot = document.getElementById('tfoot-planilla');

    if (!data.length) {
        thead.innerHTML = '<tr><th class="text-center text-muted" colspan="3">Aplique filtros y haga clic en Generar</th></tr>';
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No hay compras de planilla con los filtros seleccionados</td></tr>';
        tfoot.innerHTML = '';
        renderPaginated('tbody-planilla', []);
        document.getElementById('resultado-label').textContent = '0 registro(s) encontrado(s)';
        return;
    }

    // 1. Extraer productos únicos y ordenarlos
    const productosMap = {};
    data.forEach(r => {
        if (!productosMap[r.id_producto]) {
            productosMap[r.id_producto] = {
                id: r.id_producto,
                nombre: r.nombre_producto,
                unidad: r.unidad_medida || 'KILO'
            };
        }
    });
    const productos = Object.values(productosMap).sort((a, b) => a.nombre.localeCompare(b.nombre));

    // 2. Agrupar por empleado y por producto (sumar cantidades, subtotales; promediar precio o tomar el último)
    const empleadosMap = {};
    data.forEach(r => {
        const key = r.id_cliente;
        if (!empleadosMap[key]) {
            empleadosMap[key] = {
                id_cliente: r.id_cliente,
                nombre: r.nombre_cliente,
                dni_ruc: r.dni_ruc,
                productos: {},
                total: 0
            };
        }
        const emp = empleadosMap[key];
        if (!emp.productos[r.id_producto]) {
            emp.productos[r.id_producto] = { cantidad: 0, precio: parseFloat(r.precio_unitario || 0), subtotal: 0 };
        }
        emp.productos[r.id_producto].cantidad += parseFloat(r.cantidad || 0);
        emp.productos[r.id_producto].subtotal += parseFloat(r.subtotal || 0);
        emp.total += parseFloat(r.subtotal || 0);
    });
    const empleados = Object.values(empleadosMap).sort((a, b) => a.nombre.localeCompare(b.nombre));

    // 3. Totales por producto y gran total
    const totalesProducto = {};
    productos.forEach(p => { totalesProducto[p.id] = { cantidad: 0, subtotal: 0 }; });
    let granTotal = 0;
    empleados.forEach(emp => {
        granTotal += emp.total;
        productos.forEach(p => {
            const d = emp.productos[p.id];
            if (d) {
                totalesProducto[p.id].cantidad += d.cantidad;
                totalesProducto[p.id].subtotal += d.subtotal;
            }
        });
    });

    // 4. Construir thead (dos niveles)
    let thProductos = '';
    let thSubcols = '';
    productos.forEach(p => {
        thProductos += `<th colspan="3" class="text-center border">${escapeHtml(p.nombre).toUpperCase()}<br><small class="fw-normal">${escapeHtml(p.unidad)}</small></th>`;
        thSubcols += `<th class="text-center border" style="min-width:55px;">CANT</th><th class="text-center border" style="min-width:55px;">COSTO</th><th class="text-center border" style="min-width:55px;">TOTAL</th>`;
    });
    thead.innerHTML = `<tr><th rowspan="2" class="text-center align-middle border" style="width:40px;">N°</th><th rowspan="2" class="text-center align-middle border" style="min-width:90px;">FECHA</th><th rowspan="2" class="text-center align-middle border" style="min-width:220px;">APELLIDOS Y NOMBRES</th>${thProductos}<th rowspan="2" class="text-center align-middle border" style="min-width:70px;">TOTAL</th></tr><tr>${thSubcols}</tr>`;

    // 5. Construir tbody
    const filasPlanilla = empleados.map((emp, idx) => {
        let celdas = '';
        productos.forEach(p => {
            const d = emp.productos[p.id];
            if (d && d.cantidad > 0) {
                celdas += `<td class="text-center border">${d.cantidad.toLocaleString('es-PE', {maximumFractionDigits:2})}</td>
                           <td class="text-end border">S/ ${d.precio.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                           <td class="text-end border">S/ ${d.subtotal.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>`;
            } else {
                celdas += `<td class="text-center border">-</td><td class="text-center border">-</td><td class="text-center border">-</td>`;
            }
        });
        const primeraFecha = data.find(r => r.id_cliente === emp.id_cliente)?.fecha_creacion || '';
        const fechaCorta = primeraFecha ? new Date(primeraFecha).toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit'}) : '-';
        return `<tr>
            <td class="text-center border">${idx + 1}</td>
            <td class="text-center border">${fechaCorta}</td>
            <td class="border">${escapeHtml(emp.nombre)}</td>
            ${celdas}
            <td class="text-end fw-bold border">S/ ${emp.total.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
        </tr>`;
    });
    renderPaginated('tbody-planilla', filasPlanilla);

    // 6. Construir tfoot
    let footCeldas = '';
    productos.forEach(p => {
        const t = totalesProducto[p.id];
        const precioProm = t.cantidad > 0 ? t.subtotal / t.cantidad : 0;
        footCeldas += `<td class="text-center border fw-bold">${t.cantidad.toLocaleString('es-PE', {maximumFractionDigits:2})}</td>
                       <td class="text-end border fw-bold">S/ ${precioProm.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                       <td class="text-end border fw-bold">S/ ${t.subtotal.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>`;
    });
    tfoot.innerHTML = `<tr>
        <td colspan="3" class="text-end fw-bold border">TOTALES</td>
        ${footCeldas}
        <td class="text-end fw-bold border">S/ ${granTotal.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
    </tr>`;

    document.getElementById('resultado-label').textContent = `${empleados.length} empleado(s) / ${data.length} línea(s)`;
}


// =============================================================
// EXPORTADOR EXCEL (CSV con BOM UTF-8)
// =============================================================
function exportarExcel() {
    if (tabActiva === 'planilla') {
        exportarExcelPlanilla();
        return;
    }
    const config = getTabConfig();
    const rows   = getTableRows(config.tableId);
    const bom    = '\uFEFF';
    const csv    = bom + [config.headers.join(';'), ...rows.map(r => r.map(c => `"${c}"`).join(';'))].join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href  = URL.createObjectURL(blob);
    link.download = `reporte_${tabActiva}_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
}

function exportarExcelPlanilla() {
    const data = window.ultimoReporteData || [];
    if (!data.length) return;

    // Reconstruir la matriz igual que renderPlanilla
    const productosMap = {};
    data.forEach(r => {
        if (!productosMap[r.id_producto]) {
            productosMap[r.id_producto] = { id: r.id_producto, nombre: r.nombre_producto, unidad: r.unidad_medida || 'KILO' };
        }
    });
    const productos = Object.values(productosMap).sort((a, b) => a.nombre.localeCompare(b.nombre));

    const empleadosMap = {};
    data.forEach(r => {
        const key = r.id_cliente;
        if (!empleadosMap[key]) {
            empleadosMap[key] = { nombre: r.nombre_cliente, productos: {}, total: 0 };
        }
        const emp = empleadosMap[key];
        if (!emp.productos[r.id_producto]) {
            emp.productos[r.id_producto] = { cantidad: 0, precio: parseFloat(r.precio_unitario || 0), subtotal: 0 };
        }
        emp.productos[r.id_producto].cantidad += parseFloat(r.cantidad || 0);
        emp.productos[r.id_producto].subtotal += parseFloat(r.subtotal || 0);
        emp.total += parseFloat(r.subtotal || 0);
    });
    const empleados = Object.values(empleadosMap).sort((a, b) => a.nombre.localeCompare(b.nombre));

    // Headers
    let headers = ['N°', 'FECHA', 'APELLIDOS Y NOMBRES'];
    productos.forEach(p => {
        headers.push(p.nombre + ' - CANT', p.nombre + ' - COSTO', p.nombre + ' - TOTAL');
    });
    headers.push('TOTAL');

    // Rows
    const rows = empleados.map((emp, idx) => {
        const primeraFecha = data.find(r => r.nombre_cliente === emp.nombre)?.fecha_creacion || '';
        const fechaCorta = primeraFecha ? new Date(primeraFecha).toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit'}) : '';
        const row = [idx + 1, fechaCorta, emp.nombre];
        productos.forEach(p => {
            const d = emp.productos[p.id];
            if (d && d.cantidad > 0) {
                row.push(d.cantidad, d.precio.toFixed(2), d.subtotal.toFixed(2));
            } else {
                row.push('', '', '');
            }
        });
        row.push(emp.total.toFixed(2));
        return row;
    });

    // Totals row
    const totalRow = ['', '', 'TOTALES'];
    let granTotal = 0;
    productos.forEach(p => {
        let cant = 0, sub = 0;
        empleados.forEach(emp => {
            const d = emp.productos[p.id];
            if (d) { cant += d.cantidad; sub += d.subtotal; }
        });
        const precioProm = cant > 0 ? sub / cant : 0;
        totalRow.push(cant, precioProm.toFixed(2), sub.toFixed(2));
        granTotal += sub;
    });
    totalRow.push(granTotal.toFixed(2));
    rows.push(totalRow);

    const bom = '\uFEFF';
    const csv = bom + [headers.join(';'), ...rows.map(r => r.map(c => `"${c}"`).join(';'))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `reporte_planilla_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
}

// =============================================================
// AUXILIARES DE DIBUJO jsPDF (LOGO, CABECERA Y PIE DE PÁGINA)
// =============================================================
function drawPECHLogoFallback(doc, x, y) {
    // Dibujo vectorial simple del logo de PECH (Azul y Verde)
    doc.setFillColor(0, 77, 153); // Azul Chavimochic
    doc.rect(x, y, 14, 9, 'F');
    doc.setFillColor(0, 149, 64); // Verde Chavimochic
    doc.rect(x + 14, y, 14, 9, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(6);
    doc.text('PECH', x + 14, y + 6.5, { align: 'center' });
}

function formatFecha(iso) {
    if (!iso) return '';
    const partes = String(iso).split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : iso;
}

function getPeriodoReporte() {
    const partes = [];
    const desde = document.getElementById('f_fecha_desde')?.value || '';
    const hasta = document.getElementById('f_fecha_hasta')?.value || '';
    if (desde && hasta) partes.push(`Período: ${formatFecha(desde)} al ${formatFecha(hasta)}`);
    else if (desde) partes.push(`Período: desde ${formatFecha(desde)}`);
    else if (hasta) partes.push(`Período: hasta ${formatFecha(hasta)}`);
    else partes.push('Período: completo');

    [['f_centro', 'Centro'], ['f_clase', 'Clase'], ['f_estado', 'Estado']].forEach(([id, etiqueta]) => {
        const el = document.getElementById(id);
        const txt = el && el.selectedOptions && el.selectedOptions[0] ? el.selectedOptions[0].text : '';
        if (txt && txt !== 'Todos' && txt !== 'Todas') {
            partes.push(`${etiqueta}: ${txt}`);
        }
    });
    return partes.join('  ·  ');
}

function drawPortraitHeaderFooter(doc, page, totalPages, totalCount) {
    // Logo local primero, luego navbar, luego fallback vectorial
    const imgLogo = document.getElementById('logo-pech-local')
                     || document.querySelector('.navbar-brand-image')
                     || document.querySelector('img[alt="PECH"]');

    // Encabezado institucional: franja blanca con doble línea PECH
    doc.setFillColor(255, 255, 255);
    doc.rect(0, 0, 210, 30, 'F');
    doc.setFillColor(0, 77, 153); // Azul PECH
    doc.rect(0, 30, 210, 1.4, 'F');
    doc.setFillColor(0, 149, 64); // Verde PECH
    doc.rect(0, 31.4, 210, 0.7, 'F');

    if (imgLogo && imgLogo.complete && imgLogo.naturalWidth > 0) {
        try {
            doc.addImage(imgLogo, 'PNG', 10, 6, 30, 9);
        } catch (e) {
            drawPECHLogoFallback(doc, 10, 6);
        }
    } else {
        drawPECHLogoFallback(doc, 10, 6);
    }

    // Título del sistema (azul PECH)
    doc.setTextColor(0, 77, 153);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.text('Sistema de Gestión y Desarrollo Agrícola PECH', 105, 9.5, { align: 'center' });

    // Título del reporte (verde PECH)
    doc.setTextColor(0, 149, 64);
    doc.setFontSize(11);
    doc.text('CATÁLOGO DE PRECIOS VIGENTES', 105, 14.5, { align: 'center' });

    // Período / filtros aplicados
    const periodo = getPeriodoReporte();
    doc.setTextColor(71, 85, 105);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.text(periodo, 105, 18.5, { align: 'center' });

    // Bloque de información derecha
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    const now = new Date();
    const dateStr = now.toLocaleDateString('es-PE') + ' ' + now.toLocaleTimeString('es-PE');
    doc.text(dateStr, 199, 9.5, { align: 'right' });
    doc.text(`Año : ${now.getFullYear()}`, 199, 13, { align: 'right' });
    doc.text(`Total Productos : ${totalCount}`, 199, 16.5, { align: 'right' });

    // ---- Pie de página ----
    doc.setFillColor(248, 249, 250);
    doc.rect(10, 268, 190, 16, 'F');
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.3);
    doc.line(10, 268, 200, 268);

    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(0, 0, 0);
    doc.text('UBICANOS', 14, 272);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('Campamento San José Km. 513\nPanamericana Norte\nProvincia de Virú\nRegión La Libertad.', 14, 275.5);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.text('HORARIO DE ATENCIÓN', 85, 272);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('De lunes a viernes:\n09:00 am a 03:00 pm', 85, 275.5);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.text('TELEFONOS', 150, 272);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('044-272286\nAnexo 2030 Subgerencia', 150, 275.5);

    // Paginación
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(0, 77, 153);
    doc.text(`${page}/${totalPages}`, 105, 281.5, { align: 'center' });
}

function drawLandscapeHeaderFooter(doc, page, totalPages, totalCount, tituloReporte) {
    // Logo local primero, luego navbar, luego fallback vectorial
    const imgLogo = document.getElementById('logo-pech-local')
                     || document.querySelector('.navbar-brand-image')
                     || document.querySelector('img[alt="PECH"]');

    // Encabezado institucional: franja blanca con doble línea PECH
    doc.setFillColor(255, 255, 255);
    doc.rect(0, 0, 297, 30, 'F');
    doc.setFillColor(0, 77, 153); // Azul PECH
    doc.rect(0, 30, 297, 1.4, 'F');
    doc.setFillColor(0, 149, 64); // Verde PECH
    doc.rect(0, 31.4, 297, 0.7, 'F');

    if (imgLogo && imgLogo.complete && imgLogo.naturalWidth > 0) {
        try {
            doc.addImage(imgLogo, 'PNG', 10, 6, 30, 9);
        } catch (e) {
            drawPECHLogoFallback(doc, 10, 6);
        }
    } else {
        drawPECHLogoFallback(doc, 10, 6);
    }

    // Título del sistema (azul PECH)
    doc.setTextColor(0, 77, 153);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.text('Sistema de Gestión y Desarrollo Agrícola PECH', 148.5, 9.5, { align: 'center' });

    // Título del reporte (verde PECH)
    doc.setTextColor(0, 149, 64);
    doc.setFontSize(11);
    doc.text(String(tituloReporte).toUpperCase(), 148.5, 14.5, { align: 'center' });

    // Período / filtros aplicados
    const periodo = getPeriodoReporte();
    doc.setTextColor(71, 85, 105);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.text(periodo, 148.5, 18.5, { align: 'center' });

    // Bloque de información derecha
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    const now = new Date();
    const dateStr = now.toLocaleDateString('es-PE') + ' ' + now.toLocaleTimeString('es-PE');
    doc.text(dateStr, 286, 9.5, { align: 'right' });
    doc.text(`Año : ${now.getFullYear()}`, 286, 13, { align: 'right' });
    doc.text(`Total Registros : ${totalCount}`, 286, 16.5, { align: 'right' });

    // ---- Pie de página ----
    doc.setFillColor(248, 249, 250);
    doc.rect(10, 186, 277, 16, 'F');
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.3);
    doc.line(10, 186, 287, 186);

    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(0, 0, 0);
    doc.text('UBICANOS', 14, 190);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('Campamento San José Km. 513\nPanamericana Norte\nProvincia de Virú\nRegión La Libertad.', 14, 193.5);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.text('HORARIO DE ATENCIÓN', 120, 190);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('De lunes a viernes:\n09:00 am a 03:00 pm', 120, 193.5);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.text('TELEFONOS', 210, 190);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('044-272286\nAnexo 2030 Subgerencia', 210, 193.5);

    // Paginación
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(0, 77, 153);
    doc.text(`${page}/${totalPages}`, 148.5, 199, { align: 'center' });
}

// =============================================================
// EXPORTADOR PDF (jsPDF + AutoTable)
// =============================================================
function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const config = getTabConfig();
    const data = window.ultimoReporteData || [];

    if (tabActiva === 'precios') {
        // -------------------------------------------------------------
        // FORMATO DE PRECIOS: Vertical (A4 Portrait), 2 Columnas
        // -------------------------------------------------------------
        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        
        let currentY = 40;
        let currentCol = 0;
        const colX = [14, 108];
        const colWidth = 88;
        const maxY = 258; // Margen antes del footer
        let globalIndex = 1;

        // Agrupar por Clase
        const grouped = {};
        data.forEach(p => {
            const clase = p.nombre_clase || 'OTROS';
            if (!grouped[clase]) grouped[clase] = [];
            grouped[clase].push(p);
        });

        // Función auxiliar para control de espacio
        function checkSpace(neededHeight) {
            if (currentY + neededHeight > maxY) {
                if (currentCol === 0) {
                    currentCol = 1;
                    currentY = 40;
                } else {
                    doc.addPage();
                    currentCol = 0;
                    currentY = 40;
                }
            }
        }

        // Iterar sobre las clases
        for (const [clase, productos] of Object.entries(grouped)) {
            // Validar espacio para Cabecera Clase (6) + Cabecera Tabla (5) + Primer Fila (5.5) = 16.5mm
            checkSpace(16.5);

            // 1. Caja de Título de la Clase
            doc.setFillColor(0, 77, 153); // Azul PECH
            doc.setDrawColor(0, 77, 153);
            doc.setLineWidth(0.2);
            doc.rect(colX[currentCol], currentY, colWidth, 6, 'FD');
            
            doc.setTextColor(255, 255, 255);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(8.5);
            doc.text(clase.toUpperCase(), colX[currentCol] + 2, currentY + 4.2);

            // 2. Cabeceras de Columnas de la Tabla
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(148, 163, 184); // slate-400
            doc.rect(colX[currentCol], currentY + 6, colWidth, 5, 'FD');
            
            doc.setTextColor(15, 23, 42);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7.5);
            doc.text('Item', colX[currentCol] + 1, currentY + 6 + 3.8);
            doc.text('Producto', colX[currentCol] + 10, currentY + 6 + 3.8);
            doc.text('Tipo', colX[currentCol] + 62, currentY + 6 + 3.8);
            doc.text('Precio', colX[currentCol] + 78, currentY + 6 + 3.8);

            currentY += 11;

            // 3. Filas de Productos
            productos.forEach(p => {
                const lines = doc.splitTextToSize(p.nombre_producto, 50);
                const rowHeight = Math.max(5.5, lines.length * 3.2 + 1);

                checkSpace(rowHeight);

                // Dibujar bordes de celda
                doc.setDrawColor(203, 213, 225); // slate-300
                doc.rect(colX[currentCol], currentY, colWidth, rowHeight, 'D');
                
                // Líneas divisorias verticales
                doc.line(colX[currentCol] + 9, currentY, colX[currentCol] + 9, currentY + rowHeight);
                doc.line(colX[currentCol] + 61, currentY, colX[currentCol] + 61, currentY + rowHeight);
                doc.line(colX[currentCol] + 77, currentY, colX[currentCol] + 77, currentY + rowHeight);

                // Escribir contenido
                doc.setTextColor(51, 65, 85); // slate-700
                doc.setFontSize(7);

                // Centrado vertical matemático
                const textY = currentY + (rowHeight / 2) + 0.8;

                // Item (globalIndex correlativo)
                doc.setFont('helvetica', 'normal');
                doc.text(String(globalIndex++), colX[currentCol] + 4.5, textY, { align: 'center' });

                // Producto (multi-línea)
                lines.forEach((line, idx) => {
                    const lineY = currentY + 3.2 + (idx * 3.2);
                    doc.text(line, colX[currentCol] + 10.5, lineY);
                });

                // Tipo (Unidad Medida)
                const tipo = p.unidad_medida || '-';
                doc.text(tipo, colX[currentCol] + 62.5, textY);

                // Precio (Right aligned)
                const precio = parseFloat(p.precio_unitario || 0).toFixed(2);
                doc.text(precio, colX[currentCol] + 86.5, textY, { align: 'right' });

                currentY += rowHeight;
            });
        }

        // Estampar cabeceras y pies de página en segunda pasada
        const totalPages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
            drawPortraitHeaderFooter(doc, i, totalPages, data.length);
        }

        doc.save(`reporte_${tabActiva}_${new Date().toISOString().slice(0,10)}.pdf`);

    } else if (tabActiva === 'planilla') {
        // -------------------------------------------------------------
        // FORMATO PLANILLA: Horizontal (A4 Landscape), tabla pivote
        // -------------------------------------------------------------
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

        // Reconstruir matriz
        const data = window.ultimoReporteData || [];
        const productosMap = {};
        data.forEach(r => {
            if (!productosMap[r.id_producto]) {
                productosMap[r.id_producto] = { id: r.id_producto, nombre: r.nombre_producto, unidad: r.unidad_medida || 'KILO' };
            }
        });
        const productos = Object.values(productosMap).sort((a, b) => a.nombre.localeCompare(b.nombre));

        const empleadosMap = {};
        data.forEach(r => {
            const key = r.id_cliente;
            if (!empleadosMap[key]) {
                empleadosMap[key] = { nombre: r.nombre_cliente, productos: {}, total: 0 };
            }
            const emp = empleadosMap[key];
            if (!emp.productos[r.id_producto]) {
                emp.productos[r.id_producto] = { cantidad: 0, precio: parseFloat(r.precio_unitario || 0), subtotal: 0 };
            }
            emp.productos[r.id_producto].cantidad += parseFloat(r.cantidad || 0);
            emp.productos[r.id_producto].subtotal += parseFloat(r.subtotal || 0);
            emp.total += parseFloat(r.subtotal || 0);
        });
        const empleados = Object.values(empleadosMap).sort((a, b) => a.nombre.localeCompare(b.nombre));

        // Head
        const headRow = ['N°', 'FECHA', 'APELLIDOS Y NOMBRES'];
        productos.forEach(p => {
            headRow.push(p.nombre.toUpperCase() + '\nCANT', p.nombre.toUpperCase() + '\nCOSTO', p.nombre.toUpperCase() + '\nTOTAL');
        });
        headRow.push('TOTAL');

        // Body
        const body = empleados.map((emp, idx) => {
            const primeraFecha = data.find(r => r.nombre_cliente === emp.nombre)?.fecha_creacion || '';
            const fechaCorta = primeraFecha ? new Date(primeraFecha).toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit'}) : '-';
            const row = [String(idx + 1), fechaCorta, emp.nombre];
            productos.forEach(p => {
                const d = emp.productos[p.id];
                if (d && d.cantidad > 0) {
                    row.push(d.cantidad.toLocaleString('es-PE', {maximumFractionDigits:2}), d.precio.toFixed(2), d.subtotal.toFixed(2));
                } else {
                    row.push('-', '-', '-');
                }
            });
            row.push(emp.total.toFixed(2));
            return row;
        });

        // Footer (totales)
        const totalRow = ['', '', 'TOTALES'];
        let granTotal = 0;
        productos.forEach(p => {
            let cant = 0, sub = 0;
            empleados.forEach(emp => {
                const d = emp.productos[p.id];
                if (d) { cant += d.cantidad; sub += d.subtotal; }
            });
            const precioProm = cant > 0 ? sub / cant : 0;
            totalRow.push(cant.toLocaleString('es-PE', {maximumFractionDigits:2}), precioProm.toFixed(2), sub.toFixed(2));
            granTotal += sub;
        });
        totalRow.push(granTotal.toFixed(2));
        body.push(totalRow);

        // Column styles
        const colStyles = { 0: { cellWidth: 8 }, 1: { cellWidth: 18 }, 2: { cellWidth: 50 } };
        let colIdx = 3;
        productos.forEach(() => {
            colStyles[colIdx++] = { cellWidth: 18, halign: 'center' };
            colStyles[colIdx++] = { cellWidth: 20, halign: 'right' };
            colStyles[colIdx++] = { cellWidth: 22, halign: 'right' };
        });
        colStyles[colIdx] = { cellWidth: 22, halign: 'right' };

        doc.autoTable({
            head: [headRow],
            body: body,
            startY: 34,
            margin: { top: 34, bottom: 26, left: 10, right: 10 },
            styles: { fontSize: 6.5, cellPadding: 1.5, overflow: 'linebreak' },
            headStyles: { fillColor: [0, 77, 153], textColor: 255, fontStyle: 'bold', halign: 'center' },
            columnStyles: colStyles
        });

        // Estampar cabeceras y pies de página en segunda pasada
        const totalPages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
            drawLandscapeHeaderFooter(doc, i, totalPages, body.length, 'Relación del Personal de Planilla');
        }

        doc.save(`reporte_planilla_${new Date().toISOString().slice(0,10)}.pdf`);

    } else {
        // -------------------------------------------------------------
        // FORMATO GENERAL: Horizontal (A4 Landscape), 1 Columna AutoTable
        // -------------------------------------------------------------
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const rows = getTableRows(config.tableId);
        const totRow = getTotalesFromFooter(config.tableId, config.headers.length);
        if (totRow) rows.push(totRow);

        // Alinear a la derecha las columnas numéricas según su encabezado
        const columnStyles = {};
        config.headers.forEach((h, i) => {
            if (/(total|precio|stock|saldo|monto|acumulado|ventas|donaciones|inventario|mermas|transacciones|proformas|cantidad|valor)/i.test(h)) {
                columnStyles[i] = { halign: 'right' };
            }
        });

        doc.autoTable({
            head:       [config.headers],
            body:       rows,
            startY:     34, // Margen de cabecera
            margin:     { top: 34, bottom: 26, left: 10, right: 10 },
            styles:     { fontSize: 7.5, cellPadding: 2.5, textColor: [30, 41, 59] },
            headStyles: { fillColor: [0, 77, 153], textColor: 255, fontStyle: 'bold', halign: 'center' },
            alternateRowStyles: { fillColor: [241, 245, 249] },
            columnStyles: columnStyles,
        });

        // Estampar cabeceras y pies de página en segunda pasada
        const totalPages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
            drawLandscapeHeaderFooter(doc, i, totalPages, rows.length, config.titulo);
        }

        doc.save(`reporte_${tabActiva}_${new Date().toISOString().slice(0,10)}.pdf`);
    }
}

// =============================================================
// HELPERS EXPORTADORES
// =============================================================
function getTabConfig() {
    const configs = {
        ventas:       { titulo: 'Ventas y Facturación',      tableId: 'tabla-ventas',       headers: ['#','Fecha','Cliente','Centro','Método','Comprobante','Estado','Total (S/)'] },
        inventario:   { titulo: 'Valorización de Inventario',tableId: 'tabla-inventario',   headers: ['Producto','Clase','Centro','Lote','Antigüedad','Stock','Tipo Precio','Precio Unit.','Valor Total (S/)'] },
        mermas:       { titulo: 'Mermas y Pérdidas',         tableId: 'tabla-mermas',       headers: ['Fecha','Producto','Clase','Centro','Lote','Cantidad','Precio Unit.','Valor Pérdida (S/)'] },
        'clientes-rep': { titulo: 'Clientes y Recaudación',  tableId: 'tabla-clientes-rep', headers: ['ID','DNI/RUC','Cliente','Tipo Cliente','Transacciones','Ventas (S/)','Donaciones (S/)','Acumulado (S/)'] },
        consolidado:  { titulo: 'Consolidado por Centro',    tableId: 'tabla-consolidado',  headers: ['ID','Centro','Encargado','Ventas (S/)','Donaciones (S/)','Inventario (S/)','Mermas (S/)'] },
        precios:      { titulo: 'Catálogo de Precios',        tableId: 'tabla-precios',      headers: ['ID','Producto','Clase','Centro','Tipo Precio','Vigencia UIT / Histórico','Precio Vigente (S/)'] },
        planilla:     { titulo: 'Relación del Personal de Planilla', tableId: 'tabla-planilla', headers: ['N°','Fecha','Empleado','Productos','Total (S/)'] }
    };
    return configs[tabActiva] || configs.ventas;
}

function getTableRows(tableId) {
    const table  = document.getElementById(tableId);
    const tbody  = table ? table.querySelector('tbody') : null;
    if (!tbody) return [];

    // Con paginación activa, el DOM solo muestra la página actual.
    // Usar el conjunto completo de filas del estado de paginación para no perder datos.
    const tbodyId = tbody.id;
    const st = paginationState && paginationState[tbodyId];
    const rowsHtml = st
        ? st.rows
        : Array.from(tbody.querySelectorAll('tr')).map(r => r.outerHTML);

    return rowsHtml.map(html => {
        const tr = document.createElement('tr');
        tr.innerHTML = html;
        return Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim().replace(/\n+/g, ' '));
    });
}

// Normaliza la fila de totales del tfoot al ancho de columnas del reporte:
// etiqueta en la 1ª columna y valor en la última (evita colspans desalineados)
function getTotalesFromFooter(tableId, numCols) {
    const table = document.getElementById(tableId);
    const tr = table && table.querySelector('tfoot tr');
    if (!tr) return null;
    const cells = Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim().replace(/\n+/g, ' '));
    if (!cells.length) return null;
    const row = new Array(Math.max(numCols, 1)).fill('');
    row[0] = cells[0] || '';
    if (numCols > 1) row[numCols - 1] = cells[cells.length - 1] || '';
    return row;
}
</script>
