<?php
// boleta20252.php - versión SQLSRV (sin ADODB
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ... resto de tu código
define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/');
require('fpdf/fpdf.php');



require_once('../../config/db.php');
date_default_timezone_set('America/Lima');

$conn = Conexion::conectar(); // debe devolver resource sqlsrv

class PDF extends FPDF
{
    function __construct($orientation = 'L', $unit = 'mm', $size = 'A5')
    {
        parent::__construct($orientation, $unit, $size);
    }

    function Header()
    {
        $this->Ln(1);
    }
}




function verMes($mes)
{
    $mesLetra = array(
        "01" => 'ENERO',
        "02" => 'FEBRERO',
        "03" => 'MARZO',
        "04" => 'ABRIL',
        "05" => 'MAYO',
        "06" => 'JUNIO',
        "07" => 'JULIO',
        "08" => 'AGOSTO',
        "09" => 'SEPTIEMBRE',
        "10" => 'OCTUBRE',
        "11" => 'NOVIEMBRE',
        "12" => 'DICIEMBRE'
    );
    return isset($mesLetra[$mes]) ? $mesLetra[$mes] : $mes;
}

// === INICIALIZACIÓN ===
$pdf = new PDF('L', 'mm', 'A5');

$anio = isset($_GET['anio']) ? $_GET['anio'] : '';
$mes = isset($_GET['mes']) ? $_GET['mes'] : '';
$tipotrabajador = isset($_GET['tipotrabajador']) ? $_GET['tipotrabajador'] : '';
$idplanillaauxiliar = isset($_GET['idplanillaauxiliar']) ? $_GET['idplanillaauxiliar'] : '';
$idcomponente = isset($_POST['idcomponente']) ? $_POST['idcomponente'] : (isset($_GET['idcomponente']) ? $_GET['idcomponente'] : '');
$idmeta = isset($_POST['idmeta']) ? $_POST['idmeta'] : (isset($_GET['idmeta']) ? $_GET['idmeta'] : '');
$idactividad = isset($_POST['idactividad']) ? $_POST['idactividad'] : (isset($_GET['idactividad']) ? $_GET['idactividad'] : '');
$idtrabajador = isset($_POST['idtrabajador']) ? $_POST['idtrabajador'] : (isset($_GET['idtrabajador']) ? $_GET['idtrabajador'] : '');
$numeroplanilla = isset($_GET['numeroplanilla']) ? $_GET['numeroplanilla'] : '';
$contrato = isset($_GET['contrato']) ? $_GET['contrato'] : '';
$dato = isset($_GET['dato']) ? $_GET['dato'] : '';

if ($idcomponente == '' or $idcomponente == 'Todos') $idcomponente = null;
if ($idmeta == '' or $idmeta == 'Todos') $idmeta = null;
if ($idactividad == '') $idactividad = null;
if ($idtrabajador == '') $idtrabajador = null;

// ==== CONSULTA CABECERA (SQLSRV) ====
$tsqlCab = "{CALL BD_PERSONAL.Planilla.pa_Boleta_Unica_Cabecera_Consultar_WEBSERVICE(?, ?, ?, ?, ?, ?, ?)}";
$paramsCab = array($anio, $mes, $idplanillaauxiliar, $tipotrabajador, $idtrabajador, $numeroplanilla, $contrato);
$stmtCab = sqlsrv_query($conn, $tsqlCab, $paramsCab);
if ($stmtCab === false) {
    die(print_r(sqlsrv_errors(), true));
}

// recorrer resultados de cabecera
while ($cab = sqlsrv_fetch_array($stmtCab, SQLSRV_FETCH_NUMERIC)) {

    // === nombre planilla (consulta separada) ===
    $tsqlP = "SELECT aux.PlanAuxi_Descripcion
              FROM BD_PERSONAL.Planilla.Tbl_Planilla_Auxiliar AS aux
              INNER JOIN BD_PERSONAL.Planilla.Tbl_Pago AS pa ON aux.Id_Planilla_Auxiliar = pa.Id_Planilla_Auxiliar
              WHERE aux.Id_Planilla_Auxiliar = ? AND pa.Id_Anio = ? AND pa.Id_Mes = ?";
    $paramsP = array($idplanillaauxiliar, $anio, $mes);
    $stmtP = sqlsrv_query($conn, $tsqlP, $paramsP);
    $nombre_planilla_desc = '';
    if ($stmtP !== false) {
        $rowP = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_NUMERIC);
        if ($rowP !== null && $rowP !== false) $nombre_planilla_desc = $rowP[0];
    }

    // === nombre trabajador / documento (consulta separada) ===
    $tsqlT = "SELECT RTRIM(LTRIM(Trab_documento)) FROM BD_PERSONAL.Escalafon.Tbl_Trabajador WHERE Id_Trabajador = ?";
    $paramsT = array($idtrabajador);
    $stmtT = sqlsrv_query($conn, $tsqlT, $paramsT);
    $nombre_planilla2_doc = '';
    if ($stmtT !== false) {
        $rowT = sqlsrv_fetch_array($stmtT, SQLSRV_FETCH_NUMERIC);
        if ($rowT !== null && $rowT !== false) $nombre_planilla2_doc = $rowT[0];
    }

    // Variables usadas en output filename later
    $tipotrabajador_nombre = isset($cab[19]) ? $cab[19] : '';

    // === DIBUJA PÁGINA ===
    $pdf->AddPage();

    // Rect normal en vez de RoundedRect
    $pdf->Rect(8, 15, 195, 120);

    $pdf->SetFont('helvetica', '', 6);
    $pdf->Text(13, 19, 'PROYECTO ESPECIAL CHAVIMOCHIC');
    $pdf->Text(13, 22, 'UNIDAD DE PERSONAL');
    $pdf->Text(13, 25, 'AREA DE REMUNERACIONES');

    // Imagenes (rutas relativas)
    if (file_exists('images/logoPECH.png')) {
        $pdf->Image('images/logoPECH.png', 162, 19, 35, 8, 'PNG', '');
    }

    // selector de vistos por año/mes (igual que tu lógica)
    if (($anio == '2021' && in_array($mes, ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'])) || ($anio == '2022' && $mes == '01')) {
        if (file_exists('images/visto3.jpg')) $pdf->Image('images/visto3.jpg', 160, 104, 28, 30, 'JPG', '');
        if (file_exists('images/visto4.jpg')) $pdf->Image('images/visto4.jpg', 120, 105, 32, 28, 'JPG', '');
    } elseif ($anio == '2020' && in_array($mes, ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'])) {
        if (file_exists('images/visto3.jpg')) $pdf->Image('images/visto3.jpg', 160, 104, 28, 30, 'JPG', '');
        if (file_exists('images/visto4.jpg')) $pdf->Image('images/visto4.jpg', 120, 105, 32, 28, 'JPG', '');
    } elseif (($anio == '2022' && in_array($mes, ['02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'])) || ($anio == '2021' && $mes == '11') || ($anio == '2023' && $mes == '01')) {
        if (file_exists('images/Visto5.jpg')) $pdf->Image('images/Visto5.jpg', 160, 104, 41, 30, 'JPG', '');
        if (file_exists('images/Visto6.jpg')) $pdf->Image('images/Visto6.jpg', 120, 105, 39, 28, 'JPG', '');
    } elseif ($anio == '2023' && in_array($mes, ['02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'])) {
        if (file_exists('images/Remuneraciones.jpg')) $pdf->Image('images/Remuneraciones.jpg', 160, 104, 35, 25, 'JPG', '');
        if (file_exists('images/JefePersonal.jpg')) $pdf->Image('images/JefePersonal.jpg', 120, 105, 32, 25, 'JPG', '');
    } elseif ($anio == '2024') {
        if (file_exists('images/Remuneraciones.jpg')) $pdf->Image('images/Remuneraciones.jpg', 160, 104, 35, 25, 'JPG', '');
        if (file_exists('images/VistoGary24.jpg')) $pdf->Image('images/VistoGary24.jpg', 110, 103, 44, 30, 'JPG', '');
    } elseif ($anio == '2025') {
        if (file_exists('images/Remuneraciones.jpg')) $pdf->Image('images/Remuneraciones.jpg', 160, 104, 35, 25, 'JPG', '');
        if (file_exists('images/VistoGary24.jpg')) $pdf->Image('images/VistoGary24.jpg', 110, 103, 44, 30, 'JPG', '');
    }

    // TITULOS Y DATOS
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Text(13, 33, 'Boleta de Pago');

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Text(80, 27, isset($cab[19]) ? $cab[19] : '');
    $pdf->Text(50, 33, $nombre_planilla_desc);
    $pdf->Text(110, 33, verMes($mes));
    $pdf->Text(140, 33, $anio);

    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Text(13, 40, 'Apellidos :');
    $pdf->Text(13, 46, 'Nombres  :');
    $pdf->Text(13, 56, 'DNI           :');
    $pdf->Text(13, 66, 'Actividad :');

    $pdf->Text(80, 40, 'Dias     :');
    $pdf->Text(105, 40, 'Categoria  :');
    $pdf->Text(80, 46, 'Cargo   :');
    $pdf->Text(80, 56, 'AFP      :');
    $pdf->Text(80, 62, 'Situac.  :');

    $pdf->Text(140, 40, 'Fec Ingreso  :');
    $pdf->Text(140, 46, 'Area  :');
    $pdf->Text(140, 56, 'Grupo  :');

    $pdf->SetFont('helvetica', '', 7);
    $pdf->Text(32, 40, isset($cab[28]) ? $cab[28] : '');
    $pdf->Text(32, 46, isset($cab[29]) ? $cab[29] : '');
    $pdf->Text(32, 56, isset($cab[4]) ? $cab[4] : '');
    $pdf->Text(32, 66, isset($cab[26]) ? $cab[26] : '');

    $pdf->Text(95, 40, isset($cab[24]) ? $cab[24] : '');
    $pdf->Text(125, 40, isset($cab[32]) ? $cab[32] : '');

    $pdf->SetXY(94, 44);
    $pdf->MultiCell(40, 3, isset($cab[30]) ? $cab[30] : '', 0);

    $pdf->SetXY(94, 54);
    $pdf->MultiCell(40, 3, isset($cab[34]) ? $cab[34] : '', 0);

    $pdf->Text(95, 62, isset($cab[35]) ? $cab[35] : '');

    $pdf->Text(160, 40, isset($cab[33]) ? $cab[33] : '');

    $pdf->SetXY(150, 44);
    $pdf->MultiCell(45, 3, isset($cab[27]) ? $cab[27] : '', 0);

    $pdf->Text(151, 56, isset($cab[31]) ? $cab[31] : '');

    // Líneas divisorias
    $pdf->Line(8, 69, 203, 69);
    $pdf->Line(60, 69, 60, 135);
    $pdf->Line(110, 69, 110, 135);
    $pdf->Line(155, 69, 155, 135);

    // === CONSULTA DETALLE ===
    $tsqlDet = "{CALL BD_PERSONAL.Planilla.pa_Boleta_Unica_Consultar_WEBSERVICE_2025(?, ?, ?, ?, ?, ?, ?, ?)}";
    $paramsDet = array($anio, $mes, $idplanillaauxiliar, $tipotrabajador, $idtrabajador, $numeroplanilla, $contrato, $dato);
    $stmtDet = sqlsrv_query($conn, $tsqlDet, $paramsDet);
    if ($stmtDet === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Text(12, 73, 'Remuneraciones');
    $pdf->Line(12, 73.4, 32, 73.4);
    $pdf->Text(62, 73, 'Descuentos de Ley');
    $pdf->Line(62, 73.4, 84, 73.4);
    $pdf->Line(112, 73.4, 134, 73.4);
    $pdf->Text(112, 73, 'Descuentos Varios');
    $pdf->Line(158, 73.5, 167, 73.5);
    $pdf->Text(158, 73, 'Totales');

    $pdf->SetFont('helvetica', '', 7);
    $pdf->Text(158, 79, 'Total Ingresos');
    $pdf->Text(158, 84, 'Total Egresos');
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Text(158, 89, 'Neto Pagar');
    $pdf->Line(185, 92, 203, 92);
    $pdf->Line(185, 93, 203, 93);

    $y = 71;
    $pdf->SetFont('helvetica', '', 7);

    while ($d = sqlsrv_fetch_array($stmtDet, SQLSRV_FETCH_NUMERIC)) {

        // TOTALES
        if (isset($d[11]) && $d[11] != '.00') {
            $pdf->SetXY(186, $y + 5);
            $pdf->Cell(15, 5, $d[11], 0, 0, 'R');
        }

        // REMUNERACIONES
        if (isset($d[4]) && $d[4] != '' && $d[4] != '.00') {
            $pdf->SetXY(12, $y + 5);
            $pdf->Cell(12, 5, $d[4], 0, 0, 'L');
            $pdf->SetXY(47, $y + 5);
            $pdf->Cell(12, 5, $d[5], 0, 2, 'R');
        }

        $pdf->SetXY(61, $y + 5);
        if (isset($d[6])) $pdf->Cell(12, 5, $d[6], 0, 0, 'L');

        // DESCUENTOS DE LEY
        if (isset($d[7]) && $d[7] != '' && $d[7] != '.00') {
            $pdf->SetXY(98, $y + 5);
            $pdf->Cell(12, 5, number_format($d[7], 2), 0, 2, 'R');
        }

        // DESCUENTOS VARIOS
        if (isset($d[8]) && $d[8] != '' && isset($d[9]) && $d[9] != '.00') {
            $pdf->SetXY(111, $y + 5);
            $pdf->Cell(12, 5, substr($d[8], 0, 20), 0, 0, 'L');
            $pdf->SetXY(143.5, $y + 5);
            $pdf->Cell(12, 5, $d[9], 0, 2, 'R');
        }

        $y += 3.7;
    }

    // fin while cabecera (seguirá si hay varias cabeceras)
}

// Salida final
// Construir nombre de archivo seguro (reemplazar espacios)
$nombre_planilla_file = isset($nombre_planilla_desc) ? preg_replace('/\s+/', '_', $nombre_planilla_desc) : 'Planilla';
$nombre_trab_file = isset($nombre_planilla2_doc) ? preg_replace('/\s+/', '_', $nombre_planilla2_doc) : '';

$outName = 'Boleta_' . $nombre_planilla_file . '_' . verMes($mes) . '_' . $anio . '_' . preg_replace('/\s+/', '_', $tipotrabajador_nombre) . '_' . $nombre_trab_file . '.pdf';
$pdf->Output($outName, 'I');
exit;
