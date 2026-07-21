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
     * Los servicios, parámetros y paquetes se configuran via setup_servicios_paquetes.php
     */
    private function asegurarDatosDePruebaLaboratorio($usuario_id) {
        // Cliente CHAVIMOCHIC
        $sqlCliente = "IF NOT EXISTS (SELECT 1 FROM laboratorio.Cliente WHERE Razon_Social LIKE '%CHAVIMOCHIC%' AND Activo = 1)
                       BEGIN
                           INSERT INTO laboratorio.Cliente (Razon_Social, RUC, Activo, Usuario_Creacion, Fecha_Creacion) 
                           VALUES ('CHAVIMOCHIC', '20146030971', 1, " . intval($usuario_id) . ", GETDATE());
                       END";
        sqlsrv_query($this->db, $sqlCliente);
    }

    /**
     * Sincroniza los monitoreos desde PostgreSQL y crea Proyectos Masivos de Pozos
     */
    public function sincronizarMonitoreos($pdoPg, $usuario_id) {
        $stats = [
            'proyectos_creados' => 0,
            'proyectos_actualizados' => 0,
            'errores' => []
        ];

        try {
            // Asegurar que exista configuración mínima de prueba
            $this->asegurarDatosDePruebaLaboratorio($usuario_id);

            // Obtener todos los monitoreos distintos de PG incluyendo su valle
            $sqlPg = "SELECT m.monitoreo, MIN(m.fechamonitoreo) as fecha_inicio, MAX(c.valle) as valle
                      FROM " . PG_SCHEMA . ".pozos_monitoreo m
                      LEFT JOIN " . PG_SCHEMA . ".pozos_catastro c ON m.id_pozo = c.id_pozo
                      WHERE m.monitoreo IS NOT NULL AND TRIM(m.monitoreo) <> ''
                      GROUP BY m.monitoreo 
                      ORDER BY fecha_inicio DESC";
            
            $stmtPg = $pdoPg->query($sqlPg);
            $monitoreos = $stmtPg->fetchAll(PDO::FETCH_ASSOC);

            $proyectoModel = new ProyectoModel($this->db);
            $asignacionModel = new MonitoreoPozoAsignacionModel($this->db);

            foreach ($monitoreos as $monitoreo) {
                $nombre_proyecto = trim($monitoreo['monitoreo']);
                $fecha_inicio = $monitoreo['fecha_inicio'];
                $valle = $monitoreo['valle'] ?: 'Chao'; // Default si no tiene valle

                // Calcular Temporada
                $mes = date('n', strtotime($fecha_inicio));
                $anio = date('Y', strtotime($fecha_inicio));
                $temporada = ($mes <= 6) ? ($anio . '-01') : ($anio . '-02');

                // Buscar si ya existe el proyecto
                $sqlCheck = "SELECT Id_Proyecto, Estado FROM laboratorio.Proyecto_Monitoreo 
                             WHERE Nombre_Proyecto = ? AND Es_Pozos = 1 AND Activo = 1";
                $stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$nombre_proyecto]);
                $proyectoExistente = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

                if (!$proyectoExistente) {
                    // Crear nuevo proyecto
                    sqlsrv_begin_transaction($this->db);
                    try {
                        // Insertar proyecto
                        $datosProy = [
                            'Nombre_Proyecto' => $nombre_proyecto,
                            'Fecha_Inicio' => $fecha_inicio,
                            'Valle' => $valle,
                            'Temporada' => $temporada,
                            'Tipo_Muestra' => 'Agua',
                            'Uso_Agua' => 'Otros',
                            'Fuente_Agua' => 'Subterráneo',
                            'Es_Pozos' => 1,
                            'Id_Responsable' => $usuario_id,
                            'Estado' => 'Planificado' // Inicialmente Planificado
                        ];
                        
                        $id_proyecto = $proyectoModel->guardar($datosProy);
                        
                        // Obtener los pozos de este monitoreo en PG
                        $sqlPozos = "SELECT DISTINCT id_pozo FROM " . PG_SCHEMA . ".pozos_monitoreo WHERE monitoreo = ? ORDER BY id_pozo";
                        $stmtPozos = $pdoPg->prepare($sqlPozos);
                        $stmtPozos->execute([$nombre_proyecto]);
                        $pozos = $stmtPozos->fetchAll(PDO::FETCH_COLUMN);

                        // Asignarlos al proyecto con Numero_Muestra secuencial y Orden = 1 (primera ronda)
                        $numero_muestra = 1;
                        $orden = 1;  // ← Primera ronda de monitoreo para sync de monitoreos
                        foreach ($pozos as $id_pozo) {
                            $id_pozo = strtoupper(trim($id_pozo));
                            if (empty($id_pozo)) continue;

                            // Insertar asignación con Orden
                            $sqlAsig = "INSERT INTO laboratorio.Monitoreo_Pozo_Asignacion 
                                      (Id_Proyecto, Numero_Muestra, Id_Pozo, Orden, Es_Analisis_Laboratorio, Usuario_Creacion, Activo) 
                                      VALUES (?, ?, ?, ?, 0, ?, 1)";
                            sqlsrv_query($this->db, $sqlAsig, [$id_proyecto, $numero_muestra, $id_pozo, $orden, $usuario_id]);
                            $numero_muestra++;
                        }

                        // Auto-asignar laboratorio basado en historial
                        $asignacionModel->autoAsignarLaboratorio($id_proyecto, null);

                        // Cambiar estado a "En Progreso" para disparar la creación de Muestras (crearMuestrasDesdePeriodo)
                        $proyectoModel->guardar([
                            'Id_Proyecto' => $id_proyecto,
                            'Estado' => 'En Progreso'
                        ]);

                        sqlsrv_commit($this->db);
                        $stats['proyectos_creados']++;
                    } catch (Exception $e) {
                        sqlsrv_rollback($this->db);
                        $stats['errores'][] = "Error al crear $nombre_proyecto: " . $e->getMessage();
                    }
                } else {
                    $stats['proyectos_actualizados']++;
                }
            }
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
            'muestras_habilitadas' => 0,
            'errores' => []
        ];

        try {
            // 1. Obtener nombre del proyecto (que coincide con 'monitoreo' en PG)
            $sqlProy = "SELECT Nombre_Proyecto FROM laboratorio.Proyecto_Monitoreo WHERE Id_Proyecto = ?";
            $stmtProy = sqlsrv_query($this->db, $sqlProy, [$id_proyecto]);
            $proy = sqlsrv_fetch_array($stmtProy, SQLSRV_FETCH_ASSOC);
            if (!$proy) throw new Exception("Proyecto no encontrado");
            
            $nombre_monitoreo = $proy['Nombre_Proyecto'];

            // 2. Obtener datos de PG para este monitoreo
            $sqlPg = "SELECT * FROM " . PG_SCHEMA . ".pozos_monitoreo WHERE monitoreo = ?";
            $stmtPg = $pdoPg->prepare($sqlPg);
            $stmtPg->execute([$nombre_monitoreo]);
            $registrosPg = $stmtPg->fetchAll(PDO::FETCH_ASSOC);

            // 3. Mapear parámetros que tienen Posgre_Tabla = 'pozos_monitoreo'
            $sqlParam = "SELECT Id_Parametro, Posgre_Nombre FROM laboratorio.Parametro_Analisis 
                         WHERE Posgre_Tabla = 'pozos_monitoreo' AND Posgre_Nombre IS NOT NULL AND Activo = 1";
            $stmtParam = sqlsrv_query($this->db, $sqlParam);
            $mapaParams = [];
            while ($rowP = sqlsrv_fetch_array($stmtParam, SQLSRV_FETCH_ASSOC)) {
                $mapaParams[$rowP['Posgre_Nombre']] = $rowP['Id_Parametro'];
            }

            // 4. Por cada registro de PG, actualizar en SQL Server
            foreach ($registrosPg as $reg) {
                $id_pozo = strtoupper(trim($reg['id_pozo']));
                $id_medicion = $reg['id_medicion'];

                // Encontrar la solicitud "In-Situ" de esta muestra en este proyecto
                $sqlSol = "SELECT sa.Id_Solicitud_Analisis, ml.Id_Muestra, ml.Lab_Habilitado, mpa.Es_Analisis_Laboratorio
                           FROM laboratorio.Muestra_Lab ml
                           INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Muestra = ml.Id_Muestra
                           INNER JOIN laboratorio.Servicio_Tecnico st ON st.Id_Servicio = sa.Id_Servicio
                           LEFT JOIN laboratorio.Monitoreo_Pozo_Asignacion mpa ON mpa.Id_Proyecto = ml.Id_Proyecto AND mpa.Id_Pozo = ml.Id_Pozo
                           WHERE ml.Id_Proyecto = ? AND ml.Id_Pozo = ? AND st.Nombre = 'In-Situ Pozos' AND ml.Activo = 1 AND sa.Activo = 1";
                $stmtSol = sqlsrv_query($this->db, $sqlSol, [$id_proyecto, $id_pozo]);
                $sol = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);

                if ($sol) {
                    $id_solicitud = $sol['Id_Solicitud_Analisis'];
                    $id_muestra = $sol['Id_Muestra'];
                    
                    // Actualizar Id_Medicion_PG en la muestra
                    sqlsrv_query($this->db, "UPDATE laboratorio.Muestra_Lab SET Id_Medicion_PG = ? WHERE Id_Muestra = ?", [$id_medicion, $id_muestra]);

                    // Actualizar cada parámetro mapeado
                    foreach ($mapaParams as $colPg => $id_parametro) {
                        if (isset($reg[$colPg]) && $reg[$colPg] !== null && $reg[$colPg] !== '') {
                            $valor = floatval($reg[$colPg]);
                            
                            $sqlUpdRes = "UPDATE laboratorio.Resultado_Analisis 
                                          SET Valor_Hallado = ?, Fecha_Modificacion = GETDATE()
                                          WHERE Id_Solicitud_Analisis = ? AND Id_Parametro = ?";
                            sqlsrv_query($this->db, $sqlUpdRes, [$valor, $id_solicitud, $id_parametro]);
                            $stats['resultados_actualizados']++;
                        }
                    }

                    // Verificar si In-Situ está "completo" para habilitar laboratorio (ej: ph y ce llenos)
                    $in_situ_completo = (isset($reg['ph']) && $reg['ph'] !== null && $reg['ph'] !== '') &&
                                        (isset($reg['ce']) && $reg['ce'] !== null && $reg['ce'] !== '');

                    if ($in_situ_completo && $sol['Es_Analisis_Laboratorio'] == 1 && $sol['Lab_Habilitado'] == 0) {
                        $sqlHab = "UPDATE laboratorio.Muestra_Lab SET Lab_Habilitado = 1 WHERE Id_Muestra = ?";
                        sqlsrv_query($this->db, $sqlHab, [$id_muestra]);
                        $stats['muestras_habilitadas']++;
                    }
                    
                    if ($in_situ_completo) {
                        $sqlEst = "UPDATE laboratorio.Solicitud_Analisis SET Estado = 'Finalizado', Fecha_Modificacion = GETDATE() WHERE Id_Solicitud_Analisis = ?";
                        sqlsrv_query($this->db, $sqlEst, [$id_solicitud]);
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
