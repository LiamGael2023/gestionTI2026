<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
// require_once $base_path . '/config/db_postgresql.php'; // Datos se leen de SQL Server
require_once $base_path . '/config/config.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$autoloadLibs = $base_path . '/libs/vendor/autoload.php';
if (!file_exists($autoloadLibs)) {
    http_response_code(500);
    die('No se encontro la libreria de exportacion (libs/vendor/autoload.php)');
}
require_once $autoloadLibs;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;

$id_proyecto = intval($_GET['id_proyecto'] ?? 0);
if ($id_proyecto <= 0) {
    http_response_code(400);
    die('Proyecto invalido');
}

$conn = Conexion::conectar();
if (!$conn) {
    http_response_code(500);
    die('Error de conexion a SQL Server');
}

// Conexion PostgreSQL ya no es necesaria - resultados se leen de SQL Server

// ─── Obtener datos del proyecto ─────────────────────────────────
$sqlProyecto = "SELECT TOP 1 Id_Proyecto, Nombre_Proyecto, Valle, Temporada, Estado
               FROM laboratorio.Proyecto_Monitoreo
               WHERE Id_Proyecto = ? AND Activo = 1";
$stmtProyecto = sqlsrv_query($conn, $sqlProyecto, [$id_proyecto]);
if ($stmtProyecto === false) {
    http_response_code(500);
    die('Error al obtener proyecto: ' . print_r(sqlsrv_errors(), true));
}
$proyecto = sqlsrv_fetch_array($stmtProyecto, SQLSRV_FETCH_ASSOC);
if (!$proyecto) {
    http_response_code(404);
    die('Proyecto no encontrado');
}

$valleProyecto = strtoupper(trim((string)($proyecto['Valle'] ?? '')));

// ─── Obtener pozos del proyecto ─────────────────────────────────
$sqlMuestras = "SELECT m.Id_Muestra,
                       m.Id_Pozo,
                       m.Eje_X,
                       m.Eje_Y,
                       p.codigopech,
                       p.codigo AS codigo_irhs,
                       mpa.Orden
                FROM laboratorio.Muestra_Lab m
                LEFT JOIN laboratorio.Catastro_Pozo p ON p.Id_Pozo = m.Id_Pozo
                LEFT JOIN laboratorio.Monitoreo_Pozo_Asignacion mpa
                       ON mpa.Id_Proyecto = m.Id_Proyecto
                      AND mpa.Id_Pozo = m.Id_Pozo
                      AND mpa.Activo = 1
                WHERE m.Id_Proyecto = ? AND m.Activo = 1
                ORDER BY mpa.Orden, m.Id_Muestra";
$stmtMuestras = sqlsrv_query($conn, $sqlMuestras, [$id_proyecto]);
if ($stmtMuestras === false) {
    http_response_code(500);
    die('Error al obtener muestras: ' . print_r(sqlsrv_errors(), true));
}

$pozosProyecto = [];
while ($row = sqlsrv_fetch_array($stmtMuestras, SQLSRV_FETCH_ASSOC)) {
    $idPozo = trim((string)($row['Id_Pozo'] ?? ''));
    if ($idPozo === '') continue;
    $pozosProyecto[$idPozo] = [
            'id_muestra' => intval($row['Id_Muestra']),
            'id_pozo' => $idPozo,
            'eje_x' => $row['Eje_X'],
            'eje_y' => $row['Eje_Y'],
            'codigopech' => trim((string)($row['codigopech'] ?? '')),
            'codigo_irhs' => trim((string)($row['codigo_irhs'] ?? '')),
            'orden' => intval($row['Orden'] ?? 0),
        ];
}

if (empty($pozosProyecto)) {
    http_response_code(409);
    die('El proyecto no tiene pozos para exportar');
}

// ─── Obtener categorías seleccionadas ───────────────────────────
$categoriasInput = $_GET['categorias'] ?? [];
if (!is_array($categoriasInput)) {
    $categoriasInput = [$categoriasInput];
}
$categoriasSeleccionadas = [];
foreach ($categoriasInput as $cat) {
    $desc = trim((string)$cat);
    if ($desc !== '') {
        $categoriasSeleccionadas[] = $desc;
    }
}

// ─── Obtener límites de normativas seleccionadas ────────────────
$normalizarTexto = function ($txt) {
    $txt = trim((string)$txt);
    if ($txt === '') return '';
    $txt = str_replace(
        ['Á','É','Í','Ó','Ú','á','é','í','ó','ú','Ñ','ñ'],
        ['A','E','I','O','U','a','e','i','o','u','N','n'],
        $txt
    );
    return strtoupper(preg_replace('/\s+/', ' ', $txt));
};

$limitesNormativas = [];
if (!empty($categoriasSeleccionadas)) {
    // Generar tanto descripciones originales como normalizadas para asegurar coincidencia total
    $searchValues = [];
    foreach ($categoriasSeleccionadas as $cat) {
        $raw = trim((string)$cat);
        if ($raw !== '') {
            $searchValues[] = $raw;
            $norm = $normalizarTexto($raw);
            if ($norm !== '' && !in_array($norm, $searchValues)) {
                $searchValues[] = $norm;
            }
        }
    }
    
    if (!empty($searchValues)) {
            // La query compara 3 columnas con IN (...) — se necesitan 3 x N placeholders
            // (l.Descripcion, n.Descripcion, n.Nombre). Antes solo se pasaban N y
            // sqlsrv_query fallaba silenciosamente: el marcado en rojo por categorías
            // NUNCA llegó a cargar límites.
            $placeholders = implode(',', array_fill(0, count($searchValues), '?'));
            $sqlLimites = "SELECT l.Id_Parametro,
                                  l.Valor_Min,
                                  l.Valor_Max,
                                  l.Unidad_Medida,
                                  LTRIM(RTRIM(l.Descripcion)) AS DescripcionLimite,
                                  LTRIM(RTRIM(n.Descripcion)) AS DescripcionNormativa
                           FROM laboratorio.Limite_Legal l
                           LEFT JOIN laboratorio.Normativa_Legal n ON n.Id_Normativa = l.Id_Normativa
                           WHERE l.Activo = 1
                             AND (LTRIM(RTRIM(l.Descripcion)) IN ($placeholders)
                                  OR LTRIM(RTRIM(n.Descripcion)) IN ($placeholders)
                                  OR LTRIM(RTRIM(n.Nombre)) IN ($placeholders))";
            $valoresBusqueda = array_values($searchValues);
            $stmtLimites = sqlsrv_query(
                $conn,
                $sqlLimites,
                array_merge($valoresBusqueda, $valoresBusqueda, $valoresBusqueda)
            );
            if ($stmtLimites !== false) {
                while ($lim = sqlsrv_fetch_array($stmtLimites, SQLSRV_FETCH_ASSOC)) {
                    $idParametro = intval($lim['Id_Parametro'] ?? 0);
                    if ($idParametro <= 0) continue;
                    $limitesNormativas[$idParametro][] = [
                        'min' => $lim['Valor_Min'],
                        'max' => $lim['Valor_Max'],
                    ];
                }
            }
        }
}

// ─── Plantilla del usuario ────────────────────────────────────────
$templatePath = 'D:/SISTEMAS/gestionTI2026/modules/laboratorio/muestra/plantilla/AGUAS SUBTERRANEAS - PRIMER MONITOREO.xlsx';
if (!file_exists($templatePath)) {
    $templatePath = $base_path . '/templates/AGUAS SUBTERRANEAS - PRIMER MONITOREO -2026.xlsx';
}
if (!file_exists($templatePath)) {
    http_response_code(500);
    die('No se encontro la plantilla Excel');
}

$spreadsheet = IOFactory::load($templatePath);

// ─── Mapeo de columnas Excel → datos PostgreSQL ─────────────────
$mapaColumnas = [
    'G' => ['tabla' => 'pozos_monitoreo', 'col' => 'ce',     'param' => 'Conductividad Electrica', 'id_param' => 2],
    'H' => ['tabla' => 'pozos_monitoreo', 'col' => 'ph',     'param' => 'pH', 'id_param' => 1],
    'I' => ['tabla' => 'pozos_monitoreo', 'col' => 'std',    'param' => 'Solidos Totales Disueltos', 'id_param' => 3],
    'J' => ['tabla' => 'pozos_monitoreo', 'col' => 't',      'param' => 'Temperatura', 'id_param' => 9],
    'K' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'turbidez',                'param' => 'Turbidez', 'id_param' => 8],
    'L' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'nitratos',                'param' => 'Nitratos', 'id_param' => 10],
    'M' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'nitritos',                'param' => 'Nitritos', 'id_param' => 11],
    'N' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'cobre',                   'param' => 'Cobre', 'id_param' => 12],
    'O' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'cromohexavalente',        'param' => 'Cromo Hexavalente', 'id_param' => 13],
    'P' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'sulfatos',                'param' => 'Sulfatos', 'id_param' => 16],
    'Q' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'manganeso',               'param' => 'Manganeso', 'id_param' => 14],
    'R' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'zinc',                    'param' => 'Zinc', 'id_param' => 17],
    'S' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'hierro',                  'param' => 'Hierro', 'id_param' => 15],
    'T' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'amonio',                  'param' => 'Amonio', 'id_param' => 33],
    'U' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'magnesio',                'param' => 'Magnesio', 'id_param' => 34],
    'V' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'sodio',                   'param' => 'Sodio', 'id_param' => 35],
    'W' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'calcio',                  'param' => 'Calcio', 'id_param' => 36],
    'AC' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'coliformestotales',       'param' => 'Coliformes Totales', 'id_param' => 20],
    'AD' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'coliformestermotolerantes','param' => 'Coliformes Termotolerantes', 'id_param' => 21],
    'AE' => ['tabla' => 'calidad_agua_laboratorio', 'col' => 'escherichiacoli',         'param' => 'Escherichia coli', 'id_param' => 22],
];

// Pesos moleculares y valencias
$pesosMolDefecto = ['Mg' => 24.305, 'Na' => 22.989, 'Ca' => 40.078];
$valencias = ['Mg' => 2, 'Na' => 1, 'Ca' => 2];

// Los datos se obtienen de SQL Server (Resultado_Analisis) en lugar de PostgreSQL

// ─── Función auxiliar: limpiar y parsear número ─────────────────
function parseNumberClean($val) {
    if ($val === null || $val === '') return null;
    $s = trim((string)$val);
    if ($s === '' || $s === '-' || $s === '–' || $s === '—') return null;
    
    // Quitar espacios internos (ej: "1 500" -> "1500")
    $s = str_replace([' ', "\t", "\n", "\r"], '', $s);
    
    // Casos especiales de miles y decimales
    if (preg_match('/^\d{1,3}(,\d{3})+$/', $s)) {
        // Formato miles con comas: "4,590" o "12,345" -> "4590"
        $s = str_replace(',', '', $s);
    } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
        // Formato miles con puntos: "4.590" -> "4590"
        $s = str_replace('.', '', $s);
    } elseif (preg_match('/^\d+,\d{1,2}$/', $s)) {
        // Formato decimal con coma: "6,5" o "8,50" -> "6.5"
        $s = str_replace(',', '.', $s);
    } else {
        // Si hay comas y puntos combinados
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            if (strrpos($s, '.') > strrpos($s, ',')) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            }
        } else {
            // Solo comas: si tiene 3 dígitos tras la coma "4,590", es miles. Si tiene 1 o 2 "6,5", es decimal.
            if (preg_match('/^\d+,\d{3}$/', $s)) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        }
    }
    
    return is_numeric($s) ? floatval($s) : null;
}

// ─── Función auxiliar: parsear límite ───────────────────────────
function parseLimite($valor) {
    if ($valor === null || $valor === '') return null;
    $valor = trim((string)$valor);
    if ($valor === '' || $valor === '-' || $valor === '0') return null;
    $valor = str_replace(['–', '—'], '-', $valor);
    if (strpos($valor, '-') !== false) {
        $partes = explode('-', $valor, 2);
        $min = parseNumberClean($partes[0]);
        $max = parseNumberClean($partes[1]);
        if ($min !== null || $max !== null) {
            return ['min' => $min, 'max' => $max];
        }
    }
    $v = parseNumberClean($valor);
    if ($v !== null) {
        return ['max' => $v];
    }
    return null;
}

// ─── Función auxiliar: verificar si excede límite ───────────────
function excedeLimite($valor, $limite) {
    $v = parseNumberClean($valor);
    if ($v === null || empty($limite)) return false;
    
    // Si $limite contiene un grupo de normativas seleccionadas
    if (isset($limite[0]) && is_array($limite[0])) {
        foreach ($limite as $subLim) {
            if (excedeLimiteSingle($v, $subLim)) return true;
        }
        return false;
    }
    
    return excedeLimiteSingle($v, $limite);
}

function excedeLimiteSingle($v, $lim) {
    if (!is_array($lim)) return false;
    
    // Evaluar ÚNICAMENTE si supera el límite superior (max)
    if (isset($lim['max']) && $lim['max'] !== null && trim((string)$lim['max']) !== '') {
        $max = parseNumberClean($lim['max']);
        if ($max !== null && $v > $max) {
            return true;
        }
    }
    
    return false;
}

// ─── Función auxiliar: marcar celda en rojo ─────────────────────
function marcarRojo($sheet, $celda) {
    $sheet->getStyle($celda)
          ->getFont()
          ->getColor()
          ->setARGB(Color::COLOR_RED);
}

// Normaliza un nombre de hoja/valle para comparar sin distraer: MAYÚSCULAS,
// sin tildes, sin símbolos ni espacios (ej: "1° MONITOREO VALLE CHICAMA-2024"
// -> "1MONITOREOVALLECHICAMA2024")
function _normHoja($s) {
    $b = ['Á','É','Í','Ó','Ú','Ü','Ñ','á','é','í','ó','ú','ü','ñ','°','-','–','—',' ','.',"'",'"'];
    $r = ['A','E','I','O','U','U','N','a','e','i','o','u','u','n','','','','','','','',''];
    return strtoupper(str_replace($b, $r, strtoupper((string)$s)));
}

// Fecha de monitoreo ya no se necesita de PG - los resultados se leen por Id_Proyecto

// ─── Determinar hoja según valle ────────────────────────────────
// 1) Mapeo directo: nombres exactos esperados en la plantilla
$hojaDestino = null;
$mapeoHojas = [
    'CHAO' => 'SUB. CHAO - 1°- 2026',
    'VIRU' => 'SUB. VIRÚ - 1°- 2026 ',
    'VIRÚ' => 'SUB. VIRÚ - 1°- 2026 ',
    'MOCHE' => 'SUB. MOCHE - 1°- 2026 ',
    'CHICAMA' => 'SUB. CHICAMA - 1°- 2026 ',
];

foreach ($mapeoHojas as $valle => $hoja) {
    if (strpos($valleProyecto, $valle) !== false) {
        $hojaDestino = $hoja;
        break;
    }
}

$hojasDisponibles = $spreadsheet->getSheetNames();

// 2) Si el nombre exacto no existe en la plantilla, buscar por coincidencia
//    del valle dentro del nombre de la hoja (sin tildes/mayúsculas),
//    prefiriendo la hoja del año vigente (2026).
//    SOLO se aceptan hojas con el layout 2026 (la de referencia VIRU):
//    B6 = "PARAMETRO" y B7 = "UM". Las hojas viejas (2024, otros formatos)
//    tienen otra estructura y corromperían el llenado por celdas fijas.
$esFormato2026 = function ($nombreHoja) use ($spreadsheet) {
    $h = $spreadsheet->getSheetByName($nombreHoja);
    if (!$h) {
        return false;
    }
    $b6 = trim((string)$h->getCell('B6')->getValue());
    $b7 = trim((string)$h->getCell('B7')->getValue());
    return stripos($b6, 'PARAMETRO') !== false && stripos($b7, 'UM') !== false;
};

if (!$hojaDestino || !in_array($hojaDestino, $hojasDisponibles)) {
    $hojaDestino = null;
    $normValle = _normHoja($valleProyecto);
    if ($normValle !== '') {
        $pref2026 = null;
        $otra     = null;
        foreach ($hojasDisponibles as $nombreHoja) {
            $normHoja = _normHoja($nombreHoja);
            if ($normHoja === '' || strpos($normHoja, $normValle) === false) {
                continue;
            }
            if (!$esFormato2026($nombreHoja)) {
                continue;
            }
            if (strpos($normHoja, '2026') !== false) {
                $pref2026 = $nombreHoja;
                break;
            }
            if ($otra === null) {
                $otra = $nombreHoja;
            }
        }
        $hojaDestino = $pref2026 !== null ? $pref2026 : $otra;
    }
}

if (!$hojaDestino) {
    http_response_code(500);
    die('No se encontro la hoja para el valle: ' . $valleProyecto
        . '. Hojas disponibles en la plantilla: ' . implode(' | ', $hojasDisponibles));
}

$sheet = $spreadsheet->getSheetByName($hojaDestino);
if (!$sheet) {
    http_response_code(500);
    die('No se pudo cargar la hoja: ' . $hojaDestino);
}

// ─── Título de la hoja según el N° de MONITOREO (Temporada) ─────
// Ej: Temporada "2026-01" → 1° ... "2026-02" → 2°. La plantilla trae "1°":
// se reemplaza el grado por el del proyecto (SUB. CHAO - 1°- 2026 → - 2°- 2026).
$numeroMonitoreo = 1;
if (preg_match('/-\s*(\d{1,2})\s*$/', trim((string)($proyecto['Temporada'] ?? '')), $mTemp)) {
    $numeroMonitoreo = max(1, intval($mTemp[1]));
}
$tituloHoja = $sheet->getTitle();
$tituloFinal = preg_replace('/(?<!\d)1°/', $numeroMonitoreo . '°', $tituloHoja, 1);
$tituloFinal = ($tituloFinal === null || $tituloFinal === $tituloHoja) ? $tituloHoja : $tituloFinal;
$sheet->setTitle($tituloFinal);

// ─── Leer pesos moleculares de la plantilla ─────────────────────
$pesosMol = $pesosMolDefecto;
$valenciasPlantilla = [];
foreach ([['U','Mg'],['V','Na'],['W','Ca']] as $pair) {
    $col = $pair[0]; $key = $pair[1];
    $pesoCel = $sheet->getCell($col . '2')->getValue();
    $valCel = $sheet->getCell($col . '3')->getValue();
    if (is_numeric($pesoCel)) $pesosMol[$key] = floatval($pesoCel);
    if (is_numeric($valCel)) $valenciasPlantilla[$key] = floatval($valCel);
}
$valenciasUsar = array_merge($valencias, $valenciasPlantilla);

// ─── Leer límites de la plantilla (fila 10 - Consumo Humano) ────
$limitesPlantilla = [];
foreach ($mapaColumnas as $colLetra => $map) {
    $rawLimite = $sheet->getCell($colLetra . '10')->getValue();
    $limitesPlantilla[$colLetra] = parseLimite($rawLimite);
}

// ─── Cargar resultados desde SQL Server ─────────────────────────
$sqlResultados = "SELECT ra.Id_Parametro, ra.Valor_Hallado, sa.Id_Muestra
                  FROM laboratorio.Resultado_Analisis ra
                  INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                  WHERE sa.Id_Muestra IN (
                      SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Id_Proyecto = ? AND Activo = 1
                  )
                  AND ra.Activo = 1 AND sa.Activo = 1";
$stmtResultados = sqlsrv_query($conn, $sqlResultados, [$id_proyecto]);
$resultadosMap = [];
if ($stmtResultados !== false) {
    while ($rowRes = sqlsrv_fetch_array($stmtResultados, SQLSRV_FETCH_ASSOC)) {
        $idMuestra = intval($rowRes['Id_Muestra']);
        $idParametro = intval($rowRes['Id_Parametro']);
        $resultadosMap[$idMuestra][$idParametro] = $rowRes['Valor_Hallado'];
    }
}

// ─── Rellenar datos por pozo ────────────────────────────────────
$filaCursor   = 11;   // siguiente fila libre para pozos SIN orden asignado
$numeroMuestra = 1;   // correlativo de respaldo para pozos sin orden
$filasOcupadas = [];  // filas ya usadas (por orden real o por fallback)
$filaPorPozo  = [];   // id_pozo => fila real donde quedó cada pozo
$stats = ['pozos_procesados' => 0, 'celdas_rellenadas' => 0, 'marcados_rojo' => 0];
// Registra qué filas tienen al menos un resultado microbiológico (id_param 20, 21 o 22)
$filasTienenMicro = [];
$idsMicro = [20, 21, 22];

foreach ($pozosProyecto as $idPozo => $pozo) {
    $idMuestra = $pozo['id_muestra'];
    $resultados = $resultadosMap[$idMuestra] ?? [];
    
    // ── Fila según el N° de ORDEN real del pozo ──────────────────
    // La plantilla pre-imprime M1..M62 en B11..B72: el pozo con Orden N
    // debe quedar en la fila 10+N (no en orden de Id_Muestra).
    $orden = intval($pozo['orden'] ?? 0);
    $filaPozo = ($orden > 0) ? (10 + $orden) : 0;
    if ($filaPozo < 11 || isset($filasOcupadas[$filaPozo])) {
        // Sin orden o conflicto (orden repetido) → siguiente fila libre
        while (isset($filasOcupadas[$filaCursor])) { $filaCursor++; }
        $filaPozo = $filaCursor;
    }
    $filasOcupadas[$filaPozo] = true;
    $filaPorPozo[$idPozo] = $filaPozo;
    $filaActual = $filaPozo;
    
    $stats['pozos_procesados']++;
    
    // Columna B: Número de muestra (M1, M2, ... según el N° de orden real)
    $nroVisible = ($orden > 0) ? $orden : $numeroMuestra;
    $sheet->setCellValue('B' . $filaActual, 'M' . $nroVisible);
    
    // Columnas C y D: Coordenadas
    if (is_numeric($pozo['eje_x'])) {
        $sheet->setCellValue('C' . $filaActual, round(floatval($pozo['eje_x']), 2));
    }
    if (is_numeric($pozo['eje_y'])) {
        $sheet->setCellValue('D' . $filaActual, round(floatval($pozo['eje_y']), 2));
    }
    
    // Columna E: Código PECH
    if ($pozo['codigopech'] !== '') {
        $sheet->setCellValue('E' . $filaActual, $pozo['codigopech']);
    }
    
    // Columna F: Código IRHS
    if ($pozo['codigo_irhs'] !== '') {
        $sheet->setCellValue('F' . $filaActual, $pozo['codigo_irhs']);
    }
    
    // Rellenar columnas de datos
    $valoresParaCalculo = ['Mg' => null, 'Na' => null, 'Ca' => null];
    $filaTieneMicro = false;
    
    foreach ($mapaColumnas as $colLetra => $map) {
        $idParam = $map['id_param'];
        $valor = $resultados[$idParam] ?? null;
        
        if ($valor !== null && $valor !== '') {
                    // Cifra EXACTA tal como está en la BD (decimal 18,4): sin redondeo.
                    // El formato de celda se normaliza a General más abajo para que
                    // Excel no la muestre redondeada (la plantilla trae formatos de
                    // enteros/1 decimal en G..AE).
                    $valorNumerico = is_numeric($valor) ? floatval($valor) : $valor;
                    $sheet->setCellValue($colLetra . $filaActual, $valorNumerico);
            $stats['celdas_rellenadas']++;
            
            // Detectar si este parámetro es microbiológico y tiene valor real (> 0)
            if (in_array($idParam, $idsMicro) && floatval($valor) > 0) {
                $filaTieneMicro = true;
            }
            
            // Guardar valores para cálculo de me/L
            if ($idParam == 34) $valoresParaCalculo['Mg'] = floatval($valor);
            if ($idParam == 35) $valoresParaCalculo['Na'] = floatval($valor);
            if ($idParam == 36) $valoresParaCalculo['Ca'] = floatval($valor);
            
            // Marcar en rojo si excede límite
            // Si el usuario seleccionó categorías en el modal, usar EXCLUSIVAMENTE esas categorías.
            // Solo si no se seleccionó ninguna categoría, se usa la plantilla como fallback.
            $limiteUsar = null;
            if (!empty($categoriasSeleccionadas)) {
                $limiteUsar = $limitesNormativas[$idParam] ?? null;
            } else {
                $limiteUsar = $limitesPlantilla[$colLetra] ?? null;
            }
            
            if ($limiteUsar && excedeLimite($valor, $limiteUsar)) {
                marcarRojo($sheet, $colLetra . $filaActual);
                $stats['marcados_rojo']++;
            }
        } else {
            // Celda vacía: poner guión
            $sheet->setCellValue($colLetra . $filaActual, '-');
        }
    }
    
    // Registrar si la fila tiene resultados microbiológicos
    $filasTienenMicro[$filaActual] = $filaTieneMicro;
    
    // Calcular me/L y RAS
    $me = [];
    foreach (['Mg','Na','Ca'] as $k) {
        $v = $valoresParaCalculo[$k];
        if ($v !== null && $pesosMol[$k] != 0) {
            $me[$k] = ($v * $valenciasUsar[$k]) / $pesosMol[$k];
        } else {
            $me[$k] = null;
        }
    }
    
    // Escribir me/L (guion si no se puede calcular)
    $mapMe = ['Mg' => 'X', 'Na' => 'Y', 'Ca' => 'Z'];
    foreach ($mapMe as $k => $colLetra) {
        if ($me[$k] !== null) {
            $sheet->setCellValue($colLetra . $filaActual, round($me[$k], 2));
            $stats['celdas_rellenadas']++;
        } else {
            $sheet->setCellValue($colLetra . $filaActual, '-');
        }
    }
    
    // Calcular RAS solo si existen los tres valores
    if ($me['Na'] !== null && $me['Ca'] !== null && $me['Mg'] !== null) {
        $denominador = ($me['Ca'] + $me['Mg']) / 2;
        if ($denominador > 0) {
            $ras = $me['Na'] / sqrt($denominador);
            $sheet->setCellValue('AA' . $filaActual, round($ras, 2));
            
            // Grado de restricción
            if ($ras < 3) {
                $grado = 'NINGUNO';
            } elseif ($ras <= 9) {
                $grado = 'LEVE O MODERADO';
            } else {
                $grado = 'SEVERO';
            }
            $sheet->setCellValue('AB' . $filaActual, $grado);
            $stats['celdas_rellenadas'] += 2;
        } else {
            $sheet->setCellValue('AA' . $filaActual, '-');
            $sheet->setCellValue('AB' . $filaActual, '-');
        }
    } else {
        $sheet->setCellValue('AA' . $filaActual, '-');
        $sheet->setCellValue('AB' . $filaActual, '-');
    }
    
    // Avanzar el cursor de filas libres más allá de la fila usada
            if ($filaActual >= $filaCursor) { $filaCursor = $filaActual + 1; }
            $numeroMuestra++;
        }

    // ─── Límite de muestras: hasta la última muestra recolectada ─────
    // El monitoreo puede estar incompleto: el reporte solo llega hasta el
    // N° de orden máximo que tiene muestra. Las posiciones sin muestra
    // (huecos dentro del límite) se rellenan con "-" y las filas sobrantes
    // de la plantilla (ej. M41..M60 si el tope es 40) se ELIMINAN para que
    // todo el bloque de muestras mantenga un único formato.
    $limiteMuestras = 0;
    foreach ($pozosProyecto as $pozo) {
        if ($pozo['orden'] > $limiteMuestras) $limiteMuestras = $pozo['orden'];
    }
    if ($limiteMuestras <= 0) $limiteMuestras = count($pozosProyecto);
    $filaLimite = 10 + $limiteMuestras;

    // Columnas de datos del layout 2026 (B es el N° de muestra, A queda en blanco)
    $columnasDatosHoja = ['C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE'];

    // Huecos dentro del límite (muestras aún no recolectadas) → "-" + etiqueta M#
    for ($fila = 11; $fila <= $filaLimite; $fila++) {
        if (isset($filasOcupadas[$fila])) continue;
        $sheet->setCellValue('B' . $fila, 'M' . ($fila - 10));
        foreach ($columnasDatosHoja as $col) {
            $sheet->setCellValue($col . $fila, '-');
        }
    }

    // Última fila pre-impresa de muestras en la plantilla (etiquetas M1..Mn en B,
    // que pueden ser RichText). Se detecta recorriendo B desde la fila 11.
    $filaUltimaPlantilla = 10;
    for ($r = 11; $r <= 200; $r++) {
        $bVal = $sheet->getCell('B' . $r)->getValue();
        if ($bVal instanceof RichText) $bVal = $bVal->getPlainText();
        if (is_string($bVal) && preg_match('/^M\s*\d+$/i', trim($bVal))) {
            $filaUltimaPlantilla = $r;
        } elseif ($r > 11) {
            break;
        }
    }

    // Eliminar filas de muestras pre-impresas más allá del límite (ej. M41..M60).
    // Debajo viven notas/resumen con fórmulas y merges: se reubican con el mismo
    // corrimiento y las referencias de fila se re-mapean (G11:G70 → G11:G50).
    if ($filaLimite < $filaUltimaPlantilla) {
        $filaInicioEliminar = $filaLimite + 1;
        $nEliminar = $filaUltimaPlantilla - $filaLimite;

        // Capturar las fórmulas del resumen ANTES de borrar (removeRow de
        // PhpSpreadsheet ya reescribe referencias por su cuenta; re-mapeamos
        // el texto ORIGINAL para controlar el resultado).
        $formulasResumen = [];
        $filaMaxAntes = $sheet->getHighestDataRow();
        for ($r = $filaUltimaPlantilla + 1; $r <= $filaMaxAntes; $r++) {
            foreach ($columnasDatosHoja as $col) {
                $valFormula = $sheet->getCell($col . $r)->getValue();
                if (is_string($valFormula) && strpos($valFormula, '=') === 0) {
                    $formulasResumen[$col . $r] = $valFormula;
                }
            }
        }

        $mergesResumen = $sheet->getMergeCells();

        // Quitar merges antes de borrar (removeRow no los reubica)
        foreach ($mergesResumen as $rangoMerge) {
            $sheet->unmergeCells($rangoMerge);
        }
        $sheet->removeRow($filaInicioEliminar, $nEliminar);

        // Re-crear los merges: los que viven debajo de la zona borrada se desplazan
            // con el mismo corrimiento; los de la cabecera (arriba) se restauran igual.
            foreach ($mergesResumen as $rangoMerge) {
                if (preg_match('/^([A-Z]{1,2})(\d+):([A-Z]{1,2})(\d+)$/', $rangoMerge, $mMerge)) {
                    if (intval($mMerge[2]) > $filaUltimaPlantilla) {
                        $sheet->mergeCells(
                            $mMerge[1] . (intval($mMerge[2]) - $nEliminar)
                            . ':' . $mMerge[3] . (intval($mMerge[4]) - $nEliminar)
                        );
                    } else {
                        $sheet->mergeCells($rangoMerge);
                    }
                }
            }

        // Re-mapear referencias de fila (sobre el texto ORIGINAL capturado):
        //   fila ≤ límite            → se conserva
        //   límite < fila ≤ plantilla → se ancla al límite (rangos MAX/MIN)
        //   fila > plantilla          → se desplaza hacia arriba (refs a filas vivas)
        $remapearFilasFormula = function ($formula) use ($filaLimite, $filaUltimaPlantilla, $nEliminar) {
            return preg_replace_callback('/\$?([A-Z]{1,2})\$?(\d+)/', function ($mm) use ($filaLimite, $filaUltimaPlantilla, $nEliminar) {
                $refFila = intval($mm[2]);
                if ($refFila > $filaUltimaPlantilla) {
                    $refFila -= $nEliminar;
                } elseif ($refFila > $filaLimite) {
                    $refFila = $filaLimite;
                }
                return $mm[1] . $refFila;
            }, $formula);
        };
        foreach ($formulasResumen as $addr => $formula) {
            if (preg_match('/^([A-Z]{1,2})(\d+)$/', $addr, $mAddr) && intval($mAddr[2]) > $filaUltimaPlantilla) {
                $sheet->setCellValue($mAddr[1] . (intval($mAddr[2]) - $nEliminar), $remapearFilasFormula($formula));
            }
        }

        // Área de impresión vigente → ajustar al nuevo alto
        if ($sheet->getPageSetup()->getPrintArea()) {
            $sheet->getPageSetup()->setPrintArea('A1:' . $sheet->getHighestColumn() . $sheet->getHighestDataRow());
        }
    }

    // (variables de estilo definidas a continuación)

// ─── Aplicar estilos de color y bordes ─────────────────────────────
// Paleta de colores:
//   Encabezado (filas 1-10): verde oscuro (#548235), letra BLANCA
//   Datos regulares (fila 11+): verde limón claro (#E2EFDA), letra negra
//   Columnas microbiológicas (AC-AE): verde muy suave (#F0FAF0), letra negra
//   Fila con micro positivo: azul suave (#D9E8F5) toda la fila, letra negra
//   Columna A filas 1-3: sin color (no se colorea)
//   Texto: negro excepto encabezado (blanco) y celdas que excedan límites (rojo)

$colorEncabezado = 'FF548235';  // Verde oscuro
$colorDatos      = 'FFE2EFDA';  // Verde limón claro
$colorMicro      = 'FFF0FAF0';  // Verde muy suave (microbiológicos)
$colorFilaMicro  = 'FFD9E8F5';  // Azul suave para filas con resultado microbiológico positivo

// Estilo base de borde blanco fino (da apariencia sin borde visible)
$bordeBlanco = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color'       => ['argb' => 'FFFFFFFF'],
        ],
    ],
];

// Columna más alta de la hoja
$ultimaColHoja = $sheet->getHighestColumn();
// Última fila con datos (la fila real del último pozo, ya mapeada por orden)
$ultimaFilaDatos = $filaActual;

// --- Filas de encabezado (4 a 10): verde oscuro + bordes blancos + texto BLANCO ---
// Las filas 1 a 3 no tienen color de fondo. De la 4 a la 10 todo el texto es blanco con fondo verde oscuro.
$rangoEncabezado = 'A4:' . $ultimaColHoja . '10';
$sheet->getStyle($rangoEncabezado)
      ->getFill()->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB($colorEncabezado);
$sheet->getStyle($rangoEncabezado)->applyFromArray($bordeBlanco);
$sheet->getStyle($rangoEncabezado)->getFont()->getColor()->setARGB('FFFFFFFF');

// Forzar color blanco en todos los objetos RichText (textos con múltiples formatos internos en el Excel)
$maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ultimaColHoja);
for ($r = 4; $r <= 10; $r++) {
    for ($c = 1; $c <= $maxColIndex; $c++) {
        $cell = $sheet->getCellByColumnAndRow($c, $r);
        $val = $cell->getValue();
        if ($val instanceof RichText) {
            foreach ($val->getRichTextElements() as $element) {
                if ($element instanceof Run) {
                    $element->getFont()->getColor()->setARGB('FFFFFFFF');
                }
            }
        }
    }
}

// --- Filas de datos (11 en adelante, desde columna B): verde limón + bordes blancos + texto negro ---
if ($ultimaFilaDatos >= 11) {
    // Aplicar verde limón a las filas de datos desde columna B hasta AB (la columna A queda en blanco)
    $rangoRegular = 'B11:AB' . $ultimaFilaDatos;
    $sheet->getStyle($rangoRegular)
          ->getFill()->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB($colorDatos);
    $sheet->getStyle($rangoRegular)->applyFromArray($bordeBlanco);
    $sheet->getStyle($rangoRegular)->getFont()->getColor()->setARGB('FF000000');

    // Columnas microbiológicas (AC, AD, AE): verde muy suave
        $rangoMicro = 'AC11:AE' . $ultimaFilaDatos;
        $sheet->getStyle($rangoMicro)
              ->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB($colorMicro);
        $sheet->getStyle($rangoMicro)->applyFromArray($bordeBlanco);
        $sheet->getStyle($rangoMicro)->getFont()->getColor()->setARGB('FF000000');

        // Cifras EXACTAS en pantalla: formato General en todas las celdas de datos.
        // La plantilla trae formatos que redondean la VISUALIZACIÓN (columna G:
        // formato contable sin decimales; L/U/M/V: [0]; H/J/N/O/R: 0.0) — con ellos
        // un 8.66 se mostraría como 9 y un 0.05 como 0.
        $sheet->getStyle('G11:AE' . $ultimaFilaDatos)
              ->getNumberFormat()->setFormatCode('General');

    // Pintar fila de azul suave (desde columna B hasta AE) si tiene resultados microbiológicos positivos
    foreach ($filasTienenMicro as $filaM => $tieneMicro) {
        if ($tieneMicro && $filaM <= $ultimaFilaDatos) {
            $rangoFilaMicro = 'B' . $filaM . ':AE' . $filaM;
            $sheet->getStyle($rangoFilaMicro)
                  ->getFill()->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB($colorFilaMicro);
            $sheet->getStyle($rangoFilaMicro)->applyFromArray($bordeBlanco);
            $sheet->getStyle($rangoFilaMicro)->getFont()->getColor()->setARGB('FF000000');
        }
    }

    // Re-aplicar rojo a celdas que exceden límites (el estilo anterior pudo sobrescribir el color de fuente)
        // Recorremos de nuevo cada pozo usando su FILA REAL (la misma del llenado por orden)
        $filaRecheck = 11;
        foreach ($pozosProyecto as $idPozo => $pozo) {
            $idMuestraRC = $pozo['id_muestra'];
            $resultadosRC = $resultadosMap[$idMuestraRC] ?? [];
            if (!isset($filaPorPozo[$idPozo])) continue;
            $filaRecheck = $filaPorPozo[$idPozo];
            foreach ($mapaColumnas as $colLetra => $map) {
                $idParamRC = $map['id_param'];
                $valorRC   = $resultadosRC[$idParamRC] ?? null;
                if ($valorRC === null || $valorRC === '') continue;
                $limiteRC = null;
                if (!empty($categoriasSeleccionadas)) {
                    $limiteRC = $limitesNormativas[$idParamRC] ?? null;
                } else {
                    $limiteRC = $limitesPlantilla[$colLetra] ?? null;
                }
                if ($limiteRC && excedeLimite($valorRC, $limiteRC)) {
                    marcarRojo($sheet, $colLetra . $filaRecheck);
                }
            }
            if ($filaRecheck > $ultimaFilaDatos) break;
        }
}

// ─── Guardar y descargar ────────────────────────────────────────
// Exportar SOLO la hoja del monitoreo: se eliminan todas las demás hojas
// de la plantilla (el reporte debe tener únicamente la pestaña del proyecto).
$nombreHojaFinal = $sheet->getTitle();
foreach (array_reverse($spreadsheet->getSheetNames()) as $nombreOtra) {
    if ($nombreOtra === $nombreHojaFinal) continue;
    $hojaOtra = $spreadsheet->getSheetByName($nombreOtra);
    if ($hojaOtra !== null) {
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($hojaOtra));
    }
}

// Fijar la hoja del valle como ACTIVA para que Excel (y la previsualización
// del frontend vía activeTab) abran en la hoja correcta y no en la primera
// de la plantilla (VIRU). Usa el título FINAL (ya renombrado con el N° de monitoreo).
$spreadsheet->setActiveSheetIndexByName($sheet->getTitle());

$outputPath = $base_path . '/temp/proyecto_monitoreo_exportado.xlsx';
$outputDir = dirname($outputPath);
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

$writer = new Xlsx($spreadsheet);
$writer->setPreCalculateFormulas(true);
$writer->save($outputPath);

ob_end_clean();

// Descargar con nombre único y headers anti-caché
$nombreArchivo = 'MONITOREO_' . $valleProyecto . '_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . filesize($outputPath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
readfile($outputPath);
exit;
?>
