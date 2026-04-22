<?php
class ServicioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM laboratorio.Servicio_Tecnico WHERE Activo = 1 ORDER BY Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Servicios: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Servicio_Tecnico WHERE Id_Servicio = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Servicio: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Servicio'])) {
            // INSERT - Agregar columna Requiere_Reactivos si existe en la tabla
            $sql = "INSERT INTO laboratorio.Servicio_Tecnico (Nombre, Descripcion, Tipo_Muestra, Usuario_Creacion, Activo, Fecha_Creacion)";
            
            // Verificar si existe la columna Requiere_Reactivos
            $sqlCheck = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Servicio_Tecnico' AND COLUMN_NAME = 'Requiere_Reactivos'";
            $stmtCheck = sqlsrv_query($this->db, $sqlCheck);
            $tieneColumna = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) !== null;
            
            if ($tieneColumna) {
                $sql = "INSERT INTO laboratorio.Servicio_Tecnico (Nombre, Descripcion, Tipo_Muestra, Requiere_Reactivos, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                $params = array(
                    $datos['Nombre'],
                    $datos['Descripcion'] ?? null,
                    $datos['Tipo_Muestra'],
                    $datos['Requiere_Reactivos'] ?? 1,  // Por defecto true
                    $_SESSION['usuario_id'] ?? 1
                );
            } else {
                $sql = "INSERT INTO laboratorio.Servicio_Tecnico (Nombre, Descripcion, Tipo_Muestra, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                $params = array(
                    $datos['Nombre'],
                    $datos['Descripcion'] ?? null,
                    $datos['Tipo_Muestra'],
                    $_SESSION['usuario_id'] ?? 1
                );
            }
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Servicio: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // UPDATE
            $sql = "UPDATE laboratorio.Servicio_Tecnico SET Nombre=?, Descripcion=?, Tipo_Muestra=?, Fecha_Modificacion=GETDATE() WHERE Id_Servicio=?";
            
            // Verificar si existe la columna Requiere_Reactivos
            $sqlCheck = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Servicio_Tecnico' AND COLUMN_NAME = 'Requiere_Reactivos'";
            $stmtCheck = sqlsrv_query($this->db, $sqlCheck);
            $tieneColumna = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) !== null;
            
            if ($tieneColumna) {
                $sql = "UPDATE laboratorio.Servicio_Tecnico SET Nombre=?, Descripcion=?, Tipo_Muestra=?, Requiere_Reactivos=?, Fecha_Modificacion=GETDATE() WHERE Id_Servicio=?";
                $params = array(
                    $datos['Nombre'],
                    $datos['Descripcion'] ?? null,
                    $datos['Tipo_Muestra'],
                    $datos['Requiere_Reactivos'] ?? 1,
                    $datos['Id_Servicio']
                );
            } else {
                $params = array(
                    $datos['Nombre'],
                    $datos['Descripcion'] ?? null,
                    $datos['Tipo_Muestra'],
                    $datos['Id_Servicio']
                );
            }
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Servicio: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Servicio'];
        }
    }

    public function eliminar($id) {
        // Verificar si el servicio está ligado a ventas activas
        $sql_check = "SELECT COUNT(*) AS count FROM laboratorio.Producto_Servicio WHERE Id_Servicio = ? AND Activo = 1";
        $stmt_check = sqlsrv_query($this->db, $sql_check, array($id));
        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        
        if ($row_check['count'] > 0) {
            throw new Exception('No se puede eliminar este servicio porque está ligado a venta(s). Primero debes eliminarlo de los productos de venta.');
        }
        
        $sql = "UPDATE laboratorio.Servicio_Tecnico SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Servicio = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al eliminar servicio: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function obtenerProductos($idServicio) {
        $sql = "SELECT ps.*, pv.Nombre_Comercial FROM laboratorio.Producto_Servicio ps JOIN laboratorio.Producto_Venta pv ON ps.Id_Producto = pv.Id_Producto WHERE ps.Id_Servicio = ? AND ps.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarProductoServicio($idProducto, $idServicio) {
        $sql = "INSERT INTO laboratorio.Producto_Servicio (Id_Producto, Id_Servicio, Usuario_Creacion, Activo) VALUES (?, ?, ?, 1)";
        sqlsrv_query($this->db, $sql, array($idProducto, $idServicio, $_SESSION['usuario_id'] ?? 1));
    }

    public function obtenerRecetas($idServicio) {
        $sql = "SELECT rs.*, r.Nombre AS Reactivo_Nombre FROM laboratorio.Receta_Servicio rs JOIN laboratorio.Reactivo_Lab r ON rs.Id_Reactivo = r.Id_Reactivo WHERE rs.Id_Servicio = ? AND rs.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarReceta($datos) {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;
        $idReactivo = $datos['Id_Reactivo'];
        $idServicio = $datos['Id_Servicio'];
        $cantidadNecesaria = $datos['Cantidad_Necesaria'] ?? 0;
        
        // PRIMERO: Verificar si ya existe un registro (activo o inactivo)
        $sqlCheck = "SELECT COUNT(*) as total FROM laboratorio.Receta_Servicio 
                     WHERE Id_Reactivo = ? AND Id_Servicio = ?";
        $stmtCheck = sqlsrv_query($this->db, $sqlCheck, array($idReactivo, $idServicio));
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        
        if ($rowCheck['total'] > 0) {
            // YA EXISTE: Hacer UPDATE (reactivar si estaba inactivo + actualizar cantidad)
            $sql = "UPDATE laboratorio.Receta_Servicio 
                    SET Cantidad_Necesaria = ?, Activo = 1, Fecha_Modificacion = GETDATE() 
                    WHERE Id_Reactivo = ? AND Id_Servicio = ?";
            $stmt = sqlsrv_query($this->db, $sql, array($cantidadNecesaria, $idReactivo, $idServicio));
        } else {
            // NO EXISTE: Hacer INSERT nuevo
            $sql = "INSERT INTO laboratorio.Receta_Servicio (Id_Reactivo, Id_Servicio, Cantidad_Necesaria, Usuario_Creacion, Activo, Fecha_Creacion) 
                    VALUES (?, ?, ?, ?, 1, GETDATE())";
            $stmt = sqlsrv_query($this->db, $sql, array($idReactivo, $idServicio, $cantidadNecesaria, $usuarioId));
        }
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al guardar receta: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function obtenerRequisitos($idServicio) {
        $sql = "SELECT re.*, e.Nombre AS Equipo_Nombre FROM laboratorio.Requisito_Equipo re JOIN laboratorio.Equipo_Lab e ON re.Id_Equipo = e.Id_Equipo WHERE re.Id_Servicio = ? AND re.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarRequisito($datos) {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;
        $sql = "INSERT INTO laboratorio.Requisito_Equipo (Id_Equipo, Id_Servicio, Es_Bloqueante, Usuario_Creacion, Activo, Fecha_Creacion) 
                VALUES (?, ?, ?, ?, 1, GETDATE())";
        $stmt = sqlsrv_query($this->db, $sql, array(
            $datos['Id_Equipo'], 
            $datos['Id_Servicio'], 
            $datos['Es_Bloqueante'] ?? 1, 
            $usuarioId
        ));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al guardar requisito: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function obtenerParametros($idServicio) {
        $sql = "SELECT pa.* FROM laboratorio.Parametro_Analisis pa 
                WHERE pa.Id_Servicio = ? AND pa.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en obtenerParametros: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Servicio_Tecnico SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Servicio = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al reactivar servicio: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function eliminarRequisito($idServicio, $idEquipo) {
        $sql = "UPDATE laboratorio.Requisito_Equipo SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Servicio = ? AND Id_Equipo = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio, $idEquipo));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al eliminar requisito: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function eliminarReceta($idServicio, $idReactivo) {
        $sql = "UPDATE laboratorio.Receta_Servicio SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Servicio = ? AND Id_Reactivo = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio, $idReactivo));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al eliminar receta: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function eliminarParametro($idServicio, $idParametro) {
        // Desasociar el parámetro del servicio sin eliminarlo completamente
        // Permite reutilizarlo en otro servicio
        $sql = "UPDATE laboratorio.Parametro_Analisis 
                SET Id_Servicio = NULL, Fecha_Modificacion = GETDATE()
                WHERE Id_Parametro = ? AND Id_Servicio = ?";
        $params = array($idParametro, $idServicio);
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al desasociar parámetro ' . $idParametro . ' del servicio ' . $idServicio . ': ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        // Verificar que realmente se actualizó
        $affectedRows = sqlsrv_rows_affected($stmt);
        if ($affectedRows === 0) {
            // Posible causa: el parámetro no existe o no estaba asignado a este servicio
            // Verificar si el parámetro existe y qué servicio tiene asignado
            $checkSql = "SELECT Id_Servicio FROM laboratorio.Parametro_Analisis WHERE Id_Parametro = ?";
            $checkStmt = sqlsrv_query($this->db, $checkSql, array($idParametro));
            if ($checkStmt && $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
                $currentServicio = $row['Id_Servicio'];
                if ($currentServicio != $idServicio && $currentServicio != null) {
                    throw new Exception('El parámetro ' . $idParametro . ' está asignado a otro servicio (ID: ' . $currentServicio . '), no a este (ID: ' . $idServicio . ')');
                }
                // Si currentServicio es null o igual a idServicio, el parámetro ya estaba desasociado o no era del servicio
            } else {
                throw new Exception('El parámetro ' . $idParametro . ' no existe');
            }
        }
    }

    public function guardarParametro($datos) {
        // UPDATE el parámetro existente para asignarle el servicio
        // En lugar de INSERT, actualizamos el parámetro con el Id_Servicio
        $sql = "UPDATE laboratorio.Parametro_Analisis 
                SET Id_Servicio = ?, Usuario_Creacion = ?, Fecha_Modificacion = GETDATE()
                WHERE Id_Parametro = ?";
        $stmt = sqlsrv_query($this->db, $sql, array(
            $datos['Id_Servicio'],
            $_SESSION['usuario_id'] ?? 1,
            $datos['Id_Parametro']
        ));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al guardar parámetro: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }
}