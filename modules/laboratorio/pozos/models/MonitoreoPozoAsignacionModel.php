<?php
class MonitoreoPozoAsignacionModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerAsignacionesPorProyecto($id_proyecto, $solo_activas = true) {
        $sql = "SELECT mpa.*, cp.Id_Pozo AS Codigo_Pozo, cp.valle, cp.ubicacion, cp.propietario,
                       cp.coord_este, cp.coord_norte, cp.tipopozo
                FROM laboratorio.Monitoreo_Pozo_Asignacion mpa
                INNER JOIN laboratorio.Catastro_Pozo cp ON mpa.Id_Pozo = cp.Id_Pozo
                WHERE mpa.Id_Proyecto = ?";
        $params = [$id_proyecto];

        if ($solo_activas) {
            $sql .= " AND mpa.Activo = 1";
        }

        $sql .= " ORDER BY ISNULL(mpa.Orden, 0), mpa.Numero_Muestra";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerAsignacionesPorProyecto: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function contarAsignacionesPorProyecto($id_proyecto, $solo_activas = true) {
        $sql = "SELECT COUNT(*) AS total FROM laboratorio.Monitoreo_Pozo_Asignacion WHERE Id_Proyecto = ?";
        $params = [$id_proyecto];
        if ($solo_activas) {
            $sql .= " AND Activo = 1";
        }
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en contarAsignacionesPorProyecto: ' . print_r(sqlsrv_errors(), true));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['total'] ?? 0);
    }

    public function obtenerAsignacionPorSlot($id_proyecto, $numero_muestra, $orden = null) {
        $sql = "SELECT * FROM laboratorio.Monitoreo_Pozo_Asignacion
                WHERE Id_Proyecto = ? AND Numero_Muestra = ? AND Activo = 1";
        $params = [$id_proyecto, $numero_muestra];
        
        if ($orden !== null) {
            $sql .= " AND Orden = ?";
            $params[] = $orden;
        }
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerAsignacionPorSlot: ' . print_r(sqlsrv_errors(), true));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardarAsignacion($datos) {
        $id_proyecto   = intval($datos['Id_Proyecto'] ?? 0);
        $id_pozo       = strtoupper(trim((string)($datos['Id_Pozo'] ?? '')));
        $numero_muestra= intval($datos['Numero_Muestra'] ?? 0);
        $orden         = isset($datos['Orden']) ? intval($datos['Orden']) : null;
        $es_lab        = isset($datos['Es_Analisis_Laboratorio']) ? (int)(bool)$datos['Es_Analisis_Laboratorio'] : 0;
        $usuario_id    = $_SESSION['usuario_id'] ?? 1;

        if ($id_proyecto <= 0 || $id_pozo === '' || $numero_muestra <= 0) {
            throw new Exception('Datos incompletos para la asignacion.');
        }

        // Desactivar asignacion anterior activa para este slot (Proyecto + Orden + Muestra)
        $sqlDes = "UPDATE laboratorio.Monitoreo_Pozo_Asignacion
                   SET Activo = 0, Fecha_Modificacion = GETDATE()
                   WHERE Id_Proyecto = ? AND Numero_Muestra = ? AND Orden = ? AND Activo = 1";
        sqlsrv_query($this->db, $sqlDes, [$id_proyecto, $numero_muestra, $orden]);

        $sqlIns = "INSERT INTO laboratorio.Monitoreo_Pozo_Asignacion
                   (Id_Proyecto, Id_Pozo, Numero_Muestra, Orden, Es_Analisis_Laboratorio, Activo, Fecha_Creacion, Usuario_Creacion)
                   VALUES (?, ?, ?, ?, ?, 1, GETDATE(), ?)";
        $stmt = sqlsrv_query($this->db, $sqlIns, [$id_proyecto, $id_pozo, $numero_muestra, $orden, $es_lab, $usuario_id]);
        if ($stmt === false) {
            throw new Exception('Error al guardar asignacion: ' . print_r(sqlsrv_errors(), true));
        }
        return true;
    }

    public function guardarAsignacionesBatch($id_proyecto, array $asignaciones) {
        $id_proyecto = intval($id_proyecto);
        $usuario_id  = $_SESSION['usuario_id'] ?? 1;
        $guardadas   = 0;

        foreach ($asignaciones as $asig) {
            $this->guardarAsignacion([
                'Id_Proyecto'             => $id_proyecto,
                'Id_Pozo'                 => $asig['Id_Pozo'] ?? '',
                'Numero_Muestra'          => $asig['Numero_Muestra'] ?? 0,
                'Orden'                   => $asig['Orden'] ?? null,
                'Es_Analisis_Laboratorio' => $asig['Es_Analisis_Laboratorio'] ?? 0
            ]);
            $guardadas++;
        }

        return $guardadas;
    }

    public function copiarAsignacionesDeProyecto($id_proyecto_origen, $id_proyecto_destino) {
        $asignaciones = $this->obtenerAsignacionesPorProyecto($id_proyecto_origen, true);
        if (empty($asignaciones)) {
            throw new Exception('El proyecto origen no tiene asignaciones activas.');
        }

        $usuario_id = $_SESSION['usuario_id'] ?? 1;
        $copiadas = 0;

        foreach ($asignaciones as $a) {
            $sqlIns = "INSERT INTO laboratorio.Monitoreo_Pozo_Asignacion
                       (Id_Proyecto, Id_Pozo, Numero_Muestra, Orden, Es_Analisis_Laboratorio, Activo, Fecha_Creacion, Usuario_Creacion)
                       VALUES (?, ?, ?, ?, ?, 1, GETDATE(), ?)";
            $stmt = sqlsrv_query($this->db, $sqlIns, [
                $id_proyecto_destino,
                trim((string)$a['Id_Pozo']),
                intval($a['Numero_Muestra']),
                intval($a['Orden'] ?? 0),
                intval($a['Es_Analisis_Laboratorio'] ?? 0),
                $usuario_id
            ]);
            if ($stmt === false) {
                throw new Exception('Error al copiar asignacion: ' . print_r(sqlsrv_errors(), true));
            }
            $copiadas++;
        }

        return $copiadas;
    }

    public function obtenerHistorialAsignaciones($id_pozo) {
        $sql = "SELECT mpa.*,
                       pm.Nombre_Proyecto, pm.Temporada, pm.Estado AS Estado_Proyecto
                FROM laboratorio.Monitoreo_Pozo_Asignacion mpa
                INNER JOIN laboratorio.Proyecto_Monitoreo pm ON mpa.Id_Proyecto = pm.Id_Proyecto
                WHERE mpa.Id_Pozo = ?
                ORDER BY pm.Fecha_Creacion DESC, ISNULL(mpa.Orden, 0), mpa.Numero_Muestra";
        $stmt = sqlsrv_query($this->db, $sql, [$id_pozo]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerHistorialAsignaciones: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerAsignacionesPorProyectoDataTable($id_proyecto, $start, $length, $search = '') {
        $sql = "SELECT mpa.*, cp.valle, cp.ubicacion, cp.tipopozo,
                       cp.coord_este, cp.coord_norte
                FROM laboratorio.Monitoreo_Pozo_Asignacion mpa
                INNER JOIN laboratorio.Catastro_Pozo cp ON mpa.Id_Pozo = cp.Id_Pozo
                WHERE mpa.Id_Proyecto = ? AND mpa.Activo = 1";
        $params = [$id_proyecto];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= " AND (mpa.Id_Pozo LIKE ? OR cp.valle LIKE ? OR cp.ubicacion LIKE ?
                      OR CAST(mpa.Numero_Muestra AS NVARCHAR) LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY ISNULL(mpa.Orden, 0), mpa.Numero_Muestra OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        $params[] = $start;
        $params[] = $length;

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerAsignacionesPorProyectoDataTable: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerAsignacionesPrevias($valle) {
        $sql = "SELECT TOP 1 mpa.Id_Proyecto
                FROM laboratorio.Monitoreo_Pozo_Asignacion mpa
                INNER JOIN laboratorio.Proyecto_Monitoreo pm ON mpa.Id_Proyecto = pm.Id_Proyecto
                INNER JOIN laboratorio.Catastro_Pozo cp ON mpa.Id_Pozo = cp.Id_Pozo
                WHERE pm.Es_Pozos = 1 AND cp.valle = ? AND mpa.Activo = 1
                ORDER BY pm.Fecha_Creacion DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$valle]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerAsignacionesPrevias: ' . print_r(sqlsrv_errors(), true));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            return $this->obtenerAsignacionesPorProyecto($row['Id_Proyecto'], true);
        }
        return [];
    }

    public function autoAsignarLaboratorio($id_proyecto, $cantidad_lab, $valle = null) {
        $asignaciones = $this->obtenerAsignacionesPorProyecto($id_proyecto, true);
        if (empty($asignaciones)) {
            return 0;
        }

        $asignados = 0;
        
        // 1. Intentar usar la última asignación de ese valle si existe
        if ($valle) {
            $previas = $this->obtenerAsignacionesPrevias($valle);
            $pozos_lab_previos = [];
            foreach ($previas as $p) {
                if (intval($p['Es_Analisis_Laboratorio']) === 1) {
                    $pozos_lab_previos[] = $p['Id_Pozo'];
                }
            }

            if (!empty($pozos_lab_previos)) {
                foreach ($asignaciones as $a) {
                    if ($asignados >= $cantidad_lab) break;
                    if (in_array($a['Id_Pozo'], $pozos_lab_previos)) {
                        $this->marcarParaLaboratorio($id_proyecto, $a['Numero_Muestra']);
                        $asignados++;
                    }
                }
                
                if ($asignados >= $cantidad_lab) {
                    return $asignados;
                }
            }
        }

        // 2. Matching inteligente (CHAVI-0004 -> muestra 04)
        foreach ($asignaciones as $a) {
            if ($asignados >= $cantidad_lab) break;
            
            if (isset($a['Es_Analisis_Laboratorio']) && intval($a['Es_Analisis_Laboratorio']) === 1) {
                continue; 
            }

            $id_pozo = trim((string)$a['Id_Pozo']);
            if (preg_match('/(\d+)$/', $id_pozo, $matches)) {
                $num_pozo = intval($matches[1]);
                $num_muestra = intval($a['Numero_Muestra']);
                
                if ($num_pozo === $num_muestra) {
                    $this->marcarParaLaboratorio($id_proyecto, $num_muestra);
                    $asignados++;
                }
            }
        }
        
        // 3. Si aun faltan, asignar los primeros disponibles
        if ($asignados < $cantidad_lab) {
            $asignaciones_actualizadas = $this->obtenerAsignacionesPorProyecto($id_proyecto, true);
            foreach ($asignaciones_actualizadas as $a) {
                if ($asignados >= $cantidad_lab) break;
                if (intval($a['Es_Analisis_Laboratorio']) === 0) {
                    $this->marcarParaLaboratorio($id_proyecto, $a['Numero_Muestra']);
                    $asignados++;
                }
            }
        }

        return $asignados;
    }

    private function marcarParaLaboratorio($id_proyecto, $numero_muestra) {
        $sql = "UPDATE laboratorio.Monitoreo_Pozo_Asignacion 
                SET Es_Analisis_Laboratorio = 1, Fecha_Modificacion = GETDATE() 
                WHERE Id_Proyecto = ? AND Numero_Muestra = ? AND Activo = 1";
        sqlsrv_query($this->db, $sql, [$id_proyecto, $numero_muestra]);
    }
}
