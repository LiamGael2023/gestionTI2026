<?php
class PuntoVentaModel {
    private $db;
    public $lastError = '';

    public function __construct($db) {
        $this->db = $db;
    }

    // ========================================
    // CLIENTES (para select)
    // ========================================
    
    public function listarClientes() {
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, tipo_cliente 
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente 
                WHERE activo = 1
                ORDER BY nombre_rs";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function buscarClientes($query) {
        // Búsqueda solo por nombre
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, 
                       CASE WHEN tipo_cliente = 1 THEN 'Planilla' ELSE 'Externo' END as tipo_cliente 
                FROM BD_PRODUCCIONDESARROLLO.dbo.cliente 
                WHERE nombre_rs COLLATE SQL_Latin1_General_CP1_CI_AS LIKE ?
                AND activo = 1
                ORDER BY nombre_rs";
        $searchTerm = '%' . $query . '%';
        $stmt = sqlsrv_query($this->db, $sql, [$searchTerm]);
        if ($stmt === false) {
            error_log('SQL Error buscarClientes: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // ========================================
    // PRODUCTOS (para venta - solo los que manejan stock)
    // ========================================
    
    public function listarProductosVenta() {
        $sql = "SELECT p.id_producto, p.nombre, p.unidad_medida, p.imagen_nombre, p.tipo_precio, p.porcentaje_uit,
                       p.id_clase, p.id_centro, c.nombre_clase, cp.nombre_centro,
                       u.valor as valor_uit,
                       hp.precio_oficial as precio_variable,
                       (SELECT ISNULL(SUM(l.stock_actual), 0) 
                        FROM BD_PRODUCCIONDESARROLLO.dbo.lote l 
                        WHERE l.id_producto = p.id_producto AND l.stock_actual > 0) as stock_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit u ON u.anio = YEAR(GETDATE())
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (
                        SELECT MAX(hp2.fecha_registro)
                        FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                        WHERE hp2.id_producto = hp1.id_producto
                    )
                ) hp ON p.id_producto = hp.id_producto
                WHERE p.maneja_stock = 1 AND p.activo = 1
                ORDER BY p.nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            error_log('SQL Error listarProductosVenta: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Calcular precio según tipo
            $row['precio_venta'] = $this->calcularPrecio($row);
            // Asegurar que stock_total sea entero
            $row['stock_total'] = intval($row['stock_total'] ?? 0);
            $result[] = $row;
        }
        return $result;
    }

    public function buscarProducto($id) {
        $sql = "SELECT p.id_producto, p.nombre, p.unidad_medida, p.imagen_nombre, p.tipo_precio, p.porcentaje_uit,
                       p.id_clase, p.id_centro, c.nombre_clase, cp.nombre_centro,
                       u.valor as valor_uit,
                       hp.precio_oficial as precio_variable,
                       (SELECT ISNULL(SUM(l.stock_actual), 0) 
                        FROM BD_PRODUCCIONDESARROLLO.dbo.lote l 
                        WHERE l.id_producto = p.id_producto AND l.stock_actual > 0) as stock_total
                FROM BD_PRODUCCIONDESARROLLO.dbo.producto p
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit u ON u.anio = YEAR(GETDATE())
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                    WHERE hp1.fecha_registro = (
                        SELECT MAX(hp2.fecha_registro)
                        FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp2
                        WHERE hp2.id_producto = hp1.id_producto
                    )
                ) hp ON p.id_producto = hp.id_producto
                WHERE p.id_producto = ? AND p.maneja_stock = 1 AND p.activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['precio_venta'] = $this->calcularPrecio($row);
            $row['stock_total'] = intval($row['stock_total'] ?? 0);
            return $row;
        }
        return null;
    }

    private function calcularPrecio($producto) {
        if ($producto['tipo_precio'] === 'UIT' && !empty($producto['valor_uit']) && !empty($producto['porcentaje_uit'])) {
            return floatval($producto['valor_uit']) * floatval($producto['porcentaje_uit']);
        }
        if ($producto['tipo_precio'] === 'Variable' && !empty($producto['precio_variable'])) {
            return floatval($producto['precio_variable']);
        }
        // Sin precio definido
        return 0;
    }

    // ========================================
    // TRANSACCIONES (ventas)
    // ========================================
    
    public function guardarVenta($data) {
        sqlsrv_begin_transaction($this->db);
        
        try {
            // Obtener id_centro del primer producto
            $idCentro = null;
            if (!empty($data['items'])) {
                $primerItem = $data['items'][0];
                $sqlCentro = "SELECT id_centro FROM BD_PRODUCCIONDESARROLLO.dbo.producto WHERE id_producto = ? AND activo = 1";
                $stmtCentro = sqlsrv_query($this->db, $sqlCentro, [$primerItem['id_producto']]);
                if ($stmtCentro && $rowCentro = sqlsrv_fetch_array($stmtCentro, SQLSRV_FETCH_ASSOC)) {
                    $idCentro = $rowCentro['id_centro'];
                }
            }
            
            // Insertar encabezado de transacción y obtener ID
            $sqlTransaccion = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                        (id_cliente, id_centro, id_voucher, responsable_venta, tipo_op, metodo_pago, estado, fecha_creacion, total, serie_comprobante, correlativo_comprobante, doc_justificante, descuento_planilla, num_grupo)
                        OUTPUT INSERTED.id_transaccion
                        VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?, ?, ?, ?, ?)";
            $paramsTransaccion = [
                $data['id_cliente'],
                $idCentro ?? 1, // id_centro del producto o valor por defecto
                null, // id_voucher - se asigna en proformas
                $_SESSION['usuario_nombre'] ?? 'Sistema', // responsable_venta
                'VENTA', // tipo_op
                $data['metodo_pago'] ?? 'VENTA', // metodo_pago
                ($data['metodo_pago'] ?? 'VENTA') === 'PLANILLA' ? 'PROCESADO' : 'PENDIENTE', // estado
                $data['total'],
                null, // serie_comprobante - se asigna en proformas
                null, // correlativo_comprobante - se asigna en proformas
                null,  // doc_justificante
                ($data['descuento_planilla'] ?? false) ? 1 : 0,
                !empty($data['num_grupo']) ? $data['num_grupo'] : null // num_grupo para ventas masivas
            ];
            $stmtTransaccion = sqlsrv_query($this->db, $sqlTransaccion, $paramsTransaccion);
            if ($stmtTransaccion === false) {
                throw new Exception('Error al insertar transacción: ' . print_r(sqlsrv_errors(), true));
            }
            
            $row = sqlsrv_fetch_array($stmtTransaccion, SQLSRV_FETCH_ASSOC);
            $idTransaccion = $row ? $row['id_transaccion'] : null;
            
            if (!$idTransaccion) {
                throw new Exception('Error: No se pudo obtener el ID de transacción');
            }
            
            // Insertar detalles y descontar de lotes (FIFO)
            foreach ($data['items'] as $item) {
                $cantidadPendiente = $item['cantidad'];
                $idProducto = $item['id_producto'];
                
                // Buscar lotes del producto ordenados por fecha de creacion (FIFO), filtrados por centro
                $sqlLotes = "SELECT id_lote, stock_actual, codigo_lote 
                            FROM BD_PRODUCCIONDESARROLLO.dbo.lote 
                            WHERE id_producto = ? AND stock_actual > 0 AND id_centro = ?
                            ORDER BY fecha_creacion ASC, id_lote ASC";
                $stmtLotes = sqlsrv_query($this->db, $sqlLotes, [$idProducto, $idCentro ?? 1]);
                if ($stmtLotes === false) {
                    throw new Exception('Error al buscar lotes: ' . print_r(sqlsrv_errors(), true));
                }
                
                $lotes = [];
                while ($rowLote = sqlsrv_fetch_array($stmtLotes, SQLSRV_FETCH_ASSOC)) {
                    $lotes[] = $rowLote;
                }
                
                if (empty($lotes)) {
                    throw new Exception("No hay stock disponible para el producto: {$item['nombre']}");
                }
                
                $idLoteUsado = null;
                $saldoKardex = 0;
                
                // Descontar de los lotes más antiguos primero
                foreach ($lotes as $lote) {
                    if ($cantidadPendiente <= 0) break;
                    
                    $idLote = $lote['id_lote'];
                    $stockDisponible = $lote['stock_actual'];
                    $cantidadDescontar = min($cantidadPendiente, $stockDisponible);
                    $nuevoStock = $stockDisponible - $cantidadDescontar;
                    
                    // Actualizar stock del lote
                    $sqlUpdateLote = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.lote 
                                     SET stock_actual = ? 
                                     WHERE id_lote = ?";
                    $stmtUpdateLote = sqlsrv_query($this->db, $sqlUpdateLote, [$nuevoStock, $idLote]);
                    if ($stmtUpdateLote === false) {
                        throw new Exception('Error al actualizar stock del lote: ' . print_r(sqlsrv_errors(), true));
                    }
                    
                    // Obtener saldo actual del kardex para este producto
                    $sqlSaldo = "SELECT TOP 1 saldo_final 
                               FROM BD_PRODUCCIONDESARROLLO.dbo.kardex 
                               WHERE id_lote = ? 
                               ORDER BY fecha DESC, id_kardex DESC";
                    $stmtSaldo = sqlsrv_query($this->db, $sqlSaldo, [$idLote]);
                    $saldoActual = 0;
                    if ($stmtSaldo && $rowSaldo = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                        $saldoActual = $rowSaldo['saldo_final'];
                    }
                    $nuevoSaldo = $saldoActual - $cantidadDescontar;
                    
                    // Registrar en kardex
                    $sqlKardex = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                                (id_lote, id_transaccion, tipo_movimiento, cantidad, saldo_final, fecha)
                                VALUES (?, ?, 'VENTA', ?, ?, GETDATE())";
                    $stmtKardex = sqlsrv_query($this->db, $sqlKardex, [$idLote, $idTransaccion, $cantidadDescontar, $nuevoSaldo]);
                    if ($stmtKardex === false) {
                        throw new Exception('Error al registrar kardex: ' . print_r(sqlsrv_errors(), true));
                    }
                    
                    $cantidadPendiente -= $cantidadDescontar;
                    $idLoteUsado = $idLote;
                    $saldoKardex = $nuevoSaldo;
                }
                
                if ($cantidadPendiente > 0) {
                    throw new Exception("Stock insuficiente para el producto: {$item['nombre']}. Faltan {$cantidadPendiente} unidades.");
                }
                
                // Insertar detalle de transacción con el lote usado (el más antiguo)
                $sqlDetalle = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle 
                               (id_transaccion, id_producto, id_lote, cantidad, precio_unitario, subtotal)
                               OUTPUT INSERTED.id_detalle
                               VALUES (?, ?, ?, ?, ?, ?)";
                $paramsDetalle = [
                    $idTransaccion,
                    $idProducto,
                    $idLoteUsado,
                    $item['cantidad'],
                    $item['precio'],
                    $item['subtotal']
                ];
                $stmtDetalle = sqlsrv_query($this->db, $sqlDetalle, $paramsDetalle);
                if ($stmtDetalle === false) {
                    throw new Exception('Error al insertar detalle: ' . print_r(sqlsrv_errors(), true));
                }
                
                // Obtener el ID del detalle insertado
                $rowDetalle = sqlsrv_fetch_array($stmtDetalle, SQLSRV_FETCH_ASSOC);
                $idDetalle = $rowDetalle ? $rowDetalle['id_detalle'] : null;
            }
            
            sqlsrv_commit($this->db);
            return ['success' => true, 'id_transaccion' => $idTransaccion];
            
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listarVentasHoy() {
        $sql = "SELECT t.id_transaccion, t.fecha_creacion as fecha, t.metodo_pago, t.total, t.estado,
                       c.nombre_rs as cliente
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente AND c.activo = 1
                WHERE CAST(t.fecha_creacion as DATE) = CAST(GETDATE() as DATE)
                AND t.tipo_op = 'VENTA'
                ORDER BY t.fecha_creacion DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function crearClienteRapido($nombre) {
        try {
            $dniTemp = 'TEMP' . date('ymd') . rand(1000, 9999);
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.cliente (dni_ruc, nombre_rs, tipo_cliente) VALUES (?, ?, 0)";
            $stmt = sqlsrv_query($this->db, $sql, [$dniTemp, $nombre]);
            if ($stmt === false) {
                throw new Exception('Error al registrar cliente rápido: ' . print_r(sqlsrv_errors(), true));
            }
            
            $sqlId = "SELECT @@IDENTITY as id_cliente";
            $stmtId = sqlsrv_query($this->db, $sqlId);
            $rowId = sqlsrv_fetch_array($stmtId, SQLSRV_FETCH_ASSOC);
            $idCliente = $rowId ? intval($rowId['id_cliente']) : null;
            
            return [
                'success' => true,
                'id_cliente' => $idCliente,
                'nombre_rs' => $nombre,
                'dni_ruc' => $dniTemp
            ];
        } catch (Exception $e) {
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

    // ========================================
    // BUSQUEDA DE CLIENTES POR API (RENIEC/SUNAT/PERSONAL PECH)
    // ========================================

    public function httpGet($url) {
        $hasCurl = function_exists('curl_init');
        $this->lastError = '';

        if ($hasCurl) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            $resp = curl_exec($ch);
            $curlErr = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            $httpCode = $curlInfo['http_code'] ?? 0;
            curl_close($ch);

            if ($resp === false || $resp === '') {
                $this->lastError = "curl error: " . ($curlErr ?: 'empty response') . " http=$httpCode";
                return null;
            }
            $decoded = json_decode($resp, true);
            if ($decoded === null) {
                $sample = substr($resp, 0, 300);
                $this->lastError = "http=$httpCode, not JSON. Sample: " . $sample;
                return null;
            }
            return $decoded;
        }

        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create([
                'http' => ['timeout' => 10, 'header' => "User-Agent: gestionTI/1.0\r\n"],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp === false || $resp === '') {
                $this->lastError = 'fopen: empty response';
                return null;
            }
            $decoded = @json_decode($resp, true);
            if ($decoded === null) {
                $sample = substr($resp, 0, 200);
                $this->lastError = "fopen: not JSON. Sample: " . $sample;
                return null;
            }
            return $decoded;
        }

        $this->lastError = 'no HTTP client available';
        return null;
    }

    private function consultarRENIEC($dni) {
        $data = $this->httpGet("https://api.apis.net.pe/v1/dni?numero=$dni");
        return $data['nombre'] ?? null;
    }

    private function consultarSUNAT($ruc) {
        $data = $this->httpGet("https://api.apis.net.pe/v1/ruc?numero=$ruc");
        return $data['nombre'] ?? null;
    }

    private function consultarPersonalPECH($dni) {
        $data = $this->httpGet("https://www.chavimochic.gob.pe/api_incidencias/api_personal.php?documento=$dni");
        if ($data && isset($data['success']) && $data['success'] && !empty($data['data'])) {
            $emp = $data['data'][0];
            $nombre = trim(($emp['Trab_Paterno'] ?? '') . ' ' . ($emp['Trab_Materno'] ?? '') . ' ' . ($emp['Nombres'] ?? ''));
            return ['empleado' => true, 'nombre' => $nombre];
        }
        return ['empleado' => false, 'nombre' => null];
    }

    public function buscarClientePorAPI($documento) {
        $doc = trim($documento);
        $this->lastError = '';

        if (empty($doc)) return null;

        $s = sqlsrv_query($this->db,
            "SELECT id_cliente, dni_ruc, nombre_rs, tipo_cliente FROM BD_PRODUCCIONDESARROLLO.dbo.cliente WHERE dni_ruc = ?",
            [$doc]);
        if ($s && sqlsrv_has_rows($s)) {
            $r = sqlsrv_fetch_array($s, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($s);
            sqlsrv_query($this->db, "UPDATE BD_PRODUCCIONDESARROLLO.dbo.cliente SET activo = 1 WHERE id_cliente = ?", [$r['id_cliente']]);
            $r['tipo_cliente'] = ($r['tipo_cliente'] == 0) ? 'Planilla' : 'Externo';
            $r['fuente'] = 'BD';
            return $r;
        }
        sqlsrv_free_stmt($s);

        $len = strlen($doc);
        $t1 = microtime(true);

        if ($len === 8 && ctype_digit($doc)) {
            $nombre = $this->consultarRENIEC($doc);
            $errReniec = $this->lastError;
            $pech = $this->consultarPersonalPECH($doc);
            $errPech = $this->lastError;
            $esEmpleado = $pech['empleado'];
            $nombrePech = $pech['nombre'];
            $tipo = $esEmpleado ? 0 : 1;

            $nombreFinal = $nombre;
            $fuente = 'RENIEC';

            if (!$nombre && $nombrePech) {
                $nombreFinal = $nombrePech;
                $fuente = 'PERSONAL_PECH';
            }
            if (!$nombre && !$nombrePech && $esEmpleado) {
                $nombreFinal = 'EMPLEADO PECH - ' . $doc;
                $fuente = 'PERSONAL_PECH';
            }

            $this->lastError = "DNI $doc: RENIEC=" . ($nombre?:'FAIL') . " ($errReniec) | PECH=" . ($esEmpleado?'SI':'NO') . " nombre=" . ($nombrePech?:'NO') . " ($errPech)";

            if ($nombreFinal) {
                $r = $this->registrarClienteAPI($doc, $nombreFinal, $tipo, $fuente);
                if ($r) $r['_diag'] = "{$fuente}=OK, PersonalPECH=" . ($esEmpleado?'SI':'NO') . ", time=" . round((microtime(true)-$t1)*1000)."ms";
                return $r;
            }
            return null;
        }

        if ($len === 11 && ctype_digit($doc)) {
            $nombre = $this->consultarSUNAT($doc);
            $errSunat = $this->lastError;
            if ($nombre) {
                $r = $this->registrarClienteAPI($doc, $nombre, 1, 'SUNAT');
                if ($r) $r['_diag'] = "SUNAT=OK, time=" . round((microtime(true)-$t1)*1000)."ms";
                return $r;
            }
            $this->lastError = "RUC $doc: SUNAT=FAIL ($errSunat)";
        }

        return null;
    }

    public function registrarClienteAPI($dniRuc, $nombreRs, $tipoCliente, $fuente) {
        $tipoCliente = intval($tipoCliente);
        $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.cliente (dni_ruc, nombre_rs, tipo_cliente, activo)
                OUTPUT INSERTED.id_cliente VALUES (?, ?, ?, 1)";
        $stmt = sqlsrv_query($this->db, $sql, [$dniRuc, $nombreRs, $tipoCliente]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return [
                'id_cliente' => $row['id_cliente'],
                'dni_ruc' => $dniRuc,
                'nombre_rs' => $nombreRs,
                'tipo_cliente' => ($tipoCliente == 0) ? 'Planilla' : 'Externo',
                'fuente' => $fuente
            ];
        }

        $errors = sqlsrv_errors();
        $isDuplicate = false;
        foreach ($errors as $err) {
            if ($err['code'] == 2627 || $err['code'] == 2601) {
                $isDuplicate = true;
                break;
            }
        }

        if ($isDuplicate) {
            $upd = sqlsrv_query($this->db,
                "UPDATE BD_PRODUCCIONDESARROLLO.dbo.cliente SET activo = 1, nombre_rs = ?, tipo_cliente = ?
                 OUTPUT INSERTED.id_cliente, INSERTED.dni_ruc, INSERTED.nombre_rs, INSERTED.tipo_cliente
                 WHERE dni_ruc = ?",
                [$nombreRs, $tipoCliente, $dniRuc]);
            if ($upd && sqlsrv_has_rows($upd)) {
                $row = sqlsrv_fetch_array($upd, SQLSRV_FETCH_ASSOC);
                return [
                    'id_cliente' => $row['id_cliente'],
                    'dni_ruc' => $row['dni_ruc'],
                    'nombre_rs' => $row['nombre_rs'],
                    'tipo_cliente' => ($row['tipo_cliente'] == 0) ? 'Planilla' : 'Externo',
                    'fuente' => $fuente . ' (reutilizado)'
                ];
            }
            $this->lastError = 'UPDATE fallido: ' . json_encode(sqlsrv_errors());
        } else {
            $this->lastError = 'SQL INSERT ERROR: ' . json_encode($errors);
        }
        return null;
    }
}
?>
