<?php
/**
 * generar_acta.php — Acta de Entrega de Bienes Informáticos
 * Convertido 1:1 desde test_acta_final.py (reportlab → TCPDF)
 * GET ?idAsignacion=N → descarga PDF
 */

session_start();
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/AsignacionModel.php";
require_once __DIR__ . "/../utils/tcpdf/TCPDF-main/tcpdf.php";

/* ═══════════════════════════════════════════
   OBTENER DATOS
═══════════════════════════════════════════ */
$idAsignacion = intval($_GET['idAsignacion'] ?? 0);
if (!$idAsignacion) { http_response_code(400); die('ID requerido.'); }

$conn = Conexion::conectar();
$sql  = "
    SELECT a.idAsignacion, a.idEstacion, est.nombreEstacion,
           a.idAmbiente, amb.descripcion AS nombreAmbiente,
           ub.descripcion AS nombreUbicacion,
           a.dniTrabajadorResponsable, a.trabajadorResponsable,
           a.trabajadorAsignado, a.fechaAsignacion, a.observaciones
    FROM inventario.asignacion a
    INNER JOIN inventario.estacion  est ON a.idEstacion   = est.idEstacion
    LEFT  JOIN inventario.ambiente  amb ON a.idAmbiente   = amb.idAmbiente
    LEFT  JOIN inventario.ubicacion ub  ON amb.idUbicacion = ub.idUbicacion
    WHERE a.idAsignacion = ?
";
$stmt = sqlsrv_query($conn, $sql, [[$idAsignacion, SQLSRV_PARAM_IN]]);
$asig = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
if (!$asig) { sqlsrv_close($conn); http_response_code(404); die('No encontrado.'); }

$equipos = AsignacionModel::mdlEquiposEstacion(intval($asig['idEstacion']));
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

function fmtF($f){ if(!$f)return'—'; if($f instanceof DateTime)return$f->format('d/m/Y'); $t=strtotime($f); return $t?date('d/m/Y',$t):'—'; }

$nroActa     = str_pad($idAsignacion, 4, '0', STR_PAD_LEFT);
$anio        = date('Y');
$fechaStr    = fmtF($asig['fechaAsignacion'] ?? null);
$responsable = $asig['trabajadorResponsable']    ?? '—';
$dniResp     = $asig['dniTrabajadorResponsable'] ?? '—';
$asignado    = $asig['trabajadorAsignado']        ?? '—';
$estacion    = $asig['nombreEstacion']            ?? '—';
$ambiente    = $asig['nombreAmbiente']            ?? '—';
$ubicacion   = $asig['nombreUbicacion']           ?? '';
$dest        = $ambiente . ($ubicacion ? '  —  ' . $ubicacion : '');
$observ      = $asig['observaciones']             ?? '';

/* ═══════════════════════════════════════════
   COLORES (igual que Python)
   AZUL   = #1a3a6b
   AZULLT = #e8f0fb
   GRIS   = #f8f9fa
═══════════════════════════════════════════ */
$cAzul   = [26,  58,  107];
$cAzulLt = [232, 240, 251];
$cGris   = [248, 249, 250];
$cNegro  = [30,  30,   30];
$cGrisT  = [100, 100, 100];
$cBlanco = [255, 255, 255];
$cBorde  = [192, 200, 216];

/* ═══════════════════════════════════════════
   CLASE PDF — header y footer igual al test
═══════════════════════════════════════════ */
class ActaPDF extends TCPDF {
    public function Header() {
        // Banda azul superior delgada
        $this->SetFillColor(26, 58, 107);
        $this->Rect(0, 0, 210, 2, 'F');
        $this->SetY(4);
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(26, 58, 107);
        $this->Cell(0, 5, 'GOBIERNO REGIONAL LA LIBERTAD', 0, 1, 'C');
        $this->SetFont('helvetica', '', 7.5);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 4, 'PROYECTO ESPECIAL CHAVIMOCHIC', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(26, 58, 107);
        $this->Cell(0, 7, 'ACTA DE ENTREGA DE BIENES INFORMÁTICOS', 0, 1, 'C');
        // Línea separadora azul gruesa
        $this->SetDrawColor(26, 58, 107);
        $this->SetLineWidth(1.2);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(3);
    }
    public function Footer() {
        $this->SetY(-12);
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.3);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(1);
        $this->SetFont('helvetica', 'I', 6.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(93, 4, 'ÁREA DE INFORMÁTICA — CONTROL PATRIMONIAL', 0, 0, 'L');
        $this->Cell(93, 4, 'Pág. '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, 0, 'R');
    }
}

$pdf = new ActaPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema Inventario TI');
$pdf->SetAuthor('Área de Informática');
$pdf->SetTitle('Acta de Entrega N° '.$nroActa.'-'.$anio);
$pdf->SetMargins(12, 38, 12);
$pdf->SetHeaderMargin(3);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

/* ═══════════════════════════════════════════
   BLOQUE 1: FECHA / N° ACTA / AÑO
   Python: colWidths=[40, 106, 40] rowHeights=[7,9]
═══════════════════════════════════════════ */
$pdf->SetLineWidth(0.4);
$pdf->SetDrawColor(...$cAzul);

// Fila 1 — etiquetas
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzulLt);
$pdf->SetTextColor(...$cAzul);
$pdf->Cell(40,  7, 'FECHA',      1, 0, 'C', true);
$pdf->Cell(106, 7, 'N° DE ACTA',1, 0, 'C', true);
$pdf->Cell(40,  7, 'AÑO',        1, 1, 'C', true);

// Fila 2 — valores
$pdf->SetFillColor(...$cBlanco);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(...$cNegro);
$pdf->Cell(40,  9, $fechaStr,        1, 0, 'C', true);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(...$cAzul);
$pdf->Cell(106, 9, 'N° '.$nroActa,  1, 0, 'C', true);
$pdf->Cell(40,  9, $anio,            1, 1, 'C', true);
$pdf->Ln(4);

/* ═══════════════════════════════════════════
   BLOQUE 2: DATOS DE ASIGNACIÓN
   Python: colWidths=[52, 134]
═══════════════════════════════════════════ */
// Header sección
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzul);
$pdf->SetTextColor(...$cBlanco);
$pdf->Cell(186, 6.5, '  DATOS DE ASIGNACIÓN', 1, 1, 'L', true);

$filas = [
    ['RESPONSABLE',         $responsable.'   |   DNI: '.$dniResp],
    ['TRABAJADOR ASIGNADO', $asignado],
    ['ESTACIÓN',            $estacion],
    ['AMBIENTE / DESTINO',  $dest],
    ['FECHA DE ASIGNACIÓN', $fechaStr],
];
if (trim($observ)) $filas[] = ['OBSERVACIONES', $observ];

foreach ($filas as $i => $f) {
    // Col label — fondo azul claro
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(...$cAzulLt);
    $pdf->SetTextColor(...$cAzul);
    $pdf->Cell(52, 6, '  '.$f[0], 1, 0, 'L', true);
    // Col valor — alternado blanco/gris
    $bg = ($i % 2 === 0) ? $cBlanco : $cGris;
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(...$bg);
    $pdf->SetTextColor(...$cNegro);
    $pdf->Cell(134, 6, '  '.$f[1], 1, 1, 'L', true);
}
$pdf->Ln(4);

/* ═══════════════════════════════════════════
   BLOQUE 3: TABLA EQUIPOS
   Python: colWidths=[9,32,28,93,24] total=186
═══════════════════════════════════════════ */
// Header tabla
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzul);
$pdf->SetTextColor(...$cBlanco);
$pdf->Cell(186, 6.5, '  RELACIÓN DE BIENES INFORMÁTICOS', 1, 1, 'L', true);

// Cabecera columnas
$pdf->SetFont('helvetica', 'B', 7.5);
$pdf->SetFillColor(...$cAzul);
$pdf->SetTextColor(...$cBlanco);
$pdf->Cell(9,  6, 'N°',               1, 0, 'C', true);
$pdf->Cell(32, 6, 'CÓD. PATRIMONIAL', 1, 0, 'C', true);
$pdf->Cell(28, 6, 'TIPO',             1, 0, 'C', true);
$pdf->Cell(93, 6, 'DESCRIPCIÓN / CARACTERÍSTICAS', 1, 0, 'C', true);
$pdf->Cell(24, 6, 'N° SERIE',         1, 1, 'C', true);

// Filas equipos — altura dinámica según contenido
$pdf->SetFont('helvetica', '', 7.5);
$minRows = max(count($equipos), 8);

for ($i = 0; $i < $minRows; $i++) {
    $eq     = $equipos[$i] ?? null;
    $bg     = ($i % 2 === 0) ? $cBlanco : $cGris;
    $num    = $eq ? (string)($i + 1)               : '';
    $cp     = $eq ? ($eq['codigoPatrimonial'] ?? '') : '';
    $tipo   = $eq ? ($eq['tipoEquipo']        ?? '') : '';
    $desc   = $eq ? ($eq['nombreActivo']      ?? '') : '';
    $caract = $eq ? ($eq['caracteristicas']   ?? '') : '';
    $fullD  = $desc . ($caract ? ' — '.$caract : '');
    $serie  = $eq ? ($eq['numeroSerie']       ?? '') : '';

    // ── PASO 1: Calcular altura ANTES de dibujar ──
    $lineH   = 5.5;
    $nLineas = $eq ? $pdf->getNumLines($fullD, 90) : 1;
    $lh      = max($nLineas * $lineH, 9);

    $xM   = 12;
    $yRow = $pdf->GetY();

    // ── PASO 2: Dibujar fondos de color (sin bordes, sin texto) ──
    $pdf->SetDrawColor(255,255,255); // borde invisible
    $pdf->SetFillColor(...$bg);
    $pdf->SetXY($xM,       $yRow); $pdf->Cell(9,   $lh, '', 0, 0, 'C', true);
    $pdf->SetXY($xM+9,     $yRow); $pdf->Cell(32,  $lh, '', 0, 0, 'C', true);
    $pdf->SetXY($xM+41,    $yRow); $pdf->Cell(28,  $lh, '', 0, 0, 'C', true);
    $pdf->SetXY($xM+69,    $yRow); $pdf->Cell(93,  $lh, '', 0, 0, 'C', true);
    $pdf->SetXY($xM+162,   $yRow); $pdf->Cell(24,  $lh, '', 0, 0, 'C', true);

    // ── PASO 3: Escribir texto encima (sin bordes, sin fondo) ──
    $pdf->SetTextColor(...$cNegro);
    $pdf->SetFont('helvetica', '', 7.5);

    $pdf->SetXY($xM,       $yRow); $pdf->Cell(9,   $lh, $num,   0, 0, 'C', false);
    $pdf->SetXY($xM+9,     $yRow); $pdf->Cell(32,  $lh, $cp,    0, 0, 'C', false);
    $pdf->SetXY($xM+41,    $yRow); $pdf->Cell(28,  $lh, $tipo,  0, 0, 'C', false);

    // Descripción multilinea — solo texto, sin borde, sin fondo
    $pdf->SetXY($xM+69+2,  $yRow+1.5); // pequeño padding interno
    $pdf->MultiCell(90, $lineH, $fullD, 0, 'L', false, 0);

    $pdf->SetXY($xM+162,   $yRow); $pdf->Cell(24,  $lh, $serie, 0, 0, 'C', false);

    // ── PASO 4: Dibujar bordes de la fila de una sola vez ──
    $pdf->SetDrawColor(...$cAzul);
    $pdf->SetLineWidth(0.4);
    // Borde exterior de toda la fila
    $pdf->Rect($xM, $yRow, 186, $lh);
    // Líneas verticales internas
    $pdf->SetDrawColor(...$cBorde);
    $pdf->SetLineWidth(0.25);
    $pdf->Line($xM+9,   $yRow, $xM+9,   $yRow+$lh);
    $pdf->Line($xM+41,  $yRow, $xM+41,  $yRow+$lh);
    $pdf->Line($xM+69,  $yRow, $xM+69,  $yRow+$lh);
    $pdf->Line($xM+162, $yRow, $xM+162, $yRow+$lh);

    // ── PASO 5: Avanzar al siguiente renglón ──
    $pdf->SetDrawColor(...$cAzul);
    $pdf->SetXY($xM, $yRow + $lh);
}
$pdf->Ln(5);

/* ═══════════════════════════════════════════
   BLOQUE 4: FIRMAS
   Python: Spacer(14mm) + línea + etiqueta + nombre + DNI
   colWidths=[62,62,62] total=186
═══════════════════════════════════════════ */
// Si no queda espacio suficiente, nueva página
if (($pdf->getPageHeight() - $pdf->GetY() - $pdf->getBreakMargin()) < 55) {
    $pdf->AddPage();
}

// Header sección
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(...$cAzul);
$pdf->SetTextColor(...$cBlanco);
$pdf->Cell(186, 6.5, '  FIRMAS Y CONFORMIDAD', 1, 1, 'L', true);
$pdf->Ln(16); // espacio para firmar

// Anchos: 3 × 57mm + 2 × 7.5mm gap = 186mm
$fw  = 57;
$gap = 7.5;

// Líneas de firma (horizontal)
$pdf->SetDrawColor(...$cNegro);
$pdf->SetLineWidth(0.7);
$xL = $pdf->GetX();
$yL = $pdf->GetY();
$pdf->Line($xL,              $yL, $xL + $fw,              $yL);
$pdf->Line($xL+$fw+$gap,     $yL, $xL+($fw*2)+$gap,       $yL);
$pdf->Line($xL+($fw*2)+($gap*2), $yL, $xL+($fw*3)+($gap*2), $yL);
$pdf->Ln(2);

// Etiquetas — azul negrita — centradas
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(...$cAzul);
$pdf->Cell($fw,  5, 'FIRMA ENTREGA',         0, 0, 'C');
$pdf->Cell($gap, 5, '',                       0, 0, 'C');
$pdf->Cell($fw,  5, 'FIRMA RECIBE/CONFORME', 0, 0, 'C');
$pdf->Cell($gap, 5, '',                       0, 0, 'C');
$pdf->Cell($fw,  5, 'V°B° JEFE INFORMÁTICA', 0, 1, 'C');

// Nombres — negro normal
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(...$cNegro);
$pdf->Cell($fw,  5, $responsable,             0, 0, 'C');
$pdf->Cell($gap, 5, '',                       0, 0, 'C');
$pdf->Cell($fw,  5, $asignado,                0, 0, 'C');
$pdf->Cell($gap, 5, '',                       0, 0, 'C');
$pdf->Cell($fw,  5, 'JEFE ÁREA INFORMÁTICA',  0, 1, 'C');

// DNI — gris itálica
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(...$cGrisT);
$pdf->Cell($fw,  4, 'DNI: '.$dniResp, 0, 0, 'C');
$pdf->Cell($gap, 4, '',               0, 0, 'C');
$pdf->Cell($fw,  4, '',               0, 0, 'C');
$pdf->Cell($gap, 4, '',               0, 0, 'C');
$pdf->Cell($fw,  4, '',               0, 1, 'C');
$pdf->Ln(5);

/* ═══════════════════════════════════════════
   NOTA FINAL
═══════════════════════════════════════════ */
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.3);
$pdf->Line(12, $pdf->GetY(), 198, $pdf->GetY());
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(...$cGrisT);
$pdf->MultiCell(186, 4,
    'Nota: El trabajador responsable se hace cargo de los bienes informáticos listados '.
    'en este documento, comprometiéndose a su correcto uso y custodia. Cualquier incidencia '.
    'debe ser reportada al Área de Informática — Control Patrimonial.',
    0, 'J');

/* ═══════════════════════════════════════════
   OUTPUT
═══════════════════════════════════════════ */
$filename = 'Acta_Entrega_'.$nroActa.'_'.$anio.'.pdf';
$pdf->Output($filename, 'D');