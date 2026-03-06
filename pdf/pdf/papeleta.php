<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once 'tcpdf_include.php';
require_once('../../config/db.php');
require_once('../../modules/papeletas/controllers/PapeletasController.php');
require_once('../../modules/papeletas/models/PapeletasModel.php');

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

// === CACHÉ PDF ===
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
$pdfFile = "$cacheDir/papeleta_$id.pdf";

// Tiempo máximo de validez del caché (en segundos)
$cacheTTL = 24 * 60 * 60; // 24 horas

if (file_exists($pdfFile)) {
    $fileAge = time() - filemtime($pdfFile);
    if ($fileAge < $cacheTTL) {
        // Sirve desde caché
        header('Content-Type: application/pdf');
        readfile($pdfFile);
        exit;
    } else {
        // Elimina versión vieja
        unlink($pdfFile);
    }
}

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
$codigopapeleta = $row["id_papeleta"];
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
$pdf->SetTitle('Papeleta');
$pdf->SetMargins(0, 0, 0);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setFontSubsetting(true);
$pdf->SetFont('helvetica', '', 10);

// === PÁGINA 1 ===
$pdf->AddPage('L', [210, 148]);
$pdf->Image(__DIR__ . '/images/papeleta_1.png', 0, 0, 210, 148, 'PNG');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(175, 23); $pdf->Write(0, $codigopapeleta);
$pdf->SetXY(165, 39); $pdf->Write(0, $fechaP);
$pdf->SetXY(53, 39);  $pdf->Write(0, $nombrestrabajador);
$pdf->SetXY(53, 49);  $pdf->Write(0, $oficinatrabajador);
$pdf->SetXY(53, 59);  $pdf->Write(0, $concepto);
$pdf->SetXY(53, 70);
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(130, 5, $motivo, 0, 'L', false, 1);
$pdf->SetXY(22, 97); $pdf->Write(0, $hora_salida);
$pdf->SetXY(55, 97); $pdf->Write(0, $hora_llegada);
$pdf->SetXY(90, 97); $pdf->Write(0, $fechainicio);
$pdf->SetXY(122, 97); $pdf->Write(0, $fechafinal);
$pdf->SetXY(168, 95);
$pdf->MultiCell(30, 5, $lugar, 0, 'L', false, 1);

// === FIRMAS P1 ===
if ($firmas['trabajador'])     $pdf->Image($firmas['trabajador'], 18, 110, 20);
if ($firmas['jefe_inmediato']) $pdf->Image($firmas['jefe_inmediato'], 85, 110, 20);
if ($firmas['jefe_sede'])      $pdf->Image($firmas['jefe_sede'], 155, 110, 20);

// === PÁGINA 2 (si vehicular) ===
if ($es_salida_vehicular === 1) {
    $pdf->AddPage('L', [210, 148]);
    $pdf->Image(__DIR__ . '/images/papeleta_2.png', 0, 0, 210, 148, 'PNG');

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
    $pdf->SetXY(22, 90);  $pdf->Write(0, $hora_salida);
    $pdf->SetXY(55, 90);  $pdf->Write(0, $hora_llegada);
    $pdf->SetXY(90, 90);  $pdf->Write(0, $placa);
    $pdf->SetXY(122, 90); $pdf->Write(0, $kilometraje_inicial);
    $pdf->SetXY(167, 90); $pdf->Write(0, $kilometraje_final);

    if ($firmas['trabajador'])     $pdf->Image($firmas['trabajador'], 18, 105, 20);
    if ($firmas['jefe_inmediato']) $pdf->Image($firmas['jefe_inmediato'], 64, 105, 20);
    if ($firmas['jefe_sede'])      $pdf->Image($firmas['jefe_sede'], 107, 105, 20);
    // Firma del área de transportes fija
    $pdf->Image(__DIR__ . '/../perfil/Porras_Salceda_Juan.jpg', 160, 105, 20);
}

// === SALIDA FINAL ===
ob_clean();
$pdf->Output($pdfFile, 'F'); // guarda en caché
$pdf->Output('Papeleta.pdf', 'I'); // muestra al usuario
exit;
?>
