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
        reset()     { if (opciones.length) seleccionar(opciones[0].value, opciones[0].label); },
        getExtra(v) { return opciones.find(o => String(o.value) === String(v)) ?? null; }
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
        hidden.value = lista.map(c => c.idCaracteristica).join(',');
    }
}

/* ─────────────────────────────────────────────────────────
   HELPER escapeHtml
───────────────────────────────────────────────────────── */
function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ═══════════════════════════════════════════════════════
   DOM READY
═══════════════════════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {

    /* ── Inicializar custom selects ── */
    const csNuevoActivo  = crearCustomSelect('nuevoIdActivo');
    const csNuevoTipo    = crearCustomSelect('nuevoTipoCaracteristica');
    const csNuevoValor   = crearCustomSelect('nuevoValorCaracteristica');
    const csEditarActivo = crearCustomSelect('editarIdActivo');
    const csEditarTipo   = crearCustomSelect('editarTipoCaracteristica');
    const csEditarValor  = crearCustomSelect('editarValorCaracteristica');

    /* ── Custom select para el modal Armar Equipo ── */
    const csComponente = crearCustomSelect('armarComponenteSelect');

    /* ════════════════════════════════════════════════
       MODAL AGREGAR EQUIPO
    ════════════════════════════════════════════════ */
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

    /* ════════════════════════════════════════════════
       BOTÓN EDITAR EQUIPO
    ════════════════════════════════════════════════ */
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

            // Reconstruir lista de características con IDs reales de la BD
            caracteristicasEditar.length = 0;
            if (Array.isArray(json.caracteristicasDetalle) && json.caracteristicasDetalle.length) {
                json.caracteristicasDetalle.forEach(c => {
                    caracteristicasEditar.push({
                        idCaracteristica: String(c.idCaracteristica),
                        tipo:  c.tipo,
                        valor: c.valor
                    });
                });
            }
            renderTabla('tablaEditarEquipoCaracteristicas', caracteristicasEditar, 'editarCaracteristicasIds');
            csEditarValor.setOptions([{ value: '', label: 'Seleccionar valor...' }]);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarEquipo')).show();
        } catch (err) {
            console.error(err);
            mostrarToast('error', 'Error al cargar datos del equipo.');
        }
    });

    /* ════════════════════════════════════════════════
       GUARDAR NUEVO EQUIPO
    ════════════════════════════════════════════════ */
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

    /* ════════════════════════════════════════════════
       ACTUALIZAR EQUIPO
    ════════════════════════════════════════════════ */
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

    /* ════════════════════════════════════════════════
       MODAL ARMAR EQUIPO — abrir
    ════════════════════════════════════════════════ */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btnArmarEquipo');
        if (!btn) return;

        const idPadre = btn.getAttribute('data-id');
        const nombre  = btn.getAttribute('data-nombre');
        const icono   = btn.getAttribute('data-icono');

        // Rellenar header del modal
        document.getElementById('armarIdEquipoPadre').value       = idPadre;
        document.getElementById('armarNombrePadre').textContent    = nombre;
        document.getElementById('armarIconoPadre').className       = `ti ${icono} fs-2`;

        // Resetear sección agregar
        document.getElementById('armarComponenteInfo').style.display = 'none';
        document.getElementById('btnAgregarComponente').disabled      = true;

        // Abrir modal primero para que el DOM esté visible
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalArmarEquipo')).show();

        // Cargar en paralelo: componentes actuales + equipos disponibles
        await Promise.all([
            cargarComponentesActuales(idPadre),
            cargarEquiposDisponibles(idPadre),
        ]);
    });

    /* ════════════════════════════════════════════════
       ARMAR EQUIPO — cargar componentes actuales
    ════════════════════════════════════════════════ */
    async function cargarComponentesActuales(idPadre) {
        const lista = document.getElementById('armarListaComponentes');
        lista.innerHTML = `<div class="text-muted small text-center py-3">
            <span class="spinner-border spinner-border-sm me-1"></span>Cargando...</div>`;

        try {
            const fd = new FormData();
            fd.append('idEquipoPadre', idPadre);
            const res  = await fetch('modules/inventario/ajax/equipos.ajax.php', { method: 'POST', body: fd });
            const data = await res.json();
            renderComponentes(data, idPadre);
        } catch {
            lista.innerHTML = '<div class="text-danger small text-center py-3">Error al cargar componentes.</div>';
        }
    }

    function renderComponentes(componentes, idPadre) {
        const lista    = document.getElementById('armarListaComponentes');
        const contador = document.getElementById('armarContador');

        if (!componentes || componentes.length === 0) {
            contador.textContent = '0';
            lista.innerHTML = `
                <div class="componentes-vacio">
                    <i class="ti ti-inbox fs-2 d-block mb-1"></i>
                    Sin componentes asignados
                </div>`;
            return;
        }

        contador.textContent = componentes.length;
        lista.innerHTML = componentes.map(c => {
            const icono = c.iconoActivo ?? 'ti-package';
            const serie = c.numeroSerie
                ? `<span class="text-muted small">S/N: ${escHtml(c.numeroSerie)}</span>` : '';
            const caract = c.caracteristicas
                ? `<div class="text-muted small text-truncate">${escHtml(c.caracteristicas)}</div>` : '';
            return `
            <div class="componente-card" data-id="${c.idEquipo}">
                <div class="componente-icon">
                    <i class="ti ${escHtml(icono)}"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-semibold small text-truncate">${escHtml(c.nombreActivo ?? 'Componente')}</div>
                    ${serie}
                    ${caract}
                    ${c.codigoPatrimonial
                        ? `<span class="badge badge-outline text-muted small">${escHtml(c.codigoPatrimonial)}</span>`
                        : ''}
                </div>
                <button type="button"
                    class="btn btn-sm btn-icon btn-outline-danger btnQuitarComponente flex-shrink-0"
                    data-id-hijo="${c.idEquipo}"
                    data-id-padre="${idPadre}"
                    title="Quitar del equipo">
                    <i class="ti ti-unlink"></i>
                </button>
            </div>`;
        }).join('');
    }

    /* ════════════════════════════════════════════════
       ARMAR EQUIPO — cargar equipos disponibles
    ════════════════════════════════════════════════ */
    async function cargarEquiposDisponibles(idPadre) {
        try {
            const res  = await fetch(
                `modules/inventario/ajax/equipos.ajax.php?disponibles=1&idPadre=${idPadre}`
            );
            const data = await res.json();

            const ops   = [{ value: '', label: 'Seleccionar componente...' }];
            const extra = {};
            data.forEach(eq => {
                ops.push({ value: String(eq.idEquipo), label: eq.label });
                extra[String(eq.idEquipo)] = eq;
            });
            csComponente.setOptions(ops);
            csComponente._extra = extra;
        } catch (e) { console.error('[cargarEquiposDisponibles]', e); }
    }

    /* ── Cambio en select componente → mostrar info ── */
    document.getElementById('armarComponenteSelect')
        ?.addEventListener('change', function () {
            const val        = this.value;
            const infoBox    = document.getElementById('armarComponenteInfo');
            const btnAgregar = document.getElementById('btnAgregarComponente');

            if (!val) {
                infoBox.style.display = 'none';
                btnAgregar.disabled   = true;
                return;
            }
            const extra = csComponente._extra?.[val];
            if (extra) {
                document.getElementById('armarInfoSerie').textContent  = extra.numeroSerie       || '—';
                document.getElementById('armarInfoCodigo').textContent = extra.codigoPatrimonial || '—';
                document.getElementById('armarInfoCaract').textContent = extra.caracteristicas   || '—';
                infoBox.style.display = 'block';
            }
            btnAgregar.disabled = false;
        });

    /* ════════════════════════════════════════════════
       ARMAR EQUIPO — agregar componente
    ════════════════════════════════════════════════ */
    document.getElementById('btnAgregarComponente')
        ?.addEventListener('click', async function () {
            const idPadre = document.getElementById('armarIdEquipoPadre').value;
            const idHijo  = csComponente.getValue();
            if (!idHijo) { mostrarToast('warning', 'Seleccione un componente primero.'); return; }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Agregando...';
            try {
                const fd = new FormData();
                fd.append('accion',        'agregarComponente');
                fd.append('idEquipoPadre', idPadre);
                fd.append('idEquipoHijo',  idHijo);
                const res  = await fetch('modules/inventario/ajax/equipos.ajax.php', { method: 'POST', body: fd });
                const data = await res.json();
                manejarRespuestaSP(data, null, async () => {
                    csComponente.reset();
                    document.getElementById('armarComponenteInfo').style.display = 'none';
                    document.getElementById('btnAgregarComponente').disabled      = true;
                    await Promise.all([
                        cargarComponentesActuales(idPadre),
                        cargarEquiposDisponibles(idPadre),
                    ]);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally {
                this.disabled = false;
                this.innerHTML = '<i class="ti ti-plus me-1"></i>Agregar al equipo';
            }
        });

    /* ════════════════════════════════════════════════
       ARMAR EQUIPO — quitar componente (delegado)
    ════════════════════════════════════════════════ */
    document.getElementById('armarListaComponentes')
        ?.addEventListener('click', async function (e) {
            const btn = e.target.closest('.btnQuitarComponente');
            if (!btn) return;

            const idHijo  = btn.getAttribute('data-id-hijo');
            const idPadre = btn.getAttribute('data-id-padre');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            try {
                const fd = new FormData();
                fd.append('accion',       'quitarComponente');
                fd.append('idEquipoHijo', idHijo);
                const res  = await fetch('modules/inventario/ajax/equipos.ajax.php', { method: 'POST', body: fd });
                const data = await res.json();
                manejarRespuestaSP(data, null, async () => {
                    await Promise.all([
                        cargarComponentesActuales(idPadre),
                        cargarEquiposDisponibles(idPadre),
                    ]);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-unlink"></i>';
            }
        });

    /* ════════════════════════════════════════════════
       DATATABLES
    ════════════════════════════════════════════════ */
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

    /* ── ELIMINAR EQUIPO ── */
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btnEliminarEquipo');
        if (!btn) return;

        const id      = btn.getAttribute('data-id');
        const nombre  = btn.getAttribute('data-nombre') || 'este equipo';
        const esPadre = btn.getAttribute('data-es-padre') === '1';

        // Si es equipo padre (compuesto=1) avisar que primero debe quitar componentes
        if (esPadre) {
            mostrarToast('warning', 'Para eliminar un equipo compuesto, primero quita todos sus componentes desde "Armar Equipo".');
            return;
        }

        document.getElementById('eliminarNombreEquipo').textContent = nombre;
        document.getElementById('confirmarEliminarEquipo').setAttribute('data-id', id);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarEliminarEquipo')).show();
    });

    const btnConfirmarEq = document.getElementById('confirmarEliminarEquipo');
    if (btnConfirmarEq) {
        btnConfirmarEq.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const fd = new FormData();
            fd.append('eliminarIdEquipo', id);

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';

            fetch('modules/inventario/ajax/equipos.ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(json => {
                    bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminarEquipo')).hide();
                    if (json.resultado === 'ok') {
                        mostrarToast('success', json.mensaje || 'Equipo eliminado correctamente.');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarToast('error', json.mensaje || 'No se pudo eliminar.');
                    }
                })
                .catch(() => mostrarToast('error', 'Error al comunicarse con el servidor.'))
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-trash me-1"></i>Sí, eliminar';
                });
        });
    }

}); // fin DOMContentLoaded