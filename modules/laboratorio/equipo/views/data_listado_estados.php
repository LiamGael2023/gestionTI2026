<?php
session_start();
require_once '../../../../core/Auth.php';
require_once '../../../../config/db.php';
Auth::check();

$conn = Conexion::conectar();

// ConfiguraciÃ³n de columnas para estados
$columns = array(
    0 => 'ee.Id_Estado',
    1 => 'ee.Nombre',
    2 => 'ee.Descripcion',
    3 => 'ee.Id_Estado'
);

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Validar Ã­ndice de columna
if ($colIndex < 0 || $colIndex >= count($columns)) {
    $colIndex = 0;
}

// Base SQL
$sqlBase = " FROM laboratorio.Equipo_Estado ee ";
$sqlWhere = " WHERE 1=1 ";
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (ee.Nombre LIKE ? OR ee.Descripcion LIKE ?) ";
    $params = array("%$search%", "%$search%");
}

// Conteo Total
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM laboratorio.Equipo_Estado");
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

// Datos con paginaciÃ³n
$sqlData = "SELECT ee.* " . $sqlBase . $sqlWhere . 
           " ORDER BY ee.Activo DESC, " . $columns[$colIndex] . " " . $colDir . 
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en consulta de datos', 'details' => sqlsrv_errors()]));
}

$data = array();
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    // Botones de acciÃ³n segÃºn estado activo/inactivo
    if ($row['Activo']) {
        // Estado activo: editar y eliminar
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-primary" onclick="editarEstado(' . $row['Id_Estado'] . ')" title="Editar">' .
                    '<i class="ti ti-pencil"></i></button>' .
                    '<button type="button" class="btn btn-ghost-danger" onclick="eliminarEstado(' . $row['Id_Estado'] . ')" title="Eliminar">' .
                    '<i class="ti ti-trash"></i></button>' .
                    '</div>';
        $badgeEstado = '';
    } else {
        // Estado inactivo: mostrar badge y botÃ³n de reactivar
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-success" onclick="reactivarEstado(' . $row['Id_Estado'] . ')" title="Reactivar">' .
                    '<i class="ti ti-check"></i></button>' .
                    '</div>';
        $badgeEstado = ' <span class="badge bg-secondary">Inactivo</span>';
    }

    $data[] = array(
        $contador++,
        htmlspecialchars($row['Nombre']) . $badgeEstado,
        htmlspecialchars($row['Descripcion'] ?: '-'),
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

