<?php

require_once '../libs/fpdf/fpdf.php';
require_once '../../models/CertificadosModel.php';
require_once '../../../config/database.php';

$model = new CertificadosModel();
$certificados = $model->obtenerCertificadosPorVencer2();

$pdf = new FPDF('L','mm','A4');
$pdf->AddPage();

$pdf->SetTextColor(0,0,0); // texto negro

$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'CERTIFICADOS POR VENCER',0,1,'C');

$pdf->SetFont('Arial','B',9);

$pdf->Cell(30,8,'DNI',1);
$pdf->Cell(70,8,'APELLIDOS Y NOMBRES',1);
$pdf->Cell(60,8,'DIRECCION LABORAL',1);
$pdf->Cell(60,8,'AREA',1);
$pdf->Cell(40,8,'CELULAR',1);
$pdf->Cell(80,8,'EMAIL',1);
$pdf->Cell(50,8,'TIPO CERTIFICADO',1);

$pdf->Ln();

$pdf->SetFont('Arial','',8);

foreach($certificados as $c){

$pdf->Cell(30,8,$c['dni'],1);
$pdf->Cell(70,8,$c['apellidos'].' '.$c['nombres'],1);
$pdf->Cell(60,8,'AV. FATIMA 431',1);
$pdf->Cell(60,8,$c['gerencia_laboral'],1);
$pdf->Cell(40,8,$c['telefono'],1);
$pdf->Cell(80,8,$c['correo'],1);
$pdf->Cell(50,8,$c['tipo_certificado'],1);

$pdf->Ln();

}

$pdf->Output('I','certificados_por_vencer.pdf');
exit;