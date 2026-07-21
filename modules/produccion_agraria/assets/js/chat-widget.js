/* ============================================================
   Chat Widget Flotante — Asistente IA PECH
   ============================================================ */
(function() {
    'use strict';

    var historial = JSON.parse(localStorage.getItem('cw_historial') || '[]');
    var panel, messages, input;
    var typingEl = null;

    var TOOL_LABELS = {
        'consultar_stock': 'Stock', 'consultar_ventas': 'Ventas',
        'consultar_proformas': 'Proformas', 'consultar_vouchers': 'Vouchers',
        'consultar_productos': 'Productos', 'consultar_clientes': 'Clientes',
        'consultar_mermas': 'Mermas', 'consultar_kardex': 'Kardex',
        'consultar_top_productos_vendidos': 'Top productos',
        'consultar_valorizacion_inventario': 'Valorizacion',
        'consultar_ventas_por_mes': 'Ventas mensuales',
        'consultar_vouchers_saldo': 'Saldos vouchers',
        'consultar_grafico': 'Grafico', 'consultar_resumen': 'Resumen',
        'consultar_comparativa': 'Comparativa', 'consultar_buscar': 'Busqueda',
        'consultar_recomendaciones': 'Recomendaciones'
    };

    var PROMPTS = [
        { label: 'Stock actual', icon: 'ti-package', q: '¿Cuál es el stock actual de todos los productos?' },
        { label: 'Ventas del mes', icon: 'ti-chart-line', q: '¿Cómo van las ventas este mes?' },
        { label: 'Top productos', icon: 'ti-trophy', q: '¿Cuáles son los productos más vendidos?' },
        { label: 'Proformas', icon: 'ti-file-invoice', q: '¿Cuántas proformas pendientes hay?' },
        { label: 'Valor inventario', icon: 'ti-coin', q: '¿Cuál es el valor total del inventario?' },
        { label: 'Mermas', icon: 'ti-trash', q: '¿Qué mermas se han registrado este mes?' },
        { label: 'Grafico ventas', icon: 'ti-chart-bar', q: 'Muéstrame un gráfico de ventas por mes' },
        { label: 'Resumen', icon: 'ti-notes', q: 'Dame un resumen ejecutivo del día' }
    ];

    function createDOM() {
        var body = document.body;

        // Toggle button
        var toggle = document.createElement('button');
        toggle.id = 'chat-widget-toggle';
        toggle.innerHTML = '<i class="ti ti-messages"></i><span class="cw-badge" id="cw-badge"></span>';
        toggle.title = 'Asistente IA PECH';
        body.appendChild(toggle);

        // Panel
        panel = document.createElement('div');
        panel.id = 'chat-widget-panel';
        panel.innerHTML = [
            '<div class="cw-header">',
            '<div class="cw-avatar"><i class="ti ti-robot"></i></div>',
            '<div><div class="cw-title">Asistente PECH</div><div class="cw-subtitle">Consulta inteligente</div></div>',
            '<button class="cw-expand" id="cw-expand" title="Expandir al centro"><i class="ti ti-arrows-maximize"></i></button>',
            '<button class="cw-collapse-btn" id="cw-collapse-expand" title="Volver a la esquina" style="display:none"><i class="ti ti-arrows-minimize"></i></button>',
            '<button class="cw-close" id="cw-close"><i class="ti ti-x"></i></button>',
            '</div>',
            '<div class="cw-prompts" id="cw-prompts"></div>',
            '<div class="cw-messages" id="cw-messages"></div>',
            '<div class="cw-input-area">',
            '<button class="cw-btn-mic" id="cw-mic" title="Voz"><i class="ti ti-microphone"></i></button>',
            '<input type="text" class="cw-input" id="cw-input" placeholder="Escribe tu consulta..." autocomplete="off">',
            '<button class="cw-btn-send" id="cw-send" title="Enviar"><i class="ti ti-send"></i></button>',
            '<button class="cw-btn-clear" id="cw-clear" title="Limpiar chat"><i class="ti ti-trash"></i></button>',
            '</div>'
        ].join('');
        body.appendChild(panel);

        messages = document.getElementById('cw-messages');
        input = document.getElementById('cw-input');

        // Prompts
        renderPrompts();
        // Welcome
        agregarBurbujaBot('Hola, soy el asistente virtual de PECH. Puedo consultar stock, ventas, mermas, proformas, vouchers, generar graficos y mas. ¿En que puedo ayudarte?', false);
        // Load historial
        if (historial.length) renderHistorial();
    }

    function renderPrompts() {
        var cont = document.getElementById('cw-prompts');
        cont.innerHTML = PROMPTS.map(function(p) {
            return '<span class="cw-prompt-chip" data-q="' + escapeAttr(p.q) + '"><i class="ti ' + p.icon + '"></i>' + escapeHtml(p.label) + '</span>';
        }).join('');
    }

    function renderHistorial() {
        historial.forEach(function(h) {
            if (h.role === 'user') agregarBurbujaUsuario(h.content);
            else if (h.role === 'assistant') agregarBurbujaBot(h.content, false, h.resultado_raw || null, h.tool_usada || null);
        });
        scrollToBottom();
    }

    function agregarBurbujaUsuario(texto) {
        var div = document.createElement('div');
        div.className = 'cw-bubble cw-bubble-user';
        div.innerHTML = '<strong>Tú</strong><p class="mb-0 mt-1">' + escapeHtml(texto) + '</p>';
        messages.appendChild(div);
        scrollToBottom();
    }

    function agregarBurbujaBot(texto, animar, resultadoRaw, toolUsada) {
        var div = document.createElement('div');
        div.className = 'cw-bubble cw-bubble-bot';
        if (animar !== false) div.style.animation = 'cwFadeIn 0.25s ease';

        var parts = [];

        if (toolUsada) {
            var label = toolUsada.split('+').map(function(t) { return TOOL_LABELS[t.trim()] || t.trim(); }).join(' + ');
            parts.push('<div class="mb-1"><span class="badge bg-primary-lt text-primary small"><i class="ti ti-database me-1"></i>' + escapeHtml(label) + '</span></div>');
        }

        var tablaHTML = '', graficoHTML = '', exportHTML = '';
        if (resultadoRaw) {
            if (resultadoRaw.grafico) {
                var gid = 'cwchart-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
                graficoHTML = '<div class="cw-chart-box"><div class="cw-chart-title"><i class="ti ti-chart-bar me-1"></i>' + escapeHtml(resultadoRaw.grafico.titulo || 'Grafico') + '</div><div id="' + gid + '" style="min-height:200px;"></div></div>';
                div._chartId = gid;
                div._chartConfig = resultadoRaw.grafico;
            }
            if (resultadoRaw.columns && resultadoRaw.rows) {
                tablaHTML = renderTabla(resultadoRaw);
                exportHTML = '<div class="cw-export-btns"><button class="btn btn-sm btn-outline-secondary" onclick="ChatWidget.exportarCSV(\'' + escapeAttr(JSON.stringify(resultadoRaw)) + '\')" title="CSV"><i class="ti ti-file-spreadsheet me-1"></i>CSV</button></div>';
            }
        }

        parts.push(exportHTML + tablaHTML + '<div>' + formatearMarkdown(texto) + '</div>' + graficoHTML);

        div.innerHTML = '<strong><i class="ti ti-robot me-1"></i>Asistente</strong>' + parts.join('');
        messages.appendChild(div);
        scrollToBottom();

        if (div._chartId) setTimeout(function() { renderGrafico(div); }, 100);
    }

    function renderTabla(data) {
        var html = '<table class="table table-sm mb-1"><thead><tr>';
        data.columns.forEach(function(c) {
            html += '<th>' + escapeHtml(c.label || c.key || c) + '</th>';
        });
        html += '</tr></thead><tbody>';
        data.rows.forEach(function(r) {
            html += '<tr>';
            data.columns.forEach(function(c) {
                var key = c.key || c;
                var v = r[key];
                html += '<td>' + (v !== null && v !== undefined ? escapeHtml(String(v)) : '-') + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }

    function renderGrafico(div) {
        if (typeof ApexCharts === 'undefined') {
            loadScript('https://cdn.jsdelivr.net/npm/apexcharts', function() {
                renderApexChart(div._chartId, div._chartConfig);
            });
        } else {
            renderApexChart(div._chartId, div._chartConfig);
        }
    }

    function renderApexChart(id, config) {
        var el = document.getElementById(id);
        if (!el) return;
        var opts = {
            chart: { type: config.tipo || 'bar', height: 200, toolbar: { show: false }, animations: { enabled: true } },
            series: config.series || [],
            xaxis: config.xaxis || { categories: config.categorias || [] },
            colors: config.colores || ['#004d99', '#2fb344', '#f59e0b', '#dc2626', '#7c3aed'],
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: function(v) { return config.formato_moneda ? 'S/ ' + v.toLocaleString() : v.toLocaleString(); } } }
        };
        if (config.tipo === 'pie' || config.tipo === 'donut') {
            opts.labels = config.categorias || [];
            delete opts.xaxis;
        }
        new ApexCharts(el, opts).render();
    }

    function formatearMarkdown(texto) {
        if (!texto) return '';
        var html = escapeHtml(texto);
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        html = html.replace(/```(\w*)\n?([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    function showTyping() {
        if (typingEl) return;
        typingEl = document.createElement('div');
        typingEl.className = 'cw-typing';
        typingEl.innerHTML = '<div class="cw-typing-dot"></div><div class="cw-typing-dot"></div><div class="cw-typing-dot"></div><span class="ms-1 small text-muted">Pensando...</span>';
        messages.appendChild(typingEl);
        scrollToBottom();
    }
    function hideTyping() {
        if (typingEl) { typingEl.remove(); typingEl = null; }
    }

    function scrollToBottom() {
        if (messages) messages.scrollTop = messages.scrollHeight;
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function escapeAttr(s) {
        return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function loadScript(url, cb) {
        var s = document.createElement('script');
        s.src = url;
        s.onload = cb;
        document.head.appendChild(s);
    }

    function enviar(texto) {
        if (!texto.trim()) return;
        agregarBurbujaUsuario(texto);
        showTyping();
        input.value = '';
        input.disabled = true;

        var payload = { mensaje: texto, historial: historial };

        fetch(window.BASE_URL + '/index.php?module=produccion_agraria&action=chat_enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) {
            if (r.status === 429) {
                var retry = parseInt(r.headers.get('Retry-After') || '5');
                throw new Error('Demasiadas consultas. Espera ' + retry + ' segundos.');
            }
            return r.text();
        })
        .then(function(text) {
            hideTyping();
            input.disabled = false;
            input.focus();

            var trimmed = text.trim();
            var start = trimmed.indexOf('{');
            var end = trimmed.lastIndexOf('}');
            if (start === -1 || end === -1) {
                agregarBurbujaBot('Lo siento, ocurrio un error al procesar tu consulta.');
                return;
            }
            var data = JSON.parse(trimmed.substring(start, end + 1));

            if (data.success) {
                var respuesta = data.respuesta || 'No obtuve resultados. Intenta con otra consulta.';
                agregarBurbujaBot(respuesta, true, data.resultado_raw || null, data.tool_usada || null);

                historial.push({ role: 'user', content: texto });
                historial.push({ role: 'assistant', content: respuesta, resultado_raw: data.resultado_raw || null, tool_usada: data.tool_usada || null });
                if (historial.length > 40) historial = historial.slice(-40);
                localStorage.setItem('cw_historial', JSON.stringify(historial));
            } else {
                agregarBurbujaBot(data.message || 'Error al procesar la consulta.');
            }
        })
        .catch(function(err) {
            hideTyping();
            input.disabled = false;
            agregarBurbujaBot('Error: ' + err.message);
        });
    }

    function limpiarChat() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Limpiar chat?',
                text: 'Se borrara todo el historial.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, limpiar',
                cancelButtonText: 'Cancelar'
            }).then(function(r) {
                if (r.isConfirmed) doClear();
            });
        } else {
            if (confirm('¿Limpiar todo el historial del chat?')) doClear();
        }
    }

    function doClear() {
        historial = [];
        localStorage.removeItem('cw_historial');
        messages.innerHTML = '';
        agregarBurbujaBot('Hola, soy el asistente virtual de PECH. ¿En que puedo ayudarte?', false);
    }

    // Voice
    var recognition = null;
    function toggleVoice() {
        var mic = document.getElementById('cw-mic');
        if (!recognition) {
            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) { alert('Reconocimiento de voz no soportado en este navegador.'); return; }
            recognition = new SpeechRecognition();
            recognition.lang = 'es-PE';
            recognition.interimResults = false;
            recognition.onresult = function(e) { input.value = e.results[0][0].transcript; enviar(input.value); };
            recognition.onend = function() { mic.classList.remove('listening'); };
            recognition.onerror = function() { mic.classList.remove('listening'); };
        }
        if (mic.classList.contains('listening')) {
            recognition.stop();
        } else {
            recognition.start();
            mic.classList.add('listening');
        }
    }

    function exportarCSV(jsonStr) {
        try {
            var data = JSON.parse(jsonStr);
            if (!data.columns || !data.rows) return;
            var csv = '\uFEFF' + data.columns.map(function(c) { return c.label || c.key || c; }).join(';') + '\n';
            data.rows.forEach(function(r) {
                csv += data.columns.map(function(c) {
                    var key = c.key || c;
                    var v = r[key];
                    return v !== null && v !== undefined ? String(v) : '';
                }).join(';') + '\n';
            });
            downloadBlob(csv, 'consulta.csv', 'text/csv;charset=utf-8');
        } catch(e) {}
    }

    function downloadBlob(content, filename, mime) {
        var blob = new Blob([content], { type: mime });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        a.click();
    }

    // Expandir: morph del panel flotante al centro
    var overlayEl = null;
    function expandChat() {
        if (!overlayEl) {
            overlayEl = document.createElement('div');
            overlayEl.className = 'cw-overlay';
            overlayEl.addEventListener('click', function() { collapseChat(); });
            document.body.appendChild(overlayEl);
        }
        panel.classList.add('visible', 'cw-expanded');
        overlayEl.classList.add('visible');
        document.getElementById('cw-expand').style.display = 'none';
        document.getElementById('cw-collapse-expand').style.display = '';
        messages.style.maxHeight = 'none';
        scrollToBottom();
    }

    function collapseChat() {
        panel.classList.remove('cw-expanded');
        if (overlayEl) overlayEl.classList.remove('visible');
        document.getElementById('cw-expand').style.display = '';
        document.getElementById('cw-collapse-expand').style.display = 'none';
        messages.style.maxHeight = '320px';
        scrollToBottom();
    }

    // Events
    function initEvents() {
        document.getElementById('cw-close').addEventListener('click', function() {
            if (panel.classList.contains('cw-expanded')) collapseChat();
            panel.classList.remove('visible');
        });

        document.getElementById('chat-widget-toggle').addEventListener('click', function() {
            if (panel.classList.contains('cw-expanded')) {
                collapseChat();
                return;
            }
            panel.classList.toggle('visible');
            if (panel.classList.contains('visible')) {
                scrollToBottom();
                input.focus();
            }
        });

        document.getElementById('cw-send').addEventListener('click', function() {
            enviar(input.value);
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                enviar(input.value);
            }
        });

        document.getElementById('cw-clear').addEventListener('click', limpiarChat);
        document.getElementById('cw-mic').addEventListener('click', toggleVoice);
        document.getElementById('cw-expand').addEventListener('click', expandChat);
        document.getElementById('cw-collapse-expand').addEventListener('click', collapseChat);

        // Prompt chips
        document.getElementById('cw-prompts').addEventListener('click', function(e) {
            var chip = e.target.closest('.cw-prompt-chip');
            if (chip) {
                var q = chip.getAttribute('data-q');
                panel.classList.add('visible');
                if (q) enviar(q);
            }
        });
    }

    // Init
    function init() {
        createDOM();
        initEvents();
    }

    // Expose public API
    window.ChatWidget = {
        init: init,
        exportarCSV: exportarCSV
    };

    // Auto-init if loaded after DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
