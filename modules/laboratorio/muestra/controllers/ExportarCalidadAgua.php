<?php
/**
 * ExportarCalidadAgua.php — v2 (plantilla "RESULTADOS ... AGUAS SUPERFICIALES - 2026.xlsx")
 * Exporta el informe de Calidad Superficial de un proyecto (Es_Control_Calidad=1).
 *
 * Comportamiento (decisiones del usuario):
 *  - Solo se exportan los parámetros que tienen al menos UN resultado no nulo en alguna estación.
 *    Si todas las estaciones están vacías, la fila del parámetro se ELIMINA del Excel
 *    (secciones, nota y bloque LMP se reacomodan automáticamente).
 *  - MAX/MIN se calculan en PHP y se escriben como valores (nada de fórmulas: se romperían al
 *    quitar filas y no se recalcularían en visores sin cálculo automático).
 *  - SIN bordes y SIN rellenos: ninguna celda lleva borde; todo queda en blanco.
 *
 * Uso: ExportarCalidadAgua.php?id_proyecto=X
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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ============================================================
// HELPERS
// ============================================================
function _cs_sinTildes(string $s): string {
    $b = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü','ñ','Ñ'];
    $r = ['a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U','n','N'];
    return str_replace($b, $r, $s);
}

// Normaliza para comparar: MAYÚSCULAS, sin tildes, sin caracteres especiales ni espacios
function _cs_norm(string $s): string {
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', _cs_sinTildes($s)));
}

// Base del nombre de parámetro: quita paréntesis y sufijos (ej. "Amonio (NH4)" → "AMONIO")
function _cs_base(string $s): string {
    $t = preg_replace('/\s*\(.*$/', '', trim($s));   // quita "(...)" y lo que sigue
    $t = preg_replace('/[^A-Z0-9]/', '', strtoupper(_cs_sinTildes($t)));
    return $t;
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
$anio       = $m[1];
$mesNombre  = ucfirst(strtolower($m[2]));   // "Marzo"
$mesUpper   = strtoupper($mesNombre);       // "MARZO"

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
// PARÁMETROS ACTIVOS (para mapear nombre del template → Id_Parametro)
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
// PLANTILLA
// ============================================================
$templatePath = $base_path . '/modules/laboratorio/muestra/plantilla/RESULTADOS ANÁLISIS FISICO QUIMICOS Y MICROBIOLOGICOS DE LA CALIDAD AMBIENTAL DE AGUAS SUPERFICIALES - 2026.xlsx';
if (!file_exists($templatePath)) { http_response_code(500); die('Plantilla no encontrada'); }
$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();   // única hoja: "MARZO 2026"
$sheet->setTitle($mesUpper . ' ' . $anio); // "MARZO 2026"
$sheet->setShowGridlines(false);           // vista limpia, sin rejilla

// Título (fila 4): texto fijo + mes - año
$sheet->getCell('B4')->setValue(
    "RESULTADOS ANÁLISIS FISICO QUIMICOS Y MICROBIOLOGICOS DE LA CALIDAD AMBIENTAL DE AGUAS SUPERFICIALES\n" .
    $mesUpper . ' - ' . $anio
);

// ============================================================
// MAPEO ESTACIONES: cabeceras F7..O7 ↔ muestras
// Reglas (por puntaje, cada muestra se usa 1 sola vez):
//   100 = Nivel_Agua normalizado idéntico a la cabecera
//    80 = Nivel_Agua EMPIEZA con la cabecera  (ej. "CANAL EVACUADOR DEL DESARENADOR" → "CANAL EVACUADOR")
//    70 = la cabecera EMPIEZA con el Nivel_Agua
//    60 = la descripción completa contiene TODAS las palabras (≥3 letras) de la cabecera
//         (ej. "CANAL MADRE: EN LA ENTRADA AL DESARENADOR" → "ENTRADA DESARENADOR")
// ============================================================
$columnasEstacion = [];   // letra columna (F..O) => [id_muestra, header]
for ($ci = 6; $ci <= 15; $ci++) {
    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci);
    $header = trim((string)$sheet->getCell($letra . '7')->getValue());
    if ($header === '') continue;
    $columnasEstacion[$letra] = ['id' => null, 'header' => $header];
}

function _cs_palabrasSignificativas(string $norm): array {
    // palabras de ≥3 letras del texto normalizado (sin caracteres especiales)
    $t = preg_replace('/[^A-Z0-9 ]/', ' ', $norm);
    $palabras = array_values(array_filter(explode(' ', $t), fn($p) => strlen($p) >= 3));
    return $palabras;
}

// Asignación "most constrained first": para cada columna se calculan sus
// mejores candidatas (mayor puntaje); se asignan PRIMERO las columnas con
// MENOS candidatas, de modo que una muestra ambigua (ej. varias "CANAL MADRE")
// no sea "robada" por una columna que tiene alternativas.
$colCands = [];
foreach ($columnasEstacion as $letra => $infoCol) {
    $hdrNorm = _cs_norm($infoCol['header']);
    // OJO: las "palabras significativas" deben calcularse sobre el texto CON
    // espacios (la normalización _cs_norm pega todo y rompe el match por palabras).
    $hdrWords = _cs_palabrasSignificativas(_cs_sinTildes(strtoupper($infoCol['header'])));

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

// Orden: primero las columnas con menos candidatas (más restrictivas),
// y dentro del mismo tamaño, las de mayor puntaje.
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
// FILAS DE DATOS DEL TEMPLATE (estructura fija verificada)
// Físicos: 10-14 | Químicos: 17-29 | Microbiológicos: 32-33
// ============================================================
$filasDatos = [10, 11, 12, 13, 14, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 32, 33];

// Para cada fila: nombre del template → Id_Parametro
$filasInfo = [];
foreach ($filasDatos as $fila) {
    $nombreTpl = trim((string)$sheet->getCell('B' . $fila)->getValue());
    $idParam   = $paramsActivos[_cs_base($nombreTpl)] ?? 0;
    $filasInfo[$fila] = ['nombre' => $nombreTpl, 'id_param' => $idParam];
}

// ============================================================
// LIMPIAR VALORES DE EJEMPLO DE LA PLANTILLA en las filas de datos
// (F..O): evita que queden valores del mes de muestra cuando una
// estación no tiene muestra asignada o el resultado es nulo.
// ============================================================
foreach ($filasDatos as $fila) {
    for ($ci = 6; $ci <= 15; $ci++) {
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

// ============================================================
// ELIMINAR FILAS SIN RESULTADOS (de abajo hacia arriba para no romper índices)
// ============================================================
rsort($filasOmitir);
foreach ($filasOmitir as $fila) {
    $sheet->removeRow($fila, 1);
}

// Fila final de cada fila de datos tras los borrados
function _cs_filaFinal(int $filaOrig, array $omitidas): int {
    $menores = 0;
    foreach ($omitidas as $o) { if ($o < $filaOrig) $menores++; }
    return $filaOrig - $menores;
}

// ============================================================
// LIMPIAR COLUMNAS MAX/MIN (P/Q) — se reescriben con valores calculados
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
            $sheet->getCell($letra . $nf)->setValue(null);   // en blanco (sin valores de ejemplo)
            continue;
        }
        if (_cs_esNumero($v)) {
            $sheet->getCell($letra . $nf)->setValue(_cs_aNumero($v));
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
// BARRIDO FINAL: SIN BORDES Y SIN RELLENOS en todo el rango usado
// (decisión del usuario: ninguna celda con borde; todo en blanco)
// ============================================================
$maxFila = 45;   // el template llega a la fila 45 (bloque LMP); tras borrar filas, menos
$sheet->getStyle('B4:Q' . $maxFila)->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_NONE, 'color' => ['argb' => 'FF000000']],
    ],
    'fill' => [
        'fillType' => Fill::FILL_NONE,
        'startColor' => ['argb' => 'FFFFFFFF'],
    ],
]);

// ============================================================
// OUTPUT
// ============================================================
$filename = 'CALIDAD_SUPERFICIAL_' . $mesUpper . '_' . $anio . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
