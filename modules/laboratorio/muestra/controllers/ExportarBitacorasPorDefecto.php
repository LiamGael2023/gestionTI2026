<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';
require_once $base_path . '/modules/laboratorio/muestra/models/MuestraModel.php';

Auth::check();

$autoloadLibs = $base_path . '/libs/vendor/autoload.php';
if (!file_exists($autoloadLibs)) {
    http_response_code(500);
    die('No se encontro la libreria de exportacion (libs/vendor/autoload.php)');
}
require_once $autoloadLibs;

$fechaDesde = trim((string)($_GET['fecha_desde'] ?? ''));
$fechaHasta = trim((string)($_GET['fecha_hasta'] ?? ''));
if ($fechaDesde === '' || $fechaHasta === '') {
    http_response_code(400);
    die('Debe indicar fecha desde y fecha hasta');
}

$conn = Conexion::conectar();
require_once $base_path . '/modules/laboratorio/models/LaboratorioModel.php';
$labAuthExp = new LaboratorioModel($conn);
$labAuthExp->denegarSiSinPermiso($_SESSION['usuario_id'], '?module=laboratorio&action=muestra', 'exportar');
if (!$conn) {
    http_response_code(500);
    die('Error de conexion');
}

$model = new MuestraModel($conn);

try {
    $rows = $model->obtenerDetalleBitacorasPorDefectoParaExportacion($fechaDesde, $fechaHasta);
    if (empty($rows)) {
        http_response_code(404);
        die('No hay bitacoras en el rango seleccionado');
    }

    $normalize = static function ($text) {
        $txt = strtolower(trim((string)$text));
        return preg_replace('/[^a-z0-9]/', '', $txt);
    };

    $formatNumero = static function ($valor) {
        if ($valor === null) {
            return '';
        }
        $txt = trim((string)$valor);
        if ($txt === '') {
            return '';
        }
        if (!is_numeric($txt)) {
            return $txt;
        }
        $num = (float)$txt;
        $fmt = rtrim(rtrim(number_format($num, 4, '.', ''), '0'), '.');
        return $fmt === '-0' ? '0' : $fmt;
    };

    $buildLimite = static function ($min, $max) use ($formatNumero) {
        $minTxt = $formatNumero($min);
        $maxTxt = $formatNumero($max);

        $hasMin = $minTxt !== '';
        $hasMax = $maxTxt !== '';

        if ($hasMin && $hasMax) {
            return $minTxt . '-' . $maxTxt;
        }
        if ($hasMax) {
            return '<= ' . $maxTxt;
        }
        if ($hasMin) {
            return '>= ' . $minTxt;
        }
        return '';
    };

    $buildTituloParametro = static function ($parametro, $unidad) {
        $nombre = trim((string)$parametro);
        $unidadTxt = trim((string)$unidad);

        if ($nombre === '') {
            return '';
        }
        if ($unidadTxt === '') {
            return $nombre;
        }
        $normNombre = preg_replace('/[^a-z0-9]/', '', strtolower($nombre));
        $normUnidad = preg_replace('/[^a-z0-9]/', '', strtolower($unidadTxt));
        if ($normUnidad !== '' && ($normUnidad === $normNombre || strpos($normNombre, $normUnidad) !== false)) {
            return $nombre;
        }
        if (preg_match('/\([^\)]*\)$/', $nombre)) {
            return $nombre;
        }
        return $nombre . ' (' . $unidadTxt . ')';
    };

    $knownParams = [
        'ph' => [
            'title' => 'pH',
            'unit' => '',
            'lmp' => 'LMP: 6.5-8.5',
            'aliases' => ['ph'],
            'order' => 1,
        ],
        'ce' => [
            'title' => 'C.E.',
            'unit' => 'us/cm',
            'lmp' => 'LMP: <= 1500',
            'aliases' => ['ce', 'conductividad', 'conductividadelectrica'],
            'order' => 2,
        ],
        'turbidez' => [
            'title' => 'Turbidez',
            'unit' => 'NTU',
            'lmp' => 'LMP: <= 5',
            'aliases' => ['turbidez'],
            'order' => 3,
        ],
        'cloro' => [
            'title' => 'Cloro Residual',
            'unit' => 'ppm',
            'lmp' => 'LMP: 0.5-1.5',
            'aliases' => ['clororesidual', 'cloro'],
            'order' => 4,
        ],
        'nitrato' => [
            'title' => 'Nitrato',
            'unit' => 'Ntr',
            'lmp' => 'LMP: <= 50',
            'aliases' => ['nitrato', 'nitratos', 'no3'],
            'order' => 5,
        ],
    ];

    $resolverClaveParametro = static function ($paramNorm) use ($knownParams) {
        foreach ($knownParams as $key => $meta) {
            foreach ($meta['aliases'] as $alias) {
                if ($paramNorm !== '' && strpos($paramNorm, $alias) !== false) {
                    return $key;
                }
            }
        }
        return null;
    };

    $porFecha = [];
    $paramMeta = [];
    $paramsConResultado = [];

    foreach ($rows as $row) {
        $fecha = trim((string)($row['Fecha'] ?? ''));
        $turno = trim((string)($row['Turno'] ?? ''));

        if ($fecha === '' || ($turno !== 'Mañana' && $turno !== 'Tarde')) {
            continue;
        }

        if (!isset($porFecha[$fecha])) {
            $porFecha[$fecha] = [
                'fecha' => $fecha,
                'turnos' => [
                    'Mañana' => ['observacion' => '', 'samples' => []],
                    'Tarde' => ['observacion' => '', 'samples' => []],
                ],
            ];
        }

        $observacion = trim((string)($row['Observacion_General'] ?? ''));
        if ($observacion !== '') {
            $porFecha[$fecha]['turnos'][$turno]['observacion'] = $observacion;
        }

        $idMuestra = intval($row['Id_Muestra'] ?? 0);
        if ($idMuestra > 0) {
            if (!isset($porFecha[$fecha]['turnos'][$turno]['samples'][$idMuestra])) {
                $porFecha[$fecha]['turnos'][$turno]['samples'][$idMuestra] = [
                    'id_muestra' => $idMuestra,
                    'ubicacion_punto' => trim((string)($row['Ubicacion_Punto'] ?? '')),
                    'punto_toma' => trim((string)($row['Punto_Toma'] ?? '')),
                    'hora_muestreo' => trim((string)($row['Hora_Muestreo'] ?? '')),
                    'parametros' => [],
                ];
            }

            $parametro = trim((string)($row['Parametro'] ?? ''));
            if ($parametro !== '') {
                $unidad = trim((string)($row['Unidad'] ?? ''));
                $limiteDinamico = $buildLimite($row['Valor_Min'] ?? null, $row['Valor_Max'] ?? null);
                $paramNorm = $normalize($parametro);
                $knownKey = $resolverClaveParametro($paramNorm);

                if ($knownKey !== null) {
                    $paramKey = $knownKey;
                    $unidadFinal = $unidad !== '' ? $unidad : (string)($knownParams[$knownKey]['unit'] ?? '');
                    $tituloFinal = $buildTituloParametro($knownParams[$knownKey]['title'], $unidadFinal);
                    $lmpFinal = $limiteDinamico !== ''
                        ? 'LMP: ' . $limiteDinamico
                        : (string)$knownParams[$knownKey]['lmp'];

                    if (!isset($paramMeta[$paramKey])) {
                        $paramMeta[$paramKey] = [
                            'title' => $tituloFinal,
                            'lmp' => $lmpFinal,
                            'order' => $knownParams[$knownKey]['order'],
                        ];
                    } else {
                        if ($tituloFinal !== '' && strpos((string)$paramMeta[$paramKey]['title'], '(') === false && strpos($tituloFinal, '(') !== false) {
                            $paramMeta[$paramKey]['title'] = $tituloFinal;
                        }
                        if ($limiteDinamico !== '') {
                            $paramMeta[$paramKey]['lmp'] = 'LMP: ' . $limiteDinamico;
                        }
                    }
                } else {
                    $idParametro = intval($row['Id_Parametro'] ?? 0);
                    $paramKey = $idParametro > 0 ? ('id_' . $idParametro) : ('extra_' . ($paramNorm !== '' ? $paramNorm : md5($parametro)));
                    $tituloFinal = $buildTituloParametro($parametro, $unidad);

                    if (!isset($paramMeta[$paramKey])) {
                        $paramMeta[$paramKey] = [
                            'title' => $tituloFinal !== '' ? $tituloFinal : $parametro,
                            'lmp' => $limiteDinamico !== '' ? ('LMP: ' . $limiteDinamico) : '',
                            'order' => 999,
                        ];
                    } else {
                        if ($tituloFinal !== '' && strpos((string)$paramMeta[$paramKey]['title'], '(') === false && strpos($tituloFinal, '(') !== false) {
                            $paramMeta[$paramKey]['title'] = $tituloFinal;
                        }
                        if ($limiteDinamico !== '' && trim((string)$paramMeta[$paramKey]['lmp']) === '') {
                            $paramMeta[$paramKey]['lmp'] = 'LMP: ' . $limiteDinamico;
                        }
                    }
                }

                $valorHallado = trim((string)($row['Valor_Hallado'] ?? ''));
                $porFecha[$fecha]['turnos'][$turno]['samples'][$idMuestra]['parametros'][$paramKey] = $valorHallado !== '' ? $valorHallado : '-';

                // Solo habilita columnas para parámetros con resultado real.
                if ($valorHallado !== '') {
                    $paramsConResultado[$paramKey] = true;
                }
            }
        }
    }

    if (empty($porFecha)) {
        http_response_code(404);
        die('No hay datos válidos para exportar en el rango seleccionado');
    }

    ksort($porFecha);

    $paramCols = [];
    foreach (array_keys($paramsConResultado) as $pkey) {
        if (!isset($paramMeta[$pkey])) {
            continue;
        }
        $paramCols[] = [
            'key' => $pkey,
            'title' => $paramMeta[$pkey]['title'],
            'lmp' => $paramMeta[$pkey]['lmp'],
            'order' => $paramMeta[$pkey]['order'],
        ];
    }

    usort($paramCols, static function ($a, $b) {
        if (($a['order'] ?? 999) === ($b['order'] ?? 999)) {
            return strcasecmp((string)$a['title'], (string)$b['title']);
        }
        return (($a['order'] ?? 999) < ($b['order'] ?? 999)) ? -1 : 1;
    });

    $fechaRevision = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    if (!$fechaRevision) {
        $fechaRevision = DateTime::createFromFormat('d/m/Y', $fechaHasta);
    }
    if (!$fechaRevision) {
        $fechaRevision = new DateTime('now');
    }
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];
    $numeroMesRevision = intval($fechaRevision->format('n'));
    $textoMesAnioRevision = ucfirst($meses[$numeroMesRevision] ?? 'enero') . ' ' . $fechaRevision->format('Y');

    $crearMuestraPlantilla = static function ($sample) {
        return [
            'id_muestra' => intval($sample['id_muestra'] ?? 0),
            'ubicacion_punto' => trim((string)($sample['ubicacion_punto'] ?? '-')),
            'punto_toma' => trim((string)($sample['punto_toma'] ?? '-')),
            'hora_muestreo' => '-',
            'parametros' => [],
        ];
    };

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Bitacoras PTA');

    // Columna izquierda en blanco (A), tal como pidió el usuario.
    $startColIndex = 2; // B
    $fechaColIdx = $startColIndex;
    $turnoColIdx = $startColIndex + 1;
    $ubicColIdx = $startColIndex + 2;
    $puntoColIdx = $startColIndex + 3;
    $horaColIdx = $startColIndex + 4;
    $firstParamColIdx = $startColIndex + 5;
    $obsColIndex = $firstParamColIdx + count($paramCols);

    $colLetter = static function ($index) {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    };

    $startCol = $colLetter($startColIndex);
    $endCol = $colLetter($obsColIndex);

    $fechaCol = $colLetter($fechaColIdx);
    $turnoCol = $colLetter($turnoColIdx);
    $ubicCol = $colLetter($ubicColIdx);
    $puntoCol = $colLetter($puntoColIdx);
    $horaCol = $colLetter($horaColIdx);
    $obsCol = $colLetter($obsColIndex);

    $sheet->setCellValue($startCol . '2', 'REGISTRO DE PARÁMETROS FISICO-QUÍMICO PLANTA DE TRATAMIENTO DE AGUA (PTA) CAMPAMENTO SAN JOSÉ');
    $sheet->mergeCells($startCol . '2:' . $endCol . '2');

    $sheet->setCellValue($startCol . '3', 'PARÁMETROS MEDIDOS EN LABORATORIO');
    $sheet->mergeCells($startCol . '3:' . $endCol . '3');

    $sheet->setCellValue($fechaCol . '5', 'Fecha de muestreo');
    $sheet->setCellValue($turnoCol . '5', 'Turno');
    $sheet->setCellValue($ubicCol . '5', 'Ubicación del punto de muestreo');
    $sheet->setCellValue($puntoCol . '5', 'Punto de toma de la muestra');
    $sheet->setCellValue($horaCol . '5', 'Hora de muestreo');

    $colIdx = $firstParamColIdx;
    foreach ($paramCols as $pcol) {
        $pLetter = $colLetter($colIdx);
        $sheet->setCellValue($pLetter . '5', $pcol['title']);
        $sheet->setCellValue($pLetter . '6', $pcol['lmp']);
        $colIdx++;
    }

    $sheet->setCellValue($obsCol . '5', 'Observaciones');

    $sheet->mergeCells($fechaCol . '5:' . $fechaCol . '6');
    $sheet->mergeCells($turnoCol . '5:' . $turnoCol . '6');
    $sheet->mergeCells($ubicCol . '5:' . $ubicCol . '6');
    $sheet->mergeCells($puntoCol . '5:' . $puntoCol . '6');
    $sheet->mergeCells($horaCol . '5:' . $horaCol . '6');
    $sheet->mergeCells($obsCol . '5:' . $obsCol . '6');

    $dataRow = 7;
    $colorTurno = [
        'Mañana' => 'FFD9D9D9',
        'Tarde' => 'FFC7C7C7',
    ];

    foreach ($porFecha as $fechaData) {
        $fecha = $fechaData['fecha'];
        $fechaStart = $dataRow;

        $samplesMananaOriginal = array_values($fechaData['turnos']['Mañana']['samples'] ?? []);
        $samplesTardeOriginal = array_values($fechaData['turnos']['Tarde']['samples'] ?? []);
        $mananaSinMuestras = empty($samplesMananaOriginal);
        $tardeSinMuestras = empty($samplesTardeOriginal);

        $samplesManana = $samplesMananaOriginal;
        $samplesTarde = $samplesTardeOriginal;

        if (empty($samplesManana) && !empty($samplesTarde)) {
            $samplesManana = array_map($crearMuestraPlantilla, $samplesTarde);
        }
        if (empty($samplesTarde) && !empty($samplesManana)) {
            $samplesTarde = array_map($crearMuestraPlantilla, $samplesManana);
        }
        if (empty($samplesManana) && empty($samplesTarde)) {
            $placeholder = [[
                'id_muestra' => 0,
                'ubicacion_punto' => '-',
                'punto_toma' => '-',
                'hora_muestreo' => '-',
                'parametros' => [],
            ]];
            $samplesManana = $placeholder;
            $samplesTarde = $placeholder;
        }

        $turnos = [
            'Mañana' => $samplesManana,
            'Tarde' => $samplesTarde,
        ];

        foreach ($turnos as $turnoNombre => $samples) {
            usort($samples, static function ($a, $b) {
                return strcmp((string)($a['hora_muestreo'] ?? ''), (string)($b['hora_muestreo'] ?? ''));
            });

            $turnoStart = $dataRow;

            foreach ($samples as $sample) {
                $sheet->setCellValue($ubicCol . $dataRow, trim((string)($sample['ubicacion_punto'] ?? '')) !== '' ? $sample['ubicacion_punto'] : '-');
                $sheet->setCellValue($puntoCol . $dataRow, trim((string)($sample['punto_toma'] ?? '')) !== '' ? $sample['punto_toma'] : '-');
                $sheet->setCellValue($horaCol . $dataRow, trim((string)($sample['hora_muestreo'] ?? '')) !== '' ? $sample['hora_muestreo'] : '-');

                $colIdx = $firstParamColIdx;
                foreach ($paramCols as $pcol) {
                    $pLetter = $colLetter($colIdx);
                    $valor = $sample['parametros'][$pcol['key']] ?? '-';
                    $sheet->setCellValue($pLetter . $dataRow, $valor);
                    $colIdx++;
                }

                $dataRow++;
            }

            $turnoEnd = $dataRow - 1;
            $sheet->setCellValue($turnoCol . $turnoStart, $turnoNombre);
            if ($turnoEnd > $turnoStart) {
                $sheet->mergeCells($turnoCol . $turnoStart . ':' . $turnoCol . $turnoEnd);
            }

            $sheet->getStyle($startCol . $turnoStart . ':' . $endCol . $turnoEnd)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => $colorTurno[$turnoNombre] ?? 'FFD9D9D9'],
                ],
            ]);
        }

        $observacionMostrar = '';
        if ($mananaSinMuestras xor $tardeSinMuestras) {
            $turnoSinMuestras = $mananaSinMuestras ? 'Mañana' : 'Tarde';
            $obsTurno = trim((string)($fechaData['turnos'][$turnoSinMuestras]['observacion'] ?? ''));
            $observacionMostrar = 'Turno ' . $turnoSinMuestras . ': ' . ($obsTurno !== '' ? $obsTurno : '(sin observación)');
        }

        $fechaEnd = $dataRow - 1;
        $sheet->setCellValue($fechaCol . $fechaStart, $fecha);
        if ($fechaEnd > $fechaStart) {
            $sheet->mergeCells($fechaCol . $fechaStart . ':' . $fechaCol . $fechaEnd);
            $sheet->mergeCells($obsCol . $fechaStart . ':' . $obsCol . $fechaEnd);
        }
        $sheet->setCellValue($obsCol . $fechaStart, $observacionMostrar);
    }

    $lastDataRow = max(7, $dataRow - 1);

    $sheet->getColumnDimension('A')->setWidth(3);
    $sheet->getColumnDimension($fechaCol)->setWidth(14);
    $sheet->getColumnDimension($turnoCol)->setWidth(10);
    $sheet->getColumnDimension($ubicCol)->setWidth(24);
    $sheet->getColumnDimension($puntoCol)->setWidth(22);
    $sheet->getColumnDimension($horaCol)->setWidth(12);

    $colIdx = $firstParamColIdx;
    foreach ($paramCols as $_pcol) {
        $sheet->getColumnDimension($colLetter($colIdx))->setWidth(10);
        $colIdx++;
    }
    $sheet->getColumnDimension($obsCol)->setWidth(34);

    $sheet->getRowDimension(2)->setRowHeight(20);
    $sheet->getRowDimension(3)->setRowHeight(20);
    $sheet->getRowDimension(5)->setRowHeight(46);
    $sheet->getRowDimension(6)->setRowHeight(28);

    $sheet->getStyle($startCol . '2:' . $endCol . '2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FF000000'],
        ],
    ]);

    $sheet->getStyle($startCol . '3:' . $endCol . '3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FF000000'],
        ],
    ]);

    $sheet->getStyle($startCol . '5:' . $endCol . '5')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FF000000'],
        ],
    ]);

    $sheet->getStyle($startCol . '6:' . $endCol . '6')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FF000000'],
        ],
    ]);

    $sheet->getStyle($startCol . '7:' . $endCol . $lastDataRow)->applyFromArray([
        'font' => ['size' => 12],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ]);

    $sheet->getStyle($obsCol . '7:' . $obsCol . $lastDataRow)->applyFromArray([
        'font' => ['color' => ['argb' => 'FF000000']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFFFFFFF'],
        ],
    ]);

    $sheet->getStyle($fechaCol . '7:' . $fechaCol . $lastDataRow)->applyFromArray([
        'font' => ['color' => ['argb' => 'FF000000']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFFFFFFF'],
        ],
    ]);

    $sheet->getStyle($startCol . '2:' . $endCol . $lastDataRow)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ]);

    $footerRow = $lastDataRow + 3;

    $setMergedText = static function ($sheetObj, $startIdx, $endIdx, $row, $text) use ($colLetter) {
        $start = $colLetter($startIdx);
        $end = $colLetter($endIdx);
        $sheetObj->setCellValue($start . $row, $text);
        if ($endIdx > $startIdx) {
            $sheetObj->mergeCells($start . $row . ':' . $end . $row);
        }
    };

    $totalCols = $obsColIndex - $startColIndex + 1;
    $seg1End = min($startColIndex + 2, $obsColIndex);
    $seg2Start = min($seg1End + 1, $obsColIndex);
    $seg2End = min($seg2Start + max(2, intval($totalCols / 3)), $obsColIndex);
    $seg3Start = min($seg2End + 1, $obsColIndex);

    $setMergedText($sheet, $startColIndex, $seg1End, $footerRow, 'CSJ-DRDYCS-LAYS - R - 3');
    $setMergedText($sheet, $seg2Start, $seg2End, $footerRow, 'PROYECTO ESPECIAL CHAVIMOCHIC' . PHP_EOL . '"Prohibida su reproducción"');
    $setMergedText($sheet, $seg3Start, $obsColIndex, $footerRow, 'REVISIÓN: 01' . PHP_EOL . $textoMesAnioRevision);

    $sheet->getRowDimension($footerRow)->setRowHeight(34);
    $sheet->getStyle($startCol . $footerRow . ':' . $endCol . $footerRow)->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ]);

    $sheet->freezePane($startCol . '7');

    $sheet->getPageSetup()
        ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
        ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
        ->setFitToWidth(1)
        ->setFitToHeight(0);

    $sheet->getPageMargins()
        ->setTop(0.25)
        ->setRight(0.25)
        ->setLeft(0.25)
        ->setBottom(0.35);

    $fileName = 'bitacoras_por_defecto_' . str_replace('-', '', $fechaDesde) . '_' . str_replace('-', '', $fechaHasta) . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    die('Error al exportar bitacoras por defecto: ' . $e->getMessage());
}
