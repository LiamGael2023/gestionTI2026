<?php
require_once __DIR__ . "/../models/TipoActivosModel.php";

class TipoActivosController
{
    /*=============================================
    AGREGAR TIPO ACTIVO
    =============================================*/
    static public function ctrCrearActivo()
    {
        if (!isset($_POST["nuevaDescripcion"])) return null;

        $idUsuario = $_SESSION["usuario_id"];

        $datos = array(
            "descripcion"       => mb_strtoupper(trim($_POST["nuevaDescripcion"]), "UTF-8"),
            "icono"             => $_POST["iconoActivo"]           ?? "",
            "esCompuesto"       => isset($_POST["nuevoEsCompuesto"])   ? 1 : 0,
            "esComponente"      => isset($_POST["nuevoEsComponente"])  ? 1 : 0,
            "esPeriferico"      => isset($_POST["nuevoEsPeriferico"])  ? 1 : 0,
            "idUsuarioRegistro" => $idUsuario
        );

        return TipoActivosModel::mdlCrearActivo("inventario.tipoActivo", $datos);
    }

    /*=============================================
    EDITAR TIPO ACTIVO
    =============================================*/
    static public function ctrEditarActivo()
    {
        if (!isset($_POST["editarDescripcion"])) return null;

        if (empty($_POST["editarIdActivo"]))
            return ["resultado" => "error", "mensaje" => "ID no recibido."];

        $idUsuario = $_SESSION["usuario_id"];

        $datos = array(
            "idTipoActivo"  => $_POST["editarIdActivo"],
            "descripcion"   => mb_strtoupper(trim($_POST["editarDescripcion"]), "UTF-8"),
            "esCompuesto"   => isset($_POST["editarEsCompuesto"])   ? 1 : 0,
            "esComponente"  => isset($_POST["editarEsComponente"])  ? 1 : 0,
            "esPeriferico"  => isset($_POST["editarEsPeriferico"])  ? 1 : 0,
            "icono"         => $_POST["editarIconoActivo"] ?? "",
            "usuario"       => $idUsuario
        );

        return TipoActivosModel::mdlEditarActivo("inventario.tipoActivo", $datos);
    }

    /*=============================================
    MOSTRAR TIPO ACTIVOS
    =============================================*/
    static public function ctrMostrarActivos($item, $valor)
    {
        return TipoActivosModel::mdlMostrarActivos("inventario.tipoActivo", $item, $valor);
    }

    /*=============================================
    ELIMINAR TIPO ACTIVO (lógico)
    =============================================*/
    static public function ctrEliminarActivo()
    {
        if (empty($_POST["eliminarIdActivo"]))
            return ["resultado" => "error", "mensaje" => "ID no recibido."];

        $datos = array(
            "idTipoActivo"      => intval($_POST["eliminarIdActivo"]),
            "idUsuarioModifica" => intval($_SESSION["usuario_id"])
        );

        return TipoActivosModel::mdlEliminarActivo($datos);
    }
}
