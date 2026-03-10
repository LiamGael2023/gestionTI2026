<?php
require_once 'tcpdf_include.php';
require_once('../../config/db.php');
require_once('../../modules/transportes/controllers/VehiculoController.php');
require_once('../../modules/transportes/models/VehiculoModel.php');


if (!isset($_GET['placa'])) {
    die('Faltan parámetros');
}

$placa = $_GET['placa'];

$datos_vehiculo = ControladorVehiculo::ctrMostrarVehiculoReporte("placa", $placa);

if (!$datos_vehiculo || count($datos_vehiculo) == 0) {
    die("No se encontró la placa.");
}

$row = $datos_vehiculo[0];

function safeText($text) {
    $text = trim($text ?? '');
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

$placa_text       = safeText($row["placa"] ?? '');
$tipo_vehiculo    = safeText($row["nombre_tipo"] ?? '');
$marca            = safeText($row["marca"] ?? '');
$modelo           = safeText($row["modelo"] ?? '');
$color            = safeText($row["color"] ?? '');
$anio             = safeText($row["anioFabricacion"] ?? '');
$chasis           = safeText($row["numero_chasis"] ?? '');
$codigo_patr      = safeText($row["codigo_patrimonial"] ?? '');
$fecha_registro   = !empty($row["fecha_registro"]) && $row["fecha_registro"] instanceof DateTime
    ? $row["fecha_registro"]->format("d/m/Y")
    : (is_string($row["fecha_registro"]) ? date("d/m/Y", strtotime($row["fecha_registro"])) : '');

$pdf = new TCPDF('P', 'mm', 'A6', true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('CHAVIMOCHIC');
$pdf->SetTitle('Reporte Vehículo');
$pdf->SetSubject('Datos del Vehículo');

$pdf->SetMargins(0, 0, 0);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetAutoPageBreak(false, 0);
$pdf->SetFont('helvetica', '', 10);

$pdf->AddPage('P', 'A6');


$imgPath = __DIR__ . '/images/vehiculo.png';
$pdf->Image($imgPath, 0, 0, 105, 148, 'PNG');


$pdf->SetFont('helvetica', 'B', 14); 
$pdf->SetXY(0, 22);
$pdf->Cell(0, 10, 'DATOS DEL VEHÍCULO', 0, 1, 'C'); 


function writeLabelAndValue($pdf, $x, $y, $label, $value) {
    $pdf->SetXY($x, $y);
    $pdf->SetFont('helvetica', 'B', 12); 
    $pdf->Write(0, $label . ': ');
    $pdf->SetFont('helvetica', '', 12); 
    $pdf->Write(0, $value);
}

writeLabelAndValue($pdf, 20, 37, 'Placa', $placa_text);
writeLabelAndValue($pdf, 20, 44, 'Tipo Vehículo', $tipo_vehiculo);
writeLabelAndValue($pdf, 20, 51, 'Marca', $marca);
writeLabelAndValue($pdf, 20, 58, 'Modelo', $modelo);
writeLabelAndValue($pdf, 20, 65, 'Color', $color);
writeLabelAndValue($pdf, 20, 72, 'Año', $anio);
writeLabelAndValue($pdf, 20, 79, 'Chasis', $chasis);
writeLabelAndValue($pdf, 20, 86, 'Código Patrimonial', $codigo_patr);

writeLabelAndValue($pdf, 20, 93, 'Fecha Consulta', $fecha_registro);

$pdf->Output('reporte_vehiculo.pdf', 'I');
?>
