<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'modules/turnos/models/horarioTrabajador.php';
require_once 'modules/turnos/controllers/horarioTrabajadorController.php';

if(isset($_POST["datos"])){

    $guardar = new HorarioTrabajadorController();
    $guardar -> ctrGuardarHorario();

}
