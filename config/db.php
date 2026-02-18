<?php
class Conexion {
    static public function conectar() {
        $serverName = "10.0.100.252"; 
        $connectionOptions = array(
            "Database" => "BD_GESTION_TI", // Asumiendo que esta es la DB correcta
            "Uid" => "sa",
            "PWD" => "SrvPRU01#$",
            "CharacterSet" => "UTF-8"
        );

        $conn = sqlsrv_connect($serverName, $connectionOptions);

        if ($conn === false) {
            // En producción, esto debería registrarse en un log, no imprimirse
            die(print_r(sqlsrv_errors(), true));
        }

        return $conn;
    }
}
?>