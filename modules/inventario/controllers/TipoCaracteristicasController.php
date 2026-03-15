<?php
require_once __DIR__ . "/../models/TipoCaracteristicasModel.php";

class TipoCaracteristicasController
{

    /*=============================================
    AGREGAR TIPO CARACTERISTICAS
    =============================================*/
    static public function ctrCrearTipoCaracteristica()
    {

        if (isset($_POST["nuevaDescripcion"])) {

            $idUsuario = $_SESSION["usuario_id"];

            $datos = array(
                "descripcion"       => $_POST["nuevaDescripcion"],
                "idUsuarioRegistro" => $idUsuario
            );

            // Llamada al modelo para ejecutar el SP sp_CrearTipoCaracteristica
            $tabla = "inventario.tipoCaracteristica";
            $respuesta = TipoCaracteristicasModel::mdlCrearTipoCaracteristica($tabla, $datos);

            return $respuesta;
        }
    }
    /*=============================================
    EDITAR TIPO CARACTERISTICAS
    =============================================*/
    static public function ctrEditarTipoCaracteristica() {
    if (isset($_POST["editarDescripcion"])) {
        $datos = [
            "idTipoCaracteristicas" => $_POST["editarIdTipoCaracteristica"],
            "descripcion" => trim($_POST["editarDescripcion"]),
            "usuario" => $_SESSION["usuario_id"]
        ];
        $tabla = "inventario.TipoCaracteristica";
        $respuesta = TipoCaracteristicasModel::mdlEditarTipoCaracteristica($tabla, $datos);
        
        // Empaquetar SIEMPRE como array
        return $respuesta; 
    }
}


    /*=============================================
    MOSTRAR TipoCaracteristicaS
    =============================================*/
    static public function ctrMostrarTipoCaracteristicas($item, $valor)
    {

        $tabla = "inventario.TipoCaracteristica";

        $respuesta = TipoCaracteristicasModel::mdlMostrarTipoCaracteristicas($tabla, $item, $valor);

        return $respuesta;
    }
}
