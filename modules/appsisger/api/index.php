<?php


require_once __DIR__ . "/../controllers/UsuarioController.php";
require_once __DIR__ . "/../controllers/PeriodoController.php";
require_once __DIR__ . "/../controllers/OrdenRiegoController.php";
require_once __DIR__ . "/../reports/orden_pdf.php";
require_once __DIR__ . "/../services/ordenRiegoService.php";

$accion = $_POST["accion"] ?? $_GET["accion"] ?? "";


switch($accion){

    case "usuario":
        header("Content-Type: application/json; charset=UTF-8");
        UsuarioController::obtener();
        break;

    case "periodo":
        header("Content-Type: application/json; charset=UTF-8");
        PeriodoController::obtener();
        break;

    case "ordenRiego":
        header("Content-Type: application/json; charset=UTF-8");
        OrdenRiegoController::obtener();
        break;

  case "pdfOrden":

    ob_clean();

    $idAmbito = $_GET["Id_AmbitoOrganizacionUsuarios"];
    $anio = $_GET["Id_Anio"];
    $codigo = $_GET["UC"];
    $periodo = $_GET["Periodo"];

    require_once __DIR__ . "/../models/OrdenRiegoModel.php";

    $resp = OrdenRiegoModel::obtenerOrden(
        $idAmbito,
        $anio,
        $codigo,
        $periodo
    );

    if(!$resp || count($resp) == 0){
        die("No hay datos");
    }

  
    $d = OrdenRiegoService::mapear($resp[0]);

    $pdf = generarPDFOrden($d);

    ob_end_clean();

    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=orden_riego.pdf");

    $pdf->Output("orden_riego.pdf", "I");

    exit;

break;

    default:
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success"=>false,
            "mensaje"=>"Accion no valida"
        ]);
}