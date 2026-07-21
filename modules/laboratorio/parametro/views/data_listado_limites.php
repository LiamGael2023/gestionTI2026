<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../../../../config/db.php';
require_once '../../../../core/Auth.php';
require_once '../models/LimiteModel.php';

Auth::check();

try {
    $conn = Conexion::conectar();
    if (!$conn) {
        die(json_encode(['error' => 'Error de conexion']));
    }

    $limite_model = new LimiteModel($conn);

    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';

    $sql_where = "WHERE l.Activo = 1";
    if (!empty($search)) {
        $search = str_replace("'", "''", $search);
        $sql_where .= " AND (
            p.Nombre LIKE '%$search%'
            OR n.Nombre LIKE '%$search%'
            OR CAST(l.Valor_Max AS VARCHAR(50)) LIKE '%$search%'
            OR CAST(l.Valor_Min AS VARCHAR(50)) LIKE '%$search%'
            OR ISNULL(l.Unidad_Medida, '') LIKE '%$search%'
            OR ISNULL(l.Descripcion, '') LIKE '%$search%'
        )";
    }

    $sql_count_total = "SELECT COUNT(*) AS total
                        FROM laboratorio.Limite_Legal l
                        WHERE l.Activo = 1";
    $stmt_count_total = sqlsrv_query($conn, $sql_count_total);
    $row_count_total = $stmt_count_total ? sqlsrv_fetch_array($stmt_count_total, SQLSRV_FETCH_ASSOC) : ['total' => 0];
    $recordsTotal = intval($row_count_total['total'] ?? 0);

    $sql_count_filtered = "SELECT COUNT(*) AS total FROM laboratorio.Limite_Legal l
                           JOIN laboratorio.Parametro_Analisis p ON l.Id_Parametro = p.Id_Parametro
                           JOIN laboratorio.Normativa_Legal n ON l.Id_Normativa = n.Id_Normativa
                           $sql_where";
    $stmt_count_filtered = sqlsrv_query($conn, $sql_count_filtered);
    $row_count_filtered = $stmt_count_filtered ? sqlsrv_fetch_array($stmt_count_filtered, SQLSRV_FETCH_ASSOC) : ['total' => 0];
    $recordsFiltered = intval($row_count_filtered['total'] ?? 0);

    $sql = "SELECT
                l.Id_Limite_Legal,
                l.Valor_Max,
                l.Valor_Min,
                l.Unidad_Medida,
                l.Descripcion,
                p.Nombre AS Parametro_Nombre,
                n.Nombre AS Normativa_Nombre
            FROM laboratorio.Limite_Legal l
            JOIN laboratorio.Parametro_Analisis p ON l.Id_Parametro = p.Id_Parametro
            JOIN laboratorio.Normativa_Legal n ON l.Id_Normativa = n.Id_Normativa
            $sql_where
            ORDER BY p.Nombre ASC OFFSET $start ROWS FETCH NEXT $length ROWS ONLY";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die(json_encode(['error' => 'Error en consulta']));
    }

    $data = [];
    $contador = $start + 1;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $botones = '<div class="btn-group btn-group-sm" role="group">' .
                    '<button type="button" class="btn btn-ghost-primary" onclick="editarLimite(' . $row['Id_Limite_Legal'] . ')" title="Editar">' .
                    '<i class="ti ti-pencil"></i></button>' .
                    '<button type="button" class="btn btn-ghost-danger" onclick="eliminarLimite(' . $row['Id_Limite_Legal'] . ')" title="Eliminar">' .
                    '<i class="ti ti-trash"></i></button>' .
                    '</div>';
        $data[] = [
            $contador++,
            $row['Parametro_Nombre'],
            $row['Normativa_Nombre'],
            $row['Valor_Max'] ?? '-',
            $row['Valor_Min'] ?? '-',
            $row['Unidad_Medida'] ?? '-',
            $row['Descripcion'] ?? '-',
            $botones
        ];
    }

    die(json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]));

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
}
