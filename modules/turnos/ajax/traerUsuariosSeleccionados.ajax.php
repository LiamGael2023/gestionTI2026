<?php
require_once __DIR__ . '/../modelo/conexion.php';

$componente = $_POST['componente'];
$meta = $_POST['meta'];
$tipotrabajador = $_POST['tipotrabajador'];
$anio = $_POST['anio'];

$conn = Conexion::conectar();

$sql = "EXEC Asistencia.pa_ListarTrabajadores_Seleccionados
    @Id_Componente = ?, 
    @Id_Meta = ?, 
    @Id_Trabajador_Tipo = ?, 
    @anio = ?";

$params = [$componente, $meta, $tipotrabajador, $anio];

$stmt = sqlsrv_query($conn, $sql, $params);

$ids = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $ids[] = $row['Id_Trabajador'];
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($ids);
?>