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
        // tipo_cliente: 0 = Planilla (empleados PECH), 1 = Externo
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs,
                       CASE WHEN tipo_cliente = 0 THEN 'Planilla' ELSE 'Externo' END as tipo_cliente 
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
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase AND c.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro AND cp.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit u ON u.anio = YEAR(GETDATE()) AND u.activo = 1
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial,
                           ROW_NUMBER() OVER (PARTITION BY hp1.id_producto ORDER BY hp1.fecha_registro DESC, hp1.id_historial DESC) AS rn
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                ) hp ON p.id_producto = hp.id_producto AND hp.rn = 1
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
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.clase c ON p.id_clase = c.id_clase AND c.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON p.id_centro = cp.id_centro AND cp.activo = 1
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.uit u ON u.anio = YEAR(GETDATE()) AND u.activo = 1
                LEFT JOIN (
                    SELECT hp1.id_producto, hp1.precio_oficial,
                           ROW_NUMBER() OVER (PARTITION BY hp1.id_producto ORDER BY hp1.fecha_registro DESC, hp1.id_historial DESC) AS rn
                    FROM BD_PRODUCCIONDESARROLLO.dbo.historial_precio hp1
                ) hp ON p.id_producto = hp.id_producto AND hp.rn = 1
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
            // ── Validación básica ──
            if (empty($data['id_cliente'])) {
                throw new Exception('Debe seleccionar un cliente.');
            }
            if (empty($data['items']) || !is_array($data['items'])) {
                throw new Exception('No se recibieron ítems de venta.');
            }

            // ── Fecha opcional (permite ventas retroactivas) ──
            $fecha = null;
            if (!empty($data['fecha'])) {
                $fechaObj = DateTime::createFromFormat('Y-m-d', $data['fecha']);
                if ($fechaObj) {
                    $fecha = $fechaObj->format('Y-m-d');
                }
            }

            // ── Normalizar ítems y RECALCULAR precios en servidor ──
            // No se confía en el precio/subtotal/total enviado por el cliente.
            $itemsNorm = [];
            $total = 0.0;
            $idCentro = null;

            foreach ($data['items'] as $item) {
                $idProducto = intval($item['id_producto'] ?? 0);
                $cantidad = intval($item['cantidad'] ?? 0);

                if ($idProducto <= 0) {
                    throw new Exception('Producto inválido en la venta.');
                }
                if ($cantidad <= 0) {
                    throw new Exception('La cantidad debe ser un número entero mayor a cero.');
                }

                $producto = $this->buscarProducto($idProducto);
                if (!$producto) {
                    throw new Exception('Producto no encontrado o no disponible para la venta.');
                }

                $precio = floatval($producto['precio_venta'] ?? 0);
                if ($precio <= 0) {
                    throw new Exception("El producto '{$producto['nombre']}' no tiene precio definido.");
                }

                if ($idCentro === null) {
                    $idCentro = $producto['id_centro'] ?? null;
                }

                $subtotal = round($precio * $cantidad, 2);
                $total += $subtotal;

                $itemsNorm[] = [
                    'id_producto' => $idProducto,
                    'nombre'      => $producto['nombre'],
                    'cantidad'    => $cantidad,
                    'precio'      => $precio,
                    'subtotal'    => $subtotal,
                    'id_centro'   => $producto['id_centro'] ?? null,
                ];
            }
            $total = round($total, 2);

            // ── Insertar encabezado de transacción ──
            $sqlTransaccion = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                        (id_cliente, id_centro, id_voucher, responsable_venta, tipo_op, metodo_pago, estado, fecha_creacion, total, serie_comprobante, correlativo_comprobante, doc_justificante, descuento_planilla, num_grupo)
                        OUTPUT INSERTED.id_transaccion
                        VALUES (?, ?, ?, ?, ?, ?, ?, ISNULL(CAST(? AS DATE), GETDATE()), ?, ?, ?, ?, ?, ?)";
            $paramsTransaccion = [
                $data['id_cliente'],
                $idCentro ?? 1, // id_centro del primer producto o valor por defecto
                null, // id_voucher - se asigna en proformas
                $_SESSION['usuario_nombre'] ?? 'Sistema', // responsable_venta
                'VENTA', // tipo_op
                $data['metodo_pago'] ?? 'VENTA', // metodo_pago
                ($data['metodo_pago'] ?? 'VENTA') === 'PLANILLA' ? 'PROCESADO' : 'PENDIENTE', // estado
                $fecha, // fecha_creacion (retroactiva si se indicó)
                $total,
                null, // serie_comprobante - se asigna en proformas
                null, // correlativo_comprobante - se asigna en proformas
                null,  // doc_justificante
                ($data['descuento_planilla'] ?? false) ? 1 : 0,
                !empty($data['num_grupo']) ? $data['num_grupo'] : null // num_grupo para ventas masivas
            ];
            $stmtTransaccion = sqlsrv_query($this->db, $sqlTransaccion, $paramsTransaccion);
            if ($stmtTransaccion === false) {
                error_log('[guardarVenta] Error insertando transaccion: ' . print_r(sqlsrv_errors(), true));
                throw new Exception('Error al insertar la transacción.');
            }

            $row = sqlsrv_fetch_array($stmtTransaccion, SQLSRV_FETCH_ASSOC);
            $idTransaccion = $row ? $row['id_transaccion'] : null;

            if (!$idTransaccion) {
                throw new Exception('No se pudo obtener el ID de la transacción.');
            }

            // ── Descontar stock FIFO e insertar detalle por lote ──
            foreach ($itemsNorm as $item) {
                $cantidadPendiente = $item['cantidad'];
                $idProducto = $item['id_producto'];

                // UPDLOCK evita sobreventa ante ventas concurrentes del mismo lote
                $sqlLotes = "SELECT id_lote, stock_actual, codigo_lote 
                            FROM BD_PRODUCCIONDESARROLLO.dbo.lote WITH (UPDLOCK, ROWLOCK) 
                            WHERE id_producto = ? AND stock_actual > 0 AND id_centro = ?
                            ORDER BY fecha_creacion ASC, id_lote ASC";
                $stmtLotes = sqlsrv_query($this->db, $sqlLotes, [$idProducto, $item['id_centro'] ?? $idCentro ?? 1]);
                if ($stmtLotes === false) {
                    error_log('[guardarVenta] Error buscando lotes: ' . print_r(sqlsrv_errors(), true));
                    throw new Exception('Error al buscar lotes del producto.');
                }

                $lotes = [];
                while ($rowLote = sqlsrv_fetch_array($stmtLotes, SQLSRV_FETCH_ASSOC)) {
                    $lotes[] = $rowLote;
                }

                if (empty($lotes)) {
                    throw new Exception("No hay stock disponible para el producto: {$item['nombre']}");
                }

                $asignaciones = []; // id_lote => cantidad usada

                // Descontar de los lotes más antiguos primero (FIFO)
                foreach ($lotes as $lote) {
                    if ($cantidadPendiente <= 0) break;

                    $idLote = $lote['id_lote'];
                    $stockDisponible = intval($lote['stock_actual']);
                    $cantidadDescontar = min($cantidadPendiente, $stockDisponible);
                    $nuevoStock = $stockDisponible - $cantidadDescontar;

                    // Actualizar stock del lote
                    $sqlUpdateLote = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.lote 
                                     SET stock_actual = ? 
                                     WHERE id_lote = ?";
                    $stmtUpdateLote = sqlsrv_query($this->db, $sqlUpdateLote, [$nuevoStock, $idLote]);
                    if ($stmtUpdateLote === false) {
                        error_log('[guardarVenta] Error actualizando lote: ' . print_r(sqlsrv_errors(), true));
                        throw new Exception('Error al actualizar el stock del lote.');
                    }

                    // Obtener saldo actual del kardex para este lote
                    $sqlSaldo = "SELECT TOP 1 saldo_final 
                               FROM BD_PRODUCCIONDESARROLLO.dbo.kardex 
                               WHERE id_lote = ? 
                               ORDER BY fecha DESC, id_kardex DESC";
                    $stmtSaldo = sqlsrv_query($this->db, $sqlSaldo, [$idLote]);
                    $saldoActual = 0;
                    if ($stmtSaldo && $rowSaldo = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                        $saldoActual = floatval($rowSaldo['saldo_final'] ?? 0);
                    }
                    $nuevoSaldo = $saldoActual - $cantidadDescontar;

                    // Registrar en kardex
                    $sqlKardex = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.kardex 
                                (id_lote, id_transaccion, tipo_movimiento, cantidad, saldo_final, fecha)
                                VALUES (?, ?, 'VENTA', ?, ?, GETDATE())";
                    $stmtKardex = sqlsrv_query($this->db, $sqlKardex, [$idLote, $idTransaccion, $cantidadDescontar, $nuevoSaldo]);
                    if ($stmtKardex === false) {
                        error_log('[guardarVenta] Error insertando kardex: ' . print_r(sqlsrv_errors(), true));
                        throw new Exception('Error al registrar el kardex.');
                    }

                    $asignaciones[] = ['id_lote' => $idLote, 'cantidad' => $cantidadDescontar];
                    $cantidadPendiente -= $cantidadDescontar;
                }

                if ($cantidadPendiente > 0) {
                    throw new Exception("Stock insuficiente para el producto: {$item['nombre']}. Faltan {$cantidadPendiente} unidades.");
                }

                // Una fila de detalle por cada lote usado (trazabilidad FIFO completa)
                foreach ($asignaciones as $asig) {
                    $subtotalLote = round($asig['cantidad'] * $item['precio'], 2);
                    $sqlDetalle = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.transaccion_detalle 
                                   (id_transaccion, id_producto, id_lote, cantidad, precio_unitario, subtotal)
                                   VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtDetalle = sqlsrv_query($this->db, $sqlDetalle, [
                        $idTransaccion,
                        $idProducto,
                        $asig['id_lote'],
                        $asig['cantidad'],
                        $item['precio'],
                        $subtotalLote
                    ]);
                    if ($stmtDetalle === false) {
                        error_log('[guardarVenta] Error insertando detalle: ' . print_r(sqlsrv_errors(), true));
                        throw new Exception('Error al insertar el detalle de la venta.');
                    }
                }
            }

            sqlsrv_commit($this->db);
            return ['success' => true, 'id_transaccion' => $idTransaccion];

        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            error_log('[guardarVenta] Venta rechazada: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function crearClienteRapido($nombre) {
        try {
            $dniTemp = 'TEMP' . date('ymd') . rand(1000, 9999);
            // tipo_cliente: 0 = Planilla (empleados PECH), 1 = Externo
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.cliente (dni_ruc, nombre_rs, tipo_cliente)
                    OUTPUT INSERTED.id_cliente VALUES (?, ?, 1)";
            $stmt = sqlsrv_query($this->db, $sql, [$dniTemp, $nombre]);
            if ($stmt === false) {
                error_log('[PuntoVentaModel::crearClienteRapido] SQL Error: ' . print_r(sqlsrv_errors(), true));
                throw new Exception('Error al registrar cliente rápido.');
            }

            $rowId = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $idCliente = $rowId ? intval($rowId['id_cliente']) : null;

            return [
                'success' => true,
                'id_cliente' => $idCliente,
                'nombre_rs' => $nombre,
                'dni_ruc' => $dniTemp,
                'tipo_cliente' => 'Externo'
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
                return $r;
            }
            return null;
        }

        if ($len === 11 && ctype_digit($doc)) {
            $nombre = $this->consultarSUNAT($doc);
            $errSunat = $this->lastError;
            if ($nombre) {
                $r = $this->registrarClienteAPI($doc, $nombre, 1, 'SUNAT');
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
