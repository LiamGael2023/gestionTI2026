<?php


require_once 'modules/turnos/models/horario.php';

class HorarioController
{

static public function ctrListarHorarios(){
 $tabla = "Asistencia. Tbl_Horario";
        $respuesta = Horario::mdlListarHorarios();

        return $respuesta;
    }


}