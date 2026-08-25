<?php
session_start();
require_once '../../../../core/Auth.php';
require_once '../../../../config/db.php';
Auth::check();

$conn = Conexion::conectar();

$columns = [
    0 => 'p.Id_Proveedor',
    1 => 'p.Razon_Social',
    2 => 'p.Ruc',
    3 => 'p.Nombre_Contacto',
    4 => 'p.Telefono',
    5 => 'p.Email',
    6 => 'p.Id_Proveedor'
];

$draw     = intval($_POST['draw'] ?? 0);
$start    = intval($_POST['start'] ?? 0);
$length   = intval($_POST['length'] ?? 10);
$search   = $_POST['search']['value'] ?? '';
$colIndex = intval($_POST['order'][0]['column'] ?? 0);
$colDir   = in_array($_POST['order'][0]['dir'] ?? '', ['asc', 'desc']) ? $_POST['order'][0]['dir'] : 'asc';

if ($colIndex < 0 || $colIndex >= count($columns)) {
    $colIndex = 0;
}

$sqlBase  = " FROM laboratorio.Proveedor p ";
$sqlWhere = " WHERE 1=1 ";
$params   = [];

if (!empty($search)) {
    $sqlWhere .= " AND (p.Razon_Social LIKE ? OR p.Ruc LIKE ? OR p.Nombre_Contacto LIKE ? OR p.Email LIKE ?) ";
    $params    = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM laboratorio.Proveedor");
if ($stmtTotal === false) { die(json_encode(['error' => 'Error conteo total'])); }
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

$paramsFilter   = $params;
$stmtFiltrados  = sqlsrv_query($conn, "SELECT COUNT(*) AS total " . $sqlBase . $sqlWhere, $paramsFilter);
if ($stmtFiltrados === false) { die(json_encode(['error' => 'Error conteo filtrado'])); }
$totalFiltered  = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

$sqlData = "SELECT p.* " . $sqlBase . $sqlWhere .
           " ORDER BY p.Activo DESC, " . $columns[$colIndex] . " " . $colDir .
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) { die(json_encode(['error' => 'Error en datos'])); }

$data     = [];
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    if ($row['Activo']) {
        $acciones = '<div class="btn-group btn-group-sm">' .
            '<button type="button" class="btn btn-ghost-primary" onclick="editarProveedor(' . $row['Id_Proveedor'] . ')" title="Editar"><i class="ti ti-pencil"></i></button>' .
            '<button type="button" class="btn btn-ghost-danger" onclick="eliminarProveedor(' . $row['Id_Proveedor'] . ')" title="Eliminar"><i class="ti ti-trash"></i></button>' .
            '</div>';
        $badge = '';
    } else {
        $acciones = '<div class="btn-group btn-group-sm">' .
            '<button type="button" class="btn btn-ghost-success" onclick="reactivarProveedor(' . $row['Id_Proveedor'] . ')" title="Reactivar"><i class="ti ti-check"></i></button>' .
            '</div>';
        $badge = ' <span class="badge bg-secondary">Inactivo</span>';
    }

    $data[] = [
        $contador++,
        htmlspecialchars($row['Razon_Social']) . $badge,
        htmlspecialchars($row['Ruc'] ?: '-'),
        htmlspecialchars($row['Nombre_Contacto'] ?: '-'),
        htmlspecialchars($row['Telefono'] ?: '-'),
        htmlspecialchars($row['Email'] ?: '-'),
        $acciones
    ];
}

echo json_encode([
    "draw"            => $draw,
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
]);

