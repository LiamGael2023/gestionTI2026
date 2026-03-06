<?php
error_reporting(0);
ob_start();
require_once 'tcpdf_include.php';


$pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(TRUE, 6);
$pdf->AddPage();


$data = [
    'APELLIDOS' => 'MINES OLIVA', 'NOMBRES' => 'GARY MANUEL', 'DNI' => '43205792',
    'DIAS' => '30', 'CATEGORIA' => 'SPA', 'FECHA_INGRESO' => '03/05/2024',
    'CARGO' => 'Especialista Administrativo III', 'AREA' => 'Oficina de Administración - Personal',
    'AFP' => 'Spp Profuturo', 'GRUPO' => 'Profesional', 'SITUACION' => 'Activo',
    'ACTIVIDAD' => '', 'MES_ANIO' => 'SEPTIEMBRE 2025'
];

$remuneraciones = [
    'Incre. Remune. Conven.' => 700.00, 'Sueldo Basico' => 9800.00, 'Asignacion Familiar' => 113.00,
    'Incremento DS 313-2023-EF' => 50.00, 'Incremento DS 265-2024-EF' => 50.00, 'Incremento DS 279-2024-EF' => 100.00
];
$descuentos_ley = [
    'Aporte Obligatorio' => 1081.30, 'Prima de Seguro' => 148.14, 'Comision Variable' => 182.74, 'Quinta Categoria' => 1511.00
];
$descuentos_varios = [
    'Descuento Judicial' => 2366.95, 'BANCO DE COMERCIO' => 2084.41, 'Descuento Sindical' => 20.00,
    'Essalud Vida' => 5.00, 'Cooperativa de Ahor' => 88.00, 'COOSEDOM - "02 DE MA"' => 778.65
];
$aportes_empleador = ['RCSSS ESSALUD (9.00%)' => 973.00];
$totales_resumen = [
    'Total Ingresos     ' => 10813.00,
    'Total Egresos' => 8266.19,
    'Neto Pagar' => 2546.81
];


$width = 193; 
$height_fila1 = 55; 
$height_fila2 = 64; 
$col_width = $width / 4; 


// $logo_path = 'imagen.png'; 
$logo_path = __DIR__ . '/images/logo.png';

$pdf->Image($logo_path, 160, 17, 38, 10); 



$pdf->SetXY(9, 14);
$pdf->SetFillColor(255,255,255); 
$pdf->Rect(9, 14, $width, $height_fila1, 'D'); 



$pdf->SetFont('helvetica', '', 6);

// Proyecto
$pdf->SetXY(12, 16);
$pdf->MultiCell($width, 6, "PROYECTO ESPECIAL CHAVIMOCHIC", 0, 'L', 0, 1);

// Unidad
$pdf->SetXY(12, 19);
$pdf->MultiCell($width, 5, "UNIDAD DE PERSONAL", 0, 'L', 0, 1);

// Área
$pdf->SetXY(12, 22);
$pdf->MultiCell($width, 5, "ÁREA DE REMUNERACIONES", 0, 'L', 0, 1);




$pdf->SetFont('helvetica', 'B', 9);

// CAP
$pdf->SetXY(78, 22);
$pdf->MultiCell(20, 5, "CAP", 0, 'L', 0, 0);

// Mes y año
$pdf->SetXY(100, 28);
$pdf->MultiCell(50, 5, "SEPTIEMBRE", 0, 'L', 0, 0);

$pdf->SetXY(135, 28);
$pdf->MultiCell(50, 5, "2025", 0, 'L', 0, 0);

// Boleta de pago
$pdf->SetXY(12, 28);
$pdf->MultiCell(60, 5, "Boleta de Pago: Mensual", 0, 'L', 0, 1);



// --- Datos personales ---
// Apellidos
$pdf->SetFont('helvetica', '', 8);

// === Fila 1 ===
$pdf->SetXY(12, 35);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Apellidos:</b> {$data['APELLIDOS']}", 0, 0);

$pdf->SetXY(70, 35);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Días:</b> {$data['DIAS']}", 0, 0);

$pdf->SetXY(100, 35);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Categoría:</b> {$data['CATEGORIA']}", 0, 0);

$pdf->SetXY(135, 35);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Fec Ingreso:</b> {$data['FECHA_INGRESO']}", 0, 1);


// === Fila 2 ===
$pdf->SetXY(12, 42);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Nombres:</b> {$data['NOMBRES']}", 0, 0);

$pdf->SetXY(70, 42);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Cargo:</b> {$data['CARGO']}", 0, 0);

$pdf->SetXY(135, 42);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Área:</b> {$data['AREA']}", 0, 1);


// === Fila 3 ===
$pdf->SetXY(12, 52);
$pdf->writeHTMLCell(0, 5, '', '', "<b>DNI:</b> {$data['DNI']}", 0, 0);

$pdf->SetXY(70, 52);
$pdf->writeHTMLCell(0, 5, '', '', "<b>AFP:</b> {$data['AFP']}", 0, 0);

$pdf->SetXY(135, 52);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Grupo:</b> {$data['GRUPO']}", 0, 1);


// === Fila 4 ===
$pdf->SetXY(12, 62);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Actividad:</b> {$data['ACTIVIDAD']}", 0, 0);

$pdf->SetXY(70, 60);
$pdf->writeHTMLCell(0, 5, '', '', "<b>Situac.:</b> {$data['SITUACION']}", 0, 1);



// --- Fila 2: 4 columnas ---
$y_start = 14 + $height_fila1;
$pdf->SetXY(9, $y_start);
$pdf->SetFont('helvetica', '', 7);
$html1 = '<b><u>Remuneraciones:</u></b><br><br>'; 
foreach ($remuneraciones as $k => $v) {
    $html1 .= "$k: " . number_format($v, 2) . "<br>";
}
$pdf->writeHTMLCell($col_width, $height_fila2, 9, $y_start, $html1, 1, 0, false, true, 'L', true);

$html2 = '<b><u>Descuentos de Ley:</u></b><br><br>';
foreach ($descuentos_ley as $k => $v) {
    $html2 .= "$k: " . number_format($v, 2) . "<br>";
}
$pdf->writeHTMLCell($col_width, $height_fila2, 9 + $col_width, $y_start, $html2, 1, 0, false, true, 'L', true);

$html3 = '<b><u>Descuentos Varios:</u></b><br><br>';
foreach ($descuentos_varios as $k => $v) {
    $html3 .= "$k: " . number_format($v, 2) . "<br>";
}
$pdf->writeHTMLCell($col_width, $height_fila2, 9 + $col_width*2, $y_start, $html3, 1, 0, false, true, 'L', true);

$html4 = '<b><u>Totales:</u></b><br><br>';
foreach ($totales_resumen as $k => $v) {
    $html4 .= "$k: " . number_format($v, 2) . "<br>";
}
$pdf->writeHTMLCell($col_width, $height_fila2, 9 + $col_width*3, $y_start, $html4, 1, 1, false, true, 'L', true);

ob_end_clean();
// --- Salida ---
$pdf->Output('boleta_pago_A5_horizontal_tabla.pdf', 'I');
?>
