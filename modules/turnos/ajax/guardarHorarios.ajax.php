<?php

require_once 'modules/turnos/models/horarioTrabajadorModelo.php';
require_once 'modules/turnos/models/horarioTrabajador.php';
require_once 'modules/turnos/controllers/horarioTrabajadorController.php';

if(isset($_POST["datos"])){

    $datos = json_decode($_POST["datos"], true);

    foreach($datos as $item){

        HorarioTrabajadorModelo::mdlGuardarHorario($item);

    }

    echo "ok";
}