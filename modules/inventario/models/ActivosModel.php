<?php
require_once "../../../config/db.php"; // Ruta hacia tu db.php

class InventarioModel {
    static public function mdlIngresarActivo($tabla, $datos) {
        $conn = Conexion::conectar();
        
        // Query para SQL Server
        $sql = "INSERT INTO $tabla (descripcion, icono, compuesto, usuarioCreacion, fechaCreacion) 
                VALUES (?, ?, ?, ?, GETDATE())";

        $params = array(
            $datos["descripcion"],
            $datos["icono"],
            $datos["compuesto"],
            $datos["usuarioCreacion"]
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            return "ok";
        } else {
            return "error";
        }
    }
}