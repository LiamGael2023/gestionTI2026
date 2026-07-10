<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$id_cliente = intval($_GET['id_cliente'] ?? 0);
$id_muestra = intval($_GET['id_muestra'] ?? 0);
$agricultor_query = trim((string)($_GET['agricultor'] ?? ''));

$conn = Conexion::conectar();
if (!$conn) {
    echo '<div class="alert alert-danger">ERROR: No se pudo conectar a la BD</div>';
    exit;
}

// Verificar si el usuario actual tiene firma registrada
$usuario_id_actual = intval($_SESSION['usuario_id'] ?? 0);
$stmtFirmaCheck = sqlsrv_query($conn, "SELECT TOP 1 Img_Firma FROM laboratorio.Usuario_Lab_Firma WHERE Id_Usuario = ? AND Activo = 1", [$usuario_id_actual]);
$firmaCheckRow   = $stmtFirmaCheck ? sqlsrv_fetch_array($stmtFirmaCheck, SQLSRV_FETCH_ASSOC) : null;
$tienesFirma     = ($firmaCheckRow !== null && !empty($firmaCheckRow['Img_Firma']));

// Solo el Encargado de Laboratorio (Id_Rol=1) o admin puede ejecutar la firma
$es_encargado_lab = false;
$stmtEncLab = sqlsrv_query($conn, "SELECT TOP 1 1 FROM laboratorio.Usuario_Rol WHERE Id_Usuario = ? AND Id_Rol = 1", [$usuario_id_actual]);
if ($stmtEncLab && sqlsrv_fetch_array($stmtEncLab, SQLSRV_FETCH_ASSOC)) {
    $es_encargado_lab = true;
}
if (!$es_encargado_lab) {
    $stmtAdmLab = sqlsrv_query($conn, "SELECT TOP 1 rol FROM comun.Usuarios WHERE id_usuario = ? AND activo = 1", [$usuario_id_actual]);
    if ($stmtAdmLab) {
        $rowAdmLab = sqlsrv_fetch_array($stmtAdmLab, SQLSRV_FETCH_ASSOC);
        if ($rowAdmLab && in_array(strtolower(trim((string)$rowAdmLab['rol'])), ['administrador','admin','superadmin','super admin'], true)) {
            $es_encargado_lab = true;
        }
    }
}
$puede_firmar_como_encargado = $es_encargado_lab && $tienesFirma;

$conn = Conexion::conectar();
if (!$conn) {
    echo '<div class="alert alert-danger">ERROR: No se pudo conectar a la BD</div>';
    exit;
}

if ($id_cliente <= 0 && $id_muestra > 0) {
    $sqlClienteByMuestra = "SELECT TOP 1 Id_Cliente
                           FROM laboratorio.Muestra_Lab
                           WHERE Id_Muestra = ? AND Activo = 1";
    $stmtClienteByMuestra = sqlsrv_query($conn, $sqlClienteByMuestra, array($id_muestra));
    $rowClienteByMuestra = $stmtClienteByMuestra ? sqlsrv_fetch_array($stmtClienteByMuestra, SQLSRV_FETCH_ASSOC) : null;
    $id_cliente = intval($rowClienteByMuestra['Id_Cliente'] ?? 0);
}

if ($id_cliente <= 0) {
    echo '<div class="alert alert-danger">ERROR: Agricultor invalido</div>';
    exit;
}

$sql_muestras = "SELECT DISTINCT m.Id_Muestra, ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden
                 FROM laboratorio.Muestra_Lab m
                 WHERE m.Id_Cliente = ?
                   AND m.Id_Proyecto IS NULL
                   AND m.Estado = 'Por Firmar'
                   AND m.Activo = 1
                 ORDER BY m.Id_Muestra";
$stmt_muestras = sqlsrv_query($conn, $sql_muestras, array($id_cliente));

$muestras = [];
$ids_muestras = [];
while ($row = sqlsrv_fetch_array($stmt_muestras, SQLSRV_FETCH_ASSOC)) {
    $muestras[] = $row;
    $ids_muestras[] = intval($row['Id_Muestra']);
}

if (empty($muestras)) {
    echo '<div class="alert alert-warning">No hay muestras en estado Por Firmar para este agricultor.</div>';
    exit;
}

$placeholdersCliente = implode(',', array_fill(0, count($ids_muestras), '?'));
$sql_cliente = "SELECT TOP 1
                  COALESCE(NULLIF(LTRIM(RTRIM(CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno))), ''), '-') AS Agricultor
                FROM laboratorio.Muestra_Lab m
                LEFT JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
                WHERE m.Id_Muestra IN ($placeholdersCliente)
                ORDER BY m.Id_Muestra";
$stmt_cliente = sqlsrv_query($conn, $sql_cliente, $ids_muestras);
$clienteTmp = $stmt_cliente ? sqlsrv_fetch_array($stmt_cliente, SQLSRV_FETCH_ASSOC) : null;
$agricultor = trim((string)($clienteTmp['Agricultor'] ?? ''));
if ($agricultor === '' || $agricultor === '-') {
    $agricultor = $agricultor_query !== '' ? $agricultor_query : 'Cliente no identificado';
}

$sql_parametros = "SELECT pa.Id_Parametro, pa.Nombre, ISNULL(um.Abreviatura, pa.Unidad_Medida) AS Unidad_Medida, pa.Categoria,
                   CASE pa.Categoria WHEN 'Fisico' THEN 1 WHEN 'Quimico' THEN 2 WHEN 'Microbiologico' THEN 3 ELSE 4 END AS OrderCat
                   FROM laboratorio.Parametro_Analisis pa
                   LEFT JOIN laboratorio.Unidad_Medida um ON pa.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                   WHERE pa.Activo = 1
                   ORDER BY OrderCat, pa.Nombre";
$stmt_parametros = sqlsrv_query($conn, $sql_parametros);
if (!$stmt_parametros) {
    echo '<div class="alert alert-danger">ERROR al cargar parametros.</div>';
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
    echo '<div class="alert alert-danger">ERROR al cargar resultados.</div>';
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
?>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Firma de Resultados</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-2">
      <div class="col">
        <h2 class="page-title">REVISION Y FIRMA DE ANALISIS</h2>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <span class="badge bg-primary">Cliente: <?php echo htmlspecialchars($agricultor); ?></span>
      </div>
      <div class="col-auto">
        <span class="badge bg-azure">Muestras por firmar: <?php echo count($muestras); ?></span>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info" role="alert">
      <strong><i class="ti ti-info-circle me-2"></i>Vista de solo lectura</strong>
      <div>Se muestran todos los resultados del agricultor en estado Por Firmar. No es posible modificar casilleros en esta pantalla.</div>
    </div>

    <?php if (!$tienesFirma): ?>
    <div class="alert alert-danger d-flex align-items-center gap-3" role="alert">
      <i class="ti ti-signature-off" style="font-size:1.6rem;"></i>
      <div>
        <strong>No tienes una firma digital registrada.</strong><br>
        Para poder firmar muestras debes subir tu firma en el
        <a href="?module=laboratorio" class="alert-link">Módulo Principal → Mi Firma Digital</a>.
      </div>
    </div>
    <?php elseif (!$es_encargado_lab): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
      <i class="ti ti-lock" style="font-size:1.4rem;"></i>
      <div>
        <strong>Solo el Encargado de Laboratorio puede firmar muestras.</strong><br>
        Estás en modo de solo lectura. La firma de autorización la realiza el Encargado.
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
      <i class="ti ti-circle-check"></i>
      <div>Tu firma digital está registrada y será incluida automáticamente en el reporte Excel al firmar.</div>
    </div>
    <?php endif; ?>

    <?php
      $categoria_descripciones = [
          'Parámetros Físicos' => 'Resultados de variables fisicas en muestras pendientes de firma.',
          'Parámetros Químicos' => 'Resultados de componentes quimicos en muestras pendientes de firma.',
          'Parámetros Microbiológicos' => 'Resultados de componentes microbiologicos en muestras pendientes de firma.',
          'Físicos' => 'Resultados de variables fisicas en muestras pendientes de firma.',
          'Químicos' => 'Resultados de componentes quimicos en muestras pendientes de firma.',
          'Microbiológicos' => 'Resultados de componentes microbiologicos en muestras pendientes de firma.',
          'Fisico' => 'Resultados de variables fisicas en muestras pendientes de firma.',
          'Quimico' => 'Resultados de componentes quimicos en muestras pendientes de firma.',
          'Microbiologico' => 'Resultados de componentes microbiologicos en muestras pendientes de firma.'
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
                  <th style="font-weight: 600;">Ac</th>
                  <th style="font-weight: 600;">No</th>
                  <?php foreach ($params_categoria as $param): ?>
                    <th style="font-weight: 600; text-align:center;">
                      <div style="font-size: 0.95em; margin-bottom: 3px;"><?php echo htmlspecialchars($param['Nombre']); ?></div>
                      <div style="font-size: 0.82em; color: #6c757d;"><?php echo htmlspecialchars($param['Unidad_Medida'] ?? '-'); ?></div>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($muestras as $muestra): ?>
                <tr>
                  <td><i class="ti ti-files" style="font-size: 1.2em; color: #004d99;"></i></td>
                  <td><?php echo intval($muestra['NumeroOrden']); ?></td>
                  <?php foreach ($params_categoria as $param):
                    $key = $muestra['Id_Muestra'] . '_' . $param['Id_Parametro'];
                    $existe = isset($resultados[$key]);
                  ?>
                    <td>
                      <?php if ($existe): ?>
                        <input type="text"
                               class="form-control form-control-sm"
                               disabled
                               style="text-align: center;"
                               value="<?php echo $resultados[$key]['Valor_Hallado'] !== null ? htmlspecialchars((string)$resultados[$key]['Valor_Hallado']) : ''; ?>">
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
        <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='?module=laboratorio&action=muestra'">
          <i class="ti ti-arrow-left me-2"></i> Volver
        </button>
      </div>
      <div class="col-auto ms-auto" style="display: flex; gap: 10px;">
        <?php if ($puede_firmar_como_encargado): ?>
        <button type="button" class="btn btn-outline-success" onclick="firmar(false)">
          <i class="ti ti-signature me-2"></i> FIRMAR ESTA MUESTRA
        </button>
        <button type="button" class="btn btn-success" onclick="firmar(true)">
          <i class="ti ti-signature me-2"></i> FIRMAR TODAS DEL AGRICULTOR
        </button>
        <?php elseif ($es_encargado_lab && !$tienesFirma): ?>
        <button type="button" class="btn btn-outline-success" disabled title="Registra tu firma digital primero">
          <i class="ti ti-signature me-2"></i> FIRMAR ESTA MUESTRA
        </button>
        <button type="button" class="btn btn-success" disabled title="Registra tu firma digital primero">
          <i class="ti ti-signature me-2"></i> FIRMAR TODAS DEL AGRICULTOR
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function firmar(firmarTodos) {
  const payload = {
    id_muestra: <?php echo intval($id_muestra > 0 ? $id_muestra : $ids_muestras[0]); ?>,
    firmar_todos: !!firmarTodos
  };

  const mensaje = firmarTodos
    ? 'Se firmaran todas las muestras del agricultor en estado Por Firmar.'
    : 'Se firmara la muestra seleccionada y pasara a Finalizado.';

  Swal.fire({
    title: 'Confirmar firma',
    html: mensaje,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Confirmar',
    cancelButtonText: 'Cancelar'
  }).then(function(result) {
    if (!result.isConfirmed) {
      return;
    }

    fetch('modules/laboratorio/muestra/controllers/MuestraAPI.php?action=firmar_muestra', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (!data.success) {
          Swal.fire('Error', data.message || 'No se pudo registrar la firma.', 'error');
          return;
        }

        Swal.fire('Exito', data.message || 'Firma registrada correctamente.', 'success')
          .then(function() {
            window.location.href = '?module=laboratorio&action=muestra';
          });
      })
      .catch(function() {
        Swal.fire('Error', 'Error de red al registrar la firma.', 'error');
      });
  });
}
</script>
