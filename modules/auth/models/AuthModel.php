<?php
class AuthModel {
    private $db;

    public function __construct($db) {
        if (!$db) die("Error: No se pudo conectar a la base de datos");
        $this->db = $db;
    }

    // Buscar usuario activo por usuario
    public function buscarUsuario($usuario) {
        $sql = "SELECT id_usuario, nombres, correo, contrasenia, rol, usuario, apellidos
                FROM comun.Usuarios
                WHERE usuario = ? AND activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, [$usuario]);
        if ($stmt === false) return false;
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    // Registrar último acceso
    public function registrarAcceso($id_usuario) {
        $sql = "UPDATE comun.Usuarios SET fecha_creacion = GETDATE() WHERE id_usuario = ?";
        sqlsrv_query($this->db, $sql, [$id_usuario]);
    }
}
?>