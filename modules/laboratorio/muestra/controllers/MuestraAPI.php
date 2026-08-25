<?php
/**
 * MuestraAPI.php
 * API Handler - Maneja acciones AJAX para Muestras y Proyectos
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');
ini_set('memory_limit', '256M');   // necesario para payload base64 de adjuntos

header('Content-Type: application/json; charset=utf-8');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $errstr
    ]);
    exit;
});

try {
    require_once '../../../../config/db.php';
    require_once '../../../../core/Auth.php';
    require_once '../models/ProyectoModel.php';
    require_once '../models/MuestraModel.php';
    require_once '../models/ResultadoAnalisisModel.php';
    
    Auth::check();
    
    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }
    
    $proyecto_model = new ProyectoModel($conn);
    $muestra_model = new MuestraModel($conn);
    $resultado_model = new ResultadoAnalisisModel($conn);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }
    
    // ==================== INICIAR EJECUCIÓN DE PROYECTO ====================
    
    if ($action === 'iniciar_ejecucion') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_proyecto = intval($datos['Id_Proyecto'] ?? 0);
            
            if ($id_proyecto <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID proyecto inválido']);
                exit;
            }
            
            // Actualizar estado del proyecto a "En Progreso"
            $resultado = $proyecto_model->guardar([
                'Id_Proyecto' => $id_proyecto,
                'Estado' => 'En Progreso'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Proyecto iniciado correctamente',
                'id_proyecto' => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al iniciar proyecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'confirmar_recepcion') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $pasa = !empty($datos['pasa']);
            $tipo_servicio = trim((string)($datos['tipo_servicio'] ?? ''));
            $observacion = trim((string)($datos['observacion'] ?? ''));
            $checklist = $datos['checklist'] ?? [];
            $usuario_id = $_SESSION['usuario_id'] ?? 1;

            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra inválido']);
                exit;
            }

            if (!$pasa && $observacion === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe registrar observación cuando la muestra no cumple']);
                exit;
            }

            if ($tipo_servicio !== 'Interno' && $tipo_servicio !== 'Externo') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo de servicio inválido']);
                exit;
            }

            // Si la muestra no pasa, restaurar reactivos consumidos (si hubiera)
            if (!$pasa) {
                $sql_movimientos = "
                    SELECT mk.Id_Movimiento, mk.Id_Reactivo, mk.Cantidad
                    FROM laboratorio.Movimiento_Kardex mk
                    INNER JOIN laboratorio.Consumo_Reaccion cr ON mk.Id_Movimiento = cr.Id_Movimiento
                    INNER JOIN laboratorio.Muestra_Producto mp ON cr.Id_Muestra_Producto = mp.Id_Muestra_Producto
                    WHERE mp.Id_Muestra = ?
                      AND mk.Tipo_Movimiento = 'S'
                      AND mk.Activo = 1
                      AND cr.Activo = 1
                      AND mp.Activo = 1
                ";
                $stmt_mov = sqlsrv_query($conn, $sql_movimientos, [$id_muestra]);
                if ($stmt_mov === false) {
                    throw new Exception('Error al obtener movimientos: ' . print_r(sqlsrv_errors(), true));
                }
                $movimientos_revertidos = 0;
                while ($fila = sqlsrv_fetch_array($stmt_mov, SQLSRV_FETCH_ASSOC)) {
                    $id_mov   = intval($fila['Id_Movimiento']);
                    $id_react = intval($fila['Id_Reactivo']);
                    $cantidad = floatval($fila['Cantidad']);
                    $r = sqlsrv_query($conn, "UPDATE laboratorio.Reactivo_Lab SET Cantidad_Stock = Cantidad_Stock + ? WHERE Id_Reactivo = ? AND Activo = 1", [$cantidad, $id_react]);
                    if ($r === false) throw new Exception('Error al restaurar stock: ' . print_r(sqlsrv_errors(), true));
                    $r = sqlsrv_query($conn, "UPDATE laboratorio.Movimiento_Kardex SET Activo = 0 WHERE Id_Movimiento = ?", [$id_mov]);
                    if ($r === false) throw new Exception('Error al anular kardex: ' . print_r(sqlsrv_errors(), true));
                    $r = sqlsrv_query($conn, "UPDATE laboratorio.Consumo_Reaccion SET Activo = 0 WHERE Id_Movimiento = ?", [$id_mov]);
                    if ($r === false) throw new Exception('Error al anular consumo: ' . print_r(sqlsrv_errors(), true));
                    $movimientos_revertidos++;
                }
            }

            $resultado = $muestra_model->confirmarRecepcion($id_muestra, $usuario_id, $pasa, $tipo_servicio, $observacion, $checklist);

            echo json_encode([
                'success' => true,
                'message' => $pasa ? 'Recepción registrada correctamente' : 'Muestra rechazada en recepción',
                'estado' => $resultado['estado']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al confirmar recepción: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'iniciar_analisis_agricultor') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $iniciar_todos = !empty($datos['iniciar_todos']);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;

            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra inválido']);
                exit;
            }

            $resultado = $muestra_model->iniciarAnalisisDesdeMuestra($id_muestra, $usuario_id, $iniciar_todos);

            echo json_encode([
                'success' => true,
                'message' => 'Análisis iniciado correctamente',
                'id_cliente' => intval($resultado['id_cliente']),
                'muestras_actualizadas' => intval($resultado['muestras_actualizadas']),
                'solicitudes_creadas' => intval($resultado['solicitudes_creadas']),
                'resultados_creados' => intval($resultado['resultados_creados'])
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al iniciar análisis: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'firmar_muestra') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $firmar_todos = !empty($datos['firmar_todos']);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;

            // Solo el Encargado de Laboratorio (Id_Rol=1) o administrador puede firmar
            $es_admin_firma = false;
            $stmtAdmF = sqlsrv_query($conn, "SELECT TOP 1 rol FROM comun.Usuarios WHERE id_usuario = ? AND activo = 1", [$usuario_id]);
            if ($stmtAdmF) {
                $rowAdmF = sqlsrv_fetch_array($stmtAdmF, SQLSRV_FETCH_ASSOC);
                if ($rowAdmF && in_array(strtolower(trim((string)$rowAdmF['rol'])), ['administrador','admin','superadmin','super admin'], true)) {
                    $es_admin_firma = true;
                }
            }
            $es_encargado = false;
            $stmtEncF = sqlsrv_query($conn, "SELECT TOP 1 1 FROM laboratorio.Usuario_Rol WHERE Id_Usuario = ? AND Id_Rol = 1", [$usuario_id]);
            if ($stmtEncF && sqlsrv_fetch_array($stmtEncF, SQLSRV_FETCH_ASSOC)) {
                $es_encargado = true;
            }
            if (!$es_admin_firma && !$es_encargado) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Solo el Encargado de Laboratorio puede firmar muestras.']);
                exit;
            }

            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra inválido']);
                exit;
            }

            // Verificar que el usuario tiene firma digital registrada
            $stmtFirma = sqlsrv_query($conn, "SELECT TOP 1 Id_Usuario FROM laboratorio.Usuario_Lab_Firma WHERE Id_Usuario = ? AND Activo = 1", [$usuario_id]);
            $firmaRow = $stmtFirma ? sqlsrv_fetch_array($stmtFirma, SQLSRV_FETCH_ASSOC) : null;
            if (!$firmaRow) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Debes registrar tu firma digital antes de poder firmar muestras. Ve al Módulo Principal → Mi Firma Digital.']);
                exit;
            }

            $muestra = $muestra_model->obtenerPorId($id_muestra);
            if (!$muestra) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Muestra no encontrada']);
                exit;
            }

            if (strtolower(trim((string)($muestra['Estado'] ?? ''))) !== strtolower('Por Firmar')) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo se pueden firmar muestras en estado Por Firmar'
                ]);
                exit;
            }

            if ($firmar_todos) {
                $id_cliente = intval($muestra['Id_Cliente'] ?? 0);
                if ($id_cliente <= 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'No se encontró cliente asociado']);
                    exit;
                }

                $totalFirmadas = $muestra_model->firmarMuestrasPorAgricultor($id_cliente, $usuario_id);

                echo json_encode([
                    'success' => true,
                    'message' => 'Firmas registradas correctamente',
                    'muestras_firmadas' => intval($totalFirmadas),
                    'firmar_todos' => true
                ]);
                exit;
            }

            $muestra_model->validarMuestra($id_muestra, $usuario_id);

            echo json_encode([
                'success' => true,
                'message' => 'Firma registrada. La muestra pasó a Finalizado.'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al firmar muestra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'obtener_detalle_firma') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $firmar_todos = !empty($datos['firmar_todos']);

            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra inválido']);
                exit;
            }

            $muestraBase = $muestra_model->obtenerPorId($id_muestra);
            if (!$muestraBase) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Muestra no encontrada']);
                exit;
            }

            $id_cliente = intval($muestraBase['Id_Cliente'] ?? 0);
            if ($id_cliente <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cliente no asociado']);
                exit;
            }

            $muestrasObjetivo = [$id_muestra];
            if ($firmar_todos) {
                $muestrasObjetivo = $muestra_model->obtenerMuestrasPorFirmarAgricultor($id_cliente);
            }

            if (empty($muestrasObjetivo)) {
                echo json_encode([
                    'success' => true,
                    'agricultor' => trim((string)($muestraBase['Agricultor'] ?? '')),
                    'id_cliente' => $id_cliente,
                    'muestras' => [],
                    'resultados' => []
                ]);
                exit;
            }

            $muestrasData = [];
            $resultadosData = [];

            foreach ($muestrasObjetivo as $idObj) {
                $muestra = $muestra_model->obtenerPorId($idObj);
                if (!$muestra) {
                    continue;
                }

                $estado = strtolower(trim((string)($muestra['Estado'] ?? '')));
                if ($estado !== strtolower('Por Firmar')) {
                    continue;
                }

                $muestrasData[] = [
                    'id_muestra' => intval($muestra['Id_Muestra'] ?? 0),
                    'agricultor' => trim((string)($muestra['Agricultor'] ?? '')),
                    'valle' => trim((string)($muestra['Valle'] ?? '')),
                    'estado' => trim((string)($muestra['Estado'] ?? '')),
                ];

                $resultados = $resultado_model->obtenerResultadosEditables(intval($muestra['Id_Muestra']));
                foreach ($resultados as $r) {
                    $resultadosData[] = [
                        'id_muestra' => intval($muestra['Id_Muestra'] ?? 0),
                        'servicio' => trim((string)($r['Servicio_Nombre'] ?? '')),
                        'parametro' => trim((string)($r['Parametro_Nombre'] ?? '')),
                        'unidad' => trim((string)($r['Unidad_Medida'] ?? '')),
                        'valor_hallado' => $r['Valor_Hallado']
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'agricultor' => trim((string)($muestraBase['Agricultor'] ?? '')),
                'id_cliente' => $id_cliente,
                'muestras' => $muestrasData,
                'resultados' => $resultadosData
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener detalle de firma: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'obtener_catalogos_por_defecto') {
        try {
            $agricultores = $muestra_model->obtenerAgricultoresActivos();
            $valles = $muestra_model->obtenerVallesRegistrados();
            $servicios = $muestra_model->obtenerServiciosDisponibles();

            echo json_encode([
                'success' => true,
                'agricultores' => $agricultores,
                'valles' => $valles,
                'servicios' => $servicios
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al cargar catálogos: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'obtener_muestras_por_defecto') {
        try {
            $draw = intval($_POST['draw'] ?? 0);
            $start = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $searchValue = trim((string)($_POST['search']['value'] ?? ''));

            $filas = $muestra_model->obtenerMuestrasOriginalesPorDefecto($start, $length, $searchValue);
            $total = $muestra_model->contarMuestrasOriginalesPorDefecto('');
            $filtrados = $searchValue === '' ? $total : $muestra_model->contarMuestrasOriginalesPorDefecto($searchValue);

            $data = [];
            foreach ($filas as $row) {
                $idMuestra = intval($row['Id_Muestra'] ?? 0);
                $data[] = [
                    'id' => $idMuestra,
                    'activo' => intval($row['Activo'] ?? 1),
                    'estado' => intval($row['Activo'] ?? 1) === 1
                        ? '<span class="badge bg-green-lt">Activo</span>'
                        : '<span class="badge bg-secondary-lt">Inactivo</span>',
                    'ubicacion_punto' => trim((string)($row['Ubicacion_Punto'] ?? '-')),
                    'punto_toma' => trim((string)($row['Punto_Toma'] ?? '-')),
                    'coordenadas' => trim((string)($row['Coordenadas'] ?? '-')),
                    'valle' => trim((string)($row['Valle'] ?? '-')),
                    'fecha_creacion' => trim((string)($row['Fecha_Creacion'] ?? '-')),
                    'tipo_muestra' => trim((string)($row['Tipo_Muestra'] ?? '-')),
                    'turno' => trim((string)($row['Turno'] ?? '')) !== '' ? trim((string)($row['Turno'] ?? '')) : '-',
                    'accion' => intval($row['Activo'] ?? 1) === 1
                        ? '<div class="d-flex gap-1">'
                            . '<button type="button" class="btn btn-sm btn-primary" title="Editar" onclick="editarMuestraPorDefecto(' . $idMuestra . ')"><i class="ti ti-edit"></i></button>'
                            . '<button type="button" class="btn btn-sm btn-danger" title="Desactivar" onclick="desactivarMuestraPorDefecto(' . $idMuestra . ')"><i class="ti ti-trash"></i></button>'
                          . '</div>'
                        : '<button type="button" class="btn btn-sm btn-ghost-success" title="Reactivar" onclick="reactivarMuestraPorDefecto(' . $idMuestra . ')"><i class="ti ti-check"></i></button>'
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => intval($total),
                'recordsFiltered' => intval($filtrados),
                'data' => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'draw' => intval($_POST['draw'] ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error al listar muestras por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'obtener_muestras_por_defecto_en_analisis') {
        try {
            $draw = intval($_POST['draw'] ?? 0);
            $start = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $searchValue = trim((string)($_POST['search']['value'] ?? ''));

            $filas = $muestra_model->obtenerMuestrasDuplicadasEnAnalisisPorDefecto($start, $length, $searchValue);
            $total = $muestra_model->contarMuestrasDuplicadasEnAnalisisPorDefecto('');
            $filtrados = $searchValue === '' ? $total : $muestra_model->contarMuestrasDuplicadasEnAnalisisPorDefecto($searchValue);

            $data = [];
            foreach ($filas as $row) {
                $idMuestra = intval($row['Id_Muestra'] ?? 0);
                $idOriginal = intval($row['Muestra_Original'] ?? 0);
                $data[] = [
                    'id' => $idMuestra,
                    'id_bitacora' => intval($row['Id_Bitacora'] ?? 0),
                    'fecha_bitacora' => trim((string)($row['Fecha_Bitacora'] ?? '-')),
                    'ubicacion_punto' => trim((string)($row['Ubicacion_Punto'] ?? '-')),
                    'punto_toma' => trim((string)($row['Punto_Toma'] ?? '-')),
                    'coordenadas' => trim((string)($row['Coordenadas'] ?? '-')),
                    'valle' => trim((string)($row['Valle'] ?? '-')),
                    'fecha_creacion' => trim((string)($row['Fecha_Creacion'] ?? '-')),
                    'tipo_muestra' => trim((string)($row['Tipo_Muestra'] ?? '-')),
                    'turno' => trim((string)($row['Turno'] ?? '-')),
                    'id_original' => $idOriginal > 0 ? $idOriginal : '-',
                    'accion' => '<a class="btn btn-sm btn-primary" title="Continuar análisis de la bitácora" href="?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_muestra=' . $idMuestra . '&id_bitacora=' . intval($row['Id_Bitacora'] ?? 0) . '&agricultor=' . rawurlencode('Muestra por defecto') . '"><i class="ti ti-clipboard-data"></i></a>'
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => intval($total),
                'recordsFiltered' => intval($filtrados),
                'data' => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'draw' => intval($_POST['draw'] ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error al listar muestras en análisis por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'obtener_resumen_bitacoras_por_defecto') {
        try {
            $fechaDesde = trim((string)($_POST['fecha_desde'] ?? $_GET['fecha_desde'] ?? ''));
            $fechaHasta = trim((string)($_POST['fecha_hasta'] ?? $_GET['fecha_hasta'] ?? ''));

            $rows = $muestra_model->obtenerResumenBitacorasPorDefecto($fechaDesde, $fechaHasta);
            $data = [];
            foreach ($rows as $row) {
                $idBitManana = intval($row['Id_Bitacora_Manana'] ?? 0);
                $idBitTarde = intval($row['Id_Bitacora_Tarde'] ?? 0);
                $obsManana = trim((string)($row['Observacion_Manana'] ?? ''));
                $obsTarde = trim((string)($row['Observacion_Tarde'] ?? ''));
                $obsMananaJs = str_replace(["\\", "'", "\r", "\n"], ["\\\\", "\\'", ' ', ' '], $obsManana);
                $obsTardeJs = str_replace(["\\", "'", "\r", "\n"], ["\\\\", "\\'", ' ', ' '], $obsTarde);
                $totalManana = intval($row['Total_Muestras_Manana'] ?? 0);
                $totalTarde = intval($row['Total_Muestras_Tarde'] ?? 0);
                $fecha = trim((string)($row['Fecha'] ?? ''));

                $turnoManana = $idBitManana > 0
                    ? '<div><span class="badge bg-blue-lt me-1">#' . $idBitManana . '</span><span class="text-muted">Muestras: ' . $totalManana . '</span></div>'
                    : '<span class="badge bg-secondary-lt">Sin bitácora</span>';
                $turnoTarde = $idBitTarde > 0
                    ? '<div><span class="badge bg-blue-lt me-1">#' . $idBitTarde . '</span><span class="text-muted">Muestras: ' . $totalTarde . '</span></div>'
                    : '<span class="badge bg-secondary-lt">Sin bitácora</span>';

                if ($idBitManana > 0 && $totalManana === 0) {
                    $turnoManana .= '<div class="small text-warning mt-1">Sin muestras: registre observación.</div>';
                }
                if ($idBitTarde > 0 && $totalTarde === 0) {
                    $turnoTarde .= '<div class="small text-warning mt-1">Sin muestras: registre observación.</div>';
                }

                $acciones = '<div class="d-flex gap-1">';
                if ($idBitManana > 0 || $idBitTarde > 0) {
                    $acciones .= '<button type="button" class="btn btn-sm btn-primary" title="Ver detalle por fecha" onclick="abrirDetalleBitacoraFecha(\'' . $fecha . '\')"><i class="ti ti-eye"></i> Ver</button>';
                }
                if ($idBitManana > 0) {
                    $acciones .= '<button type="button" class="btn btn-sm btn-outline-primary" title="Observación Mañana" onclick="abrirObservacionBitacora(' . $idBitManana . ', \'' . $obsMananaJs . '\', \'' . $fecha . '\', \'Mañana\')"><i class="ti ti-message-2"></i></button>';
                } else {
                    $acciones .= '<button type="button" class="btn btn-sm btn-success" title="Crear bitácora Mañana" onclick="crearBitacoraTurno(\'' . $fecha . '\', \'Mañana\')"><i class="ti ti-plus"></i> Mañana</button>';
                }

                if ($idBitTarde > 0) {
                    $acciones .= '<button type="button" class="btn btn-sm btn-outline-primary" title="Observación Tarde" onclick="abrirObservacionBitacora(' . $idBitTarde . ', \'' . $obsTardeJs . '\', \'' . $fecha . '\', \'Tarde\')"><i class="ti ti-message-2"></i></button>';
                } else {
                    $acciones .= '<button type="button" class="btn btn-sm btn-success" title="Crear bitácora Tarde" onclick="crearBitacoraTurno(\'' . $fecha . '\', \'Tarde\')"><i class="ti ti-plus"></i> Tarde</button>';
                }
                $acciones .= '</div>';

                $data[] = [
                    'fecha' => $fecha,
                    'manana' => $turnoManana,
                    'observacion_manana' => $obsManana !== '' ? htmlspecialchars($obsManana, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">(sin observación)</span>',
                    'tarde' => $turnoTarde,
                    'observacion_tarde' => $obsTarde !== '' ? htmlspecialchars($obsTarde, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">(sin observación)</span>',
                    'accion' => $acciones
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener resumen de bitácoras: ' . $e->getMessage(),
                'data' => []
            ]);
        }
        exit;
    }

    if ($action === 'obtener_detalle_bitacora_por_fecha') {
        try {
            $fecha = trim((string)($_GET['fecha'] ?? $_POST['fecha'] ?? ''));
            if ($fecha === '') {
                throw new Exception('Debe seleccionar una fecha válida.');
            }

            $bitacoras = $muestra_model->obtenerBitacorasPorFechaDefecto($fecha);

            $armarTurno = function($turnoData) use ($muestra_model) {
                $idBitacora = intval($turnoData['Id_Bitacora'] ?? 0);
                $totalMuestras = intval($turnoData['Total_Muestras'] ?? 0);
                $observacion = trim((string)($turnoData['Observacion_General'] ?? ''));
                $resultados = $idBitacora > 0 ? $muestra_model->obtenerResultadosPorBitacora($idBitacora) : [];
                $pendiente = $idBitacora > 0 ? $muestra_model->bitacoraTieneAnalisisPendiente($idBitacora) : false;

                $resultadoRows = [];
                foreach ($resultados as $r) {
                    $resultadoRows[] = [
                        'id_muestra' => intval($r['Id_Muestra'] ?? 0),
                        'ubicacion_punto' => trim((string)($r['Ubicacion_Punto'] ?? '-')),
                        'punto_toma' => trim((string)($r['Punto_Toma'] ?? '-')),
                        'parametro' => trim((string)($r['Parametro'] ?? '-')),
                        'unidad' => trim((string)($r['Unidad'] ?? '')),
                        'valor_hallado' => trim((string)($r['Valor_Hallado'] ?? '')),
                        'estado' => trim((string)($r['Estado'] ?? '-'))
                    ];
                }

                return [
                    'id_bitacora' => $idBitacora,
                    'total_muestras' => $totalMuestras,
                    'observacion' => $observacion,
                    'tiene_pendientes' => $pendiente,
                    'resultados' => $resultadoRows
                ];
            };

            echo json_encode([
                'success' => true,
                'fecha' => $fecha,
                'manana' => $armarTurno($bitacoras['Mañana'] ?? []),
                'tarde' => $armarTurno($bitacoras['Tarde'] ?? [])
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener detalle de bitácora por fecha: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'crear_bitacora_por_defecto_turno') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $idBitacora = $muestra_model->crearBitacoraPorDefectoTurno(
                trim((string)($datos['fecha_registro'] ?? '')),
                trim((string)($datos['turno'] ?? '')),
                trim((string)($datos['observacion'] ?? ''))
            );

            echo json_encode([
                'success' => true,
                'message' => 'Bitácora creada correctamente',
                'id_bitacora' => $idBitacora
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear bitácora: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'actualizar_observacion_bitacora_por_defecto') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $muestra_model->actualizarObservacionBitacoraPorDefecto(
                intval($datos['id_bitacora'] ?? 0),
                trim((string)($datos['observacion'] ?? ''))
            );

            echo json_encode([
                'success' => true,
                'message' => 'Observación de bitácora actualizada correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar observación de bitácora: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'exportar_bitacoras_por_defecto_rango') {
        try {
            $fechaDesde = trim((string)($_GET['fecha_desde'] ?? $_POST['fecha_desde'] ?? ''));
            $fechaHasta = trim((string)($_GET['fecha_hasta'] ?? $_POST['fecha_hasta'] ?? ''));
            $rows = $muestra_model->obtenerBitacorasTurnoParaExportacion($fechaDesde, $fechaHasta);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="bitacoras_por_defecto_' . date('Ymd_His') . '.csv"');

            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fecha', 'Turno', 'Id Bitacora', 'Total Muestras', 'Observacion']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    trim((string)($r['Fecha'] ?? '')),
                    trim((string)($r['Turno'] ?? '')),
                    intval($r['Id_Bitacora'] ?? 0),
                    intval($r['Total_Muestras'] ?? 0),
                    trim((string)($r['Observacion_General'] ?? ''))
                ]);
            }
            fclose($out);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al exportar bitácoras: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'obtener_muestra_por_defecto') {
        try {
            $idMuestra = intval($_GET['id_muestra'] ?? $_POST['id_muestra'] ?? 0);
            $detalle = $muestra_model->obtenerMuestraPorDefectoPorId($idMuestra);
            if (!$detalle) {
                throw new Exception('No se encontró la muestra solicitada.');
            }

            echo json_encode([
                'success' => true,
                'data' => $detalle
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener muestra por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'guardar_muestra_por_defecto') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $resultado = $muestra_model->crearMuestraPorDefectoOriginal([
                'Id_Cliente' => intval($datos['id_cliente'] ?? 0),
                'Valle' => trim((string)($datos['valle'] ?? '')),
                'Eje_X' => isset($datos['eje_x']) ? trim((string)$datos['eje_x']) : null,
                'Eje_Y' => isset($datos['eje_y']) ? trim((string)$datos['eje_y']) : null,
                'Fecha_Recepcion' => trim((string)($datos['fecha_registro'] ?? '')),
                'Fecha_Toma' => trim((string)($datos['fecha_registro'] ?? '')),
                'Turno' => trim((string)($datos['turno'] ?? 'Mañana')),
                'Tipo_Muestra' => trim((string)($datos['tipo_muestra'] ?? 'Agua')),
                'Punto_Toma' => trim((string)($datos['punto_toma'] ?? '')),
                'Ubicacion_Punto' => trim((string)($datos['ubicacion_punto'] ?? '')),
                'Observacion_Muestra' => trim((string)($datos['observacion'] ?? '')),
                'Id_Producto_Venta' => intval($datos['id_producto_venta'] ?? (($datos['servicios'][0] ?? 0))),
                'Es_Muestra_Original' => 1,
                'Uso_Agua' => trim((string)($datos['uso_agua'] ?? '')),
                'Fuente_Agua' => trim((string)($datos['fuente_agua'] ?? '')),
                'Nivel_Agua' => trim((string)($datos['nivel_agua'] ?? '')),
                'Cantidad_Muestra_Agua' => trim((string)($datos['cantidad_agua'] ?? '1 Litro')),
                'Fuente_Riego' => trim((string)($datos['fuente_riego'] ?? '')),
                'Profundidad' => trim((string)($datos['profundidad'] ?? '')),
                'Numero_Submuestras' => trim((string)($datos['numero_submuestras'] ?? '')),
                'Cantidad_Muestra_Suelo' => trim((string)($datos['cantidad_suelo'] ?? '1 Kg')),
                'Cultivo_Anterior' => trim((string)($datos['cultivo_anterior'] ?? '')),
                'Cultivo_Implementado' => trim((string)($datos['cultivo_implementado'] ?? '')),
                'Cultivo_Por_Implementar' => trim((string)($datos['cultivo_por_implementar'] ?? ''))
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Muestra por defecto creada correctamente',
                'id_muestra' => intval($resultado['id_muestra'] ?? 0),
                'id_bitacora' => intval($resultado['id_bitacora'] ?? 0)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear muestra por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'guardar_muestra_individual') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $resultado = $muestra_model->crearMuestraIndividual([
                'Id_Cliente' => intval($datos['id_cliente'] ?? 0),
                'Valle' => trim((string)($datos['valle'] ?? '')),
                'Eje_X' => isset($datos['eje_x']) ? trim((string)$datos['eje_x']) : null,
                'Eje_Y' => isset($datos['eje_y']) ? trim((string)$datos['eje_y']) : null,
                'Fecha_Toma' => trim((string)($datos['fecha_toma'] ?? '')),
                'Tipo_Muestra' => trim((string)($datos['tipo_muestra'] ?? 'Agua')),
                'Tipo_Servicio' => trim((string)($datos['tipo_servicio'] ?? 'Interno')),
                'Observacion_Muestra' => trim((string)($datos['observacion'] ?? '')),
                'Ubicacion_Punto' => trim((string)($datos['ubicacion_punto'] ?? '')),
                'Punto_Toma' => trim((string)($datos['punto_toma'] ?? '')),
                'Id_Producto_Venta' => intval($datos['id_producto_venta'] ?? 0),
                'Uso_Agua' => trim((string)($datos['uso_agua'] ?? '')),
                'Fuente_Agua' => trim((string)($datos['fuente_agua'] ?? '')),
                'Nivel_Agua' => trim((string)($datos['nivel_agua'] ?? '')),
                'Cantidad_Muestra_Agua' => trim((string)($datos['cantidad_agua'] ?? '1 Litro')),
                'Fuente_Riego' => trim((string)($datos['fuente_riego'] ?? '')),
                'Profundidad' => trim((string)($datos['profundidad'] ?? '')),
                'Numero_Submuestras' => trim((string)($datos['numero_submuestras'] ?? '')),
                'Cantidad_Muestra_Suelo' => trim((string)($datos['cantidad_suelo'] ?? '1 Kg')),
                'Cultivo_Anterior' => trim((string)($datos['cultivo_anterior'] ?? '')),
                'Cultivo_Implementado' => trim((string)($datos['cultivo_implementado'] ?? '')),
                'Cultivo_Por_Implementar' => trim((string)($datos['cultivo_por_implementar'] ?? '')),
                'Ruta_Imagen' => (isset($datos['ruta_imagen']) && $datos['ruta_imagen'] !== '') ? $datos['ruta_imagen'] : null
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Muestra individual creada correctamente',
                'id_muestra' => intval($resultado['id_muestra'] ?? 0)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear muestra individual: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'actualizar_muestra_por_defecto') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $muestra_model->actualizarMuestraPorDefectoOriginal([
                'Id_Muestra' => intval($datos['id_muestra'] ?? 0),
                'Id_Cliente' => intval($datos['id_cliente'] ?? 0),
                'Valle' => trim((string)($datos['valle'] ?? '')),
                'Eje_X' => isset($datos['eje_x']) ? trim((string)$datos['eje_x']) : null,
                'Eje_Y' => isset($datos['eje_y']) ? trim((string)$datos['eje_y']) : null,
                'Fecha_Recepcion' => trim((string)($datos['fecha_registro'] ?? '')),
                'Fecha_Toma' => trim((string)($datos['fecha_registro'] ?? '')),
                'Turno' => trim((string)($datos['turno'] ?? 'Mañana')),
                'Tipo_Muestra' => trim((string)($datos['tipo_muestra'] ?? 'Agua')),
                'Punto_Toma' => trim((string)($datos['punto_toma'] ?? '')),
                'Ubicacion_Punto' => trim((string)($datos['ubicacion_punto'] ?? '')),
                'Observacion_Muestra' => trim((string)($datos['observacion'] ?? '')),
                'Id_Producto_Venta' => intval($datos['id_producto_venta'] ?? 0),
                'Uso_Agua' => trim((string)($datos['uso_agua'] ?? '')),
                'Fuente_Agua' => trim((string)($datos['fuente_agua'] ?? '')),
                'Nivel_Agua' => trim((string)($datos['nivel_agua'] ?? '')),
                'Cantidad_Muestra_Agua' => trim((string)($datos['cantidad_agua'] ?? '1 Litro')),
                'Fuente_Riego' => trim((string)($datos['fuente_riego'] ?? '')),
                'Profundidad' => trim((string)($datos['profundidad'] ?? '')),
                'Numero_Submuestras' => trim((string)($datos['numero_submuestras'] ?? '')),
                'Cantidad_Muestra_Suelo' => trim((string)($datos['cantidad_suelo'] ?? '1 Kg')),
                'Cultivo_Anterior' => trim((string)($datos['cultivo_anterior'] ?? '')),
                'Cultivo_Implementado' => trim((string)($datos['cultivo_implementado'] ?? '')),
                'Cultivo_Por_Implementar' => trim((string)($datos['cultivo_por_implementar'] ?? ''))
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Muestra por defecto actualizada correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar muestra por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'duplicar_muestras_por_defecto') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $resultado = $muestra_model->duplicarMuestrasPorDefecto(
                $datos['ids_muestras'] ?? [],
                trim((string)($datos['fecha_registro'] ?? '')),
                trim((string)($datos['turno'] ?? ''))
            );

            echo json_encode([
                'success' => true,
                'message' => 'Duplicación ejecutada correctamente',
                'id_bitacora' => intval($resultado['id_bitacora'] ?? 0),
                'id_muestra_inicial' => intval($resultado['id_muestra_inicial'] ?? 0),
                'total' => intval($resultado['total'] ?? 0),
                'ids_muestras' => $resultado['ids_muestras'] ?? []
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al duplicar muestras por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'desactivar_muestra_por_defecto') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }
            $idMuestra = intval($datos['id_muestra'] ?? $_GET['id_muestra'] ?? 0);
            $muestra_model->desactivarMuestraPorDefecto($idMuestra);

            echo json_encode([
                'success' => true,
                'message' => 'Muestra por defecto desactivada correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al desactivar muestra por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'reactivar_muestra_por_defecto') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }
            $idMuestra = intval($datos['id_muestra'] ?? $_GET['id_muestra'] ?? 0);
            $muestra_model->reactivarMuestraPorDefecto($idMuestra);

            echo json_encode([
                'success' => true,
                'message' => 'Muestra por defecto reactivada correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al reactivar muestra por defecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== ELIMINAR PROYECTO ====================
    
    if ($action === 'eliminar_proyecto') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_proyecto = intval($datos['Id_Proyecto'] ?? 0);
            
            if ($id_proyecto <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID proyecto inválido']);
                exit;
            }
            
            $proyecto_model->eliminar($id_proyecto);
            
            echo json_encode([
                'success' => true,
                'message' => 'Proyecto eliminado correctamente'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar proyecto: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== RECHAZAR MUESTRA ====================

    if ($action === 'rechazar_muestra') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $motivo     = trim($datos['motivo'] ?? '');

            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra inválido']);
                exit;
            }

            // Obtener todos los movimientos de salida (kardex) vinculados a esta muestra
            $sql_movimientos = "
                SELECT mk.Id_Movimiento, mk.Id_Reactivo, mk.Cantidad
                FROM laboratorio.Movimiento_Kardex mk
                INNER JOIN laboratorio.Consumo_Reaccion cr ON mk.Id_Movimiento = cr.Id_Movimiento
                INNER JOIN laboratorio.Muestra_Producto mp ON cr.Id_Muestra_Producto = mp.Id_Muestra_Producto
                WHERE mp.Id_Muestra = ?
                  AND mk.Tipo_Movimiento = 'S'
                  AND mk.Activo = 1
                  AND cr.Activo = 1
                  AND mp.Activo = 1
            ";
            $stmt_mov = sqlsrv_query($conn, $sql_movimientos, [$id_muestra]);
            if ($stmt_mov === false) {
                throw new Exception('Error al obtener movimientos: ' . print_r(sqlsrv_errors(), true));
            }

            $movimientos = [];
            while ($fila = sqlsrv_fetch_array($stmt_mov, SQLSRV_FETCH_ASSOC)) {
                $movimientos[] = $fila;
            }

            // Revertir cada salida: restaurar stock y anular kardex + consumo
            foreach ($movimientos as $mov) {
                $id_mov    = intval($mov['Id_Movimiento']);
                $id_react  = intval($mov['Id_Reactivo']);
                $cantidad  = floatval($mov['Cantidad']);

                // Restaurar stock del reactivo
                $sql_stock = "UPDATE laboratorio.Reactivo_Lab SET Cantidad_Stock = Cantidad_Stock + ? WHERE Id_Reactivo = ? AND Activo = 1";
                $r = sqlsrv_query($conn, $sql_stock, [$cantidad, $id_react]);
                if ($r === false) {
                    throw new Exception('Error al restaurar stock: ' . print_r(sqlsrv_errors(), true));
                }

                // Marcar movimiento kardex como inactivo
                $sql_kardex = "UPDATE laboratorio.Movimiento_Kardex SET Activo = 0 WHERE Id_Movimiento = ?";
                $r = sqlsrv_query($conn, $sql_kardex, [$id_mov]);
                if ($r === false) {
                    throw new Exception('Error al anular kardex: ' . print_r(sqlsrv_errors(), true));
                }

                // Marcar consumo_reaccion como inactivo
                $sql_consumo = "UPDATE laboratorio.Consumo_Reaccion SET Activo = 0 WHERE Id_Movimiento = ?";
                $r = sqlsrv_query($conn, $sql_consumo, [$id_mov]);
                if ($r === false) {
                    throw new Exception('Error al anular consumo: ' . print_r(sqlsrv_errors(), true));
                }
            }

            // Marcar la muestra como Rechazado
            $muestra_model->rechazarMuestra($id_muestra, $motivo);

            echo json_encode([
                'success'            => true,
                'message'            => 'Muestra rechazada correctamente',
                'movimientos_revertidos' => count($movimientos)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al rechazar muestra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== OBTENER DETALLE MUESTRA RECHAZADA ====================

    if ($action === 'obtener_rechazada') {
        try {
            $id_muestra = intval($_GET['id_muestra'] ?? 0);
            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra inválido']);
                exit;
            }
            $sql_r = "SELECT m.Id_Muestra,
                             RTRIM(CONCAT(c.Nombres, ' ', c.Apellido_Paterno,
                                   CASE WHEN c.Apellido_Materno IS NOT NULL AND c.Apellido_Materno <> '' THEN ' ' + c.Apellido_Materno ELSE '' END)) AS Agricultor,
                             m.Valle,
                             m.Tipo_Servicio,
                             CONVERT(VARCHAR(16), m.Fecha_Analisis, 120) AS Fecha_Analisis,
                             CONVERT(VARCHAR(16), m.Fecha_Recepcion, 120) AS Fecha_Rechazo,
                             m.Observacion_Muestra,
                             CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                                  WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                                  ELSE 'Sin clasificar' END AS TipoMuestra
                      FROM laboratorio.Muestra_Lab m
                      INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
                      LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
                      LEFT JOIN laboratorio.Detalle_Agua   da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
                      WHERE m.Id_Muestra = ? AND m.Activo = 1";
            $stmt_r = sqlsrv_query($conn, $sql_r, [$id_muestra]);
            if ($stmt_r === false) throw new Exception('Error al obtener rechazada: ' . print_r(sqlsrv_errors(), true));
            $row_r = sqlsrv_fetch_array($stmt_r, SQLSRV_FETCH_ASSOC);
            if (!$row_r) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Muestra no encontrada']);
                exit;
            }
            // Convertir DateTime a string
            foreach (['Fecha_Analisis','Fecha_Rechazo'] as $campo) {
                if ($row_r[$campo] instanceof DateTime) {
                    $row_r[$campo] = $row_r[$campo]->format('Y-m-d H:i');
                }
            }
            echo json_encode(['success' => true, 'data' => $row_r]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== RETORNAR MUESTRA A ANÁLISIS (corregir resultados) ====================

    if ($action === 'retornar_a_analisis') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $usuario_id = $_SESSION['usuario_id'] ?? 0;

            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra inválido']);
                exit;
            }

            // Solo Encargado (Id_Rol=1), Analista Jefe (Id_Rol=2) o admin pueden devolver a análisis
            $es_admin_ret = isset($_SESSION['rol']) && strtolower(trim($_SESSION['rol'])) === 'administrador';
            if (!$es_admin_ret) {
                $sql_rol_ret = "SELECT ur.Id_Rol FROM laboratorio.Usuario_Rol ur WHERE ur.Id_Usuario = ? AND ur.Id_Rol IN (1,2)";
                $stmt_rol_ret = sqlsrv_query($conn, $sql_rol_ret, [$usuario_id]);
                if ($stmt_rol_ret === false || !sqlsrv_fetch_array($stmt_rol_ret, SQLSRV_FETCH_ASSOC)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'No tiene permisos para retornar la muestra a análisis']);
                    exit;
                }
            }

            // Verificar que la muestra esté en estado Por Firmar
            $sql_estado = "SELECT Estado FROM laboratorio.Muestra_Lab WHERE Id_Muestra = ? AND Activo = 1";
            $stmt_estado = sqlsrv_query($conn, $sql_estado, [$id_muestra]);
            if ($stmt_estado === false) throw new Exception('Error al verificar estado: ' . print_r(sqlsrv_errors(), true));
            $row_estado = sqlsrv_fetch_array($stmt_estado, SQLSRV_FETCH_ASSOC);
            if (!$row_estado || strtolower(trim($row_estado['Estado'])) !== 'por firmar') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La muestra no está en estado Por Firmar']);
                exit;
            }

            // Revertir estado a En Analisis y limpiar firma
            $sql_revert = "UPDATE laboratorio.Muestra_Lab
                           SET Estado = 'En Analisis',
                               Id_Jefe_Lab = NULL,
                               Fecha_Modificacion = GETDATE()
                           WHERE Id_Muestra = ? AND Activo = 1";
            $r = sqlsrv_query($conn, $sql_revert, [$id_muestra]);
            if ($r === false) throw new Exception('Error al retornar muestra: ' . print_r(sqlsrv_errors(), true));

            // Revertir estado de solicitudes a En Análisis para permitir re-ingreso
            $sql_sol = "UPDATE laboratorio.Solicitud_Analisis
                        SET Estado = 'En Análisis', Fecha_Modificacion = GETDATE()
                        WHERE Id_Muestra = ?";
            sqlsrv_query($conn, $sql_sol, [$id_muestra]);

            echo json_encode([
                'success'  => true,
                'message'  => 'Muestra retornada a análisis. Puede corregir los resultados.',
                'id_muestra' => $id_muestra
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al retornar muestra: ' . $e->getMessage()
            ]);
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
