<?php
/**
 * html/mis-reservas.php — Vista dedicada "Mis Reservas"
 * Accesible por cualquier usuario autenticado.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
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
            <i class="ti ti-calendar-check me-2 text-primary"></i>Mis Reservas
          </h2>
          <div class="text-secondary mt-1">Solicitudes de reserva de sala realizadas por usted</div>
        </div>
        <div class="col-auto d-flex gap-2">
          <button class="btn btn-primary btn-sm" onclick="window.location.href='?module=salas&action=nueva-solicitud'" style="display:none;" id="btn-nueva-top">
            <i class="ti ti-plus me-1"></i>Nueva Solicitud
          </button>
          <button class="btn btn-outline-secondary btn-sm" onclick="cargarMisReservas()">
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
            <i class="ti ti-table me-2 text-secondary"></i>Listado de solicitudes
          </h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tabla-mis-reservas" class="table table-vcenter table-striped table-sm w-100">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Fecha</th>
                  <th>Horario</th>
                  <th>Sala</th>
                  <th>Sede</th>
                  <th>Motivo</th>
                  <th>Estado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbody-mis-reservas">
                <tr><td colspan="8" class="text-center text-muted py-4">
                  <div class="spinner-border spinner-border-sm me-2 text-primary"></div>Cargando...
                </td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- =========================================================
     MODAL — Detalle de Reserva
     ========================================================= -->
<div class="modal modal-blur fade" id="modal-detalle-mr" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background:#1a2940; color:#fff;">
        <h5 class="modal-title">
          <i class="ti ti-calendar-check me-2"></i>Detalle de Reserva
          <span id="mr-detalle-id" class="ms-1 small opacity-75"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="mr-detalle-body">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


<!-- =========================================================
     MODAL — Editar Reserva
     ========================================================= -->
<div class="modal modal-blur fade" id="modal-editar-mr" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-edit me-2"></i>Editar Reserva</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-editar-mr" novalidate>
        <div class="modal-body">
          <input type="hidden" id="edit-id-reserva" name="id_reserva">

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label required">Sede</label>
              <select id="edit-sede" class="form-select" required>
                <option value="">— Seleccione sede —</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label required">Sala</label>
              <select id="edit-sala" name="id_sala" class="form-select" required disabled>
                <option value="">— Seleccione sala —</option>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-4">
              <label class="form-label required">Fecha</label>
              <input type="date" id="edit-fecha" name="fecha" class="form-control" required
                     min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label required">Hora Inicio</label>
              <input type="time" id="edit-hora-inicio" name="hora_inicio" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label required">Hora Fin</label>
              <input type="time" id="edit-hora-fin" name="hora_fin" class="form-control" required>
            </div>
          </div>

          <div id="edit-disponibilidad" class="mb-2" style="display:none;"></div>

          <div class="mb-3" id="edit-equipos-section" style="display:none;">
            <label class="form-label">Equipos AV Requeridos</label>
            <div id="edit-equipos-lista" class="row g-2"></div>
          </div>

          <div class="mb-3">
            <label class="form-label required">Motivo</label>
            <textarea id="edit-motivo" name="motivo" class="form-control" rows="3"
                      maxlength="500" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btn-guardar-editar" class="btn btn-warning ms-auto">
            <i class="ti ti-device-floppy me-1"></i>Guardar Cambios
          </button>
        </div>
      </form>
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

<script>
var AJAX = '<?= htmlspecialchars($AJAX, ENT_QUOTES) ?>';
</script>

<!-- Shared (código reutilizable) -->
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/api.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/alerts.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/utils.js"></script>

<!-- Mis Reservas para Usuarios -->
<script src="<?= BASE_URL ?>/modules/salas/views/js/roles/usuario/mis-reservas.js?v=<?= filemtime(__DIR__ . '/../../../js/roles/usuario/mis-reservas.js') ?>"></script>
