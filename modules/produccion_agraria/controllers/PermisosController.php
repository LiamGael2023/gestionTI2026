<?php
// PermisosController — Gestión de Roles y Permisos de Producción Agraria.
// Solo administradores (comun) tienen acceso a este submódulo.
try {
    $base_path = dirname(dirname(dirname(__DIR__)));

    if (!isset($conn) || !$conn) {
        require_once $base_path . '/config/db.php';
        require_once $base_path . '/core/Auth.php';
        Auth::check();
        $conn = Conexion::conectar();
    }

    require_once __DIR__ . '/../models/PermisosModel.php';

    $permisosModel = new PermisosModel($conn);
    $usuarioId     = intval($_SESSION['usuario_id'] ?? 0);

    // El submódulo de permisos es exclusivo para administradores
    if (!$permisosModel->esAdministrador($usuarioId)) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo administradores.']);
        exit;
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? 'index';

    // ========================================
    // ACCIONES AJAX/JSON
    // ========================================

    if (in_array($action, ['listar_roles_pa', 'listar_usuarios_pa', 'permisos_rol_pa', 'asignar_rol_pa', 'crear_rol_pa', 'eliminar_rol_pa', 'guardar_permisos_pa'])) {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');

        switch ($action) {
            case 'listar_roles_pa':
                echo json_encode(['success' => true, 'data' => $permisosModel->listarRoles()]);
                break;

            case 'listar_usuarios_pa':
                $usuarios = $permisosModel->listarUsuariosProduccion();
                $data = [];
                foreach ($usuarios as $u) {
                    $data[] = [
                        'id_usuario'       => $u['id_usuario'],
                        'nombres'          => $u['nombres'],
                        'apellidos'        => $u['apellidos'],
                        'usuario'          => $u['usuario'],
                        'correo'           => $u['correo'],
                        'rol_comun'        => $u['Rol_Comun'],
                        'id_rol_pa'        => $u['Id_Rol_PA'],
                        'rol_pa'           => $u['Rol_PA'] ?? '—',
                        'fecha_asignacion' => $u['Fecha_Asignacion']
                            ? (is_object($u['Fecha_Asignacion'])
                                ? $u['Fecha_Asignacion']->format('d/m/Y')
                                : substr((string)$u['Fecha_Asignacion'], 0, 10))
                            : null,
                    ];
                }
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'permisos_rol_pa':
                $id_rol = intval($_GET['id_rol'] ?? 0);
                if ($id_rol <= 0) {
                    echo json_encode(['success' => false, 'message' => 'id_rol inválido.']);
                    break;
                }
                echo json_encode([
                    'success'    => true,
                    'permisos'   => $permisosModel->obtenerPermisosRol($id_rol),
                    'submodulos' => $permisosModel->listarSubmodulos(),
                ]);
                break;

            case 'asignar_rol_pa':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
                $id_usuario = intval($_POST['id_usuario'] ?? 0);
                $id_rol     = intval($_POST['id_rol'] ?? 0);
                if ($id_usuario <= 0) {
                    echo json_encode(['success' => false, 'message' => 'id_usuario inválido.']);
                    break;
                }
                try {
                    $permisosModel->asignarRolUsuario($id_usuario, $id_rol > 0 ? $id_rol : null, $usuarioId);
                    echo json_encode(['success' => true, 'message' => $id_rol > 0 ? 'Rol asignado correctamente.' : 'Rol removido correctamente.']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'crear_rol_pa':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
                $nombre      = trim($_POST['nombre'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');
                if ($nombre === '') {
                    echo json_encode(['success' => false, 'message' => 'El nombre del rol es obligatorio.']);
                    break;
                }
                try {
                    $nuevoId = $permisosModel->crearRol($nombre, $descripcion, $usuarioId);
                    echo json_encode(['success' => true, 'message' => 'Rol creado correctamente.', 'id_rol' => $nuevoId]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error al crear el rol.']);
                }
                break;

            case 'eliminar_rol_pa':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
                $id_rol = intval($_POST['id_rol'] ?? 0);
                if ($id_rol <= 0) {
                    echo json_encode(['success' => false, 'message' => 'id_rol inválido.']);
                    break;
                }
                try {
                    $permisosModel->eliminarRol($id_rol);
                    echo json_encode(['success' => true, 'message' => 'Rol eliminado correctamente.']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar el rol.']);
                }
                break;

            case 'guardar_permisos_pa':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
                $id_rol = intval($_POST['id_rol'] ?? 0);
                if ($id_rol <= 0) {
                    echo json_encode(['success' => false, 'message' => 'id_rol inválido.']);
                    break;
                }
                $permisos = json_decode($_POST['permisos'] ?? '[]', true);
                if (!is_array($permisos)) {
                    echo json_encode(['success' => false, 'message' => 'Datos de permisos inválidos.']);
                    break;
                }
                try {
                    $permisosModel->guardarPermisosRol($id_rol, $permisos, $usuarioId);
                    echo json_encode(['success' => true, 'message' => 'Permisos guardados correctamente.']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error al guardar permisos.']);
                }
                break;
        }
        exit;
    }

    // ========================================
    // VISTA (index / permisos)
    // ========================================
    include __DIR__ . '/../views/permisos/index.php';

} catch (Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    exit;
}