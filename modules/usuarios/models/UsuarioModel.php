<?php
class UsuarioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM comun.Usuarios WHERE id_usuario = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function existeUsuario($usuario, $id_excluir = null) {
        $sql = "SELECT COUNT(*) as total FROM comun.Usuarios WHERE usuario = ?";
        $params = array($usuario);
        if ($id_excluir) {
            $sql .= " AND id_usuario != ?";
            $params[] = $id_excluir;
        }
        $stmt = sqlsrv_query($this->db, $sql, $params);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'] > 0;
    }

    public function guardar($datos) {
        if (empty($datos['id_usuario'])) {
            // INSERTAR
            $pass = password_hash($datos['contrasenia'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO comun.Usuarios (nombres, apellidos, usuario, correo, contrasenia, rol, sede_id, documento, activo, fecha_creacion, id_rol) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, GETDATE(), 1); SELECT SCOPE_IDENTITY() AS id;";
            $params = array($datos['nombres'], $datos['apellidos'], $datos['usuario'], $datos['correo'], $pass, $datos['rol'], $datos['sede_id'], $datos['documento']);
            $stmt = sqlsrv_query($this->db, $sql, $params);
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // ACTUALIZAR
            if (!empty($datos['contrasenia'])) {
                $pass = password_hash($datos['contrasenia'], PASSWORD_DEFAULT);
                $sql = "UPDATE comun.Usuarios SET nombres=?, apellidos=?, usuario=?, correo=?, rol=?, sede_id=?, documento=?, contrasenia=? WHERE id_usuario=?";
                $params = array($datos['nombres'], $datos['apellidos'], $datos['usuario'], $datos['correo'], $datos['rol'], $datos['sede_id'], $datos['documento'], $pass, $datos['id_usuario']);
            } else {
                $sql = "UPDATE comun.Usuarios SET nombres=?, apellidos=?, usuario=?, correo=?, rol=?, sede_id=?, documento=? WHERE id_usuario=?";
                $params = array($datos['nombres'], $datos['apellidos'], $datos['usuario'], $datos['correo'], $datos['rol'], $datos['sede_id'], $datos['documento'], $datos['id_usuario']);
            }
            sqlsrv_query($this->db, $sql, $params);
            return $datos['id_usuario'];
        }
    }

    public function actualizarPermisos($id_usuario, $modulos) {
        sqlsrv_query($this->db, "DELETE FROM comun.Permisos WHERE id_usuario = ?", array($id_usuario));
        if (!empty($modulos)) {
            foreach ($modulos as $id_mod) {
                $sql = "INSERT INTO comun.Permisos (id_usuario, id_modulo, pueden_ver, pueden_editar) VALUES (?, ?, 1, 1)";
                sqlsrv_query($this->db, $sql, array($id_usuario, $id_mod));
            }
        }
    }

    public function obtenerPermisos($id_usuario) {
        $permisos = [];
        $sql = "SELECT id_modulo FROM comun.Permisos WHERE id_usuario = ? AND pueden_ver = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id_usuario));
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $permisos[] = $row;
        }
        return $permisos;
    }

    public function eliminar($id) {
        $sql = "UPDATE comun.Usuarios SET activo = 0 WHERE id_usuario = ?";
        return sqlsrv_query($this->db, $sql, array($id));
    }
}