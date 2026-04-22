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

    $muestras = $model->obtenerPorEstado('Por Firmar', $start, $length, $tipoServicio);
    $total = $model->contarPorEstado('Por Firmar', $tipoServicio);

    $data = [];
    foreach ($muestras as $row) {
        $id = isset($row['Id_Muestra']) ? intval($row['Id_Muestra']) : 0;
        $id_cliente = isset($row['Id_Cliente']) ? intval($row['Id_Cliente']) : 0;
        $agricultor = valorTexto($row['Agricultor'] ?? null);
        $valle = valorTexto($row['Valle'] ?? null);
        $servicio = tipoServicioUI($row['Tipo_Servicio'] ?? null);
        $fecha_analisis = valorTexto($row['Fecha_Analisis'] ?? null);
        $estado = valorTexto($row['Estado'] ?? null);
        $tipo = valorTexto($row['TipoMuestra'] ?? null);
        $agricultorUrl = rawurlencode($agricultor);
        $accion = '<a class="btn btn-sm btn-success" href="?module=laboratorio&action=muestra&subaction=firma_agricultor&id_muestra=' . $id . '&id_cliente=' . $id_cliente . '&agricultor=' . $agricultorUrl . '" title="Revisar y firmar"><i class="ti ti-signature"></i></a>';
        
        $data[] = [$id, $agricultor, $valle, $servicio, $fecha_analisis, $estado, $tipo, $accion];
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