<?php
require_once 'modules/comunicados/models/ArchivoModel.php';
require_once 'modules/comunicados/models/ComunicadoModel.php';

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$archivoModel = new ArchivoModel($conn);
$comunicadoModelArchivo = new ComunicadoModel($conn);
$action = $_GET['action'] ?? 'archivos';
$idUsuarioSesion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

function comArchivoJson($payload)
{
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($payload);
	exit;
}

function comArchivoBaseUrl()
{
	$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
	return $base !== '' ? $base : rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
}

function comArchivoUploadDir()
{
	$directorio = defined('COMUNICADOS_UPLOAD_DIR') ? COMUNICADOS_UPLOAD_DIR : 'D:\\SISTEMAS\\APP\\repositorio-chavisystems\\comunicados';
	return rtrim((string) $directorio, "\\/");
}

function comArchivoUrl($nombreServidor)
{
	$base = defined('COMUNICADOS_PUBLIC_URL') ? COMUNICADOS_PUBLIC_URL : 'https://app.chavimochic.gob.pe/repositorio-chavisystems/comunicados';
	return rtrim((string) $base, '/') . '/' . rawurlencode((string) $nombreServidor);
}

switch ($action) {
	case 'subirArchivoAjax':
		if (empty($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
			comArchivoJson(['success' => false, 'message' => 'No se recibio ningun archivo.']);
		}

		$file = $_FILES['archivo'];
		$maxBytes = 5 * 1024 * 1024;
		if ((int) $file['size'] > $maxBytes) {
			comArchivoJson(['success' => false, 'message' => 'El archivo supera el limite de 5 MB.']);
		}

		$nombreOriginal = (string) $file['name'];
		$extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
		$permitidos = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
		if (!in_array($extension, $permitidos, true)) {
			comArchivoJson(['success' => false, 'message' => 'Tipo de archivo no permitido.']);
		}

		$tipoMime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : ($file['type'] ?? null);
		$esImagen = in_array($extension, ['jpg', 'jpeg', 'png'], true);
		$nombreServidor = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
		$directorio = comArchivoUploadDir();
		$idComunicado = isset($_POST['IdComunicado']) ? (int) $_POST['IdComunicado'] : null;

		if ($idComunicado > 0 && !$comunicadoModelArchivo->obtener($idComunicado, $idUsuarioSesion)) {
			comArchivoJson(['success' => false, 'message' => 'El comunicado indicado no existe.']);
		}

		if (!is_dir($directorio)) {
			comArchivoJson(['success' => false, 'message' => 'El directorio de carga no existe o no esta disponible.']);
		}

		$rutaDestino = $directorio . DIRECTORY_SEPARATOR . $nombreServidor;
		if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
			comArchivoJson(['success' => false, 'message' => 'No se pudo guardar el archivo en el servidor.']);
		}

		$rutaRelativa = $rutaDestino;
		$urlPublica = comArchivoUrl($nombreServidor);

		$idArchivo = $archivoModel->guardar([
			'IdComunicado' => $idComunicado,
			'NombreOriginal' => $nombreOriginal,
			'NombreServidor' => $nombreServidor,
			'RutaRelativa' => $rutaRelativa,
			'UrlPublica' => $urlPublica,
			'TipoMime' => $tipoMime,
			'ExtensionArchivo' => $extension,
			'TamanoBytes' => (int) $file['size'],
			'TipoArchivo' => $esImagen ? 'IMAGEN' : 'DOCUMENTO',
			'IdUsuarioRegistro' => $idUsuarioSesion,
		]);

		if (!$idArchivo) {
			comArchivoJson(['success' => false, 'message' => 'El archivo se guardo, pero no se pudo registrar en base de datos.']);
		}

		comArchivoJson([
			'success' => true,
			'id' => $idArchivo,
			'archivo' => [
				'IdArchivo' => $idArchivo,
				'NombreOriginal' => $nombreOriginal,
				'UrlPublica' => $urlPublica,
				'TipoArchivo' => $esImagen ? 'IMAGEN' : 'DOCUMENTO',
				'TamanoBytes' => (int) $file['size'],
			],
		]);
		break;

	case 'eliminarArchivoAjax':
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$ok = $id > 0 && $archivoModel->cambiarEstado($id, 0, $idUsuarioSesion);
		comArchivoJson(['success' => $ok, 'message' => $ok ? 'Archivo inactivado.' : 'No se pudo inactivar el archivo.']);
		break;

	default:
		$vistaActual = 'archivo';
		$archivos = $archivoModel->listar(false, $idUsuarioSesion);
		include 'modules/comunicados/views/index.php';
		break;
}
