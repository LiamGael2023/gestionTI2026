<?php
/**
 * obtener_plantilla_preview.php
 * Genera HTML simplificado de la plantilla Excel para preview
 */
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: text/html; charset=utf-8');

$tipo = $_GET['tipo'] ?? 'agua';

$plantilla_dir = __DIR__ . '/../muestra/plantilla';
$archivos = [
    'agua'  => 'CSJ-DRDYCS-LAYS – R - 2- RESULTADOS ANALISIS DE AGUAS.xlsx',
    'suelo' => 'CSJ-DRDYCS-LAYS – R - 1-RESULTADOS ANALISIS DE  SUELOS.xlsx'
];
$archivo = $archivos[$tipo] ?? $archivos['agua'];
$filepath = $plantilla_dir . '/' . $archivo;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('<div style="color:red;padding:20px;">Plantilla no encontrada</div>');
}

require_once __DIR__ . '/../../../libs/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

try {
    $reader = IOFactory::createReader('Xlsx');
    $spreadsheet = $reader->load($filepath);
    $sheet = $spreadsheet->getActiveSheet();
    
    $maxRow = $sheet->getHighestRow();
    $maxCol = $sheet->getHighestColumn();
    $maxColIdx = Coordinate::columnIndexFromString($maxCol);
    
    $colWidths = [];
    for ($c = 1; $c <= min($maxColIdx, 15); $c++) {
        $letter = Coordinate::stringFromColumnIndex($c);
        $w = $sheet->getColumnDimension($letter)->getWidth();
        $colWidths[$c] = max(40, min(180, intval(abs($w) * 6)));
    }
    
    $mergedMap = [];
    foreach ($sheet->getMergeCells() as $range) {
        $parts = explode(':', $range);
        $start = Coordinate::coordinateFromString($parts[0]);
        $end = Coordinate::coordinateFromString($parts[1] ?? $parts[0]);
        $sr = $start[1]; $sc = Coordinate::columnIndexFromString($start[0]);
        $er = $end[1]; $ec = Coordinate::columnIndexFromString($end[0]);
        for ($r = $sr; $r <= $er; $r++) {
            for ($c = $sc; $c <= $ec; $c++) {
                $mergedMap["$r.$c"] = ['sr' => $sr, 'sc' => $sc, 'er' => $er, 'ec' => $ec];
            }
        }
    }
    
    $firmaRows = [];
    for ($r = 1; $r <= $maxRow; $r++) {
        for ($c = 1; $c <= min($maxColIdx, 15); $c++) {
            $val = trim(strtoupper((string)$sheet->getCell([$c, $r])->getValue()));
            if (preg_match('/ENCARGADO|ANALISTA|JEFE|FIRMA|RESPONSABLE/', $val)) {
                $firmaRows[$r] = true;
            }
        }
    }
    
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        body { margin:0; padding:8px; font-family:Calibri,Arial,sans-serif; background:#fff; }
        table.excel { border-collapse:collapse; font-size:8px; width:auto; }
        table.excel td { padding:2px 4px; border:1px solid #d0d0d0; white-space:nowrap; overflow:hidden; }
        table.excel tr.firma-row td { background:#fff9c4 !important; }
        .bold { font-weight:600; }
</style></head><body><table class="excel">';
    
    $skipUntil = [];
    
    for ($r = 1; $r <= min($maxRow, 80); $r++) {
        $isFirma = isset($firmaRows[$r]);
        $html .= '<tr class="' . ($isFirma ? 'firma-row' : '') . '">';
        
        for ($c = 1; $c <= min($maxColIdx, 15); $c++) {
            if (isset($skipUntil["$r.$c"])) continue;
            
            $merge = $mergedMap["$r.$c"] ?? null;
            $colspan = 1; $rowspan = 1;
            if ($merge && $merge['sr'] == $r && $merge['sc'] == $c) {
                $colspan = $merge['ec'] - $merge['sc'] + 1;
                $rowspan = $merge['er'] - $merge['sr'] + 1;
                for ($mr = $merge['sr']; $mr <= $merge['er']; $mr++) {
                    for ($mc = $merge['sc']; $mc <= $merge['ec']; $mc++) {
                        if ($mr != $r || $mc != $c) $skipUntil["$mr.$mc"] = true;
                    }
                }
            } elseif ($merge) {
                continue;
            }
            
            $cell = $sheet->getCell([$c, $r]);
            $val = $cell->getValue();
            $valStr = $val !== null ? trim((string)$val) : '';
            $style = $sheet->getStyle([$c, $r]);
            $font = $style->getFont();
            $fill = $style->getFill()->getStartColor()->getARGB();
            $align = $style->getAlignment()->getHorizontal();
            
            $tdStyle = '';
            if ($font->getBold()) $tdStyle .= 'font-weight:600;';
            if ($font->getSize() > 0) $tdStyle .= 'font-size:' . ($font->getSize() * 0.75) . 'px;';
            if ($fill && $fill !== '00000000') $tdStyle .= 'background:#' . substr($fill, 2) . ';';
            if ($align === 'center') $tdStyle .= 'text-align:center;';
            elseif ($align === 'right') $tdStyle .= 'text-align:right;';
            if ($isFirma) $tdStyle .= 'background:#fff9c4 !important;';
            
            $w = $colWidths[$c] ?? 50;
            $tdStyle .= 'width:' . ($w) . 'px;';
            
            $attrs = 'style="' . $tdStyle . '"';
            if ($colspan > 1) $attrs .= ' colspan="' . $colspan . '"';
            if ($rowspan > 1) $attrs .= ' rowspan="' . $rowspan . '"';
            
            $html .= '<td ' . $attrs . '>' . htmlspecialchars($valStr ?: ' ', ENT_QUOTES) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</table></body></html>';
    echo $html;
    
} catch (Exception $e) {
    http_response_code(500);
    echo '<div style="color:red;padding:20px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
