/**
 * chavibot.js
 * Lógica del chatbot ChaviBot — CHAVIMOCHIC
 * Maneja: chat, feedback, entrenamiento RAG
 */

const ChaviBot = (() => {

    // ── Config ───────────────────────────────────────────────────
    const AJAX_URL = window.CHAVIBOT_AJAX_URL || 'modules/chatbot/ajax/chavibot.ajax.php';

    // ── Estado ───────────────────────────────────────────────────
    let enviando   = false;
    let idUltimoMsg= 0;

    // ── DOM refs ─────────────────────────────────────────────────
    const $msgs     = () => document.getElementById('cb-messages');
    const $input    = () => document.getElementById('cb-input');
    const $sendBtn  = () => document.getElementById('cb-send-btn');

    // ════════════════════════════════════════════════════════════
    // INICIALIZAR
    // ════════════════════════════════════════════════════════════
    function init() {
        // Enter para enviar (Shift+Enter = nueva línea)
        $input()?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                enviar();
            }
        });
        // Auto-resize textarea
        $input()?.addEventListener('input', () => {
            const el = $input();
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        });

        // Botón enviar
        $sendBtn()?.addEventListener('click', enviar);

        // Botón toggle panel entrenamiento
        document.getElementById('cb-btn-train')
            ?.addEventListener('click', toggleTrainPanel);

        // Formulario entrenamiento
        document.getElementById('cb-form-rag')
            ?.addEventListener('submit', submitRAG);

        // Tabs entrenamiento
        document.querySelectorAll('.cb-tab').forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });

        // Sugerencias rápidas
        document.querySelectorAll('.cb-sug-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                $input().value = btn.textContent.trim();
                enviar();
            });
        });

        // Cargar lista RAG si el panel existe
        if (document.getElementById('cb-rag-lista')) cargarRAG();
    }

    // ════════════════════════════════════════════════════════════
    // CHAT — ENVIAR MENSAJE
    // ════════════════════════════════════════════════════════════
    async function enviar() {
        if (enviando) return;
        const input  = $input();
        const mensaje = input.value.trim();
        if (!mensaje) return;

        // Ocultar bienvenida y sugerencias
        document.getElementById('cb-bienvenida')?.remove();
        document.getElementById('cb-sugerencias-wrap')?.remove();

        // Mostrar mensaje del usuario
        agregarBurbuja('user', mensaje);
        input.value = '';
        input.style.height = 'auto';

        // Deshabilitar envío
        enviando = true;
        $sendBtn().disabled = true;
        const typingId = mostrarTyping();

        try {
            const res  = await post({ accion: 'responder', mensaje });
            quitarTyping(typingId);

            if (res.error) {
                agregarBurbuja('bot', '⚠️ ' + (res.respuesta || res.mensaje || 'Error desconocido.'));
            } else {
                idUltimoMsg = res.idMensaje || 0;
                agregarBurbuja('bot', res.respuesta, {
                    schema   : res.schema,
                    filas    : res.totalFilas,
                    tiempoMs : res.tiempoMs,
                    idMensaje: idUltimoMsg,
                });
            }
        } catch (err) {
            quitarTyping(typingId);
            agregarBurbuja('bot', '⚠️ Error de conexión. Verifica que el servidor esté activo.');
        }

        enviando = false;
        $sendBtn().disabled = false;
        input.focus();
    }

    // ════════════════════════════════════════════════════════════
    // BURBUJAS
    // ════════════════════════════════════════════════════════════
    function agregarBurbuja(tipo, texto, meta = {}) {
        const wrap = document.createElement('div');
        wrap.className = `cb-bubble-wrap ${tipo}`;

        const avatar = document.createElement('div');
        avatar.className = 'cb-avatar';
        avatar.textContent = tipo === 'bot' ? '🤖' : '👤';

        const contenido = document.createElement('div');

        const bubble = document.createElement('div');
        bubble.className = 'cb-bubble';
        bubble.innerHTML = formatearTexto(texto);
        contenido.appendChild(bubble);

        // Meta info (solo para bot)
        if (tipo === 'bot') {
            const metaDiv = document.createElement('div');
            metaDiv.className = 'cb-bubble-meta';

            const hora = new Date().toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'});
            metaDiv.innerHTML = `<span>${hora}</span>`;

            if (meta.schema) {
                metaDiv.innerHTML += `<span title="Tabla consultada">📊 ${meta.schema}</span>`;
            }
            if (meta.tiempoMs) {
                metaDiv.innerHTML += `<span>⚡ ${meta.tiempoMs}ms</span>`;
            }
            contenido.appendChild(metaDiv);

            // Feedback
            if (meta.idMensaje) {
                contenido.appendChild(crearFeedback(meta.idMensaje));
            }
        }

        wrap.appendChild(avatar);
        wrap.appendChild(contenido);
        $msgs().appendChild(wrap);
        scrollAbajo();
    }

    function crearFeedback(idMensaje) {
        const div = document.createElement('div');
        div.className = 'cb-feedback';
        div.innerHTML = `
            <button class="cb-fb-btn" data-util="1" data-id="${idMensaje}" title="Útil">👍 Útil</button>
            <button class="cb-fb-btn" data-util="0" data-id="${idMensaje}" title="No útil">👎 Mejorar</button>
        `;
        div.querySelectorAll('.cb-fb-btn').forEach(btn => {
            btn.addEventListener('click', () => enviarFeedback(btn, idMensaje, btn.dataset.util));
        });
        return div;
    }

    async function enviarFeedback(btn, idMensaje, util) {
        const wrap = btn.parentElement;
        wrap.querySelectorAll('.cb-fb-btn').forEach(b => b.classList.remove('activo'));
        btn.classList.add('activo');

        await post({ accion: 'feedback', idMensaje, util });
    }

    // ════════════════════════════════════════════════════════════
    // TYPING INDICATOR
    // ════════════════════════════════════════════════════════════
    function mostrarTyping() {
        const id   = 'typing-' + Date.now();
        const wrap = document.createElement('div');
        wrap.className = 'cb-bubble-wrap bot';
        wrap.id = id;
        wrap.innerHTML = `
            <div class="cb-avatar">🤖</div>
            <div class="cb-bubble" style="padding:0">
                <div class="cb-typing">
                    <span></span><span></span><span></span>
                </div>
            </div>`;
        $msgs().appendChild(wrap);
        scrollAbajo();
        return id;
    }

    function quitarTyping(id) {
        document.getElementById(id)?.remove();
    }

    // ════════════════════════════════════════════════════════════
    // PANEL ENTRENAMIENTO
    // ════════════════════════════════════════════════════════════
    function toggleTrainPanel() {
        const panel = document.getElementById('cb-train-panel');
        panel?.classList.toggle('oculto');
        if (!panel?.classList.contains('oculto')) cargarRAG();
    }

    function switchTab(tab) {
        document.querySelectorAll('.cb-tab').forEach(t => t.classList.remove('activo'));
        document.querySelector(`[data-tab="${tab}"]`)?.classList.add('activo');
        document.querySelectorAll('.cb-tab-content').forEach(c => c.style.display = 'none');
        document.getElementById(`cb-tab-${tab}`)?.style.setProperty('display', 'block');
        if (tab === 'lista') cargarRAG();
    }

    async function submitRAG(e) {
        e.preventDefault();
        const alerta = document.getElementById('cb-rag-alert');
        ocultarAlerta(alerta);

        const datos = {
            accion   : 'agregar_rag',
            pregunta : document.getElementById('rag-pregunta')?.value.trim(),
            palabras : document.getElementById('rag-palabras')?.value.trim(),
            schema   : document.getElementById('rag-schema')?.value.trim(),
            sql      : document.getElementById('rag-sql')?.value.trim(),
            respBase : document.getElementById('rag-respbase')?.value.trim(),
            rol      : document.getElementById('rag-rol')?.value.trim(),
            area     : document.getElementById('rag-area')?.value.trim(),
            canal    : document.getElementById('rag-canal')?.value,
        };

        if (!datos.pregunta || !datos.sql) {
            mostrarAlerta(alerta, 'La Pregunta y el SQL son obligatorios.', 'error');
            return;
        }

        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled = true; btn.textContent = 'Guardando...';

        try {
            const res = await post(datos);
            if (res.error) {
                mostrarAlerta(alerta, res.mensaje, 'error');
            } else {
                mostrarAlerta(alerta, res.mensaje, 'ok');
                e.target.reset();
                cargarRAG();
            }
        } catch {
            mostrarAlerta(alerta, 'Error de conexión.', 'error');
        }

        btn.disabled = false; btn.textContent = 'Guardar ejemplo';
    }

    async function cargarRAG() {
        const lista = document.getElementById('cb-rag-lista');
        if (!lista) return;
        lista.innerHTML = '<p style="color:#94a3b8;font-size:.84rem">Cargando...</p>';

        try {
            const res = await post({ accion: 'listar_rag' });
            if (res.error || !res.datos?.length) {
                lista.innerHTML = '<p style="color:#94a3b8;font-size:.84rem">Sin ejemplos aún.</p>';
                return;
            }
            lista.innerHTML = '';
            res.datos.forEach(ej => lista.appendChild(crearItemRAG(ej)));
        } catch {
            lista.innerHTML = '<p style="color:red;font-size:.84rem">Error al cargar.</p>';
        }
    }

    function crearItemRAG(ej) {
        const div = document.createElement('div');
        div.className = 'cb-rag-item';
        div.innerHTML = `
            <div class="cb-rag-item-header">
                <span class="cb-rag-pregunta">${esc(ej.preguntaEjemplo)}</span>
                <span class="cb-rag-badge ${ej.activo ? 'activo' : 'inactivo'}">
                    ${ej.activo ? 'Activo' : 'Inactivo'}
                </span>
            </div>
            <div class="cb-rag-schema">📊 ${esc(ej.schemaObjetivo)} · Canal: ${esc(ej.canal)}</div>
            <div class="cb-rag-stats">Usado: ${ej.vecesUsado}x · Útil: ${ej.vecesUtil}x · ${esc(ej.fechaCreacion)}</div>
            <div class="cb-rag-actions">
                <button class="cb-btn-sm" onclick="ChaviBot.toggleRAG(${ej.idEjemplo}, ${ej.activo ? 0 : 1})">
                    ${ej.activo ? 'Desactivar' : 'Activar'}
                </button>
            </div>`;
        return div;
    }

    async function toggleRAG(id, activo) {
        await post({ accion: 'toggle_rag', id, activo });
        cargarRAG();
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════
    function formatearTexto(texto) {
        return texto
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g,    '<em>$1</em>')
            .replace(/\n/g,           '<br>');
    }

    function esc(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function scrollAbajo() {
        const el = $msgs();
        if (el) el.scrollTop = el.scrollHeight;
    }

    function mostrarAlerta(el, msg, tipo) {
        if (!el) return;
        el.textContent = msg;
        el.className = `cb-alert ${tipo} show`;
        setTimeout(() => el.classList.remove('show'), 4000);
    }

    function ocultarAlerta(el) {
        if (el) el.classList.remove('show');
    }

    async function post(datos) {
        const body = new FormData();
        Object.entries(datos).forEach(([k, v]) => body.append(k, v ?? ''));
        const res = await fetch(AJAX_URL, { method: 'POST', body });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    // API pública
    return { init, toggleRAG };
})();

document.addEventListener('DOMContentLoaded', ChaviBot.init);
