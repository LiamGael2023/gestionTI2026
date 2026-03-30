<?php
session_start();
require_once __DIR__ . "/../controllers/ReporteAsignacionController.php";

header('Content-Type: application/json; charset=utf-8');
function responder($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

/* ── Obtener opciones de filtros ── */
if (isset($_GET['filtros'])) {
    responder(ReporteAsignacionController::ctrObtenerFiltros());
}

/* ── Obtener jefes para filtro jerárquico ── */
if (isset($_GET['jefes'])) {
    responder(ReporteAsignacionController::ctrObtenerJefes());
}

/* ── Generar reporte con filtros ── */
if (isset($_POST['generarReporte'])) {
    responder(ReporteAsignacionController::ctrGenerarReporte($_POST));
}

/* ── Detalle de activos de un trabajador + tipo (con hijos) ── */
if (isset($_POST['detalleDni'])) {
    $dni      = trim($_POST['detalleDni']   ?? '');
    $idTipo   = intval($_POST['detalleIdTipo'] ?? 0);
    if (!$dni || !$idTipo) responder(['error' => 'Parámetros inválidos']);
    responder(ReporteAsignacionController::ctrDetalleActivos($dni, $idTipo));
}

/* ── Buscar por código patrimonial ── */
if (isset($_GET['buscarCodigo'])) {
    $codigo = trim($_GET['buscarCodigo'] ?? '');
    if (strlen($codigo) < 2) responder(['error' => 'Ingresa al menos 2 caracteres.']);
    responder(ReporteAsignacionController::ctrBuscarPorCodigoPatrimonial($codigo));
}
