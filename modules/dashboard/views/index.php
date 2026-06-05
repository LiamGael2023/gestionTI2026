<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">Resumen General</div>
        <h2 class="page-title">Dashboard</h2>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <span class="text-muted" style="font-size:0.85rem;"><?php echo date('d/m/Y'); ?></span>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    
    <!-- KPI Cards — Datos reales desde el sistema -->
    <?php
    if (!isset($conn) || !$conn) {
        $conn = Conexion::conectar();
    }
    require_once __DIR__ . '/../../modules/produccion_agraria/models/DashboardModel.php';
    $dashModel = new DashboardModel($conn);
    $resumen = $dashModel->getWidgetData('resumen_ejecutivo', []);
    $kpiRows = $resumen['rows'] ?? [];
    $kpiCards = [
        ['label' => 'Ventas Hoy',       'cls' => 'bg-azure-lt',   'icon' => 'ti ti-cash text-azure',           'color' => 'text-azure'],
        ['label' => 'Proformas Pend.',  'cls' => 'bg-orange-lt',  'icon' => 'ti ti-file-invoice text-orange',   'color' => 'text-orange'],
        ['label' => 'Stock Crítico',    'cls' => 'bg-red-lt',     'icon' => 'ti ti-alert-triangle text-red',    'color' => 'text-red'],
        ['label' => 'Vouchers Sin Asig.','cls' => 'bg-purple-lt', 'icon' => 'ti ti-credit-card text-purple',    'color' => 'text-purple'],
        ['label' => 'Mermas Hoy',       'cls' => 'bg-pink-lt',    'icon' => 'ti ti-trash text-pink',            'color' => 'text-pink'],
        ['label' => 'Valor Inventario', 'cls' => 'bg-green-lt',   'icon' => 'ti ti-coin text-green',            'color' => 'text-green'],
    ];
    ?>
    <div class="row row-deck row-cards mb-4">
      <?php foreach ($kpiRows as $i => $kpi): ?>
      <?php $card = $kpiCards[$i] ?? $kpiCards[0]; ?>
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader"><?php echo htmlspecialchars((string)($kpi['indicador'] ?? $card['label'])); ?></div>
            </div>
            <div class="d-flex align-items-baseline">
              <div class="h1 mb-0 me-2"><?php echo htmlspecialchars((string)($kpi['valor'] ?? '-')); ?></div>
              <div class="me-auto">
                <span class="<?php echo $card['color']; ?> d-inline-flex align-items-center lh-1">
                  <?php echo htmlspecialchars((string)($kpi['detalle'] ?? '')); ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Link al Dashboard CMS -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div>
                <i class="ti ti-layout-dashboard text-primary me-2" style="font-size:1.5rem;"></i>
              </div>
              <div>
                <h4 class="mb-1">Dashboard Personalizable</h4>
                <p class="text-muted mb-0">Arma tu propio panel con KPIs, gráficos y tablas personalizados.</p>
              </div>
              <div class="ms-auto">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria/dashboard" class="btn btn-primary">
                  <i class="ti ti-arrow-right me-1"></i>Ir al Dashboard CMS
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
