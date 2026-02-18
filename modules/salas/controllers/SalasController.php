<?php
require_once 'config/db.php';

// Conectar BD
$conn = Conexion::conectar();

// CORRECCIÓN AQUÍ: Cambiamos 'comun.Salas' por 'salas.Salas'
$sql = "SELECT * FROM salas.Salas";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Cargar la vista y pasarle los datos
include 'modules/salas/views/index.php';
?>