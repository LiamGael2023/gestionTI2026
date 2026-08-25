<?php
/**
 * LaboratorioModel
 * Modelo del módulo Laboratorio
 * Buenas prácticas: Consultas parametrizadas, separación de responsabilidades
 */

class LaboratorioModel {
    private $db;

    public function __construct($db) {
        if (!$db) {
            throw new Exception("Error: No se pudo establecer la conexión a la base de datos");
        }
        $this->db = $db;
    }

    /**
     * Obtiene la información del usuario logueado
     * @param int $id_usuario ID del usuario
     * @return array Datos del usuario
     */
    public function obtenerUsuario($id_usuario) {
        $sql = "SELECT id_usuario, documento, nombres, apellidos, usuario, correo, rol, sede_id 
                FROM comun.Usuarios 
                WHERE id_usuario = ? AND activo = 1";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_usuario));
        
        if ($stmt === false) {
            return null;
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Devuelve true si el usuario tiene rol de administrador en comun.Usuarios
     */
    public function esAdministrador($id_usuario) {
        $sql = "SELECT TOP 1 rol FROM comun.Usuarios WHERE id_usuario = ? AND activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario]);
        if ($stmt === false) return false;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) return false;
        $rol = strtolower(trim((string)($row['rol'] ?? '')));
        return in_array($rol, ['administrador', 'admin', 'superadmin', 'super admin'], true);
    }

    /**
     * Obtiene el rol de laboratorio asignado al usuario (con sus permisos por submódulo)
     */
    public function obtenerRolLaboratorio($id_usuario) {
        $sql = "SELECT r.Id_Rol, r.Nombre AS Rol_Nombre, r.Descripcion AS Rol_Descripcion
                FROM laboratorio.Usuario_Rol ur
                INNER JOIN laboratorio.Rol r ON ur.Id_Rol = r.Id_Rol AND r.Activo = 1
                WHERE ur.Id_Usuario = ?
                ORDER BY ur.Fecha_Asignacion DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario]);
        if ($stmt === false) return null;
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Obtiene los submódulos accesibles para un usuario según su rol de laboratorio.
     * Los administradores comun ven todo + sección Roles.
     */
    public function obtenerResponsabilidades($id_usuario) {
        $esAdmin = $this->esAdministrador($id_usuario);

        if ($esAdmin) {
            // Admin ve todos los submódulos desde la tabla + la sección de gestión de roles
            $sql = "SELECT Nombre, Icono, Descripcion, Url
                    FROM laboratorio.Submodulo
                    WHERE Activo = 1
                    ORDER BY Id_Submodulo";
            $stmt = sqlsrv_query($this->db, $sql);
            $result = [];
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $result[] = [
                        'nombre'      => $row['Nombre'],
                        'icono'       => $row['Icono'],
                        'descripcion' => $row['Descripcion'],
                        'url'         => $row['Url'],
                        'color'       => 'primary',
                    ];
                }
            }
            // Agregar sección de gestión de roles (solo admins)
            $result[] = [
                'nombre'      => 'Roles',
                'icono'       => 'shield-lock',
                'descripcion' => 'Gestión de Roles de Laboratorio',
                'url'         => '?module=laboratorio&action=roles',
                'color'       => 'danger',
            ];
            return $result;
        }

        // Usuario normal: mostrar solo los submódulos donde tiene Pueden_Ver = 1
        $sql = "SELECT s.Nombre, s.Icono, s.Descripcion, s.Url, pr.Pueden_Ver
                FROM laboratorio.Usuario_Rol ur
                INNER JOIN laboratorio.Rol r        ON ur.Id_Rol = r.Id_Rol AND r.Activo = 1
                INNER JOIN laboratorio.Permiso_Rol pr ON r.Id_Rol = pr.Id_Rol AND pr.Activo = 1
                INNER JOIN laboratorio.Submodulo s   ON pr.Id_Submodulo = s.Id_Submodulo AND s.Activo = 1
                WHERE ur.Id_Usuario = ? AND pr.Pueden_Ver = 1
                ORDER BY s.Id_Submodulo";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario]);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = [
                    'nombre'      => $row['Nombre'],
                    'icono'       => $row['Icono'],
                    'descripcion' => $row['Descripcion'],
                    'url'         => $row['Url'],
                    'color'       => 'primary',
                ];
            }
        }
        return $result;
    }

    /**
     * Obtiene los permisos detallados de un usuario en un submódulo específico
     */
    public function obtenerPermisosSubmodulo($id_usuario, $url_submodulo) {
        if ($this->esAdministrador($id_usuario)) {
            return ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true, 'exportar' => true, 'firmar' => true];
        }
        $sql = "SELECT pr.Pueden_Ver, pr.Pueden_Crear, pr.Pueden_Editar,
                       pr.Pueden_Eliminar, pr.Pueden_Exportar, pr.Pueden_Firmar
                FROM laboratorio.Usuario_Rol ur
                INNER JOIN laboratorio.Rol r          ON ur.Id_Rol = r.Id_Rol AND r.Activo = 1
                INNER JOIN laboratorio.Permiso_Rol pr  ON r.Id_Rol = pr.Id_Rol AND pr.Activo = 1
                INNER JOIN laboratorio.Submodulo s     ON pr.Id_Submodulo = s.Id_Submodulo AND s.Activo = 1
                WHERE ur.Id_Usuario = ? AND s.Url = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario, $url_submodulo]);
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) return null;
        return [
            'ver'      => (bool)$row['Pueden_Ver'],
            'crear'    => (bool)$row['Pueden_Crear'],
            'editar'   => (bool)$row['Pueden_Editar'],
            'eliminar' => (bool)$row['Pueden_Eliminar'],
            'exportar' => (bool)$row['Pueden_Exportar'],
            'firmar'   => (bool)$row['Pueden_Firmar'],
        ];
    }

    /**
     * Verifica si el usuario tiene un permiso concreto (ver/crear/editar/eliminar/
     * exportar/firmar) sobre un submódulo del laboratorio.
     * Los administradores comun tienen todos los permisos.
     *
     * @param int    $id_usuario     ID de usuario (comun.Usuarios)
     * @param string $url_submodulo  URL del submódulo, ej. '?module=laboratorio&action=equipo'
     * @param string $accion         'ver'|'crear'|'editar'|'eliminar'|'exportar'|'firmar'
     * @return bool
     */
    public function verificarPermisoLab($id_usuario, $url_submodulo, $accion) {
        if ($this->esAdministrador($id_usuario)) return true;
        $permisos = $this->obtenerPermisosSubmodulo($id_usuario, $url_submodulo);
        if ($permisos === null) return false;
        return (bool)($permisos[$accion] ?? false);
    }

    /**
     * Helper de API: si el usuario no tiene el permiso requerido, responde 403
     * en JSON y detiene la ejecución. Devuelve true si SÍ tiene permiso.
     */
    public function denegarSiSinPermiso($id_usuario, $url_submodulo, $accion) {
        if ($this->verificarPermisoLab($id_usuario, $url_submodulo, $accion)) {
            return true;
        }
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Acceso denegado: no tiene permiso para ' . $accion . ' en este módulo.']);
        exit;
    }

    // ── Gestión de roles (solo para admins) ────────────────────────────────

    public function listarRoles() {
        $sql = "SELECT r.Id_Rol, r.Nombre, r.Descripcion, r.Activo, r.Fecha_Creacion,
                       COUNT(ur.Id_Usuario_Rol) AS Total_Usuarios
                FROM laboratorio.Rol r
                LEFT JOIN laboratorio.Usuario_Rol ur ON r.Id_Rol = ur.Id_Rol
                WHERE r.Activo = 1
                GROUP BY r.Id_Rol, r.Nombre, r.Descripcion, r.Activo, r.Fecha_Creacion
                ORDER BY r.Id_Rol";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function crearRol($nombre, $descripcion, $usuario_creacion) {
        $nombre      = trim((string)$nombre);
        $descripcion = trim((string)$descripcion);
        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre del rol es obligatorio.');
        }
        $sql  = "INSERT INTO laboratorio.Rol (Nombre, Descripcion, Activo, Fecha_Creacion, Usuario_Creacion)
                 VALUES (?, ?, 1, GETDATE(), ?)";
        $stmt = sqlsrv_query($this->db, $sql, [$nombre, $descripcion ?: null, $usuario_creacion]);
        if ($stmt === false) {
            throw new \Exception('Error al crear rol: ' . print_r(sqlsrv_errors(), true));
        }
        // Devolver el Id generado
        $idSql  = "SELECT SCOPE_IDENTITY() AS Id_Rol";
        $idStmt = sqlsrv_query($this->db, $idSql);
        $row    = sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC);
        return (int)($row['Id_Rol'] ?? 0);
    }

    public function listarSubmodulos() {
        $sql = "SELECT Id_Submodulo, Nombre, Icono, Descripcion, Activo FROM laboratorio.Submodulo WHERE Activo = 1 ORDER BY Id_Submodulo";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function obtenerPermisosRol($id_rol) {
        $sql = "SELECT pr.Id_Permiso_Rol, pr.Id_Submodulo, s.Nombre AS Submodulo_Nombre,
                       pr.Pueden_Ver, pr.Pueden_Crear, pr.Pueden_Editar,
                       pr.Pueden_Eliminar, pr.Pueden_Exportar, pr.Pueden_Firmar
                FROM laboratorio.Permiso_Rol pr
                INNER JOIN laboratorio.Submodulo s ON pr.Id_Submodulo = s.Id_Submodulo
                WHERE pr.Id_Rol = ? AND pr.Activo = 1
                ORDER BY s.Id_Submodulo";
        $stmt = sqlsrv_query($this->db, $sql, [$id_rol]);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * Listar usuarios con acceso al módulo laboratorio + su rol asignado.
     * Solo usuarios que tienen el módulo laboratorio en comun.Permisos (id_modulo = 34).
     */
    public function listarUsuariosLaboratorio() {
        $sql = "SELECT DISTINCT
                       u.id_usuario, u.nombres, u.apellidos, u.usuario, u.correo, u.rol AS Rol_Comun,
                       r.Id_Rol AS Id_Rol_Lab, r.Nombre AS Rol_Lab, ur.Fecha_Asignacion
                FROM comun.Usuarios u
                INNER JOIN comun.Permisos p ON u.id_usuario = p.id_usuario
                LEFT  JOIN laboratorio.Usuario_Rol ur ON u.id_usuario = ur.Id_Usuario
                LEFT  JOIN laboratorio.Rol r ON ur.Id_Rol = r.Id_Rol AND r.Activo = 1
                WHERE u.activo = 1
                  AND p.id_modulo = 34
                  AND p.pueden_ver = 1
                ORDER BY u.nombres, u.apellidos";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function asignarRolUsuario($id_usuario, $id_rol, $usuario_asignador) {
        // Eliminar rol anterior si existe
        $sqlDel = "DELETE FROM laboratorio.Usuario_Rol WHERE Id_Usuario = ?";
        sqlsrv_query($this->db, $sqlDel, [$id_usuario]);

        if ($id_rol === null || $id_rol <= 0) return true; // solo quitar rol

        // Encargado (Id_Rol=1) y Analista Jefe (Id_Rol=2) son únicos: solo puede haber uno
        if ($id_rol === 1 || $id_rol === 2) {
            $sqlCheck = "SELECT COUNT(*) AS cnt FROM laboratorio.Usuario_Rol WHERE Id_Rol = ? AND Id_Usuario <> ?";
            $stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$id_rol, $id_usuario]);
            if ($stmtCheck) {
                $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                if (intval($rowCheck['cnt'] ?? 0) > 0) {
                    $nombreRol = ($id_rol === 1) ? 'Encargado de Laboratorio' : 'Analista Jefe';
                    throw new \Exception("Ya existe un usuario asignado como $nombreRol. Solo puede haber uno por rol.");
                }
            }
        }

        $sqlIns = "INSERT INTO laboratorio.Usuario_Rol (Id_Usuario, Id_Rol, Fecha_Asignacion, Usuario_Asignador)
                   VALUES (?, ?, GETDATE(), ?)";
        $stmt = sqlsrv_query($this->db, $sqlIns, [$id_usuario, $id_rol, $usuario_asignador]);
        if ($stmt === false) {
            throw new \Exception('Error al asignar rol: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function guardarPermisosRol($id_rol, array $permisos, $usuario_creacion) {
        // Desactivar permisos actuales
        $sqlDes = "UPDATE laboratorio.Permiso_Rol SET Activo = 0 WHERE Id_Rol = ?";
        sqlsrv_query($this->db, $sqlDes, [$id_rol]);

        foreach ($permisos as $p) {
            $id_sub = intval($p['id_submodulo'] ?? 0);
            if ($id_sub <= 0) continue;
            $sqlIns = "INSERT INTO laboratorio.Permiso_Rol
                           (Id_Rol, Id_Submodulo, Pueden_Ver, Pueden_Crear, Pueden_Editar,
                            Pueden_Eliminar, Pueden_Exportar, Pueden_Firmar, Activo, Usuario_Creacion)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
            sqlsrv_query($this->db, $sqlIns, [
                $id_rol, $id_sub,
                (int)(bool)($p['ver']      ?? false),
                (int)(bool)($p['crear']    ?? false),
                (int)(bool)($p['editar']   ?? false),
                (int)(bool)($p['eliminar'] ?? false),
                (int)(bool)($p['exportar'] ?? false),
                (int)(bool)($p['firmar']   ?? false),
                $usuario_creacion,
            ]);
        }
        return true;
    }

    /**
     * Obtiene listado de equipos (para futuras estadísticas)
     * @return mixed Resultado de la consulta
     */
    public function listarEquipos() {
        $sql = "SELECT * FROM laboratorio_equipos WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }

    /**
     * Obtiene listado de reactivos (para futuras estadísticas)
     * @return mixed Resultado de la consulta
     */
    public function listarReactivos() {
        $sql = "SELECT * FROM laboratorio_reactivos WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }

    /**
     * Obtiene listado de servicios (para futuras estadísticas)
     * @return mixed Resultado de la consulta
     */
    public function listarServicios() {
        $sql = "SELECT * FROM laboratorio_servicios WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }

    /**
     * Obtiene la firma digital del usuario
     */
    public function obtenerFirmaUsuario($id_usuario) {
        $sql = "SELECT TOP 1 Id_Usuario, Img_Firma, Activo, Fecha_Modificacion
                FROM laboratorio.Usuario_Lab_Firma
                WHERE Id_Usuario = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, [$id_usuario]);
        if ($stmt === false) return null;
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Guarda o actualiza la firma digital del usuario (UPSERT)
     */
    public function guardarFirmaUsuario($id_usuario, $img_firma, $usuario_creacion) {
        // Forzar tipo NVARCHAR(MAX) para evitar truncamiento de cadenas base64 largas en sqlsrv
        $paramFirma = [$img_firma, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_NVARCHAR('max')];
        $paramId    = [$id_usuario, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT];
        $paramCreac = [$usuario_creacion, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT];

        $exists = $this->obtenerFirmaUsuario($id_usuario);
        if ($exists) {
            $sql = "UPDATE laboratorio.Usuario_Lab_Firma
                    SET Img_Firma = ?, Fecha_Modificacion = GETDATE(), Activo = 1
                    WHERE Id_Usuario = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$paramFirma, $paramId]);
        } else {
            $sql = "INSERT INTO laboratorio.Usuario_Lab_Firma
                        (Id_Usuario, Img_Firma, Activo, Fecha_Creacion, Usuario_Creacion)
                    VALUES (?, ?, 1, GETDATE(), ?)";
            $stmt = sqlsrv_query($this->db, $sql, [$paramId, $paramFirma, $paramCreac]);
        }
        if ($stmt === false) {
            throw new \Exception('Error al guardar firma: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    /**
     * Obtiene la firma del superadmin (primer usuario con rol superadmin activo en el laboratorio)
     */
    public function obtenerFirmaSuperadmin() {
        $sql = "SELECT TOP 1 f.Img_Firma
                FROM laboratorio.Usuario_Lab_Firma f
                INNER JOIN comun.Usuarios u ON f.Id_Usuario = u.id_usuario
                WHERE f.Activo = 1 AND u.activo = 1
                  AND LOWER(u.rol) IN ('superadmin','super admin','administrador','jefe laboratorio')
                ORDER BY u.id_usuario ASC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? $row['Img_Firma'] : null;
    }

    /**
     * Verifica si el usuario puede usar firma digital
     * (tiene Pueden_Firmar=1 en el submódulo Muestras, o es administrador del sistema)
     */
    public function puedeUsarFirma($id_usuario) {
        if ($this->esAdministrador($id_usuario)) return true;
        $permisos = $this->obtenerPermisosSubmodulo($id_usuario, '?module=laboratorio&action=muestra');
        return $permisos ? (bool)($permisos['firmar'] ?? false) : false;
    }

    /**
     * Obtiene el Id_Usuario del Analista Jefe (Id_Rol=2) actualmente asignado
     */
    public function obtenerAnalistaJefe() {
        $sql = "SELECT TOP 1 Id_Usuario FROM laboratorio.Usuario_Rol WHERE Id_Rol = 2 ORDER BY Id_Usuario_Rol ASC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? intval($row['Id_Usuario']) : null;
    }
}
