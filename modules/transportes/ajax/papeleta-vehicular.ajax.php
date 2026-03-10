<?php
error_log(print_r($_POST, true));

require_once "../../controladores/transportes/papeleta-vehicular.controlador.php";
require_once "../../modelos/transportes/papeleta-vehicular.modelo.php";

class AjaxPapeletaVehicular
{
   static public function ajaxRegistrarBitacora($datos)
   {
       $respuesta = ControladorPapeletaVehicular::ctrRegistrarBitacora($datos);
       echo json_encode($respuesta);
   }
}



if (isset($_POST["accion"]) && $_POST["accion"] === "registrarBitacora") {
   $datos = array(
       "descripcion_bitacora" => $_POST["descripcion_bitacora"],
       "id_papeleta_vehicular" => $_POST["id_papeleta_vehicular"],
   );
   AjaxPapeletaVehicular::ajaxRegistrarBitacora($datos);
}