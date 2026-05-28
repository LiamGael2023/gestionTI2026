<!-- Estilos del chatbot -->
<style>
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
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
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
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}
.chat-bubble-bot pre {
    background: #f1f5f9;
    padding: 0.75rem;
    border-radius: 8px;
    overflow-x: auto;
    font-size: 0.85rem;
}
.chat-bubble-bot code {
    background: #f1f5f9;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-size: 0.85rem;
    color: #004d99;
}
.chat-input-area {
    display: flex;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    margin-top: 1rem;
}
.chat-input {
    flex: 1;
    border-radius: 24px;
    padding: 0.75rem 1.25rem;
    border: 1px solid #cbd5e1;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
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
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
    margin-bottom: 1rem;
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

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes typingBounce {
    0%, 80%, 100% { transform: scale(0.6); }
    40% { transform: scale(1); }
}
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

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const btnEnviar = document.getElementById('btn-enviar');

// Cargar historial desde localStorage
let historial = JSON.parse(localStorage.getItem('chat_historial_pech') || '[]');

function renderHistorial() {
    if (!historial.length) return;
    // Limpiar solo los mensajes dinámicos (mantener bienvenida)
    const bubbles = chatMessages.querySelectorAll('.chat-bubble-user, .chat-bubble-bot:not(:first-child)');
    bubbles.forEach(b => b.remove());
    
    historial.forEach(h => {
        if (h.role === 'user') {
            agregarBurbujaUsuario(h.content, false);
        } else if (h.role === 'assistant') {
            agregarBurbujaBot(h.content, false);
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

function agregarBurbujaBot(texto, animar = true, resultadoRaw = null) {
    const div = document.createElement('div');
    div.className = 'chat-bubble chat-bubble-bot';
    div.style.animation = animar ? 'fadeInUp 0.3s ease' : 'none';
    
    let tablaHTML = '';
    if (resultadoRaw && resultadoRaw.columns && resultadoRaw.rows) {
        tablaHTML = renderizarTablaDesdeJSON(resultadoRaw);
    }
    
    div.innerHTML = `
        <div class="d-flex align-items-center mb-2">
            <span class="avatar avatar-sm bg-primary-lt me-2"><i class="ti ti-robot"></i></span>
            <strong>Asistente PECH</strong>
        </div>
        ${tablaHTML}
        <div class="mb-0">${formatearMarkdown(texto)}</div>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
}

function renderizarTablaDesdeJSON(data) {
    if (!data.columns || !data.rows || data.rows.length === 0) return '';
    
    const cols = data.columns;
    const rows = data.rows;
    
    let html = '<div class="table-responsive mb-3" style="max-height: 300px;">';
    html += '<table class="table table-sm table-vcenter card-table table-striped" style="font-size: 0.85rem;">';
    html += '<thead class="table-light sticky-top"><tr>';
    cols.forEach(col => {
        html += `<th>${escapeHtml(col.label)}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    rows.forEach(row => {
        html += '<tr>';
        cols.forEach(col => {
            const val = row[col.key] ?? '-';
            // Detectar si es numérico/dinero para alinear a la derecha
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
    // Conversión de markdown a HTML
    let html = escapeHtml(texto);
    
    // Detectar y convertir tablas markdown | col1 | col2 |
    // Buscar bloques que parezcan tablas markdown
    const tableRegex = /((?:\|[^\n|]+)+\|)\n((?:\|[-:\s]+)+\|)\n((?:\|[^\n|]+)+\|(?:\n\|[^\n|]+\|)*)/g;
    html = html.replace(tableRegex, (match, headerLine, separatorLine, bodyLines) => {
        const headers = headerLine.split('|').map(h => h.trim()).filter(h => h);
        const rows = bodyLines.trim().split('\n').map(line => {
            return line.split('|').map(c => c.trim()).filter((_, i) => i > 0 || c.trim());
        });
        
        let tbl = '<div class="table-responsive mb-2"><table class="table table-sm table-vcenter table-striped" style="font-size:0.85rem;"><thead class="table-light"><tr>';
        headers.forEach(h => {
            tbl += `<th>${h}</th>`;
        });
        tbl += '</tr></thead><tbody>';
        rows.forEach(row => {
            tbl += '<tr>';
            row.forEach(cell => {
                tbl += `<td>${escapeHtml(cell)}</td>`;
            });
            tbl += '</tr>';
        });
        tbl += '</tbody></table></div>';
        return tbl;
    });
    
    // Código en línea `codigo`
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    
    // Bloques de código ```
    html = html.replace(/```(\w*)\n?([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
    
    // Negrita **texto**
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    
    // Cursiva *texto*
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    
    // Saltos de línea
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
            // Mantener solo el mensaje de bienvenida
            const bubbles = chatMessages.querySelectorAll('.chat-bubble-user, .chat-bubble-bot:not(:first-child)');
            bubbles.forEach(b => b.remove());
            Swal.fire({ icon: 'success', title: 'Listo', timer: 1000, showConfirmButton: false });
        }
    });
}

function mostrarConsultandoBD() {
    const div = document.createElement('div');
    div.className = 'chat-typing';
    div.id = 'chat-consultando-bd';
    div.innerHTML = `
        <i class="ti ti-database text-primary"></i>
        <span class="text-muted small">Consultando base de datos...</span>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
}

function ocultarConsultandoBD() {
    const el = document.getElementById('chat-consultando-bd');
    if (el) el.remove();
}

async function enviarMensaje() {
    const texto = chatInput.value.trim();
    if (!texto) return;
    
    // Deshabilitar input
    chatInput.value = '';
    chatInput.disabled = true;
    btnEnviar.disabled = true;
    
    // Agregar mensaje del usuario
    agregarBurbujaUsuario(texto);
    historial.push({ role: 'user', content: texto });
    guardarHistorial();
    
    // Mostrar indicador de escritura
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
        
        if (data.success) {
            // Si usó una tool, mostrar indicador de consulta a BD brevemente
            if (data.tool_usada) {
                mostrarConsultandoBD();
                await new Promise(r => setTimeout(r, 800));
                ocultarConsultandoBD();
            }
            
            agregarBurbujaBot(data.respuesta, true, data.resultado_raw);
            historial.push({ role: 'assistant', content: data.respuesta });
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
</script>
