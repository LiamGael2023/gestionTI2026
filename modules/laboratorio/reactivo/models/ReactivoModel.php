<?php

class ReactivoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->migrarColumnas();
    }

    private function migrarColumnas() {
        $migraciones = [
            // Id_Unidad_Medida — FK to laboratorio.Unidad_Medida (not in base DDL)
            "IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='laboratorio' AND TABLE_NAME='Reactivo_Lab' AND COLUMN_NAME='Id_Unidad_Medida')
             ALTER TABLE laboratorio.Reactivo_Lab ADD Id_Unidad_Medida INT NULL",
            // Id_Proveedor — FK to laboratorio.Proveedor (not in base DDL)
            "IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='laboratorio' AND TABLE_NAME='Reactivo_Lab' AND COLUMN_NAME='Id_Proveedor')
             ALTER TABLE laboratorio.Reactivo_Lab ADD Id_Proveedor INT NULL",
        ];
        foreach ($migraciones as $sql) {
            sqlsrv_query($this->db, $sql);
        }
    }

    /**
     * Obtiene todos los reactivos activos
     */
    public function obtenerTodos() {
        $sql = "SELECT * FROM laboratorio.Reactivo_Lab WHERE Activo = 1 ORDER BY Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Reactivos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Obtiene un reactivo por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Reactivo_Lab WHERE Id_Reactivo = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Reactivo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Guarda o actualiza un reactivo
     */
    public function guardar($datos, $usuarioId) {
        if (empty($datos['Id_Reactivo'])) {
            // --- INSERTAR NUEVO REACTIVO ---
            $cantidadReferencial = floatval($datos['Cantidad_Inicial'] ?? 0);

            $sql = "INSERT INTO laboratorio.Reactivo_Lab (
                        Nombre, Tipo, Fecha_Vencimiento,
                        Cantidad_Inicial, Cantidad_Stock, Cantidad_Reservada,
                        Fecha_Ingreso, Activo, Fecha_Creacion, Usuario_Creacion,
                        Id_Proveedor, Id_Unidad_Medida
                    )
                    VALUES (?, ?, ?, ?, 0, 0, GETDATE(), 1, GETDATE(), ?, ?, ?);
                    SELECT SCOPE_IDENTITY() AS id;";

            $params = array(
                $datos['Nombre'],
                !empty($datos['Tipo']) ? $datos['Tipo'] : null,
                !empty($datos['Fecha_Vencimiento']) ? $datos['Fecha_Vencimiento'] : null,
                $cantidadReferencial,
                $usuarioId,
                !empty($datos['Id_Proveedor']) ? intval($datos['Id_Proveedor']) : null,
                !empty($datos['Id_Unidad_Medida']) ? intval($datos['Id_Unidad_Medida']) : null
            );

            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en INSERT Reactivo: ' . sqlsrv_errors()[0]['message']);
            }

            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];

        } else {
            // --- ACTUALIZAR REACTIVO EXISTENTE ---
            // Solo actualizamos datos maestros. NUNCA tocamos Cantidad_Stock aquí.
            $sql = "UPDATE laboratorio.Reactivo_Lab
                    SET Nombre = ?,
                        Tipo = ?,
                        Fecha_Vencimiento = ?,
                        Cantidad_Inicial = ?,
                        Id_Proveedor = ?,
                        Id_Unidad_Medida = ?,
                        Fecha_Modificacion = GETDATE()
                    WHERE Id_Reactivo = ?";

            $params = array(
                $datos['Nombre'],
                !empty($datos['Tipo']) ? $datos['Tipo'] : null,
                !empty($datos['Fecha_Vencimiento']) ? $datos['Fecha_Vencimiento'] : null,
                floatval($datos['Cantidad_Inicial'] ?? 0),
                !empty($datos['Id_Proveedor']) ? intval($datos['Id_Proveedor']) : null,
                !empty($datos['Id_Unidad_Medida']) ? intval($datos['Id_Unidad_Medida']) : null,
                $datos['Id_Reactivo']
            );

            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en UPDATE Reactivo: ' . sqlsrv_errors()[0]['message']);
            }
            return $datos['Id_Reactivo'];
        }
    }
    /**
     * Elimina (desactiva) un reactivo
     */
    public function eliminar($id) {
        // Verificar si el reactivo está ligado a servicios activos
        $sql_check = "SELECT COUNT(*) AS count FROM laboratorio.Receta_Servicio WHERE Id_Reactivo = ? AND Activo = 1";
        $stmt_check = sqlsrv_query($this->db, $sql_check, array($id));
        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        
        if ($row_check['count'] > 0) {
            throw new Exception('No se puede eliminar este reactivo porque está ligado a ' . $row_check['count'] . ' servicio(s). Primero debes eliminarlo de los servicios.');
        }
        
        $sql = "UPDATE laboratorio.Reactivo_Lab SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Reactivo = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    /**
     * Registra un ingreso de reactivo (Única vía para aumentar stock)
     */
    public function registrarIngreso($idReactivo, $cantidad, $facturaReferencia, $usuarioId) {
        // 1. Validar existencia
        $sqlValidar = "SELECT Id_Reactivo FROM laboratorio.Reactivo_Lab WHERE Id_Reactivo = ? AND Activo = 1";
        $stmtValidar = sqlsrv_query($this->db, $sqlValidar, array($idReactivo));
        
        if ($stmtValidar === false || !sqlsrv_fetch_array($stmtValidar, SQLSRV_FETCH_ASSOC)) {
            throw new Exception('El reactivo no existe o está inactivo');
        }
        
        if ($cantidad <= 0) throw new Exception('La cantidad debe ser mayor a 0');

        // 2. Insertar en Ingreso_Reactivo. 
        // EL TRIGGER hará: INSERT Kardex (Tipo 'E') y UPDATE Reactivo_Lab (Stock = Stock + Cantidad)
        $sqlIngreso = "INSERT INTO laboratorio.Ingreso_Reactivo 
                    (Id_Reactivo, Id_Usuario, Cantidad, Factura_Referencia, Fecha_Ingreso, Activo, Fecha_Creacion, Usuario_Creacion)
                    VALUES (?, ?, ?, ?, GETDATE(), 1, GETDATE(), ?)";
        
        $stmtIngreso = sqlsrv_query($this->db, $sqlIngreso, array($idReactivo, $usuarioId, $cantidad, $facturaReferencia, $usuarioId));
        
        if ($stmtIngreso === false) {
            throw new Exception('Error al registrar ingreso: ' . sqlsrv_errors()[0]['message']);
        }
        
        return 1;
    }
    /**
     * Registra una salida de reactivo
     * Ejecuta 3 operaciones en secuencia:
     * 1. INSERT en Ajuste_Inventario
     * 2. INSERT en Movimiento_Kardex (tipo 'S')
     * 3. UPDATE Reactivo_Lab (disminuir stock)
     */
    public function registrarSalida($idReactivo, $cantidad, $concepto, $usuarioId) {
        // Validar que el reactivo existe y está activo
        $sqlValidar = "SELECT Id_Reactivo, ISNULL(Cantidad_Stock, 0) AS saldo FROM laboratorio.Reactivo_Lab WHERE Id_Reactivo = ? AND Activo = 1";
        $stmtValidar = sqlsrv_query($this->db, $sqlValidar, array($idReactivo));
        
        if ($stmtValidar === false || !($reactivo = sqlsrv_fetch_array($stmtValidar, SQLSRV_FETCH_ASSOC))) {
            throw new Exception('El reactivo no existe o está inactivo');
        }
        
        // Validar cantidad
        if ($cantidad <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }
        
        $saldoActual = floatval($reactivo['saldo']);
        
        // VALIDACIÓN CRÍTICA: No permitir stock negativo
        if ($cantidad > $saldoActual) {
            throw new Exception('Stock insuficiente. Disponible: ' . $saldoActual . ', Solicitado: ' . $cantidad);
        }
        
        // 1. Insertar en Ajuste_Inventario y obtener el ID
        $sqlAjuste = "DECLARE @AjusteId INT; INSERT INTO laboratorio.Ajuste_Inventario 
                      (Id_Reactivo, Id_Usuario, Tipo_Ajuste, Cantidad, Fecha_Ajuste, Notas, Activo, Fecha_Creacion, Usuario_Creacion)
                      VALUES (?, ?, 'Salida Manual', ?, GETDATE(), ?, 1, GETDATE(), ?); 
                      SET @AjusteId = SCOPE_IDENTITY(); 
                      SELECT @AjusteId AS id;";
        
        $stmtAjuste = sqlsrv_query($this->db, $sqlAjuste, array($idReactivo, $usuarioId, $cantidad, $concepto, $usuarioId));
        if ($stmtAjuste === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al registrar salida: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        // Obtener el ID del ajuste
        $adjusteRow = sqlsrv_fetch_array($stmtAjuste, SQLSRV_FETCH_ASSOC);
        $adjusteId = ($adjusteRow && isset($adjusteRow['id'])) ? $adjusteRow['id'] : 1;
        
        // 2. Insertar en Movimiento_Kardex con tipo 'S'
        $nuevoSaldo = $saldoActual - $cantidad;
        $sqlKardex = "INSERT INTO laboratorio.Movimiento_Kardex 
                      (Id_Reactivo, Tipo_Movimiento, Cantidad, Concepto, Saldo_Resultante, Activo, Fecha_Registro, Fecha_Creacion, Usuario_Creacion)
                      VALUES (?, 'S', ?, ?, ?, 1, GETDATE(), GETDATE(), ?)";
        
        $stmtKardex = sqlsrv_query($this->db, $sqlKardex, array($idReactivo, $cantidad, $concepto, $nuevoSaldo, $usuarioId));
        if ($stmtKardex === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al registrar en kardex: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        // 3. Actualizar stock en Reactivo_Lab
        $sqlUpdate = "UPDATE laboratorio.Reactivo_Lab 
                      SET Cantidad_Stock = Cantidad_Stock - ?, Fecha_Modificacion = GETDATE() 
                      WHERE Id_Reactivo = ?";
        
        $stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, array($cantidad, $idReactivo));
        if ($stmtUpdate === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al actualizar stock: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        return $adjusteId;
    }

    /**
     * Obtiene el kardex de un reactivo
     */
    public function obtenerKardex($idReactivo, $limite = 50, $offset = 0) {
        $sql = "SELECT * FROM laboratorio.Movimiento_Kardex 
                WHERE Id_Reactivo = ? AND Activo = 1 
                ORDER BY Fecha_Registro DESC 
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        
        $stmt = sqlsrv_query($this->db, $sql, array($idReactivo, $offset, $limite));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener kardex: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Obtiene todo el kardex con información del reactivo
     */
    public function obtenerKardexCompleto($limite = 100, $offset = 0) {
        $sql = "SELECT 
                    mk.Id_Movimiento,
                    mk.Fecha_Registro,
                    mk.Tipo_Movimiento,
                    mk.Cantidad,
                    mk.Concepto,
                    mk.Saldo_Resultante,
                    rl.Nombre,
                    rl.Unidad_Medida,
                    rl.Id_Reactivo
                FROM laboratorio.Movimiento_Kardex mk
                JOIN laboratorio.Reactivo_Lab rl ON mk.Id_Reactivo = rl.Id_Reactivo
                WHERE mk.Activo = 1
                ORDER BY mk.Fecha_Registro DESC
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        
        $stmt = sqlsrv_query($this->db, $sql, array($offset, $limite));
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener kardex: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear la fila para la respuesta
            $fila = array(
                'Reactivo_Nombre' => $row['Nombre'],
                'U_M' => $row['Unidad_Medida'],
                'Tipo_Movimiento' => $row['Tipo_Movimiento'],
                'Cantidad' => $row['Cantidad'],
                'Concepto' => $row['Concepto'],
                'Saldo_Resultante' => $row['Saldo_Resultante'],
                'Fecha_Registro' => $row['Fecha_Registro']
            );
            $result[] = $fila;
        }
        return $result;
    }

    /**
     * Obtiene el saldo actual de stock
     */
    public function obtenerStock($idReactivo) {
        $sql = "SELECT Cantidad_Stock, Cantidad_Reservada, (Cantidad_Stock - Cantidad_Reservada) AS Disponible 
                FROM laboratorio.Reactivo_Lab 
                WHERE Id_Reactivo = ?";
        
        $stmt = sqlsrv_query($this->db, $sql, array($idReactivo));
        
        if ($stmt === false) {
            return null;
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Obtiene reactivos con sus recetas
     */
    public function obtenerConRecetas() {
        $sql = "SELECT r.*, rs.Id_Servicio, rs.Cantidad_Necesaria FROM laboratorio.Reactivo_Lab r LEFT JOIN laboratorio.Receta_Servicio rs ON r.Id_Reactivo = rs.Id_Reactivo WHERE r.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Reactivar un reactivo
     */
    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Reactivo_Lab SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Reactivo = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    /**
     * Edita la cantidad de un ingreso y ajusta el stock
     */
    public function editarIngreso($idIngreso, $nuevaCantidad) {
        $stmt = sqlsrv_query($this->db,
            "SELECT Id_Ingreso, Cantidad, Id_Reactivo, Factura_Referencia FROM laboratorio.Ingreso_Reactivo WHERE Id_Ingreso = ? AND Activo = 1",
            [intval($idIngreso)]
        );
        if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            throw new Exception('Ingreso no encontrado o inactivo');
        }

        $idReactivo    = $row['Id_Reactivo'];
        $viejaCantidad = floatval($row['Cantidad']);
        $diff          = $nuevaCantidad - $viejaCantidad;
        $esIngresoInicial = (strtoupper(trim($row['Factura_Referencia'] ?? '')) === 'INGRESO INICIAL');

        // Verificar que el stock no quede negativo si reducimos
        if ($diff < 0) {
            $stmtStock = sqlsrv_query($this->db,
                "SELECT Cantidad_Stock FROM laboratorio.Reactivo_Lab WHERE Id_Reactivo = ?",
                [$idReactivo]
            );
            $rowStock = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            $stock = floatval($rowStock['Cantidad_Stock'] ?? 0);
            if ($stock + $diff < 0) {
                throw new Exception('No se puede reducir: el stock actual (' . $stock . ') quedaría negativo');
            }
        }

        // Actualizar Ingreso_Reactivo
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Ingreso_Reactivo SET Cantidad = ? WHERE Id_Ingreso = ?",
            [$nuevaCantidad, intval($idIngreso)]
        );

        // Ajustar Cantidad_Stock (siempre) y Cantidad_Inicial (solo si es el ingreso inicial)
        if ($diff != 0) {
            if ($esIngresoInicial) {
                sqlsrv_query($this->db,
                    "UPDATE laboratorio.Reactivo_Lab
                     SET Cantidad_Stock   = Cantidad_Stock   + ?,
                         Cantidad_Inicial = ISNULL(Cantidad_Inicial, 0) + ?,
                         Fecha_Modificacion = GETDATE()
                     WHERE Id_Reactivo = ?",
                    [$diff, $diff, $idReactivo]
                );
            } else {
                sqlsrv_query($this->db,
                    "UPDATE laboratorio.Reactivo_Lab
                     SET Cantidad_Stock = Cantidad_Stock + ?,
                         Fecha_Modificacion = GETDATE()
                     WHERE Id_Reactivo = ?",
                    [$diff, $idReactivo]
                );
            }
        }

        // Actualizar el registro correspondiente en Movimiento_Kardex (tipo E del mismo día)
        // Nota: UPDATE TOP(1)...ORDER BY no es válido en SQL Server — usar subquery
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Movimiento_Kardex
             SET Cantidad = ?
             WHERE Id_Movimiento = (
                 SELECT TOP 1 Id_Movimiento
                 FROM laboratorio.Movimiento_Kardex
                 WHERE Id_Reactivo = ? AND Tipo_Movimiento = 'E' AND Activo = 1
                   AND CAST(Fecha_Registro AS DATE) = (
                       SELECT TOP 1 CAST(Fecha_Ingreso AS DATE)
                       FROM laboratorio.Ingreso_Reactivo
                       WHERE Id_Ingreso = ?
                   )
                 ORDER BY Id_Movimiento DESC
             )",
            [$nuevaCantidad, $idReactivo, intval($idIngreso)]
        );
    }

    /**
     * Edita la cantidad/concepto de una salida (Movimiento_Kardex tipo S) y ajusta el stock
     */
    public function editarSalida($idMovimiento, $nuevaCantidad, $concepto) {
        $stmt = sqlsrv_query($this->db,
            "SELECT Id_Movimiento, Cantidad, Id_Reactivo FROM laboratorio.Movimiento_Kardex WHERE Id_Movimiento = ? AND Tipo_Movimiento = 'S' AND Activo = 1",
            [intval($idMovimiento)]
        );
        if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            throw new Exception('Movimiento de salida no encontrado');
        }

        $idReactivo   = $row['Id_Reactivo'];
        $viejaCantidad = floatval($row['Cantidad']);
        $diff          = $nuevaCantidad - $viejaCantidad; // positivo = más salida

        // Si aumenta la salida, verificar stock disponible
        if ($diff > 0) {
            $stmtStock = sqlsrv_query($this->db,
                "SELECT Cantidad_Stock FROM laboratorio.Reactivo_Lab WHERE Id_Reactivo = ?",
                [$idReactivo]
            );
            $rowStock = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            $stock = floatval($rowStock['Cantidad_Stock'] ?? 0);
            if ($stock - $diff < 0) {
                throw new Exception('Stock insuficiente. Disponible: ' . $stock . ', diferencia requerida: ' . $diff);
            }
        }

        // Actualizar Movimiento_Kardex
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Movimiento_Kardex SET Cantidad = ?, Concepto = ? WHERE Id_Movimiento = ?",
            [$nuevaCantidad, $concepto, intval($idMovimiento)]
        );

        // Ajustar stock (salida mayor = stock baja; salida menor = stock sube)
        if ($diff != 0) {
            sqlsrv_query($this->db,
                "UPDATE laboratorio.Reactivo_Lab SET Cantidad_Stock = Cantidad_Stock - ?, Fecha_Modificacion = GETDATE() WHERE Id_Reactivo = ?",
                [$diff, $idReactivo]
            );
        }
    }

    /**
     * Elimina (soft-delete) una salida manual NO vinculada a ningún consumo/muestra
     * y restaura el stock correspondiente
     */
    public function eliminarSalida($idMovimiento) {
        // Verificar que existe, es salida tipo S, activa, y NO tiene Consumo_Reaccion vinculado
        $stmt = sqlsrv_query($this->db,
            "SELECT mk.Id_Movimiento, mk.Cantidad, mk.Id_Reactivo
             FROM laboratorio.Movimiento_Kardex mk
             LEFT JOIN laboratorio.Consumo_Reaccion cr ON cr.Id_Movimiento = mk.Id_Movimiento AND cr.Activo = 1
             WHERE mk.Id_Movimiento = ? AND mk.Tipo_Movimiento = 'S' AND mk.Activo = 1
               AND cr.Id_Movimiento IS NULL",
            [intval($idMovimiento)]
        );
        if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            throw new Exception('Movimiento no encontrado, ya eliminado, o tiene consumos vinculados y no puede borrarse');
        }

        $idReactivo = $row['Id_Reactivo'];
        $cantidad   = floatval($row['Cantidad']);

        // Soft-delete en Movimiento_Kardex
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Movimiento_Kardex SET Activo = 0 WHERE Id_Movimiento = ?",
            [intval($idMovimiento)]
        );

        // Restaurar stock (la salida ya no existe → stock sube)
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Reactivo_Lab SET Cantidad_Stock = Cantidad_Stock + ?, Fecha_Modificacion = GETDATE() WHERE Id_Reactivo = ?",
            [$cantidad, $idReactivo]
        );

        // Soft-delete del Ajuste_Inventario asociado (si existe)
        sqlsrv_query($this->db,
            "UPDATE TOP(1) laboratorio.Ajuste_Inventario
             SET Activo = 0
             WHERE Id_Reactivo = ? AND Tipo_Ajuste = 'Salida Manual'
               AND CAST(Fecha_Ajuste AS DATE) = (
                   SELECT TOP 1 CAST(Fecha_Registro AS DATE)
                   FROM laboratorio.Movimiento_Kardex
                   WHERE Id_Movimiento = ?
               )",
            [$idReactivo, intval($idMovimiento)]
        );
    }
}
