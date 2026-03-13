<?php
class AppsisgerModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $sql = "SELECT * FROM appsisger WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }
}