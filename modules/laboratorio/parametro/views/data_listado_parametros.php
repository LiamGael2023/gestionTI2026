<?php
session_start();
require_once '../../../../config/db.php';
require_once '../../../../modules/laboratorio/models/LaboratorioModel.php';

$conn     = Conexion::conectar();
$labModel = new LaboratorioModel($conn);
$userId   = intval($_SESSION['usuario_id'] ?? 0);
$perms    = $labModel->obtenerPermisosSubmodulo($userId, '?module=laboratorio&action=parametro');
if ($perms === null) { $perms = ['editar' => true, 'eliminar' => true]; }
$puedeEditar   = (bool)($perms['editar']   ?? false);
$puedeEliminar = (bool)($perms['eliminar'] ?? false);

$columns = array(
    0 => 'p.Id_Parametro',
    1 => 'p.Nombre',
    2 => 'ISNULL(um.Abreviatura, p.Unidad_Medida)',
    3 => 'p.Categoria',
    4 => 'p.Metodo_Utilizado',
    5 => 'p.Posgre_Nombre',
    6 => 'p.Id_Parametro'
);

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

if ($colIndex < 0 || $colIndex >= count($columns)) {
    $colIndex = 0;
}

$sqlBase = "FROM laboratorio.Parametro_Analisis p 
            LEFT JOIN laboratorio.Servicio_Tecnico s ON p.Id_Servicio = s.Id_Servicio
            LEFT JOIN laboratorio.Unidad_Medida um ON p.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1";
$sqlWhere = " WHERE p.Activo = 1";
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (p.Nombre LIKE ? OR p.Categoria LIKE ? OR s.Nombre LIKE ? OR ISNULL(um.Abreviatura, p.Unidad_Medida) LIKE ?)";
    $params = array("%$search%", "%$search%", "%$search%", "%$search%");
}

$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM laboratorio.Parametro_Analisis WHERE Activo = 1");
$totalRecords = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'];

$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) as total " . $sqlBase . $sqlWhere, $params);
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados, SQLSRV_FETCH_ASSOC)['total'];

$sqlData = "SELECT p.*, s.Nombre as Servicio_Nombre, ISNULL(um.Abreviatura, p.Unidad_Medida) AS Unidad_Abreviatura " . $sqlBase . $sqlWhere .
           " ORDER BY " . $columns[$colIndex] . " " . $colDir .
           " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
if ($stmtData === false) {
    die(json_encode(['error' => 'Error en SQL', 'details' => sqlsrv_errors()]));
}

$data = array();
$contador = $start + 1;

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    $acciones = '<div class="btn-group btn-group-sm">' .
                ($puedeEditar   ? '<button class="btn btn-ghost-primary" onclick="editarParametro(' . $row['Id_Parametro'] . ')" title="Editar"><i class="ti ti-pencil"></i></button>' : '') .
                ($puedeEliminar ? '<button class="btn btn-ghost-danger" onclick="eliminarParametro(' . $row['Id_Parametro'] . ')" title="Eliminar"><i class="ti ti-trash"></i></button>' : '') .
                '</div>';
    
    $servicio = $row['Servicio_Nombre'] ? htmlspecialchars($row['Servicio_Nombre']) : '<span class="text-muted">Sin servicio</span>';
    
    $mapeo = ($row['Posgre_Tabla'] && $row['Posgre_Nombre']) 
             ? htmlspecialchars($row['Posgre_Tabla'] . '.' . $row['Posgre_Nombre'])
             : '<span class="text-muted">Ninguno</span>';
    
    $data[] = array(
        $contador++,
        htmlspecialchars($row['Nombre']),
        $servicio,
        htmlspecialchars($row['Unidad_Abreviatura'] ?: '-'),
        htmlspecialchars($row['Categoria'] ?: '-'),
        htmlspecialchars($row['Metodo_Utilizado'] ?: '-'),
        $mapeo,
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
?>
