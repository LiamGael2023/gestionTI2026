<?php
/**
 * PermisosModel — Sistema de Roles y Permisos del módulo Producción Agraria.
 * Tablas: rol_pa, submodulo_pa, permiso_rol_pa, usuario_rol_pa (BD_PRODUCCIONDESARROLLO.dbo)
 */
class PermisosModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── Admin comun ──────────────────────────────────────────────────

    public function esAdministrador($id_usuario) {
        // Fuente primaria: rol de sesión (rápido)
        $rolSesion = strtolower(trim((string)($_SESSION['usuario_rol'] ?? '')));
        if ($rolSesion !== '' && in_array($rolSesion, $this->rolesAdmin(), true)) {
            return true;
        }
        // Fallback: consulta comun.Usuarios (id_usuario siempre confiable)
        $sql = "SELECT TOP 1 rol FROM comun.Usuarios WHERE id_usuario = ? AND activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario]);
        if ($stmt === false) return false;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) return false;
        $rol = strtolower(trim((string)($row['rol'] ?? '')));
        return in_array($rol, $this->rolesAdmin(), true);
    }

    private function rolesAdmin() {
        return ['administrador', 'admin', 'superadmin', 'super admin', 'jefe', 'gerente'];
    }

    // ── Roles ────────────────────────────────────────────────────────

    public function listarRoles() {
        $sql = "SELECT r.Id_Rol_PA, r.Nombre, r.Descripcion, r.Activo, r.Fecha_Creacion,
                       COUNT(ur.Id_Usuario_Rol_PA) AS Total_Usuarios
                FROM BD_PRODUCCIONDESARROLLO.dbo.rol_pa r
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa ur ON r.Id_Rol_PA = ur.Id_Rol_PA
                WHERE r.Activo = 1
                GROUP BY r.Id_Rol_PA, r.Nombre, r.Descripcion, r.Activo, r.Fecha_Creacion
                ORDER BY r.Id_Rol_PA";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $result[] = $row;
        }
        return $result;
    }

    public function crearRol($nombre, $descripcion, $usuario_creacion) {
        $nombre = trim((string)$nombre);
        $descripcion = trim((string)$descripcion);
        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre del rol es obligatorio.');
        }
        $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.rol_pa (Nombre, Descripcion, Activo, Fecha_Creacion, Usuario_Creacion)
                VALUES (?, ?, 1, GETDATE(), ?)";
        $stmt = sqlsrv_query($this->db, $sql, [$nombre, $descripcion ?: null, $usuario_creacion]);
        if ($stmt === false) {
            throw new \Exception('Error al crear rol: ' . print_r(sqlsrv_errors(), true));
        }
        $idSql = "SELECT SCOPE_IDENTITY() AS Id_Rol_PA";
        $idStmt = sqlsrv_query($this->db, $idSql);
        $row = sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC);
        return (int)($row['Id_Rol_PA'] ?? 0);
    }

    public function eliminarRol($id_rol) {
        $id_rol = intval($id_rol);
        if ($id_rol <= 0) throw new \InvalidArgumentException('Rol inválido.');
        // Quitar asignaciones y permisos primero, luego soft-delete del rol
        sqlsrv_query($this->db, "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa WHERE Id_Rol_PA = ?", [$id_rol]);
        sqlsrv_query($this->db, "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa WHERE Id_Rol_PA = ?", [$id_rol]);
        $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.rol_pa SET Activo = 0 WHERE Id_Rol_PA = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id_rol]);
        if ($stmt === false) throw new \Exception('Error al eliminar rol.');
        return true;
    }

    // ── Submódulos ───────────────────────────────────────────────────

    public function listarSubmodulos() {
        $sql = "SELECT Id_Submodulo_PA, Nombre, Icono, Descripcion, Url, Activo
                FROM BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa
                WHERE Activo = 1 ORDER BY Id_Submodulo_PA";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $result[] = $row;
        }
        return $result;
    }

    // ── Permisos de rol ──────────────────────────────────────────────

    public function obtenerPermisosRol($id_rol) {
        $sql = "SELECT pr.Id_Permiso_Rol_PA, pr.Id_Submodulo_PA, s.Nombre AS Submodulo_Nombre,
                       pr.Pueden_Ver
                FROM BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa pr
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa s ON pr.Id_Submodulo_PA = s.Id_Submodulo_PA
                WHERE pr.Id_Rol_PA = ? AND pr.Activo = 1
                ORDER BY s.Id_Submodulo_PA";
        $stmt = sqlsrv_query($this->db, $sql, [$id_rol]);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $result[] = $row;
        }
        return $result;
    }

    public function guardarPermisosRol($id_rol, array $permisos, $usuario_creacion) {
        sqlsrv_query($this->db, "UPDATE BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa SET Activo = 0 WHERE Id_Rol_PA = ?", [$id_rol]);

        foreach ($permisos as $p) {
            $id_sub = intval($p['id_submodulo'] ?? 0);
            if ($id_sub <= 0) continue;
            // El permiso solo controla VISIBILIDAD (Pueden_Ver). Las demás flags quedan en 0.
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa
                        (Id_Rol_PA, Id_Submodulo_PA, Pueden_Ver, Pueden_Crear, Pueden_Editar,
                         Pueden_Eliminar, Pueden_Exportar, Pueden_Firmar, Activo, Usuario_Creacion)
                    VALUES (?, ?, ?, 0, 0, 0, 0, 0, 1, ?)";
            sqlsrv_query($this->db, $sql, [
                $id_rol, $id_sub,
                (int)(bool)($p['ver'] ?? false),
                $usuario_creacion,
            ]);
        }
        return true;
    }

    // ── Usuarios y asignación ────────────────────────────────────────

    public function listarUsuariosProduccion() {
        $sql = "SELECT DISTINCT
                       u.id_usuario, u.nombres, u.apellidos, u.usuario, u.correo, u.rol AS Rol_Comun,
                       r.Id_Rol_PA AS Id_Rol_PA, r.Nombre AS Rol_PA, ur.Fecha_Asignacion
                FROM comun.Usuarios u
                INNER JOIN comun.Permisos p ON u.id_usuario = p.id_usuario
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa ur ON u.id_usuario = ur.Id_Usuario
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.rol_pa r ON ur.Id_Rol_PA = r.Id_Rol_PA AND r.Activo = 1
                INNER JOIN comun.Modulos m ON p.id_modulo = m.id_modulo
                WHERE u.activo = 1
                  AND m.nombre = 'produccion_agraria'
                  AND p.pueden_ver = 1
                ORDER BY u.nombres, u.apellidos";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $result[] = $row;
        }
        return $result;
    }

    public function asignarRolUsuario($id_usuario, $id_rol, $usuario_asignador) {
        sqlsrv_query($this->db, "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa WHERE Id_Usuario = ?", [$id_usuario]);
        if ($id_rol === null || $id_rol <= 0) return true; // solo quitar rol
        $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa (Id_Usuario, Id_Rol_PA, Fecha_Asignacion, Usuario_Asignador)
                VALUES (?, ?, GETDATE(), ?)";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario, $id_rol, $usuario_asignador]);
        if ($stmt === false) throw new \Exception('Error al asignar rol: ' . print_r(sqlsrv_errors(), true));
        return true;
    }

    // ── Verificación de permisos (usado por controladores y vistas) ──

    public function obtenerPermisosSubmodulo($id_usuario, $url_submodulo) {
        if ($this->esAdministrador($id_usuario)) {
            return ['ver' => true];
        }
        $sql = "SELECT pr.Pueden_Ver
                FROM BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa ur
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.rol_pa r         ON ur.Id_Rol_PA = r.Id_Rol_PA AND r.Activo = 1
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa pr ON r.Id_Rol_PA = pr.Id_Rol_PA AND pr.Activo = 1
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa s   ON pr.Id_Submodulo_PA = s.Id_Submodulo_PA AND s.Activo = 1
                WHERE ur.Id_Usuario = ? AND s.Url = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario, $url_submodulo]);
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) return null;
        return [
            'ver' => (bool)$row['Pueden_Ver'],
        ];
    }

    public function verificarPermiso($id_usuario, $url_submodulo, $accion = 'ver') {
        if ($this->esAdministrador($id_usuario)) return true;
        $permisos = $this->obtenerPermisosSubmodulo($id_usuario, $url_submodulo);
        if ($permisos === null) return false;
        // Único permiso gestionado: ver (visibilidad)
        return (bool)($permisos['ver'] ?? false);
    }

    public function denegarSiSinPermiso($id_usuario, $url_submodulo, $accion) {
        if ($this->verificarPermiso($id_usuario, $url_submodulo, $accion)) {
            return true;
        }
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Acceso denegado: no tiene permiso para ' . $accion . ' en este módulo.']);
        exit;
    }
}