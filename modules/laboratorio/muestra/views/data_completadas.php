<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../../../config/db.php';
require_once '../models/MuestraModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = Conexion::conectar();
    if ($conn === false) {
        throw new Exception('No se pudo conectar a la BD');
    }
    
    $model = new MuestraModel($conn);
    
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);

    // Obtener completadas (Validado + Rechazado)
    $sql = "SELECT m.*, 
                   CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                   CONCAT(m.Eje_X, ', ', m.Eje_Y) AS Ubicacion,
                   CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo' 
                        WHEN da.Id_Muestra IS NOT NULL THEN 'Agua' 
                        ELSE 'Sin clasificar' END AS TipoMuestra
            FROM laboratorio.Muestra_Lab m 
            INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente 
            LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
            LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
            WHERE m.Activo = 1 AND (m.Estado = 'Validado' OR m.Estado = 'Rechazado')
            ORDER BY m.Id_Muestra DESC 
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

    $conn = Conexion::conectar();
    $stmt = sqlsrv_query($conn, $sql, array($start, $length));
    if ($stmt === false) {
        throw new Exception('Error en query: ' . print_r(sqlsrv_errors(), true));
    }
    
    $muestras = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $muestras[] = $row;
    }

    // Contar total
    $sql_count = "SELECT COUNT(*) as total FROM laboratorio.Muestra_Lab WHERE Activo = 1 AND (Estado = 'Validado' OR Estado = 'Rechazado')";
    $stmt_count = sqlsrv_query($conn, $sql_count);
    $count_row = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
    $total = $count_row['total'] ?? 0;

    $data = [];
    foreach ($muestras as $row) {
        $id = isset($row['Id_Muestra']) ? intval($row['Id_Muestra']) : 0;
        $agricultor = isset($row['Agricultor']) ? strval($row['Agricultor']) : '-';
        $valle = isset($row['Valle']) ? strval($row['Valle']) : '-';
        $fecha_recepcion = isset($row['Fecha_Recepcion']) ? strval($row['Fecha_Recepcion']) : '-';
        $estado = isset($row['Estado']) ? strval($row['Estado']) : '-';
        $fecha_validacion = isset($row['Fecha_Validacion']) ? strval($row['Fecha_Validacion']) : '-';
        $tipo = isset($row['TipoMuestra']) ? strval($row['TipoMuestra']) : '-';
        
        $accion = '<button class="btn btn-sm btn-info" onclick="verDetalles(' . $id . ')"><i class="ti ti-eye"></i></button>';
        
        $data[] = [$id, $agricultor, $valle, $fecha_recepcion, $estado, $fecha_validacion, $tipo, $accion];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($total),
        'recordsFiltered' => intval($total),
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
}
?>
