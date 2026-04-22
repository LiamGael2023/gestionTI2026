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

$sql_count = "SELECT COUNT(*) as total FROM laboratorio.Registro_Residuos_Log";
$stmt_count = sqlsrv_query($conn, $sql_count);
$row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
$totalRecords = $row_count['total'];

// Consulta principal
$sql = "SELECT 
        ROW_NUMBER() OVER (ORDER BY Id_Registro_Res DESC) as No,
        Id_Registro_Res,
        Codigo_SST,
        Ubicacion,
        Anio,
    Mes,
    Activo
        FROM laboratorio.Registro_Residuos_Log
    WHERE 1 = 1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (Codigo_SST LIKE ? OR Ubicacion LIKE ? OR Anio LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam, $searchParam];
}

$sql .= " ORDER BY Id_Registro_Res DESC
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$params[] = $start;
$params[] = $length;

$stmt = sqlsrv_query($conn, $sql, $params);
$informes = [];

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $mes_nombre = isset($meses[$row['Mes']]) ? $meses[$row['Mes']] : $row['Mes'];
    $activo = intval($row['Activo'] ?? 1);

    if ($activo === 1) {
        $acciones = '<button class="btn btn-sm btn-ghost-info" onclick="verInforme(' . $row['Id_Registro_Res'] . ')" title="Ver"><i class="ti ti-eye"></i></button>
         <button class="btn btn-sm btn-ghost-warning" onclick="editarInforme(' . $row['Id_Registro_Res'] . ')" title="Editar"><i class="ti ti-pencil"></i></button>
         <button class="btn btn-sm btn-ghost-danger" onclick="eliminarInforme(' . $row['Id_Registro_Res'] . ')" title="Desactivar"><i class="ti ti-trash"></i></button>';
    } else {
        $acciones = '<button class="btn btn-sm btn-ghost-success" onclick="reactivarInforme(' . $row['Id_Registro_Res'] . ')" title="Reactivar"><i class="ti ti-check"></i></button>';
    }
    
    $informes[] = [
        $row['No'],
        htmlspecialchars($row['Codigo_SST']),
        htmlspecialchars($row['Ubicacion']),
        $row['Anio'],
        $mes_nombre,
        $acciones
    ];
}

$filteredRecords = !empty($search) ? count($informes) : $totalRecords;

header('Content-Type: application/json');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $informes
]);
?>