/* =============================================================
   ESTACIONES_FORM.JS  — Utilidades compartidas
   Cargado por: estacion_agregar.php, estacion_editar.php
   NO contiene lógica de vista. Solo funciones reutilizables.
   Las constantes AJAX_EST y URL_LISTA las define cada vista PHP.
============================================================= */

/* ── Toast ── */
function mostrarToast(tipo, mensaje) {
    const colores = { success:'bg-success', error:'bg-danger', warning:'bg-warning', info:'bg-info' };
    const c = document.getElementById('toastContainerEstaciones');
    if (!c) return;
    c.insertAdjacentHTML('beforeend', `
    <div class="toast align-items-center text-white ${colores[tipo] ?? 'bg-secondary'} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`);
    const el = c.lastElementChild;
    new bootstrap.Toast(el, { delay: 4000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ── Escape HTML ── */
function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Toggle password (delegación de evento global) ── */
function initTogglePass() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btnTogglePass');
        if (!btn) return;
        const inp = document.getElementById(btn.getAttribute('data-target'));
        if (!inp) return;
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').className = inp.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
    });
}

/* ══════════════════════════════════════════════════════════════
   Custom Select
   Retorna: { setOptions(arr), setValue(v), getValue(), reset(), _data, _ipData }
══════════════════════════════════════════════════════════════ */
function crearCustomSelect(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return null;
    sel.style.display = 'none';

    const wrapId = 'cswrap_' + selectId;
    const viejo  = document.getElementById(wrapId);
    if (viejo) viejo.remove();

    const wrap = document.createElement('div');
    wrap.className = 'cs-wrap';
    wrap.id        = wrapId;
    wrap.innerHTML = `
        <div class="cs-display" tabindex="0">
            <span class="cs-text placeholder-text">Seleccionar...</span>
            <svg class="cs-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="cs-panel">
            <div class="cs-search-row">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <circle cx="5.5" cy="5.5" r="4" stroke="#9ca3af" stroke-width="1.4"/>
                    <path d="M9 9l2.5 2.5" stroke="#9ca3af" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                <input class="cs-search" type="text" placeholder="Buscar..." autocomplete="off">
            </div>
            <ul class="cs-list"></ul>
        </div>`;
    sel.parentNode.insertBefore(wrap, sel);

    const display  = wrap.querySelector('.cs-display');
    const panel    = wrap.querySelector('.cs-panel');
    const searchIn = wrap.querySelector('.cs-search');
    const list     = wrap.querySelector('.cs-list');
    let opciones   = [];

    function abrir() {
        document.querySelectorAll('.cs-wrap.cs-open').forEach(w => {
            if (w !== wrap) w.classList.remove('cs-open');
        });
        wrap.classList.add('cs-open');
        searchIn.value = '';
        renderLista(opciones);
        requestAnimationFrame(() => {
            const rect   = wrap.getBoundingClientRect();
            const panelH = 260;
            panel.style.position = 'fixed';
            panel.style.left     = rect.left + 'px';
            panel.style.width    = rect.width + 'px';
            panel.style.zIndex   = '9999';
            if ((window.innerHeight - rect.bottom) < panelH && rect.top > panelH) {
                panel.style.top    = 'auto';
                panel.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                panel.style.top    = (rect.bottom + 4) + 'px';
                panel.style.bottom = 'auto';
            }
            searchIn.focus();
        });
    }

    function cerrar() {
        wrap.classList.remove('cs-open');
        panel.style.cssText = '';
    }

    display.addEventListener('mousedown', e => {
        e.preventDefault();
        wrap.classList.contains('cs-open') ? cerrar() : abrir();
    });
    display.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrir(); }
        if (e.key === 'Escape') cerrar();
    });
    document.addEventListener('mousedown', e => {
        if (!wrap.contains(e.target)) cerrar();
    });
    searchIn.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        renderLista(q ? opciones.filter(o => o.label.toLowerCase().includes(q)) : opciones);
    });
    searchIn.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); }
    });

    function renderLista(items) {
        list.innerHTML = '';
        if (!items.length) {
            list.innerHTML = '<li class="cs-empty">Sin resultados</li>';
            return;
        }
        const val = sel.value;
        items.forEach(o => {
            const li = document.createElement('li');
            li.textContent   = o.label;
            li.dataset.value = o.value;
            if (!o.value)        li.classList.add('cs-placeholder-item');
            if (o.value === val) li.classList.add('cs-selected');
            li.addEventListener('mousedown', e => {
                e.preventDefault();
                seleccionar(o.value, o.label);
                cerrar();
            });
            list.appendChild(li);
        });
    }

    function seleccionar(value, label) {
        sel.value = value;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        const t = display.querySelector('.cs-text');
        t.textContent = label || 'Seleccionar...';
        t.classList.toggle('placeholder-text', !value);
    }

    return {
        _data:   {},  // { idActivo: {eq} }  — llenado por cargarEquiposTipo
        _ipData: {},  // { idIp:     {ip} }  — llenado por recargarComboIp
        setOptions(arr) {
            opciones = arr;
            sel.innerHTML = '';
            arr.forEach(o => {
                const opt = document.createElement('option');
                opt.value       = o.value;
                opt.textContent = o.label;
                sel.appendChild(opt);
            });
            const t = display.querySelector('.cs-text');
            t.textContent = arr[0]?.label ?? 'Seleccionar...';
            t.classList.add('placeholder-text');
            sel.value = arr[0]?.value ?? '';
        },
        setValue(v) {
            const f = opciones.find(o => String(o.value) === String(v));
            if (f) seleccionar(f.value, f.label);
        },
        getValue() { return sel.value; },
        reset()    { if (opciones.length) seleccionar(opciones[0].value, opciones[0].label); }
    };
}

/* ══════════════════════════════════════════════════════════════
   Cargar equipos por tipo desde el servidor
   El AJAX devuelve: { idActivo, label, numeroSerie,
                       codigoPatrimonial, nombreActivo, iconoActivo }
   Clave siempre: idActivo
══════════════════════════════════════════════════════════════ */
async function cargarEquiposTipo(cs, tipo, idEstacion, excluirIds = []) {
    if (!cs) return;
    try {
        const excl = excluirIds.filter(Boolean).join(',');
        const url  = `${AJAX_EST}?listarEquipos=1&tipo=${tipo}&idEstacion=${idEstacion}&excluir=${excl}`;
        const data = await (await fetch(url)).json();

        const placeholder = tipo === 'software' ? 'Seleccionar software...' : 'Seleccionar...';
        const ops  = [{ value: '', label: placeholder }];
        cs._data   = {};
        data.forEach(eq => {
            const id = String(eq.idActivo);
            ops.push({ value: id, label: eq.label });
            cs._data[id] = eq;
        });
        cs.setOptions(ops);
    } catch (e) {
        console.error('[cargarEquiposTipo]', e);
    }
}

/* ── Cargar IPs disponibles ── */
async function cargarIps(cs, idEstacion = 0, idsEnUso = []) {
    try {
        const url  = `${AJAX_EST}?listarIps=1&idEstacion=${idEstacion}`;
        const data = await (await fetch(url)).json();
        const ops  = [{ value: '', label: 'Seleccionar IP...' }];
        cs._ipData = {};
        data.forEach(ip => {
            cs._ipData[String(ip.idIp)] = ip;
            if (!idsEnUso.includes(ip.idIp))
                ops.push({ value: String(ip.idIp), label: ip.ipAddress });
        });
        cs.setOptions(ops);
    } catch (e) {
        console.error('[cargarIps]', e);
    }
}

/* ══════════════════════════════════════════════════════════════
   Render chips de IPs
══════════════════════════════════════════════════════════════ */
function renderIpChips(containerId, hiddenId, ips, onCambio) {
    const cont   = document.getElementById(containerId);
    const hidden = document.getElementById(hiddenId);
    if (!cont) return;
    cont.innerHTML = '';

    if (!ips.length) {
        cont.innerHTML = '<span class="ip-chips-empty">Sin IPs asignadas</span>';
    } else {
        ips.forEach((ip, idx) => {
            const chip = document.createElement('span');
            chip.className = 'ip-chip';
            chip.innerHTML = `<i class="ti ti-network" style="font-size:.7rem"></i>${escHtml(ip.ipAddress)}
                <button type="button" class="ip-chip-rm" title="Quitar"><i class="ti ti-x"></i></button>`;
            chip.querySelector('.ip-chip-rm').addEventListener('click', () => {
                ips.splice(idx, 1);
                renderIpChips(containerId, hiddenId, ips, onCambio);
                if (onCambio) onCambio();
            });
            cont.appendChild(chip);
        });
    }
    if (hidden) hidden.value = ips.map(ip => ip.idIp).join(',');
}

/* ══════════════════════════════════════════════════════════════
   Render ítem de equipo (usado en listas de principal/periferico/software)
══════════════════════════════════════════════════════════════ */
function renderItemEquipo(eq, colorClass, onQuitar) {
    const d = document.createElement('div');
    d.className = 'eq-item';
    d.innerHTML = `
        <div class="eq-item-ico"><i class="ti ${escHtml(eq.iconoActivo ?? 'ti-package')}"></i></div>
        <div class="eq-item-body">
            <div class="eq-item-name">${escHtml(eq.nombreActivo ?? eq.label ?? '')}</div>
            <div class="eq-item-meta">
                ${eq.codigoPatrimonial ? `<span class="eq-item-cp">${escHtml(eq.codigoPatrimonial)}</span>` : ''}
                ${eq.numeroSerie       ? `<span class="eq-item-sn">S/N: ${escHtml(eq.numeroSerie)}</span>`  : ''}
            </div>
        </div>
        <button type="button" class="btn-eq-rm" title="Quitar"><i class="ti ti-x"></i></button>`;
    d.querySelector('.btn-eq-rm').addEventListener('click', onQuitar);
    return d;
}

/* ── Renderizar lista de equipos ── */
function renderListaEquipos(listaId, contadorId, arr, colorClass, onAfterQuitar) {
    const lista = document.getElementById(listaId);
    const cont  = document.getElementById(contadorId);
    if (!lista) return;
    if (cont) cont.textContent = arr.length;
    lista.innerHTML = '';
    if (!arr.length) {
        lista.innerHTML = `<div class="eq-empty"><i class="ti ti-inbox-off" style="font-size:1rem"></i> Sin ítems asignados</div>`;
        return;
    }
    arr.forEach((eq, idx) => {
        lista.appendChild(renderItemEquipo(eq, colorClass, () => {
            arr.splice(idx, 1);
            renderListaEquipos(listaId, contadorId, arr, colorClass, onAfterQuitar);
            if (onAfterQuitar) onAfterQuitar();
        }));
    });
}

/* ── Sincronizar hiddens (usa idActivo) ── */
function sincronizarHiddens(prefijo, principal, perifericos, software) {
    const g = id => document.getElementById(id);
    const p = g(prefijo + 'EquipoPrincipalId');
    const r = g(prefijo + 'PerifericosIds');
    const s = g(prefijo + 'SoftwareIds');
    if (p) p.value = principal[0]?.idActivo ?? '';
    if (r) r.value = perifericos.map(e => e.idActivo).join(',');
    if (s) s.value = software.map(e => e.idActivo).join(',');
}

/* ── IDs a excluir (usa idActivo) ── */
function idsExcluir(arrP, arrPer, arrSoft) {
    return [...arrP, ...arrPer, ...arrSoft].map(e => e.idActivo).filter(Boolean);
}
