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

 //echo '<pre>';
 //print_r($datos_papeleta);
 //echo '</pre>';
 //exit;
$row1 = $datos_papeleta;


$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


// $contrato = $conexion->Execute("select t1.Trab_Jefe_Inmediato
  //                                 from Escalafon.Tbl_Trabajador_APP t1 where t1.Id_Trabajador_APP = 1");
                                
    //                                $contrato2 = $conexion->Execute("select '665656'   from Escalafon.Tbl_Trabajador
      //                                 t1 where t1.Id_Trabajador = '".$contrato->fields[0]."'");
                                
                                 
                                
	
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('CHAVIMOCHIC');
$pdf->SetTitle('Papeleta');
$pdf->SetSubject('Papeleta');
$pdf->SetKeywords('PDF, PDF, Papeleta, chavimochic, PDF');

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(2,2,2);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

$pdf->setPrintFooter(false);


// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 0);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

$pdf->SetFont('helvetica', '', 48);

$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));


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


$pdf->AddPage('L', 'A5');


// Firma del trabajador
$fototrabajador = trim($row1["FirmaPersonal"] ?? '');
$imagenTra = '';
if (!empty($fototrabajador) && file_exists(__DIR__ . "/../perfil/" . $fototrabajador)) {
    $imagenTra = '<img src="../perfil/' . htmlspecialchars($fototrabajador) . '" style="text-align: center;" width="720">';
}

// Firma Jefe Inmediato
$firmajefe = trim($row1["FirmaJefe"] ?? '');
$estadoJI = trim($row1["estadoJI"] ?? '');
$imagenJI = '';
if ($estadoJI && !empty($firmajefe) && file_exists(__DIR__ . "/../perfil/" . $firmajefe)) {
    $imagenJI = '<img src="../perfil/' . htmlspecialchars($firmajefe) . '" style="text-align: center;" width="720">';
}

// Firma Jefe Personal / Sede
$firmajefesede = trim($row1["FirmaJefeSede"] ?? '');
$estadoJP = trim($row1["estadoJP"] ?? '');
$imagenJP = '';
if ($estadoJP && !empty($firmajefesede) && file_exists(__DIR__ . "/../perfil/" . $firmajefesede)) {
    $imagenJP = '<img src="../perfil/' . htmlspecialchars($firmajefesede) . '" style="text-align: center;" width="720">';
}


$html = '<table style="font-size:10px;" border="1">
                
                <tr >
                        <td style="text-align: center; font-size:14pt; width:730px;text-transform: uppercase;" valign="middle">
                        <img src="images/logochavisinfondo25.png" style="text-align: center;" border="0">
			</td>
                       
		</tr>
                <tr>
                        <td style="text-align: center; font-size:9pt; width:340px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>NOMBRES Y APELLIDOS</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:100px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>N° Papeleta</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:100px;height:30px;text-transform: uppercase;">
                            00'.$codigopapeleta.'
			</td>
                        <td style="text-align: center; font-size:9pt; width:100px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>Fecha</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:90px;height:30px;text-transform: uppercase;">
                            '.$fechaP.'
			</td>
		</tr>
                <tr>
                        <td rowspan="2" style="text-align: center; font-size:8.5pt; width:340px;height:30px;text-transform: uppercase;">
                           '.$nombrestrabajador.'
                               
			</td>
                        <td style="text-align: center; font-size:9pt; width:100px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>Hora Salida</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:100px;text-transform: uppercase;">
                            '.$hora_salida.'
			</td>
                        <td style="text-align: center; font-size:9pt; width:100px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>Hora Llegada</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:90px;text-transform: uppercase;">
                            '.$hora_llegada.'
			</td>
		</tr>
                <tr>
                        <td style="text-align: center; font-size:10pt; width:100px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>Hora Salida</b>
			</td>
                        <td style="text-align: center; font-size:10pt; width:100px;text-transform: uppercase;">
                            '.$hora_salida2.'
			</td>
                        <td style="text-align: center; font-size:10pt; width:100px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>Hora Llegada</b>
			</td>
                        <td style="text-align: center; font-size:10pt; width:90px;text-transform: uppercase;">
                            '.$hora_llegada2.'
			</td>
		</tr>
                <tr>
                        <td style="text-align: center; font-size:9pt; width:500px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>GERENCIA / OFICINA</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:230px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>FECHA DE PERMISO</b>
			</td>
                        
		</tr>
                <tr>
                        <td style="text-align: center; font-size:9pt; width:500px;height:30px;text-transform: uppercase;">
                           '.$gerenciatrabajador.' / '.$oficinatrabajador.'
			</td>
                        <td style="text-align: center; font-size:9pt; width:130px;height:30px;text-transform: uppercase;">
                            '.$fechainicio.'
			</td>
                        <td style="text-align: center; font-size:9pt; width:100px;height:30px;text-transform: uppercase;">
                            '.$fechafinal.'
			</td>
		</tr>
                <tr>
                        <td style="text-align: center; font-size:8pt; width:365px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>MOTIVO DE LA SALIDA</b>
			</td>
                        <td style="text-align: center; font-size:8pt; width:365px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>MOTIVO DE LA SALIDA (ESPECIFICAR)</b>
			</td>
		</tr>
                <tr>
                        <td style="text-align: center; font-size:8pt; width:365px;height:30px;text-transform: uppercase;">
                            '.$concepto.'
			</td>
                        <td style="text-align: center; font-size:8pt; width:365px;height:30px;text-transform: uppercase;">
                            '.$motivo.'
			</td>
		</tr>
                <tr>
                        <td style="text-align: center; font-size:8pt; width:250px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>LUGAR DE DESTINO</b>
			</td>
                        <td style="text-align: center; font-size:7pt; width:200px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>NOMBRE Y APELLIDO DEL FUNCIONARIO DE LA ENTIDAD DE DESTINO</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:280px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>OBSERVACION</b>
			</td>
		</tr>
                <tr>
                        <td style="text-align: center; font-size:8pt; width:250px;height:30px;text-transform: uppercase;">
                           '.$lugar.'
			</td>
                        <td style="text-align: center; font-size:8pt; width:200px;height:30px;text-transform: uppercase;">
                            '.$destino.'
			</td>
                        <td style="text-align: center; font-size:6.8pt; width:280px;height:30px;text-transform: uppercase;">
                            '.$observacion.'
			</td>
		</tr>
                <tr>
                        <td style="text-align: center; font-size:9pt; width:515px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>FIRMAS DE LOS QUE INTERVIENEN</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:215px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                            <b>TIEMPO DE ATENCIÒN</b>
			</td>
                        
		</tr>
                
                <tr>
                        <td rowspan="2" style="text-align: center; font-size:9pt; width:111px;text-transform: uppercase;">
                           <br><br>'.$imagenTra.'
			</td>
                        <td rowspan="2" style="text-align: center; font-size:9pt; width:111px;text-transform: uppercase;">
                           <br><br>'.$imagenJI.'
			</td>
                        <td rowspan="2" style="text-align: center; font-size:9pt; width:111px;text-transform: uppercase;">
                           <br><br>'.$imagenJP.'
			</td>
                        <td rowspan="2" style="text-align: center; font-size:9pt; width:182px;text-transform: uppercase;">
                            
			</td>
                        <td style="text-align: center; font-size:9pt; width:108px;height:20px;text-transform: uppercase;">
                            <b>INICIO</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:107px;height:20px;text-transform: uppercase;">
                            <b>TERMINO</b>
			</td>
		</tr>

                <tr>
                        
                        <td style="text-align: center; font-size:9pt; width:108px;height:100px;text-transform: uppercase;">
                           
			</td>
                        <td style="text-align: center; font-size:9pt; width:107px;height:100px;text-transform: uppercase;">
                            
			</td>
		</tr>
                
                <tr>
                        <td style="text-align: center; font-size:9pt; width:111px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>Firma Solicitante</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:111px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>Jefe Inmediato</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:111px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>Unidad Personal</b>
			</td>
                        <td style="text-align: center; font-size:9pt; width:182px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>Sello y Firma (Entidad Destino)</b>
			</td>
                        <td style="text-align: center; font-size:8.5pt; width:215px;height:30px;text-transform: uppercase;background-color:#E4E4E3;">
                           <b>Llenado por quien atendio al Colaborador</b>
			</td>
		</tr>
	</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        
// ---------------------------------------------------------
//echo $html;
//exit;


if ($es_salida_vehicular === 1) {
    
        $pdf->AddPage('L', 'A5');
    
        
    
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
        $firmaTransportes = trim($row1["FirmaTransporte"] ?? '');
    
        
        $estadoSolicitanteJI = trim($row1["estadoJI"] ?? '');
        $estadoUsBegOusAsg = trim($row1["estadoJP"] ?? '');
        $estadoTransportes = trim($row1["estadoTransporte"] ?? '');
    
        
        function getFirmaImg($firma, $estado) {
            if ($estado && !empty($firma) && file_exists(__DIR__ . "/../perfil/" . $firma)) {
                return '<img src="../perfil/' . htmlspecialchars($firma) . '" style="text-align: center;" width="100">';
            }
            return '';
        }
    
        $imgFirmaSolicitanteJI = getFirmaImg($firmaSolicitanteJI, $estadoSolicitanteJI);
        $imgFirmaUsBegOusAsg = getFirmaImg($firmaUsBegOusAsg, $estadoUsBegOusAsg);
        $imgFirmaTransportes = getFirmaImg($firmaTransportes, $estadoTransportes);
    
$htmlVehicular = '
<table style="font-size:10px;" border="1">
    <tr>
        <td style="text-align: center; font-size:14pt; width:730px;" valign="middle">
            <img src="images/logo_pech_vehicular.png" style="text-align: center;" border="0">
        </td>
    </tr>

    <!-- Cabecera -->
    <tr>
        <td style="text-align: center; font-size:9pt; width:340px;height:30px;background-color:#E4E4E3;"><b>NOMBRES Y APELLIDOS</b></td>
        <td style="text-align: center; font-size:9pt; width:100px;height:30px;background-color:#E4E4E3;"><b>N° Papeleta</b></td>
        <td style="text-align: center; font-size:9pt; width:100px;height:30px;">00' . $codigopapeleta . '</td>
        <td style="text-align: center; font-size:9pt; width:100px;height:30px;background-color:#E4E4E3;"><b>Fecha</b></td>
        <td style="text-align: center; font-size:9pt; width:90px;height:30px;">' . $fecha . '</td>
    </tr>

    <!-- Datos -->
    <tr>
        <td style="text-align: center; font-size:8.5pt; width:340px;height:30px;">' . $nombre_conductor . '</td>
        <td style="text-align: center; font-size:9pt; width:100px;background-color:#E4E4E3;"><b>Hora Salida</b></td>
        <td style="text-align: center; font-size:9pt; width:100px;">' . $hora_salida . '</td>
        <td style="text-align: center; font-size:9pt; width:100px;background-color:#E4E4E3;"><b>Hora Llegada</b></td>
        <td style="text-align: center; font-size:9pt; width:90px;">' . $hora_llegada . '</td>
    </tr>

    <!-- MOTIVO Y PLACA - TITULOS -->
    <tr>
        <td style="text-align: center; font-size:9pt; height:30px;background-color:#E4E4E3;"><b>MOTIVO DE LA SALIDA</b></td>
        <td style="text-align: center; font-size:9pt; background-color:#E4E4E3;"><b>PLACA</b></td>
        <td style="text-align: center; font-size:9pt; background-color:#E4E4E3;"><b>KILOMETRAJE INICIAL</b></td>
        <td style="text-align: center; font-size:9pt; background-color:#E4E4E3;"><b>KILOMETRAJE FINAL</b></td>
        <td></td>
    </tr>

    <!-- MOTIVO Y PLACA - VALORES -->
    <tr>
        <td style="text-align: center; font-size:8pt; height:30px;">' . $motivo . '</td>
        <td style="text-align: center; font-size:8pt;">' . $placa . '</td>
        <td style="text-align: center; font-size:8pt;">' . $kilometraje_inicial . '</td>
        <td style="text-align: center; font-size:8pt;">' . $kilometraje_final . '</td>
        <td></td>
    </tr>

    <!-- Firmas -->
    <tr>
        <td style="text-align: center; font-size:9pt;"><br>' . $imgFirmaSolicitanteJI . '</td>
        <td style="text-align: center; font-size:9pt;"><br>' . $imgFirmaTransportes . '</td>
        <td style="text-align: center; font-size:9pt;"><br>' . $imgFirmaUsBegOusAsg . '</td>
        <td colspan="2" style="text-align: center; font-size:9pt;"><br>' . $imgFirmaTransportes . '</td>
    </tr>
    <tr>
        <td style="text-align: center; font-size:9pt; background-color:#E4E4E3;"><b>Solicitante</b></td>
        <td style="text-align: center; font-size:9pt; background-color:#E4E4E3;"><b>Jefe Inmediato</b></td>
        <td style="text-align: center; font-size:9pt; background-color:#E4E4E3;"><b>Subgerencia / UASAG</b></td>
        <td colspan="2" style="text-align: center; font-size:9pt; background-color:#E4E4E3;"><b>Transportes</b></td>
    </tr>
</table>';



    
        $pdf->writeHTML($htmlVehicular, true, false, true, false, '');
    }
    
    


// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('Papeleta.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+