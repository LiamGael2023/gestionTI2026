<?php
require_once 'modules/adquisiciones/models/RequerimientoModel.php';
require_once 'modules/adquisiciones/models/DetalleRequerimientoModel.php';
require_once 'modules/adquisiciones/models/DistribucionDetalleModel.php';
require_once 'modules/adquisiciones/helpers.php';

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$requerimientoModel = new RequerimientoModel($conn);
$model = new DetalleRequerimientoModel($conn);
$distribucionModel = new DistribucionDetalleModel($conn);
$action = $_GET['action'] ?? 'requerimiento';
$vistaActual = 'detalle';
$requerimiento = null;
$detalles = [];
$catalogoOpciones = [];
$centrosCostoDistribucion = [];
$subCentrosCostoDistribucion = [];
$idUsuarioSesion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;

// Normaliza el clasificador removiendo espacios, convirtiendo a mayúsculas y limitando a 12 caracteres
function normalizarClasificadorDetalle($valor)
{
	$clasificador = trim((string) $valor);
	if ($clasificador === '') {
		return null;
	}

	$clasificador = str_replace('  ', '.', $clasificador);
	$clasificador = str_replace(' ', '', $clasificador);
	while (strpos($clasificador, '..') !== false) {
		$clasificador = str_replace('..', '.', $clasificador);
	}

	$clasificador = strtoupper(substr($clasificador, 0, 12));

	return $clasificador !== '' ? $clasificador : null;
}

switch ($action) {
	// Carga la vista de detalle del requerimiento con sus detalles y opciones de distribución
	case 'requerimiento':
		$vistaActual = 'detalle';
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		if ($id > 0) {
			$requerimiento = $requerimientoModel->obtenerRequerimientoPorId($id);
			$detalles = $model->listarDetallesPorRequerimiento($id);
			$catalogoOpciones = $model->listarOpcionesCatalogoTecnologico();
			$centrosCostoDistribucion = $requerimientoModel->obtenerCentrosCosto();
			$subCentrosCostoDistribucion = $requerimientoModel->obtenerSubCentrosCostoActivos();
		}
		if (!$requerimiento) {
			adqRedirigirSeguro('index.php?module=adquisiciones&action=requerimientos');
		}
		break;

	// Guarda un nuevo detalle de requerimiento vía AJAX validando datos completos
	case 'guardarDetalleAjax':
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		
		$datos = [
			'IdRequerimiento' => isset($_POST['IdRequerimiento']) ? (int) $_POST['IdRequerimiento'] : 0,
			'IdCatalogoTecnologico' => isset($_POST['IdCatalogoTecnologico']) ? (int) $_POST['IdCatalogoTecnologico'] : 0,
			'CodigoSiga' => isset($_POST['CodigoSiga']) ? trim($_POST['CodigoSiga']) : '',
			'Clasificador' => normalizarClasificadorDetalle($_POST['Clasificador'] ?? null),
			'DescripcionDetallada' => isset($_POST['DescripcionDetallada']) ? trim($_POST['DescripcionDetallada']) : '',
			'Cantidad' => isset($_POST['Cantidad']) ? (int) $_POST['Cantidad'] : 0,
			'UnidadMedida' => isset($_POST['UnidadMedida']) ? strtoupper(trim($_POST['UnidadMedida'])) : 'UND',
			'idUsuarioRegistro' => $idUsuarioSesion
		];

		if ($datos['IdRequerimiento'] > 0 && $datos['IdCatalogoTecnologico'] > 0 && !empty($datos['CodigoSiga']) && !empty($datos['DescripcionDetallada']) && $datos['Cantidad'] > 0) {
			$id = $model->guardarDetalle($datos);
			if ($id) {
				echo json_encode(['success' => true, 'message' => 'Detalle registrado correctamente', 'id' => $id]);
			} else {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				$detalle = '';
				if (is_array($errors) && count($errors) > 0) {
					$detalle = ' - ' . $errors[0]['message'];
				}
				echo json_encode(['success' => false, 'message' => 'No se pudo guardar el detalle' . $detalle]);
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
		}
		exit;

	// Actualiza un detalle existente vía AJAX validando que el ID y datos sean válidos
	case 'actualizarDetalleAjax':
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		
		$id = isset($_POST['Id']) ? (int) $_POST['Id'] : 0;
		$datos = [
			'IdCatalogoTecnologico' => isset($_POST['IdCatalogoTecnologico']) ? (int) $_POST['IdCatalogoTecnologico'] : 0,
			'CodigoSiga' => isset($_POST['CodigoSiga']) ? trim($_POST['CodigoSiga']) : '',
			'Clasificador' => normalizarClasificadorDetalle($_POST['Clasificador'] ?? null),
			'DescripcionDetallada' => isset($_POST['DescripcionDetallada']) ? trim($_POST['DescripcionDetallada']) : '',
			'Cantidad' => isset($_POST['Cantidad']) ? (int) $_POST['Cantidad'] : 0,
			'UnidadMedida' => isset($_POST['UnidadMedida']) ? strtoupper(trim($_POST['UnidadMedida'])) : 'UND',
			'idUsuarioModifica' => $idUsuarioSesion
		];

		if ($id > 0 && $datos['IdCatalogoTecnologico'] > 0 && !empty($datos['CodigoSiga']) && !empty($datos['DescripcionDetallada']) && $datos['Cantidad'] > 0) {
			if ($model->actualizarDetalle($id, $datos)) {
				echo json_encode(['success' => true, 'message' => 'Detalle actualizado correctamente']);
			} else {
				$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				$detalle = '';
				if (is_array($errors) && count($errors) > 0) {
					$detalle = ' - ' . $errors[0]['message'];
				}
				echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el detalle' . $detalle]);
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
		}
		exit;

	// Obtiene todas las distribuciones de un detalle vía AJAX
	case 'buscarDetallePorCodigoSigaAjax':
		ini_set('display_errors', '0');
		ini_set('display_startup_errors', '0');
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');

		$codigoSiga = isset($_GET['codigoSiga']) ? trim((string) $_GET['codigoSiga']) : '';
		$idRequerimientoExcluir = isset($_GET['idRequerimiento']) ? (int) $_GET['idRequerimiento'] : 0;

		if ($codigoSiga === '') {
			echo json_encode(['success' => false, 'message' => 'Codigo SIGA vacio']);
			exit;
		}

		$datosSiga = $model->obtenerDatosPorCodigoSiga($codigoSiga, $idRequerimientoExcluir);
		if ($datosSiga) {
			echo json_encode(['success' => true, 'data' => $datosSiga]);
		} else {
			echo json_encode(['success' => false, 'message' => 'No hay datos previos para este Codigo SIGA']);
		}
		exit;

	case 'obtenerDistribucionDetalleAjax':
		ini_set('display_errors', '0');
		ini_set('display_startup_errors', '0');
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		$idDetalle = isset($_GET['idDetalle']) ? (int) $_GET['idDetalle'] : 0;
		if ($idDetalle <= 0) {
			echo json_encode(['success' => false, 'message' => 'Detalle inválido']);
			exit;
		}

		$distribuciones = $distribucionModel->listarPorDetalle($idDetalle);
		echo json_encode(['success' => true, 'distribuciones' => $distribuciones]);
		exit;

	// Guarda o actualiza una distribución del detalle vía AJAX validando duplicados
	case 'guardarDistribucionDetalleAjax':
		ini_set('display_errors', '0');
		ini_set('display_startup_errors', '0');
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		$id = isset($_POST['Id']) ? (int) $_POST['Id'] : 0;
		$datos = [
			'IdDetalleRequerimiento' => isset($_POST['IdDetalleRequerimiento']) ? (int) $_POST['IdDetalleRequerimiento'] : 0,
			'IdCentroCosto' => isset($_POST['IdCentroCosto']) ? (int) $_POST['IdCentroCosto'] : 0,
			'IdSubCentroCosto' => isset($_POST['IdSubCentroCosto']) && $_POST['IdSubCentroCosto'] !== '' ? (int) $_POST['IdSubCentroCosto'] : null,
			'Cantidad' => isset($_POST['Cantidad']) ? (int) $_POST['Cantidad'] : 0,
			'IdUsuarioRegistro' => $idUsuarioSesion,
			'IdUsuarioModifica' => $idUsuarioSesion,
		];

		if ($datos['IdDetalleRequerimiento'] > 0 && $datos['IdCentroCosto'] > 0 && $datos['Cantidad'] > 0) {
			if ($distribucionModel->existeDistribucionDuplicada($datos['IdDetalleRequerimiento'], $datos['IdCentroCosto'], $datos['IdSubCentroCosto'], $id)) {
				echo json_encode(['success' => false, 'message' => 'Ya existe una distribución para el mismo centro/subcentro.']);
				exit;
			}
			if ($id > 0) {
				$success = $distribucionModel->actualizar($id, $datos);
				if ($success) {
					echo json_encode(['success' => true, 'message' => 'Distribución actualizada correctamente']);
				} else {
					echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la distribución']);
				}
			} else {
				$newId = $distribucionModel->guardar($datos);
				if ($newId) {
					echo json_encode(['success' => true, 'message' => 'Distribución registrada correctamente', 'id' => $newId]);
				} else {
					echo json_encode(['success' => false, 'message' => 'No se pudo guardar la distribución']);
				}
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'Datos incompletos para la distribución']);
		}
		exit;

	// Elimina una distribución del detalle vía AJAX
	case 'eliminarDistribucionDetalleAjax':
		ini_set('display_errors', '0');
		ini_set('display_startup_errors', '0');
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		if ($id > 0 && $distribucionModel->eliminar($id)) {
			echo json_encode(['success' => true, 'message' => 'Distribución eliminada correctamente']);
		} else {
			echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la distribución']);
		}
		exit;

	// Guarda o actualiza un detalle mediante formulario HTML tradicional con redireccionamiento
	case 'guardarDetalleForm':
		$id = isset($_POST['Id']) ? (int) $_POST['Id'] : 0;
		$datos = [
			'IdRequerimiento' => isset($_POST['IdRequerimiento']) ? (int) $_POST['IdRequerimiento'] : 0,
			'IdCatalogoTecnologico' => isset($_POST['IdCatalogoTecnologico']) ? (int) $_POST['IdCatalogoTecnologico'] : 0,
			'CodigoSiga' => isset($_POST['CodigoSiga']) ? trim($_POST['CodigoSiga']) : '',
			'Clasificador' => normalizarClasificadorDetalle($_POST['Clasificador'] ?? null),
			'DescripcionDetallada' => isset($_POST['DescripcionDetallada']) ? trim($_POST['DescripcionDetallada']) : '',
			'Cantidad' => isset($_POST['Cantidad']) ? (int) $_POST['Cantidad'] : 0,
			'UnidadMedida' => isset($_POST['UnidadMedida']) ? strtoupper(trim($_POST['UnidadMedida'])) : 'UND'
		];

		$esValido = $datos['IdRequerimiento'] > 0
			&& $datos['IdCatalogoTecnologico'] > 0
			&& !empty($datos['CodigoSiga'])
			&& !empty($datos['DescripcionDetallada'])
			&& $datos['Cantidad'] > 0;

		if ($esValido) {
			if ($id > 0) {
				$datos['idUsuarioModifica'] = $idUsuarioSesion;
				$model->actualizarDetalle($id, $datos);
			} else {
				$datos['idUsuarioRegistro'] = $idUsuarioSesion;
				$model->guardarDetalle($datos);
			}
		}

		adqRedirigirSeguro('index.php?module=adquisiciones&action=requerimiento&id=' . (int) $datos['IdRequerimiento']);

	// Elimina un detalle vía AJAX
	case 'eliminarDetalleAjax':
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		
		if ($id > 0 && $model->eliminarDetalle($id)) {
			echo json_encode(['success' => true, 'message' => 'Detalle eliminado correctamente']);
		} else {
			echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el detalle']);
		}
		exit;

	// Actualiza el estado de un requerimiento vía AJAX
	case 'actualizarEstadoAjax':
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 0;
		
		if ($id > 0 && $requerimientoModel->actualizarEstado($id, $estado, $idUsuarioSesion)) {
			echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
		} else {
			echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado']);
		}
		exit;

	default:
		adqRedirigirSeguro('index.php?module=adquisiciones&action=requerimientos');
}

include 'modules/adquisiciones/views/index.php';
