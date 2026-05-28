<?php
// Prevenir cualquier output antes de headers
ob_start();

try {
    $base_path = dirname(dirname(dirname(__DIR__)));
    
    if (!isset($conn) || !$conn) {
        require_once $base_path . '/config/db.php';
        require_once $base_path . '/core/Auth.php';
        Auth::check();
        $conn = Conexion::conectar();
    }
    
    require_once __DIR__ . '/../models/ChatToolsModel.php';
    $toolsModel = new ChatToolsModel($conn);
    
    $action = $_GET['action'] ?? 'index';
    
    // ============================================================
    // SYSTEM PROMPT CON TOOLS
    // ============================================================
    $SYSTEM_PROMPT_TOOLS = 'Eres un asistente virtual del Sistema de Gestión TI del Proyecto Especial Chavimochic (PECH). ' .
        'Puedes consultar la base de datos usando herramientas especializadas. ' .
        'Cuando un usuario te pida información que requiera datos de la base de datos (stock, ventas, proformas, vouchers, productos, clientes, mermas), ' .
        'debes responder EXACTAMENTE con un JSON en este formato:' . "\n" .
        '{"tool": "nombre_de_la_tool", "params": {"param1": "valor1", "param2": "valor2"}}' . "\n\n" .
        'REGLAS ESTRICTAS PARA TOOLS:' . "\n" .
        '- NO expliques tu razonamiento.' . "\n" .
        '- NO uses markdown, code blocks, ni texto adicional.' . "\n" .
        '- NO saludes ni te despidas cuando emites un tool call.' . "\n" .
        '- EMITE SOLAMENTE el JSON raw, sin comillas triples, sin backticks.' . "\n" .
        '- Si el usuario pregunta sobre donaciones, usa la tool consultar_ventas con metodo_pago = DONACION.' . "\n\n" .
        'Las herramientas disponibles son:' . "\n" .
        '1. consultar_stock: Stock actual de productos. Params: producto (nombre o vacio), clase (nombre o vacio), centro (nombre o vacio).' . "\n" .
        '2. consultar_ventas: Ventas y donaciones por período. Params: fecha_desde (YYYY-MM-DD o vacio), fecha_hasta (YYYY-MM-DD o vacio), estado (PROCESADO/PENDIENTE/RECHAZADO o vacio), cliente (nombre o vacio), metodo_pago (VENTA/DONACION o vacio).' . "\n" .
        '3. consultar_proformas: Proformas registradas. Params: estado (PENDIENTE/PROCESADO/RECHAZADO o vacio), cliente (nombre o vacio), fecha_desde (YYYY-MM-DD o vacio).' . "\n" .
        '4. consultar_vouchers: Vouchers de depósito. Params: fecha_desde (YYYY-MM-DD o vacio), fecha_hasta (YYYY-MM-DD o vacio), asignado (si/no/vacio).' . "\n" .
        '5. consultar_productos: Catálogo con precios vigentes. Params: clase (nombre o vacio), centro (nombre o vacio), tipo_precio (Fijo/UIT/Variable o vacio), nombre (nombre o vacio).' . "\n" .
        '6. consultar_clientes: Directorio de clientes. Params: nombre (nombre o vacio), tipo (Planilla/Externo o vacio).' . "\n" .
        '7. consultar_mermas: Pérdidas de stock. Params: fecha_desde (YYYY-MM-DD o vacio), fecha_hasta (YYYY-MM-DD o vacio), producto (nombre o vacio).' . "\n\n" .
        'IMPORTANTE: Si la consulta NO requiere datos de base de datos (saludos, despedidas, preguntas generales), responde normalmente sin JSON.' . "\n" .
        'Si necesitas datos de la BD, emite SOLO el JSON, nada más.';
    
    // ============================================================
    // HELPER: Llamar a la API de OpenCode Zen
    // ============================================================
    function llamarOpenCodeZen($messages, $apiKey, $model) {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 1024
        ];
        
        $ch = curl_init('https://opencode.ai/zen/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log('[Chatbot] cURL Error: ' . $curlError);
            return ['error' => 'Error cURL: ' . $curlError];
        }
        
        if ($httpCode !== 200) {
            error_log('[Chatbot] HTTP Error ' . $httpCode . ': ' . $response);
            $errorData = json_decode($response, true);
            $errorMsg = 'Error del servicio de IA';
            if ($httpCode == 401 && isset($errorData['error']['type']) && $errorData['error']['type'] === 'CreditsError') {
                $errorMsg = 'Sin créditos disponibles en la cuenta de OpenCode. Por favor recarga tu saldo en opencode.ai/billing';
            } elseif (isset($errorData['error']['message'])) {
                $errorMsg = $errorData['error']['message'];
            }
            return ['error' => $errorMsg];
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[Chatbot] JSON Parse Error: ' . json_last_error_msg());
            return ['error' => 'Error al procesar respuesta del servicio'];
        }
        
        $choice = $data['choices'][0] ?? null;
        $message = $choice['message'] ?? null;
        $content = '';
        
        if (!empty($message['content'])) {
            $content = $message['content'];
        } elseif (!empty($message['reasoning_content'])) {
            $content = $message['reasoning_content'];
        }
        
        return ['content' => $content];
    }
    
    // ============================================================
    // HELPER: Detectar y ejecutar tool call
    // ============================================================
    function procesarToolCall($content, $toolsModel) {
        $content = trim($content);
        
        $json = null;
        
        // Intento 1: El contenido completo es JSON
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['tool'])) {
            $json = $decoded;
        }
        
        // Intento 2: Extraer JSON embebido en texto (modelos de razonamiento a veces escriben párrafos + JSON)
        if ($json === null) {
            // Buscar un objeto JSON que tenga "tool" dentro del texto
            if (preg_match('/\{\s*"tool"\s*:[^}]+\}/s', $content, $matches)) {
                $decoded = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['tool'])) {
                    $json = $decoded;
                }
            }
        }
        
        // Intento 3: Buscar cualquier objeto JSON profundo en el texto
        if ($json === null) {
            if (preg_match('/\{[\s\S]*?"tool"[\s\S]*?\}/s', $content, $matches)) {
                // Intentar con el match más largo posible (el más profundo)
                $candidate = $matches[0];
                // A veces hay objetos anidados; intentar parsear
                $decoded = json_decode($candidate, true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['tool'])) {
                    $json = $decoded;
                }
            }
        }
        
        if ($json === null || empty($json['tool']) || !isset($json['params'])) {
            return null; // No es un tool call válido
        }
        
        $tool = $json['tool'];
        $params = $json['params'] ?? [];
        
        error_log('[Chatbot] Tool call detectado: ' . $tool . ' | params: ' . json_encode($params));
        
        $resultado = null;
        switch ($tool) {
            case 'consultar_stock':
                $resultado = $toolsModel->consultarStock($params);
                break;
            case 'consultar_ventas':
                $resultado = $toolsModel->consultarVentas($params);
                break;
            case 'consultar_proformas':
                $resultado = $toolsModel->consultarProformas($params);
                break;
            case 'consultar_vouchers':
                $resultado = $toolsModel->consultarVouchers($params);
                break;
            case 'consultar_productos':
                $resultado = $toolsModel->consultarProductos($params);
                break;
            case 'consultar_clientes':
                $resultado = $toolsModel->consultarClientes($params);
                break;
            case 'consultar_mermas':
                $resultado = $toolsModel->consultarMermas($params);
                break;
            default:
                return ['error' => 'Tool desconocida: ' . $tool];
        }
        
        if (isset($resultado['error'])) {
            return ['error' => $resultado['error']];
        }
        
        return [
            'tool' => $tool,
            'resultado' => $toolsModel->formatearResultado($tool, $resultado),
            'resultado_raw' => $toolsModel->formatearResultadoRaw($resultado)
        ];
    }
    
    // ========================================
    // ENDPOINT AJAX: Chat principal con tools
    // ========================================
    if ($action === 'chat_enviar') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $mensaje = trim($input['mensaje'] ?? '');
        $historial = $input['historial'] ?? [];
        
        if (empty($mensaje)) {
            echo json_encode(['success' => false, 'message' => 'El mensaje está vacío']);
            exit;
        }
        
        $apiKey = env('OPENCODE_API_KEY');
        $model = env('OPENCODE_MODEL', 'deepseek-v4-flash-free');
        
        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'API key no configurada']);
            exit;
        }
        
        // Preparar mensajes para la API
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $GLOBALS['SYSTEM_PROMPT_TOOLS']];
        
        // Historial previo (limitado a últimos 8 mensajes)
        $historialReciente = array_slice($historial, -8);
        foreach ($historialReciente as $h) {
            if (!empty($h['role']) && !empty($h['content'])) {
                $messages[] = ['role' => $h['role'], 'content' => $h['content']];
            }
        }
        
        $messages[] = ['role' => 'user', 'content' => $mensaje];
        
        // === LLAMADA 1: El modelo decide si necesita una tool ===
        $respuesta1 = llamarOpenCodeZen($messages, $apiKey, $model);
        
        if (isset($respuesta1['error'])) {
            echo json_encode(['success' => false, 'message' => $respuesta1['error']]);
            exit;
        }
        
        $content1 = $respuesta1['content'];
        
        // Intentar detectar si es un tool call
        $toolResult = procesarToolCall($content1, $toolsModel);
        
        if ($toolResult !== null) {
            // Hubo un tool call
            if (isset($toolResult['error'])) {
                echo json_encode(['success' => false, 'message' => $toolResult['error']]);
                exit;
            }
            
            // === LLAMADA 2: Reenviar resultado al modelo para que responda al usuario ===
            $messages[] = ['role' => 'assistant', 'content' => $content1];
            $messages[] = ['role' => 'user', 'content' => 'Resultado de la consulta:\n' . $toolResult['resultado'] . '\n\nPor favor, resume estos datos de manera clara y concisa para el usuario. Si hay muchos registros, destaca los más relevantes.'];
            
            $respuesta2 = llamarOpenCodeZen($messages, $apiKey, $model);
            
            if (isset($respuesta2['error'])) {
                echo json_encode(['success' => false, 'message' => $respuesta2['error']]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'respuesta' => $respuesta2['content'],
                'modelo' => $model,
                'tool_usada' => $toolResult['tool'],
                'resultado_raw' => $toolResult['resultado_raw']
            ]);
            exit;
        }
        
        // No fue tool call, responder directamente
        echo json_encode([
            'success' => true,
            'respuesta' => $content1,
            'modelo' => $model,
            'resultado_raw' => null
        ]);
        exit;
    }
    
    // ========================================
    // ENDPOINTS SEPARADOS PARA CADA TOOL
    // ========================================
    
    function toolResponse($toolResult) {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        
        if (isset($toolResult['error'])) {
            echo json_encode(['success' => false, 'message' => $toolResult['error']]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $toolResult
        ]);
        exit;
    }
    
    if ($action === 'tool_stock') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarStock($params));
    }
    
    if ($action === 'tool_ventas') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarVentas($params));
    }
    
    if ($action === 'tool_proformas') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarProformas($params));
    }
    
    if ($action === 'tool_vouchers') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarVouchers($params));
    }
    
    if ($action === 'tool_productos') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarProductos($params));
    }
    
    if ($action === 'tool_clientes') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarClientes($params));
    }
    
    if ($action === 'tool_mermas') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarMermas($params));
    }
    
    // ========================================
    // VISTA PRINCIPAL
    // ========================================
    ob_clean();
    include __DIR__ . '/../views/index.php';
    
} catch (Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
