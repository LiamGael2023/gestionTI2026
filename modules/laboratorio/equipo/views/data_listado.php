<?php
session_start();
require_once '../../../../config/db.php';
require_once '../../../../modules/laboratorio/models/LaboratorioModel.php';

$conn     = Conexion::conectar();
$labModel = new LaboratorioModel($conn);
$userId   = intval($_SESSION['usuario_id'] ?? 0);
$perms    = $labModel->obtenerPermisosSubmodulo($userId, '?module=laboratorio&action=equipo');
// Admins o usuarios sin restricción explícita → todos los permisos
if ($perms === null) {
    $perms = ['editar' => true, 'eliminar' => true];
}
$puedeEditar   = (bool)($perms['editar']   ?? false);
$puedeEliminar = (bool)($perms['eliminar'] ?? false);

// Configuración de columnas
$columns = [
    0 => 'el.Id_Equipo',
    1 => 'el.Nombre',
    2 => 'COALESCE(p.Razon_Social, el.Proveedor)',
    3 => 'el.Fecha_Adquisicion',
    4 => 'el.Fecha_Proxima_Calibracion',
    5 => 'ee.Nombre',
    6 => 'el.Id_Equipo',
    7 => 'el.Id_Equipo'   // columna oculta: clase CSS de fila
];

$draw     = isset($_POST['draw'])               ? intval($_POST['draw'])               : 0;
$start    = isset($_POST['start'])              ? intval($_POST['start'])              : 0;
$length   = isset($_POST['length'])             ? intval($_POST['length'])             : 10;
$search   = isset($_POST['search']['value'])    ? $_POST['search']['value']            : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir   = (isset($_POST['order'][0]['dir']) && $_POST['order'][0]['dir'] === 'desc') ? 'desc' : 'asc';

if ($colIndex < 0 || $colIndex >= count($columns)) {
    $colIndex = 0;
}

// Base SQL
$sqlBase  = " FROM laboratorio.Equipo_Lab el
              LEFT JOIN laboratorio.Equipo_Estado ee ON el.Id_Estado = ee.Id_Estado
              LEFT JOIN laboratorio.Proveedor p      ON el.Id_Proveedor = p.Id_Proveedor ";
$sqlWhere = " WHERE 1=1 ";
$params   = [];

if (!empty($search)) {
    $sqlWhere .= " AND (el.Nombre LIKE ? OR COALESCE(p.Razon_Social, el.Proveedor) LIKE ? OR ee.Nombre LIKE ?) ";
    $params    = ["%$search%", "%$search%", "%$search%"];
}

// Conteo Total
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM laboratorio.Equipo_Lab");
if ($stmtTotal === false) {
    die(json_encode(['error' => 'Error en consulta total']));
}
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

// Conteo Filtrado
$paramsFilter  = $params;
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) AS total " . $sqlBase . $sqlWhere, $paramsFilter);
if ($stmtFiltrados === false) {
    die(json_encode(['error' => 'Error en consulta filtrada']));
}
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

// Datos con paginación
$sqlData = "SELECT el.*, ee.Nombre AS Estado_Nombre,
                   COALESCE(p.Razon_Social, el.Proveedor) AS Proveedor_Display"
         . $sqlBase . $sqlWhere
         . " ORDER BY el.Activo DESC, " . $columns[$colIndex] . " " . $colDir
         . " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en consulta de datos', 'details' => sqlsrv_errors()]));
}

$data     = [];
$contador = $start + 1;
$hoy      = new DateTime();

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    $estado      = $row['Estado_Nombre'] ?: 'Sin estado';
    $estadoLower = strtolower($estado);

    // ---- BADGE DE ESTADO ----
    if ($row['Activo'] == 0) {
        $estadoBadge = '<span class="badge bg-danger-lt text-danger">Inactivo</span>';
    } else {
        $coloresBadge = [
            'disponible' => 'bg-success-lt text-success',
            'correctivo' => 'bg-orange-lt text-orange',
            'preventivo' => 'bg-yellow-lt text-yellow',
            'predictivo' => 'bg-blue-lt text-blue',
        ];
        $badgeClass  = $coloresBadge[$estadoLower] ?? 'bg-secondary-lt text-secondary';
        $estadoBadge = '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($estado) . '</span>';
    }

    // ---- CLASE DE FILA (columna 7, oculta) ----
    if ($row['Activo'] == 0) {
        $rowClass = 'row-eq-inactivo';
    } elseif ($estadoLower === 'disponible') {
        $rowClass = '';
    } else {
        $mapaClase = [
            'correctivo' => 'row-eq-correctivo',
            'preventivo' => 'row-eq-preventivo',
            'predictivo' => 'row-eq-predictivo',
        ];
        $rowClass = $mapaClase[$estadoLower] ?? 'row-eq-otro';
    }

    // ---- PRÓXIMA CALIBRACIÓN (con alerta visual si se acerca) ----
    $proximaHtml = '-';
    if ($row['Fecha_Proxima_Calibracion'] instanceof DateTime) {
        $proximaDate   = $row['Fecha_Proxima_Calibracion'];
        $diff          = $hoy->diff($proximaDate);
        $diasRestantes = $diff->invert ? -$diff->days : $diff->days;
        $proximaStr    = $proximaDate->format('d/m/Y');

        if ($diasRestantes >= 0 && $diasRestantes <= 30) {
            $alerta      = $diasRestantes === 0 ? '¡Hoy es la fecha de calibración!' : "Faltan {$diasRestantes} día(s)";
            $proximaHtml = '<span class="badge-cal-proxima px-2 py-1 rounded" title="' . htmlspecialchars($alerta) . '" '
                         . 'style="background:#ede9fe;color:#6d28d9;font-weight:600;">'
                         . $proximaStr . ' <i class="ti ti-alert-circle" style="font-size:11px"></i></span>';
        } elseif ($diasRestantes < 0) {
            $proximaHtml = '<span class="px-2 py-1 rounded" title="Calibración vencida hace ' . abs($diasRestantes) . ' día(s)" '
                         . 'style="background:#fee2e2;color:#dc2626;font-weight:600;">'
                         . $proximaStr . ' <i class="ti ti-alert-triangle" style="font-size:11px"></i></span>';
        } else {
            $proximaHtml = $proximaStr;
        }
    }

    // ---- ANTIGÜEDAD (Fecha_Adquisicion) ----
    $antiguedad = '-';
    if ($row['Fecha_Adquisicion'] instanceof DateTime) {
        $diffAdq = $row['Fecha_Adquisicion']->diff($hoy);
        $anios   = $diffAdq->y;
        $meses   = $diffAdq->m;
        if ($anios > 0 && $meses > 0) {
            $antiguedad = $anios . ' año' . ($anios != 1 ? 's' : '') . ' y ' . $meses . ' mes' . ($meses != 1 ? 'es' : '');
        } elseif ($anios > 0) {
            $antiguedad = $anios . ' año' . ($anios != 1 ? 's' : '');
        } elseif ($meses > 0) {
            $antiguedad = $meses . ' mes' . ($meses != 1 ? 'es' : '');
        } else {
            $antiguedad = '<span class="text-muted">Reciente</span>';
        }
    }

    // ---- BOTONES DE ACCIÓN ----
    $idEq = $row['Id_Equipo'];
    if ($row['Activo'] == 1) {
        $btnEditar   = $puedeEditar
            ? '<button type="button" class="btn btn-ghost-primary btn-sm" onclick="editarEquipo(' . $idEq . ')" title="Editar"><i class="ti ti-pencil"></i></button>'
            : '';
        $btnEliminar = $puedeEliminar
            ? '<button type="button" class="btn btn-ghost-danger btn-sm" onclick="eliminarEquipo(' . $idEq . ')" title="Eliminar"><i class="ti ti-trash"></i></button>'
            : '';

        $nombreJson = htmlspecialchars(json_encode($row['Nombre']), ENT_QUOTES);
        if ($estadoLower === 'disponible') {
            $btnCalib = $puedeEditar
                ? '<button type="button" class="btn btn-ghost-warning btn-sm" onclick="iniciarCalibracion(' . $idEq . ', ' . $nombreJson . ')" title="Iniciar Calibración"><i class="ti ti-tool"></i></button>'
                : '';
        } else {
            $btnCalib = $puedeEditar
                ? '<button type="button" class="btn btn-ghost-teal btn-sm" onclick="finalizarCalibracion(' . $idEq . ', ' . $nombreJson . ')" title="Finalizar Calibración y marcar Disponible"><i class="ti ti-checks"></i></button>'
                : '';
        }
        $btnHistorial = '<button type="button" class="btn btn-ghost-blue btn-sm" onclick="verHistorial(' . $idEq . ', ' . $nombreJson . ')" title="Ver historial de calibraciones"><i class="ti ti-history"></i></button>';

        $acciones = '<div class="btn-group btn-group-sm" role="group">' . $btnEditar . $btnCalib . $btnHistorial . $btnEliminar . '</div>';
    } else {
        $reactivarBtn = $puedeEditar
            ? '<button type="button" class="btn btn-ghost-success btn-sm" onclick="reactivarEquipo(' . $idEq . ')" title="Reactivar"><i class="ti ti-refresh"></i></button>'
            : '<span class="text-muted small">Inactivo</span>';
        $acciones = '<div class="btn-group btn-group-sm" role="group">' . $reactivarBtn . '</div>';
    }

    $data[] = [
        $contador++,
        htmlspecialchars($row['Nombre']),
        htmlspecialchars($row['Proveedor_Display'] ?: '-'),
        $antiguedad,
        $proximaHtml,
        $estadoBadge,
        $acciones,
        $rowClass    // columna 7 — oculta, usada por createdRow en JS
    ];
}

echo json_encode([
    "draw"            => $draw,
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
]);

