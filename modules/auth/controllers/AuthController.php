<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'config/config.php';
require_once 'core/Auth.php';
require_once 'modules/auth/models/AuthModel.php';

$conn = Conexion::conectar();
$model = new AuthModel($conn);

$action = $_GET['action'] ?? 'login';

switch ($action) {

    case 'autenticar':
        header('Content-Type: application/json');
        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['contrasenia'] ?? '';

        if (Auth::login($usuario, $password)) {
            echo json_encode([
                'success' => true,
                'redirect' => BASE_URL . '/index.php?module=dashboard'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.'
            ]);
        }
        exit;

    case 'logout':
        Auth::logout();
        break;

    default:
        include 'modules/auth/views/login.php';
        break;
}
?>