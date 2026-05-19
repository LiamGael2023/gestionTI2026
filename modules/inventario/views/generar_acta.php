<?php
/**
 * generar_acta.php
 * Acta de Entrega de Bienes Informáticos — generado con TCPDF
 * GET ?idAsignacion=N  → descarga el PDF directamente
 *
 * Ruta TCPDF: modules/inventario/utils/tcpdf/TCPDF-main/tcpdf.php
 */

session_start();
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/AsignacionModel.php";
require_once __DIR__ . "/../utils/tcpdf/TCPDF-main/tcpdf.php";

/* ═══════════════════════════════════════════
   VALIDAR PARÁMETRO
═══════════════════════════════════════════ */
$idAsignacion = intval($_GET['idAsignacion'] ?? 0);
if (!$idAsignacion) {
    http_response_code(400);
    die('ID de asignación requerido.');
}

/* ═══════════════════════════════════════════
   OBTENER DATOS DE LA ASIGNACIÓN
═══════════════════════════════════════════ */
$conn = Conexion::conectar();
$sql  = "
    SELECT
        a.idAsignacion,
        a.idEstacion,
        est.nombreEstacion,
        a.idAmbiente,
        amb.descripcion         AS nombreAmbiente,
        ub.descripcion          AS nombreUbicacion,
        a.dniTrabajadorResponsable,
        a.trabajadorResponsable,
        a.trabajadorAsignado,
        a.fechaAsignacion,
        a.observaciones
    FROM inventario.asignacion a
    INNER JOIN inventario.estacion   est ON a.idEstacion   = est.idEstacion
    LEFT  JOIN inventario.ambiente   amb ON a.idAmbiente   = amb.idAmbiente
    LEFT  JOIN inventario.ubicacion  ub  ON amb.idUbicacion = ub.idUbicacion
    WHERE a.idAsignacion = ?
";
$stmt = sqlsrv_query($conn, $sql, [[$idAsignacion, SQLSRV_PARAM_IN]]);
$asig = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$asig) {
    sqlsrv_close($conn);
    http_response_code(404);
    die('Asignación no encontrada.');
}

$equipos = AsignacionModel::mdlEquiposEstacion(intval($asig['idEstacion']));
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

/* ═══════════════════════════════════════════
   PREPARAR DATOS
═══════════════════════════════════════════ */
function fmtFecha($f) {
    if (!$f) return '—';
    if ($f instanceof DateTime) return $f->format('d/m/Y');
    $ts = strtotime($f);
    return $ts ? date('d/m/Y', $ts) : '—';
}

$nroActa     = str_pad($idAsignacion, 4, '0', STR_PAD_LEFT);
$anio        = date('Y');
$fechaStr    = fmtFecha($asig['fechaAsignacion'] ?? null);
$responsable = $asig['trabajadorResponsable']    ?? '—';
$dniResp     = $asig['dniTrabajadorResponsable'] ?? '—';
$asignado    = $asig['trabajadorAsignado']        ?? '—';
$estacion    = $asig['nombreEstacion']            ?? '—';
$ambiente    = $asig['nombreAmbiente']            ?? '—';
$ubicacion   = $asig['nombreUbicacion']           ?? '';
$observ      = $asig['observaciones']             ?? '';

/* ═══════════════════════════════════════════
   CLASE PDF PERSONALIZADA
═══════════════════════════════════════════ */
class ActaPDF extends TCPDF
{
    public function Header()
    {
        // Banda superior azul
        $this->SetFillColor(26, 58, 107);
        $this->Rect(0, 0, 210, 3, 'F');

        $this->SetY(5);
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(26, 58, 107);
        $this->Cell(0, 5, 'GOBIERNO REGIONAL LA LIBERTAD', 0, 1, 'C');

        $this->SetFont('helvetica', '', 7.5);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 4, 'PROYECTO ESPECIAL CHAVIMOCHIC', 0, 1, 'C');

        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(26, 58, 107);
        $this->Cell(0, 7, 'ACTA DE ENTREGA DE BIENES INFORMÁTICOS', 0, 1, 'C');

        // Línea separadora
        $this->SetDrawColor(26, 58, 107);
        $this->SetLineWidth(0.7);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(2);
    }

    public function Footer()
    {
        $this->SetY(-13);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(1);
        $this->SetFont('helvetica', 'I', 6.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(93, 5, 'ÁREA DE INFORMÁTICA — CONTROL PATRIMONIAL', 0, 0, 'L');
        $this->Cell(93, 5, 'Pág. ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

/* ═══════════════════════════════════════════
   INICIALIZAR PDF
═══════════════════════════════════════════ */
$pdf = new ActaPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema Inventario TI - Chavimochic');
$pdf->SetAuthor('Área de Informática');
$pdf->SetTitle('Acta de Entrega N° ' . $nroActa . '-' . $anio);
$pdf->SetMargins(12, 40, 12);
$pdf->SetHeaderMargin(3);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// Colores
$cAzul    = [26,  58,  107];
$cAzulLt  = [232, 240, 251];
$cGris    = [248, 249, 250];
$cGrisMd  = [230, 232, 235];
$cNegro   = [30,  30,   30];
$cBlanco  = [255, 255, 255];
$cTextoG  = [100, 100, 100];

/* ═══════════════════════════════════════════
   BLOQUE 1: N° ACTA — FECHA — AÑO
═══════════════════════════════════════════ */
$pdf->SetLineWidth(0.4);
$pdf->SetDrawColor(...$cAzul);

// Fila etiquetas
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzulLt);
$pdf->SetTextColor(...$cAzul);
$pdf->Cell(40,  6, 'FECHA',       1, 0, 'C', true);
$pdf->Cell(108, 6, 'N° DE ACTA', 1, 0, 'C', true);
$pdf->Cell(38,  6, 'AÑO',         1, 1, 'C', true);

// Fila valores
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(...$cBlanco);
$pdf->SetTextColor(...$cNegro);
$pdf->Cell(40,  9, $fechaStr,             1, 0, 'C', true);

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(...$cAzul);
$pdf->Cell(108, 9, 'N° ' . $nroActa,     1, 0, 'C', true);

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(38,  9, $anio,                 1, 1, 'C', true);
$pdf->Ln(4);

/* ═══════════════════════════════════════════
   BLOQUE 2: DATOS DE ASIGNACIÓN
═══════════════════════════════════════════ */
// Header de sección
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzul);
$pdf->SetTextColor(...$cBlanco);
$pdf->Cell(186, 6.5, '  DATOS DE ASIGNACIÓN', 1, 1, 'L', true);

$pdf->SetTextColor(...$cNegro);
$filas = [
    ['RESPONSABLE',         $responsable . '   |   DNI: ' . $dniResp],
    ['TRABAJADOR ASIGNADO', $asignado],
    ['ESTACIÓN',            $estacion],
    ['AMBIENTE / DESTINO',  $ambiente . ($ubicacion ? '  —  ' . $ubicacion : '')],
    ['FECHA DE ASIGNACIÓN', $fechaStr],
];
if (trim($observ)) {
    $filas[] = ['OBSERVACIONES', $observ];
}

foreach ($filas as $i => $fila) {
    $bg = ($i % 2 === 0) ? $cBlanco : $cGris;
    $pdf->SetFillColor(...$bg);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(...$cAzul);
    $pdf->Cell(52, 6, '  ' . $fila[0], 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...$cNegro);
    $pdf->Cell(134, 6, '  ' . $fila[1], 1, 1, 'L', true);
}
$pdf->Ln(4);

/* ═══════════════════════════════════════════
   BLOQUE 3: TABLA DE EQUIPOS
═══════════════════════════════════════════ */
// Header sección
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzul);
$pdf->SetTextColor(...$cBlanco);
$pdf->Cell(186, 6.5, '  RELACIÓN DE BIENES INFORMÁTICOS', 1, 1, 'L', true);

// Cabecera columnas
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->SetFillColor(...$cAzulLt);
$pdf->SetTextColor(...$cAzul);
$cols = [
    ['N°',                   8,  'C'],
    ['CÓD. PATRIMONIAL',    32,  'C'],
    ['TIPO',                28,  'C'],
    ['DESCRIPCIÓN / CARACTERÍSTICAS', 88, 'C'],
    ['N° SERIE',            30,  'C'],
];
foreach ($cols as $col) {
    $pdf->Cell($col[1], 6, $col[0], 1, 0, $col[2], true);
}
$pdf->Ln();

// Filas de equipos
$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetTextColor(...$cNegro);
$minRows = max(count($equipos), 8);

for ($i = 0; $i < $minRows; $i++) {
    $eq     = $equipos[$i] ?? null;
    $bg     = ($i % 2 === 0) ? $cBlanco : $cGris;
    $num    = $eq ? (string)($i + 1)                  : '';
    $cp     = $eq ? ($eq['codigoPatrimonial'] ?? '')   : '';
    $tipo   = $eq ? ($eq['tipoEquipo']        ?? '')   : '';
    $desc   = $eq ? ($eq['nombreActivo']      ?? '')   : '';
    $caract = $eq ? ($eq['caracteristicas']   ?? '')   : '';
    $fullD  = $desc . ($caract ? ' — ' . $caract : '');
    $serie  = $eq ? ($eq['numeroSerie']       ?? '')   : '';

    $pdf->SetFillColor(...$bg);
    $lh = 5.5;

    // Guardar Y antes de la MultiCell
    $xBefore = $pdf->GetX();
    $yBefore = $pdf->GetY();

    $pdf->Cell(8,   $lh, $num,  1, 0, 'C', true);
    $pdf->Cell(32,  $lh, $cp,   1, 0, 'C', true);
    $pdf->Cell(28,  $lh, $tipo, 1, 0, 'C', true);

    // MultiCell para descripción (puede ser larga)
    $xDesc = $pdf->GetX();
    $yDesc = $pdf->GetY();
    $pdf->MultiCell(88, $lh, $fullD, 1, 'L', true, 0);
    $pdf->SetXY($xDesc + 88, $yDesc);
    $pdf->Cell(30,  $lh, $serie, 1, 1, 'C', true);
}
$pdf->Ln(6);

/* ═══════════════════════════════════════════
   BLOQUE 4: FIRMAS
═══════════════════════════════════════════ */
// Header sección
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzul);
$pdf->SetTextColor(...$cBlanco);
$pdf->Cell(186, 6.5, '  FIRMAS Y CONFORMIDAD', 1, 1, 'L', true);
$pdf->Ln(12);

// Tres columnas de firma
$fw   = 56;
$gap  = 9;
$xIni = 12;

$yL = $pdf->GetY();

// Líneas de firma
$pdf->SetDrawColor(...$cNegro);
$pdf->SetLineWidth(0.5);
$pdf->Line($xIni,              $yL, $xIni + $fw,              $yL);
$pdf->Line($xIni+$fw+$gap,     $yL, $xIni+$fw+$gap+$fw,       $yL);
$pdf->Line($xIni+($fw+$gap)*2, $yL, $xIni+($fw+$gap)*2+$fw,   $yL);

$pdf->Ln(2);

// Etiquetas de firma
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(...$cAzul);
$pdf->Cell($fw,  5, 'FIRMA ENTREGA',          0, 0, 'C');
$pdf->Cell($gap, 5, '',                        0, 0, 'C');
$pdf->Cell($fw,  5, 'FIRMA RECIBE/CONFORME',  0, 0, 'C');
$pdf->Cell($gap, 5, '',                        0, 0, 'C');
$pdf->Cell($fw,  5, 'V°B° JEFE INFORMÁTICA',  0, 1, 'C');

// Nombres
$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetTextColor(...$cNegro);
$pdf->Cell($fw,  5, $responsable,              0, 0, 'C');
$pdf->Cell($gap, 5, '',                        0, 0, 'C');
$pdf->Cell($fw,  5, $asignado,                 0, 0, 'C');
$pdf->Cell($gap, 5, '',                        0, 0, 'C');
$pdf->Cell($fw,  5, 'JEFE ÁREA INFORMÁTICA',   0, 1, 'C');

// DNI
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(...$cTextoG);
$pdf->Cell($fw,  4, 'DNI: ' . $dniResp,        0, 0, 'C');
$pdf->Cell($gap, 4, '',                         0, 0, 'C');
$pdf->Cell($fw,  4, '',                         0, 0, 'C');
$pdf->Cell($gap, 4, '',                         0, 0, 'C');
$pdf->Cell($fw,  4, '',                         0, 1, 'C');

$pdf->Ln(5);

/* ─── NOTA FINAL ─── */
$pdf->SetLineWidth(0.3);
$pdf->SetDrawColor(...$cGrisMd);
$pdf->Line(12, $pdf->GetY(), 198, $pdf->GetY());
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(...$cTextoG);
$pdf->MultiCell(186, 4,
    'Nota: El trabajador responsable se hace cargo de los bienes informáticos listados '.
    'en este documento, comprometiéndose a su correcto uso y custodia. Cualquier incidencia '.
    'debe ser reportada al Área de Informática — Control Patrimonial.',
    0, 'J');

/* ═══════════════════════════════════════════
   OUTPUT — DESCARGA DIRECTA
═══════════════════════════════════════════ */
$filename = 'Acta_Entrega_' . $nroActa . '_' . $anio . '.pdf';
$pdf->Output($filename, 'D');
