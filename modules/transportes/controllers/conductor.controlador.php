<?php

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/ColaboradorModel.php";
$model = new ModeloColaborador($conn);
$action = $_GET['action'] ?? 'index';

// switch ($action) {
//     case 'guardar':
//         // Lógica de guardado
//         break;
//     default:
//         break;
// }
$conn  = Conexion::conectar();
$model = new ModeloColaborador($conn);

class ControladorConductor
{

    static public function ctrMostrarConductor($fecha)
    {


        $respuesta = ModeloConductor::MdlMostrarConductor($fecha);

        return $respuesta;
    }

    static public function ctrMostrarConductorReporteHistorial($item, $valor)
    {

        $tabla = "Transportes.tbl_asignacion_vehiculo";

        $respuesta = ModeloConductor::MdlMostrarConductorReporteHistorial(

            $item,
            $valor
        );

        return $respuesta;
    }
}
