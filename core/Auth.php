<?php
session_start();

class Auth {
    // Verificar sesión y permisos del módulo actual
    static public function check() {
        // 1. Validar si está logueado
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        // 2. Obtener el módulo actual (Soporta URL amigable y parámetros tradicionales)
        // Priorizamos 'route' que viene del web.config
        $modulo_actual = 'dashboard';

        if (isset($_GET['route'])) {
            $partes = explode('/', rtrim($_GET['route'], '/'));
            $modulo_actual = $partes[0];
        } elseif (isset($_GET['module'])) {
            $modulo_actual = $_GET['module'];
        }

        // 3. Excepciones (Módulos que todos los logueados pueden ver)
        // Agregamos 'dashboard' y 'perfil' a la lista
        $excepciones = ['dashboard', 'auth', 'perfil', 'login', 'logout'];
        if (in_array($modulo_actual, $excepciones)) {
            return true;
        }

        // 4. Validar permisos en Base de Datos
        if (!self::tienePermiso($modulo_actual)) {
            // Si no tiene permiso, redirigimos al dashboard con alerta
            header("Location: " . BASE_URL . "/dashboard?error=access_denied");
            exit();
        }
    }

    /**
     * Consulta privada para validar el acceso al módulo en la BD
     */
    static private function tienePermiso($modulo) {
        $db = Conexion::conectar();
        $id_usuario = $_SESSION['usuario_id'];

        // Usamos esquema 'comun' como en tu script original
        $sql = "SELECT p.pueden_ver 
                FROM comun.Permisos p
                INNER JOIN comun.Modulos m ON p.id_modulo = m.id_modulo
                WHERE p.id_usuario = ? AND m.nombre = ? AND p.pueden_ver = 1";
        
        $params = array($id_usuario, $modulo);
        $stmt = sqlsrv_query($db, $sql, $params);

        // Si hay error en la consulta o no devuelve filas, no tiene permiso
        if ($stmt === false || !sqlsrv_has_rows($stmt)) {
            return false;
        }

        return true;
    }

    static public function login($id, $nombre, $rol) {
        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_rol'] = $rol;
    }

    static public function logout() {
        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit();
    }

    /**
     * Retorna los 5 niveles de permiso del usuario en sesión para un módulo.
     * Devuelve todos en 0 si no existe registro en comun.Permisos.
     *
     * @param  string $modulo Nombre interno del módulo (ej: 'produccion_agraria')
     * @return array  ['pueden_ver'=>0|1, 'pueden_crear'=>0|1, 'pueden_editar'=>0|1,
     *                 'pueden_eliminar'=>0|1, 'pueden_exportar'=>0|1]
     */
    static public function permisosModulo($modulo) {
        $defaults = [
            'pueden_ver'      => 0,
            'pueden_crear'    => 0,
            'pueden_editar'   => 0,
            'pueden_eliminar' => 0,
            'pueden_exportar' => 0,
        ];

        if (!isset($_SESSION['usuario_id'])) {
            return $defaults;
        }

        // Los ADMIN siempre tienen todos los permisos
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'ADMIN') {
            return array_fill_keys(array_keys($defaults), 1);
        }

        $db = Conexion::conectar();
        $sql = "SELECT p.pueden_ver, p.pueden_crear, p.pueden_editar, p.pueden_eliminar, p.pueden_exportar
                FROM comun.Permisos p
                INNER JOIN comun.Modulos m ON p.id_modulo = m.id_modulo
                WHERE p.id_usuario = ? AND m.nombre = ?";
        $stmt = sqlsrv_query($db, $sql, [$_SESSION['usuario_id'], $modulo]);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            return [
                'pueden_ver'      => (int)($row['pueden_ver']      ?? 0),
                'pueden_crear'    => (int)($row['pueden_crear']    ?? 0),
                'pueden_editar'   => (int)($row['pueden_editar']   ?? 0),
                'pueden_eliminar' => (int)($row['pueden_eliminar'] ?? 0),
                'pueden_exportar' => (int)($row['pueden_exportar'] ?? 0),
            ];
        }

        if ($stmt) sqlsrv_free_stmt($stmt);
        return $defaults;
    }
}

