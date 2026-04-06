<?php


// require_once 'modules/turnos/models/trabajador.php';

require_once __DIR__ .'/../models/trabajador.php';
require_once __DIR__ . "/../../../config/db.php"; 


$conn  = Conexion::conectar();
$model = new Trabajador($conn);


class TrabajadorController {


   static public function ctrMostrarTrabajadoresFiltro($anio, $componente, $meta,$tipotrabajador){

    $tabla = "Escalafon.Tbl_Trabajador";

    $respuesta = Trabajador::mdlMostrarTrabajadoresFiltro($anio, $componente, $meta,$tipotrabajador);

    return $respuesta;

}
    static public function ctrMostrarComponentes(){
 $tabla = "Asistencia.Tbl_Componente";
        $respuesta = Trabajador::mdlMostrarComponente();

        return $respuesta;
    }
static public function ctrMostrarMetas($anio, $componente){
 $tabla = "Asistencia.Tbl_Meta";
    $respuesta = Trabajador::mdlMostrarMetas($anio, $componente);

    return $respuesta;
}

  static public function ctrMostrarTipoTrabajador(){
 $tabla = "Escalafon.Tbl_Trabajador_tipo";
        $respuesta = Trabajador::mdlMostrarTipoTrabajador();

        return $respuesta;
    }

      static public function ctrMostrarTurnoTrabajador(){
 $tabla = "Asistencia.Tbl_Marcacion_Tipo";
        $respuesta = Trabajador::mdlMostrarTurnoTrabajador();

        return $respuesta;
    }
  

static public function ctrListarTurnosTrabajador($id_trabajador, $anio){
    $respuesta = Trabajador::mdlMostrarTurnosTrabajador($id_trabajador, $anio);
    return $respuesta;
}

static public function ctrListarTurnosTrabajadoresModal($id_anio, $id_mes,$trabajadores){
    $respuesta = Trabajador::mdlMostrarTurnosTrabajadoresModal($id_anio, $id_mes,$trabajadores);
    return $respuesta;
}

public static function ctrActualizarDescripcion($trabajador, $fecha, $descripcion){
    return TrabajadorModel::mdlActualizarDescripcion($trabajador, $fecha, $descripcion);
}

 static public function ctrGuardarTrabajadorTurno(){

        if(isset($_POST["datos"])){

            $lista = json_decode($_POST["datos"], true);

            foreach($lista as $fila){


                $datos = array(                      
                    "trabajador" => $fila["trabajador"],
                    "componente" => $fila["componente"],
                    "meta" => $fila["meta"],
                    "trabajadortipo" => $fila["trabajadortipo"],
                    "anio" => $fila["anio"],           
                );

                $respuesta = Trabajador::mdlGuardarTrabajadorTurno($datos);

            }

            echo json_encode($respuesta);

        }

    }

    
static public function ctrMostrarTrabajadorSeleccionados($componente, $meta,  $anio){
    $componente = !empty($componente) ? $componente : 0;
    $meta = !empty($meta) ? $meta : 0;
    $anio = !empty($anio) ? $anio : date("Y");

    $trabajadores = Trabajador::mdlMostrarTrabajadorSeleccionados($componente, $meta,  $anio);

    $ids = [];
    foreach($trabajadores as $t){
        $ids[] = $t['Id_Trabajador'];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($ids);
    exit; 
}
static public function ctrEliminarTurnoTrabajador($componente,$meta,$anio,$id_trabajador){
    return Trabajador::mdlEliminarTurnoTrabajador($componente,$meta,$anio,$id_trabajador);
}


}

if(isset($_POST['accion']) && $_POST['accion'] == "traerSeleccionados"){

    $componente = $_POST['componente'] ?? 0;
    $meta = $_POST['meta'] ?? 0;
    $tipotrabajador = $_POST['tipotrabajador'] ?? 0;
    $anio = $_POST['anio'] ?? date("Y");

    TrabajadorController::ctrMostrarTrabajadorSeleccionados($componente, $meta, $tipotrabajador, $anio);
}


if(isset($_POST['accion']) && $_POST['accion'] == "eliminarTurno"){

    $componente = $_POST['componente'];
    $meta = $_POST['meta'];
    $anio = $_POST['anio'];
    $trabajador = $_POST['trabajador'];

    $respuesta = TrabajadorController::ctrEliminarTurnoTrabajador(
        $componente,
        $meta,
        $anio,
        $trabajador
    );

    echo json_encode($respuesta);
    exit;
}

