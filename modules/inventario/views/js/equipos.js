/* =============================================================
   EQUIPOS.JS
   Rutas ajax según árbol REAL del proyecto:
     modules/inventario/ajax/activosTabla.ajax.php
     modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php
     modules/inventario/ajax/caracteristicasTabla.ajax.php
     modules/inventario/ajax/equipos.ajax.php
============================================================= */

const caracteristicasNuevo  = [];
const caracteristicasEditar = [];

/* ─────────────────────────────────────────────────────────
   TOAST
───────────────────────────────────────────────────────── */
function mostrarToast(tipo, mensaje) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = document.getElementById("toastContainerEquipos");
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

/* ─────────────────────────────────────────────────────────
   MANEJAR RESPUESTA DEL SP
   El SP devuelve: { resultado, mensaje }
   resultado puede ser: 'ok', 'error', 'error_duplicado_cp',
   'error_fecha', u otro string de error
───────────────────────────────────────────────────────── */
function manejarRespuestaSP(data, modalId, onSuccess) {
    if (!data || typeof data !== 'object') {
        mostrarToast('error', 'Respuesta inesperada del servidor.'); return;
    }

    const resultado = (data.resultado ?? '').toString().trim();
    const mensaje   = (data.mensaje   ?? '').toString().trim();

    switch (resultado) {
        case 'ok':
            mostrarToast('success', mensaje || 'Operación realizada correctamente.');
            if (onSuccess) onSuccess();
            break;

        case 'error_duplicado_cp':
            mostrarToast('warning', mensaje || 'El código patrimonial ya existe.');
            break;

        case 'error_fecha':
            mostrarToast('warning', mensaje || 'Error en las fechas ingresadas.');
            break;

        case 'error':
        default:
            mostrarToast('error', mensaje || 'Ocurrió un error. Intente nuevamente.');
            break;
    }
}

/* ─────────────────────────────────────────────────────────
   CUSTOM SELECT CON BÚSQUEDA
───────────────────────────────────────────────────────── */
function crearCustomSelect(selectId) {
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
            if (!o.value)              li.classList.add('cs-placeholder-item');
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

/* ─────────────────────────────────────────────────────────
   CARGA DE DATOS AJAX
───────────────────────────────────────────────────────── */
async function cargarActivos(cs) {
    try {
        const res  = await fetch('modules/inventario/ajax/activosTabla.ajax.php');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const ops  = [{ value: '', label: 'Seleccionar activo...' }];
        data.forEach(a => ops.push({ value: String(a.idActivos), label: String(a.descripcion) }));
        cs.setOptions(ops);
    } catch (e) { console.error('[cargarActivos]', e); }
}

async function cargarTipos(cs) {
    try {
        const res  = await fetch('modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const ops  = [{ value: '', label: 'Seleccionar tipo...' }];
        data.forEach(t => ops.push({ value: String(t.idTipoCaracteristica), label: String(t.descripcion) }));
        cs.setOptions(ops);
    } catch (e) { console.error('[cargarTipos]', e); }
}

async function cargarValores(cs, idTipo) {
    cs.setOptions([{ value: '', label: 'Seleccionar valor...' }]);
    if (!idTipo) return;
    try {
        const url  = 'modules/inventario/ajax/caracteristicasTabla.ajax.php?idTipoCaracteristica='
                     + encodeURIComponent(idTipo);
        const res  = await fetch(url);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const ops  = [{ value: '', label: 'Seleccionar valor...' }];
        data.forEach(v => ops.push({ value: String(v.idCaracteristica), label: String(v.valor) }));
        cs.setOptions(ops);
    } catch (e) { console.error('[cargarValores]', e); }
}

/* ─────────────────────────────────────────────────────────
   TABLA TEMPORAL DE CARACTERÍSTICAS
───────────────────────────────────────────────────────── */
function renderTabla(tablaId, lista, hiddenId) {
    const tbody = document.querySelector('#' + tablaId + ' tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (!lista.length) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3 small">
            <i class="ti ti-info-circle me-1"></i>Sin características agregadas</td></tr>`;
    } else {
        lista.forEach((c, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="small fw-semibold">${c.tipo}</td>
                <td class="small">${c.valor}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-icon btn-outline-danger btnEliminarCaract"
                    data-idx="${idx}" data-tabla="${tablaId}" data-hidden="${hiddenId}">
                    <i class="ti ti-trash"></i>
                  </button>
                </td>`;
            tbody.appendChild(tr);
        });
    }
    const hidden = document.getElementById(hiddenId);
    if (hidden) {
        // Todos los IDs son reales (no más prefijo 'existing_')
        hidden.value = lista.map(c => c.idCaracteristica).join(',');
    }
}

/* ─────────────────────────────────────────────────────────
   DOM READY
───────────────────────────────────────────────────────── */
document.addEventListener("DOMContentLoaded", function () {

    /* Inicializar custom selects */
    const csNuevoActivo  = crearCustomSelect('nuevoIdActivo');
    const csNuevoTipo    = crearCustomSelect('nuevoTipoCaracteristica');
    const csNuevoValor   = crearCustomSelect('nuevoValorCaracteristica');
    const csEditarActivo = crearCustomSelect('editarIdActivo');
    const csEditarTipo   = crearCustomSelect('editarTipoCaracteristica');
    const csEditarValor  = crearCustomSelect('editarValorCaracteristica');

    /* ── Abrir modal AGREGAR ── */
    document.getElementById('modalAgregarEquipo')
        ?.addEventListener('show.bs.modal', async () => {
            document.getElementById('formNuevoEquipo')?.reset();
            caracteristicasNuevo.length = 0;
            renderTabla('tablaNuevoEquipoCaracteristicas', caracteristicasNuevo, 'nuevoCaracteristicasIds');
            await cargarActivos(csNuevoActivo);
            await cargarTipos(csNuevoTipo);
            csNuevoValor.setOptions([{ value: '', label: 'Seleccionar valor...' }]);
        });

    /* ── Cambio tipo → valores AGREGAR ── */
    document.getElementById('nuevoTipoCaracteristica')
        ?.addEventListener('change', function () {
            cargarValores(csNuevoValor, this.value);
        });

    /* ── Cambio tipo → valores EDITAR ── */
    document.getElementById('editarTipoCaracteristica')
        ?.addEventListener('change', function () {
            cargarValores(csEditarValor, this.value);
        });

    /* ── Agregar característica AGREGAR ── */
    document.getElementById('btnAgregarNuevaCaracteristica')
        ?.addEventListener('click', () => {
            const idTipo  = csNuevoTipo.getValue();
            const idValor = csNuevoValor.getValue();
            if (!idTipo || !idValor) { mostrarToast('warning', 'Selecciona un tipo y un valor.'); return; }
            if (caracteristicasNuevo.some(c => c.idCaracteristica === idValor)) {
                mostrarToast('warning', 'Esta característica ya fue agregada.'); return;
            }
            const selTipo  = document.getElementById('nuevoTipoCaracteristica');
            const selValor = document.getElementById('nuevoValorCaracteristica');
            caracteristicasNuevo.push({
                idCaracteristica: idValor,
                tipo:  selTipo.options[selTipo.selectedIndex]?.text  ?? idTipo,
                valor: selValor.options[selValor.selectedIndex]?.text ?? idValor
            });
            renderTabla('tablaNuevoEquipoCaracteristicas', caracteristicasNuevo, 'nuevoCaracteristicasIds');
        });

    /* ── Agregar característica EDITAR ── */
    document.getElementById('btnAgregarEditarCaracteristica')
        ?.addEventListener('click', () => {
            const idTipo  = csEditarTipo.getValue();
            const idValor = csEditarValor.getValue();
            if (!idTipo || !idValor) { mostrarToast('warning', 'Selecciona un tipo y un valor.'); return; }
            if (caracteristicasEditar.some(c => c.idCaracteristica === idValor)) {
                mostrarToast('warning', 'Esta característica ya fue agregada.'); return;
            }
            const selTipo  = document.getElementById('editarTipoCaracteristica');
            const selValor = document.getElementById('editarValorCaracteristica');
            caracteristicasEditar.push({
                idCaracteristica: idValor,
                tipo:  selTipo.options[selTipo.selectedIndex]?.text  ?? idTipo,
                valor: selValor.options[selValor.selectedIndex]?.text ?? idValor
            });
            renderTabla('tablaEditarEquipoCaracteristicas', caracteristicasEditar, 'editarCaracteristicasIds');
        });

    /* ── Eliminar característica ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btnEliminarCaract');
        if (!btn) return;
        const lista = btn.dataset.tabla === 'tablaNuevoEquipoCaracteristicas'
            ? caracteristicasNuevo : caracteristicasEditar;
        lista.splice(parseInt(btn.dataset.idx), 1);
        renderTabla(btn.dataset.tabla, lista, btn.dataset.hidden);
    });

    /* ── Botón editar equipo en tabla ── */
    document.addEventListener('click', async function (e) {
        const boton = e.target.closest('.btnEditarEquipo');
        if (!boton) return;
        const fd = new FormData();
        fd.append('idEquipo', boton.getAttribute('data-id'));
        try {
            const res  = await fetch('modules/inventario/ajax/equipos.ajax.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.error) { mostrarToast('error', json.error); return; }

            await cargarActivos(csEditarActivo);
            await cargarTipos(csEditarTipo);
            csEditarActivo.setValue(String(json.idActivo));

            document.getElementById('editarIdEquipo').value             = json.idEquipo;
            document.getElementById('editarCodigoPatrimonial').value    = json.codigoPatrimonial   ?? '';
            document.getElementById('editarNumeroSerie').value          = json.numeroSerie         ?? '';
            document.getElementById('editarFechaAdquisicion').value     = json.fechaAdquisicion    ?? '';
            document.getElementById('editarFechaInicioGarantia').value  = json.fechaInicioGarantia ?? '';
            document.getElementById('editarFechaFinGarantia').value     = json.fechaFinGarantia    ?? '';
            document.getElementById('editarUsuarioCreacion').textContent     = json.idUsuarioRegistro ?? '--';
            document.getElementById('editarFechaCreacion').textContent       = json.fechaCreacion     ?? '--';
            document.getElementById('editarUsuarioModificacion').textContent = json.idUsuarioModifica ?? '--';
            document.getElementById('editarFechaModificacion').textContent   = json.fechaModificacion ?? '--';

            // Reconstruir lista con IDs REALES de la BD
            caracteristicasEditar.length = 0;
            if (Array.isArray(json.caracteristicasDetalle) && json.caracteristicasDetalle.length) {
                json.caracteristicasDetalle.forEach(c => {
                    caracteristicasEditar.push({
                        idCaracteristica: String(c.idCaracteristica),  // ID real
                        tipo:  c.tipo,
                        valor: c.valor
                    });
                });
            }
            renderTabla('tablaEditarEquipoCaracteristicas', caracteristicasEditar, 'editarCaracteristicasIds');
            // El hidden ya queda lleno con los IDs reales gracias a renderTabla
            csEditarValor.setOptions([{ value: '', label: 'Seleccionar valor...' }]);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarEquipo')).show();
        } catch (err) {
            console.error(err);
            mostrarToast('error', 'Error al cargar datos del equipo.');
        }
    });

    /* ── Guardar nuevo equipo ── */
    document.getElementById('formNuevoEquipo')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btnSubmit = this.querySelector('[type=submit]');
            btnSubmit.disabled = true;
            try {
                const resp = await fetch('modules/inventario/ajax/equipos.ajax.php',
                    { method: 'POST', body: new FormData(this) });
                const data = await resp.json();
                manejarRespuestaSP(data, 'modalAgregarEquipo', () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarEquipo')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally  { btnSubmit.disabled = false; }
        });

    /* ── Actualizar equipo ── */
    document.getElementById('formEditarEquipo')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btnSubmit = this.querySelector('[type=submit]');
            btnSubmit.disabled = true;
            try {
                const resp = await fetch('modules/inventario/ajax/equipos.ajax.php',
                    { method: 'POST', body: new FormData(this) });
                const data = await resp.json();
                manejarRespuestaSP(data, 'modalEditarEquipo', () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarEquipo')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error al comunicarse con el servidor.'); }
            finally  { btnSubmit.disabled = false; }
        });

    /* ── DataTable ── */
    if ($.fn.DataTable.isDataTable('#tablaEquipos')) $('#tablaEquipos').DataTable().destroy();
    $('#tablaEquipos').DataTable({
        responsive: true, pageLength: 10, autoWidth: false,
        dom: `<'card-body border-bottom py-3'<'row g-3 align-items-center'
              <'col-12 col-md-auto'l>
              <'col-12 col-md-auto ms-auto'<'d-flex gap-2'Bf>>
              >>tr<'card-footer d-flex align-items-center py-2'
              <'m-0 text-muted small'i><'pagination m-0 ms-auto'p>>`,
        buttons: [
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-outline-success btn-sm m-0' },
            { extend: 'pdfHtml5',   text: '<i class="ti ti-file-description"></i>',  className: 'btn btn-outline-danger btn-sm m-0' }
        ],
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm m-0').attr('placeholder', 'Buscar equipo...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dt-buttons').addClass('d-flex gap-2 m-0');
        }
    });

}); // fin DOMContentLoaded