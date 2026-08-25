<?php
session_start();
require_once '../../../../config/db.php';
require_once '../../../../modules/laboratorio/models/LaboratorioModel.php';

$conn     = Conexion::conectar();
$labModel = new LaboratorioModel($conn);
$userId   = intval($_SESSION['usuario_id'] ?? 0);
$perms    = $labModel->obtenerPermisosSubmodulo($userId, '?module=laboratorio&action=servicio');
if ($perms === null) { $perms = ['editar' => true, 'eliminar' => true]; }
$puedeEditar   = (bool)($perms['editar']   ?? false);
$puedeEliminar = (bool)($perms['eliminar'] ?? false);

// Configuración de columnas
$columns = array(
    0 => 'st.Id_Servicio',
    1 => 'st.Nombre',
    2 => 'st.Tipo_Muestra',
    3 => 'st.Descripcion',
    4 => 'st.Id_Servicio',
    5 => 'st.Id_Servicio',
    6 => 'st.Id_Servicio'
);

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Base SQL
$sqlBase = " FROM laboratorio.Servicio_Tecnico st ";
$sqlWhere = " WHERE 1=1 ";
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (st.Nombre LIKE ? OR st.Descripcion LIKE ?) ";
    $params = array("%$search%", "%$search%");
}

// Conteo Total
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM laboratorio.Servicio_Tecnico");
if ($stmtTotal === false) {
    die(json_encode(['error' => 'Error en consulta total']));
}
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

// Conteo Filtrado
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) as total " . $sqlBase . $sqlWhere, $params);
if ($stmtFiltrados === false) {
    die(json_encode(['error' => 'Error en consulta filtrada']));
}
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

// Datos con paginación
$sqlData = "SELECT st.*, (
                SELECT
                    CASE
                        WHEN COUNT(1) = 0 THEN NULL
                        ELSE FLOOR(
                            MIN(
                                CASE
                                    WHEN CAST(ISNULL(rs.Cantidad_Necesaria, 0) AS DECIMAL(18,6)) > 0 THEN
                                        (
                                            CASE
                                                WHEN CAST(ISNULL(rl.Cantidad_Stock, 0) AS DECIMAL(18,6)) - CAST(ISNULL(rl.Cantidad_Reservada, 0) AS DECIMAL(18,6)) > 0
                                                    THEN CAST(ISNULL(rl.Cantidad_Stock, 0) AS DECIMAL(18,6)) - CAST(ISNULL(rl.Cantidad_Reservada, 0) AS DECIMAL(18,6))
                                                ELSE 0
                                            END
                                        ) / CAST(rs.Cantidad_Necesaria AS DECIMAL(18,6))
                                    ELSE NULL
                                END
                            )
                        )
                    END
                FROM laboratorio.Receta_Servicio rs
                LEFT JOIN laboratorio.Reactivo_Lab rl
                    ON rl.Id_Reactivo = rs.Id_Reactivo
                   AND rl.Activo = 1
                WHERE rs.Id_Servicio = st.Id_Servicio
                  AND rs.Activo = 1
            ) AS Capacidad_Lab " . $sqlBase . $sqlWhere . 
           " ORDER BY st.Activo DESC, " . $columns[$colIndex] . " " . $colDir . 
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en consulta de datos']));
}

$data = array();
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    // Verificar estado de equipos bloqueantes
    $sqlCheck = "SELECT COUNT(*) as bloqueados FROM laboratorio.Requisito_Equipo re
                 JOIN laboratorio.Equipo_Lab el ON re.Id_Equipo = el.Id_Equipo
                 JOIN laboratorio.Equipo_Estado ee ON el.Id_Estado = ee.Id_Estado
                 WHERE re.Id_Servicio = ? AND re.Es_Bloqueante = 1 AND ee.Nombre <> 'Disponible' AND re.Activo = 1";
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, array($row['Id_Servicio']));
    $checkRow = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    $tieneEquipoBloqueadoNoDisponible = $checkRow['bloqueados'] > 0;

    // Badges por estado
    if ($row['Activo'] == 0) {
        $estadoBadge = '<span class="badge bg-danger">Inactivo</span>';
    } elseif ($tieneEquipoBloqueadoNoDisponible) {
        $estadoBadge = '<span class="badge bg-warning">Equipo No Operativo</span>';
    } else {
        $estadoBadge = '<span class="badge bg-success">Disponible</span>';
    }

    // Botones de acción - Diferentes según si está activo o no
    if ($row['Activo'] == 1) {
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    ($puedeEditar   ? '<button type="button" class="btn btn-ghost-primary" onclick="editarServicio(' . $row['Id_Servicio'] . ')" title="Editar"><i class="ti ti-pencil"></i></button>' : '') .
                    ($puedeEliminar ? '<button type="button" class="btn btn-ghost-danger" onclick="eliminarServicio(' . $row['Id_Servicio'] . ')" title="Eliminar"><i class="ti ti-trash"></i></button>' : '') .
                    '</div>';
    } else {
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    ($puedeEditar ? '<button type="button" class="btn btn-ghost-success" onclick="reactivarServicio(' . $row['Id_Servicio'] . ')" title="Reactivar"><i class="ti ti-check"></i></button>' : '<span class="text-muted small">Inactivo</span>') .
                    '</div>';
    }

    if ($row['Capacidad_Lab'] === null) {
        $capacidadLab = '<span class="text-muted">Sin receta</span>';
    } else {
        $capacidadLab = '<span class="badge bg-blue-lt text-blue">' . number_format((float)$row['Capacidad_Lab'], 0, '.', ',') . '</span>';
    }

    $data[] = array(
        $contador++,
        htmlspecialchars($row['Nombre']),
        htmlspecialchars($row['Tipo_Muestra']),
        htmlspecialchars($row['Descripcion'] ?: '-'),
        $capacidadLab,
        $estadoBadge,
        $acciones
    );
}

$json_data = array(
    "draw" => $draw,
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
);

echo json_encode($json_data);
