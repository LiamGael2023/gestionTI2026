<?php
/**
 * ExportarDrenes.php — v3 (plantilla "RESULTADOS AGUA DRENADA 2026.xlsx", hoja JULIO 2025)
 * Exporta el informe de Calidad de Agua Drenada (Es_Drene=1).
 *
 * Comportamiento (mismo patrón que ExportarCalidadAgua v2 - decisión del usuario):
 *  - Solo se exportan los parámetros con al menos UN resultado no nulo en alguna estación.
 *  - MAX/MIN se calculan en PHP y se escriben como valores (sin fórmulas).
 *  - Se respeta la plantilla: solo setCellValue, sin crear/eliminar columnas ni merges.
 *
 * Uso: ExportarDrenes.php?id_proyecto=X
 */
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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ============================================================
// HELPERS (mismos criterios de normalización que Calidad Superficial)
// ============================================================
function _dre_sinTildes(string $s): string {
    $b = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü','ñ','Ñ'];
    $r = ['a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U','n','N'];
    return str_replace($b, $r, $s);
}

function _dre_norm(string $s): string {
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', _dre_sinTildes($s)));
}

function _dre_base(string $s): string {
    $t = preg_replace('/\s*\(.*$/', '', trim($s));
    $t = preg_replace('/[^A-Z0-9]/', '', strtoupper(_dre_sinTildes($t)));
    return $t;
}

function _dre_palabrasSignificativas(string $texto): array {
    $t = preg_replace('/[^A-Z0-9 ]/', ' ', strtoupper(_dre_sinTildes($texto)));
    $palabras = array_values(array_filter(explode(' ', $t), fn($p) => strlen($p) >= 3));
    return $palabras;
}

function _dre_esNumero($v): bool {
    if ($v === null) return false;
    $t = trim(str_replace(',', '.', (string)$v));
    if ($t === '' || $t === '-') return false;
    return is_numeric($t);
}

function _dre_aNumero($v): float {
    return floatval(str_replace(',', '.', (string)$v));
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
     WHERE Id_Proyecto = ? AND Activo = 1 AND Es_Drene = 1",
    [$id_proyecto]);
if (!$stmtP) { http_response_code(500); die('Error BD: proyecto'); }
$proyecto = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC);
if (!$proyecto) { http_response_code(404); die('Proyecto Drenes no encontrado'); }

// Parsear año y mes desde "CALIDAD DRENES {año} - {MES}"
$nombreProyecto = trim((string)($proyecto['Nombre_Proyecto'] ?? ''));
if (!preg_match('/^CALIDAD\s+DRENES\s+(\d{4})\s*-\s*([A-Za-zÁÉÍÓÚÑÜ]+)$/i', $nombreProyecto, $m)) {
    http_response_code(400);
    die('Nombre de proyecto no reconocido: ' . $nombreProyecto);
}
$anio      = $m[1];
$mesNombre = ucfirst(strtolower($m[2]));
$mesUpper  = strtoupper($mesNombre);

// ============================================================
// MUESTRAS DEL PROYECTO (estaciones de dren)
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
// PARÁMETROS ACTIVOS (nombre de la plantilla → Id_Parametro)
// ============================================================
$paramsActivos = [];
$stmtPa = sqlsrv_query($conn, "SELECT Id_Parametro, Nombre FROM laboratorio.Parametro_Analisis WHERE Activo = 1");
if ($stmtPa) {
    while ($r = sqlsrv_fetch_array($stmtPa, SQLSRV_FETCH_ASSOC)) {
        $paramsActivos[_dre_base((string)$r['Nombre'])] = intval($r['Id_Parametro']);
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
            $resultados[$idM][$idP] = $v;
        }
    }
}

// ============================================================
// PLANTILLA (hoja moderna de referencia: JULIO 2025)
// ============================================================
$templatePath = $base_path . '/modules/laboratorio/muestra/plantilla/RESULTADOS AGUA DRENADA  2026.xlsx';
if (!file_exists($templatePath)) { http_response_code(500); die('Plantilla no encontrada'); }
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getSheetByName('JULIO 2025 ');
if (!$sheet) { http_response_code(500); die('Hoja de plantilla no encontrada'); }

// Eliminar TODAS las demás hojas del libro (la plantilla es un archivo histórico
// con 23 hojas; el informe debe llevar únicamente la hoja del mes).
foreach ($spreadsheet->getSheetNames() as $nombreHoja) {
    if ($nombreHoja === 'JULIO 2025 ') continue;
    $hojaExtra = $spreadsheet->getSheetByName($nombreHoja);
    if ($hojaExtra) {
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($hojaExtra));
    }
}

$spreadsheet->setActiveSheetIndex(0);
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle($mesUpper . ' ' . $anio);
$sheet->setShowGridlines(false);

// Título (fila 4): primera línea fija de la plantilla + mes - año
$tituloTpl = trim(explode("\n", (string)$sheet->getCell('B4')->getValue())[0]);
$sheet->getCell('B4')->setValue($tituloTpl . "\n" . $mesUpper . ' - ' . $anio);

// ============================================================
// MAPEO ESTACIONES: cabeceras F7..L7 ↔ muestras
// (mismo algoritmo "most constrained first" que Calidad Superficial)
// ============================================================
$columnasEstacion = [];
for ($ci = 6; $ci <= 12; $ci++) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci);
    $header = trim((string)$sheet->getCell($letra . '7')->getValue());
    if ($header === '') continue;
    $columnasEstacion[$letra] = ['id' => null, 'header' => $header];
}

$colCands = [];
foreach ($columnasEstacion as $letra => $infoCol) {
    $hdrNorm = _dre_norm($infoCol['header']);
    $hdrWords = _dre_palabrasSignificativas($infoCol['header']);

    $mejorScore = 0;
    $cands = [];
    foreach ($muestras as $mu) {
        $cortoNorm = _dre_norm($mu['corto']);
        $obsNorm   = _dre_norm($mu['obs']);

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
// FILAS DE DATOS DEL TEMPLATE (JULIO 2025):
// Físicos: 10-14 | Químicos: 17-30 | Microbiológicos: 33-34
// ============================================================
$filasDatos = [
    10, 11, 12, 13, 14,
    17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30,
    33, 34,
];

$filasInfo = [];
foreach ($filasDatos as $fila) {
    $nombreTpl = trim((string)$sheet->getCell('B' . $fila)->getValue());
    $idParam   = $paramsActivos[_dre_base($nombreTpl)] ?? 0;
    $filasInfo[$fila] = ['nombre' => $nombreTpl, 'id_param' => $idParam];
}

// ============================================================
// LIMPIAR VALORES DE EJEMPLO DE LA PLANTILLA (F..L de filas de datos)
// ============================================================
foreach ($filasDatos as $fila) {
    for ($ci = 6; $ci <= 12; $ci++) {
        $l = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci);
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

rsort($filasOmitir);
foreach ($filasOmitir as $fila) {
    $sheet->removeRow($fila, 1);
}

function _dre_filaFinal(int $filaOrig, array $omitidas): int {
    $menores = 0;
    foreach ($omitidas as $o) { if ($o < $filaOrig) $menores++; }
    return $filaOrig - $menores;
}

// ============================================================
// LIMPIAR COLUMNAS MAX/MIN (M/N) — se reescriben con valores calculados
// ============================================================
for ($f = 4; $f <= 46; $f++) {
    $sheet->getCell('M' . $f)->setValue(null);
    $sheet->getCell('N' . $f)->setValue(null);
}
$sheet->getCell('M9')->setValue('MAX');
$sheet->getCell('N9')->setValue('MIN');

// ============================================================
// ESCRIBIR VALORES POR ESTACIÓN + MAX/MIN CALCULADOS
// ============================================================
foreach ($filasInfo as $filaOrig => $info) {
    if (in_array($filaOrig, $filasOmitir, true)) continue;
    $nf = _dre_filaFinal($filaOrig, $filasOmitir);
    $idParam = $info['id_param'];

    $valoresNum = [];
    foreach ($columnasEstacion as $letra => $col) {
        $idM = $col['id'];
        $v = ($idM !== null) ? ($resultados[$idM][$idParam] ?? '') : '';
        if ($v === '') {
            $sheet->getCell($letra . $nf)->setValue(null);
            continue;
        }
        if (_dre_esNumero($v)) {
            $sheet->getCell($letra . $nf)->setValue(_dre_aNumero($v));
            $valoresNum[] = _dre_aNumero($v);
        } else {
            $sheet->getCell($letra . $nf)->setValue($v);
        }
        // Quitar color/fuente heredados de los valores de ejemplo de la plantilla
        $sheet->getStyle($letra . $nf)->getFont()->getColor()->setARGB('FF000000');
        $sheet->getStyle($letra . $nf)->getFont()->setBold(false);
    }

    if (!empty($valoresNum)) {
        $sheet->getCell('M' . $nf)->setValue(max($valoresNum));
        $sheet->getCell('N' . $nf)->setValue(min($valoresNum));
    }
}

// ============================================================
// OUTPUT
// ============================================================
$nombreSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreProyecto);
$filename = 'Drenes_' . $nombreSafe . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
