<?php
class AuthModel {
    private $db;

    public function __construct($db) {
        if (!$db) {
            die("Error: No se pudo establecer la conexión a la base de datos en el Modelo.");
        }
        $this->db = $db;
    }

    /**
     * Busca un usuario por su nombre de usuario (login)
     */
    public function buscarUsuario($usuario) {
        $sql = "SELECT id_usuario, usuario, contrasenia, nombres, apellidos, rol 
                FROM comun.Usuarios 
                WHERE usuario = ? AND activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($usuario));
        
        if ($stmt === false) return false;
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Registra el último acceso (Opcional, pero muy útil para auditoría)
     */
    public function registrarAcceso($id_usuario) {
        $sql = "UPDATE comun.Usuarios SET ultimo_acceso = GETDATE() WHERE id_usuario = ?";
        sqlsrv_query($this->db, $sql, array($id_usuario));
    }
}