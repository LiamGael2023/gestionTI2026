<?php
session_start();
require_once '../../../../config/db.php';

$conn = Conexion::conectar();

$columns = array(
    0 => 'p.Id_Parametro',
    1 => 'p.Nombre',
    2 => 'p.Unidad_Medida',
    3 => 'p.Categoria',
    4 => 'p.Metodo_Utilizado',
    5 => 'p.Id_Parametro'
);

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

if ($colIndex < 0 || $colIndex >= count($columns)) {
    $colIndex = 0;
}

$sqlBase = "FROM laboratorio.Parametro_Analisis p LEFT JOIN laboratorio.Servicio_Tecnico s ON p.Id_Servicio = s.Id_Servicio";
$sqlWhere = " WHERE p.Activo = 1";
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (p.Nombre LIKE ? OR p.Categoria LIKE ? OR s.Nombre LIKE ?)";
    $params = array("%$search%", "%$search%", "%$search%");
}

$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM laboratorio.Parametro_Analisis WHERE Activo = 1");
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) as total " . $sqlBase . $sqlWhere, $params);
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

$sqlData = "SELECT p.*, s.Nombre as Servicio_Nombre " . $sqlBase . $sqlWhere .
           " ORDER BY " . $columns[$colIndex] . " " . $colDir .
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en SQL', 'details' => sqlsrv_errors()]));
}

$data = array();
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    $acciones = '<div class="btn-group btn-group-sm">' .
                '<button class="btn btn-ghost-primary" onclick="editarParametro(' . $row['Id_Parametro'] . ')" title="Editar"><i class="ti ti-pencil"></i></button>' .
                '<button class="btn btn-ghost-danger" onclick="eliminarParametro(' . $row['Id_Parametro'] . ')" title="Eliminar"><i class="ti ti-trash"></i></button>' .
                '</div>';
    
    $servicio = $row['Servicio_Nombre'] ? htmlspecialchars($row['Servicio_Nombre']) : '<span class="text-muted">Sin servicio</span>';
    
    $data[] = array(
        $contador++,
        htmlspecialchars($row['Nombre']),
        $servicio,
        htmlspecialchars($row['Unidad_Medida'] ?: '-'),
        htmlspecialchars($row['Categoria'] ?: '-'),
        htmlspecialchars($row['Metodo_Utilizado'] ?: '-'),
        $acciones
    );
}

$json_data = array(
    "draw" => $draw,
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
);

echo json_encode($json_data);
?>
