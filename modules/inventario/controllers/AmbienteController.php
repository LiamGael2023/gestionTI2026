<?php
require_once __DIR__ . "/../models/AmbienteModel.php";

class AmbienteController
{
    static public function ctrCrearAmbiente()
    {
        if (!isset($_POST["nuevaDescripcionAmbiente"])) return null;

        if (empty($_POST["nuevoIdUbicacionAmbiente"])) {
            return ["resultado" => "error", "mensaje" => "Debe seleccionar una ubicación."];
        }

        $datos = [
            "descripcion" => trim($_POST["nuevaDescripcionAmbiente"]),
            "idUbicacion" => intval($_POST["nuevoIdUbicacionAmbiente"]),
            "idUsuario"   => $_SESSION["usuario_id"],
        ];

        return AmbienteModel::mdlCrearAmbiente($datos);
    }

    static public function ctrEditarAmbiente()
    {
        if (!isset($_POST["editarDescripcionAmbiente"])) return null;

        if (empty($_POST["editarIdAmbiente"])) {
            return ["resultado" => "error", "mensaje" => "ID de ambiente no recibido."];
        }

        $datos = [
            "idAmbiente"  => intval($_POST["editarIdAmbiente"]),
            "descripcion" => trim($_POST["editarDescripcionAmbiente"]),
            "idUbicacion" => intval($_POST["editarIdUbicacionAmbiente"]),
            "idUsuario"   => $_SESSION["usuario_id"],
        ];

        return AmbienteModel::mdlEditarAmbiente($datos);
    }

    static public function ctrMostrarAmbiente($item, $valor)
    {
        return AmbienteModel::mdlMostrarAmbiente($item, $valor);
    }
}
