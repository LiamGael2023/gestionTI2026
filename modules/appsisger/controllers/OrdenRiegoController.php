<?php

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/OrdenRiegoModel.php";

class OrdenRiegoController{

    public static function obtener(){

        $idAmbito = $_POST["Id_AmbitoOrganizacionUsuarios"]
                    ?? $_GET["Id_AmbitoOrganizacionUsuarios"]
                    ?? null;

        $anio = $_POST["Id_Anio"]
                ?? $_GET["Id_Anio"]
                ?? null;

        $uc = $_POST["UC"]
              ?? $_GET["UC"]
              ?? null;

        $periodo = $_POST["Periodo"]
                   ?? $_GET["Periodo"]
                   ?? null;

        if(!$idAmbito || !$anio || !$uc || !$periodo){

            echo json_encode([
                "success"=>false,
                "mensaje"=>"Faltan parametros"
            ]);

            return;
        }

        $data = OrdenRiegoModel::obtenerOrden($idAmbito,$anio,$uc,$periodo);

        echo json_encode([
            "success"=>true,
            "data"=>$data
        ]);
    }

}