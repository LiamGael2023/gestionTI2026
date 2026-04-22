<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';

if (!defined('BASE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    define('BASE_URL', $protocol . $host . '/gestionTI');
}

require_once $base_path . '/core/Auth.php';

Auth::check();

$autoloadLibs = $base_path . '/libs/vendor/autoload.php';
if (!file_exists($autoloadLibs)) {
    http_response_code(500);
    die('No se encontro la libreria de exportacion (libs/vendor/autoload.php)');
}

require_once $autoloadLibs;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Html as HtmlWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$id_muestra = intval($_GET['id_muestra'] ?? 0);
$previewHtml = (isset($_GET['preview_html']) && $_GET['preview_html'] === '1');
if ($id_muestra <= 0) {
    http_response_code(400);
    die('Muestra invalida');
}

$conn = Conexion::conectar();
if (!$conn) {
    http_response_code(500);
    die('Error de conexion');
}

$sqlMuestra = "SELECT TOP 1
                m.Id_Muestra,
                m.Estado,
                m.Valle,
                m.Tipo_Servicio,
                m.Observacion_Muestra,
                m.Fecha_Toma,
                m.Fecha_Analisis,
                m.Fecha_Validacion,
                m.Fecha_Recepcion,
                CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                da.Id_Muestra AS TieneDetalleAgua,
                da.Uso_Agua,
                da.Fuente_Agua,
                da.Nivel_Agua,
                da.Cantidad_Muestra,
                ds.Id_Muestra AS TieneDetalleSuelo,
                ds.Fuente_Riego,
                ds.Profundidad,
                ds.Numero_Submuestras,
                ds.Cantidad_Muestra AS Cantidad_Suelo,
                CONCAT(ue.nombres, ' ', ue.apellidos) AS Especialista,
                CONCAT(uj.nombres, ' ', uj.apellidos) AS JefeLab
              FROM laboratorio.Muestra_Lab m
              LEFT JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
              LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
              LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
              LEFT JOIN comun.Usuarios ue ON m.Id_Especialista = ue.id_usuario
              LEFT JOIN comun.Usuarios uj ON m.Id_Jefe_Lab = uj.id_usuario
              WHERE m.Id_Muestra = ? AND m.Activo = 1";
$stmtMuestra = sqlsrv_query($conn, $sqlMuestra, [$id_muestra]);

if ($stmtMuestra === false) {
    http_response_code(500);
    die('Error consultando muestra: ' . print_r(sqlsrv_errors(), true));
}

$muestra = sqlsrv_fetch_array($stmtMuestra, SQLSRV_FETCH_ASSOC);

if (!$muestra) {
    http_response_code(404);
    die('Muestra no encontrada');
}

if (strcasecmp(trim((string)($muestra['Estado'] ?? '')), 'Finalizado') !== 0) {
    http_response_code(409);
    die('Solo se puede exportar muestras finalizadas');
}

$formatNumero = function ($valor) {
    if ($valor === null || $valor === '') {
        return '';
    }
    if (!is_numeric($valor)) {
        return trim((string)$valor);
    }
    $num = (float)$valor;
    if (floor($num) == $num) {
        return (string)((int)$num);
    }
    return rtrim(rtrim(number_format($num, 4, '.', ''), '0'), '.');
};

$sqlParametros = "SELECT
                    pa.Id_Parametro,
                    pa.Categoria,
                    pa.Nombre AS Parametro,
                    pa.Unidad_Medida AS UnidadParametro,
                    pa.Metodo_Utilizado
                  FROM laboratorio.Parametro_Analisis pa
                  WHERE pa.Activo = 1
                  ORDER BY pa.Categoria, pa.Nombre";
$stmtParametros = sqlsrv_query($conn, $sqlParametros);
if (!$stmtParametros) {
    http_response_code(500);
    die('No se pudo obtener parametros');
}

$parametros = [];
$idsParametros = [];
while ($p = sqlsrv_fetch_array($stmtParametros, SQLSRV_FETCH_ASSOC)) {
    $parametros[] = $p;
    $idsParametros[intval($p['Id_Parametro'])] = true;
}

$sqlResultados = "SELECT
                    ra.Id_Parametro,
                    MAX(CAST(ra.Valor_Hallado AS NVARCHAR(255))) AS Valor_Hallado
                  FROM laboratorio.Resultado_Analisis ra
                  INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                  WHERE sa.Id_Muestra = ?
                    AND sa.Activo = 1
                    AND ra.Activo = 1
                  GROUP BY ra.Id_Parametro";
$stmtResultados = sqlsrv_query($conn, $sqlResultados, [$id_muestra]);
if (!$stmtResultados) {
    http_response_code(500);
    die('No se pudo obtener resultados');
}

$resultadosPorParametro = [];
while ($r = sqlsrv_fetch_array($stmtResultados, SQLSRV_FETCH_ASSOC)) {
    $resultadosPorParametro[intval($r['Id_Parametro'])] = trim((string)($r['Valor_Hallado'] ?? ''));
}

$limitesPorParametro = [];
$normativas = [];
if (!empty($idsParametros)) {
    $paramIds = array_keys($idsParametros);
    $ph = implode(',', array_fill(0, count($paramIds), '?'));

    $sqlLimites = "SELECT
                     l.Id_Parametro,
                     l.Id_Normativa,
                     n.Nombre AS Normativa,
                     n.Descripcion AS DescripcionNormativa,
                     l.Unidad_Medida,
                     l.Valor_Max,
                     l.Valor_Min
                   FROM laboratorio.Limite_Legal l
                   INNER JOIN laboratorio.Normativa_Legal n ON l.Id_Normativa = n.Id_Normativa
                   WHERE l.Activo = 1
                     AND l.Id_Parametro IN ($ph)
                   ORDER BY l.Id_Normativa, l.Id_Parametro";

    $stmtLimites = sqlsrv_query($conn, $sqlLimites, $paramIds);
    if ($stmtLimites) {
        while ($l = sqlsrv_fetch_array($stmtLimites, SQLSRV_FETCH_ASSOC)) {
            $idp = intval($l['Id_Parametro']);
            $idn = intval($l['Id_Normativa']);
            if (!isset($limitesPorParametro[$idp])) {
                $limitesPorParametro[$idp] = [];
            }
            $limitesPorParametro[$idp][$idn] = $l;

            if (!isset($normativas[$idn])) {
                $normativas[$idn] = [
                    'id' => $idn,
                    'descripcion' => trim((string)($l['DescripcionNormativa'] ?? 'NORMATIVA')),
                    'nombre' => trim((string)($l['Normativa'] ?? '-')),
                ];
            }
        }
    }
}

if (!empty($normativas)) {
    ksort($normativas);
    $normativas = array_values($normativas);
} else {
    $normativas = [[
        'id' => 0,
        'descripcion' => 'NORMATIVA',
        'nombre' => '-',
    ]];
}

$formatLimite = function ($limite) use ($formatNumero) {
    if (!$limite) {
        return '-';
    }
    $min = $formatNumero($limite['Valor_Min'] ?? null);
    $max = $formatNumero($limite['Valor_Max'] ?? null);
    $hasMin = $min !== '';
    $hasMax = $max !== '';
    if ($hasMin && $hasMax) {
        return $min . '-' . $max;
    }
    if ($hasMax) {
        return $max;
    }
    if ($hasMin) {
        return $min;
    }
    return '-';
};

$normalizar = function ($txt) {
    $txt = trim((string)$txt);
    $txt = str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú', 'Ñ', 'ñ'],
        ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u', 'N', 'n'],
        $txt
    );
    $txt = preg_replace('/\s+/', ' ', $txt);
    return strtoupper($txt);
};

$sheetData = [];
foreach ($parametros as $p) {
    $idp = intval($p['Id_Parametro']);
    $limites = $limitesPorParametro[$idp] ?? [];

    $resultado = $resultadosPorParametro[$idp] ?? '';
    $resultado = ($resultado === '' ? '-' : $formatNumero($resultado));

    $normCols = [];
    foreach ($normativas as $normativa) {
        $limite = $limites[$normativa['id']] ?? null;
        $unidad = trim((string)($limite['Unidad_Medida'] ?? ($p['UnidadParametro'] ?? '-')));
        if ($unidad === '') {
            $unidad = '-';
        }
        $normCols[] = [
            'unidad' => $unidad,
            'limite' => $formatLimite($limite),
        ];
    }

    $sheetData[] = [
        'id_parametro' => $idp,
        'categoria' => trim((string)($p['Categoria'] ?? 'Sin categoria')),
        'parametro' => trim((string)($p['Parametro'] ?? '-')),
        'metodo' => trim((string)($p['Metodo_Utilizado'] ?? '-')),
        'normativas' => $normCols,
        'resultado' => $resultado
    ];
}

$agricultor = trim((string)($muestra['Agricultor'] ?? '-'));
$valle = trim((string)($muestra['Valle'] ?? '-'));
$fuente = trim((string)($muestra['Fuente_Agua'] ?? '-'));
$nivelAgua = trim((string)($muestra['Nivel_Agua'] ?? '-'));
$uso = trim((string)($muestra['Uso_Agua'] ?? '-'));
$fuenteRiego = trim((string)($muestra['Fuente_Riego'] ?? '-'));
$profundidadSuelo = trim((string)($muestra['Profundidad'] ?? '-'));
$numeroSubmuestras = trim((string)($muestra['Numero_Submuestras'] ?? '-'));
$cantidad = $muestra['Cantidad_Muestra'] ?? $muestra['Cantidad_Suelo'] ?? '-';
$tipoServicio = trim((string)($muestra['Tipo_Servicio'] ?? '-'));
$especialista = trim((string)($muestra['Especialista'] ?? '-'));
$jefeLab = trim((string)($muestra['JefeLab'] ?? '-'));
$observaciones = trim((string)($muestra['Observacion_Muestra'] ?? ''));

// Formatear observaciones de recepción: checklist + observación extra
$observacionFormateada = $observaciones;
if ($observaciones !== '') {
    $marker = '[RECEPCION]';
    $posMarker = strpos($observaciones, $marker);

    if ($posMarker !== false) {
        $observacionExtra = trim(substr($observaciones, 0, $posMarker));
        $jsonRaw = trim(substr($observaciones, $posMarker + strlen($marker)));

        $recepcion = json_decode($jsonRaw, true);
        if (!is_array($recepcion)) {
            $ini = strpos($jsonRaw, '{');
            $fin = strrpos($jsonRaw, '}');
            if ($ini !== false && $fin !== false && $fin >= $ini) {
                $recepcion = json_decode(substr($jsonRaw, $ini, $fin - $ini + 1), true);
            }
        }

        $lineas = [];
        if (is_array($recepcion) && !empty($recepcion['items']) && is_array($recepcion['items'])) {
            $negativos = [
                'Muestra correctamente rotulada' => 'La muestra no se encuentra correctamente rotulada',
                'Envase limpio y adecuado' => 'El envase se encuentra sucio e inadecuado',
                'Cantidad suficiente de muestra' => 'La cantidad de muestra es insuficiente',
                'Muestra sin contaminación visible' => 'La muestra presenta contaminación visible',
                'Ficha de muestreo completa' => 'La ficha de muestreo está incompleta',
                'Información legible y coherente' => 'La información es ilegible o incoherente'
            ];

            foreach ($recepcion['items'] as $it) {
                $itemTexto = trim((string)($it['item'] ?? ''));
                if ($itemTexto === '') {
                    continue;
                }
                $cumple = !empty($it['cumple']);
                $lineas[] = $cumple
                    ? ('- ' . $itemTexto)
                    : ('- ' . ($negativos[$itemTexto] ?? ('No cumple: ' . $itemTexto)));
            }
        }

        if ($observacionExtra !== '') {
            if (!empty($lineas)) {
                $lineas[] = '';
            }
            $lineas[] = $observacionExtra;
        }

        $observacionFormateada = !empty($lineas) ? implode("\n", $lineas) : ($observacionExtra !== '' ? $observacionExtra : '-');
    }
}

$parseSqlsrvDate = function ($value) {
    if ($value instanceof DateTime) {
        return $value;
    }
    $txt = trim((string)$value);
    if ($txt === '') {
        return null;
    }
    // SQL Server suele devolver: 2026-04-01 09:09:12.850
    $dt = DateTime::createFromFormat('Y-m-d H:i:s.u', $txt);
    if ($dt instanceof DateTime) {
        return $dt;
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $txt);
    if ($dt instanceof DateTime) {
        return $dt;
    }
    try {
        return new DateTime($txt);
    } catch (Exception $e) {
        return null;
    }
};

$fechaTomaObj = $parseSqlsrvDate($muestra['Fecha_Toma'] ?? null);
$fechaFirmaObj = $parseSqlsrvDate($muestra['Fecha_Validacion'] ?? null);
$fechaAnalisisObj = $parseSqlsrvDate($muestra['Fecha_Analisis'] ?? null);

$fechaToma = $fechaTomaObj ? $fechaTomaObj->format('d/m/Y') : '-';
$fechaEmision = $fechaFirmaObj ? $fechaFirmaObj->format('d/m/Y') : ($fechaAnalisisObj ? $fechaAnalisisObj->format('d/m/Y') : date('d/m/Y'));

$mesesES = [
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
    7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SETIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
];
$baseFirma = $fechaFirmaObj ?: ($fechaAnalisisObj ?: new DateTime());
$fechaFirmaMesAnio = ($mesesES[intval($baseFirma->format('n'))] ?? strtoupper($baseFirma->format('F'))) . ' ' . $baseFirma->format('Y');

$countDetalle = function ($tabla) use ($conn, $id_muestra) {
    $sql = "SELECT COUNT(1) AS Total FROM laboratorio.$tabla WHERE Id_Muestra = ? AND Activo = 1";
    $stmt = sqlsrv_query($conn, $sql, [$id_muestra]);
    if ($stmt === false) {
        return 0;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return intval($row['Total'] ?? 0);
};

$countAgua = $countDetalle('Detalle_Agua');
$countSuelo = $countDetalle('Detalle_Suelo');

$isFilled = function ($value) {
    $txt = trim((string)$value);
    if ($txt === '' || $txt === '-') {
        return false;
    }
    $up = strtoupper($txt);
    return $up !== 'NULL' && $up !== 'N/A';
};

$hasDetalleAgua = !empty($muestra['TieneDetalleAgua']);
$hasDetalleSuelo = !empty($muestra['TieneDetalleSuelo']);
$hasDataAgua = (
    $isFilled($muestra['Uso_Agua'] ?? null) ||
    $isFilled($muestra['Fuente_Agua'] ?? null) ||
    $isFilled($muestra['Nivel_Agua'] ?? null) ||
    $isFilled($muestra['Cantidad_Muestra'] ?? null)
);
$hasDataSuelo = (
    $isFilled($muestra['Fuente_Riego'] ?? null) ||
    $isFilled($muestra['Profundidad'] ?? null) ||
    $isFilled($muestra['Numero_Submuestras'] ?? null) ||
    $isFilled($muestra['Cantidad_Suelo'] ?? null)
);

$tipoForzado = strtolower(trim((string)($_GET['tipo_muestra'] ?? '')));

if ($tipoForzado === 'agua') {
    $esSuelo = false;
} elseif ($tipoForzado === 'suelo') {
    $esSuelo = true;
} elseif ($countAgua > 0) {
    // Priorizar agua cuando exista detalle activo de agua.
    // Evita que registros mixtos caigan por error en plantilla de suelo.
    $esSuelo = false;
} elseif ($countSuelo > 0) {
    $esSuelo = true;
} elseif ($hasDataAgua && !$hasDataSuelo) {
    $esSuelo = false;
} elseif ($hasDataSuelo && !$hasDataAgua) {
    $esSuelo = true;
} elseif ($hasDetalleAgua && !$hasDetalleSuelo) {
    $esSuelo = false;
} elseif ($hasDetalleSuelo && !$hasDetalleAgua) {
    $esSuelo = true;
} elseif ($hasDataAgua) {
    // Caso ambiguo: priorizar agua para respetar plantilla azul de resultados de agua.
    $esSuelo = false;
} else {
    // Fallback por contenido, en caso de registros incompletos.
    $esSuelo = (
        ($profundidadSuelo !== '' && $profundidadSuelo !== '-') ||
        ($numeroSubmuestras !== '' && $numeroSubmuestras !== '-') ||
        ($fuenteRiego !== '' && $fuenteRiego !== '-')
    );
}

$plantillaDir = $base_path . '/modules/laboratorio/muestra/plantilla';
$resolverPlantilla = function ($dir, array $preferidas, array $keywords) {
    foreach ($preferidas as $nombre) {
        $ruta = $dir . '/' . $nombre;
        if (file_exists($ruta)) {
            return $ruta;
        }
    }

    $plantillas = glob($dir . '/*.xlsx');
    $plantillas = array_values(array_filter($plantillas ?: [], function ($p) {
        $base = strtoupper((string)basename($p));
        return strpos($base, '~$') !== 0 && strpos($base, 'RESIDUOS') === false;
    }));

    foreach ($plantillas as $p) {
        $base = strtoupper((string)basename($p));
        foreach ($keywords as $kw) {
            if (strpos($base, strtoupper($kw)) !== false) {
                return $p;
            }
        }
    }

    return $plantillas[0] ?? null;
};

if ($esSuelo) {
    $plantillaPath = $resolverPlantilla(
        $plantillaDir,
        ['CSJ-DRDYCS-LAYS – R - 1-RESULTADOS ANALISIS DE  SUELOS.xlsx'],
        ['SUELO', 'SUELOS']
    );
} else {
    $plantillaPath = $resolverPlantilla(
        $plantillaDir,
        [
            'CSJ-DRDYCS-LAYS – R - 2- RESULTADOS ANALISIS DE AGUAS.xlsx',
            'AGUAS SUBTERRANEAS - MONITOREO.xlsx'
        ],
        ['AGUA', 'AGUAS']
    );
}

if (!$plantillaPath || !file_exists($plantillaPath)) {
    http_response_code(500);
    die('No se encontro la plantilla de exportacion');
}

$reader = new XlsxReader();
$reader->setReadDataOnly(false);
$reader->setIncludeCharts(true);
$spreadsheet = $reader->load($plantillaPath);
if ($esSuelo) {
    $sheet = $spreadsheet->getSheetByName('FORMATO 002 -2024');
    if ($sheet === null) {
        $sheet = $spreadsheet->getSheetByName('FORMATO 002-2024');
    }
    if ($sheet === null) {
        $sheet = $spreadsheet->getActiveSheet();
    }
} else {
    // Para agua, usar la hoja activa por defecto de la plantilla oficial.
    $sheet = $spreadsheet->getActiveSheet();
}

// Agrupación de parámetros por categoría
$categoriasMap = [
    'FISICO' => 'fisicos',
    'FISICOS' => 'fisicos',
    'QUIMICO' => 'quimicos',
    'QUIMICOS' => 'quimicos',
    'MICROBIOLOGICO' => 'microbiologicos',
    'MICROBIOLOGICOS' => 'microbiologicos',
];

$grupos = [
    'fisicos' => [],
    'quimicos' => [],
    'microbiologicos' => [],
];

foreach ($sheetData as $item) {
    $catNorm = $normalizar($item['categoria']);
    $dest = 'quimicos';
    foreach ($categoriasMap as $needle => $mapTo) {
        if (strpos($catNorm, $needle) !== false) {
            $dest = $mapTo;
            break;
        }
    }
    $grupos[$dest][] = $item;
}

$tipoServUpper = $normalizar($tipoServicio);
$codigoTipoServicio = trim((string)$tipoServicio);
$esInterno = (
    $tipoServUpper === 'INTERNO' ||
    strpos($tipoServUpper, 'INTERNO') !== false ||
    $codigoTipoServicio === '1' ||
    strtoupper($codigoTipoServicio) === 'I'
);
$esExterno = (
    $tipoServUpper === 'EXTERNO' ||
    strpos($tipoServUpper, 'EXTERNO') !== false ||
    $codigoTipoServicio === '2' ||
    strtoupper($codigoTipoServicio) === 'E'
);

$setCheckMark = function ($cell, $enabled) use ($sheet) {
    $sheet->setCellValue($cell, $enabled ? 'X' : '');
    $alignment = $sheet->getStyle($cell)->getAlignment();
    $alignment->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $alignment->setVertical(Alignment::VERTICAL_CENTER);
};

$normalizeStyleText = function ($value) {
    $txt = strtoupper(trim((string)$value));
    $txt = strtr($txt, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'Ñ' => 'N'
    ]);
    $txt = preg_replace('/\s+/', ' ', $txt);
    return $txt;
};

$paintBlueHeaders = function ($sheet, $isSuelo) use ($normalizeStyleText) {
    $exactTargets = [
        'VALLE',
        'SOLICITANTE',
        'NIVEL DE AGUA',
        'USO',
        'SERVICIO',
        'INTERNO',
        'EXTERNO',
        'PARAMETRO A EVALUAR',
        'METODO UTILIZADO',
        'NORMATIVA DE PRUEBA',
        'UNIDAD DE MEDIDA',
        'PARAMETRO',
        'RESULTADOS'
    ];

    $prefixTargets = [
        'FINALIDAD DEL ANALISIS',
        'FECHA DE TOMA DE MUESTRA',
        'FECHA EMISION DE RESULTADOS',
        'CLASIFICACION',
        '1. PARAMETROS FISICOS',
        '2. PARAMETROS QUIMICOS',
        '3. PARAMETROS MICROBIOLOGICOS'
    ];

    $categoryPrefixes = [
        '1. PARAMETROS FISICOS',
        '2. PARAMETROS QUIMICOS',
        '3. PARAMETROS MICROBIOLOGICOS'
    ];

    $paintRange = function ($range) use ($sheet) {
        $style = $sheet->getStyle($range);
        $style->getFill()->setFillType(Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setARGB('FF2F5597');
        $style->getFont()->setBold(true);
        $style->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $style->getBorders()->getAllBorders()->getColor()->setARGB(Color::COLOR_WHITE);
    };

    $mergeMeta = [];
    foreach ($sheet->getMergeCells() as $range) {
        $parts = Coordinate::rangeBoundaries($range);
        $mergeMeta[] = [
            'range' => $range,
            'startCol' => intval($parts[0][0]),
            'startRow' => intval($parts[0][1]),
            'endCol' => intval($parts[1][0]),
            'endRow' => intval($parts[1][1])
        ];
    }

    $usedRanges = [];
    $maxCol = Coordinate::columnIndexFromString('K');
    $maxRow = min(120, $sheet->getHighestRow());
    $categoryStartCol = 'B';
    $categoryEndCol = $isSuelo ? 'K' : 'I';

    for ($r = 1; $r <= $maxRow; $r++) {
        for ($c = 1; $c <= $maxCol; $c++) {
            $col = Coordinate::stringFromColumnIndex($c);
            $coord = $col . $r;
            $txtNorm = $normalizeStyleText($sheet->getCell($coord)->getValue());
            if ($txtNorm === '') {
                continue;
            }

            $isCategory = false;
            foreach ($categoryPrefixes as $prefix) {
                if (strpos($txtNorm, $prefix) === 0) {
                    $isCategory = true;
                    break;
                }
            }

            if ($isCategory) {
                $applyRange = $categoryStartCol . $r . ':' . $categoryEndCol . $r;
                if (!isset($usedRanges[$applyRange])) {
                    $usedRanges[$applyRange] = true;
                    $paintRange($applyRange);
                }
                continue;
            }

            $matched = in_array($txtNorm, $exactTargets, true);
            if (!$matched) {
                foreach ($prefixTargets as $prefix) {
                    if (strpos($txtNorm, $prefix) === 0) {
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                continue;
            }

            $applyRange = $coord;
            foreach ($mergeMeta as $m) {
                if ($c >= $m['startCol'] && $c <= $m['endCol'] && $r >= $m['startRow'] && $r <= $m['endRow']) {
                    $applyRange = $m['range'];
                    break;
                }
            }

            if (isset($usedRanges[$applyRange])) {
                continue;
            }
            $usedRanges[$applyRange] = true;
            $paintRange($applyRange);
        }
    }

    if (!$isSuelo) {
        // Agua: asegurar bloque de normativa totalmente azul en cabecera.
        for ($r = 1; $r <= $maxRow; $r++) {
            $txtNorm = $normalizeStyleText($sheet->getCell('E' . $r)->getValue());
            if (strpos($txtNorm, 'NORMATIVA DE PRUEBA') !== false) {
                $paintRange('E' . $r . ':H' . $r);
                if (($r + 1) <= $maxRow) {
                    $paintRange('E' . ($r + 1) . ':H' . ($r + 1));
                }
                if (($r + 2) <= $maxRow) {
                    $paintRange('E' . ($r + 2) . ':H' . ($r + 2));
                }
                break;
            }
        }
    }
};

if ($esSuelo) {
    // ===== REPORTE SUELO =====
    $sheet->setCellValue('D12', strtoupper($agricultor));
    $sheet->setCellValue('D20', $fechaToma);
    $sheet->setCellValue('J20', $fechaEmision);
    $desfaseFilasSuelo = 0;

    $textoValle = $normalizar($valle);
    $isChao = strpos($textoValle, 'CHAO') !== false;
    $isViru = strpos($textoValle, 'VIRU') !== false;
    $isMoche = strpos($textoValle, 'MOCHE') !== false;
    $isChicama = strpos($textoValle, 'CHICAMA') !== false;
    $isOtroValle = !$isChao && !$isViru && !$isMoche && !$isChicama && $textoValle !== '-' && $textoValle !== '';

    $setCheckMark('E10', $isChao);
    $setCheckMark('G10', $isViru);
    $setCheckMark('I10', $isMoche);
    $setCheckMark('K10', $isChicama);
    $sheet->setCellValue('E11', ($isOtroValle ? strtoupper(trim((string)$valle)) : ''));

    $textoProf = $normalizar($profundidadSuelo);
    $setCheckMark('F14', strpos($textoProf, '30') !== false);
    $setCheckMark('I14', strpos($textoProf, '60') !== false);
    $setCheckMark('K14', strpos($textoProf, '90') !== false);

    $sheet->setCellValue('D16', ($numeroSubmuestras !== '' ? $numeroSubmuestras : '-'));

    $textoCultivo = $normalizar($fuenteRiego);
    if (strpos($textoCultivo, 'ANTERIOR') !== false) {
        $setCheckMark('F18', true);
    }
    if (strpos($textoCultivo, 'IMPLEMENTADO') !== false && strpos($textoCultivo, 'POR') === false) {
        $setCheckMark('I18', true);
    }
    if (strpos($textoCultivo, 'POR IMPLEMENTAR') !== false) {
        $setCheckMark('K18', true);
    }

    $setCheckMark('E22', $esInterno);
    $setCheckMark('I22', $esExterno);

    // Detección dinámica de filas en plantilla de suelo
    $toAsciiUpper = function ($text) {
        $text = strtoupper(trim((string)$text));
        return strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'Ñ' => 'N'
        ]);
    };

    $findRowByText = function ($needle) use ($sheet, $toAsciiUpper) {
        $needleNorm = $toAsciiUpper($needle);
        $maxRow = max(120, $sheet->getHighestRow());
        for ($r = 1; $r <= $maxRow; $r++) {
            $txt = trim((string)$sheet->getCell('A' . $r)->getValue());
            if ($txt === '') {
                continue;
            }
            if (strpos($toAsciiUpper($txt), $needleNorm) !== false) {
                return $r;
            }
        }
        return null;
    };

    $filaHeaderFis = $findRowByText('1. PARAMETROS FISICOS');
    if ($filaHeaderFis === null) {
        $filaHeaderFis = $findRowByText('PARAMETROS FISICOS');
    }

    $filaHeaderQuim = $findRowByText('2. PARAMETROS QUIMICOS');
    if ($filaHeaderQuim === null) {
        $filaHeaderQuim = $findRowByText('PARAMETROS QUIMICOS');
    }

    $filaObs = $findRowByText('OBSERVACIONES');

    // Fallback seguro si la plantilla cambia demasiado
    if ($filaHeaderFis === null) { $filaHeaderFis = 28; }
    if ($filaHeaderQuim === null) { $filaHeaderQuim = 36; }
    if ($filaObs === null) { $filaObs = 42; }

    $normalizeCellText = function ($value) use ($toAsciiUpper) {
        $txt = $toAsciiUpper((string)$value);
        return preg_replace('/\s+/', ' ', trim($txt));
    };

    $findColumnByHeaderText = function (array $needles, $fromRow, $toRow) use ($sheet, $normalizeCellText) {
        $fromRow = max(1, (int)$fromRow);
        $toRow = max($fromRow, (int)$toRow);
        $maxCol = Coordinate::columnIndexFromString('K');

        $needlesNorm = array_map(function ($n) use ($normalizeCellText) {
            return $normalizeCellText($n);
        }, $needles);

        for ($r = $fromRow; $r <= $toRow; $r++) {
            for ($c = 1; $c <= $maxCol; $c++) {
                $col = Coordinate::stringFromColumnIndex($c);
                $txt = trim((string)$sheet->getCell($col . $r)->getValue());
                if ($txt === '') {
                    continue;
                }
                $txtNorm = $normalizeCellText($txt);
                foreach ($needlesNorm as $n) {
                    if ($n !== '' && strpos($txtNorm, $n) !== false) {
                        return $col;
                    }
                }
            }
        }
        return null;
    };

    $headerTableRow = null;
    for ($r = max(1, $filaHeaderFis - 8); $r <= max(1, $filaHeaderFis - 1); $r++) {
        $rowHasParametro = false;
        for ($c = 1; $c <= Coordinate::columnIndexFromString('K'); $c++) {
            $col = Coordinate::stringFromColumnIndex($c);
            $txtNorm = $normalizeCellText($sheet->getCell($col . $r)->getValue());
            if ($txtNorm !== '' && strpos($txtNorm, 'PARAMETRO A EVALUAR') !== false) {
                $rowHasParametro = true;
                break;
            }
        }
        if ($rowHasParametro) {
            $headerTableRow = $r;
        }
    }

    $findColumnInRowByHeader = function ($row, array $needles) use ($sheet, $normalizeCellText) {
        if (!$row || $row < 1) {
            return null;
        }
        $needlesNorm = array_map(function ($n) use ($normalizeCellText) {
            return $normalizeCellText($n);
        }, $needles);

        for ($c = 1; $c <= Coordinate::columnIndexFromString('K'); $c++) {
            $col = Coordinate::stringFromColumnIndex($c);
            $txtNorm = $normalizeCellText($sheet->getCell($col . $row)->getValue());
            if ($txtNorm === '') {
                continue;
            }
            foreach ($needlesNorm as $n) {
                if ($n !== '' && strpos($txtNorm, $n) !== false) {
                    return $col;
                }
            }
        }
        return null;
    };

    $headerScanFrom = max(1, $filaHeaderFis - 3);
    $headerScanTo = max(1, $filaHeaderFis - 1);

    $colParam = $findColumnInRowByHeader($headerTableRow, ['PARAMETRO A EVALUAR', 'PARAMETRO'])
        ?: $findColumnByHeaderText(['PARAMETRO A EVALUAR', 'PARAMETRO'], $headerScanFrom, $headerScanTo)
        ?: 'B';

    $colMetodo = $findColumnInRowByHeader($headerTableRow, ['METODO UTILIZADO', 'METODO'])
        ?: $findColumnByHeaderText(['METODO UTILIZADO', 'METODO'], $headerScanFrom, $headerScanTo)
        ?: 'D';

    $colUnidad = $findColumnInRowByHeader($headerTableRow, ['UNIDAD DE MEDIDA', 'UNIDAD'])
        ?: $findColumnByHeaderText(['UNIDAD DE MEDIDA', 'UNIDAD'], $headerScanFrom, $headerScanTo)
        ?: 'F';

    $colResultado = $findColumnInRowByHeader($headerTableRow, ['RESULTADOS', 'RESULTADO'])
        ?: $findColumnByHeaderText(['RESULTADOS', 'RESULTADO'], $headerScanFrom, $headerScanTo)
        ?: 'H';

    $colClasificacion = $findColumnInRowByHeader($headerTableRow, ['CLASIFICACION'])
        ?: $findColumnByHeaderText(['CLASIFICACION'], $headerScanFrom, $headerScanTo)
        ?: 'J';

    $startFis = $filaHeaderFis + 1;
    $capFis = max(0, $filaHeaderQuim - $startFis);
    $fisCount = count($grupos['fisicos']);

    if ($fisCount > $capFis) {
        $extraFis = $fisCount - $capFis;
        $sheet->insertNewRowBefore($filaHeaderQuim, $extraFis);
        // Copiar estilo de la ultima fila de fisicos original
        $templateRow = $filaHeaderQuim - 1;
        for ($k = 0; $k < $extraFis; $k++) {
            $newRow = $templateRow + 1 + $k;
            $sheet->duplicateStyle(
                $sheet->getStyle('A' . $templateRow . ':K' . $templateRow),
                'A' . $newRow . ':K' . $newRow
            );
        }
        $filaHeaderQuim += $extraFis;
        $filaObs += $extraFis;
        $desfaseFilasSuelo += $extraFis;
        $capFis = $fisCount;
    }

    for ($i = 0; $i < $fisCount; $i++) {
        $r = $startFis + $i;
        $item = $grupos['fisicos'][$i];
        $sheet->setCellValue($colParam . $r, $item['parametro']);
        $sheet->setCellValue($colMetodo . $r, ($item['metodo'] !== '' ? $item['metodo'] : '-'));
        $sheet->setCellValue($colUnidad . $r, trim((string)($item['normativas'][0]['unidad'] ?? '')));
        $sheet->setCellValue($colResultado . $r, ($item['resultado'] !== '' ? $item['resultado'] : '-'));
        $sheet->setCellValue($colClasificacion . $r, '');
    }

    $startQuim = $filaHeaderQuim + 1;
    $capQuim = max(0, $filaObs - $startQuim);
    $quimCount = count($grupos['quimicos']);

    if ($quimCount > $capQuim) {
        $extraQuim = $quimCount - $capQuim;
        $sheet->insertNewRowBefore($filaObs, $extraQuim);
        $templateRow = $filaObs - 1;
        for ($k = 0; $k < $extraQuim; $k++) {
            $newRow = $templateRow + 1 + $k;
            $sheet->duplicateStyle(
                $sheet->getStyle('A' . $templateRow . ':K' . $templateRow),
                'A' . $newRow . ':K' . $newRow
            );
        }
        $filaObs += $extraQuim;
        $desfaseFilasSuelo += $extraQuim;
        $capQuim = $quimCount;
    }

    for ($i = 0; $i < $quimCount; $i++) {
        $r = $startQuim + $i;
        $item = $grupos['quimicos'][$i];
        $sheet->setCellValue($colParam . $r, $item['parametro']);
        $sheet->setCellValue($colMetodo . $r, ($item['metodo'] !== '' ? $item['metodo'] : '-'));
        $sheet->setCellValue($colUnidad . $r, trim((string)($item['normativas'][0]['unidad'] ?? '')));
        $sheet->setCellValue($colResultado . $r, ($item['resultado'] !== '' ? $item['resultado'] : '-'));
        $sheet->setCellValue($colClasificacion . $r, '');
    }

    $sheet->setCellValue('B' . ($filaObs + 1), ($observacionFormateada !== '' ? $observacionFormateada : '-'));
    $sheet->setCellValue('J' . (65 + $desfaseFilasSuelo), $fechaFirmaMesAnio);
} else {
    // ===== REPORTE AGUA =====
    $normativasLayout = $normativas;
    if (count($normativasLayout) < 2) {
        $normativasLayout[] = ['id' => 0, 'descripcion' => '-', 'nombre' => '-'];
    }

    $sheet->setCellValue('D12', strtoupper($agricultor));
    $sheet->setCellValue('D18', 'ANALISIS DE CALIDAD DE AGUA');
    $sheet->setCellValue('D20', $fechaToma);
    $sheet->setCellValue('J20', $fechaEmision);
    $sheet->setCellValue('J79', $fechaFirmaMesAnio);

    $textoValle = $normalizar($valle);
    $isChao = strpos($textoValle, 'CHAO') !== false;
    $isViru = strpos($textoValle, 'VIRU') !== false;
    $isMoche = strpos($textoValle, 'MOCHE') !== false;
    $isChicama = strpos($textoValle, 'CHICAMA') !== false;
    $isOtroValle = !$isChao && !$isViru && !$isMoche && !$isChicama && $textoValle !== '-' && $textoValle !== '';

    $setCheckMark('E10', $isChao);
    $setCheckMark('G10', $isViru);
    $setCheckMark('I10', $isMoche);
    $setCheckMark('K10', $isChicama);
    $sheet->setCellValue('E11', ($isOtroValle ? strtoupper(trim((string)$valle)) : ''));

    $textoNivelAgua = $normalizar($nivelAgua);
    if ($textoNivelAgua === '-' || $textoNivelAgua === '') {
        $textoNivelAgua = $normalizar($fuente);
    }
    $setCheckMark('F14', strpos($textoNivelAgua, 'SUBTERR') !== false);
    $setCheckMark('I14', strpos($textoNivelAgua, 'SUPERF') !== false);
    $setCheckMark('K14', (strpos($textoNivelAgua, 'OTRO') !== false || $textoNivelAgua === '-'));

    $textoUso = $normalizar($uso);
    $setCheckMark('F16', strpos($textoUso, 'CONSUMO') !== false);
    $setCheckMark('I16', strpos($textoUso, 'RIEGO') !== false);
    $setCheckMark('K16', strpos($textoUso, 'ANIMAL') !== false);

    $setCheckMark('E22', $esInterno);
    $setCheckMark('I22', $esExterno);

    $sheet->setCellValue('E24', strtoupper(trim((string)($normativasLayout[0]['descripcion'] ?? '-'))));
    $sheet->setCellValue('E25', strtoupper(trim((string)($normativasLayout[0]['nombre'] ?? '-'))));
    $sheet->setCellValue('G24', strtoupper(trim((string)($normativasLayout[1]['descripcion'] ?? '-'))));
    $sheet->setCellValue('G25', strtoupper(trim((string)($normativasLayout[1]['nombre'] ?? '-'))));

    // Detección dinámica de filas en plantilla de agua
    $toAsciiUpper = function ($text) {
        $text = strtoupper(trim((string)$text));
        return strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'Ñ' => 'N'
        ]);
    };

    $findRowByText = function ($needle) use ($sheet, $toAsciiUpper) {
        $needleNorm = $toAsciiUpper($needle);
        $maxRow = max(140, $sheet->getHighestRow());
        for ($r = 1; $r <= $maxRow; $r++) {
            $txt = trim((string)$sheet->getCell('A' . $r)->getValue());
            if ($txt === '') {
                continue;
            }
            if (strpos($toAsciiUpper($txt), $needleNorm) !== false) {
                return $r;
            }
        }
        return null;
    };

    $filaHeaderFis = $findRowByText('1. PARAMETROS FISICOS');
    if ($filaHeaderFis === null) {
        $filaHeaderFis = $findRowByText('PARAMETROS FISICOS');
    }

    $filaHeaderQuim = $findRowByText('2. PARAMETROS QUIMICOS');
    if ($filaHeaderQuim === null) {
        $filaHeaderQuim = $findRowByText('PARAMETROS QUIMICOS');
    }

    $filaHeaderMicro = $findRowByText('3. PARAMETROS MICROBIOLOGICOS');
    if ($filaHeaderMicro === null) {
        $filaHeaderMicro = $findRowByText('PARAMETROS MICROBIOLOGICOS');
    }

    $filaObs = $findRowByText('OBSERVACIONES');

    // Fallbacks base para plantilla actual
    if ($filaHeaderFis === null) { $filaHeaderFis = 29; }
    if ($filaHeaderQuim === null) { $filaHeaderQuim = 37; }
    if ($filaHeaderMicro === null) { $filaHeaderMicro = 50; }
    if ($filaObs === null) { $filaObs = 55; }

    $startFis = $filaHeaderFis + 1;
    $capFis = max(0, $filaHeaderQuim - $startFis);
    $fisCount = count($grupos['fisicos']);

    if ($fisCount > $capFis) {
        $extraFis = $fisCount - $capFis;
        $sheet->insertNewRowBefore($filaHeaderQuim, $extraFis);
        $templateRow = $filaHeaderQuim - 1;
        for ($k = 0; $k < $extraFis; $k++) {
            $newRow = $templateRow + 1 + $k;
            $sheet->duplicateStyle(
                $sheet->getStyle('A' . $templateRow . ':K' . $templateRow),
                'A' . $newRow . ':K' . $newRow
            );
        }
        $filaHeaderQuim += $extraFis;
        $filaHeaderMicro += $extraFis;
        $filaObs += $extraFis;
        $capFis = $fisCount;
    }

    for ($i = 0; $i < $fisCount; $i++) {
        $r = $startFis + $i;
        $item = $grupos['fisicos'][$i];
        $sheet->setCellValue('B' . $r, $item['parametro']);
        $sheet->setCellValue('D' . $r, ($item['metodo'] !== '' ? $item['metodo'] : '-'));
        $sheet->setCellValue('E' . $r, $item['normativas'][0]['unidad'] ?? '-');
        $sheet->setCellValue('F' . $r, $item['normativas'][0]['limite'] ?? '-');
        $sheet->setCellValue('G' . $r, $item['normativas'][1]['unidad'] ?? '-');
        $sheet->setCellValue('H' . $r, $item['normativas'][1]['limite'] ?? '-');
        $sheet->setCellValue('I' . $r, ($item['resultado'] !== '' ? $item['resultado'] : '-'));
    }

    $startQuim = $filaHeaderQuim + 1;
    $capQuim = max(0, $filaHeaderMicro - $startQuim);
    $quimCount = count($grupos['quimicos']);

    if ($quimCount > $capQuim) {
        $extraQuim = $quimCount - $capQuim;
        $sheet->insertNewRowBefore($filaHeaderMicro, $extraQuim);
        $templateRow = $filaHeaderMicro - 1;
        for ($k = 0; $k < $extraQuim; $k++) {
            $newRow = $templateRow + 1 + $k;
            $sheet->duplicateStyle(
                $sheet->getStyle('A' . $templateRow . ':K' . $templateRow),
                'A' . $newRow . ':K' . $newRow
            );
        }
        $filaHeaderMicro += $extraQuim;
        $filaObs += $extraQuim;
        $capQuim = $quimCount;
    }

    for ($i = 0; $i < $quimCount; $i++) {
        $r = $startQuim + $i;
        $item = $grupos['quimicos'][$i];
        $sheet->setCellValue('B' . $r, $item['parametro']);
        $sheet->setCellValue('D' . $r, ($item['metodo'] !== '' ? $item['metodo'] : '-'));
        $sheet->setCellValue('E' . $r, $item['normativas'][0]['unidad'] ?? '-');
        $sheet->setCellValue('F' . $r, $item['normativas'][0]['limite'] ?? '-');
        $sheet->setCellValue('G' . $r, $item['normativas'][1]['unidad'] ?? '-');
        $sheet->setCellValue('H' . $r, $item['normativas'][1]['limite'] ?? '-');
        $sheet->setCellValue('I' . $r, ($item['resultado'] !== '' ? $item['resultado'] : '-'));
    }

    $startMicro = $filaHeaderMicro + 1;
    $capMicro = max(0, $filaObs - $startMicro);
    $microCount = count($grupos['microbiologicos']);

    if ($microCount > $capMicro) {
        $extraMicro = $microCount - $capMicro;
        $sheet->insertNewRowBefore($filaObs, $extraMicro);
        $templateRow = $filaObs - 1;
        for ($k = 0; $k < $extraMicro; $k++) {
            $newRow = $templateRow + 1 + $k;
            $sheet->duplicateStyle(
                $sheet->getStyle('A' . $templateRow . ':K' . $templateRow),
                'A' . $newRow . ':K' . $newRow
            );
        }
        $filaObs += $extraMicro;
        $capMicro = $microCount;
    }

    for ($i = 0; $i < $microCount; $i++) {
        $r = $startMicro + $i;
        $item = $grupos['microbiologicos'][$i];
        $sheet->setCellValue('B' . $r, $item['parametro']);
        $sheet->setCellValue('D' . $r, ($item['metodo'] !== '' ? $item['metodo'] : '-'));
        $sheet->setCellValue('E' . $r, $item['normativas'][0]['unidad'] ?? '-');
        $sheet->setCellValue('F' . $r, $item['normativas'][0]['limite'] ?? '-');
        $sheet->setCellValue('G' . $r, $item['normativas'][1]['unidad'] ?? '-');
        $sheet->setCellValue('H' . $r, $item['normativas'][1]['limite'] ?? '-');
        $sheet->setCellValue('I' . $r, ($item['resultado'] !== '' ? $item['resultado'] : '-'));
    }

    $sheet->setCellValue('B' . ($filaObs + 1), ($observacionFormateada !== '' ? $observacionFormateada : '-'));
}

// Fallback visual: reponer encabezados azules y bordes blancos, sin tocar el pie final.
$paintBlueHeaders($sheet, $esSuelo);

$nombreArchivo = ($esSuelo ? 'Analisis_Suelo_Muestra_' : 'Analisis_Agua_Muestra_') . $id_muestra . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

if ($previewHtml) {
    $previewSpreadsheet = clone $spreadsheet;
    $previewSheet = $previewSpreadsheet->getSheet($previewSpreadsheet->getIndex($sheet));

    foreach (range('A', 'K') as $col) {
        $previewSheet->getColumnDimension($col)->setWidth(22);
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $htmlWriter = new HtmlWriter($previewSpreadsheet);
    $htmlWriter->setSheetIndex($previewSpreadsheet->getIndex($previewSheet));
    if (method_exists($htmlWriter, 'setUseInlineCss')) {
        $htmlWriter->setUseInlineCss(true);
    }
    if (method_exists($htmlWriter, 'setEmbedImages')) {
        $htmlWriter->setEmbedImages(true);
    }

    ob_start();
    $htmlWriter->save('php://output');
    $html = ob_get_clean();

    $previewCss = '<style>'
        . 'html,body{margin:0;padding:10px;background:#f3f4f6 !important;}'
        . 'table{margin:0 auto !important;background:#ffffff !important;}'
        . 'td,th{min-width:22px !important;}'
        . '</style>';

    if (stripos($html, '</head>') !== false) {
        $html = str_ireplace('</head>', $previewCss . '</head>', $html);
    } else {
        $html = $previewCss . $html;
    }

    echo $html;
    exit;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: max-age=0');

$tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_muestra_');
if ($tmpFile === false) {
    http_response_code(500);
    die('No se pudo crear archivo temporal');
}

$writer = new Xlsx($spreadsheet);
$writer->setPreCalculateFormulas(false);
$writer->save($tmpFile);

// Restaurar solo drawings/media de la plantilla para conservar bloques de color
// definidos como formas sin alterar estilos de celdas generadas.
$srcZip = new ZipArchive();
$dstZip = new ZipArchive();
if ($srcZip->open($plantillaPath) === true && $dstZip->open($tmpFile) === true) {
    for ($i = 0; $i < $srcZip->numFiles; $i++) {
        $name = $srcZip->getNameIndex($i);
        if ($name === false) {
            continue;
        }

        $mustRestore = (
            strpos($name, 'xl/media/') === 0 ||
            strpos($name, 'xl/drawings/') === 0 ||
            $name === 'xl/worksheets/_rels/sheet1.xml.rels'
        );

        if (!$mustRestore) {
            continue;
        }

        $content = $srcZip->getFromName($name);
        if ($content === false) {
            continue;
        }

        $dstZip->deleteName($name);
        $dstZip->addFromString($name, $content);
    }
    $srcZip->close();
    $dstZip->close();
}

readfile($tmpFile);
@unlink($tmpFile);
exit;
