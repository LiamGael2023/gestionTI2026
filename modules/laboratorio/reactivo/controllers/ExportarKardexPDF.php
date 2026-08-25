<?php
/**
 * ExportarKardexPDF.php — Vista imprimible del Kardex de Reactivos
 * Se abre en una ventana nueva; el usuario imprime con Ctrl+P → Guardar como PDF
 */
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$mes  = intval($_GET['mes']  ?? date('m'));
$anio = intval($_GET['anio'] ?? date('Y'));
if ($mes < 1 || $mes > 12)        $mes  = intval(date('m'));
if ($anio < 2000 || $anio > 2100) $anio = intval(date('Y'));

$dias_mes      = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
$meses_nombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio',
                  'Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mes_nombre    = $meses_nombres[$mes - 1];
$fecha_inicio = sprintf('%04d-%02d-01', $anio, $mes);
$ultimo_dia   = $dias_mes;

$conn = Conexion::conectar();
if (!$conn) { http_response_code(500); die('Error de conexion'); }

// ===== REACTIVOS CON SALDOS =====
$sql_reactivos = "
SELECT 
    r.Id_Reactivo, r.Nombre,
    ISNULL(um.Abreviatura, '') AS Unidad_Medida,
    ISNULL(r.Cantidad_Inicial, 0) AS Cantidad_Inicial,
    
    -- Saldo inicial del mes
    ISNULL((
        SELECT TOP 1 mk.Saldo_Resultante 
        FROM laboratorio.Movimiento_Kardex mk 
        WHERE mk.Id_Reactivo = r.Id_Reactivo AND mk.Fecha_Registro < ? AND mk.Activo = 1 
        ORDER BY mk.Fecha_Registro DESC, mk.Id_Movimiento DESC
    ), 0) AS Saldo_Inicial_Mes,

    -- Entradas del mes
    ISNULL((
        SELECT SUM(mk.Cantidad) 
        FROM laboratorio.Movimiento_Kardex mk 
        WHERE mk.Id_Reactivo = r.Id_Reactivo AND mk.Tipo_Movimiento = 'E' 
        AND mk.Fecha_Registro >= ? AND mk.Fecha_Registro < DATEADD(MONTH, 1, ?) AND mk.Activo = 1
    ), 0) AS Entradas_Mes,

    -- Salidas del mes  
    ISNULL((
        SELECT SUM(mk.Cantidad) 
        FROM laboratorio.Movimiento_Kardex mk 
        WHERE mk.Id_Reactivo = r.Id_Reactivo AND mk.Tipo_Movimiento = 'S' 
        AND mk.Fecha_Registro >= ? AND mk.Fecha_Registro < DATEADD(MONTH, 1, ?) AND mk.Activo = 1
    ), 0) AS Salidas_Mes,

    -- Stock actual
    ISNULL(r.Cantidad_Stock, 0) AS Stock_Actual

FROM laboratorio.Reactivo_Lab r 
LEFT JOIN laboratorio.Unidad_Medida um ON r.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1 
WHERE r.Activo = 1 
ORDER BY r.Nombre
";
$stmt = sqlsrv_query($conn, $sql_reactivos, [$fecha_inicio, $fecha_inicio, $fecha_inicio, $fecha_inicio, $fecha_inicio]);
$reactivos = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $row['Saldo_Final_Mes'] = floatval($row['Saldo_Inicial_Mes']) + floatval($row['Entradas_Mes']) - floatval($row['Salidas_Mes']);
        $reactivos[] = $row;
    }
}

// ===== MOVIMIENTOS DEL MES (DETALLE) =====
$sql_mov = "
SELECT 
    mk.Id_Movimiento, mk.Id_Reactivo, mk.Tipo_Movimiento, mk.Cantidad, mk.Concepto, mk.Saldo_Resultante,
    CAST(mk.Fecha_Registro AS DATE) AS Fecha,
    CONVERT(VARCHAR(8), mk.Fecha_Registro, 108) AS Hora,
    rl.Nombre AS Reactivo_Nombre,
    ISNULL(um.Abreviatura, '') AS U_M,
    -- Trazabilidad de salidas
    CASE 
        WHEN mk.Tipo_Movimiento = 'S' AND cp.Id_Proyecto IS NOT NULL THEN 
            ISNULL(pm.Nombre_Proyecto, 'Proyecto') + ' | Muestra #' + CAST(ISNULL(ml.Id_Muestra,0) AS VARCHAR)
        WHEN mk.Tipo_Movimiento = 'S' AND ml2.Id_Cliente IS NOT NULL THEN
            'Cliente: ' + LTRIM(RTRIM(CONCAT(ISNULL(c.Nombres,''),' ',ISNULL(c.Apellido_Paterno,''))))
        WHEN mk.Tipo_Movimiento = 'S' THEN 'Salida manual'
        ELSE ''
    END AS Origen
FROM laboratorio.Movimiento_Kardex mk
INNER JOIN laboratorio.Reactivo_Lab rl ON mk.Id_Reactivo = rl.Id_Reactivo AND rl.Activo = 1
LEFT JOIN laboratorio.Unidad_Medida um ON rl.Id_Unidad_Medida = um.Id_Unidad_Medida
LEFT JOIN laboratorio.Consumo_Reaccion cr ON cr.Id_Movimiento = mk.Id_Movimiento AND cr.Activo = 1
LEFT JOIN laboratorio.Muestra_Producto mp ON mp.Id_Muestra_Producto = cr.Id_Muestra_Producto AND mp.Activo = 1
LEFT JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = mp.Id_Muestra AND ml.Activo = 1
LEFT JOIN laboratorio.Muestra_Lab ml2 ON ml2.Id_Muestra = mp.Id_Muestra
LEFT JOIN laboratorio.Cliente c ON c.Id_Cliente = ml2.Id_Cliente
LEFT JOIN laboratorio.Proyecto_Monitoreo cp ON cp.Id_Proyecto = ml2.Id_Proyecto
LEFT JOIN laboratorio.Proyecto_Monitoreo pm ON pm.Id_Proyecto = cp.Id_Proyecto
WHERE mk.Activo = 1
  AND rl.Activo = 1
  AND mk.Fecha_Registro >= ?
  AND mk.Fecha_Registro < DATEADD(MONTH, 1, ?)
ORDER BY mk.Fecha_Registro DESC, mk.Id_Movimiento DESC
";
$stmt_mov = sqlsrv_query($conn, $sql_mov, [$fecha_inicio, $fecha_inicio]);
$movimientos = [];
$totales = ['E' => 0, 'S' => 0];
if ($stmt_mov) {
    while ($row = sqlsrv_fetch_array($stmt_mov, SQLSRV_FETCH_ASSOC)) {
        $tipo = $row['Tipo_Movimiento'];
        $totales[$tipo] += floatval($row['Cantidad']);
        $movimientos[] = $row;
    }
}

// ===== RESUMEN CONSOLIDADO MENSUAL =====
$sql_resumen = "
SELECT 
    r.Nombre AS Reactivo,
    ISNULL(um.Abreviatura, '') AS UM,
    ISNULL(SUM(CASE WHEN mk.Tipo_Movimiento = 'E' THEN mk.Cantidad ELSE 0 END), 0) AS Total_Entradas,
    ISNULL(SUM(CASE WHEN mk.Tipo_Movimiento = 'S' THEN mk.Cantidad ELSE 0 END), 0) AS Total_Salidas,
    ISNULL(r.Cantidad_Stock, 0) AS Stock_Final
FROM laboratorio.Movimiento_Kardex mk
INNER JOIN laboratorio.Reactivo_Lab r ON mk.Id_Reactivo = r.Id_Reactivo AND r.Activo = 1
LEFT JOIN laboratorio.Unidad_Medida um ON r.Id_Unidad_Medida = um.Id_Unidad_Medida
WHERE mk.Activo = 1
  AND mk.Fecha_Registro >= ?
  AND mk.Fecha_Registro < DATEADD(MONTH, 1, ?)
GROUP BY r.Nombre, ISNULL(um.Abreviatura, ''), r.Cantidad_Stock
ORDER BY Total_Salidas DESC, r.Nombre
";
$stmt_res = sqlsrv_query($conn, $sql_resumen, [$fecha_inicio, $fecha_inicio]);
$resumen = [];
$gran_total_entradas = 0;
$gran_total_salidas = 0;
if ($stmt_res) {
    while ($row = sqlsrv_fetch_array($stmt_res, SQLSRV_FETCH_ASSOC)) {
        $gran_total_entradas += floatval($row['Total_Entradas']);
        $gran_total_salidas += floatval($row['Total_Salidas']);
        $resumen[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>KARDEX <?php echo strtoupper($mes_nombre) . ' ' . $anio; ?></title>
<style>
  @page { size: A4 landscape; margin: 8mm; }
  * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
  body { margin: 0; padding: 0; font-size: 9pt; color: #1a1a1a; }
  
  .header { text-align: center; margin-bottom: 8px; border-bottom: 3px solid #004d99; padding-bottom: 6px; }
  .header h1 { font-size: 14pt; margin: 0 0 2px 0; color: #004d99; }
  .header .sub { font-size: 10pt; color: #555; }
  
  .section-title { font-size: 11pt; font-weight: 700; color: #004d99; margin: 12px 0 5px 0; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
  
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; page-break-inside: avoid; }
  th { background: #004d99; color: white; padding: 5px 4px; font-size: 7.5pt; text-align: center; font-weight: 600; }
  td { padding: 4px; font-size: 8pt; border-bottom: 1px solid #e0e0e0; }
  tr:nth-child(even) td { background: #f8f9fa; }
  
  .num { text-align: right; font-variant-numeric: tabular-nums; }
  .entrada { color: #16a34a; font-weight: 600; }
  .salida { color: #dc2626; font-weight: 600; }
  .origen { font-size: 7pt; color: #888; }
  
  .summary-box { display: flex; gap: 10px; margin-bottom: 10px; }
  .summary-card { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 8px; text-align: center; }
  .summary-card .value { font-size: 18pt; font-weight: 700; }
  .summary-card .label { font-size: 7pt; color: #666; text-transform: uppercase; }
  .card-green { border-left: 4px solid #16a34a; } .card-green .value { color: #16a34a; }
  .card-red { border-left: 4px solid #dc2626; } .card-red .value { color: #dc2626; }
  .card-blue { border-left: 4px solid #004d99; } .card-blue .value { color: #004d99; }
  
  .footer { text-align: center; font-size: 7pt; color: #999; margin-top: 15px; border-top: 1px solid #eee; padding-top: 8px; }
  
  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
  }
</style>
</head>
<body>

<div class="no-print" style="background:#fef3c7;border:1px solid #f59e0b;padding:8px 12px;margin-bottom:10px;border-radius:4px;text-align:center;">
  <strong>🔔 Vista previa de impresión</strong> — Presiona <kbd>Ctrl+P</kbd> y selecciona <em>"Guardar como PDF"</em> para exportar.
  <button onclick="window.print()" style="margin-left:10px;padding:4px 16px;background:#004d99;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:600;">Imprimir / PDF</button>
  <button onclick="window.close()" style="margin-left:6px;padding:4px 12px;background:#eee;border:1px solid #ccc;border-radius:4px;cursor:pointer;">Cerrar</button>
</div>

<div class="header">
  <h1>📋 KARDEX DE REACTIVOS DE LABORATORIO</h1>
  <div class="sub"><?php echo strtoupper($mes_nombre) . ' ' . $anio; ?> — Proyecto Especial CHAVIMOCHIC</div>
</div>

<!-- RESUMEN -->
<div class="summary-box">
  <div class="summary-card card-green">
    <div class="value">+<?php echo number_format($gran_total_entradas, 2); ?></div>
    <div class="label">Total Entradas</div>
  </div>
  <div class="summary-card card-red">
    <div class="value">-<?php echo number_format($gran_total_salidas, 2); ?></div>
    <div class="label">Total Salidas</div>
  </div>
  <div class="summary-card card-blue">
    <div class="value"><?php echo count($reactivos); ?></div>
    <div class="label">Reactivos Activos</div>
  </div>
</div>

<!-- RESUMEN POR REACTIVO -->
<?php if (!empty($resumen)): ?>
<div class="section-title">📊 CONSUMO MENSUAL POR REACTIVO</div>
<table>
  <thead>
    <tr><th>Reactivo</th><th>U.M.</th><th class="num">Entradas</th><th class="num">Salidas</th><th class="num">Stock Final</th></tr>
  </thead>
  <tbody>
    <?php foreach ($resumen as $r): ?>
    <tr>
      <td><?php echo htmlspecialchars($r['Reactivo']); ?></td>
      <td><?php echo htmlspecialchars($r['UM']); ?></td>
      <td class="num entrada"><?php echo floatval($r['Total_Entradas']) > 0 ? '+' . number_format(floatval($r['Total_Entradas']), 2) : '-'; ?></td>
      <td class="num salida"><?php echo floatval($r['Total_Salidas']) > 0 ? '-' . number_format(floatval($r['Total_Salidas']), 2) : '-'; ?></td>
      <td class="num"><strong><?php echo number_format(floatval($r['Stock_Final']), 2); ?></strong></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- DETALLE DE MOVIMIENTOS -->
<div class="section-title">📝 DETALLE DE MOVIMIENTOS DEL MES</div>
<?php if (!empty($movimientos)): ?>
<table>
  <thead>
    <tr><th>Fecha</th><th>Hora</th><th>Reactivo</th><th>Tipo</th><th class="num">Cantidad</th><th>Concepto / Origen</th><th class="num">Saldo</th></tr>
  </thead>
  <tbody>
    <?php foreach ($movimientos as $m): ?>
    <tr>
      <td><?php echo $m['Fecha'] instanceof DateTime ? $m['Fecha']->format('d/m/Y') : substr($m['Fecha'] ?? '', 0, 10); ?></td>
      <td><?php echo $m['Hora']; ?></td>
      <td><?php echo htmlspecialchars($m['Reactivo_Nombre']); ?></td>
      <td style="text-align:center;">
        <strong class="<?php echo $m['Tipo_Movimiento'] === 'E' ? 'entrada' : 'salida'; ?>">
          <?php echo $m['Tipo_Movimiento'] === 'E' ? 'INGRESO' : 'SALIDA'; ?>
        </strong>
      </td>
      <td class="num"><?php echo number_format(floatval($m['Cantidad']), 4); ?> <?php echo htmlspecialchars($m['U_M']); ?></td>
      <td>
        <?php echo htmlspecialchars($m['Concepto'] ?? ''); ?>
        <?php if (!empty($m['Origen'])): ?>
          <br><span class="origen">📎 <?php echo htmlspecialchars($m['Origen']); ?></span>
        <?php endif; ?>
      </td>
      <td class="num"><?php echo number_format(floatval($m['Saldo_Resultante']), 2); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
  <p style="color:#999;text-align:center;padding:20px;">No hay movimientos registrados en este mes.</p>
<?php endif; ?>

<!-- KARDEX COMPLETO (TABLA RESUMEN DIARIA) -->
<div class="section-title" style="page-break-before: always;">📅 KARDEX DIARIO — <?php echo strtoupper($mes_nombre) . ' ' . $anio; ?></div>
<?php if (!empty($reactivos)): ?>
<div style="overflow-x:auto;">
<table style="min-width:1200px;font-size:7pt;">
  <thead>
    <tr>
      <th rowspan="2" style="width:30px;">#</th>
      <th rowspan="2" style="text-align:left;width:150px;">Reactivo</th>
      <th rowspan="2" style="width:40px;">UM</th>
      <th rowspan="2" style="width:50px;">Inicial</th>
      <?php for ($d = 1; $d <= $dias_mes; $d++): ?>
        <th style="width:32px;"><?php echo str_pad($d,2,'0',STR_PAD_LEFT); ?></th>
      <?php endfor; ?>
      <th rowspan="2" style="width:50px;background:#16a34a;">Tot.E</th>
      <th rowspan="2" style="width:50px;background:#dc2626;">Tot.S</th>
      <th rowspan="2" style="width:55px;">Final</th>
    </tr>
    <tr>
      <?php for ($d = 1; $d <= $dias_mes; $d++): ?>
        <th style="font-size:5pt;font-weight:normal;">E/S</th>
      <?php endfor; ?>
    </tr>
  </thead>
  <tbody>
    <?php 
    // Obtener movimientos diarios agrupados
    $mov_diario = [];
    $stmt_dia = sqlsrv_query($conn,
        "SELECT Id_Reactivo, Tipo_Movimiento, SUM(Cantidad) AS Cantidad, DAY(Fecha_Registro) AS Dia
         FROM laboratorio.Movimiento_Kardex
         WHERE Activo=1 AND Fecha_Registro >= ? AND Fecha_Registro < DATEADD(MONTH,1,?)
         GROUP BY Id_Reactivo, Tipo_Movimiento, DAY(Fecha_Registro)",
        [$fecha_inicio, $fecha_inicio]);
    if ($stmt_dia) {
        while ($r = sqlsrv_fetch_array($stmt_dia, SQLSRV_FETCH_ASSOC)) {
            $t = $r['Tipo_Movimiento'];
            $mov_diario[$r['Id_Reactivo']][$r['Dia']][$t] = floatval($r['Cantidad']);
        }
    }
    
    foreach ($reactivos as $idx => $rx): 
        $idr = $rx['Id_Reactivo'];
    ?>
    <tr>
      <td style="text-align:center;"><?php echo $idx + 1; ?></td>
      <td><?php echo htmlspecialchars($rx['Nombre']); ?></td>
      <td style="text-align:center;"><?php echo htmlspecialchars($rx['Unidad_Medida']); ?></td>
      <td class="num"><?php echo number_format(floatval($rx['Saldo_Inicial_Mes']), 1); ?></td>
      <?php 
      $totE = 0; $totS = 0;
      for ($d = 1; $d <= $dias_mes; $d++): 
          $e = $mov_diario[$idr][$d]['E'] ?? 0;
          $s = $mov_diario[$idr][$d]['S'] ?? 0;
          $totE += $e; $totS += $s;
      ?>
        <td class="num" style="font-size:6pt;">
          <?php if ($e > 0 && $s > 0): ?>
            <span class="entrada">+<?php echo number_format($e,1); ?></span><br><span class="salida">-<?php echo number_format($s,1); ?></span>
          <?php elseif ($e > 0): ?>
            <span class="entrada">+<?php echo number_format($e,1); ?></span>
          <?php elseif ($s > 0): ?>
            <span class="salida">-<?php echo number_format($s,1); ?></span>
          <?php else: ?>
            ·
          <?php endif; ?>
        </td>
      <?php endfor; ?>
      <td class="num entrada"><?php echo $totE > 0 ? number_format($totE,1) : ''; ?></td>
      <td class="num salida"><?php echo $totS > 0 ? number_format($totS,1) : ''; ?></td>
      <td class="num"><strong><?php echo number_format(floatval($rx['Saldo_Final_Mes']), 1); ?></strong></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<div class="footer">
  Generado el <?php echo date('d/m/Y H:i:s'); ?> — Sistema GestionTI 2026 — Laboratorio CHAVIMOCHIC
</div>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>
<?php
while (ob_get_level() > 0) ob_end_flush();
