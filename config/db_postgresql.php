<?php
class ConexionPostgreSQL {
    static public function conectar() {
        $host   = PG_HOST;
        $port   = PG_PORT;
        $dbname = PG_DB;
        $user   = PG_USER;
        $pass   = PG_PASS;

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            return $pdo;
        } catch (\PDOException $e) {
            error_log('PostgreSQL connection failed: ' . $e->getMessage());
            return null;
        }
    }
}
