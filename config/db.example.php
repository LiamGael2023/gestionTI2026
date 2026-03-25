<?php
/**
 * Plantilla de conexión a base de datos (sin credenciales)
 * Copiar a db.php y completar valores reales en entorno local/servidor.
 */

class Conexion {
    static public function conectar() {
        $serverName = 'Ip delServidorSQL'; // Ejemplo: 'localhost' o '127.0.0.1'
        $connectionOptions = array(
<<<<<<< Updated upstream
            'Database' => 'Nombre_BD',
=======
            'Database' => 'BD_GESTION_TI',
>>>>>>> Stashed changes
            'Uid' => 'USUARIO_SQL',
            'PWD' => 'REEMPLAZAR_PASSWORD_SQL',
            'CharacterSet' => 'UTF-8',
            'TrustServerCertificate' => true,
            'Encrypt' => true,
        );

        $conn = sqlsrv_connect($serverName, $connectionOptions);

        if ($conn === false) {
            die('<pre>Error de conexión: ' . print_r(sqlsrv_errors(), true) . '</pre>');
        }

        return $conn;
    }
}
