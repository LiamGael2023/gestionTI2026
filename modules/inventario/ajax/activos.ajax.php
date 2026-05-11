<?php
session_start();
require_once __DIR__ . "/../models/ActivosModel.php";
require_once __DIR__ . "/../controllers/ActivosController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) { echo json_encode($data); exit; }

function fmtFecha($fecha, $formato = "Y-m-d") {
    if (!$fecha) return null;
    if ($fecha instanceof DateTime) return $fecha->format($formato);
    $ts = strtotime($fecha);
    return $ts ? date($formato, $ts) : null;
}

/* ── AGREGAR COMPONENTE ── */
if (isset($_POST["accion"]) && $_POST["accion"] === "agregarComponente") {
    $idPadre = intval($_POST["idActivoPadre"] ?? 0);
    $idHijo  = intval($_POST["idActivoHijo"]  ?? 0);
    if (!$idPadre || !$idHijo) responder(["resultado" => "error", "mensaje" => "Datos incompletos."]);
    responder(ActivosController::ctrAgregarComponente($idPadre, $idHijo));
}

/* ── QUITAR COMPONENTE ── */
if (isset($_POST["accion"]) && $_POST["accion"] === "quitarComponente") {
    $idHijo = intval($_POST["idActivoHijo"] ?? 0);
    if (!$idHijo) responder(["resultado" => "error", "mensaje" => "ID de componente no recibido."]);
    responder(ActivosController::ctrQuitarComponente($idHijo));
}

/* ── ELIMINAR ACTIVO (lógico) ── */
if (isset($_POST["eliminarIdActivo"])) {
    responder(ActivosController::ctrEliminarActivo());
}

/* ── CREAR ACTIVO ── */
if (isset($_POST["nuevoIdTipoActivo"])) {
    responder(ActivosController::ctrCrearActivo());
}

/* ── EDITAR ACTIVO ── */
if (isset($_POST["editarIdTipoActivo"])) {
    responder(ActivosController::ctrEditarActivo());
}

/* ── CARGAR DATOS PARA MODAL EDITAR ── */
if (isset($_POST["idActivo"])) {
    $activo = ActivosController::ctrMostrarActivo("idActivo", intval($_POST["idActivo"]));
    if (!$activo) responder(["error" => "No se encontró el activo."]);
    $caracteristicasDetalle = ActivosController::ctrMostrarCaracteristicasActivo(intval($_POST["idActivo"]));
    responder([
        "idActivo"               => intval($activo["idActivo"]),
        "idTipoActivo"           => intval($activo["idTipoActivo"]),
        "idActivoPadre"          => $activo["idActivoPadre"]       ?? null,
        "codigoPatrimonial"      => $activo["codigoPatrimonial"]   ?? "",
        "codigoLicencia"         => $activo["codigoLicencia"]      ?? "",
        "numeroSerie"            => $activo["numeroSerie"]         ?? "",
        "fechaAdquisicion"       => fmtFecha($activo["fechaAdquisicion"]    ?? null),
        "fechaInicioGarantia"    => fmtFecha($activo["fechaInicioGarantia"] ?? null),
        "fechaFinGarantia"       => fmtFecha($activo["fechaFinGarantia"]    ?? null),
        "estado"                 => $activo["estado"]              ?? "disponible",
        "esCompuesto"            => intval($activo["esCompuesto"]  ?? 0),
        "esPeriferico"           => intval($activo["esPeriferico"] ?? 0),
        "esComponente"           => intval($activo["esComponente"] ?? 0),
        "idUsuarioRegistro"      => $activo["idUsuarioRegistro"]   ?? "",
        "nombreUsuarioRegistro"  => trim($activo["nombreUsuarioRegistro"] ?? "") ?: ("ID " . ($activo["idUsuarioRegistro"] ?? "—")),
        "nombreUsuarioModifica"  => trim($activo["nombreUsuarioModifica"]  ?? "") ?: ("ID " . ($activo["idUsuarioModifica"]  ?? "—")),
        "fechaCreacion"          => fmtFecha($activo["fechaCreacion"]       ?? null, "d/m/Y H:i:s"),
        "idUsuarioModifica"      => $activo["idUsuarioModifica"]   ?? "",
        "fechaModificacion"      => fmtFecha($activo["fechaModificacion"]   ?? null, "d/m/Y H:i:s"),
        "nombreActivo"           => $activo["nombreActivo"]        ?? "",
        "caracteristicasDetalle" => $caracteristicasDetalle,
    ]);
}

/* ── CARGAR COMPONENTES DEL ACTIVO PADRE ── */
if (isset($_POST["idActivoPadre"])) {
    $componentes = ActivosController::ctrMostrarComponentes(intval($_POST["idActivoPadre"]));
    responder($componentes ?: []);
}

/* ── ACTIVOS DISPONIBLES ── */
if (isset($_GET["disponibles"])) {
    $idPadre     = intval($_GET["idPadre"] ?? 0);
    $disponibles = ActivosController::ctrActivosDisponibles($idPadre);
    $result = [];
    foreach (($disponibles ?: []) as $eq) {
        $result[] = [
            "idActivo"          => intval($eq["idActivo"]),
            "label"             => ($eq["nombreActivo"] ?? 'Activo')
                                 . (!empty($eq["numeroSerie"])       ? ' — ' . $eq["numeroSerie"]       : '')
                                 . (!empty($eq["codigoPatrimonial"]) ? ' [' . $eq["codigoPatrimonial"] . ']' : ''),
            "icono"             => $eq["iconoActivo"]       ?? "ti-package",
            "numeroSerie"       => $eq["numeroSerie"]       ?? "",
            "codigoPatrimonial" => $eq["codigoPatrimonial"] ?? "",
            "caracteristicas"   => $eq["caracteristicas"]   ?? "",
        ];
    }
    responder($result);
}