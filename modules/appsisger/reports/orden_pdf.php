<?php

require_once __DIR__ . '/../TCPDF-main/tcpdf.php';


function labelValue($pdf, $x, $y, $label, $value, $w1=30, $w2=30){
    $pdf->SetXY($x, $y);

    $pdf->SetFont('helvetica','B',8);
    $pdf->Cell($w1,5,$label,0,0);

    $pdf->SetFont('helvetica','',8);
    $pdf->Cell($w2,5,$value,0,1);
}


function generarPDFOrden($d){

    $pdf = new TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);

    $pdf->SetCreator('SISGER');
    $pdf->SetAuthor('SISGER');
    $pdf->SetTitle('Orden de Riego');

    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();

    $pdf->StartTransform();
    $pdf->SetAlpha(0.25);
    $pdf->Image(__DIR__.'/../images/chavimochicfondo.jpg', 10, 8, 148, 210, '', '', '', false, 300, '', false, false, 0);
     $pdf->SetAlpha(1);
    $pdf->StopTransform();


  
    $pdf->Rect(8, 8, 132, 182);

   
    $pdf->Image(__DIR__.'/../images/logo_pech.png', 15, 15, 30);
    $pdf->Image(__DIR__.'/../images/logo_pech.png', 105, 15, 30);


    $pdf->SetFont('helvetica','B',11);
    $pdf->SetXY(0,15);
    $pdf->Cell(0,8,'ORDEN DE RIEGO',0,1,'C');


    $pdf->SetFont('helvetica','B',9);
    $pdf->SetXY(10, 25);
    $pdf->Cell(0,5,'PERIODO',0,1);

    labelValue($pdf, 10, 30, 'Del :', $d["fechaInicioPeriodo"], 20, 35);
    labelValue($pdf, 10, 35, 'Al :', $d["fechaFinalPeriodo"], 20, 35);

    labelValue($pdf, 70, 30, 'Nro Req.:', $d["nroRequerimiento"], 30, 30);
    labelValue($pdf, 70, 35, 'Sector:', $d["sectorRiego"], 30, 30);

  
    $pdf->SetFont('helvetica','B',10);
    $pdf->SetXY(10, 40);
    $pdf->Cell(0,5,$d["usuario"],0,1,'C');


    labelValue($pdf, 10, 50, 'U.C.:', $d["UC"], 25, 35);
    labelValue($pdf, 70, 50, 'F. Venta:', $d["fechaemision"], 30, 30);

    labelValue($pdf, 10, 57, 'Canal Deriv.:', $d["canalDerivacion"], 25, 35);
    labelValue($pdf, 70, 57, 'Canal Riego:', $d["canalRiego"], 30, 30);

    labelValue($pdf, 10, 64, 'Caudal Neto:', $d["caudalNeto"].' l/s', 30, 30);
    labelValue($pdf, 70, 64, 'Volumen:', $d["volumenSolcitado"].' m3', 30, 30);

   
    $pdf->SetFont('helvetica','B',8);
    $pdf->SetXY(10, 75);
    $pdf->Cell(30,5,'Fecha Inicio:',0,1);

    $pdf->SetFont('helvetica','B',8);
    $pdf->SetX(10);
    $pdf->Cell(60,5,$d["fechaInicioRiego"],0,1);

    $pdf->SetFont('helvetica','B',8);
    $pdf->SetXY(70, 75);
    $pdf->Cell(30,5,'Fecha Fin:',0,1);

    $pdf->SetFont('helvetica','B',8);
    $pdf->SetX(70);
    $pdf->Cell(60,5,$d["fechaFinRiego"],0,1);


    labelValue($pdf, 10, 90, 'Hora Inicio:', $d["horaInicioRiego"]);
    labelValue($pdf, 70, 90, 'Hora Fin:', $d["horaFinalRiego"]);


    labelValue($pdf, 10, 100, 'Tiempo Riego:', $d["tiempoRiego"].' h');
    labelValue($pdf, 70, 100, 'Tiempo Recorrido:', $d["recorrido"].' h');

    labelValue($pdf, 10, 110, 'Cultivo:', $d["cultivo"]);
    labelValue($pdf, 70, 110, 'Area:', $d["areaRegar"].' ha');


    $pdf->SetXY(10, 125);
    $pdf->SetFont('helvetica','B',9);
    $pdf->Cell(0,5,'Despues de: '.$d["despuesde"],0,1);

    $pdf->SetXY(15, 170);
    $pdf->Cell(50,5,'_________________',0,0,'C');

    $pdf->SetXY(75, 170);
    $pdf->Cell(50,5,'_________________',0,1,'C');

    $pdf->SetXY(15, 175);
    $pdf->Cell(50,5,'Usuario',0,0,'C');

    $pdf->SetXY(75, 175);
    $pdf->Cell(50,5,'Comision',0,1,'C');

    return $pdf;
}