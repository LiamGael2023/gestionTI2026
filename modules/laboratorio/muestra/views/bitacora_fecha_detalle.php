<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';
require_once $base_path . '/modules/laboratorio/muestra/models/MuestraModel.php';

Auth::check();

$fecha = trim((string)($_GET['fecha'] ?? ''));
if ($fecha === '') {
    echo '<div class="alert alert-danger">Fecha inválida.</div>';
    exit;
}

$conn = Conexion::conectar();
if (!$conn) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos.</div>';
    exit;
}

$model = new MuestraModel($conn);

try {
    $bitacoras = $model->obtenerBitacorasPorFechaDefecto($fecha);
    $manana = $bitacoras['Mañana'];
    $tarde = $bitacoras['Tarde'];

    if (intval($manana['Id_Bitacora']) <= 0 && intval($tarde['Id_Bitacora']) <= 0) {
        throw new Exception('No existen bitácoras de mañana/tarde para la fecha seleccionada.');
    }

    $resultadosManana = intval($manana['Id_Bitacora']) > 0 ? $model->obtenerResultadosPorBitacora(intval($manana['Id_Bitacora'])) : [];
    $resultadosTarde = intval($tarde['Id_Bitacora']) > 0 ? $model->obtenerResultadosPorBitacora(intval($tarde['Id_Bitacora'])) : [];
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    exit;
}

function renderTablaResultados($rows) {
    if (empty($rows)) {
            echo '<tr><td colspan="7" class="text-center text-muted py-4">No hay resultados registrados para este turno.</td></tr>';
            return;
        }

        $ultimoIdMuestra = null;
        foreach ($rows as $row) {
            $esPrimeraFilaMuestra = (intval($row['Id_Muestra'] ?? 0) !== $ultimoIdMuestra);
            $ultimoIdMuestra = intval($row['Id_Muestra'] ?? 0);
            echo '<tr>';
            echo '<td>' . intval($row['Id_Muestra'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['Punto_Toma'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['Parametro'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['Unidad'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars(trim((string)($row['Valor_Hallado'] ?? '')) !== '' ? (string)$row['Valor_Hallado'] : '(pendiente)', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>';
            if (!empty($row['No_Analizada'])) {
                echo '<span class="badge bg-danger">NO ANALIZADA</span>';
            } else {
                echo htmlspecialchars((string)($row['Estado'] ?? '-'), ENT_QUOTES, 'UTF-8');
            }
            echo '</td>';
            echo '<td>';
            if ($esPrimeraFilaMuestra && trim((string)($row['Observacion_Muestra'] ?? '')) !== '') {
                echo htmlspecialchars((string)$row['Observacion_Muestra'], ENT_QUOTES, 'UTF-8');
            } else {
                echo '<span class="text-muted">-</span>';
            }
            echo '</td>';
            echo '</tr>';
        }
}
?>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra&subaction=por_defecto">Por Defecto</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detalle por Fecha</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">Bitácoras por fecha: <?php echo htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="text-muted mt-1">Se visualizan en la misma interfaz los turnos Mañana y Tarde.</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="row g-3">
      <div class="col-12 col-xl-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Turno Mañana</h3>
            <?php if (intval($manana['Id_Bitacora']) > 0): ?>
              <a class="btn btn-sm btn-primary" href="?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_bitacora=<?php echo intval($manana['Id_Bitacora']); ?>&agricultor=<?php echo rawurlencode('Muestra por defecto'); ?>">
                <i class="ti ti-clipboard-data me-1"></i> Continuar análisis
              </a>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <?php if (intval($manana['Id_Bitacora']) <= 0): ?>
              <div class="alert alert-warning mb-0">No existe bitácora para el turno Mañana en esta fecha.</div>
            <?php else: ?>
              <div class="mb-3">
                <div><strong>Bitácora:</strong> #<?php echo intval($manana['Id_Bitacora']); ?></div>
                <div><strong>Muestras:</strong> <?php echo intval($manana['Total_Muestras'] ?? 0); ?></div>
                <div><strong>Observación:</strong> <?php echo htmlspecialchars(trim((string)($manana['Observacion_General'] ?? '')) !== '' ? (string)$manana['Observacion_General'] : '(sin observación)', ENT_QUOTES, 'UTF-8'); ?></div>
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
                                            <th>Estado</th>
                                            <th>Observación</th>
                                          </tr>
                  </thead>
                  <tbody>
                    <?php renderTablaResultados($resultadosManana); ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Turno Tarde</h3>
            <?php if (intval($tarde['Id_Bitacora']) > 0): ?>
              <a class="btn btn-sm btn-primary" href="?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_bitacora=<?php echo intval($tarde['Id_Bitacora']); ?>&agricultor=<?php echo rawurlencode('Muestra por defecto'); ?>">
                <i class="ti ti-clipboard-data me-1"></i> Continuar análisis
              </a>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <?php if (intval($tarde['Id_Bitacora']) <= 0): ?>
              <div class="alert alert-warning mb-0">No existe bitácora para el turno Tarde en esta fecha.</div>
            <?php else: ?>
              <div class="mb-3">
                <div><strong>Bitácora:</strong> #<?php echo intval($tarde['Id_Bitacora']); ?></div>
                <div><strong>Muestras:</strong> <?php echo intval($tarde['Total_Muestras'] ?? 0); ?></div>
                <div><strong>Observación:</strong> <?php echo htmlspecialchars(trim((string)($tarde['Observacion_General'] ?? '')) !== '' ? (string)$tarde['Observacion_General'] : '(sin observación)', ENT_QUOTES, 'UTF-8'); ?></div>
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
                                            <th>Estado</th>
                                            <th>Observación</th>
                                          </tr>
                  </thead>
                  <tbody>
                    <?php renderTablaResultados($resultadosTarde); ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
