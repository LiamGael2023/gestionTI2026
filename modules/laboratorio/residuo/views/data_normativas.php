<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$conn = Conexion::conectar();

// Variables de DataTables
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

$sql_count = "SELECT COUNT(*) as total FROM laboratorio.Normativa_SST";
$stmt_count = sqlsrv_query($conn, $sql_count);
$row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
$totalRecords = $row_count['total'];

// Consulta principal
$sql = "SELECT 
        ROW_NUMBER() OVER (ORDER BY Id_Normativa_SST DESC) as No,
        Id_Normativa_SST,
        Nombre_Ley,
        Descripcion,
        Activo
        FROM laboratorio.Normativa_SST";

$params = [];

if (!empty($search)) {
    $sql .= " WHERE (Nombre_Ley LIKE ? OR Descripcion LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam];
} else {
    $sql .= " WHERE 1=1";
}

$sql .= " ORDER BY Id_Normativa_SST DESC
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$params[] = $start;
$params[] = $length;

$stmt = sqlsrv_query($conn, $sql, $params);
$normativas = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $activo = intval($row['Activo'] ?? 1);
    
    // Botones según estado
    if ($activo == 1) {
        $acciones = '<button class="btn btn-sm btn-primary me-2" title="Editar" onclick="editarNormativa(' . $row['Id_Normativa_SST'] . ')"><i class="ti ti-edit"></i></button>
                     <button class="btn btn-sm btn-danger" title="Eliminar" onclick="eliminarNormativa(' . $row['Id_Normativa_SST'] . ')"><i class="ti ti-trash"></i></button>';
    } else {
        $acciones = '<button class="btn btn-sm btn-ghost-success" onclick="reactivarNormativa(' . $row['Id_Normativa_SST'] . ')" title="Reactivar"><i class="ti ti-check"></i></button>';
    }
    
    $normativas[] = [
        $row['No'],
        htmlspecialchars($row['Nombre_Ley']),
        htmlspecialchars($row['Descripcion']),
        $acciones
    ];
}

$filteredRecords = !empty($search) ? count($normativas) : $totalRecords;

header('Content-Type: application/json');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $normativas
]);
