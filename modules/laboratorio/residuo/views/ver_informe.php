<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$conn = Conexion::conectar();
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id_registro = intval($_GET['id'] ?? 0);

if ($id_registro <= 0) {
    die('<div class="alert alert-danger">Informe no encontrado</div>');
}

// PASO 1: Obtener cabecera
$sqlCabecera = "SELECT 
    Id_Registro_Res, 
    Mes, 
    Anio, 
    Ubicacion, 
    Codigo_SST,
    CONCAT(u.nombres, ' ', u.apellidos) AS Responsable
FROM laboratorio.Registro_Residuos_Log rrl
LEFT JOIN comun.Usuarios u ON rrl.Id_Responsable = u.id_usuario
WHERE rrl.Id_Registro_Res = ? AND rrl.Activo = 1";

$stmtCabecera = sqlsrv_query($conn, $sqlCabecera, [$id_registro]);
$cabecera = sqlsrv_fetch_array($stmtCabecera, SQLSRV_FETCH_ASSOC);

if (!$cabecera) {
    die('<div class="alert alert-danger">Informe no encontrado</div>');
}

$sqlNormativas = "SELECT
    n.Nombre_Ley,
    n.Descripcion
FROM laboratorio.Reporte_Normativa_Asociada rna
INNER JOIN laboratorio.Normativa_SST n ON rna.Id_Normativa_SST = n.Id_Normativa_SST
WHERE rna.Id_Registro_Res = ? AND n.Activo = 1
ORDER BY n.Nombre_Ley";

$stmtNormativas = sqlsrv_query($conn, $sqlNormativas, [$id_registro]);
if ($stmtNormativas === false) {
    die('Error en la consulta de normativas: ' . print_r(sqlsrv_errors(), true));
}

$normativas = [];
while ($row = sqlsrv_fetch_array($stmtNormativas, SQLSRV_FETCH_ASSOC)) {
    $nombreLey = trim((string)($row['Nombre_Ley'] ?? ''));
    $descripcion = trim((string)($row['Descripcion'] ?? ''));
    if ($nombreLey === '' && $descripcion === '') {
        continue;
    }
    $normativas[] = [
        'Nombre_Ley' => $nombreLey,
        'Descripcion' => $descripcion
    ];
}

// PASO 2: Obtener todos los detalles agrupados por fecha y subcategoría
$sqlDetalles = "SELECT 
    drl.Fecha_Dia,
    drl.Id_Residuo_Cat,
    rc.Codigo_Item,
    rc.Nombre_Item,
    rc.Unidad_Referencia,
    rc.Subcategoria,
    rc.Tipo_Principal,
    SUM(drl.Peso_Valor) AS Total_Peso
FROM laboratorio.Detalle_Residuos_Log drl
JOIN laboratorio.Residuo_Catalogo rc ON drl.Id_Residuo_Cat = rc.Id_Residuo_Cat
WHERE drl.Id_Registro_Res = ? AND drl.Activo = 1
GROUP BY drl.Fecha_Dia, drl.Id_Residuo_Cat, rc.Codigo_Item, rc.Nombre_Item, rc.Unidad_Referencia, rc.Subcategoria, rc.Tipo_Principal
ORDER BY drl.Fecha_Dia ASC";

$stmtDetalles = sqlsrv_query($conn, $sqlDetalles, [$id_registro]);

if ($stmtDetalles === false) {
    die('Error en la consulta de detalles: ' . print_r(sqlsrv_errors(), true));
}

$detallesPorFecha = [];
$totalesPorResiduo = [];

while ($row = sqlsrv_fetch_array($stmtDetalles, SQLSRV_FETCH_ASSOC)) {
    $fecha = $row['Fecha_Dia']->format('d-m-Y');
    if (!isset($detallesPorFecha[$fecha])) {
        $detallesPorFecha[$fecha] = [];
    }
    $detallesPorFecha[$fecha][] = $row;

    $idResiduo = intval($row['Id_Residuo_Cat'] ?? 0);
    if ($idResiduo > 0) {
        if (!isset($totalesPorResiduo[$idResiduo])) {
            $totalesPorResiduo[$idResiduo] = 0;
        }
        $totalesPorResiduo[$idResiduo] += floatval($row['Total_Peso'] ?? 0);
    }
}

$sqlLeyenda = "SELECT
    Id_Residuo_Cat,
    Codigo_Item,
    Nombre_Item,
    Unidad_Referencia
FROM laboratorio.Residuo_Catalogo
WHERE Activo = 1
ORDER BY Codigo_Item, Nombre_Item";

$stmtLeyenda = sqlsrv_query($conn, $sqlLeyenda);
if ($stmtLeyenda === false) {
    die('Error en la consulta de leyenda de residuos: ' . print_r(sqlsrv_errors(), true));
}

$residuosLeyenda = [];
while ($row = sqlsrv_fetch_array($stmtLeyenda, SQLSRV_FETCH_ASSOC)) {
    $residuosLeyenda[] = $row;
}

// PASO 3: Mapeo de valores de BD a categorías normalizadas
// Esto maneja variaciones como "No Aprovechables" → "NO APROVECHABLE"
$mapeoSubcategorias = [
    // NO PELIGROSOS
    'no aprovechables' => 'NO APROVECHABLE',
    'no aprovechable' => 'NO APROVECHABLE',
    'aprovechables' => 'APROVECHABLE',
    'aprovechable' => 'APROVECHABLE',
    'orgánico' => 'ORGÁNICO',
    'organico' => 'ORGÁNICO',
    'organicos' => 'ORGÁNICO',
    'orgánicos' => 'ORGÁNICO',
    
    // PELIGROSOS
    'quimico' => 'QUÍMICO',
    'químico' => 'QUÍMICO',
    'quimicos' => 'QUÍMICO',
    'químicos' => 'QUÍMICO',
    'biologico' => 'BIOLÓGICO',
    'biológico' => 'BIOLÓGICO',
    'biologicos' => 'BIOLÓGICO',
    'biológicos' => 'BIOLÓGICO',
    'metales pesados' => 'METALES PESADOS',
    'metal pesado' => 'METALES PESADOS',
    'reactivos' => 'REACTIVOS',
    'reactivo' => 'REACTIVOS',
    'material contaminado' => 'MATERIAL CONTAMINADO',
    'materiales contaminados' => 'MATERIAL CONTAMINADO',
];

// Categorías FIJAS ordenadas
$subcategoriasNoPeligrosas = [
    'ORGÁNICO' => 1,
    'APROVECHABLE' => 1,
    'NO APROVECHABLE' => 1
];

$subcategoriasPeligrosas = [
    'BIOLÓGICO' => 1,
    'MATERIAL CONTAMINADO' => 1,
    'METALES PESADOS' => 1,
    'QUÍMICO' => 1,
    'REACTIVOS' => 1
];

ksort($subcategoriasNoPeligrosas);
ksort($subcategoriasPeligrosas);

// PASO 4: Generar todos los días del mes
$mes = intval($cabecera['Mes']);
$anio = intval($cabecera['Anio']);
$diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
$todasLasFechas = [];

for ($dia = 1; $dia <= $diasEnMes; $dia++) {
    $fecha = sprintf('%02d-%02d-%04d', $dia, $mes, $anio);
    $todasLasFechas[] = $fecha;
}

// Meses en español
$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_nombre = $meses[$mes] ?? $mes;

function formatearPesoGramosVista($peso)
{
    $valor = round(floatval($peso), 3);
    if (abs($valor - round($valor)) < 0.0005) {
        return intval(round($valor)) . ' g';
    }

    $texto = number_format($valor, 3, '.', '');
    $texto = rtrim(rtrim($texto, '0'), '.');
    return $texto . ' g';
}

$residuosCatalogoJs = [];
foreach ($residuosLeyenda as $residuoItem) {
    $idResJs = intval($residuoItem['Id_Residuo_Cat'] ?? 0);
    if ($idResJs <= 0) {
        continue;
    }

    $residuosCatalogoJs[] = [
        'id' => $idResJs,
        'codigo' => trim((string)($residuoItem['Codigo_Item'] ?? '')),
        'nombre' => trim((string)($residuoItem['Nombre_Item'] ?? '')),
        'unidad' => trim((string)($residuoItem['Unidad_Referencia'] ?? ''))
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Residuos SST-16</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        body { background-color: #f5f7fb; font-size: 14px; }
        .text-muted { color: #6c757d; }
        .text-muted.mt-1 { margin-top: 0.5rem; font-size: 0.95em; }
        .alert-info {
            background-color: #e8f4f8;
            border-left: 4px solid #17a2b8;
        }
        .badge { font-size: 0.85em; padding: 0.5em 0.75em; }
        .dataTables_wrapper .pagination .page-link { color: #1d273b; }
        .dataTables_wrapper .pagination .page-item.active .page-link { 
            background-color: #004d99; border-color: #004d99; color: white; 
        }
        
        .breadcrumb-nav {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        
        .breadcrumb-nav a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb-nav a:hover {
            text-decoration: underline;
        }
        
        .page-header {
            margin-bottom: 15px;
        }
        
        .title-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .badge-sst {
            background-color: #4caf50;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .header-info {
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f6 100%);
            border-left: 4px solid #4caf50;
            padding: 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        
        .info-value {
            font-size: 14px;
            color: #222;
            font-weight: 500;
        }
        
        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px;
        }
        
        .container-xl {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .page-body {
            padding: 20px 0;
        }
        
        .page-header {
            display: none;
        }
        
        .page-header.d-print-none {
            display: block;
            background: white;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        
        .table-wrapper {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .table-header-principal {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table-header-principal th {
            padding: 14px 8px;
            text-align: center;
            font-weight: 600;
            color: #222;
            border-right: 1px solid #dee2e6;
        }
        
        .table-header-principal th:first-child {
            text-align: center;
            border-right: 2px solid #dee2e6;
            width: 110px;
        }
        
        .category-header-row th {
            background: #fafafa;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            color: #333;
            text-align: center;
            padding: 10px 8px;
            border-right: 1px solid #dee2e6;
            border-bottom: 2px solid #dee2e6;
        }
        
        .category-header-row th:first-child {
            border-right: 2px solid #dee2e6;
        }
        
        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background-color 0.15s;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        td {
            padding: 11px 8px;
            text-align: center;
            border-right: 1px solid #eee;
            vertical-align: middle;
        }
        
        td:first-child {
            text-align: left;
            font-weight: 500;
            color: #222;
            border-right: 2px solid #dee2e6;
            width: 90px;
        }
        
        .cell-empty {
            color: #aaa;
            font-style: italic;
        }
        
        .cell-content {
            line-height: 1.6;
        }

        .link-peso {
            border: 0;
            background: transparent;
            color: #0d6efd;
            padding: 0;
            font-size: inherit;
            line-height: inherit;
            text-decoration: underline;
            cursor: pointer;
        }

        .link-peso:hover {
            color: #0a58ca;
        }

        .legend-title {
            margin: 0;
            padding: 14px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #333;
        }

        .normativas-box {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 16px;
            padding: 12px 14px;
        }

        .normativas-box h4 {
            margin: 0 0 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .normativas-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .normativas-box li {
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .legend-table th {
            background: #fafafa;
            font-weight: 700;
            font-size: 12px;
            text-align: center;
            padding: 10px 8px;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
        }

        .legend-table td {
            padding: 9px 8px;
            text-align: center;
            border-right: 1px solid #eee;
        }

        .legend-table td:nth-child(2) {
            text-align: left;
            font-weight: 500;
        }
        
        .footer-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 10px 26px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-back {
            background-color: #e9ecef;
            color: #333;
            border: 1px solid #dee2e6;
        }
        
        .btn-back:hover {
            background-color: #dee2e6;
            color: #222;
        }
        
        .btn-print {
            background-color: #4caf50;
            color: white;
        }
        
        .btn-print:hover {
            background-color: #45a049;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .breadcrumb-nav,
            .footer-actions {
                display: none;
            }
            .table-wrapper {
                box-shadow: none;
                page-break-inside: avoid;
            }
            .page-header {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>


    <div class="container-xl" style="padding-top: 20px;">
        <div class="breadcrumb-nav">
            <a href="?module=laboratorio&action=residuo&view=informe_residuos">← RESIDUOS</a>
        </div>
    </div>


<div class="page-body">
    <div class="container-xl">
            <div class="title-section">
                <span class="page-title">INFORME DE RESIDUOS</span>
                <span class="badge-sst">CÓDIGO SST-16</span>
            </div>
    
        <div class="header-info">
            <div class="info-item">
                <div class="info-label">UBICACIÓN</div>
                <div class="info-value"><?php echo htmlspecialchars($cabecera['Ubicacion']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">RESPONSABLE</div>
                <div class="info-value"><?php echo htmlspecialchars($cabecera['Responsable'] ?? 'No asignado'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">FECHA</div>
                <div class="info-value"><?php echo $mes_nombre . ' del ' . $anio; ?></div>
            </div>
        </div>

        <div class="normativas-box">
            <h4>Normativas aplicables</h4>
            <?php if (empty($normativas)): ?>
                <div class="cell-empty">Sin normativa asociada</div>
            <?php else: ?>
                <ul>
                    <?php foreach ($normativas as $n): ?>
                        <li>
                            <?php
                            $lineaNorma = trim((string)$n['Nombre_Ley']);
                            if (!empty($n['Descripcion'])) {
                                $lineaNorma .= ' - ' . trim((string)$n['Descripcion']);
                            }
                            echo htmlspecialchars($lineaNorma);
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr class="table-header-principal">
                        <th>Fecha</th>
                        <?php if (!empty($subcategoriasNoPeligrosas)): ?>
                            <th colspan="<?php echo count($subcategoriasNoPeligrosas); ?>" style="text-align: center;">RESIDUOS NO PELIGROSOS</th>
                        <?php endif; ?>
                        <?php if (!empty($subcategoriasPeligrosas)): ?>
                            <th colspan="<?php echo count($subcategoriasPeligrosas); ?>" style="text-align: center;">RESIDUOS PELIGROSOS</th>
                        <?php endif; ?>
                    </tr>
                    <tr class="category-header-row">
                        <th></th>
                        <?php 
                        foreach ($subcategoriasNoPeligrosas as $subcat => $_val) {
                            echo '<th>' . strtoupper($subcat) . '</th>';
                        }
                        foreach ($subcategoriasPeligrosas as $subcat => $_val) {
                            echo '<th>' . strtoupper($subcat) . '</th>';
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todasLasFechas as $fecha): ?>
                        <tr>
                            <td><?php echo $fecha; ?></td>
                            <?php 
                            $datosDelDia = $detallesPorFecha[$fecha] ?? [];
                            $itemsPorSubcat = [];
                            
                            // Mapear subcategorías de BD a subcategorías FIJAS usando tabla de mapeo
                            foreach ($datosDelDia as $item) {
                                $subcatBD = trim($item['Subcategoria'] ?? '');
                                $subcatBDNormal = strtolower($subcatBD); // Normalizar a lowercase para búsqueda
                                
                                // Buscar la subcategoría normalizada en el mapeo
                                $subcatFija = null;
                                if (isset($mapeoSubcategorias[$subcatBDNormal])) {
                                    $subcatFija = $mapeoSubcategorias[$subcatBDNormal];
                                }
                                
                                if ($subcatFija) {
                                    if (!isset($itemsPorSubcat[$subcatFija])) {
                                        $itemsPorSubcat[$subcatFija] = [];
                                    }
                                    $itemsPorSubcat[$subcatFija][] = $item;
                                }
                            }
                            
                            // Mostrar residuos NO PELIGROSOS
                            foreach ($subcategoriasNoPeligrosas as $subcat => $_val) {
                                echo '<td>';
                                if (isset($itemsPorSubcat[$subcat]) && !empty($itemsPorSubcat[$subcat])) {
                                    echo '<div class="cell-content">';
                                    foreach ($itemsPorSubcat[$subcat] as $item) {
                                        $idRes = intval($item['Id_Residuo_Cat'] ?? 0);
                                        $fechaIso = ($item['Fecha_Dia'] instanceof DateTime) ? $item['Fecha_Dia']->format('Y-m-d') : '';
                                        $nombreItem = trim((string)($item['Nombre_Item'] ?? ''));
                                        echo '(' . $idRes . ') ';
                                        echo '<button type="button" class="link-peso js-open-ingresos" '
                                            . 'data-id-registro="' . intval($id_registro) . '" '
                                            . 'data-id-residuo="' . $idRes . '" '
                                            . 'data-fecha="' . htmlspecialchars($fechaIso, ENT_QUOTES, 'UTF-8') . '" '
                                            . 'data-nombre-residuo="' . htmlspecialchars($nombreItem, ENT_QUOTES, 'UTF-8') . '">'
                                            . formatearPesoGramosVista($item['Total_Peso'] ?? 0)
                                            . '</button><br>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<span class="cell-empty">-</span>';
                                }
                                echo '</td>';
                            }
                            
                            // Mostrar residuos PELIGROSOS
                            foreach ($subcategoriasPeligrosas as $subcat => $_val) {
                                echo '<td>';
                                if (isset($itemsPorSubcat[$subcat]) && !empty($itemsPorSubcat[$subcat])) {
                                    echo '<div class="cell-content">';
                                    foreach ($itemsPorSubcat[$subcat] as $item) {
                                        $idRes = intval($item['Id_Residuo_Cat'] ?? 0);
                                        $fechaIso = ($item['Fecha_Dia'] instanceof DateTime) ? $item['Fecha_Dia']->format('Y-m-d') : '';
                                        $nombreItem = trim((string)($item['Nombre_Item'] ?? ''));
                                        echo '(' . $idRes . ') ';
                                        echo '<button type="button" class="link-peso js-open-ingresos" '
                                            . 'data-id-registro="' . intval($id_registro) . '" '
                                            . 'data-id-residuo="' . $idRes . '" '
                                            . 'data-fecha="' . htmlspecialchars($fechaIso, ENT_QUOTES, 'UTF-8') . '" '
                                            . 'data-nombre-residuo="' . htmlspecialchars($nombreItem, ENT_QUOTES, 'UTF-8') . '">'
                                            . formatearPesoGramosVista($item['Total_Peso'] ?? 0)
                                            . '</button><br>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<span class="cell-empty">-</span>';
                                }
                                echo '</td>';
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-wrapper">
            <h4 class="legend-title">Leyenda de Residuos</h4>
            <table class="legend-table">
                <thead>
                    <tr>
                        <th style="width: 130px;">Codigo</th>
                        <th>Residuo</th>
                        <th style="width: 120px;">Peso</th>
                        <th style="width: 120px;">Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($residuosLeyenda)): ?>
                        <tr>
                            <td colspan="4" class="cell-empty">No hay residuos activos en catalogo</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($residuosLeyenda as $residuo): ?>
                            <?php
                            $idRes = intval($residuo['Id_Residuo_Cat'] ?? 0);
                            $pesoRes = floatval($totalesPorResiduo[$idRes] ?? 0);
                            $codigo = trim((string)($residuo['Codigo_Item'] ?? ''));
                            $nombreRes = trim((string)($residuo['Nombre_Item'] ?? ''));
                            $unidadRes = trim((string)($residuo['Unidad_Referencia'] ?? ''));
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($codigo !== '' ? $codigo : '-'); ?></td>
                                <td><?php echo htmlspecialchars($nombreRes !== '' ? $nombreRes : ('Residuo #' . $idRes)); ?></td>
                                <td><?php echo number_format($pesoRes, 3); ?></td>
                                <td><?php echo htmlspecialchars($unidadRes !== '' ? $unidadRes : 'Kg'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="row mt-4 mb-3" style="gap: 10px;">
            <div class="col-auto">
                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()" style="font-size: 0.95em; padding: 8px 14px;">
                    <i class="ti ti-arrow-left"></i> Volver
                </button>
                <button type="button" class="btn btn-primary" onclick="abrirIngresoManualGlobal()" style="font-size: 0.95em; padding: 8px 14px; margin-left: 10px;">
                    <i class="ti ti-plus"></i> Agregar Ingreso Manual
                </button>
                <button onclick="descargarReporteExcel(<?php echo $id_registro; ?>)" class="btn btn-success" style="background: #28a745; border: none; font-size: 0.95em; padding: 8px 18px; margin-left: 15px;">
                    <i class="ti ti-download"></i> Descargar Reporte
                </button>
            </div>


        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const idRegistroRes = <?php echo intval($id_registro); ?>;
const residuosCatalogo = <?php echo json_encode($residuosCatalogoJs, JSON_UNESCAPED_UNICODE); ?>;
let ultimoContextoDetalle = null;

function escapeHtml(texto) {
    return String(texto || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function construirOpcionesResiduoHtml(idSeleccionado) {
    let html = '<option value="">Seleccione residuo...</option>';
    (residuosCatalogo || []).forEach(function(r) {
        const seleccionado = parseInt(idSeleccionado || '0', 10) === parseInt(r.id || '0', 10) ? ' selected' : '';
        const codigo = r.codigo ? ('[' + r.codigo + '] ') : '';
        const unidad = r.unidad ? (' (' + r.unidad + ')') : '';
        html += '<option value="' + parseInt(r.id || '0', 10) + '"' + seleccionado + '>' + escapeHtml(codigo + (r.nombre || ('Residuo #' + r.id)) + unidad) + '</option>';
    });
    return html;
}

function cargarIngresosResiduo(fechaDia, idResiduoCat) {
    const params = new URLSearchParams({
        action: 'listar_ingresos_residuo',
        id_registro: String(idRegistroRes),
        fecha_dia: String(fechaDia || '')
    });

    if (parseInt(idResiduoCat || '0', 10) > 0) {
        params.set('id_residuo_cat', String(parseInt(idResiduoCat, 10)));
    }

    return fetch('modules/laboratorio/residuo/controllers/ResiduoAPI.php?' + params.toString())
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            if (!data || data.success !== true) {
                throw new Error((data && data.message) ? data.message : 'No se pudieron cargar los ingresos');
            }
            return Array.isArray(data.data) ? data.data : [];
        });
}

function renderTablaIngresos(ingresos) {
    if (!Array.isArray(ingresos) || ingresos.length === 0) {
        return '<div class="text-muted">No se encontraron ingresos para el filtro seleccionado.</div>';
    }

    let html = '<div class="table-responsive" style="max-height:320px; overflow:auto;">';
    html += '<table class="table table-sm table-striped">';
    html += '<thead><tr><th>#</th><th>Residuo</th><th>Peso</th><th>Fecha</th><th>Registro</th><th>Usuario</th><th>Acción</th></tr></thead><tbody>';

    ingresos.forEach(function(item, idx) {
        const nombre = (item.codigo_item ? ('[' + item.codigo_item + '] ') : '') + (item.nombre_item || ('Residuo #' + item.id_residuo_cat));
        const unidad = item.unidad || 'g';
        html += '<tr>';
        html += '<td>' + (idx + 1) + '</td>';
        html += '<td>' + escapeHtml(nombre) + '</td>';
        html += '<td>' + escapeHtml(String(item.peso_valor)) + ' ' + escapeHtml(unidad) + '</td>';
        html += '<td>' + escapeHtml(item.fecha_dia || '') + '</td>';
        html += '<td>' + escapeHtml(item.fecha_creacion || '') + '</td>';
        html += '<td>' + escapeHtml(item.usuario_nombre || String(item.usuario_creacion || '')) + '</td>';
        html += '<td><button type="button" class="btn btn-sm btn-outline-primary js-editar-ingreso" '
            + 'data-id-detalle="' + escapeHtml(String(item.id_detalle || 0)) + '" '
            + 'data-id-residuo="' + escapeHtml(String(item.id_residuo_cat || 0)) + '" '
            + 'data-fecha="' + escapeHtml(item.fecha_dia || '') + '" '
            + 'data-peso="' + escapeHtml(String(item.peso_valor || 0)) + '">Editar</button></td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function abrirIngresoManual(opts) {
    const fechaPrefill = (opts && opts.fecha_dia) ? String(opts.fecha_dia) : new Date().toISOString().slice(0, 10);
    const residuoPrefill = (opts && opts.id_residuo_cat) ? parseInt(opts.id_residuo_cat, 10) : 0;

    return Swal.fire({
        title: 'Agregar ingreso manual de residuo',
        width: 640,
        html:
            '<div class="text-start">' +
            '  <label class="form-label">Fecha</label>' +
            '  <input id="swal_fecha_dia" type="date" class="form-control" value="' + escapeHtml(fechaPrefill) + '">' +
            '  <label class="form-label mt-2">Residuo</label>' +
            '  <select id="swal_id_residuo" class="form-select">' + construirOpcionesResiduoHtml(residuoPrefill) + '</select>' +
            '  <label class="form-label mt-2">Cantidad/Peso</label>' +
            '  <input id="swal_peso" type="number" step="0.0001" min="0.0001" class="form-control" placeholder="Ej: 2.5000">' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: 'Guardar ingreso',
        cancelButtonText: 'Cancelar',
        preConfirm: function() {
            const fechaDia = document.getElementById('swal_fecha_dia').value;
            const idResiduo = parseInt(document.getElementById('swal_id_residuo').value || '0', 10);
            const pesoValor = parseFloat(document.getElementById('swal_peso').value || '0');

            if (!fechaDia) {
                Swal.showValidationMessage('Seleccione la fecha');
                return false;
            }
            if (!(idResiduo > 0)) {
                Swal.showValidationMessage('Seleccione el residuo');
                return false;
            }
            if (!(pesoValor > 0)) {
                Swal.showValidationMessage('Ingrese una cantidad válida');
                return false;
            }

            return fetch('modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=agregar_ingreso_manual', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    Id_Registro_Res: idRegistroRes,
                    Id_Residuo_Cat: idResiduo,
                    Fecha_Dia: fechaDia,
                    Peso_Valor: pesoValor
                })
            })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    if (!data || data.success !== true) {
                        throw new Error((data && data.message) ? data.message : 'No se pudo guardar el ingreso manual');
                    }
                    return {
                        fecha_dia: fechaDia,
                        id_residuo_cat: idResiduo,
                        mensaje: data.message || 'Ingreso manual registrado'
                    };
                })
                .catch(function(error) {
                    Swal.showValidationMessage(error.message || 'Error al registrar ingreso manual');
                    return false;
                });
        }
    });
}

function abrirDetalleIngresos(fechaDia, idResiduoCat, nombreResiduo) {
    ultimoContextoDetalle = {
        fechaDia: fechaDia,
        idResiduoCat: idResiduoCat,
        nombreResiduo: nombreResiduo || ''
    };

    Swal.fire({
        title: 'Cargando ingresos...',
        allowOutsideClick: false,
        didOpen: function() { Swal.showLoading(); }
    });

    cargarIngresosResiduo(fechaDia, idResiduoCat)
        .then(function(ingresos) {
            const nombre = nombreResiduo ? (' - ' + nombreResiduo) : '';
            return Swal.fire({
                title: 'Ingresos del ' + fechaDia + nombre,
                width: 980,
                html: renderTablaIngresos(ingresos),
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Cerrar',
                denyButtonText: 'Agregar ingreso manual',
                cancelButtonText: 'Refrescar'
            }).then(function(result) {
                if (result.isDenied) {
                    return abrirIngresoManual({ fecha_dia: fechaDia, id_residuo_cat: idResiduoCat })
                        .then(function(resManual) {
                            if (resManual && resManual.isConfirmed && resManual.value) {
                                return Swal.fire('Éxito', resManual.value.mensaje, 'success').then(function() {
                                    window.location.reload();
                                });
                            }
                            return null;
                        });
                }

                if (result.dismiss === Swal.DismissReason.cancel) {
                    return abrirDetalleIngresos(fechaDia, idResiduoCat, nombreResiduo);
                }

                return null;
            });
        })
        .catch(function(error) {
            Swal.fire('Error', error.message || 'No se pudieron cargar los ingresos', 'error');
        });
}

function abrirIngresoManualGlobal() {
    abrirIngresoManual({}).then(function(result) {
        if (result && result.isConfirmed && result.value) {
            Swal.fire('Éxito', result.value.mensaje, 'success').then(function() {
                window.location.reload();
            });
        }
    });
}

function abrirEditorIngresoManual(idDetalle, idResiduo, fechaDia, pesoValor) {
    return Swal.fire({
        title: 'Editar ingreso de residuo',
        width: 640,
        html:
            '<div class="text-start">' +
            '  <label class="form-label">Fecha</label>' +
            '  <input id="swal_edit_fecha_dia" type="date" class="form-control" value="' + escapeHtml(fechaDia || '') + '">' +
            '  <label class="form-label mt-2">Residuo</label>' +
            '  <select id="swal_edit_id_residuo" class="form-select">' + construirOpcionesResiduoHtml(idResiduo) + '</select>' +
            '  <label class="form-label mt-2">Cantidad/Peso</label>' +
            '  <input id="swal_edit_peso" type="number" step="0.0001" min="0.0001" class="form-control" value="' + escapeHtml(String(pesoValor || '')) + '">' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: 'Guardar cambios',
        cancelButtonText: 'Cancelar',
        preConfirm: function() {
            const fechaEdit = document.getElementById('swal_edit_fecha_dia').value;
            const residuoEdit = parseInt(document.getElementById('swal_edit_id_residuo').value || '0', 10);
            const pesoEdit = parseFloat(document.getElementById('swal_edit_peso').value || '0');

            if (!fechaEdit) {
                Swal.showValidationMessage('Seleccione la fecha');
                return false;
            }
            if (!(residuoEdit > 0)) {
                Swal.showValidationMessage('Seleccione el residuo');
                return false;
            }
            if (!(pesoEdit > 0)) {
                Swal.showValidationMessage('Ingrese una cantidad válida');
                return false;
            }

            return fetch('modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=editar_ingreso_manual', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    Id_Detalle_Res: parseInt(idDetalle || '0', 10),
                    Id_Residuo_Cat: residuoEdit,
                    Fecha_Dia: fechaEdit,
                    Peso_Valor: pesoEdit
                })
            })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    if (!data || data.success !== true) {
                        throw new Error((data && data.message) ? data.message : 'No se pudo editar el ingreso');
                    }
                    return data;
                })
                .catch(function(error) {
                    Swal.showValidationMessage(error.message || 'Error al editar ingreso');
                    return false;
                });
        }
    });
}

$(document).on('click', '.js-open-ingresos', function() {
    const fechaDia = $(this).data('fecha') || '';
    const idResiduo = parseInt($(this).data('id-residuo') || '0', 10);
    const nombreResiduo = $(this).data('nombre-residuo') || '';

    if (!fechaDia || !(idResiduo > 0)) {
        Swal.fire('Advertencia', 'No se pudo identificar el ingreso seleccionado', 'warning');
        return;
    }

    abrirDetalleIngresos(fechaDia, idResiduo, nombreResiduo);
});

$(document).on('click', '.js-editar-ingreso', function() {
    const idDetalle = parseInt($(this).data('id-detalle') || '0', 10);
    const idResiduo = parseInt($(this).data('id-residuo') || '0', 10);
    const fechaDia = $(this).data('fecha') || '';
    const pesoValor = $(this).data('peso') || '';

    if (!(idDetalle > 0)) {
        Swal.fire('Advertencia', 'No se pudo identificar el ingreso a editar', 'warning');
        return;
    }

    abrirEditorIngresoManual(idDetalle, idResiduo, fechaDia, pesoValor).then(function(result) {
        if (result && result.isConfirmed) {
            Swal.fire('Éxito', (result.value && result.value.message) ? result.value.message : 'Ingreso actualizado', 'success').then(function() {
                if (ultimoContextoDetalle && ultimoContextoDetalle.fechaDia) {
                    abrirDetalleIngresos(
                        ultimoContextoDetalle.fechaDia,
                        ultimoContextoDetalle.idResiduoCat,
                        ultimoContextoDetalle.nombreResiduo
                    );
                } else {
                    window.location.reload();
                }
            });
        }
    });
});

function descargarReporteExcel(idRegistro) {
    Swal.fire({
        title: 'Descargando reporte...',
        icon: 'info',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Crear un formulario temporal para descargar el archivo
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'modules/laboratorio/residuo/controllers/ExportarReporteAPI.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id_registro';
    input.value = idRegistro;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    Swal.close();
}
</script>

</body>
</html>