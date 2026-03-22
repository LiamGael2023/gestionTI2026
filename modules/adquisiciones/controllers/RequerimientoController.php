<?php
require_once 'modules/adquisiciones/models/RequerimientoModel.php';
require_once 'modules/adquisiciones/models/CatalogoTecnologicoModel.php';

function cargarVistaRequerimientos($model, $anioFiltro, &$vistaActual, &$requerimientos, &$centrosCosto, &$aniosDisponibles)
{
	$vistaActual = 'requerimientos';
	$centrosCosto = $model->obtenerCentrosCosto();
	$aniosDisponibles = $model->obtenerAniosDisponibles();
	$anioFiltro = resolverAnioFiltro($anioFiltro, $aniosDisponibles);
	$requerimientos = $model->listarRequerimientos($anioFiltro);

	return $anioFiltro;
}

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

function delegarControladorAdquisiciones($ruta)
{
	include $ruta;
	exit;
}

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$model = new RequerimientoModel($conn);
$action = $_GET['action'] ?? 'requerimientos';
$vistaActual = 'requerimientos';
$requerimientos = [];
$centrosCosto = [];
$aniosDisponibles = [];
$anioFiltro = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : null;
$accionesDetalle = ['guardarDetalleAjax', 'actualizarDetalleAjax', 'eliminarDetalleAjax', 'actualizarEstadoAjax', 'guardarDetalleForm'];
$accionesTecnologia = [
	'tecnologia',
	'verEspecificacionTecnicaAjax',
	'guardarEspecificacionTecnicaAjax',
	'actualizarEspecificacionTecnicaAjax',
	'eliminarEspecificacionTecnicaAjax',
	'verVerificacionTecnicaAjax',
	'guardarVerificacionTecnicaAjax',
	'actualizarVerificacionTecnicaAjax',
	'eliminarVerificacionTecnicaAjax',
	'verConformidadAjax',
	'guardarConformidadAjax',
	'actualizarConformidadAjax',
	'eliminarConformidadAjax',
	'verFichaTecnicaAjax',
	'guardarFichaTecnicaAjax',
	'eliminarFichaTecnicaAjax',
	'cambiarEstadoFichaTecnicaAjax',
];

if ($action === 'requerimiento') {
	delegarControladorAdquisiciones('modules/adquisiciones/controllers/DetalleRequerimientoController.php');
}

if (in_array($action, $accionesDetalle, true)) {
	delegarControladorAdquisiciones('modules/adquisiciones/controllers/DetalleRequerimientoController.php');
}

if (in_array($action, $accionesTecnologia, true)) {
	delegarControladorAdquisiciones('modules/adquisiciones/controllers/EspecificacionTecnicaController.php');
}

switch ($action) {
	case 'index':
	case 'requerimientos':
		$anioFiltro = cargarVistaRequerimientos($model, $anioFiltro, $vistaActual, $requerimientos, $centrosCosto, $aniosDisponibles);
		break;

	case 'tecnologias':
		$vistaActual = 'tecnologias';
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		$anioTecnologias = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : (int) date('Y');
		$tecnologias = $catalogoModel->listarConEstadoFicha($anioTecnologias);
		$aniosTecnologias = $catalogoModel->obtenerAniosDisponibles();
		break;

	case 'consolidado':
		$vistaActual = 'consolidado';
		$aniosDisponibles = $model->obtenerAniosDisponibles();
		$anioFiltro = resolverAnioFiltro($anioFiltro, $aniosDisponibles);
		$consolidado = $model->obtenerConsolidado($anioFiltro);
		break;

	case 'guardarAjax':
		header('Content-Type: application/json');

		$datos = [
			'IdCentroCosto' => isset($_POST['IdCentroCosto']) ? (int) $_POST['IdCentroCosto'] : 0,
			'NroPedidoCompra' => isset($_POST['NroPedidoCompra']) ? trim($_POST['NroPedidoCompra']) : '',
			'Anio' => isset($_POST['Anio']) ? (int) $_POST['Anio'] : 0
		];

		if ($datos['IdCentroCosto'] > 0 && !empty($datos['NroPedidoCompra']) && $datos['Anio'] > 0) {
			$id = $model->guardarRequerimiento($datos);
			if ($id) {
				echo json_encode(['success' => true, 'message' => 'Requerimiento registrado correctamente', 'id' => $id]);
			} else {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				$detalle = '';
				if (is_array($errors) && count($errors) > 0) {
					$detalle = ' - ' . $errors[0]['message'];
				}
				echo json_encode(['success' => false, 'message' => 'No se pudo guardar el requerimiento' . $detalle]);
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
		}
		exit;

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

	case 'importarPedidoSigaAjax':
		header('Content-Type: application/json');
		$nroPedido = isset($_POST['nro_pedido']) ? trim($_POST['nro_pedido']) : '';
		$anio      = isset($_POST['anio']) ? (int) $_POST['anio'] : 0;

		if (empty($nroPedido) || $anio < 2018 || $anio > 2099) {
			echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
			exit;
		}

		try {
			$resultado = $model->importarPedidoSiga($nroPedido, $anio);
			echo json_encode([
				'success' => true,
				'items'   => $resultado['items'],
			]);
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}
		exit;

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

	default:
		$anioFiltro = cargarVistaRequerimientos($model, $anioFiltro, $vistaActual, $requerimientos, $centrosCosto, $aniosDisponibles);
		break;
}

include 'modules/adquisiciones/views/index.php';
