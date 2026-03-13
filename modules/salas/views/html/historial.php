<?php
/**
 * html/historial.php — Vista de Historial de Reservas
 * Renamed from: historial.php
 * Solo accesible por usuarios con rol Autorizador o Administrador.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
if (!$es_admin) {
    echo '<div class="alert alert-danger m-4">Acceso restringido. Se requiere rol Administrador.</div>';
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
            <i class="ti ti-history me-2 text-primary"></i>Historial de Reservas
          </h2>
          <div class="text-secondary mt-1">Registro completo de reservas aprobadas, rechazadas y canceladas</div>
        </div>
        <div class="col-auto d-flex gap-2">
        
          <a href="?module=salas" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Volver al Calendario
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="page-body">
    <div class="container-xl">

      <!-- Filtros -->
      <div class="card mb-3">
        <div class="card-body py-2">
          <div class="row g-2 align-items-end">
            <div class="col-sm-2">
              <label class="form-label form-label-sm mb-1">Desde</label>
              <input type="date" id="hist-desde" class="form-control form-control-sm">
            </div>
            <div class="col-sm-2">
              <label class="form-label form-label-sm mb-1">Hasta</label>
              <input type="date" id="hist-hasta" class="form-control form-control-sm">
            </div>
            <div class="col-sm-3">
              <label class="form-label form-label-sm mb-1">Sede</label>
              <select id="hist-sede" class="form-select form-select-sm">
                <option value="">— Todas las sedes —</option>
              </select>
            </div>
            <div class="col-sm-3">
              <label class="form-label form-label-sm mb-1">Estado</label>
              <select id="hist-estado" class="form-select form-select-sm">
                <option value="">— Todos los estados —</option>
                <option value="APROBADA">Aprobada</option>
                <option value="RECHAZADA">Rechazada</option>
                <option value="CANCELADA">Cancelada</option>
              </select>
            </div>
            <div class="col-sm-2">
              <button class="btn btn-primary btn-sm w-100" onclick="cargarHistorial()">
                <i class="ti ti-filter me-1"></i>Filtrar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card">
        <div class="card-header">
          <h4 class="card-title mb-0"><i class="ti ti-table me-2 text-secondary"></i>Resultados</h4>          <div class="card-options">
            <button class="btn btn-sm btn-outline-primary d-print-none" onclick="imprimirHistorial()">
              <i class="ti ti-printer me-1"></i>Imprimir tabla
            </button>
          </div>        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tabla-historial" class="table table-vcenter table-striped table-sm w-100">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Solicitante</th>
                  <th>Sede / Sala</th>
                  <th>Fecha</th>
                  <th>Horario</th>
                  <th>Motivo</th>
                  <th>Estado</th>
                  <th>Autorizador</th>
                  <th>F. Autorización</th>
                  <th class="text-center">Detalle</th>
                </tr>
              </thead>
              <tbody id="tbody-historial">
                <tr><td colspan="10" class="text-center text-muted py-4">
                  <div class="spinner-border spinner-border-sm me-2 text-secondary"></div>Cargando historial...
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
     MODAL — Detalle de Reserva
     ========================================================= -->
<div class="modal fade" id="modal-detalle-hist" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#1a2940; color:#fff;">
        <h5 class="modal-title"><i class="ti ti-file-description me-2"></i>Detalle de Reserva</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detalle-hist-body">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
var AJAX_HIST = '<?= htmlspecialchars($AJAX, ENT_QUOTES) ?>';
</script>

<script src="<?= BASE_URL ?>/modules/salas/views/js/historial.js"></script>
