<?php

class MuestraModel {
    
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // ===== OBTENER MUESTRAS =====
    
    public function obtenerTodos() {
        $sql = "SELECT m.*, 
                       CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                       CONCAT(m.Eje_X, ', ', m.Eje_Y) AS Ubicacion
                FROM laboratorio.Muestra_Lab m 
                INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente 
                WHERE m.Activo = 1 
                ORDER BY m.Fecha_Creacion DESC";
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
        $sql = "SELECT m.*, 
                       CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                       CONCAT(m.Eje_X, ', ', m.Eje_Y) AS Ubicacion
                FROM laboratorio.Muestra_Lab m 
                INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente 
                WHERE m.Id_Muestra = ? AND m.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorId: ' . print_r(sqlsrv_errors(), true));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function obtenerPorEstado($estado, $offset = 0, $limit = 10, $tipoServicio = '') {
        $tipoServicio = strtolower(trim((string)$tipoServicio));
        $filtrarTipo = ($tipoServicio === 'interno' || $tipoServicio === 'externo');

        $sql = "SELECT m.*, 
                       CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                       CONCAT(m.Eje_X, ', ', m.Eje_Y) AS Ubicacion,
                       CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo' 
                            WHEN da.Id_Muestra IS NOT NULL THEN 'Agua' 
                            ELSE 'Sin clasificar' END AS TipoMuestra
                FROM laboratorio.Muestra_Lab m 
                INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente 
                LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
                LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
                                WHERE m.Activo = 1
                                    AND m.Estado = ?
                                    AND m.Id_Proyecto IS NULL
                                    AND NOT EXISTS (
                                                SELECT 1
                                                FROM laboratorio.Muestra_Bitacora mbx
                                                WHERE mbx.Id_Muestra = m.Id_Muestra
                                                    AND mbx.Muestra_Original IS NOT NULL
                                    )";

        if ($filtrarTipo) {
            $sql .= " AND LOWER(ISNULL(m.Tipo_Servicio, '')) = ?";
        }

        $sql .= " ORDER BY m.Id_Muestra DESC 
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

        $params = [$estado];
        if ($filtrarTipo) {
            $params[] = $tipoServicio;
        }
        $params[] = $offset;
        $params[] = $limit;

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorEstado: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function contarPorEstado($estado, $tipoServicio = '') {
        $tipoServicio = strtolower(trim((string)$tipoServicio));
        $filtrarTipo = ($tipoServicio === 'interno' || $tipoServicio === 'externo');

        $sql = "SELECT COUNT(*) as total FROM laboratorio.Muestra_Lab 
                                WHERE Activo = 1
                                    AND Estado = ?
                                    AND Id_Proyecto IS NULL
                                    AND NOT EXISTS (
                                                SELECT 1
                                                FROM laboratorio.Muestra_Bitacora mbx
                                                WHERE mbx.Id_Muestra = laboratorio.Muestra_Lab.Id_Muestra
                                                    AND mbx.Muestra_Original IS NOT NULL
                                    )";

        $params = [$estado];
        if ($filtrarTipo) {
            $sql .= " AND LOWER(ISNULL(Tipo_Servicio, '')) = ?";
            $params[] = $tipoServicio;
        }

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en contarPorEstado: ' . print_r(sqlsrv_errors(), true));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // ===== OBTENER MUESTRAS DEL PROYECTO =====

    public function obtenerMuestrasPorProyecto($id_proyecto, $estado = null, $offset = 0, $limit = 10) {
        $sql = "SELECT m.*, 
                       CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                       CONCAT(m.Eje_X, ', ', m.Eje_Y) AS Ubicacion,
                       CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo' 
                            WHEN da.Id_Muestra IS NOT NULL THEN 'Agua' 
                            ELSE 'Sin clasificar' END AS TipoMuestra
                FROM laboratorio.Muestra_Lab m 
                LEFT JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente 
                LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
                LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
                WHERE m.Activo = 1 AND m.Id_Proyecto = ?";
        
        if ($estado) {
            $sql .= " AND m.Estado = ?";
            $params = array($id_proyecto, $estado);
        } else {
            $params = array($id_proyecto);
        }
        
        $sql .= " ORDER BY m.Id_Muestra DESC 
                 OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        
        $params[] = $offset;
        $params[] = $limit;
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerMuestrasPorProyecto: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function contarMuestrasPorProyecto($id_proyecto, $estado = null) {
        $sql = "SELECT COUNT(*) as total FROM laboratorio.Muestra_Lab 
                WHERE Activo = 1 AND Id_Proyecto = ?";
        
        if ($estado) {
            $sql .= " AND Estado = ?";
            $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto, $estado));
        } else {
            $stmt = sqlsrv_query($this->db, $sql, array($id_proyecto));
        }
        
        if ($stmt === false) {
            throw new Exception('Error en contarMuestrasPorProyecto: ' . print_r(sqlsrv_errors(), true));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // ===== GUARDAR MUESTRA =====

    public function guardar($datos) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;

        if (empty($datos['Id_Muestra'])) {
            // INSERT - Nueva muestra
            // Determinar estado por defecto: si tiene Id_Proyecto, usar "En Análisis", sino "Por Recepcionar"
            $estado_defecto = (!empty($datos['Id_Proyecto'])) ? 'En Análisis' : 'Por Recepcionar';
            
                $sql = "INSERT INTO laboratorio.Muestra_Lab 
                    (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Valle, Eje_X, Eje_Y, 
                     Fecha_Recepcion, Estado, Tipo_Servicio, Observacion_Muestra, Ruta_Imagen, 
                     Id_Jefe_Lab, Es_Control_Calidad, Fecha_Toma, 
                     Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, GETDATE()); 
                    SELECT SCOPE_IDENTITY() AS id;";
            
            $params = array(
                $datos['Id_Cliente'] ?? null,
                $datos['Id_Receptor'] ?? null,
                $datos['Id_Especialista'] ?? null,
                $datos['Id_Proyecto'] ?? null,
                $datos['Valle'] ?? null,
                $datos['Eje_X'] ?? null,
                $datos['Eje_Y'] ?? null,
                $datos['Fecha_Recepcion'] ?? null,
                $datos['Estado'] ?? $estado_defecto,
                $datos['Tipo_Servicio'] ?? null,
                $datos['Observacion_Muestra'] ?? null,
                $datos['Ruta_Imagen'] ?? null,
                $datos['Id_Jefe_Lab'] ?? null,
                $datos['Es_Control_Calidad'] ?? 0,
                $datos['Fecha_Toma'] ?? null,
                $usuario_id
            );

            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en INSERT: ' . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'] ?? null;
        } else {
            // UPDATE - Actualizar muestra existente
            // Determinar estado por defecto: si tiene Id_Proyecto, usar "En Análisis", sino "Por Recepcionar"
            $estado_defecto = (!empty($datos['Id_Proyecto'])) ? 'En Análisis' : 'Por Recepcionar';
            
            $sql = "UPDATE laboratorio.Muestra_Lab 
                    SET Id_Cliente=?, Id_Receptor=?, Id_Especialista=?, Id_Proyecto=?, 
                        Valle=?, Eje_X=?, Eje_Y=?, Estado=?, Tipo_Servicio=?, 
                        Observacion_Muestra=?, Ruta_Imagen=?, Id_Jefe_Lab=?,
                        Fecha_Modificacion=GETDATE() 
                    WHERE Id_Muestra=? AND Activo=1";
            
            $params = array(
                $datos['Id_Cliente'] ?? null,
                $datos['Id_Receptor'] ?? null,
                $datos['Id_Especialista'] ?? null,
                $datos['Id_Proyecto'] ?? null,
                $datos['Valle'] ?? null,
                $datos['Eje_X'] ?? null,
                $datos['Eje_Y'] ?? null,
                $datos['Estado'] ?? $estado_defecto,
                $datos['Tipo_Servicio'] ?? null,
                $datos['Observacion_Muestra'] ?? null,
                $datos['Ruta_Imagen'] ?? null,
                $datos['Id_Jefe_Lab'] ?? null,
                $datos['Id_Muestra']
            );

            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en UPDATE: ' . print_r(sqlsrv_errors(), true));
            }
            return $datos['Id_Muestra'];
        }
    }

    // ===== ELIMINAR MUESTRA =====

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Muestra_Lab SET Activo = 0, Fecha_Modificacion = GETDATE() 
                WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en DELETE: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    // ===== TRANSICIONES DE ESTADO =====

    public function confirmarRecepcion($id, $usuario_id, $pasa, $tipo_servicio, $observacion, $checklist) {
        $estadoDestino = $pasa ? 'Recepcionado' : 'Pendiente';

        $observacionTexto = trim((string)$observacion);
        $checklistJson = json_encode([
            'pasa' => (bool)$pasa,
            'items' => is_array($checklist) ? $checklist : [],
            'fecha' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);

        $observacionFinal = $observacionTexto;
        if ($checklistJson !== false) {
            $bloqueRecepcion = '[RECEPCION] ' . $checklistJson;
            $observacionFinal = $observacionFinal === '' ? $bloqueRecepcion : ($observacionFinal . PHP_EOL . $bloqueRecepcion);
        }

        $sql = "UPDATE laboratorio.Muestra_Lab
                SET Estado = ?,
                    Tipo_Servicio = ?,
                    Id_Receptor = ?,
                    Fecha_Recepcion = GETDATE(),
                    Observacion_Muestra = ?,
                    Fecha_Modificacion = GETDATE()
                WHERE Id_Muestra = ? AND Activo = 1";

        $params = array($estadoDestino, $tipo_servicio, $usuario_id, $observacionFinal === '' ? null : $observacionFinal, $id);
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en confirmarRecepcion: ' . print_r(sqlsrv_errors(), true));
        }

        return [
            'estado' => $estadoDestino
        ];
    }

    public function recepcionarMuestra($id, $usuario_id) {
        $sql = "UPDATE laboratorio.Muestra_Lab 
                SET Estado = 'Recepcionado', 
                    Id_Receptor = ?, 
                    Fecha_Recepcion = GETDATE(), 
                    Fecha_Modificacion = GETDATE() 
                WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($usuario_id, $id));
        if ($stmt === false) {
            throw new Exception('Error en recepcionarMuestra: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function iniciarAnalisis($id, $usuario_id) {
        $sql = "UPDATE laboratorio.Muestra_Lab 
                SET Estado = 'En Analisis', 
                    Id_Especialista = ?, 
                    Fecha_Analisis = ISNULL(Fecha_Analisis, GETDATE()),
                    Fecha_Modificacion = GETDATE() 
                WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($usuario_id, $id));
        if ($stmt === false) {
            throw new Exception('Error en iniciarAnalisis: ' . print_r(sqlsrv_errors(), true));
        }

        $sqlSolicitud = "UPDATE laboratorio.Solicitud_Analisis
                         SET Estado = 'En Análisis',
                             Fecha_Modificacion = GETDATE()
                         WHERE Id_Muestra = ?
                           AND Activo = 1
                           AND Estado <> 'Finalizado'";
        $stmtSolicitud = sqlsrv_query($this->db, $sqlSolicitud, array($id));
        if ($stmtSolicitud === false) {
            throw new Exception('Error en iniciarAnalisis (solicitudes): ' . print_r(sqlsrv_errors(), true));
        }

        $this->registrarConsumoInternoPorMuestra($id, $usuario_id);

        return true;
    }

    public function iniciarAnalisisDesdeMuestra($id_muestra, $usuario_id, $iniciarTodosAgricultor = false) {
        $muestraBase = $this->obtenerPorId($id_muestra);
        if (!$muestraBase) {
            throw new Exception('Muestra no encontrada');
        }

        $idCliente = intval($muestraBase['Id_Cliente'] ?? 0);
        if ($idCliente <= 0) {
            throw new Exception('No se encontró agricultor asociado a la muestra');
        }

                if ($iniciarTodosAgricultor) {
            $sql = "SELECT Id_Muestra
                    FROM laboratorio.Muestra_Lab
                    WHERE Activo = 1
                      AND Id_Proyecto IS NULL
                      AND Id_Cliente = ?
                                            AND Estado = 'Recepcionado'";
            $stmt = sqlsrv_query($this->db, $sql, array($idCliente));
        } else {
            $sql = "SELECT Id_Muestra
                    FROM laboratorio.Muestra_Lab
                    WHERE Activo = 1
                      AND Id_Muestra = ?
                      AND Id_Proyecto IS NULL
                                            AND Estado = 'Recepcionado'";
            $stmt = sqlsrv_query($this->db, $sql, array($id_muestra));
        }

        if ($stmt === false) {
            throw new Exception('Error al obtener muestras para iniciar análisis: ' . print_r(sqlsrv_errors(), true));
        }

        $muestrasObjetivo = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $muestrasObjetivo[] = intval($row['Id_Muestra']);
        }

        if (empty($muestrasObjetivo)) {
            $estadoBase = strtolower(trim((string)($muestraBase['Estado'] ?? '')));
            if ($iniciarTodosAgricultor && $estadoBase === 'en analisis') {
                return [
                    'id_cliente' => $idCliente,
                    'muestras_actualizadas' => 0,
                    'solicitudes_creadas' => 0,
                    'resultados_creados' => 0
                ];
            }
            throw new Exception('No hay muestras en estado Recepcionado para iniciar análisis');
        }

        $actualizadas = 0;
        $solicitudesCreadas = 0;
        $resultadosCreados = 0;

        foreach ($muestrasObjetivo as $idObjetivo) {
            $sqlUpdate = "UPDATE laboratorio.Muestra_Lab
                          SET Estado = 'En Analisis',
                              Id_Especialista = ?,
                              Fecha_Analisis = ISNULL(Fecha_Analisis, GETDATE()),
                              Fecha_Modificacion = GETDATE()
                          WHERE Id_Muestra = ? AND Activo = 1";
            $stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, array($usuario_id, $idObjetivo));
            if ($stmtUpdate === false) {
                throw new Exception('Error al actualizar estado de muestra: ' . print_r(sqlsrv_errors(), true));
            }

            $sqlUpdateSolicitudes = "UPDATE laboratorio.Solicitud_Analisis
                                     SET Estado = 'En Análisis',
                                         Fecha_Modificacion = GETDATE()
                                     WHERE Id_Muestra = ?
                                       AND Activo = 1
                                       AND Estado <> 'Finalizado'";
            $stmtUpdateSolicitudes = sqlsrv_query($this->db, $sqlUpdateSolicitudes, array($idObjetivo));
            if ($stmtUpdateSolicitudes === false) {
                throw new Exception('Error al actualizar solicitudes a En Análisis: ' . print_r(sqlsrv_errors(), true));
            }

            $actualizadas++;
            $creacion = $this->asegurarSolicitudesYResultadosPorMuestra($idObjetivo, $usuario_id);
            $solicitudesCreadas += intval($creacion['solicitudes']);
            $resultadosCreados += intval($creacion['resultados']);

            // Descuenta reactivos de forma idempotente para muestras normales,
            // incluyendo creación individual.
            $this->registrarConsumoInternoPorMuestra($idObjetivo, $usuario_id);
        }

        return [
            'id_cliente' => $idCliente,
            'muestras_actualizadas' => $actualizadas,
            'solicitudes_creadas' => $solicitudesCreadas,
            'resultados_creados' => $resultadosCreados
        ];
    }

    private function registrarConsumoInternoPorMuestra($idMuestra, $usuarioId) {
        $idMuestra = intval($idMuestra);
        $usuarioId = intval($usuarioId);
        if ($idMuestra <= 0 || $usuarioId <= 0) {
            return;
        }

        $stmtMP = sqlsrv_query(
            $this->db,
            "SELECT Id_Muestra_Producto
             FROM laboratorio.Muestra_Producto
             WHERE Id_Muestra = ? AND Activo = 1",
            [$idMuestra]
        );
        if ($stmtMP === false) {
            throw new Exception('Error al obtener muestra_producto para consumo interno: ' . print_r(sqlsrv_errors(), true));
        }

        while ($rowMP = sqlsrv_fetch_array($stmtMP, SQLSRV_FETCH_ASSOC)) {
            $idMuestraProducto = intval($rowMP['Id_Muestra_Producto'] ?? 0);
            if ($idMuestraProducto > 0) {
                $this->registrarConsumoReactivosInterno($idMuestraProducto, $usuarioId);
            }
        }
    }

    private function asegurarSolicitudesYResultadosPorMuestra($id_muestra, $usuario_id) {
        if ($this->esMuestraFijaBitacora($id_muestra)) {
            return [
                'solicitudes' => 0,
                'resultados' => 0
            ];
        }

        $sqlServicios = "SELECT DISTINCT ps.Id_Servicio
                        FROM laboratorio.Muestra_Producto mp
                        INNER JOIN laboratorio.Producto_Servicio ps ON ps.Id_Producto = mp.Id_Producto_Venta AND ps.Activo = 1
                        WHERE mp.Id_Muestra = ? AND mp.Activo = 1";
        $stmtServicios = sqlsrv_query($this->db, $sqlServicios, array($id_muestra));
        if ($stmtServicios === false) {
            throw new Exception('Error al obtener servicios comprados: ' . print_r(sqlsrv_errors(), true));
        }

        $servicios = [];
        while ($rowServ = sqlsrv_fetch_array($stmtServicios, SQLSRV_FETCH_ASSOC)) {
            $idServicio = intval($rowServ['Id_Servicio'] ?? 0);
            if ($idServicio > 0) {
                $servicios[] = $idServicio;
            }
        }

        if (empty($servicios)) {
            $sqlServiciosBitacora = "SELECT DISTINCT ps.Id_Servicio
                                    FROM laboratorio.Muestra_Bitacora mb
                                    INNER JOIN laboratorio.Producto_Servicio ps ON ps.Id_Producto = mb.Id_Producto_Venta AND ps.Activo = 1
                                    WHERE mb.Id_Muestra = ?
                                      AND mb.Id_Producto_Venta IS NOT NULL";
            $stmtServiciosBitacora = sqlsrv_query($this->db, $sqlServiciosBitacora, array($id_muestra));
            if ($stmtServiciosBitacora === false) {
                throw new Exception('Error al obtener servicios desde bitácora: ' . print_r(sqlsrv_errors(), true));
            }

            while ($rowServBit = sqlsrv_fetch_array($stmtServiciosBitacora, SQLSRV_FETCH_ASSOC)) {
                $idServicioBit = intval($rowServBit['Id_Servicio'] ?? 0);
                if ($idServicioBit > 0) {
                    $servicios[] = $idServicioBit;
                }
            }
        }

        if (empty($servicios)) {
            throw new Exception('La muestra no tiene servicios comprados asociados');
        }

        $totalSolicitudes = 0;
        $totalResultados = 0;

        foreach ($servicios as $id_servicio) {
            $id_solicitud = 0;

            $sqlSolicitudExistente = "SELECT TOP 1 Id_Solicitud_Analisis
                                     FROM laboratorio.Solicitud_Analisis
                                     WHERE Id_Muestra = ? AND Id_Servicio = ? AND Activo = 1
                                     ORDER BY Id_Solicitud_Analisis DESC";
            $stmtSolicitudExistente = sqlsrv_query($this->db, $sqlSolicitudExistente, array($id_muestra, $id_servicio));
            if ($stmtSolicitudExistente === false) {
                throw new Exception('Error al validar solicitud existente: ' . print_r(sqlsrv_errors(), true));
            }

            $rowSol = sqlsrv_fetch_array($stmtSolicitudExistente, SQLSRV_FETCH_ASSOC);
            if ($rowSol) {
                $id_solicitud = intval($rowSol['Id_Solicitud_Analisis']);

                $sqlEstadoSolicitud = "UPDATE laboratorio.Solicitud_Analisis
                                      SET Estado = 'En Análisis',
                                          Fecha_Modificacion = GETDATE()
                                      WHERE Id_Solicitud_Analisis = ?";
                $stmtEstadoSolicitud = sqlsrv_query($this->db, $sqlEstadoSolicitud, array($id_solicitud));
                if ($stmtEstadoSolicitud === false) {
                    throw new Exception('Error al actualizar estado de solicitud: ' . print_r(sqlsrv_errors(), true));
                }
            } else {
                $sqlCrearSolicitud = "INSERT INTO laboratorio.Solicitud_Analisis
                                     (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion)
                                     VALUES (?, ?, 'En Análisis', GETDATE(), ?, 1, GETDATE());
                                     SELECT SCOPE_IDENTITY() AS id;";
                $stmtCrearSolicitud = sqlsrv_query($this->db, $sqlCrearSolicitud, array($id_muestra, $id_servicio, $usuario_id));
                if ($stmtCrearSolicitud === false) {
                    throw new Exception('Error al crear solicitud de análisis: ' . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_next_result($stmtCrearSolicitud);
                $rowNuevaSolicitud = sqlsrv_fetch_array($stmtCrearSolicitud, SQLSRV_FETCH_ASSOC);
                $id_solicitud = intval($rowNuevaSolicitud['id'] ?? 0);
                $totalSolicitudes++;
            }

            if ($id_solicitud <= 0) {
                throw new Exception('No se pudo resolver Id_Solicitud_Analisis');
            }

                        $sqlParametros = "SELECT Id_Parametro
                                                         FROM laboratorio.Parametro_Analisis
                                                         WHERE Activo = 1
                                                             AND Id_Servicio = ?
                                                         ORDER BY Id_Parametro";
                        $stmtParametros = sqlsrv_query($this->db, $sqlParametros, array($id_servicio));
            if ($stmtParametros === false) {
                throw new Exception('Error al obtener parámetros por servicio: ' . print_r(sqlsrv_errors(), true));
            }

            while ($rowParam = sqlsrv_fetch_array($stmtParametros, SQLSRV_FETCH_ASSOC)) {
                $id_parametro = intval($rowParam['Id_Parametro'] ?? 0);
                if ($id_parametro <= 0) {
                    continue;
                }

                $sqlExisteResultado = "SELECT COUNT(*) AS total
                                       FROM laboratorio.Resultado_Analisis
                                       WHERE Id_Solicitud_Analisis = ? AND Id_Parametro = ? AND Activo = 1";
                $stmtExisteResultado = sqlsrv_query($this->db, $sqlExisteResultado, array($id_solicitud, $id_parametro));
                if ($stmtExisteResultado === false) {
                    throw new Exception('Error al validar resultado existente: ' . print_r(sqlsrv_errors(), true));
                }

                $rowExisteResultado = sqlsrv_fetch_array($stmtExisteResultado, SQLSRV_FETCH_ASSOC);
                $existe = intval($rowExisteResultado['total'] ?? 0) > 0;
                if ($existe) {
                    continue;
                }

                $sqlNormativa = "SELECT TOP 1 Id_Normativa
                                FROM laboratorio.Limite_Legal
                                WHERE Id_Parametro = ? AND Activo = 1";
                $stmtNormativa = sqlsrv_query($this->db, $sqlNormativa, array($id_parametro));
                if ($stmtNormativa === false) {
                    throw new Exception('Error al consultar normativa: ' . print_r(sqlsrv_errors(), true));
                }
                $rowNormativa = sqlsrv_fetch_array($stmtNormativa, SQLSRV_FETCH_ASSOC);
                $id_normativa = !empty($rowNormativa['Id_Normativa']) ? intval($rowNormativa['Id_Normativa']) : null;

                $sqlCrearResultado = "INSERT INTO laboratorio.Resultado_Analisis
                                     (Id_Solicitud_Analisis, Id_Parametro, Id_Normativa, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                                     VALUES (?, ?, ?, NULL, ?, 1, GETDATE())";
                $stmtCrearResultado = sqlsrv_query($this->db, $sqlCrearResultado, array($id_solicitud, $id_parametro, $id_normativa, $usuario_id));
                if ($stmtCrearResultado === false) {
                    throw new Exception('Error al crear resultado en blanco: ' . print_r(sqlsrv_errors(), true));
                }

                $totalResultados++;
            }
        }

        return [
            'solicitudes' => $totalSolicitudes,
            'resultados' => $totalResultados
        ];
    }

    public function completarAnalisis($id) {
        $sql = "UPDATE laboratorio.Muestra_Lab 
                SET Estado = 'Por Firmar', 
                    Fecha_Analisis = GETDATE(), 
                    Fecha_Modificacion = GETDATE() 
                WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en completarAnalisis: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function validarMuestra($id, $usuario_id) {
        $sql = "UPDATE laboratorio.Muestra_Lab 
                SET Estado = 'Finalizado', 
                    Id_Jefe_Lab = ?, 
                    Fecha_Validacion = GETDATE(), 
                    Fecha_Modificacion = GETDATE() 
                WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($usuario_id, $id));
        if ($stmt === false) {
            throw new Exception('Error en validarMuestra: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function obtenerMuestrasPorFirmarAgricultor($id_cliente) {
        $sql = "SELECT Id_Muestra
                FROM laboratorio.Muestra_Lab
                WHERE Activo = 1
                  AND Id_Proyecto IS NULL
                  AND Id_Cliente = ?
                  AND Estado = 'Por Firmar'
                ORDER BY Id_Muestra ASC";

        $stmt = sqlsrv_query($this->db, $sql, array($id_cliente));
        if ($stmt === false) {
            throw new Exception('Error en obtenerMuestrasPorFirmarAgricultor: ' . print_r(sqlsrv_errors(), true));
        }

        $ids = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $ids[] = intval($row['Id_Muestra'] ?? 0);
        }
        return array_values(array_filter($ids));
    }

    public function firmarMuestrasPorAgricultor($id_cliente, $usuario_id) {
        $sqlIds = "SELECT Id_Muestra
                   FROM laboratorio.Muestra_Lab
                   WHERE Activo = 1
                     AND Id_Proyecto IS NULL
                     AND Id_Cliente = ?
                     AND Estado = 'Por Firmar'";
        $stmtIds = sqlsrv_query($this->db, $sqlIds, array($id_cliente));
        if ($stmtIds === false) {
            throw new Exception('Error en firmarMuestrasPorAgricultor (ids): ' . print_r(sqlsrv_errors(), true));
        }

        $idsMuestras = [];
        while ($rowId = sqlsrv_fetch_array($stmtIds, SQLSRV_FETCH_ASSOC)) {
            $idMuestra = intval($rowId['Id_Muestra'] ?? 0);
            if ($idMuestra > 0) {
                $idsMuestras[] = $idMuestra;
            }
        }

        if (empty($idsMuestras)) {
            return 0;
        }

        $sql = "UPDATE laboratorio.Muestra_Lab
                SET Estado = 'Finalizado',
                    Id_Jefe_Lab = ?,
                    Fecha_Validacion = GETDATE(),
                    Fecha_Modificacion = GETDATE()
                WHERE Activo = 1
                  AND Id_Proyecto IS NULL
                  AND Id_Cliente = ?
                  AND Estado = 'Por Firmar'";
        $stmt = sqlsrv_query($this->db, $sql, array($usuario_id, $id_cliente));
        if ($stmt === false) {
            throw new Exception('Error en firmarMuestrasPorAgricultor (update): ' . print_r(sqlsrv_errors(), true));
        }

        return count($idsMuestras);
    }

    public function registrarResiduosAutomaticosPorSolicitud($id_solicitud, $usuario_id, $validarEstadoFinalizado = true) {
        $id_solicitud = intval($id_solicitud);
        $usuario_id = intval($usuario_id);

        if ($id_solicitud <= 0) {
            return 0;
        }

        $sqlSolicitud = "SELECT Id_Servicio, Estado
                         FROM laboratorio.Solicitud_Analisis
                         WHERE Id_Solicitud_Analisis = ? AND Activo = 1";
        $stmtSolicitud = sqlsrv_query($this->db, $sqlSolicitud, array($id_solicitud));
        if ($stmtSolicitud === false) {
            throw new Exception('Error al obtener solicitud para residuos: ' . print_r(sqlsrv_errors(), true));
        }

        $rowSolicitud = sqlsrv_fetch_array($stmtSolicitud, SQLSRV_FETCH_ASSOC);
        if (!$rowSolicitud) {
            return 0;
        }

        $id_servicio = intval($rowSolicitud['Id_Servicio'] ?? 0);
        $estado_solicitud = strtolower(trim((string)($rowSolicitud['Estado'] ?? '')));

        if ($id_servicio <= 0) {
            return 0;
        }

        if ($validarEstadoFinalizado && $estado_solicitud !== 'finalizado') {
            return 0;
        }

        $sqlResiduos = "SELECT srd.Id_Residuo_Cat,
                               CAST(ISNULL(srd.Cantidad_Estimada_Por_Muestra, 0) AS DECIMAL(18,4)) AS Cantidad
                        FROM laboratorio.Servicio_Residuo_Def srd
                        WHERE srd.Id_Servicio = ?
                          AND srd.Activo = 1
                          AND ISNULL(srd.Cantidad_Estimada_Por_Muestra, 0) > 0";
        $stmtResiduos = sqlsrv_query($this->db, $sqlResiduos, array($id_servicio));
        if ($stmtResiduos === false) {
            throw new Exception('Error al obtener residuos automáticos por servicio: ' . print_r(sqlsrv_errors(), true));
        }

        $residuos = [];
        while ($rowRes = sqlsrv_fetch_array($stmtResiduos, SQLSRV_FETCH_ASSOC)) {
            $idResiduo = intval($rowRes['Id_Residuo_Cat'] ?? 0);
            $cantidad = round(floatval($rowRes['Cantidad'] ?? 0), 4);
            if ($idResiduo > 0 && $cantidad > 0) {
                $residuos[] = ['id' => $idResiduo, 'cantidad' => $cantidad];
            }
        }

        if (empty($residuos)) {
            return 0;
        }

        $mes = intval(date('n'));
        $anio = intval(date('Y'));

        $sqlCab = "SELECT TOP 1 Id_Registro_Res
                   FROM laboratorio.Registro_Residuos_Log
                   WHERE Mes = ? AND Anio = ? AND Activo = 1
                   ORDER BY Id_Registro_Res DESC";
        $stmtCab = sqlsrv_query($this->db, $sqlCab, array($mes, $anio));
        if ($stmtCab === false) {
            throw new Exception('Error al buscar cabecera de residuos automática: ' . print_r(sqlsrv_errors(), true));
        }

        $rowCab = sqlsrv_fetch_array($stmtCab, SQLSRV_FETCH_ASSOC);
        $idRegistroRes = intval($rowCab['Id_Registro_Res'] ?? 0);

        if ($idRegistroRes <= 0) {
            $sqlNewCab = "SET NOCOUNT ON;
                          INSERT INTO laboratorio.Registro_Residuos_Log
                          (Mes, Anio, Ubicacion, Id_Responsable, Codigo_SST, Activo, Fecha_Creacion, Usuario_Creacion)
                          VALUES (?, ?, 'LAB-GENERAL', ?, 'SST-16', 1, GETDATE(), ?);
                          SELECT CAST(SCOPE_IDENTITY() AS INT) AS id;";
            $stmtNewCab = sqlsrv_query($this->db, $sqlNewCab, array($mes, $anio, $usuario_id, $usuario_id));
            if ($stmtNewCab === false) {
                throw new Exception('Error al crear cabecera de residuos automática: ' . print_r(sqlsrv_errors(), true));
            }

            $rowNewCab = sqlsrv_fetch_array($stmtNewCab, SQLSRV_FETCH_ASSOC);
            $idRegistroRes = intval($rowNewCab['id'] ?? 0);
            if ($idRegistroRes <= 0) {
                throw new Exception('No se pudo obtener Id_Registro_Res en registro automático de residuos.');
            }
        }

        $registrados = 0;
        foreach ($residuos as $r) {
            $sqlInsRes = "INSERT INTO laboratorio.Detalle_Residuos_Log
                          (Id_Registro_Res, Id_Residuo_Cat, Fecha_Dia, Peso_Valor, Activo, Fecha_Creacion, Usuario_Creacion)
                          VALUES (?, ?, CAST(GETDATE() AS DATE), ?, 1, GETDATE(), ?)";
            $stmtInsRes = sqlsrv_query($this->db, $sqlInsRes, array($idRegistroRes, $r['id'], $r['cantidad'], $usuario_id));
            if ($stmtInsRes === false) {
                throw new Exception('Error al registrar residuo automático por solicitud: ' . print_r(sqlsrv_errors(), true));
            }
            $registrados++;
        }

        return $registrados;
    }

    public function asignarUsuarioCreacionResiduosPorSolicitud($id_solicitud, $usuario_id, $fecha_inicio = null, $fecha_fin = null) {
        $id_solicitud = intval($id_solicitud);
        $usuario_id = intval($usuario_id);

        if ($id_solicitud <= 0 || $usuario_id <= 0) {
            return 0;
        }

        $sqlSolicitud = "SELECT Id_Servicio, Estado
                         FROM laboratorio.Solicitud_Analisis
                         WHERE Id_Solicitud_Analisis = ? AND Activo = 1";
        $stmtSolicitud = sqlsrv_query($this->db, $sqlSolicitud, array($id_solicitud));
        if ($stmtSolicitud === false) {
            throw new Exception('Error al obtener solicitud para asignar usuario de residuos: ' . print_r(sqlsrv_errors(), true));
        }

        $rowSolicitud = sqlsrv_fetch_array($stmtSolicitud, SQLSRV_FETCH_ASSOC);
        if (!$rowSolicitud) {
            return 0;
        }

        $id_servicio = intval($rowSolicitud['Id_Servicio'] ?? 0);
        $estado_solicitud = strtolower(trim((string)($rowSolicitud['Estado'] ?? '')));
        if ($id_servicio <= 0 || $estado_solicitud !== 'finalizado') {
            return 0;
        }

        if (empty($fecha_inicio)) {
            $fecha_inicio = date('Y-m-d H:i:s', time() - 120);
        }
        if (empty($fecha_fin)) {
            $fecha_fin = date('Y-m-d H:i:s');
        }

        $sql = "WITH candidatos AS (
                    SELECT drl.Id_Detalle_Res,
                           ROW_NUMBER() OVER (
                               PARTITION BY drl.Id_Residuo_Cat
                               ORDER BY drl.Fecha_Creacion DESC, drl.Id_Detalle_Res DESC
                           ) AS rn
                    FROM laboratorio.Detalle_Residuos_Log drl
                    INNER JOIN laboratorio.Servicio_Residuo_Def srd
                            ON srd.Id_Residuo_Cat = drl.Id_Residuo_Cat
                           AND srd.Id_Servicio = ?
                           AND srd.Activo = 1
                    WHERE drl.Activo = 1
                      AND drl.Usuario_Creacion IS NULL
                      AND drl.Fecha_Creacion >= ?
                      AND drl.Fecha_Creacion <= ?
                      AND CAST(drl.Fecha_Dia AS DATE) = CAST(? AS DATE)
                      AND ABS(CAST(ISNULL(drl.Peso_Valor, 0) AS DECIMAL(18,4)) - CAST(ISNULL(srd.Cantidad_Estimada_Por_Muestra, 0) AS DECIMAL(18,4))) < 0.0001
                )
                UPDATE drl
                SET drl.Usuario_Creacion = ?,
                    drl.Fecha_Modificacion = GETDATE()
                FROM laboratorio.Detalle_Residuos_Log drl
                INNER JOIN candidatos c ON c.Id_Detalle_Res = drl.Id_Detalle_Res
                WHERE c.rn = 1";

        $params = array(
            $id_servicio,
            $fecha_inicio,
            $fecha_fin,
            $fecha_fin,
            $usuario_id
        );

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error al asignar usuario en residuos automáticos: ' . print_r(sqlsrv_errors(), true));
        }

        $afectados = sqlsrv_rows_affected($stmt);
        if (!is_int($afectados) || $afectados < 0) {
            $afectados = 0;
        }

        return $afectados;
    }

    public function registrarResiduosAutomaticosPorMuestra($id_muestra, $usuario_id, $validarEstadoFinalizado = true) {
        $id_muestra = intval($id_muestra);
        $usuario_id = intval($usuario_id);

        if ($id_muestra <= 0) {
            return 0;
        }

        if ($validarEstadoFinalizado) {
            $sqlEstado = "SELECT Estado
                          FROM laboratorio.Muestra_Lab
                          WHERE Id_Muestra = ? AND Activo = 1";
            $stmtEstado = sqlsrv_query($this->db, $sqlEstado, array($id_muestra));
            if ($stmtEstado === false) {
                throw new Exception('Error al validar estado de muestra para residuos: ' . print_r(sqlsrv_errors(), true));
            }

            $rowEstado = sqlsrv_fetch_array($stmtEstado, SQLSRV_FETCH_ASSOC);
            $estadoMuestra = strtolower(trim((string)($rowEstado['Estado'] ?? '')));
            if ($estadoMuestra !== 'finalizado') {
                return 0;
            }
        }

        $sqlResiduos = "SELECT srd.Id_Residuo_Cat,
                               SUM(CAST(ISNULL(srd.Cantidad_Estimada_Por_Muestra, 0) AS DECIMAL(18,4))) AS Cantidad_Total
                        FROM laboratorio.Solicitud_Analisis sa
                        INNER JOIN laboratorio.Servicio_Residuo_Def srd
                                ON srd.Id_Servicio = sa.Id_Servicio
                               AND srd.Activo = 1
                        WHERE sa.Id_Muestra = ?
                          AND sa.Activo = 1
                                                    AND sa.Estado = 'Finalizado'
                        GROUP BY srd.Id_Residuo_Cat
                        HAVING SUM(CAST(ISNULL(srd.Cantidad_Estimada_Por_Muestra, 0) AS DECIMAL(18,4))) > 0";

        $stmtResiduos = sqlsrv_query($this->db, $sqlResiduos, array($id_muestra));
        if ($stmtResiduos === false) {
            throw new Exception('Error al obtener residuos automáticos de muestra: ' . print_r(sqlsrv_errors(), true));
        }

        $residuos = [];
        while ($rowRes = sqlsrv_fetch_array($stmtResiduos, SQLSRV_FETCH_ASSOC)) {
            $idResiduo = intval($rowRes['Id_Residuo_Cat'] ?? 0);
            $cantidad = round(floatval($rowRes['Cantidad_Total'] ?? 0), 4);
            if ($idResiduo > 0 && $cantidad > 0) {
                $residuos[] = ['id' => $idResiduo, 'cantidad' => $cantidad];
            }
        }

        if (empty($residuos)) {
            return 0;
        }

        $mes = intval(date('n'));
        $anio = intval(date('Y'));

        $sqlCab = "SELECT TOP 1 Id_Registro_Res
                   FROM laboratorio.Registro_Residuos_Log
                   WHERE Mes = ? AND Anio = ? AND Activo = 1
                   ORDER BY Id_Registro_Res DESC";
        $stmtCab = sqlsrv_query($this->db, $sqlCab, array($mes, $anio));
        if ($stmtCab === false) {
            throw new Exception('Error al buscar cabecera de residuos automática: ' . print_r(sqlsrv_errors(), true));
        }

        $rowCab = sqlsrv_fetch_array($stmtCab, SQLSRV_FETCH_ASSOC);
        $idRegistroRes = intval($rowCab['Id_Registro_Res'] ?? 0);

        if ($idRegistroRes <= 0) {
            $sqlNewCab = "SET NOCOUNT ON;
                          INSERT INTO laboratorio.Registro_Residuos_Log
                          (Mes, Anio, Ubicacion, Id_Responsable, Codigo_SST, Activo, Fecha_Creacion, Usuario_Creacion)
                          VALUES (?, ?, 'LAB-GENERAL', ?, 'SST-16', 1, GETDATE(), ?);
                          SELECT CAST(SCOPE_IDENTITY() AS INT) AS id;";
            $stmtNewCab = sqlsrv_query($this->db, $sqlNewCab, array($mes, $anio, $usuario_id, $usuario_id));
            if ($stmtNewCab === false) {
                throw new Exception('Error al crear cabecera de residuos automática: ' . print_r(sqlsrv_errors(), true));
            }

            $rowNewCab = sqlsrv_fetch_array($stmtNewCab, SQLSRV_FETCH_ASSOC);
            $idRegistroRes = intval($rowNewCab['id'] ?? 0);
            if ($idRegistroRes <= 0) {
                throw new Exception('No se pudo obtener Id_Registro_Res en registro automático de residuos.');
            }
        }

        $registrados = 0;
        foreach ($residuos as $r) {
            $sqlInsRes = "INSERT INTO laboratorio.Detalle_Residuos_Log
                          (Id_Registro_Res, Id_Residuo_Cat, Fecha_Dia, Peso_Valor, Activo, Fecha_Creacion, Usuario_Creacion)
                          VALUES (?, ?, CAST(GETDATE() AS DATE), ?, 1, GETDATE(), ?)";
            $stmtInsRes = sqlsrv_query($this->db, $sqlInsRes, array($idRegistroRes, $r['id'], $r['cantidad'], $usuario_id));
            if ($stmtInsRes === false) {
                throw new Exception('Error al registrar residuo automático: ' . print_r(sqlsrv_errors(), true));
            }
            $registrados++;
        }

        return $registrados;
    }

    public function rechazarMuestra($id, $motivo) {
        $sql = "UPDATE laboratorio.Muestra_Lab 
                SET Estado = 'Rechazado', 
                    Observacion_Muestra = ?, 
                    Fecha_Validacion = GETDATE(), 
                    Fecha_Modificacion = GETDATE() 
                WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($motivo, $id));
        if ($stmt === false) {
            throw new Exception('Error en rechazarMuestra: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    // ===== DETALLE SUELO =====

    public function obtenerDetalleSuelo($id) {
        $sql = "SELECT * FROM laboratorio.Detalle_Suelo WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en obtenerDetalleSuelo: ' . print_r(sqlsrv_errors(), true));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardarDetalleSuelo($datos) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        $existente = $this->obtenerDetalleSuelo($datos['Id_Muestra']);
        
        if ($existente) {
            // UPDATE
            $sql = "UPDATE laboratorio.Detalle_Suelo 
                    SET Fuente_Riego=?, Profundidad=?, Numero_Submuestras=?, Cantidad_Muestra=?, 
                        Fecha_Modificacion=GETDATE() 
                    WHERE Id_Muestra=? AND Activo=1";
            $params = array(
                $datos['Fuente_Riego'] ?? null,
                $datos['Profundidad'] ?? null,
                $datos['Numero_Submuestras'] ?? null,
                $datos['Cantidad_Muestra'] ?? '1 Kg',
                $datos['Id_Muestra']
            );
        } else {
            // INSERT
            $sql = "INSERT INTO laboratorio.Detalle_Suelo 
                    (Id_Muestra, Fuente_Riego, Profundidad, Numero_Submuestras, Cantidad_Muestra, Usuario_Creacion, Activo, Fecha_Creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE())";
            $params = array(
                $datos['Id_Muestra'],
                $datos['Fuente_Riego'] ?? null,
                $datos['Profundidad'] ?? null,
                $datos['Numero_Submuestras'] ?? null,
                $datos['Cantidad_Muestra'] ?? '1 Kg',
                $usuario_id
            );
        }
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en guardarDetalleSuelo: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    // ===== DETALLE AGUA =====

    public function obtenerDetalleAgua($id) {
        $sql = "SELECT * FROM laboratorio.Detalle_Agua WHERE Id_Muestra = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en obtenerDetalleAgua: ' . print_r(sqlsrv_errors(), true));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardarDetalleAgua($datos) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        $existente = $this->obtenerDetalleAgua($datos['Id_Muestra']);
        
        if ($existente) {
            // UPDATE
            $sql = "UPDATE laboratorio.Detalle_Agua 
                    SET Uso_Agua=?, Fuente_Agua=?, Cantidad_Muestra=?, Nivel_Agua=?,
                        Fecha_Modificacion=GETDATE() 
                    WHERE Id_Muestra=? AND Activo=1";
            $params = array(
                $datos['Uso_Agua'] ?? null,
                $datos['Fuente_Agua'] ?? null,
                $datos['Cantidad_Muestra'] ?? '1 Litro',
                $datos['Nivel_Agua'] ?? null,
                $datos['Id_Muestra']
            );
        } else {
            // INSERT
            $sql = "INSERT INTO laboratorio.Detalle_Agua 
                    (Id_Muestra, Uso_Agua, Fuente_Agua, Cantidad_Muestra, Nivel_Agua, Usuario_Creacion, Activo, Fecha_Creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE())";
            $params = array(
                $datos['Id_Muestra'],
                $datos['Uso_Agua'] ?? null,
                $datos['Fuente_Agua'] ?? null,
                $datos['Cantidad_Muestra'] ?? '1 Litro',
                $datos['Nivel_Agua'] ?? null,
                $usuario_id
            );
        }
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en guardarDetalleAgua: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    // ===== PRODUCTOS DE MUESTRA =====

    public function obtenerProductos($id) {
        $sql = "SELECT mp.*, pv.Nombre_Comercial 
                FROM laboratorio.Muestra_Producto mp 
                JOIN laboratorio.Producto_Venta pv ON mp.Id_Producto_Venta = pv.Id_Producto 
                WHERE mp.Id_Muestra = ? AND mp.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en obtenerProductos: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarProducto($datos) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        $idMuestra = intval($datos['Id_Muestra'] ?? 0);

        if ($idMuestra > 0 && $this->esMuestraFijaBitacora($idMuestra)) {
            return false;
        }
        
        $sql = "INSERT INTO laboratorio.Muestra_Producto 
                (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion) 
                VALUES (?, ?, ?, ?, 1, GETDATE())";
        
        $params = array(
            $datos['Id_Muestra'],
            $datos['Id_Producto_Venta'],
            $datos['Id_Cliente'],
            $usuario_id
        );

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en guardarProducto: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function eliminarProducto($id) {
        $sql = "UPDATE laboratorio.Muestra_Producto SET Activo = 0, Fecha_Modificacion = GETDATE() 
                WHERE Id_Muestra_Producto = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            throw new Exception('Error en eliminarProducto: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    private function esMuestraFijaBitacora($idMuestra) {
        $idMuestra = intval($idMuestra);
        if ($idMuestra <= 0) {
            return false;
        }

        $sql = "SELECT TOP 1 1 AS existe
            FROM laboratorio.Muestra_Bitacora mb
            WHERE mb.Id_Muestra = ?
              AND (mb.Muestra_Original IS NULL OR mb.Muestra_Original = 0)
              AND (mb.Turno IS NULL OR LTRIM(RTRIM(mb.Turno)) = '')";

        $stmt = sqlsrv_query($this->db, $sql, [$idMuestra]);
        if ($stmt === false) {
            throw new Exception('Error en esMuestraFijaBitacora: ' . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return !empty($row['existe']);
    }

    public function obtenerAgricultoresActivos() {
        $sql = "SELECT Id_Cliente,
                       LTRIM(RTRIM(CONCAT(
                           ISNULL(Nombres, ''),
                           ' ',
                           ISNULL(Apellido_Paterno, ''),
                           ' ',
                           ISNULL(Apellido_Materno, '')
                       ))) AS Nombre
                FROM laboratorio.Cliente
                WHERE Activo = 1
                ORDER BY Nombres, Apellido_Paterno, Apellido_Materno";

        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error en obtenerAgricultoresActivos: ' . print_r(sqlsrv_errors(), true));
        }

        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = [
                'id' => intval($row['Id_Cliente'] ?? 0),
                'nombre' => trim((string)($row['Nombre'] ?? ''))
            ];
        }
        return $items;
    }

    public function obtenerVallesRegistrados() {
        $sql = "SELECT DISTINCT LTRIM(RTRIM(Valle)) AS Valle
                FROM laboratorio.Muestra_Lab
                WHERE Activo = 1
                  AND Valle IS NOT NULL
                  AND LTRIM(RTRIM(Valle)) <> ''
                ORDER BY Valle";

        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error en obtenerVallesRegistrados: ' . print_r(sqlsrv_errors(), true));
        }

        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $valle = trim((string)($row['Valle'] ?? ''));
            if ($valle !== '') {
                $items[] = $valle;
            }
        }
        return $items;
    }

    private function obtenerPkProductoVenta() {
        $sql = "SELECT TOP 1 COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = 'laboratorio'
                  AND TABLE_NAME = 'Producto_Venta'
                  AND COLUMN_NAME IN ('Id_Producto_Venta', 'Id_Producto')
                ORDER BY CASE WHEN COLUMN_NAME = 'Id_Producto_Venta' THEN 0 ELSE 1 END";

        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error en obtenerPkProductoVenta: ' . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $columna = trim((string)($row['COLUMN_NAME'] ?? ''));
        if ($columna === '') {
            throw new Exception('No se encontró columna PK en Producto_Venta.');
        }
        return $columna;
    }

    private function muestraBitacoraTieneColumnaProductoVenta() {
        $sql = "SELECT COUNT(*) AS total
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = 'laboratorio'
                  AND TABLE_NAME = 'Muestra_Bitacora'
                  AND COLUMN_NAME = 'Id_Producto_Venta'";

        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error en muestraBitacoraTieneColumnaProductoVenta: ' . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['total'] ?? 0) > 0;
    }

    public function obtenerServiciosDisponibles() {
        $columnaPk = $this->obtenerPkProductoVenta();
        $sql = "SELECT {$columnaPk} AS Id_Producto_Venta, Nombre_Comercial
                FROM laboratorio.Producto_Venta
                WHERE Activo = 1
                ORDER BY Nombre_Comercial";

        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error en obtenerServiciosDisponibles: ' . print_r(sqlsrv_errors(), true));
        }

        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = [
                'id' => intval($row['Id_Producto_Venta'] ?? 0),
                'nombre' => trim((string)($row['Nombre_Comercial'] ?? ''))
            ];
        }
        return $items;
    }

    public function crearMuestraPorDefectoOriginal($datos) {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        $idCliente = intval($datos['Id_Cliente'] ?? 0);
        $idClienteParam = $idCliente > 0 ? $idCliente : null;
        $valle = trim((string)($datos['Valle'] ?? ''));
        $fechaRecepcion = trim((string)($datos['Fecha_Recepcion'] ?? ''));
        $turno = null;
        $tipoMuestra = trim((string)($datos['Tipo_Muestra'] ?? 'Agua'));
        $puntoToma = trim((string)($datos['Punto_Toma'] ?? ''));
        $ubicacionPunto = trim((string)($datos['Ubicacion_Punto'] ?? ''));
        $idProductoVenta = intval($datos['Id_Producto_Venta'] ?? 0);
        $observacion = trim((string)($datos['Observacion_Muestra'] ?? ''));

        if ($valle === '') {
            throw new Exception('Debe seleccionar un valle.');
        }
        if ($fechaRecepcion === '') {
            throw new Exception('Debe seleccionar una fecha de registro.');
        }
        if ($tipoMuestra !== 'Agua' && $tipoMuestra !== 'Suelo') {
            throw new Exception('El tipo de muestra es inválido.');
        }
        if ($idProductoVenta <= 0) {
            throw new Exception('Debe seleccionar un producto de venta para duplicación.');
        }

        if (!sqlsrv_begin_transaction($this->db)) {
            throw new Exception('No se pudo iniciar la transacción.');
        }

        try {
            $sqlInsertMuestra = "INSERT INTO laboratorio.Muestra_Lab
                (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Valle, Eje_X, Eje_Y,
                 Fecha_Recepcion, Estado, Tipo_Servicio, Observacion_Muestra, Ruta_Imagen,
                 Id_Jefe_Lab, Es_Control_Calidad, Fecha_Toma,
                 Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, NULL,
                    NULL, 0, ?, ?, 1, GETDATE());
                SELECT SCOPE_IDENTITY() AS id;";

            $paramsInsertMuestra = [
                $idClienteParam,
                $usuarioId,
                $valle,
                $datos['Eje_X'] ?? null,
                $datos['Eje_Y'] ?? null,
                $fechaRecepcion,
                $datos['Estado'] ?? 'Por Recepcionar',
                'Interno',
                $observacion !== '' ? $observacion : null,
                $datos['Fecha_Toma'] ?? $fechaRecepcion,
                $usuarioId
            ];

            $stmtMuestra = sqlsrv_query($this->db, $sqlInsertMuestra, $paramsInsertMuestra);
            if ($stmtMuestra === false) {
                throw new Exception('Error al crear la muestra: ' . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_next_result($stmtMuestra);
            $rowMuestra = sqlsrv_fetch_array($stmtMuestra, SQLSRV_FETCH_ASSOC);
            $idMuestra = intval($rowMuestra['id'] ?? 0);
            if ($idMuestra <= 0) {
                throw new Exception('No se pudo obtener el ID de la muestra creada.');
            }

            $stmtBitacoraExistente = sqlsrv_query(
                $this->db,
                "SELECT TOP 1 Id_Bitacora
                 FROM laboratorio.Bitacora_Control_PTA
                 WHERE Fecha_Registro = ?
                   AND Turno IS NULL
                   AND Activo = 1
                 ORDER BY Id_Bitacora DESC",
                [$fechaRecepcion]
            );
            if ($stmtBitacoraExistente === false) {
                throw new Exception('Error al buscar bitácora existente: ' . print_r(sqlsrv_errors(), true));
            }

            $rowBitacora = sqlsrv_fetch_array($stmtBitacoraExistente, SQLSRV_FETCH_ASSOC);
            $idBitacora = intval($rowBitacora['Id_Bitacora'] ?? 0);

            if ($idBitacora <= 0) {
                $sqlInsertBitacora = "INSERT INTO laboratorio.Bitacora_Control_PTA
                    (Fecha_Registro, Turno, Observacion_General, Id_Responsable, Activo, Fecha_Creacion, Usuario_Creacion)
                    VALUES (?, ?, ?, ?, 1, GETDATE(), ?);
                    SELECT SCOPE_IDENTITY() AS id;";

                $stmtBitacora = sqlsrv_query($this->db, $sqlInsertBitacora, [
                    $fechaRecepcion,
                    null,
                    $observacion !== '' ? $observacion : null,
                    $usuarioId,
                    $usuarioId
                ]);

                if ($stmtBitacora === false) {
                    throw new Exception('Error al crear bitácora: ' . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_next_result($stmtBitacora);
                $rowBitacoraNueva = sqlsrv_fetch_array($stmtBitacora, SQLSRV_FETCH_ASSOC);
                $idBitacora = intval($rowBitacoraNueva['id'] ?? 0);
                if ($idBitacora <= 0) {
                    throw new Exception('No se pudo obtener el ID de la bitácora creada.');
                }
            }

            if ($this->muestraBitacoraTieneColumnaProductoVenta()) {
                $stmtMuestraBitacora = sqlsrv_query(
                    $this->db,
                    "INSERT INTO laboratorio.Muestra_Bitacora
                     (Id_Muestra, Id_Bitacora, Turno, Punto_Toma, Muestra_Original, Id_Producto_Venta, Ubicacion_Punto)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$idMuestra, $idBitacora, null, $puntoToma !== '' ? $puntoToma : null, null, $idProductoVenta, $ubicacionPunto !== '' ? $ubicacionPunto : null]
                );
            } else {
                $stmtMuestraBitacora = sqlsrv_query(
                    $this->db,
                    "INSERT INTO laboratorio.Muestra_Bitacora
                     (Id_Muestra, Id_Bitacora, Turno, Punto_Toma, Muestra_Original, Ubicacion_Punto)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$idMuestra, $idBitacora, null, $puntoToma !== '' ? $puntoToma : null, null, $ubicacionPunto !== '' ? $ubicacionPunto : null]
                );
            }
            if ($stmtMuestraBitacora === false) {
                throw new Exception('Error al vincular muestra con bitácora: ' . print_r(sqlsrv_errors(), true));
            }

            // Las muestras originales/fijas solo guardan su producto en Muestra_Bitacora.
            // No se crea Muestra_Producto para evitar consumos automáticos de reactivos o residuos.

            if ($tipoMuestra === 'Agua') {
                $this->guardarDetalleAgua([
                    'Id_Muestra' => $idMuestra,
                    'Uso_Agua' => $datos['Uso_Agua'] ?? null,
                    'Fuente_Agua' => $datos['Fuente_Agua'] ?? null,
                    'Cantidad_Muestra' => $datos['Cantidad_Muestra_Agua'] ?? '1 Litro',
                    'Nivel_Agua' => $datos['Nivel_Agua'] ?? null
                ]);
            } else {
                $this->guardarDetalleSuelo([
                    'Id_Muestra' => $idMuestra,
                    'Fuente_Riego' => $datos['Fuente_Riego'] ?? null,
                    'Profundidad' => $datos['Profundidad'] ?? null,
                    'Numero_Submuestras' => $datos['Numero_Submuestras'] ?? null,
                    'Cantidad_Muestra' => $datos['Cantidad_Muestra_Suelo'] ?? '1 Kg'
                ]);
            }

            if (!sqlsrv_commit($this->db)) {
                throw new Exception('No se pudo confirmar la transacción de creación.');
            }

            return [
                'id_muestra' => $idMuestra,
                'id_bitacora' => $idBitacora
            ];
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            throw $e;
        }
    }

    public function crearMuestraIndividual($datos) {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        $idCliente = intval($datos['Id_Cliente'] ?? 0);
        $idProductoVenta = intval($datos['Id_Producto_Venta'] ?? 0);
        $valle = trim((string)($datos['Valle'] ?? ''));
        $fechaToma = trim((string)($datos['Fecha_Toma'] ?? ''));
        $tipoMuestra = trim((string)($datos['Tipo_Muestra'] ?? 'Agua'));
        $tipoServicio = ucfirst(strtolower(trim((string)($datos['Tipo_Servicio'] ?? ''))));
        $observacion = trim((string)($datos['Observacion_Muestra'] ?? ''));
        $ubicacionPunto = trim((string)($datos['Ubicacion_Punto'] ?? ''));
        $puntoToma = trim((string)($datos['Punto_Toma'] ?? ''));

        if ($idCliente <= 0) {
            throw new Exception('Debe seleccionar un agricultor/cliente.');
        }
        if ($valle === '') {
            throw new Exception('Debe seleccionar un valle.');
        }
        if ($fechaToma === '') {
            throw new Exception('Debe seleccionar una fecha de toma.');
        }
        if ($tipoMuestra !== 'Agua' && $tipoMuestra !== 'Suelo') {
            throw new Exception('El tipo de muestra es inválido.');
        }
        if ($idProductoVenta <= 0) {
            throw new Exception('Debe seleccionar un producto/servicio.');
        }
        if ($tipoServicio !== 'Interno' && $tipoServicio !== 'Externo') {
            throw new Exception('El tipo de servicio es inválido.');
        }

        $observacionFinal = $observacion;
        $bloqueRegistro = [];
        if ($ubicacionPunto !== '') {
            $bloqueRegistro[] = 'Ubicacion: ' . $ubicacionPunto;
        }
        if ($puntoToma !== '') {
            $bloqueRegistro[] = 'Punto de toma: ' . $puntoToma;
        }
        if (!empty($bloqueRegistro)) {
            $textoRegistro = '[REGISTRO] ' . implode(' | ', $bloqueRegistro);
            $observacionFinal = $observacionFinal === '' ? $textoRegistro : ($observacionFinal . PHP_EOL . $textoRegistro);
        }

        if (!sqlsrv_begin_transaction($this->db)) {
            throw new Exception('No se pudo iniciar la transacción.');
        }

        try {
            $sqlInsertMuestra = "INSERT INTO laboratorio.Muestra_Lab
                (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Valle, Eje_X, Eje_Y,
                 Fecha_Recepcion, Estado, Tipo_Servicio, Observacion_Muestra, Ruta_Imagen,
                 Id_Jefe_Lab, Es_Control_Calidad, Fecha_Toma,
                 Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, NULL, NULL, NULL, ?, ?, ?, NULL, ?, ?, ?, NULL,
                        NULL, 0, ?, ?, 1, GETDATE());
                SELECT SCOPE_IDENTITY() AS id;";

            $stmtMuestra = sqlsrv_query($this->db, $sqlInsertMuestra, [
                $idCliente,
                $valle,
                $datos['Eje_X'] ?? null,
                $datos['Eje_Y'] ?? null,
                'Pendiente',
                $tipoServicio,
                $observacionFinal !== '' ? $observacionFinal : null,
                $fechaToma,
                $usuarioId
            ]);
            if ($stmtMuestra === false) {
                throw new Exception('Error al crear muestra individual: ' . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_next_result($stmtMuestra);
            $rowMuestra = sqlsrv_fetch_array($stmtMuestra, SQLSRV_FETCH_ASSOC);
            $idMuestra = intval($rowMuestra['id'] ?? 0);
            if ($idMuestra <= 0) {
                throw new Exception('No se pudo obtener el ID de la muestra creada.');
            }

            $stmtProducto = sqlsrv_query(
                $this->db,
                "INSERT INTO laboratorio.Muestra_Producto
                 (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion)
                 VALUES (?, ?, ?, ?, 1, GETDATE())",
                [$idMuestra, $idProductoVenta, $idCliente, $usuarioId]
            );
            if ($stmtProducto === false) {
                throw new Exception('Error al asociar producto/servicio a la muestra: ' . print_r(sqlsrv_errors(), true));
            }

            if ($tipoMuestra === 'Agua') {
                $this->guardarDetalleAgua([
                    'Id_Muestra' => $idMuestra,
                    'Uso_Agua' => $datos['Uso_Agua'] ?? null,
                    'Fuente_Agua' => $datos['Fuente_Agua'] ?? null,
                    'Cantidad_Muestra' => $datos['Cantidad_Muestra_Agua'] ?? '1 Litro',
                    'Nivel_Agua' => $datos['Nivel_Agua'] ?? null
                ]);
            } else {
                $this->guardarDetalleSuelo([
                    'Id_Muestra' => $idMuestra,
                    'Fuente_Riego' => $datos['Fuente_Riego'] ?? null,
                    'Profundidad' => $datos['Profundidad'] ?? null,
                    'Numero_Submuestras' => $datos['Numero_Submuestras'] ?? null,
                    'Cantidad_Muestra' => $datos['Cantidad_Muestra_Suelo'] ?? '1 Kg'
                ]);
            }

            if (!sqlsrv_commit($this->db)) {
                throw new Exception('No se pudo confirmar la creación de la muestra individual.');
            }

            return [
                'id_muestra' => $idMuestra
            ];
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            throw $e;
        }
    }

    public function obtenerMuestrasOriginalesPorDefecto($offset = 0, $limit = 10, $search = '') {
        $offset = max(0, intval($offset));
        $limit = max(1, intval($limit));
        $search = trim((string)$search);

           $sql = "SELECT m.Id_Muestra,
                       CASE WHEN c.Id_Cliente IS NULL THEN 'Sin agricultor'
                           ELSE LTRIM(RTRIM(CONCAT(ISNULL(c.Nombres, ''), ' ', ISNULL(c.Apellido_Paterno, ''), ' ', ISNULL(c.Apellido_Materno, ''))))
                       END AS Agricultor,
                       CONCAT(ISNULL(m.Eje_X, 'x:'), ' ', ISNULL(m.Eje_Y, 'y:')) AS Coordenadas,
                       m.Valle,
                       CONVERT(VARCHAR(10), m.Fecha_Creacion, 105) AS Fecha_Creacion,
                       CASE WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                            WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                            ELSE 'Sin clasificar' END AS Tipo_Muestra,
                       m.Activo,
                       mb.Turno,
                       mb.Punto_Toma,
                       mb.Ubicacion_Punto
                FROM laboratorio.Muestra_Lab m
                LEFT JOIN laboratorio.Cliente c ON c.Id_Cliente = m.Id_Cliente
                INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                LEFT JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra AND da.Activo = 1
                LEFT JOIN laboratorio.Detalle_Suelo ds ON ds.Id_Muestra = m.Id_Muestra AND ds.Activo = 1
                                WHERE (mb.Muestra_Original IS NULL OR mb.Muestra_Original = 0)
                                    AND (mb.Turno IS NULL OR LTRIM(RTRIM(mb.Turno)) = '')
                                    AND mb.Id_Producto_Venta IS NOT NULL";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (
                        CONCAT(ISNULL(c.Nombres, ''), ' ', ISNULL(c.Apellido_Paterno, ''), ' ', ISNULL(c.Apellido_Materno, '')) LIKE ?
                        OR m.Valle LIKE ?
                        OR ISNULL(mb.Punto_Toma, '') LIKE ?
                        OR ISNULL(mb.Ubicacion_Punto, '') LIKE ?
                    )";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY m.Id_Muestra DESC
                  OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

        $params[] = $offset;
        $params[] = $limit;

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerMuestrasOriginalesPorDefecto: ' . print_r(sqlsrv_errors(), true));
        }

        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = $row;
        }
        return $items;
    }

    public function contarMuestrasOriginalesPorDefecto($search = '') {
        $search = trim((string)$search);

        $sql = "SELECT COUNT(*) AS total
                FROM laboratorio.Muestra_Lab m
            LEFT JOIN laboratorio.Cliente c ON c.Id_Cliente = m.Id_Cliente
                INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                                WHERE (mb.Muestra_Original IS NULL OR mb.Muestra_Original = 0)
                                    AND (mb.Turno IS NULL OR LTRIM(RTRIM(mb.Turno)) = '')
                                    AND mb.Id_Producto_Venta IS NOT NULL";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (
                        CONCAT(ISNULL(c.Nombres, ''), ' ', ISNULL(c.Apellido_Paterno, ''), ' ', ISNULL(c.Apellido_Materno, '')) LIKE ?
                        OR m.Valle LIKE ?
                        OR ISNULL(mb.Punto_Toma, '') LIKE ?
                        OR ISNULL(mb.Ubicacion_Punto, '') LIKE ?
                    )";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en contarMuestrasOriginalesPorDefecto: ' . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['total'] ?? 0);
    }

    public function obtenerMuestraPorDefectoPorId($idMuestra) {
        $idMuestra = intval($idMuestra);
        if ($idMuestra <= 0) {
            return null;
        }

        $tieneProductoBitacora = $this->muestraBitacoraTieneColumnaProductoVenta();
        $colProducto = $tieneProductoBitacora ? 'mb.Id_Producto_Venta' : 'NULL';

        $sql = "SELECT TOP 1
                    m.Id_Muestra,
                    m.Id_Cliente,
                    m.Valle,
                    m.Eje_X,
                    m.Eje_Y,
                    CONVERT(VARCHAR(10), m.Fecha_Recepcion, 23) AS Fecha_Registro,
                    m.Observacion_Muestra,
                    mb.Turno,
                    mb.Punto_Toma,
                    mb.Ubicacion_Punto,
                    CASE WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                         WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                         ELSE 'Agua' END AS Tipo_Muestra,
                    {$colProducto} AS Id_Producto_Venta,
                    da.Uso_Agua,
                    da.Fuente_Agua,
                    da.Nivel_Agua,
                    da.Cantidad_Muestra AS Cantidad_Muestra_Agua,
                    ds.Fuente_Riego,
                    ds.Profundidad,
                    ds.Numero_Submuestras,
                    ds.Cantidad_Muestra AS Cantidad_Muestra_Suelo
                FROM laboratorio.Muestra_Lab m
                INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                LEFT JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra AND da.Activo = 1
                LEFT JOIN laboratorio.Detalle_Suelo ds ON ds.Id_Muestra = m.Id_Muestra AND ds.Activo = 1
                WHERE m.Id_Muestra = ?";

        $stmt = sqlsrv_query($this->db, $sql, [$idMuestra]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerMuestraPorDefectoPorId: ' . print_r(sqlsrv_errors(), true));
        }

        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: null;
    }

    public function actualizarMuestraPorDefectoOriginal($datos) {
        $idMuestra = intval($datos['Id_Muestra'] ?? 0);
        $idCliente = intval($datos['Id_Cliente'] ?? 0);
        $idClienteParam = $idCliente > 0 ? $idCliente : null;
        $valle = trim((string)($datos['Valle'] ?? ''));
        $fechaRecepcion = trim((string)($datos['Fecha_Recepcion'] ?? ''));
        $turno = null;
        $tipoMuestra = trim((string)($datos['Tipo_Muestra'] ?? 'Agua'));
        $puntoToma = trim((string)($datos['Punto_Toma'] ?? ''));
        $ubicacionPunto = trim((string)($datos['Ubicacion_Punto'] ?? ''));
        $idProductoVenta = intval($datos['Id_Producto_Venta'] ?? 0);
        $observacion = trim((string)($datos['Observacion_Muestra'] ?? ''));

        if ($idMuestra <= 0) {
            throw new Exception('ID de muestra inválido.');
        }
        if ($valle === '') {
            throw new Exception('Debe seleccionar un valle.');
        }
        if ($fechaRecepcion === '') {
            throw new Exception('Debe seleccionar una fecha de registro.');
        }
        if ($tipoMuestra !== 'Agua' && $tipoMuestra !== 'Suelo') {
            throw new Exception('El tipo de muestra es inválido.');
        }
        if ($idProductoVenta <= 0) {
            throw new Exception('Debe seleccionar un producto de venta para duplicación.');
        }

        if (!sqlsrv_begin_transaction($this->db)) {
            throw new Exception('No se pudo iniciar la transacción.');
        }

        try {
            $stmtMuestra = sqlsrv_query(
                $this->db,
                "UPDATE laboratorio.Muestra_Lab
                 SET Id_Cliente = ?,
                     Valle = ?,
                     Eje_X = ?,
                     Eje_Y = ?,
                     Fecha_Recepcion = ?,
                     Fecha_Toma = ?,
                     Observacion_Muestra = ?,
                     Fecha_Modificacion = GETDATE()
                 WHERE Id_Muestra = ?",
                [
                    $idClienteParam,
                    $valle,
                    $datos['Eje_X'] ?? null,
                    $datos['Eje_Y'] ?? null,
                    $fechaRecepcion,
                    $datos['Fecha_Toma'] ?? $fechaRecepcion,
                    $observacion !== '' ? $observacion : null,
                    $idMuestra
                ]
            );
            if ($stmtMuestra === false) {
                throw new Exception('Error al actualizar muestra: ' . print_r(sqlsrv_errors(), true));
            }

            if ($this->muestraBitacoraTieneColumnaProductoVenta()) {
                $stmtBitacora = sqlsrv_query(
                    $this->db,
                    "UPDATE laboratorio.Muestra_Bitacora
                     SET Turno = NULL, Punto_Toma = ?, Ubicacion_Punto = ?, Muestra_Original = NULL, Id_Producto_Venta = ?
                     WHERE Id_Muestra = ?",
                    [$puntoToma !== '' ? $puntoToma : null, $ubicacionPunto !== '' ? $ubicacionPunto : null, $idProductoVenta, $idMuestra]
                );
            } else {
                $stmtBitacora = sqlsrv_query(
                    $this->db,
                    "UPDATE laboratorio.Muestra_Bitacora
                     SET Turno = NULL, Punto_Toma = ?, Ubicacion_Punto = ?, Muestra_Original = NULL
                     WHERE Id_Muestra = ?",
                    [$puntoToma !== '' ? $puntoToma : null, $ubicacionPunto !== '' ? $ubicacionPunto : null, $idMuestra]
                );
            }
            if ($stmtBitacora === false) {
                throw new Exception('Error al actualizar bitácora de muestra: ' . print_r(sqlsrv_errors(), true));
            }

            if ($tipoMuestra === 'Agua') {
                $this->guardarDetalleAgua([
                    'Id_Muestra' => $idMuestra,
                    'Uso_Agua' => $datos['Uso_Agua'] ?? null,
                    'Fuente_Agua' => $datos['Fuente_Agua'] ?? null,
                    'Cantidad_Muestra' => $datos['Cantidad_Muestra_Agua'] ?? '1 Litro',
                    'Nivel_Agua' => $datos['Nivel_Agua'] ?? null
                ]);
            } else {
                $this->guardarDetalleSuelo([
                    'Id_Muestra' => $idMuestra,
                    'Fuente_Riego' => $datos['Fuente_Riego'] ?? null,
                    'Profundidad' => $datos['Profundidad'] ?? null,
                    'Numero_Submuestras' => $datos['Numero_Submuestras'] ?? null,
                    'Cantidad_Muestra' => $datos['Cantidad_Muestra_Suelo'] ?? '1 Kg'
                ]);
            }

            if (!sqlsrv_commit($this->db)) {
                throw new Exception('No se pudo confirmar la actualización.');
            }

            return true;
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            throw $e;
        }
    }

    public function desactivarMuestraPorDefecto($idMuestra) {
        $idMuestra = intval($idMuestra);
        if ($idMuestra <= 0) {
            throw new Exception('ID de muestra inválido.');
        }

        $stmt = sqlsrv_query(
            $this->db,
            "UPDATE laboratorio.Muestra_Lab
             SET Activo = 0, Fecha_Modificacion = GETDATE()
             WHERE Id_Muestra = ? AND Activo = 1",
            [$idMuestra]
        );
        if ($stmt === false) {
            throw new Exception('Error al desactivar muestra: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function reactivarMuestraPorDefecto($idMuestra) {
        $idMuestra = intval($idMuestra);
        if ($idMuestra <= 0) {
            throw new Exception('ID de muestra inválido.');
        }

        $stmt = sqlsrv_query(
            $this->db,
            "UPDATE laboratorio.Muestra_Lab
             SET Activo = 1, Fecha_Modificacion = GETDATE()
             WHERE Id_Muestra = ? AND Activo = 0",
            [$idMuestra]
        );
        if ($stmt === false) {
            throw new Exception('Error al reactivar muestra: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function duplicarMuestrasPorDefecto($idsMuestrasOriginales, $fechaRegistro, $turno) {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;
        $fechaRegistro = trim((string)$fechaRegistro);
        $turno = trim((string)$turno);

        if ($fechaRegistro === '') {
            throw new Exception('Debe seleccionar una fecha de registro.');
        }
        if ($turno !== 'Mañana' && $turno !== 'Tarde') {
            throw new Exception('Debe seleccionar un turno válido.');
        }

        $ids = [];
        foreach ((array)$idsMuestrasOriginales as $id) {
            $idInt = intval($id);
            if ($idInt > 0) {
                $ids[$idInt] = $idInt;
            }
        }
        $ids = array_values($ids);
        if (count($ids) === 0) {
            throw new Exception('Debe seleccionar al menos una muestra por defecto.');
        }

        $this->validarStockReactivosParaDuplicados($ids);

        if (!sqlsrv_begin_transaction($this->db)) {
            throw new Exception('No se pudo iniciar la transacción de duplicación.');
        }

        try {
            $stmtBitacora = sqlsrv_query(
                $this->db,
                "INSERT INTO laboratorio.Bitacora_Control_PTA
                 (Fecha_Registro, Turno, Observacion_General, Id_Responsable, Activo, Fecha_Creacion, Usuario_Creacion)
                 VALUES (?, ?, ?, ?, 1, GETDATE(), ?);
                 SELECT SCOPE_IDENTITY() AS id;",
                [$fechaRegistro, $turno, 'Bitácora generada desde muestras por defecto', $usuarioId, $usuarioId]
            );
            if ($stmtBitacora === false) {
                throw new Exception('Error al crear bitácora de duplicación: ' . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_next_result($stmtBitacora);
            $rowBit = sqlsrv_fetch_array($stmtBitacora, SQLSRV_FETCH_ASSOC);
            $idBitacora = intval($rowBit['id'] ?? 0);
            if ($idBitacora <= 0) {
                throw new Exception('No se pudo obtener el ID de la bitácora creada.');
            }

            $idsDuplicados = [];
            $tieneColProdBit = $this->muestraBitacoraTieneColumnaProductoVenta();

            foreach ($ids as $idOriginal) {
                $sqlOriginal = "SELECT TOP 1
                                   m.Id_Muestra,
                                   m.Id_Cliente,
                                   m.Valle,
                                   m.Eje_X,
                                   m.Eje_Y,
                                   m.Observacion_Muestra,
                                   mb.Ubicacion_Punto,
                                   mb.Punto_Toma,
                                   mb.Id_Producto_Venta,
                                   CASE WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                                        WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                                        ELSE 'Agua' END AS Tipo_Muestra
                                FROM laboratorio.Muestra_Lab m
                                INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                                LEFT JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra AND da.Activo = 1
                                LEFT JOIN laboratorio.Detalle_Suelo ds ON ds.Id_Muestra = m.Id_Muestra AND ds.Activo = 1
                                WHERE m.Id_Muestra = ?
                                  AND m.Activo = 1
                                  AND (mb.Muestra_Original IS NULL OR mb.Muestra_Original = 0)
                                  AND (mb.Turno IS NULL OR LTRIM(RTRIM(mb.Turno)) = '')
                                  AND mb.Id_Producto_Venta IS NOT NULL";

                $stmtOriginal = sqlsrv_query($this->db, $sqlOriginal, [$idOriginal]);
                if ($stmtOriginal === false) {
                    throw new Exception('Error al obtener muestra original: ' . print_r(sqlsrv_errors(), true));
                }
                $original = sqlsrv_fetch_array($stmtOriginal, SQLSRV_FETCH_ASSOC);
                if (!$original) {
                    throw new Exception('La muestra ID ' . $idOriginal . ' no es válida como muestra por defecto original.');
                }

                $idProductoVenta = intval($original['Id_Producto_Venta'] ?? 0);
                if ($idProductoVenta <= 0) {
                    throw new Exception('La muestra original ID ' . $idOriginal . ' no tiene producto de duplicación configurado.');
                }

                $stmtDupMuestra = sqlsrv_query(
                    $this->db,
                    "INSERT INTO laboratorio.Muestra_Lab
                     (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Valle, Eje_X, Eje_Y,
                      Fecha_Recepcion, Estado, Tipo_Servicio, Observacion_Muestra, Ruta_Imagen,
                      Id_Jefe_Lab, Es_Control_Calidad, Fecha_Toma,
                      Usuario_Creacion, Activo, Fecha_Creacion)
                     VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, NULL,
                             NULL, 0, ?, ?, 1, GETDATE());
                     SELECT SCOPE_IDENTITY() AS id;",
                    [
                        null,
                        $usuarioId,
                        $original['Valle'] ?? null,
                        $original['Eje_X'] ?? null,
                        $original['Eje_Y'] ?? null,
                        $fechaRegistro,
                        'Por Recepcionar',
                        'Interno',
                        $original['Observacion_Muestra'] ?? null,
                        $fechaRegistro,
                        $usuarioId
                    ]
                );
                if ($stmtDupMuestra === false) {
                    throw new Exception('Error al crear muestra duplicada: ' . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_next_result($stmtDupMuestra);
                $rowDup = sqlsrv_fetch_array($stmtDupMuestra, SQLSRV_FETCH_ASSOC);
                $idMuestraDuplicada = intval($rowDup['id'] ?? 0);
                if ($idMuestraDuplicada <= 0) {
                    throw new Exception('No se pudo obtener el ID de la muestra duplicada.');
                }

                if ($tieneColProdBit) {
                    $stmtDupBit = sqlsrv_query(
                        $this->db,
                        "INSERT INTO laboratorio.Muestra_Bitacora
                         (Id_Muestra, Id_Bitacora, Turno, Punto_Toma, Muestra_Original, Id_Producto_Venta, Ubicacion_Punto)
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$idMuestraDuplicada, $idBitacora, $turno, $original['Punto_Toma'] ?? null, $idOriginal, $idProductoVenta, $original['Ubicacion_Punto'] ?? null]
                    );
                } else {
                    $stmtDupBit = sqlsrv_query(
                        $this->db,
                        "INSERT INTO laboratorio.Muestra_Bitacora
                         (Id_Muestra, Id_Bitacora, Turno, Punto_Toma, Muestra_Original, Ubicacion_Punto)
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [$idMuestraDuplicada, $idBitacora, $turno, $original['Punto_Toma'] ?? null, $idOriginal, $original['Ubicacion_Punto'] ?? null]
                    );
                }
                if ($stmtDupBit === false) {
                    throw new Exception('Error al vincular muestra duplicada con bitácora: ' . print_r(sqlsrv_errors(), true));
                }

                $idClienteProducto = intval($original['Id_Cliente'] ?? 0);
                $stmtDupProducto = sqlsrv_query(
                    $this->db,
                    "INSERT INTO laboratorio.Muestra_Producto
                     (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion)
                     VALUES (?, ?, ?, ?, 1, GETDATE())",
                    [
                        $idMuestraDuplicada,
                        $idProductoVenta,
                        $idClienteProducto > 0 ? $idClienteProducto : null,
                        $usuarioId
                    ]
                );
                if ($stmtDupProducto === false) {
                    throw new Exception('Error al registrar producto en muestra duplicada: ' . print_r(sqlsrv_errors(), true));
                }

                $stmtEnAnalisis = sqlsrv_query(
                    $this->db,
                    "UPDATE laboratorio.Muestra_Lab
                     SET Estado = 'En Analisis',
                         Id_Especialista = ?,
                         Fecha_Analisis = ISNULL(Fecha_Analisis, GETDATE()),
                         Fecha_Modificacion = GETDATE()
                     WHERE Id_Muestra = ? AND Activo = 1",
                    [$usuarioId, $idMuestraDuplicada]
                );
                if ($stmtEnAnalisis === false) {
                    throw new Exception('Error al pasar muestra duplicada a En Analisis: ' . print_r(sqlsrv_errors(), true));
                }

                $this->asegurarSolicitudesYResultadosPorMuestra($idMuestraDuplicada, $usuarioId);

                $tipoMuestra = trim((string)($original['Tipo_Muestra'] ?? 'Agua'));
                if ($tipoMuestra === 'Agua') {
                    $detalleAgua = $this->obtenerDetalleAgua($idOriginal);
                    $this->guardarDetalleAgua([
                        'Id_Muestra' => $idMuestraDuplicada,
                        'Uso_Agua' => $detalleAgua['Uso_Agua'] ?? null,
                        'Fuente_Agua' => $detalleAgua['Fuente_Agua'] ?? null,
                        'Cantidad_Muestra' => $detalleAgua['Cantidad_Muestra'] ?? '1 Litro',
                        'Nivel_Agua' => $detalleAgua['Nivel_Agua'] ?? null
                    ]);
                } else {
                    $detalleSuelo = $this->obtenerDetalleSuelo($idOriginal);
                    $this->guardarDetalleSuelo([
                        'Id_Muestra' => $idMuestraDuplicada,
                        'Fuente_Riego' => $detalleSuelo['Fuente_Riego'] ?? null,
                        'Profundidad' => $detalleSuelo['Profundidad'] ?? null,
                        'Numero_Submuestras' => $detalleSuelo['Numero_Submuestras'] ?? null,
                        'Cantidad_Muestra' => $detalleSuelo['Cantidad_Muestra'] ?? '1 Kg'
                    ]);
                }

                $idsDuplicados[] = $idMuestraDuplicada;
            }

            foreach ($idsDuplicados as $idMuestraDuplicada) {
                $stmtMP = sqlsrv_query(
                    $this->db,
                    "SELECT TOP 1 Id_Muestra_Producto
                     FROM laboratorio.Muestra_Producto
                     WHERE Id_Muestra = ? AND Activo = 1
                     ORDER BY Id_Muestra_Producto DESC",
                    [$idMuestraDuplicada]
                );
                if ($stmtMP === false) {
                    throw new Exception('Error al obtener Id_Muestra_Producto para muestra duplicada ' . $idMuestraDuplicada . ': ' . print_r(sqlsrv_errors(), true));
                }

                $rowMP = sqlsrv_fetch_array($stmtMP, SQLSRV_FETCH_ASSOC);
                $idMuestraProducto = intval($rowMP['Id_Muestra_Producto'] ?? 0);
                if ($idMuestraProducto <= 0) {
                    throw new Exception('No se pudo obtener Id_Muestra_Producto para muestra duplicada ' . $idMuestraDuplicada . '.');
                }

                $this->registrarConsumoReactivosInterno($idMuestraProducto, $usuarioId);
            }

            if (!sqlsrv_commit($this->db)) {
                throw new Exception('No se pudo confirmar la duplicación de muestras.');
            }

            return [
                'id_bitacora' => $idBitacora,
                'ids_muestras' => $idsDuplicados,
                'id_muestra_inicial' => !empty($idsDuplicados) ? intval($idsDuplicados[0]) : 0,
                'total' => count($idsDuplicados)
            ];
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            throw $e;
        }
    }

    private function validarStockReactivosParaDuplicados($idsMuestrasOriginales) {
        $ids = array_values(array_unique(array_map('intval', (array)$idsMuestrasOriginales)));
        $ids = array_values(array_filter($ids, function($v) { return $v > 0; }));

        if (empty($ids)) {
            throw new Exception('No se recibieron muestras válidas para duplicar.');
        }

        $totalesPorReactivo = [];

        foreach ($ids as $idOriginal) {
            $stmtOriginal = sqlsrv_query(
                $this->db,
                "SELECT TOP 1 mb.Id_Producto_Venta
                 FROM laboratorio.Muestra_Lab m
                 INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                 WHERE m.Id_Muestra = ?
                   AND m.Activo = 1
                   AND (mb.Muestra_Original IS NULL OR mb.Muestra_Original = 0)
                   AND (mb.Turno IS NULL OR LTRIM(RTRIM(mb.Turno)) = '')
                   AND mb.Id_Producto_Venta IS NOT NULL",
                [$idOriginal]
            );
            if ($stmtOriginal === false) {
                throw new Exception('Error al validar muestra original para duplicado: ' . print_r(sqlsrv_errors(), true));
            }

            $rowOriginal = sqlsrv_fetch_array($stmtOriginal, SQLSRV_FETCH_ASSOC);
            if (!$rowOriginal) {
                throw new Exception('La muestra ID ' . $idOriginal . ' no es válida como muestra por defecto original.');
            }

            $idProductoVenta = intval($rowOriginal['Id_Producto_Venta'] ?? 0);
            if ($idProductoVenta <= 0) {
                throw new Exception('La muestra original ID ' . $idOriginal . ' no tiene producto de duplicación configurado.');
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

            $stmtDemanda = sqlsrv_query($this->db, $sqlDemanda, [$idProductoVenta]);
            if ($stmtDemanda === false) {
                throw new Exception('Error al calcular reactivos requeridos para duplicados: ' . print_r(sqlsrv_errors(), true));
            }

            while ($rowDemanda = sqlsrv_fetch_array($stmtDemanda, SQLSRV_FETCH_ASSOC)) {
                $idReactivo = intval($rowDemanda['Id_Reactivo'] ?? 0);
                $cantidad = floatval($rowDemanda['Cantidad_Por_Muestra'] ?? 0);
                if ($idReactivo <= 0 || $cantidad <= 0) {
                    continue;
                }
                if (!isset($totalesPorReactivo[$idReactivo])) {
                    $totalesPorReactivo[$idReactivo] = 0;
                }
                $totalesPorReactivo[$idReactivo] += $cantidad;
            }
        }

        $faltantes = [];
        foreach ($totalesPorReactivo as $idReactivo => $requeridoTotal) {
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
                throw new Exception('Error al validar stock de reactivo para duplicados: ' . print_r(sqlsrv_errors(), true));
            }

            $rowStock = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            if (!$rowStock) {
                throw new Exception('Reactivo no encontrado para duplicados: ' . $idReactivo);
            }

            $stock = floatval($rowStock['Stock'] ?? 0);
            $reservada = floatval($rowStock['Reservada'] ?? 0);
            $disponibleLibre = round($stock - $reservada, 6);

            if ($disponibleLibre < $requeridoTotal - 0.000001) {
                $faltantes[] = trim((string)($rowStock['Nombre'] ?? ('Reactivo #' . $idReactivo)))
                    . ' (disponible libre: ' . round($disponibleLibre, 4)
                    . ', requerido: ' . round($requeridoTotal, 4) . ')';
            }
        }

        if (!empty($faltantes)) {
            throw new Exception(
                'No hay reactivos suficientes para generar duplicados con la configuración actual. Recargue stock o reduzca muestras. Detalle: '
                . implode('; ', $faltantes)
            );
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

    public function obtenerMuestrasDuplicadasEnAnalisisPorDefecto($offset = 0, $limit = 10, $search = '') {
        $offset = max(0, intval($offset));
        $limit = max(1, intval($limit));
        $search = trim((string)$search);

        $sql = "SELECT m.Id_Muestra,
                       ISNULL(mb.Ubicacion_Punto, '-') AS Ubicacion_Punto,
                       ISNULL(mb.Punto_Toma, '-') AS Punto_Toma,
                       CONCAT(ISNULL(m.Eje_X, 'x:'), ' ', ISNULL(m.Eje_Y, 'y:')) AS Coordenadas,
                       m.Valle,
                       CONVERT(VARCHAR(10), m.Fecha_Creacion, 105) AS Fecha_Creacion,
                       CASE WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                            WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                            ELSE 'Sin clasificar' END AS Tipo_Muestra,
                      mb.Id_Bitacora,
                      CONVERT(VARCHAR(10), b.Fecha_Registro, 23) AS Fecha_Bitacora,
                       mb.Turno,
                       m.Estado,
                       m.Activo,
                       mb.Muestra_Original
                FROM laboratorio.Muestra_Lab m
                INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                  LEFT JOIN laboratorio.Bitacora_Control_PTA b ON b.Id_Bitacora = mb.Id_Bitacora
                LEFT JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra AND da.Activo = 1
                LEFT JOIN laboratorio.Detalle_Suelo ds ON ds.Id_Muestra = m.Id_Muestra AND ds.Activo = 1
                WHERE mb.Muestra_Original IS NOT NULL
                  AND m.Estado = 'En Analisis'";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (
                        ISNULL(mb.Punto_Toma, '') LIKE ?
                        OR ISNULL(mb.Ubicacion_Punto, '') LIKE ?
                        OR ISNULL(m.Valle, '') LIKE ?
                        OR ISNULL(mb.Turno, '') LIKE ?
                        OR CONVERT(VARCHAR(10), b.Fecha_Registro, 23) LIKE ?
                    )";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY m.Id_Muestra DESC
                  OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

        $params[] = $offset;
        $params[] = $limit;

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerMuestrasDuplicadasEnAnalisisPorDefecto: ' . print_r(sqlsrv_errors(), true));
        }

        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = $row;
        }
        return $items;
    }

    public function contarMuestrasDuplicadasEnAnalisisPorDefecto($search = '') {
        $search = trim((string)$search);

        $sql = "SELECT COUNT(*) AS total
                FROM laboratorio.Muestra_Lab m
                INNER JOIN laboratorio.Muestra_Bitacora mb ON mb.Id_Muestra = m.Id_Muestra
                                LEFT JOIN laboratorio.Bitacora_Control_PTA b ON b.Id_Bitacora = mb.Id_Bitacora
                WHERE mb.Muestra_Original IS NOT NULL
                  AND m.Estado = 'En Analisis'";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (
                        ISNULL(mb.Punto_Toma, '') LIKE ?
                        OR ISNULL(mb.Ubicacion_Punto, '') LIKE ?
                        OR ISNULL(m.Valle, '') LIKE ?
                        OR ISNULL(mb.Turno, '') LIKE ?
                        OR CONVERT(VARCHAR(10), b.Fecha_Registro, 23) LIKE ?
                    )";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en contarMuestrasDuplicadasEnAnalisisPorDefecto: ' . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['total'] ?? 0);
    }

    public function obtenerResumenBitacorasPorDefecto($fechaDesde = '', $fechaHasta = '') {
        $fechaDesde = trim((string)$fechaDesde);
        $fechaHasta = trim((string)$fechaHasta);

        $sql = "WITH Fechas AS (
                    SELECT DISTINCT CONVERT(date, b.Fecha_Registro) AS Fecha
                    FROM laboratorio.Bitacora_Control_PTA b
                    WHERE b.Activo = 1
                      AND b.Turno IN ('Mañana', 'Tarde')";

        $params = [];
        if ($fechaDesde !== '') {
            $sql .= " AND CONVERT(date, b.Fecha_Registro) >= ?";
            $params[] = $fechaDesde;
        }
        if ($fechaHasta !== '') {
            $sql .= " AND CONVERT(date, b.Fecha_Registro) <= ?";
            $params[] = $fechaHasta;
        }

        $sql .= ")
                 SELECT
                    CONVERT(VARCHAR(10), f.Fecha, 23) AS Fecha,
                    ISNULL(mn.Id_Bitacora, 0) AS Id_Bitacora_Manana,
                    ISNULL(mn.Total_Muestras, 0) AS Total_Muestras_Manana,
                    ISNULL(mn.Observacion_General, '') AS Observacion_Manana,
                    ISNULL(td.Id_Bitacora, 0) AS Id_Bitacora_Tarde,
                    ISNULL(td.Total_Muestras, 0) AS Total_Muestras_Tarde,
                    ISNULL(td.Observacion_General, '') AS Observacion_Tarde
                 FROM Fechas f
                 OUTER APPLY (
                    SELECT TOP 1
                        b.Id_Bitacora,
                        b.Observacion_General,
                        (SELECT COUNT(*)
                         FROM laboratorio.Muestra_Bitacora mb
                         WHERE mb.Id_Bitacora = b.Id_Bitacora
                           AND mb.Muestra_Original IS NOT NULL) AS Total_Muestras
                    FROM laboratorio.Bitacora_Control_PTA b
                    WHERE b.Activo = 1
                      AND b.Turno = 'Mañana'
                      AND CONVERT(date, b.Fecha_Registro) = f.Fecha
                    ORDER BY b.Id_Bitacora DESC
                 ) mn
                 OUTER APPLY (
                    SELECT TOP 1
                        b.Id_Bitacora,
                        b.Observacion_General,
                        (SELECT COUNT(*)
                         FROM laboratorio.Muestra_Bitacora mb
                         WHERE mb.Id_Bitacora = b.Id_Bitacora
                           AND mb.Muestra_Original IS NOT NULL) AS Total_Muestras
                    FROM laboratorio.Bitacora_Control_PTA b
                    WHERE b.Activo = 1
                      AND b.Turno = 'Tarde'
                      AND CONVERT(date, b.Fecha_Registro) = f.Fecha
                    ORDER BY b.Id_Bitacora DESC
                 ) td
                 ORDER BY f.Fecha DESC";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerResumenBitacorasPorDefecto: ' . print_r(sqlsrv_errors(), true));
        }

        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = $row;
        }
        return $items;
    }

    public function crearBitacoraPorDefectoTurno($fechaRegistro, $turno, $observacion = '') {
        $fechaRegistro = trim((string)$fechaRegistro);
        $turno = trim((string)$turno);
        $observacion = trim((string)$observacion);
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        if ($fechaRegistro === '') {
            throw new Exception('Debe seleccionar una fecha.');
        }
        if ($turno !== 'Mañana' && $turno !== 'Tarde') {
            throw new Exception('El turno es inválido.');
        }

        $stmtExiste = sqlsrv_query(
            $this->db,
            "SELECT TOP 1 Id_Bitacora
             FROM laboratorio.Bitacora_Control_PTA
             WHERE Activo = 1
               AND Turno = ?
               AND CONVERT(date, Fecha_Registro) = CONVERT(date, ?)
             ORDER BY Id_Bitacora DESC",
            [$turno, $fechaRegistro]
        );
        if ($stmtExiste === false) {
            throw new Exception('Error al validar bitácora existente: ' . print_r(sqlsrv_errors(), true));
        }

        $existe = sqlsrv_fetch_array($stmtExiste, SQLSRV_FETCH_ASSOC);
        if ($existe && intval($existe['Id_Bitacora'] ?? 0) > 0) {
            throw new Exception('Ya existe una bitácora para esa fecha y turno.');
        }

        $stmtInsert = sqlsrv_query(
            $this->db,
            "INSERT INTO laboratorio.Bitacora_Control_PTA
             (Fecha_Registro, Turno, Observacion_General, Id_Responsable, Activo, Fecha_Creacion, Usuario_Creacion)
             VALUES (?, ?, ?, ?, 1, GETDATE(), ?);
             SELECT SCOPE_IDENTITY() AS id;",
            [$fechaRegistro, $turno, $observacion !== '' ? $observacion : null, $usuarioId, $usuarioId]
        );
        if ($stmtInsert === false) {
            throw new Exception('Error al crear bitácora: ' . print_r(sqlsrv_errors(), true));
        }

        sqlsrv_next_result($stmtInsert);
        $row = sqlsrv_fetch_array($stmtInsert, SQLSRV_FETCH_ASSOC);
        $idBitacora = intval($row['id'] ?? 0);
        if ($idBitacora <= 0) {
            throw new Exception('No se pudo obtener el ID de la bitácora creada.');
        }

        return $idBitacora;
    }

    public function actualizarObservacionBitacoraPorDefecto($idBitacora, $observacion) {
        $idBitacora = intval($idBitacora);
        $observacion = trim((string)$observacion);

        if ($idBitacora <= 0) {
            throw new Exception('ID de bitácora inválido.');
        }

        $stmt = sqlsrv_query(
            $this->db,
            "UPDATE laboratorio.Bitacora_Control_PTA
             SET Observacion_General = ?, Fecha_Modificacion = GETDATE(), Usuario_Modificacion = ?
             WHERE Id_Bitacora = ? AND Activo = 1",
            [$observacion !== '' ? $observacion : null, $_SESSION['usuario_id'] ?? 1, $idBitacora]
        );
        if ($stmt === false) {
            throw new Exception('Error al actualizar observación de bitácora: ' . print_r(sqlsrv_errors(), true));
        }

        return true;
    }

    public function obtenerBitacoraPorId($idBitacora) {
        $idBitacora = intval($idBitacora);
        if ($idBitacora <= 0) {
            return null;
        }

        $stmt = sqlsrv_query(
            $this->db,
            "SELECT TOP 1
                b.Id_Bitacora,
                CONVERT(VARCHAR(10), b.Fecha_Registro, 23) AS Fecha_Registro,
                b.Turno,
                ISNULL(b.Observacion_General, '') AS Observacion_General,
                (SELECT COUNT(*) FROM laboratorio.Muestra_Bitacora mb WHERE mb.Id_Bitacora = b.Id_Bitacora AND mb.Muestra_Original IS NOT NULL) AS Total_Muestras
             FROM laboratorio.Bitacora_Control_PTA b
             WHERE b.Id_Bitacora = ? AND b.Activo = 1",
            [$idBitacora]
        );
        if ($stmt === false) {
            throw new Exception('Error en obtenerBitacoraPorId: ' . print_r(sqlsrv_errors(), true));
        }

        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: null;
    }

    public function obtenerBitacorasPorFechaDefecto($fechaRegistro) {
        $fechaRegistro = trim((string)$fechaRegistro);
        if ($fechaRegistro === '') {
            throw new Exception('Debe indicar una fecha válida.');
        }

        $sql = "WITH UltimasBitacoras AS (
                    SELECT
                        b.Turno,
                        b.Id_Bitacora,
                        ISNULL(b.Observacion_General, '') AS Observacion_General,
                        ROW_NUMBER() OVER (PARTITION BY b.Turno ORDER BY b.Id_Bitacora DESC) AS rn
                    FROM laboratorio.Bitacora_Control_PTA b
                    WHERE b.Activo = 1
                      AND b.Turno IN ('Mañana', 'Tarde')
                      AND CONVERT(date, b.Fecha_Registro) = CONVERT(date, ?)
                 )
                 SELECT
                    ub.Turno,
                    ub.Id_Bitacora,
                    ub.Observacion_General,
                    (SELECT COUNT(*)
                     FROM laboratorio.Muestra_Bitacora mb
                     WHERE mb.Id_Bitacora = ub.Id_Bitacora
                       AND mb.Muestra_Original IS NOT NULL) AS Total_Muestras
                 FROM UltimasBitacoras ub
                 WHERE ub.rn = 1";

        $stmt = sqlsrv_query($this->db, $sql, [$fechaRegistro]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerBitacorasPorFechaDefecto: ' . print_r(sqlsrv_errors(), true));
        }

        $respuesta = [
            'Mañana' => [
                'Id_Bitacora' => 0,
                'Turno' => 'Mañana',
                'Observacion_General' => '',
                'Total_Muestras' => 0
            ],
            'Tarde' => [
                'Id_Bitacora' => 0,
                'Turno' => 'Tarde',
                'Observacion_General' => '',
                'Total_Muestras' => 0
            ]
        ];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $turno = (string)($row['Turno'] ?? '');
            if ($turno === 'Mañana' || $turno === 'Tarde') {
                $respuesta[$turno] = [
                    'Id_Bitacora' => intval($row['Id_Bitacora'] ?? 0),
                    'Turno' => $turno,
                    'Observacion_General' => (string)($row['Observacion_General'] ?? ''),
                    'Total_Muestras' => intval($row['Total_Muestras'] ?? 0)
                ];
            }
        }

        return $respuesta;
    }

    public function obtenerResultadosPorBitacora($idBitacora) {
        $idBitacora = intval($idBitacora);
        if ($idBitacora <= 0) {
            throw new Exception('ID de bitácora inválido.');
        }

        $sql = "SELECT
                    m.Id_Muestra,
                    ISNULL(mb.Ubicacion_Punto, '-') AS Ubicacion_Punto,
                    ISNULL(mb.Punto_Toma, '-') AS Punto_Toma,
                    ISNULL(pa.Nombre, '(sin parámetro)') AS Parametro,
                    ISNULL(pa.Unidad_Medida, '') AS Unidad,
                    ISNULL(CAST(ra.Valor_Hallado AS VARCHAR(100)), '') AS Valor_Hallado,
                    m.Estado
                FROM laboratorio.Muestra_Bitacora mb
                INNER JOIN laboratorio.Muestra_Lab m ON m.Id_Muestra = mb.Id_Muestra AND m.Activo = 1
                LEFT JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Muestra = m.Id_Muestra AND sa.Activo = 1
                LEFT JOIN laboratorio.Resultado_Analisis ra ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis AND ra.Activo = 1
                LEFT JOIN laboratorio.Parametro_Analisis pa ON pa.Id_Parametro = ra.Id_Parametro
                WHERE mb.Id_Bitacora = ?
                ORDER BY m.Id_Muestra ASC, pa.Nombre ASC";

        $stmt = sqlsrv_query($this->db, $sql, [$idBitacora]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerResultadosPorBitacora: ' . print_r(sqlsrv_errors(), true));
        }

        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = $row;
        }
        return $items;
    }

    public function bitacoraTieneAnalisisPendiente($idBitacora) {
        $idBitacora = intval($idBitacora);
        if ($idBitacora <= 0) {
            return false;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM laboratorio.Muestra_Bitacora mb
                INNER JOIN laboratorio.Muestra_Lab m ON m.Id_Muestra = mb.Id_Muestra
                WHERE mb.Id_Bitacora = ?
                  AND m.Activo = 1
                  AND m.Estado = 'En Analisis'";

        $stmt = sqlsrv_query($this->db, $sql, [$idBitacora]);
        if ($stmt === false) {
            throw new Exception('Error en bitacoraTieneAnalisisPendiente: ' . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['total'] ?? 0) > 0;
    }

    public function obtenerBitacorasTurnoParaExportacion($fechaDesde, $fechaHasta) {
        $fechaDesde = trim((string)$fechaDesde);
        $fechaHasta = trim((string)$fechaHasta);

        if ($fechaDesde === '' || $fechaHasta === '') {
            throw new Exception('Debe indicar fecha inicio y fecha fin para exportar.');
        }

        $sql = "SELECT
                    CONVERT(VARCHAR(10), b.Fecha_Registro, 23) AS Fecha,
                    b.Turno,
                    b.Id_Bitacora,
                    ISNULL(b.Observacion_General, '') AS Observacion_General,
                    (SELECT COUNT(*)
                     FROM laboratorio.Muestra_Bitacora mb
                     WHERE mb.Id_Bitacora = b.Id_Bitacora
                       AND mb.Muestra_Original IS NOT NULL) AS Total_Muestras
                FROM laboratorio.Bitacora_Control_PTA b
                WHERE b.Activo = 1
                  AND b.Turno IN ('Mañana', 'Tarde')
                  AND CONVERT(date, b.Fecha_Registro) BETWEEN CONVERT(date, ?) AND CONVERT(date, ?)
                ORDER BY CONVERT(date, b.Fecha_Registro) ASC,
                         CASE WHEN b.Turno = 'Mañana' THEN 1 ELSE 2 END ASC,
                         b.Id_Bitacora ASC";

        $stmt = sqlsrv_query($this->db, $sql, [$fechaDesde, $fechaHasta]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerBitacorasTurnoParaExportacion: ' . print_r(sqlsrv_errors(), true));
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function obtenerDetalleBitacorasPorDefectoParaExportacion($fechaDesde, $fechaHasta) {
        $fechaDesde = trim((string)$fechaDesde);
        $fechaHasta = trim((string)$fechaHasta);

        if ($fechaDesde === '' || $fechaHasta === '') {
            throw new Exception('Debe indicar fecha inicio y fecha fin para exportar.');
        }

        $sql = "SELECT
                    CONVERT(VARCHAR(10), b.Fecha_Registro, 23) AS Fecha,
                    b.Turno,
                    b.Id_Bitacora,
                    ISNULL(b.Observacion_General, '') AS Observacion_General,
                    m.Id_Muestra,
                    ISNULL(mb.Ubicacion_Punto, '') AS Ubicacion_Punto,
                    ISNULL(mb.Punto_Toma, '') AS Punto_Toma,
                    CONVERT(VARCHAR(5), m.Fecha_Toma, 108) AS Hora_Muestreo,
                    ISNULL(ra.Id_Parametro, 0) AS Id_Parametro,
                    ISNULL(pa.Nombre, '') AS Parametro,
                    ISNULL(NULLIF(ll.Unidad_Medida, ''), ISNULL(pa.Unidad_Medida, '')) AS Unidad,
                    ll.Valor_Min,
                    ll.Valor_Max,
                    ISNULL(CAST(ra.Valor_Hallado AS VARCHAR(100)), '') AS Valor_Hallado
                FROM laboratorio.Bitacora_Control_PTA b
                LEFT JOIN laboratorio.Muestra_Bitacora mb
                    ON mb.Id_Bitacora = b.Id_Bitacora
                   AND mb.Muestra_Original IS NOT NULL
                LEFT JOIN laboratorio.Muestra_Lab m
                    ON m.Id_Muestra = mb.Id_Muestra
                   AND m.Activo = 1
                LEFT JOIN laboratorio.Solicitud_Analisis sa
                    ON sa.Id_Muestra = m.Id_Muestra
                   AND sa.Activo = 1
                LEFT JOIN laboratorio.Resultado_Analisis ra
                    ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                   AND ra.Activo = 1
                LEFT JOIN laboratorio.Parametro_Analisis pa
                    ON pa.Id_Parametro = ra.Id_Parametro
                                OUTER APPLY (
                                        SELECT TOP 1 l.Valor_Min, l.Valor_Max, l.Unidad_Medida
                                        FROM laboratorio.Limite_Legal l
                                        WHERE l.Id_Parametro = ra.Id_Parametro
                                            AND l.Activo = 1
                                        ORDER BY l.Id_Limite_Legal DESC
                                ) ll
                WHERE b.Activo = 1
                  AND b.Turno IN ('Mañana', 'Tarde')
                  AND CONVERT(date, b.Fecha_Registro) BETWEEN CONVERT(date, ?) AND CONVERT(date, ?)
                ORDER BY CONVERT(date, b.Fecha_Registro) ASC,
                         CASE WHEN b.Turno = 'Mañana' THEN 1 ELSE 2 END ASC,
                         b.Id_Bitacora ASC,
                         m.Id_Muestra ASC,
                         pa.Nombre ASC";

        $stmt = sqlsrv_query($this->db, $sql, [$fechaDesde, $fechaHasta]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerDetalleBitacorasPorDefectoParaExportacion: ' . print_r(sqlsrv_errors(), true));
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
?>
