<?php
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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$mes  = intval($_GET['mes']  ?? date('m'));
$anio = intval($_GET['anio'] ?? date('Y'));
if ($mes < 1 || $mes > 12)        $mes  = intval(date('m'));
if ($anio < 2000 || $anio > 2100) $anio = intval(date('Y'));

$dias_mes      = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
$meses_nombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio',
                  'Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mes_nombre    = $meses_nombres[$mes - 1];

$conn = Conexion::conectar();
require_once $base_path . '/modules/laboratorio/models/LaboratorioModel.php';
$labAuthExp = new LaboratorioModel($conn);
$labAuthExp->denegarSiSinPermiso($_SESSION['usuario_id'], '?module=laboratorio&action=reactivo', 'exportar');
if (!$conn) { http_response_code(500); die('Error de conexion'); }

// ===== DATOS =====
$reactivos = [];
$stmt_rl = sqlsrv_query($conn,
    "SELECT Id_Reactivo, Nombre, Unidad_Medida, Cantidad_Stock,
            ISNULL(Cantidad_Inicial,0) AS Cantidad_Inicial
     FROM laboratorio.Reactivo_Lab WHERE Activo=1 ORDER BY Nombre");
if ($stmt_rl) {
    while ($r = sqlsrv_fetch_array($stmt_rl, SQLSRV_FETCH_ASSOC)) {
        $reactivos[] = $r;
    }
}

$movimientos = [];
$stmt_mov = sqlsrv_query($conn,
    "SELECT mk.Id_Reactivo, mk.Tipo_Movimiento, mk.Cantidad,
            DAY(mk.Fecha_Registro) AS Dia
     FROM laboratorio.Movimiento_Kardex mk
     WHERE mk.Activo=1 AND YEAR(mk.Fecha_Registro)=? AND MONTH(mk.Fecha_Registro)=?
     ORDER BY mk.Id_Reactivo, mk.Fecha_Registro",
    [$anio, $mes]);
if ($stmt_mov) {
    while ($row = sqlsrv_fetch_array($stmt_mov, SQLSRV_FETCH_ASSOC)) {
        $ir   = $row['Id_Reactivo'];
        $dia  = $row['Dia'];
        $tipo = strtoupper($row['Tipo_Movimiento'][0] ?? 'E');
        if (!isset($movimientos[$ir]))       $movimientos[$ir] = [];
        if (!isset($movimientos[$ir][$dia])) $movimientos[$ir][$dia] = ['E'=>0,'S'=>0];
        $movimientos[$ir][$dia][$tipo] += intval($row['Cantidad']);
    }
}

// ===== SPREADSHEET =====
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Kardex ' . $mes_nombre . ' ' . $anio);

// Colors
$C_AZUL_OSC  = 'FF1F3864';
$C_AZUL_MED  = 'FF2E75B6';
$C_VERDE_E   = 'FF70AD47';
$C_NARANJA_S = 'FFED7D31';
$C_GRIS_ALT  = 'FFF2F2F2';
$C_BLANCO    = 'FFFFFFFF';
$C_NEGRO     = 'FF000000';

// Layout columns:
// A: margin | B: N° | C: Reactivo | D: U.M. | E: Inicial | F: Actual
// G..G+dias-1: dias | G+dias: Total E | G+dias+1: Total S
$COL_B = 2; $COL_C = 3; $COL_D = 4; $COL_E = 5; $COL_F = 6;
$colDiaStart = 7;
$colTotE     = $colDiaStart + $dias_mes;
$colTotS     = $colTotE + 1;
$colFin      = $colTotS;

$letC     = 'C';
$letD     = 'D';
$letE     = 'E';
$letF     = 'F';
$letDiaS  = Coordinate::stringFromColumnIndex($colDiaStart);
$letTotE  = Coordinate::stringFromColumnIndex($colTotE);
$letTotS  = Coordinate::stringFromColumnIndex($colTotS);
$letFin_s = Coordinate::stringFromColumnIndex($colFin);

// ===== HELPERS =====
$mk = function(string $bg, string $fg = 'FFFFFFFF', bool $bold = true, int $sz = 9,
               string $ha = Alignment::HORIZONTAL_CENTER) use ($C_BLANCO): array {
    return [
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
        'font'      => ['bold' => $bold, 'color' => ['argb' => $fg], 'size' => $sz, 'name' => 'Calibri'],
        'alignment' => ['horizontal' => $ha, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $C_BLANCO]]],
    ];
};

$ap = function(string $range, array $style) use ($sheet) {
    $sheet->getStyle($range)->applyFromArray($style);
};

// ===== MARGENES VISUALES (filas 1-3) =====
foreach ([1, 2, 3] as $mr) { $sheet->getRowDimension($mr)->setRowHeight(4); }
$ap('A1:' . $letFin_s . '3', ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_BLANCO]]]);

// ===== FILA 4: TITULO =====
$sheet->mergeCells('B4:' . $letFin_s . '4');
$sheet->getCell('B4')->setValue('KARDEX DE REACTIVOS DE LABORATORIO');
$ap('B4:' . $letFin_s . '4', $mk($C_AZUL_OSC, 'FFFFFFFF', true, 13));
$sheet->getRowDimension(4)->setRowHeight(34);

// ===== FILA 5: MES/AÑO =====
$sheet->mergeCells('B5:' . $letFin_s . '5');
$sheet->getCell('B5')->setValue(strtoupper($mes_nombre) . ' ' . $anio);
$ap('B5:' . $letFin_s . '5', $mk($C_AZUL_MED, 'FFFFFFFF', true, 11));
$sheet->getRowDimension(5)->setRowHeight(22);

// ===== FILAS 6-7: CABECERAS =====
// Celdas fijas (doble fila)
foreach (['B6:B7'=>'N°', 'C6:C7'=>'REACTIVO', 'D6:D7'=>'U.M.', 'E6:E7'=>'INICIAL', 'F6:F7'=>'ACTUAL'] as $merge => $label) {
    $sheet->mergeCells($merge);
    [$col, $row] = [$merge[0], '6'];
    $sheet->getCell($col . '6')->setValue($label);
}
$ap('B6:F7', $mk($C_AZUL_OSC));
// Nombre con alineación izquierda
$ap('C6', ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1]]);

// Dias: fila 6 = número del día, fila 7 = E / S
for ($dia = 1; $dia <= $dias_mes; $dia++) {
    $col = Coordinate::stringFromColumnIndex($colDiaStart + $dia - 1);
    $sheet->getCell($col . '6')->setValue(str_pad($dia, 2, '0', STR_PAD_LEFT));
    $ap($col . '6', $mk($C_AZUL_MED, 'FFFFFFFF', true, 8));
    $sheet->getCell($col . '7')->setValue('E/S');
    $ap($col . '7', $mk($C_AZUL_MED, 'FFFFFFFF', false, 7));
}

// Total E / Total S
$sheet->mergeCells($letTotE . '6:' . $letTotE . '7');
$sheet->getCell($letTotE . '6')->setValue("TOTAL\nENT.");
$ap($letTotE . '6:' . $letTotE . '7', $mk($C_VERDE_E));

$sheet->mergeCells($letTotS . '6:' . $letTotS . '7');
$sheet->getCell($letTotS . '6')->setValue("TOTAL\nSAL.");
$ap($letTotS . '6:' . $letTotS . '7', $mk($C_NARANJA_S));

$sheet->getRowDimension(6)->setRowHeight(20);
$sheet->getRowDimension(7)->setRowHeight(14);

// ===== ANCHOS DE COLUMNA =====
$sheet->getColumnDimension('A')->setWidth(2.5);
$sheet->getColumnDimension('B')->setWidth(5);
$sheet->getColumnDimension('C')->setWidth(28);
$sheet->getColumnDimension('D')->setWidth(7);
$sheet->getColumnDimension('E')->setWidth(9);
$sheet->getColumnDimension('F')->setWidth(9);
for ($dia = 1; $dia <= $dias_mes; $dia++) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colDiaStart + $dia - 1))->setWidth(7);
}
$sheet->getColumnDimension($letTotE)->setWidth(8.5);
$sheet->getColumnDimension($letTotS)->setWidth(8.5);

// ===== FILAS DE DATOS =====
$fila = 8;
foreach ($reactivos as $idx => $reactivo) {
    $id_react      = $reactivo['Id_Reactivo'];
    $stock_actual  = floatval($reactivo['Cantidad_Stock']);
    $stock_inicial = floatval($reactivo['Cantidad_Inicial']);
    $bg            = ($idx % 2 === 0) ? $C_BLANCO : $C_GRIS_ALT;

    $stBase  = $mk($bg, $C_NEGRO, false, 9);
    $stLeft  = $mk($bg, $C_NEGRO, false, 9, Alignment::HORIZONTAL_LEFT);
    $stLeft['alignment']['indent'] = 1;

    $sheet->getCell('B' . $fila)->setValue($idx + 1);
    $ap('B' . $fila, $stBase);

    $sheet->getCell('C' . $fila)->setValue($reactivo['Nombre']);
    $ap('C' . $fila, $stLeft);

    $sheet->getCell('D' . $fila)->setValue($reactivo['Unidad_Medida']);
    $ap('D' . $fila, $stBase);

    $sheet->getCell('E' . $fila)->setValue(number_format($stock_inicial, 2, '.', ''));
    $ap('E' . $fila, $stBase);

    $sheet->getCell('F' . $fila)->setValue(number_format($stock_actual, 2, '.', ''));
    $ap('F' . $fila, $stBase);

    $totalE = 0;
    $totalS = 0;

    for ($dia = 1; $dia <= $dias_mes; $dia++) {
        $col     = Coordinate::stringFromColumnIndex($colDiaStart + $dia - 1);
        $entrada = $movimientos[$id_react][$dia]['E'] ?? 0;
        $salida  = $movimientos[$id_react][$dia]['S'] ?? 0;
        $totalE += $entrada;
        $totalS += $salida;

        if ($entrada > 0 || $salida > 0) {
            if ($entrada > 0 && $salida > 0) {
                $texto = '+' . $entrada . "\n-" . $salida;
                $bgCel = $C_AZUL_MED;
            } elseif ($entrada > 0) {
                $texto = '+' . $entrada;
                $bgCel = $C_VERDE_E;
            } else {
                $texto = '-' . $salida;
                $bgCel = $C_NARANJA_S;
            }
            $sheet->getCell($col . $fila)->setValue($texto);
            $ap($col . $fila, $mk($bgCel, 'FFFFFFFF', true, 8));
        } else {
            $sheet->getCell($col . $fila)->setValue('');
            $ap($col . $fila, $stBase);
        }
    }

    // Totales
    if ($totalE > 0) {
        $sheet->getCell($letTotE . $fila)->setValue($totalE);
        $ap($letTotE . $fila, $mk($C_VERDE_E, 'FFFFFFFF', true, 9));
    } else {
        $sheet->getCell($letTotE . $fila)->setValue('');
        $ap($letTotE . $fila, $stBase);
    }

    if ($totalS > 0) {
        $sheet->getCell($letTotS . $fila)->setValue($totalS);
        $ap($letTotS . $fila, $mk($C_NARANJA_S, 'FFFFFFFF', true, 9));
    } else {
        $sheet->getCell($letTotS . $fila)->setValue('');
        $ap($letTotS . $fila, $stBase);
    }

    $sheet->getRowDimension($fila)->setRowHeight(17);
    $fila++;
}

// Columna A siempre blanca
$ap('A1:A' . ($fila + 1), [
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $C_BLANCO]],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_NONE]],
]);

// Freeze pane en dia 1
$sheet->freezePane($letDiaS . '8');

// ===== OUTPUT =====
$filename = 'Kardex_Reactivos_' . strtoupper($mes_nombre) . '_' . $anio . '.xlsx';
while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
