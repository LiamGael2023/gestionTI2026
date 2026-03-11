<?php
ob_start();


require_once 'tcpdf_include.php';
require_once('../../modules/transportes/controllers/PapeletaVehicularController.php');
require_once('../../modules/papeletas/controllers/PapeletasController.php');
require_once('../../modules/transportes/models/PapeletaVehicularModel.php');

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

// === FUNCIONES ===
function safeText($text) {
    $text = trim($text ?? '');
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function loadImagePath($file, $estado = true) {
    static $cache = [];
    $path = __DIR__ . "/../perfil/" . trim($file);
    if (!$file || !$estado) return null;
    if (!isset($cache[$path])) $cache[$path] = file_exists($path);
    return $cache[$path] ? $path : null;
}

// === DATOS PRINCIPALES ===
$codigopapeleta = $row["Id_PapeletaVehicular"];
$fechaP = $row["fecha"] instanceof DateTime ? $row["fecha"]->format("d/m/Y") : '';
$fechainicio = $row["fecha_inicio"] instanceof DateTime ? $row["fecha_inicio"]->format("d/m/Y") : '';
$fechafinal  = $row["fecha_fin"] instanceof DateTime ? $row["fecha_fin"]->format("d/m/Y") : '';

$nombrestrabajador = safeText($row["nombres"]);
$oficinatrabajador = safeText($row["oficina"]);
$concepto = safeText($row["Id_Trabajador_Concepto_APP"]);
$motivo = safeText($row["Id_Trabajador_Motivo_APP"]);
$lugar = safeText($row["Id_Trabajador_Lugar_APP"]);
$hora_salida = $row["hora_salida"] instanceof DateTime ? $row["hora_salida"]->format("H:i") : '';
$hora_llegada = $row["hora_llegada"] instanceof DateTime ? $row["hora_llegada"]->format("H:i") : '';

// === FIRMAS ===
$firmas = [
    'trabajador'      => loadImagePath($row["FirmaPersonal"] ?? ''),
    'jefe_inmediato'  => loadImagePath($row["FirmaJefe"] ?? '', $row["estadoJI"] ?? ''),
    'jefe_sede'       => loadImagePath($row["FirmaJefeSede"] ?? '', $row["estadoJP"] ?? ''),
];

// === CONFIG TCPDF ===
$pdf = new TCPDF('L', 'mm', [210, 148], true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('CHAVIMOCHIC');
$pdf->SetTitle('Papeleta Vehicular');
$pdf->SetMargins(0, 0, 0);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setFontSubsetting(true);
$pdf->SetFont('helvetica', '', 10);

    $pdf->AddPage('L', [210, 148]);
    $pdf->Image(__DIR__ . '/images/papeleta_1.png', 0, 0, 210, 148, 'PNG');

    $placa = safeText($row["placa"] ?? '');
    $kilometraje_inicial = safeText($row["kilometraje_inicial"] ?? '');
    $kilometraje_final   = safeText($row["kilometraje_final"] ?? '');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(172, 24); $pdf->Write(0, '00' . $codigopapeleta);
    $pdf->SetXY(165, 39); $pdf->Write(0, $fechaP);
    $pdf->SetXY(53, 39);  $pdf->Write(0, $nombrestrabajador);
    $pdf->SetXY(53, 48);  $pdf->Write(0, $oficinatrabajador);
    $pdf->SetXY(53, 65);  $pdf->Write(0, $motivo);
    $pdf->SetXY(22, 95);  $pdf->Write(0, $hora_salida);
    $pdf->SetXY(55, 95);  $pdf->Write(0, $hora_llegada);
    $pdf->SetXY(90, 95);  $pdf->Write(0, $placa);
    $pdf->SetXY(122, 95); $pdf->Write(0, $kilometraje_inicial);
    $pdf->SetXY(167, 95); $pdf->Write(0, $kilometraje_final);

    if ($firmas['trabajador'])     $pdf->Image($firmas['trabajador'], 18, 108, 20);
    if ($firmas['jefe_inmediato']) $pdf->Image($firmas['jefe_inmediato'], 64, 108, 20);
    if ($firmas['jefe_sede'])      $pdf->Image($firmas['jefe_sede'], 107, 108, 20);
    // Firma del área de transportes fija
    $pdf->Image(__DIR__ . '/../perfil/Porras_Salceda_Juan.jpg', 160, 108, 20);


// === SALIDA FINAL ===
ob_clean();
$pdf->Output('Papeleta-Vehicular.pdf', 'I'); // muestra al usuario
exit;
?>
