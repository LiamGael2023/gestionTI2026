<?php
class SistemasModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listarModulos() {
        $sql = "SELECT * FROM comun.Modulos ORDER BY orden ASC";
        return sqlsrv_query($this->db, $sql);
    }

    public function existeNombre($nombre) {
        $sql = "SELECT COUNT(*) as total FROM comun.Modulos WHERE nombre = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($nombre));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'] > 0;
    }

    public function registrarModulo($nombre, $etiqueta, $icono, $orden) {
        $sql = "INSERT INTO comun.Modulos (nombre, etiqueta, icono, orden) VALUES (?, ?, ?, ?)";
        return sqlsrv_query($this->db, $sql, array($nombre, $etiqueta, $icono, $orden));
    }

    public function actualizarModulo($id, $etiqueta, $icono, $orden) {
        $sql = "UPDATE comun.Modulos SET etiqueta = ?, icono = ?, orden = ? WHERE id_modulo = ?";
        return sqlsrv_query($this->db, $sql, array($etiqueta, $icono, $orden, $id));
    }

    public function eliminarModulo($id) {
        // Primero limpiamos permisos por la llave foránea
        sqlsrv_query($this->db, "DELETE FROM comun.Permisos WHERE id_modulo = ?", array($id));
        $sql = "DELETE FROM comun.Modulos WHERE id_modulo = ?";
        return sqlsrv_query($this->db, $sql, array($id));
    }
}