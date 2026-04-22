<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config/db.php';
require_once 'core/Auth.php';

Auth::check();

$conn = Conexion::conectar();

// Obtener mes y año
$mes = intval($_GET['mes'] ?? date('m'));
$anio = intval($_GET['anio'] ?? date('Y'));

if ($mes < 1 || $mes > 12) $mes = intval(date('m'));
if ($anio < 2000 || $anio > 2100) $anio = intval(date('Y'));

$mes_str = str_pad($mes, 2, '0', STR_PAD_LEFT);
$fecha_inicio = "$anio-$mes_str-01";
$dias_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);

// Obtener todos los reactivos activos
$sql_reactivos = "SELECT Id_Reactivo, Nombre, Unidad_Medida, Cantidad_Stock, ISNULL(Cantidad_Inicial, 0) AS Cantidad_Inicial FROM laboratorio.Reactivo_Lab WHERE Activo = 1 ORDER BY Nombre";
$stmt_react = sqlsrv_query($conn, $sql_reactivos);
$reactivos = [];
if ($stmt_react) {
    while ($row = sqlsrv_fetch_array($stmt_react, SQLSRV_FETCH_ASSOC)) {
        $reactivos[] = $row;
    }
}

// Obtener movimientos del mes
$sql_movimientos = "
    SELECT 
        mk.Id_Reactivo,
        mk.Tipo_Movimiento,
        mk.Cantidad,
        mk.Concepto,
        mk.Saldo_Resultante,
        CAST(mk.Fecha_Registro AS DATE) as Fecha_Dia,
        DAY(mk.Fecha_Registro) as Dia
    FROM laboratorio.Movimiento_Kardex mk
    WHERE mk.Activo = 1
    AND YEAR(mk.Fecha_Registro) = ?
    AND MONTH(mk.Fecha_Registro) = ?
    ORDER BY mk.Id_Reactivo, mk.Fecha_Registro
";
$stmt_mov = sqlsrv_query($conn, $sql_movimientos, [$anio, $mes]);
$movimientos_raw = [];
if ($stmt_mov) {
    while ($row = sqlsrv_fetch_array($stmt_mov, SQLSRV_FETCH_ASSOC)) {
        $movimientos_raw[] = $row;
    }
}

// Agrupar movimientos por reactivo y día
$movimientos = [];

foreach ($movimientos_raw as $mov) {
    $id_react = $mov['Id_Reactivo'];
    $dia = $mov['Dia'];
    $tipo = strtoupper($mov['Tipo_Movimiento'][0] ?? 'E');
    $cantidad = intval($mov['Cantidad']);
    
    if (!isset($movimientos[$id_react])) {
        $movimientos[$id_react] = [];
    }
    if (!isset($movimientos[$id_react][$dia])) {
        $movimientos[$id_react][$dia] = ['E' => 0, 'S' => 0];
    }
    
    $movimientos[$id_react][$dia][$tipo] += $cantidad;
}

// Obtener ingresos del mes
$sql_ingresos = "
    SELECT 
        ir.Id_Reactivo,
        ir.Cantidad,
        ir.Factura_Referencia,
        CAST(ir.Fecha_Ingreso AS DATE) as Fecha_Dia,
        rl.Nombre as Reactivo_Nombre
    FROM laboratorio.Ingreso_Reactivo ir
    JOIN laboratorio.Reactivo_Lab rl ON ir.Id_Reactivo = rl.Id_Reactivo
    WHERE rl.Activo = 1
    AND YEAR(ir.Fecha_Ingreso) = ?
    AND MONTH(ir.Fecha_Ingreso) = ?
    ORDER BY ir.Fecha_Ingreso DESC
";
$stmt_ingresos = sqlsrv_query($conn, $sql_ingresos, [$anio, $mes]);
$ingresos_raw = [];
if ($stmt_ingresos) {
    while ($row = sqlsrv_fetch_array($stmt_ingresos, SQLSRV_FETCH_ASSOC)) {
        $ingresos_raw[] = $row;
    }
} else {
    $errors = sqlsrv_errors();
    if ($errors) {
        echo "<!-- Error en query de ingresos: " . print_r($errors, true) . " -->";
    }
}

// Agrupar ingresos por fecha y reactivo (consolidado)
$ingresos_agrupados = [];
foreach ($ingresos_raw as $ing) {
    $fecha = $ing['Fecha_Dia'];
    if (is_object($fecha)) {
        $fecha = $fecha->format('Y-m-d');
    }
    if (!isset($ingresos_agrupados[$fecha])) {
        $ingresos_agrupados[$fecha] = [];
    }
    
    // Agrupar por nombre de reactivo dentro de la fecha
    $reactivo_nombre = $ing['Reactivo_Nombre'];
    $encontrado = false;
    
    foreach ($ingresos_agrupados[$fecha] as &$item) {
        if ($item['Reactivo_Nombre'] === $reactivo_nombre) {
            // Sumar cantidad al reactivo existente
            $item['Cantidad'] += $ing['Cantidad'];
            $encontrado = true;
            break;
        }
    }
    
    // Si no existe, crear nueva entrada consolidada
    if (!$encontrado) {
        $ingresos_agrupados[$fecha][] = [
            'Id_Reactivo' => $ing['Id_Reactivo'],
            'Reactivo_Nombre' => $ing['Reactivo_Nombre'],
            'Cantidad' => $ing['Cantidad'],
            'Factura_Referencia' => $ing['Factura_Referencia'],
            'Fecha_Dia' => $ing['Fecha_Dia']
        ];
    }
}

// Obtener salidas del mes (desde Movimiento_Kardex con tipo 'S')
$sql_salidas = "
    SELECT 
        mk.Id_Movimiento,
        mk.Id_Reactivo,
        mk.Cantidad,
        mk.Concepto,
        CAST(mk.Fecha_Registro AS DATE) as Fecha_Dia,
        rl.Nombre as Reactivo_Nombre
    FROM laboratorio.Movimiento_Kardex mk
    JOIN laboratorio.Reactivo_Lab rl ON mk.Id_Reactivo = rl.Id_Reactivo
    WHERE mk.Activo = 1
    AND mk.Tipo_Movimiento = 'S'
    AND rl.Activo = 1
    AND YEAR(mk.Fecha_Registro) = ?
    AND MONTH(mk.Fecha_Registro) = ?
    ORDER BY mk.Fecha_Registro DESC, mk.Id_Movimiento DESC
";
$stmt_salidas = sqlsrv_query($conn, $sql_salidas, [$anio, $mes]);
$salidas_raw = [];
if ($stmt_salidas) {
    while ($row = sqlsrv_fetch_array($stmt_salidas, SQLSRV_FETCH_ASSOC)) {
        $salidas_raw[] = $row;
    }
} else {
    // Log de error si la query falla
    $errors = sqlsrv_errors();
    if ($errors) {
        echo "<!-- Error en query de salidas: " . print_r($errors, true) . " -->";
    }
}

// Agrupar salidas por fecha y reactivo (consolidado)
$salidas_agrupadas = [];
foreach ($salidas_raw as $sal) {
    $fecha = $sal['Fecha_Dia'];
    if (is_object($fecha)) {
        $fecha = $fecha->format('Y-m-d');
    }
    if (!isset($salidas_agrupadas[$fecha])) {
        $salidas_agrupadas[$fecha] = [];
    }
    
    // Agrupar por nombre de reactivo dentro de la fecha
    $reactivo_nombre = $sal['Reactivo_Nombre'];
    $encontrado = false;
    
    foreach ($salidas_agrupadas[$fecha] as &$item) {
        if ($item['Reactivo_Nombre'] === $reactivo_nombre) {
            // Sumar cantidad al reactivo existente
            $item['Cantidad'] += $sal['Cantidad'];
            $encontrado = true;
            break;
        }
    }
    
    // Si no existe, crear nueva entrada consolidada
    if (!$encontrado) {
        $salidas_agrupadas[$fecha][] = [
            'Id_Reactivo' => $sal['Id_Reactivo'],
            'Reactivo_Nombre' => $sal['Reactivo_Nombre'],
            'Cantidad' => $sal['Cantidad'],
            'Concepto' => $sal['Concepto'],
            'Fecha_Dia' => $sal['Fecha_Dia']
        ];
    }
}

$meses_nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_nombre = $meses_nombres[$mes - 1];

// Debug info
echo "<!-- Debug: Ingresos encontrados: " . count($ingresos_agrupados) . " fechas, Salidas encontradas: " . count($salidas_agrupadas) . " fechas -->";
echo "<!-- Mes: $mes, Año: $anio -->";
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .entrada {
        color: #31ce36;
        font-weight: 600;
    }
    
    .salida {
        color: #f97316;
        font-weight: 600;
    }

    .movimiento-click {
        cursor: pointer;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    
    .value-empty {
        color: #d4d4d8;
        font-size: 9px;
    }
    
    .table-wrapper {
        overflow-x: auto;
        border-radius: 4px;
    }
    
    .table-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .kardex-table {
        font-size: 0.85rem;
        width: 100%;
        border-collapse: collapse;
        background: white;
        min-width: 1400px;
    }
    
    .kardex-table thead {
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
    }
    
    .kardex-table th {
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 8px;
        text-align: center;
        white-space: nowrap;
        font-size: 11px;
        color: #1d273b;
    }
    
    .kardex-table th:nth-child(1) { width: 45px; }
    .kardex-table th:nth-child(2) { width: 160px; text-align: left; }
    .kardex-table th:nth-child(3) { width: 55px; }
    .kardex-table th:nth-child(4) { width: 55px; }
    .kardex-table th:nth-child(5) { width: 65px; }
    
    .kardex-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #dee2e6;
        text-align: center;
        font-size: 11px;
    }
    
    .kardex-table td:nth-child(1) {
        font-weight: 600;
        text-align: center;
    }
    
    .kardex-table td:nth-child(2) {
        text-align: left;
        font-weight: 600;
    }
    
    .kardex-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .mes-year-label {
        text-align: center;
        font-size: 16px;
        font-weight: 600;
        padding: 15px 0;
        color: #1d273b;
        background-color: #f8f9fa;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    
    .filter-group {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .filter-group label {
        font-weight: 600;
        margin: 0;
        color: #1d273b;
        font-size: 13px;
    }
    
    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
    }

    /* Estilos para sección de Ingresos */
    .ingresos-section {
        margin-top: 30px;
    }

    .ingresos-header {
        margin-bottom: 25px;
    }

    .ingresos-header h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1d273b;
        margin: 0 0 5px 0;
    }

    .ingresos-header p {
        color: #6b7280;
        font-size: 14px;
        margin: 0;
    }

    .ingresos-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 15px;
    }

    .ingresos-search {
        flex: 1;
        max-width: 300px;
    }

    .ingresos-search input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
    }

    .ingresos-filter-btn {
        padding: 8px 16px;
        border: 1px solid #31ce36;
        background: white;
        color: #31ce36;
        border-radius: 4px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ingresos-filter-btn:hover {
        background: #f0fdf4;
    }

    .ingresos-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .ingreso-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
    }

    .ingreso-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .ingreso-card-date {
        font-size: 16px;
        font-weight: 600;
        color: #1d273b;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .ingreso-card-title {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 5px;
    }

    .ingreso-card-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-bottom: 15px;
    }

    .ingreso-card-table th {
        background: #f8f9fa;
        padding: 8px;
        text-align: left;
        font-weight: 600;
        color: #1d273b;
        border-bottom: 1px solid #dee2e6;
    }

    .ingreso-card-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #f0f0f0;
    }

    .ingreso-card-table td:last-child {
        text-align: center;
        font-weight: 600;
    }

    .ingreso-card-button {
        width: 100%;
        padding: 8px;
        background: #31ce36;
        color: white;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .ingreso-card-button:hover {
        background: #2ab827;
    }

    .ingresos-empty {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
    }

    .ingresos-empty i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
</style>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
                <li class="breadcrumb-item"><a href="?module=laboratorio&action=reactivo">Reactivos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kardex</li>
            </ol>
        </nav>
        
        <!-- Page Title -->
        <div class="row g-2 align-items-center mb-3">
            <div class="col">
                <h2 class="page-title">KARDEX DE REACTIVOS DE LABORATORIO</h2>
                <div class="text-muted mt-1">Registro mensual de movimientos de inventario detallando las entradas (E) y salidas (S) diarias para cada reactivo</div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row g-2">
            <div class="col-auto">
                <button class="btn btn-success" onclick="abrirModalIngreso()">
                    <i class="ti ti-plus me-2"></i> Realizar Ingreso
                </button>
            </div>
            <div class="col-auto">
                <button class="btn btn-danger" onclick="abrirModalSalida()">
                    <i class="ti ti-minus me-2"></i> Realizar Salida
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">
        <!-- Alert Info -->
        <div class="alert alert-info" role="alert">
            <div class="d-flex">
                <div>
                    <i class="ti ti-info-circle me-2"></i>
                    <strong>Fórmula de cálculo:</strong> El sistema calcula automáticamente el balance actual basándose en: <code>Inicial + Entradas - Salidas</code>. Al ingresar reactivos, indique la cantidad que se está ingresando.
                    <br><strong>Tip:</strong> haga clic en valores <span class="entrada">+E</span> o <span class="salida">-S</span> dentro del calendario para ver el detalle del movimiento de ese día.
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="filter-group">
                    <label>Mes:</label>
                    <select id="mes" onchange="cambiarMes()" class="form-select" style="width: auto;">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $mes ? 'selected' : ''; ?>>
                                <?php echo $meses_nombres[$m-1]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    
                    <label style="margin-left: 20px;">Año:</label>
                    <input type="number" id="anio" value="<?php echo $anio; ?>" min="2000" max="2100" style="width: 100px;" class="form-control">
                    
                    <button onclick="cambiarMes()" class="btn btn-primary" style="margin-left: 20px;">
                        <i class="ti ti-refresh me-2"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Kardex Table Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo $mes_nombre; ?> de <?php echo $anio; ?></h3>
            </div>
            <div class="card-body table-responsive">
                <div class="table-wrapper">
                    <table class="kardex-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nombre</th>
                                <th>U.M.</th>
                                <th>Inicial</th>
                                <th>Actual</th>
                                <?php for ($dia = 1; $dia <= $dias_mes; $dia++): ?>
                                    <th title="Día <?php echo $dia; ?>">
                                        <div><?php echo str_pad($dia, 2, '0', STR_PAD_LEFT); ?></div>
                                        <div style="font-size: 9px; font-weight: normal;">E / S</div>
                                    </th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reactivos) > 0): ?>
                                <?php foreach ($reactivos as $idx => $reactivo): 
                                    $no = $idx + 1;
                                    $id_react = $reactivo['Id_Reactivo'];
                                    $nombre = $reactivo['Nombre'];
                                    $unidad = $reactivo['Unidad_Medida'];
                                    $stock_actual = floatval($reactivo['Cantidad_Stock']);
                                    $stock_inicial = floatval($reactivo['Cantidad_Inicial']);
                                ?>
                                <tr>
                                    <td><?php echo $no; ?></td>
                                    <td><?php echo htmlspecialchars($nombre); ?></td>
                                    <td><?php echo htmlspecialchars($unidad); ?></td>
                                    <td><?php echo number_format($stock_inicial, 2, '.', ''); ?></td>
                                    <td><strong><?php echo number_format($stock_actual, 2, '.', ''); ?></strong></td>
                                    
                                    <?php for ($dia = 1; $dia <= $dias_mes; $dia++): ?>
                                        <td>
                                            <?php 
                                                $entrada = 0;
                                                $salida = 0;
                                                $fecha_click = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                                                
                                                if (isset($movimientos[$id_react][$dia])) {
                                                    $entrada = $movimientos[$id_react][$dia]['E'];
                                                    $salida = $movimientos[$id_react][$dia]['S'];
                                                }
                                            ?>
                                            <div style="line-height: 1.4;">
                                                <?php if ($entrada > 0): ?>
                                                    <div class="entrada movimiento-click" onclick='mostrarDetallesIngreso("<?php echo $fecha_click; ?>", <?php echo intval($id_react); ?>, <?php echo json_encode((string)$nombre); ?>)' title="Ver detalle de ingresos del día">+<?php echo $entrada; ?></div>
                                                <?php else: ?>
                                                    <div class="value-empty">-</div>
                                                <?php endif; ?>
                                                
                                                <?php if ($salida > 0): ?>
                                                    <div class="salida movimiento-click" onclick='mostrarDetallesSalida("<?php echo $fecha_click; ?>", <?php echo intval($id_react); ?>, <?php echo json_encode((string)$nombre); ?>)' title="Ver detalle de salidas del día">-<?php echo $salida; ?></div>
                                                <?php else: ?>
                                                    <div class="value-empty">-</div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo 5 + $dias_mes; ?>" style="text-align: center; padding: 40px; color: #999;">
                                        <i class="ti ti-alert-circle" style="margin-right: 8px;"></i>
                                        No hay reactivos registrados en el sistema
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ingreso -->
<div class="modal fade" id="modal-ingreso" tabindex="-1" aria-labelledby="modal-ingreso-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-ingreso-label">Realizar Ingreso de Reactivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Reactivo *</label>
                    <select id="ingreso-reactivo" class="form-select">
                        <option value="">Seleccione un reactivo...</option>
                        <?php foreach ($reactivos as $r): ?>
                            <option value="<?php echo $r['Id_Reactivo']; ?>">
                                <?php echo htmlspecialchars($r['Nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" id="ingreso-cantidad" class="form-control" min="0.01" step="0.01" placeholder="Cantidad a ingresar">
                </div>
                <div class="mb-3">
                    <label class="form-label">Factura/Referencia</label>
                    <input type="text" id="ingreso-factura" class="form-control" placeholder="FAC-2024-001">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarIngreso()">Guardar Ingreso</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Salida -->
<div class="modal fade" id="modal-salida" tabindex="-1" aria-labelledby="modal-salida-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-salida-label">Realizar Salida de Reactivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Reactivo *</label>
                    <select id="salida-reactivo" class="form-select">
                        <option value="">Seleccione un reactivo...</option>
                        <?php foreach ($reactivos as $r): ?>
                            <option value="<?php echo $r['Id_Reactivo']; ?>">
                                <?php echo htmlspecialchars($r['Nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" id="salida-cantidad" class="form-control" min="0.01" step="0.01" placeholder="Cantidad a retirar">
                </div>
                <div class="mb-3">
                    <label class="form-label">Concepto</label>
                    <input type="text" id="salida-concepto" class="form-control" placeholder="Ej: Consumo en análisis">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="guardarSalida()">Guardar Salida</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function cambiarMes() {
    const mes = document.getElementById('mes').value;
    const anio = document.getElementById('anio').value;
    window.location.href = `?module=laboratorio&action=reactivo&subaction=kardex&mes=${mes}&anio=${anio}`;
}

function abrirModalIngreso() {
    document.getElementById('ingreso-reactivo').value = '';
    document.getElementById('ingreso-cantidad').value = '';
    document.getElementById('ingreso-factura').value = '';
    const modal = new bootstrap.Modal(document.getElementById('modal-ingreso'));
    modal.show();
}

function abrirModalSalida() {
    document.getElementById('salida-reactivo').value = '';
    document.getElementById('salida-cantidad').value = '';
    document.getElementById('salida-concepto').value = '';
    const modal = new bootstrap.Modal(document.getElementById('modal-salida'));
    modal.show();
}

function guardarIngreso() {
    const idReactivo = document.getElementById('ingreso-reactivo').value;
    const cantidad = document.getElementById('ingreso-cantidad').value;
    const factura = document.getElementById('ingreso-factura').value;
    
    if (!idReactivo) {
        Swal.fire('Error', 'Por favor seleccione un reactivo', 'error');
        return;
    }
    
    if (!cantidad || parseFloat(cantidad) <= 0) {
        Swal.fire('Error', 'Por favor ingrese una cantidad válida', 'error');
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader animate-spin me-2"></i> Guardando...';
    
    $.ajax({
        url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=registrar_ingreso',
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({
            Id_Reactivo: parseInt(idReactivo),
            Cantidad: parseFloat(cantidad),
            Factura_Referencia: factura || 'S/N'
        }),
        success: function(response) {
            btn.disabled = false;
            btn.innerHTML = 'Guardar Ingreso';
            
            if (response.success) {
                Swal.fire('Éxito', 'Ingreso registrado correctamente', 'success').then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modal-ingreso')).hide();
                    setTimeout(() => location.reload(), 800);
                });
            } else {
                Swal.fire('Error', response.message || 'Error al registrar el ingreso', 'error');
            }
        },
        error: function(xhr, status, error) {
            btn.disabled = false;
            btn.innerHTML = 'Guardar Ingreso';
            
            let mensajeError = 'No se pudo conectar con el servidor';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensajeError = xhr.responseJSON.message;
            }
            Swal.fire('Error', mensajeError, 'error');
        }
    });
}

function guardarSalida() {
    const idReactivo = document.getElementById('salida-reactivo').value;
    const cantidad = document.getElementById('salida-cantidad').value;
    const concepto = document.getElementById('salida-concepto').value;
    
    if (!idReactivo) {
        Swal.fire('Error', 'Por favor seleccione un reactivo', 'error');
        return;
    }
    
    if (!cantidad || parseFloat(cantidad) <= 0) {
        Swal.fire('Error', 'Por favor ingrese una cantidad válida', 'error');
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader animate-spin me-2"></i> Guardando...';
    
    $.ajax({
        url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=registrar_salida',
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({
            Id_Reactivo: parseInt(idReactivo),
            Cantidad: parseFloat(cantidad),
            Concepto: concepto || 'S/N'
        }),
        success: function(response) {
            btn.disabled = false;
            btn.innerHTML = 'Guardar Salida';
            
            if (response.success) {
                Swal.fire('Éxito', 'Salida registrada correctamente', 'success').then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modal-salida')).hide();
                    setTimeout(() => location.reload(), 800);
                });
            } else {
                Swal.fire('Error', response.message || 'Error al registrar la salida', 'error');
            }
        },
        error: function(xhr, status, error) {
            btn.disabled = false;
            btn.innerHTML = 'Guardar Salida';
            
            let mensajeError = 'No se pudo conectar con el servidor';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensajeError = xhr.responseJSON.message;
            }
            Swal.fire('Error', mensajeError, 'error');
        }
    });
}

function mostrarDetallesIngreso(fecha, idReactivo, nombreReactivo) {
    // Obtener los detalles de ingresos de esa fecha
    $.ajax({
        url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=obtener_detalles_ingreso&fecha=' + encodeURIComponent(fecha) + '&id_reactivo=' + encodeURIComponent(idReactivo || 0),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.ingresos && response.ingresos.length > 0) {
                // Construir tabla de ingresos
                let html = '<div style="text-align: left; font-size: 13px;">';
                html += '<p style="color: #6b7280; margin-bottom: 15px; font-size: 12px;">Detalles de los reactivos ingresados en esta fecha:</p>';
                html += '<table style="width: 100%; border-collapse: collapse;">';
                html += '<thead style="background: #f8f9fa;">';
                html += '<tr>';
                html += '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #1d273b;">Reactivo</th>';
                html += '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #1d273b;">Cantidad</th>';
                html += '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #1d273b;">Unidad</th>';
                html += '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #1d273b;">Factura</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';
                
                response.ingresos.forEach(function(ing) {
                    html += '<tr style="border-bottom: 1px solid #f0f0f0;">';
                    html += '<td style="padding: 10px; text-align: left;">' + (ing.Reactivo_Nombre || 'N/A') + '</td>';
                    html += '<td style="padding: 10px; text-align: center; font-weight: 600; color: #31ce36;">' + (ing.Cantidad || 0) + '</td>';
                    html += '<td style="padding: 10px; text-align: center;">' + (ing.Unidad_Medida || 'N/A') + '</td>';
                    html += '<td style="padding: 10px; text-align: left;">' + (ing.Factura_Referencia || 'S/N') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody>';
                html += '</table>';
                if (idReactivo && nombreReactivo) {
                    html += '<p style="margin-top:10px;color:#6b7280;font-size:12px;">Reactivo: <strong>' + nombreReactivo + '</strong></p>';
                }
                html += '</div>';
                
                Swal.fire({
                    title: 'Detalles de Ingreso - ' + fecha,
                    html: html,
                    icon: 'success',
                    confirmButtonText: 'Cerrar',
                    width: '700px'
                });
            } else {
                Swal.fire({
                    title: 'Detalles de Ingreso - ' + fecha,
                    html: '<p style="color: #9ca3af;">No hay ingresos registrados para esta fecha</p>',
                    icon: 'info',
                    confirmButtonText: 'Cerrar'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                title: 'Error',
                text: 'No se pudieron obtener los detalles del ingreso',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            });
        }
    });
}

function mostrarDetallesSalida(fecha, idReactivo, nombreReactivo) {
    // Obtener salidas reales del kardex para que cuadre con el valor del calendario
    $.ajax({
        url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=obtener_detalles_salida&fecha=' + encodeURIComponent(fecha) + '&id_reactivo=' + encodeURIComponent(idReactivo || 0),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.salidas && response.salidas.length > 0) {
                const escapeHtml = function(text) {
                    return String(text || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                };

                const getParentInfo = function(sal) {
                    const principal = String(sal.Segmento_Principal || '').trim();
                    const label = principal !== '' ? principal : 'Otros consumos';
                    return {
                        key: label.toLowerCase(),
                        label: label
                    };
                };

                const getMuestraKey = function(sal) {
                    const secundaria = String(sal.Segmento_Secundario || '').trim();
                    if (secundaria !== '') {
                        return secundaria;
                    }
                    return 'Movimiento #' + (sal.Id_Movimiento || 0);
                };

                const getDetalleExpandido = function(sal) {
                    const partes = [];
                    const tipo = String(sal.Tipo_Detalle || '').trim();
                    const producto = String(sal.Producto_Nombre || '').trim();
                    const concepto = String(sal.Concepto || '').trim();

                    if (tipo !== '') {
                        partes.push('Tipo: ' + tipo);
                    }
                    if (producto !== '') {
                        partes.push('Producto: ' + producto);
                    }
                    if (sal.Id_Muestra_Producto) {
                        partes.push('MP#' + sal.Id_Muestra_Producto);
                    }
                    if (concepto !== '') {
                        partes.push('Concepto: ' + concepto);
                    }

                    return partes.join(' | ');
                };

                const grupos = {};
                const ordenGrupos = [];

                response.salidas.forEach(function(sal) {
                    const parent = getParentInfo(sal);
                    const gKey = parent.key;
                    const cantidad = Number(sal.Cantidad || 0);
                    const unidad = sal.Unidad_Medida || 'UND';
                    const muestraKey = getMuestraKey(sal);

                    if (!grupos[gKey]) {
                        grupos[gKey] = {
                            label: parent.label,
                            unidad: unidad,
                            total: 0,
                            children: {},
                            orderChildren: []
                        };
                        ordenGrupos.push(gKey);
                    }

                    grupos[gKey].total += cantidad;

                    if (!grupos[gKey].children[muestraKey]) {
                        grupos[gKey].children[muestraKey] = {
                            total: 0,
                            detalles: []
                        };
                        grupos[gKey].orderChildren.push(muestraKey);
                    }

                    grupos[gKey].children[muestraKey].total += cantidad;
                    grupos[gKey].children[muestraKey].detalles.push(getDetalleExpandido(sal));
                });

                let html = '<div style="text-align:left;font-size:13px;">';
                html += '<p style="color:#6b7280;margin-bottom:10px;font-size:12px;">Vista por niveles: Segmento principal -> Muestra/Movimiento</p>';
                html += '<div style="max-height:420px; overflow:auto; border:1px solid #e5e7eb; border-radius:6px;">';
                html += '<table style="width:100%; border-collapse:collapse; min-width: 620px;">';
                html += '<thead style="background:#f8f9fa; position:sticky; top:0; z-index:1;">';
                html += '<tr>';
                html += '<th style="padding:10px; width:50px; text-align:center; border-bottom:2px solid #dee2e6;">+</th>';
                html += '<th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;">Nivel</th>';
                html += '<th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;">Cantidad</th>';
                html += '<th style="padding:10px; text-align:center; border-bottom:2px solid #dee2e6;">Unidad</th>';
                html += '<th style="padding:10px; text-align:left; border-bottom:2px solid #dee2e6;">Detalle</th>';
                html += '</tr></thead><tbody>';

                ordenGrupos.forEach(function(gKey, idx) {
                    const grupo = grupos[gKey];
                    const groupId = 'grp_' + idx;

                    html += '<tr style="background:#fff8f1; border-bottom:1px solid #f0f0f0;">';
                    html += '<td style="padding:8px; text-align:center;">';
                    html += '<button type="button" class="toggle-group" data-group="' + groupId + '" style="border:1px solid #f97316;color:#f97316;background:#fff;border-radius:4px;width:24px;height:24px;line-height:20px;font-weight:700;">+</button>';
                    html += '</td>';
                    html += '<td style="padding:10px; font-weight:600;">' + escapeHtml(grupo.label) + '</td>';
                    html += '<td style="padding:10px; text-align:center; font-weight:700; color:#f97316;">-' + grupo.total.toFixed(2) + '</td>';
                    html += '<td style="padding:10px; text-align:center;">' + escapeHtml(grupo.unidad) + '</td>';
                    html += '<td style="padding:10px; color:#6b7280;">Total por nivel</td>';
                    html += '</tr>';

                    grupo.orderChildren.forEach(function(mKey) {
                        const child = grupo.children[mKey];
                        const detalle = child.detalles[0] || '';
                        html += '<tr class="child-row ' + groupId + '" style="display:none; border-bottom:1px solid #f3f4f6; background:#ffffff;">';
                        html += '<td style="padding:8px; text-align:center; color:#9ca3af;">-</td>';
                        html += '<td style="padding:10px 10px 10px 24px;">' + escapeHtml(mKey) + '</td>';
                        html += '<td style="padding:10px; text-align:center; font-weight:600; color:#f97316;">-' + child.total.toFixed(2) + '</td>';
                        html += '<td style="padding:10px; text-align:center;">' + escapeHtml(grupo.unidad) + '</td>';
                        html += '<td style="padding:10px; color:#6b7280;">' + escapeHtml(detalle) + '</td>';
                        html += '</tr>';
                    });
                });

                html += '</tbody></table></div>';

                if (typeof response.total_cantidad !== 'undefined') {
                    html += '<p style="margin-top:10px;color:#6b7280;font-size:12px;">Total salida del día para este reactivo: <strong>-' + Number(response.total_cantidad).toFixed(2) + '</strong></p>';
                }

                html += '</div>';
                
                Swal.fire({
                    title: 'Detalle de Salida - ' + fecha + (nombreReactivo ? ' (' + nombreReactivo + ')' : ''),
                    html: html,
                    icon: 'info',
                    confirmButtonText: 'Cerrar',
                    width: '860px',
                    didOpen: function() {
                        const popup = Swal.getPopup();
                        if (!popup) return;
                        popup.querySelectorAll('.toggle-group').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                const grp = this.getAttribute('data-group');
                                const rows = popup.querySelectorAll('.child-row.' + grp);
                                const abierto = this.textContent.trim() === '-';
                                rows.forEach(function(r) {
                                    r.style.display = abierto ? 'none' : 'table-row';
                                });
                                this.textContent = abierto ? '+' : '-';
                            });
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: 'Detalle de Salida - ' + fecha,
                    html: '<p style="color: #9ca3af;">No hay salidas registradas para esta fecha y reactivo</p>',
                    icon: 'info',
                    confirmButtonText: 'Cerrar'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                title: 'Error',
                text: 'No se pudieron obtener los detalles de salida',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            });
        }
    });
}
</script>
