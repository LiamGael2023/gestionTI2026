/* =============================================================
   ESTACIONES.JS
   Ruta ajax: modules/inventario/ajax/estaciones.ajax.php
   Secciones: Equipo Principal (1 max) | Periféricos | Software
============================================================= */

const AJAX_EST = 'modules/inventario/ajax/estaciones.ajax.php';

/* ── Toast ── */
function mostrarToast(tipo, mensaje) {
    const colores = { success:'bg-success', error:'bg-danger', warning:'bg-warning', info:'bg-info' };
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
    const t  = new bootstrap.Toast(el, { delay:4000 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
}

function manejarRespuesta(data, onSuccess) {
    if (!data || typeof data !== 'object') { mostrarToast('error','Respuesta inesperada.'); return; }
    const r = (data.resultado ?? '').toString().trim();
    const m = (data.mensaje   ?? '').toString().trim();
    if      (r === 'ok')              { mostrarToast('success', m || 'Operación realizada.'); if (onSuccess) onSuccess(); }
    else if (r === 'error_duplicado') { mostrarToast('warning', m || 'Registro duplicado.'); }
    else                              { mostrarToast('error',   m || 'Ocurrió un error.'); }
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
        document.querySelectorAll('.cs-wrap.cs-open').forEach(w => { if(w!==wrap) w.classList.remove('cs-open'); });
        wrap.classList.add('cs-open'); searchIn.value = ''; renderLista(opciones);
        requestAnimationFrame(() => {
            const rect = wrap.getBoundingClientRect();
            if ((window.innerHeight - rect.bottom) < 230 && rect.top > 230) {
                panel.style.top='auto'; panel.style.bottom='100%'; panel.style.marginTop='0'; panel.style.marginBottom='3px';
            } else {
                panel.style.top='100%'; panel.style.bottom='auto'; panel.style.marginTop='3px'; panel.style.marginBottom='0';
            }
            searchIn.focus();
        });
    }
    function cerrar() { wrap.classList.remove('cs-open'); }
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
    return {
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
}

/* ── Render item en lista ── */
function renderItem(eq, iconClass, colorClass, onQuitar) {
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

/* ── Actualizar lista visual ── */
function actualizarLista(listaId, contadorId, arr, iconClass, colorClass, onAfterQuitar) {
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
        const el = renderItem(eq, iconClass, colorClass, () => {
            arr.splice(idx, 1);
            actualizarLista(listaId, contadorId, arr, iconClass, colorClass, onAfterQuitar);
            if (onAfterQuitar) onAfterQuitar();
        });
        lista.appendChild(el);
    });
}

/* ── Cargar IPs disponibles ── */
async function cargarIps(cs, valorActual = '') {
    try {
        const res  = await fetch(AJAX_EST + '?listarIps=1');
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
        const url  = `${AJAX_EST}?listarEquipos=1&tipo=${tipo}&idEstacion=${idEstacion}&excluir=${excl}`;
        const res  = await fetch(url);
        const data = await res.json();
        const ops  = [{ value:'', label: tipo === 'software' ? 'Seleccionar software...' : 'Seleccionar...' }];
        data.forEach(eq => ops.push({ value:String(eq.idEquipo), label:eq.label }));
        cs.setOptions(ops);
        // Guardar datos extra para mostrar en la lista
        cs._data = {};
        data.forEach(eq => { cs._data[String(eq.idEquipo)] = eq; });
    } catch(e) { console.error('[cargarEquiposTipo]', e); }
}

/* ════════════════════════════════════════
   DOM READY
════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {

    /* Toggle pass */
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btnTogglePass');
        if (!btn) return;
        const input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        const icon = btn.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'ti ti-eye' : 'ti ti-eye-off';
    });

    /* ── Custom selects ── */
    const csNuevoIp          = crearCustomSelect('nuevoIdIp');
    const csNuevoPrincipal   = crearCustomSelect('nuevoEquipoPrincipalSelect');
    const csNuevoPeriferico  = crearCustomSelect('nuevoPerifericoSelect');
    const csNuevoSoftware    = crearCustomSelect('nuevoSoftwareSelect');
    const csEditarIp         = crearCustomSelect('editarIdIp');
    const csEditarPrincipal  = crearCustomSelect('editarEquipoPrincipalSelect');
    const csEditarPeriferico = crearCustomSelect('editarPerifericoSelect');
    const csEditarSoftware   = crearCustomSelect('editarSoftwareSelect');

    /* ── Estado local para modal NUEVO ── */
    let nPrincipal  = [];   // max 1
    let nPerifericos = [];
    let nSoftware   = [];

    /* ── Estado local para modal EDITAR ── */
    let ePrincipal  = [];
    let ePerifericos = [];
    let eSoftware   = [];

    function idsExcluir(arrP, arrPer, arrSoft) {
        return [...arrP, ...arrPer, ...arrSoft].map(e => e.idEquipo).filter(Boolean);
    }

    /* ════ MODAL NUEVO — abrir ════ */
    document.getElementById('modalAgregarEstacion')
        ?.addEventListener('show.bs.modal', async () => {
            document.getElementById('formNuevaEstacion')?.reset();
            nPrincipal = []; nPerifericos = []; nSoftware = [];
            renderNuevo();
            document.getElementById('btnAgregarNuevoPrincipal').disabled  = true;
            document.getElementById('btnAgregarNuevoPeriferico').disabled = true;
            document.getElementById('btnAgregarNuevoSoftware').disabled   = true;
            await Promise.all([
                cargarIps(csNuevoIp),
                cargarEquiposTipo(csNuevoPrincipal, 'principal', 0, []),
                cargarEquiposTipo(csNuevoPeriferico, 'periferico', 0, []),
                cargarEquiposTipo(csNuevoSoftware, 'software', 0, []),
            ]);
        });

    function sincronizarHiddensNuevo() {
        document.getElementById('nuevoEquipoPrincipalId').value = nPrincipal[0]?.idEquipo ?? '';
        document.getElementById('nuevoPerifericosIds').value    = nPerifericos.map(e=>e.idEquipo).join(',');
        document.getElementById('nuevoSoftwareIds').value       = nSoftware.map(e=>e.idEquipo).join(',');
    }

    function renderNuevo() {
        actualizarLista('nuevoEquipoPrincipalLista', null, nPrincipal, 'icon-equipo','', () => {
            sincronizarHiddensNuevo();
            const wP = document.getElementById('cswrap_nuevoEquipoPrincipalSelect');
            if (wP) wP.style.opacity = nPrincipal.length ? '.5' : '1';
            document.getElementById('btnAgregarNuevoPrincipal').disabled = nPrincipal.length > 0;
            recargarCombosNuevo();
        });
        actualizarLista('nuevoPerifericosLista', 'nuevoPerifericosContador', nPerifericos, 'icon-periferico','', () => {
            sincronizarHiddensNuevo();
            recargarCombosNuevo();
        });
        actualizarLista('nuevoSoftwareLista', 'nuevoSoftwareContador', nSoftware, 'icon-software','', () => {
            sincronizarHiddensNuevo();
            recargarCombosNuevo();
        });
        sincronizarHiddensNuevo();
        const wrapP = document.getElementById('cswrap_nuevoEquipoPrincipalSelect');
        if (wrapP) wrapP.style.opacity = nPrincipal.length ? '.5' : '1';
        if (nPrincipal.length) {
            document.getElementById('btnAgregarNuevoPrincipal').disabled = true;
            csNuevoPrincipal.reset();
        }
    }

    /* Cambios en selects NUEVO */
    document.getElementById('nuevoEquipoPrincipalSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarNuevoPrincipal').disabled = !this.value || nPrincipal.length > 0;
    });
    document.getElementById('nuevoPerifericoSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarNuevoPeriferico').disabled = !this.value;
    });
    document.getElementById('nuevoSoftwareSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarNuevoSoftware').disabled = !this.value;
    });

    /* Agregar principal NUEVO */
    document.getElementById('btnAgregarNuevoPrincipal')?.addEventListener('click', () => {
        const val = csNuevoPrincipal.getValue(); if (!val || nPrincipal.length) return;
        const eq  = csNuevoPrincipal._data?.[val];
        if (!eq) return;
        nPrincipal = [{ idEquipo:val, ...eq }];
        renderNuevo();
        recargarCombosNuevo();
    });

    /* Agregar periférico NUEVO */
    document.getElementById('btnAgregarNuevoPeriferico')?.addEventListener('click', () => {
        const val = csNuevoPeriferico.getValue(); if (!val) return;
        if (nPerifericos.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq  = csNuevoPeriferico._data?.[val];
        if (!eq) return;
        nPerifericos.push({ idEquipo:val, ...eq });
        csNuevoPeriferico.reset();
        document.getElementById('btnAgregarNuevoPeriferico').disabled = true;
        renderNuevo();
        recargarCombosNuevo();
    });

    /* Agregar software NUEVO */
    document.getElementById('btnAgregarNuevoSoftware')?.addEventListener('click', () => {
        const val = csNuevoSoftware.getValue(); if (!val) return;
        if (nSoftware.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq  = csNuevoSoftware._data?.[val];
        if (!eq) return;
        nSoftware.push({ idEquipo:val, ...eq });
        csNuevoSoftware.reset();
        document.getElementById('btnAgregarNuevoSoftware').disabled = true;
        renderNuevo();
        recargarCombosNuevo();
    });

    async function recargarCombosNuevo() {
        const excl = idsExcluir(nPrincipal, nPerifericos, nSoftware);
        await Promise.all([
            cargarEquiposTipo(csNuevoPrincipal,  'principal',  0, excl),
            cargarEquiposTipo(csNuevoPeriferico, 'periferico', 0, excl),
            cargarEquiposTipo(csNuevoSoftware,   'software',   0, excl),
        ]);
    }

    /* ════ GUARDAR NUEVA ════ */
    document.getElementById('formNuevaEstacion')
        ?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            try {
                const resp = await fetch(AJAX_EST, { method:'POST', body:new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarEstacion')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error','Error de servidor.'); }
            finally { btn.disabled = false; }
        });

    /* ════ BOTÓN EDITAR ════ */
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btnEditarEstacion');
        if (!btn) return;
        const idEst = btn.getAttribute('data-id');
        const fd = new FormData(); fd.append('idEstacion', idEst);
        try {
            const res  = await fetch(AJAX_EST, { method:'POST', body:fd });
            const json = await res.json();
            if (json.error) { mostrarToast('error', json.error); return; }

            document.getElementById('editarIdEstacion').value           = json.idEstacion;
            document.getElementById('editarNombreEstacion').value       = json.nombreEstacion    ?? '';
            document.getElementById('editarCodigoAnydesk').value        = json.codigoAnydesk     ?? '';
            document.getElementById('editarContrasenaAnydesk').value    = json.contrasenaAnydesk ?? '';
            document.getElementById('editarEstUsuarioCreacion').textContent     = json.idUsuarioRegistro ?? '--';
            document.getElementById('editarEstFechaCreacion').textContent       = json.fechaCreacion     ?? '--';
            document.getElementById('editarEstUsuarioModificacion').textContent = json.idUsuarioModifica ?? '--';
            document.getElementById('editarEstFechaModificacion').textContent   = json.fechaModificacion ?? '--';

            // Reconstruir listas
            ePrincipal   = (json.principal  ?? []).map(e=>({...e, idEquipo:String(e.idEquipo)}));
            ePerifericos = (json.perifericos ?? []).map(e=>({...e, idEquipo:String(e.idEquipo)}));
            eSoftware    = (json.software    ?? []).map(e=>({...e, idEquipo:String(e.idEquipo)}));

            renderEditar();

            const excl = idsExcluir(ePrincipal, ePerifericos, eSoftware);
            await Promise.all([
                cargarIps(csEditarIp, json.idIp ?? ''),
                cargarEquiposTipo(csEditarPrincipal,  'principal',  idEst, excl),
                cargarEquiposTipo(csEditarPeriferico, 'periferico', idEst, excl),
                cargarEquiposTipo(csEditarSoftware,   'software',   idEst, excl),
            ]);

            document.getElementById('btnAgregarEditarPrincipal').disabled  = ePrincipal.length > 0;
            document.getElementById('btnAgregarEditarPeriferico').disabled = true;
            document.getElementById('btnAgregarEditarSoftware').disabled   = true;

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarEstacion')).show();
        } catch(err) { console.error(err); mostrarToast('error','Error al cargar la estación.'); }
    });

    function sincronizarHiddensEditar() {
        document.getElementById('editarEquipoPrincipalId').value = ePrincipal[0]?.idEquipo ?? '';
        document.getElementById('editarPerifericosIds').value    = ePerifericos.map(e=>e.idEquipo).join(',');
        document.getElementById('editarSoftwareIds').value       = eSoftware.map(e=>e.idEquipo).join(',');
    }

    function renderEditar() {
        actualizarLista('editarEquipoPrincipalLista', null, ePrincipal, 'icon-equipo','', () => {
            sincronizarHiddensEditar();
            const wP = document.getElementById('cswrap_editarEquipoPrincipalSelect');
            if (wP) wP.style.opacity = ePrincipal.length ? '.5' : '1';
            document.getElementById('btnAgregarEditarPrincipal').disabled = ePrincipal.length > 0;
            recargarCombosEditar();
        });
        actualizarLista('editarPerifericosLista', 'editarPerifericosContador', ePerifericos, 'icon-periferico','', () => {
            sincronizarHiddensEditar();
            recargarCombosEditar();
        });
        actualizarLista('editarSoftwareLista', 'editarSoftwareContador', eSoftware, 'icon-software','', () => {
            sincronizarHiddensEditar();
            recargarCombosEditar();
        });
        sincronizarHiddensEditar();
        const wrapP = document.getElementById('cswrap_editarEquipoPrincipalSelect');
        if (wrapP) wrapP.style.opacity = ePrincipal.length ? '.5' : '1';
        if (ePrincipal.length) document.getElementById('btnAgregarEditarPrincipal').disabled = true;
    }

    /* Cambios en selects EDITAR */
    document.getElementById('editarEquipoPrincipalSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarEditarPrincipal').disabled = !this.value || ePrincipal.length > 0;
    });
    document.getElementById('editarPerifericoSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarEditarPeriferico').disabled = !this.value;
    });
    document.getElementById('editarSoftwareSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarEditarSoftware').disabled = !this.value;
    });

    /* Agregar principal EDITAR */
    document.getElementById('btnAgregarEditarPrincipal')?.addEventListener('click', () => {
        const val = csEditarPrincipal.getValue(); if (!val || ePrincipal.length) return;
        const eq  = csEditarPrincipal._data?.[val]; if (!eq) return;
        ePrincipal = [{ idEquipo:val, ...eq }];
        renderEditar();
        recargarCombosEditar();
    });

    /* Agregar periférico EDITAR */
    document.getElementById('btnAgregarEditarPeriferico')?.addEventListener('click', () => {
        const val = csEditarPeriferico.getValue(); if (!val) return;
        if (ePerifericos.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq  = csEditarPeriferico._data?.[val]; if (!eq) return;
        ePerifericos.push({ idEquipo:val, ...eq });
        csEditarPeriferico.reset();
        document.getElementById('btnAgregarEditarPeriferico').disabled = true;
        renderEditar();
        recargarCombosEditar();
    });

    /* Agregar software EDITAR */
    document.getElementById('btnAgregarEditarSoftware')?.addEventListener('click', () => {
        const val = csEditarSoftware.getValue(); if (!val) return;
        if (eSoftware.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq  = csEditarSoftware._data?.[val]; if (!eq) return;
        eSoftware.push({ idEquipo:val, ...eq });
        csEditarSoftware.reset();
        document.getElementById('btnAgregarEditarSoftware').disabled = true;
        renderEditar();
        recargarCombosEditar();
    });

    async function recargarCombosEditar() {
        const idEst = document.getElementById('editarIdEstacion').value;
        const excl  = idsExcluir(ePrincipal, ePerifericos, eSoftware);
        await Promise.all([
            cargarEquiposTipo(csEditarPrincipal,  'principal',  idEst, excl),
            cargarEquiposTipo(csEditarPeriferico, 'periferico', idEst, excl),
            cargarEquiposTipo(csEditarSoftware,   'software',   idEst, excl),
        ]);
    }

    /* ════ GUARDAR EDICIÓN ════ */
    document.getElementById('formEditarEstacion')
        ?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            // Sincronizar hiddens antes de enviar (por si acaso)
            sincronizarHiddensEditar();
            try {
                const resp = await fetch(AJAX_EST, { method:'POST', body:new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarEstacion')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error','Error de servidor.'); }
            finally { btn.disabled = false; }
        });

    /* ════ BOTÓN VER DETALLE ════ */
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btnVerDetalle');
        if (!btn) return;
        const idEst  = btn.getAttribute('data-id');
        const nombre = btn.getAttribute('data-nombre');
        document.getElementById('detalleNombreEstacion').textContent = nombre;
        document.getElementById('detalleContenido').innerHTML =
            '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</div>';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVerDetalle')).show();

        try {
            const fd = new FormData(); fd.append('verDetalle', idEst);
            const res  = await fetch(AJAX_EST, { method:'POST', body:fd });
            const data = await res.json();
            renderDetalle(data);
        } catch { document.getElementById('detalleContenido').innerHTML = '<div class="text-danger text-center py-3">Error al cargar detalle.</div>'; }
    });

    function renderDetalle(data) {
        const cont = document.getElementById('detalleContenido');

        function seccion(titulo, icono, color, items, iconoItem, colorItem) {
            const rows = items.length
                ? items.map(it => `
                    <div class="detalle-item">
                        <div class="detalle-item-icon" style="background:${colorItem}15;color:${colorItem}">
                            <i class="ti ${escHtml(it.iconoActivo??'ti-package')}"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold small text-truncate">${escHtml(it.nombreActivo??'')}</div>
                            <div class="d-flex gap-2 flex-wrap">
                                ${it.codigoPatrimonial ? `<span class="text-muted small">CP: ${escHtml(it.codigoPatrimonial)}</span>` : ''}
                                ${it.numeroSerie ? `<span class="text-muted small">S/N: ${escHtml(it.numeroSerie)}</span>` : ''}
                            </div>
                        </div>
                        ${it.estado ? `<span class="badge" style="background:${colorItem}15;color:${colorItem}">${escHtml(it.estado)}</span>` : ''}
                    </div>`).join('')
                : '<div class="detalle-vacio">Sin ítems</div>';

            return `
            <div class="detalle-seccion">
                <div class="detalle-seccion-header" style="color:${color}">
                    <i class="ti ${icono}"></i>${titulo}
                    <span class="badge ms-auto" style="background:${color}15;color:${color}">${items.length}</span>
                </div>
                ${rows}
            </div>`;
        }

        // Equipo principal + sus componentes internos
        let principalHtml = '';
        if (data.principal && data.principal.length) {
            const p = data.principal[0];
            principalHtml = `
            <div class="detalle-seccion">
                <div class="detalle-seccion-header" style="color:#0054a6">
                    <i class="ti ti-cpu"></i>EQUIPO PRINCIPAL
                </div>
                <div class="detalle-item">
                    <div class="detalle-item-icon" style="background:#e7f0ff;color:#0054a6">
                        <i class="ti ${escHtml(p.iconoActivo??'ti-package')}"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold small text-truncate">${escHtml(p.nombreActivo??'')}</div>
                        <div class="d-flex gap-2 flex-wrap">
                            ${p.codigoPatrimonial ? `<span class="text-muted small">CP: ${escHtml(p.codigoPatrimonial)}</span>` : ''}
                            ${p.numeroSerie ? `<span class="text-muted small">S/N: ${escHtml(p.numeroSerie)}</span>` : ''}
                        </div>
                    </div>
                    ${p.estado ? `<span class="badge bg-primary-lt text-primary">${escHtml(p.estado)}</span>` : ''}
                </div>
                ${(data.componentesPrincipal??[]).length ? `
                <div style="padding:.3rem .9rem .3rem 2.5rem;background:#f8fafc;border-top:1px solid #f0f3f8">
                    <div class="text-muted small fw-semibold mb-1"><i class="ti ti-git-branch me-1"></i>Componentes internos</div>
                    ${(data.componentesPrincipal??[]).map(c => `
                    <div class="detalle-item" style="padding-left:.5rem">
                        <div class="detalle-item-icon" style="background:#fff3e0;color:#e65100">
                            <i class="ti ${escHtml(c.iconoActivo??'ti-package')}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">${escHtml(c.nombreActivo??'')}</div>
                            <div class="d-flex gap-2">
                                ${c.codigoPatrimonial ? `<span class="text-muted small">CP: ${escHtml(c.codigoPatrimonial)}</span>` : ''}
                                ${c.numeroSerie ? `<span class="text-muted small">S/N: ${escHtml(c.numeroSerie)}</span>` : ''}
                            </div>
                        </div>
                        ${c.estado ? `<span class="badge" style="background:#fff3e015;color:#e65100">${escHtml(c.estado)}</span>` : ''}
                    </div>`).join('')}
                </div>` : ''}
            </div>`;
        } else {
            principalHtml = `
            <div class="detalle-seccion">
                <div class="detalle-seccion-header" style="color:#0054a6"><i class="ti ti-cpu"></i>EQUIPO PRINCIPAL</div>
                <div class="detalle-vacio">Sin equipo principal asignado</div>
            </div>`;
        }

        cont.innerHTML =
            principalHtml +
            seccion('PERIFÉRICOS', 'ti-devices', '#2e7d32', data.perifericos??[], 'ti-device-desktop', '#2e7d32') +
            seccion('SOFTWARE', 'ti-brand-windows', '#6a1b9a', data.software??[], 'ti-code', '#6a1b9a');
    }

    /* ── DataTables ── */
    if ($.fn.DataTable.isDataTable('#tablaEstaciones')) $('#tablaEstaciones').DataTable().destroy();
    $('#tablaEstaciones').DataTable({
        responsive:true, pageLength:10, autoWidth:false,
        dom:`<'card-body border-bottom py-3'<'row g-3 align-items-center'<'col-12 col-md-auto'l><'col-12 col-md-auto ms-auto'<'d-flex gap-2'Bf>>>>tr<'card-footer d-flex align-items-center py-2'<'m-0 text-muted small'i><'pagination m-0 ms-auto'p>>`,
        buttons:[
            { extend:'excelHtml5', text:'<i class="ti ti-file-spreadsheet"></i>', className:'btn btn-outline-success btn-sm m-0' },
            { extend:'pdfHtml5',   text:'<i class="ti ti-file-description"></i>',  className:'btn btn-outline-danger btn-sm m-0'  }
        ],
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm m-0').attr('placeholder','Buscar estación...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dt-buttons').addClass('d-flex gap-2 m-0');
        }
    });

}); // fin DOMContentLoaded
