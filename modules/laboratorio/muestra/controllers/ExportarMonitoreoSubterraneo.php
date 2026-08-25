<?php
/**
 * Exportar Monitoreo de Aguas Subterraneas
 * Rellena la plantilla Excel con datos de PostgreSQL.
 * URL: ?module=laboratorio&action=muestra&subaction=exportar_monitoreo_subterraneo
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/config/db_postgresql.php';
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
use PhpOffice\PhpSpreadsheet\Style\Color;

$conn = Conexion::conectar();
if (!$conn) { http_response_code(500); die('No se pudo conectar a SQL Server'); }

$pdoPg = ConexionPostgreSQL::conectar();
if (!$pdoPg) { http_response_code(500); die('No se pudo conectar a PostgreSQL'); }

// Log de diagnóstico: se escribe cada vez que se invoca el exportador
$debugLog = $base_path . '/logs/export_debug.log';
file_put_contents($debugLog, date('Y-m-d H:i:s') . ' - ExportarMonitoreoSubterraneo invocado. URL: ' . ($_SERVER['REQUEST_URI'] ?? 'CLI') . PHP_EOL, FILE_APPEND);

// ─── Template ────────────────────────────────────────────────────
$templatePath = 'D:/SISTEMAS/gestionTI2026/modules/laboratorio/muestra/plantilla/AGUAS SUBTERRANEAS - PRIMER MONITOREO.xlsx';
if (!file_exists($templatePath)) {
    // Intentar ruta relativa
    $templatePath = $base_path . '/templates/AGUAS SUBTERRANEAS - PRIMER MONITOREO -2026.xlsx';
}
if (!file_exists($templatePath)) {
    http_response_code(500);
    die('No se encontro la plantilla Excel en: ' . $templatePath);
}

// ─── Mapeo de columnas Excel → columna PostgreSQL ───────────────
// Estructura de la plantilla 2026:
//  B=M#, C=Este, D=Norte, E=CodPozo(PECH), F=IRHS
//  G-J: In-Situ (CE, pH, STD, T)
//  K-W: Laboratorio
//  X-Z: me/L calculados (Mg, Na, Ca)
//  AA : RAS calculado
//  AB : Grado de restricción
//  AC-AE: Microbiológicos
$mapaColumnas = [
    // In-Situ (pozos_monitoreo)
    'G' => ['tabla' => 'pozos_monitoreo', 'col' => 'ce',     'param' => 'Conductividad Electrica'],
    'H' => ['tabla' => 'pozos_monitoreo', 'col' => 'ph',     'param' => 'pH'],
    'I' => ['tabla' => 'pozos_monitoreo', 'col' => 'std',    'param' => 'Solidos Totales Disueltos'],
    'J' => ['tabla' => 'pozos_monitoreo', 'col' => 't',      'param' => 'Temperatura'],
    // Laboratorio (calidad_agua_laboratorio)
    'K' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'turbidez',                'param' => 'Turbidez'],
    'L' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'nitratos',                'param' => 'Nitratos'],
    'M' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'nitritos',                'param' => 'Nitritos'],
    'N' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'cobre',                   'param' => 'Cobre'],
    'O' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'cromohexavalente',        'param' => 'Cromo Hexavalente'],
    'P' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'sulfatos',                'param' => 'Sulfatos'],
    'Q' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'manganeso',               'param' => 'Manganeso'],
    'R' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'zinc',                    'param' => 'Zinc'],
    'S' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'hierro',                  'param' => 'Hierro'],
    'T' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'amonio',                  'param' => 'Amonio'],
    'U' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'magnesio',                'param' => 'Magnesio (mg/L)'],
    'V' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'sodio',                   'param' => 'Sodio (mg/L)'],
    'W' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'calcio',                  'param' => 'Calcio (mg/L)'],
    // Microbiológicos
    'AC' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'coliformestotales',       'param' => 'Coliformes Totales'],
    'AD' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'coliformestermotolerantes','param' => 'Coliformes Termotolerantes'],
    'AE' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'escherichiacoli',         'param' => 'Escherichia coli'],
];

// Pesos moleculares y valencias por defecto (los mismos de la plantilla)
$pesosMolDefecto = ['Mg' => 24.305, 'Na' => 22.989, 'Ca' => 40.078];
$valencias       = ['Mg' => 2,      'Na' => 1,      'Ca' => 2];

// ─── Función auxiliar: buscar Id_Pozo por IRHS o PECH ────────────
function buscarIdPozo($conn, $codPozo, $codIRHS) {
    $idPozoReal = null;
    $codPozo = trim((string)$codPozo);
    $codIRHS = trim((string)$codIRHS);

    // 1) Match exacto por código IRHS
    if ($idPozoReal === null && $codIRHS !== '' && $codIRHS !== '-') {
        $stmt = sqlsrv_query($conn, "SELECT Id_Pozo FROM laboratorio.Catastro_Pozo WHERE codigo = ?", [$codIRHS]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idPozoReal = trim((string)$row['Id_Pozo']);
        }
    }

    // 2) Match parcial por código IRHS (p.ej. plantilla sin -SP o con espacios)
    if ($idPozoReal === null && $codIRHS !== '' && $codIRHS !== '-') {
        $stmt = sqlsrv_query($conn, "SELECT TOP 1 Id_Pozo FROM laboratorio.Catastro_Pozo WHERE codigo LIKE ?", ['%' . $codIRHS . '%']);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idPozoReal = trim((string)$row['Id_Pozo']);
        }
    }

    // 3) Match exacto por código PECH (codigopech) o Id_Pozo
    if ($idPozoReal === null && $codPozo !== '' && $codPozo !== '-') {
        $stmt = sqlsrv_query($conn, "SELECT TOP 1 Id_Pozo FROM laboratorio.Catastro_Pozo WHERE codigopech = ? OR Id_Pozo = ?", [$codPozo, $codPozo]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idPozoReal = trim((string)$row['Id_Pozo']);
        }
    }

    return $idPozoReal;
}

// ─── Función auxiliar: parsear límite de la fila 10 ───────────────
// Devuelve ['min'=>x, 'max'=>y] o ['max'=>z] según el texto del límite.
function parseLimite($valor) {
    $valor = trim(str_replace([' ', ','], ['', '.'], (string)$valor));
    if ($valor === '' || $valor === '-' || $valor === '0') return null;
    // Rango tipo "6.5-8.5" o "6,5 – 8,5"
    $valor = str_replace(['–', '—'], '-', $valor);
    if (strpos($valor, '-') !== false) {
        $partes = explode('-', $valor, 2);
        $min = str_replace(',', '.', trim($partes[0]));
        $max = str_replace(',', '.', trim($partes[1]));
        if (is_numeric($min) && is_numeric($max)) {
            return ['min' => floatval($min), 'max' => floatval($max)];
        }
    }
    // Valor tipo "0.05" o "1000"
    if (is_numeric($valor)) {
        return ['max' => floatval($valor)];
    }
    return null;
}

// ─── Función auxiliar: determinar si un valor excede el límite ───
function excedeLimite($valor, $limite) {
    if (!is_numeric($valor) || $limite === null) return false;
    $v = floatval($valor);
    if (isset($limite['min']) && $v < $limite['min']) return true;
    if (isset($limite['max']) && $v > $limite['max']) return true;
    return false;
}

// ─── Función auxiliar: pintar celda de rojo ───────────────────────
function marcarRojo($sheet, $celda) {
    $sheet->getStyle($celda)
          ->getFont()
          ->getColor()
          ->setARGB(Color::COLOR_RED);
}

// ─── Función auxiliar: mejor match por fecha ─────────────────────
$mejorMatch = function($registros, $fechaObj) {
    if (empty($registros)) return null;
    if (!$fechaObj) return $registros[0];
    $mejor = $registros[0];
    $mejorDiff = PHP_INT_MAX;
    $tsObj = strtotime($fechaObj);
    foreach ($registros as $r) {
        $f = $r['fechamonitoreo'] ?? $r['fecha_toma_muestra'] ?? '';
        if (!$f) continue;
        $diff = abs(strtotime($f) - $tsObj);
        if ($diff < $mejorDiff) { $mejorDiff = $diff; $mejor = $r; }
    }
    return $mejor;
};

try {
    $spreadsheet = IOFactory::load($templatePath);

    // ─── Determinar fecha del monitoreo ──────────────────────────
    $fechaMonitoreo = null;
    $stmtUltimo = $pdoPg->query("SELECT MAX(fechamonitoreo) as ultima FROM " . PG_SCHEMA . ".pozos_monitoreo");
    if ($stmtUltimo && $rowUlt = $stmtUltimo->fetch(PDO::FETCH_ASSOC)) {
        $fechaMonitoreo = $rowUlt['ultima'] ?? null;
    }

    // ─── Cargar TODOS los datos de PG (fuera del loop, solo una vez) ──
    $datosInsitu = [];
    $stmtInsituAll = $pdoPg->query("SELECT * FROM " . PG_SCHEMA . ".pozos_monitoreo");
    while ($row = $stmtInsituAll->fetch(PDO::FETCH_ASSOC)) {
        $idP = $row['id_pozo'] ?? '';
        if ($idP) $datosInsitu[$idP][] = $row;
    }

    $datosLab = [];
    $stmtLabAll = $pdoPg->query("SELECT cal.*, pm.fechamonitoreo 
                                  FROM " . PG_SCHEMA . ".calidad_agua_laboratorio cal
                                  LEFT JOIN " . PG_SCHEMA . ".pozos_monitoreo pm ON pm.id_medicion = cal.id_medicion");
    while ($row = $stmtLabAll->fetch(PDO::FETCH_ASSOC)) {
        $idP = $row['id_pozo'] ?? '';
        if ($idP) $datosLab[$idP][] = $row;
    }

    $stats = ['pozos_procesados' => 0, 'celdas_rellenadas' => 0, 'errores' => 0];

    // ─── Procesar cada hoja (valle) ──────────────────────────────
    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        // Solo procesar hojas de 2026
        if (strpos($sheetName, '2026') === false) continue;

        $sheet = $spreadsheet->getSheetByName($sheetName);
        $maxRow = $sheet->getHighestRow();

        // Leer pesos moleculares/valencias de la plantilla si existen (U2/U3, V2/V3, W2/W3)
        $pesosMol = $pesosMolDefecto;
        $valenciasPlantilla = [];
        foreach ([['U','Mg'],['V','Na'],['W','Ca']] as $pair) {
            $col = $pair[0]; $key = $pair[1];
            $pesoCel = $sheet->getCell($col . '2')->getValue();
            $valCel  = $sheet->getCell($col . '3')->getValue();
            if (is_numeric($pesoCel)) $pesosMol[$key] = floatval($pesoCel);
            if (is_numeric($valCel))  $valenciasPlantilla[$key] = floatval($valCel);
        }
        // Usar valencias de plantilla solo si existen; si no, las por defecto
        $valenciasUsar = array_merge($valencias, $valenciasPlantilla);

        // ─── Leer límites de Consumo Humano (fila 10) para resaltar en rojo ───
        $limites = [];
        foreach ($mapaColumnas as $colLetra => $map) {
            $rawLimite = $sheet->getCell($colLetra . '10')->getValue();
            $limites[$colLetra] = parseLimite($rawLimite);
        }

        // ─── Iterar filas de datos (desde fila 11 hasta antes de notas) ───
        for ($row = 11; $row <= $maxRow; $row++) {
            $codPozo = trim((string)($sheet->getCell('E' . $row)->getValue() ?? ''));
            $codIRHS = trim((string)($sheet->getCell('F' . $row)->getValue() ?? ''));
            if (($codPozo === '' || $codPozo === '-') && ($codIRHS === '' || $codIRHS === '-')) continue;

            // Detectar fin de datos (filas de notas/estadísticas)
            $cellB = trim((string)($sheet->getCell('B' . $row)->getValue() ?? ''));
            if (!empty($cellB) && strpos($cellB, 'M') !== 0) {
                if (stripos($cellB, 'No se realiza') !== false ||
                    stripos($cellB, 'MAX') !== false ||
                    stripos($cellB, 'MIN') !== false) break;
            }

            // Buscar Id_Pozo en SQL Server
            $idPozoReal = buscarIdPozo($conn, $codPozo, $codIRHS);
            if (!$idPozoReal) continue;

            // Buscar datos para este pozo (mejor match por fecha)
            $insitu = $mejorMatch($datosInsitu[$idPozoReal] ?? [], $fechaMonitoreo);
            $lab = $mejorMatch($datosLab[$idPozoReal] ?? [], $fechaMonitoreo);
            if (!$insitu && !$lab) continue;

            $stats['pozos_procesados']++;

            // ─── Rellenar celdas según mapeo ─────────────────────
            foreach ($mapaColumnas as $colLetra => $map) {
                $valor = null;
                if ($map['tabla'] === 'pozos_monitoreo' && $insitu) {
                    $valor = $insitu[$map['col']] ?? null;
                } elseif ($map['tabla'] === 'calidad_agua_laboratorio' && $lab) {
                    $valor = $lab[$map['col']] ?? null;
                }

                if ($valor !== null && $valor !== '') {
                    $cell = $sheet->getCell($colLetra . $row);
                    $valorNumerico = is_numeric($valor) ? round(floatval($valor), 2) : $valor;
                    $cell->setValue($valorNumerico);
                    $stats['celdas_rellenadas']++;

                    // Resaltar en rojo si excede el límite de Consumo Humano (fila 10)
                    if (excedeLimite($valor, $limites[$colLetra])) {
                        marcarRojo($sheet, $colLetra . $row);
                    }
                }
            }

            // ─── Calcular me/L, RAS y Grado ─────────────────────────
            // Se escriben VALORES calculados (no solo fórmulas) para que se vean
            // inmediatamente al abrir el archivo, sin depender del recálculo de Excel.
            $mg = [
                'Mg' => $sheet->getCell('U' . $row)->getValue(),
                'Na' => $sheet->getCell('V' . $row)->getValue(),
                'Ca' => $sheet->getCell('W' . $row)->getValue(),
            ];

            // me/L = (mg/L * valencia) / peso molecular
            $me = [];
            foreach (['Mg','Na','Ca'] as $k) {
                $v = $mg[$k];
                if (is_numeric($v) && $pesosMol[$k] != 0) {
                    $me[$k] = (floatval($v) * $valenciasUsar[$k]) / $pesosMol[$k];
                } else {
                    $me[$k] = null;
                }
            }

            $mapMe = ['Mg' => 'X', 'Na' => 'Y', 'Ca' => 'Z'];
            foreach ($mapMe as $k => $colLetra) {
                if ($me[$k] !== null) {
                    $sheet->getCell($colLetra . $row)->setValue(round($me[$k], 2));
                    $stats['celdas_rellenadas']++;
                }
            }

            // RAS = Na / sqrt((Ca + Mg) / 2)
            if ($me['Na'] !== null && $me['Ca'] !== null && $me['Mg'] !== null) {
                $denominador = ($me['Ca'] + $me['Mg']) / 2;
                if ($denominador > 0) {
                    $ras = $me['Na'] / sqrt($denominador);
                    $sheet->getCell('AA' . $row)->setValue(round($ras, 2));

                    // Grado de restricción según la fórmula de la plantilla:
                    // <3 NINGUNO, <=9 LEVE O MODERADO, >9 SEVERO
                    if ($ras < 3) {
                        $grado = 'NINGUNO';
                    } elseif ($ras <= 9) {
                        $grado = 'LEVE O MODERADO';
                    } else {
                        $grado = 'SEVERO';
                    }
                    $sheet->getCell('AB' . $row)->setValue($grado);
                    $stats['celdas_rellenadas'] += 2;
                }
            }

            // ─── Rellenar datos del pozo desde Catastro_Pozo ──────
            $stmtPz = sqlsrv_query($conn,
                "SELECT codigopech, codigo, coord_este, coord_norte FROM laboratorio.Catastro_Pozo WHERE Id_Pozo = ?",
                [$idPozoReal]);
            if ($stmtPz && $rowPz = sqlsrv_fetch_array($stmtPz, SQLSRV_FETCH_ASSOC)) {
                // Columna E: código PECH (ej: PM-28A, CH-181)
                $codPech = trim((string)($rowPz['codigopech'] ?? ''));
                if ($codPech !== '') {
                    $sheet->getCell('E' . $row)->setValue($codPech);
                }
                // Columna F: código IRHS (ej: IRHS-13-12-01-908)
                $codIRHSdb = trim((string)($rowPz['codigo'] ?? ''));
                if ($codIRHSdb !== '') {
                    $sheet->getCell('F' . $row)->setValue($codIRHSdb);
                }
                // Coordenadas
                if (is_numeric($rowPz['coord_este'])) {
                    $sheet->getCell('C' . $row)->setValue(round(floatval($rowPz['coord_este']), 2));
                }
                if (is_numeric($rowPz['coord_norte'])) {
                    $sheet->getCell('D' . $row)->setValue(round(floatval($rowPz['coord_norte']), 2));
                }
            }
        }
    }

    // ─── Guardar y descargar ─────────────────────────────────────
    $outputPath = $base_path . '/temp/monitoreo_subterraneo_exportado.xlsx';
    $outputDir = dirname($outputPath);
    if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

    $writer = new Xlsx($spreadsheet);
    // Precalcular fórmulas (aunque ya no queden fórmulas en X-Z/AA-AB, otras hojas/históricas se benefician)
    $writer->setPreCalculateFormulas(true);
    $writer->save($outputPath);

    ob_end_clean();

    // Descargar (nombre único y headers anti-caché para evitar que el navegador use archivo viejo)
    $nombreArchivo = 'AGUAS_SUBTERRANEAS_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . filesize($outputPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($outputPath);
    exit;

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>
