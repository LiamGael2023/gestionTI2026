<?php
class Certificados digitalesModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $sql = "SELECT * FROM certificados digitales WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }
}