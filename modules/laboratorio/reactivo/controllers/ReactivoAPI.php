<?php
/**
 * ReactivoAPI.php
 * API Handler - Maneja acciones AJAX para Reactivos
 * Con validaciones completas y kardex integrado
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');  // NO mostrar errores en la salida
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

header('Content-Type: application/json; charset=utf-8');

// Capturar errores PHP y devolverlos como JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});

try {
    require_once '../../../../config/db.php';
    require_once '../../../../core/Auth.php';
    require_once '../../Validaciones.php';
    require_once '../models/ReactivoModel.php';
    require_once '../models/UnidadMedidaModel.php';
    require_once '../../proveedor/models/ProveedorModel.php';

    Auth::check();

    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }

    $reactivo_model  = new ReactivoModel($conn);
    $unidad_model    = new UnidadMedidaModel($conn);
    $proveedor_model = new ProveedorModel($conn);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }
    
    // ==================== REACTIVOS ====================
    
    if ($action === 'listar') {
        $reactivos = $reactivo_model->obtenerTodos();
        
        // Convertir DateTime objects a strings para JSON
        foreach ($reactivos as &$reactivo) {
            // Convertir Fecha_Vencimiento
            if (isset($reactivo['Fecha_Vencimiento'])) {
                if ($reactivo['Fecha_Vencimiento'] instanceof DateTime) {
                    $reactivo['Fecha_Vencimiento'] = $reactivo['Fecha_Vencimiento']->format('Y-m-d');
                } elseif (is_string($reactivo['Fecha_Vencimiento'])) {
                    // Si ya es string, asegurarse que esté en formato YYYY-MM-DD
                    $reactivo['Fecha_Vencimiento'] = substr($reactivo['Fecha_Vencimiento'], 0, 10);
                }
            }
            
            // Convertir Fecha_Ingreso
            if (isset($reactivo['Fecha_Ingreso'])) {
                if ($reactivo['Fecha_Ingreso'] instanceof DateTime) {
                    $reactivo['Fecha_Ingreso'] = $reactivo['Fecha_Ingreso']->format('Y-m-d H:i:s');
                } elseif (is_string($reactivo['Fecha_Ingreso'])) {
                    // Si ya es string, asegurarse que esté bien formateado
                    if (strpos($reactivo['Fecha_Ingreso'], 'T') !== false) {
                        // ISO format
                        $reactivo['Fecha_Ingreso'] = str_replace('T', ' ', substr($reactivo['Fecha_Ingreso'], 0, 19));
                    }
                }
            }
            
            // Convertir Fecha_Creacion
            if (isset($reactivo['Fecha_Creacion'])) {
                if ($reactivo['Fecha_Creacion'] instanceof DateTime) {
                    $reactivo['Fecha_Creacion'] = $reactivo['Fecha_Creacion']->format('Y-m-d H:i:s');
                }
            }
            
            // Convertir Fecha_Modificacion
            if (isset($reactivo['Fecha_Modificacion'])) {
                if ($reactivo['Fecha_Modificacion'] instanceof DateTime) {
                    $reactivo['Fecha_Modificacion'] = $reactivo['Fecha_Modificacion']->format('Y-m-d H:i:s');
                }
            }
        }
        
        echo json_encode(['success' => true, 'data' => $reactivos]);
        exit;
    }
    
    if ($action === 'guardar') {
        $datos = json_decode(file_get_contents('php://input'), true);
        $usuarioId = $_SESSION['usuario_id'] ?? 1;
        
        // Validaciones del reactivo
        $errores = [];
        
        // Validar Nombre (obligatorio, 3-100 chars, debe tener letras)
        $errores['Nombre'] = Validaciones::validarNombre(
            $datos['Nombre'] ?? '',
            3,
            100
        );
        
        // Validar Unidad de Medida (opcional, texto legacy)
        $errores['Unidad_Medida'] = Validaciones::validarTexto(
            $datos['Unidad_Medida'] ?? '',
            false,
            20
        );

        // Validar Fecha Vencimiento (opcional, YYYY-MM-DD)
        $errores['Fecha_Vencimiento'] = Validaciones::validarFecha(
            $datos['Fecha_Vencimiento'] ?? '',
            false
        );

        // Validar Tipo (opcional: Agua | Suelo)
        if (!empty($datos['Tipo']) && !in_array($datos['Tipo'], ['Agua', 'Suelo'])) {
            $errores['Tipo'] = 'Tipo debe ser Agua o Suelo';
        }

        // Validar Cantidad Inicial (obligatoria para crear, debe ser positiva)
        $cantidadInicial = $datos['Cantidad_Inicial'] ?? null;
        if (empty($cantidadInicial) || !is_numeric($cantidadInicial) || floatval($cantidadInicial) <= 0) {
            $errores['Cantidad_Inicial'] = 'La cantidad inicial es obligatoria y debe ser mayor a 0';
        }
        
        // Si hay errores, devolverlos
        if (Validaciones::hayErrores($errores)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errores en los campos',
                'errors' => Validaciones::obtenerErrores($errores)
            ]);
            exit;
        }
        
        // Crear el reactivo
        $id = $reactivo_model->guardar($datos, $usuarioId);
        
        // Registrar el ingreso inicial en el kardex
        try {
            $reactivo_model->registrarIngreso(
                $id,
                floatval($cantidadInicial),
                'INGRESO INICIAL',
                $usuarioId
            );
        } catch (Exception $e) {
            // Log del error pero continuamos
            error_log('Error registrando ingreso inicial: ' . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            'message' => 'Reactivo guardado correctamente con cantidad inicial en kardex'
        ]);
        exit;
    }
    
    if ($action === 'obtener') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $reactivo = $reactivo_model->obtenerPorId($id);
        
        // Convertir DateTime objects a strings para JSON
        if ($reactivo) {
            if (isset($reactivo['Fecha_Vencimiento']) && $reactivo['Fecha_Vencimiento'] instanceof DateTime) {
                $reactivo['Fecha_Vencimiento'] = $reactivo['Fecha_Vencimiento']->format('Y-m-d');
            } elseif (isset($reactivo['Fecha_Vencimiento']) && is_string($reactivo['Fecha_Vencimiento'])) {
                $reactivo['Fecha_Vencimiento'] = substr($reactivo['Fecha_Vencimiento'], 0, 10);
            }
            
            if (isset($reactivo['Fecha_Ingreso']) && $reactivo['Fecha_Ingreso'] instanceof DateTime) {
                $reactivo['Fecha_Ingreso'] = $reactivo['Fecha_Ingreso']->format('Y-m-d H:i:s');
            }
        }
        
        echo json_encode(['success' => true, 'data' => $reactivo]);
        exit;
    }
    
    if ($action === 'actualizar') {
        $datos = json_decode(file_get_contents('php://input'), true);
        $usuarioId = $_SESSION['usuario_id'] ?? 1;
        
        $errores = [];
        
        // Validar ID
        $errores['Id_Reactivo'] = Validaciones::validarId(
            $datos['Id_Reactivo'] ?? null,
            true
        );
        
        // Validar Nombre (obligatorio, 3-100 chars, debe tener letras)
        $errores['Nombre'] = Validaciones::validarNombre(
            $datos['Nombre'] ?? '',
            3,
            100
        );
        
        // Validar Unidad de Medida (opcional, texto legacy)
        $errores['Unidad_Medida'] = Validaciones::validarTexto(
            $datos['Unidad_Medida'] ?? '',
            false,
            20
        );

        // Validar Fecha Vencimiento (opcional, YYYY-MM-DD)
        $errores['Fecha_Vencimiento'] = Validaciones::validarFecha(
            $datos['Fecha_Vencimiento'] ?? '',
            false
        );

        // Validar Tipo (opcional: Agua | Suelo)
        if (!empty($datos['Tipo']) && !in_array($datos['Tipo'], ['Agua', 'Suelo'])) {
            $errores['Tipo'] = 'Tipo debe ser Agua o Suelo';
        }

        // Si hay errores, devolverlos
        if (Validaciones::hayErrores($errores)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errores en los campos',
                'errors' => Validaciones::obtenerErrores($errores)
            ]);
            exit;
        }
        
        $reactivo_model->guardar($datos, $usuarioId);
        echo json_encode(['success' => true, 'message' => 'Reactivo actualizado correctamente']);
        exit;
    }
    
    if ($action === 'eliminar') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $reactivo_model->eliminar($id);
        echo json_encode(['success' => true, 'message' => 'Reactivo eliminado correctamente']);
        exit;
    }
    
    // ==================== INGRESOS/KARDEX ====================
    
    if ($action === 'registrar_ingreso') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $usuarioId = $_SESSION['usuario_id'] ?? 1;
            
            $errores = [];
            
            // Validar ID Reactivo
            $errores['Id_Reactivo'] = Validaciones::validarId(
                $datos['Id_Reactivo'] ?? null,
                true
            );
            
            // Validar Cantidad
            $cantidad = $datos['Cantidad'] ?? null;
            if (empty($cantidad) || !is_numeric($cantidad) || floatval($cantidad) <= 0) {
                $errores['Cantidad'] = 'La cantidad debe ser un número mayor a 0';
            }
            
            // Validar Factura (opcional, máx 50)
            $errores['Factura_Referencia'] = Validaciones::validarTexto(
                $datos['Factura_Referencia'] ?? '',
                false,
                50
            );
            
            if (Validaciones::hayErrores($errores)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Errores en los campos',
                    'errors' => Validaciones::obtenerErrores($errores)
                ]);
                exit;
            }
            
            $ingresoId = $reactivo_model->registrarIngreso(
                $datos['Id_Reactivo'],
                floatval($datos['Cantidad']),
                $datos['Factura_Referencia'] ?? 'S/N',
                $usuarioId
            );
            
            echo json_encode([
                'success' => true,
                'id' => $ingresoId,
                'message' => 'Ingreso registrado correctamente en kardex'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al registrar ingreso: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($action === 'registrar_salida') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $usuarioId = $_SESSION['usuario_id'] ?? 1;
            
            $errores = [];
            
            // Validar ID Reactivo
            $errores['Id_Reactivo'] = Validaciones::validarId(
                $datos['Id_Reactivo'] ?? null,
                true
            );
            
            // Validar Cantidad
            $cantidad = $datos['Cantidad'] ?? null;
            if (empty($cantidad) || !is_numeric($cantidad) || floatval($cantidad) <= 0) {
                $errores['Cantidad'] = 'La cantidad debe ser un número mayor a 0';
            }
            
            // Validar Concepto (opcional, máx 200)
            $errores['Concepto'] = Validaciones::validarTexto(
                $datos['Concepto'] ?? '',
                false,
                200
            );
            
            if (Validaciones::hayErrores($errores)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Errores en los campos',
                    'errors' => Validaciones::obtenerErrores($errores)
                ]);
                exit;
            }
            
            $salidaId = $reactivo_model->registrarSalida(
                $datos['Id_Reactivo'],
                floatval($datos['Cantidad']),
                $datos['Concepto'] ?? 'S/N',
                $usuarioId
            );
            
            echo json_encode([
                'success' => true,
                'id' => $salidaId,
                'message' => 'Salida registrada correctamente en kardex'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al registrar salida: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($action === 'obtener_kardex') {
        $id = $_GET['id'] ?? null;
        $limite = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;
        
        if ($id) {
            // Kardex de un reactivo específico
            $movimientos = $reactivo_model->obtenerKardex($id, $limite, $offset);
            $stock = $reactivo_model->obtenerStock($id);
        } else {
            // Kardex completo de todos los reactivos
            $movimientos = $reactivo_model->obtenerKardexCompleto($limite, $offset);
            $stock = null;
        }
        
        echo json_encode([
            'success' => true,
            'stock' => $stock,
            'movimientos' => $movimientos
        ]);
        exit;
    }
    
    // Acción no encontrada
    if ($action === 'reactivar') {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            exit;
        }

        $reactivo_model->reactivar($id);
        echo json_encode(['success' => true, 'message' => 'Reactivo reactivado correctamente']);
        exit;
    }

    // ==================== DETALLES INGRESOS POR FECHA ====================
    
    if ($action === 'obtener_detalles_ingreso') {
        try {
            $fecha = $_GET['fecha'] ?? null;
            $id_reactivo = intval($_GET['id_reactivo'] ?? 0);
            
            if (!$fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Fecha requerida']);
                exit;
            }
            
            // Validar formato de fecha
            $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
            if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (use Y-m-d)']);
                exit;
            }
            
            // Query para obtener ingresos de esa fecha
            $sql = "
                SELECT 
                    ir.Id_Ingreso,
                    ir.Id_Reactivo,
                    ir.Cantidad,
                    ir.Factura_Referencia,
                    CAST(ir.Fecha_Ingreso AS DATE) as Fecha_Dia,
                    rl.Nombre as Reactivo_Nombre,
                    ISNULL(um.Abreviatura, '') AS Unidad_Medida
                FROM laboratorio.Ingreso_Reactivo ir
                INNER JOIN laboratorio.Reactivo_Lab rl ON ir.Id_Reactivo = rl.Id_Reactivo
                LEFT JOIN laboratorio.Unidad_Medida um ON um.Id_Unidad_Medida = rl.Id_Unidad_Medida AND um.Activo = 1
                WHERE CAST(ir.Fecha_Ingreso AS DATE) = ?
                AND rl.Activo = 1
                ORDER BY ir.Fecha_Ingreso DESC, rl.Nombre
            ";

            $params = [$fecha];
            if ($id_reactivo > 0) {
                $sql = str_replace('ORDER BY', 'AND ir.Id_Reactivo = ? ORDER BY', $sql);
                $params[] = $id_reactivo;
            }

            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en consulta: ' . print_r(sqlsrv_errors(), true));
            }
            
            $ingresos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $ingresos[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'fecha' => $fecha,
                'ingresos' => $ingresos,
                'total' => count($ingresos)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener ingresos: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== DETALLES SALIDAS POR FECHA/REACTIVO ====================

    if ($action === 'obtener_detalles_salida') {
        try {
            $fecha = $_GET['fecha'] ?? null;
            $id_reactivo = intval($_GET['id_reactivo'] ?? 0);

            if (!$fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Fecha requerida']);
                exit;
            }

            $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
            if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (use Y-m-d)']);
                exit;
            }

            $sql = "
                SELECT
                    mk.Id_Movimiento,
                    mk.Id_Reactivo,
                    mk.Cantidad,
                    mk.Concepto,
                    CONVERT(VARCHAR(19), mk.Fecha_Registro, 120) AS Fecha_Hora,
                    rl.Nombre AS Reactivo_Nombre,
                    ISNULL(um.Abreviatura, '') AS Unidad_Medida,
                    cr.Id_Muestra_Producto,
                    mp.Id_Muestra,
                    ml.Id_Proyecto,
                    mp.Id_Producto_Venta,
                    pv.Nombre_Comercial AS Producto_Nombre,
                    CASE
                        WHEN mk.Concepto LIKE '%Consumo extra manual%' THEN 'Consumo extra manual'
                        WHEN mk.Concepto LIKE '%Consumo extra por repeticion%' THEN 'Consumo extra analisis'
                        WHEN mk.Concepto LIKE '%Consumo interno por muestra producto%' THEN 'Consumo interno'
                        WHEN cr.Id_Movimiento IS NOT NULL THEN 'Consumo tecnico'
                        ELSE 'Salida manual'
                    END AS Tipo_Detalle,
                    COALESCE(
                        CASE WHEN ml.Id_Proyecto IS NOT NULL THEN
                            ISNULL(pm.Nombre_Proyecto, 'Proyecto #' + CAST(ml.Id_Proyecto AS VARCHAR(20)))
                            + ISNULL(' (' + CONVERT(VARCHAR(10), pm.Fecha_Inicio, 103) + ')', '')
                            + CASE WHEN pm.Es_Control_Calidad = 1 THEN N' \u2014 Calidad de Agua' ELSE N' \u2014 Monitoreo' END
                        END,
                        CASE WHEN ml.Id_Cliente IS NOT NULL THEN
                            'Cliente: ' + LTRIM(RTRIM(CONCAT(ISNULL(c.Nombres, ''), ' ', ISNULL(c.Apellido_Paterno, ''), ' ', ISNULL(c.Apellido_Materno, ''))))
                        END,
                        'Otros consumos'
                    ) AS Segmento_Principal,
                    COALESCE(
                        CASE WHEN mp.Id_Muestra IS NOT NULL THEN 'Muestra #' + CAST(mp.Id_Muestra AS VARCHAR(20)) END,
                        'Movimiento #' + CAST(mk.Id_Movimiento AS VARCHAR(20))
                    ) AS Segmento_Secundario
                FROM laboratorio.Movimiento_Kardex mk
                INNER JOIN laboratorio.Reactivo_Lab rl ON mk.Id_Reactivo = rl.Id_Reactivo
                LEFT JOIN laboratorio.Consumo_Reaccion cr ON cr.Id_Movimiento = mk.Id_Movimiento AND cr.Activo = 1
                LEFT JOIN laboratorio.Muestra_Producto mp ON mp.Id_Muestra_Producto = cr.Id_Muestra_Producto AND mp.Activo = 1
                LEFT JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = mp.Id_Muestra AND ml.Activo = 1
                LEFT JOIN laboratorio.Cliente c ON c.Id_Cliente = ml.Id_Cliente
                LEFT JOIN laboratorio.Producto_Venta pv ON pv.Id_Producto = mp.Id_Producto_Venta
                LEFT JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = ml.Id_Proyecto AND pm.Activo = 1
                LEFT JOIN laboratorio.Unidad_Medida um ON um.Id_Unidad_Medida = rl.Id_Unidad_Medida AND um.Activo = 1
                WHERE mk.Activo = 1
                  AND mk.Tipo_Movimiento = 'S'
                  AND CAST(mk.Fecha_Registro AS DATE) = ?
                  AND rl.Activo = 1
            ";

            $params = [$fecha];
            if ($id_reactivo > 0) {
                $sql .= " AND mk.Id_Reactivo = ?";
                $params[] = $id_reactivo;
            }

            $sql .= " ORDER BY mk.Fecha_Registro DESC, mk.Id_Movimiento DESC";

            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                throw new Exception('Error en consulta: ' . print_r(sqlsrv_errors(), true));
            }

            $salidas = [];
            $totalCantidad = 0.0;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $cantidad = floatval($row['Cantidad'] ?? 0);
                $totalCantidad += $cantidad;
                $salidas[] = $row;
            }

            echo json_encode([
                'success' => true,
                'fecha' => $fecha,
                'salidas' => $salidas,
                'total' => count($salidas),
                'total_cantidad' => $totalCantidad
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener salidas: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== SERVICIOS POR FECHA ====================
    
    if ($action === 'obtener_servicios_por_fecha') {
        try {
            $fecha = $_GET['fecha'] ?? null;
            
            if (!$fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Fecha requerida']);
                exit;
            }
            
            // Validar formato de fecha
            $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
            if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (use Y-m-d)']);
                exit;
            }
            
            // Query para obtener servicios realizados en esa fecha con sus reactivos y cantidad del producto
            $sql = "
                SELECT DISTINCT
                    st.Nombre AS Servicio_Nombre,
                    pv.Nombre_Comercial AS Producto_Nombre,
                    ISNULL(pda.Cantidad_Planificada, 1) AS Cantidad_Planificada,
                    r.Nombre AS Reactivo_Nombre,
                    rs.Cantidad_Necesaria AS Cantidad_Por_Unidad,
                    (ISNULL(pda.Cantidad_Planificada, 1) * rs.Cantidad_Necesaria) AS Cantidad_Total_Usada
                FROM laboratorio.Solicitud_Analisis sa
                INNER JOIN laboratorio.Muestra_Lab ml ON sa.Id_Muestra = ml.Id_Muestra
                INNER JOIN laboratorio.Muestra_Producto mp ON ml.Id_Muestra = mp.Id_Muestra
                INNER JOIN laboratorio.Producto_Venta pv ON mp.Id_Producto_Venta = pv.Id_Producto
                LEFT JOIN laboratorio.Proyecto_Detalle_Analisis pda ON ml.Id_Proyecto = pda.Id_Proyecto AND pv.Id_Producto = pda.Id_Producto_Venta
                INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
                INNER JOIN laboratorio.Receta_Servicio rs ON st.Id_Servicio = rs.Id_Servicio
                INNER JOIN laboratorio.Reactivo_Lab r ON rs.Id_Reactivo = r.Id_Reactivo
                WHERE CAST(ml.Fecha_Creacion AS DATE) = ?
                AND rs.Activo = 1
                AND st.Activo = 1
                AND r.Activo = 1
                AND pv.Activo = 1
                ORDER BY pv.Nombre_Comercial, st.Nombre, r.Nombre
            ";
            
            $stmt = sqlsrv_query($conn, $sql, [$fecha]);
            if ($stmt === false) {
                throw new Exception('Error en consulta: ' . print_r(sqlsrv_errors(), true));
            }
            
            $servicios = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $servicios[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'fecha' => $fecha,
                'servicios' => $servicios,
                'total' => count($servicios)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener servicios: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== PROVEEDORES ====================

    if ($action === 'listar_proveedores') {
        echo json_encode(['success' => true, 'data' => $proveedor_model->obtenerTodos()]);
        exit;
    }

    // ==================== UNIDADES DE MEDIDA ====================

    if ($action === 'listar_unidades') {
        echo json_encode(['success' => true, 'data' => $unidad_model->obtenerTodos()]);
        exit;
    }

    if ($action === 'guardar_unidad') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id = $unidad_model->guardar($datos);
            $unidad = $unidad_model->obtenerPorId($id);
            echo json_encode(['success' => true, 'id' => $id, 'unidad' => $unidad, 'message' => 'Unidad guardada correctamente']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'eliminar_unidad') {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requerido']); exit; }
            $unidad_model->eliminar($id);
            echo json_encode(['success' => true, 'message' => 'Unidad eliminada correctamente']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== EDITAR KARDEX ====================

    if ($action === 'editar_ingreso') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $idIngreso = intval($datos['Id_Ingreso'] ?? 0);
            $nuevaCantidad = floatval($datos['Cantidad'] ?? 0);
            if (!$idIngreso || $nuevaCantidad <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de ingreso y cantidad válida son requeridos']);
                exit;
            }
            $reactivo_model->editarIngreso($idIngreso, $nuevaCantidad);
            echo json_encode(['success' => true, 'message' => 'Ingreso actualizado correctamente']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'editar_salida') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $idMovimiento = intval($datos['Id_Movimiento'] ?? 0);
            $nuevaCantidad = floatval($datos['Cantidad'] ?? 0);
            $concepto = trim($datos['Concepto'] ?? '');
            if (!$idMovimiento || $nuevaCantidad <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de movimiento y cantidad válida son requeridos']);
                exit;
            }
            $reactivo_model->editarSalida($idMovimiento, $nuevaCantidad, $concepto ?: 'S/N');
            echo json_encode(['success' => true, 'message' => 'Salida actualizada correctamente']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'eliminar_salida') {
        try {
            $idMovimiento = intval($_GET['id'] ?? 0);
            if (!$idMovimiento) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de movimiento requerido']);
                exit;
            }
            $reactivo_model->eliminarSalida($idMovimiento);
            echo json_encode(['success' => true, 'message' => 'Movimiento eliminado y stock restaurado']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Acción no encontrada: {$action}"]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
