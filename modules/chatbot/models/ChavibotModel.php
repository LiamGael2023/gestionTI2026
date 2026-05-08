<?php
/**
 * ChavibotModel.php — Modelo ChaviBot v2
 *
 * CAMBIOS v2:
 *  - mdlObtenerPerfil() incluye 'permisos' (array de módulos permitidos)
 *  - mdlBuscarRAG() filtra por permisos cuando el usuario NO es admin/tecnico
 *  - mdlEjecutarQueryRAG() sustituye {{DNI}}, {{USUARIO_ID}}, {{AREA}}, {{PARAM}}
 */

require_once __DIR__ . '/../../../config/db.php';

class ChavibotModel
{
    // ── Módulos ChaviBot mapeados a nombres de comun.Modulos ─────────────
    // Permite saber qué permiso de módulo cubre cada tipo de consulta RAG
    private static $MODULO_MAP = [
        'soporte'      => ['reportestecnicos', 'soporte'],
        'inventario'   => ['inventario'],
        'laboratorio'  => ['agricola', 'laboratorio'],
        'salas'        => ['salas'],
        'activos'      => ['certificados'],
        'certificados' => ['certificados'],
        'adquisiciones'=> ['adquisiciones'],
        'patrimonio'   => ['patrimonio'],
        'comun'        => ['usuarios'],
    ];

    // ══════════════════════════════════════════════════════════════════════
    // PERFIL DE USUARIO
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Para WhatsApp: recibe $datosWA con el array 'permisos' ya cargado.
     * Para Web:      lee $_SESSION y carga permisos en tiempo real.
     */
    static public function mdlObtenerPerfil(array $datosWA = []): array
    {
        if (!empty($datosWA)) {
            // ── Canal WhatsApp ─────────────────────────────────────────
            return [
                'idUsuario'   => intval($datosWA['idUsuario']  ?? 0),
                'dni'         => $datosWA['dni']               ?? '',
                'nombres'     => trim(($datosWA['nombres'] ?? '') . ' ' . ($datosWA['apellidos'] ?? '')),
                'rol'         => strtolower($datosWA['rol']    ?? 'usuario'),
                'area'        => $datosWA['area']              ?? '',
                'permisos'    => $datosWA['permisos']          ?? [],  // array módulos permitidos
                'sessionId'   => $datosWA['sessionId']         ?? 'wa_' . ($datosWA['telefono'] ?? ''),
                'canal'       => 'whatsapp',
                'telefono'    => $datosWA['telefono']          ?? null,
                'autenticado' => (bool)($datosWA['autenticado'] ?? false),
            ];
        }

        // ── Canal Web — leer $_SESSION ─────────────────────────────────
        $idUsuario = intval(
            $_SESSION['id_usuario']  ??
            $_SESSION['usuario_id']  ?? 0
        );
        $nombres = trim(
            ($_SESSION['usuario_nombre'] ?? $_SESSION['nombres'] ?? 'Usuario') . ' ' .
            ($_SESSION['apellidos'] ?? '')
        );
        $rol = strtolower(
            $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? 'usuario'
        );
        $dni = $_SESSION['documento'] ?? $_SESSION['usuario_dni'] ?? '';

        // Cargar permisos del usuario web en tiempo real
        $permisos = [];
        if ($idUsuario > 0) {
            $permisos = self::_cargarPermisosWeb($idUsuario, $rol);
        }

        return [
            'idUsuario'   => $idUsuario,
            'dni'         => $dni,
            'nombres'     => $nombres,
            'rol'         => $rol,
            'area'        => $_SESSION['area'] ?? '',
            'permisos'    => $permisos,
            'sessionId'   => session_id() ?: 'web_' . $idUsuario,
            'canal'       => 'web',
            'telefono'    => null,
            'autenticado' => ($idUsuario > 0),
        ];
    }

    /**
     * Carga permisos de la BD para el canal web.
     * Admin/técnico reciben todos los módulos automáticamente.
     */
    private static function _cargarPermisosWeb(int $idUsuario, string $rol): array
    {
        if (in_array($rol, ['admin', 'administrador'])) {
            // Admin ve todo — devolver lista completa de módulos
            return ['inventario','reportestecnicos','salas','certificados',
                    'adquisiciones','patrimonio','agricola','usuarios','soporte','laboratorio'];
        }
        $conn = Conexion::conectar();
        if (!$conn) return [];
        $sql = "
            SELECT m.nombre FROM comun.Permisos p
            INNER JOIN comun.Modulos m ON p.id_modulo = m.id_modulo
            WHERE p.id_usuario = ? AND p.pueden_ver = 1
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idUsuario, SQLSRV_PARAM_IN]]);
        $modulos = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $modulos[] = strtolower($r['nombre']);
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $modulos;
    }

    // ══════════════════════════════════════════════════════════════════════
    // RAG — BÚSQUEDA CON FILTRO DE PERMISOS
    // ══════════════════════════════════════════════════════════════════════

    static public function mdlBuscarRAG(string $mensaje, array $perfil): array
    {
        $conn    = Conexion::conectar();
        if (!$conn) return [];

        $palabras = self::_extraerPalabras($mensaje);
        $rol      = $perfil['rol'];
        $canal    = $perfil['canal'];
        $permisos = $perfil['permisos'] ?? [];
        $esAdmin  = in_array($rol, ['admin', 'administrador', 'tecnico']);

        if (empty($palabras)) { sqlsrv_close($conn); return []; }

        // Construir score
        $condiciones = [];
        $params      = [];
        foreach ($palabras as $p) {
            $condiciones[] = "CASE WHEN LOWER(r.palabrasClave) LIKE ? THEN 1 ELSE 0 END";
            $params[]      = ['%' . $p . '%', SQLSRV_PARAM_IN];
        }
        $score = implode(' + ', $condiciones);

        // Filtro de canal y score
        $sql = "
            SELECT TOP 3
                r.idEjemplo, r.preguntaEjemplo, r.palabrasClave,
                r.schemaObjetivo, r.sqlQuery, r.respuestaBase,
                r.rolPermitido, r.areaPermitida, r.vecesUtil,
                ({$score}) AS score
            FROM [chaviBot].[RAG] r
            WHERE r.activo = 1
              AND (r.canal = 'ambos' OR r.canal = ?)
              AND ({$score}) > 0
            ORDER BY ({$score}) DESC, r.vecesUtil DESC
        ";
        $canalParam = [$canal, SQLSRV_PARAM_IN];
        $allParams  = array_merge($params, [$canalParam], $params, $params);

        $stmt = sqlsrv_query($conn, $sql, $allParams);
        $rows = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);

        // ── Filtrar por permisos si NO es admin ───────────────────────
        if (!$esAdmin && !empty($permisos)) {
            $rows = array_filter($rows, function($ej) use ($permisos) {
                return self::_tienePermiso($ej['schemaObjetivo'] ?? '', $permisos);
            });
            $rows = array_values($rows);
        }

        if (!empty($rows)) self::_incrementarUsoRAG(array_column($rows, 'idEjemplo'));
        return $rows;
    }

    /**
     * Verifica si el schema del RAG está dentro de los módulos permitidos del usuario.
     * Ej: schema = 'inventario.activo' → módulo 'inventario'
     */
    private static function _tienePermiso(string $schema, array $permisos): bool
    {
        if (empty($schema)) return true; // consultas genéricas siempre permitidas
        $modSchema = strtolower(explode('.', $schema)[0]);

        foreach (self::$MODULO_MAP as $moduloChavibot => $modulosSistema) {
            if ($modSchema === $moduloChavibot) {
                // Si alguno de los módulos del sistema está en los permisos del usuario
                foreach ($modulosSistema as $ms) {
                    if (in_array($ms, $permisos)) return true;
                }
                return false; // módulo encontrado pero sin permiso
            }
        }
        // Schema no mapeado → permitir (consulta genérica)
        return true;
    }

    // ══════════════════════════════════════════════════════════════════════
    // EJECUTAR QUERY RAG (con sustitución de placeholders)
    // ══════════════════════════════════════════════════════════════════════

    static public function mdlEjecutarQueryRAG(string $sql, array $perfil): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];

        // Sustituir placeholders
        $sql = str_replace(
            ['{{DNI}}', '{{USUARIO_ID}}', '{{AREA}}'],
            [$perfil['dni'], $perfil['idUsuario'], $perfil['area']],
            $sql
        );
        // {{PARAM}} se sustituye desde el bot antes de llamar a este método
        // (ya viene sustituido en el mensaje)

        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    // ══════════════════════════════════════════════════════════════════════
    // RESTO DE MÉTODOS (sin cambios respecto a v1)
    // ══════════════════════════════════════════════════════════════════════

    static public function mdlObtenerHistorial(string $sessionId, int $max = 4): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];
        $sql = "
            SELECT TOP {$max} pregunta, respuesta,
                   FORMAT(fechaCreacion,'HH:mm') AS hora
            FROM [chaviBot].[Historial]
            WHERE sessionId = ?
            ORDER BY fechaCreacion DESC
        ";
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
        $sql = "{call chaviBot.sp_GuardarHistorial(?,?,?,?,?,?,?,?,?,?,?,?,?)}";
        $params = [
            [$d['sessionId'],                         SQLSRV_PARAM_IN],
            [intval($d['idUsuario']),                 SQLSRV_PARAM_IN],
            [$d['dni']       ?? '',                   SQLSRV_PARAM_IN],
            [$d['nombres']   ?? '',                   SQLSRV_PARAM_IN],
            [$d['rol']       ?? 'usuario',            SQLSRV_PARAM_IN],
            [$d['area']      ?? '',                   SQLSRV_PARAM_IN],
            [$d['pregunta'],                          SQLSRV_PARAM_IN],
            [$d['respuesta'],                         SQLSRV_PARAM_IN],
            [$d['schema']    ?? '',                   SQLSRV_PARAM_IN],
            [intval($d['filas']   ?? 0),              SQLSRV_PARAM_IN],
            [intval($d['tiempoMs']?? 0),              SQLSRV_PARAM_IN],
            [$d['canal']     ?? 'web',                SQLSRV_PARAM_IN],
            [$d['telefono']  ?? null,                 SQLSRV_PARAM_IN],
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        $id = 0;
        if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)))
            $id = intval($row[0]);
        sqlsrv_close($conn);
        return $id;
    }

    static public function mdlAgregarRAG(array $d): int
    {
        $conn = Conexion::conectar();
        if (!$conn) return 0;
        $stmt = sqlsrv_query($conn,
            "{call chaviBot.sp_AgregarRAG(?,?,?,?,?,?,?,?,?)}",
            [
                [$d['pregunta'],            SQLSRV_PARAM_IN],
                [$d['palabras'],            SQLSRV_PARAM_IN],
                [$d['schema'],              SQLSRV_PARAM_IN],
                [$d['sql'],                 SQLSRV_PARAM_IN],
                [$d['respBase'] ?? '',      SQLSRV_PARAM_IN],
                [$d['rol']      ?? null,    SQLSRV_PARAM_IN],
                [$d['area']     ?? null,    SQLSRV_PARAM_IN],
                [$d['canal']    ?? 'ambos', SQLSRV_PARAM_IN],
                [intval($d['idUsuario']??0),SQLSRV_PARAM_IN],
            ]
        );
        $id = 0;
        if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)))
            $id = intval($row[0]);
        sqlsrv_close($conn);
        return $id;
    }

    static public function mdlListarRAG(): array
    {
        $conn = Conexion::conectar();
        if (!$conn) return [];
        $sql = "
            SELECT idEjemplo, preguntaEjemplo, palabrasClave, schemaObjetivo,
                   canal, activo, vecesUsado, vecesUtil,
                   FORMAT(fechaCreacion,'dd/MM/yyyy') AS fechaCreacion
            FROM [chaviBot].[RAG]
            ORDER BY fechaCreacion DESC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    static public function mdlToggleRAG(int $id, bool $activo): bool
    {
        $conn = Conexion::conectar();
        if (!$conn) return false;
        $stmt = sqlsrv_query($conn,
            "UPDATE [chaviBot].[RAG] SET activo=? WHERE idEjemplo=?",
            [[$activo ? 1 : 0, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]
        );
        $ok = ($stmt !== false);
        sqlsrv_close($conn);
        return $ok;
    }

    static public function mdlGuardarFeedback(int $idMsg, int $uid, bool $util, string $com): bool
    {
        $conn = Conexion::conectar();
        if (!$conn) return false;
        $stmt = sqlsrv_query($conn,
            "{call chaviBot.sp_GuardarFeedback(?,?,?,?)}",
            [[$idMsg,SQLSRV_PARAM_IN],[$uid,SQLSRV_PARAM_IN],[$util?1:0,SQLSRV_PARAM_IN],[$com,SQLSRV_PARAM_IN]]
        );
        sqlsrv_close($conn);
        return ($stmt !== false);
    }

    // ── Helpers privados ──────────────────────────────────────────────────
    private static function _extraerPalabras(string $texto): array
    {
        $texto = mb_strtolower(preg_replace('/[^a-záéíóúüñA-ZÁÉÍÓÚÜÑ0-9\s]/u', ' ', $texto));
        $stop  = ['de','la','el','los','las','un','una','que','hay','me','mi',
                  'en','es','con','para','por','como','del','al','se','y','o'];
        $words = preg_split('/\s+/', $texto, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter($words,
            fn($w) => strlen($w) > 2 && !in_array($w, $stop))));
    }

    private static function _incrementarUsoRAG(array $ids): void
    {
        if (empty($ids)) return;
        $conn = Conexion::conectar();
        if (!$conn) return;
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $prms = array_map(fn($id) => [$id, SQLSRV_PARAM_IN], $ids);
        sqlsrv_query($conn,
            "UPDATE [chaviBot].[RAG] SET vecesUsado = vecesUsado + 1 WHERE idEjemplo IN ({$ph})",
            $prms
        );
        sqlsrv_close($conn);
    }
}
