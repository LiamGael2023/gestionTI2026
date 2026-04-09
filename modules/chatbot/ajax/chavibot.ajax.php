<?php
/**
 * modules/chatbot/ajax/chavibot.ajax.php
 *
 * Acepta llamadas de DOS orígenes:
 *   1. Navegador web  → tiene $_SESSION['usuario_id']
 *   2. Node.js (WA)   → envía node_token en el POST
 *
 * IMPORTANTE: verificar node_token ANTES de verificar la sesión
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

// ── PASO 1: Leer el token que envía Node.js ───────────────────────────────
$nodeTokenRecibido = trim($_POST['node_token'] ?? '');

// ── PASO 2: Cargar db.php y whatsapp.php ─────────────────────────────────
$posiblesDb = [
    dirname(__DIR__, 3) . '/config/db.php',  // raíz/config/db.php  ← el correcto
    dirname(__DIR__, 2) . '/config/db.php',
    dirname(__DIR__)    . '/config/db.php',
];
$dbPath = null;
foreach ($posiblesDb as $ruta) {
    if (file_exists($ruta)) { $dbPath = $ruta; break; }
}
if (!$dbPath) {
    echo json_encode(['error'=>true,'mensaje'=>'db.php no encontrado','rutas'=>$posiblesDb]);
    exit;
}
require_once $dbPath;

// Cargar whatsapp.php que define WA_NODE_TOKEN
$waPath = dirname(__DIR__) . '/config/whatsapp.php';
if (file_exists($waPath)) {
    require_once $waPath;
}
// Si no existe, definir token por defecto
if (!defined('WA_NODE_TOKEN'))    define('WA_NODE_TOKEN',    'chavibot_node_2026');
if (!defined('API_PERSONAL_URL')) define('API_PERSONAL_URL', 'https://www.chavimochic.gob.pe/api_incidencias/api_personal.php');

// ── PASO 3: Determinar si viene de Node.js o del navegador ────────────────
$esNode = ($nodeTokenRecibido !== '' && $nodeTokenRecibido === WA_NODE_TOKEN);

// ── PASO 4: Autorización ──────────────────────────────────────────────────
if (!$esNode) {
    // Llamada desde el navegador: verificar sesión PHP
    // Auth.php del sistema guarda exactamente: $_SESSION['usuario_id']
    $uid = intval($_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 0);
    if ($uid === 0) {
        http_response_code(403);
        echo json_encode([
            'error'        => true,
            'code'         => 'access_denied',
            'mensaje'      => 'Sesión no válida. Recarga e inicia sesión.',
            'session_keys' => array_keys($_SESSION), // debug — quitar en producción
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error'=>true,'mensaje'=>'Solo POST.']);
    exit;
}

require_once dirname(__DIR__) . '/controllers/ChavibotController.php';

$accion = trim($_POST['accion'] ?? '');

// ══════════════════════════════════════════════════════════════════════════
// ACCIONES WEB (desde el navegador)
// ══════════════════════════════════════════════════════════════════════════
switch ($accion) {

    case 'responder':
        $msg = trim($_POST['mensaje'] ?? '');
        if (!$msg) { echo json_encode(['error'=>true,'respuesta'=>'Mensaje vacío.']); break; }
        echo json_encode(ChavibotController::ctrResponder($msg));
        break;

    case 'feedback':
        echo json_encode(ChavibotController::ctrFeedback());
        break;

    case 'agregar_rag':
        echo json_encode(ChavibotController::ctrAgregarRAG());
        break;

    case 'listar_rag':
        echo json_encode(ChavibotController::ctrListarRAG());
        break;

    case 'toggle_rag':
        echo json_encode(ChavibotController::ctrToggleRAG());
        break;

    case 'diagnostico':
        $perfil = ChavibotModel::mdlObtenerPerfil();
        $conn   = Conexion::conectar();
        echo json_encode([
            'ok'           => true,
            'es_node'      => $esNode,
            'perfil'       => $perfil,
            'sql_ok'       => ($conn !== false),
            'session'      => $_SESSION,
            'wa_token_ok'  => defined('WA_NODE_TOKEN'),
            'db_path'      => $dbPath,
        ]);
        if ($conn) sqlsrv_close($conn);
        break;

    // ══════════════════════════════════════════════════════════════════════
    // ACCIONES DE NODE.JS (WhatsApp con Baileys)
    // ══════════════════════════════════════════════════════════════════════

    case 'wa_buscar_dni':
        if (!$esNode) {
            echo json_encode(['error'=>true,'mensaje'=>'No autorizado.']);
            break;
        }
        $dni = preg_replace('/\D/', '', $_POST['dni'] ?? '');
        if (strlen($dni) !== 8) {
            echo json_encode(['error'=>true,'persona'=>null,'mensaje'=>'DNI inválido']);
            break;
        }

        require_once dirname(__DIR__) . '/models/ChavibotModel.php';
        $data = ChavibotModel::mdlBuscarPersonalPorDni($dni);

        if (empty($data)) {
            echo json_encode(['error'=>true,'persona'=>null,'mensaje'=>'No encontrado']);
            break;
        }

        // Normalizar respuesta de la API
        $p = is_array($data[0] ?? null) ? $data[0] : $data;

        // Obtener rol desde comun.Usuarios por documento
        $rol  = 'usuario';
        $conn = Conexion::conectar();
        if ($conn) {
            $stmt = sqlsrv_query($conn,
                "SELECT LOWER(u.rol) AS rol FROM [comun].[Usuarios] u
                 WHERE u.documento = ? AND u.activo = 1",
                [[$dni, SQLSRV_PARAM_IN]]
            );
            if ($stmt) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($row) $rol = $row['rol'] ?? 'usuario';
                sqlsrv_free_stmt($stmt);
            }
            sqlsrv_close($conn);
        }

        echo json_encode([
            'error'   => false,
            'persona' => [
                'nombres'   => $p['nombres']   ?? $p['NOMBRES']   ?? '',
                'apellidos' => $p['apellidos'] ?? $p['APELLIDOS'] ?? '',
                'area'      => $p['area']       ?? $p['AREA']     ?? '',
                'rol'       => $rol,
            ],
        ]);
        break;

    case 'wa_responder':
        if (!$esNode) { echo json_encode(['error'=>true]); break; }

        $telefono = trim($_POST['telefono'] ?? '');
        $mensaje  = trim($_POST['mensaje']  ?? '');
        if (!$telefono || !$mensaje) {
            echo json_encode(['error'=>true,'respuesta'=>'Datos incompletos']); break;
        }

        require_once dirname(__DIR__) . '/models/ChavibotModel.php';

        $sesionWA = ChavibotModel::mdlObtenerSesionWA($telefono);
        $datosWA  = [
            'telefono'    => $telefono,
            'sessionId'   => $sesionWA['sessionId'] ?? ('wa_' . $telefono),
            'idUsuario'   => intval($sesionWA['idUsuario'] ?? 0),
            'dni'         => trim($_POST['dni']       ?? ''),
            'nombres'     => trim($_POST['nombres']   ?? '') . ' ' . trim($_POST['apellidos'] ?? ''),
            'apellidos'   => trim($_POST['apellidos'] ?? ''),
            'rol'         => trim($_POST['rol']       ?? 'usuario'),
            'area'        => trim($_POST['area']      ?? ''),
            'autenticado' => true,
        ];

        $perfil  = ChavibotModel::mdlObtenerPerfil($datosWA);
        $inicio  = microtime(true);
        $hist    = ChavibotModel::mdlObtenerHistorial($perfil['sessionId'], 4);
        $ejes    = ChavibotModel::mdlBuscarRAG($mensaje, $perfil);
        $ej      = $ejes[0] ?? null;

        $datos = []; $schema = '';
        if ($ej && !empty($ej['sqlQuery'])) {
            $datos  = ChavibotModel::mdlEjecutarQueryRAG($ej['sqlQuery'], $perfil);
            $schema = $ej['schemaObjetivo'] ?? '';
        }

        $resp = ChavibotController::_promptYOllama($mensaje, $perfil, $hist, $ej, $datos);
        $resp = ChavibotController::_limpiarWA($resp);

        $ms = intval((microtime(true) - $inicio) * 1000);
        ChavibotModel::mdlGuardarHistorial([
            'sessionId'=>$perfil['sessionId'], 'idUsuario'=>$perfil['idUsuario'],
            'dni'=>$perfil['dni'],             'nombres'=>$perfil['nombres'],
            'rol'=>$perfil['rol'],             'area'=>$perfil['area'],
            'pregunta'=>$mensaje,              'respuesta'=>$resp,
            'schema'=>$schema,                 'filas'=>count($datos),
            'tiempoMs'=>$ms,                   'canal'=>'whatsapp',
            'telefono'=>$telefono,
        ]);
        ChavibotModel::mdlActualizarSesionWA($telefono);

        echo json_encode(['error'=>false,'respuesta'=>$resp,'ms'=>$ms]);
        break;

    case 'wa_registrar':
        if (!$esNode) { echo json_encode(['error'=>true]); break; }
        require_once dirname(__DIR__) . '/models/ChavibotModel.php';
        $tel = trim($_POST['telefono'] ?? '');
        if ($tel) {
            ChavibotModel::mdlActualizarSesionWA($tel, [
                'autenticado' => 1,
                'pasoAuth'    => 'autenticado',
                'dni'         => trim($_POST['dni']       ?? ''),
                'nombres'     => trim($_POST['nombres']   ?? ''),
                'apellidos'   => trim($_POST['apellidos'] ?? ''),
                'rol'         => trim($_POST['rol']       ?? 'usuario'),
                'area'        => trim($_POST['area']      ?? ''),
            ]);
        }
        echo json_encode(['error'=>false,'ok'=>true]);
        break;

    case 'wa_agregar_rag':
        if (!$esNode) { echo json_encode(['error'=>true]); break; }
        require_once dirname(__DIR__) . '/models/ChavibotModel.php';
        $rol = strtolower(trim($_POST['rol'] ?? ''));
        if (!in_array($rol, ['admin','tecnico'])) {
            echo json_encode(['error'=>true,'mensaje'=>'Sin permisos.']); break;
        }
        $id = ChavibotModel::mdlAgregarRAG([
            'pregunta'  => trim($_POST['pregunta'] ?? ''),
            'palabras'  => trim($_POST['palabras'] ?? ''),
            'schema'    => trim($_POST['schema']   ?? ''),
            'sql'       => trim($_POST['sql']      ?? ''),
            'respBase'  => trim($_POST['respBase'] ?? ''),
            'rol'=>'','area'=>'','canal'=>'ambos','idUsuario'=>0,
        ]);
        echo json_encode(['error'=>($id===0),'id'=>$id]);
        break;

    default:
        echo json_encode(['error'=>true,'mensaje'=>"Acción '{$accion}' no reconocida."]);
        break;
}
exit;
