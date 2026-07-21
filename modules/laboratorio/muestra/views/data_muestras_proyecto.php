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
        
        $accion = '<div class="dropdown">'
            . '<a class="btn btn-ghost-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">'
            . '<i class="ti ti-dots-vertical"></i>'
            . '</a>'
            . '<div class="dropdown-menu dropdown-menu-end">'
            . '<a class="dropdown-item" href="javascript:verDetalleMuestra(' . $id . ')">'
            . '<i class="ti ti-eye me-2"></i> Ver Detalle'
            . '</a>'
            . '<a class="dropdown-item" href="javascript:editarMuestra(' . $id . ')">'
            . '<i class="ti ti-edit me-2"></i> Editar'
            . '</a>'
            . '<a class="dropdown-item text-danger" href="javascript:eliminarMuestra(' . $id . ')">'
            . '<i class="ti ti-trash me-2"></i> Eliminar'
            . '</a>'
            . '</div>'
            . '</div>';
        
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
