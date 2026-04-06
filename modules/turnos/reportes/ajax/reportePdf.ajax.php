<?php

require_once "../controllers/reportePdfController.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["error" => "No se recibió data"]);
    exit;
}

try {
    ReporteControllerPDF::ctrGenerarPDF(  
        $data["trabajadores"],
        $data["turnos"],
        (int) $data["mes"],
        (int) $data["anio"],
        $data["estructuras"] ?? []
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