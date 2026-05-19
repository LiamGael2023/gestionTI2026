<?php
require_once 'modules/adquisiciones/models/RequerimientoModel.php';
require_once 'modules/adquisiciones/models/CatalogoTecnologicoModel.php';
require_once 'modules/adquisiciones/models/CierreAdquisicionModel.php';
require_once 'modules/adquisiciones/helpers.php';

// Carga la vista de requerimientos con filtros por año y opciones base
function cargarVistaRequerimientos($model, $anioFiltro, &$vistaActual, &$requerimientos, &$centrosCosto, &$aniosDisponibles, &$metasSiafActivas)
{
	$vistaActual = 'requerimientos';
	$centrosCosto = $model->obtenerCentrosCosto();
	$metasSiafActivas = $model->obtenerMetasSiafActivas();
	$aniosDisponibles = $model->obtenerAniosDisponibles();
	$anioFiltro = resolverAnioFiltro($anioFiltro, $aniosDisponibles);
	$requerimientos = $model->listarRequerimientos($anioFiltro);

	return $anioFiltro;
}

// Resuelve el año de filtro priorizando el año solicitado o el año actual o el primer año disponible
function resolverAnioFiltro($anioSolicitado, array $aniosDisponibles)
{
	if ($anioSolicitado !== null && $anioSolicitado > 0) {
		return $anioSolicitado;
	}

	$anioActual = (int) date('Y');
	if (in_array($anioActual, $aniosDisponibles, true)) {
		return $anioActual;
	}

	if (!empty($aniosDisponibles)) {
		return (int) $aniosDisponibles[0];
	}

	return $anioActual;
}

// Delega la ejecución a otro controlador específico incluyendo su archivo
function delegarControladorAdquisiciones($ruta)
{
	include $ruta;
	exit;
}

// Genera y descarga un archivo XLSX con el consolidado de equipos por centro de costo (formato no oficial)
function descargarConsolidadoNoOficialXlsx($model, $anioSolicitado)
{
	// Evita que avisos E_DEPRECATED de dependencias antiguas contaminen la salida binaria del XLSX.
	error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
	@ini_set('display_errors', '0');

	$autoload = 'libs/vendor/autoload.php';
	if (!file_exists($autoload)) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=UTF-8');
		echo 'No se encontró el autoload de Composer en libs/vendor/autoload.php';
		return;
	}

	require_once $autoload;

	$aniosDisponibles = $model->obtenerAniosDisponibles();
	$anioConsulta = resolverAnioFiltro($anioSolicitado, $aniosDisponibles);
	$consolidado = $model->obtenerConsolidado($anioConsulta);
	$cabeceraExportacion = $model->obtenerCabeceraConsolidadoCompleta();

	$equipos = array_values($consolidado['equipos'] ?? []);
	$matriz = $consolidado['matriz'] ?? [];
	$tiposSolicitudPorEquipo = $consolidado['tiposSolicitudPorEquipo'] ?? [];
	$cabeceraCentros = $cabeceraExportacion['cabeceraCentros'] ?? [];

	$columnasAgrupadas = [];
	$columnKeys = [];
	foreach ($cabeceraCentros as $grupo) {
		$labelGrupo = trim((string) ($grupo['label'] ?? ''));
		$columnas = is_array($grupo['columnas'] ?? null) ? $grupo['columnas'] : [];
		$columnasValidas = [];
		foreach ($columnas as $col) {
			$key = trim((string) ($col['key'] ?? ''));
			$label = trim((string) ($col['label'] ?? ''));
			if ($key === '') {
				continue;
			}
			$columnasValidas[] = ['key' => $key, 'label' => $label !== '' ? $label : $key];
			$columnKeys[] = $key;
		}
		if (!empty($columnasValidas)) {
			$columnasAgrupadas[] = [
				'label' => $labelGrupo,
				'columnas' => $columnasValidas,
			];
		}
	}

	usort($equipos, static function ($a, $b) use ($tiposSolicitudPorEquipo) {
		$equipoA = (string) $a;
		$equipoB = (string) $b;
		$tipoA = strtoupper(trim((string) ($tiposSolicitudPorEquipo[$equipoA] ?? '')));
		$tipoB = strtoupper(trim((string) ($tiposSolicitudPorEquipo[$equipoB] ?? '')));

		if ($tipoA !== $tipoB) {
			if ($tipoA === '') {
				return 1;
			}
			if ($tipoB === '') {
				return -1;
			}
			return strcmp($tipoA, $tipoB);
		}

		return strcmp(strtoupper($equipoA), strtoupper($equipoB));
	});

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(9);
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle('Consolidado');

	$totalCols = 3 + count($columnKeys);
	$lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

	$sheet->setCellValue('A1', 'CONSOLIDADO DE EQUIPOS POR CENTRO DE COSTO');
	$sheet->mergeCells('A1:' . $lastColLetter . '1');
	$sheet->setCellValue('A2', 'ANIO: ' . (string) $anioConsulta);
	$sheet->mergeCells('A2:' . $lastColLetter . '2');

	$sheet->setCellValue('A4', 'EQUIPO');
	$sheet->setCellValue('B4', 'TIPO DE SOLICITUD');
	$sheet->mergeCells('A4:A5');
	$sheet->mergeCells('B4:B5');

	$colActual = 3;
	foreach ($columnasAgrupadas as $grupo) {
		$cols = $grupo['columnas'];
		if (count($cols) > 1) {
			$inicio = $colActual;
			$fin = $colActual + count($cols) - 1;
			$sheet->setCellValueByColumnAndRow($inicio, 4, strtoupper((string) ($grupo['label'] ?? '')));
			$sheet->mergeCells(
				\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($inicio) . '4:' .
				\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($fin) . '4'
			);
			foreach ($cols as $columna) {
				$sheet->setCellValueByColumnAndRow($colActual, 5, strtoupper((string) ($columna['label'] ?? '')));
				$colActual++;
			}
			continue;
		}

		$labelSimple = strtoupper((string) ($cols[0]['label'] ?? ($grupo['label'] ?? '')));
		$sheet->setCellValueByColumnAndRow($colActual, 4, $labelSimple);
		$sheet->mergeCells(
			\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colActual) . '4:' .
			\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colActual) . '5'
		);
		$colActual++;
	}

	$sheet->setCellValueByColumnAndRow($colActual, 4, 'TOTAL');
	$sheet->mergeCells(
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colActual) . '4:' .
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colActual) . '5'
	);

	$filaData = 6;
	$totalesPorColumna = array_fill_keys($columnKeys, 0);
	$totalGeneral = 0;

	foreach ($equipos as $equipo) {
		$equipoKey = (string) $equipo;
		$tipoSolicitud = (string) ($tiposSolicitudPorEquipo[$equipoKey] ?? '');
		$sheet->setCellValueByColumnAndRow(1, $filaData, $equipoKey);
		$sheet->setCellValueByColumnAndRow(2, $filaData, strtoupper(trim($tipoSolicitud)));

		$totalFila = 0;
		$colData = 3;
		foreach ($columnKeys as $key) {
			$valor = (int) (($matriz[$equipoKey][$key] ?? 0));
			if ($valor > 0) {
				$sheet->setCellValueByColumnAndRow($colData, $filaData, $valor);
			}
			$totalesPorColumna[$key] += $valor;
			$totalFila += $valor;
			$colData++;
		}

		if ($totalFila > 0) {
			$sheet->setCellValueByColumnAndRow($colData, $filaData, $totalFila);
		}
		$totalGeneral += $totalFila;
		$filaData++;
	}

	$sheet->setCellValueByColumnAndRow(1, $filaData, 'TOTAL');
	$sheet->setCellValueByColumnAndRow(2, $filaData, '');
	$colTotal = 3;
	foreach ($columnKeys as $key) {
		$sheet->setCellValueByColumnAndRow($colTotal, $filaData, (int) ($totalesPorColumna[$key] ?? 0));
		$colTotal++;
	}
	$sheet->setCellValueByColumnAndRow($colTotal, $filaData, $totalGeneral);

	$sheet->getStyle('A1:' . $lastColLetter . '1')->getFont()->setBold(true);
	$sheet->getStyle('A4:' . $lastColLetter . '5')->getFont()->setBold(true);
	$sheet->getStyle('A' . $filaData . ':' . $lastColLetter . $filaData)->getFont()->setBold(true);
	$sheet->getStyle('A1:' . $lastColLetter . $filaData)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
	$sheet->getStyle('A4:' . $lastColLetter . '5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('A4:' . $lastColLetter . '5')->getAlignment()->setWrapText(false);
	$sheet->getStyle('A6:A' . $filaData)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
	$sheet->getStyle('B6:B' . $filaData)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
	$sheet->getStyle('A6:B' . $filaData)->getAlignment()->setWrapText(true);
	if ($totalCols >= 3) {
		$primerNumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3);
		$sheet->getStyle($primerNumero . '6:' . $lastColLetter . $filaData)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		$sheet->getStyle($primerNumero . '6:' . $lastColLetter . $filaData)->getFont()->setSize(11);
	}
	$sheet->getStyle('A1:' . $lastColLetter . $filaData)->getFont()->setName('Calibri')->setSize(9);
	if ($totalCols >= 3) {
		$primerNumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3);
		$sheet->getStyle($primerNumero . '6:' . $lastColLetter . $filaData)->getFont()->setSize(11);
	}
	$sheet->getStyle('A4:' . $lastColLetter . $filaData)
		->getBorders()
		->getAllBorders()
		->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
	$sheet->getRowDimension(4)->setRowHeight(20);
	$sheet->getRowDimension(5)->setRowHeight(20);

	// Calculo manual de anchos para evitar que AutoSize colapse columnas en algunos entornos.
	$maxLens = array_fill(1, $totalCols, 0);
	for ($col = 1; $col <= $totalCols; $col++) {
		$valorCab4 = (string) $sheet->getCellByColumnAndRow($col, 4)->getCalculatedValue();
		$valorCab5 = (string) $sheet->getCellByColumnAndRow($col, 5)->getCalculatedValue();
		$maxLens[$col] = max($maxLens[$col], mb_strlen(trim($valorCab4), 'UTF-8'), mb_strlen(trim($valorCab5), 'UTF-8'));
	}

	for ($row = 6; $row <= $filaData; $row++) {
		for ($col = 1; $col <= $totalCols; $col++) {
			$valor = (string) $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
			$len = mb_strlen(trim($valor), 'UTF-8');
			if ($len > $maxLens[$col]) {
				$maxLens[$col] = $len;
			}
		}
	}

	for ($col = 1; $col <= $totalCols; $col++) {
		$letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
		$base = max(1, (int) $maxLens[$col]);

		if ($col === 1) {
			$ancho = max(34, min(60, $base + 3));
		} elseif ($col === 2) {
			$ancho = max(27, min(45, $base + 3));
		} elseif ($col === $totalCols) {
			$ancho = max(9, min(14, $base + 2));
		} else {
			$ancho = max(8.5, min(14, $base + 1.2));
		}

		$sheet->getColumnDimension($letra)->setWidth($ancho);
	}

	$fileName = 'Consolidado_Equipos_' . $anioConsulta . '.xlsx';
	if (function_exists('ob_get_level')) {
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
	}
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="' . $fileName . '"');
	header('Cache-Control: max-age=0');
	header('Cache-Control: max-age=1');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: cache, must-revalidate');
	header('Pragma: public');

	$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
	$writer->save('php://output');
	exit;
}

// Genera y descarga un archivo XLSX con el consolidado oficial incluyendo metas SIAF y cálculos de montos
function descargarConsolidadoOficialXlsx($model, $anioSolicitado)
{
	error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
	@ini_set('display_errors', '0');

	$autoload = 'libs/vendor/autoload.php';
	if (!file_exists($autoload)) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=UTF-8');
		echo 'No se encontró el autoload de Composer en libs/vendor/autoload.php';
		return;
	}

	require_once $autoload;

	$aniosDisponibles = $model->obtenerAniosDisponibles();
	$anioConsulta = resolverAnioFiltro($anioSolicitado, $aniosDisponibles);
	$metasCabecera = $model->obtenerMetasCabeceraConsolidado($anioConsulta);
	$filas = $model->obtenerConsolidadoFormatoOficial($anioConsulta, $metasCabecera);

	$metas = [];
	$vistos = [];
	foreach ($metasCabecera as $meta) {
		$codigo = preg_replace('/[^0-9]/', '', (string) ($meta['CodigoMeta'] ?? ''));
		if ($codigo === '') {
			continue;
		}
		if (strlen($codigo) < 3) {
			$codigo = str_pad($codigo, 3, '0', STR_PAD_LEFT);
		}
		if (strlen($codigo) > 4) {
			$codigo = substr($codigo, -4);
		}
		if (isset($vistos[$codigo])) {
			continue;
		}
		$vistos[$codigo] = true;
		$metas[] = [
			'codigo' => $codigo,
			'nombre' => trim((string) ($meta['Descripcion'] ?? $codigo)),
			'alias' => 'Meta' . str_pad($codigo, 4, '0', STR_PAD_LEFT),
		];
	}

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$spreadsheet->getDefaultStyle()->getFont()->setName('Tahoma')->setSize(9);
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle('Consolidado Oficial');

	$columnasFijas = 7;
	$columnasMetas = count($metas) * 2;
	$indiceInicioMetas = $columnasFijas + 1;
	$indiceFinMetas = $indiceInicioMetas + $columnasMetas - 1;
	$indiceTotalInicial = $indiceFinMetas + 1;
	$indiceMontoTotal = $indiceFinMetas + 2;
	$totalColumnas = $indiceMontoTotal;
	$lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumnas);

	$sheet->setCellValue('A1', 'N');
	$sheet->setCellValue('B1', 'USUARIO ASIGNADO');
	$sheet->setCellValue('C1', 'TIPO DE EQUIPO');
	$sheet->setCellValue('D1', 'DESCRIPCION DEL COMPONENTE');
	$sheet->setCellValue('E1', 'REFERENCIA');
	$sheet->setCellValue('F1', 'UNIDAD DE MEDIDA');
	$sheet->setCellValue('G1', 'PRECIO UNITARIO REFERENCIA');
	$sheet->mergeCells('A1:A2');
	$sheet->mergeCells('B1:B2');
	$sheet->mergeCells('C1:C2');
	$sheet->mergeCells('D1:D2');
	$sheet->mergeCells('E1:E2');
	$sheet->mergeCells('F1:F2');
	$sheet->mergeCells('G1:G2');

	if (!empty($metas)) {
		$sheet->setCellValueByColumnAndRow($indiceInicioMetas, 1, 'METAS SIAF');
		$sheet->mergeCells(
			\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceInicioMetas) . '1:' .
			\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceFinMetas) . '1'
		);
		$colMeta = $indiceInicioMetas;
		foreach ($metas as $meta) {
			$sheet->setCellValueByColumnAndRow($colMeta, 2, $meta['codigo']);
			$sheet->setCellValueByColumnAndRow($colMeta + 1, 2, strtoupper($meta['nombre']));
			$colMeta += 2;
		}
	}

	$sheet->setCellValueByColumnAndRow($indiceTotalInicial, 1, 'TOTAL INICIAL');
	$sheet->setCellValueByColumnAndRow($indiceMontoTotal, 1, 'MONTO TOTAL');
	$sheet->mergeCells(
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceTotalInicial) . '1:' .
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceTotalInicial) . '2'
	);
	$sheet->mergeCells(
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceMontoTotal) . '1:' .
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceMontoTotal) . '2'
	);

	$filaData = 3;
	$contador = 1;
	$totalesCantidades = array_fill(0, count($metas), 0);
	$totalesMontos = array_fill(0, count($metas), 0.0);
	$totalInicialGeneral = 0;
	$montoTotalGeneral = 0.0;

	foreach ($filas as $fila) {
		$tipoCodigo = trim((string) ($fila['TipoCodigo'] ?? ''));
		$tipoNombre = trim((string) ($fila['TipoNombre'] ?? ''));
		$tipoEquipo = $tipoCodigo !== '' && $tipoNombre !== '' ? $tipoCodigo . ': ' . $tipoNombre : ($tipoCodigo !== '' ? $tipoCodigo : $tipoNombre);
		$precioUnitario = isset($fila['PrecioUnitario']) ? (float) $fila['PrecioUnitario'] : 0.0;

		$sheet->setCellValueByColumnAndRow(1, $filaData, $contador);
		$sheet->setCellValueByColumnAndRow(2, $filaData, '');
		$sheet->setCellValueByColumnAndRow(3, $filaData, $tipoEquipo);
		$sheet->setCellValueByColumnAndRow(4, $filaData, trim((string) ($fila['Componente'] ?? '')));
		$sheet->setCellValueByColumnAndRow(5, $filaData, trim((string) ($fila['Referencia'] ?? '')));
		$sheet->setCellValueByColumnAndRow(6, $filaData, trim((string) ($fila['UnidadMedida'] ?? '')));
		$sheet->setCellValueByColumnAndRow(7, $filaData, $precioUnitario > 0 ? $precioUnitario : '');

		$colMeta = $indiceInicioMetas;
		$totalInicialFila = 0;
		$montoTotalFila = 0.0;
		foreach ($metas as $indexMeta => $meta) {
			$cantidad = isset($fila[$meta['alias']]) ? (int) $fila[$meta['alias']] : 0;
			$monto = round($cantidad * $precioUnitario, 2);
			if ($cantidad > 0) {
				$sheet->setCellValueByColumnAndRow($colMeta, $filaData, $cantidad);
			}
			if ($monto > 0) {
				$sheet->setCellValueByColumnAndRow($colMeta + 1, $filaData, $monto);
			}
			$totalesCantidades[$indexMeta] += $cantidad;
			$totalesMontos[$indexMeta] += $monto;
			$totalInicialFila += $cantidad;
			$montoTotalFila += $monto;
			$colMeta += 2;
		}

		if ($totalInicialFila > 0) {
			$sheet->setCellValueByColumnAndRow($indiceTotalInicial, $filaData, $totalInicialFila);
		}
		if ($montoTotalFila > 0) {
			$sheet->setCellValueByColumnAndRow($indiceMontoTotal, $filaData, round($montoTotalFila, 2));
		}

		$totalInicialGeneral += $totalInicialFila;
		$montoTotalGeneral += $montoTotalFila;
		$contador++;
		$filaData++;
	}

	$sheet->setCellValueByColumnAndRow(1, $filaData, 'TOTAL GENERAL');
	$sheet->mergeCells('A' . $filaData . ':G' . $filaData);
	$colMeta = $indiceInicioMetas;
	foreach ($metas as $indexMeta => $meta) {
		if ($totalesCantidades[$indexMeta] > 0) {
			$sheet->setCellValueByColumnAndRow($colMeta, $filaData, $totalesCantidades[$indexMeta]);
		}
		if ($totalesMontos[$indexMeta] > 0) {
			$sheet->setCellValueByColumnAndRow($colMeta + 1, $filaData, round($totalesMontos[$indexMeta], 2));
		}
		$colMeta += 2;
	}
	$sheet->setCellValueByColumnAndRow($indiceTotalInicial, $filaData, $totalInicialGeneral);
	$sheet->setCellValueByColumnAndRow($indiceMontoTotal, $filaData, round($montoTotalGeneral, 2));

	$sheet->getStyle('A1:' . $lastColLetter . '2')->getFont()->setName('Tahoma')->setSize(12)->setBold(true);
	$sheet->getStyle('A1:' . $lastColLetter . $filaData)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
	$sheet->getStyle('A1:' . $lastColLetter . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('A1:' . $lastColLetter . '2')->getAlignment()->setWrapText(true);
	$sheet->getStyle('A1:' . $lastColLetter . $filaData)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
	$sheet->getStyle('A' . $filaData . ':' . $lastColLetter . $filaData)->getFont()->setBold(true);
	$sheet->getRowDimension(1)->setRowHeight(26);
	$sheet->getRowDimension(2)->setRowHeight(150);

	if (!empty($metas)) {
		$metaStartLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceInicioMetas);
		$metaEndLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceFinMetas);
		$sheet->getStyle($metaStartLetter . '2:' . $metaEndLetter . '2')->getAlignment()->setTextRotation(90);
		$sheet->getStyle($metaStartLetter . '2:' . $metaEndLetter . '2')->getAlignment()->setWrapText(true);
		$sheet->getStyle($metaStartLetter . '2:' . $metaEndLetter . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		$sheet->getStyle($metaStartLetter . '2:' . $metaEndLetter . '2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

		$colMeta = $indiceInicioMetas;
		foreach ($metas as $meta) {
			$codeLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMeta);
			$nameLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMeta + 1);
			$sheet->getStyle($nameLetter . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
			$sheet->getStyle($nameLetter . '2')->getFill()->getStartColor()->setARGB('FFD9D9D9');
			$sheet->getStyle($codeLetter . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
			$sheet->getStyle($codeLetter . '2')->getFill()->getStartColor()->setARGB('FFFFFFFF');
			$colMeta += 2;
		}
	}

	for ($col = 1; $col <= $totalColumnas; $col++) {
		$letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
		if ($col === 1) {
			$width = 8;
		} elseif ($col === 2) {
			$width = 22;
		} elseif ($col === 3) {
			$width = 34;
		} elseif ($col === 4) {
			$width = 28;
		} elseif ($col === 5) {
			$width = 16;
		} elseif ($col === 6 || $col === 7) {
			$width = 14;
		} elseif ($col === $indiceTotalInicial || $col === $indiceMontoTotal) {
			$width = 14;
		} else {
			$esColumnaCodigoMeta = (($col - $indiceInicioMetas) % 2 === 0);
			$width = $esColumnaCodigoMeta ? 7 : 18;
		}
		$sheet->getColumnDimension($letter)->setWidth($width);
	}

	if (function_exists('ob_get_level')) {
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
	}

	$fileName = 'RESUMEN_Consolidado_Oficial_' . $anioConsulta . '.xlsx';
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="' . $fileName . '"');
	header('Cache-Control: max-age=0');
	header('Cache-Control: max-age=1');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: cache, must-revalidate');
	header('Pragma: public');

	$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
	$writer->save('php://output');
	exit;
}

// Garantiza una conexion activa cuando el controlador se ejecuta de forma directa.
if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

// Inicializa el modelo, la accion solicitada y los datos base de las vistas.
$model = new RequerimientoModel($conn);
$action = $_GET['action'] ?? 'requerimientos';
$vistaActual = 'requerimientos';
$requerimientos = [];
$centrosCosto = [];
$metasSiafActivas = [];
$aniosDisponibles = [];
$anioFiltro = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : null;
$dashboardResumenGeneral = [];
$dashboardItemsPorTipo = [];
$dashboardCentroCosto = [];
$dashboardEstadoDocumental = [];
$dashboardOrdenesProximas = [];
$dashboardMetaSiaf = [];
$dashboardSubCentrosCosto = [];
$idUsuarioSesion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
// Define las acciones que deben resolverse en controladores especializados.
$accionesDetalle = ['guardarDetalleAjax', 'actualizarDetalleAjax', 'eliminarDetalleAjax', 'actualizarEstadoAjax', 'guardarDetalleForm', 'buscarDetallePorCodigoSigaAjax', 'obtenerDistribucionDetalleAjax', 'guardarDistribucionDetalleAjax', 'eliminarDistribucionDetalleAjax'];
$accionesTecnologia = [
	'tecnologia',
	'verEspecificacionTecnicaAjax',
	'guardarEspecificacionTecnicaAjax',
	'actualizarEspecificacionTecnicaAjax',
	'eliminarEspecificacionTecnicaAjax',
	'verOrdenCompraAjax',
	'guardarOrdenCompraAjax',
	'actualizarOrdenCompraAjax',
	'actualizarFechaOrdenCompraAjax',
	'eliminarOrdenCompraAjax',
	'verVerificacionTecnicaAjax',
	'guardarVerificacionTecnicaAjax',
	'actualizarVerificacionTecnicaAjax',
	'eliminarVerificacionTecnicaAjax',
	'verFichaTecnicaAjax',
	'guardarFichaTecnicaAjax',
	'eliminarFichaTecnicaAjax',
	'cambiarEstadoFichaTecnicaAjax',
	'moverFichaTecnicaRangoAjax',
	'obtenerCierreTecnologiaAjax',
	'cambiarCierreTecnologiaAjax',
	'obtenerPresupuestoTecnologiaAjax',
	'guardarPresupuestoTecnologiaAjax',
];

// Redirige la ruta singular antigua al controlador de detalle.
if ($action === 'requerimiento') {
	delegarControladorAdquisiciones('modules/adquisiciones/controllers/DetalleRequerimientoController.php');
}

// Delega las acciones de detalle de requerimiento a su controlador.
if (in_array($action, $accionesDetalle, true)) {
	delegarControladorAdquisiciones('modules/adquisiciones/controllers/DetalleRequerimientoController.php');
}

// Delega las acciones documentales de tecnologia a su controlador.
if (in_array($action, $accionesTecnologia, true)) {
	delegarControladorAdquisiciones('modules/adquisiciones/controllers/EspecificacionTecnicaController.php');
}

// Resuelve la accion principal solicitada para renderizar vistas o responder AJAX.
switch ($action) {
	// Carga la lista de requerimientos con opciones de filtro por año
	case 'index':
	case 'requerimientos':
		$anioFiltro = cargarVistaRequerimientos($model, $anioFiltro, $vistaActual, $requerimientos, $centrosCosto, $aniosDisponibles, $metasSiafActivas);
		break;

	// Carga la lista de tecnologías con estado de fichas técnicas
	case 'tecnologias':
		$vistaActual = 'tecnologias';
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		$aniosTecnologias = $catalogoModel->obtenerAniosDisponibles();
		$anioTecnologiasSolicitado = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : null;
		$anioTecnologias = resolverAnioFiltro($anioTecnologiasSolicitado, $aniosTecnologias);
		$tecnologias = $catalogoModel->listarConEstadoFicha($anioTecnologias);
		break;

	// Carga la vista de consolidado de equipos por centro de costo
	case 'consolidado':
		$vistaActual = 'consolidado';
		$aniosDisponibles = $model->obtenerAniosDisponibles();
		$anioFiltro = resolverAnioFiltro($anioFiltro, $aniosDisponibles);
		$consolidado = $model->obtenerConsolidado($anioFiltro);
		$consolidadoCabeceraExportacion = $model->obtenerCabeceraConsolidadoCompleta();
		break;

	// Obtiene datos del consolidado en formato oficial con metas SIAF vía JSON
	case 'consolidadoFormatoOficialAjax':
		header('Content-Type: application/json');
		$aniosDisponibles = $model->obtenerAniosDisponibles();
		$anioSolicitud = isset($_POST['anio']) && $_POST['anio'] !== '' ? (int) $_POST['anio'] : $anioFiltro;
		$anioConsulta = resolverAnioFiltro($anioSolicitud, $aniosDisponibles);
		$metasCabecera = $model->obtenerMetasCabeceraConsolidado($anioConsulta);
		$filas = $model->obtenerConsolidadoFormatoOficial($anioConsulta, $metasCabecera);
		echo json_encode([
			'success' => true,
			'anio' => (int) $anioConsulta,
			'metasCabecera' => $metasCabecera,
			'filas' => $filas,
		]);
		exit;

	// Descarga consolidado oficial en formato XLSX
	case 'consolidadoOficialXlsxAjax':
		descargarConsolidadoOficialXlsx($model, $anioFiltro);
		exit;

	// Obtiene la cabecera de exportación del consolidado vía JSON
	case 'consolidadoCabeceraExportacionAjax':
		header('Content-Type: application/json');
		$cabeceraCompleta = $model->obtenerCabeceraConsolidadoCompleta();
		echo json_encode([
			'success' => true,
			'cabeceraCentros' => $cabeceraCompleta['cabeceraCentros'] ?? [],
			'centrosCosto' => $cabeceraCompleta['centrosCosto'] ?? [],
		]);
		exit;

	// Descarga consolidado no oficial en formato XLSX
	case 'consolidadoNoOficialXlsxAjax':
		descargarConsolidadoNoOficialXlsx($model, $anioFiltro);
		exit;

	// Carga la vista del dashboard con resúmenes y estadísticas del año
	case 'dashboard':
		$vistaActual = 'dashboard';
		$aniosDisponibles = $model->obtenerAniosDisponibles();
		$anioFiltro = resolverAnioFiltro($anioFiltro, $aniosDisponibles);
		$dashboardResumenGeneral = $model->obtenerDashboardResumenGeneral($anioFiltro);
		$dashboardItemsPorTipo = $model->obtenerDashboardItemsPorTipo($anioFiltro);
		$dashboardCentroCosto = $model->obtenerDashboardCentroCosto($anioFiltro);
		$dashboardEstadoDocumental = $model->obtenerDashboardEstadoDocumental($anioFiltro);
		$dashboardOrdenesProximas = $model->obtenerDashboardOrdenesProximas($anioFiltro, 30, 6);
		$dashboardMetaSiaf = $model->obtenerDashboardMetaSiafResumen();
		$dashboardTipoSolicitud = $model->obtenerDashboardTipoSolicitudResumen();
		$dashboardSubCentrosCosto = $model->obtenerDashboardSubCentrosCostoResumen();
		$cierreModel = new CierreAdquisicionModel($conn);
		$dashboardFinalizados = $cierreModel->contarFinalizadosPorAnio($anioFiltro);
		break;

	// Guarda un nuevo requerimiento vía AJAX validando duplicados
	case 'guardarAjax':
		header('Content-Type: application/json');

		$datos = [
			'IdCentroCosto' => isset($_POST['IdCentroCosto']) ? (int) $_POST['IdCentroCosto'] : 0,
			'IdMetaSIAF' => isset($_POST['IdMetaSIAF']) && (int) $_POST['IdMetaSIAF'] > 0 ? (int) $_POST['IdMetaSIAF'] : null,
			'NroPedidoCompra' => isset($_POST['NroPedidoCompra']) ? trim($_POST['NroPedidoCompra']) : '',
			'CodigoMeta' => adqNormalizarCodigoMeta($_POST['CodigoMeta'] ?? null),
			'Anio' => isset($_POST['Anio']) ? (int) $_POST['Anio'] : 0,
			'idUsuarioRegistro' => $idUsuarioSesion
		];

		if ($datos['IdCentroCosto'] > 0 && !empty($datos['NroPedidoCompra']) && $datos['Anio'] > 0) {
			$id = $model->guardarRequerimiento($datos);
			if ($id) {
				echo json_encode(['success' => true, 'message' => 'Requerimiento registrado correctamente', 'id' => $id]);
			} else {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				$mensaje = 'No se pudo guardar el requerimiento.';
				if (is_array($errors) && count($errors) > 0) {
					// Error 2627 / 2601: violación de clave única (registro duplicado)
					if (in_array((int) $errors[0]['code'], [2627, 2601], true)) {
						$mensaje = 'Ya existe un requerimiento con el Nro. de Pedido "' . htmlspecialchars($datos['NroPedidoCompra']) . '" para el año ' . $datos['Anio'] . '.';
					}
				}
				echo json_encode(['success' => false, 'message' => $mensaje]);
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
		}
		exit;

	// Actualiza un requerimiento existente vía AJAX
	case 'actualizarAjax':
		header('Content-Type: application/json');

		$id = isset($_POST['Id']) ? (int) $_POST['Id'] : 0;
		$datos = [
			'IdCentroCosto' => isset($_POST['IdCentroCosto']) ? (int) $_POST['IdCentroCosto'] : 0,
			'IdMetaSIAF' => isset($_POST['IdMetaSIAF']) && (int) $_POST['IdMetaSIAF'] > 0 ? (int) $_POST['IdMetaSIAF'] : null,
			'NroPedidoCompra' => isset($_POST['NroPedidoCompra']) ? trim($_POST['NroPedidoCompra']) : '',
			'CodigoMeta' => adqNormalizarCodigoMeta($_POST['CodigoMeta'] ?? null),
			'Anio' => isset($_POST['Anio']) ? (int) $_POST['Anio'] : 0,
			'idUsuarioModifica' => $idUsuarioSesion,
		];

		if ($id > 0 && $datos['IdCentroCosto'] > 0 && !empty($datos['NroPedidoCompra']) && $datos['Anio'] > 0) {
			if ($model->actualizarRequerimiento($id, $datos)) {
				echo json_encode(['success' => true, 'message' => 'Requerimiento actualizado correctamente']);
			} else {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				$mensaje = 'No se pudo actualizar el requerimiento.';
				if (is_array($errors) && count($errors) > 0) {
					if (in_array((int) $errors[0]['code'], [2627, 2601], true)) {
						$mensaje = 'Ya existe un requerimiento con el Nro. de Pedido "' . htmlspecialchars($datos['NroPedidoCompra']) . '" para el año ' . $datos['Anio'] . '.';
					}
				}
				echo json_encode(['success' => false, 'message' => $mensaje]);
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
		}
		exit;

	// Guarda o actualiza un requerimiento mediante formulario HTML con redireccionamiento
	case 'guardarForm':
		$idRequerimiento = isset($_POST['Id']) ? (int) $_POST['Id'] : 0;
		$datos = [
			'IdCentroCosto' => isset($_POST['IdCentroCosto']) ? (int) $_POST['IdCentroCosto'] : 0,
			'IdMetaSIAF' => isset($_POST['IdMetaSIAF']) && (int) $_POST['IdMetaSIAF'] > 0 ? (int) $_POST['IdMetaSIAF'] : null,
			'NroPedidoCompra' => isset($_POST['NroPedidoCompra']) ? trim((string) $_POST['NroPedidoCompra']) : '',
			'CodigoMeta' => adqNormalizarCodigoMeta($_POST['CodigoMeta'] ?? null),
			'Anio' => isset($_POST['Anio']) ? (int) $_POST['Anio'] : 0,
			'idUsuarioRegistro' => $idUsuarioSesion,
			'idUsuarioModifica' => $idUsuarioSesion,
		];

		$anioRedirect = $datos['Anio'] > 0 ? $datos['Anio'] : (int) date('Y');
		$urlRedirect = 'index.php?module=adquisiciones&action=requerimientos&anio=' . $anioRedirect;

		if ($datos['IdCentroCosto'] <= 0 || $datos['NroPedidoCompra'] === '' || $datos['Anio'] <= 0) {
			adqRedirigirSeguro($urlRedirect);
		}

		if ($idRequerimiento > 0) {
			$model->actualizarRequerimiento($idRequerimiento, $datos);
		} else {
			$model->guardarRequerimiento($datos);
		}
		adqRedirigirSeguro($urlRedirect);
		break;

	// Elimina un requerimiento vía AJAX
	case 'eliminarAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

		if ($id > 0 && $model->eliminarRequerimiento($id)) {
			echo json_encode(['success' => true, 'message' => 'Requerimiento eliminado correctamente']);
		} else {
			$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
			$detalle = '';
			if (is_array($errors) && count($errors) > 0) {
				$detalle = ' - ' . $errors[0]['message'];
			}
			echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el requerimiento' . $detalle]);
		}
		exit;

	// Busca pedidos en SIGA para un año específico vía AJAX
	case 'buscarPedidosSigaAjax':
		header('Content-Type: application/json');
		$anio = isset($_POST['anio']) ? (int) $_POST['anio'] : 0;

		if ($anio < 2018 || $anio > 2099) {
			echo json_encode(['success' => false, 'message' => 'Año inválido']);
			exit;
		}

		try {
			$pedidos = $model->buscarPedidosSiga($anio);
			echo json_encode(['success' => true, 'pedidos' => $pedidos]);
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}
		exit;

	// Importa un pedido desde SIGA creando el requerimiento y sus detalles
	case 'importarPedidoSigaAjax':
		header('Content-Type: application/json');
		$nroPedido = isset($_POST['nro_pedido']) ? trim($_POST['nro_pedido']) : '';
		$anio      = isset($_POST['anio']) ? (int) $_POST['anio'] : 0;

		if (empty($nroPedido) || $anio < 2018 || $anio > 2099) {
			echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
			exit;
		}

		try {
			$resultado = $model->importarPedidoSiga($nroPedido, $anio, $idUsuarioSesion);
			echo json_encode([
				'success' => true,
				'items'   => $resultado['items'],
			]);
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}
		exit;

	// Sincroniza la homologación de tecnologías entre sistemas
	case 'sincronizarHomologacionAjax':
		header('Content-Type: application/json');
		try {
			$catalogoModel = new CatalogoTecnologicoModel($conn);
			$resultado     = $catalogoModel->sincronizarHomologacion();
			echo json_encode([
				'success'      => true,
				'nuevos'       => $resultado['nuevos'],
				'actualizados' => $resultado['actualizados'],
			]);
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}
		exit;

	// Agrega una nueva tecnología al catálogo
	case 'agregarTecnologiaAjax':
		header('Content-Type: application/json');
		$codigo = isset($_POST['codigo']) ? trim((string) $_POST['codigo']) : '';
		$nombreGenerico = isset($_POST['nombreGenerico']) ? trim((string) $_POST['nombreGenerico']) : '';

		if ($codigo === '' || $nombreGenerico === '') {
			echo json_encode([
				'success' => false,
				'message' => 'Debe completar codigo y nombre generico.',
			]);
			exit;
		}

		$catalogoModel = new CatalogoTecnologicoModel($conn);
		$resultado = $catalogoModel->agregarTecnologia($codigo, $nombreGenerico);
		echo json_encode($resultado);
		exit;

	// Lista todos los centros de costo disponibles
	case 'listarCentrosCostoAjax':
		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => $model->listarCentrosCostoGestion(),
		]);
		exit;

	// Agrega un nuevo centro de costo
	case 'agregarCentroCostoAjax':
		header('Content-Type: application/json');
		$siglas = isset($_POST['siglas']) ? trim((string) $_POST['siglas']) : '';
		$nombreCentroCosto = isset($_POST['nombreCentroCosto']) ? trim((string) $_POST['nombreCentroCosto']) : '';
		echo json_encode($model->agregarCentroCosto($siglas, $nombreCentroCosto));
		exit;

	// Actualiza los datos de un centro de costo
	case 'actualizarCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$siglas = isset($_POST['siglas']) ? trim((string) $_POST['siglas']) : '';
		$nombreCentroCosto = isset($_POST['nombreCentroCosto']) ? trim((string) $_POST['nombreCentroCosto']) : '';
		echo json_encode($model->actualizarCentroCosto($id, $siglas, $nombreCentroCosto));
		exit;

	// Elimina un centro de costo
	case 'eliminarCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->eliminarCentroCosto($id));
		exit;

	// Activa un centro de costo desactivado
	case 'activarCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->activarCentroCosto($id));
		exit;

	// Lista todas las tecnologías activas del catálogo
	case 'listarTecnologiasCatalogoAjax':
		header('Content-Type: application/json');
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode([
			'success' => true,
			'data' => $catalogoModel->listarTecnologiasActivas(),
		]);
		exit;

	// Actualiza los datos de una tecnología del catálogo
	case 'actualizarTecnologiaCatalogoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$codigo = isset($_POST['codigo']) ? trim((string) $_POST['codigo']) : '';
		$nombreGenerico = isset($_POST['nombreGenerico']) ? trim((string) $_POST['nombreGenerico']) : '';
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->actualizarTecnologia($id, $codigo, $nombreGenerico));
		exit;

	// Elimina una tecnología del catálogo
	case 'eliminarTecnologiaCatalogoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->eliminarTecnologia($id));
		exit;

	// Activa una tecnología del catálogo desactivada
	case 'activarTecnologiaCatalogoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->activarTecnologia($id));
		exit;

	// Lista todos los tipos de solicitud disponibles
	case 'listarTiposSolicitudAjax':
		header('Content-Type: application/json');
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode([
			'success' => true,
			'data' => $catalogoModel->listarTiposSolicitudGestion(),
		]);
		exit;

	// Agrega un nuevo tipo de solicitud
	case 'agregarTipoSolicitudAjax':
		header('Content-Type: application/json');
		$nombre = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->agregarTipoSolicitud($nombre, $idUsuarioSesion));
		exit;

	// Actualiza los datos de un tipo de solicitud
	case 'actualizarTipoSolicitudAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$nombre = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->actualizarTipoSolicitud($id, $nombre, $idUsuarioSesion));
		exit;

	// Elimina un tipo de solicitud
	case 'eliminarTipoSolicitudAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->eliminarTipoSolicitud($id, $idUsuarioSesion));
		exit;

	// Activa un tipo de solicitud desactivado
	case 'activarTipoSolicitudAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->activarTipoSolicitud($id, $idUsuarioSesion));
		exit;

	// Obtiene las asociaciones entre tecnologías y tipos de solicitud para un año
	case 'listarTecnologiaTipoSolicitudAjax':
		header('Content-Type: application/json');
		$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : (int) date('Y');
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		$tecnologiasGestion = $catalogoModel->listarTecnologiasActivas();
		$tecnologiasActivas = array_values(array_filter($tecnologiasGestion, static function ($item) {
			return (int) ($item['Activo'] ?? 0) === 1;
		}));
		echo json_encode([
			'success' => true,
			'anio' => $anio,
			'tecnologias' => $tecnologiasActivas,
			'tiposSolicitud' => $catalogoModel->listarTiposSolicitudActivos(),
			'data' => $catalogoModel->listarAsociacionesTecnologiaTipoSolicitud($anio),
		]);
		exit;

	// Guarda una asociación entre una tecnología y un tipo de solicitud
	case 'guardarTecnologiaTipoSolicitudAjax':
		header('Content-Type: application/json');
		$idCatalogoTecnologico = isset($_POST['idCatalogoTecnologico']) ? (int) $_POST['idCatalogoTecnologico'] : 0;
		$idTipoSolicitud = isset($_POST['idTipoSolicitud']) ? (int) $_POST['idTipoSolicitud'] : 0;
		$anioAsociacion = isset($_POST['anio']) ? (int) $_POST['anio'] : 0;
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->guardarAsociacionTecnologiaTipoSolicitud($idCatalogoTecnologico, $idTipoSolicitud, $anioAsociacion, $idUsuarioSesion));
		exit;

	// Lista todas las metas SIAF disponibles
	case 'listarMetasSiafAjax':
		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => $model->listarMetasSiafGestion(),
		]);
		exit;

	// Agrega una nueva meta SIAF
	case 'agregarMetaSiafAjax':
		header('Content-Type: application/json');
		$codigoMeta = isset($_POST['codigoMeta']) ? trim((string) $_POST['codigoMeta']) : '';
		$descripcion = isset($_POST['descripcion']) ? trim((string) $_POST['descripcion']) : '';
		echo json_encode($model->agregarMetaSiaf($codigoMeta, $descripcion, $idUsuarioSesion));
		exit;

	// Actualiza los datos de una meta SIAF
	case 'actualizarMetaSiafAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$codigoMeta = isset($_POST['codigoMeta']) ? trim((string) $_POST['codigoMeta']) : '';
		$descripcion = isset($_POST['descripcion']) ? trim((string) $_POST['descripcion']) : '';
		echo json_encode($model->actualizarMetaSiaf($id, $codigoMeta, $descripcion, $idUsuarioSesion));
		exit;

	// Elimina una meta SIAF
	case 'eliminarMetaSiafAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->eliminarMetaSiaf($id, $idUsuarioSesion));
		exit;

	// Activa una meta SIAF desactivada
	case 'activarMetaSiafAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->activarMetaSiaf($id, $idUsuarioSesion));
		exit;

	// Lista todos los subcentros de costo disponibles
	case 'listarSubCentrosCostoAjax':
		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data'    => $model->listarSubCentrosCostoGestion(),
			'centros' => $model->obtenerCentrosCosto(),
		]);
		exit;

	// Agrega un nuevo subcentro de costo
	case 'agregarSubCentroCostoAjax':
		header('Content-Type: application/json');
		$idCC   = isset($_POST['idCentroCosto']) ? (int) $_POST['idCentroCosto'] : 0;
		$siglas = isset($_POST['siglas']) ? trim((string) $_POST['siglas']) : '';
		$nombre = isset($_POST['nombreSubCentroCosto']) ? trim((string) $_POST['nombreSubCentroCosto']) : '';
		echo json_encode($model->agregarSubCentroCosto($idCC, $siglas, $nombre, $idUsuarioSesion));
		exit;

	// Actualiza los datos de un subcentro de costo
	case 'actualizarSubCentroCostoAjax':
		header('Content-Type: application/json');
		$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$idCC   = isset($_POST['idCentroCosto']) ? (int) $_POST['idCentroCosto'] : 0;
		$siglas = isset($_POST['siglas']) ? trim((string) $_POST['siglas']) : '';
		$nombre = isset($_POST['nombreSubCentroCosto']) ? trim((string) $_POST['nombreSubCentroCosto']) : '';
		echo json_encode($model->actualizarSubCentroCosto($id, $idCC, $siglas, $nombre, $idUsuarioSesion));
		exit;

	// Elimina un subcentro de costo
	case 'eliminarSubCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->eliminarSubCentroCosto($id));
		exit;

	// Activa un subcentro de costo desactivado
	case 'activarSubCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->activarSubCentroCosto($id));
		exit;

	// Carga la vista predeterminada de requerimientos
	default:
		$anioFiltro = cargarVistaRequerimientos($model, $anioFiltro, $vistaActual, $requerimientos, $centrosCosto, $aniosDisponibles, $metasSiafActivas);
		break;
}

// Renderiza la plantilla principal de adquisiciones con los datos preparados.
include 'modules/adquisiciones/views/index.php';
