<?php
/**
 * ExportarDrenes.php — v5 (limpia, 2026-08-25)
 * Exporta el informe de Calidad de Agua Drenada (Es_Drene=1).
 *
 * Comportamiento (decisiones del usuario):
 *  - Plantilla: "RESULTADOS AGUA DRENADA  2026.xlsx" (arreglada por el usuario; aún trae
 *    dibujos/gráficos históricos → se quitan ANTES de cargar con _plantilla_sin_graficos,
 *    porque PhpSpreadsheet los deja referenciados sin escribirlos y Excel pide reparar).
 *  - Solo se exportan los parámetros con al menos UN resultado no nulo en alguna estación.
 *  - MAX/MIN se calculan en PHP y se escriben como valores (sin fórmulas).
 *  - Cifras EXACTAS de BD: valor sin round() + formato General (F..N).
 *  - LÍMITES DEL MODAL: normativa_nombre + categorias[] → marcado en ROJO solo si el valor
 *    supera el límite MÁXIMO de la normativa seleccionada.
 *  - Colores explícitos: azul FUERTE 335693 (título) + texto blanco bold; azul SUAVE
 *    DAE3F3 (cabeceras, datos, secciones, LMP) + texto negro/rojo.
 *  - Config: db.php → config.php → Auth.php (patrón ExportarProyectoMonitoreo).
 *
 * Uso: ExportarDrenes.php?id_proyecto=X&normativa_nombre=...&categorias[]=...
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
// HELPERS (mismos criterios que Calidad Superficial)
// ============================================================
function _dre_sinTildes(string $s): string {
    $b = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü','ñ','Ñ'];
    $r = ['a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U','n','N'];
    return str_replace($b, $r, $s);
}

// Normaliza para comparar: MAYÚSCULAS, sin tildes, sin caracteres especiales ni espacios.
// ⚠️ strtoupper ANTES del preg_replace (si va después, "Dren Bitín" → "DB").
function _dre_norm(string $s): string {
    return preg_replace('/[^A-Z0-9]/', '', strtoupper(_dre_sinTildes($s)));
}

// Base del nombre de parámetro: quita paréntesis y sufijos (ej. "Amonio (NH4)" → "AMONIO")
function _dre_base(string $s): string {
    $t = preg_replace('/\s*\(.*$/', '', trim($s));
    $t = preg_replace('/[^A-Z0-9]/', '', strtoupper(_dre_sinTildes($t)));
    return $t;
}

function _dre_palabrasSignificativas(string $texto): array {
    $t = preg_replace('/[^A-Z0-9 ]/', ' ', strtoupper(_dre_sinTildes($texto)));
    return array_values(array_filter(explode(' ', $t), fn($p) => strlen($p) >= 3));
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

// Fila final tras eliminar filas sin resultados
function _dre_filaFinal(int $filaOrig, array $omitidas): int {
    $menores = 0;
    foreach ($omitidas as $o) { if ($o < $filaOrig) $menores++; }
    return $filaOrig - $menores;
}

/**
 * Copia de la plantilla SIN dibujos ni gráficos (charts/shapes/vml/media).
 * Decisión del usuario (2026-08-25): el export no los necesita; además PhpSpreadsheet
 * descarta esas partes al guardar pero deja sus referencias en las rels y Content_Types
 * → Excel avisa "Parte quitada: Forma de dibujo" / "formato de archivo no es valido".
 * Se copia el zip eliminando xl/drawings|charts|embeddings|media (y sus rels/Overrides)
 * y los elementos <drawing>/<legacyDrawing> de las hojas. Mantiene TODO lo demás
 * (estilos, merges, anchos de columna, colores de la plantilla).
 */
function _plantilla_sin_graficos(string $rutaPlantilla, string $dirTemp): string {
    $destino = $dirTemp . '/plantilla_sin_graficos_' . md5($rutaPlantilla) . '.xlsx';
    if (file_exists($destino) && filesize($destino) > 0) return $destino; // ya procesada
    if (!is_dir($dirTemp)) @mkdir($dirTemp, 0777, true);
    $src = new ZipArchive();
    $dst = new ZipArchive();
    if ($src->open($rutaPlantilla) !== true) return $rutaPlantilla;
    if ($dst->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        $src->close();
        return $rutaPlantilla;
    }
    $esParteGraf = function (string $t): bool {
        return (bool)preg_match('#(^|/)(drawings|charts|embeddings|media)/#i', $t);
    };
    $n = $src->numFiles;
    for ($i = 0; $i < $n; $i++) {
        $nombre = $src->getNameIndex($i);
        if ($esParteGraf($nombre)) continue; // dibujos, gráficos, vml, media → fuera
        $contenido = $src->getFromIndex($i);
        if ($contenido === false) continue;
        if ($nombre === '[Content_Types].xml' || substr($nombre, -5) === '.rels') {
            $contenido = preg_replace_callback('/<Override[^>]*PartName="([^"]+)"[^>]*\/>/i', function ($m) use ($esParteGraf) {
                return $esParteGraf($m[1]) ? '' : $m[0];
            }, $contenido);
            $contenido = preg_replace_callback('/<Relationship[^>]*Target="([^"]+)"[^>]*\/>/i', function ($m) use ($esParteGraf) {
                return $esParteGraf($m[1]) ? '' : $m[0];
            }, $contenido);
        } elseif (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $nombre)) {
            // Quitar <drawing r:id="..."/> y <legacyDrawing .../> de cada hoja
            $contenido = preg_replace('~<(?:drawing|legacyDrawing|legacyDrawingHF)\b[^>]*/>~i', '', $contenido);
            if ($contenido === null) $contenido = $src->getFromIndex($i);
        }
        $dst->addFromString($nombre, $contenido);
    }
    $src->close();
    $dst->close();
    return $destino;
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
$mesUpper  = strtoupper($m[2]);        // "MARZO"

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
// PARÁMETROS ACTIVOS (nombre → Id_Parametro)
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
            $resultados[$idM][$idP] = $v;   // '' cuando es null
        }
    }
}

// ============================================================
// LÍMITES SELECCIONADOS EN EL MODAL (normativa + categorías)
// ============================================================
$limitesNormativas = [];
$categoriasSel = $_GET['categorias'] ?? [];
if (!is_array($categoriasSel)) { $categoriasSel = [$categoriasSel]; }
$categoriasSel = array_values(array_filter(array_map(
    fn($c) => trim((string)$c),
    $categoriasSel
), fn($c) => $c !== ''));
$normativaNombre = trim((string)($_GET['normativa_nombre'] ?? ''));

if (!empty($categoriasSel)) {
    $valsBusqueda = [];
    foreach ($categoriasSel as $cat) {
        $valsBusqueda[] = $cat;
        $normCat = _dre_norm($cat);
        if ($normCat !== '' && !in_array($normCat, $valsBusqueda, true)) {
            $valsBusqueda[] = $normCat;
        }
    }
    // 3 columnas comparadas con IN → se triplican los placeholders (bug histórico:
    // con N en vez de 3×N, sqlsrv_query fallaba en silencio y nunca marcaba rojo)
    $ph = implode(',', array_fill(0, count($valsBusqueda), '?'));
    $sqlLim = "SELECT l.Id_Parametro, l.Valor_Min, l.Valor_Max
               FROM laboratorio.Limite_Legal l
               LEFT JOIN laboratorio.Normativa_Legal n ON n.Id_Normativa = l.Id_Normativa
               WHERE l.Activo = 1
                 AND (LTRIM(RTRIM(l.Descripcion)) IN ($ph)
                      OR LTRIM(RTRIM(n.Descripcion)) IN ($ph)
                      OR LTRIM(RTRIM(n.Nombre)) IN ($ph))
                 AND (LTRIM(RTRIM(n.Nombre)) = ? OR ? = '')";
    $paramsLim = array_merge($valsBusqueda, $valsBusqueda, $valsBusqueda, [$normativaNombre, $normativaNombre]);
    $stmtLim = sqlsrv_query($conn, $sqlLim, $paramsLim);
    if ($stmtLim !== false) {
        while ($lim = sqlsrv_fetch_array($stmtLim, SQLSRV_FETCH_ASSOC)) {
            $idParamLim = intval($lim['Id_Parametro'] ?? 0);
            if ($idParamLim <= 0) continue;
            $limitesNormativas[$idParamLim][] = [
                'min' => $lim['Valor_Min'],
                'max' => $lim['Valor_Max'],
            ];
        }
    }
}

// ============================================================
// PLANTILLA (hoja moderna de referencia: JULIO 2025)
// ============================================================
$templatePath = $base_path . '/modules/laboratorio/muestra/plantilla/RESULTADOS AGUA DRENADA  2026.xlsx';
if (!file_exists($templatePath)) { http_response_code(500); die('Plantilla no encontrada'); }
// La plantilla de drenes aún trae dibujos/gráficos históricos → copia limpia ANTES de cargar
$templatePath = _plantilla_sin_graficos($templatePath, $base_path . '/temp');

$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getSheetByName('JULIO 2025 ');
if (!$sheet) { http_response_code(500); die('Hoja de plantilla no encontrada'); }

// Eliminar TODAS las demás hojas del libro (archivo histórico con ~23 hojas;
// el informe debe llevar únicamente la hoja del mes)
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
// ============================================================
$columnasEstacion = [];
for ($ci = 6; $ci <= 12; $ci++) {
    $letra = Coordinate::stringFromColumnIndex($ci);
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

// Limpiar valores de ejemplo de la plantilla (F..L de filas de datos)
foreach ($filasDatos as $fila) {
    for ($ci = 6; $ci <= 12; $ci++) {
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

rsort($filasOmitir);
foreach ($filasOmitir as $fila) {
    $sheet->removeRow($fila, 1);
}

// ============================================================
// LIMPIAR COLUMNAS MAX/MIN (M/N) — se reescriben calculados
// ============================================================
for ($f = 4; $f <= 46; $f++) {
    $sheet->getCell('M' . $f)->setValue(null);
    $sheet->getCell('N' . $f)->setValue(null);
}
$sheet->getCell('M9')->setValue('MAX');
$sheet->getCell('N9')->setValue('MIN');

// ============================================================
// ESCRIBIR VALORES POR ESTACIÓN + MAX/MIN + MARCADO EN ROJO
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
            $sheet->getCell($letra . $nf)->setValue(null);   // en blanco
            continue;
        }
        if (_dre_esNumero($v)) {
            $sheet->getCell($letra . $nf)->setValue(_dre_aNumero($v));   // cifra EXACTA
            $valoresNum[] = _dre_aNumero($v);
        } else {
            $sheet->getCell($letra . $nf)->setValue($v);     // ">1100", "N.D." etc.
        }
        // Quitar color/fuente heredados de los valores de ejemplo de la plantilla
        $sheet->getStyle($letra . $nf)->getFont()->getColor()->setARGB('FF000000');
        $sheet->getStyle($letra . $nf)->getFont()->setBold(false);

        // Marcar en ROJO si excede el límite máximo de la normativa seleccionada
        if (_dre_esNumero($v)) {
            $vn = _dre_aNumero($v);
            $vLimites = $limitesNormativas[$idParam] ?? null;
            if (!empty($vLimites)) {
                foreach ($vLimites as $subLim) {
                    if (isset($subLim['max']) && $subLim['max'] !== null
                        && trim((string)$subLim['max']) !== ''
                        && _dre_esNumero($subLim['max'])
                        && $vn > _dre_aNumero($subLim['max'])) {
                        $sheet->getStyle($letra . $nf)->getFont()->getColor()->setARGB('FFFF0000');
                        break;
                    }
                }
            }
        }
    }

    // MAX / MIN calculados en PHP (valores, no fórmulas)
    if (!empty($valoresNum)) {
        $sheet->getCell('M' . $nf)->setValue(max($valoresNum));
        $sheet->getCell('N' . $nf)->setValue(min($valoresNum));
    }
}

// ============================================================
// CIFRAS EXACTAS EN PANTALLA: formato General en el área de resultados (F..N)
// ============================================================
$sheet->getStyle('F9:N46')->getNumberFormat()->setFormatCode('General');

// ============================================================
// COLORES DE LA PLANTILLA (azul fuerte + azul suave) — EXPLÍCITOS
// ============================================================
$azulFuerte = 'FF335693';
$azulSuave  = 'FFDAE3F3';
$maxFilaColores = $sheet->getHighestDataRow();

// Fila 4 (título): azul fuerte + texto blanco bold
$sheet->getStyle('B4:Q4')
      ->getFill()->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB($azulFuerte);
$sheet->getStyle('B4:Q4')->getFont()->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle('B4:Q4')->getFont()->setBold(true);

// Cabeceras (filas 6-7): azul suave + texto negro
$sheet->getStyle('B6:Q7')
      ->getFill()->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB($azulSuave);
$sheet->getStyle('B6:Q7')->getFont()->getColor()->setARGB('FF000000');

// Filas de datos/secciones/LMP (9 en adelante): azul suave
// (solo relleno; el color de fuente ya quedó negro o rojo al escribir los datos)
$sheet->getStyle('B9:N' . $maxFilaColores)
      ->getFill()->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB($azulSuave);

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
$nombreSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombreProyecto);
$filename = 'Drenes_' . $nombreSafe . '_' . date('Ymd_His') . '.xlsx';

// Archivo temporal con nombre ÚNICO por request (dos descargas no se pisan)
$outputPath = $base_path . '/temp/drenes_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.xlsx';
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