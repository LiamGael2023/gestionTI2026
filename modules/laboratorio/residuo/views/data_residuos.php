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

$sql_count = "SELECT COUNT(*) as total FROM laboratorio.Residuo_Catalogo";
$stmt_count = sqlsrv_query($conn, $sql_count);
$row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
$totalRecords = $row_count['total'];

// Consulta principal
$sql = "SELECT 
        ROW_NUMBER() OVER (ORDER BY Id_Residuo_Cat DESC) as No,
        Id_Residuo_Cat,
        Nombre_Item,
        Tipo_Principal,
        Subcategoria,
        Unidad_Referencia,
        Activo
        FROM laboratorio.Residuo_Catalogo";

$params = [];

if (!empty($search)) {
    $sql .= " WHERE (Nombre_Item LIKE ? OR Codigo_Item LIKE ? OR Subcategoria LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam, $searchParam];
} else {
    $sql .= " WHERE 1=1";
}

$sql .= " ORDER BY Id_Residuo_Cat DESC
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$params[] = $start;
$params[] = $length;

$stmt = sqlsrv_query($conn, $sql, $params);
$residuos = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $activo = intval($row['Activo'] ?? 1);
    
    // Botones según estado
    if ($activo == 1) {
        $acciones = '<button class="btn btn-sm btn-primary me-2" title="Editar" onclick="editarResiduo(' . $row['Id_Residuo_Cat'] . ')"><i class="ti ti-edit"></i></button>
                     <button class="btn btn-sm btn-danger" title="Eliminar" onclick="eliminarResiduo(' . $row['Id_Residuo_Cat'] . ')"><i class="ti ti-trash"></i></button>';
    } else {
        $acciones = '<button class="btn btn-sm btn-ghost-success" onclick="reactivarResiduo(' . $row['Id_Residuo_Cat'] . ')" title="Reactivar"><i class="ti ti-check"></i></button>';
    }
    
    $residuos[] = [
        $row['No'],
        htmlspecialchars($row['Nombre_Item']),
        $row['Tipo_Principal'],
        htmlspecialchars($row['Subcategoria']),
        htmlspecialchars($row['Unidad_Referencia']),
        $acciones
    ];
}

$filteredRecords = !empty($search) ? count($residuos) : $totalRecords;

header('Content-Type: application/json');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $residuos
]);
