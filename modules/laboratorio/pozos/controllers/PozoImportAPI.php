<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');

ob_start();

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
    require_once '../../../../core/Auth.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    Auth::check();

    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuario no autenticado.');
    }
    $usuario_id = intval($_SESSION['usuario_id']);
    $conn = Conexion::conectar();
    if (!$conn) throw new Exception("No se pudo conectar a SQL Server.");

    // ── Control de permisos (roles de laboratorio) ─────────────────────
    require_once '../../models/LaboratorioModel.php';
    $labAuthImp = new LaboratorioModel($conn);
    $urlSubImp  = '?module=laboratorio&action=pozos';
    $permImpMap = [
        'importar_historicos_init'  => 'eliminar',   // destructivo (DELETE + reseed)
        'importar_historicos_batch' => 'editar',
    ];
    if (isset($permImpMap[$action])) {
        $labAuthImp->denegarSiSinPermiso($usuario_id, $urlSubImp, $permImpMap[$action]);
    }

    // ==================== INIT: Limpiar solo datos de pozos y obtener lotes ====================
    if ($action === 'importar_historicos_init') {
        set_time_limit(300);
        
        // 1. Borrar datos relacionados a pozos (Es_Pozo = 1 / Es_Pozos = 1)
        $resetSqls = [
            "DELETE FROM laboratorio.Consumo_Resultado WHERE Id_Resultado IN (SELECT Id_Resultado FROM laboratorio.Resultado_Analisis WHERE Id_Solicitud_Analisis IN (SELECT Id_Solicitud_Analisis FROM laboratorio.Solicitud_Analisis WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1)))",
            "DELETE FROM laboratorio.Resultado_Analisis WHERE Id_Solicitud_Analisis IN (SELECT Id_Solicitud_Analisis FROM laboratorio.Solicitud_Analisis WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1))",
            "DELETE FROM laboratorio.Solicitud_Analisis WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1)",
            "DELETE FROM laboratorio.Detalle_Agua WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1)",
            "DELETE FROM laboratorio.Muestra_Producto WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1)",
            "DELETE FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1",
            "DELETE FROM laboratorio.Monitoreo_Pozo_Asignacion",
            "DELETE FROM laboratorio.Proyecto_Detalle_Analisis WHERE Id_Proyecto IN (SELECT Id_Proyecto FROM laboratorio.Proyecto_Monitoreo WHERE Es_Pozos = 1)",
            "DELETE FROM laboratorio.Proyecto_Monitoreo WHERE Es_Pozos = 1"
        ];
        foreach ($resetSqls as $sqlReset) {
            sqlsrv_query($conn, $sqlReset);
        }

        // 2. REINICIAR IDENTITY de las tablas afectadas (reseteo inteligente al máximo real)
        $resetIdentity = [
            "DBCC CHECKIDENT ('laboratorio.Muestra_Lab', RESEED)",
            "DBCC CHECKIDENT ('laboratorio.Muestra_Producto', RESEED)",
            "DBCC CHECKIDENT ('laboratorio.Solicitud_Analisis', RESEED)",
            "DBCC CHECKIDENT ('laboratorio.Resultado_Analisis', RESEED)",
            "DBCC CHECKIDENT ('laboratorio.Monitoreo_Pozo_Asignacion', RESEED)",
            "DBCC CHECKIDENT ('laboratorio.Proyecto_Detalle_Analisis', RESEED)",
            "DBCC CHECKIDENT ('laboratorio.Proyecto_Monitoreo', RESEED)",
            "DBCC CHECKIDENT ('laboratorio.Consumo_Resultado', RESEED)"
        ];
        foreach ($resetIdentity as $sqlId) {
            sqlsrv_query($conn, $sqlId);
        }

        // 3. Obtener TODOS los registros desde PostgreSQL (incluso si id_medicion es NULL)
        // Usamos LEFT JOIN inteligente para asociarlos a un proyecto (monitoreo), o le creamos uno por defecto.
        $pdoPg = ConexionPostgreSQL::conectar();
        if (!$pdoPg) throw new Exception("No se pudo conectar a PostgreSQL.");

        $sqlMuestras = "
            SELECT 
                cal.id_laboratorio,
                cal.id_pozo,
                TO_CHAR(cal.fecha_toma_muestra, 'YYYY-MM-DD') AS fechamonitoreo,
                cal.orden,
                COALESCE(pm.monitoreo, pm2.monitoreo, 'HISTORICO ' || COALESCE(TO_CHAR(cal.fecha_toma_muestra, 'YYYY-MM'), 'PLANIFICADO')) AS monitoreo,
                COALESCE(pc.valle, 'CHICAMA') AS valle
            FROM " . PG_SCHEMA . ".calidad_agua_laboratorio cal
            LEFT JOIN " . PG_SCHEMA . ".pozos_monitoreo pm ON cal.id_medicion = pm.id_medicion AND pm.id_medicion IS NOT NULL
            LEFT JOIN " . PG_SCHEMA . ".pozos_monitoreo pm2 ON cal.id_pozo = pm2.id_pozo AND cal.fecha_toma_muestra = pm2.fechamonitoreo AND cal.id_medicion IS NULL
            LEFT JOIN " . PG_SCHEMA . ".pozos_catastro pc ON cal.id_pozo = pc.id_pozo
            ORDER BY monitoreo, cal.orden, cal.id_pozo
        ";
        $stmtM = $pdoPg->query($sqlMuestras);
        $todos = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        // 4. Preparar lotes — cada fila de PG es un lote unico
        $lotes = [];
        
        foreach ($todos as $row) {
            $monitoreo = trim((string)($row['monitoreo'] ?? ''));
            $orden = intval($row['orden'] ?? 0);
            $id_lab = intval($row['id_laboratorio']);
            
            $lotes[] = [
                'id_medicion'     => $id_lab,
                'monitoreo'       => $monitoreo,
                'valle'           => trim($row['valle'] ?? 'CHICAMA'),
                'fechamonitoreo'  => $row['fechamonitoreo'],
                'id_pozo'         => strtoupper(trim((string)($row['id_pozo'] ?? ''))),
                'orden'           => $orden,
                'numero_muestra'  => $id_lab  // Usamos id_laboratorio como Numero_Muestra (siempre unico)
            ];
        }

        $total_lotes = count($lotes);
        
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'lotes' => $lotes,
            'total_lotes' => $total_lotes
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== BATCH: Procesar un lote (una medicion = pozo, muestra) ====================
    if ($action === 'importar_historicos_batch') {
        $id_medicion    = intval($_POST['id_medicion'] ?? 0);
        $monitoreo_raw  = trim($_POST['monitoreo'] ?? '');
        $valle_raw      = strtoupper(trim((string)($_POST['valle'] ?? 'CHICAMA')));
        $id_pozo        = strtoupper(trim((string)($_POST['id_pozo'] ?? '')));
        $orden          = intval($_POST['orden'] ?? 0);
        $numero_muestra = intval($_POST['numero_muestra'] ?? 1);
        
        $fecha_toma_raw = trim((string)($_POST['fechamonitoreo'] ?? ''));
        $es_futuro      = empty($fecha_toma_raw);
        $fecha_toma     = $es_futuro ? null : $fecha_toma_raw;

        if (!$id_medicion || !$id_pozo) {
            throw new Exception("Datos incompletos: medicion=$id_medicion, pozo=$id_pozo");
        }

        // 1. Cliente: usar el primero que exista
        $stmtCli = sqlsrv_query($conn, "SELECT TOP 1 Id_Cliente FROM laboratorio.Cliente WHERE Activo = 1 ORDER BY Id_Cliente");
        $rowCli = ($stmtCli !== false) ? sqlsrv_fetch_array($stmtCli, SQLSRV_FETCH_ASSOC) : null;
        $id_cliente = $rowCli ? intval($rowCli['Id_Cliente']) : 1;

        // 2. Paquete de venta (obtener ANTES de mapear parámetros)
        $stmtPaq = sqlsrv_query($conn, "SELECT TOP 1 Id_Producto FROM laboratorio.Producto_Venta WHERE Activo = 1 ORDER BY Id_Producto");
        $rowPaq = ($stmtPaq !== false) ? sqlsrv_fetch_array($stmtPaq, SQLSRV_FETCH_ASSOC) : null;
        $id_paquete = $rowPaq ? intval($rowPaq['Id_Producto']) : null;
        if (!$id_paquete) {
            throw new Exception("No hay paquetes de laboratorio configurados.");
        }

        // 3. Mapeo de parametros PG -> SQL Server (SOLO los del paquete seleccionado)
        // Incluir parámetros de AMBAS tablas: calidad_agua_laboratorio (lab) y pozos_monitoreo (in-situ)
        $stmtMapeo = sqlsrv_query($conn, "SELECT pa.Id_Parametro, pa.Posgre_Nombre, pa.Id_Servicio, pa.Posgre_Tabla
                                          FROM laboratorio.Parametro_Analisis pa
                                          INNER JOIN laboratorio.Producto_Servicio ps ON ps.Id_Servicio = pa.Id_Servicio
                                          WHERE (pa.Posgre_Tabla = 'calidad_agua_laboratorio' OR pa.Posgre_Tabla = 'pozos_monitoreo')
                                          AND pa.Posgre_Nombre IS NOT NULL 
                                          AND pa.Activo = 1 
                                          AND ps.Id_Producto = ?
                                          AND ps.Activo = 1", [$id_paquete]);
        if ($stmtMapeo === false) throw new Exception("Error al obtener mapeo de parametros: " . print_r(sqlsrv_errors(), true));
        $mapaParametros = [];
        while ($rowM = sqlsrv_fetch_array($stmtMapeo, SQLSRV_FETCH_ASSOC)) {
            $mapaParametros[$rowM['Posgre_Nombre']] = [
                'Id_Parametro' => intval($rowM['Id_Parametro']),
                'Id_Servicio' => intval($rowM['Id_Servicio'])
            ];
        }
        if (empty($mapaParametros)) {
            throw new Exception("No hay parametros mapeados a calidad_agua_laboratorio para el paquete seleccionado.");
        }

        // 4. Proyecto (buscar existente o crear nuevo) agrupado por VALLE y SEMESTRE
        if ($es_futuro) {
            $temporada = date('Y') . '-02';
        } else {
            $mes = intval(date('n', strtotime($fecha_toma)));
            $anio = date('Y', strtotime($fecha_toma));
            $temporada = ($mes <= 6) ? ($anio . '-01') : ($anio . '-02');
        }
        $nombre_proyecto = "MONITOREO POZOS $valle_raw - $temporada";

        $stmtCheckProy = sqlsrv_query($conn, "SELECT Id_Proyecto FROM laboratorio.Proyecto_Monitoreo WHERE Nombre_Proyecto = ? AND Activo = 1", [$nombre_proyecto]);
        $rowCheckProy = ($stmtCheckProy !== false) ? sqlsrv_fetch_array($stmtCheckProy, SQLSRV_FETCH_ASSOC) : null;
        
        if ($rowCheckProy) {
            $id_proyecto = intval($rowCheckProy['Id_Proyecto']);
        } else {
            // Usa fecha actual para Inicio de proyecto si la muestra no tiene fecha
            $fecha_inicio_proy = $fecha_toma ?? date('Y-m-d');
            
            $sqlProy = "INSERT INTO laboratorio.Proyecto_Monitoreo 
                (Nombre_Proyecto, Valle, Temporada, Fecha_Inicio, Tipo_Muestra, Uso_Agua, Fuente_Agua, 
                 Es_Control_Calidad, Es_Drene, Es_Pozos, Id_Responsable, Estado, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, ?, 'Agua', 'Otros', N'Subterráneo', 0, 0, 1, ?, 'Terminado', ?, 1, GETDATE());
                SELECT SCOPE_IDENTITY() AS id;";
            $stmtProy = sqlsrv_query($conn, $sqlProy, [$nombre_proyecto, $valle_raw, $temporada, $fecha_inicio_proy, $usuario_id, $usuario_id]);
            if ($stmtProy === false) throw new Exception("Error al crear proyecto '$nombre_proyecto': " . print_r(sqlsrv_errors(), true));
            sqlsrv_next_result($stmtProy);
            $rowProy = sqlsrv_fetch_array($stmtProy, SQLSRV_FETCH_ASSOC);
            $id_proyecto = intval($rowProy['id'] ?? 0);
            if ($id_proyecto <= 0) throw new Exception("No se obtuvo ID para proyecto '$nombre_proyecto'");

            // Proyecto Detalle Analisis
            if ($id_paquete) {
                sqlsrv_query($conn, "INSERT INTO laboratorio.Proyecto_Detalle_Analisis (Id_Proyecto, Id_Producto_Venta, Cantidad_Planificada, Activo, Fecha_Creacion, Usuario_Creacion) VALUES (?, ?, 999, 1, GETDATE(), ?)",
                    [$id_proyecto, $id_paquete, $usuario_id]);
            }
        }

        // 5. Asignacion Pozo — una por cada muestra individual (Id_Proyecto + Id_Pozo + Numero_Muestra)
        $sqlAsigCheck = "SELECT Id_Asignacion FROM laboratorio.Monitoreo_Pozo_Asignacion WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Orden = ?";
        $stmtAsigCheck = sqlsrv_query($conn, $sqlAsigCheck, [$id_proyecto, $id_pozo, $orden]);
        $rowAsigCheck = ($stmtAsigCheck !== false) ? sqlsrv_fetch_array($stmtAsigCheck, SQLSRV_FETCH_ASSOC) : null;
        
        if (!$rowAsigCheck) {
            $sqlAsig = "INSERT INTO laboratorio.Monitoreo_Pozo_Asignacion 
                        (Id_Proyecto, Numero_Muestra, Id_Pozo, Orden, Es_Analisis_Laboratorio, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, 1, 1, GETDATE());
                        SELECT SCOPE_IDENTITY() AS Id_Asignacion;";
            $stmtAsig = sqlsrv_query($conn, $sqlAsig, [$id_proyecto, $numero_muestra, $id_pozo, $orden]);
            if ($stmtAsig === false) {
                throw new Exception("Error al crear asignacion pozo $id_pozo: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_next_result($stmtAsig);
            $rowAsig = sqlsrv_fetch_array($stmtAsig, SQLSRV_FETCH_ASSOC);
            $id_asignacion = intval($rowAsig['Id_Asignacion'] ?? 0);
        } else {
            $id_asignacion = intval($rowAsigCheck['Id_Asignacion'] ?? 0);
        }

        // 6. Datos de PG para esta fila unica (AMBAS tablas)
        $pdoPg = ConexionPostgreSQL::conectar();
        if (!$pdoPg) throw new Exception("No se pudo conectar a PostgreSQL.");
        
        // Leer datos de calidad_agua_laboratorio (parámetros de lab)
        $stmtDatos = $pdoPg->prepare("SELECT * FROM " . PG_SCHEMA . ".calidad_agua_laboratorio WHERE id_laboratorio = ?");
        $stmtDatos->execute([$id_medicion]);
        $filasLab = $stmtDatos->fetchAll(PDO::FETCH_ASSOC);
        
        // Leer datos de pozos_monitoreo (parámetros in-situ)
        $stmtDatosInsitu = $pdoPg->prepare("SELECT * FROM " . PG_SCHEMA . ".pozos_monitoreo WHERE id_medicion = ?");
        $stmtDatosInsitu->execute([$id_medicion]);
        $filasInsitu = $stmtDatosInsitu->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar datos de ambas tablas
        $filas = [];
        if (!empty($filasLab)) {
            $filas = array_merge($filas, $filasLab);
        }
        if (!empty($filasInsitu)) {
            $filas = array_merge($filas, $filasInsitu);
        }

        if (empty($filas)) {
            ob_end_clean();
            echo json_encode(['success' => true, 'resultados' => 0], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 7. Verificar si ya existe muestra para este pozo+medicion
        $stmtCheckM = sqlsrv_query($conn, "SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Id_Medicion_PG = ? AND Activo = 1", [$id_proyecto, $id_pozo, $id_medicion]);
        $rowCheckM = ($stmtCheckM !== false) ? sqlsrv_fetch_array($stmtCheckM, SQLSRV_FETCH_ASSOC) : null;
        if ($rowCheckM) {
            ob_end_clean();
            echo json_encode(['success' => true, 'resultados' => 0], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 8. Coordenadas
        $stmtCoord = sqlsrv_query($conn, "SELECT coord_este, coord_norte FROM laboratorio.Catastro_Pozo WHERE Id_Pozo = ?", [$id_pozo]);
        $rowCoord = ($stmtCoord !== false) ? sqlsrv_fetch_array($stmtCoord, SQLSRV_FETCH_ASSOC) : null;
        $coord_este = $rowCoord['coord_este'] ?? '';
        $coord_norte = $rowCoord['coord_norte'] ?? '';

        // 9. Crear muestra + solicitudes + resultados en transaccion
        sqlsrv_begin_transaction($conn);
        try {
            $obs = 'Importacion PG. Medicion: ' . $id_medicion;
            if ($es_futuro) {
                $obs .= ' - Planificado para Recepcion.';
            }
            $estado_muestra = $es_futuro ? 'Por Recepcionar' : 'Finalizado';

            $sqlMuestra = "INSERT INTO laboratorio.Muestra_Lab
                (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Id_Pozo, Id_Asignacion, Valle, Eje_X, Eje_Y, 
                 Fecha_Recepcion, Fecha_Toma, Estado, Tipo_Servicio, Observacion_Muestra, 
                 Es_Control_Calidad, Es_Drene, Es_Pozo, Fecha_Analisis, Lab_Habilitado, Id_Medicion_PG, 
                 Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Analisis Historico', ?, 0, 0, 1, ?, 1, ?, ?, 1, GETDATE());
                SELECT SCOPE_IDENTITY() AS id;";
            $stmtM = sqlsrv_query($conn, $sqlMuestra, [
                $id_cliente, $usuario_id, $usuario_id, $id_proyecto, $id_pozo, $id_asignacion, $valle_raw,
                $coord_este, $coord_norte, $fecha_toma, $fecha_toma, $estado_muestra,
                $obs, $fecha_toma, $id_medicion, $usuario_id
            ]);
            if ($stmtM === false) throw new Exception("Error INSERT Muestra_Lab: " . print_r(sqlsrv_errors(), true));
            sqlsrv_next_result($stmtM);
            $rowMId = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC);
            $id_muestra = intval($rowMId['id'] ?? 0);
            if ($id_muestra <= 0) throw new Exception("No se obtuvo ID de muestra para pozo $id_pozo");

            // Detalle Agua
            sqlsrv_query($conn, "INSERT INTO laboratorio.Detalle_Agua (Id_Muestra, Uso_Agua, Fuente_Agua, Cantidad_Muestra, Nivel_Agua, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, 'Consumo Humano / Riego', N'Subterráneo', '1 Litro', ?, ?, 1, GETDATE())",
                [$id_muestra, 'Pozo ' . $id_pozo, $usuario_id]);

            // Muestra Producto — solo para historicas (las futuras elegiran producto al recepcionar)
            if ($id_paquete && !$es_futuro) {
                sqlsrv_query($conn, "INSERT INTO laboratorio.Muestra_Producto (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, ?, 1, GETDATE())",
                    [$id_muestra, $id_paquete, $id_cliente, $usuario_id]);
            }

            // Solicitudes y Resultados — solo para historicas con datos
            $resultados_count = 0;
            if (!$es_futuro) {
                $servicios_procesados = [];
            foreach ($filas as $fila) {
                foreach ($mapaParametros as $col_pg => $info) {
                    $valor = $fila[$col_pg] ?? null;
                    if ($valor === null || $valor === '') continue;

                    $id_param = $info['Id_Parametro'];
                    $id_serv = $info['Id_Servicio'];

                    if (!isset($servicios_procesados[$id_serv])) {
                        $sqlSol = "INSERT INTO laboratorio.Solicitud_Analisis (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, 'Finalizado', ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                        $stmtSol = sqlsrv_query($conn, $sqlSol, [$id_muestra, $id_serv, $fecha_toma, $usuario_id]);
                        if ($stmtSol === false) continue;
                        sqlsrv_next_result($stmtSol);
                        $rowSol = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                        $servicios_procesados[$id_serv] = intval($rowSol['id'] ?? 0);
                    }

                    $id_sol = $servicios_procesados[$id_serv] ?? 0;
                    if ($id_sol <= 0) continue;

                    $stmtRes = sqlsrv_query($conn, "INSERT INTO laboratorio.Resultado_Analisis (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, ?, 1, GETDATE())",
                        [$id_sol, $id_param, floatval($valor), $usuario_id]);
                    if ($stmtRes !== false) $resultados_count++;
                }
            } // end foreach $filas
            } // end if !$es_futuro

            sqlsrv_commit($conn);
            ob_end_clean();
            echo json_encode(['success' => true, 'resultados' => $resultados_count], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }
        exit;
    }

    // Accion no reconocida
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Accion no reconocida: ' . $action], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    ob_end_clean();
    file_put_contents(__DIR__ . '/import_errors.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
