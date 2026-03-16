<?php
require_once __DIR__ . "/../models/EquipoModel.php";

class EquipoController {

    /*=============================================
    CREAR / EDITAR EQUIPO
    =============================================*/
    static public function ctrCrearEditarEquipo() {
        if (isset($_POST["idActivo"])) {
            $idUsuario = $_SESSION["usuario_id"];

            $datos = array(
                "idEquipo"          => $_POST["idEquipo"] ?? null,
                "idActivo"          => $_POST["idActivo"],
                "idEquipoPadre"     => $_POST["idEquipoPadre"] ?? null,
                "codigoPatrimonial" => $_POST["codigoPatrimonial"] ?? null,
                "numeroSerie"       => $_POST["numeroSerie"] ?? null,
                "fechaInicioGarantia" => $_POST["fechaInicioGarantia"] ?? null,
                "fechaFinGarantia"    => $_POST["fechaFinGarantia"] ?? null,
                "fechaAdquisicion"    => $_POST["fechaAdquisicion"] ?? null,
                "idCaracteristicas"   => $_POST["idCaracteristicas"] ?? null,
                "idUsuario"           => $idUsuario
            );

            $tabla = "inventario.equipo";
            $respuesta = EquipoModel::mdlCrearEditarEquipo($datos);

            return $respuesta;
        }
    }

    /*=============================================
    MOSTRAR EQUIPO
    =============================================*/
    static public function ctrMostrarEquipo($item, $valor) {
        $tabla = "inventario.equipo";
        $respuesta = EquipoModel::mdlMostrarEquipo($tabla, $item, $valor);
        return $respuesta;
    }
}
