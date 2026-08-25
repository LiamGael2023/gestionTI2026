<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$autoloadLibs = $base_path . '/libs/vendor/autoload.php';
if (!file_exists($autoloadLibs)) {
    http_response_code(500);
    die('No se encontro la libreria de exportacion (libs/vendor/autoload.php)');
}
require_once $autoloadLibs;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$id_proyecto = intval($_GET['id_proyecto'] ?? 0);
if ($id_proyecto <= 0) {
    http_response_code(400);
    die('Proyecto invalido');
}

$conn = Conexion::conectar();
require_once $base_path . '/modules/laboratorio/models/LaboratorioModel.php';
$labAuthExp = new LaboratorioModel($conn);
$labAuthExp->denegarSiSinPermiso($_SESSION['usuario_id'], '?module=laboratorio&action=muestra', 'exportar');
if (!$conn) {
    http_response_code(500);
    die('Error de conexion');
}

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

$sqlMuestras = "SELECT m.Id_Muestra,
                       ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden,
                       m.Eje_X,
                       m.Eje_Y
                FROM laboratorio.Muestra_Lab m
                WHERE m.Id_Proyecto = ? AND m.Activo = 1
                ORDER BY m.Id_Muestra";
$stmtMuestras = sqlsrv_query($conn, $sqlMuestras, [$id_proyecto]);
if ($stmtMuestras === false) {
    http_response_code(500);
    die('Error al obtener muestras: ' . print_r(sqlsrv_errors(), true));
}

$muestras = [];
$ids_muestras = [];
while ($row = sqlsrv_fetch_array($stmtMuestras, SQLSRV_FETCH_ASSOC)) {
    $idMuestra = intval($row['Id_Muestra'] ?? 0);
    if ($idMuestra <= 0) {
        continue;
    }
    $muestras[] = [
        'id_muestra' => $idMuestra,
        'numero' => intval($row['NumeroOrden'] ?? 0),
        'eje_x' => $row['Eje_X'] ?? null,
        'eje_y' => $row['Eje_Y'] ?? null,
    ];
    $ids_muestras[] = $idMuestra;
}

if (empty($muestras)) {
    http_response_code(409);
    die('El proyecto no tiene muestras para exportar');
}

$sqlParametros = "SELECT DISTINCT pa.Id_Parametro, pa.Nombre, ISNULL(um.Abreviatura, pa.Unidad_Medida) AS Unidad_Medida, pa.Categoria, CASE pa.Categoria WHEN 'Fisico' THEN 1 WHEN 'Quimico' THEN 2 WHEN 'Microbiologico' THEN 3 ELSE 4 END AS OrderCat
                  FROM laboratorio.Parametro_Analisis pa
                  LEFT JOIN laboratorio.Unidad_Medida um ON pa.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                  INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Servicio = pa.Id_Servicio
                  INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                  WHERE ml.Id_Proyecto = ?
                    AND ml.Activo = 1
                    AND sa.Activo = 1
                    AND pa.Activo = 1
                  ORDER BY OrderCat, pa.Nombre";
$stmtParametros = sqlsrv_query($conn, $sqlParametros, [$id_proyecto]);
if ($stmtParametros === false) {
    http_response_code(500);
    die('Error al obtener parametros: ' . print_r(sqlsrv_errors(), true));
}

$parametros = [];
while ($row = sqlsrv_fetch_array($stmtParametros, SQLSRV_FETCH_ASSOC)) {
    $idParametro = intval($row['Id_Parametro'] ?? 0);
    if ($idParametro <= 0) {
        continue;
    }
    $parametros[] = [
        'id'        => $idParametro,
        'nombre'    => trim((string)($row['Nombre'] ?? ('Parametro #' . $idParametro))),
        'unidad'    => trim((string)($row['Unidad_Medida'] ?? '-')),
        'categoria' => trim((string)($row['Categoria'] ?? '')),
    ];
}

if (empty($parametros)) {
    http_response_code(409);
    die('El proyecto no tiene parametros asociados para exportar');
}

// Ordenar parámetros: Físicos → Químicos → Microbiológicos → Otros
$_catPrioridad = function (string $cat): int {
    $c = strtoupper(str_replace(
        ['Á','É','Í','Ó','Ú','á','é','í','ó','ú','ñ','Ñ'],
        ['A','E','I','O','U','a','e','i','o','u','n','N'],
        $cat
    ));
    if (strpos($c, 'FISIC')       !== false) return 1;
    if (strpos($c, 'QUIMIC')      !== false) return 2;
    if (strpos($c, 'MICROBIOLOG') !== false) return 3;
    return 9;
};
usort($parametros, function ($a, $b) use ($_catPrioridad) {
    $pa = $_catPrioridad((string)($a['categoria'] ?? ''));
    $pb = $_catPrioridad((string)($b['categoria'] ?? ''));
    if ($pa !== $pb) return $pa <=> $pb;
    return strcasecmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
});

$formatNumero = function ($valor) {
    if ($valor === null) {
        return '';
    }
    if ($valor instanceof DateTime) {
        return $valor->format('d-m-Y');
    }
    $txt = trim((string)$valor);
    if ($txt === '') {
        return '';
    }
    if (!is_numeric($txt)) {
        return $txt;
    }
    if (strpos($txt, '.') !== false) {
        $txt = rtrim(rtrim($txt, '0'), '.');
    }
    return $txt;
};

$normalizarTexto = function ($txt) {
    $txt = trim((string)$txt);
    if ($txt === '') {
        return '';
    }
    $txt = str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú', 'Ñ', 'ñ'],
        ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u', 'N', 'n'],
        $txt
    );
    $txt = preg_replace('/\s+/', ' ', $txt);
    return strtoupper($txt);
};

$parseNumero = function ($valor) {
    if ($valor === null) {
        return null;
    }
    $txt = trim((string)$valor);
    if ($txt === '' || $txt === '-') {
        return null;
    }
    $txt = str_replace(',', '.', $txt);
    $txt = preg_replace('/[^0-9.\-]/', '', $txt);
    if ($txt === '' || $txt === '-' || !is_numeric($txt)) {
        return null;
    }
    return floatval($txt);
};

$categoriasInput = $_GET['categorias'] ?? [];
if (!is_array($categoriasInput)) {
    $categoriasInput = [$categoriasInput];
}

$categoriasSeleccionadas = [];
$categoriasSeleccionadasNorm = [];
foreach ($categoriasInput as $cat) {
    $desc = trim((string)$cat);
    if ($desc === '') {
        continue;
    }
    $descNorm = $normalizarTexto($desc);
    if ($descNorm === '' || isset($categoriasSeleccionadasNorm[$descNorm])) {
        continue;
    }
    $categoriasSeleccionadas[] = $desc;
    $categoriasSeleccionadasNorm[$descNorm] = true;
}

$ordenCategoriasSeleccionadas = [];
foreach ($categoriasSeleccionadas as $idx => $cat) {
    $ordenCategoriasSeleccionadas[$normalizarTexto($cat)] = $idx;
}

$prioridadDescripcionLimite = function ($descripcion) use ($normalizarTexto) {
    $txt = $normalizarTexto($descripcion);
    if (strpos($txt, 'RIEGO') !== false) {
        return 1;
    }
    if (strpos($txt, 'ANIMAL') !== false) {
        return 2;
    }
    if (strpos($txt, 'HUMAN') !== false) {
        return 3;
    }
    return 9;
};

$formatLimite = function ($limite) use ($formatNumero) {
    if (!$limite) {
        return '-';
    }
    $min = $formatNumero($limite['min'] ?? null);
    $max = $formatNumero($limite['max'] ?? null);
    $hasMin = ($min !== '');
    $hasMax = ($max !== '');

    if ($hasMin && $hasMax) {
        return $min . ' - ' . $max;
    }
    if ($hasMax) {
        return $max;
    }
    if ($hasMin) {
        return $min;
    }
    return '-';
};

$paramIdsForLimites = array_map(function ($p) {
    return intval($p['id']);
}, $parametros);

$limitesPorParametro = [];
$filasNormativasMap = [];

if (!empty($paramIdsForLimites)) {
    $placeholders = implode(',', array_fill(0, count($paramIdsForLimites), '?'));
    $sqlLimites = "SELECT l.Id_Parametro,
                          l.Id_Normativa,
                          l.Valor_Min,
                          l.Valor_Max,
                          l.Unidad_Medida,
                          l.Descripcion AS DescripcionLimite,
                          n.Nombre AS NormativaNombre,
                          n.Descripcion AS DescripcionNormativa
                   FROM laboratorio.Limite_Legal l
                   INNER JOIN laboratorio.Normativa_Legal n ON n.Id_Normativa = l.Id_Normativa
                   WHERE l.Activo = 1
                     AND n.Activo = 1
                     AND l.Id_Parametro IN ($placeholders)";
    $stmtLimites = sqlsrv_query($conn, $sqlLimites, $paramIdsForLimites);
    if ($stmtLimites === false) {
        http_response_code(500);
        die('Error al obtener limites legales: ' . print_r(sqlsrv_errors(), true));
    }

    while ($lim = sqlsrv_fetch_array($stmtLimites, SQLSRV_FETCH_ASSOC)) {
        $idParametro = intval($lim['Id_Parametro'] ?? 0);
        $idNormativa = intval($lim['Id_Normativa'] ?? 0);
        if ($idParametro <= 0 || $idNormativa <= 0) {
            continue;
        }

        $normativa = trim((string)($lim['NormativaNombre'] ?? 'NORMATIVA'));
        if ($normativa === '') {
            $normativa = 'NORMATIVA';
        }

        $descripcionLimite = trim((string)($lim['DescripcionLimite'] ?? ''));
        $descripcionNormativa = trim((string)($lim['DescripcionNormativa'] ?? ''));
        $descripcionFila = ($descripcionLimite !== '') ? $descripcionLimite : (($descripcionNormativa !== '') ? $descripcionNormativa : 'NORMATIVA');
        $descripcionFilaNorm = $normalizarTexto($descripcionFila);

        if (!empty($categoriasSeleccionadasNorm) && !isset($categoriasSeleccionadasNorm[$descripcionFilaNorm])) {
            continue;
        }

        $rowKey = $idNormativa . '|' . $descripcionFilaNorm;
        if (!isset($filasNormativasMap[$rowKey])) {
            $filasNormativasMap[$rowKey] = [
                'key' => $rowKey,
                'id_normativa' => $idNormativa,
                'normativa' => $normativa,
                'descripcion' => $descripcionFila,
                'descripcion_norm' => $descripcionFilaNorm,
                'prioridad' => $prioridadDescripcionLimite($descripcionFila),
            ];
        }

        if (!isset($limitesPorParametro[$idParametro])) {
            $limitesPorParametro[$idParametro] = [];
        }

        $limitesPorParametro[$idParametro][$rowKey] = [
            'min' => $lim['Valor_Min'] ?? null,
            'max' => $lim['Valor_Max'] ?? null,
            'unidad' => trim((string)($lim['Unidad_Medida'] ?? '')),
        ];
    }
}

$filasNormativas = array_values($filasNormativasMap);
usort($filasNormativas, function ($a, $b) use ($ordenCategoriasSeleccionadas) {
    $descANorm = (string)($a['descripcion_norm'] ?? '');
    $descBNorm = (string)($b['descripcion_norm'] ?? '');

    $ordA = $ordenCategoriasSeleccionadas[$descANorm] ?? null;
    $ordB = $ordenCategoriasSeleccionadas[$descBNorm] ?? null;
    if ($ordA !== null && $ordB !== null && $ordA !== $ordB) {
        return $ordA <=> $ordB;
    }
    if ($ordA !== null && $ordB === null) {
        return -1;
    }
    if ($ordA === null && $ordB !== null) {
        return 1;
    }

    if (($a['prioridad'] ?? 9) !== ($b['prioridad'] ?? 9)) {
        return intval($a['prioridad'] ?? 9) <=> intval($b['prioridad'] ?? 9);
    }
    $cmpNormativa = strcasecmp((string)($a['normativa'] ?? ''), (string)($b['normativa'] ?? ''));
    if ($cmpNormativa !== 0) {
        return $cmpNormativa;
    }
    return strcasecmp((string)($a['descripcion'] ?? ''), (string)($b['descripcion'] ?? ''));
});

if (!empty($categoriasSeleccionadas)) {
    $descPresentes = [];
    foreach ($filasNormativas as $filaNorm) {
        $descPresentes[(string)($filaNorm['descripcion_norm'] ?? '')] = true;
    }

    foreach ($categoriasSeleccionadas as $catSel) {
        $catSelNorm = $normalizarTexto($catSel);
        if (isset($descPresentes[$catSelNorm])) {
            continue;
        }
        $filasNormativas[] = [
            'key' => '0|' . $catSelNorm,
            'id_normativa' => 0,
            'normativa' => '-',
            'descripcion' => $catSel,
            'descripcion_norm' => $catSelNorm,
            'prioridad' => $prioridadDescripcionLimite($catSel),
        ];
    }

    usort($filasNormativas, function ($a, $b) use ($ordenCategoriasSeleccionadas) {
        $descANorm = (string)($a['descripcion_norm'] ?? '');
        $descBNorm = (string)($b['descripcion_norm'] ?? '');

        $ordA = $ordenCategoriasSeleccionadas[$descANorm] ?? null;
        $ordB = $ordenCategoriasSeleccionadas[$descBNorm] ?? null;
        if ($ordA !== null && $ordB !== null && $ordA !== $ordB) {
            return $ordA <=> $ordB;
        }
        if ($ordA !== null && $ordB === null) {
            return -1;
        }
        if ($ordA === null && $ordB !== null) {
            return 1;
        }

        if (($a['prioridad'] ?? 9) !== ($b['prioridad'] ?? 9)) {
            return intval($a['prioridad'] ?? 9) <=> intval($b['prioridad'] ?? 9);
        }
        return strcasecmp((string)($a['descripcion'] ?? ''), (string)($b['descripcion'] ?? ''));
    });
}

if (empty($filasNormativas)) {
    if (!empty($categoriasSeleccionadas)) {
        foreach ($categoriasSeleccionadas as $catSel) {
            $catSelNorm = $normalizarTexto($catSel);
            $filasNormativas[] = [
                'key' => '0|' . $catSelNorm,
                'id_normativa' => 0,
                'normativa' => '-',
                'descripcion' => $catSel,
                'descripcion_norm' => $catSelNorm,
                'prioridad' => $prioridadDescripcionLimite($catSel),
            ];
        }
    } else {
        $filasNormativas = [[
            'key' => '0|NORMATIVA',
            'id_normativa' => 0,
            'normativa' => 'NORMATIVA',
            'descripcion' => '-',
            'descripcion_norm' => 'NORMATIVA',
            'prioridad' => 9,
        ]];
    }
}

$filasNormativas = array_slice($filasNormativas, 0, 3);
while (count($filasNormativas) < 3) {
    $filasNormativas[] = [
        'key' => '0|FILA_' . count($filasNormativas),
        'id_normativa' => 0,
        'normativa' => '-',
        'descripcion' => '-',
        'descripcion_norm' => 'FILA',
        'prioridad' => 9,
    ];
}

$sqlResultados = "SELECT sa.Id_Muestra,
                         ra.Id_Parametro,
                         MAX(CAST(ra.Valor_Hallado AS NVARCHAR(255))) AS Valor_Hallado
                  FROM laboratorio.Resultado_Analisis ra
                  INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Solicitud_Analisis = ra.Id_Solicitud_Analisis
                  INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
                  WHERE ml.Id_Proyecto = ?
                    AND ml.Activo = 1
                    AND sa.Activo = 1
                    AND ra.Activo = 1
                  GROUP BY sa.Id_Muestra, ra.Id_Parametro";
$stmtResultados = sqlsrv_query($conn, $sqlResultados, [$id_proyecto]);
if ($stmtResultados === false) {
    http_response_code(500);
    die('Error al obtener resultados: ' . print_r(sqlsrv_errors(), true));
}

$resultados = [];
while ($row = sqlsrv_fetch_array($stmtResultados, SQLSRV_FETCH_ASSOC)) {
    $idMuestra = intval($row['Id_Muestra'] ?? 0);
    $idParametro = intval($row['Id_Parametro'] ?? 0);
    if ($idMuestra <= 0 || $idParametro <= 0) {
        continue;
    }
    if (!isset($resultados[$idMuestra])) {
        $resultados[$idMuestra] = [];
    }
    $valor = trim((string)($row['Valor_Hallado'] ?? ''));
    $resultados[$idMuestra][$idParametro] = ($valor === '' ? '-' : $valor);
}

$plantillaPath = $base_path . '/modules/laboratorio/muestra/plantilla/AGUAS SUBTERRANEAS - MONITOREO.xlsx';
if (!file_exists($plantillaPath)) {
    http_response_code(500);
    die('No se encontro la plantilla de monitoreo');
}

$spreadsheet = IOFactory::load($plantillaPath);
$sheet = $spreadsheet->getSheet(0);

$inicioColParam = 7; // G
$inicioFilaData = 11;
$totalParams = count($parametros);
$ultimaColParam = $inicioColParam + $totalParams - 1;
$ultimaColExport = max($ultimaColParam, 31); // al menos hasta AE
$colFinEstilo = 31; // AE fijo para formato visual solicitado
$colFinEstiloLetra = Coordinate::stringFromColumnIndex($colFinEstilo);
$ultimaFilaExport = max($inicioFilaData + count($muestras) + 5, 160);

for ($row = 6; $row <= $ultimaFilaExport; $row++) {
    for ($col = 2; $col <= ($ultimaColExport + 20); $col++) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, '');
    }
}

$styleHeaderParam = $sheet->getStyle('G6');
$styleHeaderUm = $sheet->getStyle('G7');
$styleNormativaFila1Fija = $sheet->getStyle('B8:F8');
$styleNormativaFila2Fija = $sheet->getStyle('B9:F9');
$styleNormativaFila3Fija = $sheet->getStyle('B10:F10');
$styleLimiteFila1 = $sheet->getStyle('G8');
$styleLimiteFila2 = $sheet->getStyle('G9');
$styleLimiteFila3 = $sheet->getStyle('G10');
$styleDataOdd = $sheet->getStyle('G11');
$styleDataEven = $sheet->getStyle('G12');
$styleDataFixedOdd = $sheet->getStyle('B11');
$styleDataFixedEven = $sheet->getStyle('B12');

$forzarColorRelleno = function ($rango, $argbColor) use ($sheet) {
    $sheet->getStyle($rango)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $sheet->getStyle($rango)->getFill()->getStartColor()->setARGB($argbColor);
    $sheet->getStyle($rango)->getFill()->getEndColor()->setARGB($argbColor);
};

$forzarFuente = function ($rango, $argbColor, $negrita) use ($sheet) {
    $sheet->getStyle($rango)->getFont()->getColor()->setARGB($argbColor);
    $sheet->getStyle($rango)->getFont()->setBold((bool)$negrita);
};

$colorVerdeCabecera  = 'FF70AD47';
$colorVerdeFilas     = 'FFE2EFDA';
$colorBlanco         = 'FFFFFFFF';
// Celeste intenso para muestras editadas (con resultados ingresados)
$colorCelesteEditado = 'FF9DC3E6'; // celeste medio-fuerte
// Celeste más oscuro para Consumo de Agua / Consumo Humano
$colorCelesteConsumo = 'FF5B9BD5'; // celeste oscuro

// Determinar muestras con análisis EXTRA (servicios fuera del plan original del proyecto)
$idsParaEdicion = array_map(function($m) { return intval($m['id_muestra']); }, $muestras);
$muestrasEditadasSet    = [];
$muestrasConsumoAguaSet = [];
if (!empty($idsParaEdicion)) {
    $phEdit = implode(',', array_fill(0, count($idsParaEdicion), '?'));

    // Extra: solicitudes cuyo servicio NO está en el plan del proyecto
    $sqlEditadas = "SELECT DISTINCT sa.Id_Muestra
                    FROM laboratorio.Solicitud_Analisis sa
                    WHERE sa.Id_Muestra IN ($phEdit)
                      AND sa.Activo = 1
                      AND sa.Id_Servicio NOT IN (
                          SELECT ps.Id_Servicio
                          FROM laboratorio.Proyecto_Detalle_Analisis pda
                          INNER JOIN laboratorio.Producto_Servicio ps
                              ON ps.Id_Producto = pda.Id_Producto_Venta AND ps.Activo = 1
                          WHERE pda.Id_Proyecto = ? AND pda.Activo = 1
                      )";
    $paramsEdit = array_merge(array_values($idsParaEdicion), [$id_proyecto]);
    $stmtEdit = sqlsrv_query($conn, $sqlEditadas, $paramsEdit);
    if ($stmtEdit !== false) {
        while ($eRow = sqlsrv_fetch_array($stmtEdit, SQLSRV_FETCH_ASSOC)) {
            $muestrasEditadasSet[intval($eRow['Id_Muestra'])] = true;
        }
    }

    $sqlCA = "SELECT Id_Muestra FROM laboratorio.Muestra_Lab
              WHERE Id_Muestra IN ($phEdit)
                AND Tipo_Servicio IN ('Consumo de Agua', 'Consumo Humano')
                AND Activo = 1";
    $stmtCA = sqlsrv_query($conn, $sqlCA, $idsParaEdicion);
    if ($stmtCA !== false) {
        while ($caRow = sqlsrv_fetch_array($stmtCA, SQLSRV_FETCH_ASSOC)) {
            $muestrasConsumoAguaSet[intval($caRow['Id_Muestra'])] = true;
        }
    }
}

$titulo = 'RESULTADOS ANALISIS DE ' . strtoupper((string)($proyecto['Nombre_Proyecto'] ?? 'PROYECTO'));
$fechaFinal = new DateTime();
$subtitulo = strtoupper((string)($proyecto['Temporada'] ?? '')) . ' - ' . strtoupper((string)($proyecto['Valle'] ?? ''));
$sheet->setCellValue('B4', $titulo . "\n" . $subtitulo);

$sheet->setCellValue('B6', 'PARAMETRO');
$sheet->setCellValue('C6', 'COORDENADA X (WGS-84)');
$sheet->setCellValue('D6', 'COORDENADA Y (WGS-84)');
$sheet->setCellValue('E6', 'N DE POZO OBSERVACION');
$sheet->setCellValue('F6', 'CODIGO IRHS');

$sheet->setCellValue('B7', 'UM');
$sheet->setCellValue('C7', '-');
$sheet->setCellValue('D7', '-');
$sheet->setCellValue('E7', '-');
$sheet->setCellValue('F7', '-');

$sheet->duplicateStyle($styleHeaderParam, 'B6:F6');
$sheet->duplicateStyle($styleHeaderUm, 'B7:F7');

$forzarColorRelleno('B4:' . $colFinEstiloLetra . '10', $colorVerdeCabecera);
$forzarFuente('B4:' . $colFinEstiloLetra . '10', 'FFFFFFFF', true);
$forzarColorRelleno('A1:A' . $ultimaFilaExport, $colorBlanco);

$sheet->getColumnDimension('B')->setWidth(13);
$sheet->getColumnDimension('C')->setWidth(16);
$sheet->getColumnDimension('D')->setWidth(16);
$sheet->getColumnDimension('E')->setWidth(24);
$sheet->getColumnDimension('F')->setWidth(20);

$paramIndex = 0;
foreach ($parametros as $param) {
    $colIndex = $inicioColParam + $paramIndex;
    $col = Coordinate::stringFromColumnIndex($colIndex);

    $nombre = trim((string)$param['nombre']);
    $unidad = trim((string)$param['unidad']);
    if ($unidad === '') {
        $unidad = '-';
    }

    $sheet->setCellValue($col . '6', $nombre);
    $sheet->setCellValue($col . '7', $unidad);

    $sheet->duplicateStyle($styleHeaderParam, $col . '6');
    $sheet->duplicateStyle($styleHeaderUm, $col . '7');

    $ancho = max(11, min(28, strlen($nombre) * 0.9));
    $sheet->getColumnDimension($col)->setWidth($ancho);

    $paramIndex++;
}

// Reforzar estilos de cabecera luego de escribir parametros dinamicos
$forzarColorRelleno('B4:' . $colFinEstiloLetra . '10', $colorVerdeCabecera);
$forzarFuente('B4:' . $colFinEstiloLetra . '10', 'FFFFFFFF', true);

$filasLimitesExcel = [8, 9, 10];
$estilosLimites = [
    8 => $styleLimiteFila1,
    9 => $styleLimiteFila2,
    10 => $styleLimiteFila3,
];

$aplicarBordeBlanco = function ($rango) use ($sheet) {
    $sheet->getStyle($rango)
        ->getBorders()
        ->getAllBorders()
        ->getColor()
        ->setARGB('FFFFFFFF');
};

$estilosNormativaFijos = [
    8 => $styleNormativaFila1Fija,
    9 => $styleNormativaFila2Fija,
    10 => $styleNormativaFila3Fija,
];

foreach ($filasLimitesExcel as $idx => $filaLimite) {
    $meta = $filasNormativas[$idx] ?? null;
    if (!$meta) {
        $meta = [
            'key' => '0|VACIO',
            'id_normativa' => 0,
            'normativa' => '',
            'descripcion' => '-',
        ];
    }

    $sheet->duplicateStyle($estilosNormativaFijos[$filaLimite], 'B' . $filaLimite . ':F' . $filaLimite);

    $normativaTexto = trim((string)($meta['normativa'] ?? ''));
    if ($normativaTexto === '') {
        $normativaTexto = '-';
    }
    $sheet->setCellValue('B' . $filaLimite, $normativaTexto);

    $sheet->setCellValue('C' . $filaLimite, '');
    $sheet->setCellValue('D' . $filaLimite, '');
    $sheet->setCellValue('E' . $filaLimite, trim((string)($meta['descripcion'] ?? '-')));
    $sheet->setCellValue('F' . $filaLimite, '');

    $paramPos = 0;
    foreach ($parametros as $param) {
        $idParametro = intval($param['id']);
        $col = Coordinate::stringFromColumnIndex($inicioColParam + $paramPos);
        $limite = $limitesPorParametro[$idParametro][$meta['key']] ?? null;
        $sheet->setCellValue($col . $filaLimite, $formatLimite($limite));
        $sheet->duplicateStyle($estilosLimites[$filaLimite], $col . $filaLimite);
        $paramPos++;
    }

    $forzarColorRelleno('B' . $filaLimite . ':' . $colFinEstiloLetra . $filaLimite, $colorVerdeCabecera);
    $forzarFuente('B' . $filaLimite . ':' . $colFinEstiloLetra . $filaLimite, 'FFFFFFFF', true);

    $aplicarBordeBlanco('B' . $filaLimite . ':' . $colFinEstiloLetra . $filaLimite);
}

$fmtValor = function ($valor, $porDefecto = '-') {
    if ($valor === null) {
        return $porDefecto;
    }
    if ($valor instanceof DateTime) {
        return $valor->format('d-m-Y');
    }
    $txt = trim((string)$valor);
    if ($txt === '') {
        return $porDefecto;
    }
    return $txt;
};

$excedeLimitesSeleccionados = function ($idParametro, $valorHallado) use ($limitesPorParametro, $parseNumero) {
    $valorNum = $parseNumero($valorHallado);
    if ($valorNum === null) {
        return false;
    }

    $limitesParametro = $limitesPorParametro[intval($idParametro)] ?? null;
    if (!$limitesParametro || !is_array($limitesParametro)) {
        return false;
    }

    foreach ($limitesParametro as $limiteInfo) {
        $minNum = $parseNumero($limiteInfo['min'] ?? null);
        $maxNum = $parseNumero($limiteInfo['max'] ?? null);

        if ($maxNum !== null && $valorNum > $maxNum) {
            return true;
        }
        if ($minNum !== null && $valorNum < $minNum) {
            return true;
        }
    }

    return false;
};

$paramIds = array_map(function ($p) { return intval($p['id']); }, $parametros);

$fila = $inicioFilaData;
foreach ($muestras as $muestra) {
    $idMuestra = intval($muestra['id_muestra']);
    $numero = intval($muestra['numero']);

    $esConsumoAgua = isset($muestrasConsumoAguaSet[$idMuestra]);
    $esEditada     = isset($muestrasEditadasSet[$idMuestra]);

    // Etiqueta de fila: M{n} o M{n} (CH) para consumo humano/agua
    $etiquetaMuestra = 'M' . $numero . ($esConsumoAgua ? ' (CH)' : '');

    $sheet->setCellValue('B' . $fila, $etiquetaMuestra);
    $sheet->setCellValue('C' . $fila, $fmtValor($muestra['eje_x']));
    $sheet->setCellValue('D' . $fila, $fmtValor($muestra['eje_y']));
    $sheet->setCellValue('E' . $fila, '');
    $sheet->setCellValue('F' . $fila, '');

    if (($fila % 2) === 0) {
        $sheet->duplicateStyle($styleDataFixedEven, 'B' . $fila . ':F' . $fila);
    } else {
        $sheet->duplicateStyle($styleDataFixedOdd, 'B' . $fila . ':F' . $fila);
    }

    $colOffset = 0;
    foreach ($paramIds as $idParametro) {
        $col = Coordinate::stringFromColumnIndex($inicioColParam + $colOffset);
        $valor = $resultados[$idMuestra][$idParametro] ?? '-';
        $sheet->setCellValue($col . $fila, $fmtValor($valor));

        if (($fila % 2) === 0) {
            $sheet->duplicateStyle($styleDataEven, $col . $fila);
        } else {
            $sheet->duplicateStyle($styleDataOdd, $col . $fila);
        }

        if ($excedeLimitesSeleccionados($idParametro, $valor)) {
            $sheet->getStyle($col . $fila)->getFont()->getColor()->setARGB('FFFF0000');
            $sheet->getStyle($col . $fila)->getFont()->setBold(true);
        }

        $colOffset++;
    }

    $rangoFilaMuestra = 'B' . $fila . ':' . $colFinEstiloLetra . $fila;

    // Elegir color de fondo según estado de la muestra
    if ($esConsumoAgua) {
        $colorFilaBg = $colorCelesteConsumo;
        $colorFilaFont = 'FFFFFFFF'; // texto blanco sobre celeste oscuro
        $negritaFila = true;
    } elseif ($esEditada) {
        $colorFilaBg = $colorCelesteEditado;
        $colorFilaFont = 'FF000000';
        $negritaFila = false;
    } else {
        $colorFilaBg = $colorVerdeFilas;
        $colorFilaFont = 'FF000000';
        $negritaFila = false;
    }

    $forzarColorRelleno($rangoFilaMuestra, $colorFilaBg);
    $forzarFuente($rangoFilaMuestra, $colorFilaFont, $negritaFila);
    $aplicarBordeBlanco($rangoFilaMuestra);

    $fila++;
}

$aplicarBordeBlanco('B4:' . $colFinEstiloLetra . '10');
$forzarColorRelleno('B11:' . $colFinEstiloLetra . $ultimaFilaExport, $colorVerdeFilas);
$forzarFuente('B11:' . $colFinEstiloLetra . $ultimaFilaExport, 'FF000000', false);
$aplicarBordeBlanco('B11:' . $colFinEstiloLetra . $ultimaFilaExport);
$forzarColorRelleno('A1:A' . max($fila - 1, 11), $colorBlanco);

// Reaplicar colores especiales por muestra y excedencias
$filaTmp = $inicioFilaData;
foreach ($muestras as $muestraTmp) {
    $idMuestraTmp  = intval($muestraTmp['id_muestra']);
    $esCAReapl     = isset($muestrasConsumoAguaSet[$idMuestraTmp]);
    $esEditReapl   = isset($muestrasEditadasSet[$idMuestraTmp]);

    // Reaplicar color de fila especial (se pierde con el bloque global anterior)
    if ($esCAReapl || $esEditReapl) {
        $bgReapl   = $esCAReapl ? $colorCelesteConsumo : $colorCelesteEditado;
        $fntReapl  = $esCAReapl ? 'FFFFFFFF' : 'FF000000';
        $boldReapl = $esCAReapl;
        $rangoReapl = 'B' . $filaTmp . ':' . $colFinEstiloLetra . $filaTmp;
        $forzarColorRelleno($rangoReapl, $bgReapl);
        $forzarFuente($rangoReapl, $fntReapl, $boldReapl);
        $aplicarBordeBlanco($rangoReapl);
    }

    // Reaplicar excedencias en rojo/negrita
    $colOffsetTmp = 0;
    foreach ($paramIds as $idParametroTmp) {
        $colTmp = Coordinate::stringFromColumnIndex($inicioColParam + $colOffsetTmp);
        $valorTmp = $resultados[$idMuestraTmp][$idParametroTmp] ?? '-';
        if ($excedeLimitesSeleccionados($idParametroTmp, $valorTmp)) {
            $sheet->getStyle($colTmp . $filaTmp)->getFont()->getColor()->setARGB('FFFF0000');
            $sheet->getStyle($colTmp . $filaTmp)->getFont()->setBold(true);
        }
        $colOffsetTmp++;
    }
    $filaTmp++;
}

// Borde blanco para todo el bloque pintado (incluye celdas vacias y con datos)
$aplicarBordeBlanco('B4:' . $colFinEstiloLetra . $ultimaFilaExport);

$sheet->freezePane('G11');
$sheet->setAutoFilter('B6:' . Coordinate::stringFromColumnIndex($ultimaColParam) . ($fila - 1));

$nombreProyectoSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($proyecto['Nombre_Proyecto'] ?? 'proyecto'));
$filename = 'Monitoreo_' . $nombreProyectoSafe . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
