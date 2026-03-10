<?php

require_once('tcpdf_include.php');
require_once('../../config/db.php');
require_once('../../modules/transportes/controllers/VehiculoController.php');
require_once('../../modules/transportes/models/VehiculoModel.php');


$vehiculos = ControladorVehiculo::ctrMostrarReporteVehiculos(null, null);


$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);


$pdf->SetCreator('TCPDF');
$pdf->SetAuthor('Transportes');
$pdf->SetTitle('Listado de Vehículos Asignados');
$pdf->SetSubject('Reporte');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10, true);
$pdf->SetFont('helvetica', '', 9);
$pdf->AddPage();


$logoPath = __DIR__ . '/images/logo_pech.png';

if (@getimagesize($logoPath)) {
    @$pdf->Image($logoPath, 10, 10, 50, 12);
}

$logoPat = __DIR__ . '/images/gobierno.png';

if (@getimagesize($logoPat)) {
    @$pdf->Image($logoPat, 230, 4, 55, 22);
}

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(0, 10);
$pdf->Cell(0, 10, 'LISTADO DE VEHÍCULOS ', 0, 1, 'C');


$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(-60, 25);
$pdf->Cell(0, 10, 'Generado: ' . date('Y/m/d H:i:s'), 0, 1, 'R');

$pdf->Ln(5);


$html = <<<EOD
<style>
    table {
        border-collapse: collapse;
        width: 100%;
    }
    thead {
        background-color: #d4e3fa;
    }
    th {
        background-color: #007bff;
        color: #fff;
        border: 1px solid #000;
        font-weight: bold;
        padding: 5px;
        text-align: center;
    }
    td {
        border: 1px solid #000;
        padding: 5px;
        text-align: center;
        font-size: 9pt;
    }
</style>

<table>
    <thead>
        <tr>
            <th>Placa</th>
            <th>Tipo Vehículo</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Código Patrimonial</th>
            <th>Conductor</th>
            <th>Fecha Asignación</th>
        </tr>
    </thead>
    <tbody>
EOD;

$totalVehiculos = 0;

foreach ($vehiculos as $v) {
    $fecha = "No asignado";
    if (isset($v["fecha_asignacion"])) {
        if (is_object($v["fecha_asignacion"]) && method_exists($v["fecha_asignacion"], 'format')) {
            $fecha = $v["fecha_asignacion"]->format('Y-m-d');
        } else {
            $fecha = $v["fecha_asignacion"];
        }
    }

    $html .= '<tr>
        <td>' . htmlspecialchars($v['placa']) . '</td>
        <td>' . htmlspecialchars($v['nombre_tipo']) . '</td>
        <td>' . htmlspecialchars($v['nombre_marca']) . '</td>
        <td>' . htmlspecialchars($v['modelo']) . '</td>
        <td>' . htmlspecialchars($v['codigo_patrimonial']) . '</td>
        <td>' . htmlspecialchars($v['conductor']) . '</td>
        <td>' . $fecha . '</td>
    </tr>';

    $totalVehiculos++;
}

$html .= '</tbody></table>';

// Totales
$html .= '<br><br><table><tr><td style="text-align:right;font-weight:bold;">Total de vehículos : ' . $totalVehiculos . '</td></tr></table>';

// Mostrar tabla
$pdf->writeHTML($html, true, false, true, false, '');

// Salida
$pdf->Output('Reporte_Vehiculos_Asignados.pdf', 'I');
