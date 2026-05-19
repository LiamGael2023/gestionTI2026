<?php
require_once 'modules/comunicados/models/PlantillaModel.php';

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$plantillaModel = new PlantillaModel($conn);
$action = $_GET['action'] ?? 'plantillas';
$idUsuarioSesion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

function comPlantillaJson($payload)
{
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($payload);
	exit;
}

function comPlantillaPayload()
{
	$raw = file_get_contents('php://input');
	$json = $raw ? json_decode($raw, true) : null;
	return is_array($json) ? $json : $_POST;
}

switch ($action) {
	case 'guardarPlantillaAjax':
		$payload = comPlantillaPayload();
		$id = isset($payload['IdPlantilla']) ? (int) $payload['IdPlantilla'] : 0;
		$nombre = trim((string) ($payload['NombrePlantilla'] ?? ''));

		if ($nombre === '') {
			comPlantillaJson(['success' => false, 'message' => 'Debe ingresar el nombre de la plantilla.']);
		}

		$datos = [
			'NombrePlantilla' => $nombre,
			'DescripcionPlantilla' => $payload['DescripcionPlantilla'] ?? null,
			'ContenidoJson' => $payload['ContenidoJson'] ?? '[]',
			'HtmlBase' => $payload['HtmlBase'] ?? null,
			'IdUsuarioRegistro' => $idUsuarioSesion,
			'IdUsuarioModificacion' => $idUsuarioSesion,
		];

		if ($id > 0) {
			$ok = $plantillaModel->actualizar($id, $datos);
			comPlantillaJson(['success' => $ok, 'id' => $id, 'message' => $ok ? 'Plantilla actualizada.' : 'No se pudo actualizar la plantilla.']);
		}

		$nuevoId = $plantillaModel->guardar($datos);
		comPlantillaJson(['success' => (bool) $nuevoId, 'id' => $nuevoId, 'message' => $nuevoId ? 'Plantilla registrada.' : 'No se pudo registrar la plantilla.']);
		break;

	case 'eliminarPlantillaAjax':
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$ok = $id > 0 && $plantillaModel->cambiarEstado($id, 0, $idUsuarioSesion);
		comPlantillaJson(['success' => $ok, 'message' => $ok ? 'Plantilla inactivada.' : 'No se pudo inactivar la plantilla.']);
		break;

	case 'activarPlantillaAjax':
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$ok = $id > 0 && $plantillaModel->cambiarEstado($id, 1, $idUsuarioSesion);
		comPlantillaJson(['success' => $ok, 'message' => $ok ? 'Plantilla activada.' : 'No se pudo activar la plantilla.']);
		break;

	case 'obtenerPlantillaAjax':
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		$plantilla = $id > 0 ? $plantillaModel->obtener($id) : null;
		comPlantillaJson(['success' => (bool) $plantilla, 'data' => $plantilla]);
		break;

	case 'listarPlantillasAjax':
		comPlantillaJson(['success' => true, 'data' => $plantillaModel->listar(false)]);
		break;

	default:
		$vistaActual = 'plantilla';
		$plantillas = $plantillaModel->listar(false);
		include 'modules/comunicados/views/index.php';
		break;
}
