<body>
<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
?>

<style>
.badge-disponible { background: #d1fae5; color: #065f46; }
.badge-asignada   { background: #dbeafe; color: #1e40af; }
</style>

<div class="page">
<?php include __DIR__ . '/_submenu.php'; ?>

  <div class="page-wrapper">
    <div class="container-xl">

      <div class="page-header d-print-none">
        <div class="row align-items-center">
          <div class="col">
            <h2 class="page-title">Gestión de Estaciones</h2>
            <p class="text-muted mb-0">Administración de estaciones de trabajo y sus equipos.</p>
          </div>
          <div class="col-auto ms-auto">
            <a href="?module=inventario&action=agregarEstacion" class="btn btn-primary">
              <i class="ti ti-plus me-1"></i>Nueva Estación
            </a>
            <button class="btn btn-outline-success ms-2" id="btnNuevaTerminal">
              <i class="ti ti-plug me-1"></i>Terminal de Equipo
            </button>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-header">
          <h3 class="card-title">
            <i class="ti ti-desktop me-2 text-primary"></i>Listado de Estaciones
          </h3>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tablaEstaciones" class="table table-vcenter table-mobile-md card-table table-sm">
              <thead>
                <tr>
                  <th>Estación</th>
                  <th>IP Asignada</th>
                  <th>Código Anydesk</th>
                  <th>Contraseña Anydesk</th>
                  <th>Equipos</th>
                  <th>Fecha Creación</th>
                  <th class="d-none d-sm-table-cell">Registrado Por</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $estaciones = EstacionController::ctrMostrarEstacion(null, null);
                if (!is_array($estaciones)) $estaciones = [];
                foreach ($estaciones as $e) {
                    $fecha = isset($e["fechaCreacion"])
                        ? ($e["fechaCreacion"] instanceof DateTime
                            ? $e["fechaCreacion"]->format("d/m/Y")
                            : date("d/m/Y", strtotime($e["fechaCreacion"])))
                        : "Sin fecha";

                    $ipBadge = !empty($e["ipAddress"])
                        ? '<span class="badge bg-primary-lt text-primary font-monospace">'
                          . htmlspecialchars($e["ipAddress"]) . '</span>'
                        : '<span class="text-muted small">—</span>';

                    $anydesk = !empty($e["codigoAnydesk"])
                        ? '<span class="badge badge-outline text-muted font-monospace">'
                          . htmlspecialchars($e["codigoAnydesk"]) . '</span>'
                        : '<span class="text-muted small">—</span>';

                    $passAnydesk = !empty($e["contrasenaAnydesk"])
                        ? '<span class="badge badge-outline text-muted">••••••</span>'
                        : '<span class="text-muted small">—</span>';

                    $total = intval($e["totalEquipos"] ?? 0);
                    $equiposBadge = $total > 0
                        ? '<span class="badge bg-success-lt text-success">' . $total . ' ítem(s)</span>'
                        : '<span class="badge badge-outline text-muted">Sin equipos</span>';

                    $nombre = htmlspecialchars($e["nombreEstacion"] ?? '', ENT_QUOTES, 'UTF-8');

                    echo '
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-desktop text-primary fs-3"></i>
                          <span class="fw-medium">' . $nombre . '</span>
                        </div>
                      </td>
                      <td>' . $ipBadge . '</td>
                      <td>' . $anydesk . '</td>
                      <td>' . $passAnydesk . '</td>
                      <td>' . $equiposBadge . '</td>
                      <td class="small text-muted">' . $fecha . '</td>
                      <td class="d-none d-sm-table-cell">
                        <span class="badge badge-outline text-muted fw-normal">ID: ' . $e["idUsuarioRegistro"] . '</span>
                      </td>
                      <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                          <button type="button"
                            class="btn btn-sm btn-icon btn-outline-info btnVerDetalle"
                            data-id="' . $e["idEstacion"] . '"
                            data-nombre="' . $nombre . '"
                            title="Ver equipos y software">
                            <i class="ti ti-eye"></i>
                          </button>
                          <a href="?module=inventario&action=editarEstacion&id=' . $e["idEstacion"] . '"
                             class="btn btn-sm btn-icon btn-outline-primary" title="Editar estación">
                            <i class="ti ti-edit"></i>
                          </a>
                          <button type="button"
                            class="btn btn-sm btn-icon btn-outline-danger btnEliminarEstacion"
                            data-id="' . $e["idEstacion"] . '"
                            data-nombre="' . $nombre . '"
                            title="Eliminar estación">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>


<!-- ════════ MODAL VER DETALLE ════════ -->
<style>
.detalle-seccion{border:1px solid var(--tblr-border-color,#e6ebf1);border-radius:.5rem;overflow:hidden;margin-bottom:.75rem}
.detalle-seccion-header{display:flex;align-items:center;gap:.5rem;padding:.5rem .9rem;background:var(--tblr-bg-surface-secondary,#f8fafc);border-bottom:1px solid var(--tblr-border-color,#e6ebf1);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
.detalle-item{display:flex;align-items:center;gap:.6rem;padding:.45rem .9rem;border-bottom:1px solid var(--tblr-border-color-light,#f0f3f8)}
.detalle-item:last-child{border-bottom:none}
.detalle-item-icon{width:28px;height:28px;border-radius:.25rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem}
.detalle-vacio{padding:.75rem .9rem;color:#9ca3af;font-style:italic;font-size:.82rem;text-align:center}
</style>

<div class="modal modal-blur fade" id="modalVerDetalle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-eye fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Detalle de Estación</h5>
            <small class="text-muted fw-semibold" id="detalleNombreEstacion"></small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <div id="detalleContenido">
          <div class="text-center py-4 text-muted">
            <span class="spinner-border spinner-border-sm me-2"></span>Cargando...
          </div>
        </div>
      </div>
      <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ════════ MODAL CONFIRMAR ELIMINACIÓN ════════ -->
<div class="modal modal-blur fade" id="modalConfirmarEliminarEstacion" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2 text-danger">
          <i class="ti ti-alert-triangle"></i> Confirmar eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-1">¿Estás seguro de que deseas eliminar la estación:</p>
        <p class="fw-bold mb-0" id="eliminarNombreEstacion"></p>
        <p class="text-muted small mt-2 mb-0">Esta acción es reversible solo desde la base de datos.</p>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminarEstacion">
          <i class="ti ti-trash me-1"></i>Sí, eliminar
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ════════ MODAL TERMINAL DE EQUIPO ════════ -->
<div class="modal modal-blur fade" id="modalTerminalEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-success-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:#2fb344;flex-shrink:0">
            <i class="ti ti-plug fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Nueva Terminal de Equipo</h5>
            <small class="text-muted">Estación simple — solo nombre y equipo</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTerminal" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nombre de la Terminal <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="terminalNombre" id="terminalNombre"
                   placeholder="Ej: IMPRESORA-ADM-01, PROYECTOR-SALA-A..."
                   style="text-transform:uppercase">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Equipo disponible <span class="text-danger">*</span></label>
            <select id="terminalIdEquipoSelect" style="display:none">
              <option value="">Seleccionar equipo...</option>
            </select>
            <input type="hidden" name="terminalIdEquipo" id="terminalIdEquipo">
            <div id="terminalEquipoInfo" style="display:none" class="mt-2 border rounded p-2 small">
              <div class="row g-1">
                <div class="col-6">
                  <div class="text-muted">Cód. Patrimonial</div>
                  <div class="fw-semibold" id="terminalEquipoCp">—</div>
                </div>
                <div class="col-6">
                  <div class="text-muted">N° Serie</div>
                  <div class="fw-semibold" id="terminalEquipoSerie">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color)">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-success" id="btnGuardarTerminal" disabled>
            <i class="ti ti-plug me-1"></i>Crear Terminal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<div id="toastContainerEstaciones" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
const AJAX_EST_TABLA = 'modules/inventario/ajax/estaciones.ajax.php';

function escHtmlEst(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function mostrarToastEst(tipo, mensaje) {
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

document.addEventListener('DOMContentLoaded', function () {

    /* ── DataTables ── */
    if ($.fn.DataTable.isDataTable('#tablaEstaciones')) $('#tablaEstaciones').DataTable().destroy();
    $('#tablaEstaciones').DataTable({
        responsive: true, pageLength: 10, autoWidth: false,
        dom: `<'card-body border-bottom py-3'<'row g-3 align-items-center'<'col-12 col-md-auto'l><'col-12 col-md-auto ms-auto'<'d-flex gap-2'Bf>>>>tr<'card-footer d-flex align-items-center py-2'<'m-0 text-muted small'i><'pagination m-0 ms-auto'p>>`,
        buttons: [
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-outline-success btn-sm m-0' },
            { extend: 'pdfHtml5',   text: '<i class="ti ti-file-description"></i>',  className: 'btn btn-outline-danger btn-sm m-0' }
        ],
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm m-0').attr('placeholder', 'Buscar estación...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dt-buttons').addClass('d-flex gap-2 m-0');
        }
    });

    /* ── Ver Detalle ── */
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
            const fd = new FormData();
            fd.append('verDetalle', idEst);
            const res  = await fetch(AJAX_EST_TABLA, { method:'POST', body:fd });
            const data = await res.json();
            renderDetalleEst(data);
        } catch {
            document.getElementById('detalleContenido').innerHTML =
                '<div class="text-danger text-center py-3">Error al cargar detalle.</div>';
        }
    });

    function renderDetalleEst(data) {
        const cont = document.getElementById('detalleContenido');
        function seccion(titulo, icono, color, items) {
            const rows = items.length ? items.map(it => `
                <div class="detalle-item">
                    <div class="detalle-item-icon" style="background:${color}15;color:${color}">
                        <i class="ti ${escHtmlEst(it.iconoActivo??'ti-package')}"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold small text-truncate">${escHtmlEst(it.nombreActivo??'')}</div>
                        <div class="d-flex gap-2 flex-wrap">
                            ${it.codigoPatrimonial ? `<span class="text-muted small">CP: ${escHtmlEst(it.codigoPatrimonial)}</span>` : ''}
                            ${it.numeroSerie ? `<span class="text-muted small">S/N: ${escHtmlEst(it.numeroSerie)}</span>` : ''}
                        </div>
                    </div>
                </div>`).join('')
                : '<div class="detalle-vacio">Sin ítems</div>';
            return `<div class="detalle-seccion">
                <div class="detalle-seccion-header" style="color:${color}">
                    <i class="ti ${icono}"></i>${titulo}
                    <span class="badge ms-auto" style="background:${color}15;color:${color}">${items.length}</span>
                </div>${rows}</div>`;
        }
        let principalHtml = '';
        const pList = data.principal ?? [];
        if (pList.length) {
            const p = pList[0];
            const comps = data.componentesPrincipal ?? [];
            principalHtml = `
            <div class="detalle-seccion">
                <div class="detalle-seccion-header" style="color:#0054a6">
                    <i class="ti ti-cpu"></i>EQUIPO PRINCIPAL
                    <span class="badge ms-auto" style="background:#e7f0ff;color:#0054a6">1</span>
                </div>
                <div class="detalle-item">
                    <div class="detalle-item-icon" style="background:#e7f0ff;color:#0054a6">
                        <i class="ti ${escHtmlEst(p.iconoActivo??'ti-package')}"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold small">${escHtmlEst(p.nombreActivo??'')}</div>
                        <div class="d-flex gap-2">
                            ${p.codigoPatrimonial ? `<span class="text-muted small">CP: ${escHtmlEst(p.codigoPatrimonial)}</span>` : ''}
                            ${p.numeroSerie ? `<span class="text-muted small">S/N: ${escHtmlEst(p.numeroSerie)}</span>` : ''}
                        </div>
                    </div>
                </div>
                ${comps.length ? `
                <div style="padding:.4rem .9rem .4rem 2.8rem;background:#f8fafc;border-top:1px solid #f0f3f8">
                    <div class="text-muted small fw-semibold mb-1" style="color:#e65100">
                        <i class="ti ti-git-branch me-1"></i>Componentes internos (${comps.length})
                    </div>
                    ${comps.map(c=>`
                    <div class="detalle-item" style="padding-left:.5rem">
                        <div class="detalle-item-icon" style="background:#fff3e0;color:#e65100">
                            <i class="ti ${escHtmlEst(c.iconoActivo??'ti-package')}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">${escHtmlEst(c.nombreActivo??'')}</div>
                            <div class="d-flex gap-2">
                                ${c.codigoPatrimonial?`<span class="text-muted small">CP: ${escHtmlEst(c.codigoPatrimonial)}</span>`:''}
                                ${c.numeroSerie?`<span class="text-muted small">S/N: ${escHtmlEst(c.numeroSerie)}</span>`:''}
                            </div>
                        </div>
                    </div>`).join('')}
                </div>` : ''}
            </div>`;
        } else {
            principalHtml = `<div class="detalle-seccion">
                <div class="detalle-seccion-header" style="color:#0054a6"><i class="ti ti-cpu"></i>EQUIPO PRINCIPAL</div>
                <div class="detalle-vacio">Sin equipo principal asignado</div>
            </div>`;
        }
        cont.innerHTML = principalHtml
            + seccion('PERIFÉRICOS', 'ti-devices', '#2e7d32', data.perifericos ?? [])
            + seccion('SOFTWARE',    'ti-brand-windows', '#6a1b9a', data.software ?? []);
    }

    /* ── Eliminar Estación ── */
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btnEliminarEstacion');
        if (!btn) return;
        const id     = btn.getAttribute('data-id');
        const nombre = btn.getAttribute('data-nombre') || 'esta estación';
        document.getElementById('eliminarNombreEstacion').textContent = nombre;
        document.getElementById('confirmarEliminarEstacion').setAttribute('data-id', id);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarEliminarEstacion')).show();
    });

    const btnConfirmar = document.getElementById('confirmarEliminarEstacion');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const fd = new FormData();
            fd.append('eliminarIdEstacion', id);

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';

            fetch(AJAX_EST_TABLA, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(json => {
                    bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminarEstacion')).hide();
                    if (json.resultado === 'ok') {
                        mostrarToastEst('success', json.mensaje || 'Estación eliminada correctamente.');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarToastEst('error', json.mensaje || 'No se pudo eliminar.');
                    }
                })
                .catch(() => mostrarToastEst('error', 'Error al comunicarse con el servidor.'))
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-trash me-1"></i>Sí, eliminar';
                });
        });
    }
});

/* ════════════════════════════════════════
   TERMINAL DE EQUIPO
════════════════════════════════════════ */
(function() {
    const AJAX_EST = 'modules/inventario/ajax/estaciones.ajax.php';

    function crearCSTerminal(selectId) {
        const sel = document.getElementById(selectId);
        if (!sel) return null;
        sel.style.display = 'none';
        const prev = document.getElementById('cswrap_' + selectId);
        if (prev) prev.remove();
        const wrap = document.createElement('div');
        wrap.id = 'cswrap_' + selectId;
        wrap.style.cssText = 'position:relative;width:100%;font-size:.875rem';
        wrap.innerHTML = `
            <div id="tcs_display_${selectId}" tabindex="0" style="
                display:flex;align-items:center;justify-content:space-between;gap:.5rem;
                padding:.375rem .75rem;min-height:36px;background:#fff;
                border:1px solid var(--tblr-border-color,#d0d5dd);
                border-radius:var(--tblr-border-radius,.375rem);
                cursor:pointer;outline:none;transition:border-color .15s">
                <span id="tcs_text_${selectId}" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#9ca3af">Seleccionar equipo...</span>
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" style="flex-shrink:0;color:#6c757d">
                    <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>`;
        sel.parentNode.insertBefore(wrap, sel);
        const panel = document.createElement('div');
        panel.id = 'tcs_panel_' + selectId;
        panel.style.cssText = 'display:none;position:fixed;z-index:99999;background:#fff;border:1px solid var(--tblr-border-color,#d0d5dd);border-radius:.375rem;box-shadow:0 4px 20px rgba(0,0,0,.15);overflow:hidden';
        panel.innerHTML = `
            <div style="display:flex;align-items:center;gap:.4rem;padding:.4rem .65rem;border-bottom:1px solid #e6ebf1;background:#f8fafc">
                <input id="tcs_search_${selectId}" type="text" placeholder="Buscar..."
                       style="border:none;outline:none;background:transparent;font-size:.8rem;width:100%;color:#374151">
            </div>
            <ul id="tcs_list_${selectId}" style="list-style:none;margin:0;padding:.2rem 0;max-height:200px;overflow-y:auto"></ul>`;
        document.body.appendChild(panel);

        const display = document.getElementById('tcs_display_' + selectId);
        const textEl  = document.getElementById('tcs_text_'    + selectId);
        const search  = document.getElementById('tcs_search_'  + selectId);
        const list    = document.getElementById('tcs_list_'    + selectId);
        let optsArr = []; let isOpen = false;

        function abrir() {
            const r = wrap.getBoundingClientRect();
            panel.style.width = r.width + 'px';
            panel.style.left  = r.left  + 'px';
            panel.style.top   = (r.bottom + 3) + 'px';
            panel.style.display = 'block';
            search.value = ''; render(optsArr); search.focus(); isOpen = true;
        }
        function cerrar() { panel.style.display = 'none'; isOpen = false; }
        function render(items) {
            list.innerHTML = '';
            if (!items.length) { list.innerHTML = '<li style="padding:.38rem .75rem;color:#9ca3af;font-style:italic">Sin resultados</li>'; return; }
            items.forEach(o => {
                const li = document.createElement('li');
                li.textContent = o.label;
                li.style.cssText = 'padding:.38rem .75rem;cursor:pointer;font-size:.85rem;transition:background .1s';
                li.addEventListener('mouseover', () => { li.style.background='#eff6ff'; });
                li.addEventListener('mouseout',  () => { li.style.background=''; });
                li.addEventListener('mousedown', e => { e.preventDefault(); select(o.value, o.label); cerrar(); });
                list.appendChild(li);
            });
        }
        function select(value, label) {
            sel.value = value;
            sel.dispatchEvent(new Event('change', { bubbles:true }));
            textEl.textContent = label || 'Seleccionar equipo...';
            textEl.style.color = value ? '#374151' : '#9ca3af';
        }
        display.addEventListener('mousedown', e => { e.preventDefault(); isOpen ? cerrar() : abrir(); });
        document.addEventListener('mousedown', e => { if (!wrap.contains(e.target) && !panel.contains(e.target)) cerrar(); });
        search.addEventListener('input', function() { const q=this.value.toLowerCase(); render(q?optsArr.filter(o=>o.label.toLowerCase().includes(q)):optsArr); });
        search.addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();e.stopPropagation();} });

        const _data = {};
        return {
            _data,
            setOptions(arr) {
                optsArr = arr; sel.innerHTML = '';
                arr.forEach(o => { const opt=document.createElement('option'); opt.value=o.value; opt.textContent=o.label; sel.appendChild(opt); });
                textEl.textContent = 'Seleccionar equipo...'; textEl.style.color = '#9ca3af'; sel.value = '';
            },
            getValue() { return sel.value; }
        };
    }

    let csTerminal = null;

    document.getElementById('btnNuevaTerminal')?.addEventListener('click', async function() {
        document.getElementById('formTerminal').reset();
        document.getElementById('terminalIdEquipo').value = '';
        document.getElementById('terminalEquipoInfo').style.display = 'none';
        document.getElementById('btnGuardarTerminal').disabled = true;
        csTerminal = crearCSTerminal('terminalIdEquipoSelect');
        try {
            const res  = await fetch(AJAX_EST + '?equiposDisponibles=1');
            const data = await res.json();
            const ops  = [{ value:'', label:'Seleccionar equipo...' }];
            data.forEach(eq => { ops.push({ value:String(eq.idEquipo), label:eq.label }); csTerminal._data[String(eq.idEquipo)] = eq; });
            csTerminal.setOptions(ops);
        } catch(e) { console.error(e); }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTerminalEquipo')).show();
    });

    document.getElementById('terminalIdEquipoSelect')?.addEventListener('change', function() {
        const val = this.value;
        document.getElementById('terminalIdEquipo').value = val;
        if (val && csTerminal?._data[val]) {
            const eq = csTerminal._data[val];
            document.getElementById('terminalEquipoCp').textContent    = eq.codigoPatrimonial || '—';
            document.getElementById('terminalEquipoSerie').textContent = eq.numeroSerie       || '—';
            document.getElementById('terminalEquipoInfo').style.display = 'block';
        } else {
            document.getElementById('terminalEquipoInfo').style.display = 'none';
        }
        document.getElementById('btnGuardarTerminal').disabled = !val || !document.getElementById('terminalNombre').value.trim();
    });

    document.getElementById('terminalNombre')?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
        document.getElementById('btnGuardarTerminal').disabled = !this.value.trim() || !document.getElementById('terminalIdEquipo').value;
    });

    document.getElementById('formTerminal')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardarTerminal');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:13px;height:13px"></span>Creando...';
        try {
            const resp = await fetch(AJAX_EST, { method:'POST', body: new FormData(this) });
            const data = await resp.json();
            const r = (data.resultado ?? '').trim();
            const m = (data.mensaje   ?? '').trim();
            if (r === 'ok') {
                mostrarToastEst('success', m || 'Terminal creada correctamente.');
                bootstrap.Modal.getInstance(document.getElementById('modalTerminalEquipo')).hide();
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarToastEst('error', m || 'Error al crear la terminal.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-plug me-1"></i>Crear Terminal';
            }
        } catch {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-plug me-1"></i>Crear Terminal';
        }
    });
})();
</script>
