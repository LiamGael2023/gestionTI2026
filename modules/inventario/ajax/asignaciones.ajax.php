<?php
session_start();
require_once __DIR__ . "/../controllers/AsignacionController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

function fmtFecha($f, $fmt = "d/m/Y") {
    if (!$f) return "—";
    if ($f instanceof DateTime) return $f->format($fmt);
    $ts = strtotime($f); return $ts ? date($fmt, $ts) : "—";
}

/* ── GET: listar ambientes ── */
if (isset($_GET['listarAmbientes'])) {
    $ambientes = AsignacionController::ctrListarAmbientes();
    $result    = [];
    foreach ($ambientes as $a) {
        $label = $a['descripcion'];
        if (!empty($a['nombreUbicacion'])) {
            $label .= ' — ' . $a['nombreUbicacion'];
        }
        $result[] = [
            'idAmbiente'      => $a['idAmbiente'],
            'descripcion'     => $a['descripcion'],
            'nombreUbicacion' => $a['nombreUbicacion'] ?? '',
            'label'           => $label,
        ];
    }
    responder($result);
}

/* ── GET: listar estaciones sin asignación activa ── */
if (isset($_GET['listarEstaciones'])) {
    $rows = AsignacionController::ctrEstacionesSinAsignacion();
    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'idEstacion'      => intval($r['idEstacion']),
            'label'           => $r['nombreEstacion'] . (!empty($r['ipAddress']) ? ' — '.$r['ipAddress'] : ''),
            'nombreEstacion'  => $r['nombreEstacion'],
        ];
    }
    responder($result);
}

/* ── GET: equipos de una estación (preview reporte) ── */
if (isset($_GET['equiposEstacion'])) {
    $idEst = intval($_GET['idEstacion'] ?? 0);
    if (!$idEst) responder([]);
    $equipos = AsignacionController::ctrEquiposEstacion($idEst);
    $result  = [];
    foreach ($equipos as $eq) {
        $result[] = [
            'idEquipo'          => intval($eq['idEquipo']),
            'codigoPatrimonial' => $eq['codigoPatrimonial'] ?? '',
            'numeroSerie'       => $eq['numeroSerie']       ?? '',
            'nombreActivo'      => $eq['nombreActivo']      ?? '',
            'iconoActivo'       => $eq['iconoActivo']       ?? 'ti-package',
            'tipoEquipo'        => $eq['tipoEquipo']        ?? '',
            'caracteristicas'   => $eq['caracteristicas']   ?? '',
        ];
    }
    responder($result);
}

/* ── GET: historial de una estación ── */
if (isset($_GET['historial'])) {
    $idEst = intval($_GET['idEstacion'] ?? 0);
    if (!$idEst) responder([]);
    $rows   = AsignacionController::ctrHistorialEstacion($idEst);
    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'idAsignacion'            => intval($r['idAsignacion']),
            'dniTrabajadorResponsable'=> $r['dniTrabajadorResponsable'] ?? '',
            'trabajadorResponsable'   => $r['trabajadorResponsable']    ?? '',
            'trabajadorAsignado'      => $r['trabajadorAsignado']       ?? '',
            'nombreAmbiente'          => $r['nombreAmbiente']           ?? '',
            'fechaAsignacion'         => fmtFecha($r['fechaAsignacion'] ?? null),
            'fechaLiberacion'         => fmtFecha($r['fechaLiberacion'] ?? null),
            'motivoCambio'            => $r['motivoCambio']             ?? '',
            'estado'                  => empty($r['fechaLiberacion']) ? 'activa' : 'liberada',
        ];
    }
    responder($result);
}

/* ── GET: asignación activa de una estación ── */
if (isset($_GET['asignacionActiva'])) {
    $idEst = intval($_GET['idEstacion'] ?? 0);
    $a = AsignacionController::ctrAsignacionActiva($idEst);
    if (!$a) responder(null);
    responder([
        'idAsignacion'            => intval($a['idAsignacion']),
        'idEstacion'              => intval($a['idEstacion']),
        'idAmbiente'              => $a['idAmbiente'] ? intval($a['idAmbiente']) : null,
        'dniTrabajadorResponsable'=> $a['dniTrabajadorResponsable'] ?? '',
        'trabajadorResponsable'   => $a['trabajadorResponsable']    ?? '',
        'trabajadorAsignado'      => $a['trabajadorAsignado']       ?? '',
        'fechaAsignacion'         => fmtFecha($a['fechaAsignacion'] ?? null, 'Y-m-d'),
        'observaciones'           => $a['observaciones']            ?? '',
    ]);
}

/* ── POST: crear asignación ── */
if (isset($_POST['nuevoIdEstacion'])) {
    responder(AsignacionController::ctrCrearAsignacion());
}

/* ── POST: liberar asignación ── */
if (isset($_POST['accion']) && $_POST['accion'] === 'liberar') {
    responder(AsignacionController::ctrLiberarAsignacion());
}
