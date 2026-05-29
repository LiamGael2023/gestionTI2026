<?php
class EquipoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->migrarColumnas();
    }

    /**
     * Agrega columnas nuevas a Equipo_Lab si no existen todavía.
     * Se ejecuta una vez por petición (las IF son baratas).
     */
    private function migrarColumnas() {
        $migraciones = [
            "IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='laboratorio' AND TABLE_NAME='Equipo_Lab' AND COLUMN_NAME='Id_Proveedor')
             ALTER TABLE laboratorio.Equipo_Lab ADD Id_Proveedor INT NULL",

            "IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='laboratorio' AND TABLE_NAME='Equipo_Lab' AND COLUMN_NAME='Fecha_Adquisicion')
             ALTER TABLE laboratorio.Equipo_Lab ADD Fecha_Adquisicion DATE NULL",
        ];
        foreach ($migraciones as $sql) {
            sqlsrv_query($this->db, $sql); // ignorar errores no críticos
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT e.*, es.Nombre AS Estado_Nombre FROM laboratorio.Equipo_Lab e JOIN laboratorio.Equipo_Estado es ON e.Id_Estado = es.Id_Estado WHERE e.Activo = 1 ORDER BY e.Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Equipos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT e.*, es.Nombre AS Estado_Nombre FROM laboratorio.Equipo_Lab e JOIN laboratorio.Equipo_Estado es ON e.Id_Estado = es.Id_Estado WHERE e.Id_Equipo = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Equipo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Equipo'])) {
            // INSERT
            $sql = "INSERT INTO laboratorio.Equipo_Lab
                        (Id_Estado, Nombre, Proveedor, Id_Proveedor, Fecha_Adquisicion,
                         Fecha_Ultima_Calibracion, Fecha_Proxima_Calibracion, Usuario_Creacion, Activo, Fecha_Creacion)
                    OUTPUT INSERTED.Id_Equipo AS id
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, GETDATE())";
            $params = array(
                $datos['Id_Estado'],
                $datos['Nombre'],
                $datos['Proveedor'] ?? null,
                !empty($datos['Id_Proveedor']) ? $datos['Id_Proveedor'] : null,
                !empty($datos['Fecha_Adquisicion']) ? $datos['Fecha_Adquisicion'] : null,
                !empty($datos['Fecha_Ultima_Calibracion']) ? $datos['Fecha_Ultima_Calibracion'] : null,
                !empty($datos['Fecha_Proxima_Calibracion']) ? $datos['Fecha_Proxima_Calibracion'] : null,
                $_SESSION['usuario_id'] ?? 1
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // UPDATE
            $sql    = "UPDATE laboratorio.Equipo_Lab SET Id_Estado=?, Nombre=?, Proveedor=?, Id_Proveedor=?";
            $params = array(
                $datos['Id_Estado'],
                $datos['Nombre'],
                $datos['Proveedor'] ?? null,
                !empty($datos['Id_Proveedor']) ? $datos['Id_Proveedor'] : null
            );

            if (!empty($datos['Fecha_Adquisicion'])) {
                $sql .= ", Fecha_Adquisicion=?";
                $params[] = $datos['Fecha_Adquisicion'];
            }
            if (!empty($datos['Fecha_Ultima_Calibracion'])) {
                $sql .= ", Fecha_Ultima_Calibracion=?";
                $params[] = $datos['Fecha_Ultima_Calibracion'];
            }
            if (!empty($datos['Fecha_Proxima_Calibracion'])) {
                $sql .= ", Fecha_Proxima_Calibracion=?";
                $params[] = $datos['Fecha_Proxima_Calibracion'];
            }

            $sql      .= ", Fecha_Modificacion=GETDATE() WHERE Id_Equipo=?";
            $params[]  = $datos['Id_Equipo'];

            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Equipo'];
        }
    }

    public function actualizarEstado($idEquipo, $idEstado, $actualizarFechaUltima = false, $fechaProxima = null) {
        $sql    = "UPDATE laboratorio.Equipo_Lab SET Id_Estado=?, Fecha_Modificacion=GETDATE()";
        $params = [$idEstado];
        if ($actualizarFechaUltima) {
            $sql .= ", Fecha_Ultima_Calibracion=CAST(GETDATE() AS DATE)";
        }
        if ($fechaProxima !== null) {
            $sql .= ", Fecha_Proxima_Calibracion=?";
            $params[] = $fechaProxima;
        }
        $sql      .= " WHERE Id_Equipo=?";
        $params[]  = $idEquipo;
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error actualizando estado: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function registrarObservacionCalibracion($idEquipo, $observacion) {
        // Auto-crear tabla si no existe aún en la BD
        $createSql = "
            IF OBJECT_ID('laboratorio.Observacion_Calibracion', 'U') IS NULL
            BEGIN
                CREATE TABLE laboratorio.Observacion_Calibracion (
                    Id_Observacion_Cal   INT IDENTITY(1,1) PRIMARY KEY,
                    Id_Equipo            INT NOT NULL,
                    Observacion          NVARCHAR(MAX) NOT NULL,
                    Fecha_Observacion    DATETIME NOT NULL,
                    Usuario_Creacion     INT NULL,
                    Activo               BIT NOT NULL DEFAULT 1,
                    Fecha_Creacion       DATETIME NOT NULL DEFAULT GETDATE(),
                    CONSTRAINT FK_ObsCal_Equipo FOREIGN KEY (Id_Equipo)
                        REFERENCES laboratorio.Equipo_Lab(Id_Equipo)
                )
            END";
        $stmtCreate = sqlsrv_query($this->db, $createSql);
        if ($stmtCreate === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error creando tabla de observaciones: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }

        $sql    = "INSERT INTO laboratorio.Observacion_Calibracion
                      (Id_Equipo, Observacion, Fecha_Observacion, Usuario_Creacion, Activo, Fecha_Creacion)
                  VALUES (?, ?, GETDATE(), ?, 1, GETDATE())";
        $params = [$idEquipo, $observacion, $_SESSION['usuario_id'] ?? 1];
        $stmt   = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error guardando observación: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function obtenerIdEstadoDisponible() {
        $stmt = sqlsrv_query($this->db,
            "SELECT TOP 1 Id_Estado FROM laboratorio.Equipo_Estado WHERE LOWER(Nombre) = 'disponible' AND Activo = 1"
        );
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? $row['Id_Estado'] : null;
    }

    public function obtenerHistorialCalibracion($idEquipo) {
        $sql = "SELECT oc.Id_Observacion_Cal,
                       oc.Observacion,
                       FORMAT(oc.Fecha_Observacion, 'dd/MM/yyyy HH:mm') AS Fecha_Observacion,
                       ISNULL(u.nombres + ' ' + u.apellidos, u.usuario) AS Usuario
                FROM laboratorio.Observacion_Calibracion oc
                LEFT JOIN comun.Usuarios u ON oc.Usuario_Creacion = u.id_usuario
                WHERE oc.Id_Equipo = ? AND oc.Activo = 1
                ORDER BY oc.Fecha_Observacion DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$idEquipo]);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function eliminar($id) {
        // Verificar si el equipo está ligado a servicios activos
        $sql_check = "SELECT COUNT(*) AS count FROM laboratorio.Requisito_Equipo WHERE Id_Equipo = ? AND Activo = 1";
        $stmt_check = sqlsrv_query($this->db, $sql_check, array($id));
        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        
        if ($row_check['count'] > 0) {
            throw new Exception('No se puede eliminar este equipo porque está ligado a ' . $row_check['count'] . ' servicio(s). Primero debes eliminarlo de los servicios.');
        }
        
        $sql = "UPDATE laboratorio.Equipo_Lab SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Equipo = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function obtenerEstados() {
        $sql = "SELECT * FROM laboratorio.Equipo_Estado WHERE Activo = 1 ORDER BY Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Estados: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerRequisitosPorServicio($idServicio) {
        $sql = "SELECT re.*, e.Nombre AS laboratorio.Equipo_Nombre FROM laboratorio.Requisito_Equipo re JOIN laboratorio.Equipo_Lab e ON re.Id_Equipo = e.Id_Equipo WHERE re.Id_Servicio = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Equipo_Lab SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Equipo = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }
}
