<body>
<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
?>

<style>
.badge-disponible { background: #d1fae5; color: #065f46; }
.badge-asignada   { background: #dbeafe; color: #1e40af; }
</style>

<div class="page">

  <header class="navbar navbar-expand-md navbar-light d-print-none shadow-sm">
    <div class="container-xl">
      <h1 class="navbar-brand">
        <i class="ti ti-package me-2 text-primary"></i>Inventario TI
      </h1>
    </div>
  </header>

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
                if ($estaciones && $estaciones !== "error") {
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

                        echo '
                        <tr>
                          <td>
                            <div class="d-flex align-items-center gap-2">
                              <i class="ti ti-desktop text-primary fs-3"></i>
                              <span class="fw-medium">' . htmlspecialchars($e["nombreEstacion"] ?? '') . '</span>
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
                                data-nombre="' . htmlspecialchars($e["nombreEstacion"] ?? '') . '"
                                title="Ver equipos y software">
                                <i class="ti ti-eye"></i>
                              </button>
                              <a href="?module=inventario&action=editarEstacion&id=' . $e["idEstacion"] . '"
                                 class="btn btn-sm btn-icon btn-outline-primary" title="Editar estación">
                                <i class="ti ti-edit"></i>
                              </a>
                            </div>
                          </td>
                        </tr>';
                    }
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

<div id="toastContainerEstaciones" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
const AJAX_EST_TABLA = 'modules/inventario/ajax/estaciones.ajax.php';

function escHtmlEst(s) {
    return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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

        // Equipo principal + componentes internos
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
});
</script>
