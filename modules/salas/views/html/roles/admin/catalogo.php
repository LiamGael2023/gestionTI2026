<?php
/**
 * html/catalogo.php — Vista de Administración del módulo Salas
 * Renamed from: admin.php
 * Solo accesible por usuarios con rol Administrador.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
if (!$es_admin) {
    echo '<div class="alert alert-danger m-4">Acceso restringido al administrador.</div>';
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
            <i class="ti ti-settings me-2 text-primary"></i>Administración — Salas de Reunión
          </h2>
          <div class="text-secondary mt-1">Gestión de Sedes, Salas y Equipos Audiovisuales</div>
        </div>
        <div class="col-auto">
          <a href="?module=salas" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Volver al Calendario
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="page-body">
    <div class="container-xl">

      <!-- Tabs de navegación -->
      <div class="card">
        <div class="card-header">
          <ul class="nav nav-tabs card-header-tabs" id="admin-tabs">
            <li class="nav-item">
              <a class="nav-link active" data-bs-toggle="tab" href="#tab-sedes" id="link-sedes">
                <i class="ti ti-map-pin me-1"></i>Sedes
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#tab-salas" id="link-salas">
                <i class="ti ti-door me-1"></i>Salas
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#tab-equipos" id="link-equipos">
                <i class="ti ti-device-projector me-1"></i>Equipos AV
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body">
          <div class="tab-content">

            <!-- TAB: SEDES -->
            <div class="tab-pane fade show active" id="tab-sedes">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Gestión de Sedes</h5>
                <button class="btn btn-primary btn-sm" onclick="abrirModalSede()">
                  <i class="ti ti-plus me-1"></i>Nueva Sede
                </button>
              </div>
              <div class="table-responsive">
                <table id="tabla-admin-sedes" class="table table-vcenter table-striped table-sm" style="width:100%">
                  <thead>
                    <tr>
                      <th>#</th><th>Nombre</th><th>Dirección</th>
                      <th>Estado</th><th class="text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tbody-admin-sedes">
                    <tr><td colspan="5" class="text-center text-muted py-3">Cargando...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- TAB: SALAS -->
            <div class="tab-pane fade" id="tab-salas">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Gestión de Salas</h5>
                <button class="btn btn-primary btn-sm" onclick="abrirModalSala()">
                  <i class="ti ti-plus me-1"></i>Nueva Sala
                </button>
              </div>
              <div class="table-responsive">
                <table id="tabla-admin-salas" class="table table-vcenter table-striped table-sm" style="width:100%">
                  <thead>
                    <tr>
                      <th>#</th><th>Sede</th><th>Sala</th><th>Capacidad</th>
                      <th>Descripción</th><th>Estado</th><th class="text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tbody-admin-salas">
                    <tr><td colspan="7" class="text-center text-muted py-3">Cargando...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- TAB: EQUIPOS -->
            <div class="tab-pane fade" id="tab-equipos">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Gestión de Equipos Audiovisuales</h5>
                <button class="btn btn-primary btn-sm" onclick="abrirModalEquipo()">
                  <i class="ti ti-plus me-1"></i>Nuevo Equipo
                </button>
              </div>
              <div class="table-responsive">
                <table id="tabla-admin-equipos" class="table table-vcenter table-striped table-sm" style="width:100%">
                  <thead>
                    <tr>
                      <th>#</th><th>Sede / Sala</th><th>Equipo</th><th>Tipo</th>
                      <th>Descripción</th><th>Estado</th><th class="text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tbody-admin-equipos">
                    <tr><td colspan="7" class="text-center text-muted py-3">Cargando...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div><!-- /tab-content -->
        </div><!-- /card-body -->
      </div><!-- /card -->

    </div><!-- /container-xl -->
  </div><!-- /page-body -->
</div><!-- /page-wrapper -->


<!-- =========================================================
     MODAL — Sede
     ========================================================= -->
<div class="modal modal-blur fade" id="modal-sede" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-sede-titulo">Nueva Sede</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-sede">
        <div class="modal-body">
          <input type="hidden" id="sede-id" name="id">
          <div class="mb-3">
            <label class="form-label required">Nombre</label>
            <input type="text" id="sede-nombre" name="nombre" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Dirección</label>
            <input type="text" id="sede-direccion" name="direccion" class="form-control" maxlength="250">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary ms-auto"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================
     MODAL — Sala
     ========================================================= -->
<div class="modal modal-blur fade" id="modal-sala" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-sala-titulo">Nueva Sala</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-sala">
        <div class="modal-body">
          <input type="hidden" id="sala-id" name="id_sala">
          <div class="mb-3">
            <label class="form-label required">Sede</label>
            <select id="sala-sede" name="id_sede" class="form-select" required>
              <option value="">— Seleccione sede —</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label required">Nombre de la Sala</label>
            <input type="text" id="sala-nombre" name="nombre" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label class="form-label required">Capacidad (personas)</label>
            <input type="number" id="sala-capacidad" name="capacidad" class="form-control" min="1" max="500" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea id="sala-descripcion" name="descripcion" class="form-control" rows="2" maxlength="500"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Fotografía de la Sala</label>
            <div id="sala-foto-actual-wrap" class="mb-2 text-center d-none">
              <img id="sala-foto-actual" src="" alt="Foto actual"
                   style="max-height:160px;max-width:100%;border-radius:8px;border:1px solid #dee2e6;object-fit:cover;">
            </div>
            <input type="file" id="sala-foto-file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
            <small class="form-hint text-muted">JPG, PNG o WebP — máx. 5 MB. Si ya hay foto, será reemplazada.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary ms-auto"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================================================
     MODAL — Equipo AV
     ========================================================= -->
<div class="modal modal-blur fade" id="modal-equipo" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-equipo-titulo">Nuevo Equipo AV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-equipo">
        <div class="modal-body">
          <input type="hidden" id="equipo-id" name="id_equipo">
          <div class="mb-3">
            <label class="form-label required">Sala</label>
            <select id="equipo-sala" name="id_sala" class="form-select" required>
              <option value="">— Seleccione sala —</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label required">Nombre del Equipo</label>
            <input type="text" id="equipo-nombre" name="nombre" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label class="form-label required">Tipo</label>
            <select id="equipo-tipo" name="tipo" class="form-select" required>
              <option value="">— Seleccione tipo —</option>
              <option value="Proyector">Proyector</option>
              <option value="Computadora">Computadora</option>
              <option value="Tablet">Tablet</option>
              <option value="Televisor">Televisor</option>
              <option value="Pantalla">Pantalla</option>
              <option value="Micrófono">Micrófono</option>
              <option value="Videoconferencia">Videoconferencia</option>
              <option value="Pizarra Digital">Pizarra Digital</option>
              <option value="Sistema de Sonido">Sistema de Sonido</option>
              <option value="Puntero Láser">Puntero Láser</option>
              <option value="Otro">Otro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción / Especificaciones</label>
            <textarea id="equipo-descripcion" name="descripcion" class="form-control" rows="2" maxlength="500"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary ms-auto"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- =========================================================
     MODAL — Equipos de la Sala
     ========================================================= -->
<div class="modal modal-blur fade" id="modal-equipos-sala" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#1a2940;color:#fff;">
        <h5 class="modal-title"><i class="ti ti-device-tv me-2"></i>Equipos AV asignados: <span id="modal-equipos-sala-nombre"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modal-equipos-sala-body">
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
var AJAX            = '<?= $AJAX ?>';
var SALAS_ASSETS_URL = '<?= BASE_URL ?>/modules/salas/assets/salas/';
</script>

<!-- Shared (código reutilizable) -->
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/api.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/alerts.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/utils.js"></script>

<!-- Catálogo -->
<script src="<?= BASE_URL ?>/modules/salas/views/js/roles/admin/catalogo.js?v=<?= filemtime(__DIR__ . '/../../../js/roles/admin/catalogo.js') ?>"></script>
