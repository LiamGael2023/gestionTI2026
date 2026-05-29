<?php

class ProyectoModel {
    
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // ===== OBTENER PROYECTOS =====
    
    public function obtenerTodos($activos_solo = true) {
        $sql = "SELECT p.*, 
                       u.Nombres AS Responsable
                FROM laboratorio.Proyecto_Monitoreo p 
                LEFT JOIN comun.Usuarios u ON p.Id_Responsable = u.id_usuario";
        
        if ($activos_solo) {
            $sql .= " WHERE p.Activo = 1";
        }
        
        $sql .= " ORDER BY p.Fecha_Creacion DESC";
        
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error en obtenerTodos: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT p.*, 
                       u.Nombres AS Responsable
                FROM laboratorio.Proyecto_Monitoreo p 
                LEFT JOIN comun.Usuarios u ON p.Id_Responsable = u.id_usuario 
                WHERE p.Id_Proyecto = ? AND p.Activo = 1";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorId: ' . print_r(sqlsrv_errors(), true));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function obtenerDetalles($id_proyecto) {
        $sql = "SELECT pd.*, pv.Nombre_Comercial AS Nombre_Producto, pv.Tipo
                FROM laboratorio.Proyecto_Detalle_Analisis pd
                INNER JOIN laboratorio.Producto_Venta pv ON pd.Id_Producto_Venta = pv.Id_Producto
                WHERE pd.Id_Proyecto = ? AND pd.Activo = 1
                ORDER BY pd.Id_Detalle_Proyecto";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto));
        if ($stmt === false) {
            throw new Exception('Error en obtenerDetalles: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function proyectoTieneAnalisisIniciado($id_proyecto) {
        $sqlMuestras = "SELECT COUNT(1) AS total
                       FROM laboratorio.Muestra_Lab
                       WHERE Id_Proyecto = ? AND Activo = 1";

        $stmtMuestras = sqlsrv_query($this->db, $sqlMuestras, array($id_proyecto));
        if ($stmtMuestras === false) {
            throw new Exception('Error al validar muestras del proyecto: ' . print_r(sqlsrv_errors(), true));
        }

        $rowMuestras = sqlsrv_fetch_array($stmtMuestras, SQLSRV_FETCH_ASSOC);
        if (intval($rowMuestras['total'] ?? 0) > 0) {
            return true;
        }

        $sql = "SELECT COUNT(1) AS total
                FROM laboratorio.Resultado_Analisis ra
                INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
                INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                WHERE ml.Id_Proyecto = ?
                  AND ml.Activo = 1
                  AND sa.Activo = 1
                  AND ra.Activo = 1
                  AND (
                        ra.Valor_Hallado IS NOT NULL
                     OR ISNULL(LTRIM(RTRIM(ra.Observacion)), '') <> ''
                     OR sa.Id_Analista IS NOT NULL
                     OR sa.Estado = 'Finalizado'
                  )";

        $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto));
        if ($stmt === false) {
            throw new Exception('Error al validar inicio de analisis del proyecto: ' . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['total'] ?? 0) > 0;
    }

    // ===== CREAR PROYECTO =====
    
    public function guardar($datos) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        $log_file = dirname(__FILE__) . '/../../debug_resultado_analisis.log';
        file_put_contents($log_file, "\n[" . date('Y-m-d H:i:s') . "] === METODO guardar() LLAMADO ===\n", FILE_APPEND);
        file_put_contents($log_file, "Datos: " . json_encode($datos) . "\n", FILE_APPEND);

        if (empty($datos['Id_Proyecto'])) {
            // INSERT - Nuevo proyecto
            $sql = "INSERT INTO laboratorio.Proyecto_Monitoreo 
                    (Nombre_Proyecto, Valle, Temporada, Fecha_Inicio, Tipo_Muestra, Uso_Agua, Fuente_Agua, Nivel_Agua, Es_Control_Calidad, Es_Drene, Id_Responsable, Estado, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, GETDATE()); 
                    SELECT SCOPE_IDENTITY() AS id;";
            
            $params = array(
                $datos['Nombre_Proyecto'] ?? null,
                $datos['Valle'] ?? null,
                $datos['Temporada'] ?? null,
                $datos['Fecha_Inicio'] ?? null,
                $datos['Tipo_Muestra'] ?? null,
                $datos['Uso_Agua'] ?? null,
                $datos['Fuente_Agua'] ?? null,
                $datos['Nivel_Agua'] ?? null,
                intval($datos['Es_Control_Calidad'] ?? 0),
                intval($datos['Es_Drene'] ?? 0),
                $datos['Id_Responsable'] ?? $usuario_id,
                'Planificado', // Estado inicial SIEMPRE Planificado
                $usuario_id
            );

            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en INSERT: ' . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'] ?? 0;
        } else {
            // UPDATE - Actualizar proyecto
            $estado_anterior = null;
            $estado_nuevo = $datos['Estado'] ?? null;
            $fuentes_calidad = (isset($datos['Fuentes_Calidad']) && is_array($datos['Fuentes_Calidad']))
                ? $datos['Fuentes_Calidad']
                : null;

            // Obtener estado anterior si cambia a "En Progreso"
            if ($estado_nuevo === 'En Progreso') {
                $sqlCheck = "SELECT Estado FROM laboratorio.Proyecto_Monitoreo WHERE Id_Proyecto = ?";
                $stmtCheck = sqlsrv_query($this->db, $sqlCheck, array($datos['Id_Proyecto']));
                $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                $estado_anterior = $rowCheck['Estado'] ?? null;
                file_put_contents($log_file, "Estado anterior: $estado_anterior, Estado nuevo: $estado_nuevo\n", FILE_APPEND);
            }

            // Si solo viene Id_Proyecto y Estado, actualizar solo eso
            $claves = array_keys($datos);
            $soloCambioEstado = isset($datos['Id_Proyecto']) && isset($datos['Estado']);
            $clavesNoEstado = array_diff($claves, array('Id_Proyecto', 'Estado', 'Fuentes_Calidad', 'Fuentes_Drene'));
            if ($soloCambioEstado && count($clavesNoEstado) === 0) {
                $sql = "UPDATE laboratorio.Proyecto_Monitoreo 
                        SET Estado = ?, Fecha_Modificacion = GETDATE()
                        WHERE Id_Proyecto = ?";
                
                $params = array(
                    $estado_nuevo,
                    $datos['Id_Proyecto']
                );
            } else {
                // Actualizar múltiples campos
                $sql = "UPDATE laboratorio.Proyecto_Monitoreo 
                        SET Nombre_Proyecto = ?, Valle = ?, Temporada = ?, Fecha_Inicio = ?, Tipo_Muestra = ?, 
                            Uso_Agua = ?, Fuente_Agua = ?, Nivel_Agua = ?, Es_Control_Calidad = ?, Es_Drene = ?,
                            Id_Responsable = ?, Estado = ?, Fecha_Modificacion = GETDATE()
                        WHERE Id_Proyecto = ?";
                
                $params = array(
                    $datos['Nombre_Proyecto'] ?? null,
                    $datos['Valle'] ?? null,
                    $datos['Temporada'] ?? null,
                    $datos['Fecha_Inicio'] ?? null,
                    $datos['Tipo_Muestra'] ?? null,
                    $datos['Uso_Agua'] ?? null,
                    $datos['Fuente_Agua'] ?? null,
                    $datos['Nivel_Agua'] ?? null,
                    intval($datos['Es_Control_Calidad'] ?? 0),
                    intval($datos['Es_Drene'] ?? 0),
                    $datos['Id_Responsable'] ?? null,
                    $estado_nuevo ?? null,
                    $datos['Id_Proyecto']
                );
            }

            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en UPDATE: ' . print_r(sqlsrv_errors(), true));
            }
            
            file_put_contents($log_file, "UPDATE ejecutado. Verificando si debe crear muestras...\n", FILE_APPEND);
            file_put_contents($log_file, "Condición: estado_anterior !== 'En Progreso' && estado_nuevo === 'En Progreso'\n", FILE_APPEND);
            file_put_contents($log_file, "Valores: '$estado_anterior' !== 'En Progreso' && '$estado_nuevo' === 'En Progreso'\n", FILE_APPEND);
            
            // Si cambio a "En Progreso", crear muestras
            if ($estado_anterior !== 'En Progreso' && $estado_nuevo === 'En Progreso') {
                file_put_contents($log_file, "✓ LLAMANDO A crearMuestrasDesdePeriodo(" . $datos['Id_Proyecto'] . ")\n", FILE_APPEND);
                $this->crearMuestrasDesdePeriodo($datos['Id_Proyecto'], $fuentes_calidad, $datos['Fuentes_Drene'] ?? null);
            } else {
                file_put_contents($log_file, "✗ NO LLAMAR A crearMuestrasDesdePeriodo (condición no cumplida)\n", FILE_APPEND);
            }
            
            return $datos['Id_Proyecto'];
        }
    }

    // ===== CREAR MUESTRAS CUANDO PROYECTO INICIA =====

    private function crearMuestrasDesdePeriodo($id_proyecto, $fuentes_calidad = null, $fuentes_drene = null) {
        $log_file = dirname(__FILE__) . '/../../debug_resultado_analisis.log';
        file_put_contents($log_file, "\n[" . date('Y-m-d H:i:s') . "] === INICIANDO crearMuestrasDesdePeriodo($id_proyecto) ===\n", FILE_APPEND);
        
        // Obtener detalles del proyecto
        $detalles = $this->obtenerDetalles($id_proyecto);
        $proyecto = $this->obtenerPorId($id_proyecto);
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        $es_control_calidad_proyecto = intval($proyecto['Es_Control_Calidad'] ?? 0) === 1;
        $es_drene_proyecto = intval($proyecto['Es_Drene'] ?? 0) === 1;

        $total_muestras_planificadas = 0;
        foreach ($detalles as $detalleTmp) {
            $total_muestras_planificadas += max(0, intval($detalleTmp['Cantidad_Planificada'] ?? 0));
        }

        $fuentes_calidad_normalizadas = $es_control_calidad_proyecto
            ? $this->normalizarFuentesControlCalidad($fuentes_calidad, $total_muestras_planificadas)
            : [];
        $fuentes_drene_normalizadas = $es_drene_proyecto
            ? $this->normalizarFuentesControlCalidad($fuentes_drene, $total_muestras_planificadas)
            : [];
        $indice_fuente_calidad = 0;

        file_put_contents($log_file, "Proyecto: " . ($proyecto ? $proyecto['Nombre_Proyecto'] : 'NULL') . "\n", FILE_APPEND);
        file_put_contents($log_file, "Detalles encontrados: " . count($detalles) . "\n", FILE_APPEND);
        file_put_contents($log_file, "Usuario: $usuario_id\n", FILE_APPEND);

        if (!$proyecto || empty($detalles)) {
            file_put_contents($log_file, "✗ ERROR: Proyecto vacío o sin detalles\n", FILE_APPEND);
            return; // No hay nada que crear
        }

        // Validar antes de crear muestras: cada servicio del proyecto debe tener
        // al menos un parametro activo para poder crear filas en Resultado_Analisis.
        $this->validarServiciosConParametros($id_proyecto, $log_file);

        // Tomar el primer cliente activo disponible
        $id_cliente = $this->obtenerIdClienteProyecto();
        
        file_put_contents($log_file, "Id_Cliente obtenido: $id_cliente\n", FILE_APPEND);
        
        if ($id_cliente <= 0) {
            file_put_contents($log_file, "✗ ERROR: Id_Cliente inválido\n", FILE_APPEND);
            throw new Exception('No se pudo obtener Id_Cliente válido');
        }

        try {
            $muestra_count = 0;
            $solicitud_count = 0;
            $resultado_count = 0;

            $sqlFechaAnalisis = "SELECT GETDATE() AS Fecha_Analisis";
            $stmtFechaAnalisis = sqlsrv_query($this->db, $sqlFechaAnalisis);
            if ($stmtFechaAnalisis === false) {
                throw new Exception('Error al obtener fecha base de análisis del proyecto: ' . print_r(sqlsrv_errors(), true));
            }
            $rowFechaAnalisis = sqlsrv_fetch_array($stmtFechaAnalisis, SQLSRV_FETCH_ASSOC);
            $fecha_analisis_proyecto = $rowFechaAnalisis['Fecha_Analisis'] ?? null;
            if ($fecha_analisis_proyecto === null) {
                throw new Exception('No se pudo determinar la fecha base de análisis del proyecto');
            }

            $fechaAnalisisLog = ($fecha_analisis_proyecto instanceof DateTime)
                ? $fecha_analisis_proyecto->format('Y-m-d H:i:s')
                : strval($fecha_analisis_proyecto);
            file_put_contents($log_file, "Fecha base de análisis de proyecto: {$fechaAnalisisLog}\n", FILE_APPEND);
            
            foreach ($detalles as $detalle) {
                $cantidad_planificada = intval($detalle['Cantidad_Planificada']);
                file_put_contents($log_file, "\nProcesando detalle: " . $detalle['Nombre_Producto'] . " (Cantidad: $cantidad_planificada)\n", FILE_APPEND);
                
                // Crear una muestra por cada unidad planificada
                for ($i = 0; $i < $cantidad_planificada; $i++) {
                    $esControlMuestra = $es_control_calidad_proyecto ? 1 : 0;
                    $esDreneMuestra = $es_drene_proyecto ? 1 : 0;
                    $fuente_muestra = $proyecto['Fuente_Agua'] ?? null;
                    if ($es_control_calidad_proyecto) {
                        $fuente_muestra = $this->obtenerFuenteControlCalidadPorIndice($indice_fuente_calidad, $fuentes_calidad_normalizadas);
                        $indice_fuente_calidad++;
                    } elseif ($es_drene_proyecto) {
                        $fuente_muestra = $this->obtenerFuenteControlCalidadPorIndice($indice_fuente_calidad, $fuentes_drene_normalizadas);
                        $indice_fuente_calidad++;
                    }

                    // INSERT en Muestra_Lab
                    $sqlMuestra = "INSERT INTO laboratorio.Muestra_Lab 
                        (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Valle, Eje_X, Eje_Y,
                         Fecha_Recepcion, Fecha_Toma, Estado, Tipo_Servicio, Observacion_Muestra,
                         Es_Control_Calidad, Es_Drene, Fecha_Analisis,
                         Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, ?, '', '', 
                                GETDATE(), GETDATE(), 'En Análisis', ?, 'Muestra de Proyecto: ' + ?,
                                ?, ?, ?,
                                ?, 1, GETDATE()); 
                        SELECT SCOPE_IDENTITY() AS id;";
                    
                    $paramsM = array(
                        $id_cliente,           // Id_Cliente
                        $usuario_id,           // Id_Receptor
                        $usuario_id,           // Id_Especialista
                        $id_proyecto,          // Id_Proyecto
                        $proyecto['Valle'],    // Valle
                        $detalle['Nombre_Producto'],  // Tipo_Servicio
                        $proyecto['Nombre_Proyecto'], // Observacion
                        $esControlMuestra,            // Es_Control_Calidad
                        $esDreneMuestra,              // Es_Drene
                        $fecha_analisis_proyecto,     // Fecha_Analisis
                        $usuario_id            // Usuario_Creacion
                    );

                    $stmt = sqlsrv_query($this->db, $sqlMuestra, $paramsM);
                    if ($stmt === false) {
                        file_put_contents($log_file, "✗ ERROR INSERT Muestra: " . print_r(sqlsrv_errors(), true) . "\n", FILE_APPEND);
                        throw new Exception('Error al insertar muestra: ' . print_r(sqlsrv_errors(), true));
                    }
                    
                    sqlsrv_next_result($stmt);
                    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    $id_muestra = intval($row['id'] ?? 0);
                    
                    if ($id_muestra > 0) {
                        $muestra_count++;
                        file_put_contents($log_file, "  ✓ Muestra creada: Id_Muestra=$id_muestra\n", FILE_APPEND);
                        // Crear registro en Muestra_Producto
                        if (!empty($detalle['Id_Producto_Venta'])) {
                            $sqlProducto = "INSERT INTO laboratorio.Muestra_Producto 
                                (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion)
                                VALUES (?, ?, ?, ?, 1, GETDATE());
                                SELECT SCOPE_IDENTITY() AS id;";
                            
                            $paramsP = array($id_muestra, $detalle['Id_Producto_Venta'], $id_cliente, $usuario_id);
                            $stmtP = sqlsrv_query($this->db, $sqlProducto, $paramsP);
                            if ($stmtP === false) {
                                throw new Exception('Error al insertar muestra_producto: ' . print_r(sqlsrv_errors(), true));
                            }
                            
                            // Obtener ID de Muestra_Producto recién creado
                            sqlsrv_next_result($stmtP);
                            $rowP = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC);
                            $id_muestra_producto = intval($rowP['id'] ?? 0);
                            
                            // Crear Solicitudes de Análisis para cada servicio del producto
                            $sqlServicios = "SELECT DISTINCT ps.Id_Servicio 
                                            FROM laboratorio.Producto_Servicio ps 
                                            WHERE ps.Id_Producto = ? AND ps.Activo = 1";
                            $paramsServ = array($detalle['Id_Producto_Venta']);
                            $stmtServ = sqlsrv_query($this->db, $sqlServicios, $paramsServ);
                            
                            file_put_contents($log_file, "    Buscando servicios para Id_Producto=" . $detalle['Id_Producto_Venta'] . "\n", FILE_APPEND);
                            
                            if ($stmtServ !== false) {
                                $servicio_count = 0;
                                while ($rowServ = sqlsrv_fetch_array($stmtServ, SQLSRV_FETCH_ASSOC)) {
                                    $id_servicio = intval($rowServ['Id_Servicio'] ?? 0);
                                    $servicio_count++;
                                    
                                    file_put_contents($log_file, "    Servicio #$servicio_count: Id_Servicio=$id_servicio\n", FILE_APPEND);
                                    
                                    if ($id_servicio > 0) {
                                        // PASO 1: Insertar Solicitud_Analisis
                                        $sqlSolicitud = "INSERT INTO laboratorio.Solicitud_Analisis 
                                            (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion)
                                            VALUES (?, ?, 'En Análisis', GETDATE(), ?, 1, GETDATE());
                                            SELECT SCOPE_IDENTITY() AS id";
                                        
                                        $paramsSol = array($id_muestra, $id_servicio, $usuario_id);
                                        $stmtSol = sqlsrv_query($this->db, $sqlSolicitud, $paramsSol);
                                        
                                        if ($stmtSol === false) {
                                            file_put_contents($log_file, "    ✗ ERROR INSERT Solicitud: " . print_r(sqlsrv_errors(), true) . "\n", FILE_APPEND);
                                            throw new Exception('Error al crear solicitud de análisis: ' . print_r(sqlsrv_errors(), true));
                                        }
                                        
                                        file_put_contents($log_file, "    ✓ Solicitud INSERT exitoso\n", FILE_APPEND);
                                        $solicitud_count++;
                                        
                                        // PASO 2: Obtener el ID de la solicitud creada
                                        sqlsrv_next_result($stmtSol);
                                        $row_id = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                                        $id_solicitud = intval($row_id['id'] ?? 0);
                                        
                                        file_put_contents($log_file, "    → Id_Solicitud_Analisis: $id_solicitud\n", FILE_APPEND);
                                        
                                        // PASO 3: Crear registros en blanco para TODOS los parámetros del servicio
                                        if ($id_solicitud > 0) {
                                            // Obtener parámetros del servicio
                                                                                        $sqlParams = "SELECT pa.Id_Parametro
                                                                                                                 FROM laboratorio.Parametro_Analisis pa
                                                                                                                 WHERE pa.Activo = 1
                                                                                                                     AND pa.Id_Servicio = ?
                                                                                                                 ORDER BY pa.Id_Parametro";
                                            
                                            file_put_contents($log_file, "      Buscando parámetros para Id_Servicio=$id_servicio\n", FILE_APPEND);
                                            
                                            $stmtParams = sqlsrv_query($this->db, $sqlParams, array($id_servicio));
                                            
                                            if ($stmtParams === false) {
                                                file_put_contents($log_file, "    ✗ ERROR SELECT parámetros: " . print_r(sqlsrv_errors(), true) . "\n", FILE_APPEND);
                                                throw new Exception('Error al obtener parámetros: ' . print_r(sqlsrv_errors(), true));
                                            }
                                            
                                            $param_count = 0;
                                            $resultado_local_count = 0;
                                            
                                            // Crear Resultado_Analisis para cada parámetro
                                            while ($rowParam = sqlsrv_fetch_array($stmtParams, SQLSRV_FETCH_ASSOC)) {
                                                $id_parametro = intval($rowParam['Id_Parametro']);
                                                $param_count++;
                                                
                                                // Obtener Id_Normativa del parámetro si existe
                                                $sqlNormativa = "SELECT TOP 1 Id_Normativa FROM laboratorio.Limite_Legal 
                                                                WHERE Id_Parametro = ? AND Activo = 1";
                                                $stmtNormativa = sqlsrv_query($this->db, $sqlNormativa, array($id_parametro));
                                                $row_normativa = sqlsrv_fetch_array($stmtNormativa, SQLSRV_FETCH_ASSOC);
                                                $id_normativa = !empty($row_normativa['Id_Normativa']) ? intval($row_normativa['Id_Normativa']) : null;
                                                
                                                // Insertar Resultado_Analisis
                                                $sqlResultado = "INSERT INTO laboratorio.Resultado_Analisis 
                                                    (Id_Solicitud_Analisis, Id_Parametro, Id_Normativa, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                                                    VALUES (?, ?, ?, NULL, ?, 1, GETDATE())";
                                                
                                                $paramsResultado = array($id_solicitud, $id_parametro, $id_normativa, $usuario_id);
                                                $stmtResultado = sqlsrv_query($this->db, $sqlResultado, $paramsResultado);
                                                
                                                if ($stmtResultado === false) {
                                                    file_put_contents($log_file, "      ✗ ERROR INSERT Resultado: " . print_r(sqlsrv_errors(), true) . "\n", FILE_APPEND);
                                                    throw new Exception('Error al crear resultado: ' . print_r(sqlsrv_errors(), true));
                                                }
                                                
                                                $resultado_local_count++;
                                                $resultado_count++;
                                                file_put_contents($log_file, "      ✓ Resultado#$resultado_local_count: Id_Param=$id_parametro, Id_Normativa=$id_normativa\n", FILE_APPEND);
                                            }
                                            
                                            file_put_contents($log_file, "    → Parámetros encontrados: $param_count, Resultados creados: $resultado_local_count\n", FILE_APPEND);
                                        } else {
                                            file_put_contents($log_file, "    ✗ ERROR: Id_Solicitud_Analisis = 0\n", FILE_APPEND);
                                        }
                                    }
                                }
                            }
                            
                            // Registrar consumo de reactivos de forma interna e idempotente.
                            if ($id_muestra_producto > 0) {
                                $this->registrarConsumoReactivosInterno($id_muestra_producto, $usuario_id);
                            }
                        }

                        // Si es tipo Agua, crear registro en Detalle_Agua
                        // Nota: Fuente_Agua almacena el tipo (Río/Canal/Pozo = $proyecto['Nivel_Agua']),
                        //       Nivel_Agua almacena la fuente individual (RIO TABLACHACA / dren name = $fuente_muestra).
                        if ($proyecto['Tipo_Muestra'] === 'Agua' && !empty($proyecto['Uso_Agua'])) {
                            $sqlDetalleAgua = "INSERT INTO laboratorio.Detalle_Agua 
                                (Id_Muestra, Uso_Agua, Fuente_Agua, Cantidad_Muestra, Nivel_Agua, Usuario_Creacion, Activo, Fecha_Creacion)
                                VALUES (?, ?, ?, '1 Litro', ?, ?, 1, GETDATE())";
                            
                            $paramsA = array(
                                $id_muestra,
                                $proyecto['Uso_Agua'],
                                $proyecto['Nivel_Agua'] ?? null,  // Fuente_Agua = tipo (Río, Canal, etc.)
                                $fuente_muestra,                  // Nivel_Agua  = fuente individual (RIO TABLACHACA / dren)
                                $usuario_id
                            );
                            $stmtA = sqlsrv_query($this->db, $sqlDetalleAgua, $paramsA);
                            if ($stmtA === false) {
                                throw new Exception('Error al insertar detalle_agua: ' . print_r(sqlsrv_errors(), true));
                            }
                        }
                    }
                }
            }

            // Al finalizar la creación masiva, garantizar que todas las muestras tengan
            // celdas en Resultado_Analisis por cada parámetro del servicio solicitado.
            $resultado_count += $this->asegurarResultadosProyecto($id_proyecto, $usuario_id, $log_file);
        } catch (Exception $e) {
            file_put_contents($log_file, "\n✗ EXCEPCION CAPTURADA:\n" . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }
        
        file_put_contents($log_file, "\n=== RESUMEN crearMuestrasDesdePeriodo($id_proyecto) ===\n", FILE_APPEND);
        file_put_contents($log_file, "Muestras creadas: $muestra_count\n", FILE_APPEND);
        file_put_contents($log_file, "Solicitudes creadas: $solicitud_count\n", FILE_APPEND);
        file_put_contents($log_file, "Resultados creados: $resultado_count\n", FILE_APPEND);
        file_put_contents($log_file, "=== FIN crearMuestrasDesdePeriodo ===\n\n", FILE_APPEND);
    }

    public function agregarMuestrasAdicionales($id_proyecto, $extras_por_producto) {
        $log_file = dirname(__FILE__) . '/../../debug_resultado_analisis.log';
        $id_proyecto = intval($id_proyecto);
        $usuario_id = $_SESSION['usuario_id'] ?? 1;

        file_put_contents($log_file, "\n[" . date('Y-m-d H:i:s') . "] === INICIANDO agregarMuestrasAdicionales($id_proyecto) ===\n", FILE_APPEND);

        if ($id_proyecto <= 0) {
            throw new Exception('ID de proyecto inválido para agregar muestras.');
        }

        $proyecto = $this->obtenerPorId($id_proyecto);
        if (!$proyecto) {
            throw new Exception('Proyecto no encontrado.');
        }

        $estado_proyecto = trim((string)($proyecto['Estado'] ?? ''));
        if ($estado_proyecto !== 'En Progreso') {
            throw new Exception('Solo se pueden agregar muestras cuando el proyecto está en estado En Progreso.');
        }

        $detalles = $this->obtenerDetalles($id_proyecto);
        if (empty($detalles)) {
            throw new Exception('El proyecto no tiene detalles de servicio configurados.');
        }

        $mapa_detalles = [];
        foreach ($detalles as $det) {
            $id_prod = intval($det['Id_Producto_Venta'] ?? 0);
            if ($id_prod > 0) {
                $mapa_detalles[$id_prod] = $det;
            }
        }

        $extras_normalizados = [];
        if (is_array($extras_por_producto)) {
            foreach ($extras_por_producto as $extra) {
                $id_producto = intval($extra['id'] ?? ($extra['id_producto'] ?? 0));
                $cantidad_extra = intval($extra['cantidad_extra'] ?? ($extra['cantidad'] ?? 0));
                if ($id_producto > 0 && $cantidad_extra > 0) {
                    if (!isset($extras_normalizados[$id_producto])) {
                        $extras_normalizados[$id_producto] = 0;
                    }
                    $extras_normalizados[$id_producto] += $cantidad_extra;
                }
            }
        }

        if (empty($extras_normalizados)) {
            throw new Exception('Debe indicar al menos una cantidad adicional mayor a 0.');
        }

        foreach ($extras_normalizados as $id_producto => $cantidad_extra) {
            if (!isset($mapa_detalles[$id_producto])) {
                throw new Exception('El producto/servicio #' . intval($id_producto) . ' no pertenece al proyecto.');
            }
            if ($cantidad_extra <= 0) {
                throw new Exception('Cantidad adicional inválida para producto #' . intval($id_producto));
            }
        }

        $this->validarServiciosConParametros($id_proyecto, $log_file);

        $id_cliente = $this->obtenerIdClienteProyecto();
        if ($id_cliente <= 0) {
            throw new Exception('No se pudo obtener cliente para crear muestras adicionales.');
        }

        $sqlFechaAnalisis = "SELECT GETDATE() AS Fecha_Analisis";
        $stmtFechaAnalisis = sqlsrv_query($this->db, $sqlFechaAnalisis);
        if ($stmtFechaAnalisis === false) {
            throw new Exception('Error al obtener fecha base de análisis: ' . print_r(sqlsrv_errors(), true));
        }
        $rowFechaAnalisis = sqlsrv_fetch_array($stmtFechaAnalisis, SQLSRV_FETCH_ASSOC);
        $fecha_analisis_proyecto = $rowFechaAnalisis['Fecha_Analisis'] ?? null;
        if ($fecha_analisis_proyecto === null) {
            throw new Exception('No se pudo determinar la fecha de análisis para muestras adicionales.');
        }

        $es_control_calidad_proyecto = intval($proyecto['Es_Control_Calidad'] ?? 0) === 1;
        $es_drene_proyecto = intval($proyecto['Es_Drene'] ?? 0) === 1;
        $stmtConteoMuestras = sqlsrv_query(
            $this->db,
            "SELECT COUNT(1) AS total FROM laboratorio.Muestra_Lab WHERE Id_Proyecto = ? AND Activo = 1",
            [$id_proyecto]
        );
        if ($stmtConteoMuestras === false) {
            throw new Exception('Error al contar muestras actuales del proyecto: ' . print_r(sqlsrv_errors(), true));
        }
        $rowConteo = sqlsrv_fetch_array($stmtConteoMuestras, SQLSRV_FETCH_ASSOC);
        $indice_fuente_calidad = intval($rowConteo['total'] ?? 0);

        $muestra_count = 0;
        $solicitud_count = 0;
        $resultado_count = 0;

        foreach ($extras_normalizados as $id_producto => $cantidad_extra) {
            $detalle = $mapa_detalles[$id_producto];
            $cantidad_actual = intval($detalle['Cantidad_Planificada'] ?? 0);
            $nueva_cantidad = $cantidad_actual + $cantidad_extra;

            // Incrementa reserva planificada del detalle antes de crear las muestras extra.
            $this->guardarDetalle($id_proyecto, $id_producto, $nueva_cantidad);

            file_put_contents(
                $log_file,
                "Detalle producto #{$id_producto}: cantidad actual={$cantidad_actual}, extra={$cantidad_extra}, nueva={$nueva_cantidad}\n",
                FILE_APPEND
            );

            for ($i = 0; $i < $cantidad_extra; $i++) {
                $esControlMuestra = $es_control_calidad_proyecto ? 1 : 0;
                $esDreneMuestra = $es_drene_proyecto ? 1 : 0;
                $fuente_muestra = $proyecto['Fuente_Agua'] ?? null;
                if ($es_control_calidad_proyecto) {
                    $fuente_muestra = $this->obtenerFuenteControlCalidadPorIndice($indice_fuente_calidad, []);
                    $indice_fuente_calidad++;
                } elseif ($es_drene_proyecto) {
                    $fuente_muestra = $this->obtenerFuenteControlCalidadPorIndice($indice_fuente_calidad, []);
                    $indice_fuente_calidad++;
                }

                $sqlMuestra = "INSERT INTO laboratorio.Muestra_Lab
                    (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Valle, Eje_X, Eje_Y,
                     Fecha_Recepcion, Fecha_Toma, Estado, Tipo_Servicio, Observacion_Muestra,
                     Es_Control_Calidad, Es_Drene, Fecha_Analisis,
                     Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, '', '',
                            GETDATE(), GETDATE(), 'En Análisis', ?, 'Muestra adicional de Proyecto: ' + ?,
                            ?, ?, ?,
                            ?, 1, GETDATE());
                    SELECT SCOPE_IDENTITY() AS id;";

                $paramsM = array(
                    $id_cliente,
                    $usuario_id,
                    $usuario_id,
                    $id_proyecto,
                    $proyecto['Valle'],
                    $detalle['Nombre_Producto'],
                    $proyecto['Nombre_Proyecto'],
                    $esControlMuestra,
                    $esDreneMuestra,
                    $fecha_analisis_proyecto,
                    $usuario_id
                );

                $stmtMuestra = sqlsrv_query($this->db, $sqlMuestra, $paramsM);
                if ($stmtMuestra === false) {
                    throw new Exception('Error al insertar muestra adicional: ' . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_next_result($stmtMuestra);
                $rowMuestra = sqlsrv_fetch_array($stmtMuestra, SQLSRV_FETCH_ASSOC);
                $id_muestra = intval($rowMuestra['id'] ?? 0);

                if ($id_muestra <= 0) {
                    throw new Exception('No se pudo obtener Id_Muestra en creación adicional.');
                }

                $muestra_count++;

                if (!empty($detalle['Id_Producto_Venta'])) {
                    $sqlProducto = "INSERT INTO laboratorio.Muestra_Producto
                        (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, 1, GETDATE());
                        SELECT SCOPE_IDENTITY() AS id;";

                    $stmtP = sqlsrv_query(
                        $this->db,
                        $sqlProducto,
                        array($id_muestra, $detalle['Id_Producto_Venta'], $id_cliente, $usuario_id)
                    );
                    if ($stmtP === false) {
                        throw new Exception('Error al insertar muestra_producto adicional: ' . print_r(sqlsrv_errors(), true));
                    }

                    sqlsrv_next_result($stmtP);
                    $rowP = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC);
                    $id_muestra_producto = intval($rowP['id'] ?? 0);

                    $sqlServicios = "SELECT DISTINCT ps.Id_Servicio
                                    FROM laboratorio.Producto_Servicio ps
                                    WHERE ps.Id_Producto = ? AND ps.Activo = 1";
                    $stmtServ = sqlsrv_query($this->db, $sqlServicios, array($detalle['Id_Producto_Venta']));
                    if ($stmtServ === false) {
                        throw new Exception('Error al obtener servicios para muestra adicional: ' . print_r(sqlsrv_errors(), true));
                    }

                    while ($rowServ = sqlsrv_fetch_array($stmtServ, SQLSRV_FETCH_ASSOC)) {
                        $id_servicio = intval($rowServ['Id_Servicio'] ?? 0);
                        if ($id_servicio <= 0) {
                            continue;
                        }

                        $sqlSolicitud = "INSERT INTO laboratorio.Solicitud_Analisis
                            (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion)
                            VALUES (?, ?, 'En Análisis', GETDATE(), ?, 1, GETDATE());
                            SELECT SCOPE_IDENTITY() AS id";

                        $stmtSol = sqlsrv_query($this->db, $sqlSolicitud, array($id_muestra, $id_servicio, $usuario_id));
                        if ($stmtSol === false) {
                            throw new Exception('Error al crear solicitud adicional: ' . print_r(sqlsrv_errors(), true));
                        }

                        $solicitud_count++;
                        sqlsrv_next_result($stmtSol);
                        $rowIdSol = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                        $id_solicitud = intval($rowIdSol['id'] ?? 0);

                        if ($id_solicitud <= 0) {
                            throw new Exception('No se pudo obtener Id_Solicitud_Analisis en creación adicional.');
                        }

                        $sqlParams = "SELECT pa.Id_Parametro
                                      FROM laboratorio.Parametro_Analisis pa
                                      WHERE pa.Activo = 1 AND pa.Id_Servicio = ?
                                      ORDER BY pa.Id_Parametro";
                        $stmtParams = sqlsrv_query($this->db, $sqlParams, array($id_servicio));
                        if ($stmtParams === false) {
                            throw new Exception('Error al obtener parámetros en muestra adicional: ' . print_r(sqlsrv_errors(), true));
                        }

                        while ($rowParam = sqlsrv_fetch_array($stmtParams, SQLSRV_FETCH_ASSOC)) {
                            $id_parametro = intval($rowParam['Id_Parametro'] ?? 0);
                            if ($id_parametro <= 0) {
                                continue;
                            }

                            $sqlNormativa = "SELECT TOP 1 Id_Normativa FROM laboratorio.Limite_Legal
                                             WHERE Id_Parametro = ? AND Activo = 1";
                            $stmtNormativa = sqlsrv_query($this->db, $sqlNormativa, array($id_parametro));
                            $rowNormativa = $stmtNormativa ? sqlsrv_fetch_array($stmtNormativa, SQLSRV_FETCH_ASSOC) : null;
                            $id_normativa = !empty($rowNormativa['Id_Normativa']) ? intval($rowNormativa['Id_Normativa']) : null;

                            $sqlResultado = "INSERT INTO laboratorio.Resultado_Analisis
                                (Id_Solicitud_Analisis, Id_Parametro, Id_Normativa, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                                VALUES (?, ?, ?, NULL, ?, 1, GETDATE())";

                            $stmtResultado = sqlsrv_query(
                                $this->db,
                                $sqlResultado,
                                array($id_solicitud, $id_parametro, $id_normativa, $usuario_id)
                            );
                            if ($stmtResultado === false) {
                                throw new Exception('Error al crear resultado adicional: ' . print_r(sqlsrv_errors(), true));
                            }

                            $resultado_count++;
                        }
                    }

                    if ($id_muestra_producto > 0) {
                        $this->registrarConsumoReactivosInterno($id_muestra_producto, $usuario_id);
                    }
                }

                if ($proyecto['Tipo_Muestra'] === 'Agua' && !empty($proyecto['Uso_Agua'])) {
                    $sqlDetalleAgua = "INSERT INTO laboratorio.Detalle_Agua
                        (Id_Muestra, Uso_Agua, Fuente_Agua, Cantidad_Muestra, Nivel_Agua, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, '1 Litro', ?, ?, 1, GETDATE())";

                    $paramsA = array(
                        $id_muestra,
                        $proyecto['Uso_Agua'],
                        $proyecto['Nivel_Agua'] ?? null,  // Fuente_Agua = tipo (Río, Canal, etc.)
                        $fuente_muestra,                  // Nivel_Agua  = fuente individual
                        $usuario_id
                    );
                    $stmtA = sqlsrv_query($this->db, $sqlDetalleAgua, $paramsA);
                    if ($stmtA === false) {
                        throw new Exception('Error al insertar detalle_agua adicional: ' . print_r(sqlsrv_errors(), true));
                    }
                }
            }
        }

        $resultado_count += $this->asegurarResultadosProyecto($id_proyecto, $usuario_id, $log_file);

        file_put_contents($log_file, "Muestras adicionales creadas: {$muestra_count}\n", FILE_APPEND);
        file_put_contents($log_file, "Solicitudes adicionales creadas: {$solicitud_count}\n", FILE_APPEND);
        file_put_contents($log_file, "Resultados adicionales creados: {$resultado_count}\n", FILE_APPEND);
        file_put_contents($log_file, "=== FIN agregarMuestrasAdicionales ===\n\n", FILE_APPEND);

        return array(
            'muestras_creadas' => intval($muestra_count),
            'solicitudes_creadas' => intval($solicitud_count),
            'resultados_creados' => intval($resultado_count)
        );
    }

    private function obtenerFuentesControlCalidadBase() {
        return array(
            'RIO TABLACHACA',
            'RIO SANTA',
            'ENTRADA DESARENADOR',
            'SALIDA DESARENADOR',
            'CANAL EVACUADOR',
            'RIO VIRU',
            'RIO MOCHE',
            'RIO CHICAMA',
            'CANAL MADRE',
            'CENTRAL HIDROELECTRICA VIRU SAN JOSE'
        );
    }

    private function normalizarFuentesControlCalidad($fuentesEntrada, $totalMuestras) {
        $base = $this->obtenerFuentesControlCalidadBase();
        $normalizadas = array();

        if (is_array($fuentesEntrada)) {
            foreach ($fuentesEntrada as $f) {
                $txt = trim((string)$f);
                $normalizadas[] = $txt !== '' ? $txt : null;
            }
        }

        $total = max(0, intval($totalMuestras));
        for ($i = 0; $i < $total; $i++) {
            if (!isset($normalizadas[$i]) || $normalizadas[$i] === null || $normalizadas[$i] === '') {
                $normalizadas[$i] = $base[$i % count($base)];
            }
        }

        return $normalizadas;
    }

    private function obtenerFuenteControlCalidadPorIndice($indice, $fuentesNormalizadas) {
        $indice = intval($indice);
        if (isset($fuentesNormalizadas[$indice])) {
            $valor = trim((string)$fuentesNormalizadas[$indice]);
            if ($valor !== '') {
                return $valor;
            }
        }

        $base = $this->obtenerFuentesControlCalidadBase();
        return $base[$indice % count($base)];
    }

    private function asegurarResultadosProyecto($id_proyecto, $usuario_id, $log_file = null) {
        $sqlReactivar = "UPDATE ra
                         SET ra.Activo = 1,
                             ra.Fecha_Modificacion = GETDATE()
                         FROM laboratorio.Resultado_Analisis ra
                         INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
                         INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                         INNER JOIN laboratorio.Parametro_Analisis pa ON pa.Id_Parametro = ra.Id_Parametro
                         WHERE ml.Id_Proyecto = ?
                           AND ml.Activo = 1
                           AND sa.Activo = 1
                           AND pa.Activo = 1
                           AND pa.Id_Servicio = sa.Id_Servicio
                           AND ra.Activo = 0";
        $stmtReactivar = sqlsrv_query($this->db, $sqlReactivar, array($id_proyecto));
        if ($stmtReactivar === false) {
            throw new Exception('Error al reactivar resultados del proyecto: ' . print_r(sqlsrv_errors(), true));
        }

        $reactivados = sqlsrv_rows_affected($stmtReactivar);
        if (!is_int($reactivados) || $reactivados < 0) {
            $reactivados = 0;
        }

        $sql = "INSERT INTO laboratorio.Resultado_Analisis
                    (Id_Solicitud_Analisis, Id_Parametro, Id_Normativa, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                SELECT sa.Id_Solicitud_Analisis,
                       pa.Id_Parametro,
                       ll.Id_Normativa,
                       NULL,
                       ?,
                       1,
                       GETDATE()
                FROM laboratorio.Solicitud_Analisis sa
                INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                INNER JOIN laboratorio.Parametro_Analisis pa
                    ON pa.Id_Servicio = sa.Id_Servicio
                   AND pa.Activo = 1
                OUTER APPLY (
                    SELECT TOP 1 Id_Normativa
                    FROM laboratorio.Limite_Legal
                    WHERE Id_Parametro = pa.Id_Parametro AND Activo = 1
                    ORDER BY Id_Normativa
                ) ll
                WHERE ml.Id_Proyecto = ?
                  AND ml.Activo = 1
                  AND sa.Activo = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM laboratorio.Resultado_Analisis ra
                      WHERE ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                        AND ra.Id_Parametro = pa.Id_Parametro
                  )";

        $stmt = sqlsrv_query($this->db, $sql, array($usuario_id, $id_proyecto));
        if ($stmt === false) {
            throw new Exception('Error al asegurar resultados del proyecto: ' . print_r(sqlsrv_errors(), true));
        }

        $insertados = sqlsrv_rows_affected($stmt);
        if (!is_int($insertados) || $insertados < 0) {
            $insertados = 0;
        }

        if (!empty($log_file)) {
            file_put_contents($log_file, "Aseguramiento final Resultado_Analisis: {$reactivados} filas reactivadas\n", FILE_APPEND);
            file_put_contents($log_file, "Aseguramiento final Resultado_Analisis: {$insertados} filas insertadas\n", FILE_APPEND);
        }

        return $insertados;
    }

    private function validarServiciosConParametros($id_proyecto, $log_file = null) {
        $sql = "SELECT st.Id_Servicio,
                       st.Nombre
                FROM laboratorio.Proyecto_Detalle_Analisis pd
                INNER JOIN laboratorio.Producto_Servicio ps
                    ON ps.Id_Producto = pd.Id_Producto_Venta
                   AND ps.Activo = 1
                INNER JOIN laboratorio.Servicio_Tecnico st
                    ON st.Id_Servicio = ps.Id_Servicio
                   AND st.Activo = 1
                LEFT JOIN laboratorio.Parametro_Analisis pa
                    ON pa.Id_Servicio = st.Id_Servicio
                   AND pa.Activo = 1
                WHERE pd.Id_Proyecto = ?
                  AND pd.Activo = 1
                GROUP BY st.Id_Servicio, st.Nombre
                HAVING COUNT(pa.Id_Parametro) = 0";

        $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto));
        if ($stmt === false) {
            throw new Exception('Error al validar parametros por servicio: ' . print_r(sqlsrv_errors(), true));
        }

        $serviciosSinParametro = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idServicio = intval($row['Id_Servicio'] ?? 0);
            $nombreServicio = trim((string)($row['Nombre'] ?? ''));
            if ($idServicio > 0) {
                $serviciosSinParametro[] = ($nombreServicio !== '' ? $nombreServicio : 'Servicio') . ' (ID ' . $idServicio . ')';
            }
        }

        if (!empty($serviciosSinParametro)) {
            $listaServicios = implode(', ', $serviciosSinParametro);
            $mensaje = 'No se puede realizar la creacion masiva por que este servicio no tiene ligado al menos un parametro: ' . $listaServicios;

            if (!empty($log_file)) {
                file_put_contents($log_file, "✗ VALIDACION PREVIA FALLIDA: $mensaje\n", FILE_APPEND);
            }

            throw new Exception($mensaje);
        }

        if (!empty($log_file)) {
            file_put_contents($log_file, "✓ VALIDACION PREVIA: todos los servicios tienen al menos un parametro activo\n", FILE_APPEND);
        }
    }

    private function registrarConsumoReactivosInterno($idMuestraProducto, $usuarioId) {
        $idMuestraProducto = intval($idMuestraProducto);
        $usuarioId = intval($usuarioId);

        if ($idMuestraProducto <= 0) {
            return;
        }

        $stmtCheckConsumo = sqlsrv_query(
            $this->db,
            "SELECT COUNT(*) AS total
             FROM laboratorio.Consumo_Reaccion
             WHERE Id_Muestra_Producto = ? AND Activo = 1",
            [$idMuestraProducto]
        );
        if ($stmtCheckConsumo === false) {
            throw new Exception('Error al verificar consumo existente: ' . print_r(sqlsrv_errors(), true));
        }

        $rowCheckConsumo = sqlsrv_fetch_array($stmtCheckConsumo, SQLSRV_FETCH_ASSOC);
        if (intval($rowCheckConsumo['total'] ?? 0) > 0) {
            return;
        }

        $stmtProducto = sqlsrv_query(
            $this->db,
            "SELECT TOP 1 Id_Producto_Venta
             FROM laboratorio.Muestra_Producto
             WHERE Id_Muestra_Producto = ? AND Activo = 1",
            [$idMuestraProducto]
        );
        if ($stmtProducto === false) {
            throw new Exception('Error al obtener producto de la muestra: ' . print_r(sqlsrv_errors(), true));
        }

        $rowProducto = sqlsrv_fetch_array($stmtProducto, SQLSRV_FETCH_ASSOC);
        $idProductoVenta = intval($rowProducto['Id_Producto_Venta'] ?? 0);
        if ($idProductoVenta <= 0) {
            return;
        }

        $stmtProyecto = sqlsrv_query(
            $this->db,
            "SELECT TOP 1 ISNULL(m.Id_Proyecto, 0) AS Id_Proyecto
             FROM laboratorio.Muestra_Producto mp
             INNER JOIN laboratorio.Muestra_Lab m ON m.Id_Muestra = mp.Id_Muestra
             WHERE mp.Id_Muestra_Producto = ? AND mp.Activo = 1 AND m.Activo = 1",
            [$idMuestraProducto]
        );
        if ($stmtProyecto === false) {
            throw new Exception('Error al verificar proyecto de la muestra: ' . print_r(sqlsrv_errors(), true));
        }

        $rowProyecto = sqlsrv_fetch_array($stmtProyecto, SQLSRV_FETCH_ASSOC);
        $aplicarDescuentoReserva = intval($rowProyecto['Id_Proyecto'] ?? 0) > 0;

        $sqlReactivos = "WITH ServiciosProducto AS (
                            SELECT DISTINCT ps.Id_Servicio
                            FROM laboratorio.Producto_Servicio ps
                            WHERE ps.Id_Producto = ? AND ps.Activo = 1
                         ),
                         RecetaServicioUnica AS (
                            SELECT rs.Id_Servicio,
                                   rs.Id_Reactivo,
                                   MAX(CAST(ISNULL(rs.Cantidad_Necesaria, 0) AS DECIMAL(18,6))) AS Cantidad_Necesaria
                            FROM laboratorio.Receta_Servicio rs
                            INNER JOIN ServiciosProducto sp ON sp.Id_Servicio = rs.Id_Servicio
                            WHERE rs.Activo = 1
                            GROUP BY rs.Id_Servicio, rs.Id_Reactivo
                         )
                         SELECT rsu.Id_Reactivo,
                                SUM(rsu.Cantidad_Necesaria) AS Cantidad_Total
                         FROM RecetaServicioUnica rsu
                         GROUP BY rsu.Id_Reactivo
                         HAVING SUM(rsu.Cantidad_Necesaria) > 0";

        $stmtReactivos = sqlsrv_query($this->db, $sqlReactivos, [$idProductoVenta]);
        if ($stmtReactivos === false) {
            throw new Exception('Error al obtener receta de reactivos: ' . print_r(sqlsrv_errors(), true));
        }

        while ($rowReactivo = sqlsrv_fetch_array($stmtReactivos, SQLSRV_FETCH_ASSOC)) {
            $idReactivo = intval($rowReactivo['Id_Reactivo'] ?? 0);
            $cantidad = round(floatval($rowReactivo['Cantidad_Total'] ?? 0), 6);

            if ($idReactivo <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmtStock = sqlsrv_query(
                $this->db,
                "SELECT Nombre, ISNULL(Cantidad_Stock, 0) AS Stock
                 FROM laboratorio.Reactivo_Lab
                 WHERE Id_Reactivo = ? AND Activo = 1",
                [$idReactivo]
            );
            if ($stmtStock === false) {
                throw new Exception('Error al consultar stock de reactivo: ' . print_r(sqlsrv_errors(), true));
            }

            $rowStock = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            if (!$rowStock) {
                throw new Exception('Reactivo no encontrado para consumo: ' . $idReactivo);
            }

            $stockActual = floatval($rowStock['Stock'] ?? 0);
            if ($stockActual < $cantidad) {
                throw new Exception(
                    'Stock insuficiente en reactivo ' . trim((string)$rowStock['Nombre']) .
                    '. Disponible: ' . $stockActual . ', requerido: ' . $cantidad
                );
            }

            $saldoNuevo = round($stockActual - $cantidad, 4);
            $concepto = 'Consumo interno por muestra producto #' . $idMuestraProducto;

            $stmtMov = sqlsrv_query(
                $this->db,
                "SET NOCOUNT ON;
                 INSERT INTO laboratorio.Movimiento_Kardex
                 (Id_Reactivo, Fecha_Registro, Tipo_Movimiento, Cantidad, Concepto, Saldo_Resultante, Activo, Fecha_Creacion, Usuario_Creacion)
                 VALUES (?, GETDATE(), 'S', ?, ?, ?, 1, GETDATE(), ?);
                 SELECT CAST(SCOPE_IDENTITY() AS INT) AS id;",
                [$idReactivo, $cantidad, $concepto, $saldoNuevo, $usuarioId]
            );
            if ($stmtMov === false) {
                throw new Exception('Error al registrar movimiento kardex: ' . print_r(sqlsrv_errors(), true));
            }

            $rowMov = sqlsrv_fetch_array($stmtMov, SQLSRV_FETCH_ASSOC);
            $idMovimiento = intval($rowMov['id'] ?? 0);
            if ($idMovimiento <= 0) {
                throw new Exception('No se pudo obtener Id_Movimiento para consumo interno.');
            }

            $stmtConsumo = sqlsrv_query(
                $this->db,
                "INSERT INTO laboratorio.Consumo_Reaccion
                 (Id_Movimiento, Id_Muestra_Producto, Activo, Fecha_Creacion, Usuario_Creacion)
                 VALUES (?, ?, 1, GETDATE(), ?)",
                [$idMovimiento, $idMuestraProducto, $usuarioId]
            );
            if ($stmtConsumo === false) {
                throw new Exception('Error al registrar consumo_reaccion: ' . print_r(sqlsrv_errors(), true));
            }

            if ($aplicarDescuentoReserva) {
                $stmtUpdate = sqlsrv_query(
                    $this->db,
                    "UPDATE laboratorio.Reactivo_Lab
                     SET Cantidad_Stock = Cantidad_Stock - ?,
                         Cantidad_Reservada = CASE
                             WHEN ISNULL(Cantidad_Reservada, 0) >= ? THEN ISNULL(Cantidad_Reservada, 0) - ?
                             ELSE 0
                         END,
                         Fecha_Modificacion = GETDATE()
                     WHERE Id_Reactivo = ?",
                    [$cantidad, $cantidad, $cantidad, $idReactivo]
                );
            } else {
                $stmtUpdate = sqlsrv_query(
                    $this->db,
                    "UPDATE laboratorio.Reactivo_Lab
                     SET Cantidad_Stock = Cantidad_Stock - ?,
                         Fecha_Modificacion = GETDATE()
                     WHERE Id_Reactivo = ?",
                    [$cantidad, $idReactivo]
                );
            }
            if ($stmtUpdate === false) {
                throw new Exception('Error al actualizar stock de reactivo: ' . print_r(sqlsrv_errors(), true));
            }
        }
    }

    // ===== OBTENER PRIMER CLIENTE DISPONIBLE =====
    
    private function obtenerIdClienteProyecto() {
        $sql = "SELECT TOP 1 Id_Cliente
                FROM laboratorio.Cliente
                WHERE Activo = 1
                ORDER BY Id_Cliente ASC";
        
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error al buscar cliente: ' . print_r(sqlsrv_errors(), true));
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row && intval($row['Id_Cliente'] ?? 0) > 0) {
            return intval($row['Id_Cliente']);
        }

        throw new Exception('No se encontró ningún cliente activo en laboratorio.Cliente');
    }

    // ===== GUARDAR DETALLES DEL PROYECTO =====
    
    public function guardarDetalle($id_proyecto, $id_producto, $cantidad) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;

        $this->validarReservaReactivosDetalle($id_proyecto, $id_producto, $cantidad);
        
        // Verificar si ya existe
        $sqlCheck = "SELECT COUNT(*) as total FROM laboratorio.Proyecto_Detalle_Analisis 
                    WHERE Id_Proyecto = ? AND Id_Producto_Venta = ? AND Activo = 1";
        $stmtCheck = sqlsrv_query($this->db, $sqlCheck, array($id_proyecto, $id_producto));
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

        if ($rowCheck['total'] > 0) {
            // Actualizar cantidad - El trigger maneja la reserva
            $sql = "UPDATE laboratorio.Proyecto_Detalle_Analisis 
                   SET Cantidad_Planificada = ?, Fecha_Modificacion = GETDATE()
                   WHERE Id_Proyecto = ? AND Id_Producto_Venta = ? AND Activo = 1";
            $params = array($cantidad, $id_proyecto, $id_producto);
        } else {
            // Insertar nuevo detalle - El trigger TR_Reserva_Reactivos_Proyecto maneja la reserva
            $sql = "INSERT INTO laboratorio.Proyecto_Detalle_Analisis 
                   (Id_Proyecto, Id_Producto_Venta, Cantidad_Planificada, Usuario_Creacion, Activo, Fecha_Creacion)
                   VALUES (?, ?, ?, ?, 1, GETDATE())";
            $params = array($id_proyecto, $id_producto, $cantidad, $usuario_id);
        }

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error al guardar detalle: ' . print_r(sqlsrv_errors(), true));
        }
        
        return true;
    }

    public function eliminarDetalle($id_detalle) {
        // DELETE real (no soft delete) para activar trigger TR_Reserva_Reactivos_Proyecto
        // que liberará la cantidad reservada del reactivo
        $sql = "DELETE FROM laboratorio.Proyecto_Detalle_Analisis 
               WHERE Id_Detalle_Proyecto = ?";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_detalle));
        if ($stmt === false) {
            throw new Exception('Error al eliminar detalle: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    private function validarReservaReactivosDetalle($id_proyecto, $id_producto, $cantidad_nueva) {
        $id_proyecto = intval($id_proyecto);
        $id_producto = intval($id_producto);
        $cantidad_nueva = intval($cantidad_nueva);

        if ($id_proyecto <= 0 || $id_producto <= 0 || $cantidad_nueva <= 0) {
            throw new Exception('Datos inválidos para reservar reactivos del proyecto.');
        }

        $cantidad_actual = 0;
        $stmtActual = sqlsrv_query(
            $this->db,
            "SELECT TOP 1 Cantidad_Planificada
             FROM laboratorio.Proyecto_Detalle_Analisis
             WHERE Id_Proyecto = ? AND Id_Producto_Venta = ? AND Activo = 1",
            [$id_proyecto, $id_producto]
        );
        if ($stmtActual === false) {
            throw new Exception('Error al validar reserva actual del detalle: ' . print_r(sqlsrv_errors(), true));
        }
        $rowActual = sqlsrv_fetch_array($stmtActual, SQLSRV_FETCH_ASSOC);
        if ($rowActual) {
            $cantidad_actual = intval($rowActual['Cantidad_Planificada'] ?? 0);
        }

        $sqlDemanda = "WITH ServiciosProducto AS (
                           SELECT DISTINCT ps.Id_Servicio
                           FROM laboratorio.Producto_Servicio ps
                           WHERE ps.Id_Producto = ? AND ps.Activo = 1
                        ),
                        RecetaServicioUnica AS (
                           SELECT rs.Id_Servicio,
                                  rs.Id_Reactivo,
                                  MAX(CAST(ISNULL(rs.Cantidad_Necesaria, 0) AS DECIMAL(18,6))) AS Cantidad_Necesaria
                           FROM laboratorio.Receta_Servicio rs
                           INNER JOIN ServiciosProducto sp ON sp.Id_Servicio = rs.Id_Servicio
                           WHERE rs.Activo = 1
                           GROUP BY rs.Id_Servicio, rs.Id_Reactivo
                        )
                        SELECT rsu.Id_Reactivo,
                               SUM(rsu.Cantidad_Necesaria) AS Cantidad_Por_Muestra
                        FROM RecetaServicioUnica rsu
                        GROUP BY rsu.Id_Reactivo
                        HAVING SUM(rsu.Cantidad_Necesaria) > 0";

        $stmtDemanda = sqlsrv_query($this->db, $sqlDemanda, [$id_producto]);
        if ($stmtDemanda === false) {
            throw new Exception('Error al calcular demanda de reactivos del producto: ' . print_r(sqlsrv_errors(), true));
        }

        $faltantes = [];
        while ($rowDem = sqlsrv_fetch_array($stmtDemanda, SQLSRV_FETCH_ASSOC)) {
            $idReactivo = intval($rowDem['Id_Reactivo'] ?? 0);
            $cantPorMuestra = floatval($rowDem['Cantidad_Por_Muestra'] ?? 0);
            if ($idReactivo <= 0 || $cantPorMuestra <= 0) {
                continue;
            }

            $requeridoNuevo = round($cantPorMuestra * $cantidad_nueva, 6);
            $requeridoActual = round($cantPorMuestra * $cantidad_actual, 6);

            $stmtStock = sqlsrv_query(
                $this->db,
                "SELECT Nombre,
                        CAST(ISNULL(Cantidad_Stock, 0) AS DECIMAL(18,6)) AS Stock,
                        CAST(ISNULL(Cantidad_Reservada, 0) AS DECIMAL(18,6)) AS Reservada
                 FROM laboratorio.Reactivo_Lab
                 WHERE Id_Reactivo = ? AND Activo = 1",
                [$idReactivo]
            );
            if ($stmtStock === false) {
                throw new Exception('Error al consultar stock para reserva de proyecto: ' . print_r(sqlsrv_errors(), true));
            }

            $rowStock = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            if (!$rowStock) {
                throw new Exception('Reactivo no encontrado para reserva: ' . $idReactivo);
            }

            $stock = floatval($rowStock['Stock'] ?? 0);
            $reservada = floatval($rowStock['Reservada'] ?? 0);
            $disponibleEfectivo = round(($stock - $reservada) + $requeridoActual, 6);

            if ($requeridoNuevo > $disponibleEfectivo + 0.000001) {
                $faltantes[] = trim((string)($rowStock['Nombre'] ?? ('Reactivo #' . $idReactivo)))
                    . ' (disponible para reservar: ' . round($disponibleEfectivo, 4)
                    . ', requerido: ' . round($requeridoNuevo, 4) . ')';
            }
        }

        if (!empty($faltantes)) {
            throw new Exception(
                'No se puede reservar más reactivos de los disponibles para este proyecto. Ajuste la cantidad planificada o reponga stock. Detalle: '
                . implode('; ', $faltantes)
            );
        }
    }

    // ===== OBTENER MUESTRAS DEL PROYECTO =====
    
    public function obtenerMuestrasProyecto($id_proyecto) {
        $sql = "SELECT 
                    m.*,
                    ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden
                FROM laboratorio.Muestra_Lab m
                WHERE m.Id_Proyecto = ? AND m.Activo = 1
                ORDER BY m.Id_Muestra";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto));
        if ($stmt === false) {
            throw new Exception('Error en obtenerMuestrasProyecto: ' . print_r(sqlsrv_errors(), true));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ===== ELIMINAR PROYECTO =====
    
    public function eliminar($id) {
        // PRIMERO: Eliminar detalles (DELETE real) para activar trigger que libera reactivos
        $sqlDetalles = "DELETE FROM laboratorio.Proyecto_Detalle_Analisis 
                       WHERE Id_Proyecto = ?";
        
        $stmtDetalles = sqlsrv_query($this->db, $sqlDetalles, array($id));
        if ($stmtDetalles === false) {
            throw new Exception('Error al eliminar detalles del proyecto: ' . print_r(sqlsrv_errors(), true));
        }

        // SEGUNDO: Desactivar proyecto (soft delete)
        $sql = "UPDATE laboratorio.Proyecto_Monitoreo 
               SET Activo = 0, Fecha_Modificacion = GETDATE()
               WHERE Id_Proyecto = ?";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error al eliminar proyecto: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }
}
?>
