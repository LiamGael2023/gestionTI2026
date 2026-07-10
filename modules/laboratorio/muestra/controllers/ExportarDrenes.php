<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$autoloadLibs = $base_path . '/libs/vendor/autoload.php';
if (!file_exists($autoloadLibs)) {
    http_response_code(500);
    die('No se encontro la libreria de exportacion');
}
require_once $autoloadLibs;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ============================================================
// HELPERS
// ============================================================
function _dre_sinTildes(string $s): string {
    $b = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü','ñ','Ñ'];
    $r = ['a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U','n','N'];
    return str_replace($b, $r, $s);
}

function _dre_normCat(string $cat): string {
    $c = strtoupper(_dre_sinTildes(trim($cat)));
    if (strpos($c, 'FISIC')       !== false) return 'FISICO';
    if (strpos($c, 'QUIMIC')      !== false) return 'QUIMICO';
    if (strpos($c, 'MICROBIOLOG') !== false) return 'MICROBIOLOGICO';
    return ($c !== '') ? $c : 'OTROS';
}

function _dre_catLabel(string $normCat): string {
    $m = [
        'FISICO'         => 'ANÁLISIS FÍSICOS',
        'QUIMICO'        => 'ANÁLISIS QUÍMICOS',
        'MICROBIOLOGICO' => 'ANÁLISIS MICROBIOLÓGICOS',
    ];
    return $m[$normCat] ?? ('ANÁLISIS ' . $normCat);
}

function _dre_fmtNum($v): string {
    if ($v === null || trim((string)$v) === '') return '';
    $s = rtrim(rtrim(number_format((float)$v, 6, '.', ''), '0'), '.');
    return ($s === '' || $s === '-') ? '0' : $s;
}

function _dre_fmtLim($min, $max): string {
    $mi = ($min !== null && trim((string)$min) !== '') ? _dre_fmtNum($min) : '';
    $ma = ($max !== null && trim((string)$max) !== '') ? _dre_fmtNum($max) : '';
    if ($mi !== '' && $ma !== '') return $mi . ' - ' . $ma;
    if ($ma !== '') return $ma;
    if ($mi !== '') return $mi;
    return '-';
}

function _dre_parseNum($v): ?float {
    if ($v === null) return null;
    $t = trim(str_replace(',', '.', (string)$v));
    if ($t === '' || $t === '-') return null;
    $t = preg_replace('/[^0-9.\-]/', '', $t);
    return (is_numeric($t) && $t !== '') ? floatval($t) : null;
}

// ============================================================
// INPUT
// ============================================================
$id_proyecto = intval($_GET['id_proyecto'] ?? 0);
if ($id_proyecto <= 0) { http_response_code(400); die('Proyecto invalido'); }

$normativa_nombre = trim((string)($_GET['normativa_nombre'] ?? ''));
if ($normativa_nombre === '') $normativa_nombre = 'DS N°004-2017 MINAM';

$categorias_sel = [];
if (isset($_GET['categorias']) && is_array($_GET['categorias'])) {
    foreach ($_GET['categorias'] as $c) {
        $cat = trim((string)$c);
        if ($cat !== '') $categorias_sel[] = $cat;
    }
}
if (empty($categorias_sel)) {
    $categorias_sel = ['Riego de Vegetales', 'Consumo de Animales'];
}

// ============================================================
// BASE DE DATOS
// ============================================================
$conn = Conexion::conectar();
if (!$conn) { http_response_code(500); die('Error de conexion'); }

// 1. Proyecto (debe ser tipo Drene)
$stmtP = sqlsrv_query($conn,
    "SELECT TOP 1 Id_Proyecto, Nombre_Proyecto, Valle, Temporada, Fecha_Inicio
     FROM laboratorio.Proyecto_Monitoreo
     WHERE Id_Proyecto = ? AND Activo = 1 AND Es_Drene = 1",
    [$id_proyecto]);
if (!$stmtP) { http_response_code(500); die('Error BD: proyecto'); }
$proyecto = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC);
if (!$proyecto) { http_response_code(404); die('Proyecto Drenes no encontrado'); }

// 2. Fuentes (Nivel_Agua — nombre individual de cada dren)
$stmtF = sqlsrv_query($conn,
    "SELECT DISTINCT da.Nivel_Agua
     FROM laboratorio.Muestra_Lab m
     INNER JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra
     WHERE m.Id_Proyecto = ? AND m.Activo = 1 AND da.Activo = 1
       AND da.Nivel_Agua IS NOT NULL AND LTRIM(RTRIM(da.Nivel_Agua)) <> ''
     ORDER BY da.Nivel_Agua",
    [$id_proyecto]);
$fuentes = [];
if ($stmtF) {
    while ($r = sqlsrv_fetch_array($stmtF, SQLSRV_FETCH_ASSOC)) {
        $fuentes[] = trim((string)$r['Nivel_Agua']);
    }
}
if (empty($fuentes)) { http_response_code(409); die('Sin fuentes de dren definidas para exportar'); }

// 3. Parametros agrupados por categoria
$stmtPa = sqlsrv_query($conn,
    "SELECT sub.Id_Parametro, sub.Nombre, sub.Unidad_Medida, sub.Categoria
     FROM (
         SELECT DISTINCT pa.Id_Parametro, pa.Nombre, pa.Unidad_Medida,
                ISNULL(pa.Categoria, 'Otros') AS Categoria
         FROM laboratorio.Parametro_Analisis pa
         INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Servicio = pa.Id_Servicio
         INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
         WHERE ml.Id_Proyecto = ? AND ml.Activo = 1 AND sa.Activo = 1 AND pa.Activo = 1
     ) sub
     ORDER BY sub.Categoria, sub.Nombre",
    [$id_proyecto]);
if (!$stmtPa) { http_response_code(500); die('Error BD: parametros'); }

$paramsPorCat = [];
$paramIds     = [];
while ($r = sqlsrv_fetch_array($stmtPa, SQLSRV_FETCH_ASSOC)) {
    $id = intval($r['Id_Parametro'] ?? 0);
    if ($id <= 0) continue;
    $cat = _dre_normCat(trim((string)($r['Categoria'] ?? '')));
    $paramsPorCat[$cat][] = [
        'id'     => $id,
        'nombre' => trim((string)($r['Nombre'] ?? '')),
        'unidad' => trim((string)($r['Unidad_Medida'] ?? '-')),
    ];
    $paramIds[] = $id;
}
if (empty($paramIds)) { http_response_code(409); die('Sin parametros asociados al proyecto'); }

$phP    = implode(',', array_fill(0, count($paramIds), '?'));
$phCats = implode(',', array_fill(0, count($categorias_sel), '?'));

// 4. Resultados [nivel_agua][id_param] = valor
$resultados = [];
$stmtR = sqlsrv_query($conn,
    "SELECT da.Nivel_Agua, ra.Id_Parametro,
            MAX(CAST(ra.Valor_Hallado AS NVARCHAR(255))) AS Valor
     FROM laboratorio.Resultado_Analisis ra
     INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
     INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
     INNER JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = ml.Id_Muestra
     WHERE ml.Id_Proyecto = ? AND ml.Activo = 1 AND sa.Activo = 1 AND ra.Activo = 1
       AND da.Nivel_Agua IS NOT NULL AND ra.Id_Parametro IN ($phP)
     GROUP BY da.Nivel_Agua, ra.Id_Parametro",
    array_merge([$id_proyecto], $paramIds));
if ($stmtR) {
    while ($r = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC)) {
        $f = trim((string)($r['Nivel_Agua'] ?? ''));
        $p = intval($r['Id_Parametro'] ?? 0);
        $v = trim((string)($r['Valor'] ?? ''));
        if ($f !== '' && $p > 0) {
            $resultados[$f][$p] = ($v === '' ? '-' : $v);
        }
    }
}

// 5. Limites por categorias seleccionadas: $limites[$id_param][$descripcion]
$limites = [];
$stmtL = sqlsrv_query($conn,
    "SELECT Id_Parametro, Valor_Min, Valor_Max, LTRIM(RTRIM(Descripcion)) AS Descripcion
     FROM laboratorio.Limite_Legal
     WHERE Activo = 1 AND Id_Parametro IN ($phP) AND Descripcion IN ($phCats)",
    array_merge($paramIds, $categorias_sel));
if ($stmtL) {
    while ($r = sqlsrv_fetch_array($stmtL, SQLSRV_FETCH_ASSOC)) {
        $id   = intval($r['Id_Parametro'] ?? 0);
        $desc = trim((string)($r['Descripcion'] ?? ''));
        $limites[$id][$desc] = [
            'texto' => _dre_fmtLim($r['Valor_Min'], $r['Valor_Max']),
            'min'   => $r['Valor_Min'],
            'max'   => $r['Valor_Max'],
        ];
    }
}

$excedeLimite = function (int $idParam, $valor) use ($limites, $categorias_sel): bool {
    $n = _dre_parseNum($valor);
    if ($n === null) return false;
    foreach ($categorias_sel as $cat) {
        $info = $limites[$idParam][$cat] ?? null;
        if (!$info) continue;
        $mx = _dre_parseNum($info['max'] ?? null);
        $mn = _dre_parseNum($info['min'] ?? null);
        if ($mx !== null && $n > $mx) return true;
        if ($mn !== null && $n < $mn) return true;
    }
    return false;
};

// ============================================================
// LAYOUT DE COLUMNAS
// A(1):  margen blanco
// B(2):  Parámetros
// C(3):  Unidad de Medida
// D(4) .. D+nCats-1: columnas de límites (una por categoría seleccionada)
// D+nCats .. D+nCats+nFuentes-1: columnas de resultados (una por dren)
// D+nCats+nFuentes: MAX
// D+nCats+nFuentes+1: MIN
// ============================================================
$nCats    = count($categorias_sel);
$nFuentes = count($fuentes);

$colLimStart = 4;                        // D
$colLimEnd   = 3 + $nCats;               // D si 1 cat, E si 2, etc.
$colFStart   = 3 + $nCats + 1;           // primera columna de resultados
$colFEnd     = $colFStart + $nFuentes - 1;
$colMax      = $colFEnd + 1;
$colMin      = $colMax + 1;
$colLast     = $colMin;

$letLimStart = Coordinate::stringFromColumnIndex($colLimStart);
$letLimEnd   = Coordinate::stringFromColumnIndex($colLimEnd);
$letFStart   = Coordinate::stringFromColumnIndex($colFStart);
$letFEnd     = Coordinate::stringFromColumnIndex($colFEnd);
$letMax      = Coordinate::stringFromColumnIndex($colMax);
$letMin      = Coordinate::stringFromColumnIndex($colMin);
$letLast     = Coordinate::stringFromColumnIndex($colLast);

// ============================================================
// CONSTRUIR SPREADSHEET
// ============================================================
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Resultados Drenes');

$C_AZUL_OSC    = 'FF3483CC';  // Azul oscuro: título, cabeceras principales
$C_AZUL_MED    = 'FF6CB0F0';  // Azul medio: secciones, normativa, MAX/MIN
$C_CELESTE     = 'FFC4DFF7';  // Celeste: nombres de nivel (F7+) y celdas de resultados
$C_PARAM_LIGHT = 'FFDCE6F1';  // Celeste muy claro: nombres de parámetros
$C_BLANCO      = 'FFFFFFFF';  // Blanco: bordes y fondo de límites/unidades
$C_NEGRO       = 'FF000000';
$C_ROJO        = 'FFFF0000';

// Bordes blancos para todas las celdas
$bBlancos = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $C_BLANCO]]];

$applyStyle = function (string $rango, array $arr) use ($sheet) {
    $sheet->getStyle($rango)->applyFromArray($arr);
};

// Estilo cabecera oscura
$mkOsc = function (int $size = 10) use ($C_AZUL_OSC, $C_BLANCO, $bBlancos): array {
    return [
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_AZUL_OSC]],
        'font'      => ['bold' => true, 'color' => ['argb' => $C_BLANCO], 'size' => $size],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => $bBlancos,
    ];
};

// Estilo cabecera media
$mkMed = function (int $size = 10) use ($C_AZUL_MED, $C_BLANCO, $bBlancos): array {
    return [
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_AZUL_MED]],
        'font'      => ['bold' => true, 'color' => ['argb' => $C_BLANCO], 'size' => $size],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => $bBlancos,
    ];
};

// Estilo celda nombre de nivel (F7+): celeste con texto negro
$stNivel = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_CELESTE]],
    'font'      => ['bold' => true, 'color' => ['argb' => $C_NEGRO], 'size' => 9],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders'   => $bBlancos,
];

// Estilo celda resultado: celeste
$stResultado = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_CELESTE]],
    'font'      => ['bold' => false, 'color' => ['argb' => $C_NEGRO], 'size' => 9],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders'   => $bBlancos,
];

// Estilo nombre de parámetro: celeste muy claro
$stParamNombre = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_PARAM_LIGHT]],
    'font'      => ['bold' => true, 'color' => ['argb' => $C_NEGRO], 'size' => 9],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1, 'wrapText' => true],
    'borders'   => $bBlancos,
];

// Estilo dato blanco (unidades, límites)
$stDato = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_BLANCO]],
    'font'      => ['bold' => false, 'color' => ['argb' => $C_NEGRO], 'size' => 9],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders'   => $bBlancos,
];

// ============================================================
// FILAS 1-3: margen visual
// ============================================================
foreach ([1, 2, 3] as $mr) { $sheet->getRowDimension($mr)->setRowHeight(5); }

// ============================================================
// FILA 4: TÍTULO
// ============================================================
$titulo = 'RESULTADOS DE LA CALIDAD DE AGUA DRENADA ' . strtoupper(trim((string)($proyecto['Nombre_Proyecto'] ?? '')));
$sheet->mergeCells("B4:{$letLast}4");
$sheet->getCell('B4')->setValue($titulo);
$applyStyle("B4:{$letLast}4", $mkOsc(11));
$sheet->getRowDimension(4)->setRowHeight(34);

// ============================================================
// FILA 5: FECHA INICIO
// ============================================================
$sheet->mergeCells("B5:{$letLast}5");

$fecha_inicio = '';
if ($proyecto['Fecha_Inicio']) {
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $mes = intval($proyecto['Fecha_Inicio']->format('n')) - 1;
    $año = $proyecto['Fecha_Inicio']->format('Y');
    $fecha_inicio = strtoupper($meses[$mes]) . ' ' . $año;
}
$sheet->getCell('B5')->setValue($fecha_inicio);
$applyStyle("B5:{$letLast}5", $mkOsc(10));
$sheet->getRowDimension(5)->setRowHeight(20);

// ============================================================
// FILAS 6-7: CABECERAS
// B6:B7 merged → "PARÁMETROS"
// C6:C7 merged → "Unidad de Medida"
// D6:(D+nCats-1)6 merged → normativa_nombre  (azul oscuro)
// (D+nCats)6:last6 merged → "RESULTADOS"     (azul medio)
// D7...(D+nCats-1)7 → nombres de categorías  (azul medio)
// F7...last-2 7 → nombres de fuentes (drenes) (celeste)
// last-1 7 → MAX  /  last 7 → MIN             (azul medio)
// ============================================================
$sheet->mergeCells('B6:B7');
$sheet->getCell('B6')->setValue('PARÁMETROS');
$applyStyle('B6:B7', $mkOsc());

$sheet->mergeCells('C6:C7');
$sheet->getCell('C6')->setValue('Unidad de Medida');
$applyStyle('C6:C7', $mkOsc());

// Normativa (D6 a letLimEnd6, merged si hay más de 1 categoría)
if ($nCats > 1) {
    $sheet->mergeCells("{$letLimStart}6:{$letLimEnd}6");
}
$sheet->getCell("{$letLimStart}6")->setValue($normativa_nombre);
$applyStyle("{$letLimStart}6:{$letLimEnd}6", $mkOsc());

// RESULTADOS (letFStart6 a letLast6)
$sheet->mergeCells("{$letFStart}6:{$letLast}6");
$sheet->getCell("{$letFStart}6")->setValue('RESULTADOS');
$applyStyle("{$letFStart}6:{$letLast}6", $mkMed());

$sheet->getRowDimension(6)->setRowHeight(20);

// Fila 7: categorías de límite + nombres de drenes + MAX/MIN
for ($i = 0; $i < $nCats; $i++) {
    $colLtr = Coordinate::stringFromColumnIndex($colLimStart + $i);
    $sheet->getCell("{$colLtr}7")->setValue($categorias_sel[$i]);
    $applyStyle("{$colLtr}7", $mkMed());
}

for ($j = 0; $j < $nFuentes; $j++) {
    $colLtr = Coordinate::stringFromColumnIndex($colFStart + $j);
    $sheet->getCell("{$colLtr}7")->setValue(strtoupper($fuentes[$j]));
    $applyStyle("{$colLtr}7", $stNivel);
}

$sheet->getCell("{$letMax}7")->setValue('MAX');
$sheet->getCell("{$letMin}7")->setValue('MIN');
$applyStyle("{$letMax}7:{$letMin}7", $mkMed());

$sheet->getRowDimension(7)->setRowHeight(44);

// ============================================================
// FILA 8: ESPACIADOR
// ============================================================
$sheet->getRowDimension(8)->setRowHeight(5);
$applyStyle("A8:{$letLast}8", [
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_BLANCO]],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
]);

// ============================================================
// ANCHOS DE COLUMNA
// ============================================================
$sheet->getColumnDimension('A')->setWidth(3);
$sheet->getColumnDimension('B')->setWidth(26);
$sheet->getColumnDimension('C')->setWidth(13);
for ($i = 0; $i < $nCats; $i++) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colLimStart + $i))->setWidth(14);
}
for ($j = 0; $j < $nFuentes; $j++) {
    $colLtr = Coordinate::stringFromColumnIndex($colFStart + $j);
    $w = max(14, min(30, strlen($fuentes[$j]) * 0.9 + 2));
    $sheet->getColumnDimension($colLtr)->setWidth($w);
}
$sheet->getColumnDimension($letMax)->setWidth(10);
$sheet->getColumnDimension($letMin)->setWidth(10);

// ============================================================
// SECCIONES Y PARÁMETROS (empezando en fila 9 → primera data en fila 10)
// ============================================================
$catOrden = ['FISICO', 'QUIMICO', 'MICROBIOLOGICO'];
foreach (array_keys($paramsPorCat) as $cat) {
    if (!in_array($cat, $catOrden, true)) $catOrden[] = $cat;
}

$filaActual = 9;

foreach ($catOrden as $normCat) {
    if (!isset($paramsPorCat[$normCat])) continue;

    // Fila de sección (azul medio, merged)
    $sheet->mergeCells("B{$filaActual}:{$letLast}{$filaActual}");
    $sheet->getCell("B{$filaActual}")->setValue(_dre_catLabel($normCat));
    $applyStyle("B{$filaActual}:{$letLast}{$filaActual}", $mkMed());
    $sheet->getRowDimension($filaActual)->setRowHeight(18);
    $filaActual++;

    // Filas de parámetros
    foreach ($paramsPorCat[$normCat] as $param) {
        $idParam = $param['id'];
        $fila    = $filaActual;
        $filaActual++;

        // B: nombre del parámetro (celeste muy claro)
        $sheet->getCell("B{$fila}")->setValue($param['nombre']);
        $applyStyle("B{$fila}", $stParamNombre);

        // C: unidad de medida (blanco)
        $sheet->getCell("C{$fila}")->setValue($param['unidad'] !== '' ? $param['unidad'] : '-');
        $applyStyle("C{$fila}", $stDato);

        // D...: valores de límites seleccionados (blanco)
        for ($i = 0; $i < $nCats; $i++) {
            $colLtr = Coordinate::stringFromColumnIndex($colLimStart + $i);
            $limVal = $limites[$idParam][$categorias_sel[$i]]['texto'] ?? '-';
            $sheet->getCell("{$colLtr}{$fila}")->setValue($limVal);
            $applyStyle("{$colLtr}{$fila}", $stDato);
        }

        // F...: resultados por dren (celeste, rojo si excede)
        $numVals = [];
        for ($j = 0; $j < $nFuentes; $j++) {
            $colLtr = Coordinate::stringFromColumnIndex($colFStart + $j);
            $valor  = $resultados[$fuentes[$j]][$idParam] ?? '-';
            $sheet->getCell("{$colLtr}{$fila}")->setValue($valor);
            $applyStyle("{$colLtr}{$fila}", $stResultado);

            if ($excedeLimite($idParam, $valor)) {
                $sheet->getStyle("{$colLtr}{$fila}")->getFont()->getColor()->setARGB($C_ROJO);
                $sheet->getStyle("{$colLtr}{$fila}")->getFont()->setBold(true);
            }

            $numV = _dre_parseNum($valor);
            if ($numV !== null) $numVals[] = $numV;
        }

        // MAX / MIN
        if (!empty($numVals)) {
            $maxV = max($numVals);
            $minV = min($numVals);
            $sheet->getCell("{$letMax}{$fila}")->setValue($maxV);
            $sheet->getCell("{$letMin}{$fila}")->setValue($minV);
            $applyStyle("{$letMax}{$fila}:{$letMin}{$fila}", $stResultado);
            if ($excedeLimite($idParam, (string)$maxV)) {
                $sheet->getStyle("{$letMax}{$fila}")->getFont()->getColor()->setARGB($C_ROJO);
                $sheet->getStyle("{$letMax}{$fila}")->getFont()->setBold(true);
            }
        } else {
            $sheet->getCell("{$letMax}{$fila}")->setValue('-');
            $sheet->getCell("{$letMin}{$fila}")->setValue('-');
            $applyStyle("{$letMax}{$fila}:{$letMin}{$fila}", $stResultado);
        }

        $sheet->getRowDimension($fila)->setRowHeight(16);
    }
}

// Columna A: margen blanco puro
$applyStyle("A1:A" . ($filaActual + 2), [
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_BLANCO]],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
]);

// Fijar paneles: columnas A-D (o más) y filas 1-7 congeladas
$sheet->freezePane("{$letFStart}8");

// ============================================================
// OUTPUT
// ============================================================
$nombreSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($proyecto['Nombre_Proyecto'] ?? 'drenes'));
$filename   = 'Drenes_' . $nombreSafe . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
