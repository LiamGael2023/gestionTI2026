<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$conn = Conexion::conectar();

// Consulta principal (sin paginación server-side)
$sql = "SELECT 
        ROW_NUMBER() OVER (ORDER BY Id_Registro_Res DESC) as No,
        Id_Registro_Res,
        Codigo_SST,
        Ubicacion,
        Anio,
        Mes,
        Activo
        FROM laboratorio.Registro_Residuos_Log
        ORDER BY Id_Registro_Res DESC";

$stmt = sqlsrv_query($conn, $sql);
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

header('Content-Type: application/json');
echo json_encode([
    'data' => $informes
]);
?>
