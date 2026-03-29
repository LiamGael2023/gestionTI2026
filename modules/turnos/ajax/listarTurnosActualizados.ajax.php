<?php

require_once "../../controllers/trabajadorController.php";

$anio = $_POST["anio"];

$trabajadores = TrabajadorController::ctrMostrarTrabajadoresFiltro($anio, '', '', '');

$data = [];

foreach ($trabajadores as $t){

    $turnos = TrabajadorController::ctrListarTurnosTrabajador($t["Id_Trabajador"], $anio);

    $data[] = [
        "id" => $t["Id_Trabajador"],
        "turnos" => $turnos
    ];
}

echo json_encode($data);