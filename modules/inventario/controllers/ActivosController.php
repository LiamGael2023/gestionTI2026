<?php
require_once __DIR__ . "/../models/ActivosModel.php";

class ActivosController
{

    /*=============================================
    AGREGAR ACTIVOS
    =============================================*/
    static public function ctrCrearActivo()
    {
        if (isset($_POST["nuevaDescripcion"])) {

            $idUsuario = $_SESSION["usuario_id"];

            $datos = array(
                // Guardar siempre en MAYÚSCULAS y sin espacios extra
                "descripcion"       => mb_strtoupper(trim($_POST["nuevaDescripcion"]), "UTF-8"),
                "icono"             => $_POST["iconoActivo"],
                "compuesto"         => isset($_POST["nuevoCompuesto"]) ? 1 : 0,
                "idUsuarioRegistro" => $idUsuario
            );

            $tabla = "inventario.activos";
            $respuesta = ActivosModel::mdlCrearActivo($tabla, $datos);

            return $respuesta;
        }
    }

    /*=============================================
    EDITAR ACTIVOS
    =============================================*/
    static public function ctrEditarActivo()
    {
        if (isset($_POST["editarDescripcion"])) {

            if (empty($_POST["editarIdActivo"])) {
                return "error";
            }

            $idUsuario = $_SESSION["usuario_id"];

            $datos = array(
                "idActivos"   => $_POST["editarIdActivo"],
                // Guardar siempre en MAYÚSCULAS y sin espacios extra
                "descripcion" => mb_strtoupper(trim($_POST["editarDescripcion"]), "UTF-8"),
                "compuesto"   => isset($_POST["editarCompuesto"]) ? 1 : 0,
                "icono"       => $_POST["editarIconoActivo"],
                "usuario"     => $idUsuario
            );

            $tabla = "inventario.activos";
            $respuesta = ActivosModel::mdlEditarActivo($tabla, $datos);

            return $respuesta;
        }
    }

    /*=============================================
    MOSTRAR ACTIVOS
    =============================================*/
    static public function ctrMostrarActivos($item, $valor)
    {
        $tabla = "inventario.activos";
        $respuesta = ActivosModel::mdlMostrarActivos($tabla, $item, $valor);
        return $respuesta;
    }

    /*=============================================
    ELIMINAR ACTIVO (lógico)
    =============================================*/
    static public function ctrEliminarActivo()
    {
        if (isset($_POST["eliminarIdActivo"])) {

            if (empty($_POST["eliminarIdActivo"])) {
                return ["resultado" => "error", "mensaje" => "ID no recibido."];
            }

            $datos = array(
                "idActivos"         => intval($_POST["eliminarIdActivo"]),
                "idUsuarioModifica" => intval($_SESSION["usuario_id"])
            );

            $respuesta = ActivosModel::mdlEliminarActivo($datos);
            return $respuesta;
        }
    }
}
