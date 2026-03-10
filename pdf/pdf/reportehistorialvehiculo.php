<?php
require_once 'tcpdf_include.php';



require_once __DIR__ . "/../controllers/VehiculoController.php";
require_once __DIR__ . "/../models/VehiculoModel.php";

function safeText($text) {
    $text = trim($text ?? '');
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

$placa = $_GET['placa'] ?? '';
$fecha_consulta = date("d/m/Y H:i:s");

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();

// $logoPath = __DIR__ . '/../../img/logo_pech.png';
// if (file_exists($logoPath)) {
//     $pdf->Image($logoPath, 15, 10, 30);
// }

$logoPath = __DIR__ . '/images/logo_pech.png';

if (@getimagesize($logoPath)) {
    @$pdf->Image($logoPath, 16, 15, 48, 10);
}
$logoPat = __DIR__ . '/images/gobierno.png';

if (@getimagesize($logoPat)) {
    @$pdf->Image($logoPat, 230, 10, 55, 22);
}
// $pdf->SetY(28);
 $pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0, 31, 63);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, 'HISTORIAL DE VEHÍCULO', 0, 1, 'C', 0);
$pdf->Ln(5);


$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetDrawColor(0, 102, 102);
$pdf->SetLineWidth(0.7);
$pdf->Cell(0, 10, 'PLACA DEL VEHÍCULO: ' . safeText($placa), 1, 1, 'L');

$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'FECHA DEL REPORTE: ' . $fecha_consulta, 0, 1, 'L');
$pdf->Ln(3);

if (empty($placa)) {
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No se proporcionó ninguna placa para mostrar información.', 0, 1, 'C');
} else {
    $datos_vehiculo = ControladorVehiculo::ctrMostrarVehiculoReporteHistorial("placa", $placa);

    if (!$datos_vehiculo || count($datos_vehiculo) === 0) {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'No se encontró información para la placa ingresada.', 0, 1, 'C');
    } else {
        $marca = safeText($datos_vehiculo[0]['nombre_marca'] ?? '');
        $modelo = safeText($datos_vehiculo[0]['modelo'] ?? '');
        $color = safeText($datos_vehiculo[0]['color'] ?? '');

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'MARCA: ' . $marca, 0, 1, 'L');
        $pdf->Cell(0, 8, 'MODELO: ' . $modelo, 0, 1, 'L');
        $pdf->Cell(0, 8, 'COLOR: ' . $color, 0, 1, 'L');
        $pdf->Ln(3);

        $html = '
        <style>
            th { background-color: #d0e9c6; font-weight: bold; text-align: center; }
            td { padding: 4px; font-size: 10px; }
        </style>
        <table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Asignación</th>
                    <th>Nombre Conductor</th>
                    <th>Gerencia</th>
                    <th>Fecha de Asignación</th>
                    <th>Fecha de Devolución</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($datos_vehiculo as $row) {
            $fecha_asignacion = $row['fecha_asignacion'] instanceof DateTime
                ? $row['fecha_asignacion']->format('d/m/Y')
                : (!empty($row['fecha_asignacion']) ? date('d/m/Y', strtotime($row['fecha_asignacion'])) : '-');

            $fecha_devolucion = $row['fecha_devolucion'] instanceof DateTime
                ? $row['fecha_devolucion']->format('d/m/Y')
                : (!empty($row['fecha_devolucion']) ? date('d/m/Y', strtotime($row['fecha_devolucion'])) : '-');

            $trab_apellidos = safeText($row['Trab_apellidos'] ?? '');
            $trab_nombres = safeText($row['Trab_Nombres'] ?? '');
            $gerencia = safeText($row['gerencia'] ?? '');

            $html .= '<tr>
                <td align="center">' . safeText($row['id_asignacion']) . '</td>
                <td>' . $trab_apellidos . ' ' . $trab_nombres . '</td>
                <td>' . $gerencia . '</td>
                <td align="center">' . $fecha_asignacion . '</td>
                <td align="center">' . $fecha_devolucion . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
    }
}

$pdf->Output('historial_vehiculo.pdf', 'I');
?>
