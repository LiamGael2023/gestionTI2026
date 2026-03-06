<?php
require_once "../controllers/ActivosController.php";
require_once "../models/InventarioModel.php";

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