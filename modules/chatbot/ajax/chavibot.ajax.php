<?php
/**
 * chavibot.ajax.php — Endpoint unificado ChaviBot
 * Acciones web:       responder, feedback, agregar_rag, listar_rag, toggle_rag, diagnostico
 * Acciones WhatsApp:  wa_login_dni, wa_responder, wa_registrar, wa_agregar_rag
 *
 * CAMBIOS v2:
 *  - wa_login_dni: autentica solo por DNI (sin contraseña)
 *  - Carga permisos reales de comun.Permisos → comun.Modulos
 *  - El perfil incluye array $permisos con los módulos que puede ver el usuario
 *  - El bot filtra el menú y las consultas según esos permisos
 */

// ── Rutas de includes ──────────────────────────────────────────────────────
$root = dirname(__DIR__, 3);   // llega a gestionTI/
$posiblesDb = [
    $root . '/config/db.php',
    dirname(__DIR__, 2) . '/config/db.php',
    __DIR__ . '/../../../config/db.php',
];
$dbCargado = false;
foreach ($posiblesDb as $ruta) {
    if (file_exists($ruta)) { require_once $ruta; $dbCargado = true; break; }
}
if (!$dbCargado) {
    echo json_encode(['error' => true, 'mensaje' => 'db.php no encontrado']);
    exit;
}

require_once __DIR__ . '/../controllers/ChatbotController.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'mensaje' => 'Solo POST.']); exit;
}

$accion = trim($_POST['accion'] ?? '');

// ══════════════════════════════════════════════════════════════════════════
// TOKEN NODE.JS — verificar ANTES que la sesión PHP
// ══════════════════════════════════════════════════════════════════════════
$accionesWA = ['wa_login_dni', 'wa_responder', 'wa_registrar', 'wa_agregar_rag'];
if (in_array($accion, $accionesWA)) {
    require_once __DIR__ . '/../config/whatsapp.php';
    $tokenEnviado = trim($_POST['node_token'] ?? '');
    if ($tokenEnviado !== WA_NODE_TOKEN) {
        http_response_code(403);
        echo json_encode(['error' => true, 'mensaje' => 'Token inválido.']); exit;
    }
}

// ══════════════════════════════════════════════════════════════════════════
// ACCIONES
// ══════════════════════════════════════════════════════════════════════════
switch ($accion) {

    // ── Chat web ─────────────────────────────────────────────────────────
    case 'responder':
        session_start();
        $msg = trim($_POST['mensaje'] ?? '');
        if (!$msg) { echo json_encode(['error'=>true,'respuesta'=>'Mensaje vacío.']); break; }
        echo json_encode(ChavibotController::ctrResponder($msg));
        break;

    case 'feedback':
        session_start();
        echo json_encode(ChavibotController::ctrFeedback());
        break;

    case 'agregar_rag':
        session_start();
        echo json_encode(ChavibotController::ctrAgregarRAG());
        break;

    case 'listar_rag':
        session_start();
        echo json_encode(ChavibotController::ctrListarRAG());
        break;

    case 'toggle_rag':
        session_start();
        echo json_encode(ChavibotController::ctrToggleRAG());
        break;

    // ── WhatsApp: LOGIN POR DNI ──────────────────────────────────────────
    case 'wa_login_dni':
        $dni      = trim($_POST['dni']      ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (!$dni || !$telefono) {
            echo json_encode(['error'=>true,'mensaje'=>'DNI y teléfono requeridos.']); break;
        }

        $conn = Conexion::conectar();
        if (!$conn) {
            echo json_encode(['error'=>true,'mensaje'=>'Sin conexión a BD.']); break;
        }

        // 1. Buscar usuario activo por DNI (campo documento en comun.Usuarios)
        $sql = "SELECT id_usuario, nombres, apellidos, rol, id_rol, usuario, documento
                FROM comun.Usuarios
                WHERE documento = ? AND activo = 1";
        $stmt = sqlsrv_query($conn, $sql, [[$dni, SQLSRV_PARAM_IN]]);

        if (!$stmt || !($usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            sqlsrv_close($conn);
            echo json_encode(['error'=>true,'usuario'=>null,'mensaje'=>'DNI no encontrado o usuario inactivo.']);
            break;
        }
        sqlsrv_free_stmt($stmt);

        $idUsuario = intval($usuario['id_usuario']);

        // 2. Cargar permisos reales de comun.Permisos → comun.Modulos
        $permisos = _cargarPermisos($conn, $idUsuario);

        sqlsrv_close($conn);

        // 3. Determinar rol efectivo
        $rolEfectivo = strtolower(trim($usuario['rol'] ?? 'usuario'));

        echo json_encode([
            'error'   => false,
            'usuario' => [
                'id_usuario' => $idUsuario,
                'nombres'    => $usuario['nombres']  ?? '',
                'apellidos'  => $usuario['apellidos'] ?? '',
                'rol'        => $rolEfectivo,
                'usuario'    => $usuario['usuario']   ?? '',
                'dni'        => $usuario['documento'] ?? $dni,
                'permisos'   => $permisos,   // ← array de nombres de módulo que puede ver
            ],
        ]);
        break;

    // ── WhatsApp: RESPONDER ──────────────────────────────────────────────
    case 'wa_responder':
        $telefono = trim($_POST['telefono'] ?? '');
        $mensaje  = trim($_POST['mensaje']  ?? '');
        if (!$telefono || !$mensaje) {
            echo json_encode(['error'=>true,'respuesta'=>'Datos incompletos.']); break;
        }
        $datosWA = [
            'telefono'   => $telefono,
            'idUsuario'  => intval($_POST['idUsuario']  ?? 0),
            'nombres'    => trim($_POST['nombres']       ?? ''),
            'apellidos'  => trim($_POST['apellidos']     ?? ''),
            'rol'        => trim($_POST['rol']           ?? 'usuario'),
            'area'       => trim($_POST['area']          ?? ''),
            'sessionId'  => trim($_POST['sessionId']     ?? 'wa_'.$telefono),
            'dni'        => trim($_POST['dni']           ?? ''),
            'permisos'   => json_decode($_POST['permisos'] ?? '[]', true),
            'autenticado'=> true,
            'canal'      => 'whatsapp',
        ];
        require_once __DIR__ . '/../controllers/ChavibotController.php';
        $perfil = ChavibotModel::mdlObtenerPerfil($datosWA);
        $hist   = ChavibotModel::mdlObtenerHistorial($perfil['sessionId'], 4);
        $ejes   = ChavibotModel::mdlBuscarRAG($mensaje, $perfil);
        $ej     = $ejes[0] ?? null;
        $datos  = [];
        if ($ej && !empty($ej['sqlQuery'])) {
            $datos = ChavibotModel::mdlEjecutarQueryRAG($ej['sqlQuery'], $perfil);
        }
        $resp = ChavibotController::_promptYOllama($mensaje, $perfil, $hist, $ej, $datos);
        $resp = ChavibotController::_limpiarWA($resp);
        $ms   = 0;
        $id   = ChavibotModel::mdlGuardarHistorial([
            'sessionId' => $perfil['sessionId'], 'idUsuario' => $perfil['idUsuario'],
            'dni'       => $perfil['dni'],        'nombres'  => $perfil['nombres'],
            'rol'       => $perfil['rol'],        'area'     => $perfil['area'],
            'pregunta'  => $mensaje,              'respuesta'=> $resp,
            'schema'    => $ej['schemaObjetivo'] ?? '', 'filas' => count($datos),
            'tiempoMs'  => $ms, 'canal' => 'whatsapp', 'telefono' => $telefono,
        ]);
        echo json_encode(['respuesta'=>$resp,'idMensaje'=>$id]);
        break;

    // ── WhatsApp: REGISTRAR SESIÓN ────────────────────────────────────────
    case 'wa_registrar':
        echo json_encode(['ok'=>true]);
        break;

    // ── WhatsApp: AGREGAR RAG ─────────────────────────────────────────────
    case 'wa_agregar_rag':
        $rol = strtolower(trim($_POST['rol'] ?? ''));
        if (!in_array($rol, ['admin','tecnico'])) {
            echo json_encode(['error'=>true,'mensaje'=>'Sin permisos.']); break;
        }
        require_once __DIR__ . '/../controllers/ChavibotController.php';
        $d = [
            'pregunta' => trim($_POST['pregunta'] ?? ''),
            'palabras' => trim($_POST['palabras'] ?? ''),
            'schema'   => trim($_POST['schema']   ?? ''),
            'sql'      => trim($_POST['sql']      ?? ''),
            'respBase' => trim($_POST['respBase'] ?? ''),
            'rol'      => '', 'area' => '', 'canal' => 'ambos',
            'idUsuario'=> 0,
        ];
        if (!$d['pregunta'] || !$d['sql']) {
            echo json_encode(['error'=>true,'mensaje'=>'Pregunta y SQL obligatorios.']); break;
        }
        $id = ChavibotModel::mdlAgregarRAG($d);
        echo json_encode(['error'=>($id===0),'id'=>$id]);
        break;

    default:
        echo json_encode(['error'=>true,'mensaje'=>"Acción desconocida: {$accion}"]);
}

// ══════════════════════════════════════════════════════════════════════════
// FUNCIÓN: cargar permisos reales del usuario desde comun.Permisos
// Devuelve array con nombres de módulos que el usuario puede_ver
// ══════════════════════════════════════════════════════════════════════════
function _cargarPermisos($conn, int $idUsuario): array
{
    $sql = "
        SELECT m.nombre AS modulo
        FROM comun.Permisos p
        INNER JOIN comun.Modulos m ON p.id_modulo = m.id_modulo
        WHERE p.id_usuario = ?
          AND p.pueden_ver = 1
        ORDER BY m.orden ASC
    ";
    $stmt = sqlsrv_query($conn, $sql, [[$idUsuario, SQLSRV_PARAM_IN]]);
    $modulos = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $modulos[] = strtolower($row['modulo']);
        }
        sqlsrv_free_stmt($stmt);
    }
    return $modulos;
}
