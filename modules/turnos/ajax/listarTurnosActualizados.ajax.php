<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
require_once "../controllers/trabajadorController.php";

$id_anio = $_POST["anio"];
$id_mes = $_POST["mes"];
$trabajadores = $_POST["trabajadores"] ?? [];

$respuesta = TrabajadorController::ctrListarTurnosTrabajadoresModal($id_anio, $id_mes, $trabajadores  );

echo json_encode($respuesta);