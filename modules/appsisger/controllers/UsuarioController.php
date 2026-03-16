<?php

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/UsuarioModel.php";

class UsuarioController{

    public static function obtener(){

    $codigo = $_POST["codigo"] ?? $_GET["codigo"] ?? null;

    if(!$codigo){
        echo json_encode([
            "success"=>false,
            "mensaje"=>"Falta el parametro codigo"
        ]);
        return;
    }

    $data = UsuarioModel::obtenerUsuario($codigo);

     if(empty($data)){
            echo json_encode([
                "success"=>false,
                "mensaje"=>"Usuario no encontrado"
            ]);
            return;
        }

   
        echo json_encode([
            "success"=>true,
            "data"=>$data
        ]);
    }

}