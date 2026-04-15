<?php

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/NotificacionModel.php";
require_once __DIR__ . "/../services/NotificacionService.php";
require_once __DIR__ . "/../services/FCMService.php";

class NotificacionController {

    public function procesar() {

        header('Content-Type: application/json');

        $loteId = $_POST['loteId'] ?? null;

        if (!$loteId) {
            echo json_encode([
                "status" => "error",
                "message" => "loteId requerido"
            ]);
            return;
        }

        $service = new NotificacionService();
        $service->procesarLote($loteId);

        echo json_encode(["status" => "ok"]);
    }
}