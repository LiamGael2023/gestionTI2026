<?php



require_once __DIR__ .'/../models/horarioTrabajador.php';
require_once __DIR__ .'/../controllers/horarioTrabajadorController.php';

if(isset($_POST["datos"])){

    

    $guardar = new HorarioTrabajadorController();
    $guardar -> ctrGuardarHorario();
}
