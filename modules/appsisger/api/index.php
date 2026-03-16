<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../controllers/UsuarioController.php";
require_once __DIR__ . "/../controllers/PeriodoController.php";
require_once __DIR__ . "/../controllers/OrdenRiegoController.php";

$accion = $_POST["accion"] ?? $_GET["accion"] ?? "";


switch($accion){

    case "usuario":
        UsuarioController::obtener();
        break;
    case "periodo":
        PeriodoController::obtener();
        break;
     case "ordenRiego":
        OrdenRiegoController::obtener();
        break;
    default:
        echo json_encode([
            "success"=>false,
            "mensaje"=>"Accion no valida"
        ]);
}
