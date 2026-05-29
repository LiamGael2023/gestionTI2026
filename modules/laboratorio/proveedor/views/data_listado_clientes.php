<?php
session_start();
require_once '../../../../config/db.php';

$conn = Conexion::conectar();

$columns = [
    0 => 'c.Id_Cliente',
    1 => 'c.Nombres',
    2 => 'c.Apellido_Paterno',
    3 => 'c.Id_Cliente',
    4 => 'c.Id_Cliente',
    5 => 'c.Id_Cliente',
    6 => 'c.Id_Cliente'
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

$sqlBase  = " FROM laboratorio.Cliente c ";
$sqlWhere = " WHERE 1=1 ";
$params   = [];

if (!empty($search)) {
    $sqlWhere .= " AND (c.Nombres LIKE ? OR c.Apellido_Paterno LIKE ? OR c.Apellido_Materno LIKE ?) ";
    $params    = ["%$search%", "%$search%", "%$search%"];
}

$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM laboratorio.Cliente");
if ($stmtTotal === false) { die(json_encode(['error' => 'Error conteo total'])); }
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

$paramsFilter  = $params;
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) AS total " . $sqlBase . $sqlWhere, $paramsFilter);
if ($stmtFiltrados === false) { die(json_encode(['error' => 'Error conteo filtrado'])); }
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

$sqlData = "SELECT c.Id_Cliente, c.Nombres, c.Apellido_Paterno, c.Apellido_Materno, c.Activo " . $sqlBase . $sqlWhere .
           " ORDER BY c.Activo DESC, c.Nombres ASC, c.Apellido_Paterno ASC" .
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) { die(json_encode(['error' => 'Error en datos'])); }

$data     = [];
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    $apellidos = trim(($row['Apellido_Paterno'] ?? '') . ' ' . ($row['Apellido_Materno'] ?? ''));
    if ($row['Activo']) {
        $acciones = '<div class="btn-group btn-group-sm">' .
            '<button type="button" class="btn btn-ghost-primary" onclick="editarCliente(' . $row['Id_Cliente'] . ')" title="Editar"><i class="ti ti-pencil"></i></button>' .
            '<button type="button" class="btn btn-ghost-danger" onclick="eliminarCliente(' . $row['Id_Cliente'] . ')" title="Eliminar"><i class="ti ti-trash"></i></button>' .
            '</div>';
        $badge = '';
    } else {
        $acciones = '<div class="btn-group btn-group-sm">' .
            '<button type="button" class="btn btn-ghost-success" onclick="reactivarCliente(' . $row['Id_Cliente'] . ')" title="Reactivar"><i class="ti ti-check"></i></button>' .
            '</div>';
        $badge = ' <span class="badge bg-secondary">Inactivo</span>';
    }

    $data[] = [
        $contador++,
        htmlspecialchars($row['Nombres'] ?? '') . $badge,
        htmlspecialchars($apellidos ?: '-'),
        '-',
        '-',
        '-',
        $acciones
    ];
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data'            => $data
]);
