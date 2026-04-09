<?php
require_once __DIR__ . "/../../../config/db.php";
// Cargar config de WhatsApp (con if para no fallar si ya está definido)
$_waConfig = __DIR__ . '/../config/whatsapp.php';
if (file_exists($_waConfig)) require_once $_waConfig;
if (!defined('API_PERSONAL_URL')) define('API_PERSONAL_URL', '');
if (!defined('WA_NODE_TOKEN'))    define('WA_NODE_TOKEN', 'chavibot_node_2024');

class ChavibotModel
{
    // ══════════════════════════════════════════════════════════════════════
    // API PERSONAL CHAVIMOCHIC
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Busca un usuario por DNI en la API de personal.
     * Retorna array con datos o [] si no encontrado.
     */
    static public function mdlBuscarPersonalPorDni(string $dni): array
    {
        $url = API_PERSONAL_URL . '?dni=' . urlencode(trim($dni));
        return self::_llamarApiPersonal($url);
    }

    /**
     * Busca personal por área.
     */
    static public function mdlBuscarPersonalPorArea(string $area): array
    {
        $url = API_PERSONAL_URL . '?area=' . urlencode($area);
        return self::_llamarApiPersonal($url);
    }

    /**
     * Busca personal por subgerencia.
     */
    static public function mdlBuscarPersonalPorSubgerencia(string $subgerencia): array
    {
        $url = API_PERSONAL_URL . '?subgerencia=' . urlencode($subgerencia);
        return self::_llamarApiPersonal($url);
    }

    /**
     * Llamada interna a la API de personal de CHAVIMOCHIC.
     */
    static private function _llamarApiPersonal(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,  // certificado interno
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw   = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || !$raw) {
            error_log('[chaviBot] API personal error: ' . $errno . ' url=' . $url);
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // ══════════════════════════════════════════════════════════════════════
    // AUTENTICACION WEB (tabla comun.Usuarios)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verifica credenciales de login web contra comun.Usuarios.
     * Retorna array con datos del usuario o [] si falla.
     */
    static public function mdlLoginWeb(string $usuario, string $contrasena): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];

        $sql  = "SELECT id_usuario, nombres, apellidos, correo, rol, sede_id,
                        activo, usuario, documento, id_rol
                 FROM [comun].[Usuarios]
                 WHERE usuario = ? AND activo = 1";
        $stmt = sqlsrv_query($conn, $sql, [[$usuario, SQLSRV_PARAM_IN]]);
        $user = [];

        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row) {
                // Verificar contraseña (ajusta según cómo la guardas: md5, bcrypt, etc.)
                if (md5($contrasena) === $row['contrasena'] || $contrasena === $row['contrasena']) {
                    $user = $row;
                }
            }
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $user;
    }

    // ══════════════════════════════════════════════════════════════════════
    // PERFIL DEL USUARIO
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Construye el perfil del usuario para web (desde $_SESSION)
     * o para WhatsApp (desde $datosWA).
     */
    static public function mdlObtenerPerfil(array $datosWA = []): array
    {
        $esWA = !empty($datosWA);

        if ($esWA) {
            return [
                'idUsuario' => intval($datosWA['idUsuario'] ?? 0),
                'dni'       => $datosWA['dni']       ?? '',
                'nombres'   => ($datosWA['nombres']   ?? '') . ' ' . ($datosWA['apellidos'] ?? ''),
                'rol'       => strtolower($datosWA['rol']  ?? 'usuario'),
                'area'      => $datosWA['area']       ?? '',
                'subgerencia'=> $datosWA['subgerencia'] ?? '',
                'sessionId' => $datosWA['sessionId']  ?? 'wa_' . ($datosWA['telefono'] ?? ''),
                'canal'     => 'whatsapp',
                'telefono'  => $datosWA['telefono']   ?? null,
                'autenticado'=> (bool)($datosWA['autenticado'] ?? false),
            ];
        }

        // ─────────────────────────────────────────────────────────────
        // WEB: leer $_SESSION con TODAS las variantes posibles de clave
        // El sistema principal guarda: id_usuario, nombres, id_rol, etc.
        // ─────────────────────────────────────────────────────────────
        $idUsuario = intval(
            $_SESSION['id_usuario']   ??   // ← clave real del sistema
            $_SESSION['usuario_id']   ??   // ← variante alternativa
            0
        );

        $dni = $_SESSION['documento']     ??
               $_SESSION['usuario_dni']   ??
               '';

        $nombres = trim(
            ($_SESSION['nombres']        ?? '') . ' ' .   // ← clave real
            ($_SESSION['apellidos']      ?? '')
        );
        if (!$nombres) {
            $nombres = $_SESSION['usuario_nombre'] ?? 'Usuario';
        }

        // id_rol puede ser un entero (1=admin, 2=tecnico, 3=usuario)
        // o puede ser el string del rol directamente
        $rolRaw = $_SESSION['id_rol']      ??   // ← clave real (puede ser int)
                  $_SESSION['rol']          ??
                  $_SESSION['usuario_rol']  ??
                  'usuario';

        // Normalizar rol: si es número, mapearlo a string
        $rolMap = [1 => 'admin', 2 => 'tecnico', 3 => 'usuario',
                   '1' => 'admin', '2' => 'tecnico', '3' => 'usuario'];
        $rol = $rolMap[$rolRaw] ?? strtolower((string)$rolRaw);

        $area = $_SESSION['area']              ??
                $_SESSION['usuario_area']       ??
                $_SESSION['departamento']       ??
                '';

        $subgerencia = $_SESSION['subgerencia']          ??
                       $_SESSION['usuario_subgerencia']  ??
                       '';

        return [
            'idUsuario'   => $idUsuario,
            'dni'         => $dni,
            'nombres'     => $nombres ?: 'Usuario',
            'rol'         => $rol,
            'area'        => $area,
            'subgerencia' => $subgerencia,
            'sessionId'   => session_id(),
            'canal'       => 'web',
            'telefono'    => null,
            'autenticado' => ($idUsuario > 0),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // SESION WHATSAPP  (schema chaviBot)
    // ══════════════════════════════════════════════════════════════════════

    static public function mdlObtenerSesionWA(string $telefono): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];
        $stmt = sqlsrv_query($conn,
            "{call chaviBot.sp_WA_ObtenerSesion(?)}",
            [[$telefono, SQLSRV_PARAM_IN]]
        );
        $sesion = [];
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $sesion = $row ?: [];
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $sesion;
    }

    static public function mdlActualizarSesionWA(string $telefono, array $datos = []): void
    {
        $conn = Conexion::conectar();
        if (!$conn) return;
        $stmt = sqlsrv_query($conn,
            "{call chaviBot.sp_WA_ActualizarSesion(?,?,?,?,?,?,?,?,?,?,?)}",
            [
                [$telefono,                            SQLSRV_PARAM_IN],
                [$datos['pasoAuth']     ?? null,       SQLSRV_PARAM_IN],
                [$datos['autenticado']  ?? null,       SQLSRV_PARAM_IN],
                [$datos['idUsuario']    ?? null,       SQLSRV_PARAM_IN],
                [$datos['dni']          ?? null,       SQLSRV_PARAM_IN],
                [$datos['nombres']      ?? null,       SQLSRV_PARAM_IN],
                [$datos['apellidos']    ?? null,       SQLSRV_PARAM_IN],
                [$datos['rol']          ?? null,       SQLSRV_PARAM_IN],
                [$datos['area']         ?? null,       SQLSRV_PARAM_IN],
                [$datos['subgerencia']  ?? null,       SQLSRV_PARAM_IN],
                [$datos['intentos']     ?? null,       SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt) sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
    }

    // ══════════════════════════════════════════════════════════════════════
    // RAG  (schema chaviBot)
    // ══════════════════════════════════════════════════════════════════════

    static public function mdlBuscarRAG(string $mensaje, array $perfil): array
    {
        $conn     = Conexion::conectar();
        if (!$conn) return [];
        $palabras = self::_extraerPalabras($mensaje);
        $rol      = $perfil['rol'];
        $area     = $perfil['area'];
        $canal    = $perfil['canal'];

        $condiciones = [];
        $params      = [];
        foreach ($palabras as $p) {
            $condiciones[] = "CASE WHEN LOWER(r.palabrasClave) LIKE ? THEN 1 ELSE 0 END";
            $params[]      = ['%' . $p . '%', SQLSRV_PARAM_IN];
        }

        if (empty($condiciones)) { sqlsrv_close($conn); return []; }

        $score = implode(' + ', $condiciones);

        $sql = "
            SELECT TOP 3
                r.idEjemplo, r.preguntaEjemplo, r.palabrasClave,
                r.schemaObjetivo, r.sqlQuery, r.respuestaBase,
                r.rolPermitido, r.areaPermitida, r.vecesUtil,
                ({$score}) AS score
            FROM [chaviBot].[RAG] r
            WHERE r.activo = 1
              AND (r.canal = 'ambos' OR r.canal = ?)
              AND (r.rolPermitido IS NULL OR r.rolPermitido = '' OR LOWER(r.rolPermitido) LIKE ?)
              AND (r.areaPermitida IS NULL OR r.areaPermitida = '' OR LOWER(r.areaPermitida) LIKE ?)
              AND ({$score}) > 0
            ORDER BY ({$score}) DESC, r.vecesUtil DESC
        ";

        $canalParam = [$canal,                         SQLSRV_PARAM_IN];
        $rolParam   = ['%' . $rol . '%',               SQLSRV_PARAM_IN];
        $areaParam  = ['%' . strtolower($area) . '%',  SQLSRV_PARAM_IN];
        $allParams  = array_merge($params, [$canalParam, $rolParam, $areaParam], $params, $params);

        $stmt = sqlsrv_query($conn, $sql, $allParams);
        $rows = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);

        if (!empty($rows)) self::_incrementarUsoRAG(array_column($rows, 'idEjemplo'));
        return $rows;
    }

    static public function mdlEjecutarQueryRAG(string $sql, array $perfil): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];
        $sql  = str_replace(['{{DNI}}','{{USUARIO_ID}}','{{AREA}}'],
                            [$perfil['dni'], $perfil['idUsuario'], $perfil['area']], $sql);
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    static public function mdlAgregarRAG(array $datos): int
    {
        $conn = Conexion::conectar();
        if (!$conn) return 0;
        $stmt = sqlsrv_query($conn,
            "{call chaviBot.sp_AgregarRAG(?,?,?,?,?,?,?,?,?)}",
            [
                [$datos['pregunta'],  SQLSRV_PARAM_IN],
                [$datos['palabras'],  SQLSRV_PARAM_IN],
                [$datos['schema'],    SQLSRV_PARAM_IN],
                [$datos['sql'],       SQLSRV_PARAM_IN],
                [$datos['respBase'],  SQLSRV_PARAM_IN],
                [$datos['rol'],       SQLSRV_PARAM_IN],
                [$datos['area'],      SQLSRV_PARAM_IN],
                [$datos['canal'],     SQLSRV_PARAM_IN],
                [$datos['idUsuario'], SQLSRV_PARAM_IN],
            ]
        );
        $id = 0;
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $id  = intval($row['idEjemplo'] ?? 0);
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $id;
    }

    /**
     * Lista todos los ejemplos RAG (para panel de entrenamiento).
     */
    static public function mdlListarRAG(): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];
        $sql  = "SELECT idEjemplo, preguntaEjemplo, palabrasClave, schemaObjetivo,
                        sqlQuery, respuestaBase, rolPermitido, areaPermitida,
                        canal, activo, vecesUsado, vecesUtil,
                        FORMAT(fechaCreacion,'dd/MM/yyyy HH:mm') AS fechaCreacion
                 FROM [chaviBot].[RAG] ORDER BY fechaCreacion DESC";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /**
     * Activa/desactiva un ejemplo RAG.
     */
    static public function mdlToggleRAG(int $id, bool $activo): bool
    {
        $conn = Conexion::conectar();
        if (!$conn) return false;
        $stmt = sqlsrv_query($conn,
            "UPDATE [chaviBot].[RAG] SET activo = ? WHERE idEjemplo = ?",
            [[$activo ? 1 : 0, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]
        );
        $ok = ($stmt !== false);
        if ($stmt) sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $ok;
    }

    // ══════════════════════════════════════════════════════════════════════
    // HISTORIAL  (schema chaviBot)
    // ══════════════════════════════════════════════════════════════════════

    static public function mdlObtenerHistorial(string $sessionId, int $n = 4): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];
        $sql  = "SELECT TOP {$n} pregunta, respuesta, schemaConsultado,
                        FORMAT(fechaCreacion,'HH:mm') AS hora
                 FROM [chaviBot].[Historial]
                 WHERE sessionId = ? ORDER BY fechaCreacion DESC";
        $stmt = sqlsrv_query($conn, $sql, [[$sessionId, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return array_reverse($rows);
    }

    static public function mdlGuardarHistorial(array $d): int
    {
        $conn = Conexion::conectar();
        if (!$conn) return 0;
        $stmt = sqlsrv_query($conn,
            "{call chaviBot.sp_GuardarHistorial(?,?,?,?,?,?,?,?,?,?,?,?,?)}",
            [
                [$d['sessionId'],  SQLSRV_PARAM_IN],
                [$d['idUsuario'],  SQLSRV_PARAM_IN],
                [$d['dni'],        SQLSRV_PARAM_IN],
                [$d['nombres'],    SQLSRV_PARAM_IN],
                [$d['rol'],        SQLSRV_PARAM_IN],
                [$d['area'],       SQLSRV_PARAM_IN],
                [$d['pregunta'],   SQLSRV_PARAM_IN],
                [$d['respuesta'],  SQLSRV_PARAM_IN],
                [$d['schema'],     SQLSRV_PARAM_IN],
                [$d['filas'],      SQLSRV_PARAM_IN],
                [$d['tiempoMs'],   SQLSRV_PARAM_IN],
                [$d['canal'],      SQLSRV_PARAM_IN],
                [$d['telefono'],   SQLSRV_PARAM_IN],
            ]
        );
        $id = 0;
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $id  = intval($row['idMensaje'] ?? 0);
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $id;
    }

    // ══════════════════════════════════════════════════════════════════════
    // FEEDBACK
    // ══════════════════════════════════════════════════════════════════════

    static public function mdlGuardarFeedback(int $idMensaje, int $idUsuario, bool $util, string $comentario = ''): bool
    {
        $conn = Conexion::conectar();
        if (!$conn) return false;
        $stmt = sqlsrv_query($conn,
            "{call chaviBot.sp_GuardarFeedback(?,?,?,?)}",
            [
                [$idMensaje,  SQLSRV_PARAM_IN],
                [$idUsuario,  SQLSRV_PARAM_IN],
                [$util,       SQLSRV_PARAM_IN],
                [$comentario, SQLSRV_PARAM_IN],
            ]
        );
        $ok = ($stmt !== false);
        if ($stmt) sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $ok;
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════════════

    static private function _extraerPalabras(string $msg): array
    {
        $stop = ['de','la','el','en','que','y','a','los','las','un','una','con',
                 'por','para','es','son','hay','qué','cuántos','cuántas','cuál',
                 'están','esta','este','tiene','me','mi','mis','su','sus','se',
                 'no','si','al','del','lo','le','como','más','pero','o','u',
                 'quiero','saber','puedo','ver','dame','muestra','listar','lista',
                 'mostrar','dime','cuales','cuantos','estan'];

        $msg  = mb_strtolower($msg, 'UTF-8');
        $msg  = preg_replace('/[^a-záéíóúüñ\s]/u', ' ', $msg);
        $pals = preg_split('/\s+/', trim($msg));
        return array_values(array_filter($pals, fn($p) => strlen($p) > 2 && !in_array($p, $stop)));
    }

    static private function _incrementarUsoRAG(array $ids): void
    {
        if (empty($ids)) return;
        $conn   = Conexion::conectar();
        if (!$conn) return;
        $ph     = implode(',', array_fill(0, count($ids), '?'));
        $params = array_map(fn($id) => [$id, SQLSRV_PARAM_IN], $ids);
        $stmt   = sqlsrv_query($conn,
            "UPDATE [chaviBot].[RAG] SET vecesUsado = vecesUsado + 1 WHERE idEjemplo IN ({$ph})",
            $params
        );
        if ($stmt) sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
    }
}
