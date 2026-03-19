<?php
/* ═══════════════════════════════════════════════════════
   DASHBOARD — Inventario TI
   Consultas reales a la BD para KPIs y gráficos
═══════════════════════════════════════════════════════ */
$conn = Conexion::conectar();

function queryVal($conn, $sql, $params = []) {
    $stmt = $params ? sqlsrv_query($conn, $sql, $params) : sqlsrv_query($conn, $sql);
    if (!$stmt) return 0;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($stmt);
    return $row ? ($row[0] ?? 0) : 0;
}
function queryRows($conn, $sql, $params = []) {
    $stmt = $params ? sqlsrv_query($conn, $sql, $params) : sqlsrv_query($conn, $sql);
    if (!$stmt) return [];
    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
    sqlsrv_free_stmt($stmt);
    return $rows;
}

// ── KPIs principales ──────────────────────────────────
$totalActivos   = queryVal($conn, "SELECT COUNT(*) FROM inventario.activos");
$totalEquipos   = queryVal($conn, "SELECT COUNT(*) FROM inventario.equipo");
$totalEstaciones= queryVal($conn, "SELECT COUNT(*) FROM inventario.estacion");
$totalAsignados = queryVal($conn, "SELECT COUNT(*) FROM inventario.asignacion WHERE fechaLiberacion IS NULL");
$totalIPs       = queryVal($conn, "SELECT COUNT(*) FROM inventario.ip");
$ipsDisponibles = queryVal($conn, "SELECT COUNT(*) FROM inventario.ip WHERE estado = 'disponible'");
$ipsAsignadas   = queryVal($conn, "SELECT COUNT(*) FROM inventario.ip WHERE estado = 'asignada'");
$estSinAsignar  = $totalEstaciones - $totalAsignados;

// ── Equipos por tipo de activo (top 6) ────────────────
$equiposPorTipo = queryRows($conn, "
    SELECT TOP 6 a.descripcion AS tipo, COUNT(e.idEquipo) AS total
    FROM inventario.equipo e
    INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
    GROUP BY a.descripcion
    ORDER BY total DESC
");

// ── Estaciones por ubicación ──────────────────────────
$estPorUbicacion = queryRows($conn, "
    SELECT TOP 8 u.descripcion AS ubicacion, COUNT(DISTINCT ee.idEstacion) AS total
    FROM inventario.estacionEquipo ee
    INNER JOIN inventario.estacion est ON ee.idEstacion = est.idEstacion
    INNER JOIN inventario.asignacion a ON est.idEstacion = a.idEstacion
        AND a.fechaLiberacion IS NULL
    INNER JOIN inventario.ambiente amb ON a.idAmbiente = amb.idAmbiente
    INNER JOIN inventario.ubicacion u ON amb.idUbicacion = u.idUbicacion
    GROUP BY u.descripcion
    ORDER BY total DESC
");

// ── Equipos por ambiente (top 8) ──────────────────────
$equiposPorAmbiente = queryRows($conn, "
    SELECT TOP 8
        amb.descripcion AS ambiente,
        u.descripcion   AS ubicacion,
        COUNT(ee.idEquipo) AS total
    FROM inventario.estacionEquipo ee
    INNER JOIN inventario.estacion est  ON ee.idEstacion  = est.idEstacion
    INNER JOIN inventario.asignacion a  ON est.idEstacion = a.idEstacion
        AND a.fechaLiberacion IS NULL
    INNER JOIN inventario.ambiente amb  ON a.idAmbiente   = amb.idAmbiente
    INNER JOIN inventario.ubicacion u   ON amb.idUbicacion = u.idUbicacion
    GROUP BY amb.descripcion, u.descripcion
    ORDER BY total DESC
");

// ── Jerarquía ubicación → ambientes → estaciones ──────
$jerarquia = queryRows($conn, "
    SELECT
        u.idUbicacion,
        u.descripcion   AS ubicacion,
        COUNT(DISTINCT amb.idAmbiente)  AS totalAmbientes,
        COUNT(DISTINCT est.idEstacion)  AS totalEstaciones,
        COUNT(DISTINCT ee.idEquipo)     AS totalEquipos
    FROM inventario.ubicacion u
    LEFT JOIN inventario.ambiente    amb ON u.idUbicacion  = amb.idUbicacion
    LEFT JOIN inventario.asignacion  a   ON amb.idAmbiente = a.idAmbiente
        AND a.fechaLiberacion IS NULL
    LEFT JOIN inventario.estacion    est ON a.idEstacion   = est.idEstacion
    LEFT JOIN inventario.estacionEquipo ee ON est.idEstacion = ee.idEstacion
    GROUP BY u.idUbicacion, u.descripcion
    ORDER BY totalEquipos DESC
");

// ── Últimas asignaciones ───────────────────────────────
$ultimasAsignaciones = queryRows($conn, "
    SELECT TOP 5
        est.nombreEstacion,
        a.trabajadorResponsable,
        a.fechaAsignacion,
        amb.descripcion AS ambiente
    FROM inventario.asignacion a
    INNER JOIN inventario.estacion est ON a.idEstacion = est.idEstacion
    LEFT  JOIN inventario.ambiente amb ON a.idAmbiente  = amb.idAmbiente
    WHERE a.fechaLiberacion IS NULL
    ORDER BY a.fechaCreacion DESC
");

// ── Terminales vs Estaciones completas ────────────────
// esSimple puede no existir aún si no se ejecutó el SQL de terminal
try {
    $totalTerminales = queryVal($conn, "SELECT COUNT(*) FROM inventario.estacion WHERE esSimple = 1");
    $totalCompletas  = queryVal($conn, "SELECT COUNT(*) FROM inventario.estacion WHERE esSimple = 0");
} catch (Exception $e) {
    $totalTerminales = 0;
    $totalCompletas  = $totalEstaciones;
}

sqlsrv_close($conn);

// ── Helpers PHP ───────────────────────────────────────
function fmtFecha($f) {
    if (!$f) return '—';
    if ($f instanceof DateTime) return $f->format('d/m/Y');
    $ts = strtotime($f); return $ts ? date('d/m/Y', $ts) : '—';
}
$pctAsignadas = $totalEstaciones > 0 ? round(($totalAsignados / $totalEstaciones) * 100) : 0;
$pctIPs       = $totalIPs > 0 ? round(($ipsAsignadas / $totalIPs) * 100) : 0;
?>

<style>
/* ══════════════════════════════════════════
   DASHBOARD — Variables y base
══════════════════════════════════════════ */
.dash-kpi {
    border-radius: 12px;
    border: 1px solid var(--tblr-border-color);
    background: #fff;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: box-shadow .2s, transform .2s;
    height: 100%;
}
.dash-kpi:hover {
    box-shadow: 0 8px 32px rgba(0,0,0,.08);
    transform: translateY(-2px);
}
.dash-kpi-icon {
    width: 48px; height: 48px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.dash-kpi-val  { font-size: 1.9rem; font-weight: 800; line-height: 1; color: #1e293b; }
.dash-kpi-label{ font-size: .78rem; font-weight: 700; text-transform: uppercase;
                  letter-spacing: .06em; color: #94a3b8; margin-top: .2rem; }
.dash-kpi-sub  { font-size: .75rem; color: #64748b; margin-top: .3rem; }

/* Progress bar */
.dash-progress { height: 6px; border-radius: 99px; background: #e2e8f0; overflow: hidden; margin-top: .5rem; }
.dash-progress-bar { height: 100%; border-radius: 99px; transition: width 1s ease; }

/* Sección card */
.dash-card {
    border-radius: 12px;
    border: 1px solid var(--tblr-border-color);
    background: #fff;
    overflow: hidden;
}
.dash-card-header {
    display: flex; align-items: center; gap: .5rem;
    padding: .85rem 1.25rem;
    border-bottom: 1px solid var(--tblr-border-color);
    background: #f8fafc;
}
.dash-card-header-title {
    font-size: .82rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    color: #475569;
}
.dash-card-body { padding: 1.1rem 1.25rem; }

/* Jerarquía */
.jerar-row {
    display: flex; align-items: center; gap: .75rem;
    padding: .6rem .85rem;
    border-radius: 8px;
    transition: background .15s;
    cursor: default;
}
.jerar-row:hover { background: #f1f5f9; }
.jerar-badge {
    font-size: .68rem; font-weight: 700; padding: .15rem .45rem;
    border-radius: 20px; white-space: nowrap;
}

/* Timeline asignaciones */
.timeline-item {
    display: flex; gap: .75rem;
    padding: .55rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.timeline-item:last-child { border-bottom: none; }
.timeline-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #2563eb; flex-shrink: 0; margin-top: .35rem;
}

/* Animación de entrada */
@keyframes fadeInUp {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
}
.dash-animate { animation: fadeInUp .45s ease both; }
.dash-animate:nth-child(1){animation-delay:.05s}
.dash-animate:nth-child(2){animation-delay:.10s}
.dash-animate:nth-child(3){animation-delay:.15s}
.dash-animate:nth-child(4){animation-delay:.20s}
.dash-animate:nth-child(5){animation-delay:.25s}
.dash-animate:nth-child(6){animation-delay:.30s}
</style>

<div class="page-header d-print-none" style="padding-bottom:0">
  <div class="container-xl">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h2 class="fw-bold mb-1" style="color:#1e293b">
          <i class="ti ti-layout-dashboard me-2 text-primary"></i>Dashboard — Inventario TI
        </h2>
        <div class="text-muted small">Resumen general de activos, equipos, estaciones y asignaciones</div>
      </div>
      <div class="text-muted small">
        <i class="ti ti-refresh me-1"></i>
        Actualizado: <?= date('d/m/Y H:i') ?>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
<div class="container-xl">
    <!-- ══════════════════════════════════════════
     ACCESOS RÁPIDOS — Cards de módulos
══════════════════════════════════════════ -->
<div class="row g-4 mb-4">
  <!-- Configuraciones -->
  <div class="col-12 col-md-6 col-lg-3">
    <a href="?module=inventario&action=activos" style="text-decoration:none">
      <div class="card shadow-sm card-modern h-100" style="border-radius:16px;transition:all .2s ease;border:1px solid var(--tblr-border-color)">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-primary-lt text-primary">
              <i class="ti ti-settings" style="font-size:24px"></i>
            </div>
            <div class="h2 mb-0 fw-bold text-dark"><?= $totalActivos ?></div>
          </div>
          <div class="fw-semibold text-dark">Configuraciones</div>
          <div class="text-muted small mb-3">Activos, tipos, características, ubicaciones e IPs del sistema.</div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex flex-wrap gap-1">
              <span class="badge bg-primary-lt text-primary" style="font-size:.65rem">Activos</span>
              <span class="badge bg-azure-lt text-azure" style="font-size:.65rem">IPs</span>
              <span class="badge bg-teal-lt text-teal" style="font-size:.65rem">Ubicaciones</span>
            </div>
            <i class="ti ti-arrow-right text-muted" style="font-size:20px;transition:all .2s"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <!-- Equipos -->
  <div class="col-12 col-md-6 col-lg-3">
    <a href="?module=inventario&action=equipos" style="text-decoration:none">
      <div class="card shadow-sm card-modern h-100" style="border-radius:16px;transition:all .2s ease;border:1px solid var(--tblr-border-color)">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-azure-lt text-azure">
              <i class="ti ti-devices" style="font-size:24px"></i>
            </div>
            <div class="h2 mb-0 fw-bold text-dark"><?= $totalEquipos ?></div>
          </div>
          <div class="fw-semibold text-dark">Gestión de Equipos</div>
          <div class="text-muted small mb-3">Administración y control de los equipos registrados en inventario.</div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-uppercase text-muted small fw-semibold">Equipos Totales</span>
            <i class="ti ti-arrow-right text-muted" style="font-size:20px"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <!-- Estaciones -->
  <div class="col-12 col-md-6 col-lg-3">
    <a href="?module=inventario&action=estaciones" style="text-decoration:none">
      <div class="card shadow-sm card-modern h-100" style="border-radius:16px;transition:all .2s ease;border:1px solid var(--tblr-border-color)">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-teal-lt text-teal">
              <i class="ti ti-desktop" style="font-size:24px"></i>
            </div>
            <div class="h2 mb-0 fw-bold text-dark"><?= $totalEstaciones ?></div>
          </div>
          <div class="fw-semibold text-dark">Estaciones</div>
          <div class="text-muted small mb-3">Puestos de trabajo con equipos y acceso remoto configurado.</div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2 small text-muted">
              <span><?= $totalCompletas ?> completas</span>
              <span>·</span>
              <span><?= $totalTerminales ?> terminales</span>
            </div>
            <i class="ti ti-arrow-right text-muted" style="font-size:20px"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <!-- Asignaciones -->
  <div class="col-12 col-md-6 col-lg-3">
    <a href="?module=inventario&action=asignaciones" style="text-decoration:none">
      <div class="card shadow-sm card-modern h-100" style="border-radius:16px;transition:all .2s ease;border:1px solid var(--tblr-border-color)">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center" class="bg-success-lt text-success">
              <i class="ti ti-user-check" style="font-size:24px"></i>
            </div>
            <div class="h2 mb-0 fw-bold text-dark"><?= $totalAsignados ?></div>
          </div>
          <div class="fw-semibold text-dark">Asignaciones</div>
          <div class="text-muted small mb-3">Control de asignación de estaciones y equipos a trabajadores.</div>
          <hr class="my-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="badge bg-success-lt text-success" style="font-size:.72rem"><?= $pctAsignadas ?>% asignadas</span>
              <span class="text-muted small ms-1"><?= $estSinAsignar ?> libres</span>
            </div>
            <i class="ti ti-arrow-right text-muted" style="font-size:20px"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

</div>

<!-- ══════════════════════════════════════════
     FILA 1: KPIs principales
══════════════════════════════════════════ -->
<div class="row g-3 mb-4">

  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $totalActivos ?></div>
        <div class="dash-kpi-label">Activos</div>
        <div class="dash-kpi-sub">Tipos registrados</div>
      </div>
      <div class="dash-kpi-icon bg-primary-lt text-primary ms-auto">
        <i class="ti ti-package"></i>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $totalEquipos ?></div>
        <div class="dash-kpi-label">Equipos</div>
        <div class="dash-kpi-sub">En inventario</div>
      </div>
      <div class="dash-kpi-icon bg-azure-lt text-azure ms-auto">
        <i class="ti ti-devices"></i>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $totalEstaciones ?></div>
        <div class="dash-kpi-label">Estaciones</div>
        <div class="dash-kpi-sub"><?= $totalCompletas ?> compl. · <?= $totalTerminales ?> term.</div>
      </div>
      <div class="dash-kpi-icon bg-teal-lt text-teal ms-auto">
        <i class="ti ti-desktop"></i>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $totalAsignados ?></div>
        <div class="dash-kpi-label">Asignaciones</div>
        <div class="dash-kpi-sub"><?= $pctAsignadas ?>% asignadas</div>
        <div class="dash-progress mt-1" style="width:80px">
          <div class="dash-progress-bar bg-success" style="width:<?= $pctAsignadas ?>%"></div>
        </div>
      </div>
      <div class="dash-kpi-icon bg-success-lt text-success ms-auto">
        <i class="ti ti-user-check"></i>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $ipsDisponibles ?></div>
        <div class="dash-kpi-label">IPs Libres</div>
        <div class="dash-kpi-sub"><?= $ipsAsignadas ?> asignadas</div>
        <div class="dash-progress mt-1" style="width:80px">
          <div class="dash-progress-bar bg-warning" style="width:<?= $pctIPs ?>%"></div>
        </div>
      </div>
      <div class="dash-kpi-icon bg-warning-lt text-warning ms-auto">
        <i class="ti ti-network"></i>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-4 col-lg-2 dash-animate">
    <div class="dash-kpi">
      <div>
        <div class="dash-kpi-val"><?= $estSinAsignar ?></div>
        <div class="dash-kpi-label">Sin Asignar</div>
        <div class="dash-kpi-sub">Estaciones libres</div>
      </div>
      <div class="dash-kpi-icon bg-red-lt text-red ms-auto">
        <i class="ti ti-alert-circle"></i>
      </div>
    </div>
  </div>

</div>

<!-- ══════════════════════════════════════════
     FILA 2: Gráficos — Equipos por tipo + IPs
══════════════════════════════════════════ -->
<div class="row g-3 mb-4">

  <!-- Equipos por tipo — Doughnut -->
  <div class="col-12 col-lg-4">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-chart-donut text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Equipos por Tipo</span>
      </div>
      <div class="dash-card-body d-flex align-items-center justify-content-center" style="min-height:260px">
        <canvas id="chartEquiposTipo" height="260"></canvas>
      </div>
    </div>
  </div>

  <!-- Estaciones por ubicación — Bar horizontal -->
  <div class="col-12 col-lg-5">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-chart-bar text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Estaciones por Ubicación</span>
      </div>
      <div class="dash-card-body" style="min-height:260px">
        <canvas id="chartEstUbicacion" height="260"></canvas>
      </div>
    </div>
  </div>

  <!-- IPs — Gauge simple -->
  <div class="col-12 col-lg-3">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-network text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Red / IPs</span>
      </div>
      <div class="dash-card-body d-flex flex-column align-items-center justify-content-center gap-3" style="min-height:260px">
        <canvas id="chartIPs" width="180" height="180"></canvas>
        <div class="d-flex gap-3 small">
          <div class="d-flex align-items-center gap-1">
            <div style="width:10px;height:10px;border-radius:50%;background:#ef4444"></div>
            <span class="text-muted">Asignadas <?= $ipsAsignadas ?></span>
          </div>
          <div class="d-flex align-items-center gap-1">
            <div style="width:10px;height:10px;border-radius:50%;background:#22c55e"></div>
            <span class="text-muted">Disponibles <?= $ipsDisponibles ?></span>
          </div>
        </div>
        <div class="text-center">
          <div style="font-size:1.5rem;font-weight:800;color:#1e293b"><?= $totalIPs ?></div>
          <div class="text-muted small">IPs registradas</div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ══════════════════════════════════════════
     FILA 3: Equipos por ambiente + últimas asignaciones
══════════════════════════════════════════ -->
<div class="row g-3 mb-4">

  <!-- Equipos por ambiente -->
  <div class="col-12 col-lg-7">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-building text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Equipos por Ambiente</span>
        <span class="badge bg-primary-lt text-primary ms-auto">Top <?= count($equiposPorAmbiente) ?></span>
      </div>
      <div class="dash-card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0" style="font-size:.82rem">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Ambiente</th>
                <th>Ubicación</th>
                <th class="text-end pe-3">Equipos</th>
                <th style="width:100px"></th>
              </tr>
            </thead>
            <tbody>
              <?php
              $maxAmb = max(1, max(array_column($equiposPorAmbiente, 'total') ?: [1]));
              foreach ($equiposPorAmbiente as $i => $row):
                  $pct = round(($row['total'] / $maxAmb) * 100);
                  $colors = ['bg-primary','bg-azure','bg-teal','bg-green','bg-yellow','bg-orange','bg-red','bg-purple'];
                  $col = $colors[$i % count($colors)];
              ?>
              <tr>
                <td class="ps-3 fw-medium"><?= htmlspecialchars($row['ambiente']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($row['ubicacion']) ?></td>
                <td class="text-end pe-3 fw-bold"><?= $row['total'] ?></td>
                <td class="pe-3">
                  <div class="dash-progress">
                    <div class="dash-progress-bar <?= $col ?>" style="width:<?= $pct ?>%"></div>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($equiposPorAmbiente)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">Sin datos</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Últimas asignaciones -->
  <div class="col-12 col-lg-5">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <i class="ti ti-clock text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Últimas Asignaciones</span>
        <a href="?module=inventario&action=asignaciones" class="ms-auto text-muted small" style="text-decoration:none">
          Ver todas <i class="ti ti-arrow-right"></i>
        </a>
      </div>
      <div class="dash-card-body">
        <?php if (empty($ultimasAsignaciones)): ?>
          <div class="text-center text-muted py-3 small">Sin asignaciones recientes</div>
        <?php else: ?>
          <?php foreach ($ultimasAsignaciones as $a): ?>
          <div class="timeline-item">
            <div class="timeline-dot mt-1"></div>
            <div class="flex-grow-1">
              <div class="fw-semibold small"><?= htmlspecialchars($a['nombreEstacion']) ?></div>
              <div class="text-muted" style="font-size:.75rem">
                <?= htmlspecialchars($a['trabajadorResponsable']) ?>
              </div>
              <div class="d-flex gap-2 mt-1">
                <?php if ($a['ambiente']): ?>
                <span class="badge bg-primary-lt text-primary" style="font-size:.65rem"><?= htmlspecialchars($a['ambiente']) ?></span>
                <?php endif; ?>
                <span class="badge badge-outline text-muted" style="font-size:.65rem"><?= fmtFecha($a['fechaAsignacion']) ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ══════════════════════════════════════════
     FILA 4: Jerarquía Ubicación → Ambientes → Estaciones
══════════════════════════════════════════ -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="dash-card">
      <div class="dash-card-header">
        <i class="ti ti-sitemap text-primary" style="font-size:1rem"></i>
        <span class="dash-card-header-title">Jerarquía de Ubicaciones</span>
        <span class="text-muted small ms-auto"><?= count($jerarquia) ?> ubicaciones</span>
      </div>
      <div class="dash-card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0" style="font-size:.83rem">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Ubicación</th>
                <th class="text-center">Ambientes</th>
                <th class="text-center">Estaciones Asignadas</th>
                <th class="text-center">Equipos</th>
                <th style="width:140px" class="pe-3">Distribución</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $maxEquiposJer = max(1, max(array_column($jerarquia, 'totalEquipos') ?: [1]));
              foreach ($jerarquia as $j):
                  $pct = round(($j['totalEquipos'] / $maxEquiposJer) * 100);
              ?>
              <tr>
                <td class="ps-3">
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-map-pin text-muted" style="font-size:.9rem"></i>
                    <span class="fw-semibold"><?= htmlspecialchars($j['ubicacion']) ?></span>
                  </div>
                </td>
                <td class="text-center">
                  <span class="jerar-badge bg-azure-lt text-azure"><?= $j['totalAmbientes'] ?></span>
                </td>
                <td class="text-center">
                  <span class="jerar-badge bg-teal-lt text-teal"><?= $j['totalEstaciones'] ?></span>
                </td>
                <td class="text-center">
                  <span class="fw-bold text-primary"><?= $j['totalEquipos'] ?></span>
                </td>
                <td class="pe-3">
                  <div class="d-flex align-items-center gap-2">
                    <div class="dash-progress flex-grow-1">
                      <div class="dash-progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="text-muted" style="font-size:.7rem;width:28px;text-align:right"><?= $pct ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($jerarquia)): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">Sin datos de ubicaciones</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>




<style>
.card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,.08) !important;
}
.card-modern:hover .ti-arrow-right {
    color: var(--tblr-primary) !important;
    transform: translateX(4px);
}
</style>

</div><!-- /container-xl -->
</div><!-- /page-body -->


<!-- ══════════════════════════════════════════
     GRÁFICOS — Chart.js
══════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const COLORS = ['#2563eb','#0891b2','#0d9488','#16a34a','#ca8a04','#ea580c','#dc2626','#7c3aed'];

    // ── Gráfico 1: Equipos por tipo (Doughnut) ──
    <?php
    $tipoLabels = json_encode(array_column($equiposPorTipo, 'tipo'));
    $tipoData   = json_encode(array_map('intval', array_column($equiposPorTipo, 'total')));
    ?>
    const ctxTipo = document.getElementById('chartEquiposTipo');
    if (ctxTipo) {
        new Chart(ctxTipo, {
            type: 'doughnut',
            data: {
                labels: <?= $tipoLabels ?>,
                datasets: [{
                    data: <?= $tipoData ?>,
                    backgroundColor: COLORS,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 12, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} equipos`
                        }
                    }
                }
            }
        });
    }

    // ── Gráfico 2: Estaciones por ubicación (Bar horizontal) ──
    <?php
    $ubLabels = json_encode(array_column($estPorUbicacion, 'ubicacion'));
    $ubData   = json_encode(array_map('intval', array_column($estPorUbicacion, 'total')));
    ?>
    const ctxUb = document.getElementById('chartEstUbicacion');
    if (ctxUb) {
        new Chart(ctxUb, {
            type: 'bar',
            data: {
                labels: <?= $ubLabels ?>,
                datasets: [{
                    label: 'Estaciones',
                    data: <?= $ubData ?>,
                    backgroundColor: COLORS.map(c => c + 'cc'),
                    borderColor: COLORS,
                    borderWidth: 1.5,
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: ctx => ` ${ctx.parsed.x} estaciones` }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 11 }, stepSize: 1 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            callback: function(val, idx) {
                                const label = this.getLabelForValue(val);
                                return label.length > 18 ? label.substr(0,18)+'…' : label;
                            }
                        }
                    }
                }
            }
        });
    }

    // ── Gráfico 3: IPs (Doughnut) ──
    const ctxIPs = document.getElementById('chartIPs');
    if (ctxIPs) {
        new Chart(ctxIPs, {
            type: 'doughnut',
            data: {
                labels: ['Asignadas', 'Disponibles'],
                datasets: [{
                    data: [<?= $ipsAsignadas ?>, <?= $ipsDisponibles ?>],
                    backgroundColor: ['#ef4444cc', '#22c55ecc'],
                    borderColor:     ['#ef4444',   '#22c55e'],
                    borderWidth: 2,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // Animar barras de progreso
    setTimeout(() => {
        document.querySelectorAll('.dash-progress-bar').forEach(el => {
            const w = el.style.width;
            el.style.width = '0%';
            setTimeout(() => el.style.width = w, 100);
        });
    }, 300);
});
</script>
