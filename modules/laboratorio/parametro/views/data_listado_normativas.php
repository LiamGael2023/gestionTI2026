<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../../../../config/db.php';
require_once '../../../../core/Auth.php';
require_once '../models/NormativaModel.php';

Auth::check();

try {
    $conn = Conexion::conectar();
    if (!$conn) {
        die(json_encode(['error' => 'Error de conexion']));
    }

    $normativa_model = new NormativaModel($conn);

    $draw = $_POST['draw'] ?? 1;
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $search = $_POST['search']['value'] ?? '';

    $sql_where = "WHERE Activo = 1";
    if (!empty($search)) {
        $search = str_replace("'", "''", $search);
        $sql_where .= " AND (Nombre LIKE '%$search%' OR Descripcion LIKE '%$search%')";
    }

    $sql_count = "SELECT COUNT(*) AS total FROM laboratorio.Normativa_Legal $sql_where";
    $stmt_count = sqlsrv_query($conn, $sql_count);
    $row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
    $total = $row_count['total'];

    $sql = "SELECT * FROM laboratorio.Normativa_Legal $sql_where ORDER BY Nombre ASC OFFSET $start ROWS FETCH NEXT $length ROWS ONLY";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die(json_encode(['error' => 'Error en consulta']));
    }

    $data = [];
    $contador = $start + 1;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $botones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-primary" onclick="editarNormativa(' . $row['Id_Normativa'] . ')" title="Editar">' .
                    '<i class="ti ti-pencil"></i></button>' .
                    '<button type="button" class="btn btn-ghost-danger" onclick="eliminarNormativa(' . $row['Id_Normativa'] . ')" title="Eliminar">' .
                    '<i class="ti ti-trash"></i></button>' .
                    '</div>';
        $data[] = [
            $contador++,
            $row['Nombre'],
            $row['Descripcion'] ?? '-',
            $botones
        ];
    }

    die(json_encode([
        'draw' => intval($draw),
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $data
    ]));

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
}
