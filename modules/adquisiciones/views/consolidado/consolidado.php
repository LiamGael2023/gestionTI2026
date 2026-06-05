<?php
declare(strict_types=1);

$export = isset($_GET['export']) ? (string) $_GET['export'] : '';

if ($export === '') {
	header('Content-Type: application/javascript; charset=UTF-8');
	echo <<<'JS'
function exportarConsolidado() {
	var anio = obtenerAnioConsolidado();
	var url = 'modules/adquisiciones/views/consolidado/consolidado.php?export=noOficial';
	if (anio) {
		url += '&anio=' + encodeURIComponent(anio);
	}
	window.location.href = url;
}

function exportarConsolidadoOficial() {
	var anio = obtenerAnioConsolidado();
	var url = 'modules/adquisiciones/views/consolidado/consolidado.php?export=oficial';
	if (anio) {
		url += '&anio=' + encodeURIComponent(anio);
	}
	window.location.href = url;
}

function obtenerAnioConsolidado() {
	var anioEl = document.getElementById('filtroAnioConsolidado');
	return anioEl ? String(anioEl.value || '').trim() : '';
}
JS;
	exit;
}

$rootPath = dirname(__DIR__, 4);
chdir($rootPath);

$_GET['module'] = 'adquisiciones';

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'core/Auth.php';
require_once 'modules/adquisiciones/models/RequerimientoModel.php';

Auth::check();

$conn = Conexion::conectar();
$anioSolicitado = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : null;

//Consolidado No Oficial
if ($export === 'noOficial') {
	define('ADQ_CONTROLLER_FUNCTIONS_ONLY', true);
	require_once 'modules/adquisiciones/controllers/RequerimientoController.php';

	$model = new RequerimientoModel($conn);
	descargarConsolidadoNoOficialXlsx($model, $anioSolicitado);
}

if ($export !== 'oficial') {
	http_response_code(400);
	exit;
}

error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
@ini_set('display_errors', '0');

$autoload = 'libs/vendor/autoload.php';
if (!file_exists($autoload)) {
	http_response_code(500);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'No se encontro el autoload de Composer en libs/vendor/autoload.php';
	exit;
}

require_once $autoload;

function normalizarCodigoMetaExportacionOficial($codigoMeta): string
{
	$codigo = preg_replace('/[^0-9]/', '', (string) $codigoMeta);
	if ($codigo === '') {
		return '';
	}
	if (strlen($codigo) < 3) {
		$codigo = str_pad($codigo, 3, '0', STR_PAD_LEFT);
	}
	if (strlen($codigo) > 4) {
		$codigo = substr($codigo, -4);
	}
	return $codigo;
}

function resolverAnioExportacionOficial(RequerimientoModel $model, ?int $anioSolicitado): int
{
	if ($anioSolicitado !== null && $anioSolicitado > 0) {
		return $anioSolicitado;
	}

	$aniosDisponibles = $model->obtenerAniosDisponibles();
	$anioActual = (int) date('Y');
	if (in_array($anioActual, $aniosDisponibles, true)) {
		return $anioActual;
	}

	return !empty($aniosDisponibles) ? (int) $aniosDisponibles[0] : $anioActual;
}

//Consolidado Oficial
$model = new RequerimientoModel($conn);
$anioConsulta = resolverAnioExportacionOficial($model, $anioSolicitado);
$metasCabecera = $model->obtenerMetasSiafActivas();
$filas = $model->obtenerConsolidadoFormatoOficial($anioConsulta, $metasCabecera, true);

$metas = [];
$vistos = [];
foreach ($metasCabecera as $meta) {
	$codigo = normalizarCodigoMetaExportacionOficial($meta['CodigoMeta'] ?? '');
	if ($codigo === '' || isset($vistos[$codigo])) {
		continue;
	}

	$vistos[$codigo] = true;
	$metas[] = [
		'codigo' => $codigo,
		'nombre' => strtoupper(trim((string) ($meta['Descripcion'] ?? $codigo))),
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

$cabecerasFijas = [
	1 => 'N°',
	2 => 'USUARIO ASIGNADO',
	3 => 'TIPO DE EQUIPO',
	4 => "DESCRIPCION DEL\nCOMPONENTE",
	5 => 'REFERENCIA',
	6 => 'UNIDAD MEDIDA',
	7 => 'PRECIO UNITARIO REF',
];

foreach ($cabecerasFijas as $col => $label) {
	$sheet->setCellValueByColumnAndRow($col, 1, $label);
	$letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
	$sheet->mergeCells($letter . '1:' . $letter . '2');
}

if (!empty($metas)) {
	$sheet->setCellValueByColumnAndRow($indiceInicioMetas, 1, 'METAS SIAF');
	$sheet->mergeCells(
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceInicioMetas) . '1:' .
		\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceFinMetas) . '1'
	);

	$colMeta = $indiceInicioMetas;
	foreach ($metas as $meta) {
		$sheet->setCellValueByColumnAndRow($colMeta, 2, $meta['codigo']);
		$sheet->setCellValueByColumnAndRow($colMeta + 1, 2, $meta['nombre']);
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

$sheet->getStyle('A1:' . $lastColLetter . '2')->getFont()->setName('Tahoma')->setSize(9)->setBold(true);
$sheet->getStyle('A1:' . $lastColLetter . $filaData)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$sheet->getStyle('A1:' . $lastColLetter . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:' . $lastColLetter . '2')->getAlignment()->setWrapText(true);
$sheet->getStyle('A1:' . $lastColLetter . $filaData)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$sheet->getStyle('A' . $filaData . ':' . $lastColLetter . $filaData)->getFont()->setBold(true);
$sheet->getStyle('E1:G2')->getAlignment()->setTextRotation(90);
$sheet->getStyle(
	\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceTotalInicial) . '1:' .
	\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceMontoTotal) . '2'
)->getAlignment()->setTextRotation(90);

if (!empty($metas)) {
	$metaStartLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceInicioMetas);
	$metaEndLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indiceFinMetas);
	$sheet->getStyle($metaStartLetter . '2:' . $metaEndLetter . '2')->getAlignment()->setTextRotation(90);
	$sheet->getStyle($metaStartLetter . '2:' . $metaEndLetter . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle($metaStartLetter . '2:' . $metaEndLetter . '2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
}

$sheet->getRowDimension(1)->setRowHeight(22);
$sheet->getRowDimension(2)->setRowHeight(88);

$widths = [
	1 => 5,
	2 => 28,
	3 => 32,
	4 => 24,
	5 => 12,
	6 => 10,
	7 => 10,
];
for ($col = 1; $col <= $totalColumnas; $col++) {
	$letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
	if (isset($widths[$col])) {
		$width = $widths[$col];
	} elseif ($col === $indiceTotalInicial || $col === $indiceMontoTotal) {
		$width = 10;
	} else {
		$esCodigoMeta = (($col - $indiceInicioMetas) % 2 === 0);
		$width = $esCodigoMeta ? 5 : 16;
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
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
