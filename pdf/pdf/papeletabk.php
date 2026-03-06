<?php
require_once 'tcpdf_include.php';

require_once __DIR__ . '/../../controladores/personal/papeleta.controlador.php';
require_once __DIR__ . '/../../modelos/personal/papeleta.modelo.php';

if (!isset($_GET['id'])) {
        die('Faltan parámetros');
    }
    
    $id = intval($_GET['id']);
    
 
    $papeleta = ControladorPapeleta::ctrMostrarPapeletaReporte("id_papeleta", $id);
    if (!$papeleta || count($papeleta) == 0) {
        die("No se encontró la papeleta.");
    }
    

    
    $row = $papeleta[0];
    $es_salida_vehicular = intval($row['es_salida_vehicular']);
    

$datos_papeleta = $row; 


if (!isset($datos_papeleta)) {
    die("No se encontró la papeleta.");
}

$row1 = $datos_papeleta;



function safeText($text) {
        $text = trim($text ?? '');
        
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    
$codigopapeleta = $row1["id_papeleta"];

 
                
                                                    
$fechaP = $row1["fecha"] instanceof DateTime ? $row1["fecha"]->format("d/m/Y") : '';


$fechainicio = $row1["fecha_inicio"] instanceof DateTime ? $row1["fecha_inicio"]->format("d/m/Y") : '';
$fechafinal  = $row1["fecha_fin"] instanceof DateTime ? $row1["fecha_fin"]->format("d/m/Y") : '';


// diferencia de fechas
date_default_timezone_set('America/Lima');		

$fechaInicioTrabajo = $row1["fecha_inicio"];

$fechafinT=$row1["fecha_fin"];
$fechaFinTrabajo = $row1["fecha_fin"];

$mesfinT = $fechafinT->format("m");
$aniofinT = $fechafinT->format("Y");

if ($aniofinT == "1900" || $aniofinT == "-0001") {
    $fecha_actual = new DateTime();
    $fechaFinTrabajo = $fecha_actual;
}

$intervalo = $fechaInicioTrabajo->diff($fechaFinTrabajo);


//$nombresJefe = $contrato2->fields[0];



$nombrestrabajador = safeText($row1["nombres"]);
$oficinatrabajador = safeText($row1["oficina"]);
$gerenciatrabajador = safeText($row1["gerencia"]);
$observacion = $row1["observacion"];


$concepto = safeText($row1["Id_Trabajador_Concepto_APP"]);
$motivo = safeText($row1["Id_Trabajador_Motivo_APP"]);
$lugar = safeText($row1["Id_Trabajador_Lugar_APP"]);
$destino = safeText($row1["destinatario"]);
$observacion = $row1["observacion"];

$hora_salida = $row1["hora_salida"] instanceof DateTime ? $row1["hora_salida"]->format("H:i") : '';
$hora_llegada = $row1["hora_llegada"] instanceof DateTime ? $row1["hora_llegada"]->format("H:i") : '';
$hora_salida2 = $row1["hora_salida"] instanceof DateTime ? $row1["hora_salida"]->format("H:i") : '';
$hora_llegada2 = $row1["hora_llegada"] instanceof DateTime ? $row1["hora_llegada"]->format("H:i") : '';





// Firma del trabajador
$fototrabajador = trim($row1["FirmaPersonal"] ?? '');
$imagenTra = '';
$rutaImagenTra = ''; 
if (!empty($fototrabajador) && file_exists(__DIR__ . "/../perfil/" . $fototrabajador)) {
    $imagenFilename = htmlspecialchars($fototrabajador); 
    $imagenTra = '<img src="../perfil/' . $imagenFilename . '" style="text-align: center;" width="720">';
    $rutaImagenTra = __DIR__ . "/../perfil/" . $fototrabajador; 
}

// Firma Jefe Inmediato
$firmajefe = trim($row1["FirmaJefe"] ?? '');
$estadoJI = trim($row1["estadoJI"] ?? '');
$imagenJI = '';
$rutaImagenJI = '';
if ($estadoJI && !empty($firmajefe) && file_exists(__DIR__ . "/../perfil/" . $firmajefe)) {
    $imagenJI = '<img src="../perfil/' . htmlspecialchars($firmajefe) . '" style="text-align: center;" width="720">';
    $rutaImagenJI = __DIR__ . "/../perfil/" . $firmajefe; 
}

// Firma Jefe Personal / Sede
$firmajefesede = trim($row1["FirmaJefeSede"] ?? '');
$estadoJP = trim($row1["estadoJP"] ?? '');
$imagenJP = '';
$rutaImagenJP = ''; 
if ($estadoJP && !empty($firmajefesede) && file_exists(__DIR__ . "/../perfil/" . $firmajefesede)) {
    $imagenJP = '<img src="../perfil/' . htmlspecialchars($firmajefesede) . '" style="text-align: center;" width="720">';
    $rutaImagenJP = __DIR__ . "/../perfil/" . $firmajefesede;
}




$pdf = new TCPDF('L', 'mm', [210, 148], true, 'UTF-8', false);


$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('CHAVIMOCHIC');
$pdf->SetTitle('Papeleta');
$pdf->SetSubject('Papeleta');


$pdf->SetMargins(0, 0, 0);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);


$pdf->SetAutoPageBreak(false, 0);


$pdf->SetFont('helvetica', '', 10);


$pdf->AddPage('L', [210, 148]);


$imgPath = __DIR__ . '/images/papeleta_1.png';



$pdf->Image($imgPath, 0, 0, 210, 148, 'PNG');  


$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(175, 23);
$pdf->Write(0, $codigopapeleta );

$pdf->SetXY(165, 39);
$pdf->Write(0, $fechaP );

$pdf->SetXY(53, 39);
$pdf->Write(0, $nombrestrabajador );

$pdf->SetXY(53, 49);
$pdf->Write(0, $oficinatrabajador );
$pdf->SetXY(53, 59);
$pdf->Write(0, $concepto );


$pdf->SetXY(53, 70); 
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(130, 5, $motivo, 0, 'L', false, 1); 

$pdf->SetXY(22, 97);
$pdf->Write(0, $hora_salida );

$pdf->SetXY(55, 97);
$pdf->Write(0, $hora_llegada );

$pdf->SetXY(90, 97);
$pdf->Write(0, $fechainicio );

$pdf->SetXY(122, 97);
$pdf->Write(0, $fechafinal );

$pdf->SetXY(168, 95); 
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(30, 5, $lugar, 0, 'L', false, 1); 


 if (!empty($rutaImagenTra)) {
    $pdf->SetXY(18, 110); 
    $pdf->Image($rutaImagenTra, 18, 110, 20); 
} 

if (!empty($rutaImagenJI)) {
    $pdf->SetXY(85, 110); 
    $pdf->Image($rutaImagenJI, 85, 110, 20); 
} 

if (!empty($rutaImagenJP)) {
    $pdf->SetXY(155, 110); 
    $pdf->Image($rutaImagenJP, 155, 110, 20); 
} 






if ($es_salida_vehicular === 1) {
    
       
    
        
    $codigopapeleta = $row1["Id_PapeletaVehicular"];
        $nombre_conductor = safeText($datos_papeleta["nombres"] ?? '');
        $placa = safeText($datos_papeleta["placa"] ?? '');
        $kilometraje_inicial = safeText($datos_papeleta["kilometraje_inicial"] ?? '');
        $kilometraje_final = safeText($datos_papeleta["kilometraje_final"] ?? '');
        $hora_salida = ($datos_papeleta["hora_salida"] instanceof DateTime) 
            ? $datos_papeleta["hora_salida"]->format("H:i") 
            : '';
        $hora_llegada = ($datos_papeleta["hora_llegada"] instanceof DateTime) 
            ? $datos_papeleta["hora_llegada"]->format("H:i") 
            : '';
        $fecha = ($datos_papeleta["fecha"] instanceof DateTime) 
            ? $datos_papeleta["fecha"]->format("d/m/Y") 
            : '';
    

        $firmaSolicitanteJI = trim($row1["FirmaJefe"] ?? '');
        $firmaUsBegOusAsg = trim($row1["FirmaJefeSede"] ?? '');
        //$firmaTransportes = trim($row1["firma_transportes"] ?? '');
    
        
        $estadoSolicitanteJI = trim($row1["estadoJI"] ?? '');
        $estadoUsBegOusAsg = trim($row1["estadoJP"] ?? '');
        //$estadoTransportes = trim($row1["estado_transportes"] ?? '');
    
        
        function getFirmaImg($firma, $estado) {
            if ($estado && !empty($firma) && file_exists(__DIR__ . "/../perfil/" . $firma)) {
                return '<img src="../perfil/' . htmlspecialchars($firma) . '" style="text-align: center;" width="100">';
            }
            return '';
        }
        // Firma Jefe Inmediato
            $firmajefe = trim($row1["FirmaJefe"] ?? '');
            $estadoJI = trim($row1["estadoJI"] ?? '');
            $imagenJI = '';
            $rutaImagenJI = '';
            if ($estadoJI && !empty($firmajefe) && file_exists(__DIR__ . "/../perfil/" . $firmajefe)) {
                $imagenJI = '<img src="../perfil/' . htmlspecialchars($firmajefe) . '" style="text-align: center;" width="720">';
                $rutaImagenJI = __DIR__ . "/../perfil/" . $firmajefe; 
            }

            $firmaTransportes = trim($firmaTransportes ?? '');
            $estadoTransportes = trim($estadoTransportes ?? '');
            $imagenTransportes = '';
            $rutaImagenTrans = '';

            if ($estadoTransportes && !empty($firmaTransportes) && file_exists(__DIR__ . "/../perfil/" . $firmaTransportes)) {
                $imagenTransportes = '<img src="../perfil/' . htmlspecialchars($firmaTransportes) . '" style="text-align: center;" width="720">';
                $rutaImagenTrans = __DIR__ . "/../perfil/" . $firmaTransportes;
            }
            $firmaUsBegOusAsg = trim($firmaUsBegOusAsg ?? '');
            $estadoUsBegOusAsg = trim($estadoUsBegOusAsg ?? '');
            $imagenUsBegOusAsg = '';
            $rutaImagenUsBegOusAsg = '';

            if ($estadoUsBegOusAsg && !empty($firmaUsBegOusAsg) && file_exists(__DIR__ . "/../perfil/" . $firmaUsBegOusAsg)) {
                $imagenUsBegOusAsg = '<img src="../perfil/' . htmlspecialchars($firmaUsBegOusAsg) . '" style="text-align: center;" width="720">';
                $rutaImagenUsBegOusAsg = __DIR__ . "/../perfil/" . $firmaUsBegOusAsg;
            }
    
        $imgFirmaSolicitanteJI = getFirmaImg($firmaSolicitanteJI, $estadoSolicitanteJI);
        $imgFirmaUsBegOusAsg = getFirmaImg($firmaUsBegOusAsg, $estadoUsBegOusAsg);
        $imgFirmaTransportes = getFirmaImg($firmaTransportes, $estadoTransportes);
    

        $pdf->AddPage('L', [210, 148]);
        $pdf->Image(__DIR__ . '/images/papeleta_2.png', 0, 0, 210, 148, 'PNG'); 
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);

    $pdf->SetXY(172, 24);
    $pdf->Write(0, '00' . $codigopapeleta);

 $pdf->SetXY(165, 39);
    $pdf->Write(0, $fecha);
    
    $pdf->SetXY(53, 39);
    $pdf->Write(0, $nombre_conductor );

    
    $pdf->SetXY(53, 48);
    $pdf->Write(0, $oficinatrabajador );

    $pdf->SetXY(53, 65);
    $pdf->Write(0, $motivo );

    $pdf->SetXY(22, 90);
    $pdf->Write(0, $hora_salida );

    $pdf->SetXY(55, 90);
    $pdf->Write(0, $hora_llegada );

    $pdf->SetXY(90, 90);
    $pdf->Write(0, $placa);

    $pdf->SetXY(122, 90);
    $pdf->Write(0, $kilometraje_inicial );

    $pdf->SetXY(167, 90);
    $pdf->Write(0, $kilometraje_final );

  
    
    if (!empty($rutaImagenJI)) {
    $pdf->SetXY(64, 105); 
    $pdf->Image($rutaImagenJI, 64, 105, 20); 
    } 
    if (!empty($rutaImagenTra)) {
    $pdf->SetXY(18, 105); 
    $pdf->Image($rutaImagenTra, 18, 105, 20); 
    } 
    $pdf->SetXY(160, 105); 
$pdf->Image(__DIR__ . '/../perfil/Porras_Salceda_Juan.jpg', 160, 105, 20); 

    if (!empty($rutaImagenUsBegOusAsg)) {
    $pdf->SetXY(107, 105); 
    $pdf->Image($rutaImagenUsBegOusAsg, 107, 105, 20); 
    }
    

}   
    


// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('Papeleta.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

