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
        if (isset($_POST["editarDescripcion"])) {

            // Validamos que el ID no llegue vacío
            if (empty($_POST["editarIdTipoCaracteristica"])) {
                return "error";
            }

            // Obtenemos el nombre de usuario de la sesión
            $idUsuario = $_SESSION["usuario_id"];

            $datos = array(
                "idTipoCaracteristicas"   => $_POST["editarIdTipoCaracteristica"],
                "descripcion" => $_POST["editarDescripcion"],
                "usuario"     => $idUsuario
            );

            $tabla = "inventario.TipoCaracteristica";
            $respuesta = TipoCaracteristicasModel::mdlEditarTipoCaracteristica($tabla, $datos);

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
