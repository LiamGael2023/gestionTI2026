<?php
/**
 * admin_page.php — Panel de Administración de Salas (página independiente)
 * Acceso directo, sin pasar por el router de index.php.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/models/SalasModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

$conn           = Conexion::conectar();
$rol_usuario    = $_SESSION['usuario_rol'] ?? '';
$rol_normalizado = ($rol_usuario === 'ADMIN') ? SalasModel::ROL_ADMINISTRADOR : $rol_usuario;
$es_admin        = SalasModel::esAdmin($rol_normalizado);

if (!$es_admin) {
    http_response_code(403);
    die('<!doctype html><html lang="es"><body><div style="padding:2rem;font-family:sans-serif;color:#d63939"><h2>Acceso denegado</h2><p>Esta sección es solo para administradores.</p><a href="' . BASE_URL . '/?module=salas">&#8592; Volver</a></div></body></html>');
}

$AJAX     = BASE_URL . '/modules/salas/controllers/ajax_handler.php';
$BASE     = BASE_URL;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <title>Administración — Salas de Reunión</title>

  <!-- Tabler CSS -->
  <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

  <style>
    body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
    .page-header { background: #fff; border-bottom: 1px solid #e6e7e8; padding: 1rem 0; }
  </style>
</head>
<body class="antialiased">
<div class="wrapper">

  <!-- Header simple -->
  <div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title mb-0">
            <i class="ti ti-settings me-2 text-primary"></i>Administración — Salas de Reunión
          </h2>
          <div class="text-secondary mt-1 small">Gestión de Sedes, Salas y Equipos Audiovisuales</div>
        </div>
        <div class="col-auto">
          <a href="<?= $BASE ?>/?module=salas" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Volver al Calendario
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Contenido -->
  <div class="container-xl mt-3">

    <div class="card">
      <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs" id="admin-tabs">
          <li class="nav-item">
            <a class="nav-link active px-3 py-3" data-bs-toggle="tab" href="#tab-sedes">
              <i class="ti ti-map-pin me-1"></i>Sedes
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 py-3" data-bs-toggle="tab" href="#tab-salas">
              <i class="ti ti-door me-1"></i>Salas
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 py-3" data-bs-toggle="tab" href="#tab-equipos">
              <i class="ti ti-device-projector me-1"></i>Equipos AV
            </a>
          </li>
        </ul>
      </div>

      <div class="card-body">
        <div class="tab-content">

          <!-- SEDES -->
          <div class="tab-pane fade show active" id="tab-sedes">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Gestión de Sedes</h5>
              <button class="btn btn-primary btn-sm" onclick="abrirModalSede()">
                <i class="ti ti-plus me-1"></i>Nueva Sede
              </button>
            </div>
            <div class="table-responsive">
              <table id="tabla-sedes" class="table table-vcenter table-striped table-sm w-100">
                <thead><tr><th>#</th><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody id="tbody-sedes"><tr><td colspan="6" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
              </table>
            </div>
          </div>

          <!-- SALAS -->
          <div class="tab-pane fade" id="tab-salas">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Gestión de Salas</h5>
              <button class="btn btn-primary btn-sm" onclick="abrirModalSala()">
                <i class="ti ti-plus me-1"></i>Nueva Sala
              </button>
            </div>
            <div class="table-responsive">
              <table id="tabla-salas" class="table table-vcenter table-striped table-sm w-100">
                <thead><tr><th>#</th><th>Sede</th><th>Sala</th><th>Capacidad</th><th>Descripción</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody id="tbody-salas"><tr><td colspan="7" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
              </table>
            </div>
          </div>

          <!-- EQUIPOS -->
          <div class="tab-pane fade" id="tab-equipos">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Gestión de Equipos Audiovisuales</h5>
              <button class="btn btn-primary btn-sm" onclick="abrirModalEquipo()">
                <i class="ti ti-plus me-1"></i>Nuevo Equipo
              </button>
            </div>
            <div class="table-responsive">
              <table id="tabla-equipos" class="table table-vcenter table-striped table-sm w-100">
                <thead><tr><th>#</th><th>Sede / Sala</th><th>Equipo</th><th>Tipo</th><th>Descripción</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody id="tbody-equipos"><tr><td colspan="7" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div><!-- /container -->

</div><!-- /wrapper -->


<!-- MODAL: Sede -->
<div class="modal fade" id="modal-sede" tabindex="-1">
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
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" id="sede-nombre" name="nombre" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Dirección</label>
            <input type="text" id="sede-direccion" name="direccion" class="form-control" maxlength="250">
          </div>
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" id="sede-telefono" name="telefono" class="form-control" maxlength="50">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: Sala -->
<div class="modal fade" id="modal-sala" tabindex="-1">
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
            <label class="form-label">Sede <span class="text-danger">*</span></label>
            <select id="sala-sede" name="id_sede" class="form-select" required>
              <option value="">— Seleccione sede —</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" id="sala-nombre" name="nombre" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Capacidad <span class="text-danger">*</span></label>
            <input type="number" id="sala-capacidad" name="capacidad" class="form-control" min="1" max="500" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea id="sala-descripcion" name="descripcion" class="form-control" rows="2" maxlength="500"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: Equipo AV -->
<div class="modal fade" id="modal-equipo" tabindex="-1">
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
            <label class="form-label">Sala <span class="text-danger">*</span></label>
            <select id="equipo-sala" name="id_sala" class="form-select" required>
              <option value="">— Seleccione sala —</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" id="equipo-nombre" name="nombre" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo <span class="text-danger">*</span></label>
            <select id="equipo-tipo" name="tipo" class="form-select" required>
              <option value="">— Seleccione tipo —</option>
              <option>Proyector</option><option>Televisor</option><option>Pantalla</option>
              <option>Micrófono</option><option>Videoconferencia</option>
              <option>Pizarra Digital</option><option>Sistema de Sonido</option>
              <option>Puntero Láser</option><option>Otro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción / Especificaciones</label>
            <textarea id="equipo-descripcion" name="descripcion" class="form-control" rows="2" maxlength="500"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- SCRIPTS: carga ordenada y sin conflictos -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>

<script>
const AJAX = '<?= htmlspecialchars($AJAX, ENT_QUOTES) ?>';

function req(action, data, method) {
  return $.ajax({ url: AJAX + '?action=' + action, method: method || 'POST', data: data || {}, dataType: 'json', cache: false });
}

function esc(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function badgeActivo(v) {
  return v == 1
    ? '<span class="badge bg-success-lt text-success">Activo</span>'
    : '<span class="badge bg-secondary-lt text-secondary">Inactivo</span>';
}

var showOk   = function(msg) { Swal.fire({ icon:'success', title:'Éxito', text:msg, timer:2000, showConfirmButton:false }); };
var showErr  = function(msg) { Swal.fire({ icon:'error',   title:'Error', text:msg }); };
var showConf = function(msg, cb) {
  Swal.fire({ icon:'warning', title:'¿Está seguro?', text:msg,
    showCancelButton:true, confirmButtonText:'Sí', cancelButtonText:'Cancelar',
    confirmButtonColor:'#d63939' }).then(function(r){ if (r.isConfirmed) cb(); });
};

/* ─── DataTables instances ──────────────────────────────── */
var dtSedes = null, dtSalas = null, dtEquipos = null;

function dtInit(id, cols, instance) {
  return $('#' + id).DataTable({
    destroy: true, autoWidth: false,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
    columnDefs: [{ targets: cols - 1, orderable: false }]
  });
}

/* ========================================================
   SEDES
   ======================================================== */
function cargarSedes() {
  req('getAllSedes', {}, 'GET').done(function(res) {
    if (!res || !res.ok) { $('#tbody-sedes').html('<tr><td colspan="6" class="text-danger text-center">' + (res && res.msg ? res.msg : 'Error al obtener sedes') + '</td></tr>'); return; }
    if (dtSedes) { dtSedes.destroy(); dtSedes = null; }
    var html = '';
    $.each(res.data, function(i, s) {
      var btnToggle = s.activo == 1
        ? '<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleSede(' + s.id + ',0)"><i class="ti ti-toggle-right"></i></button>'
        : '<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleSede(' + s.id + ',1)"><i class="ti ti-toggle-left"></i></button>';
      html += '<tr><td>' + s.id + '</td><td><strong>' + esc(s.nombre) + '</strong></td>'
            + '<td>' + esc(s.direccion||'—') + '</td><td>' + esc(s.telefono||'—') + '</td>'
            + '<td>' + badgeActivo(s.activo) + '</td>'
            + '<td class="text-center"><button class="btn btn-sm btn-outline-warning" onclick="abrirModalSede(' + s.id + ')"><i class="ti ti-edit"></i></button>' + btnToggle + '</td></tr>';
    });
    $('#tbody-sedes').html(html || '<tr><td colspan="6" class="text-center text-muted">Sin sedes registradas.</td></tr>');
    if (html) dtSedes = dtInit('tabla-sedes', 6);
  }).fail(function(xhr) { $('#tbody-sedes').html('<tr><td colspan="6" class="text-danger text-center">HTTP ' + xhr.status + '</td></tr>'); });
}

function abrirModalSede(id) {
  $('#form-sede')[0].reset(); $('#sede-id').val(''); $('#modal-sede-titulo').text('Nueva Sede');
  if (id) {
    req('getAllSedes', {}, 'GET').done(function(res) {
      var s = $.grep(res.data, function(x){ return x.id == id; })[0];
      if (!s) return;
      $('#modal-sede-titulo').text('Editar Sede');
      $('#sede-id').val(s.id); $('#sede-nombre').val(s.nombre);
      $('#sede-direccion').val(s.direccion); $('#sede-telefono').val(s.telefono);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-sede')).show();
}

$('#form-sede').on('submit', function(e) {
  e.preventDefault();
  req('guardarSede', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-sede')).hide();
    if (res.ok) { showOk(res.msg); cargarSedes(); } else showErr(res.msg);
  }).fail(function(){ showErr('Error de comunicación.'); });
});

function toggleSede(id, activo) {
  showConf('¿Confirma el cambio de estado de la sede?', function() {
    req('toggleSede', { id: id, activo: activo }).done(function(res) {
      if (res.ok) cargarSedes(); else showErr(res.msg);
    });
  });
}

/* ========================================================
   SALAS
   ======================================================== */
function cargarSalas() {
  req('getAllSalas', {}, 'GET').done(function(res) {
    if (!res.ok) { $('#tbody-salas').html('<tr><td colspan="7" class="text-danger text-center">' + (res.msg||'Error') + '</td></tr>'); return; }
    if (dtSalas) { dtSalas.destroy(); dtSalas = null; }
    var html = '';
    $.each(res.data, function(i, s) {
      var btnToggle = s.activo == 1
        ? '<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleSala(' + s.id_sala + ',0)"><i class="ti ti-toggle-right"></i></button>'
        : '<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleSala(' + s.id_sala + ',1)"><i class="ti ti-toggle-left"></i></button>';
      html += '<tr><td>' + s.id_sala + '</td><td>' + esc(s.sede_nombre) + '</td><td><strong>' + esc(s.nombre) + '</strong></td>'
            + '<td class="text-center">' + s.capacidad + '</td><td><small>' + esc(s.descripcion||'—') + '</small></td>'
            + '<td>' + badgeActivo(s.activo) + '</td>'
            + '<td class="text-center"><button class="btn btn-sm btn-outline-warning" onclick="abrirModalSala(' + s.id_sala + ')"><i class="ti ti-edit"></i></button>' + btnToggle + '</td></tr>';
    });
    $('#tbody-salas').html(html || '<tr><td colspan="7" class="text-center text-muted">Sin salas registradas.</td></tr>');
    if (html) dtSalas = dtInit('tabla-salas', 7);
  }).fail(function(xhr) { $('#tbody-salas').html('<tr><td colspan="7" class="text-danger text-center">HTTP ' + xhr.status + '</td></tr>'); });
}

function cargarSedesEnSelect() {
  req('getAllSedes', {}, 'GET').done(function(res) {
    var opts = '<option value="">— Seleccione sede —</option>';
    if (res.ok) $.each(res.data, function(i, s) { opts += '<option value="' + s.id + '">' + esc(s.nombre) + '</option>'; });
    $('#sala-sede').html(opts);
  });
}

function abrirModalSala(id) {
  $('#form-sala')[0].reset(); $('#sala-id').val(''); $('#modal-sala-titulo').text('Nueva Sala');
  cargarSedesEnSelect();
  if (id) {
    req('getAllSalas', {}, 'GET').done(function(res) {
      var s = $.grep(res.data, function(x){ return x.id_sala == id; })[0];
      if (!s) return;
      $('#modal-sala-titulo').text('Editar Sala'); $('#sala-id').val(s.id_sala);
      setTimeout(function() {
        $('#sala-sede').val(s.id_sede); $('#sala-nombre').val(s.nombre);
        $('#sala-capacidad').val(s.capacidad); $('#sala-descripcion').val(s.descripcion);
      }, 300);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-sala')).show();
}

$('#form-sala').on('submit', function(e) {
  e.preventDefault();
  req('guardarSala', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-sala')).hide();
    if (res.ok) { showOk(res.msg); cargarSalas(); cargarSalasEnSelectEquipo(); } else showErr(res.msg);
  }).fail(function(){ showErr('Error de comunicación.'); });
});

function toggleSala(id, activo) {
  showConf('¿Confirma el cambio de estado de la sala?', function() {
    req('toggleSala', { id_sala: id, activo: activo }).done(function(res) {
      if (res.ok) cargarSalas(); else showErr(res.msg);
    });
  });
}

/* ========================================================
   EQUIPOS
   ======================================================== */
function cargarEquipos() {
  req('getAllEquipos', {}, 'GET').done(function(res) {
    if (!res.ok) { $('#tbody-equipos').html('<tr><td colspan="7" class="text-danger text-center">' + (res.msg||'Error') + '</td></tr>'); return; }
    if (dtEquipos) { dtEquipos.destroy(); dtEquipos = null; }
    var html = '';
    $.each(res.data, function(i, e) {
      var btnToggle = e.activo == 1
        ? '<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleEquipo(' + e.id_equipo + ',0)"><i class="ti ti-toggle-right"></i></button>'
        : '<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleEquipo(' + e.id_equipo + ',1)"><i class="ti ti-toggle-left"></i></button>';
      html += '<tr><td>' + e.id_equipo + '</td>'
            + '<td><strong>' + esc(e.sede_nombre) + '</strong><br><small>' + esc(e.sala_nombre) + '</small></td>'
            + '<td><strong>' + esc(e.nombre) + '</strong></td>'
            + '<td><span class="badge bg-blue-lt text-blue">' + esc(e.tipo) + '</span></td>'
            + '<td><small>' + esc(e.descripcion||'—') + '</small></td>'
            + '<td>' + badgeActivo(e.activo) + '</td>'
            + '<td class="text-center"><button class="btn btn-sm btn-outline-warning" onclick="abrirModalEquipo(' + e.id_equipo + ')"><i class="ti ti-edit"></i></button>' + btnToggle + '</td></tr>';
    });
    $('#tbody-equipos').html(html || '<tr><td colspan="7" class="text-center text-muted">Sin equipos registrados.</td></tr>');
    if (html) dtEquipos = dtInit('tabla-equipos', 7);
  }).fail(function(xhr) { $('#tbody-equipos').html('<tr><td colspan="7" class="text-danger text-center">HTTP ' + xhr.status + '</td></tr>'); });
}

function cargarSalasEnSelectEquipo() {
  req('getAllSalas', {}, 'GET').done(function(res) {
    var opts = '<option value="">— Seleccione sala —</option>';
    if (res.ok) $.each(res.data, function(i, s) { opts += '<option value="' + s.id_sala + '">[' + esc(s.sede_nombre) + '] ' + esc(s.nombre) + '</option>'; });
    $('#equipo-sala').html(opts);
  });
}

function abrirModalEquipo(id) {
  $('#form-equipo')[0].reset(); $('#equipo-id').val(''); $('#modal-equipo-titulo').text('Nuevo Equipo AV');
  cargarSalasEnSelectEquipo();
  if (id) {
    req('getAllEquipos', {}, 'GET').done(function(res) {
      var e = $.grep(res.data, function(x){ return x.id_equipo == id; })[0];
      if (!e) return;
      $('#modal-equipo-titulo').text('Editar Equipo AV'); $('#equipo-id').val(e.id_equipo);
      setTimeout(function() {
        $('#equipo-sala').val(e.id_sala); $('#equipo-nombre').val(e.nombre);
        $('#equipo-tipo').val(e.tipo); $('#equipo-descripcion').val(e.descripcion);
      }, 300);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-equipo')).show();
}

$('#form-equipo').on('submit', function(e) {
  e.preventDefault();
  req('guardarEquipo', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-equipo')).hide();
    if (res.ok) { showOk(res.msg); cargarEquipos(); } else showErr(res.msg);
  }).fail(function(){ showErr('Error de comunicación.'); });
});

function toggleEquipo(id, activo) {
  showConf('¿Confirma el cambio de estado del equipo?', function() {
    req('toggleEquipo', { id_equipo: id, activo: activo }).done(function(res) {
      if (res.ok) cargarEquipos(); else showErr(res.msg);
    });
  });
}

/* ─── Tabs ──────────────────────────────────────────────── */
$('#admin-tabs a').on('shown.bs.tab', function(e) {
  var t = $(e.target).attr('href');
  if (t === '#tab-sedes')   cargarSedes();
  if (t === '#tab-salas')   cargarSalas();
  if (t === '#tab-equipos') cargarEquipos();
});

// Carga inicial del tab activo (sedes) — llamada directa sin document.ready
// para evitar conflictos con la inicialización de Tabler/Bootstrap tabs
cargarSedes();
</script>

</body>
</html>
