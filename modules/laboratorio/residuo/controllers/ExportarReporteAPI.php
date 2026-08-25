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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Html as HtmlWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function failExport($status, $message)
{
    http_response_code($status);
    die($message);
}

function normalizeResiduoText($value)
{
    $text = trim((string)$value);
    $text = strtolower($text);
    $text = strtr($text, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n'
    ]);
    $text = preg_replace('/\s+/', ' ', $text);
    return $text;
}

function formatPesoGramos($peso)
{
    $valor = round(floatval($peso), 3);
    if (abs($valor - round($valor)) < 0.0005) {
        return intval(round($valor)) . ' g';
    }

    $texto = number_format($valor, 3, '.', '');
    $texto = rtrim(rtrim($texto, '0'), '.');
    return $texto . ' g';
}

$conn = Conexion::conectar();
require_once $base_path . '/modules/laboratorio/models/LaboratorioModel.php';
$labAuthExp = new LaboratorioModel($conn);
$labAuthExp->denegarSiSinPermiso($_SESSION['usuario_id'], '?module=laboratorio&action=residuo', 'exportar');
$id_registro = intval($_REQUEST['id_registro'] ?? 0);
$previewHtml = (isset($_GET['preview_html']) && $_GET['preview_html'] === '1');

if ($id_registro <= 0) {
    failExport(400, 'Registro no encontrado');
}

$sqlCabecera = "SELECT
    rrl.Id_Registro_Res,
    rrl.Mes,
    rrl.Anio,
    rrl.Ubicacion,
    rrl.Codigo_SST,
    rrl.Observacion,
    CONCAT(u.nombres, ' ', u.apellidos) AS Responsable
FROM laboratorio.Registro_Residuos_Log rrl
LEFT JOIN comun.Usuarios u ON rrl.Id_Responsable = u.id_usuario
WHERE rrl.Id_Registro_Res = ? AND rrl.Activo = 1";

$stmtCabecera = sqlsrv_query($conn, $sqlCabecera, [$id_registro]);
if ($stmtCabecera === false) {
    failExport(500, 'Error consultando cabecera');
}

$cabecera = sqlsrv_fetch_array($stmtCabecera, SQLSRV_FETCH_ASSOC);
if (!$cabecera) {
    failExport(404, 'Informe no encontrado');
}

$mes = intval($cabecera['Mes'] ?? 0);
$anio = intval($cabecera['Anio'] ?? 0);
if ($mes <= 0 || $mes > 12 || $anio <= 0) {
    failExport(500, 'El informe no tiene mes/anio validos');
}

$sqlDetalles = "SELECT
    CAST(drl.Fecha_Dia AS DATE) AS Fecha_Dia,
    drl.Id_Residuo_Cat,
    rc.Subcategoria,
    SUM(CAST(drl.Peso_Valor AS FLOAT)) AS Total_Peso
FROM laboratorio.Detalle_Residuos_Log drl
INNER JOIN laboratorio.Residuo_Catalogo rc ON drl.Id_Residuo_Cat = rc.Id_Residuo_Cat
WHERE drl.Id_Registro_Res = ? AND drl.Activo = 1
GROUP BY CAST(drl.Fecha_Dia AS DATE), drl.Id_Residuo_Cat, rc.Subcategoria
ORDER BY CAST(drl.Fecha_Dia AS DATE) ASC";

$stmtDetalles = sqlsrv_query($conn, $sqlDetalles, [$id_registro]);
if ($stmtDetalles === false) {
    failExport(500, 'Error consultando detalles');
}

$sqlResiduos = "SELECT
    Id_Residuo_Cat,
    Codigo_Item,
    Nombre_Item,
    Unidad_Referencia
FROM laboratorio.Residuo_Catalogo
WHERE Activo = 1
ORDER BY Codigo_Item, Nombre_Item";

$stmtResiduos = sqlsrv_query($conn, $sqlResiduos);
if ($stmtResiduos === false) {
    failExport(500, 'Error consultando catalogo de residuos');
}

$sqlNormativas = "SELECT
    n.Nombre_Ley,
    n.Descripcion
FROM laboratorio.Reporte_Normativa_Asociada rna
INNER JOIN laboratorio.Normativa_SST n ON rna.Id_Normativa_SST = n.Id_Normativa_SST
WHERE rna.Id_Registro_Res = ? AND n.Activo = 1
ORDER BY n.Nombre_Ley";

$stmtNormativas = sqlsrv_query($conn, $sqlNormativas, [$id_registro]);
if ($stmtNormativas === false) {
    failExport(500, 'Error consultando normativas asociadas');
}

$mapeoSubcategorias = [
    'organico' => 'ORGANICO',
    'organicos' => 'ORGANICO',
    'aprovechable' => 'APROVECHABLE',
    'aprovechables' => 'APROVECHABLE',
    'no aprovechable' => 'NO APROVECHABLE',
    'no aprovechables' => 'NO APROVECHABLE',
    'quimico' => 'QUIMICO',
    'quimicos' => 'QUIMICO',
    'biologico' => 'BIOLOGICO',
    'biologicos' => 'BIOLOGICO',
    'metales pesados' => 'METALES PESADOS',
    'metal pesado' => 'METALES PESADOS',
    'reactivo' => 'REACTIVOS',
    'reactivos' => 'REACTIVOS',
    'material contaminado' => 'MATERIAL CONTAMINADO',
    'materiales contaminados' => 'MATERIAL CONTAMINADO'
];

$columnasCategoria = [
    'ORGANICO' => 'C',
    'APROVECHABLE' => 'D',
    'NO APROVECHABLE' => 'E',
    'QUIMICO' => 'F',
    'BIOLOGICO' => 'G',
    'METALES PESADOS' => 'H',
    'REACTIVOS' => 'I',
    'MATERIAL CONTAMINADO' => 'J'
];

$itemsPorDia = [];
$totalesPorResiduo = [];
while ($row = sqlsrv_fetch_array($stmtDetalles, SQLSRV_FETCH_ASSOC)) {
    $fechaObj = $row['Fecha_Dia'] ?? null;
    if (!($fechaObj instanceof DateTime)) {
        continue;
    }

    if (intval($fechaObj->format('n')) !== $mes || intval($fechaObj->format('Y')) !== $anio) {
        continue;
    }

    $dia = intval($fechaObj->format('j'));
    if ($dia <= 0 || $dia > 31) {
        continue;
    }

    $subcatKey = normalizeResiduoText($row['Subcategoria'] ?? '');
    $categoria = $mapeoSubcategorias[$subcatKey] ?? null;
    $peso = floatval($row['Total_Peso'] ?? 0);
    $idResiduo = intval($row['Id_Residuo_Cat'] ?? 0);

    if ($categoria !== null && isset($columnasCategoria[$categoria]) && $idResiduo > 0 && $peso > 0) {
        if (!isset($itemsPorDia[$dia])) {
            $itemsPorDia[$dia] = [];
        }
        if (!isset($itemsPorDia[$dia][$categoria])) {
            $itemsPorDia[$dia][$categoria] = [];
        }
        $itemsPorDia[$dia][$categoria][] = [
            'id' => $idResiduo,
            'peso' => $peso
        ];
    }

    if ($idResiduo > 0) {
        if (!isset($totalesPorResiduo[$idResiduo])) {
            $totalesPorResiduo[$idResiduo] = 0;
        }
        $totalesPorResiduo[$idResiduo] += $peso;
    }
}

$residuosCatalogo = [];
while ($row = sqlsrv_fetch_array($stmtResiduos, SQLSRV_FETCH_ASSOC)) {
    $residuosCatalogo[] = $row;
}

$normativas = [];
while ($row = sqlsrv_fetch_array($stmtNormativas, SQLSRV_FETCH_ASSOC)) {
    $nombreLey = trim((string)($row['Nombre_Ley'] ?? ''));
    $descripcion = trim((string)($row['Descripcion'] ?? ''));
    if ($nombreLey === '' && $descripcion === '') {
        continue;
    }
    $normativas[] = [
        'Nombre_Ley' => $nombreLey,
        'Descripcion' => $descripcion
    ];
}

$templatePath = realpath(__DIR__ . '/../temple/template_residuos.xlsx');
if (!$templatePath || !file_exists($templatePath)) {
    failExport(500, 'No se encontro la plantilla oficial de residuos');
}

try {
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    $meses = [
        1 => 'ENERO',
        2 => 'FEBRERO',
        3 => 'MARZO',
        4 => 'ABRIL',
        5 => 'MAYO',
        6 => 'JUNIO',
        7 => 'JULIO',
        8 => 'AGOSTO',
        9 => 'SEPTIEMBRE',
        10 => 'OCTUBRE',
        11 => 'NOVIEMBRE',
        12 => 'DICIEMBRE'
    ];

    $mesNombre = $meses[$mes] ?? ('MES ' . $mes);
    $codigoSst = trim((string)($cabecera['Codigo_SST'] ?? 'SST-16'));
    if ($codigoSst === '') {
        $codigoSst = 'SST-16';
    }

    $responsable = trim((string)($cabecera['Responsable'] ?? ''));
    $ubicacion = trim((string)($cabecera['Ubicacion'] ?? ''));
    $observacion = trim((string)($cabecera['Observacion'] ?? ''));

    $sheet->setCellValue('B2', 'REGISTRO DE RESIDUOS SOLIDOS - ' . $mesNombre . ' ' . $anio);
    $sheet->setCellValue('C5', $ubicacion !== '' ? $ubicacion : 'Laboratorio de Agua y Suelos');
    $sheet->setCellValue('G4', $responsable !== '' ? $responsable : 'No asignado');
    $sheet->setCellValue('G2', 'CODIGO ' . $codigoSst);
    $sheet->setCellValue('J2', 'VERSION 2');

    $sheetTitle = preg_replace('/[\[\]\:\*\?\/\\\\]/', '', $mesNombre . ' ' . $anio);
    $sheet->setTitle(substr($sheetTitle, 0, 31));

    // Amplia columnas para mejorar legibilidad del reporte exportado.
    $sheet->getColumnDimension('B')->setWidth(10.5);
    foreach (range('C', 'J') as $columna) {
        $sheet->getColumnDimension($columna)->setWidth(15);
    }

    $startDataRow = 9;
    $templateDataRows = 20;
    $diasMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
    $extraDataRows = max(0, $diasMes - $templateDataRows);

    if ($extraDataRows > 0) {
        $insertAt = $startDataRow + $templateDataRows;
        $sheet->insertNewRowBefore($insertAt, $extraDataRows);

        $styleRowData = $sheet->getStyle('B' . ($insertAt - 1) . ':J' . ($insertAt - 1));

        for ($i = 0; $i < $extraDataRows; $i++) {
            $targetRow = $insertAt + $i;
            $sheet->duplicateStyle($styleRowData, 'B' . $targetRow . ':J' . $targetRow);
        }
    }

    for ($dia = 1; $dia <= $diasMes; $dia++) {
        $rowExcel = $startDataRow + $dia - 1;
        $fechaDia = new DateTime(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
        $sheet->setCellValue('B' . $rowExcel, ExcelDate::PHPToExcel($fechaDia));
        $sheet->getStyle('B' . $rowExcel)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        foreach ($columnasCategoria as $categoria => $columna) {
            $cell = $columna . $rowExcel;

            $lineas = [];
            if (!empty($itemsPorDia[$dia][$categoria])) {
                usort($itemsPorDia[$dia][$categoria], function ($a, $b) {
                    return intval($a['id']) <=> intval($b['id']);
                });

                foreach ($itemsPorDia[$dia][$categoria] as $item) {
                    $lineas[] = '(' . intval($item['id']) . ') ' . formatPesoGramos($item['peso']);
                }
            }

            if (!empty($lineas)) {
                $sheet->setCellValue($cell, implode("\n", $lineas));
                $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
            } else {
                $sheet->setCellValue($cell, null);
            }
        }
    }

    $normHeaderRow = 29 + $extraDataRows;
    $normStartRow = $normHeaderRow + 1;
    $obsRowBase = 32 + $extraDataRows;
    $legendHeaderRowBase = 35 + $extraDataRows;

    $lineasNormativa = [];
    foreach ($normativas as $n) {
        $linea = trim((string)$n['Nombre_Ley']);
        if ($n['Descripcion'] !== '') {
            $linea .= ' - ' . trim((string)$n['Descripcion']);
        }
        if ($linea !== '') {
            $lineasNormativa[] = $linea;
        }
    }
    if (empty($lineasNormativa)) {
        $lineasNormativa[] = 'Sin normativa asociada.';
    }

    $extraNormRows = max(0, count($lineasNormativa) - 2);
    if ($extraNormRows > 0) {
        $sheet->insertNewRowBefore($obsRowBase, $extraNormRows);

        $styleNorm = $sheet->getStyle('B31:J31');
        for ($i = 0; $i < $extraNormRows; $i++) {
            $targetRow = $obsRowBase + $i;
            $sheet->duplicateStyle($styleNorm, 'B' . $targetRow . ':J' . $targetRow);
            $sheet->mergeCells('B' . $targetRow . ':I' . $targetRow);
        }
    }

    $obsRow = $obsRowBase + $extraNormRows;
    $legendHeaderRow = $legendHeaderRowBase + $extraNormRows;
    $legendStartRow = $legendHeaderRow + 1;

    $sheet->setCellValue('B' . $normHeaderRow, 'Normativa aplicable:');
    for ($i = 0; $i < count($lineasNormativa); $i++) {
        $targetRow = $normStartRow + $i;
        $sheet->setCellValue('B' . $targetRow, '(' . ($i + 1) . ') ' . $lineasNormativa[$i]);
        $sheet->getStyle('B' . $targetRow)->getAlignment()->setWrapText(true);
    }

    $obsTexto = 'OBSERVACIONES:';
    if ($observacion !== '') {
        $obsTexto .= ' ' . $observacion;
    }
    $sheet->setCellValue('B' . $obsRow, $obsTexto);
    $sheet->getStyle('B' . $obsRow)->getAlignment()->setWrapText(true);

    $sheet->setCellValue('C' . $legendHeaderRow, 'LEYENDA');
    $sheet->setCellValue('E' . $legendHeaderRow, 'PESO');
    $sheet->setCellValue('F' . $legendHeaderRow, 'UNIDAD');
    $sheet->duplicateStyle($sheet->getStyle('E' . $legendHeaderRow), 'F' . $legendHeaderRow);

    $baseLegendRows = 13;
    $legendCount = max(1, count($residuosCatalogo));
    $extraLegendRows = max(0, $legendCount - $baseLegendRows);

    if ($extraLegendRows > 0) {
        $insertLegendAt = $legendStartRow + $baseLegendRows;
        $sheet->insertNewRowBefore($insertLegendAt, $extraLegendRows);

        $styleLegend = $sheet->getStyle('C' . $legendStartRow . ':I' . $legendStartRow);

        for ($i = 0; $i < $extraLegendRows; $i++) {
            $targetRow = $insertLegendAt + $i;
            $sheet->duplicateStyle($styleLegend, 'C' . $targetRow . ':I' . $targetRow);
        }
    }

    $rowsToClear = max($baseLegendRows, $legendCount);
    for ($i = 0; $i < $rowsToClear; $i++) {
        $targetRow = $legendStartRow + $i;
        $sheet->setCellValue('C' . $targetRow, null);
        $sheet->setCellValue('E' . $targetRow, null);
        $sheet->setCellValue('F' . $targetRow, null);
    }

    if (empty($residuosCatalogo)) {
        $sheet->setCellValue('C' . $legendStartRow, 'Sin residuos activos en catalogo');
    } else {
        foreach ($residuosCatalogo as $idx => $residuo) {
            $targetRow = $legendStartRow + $idx;
            $idResiduo = intval($residuo['Id_Residuo_Cat'] ?? 0);
            $codigo = trim((string)($residuo['Codigo_Item'] ?? ''));
            $nombre = trim((string)($residuo['Nombre_Item'] ?? ''));
            $unidad = trim((string)($residuo['Unidad_Referencia'] ?? ''));
            $totalPeso = round(floatval($totalesPorResiduo[$idResiduo] ?? 0), 3);

            $leyenda = trim($codigo . ' - ' . $nombre, ' -');
            if ($leyenda === '') {
                $leyenda = 'Residuo #' . $idResiduo;
            }

            $sheet->setCellValue('C' . $targetRow, $leyenda);
            $sheet->setCellValue('E' . $targetRow, formatPesoGramos($totalPeso));
            $sheet->setCellValue('F' . $targetRow, $unidad !== '' ? $unidad : 'g');
        }
    }

    $codigoSeguro = preg_replace('/[^A-Za-z0-9_\-]/', '_', $codigoSst);
    if ($codigoSeguro === '') {
        $codigoSeguro = 'SST16';
    }

    $nombreArchivo = 'Registro_Residuos_' . $codigoSeguro . '_' . sprintf('%02d', $mes) . '_' . $anio . '.xlsx';

    if ($previewHtml) {
        $previewSpreadsheet = clone $spreadsheet;
        $previewSheet = $previewSpreadsheet->getActiveSheet();

        foreach (range('B', 'J') as $col) {
            $previewSheet->getColumnDimension($col)->setWidth(22);
        }

        $legendRowsUsed = max(1, count($residuosCatalogo));
        $legendRowsTotal = max($baseLegendRows, $legendCount);
        $legendUsedEndRow = $legendStartRow + $legendRowsUsed - 1;
        $legendTotalEndRow = $legendStartRow + $legendRowsTotal - 1;

        for ($r = $legendHeaderRow; $r <= $legendTotalEndRow; $r++) {
            $previewSheet->getStyle('G' . $r . ':I' . $r)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_NONE);

            if ($r > $legendUsedEndRow) {
                $previewSheet->getStyle('C' . $r . ':F' . $r)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_NONE);
            }
        }

        if ($legendRowsUsed < $legendRowsTotal) {
            for ($i = $legendRowsUsed; $i < $legendRowsTotal; $i++) {
                $targetRow = $legendStartRow + $i;
                $previewSheet->getStyle('C' . $targetRow . ':I' . $targetRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_NONE);
            }
        }

        $lastRelevantRow = $legendStartRow + $legendRowsUsed + 1;
        for ($r = $legendHeaderRow; $r <= $lastRelevantRow; $r++) {
            foreach (range('B', 'J') as $col) {
                $coord = $col . $r;
                $cellValue = trim((string)$previewSheet->getCell($coord)->getFormattedValue());

                $fill = $previewSheet->getStyle($coord)->getFill();
                $fillType = $fill->getFillType();
                $fillColor = strtoupper((string)$fill->getStartColor()->getARGB());
                $hasColor = ($fillType !== Fill::FILL_NONE)
                    && !in_array($fillColor, ['00000000', '00FFFFFF', 'FFFFFFFF'], true);

                if ($cellValue === '' && !$hasColor) {
                    $previewSheet->getStyle($coord)
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_NONE);
                }
            }
        }

        $previewSheet->getColumnDimension('A')->setVisible(false);

        $highestRow = $previewSheet->getHighestRow();
        for ($r = $lastRelevantRow + 1; $r <= $highestRow; $r++) {
            $previewSheet->getRowDimension($r)->setVisible(false);
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
            . 'img{max-width:none !important;height:auto !important;}'
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

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    failExport(500, 'Error al generar el Excel: ' . $e->getMessage());
}
?>

