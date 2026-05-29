<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../..');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();
header('Content-Type: application/json; charset=utf-8');

$conn = Conexion::conectar();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la BD']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar_registros_residuos':
            $sql = "SELECT Id_Registro_Res, Codigo_SST, Ubicacion, Mes, Anio
                    FROM laboratorio.Registro_Residuos_Log
                    WHERE Activo = 1
                    ORDER BY Anio DESC, Mes DESC, Id_Registro_Res DESC";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al obtener registros de residuos');
            }

            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Setiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $mes = intval($row['Mes'] ?? 0);
                $mesTexto = $meses[$mes] ?? ('Mes ' . $mes);
                $data[] = [
                    'id' => intval($row['Id_Registro_Res']),
                    'label' => trim((string)$row['Codigo_SST']) . ' - ' . trim((string)$row['Ubicacion']) . ' - ' . $mesTexto . ' ' . trim((string)$row['Anio'])
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'obtener_residuo_reporte_data':
            $idRegistro = intval($_GET['id_registro'] ?? 0);
            if ($idRegistro <= 0) {
                throw new Exception('Registro de residuos inválido');
            }

            $sqlCabecera = "SELECT Id_Registro_Res, Mes, Anio, Ubicacion, Codigo_SST
                           FROM laboratorio.Registro_Residuos_Log
                           WHERE Id_Registro_Res = ? AND Activo = 1";
            $stmtCabecera = sqlsrv_query($conn, $sqlCabecera, [$idRegistro]);
            if ($stmtCabecera === false) {
                throw new Exception('Error al obtener cabecera de residuos');
            }

            $cabecera = sqlsrv_fetch_array($stmtCabecera, SQLSRV_FETCH_ASSOC);
            if (!$cabecera) {
                throw new Exception('No se encontró el registro de residuos');
            }

            $sqlDetalles = "SELECT drl.Fecha_Dia,
                                   rc.Subcategoria,
                                   SUM(CAST(drl.Peso_Valor AS FLOAT)) AS Total_Peso
                            FROM laboratorio.Detalle_Residuos_Log drl
                            JOIN laboratorio.Residuo_Catalogo rc ON drl.Id_Residuo_Cat = rc.Id_Residuo_Cat
                            WHERE drl.Id_Registro_Res = ? AND drl.Activo = 1
                            GROUP BY drl.Fecha_Dia, rc.Subcategoria";
            $stmtDetalles = sqlsrv_query($conn, $sqlDetalles, [$idRegistro]);
            if ($stmtDetalles === false) {
                throw new Exception('Error al obtener detalle de residuos');
            }

            $mapeoSubcategorias = [
                'no aprovechables' => 'NO APROVECHABLE',
                'no aprovechable' => 'NO APROVECHABLE',
                'aprovechables' => 'APROVECHABLE',
                'aprovechable' => 'APROVECHABLE',
                'orgánico' => 'ORGÁNICO',
                'organico' => 'ORGÁNICO',
                'orgánicos' => 'ORGÁNICO',
                'quimico' => 'QUÍMICO',
                'químico' => 'QUÍMICO',
                'quimicos' => 'QUÍMICO',
                'biologico' => 'BIOLÓGICO',
                'biológico' => 'BIOLÓGICO',
                'biologicos' => 'BIOLÓGICO',
                'metales pesados' => 'METALES PESADOS',
                'metal pesado' => 'METALES PESADOS',
                'reactivos' => 'REACTIVOS',
                'reactivo' => 'REACTIVOS',
                'material contaminado' => 'MATERIAL CONTAMINADO',
            ];

            $categorias = ['ORGÁNICO', 'APROVECHABLE', 'NO APROVECHABLE', 'QUÍMICO', 'BIOLÓGICO', 'METALES PESADOS', 'REACTIVOS', 'MATERIAL CONTAMINADO'];

            $detallesPorFecha = [];
            while ($row = sqlsrv_fetch_array($stmtDetalles, SQLSRV_FETCH_ASSOC)) {
                $fechaTxt = ($row['Fecha_Dia'] instanceof DateTime) ? $row['Fecha_Dia']->format('d/m/Y') : trim((string)$row['Fecha_Dia']);
                if ($fechaTxt === '') {
                    continue;
                }
                if (!isset($detallesPorFecha[$fechaTxt])) {
                    $detallesPorFecha[$fechaTxt] = array_fill_keys($categorias, 0.0);
                }
                $subKey = strtolower(trim((string)$row['Subcategoria']));
                $cat = $mapeoSubcategorias[$subKey] ?? null;
                if ($cat && isset($detallesPorFecha[$fechaTxt][$cat])) {
                    $detallesPorFecha[$fechaTxt][$cat] += floatval($row['Total_Peso'] ?? 0);
                }
            }

            $anio = intval($cabecera['Anio'] ?? date('Y'));
            $mes = intval($cabecera['Mes'] ?? date('n'));
            $primerDia = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
            $ultimoDia = (clone $primerDia)->modify('last day of this month');

            $rows = [];
            $actual = clone $primerDia;
            while ($actual <= $ultimoDia) {
                $f = $actual->format('d/m/Y');
                $val = $detallesPorFecha[$f] ?? array_fill_keys($categorias, 0.0);
                $rows[] = [
                    $f,
                    round($val['ORGÁNICO'] ?? 0, 2),
                    round($val['APROVECHABLE'] ?? 0, 2),
                    round($val['NO APROVECHABLE'] ?? 0, 2),
                    round($val['QUÍMICO'] ?? 0, 2),
                    round($val['BIOLÓGICO'] ?? 0, 2),
                    round($val['METALES PESADOS'] ?? 0, 2),
                    round($val['REACTIVOS'] ?? 0, 2),
                    round($val['MATERIAL CONTAMINADO'] ?? 0, 2)
                ];
                $actual->modify('+1 day');
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'meta' => [
                        'id_registro' => $idRegistro,
                        'codigo_sst' => trim((string)($cabecera['Codigo_SST'] ?? 'SST-16')),
                        'ubicacion' => trim((string)($cabecera['Ubicacion'] ?? '-')),
                        'mes' => $mes,
                        'anio' => $anio
                    ],
                    'headers' => ['FECHA', 'Orgánicos (Kg)', 'Aprovechables (Kg)', 'No Aprovechables (Kg)', 'Químicos (Kg)', 'Biológicos (Kg)', 'Metales pesados (Kg)', 'Reactivos (Kg)', 'Material contaminado (Kg)'],
                    'rows' => $rows
                ]
            ]);
            break;

        case 'listar_clientes_muestras':
            $sql = "SELECT DISTINCT
                        m.Id_Cliente,
                        COALESCE(NULLIF(LTRIM(RTRIM(CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno))), ''), 'Cliente sin nombre') AS Agricultor
                    FROM laboratorio.Muestra_Lab m
                    INNER JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
                    WHERE m.Activo = 1
                      AND m.Estado = 'Finalizado'
                      AND m.Id_Proyecto IS NULL
                    ORDER BY Agricultor";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al obtener clientes de muestras');
            }

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = [
                    'id' => intval($row['Id_Cliente']),
                    'label' => trim((string)$row['Agricultor'])
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'listar_muestras_cliente':
            $idCliente = intval($_GET['id_cliente'] ?? 0);
            if ($idCliente <= 0) {
                throw new Exception('Cliente inválido');
            }

            $sql = "SELECT m.Id_Muestra,
                           m.Fecha_Validacion,
                           m.Valle,
                           CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                                WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                                ELSE '-' END AS TipoMuestra
                    FROM laboratorio.Muestra_Lab m
                    LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
                    LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
                    WHERE m.Activo = 1
                      AND m.Estado = 'Finalizado'
                      AND m.Id_Proyecto IS NULL
                      AND m.Id_Cliente = ?
                    ORDER BY m.Id_Muestra DESC";
            $stmt = sqlsrv_query($conn, $sql, [$idCliente]);
            if ($stmt === false) {
                throw new Exception('Error al obtener muestras por cliente');
            }

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fecha = $row['Fecha_Validacion'] instanceof DateTime ? $row['Fecha_Validacion']->format('d/m/Y') : '-';
                $data[] = [
                    'id' => intval($row['Id_Muestra']),
                    'label' => 'Muestra #' . intval($row['Id_Muestra']) . ' - ' . trim((string)($row['TipoMuestra'] ?? '-')) . ' - ' . trim((string)($row['Valle'] ?? '-')) . ' - ' . $fecha
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'listar_proyectos_muestras':
            $sql = "SELECT DISTINCT
                        p.Id_Proyecto,
                        p.Nombre_Proyecto,
                        p.Temporada,
                        p.Valle
                    FROM laboratorio.Muestra_Lab m
                    INNER JOIN laboratorio.Proyecto_Monitoreo p ON m.Id_Proyecto = p.Id_Proyecto
                    WHERE m.Activo = 1
                      AND m.Estado = 'Finalizado'
                      AND m.Id_Proyecto IS NOT NULL
                      AND p.Activo = 1
                    ORDER BY p.Id_Proyecto DESC";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al obtener proyectos de muestras');
            }

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = [
                    'id' => intval($row['Id_Proyecto']),
                    'label' => 'Proyecto #' . intval($row['Id_Proyecto']) . ' - ' . trim((string)$row['Nombre_Proyecto']) . ' - ' . trim((string)($row['Temporada'] ?? '-')) . ' - ' . trim((string)($row['Valle'] ?? '-'))
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'listar_muestras_proyecto':
            $idProyecto = intval($_GET['id_proyecto'] ?? 0);
            if ($idProyecto <= 0) {
                throw new Exception('Proyecto inválido');
            }

            $sql = "SELECT m.Id_Muestra,
                           m.Fecha_Validacion,
                           m.Valle,
                           CASE WHEN ds.Id_Muestra IS NOT NULL THEN 'Suelo'
                                WHEN da.Id_Muestra IS NOT NULL THEN 'Agua'
                                ELSE '-' END AS TipoMuestra
                    FROM laboratorio.Muestra_Lab m
                    LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
                    LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
                    WHERE m.Activo = 1
                      AND m.Estado = 'Finalizado'
                      AND m.Id_Proyecto = ?
                    ORDER BY m.Id_Muestra DESC";
            $stmt = sqlsrv_query($conn, $sql, [$idProyecto]);
            if ($stmt === false) {
                throw new Exception('Error al obtener muestras por proyecto');
            }

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fecha = $row['Fecha_Validacion'] instanceof DateTime ? $row['Fecha_Validacion']->format('d/m/Y') : '-';
                $data[] = [
                    'id' => intval($row['Id_Muestra']),
                    'label' => 'Muestra #' . intval($row['Id_Muestra']) . ' - ' . trim((string)($row['TipoMuestra'] ?? '-')) . ' - ' . trim((string)($row['Valle'] ?? '-')) . ' - ' . $fecha
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'listar_proyectos_monitoreo':
            $sql = "SELECT Id_Proyecto, Nombre_Proyecto, Temporada, Valle
                    FROM laboratorio.Proyecto_Monitoreo
                    WHERE Activo = 1
                      AND (Es_Control_Calidad = 0 OR Es_Control_Calidad IS NULL)
                    ORDER BY Id_Proyecto DESC";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al obtener proyectos de monitoreo');
            }

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = [
                    'id' => intval($row['Id_Proyecto']),
                    'label' => trim((string)$row['Nombre_Proyecto'])
                        . ' — ' . trim((string)($row['Temporada'] ?? '-'))
                        . ' — Valle ' . trim((string)($row['Valle'] ?? '-'))
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'listar_proyectos_calidad_agua':
            $sql = "SELECT Id_Proyecto, Nombre_Proyecto, Temporada, Valle
                    FROM laboratorio.Proyecto_Monitoreo
                    WHERE Activo = 1
                      AND Es_Control_Calidad = 1
                    ORDER BY Id_Proyecto DESC";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                throw new Exception('Error al obtener proyectos de calidad de agua');
            }

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = [
                    'id' => intval($row['Id_Proyecto']),
                    'label' => trim((string)$row['Nombre_Proyecto'])
                        . ' — ' . trim((string)($row['Temporada'] ?? '-'))
                        . ' — Valle ' . trim((string)($row['Valle'] ?? '-'))
                ];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
