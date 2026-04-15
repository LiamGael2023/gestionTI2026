<?php

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/DispositivoModel.php";

class DispositivoController {

    public static function guardar(){

        header('Content-Type: application/json');

        $codigoUnico = $_POST["CodigoUnico"] ?? null;
        $token = $_POST["token"] ?? null;

        if(!$codigoUnico || !$token){
            echo json_encode([
                "success"=>false,
                "mensaje"=>"Faltan datos"
            ]);
            return;
        }

        $ok = DispositivoModel::guardarToken($codigoUnico, $token);

        if($ok === false){
            echo json_encode([
                "success"=>false,
                "error"=> sqlsrv_errors()
            ]);
        }else{
            echo json_encode([
                "success"=>true
            ]);
        }
    }
}