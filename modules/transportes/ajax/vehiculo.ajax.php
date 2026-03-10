<?php
ini_set('display_errors', 0);


require_once __DIR__ . "/../controllers/VehiculoController.php";
require_once __DIR__ . "/../models/VehiculoModel.php";

class AjaxVehiculo
{

    public function ajaxCrearVehiculo()
    {
        $respuesta = ControladorVehiculo::ctrCrearVehiculo();
        echo $respuesta; // ya devuelve JSON
    }



    public function ajaxConsultarConductoresDisponibles()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : null;

        $respuesta = ControladorVehiculo::ctrMostrarConductoresDisponibles(null, null);

        $data = [];
        foreach ($respuesta as $row) {

            // 🔧 Normalizar a UTF-8 sin perder ñ/acentos
            $trabajador = $row["trabajador"];
            $gerencia = $row["gerencia"];
            $fotocheck = $row["fotocheck"];

            if (!mb_check_encoding($trabajador, 'UTF-8')) {
                $trabajador = iconv('ISO-8859-1', 'UTF-8//IGNORE', $trabajador);
            }
            if (!mb_check_encoding($gerencia, 'UTF-8')) {
                $gerencia = iconv('ISO-8859-1', 'UTF-8//IGNORE', $gerencia);
            }
            if (!mb_check_encoding($fotocheck, 'UTF-8')) {
                $fotocheck = iconv('ISO-8859-1', 'UTF-8//IGNORE', $fotocheck);
            }


            if (!$q || stripos($trabajador, $q) !== false) {
                $data[] = [
                    "id" => $row["Id_Trabajador"],
                    "text" => $trabajador, // Nombre completo
                    "gerencia" => $gerencia,   // Sub Gerencia / Gerencia
                    "foto" => "../fotos-trabajador/" . $fotocheck. ".jpg"
                ];

            }
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) {
            echo json_encode([
                "error" => "json_encode falló",
                "msg" => json_last_error_msg()
            ]);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo $json;
        }

        exit;
    }





    // Nuevo método para consultar la placa asignada
    public function ajaxConsultarPlacaAsignada()
    {
        $log = [];
        session_start();
        if (!empty($_SESSION["id_Trabajador"])) {
            $valor = $_SESSION["id_Trabajador"];
            $log[] = "👉 id_Trabajador: $valor";

            $respuesta = ControladorVehiculo::ctrConsultarPlacaAsignada($valor);
            $log[] = "👉 Respuesta del controlador: " . print_r($respuesta, true);

            echo $respuesta;
        } else {
            $log[] = "⚠️ No existe Id_Trabajador en la sesión";
            echo json_encode([
                "success" => false,
                "data" => null,
                "log" => $log
            ]);
        }
    }


}

if (isset($_POST["accion"]) && $_POST["accion"] === "crearVehiculo") {
    $vehiculo = new AjaxVehiculo();
    $vehiculo->ajaxCrearVehiculo();
}


// if (isset($_POST["id_estado_vehiculo"])) {
//     $crear = new AjaxVehiculo();
//     $crear->ajaxCrearVehiculo();
// }

if (isset($_POST["accion"]) && $_POST["accion"] === "getPlaca") {
    $consulta = new AjaxVehiculo();
    $consulta->ajaxConsultarPlacaAsignada();
}

if (isset($_GET["accion"]) && $_GET["accion"] === "getConductores") {
    $consulta = new AjaxVehiculo();
    $consulta->ajaxConsultarConductoresDisponibles();
}


if (isset($_POST["action"]) && $_POST["action"] === "anularVehiculo") {

    $id = $_POST["placa"];
    $respuesta = ControladorVehiculo::ctrAnularVehiculo($id);

    if ($respuesta) {
        echo json_encode([
            "status" => "ok",
            "message" => "El vehiculo fue marcado como ANULADO."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No se pudo anular el vehiculo."
        ]);
        exit;
    }

}



if (isset($_POST['accion']) && $_POST['accion'] === 'asignarConductor') {
    $placa = $_POST['placa'];
    $idConductor = $_POST['idConductor'];

    $resultado = ControladorVehiculo::ctrAsignarConductor($placa, (int) $idConductor);

    if ($resultado === 'ok') {
        echo json_encode(['status' => 'success', 'message' => 'Conductor asignado correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al asignar el conductor: ' . $resultado]);
    }
}

if (isset($_POST['accion']) && $_POST['accion'] === 'desasignarVehiculo') {
    $placa = $_POST["placa"];
    $respuesta = ControladorVehiculo::ctrDesasignarConductor($placa);
    echo json_encode($respuesta);
}

if (isset($_POST['accion']) && $_POST['accion'] === 'obtenerColores') {
    $vehiculo = new AjaxVehiculo();
    $colores = ControladorVehiculo::ctrMostrarColorVehiculo();
    echo json_encode($colores);
}
