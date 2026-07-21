<?php
require 'config/db.php';
$conn = Conexion::conectar();
if ($conn) {
    sqlsrv_query($conn, "IF COL_LENGTH('laboratorio.Muestra_Lab', 'Es_Pozo') IS NULL ALTER TABLE laboratorio.Muestra_Lab ADD Es_Pozo BIT DEFAULT 0");
    echo "Added";
}
