<?php

require_once "../controllers/reporteController.php";

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    http_response_code(400);
    echo json_encode(["error" => "No se recibió data"]);
    exit;
}

try {
    ReporteController::ctrGenerarExcel(
        $data["trabajadores"],
        $data["turnos"],
        $data["mes"],
        $data["anio"]
    );
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        "error" => $e->getMessage(),
        "file"  => $e->getFile(),
        "line"  => $e->getLine()
    ]);
    exit;
}