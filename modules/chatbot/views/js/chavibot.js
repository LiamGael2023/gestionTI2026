/**
 * chavibot.js — UI WhatsApp-style para ChaviBot
 * Verde #009540 / Azul #004d99 — colores PECH Tabler
 */
const ChaviBot = (() => {

    const AJAX_URL = window.CHAVIBOT_AJAX_URL || '../ajax/chavibot.ajax.php';

    let enviando   = false;
    let idUltimoMsg = 0;

    // ── DOM ──────────────────────────────────────────────────────────────
    const $msgs    = () => document.getElementById('cb-messages');
    const $input   = () => document.getElementById('cb-input');
    const $sendBtn = () => document.getElementById('cb-send-btn');

    // ── INIT ─────────────────────────────────────────────────────────────
    function init() {
        $input()?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviar(); }
        });
        $input()?.addEventListener('input', () => {
            const el = $input();
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 100) + 'px';
        });
        $sendBtn()?.addEventListener('click', enviar);
        document.getElementById('cb-btn-train')?.addEventListener('click', toggleTrain);
        document.getElementById('cb-form-rag')?.addEventListener('submit', submitRAG);
        document.querySelectorAll('.cb-tab').forEach(t =>
            t.addEventListener('click', () => switchTab(t.dataset.tab))
        );
        document.querySelectorAll('.cb-sug-btn').forEach(b =>
            b.addEventListener('click', () => { $input().value = b.textContent.trim(); enviar(); })
        );
        if (document.getElementById('cb-rag-lista')) cargarRAG();

        // Chip de fecha HOY
        const chip = document.createElement('div');
        chip.className = 'cb-date-chip';
        chip.innerHTML = '<span>HOY</span>';
        $msgs()?.prepend(chip);
    }

    // ── ENVIAR ────────────────────────────────────────────────────────────
    async function enviar() {
        if (enviando) return;
        const inp     = $input();
        const mensaje = inp.value.trim();
        if (!mensaje) return;

        document.getElementById('cb-bienvenida')?.remove();
        document.getElementById('cb-sugerencias-wrap')?.remove();

        agregarBurbuja('user', mensaje);
        inp.value = ''; inp.style.height = 'auto';

        enviando = true; $sendBtn().disabled = true;
        const typId = mostrarTyping();

        try {
            const res = await post({ accion: 'responder', mensaje });
            quitarTyping(typId);
            if (res.error) {
                agregarBurbuja('bot', '⚠️ ' + (res.respuesta || res.mensaje || 'Error.'));
            } else {
                idUltimoMsg = res.idMensaje || 0;
                agregarBurbuja('bot', res.respuesta, {
                    schema: res.schema, filas: res.totalFilas,
                    tiempoMs: res.tiempoMs, idMensaje: idUltimoMsg,
                });
            }
        } catch {
            quitarTyping(typId);
            agregarBurbuja('bot', '⚠️ Error de conexión. Verifica que Ollama esté activo.');
        }

        enviando = false; $sendBtn().disabled = false; inp.focus();
    }

    // ── BURBUJAS ──────────────────────────────────────────────────────────
    function agregarBurbuja(tipo, texto, meta = {}) {
        const wrap = document.createElement('div');
        wrap.className = `cb-bubble-wrap ${tipo}`;

        const bubble = document.createElement('div');
        bubble.className = 'cb-bubble';
        bubble.innerHTML = formatear(texto);

        const cont = document.createElement('div');
        cont.appendChild(bubble);

        // Meta: hora + ticks (usuario) | hora + schema (bot)
        const metaDiv = document.createElement('div');
        metaDiv.className = 'cb-bubble-meta';
        const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

        if (tipo === 'user') {
            metaDiv.innerHTML = `<span>${hora}</span>
            <svg width="15" height="10" viewBox="0 0 18 10" fill="none">
              <path stroke="#34b7f1" stroke-width="1.8" stroke-linecap="round" d="M1 5l3.5 3.5L13 1"/>
              <path stroke="#34b7f1" stroke-width="1.8" stroke-linecap="round" d="M5 5l3.5 3.5L17 1" opacity=".6"/>
            </svg>`;
        } else {
            let extra = `<span>${hora}</span>`;
            if (meta.schema) extra += `<span>📊 ${esc(meta.schema)}</span>`;
            if (meta.tiempoMs) extra += `<span>⚡ ${meta.tiempoMs}ms</span>`;
            metaDiv.innerHTML = extra;
        }
        cont.appendChild(metaDiv);

        // Feedback (solo bot)
        if (tipo === 'bot' && meta.idMensaje) {
            cont.appendChild(crearFeedback(meta.idMensaje));
        }

        wrap.appendChild(cont);
        $msgs().appendChild(wrap);
        scroll();
    }

    function crearFeedback(id) {
        const d = document.createElement('div');
        d.className = 'cb-feedback';
        d.innerHTML = `<button class="cb-fb-btn" data-util="1" data-id="${id}">👍 Útil</button>
                       <button class="cb-fb-btn" data-util="0" data-id="${id}">👎 Mejorar</button>`;
        d.querySelectorAll('.cb-fb-btn').forEach(b =>
            b.addEventListener('click', () => {
                d.querySelectorAll('.cb-fb-btn').forEach(x => x.classList.remove('activo'));
                b.classList.add('activo');
                post({ accion: 'feedback', idMensaje: id, util: b.dataset.util });
            })
        );
        return d;
    }

    // ── TYPING ────────────────────────────────────────────────────────────
    function mostrarTyping() {
        const id   = 'typ-' + Date.now();
        const wrap = document.createElement('div');
        wrap.className = 'cb-bubble-wrap bot'; wrap.id = id;
        wrap.innerHTML = `<div class="cb-bubble" style="padding:10px 14px">
            <div class="cb-typing"><span></span><span></span><span></span></div></div>`;
        $msgs().appendChild(wrap); scroll();
        return id;
    }
    function quitarTyping(id) { document.getElementById(id)?.remove(); }

    // ── ENTRENAMIENTO ─────────────────────────────────────────────────────
    function toggleTrain() {
        const p = document.getElementById('cb-train-panel');
        p?.classList.toggle('oculto');
        if (!p?.classList.contains('oculto')) cargarRAG();
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
        alerta?.classList.remove('show');
        const datos = {
            accion: 'agregar_rag',
            pregunta: v('rag-pregunta'), palabras: v('rag-palabras'),
            schema: v('rag-schema'),     sql: v('rag-sql'),
            respBase: v('rag-respbase'), rol: v('rag-rol'),
            canal: v('rag-canal'),
        };
        if (!datos.pregunta || !datos.sql) {
            mostrarAlerta(alerta, 'La Pregunta y el SQL son obligatorios.', 'error'); return;
        }
        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled = true; btn.textContent = 'Guardando…';
        try {
            const res = await post(datos);
            if (res.error) mostrarAlerta(alerta, res.mensaje, 'error');
            else { mostrarAlerta(alerta, res.mensaje, 'ok'); e.target.reset(); cargarRAG(); }
        } catch { mostrarAlerta(alerta, 'Error de conexión.', 'error'); }
        btn.disabled = false; btn.textContent = '💾 Guardar';
    }
    async function cargarRAG() {
        const lista = document.getElementById('cb-rag-lista');
        if (!lista) return;
        lista.innerHTML = '<p style="color:var(--txt3);font-size:.82rem;padding:.25rem">Cargando…</p>';
        try {
            const res = await post({ accion: 'listar_rag' });
            if (!res.datos?.length) { lista.innerHTML = '<p style="color:var(--txt3);font-size:.82rem">Sin ejemplos.</p>'; return; }
            lista.innerHTML = '';
            res.datos.forEach(ej => lista.appendChild(itemRAG(ej)));
        } catch { lista.innerHTML = '<p style="color:red;font-size:.82rem">Error al cargar.</p>'; }
    }
    function itemRAG(ej) {
        const d = document.createElement('div');
        d.className = 'cb-rag-item';
        d.innerHTML = `
            <div class="cb-rag-item-header">
                <span class="cb-rag-pregunta">${esc(ej.preguntaEjemplo)}</span>
                <span class="cb-rag-badge ${ej.activo ? 'activo' : 'inactivo'}">${ej.activo ? 'Activo' : 'Inactivo'}</span>
            </div>
            <div class="cb-rag-schema">📊 ${esc(ej.schemaObjetivo)} · ${esc(ej.canal)}</div>
            <div class="cb-rag-stats">Usado: ${ej.vecesUsado}x · Útil: ${ej.vecesUtil}x · ${esc(ej.fechaCreacion)}</div>
            <div class="cb-rag-actions">
                <button class="cb-btn-sm" onclick="ChaviBot.toggleRAG(${ej.idEjemplo},${ej.activo?0:1})">
                    ${ej.activo ? 'Desactivar' : 'Activar'}
                </button>
            </div>`;
        return d;
    }
    async function toggleRAG(id, activo) {
        await post({ accion: 'toggle_rag', id, activo });
        cargarRAG();
    }

    // ── HELPERS ───────────────────────────────────────────────────────────
    function formatear(txt) {
        return txt
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g,    '<em>$1</em>')
            .replace(/\n/g,           '<br>');
    }
    function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function scroll() { const el = $msgs(); if(el) el.scrollTop = el.scrollHeight; }
    function v(id) { return document.getElementById(id)?.value.trim() || ''; }
    function mostrarAlerta(el, msg, tipo) {
        if (!el) return;
        el.textContent = msg; el.className = `cb-alert ${tipo} show`;
        setTimeout(() => el.classList.remove('show'), 4000);
    }
    async function post(datos) {
        const body = new FormData();
        Object.entries(datos).forEach(([k,v]) => body.append(k, v ?? ''));
        const res = await fetch(AJAX_URL, { method: 'POST', body });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    return { init, toggleRAG };
})();

document.addEventListener('DOMContentLoaded', ChaviBot.init);
