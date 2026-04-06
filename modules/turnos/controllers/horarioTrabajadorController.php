<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../../config/db.php"; 
require_once __DIR__ .'/../models/horarioTrabajador.php';


$conn  = Conexion::conectar();
$model = new HorarioTrabajadorModel($conn);

class HorarioTrabajadorController{

   static public function ctrGuardarTurnoTrabajador(){

    if(!isset($_POST["datos"])){
        echo json_encode(["error" => "No llegó datos"]);
        return;
    }
    $lista = json_decode($_POST["datos"], true);

    if(!$lista){
        echo json_encode(["error" => "JSON inválido"]);
        return;
    }
    $conn = Conexion::conectar();

    $respuestas = [];

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

        $respuestas[] = HorarioTrabajadorModel::mdlGuardarTurnoTrabajador($conn, $datos);
    }

    sqlsrv_close($conn);

    echo json_encode([
        "status" => "ok",
        "data" => $respuestas
    ]);
}

    static public function ctrGuardarTrabajador(){
     

        if(isset($_POST["datos"])){

            $lista = json_decode($_POST["datos"], true);

            foreach($lista as $fila){

                

                 $datos = array(
                    "id" => $fila["id"],
                    "componente" => $fila["componente"],
                    "meta" => $fila["meta"],
                    "tipotrabajador" => $fila["tipotrabajador"],
                    "anio" => $fila["anio"],

                );

                $respuesta = HorarioTrabajadorModel::mdlGuardarTrabajador($datos);

            }

            echo json_encode($respuesta);

        }
      }


}