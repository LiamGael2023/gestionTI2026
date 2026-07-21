<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$id_muestra = intval($_GET['id_muestra'] ?? 0);
$id_cliente = intval($_GET['id_cliente'] ?? 0);
$agricultor_query = trim((string)($_GET['agricultor'] ?? ''));

if ($id_muestra <= 0) {
    echo '<div class="alert alert-danger">Muestra invalida</div>';
    exit;
}

$conn = Conexion::conectar();
if (!$conn) {
    echo '<div class="alert alert-danger">No se pudo conectar a la BD</div>';
    exit;
}

$sql_muestra = "SELECT TOP 1 m.Id_Muestra, m.Id_Cliente, m.Estado, m.Valle,
               COALESCE(NULLIF(LTRIM(RTRIM(CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno))), ''), '-') AS Agricultor
               FROM laboratorio.Muestra_Lab m
               LEFT JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
               WHERE m.Id_Muestra = ? AND m.Activo = 1";
$stmt_muestra = sqlsrv_query($conn, $sql_muestra, [$id_muestra]);
$muestra = $stmt_muestra ? sqlsrv_fetch_array($stmt_muestra, SQLSRV_FETCH_ASSOC) : null;

if (!$muestra) {
    echo '<div class="alert alert-danger">Muestra no encontrada</div>';
    exit;
}

$estado = trim((string)($muestra['Estado'] ?? ''));
if (strcasecmp($estado, 'Finalizado') !== 0) {
    echo '<div class="alert alert-warning">La muestra seleccionada aun no esta finalizada.</div>';
    exit;
}

$id_cliente_real = intval($muestra['Id_Cliente'] ?? $id_cliente);
$agricultor = trim((string)($muestra['Agricultor'] ?? ''));
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
    echo '<div class="alert alert-danger">Error al cargar parametros.</div>';
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

$sql_resultados = "SELECT ra.Id_Parametro, ra.Valor_Hallado
                   FROM laboratorio.Resultado_Analisis ra
                   INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                   WHERE sa.Id_Muestra = ?
                     AND sa.Activo = 1
                     AND ra.Activo = 1";
$stmt_resultados = sqlsrv_query($conn, $sql_resultados, [$id_muestra]);
if (!$stmt_resultados) {
    echo '<div class="alert alert-danger">Error al cargar resultados.</div>';
    exit;
}

$resultados = [];
while ($row = sqlsrv_fetch_array($stmt_resultados, SQLSRV_FETCH_ASSOC)) {
    $resultados[intval($row['Id_Parametro'])] = $row['Valor_Hallado'];
}
?>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Resultados Pasados</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-2">
      <div class="col">
        <h2 class="page-title">RESULTADOS DE MUESTRA FINALIZADA</h2>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <span class="badge bg-primary">Cliente: <?php echo htmlspecialchars($agricultor); ?></span>
      </div>
      <div class="col-auto">
        <span class="badge bg-azure">Muestra: #<?php echo intval($id_muestra); ?></span>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info" role="alert">
      <strong><i class="ti ti-info-circle me-2"></i>Vista de solo lectura</strong>
      <div>Se muestran los resultados finales de la muestra. Los casilleros no se pueden editar.</div>
    </div>

    <?php foreach ($parametros_por_categoria as $categoria => $params_categoria): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h3 class="card-title"><i class="ti ti-flask me-2"></i><?php echo htmlspecialchars($categoria); ?></h3>
        </div>
        <div class="card-body pt-3">
          <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
              <thead>
                <tr>
                  <?php foreach ($params_categoria as $param): ?>
                    <th style="text-align:center;">
                      <div><?php echo htmlspecialchars($param['Nombre']); ?></div>
                      <small class="text-muted"><?php echo htmlspecialchars($param['Unidad_Medida'] ?? '-'); ?></small>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <?php foreach ($params_categoria as $param):
                    $pid = intval($param['Id_Parametro']);
                    $valor = $resultados[$pid] ?? null;
                  ?>
                    <td>
                      <input type="text"
                             class="form-control form-control-sm"
                             disabled
                             style="text-align:center;"
                             value="<?php echo $valor !== null ? htmlspecialchars((string)$valor) : '-'; ?>">
                    </td>
                  <?php endforeach; ?>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="row mt-4 mb-3" style="gap: 10px;">
      <div class="col-auto">
        <a class="btn btn-outline-secondary" href="?module=laboratorio&action=muestra#panel-muestras-pasadas">
          <i class="ti ti-arrow-left me-2"></i> Volver
        </a>
      </div>
      <div class="col-auto ms-auto">
        <a class="btn btn-success" href="modules/laboratorio/muestra/controllers/ExportarResultadosPasados.php?id_muestra=<?php echo intval($id_muestra); ?>">
          <i class="ti ti-file-spreadsheet me-2"></i> Descargar Excel
        </a>
      </div>
    </div>
  </div>
</div>
