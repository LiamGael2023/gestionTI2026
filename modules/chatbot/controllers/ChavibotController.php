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

        return ['respuesta'=>$resp,'idMensaje'=>$id,'schema'=>$schema,
                'totalFilas'=>count($datos),'tiempoMs'=>$ms,
                'usuario'=>$perfil['nombres'],'rol'=>$perfil['rol']];
    }

    // ── Prompt + Ollama — PUBLIC para que el ajax pueda llamarlo ──────────
    static public function _promptYOllama(
        string $msg, array $p, array $hist, ?array $ej, array $datos
    ): string {
        $pr  = "Eres ChaviBot, asistente inteligente del área de TI de CHAVIMOCHIC.\n";
        $pr .= "Respondes siempre en español, de forma clara y profesional.\n";
        $pr .= "Solo usas los datos proporcionados. Nunca inventas información.\n";
        if ($p['canal'] === 'whatsapp')
            $pr .= "Canal WhatsApp: respuesta muy breve (máx 4 líneas), sin markdown.\n";

        $pr .= "\n=== USUARIO ===\n";
        $pr .= "Nombre: {$p['nombres']} | Rol: {$p['rol']} | Área: {$p['area']}\n";
        if ($p['rol'] === 'admin') $pr .= "→ Puede ver TODA la información.\n";
        elseif ($p['rol'] === 'tecnico') $pr .= "→ Ve información técnica de su área.\n";
        else $pr .= "→ Solo ve información de su área/usuario.\n";

        if (!empty($hist)) {
            $pr .= "\n=== CONVERSACIÓN RECIENTE ===\n";
            foreach ($hist as $h)
                $pr .= "[{$h['hora']}] U: {$h['pregunta']}\n[{$h['hora']}] B: {$h['respuesta']}\n";
        }

        if ($ej) {
            $pr .= "\n=== DATOS DE BD_GESTION_TI ===\nTabla: {$ej['schemaObjetivo']}\n";
            if ($ej['respuestaBase']) $pr .= "Descripción: {$ej['respuestaBase']}\n";
        }

        if (!empty($datos)) {
            $pr .= "Registros (" . count($datos) . "):\n";
            $pr .= json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } elseif ($ej) {
            $pr .= "La consulta no retornó datos en este momento.\n";
        } else {
            $pr .= "\n=== NOTA ===\nNo hay consulta específica. Responde con conocimiento general de TI.\n";
        }

        $pr .= "\n=== PREGUNTA ===\n{$msg}\n\nRespuesta:";

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
