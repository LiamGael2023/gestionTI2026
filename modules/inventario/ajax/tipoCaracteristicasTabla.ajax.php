<?php
// modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../controllers/TipoCaracteristicasController.php";

header('Content-Type: application/json; charset=utf-8');

$tipos = TipoCaracteristicasController::ctrMostrarTipoCaracteristicas(null, null);

if ($tipos === "error" || $tipos === false || $tipos === null) {
    echo json_encode([]);
    exit;
}

$result = [];
foreach ($tipos as $t) {
    $id = isset($t['idTipoCaracteristica']) ? $t['idTipoCaracteristica'] : (isset($t->idTipoCaracteristica) ? $t->idTipoCaracteristica : null);
    $desc = isset($t['descripcion']) ? $t['descripcion'] : (isset($t->descripcion) ? $t->descripcion : '');
    if ($id === null) continue;
    $result[] = [
        'idTipoCaracteristica' => (int)$id,
        'descripcion' => (string)$desc
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
