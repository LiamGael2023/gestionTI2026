<?php
ini_set('display_errors', 0);

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/PapeletaVehicularModel.php";
$model = new ModeloPapeletaVehicular($conn);
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        break;
}
$conn  = Conexion::conectar();
$model = new ModeloPapeletaVehicular($conn);

class ControladorPapeletaVehicular
{

    static public function ctrRegistrarBitacora($datos)
    {


        $respuesta = ModeloPapeletaVehicular::mdlRegistrarBitacora($datos);
        return $respuesta;
    }


    static public function ctrMostrarPapeletasVehiculares(
        $id_establecimiento,
        $start,
        $length,
        $search,
        $filtro,      // ← HOY, AYER, MES, ESTE AÑO...
        $firmas
    ) {

        $respuesta = ModeloPapeletaVehicular::MdlMostrarPapeletasVehiculares(
            $id_establecimiento,
            $start,
            $length,
            $search,
            $filtro,      // ← HOY, AYER, MES, ESTE AÑO...
            $firmas
        );

        return $respuesta;
    }

    static public function ctrMostrarSedeSalidaVehicular()
    {


        $respuesta = ModeloPapeletaVehicular::MdlMostrarSedesSalidaVehicular();

        return $respuesta;
    }

    static public function ctrMostrarPlacaVehicular()
    {


        $respuesta = ModeloPapeletaVehicular::MdlMostrarPlacaVehicular();

        return $respuesta;
    }
}
