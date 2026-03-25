<?php

require_once 'modules/turnos/models/horario.php';
require_once 'modules/turnos/controllers/horarioController.php';


class AjaxHorarios{

    public function ajaxListarHorarios(){

        $respuesta = HorarioController::ctrListarHorarios();

        echo json_encode($respuesta);

    }

}



if(isset($_GET["action"]) && $_GET["action"] == "listarHorarios"){

    $horarios = new AjaxHorarios();
    $horarios->ajaxListarHorarios();

}