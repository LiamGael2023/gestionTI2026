<?php
/**
 * modules/chatbot/ajax/chavibot.ajax.php
 *
 * Acepta llamadas de DOS orígenes:
 *   1. Navegador web  → tiene $_SESSION['usuario_id']
 *   2. Node.js (WA)   → envía node_token en el POST
 *
 * IMPORTANTE: node_token se verifica ANTES de la sesión PHP
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Cargar db.php ─────────────────────────────────────────────────────────
$posiblesDb = [
    dirname(__DIR__, 3) . '/config/db.php',
    dirname(__DIR__, 2) . '/config/db.php',
    dirname(__DIR__)    . '/config/db.php',
];
$dbPath = null;
foreach ($posiblesDb as $r) { if (file_exists($r)) { $dbPath = $r; break; } }
if (!$dbPath) {
    echo json_encode(['error'=>true,'mensaje'=>'db.php no encontrado','rutas'=>$posiblesDb]);
    exit;
}
require_once $dbPath;

// ── Cargar whatsapp.php (define WA_NODE_TOKEN) ────────────────────────────
$waPath = dirname(__DIR__) . '/config/whatsapp.php';
if (file_exists($waPath)) require_once $waPath;
if (!defined('WA_NODE_TOKEN'))    define('WA_NODE_TOKEN',    'chavibot_node_2026');
if (!defined('API_PERSONAL_URL')) define('API_PERSONAL_URL', '');

// ── Verificar origen ──────────────────────────────────────────────────────
$nodeToken = trim($_POST['node_token'] ?? '');
$esNode    = ($nodeToken !== '' && $nodeToken === WA_NODE_TOKEN);

if (!$esNode) {
    // Llamada web: requiere sesión PHP
    // Auth.php guarda: $_SESSION['usuario_id']
    $uid = intval($_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 0);
    if ($uid === 0) {
        http_response_code(403);
        echo json_encode([
            'error'        => true,
            'code'         => 'access_denied',
            'mensaje'      => 'Sesión no válida.',
            'session_keys' => array_keys($_SESSION),
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

switch ($accion) {

    // ══════════════════════════════════════════════════════════════════════
    // ACCIONES WEB
    // ══════════════════════════════════════════════════════════════════════
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
        require_once dirname(__DIR__) . '/models/ChavibotModel.php';
        $perfil = ChavibotModel::mdlObtenerPerfil();
        $conn   = Conexion::conectar();
        echo json_encode([
            'ok'           => true,
            'es_node'      => $esNode,
            'perfil'       => $perfil,
            'sql_ok'       => ($conn !== false),
            'session'      => $_SESSION,
            'db_path'      => $dbPath,
        ]);
        if ($conn) sqlsrv_close($conn);
        break;

    // ══════════════════════════════════════════════════════════════════════
    // ACCIONES NODE.JS (WhatsApp)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * LOGIN WhatsApp: verifica usuario+contraseña contra comun.Usuarios
     * Igual que AuthController.php pero para WhatsApp
     */
    case 'wa_login':
        if (!$esNode) { echo json_encode(['error'=>true,'usuario'=>null]); break; }

        $usuarioInput   = trim($_POST['usuario']    ?? '');
        $contrasenaInput= trim($_POST['contrasena'] ?? '');

        if (!$usuarioInput || !$contrasenaInput) {
            echo json_encode(['error'=>true,'usuario'=>null,'mensaje'=>'Datos incompletos']);
            break;
        }

        $conn = Conexion::conectar();
        if (!$conn) {
            echo json_encode(['error'=>true,'usuario'=>null,'mensaje'=>'Error de BD']);
            break;
        }

        // Misma query que AuthModel.php
        $sql  = "SELECT id_usuario, usuario, contrasenia, nombres, apellidos, rol, sede_id
                 FROM [comun].[Usuarios]
                 WHERE usuario = ? AND activo = 1";
        $stmt = sqlsrv_query($conn, $sql, [[$usuarioInput, SQLSRV_PARAM_IN]]);
        $user = null;

        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row && password_verify($contrasenaInput, $row['contrasenia'])) {
                $user = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);

        if (!$user) {
            echo json_encode([
                'error'   => true,
                'usuario' => null,
                'mensaje' => 'Usuario o contraseña incorrectos',
            ]);
            break;
        }

        echo json_encode([
            'error'   => false,
            'usuario' => [
                'id_usuario' => $user['id_usuario'],
                'usuario'    => $user['usuario'],
                'nombres'    => $user['nombres'],
                'apellidos'  => $user['apellidos'] ?? '',
                'rol'        => strtolower($user['rol'] ?? 'usuario'),
                'area'       => '',  // se puede extender si tienes área en la tabla
            ],
        ]);
        break;

    /**
     * RESPONDER: procesa una pregunta y devuelve respuesta de Ollama + BD
     */
    case 'wa_responder':
        if (!$esNode) { echo json_encode(['error'=>true]); break; }

        $telefono = trim($_POST['telefono'] ?? '');
        $mensaje  = trim($_POST['mensaje']  ?? '');
        if (!$telefono || !$mensaje) {
            echo json_encode(['error'=>true,'respuesta'=>'Datos incompletos']); break;
        }

        require_once dirname(__DIR__) . '/models/ChavibotModel.php';

        // Construir sesionId estable para historial
        $sessionId = trim($_POST['sessionId'] ?? '') ?: ('wa_' . $telefono);

        $datosWA = [
            'telefono'   => $telefono,
            'sessionId'  => $sessionId,
            'idUsuario'  => intval($_POST['idUsuario'] ?? 0),
            'dni'        => '',
            'nombres'    => trim($_POST['nombres']   ?? '') . ' ' . trim($_POST['apellidos'] ?? ''),
            'apellidos'  => trim($_POST['apellidos'] ?? ''),
            'rol'        => trim($_POST['rol']       ?? 'usuario'),
            'area'       => trim($_POST['area']      ?? ''),
            'autenticado'=> true,
        ];

        $perfil = ChavibotModel::mdlObtenerPerfil($datosWA);
        $inicio = microtime(true);
        $hist   = ChavibotModel::mdlObtenerHistorial($perfil['sessionId'], 4);
        $ejes   = ChavibotModel::mdlBuscarRAG($mensaje, $perfil);
        $ej     = $ejes[0] ?? null;

        $datos = []; $schema = '';
        if ($ej && !empty($ej['sqlQuery'])) {
            $datos  = ChavibotModel::mdlEjecutarQueryRAG($ej['sqlQuery'], $perfil);
            $schema = $ej['schemaObjetivo'] ?? '';
        }

        $resp = ChavibotController::_promptYOllama($mensaje, $perfil, $hist, $ej, $datos);
        $resp = ChavibotController::_limpiarWA($resp);
        $ms   = intval((microtime(true) - $inicio) * 1000);

        ChavibotModel::mdlGuardarHistorial([
            'sessionId' => $perfil['sessionId'],
            'idUsuario' => $perfil['idUsuario'],
            'dni'       => '',
            'nombres'   => $perfil['nombres'],
            'rol'       => $perfil['rol'],
            'area'      => $perfil['area'],
            'pregunta'  => $mensaje,
            'respuesta' => $resp,
            'schema'    => $schema,
            'filas'     => count($datos),
            'tiempoMs'  => $ms,
            'canal'     => 'whatsapp',
            'telefono'  => $telefono,
        ]);

        echo json_encode(['error'=>false,'respuesta'=>$resp,'ms'=>$ms]);
        break;

    /**
     * REGISTRAR: registra sesión WA autenticada en BD
     */
    case 'wa_registrar':
        if (!$esNode) { echo json_encode(['error'=>true]); break; }
        require_once dirname(__DIR__) . '/models/ChavibotModel.php';
        $tel = trim($_POST['telefono'] ?? '');
        if ($tel) {
            ChavibotModel::mdlActualizarSesionWA($tel, [
                'autenticado' => 1,
                'pasoAuth'    => 'autenticado',
                'idUsuario'   => intval($_POST['idUsuario'] ?? 0),
                'nombres'     => trim($_POST['nombres']     ?? ''),
                'apellidos'   => trim($_POST['apellidos']   ?? ''),
                'rol'         => trim($_POST['rol']         ?? 'usuario'),
                'area'        => trim($_POST['area']        ?? ''),
            ]);
        }
        echo json_encode(['error'=>false,'ok'=>true]);
        break;

    /**
     * AGREGAR RAG: desde WhatsApp (admin/tecnico)
     */
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
        echo json_encode(['error'=>true,'mensaje'=>"Acción '$accion' desconocida."]);
        break;
}
exit;
