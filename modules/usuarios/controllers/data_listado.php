<?php
session_start(); // Necesario para saber "quién soy yo"
require_once '../../../config/db.php'; 

// ... (Tu configuración de columnas y parámetros draw/start/length sigue igual) ...
$columns = array(0=>'nombres', 1=>'usuario', 2=>'rol', 3=>'documento', 4=>'activo', 5=>'id_usuario');
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$colIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$colDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

$conn = Conexion::conectar();
$sqlBase = " FROM comun.Usuarios ";
$sqlWhere = " WHERE activo = 1 "; 
$params = array();

if (!empty($search)) {
    $sqlWhere .= " AND (nombres LIKE ? OR apellidos LIKE ? OR usuario LIKE ? OR documento LIKE ?) ";
    $params = array("%$search%", "%$search%", "%$search%", "%$search%");
}

// Conteo Total Activos
$stmtTotal = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM comun.Usuarios WHERE activo = 1");
$totalRecords = sqlsrv_fetch_array($stmtTotal)['total'];

// Conteo Filtrado
$stmtFiltrados = sqlsrv_query($conn, "SELECT COUNT(*) as total " . $sqlBase . $sqlWhere, $params);
$totalFiltered = sqlsrv_fetch_array($stmtFiltrados)['total'];

// Datos
$sqlData = "SELECT * " . $sqlBase . $sqlWhere . " ORDER BY " . $columns[$colIndex] . " " . $colDir . " OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
array_push($params, $start, $length);

$stmtData = sqlsrv_query($conn, $sqlData, $params);
$data = array();

while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
    
    $estado = ($row['activo']) ? '<span class="badge bg-green-lt">Activo</span>' : '<span class="badge bg-red-lt">Inactivo</span>';
    
    // --- LÓGICA VISUAL DE PROTECCIÓN ---
    $usuario_actual = strtolower($row['usuario']); // Nombre en minúsculas
    $soy_yo = (isset($_SESSION['usuario_id']) && $row['id_usuario'] == $_SESSION['usuario_id']);
    
    // ¿Es superadmin O soy yo mismo?
    $es_intocable = ($usuario_actual === 'superadmin' || $soy_yo);

    // Botón Editar
    $btnEditar = '<button class="btn btn-sm btn-primary" onclick="editarUsuario('.$row['id_usuario'].')" title="Editar">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4" /><line x1="13.5" y1="6.5" x2="17.5" y2="10.5" /></svg>
    </button>';

    // Botón Eliminar (Condicionado)
    if ($es_intocable) {
        // Muestra un candado
        $btnEliminar = '<span class="text-muted ms-2" title="Usuario Protegido"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="5" y="11" width="14" height="10" rx="2" /><circle cx="12" cy="16" r="1" /><path d="M8 11v-4a4 4 0 0 1 8 0v4" /></svg></span>';
    } else {
        // Muestra el botón rojo normal
        $btnEliminar = '<button class="btn btn-sm btn-danger ms-1" onclick="eliminarUsuario('.$row['id_usuario'].')" title="Eliminar">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
        </button>';
    }
    
    $acciones = '<div class="btn-list flex-nowrap">' . $btnEditar . $btnEliminar . '</div>';
    
    $data[] = array(
        '<div class="font-weight-medium">'.$row['nombres'].' '.$row['apellidos'].'</div>',
        '<div>'.$row['usuario'].'</div><div class="small text-muted">'.$row['correo'].'</div>',
        '<span class="badge bg-blue-lt">'.$row['rol'].'</span>',
        $row['documento'],
        $estado,
        $acciones
    );
}

echo json_encode(array("draw"=>$draw, "recordsTotal"=>$totalRecords, "recordsFiltered"=>$totalFiltered, "data"=>$data));
?>