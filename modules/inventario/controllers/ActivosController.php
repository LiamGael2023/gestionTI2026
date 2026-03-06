<?php
class InventarioController {
    static public function ctrCrearActivo() {
        if (isset($_POST["nuevaDescripcion"])) {
            $tabla = "inventario.activos"; // Asegúrate de que el esquema exista
            
            $datos = array(
                "descripcion"     => strtoupper($_POST["nuevaDescripcion"]),
                "icono"           => $_POST["iconoActivo"],
                "compuesto"       => isset($_POST["nuevoCompuesto"]) ? 1 : 0,
                "usuarioCreacion" => "admin_user" 
            );

            $respuesta = ActivosModel::mdlIngresarActivo($tabla, $datos);
            return $respuesta;
        }
    }
}