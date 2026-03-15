<?php
require_once __DIR__ . "/../models/CaracteristicasModel.php";

class CaracteristicasController
{
    /*=============================================
    AGREGAR CARACTERISTICA
    =============================================*/
    static public function ctrCrearCaracteristica()
    {
        // Espera: nuevoValor y idTipoCaracteristica (puede venir como nuevoIdTipoCaracteristica o idTipoCaracteristica)
        if (isset($_POST["nuevoValor"])) {

            $idUsuario = $_SESSION["usuario_id"] ?? 0;

            // Resolver idTipoCaracteristica desde distintos nombres posibles en el formulario
            $idTipo = null;
            if (isset($_POST["nuevoIdTipoCaracteristica"])) {
                $idTipo = (int) $_POST["nuevoIdTipoCaracteristica"];
            } elseif (isset($_POST["idTipoCaracteristica"])) {
                $idTipo = (int) $_POST["idTipoCaracteristica"];
            } elseif (isset($_POST["selectTipoCaracteristica"])) {
                $idTipo = (int) $_POST["selectTipoCaracteristica"];
            }

            // Validaciones mínimas
            if (empty($idTipo) || empty(trim($_POST["nuevoValor"]))) {
                return ["status" => "error", "message" => "Faltan datos obligatorios"];
            }

            $datos = array(
                "idTipoCaracteristica" => $idTipo,
                "valor"                => trim($_POST["nuevoValor"]),
                "idUsuarioCreacion"    => $idUsuario
            );

            $tabla = "inventario.caracteristicas";
            $respuesta = CaracteristicasModel::mdlCrearCaracteristica($tabla, $datos);

            return $respuesta;
        }
    }

    /*=============================================
    EDITAR CARACTERISTICA
    =============================================*/
    static public function ctrEditarCaracteristica()
    {
        // Espera: editarIdCaracteristica y editarValor (opcional editarIdTipoCaracteristica)
        if (isset($_POST["editarIdCaracteristica"]) && isset($_POST["editarValor"])) {

            $idUsuario = $_SESSION["usuario_id"] ?? 0;

            $idCaracteristica = (int) $_POST["editarIdCaracteristica"];

            // Resolver idTipoCaracteristica si viene en el formulario de edición
            $idTipo = null;
            if (isset($_POST["editarIdTipoCaracteristica"])) {
                $idTipo = (int) $_POST["editarIdTipoCaracteristica"];
            } elseif (isset($_POST["editarSelectTipo"])) {
                $idTipo = (int) $_POST["editarSelectTipo"];
            }

            $datos = [
                "idCaracteristica"    => $idCaracteristica,
                // Si no se envía idTipo, el modelo/SP debería mantener el valor actual o manejarlo según su lógica
                "idTipoCaracteristica"=> $idTipo,
                "valor"               => trim($_POST["editarValor"]),
                "idUsuarioModifica"   => $idUsuario
            ];

            $tabla = "inventario.caracteristicas";
            $respuesta = CaracteristicasModel::mdlEditarCaracteristica($tabla, $datos);

            return $respuesta;
        }
    }

    /*=============================================
    MOSTRAR CARACTERISTICAS
    =============================================*/
    static public function ctrMostrarCaracteristicas($item, $valor)
    {
        $tabla = "inventario.caracteristicas";
        $respuesta = CaracteristicasModel::mdlMostrarCaracteristicas($tabla, $item, $valor);
        return $respuesta;
    }
}
