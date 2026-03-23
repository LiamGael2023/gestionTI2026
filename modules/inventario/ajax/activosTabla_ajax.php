<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../controllers/ActivosController.php";

header('Content-Type: application/json; charset=utf-8');

$activos = ActivosController::ctrMostrarActivos(null, null);

if ($activos === "error" || !$activos) {
    echo json_encode([]);
    exit;
}

$result = [];
foreach ($activos as $a) {
    $id           = $a['idActivos']    ?? null;
    $desc         = $a['descripcion']  ?? '';
    $icono        = $a['icono']        ?? 'ti-package';
    $compuesto    = $a['compuesto']    ?? 0;
    $esPeriferico = $a['esPeriferico'] ?? 0;

    if ($id === null) continue;

    $result[] = [
        'idActivos'    => (int)$id,
        'descripcion'  => (string)$desc,
        'icono'        => (string)$icono,
        'compuesto'    => (int)$compuesto,
        'esPeriferico' => (int)$esPeriferico,
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
