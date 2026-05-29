<?php
/**
 * RolesAPI.php — Endpoints AJAX para gestión de roles de laboratorio.
 * Solo accesible para usuarios con rol comun 'administrador'.
 *
 * GET  modules/laboratorio/controllers/RolesAPI.php?op=listar_usuarios
 * GET  modules/laboratorio/controllers/RolesAPI.php?op=listar_roles
 * GET  modules/laboratorio/controllers/RolesAPI.php?op=permisos_rol&id_rol=X
 * POST modules/laboratorio/controllers/RolesAPI.php?op=asignar_rol
 * POST modules/laboratorio/controllers/RolesAPI.php?op=guardar_permisos
 */

error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../../config/db.php';
require_once '../../../core/Auth.php';
require_once '../models/LaboratorioModel.php';

Auth::check();

header('Content-Type: application/json; charset=utf-8');

$conn     = Conexion::conectar();
$model    = new LaboratorioModel($conn);
$usuarioId = intval($_SESSION['usuario_id'] ?? 0);

// Solo administradores
if (!$model->esAdministrador($usuarioId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$op = trim($_REQUEST['op'] ?? '');

switch ($op) {

    // ── Listar usuarios con/sin rol de laboratorio ───────────────────────
    case 'listar_usuarios':
        $usuarios = $model->listarUsuariosLaboratorio();
        $data = [];
        foreach ($usuarios as $u) {
            $data[] = [
                'id_usuario'        => $u['id_usuario'],
                'nombres'           => $u['nombres'],
                'apellidos'         => $u['apellidos'],
                'usuario'           => $u['usuario'],
                'correo'            => $u['correo'],
                'rol_comun'         => $u['Rol_Comun'],
                'id_rol_lab'        => $u['Id_Rol_Lab'],
                'rol_lab'           => $u['Rol_Lab'] ?? '—',
                'fecha_asignacion'  => $u['Fecha_Asignacion']
                    ? (is_object($u['Fecha_Asignacion'])
                        ? $u['Fecha_Asignacion']->format('d/m/Y')
                        : substr($u['Fecha_Asignacion'], 0, 10))
                    : null,
            ];
        }
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── Listar roles de laboratorio ──────────────────────────────────────
    case 'listar_roles':
        $roles = $model->listarRoles();
        echo json_encode(['success' => true, 'data' => $roles]);
        break;

    // ── Permisos de un rol específico ────────────────────────────────────
    case 'permisos_rol':
        $id_rol = intval($_GET['id_rol'] ?? 0);
        if ($id_rol <= 0) {
            echo json_encode(['success' => false, 'message' => 'id_rol inválido.']);
            break;
        }
        $permisos    = $model->obtenerPermisosRol($id_rol);
        $submodulos  = $model->listarSubmodulos();
        echo json_encode(['success' => true, 'permisos' => $permisos, 'submodulos' => $submodulos]);
        break;

    // ── Asignar / quitar rol a usuario ───────────────────────────────────
    case 'asignar_rol':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
        }
        $id_usuario = intval($_POST['id_usuario'] ?? 0);
        $id_rol     = intval($_POST['id_rol'] ?? 0); // 0 = quitar rol

        if ($id_usuario <= 0) {
            echo json_encode(['success' => false, 'message' => 'id_usuario inválido.']);
            break;
        }

        try {
            $model->asignarRolUsuario($id_usuario, $id_rol > 0 ? $id_rol : null, $usuarioId);
            $msg = $id_rol > 0 ? 'Rol asignado correctamente.' : 'Rol removido correctamente.';
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            error_log('RolesAPI asignar_rol: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── Crear nuevo rol ──────────────────────────────────────────────────
    case 'crear_rol':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
        }
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        if ($nombre === '') {
            echo json_encode(['success' => false, 'message' => 'El nombre del rol es obligatorio.']);
            break;
        }
        try {
            $nuevoId = $model->crearRol($nombre, $descripcion, $usuarioId);
            echo json_encode(['success' => true, 'message' => 'Rol creado correctamente.', 'id_rol' => $nuevoId]);
        } catch (Exception $e) {
            error_log('RolesAPI crear_rol: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al crear el rol.']);
        }
        break;

    // ── Guardar permisos de un rol ───────────────────────────────────────
    case 'guardar_permisos':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
        }
        $id_rol = intval($_POST['id_rol'] ?? 0);
        if ($id_rol <= 0) {
            echo json_encode(['success' => false, 'message' => 'id_rol inválido.']);
            break;
        }
        $rawPermisos = $_POST['permisos'] ?? '[]';
        $permisos    = json_decode($rawPermisos, true);
        if (!is_array($permisos)) {
            echo json_encode(['success' => false, 'message' => 'Datos de permisos inválidos.']);
            break;
        }

        try {
            $model->guardarPermisosRol($id_rol, $permisos, $usuarioId);
            echo json_encode(['success' => true, 'message' => 'Permisos guardados correctamente.']);
        } catch (Exception $e) {
            error_log('RolesAPI guardar_permisos: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al guardar permisos.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operación desconocida.']);
        break;
}
exit;
