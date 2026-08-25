<?php
/**
 * ExportarCalidadAgua.php — v5 (limpia, 2026-08-25)
 * Exporta el informe de Calidad Superficial de un proyecto (Es_Control_Calidad=1).
 *
 * Comportamiento (decisiones del usuario):
 *  - Plantilla: "RESULTADOS ... AGUAS SUPERFICIALES.xlsx" (arreglada por el usuario,
 *    SIN dibujos ni gráficos → el archivo sale íntegro, Excel no pide reparar).
 *  - Solo se exportan los parámetros con al menos UN resultado no nulo en alguna estación.
 *    Si todas las estaciones están vacías, la fila del parámetro se ELIMINA del Excel.
 *  - MAX/MIN se calculan en PHP y se escriben como valores (sin fórmulas).
 *  - Cifras EXACTAS de BD: valor sin round() + formato General (la plantilla hereda
 *    formatos que redondean la visualización: contable sin decimales, 0.0, [0]).
 *  - Colores explícitos: azul FUERTE 335693 (título, cabeceras, secciones) + texto blanco
 *    bold; azul SUAVE DAE3F3 (filas de datos y LMP) + texto negro.
 *  - Config: db.php → config.php → Auth.php (patrón ExportarProyectoMonitoreo; sin
 *    config.php el redirect de sesión vencida fatala por BASE_URL indefinida).
 *
 * Uso: ExportarCalidadAgua.php?id_proyecto=X
 */
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$autoloadLibs = $base_path . '/libs/vendor/autoload.php';
if (!file_exists($autoloadLibs)) {
    http_response_code(500);
    die('No se encontro la libreria de exportacion');
}
require_once $autoloadLibs;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// ============================================================
// HELPERS
// ============================================================
function _cs_sinTildes(string $s): string {
    $b = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü','ñ','Ñ'];
    $r = ['a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U','n','N'];
    return str_replace($b, $r, $s);
}

// Normaliza para comparar: MAYÚSCULAS, sin tildes, sin caracteres especiales ni espacios.
// ⚠️ strtoupper ANTES del preg_replace (si va después, "Dren Bitín" → "DB").
function _cs_norm(string $s): string {
    return preg_replace('/[^A-Z0-9]/', '', strtoupper(_cs_sinTildes($s)));
}

// Base del nombre de parámetro: quita paréntesis y sufijos (ej. "Amonio (NH4)" → "AMONIO")
function _cs_base(string $s): string {
    $t = preg_replace('/\s*\(.*$/', '', trim($s));
    $t = preg_replace('/[^A-Z0-9]/', '', strtoupper(_cs_sinTildes($t)));
    return $t;
}

function _cs_palabrasSignificativas(string $texto): array {
    $t = preg_replace('/[^A-Z0-9 ]/', ' ', strtoupper(_cs_sinTildes($texto)));
    return array_values(array_filter(explode(' ', $t), fn($p) => strlen($p) >= 3));
}

function _cs_esNumero($v): bool {
    if ($v === null) return false;
    $t = trim(str_replace(',', '.', (string)$v));
    if ($t === '' || $t === '-') return false;
    return is_numeric($t);
}

function _cs_aNumero($v): float {
    return floatval(str_replace(',', '.', (string)$v));
}

// Fila final tras eliminar filas sin resultados
function _cs_filaFinal(int $filaOrig, array $omitidas): int {
    $menores = 0;
    foreach ($omitidas as $o) { if ($o < $filaOrig) $menores++; }
    return $filaOrig - $menores;
}

// ============================================================
// PROYECTO
// ============================================================
$id_proyecto = intval($_GET['id_proyecto'] ?? 0);
if ($id_proyecto <= 0) { http_response_code(400); die('Proyecto invalido'); }

$conn = Conexion::conectar();
if (!$conn) { http_response_code(500); die('Error de conexion'); }

$stmtP = sqlsrv_query($conn,
    "SELECT TOP 1 Id_Proyecto, Nombre_Proyecto
     FROM laboratorio.Proyecto_Monitoreo
     WHERE Id_Proyecto = ? AND Activo = 1 AND Es_Control_Calidad = 1",
    [$id_proyecto]);
if (!$stmtP) { http_response_code(500); die('Error BD: proyecto'); }
$proyecto = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC);
if (!$proyecto) { http_response_code(404); die('Proyecto Calidad Superficial no encontrado'); }

// Parsear año y mes desde "CALIDAD SUPERFICIAL {año} - {MES}"
$nombreProyecto = trim((string)($proyecto['Nombre_Proyecto'] ?? ''));
if (!preg_match('/^CALIDAD\s+SUPERFICIAL\s+(\d{4})\s*-\s*([A-Za-zÁÉÍÓÚÑÜ]+)$/i', $nombreProyecto, $m)) {
    http_response_code(400);
    die('Nombre de proyecto no reconocido: ' . $nombreProyecto);
}
$anio      = $m[1];
$mesUpper  = strtoupper($m[2]);        // "MARZO"

// ============================================================
// MUESTRAS DEL PROYECTO (estaciones)
// ============================================================
$muestras = [];
$stmtM = sqlsrv_query($conn,
    "SELECT ml.Id_Muestra, da.Nivel_Agua, ml.Observacion_Muestra
     FROM laboratorio.Muestra_Lab ml
     INNER JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = ml.Id_Muestra AND da.Activo = 1
     WHERE ml.Id_Proyecto = ? AND ml.Activo = 1
     ORDER BY ml.Id_Muestra", [$id_proyecto]);
if ($stmtM) {
    while ($r = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC)) {
        $muestras[] = [
            'id'    => intval($r['Id_Muestra'] ?? 0),
            'corto' => trim((string)($r['Nivel_Agua'] ?? '')),
            'obs'   => trim((string)($r['Observacion_Muestra'] ?? '')),
        ];
    }
}
if (empty($muestras)) { http_response_code(409); die('El proyecto no tiene muestras para exportar'); }

$idsMuestras = array_column($muestras, 'id');

// ============================================================
// PARÁMETROS ACTIVOS (nombre → Id_Parametro)
// ============================================================
$paramsActivos = [];
$stmtPa = sqlsrv_query($conn, "SELECT Id_Parametro, Nombre FROM laboratorio.Parametro_Analisis WHERE Activo = 1");
if ($stmtPa) {
    while ($r = sqlsrv_fetch_array($stmtPa, SQLSRV_FETCH_ASSOC)) {
        $paramsActivos[_cs_base((string)$r['Nombre'])] = intval($r['Id_Parametro']);
    }
}

// ============================================================
// RESULTADOS [Id_Muestra][Id_Parametro] = Valor_Hallado
// ============================================================
$resultados = [];
$phM = implode(',', array_fill(0, count($idsMuestras), '?'));
$stmtR = sqlsrv_query($conn,
    "SELECT sa.Id_Muestra, ra.Id_Parametro, ra.Valor_Hallado
     FROM laboratorio.Resultado_Analisis ra
     INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
     WHERE sa.Id_Muestra IN ($phM) AND ra.Activo = 1 AND sa.Activo = 1",
    $idsMuestras);
if ($stmtR) {
    while ($r = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC)) {
        $idM = intval($r['Id_Muestra'] ?? 0);
        $idP = intval($r['Id_Parametro'] ?? 0);
        $v   = trim((string)($r['Valor_Hallado'] ?? ''));
        if ($idM > 0 && $idP > 0) {
            $resultados[$idM][$idP] = $v;   // '' cuando es null
        }
    }
}

// ============================================================
// PLANTILLA (arreglada por el usuario: SIN dibujos ni gráficos)
// ============================================================
$templatePath = $base_path . '/modules/laboratorio/muestra/plantilla/RESULTADOS ANÁLISIS FISICO QUIMICOS Y MICROBIOLOGICOS DE LA CALIDAD AMBIENTAL DE AGUAS SUPERFICIALES.xlsx';
if (!file_exists($templatePath)) { http_response_code(500); die('Plantilla no encontrada'); }

$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();          // única hoja: "MARZO 2026"
$sheet->setTitle($mesUpper . ' ' . $anio);        // "MARZO 2026"
$sheet->setShowGridlines(false);

// Título (fila 4): texto fijo + mes - año
$sheet->getCell('B4')->setValue(
    "RESULTADOS ANÁLISIS FISICO QUIMICOS Y MICROBIOLOGICOS DE LA CALIDAD AMBIENTAL DE AGUAS SUPERFICIALES\n" .
    $mesUpper . ' - ' . $anio
);

// ============================================================
// MAPEO ESTACIONES: cabeceras F7..O7 ↔ muestras
// (asignación "most constrained first": se asignan primero las columnas
//  con menos candidatas para que una muestra ambigua no sea robada)
// ============================================================
$columnasEstacion = [];   // letra columna (F..O) => [id_muestra, header]
for ($ci = 6; $ci <= 15; $ci++) {
    $letra = Coordinate::stringFromColumnIndex($ci);
    $header = trim((string)$sheet->getCell($letra . '7')->getValue());
    if ($header === '') continue;
    $columnasEstacion[$letra] = ['id' => null, 'header' => $header];
}

$colCands = [];
foreach ($columnasEstacion as $letra => $infoCol) {
    $hdrNorm = _cs_norm($infoCol['header']);
    // palabras significativas sobre el texto CON espacios (normalización aparte)
    $hdrWords = _cs_palabrasSignificativas($infoCol['header']);

    $mejorScore = 0;
    $cands = [];
    foreach ($muestras as $mu) {
        $cortoNorm = _cs_norm($mu['corto']);
        $obsNorm   = _cs_norm($mu['obs']);

        $score = 0;
        if ($cortoNorm !== '' && $cortoNorm === $hdrNorm) {
            $score = 100;
        } elseif ($cortoNorm !== '' && strpos($cortoNorm, $hdrNorm) === 0 && $hdrNorm !== '') {
            $score = 80;
        } elseif ($cortoNorm !== '' && $hdrNorm !== '' && strpos($hdrNorm, $cortoNorm) === 0) {
            $score = 70;
        } elseif (!empty($hdrWords)) {
            $todas = true;
            foreach ($hdrWords as $w) {
                if ($w === '' || strpos($obsNorm, $w) === false) { $todas = false; break; }
            }
            if ($todas) $score = 60;
        }

        if ($score > $mejorScore) {
            $mejorScore = $score;
            $cands = [$mu['id']];
        } elseif ($score === $mejorScore && $score > 0) {
            $cands[] = $mu['id'];
        }
    }
    if ($mejorScore > 0) {
        $colCands[$letra] = ['score' => $mejorScore, 'cands' => $cands];
    }
}

// Orden: menos candidatas primero; dentro del mismo tamaño, mayor puntaje
uksort($colCands, function ($a, $b) use ($colCands) {
    $na = count($colCands[$a]['cands']);
    $nb = count($colCands[$b]['cands']);
    if ($na !== $nb) return $na <=> $nb;
    return $colCands[$b]['score'] <=> $colCands[$a]['score'];
});

$usadas = [];
foreach ($colCands as $letra => $infoCand) {
    $elegida = null;
    foreach ($infoCand['cands'] as $idCand) {
        if (!isset($usadas[$idCand])) { $elegida = $idCand; break; }
    }
    if ($elegida !== null) {
        $columnasEstacion[$letra]['id'] = $elegida;
        $usadas[$elegida] = true;
    }
}

// ============================================================
// FILAS DE DATOS DEL TEMPLATE (estructura fija)
// Físicos: 10-14 | Químicos: 17-29 | Microbiológicos: 32-33
// ============================================================
$filasDatos = [10, 11, 12, 13, 14, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 32, 33];

$filasInfo = [];
foreach ($filasDatos as $fila) {
    $nombreTpl = trim((string)$sheet->getCell('B' . $fila)->getValue());
    $idParam   = $paramsActivos[_cs_base($nombreTpl)] ?? 0;
    $filasInfo[$fila] = ['nombre' => $nombreTpl, 'id_param' => $idParam];
}

// Limpiar valores de ejemplo de la plantilla en las filas de datos (F..O)
foreach ($filasDatos as $fila) {
    for ($ci = 6; $ci <= 15; $ci++) {
        $l = Coordinate::stringFromColumnIndex($ci);
        $sheet->getCell($l . $fila)->setValue(null);
    }
}

// ============================================================
// DECIDIR QUÉ FILAS SE OMITEN (resultado nulo en TODAS las estaciones)
// ============================================================
$filasOmitir = [];
foreach ($filasInfo as $fila => $info) {
    $idParam = $info['id_param'];
    $tieneValor = false;
    foreach ($columnasEstacion as $col) {
        $idM = $col['id'];
        if ($idM === null) continue;
        $v = $resultados[$idM][$idParam] ?? '';
        if ($v !== '') { $tieneValor = true; break; }
    }
    if (!$tieneValor) $filasOmitir[] = $fila;
}

// Eliminar filas sin resultados (de abajo hacia arriba para no romper índices)
rsort($filasOmitir);
foreach ($filasOmitir as $fila) {
    $sheet->removeRow($fila, 1);
}

// ============================================================
// LIMPIAR COLUMNAS MAX/MIN (P/Q) — se reescriben calculados
// ============================================================
for ($f = 4; $f <= 45; $f++) {
    $sheet->getCell('P' . $f)->setValue(null);
    $sheet->getCell('Q' . $f)->setValue(null);
}
$sheet->getCell('P9')->setValue('MAX');
$sheet->getCell('Q9')->setValue('MIN');

// ============================================================
// ESCRIBIR VALORES POR ESTACIÓN + MAX/MIN CALCULADOS
// ============================================================
foreach ($filasInfo as $filaOrig => $info) {
    if (in_array($filaOrig, $filasOmitir, true)) continue;
    $nf = _cs_filaFinal($filaOrig, $filasOmitir);
    $idParam = $info['id_param'];

    $valoresNum = [];
    foreach ($columnasEstacion as $letra => $col) {
        $idM = $col['id'];
        $v = ($idM !== null) ? ($resultados[$idM][$idParam] ?? '') : '';
        if ($v === '') {
            $sheet->getCell($letra . $nf)->setValue(null);   // en blanco
            continue;
        }
        if (_cs_esNumero($v)) {
            $sheet->getCell($letra . $nf)->setValue(_cs_aNumero($v));   // cifra EXACTA (sin round)
            $valoresNum[] = _cs_aNumero($v);
        } else {
            $sheet->getCell($letra . $nf)->setValue($v);     // ">1100", "N.D." etc.
        }
        // Quitar color/fuente heredados de los valores de ejemplo de la plantilla
        $sheet->getStyle($letra . $nf)->getFont()->getColor()->setARGB('FF000000');
        $sheet->getStyle($letra . $nf)->getFont()->setBold(false);
    }

    // MAX / MIN calculados en PHP (valores, no fórmulas)
    if (!empty($valoresNum)) {
        $sheet->getCell('P' . $nf)->setValue(max($valoresNum));
        $sheet->getCell('Q' . $nf)->setValue(min($valoresNum));
    }
}

// ============================================================
// CIFRAS EXACTAS EN PANTALLA: formato General en el área de resultados
// (F..Q). La plantilla hereda formatos que redondean la visualización
// (contable sin decimales, 0.0, [0]) — un pH 8.66 se vería 9.
// ============================================================
$sheet->getStyle('F9:Q45')->getNumberFormat()->setFormatCode('General');

// ============================================================
// COLORES DE LA PLANTILLA (azul fuerte + azul suave) — EXPLÍCITOS
// ============================================================
// La plantilla usa colores THEME y PhpSpreadsheet los resuelve mal al guardar
// (los convertía a blanco). Pedido del usuario 2026-08-25.
$azulFuerte = 'FF335693';
$azulSuave  = 'FFDAE3F3';
$maxFilaColores = $sheet->getHighestDataRow();

// Fila 4 (título) y cabeceras (6-8): azul fuerte + texto blanco bold
foreach (['B4:O4', 'B6:O8'] as $rangoTitulo) {
    $sheet->getStyle($rangoTitulo)
          ->getFill()->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB($azulFuerte);
    $sheet->getStyle($rangoTitulo)->getFont()->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle($rangoTitulo)->getFont()->setBold(true);
}

// Filas de datos (10 en adelante, incluye LMP): azul suave
// (solo relleno; el color de fuente ya quedó negro o rojo al escribir los datos)
$sheet->getStyle('B10:Q' . $maxFilaColores)
      ->getFill()->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB($azulSuave);

// Filas de SECCIÓN ("ANALISIS ..."): azul fuerte + texto blanco bold
for ($r = 4; $r <= $maxFilaColores; $r++) {
    $valB = $sheet->getCell('B' . $r)->getValue();
    if (is_string($valB) && stripos($valB, 'ANALISIS') !== false) {
        $sheet->getStyle('B' . $r . ':Q' . $r)
              ->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB($azulFuerte);
        $sheet->getStyle('B' . $r . ':Q' . $r)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('B' . $r . ':Q' . $r)->getFont()->setBold(true);
    }
}

// ============================================================
// BORDES BLANCOS FINOS (pedido del usuario 2026-08-25): todos los
// bordes del área usada quedan en grosor fino (1) y color blanco,
// para que el informe se vea como la plantilla.
// ============================================================
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . $maxFilaColores)
      ->getBorders()
      ->getAllBorders()
      ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . $maxFilaColores)
      ->getBorders()
      ->getAllBorders()
      ->getColor()->setARGB('FFFFFFFF');

// ============================================================
// OUTPUT (mismo patrón que ExportarProyectoMonitoreo)
// ============================================================
$filename = 'CALIDAD_SUPERFICIAL_' . $mesUpper . '_' . $anio . '_' . date('Ymd_His') . '.xlsx';

// Archivo temporal con nombre ÚNICO por request (dos descargas no se pisan)
$outputPath = $base_path . '/temp/calidad_superficial_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.xlsx';
$outputDir = dirname($outputPath);
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);

while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($outputPath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
readfile($outputPath);
@unlink($outputPath);
exit;