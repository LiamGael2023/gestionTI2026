<?php
session_start();
require_once __DIR__ . "/../models/ActivosModel.php";
require_once __DIR__ . "/../controllers/ActivosController.php";

class AjaxActivos {
    public function ajaxCrearActivo() {
        $respuesta = ActivosController::ctrCrearActivo();
        echo json_encode($respuesta);
    }
}

if (isset($_POST["nuevaDescripcion"])) {
    $crear = new AjaxActivos();
    $crear->ajaxCrearActivo();
}