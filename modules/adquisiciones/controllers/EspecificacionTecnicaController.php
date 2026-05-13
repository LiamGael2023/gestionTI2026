<?php
require_once 'modules/adquisiciones/models/CatalogoTecnologicoModel.php';
require_once 'modules/adquisiciones/models/EspecificacionTecnicaModel.php';
require_once 'modules/adquisiciones/models/CierreAdquisicionModel.php';
require_once 'modules/adquisiciones/models/FichaTecnicaModel.php';
require_once 'modules/adquisiciones/models/OrdenCompraModel.php';
require_once 'modules/adquisiciones/models/VerificacionTecnicaModel.php';
require_once 'modules/adquisiciones/models/PresupuestoTecnologicoModel.php';
require_once 'modules/adquisiciones/helpers.php';

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$catalogoModel = new CatalogoTecnologicoModel($conn);
$especificacionModel = new EspecificacionTecnicaModel($conn);
$cierreModel = new CierreAdquisicionModel($conn);
$fichaTecnicaModel = new FichaTecnicaModel($conn);
$ordenCompraModel = new OrdenCompraModel($conn);
$verificacionTecnicaModel = new VerificacionTecnicaModel($conn);
$presupuestoModel = new PresupuestoTecnologicoModel($conn);
$action = $_GET['action'] ?? 'tecnologia';
$vistaActual = 'tecnologia';

// Responde con JSON estableciendo header y finalizando ejecución
function responderJson($payload)
{
	adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
	echo json_encode($payload);
	exit;
}

// Obtiene datos JSON del cuerpo de la solicitud
function obtenerInputJson()
{
	$input = json_decode(file_get_contents('php://input'), true);
	return is_array($input) ? $input : null;
}

// Valida método POST y obtiene datos JSON de entrada
function obtenerInputJsonPost()
{
	validarMetodoPost();
	$input = obtenerInputJson();
	if (!$input) {
		responderJson(['ok' => false, 'error' => 'Datos inválidos']);
	}

	return $input;
}

// Valida que el método de solicitud sea POST
function validarMetodoPost()
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		responderJson(['ok' => false, 'error' => 'Método no permitido']);
	}
}

// Valida si una cadena base64 es un PDF válido
function validarPdfBase64($pdfBase64)
{
	$decoded = base64_decode($pdfBase64, true);
	return $decoded !== false && substr($decoded, 0, 5) === '%PDF-';
}

// Calcula la longitud de un texto en caracteres UTF-8
function longitudTexto($texto)
{
	if (function_exists('mb_strlen')) {
		return mb_strlen($texto, 'UTF-8');
	}

	return strlen($texto);
}

// Normaliza texto a mayúsculas ASCII removiendo caracteres especiales
function normalizarTextoAsciiMayuscula($texto)
{
	$texto = strtoupper(trim((string) $texto));

	if (function_exists('iconv')) {
		$convertido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
		if ($convertido !== false) {
			$texto = $convertido;
		}
	}

	$texto = preg_replace('/[^A-Z0-9]+/', ' ', (string) $texto);
	$texto = trim((string) $texto);

	return $texto;
}

// Extrae el token de código de tecnología (T###) de la orden de compra
function obtenerTokenCodigoTecnologiaOrden($codigo, $idCatalogoTecnologico)
{
	$codigoNormalizado = normalizarTextoAsciiMayuscula($codigo);
	if ($codigoNormalizado !== '' && preg_match('/\b(T\d+)\b/', $codigoNormalizado, $match)) {
		return $match[1];
	}

	$tokens = preg_split('/\s+/', $codigoNormalizado);
	$primerToken = isset($tokens[0]) ? (string) $tokens[0] : '';
	if ($primerToken === '') {
		return 'T' . (int) $idCatalogoTecnologico;
	}

	return strpos($primerToken, 'T') === 0 ? $primerToken : ('T' . $primerToken);
}

// Obtiene la primera palabra de la descripción para generar número de orden
function obtenerPrimeraPalabraDescripcionOrden($descripcion)
{
	$descripcionNormalizada = normalizarTextoAsciiMayuscula($descripcion);
	$tokens = preg_split('/\s+/', $descripcionNormalizada);
	$primeraPalabra = isset($tokens[0]) ? (string) $tokens[0] : '';

	return $primeraPalabra !== '' ? $primeraPalabra : 'TECNOLOGIA';
}

// Genera número de orden de compra con formato OC_TOKEN_PALABRA_ANIO
function generarNumeroOrdenCompra($tecnologia, $anio)
{
	$idCatalogo = isset($tecnologia['Id']) ? (int) $tecnologia['Id'] : 0;
	$tokenTecnologia = obtenerTokenCodigoTecnologiaOrden($tecnologia['Codigo'] ?? '', $idCatalogo);
	$primeraPalabra = obtenerPrimeraPalabraDescripcionOrden($tecnologia['NombreGenerico'] ?? '');
	$anioNumero = (int) $anio;
	$prefijo = 'OC_' . $tokenTecnologia . '_';
	$sufijo = '_' . $anioNumero;
	$maxPalabra = OrdenCompraModel::NUMERO_ORDEN_MAX_LENGTH - longitudTexto($prefijo) - longitudTexto($sufijo);
	if ($maxPalabra < 1) {
		$maxPalabra = 1;
	}

	$primeraPalabra = substr($primeraPalabra, 0, $maxPalabra);

	return $prefijo . $primeraPalabra . $sufijo;
}

// Obtiene un valor entero de un array de entrada
function obtenerEnteroInput($input, $clave)
{
	return isset($input[$clave]) ? (int) $input[$clave] : 0;
}

// Obtiene un valor de texto trimizado de un array de entrada
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

// Obtiene documento PDF base64 de entrada con compatibilidad de claves
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

// Normaliza texto removiendo espacios en blanco o devolviendo nulo
function normalizarTextoNullable($texto)
{
	$texto = trim((string) $texto);
	return $texto !== '' ? $texto : null;
}

// Normaliza fecha a formato Y-m-d o devuelve null si es vacía
function normalizarFechaNullable($fecha)
{
	$fecha = trim((string) $fecha);
	if ($fecha === '') {
		return null;
	}

	$timestamp = strtotime($fecha);
	if ($timestamp === false) {
		return false;
	}

	return date('Y-m-d', $timestamp);
}

// Obtiene el ID del usuario de la sesión actual
function obtenerIdUsuarioSesion()
{
	return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
}

// Responde con error JSON incluyendo mensaje de error SQL si es disponible
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

// Obtiene documento PDF de base de datos y lo envía como descarga
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

	// Limpia cualquier buffer de salida acumulado para evitar bytes basura antes del PDF
	while (ob_get_level() > 0) {
		ob_end_clean();
	}

	// Si ya se enviaron headers, no podemos servir el PDF (algo imprimió antes)
	if (headers_sent($archivoOrigen, $lineaOrigen)) {
		error_log(sprintf('PDF: headers ya enviados en %s:%d', $archivoOrigen, $lineaOrigen));
		http_response_code(500);
		exit;
	}

	header('Content-Type: application/pdf');
	header('Content-Disposition: inline; filename="' . $nombre . '.pdf"');
	header('Content-Length: ' . strlen($decoded));
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: private, max-age=0, must-revalidate');
	header('Pragma: public');
	echo $decoded;
	exit;
}

// Valida si se cumplen requisitos de secuencia documental para cada etapa
function obtenerErrorSecuenciaDocumental($idCatalogoTecnologico, $anio, $fichaTecnicaModel, $especificacionModel, $ordenCompraModel, $verificacionTecnicaModel, $etapa)
{
	$minimoFichas = 2;
	$mensajeMinimoFichas = sprintf('Primero debe registrar al menos %d fichas técnicas.', $minimoFichas);
	$totalFichas = $fichaTecnicaModel->contarPorTecnologia($idCatalogoTecnologico, $anio);
	$tieneFichasMinimas = $totalFichas >= $minimoFichas;
	$tieneEspecificacion = !empty($especificacionModel->obtenerPorTecnologia($idCatalogoTecnologico, $anio));
	$tieneOrdenCompra = !empty($ordenCompraModel->obtenerPorTecnologia($idCatalogoTecnologico, $anio));
	$tieneVerificacion = !empty($verificacionTecnicaModel->obtenerPorTecnologia($idCatalogoTecnologico, $anio));

	switch ($etapa) {
		case 'especificacion':
			return $tieneFichasMinimas ? null : $mensajeMinimoFichas;

		case 'verificacion':
			if (!$tieneFichasMinimas) {
				return $mensajeMinimoFichas;
			}

			if (!$tieneEspecificacion) {
				return 'Primero debe registrar la especificación técnica.';
			}

			return $tieneOrdenCompra ? null : 'Primero debe registrar la orden de compra.';

		case 'orden':
			if (!$tieneFichasMinimas) {
				return $mensajeMinimoFichas;
			}

			return $tieneEspecificacion ? null : 'Primero debe registrar la especificación técnica.';

		default:
			return null;
	}
}

// Resuelve el año de filtro priorizando el solicitado o el actual
function resolverAnioFiltroLocal($anioSolicitado, array $aniosDisponibles)
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

// Valida que existan pedidos para la tecnología en el año especificado
function validarPedidosTecnologiaPorAnio($catalogoModel, $idCatalogoTecnologico, $anio)
{
	if (!$catalogoModel->tienePedidosPorTecnologiaEnAnio($idCatalogoTecnologico, $anio)) {
		responderJson([
			'ok' => false,
			'error' => 'No existen requerimientos para esta tecnologia en el año seleccionado.'
		]);
	}
}

$idUsuarioSesion = obtenerIdUsuarioSesion();

// Acciones que devuelven PDF binario: se manejan ANTES del switch para evitar
// cualquier output previo (espacios, BOM, HTML de layout) que rompa el Content-Type.
$accionesPdf = [
	'verEspecificacionTecnicaAjax' => ['adquisiciones.EspecificacionTecnica', ['Codigo']],
	'verFichaTecnicaAjax'          => ['adquisiciones.FichaTecnica',          ['Marca', 'Modelo']],
	'verVerificacionTecnicaAjax'   => ['adquisiciones.VerificacionTecnica',   ['Observacion']],
	'verOrdenCompraAjax'           => ['adquisiciones.OrdenCompra',           ['NumeroOrden']],
];
if (isset($accionesPdf[$action])) {
	[$tablaPdf, $camposNombrePdf] = $accionesPdf[$action];
	$idPdf = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	enviarDocumentoPdf($conn, $tablaPdf, $idPdf, $camposNombrePdf);
	exit; // por seguridad: enviarDocumentoPdf ya hace exit internamente
}

switch ($action) {
	// Carga la vista de detalle de una tecnología con todos sus documentos relacionados
	case 'tecnologia':
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		$anioSolicitado = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : null;

		$tecnologia = $catalogoModel->obtenerPorId($id);
		if (!$tecnologia) {
			adqRedirigirSeguro('index.php?module=adquisiciones&action=tecnologias');
		}

		$aniosDisponiblesTec = $catalogoModel->obtenerAniosDisponiblesPorTecnologia($id);
		$anioFiltro = resolverAnioFiltroLocal($anioSolicitado, $aniosDisponiblesTec);
		$pedidos = $catalogoModel->obtenerPedidosPorTecnologia($id, $anioFiltro);
		$especificacionTecnica = $especificacionModel->obtenerPorTecnologia($id, $anioFiltro);
		$fichasTecnicas = $fichaTecnicaModel->listarPorTecnologia($id, $anioFiltro);
		$ordenCompra = $ordenCompraModel->obtenerPorTecnologia($id, $anioFiltro);
		$verificacionTecnica = $verificacionTecnicaModel->obtenerPorTecnologia($id, $anioFiltro);
		$cierreAdquisicion = $cierreModel->obtenerPorTecnologiaYAnio($id, $anioFiltro);
		break;

	// Guarda una nueva especificación técnica con validaciones
	case 'guardarEspecificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$codigo = obtenerTextoInput($input, 'Codigo', 'CodigoFT');
		$anio = obtenerEnteroInput($input, 'Anio');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idCat <= 0 || $codigo === '' || $anio <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		validarPedidosTecnologiaPorAnio($catalogoModel, $idCat, $anio);
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
			$ordenCompraModel,
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
			'Documento' => $pdfBase64,
			'idUsuarioRegistro' => $idUsuarioSesion
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}
		responderErrorSql(
			'No se pudo guardar la especificación técnica.',
			'El código de especificación técnica no puede exceder ' . EspecificacionTecnicaModel::CODIGO_MAX_LENGTH . ' caracteres.'
		);

	// Actualiza una especificación técnica existente
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
			'Documento' => $pdfBase64,
			'idUsuarioModifica' => $idUsuarioSesion
		]);
		if ($ok) {
			responderJson(['ok' => true]);
		}
		responderErrorSql(
			'No se pudo actualizar la especificación técnica.',
			'El código de especificación técnica no puede exceder ' . EspecificacionTecnicaModel::CODIGO_MAX_LENGTH . ' caracteres.'
		);

	// Elimina una especificación técnica
	case 'eliminarEspecificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idEspecificacion = obtenerEnteroInput($input, 'Id');
		if ($idEspecificacion <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $especificacionModel->eliminar($idEspecificacion);
		responderJson(['ok' => $ok]);

	// Guarda una nueva orden de compra con número generado automáticamente
	case 'guardarOrdenCompraAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$fechaEntrega = normalizarFechaNullable(obtenerTextoInput($input, 'FechaEntrega'));
		$anio = obtenerEnteroInput($input, 'Anio');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idCat <= 0 || $anio <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		validarPedidosTecnologiaPorAnio($catalogoModel, $idCat, $anio);
		$tecnologiaOrden = $catalogoModel->obtenerPorId($idCat);
		if (!$tecnologiaOrden) {
			responderJson(['ok' => false, 'error' => 'No se encontró la tecnología para generar la orden de compra.']);
		}
		$numeroOrden = generarNumeroOrdenCompra($tecnologiaOrden, $anio);
		if (longitudTexto($numeroOrden) > OrdenCompraModel::NUMERO_ORDEN_MAX_LENGTH) {
			responderJson([
				'ok' => false,
				'error' => 'El número de orden no puede exceder ' . OrdenCompraModel::NUMERO_ORDEN_MAX_LENGTH . ' caracteres.'
			]);
		}
		if ($fechaEntrega === false) {
			responderJson(['ok' => false, 'error' => 'La fecha de entrega es inválida.']);
		}
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$errorSecuencia = obtenerErrorSecuenciaDocumental(
			$idCat,
			$anio,
			$fichaTecnicaModel,
			$especificacionModel,
			$ordenCompraModel,
			$verificacionTecnicaModel,
			'orden'
		);
		if ($errorSecuencia !== null) {
			responderJson(['ok' => false, 'error' => $errorSecuencia]);
		}
		$resultado = $ordenCompraModel->guardar([
			'IdCatalogoTecnologico' => $idCat,
			'NumeroOrden' => $numeroOrden,
			'FechaEntrega' => $fechaEntrega,
			'Anio' => $anio,
			'Documento' => $pdfBase64,
			'idUsuarioRegistro' => $idUsuarioSesion
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}

		responderErrorSql(
			'Ya existe una orden de compra para este año o error al guardar.',
			'El número de orden no puede exceder ' . OrdenCompraModel::NUMERO_ORDEN_MAX_LENGTH . ' caracteres.'
		);

	// Actualiza una orden de compra existente
	case 'actualizarOrdenCompraAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		$fechaEntrega = normalizarFechaNullable(obtenerTextoInput($input, 'FechaEntrega'));
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idDocumento <= 0) {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		$ordenActual = $ordenCompraModel->obtenerPorId($idDocumento);
		if (!$ordenActual) {
			responderJson(['ok' => false, 'error' => 'No se encontró la orden de compra.']);
		}
		$tecnologiaOrden = $catalogoModel->obtenerPorId((int) $ordenActual['IdCatalogoTecnologico']);
		if (!$tecnologiaOrden) {
			responderJson(['ok' => false, 'error' => 'No se encontró la tecnología para generar la orden de compra.']);
		}
		$numeroOrden = generarNumeroOrdenCompra($tecnologiaOrden, (int) $ordenActual['Anio']);
		if (longitudTexto($numeroOrden) > OrdenCompraModel::NUMERO_ORDEN_MAX_LENGTH) {
			responderJson([
				'ok' => false,
				'error' => 'El número de orden no puede exceder ' . OrdenCompraModel::NUMERO_ORDEN_MAX_LENGTH . ' caracteres.'
			]);
		}
		if ($fechaEntrega === false) {
			responderJson(['ok' => false, 'error' => 'La fecha de entrega es inválida.']);
		}
		if ($pdfBase64 !== '' && !validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$documentoActualizar = $pdfBase64 !== '' ? $pdfBase64 : null;
		$ok = $ordenCompraModel->actualizar($idDocumento, [
			'NumeroOrden' => $numeroOrden,
			'FechaEntrega' => $fechaEntrega,
			'Documento' => $documentoActualizar,
			'idUsuarioModifica' => $idUsuarioSesion
		]);
		if ($ok) {
			responderJson(['ok' => true]);
		}

		responderErrorSql(
			'No se pudo actualizar la orden de compra.',
			'El número de orden no puede exceder ' . OrdenCompraModel::NUMERO_ORDEN_MAX_LENGTH . ' caracteres.'
		);

	// Actualiza solo la fecha de entrega de una orden de compra
	case 'actualizarFechaOrdenCompraAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		$fechaEntrega = normalizarFechaNullable(obtenerTextoInput($input, 'FechaEntrega'));
		if ($idDocumento <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		if ($fechaEntrega === false) {
			responderJson(['ok' => false, 'error' => 'La fecha de entrega es inválida.']);
		}

		$ok = $ordenCompraModel->actualizarFechaEntrega($idDocumento, $fechaEntrega, $idUsuarioSesion);
		if ($ok) {
			responderJson(['ok' => true]);
		}

		responderJson(['ok' => false, 'error' => 'No se pudo actualizar la fecha de entrega.']);

	// Elimina una orden de compra
	case 'eliminarOrdenCompraAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		if ($idDocumento <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $ordenCompraModel->eliminar($idDocumento);
		responderJson(['ok' => $ok]);

	// Guarda una nueva verificación técnica
	case 'guardarVerificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$observacion = obtenerTextoInput($input, 'Observacion');
		$anio = obtenerEnteroInput($input, 'Anio');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idCat <= 0 || $anio <= 0 || $pdfBase64 === '') {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		validarPedidosTecnologiaPorAnio($catalogoModel, $idCat, $anio);
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$errorSecuencia = obtenerErrorSecuenciaDocumental(
			$idCat,
			$anio,
			$fichaTecnicaModel,
			$especificacionModel,
			$ordenCompraModel,
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
			'Documento' => $pdfBase64,
			'idUsuarioRegistro' => $idUsuarioSesion
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}

		responderErrorSql('Ya existe una verificación técnica para este año o error al guardar');

	// Actualiza una verificación técnica existente
	case 'actualizarVerificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		$observacion = obtenerTextoInput($input, 'Observacion');
		$pdfBase64 = obtenerDocumentoInput($input);
		if ($idDocumento <= 0) {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if ($pdfBase64 !== '' && !validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$datosActualizar = [
			'Observacion' => normalizarTextoNullable($observacion),
			'idUsuarioModifica' => $idUsuarioSesion
		];
		if ($pdfBase64 !== '') {
			$datosActualizar['Documento'] = $pdfBase64;
		}
		$ok = $verificacionTecnicaModel->actualizar($idDocumento, $datosActualizar);
		responderJson(['ok' => $ok]);

	// Elimina una verificación técnica
	case 'eliminarVerificacionTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idDocumento = obtenerEnteroInput($input, 'Id');
		if ($idDocumento <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $verificacionTecnicaModel->eliminar($idDocumento);
		responderJson(['ok' => $ok]);

	// Guarda una nueva ficha técnica
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
		validarPedidosTecnologiaPorAnio($catalogoModel, $idCat, $anio);
		if (!validarPdfBase64($pdfBase64)) {
			responderJson(['ok' => false, 'error' => 'El archivo no es un PDF válido']);
		}
		$resultado = $fichaTecnicaModel->guardar([
			'IdCatalogoTecnologico' => $idCat,
			'Marca' => $marca,
			'Modelo' => $modelo,
			'Anio' => $anio,
			'Documento' => $pdfBase64,
			'idUsuarioRegistro' => $idUsuarioSesion
		]);
		if ($resultado) {
			responderJson(['ok' => true, 'id' => $resultado]);
		}
		responderJson(['ok' => false, 'error' => 'Error al guardar la ficha técnica']);

	// Elimina una ficha técnica
	case 'eliminarFichaTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idFicha = obtenerEnteroInput($input, 'Id');
		if ($idFicha <= 0) {
			responderJson(['ok' => false, 'error' => 'ID inválido']);
		}
		$ok = $fichaTecnicaModel->eliminar($idFicha);
		responderJson(['ok' => $ok]);

	// Cambia el estado de una ficha técnica (0=Cargado, 1=Enviado)
	case 'cambiarEstadoFichaTecnicaAjax':
		$input = obtenerInputJsonPost();
		$idFicha = obtenerEnteroInput($input, 'Id');
		$estado = isset($input['Estado']) ? (int) $input['Estado'] : -1;
		if ($idFicha <= 0 || !in_array($estado, [0, 1], true)) {
			responderJson(['ok' => false, 'error' => 'Datos inválidos']);
		}
		$ok = $fichaTecnicaModel->cambiarEstado($idFicha, $estado, $idUsuarioSesion);
		responderJson(['ok' => $ok]);

	// Cambia la posición/prioridad de una ficha técnica en la lista
	case 'moverFichaTecnicaRangoAjax':
		$input = obtenerInputJsonPost();
		$idFicha = obtenerEnteroInput($input, 'Id');
		$direccion = strtolower(obtenerTextoInput($input, 'Direccion'));
		if ($idFicha <= 0 || !in_array($direccion, ['up', 'down'], true)) {
			responderJson(['ok' => false, 'error' => 'Datos inválidos']);
		}

		$ok = $fichaTecnicaModel->moverRango($idFicha, $direccion, $idUsuarioSesion);
		if (!$ok) {
			responderJson(['ok' => false, 'error' => 'No se pudo mover la ficha técnica.']);
		}
		responderJson(['ok' => true]);

	// Obtiene el estado de cierre de una tecnología en un año específico
	case 'obtenerCierreTecnologiaAjax':
		$idCat = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		$anio  = isset($_GET['anio']) ? (int) $_GET['anio'] : 0;
		if ($idCat <= 0 || $anio <= 0) {
			responderJson(['ok' => false, 'error' => 'Parámetros inválidos']);
		}
		$cierre = $cierreModel->obtenerPorTecnologiaYAnio($idCat, $anio);
		responderJson([
			'ok'            => true,
			'finalizado'    => !empty($cierre) && (int) $cierre['Estado'] === 1,
			'fecha'         => !empty($cierre) ? $cierre['FechaFinalizacion'] : null,
		]);

	// Cambia el estado de cierre (finalizar o aperturar) de una tecnología
	case 'cambiarCierreTecnologiaAjax':
		$input  = obtenerInputJsonPost();
		$idCat  = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$anio   = obtenerEnteroInput($input, 'Anio');
		$accion = isset($input['Accion']) ? strtolower(trim((string) $input['Accion'])) : '';
		if ($idCat <= 0 || $anio <= 0 || !in_array($accion, ['finalizar', 'aperturar'], true)) {
			responderJson(['ok' => false, 'error' => 'Datos inválidos']);
		}
		if ($accion === 'finalizar') {
			if (!$verificacionTecnicaModel->obtenerPorTecnologia($idCat, $anio)) {
				responderJson(['ok' => false, 'error' => 'Debe registrar la verificación técnica antes de finalizar.']);
			}
			$ok = $cierreModel->finalizar($idCat, $anio, $idUsuarioSesion);
		} else {
			$ok = $cierreModel->aperturar($idCat, $anio, $idUsuarioSesion);
		}
		if (!$ok) {
			responderJson(['ok' => false, 'error' => 'No se pudo actualizar el estado de cierre.']);
		}
		$cierre = $cierreModel->obtenerPorTecnologiaYAnio($idCat, $anio);
		responderJson([
			'ok'         => true,
			'finalizado' => !empty($cierre) && (int) $cierre['Estado'] === 1,
			'fecha'      => !empty($cierre) ? $cierre['FechaFinalizacion'] : null,
		]);

	// Obtiene datos de presupuesto de una tecnología para un año
	case 'obtenerPresupuestoTecnologiaAjax':
		$idCat = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		$anio  = isset($_GET['anio']) ? (int) $_GET['anio'] : 0;
		if ($idCat <= 0 || $anio <= 0) {
			responderJson(['ok' => false, 'error' => 'Parámetros inválidos']);
		}
		$presupuesto = $presupuestoModel->obtenerPorTecnologiaYAnio($idCat, $anio);
		responderJson([
			'ok'    => true,
			'datos' => $presupuesto,
		]);

	// Guarda o actualiza el presupuesto de una tecnología
	case 'guardarPresupuestoTecnologiaAjax':
		$input = obtenerInputJsonPost();
		$idCat = obtenerEnteroInput($input, 'IdCatalogoTecnologico');
		$anio  = obtenerEnteroInput($input, 'Anio');
		$monto = isset($input['Monto']) && $input['Monto'] !== null && $input['Monto'] !== '' ? $input['Monto'] : null;
		if ($idCat <= 0 || $anio <= 0) {
			responderJson(['ok' => false, 'error' => 'Faltan campos obligatorios']);
		}
		if ($monto !== null && (!is_numeric($monto) || (float) $monto < 0)) {
			responderJson(['ok' => false, 'error' => 'El monto debe ser un número positivo']);
		}
		$existente = $presupuestoModel->obtenerPorTecnologiaYAnio($idCat, $anio);
		if ($existente) {
			$ok = $presupuestoModel->actualizar((int) $existente['Id'], ['Monto' => $monto]);
			if ($ok) {
				responderJson(['ok' => true, 'accion' => 'actualizado']);
			}
			responderErrorSql('No se pudo actualizar el presupuesto.');
		} else {
			$resultado = $presupuestoModel->guardar([
				'IdCatalogoTecnologico' => $idCat,
				'Anio'                 => $anio,
				'Monto'                => $monto,
				'idUsuarioRegistro'    => $idUsuarioSesion,
			]);
			if ($resultado) {
				responderJson(['ok' => true, 'accion' => 'creado']);
			}

			// Idempotencia: si el INSERT fallo pero el registro ya existe, tratarlo como actualizado.
			$existenteTrasFallo = $presupuestoModel->obtenerPorTecnologiaYAnio($idCat, $anio);
			if ($existenteTrasFallo) {
				$ok = $presupuestoModel->actualizar((int) $existenteTrasFallo['Id'], ['Monto' => $monto]);
				if ($ok) {
					responderJson(['ok' => true, 'accion' => 'actualizado']);
				}
			}

			responderErrorSql('No se pudo registrar el presupuesto.');
		}

	// Redirige a vista de tecnologías por defecto
	default:
		adqRedirigirSeguro('index.php?module=adquisiciones&action=tecnologias');
}

include 'modules/adquisiciones/views/index.php';