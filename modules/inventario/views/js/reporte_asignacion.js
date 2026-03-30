/* =============================================================
   REPORTE_ASIGNACION.JS
   Ruta ajax: modules/inventario/ajax/reporte_asignacion_ajax.php
============================================================= */
'use strict';

const AJAX_REPORTE = 'modules/inventario/ajax/reporte_asignacion_ajax.php';

const COLORES_CHART = [
    '#206bc4','#2fb344','#f59f00','#d63939','#ae3ec9','#0ca678',
    '#4299e1','#66d9e8','#f76707','#74c0fc','#a9e34b','#e599f7',
];

/* ── Utilidades ── */
function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function toastRep(tipo, msg) {
    const colores = { success:'bg-success', error:'bg-danger', warning:'bg-warning', info:'bg-info' };
    const c = document.getElementById('toastContainerReporte');
    if (!c) return;
    c.insertAdjacentHTML('beforeend', `
    <div class="toast align-items-center text-white ${colores[tipo]??'bg-secondary'} border-0 mb-2" role="alert">
      <div class="d-flex">
        <div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>`);
    const el = c.lastElementChild;
    new bootstrap.Toast(el, { delay:4000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ════════════════════════════════════════
   ESTADO GLOBAL
════════════════════════════════════════ */
let reporteData        = null;
let chartTipos         = null;
let chartGerencias     = null;
let datosPatrimonial   = null;  // último resultado búsqueda patrimonial

/* ════════════════════════════════════════
   DOM READY
════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {

    await cargarFiltros();

    /* Toggle filtros */
    document.getElementById('btnToggleFiltros')?.addEventListener('click', () => {
        const panel = document.getElementById('panelFiltros');
        const icon  = document.getElementById('iconToggleFiltros');
        const oculto = panel.style.display === 'none';
        panel.style.display = oculto ? '' : 'none';
        icon.className = oculto ? 'ti ti-chevron-up' : 'ti ti-chevron-down';
    });

    document.getElementById('tipoGrafico')?.addEventListener('change', () => {
        if (reporteData) renderGraficoTipos(reporteData.resumenTipos);
    });

    document.getElementById('buscadorTabla')?.addEventListener('input', function() {
        filtrarTabla(this.value.trim().toLowerCase());
    });

    document.getElementById('vistaTabla')?.addEventListener('change', function() {
        if (reporteData) renderTabla(reporteData, this.value);
    });

    document.getElementById('btnLimpiarFiltros')?.addEventListener('click', () => {
        document.getElementById('formReporte')?.reset();
        ['filtroGerencia','filtroUnidad','filtroSede','filtroJefe','filtroTipoContrato'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
    });

    document.getElementById('formReporte')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await generarReporte(new FormData(e.target));
    });

    document.getElementById('btnExportExcel')?.addEventListener('click', exportarExcel);
    document.getElementById('btnExportPdf')?.addEventListener('click', exportarPdf);

    /* ── Búsqueda patrimonial ── */
    document.getElementById('btnBuscarCodigo')?.addEventListener('click', buscarPorCodigo);
    document.getElementById('inputCodigoPatrimonial')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); buscarPorCodigo(); }
    });

    document.getElementById('btnExportExcelPatrimonial')?.addEventListener('click', exportarExcelPatrimonial);
    document.getElementById('btnExportPdfPatrimonial')?.addEventListener('click', exportarPdfPatrimonial);
});

/* ════════════════════════════════════════
   CARGAR FILTROS
════════════════════════════════════════ */
async function cargarFiltros() {
    try {
        const [resFiltros, resJefes] = await Promise.all([
            fetch(AJAX_REPORTE + '?filtros=1').then(r => r.json()),
            fetch(AJAX_REPORTE + '?jefes=1').then(r => r.json()),
        ]);

        poblarSelect('filtroGerencia',     resFiltros.gerencias      ?? [], '— Todas —');
        poblarSelect('filtroUnidad',       resFiltros.unidades       ?? [], '— Todas —');
        poblarSelect('filtroSede',         resFiltros.sedes          ?? [], '— Todas —');
        poblarSelect('filtroTipoContrato', resFiltros.tipos_contrato ?? [], '— Todos —');

        const selJefe = document.getElementById('filtroJefe');
        if (selJefe) {
            Object.entries(resJefes).forEach(([id, nombre]) => {
                selJefe.insertAdjacentHTML('beforeend',
                    `<option value="${escHtml(id)}">${escHtml(nombre)}</option>`);
            });
        }
        const selTipo = document.getElementById('filtroTipoActivo');
        if (selTipo) {
            (resFiltros.tiposActivo ?? []).forEach(t => {
                selTipo.insertAdjacentHTML('beforeend',
                    `<option value="${escHtml(t.idTipoActivo)}">${escHtml(t.descripcion)}</option>`);
            });
        }
    } catch(err) {
        console.error('[cargarFiltros]', err);
        toastRep('warning', 'No se pudieron cargar los filtros.');
    }
}

function poblarSelect(id, arr, placeholder) {
    const sel = document.getElementById(id);
    if (!sel) return;
    sel.innerHTML = `<option value="">${escHtml(placeholder)}</option>`;
    arr.forEach(v => sel.insertAdjacentHTML('beforeend',
        `<option value="${escHtml(v)}">${escHtml(v)}</option>`));
}

/* ════════════════════════════════════════
   GENERAR REPORTE (Tab 1)
════════════════════════════════════════ */
async function generarReporte(fd) {
    document.getElementById('seccionResumen').classList.add('d-none');
    document.getElementById('seccionGraficos').classList.add('d-none');
    document.getElementById('seccionTabla').classList.remove('d-none');
    document.getElementById('spinnerTabla').classList.remove('d-none');
    document.getElementById('wrapTabla').classList.add('d-none');
    document.getElementById('btnExportExcel').disabled = true;
    document.getElementById('btnExportPdf').disabled   = true;

    try {
        const data = await fetch(AJAX_REPORTE, { method:'POST', body:fd }).then(r => r.json());
        if (data.error) { toastRep('error', data.error); return; }

        reporteData = data;
        renderTarjetasResumen(data);
        renderGraficoTipos(data.resumenTipos);
        renderGraficoGerencias(data.filas);
        renderTabla(data, document.getElementById('vistaTabla')?.value ?? 'completa');

        document.getElementById('seccionResumen').classList.remove('d-none');
        document.getElementById('seccionGraficos').classList.remove('d-none');
        document.getElementById('btnExportExcel').disabled = false;
        document.getElementById('btnExportPdf').disabled   = false;

        toastRep('success', `Reporte generado: ${data.filas.length} trabajadores.`);
    } catch(err) {
        console.error('[generarReporte]', err);
        toastRep('error', 'Error al generar el reporte.');
    } finally {
        document.getElementById('spinnerTabla').classList.add('d-none');
        document.getElementById('wrapTabla').classList.remove('d-none');
    }
}

/* ════════════════════════════════════════
   TARJETAS RESUMEN
════════════════════════════════════════ */
function renderTarjetasResumen(data) {
    const cont = document.getElementById('tarjetasResumen');
    if (!cont) return;
    const totalActivos = data.resumenTipos.reduce((s, r) => s + parseInt(r.total), 0);

    let html = `
    <div class="col-6 col-md-3">
      <div class="card card-sm"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <span class="bg-primary-lt p-2 rounded"><i class="ti ti-users text-primary fs-2"></i></span>
          <div><div class="text-muted small">Personal</div><div class="fw-bold fs-3">${data.totalPersonal}</div></div>
        </div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-sm"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <span class="bg-success-lt p-2 rounded"><i class="ti ti-user-check text-success fs-2"></i></span>
          <div><div class="text-muted small">Con activos</div><div class="fw-bold fs-3">${data.totalConActivos}</div></div>
        </div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-sm"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <span class="bg-warning-lt p-2 rounded"><i class="ti ti-package text-warning fs-2"></i></span>
          <div><div class="text-muted small">Total activos</div><div class="fw-bold fs-3">${totalActivos}</div></div>
        </div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-sm"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <span class="bg-info-lt p-2 rounded"><i class="ti ti-category text-info fs-2"></i></span>
          <div><div class="text-muted small">Tipos</div><div class="fw-bold fs-3">${data.resumenTipos.length}</div></div>
        </div>
      </div></div>
    </div>`;

    data.resumenTipos.forEach((r, i) => {
        const color = COLORES_CHART[i % COLORES_CHART.length];
        html += `
    <div class="col-6 col-md-3 col-lg-2">
      <div class="card card-sm" style="border-left:3px solid ${escHtml(color)}">
        <div class="card-body py-2 px-3">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ${escHtml(r.icono??'ti-package')} fs-2" style="color:${escHtml(color)}"></i>
            <div>
              <div class="text-muted" style="font-size:.7rem">${escHtml(r.tipoActivo)}</div>
              <div class="fw-bold">${r.total}</div>
            </div>
          </div>
        </div>
      </div>
    </div>`;
    });
    cont.innerHTML = html;
}

/* ════════════════════════════════════════
   GRÁFICO — TIPOS
════════════════════════════════════════ */
function renderGraficoTipos(resumen) {
    const ctx = document.getElementById('graficoTipos');
    if (!ctx || !resumen?.length) return;
    if (chartTipos) { chartTipos.destroy(); chartTipos = null; }

    const labels  = resumen.map(r => r.tipoActivo);
    const totales = resumen.map(r => parseInt(r.total));
    const tipo    = document.getElementById('tipoGrafico')?.value ?? 'bar';
    const isPie   = tipo === 'pie' || tipo === 'doughnut';

    chartTipos = new Chart(ctx, {
        type: tipo === 'horizontalBar' ? 'bar' : tipo,
        data: {
            labels,
            datasets: [{
                label: 'Cantidad',
                data: totales,
                backgroundColor: COLORES_CHART.slice(0, labels.length),
                borderColor: '#fff', borderWidth: isPie ? 2 : 0, borderRadius: isPie ? 0 : 4,
            }],
        },
        options: {
            indexAxis: tipo === 'horizontalBar' ? 'y' : 'x',
            responsive: true,
            plugins: { legend: { display: isPie, position:'right' } },
            scales: isPie ? {} : { y: { beginAtZero: true, ticks: { stepSize:1 } } },
        }
    });
}

/* ════════════════════════════════════════
   GRÁFICO — GERENCIAS
════════════════════════════════════════ */
function renderGraficoGerencias(filas) {
    const ctx = document.getElementById('graficoGerencias');
    if (!ctx) return;
    if (chartGerencias) { chartGerencias.destroy(); chartGerencias = null; }

    const map = {};
    filas.forEach(f => {
        if (!f.tieneActivos) return;
        const g = f.gerencia || 'Sin gerencia';
        map[g] = (map[g] ?? 0) + f.total;
    });
    const sorted  = Object.entries(map).sort((a,b) => b[1]-a[1]).slice(0, 10);
    chartGerencias = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: sorted.map(e => e[0]),
            datasets: [{
                data: sorted.map(e => e[1]),
                backgroundColor: COLORES_CHART.slice(0, sorted.length),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: { responsive: true, plugins: { legend: { position:'bottom', labels:{ boxWidth:12, font:{size:11} } } } }
    });
}

/* ════════════════════════════════════════
   TABLA PRINCIPAL (Tab 1)
════════════════════════════════════════ */
function renderTabla(data, vista) {
    const soloConActivos = document.getElementById('soloConActivos')?.checked;
    const filas = soloConActivos ? data.filas.filter(f => f.tieneActivos) : data.filas;

    const tiposIds = Object.keys(data.tiposUsados).map(Number).sort((a,b) =>
        data.tiposUsados[a].descripcion.localeCompare(data.tiposUsados[b].descripcion)
    );

    // THEAD
    // En vista completa se añaden Cód. Patrimonial (primero activo), Marca y Valor tras el bloque de tipos
    let thead = '<tr class="text-uppercase text-muted small"><th>#</th><th>DNI</th><th>Trabajador</th>';
    if (vista === 'completa') thead += '<th>Cargo</th><th>Gerencia</th><th>Unidad</th><th>Sede</th><th>Jefe Inmediato</th>';
    tiposIds.forEach(id => {
        const t = data.tiposUsados[id];
        thead += `<th class="text-center"><i class="ti ${escHtml(t.icono??'ti-package')} me-1"></i>${escHtml(t.descripcion)}</th>`;
    });
    thead += '<th class="text-center fw-bold">Total</th>';
    if (vista === 'completa') {
        thead += '<th>Estación</th>';
        thead += '<th><i class="ti ti-barcode me-1"></i>Cód. Patrimonial</th>';
        thead += '<th><i class="ti ti-tag me-1"></i>Marca</th>';
        thead += '<th><i class="ti ti-currency-dollar me-1"></i>Valor</th>';
    }
    thead += '</tr>';
    document.getElementById('theadReporte').innerHTML = thead;

    // TBODY
    let tbodyHtml = '';
    filas.forEach((f, idx) => {
        tbodyHtml += `<tr class="${f.tieneActivos?'':'text-muted'}" data-dni="${escHtml(f.dni)}" data-nombre="${escHtml(f.nombre)}">`;
        tbodyHtml += `<td class="small">${idx+1}</td>`;
        tbodyHtml += `<td class="font-monospace small">${escHtml(f.dni)}</td>`;
        tbodyHtml += `<td><div class="fw-semibold small">${escHtml(f.nombre)}</div>
                       ${f.cargo?`<div class="text-muted" style="font-size:.7rem">${escHtml(f.cargo)}</div>`:''}</td>`;
        if (vista === 'completa') {
            tbodyHtml += `<td class="small">${escHtml(f.cargo)}</td>
                          <td class="small text-truncate" style="max-width:150px" title="${escHtml(f.gerencia)}">${escHtml(f.gerencia)}</td>
                          <td class="small text-truncate" style="max-width:150px" title="${escHtml(f.unidad)}">${escHtml(f.unidad)}</td>
                          <td><span class="badge bg-secondary-lt text-secondary">${escHtml(f.sede||'—')}</span></td>
                          <td class="small text-muted text-truncate" style="max-width:140px">${escHtml(f.jefeInmediato||'—')}</td>`;
        }
        tiposIds.forEach(id => {
            const cant = f.tipos[id] ?? 0;
            tbodyHtml += cant > 0
                ? `<td class="text-center"><button class="btn btn-sm badge bg-primary-lt text-primary btnVerDetalle"
                       data-dni="${escHtml(f.dni)}" data-tipo="${id}"
                       data-nombre="${escHtml(f.nombre)}"
                       data-tipodesc="${escHtml(data.tiposUsados[id]?.descripcion)}">${cant}</button></td>`
                : `<td class="text-center text-muted">—</td>`;
        });
        tbodyHtml += `<td class="text-center">${f.total>0?`<span class="badge bg-success-lt text-success fw-bold">${f.total}</span>`:'<span class="text-muted small">—</span>'}</td>`;
        if (vista === 'completa') {
            tbodyHtml += `<td class="small">${escHtml(f.estacion||'—')}</td>`;
            // Cód. Patrimonial: muestra el primero; si hay más muestra badge "+N"
            const codigos = f.codigosPatrimoniales ?? [];
            if (codigos.length === 0) {
                tbodyHtml += `<td class="small text-muted">—</td>`;
            } else if (codigos.length === 1) {
                tbodyHtml += `<td><span class="font-monospace small">${escHtml(codigos[0])}</span></td>`;
            } else {
                tbodyHtml += `<td>
                    <span class="font-monospace small">${escHtml(codigos[0])}</span>
                    <span class="badge bg-secondary-lt text-secondary ms-1" title="${escHtml(codigos.join(', '))}">+${codigos.length-1}</span>
                </td>`;
            }
            // Marca
            const marcas = f.marcas ?? [];
            tbodyHtml += marcas.length
                ? `<td class="small">${escHtml([...new Set(marcas)].join(', '))}</td>`
                : `<td class="small text-muted">—</td>`;
            // Valor
            const valorTotal = f.valorTotal ?? null;
            tbodyHtml += valorTotal !== null && valorTotal > 0
                ? `<td class="small text-end">S/ ${parseFloat(valorTotal).toLocaleString('es-PE', {minimumFractionDigits:2})}</td>`
                : `<td class="small text-muted">—</td>`;
        }
        tbodyHtml += '</tr>';
    });
    document.getElementById('tbodyReporte').innerHTML = tbodyHtml ||
        '<tr><td colspan="99" class="text-center text-muted py-4">Sin resultados</td></tr>';

    // TFOOT
    const colBase = vista === 'completa' ? 9 : 3;
    let tfoot = `<tr class="fw-bold bg-light"><td colspan="${colBase}" class="small">Totales</td>`;
    tiposIds.forEach(id => {
        const sum = filas.reduce((acc, f) => acc + (f.tipos[id]??0), 0);
        tfoot += `<td class="text-center">${sum > 0 ? sum : '—'}</td>`;
    });
    tfoot += `<td class="text-center text-success fw-bold">${filas.reduce((acc,f)=>acc+f.total,0)}</td>`;
    if (vista === 'completa') {
        // Estación (vacío), Cód. Patrimonial (vacío), Marca (vacío), Valor (suma total)
        const sumValor = filas.reduce((acc, f) => acc + (parseFloat(f.valorTotal) || 0), 0);
        tfoot += '<td></td><td></td><td></td>';
        tfoot += sumValor > 0
            ? `<td class="text-end fw-bold">S/ ${sumValor.toLocaleString('es-PE', {minimumFractionDigits:2})}</td>`
            : '<td></td>';
    }
    tfoot += '</tr>';
    document.getElementById('tfootReporte').innerHTML = tfoot;

    document.getElementById('tituloTabla').innerHTML =
        `<i class="ti ti-table me-2 text-primary"></i>Resultado — ${filas.length} trabajador(es)`;

    document.querySelectorAll('.btnVerDetalle').forEach(btn => {
        btn.addEventListener('click', () => verDetalleActivos(
            btn.dataset.dni, parseInt(btn.dataset.tipo),
            btn.dataset.nombre, btn.dataset.tipodesc
        ));
    });
}

function filtrarTabla(q) {
    document.querySelectorAll('#tbodyReporte tr').forEach(tr => {
        const txt = (tr.dataset.nombre??'') + ' ' + (tr.dataset.dni??'');
        tr.style.display = (!q || txt.toLowerCase().includes(q)) ? '' : 'none';
    });
}

/* ════════════════════════════════════════
   MODAL DETALLE CON HIJOS (Tab 1)
════════════════════════════════════════ */
async function verDetalleActivos(dni, idTipo, nombre, tipoDesc) {
    document.getElementById('detalleModalTitulo').textContent = `${nombre} — ${tipoDesc}`;
    document.getElementById('detalleModalCuerpo').innerHTML =
        '<div class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</div>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleActivos')).show();

    try {
        const fd = new FormData();
        fd.append('detalleDni',    dni);
        fd.append('detalleIdTipo', idTipo);
        const data = await fetch(AJAX_REPORTE, { method:'POST', body:fd }).then(r => r.json());

        if (!data.length) {
            document.getElementById('detalleModalCuerpo').innerHTML =
                '<div class="text-muted text-center py-3">Sin activos registrados.</div>';
            return;
        }

        document.getElementById('detalleModalCuerpo').innerHTML =
            data.map((a, i) => renderCardActivo(a, i)).join('');
    } catch(err) {
        document.getElementById('detalleModalCuerpo').innerHTML =
            '<div class="text-danger text-center py-3">Error al cargar el detalle.</div>';
    }
}

/* ════════════════════════════════════════
   CARD DE ACTIVO (reutilizable para Tab1 y Tab2)
   Ahora incluye: marca, valor, codigoPatrimonial ya existía
════════════════════════════════════════ */
function renderCardActivo(a, idx) {
    const estadoClass = {
        asignado:'bg-primary-lt text-primary', disponible:'bg-success-lt text-success',
        reparacion:'bg-warning-lt text-warning', inoperativo:'bg-danger-lt text-danger',
        baja:'bg-danger-lt text-danger',
    }[a.estado?.toLowerCase()] ?? 'bg-secondary-lt text-secondary';

    // Fila de trabajador asignado (solo en búsqueda patrimonial)
    const filaTrabajador = a.dniAsignado
        ? `<tr>
            <td class="text-muted small" style="width:160px">Trabajador asignado</td>
            <td class="fw-semibold small">${escHtml(a.nombreAsignado)} <span class="badge bg-primary-lt text-primary ms-1">${escHtml(a.dniAsignado)}</span></td>
           </tr>
           <tr>
            <td class="text-muted small">Fecha asignación</td>
            <td class="small">${escHtml(a.fechaAsignacion||'—')}</td>
           </tr>` : '';

    // Indicador de componente hijo
    const filaPadre = a.codigoPatrimonialPadre
        ? `<tr>
            <td class="text-muted small">Componente de</td>
            <td><span class="badge bg-warning-lt text-warning font-monospace">${escHtml(a.codigoPatrimonialPadre)}</span>
                <span class="text-muted small ms-1">${escHtml(a.tipoPadre||'')}</span></td>
           </tr>` : '';

    // ── NUEVO: filas de marca y valor ──
    const filaMarca = a.marca
        ? `<tr>
            <td class="text-muted small">Marca</td>
            <td class="small fw-semibold">${escHtml(a.marca)}</td>
           </tr>` : '';

    const filaValor = (a.valor !== null && a.valor !== undefined && a.valor !== '')
        ? `<tr>
            <td class="text-muted small">Valor</td>
            <td class="small">
              <span class="badge bg-success-lt text-success">
                S/ ${parseFloat(a.valor).toLocaleString('es-PE', {minimumFractionDigits:2})}
              </span>
            </td>
           </tr>` : '';

    // Hijos (componentes)
    let hijosHtml = '';
    if (a.hijos?.length) {
        hijosHtml = `
        <div class="mt-2">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="ti ti-git-branch text-orange"></i>
            <span class="fw-semibold small text-orange">Componentes / Activos hijos (${a.hijos.length})</span>
          </div>
          <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0" style="font-size:.8rem">
            <thead class="table-warning">
              <tr>
                <th>#</th>
                <th>Tipo</th>
                <th>Cód. Patrimonial</th>
                <th>N° Serie</th>
                <th>Licencia</th>
                <th>Marca</th>
                <th>Valor</th>
                <th>Estado</th>
                <th>F. Adquisición</th>
                <th>Garantía</th>
              </tr>
            </thead>
            <tbody>
        `;
        a.hijos.forEach((h, j) => {
            const estH = {
                asignado:'bg-primary-lt text-primary', disponible:'bg-success-lt text-success',
                reparacion:'bg-warning-lt text-warning', inoperativo:'bg-danger-lt text-danger',
            }[h.estado?.toLowerCase()] ?? 'bg-secondary-lt text-secondary';
            const garantia = h.fechaFinGarantia
                ? `<span class="text-muted small">${escHtml(h.fechaInicioGarantia||'')} → ${escHtml(h.fechaFinGarantia)}</span>`
                : '—';
            const marcaHijo = h.marca ? escHtml(h.marca) : '<span class="text-muted">—</span>';
            const valorHijo = (h.valor !== null && h.valor !== undefined && h.valor !== '')
                ? `S/ ${parseFloat(h.valor).toLocaleString('es-PE', {minimumFractionDigits:2})}`
                : '<span class="text-muted">—</span>';
            hijosHtml += `
              <tr>
                <td class="text-muted">${j+1}</td>
                <td><i class="ti ${escHtml(h.iconoTipo??'ti-package')} me-1"></i>${escHtml(h.tipoActivo)}</td>
                <td class="font-monospace">${escHtml(h.codigoPatrimonial||'—')}</td>
                <td class="font-monospace">${escHtml(h.numeroSerie||'—')}</td>
                <td class="font-monospace">${escHtml(h.codigoLicencia||'—')}</td>
                <td>${marcaHijo}</td>
                <td>${valorHijo}</td>
                <td><span class="badge ${estH}">${escHtml(h.estado)}</span></td>
                <td>${escHtml(h.fechaAdquisicion||'—')}</td>
                <td>${garantia}</td>
              </tr>`;
        });
        hijosHtml += '</tbody></table></div></div>';
    }

    return `
    <div class="card mb-3 border-start border-primary border-3">
      <div class="card-header py-2">
        <div class="d-flex align-items-center gap-2">
          <i class="ti ${escHtml(a.iconoTipo??'ti-package')} text-primary fs-3"></i>
          <div>
            <span class="fw-bold font-monospace">${escHtml(a.codigoPatrimonial||'Sin código')}</span>
            <span class="badge ${estadoClass} ms-2">${escHtml(a.estado)}</span>
            <span class="text-muted small ms-2">${escHtml(a.tipoActivo)}</span>
            ${a.marca ? `<span class="badge bg-secondary-lt text-secondary ms-2"><i class="ti ti-tag me-1"></i>${escHtml(a.marca)}</span>` : ''}
          </div>
          ${a.hijos?.length ? `<span class="badge bg-orange-lt text-orange ms-auto">
            <i class="ti ti-git-branch me-1"></i>${a.hijos.length} componente(s)
          </span>` : ''}
        </div>
      </div>
      <div class="card-body pt-2 pb-2">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <table class="table table-sm mb-0">
              <tbody>
                <tr><td class="text-muted small" style="width:160px">N° Serie</td>
                    <td class="font-monospace small">${escHtml(a.numeroSerie||'—')}</td></tr>
                <tr><td class="text-muted small">Licencia</td>
                    <td class="font-monospace small">${escHtml(a.codigoLicencia||'—')}</td></tr>
                ${filaMarca}
                ${filaValor}
                <tr><td class="text-muted small">Adquisición</td>
                    <td class="small">${escHtml(a.fechaAdquisicion||'—')}</td></tr>
                <tr><td class="text-muted small">Garantía</td>
                    <td class="small">${escHtml(a.fechaInicioGarantia||'—')} ${a.fechaFinGarantia?'→ '+escHtml(a.fechaFinGarantia):''}</td></tr>
                ${filaPadre}
              </tbody>
            </table>
          </div>
          <div class="col-12 col-md-6">
            <table class="table table-sm mb-0">
              <tbody>
                <tr><td class="text-muted small" style="width:140px">Estación</td>
                    <td class="small">${escHtml(a.nombreEstacion||'—')}</td></tr>
                <tr><td class="text-muted small">Ubicación</td>
                    <td class="small">${escHtml(a.ubicacion||'—')}${a.ambiente?' / '+escHtml(a.ambiente):''}</td></tr>
                ${filaTrabajador}
              </tbody>
            </table>
          </div>
        </div>
        ${hijosHtml}
      </div>
    </div>`;
}

/* ════════════════════════════════════════
   BÚSQUEDA POR CÓDIGO PATRIMONIAL (Tab 2)
════════════════════════════════════════ */
async function buscarPorCodigo() {
    const codigo = document.getElementById('inputCodigoPatrimonial')?.value?.trim() ?? '';
    if (codigo.length < 2) { toastRep('warning', 'Ingresa al menos 2 caracteres.'); return; }

    document.getElementById('resultadoPatrimonial').classList.add('d-none');
    document.getElementById('sinResultadosPatrimonial').classList.add('d-none');
    document.getElementById('spinnerPatrimonial').classList.remove('d-none');

    try {
        const data = await fetch(`${AJAX_REPORTE}?buscarCodigo=${encodeURIComponent(codigo)}`).then(r => r.json());

        document.getElementById('spinnerPatrimonial').classList.add('d-none');

        if (data.error) { toastRep('error', data.error); return; }

        if (!data.length) {
            document.getElementById('sinResultadosPatrimonial').classList.remove('d-none');
            return;
        }

        datosPatrimonial = data;

        // Separar raíces de hijos directos devueltos (los hijos ya vienen en .hijos)
        const raices = data.filter(a => !a.idActivoPadre);
        const hijosDirectos = data.filter(a => a.idActivoPadre);

        let html = '';
        // Primero los activos raíz con sus hijos embebidos
        raices.forEach((a, i) => { html += renderCardActivo(a, i); });
        // Si la búsqueda devolvió activos que son hijos y no tienen padre en el resultado
        if (hijosDirectos.length && !raices.length) {
            hijosDirectos.forEach((a, i) => { html += renderCardActivo(a, i); });
        }

        document.getElementById('cuerpoPatrimonial').innerHTML =
            `<div class="p-3">${html}</div>`;
        document.getElementById('tituloPatrimonial').innerHTML =
            `<i class="ti ti-package me-2 text-primary"></i>${data.length} activo(s) encontrado(s) para "<strong>${escHtml(codigo)}</strong>"`;
        document.getElementById('resultadoPatrimonial').classList.remove('d-none');

    } catch(err) {
        document.getElementById('spinnerPatrimonial').classList.add('d-none');
        console.error('[buscarPorCodigo]', err);
        toastRep('error', 'Error al realizar la búsqueda.');
    }
}

/* ════════════════════════════════════════
   EXPORTAR EXCEL (Tab 1)
   Añade: Cód. Patrimonial(es), Marca, Valor
════════════════════════════════════════ */
function exportarExcel() {
    if (!reporteData || typeof XLSX === 'undefined') {
        toastRep('error', 'Datos no disponibles o SheetJS no cargado.'); return;
    }
    const soloConActivos = document.getElementById('soloConActivos')?.checked;
    const filas = soloConActivos ? reporteData.filas.filter(f => f.tieneActivos) : reporteData.filas;
    const tiposIds = Object.keys(reporteData.tiposUsados).map(Number).sort((a,b) =>
        reporteData.tiposUsados[a].descripcion.localeCompare(reporteData.tiposUsados[b].descripcion)
    );

    const enc = ['#','DNI','Trabajador','Cargo','Gerencia','Unidad','Sede','Jefe Inmediato'];
    tiposIds.forEach(id => enc.push(reporteData.tiposUsados[id].descripcion));
    enc.push('TOTAL','Estación','F. Asignación','Cód. Patrimonial(es)','Marca(s)','Valor Total (S/)');

    const wsData = [enc];
    filas.forEach((f, i) => {
        const row = [i+1, f.dni, f.nombre, f.cargo, f.gerencia, f.unidad, f.sede, f.jefeInmediato];
        tiposIds.forEach(id => row.push(f.tipos[id] ?? 0));
        row.push(
            f.total,
            f.estacion,
            f.fechaAsig,
            (f.codigosPatrimoniales ?? []).join(' | ') || '—',
            [...new Set(f.marcas ?? [])].join(', ') || '—',
            f.valorTotal ?? ''
        );
        wsData.push(row);
    });
    const totRow = ['','','','','','','',''];
    tiposIds.forEach(id => totRow.push(filas.reduce((a,f)=>a+(f.tipos[id]??0),0)));
    const sumValor = filas.reduce((a,f)=>a+(parseFloat(f.valorTotal)||0),0);
    totRow.push(filas.reduce((a,f)=>a+f.total,0),'','','','', sumValor > 0 ? sumValor.toFixed(2) : '');
    wsData.push(totRow);

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(wsData), 'Reporte');
    XLSX.utils.book_append_sheet(wb,
        XLSX.utils.aoa_to_sheet([['Tipo','Total']].concat(reporteData.resumenTipos.map(r=>[r.tipoActivo,r.total]))),
        'Resumen'
    );
    XLSX.writeFile(wb, `Reporte_Activos_${new Date().toISOString().slice(0,10)}.xlsx`);
    toastRep('success', 'Excel generado.');
}

/* ════════════════════════════════════════
   EXPORTAR PDF (Tab 1)
   Añade: Cód. Patrimonial, Marca, Valor
════════════════════════════════════════ */
function exportarPdf() {
    if (!reporteData || typeof window.jspdf?.jsPDF === 'undefined') {
        toastRep('error', 'Datos no disponibles o jsPDF no cargado.'); return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a3' });
    const soloConActivos = document.getElementById('soloConActivos')?.checked;
    const filas = soloConActivos ? reporteData.filas.filter(f => f.tieneActivos) : reporteData.filas;
    const tiposIds = Object.keys(reporteData.tiposUsados).map(Number).sort((a,b) =>
        reporteData.tiposUsados[a].descripcion.localeCompare(reporteData.tiposUsados[b].descripcion)
    );

    doc.setFontSize(14); doc.setFont('helvetica','bold');
    doc.text('Reporte de Activos por Trabajador', 14, 15);
    doc.setFontSize(9); doc.setFont('helvetica','normal');
    doc.text(`Generado: ${new Date().toLocaleString('es-PE')}  |  Trabajadores: ${filas.length}`, 14, 21);

    const enc = ['#','DNI','Trabajador','Cargo','Gerencia','Sede'];
    tiposIds.forEach(id => enc.push(reporteData.tiposUsados[id].descripcion));
    enc.push('TOTAL','Cód. Patrimonial','Marca','Valor (S/)');

    doc.autoTable({
        head: [enc],
        body: filas.map((f, i) => {
            const row = [i+1, f.dni, f.nombre.substring(0,30), f.cargo.substring(0,20),
                         f.gerencia.substring(0,25), f.sede];
            tiposIds.forEach(id => row.push(f.tipos[id] ?? '—'));
            row.push(
                f.total || '—',
                (f.codigosPatrimoniales ?? []).slice(0,2).join('\n') || '—',
                [...new Set(f.marcas ?? [])].slice(0,2).join(', ') || '—',
                f.valorTotal ? parseFloat(f.valorTotal).toLocaleString('es-PE', {minimumFractionDigits:2}) : '—'
            );
            return row;
        }),
        startY: 26,
        styles: { fontSize:6.5, cellPadding:1.5 },
        headStyles: { fillColor:[32,107,196], textColor:255, fontStyle:'bold' },
        alternateRowStyles: { fillColor:[245,247,251] },
        columnStyles: {
            // Columnas de Cód. Patrimonial, Marca y Valor al final — un poco más anchas
            [enc.length-3]: { cellWidth: 28 },
            [enc.length-2]: { cellWidth: 22 },
            [enc.length-1]: { cellWidth: 18, halign:'right' },
        }
    });

    // Hoja 2: Resumen
    doc.addPage('a4','landscape');
    doc.setFontSize(12); doc.setFont('helvetica','bold');
    doc.text('Resumen por Tipo de Activo', 14, 15);
    doc.autoTable({
        head: [['Tipo','Total']],
        body: reporteData.resumenTipos.map(r => [r.tipoActivo, r.total]),
        startY: 20,
        styles: { fontSize:9 },
        headStyles: { fillColor:[32,107,196], textColor:255 },
    });

    doc.save(`Reporte_Activos_${new Date().toISOString().slice(0,10)}.pdf`);
    toastRep('success', 'PDF generado.');
}

/* ════════════════════════════════════════
   EXPORTAR PATRIMONIAL — Excel
   Añade: Marca y Valor
════════════════════════════════════════ */
function exportarExcelPatrimonial() {
    if (!datosPatrimonial || typeof XLSX === 'undefined') {
        toastRep('error', 'Sin datos o SheetJS no cargado.'); return;
    }
    const enc  = ['Cód. Patrimonial','N° Serie','Licencia','Marca','Valor (S/)','Tipo','Estado',
                   'Trabajador Asignado','DNI Asignado','Estación','Ubicación',
                   'F. Asignación','F. Adquisición','Garantía Inicio','Garantía Fin',
                   'Es Componente de','Tipo Padre'];
    const encH = ['  Cód. Patrimonial Hijo','  Tipo Hijo','  N° Serie Hijo','  Marca Hijo','  Valor Hijo (S/)','  Estado Hijo','  F. Adquisición Hijo'];
    const wsData = [enc.concat(encH)];

    datosPatrimonial.forEach(a => {
        const fila = [
            a.codigoPatrimonial||'', a.numeroSerie||'', a.codigoLicencia||'',
            a.marca||'', a.valor||'',
            a.tipoActivo||'', a.estado||'', a.nombreAsignado||'', a.dniAsignado||'',
            a.nombreEstacion||'', (a.ubicacion||'')+(a.ambiente?' / '+a.ambiente:''),
            a.fechaAsignacion||'', a.fechaAdquisicion||'',
            a.fechaInicioGarantia||'', a.fechaFinGarantia||'',
            a.codigoPatrimonialPadre||'', a.tipoPadre||'',
        ];
        if (a.hijos?.length) {
            const primerHijo = a.hijos[0];
            fila.push(
                primerHijo.codigoPatrimonial||'', primerHijo.tipoActivo||'',
                primerHijo.numeroSerie||'', primerHijo.marca||'', primerHijo.valor||'',
                primerHijo.estado||'', primerHijo.fechaAdquisicion||''
            );
            wsData.push(fila);
            a.hijos.slice(1).forEach(h => {
                wsData.push(['','','','','','','','','','','','','','','','','',
                    h.codigoPatrimonial||'', h.tipoActivo||'',
                    h.numeroSerie||'', h.marca||'', h.valor||'',
                    h.estado||'', h.fechaAdquisicion||'']);
            });
        } else {
            wsData.push(fila);
        }
    });

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(wsData), 'Activos');
    XLSX.writeFile(wb, `Activos_CodigoPatrimonial_${new Date().toISOString().slice(0,10)}.xlsx`);
    toastRep('success', 'Excel generado.');
}

/* ════════════════════════════════════════
   EXPORTAR PATRIMONIAL — PDF
   Añade: Marca y Valor
════════════════════════════════════════ */
function exportarPdfPatrimonial() {
    if (!datosPatrimonial || typeof window.jspdf?.jsPDF === 'undefined') {
        toastRep('error', 'Sin datos o jsPDF no cargado.'); return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });

    const codigo = document.getElementById('inputCodigoPatrimonial')?.value ?? '';
    doc.setFontSize(13); doc.setFont('helvetica','bold');
    doc.text(`Activos — Código Patrimonial: "${codigo}"`, 14, 15);
    doc.setFontSize(9); doc.setFont('helvetica','normal');
    doc.text(`Generado: ${new Date().toLocaleString('es-PE')}  |  ${datosPatrimonial.length} activo(s)`, 14, 21);

    const rows = [];
    datosPatrimonial.forEach(a => {
        rows.push([
            a.codigoPatrimonial||'—',
            a.tipoActivo||'—',
            a.numeroSerie||'—',
            a.marca||'—',
            a.valor ? parseFloat(a.valor).toLocaleString('es-PE',{minimumFractionDigits:2}) : '—',
            a.estado||'—',
            a.nombreAsignado ? `${a.nombreAsignado}\n(${a.dniAsignado})` : '—',
            a.nombreEstacion||'—',
            a.fechaAsignacion||'—',
            a.hijos?.length ? `${a.hijos.length} comp.` : '—',
        ]);
        (a.hijos ?? []).forEach(h => {
            rows.push([
                `  ↳ ${h.codigoPatrimonial||'—'}`,
                h.tipoActivo||'—',
                h.numeroSerie||'—',
                h.marca||'—',
                h.valor ? parseFloat(h.valor).toLocaleString('es-PE',{minimumFractionDigits:2}) : '—',
                h.estado||'—',
                '—','—','—','—',
            ]);
        });
    });

    doc.autoTable({
        head: [['Cód. Patrimonial','Tipo','N° Serie','Marca','Valor (S/)','Estado','Trabajador','Estación','F. Asignación','Componentes']],
        body: rows,
        startY: 26,
        styles: { fontSize:7, cellPadding:1.5 },
        headStyles: { fillColor:[32,107,196], textColor:255, fontStyle:'bold' },
        alternateRowStyles: { fillColor:[245,247,251] },
        columnStyles: {
            4: { halign:'right' },  // Valor alineado a la derecha
        },
        didParseCell(d) {
            if (d.section==='body' && String(d.cell.raw).startsWith('  ↳')) {
                d.cell.styles.fillColor = [255,249,230];
                d.cell.styles.textColor = [150,80,0];
            }
        }
    });

    doc.save(`Activos_PatrimonialSearch_${new Date().toISOString().slice(0,10)}.pdf`);
    toastRep('success', 'PDF generado.');
}