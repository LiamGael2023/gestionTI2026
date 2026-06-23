<!-- Estilos del chatbot -->
<style>
:root {
    --chat-bg: #f8fafc;
    --chat-bubble-bot: #ffffff;
    --chat-bubble-bot-text: #1e293b;
    --chat-border: #e2e8f0;
    --chat-input-border: #cbd5e1;
    --chat-code-bg: #f1f5f9;
    --chat-code-color: #004d99;
    --chat-prompt-bg: #f1f5f9;
    --chat-prompt-hover: #e2e8f0;
    --chat-prompt-text: #475569;
    --chat-prompt-border: #cbd5e1;
}

@media (prefers-color-scheme: dark) {
    :root {
        --chat-bg: #f0f4f8;
        --chat-bubble-bot: #ffffff;
        --chat-bubble-bot-text: #1e293b;
        --chat-border: #d1d5db;
        --chat-input-border: #cbd5e1;
        --chat-code-bg: #f1f5f9;
        --chat-code-color: #004d99;
        --chat-prompt-bg: #e8ecf1;
        --chat-prompt-hover: #d1d5db;
        --chat-prompt-text: #475569;
        --chat-prompt-border: #cbd5e1;
    }
}

.chat-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 200px);
    min-height: 500px;
    max-height: 800px;
}
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    background: var(--chat-bg);
    border-radius: 12px;
    border: 1px solid var(--chat-border);
}
.chat-bubble {
    max-width: 75%;
    margin-bottom: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 16px;
    font-size: 0.95rem;
    line-height: 1.5;
    animation: fadeInUp 0.3s ease;
}
.chat-bubble-user {
    margin-left: auto;
    background: linear-gradient(135deg, #004d99 0%, #0070cc 100%);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.chat-bubble-bot {
    margin-right: auto;
    background: var(--chat-bubble-bot);
    color: var(--chat-bubble-bot-text);
    border: 1px solid var(--chat-border);
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}
.chat-bubble-bot pre {
    background: var(--chat-code-bg);
    padding: 0.75rem;
    border-radius: 8px;
    overflow-x: auto;
    font-size: 0.85rem;
}
.chat-bubble-bot code {
    background: var(--chat-code-bg);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-size: 0.85rem;
    color: var(--chat-code-color);
}
.chat-input-area {
    display: flex;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--chat-border);
    margin-top: 1rem;
}
.chat-input {
    flex: 1;
    border-radius: 24px;
    padding: 0.75rem 1.25rem;
    border: 1px solid var(--chat-input-border);
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: var(--chat-bubble-bot);
    color: var(--chat-bubble-bot-text);
}
.chat-input:focus {
    outline: none;
    border-color: #004d99;
    box-shadow: 0 0 0 3px rgba(0, 77, 153, 0.1);
}
.chat-typing {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: var(--chat-bubble-bot);
    border: 1px solid var(--chat-border);
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
    margin-bottom: 1rem;
    color: var(--chat-bubble-bot-text);
}
.chat-typing-dot {
    width: 8px;
    height: 8px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typingBounce 1.4s infinite ease-in-out both;
}
.chat-typing-dot:nth-child(1) { animation-delay: -0.32s; }
.chat-typing-dot:nth-child(2) { animation-delay: -0.16s; }

.chat-prompt-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.75rem 0 0.25rem;
}
.chat-prompt-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.85rem;
    font-size: 0.82rem;
    border-radius: 20px;
    background: var(--chat-prompt-bg);
    color: var(--chat-prompt-text);
    border: 1px solid var(--chat-prompt-border);
    cursor: pointer;
    transition: background 0.15s, transform 0.15s;
    white-space: nowrap;
}
.chat-prompt-chip:hover {
    background: var(--chat-prompt-hover);
    transform: translateY(-1px);
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes typingBounce {
    0%, 80%, 100% { transform: scale(0.6); }
    40% { transform: scale(1); }
}
.chart-container {
    margin: 1rem 0;
    padding: 0.75rem;
    background: var(--chat-code-bg);
    border-radius: 12px;
    border: 1px solid var(--chat-border);
}
.chart-container .chart-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #004d99;
}
.chat-export-btns {
    display: flex;
    gap: 0.4rem;
    margin-bottom: 0.5rem;
}
.chat-export-btns button {
    font-size: 0.75rem;
    padding: 0.15rem 0.6rem;
    line-height: 1.4;
}
.chat-fav-btn {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    font-size: 1rem;
    transition: color 0.15s;
    float: right;
}
.chat-fav-btn:hover, .chat-fav-btn.active { color: #f59e0b; }
.chat-mic-btn {
    border: none;
    background: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.2s;
    font-size: 1.2rem;
}
.chat-mic-btn:hover { color: #004d99; background: rgba(0,77,153,0.08); }
.chat-mic-btn.listening { color: #dc2626; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(220,38,38,0.3); } 50% { box-shadow: 0 0 0 8px rgba(220,38,38,0); } }
</style>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <div class="container-xl">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria">Prod. Agraria</a>
            </li>
            <li class="breadcrumb-item active"><i class="ti ti-message-circle me-1"></i>Consultas IA</li>
        </ol>
    </div>
</div>

<div class="page-body">
<div class="container-xl">

    <!-- ENCABEZADO -->
    <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #004d99 0%, #0070cc 100%);">
        <div class="card-body py-4">
            <div class="row align-items-center">
                <div class="col">
                    <div class="text-uppercase fw-bold fs-5 mb-1" style="color: rgba(255,255,255,0.85);">
                        <i class="ti ti-leaf me-2" style="color: rgba(255,255,255,0.7);"></i>
                        Sistema de Seguimiento y control de Productos Agricolas
                    </div>
                    <h3 class="mb-1 fw-bold text-white"><i class="ti ti-robot me-2"></i>Asistente Virtual PECH</h3>
                    <p class="mb-0 opacity-75 text-white">Consulta dudas sobre el Sistema de Gestión TI, Producción Agraria, Inventario, Vouchers y más.</p>
                </div>
                <div class="col-auto">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">
                        <i class="ti ti-bolt me-1"></i>IA
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- SUGERENCIAS RÁPIDAS -->
    <div class="chat-prompt-chips mb-3">
        <span class="chat-prompt-chip" onclick="enviarPrompt('¿Cuál es el stock actual de todos los productos?')">
            <i class="ti ti-packages"></i> Stock actual
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('¿Cuáles son las ventas de este mes?')">
            <i class="ti ti-chart-bar"></i> Ventas del mes
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('Muestra el top 10 de productos más vendidos')">
            <i class="ti ti-trophy"></i> Top productos
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('¿Cuáles son las proformas pendientes?')">
            <i class="ti ti-file-invoice"></i> Proformas pendientes
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('¿Cuál es la valorización total del inventario?')">
            <i class="ti ti-coin"></i> Valor inventario
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('Muestra las mermas registradas este mes')">
            <i class="ti ti-trash"></i> Mermas
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('Muestra un gráfico de ventas por mes de los últimos 6 meses')">
            <i class="ti ti-chart-line"></i> Gráfico ventas
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('¿Cómo va el día? Dame un resumen')">
            <i class="ti ti-clipboard-check"></i> Resumen
        </span>
        <span class="chat-prompt-chip" onclick="enviarPrompt('¿Qué necesito atender? Dame recomendaciones')">
            <i class="ti ti-alert-triangle"></i> Recomendaciones
        </span>
    </div>

    <!-- FAVORITOS -->
    <div class="chat-prompt-chips mb-3" id="favoritos-chips" style="display:none;">
        <span style="font-size:0.8rem; color:var(--chat-prompt-text);" class="me-1"><i class="ti ti-star-filled text-warning"></i> Favoritos:</span>
    </div>

    <!-- CHAT -->
    <div class="chat-wrapper">
        <div class="chat-messages" id="chat-messages">
            <!-- Mensaje de bienvenida -->
            <div class="chat-bubble chat-bubble-bot">
                <div class="d-flex align-items-center mb-2">
                    <span class="avatar avatar-sm bg-primary-lt me-2"><i class="ti ti-robot"></i></span>
                    <strong>Asistente PECH</strong>
                </div>
                <p class="mb-0">¡Hola! Soy tu asistente virtual del Sistema de Gestión TI. ¿En qué puedo ayudarte hoy?</p>
            </div>
        </div>

        <div class="chat-input-area">
            <button class="chat-mic-btn" id="btn-mic" onclick="toggleVoz()" title="Dictado por voz">
                <i class="ti ti-microphone"></i>
            </button>
            <input type="text" class="form-control chat-input" id="chat-input" 
                   placeholder="Escribe tu consulta..." 
                   onkeypress="if(event.key==='Enter') enviarMensaje()">
            <button class="btn btn-primary px-4" id="btn-enviar" onclick="enviarMensaje()">
                <i class="ti ti-send me-1"></i>Enviar
            </button>
            <button class="btn btn-outline-secondary" onclick="limpiarChat()" title="Limpiar conversación">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const btnEnviar = document.getElementById('btn-enviar');

// Cargar historial desde localStorage
let historial = JSON.parse(localStorage.getItem('chat_historial_pech') || '[]');

// Nombres amigables de tools para feedback visual
const TOOL_LABELS = {
    'consultar_stock': 'Stock de productos',
    'consultar_ventas': 'Ventas y donaciones',
    'consultar_proformas': 'Proformas',
    'consultar_vouchers': 'Vouchers de depósito',
    'consultar_productos': 'Catálogo de productos',
    'consultar_clientes': 'Directorio de clientes',
    'consultar_mermas': 'Pérdidas / Mermas',
    'consultar_kardex': 'Movimientos de inventario',
    'consultar_top_productos_vendidos': 'Top productos vendidos',
    'consultar_valorizacion_inventario': 'Valorización de inventario',
    'consultar_ventas_por_mes': 'Tendencia mensual de ventas',
    'consultar_vouchers_saldo': 'Saldos de vouchers',
    'consultar_grafico': 'Generando gráfico',
    'consultar_resumen': 'Resumen ejecutivo',
    'consultar_comparativa': 'Comparando periodos',
    'consultar_buscar': 'Buscando',
    'consultar_recomendaciones': 'Analizando recomendaciones'
};

function renderHistorial() {
    if (!historial.length) return;
    // Limpiar solo los mensajes dinámicos (mantener bienvenida)
    const bubbles = chatMessages.querySelectorAll('.chat-bubble-user, .chat-bubble-bot:not(:first-child)');
    bubbles.forEach(b => b.remove());
    
    historial.forEach(h => {
        if (h.role === 'user') {
            agregarBurbujaUsuario(h.content, false);
        } else if (h.role === 'assistant') {
            agregarBurbujaBot(h.content, false, h.resultado_raw || null, h.tool_usada || null);
        }
    });
    scrollToBottom();
}

function agregarBurbujaUsuario(texto, animar = true) {
    const div = document.createElement('div');
    div.className = 'chat-bubble chat-bubble-user' + (animar ? '' : '');
    div.style.animation = animar ? 'fadeInUp 0.3s ease' : 'none';
    div.innerHTML = `
        <div class="d-flex align-items-center mb-1 justify-content-end">
            <strong>Tú</strong>
            <span class="avatar avatar-sm bg-white text-primary ms-2"><i class="ti ti-user"></i></span>
        </div>
        <p class="mb-0">${escapeHtml(texto)}</p>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
}

function agregarBurbujaBot(texto, animar = true, resultadoRaw = null, toolUsada = null) {
    const div = document.createElement('div');
    div.className = 'chat-bubble chat-bubble-bot';
    div.style.animation = animar ? 'fadeInUp 0.3s ease' : 'none';
    
    let tablaHTML = '';
    let graficoHTML = '';
    let graficoId = null;
    let exportHTML = '';
    let favHTML = '';
    
    if (resultadoRaw) {
        if (resultadoRaw.grafico) {
            graficoId = 'chart-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);
            const tipoTxt = resultadoRaw.grafico.tipo === 'bar' ? 'Barras' :
                           resultadoRaw.grafico.tipo === 'line' ? 'Líneas' :
                           resultadoRaw.grafico.tipo === 'area' ? 'Área' :
                           resultadoRaw.grafico.tipo === 'pie' ? 'Circular' :
                           resultadoRaw.grafico.tipo === 'donut' ? 'Anillo' : 'Gráfico';
            graficoHTML = '<div class="chart-container"><div class="chart-title"><i class="ti ti-chart-bar me-1"></i>' + escapeHtml(resultadoRaw.grafico.titulo || tipoTxt) + '</div><div id="' + graficoId + '" style="min-height: 280px;"></div></div>';
        }
        if (resultadoRaw.columns && resultadoRaw.rows) {
            const tablaId = 'tbl-' + Date.now();
            tablaHTML = renderizarTablaDesdeJSON(resultadoRaw, tablaId);
            exportHTML = '<div class="chat-export-btns">' +
                '<button class="btn btn-sm btn-outline-secondary" onclick="exportarTablaCSV(\'' + tablaId + '\',\'consulta\')" title="Exportar CSV"><i class="ti ti-file-spreadsheet me-1"></i>CSV</button>' +
                '<button class="btn btn-sm btn-outline-secondary" onclick="exportarTablaPDF(\'' + tablaId + '\',\'consulta\')" title="Exportar PDF"><i class="ti ti-file-text me-1"></i>PDF</button>' +
                '</div>';
        }
    }

    // Estrella de favorito con el ultimo prompt del usuario
    if (window._ultimoPrompt) {
        const isFav = esFavorito(window._ultimoPrompt);
        favHTML = '<button class="chat-fav-btn' + (isFav ? ' active' : '') + '" onclick="toggleFavorito(this, \'' + escapeAttr(window._ultimoPrompt) + '\')" title="Guardar consulta"><i class="ti ti-star' + (isFav ? '-filled' : '') + '"></i></button>';
        window._ultimoPrompt = null;
    }
    
    let toolBadgeHTML = '';
    if (toolUsada) {
        let label = TOOL_LABELS[toolUsada];
        if (!label && toolUsada.includes('+')) {
            label = toolUsada.split('+').map(function(t) { return TOOL_LABELS[t.trim()] || t.trim(); }).join(' + ');
        }
        if (label) {
            toolBadgeHTML = `<div class="mb-2"><span class="badge bg-primary-lt text-primary"><i class="ti ti-database me-1"></i>${escapeHtml(label)}</span></div>`;
        }
    }
    
    div.innerHTML = `
        <div class="d-flex align-items-center mb-2">
            <span class="avatar avatar-sm bg-primary-lt me-2"><i class="ti ti-robot"></i></span>
            <strong>Asistente PECH</strong>
            ${favHTML}
        </div>
        ${toolBadgeHTML}
        ${exportHTML}
        ${tablaHTML}
        ${graficoHTML}
        <div class="mb-0">${formatearMarkdown(texto)}</div>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
    
    if (graficoId && resultadoRaw.grafico) {
        setTimeout(() => renderizarGrafico(graficoId, resultadoRaw.grafico), 100);
    }
}

function renderizarTablaDesdeJSON(data, tablaId) {
    if (!data.columns || !data.rows || data.rows.length === 0) return '';
    const idAttr = tablaId ? ' id="' + tablaId + '"' : '';
    
    const cols = data.columns;
    const rows = data.rows;
    
    let html = '<div class="table-responsive mb-3" style="max-height: 300px;">';
    html += '<table class="table table-sm table-vcenter card-table table-striped" style="font-size: 0.85rem;"' + idAttr + '>';
    html += '<thead class="table-light sticky-top"><tr>';
    cols.forEach(col => {
        html += `<th>${escapeHtml(col.label)}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    rows.forEach(row => {
        html += '<tr>';
        cols.forEach(col => {
            const val = row[col.key] ?? '-';
            const isNumber = typeof val === 'string' && (val.startsWith('S/ ') || !isNaN(parseFloat(val)));
            const alignClass = isNumber ? 'text-end' : '';
            html += `<td class="${alignClass}">${escapeHtml(String(val))}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    html += `<div class="text-muted small mb-2"><i class="ti ti-info-circle me-1"></i>${rows.length} registro(s) encontrado(s)</div>`;
    
    return html;
}

function renderizarGrafico(containerId, grafico) {
    const el = document.getElementById(containerId);
    if (!el) return;
    
    const tipo = grafico.tipo || 'bar';
    const titulo = grafico.titulo || '';
    
    let options = {
        chart: {
            type: tipo === 'horizontalBar' ? 'bar' : tipo,
            height: grafico.height || 300,
            toolbar: { show: false },
            fontFamily: 'inherit',
            foreColor: '#475569',
            animations: { enabled: true, speed: 500 }
        },
        title: {
            text: titulo,
            align: 'left',
            style: { fontSize: '14px', fontWeight: 600, color: '#1e293b' }
        },
        colors: ['#004d99', '#009540', '#e67e22', '#9b59b6', '#1abc9c', '#e74c3c', '#3498db', '#f1c40f', '#2ecc71', '#e91e63'],
        tooltip: { y: { formatter: null } },
        noData: { text: 'Sin datos disponibles' }
    };
    
    if (tipo === 'horizontalBar') {
        options.chart.type = 'bar';
        options.plotOptions = { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } };
        options.dataLabels = { enabled: true, formatter: function(val) { return val; }, style: { fontSize: '11px' }, offsetX: 30 };
    }
    
    if (tipo === 'pie' || tipo === 'donut') {
        options.chart.type = 'donut';
        options.labels = grafico.categorias || [];
        options.series = grafico.series && grafico.series[0] ? grafico.series[0].datos : [];
        options.plotOptions = { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total' } } } } };
        options.dataLabels = { enabled: true, formatter: function(val, opts) { return opts.w.config.series[opts.seriesIndex]; }, style: { fontSize: '12px' } };
        options.legend = { position: 'bottom', fontSize: '12px' };
        if (tipo === 'pie') {
            options.plotOptions.pie.donut.labels.show = false;
        }
    } else {
        options.xaxis = { categories: grafico.categorias || [], labels: { rotate: grafico.categorias && grafico.categorias.length > 6 ? -45 : 0, style: { fontSize: '11px' } } };
        options.series = (grafico.series || []).map(function(s) {
            return { name: s.nombre, data: s.datos };
        });
        
        if (tipo === 'area') {
            options.chart.type = 'area';
            options.fill = { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.1 } };
            options.stroke = { curve: 'smooth', width: 2 };
        }
        
        if (tipo === 'line') {
            options.stroke = { curve: 'smooth', width: 3 };
            options.markers = { size: 4 };
        }
        
        options.dataLabels = { enabled: false };
        options.legend = { position: 'top', horizontalAlign: 'right', fontSize: '12px', itemMargin: { horizontal: 10 } };
    }
    
    if (grafico.formato === 'moneda') {
        options.tooltip.y = { formatter: function(val) { return 'S/ ' + val.toFixed(2); } };
        if (options.yaxis === undefined) options.yaxis = {};
        options.yaxis.labels = { formatter: function(val) { return 'S/' + val; } };
    }
    
    if (grafico.formato === 'entero') {
        options.tooltip.y = { formatter: function(val) { return Math.round(val); } };
    }
    
    new ApexCharts(el, options).render();
}

// ============= EXPORTACION =============
function exportarTablaCSV(tablaId, filename) {
    const table = document.getElementById(tablaId);
    if (!table) return;
    let csv = '\uFEFF';
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
        const cols = row.querySelectorAll('th,td');
        const vals = Array.from(cols).map(c => '"' + (c.textContent || '').replace(/"/g,'""') + '"');
        csv += vals.join(';') + '\n';
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = (filename || 'consulta') + '.csv'; a.click();
    URL.revokeObjectURL(url);
}

function exportarTablaPDF(tablaId, filename) {
    const table = document.getElementById(tablaId);
    if (!table || !window.jspdf) return;
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => headers.push(th.textContent));
    const rows = [];
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => row.push(td.textContent));
        rows.push(row);
    });
    
    doc.setFontSize(12);
    doc.setTextColor(0, 77, 153);
    doc.text('PECH - Consulta IA', 14, 12);
    doc.setFontSize(8);
    doc.setTextColor(128);
    doc.text(new Date().toLocaleDateString('es-PE'), 14, 18);
    
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 22,
        styles: { fontSize: 7, cellPadding: 2 },
        headStyles: { fillColor: [0, 77, 153], textColor: 255 },
        alternateRowStyles: { fillColor: [245, 247, 250] }
    });
    doc.save((filename || 'consulta') + '.pdf');
}

// ============= FAVORITOS =============
function esFavorito(prompt) {
    const favs = JSON.parse(localStorage.getItem('chat_favoritos_pech') || '[]');
    return favs.includes(prompt);
}

function toggleFavorito(btn, prompt) {
    let favs = JSON.parse(localStorage.getItem('chat_favoritos_pech') || '[]');
    const idx = favs.indexOf(prompt);
    if (idx >= 0) {
        favs.splice(idx, 1);
        btn.classList.remove('active');
        btn.innerHTML = '<i class="ti ti-star"></i>';
    } else {
        favs.unshift(prompt);
        if (favs.length > 10) favs.pop();
        btn.classList.add('active');
        btn.innerHTML = '<i class="ti ti-star-filled"></i>';
    }
    localStorage.setItem('chat_favoritos_pech', JSON.stringify(favs));
    renderizarFavoritos();
}

function renderizarFavoritos() {
    const container = document.getElementById('favoritos-chips');
    const favs = JSON.parse(localStorage.getItem('chat_favoritos_pech') || '[]');
    if (!favs.length) { container.style.display = 'none'; return; }
    container.style.display = 'flex';
    container.querySelectorAll('.chat-prompt-chip').forEach(c => c.remove());
    favs.forEach(prompt => {
        const chip = document.createElement('span');
        chip.className = 'chat-prompt-chip';
        chip.style.fontSize = '0.78rem';
        chip.innerHTML = '<i class="ti ti-star text-warning"></i> ' + escapeHtml(prompt.length > 40 ? prompt.substring(0, 40) + '...' : prompt);
        chip.onclick = function() { enviarPrompt(prompt); };
        container.appendChild(chip);
    });
}

function escapeAttr(str) {
    return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// ============= VOZ =============
let recognition = null;
function toggleVoz() {
    const btn = document.getElementById('btn-mic');
    if (!recognition) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            Swal.fire({ icon: 'info', title: 'Dictado no disponible', text: 'Tu navegador no soporta dictado por voz. Usa Chrome.', timer: 3000, showConfirmButton: false });
            return;
        }
        recognition = new SpeechRecognition();
        recognition.lang = 'es-PE';
        recognition.interimResults = false;
        recognition.onresult = function(e) {
            const texto = e.results[0][0].transcript;
            chatInput.value = texto;
            btn.classList.remove('listening');
        };
        recognition.onerror = function() { btn.classList.remove('listening'); };
        recognition.onend = function() { btn.classList.remove('listening'); };
    }
    if (btn.classList.contains('listening')) {
        recognition.abort();
        btn.classList.remove('listening');
    } else {
        recognition.start();
        btn.classList.add('listening');
    }
}

function mostrarTyping() {
    const div = document.createElement('div');
    div.className = 'chat-typing';
    div.id = 'chat-typing-indicator';
    div.innerHTML = `
        <div class="chat-typing-dot"></div>
        <div class="chat-typing-dot"></div>
        <div class="chat-typing-dot"></div>
        <span class="text-muted small ms-1">Pensando...</span>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
}

function ocultarTyping() {
    const el = document.getElementById('chat-typing-indicator');
    if (el) el.remove();
}

function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatearMarkdown(texto) {
    let html = escapeHtml(texto);
    
    const tableRegex = /((?:\|[^\n|]+)+\|)\n((?:\|[-:\s]+)+\|)\n((?:\|[^\n|]+)+\|(?:\n\|[^\n|]+\|)*)/g;
    html = html.replace(tableRegex, (match, headerLine, separatorLine, bodyLines) => {
        const headers = headerLine.split('|').map(h => h.trim()).filter(h => h);
        const rows = bodyLines.trim().split('\n').map(line => {
            return line.split('|').map(c => c.trim()).filter((_, i) => i > 0 || c.trim());
        });
        
        let tbl = '<div class="table-responsive mb-2"><table class="table table-sm table-vcenter table-striped" style="font-size:0.85rem;"><thead class="table-light"><tr>';
        headers.forEach(h => { tbl += `<th>${h}</th>`; });
        tbl += '</tr></thead><tbody>';
        rows.forEach(row => {
            tbl += '<tr>';
            row.forEach(cell => { tbl += `<td>${escapeHtml(cell)}</td>`; });
            tbl += '</tr>';
        });
        tbl += '</tbody></table></div>';
        return tbl;
    });
    
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    html = html.replace(/```(\w*)\n?([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    html = html.replace(/\n/g, '<br>');
    
    return html;
}

function guardarHistorial() {
    localStorage.setItem('chat_historial_pech', JSON.stringify(historial));
}

function limpiarChat() {
    Swal.fire({
        title: '¿Limpiar conversación?',
        text: 'Se borrará todo el historial de esta sesión.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            historial = [];
            guardarHistorial();
            const bubbles = chatMessages.querySelectorAll('.chat-bubble-user, .chat-bubble-bot:not(:first-child)');
            bubbles.forEach(b => b.remove());
            Swal.fire({ icon: 'success', title: 'Listo', timer: 1000, showConfirmButton: false });
        }
    });
}

function mostrarConsultandoBD(toolName) {
    const div = document.createElement('div');
    div.className = 'chat-typing';
    div.id = 'chat-consultando-bd';
    const label = (toolName && TOOL_LABELS[toolName]) ? TOOL_LABELS[toolName] : 'base de datos';
    div.innerHTML = `
        <i class="ti ti-database text-primary"></i>
        <span class="text-muted small">Consultando ${label}...</span>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
}

function ocultarConsultandoBD() {
    const el = document.getElementById('chat-consultando-bd');
    if (el) el.remove();
}

function enviarPrompt(texto) {
    chatInput.value = texto;
    enviarMensaje();
}

async function enviarMensaje() {
    const texto = chatInput.value.trim();
    if (!texto) return;
    
    chatInput.value = '';
    chatInput.disabled = true;
    btnEnviar.disabled = true;
    
    agregarBurbujaUsuario(texto);
    window._ultimoPrompt = texto;
    historial.push({ role: 'user', content: texto });
    guardarHistorial();
    
    mostrarTyping();
    
    try {
        const res = await fetch(`${BASE_URL}/index.php?module=produccion_agraria&action=chat_enviar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mensaje: texto,
                historial: historial
            })
        });
        
        const data = await res.json();
        ocultarTyping();
        
        // Manejar rate limiting (HTTP 429)
        if (data.rate_limit) {
            const segundos = data.retry_after || 60;
            agregarBurbujaBot(`⏳ ${data.message}`, true);
            chatInput.disabled = true;
            btnEnviar.disabled = true;
            setTimeout(() => {
                chatInput.disabled = false;
                btnEnviar.disabled = false;
                chatInput.focus();
            }, segundos * 1000);
            return;
        }
        
        if (data.success) {
            if (data.tool_usada) {
                mostrarConsultandoBD(data.tool_usada);
                await new Promise(r => setTimeout(r, 800));
                ocultarConsultandoBD();
            }
            
            agregarBurbujaBot(data.respuesta, true, data.resultado_raw, data.tool_usada);
            // Guardar historial con metadata de tool call
            historial.push({ 
                role: 'assistant', 
                content: data.respuesta,
                tool_usada: data.tool_usada || null,
                resultado_raw: data.resultado_raw || null
            });
            guardarHistorial();
        } else {
            agregarBurbujaBot('Lo siento, ocurrió un error: ' + (data.message || 'No se pudo obtener respuesta.'));
        }
    } catch (err) {
        ocultarTyping();
        ocultarConsultandoBD();
        console.error(err);
        agregarBurbujaBot('Lo siento, no pude conectarme con el servicio de IA. Por favor intenta de nuevo más tarde.');
    } finally {
        chatInput.disabled = false;
        btnEnviar.disabled = false;
        chatInput.focus();
    }
}

// Renderizar historial al cargar
renderHistorial();
renderizarFavoritos();
</script>
