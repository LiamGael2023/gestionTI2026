<?php

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/PeriodoModel.php";

class PeriodoController{

    public static function obtener(){

       
        $idAmbito = $_POST["Id_AmbitoOrganizacionUsuarios"] 
                    ?? $_GET["Id_AmbitoOrganizacionUsuarios"] 
                    ?? null;

        $codigo = $_POST["AmbOpe_CodigoCatastral"] 
                  ?? $_GET["AmbOpe_CodigoCatastral"] 
                  ?? null;

        if(!$idAmbito || !$codigo){

            echo json_encode([
                "success"=>false,
                "mensaje"=>"Faltan parametros"
            ]);

            return;
        }

        $data = PeriodoModel::obtenerPeriodo($idAmbito,$codigo);

        echo json_encode([
            "success"=>true,
            "data"=>$data
        ]);
    }
}
