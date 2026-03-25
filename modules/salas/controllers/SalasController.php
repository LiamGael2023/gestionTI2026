<?php
/**
 * SalasController.php
 * Controlador principal del módulo de Gestión de Reservas de Sala de Reunión.
 * Carga la vista principal con el contexto del rol del usuario autenticado.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

require_once 'modules/salas/models/core/SalasModel.php';

// Verificar sesión activa (la verificación principal ya la hace Auth::check() en index.php)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

$model         = new SalasModel($conn);
$action        = $_GET['action'] ?? 'index';
$id_usuario    = (int) $_SESSION['usuario_id'];
$rol_usuario   = $_SESSION['usuario_rol'] ?? '';
$usuario_login_impresion = $_SESSION['usuario_login'] ?? '';

if ($usuario_login_impresion === '') {
    $stmt_usuario = sqlsrv_query(
        $conn,
        "SELECT usuario FROM comun.Usuarios WHERE id_usuario = ?",
        [$id_usuario]
    );
    if ($stmt_usuario !== false) {
        $row_usuario = sqlsrv_fetch_array($stmt_usuario, SQLSRV_FETCH_ASSOC);
        if (!empty($row_usuario['usuario'])) {
            $usuario_login_impresion = (string) $row_usuario['usuario'];
            $_SESSION['usuario_login'] = $usuario_login_impresion;
        }
    }
}

// Normalizar rol desde sesión (con fallback)
$rol_normalizado = SalasModel::normalizarRolUsuario();

$es_autorizador_o_admin = SalasModel::esAutorizadorOAdmin($rol_normalizado);
$es_admin               = SalasModel::esAdmin($rol_normalizado);

switch ($action) {
    // ------------------------------------------------------------------
    // Vista principal del módulo (cualquier rol autenticado)
    // ------------------------------------------------------------------
    case 'index':
    default:
        include 'modules/salas/views/html/features/dashboard.php';
        break;

    // ------------------------------------------------------------------
    // Vista de Calendario (cualquier rol autenticado)
    // ------------------------------------------------------------------
    case 'calendario':
        include 'modules/salas/views/html/features/calendario.php';
        break;

    // ------------------------------------------------------------------
    // Vista de Administración (solo Administrador)
    // ------------------------------------------------------------------
    case 'admin':
        if (!$es_admin) {
            echo '<div class="alert alert-danger m-4">Acceso restringido. Se requiere rol Administrador.</div>';
            break;
        }
        include 'modules/salas/views/html/roles/admin/catalogo.php';
        break;

    // ------------------------------------------------------------------
    // Vista de Historial (solo Administrador)
    // ------------------------------------------------------------------
    case 'historial':
        if (!$es_admin) {
            echo '<div class="alert alert-danger m-4">Acceso restringido. Se requiere rol Administrador.</div>';
            break;
        }
        include 'modules/salas/views/html/roles/admin/historial.php';
        break;

    // ------------------------------------------------------------------
    // Dashboard de Indicadores (todos los roles)
    // ------------------------------------------------------------------
    case 'dashboard':
        include 'modules/salas/views/html/features/dashboard.php';
        break;

    // ------------------------------------------------------------------
    // Vista de Solicitudes Pendientes (Autorizador y Administrador)
    // ------------------------------------------------------------------
    case 'pendientes':
        if (!$es_autorizador_o_admin) {
            echo '<div class="alert alert-danger m-4">Acceso restringido. Se requiere rol Autorizador o Administrador.</div>';
            break;
        }
        include 'modules/salas/views/html/roles/autorizador/autorizaciones.php';
        break;

    // ------------------------------------------------------------------
    // Vista "Mis Reservas" — página dedicada (cualquier rol autenticado)
    // ------------------------------------------------------------------
    case 'mis-reservas':
        include 'modules/salas/views/html/roles/usuario/mis-reservas.php';
        break;
}