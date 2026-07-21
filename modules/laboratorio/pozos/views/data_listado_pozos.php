<?php
session_start();
require_once '../../../../config/db.php';
require_once '../../../../modules/laboratorio/models/LaboratorioModel.php';

$conn     = Conexion::conectar();
$labModel = new LaboratorioModel($conn);
$userId   = intval($_SESSION['usuario_id'] ?? 0);
$perms    = $labModel->obtenerPermisosSubmodulo($userId, '?module=laboratorio&action=pozos');
if ($perms === null) { $perms = ['editar' => true, 'eliminar' => true]; }
$puedeEditar   = (bool)($perms['editar']   ?? false);
$puedeEliminar = (bool)($perms['eliminar'] ?? false);

$draw     = isset($_POST['draw'])            ? intval($_POST['draw'])            : 0;
$start    = isset($_POST['start'])           ? intval($_POST['start'])           : 0;
$length   = isset($_POST['length'])          ? intval($_POST['length'])          : 25;
$search   = isset($_POST['search']['value']) ? $_POST['search']['value']         : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir   = (isset($_POST['order'][0]['dir']) && $_POST['order'][0]['dir'] === 'desc') ? 'desc' : 'asc';

$columns = [
    0 => 'Id_Pozo',
    1 => 'codigo',
    2 => 'valle',
    3 => 'ubicacion',
    4 => 'propietario',
    5 => 'tipopozo',
    6 => 'Id_Pozo',
];

if (!isset($columns[$colIndex])) $colIndex = 0;

$sqlBase  = " FROM laboratorio.Catastro_Pozo ";
$sqlWhere = " WHERE 1=1 ";
$params   = [];

if (!empty($search)) {
    $sqlWhere .= " AND (Id_Pozo LIKE ? OR codigo LIKE ? OR valle LIKE ? OR propietario LIKE ? OR ubicacion LIKE ?) ";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM laboratorio.Catastro_Pozo");
if ($stmtTotal === false) {
    die(json_encode(['error' => 'Error en consulta total', 'details' => sqlsrv_errors()]));
}
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

$stmtFiltered = sqlsrv_query($conn, "SELECT COUNT(*) AS total " . $sqlBase . $sqlWhere, $params);
if ($stmtFiltered === false) {
    die(json_encode(['error' => 'Error en consulta filtrada', 'details' => sqlsrv_errors()]));
}
$totalFiltered = sqlsrv_fetch_array($stmtFiltered, SQLSRV_FETCH_ASSOC)['total'];

$sqlData = "SELECT Id_Pozo, codigo, valle, ubicacion, propietario, tipopozo,
                   coord_este, coord_norte, Fecha_Sincronizacion"
         . $sqlBase . $sqlWhere
         . " ORDER BY " . $columns[$colIndex] . " " . $colDir
         . " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$paramsData = array_merge($params, [$start, $length]);
$stmtData = sqlsrv_query($conn, $sqlData, $paramsData);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en consulta de datos', 'details' => sqlsrv_errors()]));
}

$data = [];
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    $idPozo  = trim((string)($row['Id_Pozo'] ?? ''));
    $tipoRaw = trim((string)($row['tipopozo'] ?? ''));
    $tipoBadge = ($tipoRaw !== '')
        ? '<span class="badge bg-blue-lt text-blue">' . htmlspecialchars($tipoRaw, ENT_QUOTES, 'UTF-8') . '</span>'
        : '-';

    $data[] = [
        $contador++,
        htmlspecialchars($idPozo, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($row['codigo'] ?: '-', ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($row['valle'] ?: '-', ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($row['ubicacion'] ?: '-', ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($row['propietario'] ?: '-', ENT_QUOTES, 'UTF-8'),
        $tipoBadge,
        '<div class="dropdown">'
            . '<a class="btn btn-ghost-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">'
            . '<i class="ti ti-dots-vertical"></i>'
            . '</a>'
            . '<div class="dropdown-menu dropdown-menu-end">'
            . '<a class="dropdown-item" href="?module=laboratorio&action=pozos&subaction=historial_pozo&id_pozo=' . rawurlencode($idPozo) . '">'
            . '<i class="ti ti-chart-line me-2"></i> Ver Historial Completo'
            . '</a>'
            . '<a class="dropdown-item" href="?module=laboratorio&action=pozos&subaction=geoportal&valle=' . rawurlencode($row['valle'] ?? '') . '">'
            . '<i class="ti ti-map-pin me-2"></i> Ver en Mapa'
            . '</a>'
            . '</div>'
            . '</div>',
    ];
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => intval($totalRecords),
    'recordsFiltered' => intval($totalFiltered),
    'data'            => $data,
]);
