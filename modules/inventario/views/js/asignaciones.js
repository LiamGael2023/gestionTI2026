/* =============================================================
   ASIGNACIONES.JS
   Ruta ajax: modules/inventario/ajax/asignaciones.ajax.php
   API personal: https://www.chavimochic.gob.pe/api_incidencias/api_personal.php
============================================================= */

const AJAX_ASIG    = 'modules/inventario/ajax/asignaciones.ajax.php';
const API_PERSONAL = 'https://www.chavimochic.gob.pe/api_incidencias/api_personal.php';

/* ─────────────────────────────────────────────────────────
   TOAST
───────────────────────────────────────────────────────── */
function mostrarToastAsig(tipo, mensaje) {
    const colores = { success: 'bg-success', error: 'bg-danger', warning: 'bg-warning', info: 'bg-info' };
    const container = document.getElementById('toastContainerAsig');
    if (!container) return;
    const html = `
    <div class="toast align-items-center text-white ${colores[tipo] ?? 'bg-secondary'} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const toastEl = container.lastElementChild;
    const t = new bootstrap.Toast(toastEl, { delay: 4000 });
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    t.show();
}

/* ─────────────────────────────────────────────────────────
   MANEJAR RESPUESTA SP
───────────────────────────────────────────────────────── */
function manejarRespuestaAsig(data, onSuccess) {
    if (!data || typeof data !== 'object') {
        mostrarToastAsig('error', 'Respuesta inesperada del servidor.'); return;
    }
    const resultado = (data.resultado ?? '').toString().trim();
    const mensaje   = (data.mensaje   ?? '').toString().trim();
    switch (resultado) {
        case 'ok':
            mostrarToastAsig('success', mensaje || 'Operación realizada correctamente.');
            if (onSuccess) onSuccess();
            break;
        case 'error_duplicado':
            mostrarToastAsig('warning', mensaje || 'Ya existe una asignación activa.');
            break;
        case 'error':
        default:
            mostrarToastAsig('error', mensaje || 'Ocurrió un error. Intente nuevamente.');
            break;
    }
}

/* ─────────────────────────────────────────────────────────
   CUSTOM SELECT CON BÚSQUEDA — idéntico a equipos.js
───────────────────────────────────────────────────────── */
function crearCustomSelectAsig(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return null;

    sel.style.display = 'none';

    const wrapId = 'cswrap_' + selectId;
    let wrap = document.getElementById(wrapId);
    if (wrap) wrap.remove();

    wrap = document.createElement('div');
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
        document.querySelectorAll('.cs-wrap.cs-open').forEach(w => {
            if (w !== wrap) w.classList.remove('cs-open');
        });
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
    searchIn.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        renderLista(q ? opciones.filter(o => o.label.toLowerCase().includes(q)) : opciones);
    });
    searchIn.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); }
    });

    function renderLista(items) {
        list.innerHTML = '';
        if (!items.length) { list.innerHTML = '<li class="cs-empty">Sin resultados</li>'; return; }
        const valActual = sel.value;
        items.forEach(o => {
            const li = document.createElement('li');
            li.textContent   = o.label;
            li.dataset.value = o.value;
            if (!o.value) li.classList.add('cs-placeholder-item');
            if (o.value === valActual) li.classList.add('cs-selected');
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

    const obj = {
        _data: {},
        setOptions(arr) {
            opciones = arr;
            sel.innerHTML = '';
            arr.forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.value; opt.textContent = o.label;
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
        reset() {
            if (opciones.length) seleccionar(opciones[0].value, opciones[0].label);
        }
    };
    return obj;
}

/* ─────────────────────────────────────────────────────────
   BUSCAR TRABAJADOR POR DNI EN LA API
───────────────────────────────────────────────────────── */
async function buscarTrabajadorDni(dni) {
    try {
        const res  = await fetch(API_PERSONAL);
        const json = await res.json();
        if (!json.success || !json.data) return null;
        return json.data.find(t => String(t.Documento).trim() === String(dni).trim()) ?? null;
    } catch (e) {
        console.error('[buscarTrabajadorDni]', e);
        return null;
    }
}

/* ─────────────────────────────────────────────────────────
   CARGAR PREVIEW EQUIPOS DE LA ESTACIÓN
───────────────────────────────────────────────────────── */
async function cargarEquiposPreviewAsig(idEstacion) {
    const wrap = document.getElementById('equiposPreviewWrap');
    const body = document.getElementById('equiposPreviewBody');
    if (!wrap || !body) return;
    if (!idEstacion) { wrap.style.display = 'none'; return; }
    try {
        const res  = await fetch(`${AJAX_ASIG}?equiposEstacion=1&idEstacion=${idEstacion}`);
        const data = await res.json();
        if (!data.length) { wrap.style.display = 'none'; return; }
        const badgeClass = {
            'Equipo Principal': 'bg-primary-lt text-primary',
            'Periférico':       'bg-success-lt text-success',
            'Software':         'bg-purple-lt text-purple',
        };
        body.innerHTML = data.map(eq => `
            <tr>
                <td><span class="badge ${badgeClass[eq.tipoEquipo] ?? 'badge-outline'}">${eq.tipoEquipo}</span></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ${eq.iconoActivo} text-primary"></i>
                        <span class="fw-medium small">${eq.nombreActivo}</span>
                    </div>
                    ${eq.caracteristicas ? `<div class="text-muted" style="font-size:.72rem">${eq.caracteristicas}</div>` : ''}
                </td>
                <td class="small font-monospace text-primary">${eq.codigoPatrimonial || '—'}</td>
            </tr>`).join('');
        wrap.style.display = 'block';
    } catch (e) {
        wrap.style.display = 'none';
    }
}

/* ─────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────── */
function escHtmlAsig(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function checkBtnGuardar() {
    const tieneEst = !!document.getElementById('hdnIdEstacion').value;
    const tieneDni = !!document.getElementById('nuevoDniResponsable').value;
    document.getElementById('btnGuardarAsignacion').disabled = !(tieneEst && tieneDni);
}

function resetModalAsignar() {
    document.getElementById('formAsignar').reset();
    document.getElementById('nuevoFechaAsignacion').value       = new Date().toISOString().split('T')[0];
    document.getElementById('workerCard').style.display          = 'none';
    document.getElementById('dniError').style.display            = 'none';
    document.getElementById('equiposPreviewWrap').style.display  = 'none';
    document.getElementById('hdnIdEstacion').value               = '';
    document.getElementById('nuevoDniResponsable').value         = '';
    document.getElementById('nuevoTrabajadorResponsable').value  = '';
    document.getElementById('nuevoTrabajadorAsignado').value     = '';
    document.getElementById('inputDniResponsable').value         = '';
    document.getElementById('btnGuardarAsignacion').disabled     = true;
}

/* ═════════════════════════════════════════════════════════
   DOM READY
═════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {

    /* ── DataTables ── */
    if ($.fn.DataTable.isDataTable('#tablaAsignaciones')) {
        $('#tablaAsignaciones').DataTable().destroy();
    }
    $('#tablaAsignaciones').DataTable({
        responsive: true,
        pageLength: 10,
        autoWidth:  false,
        dom: `<'card-body border-bottom py-3'<'row g-3 align-items-center'<'col-12 col-md-auto'l><'col-12 col-md-auto ms-auto'<'d-flex gap-2'Bf>>>>tr<'card-footer d-flex align-items-center py-2'<'m-0 text-muted small'i><'pagination m-0 ms-auto'p>>`,
        buttons: [
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-outline-success btn-sm m-0' },
            { extend: 'pdfHtml5',   text: '<i class="ti ti-file-description"></i>',  className: 'btn btn-outline-danger btn-sm m-0'  }
        ],
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm m-0').attr('placeholder', 'Buscar asignación...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dt-buttons').addClass('d-flex gap-2 m-0');
        }
    });

    /* ── Custom selects ── */
    const csEstacion = crearCustomSelectAsig('nuevoIdEstacionSelect');
    const csAmbiente = crearCustomSelectAsig('nuevoIdAmbiente');

    /* ── Cambio en combo estación → cargar equipos ── */
    document.getElementById('nuevoIdEstacionSelect')?.addEventListener('change', function () {
        document.getElementById('hdnIdEstacion').value = this.value;
        cargarEquiposPreviewAsig(this.value);
        checkBtnGuardar();
    });

    /* ════════════════════════════════════════
       NUEVA ASIGNACIÓN — abrir modal
    ════════════════════════════════════════ */
    document.getElementById('btnNuevaAsignacion')
        ?.addEventListener('click', async function () {
            resetModalAsignar();
            document.getElementById('modalAsignarTitulo').textContent = 'Nueva Asignación';
            document.getElementById('wrapComboEstacion').style.display = 'block';
            document.getElementById('wrapFijaEstacion').style.display  = 'none';

            try {
                const [estaciones, ambientes] = await Promise.all([
                    fetch(AJAX_ASIG + '?listarEstaciones=1').then(r => r.json()),
                    fetch(AJAX_ASIG + '?listarAmbientes=1').then(r => r.json()),
                ]);

                const opsEst = [{ value: '', label: 'Seleccionar estación...' }];
                estaciones.forEach(e => {
                    opsEst.push({ value: String(e.idEstacion), label: e.label });
                    csEstacion._data[String(e.idEstacion)] = e;
                });
                csEstacion.setOptions(opsEst);

                const opsAmb = [{ value: '', label: 'Sin ambiente' }];
                ambientes.forEach(a => opsAmb.push({ value: String(a.idAmbiente), label: a.label || a.descripcion }));
                csAmbiente.setOptions(opsAmb);

            } catch (e) {
                mostrarToastAsig('error', 'Error al cargar datos.');
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAsignar')).show();
        });

    /* ════════════════════════════════════════
       REASIGNAR — abrir modal con estación fija
    ════════════════════════════════════════ */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btnReasignar');
        if (!btn) return;

        const idEst  = btn.getAttribute('data-id');
        const nombre = btn.getAttribute('data-nombre');

        resetModalAsignar();
        document.getElementById('modalAsignarTitulo').textContent    = `Reasignar: ${nombre}`;
        document.getElementById('wrapComboEstacion').style.display   = 'none';
        document.getElementById('wrapFijaEstacion').style.display    = 'block';
        document.getElementById('textoFijaEstacion').textContent     = nombre;
        document.getElementById('hdnIdEstacion').value               = idEst;

        cargarEquiposPreviewAsig(idEst);

        try {
            const ambientes = await fetch(AJAX_ASIG + '?listarAmbientes=1').then(r => r.json());
            const opsAmb = [{ value: '', label: 'Sin ambiente' }];
            ambientes.forEach(a => opsAmb.push({ value: String(a.idAmbiente), label: a.label || a.descripcion }));
            csAmbiente.setOptions(opsAmb);
        } catch (e) {
            mostrarToastAsig('error', 'Error al cargar ambientes.');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAsignar')).show();
    });

    /* ════════════════════════════════════════
       BUSCAR TRABAJADOR POR DNI
    ════════════════════════════════════════ */
    document.getElementById('btnBuscarResponsable')
        ?.addEventListener('click', async function () {
            const dni   = document.getElementById('inputDniResponsable').value.trim();
            const errEl = document.getElementById('dniError');

            if (!dni) {
                errEl.textContent = 'Ingrese un DNI.';
                errEl.style.display = 'block';
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:13px;height:13px"></span>Buscando...';

            const trabajador = await buscarTrabajadorDni(dni);

            this.disabled = false;
            this.innerHTML = '<i class="ti ti-search me-1"></i>Buscar';

            if (!trabajador) {
                errEl.textContent   = `No se encontró trabajador con DNI "${dni}".`;
                errEl.style.display = 'block';
                document.getElementById('workerCard').style.display = 'none';
                document.getElementById('nuevoDniResponsable').value        = '';
                document.getElementById('nuevoTrabajadorResponsable').value = '';
                document.getElementById('nuevoTrabajadorAsignado').value    = '';
                checkBtnGuardar();
                return;
            }

            errEl.style.display = 'none';

            const nombre   = (trabajador.Trab_Paterno + ' ' + trabajador.Trab_Materno + ', ' + trabajador.Nombres).trim();
            const initials = (trabajador.Trab_Paterno[0] || '') + (trabajador.Trab_Materno[0] || '');

            document.getElementById('workerInitials').textContent = initials.toUpperCase();
            document.getElementById('workerNombre').textContent   = nombre;
            document.getElementById('workerDni').textContent      = trabajador.Documento;
            document.getElementById('workerCargo').textContent    = trabajador.Carg_Descripcion || trabajador.Unidad_Laboral || '';
            document.getElementById('workerCard').style.display   = 'block';

            document.getElementById('nuevoDniResponsable').value           = trabajador.Documento;
            document.getElementById('nuevoTrabajadorResponsable').value    = nombre;
            document.getElementById('nuevoTrabajadorAsignado').value       = nombre;

            checkBtnGuardar();
        });

    /* Enter en DNI = buscar */
    document.getElementById('inputDniResponsable')
        ?.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('btnBuscarResponsable').click();
            }
        });

    /* ════════════════════════════════════════
       GUARDAR ASIGNACIÓN
    ════════════════════════════════════════ */
    document.getElementById('formAsignar')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (!document.getElementById('hdnIdEstacion').value) {
                mostrarToastAsig('warning', 'Seleccione una estación.'); return;
            }
            if (!document.getElementById('nuevoDniResponsable').value) {
                mostrarToastAsig('warning', 'Busque y seleccione un responsable.'); return;
            }

            const btn = document.getElementById('btnGuardarAsignacion');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px"></span>Guardando...';

            try {
                const resp = await fetch(AJAX_ASIG, { method: 'POST', body: new FormData(this) });
                const data = await resp.json();
                manejarRespuestaAsig(data, () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAsignar')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch {
                mostrarToastAsig('error', 'Error de servidor.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-user-check me-1"></i>Guardar Asignación';
            }
        });

    /* ════════════════════════════════════════
       VER HISTORIAL
    ════════════════════════════════════════ */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btnVerHistorial');
        if (!btn) return;

        const idEst  = btn.getAttribute('data-id');
        const nombre = btn.getAttribute('data-nombre');

        document.getElementById('historialNombreEst').textContent = nombre;
        document.getElementById('historialContenido').innerHTML =
            '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</div>';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistorial')).show();

        try {
            const res  = await fetch(`${AJAX_ASIG}?historial=1&idEstacion=${idEst}`);
            const data = await res.json();

            if (!data.length) {
                document.getElementById('historialContenido').innerHTML =
                    '<div class="text-muted text-center py-3 small">Sin historial registrado para esta estación.</div>';
                return;
            }

            document.getElementById('historialContenido').innerHTML = data.map(h => `
                <div class="hist-item">
                    <div class="hist-dot" style="background:${h.estado === 'activa' ? '#2fb344' : '#94a3b8'}"></div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">${escHtmlAsig(h.trabajadorResponsable)}</div>
                        ${h.trabajadorAsignado && h.trabajadorAsignado !== h.trabajadorResponsable
                            ? `<div class="small text-muted">Asignado: ${escHtmlAsig(h.trabajadorAsignado)}</div>` : ''}
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <span class="badge badge-outline text-muted">DNI: ${escHtmlAsig(h.dniTrabajadorResponsable)}</span>
                            ${h.nombreAmbiente ? `<span class="badge badge-outline text-muted">${escHtmlAsig(h.nombreAmbiente)}</span>` : ''}
                            <span class="badge badge-outline text-muted">Desde: ${escHtmlAsig(h.fechaAsignacion)}</span>
                            ${h.fechaLiberacion !== '—' ? `<span class="badge badge-outline text-muted">Hasta: ${escHtmlAsig(h.fechaLiberacion)}</span>` : ''}
                            ${h.motivoCambio ? `<span class="badge badge-outline text-muted">${escHtmlAsig(h.motivoCambio)}</span>` : ''}
                        </div>
                        <span class="badge mt-1 ${h.estado === 'activa' ? 'bg-success-lt text-success' : 'badge-outline text-muted'}">
                            ${h.estado === 'activa' ? 'Activa' : 'Liberada'}
                        </span>
                    </div>
                </div>`).join('');
        } catch {
            document.getElementById('historialContenido').innerHTML =
                '<div class="text-danger text-center py-3 small">Error al cargar el historial.</div>';
        }
    });

    /* ════════════════════════════════════════
       LIBERAR ASIGNACIÓN
    ════════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btnLiberar');
        if (!btn) return;
        document.getElementById('liberarIdAsignacion').value    = btn.getAttribute('data-idAsignacion');
        document.getElementById('liberarNombreEst').textContent = btn.getAttribute('data-nombre');
        document.getElementById('liberarFecha').value           = new Date().toISOString().split('T')[0];
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLiberar')).show();
    });

    document.getElementById('formLiberar')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnConfirmarLiberar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px"></span>Liberando...';
            try {
                const resp = await fetch(AJAX_ASIG, { method: 'POST', body: new FormData(this) });
                const data = await resp.json();
                manejarRespuestaAsig(data, () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalLiberar')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch {
                mostrarToastAsig('error', 'Error de servidor.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-x me-1"></i>Liberar';
            }
        });

}); // fin DOMContentLoaded
