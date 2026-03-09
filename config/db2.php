<?php
class Conexion2 {
    static public function conectar() {
        $serverName = "localhost"; 
        $connectionOptions = array(
            "Database" => "BDPERSONAL",
            "Uid" => "sa",
            "PWD" => "SrvPRU01#$",
            "CharacterSet" => "UTF-8",
            "TrustServerCertificate" => true, 
            "Encrypt" => true 
        );
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        return $conn;
    }
}


//     public static function conectar() {

//         $serverName = "localhost"; // o "127.0.0.1", según tu caso

//         // Parámetros de conexión
//         $connectionOptions = [
//             "Database" => "BDPERSONAL",
//             "Uid"      => "sa",
//             "PWD"      => "SrvPRU01#$",
//             "CharacterSet" => "UTF-8", // siempre recomendable con SQLSRV
//             "Encrypt" => "yes",
// "TrustServerCertificate" => "yes",

//         ];

//         // Opcional: desactivar warnings informativos (5701, 5703)
//         sqlsrv_configure("WarningsReturnAsErrors", 0);

//         // Intentar conexión
//         $conn = sqlsrv_connect($serverName, $connectionOptions);

//         if ($conn === false) {
//             // Mostrar errores si la conexión falla
//             die("<pre>❌ Error al conectar:\n" . print_r(sqlsrv_errors(), true) . "</pre>");
//         }

//         // Conexión exitosa
//         // echo "✅ Conectado correctamente a SQL Server.";

//         return $conn;
//     }
//}




