<?
require_once "../models/ActivosModel.php";

class ActivosController {
    static public function ctrCrearActivo() {

        if (isset($_POST["nuevaDescripcion"])) {

            /* IMPORTANTE: Extraemos el ID del usuario directamente de la sesión. 
               Esto garantiza que el registro sea auditado correctamente.
            */
            $idUsuario = $_SESSION["id_usuario"]; 

            $datos = array(
                "descripcion"       => $_POST["nuevaDescripcion"],
                "icono"             => $_POST["iconoActivo"],
                "compuesto"         => isset($_POST["nuevoCompuesto"]) ? 1 : 0,
                "idUsuarioRegistro" => $idUsuario 
            );

            // Llamada al modelo para ejecutar el SP sp_InsertarActivo
            $tabla = "inventario.activos";
            $respuesta = ActivosModel::mdlCrearActivo($tabla, $datos);

            return $respuesta;

        }
    }

    /*=============================================
    MOSTRAR ACTIVOS
    =============================================*/
    static public function ctrMostrarActivos($item, $valor){

        $tabla = "inventario.activos";

        $respuesta = ActivosModel::mdlMostrarActivos($tabla, $item, $valor);

        return $respuesta;

    }

   
}