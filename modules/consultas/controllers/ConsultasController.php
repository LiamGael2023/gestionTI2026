<?php
/**
 * ConsultasController - Asistente Virtual IA con Tool Calling
 * 
 * Mejoras 2026-06-04:
 * - SSL verification habilitada (con fallback por env)
 * - Rate limiting (20 req/min por sesión)
 * - Fallback cuando el tool call JSON no se puede parsear
 * - Historial incluye tool calls para mejor contexto
 * - Validación de tool contra lista blanca
 */
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
    // RATE LIMITING (20 req/min por sesión)
    // ============================================================
    function verificarRateLimit() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $limite = 20;
        $ventana = 60;
        $ahora = time();
        $key = 'chat_rate_limit';
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['ventana_inicio' => $ahora, 'contador' => 0];
        }
        
        $rl = &$_SESSION[$key];
        if ($ahora - $rl['ventana_inicio'] > $ventana) {
            $rl['ventana_inicio'] = $ahora;
            $rl['contador'] = 0;
        }
        
        $rl['contador']++;
        if ($rl['contador'] > $limite) {
            $restante = $ventana - ($ahora - $rl['ventana_inicio']);
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Demasiadas consultas. Espera ' . $restante . ' segundos.',
                'rate_limit' => true,
                'retry_after' => $restante
            ]);
            exit;
        }
    }
    
    // Lista blanca de tools válidas
    $TOOLS_VALIDAS = [
        'consultar_stock', 'consultar_ventas', 'consultar_proformas',
        'consultar_vouchers', 'consultar_productos', 'consultar_clientes',
        'consultar_mermas', 'consultar_kardex', 'consultar_top_productos_vendidos',
        'consultar_valorizacion_inventario', 'consultar_ventas_por_mes', 'consultar_vouchers_saldo',
        'consultar_grafico', 'consultar_resumen', 'consultar_comparativa',
        'consultar_buscar', 'consultar_recomendaciones'
    ];
    
    // ============================================================
    // SYSTEM PROMPT CON TOOLS
    // ============================================================
    $fechaHoy = date('Y-m-d');
    $SYSTEM_PROMPT_TOOLS = 'Eres un asistente virtual del Sistema de Gestion TI del Proyecto Especial Chavimochic (PECH), ' .
        'Gobierno Regional La Libertad. La fecha actual es ' . $fechaHoy . '. ' .
        'El sistema gestiona: inventario de productos agricolas, lotes FIFO, ventas, proformas, vouchers, ' .
        'clientes, precios (UIT/Variable/Fijo), mermas, kardex, graficos y reportes.' . "\n\n" .
        'CONSULTAS CON DATOS: cuando el usuario pida informacion que requiera datos de la BD, ' .
        'responde UNICA Y EXCLUSIVAMENTE con un JSON, sin ningun otro texto. ' .
        'Formato UNA tool: {"tool":"nombre_tool","params":{"p1":"v1"}}' . "\n" .
        'Formato VARIAS tools: [{"tool":"t1","params":{...}},{"tool":"t2","params":{...}}]' . "\n\n" .
        'PROHIBIDO TERMINANTEMENTE:' . "\n" .
        '- NO emitas analisis, reflexiones, ni pensamientos.' . "\n" .
        '- NO digas "veamos", "necesito", "voy a", "debo", "analizando", "el usuario quiere".' . "\n" .
        '- NO escribas parrafos explicando lo que vas a hacer.' . "\n" .
        '- NO uses frases como "interpretare", "asumire", "podria", "considerare".' . "\n" .
        '- Si necesitas datos: SOLO el JSON. Si no necesitas datos: SOLO la respuesta final.' . "\n" .
        '- NO combines analisis con JSON ni analisis con respuesta.' . "\n\n" .
        'FECHAS en lenguaje natural: interpreta "hoy" como ' . $fechaHoy . ', "ayer" como ' . date('Y-m-d', strtotime('-1 day')) . ', ' .
        '"este mes" desde ' . date('Y-m-01') . ' hasta ' . $fechaHoy . ', ' .
        '"mes pasado" como el mes anterior completo, "esta semana" desde el lunes hasta hoy, ' .
        '"ultimo trimestre" como los ultimos 90 dias, "este año" desde ' . date('Y-01-01') . ' hasta ' . $fechaHoy . '. ' .
        'Siempre usa formato YYYY-MM-DD en los params de fecha.' . "\n\n" .
        'AYUDA CONTEXTUAL: Si el usuario pregunta COMO hacer algo en el sistema (ej: "como registro una merma?", "como funciona el FIFO?", ' .
        '"que es un voucher?"), responde con instrucciones claras paso a paso SIN usar tools. ' .
        'Conoces estos procesos del sistema: inventario CRUD con imagenes BLOB, punto de venta con descuento FIFO, ' .
        'proformas pendientes que se procesan en la bandeja, vouchers de deposito con archivo PDF, ' .
        'mermas por cantidad exacta o porcentaje, tipos de precio (UIT=valor_uit*porcentaje, Variable=ultimo historial, Fijo=manual), ' .
        'exportacion PDF/Excel desde la vista de detalle de producto.' . "\n\n" .
        'Las herramientas disponibles son:' . "\n" .
        '1. consultar_stock: Stock actual. Params: producto, clase, centro.' . "\n" .
        '2. consultar_ventas: Ventas/donaciones. Params: fecha_desde, fecha_hasta, estado, cliente, metodo_pago.' . "\n" .
        '3. consultar_proformas: Proformas. Params: estado, cliente, fecha_desde.' . "\n" .
        '4. consultar_vouchers: Vouchers deposito. Params: fecha_desde, fecha_hasta, asignado (si/no).' . "\n" .
        '5. consultar_productos: Catalogo con precios. Params: clase, centro, tipo_precio, nombre.' . "\n" .
        '6. consultar_clientes: Directorio clientes. Params: nombre, tipo (Planilla/Externo).' . "\n" .
        '7. consultar_mermas: Perdidas. Params: fecha_desde, fecha_hasta, producto.' . "\n" .
        '8. consultar_kardex: Movimientos inventario. Params: producto, tipo_movimiento, fecha_desde, fecha_hasta.' . "\n" .
        '9. consultar_top_productos_vendidos: Ranking productos. Params: fecha_desde, fecha_hasta, centro, orden (cantidad/monto), limite.' . "\n" .
        '10. consultar_valorizacion_inventario: Valor monetario stock. Params: centro, clase, producto.' . "\n" .
        '11. consultar_ventas_por_mes: Tendencia mensual. Params: meses (1-24), centro, metodo_pago.' . "\n" .
        '12. consultar_vouchers_saldo: Saldos vouchers. Params: fecha_desde, fecha_hasta, saldo_estado (positivo/cero).' . "\n" .
        '13. consultar_grafico: Graficos. Params: tipo (ventas_mes/top_productos/stock_centro/ventas_metodo_pago/valorizacion_clase/mermas_mes/ventas_vs_donaciones), centro, fecha_desde, fecha_hasta, limite.' . "\n" .
        '14. consultar_resumen: Resumen ejecutivo diario (ventas hoy, proformas pendientes, stock critico, vouchers sin asignar, mermas, valor inventario). Sin params. ' .
        'Usala para preguntas como "como va el dia?", "resumen", "dashboard".' . "\n" .
        '15. consultar_comparativa: Compara dos periodos. Params: tipo (ventas/mermas/ingresos), periodo1_desde, periodo1_hasta, periodo2_desde, periodo2_hasta. ' .
        'Usala para "compara este mes vs mes pasado", "diferencia vs año anterior".' . "\n" .
        '16. consultar_buscar: Busqueda global en productos, clientes, vouchers y lotes. Params: q (termino de busqueda). ' .
        'Usala para "busca todo sobre...", "encuentra...", "donde aparece...".' . "\n" .
        '17. consultar_recomendaciones: Analisis inteligente (stock critico, clientes inactivos, alta merma, proformas antiguas). Sin params. ' .
        'Usala para "que debo atender?", "recomendaciones", "alertas", "problemas".' . "\n\n" .
        'IMPORTANTE: Si la consulta NO requiere datos, responde normalmente sin JSON.' . "\n" .
        'Si necesitas datos de la BD, emite SOLO el JSON (objeto o array), nada mas.';
    
    // ============================================================
    // HELPER: Llamar a la API de chat (OpenCode Zen o DeepSeek directo)
    // ============================================================
    function llamarChatAPI($messages, $config) {
        $service = $config['service'] ?? 'opencode';
        
        if ($service === 'deepseek') {
            $endpoint = 'https://api.deepseek.com/chat/completions';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_key']
            ];
            $model = $config['model'] ?? 'deepseek-chat';
        } else {
            // OpenCode Go (antes Zen) — endpoint recomendado para API keys de Go
            $endpoint = 'https://opencode.ai/zen/go/v1/chat/completions';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_key']
            ];
            $model = $config['model'] ?? 'deepseek-v4-flash';
        }
        
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 1024
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $sslVerify = (env('CHAT_SSL_VERIFY', 'true') !== 'false');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log('[Chatbot] cURL Error: ' . $curlError);
            return ['error' => 'Error de conexion: ' . $curlError];
        }
        
        if ($httpCode !== 200) {
            error_log('[Chatbot] HTTP ' . $httpCode . ': ' . $response);
            $errorData = json_decode($response, true);
            $errorMsg = 'Error ' . $httpCode . ' del servicio IA';
            
            if (isset($errorData['error']['message'])) {
                $errorMsg = $errorData['error']['message'];
            }
            if ($httpCode == 401) {
                $errorMsg = 'Error de autenticacion (401). Verifica tu API key. ' . ($errorData['error']['message'] ?? '');
            }
            if ($httpCode == 402) {
                $errorMsg = 'Sin creditos disponibles. Recarga saldo en ' . ($service === 'deepseek' ? 'platform.deepseek.com' : 'opencode.ai/billing');
            }
            if ($httpCode == 429) {
                $errorMsg = 'Demasiadas solicitudes. Espera un momento.';
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
    // HELPER: Detectar si un texto parece un tool call fallido
    // ============================================================
    function pareceToolCallFallido($content) {
        $content = trim($content);
        $lower = strtolower($content);
        // Contiene palabras clave de tool call pero no es JSON valido
        $hasToolKeywords = (
            strpos($lower, '"tool"') !== false || strpos($lower, '"params"') !== false ||
            strpos($lower, 'consultar_') !== false || strpos($lower, 'grafico') !== false
        );
        // Parece analisis/razonamiento largo en lugar de respuesta directa (> 200 chars)
        $pareceRazonamiento = (
            strlen($content) > 200 && (
                strpos($lower, 'necesito') !== false || strpos($lower, 'veamos') !== false ||
                strpos($lower, 'voy a') !== false || strpos($lower, 'debo') !== false ||
                strpos($lower, 'podria') !== false || strpos($lower, 'asumire') !== false ||
                strpos($lower, 'interpretare') !== false || strpos($lower, 'analizando') !== false ||
                strpos($lower, 'el usuario') !== false || strpos($lower, 'la consulta') !== false ||
                strpos($lower, 'we need') !== false || strpos($lower, 'let me') !== false ||
                strpos($lower, 'i think') !== false || strpos($lower, 'first') !== false ||
                preg_match('/^(veamos|analizando|voy a|necesito|debo|podemos|we need|let me|i will|first)/i', $content)
            )
        );
        return ($hasToolKeywords && strpos($content, '{') !== 0) || $pareceRazonamiento;
    }
    
    // ============================================================
    // HELPER: Extraer JSON tool call con balanceo de llaves
    // ============================================================
    function extraerJsonBalanced($content, $startPos) {
        $len = strlen($content);
        $pairs = ['{' => '}', '[' => ']'];
        $openChar = $content[$startPos];
        if (!isset($pairs[$openChar])) return null;
        $closeChar = $pairs[$openChar];
        $depth = 0;
        $jsonStart = $startPos;
        
        for ($i = $startPos; $i < $len; $i++) {
            $ch = $content[$i];
            if ($ch === $openChar) {
                $depth++;
            } elseif ($ch === $closeChar) {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $jsonStart, $i - $jsonStart + 1);
                }
            } elseif ($ch === '"') {
                $escaped = false;
                for ($j = $i + 1; $j < $len; $j++) {
                    if ($escaped) { $escaped = false; continue; }
                    if ($content[$j] === '\\') { $escaped = true; continue; }
                    if ($content[$j] === '"') { $i = $j; break; }
                }
            }
        }
        return null;
    }

    // ============================================================
    // HELPER: Ejecutar una tool individual
    // ============================================================
    function ejecutarTool($tool, $params, $toolsModel) {
        $resultado = null;
        switch ($tool) {
            case 'consultar_stock':           $resultado = $toolsModel->consultarStock($params); break;
            case 'consultar_ventas':          $resultado = $toolsModel->consultarVentas($params); break;
            case 'consultar_proformas':       $resultado = $toolsModel->consultarProformas($params); break;
            case 'consultar_vouchers':        $resultado = $toolsModel->consultarVouchers($params); break;
            case 'consultar_productos':       $resultado = $toolsModel->consultarProductos($params); break;
            case 'consultar_clientes':        $resultado = $toolsModel->consultarClientes($params); break;
            case 'consultar_mermas':          $resultado = $toolsModel->consultarMermas($params); break;
            case 'consultar_kardex':          $resultado = $toolsModel->consultarKardex($params); break;
            case 'consultar_top_productos_vendidos': $resultado = $toolsModel->consultarTopProductosVendidos($params); break;
            case 'consultar_valorizacion_inventario': $resultado = $toolsModel->consultarValorizacionInventario($params); break;
            case 'consultar_ventas_por_mes':  $resultado = $toolsModel->consultarVentasPorMes($params); break;
            case 'consultar_vouchers_saldo':  $resultado = $toolsModel->consultarVouchersSaldo($params); break;
            case 'consultar_grafico':         $resultado = $toolsModel->consultarGrafico($params); break;
            case 'consultar_resumen':         $resultado = $toolsModel->consultarResumen($params); break;
            case 'consultar_comparativa':     $resultado = $toolsModel->consultarComparativa($params); break;
            case 'consultar_buscar':          $resultado = $toolsModel->consultarBuscar($params); break;
            case 'consultar_recomendaciones': $resultado = $toolsModel->consultarRecomendaciones($params); break;
            default: return ['error' => 'Tool desconocida: ' . $tool];
        }
        return $resultado;
    }

    // ============================================================
    // HELPER: Detectar y ejecutar tool call(s) — soporta multi-tool
    // ============================================================
    function procesarToolCall($content, $toolsModel) {
        global $TOOLS_VALIDAS;
        $content = trim($content);
        
        // Limpiar markdown code fences
        $cleaned = $content;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $fenceMatch)) {
            $cleaned = trim($fenceMatch[1]);
        }
        
        $toolsToExec = [];
        
        // Buscar array de tools: [{"tool":...
        $arrPos = strpos($cleaned, '[{"tool"');
        if ($arrPos === false) $arrPos = strpos($cleaned, '[{ "tool"');
        if ($arrPos !== false) {
            $jsonStr = extraerJsonBalanced($cleaned, $arrPos);
            if ($jsonStr !== null) {
                $decoded = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach ($decoded as $item) {
                        if (!empty($item['tool']) && isset($item['params'])) {
                            $toolsToExec[] = $item;
                        }
                    }
                }
            }
        }
        
        // Buscar tool simple: {"tool":...
        if (empty($toolsToExec)) {
            $pos = strpos($cleaned, '{"tool"');
            if ($pos === false) $pos = strpos($cleaned, '{ "tool"');
            if ($pos !== false) {
                $jsonStr = extraerJsonBalanced($cleaned, $pos);
                if ($jsonStr !== null) {
                    $decoded = json_decode($jsonStr, true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['tool'])) {
                        $toolsToExec[] = $decoded;
                    }
                }
            }
        }
        
        if (empty($toolsToExec)) return null;
        
        // Ejecutar todas las tools detectadas
        $allResults = [];
        $allTools = [];
        
        foreach ($toolsToExec as $toolCall) {
            $tool = $toolCall['tool'];
            if (!in_array($tool, $TOOLS_VALIDAS, true)) {
                error_log('[Chatbot] Tool invalida: ' . $tool);
                continue;
            }
            $params = $toolCall['params'] ?? [];
            error_log('[Chatbot] Tool call: ' . $tool . ' | params: ' . json_encode($params));
            $resultado = ejecutarTool($tool, $params, $toolsModel);
            if (isset($resultado['error'])) continue;
            $allResults[] = $resultado;
            $allTools[] = $tool;
        }
        
        if (empty($allResults)) return null;
        
        // Si es una sola tool, devolver formato normal
        if (count($allResults) === 1 && count($allTools) === 1) {
            return [
                'tool' => $allTools[0],
                'resultado' => $toolsModel->formatearResultado($allTools[0], $allResults[0]),
                'resultado_raw' => $toolsModel->formatearResultadoRaw($allResults[0])
            ];
        }
        
        // Multi-tool: mergear resultados
        $textoCombinado = "Resultados combinados (" . count($allTools) . " consultas):\n\n";
        $allRows = [];
        $allColumns = null;
        $grafico = null;
        
        foreach ($allResults as $i => $res) {
            $textoCombinado .= $toolsModel->formatearResultado($allTools[$i], $res) . "\n";
            $raw = $toolsModel->formatearResultadoRaw($res);
            if ($raw && !empty($raw['rows'])) {
                $allRows = array_merge($allRows, $raw['rows']);
                if ($allColumns === null && !empty($raw['columns'])) {
                    $allColumns = $raw['columns'];
                }
            }
            if (!$grafico && $raw && !empty($raw['grafico'])) {
                $grafico = $raw['grafico'];
            }
        }
        
        $resultadoRaw = null;
        if ($allColumns !== null && !empty($allRows)) {
            $resultadoRaw = ['columns' => $allColumns, 'rows' => $allRows, 'total' => count($allRows)];
            if ($grafico) $resultadoRaw['grafico'] = $grafico;
        }
        
        return [
            'tool' => implode(' + ', $allTools),
            'resultado' => $textoCombinado,
            'resultado_raw' => $resultadoRaw
        ];
    }
    
    // ========================================
    // ENDPOINT AJAX: Chat principal con tools
    // ========================================
    if ($action === 'chat_enviar') {
        verificarRateLimit();
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $mensaje = trim($input['mensaje'] ?? '');
        $historial = $input['historial'] ?? [];
        
        if (empty($mensaje)) {
            echo json_encode(['success' => false, 'message' => 'El mensaje está vacío']);
            exit;
        }
        
        $chatService = env('CHAT_SERVICE', 'opencode');
        $apiKey = env('OPENCODE_API_KEY');
        $model = env('OPENCODE_MODEL', 'deepseek-v4-flash-free');
        
        if ($chatService === 'deepseek') {
            $apiKey = env('DEEPSEEK_API_KEY');
            $model = env('DEEPSEEK_MODEL', 'deepseek-chat');
        }
        
        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'API key no configurada. Agrega ' . ($chatService === 'deepseek' ? 'DEEPSEEK_API_KEY' : 'OPENCODE_API_KEY') . ' en .env']);
            exit;
        }

        $apiConfig = [
            'service' => $chatService,
            'api_key' => $apiKey,
            'model' => $model
        ];
        
        // Preparar mensajes para la API
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $SYSTEM_PROMPT_TOOLS];
        
        // Historial previo (limitado a últimos 8 mensajes)
        $historialReciente = array_slice($historial, -8);
        foreach ($historialReciente as $h) {
            if (!empty($h['role']) && !empty($h['content'])) {
                $messages[] = ['role' => $h['role'], 'content' => $h['content']];
            }
        }
        
        $messages[] = ['role' => 'user', 'content' => $mensaje];
        
        // === LLAMADA 1: El modelo decide si necesita una tool ===
        $respuesta1 = llamarChatAPI($messages, $apiConfig);
        
        if (isset($respuesta1['error'])) {
            echo json_encode(['success' => false, 'message' => $respuesta1['error']]);
            exit;
        }
        
        $content1 = $respuesta1['content'];
        
        // Intentar detectar si es un tool call
        $toolResult = procesarToolCall($content1, $toolsModel);
        
        if ($toolResult !== null) {
            // Hubo tool call pero la tool es inválida (no en whitelist)
            if (isset($toolResult['error_tool_invalida'])) {
                error_log('[Chatbot] Tool inválida: ' . $toolResult['tool'] . ' | respuesta original: ' . $content1);
                echo json_encode([
                    'success' => true,
                    'respuesta' => 'Procesé tu consulta pero hubo un problema interno. ¿Puedes reformular tu pregunta?',
                    'modelo' => $apiConfig['model'],
                    'tool_usada' => null,
                    'resultado_raw' => null,
                    'tool_call_info' => null
                ]);
                exit;
            }
            
            if (isset($toolResult['error'])) {
                echo json_encode(['success' => false, 'message' => $toolResult['error']]);
                exit;
            }
            
            // === LLAMADA 2: Reenviar resultado al modelo para que responda al usuario ===
            $messages[] = ['role' => 'assistant', 'content' => $content1];
            $messages[] = ['role' => 'user', 'content' => 'Resultado de la consulta:\n' . $toolResult['resultado'] . '\n\nPor favor, resume estos datos de manera clara y concisa para el usuario. Si hay muchos registros, destaca los más relevantes.'];
            
            $respuesta2 = llamarChatAPI($messages, $apiConfig);
            
            if (isset($respuesta2['error'])) {
                echo json_encode(['success' => false, 'message' => $respuesta2['error']]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'respuesta' => $respuesta2['content'],
                'modelo' => $apiConfig['model'],
                'tool_usada' => $toolResult['tool'],
                'resultado_raw' => $toolResult['resultado_raw'],
                'tool_call_info' => [
                    'tool' => $toolResult['tool'],
                    'hint' => 'Consulta completada con datos de la base de datos'
                ]
            ]);
            exit;
        }
        
        // No fue tool call. Verificar si parece razonamiento en vez de respuesta directa
        if (pareceToolCallFallido($content1)) {
            error_log('[Chatbot] Respuesta parece razonamiento, re-intentando: ' . substr($content1, 0, 150));
            
            // Re-intentar: pedir al modelo que responda correctamente sin razonamiento
            $messages[] = ['role' => 'user', 'content' => 'CORRECCION: NO emitas analisis ni razonamiento. Si necesitas datos de la BD, emite SOLO el JSON de tool call. Si NO necesitas datos, responde DIRECTAMENTE al usuario sin analisis previo. Responde AHORA correctamente a la consulta original: ' . $mensaje];
            
            $retryRespuesta = llamarChatAPI($messages, $apiConfig);
            
            if (isset($retryRespuesta['error'])) {
                echo json_encode(['success' => true, 'respuesta' => 'Tengo dificultades para procesar tu consulta. ¿Podrias reformularla?']);
                exit;
            }
            
            $contentRetry = $retryRespuesta['content'];
            
            // Intentar tool call en el re-intento
            $toolResultRetry = procesarToolCall($contentRetry, $toolsModel);
            
            if ($toolResultRetry !== null) {
                if (isset($toolResultRetry['error_tool_invalida']) || isset($toolResultRetry['error'])) {
                    echo json_encode(['success' => true, 'respuesta' => 'Procese tu consulta pero hubo un problema. ¿Puedes reformular?']);
                    exit;
                }
                
                $messages[] = ['role' => 'assistant', 'content' => $contentRetry];
                $messages[] = ['role' => 'user', 'content' => 'Resultado de la consulta:\n' . $toolResultRetry['resultado'] . '\n\nResume estos datos de manera clara y concisa.'];
                
                $respuesta2 = llamarChatAPI($messages, $apiConfig);
                if (isset($respuesta2['error'])) {
                    echo json_encode(['success' => false, 'message' => $respuesta2['error']]);
                    exit;
                }
                
                echo json_encode([
                    'success' => true,
                    'respuesta' => $respuesta2['content'],
                    'modelo' => $apiConfig['model'],
                    'tool_usada' => $toolResultRetry['tool'],
                    'resultado_raw' => $toolResultRetry['resultado_raw'],
                    'tool_call_info' => ['tool' => $toolResultRetry['tool'], 'hint' => 'Consulta completada']
                ]);
                exit;
            }
            
            // Si el re-intento tampoco es tool call, usar la respuesta como texto
            echo json_encode([
                'success' => true,
                'respuesta' => $contentRetry,
                'modelo' => $apiConfig['model'],
                'tool_usada' => null,
                'resultado_raw' => null
            ]);
            exit;
        }
        
        // Responder directamente (sin tool call)
        echo json_encode([
            'success' => true,
            'respuesta' => $content1,
            'modelo' => $apiConfig['model'],
            'tool_usada' => null,
            'resultado_raw' => null,
            'tool_call_info' => null
        ]);
        exit;
    }
    
    // ========================================
    // ENDPOINTS SEPARADOS PARA CADA TOOL
    // ========================================
    
    function toolResponse($toolResult) {
        verificarRateLimit();
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
    
    if ($action === 'tool_kardex') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarKardex($params));
    }
    
    if ($action === 'tool_top_productos') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarTopProductosVendidos($params));
    }
    
    if ($action === 'tool_valorizacion') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarValorizacionInventario($params));
    }
    
    if ($action === 'tool_ventas_mes') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarVentasPorMes($params));
    }
    
    if ($action === 'tool_vouchers_saldo') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarVouchersSaldo($params));
    }
    
    if ($action === 'tool_grafico') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarGrafico($params));
    }
    
    if ($action === 'tool_resumen') {
        toolResponse($toolsModel->consultarResumen([]));
    }
    
    if ($action === 'tool_comparativa') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarComparativa($params));
    }
    
    if ($action === 'tool_buscar') {
        $params = json_decode(file_get_contents('php://input'), true) ?? [];
        toolResponse($toolsModel->consultarBuscar($params));
    }
    
    if ($action === 'tool_recomendaciones') {
        toolResponse($toolsModel->consultarRecomendaciones([]));
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
