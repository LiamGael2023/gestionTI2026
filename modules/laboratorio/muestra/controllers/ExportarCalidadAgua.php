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
function _caq_sinTildes(string $s): string {
    $b = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü','ñ','Ñ'];
    $r = ['a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U','n','N'];
    return str_replace($b, $r, $s);
}

function _caq_normCat(string $cat): string {
    $c = strtoupper(_caq_sinTildes(trim($cat)));
    if (strpos($c, 'FISIC')       !== false) return 'FISICO';
    if (strpos($c, 'QUIMIC')      !== false) return 'QUIMICO';
    if (strpos($c, 'MICROBIOLOG') !== false) return 'MICROBIOLOGICO';
    return ($c !== '') ? $c : 'OTROS';
}

function _caq_catLabel(string $normCat): string {
    $m = [
        'FISICO'         => 'ANÁLISIS FÍSICOS',
        'QUIMICO'        => 'ANÁLISIS QUÍMICOS',
        'MICROBIOLOGICO' => 'ANÁLISIS MICROBIOLÓGICOS',
    ];
    return $m[$normCat] ?? ('ANÁLISIS ' . $normCat);
}

function _caq_fmtNum($v): string {
    if ($v === null || trim((string)$v) === '') return '';
    $s = rtrim(rtrim(number_format((float)$v, 6, '.', ''), '0'), '.');
    return ($s === '' || $s === '-') ? '0' : $s;
}

function _caq_fmtLim($min, $max): string {
    $mi = ($min !== null && trim((string)$min) !== '') ? _caq_fmtNum($min) : '';
    $ma = ($max !== null && trim((string)$max) !== '') ? _caq_fmtNum($max) : '';
    if ($mi !== '' && $ma !== '') return $mi . ' - ' . $ma;
    if ($ma !== '') return $ma;
    if ($mi !== '') return $mi;
    return '-';
}

function _caq_parseNum($v): ?float {
    if ($v === null) return null;
    $t = trim(str_replace(',', '.', (string)$v));
    if ($t === '' || $t === '-') return null;
    $t = preg_replace('/[^0-9.\-]/', '', $t);
    return (is_numeric($t) && $t !== '') ? floatval($t) : null;
}

// ============================================================
// BASE DE DATOS
// ============================================================
$id_proyecto = intval($_GET['id_proyecto'] ?? 0);
if ($id_proyecto <= 0) { http_response_code(400); die('Proyecto invalido'); }

$conn = Conexion::conectar();
if (!$conn) { http_response_code(500); die('Error de conexion'); }

// 1. Proyecto
$stmtP = sqlsrv_query($conn,
    "SELECT TOP 1 Id_Proyecto, Nombre_Proyecto, Valle, Temporada, Fecha_Inicio
     FROM laboratorio.Proyecto_Monitoreo
     WHERE Id_Proyecto = ? AND Activo = 1 AND Es_Control_Calidad = 1",
    [$id_proyecto]);
if (!$stmtP) { http_response_code(500); die('Error BD: proyecto'); }
$proyecto = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC);
if (!$proyecto) { http_response_code(404); die('Proyecto Calidad de Agua no encontrado'); }

// 2. Fuentes de agua (columnas dinamicas)
$stmtF = sqlsrv_query($conn,
    "SELECT DISTINCT da.Fuente_Agua
     FROM laboratorio.Muestra_Lab m
     INNER JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra
     WHERE m.Id_Proyecto = ? AND m.Activo = 1 AND da.Activo = 1
       AND da.Fuente_Agua IS NOT NULL AND LTRIM(RTRIM(da.Fuente_Agua)) <> ''
     ORDER BY da.Fuente_Agua",
    [$id_proyecto]);
$fuentes = [];
if ($stmtF) {
    while ($r = sqlsrv_fetch_array($stmtF, SQLSRV_FETCH_ASSOC)) {
        $fuentes[] = trim((string)$r['Fuente_Agua']);
    }
}
if (empty($fuentes)) { http_response_code(409); die('Sin fuentes de agua definidas para exportar'); }

// 3. Parametros agrupados por categoria
$stmtPa = sqlsrv_query($conn,
    "SELECT sub.Id_Parametro, sub.Nombre, sub.Unidad_Medida, sub.Categoria
     FROM (
         SELECT DISTINCT pa.Id_Parametro, pa.Nombre, ISNULL(um.Abreviatura, pa.Unidad_Medida) AS Unidad_Medida,
                ISNULL(pa.Categoria, 'Otros') AS Categoria
         FROM laboratorio.Parametro_Analisis pa
         LEFT JOIN laboratorio.Unidad_Medida um ON pa.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
         INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Servicio = pa.Id_Servicio
         INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
         WHERE ml.Id_Proyecto = ? AND ml.Activo = 1 AND sa.Activo = 1 AND pa.Activo = 1
     ) sub
     ORDER BY sub.Categoria, sub.Nombre",
    [$id_proyecto]);
if (!$stmtPa) {
    http_response_code(500);
    $errInfo = sqlsrv_errors();
    die('Error BD: parametros - ' . print_r($errInfo, true));
}

$paramsPorCat = [];
$paramIds     = [];
while ($r = sqlsrv_fetch_array($stmtPa, SQLSRV_FETCH_ASSOC)) {
    $id = intval($r['Id_Parametro'] ?? 0);
    if ($id <= 0) continue;
    $cat = _caq_normCat(trim((string)($r['Categoria'] ?? '')));
    $paramsPorCat[$cat][] = [
        'id'     => $id,
        'nombre' => trim((string)($r['Nombre'] ?? '')),
        'unidad' => trim((string)($r['Unidad_Medida'] ?? '-')),
    ];
    $paramIds[] = $id;
}
if (empty($paramIds)) { http_response_code(409); die('Sin parametros asociados al proyecto'); }

// 4. Resultados [fuente][id_param] = valor
$resultados = [];
$phP = implode(',', array_fill(0, count($paramIds), '?'));
$stmtR = sqlsrv_query($conn,
    "SELECT da.Fuente_Agua, ra.Id_Parametro,
            MAX(CAST(ra.Valor_Hallado AS NVARCHAR(255))) AS Valor
     FROM laboratorio.Resultado_Analisis ra
     INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
     INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
     INNER JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = ml.Id_Muestra
     WHERE ml.Id_Proyecto = ? AND ml.Activo = 1 AND sa.Activo = 1 AND ra.Activo = 1
       AND da.Fuente_Agua IS NOT NULL AND ra.Id_Parametro IN ($phP)
     GROUP BY da.Fuente_Agua, ra.Id_Parametro",
    array_merge([$id_proyecto], $paramIds));
if ($stmtR) {
    while ($r = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC)) {
        $f = trim((string)($r['Fuente_Agua'] ?? ''));
        $p = intval($r['Id_Parametro'] ?? 0);
        $v = trim((string)($r['Valor'] ?? ''));
        if ($f !== '' && $p > 0) {
            $resultados[$f][$p] = ($v === '' ? '-' : $v);
        }
    }
}

// 5. Limites [id_param]['riego'/'animales'] = ['texto','min','max']
$limites = [];
$stmtL = sqlsrv_query($conn,
    "SELECT Id_Parametro, Valor_Min, Valor_Max, Descripcion
     FROM laboratorio.Limite_Legal
     WHERE Activo = 1 AND Id_Parametro IN ($phP)
       AND Descripcion IN ('Riego de Vegetales', 'Consumo de Animales')",
    $paramIds);
if ($stmtL) {
    while ($r = sqlsrv_fetch_array($stmtL, SQLSRV_FETCH_ASSOC)) {
        $id    = intval($r['Id_Parametro'] ?? 0);
        $desc  = strtoupper(trim((string)($r['Descripcion'] ?? '')));
        $clave = (strpos($desc, 'RIEGO') !== false) ? 'riego' : 'animales';
        $limites[$id][$clave] = [
            'texto' => _caq_fmtLim($r['Valor_Min'], $r['Valor_Max']),
            'min'   => $r['Valor_Min'],
            'max'   => $r['Valor_Max'],
        ];
    }
}

$excedeLimite = function (int $idParam, $valor) use ($limites): bool {
    $n = _caq_parseNum($valor);
    if ($n === null) return false;
    foreach ($limites[$idParam] ?? [] as $info) {
        $mx = _caq_parseNum($info['max'] ?? null);
        $mn = _caq_parseNum($info['min'] ?? null);
        if ($mx !== null && $n > $mx) return true;
        if ($mn !== null && $n < $mn) return true;
    }
    return false;
};

// ============================================================
// LAYOUT DE COLUMNAS
// A(1): margen blanco
// B(2): Parametros
// C(3): Unidad de Medida
// D(4): DS Riego de Vegetales
// E(5): DS Consumo de Animales
// F(6)..F+N-1: Fuentes (dinamico)
// F+N: MAX   /   F+N+1: MIN
// ============================================================
$nFuentes  = count($fuentes);
$colFStart = 6;
$colMax    = $colFStart + $nFuentes;
$colMin    = $colMax + 1;
$colFin    = $colMin;

$letFStart = Coordinate::stringFromColumnIndex($colFStart);
$letMax    = Coordinate::stringFromColumnIndex($colMax);
$letMin    = Coordinate::stringFromColumnIndex($colMin);
$letFin    = Coordinate::stringFromColumnIndex($colFin);

// ============================================================
// CONSTRUIR SPREADSHEET DESDE CERO (sin template = sin corrupcion)
// ============================================================
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Resultados');

$C_AZUL_OSC    = 'FF3483CC';  // Azul oscuro: título, cabeceras principales
$C_AZUL_MED    = 'FF6CB0F0';  // Azul medio: secciones, normativa, MAX/MIN
$C_CELESTE     = 'FFC4DFF7';  // Celeste: nombres de nivel (F7+) y celdas de resultados
$C_PARAM_LIGHT = 'FFDCE6F1';  // Celeste muy claro: nombres de parámetros
$C_BLANCO      = 'FFFFFFFF';  // Blanco: bordes y fondo de límites/unidades
$C_NEGRO       = 'FF000000';
$C_ROJO        = 'FFFF0000';

$applyStyle = function (string $rango, array $arr) use ($sheet) {
    $sheet->getStyle($rango)->applyFromArray($arr);
};

$mkEncabezado = function (string $bgArgb, int $size = 10) use ($C_BLANCO): array {
    return [
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgArgb]],
        'font'      => ['bold' => true, 'color' => ['argb' => $C_BLANCO], 'size' => $size],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $C_BLANCO]]],
    ];
};

$estiloDato = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_BLANCO]],
    'font'      => ['bold' => false, 'color' => ['argb' => $C_NEGRO], 'size' => 9],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $C_BLANCO]]],
];
$estiloDatoNombre = array_merge($estiloDato, [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true, 'indent' => 1],
    'font'      => ['bold' => false, 'italic' => true, 'color' => ['argb' => $C_NEGRO], 'size' => 9],
]);
$estiloDatoCeleste = [
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_CELESTE]],
    'font'      => ['bold' => false, 'color' => ['argb' => $C_NEGRO], 'size' => 9],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $C_BLANCO]]],
];

// ============================================================
// FILAS 1-3: margen visual
// ============================================================
foreach ([1, 2, 3] as $mr) { $sheet->getRowDimension($mr)->setRowHeight(5); }

// ============================================================
// FILA 4: NOMBRE DEL PROYECTO
// ============================================================
$sheet->mergeCells('B4:' . $letFin . '4');
$sheet->getCell('B4')->setValue(strtoupper(trim((string)($proyecto['Nombre_Proyecto'] ?? ''))));
$applyStyle('B4:' . $letFin . '4', $mkEncabezado($C_AZUL_OSC, 11));
$sheet->getRowDimension(4)->setRowHeight(34);

// ============================================================
// FILA 5: TEMPORADA
// ============================================================
$sheet->mergeCells('B5:' . $letFin . '5');

$fecha_inicio = '';
if ($proyecto['Fecha_Inicio']) {
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $mes = intval($proyecto['Fecha_Inicio']->format('n')) - 1;
    $año = $proyecto['Fecha_Inicio']->format('Y');
    $fecha_inicio = strtoupper($meses[$mes]) . ' ' . $año;
}
$sheet->getCell('B5')->setValue($fecha_inicio);
$applyStyle('B5:' . $letFin . '5', $mkEncabezado($C_AZUL_OSC, 10));
$sheet->getRowDimension(5)->setRowHeight(20);

// ============================================================
// FILA 6: CABECERAS (con merge B6:B7 y C6:C7 para doble fila)
// ============================================================
$sheet->mergeCells('B6:B7');
$sheet->getCell('B6')->setValue('PARÁMETROS');

$sheet->mergeCells('C6:C7');
$sheet->getCell('C6')->setValue('Unidad de Medida');

$sheet->mergeCells('D6:E6');
$sheet->getCell('D6')->setValue('DS N°004-2017 MINAM');

$sheet->mergeCells($letFStart . '6:' . $letFin . '6');
$sheet->getCell($letFStart . '6')->setValue('RESULTADOS');

$applyStyle('B6:' . $letFin . '6', $mkEncabezado($C_AZUL_OSC));
$sheet->getRowDimension(6)->setRowHeight(20);

// ============================================================
// FILA 7: SUB-CABECERAS
// ============================================================
$sheet->getCell('D7')->setValue('Riego Vegetales');
$sheet->getCell('E7')->setValue('Bebida Animales');

$colIdx7 = $colFStart;
foreach ($fuentes as $fuente) {
    $sheet->getCell(Coordinate::stringFromColumnIndex($colIdx7) . '7')->setValue(strtoupper($fuente));
    $colIdx7++;
}
$sheet->getCell($letMax . '7')->setValue('MAX');
$sheet->getCell($letMin . '7')->setValue('MIN');

$applyStyle('D7:' . $letFin . '7', $mkEncabezado($C_AZUL_MED));
$sheet->getRowDimension(7)->setRowHeight(44);

// ============================================================
// ANCHOS DE COLUMNAS
// ============================================================
$sheet->getColumnDimension('A')->setWidth(3);
$sheet->getColumnDimension('B')->setWidth(26);
$sheet->getColumnDimension('C')->setWidth(13);
$sheet->getColumnDimension('D')->setWidth(14);
$sheet->getColumnDimension('E')->setWidth(14);
$sheet->getColumnDimension($letMax)->setWidth(10);
$sheet->getColumnDimension($letMin)->setWidth(10);
for ($ci = $colFStart; $ci < $colMax; $ci++) {
    $w = max(14, min(28, strlen($fuentes[$ci - $colFStart]) * 0.95 + 2));
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setWidth($w);
}

// ============================================================
// SECCIONES Y FILAS DE PARAMETROS
// ============================================================
$catOrden = ['FISICO', 'QUIMICO', 'MICROBIOLOGICO'];
foreach (array_keys($paramsPorCat) as $cat) {
    if (!in_array($cat, $catOrden, true)) $catOrden[] = $cat;
}

$filaActual = 8;

foreach ($catOrden as $normCat) {
    if (!isset($paramsPorCat[$normCat])) continue;

    // Fila de seccion (azul medio, merged)
    $sheet->mergeCells('B' . $filaActual . ':' . $letFin . $filaActual);
    $sheet->getCell('B' . $filaActual)->setValue(_caq_catLabel($normCat));
    $applyStyle('B' . $filaActual . ':' . $letFin . $filaActual, $mkEncabezado($C_AZUL_MED));
    $sheet->getRowDimension($filaActual)->setRowHeight(18);
    $filaActual++;

    // Filas de parametros
    foreach ($paramsPorCat[$normCat] as $param) {
        $idParam = $param['id'];
        $fila    = $filaActual;
        $filaActual++;

        // Estilo base de la fila completa
        $applyStyle('B' . $fila . ':' . $letFin . $fila, $estiloDato);
        // Nombre con estilo especial (izquierda + italic)
        $applyStyle('B' . $fila, $estiloDatoNombre);

        // Valores
        $sheet->getCell('B' . $fila)->setValue($param['nombre']);
        $sheet->getCell('C' . $fila)->setValue($param['unidad'] !== '' ? $param['unidad'] : '-');
        $sheet->getCell('D' . $fila)->setValue($limites[$idParam]['riego']['texto']    ?? '-');
        $sheet->getCell('E' . $fila)->setValue($limites[$idParam]['animales']['texto'] ?? '-');

        // Resultados por fuente (celeste)
        // Aplicar fondo celeste al rango completo de fuentes de esta fila
        $letFuenteFin = Coordinate::stringFromColumnIndex($colFStart + $nFuentes - 1);
        $applyStyle($letFStart . $fila . ':' . $letFuenteFin . $fila, $estiloDatoCeleste);

        $numVals = [];
        $colIdx  = $colFStart;
        foreach ($fuentes as $fuente) {
            $valor     = $resultados[$fuente][$idParam] ?? '-';
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getCell($colLetter . $fila)->setValue($valor);

            $numV = _caq_parseNum($valor);
            if ($numV !== null) $numVals[] = $numV;

            if ($excedeLimite($idParam, $valor)) {
                $sheet->getStyle($colLetter . $fila)->getFont()->getColor()->setARGB($C_ROJO);
                $sheet->getStyle($colLetter . $fila)->getFont()->setBold(true);
            }
            $colIdx++;
        }

        // MAX / MIN
        if (!empty($numVals)) {
            $maxV = max($numVals);
            $minV = min($numVals);
            $sheet->getCell($letMax . $fila)->setValue($maxV);
            $sheet->getCell($letMin . $fila)->setValue($minV);
            if ($excedeLimite($idParam, (string)$maxV)) {
                $sheet->getStyle($letMax . $fila)->getFont()->getColor()->setARGB($C_ROJO);
                $sheet->getStyle($letMax . $fila)->getFont()->setBold(true);
            }
        } else {
            $sheet->getCell($letMax . $fila)->setValue('-');
            $sheet->getCell($letMin . $fila)->setValue('-');
        }

        $sheet->getRowDimension($fila)->setRowHeight(16);
    }
}

// Columna A blanca
$sheet->getStyle('A1:A' . ($filaActual + 2))->applyFromArray([
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_BLANCO]],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
]);

// Freeze
$sheet->freezePane($letFStart . '8');

// ============================================================
// OUTPUT
// ============================================================
$nombreSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($proyecto['Nombre_Proyecto'] ?? 'proyecto'));
$filename   = 'CalidadAgua_' . $nombreSafe . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
