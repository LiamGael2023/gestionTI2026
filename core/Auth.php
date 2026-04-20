<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

class Auth {

    static public function check() {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/index.php?module=auth&action=login");
            exit();
        }

        $modulo_actual = $_GET['module'] ?? 'dashboard';
        $excepciones = ['dashboard', 'auth', 'perfil', 'login', 'logout'];

        if (in_array($modulo_actual, $excepciones)) return true;

        if (!self::tienePermiso($modulo_actual)) {
            header("Location: " . BASE_URL . "/index.php?module=dashboard&error=access_denied");
            exit();
        }
    }

    static private function tienePermiso($modulo) {
        $db = Conexion::conectar();
        $id_usuario = $_SESSION['usuario_id'];

        $sql = "SELECT p.pueden_ver 
                FROM comun.Permisos p
                INNER JOIN comun.Modulos m ON p.id_modulo = m.id_modulo
                WHERE p.id_usuario = ? AND m.nombre = ? AND p.pueden_ver = 1";

        $stmt = sqlsrv_query($db, $sql, [$id_usuario, $modulo]);
        if ($stmt === false) return false;

        return sqlsrv_has_rows($stmt);
    }

    static public function login($usuario, $password) {
        $db = Conexion::conectar();
        require_once __DIR__ . '/../modules/auth/models/AuthModel.php';
        $model = new AuthModel($db);

        $user = $model->buscarUsuario($usuario);
        if (!$user) return false;

        if (!password_verify($password, $user['contrasenia'])) return false;

        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['usuario_nombre'] = $user['nombres'];
        $_SESSION['usuario_rol'] = $user['rol'];

        $model->registrarAcceso($user['id_usuario']);
        return true;
    }

    static public function logout() {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "/index.php?module=auth&action=login");
        exit();
    }
}
?>