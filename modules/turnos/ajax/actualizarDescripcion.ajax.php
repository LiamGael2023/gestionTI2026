<?php

require_once "../controllers/trabajadorController.php";

$trabajador = $_POST["trabajador"];
$fecha = $_POST["fecha"];
$descripcion = $_POST["descripcion"];

$respuesta = TrabajadorController::ctrActualizarDescripcion($trabajador, $fecha, $descripcion);

echo json_encode($respuesta);