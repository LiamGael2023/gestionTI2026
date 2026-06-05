<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <div class="container-xl">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/produccion_agraria">Prod. Agraria</a></li>
            <li class="breadcrumb-item active"><i class="ti ti-layout-dashboard me-1"></i>Dashboard</li>
        </ol>
    </div>
</div>

<div class="page-body">
<div class="container-xl">

    <!-- TOOLBAR -->
    <div class="dash-toolbar">
        <button class="btn btn-primary" onclick="abrirPaleta()">
            <i class="ti ti-plus me-1"></i>Agregar Widget
        </button>
        <button class="btn btn-outline-primary" onclick="guardarDashboard()">
            <i class="ti ti-device-floppy me-1"></i>Guardar Layout
        </button>
        <button class="btn btn-outline-secondary" onclick="resetearDashboard()">
            <i class="ti ti-refresh me-1"></i>Restaurar Default
        </button>
        <div class="dash-toolbar-sep"></div>
        <label class="form-label mb-0 me-1" style="font-size:0.82rem;white-space:nowrap;">Filtro global:</label>
        <input type="date" id="filtro-desde" class="form-control form-control-sm" style="width:140px;" onchange="cargarDashboard()">
        <input type="date" id="filtro-hasta" class="form-control form-control-sm" style="width:140px;" onchange="cargarDashboard()">
    </div>

    <!-- GRID -->
    <div class="dash-grid" id="dash-grid">
        <div class="dash-empty" id="dash-empty" style="display:none;">
            <i class="ti ti-layout-dashboard"></i>
            <h3>Dashboard vacío</h3>
            <p>Haz clic en <strong>"Agregar Widget"</strong> para empezar a construir tu dashboard personalizado. Elige entre KPIs, gráficos y tablas.</p>
        </div>
    </div>

</div>
</div>

<!-- =========================================================== -->
<!-- MODAL: Paleta de Widgets -->
<!-- =========================================================== -->
<div class="modal fade" id="modal-paleta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-puzzle me-2"></i>Catálogo de Widgets</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="palette-grid" id="palette-grid"></div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================== -->
<!-- MODAL: Configuración de Widget -->
<!-- =========================================================== -->
<div class="modal fade" id="modal-config" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-settings me-2"></i>Configurar Widget</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cfg-widget-id">
                <div class="mb-3">
                    <label class="form-label">Título del widget</label>
                    <input type="text" class="form-control" id="cfg-titulo" placeholder="Dejar vacío para título por defecto">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tamaño</label>
                    <select class="form-select" id="cfg-tamano">
                        <option value="small">Pequeño (1 columna)</option>
                        <option value="medium" selected>Mediano (2 columnas)</option>
                        <option value="large">Grande (ancho completo)</option>
                    </select>
                </div>
                <div id="cfg-extra"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="aplicarConfig()">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================
// DASHBOARD CMS — JavaScript
// ============================================================
const BASE_URL = '<?php echo BASE_URL; ?>';
const grid = document.getElementById('dash-grid');
const emptyEl = document.getElementById('dash-empty');
let widgetsActivos = [];
let catálogo = [];
const KPI_COLORS = {
    'kpi_ventas_hoy':        { cls:'kpi-ventas',    icon:'ti ti-cash' },
    'kpi_proformas_pendientes': { cls:'kpi-proformas', icon:'ti ti-file-invoice' },
    'kpi_stock_critico':     { cls:'kpi-stock',     icon:'ti ti-alert-triangle' },
    'kpi_vouchers_sin_asignar': { cls:'kpi-vouchers',  icon:'ti ti-credit-card' },
    'kpi_mermas_hoy':        { cls:'kpi-mermas',    icon:'ti ti-trash' },
    'kpi_valor_inventario':  { cls:'kpi-valor',     icon:'ti ti-coin' }
};

// ============= CARGA INICIAL =============
async function cargarDashboard() {
    const desde = document.getElementById('filtro-desde').value;
    const hasta = document.getElementById('filtro-hasta').value;
    let url = `${BASE_URL}/index.php?module=produccion_agraria&action=dash_load`;
    if (desde || hasta) url += `&desde=${desde}&hasta=${hasta}`;

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) {
            widgetsActivos = [];
        } else {
            widgetsActivos = data.widgets || [];
        }
    } catch(e) {
        widgetsActivos = [];
    }

    grid.querySelectorAll('.dash-widget').forEach(w => w.remove());

    if (!widgetsActivos.length) {
        emptyEl.style.display = 'flex';
    } else {
        emptyEl.style.display = 'none';
        widgetsActivos.forEach((w, i) => renderizarWidget(w, i));
    }
}

// ============= RENDERIZAR WIDGET =============
function renderizarWidget(w, index) {
    const tamano = w.widget_tamano || 'medium';
    const tipo = w.widget_tipo;
    const titulo = w.widget_titulo || getTituloDefault(tipo);

    const div = document.createElement('div');
    div.className = `dash-widget size-${tamano} loaded`;
    div.draggable = true;
    div.dataset.index = index;
    div.dataset.tipo = tipo;
    div.dataset.tamano = tamano;
    div.dataset.titulo = titulo;

    div.innerHTML = `
        <div class="dash-widget-header">
            <span class="drag-handle"><i class="ti ti-grip-vertical"></i></span>
            <span class="widget-title">${escapeHtml(titulo)}</span>
            <button class="btn-icon" onclick="abrirConfig(this)" title="Configurar"><i class="ti ti-settings"></i></button>
            <button class="btn-icon" onclick="refrescarWidget(this)" title="Refrescar"><i class="ti ti-refresh"></i></button>
            <button class="btn-icon remove" onclick="eliminarWidget(this)" title="Eliminar"><i class="ti ti-x"></i></button>
        </div>
        <div class="dash-widget-body"></div>
    `;

    // Drag events
    div.addEventListener('dragstart', onDragStart);
    div.addEventListener('dragover', onDragOver);
    div.addEventListener('dragleave', onDragLeave);
    div.addEventListener('drop', onDrop);
    div.addEventListener('dragend', onDragEnd);

    grid.appendChild(div);

    // Renderizar contenido
    const body = div.querySelector('.dash-widget-body');
    if (w.data) {
        renderizarContenido(body, tipo, w.data);
    }
}

function getTituloDefault(tipo) {
    const map = {
        'kpi_ventas_hoy': 'Ventas Hoy', 'kpi_proformas_pendientes': 'Proformas Pendientes',
        'kpi_stock_critico': 'Stock Crítico', 'kpi_vouchers_sin_asignar': 'Vouchers Sin Asignar',
        'kpi_mermas_hoy': 'Mermas Hoy', 'kpi_valor_inventario': 'Valor Inventario',
        'resumen_ejecutivo': 'Resumen Ejecutivo',
        'grafico_ventas_mes': 'Ventas Mensuales', 'grafico_top_productos': 'Top Productos',
        'grafico_stock_centro': 'Stock por Centro', 'grafico_metodo_pago': 'Método de Pago',
        'grafico_valorizacion_clase': 'Valor por Clase', 'grafico_mermas_mes': 'Mermas Mensuales',
        'grafico_vs_donaciones': 'Ventas vs Donaciones',
        'tabla_stock': 'Stock Actual', 'tabla_ventas_recientes': 'Últimas Ventas',
        'tabla_proformas': 'Proformas', 'tabla_recomendaciones': 'Recomendaciones'
    };
    return map[tipo] || tipo;
}

// ============= RENDERIZAR CONTENIDO =============
function renderizarContenido(body, tipo, data) {
    if (!data) {
        body.innerHTML = '<div class="dash-widget-loading">Sin datos</div>';
        return;
    }

    // KPI individual
    if (tipo.startsWith('kpi_') && data.kpi) {
        const kpi = data.kpi;
        const color = KPI_COLORS[tipo] || {};
        body.innerHTML = `
            <div class="kpi-card ${color.cls || ''}">
                <div class="kpi-icon"><i class="${color.icon || 'ti ti-chart-bar'}"></i></div>
                <div class="kpi-valor">${escapeHtml(String(kpi.valor))}</div>
                <div class="kpi-label">${escapeHtml(kpi.indicador || '')}</div>
                <div class="kpi-sub">${escapeHtml(kpi.detalle || '')}</div>
            </div>`;
        return;
    }

    // Resumen ejecutivo (grid de KPIs)
    if (tipo === 'resumen_ejecutivo' && data.rows) {
        const kpiColors = [
            KPI_COLORS['kpi_ventas_hoy'], KPI_COLORS['kpi_proformas_pendientes'],
            KPI_COLORS['kpi_stock_critico'], KPI_COLORS['kpi_vouchers_sin_asignar'],
            KPI_COLORS['kpi_mermas_hoy'], KPI_COLORS['kpi_valor_inventario']
        ];
        let html = '<div class="kpi-grid">';
        data.rows.forEach((row, i) => {
            const c = kpiColors[i] || {};
            html += `<div class="kpi-card ${c.cls || ''}">
                <div class="kpi-icon"><i class="${c.icon || 'ti ti-chart-bar'}"></i></div>
                <div class="kpi-valor">${escapeHtml(String(row.valor))}</div>
                <div class="kpi-label">${escapeHtml(row.indicador || '')}</div>
                <div class="kpi-sub">${escapeHtml(row.detalle || '')}</div>
            </div>`;
        });
        html += '</div>';
        body.innerHTML = html;
        return;
    }

    // Gráfico
    if (tipo.startsWith('grafico_') && data.grafico) {
        const chartId = 'chart-dash-' + tipo + '-' + Date.now() + '-' + Math.random().toString(36).substr(2,4);
        body.innerHTML = `<div id="${chartId}" style="min-height:${data.grafico.height || 260}px;"></div>`;
        setTimeout(() => {
            const opts = buildChartOptions(data.grafico);
            if (opts && document.getElementById(chartId)) {
                new ApexCharts(document.getElementById(chartId), opts).render();
            }
        }, 100);
        return;
    }

    // Tabla
    if (data.columns && data.rows) {
        let html = '<div class="table-responsive" style="max-height:300px;">';
        html += '<table class="table table-sm table-vcenter table-striped"><thead class="table-light"><tr>';
        data.columns.forEach(c => { html += `<th>${escapeHtml(c.label)}</th>`; });
        html += '</tr></thead><tbody>';
        (data.rows || []).slice(0, 15).forEach(row => {
            html += '<tr>';
            data.columns.forEach(c => {
                const val = row[c.key] !== undefined && row[c.key] !== null ? String(row[c.key]) : '-';
                html += `<td>${escapeHtml(val)}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        body.innerHTML = html;
        return;
    }

    body.innerHTML = '<div class="dash-widget-loading">Sin datos disponibles</div>';
}

// ============= CHART OPTIONS BUILDER =============
function buildChartOptions(grafico) {
    const tipo = grafico.tipo || 'bar';
    const opts = {
        chart: { type: tipo === 'horizontalBar' ? 'bar' : tipo, height: grafico.height || 280, toolbar: { show: false }, fontFamily: 'inherit', foreColor: '#475569' },
        title: { text: grafico.titulo || '', align: 'left', style: { fontSize: '13px', fontWeight: 600 } },
        colors: ['#004d99','#009540','#e67e22','#9b59b6','#1abc9c','#e74c3c','#3498db','#f1c40f'],
        tooltip: { y: { formatter: null } },
        noData: { text: 'Sin datos' }
    };

    if (tipo === 'horizontalBar') {
        opts.chart.type = 'bar';
        opts.plotOptions = { bar: { horizontal: true, borderRadius: 4 } };
        opts.dataLabels = { enabled: true, formatter: v => v, style: { fontSize: '10px' } };
    }

    if (tipo === 'pie' || tipo === 'donut') {
        opts.chart.type = 'donut';
        opts.labels = grafico.categorias || [];
        opts.series = grafico.series && grafico.series[0] ? grafico.series[0].datos : [];
        opts.legend = { position: 'bottom', fontSize: '11px' };
    } else {
        opts.xaxis = { categories: grafico.categorias || [], labels: { style: { fontSize: '10px' } } };
        opts.series = (grafico.series || []).map(s => ({ name: s.nombre, data: s.datos }));
        opts.dataLabels = { enabled: false };
        opts.legend = { position: 'top', fontSize: '11px' };
        if (tipo === 'area') {
            opts.chart.type = 'area';
            opts.fill = { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05 } };
            opts.stroke = { curve: 'smooth', width: 2 };
        }
    }

    if (grafico.formato === 'moneda') {
        opts.tooltip.y = { formatter: v => 'S/ ' + v.toFixed(2) };
        if (!opts.yaxis) opts.yaxis = {};
        opts.yaxis.labels = { formatter: v => 'S/' + v };
    }
    if (grafico.formato === 'entero') {
        opts.tooltip.y = { formatter: v => Math.round(v) };
    }

    return opts;
}

// ============= PALETA DE WIDGETS =============
function abrirPaleta() {
    const paletteGrid = document.getElementById('palette-grid');
    catálogo = getStaticCatalog();

    let html = '';
    catálogo.forEach(cat => {
        html += `<div class="palette-category">
            <div class="palette-cat-title"><i class="${cat.icono}"></i>${cat.categoria}</div>
            <div class="palette-items">`;
        cat.widgets.forEach(w => {
            html += `<div class="palette-item" onclick="agregarWidget('${w.tipo}','${w.tamano}')">
                <span class="palette-icon"><i class="${w.icono}"></i></span>
                <span class="palette-info">
                    <div class="palette-name">${w.nombre}</div>
                    <div class="palette-desc">${w.desc}</div>
                </span>
                <span class="palette-badge">${w.tamano}</span>
            </div>`;
        });
        html += '</div></div>';
    });
    paletteGrid.innerHTML = html;
    new bootstrap.Modal(document.getElementById('modal-paleta')).show();
}

function getStaticCatalog() {
    return [
        { categoria: 'KPI', icono: 'ti ti-chart-bar', widgets: [
            { tipo:'kpi_ventas_hoy', nombre:'Ventas Hoy', desc:'Monto y cantidad de ventas del día', tamano:'small', icono:'ti ti-cash' },
            { tipo:'kpi_proformas_pendientes', nombre:'Proformas Pendientes', desc:'Cantidad de proformas sin procesar', tamano:'small', icono:'ti ti-file-invoice' },
            { tipo:'kpi_stock_critico', nombre:'Stock Crítico', desc:'Productos con <10 unidades', tamano:'small', icono:'ti ti-alert-triangle' },
            { tipo:'kpi_vouchers_sin_asignar', nombre:'Vouchers Sin Asignar', desc:'Vouchers disponibles sin asignar', tamano:'small', icono:'ti ti-credit-card' },
            { tipo:'kpi_mermas_hoy', nombre:'Mermas Hoy', desc:'Unidades perdidas en el día', tamano:'small', icono:'ti ti-trash' },
            { tipo:'kpi_valor_inventario', nombre:'Valor Inventario', desc:'Valor monetario total del stock', tamano:'small', icono:'ti ti-coin' },
            { tipo:'resumen_ejecutivo', nombre:'Resumen Ejecutivo', desc:'6 KPIs en un solo widget', tamano:'medium', icono:'ti ti-clipboard-check' }
        ]},
        { categoria: 'Gráficos', icono: 'ti ti-chart-line', widgets: [
            { tipo:'grafico_ventas_mes', nombre:'Ventas Mensuales', desc:'Barras: monto y transacciones por mes', tamano:'medium', icono:'ti ti-chart-bar' },
            { tipo:'grafico_top_productos', nombre:'Top Productos', desc:'Barras horizontal: más vendidos', tamano:'medium', icono:'ti ti-trophy' },
            { tipo:'grafico_stock_centro', nombre:'Stock por Centro', desc:'Donut: distribución de stock', tamano:'small', icono:'ti ti-chart-donut' },
            { tipo:'grafico_metodo_pago', nombre:'Método de Pago', desc:'Pastel: ventas por método', tamano:'small', icono:'ti ti-chart-pie' },
            { tipo:'grafico_valorizacion_clase', nombre:'Valor por Clase', desc:'Barras: inventario por clase', tamano:'medium', icono:'ti ti-chart-bar' },
            { tipo:'grafico_mermas_mes', nombre:'Mermas Mensuales', desc:'Barras: mermas por mes', tamano:'medium', icono:'ti ti-chart-bar' },
            { tipo:'grafico_vs_donaciones', nombre:'Ventas vs Donaciones', desc:'Área: tendencia comparativa', tamano:'large', icono:'ti ti-chart-area-line' }
        ]},
        { categoria: 'Tablas', icono: 'ti ti-table', widgets: [
            { tipo:'tabla_stock', nombre:'Stock Actual', desc:'Stock por producto y lote', tamano:'medium', icono:'ti ti-packages' },
            { tipo:'tabla_ventas_recientes', nombre:'Últimas Ventas', desc:'Transacciones recientes', tamano:'medium', icono:'ti ti-receipt' },
            { tipo:'tabla_proformas', nombre:'Proformas', desc:'Proformas pendientes', tamano:'medium', icono:'ti ti-file-invoice' },
            { tipo:'tabla_recomendaciones', nombre:'Recomendaciones', desc:'Alertas y sugerencias', tamano:'medium', icono:'ti ti-bell' }
        ]}
    ];
}

// ============= AGREGAR / ELIMINAR WIDGETS =============
async function agregarWidget(tipo, tamano) {
    bootstrap.Modal.getInstance(document.getElementById('modal-paleta')).hide();
    
    // Cargar datos via AJAX
    const res = await fetch(`${BASE_URL}/index.php?module=produccion_agraria&action=dash_widget&tipo=${tipo}`);
    const data = await res.json();
    
    const w = {
        widget_tipo: tipo,
        widget_tamano: tamano || 'medium',
        widget_titulo: null,
        widget_config: {},
        data: data.success ? data.data : null
    };
    
    widgetsActivos.push(w);
    emptyEl.style.display = 'none';
    renderizarWidget(w, widgetsActivos.length - 1);
}

function eliminarWidget(btn) {
    const widget = btn.closest('.dash-widget');
    const index = parseInt(widget.dataset.index);
    widgetsActivos.splice(index, 1);
    widget.remove();
    reindexar();
    if (!widgetsActivos.length) emptyEl.style.display = 'flex';
}

function reindexar() {
    grid.querySelectorAll('.dash-widget').forEach((w, i) => { w.dataset.index = i; });
}

function refrescarWidget(btn) {
    const widget = btn.closest('.dash-widget');
    const tipo = widget.dataset.tipo;
    const body = widget.querySelector('.dash-widget-body');
    body.innerHTML = '<div class="dash-widget-loading"><div class="spinner"></div>Cargando...</div>';
    
    fetch(`${BASE_URL}/index.php?module=produccion_agraria&action=dash_widget&tipo=${tipo}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) renderizarContenido(body, tipo, data.data);
        });
}

// ============= CONFIGURACION =============
function abrirConfig(btn) {
    const widget = btn.closest('.dash-widget');
    document.getElementById('cfg-widget-id').value = widget.dataset.index;
    document.getElementById('cfg-titulo').value = widget.dataset.titulo || '';
    document.getElementById('cfg-tamano').value = widget.dataset.tamano || 'medium';
    document.getElementById('cfg-extra').innerHTML = '';
    new bootstrap.Modal(document.getElementById('modal-config')).show();
}

function aplicarConfig() {
    const index = parseInt(document.getElementById('cfg-widget-id').value);
    const widget = grid.querySelector(`.dash-widget[data-index="${index}"]`);
    if (!widget) return;
    
    const titulo = document.getElementById('cfg-titulo').value.trim();
    const tamano = document.getElementById('cfg-tamano').value;
    widget.dataset.titulo = titulo;
    widget.dataset.tamano = tamano;
    widget.className = widget.className.replace(/size-\w+/, 'size-' + tamano);
    widget.querySelector('.widget-title').textContent = titulo || getTituloDefault(widget.dataset.tipo);
    widgetsActivos[index].widget_titulo = titulo || null;
    widgetsActivos[index].widget_tamano = tamano;
    
    bootstrap.Modal.getInstance(document.getElementById('modal-config')).hide();
}

// ============= GUARDAR / RESETEAR =============
async function guardarDashboard() {
    const widgets = [];
    grid.querySelectorAll('.dash-widget').forEach(w => {
        widgets.push({
            widget_tipo: w.dataset.tipo,
            widget_titulo: w.dataset.titulo || null,
            widget_tamano: w.dataset.tamano || 'medium',
            widget_config: {}
        });
    });
    
    const res = await fetch(`${BASE_URL}/index.php?module=produccion_agraria&action=dash_save`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ widgets })
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Dashboard guardado', timer: 1500, showConfirmButton: false });
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
    }
}

async function resetearDashboard() {
    const result = await Swal.fire({
        title: '¿Restaurar layout por defecto?',
        text: 'Perderás la configuración actual.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, restaurar',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;
    
    await fetch(`${BASE_URL}/index.php?module=produccion_agraria&action=dash_reset`, { method: 'POST' });
    widgetsActivos = [];
    cargarDashboard();
}

// ============= DRAG & DROP =============
let draggedItem = null;

function onDragStart(e) {
    draggedItem = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.index);
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
}

function onDragLeave(e) {
    this.classList.remove('drag-over');
}

function onDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    if (draggedItem !== this) {
        const fromIndex = parseInt(draggedItem.dataset.index);
        const toIndex = parseInt(this.dataset.index);
        
        // Reordenar en el array
        const [moved] = widgetsActivos.splice(fromIndex, 1);
        widgetsActivos.splice(toIndex, 0, moved);
        
        // Reordenar en el DOM
        if (fromIndex < toIndex) {
            this.after(draggedItem);
        } else {
            this.before(draggedItem);
        }
        
        reindexar();
    }
}

function onDragEnd(e) {
    this.classList.remove('dragging');
    grid.querySelectorAll('.dash-widget').forEach(w => w.classList.remove('drag-over'));
    draggedItem = null;
}

// ============= UTILIDADES =============
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Iniciar
cargarDashboard();
</script>
