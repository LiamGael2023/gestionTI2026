<?php
session_start();
require_once '../../../../core/Auth.php';
require_once '../../../../config/db.php';
Auth::check();
require_once '../../../../modules/laboratorio/models/LaboratorioModel.php';

$conn     = Conexion::conectar();
$labModel = new LaboratorioModel($conn);
$userId   = intval($_SESSION['usuario_id'] ?? 0);
$perms    = $labModel->obtenerPermisosSubmodulo($userId, '?module=laboratorio&action=reactivo');
if ($perms === null) { $perms = ['editar' => false, 'eliminar' => false]; }
$puedeEditar   = (bool)($perms['editar']   ?? false);
$puedeEliminar = (bool)($perms['eliminar'] ?? false);

// Asegurar columnas FK existen (Tipo ya estÃ¡ en DDL base, solo agregar FKs opcionales)
sqlsrv_query($conn, "IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='laboratorio' AND TABLE_NAME='Reactivo_Lab' AND COLUMN_NAME='Id_Unidad_Medida') ALTER TABLE laboratorio.Reactivo_Lab ADD Id_Unidad_Medida INT NULL");
sqlsrv_query($conn, "IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='laboratorio' AND TABLE_NAME='Reactivo_Lab' AND COLUMN_NAME='Id_Proveedor') ALTER TABLE laboratorio.Reactivo_Lab ADD Id_Proveedor INT NULL");

// Columnas para ordenaciÃ³n (Ã­ndice DataTables â†’ columna SQL)
$columns = [
    0 => 'r.Id_Reactivo',
    1 => 'r.Nombre',
    2 => "ISNULL(r.Tipo, '')",
    3 => "ISNULL(p.Razon_Social, '')",
    4 => "ISNULL(um.Abreviatura, r.Unidad_Medida)",
    5 => 'r.Cantidad_Stock',
    6 => 'r.Fecha_Vencimiento',
    7 => 'r.Id_Reactivo',  // acciones
    8 => 'r.Id_Reactivo',  // rowClass (hidden)
];

$draw     = isset($_POST['draw'])               ? intval($_POST['draw'])               : 0;
$start    = isset($_POST['start'])              ? intval($_POST['start'])              : 0;
$length   = isset($_POST['length'])             ? intval($_POST['length'])             : 10;
$search   = isset($_POST['search']['value'])    ? $_POST['search']['value']            : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir   = (isset($_POST['order'][0]['dir']) && $_POST['order'][0]['dir'] === 'desc') ? 'desc' : 'asc';

if ($colIndex < 0 || $colIndex >= count($columns)) $colIndex = 0;

$sqlBase  = " FROM laboratorio.Reactivo_Lab r
              LEFT JOIN laboratorio.Proveedor p ON r.Id_Proveedor = p.Id_Proveedor AND p.Activo = 1
              LEFT JOIN laboratorio.Unidad_Medida um ON r.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1 ";

$sqlWhere = " WHERE 1=1 ";
$params   = [];

if (!empty($search)) {
    $sqlWhere .= " AND (r.Nombre LIKE ? OR ISNULL(r.Tipo,'') LIKE ? OR ISNULL(p.Razon_Social,'') LIKE ?) ";
    $params    = ["%$search%", "%$search%", "%$search%"];
}

// Total sin filtro
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM laboratorio.Reactivo_Lab");
if ($stmtTotal === false) {
    die(json_encode(['error' => 'Error en consulta total', 'details' => sqlsrv_errors()]));
}
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

// Total filtrado
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) AS total " . $sqlBase . $sqlWhere, $params);
if ($stmtFiltrados === false) {
    die(json_encode(['error' => 'Error en consulta filtrada', 'details' => sqlsrv_errors()]));
}
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

// Datos paginados
$sqlData = "SELECT r.Id_Reactivo, r.Nombre, r.Activo, r.Tipo,
                   r.Cantidad_Stock, r.Fecha_Vencimiento,
                   ISNULL(p.Razon_Social, '') AS Proveedor_Display,
                   ISNULL(um.Abreviatura, '') AS UM_Display"
         . $sqlBase . $sqlWhere
         . " ORDER BY r.Activo DESC, " . $columns[$colIndex] . " " . $colDir
         . " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en consulta de datos', 'details' => sqlsrv_errors()]));
}

$data     = [];
$contador = $start + 1;
$hoy      = new DateTime();

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    // -- Vencimiento --
    $venc     = $row['Fecha_Vencimiento'];
    $vencHtml = '-';
    $rowClass = '';

    if ($venc instanceof DateTime) {
        $diff = $hoy->diff($venc);
        $dias = $diff->invert ? -intval($diff->days) : intval($diff->days);
        $str  = $venc->format('d/m/Y');

        if ($dias < 0) {
            $vencHtml = '<span class="badge bg-danger-lt text-danger" title="Vencido hace ' . abs($dias) . ' d&iacute;a(s)">'
                      . '<i class="ti ti-alert-triangle" style="font-size:10px;margin-right:3px;"></i>' . $str . '</span>';
            $rowClass = 'row-reac-vencido';
        } elseif ($dias <= 30) {
            $vencHtml = '<span class="badge bg-warning-lt text-warning" title="Vence en ' . $dias . ' d&iacute;a(s)">'
                      . '<i class="ti ti-clock" style="font-size:10px;margin-right:3px;"></i>' . $str . '</span>';
            $rowClass = 'row-reac-proximo';
        } else {
            $vencHtml = $str;
        }
    }

    // -- Tipo badge --
    $tipoBadge = '-';
    if (!empty($row['Tipo'])) {
        $t = strtolower($row['Tipo']);
        $tipoBadge = ($t === 'agua')
            ? '<span class="badge bg-blue-lt text-blue">Agua</span>'
            : '<span class="badge bg-orange-lt text-orange">Suelo</span>';
    }

    // -- Acciones --
    if ($row['Activo']) {
        $acciones = '<div class="btn-group btn-group-sm">'
                  . ($puedeEditar   ? '<button class="btn btn-ghost-primary" onclick="editarReactivo(' . intval($row['Id_Reactivo']) . ')" title="Editar"><i class="ti ti-pencil"></i></button>' : '')
                  . ($puedeEliminar ? '<button class="btn btn-ghost-danger" onclick="eliminarReactivo(' . intval($row['Id_Reactivo']) . ')" title="Eliminar"><i class="ti ti-trash"></i></button>' : '')
                  . '</div>';
        $badgeEstado = '';
    } else {
        $acciones = '<div class="btn-group btn-group-sm">'
                  . ($puedeEditar ? '<button class="btn btn-ghost-success" onclick="reactivarReactivo(' . intval($row['Id_Reactivo']) . ')" title="Reactivar"><i class="ti ti-check"></i></button>' : '<span class="text-muted small">Inactivo</span>')
                  . '</div>';
        $badgeEstado = ' <span class="badge bg-secondary">Inactivo</span>';
    }

    $data[] = [
        $contador++,
        htmlspecialchars($row['Nombre']) . $badgeEstado,
        $tipoBadge,
        htmlspecialchars($row['Proveedor_Display'] ?: '-'),
        htmlspecialchars($row['UM_Display'] ?: '-'),
        number_format(floatval($row['Cantidad_Stock']), 2, '.', ''),
        $vencHtml,
        $acciones,
        $rowClass,  // col 8, hidden
    ];
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => intval($totalRecords),
    'recordsFiltered' => intval($totalFiltered),
    'data'            => $data,
]);

