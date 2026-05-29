<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';
require_once $base_path . '/modules/laboratorio/muestra/models/ResultadoAnalisisModel.php';

Auth::check();

$log_file = dirname(__FILE__) . '/../../debug_analisis_proyecto.log';

$id_proyecto = intval($_GET['id_proyecto'] ?? 0);
if ($id_proyecto <= 0) {
    echo '<div class="alert alert-danger">ERROR: Proyecto inválido (id=' . $_GET['id_proyecto'] . ')</div>';
    exit;
}

$conn = Conexion::conectar();
if (!$conn) {
    echo '<div class="alert alert-danger">ERROR: No se pudo conectar a la BD</div>';
    exit;
}

// Solo el Analista Jefe (Id_Rol=2) o administrador puede finalizar resultados
$puede_finalizar = false;
$stmtPF = sqlsrv_query($conn, "SELECT TOP 1 1 FROM laboratorio.Usuario_Rol WHERE Id_Usuario = ? AND Id_Rol = 2", array($_SESSION['usuario_id']));
if ($stmtPF && sqlsrv_fetch_array($stmtPF, SQLSRV_FETCH_ASSOC)) {
    $puede_finalizar = true;
}
if (!$puede_finalizar) {
    $stmtAdm = sqlsrv_query($conn, "SELECT TOP 1 rol FROM comun.Usuarios WHERE id_usuario = ? AND activo = 1", array($_SESSION['usuario_id']));
    if ($stmtAdm) {
        $rowAdm = sqlsrv_fetch_array($stmtAdm, SQLSRV_FETCH_ASSOC);
        if ($rowAdm && in_array(strtolower(trim((string)$rowAdm['rol'])), ['administrador','admin','superadmin','super admin'], true)) {
            $puede_finalizar = true;
        }
    }
}
file_put_contents($log_file, "\n[" . date('Y-m-d H:i:s') . "] === CARGANDO analisis_proyecto.php ===\n", FILE_APPEND);
file_put_contents($log_file, "Id_Proyecto: $id_proyecto\n", FILE_APPEND);

// Obtener proyecto
$sql_proyecto = "SELECT * FROM laboratorio.Proyecto_Monitoreo WHERE Id_Proyecto = ? AND Activo = 1";
$stmt_proyecto = sqlsrv_query($conn, $sql_proyecto, array($id_proyecto));
if (!$stmt_proyecto || !($proyecto = sqlsrv_fetch_array($stmt_proyecto, SQLSRV_FETCH_ASSOC))) {
    file_put_contents($log_file, "ERROR: Proyecto no encontrado\n", FILE_APPEND);
    echo '<div class="alert alert-danger">ERROR: Proyecto ID ' . $id_proyecto . ' no encontrado</div>';
    exit;
}

file_put_contents($log_file, "Proyecto encontrado: " . $proyecto['Nombre_Proyecto'] . "\n", FILE_APPEND);

// Detectar si el proyecto es calidad de agua o drenes (para mostrar columna fuente)
$es_cc_proyecto    = intval($proyecto['Es_Control_Calidad'] ?? 0) === 1;
$es_drene_proyecto = intval($proyecto['Es_Drene'] ?? 0) === 1;
$mostrar_fuente    = $es_cc_proyecto || $es_drene_proyecto;

// Obtener muestras del proyecto con su número de orden
$sql_muestras = "SELECT m.Id_Muestra, ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden,
                 m.Tipo_Servicio,
                 da.Nivel_Agua
                 FROM laboratorio.Muestra_Lab m
                 LEFT JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra AND da.Activo = 1
                 WHERE m.Id_Proyecto = ? AND m.Activo = 1
                 ORDER BY m.Id_Muestra";
$stmt_muestras = sqlsrv_query($conn, $sql_muestras, array($id_proyecto));
$muestras = [];
$ids_muestras = [];
while ($row = sqlsrv_fetch_array($stmt_muestras, SQLSRV_FETCH_ASSOC)) {
    $muestras[] = $row;
    $ids_muestras[] = intval($row['Id_Muestra'] ?? 0);
}

file_put_contents($log_file, "Muestras encontradas: " . count($muestras) . "\n", FILE_APPEND);

if (empty($muestras)) {
    file_put_contents($log_file, "ERROR: No hay muestras para este proyecto\n", FILE_APPEND);
    echo '<div class="alert alert-warning">ERROR: No hay muestras creadas para este proyecto</div>';
    exit;
}

// Reparar resultados faltantes en muestras ya creadas (autocuración de data histórica).
$resultado_model = new ResultadoAnalisisModel($conn);
$blancos_creados = 0;
foreach ($ids_muestras as $id_muestra_tmp) {
  try {
    $ids_creados_tmp = $resultado_model->crearBlancosPorMuestra(intval($id_muestra_tmp));
    $blancos_creados += count($ids_creados_tmp);
  } catch (Exception $e) {
    file_put_contents($log_file, "WARN: No se pudieron crear blancos para muestra {$id_muestra_tmp}: " . $e->getMessage() . "\n", FILE_APPEND);
  }
}
file_put_contents($log_file, "Blancos autocreados en carga: {$blancos_creados}\n", FILE_APPEND);

// Obtener TODOS los parámetros del sistema (del proyecto actual)
$sql_parametros = "SELECT DISTINCT pa.Id_Parametro, pa.Nombre, pa.Unidad_Medida, pa.Categoria 
                   FROM laboratorio.Parametro_Analisis pa
                   INNER JOIN laboratorio.Solicitud_Analisis sa ON pa.Id_Servicio = sa.Id_Servicio OR pa.Id_Servicio IS NULL
                   INNER JOIN laboratorio.Muestra_Lab ml ON sa.Id_Muestra = ml.Id_Muestra
                   WHERE ml.Id_Proyecto = ? AND pa.Activo = 1
                   ORDER BY pa.Categoria, pa.Nombre";
$stmt_parametros = sqlsrv_query($conn, $sql_parametros, array($id_proyecto));
if (!$stmt_parametros) {
    $error_msg = print_r(sqlsrv_errors(), true);
    file_put_contents($log_file, "ERROR SQL (parametros): $error_msg\n", FILE_APPEND);
    echo '<div class="alert alert-danger"><strong>ERROR SQL (parámetros):</strong><br><pre>' . htmlspecialchars($error_msg) . '</pre></div>';
    exit;
}
$parametros_todos = [];
while ($row = sqlsrv_fetch_array($stmt_parametros, SQLSRV_FETCH_ASSOC)) {
  $parametros_todos[] = $row;
}

file_put_contents($log_file, "Parámetros encontrados: " . count($parametros_todos) . "\n", FILE_APPEND);

// Obtener todos los Resultado_Analisis para este proyecto
$sql_resultados = "SELECT 
                    ra.Id_Resultado,
                    ra.Id_Parametro,
                    ra.Valor_Hallado,
                    sa.Id_Muestra
                   FROM laboratorio.Resultado_Analisis ra
                   INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                   WHERE sa.Id_Muestra IN (
                       SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Id_Proyecto = ? AND Activo = 1
                   )
                   AND ra.Activo = 1
                   AND sa.Activo = 1";

file_put_contents($log_file, "Ejecutando consulta de resultados...\n", FILE_APPEND);

$stmt_resultados = sqlsrv_query($conn, $sql_resultados, array($id_proyecto));
if (!$stmt_resultados) {
    $error_msg = print_r(sqlsrv_errors(), true);
    file_put_contents($log_file, "ERROR SQL: $error_msg\n", FILE_APPEND);
    echo '<div class="alert alert-danger"><strong>ERROR SQL:</strong><br><pre>' . htmlspecialchars($error_msg) . '</pre></div>';
    exit;
}

// Crear un array clave: Id_Muestra_Id_Parametro => [Id_Resultado, Valor_Hallado]
$resultados = [];
while ($row = sqlsrv_fetch_array($stmt_resultados, SQLSRV_FETCH_ASSOC)) {
    $key = $row['Id_Muestra'] . '_' . $row['Id_Parametro'];
    $resultados[$key] = [
        'Id_Resultado' => $row['Id_Resultado'],
        'Valor_Hallado' => $row['Valor_Hallado']
    ];
}

file_put_contents($log_file, "Resultados encontrados: " . count($resultados) . "\n", FILE_APPEND);

if (empty($resultados)) {
    file_put_contents($log_file, "ERROR: No hay resultados para este proyecto\n", FILE_APPEND);
    echo '<div class="alert alert-warning"><strong>No hay resultados</strong>: Verifica que los Resultado_Analisis se crearon correctamente durante "Iniciar Ejecución"</div>';
    exit;
}

file_put_contents($log_file, "✓ Página cargada exitosamente\n", FILE_APPEND);

// Detectar muestras con análisis EXTRA (servicios fuera del plan original del proyecto)
$muestras_extra_set = [];
if (!empty($ids_muestras)) {
    $ph_extra = implode(',', array_fill(0, count($ids_muestras), '?'));
    $sql_extra = "SELECT DISTINCT sa.Id_Muestra
                  FROM laboratorio.Solicitud_Analisis sa
                  WHERE sa.Id_Muestra IN ($ph_extra)
                    AND sa.Activo = 1
                    AND sa.Id_Servicio NOT IN (
                        SELECT ps.Id_Servicio
                        FROM laboratorio.Proyecto_Detalle_Analisis pda
                        INNER JOIN laboratorio.Producto_Servicio ps
                            ON ps.Id_Producto = pda.Id_Producto_Venta AND ps.Activo = 1
                        WHERE pda.Id_Proyecto = ? AND pda.Activo = 1
                    )";
    $params_extra = array_merge(array_values($ids_muestras), [$id_proyecto]);
    $stmt_extra = sqlsrv_query($conn, $sql_extra, $params_extra);
    if ($stmt_extra) {
        while ($row_ex = sqlsrv_fetch_array($stmt_extra, SQLSRV_FETCH_ASSOC)) {
            $muestras_extra_set[intval($row_ex['Id_Muestra'])] = true;
        }
    }
}

// Obtener nombre del usuario (recepcionista)
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario desconocido';

// Detectar si el proyecto está finalizado (modo solo lectura)
$es_finalizado = isset($proyecto['Estado']) && $proyecto['Estado'] === 'Finalizado';
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
    body { background-color: #f5f7fb; font-size: 14px; }
    .text-muted { color: #6c757d; }
    .alert-info {
        background-color: #e8f4f8;
        border-left: 4px solid #17a2b8;
    }
    .alert-warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }
    .badge {
        font-size: 1.10em;
        padding: 0.5em 0.75em;
    }
    .table-responsive { margin-bottom: 0; }
    .table { margin-bottom: 0; }
    .table input[type="number"] {
        width: 100%;
        text-align: center;
        padding: 6px 4px;
        font-size: 0.95em;
    }
    .table input[type="number"]:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        outline: none;
    }
    .table input:disabled {
        background-color: #f8f9fa;
        cursor: not-allowed;
        color: #333;
        font-weight: 400;
        border-color: #dee2e6;
    }
    .param-header {
        font-weight: 600;
        text-align: center;
        white-space: normal;
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1.2;
        padding: 6px 4px;
    }
    .param-nombre {
        font-size: 0.9em;
        font-weight: 600;
    }
    .param-unidad {
        font-size: 0.8em;
        color: #666;
        margin-top: 3px;
        font-weight: 400;
    }
    .col-muestra {
      background-color: #f8f9fa;
        font-weight: 600;
        min-width: 50px;
        font-size: 0.95em;
        text-align: center;
    }
    .param-input {
        text-align: center;
    }
    .param-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }
    .card-header { padding: 12px 15px; }
    .card-body { padding: 15px; }
    .card {
      border-radius: 10px;
      border: 1px solid #dee2e6;
      box-shadow: 0 3px 12px rgba(15, 23, 42, 0.06);
    }
    .tabla-excel-wrap {
      border: 1px solid #dee2e6;
      border-radius: 10px;
      overflow: auto;
      max-height: calc(100vh - 280px);
      background-color: #fff;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }
    .tabla-excel-wrap::-webkit-scrollbar {
      height: 10px;
      width: 10px;
    }
    .tabla-excel-wrap::-webkit-scrollbar-thumb {
      background-color: #cdd5df;
      border-radius: 999px;
      border: 2px solid #f8f9fa;
    }
    .tabla-excel-wrap::-webkit-scrollbar-track {
      background-color: #f5f7fa;
    }
    .tabla-excel {
      min-width: 1800px;
      border-collapse: separate;
      border-spacing: 0;
    }
    .tabla-excel thead th {
      position: sticky;
      top: 0;
      z-index: 5;
      background-color: #f8f9fa;
      color: #333;
      border: 1px solid #dee2e6;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      font-size: 0.84em;
      vertical-align: middle;
      text-align: center;
      padding: 10px 8px;
    }
    .tabla-excel .excel-title-row th {
      font-size: 0.96em;
      font-weight: 700;
      background-color: #f1f3f5;
      border-color: #dee2e6;
      white-space: normal;
      line-height: 1.25;
      padding: 10px 8px;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }
    .tabla-excel tbody td {
      border: 1px solid #e9ecef;
      background-color: #fff;
      padding: 6px 6px;
      transition: background-color 0.15s ease;
    }
    .tabla-excel tbody tr:nth-child(even) td {
      background-color: #f8f9fa;
    }
    .tabla-excel tbody tr:hover td {
      background-color: #f1f6ff;
    }
    .tabla-excel .sticky-left {
      position: sticky;
      z-index: 4;
      background-color: #f8f9fa;
      border-right: 1px solid #dee2e6;
    }
    .tabla-excel thead .sticky-left {
      z-index: 7;
      background-color: #f8f9fa;
    }
    .tabla-excel .sticky-left-1 { left: 0; min-width: 54px; width: 54px; }
    .tabla-excel .sticky-left-2 {
      left: 54px;
      min-width: 56px;
      width: 56px;
      box-shadow: 4px 0 8px -6px rgba(0, 0, 0, 0.18);
    }
    .tabla-excel .param-col { min-width: 128px; width: 128px; }
    .tabla-excel .param-input,
    .tabla-excel input[disabled] {
      min-width: 90px;
    }
    .tabla-excel .param-input {
      border: 1px solid #ced4da;
      background-color: #fff;
      font-size: 0.9em;
      font-weight: 600;
      color: #212529;
      border-radius: 6px;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .tabla-excel .param-input:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.18rem rgba(102, 126, 234, 0.15);
    }
    .tabla-excel .excel-unidad {
      display: block;
      margin-top: 2px;
      font-size: 0.8em;
      font-weight: 600;
      color: #6c757d;
      opacity: 0.95;
      text-transform: none;
    }
    .tabla-excel .col-muestra {
      font-weight: 700;
      color: #344054;
    }
    @media (max-width: 992px) {
      .tabla-excel-wrap {
        max-height: calc(100vh - 240px);
      }
    }
    /* === Filas especiales === */
    .fila-extra td {
      background-color: #BDD7EE !important;
    }
    .fila-extra td.sticky-left {
      background-color: #9DC3E6 !important;
    }
    .fila-consumo-agua td.sticky-left-1 {
      border-left: 3px solid #1565C0 !important;
    }
    .fila-extra .param-input {
      background-color: #dbeafe !important;
    }
    .btn-ac-row {
      font-size: 0.8em;
      padding: 2px 5px !important;
      line-height: 1;
    }
    .tabla-excel .col-fuente {
      min-width: 200px;
      width: 200px;
      white-space: normal;
      word-break: break-word;
      font-size: 0.82em;
      color: #344054;
      text-align: left;
      vertical-align: middle;
    }
</style>
</head>
<body>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Análisis de Muestra</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">ANÁLISIS DE MUESTRAS</h2>
        <div class="text-muted mt-1">Ingrese los valores obtenidos en el laboratorio. Solo se encuentran habilitados los casilleros correspondientes a los parámetros solicitados en la orden de servicio</div>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <span class="badge" style="background-color: #28a745; color: white;">
          <?php echo htmlspecialchars($proyecto['Nombre_Proyecto']); ?>
        </span>
      </div>
      <div class="col-auto">
        <span class="badge" style="background-color: #004d99; color: white;">
          Valle <?php echo htmlspecialchars($proyecto['Valle']); ?>
        </span>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid px-2 px-md-3">
    
    <div class="alert alert-info" role="alert">
      <div>
        <strong><i class="ti ti-info-circle me-2"></i>Ingrese los valores del análisis</strong>
        <br>
        <div>Solo se encuentran habilitados los casilleros correspondientes a los parámetros solicitados. Los campos bloqueados no forman parte del análisis contratado.</div>
        <div style="margin-top: 6px; display: block;">Recepcionista de la muestra: <strong><?php echo htmlspecialchars($usuario_nombre); ?></strong></div>
      </div>
    </div>

    <form id="form-resultados" onsubmit="<?php echo $es_finalizado ? 'return false;' : 'guardarResultados(event)'; ?>">
      
      <?php if ($es_finalizado): ?>
        <div class="alert alert-warning" role="alert">
          <i class="ti ti-lock me-2"></i>
          <strong>Modo Solo Lectura:</strong> Este monitoreo ha sido finalizado. Los resultados se muestran como referencia. No es posible realizar modificaciones.
        </div>
      <?php endif; ?>
      
      <div class="card mb-3">
        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; padding: 12px 15px;">
          <h3 class="card-title" style="font-size: 1.05em; color: #333; margin: 0; display: block;">
            <i class="ti ti-table me-2" style="color: #004d99;"></i>Tabla consolidada de resultados
          </h3>
          <div class="text-muted" style="font-size: 0.9em; line-height: 1.5; display: block; margin-top: 6px;">
            Vista horizontal tipo reporte. Desplácese lateralmente para completar todos los parámetros.
          </div>
        </div>
        <div class="card-body pt-3">
          <div class="tabla-excel-wrap">
            <table class="table table-vcenter tabla-excel">
              <thead>
                <tr class="excel-title-row">
                  <th colspan="<?php echo 2 + ($mostrar_fuente ? 1 : 0) + count($parametros_todos); ?>">
                    RESULTADOS ANÁLISIS DE <?php echo strtoupper(htmlspecialchars($proyecto['Nombre_Proyecto'])); ?>
                    <br>
                    <?php echo strtoupper(htmlspecialchars($proyecto['Temporada'] ?? '')); ?> - VALLE <?php echo strtoupper(htmlspecialchars($proyecto['Valle'] ?? '')); ?>
                  </th>
                </tr>
                <tr>
                  <th class="sticky-left sticky-left-1">Ac</th>
                  <th class="sticky-left sticky-left-2">No</th>
                  <?php if ($mostrar_fuente): ?>
                    <th class="param-col col-fuente"><?php echo $es_drene_proyecto ? 'Dren' : 'Nivel'; ?></th>
                  <?php endif; ?>
                  <?php foreach ($parametros_todos as $param): ?>
                    <th class="param-col">
                      <?php echo htmlspecialchars($param['Nombre']); ?>
                      <span class="excel-unidad"><?php echo htmlspecialchars($param['Unidad_Medida'] ?? '-'); ?></span>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($muestras as $muestra):
                  $es_consumo_humano = (isset($muestra['Tipo_Servicio']) && trim((string)$muestra['Tipo_Servicio']) === 'Consumo Humano');
                  $es_consumo_agua   = (isset($muestra['Tipo_Servicio']) && trim((string)$muestra['Tipo_Servicio']) === 'Consumo de Agua');
                  $tipo_servicio_val  = trim((string)($muestra['Tipo_Servicio'] ?? ''));
                  $es_extra = isset($muestras_extra_set[intval($muestra['Id_Muestra'])]);
                  $row_classes = trim(($es_extra ? 'fila-extra' : '') . ' ' . (($es_consumo_humano || $es_consumo_agua) ? 'fila-consumo-agua' : ''));
                ?>
                  <tr data-muestra-id="<?php echo intval($muestra['Id_Muestra']); ?>" <?php if($row_classes): ?>class="<?php echo htmlspecialchars($row_classes); ?>"<?php endif; ?>>
                    <td class="sticky-left sticky-left-1 col-muestra" style="padding:2px;">
                      <?php if (!$es_finalizado): ?>
                        <button type="button" class="btn btn-sm btn-ghost-secondary btn-ac-row"
                                title="Acciones"
                          onclick="abrirMenuAccion(<?php echo intval($muestra['Id_Muestra']); ?>, <?php echo intval($muestra['NumeroOrden']); ?>, '<?php echo addslashes($tipo_servicio_val); ?>'); return false;">
                          <?php if ($es_consumo_humano): ?>
                            <i class="ti ti-user-check" style="color:#1565C0;" title="Consumo Humano"></i>
                          <?php elseif ($es_consumo_agua): ?>
                            <i class="ti ti-droplet" style="color:#1565C0;" title="Consumo de Agua"></i>
                          <?php else: ?>
                            <i class="ti ti-dots-vertical" style="color:#888;"></i>
                          <?php endif; ?>
                        </button>
                      <?php else: ?>
                        <?php if ($es_consumo_humano): ?>
                          <i class="ti ti-user-check" style="color:#1565C0;" title="Consumo Humano"></i>
                        <?php elseif ($es_consumo_agua): ?>
                          <i class="ti ti-droplet" style="color:#1565C0;" title="Consumo de Agua"></i>
                        <?php else: ?>
                          <i class="ti ti-files" style="color:#aaa;"></i>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td class="sticky-left sticky-left-2 col-muestra"><?php echo $muestra['NumeroOrden']; ?></td>
                    <?php if ($mostrar_fuente): ?>
                      <td class="col-fuente"><?php echo htmlspecialchars($muestra['Nivel_Agua'] ?? '—'); ?></td>
                    <?php endif; ?>
                    <?php foreach ($parametros_todos as $param):
                      $key = $muestra['Id_Muestra'] . '_' . $param['Id_Parametro'];
                      $existe = isset($resultados[$key]);
                      ?>
                      <td>
                        <?php if ($existe):
                          $resultado = $resultados[$key];
                          ?>
                          <input type="number"
                                 step="0.01"
                                 placeholder="0.00"
                                 class="form-control form-control-sm param-input"
                                 data-resultado="<?php echo intval($resultado['Id_Resultado']); ?>"
                                 data-parametro="<?php echo htmlspecialchars($param['Nombre']); ?>"
                                 data-unidad="<?php echo htmlspecialchars($param['Unidad_Medida'] ?? '-'); ?>"
                                 data-muestra="<?php echo intval($muestra['NumeroOrden']); ?>"
                                 <?php if ($es_finalizado) echo 'disabled'; ?>
                                 value="<?php echo $resultado['Valor_Hallado'] !== null ? floatval($resultado['Valor_Hallado']) : ''; ?>">
                        <?php else: ?>
                          <input type="text" class="form-control form-control-sm" disabled value="—" style="text-align: center; background-color: #f8f9fa;">
                        <?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="row mt-4 mb-3" style="gap: 10px;">
        <div class="col-auto">
          <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()" style="font-size: 0.95em; padding: 8px 14px;">
            <i class="ti ti-arrow-left me-2"></i> Volver
          </button>
        </div>
        <div class="col-auto ms-auto" style="display: flex; gap: 10px;">
          <button type="button" class="btn btn-outline-success" onclick="exportarProyectoMonitoreo(idProyecto)" style="font-size: 0.95em; padding: 8px 14px;">
            <i class="ti ti-file-spreadsheet me-2"></i> DESCARGAR EXCEL
          </button>
          <?php if (!$es_finalizado): ?>
          <button type="button" class="btn btn-outline-primary" onclick="agregarMuestrasProyectoAnalisis()" style="font-size: 0.95em; padding: 8px 14px;">
            <i class="ti ti-plus me-2"></i> AGREGAR MUESTRAS
          </button>
          <button type="button" class="btn btn-outline-success" onclick="guardarAvance()" style="font-size: 0.95em; padding: 8px 14px;">
            <i class="ti ti-device-floppy me-2"></i> GUARDAR AVANCE
          </button>
          <?php if ($puede_finalizar): ?>
          <button type="submit" class="btn btn-success" style="background: #28a745; border: none; font-size: 0.95em; padding: 8px 18px;">
            <i class="ti ti-check me-2"></i> GRABAR RESULTADOS
          </button>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

    </form>
    </div>

  </div>
</div>

<div class="modal fade" id="modal-consumo-extra" tabindex="-1" aria-labelledby="modal-consumo-extra-label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-consumo-extra-label"><i class="ti ti-beaker me-2"></i>Consumo extra de reactivos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="chk_consumo_extra_modal">
          <label class="form-check-label" for="chk_consumo_extra_modal"><strong>Registrar consumo extra y/o residuo adicional</strong></label>
        </div>

        <div id="bloque_consumo_extra_modal" style="display:none;">
          <div class="mb-2">
            <label class="form-label">Muestra</label>
            <select id="id_muestra_extra_modal" class="form-select form-select-sm">
              <option value="">Seleccione...</option>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label d-block">Tipo de descuento</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="tipo_descuento_extra_modal" id="tipo_descuento_analisis_modal" value="analisis" checked>
              <label class="form-check-label" for="tipo_descuento_analisis_modal">Por analisis a repetir</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="tipo_descuento_extra_modal" id="tipo_descuento_manual_modal" value="manual">
              <label class="form-check-label" for="tipo_descuento_manual_modal">Manual por reactivo</label>
            </div>
          </div>

          <div id="bloque_tipo_analisis_modal">
            <div class="mb-2">
              <label class="form-label">Analisis / servicio a repetir</label>
              <select id="id_servicio_extra_modal" class="form-select form-select-sm">
                <option value="">Seleccione...</option>
              </select>
              <small class="text-muted">Los residuos del servicio se descontarán automáticamente según la cantidad indicada.</small>
            </div>
            <div class="mb-2">
              <label class="form-label">Cantidad equivalente de muestras</label>
              <input id="factor_extra_modal" class="form-control form-control-sm" type="number" min="0.01" step="0.01" value="1">
            </div>
          </div>

          <div id="bloque_tipo_manual_modal" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label mb-0">Reactivos manuales</label>
              <button type="button" id="btn_add_manual_modal" class="btn btn-sm btn-outline-primary">Agregar</button>
            </div>
            <div id="lista_manual_modal"></div>
          </div>

          <hr class="my-2">

          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label mb-0">Residuos adicionales (opcional)</label>
            <button type="button" id="btn_add_residuo_modal" class="btn btn-sm btn-outline-secondary">Agregar</button>
          </div>
          <div id="lista_residuos_modal"></div>

          <div class="mt-2">
            <label class="form-label">Nota</label>
            <textarea id="nota_extra_modal" class="form-control form-control-sm" rows="2" placeholder="Detalle del consumo extra (opcional)"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continuar sin consumo extra</button>
        <button type="button" id="btn_confirmar_consumo_extra_modal" class="btn btn-primary">Aplicar consumo extra</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const idProyecto = <?php echo $id_proyecto; ?>;
const apiUrl = '/gestionTI/modules/laboratorio/muestra/controllers/AnalisisAPI.php';
const apiCreacionMasivaUrl = '/gestionTI/modules/laboratorio/muestra/views/creacion_masiva_api.php';
const idsMuestrasContext = <?php echo json_encode(array_values(array_map('intval', $ids_muestras))); ?>;
let contextoConsumoExtraCache = null;

function exportarProyectoMonitoreo(idProyectoExport) {
  $.ajax({
    url: apiCreacionMasivaUrl + '?action=obtenerCategoriasLimite&id_proyecto=' + encodeURIComponent(idProyectoExport),
    method: 'GET',
    dataType: 'json',
    success: function(response) {
      const categorias = (response && response.success && Array.isArray(response.categorias)) ? response.categorias : [];

      if (!categorias.length) {
        Swal.fire('Aviso', 'No se encontraron categorías de límites para este proyecto.', 'warning');
        return;
      }

      let htmlChecks = '<div style="text-align:left; max-height:260px; overflow:auto;">';
      categorias.forEach(function(item, idx) {
        const desc = String((item && item.descripcion) ? item.descripcion : '').trim();
        if (!desc) {
          return;
        }
        const idChk = 'cat_lim_ap_' + idx;
        htmlChecks += '' +
          '<div class="form-check mb-2">' +
            '<input class="form-check-input chk-cat-lim" type="checkbox" id="' + idChk + '" value="' + escapeHtml(desc) + '" checked>' +
            '<label class="form-check-label" for="' + idChk + '">' + escapeHtml(desc) + '</label>' +
          '</div>';
      });
      htmlChecks += '</div>';

      Swal.fire({
        title: 'Seleccionar límites de comparación',
        html: '<p class="text-muted" style="text-align:left;">Selecciona las categorías (Descripción) que se usarán para marcar resultados en rojo.</p>' + htmlChecks,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Exportar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        preConfirm: function() {
          const seleccionadas = [];
          $('.swal2-container .chk-cat-lim:checked').each(function() {
            const val = String($(this).val() || '').trim();
            if (val) {
              seleccionadas.push(val);
            }
          });

          if (!seleccionadas.length) {
            Swal.showValidationMessage('Debes seleccionar al menos una categoría de límites.');
            return false;
          }

          return seleccionadas;
        }
      }).then(function(result) {
        if (!result.isConfirmed || !Array.isArray(result.value)) {
          return;
        }

        const params = new URLSearchParams();
        params.set('id_proyecto', String(idProyectoExport));
        result.value.forEach(function(cat) {
          params.append('categorias[]', cat);
        });

        window.location.href = 'modules/laboratorio/muestra/controllers/ExportarProyectoMonitoreo.php?' + params.toString();
      });
    },
    error: function(xhr) {
      let msg = 'No se pudieron cargar las categorías de límites para exportar.';
      try {
        const parsed = JSON.parse(xhr.responseText || '{}');
        if (parsed.error) {
          msg = parsed.error;
        }
      } catch (e) {}
      Swal.fire('Error', msg, 'error');
    }
  });
}

function escapeHtml(text) {
  return String(text || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// ── Menú de acciones (reemplaza Bootstrap dropdown — evita overflow:auto) ────
function abrirMenuAccion(idMuestra, numMuestra, tipoServicio) {
  var yaConsumoHumano = (tipoServicio === 'Consumo Humano');
  var tieneTipo = (tipoServicio !== '');
  var textoEstado = tieneTipo ? escapeHtml(tipoServicio) : 'Sin designación';
  var estiloEstado = yaConsumoHumano
    ? 'background:#e8f1ff;color:#0b5ed7;border:1px solid #b6d4fe;'
    : (tieneTipo
      ? 'background:#e6f8f3;color:#0f5132;border:1px solid #a3cfbb;'
      : 'background:#f1f3f5;color:#495057;border:1px solid #dee2e6;');
  var labelCH = yaConsumoHumano ? 'Reconfigurar Consumo Humano (con análisis extra)' : 'Marcar Consumo Humano (con análisis extra)';

  var html =
    '<div style="text-align:left; font-size:0.95em;">' +
      '<div style="border:1px solid #e9ecef;background:#f8f9fa;border-radius:8px;padding:10px 12px;margin-bottom:12px;">' +
        '<div style="font-size:0.82em;text-transform:uppercase;letter-spacing:0.04em;color:#6c757d;margin-bottom:6px;">Estado actual</div>' +
        '<span style="display:inline-block;padding:6px 10px;border-radius:999px;font-weight:600;font-size:0.9em;' + estiloEstado + '">' + textoEstado + '</span>' +
      '</div>' +

      '<div class="form-check mb-2">' +
        '<input class="form-check-input" type="radio" name="accion_muestra" id="accion_consumo_humano" value="consumo_humano" checked>' +
        '<label class="form-check-label" for="accion_consumo_humano">' +
          '<strong>' + labelCH + '</strong><br>' +
          '<span class="text-muted">Abre el flujo obligatorio para seleccionar servicio extra.</span>' +
        '</label>' +
      '</div>' +

      '<div class="form-check mb-2">' +
        '<input class="form-check-input" type="radio" name="accion_muestra" id="accion_extra" value="extra_analisis">' +
        '<label class="form-check-label" for="accion_extra">' +
          '<strong>Agregar Análisis Extra</strong><br>' +
          '<span class="text-muted">Solo agrega un servicio adicional sin cambiar tipo de muestra.</span>' +
        '</label>' +
      '</div>' +

      (tieneTipo
        ? '<div class="form-check mb-1">' +
            '<input class="form-check-input" type="radio" name="accion_muestra" id="accion_quitar" value="quitar_tipo">' +
            '<label class="form-check-label text-danger" for="accion_quitar">' +
              '<strong>Quitar designación actual</strong>' +
            '</label>' +
          '</div>'
        : '') +

      '<div class="small text-muted mt-3">Selecciona una opción y presiona Continuar.</div>' +
    '</div>';

  Swal.fire({
    title: 'Acciones de Muestra #' + numMuestra,
    html: html,
    showCloseButton: true,
    showCancelButton: true,
    confirmButtonText: 'Continuar',
    cancelButtonText: 'Cerrar',
    width: 560,
    padding: '1rem',
    preConfirm: function() {
      var sel = document.querySelector('.swal2-container input[name="accion_muestra"]:checked');
      if (!sel || !sel.value) {
        Swal.showValidationMessage('Debes seleccionar una acción');
        return false;
      }
      return sel.value;
    }
  }).then(function(result) {
    if (!result.isConfirmed) return;

    if (result.value === 'consumo_humano') {
      configurarConsumoHumano(idMuestra, numMuestra);
      return;
    }

    if (result.value === 'extra_analisis') {
      abrirExtraAnalisis(idMuestra, numMuestra);
      return;
    }

    if (result.value === 'quitar_tipo') {
      quitarTipoServicio(idMuestra);
    }
  });
}

// ── Quitar tipo de servicio ──────────────────────────────────────────────────
function quitarTipoServicio(idMuestra) {
  Swal.fire({
    title: 'Quitar designación',
    text: '¿Eliminar el tipo de servicio asignado a esta muestra?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, quitar',
    cancelButtonText: 'Cancelar'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    $.ajax({
      url: apiUrl + '?action=marcar_consumo_agua',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ id_muestra: idMuestra, consumo_agua: false, tipo: null }),
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.success) { window.location.reload(); }
        else { Swal.fire('Error', (resp && resp.message) || 'No se pudo actualizar', 'error'); }
      },
      error: function() { Swal.fire('Error', 'Error de comunicación', 'error'); }
    });
  });
}

// ── Consumo Humano: marcar + análisis extra en un solo flujo ─────────────────
function configurarConsumoHumano(idMuestra, numMuestra) {
  mostrarCargaAnalisis('Cargando servicios...', 'Obteniendo servicios disponibles.');
  $.ajax({
    url: apiUrl + '?action=listar_servicios_extra&id_proyecto=' + encodeURIComponent(idProyecto) + '&id_muestra=' + encodeURIComponent(idMuestra),
    method: 'GET',
    dataType: 'json',
    success: function(resp) {
      cerrarCargaAnalisis();
      const disponibles = (resp && resp.success) ? (resp.servicios || []) : [];

      if (!disponibles.length) {
        Swal.fire('Sin servicios disponibles', 'No hay servicios extra disponibles para esta muestra. Todos los servicios activos ya fueron asignados.', 'warning');
        return;
      }

      let optsServicio = '<option value="">-- Seleccione el análisis extra --</option>';
      disponibles.forEach(function(s) {
        optsServicio += '<option value="' + s.id + '" data-reactivos="' + escapeHtml(JSON.stringify(s.reactivos)) + '">'
                      + escapeHtml(s.nombre) + '</option>';
      });

      const html =
        '<div style="text-align:left; font-size:0.95em;">' +
          '<div class="alert alert-info py-2 mb-3" style="font-size:0.88em;">' +
            '<i class="ti ti-user-check me-1"></i> Muestra #' + numMuestra +
            ' se marcará como <strong>Consumo Humano</strong>.' +
          '</div>' +
          '<div class="mb-2">' +
            '<label class="form-label fw-semibold">Análisis extra a agregar <span class="text-danger">*</span></label>' +
            '<select id="swal-ch-servicio" class="form-select form-select-sm">' + optsServicio + '</select>' +
            '<div class="form-text text-muted">Requerido para Consumo Humano. Solo se muestran servicios aún no asignados.</div>' +
          '</div>' +
          '<div id="swal-ch-reactivos" class="alert alert-warning py-2 d-none mt-2" style="font-size:0.85em;"></div>' +
        '</div>';

      Swal.fire({
        title: '<i class="ti ti-user-check me-1"></i> Marcar como Consumo Humano',
        html: html,
        width: 580,
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-check me-1"></i>Confirmar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        didOpen: function() {
          var sel = document.getElementById('swal-ch-servicio');
          if (!sel) return;
          sel.addEventListener('change', function() {
            var opt  = this.options[this.selectedIndex];
            var infoDiv = document.getElementById('swal-ch-reactivos');
            if (!opt || !opt.value) {
              infoDiv.classList.add('d-none');
              return;
            }
            // Reactivos
            var reactivos = [];
            try { reactivos = JSON.parse(opt.getAttribute('data-reactivos') || '[]'); } catch(e) {}
            infoDiv.classList.remove('d-none');
            if (reactivos.length) {
              infoDiv.innerHTML = '<strong>Reactivos que se consumirán:</strong><ul class="mb-0 mt-1">' +
                reactivos.map(function(r) {
                  return '<li>' + escapeHtml(r.nombre) + ': <strong>' + r.cantidad + ' ' + r.unidad + '</strong></li>';
                }).join('') + '</ul>';
            } else {
              infoDiv.innerHTML = '<em>Este servicio no tiene reactivos registrados en receta.</em>';
            }
          });
        },
        preConfirm: function() {
          var sel = document.getElementById('swal-ch-servicio');
          var idServicio = sel ? (parseInt(sel.value || '0', 10) || 0) : 0;
          if (!idServicio) {
            Swal.showValidationMessage('Debe seleccionar el análisis extra para Consumo Humano');
            return false;
          }
          return { id_servicio: idServicio };
        }
      }).then(function(result) {
        if (!result.isConfirmed) return;
        var idServicioExtra = result.value.id_servicio;

        mostrarCargaAnalisis('Guardando...', 'Marcando muestra como Consumo Humano...');

        $.ajax({
          url: apiUrl + '?action=marcar_consumo_agua',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify({ id_muestra: idMuestra, consumo_agua: true, tipo: 'Consumo Humano' }),
          dataType: 'json',
          success: function(resp1) {
            if (!resp1 || !resp1.success) {
              cerrarCargaAnalisis();
              Swal.fire('Error', (resp1 && resp1.message) || 'No se pudo marcar la muestra', 'error');
              return;
            }
            actualizarCargaAnalisis('Creando solicitud de análisis extra...');
            $.ajax({
              url: apiUrl + '?action=agregar_analisis_extra',
              method: 'POST',
              contentType: 'application/json',
              data: JSON.stringify({ id_muestra: idMuestra, id_proyecto: idProyecto, id_servicio: idServicioExtra }),
              dataType: 'json',
              success: function(resp2) {
                cerrarCargaAnalisis();
                if (resp2 && resp2.success) {
                  Swal.fire('¡Listo!', 'Muestra marcada como <strong>Consumo Humano</strong> y análisis extra agregado.<br><small class="text-muted">' + escapeHtml(resp2.message || '') + '</small>', 'success')
                    .then(function() { window.location.reload(); });
                } else {
                  Swal.fire('Parcialmente completado', 'Muestra marcada como Consumo Humano, pero ocurrió un error al agregar el análisis extra:<br>' + escapeHtml((resp2 && resp2.message) || 'Error desconocido'), 'warning')
                    .then(function() { window.location.reload(); });
                }
              },
              error: function(xhr) {
                cerrarCargaAnalisis();
                var msg = 'Error al agregar análisis extra';
                try { var p = JSON.parse(xhr.responseText || '{}'); if (p.message) msg = p.message; } catch(e) {}
                Swal.fire('Parcialmente completado', 'Muestra marcada, pero error en análisis extra: ' + escapeHtml(msg), 'warning')
                  .then(function() { window.location.reload(); });
              }
            });
          },
          error: function() {
            cerrarCargaAnalisis();
            Swal.fire('Error', 'Error de comunicación al marcar la muestra', 'error');
          }
        });
      });
    },
    error: function() {
      cerrarCargaAnalisis();
      Swal.fire('Error', 'Error al cargar servicios disponibles', 'error');
    }
  });
}

// ── toggleConsumoAgua (legado, redirige a nuevo flujo) ───────────────────────
function toggleConsumoAgua(idMuestra, marcar) {
  if (marcar) { configurarConsumoHumano(idMuestra, '?'); return; }
  quitarTipoServicio(idMuestra);
}

// ── Análisis Extra (standalone) ─────────────────────────────────────────────
function abrirExtraAnalisis(idMuestra, numMuestra) {
  mostrarCargaAnalisis('Cargando servicios...', 'Obteniendo servicios disponibles.');
  $.ajax({
    url: apiUrl + '?action=listar_servicios_extra&id_proyecto=' + encodeURIComponent(idProyecto) + '&id_muestra=' + encodeURIComponent(idMuestra),
    method: 'GET',
    dataType: 'json',
    success: function(resp) {
      cerrarCargaAnalisis();
      if (!resp || !resp.success) {
        Swal.fire('Error', (resp && resp.message) || 'No se pudieron cargar servicios', 'error');
        return;
      }
      const servicios = resp.servicios || [];
      if (!servicios.length) {
        Swal.fire('Sin servicios', 'No hay servicios adicionales disponibles para esta muestra (todos ya están asignados).', 'info');
        return;
      }

      let opts = '<option value="">Seleccione un servicio...</option>';
      servicios.forEach(function(s) {
        opts += '<option value="' + s.id + '" data-reactivos="' + escapeHtml(JSON.stringify(s.reactivos)) + '">'
              + escapeHtml(s.nombre) + '</option>';
      });

      const html =
        '<div style="text-align:left; font-size:0.95em;">' +
          '<p class="mb-2">Agregar análisis extra a <strong>Muestra #' + numMuestra + '</strong>.</p>' +
          '<p class="text-muted mb-2">Los reactivos del servicio se consumirán de inmediato. El residuo se registrará al finalizar el análisis.</p>' +
          '<div class="mb-3"><label class="form-label fw-semibold">Servicio a agregar</label>' +
          '<select id="swal-servicio-extra" class="form-select form-select-sm">' + opts + '</select></div>' +
          '<div id="swal-reactivos-info" class="alert alert-info py-2 d-none" style="font-size:0.88em;"></div>' +
        '</div>';

      Swal.fire({
        title: '<i class="ti ti-microscope me-1"></i>Análisis Extra',
        html: html,
        width: 580,
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-flask me-1"></i>Agregar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        didOpen: function() {
          document.getElementById('swal-servicio-extra').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('swal-reactivos-info');
            if (!opt.value) { infoDiv.classList.add('d-none'); return; }
            let reactivos = [];
            try { reactivos = JSON.parse(opt.getAttribute('data-reactivos') || '[]'); } catch(e) {}
            if (reactivos.length) {
              infoDiv.classList.remove('d-none');
              infoDiv.innerHTML = '<strong>Reactivos que se consumirán:</strong><ul class="mb-0 mt-1">' +
                reactivos.map(function(r) {
                  return '<li>' + escapeHtml(r.nombre) + ': <strong>' + r.cantidad + ' ' + r.unidad + '</strong></li>';
                }).join('') + '</ul>';
            } else {
              infoDiv.classList.remove('d-none');
              infoDiv.innerHTML = '<em>Este servicio no tiene reactivos registrados en receta.</em>';
            }
          });
        },
        preConfirm: function() {
          const idServicio = parseInt(document.getElementById('swal-servicio-extra').value || '0', 10);
          if (!idServicio) {
            Swal.showValidationMessage('Debe seleccionar un servicio');
            return false;
          }
          return { id_servicio: idServicio };
        }
      }).then(function(result) {
        if (!result.isConfirmed) return;
        mostrarCargaAnalisis('Agregando análisis extra...', 'Creando solicitud y consumiendo reactivos...');
        $.ajax({
          url: apiUrl + '?action=agregar_analisis_extra',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify({
            id_muestra: idMuestra,
            id_proyecto: idProyecto,
            id_servicio: result.value.id_servicio
          }),
          dataType: 'json',
          success: function(resp) {
            cerrarCargaAnalisis();
            if (resp && resp.success) {
              Swal.fire('¡Listo!', resp.message || 'Análisis extra agregado correctamente.', 'success')
                .then(function() { window.location.reload(); });
            } else {
              Swal.fire('Error', (resp && resp.message) || 'No se pudo agregar el análisis extra', 'error');
            }
          },
          error: function(xhr) {
            cerrarCargaAnalisis();
            let msg = 'Error al agregar análisis extra';
            try { const p = JSON.parse(xhr.responseText || '{}'); if (p.message) msg = p.message; } catch(e) {}
            Swal.fire('Error', msg, 'error');
          }
        });
      });
    },
    error: function() {
      cerrarCargaAnalisis();
      Swal.fire('Error', 'Error al cargar servicios disponibles', 'error');
    }
  });
}


function cargarContextoConsumoExtra(onDone) {
  if (contextoConsumoExtraCache) {
    onDone(null, contextoConsumoExtraCache);
    return;
  }

  $.ajax({
    url: apiUrl + '?action=obtener_contexto_consumo_extra&ids_muestras=' + encodeURIComponent(idsMuestrasContext.join(',')),
    method: 'GET',
    dataType: 'json',
    success: function(resp) {
      if (!resp || !resp.success) {
        onDone(new Error(resp && resp.message ? resp.message : 'No se pudo cargar contexto'));
        return;
      }
      contextoConsumoExtraCache = resp;
      onDone(null, resp);
    },
    error: function(xhr) {
      let msg = 'No se pudo cargar datos para consumo extra';
      try {
        const parsed = JSON.parse(xhr.responseText || '{}');
        if (parsed.message) msg = parsed.message;
      } catch (e) {}
      onDone(new Error(msg));
    }
  });
}

let consumoExtraModal = null;
let consumoExtraResolver = null;
let consumoExtraPayload = null;

function getReactivoOptionsHtml() {
  const ctx = contextoConsumoExtraCache || {};
  return '<option value="">Reactivo...</option>' + (ctx.reactivos || []).map(function(r) {
    return '<option value="' + r.id + '">' + escapeHtml(r.nombre + ' | Stock: ' + r.stock + ' ' + r.unidad) + '</option>';
  }).join('');
}

function getResiduoOptionsHtml() {
  const ctx = contextoConsumoExtraCache || {};
  return '<option value="">Residuo...</option>' + (ctx.residuos || []).map(function(r) {
    return '<option value="' + r.id + '">' + escapeHtml(r.label + ' (' + r.unidad + ')') + '</option>';
  }).join('');
}

function agregarFilaManualModal() {
  const html = '' +
    '<div class="row g-1 align-items-center mb-1 manual-row">' +
      '<div class="col-8"><select class="form-select form-select-sm id-reactivo">' + getReactivoOptionsHtml() + '</select></div>' +
      '<div class="col-3"><input type="number" class="form-control form-control-sm cant-reactivo" min="0.0001" step="0.0001" placeholder="Cantidad"></div>' +
      '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></div>' +
    '</div>';
  $('#lista_manual_modal').append(html);
}

function agregarFilaResiduoModal() {
  const html = '' +
    '<div class="row g-1 align-items-center mb-1 residuo-row">' +
      '<div class="col-8"><select class="form-select form-select-sm id-residuo">' + getResiduoOptionsHtml() + '</select></div>' +
      '<div class="col-3"><input type="number" class="form-control form-control-sm cant-residuo" min="0.0001" step="0.0001" placeholder="Cantidad"></div>' +
      '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></div>' +
    '</div>';
  $('#lista_residuos_modal').append(html);
}

function refrescarServiciosModalPorMuestra() {
  const ctx = contextoConsumoExtraCache || {};
  const idM = parseInt($('#id_muestra_extra_modal').val() || '0', 10);
  const options = ['<option value="">Seleccione...</option>'];
  (ctx.servicios || []).forEach(function(s) {
    if (!idM || parseInt(s.id_muestra, 10) === idM) {
      const consumo = parseFloat(s.consumo_default || 0).toFixed(4);
      options.push('<option value="' + s.id_servicio + '">' + escapeHtml(s.label + ' | consumo base: ' + consumo) + '</option>');
    }
  });
  $('#id_servicio_extra_modal').html(options.join(''));
}

function resetModalConsumoExtra() {
  const ctx = contextoConsumoExtraCache || {};
  const muestrasOptions = ['<option value="">Seleccione...</option>'];
  (ctx.muestras || []).forEach(function(m) {
    muestrasOptions.push('<option value="' + m.id + '">' + escapeHtml(m.label) + '</option>');
  });

  $('#chk_consumo_extra_modal').prop('checked', false);
  $('#bloque_consumo_extra_modal').hide();
  $('#id_muestra_extra_modal').html(muestrasOptions.join(''));
  $('#tipo_descuento_analisis_modal').prop('checked', true);
  $('#bloque_tipo_analisis_modal').show();
  $('#bloque_tipo_manual_modal').hide();
  $('#factor_extra_modal').val('1');
  $('#lista_manual_modal').empty();
  $('#lista_residuos_modal').empty();
  $('#nota_extra_modal').val('');
  refrescarServiciosModalPorMuestra();
  agregarFilaResiduoModal();
}

function construirPayloadConsumoExtraModal() {
  const registrar = $('#chk_consumo_extra_modal').is(':checked');
  if (!registrar) {
    return null;
  }

  const tipo = $('input[name="tipo_descuento_extra_modal"]:checked').val();
  const idMuestra = parseInt($('#id_muestra_extra_modal').val() || '0', 10);
  if (!idMuestra) {
    throw new Error('Debe seleccionar una muestra');
  }

  const payload = {
    tipo: tipo,
    id_muestra: idMuestra,
    nota: ($('#nota_extra_modal').val() || '').trim(),
    manual_items: [],
    residuos: []
  };

  if (tipo === 'analisis') {
    const idServicio = parseInt($('#id_servicio_extra_modal').val() || '0', 10);
    const factor = parseFloat($('#factor_extra_modal').val() || '0');
    if (!idServicio) {
      throw new Error('Debe seleccionar el analisis/servicio a repetir');
    }
    if (!(factor > 0)) {
      throw new Error('La cantidad equivalente de muestras debe ser mayor a 0');
    }
    payload.id_servicio = idServicio;
    payload.factor = factor;
  } else {
    $('.manual-row').each(function() {
      const idReactivo = parseInt($(this).find('.id-reactivo').val() || '0', 10);
      const cantidad = parseFloat($(this).find('.cant-reactivo').val() || '0');
      if (idReactivo > 0 && cantidad > 0) {
        payload.manual_items.push({ id_reactivo: idReactivo, cantidad: cantidad });
      }
    });
    if (payload.manual_items.length === 0) {
      throw new Error('Debe ingresar al menos un reactivo manual con cantidad');
    }
  }

  $('.residuo-row').each(function() {
    const idResiduo = parseInt($(this).find('.id-residuo').val() || '0', 10);
    const cantidad = parseFloat($(this).find('.cant-residuo').val() || '0');
    if (idResiduo > 0 && cantidad > 0) {
      payload.residuos.push({ id_residuo_cat: idResiduo, cantidad: cantidad });
    }
  });

  return payload;
}

function inicializarModalConsumoExtra() {
  if (consumoExtraModal) {
    return;
  }

  const el = document.getElementById('modal-consumo-extra');
  consumoExtraModal = new bootstrap.Modal(el);

  $('#modal-consumo-extra').on('change', '#chk_consumo_extra_modal', function() {
    $('#bloque_consumo_extra_modal').toggle(this.checked);
  });

  $('#modal-consumo-extra').on('change', 'input[name="tipo_descuento_extra_modal"]', function() {
    const tipo = $('input[name="tipo_descuento_extra_modal"]:checked').val();
    $('#bloque_tipo_analisis_modal').toggle(tipo === 'analisis');
    $('#bloque_tipo_manual_modal').toggle(tipo === 'manual');
  });

  $('#modal-consumo-extra').on('click', '#btn_add_manual_modal', function() { agregarFilaManualModal(); });
  $('#modal-consumo-extra').on('click', '#btn_add_residuo_modal', function() { agregarFilaResiduoModal(); });
  $('#modal-consumo-extra').on('click', '.btn-remove-row', function() { $(this).closest('.manual-row, .residuo-row').remove(); });
  $('#modal-consumo-extra').on('change', '#id_muestra_extra_modal', refrescarServiciosModalPorMuestra);

  $('#btn_confirmar_consumo_extra_modal').on('click', function() {
    try {
      consumoExtraPayload = construirPayloadConsumoExtraModal();
      consumoExtraModal.hide();
    } catch (e) {
      Swal.fire('Advertencia', e.message || 'Datos inválidos', 'warning');
    }
  });

  el.addEventListener('hidden.bs.modal', function() {
    if (consumoExtraResolver) {
      const cb = consumoExtraResolver;
      consumoExtraResolver = null;
      const payload = consumoExtraPayload;
      consumoExtraPayload = null;
      cb(payload || null);
    }
  });
}

function abrirModalConsumoExtra(onResolved) {
  cargarContextoConsumoExtra(function(err) {
    if (err) {
      Swal.fire('Aviso', 'No se pudo cargar el modal de consumo extra. Se guardarán solo resultados.\n' + err.message, 'warning');
      onResolved(null);
      return;
    }

    inicializarModalConsumoExtra();
    resetModalConsumoExtra();
    consumoExtraResolver = onResolved;
    consumoExtraPayload = null;
    consumoExtraModal.show();
  });
}

function registrarConsumoExtra(payload, onDone) {
  if (!payload) {
    onDone(true, null);
    return;
  }

  $.ajax({
    url: apiUrl + '?action=registrar_consumo_extra',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(payload),
    dataType: 'json',
    timeout: 15000,
    success: function(resp) {
      if (resp && resp.success) {
        onDone(true, resp);
      } else {
        onDone(false, resp && resp.message ? resp.message : 'No se pudo registrar consumo extra');
      }
    },
    error: function(xhr) {
      let msg = 'No se pudo registrar consumo extra';
      try {
        const parsed = JSON.parse(xhr.responseText || '{}');
        if (parsed.message) msg = parsed.message;
      } catch (e) {}
      onDone(false, msg);
    }
  });
}

function mostrarCargaAnalisis(titulo, mensaje) {
  Swal.fire({
    title: titulo || 'Procesando...',
    html: mensaje || 'Espere un momento, por favor.',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: function() {
      Swal.showLoading();
    }
  });
}

function actualizarCargaAnalisis(mensaje) {
  const htmlContainer = Swal.getHtmlContainer();
  if (htmlContainer) {
    htmlContainer.textContent = mensaje || 'Procesando...';
  }
}

function cerrarCargaAnalisis() {
  if (Swal.isVisible()) {
    Swal.close();
  }
}

function verResultados() {
    let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
    html += '<thead style="background-color: #f0f0f0;"><tr>';
    html += '<th>Muestra No</th>';
    html += '<th>Parámetro</th>';
    html += '<th>Unidad</th>';
    html += '<th>Valor</th>';
    html += '</tr></thead><tbody>';
    
    let hayResultados = false;
    
    $('.param-input').each(function() {
        let valor = $(this).val();
        if (valor && valor !== '') {
            hayResultados = true;
            
            let muestra = $(this).data('muestra') || '';
            let parametro = $(this).data('parametro') || '';
            let unidad = $(this).data('unidad') || '-';
            
            html += '<tr>';
            html += '<td style="text-align: center; font-weight: 600;">' + muestra + '</td>';
            html += '<td>' + parametro + '</td>';
            html += '<td style="text-align: center;">' + unidad + '</td>';
            html += '<td style="text-align: center; font-weight: 600; color: #667eea;">' + valor + '</td>';
            html += '</tr>';
        }
    });
    
    html += '</tbody></table></div>';
    
    if (!hayResultados) {
        html = '<div class="alert alert-info">No hay resultados para mostrar</div>';
    }
    
    document.getElementById('tablaResultados').innerHTML = html;
    const modal = new bootstrap.Modal(document.getElementById('modalResultados'));
    modal.show();
}

  function agregarMuestrasProyectoAnalisis() {
    $.ajax({
      url: apiCreacionMasivaUrl + '?action=obtenerDetalles&id=' + idProyecto,
      type: 'GET',
      dataType: 'json',
      success: function(response) {
        const proyecto = response.proyecto || {};
        const detalles = response.detalles || [];

        if (!detalles.length) {
          Swal.fire('Aviso', 'El proyecto no tiene servicios configurados para agregar muestras.', 'warning');
          return;
        }

        let filas = '';
        detalles.forEach(function(det) {
          const idProducto = parseInt(det.Id_Producto_Venta, 10) || 0;
          const nombre = det.Nombre_Producto || ('Servicio #' + idProducto);
          const actual = parseInt(det.Cantidad_Planificada, 10) || 0;

          filas += '<tr>' +
            '<td>' + escapeHtml(nombre) + '</td>' +
            '<td class="text-center"><span class="badge bg-secondary" style="font-size:0.95em;">' + actual + '</span></td>' +
            '<td class="text-center">' +
              '<input type="number" min="0" step="1" value="0" class="form-control form-control-sm extra-cantidad-input" data-id="' + idProducto + '" style="max-width:110px; margin:0 auto; font-size:0.95em;">' +
            '</td>' +
          '</tr>';
        });

        const html =
          '<div style="text-align:left; font-size:0.95em;">' +
            '<p class="mb-2"><strong>Proyecto:</strong> ' + escapeHtml(proyecto.Nombre_Proyecto || ('#' + idProyecto)) + '</p>' +
            '<p class="text-muted mb-2">Ingrese cuántas muestras adicionales desea crear por servicio. Puede dejar 0 donde no aplique.</p>' +
            '<div class="table-responsive" style="max-height:320px; overflow:auto;">' +
              '<table class="table table-sm table-bordered mb-0">' +
                '<thead class="table-light"><tr><th>Servicio</th><th class="text-center" style="width:140px;">Planificadas</th><th class="text-center" style="width:160px;">Agregar</th></tr></thead>' +
                '<tbody>' + filas + '</tbody>' +
              '</table>' +
            '</div>' +
          '</div>';

        Swal.fire({
          title: 'Agregar muestras',
          html: html,
          width: 820,
          showCancelButton: true,
          confirmButtonText: 'Crear muestras adicionales',
          cancelButtonText: 'Cancelar',
          focusConfirm: false,
          preConfirm: function() {
            const extras = [];
            $('.swal2-container .extra-cantidad-input').each(function() {
              const idProducto = parseInt($(this).data('id'), 10) || 0;
              const cantidadExtra = parseInt($(this).val(), 10) || 0;
              if (idProducto > 0 && cantidadExtra > 0) {
                extras.push({ id: idProducto, cantidad_extra: cantidadExtra });
              }
            });

            if (!extras.length) {
              Swal.showValidationMessage('Debe ingresar al menos una cantidad adicional mayor a 0.');
              return false;
            }

            return extras;
          }
        }).then(function(result) {
          if (!result.isConfirmed || !Array.isArray(result.value) || !result.value.length) {
            return;
          }

          Swal.fire({
            title: 'Creando muestras adicionales...',
            html: 'El sistema está registrando las nuevas muestras. Por favor espere.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function() {
              Swal.showLoading();
            }
          });

          $.ajax({
            url: apiCreacionMasivaUrl,
            type: 'POST',
            data: JSON.stringify({
              action: 'agregarMuestrasAdicionales',
              id_proyecto: idProyecto,
              extras: result.value
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(resp) {
              Swal.close();
              if (resp && resp.success) {
                let msg = resp.mensaje || 'Muestras adicionales creadas correctamente';
                if (resp.muestras_creadas !== undefined) {
                  msg += ' (Nuevas: ' + resp.muestras_creadas + ')';
                }
                Swal.fire('¡Éxito!', msg, 'success').then(function() {
                  window.location.reload();
                });
              } else {
                Swal.fire('Error', (resp && resp.error) ? resp.error : 'No se pudieron crear muestras adicionales', 'error');
              }
            },
            error: function(err) {
              Swal.close();
              const msg = (err.responseJSON && err.responseJSON.error) ? err.responseJSON.error : 'Error al crear muestras adicionales';
              Swal.fire('Error', msg, 'error');
            }
          });
        });
      },
      error: function(err) {
        const msg = (err.responseJSON && err.responseJSON.error) ? err.responseJSON.error : 'No se pudo cargar el detalle del proyecto';
        Swal.fire('Error', msg, 'error');
      }
    });
  }

function guardarResultados(event) {
    event.preventDefault();
    
    let resultados = [];
    
    // Recopilar todos los valores ingresados
    $('.param-input').each(function() {
        let valor = $(this).val();
        let idResultado = $(this).data('resultado');
        
        if (idResultado) {
            resultados.push({
                Id_Resultado: parseInt(idResultado),
                Valor_Hallado: valor ? parseFloat(valor) : null
            });
        }
    });

    if (resultados.length === 0) {
        Swal.fire('Advertencia', 'No hay resultados para guardar', 'warning');
        return;
    }

    // Mostrar confirmación
    Swal.fire({
        title: 'Confirmar',
        html: 'Se guardarán <strong>' + resultados.length + '</strong> resultados. ¿Continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
        abrirModalConsumoExtra(function(payloadExtra) {
          guardarResultadosAPI(resultados, 0, payloadExtra);
        });
        }
    });
}

  function guardarResultadosAPI(resultados, index, payloadExtra) {
    if (index === 0) {
      mostrarCargaAnalisis('Grabando resultados...', 'Procesando 1 de ' + resultados.length + ' resultados.');
    }

    if (index >= resultados.length) {
      actualizarCargaAnalisis('Registrando consumo extra, por favor espere...');
      registrarConsumoExtra(payloadExtra, function(ok, dataOrMsg) {
        cerrarCargaAnalisis();
        if (!ok) {
          Swal.fire('Error', String(dataOrMsg || 'Error registrando consumo extra'), 'error');
          return;
        }

        let mensaje = 'Todos los resultados han sido guardados';
        if (dataOrMsg && dataOrMsg.movimientos !== undefined) {
          mensaje += '<br><small>Movimientos extra: <strong>' + dataOrMsg.movimientos + '</strong> | Residuos: <strong>' + (dataOrMsg.residuos || 0) + '</strong></small>';
        }

        Swal.fire({
          title: 'Exito',
          html: mensaje,
          icon: 'success'
        }).then(() => {
          window.location.href = '?module=laboratorio&action=muestra&subaction=creacion_masiva';
        });
      });
        return;
    }

    let resultado = resultados[index];

    $.ajax({
        url: apiUrl + '?action=guardar_resultado',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(resultado),
        dataType: 'json',
        timeout: 5000,
        success: function(response) {
            if (response.success) {
          const siguiente = index + 2;
          if (siguiente <= resultados.length) {
            actualizarCargaAnalisis('Procesando ' + siguiente + ' de ' + resultados.length + ' resultados.');
          }
            guardarResultadosAPI(resultados, index + 1, payloadExtra);
            } else {
            cerrarCargaAnalisis();
                Swal.fire('Error', response.message || 'Error al guardar resultado', 'error');
            }
        },
        error: function(xhr, status, error) {
          cerrarCargaAnalisis();
            let errorMsg = 'Error al guardar';
            try {
                if (xhr.responseText) {
                    let resp = JSON.parse(xhr.responseText);
                    errorMsg = resp.message || errorMsg;
                }
            } catch(e) {
                errorMsg = xhr.status + ' - ' + xhr.responseText.substring(0, 100);
            }
            
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

function guardarAvance() {
    let resultados = [];
    
    // Recopilar todos los valores ingresados
    $('.param-input').each(function() {
        let valor = $(this).val();
        let idResultado = $(this).data('resultado');
        
        if (idResultado) {
            resultados.push({
                Id_Resultado: parseInt(idResultado),
                Valor_Hallado: valor && valor !== '' ? parseFloat(valor) : null
            });
        }
    });

    if (resultados.length === 0) {
        Swal.fire('Advertencia', 'No hay resultados para guardar', 'warning');
        return;
    }

    // Mostrar confirmación
    Swal.fire({
        title: 'Confirmar',
        html: 'Se guardarán <strong>' + resultados.length + '</strong> resultados. (Sin finalizar análisis)',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar avance',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            guardarAvanceAPI(resultados, 0);
        }
    });
}

function guardarAvanceAPI(resultados, index) {
  if (index === 0) {
    mostrarCargaAnalisis('Guardando avance...', 'Procesando 1 de ' + resultados.length + ' resultados.');
  }

    if (index >= resultados.length) {
    cerrarCargaAnalisis();
        Swal.fire('¡Éxito!', 'Avance guardado correctamente', 'success');
        return;
    }

    let resultado = resultados[index];
    let fullUrl = apiUrl + '?action=guardar_avance';

    $.ajax({
        url: fullUrl,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(resultado),
        dataType: 'json',
        timeout: 5000,
        success: function(response) {
            if (response.success) {
            const siguiente = index + 2;
            if (siguiente <= resultados.length) {
              actualizarCargaAnalisis('Procesando ' + siguiente + ' de ' + resultados.length + ' resultados.');
            }
                guardarAvanceAPI(resultados, index + 1);
            } else {
            cerrarCargaAnalisis();
                Swal.fire('Error', response.message || 'Error al guardar', 'error');
            }
        },
        error: function(xhr, status, error) {
          cerrarCargaAnalisis();
            let errorMsg = 'Error al guardar';
            try {
                if (xhr.responseText) {
                    let resp = JSON.parse(xhr.responseText);
                    errorMsg = resp.message || errorMsg;
                }
            } catch(e) {
                errorMsg = xhr.status + ' - ' + xhr.responseText.substring(0, 100);
            }
            
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

</script>

</body>
</html>
