<?php
/**
 * html/autorizaciones.php — Vista de Solicitudes Pendientes
 * Renamed from: pendientes.php
 * Solo accesible por usuarios con rol Autorizador o Administrador.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
if (!$es_autorizador_o_admin) {
    echo '<div class="alert alert-danger m-4">Acceso restringido. Se requiere rol Autorizador o Administrador.</div>';
    return;
}
$AJAX = BASE_URL . '/modules/salas/controllers/ajax_handler.php';
?>

<!-- =========================================================
     DEPENDENCIAS CSS
     ========================================================= -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- =========================================================
     LAYOUT
     ========================================================= -->
<div class="page-wrapper">
  <div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            <i class="ti ti-clock-hour4 me-2 text-warning"></i>Solicitudes Pendientes
            <span class="badge bg-warning text-dark ms-2" id="badge-total-pend">—</span>
          </h2>
          <div class="text-secondary mt-1">Solicitudes de reserva en espera de autorización</div>
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-secondary btn-sm me-2" onclick="cargarPendientes()">
            <i class="ti ti-refresh me-1"></i>Actualizar
          </button>
          <a href="?module=salas" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Volver al Calendario
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="page-body">
    <div class="container-xl">

      <div class="card">
        <div class="card-header">
          <h4 class="card-title mb-0">
            <i class="ti ti-table me-2 text-secondary"></i>Solicitudes en cola
          </h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tabla-pendientes-pag" class="table table-vcenter table-striped table-sm w-100">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Solicitante</th>
                  <th>Sede / Sala</th>
                  <th>Fecha</th>
                  <th>Horario</th>
                  <th>Motivo</th>
                  <th>Enviado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbody-pendientes-pag">
                <tr><td colspan="8" class="text-center text-muted py-4">
                  <div class="spinner-border spinner-border-sm me-2 text-warning"></div>Cargando...
                </td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- /container -->
  </div><!-- /page-body -->
</div><!-- /page-wrapper -->


<!-- =========================================================
     MODAL — Detalle de reserva
     ========================================================= -->
<div class="modal fade" id="modal-det-pend" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#1a2940; color:#fff;">
        <h5 class="modal-title">
          <i class="ti ti-file-description me-2"></i>Detalle de Reserva
          <span id="det-pend-id" class="ms-1 small opacity-75"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="det-pend-body">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
      <div class="modal-footer" id="det-pend-footer"></div>
    </div>
  </div>
</div>


<!-- =========================================================
     MODAL — Rechazar solicitud
     ========================================================= -->
<div class="modal fade" id="modal-rechazar-pend" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">
          <i class="ti ti-circle-x me-2"></i>Rechazar Solicitud
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="rechazar-pend-id">
        <label class="form-label">Observación (opcional)</label>
        <textarea id="rechazar-pend-obs" class="form-control" rows="3"
                  maxlength="500" placeholder="Justificación del rechazo..."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger ms-auto" onclick="confirmarRechazo()">
          <i class="ti ti-circle-x me-1"></i>Rechazar
        </button>
      </div>
    </div>
  </div>
</div>


<!-- =========================================================
     SCRIPTS (CDN) + Variable PHP → JS + archivo JS externo
     ========================================================= -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Punto único de inyección PHP → JS -->
<script>
var AJAX_PEND = '<?= htmlspecialchars($AJAX, ENT_QUOTES) ?>';
</script>

<!-- Shared (código reutilizable) -->
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/api.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/alerts.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/utils.js"></script>

<!-- Pendientes para Autorizadores -->
<script src="<?= BASE_URL ?>/modules/salas/views/js/roles/autorizador/autorizaciones.js?v=<?= filemtime(__DIR__ . '/../../../js/roles/autorizador/autorizaciones.js') ?>"></script>
