<?php
session_start();
require_once __DIR__ . "/../controllers/EstacionController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) { echo json_encode($data); exit; }

/* ── Listar IPs disponibles ── */
if (isset($_GET["listarIps"])) {
    $idEstacion = intval($_GET["idEstacion"] ?? 0);
    responder(EstacionController::ctrListarIps($idEstacion));
}

/* ── Listar equipos por tipo ── */
if (isset($_GET["listarEquipos"])) {
    $tipo       = $_GET["tipo"]       ?? 'principal';
    $idEstacion = intval($_GET["idEstacion"] ?? 0);
    $excluir    = array_filter(array_map('intval', explode(',', $_GET["excluir"] ?? '')));

    $equipos = EstacionController::ctrListarEquiposTipo($tipo, $idEstacion, $excluir);
    $result  = [];

    foreach (($equipos ?: []) as $eq) {
        if (isset($eq['SQLSTATE'])) continue;

        $label = $tipo === 'software'
            ? ($eq["nombreActivo"] ?? 'Software') . (!empty($eq["numeroSerie"]) ? ' — ' . $eq["numeroSerie"] : '')
            : (!empty($eq["codigoPatrimonial"]) ? '[' . $eq["codigoPatrimonial"] . '] ' : '')
              . ($eq["nombreActivo"] ?? 'Equipo')
              . (!empty($eq["numeroSerie"]) ? ' — ' . $eq["numeroSerie"] : '');

        $result[] = [
            "idActivo"          => intval($eq["idActivo"] ?? 0),
            "label"             => $label,
            "numeroSerie"       => $eq["numeroSerie"]       ?? "",
            "codigoPatrimonial" => $eq["codigoPatrimonial"] ?? "",
            "nombreActivo"      => $eq["nombreActivo"]      ?? "",
            "iconoActivo"       => $eq["iconoActivo"]       ?? "ti-package",
        ];
    }
    responder($result);
}

/* ── Cargar datos para modal EDITAR (POST solo con idEstacion) ── */
if (isset($_POST["idEstacion"]) && count($_POST) === 1) {
    $idEst   = intval($_POST["idEstacion"]);
    $estacion = EstacionController::ctrMostrarEstacion('idEstacion', $idEst);
    if (!$estacion) { responder(["error" => "Estación no encontrada."]); }

    $grupos     = EstacionController::ctrEquiposDeEstacionAgrupados($idEst);
    $ipsActuales = EstacionController::ctrIpsDeEstacion($idEst);

    responder([
        "idEstacion"        => intval($estacion["idEstacion"]        ?? 0),
        "nombreEstacion"    => $estacion["nombreEstacion"]            ?? "",
        "codigoAnydesk"     => $estacion["codigoAnydesk"]            ?? "",
        "contrasenaAnydesk" => $estacion["contrasenaAnydesk"]        ?? "",
        "direccionFisica"   => $estacion["direccionFisica"]          ?? "",
        "idUsuarioRegistro" => $estacion["idUsuarioRegistro"]        ?? "",
        "fechaCreacion"     => $estacion["fechaCreacion"] instanceof DateTime
                                ? $estacion["fechaCreacion"]->format("d/m/Y H:i")
                                : ($estacion["fechaCreacion"] ?? ""),
        "idUsuarioModifica" => $estacion["idUsuarioModifica"]        ?? "",
        "fechaModificacion" => $estacion["fechaModificacion"] instanceof DateTime
                                ? $estacion["fechaModificacion"]->format("d/m/Y H:i")
                                : ($estacion["fechaModificacion"] ?? ""),
        "principal"         => $grupos["principal"]   ?? [],
        "perifericos"       => $grupos["perifericos"] ?? [],
        "software"          => $grupos["software"]    ?? [],
        "ipsActuales"       => $ipsActuales,
    ]);
}

/* ── Ver detalle ── */
if (isset($_POST["verDetalle"])) {
    responder(EstacionController::ctrVerDetalle(intval($_POST["verDetalle"])));
}

/* ══════════════════════════════════════════════════════
   ── Crear estación ── (con debug temporal)
══════════════════════════════════════════════════════ */
if (isset($_POST["nuevoNombreEstacion"])) {

    // ── DEBUG: capturar exactamente lo que llega por POST ──
    $debug = [
        'nuevoNombreEstacion'    => $_POST['nuevoNombreEstacion']    ?? '⚠ AUSENTE',
        'nuevoIpsIds'            => $_POST['nuevoIpsIds']            ?? '⚠ AUSENTE',
        'nuevoEquipoPrincipalId' => $_POST['nuevoEquipoPrincipalId'] ?? '⚠ AUSENTE',
        'nuevoPerifericosIds'    => $_POST['nuevoPerifericosIds']    ?? '⚠ AUSENTE',
        'nuevoSoftwareIds'       => $_POST['nuevoSoftwareIds']       ?? '⚠ AUSENTE',
        'nuevaDireccionFisica'   => $_POST['nuevaDireccionFisica']   ?? '⚠ AUSENTE',
        'nuevoCodigoAnydesk'     => $_POST['nuevoCodigoAnydesk']     ?? '⚠ AUSENTE',
    ];

    // Escribir en el log de PHP (ver en: storage/logs o error_log del servidor)
    error_log('[DEBUG CREAR ESTACION] POST = ' . json_encode($debug, JSON_UNESCAPED_UNICODE));

    $resultado = EstacionController::ctrCrearEstacion();

    // Adjuntar debug a la respuesta JSON → visible en consola del navegador
    $resultado['_debug'] = [
        'post_recibido' => $debug,
        'nota'          => 'ELIMINAR EN PRODUCCION',
    ];

    responder($resultado);
}

/* ══════════════════════════════════════════════════════
   ── Editar estación ── (con debug temporal)
══════════════════════════════════════════════════════ */
if (isset($_POST["editarNombreEstacion"])) {

    // ── DEBUG: capturar exactamente lo que llega por POST ──
    $debug = [
        'editarIdEstacion'        => $_POST['editarIdEstacion']        ?? '⚠ AUSENTE',
        'editarNombreEstacion'    => $_POST['editarNombreEstacion']    ?? '⚠ AUSENTE',
        'editarIpsIds'            => $_POST['editarIpsIds']            ?? '⚠ AUSENTE',
        'editarEquipoPrincipalId' => $_POST['editarEquipoPrincipalId'] ?? '⚠ AUSENTE',
        'editarPerifericosIds'    => $_POST['editarPerifericosIds']    ?? '⚠ AUSENTE',
        'editarSoftwareIds'       => $_POST['editarSoftwareIds']       ?? '⚠ AUSENTE',
        'editarDireccionFisica'   => $_POST['editarDireccionFisica']   ?? '⚠ AUSENTE',
        'editarCodigoAnydesk'     => $_POST['editarCodigoAnydesk']     ?? '⚠ AUSENTE',
    ];

    error_log('[DEBUG EDITAR ESTACION] POST = ' . json_encode($debug, JSON_UNESCAPED_UNICODE));

    $resultado = EstacionController::ctrEditarEstacion();

    $resultado['_debug'] = [
        'post_recibido' => $debug,
        'nota'          => 'ELIMINAR EN PRODUCCION',
    ];

    responder($resultado);
}

/* ── Eliminar estación (lógico) ── */
if (isset($_POST["eliminarIdEstacion"])) {
    responder(EstacionController::ctrEliminarEstacion());
}

/* ── Equipos disponibles para terminal ── */
if (isset($_GET['equiposDisponibles'])) {
    $rows   = EstacionController::ctrEquiposDisponibles();
    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'idActivo'          => $r['idActivo'],
            'label'             => $r['label'],
            'nombreActivo'      => $r['nombreActivo'],
            'codigoPatrimonial' => $r['codigoPatrimonial'],
            'numeroSerie'       => $r['numeroSerie'],
            'icono'             => $r['icono'],
        ];
    }
    responder($result);
}

/* ── Crear terminal ── */
if (isset($_POST['terminalNombre'])) {
    responder(EstacionController::ctrCrearTerminal());
}
