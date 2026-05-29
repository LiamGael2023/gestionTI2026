<?php
/**
 * AnalisisAPI.php
 * API Handler - Maneja acciones AJAX para Análisis de Muestras
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

header('Content-Type: application/json; charset=utf-8');

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
    require_once '../models/MuestraModel.php';
    require_once '../models/SolicitudAnalisisModel.php';
    require_once '../models/ResultadoAnalisisModel.php';
    require_once '../../parametro/models/ParametroModel.php';
    
    Auth::check();
    
    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }
    
    $muestra_model = new MuestraModel($conn);
    $solicitud_model = new SolicitudAnalisisModel($conn);
    $resultado_model = new ResultadoAnalisisModel($conn);
    $parametro_model = new ParametroModel($conn);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }
    
    // ==================== OBTENER SOLICITUDES POR PROYECTO ====================
    
    if ($action === 'obtener_solicitudes_proyecto') {
        $id_proyecto = $_GET['id_proyecto'] ?? null;
        
        if (!$id_proyecto) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID proyecto requerido']);
            exit;
        }
        
        $resumen = $solicitud_model->obtenerResumenProyecto($id_proyecto);
        
        echo json_encode([
            'success' => true,
            'servicios' => $resumen
        ]);
        exit;
    }
    
    // ==================== OBTENER PARÁMETROS DE SERVICIO ====================
    
    if ($action === 'obtener_parametros_servicio') {
        $id_servicio = $_GET['id_servicio'] ?? null;
        
        if (!$id_servicio) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID servicio requerido']);
            exit;
        }
        
        // Consultar los parámetros del servicio
        $sql = "
            SELECT 
                pa.Id_Parametro,
                pa.Nombre,
                pa.Unidad_Medida,
                pa.Categoria,
                pa.Metodo_Utilizado
            FROM laboratorio.Parametro_Analisis pa
            WHERE pa.Id_Servicio = ?
            AND pa.Activo = 1
            ORDER BY pa.Categoria, pa.Nombre
        ";
        
        $stmt = sqlsrv_query($conn, $sql, array($id_servicio));
        if ($stmt === false) {
            throw new Exception('Error al obtener parámetros: ' . print_r(sqlsrv_errors(), true));
        }
        
        $parametros = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $parametros[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'parametros' => $parametros
        ]);
        exit;
    }

    // ==================== CONTEXTO CONSUMO EXTRA ====================

    if ($action === 'obtener_contexto_consumo_extra') {
        try {
            $idsRaw = trim((string)($_GET['ids_muestras'] ?? ''));
            if ($idsRaw === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe indicar ids_muestras']);
                exit;
            }

            $ids = [];
            foreach (explode(',', $idsRaw) as $part) {
                $id = intval(trim($part));
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
            $ids = array_values($ids);

            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No hay muestras válidas']);
                exit;
            }

            $ph = implode(',', array_fill(0, count($ids), '?'));

            $sqlMuestras = "SELECT
                                m.Id_Muestra,
                                COALESCE(NULLIF(LTRIM(RTRIM(CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno))), ''), CONCAT('Muestra #', m.Id_Muestra)) AS Agricultor
                            FROM laboratorio.Muestra_Lab m
                            LEFT JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
                            WHERE m.Id_Muestra IN ($ph) AND m.Activo = 1
                            ORDER BY m.Id_Muestra";
            $stmtMuestras = sqlsrv_query($conn, $sqlMuestras, $ids);
            if ($stmtMuestras === false) {
                throw new Exception('Error al obtener muestras: ' . print_r(sqlsrv_errors(), true));
            }

            $muestras = [];
            while ($row = sqlsrv_fetch_array($stmtMuestras, SQLSRV_FETCH_ASSOC)) {
                $idM = intval($row['Id_Muestra'] ?? 0);
                $muestras[] = [
                    'id' => $idM,
                    'label' => 'Muestra #' . $idM . ' - ' . trim((string)($row['Agricultor'] ?? '-'))
                ];
            }

            $sqlServicios = "SELECT
                                sa.Id_Muestra,
                                sa.Id_Servicio,
                                st.Nombre AS Servicio_Nombre,
                                SUM(ISNULL(rs.Cantidad_Necesaria, 0)) AS Consumo_Default
                             FROM laboratorio.Solicitud_Analisis sa
                             INNER JOIN laboratorio.Servicio_Tecnico st ON sa.Id_Servicio = st.Id_Servicio
                             LEFT JOIN laboratorio.Receta_Servicio rs ON rs.Id_Servicio = sa.Id_Servicio AND rs.Activo = 1
                             WHERE sa.Id_Muestra IN ($ph)
                               AND sa.Activo = 1
                               AND st.Activo = 1
                             GROUP BY sa.Id_Muestra, sa.Id_Servicio, st.Nombre
                             ORDER BY sa.Id_Muestra, st.Nombre";
            $stmtServicios = sqlsrv_query($conn, $sqlServicios, $ids);
            if ($stmtServicios === false) {
                throw new Exception('Error al obtener servicios: ' . print_r(sqlsrv_errors(), true));
            }

            $servicios = [];
            while ($row = sqlsrv_fetch_array($stmtServicios, SQLSRV_FETCH_ASSOC)) {
                $idM = intval($row['Id_Muestra'] ?? 0);
                $idS = intval($row['Id_Servicio'] ?? 0);
                $consumoDefault = floatval($row['Consumo_Default'] ?? 0);
                $servicios[] = [
                    'id_muestra' => $idM,
                    'id_servicio' => $idS,
                    'label' => 'Muestra #' . $idM . ' - ' . trim((string)($row['Servicio_Nombre'] ?? ('Servicio #' . $idS))),
                    'consumo_default' => $consumoDefault
                ];
            }

            $sqlReactivos = "SELECT r.Id_Reactivo, r.Nombre, ISNULL(um.Abreviatura, '') AS Unidad_Medida, ISNULL(r.Cantidad_Stock, 0) AS Cantidad_Stock
                             FROM laboratorio.Reactivo_Lab r
                             LEFT JOIN laboratorio.Unidad_Medida um ON r.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                             WHERE r.Activo = 1
                             ORDER BY r.Nombre";
            $stmtReactivos = sqlsrv_query($conn, $sqlReactivos);
            if ($stmtReactivos === false) {
                throw new Exception('Error al obtener reactivos: ' . print_r(sqlsrv_errors(), true));
            }

            $reactivos = [];
            while ($row = sqlsrv_fetch_array($stmtReactivos, SQLSRV_FETCH_ASSOC)) {
                $reactivos[] = [
                    'id' => intval($row['Id_Reactivo'] ?? 0),
                    'nombre' => trim((string)($row['Nombre'] ?? '-')),
                    'unidad' => trim((string)($row['Unidad_Medida'] ?? 'UND')),
                    'stock' => floatval($row['Cantidad_Stock'] ?? 0)
                ];
            }

            $sqlResiduos = "SELECT Id_Residuo_Cat, Codigo_Item, Nombre_Item, Unidad_Referencia
                            FROM laboratorio.Residuo_Catalogo
                            WHERE Activo = 1
                            ORDER BY Nombre_Item";
            $stmtResiduos = sqlsrv_query($conn, $sqlResiduos);
            if ($stmtResiduos === false) {
                throw new Exception('Error al obtener residuos: ' . print_r(sqlsrv_errors(), true));
            }

            $residuos = [];
            while ($row = sqlsrv_fetch_array($stmtResiduos, SQLSRV_FETCH_ASSOC)) {
                $residuos[] = [
                    'id' => intval($row['Id_Residuo_Cat'] ?? 0),
                    'label' => '(' . intval($row['Codigo_Item'] ?? 0) . ') ' . trim((string)($row['Nombre_Item'] ?? '-')),
                    'unidad' => trim((string)($row['Unidad_Referencia'] ?? 'kg'))
                ];
            }

            echo json_encode([
                'success' => true,
                'muestras' => $muestras,
                'servicios' => $servicios,
                'reactivos' => $reactivos,
                'residuos' => $residuos
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener contexto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== REGISTRAR CONSUMO EXTRA + RESIDUOS ====================

    if ($action === 'registrar_consumo_extra') {
        $txStarted = false;
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'JSON inválido']);
                exit;
            }

            $usuario_id = intval($_SESSION['usuario_id'] ?? 1);
            $tipo = trim((string)($datos['tipo'] ?? ''));
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $nota = trim((string)($datos['nota'] ?? ''));
            $manual_items = is_array($datos['manual_items'] ?? null) ? $datos['manual_items'] : [];
            $residuos = is_array($datos['residuos'] ?? null) ? $datos['residuos'] : [];

            if ($id_muestra <= 0) {
                throw new Exception('Debe seleccionar la muestra para registrar el consumo extra');
            }
            if ($tipo !== 'analisis' && $tipo !== 'manual') {
                throw new Exception('Tipo de descuento inválido');
            }

            if (!sqlsrv_begin_transaction($conn)) {
                throw new Exception('No se pudo iniciar transacción');
            }
            $txStarted = true;

            $movimientosCreados = 0;
            $residuosCreados = 0;

            $registrarSalida = function ($idReactivo, $cantidad, $concepto, $idMuestraProducto, $tipoAjuste, $notaAjuste) use ($conn, $usuario_id, &$movimientosCreados) {
                $idReactivo = intval($idReactivo);
                $cantidad = round(floatval($cantidad), 4);
                if ($idReactivo <= 0 || $cantidad <= 0) {
                    throw new Exception('Datos de reactivo inválidos para registrar salida');
                }

                $sqlStock = "SELECT Nombre, ISNULL(Cantidad_Stock, 0) AS Stock
                             FROM laboratorio.Reactivo_Lab
                             WHERE Id_Reactivo = ? AND Activo = 1";
                $stmtStock = sqlsrv_query($conn, $sqlStock, [$idReactivo]);
                if ($stmtStock === false) {
                    throw new Exception('Error al consultar stock: ' . print_r(sqlsrv_errors(), true));
                }
                $rowStock = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
                if (!$rowStock) {
                    throw new Exception('Reactivo no encontrado: ' . $idReactivo);
                }

                $stockActual = floatval($rowStock['Stock'] ?? 0);
                if ($stockActual < $cantidad) {
                    throw new Exception('Stock insuficiente en reactivo ' . trim((string)$rowStock['Nombre']) . '. Disponible: ' . $stockActual . ', requerido: ' . $cantidad);
                }

                $saldoNuevo = round($stockActual - $cantidad, 4);

                $sqlMov = "SET NOCOUNT ON;
                           INSERT INTO laboratorio.Movimiento_Kardex
                           (Id_Reactivo, Fecha_Registro, Tipo_Movimiento, Cantidad, Concepto, Saldo_Resultante, Activo, Fecha_Creacion, Usuario_Creacion)
                           VALUES (?, GETDATE(), 'S', ?, ?, ?, 1, GETDATE(), ?);
                           SELECT CAST(SCOPE_IDENTITY() AS INT) AS id;";
                $stmtMov = sqlsrv_query($conn, $sqlMov, [$idReactivo, $cantidad, $concepto, $saldoNuevo, $usuario_id]);
                if ($stmtMov === false) {
                    throw new Exception('Error al registrar movimiento kardex: ' . print_r(sqlsrv_errors(), true));
                }
                $rowMov = sqlsrv_fetch_array($stmtMov, SQLSRV_FETCH_ASSOC);
                if (!$rowMov || empty($rowMov['id'])) {
                    throw new Exception('No se pudo obtener Id_Movimiento generado');
                }
                $idMovimiento = intval($rowMov['id']);

                $sqlConsumo = "INSERT INTO laboratorio.Consumo_Reaccion
                               (Id_Movimiento, Id_Muestra_Producto, Activo, Fecha_Creacion, Usuario_Creacion)
                               VALUES (?, ?, 1, GETDATE(), ?)";
                $stmtConsumo = sqlsrv_query($conn, $sqlConsumo, [$idMovimiento, $idMuestraProducto, $usuario_id]);
                if ($stmtConsumo === false) {
                    throw new Exception('Error al registrar consumo_reaccion: ' . print_r(sqlsrv_errors(), true));
                }

                $sqlAjuste = "INSERT INTO laboratorio.Ajuste_Inventario
                              (Id_Reactivo, Id_Usuario, Tipo_Ajuste, Cantidad, Fecha_Ajuste, Notas, Activo, Fecha_Creacion, Usuario_Creacion)
                              VALUES (?, ?, ?, ?, GETDATE(), ?, 1, GETDATE(), ?)";
                $stmtAjuste = sqlsrv_query($conn, $sqlAjuste, [$idReactivo, $usuario_id, $tipoAjuste, $cantidad, $notaAjuste, $usuario_id]);
                if ($stmtAjuste === false) {
                    throw new Exception('Error al registrar ajuste inventario: ' . print_r(sqlsrv_errors(), true));
                }

                $sqlUpdate = "UPDATE laboratorio.Reactivo_Lab
                              SET Cantidad_Stock = Cantidad_Stock - ?,
                                  Fecha_Modificacion = GETDATE()
                              WHERE Id_Reactivo = ?";
                $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, [$cantidad, $idReactivo]);
                if ($stmtUpdate === false) {
                    throw new Exception('Error al actualizar stock de reactivo: ' . print_r(sqlsrv_errors(), true));
                }

                $movimientosCreados++;
            };

            $residuosAcumulados = [];
            $agregarResiduoAcumulado = function ($idResiduo, $cantidad) use (&$residuosAcumulados) {
                $idResiduo = intval($idResiduo);
                $cantidad = round(floatval($cantidad), 4);
                if ($idResiduo <= 0 || $cantidad <= 0) {
                    return;
                }
                if (!isset($residuosAcumulados[$idResiduo])) {
                    $residuosAcumulados[$idResiduo] = 0;
                }
                $residuosAcumulados[$idResiduo] += $cantidad;
            };

            if ($tipo === 'analisis') {
                $id_servicio = intval($datos['id_servicio'] ?? 0);
                $factor = floatval($datos['factor'] ?? 0);
                if ($id_servicio <= 0) {
                    throw new Exception('Debe seleccionar el análisis/servicio a repetir');
                }
                if ($factor <= 0) {
                    throw new Exception('La cantidad equivalente de muestras debe ser mayor a 0');
                }

                $sqlMP = "SELECT TOP 1 mp.Id_Muestra_Producto
                          FROM laboratorio.Muestra_Producto mp
                          INNER JOIN laboratorio.Producto_Servicio ps ON ps.Id_Producto = mp.Id_Producto_Venta AND ps.Activo = 1
                          WHERE mp.Id_Muestra = ?
                            AND mp.Activo = 1
                            AND ps.Id_Servicio = ?
                          ORDER BY mp.Id_Muestra_Producto DESC";
                $stmtMP = sqlsrv_query($conn, $sqlMP, [$id_muestra, $id_servicio]);
                if ($stmtMP === false) {
                    throw new Exception('Error al obtener relación muestra-producto: ' . print_r(sqlsrv_errors(), true));
                }
                $rowMP = sqlsrv_fetch_array($stmtMP, SQLSRV_FETCH_ASSOC);
                if (!$rowMP) {
                    throw new Exception('No se encontró el servicio seleccionado dentro de la muestra');
                }
                $id_muestra_producto = intval($rowMP['Id_Muestra_Producto']);

                $sqlReceta = "SELECT rs.Id_Reactivo, rs.Cantidad_Necesaria
                              FROM laboratorio.Receta_Servicio rs
                              INNER JOIN laboratorio.Reactivo_Lab r ON r.Id_Reactivo = rs.Id_Reactivo
                              WHERE rs.Id_Servicio = ?
                                AND rs.Activo = 1
                                AND r.Activo = 1";
                $stmtReceta = sqlsrv_query($conn, $sqlReceta, [$id_servicio]);
                if ($stmtReceta === false) {
                    throw new Exception('Error al consultar receta de servicio: ' . print_r(sqlsrv_errors(), true));
                }

                $hayReceta = false;
                while ($rowR = sqlsrv_fetch_array($stmtReceta, SQLSRV_FETCH_ASSOC)) {
                    $hayReceta = true;
                    $idReactivo = intval($rowR['Id_Reactivo'] ?? 0);
                    $cantidadBase = floatval($rowR['Cantidad_Necesaria'] ?? 0);
                    $cantidadFinal = round($cantidadBase * $factor, 4);
                    if ($idReactivo <= 0 || $cantidadFinal <= 0) {
                        continue;
                    }

                    $concepto = 'Consumo extra por repeticion de analisis (Muestra #' . $id_muestra . ', Servicio #' . $id_servicio . ', x' . $factor . ')';
                    $notaAjuste = ($nota !== '' ? $nota . ' | ' : '') . $concepto;
                    $registrarSalida($idReactivo, $cantidadFinal, $concepto, $id_muestra_producto, 'Consumo Extra Analisis', $notaAjuste);
                }

                if (!$hayReceta) {
                    throw new Exception('El servicio seleccionado no tiene receta de reactivos activa');
                }

                // Residuos automáticos por servicio repetido
                $sqlResAuto = "SELECT Id_Residuo_Cat, ISNULL(Cantidad_Estimada_Por_Muestra, 0) AS Cantidad_Estimada_Por_Muestra
                               FROM laboratorio.Servicio_Residuo_Def
                               WHERE Id_Servicio = ?
                                 AND Activo = 1";
                $stmtResAuto = sqlsrv_query($conn, $sqlResAuto, [$id_servicio]);
                if ($stmtResAuto === false) {
                    throw new Exception('Error al consultar residuos automáticos del servicio: ' . print_r(sqlsrv_errors(), true));
                }

                while ($rowResAuto = sqlsrv_fetch_array($stmtResAuto, SQLSRV_FETCH_ASSOC)) {
                    $idResiduoAuto = intval($rowResAuto['Id_Residuo_Cat'] ?? 0);
                    $baseResiduo = floatval($rowResAuto['Cantidad_Estimada_Por_Muestra'] ?? 0);
                    $cantidadResiduoAuto = round($baseResiduo * $factor, 4);
                    $agregarResiduoAcumulado($idResiduoAuto, $cantidadResiduoAuto);
                }
            } else {
                $sqlMPManual = "SELECT TOP 1 Id_Muestra_Producto
                                FROM laboratorio.Muestra_Producto
                                WHERE Id_Muestra = ? AND Activo = 1
                                ORDER BY Id_Muestra_Producto DESC";
                $stmtMPManual = sqlsrv_query($conn, $sqlMPManual, [$id_muestra]);
                if ($stmtMPManual === false) {
                    throw new Exception('Error al obtener muestra_producto: ' . print_r(sqlsrv_errors(), true));
                }
                $rowMPManual = sqlsrv_fetch_array($stmtMPManual, SQLSRV_FETCH_ASSOC);
                if (!$rowMPManual) {
                    throw new Exception('No se encontró relación muestra-producto para consumo manual');
                }
                $id_muestra_producto = intval($rowMPManual['Id_Muestra_Producto']);

                if (empty($manual_items)) {
                    throw new Exception('Debe registrar al menos un reactivo manual');
                }

                foreach ($manual_items as $item) {
                    $idReactivo = intval($item['id_reactivo'] ?? 0);
                    $cantidad = round(floatval($item['cantidad'] ?? 0), 4);
                    if ($idReactivo <= 0 || $cantidad <= 0) {
                        continue;
                    }
                    $concepto = 'Consumo extra manual (Muestra #' . $id_muestra . ')';
                    $notaAjuste = ($nota !== '' ? $nota . ' | ' : '') . $concepto;
                    $registrarSalida($idReactivo, $cantidad, $concepto, $id_muestra_producto, 'Consumo Extra Manual', $notaAjuste);
                }
            }

            // Residuos manuales adicionales ingresados desde modal
            foreach ($residuos as $r) {
                $idResiduo = intval($r['id_residuo_cat'] ?? 0);
                $cantidadResiduo = round(floatval($r['cantidad'] ?? 0), 4);
                $agregarResiduoAcumulado($idResiduo, $cantidadResiduo);
            }

            $residuosValidos = [];
            foreach ($residuosAcumulados as $idResiduo => $cantidadAcum) {
                if ($cantidadAcum > 0) {
                    $residuosValidos[] = [
                        'id' => intval($idResiduo),
                        'cantidad' => round(floatval($cantidadAcum), 4)
                    ];
                }
            }

            if (!empty($residuosValidos)) {
                $mes = intval(date('n'));
                $anio = intval(date('Y'));

                $sqlCab = "SELECT TOP 1 Id_Registro_Res
                           FROM laboratorio.Registro_Residuos_Log
                           WHERE Mes = ? AND Anio = ? AND Activo = 1
                           ORDER BY Id_Registro_Res DESC";
                $stmtCab = sqlsrv_query($conn, $sqlCab, [$mes, $anio]);
                if ($stmtCab === false) {
                    throw new Exception('Error al buscar cabecera de residuos: ' . print_r(sqlsrv_errors(), true));
                }
                $rowCab = sqlsrv_fetch_array($stmtCab, SQLSRV_FETCH_ASSOC);
                $idRegistroRes = intval($rowCab['Id_Registro_Res'] ?? 0);

                if ($idRegistroRes <= 0) {
                    $sqlNewCab = "SET NOCOUNT ON;
                                  INSERT INTO laboratorio.Registro_Residuos_Log
                                  (Mes, Anio, Ubicacion, Id_Responsable, Codigo_SST, Activo, Fecha_Creacion, Usuario_Creacion)
                                  VALUES (?, ?, 'LAB-GENERAL', ?, 'SST-16', 1, GETDATE(), ?);
                                  SELECT CAST(SCOPE_IDENTITY() AS INT) AS id;";
                    $stmtNewCab = sqlsrv_query($conn, $sqlNewCab, [$mes, $anio, $usuario_id, $usuario_id]);
                    if ($stmtNewCab === false) {
                        throw new Exception('Error al crear cabecera de residuos: ' . print_r(sqlsrv_errors(), true));
                    }
                    $rowNewCab = sqlsrv_fetch_array($stmtNewCab, SQLSRV_FETCH_ASSOC);
                    $idRegistroRes = intval($rowNewCab['id'] ?? 0);
                    if ($idRegistroRes <= 0) {
                        throw new Exception('No se pudo obtener Id_Registro_Res generado');
                    }
                }

                foreach ($residuosValidos as $r) {
                    $sqlInsRes = "INSERT INTO laboratorio.Detalle_Residuos_Log
                                  (Id_Registro_Res, Id_Residuo_Cat, Fecha_Dia, Peso_Valor, Activo, Fecha_Creacion, Usuario_Creacion)
                                  VALUES (?, ?, CAST(GETDATE() AS DATE), ?, 1, GETDATE(), ?)";
                    $stmtInsRes = sqlsrv_query($conn, $sqlInsRes, [$idRegistroRes, $r['id'], $r['cantidad'], $usuario_id]);
                    if ($stmtInsRes === false) {
                        throw new Exception('Error al registrar residuo: ' . print_r(sqlsrv_errors(), true));
                    }
                    $residuosCreados++;
                }
            }

            if ($movimientosCreados <= 0 && $residuosCreados <= 0) {
                throw new Exception('No se registró ningún descuento extra ni residuos');
            }

            if (!sqlsrv_commit($conn)) {
                throw new Exception('No se pudo confirmar la transacción');
            }
            $txStarted = false;

            echo json_encode([
                'success' => true,
                'message' => 'Consumo extra y residuos registrados correctamente',
                'movimientos' => $movimientosCreados,
                'residuos' => $residuosCreados
            ]);
        } catch (Exception $e) {
            if ($txStarted) {
                sqlsrv_rollback($conn);
            }
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al registrar consumo extra: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== CREAR REGISTROS EN BLANCO ====================
    
    if ($action === 'crear_registros_vacios') {
        try {
            $id_proyecto = $_GET['id_proyecto'] ?? $_POST['id_proyecto'] ?? null;
            
            if (!$id_proyecto) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID proyecto requerido']);
                exit;
            }
            
            // Obtener todas las muestras del proyecto
            $sql_muestras = "
                SELECT Id_Muestra 
                FROM laboratorio.Muestra_Lab 
                WHERE Id_Proyecto = ? AND Activo = 1
            ";
            
            $stmt_muestras = sqlsrv_query($conn, $sql_muestras, array($id_proyecto));
            if ($stmt_muestras === false) {
                throw new Exception('Error al obtener muestras: ' . print_r(sqlsrv_errors(), true));
            }
            
            $total_creados = 0;
            while ($muestra = sqlsrv_fetch_array($stmt_muestras, SQLSRV_FETCH_ASSOC)) {
                $ids = $resultado_model->crearBlancosPorMuestra($muestra['Id_Muestra']);
                $total_creados += count($ids);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Registros en blanco creados',
                'total_creados' => $total_creados
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear registros: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== OBTENER RESULTADOS PARA EDITAR ====================
    
    if ($action === 'obtener_resultados') {
        try {
            $id_muestra = $_GET['id_muestra'] ?? null;
            
            if (!$id_muestra) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID muestra requerido']);
                exit;
            }
            
            $resultados = $resultado_model->obtenerResultadosEditables($id_muestra);
            
            // Obtener todos los parámetros del sistema
            $sql_todos_params = "
                SELECT Id_Parametro, Nombre, Unidad_Medida
                FROM laboratorio.Parametro_Analisis
                WHERE Activo = 1
                ORDER BY Nombre
            ";
            
            $stmt_todos = sqlsrv_query($conn, $sql_todos_params);
            if ($stmt_todos === false) {
                throw new Exception('Error ao obtener parámetros: ' . print_r(sqlsrv_errors(), true));
            }
            
            $todos_parametros = [];
            while ($row = sqlsrv_fetch_array($stmt_todos, SQLSRV_FETCH_ASSOC)) {
                $todos_parametros[] = $row;
            }
            
            // Obtener parámetros habilitados (que tienen Resultado_Analisis)
            $parametros_habilitados = [];
            foreach ($resultados as $resultado) {
                $parametros_habilitados[$resultado['Id_Parametro']] = true;
            }
            
            echo json_encode([
                'success' => true,
                'resultados' => $resultados,
                'todos_parametros' => $todos_parametros,
                'parametros_habilitados' => $parametros_habilitados
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener resultados: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== GUARDAR RESULTADO (UPDATE) ====================
    
    if ($action === 'guardar_resultado') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;

            // Solo el Analista Jefe (Id_Rol=2) o administrador puede finalizar resultados
            $es_admin_gr = false;
            $stmtAdminGR = sqlsrv_query($conn, "SELECT TOP 1 rol FROM comun.Usuarios WHERE id_usuario = ? AND activo = 1", array($usuario_id));
            if ($stmtAdminGR) {
                $rowAdminGR = sqlsrv_fetch_array($stmtAdminGR, SQLSRV_FETCH_ASSOC);
                if ($rowAdminGR && in_array(strtolower(trim((string)$rowAdminGR['rol'])), ['administrador','admin','superadmin','super admin'], true)) {
                    $es_admin_gr = true;
                }
            }
            $es_analista_jefe_gr = false;
            $stmtAJGR = sqlsrv_query($conn, "SELECT TOP 1 1 AS existe FROM laboratorio.Usuario_Rol WHERE Id_Usuario = ? AND Id_Rol = 2", array($usuario_id));
            if ($stmtAJGR && sqlsrv_fetch_array($stmtAJGR, SQLSRV_FETCH_ASSOC)) {
                $es_analista_jefe_gr = true;
            }
            if (!$es_admin_gr && !$es_analista_jefe_gr) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Solo el Analista Jefe puede finalizar resultados. Use "Guardar Avance" para guardar un borrador.']);
                exit;
            }

            // Verificar que Id_Resultado existe (Valor_Hallado puede ser null)
            if (!isset($datos['Id_Resultado']) || !array_key_exists('Valor_Hallado', $datos)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos incompletos (requiere Id_Resultado y Valor_Hallado)'
                ]);
                exit;
            }
            
            $id_resultado = intval($datos['Id_Resultado']);
            $valor_hallado = !empty($datos['Valor_Hallado']) ? floatval($datos['Valor_Hallado']) : null;
            $observacion = $datos['Observacion'] ?? null;
            
            // Obtener el Id_Solicitud_Analisis del resultado
            $sql_get = "
                SELECT Id_Solicitud_Analisis 
                FROM laboratorio.Resultado_Analisis 
                WHERE Id_Resultado = ? AND Activo = 1
            ";
            
            $stmt_get = sqlsrv_query($conn, $sql_get, array($id_resultado));
            if ($stmt_get === false) {
                throw new Exception('Error al obtener resultado: ' . print_r(sqlsrv_errors(), true));
            }
            
            $row_get = sqlsrv_fetch_array($stmt_get, SQLSRV_FETCH_ASSOC);
            if (!$row_get) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Resultado no encontrado']);
                exit;
            }
            
            $id_solicitud = $row_get['Id_Solicitud_Analisis'];

            // UPDATE del Resultado_Analisis
            $sql_update = "
                UPDATE laboratorio.Resultado_Analisis 
                SET Valor_Hallado = ?, 
                    Observacion = ?,
                    Fecha_Modificacion = GETDATE()
                WHERE Id_Resultado = ?
            ";
            
            $stmt_update = sqlsrv_query($conn, $sql_update, array($valor_hallado, $observacion, $id_resultado));
            if ($stmt_update === false) {
                throw new Exception('Error al actualizar resultado: ' . print_r(sqlsrv_errors(), true));
            }

            $sql_estado_solicitud = "SELECT Estado
                                     FROM laboratorio.Solicitud_Analisis
                                     WHERE Id_Solicitud_Analisis = ? AND Activo = 1";
            $stmt_estado_solicitud = sqlsrv_query($conn, $sql_estado_solicitud, array($id_solicitud));
            if ($stmt_estado_solicitud === false) {
                throw new Exception('Error al obtener estado actual de solicitud: ' . print_r(sqlsrv_errors(), true));
            }
            $row_estado_solicitud = sqlsrv_fetch_array($stmt_estado_solicitud, SQLSRV_FETCH_ASSOC);
            $estado_solicitud_anterior = strtolower(trim((string)($row_estado_solicitud['Estado'] ?? '')));

            // Estado de solicitud: Finalizado solo si tiene al menos un valor ingresado.
            $sql_check_solicitud = "
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN Valor_Hallado IS NOT NULL THEN 1 ELSE 0 END) AS con_valor
                FROM laboratorio.Resultado_Analisis
                WHERE Id_Solicitud_Analisis = ? AND Activo = 1
            ";
            $stmt_check_solicitud = sqlsrv_query($conn, $sql_check_solicitud, array($id_solicitud));
            if ($stmt_check_solicitud === false) {
                throw new Exception('Error al validar estado de solicitud: ' . print_r(sqlsrv_errors(), true));
            }
            $row_solicitud = sqlsrv_fetch_array($stmt_check_solicitud, SQLSRV_FETCH_ASSOC);
            $con_valor = intval($row_solicitud['con_valor'] ?? 0);
            $nuevo_estado_solicitud = $con_valor > 0 ? 'Finalizado' : 'En Análisis';
            $marca_inicio_residuo = date('Y-m-d H:i:s');
            $solicitud_model->actualizarEstado($id_solicitud, $nuevo_estado_solicitud);

            if ($estado_solicitud_anterior !== 'finalizado' && strtolower($nuevo_estado_solicitud) === 'finalizado') {
                $marca_fin_residuo = date('Y-m-d H:i:s');
                $muestra_model->asignarUsuarioCreacionResiduosPorSolicitud(
                    $id_solicitud,
                    $usuario_id,
                    $marca_inicio_residuo,
                    $marca_fin_residuo
                );
            }
            
            // Verificar si TODAS las solicitudes de la muestra están finalizadas
            $sql_check_muestra = "
                SELECT sa.Id_Muestra
                FROM laboratorio.Solicitud_Analisis sa
                WHERE sa.Id_Solicitud_Analisis = ?
                AND sa.Activo = 1
            ";
            
            $stmt_check_muestra = sqlsrv_query($conn, $sql_check_muestra, array($id_solicitud));
            $row_muestra = sqlsrv_fetch_array($stmt_check_muestra, SQLSRV_FETCH_ASSOC);
            
            if ($row_muestra) {
                $id_muestra = $row_muestra['Id_Muestra'];
                
                // Verificar si todas las Solicitud_Analisis de la muestra están finalizadas
                $sql_all_finished = "
                    SELECT COUNT(*) as total_solicitudes,
                           SUM(CASE WHEN Estado = 'Finalizado' THEN 1 ELSE 0 END) AS finalizadas
                    FROM laboratorio.Solicitud_Analisis
                    WHERE Id_Muestra = ? AND Activo = 1
                ";
                
                $stmt_all_finished = sqlsrv_query($conn, $sql_all_finished, array($id_muestra));
                $row_all = sqlsrv_fetch_array($stmt_all_finished, SQLSRV_FETCH_ASSOC);
                
                if ($row_all && intval($row_all['total_solicitudes']) > 0 && 
                    intval($row_all['total_solicitudes']) === intval($row_all['finalizadas'])) {

                    // Para muestras duplicadas por defecto o de proyecto, finaliza directo.
                    // Solo las muestras normales sin proyecto pasan a "Por Firmar".
                    $sql_get_proyecto_estado = "
                        SELECT Id_Proyecto
                        FROM laboratorio.Muestra_Lab
                        WHERE Id_Muestra = ? AND Activo = 1
                    ";
                    $stmt_get_proyecto_estado = sqlsrv_query($conn, $sql_get_proyecto_estado, array($id_muestra));
                    $row_proyecto_estado = sqlsrv_fetch_array($stmt_get_proyecto_estado, SQLSRV_FETCH_ASSOC);
                    $id_proyecto_muestra = intval($row_proyecto_estado['Id_Proyecto'] ?? 0);

                                        $sql_es_duplicada = "
                                                SELECT TOP 1 1 AS EsDuplicada
                                                FROM laboratorio.Muestra_Bitacora
                                                WHERE Id_Muestra = ?
                                                    AND Muestra_Original IS NOT NULL
                                        ";
                                        $stmt_es_duplicada = sqlsrv_query($conn, $sql_es_duplicada, array($id_muestra));
                                        $row_es_duplicada = $stmt_es_duplicada ? sqlsrv_fetch_array($stmt_es_duplicada, SQLSRV_FETCH_ASSOC) : null;
                                        $es_muestra_duplicada = $row_es_duplicada ? true : false;

                                        $estado_muestra_final = ($id_proyecto_muestra > 0 || $es_muestra_duplicada) ? 'Finalizado' : 'Por Firmar';

                    $sql_estado_actual_muestra = "
                        SELECT Estado
                        FROM laboratorio.Muestra_Lab
                        WHERE Id_Muestra = ? AND Activo = 1
                    ";
                    $stmt_estado_actual_muestra = sqlsrv_query($conn, $sql_estado_actual_muestra, array($id_muestra));
                    if ($stmt_estado_actual_muestra === false) {
                        throw new Exception('Error al consultar estado actual de muestra: ' . print_r(sqlsrv_errors(), true));
                    }
                    $row_estado_actual_muestra = sqlsrv_fetch_array($stmt_estado_actual_muestra, SQLSRV_FETCH_ASSOC);
                    $estado_muestra_actual = strtolower(trim((string)($row_estado_actual_muestra['Estado'] ?? '')));

                    $es_cierre_final_muestra = (strtolower($estado_muestra_final) === 'finalizado' && $estado_muestra_actual !== 'finalizado');
                    $es_por_firmar = (strtolower($estado_muestra_final) === 'por firmar' && $estado_muestra_actual !== 'por firmar');

                    // El usuario que finaliza (Analista Jefe) es el especialista responsable
                    $id_analista_jefe_firma = $usuario_id;

                    if ($es_cierre_final_muestra) {
                        $sql_update_muestra = "
                            UPDATE laboratorio.Muestra_Lab 
                            SET Estado = ?,
                                Id_Jefe_Lab = ?,
                                Id_Especialista = ?,
                                Fecha_Validacion = GETDATE(),
                                Fecha_Modificacion = GETDATE()
                            WHERE Id_Muestra = ? AND Activo = 1
                        ";
                        $params_update_muestra = array($estado_muestra_final, $usuario_id, $id_analista_jefe_firma, $id_muestra);
                    } elseif ($es_por_firmar) {
                        $sql_update_muestra = "
                            UPDATE laboratorio.Muestra_Lab 
                            SET Estado = ?,
                                Id_Especialista = ?,
                                Fecha_Modificacion = GETDATE()
                            WHERE Id_Muestra = ? AND Activo = 1
                        ";
                        $params_update_muestra = array($estado_muestra_final, $id_analista_jefe_firma, $id_muestra);
                    } else {
                        $sql_update_muestra = "
                            UPDATE laboratorio.Muestra_Lab 
                            SET Estado = ?, 
                                Fecha_Modificacion = GETDATE()
                            WHERE Id_Muestra = ? AND Activo = 1
                        ";
                        $params_update_muestra = array($estado_muestra_final, $id_muestra);
                    }

                    $stmt_update_muestra = sqlsrv_query($conn, $sql_update_muestra, $params_update_muestra);
                    if ($stmt_update_muestra === false) {
                        throw new Exception('Error al actualizar muestra: ' . print_r(sqlsrv_errors(), true));
                    }

                    // Verificar si TODAS las muestras del proyecto están finalizadas
                    $sql_get_proyecto = "
                        SELECT Id_Proyecto FROM laboratorio.Muestra_Lab 
                        WHERE Id_Muestra = ? AND Activo = 1
                    ";
                    
                    $stmt_get_proyecto = sqlsrv_query($conn, $sql_get_proyecto, array($id_muestra));
                    $row_proyecto = sqlsrv_fetch_array($stmt_get_proyecto, SQLSRV_FETCH_ASSOC);
                    
                    if ($row_proyecto) {
                        $id_proyecto = intval($row_proyecto['Id_Proyecto'] ?? 0);
                        if ($id_proyecto <= 0) {
                            $id_proyecto = null;
                        }

                        if ($id_proyecto !== null) {
                        
                        // Contar muestras finalizadas vs total de muestras del proyecto
                        $sql_check_proyecto = "
                            SELECT COUNT(*) as total_muestras,
                                   SUM(CASE WHEN Estado = 'Finalizado' THEN 1 ELSE 0 END) AS finalizadas
                            FROM laboratorio.Muestra_Lab
                            WHERE Id_Proyecto = ? AND Activo = 1
                        ";
                        
                            $stmt_check_proyecto = sqlsrv_query($conn, $sql_check_proyecto, array($id_proyecto));
                            $row_proyecto_check = sqlsrv_fetch_array($stmt_check_proyecto, SQLSRV_FETCH_ASSOC);
                        
                        // Si TODAS las muestras están finalizadas, cambiar el proyecto a Finalizado
                            if ($row_proyecto_check && intval($row_proyecto_check['total_muestras']) > 0 && 
                                intval($row_proyecto_check['total_muestras']) === intval($row_proyecto_check['finalizadas'])) {
                            
                            $sql_update_proyecto = "
                                UPDATE laboratorio.Proyecto_Monitoreo 
                                SET Estado = 'Finalizado', 
                                    Fecha_Modificacion = GETDATE()
                                WHERE Id_Proyecto = ? AND Activo = 1
                            ";
                            
                                sqlsrv_query($conn, $sql_update_proyecto, array($id_proyecto));
                            }
                        }
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Resultado actualizado correctamente',
                'id_resultado' => $id_resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar resultado: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== GUARDAR AVANCE (SIN FINALIZAR) ====================
    
    if ($action === 'guardar_avance') {
        try {
            $log_file = '../../debug_analisis_proyecto.log';
            
            $datos = json_decode(file_get_contents('php://input'), true);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;
            
            file_put_contents($log_file, "\n[" . date('Y-m-d H:i:s') . "] === GUARDAR AVANCE ===\n", FILE_APPEND);
            file_put_contents($log_file, "Datos recibidos: " . json_encode($datos) . "\n", FILE_APPEND);
            
            // Verificar que Id_Resultado existe (Valor_Hallado puede ser null)
            if (!isset($datos['Id_Resultado']) || !array_key_exists('Valor_Hallado', $datos)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos incompletos (requiere Id_Resultado y Valor_Hallado)'
                ]);
                exit;
            }
            
            $id_resultado = intval($datos['Id_Resultado']);
            $valor_hallado = !empty($datos['Valor_Hallado']) ? floatval($datos['Valor_Hallado']) : null;
            $observacion = $datos['Observacion'] ?? null;
            
            file_put_contents($log_file, "Id_Resultado: $id_resultado, Valor: $valor_hallado\n", FILE_APPEND);
            
            // Obtener el Id_Solicitud_Analisis del resultado
            $sql_get = "
                SELECT Id_Solicitud_Analisis 
                FROM laboratorio.Resultado_Analisis 
                WHERE Id_Resultado = ? AND Activo = 1
            ";
            
            $stmt_get = sqlsrv_query($conn, $sql_get, array($id_resultado));
            if ($stmt_get === false) {
                throw new Exception('Error al obtener resultado: ' . print_r(sqlsrv_errors(), true));
            }
            
            $row_get = sqlsrv_fetch_array($stmt_get, SQLSRV_FETCH_ASSOC);
            if (!$row_get) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Resultado no encontrado']);
                exit;
            }
            
            $id_solicitud = $row_get['Id_Solicitud_Analisis'];
            file_put_contents($log_file, "Id_Solicitud: $id_solicitud\n", FILE_APPEND);

            $sql_analista = "
                UPDATE laboratorio.Solicitud_Analisis
                SET Id_Analista = ?,
                    Fecha_Modificacion = GETDATE()
                WHERE Id_Solicitud_Analisis = ? AND Activo = 1
            ";
            $stmt_analista = sqlsrv_query($conn, $sql_analista, array($usuario_id, $id_solicitud));
            if ($stmt_analista === false) {
                throw new Exception('Error al actualizar Id_Analista en solicitud: ' . print_r(sqlsrv_errors(), true));
            }
            
            // UPDATE del Resultado_Analisis
            $sql_update = "
                UPDATE laboratorio.Resultado_Analisis 
                SET Valor_Hallado = ?, 
                    Observacion = ?,
                    Fecha_Modificacion = GETDATE()
                WHERE Id_Resultado = ?
            ";
            
            $stmt_update = sqlsrv_query($conn, $sql_update, array($valor_hallado, $observacion, $id_resultado));
            if ($stmt_update === false) {
                throw new Exception('Error al actualizar resultado: ' . print_r(sqlsrv_errors(), true));
            }

            $sql_estado_solicitud = "SELECT Estado
                                     FROM laboratorio.Solicitud_Analisis
                                     WHERE Id_Solicitud_Analisis = ? AND Activo = 1";
            $stmt_estado_solicitud = sqlsrv_query($conn, $sql_estado_solicitud, array($id_solicitud));
            if ($stmt_estado_solicitud === false) {
                throw new Exception('Error al obtener estado actual de solicitud: ' . print_r(sqlsrv_errors(), true));
            }
            $row_estado_solicitud = sqlsrv_fetch_array($stmt_estado_solicitud, SQLSRV_FETCH_ASSOC);
            $estado_solicitud_anterior = strtolower(trim((string)($row_estado_solicitud['Estado'] ?? '')));

            file_put_contents($log_file, "✓ Resultado actualizado\n", FILE_APPEND);
            
            // Estado de solicitud: Finalizado solo si tiene al menos un valor ingresado.
            $sql_check_solicitud = "
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN Valor_Hallado IS NOT NULL THEN 1 ELSE 0 END) AS con_valor
                FROM laboratorio.Resultado_Analisis
                WHERE Id_Solicitud_Analisis = ? AND Activo = 1
            ";
            $stmt_check_solicitud = sqlsrv_query($conn, $sql_check_solicitud, array($id_solicitud));
            if ($stmt_check_solicitud === false) {
                throw new Exception('Error al validar estado de solicitud: ' . print_r(sqlsrv_errors(), true));
            }
            $row_solicitud = sqlsrv_fetch_array($stmt_check_solicitud, SQLSRV_FETCH_ASSOC);
            $con_valor = intval($row_solicitud['con_valor'] ?? 0);
            $nuevo_estado_solicitud = $con_valor > 0 ? 'Finalizado' : 'En Análisis';
            file_put_contents($log_file, "Actualizando estado de solicitud a {$nuevo_estado_solicitud}...\n", FILE_APPEND);
            $marca_inicio_residuo = date('Y-m-d H:i:s');
            $solicitud_model->actualizarEstado($id_solicitud, $nuevo_estado_solicitud);
            file_put_contents($log_file, "✓ Estado de solicitud actualizado\n", FILE_APPEND);

            if ($estado_solicitud_anterior !== 'finalizado' && strtolower($nuevo_estado_solicitud) === 'finalizado') {
                $marca_fin_residuo = date('Y-m-d H:i:s');
                $afectadosUsuarioRes = $muestra_model->asignarUsuarioCreacionResiduosPorSolicitud(
                    $id_solicitud,
                    $usuario_id,
                    $marca_inicio_residuo,
                    $marca_fin_residuo
                );
                file_put_contents($log_file, "✓ Usuario asignado en residuos automáticos del trigger: " . intval($afectadosUsuarioRes) . "\n", FILE_APPEND);
            }
            
            file_put_contents($log_file, "✓ AVANCE GUARDADO EXITOSAMENTE\n", FILE_APPEND);
            
            echo json_encode([
                'success' => true,
                'message' => 'Avance guardado correctamente',
                'id_resultado' => $id_resultado
            ]);
        } catch (Exception $e) {
            file_put_contents($log_file, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar avance: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== GUARDAR RESULTADOS (BATCH - DEPRECATED) ====================
    
    if ($action === 'guardar_resultados') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;
            
            if (!isset($datos['Id_Solicitud_Analisis']) || !isset($datos['resultados'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos incompletos'
                ]);
                exit;
            }
            
            $id_solicitud = intval($datos['Id_Solicitud_Analisis']);
            $resultados = $datos['resultados'];
            
            // Guardar cada resultado
            $ids_guardados = [];
            foreach ($resultados as $resultado) {
                if (!empty($resultado['Valor_Hallado']) || !empty($resultado['Observacion'])) {
                    $id = $resultado_model->guardar([
                        'Id_Solicitud_Analisis' => $id_solicitud,
                        'Id_Parametro' => $resultado['Id_Parametro'],
                        'Id_Normativa' => $resultado['Id_Normativa'] ?? null,
                        'Valor_Hallado' => $resultado['Valor_Hallado'] ?? null,
                        'Observacion' => $resultado['Observacion'] ?? null,
                        'Interpretacion' => $resultado['Interpretacion'] ?? null
                    ]);
                    $ids_guardados[] = $id;
                }
            }
            
            // Cambiar estado de solicitud a "Terminado"
            $solicitud_model->actualizarEstado($id_solicitud, 'Terminado');
            
            echo json_encode([
                'success' => true,
                'message' => 'Resultados guardados correctamente',
                'ids_guardados' => $ids_guardados,
                'total_guardados' => count($ids_guardados)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar resultados: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== OBTENER SOLICITUD POR ID ====================
    
    if ($action === 'obtener_solicitud') {
        $id_solicitud = $_GET['id_solicitud'] ?? null;
        
        if (!$id_solicitud) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID solicitud requerido']);
            exit;
        }
        
        $solicitud = $solicitud_model->obtenerDetalles($id_solicitud);
        
        if (!$solicitud) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'solicitud' => $solicitud
        ]);
        exit;
    }

    // ==================== MARCAR CONSUMO DE AGUA ====================

    if ($action === 'marcar_consumo_agua') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'JSON inválido']);
                exit;
            }

            $id_muestra   = intval($datos['id_muestra'] ?? 0);
            $consumo_agua = (bool)($datos['consumo_agua'] ?? false);
            $usuario_id   = intval($_SESSION['usuario_id'] ?? 1);

            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Muestra inválida']);
                exit;
            }

            // Acepta tipo explícito ('Consumo Humano', 'Consumo de Agua', null)
            // Si se envía 'tipo', se usa directamente; si no, se infiere de consumo_agua (legado)
            $tiposPermitidos = ['Consumo Humano', 'Consumo de Agua'];
            if (array_key_exists('tipo', $datos)) {
                $nuevoTipo = ($datos['tipo'] !== null && in_array($datos['tipo'], $tiposPermitidos, true))
                    ? $datos['tipo']
                    : null;
            } else {
                $nuevoTipo = $consumo_agua ? 'Consumo de Agua' : null;
            }

            $sql = "UPDATE laboratorio.Muestra_Lab
                    SET Tipo_Servicio = ?,
                        Fecha_Modificacion = GETDATE()
                    WHERE Id_Muestra = ? AND Activo = 1";
            $stmt = sqlsrv_query($conn, $sql, [$nuevoTipo, $id_muestra]);
            if ($stmt === false) {
                throw new Exception('Error al actualizar: ' . print_r(sqlsrv_errors(), true));
            }

            // Sincronizar Uso_Agua en Detalle_Agua para reflejar Consumo Humano en BD.
            $nuevoUsoAgua = ($nuevoTipo === 'Consumo Humano' || $nuevoTipo === 'Consumo de Agua')
                ? 'Consumo Humano'
                : null;

            $sqlChkDet = "SELECT TOP 1 Id_Muestra
                          FROM laboratorio.Detalle_Agua
                          WHERE Id_Muestra = ? AND Activo = 1";
            $stmtChkDet = sqlsrv_query($conn, $sqlChkDet, [$id_muestra]);
            if ($stmtChkDet === false) {
                throw new Exception('Error al validar Detalle_Agua: ' . print_r(sqlsrv_errors(), true));
            }

            $rowDet = sqlsrv_fetch_array($stmtChkDet, SQLSRV_FETCH_ASSOC);
            if ($rowDet) {
                $sqlUpdDet = "UPDATE laboratorio.Detalle_Agua
                              SET Uso_Agua = ?,
                                  Fecha_Modificacion = GETDATE()
                              WHERE Id_Muestra = ? AND Activo = 1";
                $stmtUpdDet = sqlsrv_query($conn, $sqlUpdDet, [$nuevoUsoAgua, $id_muestra]);
                if ($stmtUpdDet === false) {
                    throw new Exception('Error al actualizar Uso_Agua: ' . print_r(sqlsrv_errors(), true));
                }
            } elseif ($nuevoUsoAgua !== null) {
                $sqlInsDet = "INSERT INTO laboratorio.Detalle_Agua
                              (Id_Muestra, Uso_Agua, Fuente_Agua, Cantidad_Muestra, Nivel_Agua, Usuario_Creacion, Activo, Fecha_Creacion)
                              VALUES (?, ?, NULL, '1 Litro', NULL, ?, 1, GETDATE())";
                $stmtInsDet = sqlsrv_query($conn, $sqlInsDet, [$id_muestra, $nuevoUsoAgua, $usuario_id]);
                if ($stmtInsDet === false) {
                    throw new Exception('Error al insertar Uso_Agua: ' . print_r(sqlsrv_errors(), true));
                }
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== LISTAR SERVICIOS DISPONIBLES PARA ANÁLISIS EXTRA ====================

    if ($action === 'listar_servicios_extra') {
        try {
            $id_proyecto  = intval($_GET['id_proyecto'] ?? 0);
            $id_muestra   = intval($_GET['id_muestra'] ?? 0);
            $incluir_todos = !empty($_GET['todos']);  // si ?todos=1 → muestra todos sin filtrar asignados

            if ($id_proyecto <= 0 || $id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
                exit;
            }

            if ($incluir_todos) {
                // Modo Consumo Humano: todos los servicios activos, marcando cuáles ya están asignados
                $sql = "SELECT st.Id_Servicio, st.Nombre,
                               CASE WHEN EXISTS (
                                   SELECT 1 FROM laboratorio.Solicitud_Analisis sa
                                   WHERE sa.Id_Muestra = ? AND sa.Id_Servicio = st.Id_Servicio AND sa.Activo = 1
                               ) THEN 1 ELSE 0 END AS ya_asignado
                        FROM laboratorio.Servicio_Tecnico st
                        WHERE st.Activo = 1
                        ORDER BY ya_asignado ASC, st.Nombre ASC";
                $stmt = sqlsrv_query($conn, $sql, [$id_muestra]);
            } else {
                // Modo normal: solo servicios aún no asignados a la muestra
                $sql = "SELECT st.Id_Servicio, st.Nombre, 0 AS ya_asignado
                        FROM laboratorio.Servicio_Tecnico st
                        WHERE st.Activo = 1
                          AND st.Id_Servicio NOT IN (
                              SELECT sa.Id_Servicio
                              FROM laboratorio.Solicitud_Analisis sa
                              WHERE sa.Id_Muestra = ? AND sa.Activo = 1
                          )
                        ORDER BY st.Nombre";
                $stmt = sqlsrv_query($conn, $sql, [$id_muestra]);
            }
            if ($stmt === false) {
                throw new Exception('Error al obtener servicios: ' . print_r(sqlsrv_errors(), true));
            }

            $servicios = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $idServ = intval($row['Id_Servicio'] ?? 0);

                $sqlRec = "SELECT r.Nombre, rs.Cantidad_Necesaria, ISNULL(um.Abreviatura, '') AS Unidad_Medida
                           FROM laboratorio.Receta_Servicio rs
                           INNER JOIN laboratorio.Reactivo_Lab r ON r.Id_Reactivo = rs.Id_Reactivo AND r.Activo = 1
                           LEFT JOIN laboratorio.Unidad_Medida um ON r.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                           WHERE rs.Id_Servicio = ? AND rs.Activo = 1";
                $stmtRec = sqlsrv_query($conn, $sqlRec, [$idServ]);
                $reactivos = [];
                if ($stmtRec !== false) {
                    while ($rRow = sqlsrv_fetch_array($stmtRec, SQLSRV_FETCH_ASSOC)) {
                        $reactivos[] = [
                            'nombre'   => trim((string)($rRow['Nombre'] ?? '')),
                            'cantidad' => floatval($rRow['Cantidad_Necesaria'] ?? 0),
                            'unidad'   => trim((string)($rRow['Unidad_Medida'] ?? 'UND'))
                        ];
                    }
                }

                $servicios[] = [
                    'id'          => $idServ,
                    'nombre'      => trim((string)($row['Nombre'] ?? ('Servicio #' . $idServ))),
                    'ya_asignado' => (bool)($row['ya_asignado'] ?? false),
                    'reactivos'   => $reactivos
                ];
            }

            echo json_encode(['success' => true, 'servicios' => $servicios]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== AGREGAR ANÁLISIS EXTRA ====================

    if ($action === 'agregar_analisis_extra') {
        $txStarted = false;
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'JSON inválido']);
                exit;
            }

            $id_muestra  = intval($datos['id_muestra']  ?? 0);
            $id_servicio = intval($datos['id_servicio'] ?? 0);
            $usuario_id  = intval($_SESSION['usuario_id'] ?? 1);

            if ($id_muestra <= 0 || $id_servicio <= 0) {
                throw new Exception('Parámetros inválidos: id_muestra y id_servicio son requeridos');
            }

            // Verificar que la muestra existe
            $sqlChkMuestra = "SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Id_Muestra = ? AND Activo = 1";
            $stmtChk = sqlsrv_query($conn, $sqlChkMuestra, [$id_muestra]);
            if ($stmtChk === false || !sqlsrv_fetch_array($stmtChk, SQLSRV_FETCH_ASSOC)) {
                throw new Exception('Muestra no encontrada');
            }

            if (!sqlsrv_begin_transaction($conn)) {
                throw new Exception('No se pudo iniciar transacción');
            }
            $txStarted = true;

            // 1. Obtener o crear Solicitud_Analisis activa para este servicio y muestra
            $solicitudExistente = false;
            $id_solicitud = 0;

            $sqlChkSol = "SELECT TOP 1 Id_Solicitud_Analisis
                          FROM laboratorio.Solicitud_Analisis
                          WHERE Id_Muestra = ? AND Id_Servicio = ? AND Activo = 1
                          ORDER BY Id_Solicitud_Analisis DESC";
            $stmtChkSol = sqlsrv_query($conn, $sqlChkSol, [$id_muestra, $id_servicio]);
            if ($stmtChkSol === false) {
                throw new Exception('Error al validar solicitud existente: ' . print_r(sqlsrv_errors(), true));
            }
            $rowChkSol = sqlsrv_fetch_array($stmtChkSol, SQLSRV_FETCH_ASSOC);
            if ($rowChkSol && intval($rowChkSol['Id_Solicitud_Analisis'] ?? 0) > 0) {
                $solicitudExistente = true;
                $id_solicitud = intval($rowChkSol['Id_Solicitud_Analisis']);
            } else {
                // Crear solicitud y recuperar ID de forma robusta con OUTPUT INSERTED
                $sqlSol = "INSERT INTO laboratorio.Solicitud_Analisis
                           (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Activo, Fecha_Creacion, Usuario_Creacion)
                           OUTPUT INSERTED.Id_Solicitud_Analisis AS id
                           VALUES (?, ?, 'En Análisis', GETDATE(), 1, GETDATE(), ?)";
                $stmtSol = sqlsrv_query($conn, $sqlSol, [$id_muestra, $id_servicio, $usuario_id]);
                if ($stmtSol === false) {
                    throw new Exception('Error al crear solicitud: ' . print_r(sqlsrv_errors(), true));
                }
                $rowSol = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                $id_solicitud = intval($rowSol['id'] ?? 0);
                if ($id_solicitud <= 0) {
                    throw new Exception('No se pudo obtener ID de la solicitud creada');
                }
            }

            // 2. Crear Resultado_Analisis en blanco para cada parámetro faltante del servicio
            $sqlParams = "SELECT Id_Parametro FROM laboratorio.Parametro_Analisis
                          WHERE Id_Servicio = ? AND Activo = 1";
            $stmtParams = sqlsrv_query($conn, $sqlParams, [$id_servicio]);
            if ($stmtParams === false) {
                throw new Exception('Error al obtener parámetros del servicio: ' . print_r(sqlsrv_errors(), true));
            }

            $resultadosCreados = 0;
            while ($pRow = sqlsrv_fetch_array($stmtParams, SQLSRV_FETCH_ASSOC)) {
                $idParam = intval($pRow['Id_Parametro'] ?? 0);
                if ($idParam <= 0) continue;

                $sqlChkRes = "SELECT TOP 1 Id_Resultado
                              FROM laboratorio.Resultado_Analisis
                              WHERE Id_Solicitud_Analisis = ? AND Id_Parametro = ? AND Activo = 1";
                $stmtChkRes = sqlsrv_query($conn, $sqlChkRes, [$id_solicitud, $idParam]);
                if ($stmtChkRes === false) {
                    throw new Exception('Error al verificar resultado existente: ' . print_r(sqlsrv_errors(), true));
                }
                if (sqlsrv_fetch_array($stmtChkRes, SQLSRV_FETCH_ASSOC)) {
                    continue;
                }

                $sqlRes = "INSERT INTO laboratorio.Resultado_Analisis
                           (Id_Solicitud_Analisis, Id_Parametro, Activo, Fecha_Creacion, Usuario_Creacion)
                           VALUES (?, ?, 1, GETDATE(), ?)";
                $stmtRes = sqlsrv_query($conn, $sqlRes, [$id_solicitud, $idParam, $usuario_id]);
                if ($stmtRes === false) {
                    throw new Exception('Error al crear resultado en blanco: ' . print_r(sqlsrv_errors(), true));
                }
                $resultadosCreados++;
            }

            $movimientosCreados = 0;
            // 3. Consumir reactivos de la receta del servicio DE INMEDIATO (solo si se creó nueva solicitud)
            if (!$solicitudExistente) {
                $sqlReceta = "SELECT rs.Id_Reactivo, rs.Cantidad_Necesaria, r.Nombre,
                                     ISNULL(um.Abreviatura, '') AS Unidad_Medida,
                                     ISNULL(r.Cantidad_Stock, 0) AS Stock_Actual
                              FROM laboratorio.Receta_Servicio rs
                              INNER JOIN laboratorio.Reactivo_Lab r ON r.Id_Reactivo = rs.Id_Reactivo AND r.Activo = 1
                              LEFT JOIN laboratorio.Unidad_Medida um ON r.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                              WHERE rs.Id_Servicio = ? AND rs.Activo = 1";
                $stmtReceta = sqlsrv_query($conn, $sqlReceta, [$id_servicio]);
                if ($stmtReceta === false) {
                    throw new Exception('Error al obtener receta: ' . print_r(sqlsrv_errors(), true));
                }

                while ($rRow = sqlsrv_fetch_array($stmtReceta, SQLSRV_FETCH_ASSOC)) {
                    $idReactivo  = intval($rRow['Id_Reactivo'] ?? 0);
                    $cantidad    = round(floatval($rRow['Cantidad_Necesaria'] ?? 0), 4);
                    $stockActual = round(floatval($rRow['Stock_Actual'] ?? 0), 4);
                    $nombreRea   = trim((string)($rRow['Nombre'] ?? ''));

                    if ($idReactivo <= 0 || $cantidad <= 0) continue;
                    if ($stockActual < $cantidad) {
                        throw new Exception('Stock insuficiente en reactivo "' . $nombreRea . '". Disponible: ' . $stockActual . ', requerido: ' . $cantidad);
                    }

                    $saldoNuevo = round($stockActual - $cantidad, 4);
                    $concepto   = 'Análisis Extra - Muestra #' . $id_muestra . ' - Servicio #' . $id_servicio;

                    $sqlMov = "INSERT INTO laboratorio.Movimiento_Kardex
                               (Id_Reactivo, Fecha_Registro, Tipo_Movimiento, Cantidad, Concepto, Saldo_Resultante, Activo, Fecha_Creacion, Usuario_Creacion)
                               VALUES (?, GETDATE(), 'S', ?, ?, ?, 1, GETDATE(), ?)";
                    $stmtMov = sqlsrv_query($conn, $sqlMov, [$idReactivo, $cantidad, $concepto, $saldoNuevo, $usuario_id]);
                    if ($stmtMov === false) {
                        throw new Exception('Error en Movimiento_Kardex: ' . print_r(sqlsrv_errors(), true));
                    }

                    $sqlUpdStock = "UPDATE laboratorio.Reactivo_Lab
                                    SET Cantidad_Stock = Cantidad_Stock - ?,
                                        Fecha_Modificacion = GETDATE()
                                    WHERE Id_Reactivo = ? AND Activo = 1";
                    $stmtUpdStock = sqlsrv_query($conn, $sqlUpdStock, [$cantidad, $idReactivo]);
                    if ($stmtUpdStock === false) {
                        throw new Exception('Error al actualizar stock: ' . print_r(sqlsrv_errors(), true));
                    }

                    $movimientosCreados++;
                }
            }

            // 5. Marcar muestra como modificada
            $sqlUpdM = "UPDATE laboratorio.Muestra_Lab
                        SET Fecha_Modificacion = GETDATE()
                        WHERE Id_Muestra = ? AND Activo = 1";
            sqlsrv_query($conn, $sqlUpdM, [$id_muestra]);

            sqlsrv_commit($conn);

            if ($solicitudExistente) {
                $msg = 'La solicitud del servicio ya existía para esta muestra. ';
                if ($resultadosCreados > 0) {
                    $msg .= 'Se crearon ' . $resultadosCreados . ' resultado(s) en blanco faltante(s).';
                } else {
                    $msg .= 'No fue necesario crear resultados adicionales.';
                }
            } else {
                $msg = 'Análisis extra creado: ' . $resultadosCreados . ' resultado(s) en blanco';
                if ($movimientosCreados > 0) {
                    $msg .= ', ' . $movimientosCreados . ' reactivo(s) consumido(s) del inventario';
                }
                $msg .= '. El residuo se registrará al finalizar el análisis.';
            }

            echo json_encode([
                'success'          => true,
                'message'          => $msg,
                'id_solicitud'     => $id_solicitud,
                'ya_existia'       => $solicitudExistente,
                'resultados_creados' => $resultadosCreados,
                'movimientos_creados' => $movimientosCreados
            ]);
        } catch (Exception $e) {
            if ($txStarted) {
                try { sqlsrv_rollback($conn); } catch (Exception $re) {}
            }
            http_response_code(500);
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
?>
