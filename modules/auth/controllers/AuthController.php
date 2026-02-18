<?php
require_once 'config/db.php';
require_once 'modules/auth/models/AuthModel.php';

$conn = Conexion::conectar();
$model = new AuthModel($conn);
$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'autenticar':
    header('Content-Type: application/json'); // <--- Muy importante
    
    $user_input = $_POST['usuario'] ?? '';
    $pass_input = $_POST['contrasenia'] ?? '';
    
    $usuario = $model->buscarUsuario($user_input);

    if ($usuario && password_verify($pass_input, $usuario['contrasenia'])) {
        // ... lógica de sesión ...
        echo json_encode(['success' => true, 'redirect' => 'index.php?module=dashboard']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
    }
    exit; // <--- Detener ejecución para que no se pegue el HTML del footer

    case 'logout':
        session_destroy();
        header("Location: index.php?module=auth&action=login");
        break;

    default:
        include 'modules/auth/views/login.php';
        break;
}