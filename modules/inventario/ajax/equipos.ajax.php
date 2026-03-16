<?php
session_start();
require_once __DIR__ . "/../models/EquipoModel.php";
require_once __DIR__ . "/../controllers/EquipoController.php";

class AjaxEquipo
{
    /*=============================================
    CREAR EQUIPO
    =============================================*/
    public function ajaxCrearEquipo()
    {
        $respuesta = EquipoController::ctrCrearEditarEquipo();
        echo json_encode($respuesta);
    }

    /*=============================================
    MOSTRAR PARA EDITAR EQUIPO
    =============================================*/
    public $idEquipo;

    public function ajaxMostrarEditarEquipo()
    {
        $item = "idEquipo";
        $valor = $this->idEquipo;

        $equipo = EquipoController::ctrMostrarEquipo($item, $valor);

        if (!$equipo) {
            echo json_encode(["error" => "No se encontró el equipo"]);
            return;
        }

        $respuesta = [
            "idEquipo"            => intval($equipo["idEquipo"]),
            "idActivo"            => intval($equipo["idActivo"]),
            "idEquipoPadre"       => $equipo["idEquipoPadre"] ?? null,
            "codigoPatrimonial"   => $equipo["codigoPatrimonial"] ?? "",
            "numeroSerie"         => $equipo["numeroSerie"] ?? "",
            "fechaAdquisicion"    => isset($equipo["fechaAdquisicion"])
                ? date("Y-m-d", strtotime($equipo["fechaAdquisicion"])) : null,
            "fechaInicioGarantia" => isset($equipo["fechaInicioGarantia"])
                ? date("Y-m-d", strtotime($equipo["fechaInicioGarantia"])) : null,
            "fechaFinGarantia"    => isset($equipo["fechaFinGarantia"])
                ? date("Y-m-d", strtotime($equipo["fechaFinGarantia"])) : null,
            "usuarioCreacion"     => $equipo["usuarioCreacion"] ?? "",
            "fechaCreacion"       => isset($equipo["fechaCreacion"])
                ? ($equipo["fechaCreacion"] instanceof DateTime
                    ? $equipo["fechaCreacion"]->format("d/m/Y H:i:s")
                    : date("d/m/Y H:i:s", strtotime($equipo["fechaCreacion"])))
                : "",
            "usuarioModificacion" => $equipo["usuarioModificacion"] ?? "",
            "fechaModificacion"   => isset($equipo["fechaModificacion"])
                ? ($equipo["fechaModificacion"] instanceof DateTime
                    ? $equipo["fechaModificacion"]->format("d/m/Y H:i:s")
                    : date("d/m/Y H:i:s", strtotime($equipo["fechaModificacion"])))
                : "",
            "caracteristicas"     => $equipo["caracteristicas"] ?? ""
        ];

        echo json_encode($respuesta);
    }
}

/*=============================================
DISPARADORES
=============================================*/

// Crear equipo
if (isset($_POST["idActivo"])) {
    $crear = new AjaxEquipo();
    $crear->ajaxCrearEquipo();
}

// Mostrar para editar equipo
if (isset($_POST["idEquipo"])) {
    $mostrar = new AjaxEquipo();
    $mostrar->idEquipo = $_POST["idEquipo"];
    $mostrar->ajaxMostrarEditarEquipo();
}
