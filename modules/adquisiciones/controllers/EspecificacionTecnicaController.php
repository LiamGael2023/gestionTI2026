<?php
require_once 'modules/adquisiciones/models/CatalogoTecnologicoModel.php';
require_once 'modules/adquisiciones/models/EspecificacionTecnicaModel.php';
require_once 'modules/adquisiciones/models/FichaTecnicaModel.php';
require_once 'modules/adquisiciones/models/VerificacionTecnicaModel.php';
require_once 'modules/adquisiciones/models/ConformidadModel.php';

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$catalogoModel = new CatalogoTecnologicoModel($conn);
$especificacionModel = new EspecificacionTecnicaModel($conn);
$fichaTecnicaModel = new FichaTecnicaModel($conn);
$verificacionTecnicaModel = new VerificacionTecnicaModel($conn);
$conformidadModel = new ConformidadModel($conn);
$action = $_GET['action'] ?? 'tecnologia';
$vistaActual = 'tecnologia';

function responderJson($payload)
{
	enviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
	echo json_encode($payload);
	exit;
}

function enviarHeaderSeguro($header)
{
	if (!headers_sent()) {
		header($header);
	}
}

function redirigirSeguro($url)
{
	if (!headers_sent()) {
		header('Location: ' . $url);
		exit;
	}

	echo '<script>window.location.href=' . json_encode($url) . ';</script>';
	exit;
}

function obtenerInputJson()
{
	$input = json_decode(file_get_contents('php://input'), true);
	return is_array($input) ? $input : null;
}

function obtenerInputJsonPost()
{
	validarMetodoPost();
	$input = obtenerInputJson();
	if (!$input) {
		responderJson(['ok' => false, 'error' => 'Datos inválidos']);
	}

	return $input;
}

function validarMetodoPost()
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		responderJson(['ok' => false, 'error' => 'Método no permitido']);
	}
}

function validarPdfBase64($pdfBase64)
{
	$decoded = base64_decode($pdfBase64, true);
	return $decoded !== false && substr($decoded, 0, 5) === '%PDF-';
}

function longitudTexto($texto)
{
	if (function_exists('mb_strlen')) {
		return mb_strlen($texto, 'UTF-8');
	}

	return strlen($texto);
}

function obtenerEnteroInput($input, $clave)
{
	return isset($input[$clave]) ? (int) $input[$clave] : 0;
}

function obtenerTextoInput($input, $clavePrincipal, $claveCompatibilidad = null)
{
	if (isset($input[$clavePrincipal])) {
		return trim((string) $input[$clavePrincipal]);
	}

	if ($claveCompatibilidad !== null && isset($input[$claveCompatibilidad])) {
		return trim((string) $input[$claveCompatibilidad]);
	}

	return '';
}

function obtenerDocumentoInput($input)
{
	if (isset($input['Documento'])) {
		return (string) $input['Documento'];
	}

	if (isset($input['DocumentoPDF'])) {
		return (string) $input['DocumentoPDF'];
	}

	return '';
}

function normalizarTextoNullable($texto)
{
	$texto = trim((string) $texto);
	return $texto !== '' ? $texto : null;
}

function responderErrorSql($mensajeBase, $mensajeTruncamiento = null)
{
	$mensaje = $mensajeBase;
	$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
	if (is_array($errors) && count($errors) > 0) {
		$mensajeSql = $errors[0]['message'];
		if ($mensajeTruncamiento !== null && stripos($mensajeSql, 'String or binary data would be truncated') !== false) {
			$mensaje = $mensajeTruncamiento;
		} else {
			$mensaje .= ' ' . $mensajeSql;
		}
	}

	responderJson(['ok' => false, 'error' => $mensaje]);
}

function enviarDocumentoPdf($conn, $tabla, $id, $camposNombre)
{
	if ($id <= 0) {
		http_response_code(400);
		exit;
	}

	$sql = 'SELECT Documento, ' . implode(', ', $camposNombre) . ' FROM ' . $tabla . ' WHERE Id = ?';
	$stmt = sqlsrv_query($conn, $sql, [$id]);
	$row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
	if (!$row || empty($row['Documento'])) {
		http_response_code(404);
		exit;
	}

	$decoded = base64_decode($row['Documento'], true);
	if ($decoded === false) {
		http_response_code(500);
		exit;
	}

	$partesNombre = [];
	foreach ($camposNombre as $campo) {
		$partesNombre[] = isset($row[$campo]) ? (string) $row[$campo] : '';
	}
	$nombre = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim(implode('_', $partesNombre), '_'));
	if ($nombre === '') {
		$nombre = 'documento';
	}

	enviarHeaderSeguro('Content-Type: application/pdf');
	enviarHeaderSeguro('Content-Disposition: inline; filename="' . $nombre . '.pdf"');
	enviarHeaderSeguro('Content-Length: ' . strlen($decoded));
	echo $decoded;
	exit;
}

function obtenerErrorSecuenciaDocumental($idCatalogoTecnologico, $anio, $fichaTecnicaModel, $especificacionModel, $verificacionTecnicaModel, $etapa)
{
	$minimoFichas = 4;
	$totalFichas = $fichaTecnicaModel->contarPorTecnologia($idCatalogoTecnologico, $anio);
	$tieneFichasMinimas = $totalFichas >= $minimoFichas;
	$tieneEspecificacion = !empty($especificacionModel->obtenerPorTecnologia($idCatalogoTecnologico, $anio));
	$tieneVerificacion = !empty($verificacionTecnicaModel->obtenerPorTecnologia($idCatalogoTecnologico, $anio));

	switch ($etapa) {
		case 'especificacion':
			return $tieneFichasMinimas ? null : 'Primero debe registrar al menos 4 fichas técnicas.';

		case 'verificacion':
			if (!$tieneFichasMinimas) {
				return 'Primero debe registrar al menos 4 fichas técnicas.';
			}

			return $tieneEspecificacion ? null : 'Primero debe registrar la especificación técnica.';

		case 'conformidad':
			if (!$tieneFichasMinimas) {
				return 'Primero debe registrar al menos 4 fichas técnicas.';
			}

			if (!$tieneEspecificacion) {
				return 'Primero debe registrar la especificación técnica.';
			}

			return $tieneVerificacion ? null : 'Primero debe registrar la verificación técnica.';

		default:
			return null;
	}
}

switch ($action) {
	case 'tecnologia':
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		$anioFiltro = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : (int) date('Y');

		$tecnologia = $catalogoModel->obtenerPorId($id);
		if (!$tecnologia) {
			redirigirSeguro('index.php?module=adquisiciones&action=tecnologias');
		}

		$aniosDisponiblesTec = $catalogoModel->obtenerAniosDisponibles();
		$pedidos = $catalogoModel->obtenerPedidosPorTecnologia($id, $anioFiltro);
		$especificacionTecnica = $especificacionModel->obtenerPorTecnologia($id, $anioFiltro);
		$fichasTecnicas = $fichaTecnicaModel->listarPorTecnologia($id, $anioFiltro);
		$verificacionTecnica = $verificacionTecnicaModel->obtenerPorTecnologia($id, $anioFiltro);
		$conformidad = $conformidadModel->obtenerPorTecnologia($id, $anioFiltro);
		break;

	case 'verEspecificacionTecnicaAjax':
		enviarDocumentoPdf($conn, 'adquisiciones.EspecificacionTecnica', isset($_GET['id']) ? (int) $_GET['id'] : 0, ['Codigo']);

	case 'verFichaTecnicaAjax':
		enviarDocumentoPdf($conn, 'adquisiciones.FichaTecnica', isset($_GET['id']) ? (int) $_GET['id'] : 0, ['Marca', 'Modelo']);

	case 'verVerificacionTecnicaAjax':
		enviarDocumentoPdf($conn, 'adquisiciones.VerificacionTecnica', isset($_GET['id']) ? (int) $_GET['id'] : 0, ['Observacion']);

	case 'verConformidadAjax':
		enviarDocumentoPdf($conn, 'adquisiciones.Conformidad', isset($_GET['id']) ? (int) $_GET['id'] : 0, ['Observacion']);

	case 'guardarEspecificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$codigo = obtenerTextoInput($input, 'Codigo', 'CodigoFT');
		$anio = obtenerEnteroInput($input, 'Anio');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idCat <= 0 || $codigo === '' || $anio <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if (longitudTexto($codigo) > EspecificacionTecnicaModel::CODIGO_MAX_LENGTH) {
			responderJson([
				'ok' => false,
				'error' => 'El código de especificación técnica no puede exceder ' . EspecificacionTecnicaModel::CODIGO_MAX_LENGTH . ' caracteres.'
			]);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$errorSecuencia = obtenerErrorSecuenciaDocumental(
			$idCat,
			$anio,
			$fichaTecnicaModel,
			$especificacionModel,
			$verificacionTecnicaModel,
			'especificacion'
		);
		if ($errorSecuencia !== null) {
			responderJson(['ok' => false, 'error' => $errorSecuencia]);
		}
		$resultado = $especificacionModel->guardar([
			'IdCatalogoTecnologico' => $idCat,
			'Codigo' => $codigo,
			'Anio' => $anio,
			'Documento' => $pdfBase64
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}
		responderErrorSql(
			'No se pudo guardar la especificación técnica.',
			'El código de especificación técnica no puede exceder ' . EspecificacionTecnicaModel::CODIGO_MAX_LENGTH . ' caracteres.'
		);

	case 'actualizarEspecificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idEspecificacion = obtenerEnteroInput($input, 'Id');
		$codigo = obtenerTextoInput($input, 'Codigo', 'CodigoFT');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idEspecificacion <= 0 || $codigo === '' || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if (longitudTexto($codigo) > EspecificacionTecnicaModel::CODIGO_MAX_LENGTH) {
			responderJson([
				'ok' => false,
				'error' => 'El código de especificación técnica no puede exceder ' . EspecificacionTecnicaModel::CODIGO_MAX_LENGTH . ' caracteres.'
			]);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$ok = $especificacionModel->actualizar($idEspecificacion, [
			'Codigo' => $codigo,
			'Documento' => $pdfBase64
		]);
		if ($ok) {
			responderJson(['ok' => true]);
		}
		responderErrorSql(
			'No se pudo actualizar la especificación técnica.',
			'El código de especificación técnica no puede exceder ' . EspecificacionTecnicaModel::CODIGO_MAX_LENGTH . ' caracteres.'
		);

	case 'eliminarEspecificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idEspecificacion = obtenerEnteroInput($input, 'Id');
		if ($idEspecificacion <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $especificacionModel->eliminar($idEspecificacion);
		responderJson(['ok' => $ok]);

	case 'guardarVerificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$observacion = obtenerTextoInput($input, 'Observacion');
		$anio = obtenerEnteroInput($input, 'Anio');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idCat <= 0 || $anio <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$errorSecuencia = obtenerErrorSecuenciaDocumental(
			$idCat,
			$anio,
			$fichaTecnicaModel,
			$especificacionModel,
			$verificacionTecnicaModel,
			'verificacion'
		);
		if ($errorSecuencia !== null) {
			responderJson(['ok' => false, 'error' => $errorSecuencia]);
		}
		$resultado = $verificacionTecnicaModel->guardar([
			'IdCatalogoTecnologico' => $idCat,
			'Observacion' => normalizarTextoNullable($observacion),
			'Anio' => $anio,
			'Documento' => $pdfBase64
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}

		responderErrorSql('Ya existe una verificación técnica para este año o error al guardar');

	case 'actualizarVerificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		$observacion = obtenerTextoInput($input, 'Observacion');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idDocumento <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$ok = $verificacionTecnicaModel->actualizar($idDocumento, [
			'Observacion' => normalizarTextoNullable($observacion),
			'Documento' => $pdfBase64
		]);
		responderJson(['ok' => $ok]);

	case 'eliminarVerificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		if ($idDocumento <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $verificacionTecnicaModel->eliminar($idDocumento);
		responderJson(['ok' => $ok]);

	case 'guardarConformidadAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$observacion = obtenerTextoInput($input, 'Observacion');
		$anio = obtenerEnteroInput($input, 'Anio');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idCat <= 0 || $anio <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$errorSecuencia = obtenerErrorSecuenciaDocumental(
			$idCat,
			$anio,
			$fichaTecnicaModel,
			$especificacionModel,
			$verificacionTecnicaModel,
			'conformidad'
		);
		if ($errorSecuencia !== null) {
			responderJson(['ok' => false, 'error' => $errorSecuencia]);
		}
		$resultado = $conformidadModel->guardar([
			'IdCatalogoTecnologico' => $idCat,
			'Observacion' => normalizarTextoNullable($observacion),
			'Anio' => $anio,
			'Documento' => $pdfBase64
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}

		responderErrorSql('Ya existe una conformidad para este año o error al guardar');

	case 'actualizarConformidadAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		$observacion = obtenerTextoInput($input, 'Observacion');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idDocumento <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$ok = $conformidadModel->actualizar($idDocumento, [
			'Observacion' => normalizarTextoNullable($observacion),
			'Documento' => $pdfBase64
		]);
		responderJson(['ok' => $ok]);

	case 'eliminarConformidadAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		if ($idDocumento <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $conformidadModel->eliminar($idDocumento);
		responderJson(['ok' => $ok]);

	case 'guardarFichaTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$marca = obtenerTextoInput($input, 'Marca');
		$modelo = obtenerTextoInput($input, 'Modelo');
		$anio = obtenerEnteroInput($input, 'Anio');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idCat <= 0 || $marca === '' || $modelo === '' || $anio <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$resultado = $fichaTecnicaModel->guardar([
			'IdCatalogoTecnologico' => $idCat,
			'Marca' => $marca,
			'Modelo' => $modelo,
			'Anio' => $anio,
			'Documento' => $pdfBase64
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}
		responderJson(['ok' => false, 'error' => 'Error al guardar la ficha técnica']);

	case 'eliminarFichaTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idFicha = obtenerEnteroInput($input, 'Id');
		if ($idFicha <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $fichaTecnicaModel->eliminar($idFicha);
		responderJson(['ok' => $ok]);

	case 'cambiarEstadoFichaTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idFicha = obtenerEnteroInput($input, 'Id');
		$estado = isset($input['Estado']) ? (int) $input['Estado'] : -1;
		if ($idFicha <= 0 || !in_array($estado, [0, 1], true)) {
			responderJson(['ok' => false, 'error' => 'Datos inválidos']);
		}
		$ok = $fichaTecnicaModel->cambiarEstado($idFicha, $estado);
		responderJson(['ok' => $ok]);

	default:
		redirigirSeguro('index.php?module=adquisiciones&action=tecnologias');
}

include 'modules/adquisiciones/views/index.php';