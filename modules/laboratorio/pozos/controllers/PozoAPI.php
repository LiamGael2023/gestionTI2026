<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');
ini_set('memory_limit', '256M');

header('Content-Type: application/json; charset=utf-8');

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $errstr]);
    exit;
});

try {
    require_once '../../../../config/config.php';
    require_once '../../../../config/db.php';
    require_once '../../../../config/db_postgresql.php';
    require_once '../../../../core/Auth.php';
    require_once '../models/CatastroPozoModel.php';
    require_once '../models/MonitoreoPozoAsignacionModel.php';
    require_once '../models/SincronizacionMonitoreoModel.php';

    Auth::check();

    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception('Error: No se pudo conectar a la base de datos');
    }

    $catastroModel  = new CatastroPozoModel($conn);
    $asignacionModel= new MonitoreoPozoAsignacionModel($conn);

    $action = $_GET['action'] ?? $_POST['action'] ?? null;

    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Accion no especificada']);
        exit;
    }

    // ==================== SINCRONIZAR POZOS DESDE POSTGRESQL ====================
    if ($action === 'sincronizar_pozos') {
        try {
            $pdoPg = ConexionPostgreSQL::conectar();
            if (!$pdoPg) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo conectar a PostgreSQL. Verifique PG_HOST, PG_DB, PG_USER, PG_PASS en config/config.php']);
                exit;
            }

            $resultado = $catastroModel->sincronizarDesdePostgreSQL($pdoPg);

            echo json_encode([
                'success'      => true,
                'message'      => 'Sincronizacion completada exitosamente.',
                'insertados'   => intval($resultado['insertados']),
                'actualizados' => intval($resultado['actualizados']),
                'sin_cambios'  => intval($resultado['sin_cambios'] ?? 0),
                'total_pg'     => intval($resultado['total_pg'])
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al sincronizar: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==================== SINCRONIZAR MONITOREOS DESDE POSTGRESQL ====================
    if ($action === 'sincronizar_monitoreos') {
        try {
            $pdoPg = ConexionPostgreSQL::conectar();
            if (!$pdoPg) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo conectar a PostgreSQL.']);
                exit;
            }

            $sincModel = new SincronizacionMonitoreoModel($conn);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;
            $resultado = $sincModel->sincronizarMonitoreos($pdoPg, $usuario_id);

            echo json_encode([
                'success' => true,
                'message' => 'Sincronizacion de monitoreos completada.',
                'stats' => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al sincronizar monitoreos: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==================== SINCRONIZAR RESULTADOS IN-SITU ====================
    if ($action === 'sincronizar_insitu') {
        try {
            $id_proyecto = $_POST['id_proyecto'] ?? null;
            if (!$id_proyecto) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID Proyecto no especificado']);
                exit;
            }

            $pdoPg = ConexionPostgreSQL::conectar();
            if (!$pdoPg) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo conectar a PostgreSQL.']);
                exit;
            }

            $sincModel = new SincronizacionMonitoreoModel($conn);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;
            $resultado = $sincModel->sincronizarInSitu($pdoPg, $id_proyecto, $usuario_id);

            echo json_encode([
                'success' => true,
                'message' => 'Sincronizacion In-Situ completada.',
                'stats' => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al sincronizar in-situ: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==================== ASIGNAR PRODUCTO (PAQUETE) LABORATORIO ====================
    if ($action === 'asignar_producto_laboratorio') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $id_muestra = $datos['id_muestra'] ?? null;
            $id_producto = $datos['id_producto'] ?? null;
            $usuario_id = $_SESSION['usuario_id'] ?? 1;

            if (!$id_muestra || !$id_producto) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
                exit;
            }

            sqlsrv_begin_transaction($conn);

            // 1. Obtener nombre del producto
            $sqlProd = "SELECT Nombre_Comercial FROM laboratorio.Producto_Venta WHERE Id_Producto = ?";
            $stmtProd = sqlsrv_query($conn, $sqlProd, [$id_producto]);
            $prod = sqlsrv_fetch_array($stmtProd, SQLSRV_FETCH_ASSOC);
            if (!$prod) throw new Exception("Producto no encontrado");

            // 2. Insertar Muestra_Producto
            $sqlMP = "INSERT INTO laboratorio.Muestra_Producto (Id_Muestra, Id_Producto_Venta, Usuario_Creacion) VALUES (?, ?, ?); SELECT SCOPE_IDENTITY() as id;";
            $stmtMP = sqlsrv_query($conn, $sqlMP, [$id_muestra, $id_producto, $usuario_id]);
            sqlsrv_next_result($stmtMP);
            $rowMP = sqlsrv_fetch_array($stmtMP, SQLSRV_FETCH_ASSOC);
            $id_muestra_producto = $rowMP['id'];

            // 3. Obtener servicios del producto
            $sqlServ = "SELECT Id_Servicio FROM laboratorio.Producto_Servicio WHERE Id_Producto = ? AND Activo = 1";
            $stmtServ = sqlsrv_query($conn, $sqlServ, [$id_producto]);
            
            while ($serv = sqlsrv_fetch_array($stmtServ, SQLSRV_FETCH_ASSOC)) {
                $id_servicio = $serv['Id_Servicio'];
                
                // Verificar si ya tiene este servicio
                $sqlCheckServ = "SELECT 1 FROM laboratorio.Solicitud_Analisis WHERE Id_Muestra = ? AND Id_Servicio = ? AND Activo = 1";
                $stmtCheck = sqlsrv_query($conn, $sqlCheckServ, [$id_muestra, $id_servicio]);
                if (sqlsrv_fetch_array($stmtCheck)) continue;

                // Crear Solicitud_Analisis
                $sqlSol = "INSERT INTO laboratorio.Solicitud_Analisis (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion) VALUES (?, ?, 'En Análisis', GETDATE(), ?); SELECT SCOPE_IDENTITY() as id;";
                $stmtSol = sqlsrv_query($conn, $sqlSol, [$id_muestra, $id_servicio, $usuario_id]);
                sqlsrv_next_result($stmtSol);
                $rowSol = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                $id_solicitud = $rowSol['id'];

                // Crear Resultado_Analisis (blancos)
                $sqlParam = "SELECT Id_Parametro FROM laboratorio.Parametro_Analisis WHERE Id_Servicio = ? AND Activo = 1";
                $stmtParam = sqlsrv_query($conn, $sqlParam, [$id_servicio]);
                
                // Obtener una normativa base (si aplica)
                $sqlNorm = "SELECT TOP 1 Id_Normativa FROM laboratorio.Limite_Legal ll 
                            INNER JOIN laboratorio.Parametro_Analisis pa ON pa.Id_Parametro = ll.Id_Parametro
                            WHERE pa.Id_Servicio = ? AND ll.Activo = 1";
                $stmtNorm = sqlsrv_query($conn, $sqlNorm, [$id_servicio]);
                $norm = sqlsrv_fetch_array($stmtNorm, SQLSRV_FETCH_ASSOC);
                $id_normativa = $norm ? $norm['Id_Normativa'] : null;

                while ($param = sqlsrv_fetch_array($stmtParam, SQLSRV_FETCH_ASSOC)) {
                    $id_param = $param['Id_Parametro'];
                    $sqlRes = "INSERT INTO laboratorio.Resultado_Analisis (Id_Solicitud_Analisis, Id_Parametro, Id_Normativa_Aplicada, Usuario_Creacion) VALUES (?, ?, ?, ?)";
                    sqlsrv_query($conn, $sqlRes, [$id_solicitud, $id_param, $id_normativa, $usuario_id]);
                }
            }

            // 4. Actualizar Tipo_Servicio de la muestra
            // Obtenemos el actual
            $sqlGetTipo = "SELECT Tipo_Servicio FROM laboratorio.Muestra_Lab WHERE Id_Muestra = ?";
            $stmtGetTipo = sqlsrv_query($conn, $sqlGetTipo, [$id_muestra]);
            $rowTipo = sqlsrv_fetch_array($stmtGetTipo, SQLSRV_FETCH_ASSOC);
            $tipoActual = $rowTipo['Tipo_Servicio'];
            
            $nuevoTipo = $prod['Nombre_Comercial'];
            if ($tipoActual && strpos($tipoActual, 'In-Situ') !== false) {
                $nuevoTipo = $tipoActual . ' + ' . $nuevoTipo;
            }

            $sqlUpd = "UPDATE laboratorio.Muestra_Lab SET Tipo_Servicio = ?, Estado = 'En Analisis', Fecha_Analisis = GETDATE() WHERE Id_Muestra = ?";
            sqlsrv_query($conn, $sqlUpd, [$nuevoTipo, $id_muestra]);

            sqlsrv_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Paquete asignado correctamente']);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==================== EXPORTAR RESULTADOS A POSTGRESQL ====================
    if ($action === 'exportar_resultados_pg') {
        try {
            $id_proyecto = $_POST['id_proyecto'] ?? null;
            if (!$id_proyecto) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID Proyecto no especificado']);
                exit;
            }

            $pdoPg = ConexionPostgreSQL::conectar();
            if (!$pdoPg) throw new Exception("No se pudo conectar a PostgreSQL.");

            // Validar proyecto
            $sqlProy = "SELECT Estado, Es_Pozos FROM laboratorio.Proyecto_Monitoreo WHERE Id_Proyecto = ?";
            $stmtProy = sqlsrv_query($conn, $sqlProy, [$id_proyecto]);
            $proy = sqlsrv_fetch_array($stmtProy, SQLSRV_FETCH_ASSOC);
            if (!$proy) throw new Exception("Proyecto no encontrado");
            if ($proy['Estado'] !== 'Terminado' && $proy['Estado'] !== 'Finalizado') {
                // throw new Exception("El proyecto debe estar Terminado/Finalizado para exportar.");
                // Permitiremos exportar los que ya estén listos, pero preferiblemente validarlo.
            }

            // Obtener mapeo de parámetros para calidad_agua_laboratorio
            $sqlParam = "SELECT Id_Parametro, Posgre_Nombre FROM laboratorio.Parametro_Analisis 
                         WHERE Posgre_Tabla = 'calidad_agua_laboratorio' AND Posgre_Nombre IS NOT NULL AND Activo = 1";
            $stmtParam = sqlsrv_query($conn, $sqlParam);
            $mapaParams = [];
            while ($rowP = sqlsrv_fetch_array($stmtParam, SQLSRV_FETCH_ASSOC)) {
                $mapaParams[$rowP['Id_Parametro']] = $rowP['Posgre_Nombre'];
            }

            if (empty($mapaParams)) {
                throw new Exception("No hay parámetros configurados con mapeo a calidad_agua_laboratorio");
            }

            // Obtener muestras del proyecto (solo las que tienen Id_Medicion_PG para pozos)
            $sqlMuestras = "SELECT ml.Id_Muestra, ml.Id_Pozo, ml.Id_Medicion_PG, 
                            COALESCE(mpa.Numero_Muestra, ml.Id_Muestra) AS Numero_Muestra
                            FROM laboratorio.Muestra_Lab ml
                            LEFT JOIN laboratorio.Monitoreo_Pozo_Asignacion mpa ON mpa.Id_Proyecto = ml.Id_Proyecto AND mpa.Id_Pozo = ml.Id_Pozo
                            WHERE ml.Id_Proyecto = ? AND ml.Id_Medicion_PG IS NOT NULL AND ml.Activo = 1";
            $stmtMuestras = sqlsrv_query($conn, $sqlMuestras, [$id_proyecto]);
            
            $stats = ['insertados' => 0, 'actualizados' => 0];

            while ($muestra = sqlsrv_fetch_array($stmtMuestras, SQLSRV_FETCH_ASSOC)) {
                $id_muestra = $muestra['Id_Muestra'];
                $id_medicion = $muestra['Id_Medicion_PG'];
                $id_pozo = $muestra['Id_Pozo'];
                $orden = $muestra['Numero_Muestra'];

                // Obtener todos los resultados de laboratorio para esta muestra
                $sqlRes = "SELECT ra.Id_Parametro, ra.Valor_Hallado 
                           FROM laboratorio.Resultado_Analisis ra
                           INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
                           WHERE sa.Id_Muestra = ? AND ra.Valor_Hallado IS NOT NULL AND sa.Activo = 1";
                $stmtRes = sqlsrv_query($conn, $sqlRes, [$id_muestra]);
                
                $valoresToUpdate = [];
                while ($res = sqlsrv_fetch_array($stmtRes, SQLSRV_FETCH_ASSOC)) {
                    $id_param = $res['Id_Parametro'];
                    if (isset($mapaParams[$id_param])) {
                        $colPg = $mapaParams[$id_param];
                        $valoresToUpdate[$colPg] = $res['Valor_Hallado'];
                    }
                }

                if (!empty($valoresToUpdate)) {
                    // Verificar qué valores YA existen en PG para no sobrescribir
                    $stmtCheck = $pdoPg->prepare("SELECT * FROM " . PG_SCHEMA . ".calidad_agua_laboratorio WHERE id_laboratorio = ?");
                    $stmtCheck->execute([$id_medicion]);
                    $registroPG = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($registroPG) {
                        // Filtrar: solo enviar valores que están VACÍOS/NULL en PG
                        $valoresFiltrados = [];
                        foreach ($valoresToUpdate as $col => $val) {
                            $valorExistente = $registroPG[$col] ?? null;
                            if ($valorExistente === null || $valorExistente === '' || $valorExistente === '0') {
                                $valoresFiltrados[$col] = $val;
                            }
                        }

                        if (!empty($valoresFiltrados)) {
                            $stats['actualizados']++;

                            // Construir UPDATE dinámico (SIN tocar orden)
                            $setParts = [];
                            $paramsUpd = [];
                            foreach ($valoresFiltrados as $col => $val) {
                                $setParts[] = "$col = ?";
                                $paramsUpd[] = $val;
                            }
                            $paramsUpd[] = $id_medicion;

                            $sqlUpd = "UPDATE " . PG_SCHEMA . ".calidad_agua_laboratorio SET " . implode(", ", $setParts) . " WHERE id_laboratorio = ?";
                            $stmtUpd = $pdoPg->prepare($sqlUpd);
                            $stmtUpd->execute($paramsUpd);
                        }
                    }
                    // Si no existe en PG, omitir
                }
            }

            echo json_encode(['success' => true, 'message' => 'Resultados exportados correctamente a PostgreSQL', 'stats' => $stats, 'nota' => 'Solo se actualizan registros existentes en calidad_agua_laboratorio']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al exportar: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==================== LISTAR POZOS ====================
    if ($action === 'listar_pozos') {
        try {
            $draw   = intval($_POST['draw'] ?? 0);
            $start  = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $search = trim((string)($_POST['search']['value'] ?? ''));

            $filas    = $catastroModel->obtenerTodos($start, $length, $search);
            $total    = $catastroModel->contarTodos('');
            $filtrados= $search === '' ? $total : $catastroModel->contarTodos($search);

            $data = [];
            foreach ($filas as $row) {
                $idPozo = trim((string)($row['Id_Pozo'] ?? ''));
                $data[] = [
                    'id_pozo'          => $idPozo,
                    'codigo'           => trim((string)($row['codigo'] ?? '-')),
                    'valle'            => trim((string)($row['valle'] ?? '-')),
                    'ubicacion'        => trim((string)($row['ubicacion'] ?? '-')),
                    'propietario'      => trim((string)($row['propietario'] ?? '-')),
                    'tipopozo'         => trim((string)($row['tipopozo'] ?? '-')),
                    'coord_este'       => floatval($row['coord_este'] ?? 0),
                    'coord_norte'      => floatval($row['coord_norte'] ?? 0),
                    'fecha_sinc'       => isset($row['Fecha_Sincronizacion']) && $row['Fecha_Sincronizacion'] instanceof DateTime
                                         ? $row['Fecha_Sincronizacion']->format('d-m-Y H:i')
                                         : '-',
                    'accion'           => '<a class="btn btn-sm btn-primary" href="?module=laboratorio&action=pozos&subaction=historial_pozo&id_pozo=' . rawurlencode($idPozo) . '" title="Ver historial"><i class="ti ti-chart-line"></i></a>'
                ];
            }

            echo json_encode([
                'draw'            => $draw,
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtrados,
                'data'            => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'draw'            => intval($_POST['draw'] ?? 0),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== LISTAR POZOS PARA SELECT (geoportal / asignacion) ====================
    if ($action === 'listar_pozos_select') {
        try {
            $valle = trim((string)($_GET['valle'] ?? ''));
            if ($valle !== '') {
                $pozos = $catastroModel->obtenerPorValle($valle);
            } else {
                $pozos = $catastroModel->obtenerParaGeoportal();
            }
            echo json_encode(['success' => true, 'data' => $pozos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== OBTENER VALLES ====================
    if ($action === 'listar_valles') {
        try {
            $valles = $catastroModel->obtenerValles();
            echo json_encode(['success' => true, 'data' => $valles]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== GEOPORTAL: DATOS DE POZOS ====================
    if ($action === 'geoportal_pozos') {
        try {
            $valle = trim((string)($_GET['valle'] ?? ''));
            $pozos = $catastroModel->obtenerParaGeoportal($valle);

            $data = [];
            foreach ($pozos as $p) {
                $lat = null;
                $lng = null;

                // Intentar parsear WKB del geom
                $geomHex = trim((string)($p['geom'] ?? ''));
                if ($geomHex !== '') {
                    try {
                        $coords = wkbHexToLatLng($geomHex);
                        if ($coords) {
                            $lng = $coords[0];
                            $lat = $coords[1];
                        }
                    } catch (\Throwable $ex) {}
                }

                // Fallback: usar coord_este / coord_norte con conversion UTM
                if ($lat === null || $lng === null) {
                    $este  = floatval($p['coord_este'] ?? 0);
                    $norte = floatval($p['coord_norte'] ?? 0);
                    $zona  = intval($p['zona'] ?? 0) ?: 18;

                    if ($este != 0 && $norte != 0) {
                        try {
                            if (abs($norte) > 1000000) {
                                $coords = utmToLatLng($este, $norte, $zona);
                                $lat = $coords[1];
                                $lng = $coords[0];
                            } else {
                                $lng = $este;
                                $lat = $norte;
                            }
                        } catch (\Throwable $ex) {
                            $lng = $este;
                            $lat = $norte;
                        }
                    }
                }

                $data[] = [
                    'Id_Pozo'      => trim((string)($p['Id_Pozo'] ?? '')),
                    'codigo'       => trim((string)($p['codigo'] ?? '')),
                    'valle'        => trim((string)($p['valle'] ?? '')),
                    'ubicacion'    => trim((string)($p['ubicacion'] ?? '')),
                    'propietario'  => trim((string)($p['propietario'] ?? '')),
                    'tipopozo'     => trim((string)($p['tipopozo'] ?? '')),
                    'coord_este'   => floatval($p['coord_este'] ?? 0),
                    'coord_norte'  => floatval($p['coord_norte'] ?? 0),
                    'zona'         => intval($p['zona'] ?? 0),
                    'lat'          => $lat,
                    'lng'          => $lng
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Convierte WKB hex a [lng, lat]
     */
function wkbHexToLatLng($hex) {
        if (!function_exists('hex2bin')) return null;
        $hex = str_replace(' ', '', $hex);
        if (strlen($hex) < 42) return null;
        $bin = @hex2bin($hex);
        if ($bin === false || strlen($bin) < 21) return null;

        $order = unpack('C', $bin)[1]; // 01 = little endian

        $pos = 1;
        $type = $order === 1
            ? unpack('V', substr($bin, $pos, 4))[1]
            : unpack('N', substr($bin, $pos, 4))[1];
        $pos += 4;

        // Solo puntos (type=1) y puntos 3D (type=0x80000001)
        $is3D = ($type & 0x80000000) !== 0;
        $type = $type & 0x7FFFFFFF;

        if ($type !== 1) return null;

        if ($order === 1) {
            $x = unpack('e', substr($bin, $pos, 8))[1]; $pos += 8;
            $y = unpack('e', substr($bin, $pos, 8))[1]; $pos += 8;
        } else {
            $x = unpack('E', substr($bin, $pos, 8))[1]; $pos += 8;
            $y = unpack('E', substr($bin, $pos, 8))[1]; $pos += 8;
        }

        return [$x, $y];
    }

    /**
     * Convierte UTM a [lng, lat] para hemisferio sur (Peru)
     */
function utmToLatLng($este, $norte, $zona) {
        $a = 6378137.0;
        $f = 1 / 298.257223563;
        $k0 = 0.9996;
        $e = sqrt(2 * $f - $f * $f);
        $e2 = $e * $e;
        $n = $f / (2 - $f);
        $n2 = $n * $n;
        $n3 = $n * $n2;
        $n4 = $n * $n3;

        $meridian = $zona * 6 - 183;

        // Hemisferio sur
        $norte = $norte - 10000000;
        $este  = $este - 500000;

        $M = $norte / $k0;

        $mu = $M / ($a * (1 - $e2 / 4 - 3 * $e2 * $e2 / 64 - 5 * $e2 * $e2 * $e2 / 256));

        $phi1 = $mu + (3 * $n / 2 - 27 * $n3 / 32) * sin(2 * $mu)
              + (21 * $n2 / 16 - 55 * $n4 / 32) * sin(4 * $mu)
              + (151 * $n3 / 96) * sin(6 * $mu);

        $sinPhi = sin($phi1);
        $cosPhi = cos($phi1);
        $tanPhi = tan($phi1);

        $nu = $a / sqrt(1 - $e2 * $sinPhi * $sinPhi);
        $rho = $a * (1 - $e2) / pow(1 - $e2 * $sinPhi * $sinPhi, 1.5);

        $D = $este / ($nu * $k0);

        $lat = $phi1 - ($tanPhi / (2 * $rho * $nu * $k0)) * $D * $D
             + ($tanPhi / (24 * $rho * $nu * $nu * $nu * $k0)) * (5 + 3 * $tanPhi * $tanPhi - 1 + 9 * $tanPhi * $tanPhi * $tanPhi * $tanPhi) * pow($D, 4);

        $lng = deg2rad($meridian) + ($D / $cosPhi)
             - (1 / (6 * $cosPhi)) * (1 + 2 * $tanPhi * $tanPhi + $tanPhi * $tanPhi * $tanPhi * $tanPhi - $tanPhi * $tanPhi) * pow($D / $nu, 3);

        $lat = rad2deg($lat);
        $lng = rad2deg($lng);

        return [$lng, $lat];
    }

    // ==================== LISTAR ASIGNACIONES PARA DATATABLE ====================
    if ($action === 'listar_asignaciones') {
        try {
            $draw        = intval($_POST['draw'] ?? 0);
            $start       = intval($_POST['start'] ?? 0);
            $length      = intval($_POST['length'] ?? 10);
            $search      = trim((string)($_POST['search']['value'] ?? ''));
            $id_proyecto = intval($_GET['id_proyecto'] ?? $_POST['id_proyecto'] ?? 0);

            if ($id_proyecto <= 0) {
                throw new Exception('ID de proyecto invalido');
            }

            $filas     = $asignacionModel->obtenerAsignacionesPorProyectoDataTable($id_proyecto, $start, $length, $search);
            $total     = $asignacionModel->contarAsignacionesPorProyecto($id_proyecto, true);
            $filtrados = $search === '' ? $total : $asignacionModel->contarAsignacionesPorProyecto($id_proyecto, true);

            $data = [];
            foreach ($filas as $row) {
                $numMuestra = intval($row['Numero_Muestra'] ?? 0);
                $idPozo     = trim((string)($row['Id_Pozo'] ?? ''));
                $esLab      = intval($row['Es_Analisis_Laboratorio'] ?? 0);

                $data[] = [
                    'numero_muestra'          => $numMuestra,
                    'id_pozo'                 => $idPozo,
                    'valle'                   => trim((string)($row['valle'] ?? '-')),
                    'ubicacion'               => trim((string)($row['ubicacion'] ?? '-')),
                    'tipopozo'                => trim((string)($row['tipopozo'] ?? '-')),
                    'es_laboratorio'          => $esLab
                        ? '<span class="badge bg-green-lt">Lab</span>'
                        : '<span class="badge bg-secondary-lt">In-Situ</span>',
                    'accion'                  => '<button type="button" class="btn btn-sm btn-outline-warning" title="Cambiar pozo (swap)" onclick="abrirSwapPozo(' . $numMuestra . ', \'' . htmlspecialchars($idPozo, ENT_QUOTES, 'UTF-8') . '\')"><i class="ti ti-arrows-exchange"></i></button>'
                ];
            }

            echo json_encode([
                'draw'            => $draw,
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtrados,
                'data'            => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'draw'            => intval($_POST['draw'] ?? 0),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage()
            ]);
        }
        exit;
    }

    // ==================== GUARDAR ASIGNACION (SWAP) ====================
    if ($action === 'guardar_asignacion') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $asignacionModel->guardarAsignacion([
                'Id_Proyecto'             => intval($datos['id_proyecto'] ?? 0),
                'Numero_Muestra'          => intval($datos['numero_muestra'] ?? 0),
                'Id_Pozo'                 => strtoupper(trim((string)($datos['id_pozo'] ?? ''))),
                'Es_Analisis_Laboratorio' => intval($datos['es_laboratorio'] ?? 0)
            ]);

            echo json_encode(['success' => true, 'message' => 'Asignacion guardada correctamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== AUTO ASIGNAR LABORATORIO ====================
    if ($action === 'auto_asignar_pozos_lab') {
        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!is_array($datos)) {
                $datos = $_POST;
            }

            $id_proyecto = intval($datos['id_proyecto'] ?? 0);
            $cantidad_lab = intval($datos['cantidad_lab'] ?? 0);
            $valle = trim((string)($datos['valle'] ?? ''));

            if ($id_proyecto <= 0 || $cantidad_lab <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos invalidos.']);
                exit;
            }

            $asignados = $asignacionModel->autoAsignarLaboratorio($id_proyecto, $cantidad_lab, $valle);

            echo json_encode(['success' => true, 'message' => "Se auto-asignaron $asignados pozos para laboratorio.", 'asignados' => $asignados]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== HABILITAR LABORATORIO PARA UNA MUESTRA ====================
    if ($action === 'habilitar_laboratorio') {
        try {
            $datos      = json_decode(file_get_contents('php://input'), true);
            $id_muestra = intval($datos['id_muestra'] ?? 0);
            $id_producto_lab = intval($datos['id_producto_lab'] ?? 0);
            $usuario_id = $_SESSION['usuario_id'] ?? 1;

            if ($id_muestra <= 0 || $id_producto_lab <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra o producto invalido.']);
                exit;
            }

            // Verificar que la muestra pertenece a un proyecto de pozos
            $sqlVer = "SELECT m.Id_Proyecto, m.Id_Pozo
                       FROM laboratorio.Muestra_Lab m
                       INNER JOIN laboratorio.Proyecto_Monitoreo pm ON m.Id_Proyecto = pm.Id_Proyecto
                       WHERE m.Id_Muestra = ? AND m.Activo = 1 AND pm.Es_Pozos = 1";
            $stmtVer = sqlsrv_query($conn, $sqlVer, [$id_muestra]);
            if ($stmtVer === false) {
                throw new Exception('Error al verificar muestra: ' . print_r(sqlsrv_errors(), true));
            }
            $rowVer = sqlsrv_fetch_array($stmtVer, SQLSRV_FETCH_ASSOC);
            if (!$rowVer) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La muestra no pertenece a un proyecto de pozos.']);
                exit;
            }

            // Verificar que la muestra tiene asignacion de laboratorio
            $sqlAsig = "SELECT TOP 1 1 FROM laboratorio.Monitoreo_Pozo_Asignacion
                        WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Es_Analisis_Laboratorio = 1 AND Activo = 1";
            $stmtAsig = sqlsrv_query($conn, $sqlAsig, [intval($rowVer['Id_Proyecto']), trim((string)$rowVer['Id_Pozo'])]);
            $rowAsig = sqlsrv_fetch_array($stmtAsig, SQLSRV_FETCH_ASSOC);
            if (!$rowAsig) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Este pozo no esta asignado para analisis de laboratorio.']);
                exit;
            }

            // Insertar Muestra_Producto con el producto "Laboratorio Pozos"
            $sqlMp = "INSERT INTO laboratorio.Muestra_Producto
                      (Id_Muestra, Id_Producto_Venta, Activo, Fecha_Creacion, Usuario_Creacion)
                      VALUES (?, ?, 1, GETDATE(), ?);
                      SELECT SCOPE_IDENTITY() AS id;";
            $stmtMp = sqlsrv_query($conn, $sqlMp, [$id_muestra, $id_producto_lab, $usuario_id]);
            if ($stmtMp === false) {
                throw new Exception('Error al agregar producto de laboratorio: ' . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_next_result($stmtMp);
            $rowMp = sqlsrv_fetch_array($stmtMp, SQLSRV_FETCH_ASSOC);
            $id_muestra_producto = intval($rowMp['id'] ?? 0);

            // Asegurar solicitudes y resultados (crear solo los que falten)
            $sqlServicios = "SELECT DISTINCT ps.Id_Servicio
                             FROM laboratorio.Producto_Servicio ps
                             WHERE ps.Id_Producto = ? AND ps.Activo = 1";
            $stmtServ = sqlsrv_query($conn, $sqlServicios, [$id_producto_lab]);
            if ($stmtServ === false) {
                throw new Exception('Error al obtener servicios del producto: ' . print_r(sqlsrv_errors(), true));
            }

            $servicios = [];
            while ($rowServ = sqlsrv_fetch_array($stmtServ, SQLSRV_FETCH_ASSOC)) {
                $servicios[] = intval($rowServ['Id_Servicio']);
            }

            $solicitudes_creadas = 0;
            $resultados_creados  = 0;

            foreach ($servicios as $id_servicio) {
                // Verificar si ya existe solicitud para este servicio
                $sqlSolEx = "SELECT TOP 1 Id_Solicitud_Analisis
                            FROM laboratorio.Solicitud_Analisis
                            WHERE Id_Muestra = ? AND Id_Servicio = ? AND Activo = 1";
                $stmtSolEx = sqlsrv_query($conn, $sqlSolEx, [$id_muestra, $id_servicio]);
                $rowSolEx  = sqlsrv_fetch_array($stmtSolEx, SQLSRV_FETCH_ASSOC);

                if ($rowSolEx) {
                    continue; // Ya existe, no hacer nada (los parametros ya estan)
                }

                // Crear solicitud
                $sqlSolIns = "INSERT INTO laboratorio.Solicitud_Analisis
                              (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion)
                              VALUES (?, ?, 'En Analisis', GETDATE(), ?, 1, GETDATE());
                              SELECT SCOPE_IDENTITY() AS id;";
                $stmtSolIns = sqlsrv_query($conn, $sqlSolIns, [$id_muestra, $id_servicio, $usuario_id]);
                if ($stmtSolIns === false) {
                    throw new Exception('Error al crear solicitud: ' . print_r(sqlsrv_errors(), true));
                }
                sqlsrv_next_result($stmtSolIns);
                $rowNuevaSol = sqlsrv_fetch_array($stmtSolIns, SQLSRV_FETCH_ASSOC);
                $id_solicitud = intval($rowNuevaSol['id'] ?? 0);
                $solicitudes_creadas++;

                // Crear resultados en blanco para cada parametro del servicio
                $sqlParams = "SELECT Id_Parametro FROM laboratorio.Parametro_Analisis
                              WHERE Id_Servicio = ? AND Activo = 1";
                $stmtParams = sqlsrv_query($conn, $sqlParams, [$id_servicio]);
                if ($stmtParams === false) {
                    throw new Exception('Error al obtener parametros: ' . print_r(sqlsrv_errors(), true));
                }

                while ($rowParam = sqlsrv_fetch_array($stmtParams, SQLSRV_FETCH_ASSOC)) {
                    $id_parametro = intval($rowParam['Id_Parametro'] ?? 0);
                    if ($id_parametro <= 0) continue;

                    $sqlResIns = "INSERT INTO laboratorio.Resultado_Analisis
                                  (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                                  VALUES (?, ?, NULL, ?, 1, GETDATE())";
                    $stmtRes = sqlsrv_query($conn, $sqlResIns, [$id_solicitud, $id_parametro, $usuario_id]);
                    if ($stmtRes === false) {
                        throw new Exception('Error al crear resultado: ' . print_r(sqlsrv_errors(), true));
                    }
                    $resultados_creados++;
                }
            }

            echo json_encode([
                'success'              => true,
                'message'              => 'Analisis de laboratorio habilitado.',
                'solicitudes_creadas'  => $solicitudes_creadas,
                'resultados_creados'   => $resultados_creados
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== OBTENER RESULTADOS DE UN POZO ====================
    if ($action === 'historial_pozo') {
        try {
            $id_pozo   = strtoupper(trim((string)($_GET['id_pozo'] ?? '')));
            $anio_desde = intval($_GET['anio_desde'] ?? 0);

            if ($id_pozo === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de pozo requerido.']);
                exit;
            }

            $pozoInfo = $catastroModel->obtenerPorId($id_pozo);
            if (!$pozoInfo) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Pozo no encontrado.']);
                exit;
            }

            $resultados = $catastroModel->obtenerResultadosInsituPorPozo($id_pozo, $anio_desde > 0 ? $anio_desde : null);

            echo json_encode([
                'success'    => true,
                'pozo'       => [
                    'id_pozo'       => trim((string)($pozoInfo['Id_Pozo'] ?? '')),
                    'codigo'        => trim((string)($pozoInfo['codigo'] ?? '')),
                    'valle'         => trim((string)($pozoInfo['valle'] ?? '')),
                    'ubicacion'     => trim((string)($pozoInfo['ubicacion'] ?? '')),
                    'propietario'   => trim((string)($pozoInfo['propietario'] ?? '')),
                    'coord_este'    => floatval($pozoInfo['coord_este'] ?? 0),
                    'coord_norte'   => floatval($pozoInfo['coord_norte'] ?? 0)
                ],
                'resultados'=> $resultados
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== MONITOREOS DE POZO ====================
    if ($action === 'monitoreos_pozo') {
        try {
            $id_pozo = strtoupper(trim((string)($_GET['id_pozo'] ?? '')));
            if ($id_pozo === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de pozo requerido.']);
                exit;
            }

            $sql = "SELECT 
                        pm.Nombre_Proyecto AS Proyecto,
                        ml.Fecha_Toma,
                        mpa.Orden,
                        mpa.Numero_Muestra,
                        ml.Estado,
                        ml.Id_Muestra,
                        (SELECT COUNT(*) FROM laboratorio.Resultado_Analisis ra 
                         INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis 
                         WHERE sa.Id_Muestra = ml.Id_Muestra AND ra.Activo = 1 AND sa.Activo = 1) AS Total_Parametros
                    FROM laboratorio.Muestra_Lab ml
                    INNER JOIN laboratorio.Proyecto_Monitoreo pm ON ml.Id_Proyecto = pm.Id_Proyecto
                    LEFT JOIN laboratorio.Monitoreo_Pozo_Asignacion mpa ON ml.Id_Asignacion = mpa.Id_Asignacion
                    WHERE ml.Id_Pozo = ? AND ml.Activo = 1
                    ORDER BY ml.Fecha_Toma DESC, mpa.Orden";
            
            $stmt = sqlsrv_query($conn, $sql, [$id_pozo]);
            if ($stmt === false) {
                throw new Exception('Error al consultar monitoreos: ' . print_r(sqlsrv_errors(), true));
            }

            $monitoreos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $monitoreos[] = $row;
            }

            echo json_encode([
                'success' => true,
                'monitoreos' => $monitoreos
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== DETALLE MUESTRA PARA MODAL ====================
    if ($action === 'detalle_muestra') {
        try {
            $id_muestra = intval($_GET['id_muestra'] ?? 0);
            if ($id_muestra <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de muestra requerido.']);
                exit;
            }

            // Datos de la muestra
            $sqlM = "SELECT ml.Id_Muestra, ml.Id_Pozo, ml.Valle, ml.Estado, ml.Tipo_Servicio,
                            ml.Fecha_Toma, ml.Fecha_Recepcion, ml.Observacion_Muestra,
                            COALESCE(NULLIF(LTRIM(RTRIM(CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno))), ''), '-') AS Agricultor,
                            CASE WHEN ml.Es_Pozo = 1 THEN 'Pozo' WHEN ml.Id_Cliente > 0 THEN 'Agricultor' ELSE 'General' END AS TipoMuestra
                     FROM laboratorio.Muestra_Lab ml
                     LEFT JOIN laboratorio.Cliente c ON ml.Id_Cliente = c.Id_Cliente
                     WHERE ml.Id_Muestra = ? AND ml.Activo = 1";
            $stmtM = sqlsrv_query($conn, $sqlM, [$id_muestra]);
            $muestra = $stmtM ? sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC) : null;
            if (!$muestra) {
                echo json_encode(['success' => false, 'message' => 'Muestra no encontrada.']);
                exit;
            }

            // Datos del pozo (si tiene)
            $pozo = null;
            $id_pozo = trim((string)($muestra['Id_Pozo'] ?? ''));
            if ($id_pozo !== '') {
                $stmtP = sqlsrv_query($conn, "SELECT * FROM laboratorio.Catastro_Pozo WHERE Id_Pozo = ?", [$id_pozo]);
                $pozo = $stmtP ? sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC) : null;
            }

            // Resultados
            $sqlR = "SELECT pa.Nombre AS Parametro, pa.Categoria, pa.Unidad_Medida, ra.Valor_Hallado
                     FROM laboratorio.Resultado_Analisis ra
                     INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                     INNER JOIN laboratorio.Parametro_Analisis pa ON ra.Id_Parametro = pa.Id_Parametro
                     WHERE sa.Id_Muestra = ? AND ra.Activo = 1 AND sa.Activo = 1
                     ORDER BY pa.Categoria, pa.Nombre";
            $stmtR = sqlsrv_query($conn, $sqlR, [$id_muestra]);
            $resultados = [];
            while ($r = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = $r;
            }

            echo json_encode([
                'success' => true,
                'muestra' => $muestra,
                'pozo' => $pozo,
                'resultados' => $resultados
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== IMPORTAR DATOS HISTÓRICOS DESDE POSTGRESQL ====================
    if ($action === 'importar_historicos') {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        ini_set('log_errors', 1);
        ini_set('error_log', 'd:\SISTEMAS\gestionTI2026\pozo_import.log');

        try {
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception('Usuario no autenticado.');
            }
            $usuario_id = intval($_SESSION['usuario_id']);

            // RESETEAR DATA PREVIA ANTES DE IMPORTAR
            $resetSqls = [
                "DELETE FROM laboratorio.Resultado_Analisis WHERE Id_Solicitud_Analisis IN (SELECT Id_Solicitud_Analisis FROM laboratorio.Solicitud_Analisis WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1 OR Tipo_Servicio = 'Analisis Historico'))",
                "DELETE FROM laboratorio.Solicitud_Analisis WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1 OR Tipo_Servicio = 'Analisis Historico')",
                "DELETE FROM laboratorio.Muestra_Producto WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1 OR Tipo_Servicio = 'Analisis Historico')",
                "DELETE FROM laboratorio.Detalle_Agua WHERE Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1 OR Tipo_Servicio = 'Analisis Historico')",
                "DELETE FROM laboratorio.Monitoreo_Pozo_Asignacion WHERE Id_Proyecto IN (SELECT Id_Proyecto FROM laboratorio.Proyecto_Monitoreo WHERE Es_Pozos = 1)",
                "DELETE FROM laboratorio.Muestra_Lab WHERE Es_Pozo = 1 OR Tipo_Servicio = 'Analisis Historico'",
                "DELETE FROM laboratorio.Proyecto_Detalle_Analisis WHERE Id_Proyecto IN (SELECT Id_Proyecto FROM laboratorio.Proyecto_Monitoreo WHERE Es_Pozos = 1)",
                "DELETE FROM laboratorio.Proyecto_Monitoreo WHERE Es_Pozos = 1",
                "DBCC CHECKIDENT ('laboratorio.Resultado_Analisis', RESEED)",
                "DBCC CHECKIDENT ('laboratorio.Solicitud_Analisis', RESEED)",
                "DBCC CHECKIDENT ('laboratorio.Muestra_Producto', RESEED)",
                "DBCC CHECKIDENT ('laboratorio.Muestra_Lab', RESEED)",
                "DBCC CHECKIDENT ('laboratorio.Proyecto_Monitoreo', RESEED)",
                "DBCC CHECKIDENT ('laboratorio.Detalle_Agua', RESEED)",
                "DBCC CHECKIDENT ('laboratorio.Proyecto_Detalle_Analisis', RESEED)",
                "DBCC CHECKIDENT ('laboratorio.Monitoreo_Pozo_Asignacion', RESEED)"
            ];
            foreach ($resetSqls as $sqlReset) {
                $stmtReset = sqlsrv_query($conn, $sqlReset);
                if ($stmtReset === false) {
                    $errors = sqlsrv_errors();
                    $isOnlyWarning = true;
                    if ($errors != null) {
                        foreach ($errors as $error) {
                            if ($error['SQLSTATE'] !== '01000' && $error['code'] != 7997) {
                                $isOnlyWarning = false;
                                break;
                            }
                        }
                    } else {
                        $isOnlyWarning = false;
                    }
                    if (!$isOnlyWarning) {
                        throw new Exception("Error al resetear BD: " . print_r($errors, true));
                    }
                }
            }

            $pdoPg = ConexionPostgreSQL::conectar();
            if (!$pdoPg) throw new Exception("No se pudo conectar a PostgreSQL.");

            $stats = ['proyectos' => 0, 'muestras' => 0, 'resultados' => 0, 'errores' => []];

            // 1. Obtener cliente CHAVIMOCHIC (o crearlo)
            $sqlCli = "SELECT Id_Cliente FROM laboratorio.Cliente WHERE Razon_Social LIKE '%CHAVIMOCHIC%' AND Activo = 1";
            $stmtCli = sqlsrv_query($conn, $sqlCli);
            $rowCli = $stmtCli !== false ? sqlsrv_fetch_array($stmtCli, SQLSRV_FETCH_ASSOC) : null;
            if (!$rowCli) {
                $sqlInsCli = "INSERT INTO laboratorio.Cliente (Razon_Social, RUC, Activo, Usuario_Creacion, Fecha_Creacion) 
                              VALUES ('CHAVIMOCHIC', '20146030971', 1, ?, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                $stmtInsCli = sqlsrv_query($conn, $sqlInsCli, [$usuario_id]);
                if ($stmtInsCli !== false) {
                    sqlsrv_next_result($stmtInsCli);
                    $rowCli = sqlsrv_fetch_array($stmtInsCli, SQLSRV_FETCH_ASSOC);
                    $id_cliente = $rowCli['id'] ?? 1;
                } else {
                    $id_cliente = 1;
                }
            } else {
                $id_cliente = $rowCli['Id_Cliente'];
            }

            // 2. Obtener mapeo de parámetros: Posgre_Nombre → Id_Parametro + Id_Servicio
            $sqlMapeo = "SELECT pa.Id_Parametro, pa.Posgre_Nombre, pa.Id_Servicio 
                         FROM laboratorio.Parametro_Analisis pa 
                         WHERE pa.Posgre_Tabla = 'calidad_agua_laboratorio' AND pa.Posgre_Nombre IS NOT NULL AND pa.Activo = 1";
            $stmtMapeo = sqlsrv_query($conn, $sqlMapeo);
            $mapaParametros = []; // col_pg => ['Id_Parametro' => X, 'Id_Servicio' => Y]
            if ($stmtMapeo !== false) {
                while ($rowM = sqlsrv_fetch_array($stmtMapeo, SQLSRV_FETCH_ASSOC)) {
                    $mapaParametros[$rowM['Posgre_Nombre']] = [
                        'Id_Parametro' => $rowM['Id_Parametro'],
                        'Id_Servicio' => $rowM['Id_Servicio']
                    ];
                }
            }

            if (empty($mapaParametros)) {
                throw new Exception("No hay parámetros mapeados a calidad_agua_laboratorio. Ejecute setup_servicios_paquetes.php primero.");
            }

            // 3. Obtener paquete de venta
            $sqlPaq = "SELECT TOP 1 Id_Producto FROM laboratorio.Producto_Venta WHERE Activo = 1 ORDER BY Id_Producto";
            $stmtPaq = sqlsrv_query($conn, $sqlPaq);
            $rowPaq = $stmtPaq !== false ? sqlsrv_fetch_array($stmtPaq, SQLSRV_FETCH_ASSOC) : null;
            $id_paquete = $rowPaq ? $rowPaq['Id_Producto'] : null;

            // 4. Leer datos históricos de PG agrupados por monitoreo y valle
            $sqlPgHist = "SELECT pm.monitoreo, pc.valle, MIN(pm.fechamonitoreo) AS fecha_inicio
                          FROM " . PG_SCHEMA . ".calidad_agua_laboratorio cal
                          INNER JOIN " . PG_SCHEMA . ".pozos_monitoreo pm ON cal.id_medicion = pm.id_medicion
                          LEFT JOIN " . PG_SCHEMA . ".pozos_catastro pc ON pm.id_pozo = pc.id_pozo
                          WHERE pm.monitoreo IS NOT NULL AND TRIM(pm.monitoreo) <> ''
                          GROUP BY pm.monitoreo, pc.valle
                          ORDER BY fecha_inicio";
            $stmtPgHist = $pdoPg->query($sqlPgHist);
            $monitoreos_hist = $stmtPgHist->fetchAll(PDO::FETCH_ASSOC);

            foreach ($monitoreos_hist as $mon) {
                $monitoreo_raw = trim($mon['monitoreo']);
                $fecha_inicio = $mon['fecha_inicio'] ? date('Y-m-d', strtotime($mon['fecha_inicio'])) : date('Y-m-d');
                $valle = strtoupper(trim($mon['valle'] ?: 'CHICAMA'));

                $nombre_proyecto = $valle . ' - ' . strtoupper($monitoreo_raw);

                // Calcular temporada
                $mes = intval(date('n', strtotime($fecha_inicio)));
                $anio = date('Y', strtotime($fecha_inicio));
                $temporada = ($mes <= 6) ? ($anio . '-01') : ($anio . '-02');

                // Verificar si ya existe proyecto con ese nombre
                $sqlCheck = "SELECT Id_Proyecto FROM laboratorio.Proyecto_Monitoreo WHERE Nombre_Proyecto = ? AND Activo = 1";
                $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$nombre_proyecto]);
                $rowCheck = $stmtCheck !== false ? sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) : null;

                if ($rowCheck) {
                    // Ya existe, saltar
                    continue;
                }

                // Crear proyecto
                try {
                    $sqlInsProy = "INSERT INTO laboratorio.Proyecto_Monitoreo 
                        (Nombre_Proyecto, Valle, Temporada, Fecha_Inicio, Tipo_Muestra, Uso_Agua, Fuente_Agua, Nivel_Agua, 
                         Es_Control_Calidad, Es_Drene, Es_Pozos, Id_Responsable, Estado, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, 'Agua', 'Otros', 'Subterráneo', NULL, 0, 0, 1, ?, 'Finalizado', ?, 1, GETDATE());
                        SELECT SCOPE_IDENTITY() AS id;";
                    $stmtProy = sqlsrv_query($conn, $sqlInsProy, [$nombre_proyecto, $valle, $temporada, $fecha_inicio, $usuario_id, $usuario_id]);
                    if ($stmtProy === false) throw new Exception("No se pudo crear el proyecto: " . print_r(sqlsrv_errors(), true));
                    sqlsrv_next_result($stmtProy);
                    $rowProy = sqlsrv_fetch_array($stmtProy, SQLSRV_FETCH_ASSOC);
                    $id_proyecto = intval($rowProy['id'] ?? 0);
                    $stats['proyectos']++;
                    
                    // No need for a global transaction since we are making it resumable
                } catch (Exception $e) {
                    $stats['errores'][] = "Error en proyecto '$nombre_proyecto': " . $e->getMessage();
                    continue; // Skip project on error
                }

                if ($id_proyecto <= 0) throw new Exception("No se obtuvo ID para proyecto '$nombre_proyecto'");

                // Obtener todas las filas de calidad_agua_laboratorio para este monitoreo y valle
                $sqlDatos = "SELECT cal.*, pm.id_pozo, pm.id_medicion, pm.fechamonitoreo 
                             FROM " . PG_SCHEMA . ".calidad_agua_laboratorio cal
                             INNER JOIN " . PG_SCHEMA . ".pozos_monitoreo pm ON cal.id_medicion = pm.id_medicion
                             LEFT JOIN " . PG_SCHEMA . ".pozos_catastro pc ON pm.id_pozo = pc.id_pozo
                             WHERE pm.monitoreo = ? AND COALESCE(UPPER(TRIM(pc.valle)), 'CHICAMA') = ?
                             ORDER BY cal.orden, pm.id_pozo";
                $stmtDatos = $pdoPg->prepare($sqlDatos);
                $stmtDatos->execute([$monitoreo_raw, $valle]);
                $filas_lab = $stmtDatos->fetchAll(PDO::FETCH_ASSOC);
                
                $num_muestras = count($filas_lab);

                // Insertar Proyecto_Detalle_Analisis
                if ($id_paquete && $num_muestras > 0) {
                    $sqlCheckPDA = "SELECT 1 FROM laboratorio.Proyecto_Detalle_Analisis WHERE Id_Proyecto = ? AND Id_Producto_Venta = ?";
                    $stmtCheckPDA = sqlsrv_query($conn, $sqlCheckPDA, [$id_proyecto, $id_paquete]);
                    if ($stmtCheckPDA !== false && !sqlsrv_has_rows($stmtCheckPDA)) {
                        $sqlInsPDA = "INSERT INTO laboratorio.Proyecto_Detalle_Analisis 
                                      (Id_Proyecto, Id_Producto_Venta, Cantidad_Planificada, Activo, Fecha_Creacion, Usuario_Creacion)
                                      VALUES (?, ?, ?, 1, GETDATE(), ?)";
                        sqlsrv_query($conn, $sqlInsPDA, [$id_proyecto, $id_paquete, $num_muestras, $usuario_id]);
                    }
                }

                $num_muestra = 0;
                $ultimo_orden = null;
                $contador_por_orden = [];
                foreach ($filas_lab as $fila) {
                    $id_pozo = strtoupper(trim($fila['id_pozo'] ?? ''));
                    $id_medicion = $fila['id_medicion'];
                    $fecha_toma = $fila['fechamonitoreo'] ?? $fecha_inicio;
                    $orden = intval($fila['orden'] ?? 0);

                    if (empty($id_pozo)) continue;

                    // Numero_Muestra secuencial por (proyecto, orden)
                    $keyOrden = $orden;
                    if (!isset($contador_por_orden[$keyOrden])) {
                        $contador_por_orden[$keyOrden] = 1;
                    }
                    $mi_numero_muestra = $contador_por_orden[$keyOrden];
                    $contador_por_orden[$keyOrden]++;

                    sqlsrv_begin_transaction($conn); // Start transaction per pozo
                    try {
                        // Insertar asignación de pozo CON ORDEN
                        $sqlAsig = "IF NOT EXISTS (SELECT 1 FROM laboratorio.Monitoreo_Pozo_Asignacion WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Orden = ?)
                                    INSERT INTO laboratorio.Monitoreo_Pozo_Asignacion 
                                    (Id_Proyecto, Numero_Muestra, Id_Pozo, Orden, Es_Analisis_Laboratorio, Activo, Fecha_Creacion, Usuario_Creacion)
                                    VALUES (?, ?, ?, ?, 1, 1, GETDATE(), ?)";
                        $stmtAsig = sqlsrv_query($conn, $sqlAsig, [$id_proyecto, $id_pozo, $orden, $id_proyecto, $mi_numero_muestra, $id_pozo, $orden, $usuario_id]);
                        if ($stmtAsig === false) {
                            $stats['errores'][] = "Error al asignar pozo $id_pozo: " . print_r(sqlsrv_errors(), true);
                        }

                        // Obtener coordenadas del catastro
                        $sqlCoord = "SELECT coord_este, coord_norte FROM laboratorio.Catastro_Pozo WHERE Id_Pozo = ?";
                        $stmtCoord = sqlsrv_query($conn, $sqlCoord, [$id_pozo]);
                        $rowCoord = $stmtCoord !== false ? sqlsrv_fetch_array($stmtCoord, SQLSRV_FETCH_ASSOC) : null;
                        $coord_este = $rowCoord['coord_este'] ?? '';
                        $coord_norte = $rowCoord['coord_norte'] ?? '';

                        // Insertar muestra
                        $sqlCheckM = "SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Id_Proyecto = ? AND Id_Pozo = ? AND Activo = 1";
                        $stmtCheckM = sqlsrv_query($conn, $sqlCheckM, [$id_proyecto, $id_pozo]);
                        $rowCheckM = $stmtCheckM !== false ? sqlsrv_fetch_array($stmtCheckM, SQLSRV_FETCH_ASSOC) : null;
                        
                        $ya_existe = false;
                        if ($rowCheckM) {
                            $id_muestra = intval($rowCheckM['Id_Muestra']);
                            $ya_existe = true;
                        } else {
                            $obs = 'Importación histórica desde PostgreSQL. Medicion_PG: ' . $id_medicion;
                            $sqlMuestra = "INSERT INTO laboratorio.Muestra_Lab
                                (Id_Cliente, Id_Receptor, Id_Especialista, Id_Proyecto, Id_Pozo, Valle, 
                                 Eje_X, Eje_Y, Fecha_Recepcion, Fecha_Toma, Estado, Tipo_Servicio, 
                                 Observacion_Muestra, Es_Control_Calidad, Es_Drene, Es_Pozo, Fecha_Analisis, 
                                 Lab_Habilitado, Id_Medicion_PG, Usuario_Creacion, Activo, Fecha_Creacion)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Finalizado', 'Analisis Historico',
                                        ?, 0, 0, 1, ?, 1, ?, ?, 1, GETDATE());
                                SELECT SCOPE_IDENTITY() AS id;";
                            $stmtM = sqlsrv_query($conn, $sqlMuestra, [
                                $id_cliente, $usuario_id, $usuario_id, $id_proyecto, $id_pozo, $valle,
                                $coord_este, $coord_norte, $fecha_toma, $fecha_toma,
                                $obs, $fecha_toma, $id_medicion, $usuario_id
                            ]);
                            if ($stmtM === false) {
                                $stats['errores'][] = "Error muestra $id_pozo: " . print_r(sqlsrv_errors(), true);
                                continue;
                            }
                            sqlsrv_next_result($stmtM);
                            $rowMId = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC);
                            $id_muestra = intval($rowMId['id'] ?? 0);
                            $stats['muestras']++;

                            // Insertar Detalle_Agua
                            if ($id_muestra > 0) {
                                $sqlDetAgua = "INSERT INTO laboratorio.Detalle_Agua
                                    (Id_Muestra, Uso_Agua, Fuente_Agua, Cantidad_Muestra, Nivel_Agua, Usuario_Creacion, Activo, Fecha_Creacion)
                                    VALUES (?, 'Consumo Humano / Riego', 'Subterráneo', '1 Litro', ?, ?, 1, GETDATE())";
                                sqlsrv_query($conn, $sqlDetAgua, [$id_muestra, 'Pozo ' . $id_pozo, $usuario_id]);
                            }

                        }
                        if ($id_muestra <= 0) continue;
                        if ($ya_existe) {
                            sqlsrv_commit($conn);
                            continue;
                        }

                        // Insertar Muestra_Producto si hay paquete
                        $id_muestra_producto = null;
                        if ($id_paquete) {
                            $sqlMP = "INSERT INTO laboratorio.Muestra_Producto
                                (Id_Muestra, Id_Producto_Venta, Id_Cliente, Usuario_Creacion, Activo, Fecha_Creacion)
                                VALUES (?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                            $stmtMP = sqlsrv_query($conn, $sqlMP, [$id_muestra, $id_paquete, $id_cliente, $usuario_id]);
                            if ($stmtMP !== false) {
                                sqlsrv_next_result($stmtMP);
                                $rowMP = sqlsrv_fetch_array($stmtMP, SQLSRV_FETCH_ASSOC);
                                $id_muestra_producto = $rowMP['id'] ?? null;
                            }
                        }

                        // Crear solicitudes y resultados por cada servicio/parámetro que tenga valor
                        $servicios_procesados = [];
                        foreach ($mapaParametros as $col_pg => $info) {
                            $valor = $fila[$col_pg] ?? null;
                            $id_parametro = $info['Id_Parametro'];
                            $id_servicio = $info['Id_Servicio'];

                            if (!$id_servicio) continue;

                            // Crear solicitud si no existe aún para este servicio+muestra
                            if (!isset($servicios_procesados[$id_servicio])) {
                                $estado_sol = ($valor !== null && $valor !== '') ? 'Finalizado' : 'En Analisis';
                                $sqlSol = "INSERT INTO laboratorio.Solicitud_Analisis
                                    (Id_Muestra, Id_Servicio, Estado, Fecha_Asignacion, Usuario_Creacion, Activo, Fecha_Creacion)
                                    VALUES (?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                                $stmtSol = sqlsrv_query($conn, $sqlSol, [$id_muestra, $id_servicio, $estado_sol, $fecha_toma, $usuario_id]);
                                if ($stmtSol === false) {
                                    $stats['errores'][] = "Error solicitud serv=$id_servicio muestra=$id_muestra: " . print_r(sqlsrv_errors(), true);
                                    continue;
                                }
                                sqlsrv_next_result($stmtSol);
                                $rowSol = sqlsrv_fetch_array($stmtSol, SQLSRV_FETCH_ASSOC);
                                $servicios_procesados[$id_servicio] = intval($rowSol['id'] ?? 0);
                            }

                            $id_solicitud = $servicios_procesados[$id_servicio];
                            if ($id_solicitud <= 0) continue;

                            // Insertar resultado
                            $valor_hallado = ($valor !== null && $valor !== '') ? floatval($valor) : null;
                            $sqlRes = "INSERT INTO laboratorio.Resultado_Analisis
                                (Id_Solicitud_Analisis, Id_Parametro, Valor_Hallado, Usuario_Creacion, Activo, Fecha_Creacion)
                                VALUES (?, ?, ?, ?, 1, GETDATE())";
                            $stmtRes = sqlsrv_query($conn, $sqlRes, [$id_solicitud, $id_parametro, $valor_hallado, $usuario_id]);
                            if ($stmtRes !== false) {
                                $stats['resultados']++;
                                if ($stats['resultados'] % 50 === 0) {
                                    echo " ";
                                    if (ob_get_level() > 0) ob_flush();
                                    flush();
                                }
                            }
                        }
                        sqlsrv_commit($conn); // Commit per pozo
                    } catch (Exception $e) {
                        sqlsrv_rollback($conn);
                        $stats['errores'][] = "Error al procesar pozo '$id_pozo': " . $e->getMessage();
                    }
                } // fin foreach filas_lab
            } // fin foreach monitoreos_hist

            echo json_encode([
                'success' => true,
                'message' => 'Importación de datos históricos completada.',
                'stats' => $stats
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Accion no reconocida: ' . $action]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
