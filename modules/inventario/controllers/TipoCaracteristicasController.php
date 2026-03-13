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

            // Llamada al modelo para ejecutar el SP sp_InsertarTipoCaracteristica
            $tabla = "inventario.TipoCaracteristica";
            $respuesta = TipoCaracteristicasModel::mdlCrearTipoCaracteristica($tabla, $datos);

            return $respuesta;
        }
    }
    /*=============================================
    EDITAR TIPO CARACTERISTICAS
    =============================================*/
    static public function ctrEditarTipoCaracteristica()
    {
        if (!isset($_POST["editarDescripcion"]) || !isset($_POST["editarIdTipoCaracteristica"])) {
            return ["status" => "error", "message" => "Parámetros faltantes"];
        }

        $id = (int) $_POST["editarIdTipoCaracteristica"];
        if ($id <= 0) {
            return ["status" => "error", "message" => "ID inválido"];
        }

        // Asegúrate de que la sesión tenga el id del usuario
        if (!isset($_SESSION["usuario_id"]) || empty($_SESSION["usuario_id"])) {
            return ["status" => "error", "message" => "Sesión inválida o usuario no autenticado"];
        }
        $idUsuario = (int) $_SESSION["usuario_id"];

        $descripcion = trim($_POST["editarDescripcion"]);
        if ($descripcion === "") {
            return ["status" => "error", "message" => "Descripción vacía"];
        }

        $datos = [
            "idTipoCaracteristicas" => $id,
            "descripcion"           => $descripcion,
            "usuario"               => $idUsuario
        ];

        $tabla = "inventario.TipoCaracteristica";
        $respuesta = TipoCaracteristicasModel::mdlEditarTipoCaracteristica($tabla, $datos);

        // Si el modelo devuelve un array con error, propágalo
        if (is_array($respuesta)) return $respuesta;

        return ["status" => "ok", "message" => $respuesta];
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
