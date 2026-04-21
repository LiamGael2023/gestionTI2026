<?php
/**
 * modules/chatbot/controllers/ChavibotController.php
 */
require_once __DIR__ . '/../models/ChavibotModel.php';

class ChavibotController
{
    // ── Chat web ───────────────────────────────────────────────────────────
    static public function ctrResponder(string $mensaje): array
    {
        $inicio = microtime(true);
        $perfil = ChavibotModel::mdlObtenerPerfil();

        if (!$perfil['autenticado'])
            return ['error'=>true,'respuesta'=>'Debes iniciar sesión.'];

        return self::_procesar($mensaje, $perfil, $inicio);
    }

    // ── RAG: admin/tecnico ─────────────────────────────────────────────────
    static public function ctrAgregarRAG(): array
    {
        $perfil = ChavibotModel::mdlObtenerPerfil();
        if (!in_array($perfil['rol'], ['admin','tecnico']))
            return ['error'=>true,'mensaje'=>'Sin permisos.'];

        $d = [
            'pregunta'  => trim($_POST['pregunta']  ?? ''),
            'palabras'  => trim($_POST['palabras']  ?? ''),
            'schema'    => trim($_POST['schema']    ?? ''),
            'sql'       => trim($_POST['sql']       ?? ''),
            'respBase'  => trim($_POST['respBase']  ?? ''),
            'rol'       => trim($_POST['rol']       ?? ''),
            'area'      => trim($_POST['area']      ?? ''),
            'canal'     => trim($_POST['canal']     ?? 'ambos'),
            'idUsuario' => $perfil['idUsuario'],
        ];
        if (!$d['pregunta'] || !$d['sql'])
            return ['error'=>true,'mensaje'=>'Pregunta y SQL son obligatorios.'];

        $id = ChavibotModel::mdlAgregarRAG($d);
        return ['error'=>($id===0),'mensaje'=>$id>0?"Agregado (ID: {$id}).":'Error.','id'=>$id];
    }

    static public function ctrListarRAG(): array
    {
        $p = ChavibotModel::mdlObtenerPerfil();
        if (!in_array($p['rol'], ['admin','tecnico']))
            return ['error'=>true,'datos'=>[]];
        return ['error'=>false,'datos'=>ChavibotModel::mdlListarRAG()];
    }

    static public function ctrToggleRAG(): array
    {
        $p = ChavibotModel::mdlObtenerPerfil();
        if ($p['rol'] !== 'admin')
            return ['error'=>true,'mensaje'=>'Solo admins.'];
        $id     = intval($_POST['id']     ?? 0);
        $activo = ($_POST['activo'] ?? '1') === '1';
        $ok     = ChavibotModel::mdlToggleRAG($id, $activo);
        return ['error'=>!$ok,'mensaje'=>$ok?'OK.':'Error.'];
    }

    // ── Feedback ───────────────────────────────────────────────────────────
    static public function ctrFeedback(): array
    {
        $idMsg  = intval($_POST['idMensaje'] ?? 0);
        $util   = ($_POST['util'] ?? '0') === '1';
        $com    = trim($_POST['comentario'] ?? '');
        $uid    = intval($_SESSION['usuario_id'] ?? 0);
        if (!$idMsg) return ['error'=>true,'mensaje'=>'ID inválido.'];
        $ok = ChavibotModel::mdlGuardarFeedback($idMsg, $uid, $util, $com);
        return ['error'=>!$ok,'mensaje'=>$ok?'Gracias.':'Error.'];
    }

    // ══════════════════════════════════════════════════════════════════════
    // NÚCLEO COMPARTIDO (web + whatsapp)
    // ══════════════════════════════════════════════════════════════════════

    static private function _procesar(string $msg, array $perfil, float $inicio): array
    {
        $hist  = ChavibotModel::mdlObtenerHistorial($perfil['sessionId'], 4);
        $ejes  = ChavibotModel::mdlBuscarRAG($msg, $perfil);
        $ej    = $ejes[0] ?? null;

        $datos = []; $schema = '';
        if ($ej && !empty($ej['sqlQuery'])) {
            $datos  = ChavibotModel::mdlEjecutarQueryRAG($ej['sqlQuery'], $perfil);
            $schema = $ej['schemaObjetivo'] ?? '';
        }

        $resp = self::_promptYOllama($msg, $perfil, $hist, $ej, $datos);
        if ($perfil['canal'] === 'whatsapp') $resp = self::_limpiarWA($resp);

        $ms = intval((microtime(true) - $inicio) * 1000);
        $id = ChavibotModel::mdlGuardarHistorial([
            'sessionId'=>$perfil['sessionId'], 'idUsuario'=>$perfil['idUsuario'],
            'dni'=>$perfil['dni'],             'nombres'=>$perfil['nombres'],
            'rol'=>$perfil['rol'],             'area'=>$perfil['area'],
            'pregunta'=>$msg,                  'respuesta'=>$resp,
            'schema'=>$schema,                 'filas'=>count($datos),
            'tiempoMs'=>$ms,                   'canal'=>$perfil['canal'],
            'telefono'=>$perfil['telefono'],
        ]);

        // NO exponer schema/tabla al frontend
        return ['respuesta'=>$resp,'idMensaje'=>$id,
                'totalFilas'=>count($datos),'tiempoMs'=>$ms,
                'usuario'=>$perfil['nombres'],'rol'=>$perfil['rol']];
    }

    // ── Prompt + Ollama — PUBLIC para que el ajax pueda llamarlo ──────────
    static public function _promptYOllama(
        string $msg, array $p, array $hist, ?array $ej, array $datos
    ): string {
        // Instrucciones del sistema
        $pr  = "Eres ChaviBot, asistente inteligente del área de TI de CHAVIMOCHIC.\n";
        $pr .= "Respondes siempre en español, de forma clara y profesional.\n";
        $pr .= "REGLAS IMPORTANTES:\n";
        $pr .= "- NUNCA menciones nombres de tablas, schemas, bases de datos ni SQL.\n";
        $pr .= "- NUNCA uses términos como: inventario.activo, chaviBot, BD_GESTION_TI, SELECT, JOIN, etc.\n";
        $pr .= "- Presenta la información de forma natural, como si la supieras tú mismo.\n";
        $pr .= "- Solo usas los datos proporcionados. Nunca inventas información.\n";
        $pr .= "- Si no hay datos, dilo claramente sin jerga técnica.\n";
        if ($p['canal'] === 'whatsapp')
            $pr .= "- Canal WhatsApp: respuesta muy breve (máx 5 líneas), sin markdown complejo.\n";
        else
            $pr .= "- Usa formato claro con saltos de línea. Puedes usar listas con guiones.\n";

        $pr .= "\n=== CONTEXTO DEL USUARIO ===\n";
        $pr .= "Nombre: {$p['nombres']} | Rol: {$p['rol']} | Área: {$p['area']}\n";
        if ($p['rol'] === 'admin') $pr .= "→ Tiene acceso completo a toda la información.\n";
        elseif ($p['rol'] === 'tecnico') $pr .= "→ Ve información técnica de su área.\n";
        else $pr .= "→ Ve información relacionada a su área y usuario.\n";

        if (!empty($hist)) {
            $pr .= "\n=== CONVERSACIÓN PREVIA ===\n";
            foreach ($hist as $h)
                $pr .= "Usuario preguntó: {$h['pregunta']}\nBot respondió: {$h['respuesta']}\n\n";
        }

        if ($ej && $ej['respuestaBase']) {
            $pr .= "\n=== CONTEXTO DE LA CONSULTA ===\n";
            $pr .= "{$ej['respuestaBase']}\n";
        }

        if (!empty($datos)) {
            $pr .= "\n=== DATOS ENCONTRADOS (" . count($datos) . " registros) ===\n";
            $pr .= json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $pr .= "\n→ Presenta estos datos de forma clara y natural. NO menciones de dónde vienen.\n";
        } elseif ($ej) {
            $pr .= "\n=== RESULTADO ===\nNo se encontraron datos para esta consulta en este momento.\n";
        } else {
            $pr .= "\n=== NOTA ===\nNo tengo datos específicos para esto. Responde con conocimiento general de TI si aplica.\n";
        }

        $pr .= "\n=== PREGUNTA DEL USUARIO ===\n{$msg}\n\nRespuesta (sin mencionar tablas, schemas ni código SQL):";

        $payload = json_encode([
            'model'   => 'llama3.1',
            'prompt'  => $pr,
            'stream'  => false,
            'options' => ['temperature'=>0.2,'num_predict'=>600,'top_p'=>0.9],
        ]);

        $ch = curl_init('http://localhost:11434/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>180,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>$payload,
        ]);
        $raw = curl_exec($ch); $err = curl_errno($ch); curl_close($ch);
        if ($err) return "⚠️ IA no disponible. Ejecuta: ollama serve";
        $r = json_decode($raw, true);
        return trim($r['response'] ?? 'Sin respuesta del modelo.');
    }

    // ── Limpiar texto para WhatsApp — PUBLIC ──────────────────────────────
    static public function _limpiarWA(string $t): string
    {
        $t = preg_replace('/\*\*(.*?)\*\*/s', '*$1*', $t);
        $t = preg_replace('/#{1,6}\s*(.+)/m',  '*$1*', $t);
        $t = preg_replace('/`{3}.*?`{3}/s',    '',     $t);
        $t = preg_replace('/`(.+?)`/',          '$1',   $t);
        return trim($t);
    }
}
