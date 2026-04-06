<?php
require_once __DIR__ . '/../../libs/vendor/autoload.php';
require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';

use TCPDF;

class ReporteControllerPDF {

    // ── Paleta de colores (RGB para TCPDF) ────────────────────────────────
    const C_TN_BG   = [75, 172, 198];
    const C_TN_FG   = [255, 255, 255];
    const C_TD_BG   = [255, 192, 144];
    const C_TD_FG   = [0, 0, 0];
    const C_COMP_BG = [0, 176, 80];
    const C_COMP_FG = [255, 255, 255];
    const C_ONOM_BG = [255, 224, 255];
    const C_ONOM_FG = [0, 0, 0];
    const C_DESC_BG = [68, 114, 196];
    const C_DESC_FG = [255, 255, 255];
    const C_HDR_BG  = [31, 56, 100];
    const C_HDR_FG  = [255, 255, 255];
    const C_WKD_BG  = [255, 0, 0];
    const C_WKD_FG  = [255, 255, 255];
    const C_SEC_BG  = [189, 215, 238];
    const C_ROW_ALT = [242, 247, 255];

    // ── Mapa turno → estilo visual ─────────────────────────────
    private static function turnoStyle(string $val): array {
        $map = [
            'TN' => ['bg' => self::C_TN_BG,   'fg' => self::C_TN_FG,   'label' => 'TN'],
            'TD' => ['bg' => self::C_TD_BG,   'fg' => self::C_TD_FG,   'label' => 'TD'],
            'C'  => ['bg' => self::C_COMP_BG, 'fg' => self::C_COMP_FG, 'label' => 'C'],
            'O'  => ['bg' => self::C_ONOM_BG, 'fg' => self::C_ONOM_FG, 'label' => 'O'],
            'D'  => ['bg' => self::C_DESC_BG, 'fg' => self::C_DESC_FG, 'label' => 'D'],
            'N'  => ['bg' => self::C_TN_BG,   'fg' => self::C_TN_FG,   'label' => 'TN'],
        ];
        return $map[$val] ?? [];
    }

    private static function normalizarTurno(string $desc): string {
        $d = strtoupper(trim($desc));
        if (str_starts_with($d, 'TN') || str_contains($d, 'NOCHE'))              return 'TN';
        if (str_starts_with($d, 'TD') || str_contains($d, 'DIA') || str_contains($d, 'DÍA')) return 'TD';
        if (str_starts_with($d, 'C')  || str_contains($d, 'COMP'))               return 'C';
        if (str_starts_with($d, 'O')  || str_contains($d, 'ONOM'))               return 'O';
        if (str_starts_with($d, 'D')  || str_contains($d, 'DESC'))               return 'D';
        return strtoupper(substr($desc, 0, 2));
    }

    // ── Generar PDF ─────────────────────────────────────────────
    public static function ctrGenerarPDF(
        array $trabajadores,
        array $turnos,
        int   $mes,
        int   $anio,
        array $estructuras = []
    ): void {

        while (ob_get_level()) ob_end_clean();

   
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Reporte Horario');
        $pdf->SetAuthor('Sistema');
        $pdf->SetTitle('Horario Ejecutado Personal');

      
        $marginL = 4;
        $marginR = 4;
        $marginT = 6;
        $pdf->SetMargins($marginL, $marginT, $marginR);
        $pdf->SetAutoPageBreak(true, 6);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 7);

        $diasMes  = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $mesesES  = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                     'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

       
        $pageW    = $pdf->getPageWidth();          
        $usableW  = $pageW - $marginL - $marginR;  

        $nameColW = 50;                                           
        $dayColW  = round(($usableW - $nameColW) / $diasMes, 2); 

        $rowH     = 5;  
        $hdrH     = 7;  

      
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetFillColor(...self::C_HDR_BG);
        $pdf->SetTextColor(...self::C_HDR_FG);
        $pdf->Cell($usableW, 8, 'HORARIO EJECUTADO PERSONAL', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($usableW, 6, $mesesES[$mes] . ' ' . $anio, 0, 1, 'C', true);

        
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(...self::C_HDR_BG);
        $pdf->SetTextColor(...self::C_HDR_FG);
        $pdf->Cell($nameColW, $hdrH, 'Nombres', 1, 0, 'C', true);

        for ($d = 1; $d <= $diasMes; $d++) {
            $diaSem      = (int) date('w', strtotime("$anio-$mes-$d"));
            $esFinSemana = ($diaSem === 0 || $diaSem === 6);
            $pdf->SetFillColor(...($esFinSemana ? self::C_WKD_BG : self::C_HDR_BG));
            $pdf->SetTextColor(...($esFinSemana ? self::C_WKD_FG : self::C_HDR_FG));
            $pdf->Cell($dayColW, $hdrH, $d, 1, 0, 'C', true);
        }
        $pdf->Ln();

    
        $turnosPorId = [];
        foreach ($turnos as $t) {
            $turnosPorId[$t['id']] = $t['turnos'] ?? [];
        }

        if (empty($estructuras)) {
            $estructuras = [['nombre' => null, 'trabajadores' => $trabajadores]];
        }

      
        foreach ($estructuras as $estructura) {

          
            if (!empty($estructura['nombre'])) {
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->SetFillColor(...self::C_SEC_BG);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell($usableW, $rowH, '  ' . $estructura['nombre'], 1, 1, 'L', true);
            }

            foreach ($estructura['trabajadores'] as $idx => $t) {
                $pdf->SetFont('helvetica', '', 6);
                $rowBg = ($idx % 2 === 0) ? [255, 255, 255] : self::C_ROW_ALT;
                $pdf->SetFillColor(...$rowBg);
                $pdf->SetTextColor(0, 0, 0);

               
                $pdf->Cell($nameColW, $rowH, strtoupper($t['nombre']), 1, 0, 'L', true);

                $turnArr = $turnosPorId[$t['id']] ?? [];

                for ($d = 1; $d <= $diasMes; $d++) {
                    $fecha = strtotime("$anio-$mes-$d");
                    $valor = '';
                    foreach ($turnArr as $turno) {
                        $fi = strtotime($turno['FechaInicioTurno']['date']);
                        $ff = strtotime($turno['FechaFinTurno']['date']);
                        if ($fecha >= $fi && $fecha <= $ff) {
                            $valor = self::normalizarTurno($turno['MarcTipo_Descripcion'] ?? '');
                        }
                    }

                    $style = self::turnoStyle($valor);

                    if (!empty($style)) {
                        $pdf->SetFillColor(...$style['bg']);
                        $pdf->SetTextColor(...$style['fg']);
                        $pdf->Cell($dayColW, $rowH, $style['label'], 1, 0, 'C', true);
                    } else {
                        $diaSem      = (int) date('w', $fecha);
                        $esFinSemana = ($diaSem === 0 || $diaSem === 6);
                        $bg          = $esFinSemana ? [255, 245, 245] : $rowBg;
                        $pdf->SetFillColor(...$bg);
                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->Cell($dayColW, $rowH, '', 1, 0, 'C', true);
                    }
                }
                $pdf->Ln();
            }
        }

     
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 8);

        $leyendas = [
            ['TN', self::C_TN_BG,   self::C_TN_FG,   'Turno Noche'],
            ['TD', self::C_TD_BG,   self::C_TD_FG,   'Turno Día'],
            ['C',  self::C_COMP_BG, self::C_COMP_FG, 'Compensación'],
            ['O',  self::C_ONOM_BG, self::C_ONOM_FG, 'Onomástico'],
            ['D',  self::C_DESC_BG, self::C_DESC_FG, 'Descanso Semanal'],
        ];

        foreach ($leyendas as $ley) {
            $pdf->SetFillColor(...$ley[1]);
            $pdf->SetTextColor(...$ley[2]);
            $pdf->Cell(8, 6, $ley[0], 1, 0, 'C', true);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(32, 6, $ley[3], 1, 0, 'L', true);
            $pdf->Ln();
        }

        $pdf->Output('Turnos_Ejecutado.pdf', 'I');
        exit;
    }
}