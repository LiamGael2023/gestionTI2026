<?php
require_once dirname(__FILE__) . '/../../muestra/models/ProyectoModel.php';
require_once dirname(__FILE__) . '/MonitoreoPozoAsignacionModel.php';

class SincronizacionMonitoreoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Asegura que el cliente CHAVIMOCHIC exista.
     */
    private function asegurarDatosDePruebaLaboratorio($usuario_id) {
        $sqlCliente = "IF NOT EXISTS (SELECT 1 FROM laboratorio.Cliente WHERE Razon_Social LIKE '%CHAVIMOCHIC%' AND Activo = 1)
                       BEGIN
                           INSERT INTO laboratorio.Cliente (Razon_Social, RUC, Activo, Usuario_Creacion, Fecha_Creacion)
                           VALUES ('CHAVIMOCHIC', '20146030971', 1, " . intval($usuario_id) . ", GETDATE());
                       END";
        sqlsrv_query($this->db, $sqlCliente);
    }

    /**
     * Sincroniza los monitoreos desde PostgreSQL y crea Proyectos + Muestras con celdas en blanco.
     *
     * LÓGICA IDÉNTICA A importar_historicos con las siguientes diferencias:
     *   - Itera sobre pozos_monitoreo (no calidad_agua_laboratorio, que puede estar vacía).
     *   - Fecha_Toma = pm.fechamonitoreo (fecha real del campo, por pozo individual).
     *   - Id_Medicion_PG se guarda en Muestra_Lab igual que en históricos.
     *   - Estado de Muestra_Lab = 'En Analisis' (en blanco, pendiente de llenado).
     *   - Valor_Hallado = NULL en todos los Resultado_Analisis.
     *
     * @param PDO  $pdoPg      Conexión PostgreSQL
     * @param int  $usuario_id ID del usuario en sesión
     * @param int  $anio       Año a filtrar (ej. 2026)
     * @param int  $periodo    Período: 1 = Avenida (Ene-Jun), 2 = Estiaje (Jul-Dic)
     */
    public function sincronizarMonitoreos($pdoPg, $usuario_id, $anio = null, $periodo = null) {
        $stats = [
            'proyectos_creados'      => 0,
            'proyectos_actualizados' => 0,
            'muestras_creadas'       => 0,
            'solicitudes_creadas'    => 0,
            'resultados_creados'     => 0,
            'pozos_nuevos_agregados' => 0,
            'errores'                => []
        ];

        try {
            $this->asegurarDatosDePruebaLaboratorio($usuario_id);

            // ── Rango de fechas ─────────────────────────────────────────────────
            if ($anio && $periodo) {
                $fecha_desde = $periodo == 1 ? "$anio-01-01" : "$anio-07-01";
                $fecha_hasta = $periodo == 1 ? "$anio-06-30" : "$anio-12-31";
            } else {
                $anio        = intval(date('Y'));
                $fecha_desde = ($anio - 1) . '-01-01';
                $fecha_hasta = $anio . '-12-31';
            }

            // ── Cliente CHAVIMOCHIC ─────────────────────────────────────────────
            $sqlCli  = "SELECT TOP 1 Id_Cliente FROM laboratorio.Cliente WHERE Razon_Social LIKE '%CHAVIMOCHIC%' AND Activo = 1";
            $stmtCli = sqlsrv_query($this->db, $sqlCli);
            $rowCli  = $stmtCli !== false ? sqlsrv_fetch_array($stmtCli, SQLSRV_FETCH_ASSOC) : null;
            $id_cliente = $rowCli ? intval($rowCli['Id_Cliente']) : 1;

            // ── Paquete de venta ────────────────────────────────────────────────
            $sqlPaq  = "SELECT TOP 1 Id_Producto FROM laboratorio.Producto_Venta WHERE Activo = 1 ORDER BY Id_Producto";
            $stmtPaq = sqlsrv_query($this->db, $sqlPaq);
            $rowPaq  = $stmtPaq !== false ? sqlsrv_fetch_array($stmtPaq, SQLSRV_FETCH_ASSOC) : null;
            $id_paquete = $rowPaq ? intval($rowPaq['Id_Producto']) : null;

            // ── Servicios del producto ──────────────────────────────────────────
            $serviciosPorProducto = [];
            if ($id_paquete) {
                $sqlServs  = "SELECT Id_Servicio FROM laboratorio.Producto_Servicio WHERE Id_Producto = ? AND Activo = 1";
                $stmtServs = sqlsrv_query($this->db, $sqlServs, [$id_paquete]);
                while ($rowS = sqlsrv_fetch_array($stmtServs, SQLSRV_FETCH_ASSOC)) {
                    $serviciosPorProducto[] = intval($rowS['Id_Servicio']);
                }
            }

            // ── Parámetros por servicio ─────────────────────────────────────────
            $parametrosPorServicio = [];
            foreach ($serviciosPorProducto as $id_srv) {
                $sqlP   = "SELECT Id_Parametro FROM laboratorio.Parametro_Analisis WHERE Id_Servicio = ? AND Activo = 1";
                $stmtP  = sqlsrv_query($this->db, $sqlP, [$id_srv]);
                $parametrosPorServicio[$id_srv] = [];
                while ($rowPar = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) {
                    $parametrosPorServicio[$id_srv][] = intval($rowPar['Id_Parametro']);
                }
            }

            // ── Grupos valle+temporada en PostgreSQL ─────────────────────────────
            // Fuente: calidad_agua_laboratorio (todas las muestras de laboratorio del período).
            // NO usar pozos_monitoreo: hay muestras de lab sin fila in-situ (ej. CHAVI-00516/12480).
            $sqlGrupos = "SELECT COALESCE(UPPER(TRIM(pc.valle::text)), 'VIRU') AS valle,
                                 CASE WHEN EXTRACT(MONTH FROM cal.fecha_toma_muestra) <= 6
                                      THEN EXTRACT(YEAR FROM cal.fecha_toma_muestra)::text || '-01'
                                      ELSE EXTRACT(YEAR FROM cal.fecha_toma_muestra)::text || '-02' END AS temporada,
                                 MIN(cal.fecha_toma_muestra) AS fecha_inicio
                          FROM " . PG_SCHEMA . ".calidad_agua_laboratorio cal
                          LEFT JOIN " . PG_SCHEMA . ".pozos_catastro pc ON cal.id_pozo = pc.id_pozo
                          WHERE cal.fecha_toma_muestra >= :fecha_desde AND cal.fecha_toma_muestra <= :fecha_hasta
                            AND cal.id_pozo IS NOT NULL AND TRIM(cal.id_pozo) <> ''
                          GROUP BY valle, temporada
                          ORDER BY fecha_inicio DESC";
            $stmtGrupos = $pdoPg->prepare($sqlGrupos);
            $stmtGrupos->bindValue(':fecha_desde', $fecha_desde);
            $stmtGrupos->bindValue(':fecha_hasta', $fecha_hasta);
            $stmtGrupos->execute();
            $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

            $proyectoModel = new ProyectoModel($this->db);

            foreach ($grupos as $grupo) {
                $valle         = strtoupper(trim($grupo['valle'] ?: 'VIRU'));
                $temporada     = trim((string)($grupo['temporada'] ?? ''));
                $fecha_inicio  = $grupo['fecha_inicio']
                    ? date('Y-m-d', strtotime($grupo['fecha_inicio']))
                    : date('Y-m-d');

                // Nombre oficial: MONITOREO POZOS {VALLE} - {TEMPORADA}
                $monitoreo_raw   = "MONITOREO POZOS $valle - $temporada";
                $nombre_proyecto = $monitoreo_raw;

                // ── Buscar o crear proyecto ────────────────────────────────────
                // Prefiere el proyecto con más muestras (evita unir a proyectos "fantasma" duplicados por nombre)
                $sqlCheck = "SELECT TOP 1 pm.Id_Proyecto,
                                    (SELECT COUNT(*) FROM laboratorio.Muestra_Lab ml WHERE ml.Id_Proyecto = pm.Id_Proyecto AND ml.Activo = 1) AS n_muestras
                             FROM laboratorio.Proyecto_Monitoreo pm
                             WHERE (pm.Nombre_Proyecto = ? OR pm.Nombre_Proyecto = ?) AND pm.Es_Pozos = 1 AND pm.Activo = 1
                             ORDER BY n_muestras DESC, pm.Fecha_Creacion DESC";
                $stmtChk  = sqlsrv_query($this->db, $sqlCheck, [$nombre_proyecto, $monitoreo_raw]);
                $rowChk   = sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC);

                if ($rowChk) {
                    $id_proyecto = intval($rowChk['Id_Proyecto']);
                    $stats['proyectos_actualizados']++;
                    // ⚠️ ANTI-DUPLICADO (2026-08): si el proyecto existente está 'Planificado'
                    // (creado manualmente), la extracción YA inicia el análisis → pasar a
                    // 'En Progreso' para que NO aparezca el botón "Iniciar Ejecución"
                    // (evita que generarMuestras cree una tanda duplicada de muestras).
                    sqlsrv_query($this->db,
                        "UPDATE laboratorio.Proyecto_Monitoreo SET Estado = 'En Progreso', Fecha_Modificacion = GETDATE()
                         WHERE Id_Proyecto = ? AND Estado = 'Planificado'",
                        [$id_proyecto]);
                } else {
                    $id_proyecto = $proyectoModel->guardar([
                        'Nombre_Proyecto' => $nombre_proyecto,
                        'Fecha_Inicio'    => $fecha_inicio,
                        'Valle'           => $valle,
                        'Temporada'       => $temporada,
                        'Tipo_Muestra'    => 'Agua',
                        'Uso_Agua'        => 'Otros',
                        'Fuente_Agua'     => 'Subterráneo',
                        'Es_Pozos'        => 1,
                        'Id_Responsable'  => $usuario_id,
                        'Estado'          => 'En Progreso'
                    ]);
                    if (!$id_proyecto) {
                        $stats['errores'][] = "No se pudo crear el proyecto $nombre_proyecto";
                        continue;
                    }
                    $stats['proyectos_creados']++;
                }

                // ── Leer TODAS las filas de laboratorio para este grupo ─────────
                $sqlFilas = "SELECT cal.id_pozo,
                                    cal.id_laboratorio AS id_medicion,
                                    cal.orden,
                                    cal.fecha_toma_muestra AS fechamonitoreo,
                                    COALESCE(pc.cooreste::text,'')  AS coord_este,
                                    COALESCE(pc.coornorte::text,'') AS coord_norte
                             FROM " . PG_SCHEMA . ".calidad_agua_laboratorio cal
                             LEFT JOIN " . PG_SCHEMA . ".pozos_catastro pc ON cal.id_pozo = pc.id_pozo
                             WHERE cal.fecha_toma_muestra >= :fd AND cal.fecha_toma_muestra <= :fh
                               AND COALESCE(UPPER(TRIM(pc.valle::text)), 'VIRU') = :valle
                               AND CASE WHEN EXTRACT(MONTH FROM cal.fecha_toma_muestra) <= 6
                                        THEN EXTRACT(YEAR FROM cal.fecha_toma_muestra)::text || '-01'
                                        ELSE EXTRACT(YEAR FROM cal.fecha_toma_muestra)::text || '-02' END = :temporada
                             ORDER BY cal.id_pozo, cal.fecha_toma_muestra";
                $stmtFilas = $pdoPg->prepare($sqlFilas);
                $stmtFilas->execute([
                    ':fd'        => $fecha_desde,
                    ':fh'        => $fecha_hasta,
                    ':valle'     => $valle,
                    ':temporada' => $temporada
                ]);
                $filas = $stmtFilas->fetchAll(PDO::FETCH_ASSOC);

                $num_filas = count($filas);

                // Proyecto_Detalle_Analisis (mismo patrón que importar_historicos)
                if ($id_paquete && $num_filas > 0) {
                    $stmtPDA = sqlsrv_query($this->db,
                        "SELECT 1 FROM laboratorio.Proyecto_Detalle_Analisis WHERE Id_Proyecto = ? AND Id_Producto_Venta = ?",
                        [$id_proyecto, $id_paquete]
                    );
                    if ($stmtPDA !== false && !sqlsrv_has_rows($stmtPDA)) {
                        sqlsrv_query($this->db,
                            "INSERT INTO laboratorio.Proyecto_Detalle_Analisis
                             (Id_Proyecto, Id_Producto_Venta, Cantidad_Planificada, Activo, Fecha_Creacion, Usuario_Creacion)
                             VALUES (?, ?, ?, 1, GETDATE(), ?)",
                            [$id_proyecto, $id_paquete, $num_filas, $usuario_id]
                        );
                    }
                }

                // Contador de Numero_Muestra (continúa desde el máximo existente)
                $stmtMN  = sqlsrv_query($this->db,
                    "SELECT ISNULL(MAX(Numero_Muestra), 0) AS max_num FROM laboratorio.Monitoreo_Pozo_Asignacion WHERE Id_Proyecto = ?",
                    [$id_proyecto]
                );
                $rowMN   = sqlsrv_fetch_array($stmtMN, SQLSRV_FETCH_ASSOC);
                $nextNum = intval($rowMN['max_num'] ?? 0) + 1;

                // ── Iterar fila a fila, igual que importar_historicos ──────────
                foreach ($filas as $fila) {
                    $id_pozo     = strtoupper(trim($fila['id_pozo'] ?? ''));
                    $id_medicion = $fila['id_medicion'];
                    // Número de orden real de campo (calidad_agua_laboratorio.orden):
                    // 1, 4, 5, 40... — el orden de aparición en la tabla consolidada.
                    $orden_real  = intval($fila['orden'] ?? 0);
                    // Fecha real de toma de muestra en campo (clave de la corrección)
                    $fecha_toma  = $fila['fechamonitoreo']
                        ? date('Y-m-d', strtotime($fila['fechamonitoreo']))
                        : $fecha_inicio;
                    $coord_este  = trim($fila['coord_este']  ?? '');
                    $coord_norte = trim($fila['coord_norte'] ?? '');

                    if (empty($id_pozo)) continue;

                    sqlsrv_begin_transaction($this->db);
                    try {
                        // 1. Asignación (IF NOT EXISTS) + captura de Id_Asignacion
                        // ⚠️ Monitoreo_Pozo_Asignacion NO tiene columna Usuario_Creacion
                        $id_asignacion = 0;
                        $stmtChkAsig = sqlsrv_query($this->db,
                            "SELECT Id_Asignacion FROM laboratorio.Monitoreo_Pozo_Asignacion
                             WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Activo = 1",
                            [$id_proyecto, $id_pozo]
                        );
                        $rowChkAsig = $stmtChkAsig !== false ? sqlsrv_fetch_array($stmtChkAsig, SQLSRV_FETCH_ASSOC) : null;
                        if ($rowChkAsig) {
                            $id_asignacion = intval($rowChkAsig['Id_Asignacion'] ?? 0);
                            // Re-sincronización: corregir el Orden con el valor real de PG
                            // (las asignaciones creadas antes de este fix quedaban con Orden=1).
                            if ($orden_real > 0) {
                                sqlsrv_query($this->db,
                                    "UPDATE laboratorio.Monitoreo_Pozo_Asignacion SET Orden = ? WHERE Id_Asignacion = ?",
                                    [$orden_real, $id_asignacion]
                                );
                            }
                        } else {
                            $stmtInsAsig = sqlsrv_query($this->db,
                                "INSERT INTO laboratorio.Monitoreo_Pozo_Asignacion
                                 (Id_Proyecto, Numero_Muestra, Id_Pozo, Orden, Es_Analisis_Laboratorio, Activo, Fecha_Creacion)
                                 VALUES (?, ?, ?, ?, 0, 1, GETDATE());
                                 SELECT SCOPE_IDENTITY() AS Id_Asignacion;",
                                [$id_proyecto, $nextNum, $id_pozo, $orden_real > 0 ? $orden_real : 1]
                            );
                            if ($stmtInsAsig !== false) {
                                sqlsrv_next_result($stmtInsAsig);
                                $rowInsAsig = sqlsrv_fetch_array($stmtInsAsig, SQLSRV_FETCH_ASSOC);
                                $id_asignacion = intval($rowInsAsig['Id_Asignacion'] ?? 0);
                            }
                        }

                        // 2. Coordenadas desde Catastro_Pozo SQL Server (preferencia)
                        $stmtCat = sqlsrv_query($this->db,
                            "SELECT coord_este, coord_norte FROM laboratorio.Catastro_Pozo WHERE Id_Pozo = ?",
                            [$id_pozo]
                        );
                        $rowCat = $stmtCat !== false ? sqlsrv_fetch_array($stmtCat, SQLSRV_FETCH_ASSOC) : null;
                        if ($rowCat) {
                            $coord_este  = $rowCat['coord_este']  ?? $coord_este;
                            $coord_norte = $rowCat['coord_norte'] ?? $coord_norte;
                        }

                        // 3. ¿La muestra ya existe para este proyecto + pozo + medición?
                        $stmtChkM = sqlsrv_query($this->db,
                            "SELECT Id_Muestra FROM laboratorio.Muestra_Lab
                             WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Activo = 1",
                            [$id_proyecto, $id_pozo]
                        );
                        $rowChkM = sqlsrv_fetch_array($stmtChkM, SQLSRV_FETCH_ASSOC);

                        if ($rowChkM) {
                            // Ya existe — actualizar solo Id_Medicion_PG si no está
                            $id_muestra_existente = intval($rowChkM['Id_Muestra']);
                            if ($id_medicion) {
                                sqlsrv_query($this->db,
                                    "UPDATE laboratorio.Muestra_Lab
                                     SET Id_Medicion_PG = ISNULL(Id_Medicion_PG, ?)
                                     WHERE Id_Muestra = ?",
                                    [$id_medicion, $id_muestra_existente]
                                );
                            }
                            sqlsrv_commit($this->db);
                            continue;
                        }

                        // 4. Crear Muestra_Lab con Fecha_Toma = fechamonitoreo real del campo
                        $obs   = "Extracción desde PostgreSQL. Monitoreo: $nombre_proyecto / Pozo: $id_pozo. Medicion_PG: $id_medicion";
                        $stmtM = sqlsrv_query($this->db,
                            "INSERT INTO laboratorio.Muestra_Lab
                             (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Id_Pozo, Id_Asignacion, Valle,
                              Eje_X, Eje_Y, Fecha_Recepcion, Fecha_Toma, Estado, Tipo_Servicio,
                              Observacion_Muestra, Es_Control_Calidad, Es_Drene, Es_Pozo,
                              Lab_Habilitado, Id_Medicion_PG, Fecha_Analisis, Usuario_Creacion, Activo, Fecha_Creacion)
                             VALUES (?, ?, ?, ?, ?, ?, ?,
                                     ?, ?, ?, ?, 'En Analisis', 'In-Situ Pozos',
                                     ?, 0, 0, 1,
                                     0, ?, ?, ?, 1, GETDATE());
                             SELECT SCOPE_IDENTITY() AS id;",
                            [
                                $id_cliente, $usuario_id, $usuario_id, $id_proyecto, $id_pozo,
                                ($id_asignacion > 0 ? $id_asignacion : null), $valle,
                                $coord_este, $coord_norte, $fecha_toma, $fecha_toma,
                                $obs,
                                $id_medicion, $fecha_toma, $usuario_id
                            ]
                        );
                        if ($stmtM === false) {
                            $stats['errores'][] = "Error muestra $id_pozo: " . print_r(sqlsrv_errors(), true);
                            sqlsrv_rollback($this->db);
                            continue;
                        }
                        sqlsrv_next_result($stmtM);
                        $rowMId     = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC);
                        $id_muestra = intval($rowMId['id'] ?? 0);
                        if ($id_muestra <= 0) { sqlsrv_rollback($this->db); continue; }
                        $stats['muestras_creadas']++;
                        $nextNum++;
                        $stats['pozos_nuevos_agregados']++;

                        // 5. Detalle_Agua
                        sqlsrv_query($this->db,
                            "INSERT INTO laboratorio.Detalle_Agua
                             (Id_Muestra, Uso_Agua, Fuente_Agua, Cantidad_Muestra, Nivel_Agua, Usuario_Creacion, Activo, Fecha_Creacion)
                             VALUES (?, 'Consumo Humano / Riego', 'Subterráneo', '1 Litro', ?, ?, 1, GETDATE())",
                            [$id_muestra, "Pozo $id_pozo", $usuario_id]
                        );

                        // 6. Muestra_Producto
                        if ($id_paquete) {
                            sqlsrv_query($this->db,
                                "INSERT INTO laboratorio.Muestra_Producto
                                 (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion)
                                 VALUES (?, ?, ?, ?, 1, GETDATE())",
                                [$id_muestra, $id_paquete, $id_cliente, $usuario_id]
                            );
                        }

                        // 7. Solicitud_Analisis + Resultado_Analisis (NULL = en blanco)
                        foreach ($serviciosPorProducto as $id_servicio) {
                            $stmtSol = sqlsrv_query($this->db,
                                "INSERT INTO laboratorio.Solicitud_Analisis
                                 (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion)
                                 VALUES (?, ?, 'En Analisis', ?, ?, 1, GETDATE());
                                 SELECT SCOPE_IDENTITY() AS id;",
                                [$id_muestra, $id_servicio, $fecha_toma, $usuario_id]
                            );
                            if ($stmtSol === false) continue;
                            sqlsrv_next_result($stmtSol);
                            $rowSolId   = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                            $id_solicitud = intval($rowSolId['id'] ?? 0);
                            if ($id_solicitud <= 0) continue;
                            $stats['solicitudes_creadas']++;

                            foreach (($parametrosPorServicio[$id_servicio] ?? []) as $id_parametro) {
                                $stmtRes = sqlsrv_query($this->db,
                                    "INSERT INTO laboratorio.Resultado_Analisis
                                     (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                                     VALUES (?, ?, NULL, ?, 1, GETDATE())",
                                    [$id_solicitud, $id_parametro, $usuario_id]
                                );
                                if ($stmtRes !== false) $stats['resultados_creados']++;
                            }
                        }

                        sqlsrv_commit($this->db);

                    } catch (Exception $e) {
                        sqlsrv_rollback($this->db);
                        $stats['errores'][] = "Error en pozo $id_pozo [$nombre_proyecto]: " . $e->getMessage();
                    }
                } // foreach filas

            } // foreach grupos

        } catch (Exception $e) {
            $stats['errores'][] = "Error general: " . $e->getMessage();
        }

        return $stats;
    }

    /**
     * Sincroniza los resultados in-situ (ph, ce, etc.) desde PostgreSQL a Resultado_Analisis
     * y habilita la muestra para laboratorio si están completos.
     */
    public function sincronizarInSitu($pdoPg, $id_proyecto, $usuario_id) {
        $stats = [
            'resultados_actualizados' => 0,
            'muestras_habilitadas'    => 0,
            'errores'                 => []
        ];

        try {
            $sqlProy  = "SELECT Nombre_Proyecto FROM laboratorio.Proyecto_Monitoreo WHERE Id_Proyecto = ?";
            $stmtProy = sqlsrv_query($this->db, $sqlProy, [$id_proyecto]);
            $proy     = sqlsrv_fetch_array($stmtProy, SQLSRV_FETCH_ASSOC);
            if (!$proy) throw new Exception("Proyecto no encontrado");

            $nombre_monitoreo = $proy['Nombre_Proyecto'];

            $sqlPg  = "SELECT * FROM " . PG_SCHEMA . ".pozos_monitoreo WHERE monitoreo = ?";
            $stmtPg = $pdoPg->prepare($sqlPg);
            $stmtPg->execute([$nombre_monitoreo]);
            $registrosPg = $stmtPg->fetchAll(PDO::FETCH_ASSOC);

            $sqlParam  = "SELECT Id_Parametro, Posgre_Nombre FROM laboratorio.Parametro_Analisis
                          WHERE Posgre_Tabla = 'pozos_monitoreo' AND Posgre_Nombre IS NOT NULL AND Activo = 1";
            $stmtParam = sqlsrv_query($this->db, $sqlParam);
            $mapaParams = [];
            while ($rowP = sqlsrv_fetch_array($stmtParam, SQLSRV_FETCH_ASSOC)) {
                $mapaParams[$rowP['Posgre_Nombre']] = $rowP['Id_Parametro'];
            }

            foreach ($registrosPg as $reg) {
                $id_pozo     = strtoupper(trim($reg['id_pozo']));
                $id_medicion = $reg['id_medicion'];

                $sqlSol = "SELECT sa.Id_Solicitud_Analisis, ml.Id_Muestra, ml.Lab_Habilitado, mpa.Es_Analisis_Laboratorio
                           FROM laboratorio.Muestra_Lab ml
                           INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Muestra = ml.Id_Muestra
                           INNER JOIN laboratorio.Servicio_Tecnico st ON st.Id_Servicio = sa.Id_Servicio
                           LEFT JOIN laboratorio.Monitoreo_Pozo_Asignacion mpa ON mpa.Id_Proyecto = ml.Id_Proyecto AND mpa.Id_Pozo = ml.Id_Pozo
                           WHERE ml.Id_Proyecto = ? AND ml.Id_Pozo = ? AND st.Nombre = 'In-Situ Pozos' AND ml.Activo = 1 AND sa.Activo = 1";
                $stmtSol = sqlsrv_query($this->db, $sqlSol, [$id_proyecto, $id_pozo]);
                $sol     = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);

                if ($sol) {
                    $id_solicitud = $sol['Id_Solicitud_Analisis'];
                    $id_muestra   = $sol['Id_Muestra'];

                    sqlsrv_query($this->db,
                        "UPDATE laboratorio.Muestra_Lab SET Id_Medicion_PG = ? WHERE Id_Muestra = ?",
                        [$id_medicion, $id_muestra]
                    );

                    foreach ($mapaParams as $colPg => $id_parametro) {
                        if (isset($reg[$colPg]) && $reg[$colPg] !== null && $reg[$colPg] !== '') {
                            $valor = floatval($reg[$colPg]);
                            sqlsrv_query($this->db,
                                "UPDATE laboratorio.Resultado_Analisis
                                 SET Valor_Hallado = ?, Fecha_Modificacion = GETDATE()
                                 WHERE Id_Solicitud_Analisis = ? AND Id_Parametro = ?",
                                [$valor, $id_solicitud, $id_parametro]
                            );
                            $stats['resultados_actualizados']++;
                        }
                    }

                    $in_situ_completo = (isset($reg['ph']) && $reg['ph'] !== null && $reg['ph'] !== '') &&
                                        (isset($reg['ce']) && $reg['ce'] !== null && $reg['ce'] !== '');

                    if ($in_situ_completo && $sol['Es_Analisis_Laboratorio'] == 1 && $sol['Lab_Habilitado'] == 0) {
                        sqlsrv_query($this->db,
                            "UPDATE laboratorio.Muestra_Lab SET Lab_Habilitado = 1 WHERE Id_Muestra = ?",
                            [$id_muestra]
                        );
                        $stats['muestras_habilitadas']++;
                    }

                    if ($in_situ_completo) {
                        sqlsrv_query($this->db,
                            "UPDATE laboratorio.Solicitud_Analisis SET Estado = 'Finalizado', Fecha_Modificacion = GETDATE() WHERE Id_Solicitud_Analisis = ?",
                            [$id_solicitud]
                        );
                    }
                }
            }

        } catch (Exception $e) {
            $stats['errores'][] = "Error general: " . $e->getMessage();
        }

        return $stats;
    }
}
?>
