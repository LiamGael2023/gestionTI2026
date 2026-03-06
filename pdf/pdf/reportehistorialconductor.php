<?php
require_once 'tcpdf_include.php';

require_once __DIR__ . '/../../controladores/transportes/conductor.controlador.php';
require_once __DIR__ . '/../../modelos/transportes/conductor.modelo.php';

function safeText($text) {
    $text = trim($text ?? '');
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

$id_Trabajador = $_GET['id'] ?? '';

$fecha_consulta = date("d/m/Y H:i:s");

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();

$logoPath = __DIR__ . '/images/logo_pech.png';

if (@getimagesize($logoPath)) {
    @$pdf->Image($logoPath, 16, 15, 48, 10);
}
$logoPat = __DIR__ . '/images/gobierno.png';

if (@getimagesize($logoPat)) {
    @$pdf->Image($logoPat, 230, 10, 55, 22);
}

$pdf->SetFillColor(255, 255, 255); 
$pdf->SetTextColor(0, 31, 63); 
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, 'HISTORIAL DE CONDUCTOR  ', 0, 1, 'C', 0);

$pdf->Ln(3);
$pdf->SetTextColor(0, 0, 0); 
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'FECHA DEL REPORTE: ' . $fecha_consulta, 0, 1, 'L');

$pdf->Ln(3);

if (empty($id_Trabajador)) {
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No se proporcionó ningún conductor para mostrar información.', 0, 1, 'C');
} else {
    $datos_conductor = ControladorConductor::ctrMostrarConductorReporteHistorial("id_trabajador", $id_Trabajador);

    if (!$datos_conductor || count($datos_conductor) == 0) {
      
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'No se encontró información para el conductor seleccionado.', 0, 1, 'C');
    } else {
       
        $nombre_conductor = safeText($datos_conductor[0]['conductor'] ?? '');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Conductor: ' . $nombre_conductor, 0, 1, 'L');
        $pdf->Ln(3);

        $html = '
        <style>
            th { background-color: #d0e9c6; font-weight: bold; text-align: center; }
            td { padding: 4px; font-size: 10px; }
        </style>
        <table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>N° Papeleta</th>
                    <th>Placa</th>
                    <th>Tipo Vehículo</th>
                    <th>Marca</th>
                    <th>Color</th>
                    <th>Modelo</th>
                    <th>Fecha Asignación</th>
                    <th>Fecha Devolución</th>
                    <th>Lugar</th>
                    
                </tr>
            </thead>
            <tbody>';

        foreach ($datos_conductor as $row) {
            $fecha_inicio = $row['fecha_inicio'] instanceof DateTime
                ? $row['fecha_inicio']->format('d/m/Y')
                : (!empty($row['fecha_inicio']) ? date('d/m/Y', strtotime($row['fecha_inicio'])) : '-');

            $fecha_fin = $row['fecha_fin'] instanceof DateTime
                ? $row['fecha_fin']->format('d/m/Y')
                : (!empty($row['fecha_fin']) ? date('d/m/Y', strtotime($row['fecha_fin'])) : '-');

            $html .= '<tr>
                <td align="center">' . safeText($row['id_papeleta']) . '</td>
                <td align="center">' . safeText($row['placa']) . '</td>
                <td>' . safeText($row['nombre_tipo']) . '</td>
                <td>' . safeText($row['marca']) . '</td>
                <td>' . safeText($row['color']) . '</td>
                <td>' . safeText($row['modelo']) . '</td>
                <td align="center">' . $fecha_inicio . '</td>
                <td align="center">' . $fecha_fin . '</td>
                <td align="center">' . safeText($row['lugar']) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
    }
}

$pdf->Output('historial_conductor.pdf', 'I');

?>
