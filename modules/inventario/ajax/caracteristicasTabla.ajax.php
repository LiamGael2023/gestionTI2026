<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
 * El modelo mdlMostrarCaracteristicas recibe: ($tabla, $item, $valor)
 * El controller ctrMostrarCaracteristicas recibe: ($item, $valor)
 *   y pasa la tabla internamente como "inventario.caracteristicas"
 *
 * La BD devuelve por cada fila:
 *   idCaracteristica, idTipoCaracteristica, valor,
 *   fechaCreacion, idUsuarioCreacion, fechaModificacion,
 *   idUsuarioModifica, tipoDescripcion (del JOIN)
 */

require_once __DIR__ . "/../controllers/CaracteristicasController.php";

header('Content-Type: application/json; charset=utf-8');

// Acepta GET (query string) y POST
$idTipo = $_GET["idTipoCaracteristica"] ?? $_POST["idTipoCaracteristica"] ?? null;

if (!$idTipo) {
    echo json_encode([]);
    exit;
}

// El controller recibe ($item, $valor) — sin tabla, la pone internamente
$caracts = CaracteristicasController::ctrMostrarCaracteristicas("idTipoCaracteristica", $idTipo);

if ($caracts === "error" || !$caracts) {
    echo json_encode([]);
    exit;
}

$result = [];
foreach ($caracts as $c) {
    // Campos que devuelve el modelo según el SQL del modelo
    $id    = $c['idCaracteristica']  ?? null;
    $valor = $c['valor']             ?? '';

    if ($id === null) continue;

    $result[] = [
        'idCaracteristica' => (int)$id,
        'valor'            => (string)$valor
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);