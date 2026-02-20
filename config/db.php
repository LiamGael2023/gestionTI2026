<?php
class Conexion {
    static public function conectar() {
        $serverName = "10.0.100.252"; 
        $connectionOptions = array(
            "Database" => "BD_GESTION_TI",
            "Uid" => "sa",
            "PWD" => "SrvPRU01#$",
            "CharacterSet" => "UTF-8",
            // --- ESTA ES LA LÍNEA QUE SOLUCIONA EL ERROR ---
            "TrustServerCertificate" => true, 
            // ----------------------------------------------
            "Encrypt" => true // El driver 18 requiere cifrado, pero con la línea de arriba confiamos en el server
        );

        $conn = sqlsrv_connect($serverName, $connectionOptions);

        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        return $conn;
    }
}
?>