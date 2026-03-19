<?php
session_start();
require_once __DIR__ . "/../models/EquipoModel.php";
require_once __DIR__ . "/../controllers/EquipoController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) { echo json_encode($data); exit; }

function fmtFecha($fecha, $formato = "Y-m-d") {
    if (!$fecha) return null;
    if ($fecha instanceof DateTime) return $fecha->format($formato);
    $ts = strtotime($fecha);
    return $ts ? date($formato, $ts) : null;
}

/* ═══════════════════════════════════════════════
   AGREGAR COMPONENTE  ← DEBE IR PRIMERO
   porque su POST también contiene idEquipoPadre
   y el bloque de abajo lo interceptaría antes
   POST { accion:'agregarComponente', idEquipoPadre, idEquipoHijo }
═══════════════════════════════════════════════ */
if (isset($_POST["accion"]) && $_POST["accion"] === "agregarComponente") {
    $idPadre = intval($_POST["idEquipoPadre"] ?? 0);
    $idHijo  = intval($_POST["idEquipoHijo"]  ?? 0);
    if (!$idPadre || !$idHijo) responder(["resultado" => "error", "mensaje" => "Datos incompletos."]);
    responder(EquipoController::ctrAgregarComponente($idPadre, $idHijo));
}

/* ═══════════════════════════════════════════════
   QUITAR COMPONENTE  ← también antes de idEquipoPadre
   POST { accion:'quitarComponente', idEquipoHijo }
═══════════════════════════════════════════════ */
if (isset($_POST["accion"]) && $_POST["accion"] === "quitarComponente") {
    $idHijo = intval($_POST["idEquipoHijo"] ?? 0);
    if (!$idHijo) responder(["resultado" => "error", "mensaje" => "ID de componente no recibido."]);
    responder(EquipoController::ctrQuitarComponente($idHijo));
}

/* ═══════════════════════════════════════════════
   CREAR EQUIPO
═══════════════════════════════════════════════ */
if (isset($_POST["nuevoIdActivo"])) {
    responder(EquipoController::ctrCrearEquipo());
}

/* ═══════════════════════════════════════════════
   EDITAR EQUIPO
═══════════════════════════════════════════════ */
if (isset($_POST["editarIdActivo"])) {
    responder(EquipoController::ctrEditarEquipo());
}

/* ═══════════════════════════════════════════════
   CARGAR DATOS PARA MODAL EDITAR
═══════════════════════════════════════════════ */
if (isset($_POST["idEquipo"])) {
    $equipo = EquipoController::ctrMostrarEquipo("idEquipo", intval($_POST["idEquipo"]));
    if (!$equipo) responder(["error" => "No se encontró el equipo."]);

    $caracteristicasDetalle = EquipoController::ctrMostrarCaracteristicasEquipo(intval($_POST["idEquipo"]));

    responder([
        "idEquipo"               => intval($equipo["idEquipo"]),
        "idActivo"               => intval($equipo["idActivo"]),
        "idEquipoPadre"          => $equipo["idEquipoPadre"]       ?? null,
        "codigoPatrimonial"      => $equipo["codigoPatrimonial"]   ?? "",
        "numeroSerie"            => $equipo["numeroSerie"]         ?? "",
        "fechaAdquisicion"       => fmtFecha($equipo["fechaAdquisicion"]    ?? null),
        "fechaInicioGarantia"    => fmtFecha($equipo["fechaInicioGarantia"] ?? null),
        "fechaFinGarantia"       => fmtFecha($equipo["fechaFinGarantia"]    ?? null),
        "idUsuarioRegistro"      => $equipo["idUsuarioRegistro"]   ?? "",
        "fechaCreacion"          => fmtFecha($equipo["fechaCreacion"]       ?? null, "d/m/Y H:i:s"),
        "idUsuarioModifica"      => $equipo["idUsuarioModifica"]   ?? "",
        "fechaModificacion"      => fmtFecha($equipo["fechaModificacion"]   ?? null, "d/m/Y H:i:s"),
        "nombreActivo"           => $equipo["nombreActivo"]        ?? "",
        "caracteristicasDetalle" => $caracteristicasDetalle,
    ]);
}

/* ═══════════════════════════════════════════════
   CARGAR COMPONENTES ACTUALES DEL EQUIPO PADRE
   POST { idEquipoPadre }
═══════════════════════════════════════════════ */
if (isset($_POST["idEquipoPadre"])) {
    $componentes = EquipoController::ctrMostrarComponentes(intval($_POST["idEquipoPadre"]));
    responder($componentes ?: []);
}

/* ═══════════════════════════════════════════════
   CARGAR EQUIPOS DISPONIBLES (sin padre)
   GET ?disponibles=1&idPadre=N
═══════════════════════════════════════════════ */
if (isset($_GET["disponibles"])) {
    $idPadre     = intval($_GET["idPadre"] ?? 0);
    $disponibles = EquipoController::ctrEquiposDisponibles($idPadre);
    $result = [];
    foreach (($disponibles ?: []) as $eq) {
        $result[] = [
            "idEquipo"          => intval($eq["idEquipo"]),
            "label"             => ($eq["nombreActivo"] ?? 'Equipo')
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