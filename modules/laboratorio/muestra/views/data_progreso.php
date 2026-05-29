<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../../../config/db.php';
require_once '../models/MuestraModel.php';

header('Content-Type: application/json; charset=utf-8');

function valorTexto($valor, $fallback = '-') {
    if ($valor instanceof DateTime) {
        return $valor->format('d-m-Y');
    }
    if ($valor === null) {
        return $fallback;
    }
    $texto = trim((string)$valor);
    return $texto === '' ? $fallback : $texto;
}

function tipoServicioUI($tipoServicio) {
    $valor = strtolower(trim((string)$tipoServicio));
    if ($valor === 'interno' || $valor === 'externo') {
        return ucfirst($valor);
    }
    return valorTexto($tipoServicio);
}

try {
    $conn = Conexion::conectar();
    if ($conn === false) {
        throw new Exception('No se pudo conectar a la BD');
    }
    
    $model = new MuestraModel($conn);
    
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $tipoServicio = strtolower(trim((string)($_POST['tipo_servicio'] ?? 'todos')));
    if ($tipoServicio !== 'interno' && $tipoServicio !== 'externo') {
        $tipoServicio = '';
    }
    $search = trim((string)($_POST['search']['value'] ?? ''));

    $total = $model->contarPorEstado('En Analisis', $tipoServicio);
    $totalFiltered = ($search !== '') ? $model->contarPorEstado('En Analisis', $tipoServicio, $search) : $total;
    $muestras = $model->obtenerPorEstado('En Analisis', $start, $length, $tipoServicio, $search);

    $data = [];
    foreach ($muestras as $row) {
        $id = isset($row['Id_Muestra']) ? intval($row['Id_Muestra']) : 0;
        $agricultor = valorTexto($row['Agricultor'] ?? null);
        $valle = valorTexto($row['Valle'] ?? null);
        $fecha = valorTexto($row['Fecha_Recepcion'] ?? null);
        $servicio = tipoServicioUI($row['Tipo_Servicio'] ?? null);
        $tipo = valorTexto($row['TipoMuestra'] ?? null);
        
        $idCliente = intval($row['Id_Cliente'] ?? 0);
        $agricultorEsc = htmlspecialchars($agricultor, ENT_QUOTES, 'UTF-8');
        $accion = '<button class="btn btn-sm btn-info" onclick="abrirModalContinuarAnalisis(' . $id . ', ' . $idCliente . ', \'' . $agricultorEsc . '\')">'
            . '<i class="ti ti-player-play"></i> Continuar análisis</button>';
        
        $data[] = [$id, $agricultor, $valle, $fecha, $servicio, $tipo, $accion];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $totalFiltered,
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