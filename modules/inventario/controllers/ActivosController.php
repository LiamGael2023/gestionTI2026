<?php
require_once __DIR__ . "/../models/ActivosModel.php";

class ActivosController
{
    /*=============================================
    AGREGAR ACTIVOS
    =============================================*/
    static public function ctrCrearActivo()
    {
        if (!isset($_POST["nuevaDescripcion"])) return null;

        $idUsuario = $_SESSION["usuario_id"];

        $datos = array(
            "descripcion"       => mb_strtoupper(trim($_POST["nuevaDescripcion"]), "UTF-8"),
            "icono"             => $_POST["iconoActivo"]        ?? "",
            "compuesto"         => isset($_POST["nuevoCompuesto"])    ? 1 : 0,
            "esPeriferico"      => isset($_POST["nuevoEsPeriferico"]) ? 1 : 0,
            "idUsuarioRegistro" => $idUsuario
        );

        return ActivosModel::mdlCrearActivo("inventario.activos", $datos);
    }

    /*=============================================
    EDITAR ACTIVOS
    =============================================*/
    static public function ctrEditarActivo()
    {
        if (!isset($_POST["editarDescripcion"])) return null;

        if (empty($_POST["editarIdActivo"]))
            return ["resultado" => "error", "mensaje" => "ID no recibido."];

        $idUsuario = $_SESSION["usuario_id"];

        $datos = array(
            "idActivos"    => $_POST["editarIdActivo"],
            "descripcion"  => mb_strtoupper(trim($_POST["editarDescripcion"]), "UTF-8"),
            "compuesto"    => isset($_POST["editarCompuesto"])    ? 1 : 0,
            "esPeriferico" => isset($_POST["editarEsPeriferico"]) ? 1 : 0,
            "icono"        => $_POST["editarIconoActivo"] ?? "",
            "usuario"      => $idUsuario
        );

        return ActivosModel::mdlEditarActivo("inventario.activos", $datos);
    }

    /*=============================================
    MOSTRAR ACTIVOS
    =============================================*/
    static public function ctrMostrarActivos($item, $valor)
    {
        return ActivosModel::mdlMostrarActivos("inventario.activos", $item, $valor);
    }

    /*=============================================
    ELIMINAR ACTIVO (lógico)
    =============================================*/
    static public function ctrEliminarActivo()
    {
        if (empty($_POST["eliminarIdActivo"]))
            return ["resultado" => "error", "mensaje" => "ID no recibido."];

        $datos = array(
            "idActivos"         => intval($_POST["eliminarIdActivo"]),
            "idUsuarioModifica" => intval($_SESSION["usuario_id"])
        );

        return ActivosModel::mdlEliminarActivo($datos);
    }
}
