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
    // soporta tanto array asociativo como objeto
    $id = isset($t['idTipoCaracteristica']) ? $t['idTipoCaracteristica'] : (isset($t->idTipoCaracteristica) ? $t->idTipoCaracteristica : (isset($t['id']) ? $t['id'] : null));
    $desc = isset($t['descripcion']) ? $t['descripcion'] : (isset($t->descripcion) ? $t->descripcion : '');
    $activo = isset($t['activo']) ? (bool)$t['activo'] : (isset($t->activo) ? (bool)$t->activo : true);
    $descCorta = mb_strimwidth((string)$desc, 0, 60, '...');

    if ($id === null) continue;

    $result[] = [
        'idTipoCaracteristica' => (int)$id,
        'descripcion' => (string)$desc,
        'descripcionCorta' => $descCorta,
        'activo' => $activo
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
