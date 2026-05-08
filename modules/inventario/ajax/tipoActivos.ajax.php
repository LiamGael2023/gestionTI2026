<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . "/../models/TipoActivosModel.php";
require_once __DIR__ . "/../controllers/TipoActivosController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) {
    if (is_string($data)) {
        echo json_encode(["resultado" => $data, "mensaje" => $data]);
    } elseif (is_array($data)) {
        if (!isset($data["resultado"])) $data["resultado"] = "error";
        if (!isset($data["mensaje"]))   $data["mensaje"]   = "";
        echo json_encode($data);
    } else {
        echo json_encode(["resultado" => "error", "mensaje" => "Respuesta inválida del servidor."]);
    }
    exit;
}

class AjaxTipoActivos
{
    /*=============================================
    AGREGAR TIPO ACTIVO
    =============================================*/
    public function ajaxCrearActivo()
    {
        $respuesta = TipoActivosController::ctrCrearActivo();
        responder($respuesta ?? "error");
    }

    /*=============================================
    EDITAR TIPO ACTIVO
    =============================================*/
    public function ajaxEditarActivo()
    {
        $respuesta = TipoActivosController::ctrEditarActivo();
        responder($respuesta ?? "error");
    }

    /*=============================================
    CARGAR DATOS PARA MODAL EDITAR
    =============================================*/
    public $idActivo;

    public function ajaxMostrarEditarActivo()
    {
        $item  = "idTipoActivo";
        $valor = $this->idActivo;

        $activo = TipoActivosController::ctrMostrarActivos($item, $valor);

        if (!$activo || $activo === "error") {
            responder(["resultado" => "error", "mensaje" => "No se encontró el tipo de activo."]);
            return;
        }

        // nombreUsuario viene del JOIN en el modelo (nombres + apellidos)
        $nombreUsuario = trim($activo["nombreUsuario"] ?? "");
        if ($nombreUsuario === "") {
            $nombreUsuario = "ID " . ($activo["idUsuarioRegistro"] ?? "—");
        }

        $respuesta = [
            "resultado"         => "ok",
            "idTipoActivo"      => intval($activo["idTipoActivo"]),
            "descripcion"       => $activo["descripcion"]    ?? "",
            "icono"             => $activo["icono"]          ?? "",
            "esCompuesto"       => intval($activo["esCompuesto"]  ?? 0),
            "esComponente"      => intval($activo["esComponente"] ?? 0),
            "esPeriferico"      => intval($activo["esPeriferico"] ?? 0),
            "idUsuarioRegistro" => $activo["idUsuarioRegistro"] ?? "",
            "nombreUsuario"     => $nombreUsuario,
            "fechaCreacion"     => isset($activo["fechaCreacion"])
                ? ($activo["fechaCreacion"] instanceof DateTime
                    ? $activo["fechaCreacion"]->format("d/m/Y")
                    : date("d/m/Y", strtotime($activo["fechaCreacion"])))
                : ""
        ];

        echo json_encode($respuesta);
        exit;
    }

    /*=============================================
    ELIMINAR TIPO ACTIVO (lógico)
    =============================================*/
    public function ajaxEliminarActivo()
    {
        $respuesta = TipoActivosController::ctrEliminarActivo();
        responder($respuesta ?? ["resultado" => "error", "mensaje" => "Sin respuesta."]);
    }
}

/* ── CREAR ── */
if (isset($_POST["nuevaDescripcion"])) {
    $obj = new AjaxTipoActivos();
    $obj->ajaxCrearActivo();
}

/* ── EDITAR ── */
if (isset($_POST["editarDescripcion"])) {
    $obj = new AjaxTipoActivos();
    $obj->ajaxEditarActivo();
}

/* ── CARGAR PARA MODAL EDITAR ── */
if (isset($_POST["idActivo"])) {
    $obj           = new AjaxTipoActivos();
    $obj->idActivo = $_POST["idActivo"];
    $obj->ajaxMostrarEditarActivo();
}

/* ── ELIMINAR ── */
if (isset($_POST["eliminarIdActivo"])) {
    $obj = new AjaxTipoActivos();
    $obj->ajaxEliminarActivo();
}

// Si no coincide nada
responder(["resultado" => "error", "mensaje" => "Solicitud no reconocida."]);