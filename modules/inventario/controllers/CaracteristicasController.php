<?php
require_once __DIR__ . "/../models/CaracteristicasModel.php";

class CaracteristicasController
{
    /*=============================================
    AGREGAR CARACTERISTICA
    =============================================*/
    static public function ctrCrearCaracteristica()
    {
        if (isset($_POST["nuevoValor"])) {

            $idUsuario = $_SESSION["usuario_id"] ?? 0;

            $idTipo = null;
            if (!empty($_POST["nuevoIdTipoCaracteristica"])) {
                $idTipo = (int)$_POST["nuevoIdTipoCaracteristica"];
            } elseif (!empty($_POST["idTipoCaracteristica"])) {
                $idTipo = (int)$_POST["idTipoCaracteristica"];
            } elseif (!empty($_POST["selectTipoCaracteristica"])) {
                $idTipo = (int)$_POST["selectTipoCaracteristica"];
            }

            if (empty($idTipo) || empty(trim($_POST["nuevoValor"]))) {
                return ["resultado" => "error", "mensaje" => "Faltan datos obligatorios."];
            }

            $datos = array(
                "idTipoCaracteristica" => $idTipo,
                // Guardar siempre en MAYÚSCULAS
                "valor"                => mb_strtoupper(trim($_POST["nuevoValor"]), "UTF-8"),
                "idUsuarioCreacion"    => $idUsuario
            );

            $tabla     = "inventario.caracteristicas";
            $respuesta = CaracteristicasModel::mdlCrearCaracteristica($tabla, $datos);

            return $respuesta;
        }
    }

    /*=============================================
    EDITAR CARACTERISTICA
    =============================================*/
    static public function ctrEditarCaracteristica()
    {
        if (isset($_POST["editarIdCaracteristica"]) && isset($_POST["editarValor"])) {

            $idUsuario        = $_SESSION["usuario_id"] ?? 0;
            $idCaracteristica = (int)$_POST["editarIdCaracteristica"];

            $idTipo = null;
            if (!empty($_POST["editarIdTipoCaracteristica"])) {
                $idTipo = (int)$_POST["editarIdTipoCaracteristica"];
            } elseif (!empty($_POST["editarSelectTipo"])) {
                $idTipo = (int)$_POST["editarSelectTipo"];
            }

            $datos = array(
                "idCaracteristica"     => $idCaracteristica,
                "idTipoCaracteristica" => $idTipo,
                // Guardar siempre en MAYÚSCULAS
                "valor"                => mb_strtoupper(trim($_POST["editarValor"]), "UTF-8"),
                "idUsuarioModifica"    => $idUsuario
            );

            $tabla     = "inventario.caracteristicas";
            $respuesta = CaracteristicasModel::mdlEditarCaracteristica($tabla, $datos);

            return $respuesta;
        }
    }

    /*=============================================
    MOSTRAR CARACTERISTICAS
    =============================================*/
    static public function ctrMostrarCaracteristicas($item, $valor)
    {
        $tabla     = "inventario.caracteristicas";
        $respuesta = CaracteristicasModel::mdlMostrarCaracteristicas($tabla, $item, $valor);
        return $respuesta;
    }

    /*=============================================
    ELIMINAR CARACTERISTICA (lógico)
    =============================================*/
    static public function ctrEliminarCaracteristica()
    {
        if (!empty($_POST["eliminarIdCaracteristica"])) {

            $datos = array(
                "idCaracteristica"  => intval($_POST["eliminarIdCaracteristica"]),
                "idUsuarioModifica" => intval($_SESSION["usuario_id"] ?? 0)
            );

            return CaracteristicasModel::mdlEliminarCaracteristica($datos);
        }
        return ["resultado" => "error", "mensaje" => "ID no recibido."];
    }
}
