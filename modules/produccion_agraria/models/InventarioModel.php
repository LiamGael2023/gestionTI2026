<?php
class InventarioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ========================================
    // CRUD TABLA: PRODUCTO
    // ========================================
    
    public function listarProductos() {
        $sql = "SELECT p.id_producto, p.nombre, p.nombre_cientifico, p.unidad_medida, p.maneja_stock, 
                       p.tipo_precio, p.porcentaje_uit, p.id_clase, p.id_centro, p.imagen_nombre,
                       c.nombre_clase, cp.nombre_centro,
                       STUFF((SELECT ', ' + cp2.nombre_centro 
                              FROM BD_PRODUCCIONDESARROLLO.dbo.producto_centro pc2
                              JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp2 ON pc2.id_centro = cp2.id_centro
                              WHERE pc2.id_producto = p.id_producto
                              ORDER BY cp2.nombre_centro
                              FOR XML PATH('')), 1, 2, '') as centros
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
                ORDER BY p.id_producto";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            error_log('SQL Error listarProductos: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerProducto($id) {
        $sql = "SELECT p.id_producto, p.nombre, p.nombre_cientifico, p.unidad_medida, p.maneja_stock, 
                       p.tipo_precio, p.porcentaje_uit, p.id_clase, p.id_centro, p.imagen_nombre,
                       c.nombre_clase, cp.nombre_centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
                WHERE p.id_producto = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            $producto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            // Cargar todos los centros vinculados
            $sqlCentros = "SELECT pc.id_centro, cp.nombre_centro
                          FROM BD_PRODUCCIONDESARROLLO.dbo.producto_centro pc
                          JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON pc.id_centro = cp.id_centro
                          WHERE pc.id_producto = ? ORDER BY cp.nombre_centro";
            $stmtCentros = sqlsrv_query($this->db, $sqlCentros, [$id]);
            $centros = [];
            if ($stmtCentros) {
                while ($c = sqlsrv_fetch_array($stmtCentros, SQLSRV_FETCH_ASSOC)) {
                    $centros[] = $c;
                }
            }
            $producto['centros'] = $centros;
            return $producto;
        }
        return null;
    }

    public function guardarProducto($data) {
        // Convertir valores vacios a null para campos numericos
        $porcentaje_uit = (!empty($data['porcentaje_uit']) && $data['porcentaje_uit'] !== '') 
            ? floatval($data['porcentaje_uit']) 
            : null;
        
        $id_clase = (!empty($id_clase) && $id_clase !== '') ? intval($id_clase) : null;
        $id_centro = (!empty($data['id_centro']) && $data['id_centro'] !== '') ? intval($data['id_centro']) : null;
        
        // Si tipo_precio no es UIT, forzar porcentaje_uit a null
        $tipo_precio = $data['tipo_precio'] ?? null;
        if ($tipo_precio !== 'UIT') {
            $porcentaje_uit = null;
        }
        
        // Determinar acción sobre imagen
        $eliminarImagen = !empty($data['eliminar_imagen']);
        $tieneNuevaImagen = !empty($data['imagen_blob']) && !empty($data['imagen_nombre']);
        
        // Preparar parámetro BLOB si aplica
        $blobParam = null;
        if ($tieneNuevaImagen) {
            $blobParam = array(
                $data['imagen_blob'],
                SQLSRV_PARAM_IN,
                SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),
                SQLSRV_SQLTYPE_VARBINARY('max')
            );
        }
        
        if (!empty($data['id_producto'])) {
            // UPDATE: preservar id_clase e id_centro si vienen vacios
            if (empty($id_clase)) {
                $sqlGet = "SELECT id_clase FROM BD_PRODUCCIONDESARROLLO.dbo.producto WHERE id_producto = ?";
                $stmtGet = sqlsrv_query($this->db, $sqlGet, [$data['id_producto']]);
                if ($stmtGet && $row = sqlsrv_fetch_array($stmtGet, SQLSRV_FETCH_ASSOC)) {
                    $id_clase = $row['id_clase'];
                }
            }
            if (empty($id_centro)) {
                $sqlGet = "SELECT id_centro FROM BD_PRODUCCIONDESARROLLO.dbo.producto WHERE id_producto = ?";
                $stmtGet = sqlsrv_query($this->db, $sqlGet, [$data['id_producto']]);
                if ($stmtGet && $row = sqlsrv_fetch_array($stmtGet, SQLSRV_FETCH_ASSOC)) {
                    $id_centro = $row['id_centro'];
                }
            }
            
            // UPDATE
            if ($eliminarImagen) {
                $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.producto 
                        SET nombre = ?, nombre_cientifico = ?, unidad_medida = ?, maneja_stock = ?, 
                            tipo_precio = ?, porcentaje_uit = ?, id_clase = ?, id_centro = ?,
                            imagen_nombre = NULL, imagen_blob = NULL
                        WHERE id_producto = ?";
                $params = [
                    $data['nombre'],
                    $data['nombre_cientifico'] ?? null,
                    $data['unidad_medida'], 
                    $data['maneja_stock'] ?? 0,
                    $data['tipo_precio'] ?? 'Fijo',
                    $porcentaje_uit,
                    $id_clase, 
                    $id_centro,
                    $data['id_producto']
                ];
            } elseif ($tieneNuevaImagen) {
                $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.producto 
                        SET nombre = ?, nombre_cientifico = ?, unidad_medida = ?, maneja_stock = ?, 
                            tipo_precio = ?, porcentaje_uit = ?, id_clase = ?, id_centro = ?,
                            imagen_nombre = ?, imagen_blob = ?
                        WHERE id_producto = ?";
                $params = [
                    $data['nombre'],
                    $data['nombre_cientifico'] ?? null,
                    $data['unidad_medida'], 
                    $data['maneja_stock'] ?? 0,
                    $data['tipo_precio'] ?? 'Fijo',
                    $porcentaje_uit,
                    $id_clase, 
                    $id_centro,
                    $data['imagen_nombre'],
                    $blobParam,
                    $data['id_producto']
                ];
            } else {
                $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.producto 
                        SET nombre = ?, nombre_cientifico = ?, unidad_medida = ?, maneja_stock = ?, 
                            tipo_precio = ?, porcentaje_uit = ?, id_clase = ?, id_centro = ?
                        WHERE id_producto = ?";
                $params = [
                    $data['nombre'],
                    $data['nombre_cientifico'] ?? null,
                    $data['unidad_medida'], 
                    $data['maneja_stock'] ?? 0,
                    $data['tipo_precio'] ?? 'Fijo',
                    $porcentaje_uit,
                    $id_clase, 
                    $id_centro,
                    $data['id_producto']
                ];
            }
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            if (!empty($data['centros']) && is_array($data['centros'])) {
                $this->syncProductoCentros($data['id_producto'], $data['centros']);
            }
            return ['success' => true, 'id' => $data['id_producto']];
        } else {
            // INSERT con OUTPUT para obtener el ID directamente
            if ($tieneNuevaImagen) {
                $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.producto 
                        (nombre, nombre_cientifico, unidad_medida, maneja_stock, tipo_precio, porcentaje_uit, id_clase, id_centro, imagen_nombre, imagen_blob) 
                        OUTPUT INSERTED.id_producto
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $data['nombre'],
                    $data['nombre_cientifico'] ?? null,
                    $data['unidad_medida'], 
                    $data['maneja_stock'] ?? 0,
                    $data['tipo_precio'] ?? 'Fijo',
                    $porcentaje_uit,
                    $id_clase, 
                    $id_centro,
                    $data['imagen_nombre'],
                    $blobParam
                ];
            } else {
                $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.producto 
                        (nombre, nombre_cientifico, unidad_medida, maneja_stock, tipo_precio, porcentaje_uit, id_clase, id_centro) 
                        OUTPUT INSERTED.id_producto
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $data['nombre'],
                    $data['nombre_cientifico'] ?? null,
                    $data['unidad_medida'], 
                    $data['maneja_stock'] ?? 0,
                    $data['tipo_precio'] ?? 'Fijo',
                    $porcentaje_uit,
                    $id_clase, 
                    $id_centro
                ];
            }
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $newId = $row['id_producto'] ?? null;
            // Guardar centros vinculados
            if (!empty($data['centros']) && is_array($data['centros'])) {
                $this->syncProductoCentros($newId, $data['centros']);
            }
            return ['success' => true, 'id' => $newId];
        }
    }

    public function syncProductoCentros($idProducto, $centrosIds) {
        try {
            sqlsrv_begin_transaction($this->db);
            
            $sqlDel = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.producto_centro WHERE id_producto = ?";
            sqlsrv_query($this->db, $sqlDel, [$idProducto]);
            
            foreach ($centrosIds as $idCentro) {
                $idCentro = intval($idCentro);
                if ($idCentro <= 0) continue;
                $sqlIns = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.producto_centro (id_producto, id_centro) VALUES (?, ?)";
                sqlsrv_query($this->db, $sqlIns, [$idProducto, $idCentro]);
            }
            
            sqlsrv_commit($this->db);
            return true;
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return false;
        }
    }

    public function eliminarProducto($id) {
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.producto WHERE id_producto = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorMessage = '';
            foreach ($errors as $error) {
                // Error 547 es violación de restricción de clave foránea en SQL Server
                if ($error['code'] == 547) {
                    return ['success' => false, 'message' => 'No se puede eliminar el producto porque tiene registros relacionados (lotes, ventas, historial de precios, etc.). Elimine primero esos registros.'];
                }
                $errorMessage = $error['message'];
            }
            return ['success' => false, 'message' => $errorMessage ?: 'Error al eliminar el producto'];
        }
        return ['success' => true];
    }

    // ========================================
    // RELACIONALES (para selects)
    // ========================================
    
    public function listarClasesSelect() {
        $sql = "SELECT id_clase, nombre_clase FROM BD_PRODUCCIONDESARROLLO.dbo.clase ORDER BY nombre_clase";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function listarCentrosSelect() {
        $sql = "SELECT id_centro, nombre_centro FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion ORDER BY nombre_centro";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ========================================
    // LOTES Y STOCK (PEPS)
    // ========================================
    
    public function listarLotesPorProducto($idProducto) {
        $sql = "SELECT l.id_lote, l.codigo_lote, l.fecha_creacion,
                       l.stock_actual, l.id_centro,
                       cp.nombre_centro,
                       DATEDIFF(day, l.fecha_creacion, GETDATE()) as antiguedad_dias,
                       CASE 
                           WHEN l.stock_actual <= 0 THEN 'Agotado'
                           WHEN l.stock_actual < 10 THEN 'Stock Critico'
                           ELSE 'Activo'
                       END as estado_texto
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote l
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON l.id_centro = cp.id_centro
                WHERE l.id_producto = ? AND l.stock_actual > 0
                ORDER BY l.fecha_creacion ASC";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        if ($stmt === false) {
            error_log('SQL Error listarLotes: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function listarMovimientosKardex($idProducto) {
        $sql = "SELECT k.id_kardex, k.fecha, k.tipo_movimiento,
                       l.codigo_lote, k.cantidad, k.saldo_final, k.id_lote, 
                       k.id_transaccion, k.observacion
                FROM BD_PRODUCCIONDESARROLLO.dbo.kardex k
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.lote l ON k.id_lote = l.id_lote
                WHERE l.id_producto = ?
                ORDER BY k.fecha DESC, k.id_kardex DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        if ($stmt === false) {
            error_log('SQL Error listarMovimientos: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Convertir fecha a string ISO para JavaScript
            if (isset($row['fecha']) && $row['fecha'] instanceof DateTime) {
                $row['fecha'] = $row['fecha']->format('Y-m-d H:i:s');
            }
            // Generar documento segun tipo de movimiento
            if ($row['tipo_movimiento'] === 'MERMA') {
                $row['documento'] = !empty($row['observacion']) ? $row['observacion'] : 'Merma registrada';
            } elseif (!empty($row['id_transaccion'])) {
                $row['documento'] = 'Venta #' . $row['id_transaccion'];
            } elseif ($row['tipo_movimiento'] === 'INGRESO') {
                $row['documento'] = 'Ingreso de lote';
            } else {
                $row['documento'] = '-';
            }
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerStockTotal($idProducto) {
        $sql = "SELECT SUM(stock_actual) as stock_total 
                FROM BD_PRODUCCIONDESARROLLO.dbo.lote 
                WHERE id_producto = ? AND stock_actual > 0";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return intval($row['stock_total'] ?? 0);
        }
        return 0;
    }

    public function guardarLote($data) {
        try {
            sqlsrv_begin_transaction($this->db);
            
            // Insertar el lote
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.lote 
                    (id_producto, codigo_lote, fecha_creacion, stock_actual, id_centro) 
                    VALUES (?, ?, GETDATE(), ?, ?)";
            $params = [
                $data['id_producto'],
                $data['codigo_lote'],
                $data['stock_inicial'],
                $data['id_centro'] ?? null
            ];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception('Error al insertar lote: ' . print_r(sqlsrv_errors(), true));
            }
            
            // Obtener el ID del lote insertado (buscando por código y producto)
            $sqlGetId = "SELECT id_lote FROM BD_PRODUCCIONDESARROLLO.dbo.lote 
                        WHERE id_producto = ? AND codigo_lote = ? 
                        ORDER BY fecha_creacion DESC";
            $stmtGetId = sqlsrv_query($this->db, $sqlGetId, [$data['id_producto'], $data['codigo_lote']]);
            $idLote = null;
            if ($stmtGetId && $row = sqlsrv_fetch_array($stmtGetId, SQLSRV_FETCH_ASSOC)) {
                $idLote = $row['id_lote'];
            }
            
            if (!$idLote) {
                throw new Exception('No se pudo obtener el ID del lote creado');
            }
            
            // Si hay stock inicial, registrar movimiento de entrada
            if ($data['stock_inicial'] > 0) {
                $sqlMov = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                          (id_lote, id_transaccion, tipo_movimiento, cantidad, saldo_final, fecha, observacion) 
                          VALUES (?, NULL, 'INGRESO', ?, ?, GETDATE(), 'Creacion de lote')";
                $paramsMov = [$idLote, $data['stock_inicial'], $data['stock_inicial']];
                $stmtMov = sqlsrv_query($this->db, $sqlMov, $paramsMov);
                
                if ($stmtMov === false) {
                    throw new Exception('Error al registrar movimiento: ' . print_r(sqlsrv_errors(), true));
                }
            }
            
            sqlsrv_commit($this->db);
            return ['success' => true, 'id_lote' => $idLote];
            
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function guardarMerma($data) {
        try {
            sqlsrv_begin_transaction($this->db);
            
            // Obtener stock actual del lote
            $sqlStock = "SELECT stock_actual FROM BD_PRODUCCIONDESARROLLO.dbo.lote WHERE id_lote = ?";
            $stmtStock = sqlsrv_query($this->db, $sqlStock, [$data['id_lote']]);
            if ($stmtStock === false) {
                throw new Exception('Error al obtener stock del lote: ' . print_r(sqlsrv_errors(), true));
            }
            $rowStock = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            $stockActual = $rowStock['stock_actual'] ?? 0;
            
            // Validar que no exceda el stock
            if ($data['cantidad'] > $stockActual) {
                throw new Exception('La cantidad de merma (' . $data['cantidad'] . ') excede el stock actual (' . $stockActual . ')');
            }
            
            // Actualizar stock del lote
            $nuevoStock = $stockActual - $data['cantidad'];
            $sqlUpdate = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.lote SET stock_actual = ? WHERE id_lote = ?";
            $stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, [$nuevoStock, $data['id_lote']]);
            if ($stmtUpdate === false) {
                throw new Exception('Error al actualizar stock del lote: ' . print_r(sqlsrv_errors(), true));
            }
            
            // Registrar movimiento de merma en kardex
            $sqlKardex = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                         (id_lote, id_transaccion, tipo_movimiento, cantidad, saldo_final, fecha, observacion) 
                         VALUES (?, NULL, 'MERMA', ?, ?, GETDATE(), ?)";
            $paramsKardex = [$data['id_lote'], $data['cantidad'], $nuevoStock, $data['motivo'] ?? null];
            $stmtKardex = sqlsrv_query($this->db, $sqlKardex, $paramsKardex);
            if ($stmtKardex === false) {
                throw new Exception('Error al registrar movimiento de merma: ' . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_commit($this->db);
            return ['success' => true, 'message' => 'Merma registrada correctamente'];
            
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getLastInsertId() {
        $sql = "SELECT SCOPE_IDENTITY() as id";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return $row['id'];
        }
        return null;
    }

    public function obtenerUITActual() {
        $anioActual = date('Y');
        $sql = "SELECT valor FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE anio = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$anioActual]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return $row['valor'];
        }
        return null;
    }

    // ========================================
    // HISTORIAL DE PRECIOS
    // ========================================

    public function obtenerPrecioActual($idProducto) {
        $sql = "SELECT TOP 1 precio_oficial, fecha_registro 
                FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio 
                WHERE id_producto = ? 
                ORDER BY fecha_registro DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return $row;
        }
        return null;
    }

    public function listarHistorialPrecios($idProducto) {
        $sql = "SELECT id_historial, precio_oficial, fecha_registro 
                FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio 
                WHERE id_producto = ? 
                ORDER BY fecha_registro DESC";
        $stmt = sqlsrv_query($this->db, $sql, [$idProducto]);
        if ($stmt === false) {
            error_log('SQL Error listarHistorialPrecios: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarPrecio($data) {
        // Validar que id_producto no sea nulo o vacío
        if (empty($data['id_producto'])) {
            return ['success' => false, 'message' => 'ID de producto no válido'];
        }
        
        $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.historial_precio 
                (id_producto, precio_oficial, fecha_registro) 
                VALUES (?, ?, GETDATE())";
        $params = [$data['id_producto'], $data['precio_oficial']];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('SQL Error guardarPrecio: ' . print_r(sqlsrv_errors(), true));
            return ['success' => false, 'message' => 'Error al guardar precio: ' . print_r(sqlsrv_errors(), true)];
        }
        return ['success' => true];
    }

    public function obtenerImagenProducto($id) {
        $sql = "SELECT imagen_nombre, imagen_blob 
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto 
                WHERE id_producto = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && sqlsrv_fetch($stmt) !== false) {
            $nombre = sqlsrv_get_field($stmt, 0);
            // Obtener blob como string binario explícitamente
            $blob = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY));
            if ($blob !== false && $blob !== null && strlen($blob) > 0) {
                return ['imagen_nombre' => $nombre, 'imagen_blob' => $blob];
            }
        }
        return null;
    }

    public function obtenerImagenProductoBase64($id) {
        $sql = "SELECT imagen_nombre, CONVERT(VARCHAR(MAX), imagen_blob, 1) as imagen_hex 
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto 
                WHERE id_producto = ? AND imagen_blob IS NOT NULL";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($row['imagen_hex'])) {
                // El valor viene como hexadecimal string "0x89...", convertir a binario
                $hex = $row['imagen_hex'];
                if (strpos($hex, '0x') === 0) {
                    $hex = substr($hex, 2);
                }
                $binario = hex2bin($hex);
                return [
                    'imagen_nombre' => $row['imagen_nombre'],
                    'imagen_blob' => $binario
                ];
            }
        }
        return null;
    }

    public function eliminarImagenProducto($id) {
        $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.producto 
                SET imagen_nombre = NULL, imagen_blob = NULL 
                WHERE id_producto = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt === false) {
            return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
        }
        return ['success' => true];
    }

    public function agregarStockMasivo() {
        try {
            sqlsrv_begin_transaction($this->db);

            $sqlProd = "SELECT id_producto, id_clase, id_centro FROM BD_PRODUCCIONDESARROLLO.dbo.producto WHERE maneja_stock = 1";
            $stmtProd = sqlsrv_query($this->db, $sqlProd);
            if ($stmtProd === false) {
                throw new Exception('Error al obtener productos: ' . print_r(sqlsrv_errors(), true));
            }

            $productos = [];
            while ($row = sqlsrv_fetch_array($stmtProd, SQLSRV_FETCH_ASSOC)) {
                $productos[] = ['id' => $row['id_producto'], 'id_clase' => $row['id_clase'], 'id_centro' => $row['id_centro']];
            }

            if (empty($productos)) {
                throw new Exception('No hay productos con maneja_stock activo');
            }

            $creados = 0;
            $omitidos = 0;

            foreach ($productos as $prod) {
                $idProducto = $prod['id'];
                $codigoLote = 'INI-' . $idProducto;

                $sqlCheck = "SELECT id_lote FROM BD_PRODUCCIONDESARROLLO.dbo.lote WHERE id_producto = ? AND codigo_lote = ?";
                $stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$idProducto, $codigoLote]);
                if ($stmtCheck && sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
                    $omitidos++;
                    continue;
                }

                $idCentro = $prod['id_centro'] ?? null;
                if (!$idCentro && $prod['id_clase']) {
                    // Obtener primer centro vinculado a la clase
                    $sqlCentro = "SELECT TOP 1 id_centro FROM BD_PRODUCCIONDESARROLLO.dbo.clase_centro WHERE id_clase = ?";
                    $stmtCentro = sqlsrv_query($this->db, $sqlCentro, [$prod['id_clase']]);
                    if ($stmtCentro && $c = sqlsrv_fetch_array($stmtCentro, SQLSRV_FETCH_ASSOC)) {
                        $idCentro = $c['id_centro'];
                    }
                }

                $sqlInsert = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.lote (id_producto, codigo_lote, fecha_creacion, stock_actual, id_centro) VALUES (?, ?, GETDATE(), 10, ?)";
                $stmtInsert = sqlsrv_query($this->db, $sqlInsert, [$idProducto, $codigoLote, $idCentro]);
                if ($stmtInsert === false) {
                    throw new Exception('Error al crear lote para producto ' . $idProducto . ': ' . print_r(sqlsrv_errors(), true));
                }

                $sqlGetId = "SELECT id_lote FROM BD_PRODUCCIONDESARROLLO.dbo.lote WHERE id_producto = ? AND codigo_lote = ? ORDER BY fecha_creacion DESC";
                $stmtGetId = sqlsrv_query($this->db, $sqlGetId, [$idProducto, $codigoLote]);
                $idLote = null;
                if ($stmtGetId && $rowLote = sqlsrv_fetch_array($stmtGetId, SQLSRV_FETCH_ASSOC)) {
                    $idLote = $rowLote['id_lote'];
                }

                if ($idLote) {
                    $sqlKardex = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex (id_lote, id_transaccion, tipo_movimiento, cantidad, saldo_final, fecha, observacion) VALUES (?, NULL, 'INGRESO', 10, 10, GETDATE(), 'Ingreso masivo +10')";
                    $stmtKardex = sqlsrv_query($this->db, $sqlKardex, [$idLote]);
                    if ($stmtKardex === false) {
                        throw new Exception('Error al registrar kardex para lote ' . $idLote . ': ' . print_r(sqlsrv_errors(), true));
                    }
                }

                $creados++;
            }

            sqlsrv_commit($this->db);
            return ['success' => true, 'productos_procesados' => count($productos), 'lotes_creados' => $creados, 'omitidos' => $omitidos];

        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
