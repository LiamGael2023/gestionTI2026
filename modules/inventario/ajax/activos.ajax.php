<?php
session_start();
ini_set('display_errors', 0); // No mostrar errores PHP en output
error_reporting(E_ALL);

require_once __DIR__ . "/../models/ActivosModel.php";
require_once __DIR__ . "/../controllers/ActivosController.php";

header('Content-Type: application/json; charset=utf-8');

// Función helper: siempre devuelve JSON limpio con resultado y mensaje
function responder($data) {
    if (is_string($data)) {
        echo json_encode(["resultado" => $data, "mensaje" => $data]);
    } elseif (is_array($data)) {
        // Asegurar que siempre existan las claves resultado y mensaje
        if (!isset($data["resultado"])) $data["resultado"] = "error";
        if (!isset($data["mensaje"]))   $data["mensaje"]   = "";
        echo json_encode($data);
    } else {
        echo json_encode(["resultado" => "error", "mensaje" => "Respuesta inválida del servidor."]);
    }
    exit;
}

class AjaxActivos
{
    /*=============================================
    AGREGAR ACTIVO
    =============================================*/
    public function ajaxCrearActivo()
    {
        $respuesta = ActivosController::ctrCrearActivo();
        responder($respuesta ?? "error");
    }

    /*=============================================
    EDITAR ACTIVO
    =============================================*/
    public function ajaxEditarActivo()
    {
        $respuesta = ActivosController::ctrEditarActivo();
        responder($respuesta ?? "error");
    }

    /*=============================================
    CARGAR DATOS PARA MODAL EDITAR
    =============================================*/
    public $idActivo;

    public function ajaxMostrarEditarActivo()
    {
        $item  = "idActivos";
        $valor = $this->idActivo;

        $activo = ActivosController::ctrMostrarActivos($item, $valor);

        if (!$activo || $activo === "error") {
            responder(["resultado" => "error", "mensaje" => "No se encontró el activo."]);
            return;
        }

        $respuesta = [
            "resultado"         => "ok",
            "idActivos"         => intval($activo["idActivos"]),
            "descripcion"       => $activo["descripcion"]       ?? "",
            "icono"             => $activo["icono"]             ?? "",
            "compuesto"         => intval($activo["compuesto"]  ?? 0),
            "esPeriferico"      => intval($activo["esPeriferico"] ?? 0),
            "idUsuarioRegistro" => $activo["idUsuarioRegistro"] ?? "",
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
    ELIMINAR ACTIVO (lógico)
    =============================================*/
    public function ajaxEliminarActivo()
    {
        $respuesta = ActivosController::ctrEliminarActivo();
        responder($respuesta ?? ["resultado" => "error", "mensaje" => "Sin respuesta."]);
    }
}

/* ── CREAR ── */
if (isset($_POST["nuevaDescripcion"])) {
    $obj = new AjaxActivos();
    $obj->ajaxCrearActivo();
}

/* ── EDITAR ── */
if (isset($_POST["editarDescripcion"])) {
    $obj = new AjaxActivos();
    $obj->ajaxEditarActivo();
}

/* ── CARGAR PARA MODAL EDITAR ── */
if (isset($_POST["idActivo"])) {
    $obj           = new AjaxActivos();
    $obj->idActivo = $_POST["idActivo"];
    $obj->ajaxMostrarEditarActivo();
}

/* ── ELIMINAR ── */
if (isset($_POST["eliminarIdActivo"])) {
    $obj = new AjaxActivos();
    $obj->ajaxEliminarActivo();
}

// Si no coincide nada
responder(["resultado" => "error", "mensaje" => "Solicitud no reconocida."]);
