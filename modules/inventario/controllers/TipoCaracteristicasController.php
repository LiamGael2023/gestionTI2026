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
                // Guardar siempre en MAYÚSCULAS y sin espacios extra
                "descripcion"       => mb_strtoupper(trim($_POST["nuevaDescripcion"]), "UTF-8"),
                "idUsuarioRegistro" => $idUsuario
            );

            $tabla     = "inventario.tipoCaracteristica";
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

            if (empty($_POST["editarIdTipoCaracteristica"])) {
                return "error";
            }

            $datos = array(
                "idTipoCaracteristicas" => $_POST["editarIdTipoCaracteristica"],
                // Guardar siempre en MAYÚSCULAS y sin espacios extra
                "descripcion"           => mb_strtoupper(trim($_POST["editarDescripcion"]), "UTF-8"),
                "usuario"               => $_SESSION["usuario_id"]
            );

            $tabla     = "inventario.TipoCaracteristica";
            $respuesta = TipoCaracteristicasModel::mdlEditarTipoCaracteristica($tabla, $datos);

            return $respuesta;
        }
    }

    /*=============================================
    MOSTRAR TIPO CARACTERISTICAS
    =============================================*/
    static public function ctrMostrarTipoCaracteristicas($item, $valor)
    {
        $tabla     = "inventario.TipoCaracteristica";
        $respuesta = TipoCaracteristicasModel::mdlMostrarTipoCaracteristicas($tabla, $item, $valor);
        return $respuesta;
    }

    /*=============================================
    ELIMINAR TIPO CARACTERISTICA (lógico)
    =============================================*/
    static public function ctrEliminarTipoCaracteristica()
    {
        if (isset($_POST["eliminarIdTipoCaracteristica"])) {

            if (empty($_POST["eliminarIdTipoCaracteristica"])) {
                return ["resultado" => "error", "mensaje" => "ID no recibido."];
            }

            $datos = array(
                "idTipoCaracteristica" => intval($_POST["eliminarIdTipoCaracteristica"]),
                "idUsuarioModifica"    => intval($_SESSION["usuario_id"])
            );

            $respuesta = TipoCaracteristicasModel::mdlEliminarTipoCaracteristica($datos);
            return $respuesta;
        }
    }
}
