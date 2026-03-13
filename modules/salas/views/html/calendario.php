<?php
/**
 * html/calendario.php
 * Vista principal — Módulo Gestión de Reservas de Sala de Reunión
 * Diseño: calendario central + panel lateral de acciones
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
$AJAX = BASE_URL . '/modules/salas/controllers/ajax_handler.php';

$stats = $es_autorizador_o_admin
    ? $model->getEstadisticasGlobales()
    : $model->getEstadisticasSolicitante($id_usuario);
?>

<!-- =========================================================
     DEPENDENCIAS CSS
     ========================================================= -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/salas/views/css/calendario.css?v=<?= filemtime(__DIR__ . '/../css/calendario.css') ?>">

<!-- =========================================================
     LAYOUT PRINCIPAL
     ========================================================= -->
<div id="salas-root">

  <!-- ─── CABECERA ─────────────────────────────────────────── -->
  <div id="salas-topbar">
    <span class="salas-title">
      <i class="ti ti-building me-2"></i>RESERVA DE SALAS
    </span>
    <div class="salas-filters">
      <label>SEDE:</label>
      <select id="cal-filter-sede">
        <option value="">Todas las Sedes</option>
      </select>
      <label>SALA:</label>
      <select id="cal-filter-sala">
        <option value="">Todas las Salas</option>
      </select>
      <button type="button" id="btn-imprimir-calendario" onclick="imprimirCalendarioActual()">
        <i class="ti ti-printer me-1"></i>Imprimir
      </button>
    </div>
    <span class="d-none d-lg-inline text-white opacity-50" style="font-size:.72rem;white-space:nowrap;">
      <i class="ti ti-hand-click me-1"></i>Arrastra para crear reserva
    </span>
  </div>

  <!-- ─── CUERPO ───────────────────────────────────────────── -->
  <div id="salas-body">

    <!-- Calendario -->
    <div id="salas-cal-panel">
      <div id="salas-print-meta" aria-hidden="true">
        <div><strong>Periodo:</strong> <span id="print-periodo">-</span></div>
        <div><strong>Sede:</strong> <span id="print-sede">Todas las Sedes</span></div>
        <div><strong>Sala:</strong> <span id="print-sala">Todas las Salas</span></div>
        <div><strong>Impreso por:</strong> <span id="print-usuario">-</span></div>
        <div><strong>Fecha y hora:</strong> <span id="print-fecha-hora">-</span></div>
      </div>
      <div id="salas-main-calendar"></div>
    </div>

    <!-- Panel lateral -->
    <div id="salas-side-panel">

      <!-- Botones principales -->
      <button class="btn-nueva-solicitud" onclick="abrirNuevaSolicitud()">
        <i class="ti ti-plus fs-5"></i> Nueva Solicitud
      </button>

      <a href="?module=salas&action=mis-reservas" class="btn-mis-reservas">
        <i class="ti ti-calendar-check fs-5"></i> Mis Reservas
      </a>

      <!-- Estadísticas rápidas -->
      <div class="salas-stats-card">
        <div class="stats-header">
          <i class="ti ti-chart-pie-2"></i> Mis Reservas
        </div>
        <div class="stats-row">
          <span>Pendientes</span>
          <span class="badge-stat pendiente" id="stat-pendientes"><?= (int)($stats['pendientes'] ?? 0) ?></span>
        </div>
        <div class="stats-row">
          <span>Aprobadas</span>
          <span class="badge-stat aprobada" id="stat-aprobadas"><?= (int)($stats['aprobadas'] ?? 0) ?></span>
        </div>
        <div class="stats-row">
          <span>Rechazadas</span>
          <span class="badge-stat rechazada" id="stat-rechazadas"><?= (int)($stats['rechazadas'] ?? 0) ?></span>
        </div>
        <div class="stats-row">
          <span>Canceladas</span>
          <span class="badge-stat cancelada" id="stat-canceladas"><?= (int)($stats['canceladas'] ?? 0) ?></span>
        </div>
      </div>

      <?php if ($es_autorizador_o_admin): ?>
      <!-- Botones para Autorizador/Admin -->
      <div class="side-section-title">Gestión</div>

      <button class="btn-side-secondary" onclick="window.location.href='?module=salas&action=pendientes'">
        <i class="ti ti-clock-hour4 text-warning"></i>
        Solicitudes Pendientes
        <span class="badge-count" id="badge-pendientes-side"><?= (int)($stats['pendientes'] ?? 0) ?></span>
      </button>
      <?php endif; ?>

      <?php if ($es_admin): ?>
      <button class="btn-side-secondary" onclick="window.location.href='?module=salas&action=historial'">
        <i class="ti ti-history text-secondary"></i>
        Historial
      </button>
      <button class="btn-side-secondary" onclick="window.location.href = '?module=salas&action=admin'">
        <i class="ti ti-settings text-primary"></i>
        Administración
      </button>
      <?php endif; ?>

      <!-- Leyenda -->
      <div class="salas-legend">
        <div class="legend-item"><div class="legend-dot" style="background:#2fb344;"></div> Aprobada</div>
        <div class="legend-item"><div class="legend-dot" style="background:#f59f00;"></div> Pendiente</div>
        <div class="legend-item"><div class="legend-dot" style="background:#d63939;"></div> Rechazada</div>
        <div class="legend-item"><div class="legend-dot" style="background:#6c757d;"></div> Cancelada</div>
      </div>

    </div><!-- /salas-side-panel -->
  </div><!-- /salas-body -->
</div><!-- /salas-root -->


<!-- =========================================================
     MODAL — NUEVA SOLICITUD
     ========================================================= -->
<div class="modal modal-blur fade" tabindex="-1" id="modal-nueva-solicitud">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <!-- Overlay de oscurecimiento al abrir modal de foto -->
      <div id="nr-foto-overlay"
           style="display:none;position:absolute;inset:0;background:rgba(0,0,0,0.50);
                  z-index:1060;border-radius:inherit;pointer-events:none;"></div>
      <div class="modal-header">
        <h5 class="modal-title text-white"><i class="ti ti-plus me-2"></i>Nueva Solicitud de Reserva</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
    <form id="form-nueva-reserva" novalidate autocomplete="off">

      <div class="mb-3">
        <label class="form-label required">Sede</label>
        <select id="nr-sede" name="id_sede" class="form-select" required>
          <option value="">— Seleccione una sede —</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label required">Sala de Reunión</label>
        <div class="input-group">
          <select id="nr-sala" name="id_sala" class="form-select" required disabled>
            <option value="">— Seleccione primero la sede —</option>
          </select>
          <button type="button" class="btn btn-outline-secondary" id="btn-ver-sala" disabled
                  title="Ver fotografía de la sala" onclick="verFotoSala()">
            <i class="ti ti-photo me-1"></i>Ver sala
          </button>
        </div>
        <small class="form-hint" id="nr-sala-capacidad"></small>
      </div>

      <!-- Equipos AV de la sala -->
      <div class="mb-3" id="nr-equipos-section" style="display:none;">
        <label class="form-label d-flex align-items-center gap-2">
          <i class="ti ti-device-projector text-blue"></i>
          Equipos Audiovisuales disponibles
          <small class="text-muted fw-normal ms-1">(seleccione los que necesita)</small>
        </label>
        <div id="nr-equipos-lista" class="row g-2 mt-1"></div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-4">
          <label class="form-label required">Fecha de la Reunión</label>
          <input type="date" id="nr-fecha" name="fecha" class="form-control" required
                 min="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-4">
          <label class="form-label required">Hora Inicio</label>
          <input type="time" id="nr-hora-inicio" name="hora_inicio" class="form-control"
                 required min="07:00" max="20:00" step="900">
        </div>
        <div class="col-4">
          <label class="form-label required">Hora Fin</label>
          <input type="time" id="nr-hora-fin" name="hora_fin" class="form-control"
                 required min="07:00" max="20:00" step="900">
        </div>
      </div>

      <div class="mb-3">
        <button type="button" id="btn-verificar" class="btn btn-outline-secondary btn-sm w-100" disabled>
          <i class="ti ti-search me-1"></i>Verificar Disponibilidad
        </button>
        <div id="nr-disponibilidad-result" class="mt-2 d-none"></div>
      </div>

      <!-- Mini calendario de disponibilidad -->
      <div id="nr-calendario-aviso" class="alert alert-info py-2 small mb-3">
        <i class="ti ti-info-circle me-1"></i>Seleccione una sala para ver su disponibilidad.
      </div>
      <div id="calendar" class="mb-3" style="display:none; max-height:260px; overflow:hidden;"></div>

      <div class="mb-3">
        <label class="form-label required">Motivo de la Reunión</label>
        <textarea id="nr-motivo" name="motivo" class="form-control" rows="3"
                  maxlength="500" placeholder="Describa el motivo..." required></textarea>
        <small class="form-hint"><span id="nr-motivo-count">0</span>/500 caracteres</small>
      </div>

      <button type="submit" class="btn btn-primary w-100" id="btn-guardar-reserva" disabled>
        <i class="ti ti-send me-1"></i>Enviar Solicitud
      </button>
    </form>
      </div>
    </div>
  </div>
</div>



<!-- =========================================================
     MODAL — FOTO DE LA SALA
     ========================================================= -->
<div class="modal fade" id="modal-ver-sala" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold" id="modal-ver-sala-nombre">
          <i class="ti ti-door me-1"></i>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" id="modal-ver-sala-body">
        <!-- contenido dinámico -->
      </div>
      <div class="modal-footer py-2">
        <small class="text-muted me-auto" id="modal-ver-sala-cap"></small>
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>



<?php if ($es_autorizador_o_admin): ?>
<!-- =========================================================
     OFFCANVAS — PENDIENTES
     ========================================================= -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-pendientes" style="width:min(92vw,960px);">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title fw-bold">
      <i class="ti ti-clock-hour4 me-2"></i>Solicitudes Pendientes
      <span class="badge ms-2" style="background:#fff;color:#f59f00;" id="badge-pendientes">
        <?= (int)($stats['pendientes'] ?? 0) ?>
      </span>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <div class="table-responsive">
      <table id="tabla-pendientes" class="table table-vcenter table-striped table-sm" style="width:100%">
        <thead>
          <tr><th>#</th><th>Solicitante</th><th>Sede / Sala</th><th>Fecha</th>
              <th>Horario</th><th>Motivo</th><th>Enviado</th><th class="text-center">Acciones</th></tr>
        </thead>
        <tbody id="tbody-pendientes">
          <tr><td colspan="8" class="text-center text-muted py-4">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- =========================================================
     OFFCANVAS — HISTORIAL
     ========================================================= -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-historial" style="width:min(92vw,1000px);">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title fw-bold text-white"><i class="ti ti-history me-2"></i>Historial de Reservas</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Filtros -->
    <div class="row g-2 mb-3">
      <div class="col-sm-3">
        <input type="date" id="hist-desde" class="form-control form-control-sm" placeholder="Desde">
      </div>
      <div class="col-sm-3">
        <input type="date" id="hist-hasta" class="form-control form-control-sm" placeholder="Hasta">
      </div>
      <div class="col-sm-3">
        <select id="hist-estado" class="form-select form-select-sm">
          <option value="">— Todos los estados —</option>
          <option value="APROBADA">Aprobada</option>
          <option value="RECHAZADA">Rechazada</option>
          <option value="CANCELADA">Cancelada</option>
        </select>
      </div>
      <div class="col-sm-3">
        <button class="btn btn-sm btn-primary w-100" onclick="cargarHistorial()">
          <i class="ti ti-filter me-1"></i>Filtrar
        </button>
      </div>
    </div>
    <div class="table-responsive">
      <table id="tabla-historial" class="table table-vcenter table-striped table-sm" style="width:100%">
        <thead>
          <tr><th>#</th><th>Solicitante</th><th>Sede / Sala</th><th>Fecha</th>
              <th>Horario</th><th>Estado</th><th>Autorizador</th>
              <th>F. Autorización</th><th class="text-center">Detalle</th></tr>
        </thead>
        <tbody id="tbody-historial">
          <tr><td colspan="9" class="text-center text-muted py-4">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>


<?php if ($es_admin): ?>
<!-- =========================================================
     OFFCANVAS — ADMINISTRACIÓN
     ========================================================= -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="oc-admin" style="width:min(92vw,1100px);">
  <div class="offcanvas-header" style="background:#1a2940; color:#fff;">
    <h5 class="offcanvas-title fw-bold"><i class="ti ti-settings me-2"></i>Administración</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">

    <ul class="nav nav-pills mb-3" id="admin-subtabs">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="pill" href="#admin-sedes" onclick="cargarAdminSedes()">
          <i class="ti ti-map-pin me-1"></i>Sedes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="pill" href="#admin-salas" onclick="cargarAdminSalas()">
          <i class="ti ti-door me-1"></i>Salas
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="pill" href="#admin-equipos" onclick="cargarAdminEquipos()">
          <i class="ti ti-device-projector me-1"></i>Equipos AV
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <!-- SEDES -->
      <div class="tab-pane fade show active" id="admin-sedes">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Gestión de Sedes</h6>
          <button class="btn btn-primary btn-sm" onclick="abrirModalSede()">
            <i class="ti ti-plus me-1"></i>Nueva Sede
          </button>
        </div>
        <div class="table-responsive">
          <table id="tabla-admin-sedes" class="table table-vcenter table-striped table-sm" style="width:100%">
            <thead><tr><th>#</th><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
            <tbody id="tbody-admin-sedes"><tr><td colspan="6" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- SALAS -->
      <div class="tab-pane fade" id="admin-salas">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Gestión de Salas</h6>
          <button class="btn btn-primary btn-sm" onclick="abrirModalSala()">
            <i class="ti ti-plus me-1"></i>Nueva Sala
          </button>
        </div>
        <div class="table-responsive">
          <table id="tabla-admin-salas" class="table table-vcenter table-striped table-sm" style="width:100%">
            <thead><tr><th>#</th><th>Sede</th><th>Sala</th><th>Capacidad</th><th>Descripción</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
            <tbody id="tbody-admin-salas"><tr><td colspan="7" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- EQUIPOS -->
      <div class="tab-pane fade" id="admin-equipos">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Gestión de Equipos Audiovisuales</h6>
          <button class="btn btn-primary btn-sm" onclick="abrirModalEquipo()">
            <i class="ti ti-plus me-1"></i>Nuevo Equipo
          </button>
        </div>
        <div class="table-responsive">
          <table id="tabla-admin-equipos" class="table table-vcenter table-striped table-sm" style="width:100%">
            <thead><tr><th>#</th><th>Sede / Sala</th><th>Equipo</th><th>Tipo</th><th>Descripción</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
            <tbody id="tbody-admin-equipos"><tr><td colspan="7" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
<?php endif; ?>


<!-- =========================================================
     MODALES
     ========================================================= -->

<!-- Modal: Detalle de Reserva -->
<div class="modal modal-blur fade" id="modal-detalle-reserva" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-calendar-check me-2"></i>Detalle de Reserva <span id="modal-detalle-id"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modal-detalle-body">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
      <div class="modal-footer" id="modal-detalle-footer"></div>
    </div>
  </div>
</div>



<!-- Modal: Rechazar Reserva -->
<div class="modal modal-blur fade" id="modal-rechazar" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger"><i class="ti ti-circle-x me-2"></i>Rechazar Solicitud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="rechazar-id-reserva">
        <label class="form-label">Observación (opcional)</label>
        <textarea id="rechazar-observacion" class="form-control" rows="3"
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

<!-- Modal: Gestión de Sede (Admin) -->
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
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" id="sede-telefono" name="telefono" class="form-control" maxlength="50">
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

<!-- Modal: Gestión de Sala (Admin) -->
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
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary ms-auto"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Gestión de Equipo (Admin) -->
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
     SCRIPTS (CDN) + Variables PHP → JS + archivo JS externo
     ========================================================= -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/es.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Punto único de inyección PHP → JS -->
<script>
const AJAX                  = '<?= $AJAX ?>';
const ROL                   = '<?= htmlspecialchars($rol_normalizado) ?>';
const ES_AUTORIZADOR_O_ADMIN = <?= $es_autorizador_o_admin ? 'true' : 'false' ?>;
const ES_ADMIN               = <?= $es_admin ? 'true' : 'false' ?>;
const SALAS_ASSETS_URL       = '<?= BASE_URL ?>/modules/salas/assets/salas/';
const USUARIO_IMPRESION      = <?= json_encode((string)($usuario_login_impresion ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<script src="<?= BASE_URL ?>/modules/salas/views/js/calendario.js?v=<?= filemtime(__DIR__ . '/../js/calendario.js') ?>"></script>
