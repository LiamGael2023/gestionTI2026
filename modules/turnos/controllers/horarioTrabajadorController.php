<?php



require_once 'modules/turnos/models/horarioTrabajador.php';

class HorarioTrabajadorController{

    static public function ctrGuardarHorario(){
        var_dump($_POST);
die(); // corta la ejecución para ver solo los datos

        if(isset($_POST["datos"])){

            $lista = json_decode($_POST["datos"], true);

            foreach($lista as $fila){

                
                $fecha_inicio = date("Y-m-d", strtotime($fila["fechainicioturno"]));
                $fecha_fin = date("Y-m-d", strtotime($fila["fechafinturno"]));

                 $datos = array(
                    "anio" => $fila["anio"],
                    "mes" => $fila["mes"],                       
                    "trabajador" => $fila["trabajador"],
                    "componente" => $fila["componente"],
                    "meta" => $fila["meta"],
                    "horario" => $fila["horario"],
                    "fechainicioturno" => $fecha_inicio,       
                    "fechafinturno" => $fecha_fin,
                    "marcacionturno" => $fila["marcacionturno"],
                    "descripcion" => $fila["descripcion"] ?? ""

                );

                $respuesta = HorarioTrabajadorModel::mdlGuardarHorario($datos);

            }

            echo json_encode($respuesta);

        }

    }


}