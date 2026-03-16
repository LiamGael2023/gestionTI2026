<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../controllers/CaracteristicasController.php";

header('Content-Type: application/json; charset=utf-8');

$idTipo = $_POST["idTipoCaracteristica"] ?? null;

if ($idTipo) {
    $caracts = CaracteristicasController::ctrMostrarCaracteristicas("idTipoCaracteristica", $idTipo);
} else {
    $caracts = CaracteristicasController::ctrMostrarCaracteristicas(null, null);
}

if ($caracts === "error" || !$caracts) {
    echo json_encode([]);
    exit;
}

$result = [];
foreach ($caracts as $c) {
    $id = $c['idCaracteristica'] ?? $c->idCaracteristica ?? null;
    $valor = $c['valor'] ?? $c->valor ?? '';
    $tipo = $c['tipo'] ?? $c->tipo ?? '';

    if ($id === null) continue;

    $result[] = [
        'idCaracteristica' => (int)$id,
        'valor' => (string)$valor,
        'tipo' => (string)$tipo
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
