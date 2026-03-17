<?php
session_start();
require_once __DIR__ . "/../models/EquipoModel.php";
require_once __DIR__ . "/../controllers/EquipoController.php";

header('Content-Type: application/json; charset=utf-8');

class AjaxEquipo
{
    /*=============================================
    CREAR EQUIPO
    =============================================*/
    public function ajaxCrearEquipo()
    {
        $respuesta = EquipoController::ctrCrearEquipo();
        echo json_encode($respuesta);
    }

    /*=============================================
    EDITAR EQUIPO
    =============================================*/
    public function ajaxEditarEquipo()
    {
        $respuesta = EquipoController::ctrEditarEquipo();
        echo json_encode($respuesta);
    }

    /*=============================================
    CARGAR DATOS PARA MODAL EDITAR
    Devuelve los datos del equipo + array de
    características con sus IDs reales para que
    el JS pueda reconstruir la lista editable.
    =============================================*/
    public $idEquipo;

    public function ajaxMostrarEquipo()
    {
        $equipo = EquipoController::ctrMostrarEquipo("idEquipo", $this->idEquipo);

        if (!$equipo) {
            echo json_encode(["error" => "No se encontró el equipo."]);
            return;
        }

        // Helper fechas DateTime de sqlsrv
        $fmt = function ($fecha, $formato = "Y-m-d") {
            if (!$fecha) return null;
            if ($fecha instanceof DateTime) return $fecha->format($formato);
            $ts = strtotime($fecha);
            return $ts ? date($formato, $ts) : null;
        };

        // Traer características con sus IDs reales desde la BD
        $caracteristicasDetalle = EquipoController::ctrMostrarCaracteristicasEquipo($this->idEquipo);

        $respuesta = [
            "idEquipo"            => intval($equipo["idEquipo"]),
            "idActivo"            => intval($equipo["idActivo"]),
            "idEquipoPadre"       => $equipo["idEquipoPadre"]       ?? null,
            "codigoPatrimonial"   => $equipo["codigoPatrimonial"]   ?? "",
            "numeroSerie"         => $equipo["numeroSerie"]         ?? "",
            "fechaAdquisicion"    => $fmt($equipo["fechaAdquisicion"]    ?? null),
            "fechaInicioGarantia" => $fmt($equipo["fechaInicioGarantia"] ?? null),
            "fechaFinGarantia"    => $fmt($equipo["fechaFinGarantia"]    ?? null),
            "idUsuarioRegistro"   => $equipo["idUsuarioRegistro"]   ?? "",
            "fechaCreacion"       => $fmt($equipo["fechaCreacion"]       ?? null, "d/m/Y H:i:s"),
            "idUsuarioModifica"   => $equipo["idUsuarioModifica"]   ?? "",
            "fechaModificacion"   => $fmt($equipo["fechaModificacion"]   ?? null, "d/m/Y H:i:s"),
            "nombreActivo"        => $equipo["nombreActivo"]        ?? "",
            // Array con {idCaracteristica, tipo, valor} para reconstruir la tabla editable
            "caracteristicasDetalle" => $caracteristicasDetalle,
        ];

        echo json_encode($respuesta);
    }
}

/* =============================================
   DISPARADORES
============================================= */

// Crear equipo nuevo
if (isset($_POST["nuevoIdActivo"])) {
    (new AjaxEquipo())->ajaxCrearEquipo();
    exit;
}

// Editar equipo existente
if (isset($_POST["editarIdActivo"])) {
    (new AjaxEquipo())->ajaxEditarEquipo();
    exit;
}

// Cargar datos para el modal editar
if (isset($_POST["idEquipo"])) {
    $ajax           = new AjaxEquipo();
    $ajax->idEquipo = intval($_POST["idEquipo"]);
    $ajax->ajaxMostrarEquipo();
    exit;
}