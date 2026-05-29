<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$id_cliente = intval($_GET['id_cliente'] ?? 0);
$id_muestra = intval($_GET['id_muestra'] ?? 0);
$id_bitacora = intval($_GET['id_bitacora'] ?? 0);
$agricultor_query = trim((string)($_GET['agricultor'] ?? ''));

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

if ($id_cliente <= 0 && $id_muestra > 0) {
  $sqlClienteByMuestra = "SELECT TOP 1 Id_Cliente
              FROM laboratorio.Muestra_Lab
              WHERE Id_Muestra = ? AND Activo = 1";
  $stmtClienteByMuestra = sqlsrv_query($conn, $sqlClienteByMuestra, array($id_muestra));
  $rowClienteByMuestra = $stmtClienteByMuestra ? sqlsrv_fetch_array($stmtClienteByMuestra, SQLSRV_FETCH_ASSOC) : null;
  $id_cliente = intval($rowClienteByMuestra['Id_Cliente'] ?? 0);
}

$filtrarPorCliente = ($id_cliente > 0);
$filtrarPorBitacora = ($id_bitacora > 0);

if (!$filtrarPorCliente && !$filtrarPorBitacora && $id_muestra <= 0) {
  echo '<div class="alert alert-danger">ERROR: Agricultor invalido</div>';
  exit;
}

$cliente = [
  'Agricultor' => '-',
  'Valle' => '-'
];

if ($filtrarPorBitacora) {
    $sql_muestras = "SELECT DISTINCT m.Id_Muestra, ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden
                     FROM laboratorio.Muestra_Lab m
                     INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                     WHERE mb.Id_Bitacora = ?
                       AND m.Id_Proyecto IS NULL
                       AND m.Estado = 'En Analisis'
                       AND m.Activo = 1
                     ORDER BY m.Id_Muestra";
    $stmt_muestras = sqlsrv_query($conn, $sql_muestras, array($id_bitacora));
} else if ($filtrarPorCliente) {
    $sql_muestras = "SELECT DISTINCT m.Id_Muestra, ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden
                     FROM laboratorio.Muestra_Lab m
                     WHERE m.Id_Cliente = ?
                       AND m.Id_Proyecto IS NULL
                       AND m.Estado = 'En Analisis'
                       AND m.Activo = 1
                     ORDER BY m.Id_Muestra";
    $stmt_muestras = sqlsrv_query($conn, $sql_muestras, array($id_cliente));
} else {
    $sql_muestras = "SELECT DISTINCT m.Id_Muestra, ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden
                     FROM laboratorio.Muestra_Lab m
                     WHERE m.Id_Muestra = ?
                       AND m.Id_Proyecto IS NULL
                       AND m.Estado = 'En Analisis'
                       AND m.Activo = 1
                     ORDER BY m.Id_Muestra";
    $stmt_muestras = sqlsrv_query($conn, $sql_muestras, array($id_muestra));
}
$muestras = [];
$ids_muestras = [];
while ($row = sqlsrv_fetch_array($stmt_muestras, SQLSRV_FETCH_ASSOC)) {
    $muestras[] = $row;
    $ids_muestras[] = intval($row['Id_Muestra']);
}

if (empty($muestras)) {
    echo '<div class="alert alert-warning">ERROR: No hay muestras en analisis para los filtros seleccionados</div>';
    exit;
}

if ($filtrarPorBitacora) {
  $sql_cliente = "SELECT TOP 1
            COALESCE(NULLIF(LTRIM(RTRIM(mb.Punto_Toma)), ''), 'Muestra por defecto') AS Agricultor,
            COALESCE(NULLIF(LTRIM(RTRIM(m.Valle)), ''), '-') AS Valle
          FROM laboratorio.Muestra_Lab m
          INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
          WHERE mb.Id_Bitacora = ?
          ORDER BY m.Id_Muestra";
  $stmt_cliente = sqlsrv_query($conn, $sql_cliente, array($id_bitacora));
} else {
  $placeholdersCliente = implode(',', array_fill(0, count($ids_muestras), '?'));
  $sql_cliente = "SELECT TOP 1
            COALESCE(NULLIF(LTRIM(RTRIM(CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno))), ''), '-') AS Agricultor,
            COALESCE(NULLIF(LTRIM(RTRIM(m.Valle)), ''), '-') AS Valle
          FROM laboratorio.Muestra_Lab m
          LEFT JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
          WHERE m.Id_Muestra IN ($placeholdersCliente)
          ORDER BY m.Id_Muestra";
  $stmt_cliente = sqlsrv_query($conn, $sql_cliente, $ids_muestras);
}

$clienteTmp = $stmt_cliente ? sqlsrv_fetch_array($stmt_cliente, SQLSRV_FETCH_ASSOC) : null;
if ($clienteTmp) {
  $cliente['Agricultor'] = trim((string)($clienteTmp['Agricultor'] ?? '-'));
  $cliente['Valle'] = trim((string)($clienteTmp['Valle'] ?? '-'));
}

if ($cliente['Agricultor'] === '' || $cliente['Agricultor'] === '-') {
  $cliente['Agricultor'] = $agricultor_query !== '' ? $agricultor_query : 'Cliente no identificado';
}

// Mostrar TODAS las columnas de parámetros del sistema.
// Solo se habilitan celdas cuando existe Resultado_Analisis para la muestra/parámetro.
$sql_parametros = "SELECT pa.Id_Parametro, pa.Nombre, pa.Unidad_Medida, pa.Categoria
                   FROM laboratorio.Parametro_Analisis pa
                   WHERE pa.Activo = 1
                   ORDER BY pa.Categoria, pa.Nombre";
$stmt_parametros = sqlsrv_query($conn, $sql_parametros);
if (!$stmt_parametros) {
    $error_msg = print_r(sqlsrv_errors(), true);
    echo '<div class="alert alert-danger"><strong>ERROR SQL (parametros):</strong><br><pre>' . htmlspecialchars($error_msg) . '</pre></div>';
    exit;
}

$parametros_por_categoria = [];
while ($row = sqlsrv_fetch_array($stmt_parametros, SQLSRV_FETCH_ASSOC)) {
    $categoria = $row['Categoria'] ?? 'Sin Categoria';
    if (!isset($parametros_por_categoria[$categoria])) {
        $parametros_por_categoria[$categoria] = [];
    }
    $parametros_por_categoria[$categoria][] = $row;
}

if (empty($parametros_por_categoria)) {
    echo '<div class="alert alert-warning">ERROR: No hay parametros habilitados para estas muestras</div>';
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids_muestras), '?'));
$sql_resultados = "SELECT
                    ra.Id_Resultado,
                    ra.Id_Parametro,
                    ra.Valor_Hallado,
                    sa.Id_Muestra
                   FROM laboratorio.Resultado_Analisis ra
                   INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                   WHERE sa.Id_Muestra IN ($placeholders)
                     AND ra.Activo = 1
                     AND sa.Activo = 1";
$stmt_resultados = sqlsrv_query($conn, $sql_resultados, $ids_muestras);
if (!$stmt_resultados) {
    $error_msg = print_r(sqlsrv_errors(), true);
    echo '<div class="alert alert-danger"><strong>ERROR SQL (resultados):</strong><br><pre>' . htmlspecialchars($error_msg) . '</pre></div>';
    exit;
}

$resultados = [];
while ($row = sqlsrv_fetch_array($stmt_resultados, SQLSRV_FETCH_ASSOC)) {
    $key = $row['Id_Muestra'] . '_' . $row['Id_Parametro'];
    $resultados[$key] = [
        'Id_Resultado' => $row['Id_Resultado'],
        'Valor_Hallado' => $row['Valor_Hallado']
    ];
}

if (empty($resultados)) {
  echo '<div class="alert alert-warning"><strong>Sin casilleros habilitados</strong>: no hay resultados creados para estas muestras. Debe iniciar análisis desde la bandeja para generar casilleros por servicios comprados.</div>';
}

$usuario_nombre = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario desconocido';
$es_finalizado = false;
$etiquetaCabecera = $filtrarPorBitacora ? 'Punto de toma' : 'Cliente';
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
</style>
</head>
<body>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio">Muestras</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Recepcion y Analisis</a></li>
        <li class="breadcrumb-item active" aria-current="page">Analisis de Muestra</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">ANALISIS DE MUESTRAS</h2>
        <div class="text-muted mt-1">Ingrese los valores obtenidos en el laboratorio. Solo se encuentran habilitados los casilleros correspondientes a los parametros solicitados en la orden de servicio</div>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <span class="badge" style="background-color: #004d99; color: white;">
          <?php echo htmlspecialchars($etiquetaCabecera); ?>: <?php echo htmlspecialchars($cliente['Agricultor'] ?? '-'); ?>
        </span>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">

    <div class="alert alert-info" role="alert">
      <div>
        <strong><i class="ti ti-info-circle me-2"></i>Ingrese los valores del analisis</strong>
        <br>
        <div>Solo se encuentran habilitados los casilleros correspondientes a los parametros solicitados. Los campos bloqueados no forman parte del analisis contratado.</div>
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

      <?php
      $categoria_descripciones = [
          'Parámetros Físicos' => 'Resultados de variables fisicas. Ingrese los datos en los casilleros activos, segun el requerimiento del cliente',
          'Parámetros Químicos' => 'Determinacion de componentes quimicos. Solo los parametros seleccionados durante el ingreso de la muestra permiten la edicion de resultados',
          'Parámetros Microbiológicos' => 'Cuantificacion de agentes biologicos. Complete los valores unicamente si fueron habilitados en la solicitud inicial',
          'Físicos' => 'Resultados de variables fisicas. Ingrese los datos en los casilleros activos, segun el requerimiento del cliente',
          'Químicos' => 'Determinacion de componentes quimicos. Solo los parametros seleccionados durante el ingreso de la muestra permiten la edicion de resultados',
          'Microbiológicos' => 'Cuantificacion de agentes biologicos. Complete los valores unicamente si fueron habilitados en la solicitud inicial',
          'Fisico' => 'Resultados de variables fisicas. Ingrese los datos en los casilleros activos, segun el requerimiento del cliente',
          'Quimico' => 'Determinacion de componentes quimicos. Solo los parametros seleccionados durante el ingreso de la muestra permiten la edicion de resultados',
          'Microbiologico' => 'Cuantificacion de agentes biologicos. Complete los valores unicamente si fueron habilitados en la solicitud inicial'
      ];
      foreach ($parametros_por_categoria as $categoria => $params_categoria): ?>
        <div class="card mb-3">
          <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; padding: 15px;">
            <h3 class="card-title" style="font-size: 1.05em; color: #333; margin: 0; margin-bottom: 10px; display: block;">
              <i class="ti ti-flask me-2" style="color: #004d99;"></i><?php echo htmlspecialchars($categoria); ?>
            </h3>
            <div class="text-muted" style="font-size: 0.9em; line-height: 1.5; display: block;">
              <?php echo htmlspecialchars($categoria_descripciones[$categoria] ?? ''); ?>
            </div>
          </div>
          <div class="card-body pt-3">

            <div class="table-responsive">
              <table class="table table-vcenter card-table table-striped">
                <thead style="background-color: #f8f9fa;">
                  <tr>
                    <th class="col-muestra" style="font-weight: 600;">Ac</th>
                    <th class="col-muestra" style="font-weight: 600;">No</th>
                  <?php foreach ($params_categoria as $param): ?>
                    <th style="font-weight: 600;">
                      <div class="param-header">
                        <div>
                          <div class="param-nombre" style="font-size: 0.95em; margin-bottom: 3px;"><?php echo htmlspecialchars($param['Nombre']); ?></div>
                          <div class="param-unidad" style="font-size: 0.85em; color: #6c757d;"><?php echo htmlspecialchars($param['Unidad_Medida'] ?? '-'); ?></div>
                        </div>
                      </div>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($muestras as $muestra): ?>
                  <tr>
                    <td class="col-muestra">
                      <i class="ti ti-files" style="font-size: 1.2em; color: #004d99;"></i>
                    </td>
                    <td class="col-muestra"><?php echo $muestra['NumeroOrden']; ?></td>
                    <?php foreach ($params_categoria as $param):
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
                                 style="text-align: center;"
                                 data-resultado="<?php echo intval($resultado['Id_Resultado']); ?>"
                                 <?php if ($es_finalizado) echo 'disabled'; ?>
                                 value="<?php echo $resultado['Valor_Hallado'] !== null ? floatval($resultado['Valor_Hallado']) : ''; ?>">
                        <?php else: ?>
                          <input type="text" class="form-control form-control-sm" disabled value="-" style="text-align: center;">
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
      <?php endforeach; ?>

      <div class="row mt-4 mb-3" style="gap: 10px;">
        <div class="col-auto">
          <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()" style="font-size: 0.95em; padding: 8px 14px;">
            <i class="ti ti-arrow-left me-2"></i> Volver
          </button>
        </div>
        <?php if (!$es_finalizado): ?>
        <div class="col-auto ms-auto" style="display: flex; gap: 10px;">
          <button type="button" class="btn btn-outline-success" onclick="guardarAvance()" style="font-size: 0.95em; padding: 8px 14px;">
            <i class="ti ti-device-floppy me-2"></i> GUARDAR AVANCE
          </button>
          <?php if ($puede_finalizar): ?>
          <button type="submit" class="btn btn-success" style="background: #28a745; border: none; font-size: 0.95em; padding: 8px 18px;">
            <i class="ti ti-check me-2"></i> GRABAR RESULTADOS
          </button>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

    </form>
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
const apiUrl = 'modules/laboratorio/muestra/controllers/AnalisisAPI.php';
const idsMuestrasContext = <?php echo json_encode(array_values(array_map('intval', $ids_muestras))); ?>;
let contextoConsumoExtraCache = null;

function escapeHtml(text) {
  return String(text || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
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

function guardarResultados(event) {
    event.preventDefault();

    let resultados = [];

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

    Swal.fire({
        title: 'Confirmar',
        html: 'Se guardaran <strong>' + resultados.length + '</strong> resultados. ¿Continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, guardar',
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
          window.location.href = '?module=laboratorio&action=muestra';
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
        error: function(xhr) {
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

    Swal.fire({
        title: 'Confirmar',
        html: 'Se guardaran <strong>' + resultados.length + '</strong> resultados. (Sin finalizar analisis)',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, guardar avance',
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
        Swal.fire('Exito', 'Avance guardado correctamente', 'success');
        return;
    }

    let resultado = resultados[index];

    $.ajax({
        url: apiUrl + '?action=guardar_avance',
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
        error: function(xhr) {
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
