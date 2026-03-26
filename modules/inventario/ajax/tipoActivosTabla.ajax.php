<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../controllers/TipoActivosController.php";

header('Content-Type: application/json; charset=utf-8');

$activos = TipoActivosController::ctrMostrarActivos(null, null);

if ($activos === "error" || !$activos) {
    echo json_encode([]);
    exit;
}

$result = [];
foreach ($activos as $a) {
    $id           = $a['idTipoActivo']  ?? null;
    $desc         = $a['descripcion']   ?? '';
    $icono        = $a['icono']         ?? 'ti-package';
    $esCompuesto  = $a['esCompuesto']   ?? 0;
    $esComponente = $a['esComponente']  ?? 0;
    $esPeriferico = $a['esPeriferico']  ?? 0;

    if ($id === null) continue;

    $result[] = [
        'idTipoActivo'  => (int)$id,
        'descripcion'   => (string)$desc,
        'icono'         => (string)$icono,
        'esCompuesto'   => (int)$esCompuesto,
        'esComponente'  => (int)$esComponente,
        'esPeriferico'  => (int)$esPeriferico,
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
