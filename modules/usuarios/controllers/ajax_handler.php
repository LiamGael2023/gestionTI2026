<?php
session_start();
require_once '../../../config/db.php';
$conn = Conexion::conectar();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');
if (ob_get_length()) ob_end_clean();

// --- ACCIÓN: GUARDAR O ACTUALIZAR ---
if ($action == 'guardar_proceso') {
    $id        = $_POST['id_usuario'] ?? '';
    $nombres   = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $usuario   = $_POST['usuario'] ?? '';
    $correo    = $_POST['correo'] ?? '';
    $rol       = $_POST['rol'] ?? 'USUARIO';
    $sede_id   = $_POST['sede_id'] ?? 1;
    $documento = $_POST['documento'] ?? '';
    $pass_raw  = $_POST['contrasenia'] ?? '';

    if (empty($id)) {
        // INSERTAR NUEVO
        $pass = password_hash($pass_raw, PASSWORD_DEFAULT);
        $sql = "INSERT INTO comun.Usuarios (nombres, apellidos, usuario, correo, contrasenia, rol, sede_id, documento, activo, fecha_creacion, id_rol) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, GETDATE(), 1)";
        $params = array($nombres, $apellidos, $usuario, $correo, $pass, $rol, $sede_id, $documento);
    } else {
        // ACTUALIZAR EXISTENTE
        if (!empty($pass_raw)) {
            $pass = password_hash($pass_raw, PASSWORD_DEFAULT);
            $sql = "UPDATE comun.Usuarios SET nombres=?, apellidos=?, usuario=?, correo=?, rol=?, sede_id=?, documento=?, contrasenia=? WHERE id_usuario=?";
            $params = array($nombres, $apellidos, $usuario, $correo, $rol, $sede_id, $documento, $pass, $id);
        } else {
            $sql = "UPDATE comun.Usuarios SET nombres=?, apellidos=?, usuario=?, correo=?, rol=?, sede_id=?, documento=? WHERE id_usuario=?";
            $params = array($nombres, $apellidos, $usuario, $correo, $rol, $sede_id, $documento, $id);
        }
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) {
        $id_usuario_final = empty($id) ? sqlsrv_fetch_array(sqlsrv_query($conn, "SELECT @@IDENTITY AS id"))['id'] : $id;
        
        // --- PROCESAR PERMISOS ---
        sqlsrv_query($conn, "DELETE FROM comun.Permisos WHERE id_usuario = ?", array($id_usuario_final));
        if (isset($_POST['permisos']) && is_array($_POST['permisos'])) {
            foreach ($_POST['permisos'] as $id_mod) {
                sqlsrv_query($conn, "INSERT INTO comun.Permisos (id_usuario, id_modulo, pueden_ver, pueden_editar) VALUES (?, ?, 1, 1)", array($id_usuario_final, $id_mod));
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => sqlsrv_errors()]);
    }
    exit;
}

// --- ACCIÓN: OBTENER DATOS PARA EDITAR ---
if ($action == 'obtener_json') {
    $id = intval($_GET['id']);
    $stmt = sqlsrv_query($conn, "SELECT * FROM comun.Usuarios WHERE id_usuario = ?", array($id));
    echo json_encode(sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));
    exit;
}

// --- ACCIÓN: OBTENER PERMISOS MARCADOS ---
if ($action == 'obtener_permisos') {
    $id = intval($_GET['id']);
    $permisos = [];
    $stmt = sqlsrv_query($conn, "SELECT id_modulo FROM comun.Permisos WHERE id_usuario = ? AND pueden_ver = 1", array($id));
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $permisos[] = $row; }
    echo json_encode($permisos);
    exit;
}

// --- ACCIÓN: ELIMINAR ---
if ($action == 'eliminar') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id']);
    $stmt = sqlsrv_query($conn, "UPDATE comun.Usuarios SET activo = 0 WHERE id_usuario = ?", array($id));
    echo json_encode(['success' => $stmt]);
    exit;
}