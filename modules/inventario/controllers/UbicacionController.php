<?php
require_once __DIR__ . "/../models/UbicacionModel.php";

class UbicacionController
{
    static public function ctrCrearUbicacion()
    {
        if (!isset($_POST["nuevaDescripcionUbicacion"])) return null;

        $datos = [
            "descripcion"      => trim($_POST["nuevaDescripcionUbicacion"]),
            "idUbicacionPadre" => !empty($_POST["nuevoIdUbicacionPadre"])
                                    ? intval($_POST["nuevoIdUbicacionPadre"]) : null,
            "idUsuario"        => $_SESSION["usuario_id"],
        ];

        return UbicacionModel::mdlCrearUbicacion($datos);
    }

    static public function ctrEditarUbicacion()
    {
        if (!isset($_POST["editarDescripcionUbicacion"])) return null;

        if (empty($_POST["editarIdUbicacion"])) {
            return ["resultado" => "error", "mensaje" => "ID de ubicación no recibido."];
        }

        $datos = [
            "idUbicacion"      => intval($_POST["editarIdUbicacion"]),
            "descripcion"      => trim($_POST["editarDescripcionUbicacion"]),
            "idUbicacionPadre" => !empty($_POST["editarIdUbicacionPadre"])
                                    ? intval($_POST["editarIdUbicacionPadre"]) : null,
            "idUsuario"        => $_SESSION["usuario_id"],
        ];

        return UbicacionModel::mdlEditarUbicacion($datos);
    }

    static public function ctrMostrarUbicacion($item, $valor)
    {
        return UbicacionModel::mdlMostrarUbicacion($item, $valor);
    }
}
