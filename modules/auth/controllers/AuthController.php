<?php
// MUY IMPORTANTE: La sesión debe iniciar antes de cualquier salida de texto
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'modules/auth/models/AuthModel.php';

$conn = Conexion::conectar();
$model = new AuthModel($conn);
$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'autenticar':
        header('Content-Type: application/json');
        
        $user_input = $_POST['usuario'] ?? '';
        $pass_input = $_POST['contrasenia'] ?? '';
        
        $usuario = $model->buscarUsuario($user_input);

        if ($usuario && password_verify($pass_input, $usuario['contrasenia'])) {
            
            // --- AQUÍ ESTABA EL ERROR: DEBES GUARDAR LOS DATOS ---
            $_SESSION['usuario_id']     = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombres'];
            $_SESSION['usuario_rol']    = $usuario['rol'];
            $_SESSION['autenticado']    = true;

            // Usamos una ruta relativa simple para que funcione con localhost o IP
            echo json_encode([
                'success' => true, 
                'redirect' => 'index.php?module=dashboard'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Usuario o contraseña incorrectos.'
            ]);
        }
        exit;

    case 'logout':
        session_unset();
        session_destroy();
        header("Location: index.php?module=auth&action=login");
        exit;

    default:
        include 'modules/auth/views/login.php';
        break;
}