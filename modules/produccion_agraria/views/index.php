
<div class='page-body'>
    <div class='container-xl' style='padding: 0 2rem;'>
        
        <!-- ============================================================ -->
        <!-- KPI ROW — Resumen rápido de indicadores clave -->
        <!-- ============================================================ -->
        <?php
        if (!isset($conn) || !$conn) {
            $conn = Conexion::conectar();
        }
        require_once __DIR__ . '/../models/DashboardModel.php';
        $dashModel = new DashboardModel($conn);
        $resumen = $dashModel->getWidgetData('resumen_ejecutivo', []);
        $kpiRows = $resumen['rows'] ?? [];
        $kpiIcons = ['ti ti-cash', 'ti ti-file-invoice', 'ti ti-alert-triangle', 'ti ti-credit-card', 'ti ti-trash', 'ti ti-coin'];
        $kpiClasses = ['kpi-ventas', 'kpi-proformas', 'kpi-stock', 'kpi-vouchers', 'kpi-mermas', 'kpi-valor'];
        $kpiLabels = ['Ventas Hoy', 'Proformas Pendientes', 'Stock Crítico', 'Vouchers Sin Asignar', 'Mermas Hoy', 'Valor Inventario'];
        $kpiTipos  = ['kpi_ventas_hoy','kpi_proformas_pendientes','kpi_stock_critico','kpi_vouchers_sin_asignar','kpi_mermas_hoy','kpi_valor_inventario'];
        ?>
        <div class="d-flex align-items-center mb-3">
            <h2 class="card-title mb-0">Indicadores</h2>
            <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="abrirPersonalizarHome()">
                <i class="ti ti-settings me-1"></i>Personalizar
            </button>
        </div>
        <div class="row g-3 mb-4" id="home-kpi-row">
            <?php foreach ($kpiRows as $i => $kpi): ?>
            <div class="col-sm-6 col-lg-2 home-kpi-col" data-kpi="<?php echo $kpiTipos[$i] ?? ''; ?>" data-index="<?php echo $i; ?>">
                <div class="card home-kpi-card <?php echo $kpiClasses[$i] ?? ''; ?>">
                    <div class="card-body text-center py-3">
                        <i class="<?php echo $kpiIcons[$i] ?? 'ti ti-chart-bar'; ?> home-kpi-icon"></i>
                        <div class="home-kpi-value"><?php echo htmlspecialchars((string)($kpi['valor'] ?? '-')); ?></div>
                        <div class="home-kpi-label"><?php echo $kpiLabels[$i] ?? $kpi['indicador'] ?? ''; ?></div>
                        <div class="home-kpi-sub"><?php echo htmlspecialchars((string)($kpi['detalle'] ?? '')); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2 class='card-title mb-5'>Módulos de Producción Agraria</h2>
        <div class='row g-3'>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=inventario' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-package icon-lg mb-3'></i>
                        <h5 class='card-title'>Inventario</h5>
                        <p class='card-text font-small'>Gestión de productos y existencias</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=punto_venta' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-shopping-cart icon-lg mb-3'></i>
                        <h5 class='card-title'>Punto de Venta</h5>
                        <p class='card-text font-small'>Realizar las Proformas</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=bandeja' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-inbox icon-lg mb-3'></i>
                        <h5 class='card-title'>Bandeja de Proformas</h5>
                        <p class='card-text font-small'>Validar las Proformas</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=tablas' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-table icon-lg mb-3'></i>
                        <h5 class='card-title'>Tablas</h5>
                        <p class='card-text font-small'>Datos maestros y catálogos y planillas</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=reportes' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-file-report icon-lg mb-3'></i>
                        <h5 class='card-title'>Reportes</h5>
                        <p class='card-text font-small'>Informes y estadísticas</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=dashboard' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-dashboard icon-lg mb-3'></i>
                        <h5 class='card-title'>Dashboard</h5>
                        <p class='card-text font-small'>Panel principal</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=consultas' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-message-circle icon-lg mb-3'></i>
                        <h5 class='card-title'>Consultas IA</h5>
                        <p class='card-text font-small'>Asistente virtual del sistema</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: Personalizar indicadores del home -->
<!-- ============================================================ -->
<div class="modal fade" id="modal-personalizar-home" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-layout-grid me-2"></i>Personalizar Indicadores</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Activa/desactiva y reordena los indicadores visibles en el inicio.</p>
                <div id="home-kpi-toggles"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" onclick="resetHomeKPIs()">
                    <i class="ti ti-refresh me-1"></i>Restaurar default
                </button>
                <button class="btn btn-primary" onclick="guardarHomeKPIs()">
                    <i class="ti ti-device-floppy me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos compartidos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/responsive.css">

<style>
    /* Estilos específicos para las tarjetas de módulos */
    .card-link {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    /* Gradientes para las tarjetas de módulos */
    .bg-gradient-primary {
        background: linear-gradient(135deg, var(--pech-verde, #009540) 0%, #00c851 100%) !important;
    }

    .card-link .card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        height: 100%;
    }

    .card-link:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        color: #70e796;
        text-decoration: none;
    }

    .card-link:hover .card-title {
        color: #70e796;
    }

    .card-link:hover .card-text {
        color: #70e796;
    }

    .card-link:hover i {
        color: #70e796;
    }

    .card-link .card-title {
        font-weight: 600;
        text-align: center;
    }

    .page-body h2 {
        font-size: 1.5rem;
    }

    /* Font small */
    .font-small {
        font-size: 0.875rem;
    }
    
    .card-link .card-text {
        margin-bottom: 1.5rem; 
        padding: 0 10px;       
    }

    .icon-lg {
        font-size: 2.5rem;
        margin-top: 1.5rem; 
    }

    /* --- HOME KPI CARDS --- */
    .home-kpi-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: transform 0.15s, box-shadow 0.15s;
        height: 100%;
    }
    .home-kpi-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
    .home-kpi-card .home-kpi-icon { font-size: 1.4rem; margin-bottom: 0.3rem; }
    .home-kpi-card .home-kpi-value { font-size: 1.25rem; font-weight: 700; color: #1e293b; }
    .home-kpi-card .home-kpi-label { font-size: 0.72rem; color: #64748b; margin-top: 0.1rem; }
    .home-kpi-card .home-kpi-sub { font-size: 0.68rem; color: #94a3b8; }

    .home-kpi-card.kpi-ventas { border-top: 3px solid #004d99; }
    .home-kpi-card.kpi-ventas .home-kpi-icon { color: #004d99; }
    .home-kpi-card.kpi-proformas { border-top: 3px solid #e67e22; }
    .home-kpi-card.kpi-proformas .home-kpi-icon { color: #e67e22; }
    .home-kpi-card.kpi-stock { border-top: 3px solid #dc2626; }
    .home-kpi-card.kpi-stock .home-kpi-icon { color: #dc2626; }
    .home-kpi-card.kpi-vouchers { border-top: 3px solid #9b59b6; }
    .home-kpi-card.kpi-vouchers .home-kpi-icon { color: #9b59b6; }
    .home-kpi-card.kpi-mermas { border-top: 3px solid #e74c3c; }
    .home-kpi-card.kpi-mermas .home-kpi-icon { color: #e74c3c; }
    .home-kpi-card.kpi-valor { border-top: 3px solid #009540; }
    .home-kpi-card.kpi-valor .home-kpi-icon { color: #009540; }

    @media (max-width: 768px) {
        .home-kpi-card .home-kpi-value { font-size: 1.1rem; }
        .home-kpi-card .home-kpi-label { font-size: 0.68rem; }
    }

    /* --- Personalizar modal --- */
    .home-toggle-item {
        display: flex; align-items: center; gap: 0.75rem;
        padding: 0.6rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;
        margin-bottom: 0.4rem; cursor: grab; background: #fff;
    }
    .home-toggle-item.dragging { opacity: 0.4; }
    .home-toggle-item .drag-icon { color: #94a3b8; font-size: 0.85rem; }
    .home-toggle-item .toggle-label { flex: 1; font-size: 0.85rem; font-weight: 500; }
</style>

<script>
// ============================================================
// PERSONALIZAR INDICADORES DEL HOME
// ============================================================
const HOME_KPI_DEFAULTS = [
    'kpi_ventas_hoy','kpi_proformas_pendientes','kpi_stock_critico',
    'kpi_vouchers_sin_asignar','kpi_mermas_hoy','kpi_valor_inventario'
];

function getHomeConfig() {
    try { return JSON.parse(localStorage.getItem('home_kpi_config') || 'null'); }
    catch(e) { return null; }
}
function setHomeConfig(cfg) {
    localStorage.setItem('home_kpi_config', JSON.stringify(cfg));
}

function abrirPersonalizarHome() {
    let cfg = getHomeConfig();
    if (!cfg) {
        cfg = HOME_KPI_DEFAULTS.map(function(k,i) { return { tipo: k, visible: true, orden: i }; });
    }
    
    const container = document.getElementById('home-kpi-toggles');
    let html = '';
    cfg.sort(function(a,b){ return a.orden - b.orden; }).forEach(function(item, idx) {
        const kpiEl = document.querySelector('.home-kpi-col[data-kpi="' + item.tipo + '"]');
        const label = kpiEl ? kpiEl.querySelector('.home-kpi-label').textContent : item.tipo;
        html += '<div class="home-toggle-item" draggable="true" data-tipo="' + item.tipo + '" data-index="' + idx + '">' +
            '<span class="drag-icon"><i class="ti ti-grip-vertical"></i></span>' +
            '<span class="toggle-label">' + label + '</span>' +
            '<label class="form-check form-switch mb-0">' +
            '<input class="form-check-input" type="checkbox" ' + (item.visible ? 'checked' : '') + ' onchange="toggleHomeKPI(this)">' +
            '</label></div>';
    });
    container.innerHTML = html;
    
    // Drag events
    container.querySelectorAll('.home-toggle-item').forEach(function(el) {
        el.addEventListener('dragstart', function(e) { this.classList.add('dragging'); e.dataTransfer.setData('text/plain', this.dataset.tipo); });
        el.addEventListener('dragend', function() { this.classList.remove('dragging'); });
        el.addEventListener('dragover', function(e) { e.preventDefault(); });
        el.addEventListener('drop', function(e) {
            e.preventDefault();
            const fromTipo = e.dataTransfer.getData('text/plain');
            const toTipo = this.dataset.tipo;
            if (fromTipo !== toTipo) {
                const fromEl = container.querySelector('[data-tipo="' + fromTipo + '"]');
                this.parentNode.insertBefore(fromEl, this.nextSibling === fromEl ? this : this);
            }
        });
    });
    
    new bootstrap.Modal(document.getElementById('modal-personalizar-home')).show();
}

function toggleHomeKPI(cb) {
    // Solo visual, se guarda al cerrar el modal
}

function guardarHomeKPIs() {
    const items = document.getElementById('home-kpi-toggles').querySelectorAll('.home-toggle-item');
    const cfg = [];
    items.forEach(function(el, idx) {
        const cb = el.querySelector('input[type=checkbox]');
        cfg.push({ tipo: el.dataset.tipo, visible: cb.checked, orden: idx });
    });
    setHomeConfig(cfg);
    aplicarHomeConfig(cfg);
    bootstrap.Modal.getInstance(document.getElementById('modal-personalizar-home')).hide();
}

function resetHomeKPIs() {
    localStorage.removeItem('home_kpi_config');
    const cfg = HOME_KPI_DEFAULTS.map(function(k,i) { return { tipo: k, visible: true, orden: i }; });
    aplicarHomeConfig(cfg);
    bootstrap.Modal.getInstance(document.getElementById('modal-personalizar-home')).hide();
}

function aplicarHomeConfig(cfg) {
    const row = document.getElementById('home-kpi-row');
    const allCols = row.querySelectorAll('.home-kpi-col');
    
    // Ocultar/mostrar según config
    cfg.forEach(function(item) {
        const col = row.querySelector('.home-kpi-col[data-kpi="' + item.tipo + '"]');
        if (col) col.style.display = item.visible ? '' : 'none';
    });
    
    // Reordenar
    cfg.filter(function(i){ return i.visible; }).forEach(function(item) {
        const col = row.querySelector('.home-kpi-col[data-kpi="' + item.tipo + '"]');
        if (col) row.appendChild(col);
    });
}

// Aplicar config al cargar
(function() {
    const cfg = getHomeConfig();
    if (cfg) aplicarHomeConfig(cfg);
})();
</script>