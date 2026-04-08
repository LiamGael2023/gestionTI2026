<?php
require_once 'modules/adquisiciones/models/RequerimientoModel.php';
require_once 'modules/adquisiciones/models/DetalleRequerimientoModel.php';
require_once 'modules/adquisiciones/helpers.php';

if (!isset($conn) || $conn === null) {
	if (!class_exists('Conexion')) {
		require_once 'config/db.php';
	}
	$conn = Conexion::conectar();
}

$requerimientoModel = new RequerimientoModel($conn);
$model = new DetalleRequerimientoModel($conn);
$action = $_GET['action'] ?? 'requerimiento';
$vistaActual = 'detalle';
$requerimiento = null;
$detalles = [];
$catalogoOpciones = [];
$idUsuarioSesion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;

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
	case 'requerimiento':
		$vistaActual = 'detalle';
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		if ($id > 0) {
			$requerimiento = $requerimientoModel->obtenerRequerimientoPorId($id);
			$detalles = $model->listarDetallesPorRequerimiento($id);
			$catalogoOpciones = $model->listarOpcionesCatalogoTecnologico();
		}
		if (!$requerimiento) {
			adqRedirigirSeguro('index.php?module=adquisiciones&action=requerimientos');
		}
		break;

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

	case 'eliminarDetalleAjax':
		adqEnviarHeaderSeguro('Content-Type: application/json; charset=UTF-8');
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		
		if ($id > 0 && $model->eliminarDetalle($id)) {
			echo json_encode(['success' => true, 'message' => 'Detalle eliminado correctamente']);
		} else {
			echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el detalle']);
		}
		exit;

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
