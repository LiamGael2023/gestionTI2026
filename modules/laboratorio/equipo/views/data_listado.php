<?php
session_start();
require_once '../../../../config/db.php';

$conn = Conexion::conectar();

// Configuración de columnas
$columns = array(
    0 => 'el.Id_Equipo',
    1 => 'el.Nombre',
    2 => 'el.Proveedor',
    3 => 'el.Fecha_Proxima_Calibracion',
    4 => 'el.Fecha_Ultima_Calibracion',
    5 => 'ee.Nombre',
    6 => 'el.Id_Equipo'
);

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Base SQL
$sqlBase = " FROM laboratorio.Equipo_Lab el 
             LEFT JOIN laboratorio.Equipo_Estado ee ON el.Id_Estado = ee.Id_Estado ";
$sqlWhere = " WHERE 1=1 ";
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (el.Nombre LIKE ? OR el.Proveedor LIKE ?) ";
    $params = array("%$search%", "%$search%");
}

// Conteo Total
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM laboratorio.Equipo_Lab");
if ($stmtTotal === false) {
    die(json_encode(['error' => 'Error en consulta total']));
}
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

// Conteo Filtrado
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) as total " . $sqlBase . $sqlWhere, $params);
if ($stmtFiltrados === false) {
    die(json_encode(['error' => 'Error en consulta filtrada']));
}
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

// Datos con paginación
$sqlData = "SELECT el.*, ee.Nombre as Estado_Nombre " . $sqlBase . $sqlWhere . 
           " ORDER BY el.Activo DESC, " . $columns[$colIndex] . " " . $colDir . 
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en consulta de datos']));
}

$data = array();
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    // Formatear fechas
    $proxima_cal = ($row['Fecha_Proxima_Calibracion']) ? $row['Fecha_Proxima_Calibracion']->format('d/m/Y') : '-';
    $ultima_cal = ($row['Fecha_Ultima_Calibracion']) ? $row['Fecha_Ultima_Calibracion']->format('d/m/Y') : '-';
    $estado = $row['Estado_Nombre'] ?: 'Sin estado';
    
    // Badges por estado - Agregar "Inactivo" si está desactivado
    if ($row['Activo'] == 0) {
        $estadoBadge = '<span class="badge bg-danger">Inactivo</span>';
    } else {
        $estadoBadge = match(strtolower($estado)) {
            'disponible' => '<span class="badge badge-disponible">Disponible</span>',
            'correctivo' => '<span class="badge badge-correctivo">Correctivo</span>',
            'preventivo' => '<span class="badge badge-preventivo">Preventivo</span>',
            'predictivo' => '<span class="badge badge-predictivo">Predictivo</span>',
            default => '<span class="badge bg-secondary">' . htmlspecialchars($estado) . '</span>'
        };
    }

    // Botones de acción - Diferentes según si está activo o no
    if ($row['Activo'] == 1) {
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-primary" onclick="editarEquipo(' . $row['Id_Equipo'] . ')" title="Editar">' .
                    '<i class="ti ti-pencil"></i></button>' .
                    '<button type="button" class="btn btn-ghost-danger" onclick="eliminarEquipo(' . $row['Id_Equipo'] . ')" title="Eliminar">' .
                    '<i class="ti ti-trash"></i></button>' .
                    '</div>';
    } else {
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-success" onclick="reactivarEquipo(' . $row['Id_Equipo'] . ')" title="Reactivar">' .
                    '<i class="ti ti-check"></i></button>' .
                    '</div>';
    }

    $data[] = array(
        $contador++,
        htmlspecialchars($row['Nombre']),
        htmlspecialchars($row['Proveedor'] ?: '-'),
        $proxima_cal,
        $ultima_cal,
        $estadoBadge,
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
