<?php
session_start();
require_once __DIR__ . "/../controllers/UbicacionController.php";
require_once __DIR__ . "/../controllers/AmbienteController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) { echo json_encode($data); exit; }

function fmtFecha($fecha, $formato = "d/m/Y H:i:s") {
    if (!$fecha) return "--";
    if ($fecha instanceof DateTime) return $fecha->format($formato);
    $ts = strtotime($fecha);
    return $ts ? date($formato, $ts) : "--";
}

/* ─── UBICACIONES ─── */

if (isset($_POST["nuevaDescripcionUbicacion"])) {
    responder(UbicacionController::ctrCrearUbicacion());
}

if (isset($_POST["editarDescripcionUbicacion"])) {
    responder(UbicacionController::ctrEditarUbicacion());
}

if (isset($_POST["idUbicacion"])) {
    $ub = UbicacionController::ctrMostrarUbicacion("idUbicacion", intval($_POST["idUbicacion"]));
    if (!$ub) responder(["error" => "No se encontró la ubicación."]);
    responder([
        "idUbicacion"       => intval($ub["idUbicacion"]),
        "descripcion"       => $ub["descripcion"]       ?? "",
        "idUbicacionPadre"  => $ub["idUbicacionPadre"]  ?? null,
        "descripcionPadre"  => $ub["descripcionPadre"]  ?? "",
        "idUsuarioRegistro" => $ub["idUsuarioRegistro"] ?? "",
        "fechaCreacion"     => fmtFecha($ub["fechaCreacion"]     ?? null),
        "idUsuarioModifica" => $ub["idUsuarioModifica"] ?? "",
        "fechaModificacion" => fmtFecha($ub["fechaModificacion"] ?? null),
    ]);
}

// Listar todas las ubicaciones (para el combo de ambiente)
if (isset($_GET["listarUbicaciones"])) {
    $lista = UbicacionController::ctrMostrarUbicacion(null, null);
    if ($lista === "error" || !$lista) responder([]);
    $result = [];
    foreach ($lista as $u) {
        $result[] = [
            "idUbicacion" => intval($u["idUbicacion"]),
            "descripcion" => $u["descripcion"] ?? "",
        ];
    }
    responder($result);
}

/* ─── AMBIENTES ─── */

if (isset($_POST["nuevaDescripcionAmbiente"])) {
    responder(AmbienteController::ctrCrearAmbiente());
}

if (isset($_POST["editarDescripcionAmbiente"])) {
    responder(AmbienteController::ctrEditarAmbiente());
}

if (isset($_POST["idAmbiente"])) {
    $amb = AmbienteController::ctrMostrarAmbiente("idAmbiente", intval($_POST["idAmbiente"]));
    if (!$amb) responder(["error" => "No se encontró el ambiente."]);
    responder([
        "idAmbiente"        => intval($amb["idAmbiente"]),
        "descripcion"       => $amb["descripcion"]       ?? "",
        "idUbicacion"       => intval($amb["idUbicacion"]),
        "nombreUbicacion"   => $amb["nombreUbicacion"]   ?? "",
        "idUsuarioRegistro" => $amb["idUsuarioRegistro"] ?? "",
        "fechaCreacion"     => fmtFecha($amb["fechaCreacion"]     ?? null),
        "idUsuarioModifica" => $amb["idUsuarioModifica"] ?? "",
        "fechaModificacion" => fmtFecha($amb["fechaModificacion"] ?? null),
    ]);
}
