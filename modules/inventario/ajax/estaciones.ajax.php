<?php
session_start();
require_once __DIR__ . "/../controllers/EstacionController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) { echo json_encode($data); exit; }

function fmtFecha($fecha, $fmt = "d/m/Y H:i:s") {
    if (!$fecha) return "--";
    if ($fecha instanceof DateTime) return $fecha->format($fmt);
    $ts = strtotime($fecha);
    return $ts ? date($fmt, $ts) : "--";
}

/* ── Listar IPs disponibles ── */
if (isset($_GET["listarIps"])) {
    // Pasar idEstacion para que en editar se muestre también la IP actual
    $idEstacion = intval($_GET["idEstacion"] ?? 0);
    responder(EstacionController::ctrListarIps($idEstacion));
}

/* ── Listar equipos por tipo ── */
if (isset($_GET["listarEquipos"])) {
    $tipo       = $_GET["tipo"]       ?? 'principal';
    $idEstacion = intval($_GET["idEstacion"] ?? 0);
    $excluir    = array_filter(array_map('intval', explode(',', $_GET["excluir"] ?? '')));
    $equipos    = EstacionController::ctrListarEquiposTipo($tipo, $idEstacion, $excluir);
    $result     = [];
    foreach (($equipos ?: []) as $eq) {
        $label = $tipo === 'software'
            ? ($eq["nombreActivo"] ?? 'Software') . (!empty($eq["numeroSerie"]) ? ' — '.$eq["numeroSerie"] : '')
            : (!empty($eq["codigoPatrimonial"]) ? '['.$eq["codigoPatrimonial"].'] ' : '') . ($eq["nombreActivo"] ?? 'Equipo') . (!empty($eq["numeroSerie"]) ? ' — '.$eq["numeroSerie"] : '');
        $result[] = [
            "idEquipo"          => intval($eq["idEquipo"]),
            "label"             => $label,
            "iconoActivo"       => $eq["iconoActivo"]       ?? "ti-package",
            "nombreActivo"      => $eq["nombreActivo"]      ?? "",
            "numeroSerie"       => $eq["numeroSerie"]       ?? "",
            "codigoPatrimonial" => $eq["codigoPatrimonial"] ?? "",
        ];
    }
    responder($result);
}

/* ── Ver detalle ── */
if (isset($_POST["verDetalle"])) {
    responder(EstacionController::ctrVerDetalle(intval($_POST["verDetalle"])));
}

/* ── Crear estación ── */
if (isset($_POST["nuevoNombreEstacion"])) {
    responder(EstacionController::ctrCrearEstacion());
}

/* ── Editar estación ── */
if (isset($_POST["editarNombreEstacion"])) {
    responder(EstacionController::ctrEditarEstacion());
}
