<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../../../config/db.php';
require_once '../models/MuestraModel.php';

header('Content-Type: application/json; charset=utf-8');

function valorTexto($valor, $fallback = '-') {
    if ($valor instanceof DateTime) {
        return $valor->format('d-m-Y');
    }
    if ($valor === null) {
        return $fallback;
    }
    $texto = trim((string)$valor);
    return $texto === '' ? $fallback : $texto;
}

function tipoServicioUI($tipoServicio) {
    $valor = strtolower(trim((string)$tipoServicio));
    if ($valor === 'interno' || $valor === 'externo') {
        return ucfirst($valor);
    }
    return valorTexto($tipoServicio);
}

try {
    $conn = Conexion::conectar();
    if ($conn === false) {
        throw new Exception('No se pudo conectar a la BD');
    }

    $draw   = intval($_POST['draw']   ?? 0);
    $start  = intval($_POST['start']  ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $tipoServicio = strtolower(trim((string)($_POST['tipo_servicio'] ?? 'todos')));
    if ($tipoServicio !== 'interno' && $tipoServicio !== 'externo') {
        $tipoServicio = '';
    }
    $search = trim((string)($_POST['search']['value'] ?? ''));

    $sqlBase = " FROM laboratorio.Muestra_Lab m
                 INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
                 LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
                 LEFT JOIN laboratorio.Detalle_Agua   da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1";
    $sqlWhere = " WHERE m.Activo = 1 AND m.Estado = 'Rechazado'";
    $params = [];
    if ($tipoServicio !== '') {
        $sqlWhere .= " AND m.Tipo_Servicio = ?";
        $params[] = $tipoServicio;
    }

    // Total sin búsqueda
    $stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) AS total" . $sqlBase . $sqlWhere, $params);
    if ($stmtTotal === false) throw new Exception('Error count total: ' . print_r(sqlsrv_errors(), true));
    $rowTotal = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
    $total = intval($rowTotal['total'] ?? 0);

    // Filtro de búsqueda
    $sqlSearch = '';
    $paramsSearch = [];
    if ($search !== '') {
        $like = '%' . $search . '%';
        $sqlSearch = " AND (CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) LIKE ?
                        OR ISNULL(m.Valle, '') LIKE ?
                        OR CAST(m.Id_Muestra AS NVARCHAR) LIKE ?)";
        $paramsSearch = [$like, $like, $like];
    }

    $paramsFiltered = array_merge($params, $paramsSearch);

    // Total filtrado
    $stmtFiltered = sqlsrv_query($conn, "SELECT COUNT(*) AS total" . $sqlBase . $sqlWhere . $sqlSearch, $paramsFiltered);
    if ($stmtFiltered === false) throw new Exception('Error count filtered: ' . print_r(sqlsrv_errors(), true));
    $rowFiltered = sqlsrv_fetch_array($stmtFiltered, SQLSRV_FETCH_ASSOC);
    $totalFiltered = intval($rowFiltered['total'] ?? 0);

    $sql = "SELECT m.Id_Muestra, m.Id_Cliente,
                   CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                   m.Valle,
                   m.Tipo_Servicio,
                   m.Fecha_Analisis,
                   m.Fecha_Recepcion,
                   m.Observacion_Muestra,
                   CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                        WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                        ELSE 'Sin clasificar' END AS TipoMuestra";
    $sql .= $sqlBase . $sqlWhere . $sqlSearch;
    $sql .= " ORDER BY m.Fecha_Recepcion DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $paramsData = array_merge($paramsFiltered, [$start, $length]);

    $stmt = sqlsrv_query($conn, $sql, $paramsData);
    if ($stmt === false) {
        throw new Exception('Error en query: ' . print_r(sqlsrv_errors(), true));
    }

    $muestras = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $muestras[] = $row;
    }

    $data = [];
    foreach ($muestras as $row) {
        $id           = intval($row['Id_Muestra'] ?? 0);
        $agricultor   = valorTexto($row['Agricultor'] ?? null);
        $valle        = valorTexto($row['Valle'] ?? null);
        $servicio     = tipoServicioUI($row['Tipo_Servicio'] ?? null);
        $fechaAnalisis  = valorTexto($row['Fecha_Analisis'] ?? null);
        $fechaRechazo   = valorTexto($row['Fecha_Recepcion'] ?? null);
        // Mostrar solo el texto antes de [RECEPCION] en la columna
        $observacionRaw = $row['Observacion_Muestra'] ?? '';
        $posRecepcion = strpos((string)$observacionRaw, '[RECEPCION]');
        $motivoCorto = $posRecepcion !== false
            ? trim(substr((string)$observacionRaw, 0, $posRecepcion))
            : (string)$observacionRaw;
        $motivo = valorTexto($motivoCorto !== '' ? $motivoCorto : null);
        $tipo         = valorTexto($row['TipoMuestra'] ?? null);
        $accion = '<button class="btn btn-sm btn-info" onclick="verDetallesRechazada(' . $id . ')" title="Ver detalle"><i class="ti ti-eye"></i></button>';

        $data[] = [$id, $agricultor, $valle, $servicio, $fechaAnalisis, $fechaRechazo, $motivo, $tipo, $accion];
    }

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $totalFiltered,
        'data'            => $data
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'draw'            => intval($_POST['draw'] ?? 0),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'error'           => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
}
?>
