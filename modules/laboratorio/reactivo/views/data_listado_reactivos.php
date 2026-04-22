<?php
session_start();
require_once '../../../../config/db.php';

$conn = Conexion::conectar();

// Configuración de columnas
$columns = array(
    0 => 'r.Id_Reactivo',
    1 => 'r.Nombre',
    2 => 'r.Unidad_Medida',
    3 => 'r.Cantidad_Stock',
    4 => 'r.Fecha_Vencimiento',
    5 => 'r.Fecha_Ingreso',
    6 => 'r.Id_Reactivo'
);

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Validar índice de columna
if ($colIndex < 0 || $colIndex >= count($columns)) {
    $colIndex = 0;
}

// Base SQL
$sqlBase = " FROM laboratorio.Reactivo_Lab r ";
$sqlWhere = " WHERE 1=1 ";
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (r.Nombre LIKE ?) ";
    $params = array("%$search%");
}

// Conteo Total
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM laboratorio.Reactivo_Lab");
if ($stmtTotal === false) {
    die(json_encode(['error' => 'Error en consulta total', 'details' => sqlsrv_errors()]));
}
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

// Conteo Filtrado
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) as total " . $sqlBase . $sqlWhere, $params);
if ($stmtFiltrados === false) {
    die(json_encode(['error' => 'Error en consulta filtrada', 'details' => sqlsrv_errors()]));
}
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

// Datos con paginación
$sqlData = "SELECT r.* " . $sqlBase . $sqlWhere . 
           " ORDER BY r.Activo DESC, " . $columns[$colIndex] . " " . $colDir . 
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en consulta de datos', 'details' => sqlsrv_errors()]));
}

$data = array();
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    // Formatear fechas
    $fecha_vencimiento = ($row['Fecha_Vencimiento']) ? $row['Fecha_Vencimiento']->format('d/m/Y') : '-';
    $fecha_ingreso = ($row['Fecha_Ingreso']) ? $row['Fecha_Ingreso']->format('d/m/Y') : '-';

    // Botones de acción según estado activo/inactivo
    if ($row['Activo']) {
        // Activo: editar y eliminar
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-primary" onclick="editarReactivo(' . $row['Id_Reactivo'] . ')" title="Editar">' .
                    '<i class="ti ti-pencil"></i></button>' .
                    '<button type="button" class="btn btn-ghost-danger" onclick="eliminarReactivo(' . $row['Id_Reactivo'] . ')" title="Eliminar">' .
                    '<i class="ti ti-trash"></i></button>' .
                    '</div>';
        $badgeEstado = '';
    } else {
        // Inactivo: mostrar badge y botón de reactivar
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-success" onclick="reactivarReactivo(' . $row['Id_Reactivo'] . ')" title="Reactivar">' .
                    '<i class="ti ti-check"></i></button>' .
                    '</div>';
        $badgeEstado = ' <span class="badge bg-secondary">Inactivo</span>';
    }

    $data[] = array(
        $contador++,
        htmlspecialchars($row['Nombre']) . $badgeEstado,
        htmlspecialchars($row['Unidad_Medida'] ?: '-'),
        intval($row['Cantidad_Stock']),
        $fecha_vencimiento,
        $fecha_ingreso,
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
