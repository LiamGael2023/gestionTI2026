<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
require_once '../../../../config/db.php';
require_once '../models/ProyectoModel.php';
require_once '../models/MuestraModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = Conexion::conectar();
    if ($conn === false) {
        throw new Exception('No se pudo conectar a la BD');
    }
    
    // Obtener acción de GET o POST
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    // Si es POST y JSON, parsear body
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $action = $data['action'] ?? null;
        $_POST = array_merge($_POST, $data);
    }

    // ===== OBTENER SERVICIOS/PRODUCTOS DISPONIBLES =====
    if ($action === 'obtenerServicios') {
        $scope = strtoupper(trim((string)($_GET['scope'] ?? $_POST['scope'] ?? 'INTERNO_GENERAL')));

        $sqlCheckVista = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Producto_Venta' AND COLUMN_NAME = 'Tipo_Vista'";
        $stmtCheckVista = sqlsrv_query($conn, $sqlCheckVista);
        $tieneTipoVista = $stmtCheckVista && sqlsrv_fetch_array($stmtCheckVista, SQLSRV_FETCH_ASSOC) !== null;

        $sql = "SELECT pv.Id_Producto, pv.Nombre_Comercial, pv.Tipo 
                FROM laboratorio.Producto_Venta pv 
                WHERE pv.Activo = 1";

        $params = [];
        if ($tieneTipoVista) {
            if ($scope === 'GENERAL' || $scope === 'EXTERNO') {
                $sql .= " AND pv.Tipo_Vista = ?";
                $params[] = 'GENERAL';
            } elseif ($scope === 'INTERNO') {
                $sql .= " AND pv.Tipo_Vista = ?";
                $params[] = 'INTERNO';
            } else {
                $sql .= " AND pv.Tipo_Vista IN (?, ?)";
                $params[] = 'INTERNO';
                $params[] = 'GENERAL';
            }
        }

        $sql .= "
                ORDER BY pv.Nombre_Comercial";
        
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error al obtener servicios: ' . sqlsrv_errors()[0]['message']);
        }

        $servicios = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $servicios[] = [
                'id' => intval($row['Id_Producto']),
                'nombre' => strval($row['Nombre_Comercial']),
                'tipo' => strval($row['Tipo'])
            ];
        }

        echo json_encode(['servicios' => $servicios, 'success' => true]);
        exit;
    }

    // ===== OBTENER CATEGORIAS DE LIMITES PARA EXPORTACION =====
    if ($action === 'obtenerCategoriasLimite') {
        $id_proyecto = intval($_GET['id_proyecto'] ?? $_POST['id_proyecto'] ?? 0);
        if ($id_proyecto <= 0) {
            throw new Exception('ID de proyecto inválido');
        }

        $sql = "SELECT DISTINCT LTRIM(RTRIM(l.Descripcion)) AS Descripcion
                FROM laboratorio.Limite_Legal l
                INNER JOIN laboratorio.Parametro_Analisis pa ON pa.Id_Parametro = l.Id_Parametro
                INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Servicio = pa.Id_Servicio
                INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                WHERE ml.Id_Proyecto = ?
                  AND ml.Activo = 1
                  AND sa.Activo = 1
                  AND pa.Activo = 1
                  AND l.Activo = 1
                  AND LTRIM(RTRIM(ISNULL(l.Descripcion, ''))) <> ''";

        $stmt = sqlsrv_query($conn, $sql, [$id_proyecto]);
        if ($stmt === false) {
            throw new Exception('Error al obtener categorias de limites: ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }

        $normalizar = function ($txt) {
            $txt = trim((string)$txt);
            $txt = str_replace(
                ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú', 'Ñ', 'ñ'],
                ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u', 'N', 'n'],
                $txt
            );
            $txt = preg_replace('/\s+/', ' ', $txt);
            return strtoupper($txt);
        };

        $prioridad = function ($txtNorm) {
            if (strpos($txtNorm, 'RIEGO') !== false) {
                return 1;
            }
            if (strpos($txtNorm, 'ANIMAL') !== false) {
                return 2;
            }
            if (strpos($txtNorm, 'HUMAN') !== false) {
                return 3;
            }
            return 9;
        };

        $mapa = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $descripcion = trim((string)($row['Descripcion'] ?? ''));
            if ($descripcion === '') {
                continue;
            }

            $key = $normalizar($descripcion);
            if (!isset($mapa[$key])) {
                $mapa[$key] = [
                    'descripcion' => $descripcion,
                    'key' => $key,
                    'prioridad' => $prioridad($key)
                ];
            }
        }

        $categorias = array_values($mapa);
        usort($categorias, function ($a, $b) {
            if (($a['prioridad'] ?? 9) !== ($b['prioridad'] ?? 9)) {
                return intval($a['prioridad'] ?? 9) <=> intval($b['prioridad'] ?? 9);
            }
            return strcasecmp((string)($a['descripcion'] ?? ''), (string)($b['descripcion'] ?? ''));
        });

        $categorias = array_map(function ($item) {
            return [
                'descripcion' => $item['descripcion']
            ];
        }, $categorias);

        echo json_encode([
            'success' => true,
            'categorias' => $categorias
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===== GUARDAR NUEVO PROYECTO =====
    if ($action === 'guardarProyecto') {
        $proyectoModel = new ProyectoModel($conn);
        $es_control_calidad = intval($_POST['es_control_calidad'] ?? 0) === 1 ? 1 : 0;
        $es_drene = intval($_POST['es_drene'] ?? 0) === 1 ? 1 : 0;
        
        $datos = [
            'Nombre_Proyecto' => $_POST['nombre_proyecto'] ?? null,
            'Valle' => $_POST['valle'] ?? null,
            'Temporada' => $_POST['temporada'] ?? null,
            'Fecha_Inicio' => $_POST['fecha_inicio'] ?? null,
            'Tipo_Muestra' => $_POST['tipo_muestra'] ?? null,
            'Uso_Agua' => $_POST['uso_agua'] ?? null,
            'Fuente_Agua' => $_POST['fuente_agua'] ?? null,
            'Nivel_Agua' => $_POST['nivel_agua'] ?? null,
            'Es_Control_Calidad' => $es_control_calidad,
            'Es_Drene' => $es_drene,
            'Id_Responsable' => isset($_POST['id_responsable']) ? intval($_POST['id_responsable']) : $_SESSION['usuario_id'],
            'Estado' => 'Planificado'
        ];

        if (!sqlsrv_begin_transaction($conn)) {
            throw new Exception('No se pudo iniciar transaccion para crear proyecto.');
        }

        try {
            $id_proyecto = $proyectoModel->guardar($datos);

            // Guardar detalles (servicios/productos)
            if (isset($_POST['servicios']) && is_array($_POST['servicios'])) {
                foreach ($_POST['servicios'] as $servicio) {
                    $id_producto = intval($servicio['id'] ?? 0);
                    $cantidad = intval($servicio['cantidad'] ?? 0);
                    if (($es_control_calidad || $es_drene) && $cantidad < 10) {
                        $cantidad = 10;
                    }
                    
                    if ($id_producto > 0 && $cantidad > 0) {
                        $proyectoModel->guardarDetalle($id_proyecto, $id_producto, $cantidad);
                    }
                }
            }

            if (!sqlsrv_commit($conn)) {
                throw new Exception('No se pudo confirmar la creacion del proyecto.');
            }
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        echo json_encode([
            'success' => true,
            'id_proyecto' => $id_proyecto,
            'mensaje' => 'Proyecto creado exitosamente'
        ]);
        exit;
    }

    // ===== OBTENER DETALLES DEL PROYECTO =====
    if ($action === 'obtenerDetalles') {
        $id_proyecto = intval($_GET['id'] ?? 0);
        if ($id_proyecto <= 0) {
            throw new Exception('ID de proyecto inválido');
        }

        $proyectoModel = new ProyectoModel($conn);
        $proyecto = $proyectoModel->obtenerPorId($id_proyecto);
        $detalles = $proyectoModel->obtenerDetalles($id_proyecto);

        if (!$proyecto) {
            throw new Exception('Proyecto no encontrado');
        }

        $analisis_iniciado = $proyectoModel->proyectoTieneAnalisisIniciado($id_proyecto);

        echo json_encode([
            'proyecto' => $proyecto,
            'detalles' => $detalles,
            'analisis_iniciado' => $analisis_iniciado,
            'puede_editar_cantidades' => !$analisis_iniciado
        ]);
        exit;
    }

    // ===== EDITAR PROYECTO =====
    if ($action === 'editarProyecto') {
        $id_proyecto = intval($_POST['id_proyecto'] ?? 0);
        if ($id_proyecto <= 0) {
            throw new Exception('ID de proyecto inválido');
        }

        $proyectoModel = new ProyectoModel($conn);
        $proyecto_actual = $proyectoModel->obtenerPorId($id_proyecto);
        if (!$proyecto_actual) {
            throw new Exception('Proyecto no encontrado');
        }

        $analisis_iniciado = $proyectoModel->proyectoTieneAnalisisIniciado($id_proyecto);

        $datos_actualizados = [
            'Id_Proyecto' => $id_proyecto,
            'Nombre_Proyecto' => $_POST['nombre_proyecto'] ?? $proyecto_actual['Nombre_Proyecto'],
            'Valle' => $_POST['valle'] ?? $proyecto_actual['Valle'],
            'Temporada' => $_POST['temporada'] ?? $proyecto_actual['Temporada'],
            'Fecha_Inicio' => $_POST['fecha_inicio'] ?? $proyecto_actual['Fecha_Inicio'],
            'Tipo_Muestra' => $_POST['tipo_muestra'] ?? $proyecto_actual['Tipo_Muestra'],
            'Uso_Agua' => $_POST['uso_agua'] ?? $proyecto_actual['Uso_Agua'],
            'Fuente_Agua' => $_POST['fuente_agua'] ?? $proyecto_actual['Fuente_Agua'],
            'Nivel_Agua' => $_POST['nivel_agua'] ?? $proyecto_actual['Nivel_Agua'],
            'Es_Control_Calidad' => isset($_POST['es_control_calidad'])
                ? (intval($_POST['es_control_calidad']) === 1 ? 1 : 0)
                : intval($proyecto_actual['Es_Control_Calidad'] ?? 0),
            'Es_Drene' => isset($_POST['es_drene'])
                ? (intval($_POST['es_drene']) === 1 ? 1 : 0)
                : intval($proyecto_actual['Es_Drene'] ?? 0),
            'Id_Responsable' => isset($_POST['id_responsable']) ? intval($_POST['id_responsable']) : intval($proyecto_actual['Id_Responsable']),
            'Estado' => $proyecto_actual['Estado'] ?? 'Planificado'
        ];

        if (!sqlsrv_begin_transaction($conn)) {
            throw new Exception('No se pudo iniciar transaccion para editar proyecto.');
        }

        try {
            $proyectoModel->guardar($datos_actualizados);

            if (isset($_POST['servicios']) && is_array($_POST['servicios'])) {
                $detalles_actuales = $proyectoModel->obtenerDetalles($id_proyecto);
                // mapa: id_producto => ['cantidad' => int, 'id_detalle' => int]
                $mapa_actual = [];
                foreach ($detalles_actuales as $detalle) {
                    $id_producto = intval($detalle['Id_Producto_Venta'] ?? 0);
                    if ($id_producto > 0) {
                        $mapa_actual[$id_producto] = [
                            'cantidad'   => intval($detalle['Cantidad_Planificada'] ?? 0),
                            'id_detalle' => intval($detalle['Id_Detalle_Proyecto'] ?? 0)
                        ];
                    }
                }

                $es_control_actualizado = intval($datos_actualizados['Es_Control_Calidad'] ?? 0) === 1;
                $es_drene_actualizado   = intval($datos_actualizados['Es_Drene'] ?? 0) === 1;
                $ids_enviados = [];

                foreach ($_POST['servicios'] as $servicio) {
                    $id_producto = intval($servicio['id'] ?? 0);
                    $cantidad    = intval($servicio['cantidad'] ?? 0);

                    if (($es_control_actualizado || $es_drene_actualizado) && $cantidad < 10) {
                        throw new Exception('Para proyectos de calidad/drenes, cada servicio debe tener al menos 10 muestras planificadas.');
                    }

                    if ($id_producto <= 0 || $cantidad <= 0) {
                        continue;
                    }

                    $ids_enviados[] = $id_producto;

                    if ($analisis_iniciado) {
                        if (!array_key_exists($id_producto, $mapa_actual)) {
                            throw new Exception('No se puede agregar nuevas ventas cuando el analisis ya ha iniciado.');
                        }
                        if ($mapa_actual[$id_producto]['cantidad'] !== $cantidad) {
                            throw new Exception('No se puede modificar la cantidad de muestras por venta cuando el analisis ya ha iniciado.');
                        }
                        continue;
                    }

                    // Upsert: actualiza o crea el detalle y sincroniza reserva de reactivos.
                    $proyectoModel->guardarDetalle($id_proyecto, $id_producto, $cantidad);
                }

                // Eliminar servicios que fueron quitados del proyecto (solo si análisis no inició)
                if (!$analisis_iniciado) {
                    foreach ($mapa_actual as $id_producto => $info) {
                        if (!in_array($id_producto, $ids_enviados, true)) {
                            $id_detalle = intval($info['id_detalle'] ?? 0);
                            if ($id_detalle > 0) {
                                $proyectoModel->eliminarDetalle($id_detalle);
                            }
                        }
                    }
                }
            }

            if (!sqlsrv_commit($conn)) {
                throw new Exception('No se pudo confirmar la edicion del proyecto.');
            }
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        echo json_encode([
            'success' => true,
            'analisis_iniciado' => $analisis_iniciado,
            'puede_editar_cantidades' => !$analisis_iniciado,
            'mensaje' => $analisis_iniciado
                ? 'Proyecto actualizado. Analisis iniciado: se conservaron las cantidades planificadas.'
                : 'Proyecto actualizado correctamente. Se sincronizo la cantidad reservada segun la nueva cantidad planificada.'
        ]);
        exit;
    }

    // ===== GENERAR MUESTRAS MASIVAS =====
    if ($action === 'generarMuestras') {
        $id_proyecto = intval($_POST['id_proyecto'] ?? 0);
        $fuentes_calidad = $_POST['fuentes_calidad'] ?? null;
        if (!is_array($fuentes_calidad)) {
            $fuentes_calidad = null;
        }
        $fuentes_drene = $_POST['fuentes_drene'] ?? null;
        if (!is_array($fuentes_drene)) {
            $fuentes_drene = null;
        }

        if ($id_proyecto <= 0) {
            throw new Exception('ID de proyecto inválido');
        }

        $proyectoModel = new ProyectoModel($conn);
        $muestraModel = new MuestraModel($conn);
        
        // Verificar detalles ANTES de cambiar estado
        $detalles_antes = $proyectoModel->obtenerDetalles($id_proyecto);
        
        // Cambiar estado a "En Progreso" (esto crea las muestras automáticamente)
        $id = $proyectoModel->guardar([
            'Id_Proyecto' => $id_proyecto,
            'Estado' => 'En Progreso',
            'Fuentes_Calidad' => $fuentes_calidad,
            'Fuentes_Drene' => $fuentes_drene
        ]);

        // Contar muestras creadas
        $cantidad_muestras = $muestraModel->contarMuestrasPorProyecto($id_proyecto);

        echo json_encode([
            'success' => true,
            'muestras_creadas' => $cantidad_muestras,
            'detalles_encontrados' => count($detalles_antes),
            'mensaje' => "Se crearon $cantidad_muestras muestras exitosamente"
        ]);
        exit;
    }

    // ===== AGREGAR MUESTRAS ADICIONALES =====
    if ($action === 'agregarMuestrasAdicionales') {
        $id_proyecto = intval($_POST['id_proyecto'] ?? 0);
        $extras = $_POST['extras'] ?? [];

        if ($id_proyecto <= 0) {
            throw new Exception('ID de proyecto inválido');
        }
        if (!is_array($extras) || empty($extras)) {
            throw new Exception('Debe indicar al menos una cantidad adicional de muestras.');
        }

        $proyectoModel = new ProyectoModel($conn);

        if (!sqlsrv_begin_transaction($conn)) {
            throw new Exception('No se pudo iniciar transaccion para agregar muestras adicionales.');
        }

        try {
            $resultado = $proyectoModel->agregarMuestrasAdicionales($id_proyecto, $extras);

            if (!sqlsrv_commit($conn)) {
                throw new Exception('No se pudo confirmar la creación de muestras adicionales.');
            }
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        $muestrasNuevas = intval($resultado['muestras_creadas'] ?? 0);
        echo json_encode([
            'success' => true,
            'muestras_creadas' => $muestrasNuevas,
            'solicitudes_creadas' => intval($resultado['solicitudes_creadas'] ?? 0),
            'resultados_creados' => intval($resultado['resultados_creados'] ?? 0),
            'mensaje' => 'Se agregaron ' . $muestrasNuevas . ' muestras adicionales al proyecto.'
        ]);
        exit;
    }

    // ===== ELIMINAR PROYECTO =====
    if ($action === 'eliminarProyecto') {
        $id_proyecto = intval($_POST['id'] ?? 0);
        if ($id_proyecto <= 0) {
            throw new Exception('ID de proyecto inválido');
        }

        $proyectoModel = new ProyectoModel($conn);
        $proyectoModel->eliminar($id_proyecto);

        echo json_encode([
            'success' => true,
            'mensaje' => 'Proyecto eliminado exitosamente'
        ]);
        exit;
    }

    throw new Exception('Acción no reconocida');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
