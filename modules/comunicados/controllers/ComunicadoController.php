<?php
require_once 'modules/comunicados/models/ComunicadoModel.php';
require_once 'modules/comunicados/models/PlantillaModel.php';
require_once 'modules/comunicados/models/ArchivoModel.php';

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$model = new ComunicadoModel($conn);
$plantillaModel = new PlantillaModel($conn);
$archivoModel = new ArchivoModel($conn);
$action = $_GET['action'] ?? 'dashboard';
$idUsuarioSesion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
$esAdministrador = $idUsuarioSesion === 1;
$idUsuarioPropietarioFiltro = $esAdministrador ? null : $idUsuarioSesion;
$unidadIdSesion = $model->obtenerUnidadUsuario($idUsuarioSesion);
$unidadIdVisibilidad = $esAdministrador ? null : $unidadIdSesion;
$idUsuarioVisibilidad = (!$esAdministrador && $unidadIdSesion === null) ? $idUsuarioSesion : null;

function comJson($payload)
{
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($payload);
	exit;
}

function comPayload()
{
	$raw = file_get_contents('php://input');
	$json = $raw ? json_decode($raw, true) : null;
	return is_array($json) ? $json : $_POST;
}

function comDelegar($ruta)
{
	include $ruta;
	exit;
}

if (in_array($action, [
	'plantillas',
	'guardarPlantillaAjax',
	'eliminarPlantillaAjax',
], true)) {
	comDelegar('modules/comunicados/controllers/PlantillaController.php');
}

if (in_array($action, [
	'archivos',
	'subirArchivoAjax',
	'eliminarArchivoAjax',
], true)) {
	comDelegar('modules/comunicados/controllers/ArchivoController.php');
}

switch ($action) {
	case 'guardarComunicadoAjax':
		$payload = comPayload();
		$id = isset($payload['IdComunicado']) ? (int) $payload['IdComunicado'] : 0;
		$titulo = trim((string) ($payload['TituloComunicado'] ?? ''));

		if ($titulo === '') {
			comJson(['success' => false, 'message' => 'Debe ingresar el titulo del comunicado.']);
		}

		if ($unidadIdSesion === null) {
			comJson(['success' => false, 'message' => 'El usuario no tiene una unidad asociada y no puede guardar comunicados.']);
		}

		$idPlantillaPayload = isset($payload['IdPlantilla']) ? (int) $payload['IdPlantilla'] : 0;
		if ($idPlantillaPayload > 0 && !$plantillaModel->obtener($idPlantillaPayload)) {
			comJson(['success' => false, 'message' => 'La plantilla indicada no existe.']);
		}

		$datos = [
			'IdPlantilla' => $idPlantillaPayload,
			'TituloComunicado' => $titulo,
			'ContenidoJson' => $payload['ContenidoJson'] ?? '[]',
			'HtmlFinal' => $payload['HtmlFinal'] ?? null,
			'EstadoComunicado' => $payload['EstadoComunicado'] ?? 'BORRADOR',
			'IdUsuarioRegistro' => $idUsuarioSesion,
			'IdUsuarioModificacion' => $idUsuarioSesion,
			'IdUnidad' => $unidadIdSesion,
		];

		if ($id > 0) {
			$comunicadoActual = $model->obtenerEditable($id, $unidadIdVisibilidad, $idUsuarioVisibilidad);
			if (!$comunicadoActual || (int) ($comunicadoActual['Activo'] ?? 0) !== 1) {
				comJson(['success' => false, 'message' => 'No se puede editar un comunicado inactivo.']);
			}

			$esAutor = (int) ($comunicadoActual['IdUsuarioRegistro'] ?? 0) === $idUsuarioSesion;
			$puedeAdministrarEstado = $esAdministrador || $esAutor;
			$estadoActual = strtoupper((string) ($comunicadoActual['EstadoComunicado'] ?? 'BORRADOR'));
			$estadoSolicitado = strtoupper(trim((string) ($datos['EstadoComunicado'] ?? 'BORRADOR')));

			if (!$puedeAdministrarEstado && $estadoActual === 'LISTO') {
				comJson(['success' => false, 'message' => 'El comunicado ya fue publicado y solo el autor o el administrador puede modificarlo.']);
			}

			if (!$puedeAdministrarEstado && $estadoSolicitado !== $estadoActual) {
				comJson(['success' => false, 'message' => 'Solo el autor o el administrador puede publicar el comunicado o devolverlo a borrador.']);
			}

			if (!$esAdministrador && $estadoActual === 'LISTO' && $estadoSolicitado === 'LISTO') {
				comJson(['success' => false, 'message' => 'Debe devolver el comunicado a borrador antes de modificar su contenido.']);
			}

			$ok = $model->actualizar($id, $datos, $unidadIdVisibilidad, $idUsuarioVisibilidad);
			comJson(['success' => $ok, 'id' => $id, 'message' => $ok ? 'Comunicado actualizado.' : 'No se pudo actualizar el comunicado.']);
		}

		$nuevoId = $model->guardar($datos);
		comJson(['success' => (bool) $nuevoId, 'id' => $nuevoId, 'message' => $nuevoId ? 'Comunicado registrado.' : 'No se pudo registrar el comunicado.']);
		break;

	case 'eliminarComunicadoAjax':
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$ok = $id > 0 && $model->cambiarEstadoActivo($id, 0, $idUsuarioSesion, $idUsuarioPropietarioFiltro);
		comJson(['success' => $ok, 'message' => $ok ? 'Comunicado inactivado.' : 'No se pudo inactivar el comunicado.']);
		break;

	case 'activarComunicadoAjax':
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$ok = $id > 0 && $model->cambiarEstadoActivo($id, 1, $idUsuarioSesion, $idUsuarioPropietarioFiltro);
		comJson(['success' => $ok, 'message' => $ok ? 'Comunicado activado.' : 'No se pudo activar el comunicado.']);
		break;

	case 'convertirComunicadoPlantillaAjax':
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$comunicado = $id > 0 ? $model->obtenerVisible($id, $unidadIdVisibilidad, $idUsuarioVisibilidad) : null;

		if (!$comunicado) {
			comJson(['success' => false, 'message' => 'No se encontro el comunicado.']);
		}

		$nombre = trim((string) ($_POST['nombre'] ?? ''));
		if ($nombre === '') {
			$nombre = 'Plantilla - ' . (string) ($comunicado['TituloComunicado'] ?? 'Comunicado');
		}

		$idPlantilla = $plantillaModel->guardar([
			'NombrePlantilla' => $nombre,
			'DescripcionPlantilla' => trim((string) ($_POST['descripcion'] ?? 'Generada desde comunicado')),
			'ContenidoJson' => (string) ($comunicado['ContenidoJson'] ?? '[]'),
			'HtmlBase' => (string) ($comunicado['HtmlFinal'] ?? ''),
			'IdUsuarioRegistro' => $idUsuarioSesion,
		]);

		comJson([
			'success' => (bool) $idPlantilla,
			'id' => $idPlantilla,
			'message' => $idPlantilla ? 'Plantilla creada desde el comunicado.' : 'No se pudo crear la plantilla.',
		]);
		break;

	case 'visualizar':
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		$comunicado = $id > 0 ? $model->obtenerVisible($id, $unidadIdVisibilidad, $idUsuarioVisibilidad) : null;
		include 'modules/comunicados/views/comunicado/visualizar.php';
		exit;

	case 'editor':
		$vistaActual = 'comunicado_editor';
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		$idPlantilla = isset($_GET['plantilla']) ? (int) $_GET['plantilla'] : 0;
		$comunicado = $id > 0 ? $model->obtenerEditable($id, $unidadIdVisibilidad, $idUsuarioVisibilidad) : null;
		$esAutorComunicado = !$comunicado || $esAdministrador || (int) ($comunicado['IdUsuarioRegistro'] ?? 0) === $idUsuarioSesion;
		$estaPublicado = $comunicado && strtoupper((string) ($comunicado['EstadoComunicado'] ?? 'BORRADOR')) === 'LISTO';
		if ($id > 0 && (!$comunicado || (int) ($comunicado['Activo'] ?? 0) !== 1 || ($estaPublicado && !$esAutorComunicado))) {
			header('Location: index.php?module=comunicados&action=comunicados');
			exit;
		}
		$plantillaBase = (!$comunicado && $idPlantilla > 0) ? $plantillaModel->obtener($idPlantilla, $idUsuarioPropietarioFiltro) : null;
		$plantillas = $plantillaModel->listar(true, $idUsuarioPropietarioFiltro);
		$archivos = $archivoModel->listar(true, $idUsuarioPropietarioFiltro);
		break;

	case 'comunicados':
		$vistaActual = 'comunicado';
		$comunicados = $model->listar(false, $unidadIdVisibilidad, $idUsuarioVisibilidad);
		break;

	case 'dashboard':
	default:
		$vistaActual = 'dashboard';
		$resumenComunicados = $model->obtenerResumen($unidadIdVisibilidad, $idUsuarioVisibilidad);
		$comunicadosRecientes = array_slice($model->listar(true, $unidadIdVisibilidad, $idUsuarioVisibilidad), 0, 3);
		$plantillasActivas = $plantillaModel->listar(true, $idUsuarioPropietarioFiltro);
		$archivosRecientes = array_slice($archivoModel->listar(true, $idUsuarioPropietarioFiltro), 0, 5);
		break;
}

include 'modules/comunicados/views/index.php';
