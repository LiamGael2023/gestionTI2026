<?php
require_once __DIR__ . '/../../libs/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


class ReporteController {

    // ── Paleta de colores (ARGB) ──────────────────────────────────────────────
    const C_TN_BG   = 'FF4BACC6'; // azul turquesa   – Turno Noche
    const C_TN_FG   = 'FFFFFFFF';
    const C_TD_BG   = 'FFFFC090'; // naranja claro   – Turno Día
    const C_TD_FG   = 'FF000000';
    const C_COMP_BG = 'FF00B050'; // verde            – Compensación
    const C_COMP_FG = 'FFFFFFFF';
    const C_ONOM_BG = 'FFFFE0FF'; // rosa/lila        – Onomástico
    const C_ONOM_FG = 'FF000000';
    const C_DESC_BG = 'FF4472C4'; // azul oscuro      – Descanso Semanal
    const C_DESC_FG = 'FFFFFFFF';
    const C_HDR_BG  = 'FF1F3864'; // azul marino      – cabeceras
    const C_HDR_FG  = 'FFFFFFFF';
    const C_WKD_BG  = 'FFFF0000'; // rojo             – sábado/domingo
    const C_WKD_FG  = 'FFFFFFFF';
    const C_SEC_BG  = 'FFBDD7EE'; // azul claro       – fila de sección
    const C_ROW_ALT = 'FFF2F7FF'; // azul muy claro   – fila par

    // ── Mapa turno → estilo visual ────────────────────────────────────────────
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

    // ── Normaliza descripción de turno → clave estándar ──────────────────────
    private static function normalizarTurno(string $desc): string {
        $d = strtoupper(trim($desc));
        if (str_starts_with($d, 'TN') || str_contains($d, 'NOCHE'))        return 'TN';
        if (str_starts_with($d, 'TD') || str_contains($d, 'DIA')
                                       || str_contains($d, 'DÍA'))         return 'TD';
        if (str_starts_with($d, 'C')  || str_contains($d, 'COMP'))         return 'C';
        if (str_starts_with($d, 'O')  || str_contains($d, 'ONOM'))         return 'O';
        if (str_starts_with($d, 'D')  || str_contains($d, 'DESC'))         return 'D';
        return strtoupper(substr($desc, 0, 2));
    }
    //PDF REPORTE 
    
    // =========================================================================
    public static function ctrGenerarExcel(
        array $trabajadores,
        array $turnos,
        int   $mes,
        int   $anio,
        array $estructuras = []   
    ): void {
        while (ob_get_level()) ob_end_clean();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Horario');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);

        $diasMes    = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $mesesES    = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                       'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

        // Columna A = nombres, columna B en adelante = días
        $colDia1Idx   = 2;
        $colUltimoIdx = $colDia1Idx + $diasMes - 1;
        $colUltima    = Coordinate::stringFromColumnIndex($colUltimoIdx);

        // Índice de turnos por id de trabajador
        $turnosPorId = [];
        foreach ($turnos as $t) {
            $turnosPorId[$t['id']] = $t['turnos'] ?? [];
        }

        // ════════════════════════════════════════════════════════════════════
        // FILA 1 – TÍTULO
        // ════════════════════════════════════════════════════════════════════
        $sheet->mergeCells("A1:{$colUltima}1");
        $sheet->setCellValue('A1', 'HORARIO EJECUTADO PERSONAL');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16,
                            'color' => ['argb' => self::C_HDR_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => self::C_HDR_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // ════════════════════════════════════════════════════════════════════
        // FILA 2 – MES Y AÑO
        // ════════════════════════════════════════════════════════════════════
        $sheet->mergeCells("A2:{$colUltima}2");
        $sheet->setCellValue('A2', $mesesES[$mes] . ' ' . $anio);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13,
                            'color' => ['argb' => self::C_HDR_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => self::C_HDR_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ════════════════════════════════════════════════════════════════════
        // FILA 3 – "Estructura" | números de día (1…N)
        // ════════════════════════════════════════════════════════════════════
        $sheet->setCellValue('A3', 'Nombres ');
        $sheet->getStyle('A3')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10,
                            'color' => ['argb' => self::C_HDR_FG]],
            'fill'      => ['fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => self::C_HDR_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN,
                                          'color' => ['argb' => 'FF8EA9C1']]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(16);
        $sheet->getColumnDimension('A')->setWidth(32);

        for ($d = 1; $d <= $diasMes; $d++) {
            $diaSem   = (int) date('w', strtotime("$anio-$mes-$d"));
            $colIdx   = $colDia1Idx + $d - 1;
            $colLetra = Coordinate::stringFromColumnIndex($colIdx);
            $celda    = $colLetra . '3';

            $esFinSemana = ($diaSem === 0 || $diaSem === 6);

            $sheet->setCellValue($celda, $d);
            $sheet->getStyle($celda)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9,
                                'color' => ['argb' => $esFinSemana
                                    ? self::C_WKD_FG : self::C_HDR_FG]],
                'fill'      => ['fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => $esFinSemana
                                    ? self::C_WKD_BG : self::C_HDR_BG]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER],
                'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN,
                                              'color' => ['argb' => 'FF8EA9C1']]],
            ]);

            $sheet->getColumnDimension($colLetra)->setWidth(4.0);
        }

        // ════════════════════════════════════════════════════════════════════
        // FILA 4+ – DATOS (inmediatamente después de la cabecera)
        // ════════════════════════════════════════════════════════════════════
        $rowIndex = 4;

        if (empty($estructuras)) {
            $estructuras = [['nombre' => null, 'trabajadores' => $trabajadores]];
        }

        foreach ($estructuras as $estructura) {

            // Fila de sección opcional
            if (!empty($estructura['nombre'])) {
                $sheet->mergeCells("A{$rowIndex}:{$colUltima}{$rowIndex}");
                $sheet->setCellValue("A{$rowIndex}", $estructura['nombre']);
                $sheet->getStyle("A{$rowIndex}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10],
                    'fill'      => ['fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => self::C_SEC_BG]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => Alignment::VERTICAL_CENTER,
                                    'indent'     => 1],
                    'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN,
                                                  'color' => ['argb' => 'FF8EA9C1']]],
                ]);
                $sheet->getRowDimension($rowIndex)->setRowHeight(15);
                $rowIndex++;
            }

            foreach ($estructura['trabajadores'] as $idx => $t) {
                $rowBg = ($idx % 2 === 0) ? 'FFFFFFFF' : self::C_ROW_ALT;

                // Celda nombre
                $sheet->setCellValue("A{$rowIndex}", strtoupper($t['nombre']));
                $sheet->getStyle("A{$rowIndex}")->applyFromArray([
                    'font'      => ['size' => 9],
                    'fill'      => ['fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => $rowBg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => Alignment::VERTICAL_CENTER,
                                    'indent'     => 1],
                    'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN,
                                                  'color' => ['argb' => 'FF8EA9C1']]],
                ]);
                $sheet->getRowDimension($rowIndex)->setRowHeight(14);

                $turnArr = $turnosPorId[$t['id']] ?? [];

                for ($d = 1; $d <= $diasMes; $d++) {
                    $fecha    = strtotime("$anio-$mes-$d");
                    $diaSem   = (int) date('w', $fecha);
                    $colIdx   = $colDia1Idx + $d - 1;
                    $colLetra = Coordinate::stringFromColumnIndex($colIdx);
                    $celda    = $colLetra . $rowIndex;

                    $valor = '';
                    foreach ($turnArr as $turno) {
                        $fi = strtotime($turno['FechaInicioTurno']['date']);
                        $ff = strtotime($turno['FechaFinTurno']['date']);
                        if ($fecha >= $fi && $fecha <= $ff) {
                            $valor = self::normalizarTurno(
                                $turno['MarcTipo_Descripcion'] ?? ''
                            );
                        }
                    }

                    $style = self::turnoStyle($valor);

                    if (!empty($style)) {
                        $sheet->setCellValue($celda, $style['label']);
                        $sheet->getStyle($celda)->applyFromArray([
                            'font'      => ['bold'   => true, 'italic' => true,
                                            'size'   => 8,
                                            'color'  => ['argb' => $style['fg']]],
                            'fill'      => ['fillType'   => Fill::FILL_SOLID,
                                            'startColor' => ['argb' => $style['bg']]],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                            'vertical'   => Alignment::VERTICAL_CENTER],
                            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN,
                                                          'color' => ['argb' => 'FF8EA9C1']]],
                        ]);
                    } else {
                        $esFinSemana = ($diaSem === 0 || $diaSem === 6);
                        $bg = $esFinSemana ? 'FFFFF5F5' : $rowBg;
                        $sheet->getStyle($celda)->applyFromArray([
                            'fill'    => ['fillType'   => Fill::FILL_SOLID,
                                          'startColor' => ['argb' => $bg]],
                            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN,
                                                        'color' => ['argb' => 'FFD0DCE8']]],
                        ]);
                    }
                }

                $rowIndex++;
            }
        }

        // Borde outline del bloque completo
        $sheet->getStyle("A3:{$colUltima}" . ($rowIndex - 1))->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM,
                                        'color'       => ['argb' => 'FF1F3864']]],
        ]);

        // ════════════════════════════════════════════════════════════════════
        // LEYENDA – 1 fila en blanco de separación, luego leyenda
        // ════════════════════════════════════════════════════════════════════
        $rowLeyenda = $rowIndex + 1;
        $leyendas = [
            ['TN', self::C_TN_BG,   self::C_TN_FG,   'Turno Noche'],
            ['TD', self::C_TD_BG,   self::C_TD_FG,   'Turno Día'],
            ['C',  self::C_COMP_BG, self::C_COMP_FG, 'Compensación'],
            ['O',  self::C_ONOM_BG, self::C_ONOM_FG, 'Onomástico'],
            ['D',  self::C_DESC_BG, self::C_DESC_FG, 'Descanso Semanal'],
        ];

        $colL = $colDia1Idx; // empieza desde columna B
        foreach ($leyendas as $ley) {
            $cSim  = Coordinate::stringFromColumnIndex($colL)   . $rowLeyenda;
            $cTxt1 = Coordinate::stringFromColumnIndex($colL+1) . $rowLeyenda;
            $cTxt2 = Coordinate::stringFromColumnIndex($colL+5) . $rowLeyenda;
            $sheet->mergeCells("{$cTxt1}:{$cTxt2}");

            $sheet->setCellValue($cSim, $ley[0]);
            $sheet->getStyle($cSim)->applyFromArray([
                'font'      => ['bold' => true, 'italic' => true, 'size' => 9,
                                'color' => ['argb' => $ley[2]]],
                'fill'      => ['fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => $ley[1]]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER],
                'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN,
                                              'color' => ['argb' => 'FF888888']]],
            ]);

            $sheet->setCellValue($cTxt1, $ley[3]);
            $sheet->getStyle($cTxt1)->applyFromArray([
                'font'      => ['italic' => true, 'size' => 8],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                                'vertical'   => Alignment::VERTICAL_CENTER],
            ]);

            $colL += 7;
        }
        $sheet->getRowDimension($rowLeyenda)->setRowHeight(15);

        // ════════════════════════════════════════════════════════════════════
        // CONGELAR PANELES Y CONFIGURACIÓN DE PÁGINA
        // ════════════════════════════════════════════════════════════════════
        $sheet->freezePane('B4'); // congela col A + filas 1-3

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
        $sheet->getSheetView()->setZoomScale(90);

        // ════════════════════════════════════════════════════════════════════
        // OUTPUT
        // ════════════════════════════════════════════════════════════════════
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Horario_Ejecutado.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}