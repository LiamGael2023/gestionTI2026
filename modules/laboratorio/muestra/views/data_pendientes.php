<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../../../core/Auth.php';
require_once '../../../../config/db.php';
Auth::check();
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
    
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $tipoServicio = strtolower(trim((string)($_POST['tipo_servicio'] ?? 'todos')));
    $filtrarTipo = ($tipoServicio === 'interno' || $tipoServicio === 'externo');
    $search = trim((string)($_POST['search']['value'] ?? ''));

    $sqlBase = " FROM laboratorio.Muestra_Lab m
                 INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
                 LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
                 LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1";
    $sqlWhere = " WHERE m.Activo = 1 AND m.Id_Proyecto IS NULL AND m.Estado = 'Recepcionado'
                  AND NOT EXISTS (
                      SELECT 1 FROM laboratorio.Muestra_Bitacora mbx
                      WHERE mbx.Id_Muestra = m.Id_Muestra AND mbx.Muestra_Original IS NOT NULL
                  )";
    $paramsBase = [];
    if ($filtrarTipo) {
        $sqlWhere .= " AND LOWER(ISNULL(m.Tipo_Servicio, '')) = ?";
        $paramsBase[] = $tipoServicio;
    }

    // Total sin filtro de bÃºsqueda
    $stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) AS total" . $sqlBase . $sqlWhere, $paramsBase);
    if ($stmtTotal === false) {
        throw new Exception('No se pudo contar muestras pendientes: ' . print_r(sqlsrv_errors(), true));
    }
    $rowTotal = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
    $total = intval($rowTotal['total'] ?? 0);

    // Filtro de bÃºsqueda adicional
    $sqlSearch = '';
    $paramsSearch = [];
    if ($search !== '') {
        $like = '%' . $search . '%';
        $sqlSearch = " AND (CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) LIKE ?
                        OR ISNULL(m.Valle, '') LIKE ?
                        OR CAST(m.Id_Muestra AS NVARCHAR) LIKE ?)";
        $paramsSearch = [$like, $like, $like];
    }

    // Total filtrado (con bÃºsqueda)
    $paramsFiltered = array_merge($paramsBase, $paramsSearch);
    $stmtFiltered = sqlsrv_query($conn, "SELECT COUNT(*) AS total" . $sqlBase . $sqlWhere . $sqlSearch, $paramsFiltered);
    if ($stmtFiltered === false) {
        throw new Exception('No se pudo contar muestras filtradas: ' . print_r(sqlsrv_errors(), true));
    }
    $rowFiltered = sqlsrv_fetch_array($stmtFiltered, SQLSRV_FETCH_ASSOC);
    $totalFiltered = intval($rowFiltered['total'] ?? 0);

    $sqlData = "SELECT m.*,
                       CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                       CONCAT(m.Eje_X, ', ', m.Eje_Y) AS Ubicacion,
                       CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                            WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                            ELSE 'Sin clasificar' END AS TipoMuestra";
    $sqlData .= $sqlBase . $sqlWhere . $sqlSearch;
    $sqlData .= " ORDER BY m.Id_Muestra DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $paramsData = array_merge($paramsFiltered, [$start, $length]);
    $stmtData = sqlsrv_query($conn, $sqlData, $paramsData);
    if ($stmtData === false) {
        throw new Exception('No se pudo cargar muestras pendientes: ' . print_r(sqlsrv_errors(), true));
    }

    $muestras = [];
    while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
        $muestras[] = $row;
    }

    $data = [];
    foreach ($muestras as $row) {
        $id = isset($row['Id_Muestra']) ? intval($row['Id_Muestra']) : 0;
        $agricultor = valorTexto($row['Agricultor'] ?? null);
        $ubicacion = valorTexto($row['Ubicacion'] ?? null);
        $valle = valorTexto($row['Valle'] ?? null);
        $fecha = valorTexto($row['Fecha_Recepcion'] ?? null);
        $servicio = tipoServicioUI($row['Tipo_Servicio'] ?? null);
        $estado = valorTexto($row['Estado'] ?? null);
        $tipo = valorTexto($row['TipoMuestra'] ?? null);
        
        $idCliente = intval($row['Id_Cliente'] ?? 0);
        $agricultorEsc = htmlspecialchars($agricultor, ENT_QUOTES, 'UTF-8');
        $accion = '<button class="btn btn-sm btn-success" onclick="abrirModalComenzarAnalisis(' . $id . ', ' . $idCliente . ', \'' . $agricultorEsc . '\')">'
            . '<i class="ti ti-player-play"></i> Comenzar anÃ¡lisis</button>';
        
        $data[] = [$id, $agricultor, $ubicacion, $valle, $fecha, $servicio, $estado, $tipo, $accion];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $totalFiltered,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
}
?>

