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
    
    $id_proyecto = intval($_POST['id_proyecto'] ?? 0);
    if ($id_proyecto <= 0) {
        throw new Exception('ID de proyecto inválido');
    }
    
    $model = new MuestraModel($conn);
    
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);

    $muestras = $model->obtenerMuestrasPorProyecto($id_proyecto, null, $start, $length);
    $total = $model->contarMuestrasPorProyecto($id_proyecto);

    $data = [];
    $contador = $start + 1;
    foreach ($muestras as $row) {
        $id = isset($row['Id_Muestra']) ? intval($row['Id_Muestra']) : 0;
        $agricultor = isset($row['Agricultor']) ? strval($row['Agricultor']) : '-';
        $ubicacion = isset($row['Ubicacion']) ? strval($row['Ubicacion']) : '-';
        $valle = isset($row['Valle']) ? strval($row['Valle']) : '-';
        $fecha = isset($row['Fecha_Recepcion']) ? strval($row['Fecha_Recepcion']) : '-';
        $servicio = isset($row['Tipo_Servicio']) ? strval($row['Tipo_Servicio']) : '-';
        $estado = isset($row['Estado']) ? strval($row['Estado']) : '-';
        $tipo = isset($row['TipoMuestra']) ? strval($row['TipoMuestra']) : '-';
        
        // Badge para estado
        $estado_badge = '<span class="badge ';
        if ($estado === 'Validado') {
            $estado_badge .= 'bg-success';
        } elseif ($estado === 'En Analisis') {
            $estado_badge .= 'bg-info';
        } elseif ($estado === 'Por Firmar') {
            $estado_badge .= 'bg-warning';
        } elseif ($estado === 'Recepcionado') {
            $estado_badge .= 'bg-secondary';
        } else {
            $estado_badge .= 'bg-light text-dark'; // Pendiente
        }
        $estado_badge .= '">' . htmlspecialchars($estado) . '</span>';
        
        $accion = '<a href="javascript:editarMuestra(' . $id . ')" class="btn btn-sm btn-primary me-1"><i class="ti ti-edit"></i></a> ';
        $accion .= '<a href="javascript:eliminarMuestra(' . $id . ')" class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></a>';
        
        $data[] = [$contador++, $agricultor, $ubicacion, $valle, $fecha, $servicio, $estado_badge, $tipo, $accion];
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
