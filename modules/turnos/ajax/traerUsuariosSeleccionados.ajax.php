<?php

require_once "../controllers/trabajadorController.php";
require_once "../models/trabajador.php";

header('Content-Type: application/json');

if ($_POST["accion"] == "traerSeleccionados") {

    $data = TrabajadorController::mdlMostrarTrabajadorSeleccionados(
        $_POST["componente"],
        $_POST["meta"],
        $_POST["anio"]
    );

    echo json_encode($data);
    exit;
}