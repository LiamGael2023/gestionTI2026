<?php
/**
 * CalidadAPI.php
 * Maneja Calidad Superficial (calidadaguasuperficial{año}.monitoreosuperficial{mes})
 * y Calidad Drenes (calidadaguadrenada{año}.monitoreodrenaje{mes}).
 *
 * Acciones:
 *  - listar_esquemas                  (GET)  → esquemas/tablas disponibles por tipo y año
 *  - importar_calidad_init            (GET)  → lotes de un tipo+año(+mes)
 *  - importar_calidad_batch           (POST) → crea proyecto (año+mes) + muestra + solicitudes/resultados
 *  - importar_calidad_historial_init  (GET)  → lotes de TODO el historial (todos los años)
 *  - obtener_mapeo / guardar_mapeo           → pantalla de mapeo parámetro → columna PG
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (strpos($errstr, 'session') !== false || strpos($errstr, 'ini_set') !== false) {
        return true;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    require_once '../../../../config/config.php';
    require_once '../../../../config/db.php';
    require_once '../../../../config/db_postgresql.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['usuario_id'])) {
        // No usar HTTP 500 para autenticación: el frontend distingue 401 (sin sesión → login)
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $usuario_id = intval($_SESSION['usuario_id']);
    $conn = Conexion::conectar();
    if (!$conn) throw new Exception('No se pudo conectar a SQL Server.');

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // ==================== LISTAR ESQUEMAS Y TABLAS ====================
    if ($action === 'listar_esquemas') {
        $pdoPg = ConexionPostgreSQL::conectar();
        if (!$pdoPg) throw new Exception('No se pudo conectar a PostgreSQL.');

        $tipos = [
            'superficial' => ['esquema_prefix' => 'calidadaguasuperficial', 'tabla_prefijos' => ['monitoreosuperficial', 'monitoreosuperficia']],
            'drene'       => ['esquema_prefix' => 'calidadaguadrenada',     'tabla_prefijos' => ['monitoreodrenaje']],
        ];

        $resultado = [];
        foreach ($tipos as $tipo => $cfg) {
            $anios = [];
            $stmt = $pdoPg->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE '" . $cfg['esquema_prefix'] . "%' ORDER BY schema_name");
            foreach ($stmt as $r) {
                $esquema = $r['schema_name'];
                $anio = preg_replace('/^' . $cfg['esquema_prefix'] . '/', '', $esquema);
                if (!preg_match('/^\d{4}$/', $anio)) continue;

                $meses = [];
                $prefixLike = implode("' OR table_name LIKE '", array_map(function ($p) { return $p . '%'; }, $cfg['tabla_prefijos']));
                $stmtTab = $pdoPg->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$esquema' AND (table_name LIKE '$prefixLike') ORDER BY table_name");
                foreach ($stmtTab as $t) {
                    $tabla = $t['table_name'];
                    if (strpos($tabla, '_anual') !== false) continue; // excluir consolidados anuales
                    $mes = '';
                    foreach ($cfg['tabla_prefijos'] as $pref) {
                        if (strpos($tabla, $pref) === 0) {
                            $mes = substr($tabla, strlen($pref));
                            break;
                        }
                    }
                    if ($mes !== '') {
                        $meses[] = ['tabla' => $esquema . '.' . $tabla, 'mes' => ucfirst($mes)];
                    }
                }
                if (!empty($meses)) {
                    $anios[] = ['anio' => $anio, 'esquema' => $esquema, 'tablas' => $meses];
                }
            }
            $resultado[$tipo] = $anios;
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'esquemas' => $resultado], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== MAPEO: OBTENER ====================
    if ($action === 'obtener_mapeo') {
        $sql = "SELECT mc.Id_Mapeo, mc.Id_Parametro, mc.Tipo_Calidad, mc.Columna_PG,
                       pa.Nombre AS Parametro, pa.Categoria, ISNULL(um.Abreviatura, pa.Unidad_Medida) AS Unidad
                FROM laboratorio.Mapeo_Calidad mc
                INNER JOIN laboratorio.Parametro_Analisis pa ON pa.Id_Parametro = mc.Id_Parametro
                LEFT JOIN laboratorio.Unidad_Medida um ON um.Id_Unidad_Medida = pa.Id_Unidad_Medida AND um.Activo = 1
                WHERE mc.Activo = 1
                ORDER BY mc.Tipo_Calidad, pa.Categoria, pa.Nombre";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) throw new Exception('Error al obtener mapeo: ' . print_r(sqlsrv_errors(), true));

        $mapeo = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $mapeo[] = [
                'id_mapeo'    => intval($row['Id_Mapeo']),
                'id_parametro'=> intval($row['Id_Parametro']),
                'tipo'        => $row['Tipo_Calidad'],
                'columna'     => $row['Columna_PG'],
                'parametro'   => $row['Parametro'],
                'categoria'   => $row['Categoria'],
                'unidad'      => $row['Unidad'],
            ];
        }

        // Parámetros activos sin mapeo (para agregar)
        $sqlSin = "SELECT pa.Id_Parametro, pa.Nombre, pa.Categoria, ISNULL(um.Abreviatura, pa.Unidad_Medida) AS Unidad
                   FROM laboratorio.Parametro_Analisis pa
                   LEFT JOIN laboratorio.Unidad_Medida um ON um.Id_Unidad_Medida = pa.Id_Unidad_Medida AND um.Activo = 1
                   WHERE pa.Activo = 1
                   ORDER BY pa.Categoria, pa.Nombre";
        $stmtSin = sqlsrv_query($conn, $sqlSin);
        $sinMapeo = [];
        while ($row = sqlsrv_fetch_array($stmtSin, SQLSRV_FETCH_ASSOC)) {
            $sinMapeo[] = [
                'id_parametro' => intval($row['Id_Parametro']),
                'nombre' => $row['Nombre'],
                'categoria' => $row['Categoria'],
                'unidad' => $row['Unidad'],
            ];
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'mapeo' => $mapeo, 'parametros' => $sinMapeo], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== MAPEO: GUARDAR ====================
    if ($action === 'guardar_mapeo') {
        $items = json_decode(file_get_contents('php://input'), true);
        if (!is_array($items)) throw new Exception('Formato inválido');

        sqlsrv_begin_transaction($conn);
        try {
            $count = 0;
            foreach ($items as $item) {
                $id_param = intval($item['id_parametro'] ?? 0);
                $tipo = trim((string)($item['tipo'] ?? ''));
                $columna = trim((string)($item['columna'] ?? ''));
                if ($id_param <= 0 || !in_array($tipo, ['superficial', 'drene'], true) || $columna === '') continue;

                $stmtChk = sqlsrv_query($conn, "SELECT Id_Mapeo FROM laboratorio.Mapeo_Calidad
                                                WHERE Id_Parametro = ? AND Tipo_Calidad = ? AND Columna_PG = ? AND Activo = 1",
                                                [$id_param, $tipo, $columna]);
                if ($stmtChk !== false && sqlsrv_has_rows($stmtChk)) continue; // ya existe

                $stmtIns = sqlsrv_query($conn, "INSERT INTO laboratorio.Mapeo_Calidad (Id_Parametro, Tipo_Calidad, Columna_PG, Activo, Usuario_Creacion, Fecha_Creacion)
                                                VALUES (?, ?, ?, 1, ?, GETDATE())",
                                                [$id_param, $tipo, $columna, $usuario_id]);
                if ($stmtIns !== false) $count++;
            }
            sqlsrv_commit($conn);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'guardados' => $count], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== MAPEO: GUARDAR EDICIÓN DE COLUMNAS EXISTENTES ====================
    if ($action === 'guardar_mapeo_edicion') {
        $items = json_decode(file_get_contents('php://input'), true);
        if (!is_array($items)) throw new Exception('Formato inválido');

        sqlsrv_begin_transaction($conn);
        try {
            $count = 0;
            foreach ($items as $item) {
                $id_mapeo = intval($item['id_mapeo'] ?? 0);
                $columna  = trim((string)($item['columna'] ?? ''));
                if ($id_mapeo <= 0 || $columna === '') continue;
                $stmtUpd = sqlsrv_query($conn, "UPDATE laboratorio.Mapeo_Calidad SET Columna_PG = ? WHERE Id_Mapeo = ?", [$columna, $id_mapeo]);
                if ($stmtUpd !== false) $count++;
            }
            sqlsrv_commit($conn);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'actualizados' => $count], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== ACTUALIZAR NOMBRE DEL DREN ====================
    if ($action === 'actualizar_drene') {
        $id_muestra = intval($_POST['id_muestra'] ?? 0);
        $nombre     = trim((string)($_POST['nombre'] ?? ''));
        if ($id_muestra <= 0 || $nombre === '') throw new Exception('Datos incompletos');

        // La muestra debe ser de drene
        $stmtChk = sqlsrv_query($conn, "SELECT m.Id_Muestra, m.Observacion_Muestra FROM laboratorio.Muestra_Lab m
                                        WHERE m.Id_Muestra = ? AND m.Activo = 1 AND m.Es_Drene = 1", [$id_muestra]);
        $rowChk  = ($stmtChk !== false) ? sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC) : null;
        if (!$rowChk) throw new Exception('Muestra de dren no encontrada o no es tipo drene.');

        // Nombre viejo del Detalle_Agua (para reemplazarlo en la observación)
        $stmtD = sqlsrv_query($conn, "SELECT Nivel_Agua FROM laboratorio.Detalle_Agua WHERE Id_Muestra = ? AND Activo = 1", [$id_muestra]);
        $rowD  = ($stmtD !== false) ? sqlsrv_fetch_array($stmtD, SQLSRV_FETCH_ASSOC) : null;
        $nivel_viejo = trim((string)($rowD['Nivel_Agua'] ?? ''));

        sqlsrv_begin_transaction($conn);
        try {
            if ($rowD) {
                sqlsrv_query($conn, "UPDATE laboratorio.Detalle_Agua SET Nivel_Agua = ?, Fecha_Modificacion = GETDATE()
                                     WHERE Id_Muestra = ? AND Activo = 1", [$nombre, $id_muestra]);
            } else {
                sqlsrv_query($conn, "INSERT INTO laboratorio.Detalle_Agua (Id_Muestra, Nivel_Agua, Activo, Fecha_Creacion, Usuario_Creacion)
                                     VALUES (?, ?, 1, GETDATE(), ?)", [$id_muestra, $nombre, $usuario_id]);
            }

            // Si la observación (descripción completa) contiene el nombre viejo, reemplazarlo
            $obs_vieja = trim((string)($rowChk['Observacion_Muestra'] ?? ''));
            if ($nivel_viejo !== '' && $obs_vieja !== '') {
                $posNombre = stripos($obs_vieja, $nivel_viejo);
                if ($posNombre !== false) {
                    $obs_nueva = substr($obs_vieja, 0, $posNombre) . $nombre . substr($obs_vieja, $posNombre + strlen($nivel_viejo));
                    sqlsrv_query($conn, "UPDATE laboratorio.Muestra_Lab SET Observacion_Muestra = ? WHERE Id_Muestra = ?", [$obs_nueva, $id_muestra]);
                }
            }

            sqlsrv_commit($conn);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'id_muestra' => $id_muestra, 'nombre' => $nombre], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== INIT: lotes de un tipo+año(+mes) ====================
    if ($action === 'importar_calidad_init' || $action === 'importar_calidad_historial_init') {
        set_time_limit(300);
        $pdoPg = ConexionPostgreSQL::conectar();
        if (!$pdoPg) throw new Exception('No se pudo conectar a PostgreSQL.');

        $esHistorial = ($action === 'importar_calidad_historial_init');
        $tipoSel = strtolower(trim((string)($_GET['tipo'] ?? '')));
        if (!$esHistorial && !in_array($tipoSel, ['superficial', 'drene'], true)) throw new Exception('Tipo inválido');
        // Historial: recorre AMBOS tipos (superficial + drene). Normal: solo el tipo indicado.
        $tiposRecorrer = $esHistorial ? ['superficial', 'drene'] : [$tipoSel];
        $anioSel = $esHistorial ? '' : trim((string)($_GET['anio'] ?? ''));
        $mesSel  = $esHistorial ? '' : strtolower(trim((string)($_GET['mes'] ?? '')));
        $llenar  = intval($_GET['llenar_resultados'] ?? ($esHistorial ? 1 : 0)) === 1 ? 1 : 0;

        $lotes = [];
        $proyectosInfo = [];

        foreach ($tiposRecorrer as $tipoActual) {
        $cfg = ($tipoActual === 'superficial')
            ? ['esquema_prefix' => 'calidadaguasuperficial', 'tabla_prefijos' => ['monitoreosuperficial', 'monitoreosuperficia'], 'proyecto_nombre' => 'CALIDAD SUPERFICIAL']
            : ['esquema_prefix' => 'calidadaguadrenada',     'tabla_prefijos' => ['monitoreodrenaje'], 'proyecto_nombre' => 'CALIDAD DRENES'];

        $esquemas = [];
        $stmt = $pdoPg->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE '" . $cfg['esquema_prefix'] . "%' ORDER BY schema_name");
        foreach ($stmt as $r) {
            $esquema = $r['schema_name'];
            $anio = preg_replace('/^' . $cfg['esquema_prefix'] . '/', '', $esquema);
            if (!preg_match('/^\d{4}$/', $anio)) continue;
            if ($anioSel !== '' && $anio !== $anioSel) continue;
            $esquemas[$anio] = $esquema;
        }

        foreach ($esquemas as $anio => $esquema) {
            $prefixLike = implode("' OR table_name LIKE '", array_map(function ($p) { return $p . '%'; }, $cfg['tabla_prefijos']));
            $stmtTab = $pdoPg->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$esquema' AND (table_name LIKE '$prefixLike') ORDER BY table_name");
            foreach ($stmtTab as $t) {
                $tabla = $t['table_name'];
                if (strpos($tabla, '_anual') !== false) continue;
                $mes = '';
                foreach ($cfg['tabla_prefijos'] as $pref) {
                    if (strpos($tabla, $pref) === 0) { $mes = substr($tabla, strlen($pref)); break; }
                }
                if ($mes === '') continue;
                if ($mesSel !== '' && $mes !== $mesSel) continue;

                $nombreProyecto = $cfg['proyecto_nombre'] . ' ' . $anio . ' - ' . strtoupper($mes);

                // Contar filas y obtener datos (id, orden, descripcion, fechamonitoreo)
                $sqlFilas = "SELECT id, orden, descripcion, fechamonitoreo FROM $esquema.$tabla
                             WHERE id IS NOT NULL ORDER BY orden, id";
                $stmtFilas = $pdoPg->query($sqlFilas);
                $nFilas = 0;
                foreach ($stmtFilas as $fila) {
                    $desc = trim((string)($fila['descripcion'] ?? ''));
                    $fecha = trim((string)($fila['fechamonitoreo'] ?? ''));
                    $lotes[] = [
                        'id_fila'        => intval($fila['id'] ?? 0),
                        'tipo'           => $tipoActual,
                        'anio'           => $anio,
                        'mes'            => ucfirst($mes),
                        'esquema'        => $esquema,
                        'tabla'          => $tabla,
                        'descripcion'    => $desc,
                        'fechamonitoreo' => $fecha,
                        'orden'          => intval($fila['orden'] ?? 0),
                        'proyecto'       => $nombreProyecto,
                    ];
                    $nFilas++;
                }
                if ($nFilas > 0) {
                    if (!isset($proyectosInfo[$nombreProyecto])) {
                        $proyectosInfo[$nombreProyecto] = ['nombre' => $nombreProyecto, 'tipo' => $tipoActual, 'anio' => $anio, 'mes' => ucfirst($mes), 'muestras' => 0];
                    }
                    $proyectosInfo[$nombreProyecto]['muestras'] += $nFilas;
                }
            }
        }
        } // foreach tiposRecorrer

        ob_end_clean();
        echo json_encode([
            'success'     => true,
            'lotes'       => $lotes,
            'total_lotes' => count($lotes),
            'proyectos'   => array_values($proyectosInfo),
            'llenar_resultados' => $llenar
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== BATCH: procesar una fila ====================
    if ($action === 'importar_calidad_batch') {
        set_time_limit(120);

        $tipo        = strtolower(trim((string)($_POST['tipo'] ?? '')));
        $anio        = trim((string)($_POST['anio'] ?? ''));
        $mes         = trim((string)($_POST['mes'] ?? ''));
        $esquema     = trim((string)($_POST['esquema'] ?? ''));
        $tabla       = trim((string)($_POST['tabla'] ?? ''));
        $id_fila     = intval($_POST['id_fila'] ?? 0);
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $fecha_toma  = trim((string)($_POST['fechamonitoreo'] ?? ''));
        $nombre_proyecto = trim((string)($_POST['proyecto'] ?? ''));
        $llenar      = intval($_POST['llenar_resultados'] ?? 0) === 1 ? 1 : 0;

        if (!in_array($tipo, ['superficial', 'drene'], true) || $esquema === '' || $tabla === '' || $nombre_proyecto === '') {
            throw new Exception('Datos incompletos en lote de calidad.');
        }
        if ($descripcion === '') $descripcion = $tipo === 'drene' ? 'DREN SIN DESCRIPCION' : 'PUNTO SIN DESCRIPCION';
        $fecha_toma = ($fecha_toma !== '') ? date('Y-m-d', strtotime($fecha_toma)) : null;

        // 1. Cliente
        $stmtCli = sqlsrv_query($conn, "SELECT TOP 1 Id_Cliente FROM laboratorio.Cliente WHERE Activo = 1 ORDER BY Id_Cliente");
        $rowCli  = ($stmtCli !== false) ? sqlsrv_fetch_array($stmtCli, SQLSRV_FETCH_ASSOC) : null;
        $id_cliente = $rowCli ? intval($rowCli['Id_Cliente']) : 1;

        // 2. Parámetros del mapeo de calidad para este tipo (con su servicio)
        $stmtMap = sqlsrv_query($conn, "SELECT pa.Id_Parametro, pa.Id_Servicio, mc.Columna_PG
                                        FROM laboratorio.Mapeo_Calidad mc
                                        INNER JOIN laboratorio.Parametro_Analisis pa ON pa.Id_Parametro = mc.Id_Parametro AND pa.Activo = 1
                                        WHERE mc.Tipo_Calidad = ? AND mc.Activo = 1", [$tipo]);
        if ($stmtMap === false) throw new Exception('Error al obtener mapeo de calidad: ' . print_r(sqlsrv_errors(), true));
        $parametrosPorServicio = []; // id_serv => [ [id_param, col_pg], ... ]
        $columnasPG = [];
        while ($rowM = sqlsrv_fetch_array($stmtMap, SQLSRV_FETCH_ASSOC)) {
            $id_param = intval($rowM['Id_Parametro']);
            $id_serv  = intval($rowM['Id_Servicio'] ?? 0);
            $col      = trim((string)($rowM['Columna_PG'] ?? ''));
            if ($id_param <= 0 || $col === '') continue;
            if (!isset($parametrosPorServicio[$id_serv])) $parametrosPorServicio[$id_serv] = [];
            $parametrosPorServicio[$id_serv][] = ['id_param' => $id_param, 'col' => $col];
            $columnasPG[$col] = $id_param;
        }
        if (empty($parametrosPorServicio)) {
            throw new Exception("No hay parámetros mapeados para tipo '$tipo'. Configure el mapeo de calidad primero.");
        }

        // 3. Buscar o crear proyecto (Es_Control_Calidad=1 superficial / Es_Drene=1 drene)
        $es_control_calidad = ($tipo === 'superficial') ? 1 : 0;
        $es_drene           = ($tipo === 'drene') ? 1 : 0;
        $tipo_servicio      = ($tipo === 'superficial') ? 'Calidad Superficial' : 'Calidad Drenes';

        $stmtChkP = sqlsrv_query($conn, "SELECT TOP 1 pm.Id_Proyecto,
                                            (SELECT COUNT(*) FROM laboratorio.Muestra_Lab ml WHERE ml.Id_Proyecto = pm.Id_Proyecto AND ml.Activo = 1) AS n_muestras
                                         FROM laboratorio.Proyecto_Monitoreo pm
                                         WHERE pm.Nombre_Proyecto = ? AND pm.Activo = 1
                                           AND " . ($tipo === 'superficial' ? 'pm.Es_Control_Calidad = 1' : 'pm.Es_Drene = 1') . "
                                         ORDER BY n_muestras DESC, pm.Fecha_Creacion DESC", [$nombre_proyecto]);
        $rowChkP  = ($stmtChkP !== false) ? sqlsrv_fetch_array($stmtChkP, SQLSRV_FETCH_ASSOC) : null;

        if ($rowChkP) {
            $id_proyecto = intval($rowChkP['Id_Proyecto']);
            // ⚠️ ANTI-DUPLICADO (2026-08): si el proyecto existente está 'Planificado'
            // (creado manualmente), la extracción YA inicia el análisis → pasar a
            // 'En Progreso' para que NO aparezca el botón "Iniciar Ejecución"
            // (evita que generarMuestras cree una tanda duplicada).
            sqlsrv_query($conn,
                "UPDATE laboratorio.Proyecto_Monitoreo SET Estado = 'En Progreso', Fecha_Modificacion = GETDATE()
                 WHERE Id_Proyecto = ? AND Estado = 'Planificado'",
                [$id_proyecto]);
        } else {
            // Estado al crear según el tipo de flujo (2026-08):
            //  - EXTRACCIÓN (llenar=0): 'En Progreso' — se extrae cuando ya comienza el análisis,
            //    las muestras quedan vacías para llenar resultados (antes se creaba 'Terminado'
            //    y el proyecto no se podía analizar sin reabrirlo).
            //  - HISTORIAL (llenar=1): 'Terminado' — los datos importados ya vienen con valores;
            //    el usuario puede reabrirlo con el botón "Reabrir Proyecto".
            $estado_nuevo_proy = ($llenar === 1) ? 'Terminado' : 'En Progreso';
            $sqlProy = "INSERT INTO laboratorio.Proyecto_Monitoreo
                (Nombre_Proyecto, Valle, Temporada, Fecha_Inicio, Tipo_Muestra, Uso_Agua, Fuente_Agua, Nivel_Agua,
                 Es_Control_Calidad, Es_Drene, Es_Pozos, Id_Responsable, Estado, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, 'Otros', ?, ?, 'Agua', 'Otros', 'Superficial', NULL, ?, ?, 0, ?, ?, ?, 1, GETDATE());
                SELECT SCOPE_IDENTITY() AS id;";
            $stmtProy = sqlsrv_query($conn, $sqlProy, [
                $nombre_proyecto, ($tipo === 'superficial' ? $anio . '-01-01' : ($fecha_toma ?: date('Y-m-d'))),
                ($tipo === 'superficial' ? $anio : $anio), $es_control_calidad, $es_drene, $usuario_id, $estado_nuevo_proy, $usuario_id
            ]);
            if ($stmtProy === false) throw new Exception('Error al crear proyecto: ' . print_r(sqlsrv_errors(), true));
            sqlsrv_next_result($stmtProy);
            $rowProy = sqlsrv_fetch_array($stmtProy, SQLSRV_FETCH_ASSOC);
            $id_proyecto = intval($rowProy['id'] ?? 0);
            if ($id_proyecto <= 0) throw new Exception('No se obtuvo Id_Proyecto para ' . $nombre_proyecto);
        }

        // 4. Transacción por lote
        sqlsrv_begin_transaction($conn);
        try {
            // ── Nombre corto del dren/río (antes de ":") para Detalle_Agua ──
            $nombre_corto = $descripcion;
            $posColon = strpos($descripcion, ':');
            if ($posColon !== false) {
                $nombre_corto = trim(substr($descripcion, 0, $posColon));
            }
            // Observacion_Muestra SIEMPRE con prefijo "Calidad {tipo} - " (así se guarda en BD)
            $obs = "Calidad $tipo - $descripcion";

            // ── Idempotencia (decisión del usuario: si ya existe → actualizar, si falta → crear) ──
            // 1) Match por Id_Medicion_PG (id de la fila PG — clave estable, nunca cambia)
            // 2) Fallback por Observacion_Muestra COMPLETA ("Calidad {tipo} - {descripcion}")
            //    ⚠️ BUG CORREGIDO: antes se comparaba contra $descripcion CRUDA (sin el prefijo
            //    "Calidad {tipo} - ") → NUNCA coincidía → cada extracción creaba una muestra nueva
            //    (duplicados). Ese era el "mismo error" de duplicación en Extraer Calidad.
            $stmtChkM = null;
            if ($id_fila > 0) {
                $stmtChkM = sqlsrv_query($conn, "SELECT Id_Muestra FROM laboratorio.Muestra_Lab
                                                 WHERE Id_Proyecto = ? AND Id_Medicion_PG = ? AND Activo = 1",
                                                 [$id_proyecto, $id_fila]);
            }
            if (!$stmtChkM || !sqlsrv_has_rows($stmtChkM)) {
                $stmtChkM = sqlsrv_query($conn, "SELECT Id_Muestra FROM laboratorio.Muestra_Lab
                                                 WHERE Id_Proyecto = ? AND LTRIM(RTRIM(Observacion_Muestra)) = LTRIM(RTRIM(?)) AND Activo = 1",
                                                 [$id_proyecto, $obs]);
            }
            $rowChkM = ($stmtChkM !== false) ? sqlsrv_fetch_array($stmtChkM, SQLSRV_FETCH_ASSOC) : null;

            $resultadosCreados = 0;
            if ($rowChkM) {
                $id_muestra = intval($rowChkM['Id_Muestra']);

                // Ya existe → SOLO actualizar campos técnicos (fecha, vínculo PG).
                // NO pisar: Observacion_Muestra / Detalle_Agua.Nivel_Agua (el usuario puede haber
                // editado el nombre del dren/río con el lápiz ✏️) ni los resultados ya llenados.
                $stmtAct = sqlsrv_query($conn, "SELECT Id_Medicion_PG, CONVERT(varchar(10), Fecha_Toma, 120) AS Fecha_Toma
                                                FROM laboratorio.Muestra_Lab WHERE Id_Muestra = ?", [$id_muestra]);
                $rowAct  = ($stmtAct !== false) ? sqlsrv_fetch_array($stmtAct, SQLSRV_FETCH_ASSOC) : null;
                $sets = [];
                $prms = [];
                if ($rowAct) {
                    if ($id_fila > 0 && intval($rowAct['Id_Medicion_PG'] ?? 0) !== $id_fila) {
                        $sets[] = 'Id_Medicion_PG = ?';
                        $prms[] = $id_fila;
                    }
                    if ($fecha_toma !== null && trim((string)($rowAct['Fecha_Toma'] ?? '')) !== $fecha_toma) {
                        $sets[] = 'Fecha_Toma = ?';
                        $prms[] = $fecha_toma;
                    }
                }
                if (!empty($sets)) {
                    $prms[] = $id_muestra;
                    if (!sqlsrv_query($conn, "UPDATE laboratorio.Muestra_Lab SET " . implode(', ', $sets) . " WHERE Id_Muestra = ?", $prms)) {
                        throw new Exception('Error UPDATE Muestra_Lab existente: ' . print_r(sqlsrv_errors(), true));
                    }
                }
            } else {
                $sqlM = "INSERT INTO laboratorio.Muestra_Lab
                    (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Valle,
                     Fecha_Recepcion, Fecha_Toma, Estado, Tipo_Servicio, Observacion_Muestra,
                     Es_Control_Calidad, Es_Drene, Es_Pozo, Lab_Habilitado, Id_Medicion_PG,
                     Fecha_Analisis, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, 'Otros', ?, ?, 'En Analisis', ?, ?, ?, ?, 0, 0, ?, ?, ?, 1, GETDATE());
                    SELECT SCOPE_IDENTITY() AS id;";
                $stmtM = sqlsrv_query($conn, $sqlM, [
                    $id_cliente, $usuario_id, $usuario_id, $id_proyecto,
                    $fecha_toma, $fecha_toma, $tipo_servicio, $obs,
                    $es_control_calidad, $es_drene,
                    ($id_fila > 0 ? $id_fila : null), $fecha_toma, $usuario_id
                ]);
                if ($stmtM === false) throw new Exception('Error INSERT Muestra_Lab: ' . print_r(sqlsrv_errors(), true));
                sqlsrv_next_result($stmtM);
                $rowMId = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC);
                $id_muestra = intval($rowMId['id'] ?? 0);
                if ($id_muestra <= 0) throw new Exception('No se obtuvo Id_Muestra');
            }

            // 5. Detalle_Agua: Nivel_Agua = nombre corto del dren/río (insertar si falta;
            //    si ya existe NO se pisa — el usuario puede haberlo editado con el lápiz ✏️)
            if ($nombre_corto !== '') {
                $stmtChkDA = sqlsrv_query($conn, "SELECT 1 FROM laboratorio.Detalle_Agua WHERE Id_Muestra = ? AND Activo = 1", [$id_muestra]);
                if ($stmtChkDA !== false && !sqlsrv_has_rows($stmtChkDA)) {
                    sqlsrv_query($conn, "INSERT INTO laboratorio.Detalle_Agua (Id_Muestra, Nivel_Agua, Activo, Fecha_Creacion, Usuario_Creacion)
                                         VALUES (?, ?, 1, GETDATE(), ?)", [$id_muestra, $nombre_corto, $usuario_id]);
                }
            }

            // 6. Solicitudes + resultados por servicio (mapeo de calidad)
            // Si llenar_resultados=1, leer la fila completa de PG una sola vez
            $filaPG = null;
            if ($llenar === 1 && $id_fila > 0) {
                try {
                    $pdoPgCal = ConexionPostgreSQL::conectar();
                    if ($pdoPgCal) {
                        $esquemaSeguro = preg_replace('/[^a-zA-Z0-9_]/', '', $esquema);
                        $tablaSegura   = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
                        if ($esquemaSeguro !== '' && $tablaSegura !== '') {
                            $stmtPG = $pdoPgCal->prepare("SELECT * FROM $esquemaSeguro.$tablaSegura WHERE id = ?");
                            $stmtPG->execute([$id_fila]);
                            $filaPG = $stmtPG->fetch(PDO::FETCH_ASSOC);
                        }
                    }
                } catch (\Throwable $e) { $filaPG = null; }
            }

            $solicitudesPorServicio = [];
            foreach ($parametrosPorServicio as $id_servicio => $params) {
                $stmtChkS = sqlsrv_query($conn, "SELECT Id_Solicitud_Analisis FROM laboratorio.Solicitud_Analisis
                                                 WHERE Id_Muestra = ? AND Id_Servicio = ? AND Activo = 1", [$id_muestra, $id_servicio]);
                $rowChkS  = ($stmtChkS !== false) ? sqlsrv_fetch_array($stmtChkS, SQLSRV_FETCH_ASSOC) : null;
                if ($rowChkS) {
                    $id_solicitud = intval($rowChkS['Id_Solicitud_Analisis']);
                } else {
                    $stmtSol = sqlsrv_query($conn, "INSERT INTO laboratorio.Solicitud_Analisis (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Id_Analista, Usuario_Creacion, Activo, Fecha_Creacion)
                                                    VALUES (?, ?, 'En Analisis', ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;",
                                                    [$id_muestra, $id_servicio, ($fecha_toma ?: date('Y-m-d')), $usuario_id, $usuario_id]);
                    if ($stmtSol === false) continue;
                    sqlsrv_next_result($stmtSol);
                    $rowSol = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                    $id_solicitud = intval($rowSol['id'] ?? 0);
                }
                if ($id_solicitud <= 0) continue;
                $solicitudesPorServicio[$id_servicio] = $id_solicitud;

                foreach ($params as $p) {
                    $stmtChkR = sqlsrv_query($conn, "SELECT 1 FROM laboratorio.Resultado_Analisis
                                                     WHERE Id_Solicitud_Analisis = ? AND Id_Parametro = ? AND Activo = 1", [$id_solicitud, $p['id_param']]);
                    if ($stmtChkR !== false && sqlsrv_has_rows($stmtChkR)) continue;

                    // Valor: si llenar_resultados=1 y la columna existe en PG, copiar el valor
                    $valor = null;
                    if ($filaPG !== null && isset($filaPG[$p['col']]) && $filaPG[$p['col']] !== null && $filaPG[$p['col']] !== '') {
                        $valor = $filaPG[$p['col']];
                    }

                    $stmtRes = sqlsrv_query($conn, "INSERT INTO laboratorio.Resultado_Analisis (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                                                    VALUES (?, ?, ?, ?, 1, GETDATE())", [$id_solicitud, $p['id_param'], $valor, $usuario_id]);
                    if ($stmtRes !== false) $resultadosCreados++;
                }
            }

            // 7. RESIDUOS AUTOMÁTICOS: si la solicitud tiene ≥1 resultado con valor → Estado='Finalizado'.
            //    El trigger TR_Registrar_Residuos_Automatica se dispara SOLO en UPDATE con cambio a
            //    'Finalizado' (por eso la importación no registraba residuos: insertaba directo).
            //    Registra en Detalle_Residuos_Log los residuos definidos en Servicio_Residuo_Def
            //    (Cantidad_Estimada_Por_Muestra) y crea la cabecera del mes (Registro_Residuos_Log)
            //    si no existe. La condición Estado <> 'Finalizado' evita re-disparar.
            if (!empty($solicitudesPorServicio)) {
                foreach ($solicitudesPorServicio as $id_sol) {
                    $stmtCnt = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM laboratorio.Resultado_Analisis
                                                    WHERE Id_Solicitud_Analisis = ? AND Valor_Hallado IS NOT NULL AND Activo = 1", [$id_sol]);
                    $rowCnt  = ($stmtCnt !== false) ? sqlsrv_fetch_array($stmtCnt, SQLSRV_FETCH_ASSOC) : null;
                    $nValores = intval($rowCnt['n'] ?? 0);
                    if ($nValores > 0) {
                        $stmtUp = sqlsrv_query($conn, "UPDATE laboratorio.Solicitud_Analisis
                                                       SET Estado = 'Finalizado', Id_Analista = COALESCE(Id_Analista, ?)
                                                       WHERE Id_Solicitud_Analisis = ? AND Estado <> 'Finalizado'",
                                                       [$usuario_id, $id_sol]);
                        if ($stmtUp === false) throw new Exception('Error UPDATE estado solicitud (residuos): ' . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            sqlsrv_commit($conn);
            ob_end_clean();
            echo json_encode(['success' => true, 'id_muestra' => $id_muestra, 'resultados' => $resultadosCreados], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }
        exit;
    }

    // ==================== LIMPIAR CALIDAD: borra todo lo importado antes de reimportar ====================
    // Decisión del usuario: cada "Importar Historial Calidad" borra TODO lo de Calidad Superficial y
    // Calidad Drenes (proyectos, muestras, solicitudes, resultados, detalle, consumos...) para volver a
    // importarlo desde cero y NO duplicar nada. Orden de borrado = cadena de FKs (hijos primero).
    if ($action === 'limpiar_calidad') {
        set_time_limit(300);
        $filtro    = "(pm.Es_Control_Calidad = 1 OR pm.Es_Drene = 1)";
        $filtroProy = "(Es_Control_Calidad = 1 OR Es_Drene = 1)";

        $contar = function ($sql) use ($conn) {
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al limpiar calidad: ' . print_r(sqlsrv_errors(), true));
            }
            $n = sqlsrv_rows_affected($stmt);
            return ($n === false || $n === null) ? 0 : intval($n);
        };

        // Requerido para borrar de Monitoreo_Pozo_Asignacion (índice filtrado WHERE Activo=1 → error 1934 sin esto)
        sqlsrv_query($conn, "SET QUOTED_IDENTIFIER ON;");

        sqlsrv_begin_transaction($conn);
        try {
            $stats = [
                'consumos_resultado' => $contar("DELETE cr FROM laboratorio.Consumo_Resultado cr
                    INNER JOIN laboratorio.Resultado_Analisis ra ON ra.Id_Resultado = cr.Id_Resultado
                    INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'consumos_reaccion' => $contar("DELETE c FROM laboratorio.Consumo_Reaccion c
                    INNER JOIN laboratorio.Muestra_Producto mp ON mp.Id_Muestra_Producto = c.Id_Muestra_Producto
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = mp.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'resultados' => $contar("DELETE ra FROM laboratorio.Resultado_Analisis ra
                    INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'solicitudes' => $contar("DELETE sa FROM laboratorio.Solicitud_Analisis sa
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'detalle_agua' => $contar("DELETE d FROM laboratorio.Detalle_Agua d
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = d.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'detalle_suelo' => $contar("DELETE d FROM laboratorio.Detalle_Suelo d
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = d.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'bitacora' => $contar("DELETE mb FROM laboratorio.Muestra_Bitacora mb
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = mb.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'muestra_producto' => $contar("DELETE mp FROM laboratorio.Muestra_Producto mp
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = mp.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                // Residuos vinculados a las muestras de calidad (Id_Muestra en Detalle_Residuos_Log).
                // Solo los de la importación (con vínculo); los registros propios del usuario (NULL) NO se tocan.
                'residuos' => $contar("DELETE drl FROM laboratorio.Detalle_Residuos_Log drl
                    INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = drl.Id_Muestra
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'muestras' => $contar("DELETE ml FROM laboratorio.Muestra_Lab ml
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto
                    WHERE $filtro"),
                'asignaciones' => $contar("DELETE mpa FROM laboratorio.Monitoreo_Pozo_Asignacion mpa
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = mpa.Id_Proyecto
                    WHERE $filtro"),
                'detalles_proyecto' => $contar("DELETE pd FROM laboratorio.Proyecto_Detalle_Analisis pd
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = pd.Id_Proyecto
                    WHERE $filtro"),
                // Desvincular FK auto-referencial (Id_Proyecto_Pozos_Origen) antes de borrar los proyectos
                'desvinculados' => $contar("UPDATE pm SET pm.Id_Proyecto_Pozos_Origen = NULL
                    FROM laboratorio.Proyecto_Monitoreo pm
                    INNER JOIN laboratorio.Proyecto_Monitoreo p2 ON pm.Id_Proyecto_Pozos_Origen = p2.Id_Proyecto
                    WHERE p2.Es_Control_Calidad = 1 OR p2.Es_Drene = 1"),
                'proyectos' => $contar("DELETE FROM laboratorio.Proyecto_Monitoreo WHERE $filtroProy"),
            ];
            sqlsrv_commit($conn);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'limpiado' => $stats], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Acción no reconocida
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida: ' . $action], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
