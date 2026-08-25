<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';
require_once $base_path . '/modules/laboratorio/muestra/models/MuestraModel.php';

Auth::check();

$id_bitacora = intval($_GET['id_bitacora'] ?? 0);
if ($id_bitacora <= 0) {
    echo '<div class="alert alert-danger">Bitácora inválida.</div>';
    exit;
}

$conn = Conexion::conectar();
if (!$conn) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos.</div>';
    exit;
}

$model = new MuestraModel($conn);

try {
    $bitacora = $model->obtenerBitacoraPorId($id_bitacora);
    if (!$bitacora) {
        throw new Exception('No se encontró la bitácora solicitada.');
    }

    $resultados = $model->obtenerResultadosPorBitacora($id_bitacora);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    exit;
}
?>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra&subaction=por_defecto">Por Defecto</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detalle Bitácora</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">Detalle de Bitácora #<?php echo intval($bitacora['Id_Bitacora'] ?? 0); ?></h2>
        <div class="text-muted mt-1">
          Fecha: <?php echo htmlspecialchars((string)($bitacora['Fecha_Registro'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> |
          Turno: <?php echo htmlspecialchars((string)($bitacora['Turno'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> |
          Muestras: <?php echo intval($bitacora['Total_Muestras'] ?? 0); ?>
        </div>
      </div>
      <div class="col-auto">
        <a class="btn btn-primary" href="?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_bitacora=<?php echo intval($bitacora['Id_Bitacora'] ?? 0); ?>&agricultor=<?php echo rawurlencode('Muestra por defecto'); ?>">
          <i class="ti ti-clipboard-data me-1"></i> Continuar análisis
        </a>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="card mb-3">
      <div class="card-body">
        <strong>Observación general:</strong>
        <span class="text-muted"><?php echo htmlspecialchars(trim((string)($bitacora['Observacion_General'] ?? '')) !== '' ? (string)$bitacora['Observacion_General'] : '(sin observación)', ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">Resultados por muestra</h3>
      </div>
      <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped">
          <thead>
            <tr>
              <th>ID Muestra</th>
              <th>Punto de toma</th>
              <th>Parámetro</th>
              <th>Unidad</th>
              <th>Valor hallado</th>
              <th>Estado muestra</th>
              <th>Observación</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($resultados)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No hay resultados registrados para esta bitácora.</td>
              </tr>
            <?php else: ?>
              <?php $ultimoIdMuestra = null; ?>
              <?php foreach ($resultados as $row): ?>
                <?php $esPrimeraFilaMuestra = (intval($row['Id_Muestra'] ?? 0) !== $ultimoIdMuestra); $ultimoIdMuestra = intval($row['Id_Muestra'] ?? 0); ?>
                <tr>
                  <td><?php echo intval($row['Id_Muestra'] ?? 0); ?></td>
                  <td><?php echo htmlspecialchars((string)($row['Punto_Toma'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string)($row['Parametro'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string)($row['Unidad'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(trim((string)($row['Valor_Hallado'] ?? '')) !== '' ? (string)$row['Valor_Hallado'] : '(pendiente)', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <?php if (!empty($row['No_Analizada'])): ?>
                      <span class="badge bg-danger">NO ANALIZADA</span>
                    <?php else: ?>
                      <?php echo htmlspecialchars((string)($row['Estado'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($esPrimeraFilaMuestra && trim((string)($row['Observacion_Muestra'] ?? '')) !== ''): ?>
                      <?php echo htmlspecialchars((string)$row['Observacion_Muestra'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
