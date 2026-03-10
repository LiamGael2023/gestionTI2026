<?php
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/VehiculoModel.php";
$model = new ModeloVehiculo($conn);
$action = $_GET['action'] ?? 'index';




// switch ($action) {
//     case 'guardar':
//         // Lógica de guardado
//         break;
//     default:
//         include 'modules/transportes/views/index.php';
//         break;
// }
$conn  = Conexion::conectar();
$model = new ModeloVehiculo($conn);




class ControladorVehiculo
{


    static public function ctrCrearVehiculo()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["id_tipo_vehiculo"])) {

            $cerrar = (isset($_POST["Cerrar"]) && ($_POST["Cerrar"] == '1' || $_POST["Cerrar"] === true)) ? 1 : 0;
            $estado_vehiculo = isset($_POST['id_estado_vehiculo']) ? $_POST['id_estado_vehiculo'] : null;
            $marca_vehiculo = isset($_POST['marca']) ? $_POST['marca'] : null;

            $datos = array(
                "codigo_patrimonial" => $_POST["codigo_patrimonial"],
                "placa" => $_POST["placa"],
                "id_tipo_vehiculo" => $_POST["id_tipo_vehiculo"],
                "id_estado_vehiculo" => $estado_vehiculo,
                "numero_chasis" => $_POST["numero_chasis"],
                "marca" => $marca_vehiculo,
                "modelo" => $_POST["modelo"],
                "color" => $_POST["color"],
                "anioFabricacion" => $_POST["anioFabricacion"]
            );


            $respuesta = ModeloVehiculo::mdlCrearVehiculo($datos);

            if ($respuesta["status"] === "success") {
                echo json_encode($respuesta);
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => $respuesta["message"]
                ));
            }
        }
    }
    static public function ctrMostrarVehiculo($item, $valor)
    {

        $tabla = "Transportes.tbl_Vehiculo";

        $respuesta = ModeloVehiculo::mdlMostrarVehiculos($tabla, $item, $valor);

        return $respuesta;
    }
    static public function ctrMostrarVehiculoReporte($item, $valor)
    {

        $tabla = "Transportes.tbl_vehiculo";

        $respuesta = ModeloVehiculo::MdlMostrarVehiculoReporte(

            $item,
            $valor
        );

        return $respuesta;
    }

    static public function ctrMostrarVehiculoReporteHistorial($item, $valor)
    {

        $tabla = "Transportes.tbl_asignacion_vehiculo";

        $respuesta = ModeloVehiculo::MdlMostrarVehiculoReporteHistorial(

            $item,
            $valor
        );

        return $respuesta;
    }

    static public function ctrMostrarVehiculoReporteHistorialPapeleta($fk_vehiculo)
    {


        $respuesta = ModeloVehiculo::MdlMostrarVehiculoReporteHistorialPapeleta(

            $fk_vehiculo
        );

        return $respuesta;
    }

    static public function ctrMostrarReporteVehiculos($item, $valor)
    {

        $tabla = "Transportes.tbl_Vehiculo";

        $respuesta = ModeloVehiculo::mdlMostrarReporteVehiculos($tabla, $item, $valor);

        return $respuesta;
    }

    static public function ctrMostrarTipoVehiculo($item, $valor)
    {

        $tabla = "Transportes.tbl_tipo_vehiculo";

        $respuesta = ModeloVehiculo::MdlMostrarTipoVehiculo($tabla, $item, $valor);

        return $respuesta;
    }

    static public function ctrMostrarEstadoVehiculo($item, $valor)
    {

        $tabla = "Transportes.tbl_estado_vehiculo";

        $respuesta = ModeloVehiculo::MdlMostrarEstadoVehiculo($tabla, $item, $valor);

        return $respuesta;
    }

    static public function ctrMostrarMarcaVehiculo($item, $valor)
    {

        $tabla = "Transportes.tbl_marca_vehiculo";

        $respuesta = ModeloVehiculo::MdlMostrarMarcaVehiculo($tabla, $item, $valor);

        return $respuesta;
    }


    static public function ctrMostrarColorVehiculo()
    {



        $respuesta = ModeloVehiculo::MdlMostrarColorVehiculo();

        return $respuesta;
    }

    static public function ctrMostrarConductoresDisponibles($item, $valor)
    {

        $tabla = "Transportes.tbl_estado_vehiculo";
        $respuesta = ModeloVehiculo::mdlMostrarConductoresDisponibles($tabla, $item);
        return $respuesta;
    }

    public static function ctrConsultarPlacaAsignada($id_trabajador)
    {
        $vehiculo = ModeloVehiculo::mdlBuscarPlaca($id_trabajador);
        return $vehiculo["placa"] ?? null; // Devuelve la placa como texto
    }


    static public function ctrAsignarConductor($idVehiculo, $idConductor)
    {
        return ModeloVehiculo::mdlAsignarConductor($idVehiculo, $idConductor);
    }
    static public function ctrDesasignarConductor($placa)
    {
        return ModeloVehiculo::mdlDesasignarConductor($placa);
    }

    static public function ctrAnularVehiculo($id)
    {
        return ModeloVehiculo::mdlAnularVehiculo("Transportes.Tbl_asignacion_vehicular", $id);
    }
}
