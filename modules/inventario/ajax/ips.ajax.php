<?php
session_start();
require_once __DIR__ . "/../controllers/IpController.php";

header('Content-Type: application/json; charset=utf-8');

function responder($data) { echo json_encode($data); exit; }

function fmtFecha($fecha, $formato = "d/m/Y") {
    if (!$fecha) return "--";
    if ($fecha instanceof DateTime) return $fecha->format($formato);
    $ts = strtotime($fecha);
    return $ts ? date($formato, $ts) : "--";
}

/* ═══════════════════════════════════════════════════════════
   DATATABLES — SERVER-SIDE PROCESSING
   GET ?serverSide=1
   DataTables envía: draw, start, length, search[value],
                     order[0][column], order[0][dir]
═══════════════════════════════════════════════════════════ */
if (isset($_GET["serverSide"])) {

    $draw     = intval($_GET["draw"]                ?? 1);
    $start    = intval($_GET["start"]               ?? 0);
    $length   = intval($_GET["length"]              ?? 25);
    $search   = trim($_GET["search"]["value"]       ?? "");
    $orderCol = intval($_GET["order"][0]["column"]  ?? 0);
    $orderDir = strtoupper($_GET["order"][0]["dir"] ?? "ASC") === "DESC" ? "DESC" : "ASC";

    // Mapa índice-columna → campo SQL ordenable
    $colMap = [
        0 => "i.ipAddress",
        2 => "u.descripcion",
        3 => "i.estado",
        4 => "i.fechaCreacion",
    ];
    $orderField = $colMap[$orderCol] ?? "i.idIp";

    $result = IpController::ctrServerSide($start, $length, $search, $orderField, $orderDir);

    $rows = [];
    foreach ($result["rows"] as $ip) {

        $estado = $ip["estado"] ?? 'disponible';
        $estadoBadge = match($estado) {
            'asignada'  => '<span class="badge badge-asignada"><i class="ti ti-link me-1"></i>Asignada</span>',
            'reservada' => '<span class="badge badge-reservada"><i class="ti ti-lock me-1"></i>Reservada</span>',
            default     => '<span class="badge badge-disponible"><i class="ti ti-circle-check me-1"></i>Disponible</span>',
        };

        $rangoBadge = !empty($ip["cidrOrigen"])
            ? '<span class="badge badge-rango"><i class="ti ti-sitemap me-1"></i>'
              . htmlspecialchars($ip["cidrOrigen"]) . '</span>'
            : '<span class="badge badge-outline text-muted font-monospace">'
              . htmlspecialchars($ip["mascara"] ?? '') . '</span>';

        $rows[] = [
            "ip"       => '<div class="d-flex align-items-center gap-2">'
                        . '<i class="ti ti-network text-primary fs-3"></i>'
                        . '<span class="fw-medium font-monospace">' . htmlspecialchars($ip["ipAddress"] ?? '') . '</span>'
                        . '</div>',
            "rango"    => $rangoBadge,
            "ubicacion"=> '<span class="badge bg-primary-lt text-primary">'
                        . '<i class="ti ti-map-pin me-1"></i>'
                        . htmlspecialchars($ip["descripcionUbicacion"] ?? '') . '</span>',
            "estado"   => $estadoBadge,
            "fecha"    => '<span class="small text-muted">' . fmtFecha($ip["fechaCreacion"] ?? null) . '</span>',
            "usuario"  => '<span class="badge badge-outline text-muted fw-normal">ID: '
                        . htmlspecialchars($ip["idUsuarioRegistro"] ?? '') . '</span>',
            "acciones" => '<button class="btn btn-sm btn-icon btn-outline-primary btnEditarIp"'
                        . ' data-id="' . intval($ip["idIp"]) . '" title="Editar">'
                        . '<i class="ti ti-edit"></i></button>',
        ];
    }

    responder([
        "draw"            => $draw,
        "recordsTotal"    => intval($result["total"]),
        "recordsFiltered" => intval($result["filtered"]),
        "data"            => $rows,
    ]);
}

/* ═══════════════════════════════════════════════════════════
   VERIFICAR DUPLICADOS CIDR (preview)
═══════════════════════════════════════════════════════════ */
if (isset($_POST["verificarDuplicadosCidr"])) {
    $cidr = trim($_POST["cidr"] ?? '');
    if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $cidr)) {
        responder(["error" => "CIDR no válido.", "duplicados" => []]);
    }
    $range = IpController::cidrToRangePublic($cidr);
    if (!$range) responder(["error" => "Rango no válido.", "duplicados" => []]);
    if ($range['total'] > 1022) responder(["error" => "Rango demasiado grande.", "duplicados" => []]);
    responder(["duplicados" => IpController::ctrVerificarDuplicadosCidr($range['hosts'])]);
}

/* ═══════════════════════════════════════════════════════════
   CREAR IP INDIVIDUAL
═══════════════════════════════════════════════════════════ */
if (isset($_POST["nuevaIpAddress"])) {
    responder(IpController::ctrCrearIp());
}

/* ═══════════════════════════════════════════════════════════
   CREAR RANGO CIDR
═══════════════════════════════════════════════════════════ */
if (isset($_POST["nuevoCidr"])) {
    responder(IpController::ctrCrearRangoCidr());
}

/* ═══════════════════════════════════════════════════════════
   EDITAR IP
═══════════════════════════════════════════════════════════ */
if (isset($_POST["editarIpAddress"])) {
    responder(IpController::ctrEditarIp());
}

/* ═══════════════════════════════════════════════════════════
   OBTENER IP POR ID (modal editar)
═══════════════════════════════════════════════════════════ */
if (isset($_POST["idIp"])) {
    $ip = IpController::ctrMostrarIp("idIp", intval($_POST["idIp"]));
    if (!$ip) responder(["error" => "No se encontró la dirección IP."]);
    responder([
        "idIp"                 => intval($ip["idIp"]),
        "ipAddress"            => $ip["ipAddress"]            ?? "",
        "mascara"              => $ip["mascara"]              ?? "",
        "cidrOrigen"           => $ip["cidrOrigen"]           ?? "",
        "idUbicacion"          => intval($ip["idUbicacion"]),
        "descripcionUbicacion" => $ip["descripcionUbicacion"] ?? "",
        "estado"               => $ip["estado"]               ?? "disponible",
        "idUsuarioRegistro"    => $ip["idUsuarioRegistro"]    ?? "",
        "fechaCreacion"        => fmtFecha($ip["fechaCreacion"]     ?? null, "d/m/Y H:i:s"),
        "idUsuarioModifica"    => $ip["idUsuarioModifica"]    ?? "",
        "fechaModificacion"    => fmtFecha($ip["fechaModificacion"] ?? null, "d/m/Y H:i:s"),
    ]);
}
