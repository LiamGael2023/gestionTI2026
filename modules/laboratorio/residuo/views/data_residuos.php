<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$conn = Conexion::conectar();

// Asegurar que la columna existe en la tabla
sqlsrv_query($conn, "IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='laboratorio' AND TABLE_NAME='Residuo_Catalogo' AND COLUMN_NAME='Unidad_Referencia') ALTER TABLE laboratorio.Residuo_Catalogo ADD Unidad_Referencia NVARCHAR(50) NULL");

// Variables de DataTables
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

$sql_count = "SELECT COUNT(*) as total FROM laboratorio.Residuo_Catalogo";
$stmt_count = sqlsrv_query($conn, $sql_count);
$row_count = $stmt_count ? sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC) : null;
$totalRecords = $row_count ? intval($row_count['total']) : 0;

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
        OFFSET " . $start . " ROWS FETCH NEXT " . $length . " ROWS ONLY";

$stmt = sqlsrv_query($conn, $sql, !empty($params) ? $params : null);
if ($stmt === false) {
    $errors = sqlsrv_errors();
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error en consulta: ' . ($errors[0]['message'] ?? 'Error desconocido')
    ]);
    exit;
}
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

// Contar registros filtrados
if (!empty($search)) {
    $sql_filtered = "SELECT COUNT(*) as total FROM laboratorio.Residuo_Catalogo WHERE (Nombre_Item LIKE ? OR Codigo_Item LIKE ? OR Subcategoria LIKE ?)";
    $sParam = '%' . $search . '%';
    $stmt_filtered = sqlsrv_query($conn, $sql_filtered, [$sParam, $sParam, $sParam]);
    $row_filtered = $stmt_filtered ? sqlsrv_fetch_array($stmt_filtered, SQLSRV_FETCH_ASSOC) : null;
    $filteredRecords = $row_filtered ? intval($row_filtered['total']) : 0;
} else {
    $filteredRecords = $totalRecords;
}

header('Content-Type: application/json');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $residuos
]);
