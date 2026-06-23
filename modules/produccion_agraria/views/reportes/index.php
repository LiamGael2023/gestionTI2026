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
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/reportes.css">

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

    <!-- ENCABEZADO -->
    <div class="card reporte-header text-white mb-4 border-0">
        <div class="card-body py-4">
            <div class="row align-items-center">
                <div class="col">
                    <div class="text-uppercase fw-bold fs-5 mb-1" style="color: rgba(255,255,255,0.85);">
                        <i class="ti ti-leaf me-2" style="color: rgba(255,255,255,0.7);"></i>
                        Sistema de Seguimiento y control de Productos Agricolas
                    </div>
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
        <!-- Card 4: Vouchers -->
        <div class="col-md-4">
            <div class="card report-card report-card-vouchers text-center p-4 cursor-pointer" onclick="abrirReporte('vouchers')">
                <div>
                    <div class="mb-3"><span class="avatar-md bg-warning-lt"><i class="ti ti-file-analytics fs-2 mb-0"></i></span></div>
                    <h3 class="mb-1 text-dark fw-bold">Conciliación de Vouchers</h3>
                    <p class="text-muted small mb-0">Monitoreo de depósitos bancarios vs. consumido en ventas.</p>
                </div>
                <div class="mt-4">
                    <button class="btn btn-outline-warning btn-abrir-rep w-100 py-2">
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
             TAB 4: CONCILIACIÓN DE VOUCHERS
        ===================================================== -->
        <div class="tab-pane fade p-4" id="tab-vouchers">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card kpi-card kpi-ventas">
                        <div class="card-body">
                            <div class="text-muted small">Total Depósitos en Vouchers / Resoluciones</div>
                            <div class="h3 mb-0 fw-bold text-success" id="kpi-vouchers-total">
                                S/ <?php 
                                    $sumVouchers = array_sum(array_column($vouchers_init, 'monto_total'));
                                    echo number_format($sumVouchers, 2, '.', ','); 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card kpi-card kpi-count">
                        <div class="card-body">
                            <div class="text-muted small">Total Saldo Libre (Por Conciliar)</div>
                            <div class="h3 mb-0 fw-bold text-primary" id="kpi-vouchers-saldo">
                                S/ <?php 
                                    $sumSaldo = array_sum(array_column($vouchers_init, 'saldo_restante'));
                                    echo number_format($sumSaldo, 2, '.', ','); 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="tabla-vouchers">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>N° Operación / Resolución</th>
                            <th>Fecha de Depósito</th>
                            <th class="text-center">Proformas Ligadas</th>
                            <th class="text-end">Monto del Boucher (S/)</th>
                            <th class="text-end">Monto Consumido (S/)</th>
                            <th class="text-end">Saldo Restante (S/)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers_init as $v): ?>
                        <tr>
                            <td><?php echo $v['id_voucher']; ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($v['num_operation']); ?></td>
                            <td><?php echo htmlspecialchars($v['fecha_deposito']); ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?php echo htmlspecialchars($v['total_proformas']); ?></span></td>
                            <td class="text-end"><?php echo number_format($v['monto_total'], 2, '.', ','); ?></td>
                            <td class="text-end text-success fw-semibold"><?php echo number_format($v['monto_asignado'], 2, '.', ','); ?></td>
                            <td class="text-end fw-bold <?php echo ($v['saldo_restante'] < 0) ? 'text-danger' : 'text-primary'; ?>">
                                S/ <?php echo number_format($v['saldo_restante'], 2, '.', ','); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vouchers_init)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay registros de vouchers en el período seleccionado</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                    <tbody>
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
                    <tbody>
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
    vouchers: {
        titulo: 'Reporte de Conciliación de Vouchers y Depósitos',
        filters: ['fecha-desde', 'fecha-hasta'],
        tabId: 'tab-vouchers'
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
    const allTabs = ['ventas', 'inventario', 'mermas', 'vouchers', 'clientes-rep', 'consolidado', 'precios'];
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
    } else if (tabActiva === 'vouchers') {
        promise = fetch(url + 'action=vouchers_report_data&' + qs)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.ultimoReporteData = res.data;
                    renderVouchers(res.data);
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
function renderVentas(data, kpis) {
    // KPIs
    document.getElementById('kpi-monto').textContent  = 'S/ ' + parseFloat(kpis.monto_total || 0).toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-count').textContent  = parseInt(kpis.total_transacciones || 0).toLocaleString();
    document.getElementById('kpi-ticket').textContent = 'S/ ' + parseFloat(kpis.ticket_promedio || 0).toLocaleString('es-PE', {minimumFractionDigits:2});

    let totalMostrado = 0;
    const tbody = document.getElementById('tbody-ventas');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay ventas con los filtros seleccionados</td></tr>';
        document.getElementById('subtotal-ventas').textContent = 'S/ 0.00';
        return;
    }
    tbody.innerHTML = data.map(v => {
        totalMostrado += parseFloat(v.total || 0);
        const fecha = new Date(v.fecha_creacion).toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
        const comp  = v.serie_comprobante ? `${v.serie_comprobante}-${v.correlativo_comprobante}` : '-';
        return `<tr>
            <td><code>${v.id_transaccion}</code></td>
            <td>${fecha}</td>
            <td><div>${v.nombre_cliente || '-'}</div><small class="text-muted">${v.documento_cliente || ''}</small></td>
            <td>${v.nombre_centro || '-'}</td>
            <td>${v.metodo_pago || '-'}</td>
            <td><small>${comp}</small></td>
            <td><span class="badge badge-${v.estado}">${v.estado}</span></td>
            <td class="text-end fw-bold">${parseFloat(v.total).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    }).join('');
    document.getElementById('subtotal-ventas').textContent = 'S/ ' + totalMostrado.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('resultado-label').textContent = `${data.length} registro(s) encontrado(s)`;
}

function renderInventario(data) {
    let totalValor = 0;
    const tbody = document.getElementById('tbody-inventario');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No hay inventario con stock disponible</td></tr>';
        document.getElementById('total-inventario').textContent = 'S/ 0.00';
        document.getElementById('kpi-valor-inventario').textContent = 'S/ 0.00';
        document.getElementById('kpi-lotes-activos').textContent = '0';
        return;
    }
    tbody.innerHTML = data.map(i => {
        totalValor += parseFloat(i.valor_total_lote || 0);
        const diasClass = i.antiguedad_dias > 20 ? 'text-danger fw-bold' : (i.antiguedad_dias > 7 ? 'text-warning' : 'text-success');
        const stockBadge = i.stock_actual < 10 ? 'bg-danger' : 'bg-success';
        const fecLote = i.fecha_lote ? new Date(i.fecha_lote).toLocaleDateString('es-PE') : '-';
        return `<tr>
            <td><div class="fw-semibold">${i.nombre_producto}</div>${i.nombre_cientifico ? `<small class="text-muted fst-italic">${i.nombre_cientifico}</small>` : ''}</td>
            <td>${i.nombre_clase || '-'}</td>
            <td>${i.nombre_centro || '-'}</td>
            <td><code>${i.codigo_lote}</code></td>
            <td><span class="${diasClass}"><i class="ti ti-clock me-1"></i>${i.antiguedad_dias} días</span></td>
            <td class="text-center"><span class="badge ${stockBadge}">${parseInt(i.stock_actual).toLocaleString()} ${i.unidad_medida}</span></td>
            <td class="text-center"><span class="badge bg-light text-dark">${i.tipo_precio}</span></td>
            <td class="text-end">${parseFloat(i.precio_unitario).toFixed(4)}</td>
            <td class="text-end fw-bold text-success">${parseFloat(i.valor_total_lote).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    }).join('');
    document.getElementById('total-inventario').textContent = 'S/ ' + totalValor.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-valor-inventario').textContent = 'S/ ' + totalValor.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-lotes-activos').textContent = data.length.toLocaleString();
}

function renderMermas(data) {
    let totalPerdida = 0;
    const tbody = document.getElementById('tbody-mermas');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay registros de merma en el período seleccionado</td></tr>';
        document.getElementById('total-mermas').textContent = 'S/ 0.00';
        document.getElementById('kpi-valor-mermas').textContent = 'S/ 0.00';
        document.getElementById('kpi-count-mermas').textContent = '0';
        return;
    }
    tbody.innerHTML = data.map(m => {
        totalPerdida += parseFloat(m.valor_perdida || 0);
        const fecha = new Date(m.fecha).toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
        return `<tr>
            <td>${fecha}</td>
            <td class="fw-semibold">${m.nombre_producto}</td>
            <td>${m.nombre_clase || '-'}</td>
            <td>${m.nombre_centro || '-'}</td>
            <td><code>${m.codigo_lote}</code></td>
            <td class="text-center"><span class="badge bg-danger">${parseInt(m.cantidad_merma).toLocaleString()}</span></td>
            <td class="text-end">${parseFloat(m.precio_unitario).toFixed(4)}</td>
            <td class="text-end fw-bold text-danger">${parseFloat(m.valor_perdida).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    }).join('');
    document.getElementById('total-mermas').textContent = 'S/ ' + totalPerdida.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-valor-mermas').textContent = 'S/ ' + totalPerdida.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-count-mermas').textContent = data.length.toLocaleString();
}

// Gráficos removidos - migración planeada para el Dashboard principal

function renderVouchers(data) {
    const tbody = document.querySelector('#tabla-vouchers tbody');
    if (!tbody) return;
    
    let totalMonto = 0;
    let totalSaldo = 0;
    
    tbody.innerHTML = data.map(v => {
        const monto = parseFloat(v.monto_total);
        const asignado = parseFloat(v.monto_assigned || v.monto_asignado || 0);
        const saldo = parseFloat(v.saldo_restante);
        
        totalMonto += monto;
        totalSaldo += saldo;
        
        const saldoClass = saldo < 0 ? 'text-danger' : 'text-primary';
        
        return `<tr>
            <td>${v.id_voucher}</td>
            <td class="fw-semibold">${v.num_operation || '-'}</td>
            <td>${v.fecha_deposito || '-'}</td>
            <td class="text-center"><span class="badge bg-secondary">${v.total_proformas}</span></td>
            <td class="text-end">${monto.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
            <td class="text-end text-success fw-semibold">${asignado.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
            <td class="text-end fw-bold ${saldoClass}">S/ ${saldo.toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
        </tr>`;
    }).join('');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No hay registros de vouchers en el período seleccionado</td></tr>';
    }
    
    document.getElementById('kpi-vouchers-total').textContent = 'S/ ' + totalMonto.toLocaleString('es-PE', {minimumFractionDigits:2});
    document.getElementById('kpi-vouchers-saldo').textContent = 'S/ ' + totalSaldo.toLocaleString('es-PE', {minimumFractionDigits:2});
}

function renderClientes(data) {
    const tbody = document.querySelector('#tabla-clientes-rep tbody');
    if (!tbody) return;
    
    tbody.innerHTML = data.map(c => {
        const transacciones = parseInt(c.total_transacciones);
        const ventas = parseFloat(c.total_ventas);
        const donaciones = parseFloat(c.total_donaciones);
        const acumulado = parseFloat(c.total_acumulado);
        
        const badgeClass = c.tipo_cliente === 'Planilla' ? 'bg-purple-lt' : 'bg-blue-lt';
        
        return `<tr>
            <td>${c.id_cliente}</td>
            <td>${c.dni_ruc || '-'}</td>
            <td class="fw-semibold">${c.nombre_rs}</td>
            <td>
                <span class="badge ${badgeClass}">${c.tipo_cliente}</span>
            </td>
            <td class="text-center"><span class="badge bg-secondary">${transacciones}</span></td>
            <td class="text-end text-success">${ventas.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-warning">${donaciones.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end fw-bold text-primary">S/ ${acumulado.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    }).join('');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay clientes registrados</td></tr>';
    }
}

function renderConsolidado(data) {
    const tbody = document.querySelector('#tabla-consolidado tbody');
    if (!tbody) return;
    
    tbody.innerHTML = data.map(con => {
        const ventas = parseFloat(con.total_ventas);
        const donaciones = parseFloat(con.total_donaciones);
        const inventario = parseFloat(con.valor_inventario);
        const mermas = parseFloat(con.valor_mermas);
        
        return `<tr>
            <td>${con.id_centro}</td>
            <td class="fw-bold text-dark">${con.nombre_centro}</td>
            <td>${con.encargado || '-'}</td>
            <td class="text-end text-success fw-semibold">S/ ${ventas.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-warning fw-semibold">S/ ${donaciones.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-primary fw-semibold">S/ ${inventario.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
            <td class="text-end text-danger fw-bold">S/ ${mermas.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    }).join('');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No hay centros de producción registrados</td></tr>';
    }
}

function renderPrecios(data) {
    const tbody = document.getElementById('tbody-precios');
    if (!tbody) return;

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No hay productos con los filtros seleccionados</td></tr>';
        document.getElementById('resultado-label').textContent = '0 registro(s) encontrado(s)';
        return;
    }

    tbody.innerHTML = data.map(p => {
        const tipoBadge = p.tipo_precio === 'UIT' ? 'bg-indigo-lt' : 'bg-green-lt';
        const vigencia = p.tipo_precio === 'UIT'
            ? `UIT x ${parseFloat(p.porcentaje_uit || 0).toFixed(4)}`
            : (p.fecha_cambio_precio ? `Últ. cambio: ${new Date(p.fecha_cambio_precio).toLocaleDateString('es-PE')}` : 'Variable');

        return `<tr>
            <td>${p.id_producto}</td>
            <td>
                <div class="fw-bold text-dark">${p.nombre_producto}</div>
                ${p.nombre_cientifico ? `<small class="text-muted fst-italic">${p.nombre_cientifico}</small>` : ''}
            </td>
            <td>${p.nombre_clase || '-'}</td>
            <td>${p.nombre_centro || '-'}</td>
            <td class="text-center">
                <span class="badge ${tipoBadge}">${p.tipo_precio}</span>
            </td>
            <td class="text-end text-muted small">${vigencia}</td>
            <td class="text-end fw-bold text-success">S/ ${parseFloat(p.precio_unitario || 0).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>
        </tr>`;
    }).join('');

    document.getElementById('resultado-label').textContent = `${data.length} registro(s) encontrado(s)`;
}


// =============================================================
// EXPORTADOR EXCEL (CSV con BOM UTF-8)
// =============================================================
function exportarExcel() {
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

function drawPortraitHeaderFooter(doc, page, totalPages, totalCount) {
    // Logo
    const imgLogo = document.querySelector('.navbar-brand-image') || document.querySelector('img[alt="PECH"]');
    if (imgLogo) {
        try {
            doc.addImage(imgLogo, 'PNG', 14, 8, 28, 9);
        } catch (e) {
            drawPECHLogoFallback(doc, 14, 8);
        }
    } else {
        drawPECHLogoFallback(doc, 14, 8);
    }
    
    // Título Principal
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('Sistema Desarrollo Agricola', 105, 14, { align: 'center' });
    
    // Banner Verde de Subtítulo
    doc.setFillColor(140, 181, 63); // Verde Oliva #8cb53f
    doc.rect(55, 18, 100, 6.5, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9.5);
    doc.text('Precios Productos', 105, 22.5, { align: 'center' });
    
    // Bloque de información derecha
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    const now = new Date();
    const dateStr = now.toLocaleDateString('es-PE') + ' ' + now.toLocaleTimeString('es-PE');
    doc.text(dateStr, 196, 11, { align: 'right' });
    doc.text(`Año : ${now.getFullYear()}`, 196, 15, { align: 'right' });
    doc.text(`Total Productos : ${totalCount}`, 196, 19, { align: 'right' });
    
    // Línea divisoria pie de página
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.2);
    doc.line(14, 268, 196, 268);
    
    // Información institucional en columnas
    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'bold');
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
    doc.text(`${page}/${totalPages}`, 105, 288, { align: 'center' });
}

function drawLandscapeHeaderFooter(doc, page, totalPages, totalCount, tituloReporte) {
    // Logo
    const imgLogo = document.querySelector('.navbar-brand-image') || document.querySelector('img[alt="PECH"]');
    if (imgLogo) {
        try {
            doc.addImage(imgLogo, 'PNG', 14, 8, 28, 9);
        } catch (e) {
            drawPECHLogoFallback(doc, 14, 8);
        }
    } else {
        drawPECHLogoFallback(doc, 14, 8);
    }
    
    // Título Principal
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('Sistema Desarrollo Agricola', 148.5, 14, { align: 'center' });
    
    // Banner Verde de Subtítulo
    doc.setFillColor(140, 181, 63); // Verde Oliva #8cb53f
    doc.rect(98, 18, 100, 6.5, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9.5);
    doc.text(tituloReporte, 148.5, 22.5, { align: 'center' });
    
    // Bloque de información derecha
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    const now = new Date();
    const dateStr = now.toLocaleDateString('es-PE') + ' ' + now.toLocaleTimeString('es-PE');
    doc.text(dateStr, 283, 11, { align: 'right' });
    doc.text(`Año : ${now.getFullYear()}`, 283, 15, { align: 'right' });
    doc.text(`Total Registros : ${totalCount}`, 283, 19, { align: 'right' });
    
    // Línea divisoria pie de página
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.2);
    doc.line(14, 182, 283, 182);
    
    // Información institucional en columnas
    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'bold');
    doc.text('UBICANOS', 14, 186);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('Campamento San José Km. 513\nPanamericana Norte\nProvincia de Virú\nRegión La Libertad.', 14, 189.5);
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.text('HORARIO DE ATENCIÓN', 120, 186);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('De lunes a viernes:\n09:00 am a 03:00 pm', 120, 189.5);
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.text('TELEFONOS', 210, 186);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.text('044-272286\nAnexo 2030 Subgerencia', 210, 189.5);
    
    // Paginación
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.text(`${page}/${totalPages}`, 148.5, 202, { align: 'center' });
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
        
        let currentY = 32;
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
                    currentY = 32;
                } else {
                    doc.addPage();
                    currentCol = 0;
                    currentY = 32;
                }
            }
        }

        // Iterar sobre las clases
        for (const [clase, productos] of Object.entries(grouped)) {
            // Validar espacio para Cabecera Clase (6) + Cabecera Tabla (5) + Primer Fila (5.5) = 16.5mm
            checkSpace(16.5);

            // 1. Caja de Título de la Clase
            doc.setFillColor(241, 245, 249); // slate-100
            doc.setDrawColor(203, 213, 225); // slate-300
            doc.setLineWidth(0.2);
            doc.rect(colX[currentCol], currentY, colWidth, 6, 'FD');
            
            doc.setTextColor(15, 23, 42); // slate-900
            doc.setFont('helvetica', 'bolditalic');
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

    } else {
        // -------------------------------------------------------------
        // FORMATO GENERAL: Horizontal (A4 Landscape), 1 Columna AutoTable
        // -------------------------------------------------------------
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const rows = getTableRows(config.tableId);

        doc.autoTable({
            head:       [config.headers],
            body:       rows,
            startY:     32, // Margen de cabecera
            margin:     { top: 32, bottom: 25, left: 14, right: 14 },
            styles:     { fontSize: 7.5, cellPadding: 2.5 },
            headStyles: { fillColor: [0, 77, 153], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 248, 252] },
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
        vouchers:     { titulo: 'Conciliación de Vouchers',  tableId: 'tabla-vouchers',     headers: ['ID','N° Operación','Fecha','Proformas','Monto Boucher (S/)','Monto Consumido (S/)','Saldo Restante (S/)'] },
        'clientes-rep': { titulo: 'Clientes y Recaudación',  tableId: 'tabla-clientes-rep', headers: ['ID','DNI/RUC','Cliente','Tipo Cliente','Transacciones','Ventas (S/)','Donaciones (S/)','Acumulado (S/)'] },
        consolidado:  { titulo: 'Consolidado por Centro',    tableId: 'tabla-consolidado',  headers: ['ID','Centro','Encargado','Ventas (S/)','Donaciones (S/)','Inventario (S/)','Mermas (S/)'] },
        precios:      { titulo: 'Catálogo de Precios',        tableId: 'tabla-precios',      headers: ['ID','Producto','Clase','Centro','Tipo Precio','Vigencia UIT / Histórico','Precio Vigente (S/)'] }
    };
    return configs[tabActiva] || configs.ventas;
}

function getTableRows(tableId) {
    const table  = document.getElementById(tableId);
    const tbody  = table ? table.querySelector('tbody') : null;
    if (!tbody) return [];
    return Array.from(tbody.querySelectorAll('tr')).map(tr =>
        Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim().replace(/\n+/g, ' '))
    );
}
</script>
