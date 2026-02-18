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

        // 2. Obtener el módulo actual de la URL
        $modulo_actual = $_GET['module'] ?? 'dashboard';

        // 3. Excepciones (Módulos que todos los logueados pueden ver)
        $excepciones = ['dashboard', 'auth', 'perfil'];
        if (in_array($modulo_actual, $excepciones)) {
            return true;
        }

        // 4. Validar permisos en Base de Datos
        if (!self::tienePermiso($modulo_actual)) {
            // Si no tiene permiso, lo mandamos a una página de error o al dashboard
            // con un mensaje de advertencia.
            header("Location: " . BASE_URL . "/index.php?module=dashboard&error=access_denied");
            exit();
        }
    }

    /**
     * Consulta privada para validar el acceso al módulo en la BD
     */
    static private function tienePermiso($modulo) {
        // Necesitamos la conexión aquí
        $db = Conexion::conectar();
        $id_usuario = $_SESSION['usuario_id'];

        $sql = "SELECT p.pueden_ver 
                FROM comun.Permisos p
                INNER JOIN comun.Modulos m ON p.id_modulo = m.id_modulo
                WHERE p.id_usuario = ? AND m.nombre = ? AND p.pueden_ver = 1";
        
        $params = array($id_usuario, $modulo);
        $stmt = sqlsrv_query($db, $sql, $params);

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
}
?>