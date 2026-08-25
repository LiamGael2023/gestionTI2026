<?php
/**
 * ImportarPeriodoAPI.php
 * Importa las muestras de UN período seleccionado (ej. 2026-02) desde PostgreSQL,
 * creando el proyecto de monitoreo (o usándolo si existe), uniendo el paquete de
 * laboratorio y generando solicitudes + resultados vacíos listos para llenar.
 *
 * URL (init): modules/laboratorio/muestra/controllers/ImportarPeriodoAPI.php?action=importar_periodo_init&anio=2026&periodo=2
 * URL (batch): modules/laboratorio/muestra/controllers/ImportarPeriodoAPI.php  (POST, action=importar_periodo_batch)
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
    require_once '../models/ProyectoModel.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuario no autenticado.');
    }
    $usuario_id = intval($_SESSION['usuario_id']);
    $conn = Conexion::conectar();
    if (!$conn) throw new Exception('No se pudo conectar a SQL Server.');

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // ==================== INIT: listar lotes (muestras) del período seleccionado ====================
    if ($action === 'importar_periodo_init') {
        set_time_limit(300);

        $anio    = intval($_GET['anio'] ?? $_POST['anio'] ?? 0);
        $periodo = intval($_GET['periodo'] ?? $_POST['periodo'] ?? 0);
        if ($anio < 2000 || ($periodo !== 1 && $periodo !== 2)) {
            throw new Exception('Período inválido. Seleccione año y período (1 o 2).');
        }

        $fecha_desde = $periodo === 1 ? "$anio-01-01" : "$anio-07-01";
        $fecha_hasta = $periodo === 1 ? "$anio-06-30" : "$anio-12-31";

        $pdoPg = ConexionPostgreSQL::conectar();
        if (!$pdoPg) throw new Exception('No se pudo conectar a PostgreSQL.');

        // Muestras del período desde calidad_agua_laboratorio (fuente de laboratorio —
        // incluye muestras que no tienen fila en pozos_monitoreo, ej. CHAVI-00516/12480)
        $sql = "SELECT cal.id_laboratorio,
                       cal.id_pozo,
                       TO_CHAR(cal.fecha_toma_muestra, 'YYYY-MM-DD') AS fechamonitoreo,
                       COALESCE(UPPER(TRIM(pc.valle::text)), 'VIRU') AS valle
                FROM " . PG_SCHEMA . ".calidad_agua_laboratorio cal
                LEFT JOIN " . PG_SCHEMA . ".pozos_catastro pc ON cal.id_pozo = pc.id_pozo
                WHERE cal.fecha_toma_muestra BETWEEN :fd AND :fh
                  AND cal.id_pozo IS NOT NULL AND TRIM(cal.id_pozo) <> ''
                ORDER BY cal.fecha_toma_muestra, cal.id_pozo";
        $stmt = $pdoPg->prepare($sql);
        $stmt->execute([':fd' => $fecha_desde, ':fh' => $fecha_hasta]);

        $lotes = [];
        $proyectosInfo = [];
        $contador = 1;
        foreach ($stmt as $row) {
            $valle  = trim((string)($row['valle'] ?? 'VIRU'));
            $fecha  = trim((string)($row['fechamonitoreo'] ?? ''));
            $idPozo = strtoupper(trim((string)($row['id_pozo'] ?? '')));
            if ($idPozo === '') continue;

            $mes      = $fecha !== '' ? intval(date('n', strtotime($fecha))) : 0;
            $anioTmp  = $fecha !== '' ? intval(date('Y', strtotime($fecha))) : $anio;
            $temporada = ($mes > 0 && $mes <= 6) ? "$anioTmp-01" : "$anioTmp-02";
            $nombreProyecto = "MONITOREO POZOS $valle - $temporada";

            if (!isset($proyectosInfo[$nombreProyecto])) {
                $proyectosInfo[$nombreProyecto] = ['nombre' => $nombreProyecto, 'valle' => $valle, 'temporada' => $temporada, 'muestras' => 0];
            }
            $proyectosInfo[$nombreProyecto]['muestras']++;

            $idMed = $row['id_laboratorio'];
            $lotes[] = [
                'id_medicion'     => ($idMed !== null) ? intval($idMed) : 0,
                'monitoreo'       => $nombreProyecto,
                'valle'           => $valle,
                'fechamonitoreo'  => $fecha,
                'id_pozo'         => $idPozo,
                'orden'           => 1,
                'numero_muestra'  => ($idMed !== null) ? intval($idMed) : (100000 + $contador),
            ];
            $contador++;
        }

        ob_end_clean();
        echo json_encode([
            'success'      => true,
            'lotes'        => $lotes,
            'total_lotes'  => count($lotes),
            'proyectos'    => array_values($proyectosInfo),
            'periodo'      => "$anio-0$periodo"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== BATCH: procesar un lote (un pozo/muestra del período) ====================
    if ($action === 'importar_periodo_batch') {
        set_time_limit(120);

        $id_medicion    = intval($_POST['id_medicion'] ?? 0);
        $monitoreo      = trim((string)($_POST['monitoreo'] ?? ''));
        $valle_raw      = strtoupper(trim((string)($_POST['valle'] ?? 'VIRU')));
        $fecha_toma_raw = trim((string)($_POST['fechamonitoreo'] ?? ''));
        $id_pozo        = strtoupper(trim((string)($_POST['id_pozo'] ?? '')));
        $orden          = intval($_POST['orden'] ?? 1);
        $numero_muestra = intval($_POST['numero_muestra'] ?? 1);
        $total_lotes    = intval($_POST['total_lotes'] ?? 0);

        if ($id_pozo === '') {
            throw new Exception('Pozo vacío en lote.');
        }
        $fecha_toma = ($fecha_toma_raw !== '') ? $fecha_toma_raw : null;

        // 1. Cliente (primero activo)
        $stmtCli = sqlsrv_query($conn, "SELECT TOP 1 Id_Cliente FROM laboratorio.Cliente WHERE Activo = 1 ORDER BY Id_Cliente");
        $rowCli  = ($stmtCli !== false) ? sqlsrv_fetch_array($stmtCli, SQLSRV_FETCH_ASSOC) : null;
        $id_cliente = $rowCli ? intval($rowCli['Id_Cliente']) : 1;

        // 2. Paquete de laboratorio (primer producto activo)
        $stmtPaq = sqlsrv_query($conn, "SELECT TOP 1 Id_Producto FROM laboratorio.Producto_Venta WHERE Activo = 1 ORDER BY Id_Producto");
        $rowPaq  = ($stmtPaq !== false) ? sqlsrv_fetch_array($stmtPaq, SQLSRV_FETCH_ASSOC) : null;
        $id_paquete = $rowPaq ? intval($rowPaq['Id_Producto']) : 0;

        // 3. Servicios del paquete + parámetros por servicio (para crear resultados vacíos)
        $serviciosPorProducto = [];
        $parametrosPorServicio = [];
        if ($id_paquete > 0) {
            $stmtServs = sqlsrv_query($conn, "SELECT Id_Servicio FROM laboratorio.Producto_Servicio WHERE Id_Producto = ? AND Activo = 1", [$id_paquete]);
            while ($rowS = sqlsrv_fetch_array($stmtServs, SQLSRV_FETCH_ASSOC)) {
                $serviciosPorProducto[] = intval($rowS['Id_Servicio']);
            }
            foreach ($serviciosPorProducto as $id_srv) {
                $stmtP = sqlsrv_query($conn, "SELECT Id_Parametro FROM laboratorio.Parametro_Analisis WHERE Id_Servicio = ? AND Activo = 1", [$id_srv]);
                $parametrosPorServicio[$id_srv] = [];
                while ($rowPar = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) {
                    $parametrosPorServicio[$id_srv][] = intval($rowPar['Id_Parametro']);
                }
            }
        }

        // 4. Temporada + nombre de proyecto (patrón MONITOREO POZOS {VALLE} - {AÑO}-0{PERIODO})
        $mes      = $fecha_toma ? intval(date('n', strtotime($fecha_toma))) : 0;
        $anioTmp  = $fecha_toma ? intval(date('Y', strtotime($fecha_toma))) : intval(date('Y'));
        $temporada = ($mes > 0 && $mes <= 6) ? "$anioTmp-01" : "$anioTmp-02";
        $nombre_proyecto = ($monitoreo !== '') ? $monitoreo : "MONITOREO POZOS $valle_raw - $temporada";

        // 5. Buscar o crear proyecto (Es_Pozos=1) — prefiere el proyecto con más muestras (evita unir a proyectos "fantasma" duplicados)
        $stmtCheckProy = sqlsrv_query($conn, "SELECT TOP 1 pm.Id_Proyecto,
                                                    (SELECT COUNT(*) FROM laboratorio.Muestra_Lab ml WHERE ml.Id_Proyecto = pm.Id_Proyecto AND ml.Activo = 1) AS n_muestras
                                             FROM laboratorio.Proyecto_Monitoreo pm
                                             WHERE pm.Nombre_Proyecto = ? AND pm.Es_Pozos = 1 AND pm.Activo = 1
                                             ORDER BY n_muestras DESC, pm.Fecha_Creacion DESC", [$nombre_proyecto]);
        $rowCheckProy  = ($stmtCheckProy !== false) ? sqlsrv_fetch_array($stmtCheckProy, SQLSRV_FETCH_ASSOC) : null;

        if ($rowCheckProy) {
            $id_proyecto = intval($rowCheckProy['Id_Proyecto']);
        } else {
            $proyectoModel = new ProyectoModel($conn);
            $id_proyecto = $proyectoModel->guardar([
                'Nombre_Proyecto' => $nombre_proyecto,
                'Fecha_Inicio'    => $fecha_toma ?: date('Y-m-d'),
                'Valle'           => $valle_raw,
                'Temporada'       => $temporada,
                'Tipo_Muestra'    => 'Agua',
                'Uso_Agua'        => 'Otros',
                'Fuente_Agua'     => 'Subterráneo',
                'Es_Pozos'        => 1,
                'Id_Responsable'  => $usuario_id,
                'Estado'          => 'En Progreso'
            ]);
            if (!$id_proyecto) {
                throw new Exception("No se pudo crear el proyecto $nombre_proyecto");
            }
        }

        // Proyecto_Detalle_Analisis (unir el paquete al proyecto, ya sea nuevo o existente)
        if ($id_paquete > 0) {
            $cantidad_pd = ($total_lotes > 0) ? $total_lotes : 1;
            $stmtPDA = sqlsrv_query($conn, "SELECT 1 FROM laboratorio.Proyecto_Detalle_Analisis WHERE Id_Proyecto = ? AND Id_Producto_Venta = ?", [$id_proyecto, $id_paquete]);
            if ($stmtPDA !== false && !sqlsrv_has_rows($stmtPDA)) {
                sqlsrv_query($conn, "INSERT INTO laboratorio.Proyecto_Detalle_Analisis (Id_Proyecto, Id_Producto_Venta, Cantidad_Planificada, Activo, Fecha_Creacion, Usuario_Creacion) VALUES (?, ?, ?, 1, GETDATE(), ?)",
                    [$id_proyecto, $id_paquete, $cantidad_pd, $usuario_id]);
            }
        }

        // 6. Procesar lote en transacción (idempotente)
        sqlsrv_begin_transaction($conn);
        try {
            $solicitudesCreadas = 0;
            $resultadosCreados  = 0;

            // Coordenadas desde Catastro_Pozo
            $coordEste  = '';
            $coordNorte = '';
            $stmtCat = sqlsrv_query($conn, "SELECT coord_este, coord_norte FROM laboratorio.Catastro_Pozo WHERE Id_Pozo = ?", [$id_pozo]);
            $rowCat  = ($stmtCat !== false) ? sqlsrv_fetch_array($stmtCat, SQLSRV_FETCH_ASSOC) : null;
            if ($rowCat) {
                $coordEste  = $rowCat['coord_este']  ?? '';
                $coordNorte = $rowCat['coord_norte'] ?? '';
            }

            // Asignación de pozo (IF NOT EXISTS por proyecto+pozo) y captura de Id_Asignacion
            // ⚠️ Monitoreo_Pozo_Asignacion NO tiene columna Usuario_Creacion
            $id_asignacion = 0;
            $stmtChkA = sqlsrv_query($conn, "SELECT Id_Asignacion FROM laboratorio.Monitoreo_Pozo_Asignacion WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Activo = 1", [$id_proyecto, $id_pozo]);
            $rowChkA  = ($stmtChkA !== false) ? sqlsrv_fetch_array($stmtChkA, SQLSRV_FETCH_ASSOC) : null;
            if ($rowChkA) {
                $id_asignacion = intval($rowChkA['Id_Asignacion'] ?? 0);
            } else {
                $stmtInsA = sqlsrv_query($conn, "INSERT INTO laboratorio.Monitoreo_Pozo_Asignacion (Id_Proyecto, Numero_Muestra, Id_Pozo, Orden, Es_Analisis_Laboratorio, Activo, Fecha_Creacion) VALUES (?, ?, ?, ?, 1, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS Id_Asignacion;",
                    [$id_proyecto, $numero_muestra, $id_pozo, $orden]);
                if ($stmtInsA !== false) {
                    sqlsrv_next_result($stmtInsA);
                    $rowInsA = sqlsrv_fetch_array($stmtInsA, SQLSRV_FETCH_ASSOC);
                    $id_asignacion = intval($rowInsA['Id_Asignacion'] ?? 0);
                }
            }

            // Muestra (IF NOT EXISTS por proyecto+pozo activo)
            $stmtChkM = sqlsrv_query($conn, "SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Activo = 1", [$id_proyecto, $id_pozo]);
            $rowChkM  = ($stmtChkM !== false) ? sqlsrv_fetch_array($stmtChkM, SQLSRV_FETCH_ASSOC) : null;

            if ($rowChkM) {
                $id_muestra = intval($rowChkM['Id_Muestra']);
                if ($id_medicion > 0) {
                    sqlsrv_query($conn, "UPDATE laboratorio.Muestra_Lab SET Id_Medicion_PG = ISNULL(Id_Medicion_PG, ?) WHERE Id_Muestra = ?", [$id_medicion, $id_muestra]);
                }
            } else {
                $obs = "Importación por período. Monitoreo: $nombre_proyecto / Pozo: $id_pozo. Medicion_PG: $id_medicion";
                $sqlM = "INSERT INTO laboratorio.Muestra_Lab
                    (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Id_Pozo, Id_Asignacion, Valle,
                     Eje_X, Eje_Y, Fecha_Recepcion, Fecha_Toma, Estado, Tipo_Servicio,
                     Observacion_Muestra, Es_Control_Calidad, Es_Drene, Es_Pozo,
                     Lab_Habilitado, Id_Medicion_PG, Fecha_Analisis, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En Analisis', 'In-Situ Pozos',
                            ?, 0, 0, 1, 0, ?, ?, ?, 1, GETDATE());
                    SELECT SCOPE_IDENTITY() AS id;";
                $stmtM = sqlsrv_query($conn, $sqlM, [
                    $id_cliente, $usuario_id, $usuario_id, $id_proyecto, $id_pozo,
                    ($id_asignacion > 0 ? $id_asignacion : null), $valle_raw,
                    $coordEste, $coordNorte, $fecha_toma, $fecha_toma, $obs,
                    ($id_medicion > 0 ? $id_medicion : null), $fecha_toma, $usuario_id
                ]);
                if ($stmtM === false) {
                    throw new Exception('Error INSERT Muestra_Lab: ' . print_r(sqlsrv_errors(), true));
                }
                sqlsrv_next_result($stmtM);
                $rowMId = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC);
                $id_muestra = intval($rowMId['id'] ?? 0);
                if ($id_muestra <= 0) {
                    throw new Exception('No se obtuvo Id_Muestra para pozo ' . $id_pozo);
                }
            }

            // Muestra_Producto (unir el paquete a la muestra)
            if ($id_paquete > 0) {
                $stmtChkMP = sqlsrv_query($conn, "SELECT 1 FROM laboratorio.Muestra_Producto WHERE Id_Muestra = ? AND Id_Producto_Venta = ? AND Activo = 1", [$id_muestra, $id_paquete]);
                if ($stmtChkMP !== false && !sqlsrv_has_rows($stmtChkMP)) {
                    sqlsrv_query($conn, "INSERT INTO laboratorio.Muestra_Producto (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, ?, 1, GETDATE())",
                        [$id_muestra, $id_paquete, $id_cliente, $usuario_id]);
                }
            }

            // Solicitudes + Resultados vacíos por cada servicio del paquete
            foreach ($serviciosPorProducto as $id_servicio) {
                $stmtChkS = sqlsrv_query($conn, "SELECT Id_Solicitud_Analisis FROM laboratorio.Solicitud_Analisis WHERE Id_Muestra = ? AND Id_Servicio = ? AND Activo = 1", [$id_muestra, $id_servicio]);
                $rowChkS  = ($stmtChkS !== false) ? sqlsrv_fetch_array($stmtChkS, SQLSRV_FETCH_ASSOC) : null;

                if ($rowChkS) {
                    $id_solicitud = intval($rowChkS['Id_Solicitud_Analisis']);
                } else {
                    $stmtSol = sqlsrv_query($conn, "INSERT INTO laboratorio.Solicitud_Analisis (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, 'En Analisis', ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;",
                        [$id_muestra, $id_servicio, ($fecha_toma ?: date('Y-m-d')), $usuario_id]);
                    if ($stmtSol === false) continue;
                    sqlsrv_next_result($stmtSol);
                    $rowSolId = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                    $id_solicitud = intval($rowSolId['id'] ?? 0);
                    if ($id_solicitud > 0) $solicitudesCreadas++;
                }

                if ($id_solicitud <= 0) continue;

                foreach (($parametrosPorServicio[$id_servicio] ?? []) as $id_parametro) {
                    $stmtChkR = sqlsrv_query($conn, "SELECT 1 FROM laboratorio.Resultado_Analisis WHERE Id_Solicitud_Analisis = ? AND Id_Parametro = ? AND Activo = 1", [$id_solicitud, $id_parametro]);
                    if ($stmtChkR !== false && !sqlsrv_has_rows($stmtChkR)) {
                        sqlsrv_query($conn, "INSERT INTO laboratorio.Resultado_Analisis (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, NULL, ?, 1, GETDATE())",
                            [$id_solicitud, $id_parametro, $usuario_id]);
                        $resultadosCreados++;
                    }
                }
            }

            sqlsrv_commit($conn);
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'id_muestra' => $id_muestra,
                'solicitudes' => $solicitudesCreadas,
                'resultados'  => $resultadosCreados
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }
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
