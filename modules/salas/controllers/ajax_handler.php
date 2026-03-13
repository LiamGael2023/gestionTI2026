<?php
/**
 * ajax_handler.php
 * Manejador de peticiones AJAX/JSON del módulo de Gestión de Reservas de Sala.
 * Todas las respuestas son en formato JSON.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
ob_start(); // Captura cualquier salida espuria (warnings, notices) para no corromper el JSON

// --- Bootstrap mínimo ---
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../models/SalasModel.php';

header('Content-Type: application/json; charset=UTF-8');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: solo usuarios autenticados (RNF-04)
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado.']);
    exit();
}

$conn       = Conexion::conectar();
$model      = new SalasModel($conn);
$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$id_usuario = (int) $_SESSION['usuario_id'];
$rol        = $_SESSION['usuario_rol_nombre'] ?? '';

// Fallback al campo texto legacy con normalización
if (empty($rol)) {
    $rol_legacy = $_SESSION['usuario_rol'] ?? '';
    $rol = ($rol_legacy === 'ADMIN') ? SalasModel::ROL_ADMINISTRADOR : $rol_legacy;
}

// ============================================================================
// AUTO-CANCELACIÓN: reservas PENDIENTE cuya hora de inicio ya pasó
// Se ejecuta en cada petición AJAX del módulo (sin cron job).
// ============================================================================
$model->cancelarReservasVencidas();

// Helper para responder JSON y terminar
function responder(array $data, int $codigo = 200): void
{
    ob_clean(); // Descartar cualquier salida previa (warnings/notices) antes del JSON
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Helper para validar campos obligatorios en POST
function validarCampos(array $campos, array $datos): ?string
{
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || trim($datos[$campo]) === '') {
            return "El campo '{$campo}' es obligatorio.";
        }
    }
    return null;
}

// Valida una foto de sala por tamaño, extensión y firma binaria real.
// Devuelve [ok, msg, ext_final]. ext_final es jpg/png/webp cuando ok=true.
function validarFotoSala(array $file): array
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return [false, 'Archivo inválido o no subido correctamente.', null];
    }

    $maxSize = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxSize) {
        return [false, 'La imagen debe tener un tamaño válido y no superar 5 MB.', null];
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $mapExt = [
        'jpg'  => 'jpg',
        'jpeg' => 'jpg',
        'png'  => 'png',
        'webp' => 'webp',
    ];
    if (!isset($mapExt[$ext])) {
        return [false, 'Formato no permitido. Solo JPG, PNG o WebP.', null];
    }

    $fh = @fopen($file['tmp_name'], 'rb');
    if ($fh === false) {
        return [false, 'No se pudo leer el archivo subido.', null];
    }
    $head = (string) fread($fh, 12);
    fclose($fh);

    $tipoReal = null;
    if (strlen($head) >= 3 && ord($head[0]) === 0xFF && ord($head[1]) === 0xD8 && ord($head[2]) === 0xFF) {
        $tipoReal = 'jpg';
    } elseif (strlen($head) >= 8 &&
        ord($head[0]) === 0x89 && $head[1] === 'P' && $head[2] === 'N' && $head[3] === 'G' &&
        ord($head[4]) === 0x0D && ord($head[5]) === 0x0A && ord($head[6]) === 0x1A && ord($head[7]) === 0x0A) {
        $tipoReal = 'png';
    } elseif (strlen($head) >= 12 && substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP') {
        $tipoReal = 'webp';
    }

    if ($tipoReal === null) {
        return [false, 'El archivo no corresponde a una imagen JPG, PNG o WebP válida.', null];
    }

    $extFinal = $mapExt[$ext];
    if ($tipoReal !== $extFinal) {
        return [false, 'La extensión no coincide con el contenido real del archivo.', null];
    }

    return [true, '', $extFinal];
}

// ============================================================================
// DISPATCHER
// ============================================================================
switch ($action) {

    // ------------------------------------------------------------------------
    // SEDES — Sección pública (todos los roles autenticados)
    // ------------------------------------------------------------------------
    case 'getSedes':
        responder(['ok' => true, 'data' => $model->getSedes()]);

    // ------------------------------------------------------------------------
    // SALAS — Por sede
    // ------------------------------------------------------------------------
    case 'getSalasBySede':
        $id_sede = (int) ($_GET['id_sede'] ?? $_POST['id_sede'] ?? 0);
        if ($id_sede <= 0) responder(['ok' => false, 'msg' => 'id_sede requerido.'], 400);
        responder(['ok' => true, 'data' => $model->getSalasBySede($id_sede)]);

    // ------------------------------------------------------------------------
    // EQUIPOS — Por sala
    // ------------------------------------------------------------------------
    case 'getEquiposBySala':
        $id_sala = (int) ($_GET['id_sala'] ?? $_POST['id_sala'] ?? 0);
        if ($id_sala <= 0) responder(['ok' => false, 'msg' => 'id_sala requerido.'], 400);
        responder(['ok' => true, 'data' => $model->getEquiposBySala($id_sala)]);

    // ------------------------------------------------------------------------
    // DISPONIBILIDAD — Verificar un rango horario
    // ------------------------------------------------------------------------
    case 'verificarDisponibilidad':
        $error = validarCampos(['id_sala', 'fecha', 'hora_inicio', 'hora_fin'], $_POST);
        if ($error) responder(['ok' => false, 'msg' => $error], 400);

        $excluir = (int) ($_POST['excluir_id'] ?? 0);
        $result  = $model->verificarDisponibilidad(
            (int) $_POST['id_sala'],
            $_POST['fecha'],
            $_POST['hora_inicio'],
            $_POST['hora_fin'],
            $excluir
        );
        responder(['ok' => true, 'data' => $result]);

    // ------------------------------------------------------------------------
    // DISPONIBILIDAD — Eventos para FullCalendar (vista de reserva)
    // ------------------------------------------------------------------------
    case 'getEventosCalendar':
        $id_sala     = (int) ($_GET['id_sala'] ?? 0);
        $fecha_inicio = $_GET['start'] ?? date('Y-m-01');
        $fecha_fin    = $_GET['end']   ?? date('Y-m-t');
        if ($id_sala <= 0) responder(['ok' => true, 'data' => []]);
        responder(['ok' => true, 'data' => $model->getEventosCalendar($id_sala, $fecha_inicio, $fecha_fin)]);

    // ------------------------------------------------------------------------
    // CRONOGRAMA SEMANAL — Todos los roles (RF-A09)
    // ------------------------------------------------------------------------
    case 'getEventosCronograma':
        $fecha_inicio = $_GET['start']   ?? date('Y-m-d', strtotime('monday this week'));
        $fecha_fin    = $_GET['end']     ?? date('Y-m-d', strtotime('sunday this week'));
        $id_sede      = isset($_GET['id_sede']) && $_GET['id_sede'] !== '' ? (int)$_GET['id_sede'] : null;
        $id_sala      = isset($_GET['id_sala']) && $_GET['id_sala'] !== '' ? (int)$_GET['id_sala'] : null;
        responder(['ok' => true, 'data' => $model->getEventosCronograma($fecha_inicio, $fecha_fin, $id_sede, $id_sala)]);

    // ------------------------------------------------------------------------
    // CREAR RESERVA (RF-S04)
    // ------------------------------------------------------------------------
    case 'crearReserva':
        $error = validarCampos(['id_sala', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'], $_POST);
        if ($error) responder(['ok' => false, 'msg' => $error], 400);

        // Verificar disponibilidad antes de crear (RF-G02)
        $disp = $model->verificarDisponibilidad(
            (int) $_POST['id_sala'],
            $_POST['fecha'],
            $_POST['hora_inicio'],
            $_POST['hora_fin']
        );
        if (!$disp['disponible']) {
            responder(['ok' => false, 'msg' => $disp['mensaje']]);
        }

        // Normalizar equipos seleccionados
        $equipos = [];
        if (!empty($_POST['equipos'])) {
            $equipos = is_array($_POST['equipos'])
                ? array_map('intval', $_POST['equipos'])
                : array_filter(array_map('intval', explode(',', $_POST['equipos'])));
        }

        $datos = [
            'id_usuario_solicitante' => $id_usuario,
            'id_sala'                => (int) $_POST['id_sala'],
            'fecha'                  => $_POST['fecha'],
            'hora_inicio'            => $_POST['hora_inicio'],
            'hora_fin'               => $_POST['hora_fin'],
            'motivo'                 => trim($_POST['motivo']),
            'equipos'                => $equipos,
        ];

        $id_nuevo = $model->crearReserva($datos);
        if ($id_nuevo === false) {
            responder(['ok' => false, 'msg' => 'Error al registrar la solicitud.'], 500);
        }
        responder(['ok' => true, 'msg' => 'Solicitud registrada correctamente. Estado: PENDIENTE.', 'id_reserva' => $id_nuevo]);

    // ------------------------------------------------------------------------
    // MIS RESERVAS (RF-S05)
    // ------------------------------------------------------------------------
    case 'getMisReservas':
        responder(['ok' => true, 'data' => $model->getMisReservas($id_usuario)]);

    // ------------------------------------------------------------------------
    // DETALLE DE RESERVA
    // ------------------------------------------------------------------------
    case 'getReservaDetalle':
        $id_reserva  = (int) ($_GET['id_reserva'] ?? $_POST['id_reserva'] ?? 0);
        if ($id_reserva <= 0) responder(['ok' => false, 'msg' => 'id_reserva requerido.'], 400);

        // Los autorizadores/admin pueden ver cualquier reserva; solicitante solo las propias
        $filtro_usuario = SalasModel::esAutorizadorOAdmin($rol) ? 0 : $id_usuario;
        $detalle = $model->getReservaDetalle($id_reserva, $filtro_usuario);

        if (!$detalle) responder(['ok' => false, 'msg' => 'Reserva no encontrada.'], 404);
        responder(['ok' => true, 'data' => $detalle]);

    // ------------------------------------------------------------------------
    // EDITAR RESERVA — Solo PENDIENTE, solo el solicitante (RF-S07, RF-G04)
    // ------------------------------------------------------------------------
    case 'editarReserva':
        $error = validarCampos(['id_reserva', 'id_sala', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'], $_POST);
        if ($error) responder(['ok' => false, 'msg' => $error], 400);

        $equipos = [];
        if (!empty($_POST['equipos'])) {
            $equipos = is_array($_POST['equipos'])
                ? array_map('intval', $_POST['equipos'])
                : array_filter(array_map('intval', explode(',', $_POST['equipos'])));
        }

        $datos = [
            'id_sala'     => (int) $_POST['id_sala'],
            'fecha'       => $_POST['fecha'],
            'hora_inicio' => $_POST['hora_inicio'],
            'hora_fin'    => $_POST['hora_fin'],
            'motivo'      => trim($_POST['motivo']),
            'equipos'     => $equipos,
        ];

        $result = $model->editarReserva((int) $_POST['id_reserva'], $datos, $id_usuario);
        responder($result, $result['ok'] ? 200 : 400);

    // ------------------------------------------------------------------------
    // CANCELAR RESERVA — Solo PENDIENTE, solo el solicitante (RF-S06, RF-G04)
    // ------------------------------------------------------------------------
    case 'cancelarReserva':
        $id_reserva = (int) ($_POST['id_reserva'] ?? 0);
        if ($id_reserva <= 0) responder(['ok' => false, 'msg' => 'id_reserva requerido.'], 400);
        $result = $model->cancelarReserva($id_reserva, $id_usuario);
        responder($result, $result['ok'] ? 200 : 400);

    // ------------------------------------------------------------------------
    // PENDIENTES DE AUTORIZACIÓN (RF-A03)
    // ------------------------------------------------------------------------
    case 'getPendientes':
        if (!SalasModel::esAutorizadorOAdmin($rol)) {
            responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        }
        responder(['ok' => true, 'data' => $model->getReservasPendientes()]);

    // ------------------------------------------------------------------------
    // APROBAR RESERVA (RF-A05, RF-G01, RF-G02, RF-G03)
    // ------------------------------------------------------------------------
    case 'aprobarReserva':
        if (!SalasModel::esAutorizadorOAdmin($rol)) {
            responder(['ok' => false, 'msg' => 'Sin permisos para aprobar reservas.'], 403);
        }
        $id_reserva = (int) ($_POST['id_reserva'] ?? 0);
        if ($id_reserva <= 0) responder(['ok' => false, 'msg' => 'id_reserva requerido.'], 400);
        $result = $model->aprobarReserva($id_reserva, $id_usuario);
        responder($result, $result['ok'] ? 200 : 400);

    // ------------------------------------------------------------------------
    // RECHAZAR RESERVA (RF-A06, RF-G03)
    // ------------------------------------------------------------------------
    case 'rechazarReserva':
        if (!SalasModel::esAutorizadorOAdmin($rol)) {
            responder(['ok' => false, 'msg' => 'Sin permisos para rechazar reservas.'], 403);
        }
        $id_reserva  = (int) ($_POST['id_reserva'] ?? 0);
        $observacion = trim($_POST['observacion'] ?? '');
        if ($id_reserva <= 0) responder(['ok' => false, 'msg' => 'id_reserva requerido.'], 400);
        $result = $model->rechazarReserva($id_reserva, $id_usuario, $observacion);
        responder($result, $result['ok'] ? 200 : 400);

    // ------------------------------------------------------------------------
    // HISTORIAL (RF-A07)
    // ------------------------------------------------------------------------
    case 'getHistorial':
        if (!SalasModel::esAutorizadorOAdmin($rol)) {
            responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        }
        $filtros = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'id_sala'     => $_GET['id_sala']     ?? '',
            'id_sede'     => $_GET['id_sede']     ?? '',
            'estado'      => $_GET['estado']      ?? '',
        ];
        responder(['ok' => true, 'data' => $model->getHistorial(array_filter($filtros))]);

    // ------------------------------------------------------------------------
    // HISTORIAL DE UNA RESERVA ESPECÍFICA
    // ------------------------------------------------------------------------
    case 'getHistorialReserva':
        $id_reserva = (int) ($_GET['id_reserva'] ?? 0);
        if ($id_reserva <= 0) responder(['ok' => false, 'msg' => 'id_reserva requerido.'], 400);
        responder(['ok' => true, 'data' => $model->getHistorialByReserva($id_reserva)]);

    // ========================================================================
    // ADMINISTRACIÓN — Solo Administrador
    // ========================================================================

    // ------------------------------------------------------------------------
    // GESTIÓN DE SEDES (RF-AD04)
    // ------------------------------------------------------------------------
    case 'getAllSedes':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        responder(['ok' => true, 'data' => $model->getAllSedes()]);

    case 'guardarSede':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        $error = validarCampos(['nombre'], $_POST);
        if ($error) responder(['ok' => false, 'msg' => $error], 400);
        $id = $model->guardarSede($_POST);
        if ($id === false) responder(['ok' => false, 'msg' => 'Error al guardar la sede.'], 500);
        responder(['ok' => true, 'msg' => 'Sede guardada correctamente.', 'id' => $id]);

    case 'toggleSede':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        $id     = (int) ($_POST['id'] ?? 0);
        $activo = (int) ($_POST['activo'] ?? 0);
        if ($id <= 0) responder(['ok' => false, 'msg' => 'id requerido.'], 400);
        $ok = $model->toggleSede($id, $activo);
        responder(['ok' => $ok, 'msg' => $ok ? 'Estado actualizado.' : 'Error al actualizar.']);

    // ------------------------------------------------------------------------
    // GESTIÓN DE SALAS (RF-AD05, RF-AD06)
    // ------------------------------------------------------------------------
    case 'getAllSalas':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        responder(['ok' => true, 'data' => $model->getAllSalas()]);

    case 'guardarSala':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        $error = validarCampos(['nombre', 'capacidad', 'id_sede'], $_POST);
        if ($error) responder(['ok' => false, 'msg' => $error], 400);
        $id = $model->guardarSala($_POST);
        if ($id === false) responder(['ok' => false, 'msg' => 'Error al guardar la sala. Verifique que la capacidad sea mayor a cero.'], 500);

        // Procesar foto si se incluyó en la misma petición
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['foto'];
            [$okFoto, $msgFoto, $extFinal] = validarFotoSala($file);
            if (!$okFoto) {
                responder(['ok' => false, 'msg' => $msgFoto], 400);
            }

            $nombre   = 'sala_' . $id . '.' . $extFinal;
            $carpeta  = realpath(__DIR__ . '/../assets') . DIRECTORY_SEPARATOR . 'salas' . DIRECTORY_SEPARATOR;
            if (!is_dir($carpeta)) mkdir($carpeta, 0775, true);

            foreach (['jpg', 'png', 'webp'] as $e) {
                $prev = $carpeta . 'sala_' . $id . '.' . $e;
                if (file_exists($prev)) unlink($prev);
            }

            if (!move_uploaded_file($file['tmp_name'], $carpeta . $nombre)) {
                responder(['ok' => false, 'msg' => 'Error al guardar la imagen en el servidor.'], 500);
            }

            $model->guardarFotoSala($id, $nombre);
        }

        responder(['ok' => true, 'msg' => 'Sala guardada correctamente.', 'id' => $id]);

    case 'toggleSala':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        $id     = (int) ($_POST['id_sala'] ?? 0);
        $activo = (int) ($_POST['activo'] ?? 0);
        if ($id <= 0) responder(['ok' => false, 'msg' => 'id_sala requerido.'], 400);
        $ok = $model->toggleSala($id, $activo);
        responder(['ok' => $ok, 'msg' => $ok ? 'Estado actualizado.' : 'Error al actualizar.']);

    // ------------------------------------------------------------------------
    // GESTIÓN DE EQUIPOS AV (RF-AD07, RF-AD08, RF-AD09)
    // ------------------------------------------------------------------------
    case 'getAllEquipos':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        responder(['ok' => true, 'data' => $model->getAllEquipos()]);

    case 'guardarEquipo':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        $error = validarCampos(['nombre', 'tipo', 'id_sala'], $_POST);
        if ($error) responder(['ok' => false, 'msg' => $error], 400);
        $id = $model->guardarEquipo($_POST);
        if ($id === false) responder(['ok' => false, 'msg' => 'Error al guardar el equipo.'], 500);
        responder(['ok' => true, 'msg' => 'Equipo guardado correctamente.', 'id' => $id]);

    case 'toggleEquipo':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        $id     = (int) ($_POST['id_equipo'] ?? 0);
        $activo = (int) ($_POST['activo'] ?? 0);
        if ($id <= 0) responder(['ok' => false, 'msg' => 'id_equipo requerido.'], 400);
        $ok = $model->toggleEquipo($id, $activo);
        responder(['ok' => $ok, 'msg' => $ok ? 'Estado actualizado.' : 'Error al actualizar.']);

    // ------------------------------------------------------------------------
    // ESTADÍSTICAS — Dashboard
    // ------------------------------------------------------------------------
    case 'getEstadisticas':
        if (SalasModel::esAutorizadorOAdmin($rol)) {
            responder(['ok' => true, 'data' => $model->getEstadisticasGlobales()]);
        }
        responder(['ok' => true, 'data' => $model->getEstadisticasSolicitante($id_usuario)]);

    // ------------------------------------------------------------------------
    // SUBIR FOTO DE SALA (RF-AD05)
    // ------------------------------------------------------------------------
    case 'subirFotoSala':
        if (!SalasModel::esAdmin($rol)) responder(['ok' => false, 'msg' => 'Sin permisos.'], 403);
        $id_sala = (int) ($_POST['id_sala'] ?? 0);
        if ($id_sala <= 0) responder(['ok' => false, 'msg' => 'id_sala requerido.'], 400);

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            responder(['ok' => false, 'msg' => 'No se recibió ningún archivo de imagen.'], 400);
        }

        $file    = $_FILES['foto'];
        [$okFoto2, $msgFoto2, $extFinal2] = validarFotoSala($file);
        if (!$okFoto2) responder(['ok' => false, 'msg' => $msgFoto2], 400);

        $nombre2   = 'sala_' . $id_sala . '.' . $extFinal2;
        $carpeta2  = realpath(__DIR__ . '/../assets') . DIRECTORY_SEPARATOR . 'salas' . DIRECTORY_SEPARATOR;
        if (!is_dir($carpeta2)) mkdir($carpeta2, 0775, true);

        foreach (['jpg', 'png', 'webp'] as $e) {
            $prev = $carpeta2 . 'sala_' . $id_sala . '.' . $e;
            if (file_exists($prev)) unlink($prev);
        }

        if (!move_uploaded_file($file['tmp_name'], $carpeta2 . $nombre2)) {
            responder(['ok' => false, 'msg' => 'Error al guardar la imagen en el servidor.'], 500);
        }

        $ok = $model->guardarFotoSala($id_sala, $nombre2);
        if (!$ok) responder(['ok' => false, 'msg' => 'Imagen guardada pero no se pudo registrar en la base de datos.'], 500);
        responder(['ok' => true, 'msg' => 'Fotografía guardada correctamente.', 'foto_ruta' => $nombre2]);

    // ------------------------------------------------------------------------
    // Acción desconocida
    // ------------------------------------------------------------------------
    default:
        responder(['ok' => false, 'msg' => "Acción '{$action}' no reconocida."], 400);
}
