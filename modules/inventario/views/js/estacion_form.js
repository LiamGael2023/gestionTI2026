/* =============================================================
   ESTACION_FORM.JS
   Lógica compartida para agregar y editar estación.
   Ruta ajax: modules/inventario/ajax/estaciones.ajax.php
============================================================= */

const AJAX_EST = 'modules/inventario/ajax/estaciones.ajax.php';
const URL_LISTA = '?module=inventario&action=estaciones';

/* ── Toast ── */
function mostrarToast(tipo, mensaje) {
    const colores = { success:'bg-success', error:'bg-danger', warning:'bg-warning' };
    const c = document.getElementById('toastContainerEstaciones');
    if (!c) return;
    c.insertAdjacentHTML('beforeend', `
    <div class="toast align-items-center text-white ${colores[tipo]??'bg-secondary'} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`);
    const el = c.lastElementChild;
    const t  = new bootstrap.Toast(el, { delay: 4000 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Custom Select ── */
function crearCustomSelect(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return null;
    sel.style.display = 'none';
    const wrapId = 'cswrap_' + selectId;
    const viejo  = document.getElementById(wrapId);
    if (viejo) viejo.remove();
    const wrap = document.createElement('div');
    wrap.className = 'cs-wrap'; wrap.id = wrapId;
    wrap.innerHTML = `
        <div class="cs-display" tabindex="0">
            <span class="cs-text placeholder-text">Seleccionar...</span>
            <svg class="cs-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="cs-panel">
            <div class="cs-search-row">
                <i class="ti ti-search" style="color:#94a3b8;font-size:.9rem;flex-shrink:0"></i>
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
        document.querySelectorAll('.cs-wrap.cs-open').forEach(w => { if(w!==wrap) w.classList.remove('cs-open'); });
        wrap.classList.add('cs-open'); searchIn.value = ''; renderLista(opciones);
        // Posicionar el panel con fixed para evitar recorte por overflow:hidden en flex containers
        requestAnimationFrame(() => {
            const rect    = wrap.getBoundingClientRect();
            const panelH  = 260; // max-height estimada del panel
            const spaceB  = window.innerHeight - rect.bottom;
            const spaceT  = rect.top;
            panel.style.position = 'fixed';
            panel.style.left     = rect.left + 'px';
            panel.style.width    = rect.width + 'px';
            panel.style.zIndex   = '9999';
            if (spaceB < panelH && spaceT > panelH) {
                // Abrir hacia arriba
                panel.style.top    = 'auto';
                panel.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                // Abrir hacia abajo
                panel.style.top    = (rect.bottom + 4) + 'px';
                panel.style.bottom = 'auto';
            }
            searchIn.focus();
        });
    }
    function cerrar() {
        wrap.classList.remove('cs-open');
        // Resetear posicionamiento fixed al cerrar
        panel.style.position = '';
        panel.style.left = '';
        panel.style.width = '';
        panel.style.top = '';
        panel.style.bottom = '';
        panel.style.zIndex = '';
    }
    display.addEventListener('mousedown', e => { e.preventDefault(); wrap.classList.contains('cs-open') ? cerrar() : abrir(); });
    display.addEventListener('keydown', e => { if(e.key==='Enter'||e.key===' '){e.preventDefault();abrir();} if(e.key==='Escape')cerrar(); });
    document.addEventListener('mousedown', e => { if(!wrap.contains(e.target)) cerrar(); });
    searchIn.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        renderLista(q ? opciones.filter(o => o.label.toLowerCase().includes(q)) : opciones);
    });
    searchIn.addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();e.stopPropagation();} });

    function renderLista(items) {
        list.innerHTML = '';
        if (!items.length) { list.innerHTML='<li class="cs-empty">Sin resultados</li>'; return; }
        const val = sel.value;
        items.forEach(o => {
            const li = document.createElement('li');
            li.textContent = o.label; li.dataset.value = o.value;
            if (!o.value)        li.classList.add('cs-placeholder-item');
            if (o.value === val) li.classList.add('cs-selected');
            li.addEventListener('mousedown', e => { e.preventDefault(); seleccionar(o.value, o.label); cerrar(); });
            list.appendChild(li);
        });
    }
    function seleccionar(value, label) {
        sel.value = value;
        sel.dispatchEvent(new Event('change', { bubbles:true }));
        const t = display.querySelector('.cs-text');
        t.textContent = label || 'Seleccionar...';
        t.classList.toggle('placeholder-text', !value);
    }
    const obj = {
        _data: {},
        setOptions(arr) {
            opciones = arr; sel.innerHTML = '';
            arr.forEach(o => { const opt=document.createElement('option'); opt.value=o.value; opt.textContent=o.label; sel.appendChild(opt); });
            const t = display.querySelector('.cs-text');
            t.textContent = arr[0]?.label ?? 'Seleccionar...'; t.classList.add('placeholder-text');
            sel.value = arr[0]?.value ?? '';
        },
        setValue(v) { const f=opciones.find(o=>String(o.value)===String(v)); if(f) seleccionar(f.value,f.label); },
        getValue()  { return sel.value; },
        reset()     { if(opciones.length) seleccionar(opciones[0].value, opciones[0].label); }
    };
    return obj;
}

/* ── Toggle password ── */
function initTogglePass() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btnTogglePass');
        if (!btn) return;
        const input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        const icon = btn.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
    });
}

/* ── Render item en lista ── */
function renderItem(eq, iconClass, onQuitar) {
    const d = document.createElement('div');
    d.className = 'item-card';
    d.innerHTML = `
        <div class="item-icon ${iconClass}"><i class="ti ${escHtml(eq.iconoActivo??'ti-package')}"></i></div>
        <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold small text-truncate">${escHtml(eq.nombreActivo??eq.label??'')}</div>
            ${eq.codigoPatrimonial ? `<span class="text-muted small">${escHtml(eq.codigoPatrimonial)}</span>` : ''}
            ${eq.numeroSerie ? `<span class="badge badge-outline text-muted small ms-1">${escHtml(eq.numeroSerie)}</span>` : ''}
        </div>
        <button type="button" class="btn btn-sm btn-icon btn-outline-danger flex-shrink-0 btnQuitarItem" title="Quitar">
            <i class="ti ti-x"></i>
        </button>`;
    d.querySelector('.btnQuitarItem').addEventListener('click', onQuitar);
    return d;
}

/* ── Actualizar lista visual con callback post-quitar ── */
function actualizarLista(listaId, contadorId, arr, iconClass, onAfterQuitar) {
    const lista    = document.getElementById(listaId);
    const contador = document.getElementById(contadorId);
    if (!lista) return;
    if (contador) contador.textContent = arr.length;
    lista.innerHTML = '';
    if (!arr.length) {
        lista.innerHTML = '<div class="items-vacio">Sin ítems</div>';
        return;
    }
    arr.forEach((eq, idx) => {
        const el = renderItem(eq, iconClass, () => {
            arr.splice(idx, 1);
            actualizarLista(listaId, contadorId, arr, iconClass, onAfterQuitar);
            if (onAfterQuitar) onAfterQuitar();
        });
        lista.appendChild(el);
    });
}

/* ── Cargar IPs disponibles ── */
// idEstacion=0 → solo disponibles (agregar)
// idEstacion>0 → disponibles + IP actual de la estación (editar)
async function cargarIps(cs, valorActual = '', idEstacion = 0) {
    try {
        const res  = await fetch(`${AJAX_EST}?listarIps=1&idEstacion=${idEstacion}`);
        const data = await res.json();
        const ops  = [{ value:'', label:'Sin IP asignada' }];
        data.forEach(ip => ops.push({ value:String(ip.idIp), label:ip.ipAddress }));
        cs.setOptions(ops);
        if (valorActual) cs.setValue(String(valorActual));
    } catch(e) { console.error('[cargarIps]', e); }
}

/* ── Cargar equipos por tipo ── */
async function cargarEquiposTipo(cs, tipo, idEstacion, excluirIds = []) {
    try {
        const excl = excluirIds.filter(Boolean).join(',');
        const res  = await fetch(`${AJAX_EST}?listarEquipos=1&tipo=${tipo}&idEstacion=${idEstacion}&excluir=${excl}`);
        const data = await res.json();
        const placeholder = tipo === 'software' ? 'Seleccionar software...' : 'Seleccionar...';
        const ops  = [{ value:'', label: placeholder }];
        if (!cs._data) cs._data = {};
        // Limpiar _data anterior
        Object.keys(cs._data).forEach(k => delete cs._data[k]);
        data.forEach(eq => {
            ops.push({ value:String(eq.idEquipo), label:eq.label });
            cs._data[String(eq.idEquipo)] = eq;
        });
        cs.setOptions(ops);
    } catch(e) { console.error('[cargarEquiposTipo]', e); }
}

/* ── IDs de todos los equipos seleccionados ── */
function idsExcluir(arrP, arrPer, arrSoft) {
    return [...arrP, ...arrPer, ...arrSoft].map(e => e.idEquipo).filter(Boolean);
}

/* ── Sincronizar hiddens ── */
function sincronizarHiddens(prefijo, principal, perifericos, software) {
    document.getElementById(prefijo + 'EquipoPrincipalId').value = principal[0]?.idEquipo ?? '';
    document.getElementById(prefijo + 'PerifericosIds').value    = perifericos.map(e=>e.idEquipo).join(',');
    document.getElementById(prefijo + 'SoftwareIds').value       = software.map(e=>e.idEquipo).join(',');
}
