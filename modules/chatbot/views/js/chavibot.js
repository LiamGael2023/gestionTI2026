/**
 * chavibot.js — ChaviBot UI
 * Colores PECH: verde #009540 / azul #004d99
 */
const ChaviBot = (() => {

    const AJAX_URL = window.CHAVIBOT_AJAX_URL || '../ajax/chavibot.ajax.php';
    let enviando    = false;
    let idUltimoMsg = 0;

    const $msgs    = () => document.getElementById('cb-messages');
    const $input   = () => document.getElementById('cb-input');
    const $sendBtn = () => document.getElementById('cb-send-btn');

    // ─── INIT ────────────────────────────────────────────────────────────
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

        // Chip de fecha
        const chip = document.createElement('div');
        chip.className = 'cb-date-chip';
        chip.innerHTML = `<span>${new Date().toLocaleDateString('es-PE',{day:'2-digit',month:'long'})}</span>`;
        $msgs()?.prepend(chip);
    }

    // ─── ENVIAR ──────────────────────────────────────────────────────────
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
                agregarBurbuja('bot', '⚠️ ' + (res.respuesta || res.mensaje || 'Error al procesar.'));
            } else {
                idUltimoMsg = res.idMensaje || 0;
                // NO pasar schema — no queremos mostrarlo al usuario
                agregarBurbuja('bot', res.respuesta, {
                    tiempoMs:  res.tiempoMs,
                    totalFilas: res.totalFilas,
                    idMensaje: idUltimoMsg,
                });
            }
        } catch {
            quitarTyping(typId);
            agregarBurbuja('bot', '⚠️ Error de conexión. Verifica que el sistema esté activo.');
        }

        enviando = false; $sendBtn().disabled = false; inp.focus();
    }

    // ─── BURBUJAS ────────────────────────────────────────────────────────
    function agregarBurbuja(tipo, texto, meta = {}) {
        const wrap = document.createElement('div');
        wrap.className = `cb-bubble-wrap ${tipo}`;

        const bubble = document.createElement('div');
        bubble.className = 'cb-bubble';
        bubble.innerHTML = formatear(texto);

        const cont = document.createElement('div');
        cont.appendChild(bubble);

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
            // Solo mostrar hora y tiempo de respuesta — SIN schema ni tabla
            let extra = `<span>${hora}</span>`;
            if (meta.tiempoMs)   extra += `<span style="color:var(--txt3)">⚡ ${meta.tiempoMs}ms</span>`;
            if (meta.totalFilas) extra += `<span style="color:var(--txt3)">${meta.totalFilas} resultados</span>`;
            metaDiv.innerHTML = extra;
        }
        cont.appendChild(metaDiv);

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

    // ─── TYPING ──────────────────────────────────────────────────────────
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

    // ─── PANEL ENTRENAMIENTO ──────────────────────────────────────────────
    function toggleTrain() {
        const p = document.getElementById('cb-train-panel');
        p?.classList.toggle('oculto');
        if (!p?.classList.contains('oculto')) {
            cargarRAG();
            // Asegurar que el tab "agregar" esté activo al abrir
            switchTab('agregar');
        }
    }

    function switchTab(tab) {
        document.querySelectorAll('.cb-tab').forEach(t => t.classList.remove('activo'));
        document.querySelector(`[data-tab="${tab}"]`)?.classList.add('activo');
        document.querySelectorAll('.cb-tab-content').forEach(c => c.style.display = 'none');
        const tabEl = document.getElementById(`cb-tab-${tab}`);
        if (tabEl) tabEl.style.display = 'block';
        if (tab === 'lista') cargarRAG();
    }

    async function submitRAG(e) {
        e.preventDefault();
        const alerta = document.getElementById('cb-rag-alert');
        alerta?.classList.remove('show','ok','error');

        const datos = {
            accion:   'agregar_rag',
            pregunta: v('rag-pregunta'),
            palabras: v('rag-palabras'),
            schema:   v('rag-schema'),
            sql:      v('rag-sql'),
            respBase: v('rag-respbase'),
            rol:      v('rag-rol'),
            canal:    v('rag-canal'),
        };

        if (!datos.pregunta || !datos.sql) {
            mostrarAlerta(alerta, '⚠️ La pregunta y el SQL son obligatorios.', 'error');
            return;
        }

        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled    = true;
        btn.innerHTML   = '<span class="cb-spinner"></span> Guardando…';

        try {
            const res = await post(datos);
            if (res.error) {
                mostrarAlerta(alerta, '❌ ' + (res.mensaje || 'Error al guardar.'), 'error');
            } else {
                // Alerta de éxito con animación
                mostrarAlertaExito(alerta, res.id);
                e.target.reset();
                // Cambiar automáticamente a la lista después de guardar
                setTimeout(() => switchTab('lista'), 1200);
            }
        } catch {
            mostrarAlerta(alerta, '❌ Error de conexión.', 'error');
        }

        btn.disabled  = false;
        btn.innerHTML = '💾 Guardar ejemplo';
    }

    function mostrarAlertaExito(el, id) {
        if (!el) return;
        el.className = 'cb-alert ok show';
        el.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M8 12l3 3 5-5"/>
                </svg>
                <div>
                    <div style="font-weight:700">¡Ejemplo guardado correctamente!</div>
                    <div style="font-size:.75rem;opacity:.85">ID: ${id} · El bot ya puede responder preguntas similares</div>
                </div>
            </div>`;
        setTimeout(() => el.classList.remove('show'), 5000);
    }

    async function cargarRAG() {
        const lista = document.getElementById('cb-rag-lista');
        if (!lista) return;
        lista.innerHTML = '<p style="color:var(--txt3);font-size:.82rem;padding:.5rem">Cargando ejemplos…</p>';
        try {
            const res = await post({ accion: 'listar_rag' });
            if (!res.datos?.length) {
                lista.innerHTML = '<div style="text-align:center;padding:1.5rem;color:var(--txt3)"><div style="font-size:2rem;margin-bottom:.5rem">🧠</div><div style="font-size:.84rem">Sin ejemplos. ¡Agrega el primero!</div></div>';
                return;
            }
            lista.innerHTML = '';
            // Cabecera con conteo
            const hdr = document.createElement('div');
            hdr.style.cssText = 'font-size:.75rem;color:var(--txt3);padding:.25rem .5rem .75rem;font-weight:600';
            hdr.textContent   = `${res.datos.length} ejemplo(s) en la base de conocimiento`;
            lista.appendChild(hdr);
            res.datos.forEach(ej => lista.appendChild(itemRAG(ej)));
        } catch {
            lista.innerHTML = '<p style="color:red;font-size:.82rem">Error al cargar.</p>';
        }
    }

    function itemRAG(ej) {
        const d = document.createElement('div');
        d.className = 'cb-rag-item';

        // Mapear schema a nombre amigable
        const modulos = {
            'soporte':      '🎫 Soporte',
            'inventario':   '💻 Inventario',
            'laboratorio':  '🧪 Laboratorio',
            'salas':        '🏢 Salas',
            'activos':      '📜 Certificados',
            'comun':        '👥 Usuarios',
        };
        const schema  = (ej.schemaObjetivo || '').split('.')[0].toLowerCase();
        const modNom  = modulos[schema] || `📊 ${ej.schemaObjetivo}`;
        const pctUtil = ej.vecesUsado > 0 ? Math.round((ej.vecesUtil / ej.vecesUsado) * 100) : 0;

        d.innerHTML = `
            <div class="cb-rag-item-header">
                <span class="cb-rag-pregunta">${esc(ej.preguntaEjemplo)}</span>
                <span class="cb-rag-badge ${ej.activo ? 'activo' : 'inactivo'}">${ej.activo ? 'Activo' : 'Inactivo'}</span>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin:.4rem 0">
                <span style="font-size:.7rem;padding:2px 8px;border-radius:20px;background:#f1f5f9;color:#475569;font-weight:600">${modNom}</span>
                <span style="font-size:.7rem;padding:2px 8px;border-radius:20px;background:#f1f5f9;color:#475569">${esc(ej.canal)}</span>
                ${ej.palabrasClave ? `<span style="font-size:.7rem;padding:2px 8px;border-radius:20px;background:#eff6ff;color:#3b82f6">${esc(ej.palabrasClave.split(',').slice(0,3).join(', '))}</span>` : ''}
            </div>
            <div style="display:flex;gap:12px;font-size:.71rem;color:var(--txt3);margin-top:.3rem">
                <span>Usado <strong>${ej.vecesUsado}x</strong></span>
                <span>Útil <strong>${ej.vecesUtil}x</strong></span>
                ${pctUtil > 0 ? `<span style="color:#009540;font-weight:700">${pctUtil}% positivo</span>` : ''}
                <span>${esc(ej.fechaCreacion)}</span>
            </div>
            <div class="cb-rag-actions">
                <button class="cb-btn-sm ${ej.activo ? 'danger' : ''}" onclick="ChaviBot.toggleRAG(${ej.idEjemplo},${ej.activo ? 0 : 1})">
                    ${ej.activo ? 'Desactivar' : 'Activar'}
                </button>
            </div>`;
        return d;
    }

    async function toggleRAG(id, activo) {
        await post({ accion: 'toggle_rag', id, activo });
        cargarRAG();
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────
    function formatear(txt) {
        return txt
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g,    '<em>$1</em>')
            .replace(/^- (.+)/gm,     '<div style="padding-left:.5rem">• $1</div>')
            .replace(/\n/g,           '<br>');
    }
    function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function scroll() { const el = $msgs(); if (el) el.scrollTop = el.scrollHeight; }
    function v(id)   { return document.getElementById(id)?.value.trim() || ''; }

    function mostrarAlerta(el, msg, tipo) {
        if (!el) return;
        el.textContent = msg;
        el.className   = `cb-alert ${tipo} show`;
        setTimeout(() => el.classList.remove('show'), 4000);
    }

    async function post(datos) {
        const body = new FormData();
        Object.entries(datos).forEach(([k, val]) => body.append(k, val ?? ''));
        const res = await fetch(AJAX_URL, { method: 'POST', body });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    return { init, toggleRAG };
})();

document.addEventListener('DOMContentLoaded', ChaviBot.init);
