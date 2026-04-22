<?php
session_start();
require_once '../../../../config/db.php';

$conn = Conexion::conectar();

// Configuración de columnas
$columns = array(
    0 => 'pv.Id_Producto',
    1 => 'pv.Nombre_Comercial',
    2 => 'pv.Tipo',
    3 => 'pv.Tipo_Vista',
    4 => 'pv.Descripcion',
    5 => 'pv.Precio_Venta',
    6 => 'pv.Id_Producto',
    7 => 'pv.Id_Producto',
    8 => 'pv.Id_Producto'
);

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Base SQL
$sqlBase = " FROM laboratorio.Producto_Venta pv ";
$sqlWhere = " WHERE 1=1 ";
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (pv.Nombre_Comercial LIKE ? OR pv.Descripcion LIKE ? OR pv.Tipo_Vista LIKE ?) ";
    $params = array("%$search%", "%$search%", "%$search%");
}

// Conteo Total
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM laboratorio.Producto_Venta");
if ($stmtTotal === false) {
    $errors = sqlsrv_errors();
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error en total: ' . ($errors[0]['message'] ?? 'Error desconocido')
    ]);
    exit;
}
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

// Conteo Filtrado
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) as total " . $sqlBase . $sqlWhere, $params);
if ($stmtFiltrados === false) {
    $errors = sqlsrv_errors();
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($totalRecords),
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error en filtrado: ' . ($errors[0]['message'] ?? 'Error desconocido')
    ]);
    exit;
}
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

// Datos con paginación
$sqlData = "SELECT pv.*, (
                SELECT
                    CASE
                        WHEN COUNT(1) = 0 THEN NULL
                        ELSE FLOOR(
                            MIN(
                                CASE
                                    WHEN req.Cantidad_Requerida > 0 THEN
                                        (
                                            CASE
                                                WHEN CAST(ISNULL(rl.Cantidad_Stock, 0) AS DECIMAL(18,6)) - CAST(ISNULL(rl.Cantidad_Reservada, 0) AS DECIMAL(18,6)) > 0
                                                    THEN CAST(ISNULL(rl.Cantidad_Stock, 0) AS DECIMAL(18,6)) - CAST(ISNULL(rl.Cantidad_Reservada, 0) AS DECIMAL(18,6))
                                                ELSE 0
                                            END
                                        ) / req.Cantidad_Requerida
                                    ELSE NULL
                                END
                            )
                        )
                    END
                FROM (
                    SELECT
                        t.Id_Reactivo,
                        SUM(t.Cantidad_Por_Servicio) AS Cantidad_Requerida
                    FROM (
                        SELECT
                            ps.Id_Servicio,
                            rs.Id_Reactivo,
                            MAX(CAST(ISNULL(rs.Cantidad_Necesaria, 0) AS DECIMAL(18,6))) AS Cantidad_Por_Servicio
                        FROM laboratorio.Producto_Servicio ps
                        INNER JOIN laboratorio.Receta_Servicio rs
                            ON rs.Id_Servicio = ps.Id_Servicio
                           AND rs.Activo = 1
                        WHERE ps.Id_Producto = pv.Id_Producto
                          AND ps.Activo = 1
                        GROUP BY ps.Id_Servicio, rs.Id_Reactivo
                    ) t
                    GROUP BY t.Id_Reactivo
                ) req
                LEFT JOIN laboratorio.Reactivo_Lab rl
                    ON rl.Id_Reactivo = req.Id_Reactivo
                   AND rl.Activo = 1
            ) AS Capacidad_Lab " . $sqlBase . $sqlWhere . 
           " ORDER BY pv.Activo DESC, " . $columns[$colIndex] . " " . $colDir . 
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    $errors = sqlsrv_errors();
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($totalRecords),
        'recordsFiltered' => intval($totalFiltered),
        'data' => [],
        'error' => 'Error en datos: ' . ($errors[0]['message'] ?? 'Error desconocido')
    ]);
    exit;
}

$data = array();
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    // Verificar si todos los servicios incluidos están disponibles
    $sqlCheck = "SELECT COUNT(DISTINCT ps.Id_Servicio) as noDisponibles 
                 FROM laboratorio.Producto_Servicio ps
                 JOIN laboratorio.Servicio_Tecnico st ON ps.Id_Servicio = st.Id_Servicio
                 WHERE ps.Id_Producto = ? AND ps.Activo = 1
                 AND st.Activo = 0";
    
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, array($row['Id_Producto']));
    $tieneServicioInactivo = false;
    $tieneEquipoNoDisponible = false;
    
    if ($stmtCheck !== false) {
        $checkRow = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        $tieneServicioInactivo = $checkRow['noDisponibles'] > 0;
        
        // Si no hay servicios inactivos, verificar equipos bloqueantes
        if (!$tieneServicioInactivo) {
            $sqlCheckEquipo = "SELECT COUNT(DISTINCT re.Id_Requisito_Equipo) as equiposNoDisponibles
                               FROM laboratorio.Producto_Servicio ps
                               JOIN laboratorio.Requisito_Equipo re ON ps.Id_Servicio = re.Id_Servicio
                               JOIN laboratorio.Equipo_Lab el ON re.Id_Equipo = el.Id_Equipo
                               JOIN laboratorio.Equipo_Estado ee ON el.Id_Estado = ee.Id_Estado
                               WHERE ps.Id_Producto = ? AND ps.Activo = 1 AND re.Activo = 1
                               AND re.Es_Bloqueante = 1 AND ee.Nombre <> 'Disponible'";
            
            $stmtCheckEquipo = sqlsrv_query($conn, $sqlCheckEquipo, array($row['Id_Producto']));
            if ($stmtCheckEquipo !== false) {
                $checkRowEquipo = sqlsrv_fetch_array($stmtCheckEquipo, SQLSRV_FETCH_ASSOC);
                $tieneEquipoNoDisponible = $checkRowEquipo['equiposNoDisponibles'] > 0;
            }
        }
    }

    // Badges por estado
    if ($row['Activo'] == 0) {
        $estadoBadge = '<span class="badge bg-danger">Inactivo</span>';
    } elseif ($tieneServicioInactivo || $tieneEquipoNoDisponible) {
        $estadoBadge = '<span class="badge bg-danger">Indisponible</span>';
    } else {
        $estadoBadge = '<span class="badge bg-success">Disponible</span>';
    }

    if ($row['Capacidad_Lab'] === null) {
        $capacidadLab = '<span class="text-muted">Sin receta</span>';
    } else {
        $capacidadLab = '<span class="badge bg-blue-lt text-blue">' . number_format((float)$row['Capacidad_Lab'], 0, '.', ',') . '</span>';
    }

    // Botones de acción - Diferentes según si está activo o no
    if ($row['Activo'] == 1) {
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-primary" onclick="editarVenta(' . $row['Id_Producto'] . ')" title="Editar">' .
                    '<i class="ti ti-pencil"></i></button>' .
                    '<button type="button" class="btn btn-ghost-danger" onclick="eliminarVenta(' . $row['Id_Producto'] . ')" title="Eliminar">' .
                    '<i class="ti ti-trash"></i></button>' .
                    '</div>';
    } else {
        $acciones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-success" onclick="reactivarVenta(' . $row['Id_Producto'] . ')" title="Reactivar">' .
                    '<i class="ti ti-check"></i></button>' .
                    '</div>';
    }

    $data[] = array(
        $contador++,
        htmlspecialchars($row['Nombre_Comercial']),
        htmlspecialchars($row['Tipo']),
        htmlspecialchars($row['Tipo_Vista'] ?? 'GENERAL'),
        htmlspecialchars($row['Descripcion'] ?: '-'),
        'S/. ' . number_format($row['Precio_Venta'], 2, '.', ','),
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
