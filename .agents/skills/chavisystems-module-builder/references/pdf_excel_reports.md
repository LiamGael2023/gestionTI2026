# Guia de Exportaciones y Generacion de Reportes (PDF y Excel)

Esta guia explica como construir salidas impresas en **PDF** (usando TCPDF y dompdf) y descargas en **Excel** (usando OpenSpout y PhpSpreadsheet) dentro de los modulos de CHAVIsystems.

---

## Cuando usar cada libreria

| Libreria | Ubicacion | Mejor para |
|----------|-----------|------------|
| **TCPDF** | `libs/tcpdf/` | Reportes tabulares clasicos, PDF con tablas de muchas filas, alto rendimiento |
| **dompdf** | `libs/dompdf/` | Reportes con diseno HTML/CSS rico, layouts complejos, imagenes embebidas |
| **OpenSpout** | `libs/OpenSpout/` | Exportaciones Excel/CSV masivas (streaming, sin carga en memoria) |
| **PhpSpreadsheet** | `libs/vendor/phpoffice/phpspreadsheet/` | Excel con formatos, estilos, formulas, graficos y lectura de archivos |

---

## 1. Generacion de Reportes PDF con TCPDF

Las librerias de TCPDF se encuentran en `libs/tcpdf/`. Cada reporte en PDF suele extender de `TCPDF` para configurar encabezados y pies de pagina corporativos.

### Estructura Tipica (`modules/<modulo>/reportes/tcpdf/reporte.php`)

```php
<?php
session_start();
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../../../libs/tcpdf/tcpdf.php";
require_once __DIR__ . "/../models/MiModuloModel.php";

class ReportePDF extends TCPDF {
    public function Header() {
        $image_file = __DIR__ . '/../../../public/img/logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 15, 'SISTEMA DE GESTION INSTITUCIONAL', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 15, 'Reporte Oficial de Modulo', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new ReportePDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Reporte de Modulo');
$pdf->SetMargins(15, 25, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddPage();

$datos = MiModuloModel::mdlListarRegistros();

$html = '<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color: #004d99; color: #ffffff; font-weight: bold;">
            <th width="10%">#</th>
            <th width="30%">Codigo</th>
            <th width="40%">Descripcion</th>
            <th width="20%">Estado</th>
        </tr>
    </thead>
    <tbody>';

foreach ($datos as $i => $row) {
    $estado = ($row['estado'] == 1) ? 'Activo' : 'Inactivo';
    $html .= '<tr>
        <td width="10%">' . ($i + 1) . '</td>
        <td width="30%">' . htmlspecialchars($row['codigo']) . '</td>
        <td width="40%">' . htmlspecialchars($row['descripcion']) . '</td>
        <td width="20%">' . htmlspecialchars($estado) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('reporte_modulo.pdf', 'I');
```

---

## 2. Generacion de Reportes PDF con dompdf

dompdf convierte HTML + CSS directamente a PDF. Las librerias estan en `libs/dompdf/`.

### Estructura de Reporte con dompdf (`modules/<modulo>/reportes/dompdf/reporte.php`)

```php
<?php
session_start();
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../../../libs/dompdf/vendor/autoload.php";
require_once __DIR__ . "/../models/MiModuloModel.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'helvetica');

$dompdf = new Dompdf($options);

$datos = MiModuloModel::mdlListarRegistros();

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: helvetica, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { width: 120px; }
        .header h1 { color: #004d99; margin: 10px 0 5px; font-size: 16px; }
        .header h2 { color: #666; margin: 0; font-size: 12px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #004d99; color: #fff; padding: 8px 6px; text-align: left; }
        td { padding: 6px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { text-align: center; margin-top: 30px; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://app.chavimochic.gob.pe/Webservice/contador/LogoChavimochicFINAL.png">
        <h1>SISTEMA DE GESTION INSTITUCIONAL</h1>
        <h2>Reporte Oficial de Modulo</h2>
    </div>
    <table>
        <thead>
            <tr><th>#</th><th>Codigo</th><th>Descripcion</th><th>Estado</th></tr>
        </thead>
        <tbody>';

foreach ($datos as $i => $row) {
    $estado = ($row['estado'] == 1) ? 'Activo' : 'Inactivo';
    $html .= '<tr><td>' . ($i + 1) . '</td><td>' . htmlspecialchars($row['codigo']) . '</td><td>' . htmlspecialchars($row['descripcion']) . '</td><td>' . $estado . '</td></tr>';
}

$html .= '</tbody></table>
    <div class="footer">
        Pagina {PAGE_NUM} de {PAGE_COUNT} | Generado el ' . date('d/m/Y H:i') . '
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('reporte_modulo.pdf', ['Attachment' => false]);
exit();
```

---

## 3. Exportacion a Excel con OpenSpout (Streaming)

OpenSpout permite escribir archivos Excel de millones de filas consumiendo muy poca memoria RAM. El autoloader se ubica en `libs/autoload_openspout.php`.

### Estructura (`modules/<modulo>/reportes/exportar_excel.php`)

```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../../libs/autoload_openspout.php";
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/MiModuloModel.php";

use OpenSpout\Writer\Common\Creator\WriterEntityFactory;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Reporte_Modulo_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = WriterEntityFactory::createXLSXWriter();
$writer->openToBrowser('php://output');

$headerRow = WriterEntityFactory::createRowFromArray(['ID', 'CODIGO', 'NOMBRE', 'FECHA REGISTRO', 'ESTADO']);
$writer->addRow($headerRow);

$registros = MiModuloModel::mdlListarRegistros();

foreach ($registros as $reg) {
    $row = WriterEntityFactory::createRowFromArray([
        $reg['id'], $reg['codigo'], $reg['nombre'], $reg['fecha'], $reg['estado']
    ]);
    $writer->addRow($row);
}

$writer->close();
exit();
```

**Ventajas:** Streaming directo al navegador, soporta XLSX/CSV/ODS, ideal para miles de registros.

---

## 4. Exportacion a Excel con PhpSpreadsheet (Formato Rico)

PhpSpreadsheet (`libs/vendor/phpoffice/phpspreadsheet/`) ofrece control sobre estilos, formulas, y lectura de archivos.

### Estructura de Exportador con PhpSpreadsheet

```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../../libs/vendor/autoload.php";
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/MiModuloModel.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados con estilo
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'CODIGO');
$sheet->setCellValue('C1', 'NOMBRE');
$sheet->setCellValue('D1', 'FECHA REGISTRO');
$sheet->setCellValue('E1', 'ESTADO');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004d99']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

// Datos
$registros = MiModuloModel::mdlListarRegistros();
$fila = 2;
foreach ($registros as $reg) {
    $sheet->setCellValue('A' . $fila, $reg['id']);
    $sheet->setCellValue('B' . $fila, $reg['codigo']);
    $sheet->setCellValue('C' . $fila, $reg['nombre']);
    $sheet->setCellValue('D' . $fila, $reg['fecha']);
    $sheet->setCellValue('E' . $fila, $reg['estado']);
    $fila++;
}

// Auto-ajustar columnas
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Reporte_Modulo_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
```

**Usar PhpSpreadsheet cuando necesites:** Colores en celdas, bordes, formulas, auto-ajuste de columnas, proteccion de hojas, o leer archivos Excel subidos por usuarios.

---

## 5. Directorios de Reportes por Modulo

La convencion del proyecto organiza los reportes dentro de cada modulo:

```
modules/<modulo>/reportes/
  tcpdf/
    reporte_<nombre>.php      -- Reportes con TCPDF
  dompdf/
    reporte_<nombre>.php      -- Reportes con dompdf
  exportar_excel.php          -- Exportacion Excel (puede usar OpenSpout o PhpSpreadsheet)
```

Cada archivo de reporte debe ser autocontenido: incluir sus propias dependencias, obtener datos del modelo correspondiente y generar la salida directamente al navegador.
