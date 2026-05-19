/* =============================================================
   UBICACIONES.JS  — Ubicación (con padre) + Ambiente
   Ruta ajax: modules/inventario/ajax/ubicaciones.ajax.php
============================================================= */

function mostrarToast(tipo, mensaje) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = document.getElementById("toastContainer");
    if (!container) return;
    const html = `
    <div class="toast align-items-center text-white ${colores[tipo] ?? 'bg-secondary'} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;
    container.insertAdjacentHTML("beforeend", html);
    const toastEl = container.lastElementChild;
    const t = new bootstrap.Toast(toastEl, { delay: 4000 });
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    t.show();
}

function manejarRespuesta(data, onSuccess) {
    if (!data || typeof data !== 'object') { mostrarToast('error', 'Respuesta inesperada.'); return; }
    const resultado = (data.resultado ?? '').toString().trim();
    const mensaje   = (data.mensaje   ?? '').toString().trim();
    switch (resultado) {
        case 'ok':
            mostrarToast('success', mensaje || 'Operación realizada correctamente.');
            if (onSuccess) onSuccess();
            break;
        case 'error_duplicado':
            mostrarToast('warning', mensaje || 'Ya existe un registro con ese nombre.');
            break;
        default:
            mostrarToast('error', mensaje || 'Ocurrió un error. Intente nuevamente.');
    }
}

/* ─── CUSTOM SELECT (mismo patrón de equipos.js) ─── */
function crearCustomSelect(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return null;
    sel.style.display = 'none';

    const wrapId = 'cswrap_' + selectId;
    const viejo = document.getElementById(wrapId);
    if (viejo) viejo.remove();

    const wrap = document.createElement('div');
    wrap.className = 'cs-wrap';
    wrap.id = wrapId;
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
        document.querySelectorAll('.cs-wrap.cs-open').forEach(w => { if (w !== wrap) w.classList.remove('cs-open'); });
        wrap.classList.add('cs-open');
        searchIn.value = '';
        renderLista(opciones);
        requestAnimationFrame(() => {
            const rect = wrap.getBoundingClientRect();
            if ((window.innerHeight - rect.bottom) < 230 && rect.top > 230) {
                panel.style.top = 'auto'; panel.style.bottom = '100%';
                panel.style.marginTop = '0'; panel.style.marginBottom = '3px';
            } else {
                panel.style.top = '100%'; panel.style.bottom = 'auto';
                panel.style.marginTop = '3px'; panel.style.marginBottom = '0';
            }
            searchIn.focus();
        });
    }
    function cerrar() { wrap.classList.remove('cs-open'); }

    display.addEventListener('mousedown', e => { e.preventDefault(); wrap.classList.contains('cs-open') ? cerrar() : abrir(); });
    display.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrir(); }
        if (e.key === 'Escape') cerrar();
    });
    document.addEventListener('mousedown', e => { if (!wrap.contains(e.target)) cerrar(); });
    searchIn.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        renderLista(q ? opciones.filter(o => o.label.toLowerCase().includes(q)) : opciones);
    });
    searchIn.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); } });

    function renderLista(items) {
        list.innerHTML = '';
        if (!items.length) { list.innerHTML = '<li class="cs-empty">Sin resultados</li>'; return; }
        const valActual = sel.value;
        items.forEach(o => {
            const li = document.createElement('li');
            li.textContent = o.label; li.dataset.value = o.value;
            if (!o.value)              li.classList.add('cs-placeholder-item');
            if (o.value === valActual) li.classList.add('cs-selected');
            li.addEventListener('mousedown', e => { e.preventDefault(); seleccionar(o.value, o.label); cerrar(); });
            list.appendChild(li);
        });
    }

    function seleccionar(value, label) {
        sel.value = value;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        const textEl = display.querySelector('.cs-text');
        textEl.textContent = label || 'Seleccionar...';
        textEl.classList.toggle('placeholder-text', !value);
    }

    return {
        setOptions(arr) {
            opciones = arr;
            sel.innerHTML = '';
            arr.forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.value; opt.textContent = o.label;
                sel.appendChild(opt);
            });
            const textEl = display.querySelector('.cs-text');
            textEl.textContent = arr[0]?.label ?? 'Seleccionar...';
            textEl.classList.add('placeholder-text');
            sel.value = arr[0]?.value ?? '';
        },
        setValue(value) {
            const found = opciones.find(o => String(o.value) === String(value));
            if (found) seleccionar(found.value, found.label);
        },
        getValue()  { return sel.value; },
        reset()     { if (opciones.length) seleccionar(opciones[0].value, opciones[0].label); }
    };
}

/* ─── CARGA DE UBICACIONES ─── */
async function cargarUbicacionesCombo(cs, placeholder) {
    try {
        const res  = await fetch('modules/inventario/ajax/ubicaciones.ajax.php?listarUbicaciones=1');
        const data = await res.json();
        const ops  = [{ value: '', label: placeholder }];
        data.forEach(u => ops.push({ value: String(u.idUbicacion), label: u.descripcion }));
        cs.setOptions(ops);
    } catch (e) { console.error('[cargarUbicaciones]', e); }
}

/* ─── DOM READY ─── */
document.addEventListener("DOMContentLoaded", function () {

    const csNuevoPadre    = crearCustomSelect('nuevoIdUbicacionPadre');
    const csEditarPadre   = crearCustomSelect('editarIdUbicacionPadre');
    const csNuevoUbicAmb  = crearCustomSelect('nuevoIdUbicacionAmbiente');
    const csEditarUbicAmb = crearCustomSelect('editarIdUbicacionAmbiente');

    /* ── Abrir modal AGREGAR UBICACIÓN ── */
    document.getElementById('modalAgregarUbicacion')
        ?.addEventListener('show.bs.modal', async () => {
            document.getElementById('formNuevaUbicacion')?.reset();
            await cargarUbicacionesCombo(csNuevoPadre, 'Ninguna (raíz)');
        });

    /* ── Clic EDITAR UBICACIÓN ── */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btnEditarUbicacion');
        if (!btn) return;
        const fd = new FormData();
        fd.append('idUbicacion', btn.getAttribute('data-id'));
        try {
            const res  = await fetch('modules/inventario/ajax/ubicaciones.ajax.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.error) { mostrarToast('error', json.error); return; }

            await cargarUbicacionesCombo(csEditarPadre, 'Ninguna (raíz)');
            csEditarPadre.setValue(json.idUbicacionPadre ? String(json.idUbicacionPadre) : '');

            document.getElementById('editarIdUbicacion').value          = json.idUbicacion;
            document.getElementById('editarDescripcionUbicacion').value = json.descripcion;
            document.getElementById('editarUbicUsuarioCreacion').textContent = json.idUsuarioRegistro ?? '--';
            document.getElementById('editarUbicFechaCreacion').textContent   = json.fechaCreacion     ?? '--';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarUbicacion')).show();
        } catch { mostrarToast('error', 'Error al cargar la ubicación.'); }
    });

    /* ── Guardar nueva ubicación ── */
    document.getElementById('formNuevaUbicacion')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            try {
                const resp = await fetch('modules/inventario/ajax/ubicaciones.ajax.php', { method: 'POST', body: new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarUbicacion')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally { btn.disabled = false; }
        });

    /* ── Actualizar ubicación ── */
    document.getElementById('formEditarUbicacion')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            try {
                const resp = await fetch('modules/inventario/ajax/ubicaciones.ajax.php', { method: 'POST', body: new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarUbicacion')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally { btn.disabled = false; }
        });

    /* ── Abrir modal AGREGAR AMBIENTE ── */
    document.getElementById('modalAgregarAmbiente')
        ?.addEventListener('show.bs.modal', async () => {
            document.getElementById('formNuevoAmbiente')?.reset();
            await cargarUbicacionesCombo(csNuevoUbicAmb, 'Seleccionar ubicación...');
        });

    /* ── Clic EDITAR AMBIENTE ── */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btnEditarAmbiente');
        if (!btn) return;
        const fd = new FormData();
        fd.append('idAmbiente', btn.getAttribute('data-id'));
        try {
            const res  = await fetch('modules/inventario/ajax/ubicaciones.ajax.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.error) { mostrarToast('error', json.error); return; }

            await cargarUbicacionesCombo(csEditarUbicAmb, 'Seleccionar ubicación...');
            csEditarUbicAmb.setValue(String(json.idUbicacion));

            document.getElementById('editarIdAmbiente').value          = json.idAmbiente;
            document.getElementById('editarDescripcionAmbiente').value = json.descripcion;
            document.getElementById('editarAmbUsuarioCreacion').textContent = json.idUsuarioRegistro ?? '--';
            document.getElementById('editarAmbFechaCreacion').textContent   = json.fechaCreacion     ?? '--';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarAmbiente')).show();
        } catch { mostrarToast('error', 'Error al cargar el ambiente.'); }
    });

    /* ── Guardar nuevo ambiente ── */
    document.getElementById('formNuevoAmbiente')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            try {
                const resp = await fetch('modules/inventario/ajax/ubicaciones.ajax.php', { method: 'POST', body: new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarAmbiente')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally { btn.disabled = false; }
        });

    /* ── Actualizar ambiente ── */
    document.getElementById('formEditarAmbiente')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            try {
                const resp = await fetch('modules/inventario/ajax/ubicaciones.ajax.php', { method: 'POST', body: new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarAmbiente')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally { btn.disabled = false; }
        });

    /* ── DataTables ── */
    ['#tablaUbicaciones', '#tablaAmbientes'].forEach(id => {
        if ($.fn.DataTable.isDataTable(id)) $(id).DataTable().destroy();
        $(id).DataTable({
            responsive: true, pageLength: 10, autoWidth: false,
            dom: `<'card-body border-bottom py-3'<'row g-3 align-items-center'<'col-12 col-md-auto'l><'col-12 col-md-auto ms-auto'<'d-flex gap-2'Bf>>>>tr<'card-footer d-flex align-items-center py-2'<'m-0 text-muted small'i><'pagination m-0 ms-auto'p>>`,
            buttons: [
                { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-outline-success btn-sm m-0' },
                { extend: 'pdfHtml5',   text: '<i class="ti ti-file-description"></i>',  className: 'btn btn-outline-danger btn-sm m-0' }
            ],
            initComplete: function () {
                $('.dataTables_filter input').addClass('form-control form-control-sm m-0').attr('placeholder', 'Buscar...');
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dt-buttons').addClass('d-flex gap-2 m-0');
            }
        });
    });

}); // fin DOMContentLoaded