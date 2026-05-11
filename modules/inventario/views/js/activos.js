/* =============================================================
   ACTIVOS.JS — actualizado
   Rutas ajax según árbol REAL del proyecto:
     modules/inventario/ajax/tipoActivosTabla.ajax.php
     modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php
     modules/inventario/ajax/caracteristicasTabla.ajax.php
     modules/inventario/ajax/activos.ajax.php
============================================================= */

const caracteristicasNuevo = [];
const caracteristicasEditar = [];

/* ─────────────────────────────────────────────────────────
   TOAST
───────────────────────────────────────────────────────── */
function mostrarToast(tipo, mensaje) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = document.getElementById("toastContainerActivos");
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
    const mensaje = (data.mensaje ?? '').toString().trim();
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

    const display = wrap.querySelector('.cs-display');
    const panel = wrap.querySelector('.cs-panel');
    const searchIn = wrap.querySelector('.cs-search');
    const list = wrap.querySelector('.cs-list');
    let opciones = [];

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
            li.textContent = o.label;
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
        getValue() { return sel.value; },
        reset() { if (opciones.length) seleccionar(opciones[0].value, opciones[0].label); },
        getExtra(v) { return opciones.find(o => String(o.value) === String(v)) ?? null; }
    };
}

/* ─────────────────────────────────────────────────────────
   CARGA DE DATOS AJAX
───────────────────────────────────────────────────────── */
async function cargarActivos(cs) {
    try {
        const res = await fetch('modules/inventario/ajax/tipoActivosTabla.ajax.php');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const ops = [{ value: '', label: 'Seleccionar Tipo Activo...' }];
        data.forEach(a => ops.push({
            value: String(a.idTipoActivo),
            label: String(a.descripcion),
            esCompuesto: intVal(a.esCompuesto),
            esPeriferico: intVal(a.esPeriferico),
            esComponente: intVal(a.esComponente),
        }));
        cs.setOptions(ops);
        cs._tiposData = {};
        data.forEach(a => {
            cs._tiposData[String(a.idTipoActivo)] = {
                esCompuesto: intVal(a.esCompuesto),
                esPeriferico: intVal(a.esPeriferico),
                esComponente: intVal(a.esComponente),
            };
        });
    } catch (e) { console.error('[cargarActivos]', e); }
}

function intVal(v) { return parseInt(v) || 0; }

async function cargarTipos(cs) {
    try {
        const res = await fetch('modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const ops = [{ value: '', label: 'Seleccionar tipo...' }];
        data.forEach(t => ops.push({ value: String(t.idTipoCaracteristica), label: String(t.descripcion) }));
        cs.setOptions(ops);
    } catch (e) { console.error('[cargarTipos]', e); }
}

async function cargarValores(cs, idTipo) {
    cs.setOptions([{ value: '', label: 'Seleccionar valor...' }]);
    if (!idTipo) return;
    try {
        const url = 'modules/inventario/ajax/caracteristicasTabla.ajax.php?idTipoCaracteristica='
            + encodeURIComponent(idTipo);
        const res = await fetch(url);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const ops = [{ value: '', label: 'Seleccionar valor...' }];
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
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/* ─────────────────────────────────────────────────────────
   ADAPTAR MODAL SEGÚN TIPO DE ACTIVO
   ─────────────────────────────────────────────────────────
   Tipos y reglas:
     Compuesto  (esCompuesto=1)  → todo. Estado: disponible|asignado|inoperativo|reparacion|baja
     Periférico (esPeriferico=1) → todo. Estado: disponible|asignado|inoperativo|reparacion|baja
     Componente (esComponente=1) → sin codigoPatrimonial. Estado: disponible|asignado|inoperativo|reparacion|baja
     Software   (ningún flag)    → sin codigoPatrimonial, sin numeroSerie, con codigoLicencia.
                                    Fechas: "Inicio Licencia" y "Fin Licencia". Estado: disponible|asignado|expirado
     Otros      (ningún flag, detectado como "otros") → igual que Compuesto.
───────────────────────────────────────────────────────── */
/* ─────────────────────────────────────────────────────────
   ADAPTAR MODAL SEGÚN TIPO DE ACTIVO (ACTUALIZADO)
───────────────────────────────────────────────────────── */
function adaptarModal(flags, prefijo) {
    const esCompuesto = intVal(flags?.esCompuesto);
    const esPeriferico = intVal(flags?.esPeriferico);
    const esComponente = intVal(flags?.esComponente);
    const esSoftware = flags?._esSoftware ?? false;

    const p = prefijo;
    const sufijo = (p === 'editar') ? '-editar' : '';

    /* ── Hint descriptivo del tipo seleccionado (Mejora visual) ── */
    const hintBox = document.getElementById(`${p}TipoActivoHint`);
    if (hintBox) {
        let tipoDesc = '', icon = '', color = '', bg = '';
        if (esCompuesto) { tipoDesc = '<b>Equipo Compuesto:</b> Requiere código patrimonial y permite asignarle componentes internos.'; icon = 'ti-cpu'; color = 'text-azure'; bg = 'bg-azure-lt'; }
        else if (esPeriferico) { tipoDesc = '<b>Periférico:</b> Requiere código patrimonial. Es un equipo independiente.'; icon = 'ti-keyboard'; color = 'text-teal'; bg = 'bg-teal-lt'; }
        else if (esComponente) { tipoDesc = '<b>Componente:</b> No requiere código patrimonial. Debe armarse dentro de un equipo padre.'; icon = 'ti-puzzle'; color = 'text-orange'; bg = 'bg-orange-lt'; }
        else if (esSoftware) { tipoDesc = '<b>Software:</b> Requiere código patrimonial. Registra el código de licencia y sus vigencias.'; icon = 'ti-code'; color = 'text-purple'; bg = 'bg-purple-lt'; }
        else { tipoDesc = '<b>Activo General:</b> Requiere código patrimonial. Gestión estándar.'; icon = 'ti-package'; color = 'text-secondary'; bg = 'bg-secondary-lt'; }

        if (tipoDesc) {
            hintBox.innerHTML = `
            <div class="p-3 mt-2 rounded d-flex align-items-center gap-3 ${bg} ${color}" style="border: 1px solid currentColor;">
                <i class="ti ${icon} fs-2 flex-shrink-0"></i>
                <span class="small mb-0">${tipoDesc}</span>
            </div>`;
            hintBox.style.display = 'block';
        } else {
            hintBox.style.display = 'none';
        }
    }

    /* ── Código Patrimonial (Solución al bloqueo de requerido) ── */
    const campoCp = document.querySelector(`.campo-codigoPatrimonial${sufijo}`);
    if (campoCp) {
        const ocultarCp = esComponente;
        campoCp.style.display = ocultarCp ? 'none' : '';

        // Agregar o quitar 'required' dinámicamente
        const inputCp = campoCp.querySelector('input');
        if (inputCp) {
            if (ocultarCp) {
                inputCp.removeAttribute('required');
                inputCp.value = ''; // Limpiar data basura
            } else {
                inputCp.setAttribute('required', 'required');
            }
        }
    }

    /* ── Número de Serie ── */
    const campoSerie = document.querySelector(`.campo-numeroSerie${sufijo}`);
    if (campoSerie) campoSerie.style.display = esSoftware ? 'none' : '';

    /* ── Código Licencia ── */
    const campoLic = document.querySelector(`.campo-codigoLicencia${sufijo}`);
    if (campoLic) {
        campoLic.style.display = esSoftware ? '' : 'none';

        // Agregar o quitar 'required' a licencia
        const inputLic = campoLic.querySelector('input');
        if (inputLic) {
            if (esSoftware) inputLic.setAttribute('required', 'required');
            else {
                inputLic.removeAttribute('required');
                inputLic.value = ''; // Limpiar data basura
            }
        }
    }

    /* ── Fecha Adquisición ── */
    const campoFechaAdq = document.querySelector(`.campo-fechaAdquisicion${sufijo}`);
    if (campoFechaAdq) campoFechaAdq.style.display = '';

    /* ── Labels fechas garantía / licencia ── */
    const lblInicio = document.getElementById(`${p}LabelInicioGarantia`);
    const lblFin = document.getElementById(`${p}LabelFinGarantia`);
    const tituloFechas = document.getElementById(`${p}TituloFechas`);

    if (esSoftware) {
        if (lblInicio) lblInicio.textContent = 'Inicio Licencia';
        if (lblFin) lblFin.textContent = 'Fin Licencia';
        if (tituloFechas) tituloFechas.textContent = 'Fechas de Licencia';
    } else {
        if (lblInicio) lblInicio.textContent = 'Inicio Garantía';
        if (lblFin) lblFin.textContent = 'Fin Garantía';
        if (tituloFechas) tituloFechas.textContent = 'Fechas y Garantía';
    }

    /* ── Opciones de Estado ── */
    const selectEstado = document.getElementById(`${p}Estado`);
    if (selectEstado) {
        const optExpirado = selectEstado.querySelector(`.opt-estado-expirado${sufijo}`);
        if (optExpirado) optExpirado.style.display = esSoftware ? '' : 'none';

        const optsNoSoftware = ['inoperativo', 'reparacion', 'baja'];
        optsNoSoftware.forEach(val => {
            const opt = selectEstado.querySelector(`option[value="${val}"]`);
            if (opt) opt.style.display = esSoftware ? 'none' : '';
        });

        const valActual = selectEstado.value;
        if (esSoftware && optsNoSoftware.includes(valActual)) selectEstado.value = 'disponible';
        if (!esSoftware && valActual === 'expirado') selectEstado.value = 'disponible';
    }
}

/* ═══════════════════════════════════════════════════════
   DOM READY
═══════════════════════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {

    /* ── Inicializar custom selects ── */
    const csNuevoActivo = crearCustomSelect('nuevoIdTipoActivo');
    const csNuevoTipo = crearCustomSelect('nuevoTipoCaracteristica');
    const csNuevoValor = crearCustomSelect('nuevoValorCaracteristica');
    const csEditarActivo = crearCustomSelect('editarIdTipoActivo');
    const csEditarTipo = crearCustomSelect('editarTipoCaracteristica');
    const csEditarValor = crearCustomSelect('editarValorCaracteristica');

    /* ── Custom select para el modal Armar Equipo ── */
    const csComponente = crearCustomSelect('armarComponenteSelect');

    /* ════════════════════════════════════════════════
       TABS FILTRO TIPO ACTIVO
    ════════════════════════════════════════════════ */
    // Tabs filter — aplica a tabla desktop Y cards móvil
    let tipoFiltroActivo = 'todos';
    document.querySelectorAll('.tipo-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tipo-tab-btn').forEach(b => {
                b.classList.remove('active','btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.add('active','btn-primary');
            this.classList.remove('btn-outline-secondary');
            tipoFiltroActivo = this.dataset.tipo;
            // Desktop
            document.querySelectorAll('#tablaActivos tbody tr').forEach(tr => {
                tr.style.display = (tipoFiltroActivo === 'todos' || tr.dataset.tipo === tipoFiltroActivo) ? '' : 'none';
            });
            // Móvil
            if (typeof aplicarFiltroMovilActivos === 'function') aplicarFiltroMovilActivos();
        });
    });

    /* ════════════════════════════════════════════════
       MODAL AGREGAR ACTIVO
    ════════════════════════════════════════════════ */
    document.getElementById('modalAgregarActivo')
        ?.addEventListener('show.bs.modal', async () => {
            document.getElementById('formNuevoActivo')?.reset();
            caracteristicasNuevo.length = 0;
            renderTabla('tablaNuevoEquipoCaracteristicas', caracteristicasNuevo, 'nuevoCaracteristicasIds');
            await cargarActivos(csNuevoActivo);
            await cargarTipos(csNuevoTipo);
            csNuevoValor.setOptions([{ value: '', label: 'Seleccionar valor...' }]);

            // Ocultar hint
            const hint = document.getElementById('nuevoTipoActivoHint');
            if (hint) hint.style.display = 'none';

            // Estado inicial: mostrar campos como Compuesto/Otros (sin tipo seleccionado aún)
            adaptarModal({ esCompuesto: 0, esPeriferico: 0, esComponente: 0, _esSoftware: false }, 'nuevo');
        });

    /* ── Cambio tipo activo AGREGAR → adaptar modal ── */
    document.getElementById('nuevoIdTipoActivo')
        ?.addEventListener('change', function () {
            const id = this.value;
            const datos = csNuevoActivo._tiposData?.[id] ?? { esCompuesto: 0, esPeriferico: 0, esComponente: 0 };
            // Software: ninguno de los flags activos
            datos._esSoftware = !datos.esCompuesto && !datos.esPeriferico && !datos.esComponente;
            adaptarModal(datos, 'nuevo');
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
            const idTipo = csNuevoTipo.getValue();
            const idValor = csNuevoValor.getValue();
            if (!idTipo || !idValor) { mostrarToast('warning', 'Selecciona un tipo y un valor.'); return; }
            if (caracteristicasNuevo.some(c => c.idCaracteristica === idValor)) {
                mostrarToast('warning', 'Esta característica ya fue agregada.'); return;
            }
            const selTipo = document.getElementById('nuevoTipoCaracteristica');
            const selValor = document.getElementById('nuevoValorCaracteristica');
            caracteristicasNuevo.push({
                idCaracteristica: idValor,
                tipo: selTipo.options[selTipo.selectedIndex]?.text ?? idTipo,
                valor: selValor.options[selValor.selectedIndex]?.text ?? idValor
            });
            renderTabla('tablaNuevoEquipoCaracteristicas', caracteristicasNuevo, 'nuevoCaracteristicasIds');
        });

    /* ── Agregar característica EDITAR ── */
    document.getElementById('btnAgregarEditarCaracteristica')
        ?.addEventListener('click', () => {
            const idTipo = csEditarTipo.getValue();
            const idValor = csEditarValor.getValue();
            if (!idTipo || !idValor) { mostrarToast('warning', 'Selecciona un tipo y un valor.'); return; }
            if (caracteristicasEditar.some(c => c.idCaracteristica === idValor)) {
                mostrarToast('warning', 'Esta característica ya fue agregada.'); return;
            }
            const selTipo = document.getElementById('editarTipoCaracteristica');
            const selValor = document.getElementById('editarValorCaracteristica');
            caracteristicasEditar.push({
                idCaracteristica: idValor,
                tipo: selTipo.options[selTipo.selectedIndex]?.text ?? idTipo,
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
       BOTÓN EDITAR ACTIVO
    ════════════════════════════════════════════════ */
    document.addEventListener('click', async function (e) {
        const boton = e.target.closest('.btnEditarActivo');
        if (!boton) return;
        const fd = new FormData();
        fd.append('idActivo', boton.getAttribute('data-id'));
        try {
            const res = await fetch('modules/inventario/ajax/activos.ajax.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.error) { mostrarToast('error', json.error); return; }

            await cargarActivos(csEditarActivo);
            await cargarTipos(csEditarTipo);
            csEditarActivo.setValue(String(json.idTipoActivo));

            // Adaptar modal editar según tipo
            const flags = {
                esCompuesto: intVal(json.esCompuesto ?? 0),
                esPeriferico: intVal(json.esPeriferico ?? 0),
                esComponente: intVal(json.esComponente ?? 0),
            };
            flags._esSoftware = !flags.esCompuesto && !flags.esPeriferico && !flags.esComponente;
            adaptarModal(flags, 'editar');

            document.getElementById('editarIdActivo').value = json.idActivo;
            document.getElementById('editarCodigoPatrimonial').value = json.codigoPatrimonial ?? '';
            document.getElementById('editarCodigoLicencia').value = json.codigoLicencia ?? '';
            document.getElementById('editarNumeroSerie').value = json.numeroSerie ?? '';
            document.getElementById('editarFechaAdquisicion').value = json.fechaAdquisicion ?? '';
            document.getElementById('editarFechaInicioGarantia').value = json.fechaInicioGarantia ?? '';
            document.getElementById('editarFechaFinGarantia').value = json.fechaFinGarantia ?? '';
            document.getElementById('editarEstado').value = json.estado ?? 'disponible';
            document.getElementById('editarUsuarioCreacion').textContent    = json.nombreUsuarioRegistro ?? json.idUsuarioRegistro ?? '--';
            document.getElementById('editarFechaCreacion').textContent         = json.fechaCreacion ?? '--';
            document.getElementById('editarUsuarioModificacion').textContent   = json.nombreUsuarioModifica  ?? json.idUsuarioModifica  ?? '--';
            document.getElementById('editarFechaModificacion').textContent     = json.fechaModificacion ?? '--';

            // Reconstruir lista de características con IDs reales de la BD
            caracteristicasEditar.length = 0;
            if (Array.isArray(json.caracteristicasDetalle) && json.caracteristicasDetalle.length) {
                json.caracteristicasDetalle.forEach(c => {
                    caracteristicasEditar.push({
                        idCaracteristica: String(c.idCaracteristica),
                        tipo: c.tipo,
                        valor: c.valor
                    });
                });
            }
            renderTabla('tablaEditarEquipoCaracteristicas', caracteristicasEditar, 'editarCaracteristicasIds');
            csEditarValor.setOptions([{ value: '', label: 'Seleccionar valor...' }]);

            // Re-adaptar al cambiar tipo en editar (una vez)
            document.getElementById('editarIdTipoActivo').addEventListener('change', function () {
                const id = this.value;
                const datos = csEditarActivo._tiposData?.[id] ?? { esCompuesto: 0, esPeriferico: 0, esComponente: 0 };
                datos._esSoftware = !datos.esCompuesto && !datos.esPeriferico && !datos.esComponente;
                adaptarModal(datos, 'editar');
            }, { once: true });

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarActivo')).show();
        } catch (err) {
            console.error(err);
            mostrarToast('error', 'Error al cargar datos del activo.');
        }
    });

    /* ════════════════════════════════════════════════
       GUARDAR NUEVO ACTIVO
    ════════════════════════════════════════════════ */
    document.getElementById('formNuevoActivo')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btnSubmit = this.querySelector('[type=submit]');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            try {
                const resp = await fetch('modules/inventario/ajax/activos.ajax.php',
                    { method: 'POST', body: new FormData(this) });
                const data = await resp.json();
                manejarRespuestaSP(data, 'modalAgregarActivo', () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarActivo')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error de servidor.'); }
            finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Activo';
            }
        });

    /* ════════════════════════════════════════════════
       ACTUALIZAR ACTIVO
    ════════════════════════════════════════════════ */
    document.getElementById('formEditarActivo')
        ?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btnSubmit = this.querySelector('[type=submit]');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Actualizando...';
            try {
                const resp = await fetch('modules/inventario/ajax/activos.ajax.php',
                    { method: 'POST', body: new FormData(this) });
                const data = await resp.json();
                manejarRespuestaSP(data, 'modalEditarActivo', () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo')).hide();
                    setTimeout(() => location.reload(), 1500);
                });
            } catch { mostrarToast('error', 'Error al comunicarse con el servidor.'); }
            finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="ti ti-check me-1"></i>Actualizar Activo';
            }
        });

    /* ════════════════════════════════════════════════
       MODAL ARMAR EQUIPO — abrir (solo Compuesto)
    ════════════════════════════════════════════════ */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btnArmarActivo');
        if (!btn) return;

        const idPadre = btn.getAttribute('data-id');
        const nombre = btn.getAttribute('data-nombre');
        const icono = btn.getAttribute('data-icono');

        document.getElementById('armarIdActivoPadre').value = idPadre;
        document.getElementById('armarNombrePadre').textContent = nombre;
        document.getElementById('armarIconoPadre').className = `ti ${icono} fs-2`;

        document.getElementById('armarComponenteInfo').style.display = 'none';
        document.getElementById('btnAgregarComponente').disabled = true;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalArmarActivo')).show();

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
            fd.append('idActivoPadre', idPadre);
            const res = await fetch('modules/inventario/ajax/activos.ajax.php', { method: 'POST', body: fd });
            const data = await res.json();
            renderComponentes(data, idPadre);
        } catch {
            lista.innerHTML = '<div class="text-danger small text-center py-3">Error al cargar componentes.</div>';
        }
    }

    function renderComponentes(componentes, idPadre) {
        const lista = document.getElementById('armarListaComponentes');
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
            <div class="componente-card" data-id="${c.idActivo}">
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
                    data-id-hijo="${c.idActivo}"
                    data-id-padre="${idPadre}"
                    title="Quitar del equipo">
                    <i class="ti ti-unlink"></i>
                </button>
            </div>`;
        }).join('');
    }

    /* ════════════════════════════════════════════════
       ARMAR EQUIPO — cargar activos disponibles
    ════════════════════════════════════════════════ */
    async function cargarEquiposDisponibles(idPadre) {
        try {
            const res = await fetch(
                `modules/inventario/ajax/activos.ajax.php?disponibles=1&idPadre=${idPadre}`
            );
            const data = await res.json();

            const ops = [{ value: '', label: 'Seleccionar componente...' }];
            const extra = {};
            data.forEach(eq => {
                ops.push({ value: String(eq.idActivo), label: eq.label });
                extra[String(eq.idActivo)] = eq;
            });
            csComponente.setOptions(ops);
            csComponente._extra = extra;
        } catch (e) { console.error('[cargarEquiposDisponibles]', e); }
    }

    /* ── Cambio en select componente → mostrar info ── */
    document.getElementById('armarComponenteSelect')
        ?.addEventListener('change', function () {
            const val = this.value;
            const infoBox = document.getElementById('armarComponenteInfo');
            const btnAgregar = document.getElementById('btnAgregarComponente');

            if (!val) {
                infoBox.style.display = 'none';
                btnAgregar.disabled = true;
                return;
            }
            const extra = csComponente._extra?.[val];
            if (extra) {
                document.getElementById('armarInfoSerie').textContent = extra.numeroSerie || '—';
                document.getElementById('armarInfoCodigo').textContent = extra.codigoPatrimonial || '—';
                document.getElementById('armarInfoCaract').textContent = extra.caracteristicas || '—';
                infoBox.style.display = 'block';
            }
            btnAgregar.disabled = false;
        });

    /* ════════════════════════════════════════════════
       ARMAR EQUIPO — agregar componente
    ════════════════════════════════════════════════ */
    document.getElementById('btnAgregarComponente')
        ?.addEventListener('click', async function () {
            const idPadre = document.getElementById('armarIdActivoPadre').value;
            const idHijo = csComponente.getValue();
            if (!idHijo) { mostrarToast('warning', 'Seleccione un componente primero.'); return; }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Agregando...';
            try {
                const fd = new FormData();
                fd.append('accion', 'agregarComponente');
                fd.append('idActivoPadre', idPadre);
                fd.append('idActivoHijo', idHijo);
                const res = await fetch('modules/inventario/ajax/activos.ajax.php', { method: 'POST', body: fd });
                const data = await res.json();
                manejarRespuestaSP(data, null, async () => {
                    csComponente.reset();
                    document.getElementById('armarComponenteInfo').style.display = 'none';
                    document.getElementById('btnAgregarComponente').disabled = true;
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

            const idHijo = btn.getAttribute('data-id-hijo');
            const idPadre = btn.getAttribute('data-id-padre');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            try {
                const fd = new FormData();
                fd.append('accion', 'quitarComponente');
                fd.append('idActivoHijo', idHijo);
                const res = await fetch('modules/inventario/ajax/activos.ajax.php', { method: 'POST', body: fd });
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
    if ($.fn.DataTable.isDataTable('#tablaActivos')) $('#tablaActivos').DataTable().destroy();
    const dtActivos = $('#tablaActivos').DataTable({
        responsive: false, pageLength: 10, autoWidth: false,
        dom: `<'d-none'lBf><'table-responsive'tr><'card-footer d-flex align-items-center py-2'<'text-muted small'i><'pagination m-0 ms-auto'p>>`,
        buttons: [
            { extend: 'excelHtml5', text: 'Excel' },
            { extend: 'pdfHtml5',   text: 'PDF'   }
        ],
        columnDefs: [{ targets: -1, orderable: false }]
    });

    document.getElementById('dtSearch')
        ?.addEventListener('input', function () { dtActivos.search(this.value).draw(); });
    document.getElementById('dtPageLength')
        ?.addEventListener('change', function () { dtActivos.page.len(parseInt(this.value)).draw(); });
    document.getElementById('dtBtnExcel')
        ?.addEventListener('click', () => dtActivos.button('.buttons-excel').trigger());
    document.getElementById('dtBtnPdf')
        ?.addEventListener('click', () => dtActivos.button('.buttons-pdf').trigger());

    /* ════════════════════════════════════════════════
       BUSCADOR + PAGINACIÓN MÓVIL ACTIVOS
    ════════════════════════════════════════════════ */
    (function () {
        const PER_PAGE = 5;
        let currentPage = 1;
        let filtered    = [];

        const allItems   = () => Array.from(document.querySelectorAll('#mobileListActivos .mobile-item'));
        const noRes      = document.getElementById('mobileNoResultsActivos');
        const pageInfo   = document.getElementById('mobilePageInfoActivos');
        const prevBtn    = document.getElementById('mobilePrevBtnActivos');
        const nextBtn    = document.getElementById('mobileNextBtnActivos');
        const pagination = document.getElementById('mobilePaginationActivos');

        function render() {
            const total      = filtered.length;
            const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;
            const start = (currentPage - 1) * PER_PAGE;
            const end   = start + PER_PAGE;
            allItems().forEach(item => { item.style.display = 'none'; });
            filtered.forEach((item, i) => { item.style.display = (i >= start && i < end) ? '' : 'none'; });
            if (total === 0) {
                if (pageInfo)   pageInfo.textContent = '';
                if (noRes)      noRes.classList.remove('d-none');
                if (pagination) pagination.style.display = 'none';
            } else {
                if (pageInfo)   pageInfo.textContent = 'Mostrando ' + (start+1) + '-' + Math.min(end,total) + ' de ' + total;
                if (noRes)      noRes.classList.add('d-none');
                if (pagination) pagination.style.display = '';
            }
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
        }

        window.aplicarFiltroMovilActivos = function() {
            const q = (document.getElementById('mobileSearch')?.value || '').toLowerCase().trim();
            currentPage = 1;
            filtered = allItems().filter(item => {
                const nombre = item.getAttribute('data-nombre') || '';
                const tipo   = item.getAttribute('data-tipo')   || '';
                const matchQ    = !q || nombre.includes(q);
                const matchTipo = tipoFiltroActivo === 'todos' || tipo === tipoFiltroActivo;
                return matchQ && matchTipo;
            });
            render();
        };

        document.getElementById('mobileSearch')
            ?.addEventListener('input', () => window.aplicarFiltroMovilActivos());
        prevBtn?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; render(); } });
        nextBtn?.addEventListener('click', () => {
            if (currentPage < Math.ceil(filtered.length / PER_PAGE)) { currentPage++; render(); }
        });

        // Inicializar
        window.aplicarFiltroMovilActivos();
    }());

    /* ── ELIMINAR ACTIVO ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btnEliminarActivo');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const nombre = btn.getAttribute('data-nombre') || 'este activo';
        const esPadre = btn.getAttribute('data-es-padre') === '1';

        if (esPadre) {
            mostrarToast('warning', 'Para eliminar un activo compuesto, primero quita todos sus componentes desde "Armar Equipo".');
            return;
        }

        document.getElementById('eliminarNombreActivo').textContent = nombre;
        document.getElementById('confirmarEliminarActivo').setAttribute('data-id', id);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarEliminarActivo')).show();
    });

    const btnConfirmarEq = document.getElementById('confirmarEliminarActivo');
    if (btnConfirmarEq) {
        btnConfirmarEq.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const fd = new FormData();
            fd.append('eliminarIdActivo', id);

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';

            fetch('modules/inventario/ajax/activos.ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(json => {
                    bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminarActivo')).hide();
                    if (json.resultado === 'ok') {
                        mostrarToast('success', json.mensaje || 'Activo eliminado correctamente.');
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