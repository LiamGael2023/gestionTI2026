<?php
/**
 * dashboard.php - Vista del Dashboard de Indicadores
 * Indicadores: Utilizacion, Estado, Sedes, Tendencias, Gerencia
 * Proyecto Especial Chavimochic (PECH) - GestionTI v1.0
 */

$stats = $es_autorizador_o_admin
    ? $model->getEstadisticasGlobales()
    : $model->getEstadisticasSolicitante($id_usuario);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/salas/views/css/calendario.css?v=<?= filemtime(__DIR__ . '/../../css/calendario.css') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/salas/views/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../../css/dashboard.css') ?>">

<div id="dashboard-root">
  <div id="dashboard-topbar">
    <div>
      <h2 class="dashboard-title"><i class="ti ti-chart-line me-2"></i>Dashboard de Indicadores</h2>
      <div class="text-secondary small" id="dashboard-last-update">Actualizando datos...</div>
    </div>
    <div class="dashboard-topbar-actions">
      <button type="button" id="btn-ver-calendario" onclick="verCalendarioDesdeDashboard()">
        <i class="ti ti-calendar me-1"></i>Ver Calendario
      </button>
      <button type="button" class="btn btn-outline-primary btn-sm" onclick="cargarDashboard()">
        <i class="ti ti-refresh me-1"></i>Actualizar
      </button>
    </div>
  </div>

  <div id="dashboard-body">
    <div id="dashboard-main-panel" class="view-fade">

      <div class="card mb-4">
        <div class="card-header">
          <h3 class="card-title"><i class="ti ti-door me-2"></i>Utilizacion de Salas (Ultimos 30 dias)</h3>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <div class="stat-card text-center">
                <div class="stat-value text-primary" id="ocupacion-salas-utilizadas">-</div>
                <div class="stat-label">Salas Utilizadas</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="stat-card text-center">
                <div class="stat-value text-info" id="ocupacion-total-reservas">-</div>
                <div class="stat-label">Total Reservas</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="stat-card text-center">
                <div class="stat-value text-warning" id="ocupacion-horas-totales">-</div>
                <div class="stat-label">Horas Utilizadas</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="stat-card text-center">
                <div class="stat-value text-success" id="ocupacion-promedio-horas">-</div>
                <div class="stat-label">Promedio Horas/Reserva</div>
              </div>
            </div>
          </div>

          <div class="row mt-4">
            <div class="col-md-6">
              <h5><i class="ti ti-trending-up me-2"></i>Top 3 Salas Mas Utilizadas</h5>
              <div id="container-top-salas" class="mt-3">
                <div class="placeholder-glow"><span class="placeholder col-12 mb-2"></span></div>
              </div>
            </div>
            <div class="col-md-6">
              <h5><i class="ti ti-trending-down me-2"></i>Top 3 Salas Menos Utilizadas</h5>
              <div id="container-menos-salas" class="mt-3">
                <div class="placeholder-glow"><span class="placeholder col-12 mb-2"></span></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h3 class="card-title"><i class="ti ti-users me-2"></i>Solicitudes por Gerencia/Unidad Laboral (Ultimos 30 dias)</h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-vcenter table-striped table-sm">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Email</th>
                  <th>Gerencia</th>
                  <th>Unidad Laboral</th>
                  <th>Aprobadas</th>
                  <th>Pendientes</th>
                  <th>Rechazadas</th>
                  <th>Horas</th>
                </tr>
              </thead>
              <tbody id="tbody-gerencia">
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="ti ti-loader animate-spin"></i> Sincronizando datos...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-2">
        <div class="col-md-8">
          <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-building-community me-2"></i>Cobertura Organizacional</h3></div>
            <div class="card-body">
              <div class="org-kpi-grid mb-3">
                <div class="org-kpi-card">
                  <div class="org-kpi-value" id="org-total-usuarios">0</div>
                  <div class="org-kpi-label">Usuarios con reservas</div>
                </div>
                <div class="org-kpi-card">
                  <div class="org-kpi-value" id="org-total-gerencias">0</div>
                  <div class="org-kpi-label">Gerencias activas</div>
                </div>
                <div class="org-kpi-card">
                  <div class="org-kpi-value" id="org-total-unidades">0</div>
                  <div class="org-kpi-label">Unidades activas</div>
                </div>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <h5 class="org-section-title"><i class="ti ti-briefcase text-primary me-1"></i>Top Gerencias</h5>
                  <div id="org-top-gerencias" class="org-list">
                    <div class="text-muted small">Sin datos</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <h5 class="org-section-title"><i class="ti ti-hierarchy-2 text-info me-1"></i>Top Unidades</h5>
                  <div id="org-top-unidades" class="org-list">
                    <div class="text-muted small">Sin datos</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-stats me-2"></i>Indicadores de Uso Diario</h3></div>
            <div class="card-body">
              <div><p><strong><i class="ti ti-arrow-up text-success me-1"></i>Dia Mas Utilizado</strong></p><div class="p-2 bg-success-light rounded" id="dia-mas-utilizado">-</div></div>
              <div class="mt-3"><p><strong><i class="ti ti-arrow-down text-danger me-1"></i>Dia Menos Utilizado</strong></p><div class="p-2 bg-danger-light rounded" id="dia-menos-utilizado">-</div></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title"><i class="ti ti-list-check me-2"></i>Estado de Solicitudes (Ultimos 30 dias)</h3>
            </div>
            <div class="card-body"><div class="chart-wrapper"><canvas id="chart-estados" height="300"></canvas></div></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title"><i class="ti ti-clock-check me-2"></i>Tiempo Promedio de Aprobacion</h3>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="stat-card text-center">
                    <div class="stat-value text-info" id="promedio-aprobacion-horas">-</div>
                    <div class="stat-label">Horas Promedio</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="stat-card text-center">
                    <div class="stat-value text-success" id="promedio-aprobacion-total">-</div>
                    <div class="stat-label">Total Aprobadas</div>
                  </div>
                </div>
              </div>

              <div class="mini-tendencia-wrap mt-3">
                <div class="mini-tendencia-header">
                  <span class="mini-tendencia-title">Mini tendencia (Ultimos 7 dias)</span>
                  <span class="mini-tendencia-caption">Solicitudes por dia</span>
                </div>
                <div class="mini-tendencia-chart">
                  <canvas id="chart-mini-aprobacion" height="84"></canvas>
                </div>
                <div id="mini-aprobacion-empty" class="mini-tendencia-empty d-none">Sin datos recientes para tendencia.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h3 class="card-title"><i class="ti ti-tools me-2"></i>Top 3 Equipos Mas Usados</h3>
        </div>
        <div class="card-body card-equipos-body">
          <div class="chart-wrapper chart-equipos"><canvas id="chart-equipos"></canvas></div>
        </div>
      </div>

    </div>

    <div id="dashboard-side-panel">
      <div id="salas-side-panel">
        <button class="btn-nueva-solicitud" onclick="verCalendarioDesdeDashboard('nueva')">
          <i class="ti ti-plus fs-5"></i> Nueva Solicitud
        </button>

        <a href="?module=salas&action=mis-reservas" class="btn-mis-reservas">
          <i class="ti ti-calendar-check fs-5"></i> Mis Reservas
        </a>

        <div class="salas-stats-card">
          <div class="stats-header"><i class="ti ti-chart-pie-2"></i> Mis Reservas</div>
          <div class="stats-row"><span>Pendientes</span><span class="badge-stat pendiente" id="stat-pendientes"><?= (int)($stats['pendientes'] ?? 0) ?></span></div>
          <div class="stats-row"><span>Aprobadas</span><span class="badge-stat aprobada" id="stat-aprobadas"><?= (int)($stats['aprobadas'] ?? 0) ?></span></div>
          <div class="stats-row"><span>Rechazadas</span><span class="badge-stat rechazada" id="stat-rechazadas"><?= (int)($stats['rechazadas'] ?? 0) ?></span></div>
          <div class="stats-row"><span>Canceladas</span><span class="badge-stat cancelada" id="stat-canceladas"><?= (int)($stats['canceladas'] ?? 0) ?></span></div>
        </div>

        <?php if ($es_autorizador_o_admin): ?>
        <div class="side-section-title">Gestion</div>
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
        <button class="btn-side-secondary" onclick="window.location.href='?module=salas&action=admin'">
          <i class="ti ti-settings text-primary"></i>
          Administracion
        </button>
        <?php endif; ?>

        <div class="salas-legend">
          <div class="legend-item"><div class="legend-dot" style="background:#2fb344;"></div> Aprobada</div>
          <div class="legend-item"><div class="legend-dot" style="background:#f59f00;"></div> Pendiente</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var AJAX = '<?= BASE_URL ?>/modules/salas/controllers/ajax_handler.php';
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/api.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/alerts.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/shared/utils.js"></script>
<script src="<?= BASE_URL ?>/modules/salas/views/js/features/dashboard.js"></script>
