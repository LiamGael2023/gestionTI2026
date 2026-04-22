<?php

class ReactivoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
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
            
            // IMPORTANTE: Cantidad_Stock se inicializa en 0. 
            // La Cantidad_Inicial es solo un campo informativo/fijo.
            $sql = "INSERT INTO laboratorio.Reactivo_Lab (
                        Nombre, Unidad_Medida, Fecha_Vencimiento, 
                        Cantidad_Inicial, Cantidad_Stock, Cantidad_Reservada, 
                        Fecha_Ingreso, Activo, Fecha_Creacion, Usuario_Creacion
                    )
                    VALUES (?, ?, ?, ?, 0, 0, GETDATE(), 1, GETDATE(), ?); 
                    SELECT SCOPE_IDENTITY() AS id;";
            
            $params = array(
                $datos['Nombre'],
                $datos['Unidad_Medida'] ?? 'UND',
                !empty($datos['Fecha_Vencimiento']) ? $datos['Fecha_Vencimiento'] : null,
                $cantidadReferencial, // Se guarda como referencia fija
                $usuarioId
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
                        Unidad_Medida = ?, 
                        Fecha_Vencimiento = ?, 
                        Cantidad_Inicial = ?, 
                        Fecha_Modificacion = GETDATE() 
                    WHERE Id_Reactivo = ?";
            
            $params = array(
                $datos['Nombre'],
                $datos['Unidad_Medida'],
                !empty($datos['Fecha_Vencimiento']) ? $datos['Fecha_Vencimiento'] : null,
                floatval($datos['Cantidad_Inicial'] ?? 0),
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
}