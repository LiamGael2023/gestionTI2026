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

function redireccionarAdquisiciones($url)
{
	if (!headers_sent()) {
		header('Location: ' . $url);
		exit;
	}

	echo '<script>window.location.href=' . json_encode($url) . ';</script>';
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
$dashboardResumenGeneral = [];
$dashboardItemsPorTipo = [];
$dashboardCentroCosto = [];
$dashboardEstadoDocumental = [];
$dashboardOrdenesProximas = [];
$idUsuarioSesion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
$accionesDetalle = ['guardarDetalleAjax', 'actualizarDetalleAjax', 'eliminarDetalleAjax', 'actualizarEstadoAjax', 'guardarDetalleForm'];
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
		$aniosTecnologias = $catalogoModel->obtenerAniosDisponibles();
		$anioTecnologiasSolicitado = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : null;
		$anioTecnologias = resolverAnioFiltro($anioTecnologiasSolicitado, $aniosTecnologias);
		$tecnologias = $catalogoModel->listarConEstadoFicha($anioTecnologias);
		break;

	case 'consolidado':
		$vistaActual = 'consolidado';
		$aniosDisponibles = $model->obtenerAniosDisponibles();
		$anioFiltro = resolverAnioFiltro($anioFiltro, $aniosDisponibles);
		$consolidado = $model->obtenerConsolidado($anioFiltro);
		break;

	case 'dashboard':
		$vistaActual = 'dashboard';
		$aniosDisponibles = $model->obtenerAniosDisponibles();
		$anioFiltro = resolverAnioFiltro($anioFiltro, $aniosDisponibles);
		$dashboardResumenGeneral = $model->obtenerDashboardResumenGeneral($anioFiltro);
		$dashboardItemsPorTipo = $model->obtenerDashboardItemsPorTipo($anioFiltro);
		$dashboardCentroCosto = $model->obtenerDashboardCentroCosto($anioFiltro);
		$dashboardEstadoDocumental = $model->obtenerDashboardEstadoDocumental($anioFiltro);
		$dashboardOrdenesProximas = $model->obtenerDashboardOrdenesProximas($anioFiltro, 30, 6);
		break;

	case 'guardarAjax':
		header('Content-Type: application/json');

		$datos = [
			'IdCentroCosto' => isset($_POST['IdCentroCosto']) ? (int) $_POST['IdCentroCosto'] : 0,
			'NroPedidoCompra' => isset($_POST['NroPedidoCompra']) ? trim($_POST['NroPedidoCompra']) : '',
			'Anio' => isset($_POST['Anio']) ? (int) $_POST['Anio'] : 0,
			'idUsuarioRegistro' => $idUsuarioSesion
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

	case 'guardarForm':
		$datos = [
			'IdCentroCosto' => isset($_POST['IdCentroCosto']) ? (int) $_POST['IdCentroCosto'] : 0,
			'NroPedidoCompra' => isset($_POST['NroPedidoCompra']) ? trim((string) $_POST['NroPedidoCompra']) : '',
			'Anio' => isset($_POST['Anio']) ? (int) $_POST['Anio'] : 0,
			'idUsuarioRegistro' => $idUsuarioSesion,
		];

		$anioRedirect = $datos['Anio'] > 0 ? $datos['Anio'] : (int) date('Y');
		$urlRedirect = 'index.php?module=adquisiciones&action=requerimientos&anio=' . $anioRedirect;

		if ($datos['IdCentroCosto'] <= 0 || $datos['NroPedidoCompra'] === '' || $datos['Anio'] <= 0) {
			redireccionarAdquisiciones($urlRedirect);
		}

		$model->guardarRequerimiento($datos);
		redireccionarAdquisiciones($urlRedirect);
		break;

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
			$resultado = $model->importarPedidoSiga($nroPedido, $anio, $idUsuarioSesion);
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

	case 'listarCentrosCostoAjax':
		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => $model->listarCentrosCostoGestion(),
		]);
		exit;

	case 'agregarCentroCostoAjax':
		header('Content-Type: application/json');
		$siglas = isset($_POST['siglas']) ? trim((string) $_POST['siglas']) : '';
		$nombreCentroCosto = isset($_POST['nombreCentroCosto']) ? trim((string) $_POST['nombreCentroCosto']) : '';
		echo json_encode($model->agregarCentroCosto($siglas, $nombreCentroCosto));
		exit;

	case 'actualizarCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$siglas = isset($_POST['siglas']) ? trim((string) $_POST['siglas']) : '';
		$nombreCentroCosto = isset($_POST['nombreCentroCosto']) ? trim((string) $_POST['nombreCentroCosto']) : '';
		echo json_encode($model->actualizarCentroCosto($id, $siglas, $nombreCentroCosto));
		exit;

	case 'eliminarCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->eliminarCentroCosto($id));
		exit;

	case 'activarCentroCostoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		echo json_encode($model->activarCentroCosto($id));
		exit;

	case 'listarTecnologiasCatalogoAjax':
		header('Content-Type: application/json');
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode([
			'success' => true,
			'data' => $catalogoModel->listarTecnologiasActivas(),
		]);
		exit;

	case 'actualizarTecnologiaCatalogoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$codigo = isset($_POST['codigo']) ? trim((string) $_POST['codigo']) : '';
		$nombreGenerico = isset($_POST['nombreGenerico']) ? trim((string) $_POST['nombreGenerico']) : '';
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->actualizarTecnologia($id, $codigo, $nombreGenerico));
		exit;

	case 'eliminarTecnologiaCatalogoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->eliminarTecnologia($id));
		exit;

	case 'activarTecnologiaCatalogoAjax':
		header('Content-Type: application/json');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$catalogoModel = new CatalogoTecnologicoModel($conn);
		echo json_encode($catalogoModel->activarTecnologia($id));
		exit;

	default:
		$anioFiltro = cargarVistaRequerimientos($model, $anioFiltro, $vistaActual, $requerimientos, $centrosCosto, $aniosDisponibles);
		break;
}

include 'modules/adquisiciones/views/index.php';
