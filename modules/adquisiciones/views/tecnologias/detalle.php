<style>
	.icon-action {
		cursor: pointer;
		font-size: 20px;
		padding: 6px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		line-height: 1;
		vertical-align: middle;
		transition: 0.2s ease;
	}

	.acciones-iconos {
		display: inline-flex;
		align-items: center;
		justify-content: flex-end;
		gap: 0.5rem;
	}
</style>


<?php
$idTec = (int) $tecnologia['Id'];
$codigoTec = htmlspecialchars($tecnologia['Codigo']);
$nombreTec = htmlspecialchars($tecnologia['NombreGenerico']);
$anioActual = isset($anioFiltro) ? (int) $anioFiltro : (int) date('Y');
$minimoFichasRequeridas = 4;
$totalFichas = count($fichasTecnicas);
$tieneFichas = $totalFichas >= $minimoFichasRequeridas;
$tieneEspecificacion = !empty($especificacionTecnica);
$tieneOrdenCompra = !empty($ordenCompra);
$tieneVerificacion = !empty($verificacionTecnica);
$tieneConformidad = !empty($conformidad);
$puedeRegistrarEspecificacion = $tieneFichas;
$puedeRegistrarOrdenCompra = $tieneFichas && $tieneEspecificacion;
$puedeRegistrarVerificacion = $tieneFichas && $tieneEspecificacion && $tieneOrdenCompra;
$puedeRegistrarConformidad = $tieneFichas && $tieneEspecificacion && $tieneVerificacion;
$formatearFecha = static function ($fecha) {
	if (empty($fecha)) {
		return '';
	}

	$timestamp = strtotime((string) $fecha);

	return $timestamp ? date('d-m-Y', $timestamp) : (string) $fecha;
};
$formatearHora = static function ($fecha) {
	if (empty($fecha)) {
		return '';
	}

	$timestamp = strtotime((string) $fecha);

	return $timestamp ? date('H:i', $timestamp) : '';
};
$codigosSigaDetectados = [];
foreach ($pedidos as $pedido) {
	$codigoSigaPedido = isset($pedido['CodigoSiga']) ? trim((string) $pedido['CodigoSiga']) : '';
	if ($codigoSigaPedido !== '' && !in_array($codigoSigaPedido, $codigosSigaDetectados, true)) {
		$codigosSigaDetectados[] = $codigoSigaPedido;
	}
}
$hayDiferenciaCodigoSiga = count($codigosSigaDetectados) > 1;
?>

<div class="bg-primary text-white p-3 rounded mb-3">
	<h3 class="mb-0 fw-bold fs-5"><span class="badge me-2" style="background:rgba(255,255,255,.2)"><?php echo $codigoTec; ?></span> <?php echo $nombreTec; ?></h3>
</div>

<div class="card card-body mb-3">
	<h4 class="fw-bold mb-3">Pedidos de Compra donde aparece</h4>

	<?php if ($hayDiferenciaCodigoSiga): ?>
		<div class="alert alert-warning" role="alert">
			Se detectaron diferencias de Código SIGA para esta tecnología en el año <?php echo $anioActual; ?>.
			Códigos encontrados: <strong><?php echo htmlspecialchars(implode(', ', $codigosSigaDetectados)); ?></strong>.
			Revise la tabla para identificar qué pedido debe ser corregido.
		</div>
	<?php endif; ?>

	<div class="d-flex gap-2 align-items-center mb-3">
		<label class="form-label mb-0">Año:</label>
		<select id="filtroAnioPedidos" class="form-select" style="width: 120px;" onchange="cambiarAnioDetalle()">
			<?php foreach ($aniosDisponiblesTec as $a): ?>
				<option value="<?php echo (int) $a; ?>" <?php echo ($anioActual == $a) ? 'selected' : ''; ?>>
					<?php echo (int) $a; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="table-responsive">
		<table class="table table-vcenter card-table table-striped">
			<thead>
				<tr>
					<th>Nro. de Pedido</th>
					<th>Dirección Solicitante</th>
					<th>Código SIGA</th>
					<th>Descripción</th>
					<th>Cantidad</th>
				</tr>
			</thead>
			<tbody>
				<?php if (!empty($pedidos)): ?>
					<?php foreach ($pedidos as $p): ?>
						<tr>
							<td><?php echo htmlspecialchars($p['NroPedidoCompra']); ?></td>
							<td><?php echo htmlspecialchars($p['DireccionSolicitante']); ?></td>
							<td>
								<span class="badge bg-azure-lt"><?php echo htmlspecialchars($p['CodigoSiga']); ?></span>
							</td>
							<td><?php echo htmlspecialchars($p['DescripcionDetallada']); ?></td>
							<td><?php echo (float) $p['Cantidad']; ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="5" class="text-center text-secondary">No hay pedidos de compra registrados para este año.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="card card-body mb-3">
	<h4 class="fw-bold mb-3">Fichas Técnicas</h4>

	<?php if ($totalFichas < $minimoFichasRequeridas): ?>
		<div class="alert alert-info" role="alert">
			Debe registrar al menos <?php echo $minimoFichasRequeridas; ?> fichas técnicas. Actualmente tiene <?php echo $totalFichas; ?>.
		</div>
	<?php endif; ?>

	<div class="table-responsive">
		<table class="table table-vcenter card-table table-striped">
			<thead>
				<tr>
					<th>Marca</th>
					<th>Modelo</th>
					<th>Documento</th>
					<th>Fecha</th>
					<th>Estado</th>
					<th class="text-end">Acción</th>
				</tr>
			</thead>
			<tbody id="tabla-fichas-tecnicas">
				<?php if (!empty($fichasTecnicas)): ?>
					<?php foreach ($fichasTecnicas as $ficha): ?>
						<?php $estadoFicha = (int) $ficha['Estado']; ?>
						<tr data-id="<?php echo (int) $ficha['Id']; ?>">
							<td><?php echo htmlspecialchars($ficha['Marca']); ?></td>
							<td><?php echo htmlspecialchars($ficha['Modelo']); ?></td>
							<td>
								<?php if (!empty($ficha['Documento'])): ?>
									<a href="index.php?module=adquisiciones&action=verFichaTecnicaAjax&id=<?php echo (int) $ficha['Id']; ?>"
										target="_blank"
										class="text-decoration-none text-reset"
										title="Ver PDF">
										<i class="ti ti-file-text icon-action"></i>
									</a>
								<?php else: ?>
									<span class="text-secondary">Sin documento</span>
								<?php endif; ?>
							</td>
							<td><?php echo htmlspecialchars($formatearFecha($ficha['FechaRegistro'])); ?></td>
							<td>
								<?php if ($estadoFicha === 1): ?>
									<span class="badge bg-success-lt">Enviado</span>
								<?php else: ?>
									<span class="badge bg-warning-lt text-dark">Cargado</span>
								<?php endif; ?>
							</td>
							<td class="text-end">
								<div class="acciones-iconos">
									<?php if ($estadoFicha === 0): ?>
										<i class="ti ti-send icon-action"
											title="Marcar como enviada"
											onclick="cambiarEstadoFichaTecnica(<?php echo (int) $ficha['Id']; ?>, 1)"></i>
									<?php else: ?>
										<i class="ti ti-send-off icon-action"
											title="Marcar como pendiente"
											onclick="cambiarEstadoFichaTecnica(<?php echo (int) $ficha['Id']; ?>, 0)"></i>
									<?php endif; ?>

									<i class="ti ti-trash icon-action"
										title="Eliminar"
										onclick="eliminarFichaTecnica(<?php echo (int) $ficha['Id']; ?>)"></i>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="6" class="text-center text-secondary">No hay fichas técnicas registradas.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="bg-light rounded p-3 mt-3">
		<h5 class="fw-bold mb-2">Agregar Nueva Ficha Técnica</h5>
		<form id="form-ficha-tecnica" enctype="multipart/form-data" onsubmit="return guardarFichaTecnica(event)">
			<div class="row g-3 align-items-end">
				<div class="col-md-3">
					<label class="form-label">Marca</label>
					<input type="text" class="form-control" id="ficha_marca" name="Marca" required>
				</div>
				<div class="col-md-3">
					<label class="form-label">Modelo</label>
					<input type="text" class="form-control" id="ficha_modelo" name="Modelo" required>
				</div>
				<div class="col-md-4">
					<label class="form-label">Documento PDF</label>
					<input type="file" class="form-control" id="ficha_documento" name="DocumentoPDF" accept=".pdf" required>
				</div>
				<div class="col-md-2">
					<button type="submit" class="btn btn-primary w-100">Guardar</button>
				</div>
			</div>
		</form>
	</div>
</div>

<?php require __DIR__ . '/partials/especificacion.php'; ?>
<?php require __DIR__ . '/partials/orden.php'; ?>
<?php require __DIR__ . '/partials/verificacion.php'; ?>
<?php require __DIR__ . '/partials/conformidad.php'; ?>

<!-- ===================== BARRA DE ACCIONES ===================== -->
<div class="d-flex justify-content-end gap-2 mt-2 mb-3">
	<a href="index.php?module=adquisiciones&action=tecnologias&anio=<?php echo $anioActual; ?>" class="btn btn-secondary js-adq-link">Volver</a>
</div>

<script>
	const idTecnologia = <?php echo $idTec; ?>;
	const anioActual = <?php echo $anioActual; ?>;

	function recargarVistaTecnologia() {
		if (typeof window.recargarVistaActualAdquisiciones === 'function') {
			return window.recargarVistaActualAdquisiciones();
		}
		window.location.reload();
		return Promise.resolve();
	}

	function cambiarAnioDetalle() {
		const anio = document.getElementById('filtroAnioPedidos').value;
		const url = 'index.php?module=adquisiciones&action=tecnologia&id=' + idTecnologia + '&anio=' + anio;
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	function fileToBase64(file) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onload = () => {
				const resultado = String(reader.result || '');
				const partes = resultado.split(',');
				resolve(partes.length > 1 ? partes[1] : '');
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}

	async function enviarJson(url, payload) {
		const respuesta = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(payload)
		});

		if (!respuesta.ok) {
			const texto = await respuesta.text();
			throw new Error('Error del servidor (' + respuesta.status + '): ' + texto.substring(0, 250));
		}

		return respuesta.json();
	}

	function validarPdf(file) {
		if (!file) {
			throw new Error('Debe seleccionar un archivo PDF.');
		}

		if (!file.name.toLowerCase().endsWith('.pdf')) {
			throw new Error('Solo se permiten archivos PDF.');
		}
	}

	async function guardarFichaTecnica(e) {
		e.preventDefault();

		try {
			const marca = document.getElementById('ficha_marca').value.trim();
			const modelo = document.getElementById('ficha_modelo').value.trim();
			const file = document.getElementById('ficha_documento').files[0];

			if (!marca || !modelo) {
				throw new Error('Marca y modelo son obligatorios.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson('index.php?module=adquisiciones&action=guardarFichaTecnicaAjax', {
				IdCatalogoTecnologico: idTecnologia,
				Marca: marca,
				Modelo: modelo,
				Anio: anioActual,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo guardar la ficha técnica.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al guardar ficha tecnica', error.message || 'Error al guardar la ficha tecnica.');
		}

		return false;
	}

	async function eliminarFichaTecnica(id) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: '¿Desea eliminar esta ficha técnica?',
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=eliminarFichaTecnicaAjax', {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || 'No se pudo eliminar la ficha técnica.');
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar ficha tecnica', error.message || 'Error al eliminar la ficha tecnica.');
		}
	}

	async function cambiarEstadoFichaTecnica(id, estado) {
		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=cambiarEstadoFichaTecnicaAjax', {
				Id: id,
				Estado: estado
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo cambiar el estado.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al cambiar estado', error.message || 'Error al cambiar el estado.');
		}
	}

	async function guardarEspecificacionTecnica(e) {
		e.preventDefault();

		try {
			const codigo = document.getElementById('et_codigo').value.trim();
			const file = document.getElementById('et_documento').files[0];

			if (!codigo) {
				throw new Error('El código de especificación técnica es obligatorio.');
			}
			if (codigo.length > 50) {
				throw new Error('El código de especificación técnica no puede exceder 50 caracteres.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson('index.php?module=adquisiciones&action=guardarEspecificacionTecnicaAjax', {
				IdCatalogoTecnologico: idTecnologia,
				Codigo: codigo,
				Anio: anioActual,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo guardar la especificación técnica.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al guardar especificacion', error.message || 'Error al guardar la especificacion tecnica.');
		}

		return false;
	}

	async function actualizarEspecificacionTecnica(e) {
		e.preventDefault();

		try {
			const idEspecificacion = parseInt(document.getElementById('et_id').value, 10);
			const codigo = document.getElementById('et_codigo_upd').value.trim();
			const file = document.getElementById('et_documento_upd').files[0];

			if (!idEspecificacion || !codigo) {
				throw new Error('Faltan datos para actualizar la especificación técnica.');
			}
			if (codigo.length > 50) {
				throw new Error('El código de especificación técnica no puede exceder 50 caracteres.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson('index.php?module=adquisiciones&action=actualizarEspecificacionTecnicaAjax', {
				Id: idEspecificacion,
				Codigo: codigo,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo actualizar la especificación técnica.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al actualizar especificacion', error.message || 'Error al actualizar la especificacion tecnica.');
		}

		return false;
	}

	async function eliminarEspecificacionTecnica(id) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: '¿Desea eliminar esta especificación técnica?',
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=eliminarEspecificacionTecnicaAjax', {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || 'No se pudo eliminar la especificación técnica.');
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar especificacion', error.message || 'Error al eliminar la especificacion tecnica.');
		}
	}

	function validarNumeroOrden(numeroOrden) {
		if (!numeroOrden) {
			throw new Error('El número de orden es obligatorio.');
		}
		if (numeroOrden.length > 25) {
			throw new Error('El número de orden no puede exceder 25 caracteres.');
		}
	}

	function normalizarFechaEntrega(valor) {
		const fecha = (valor || '').trim();
		return fecha !== '' ? fecha : null;
	}

	async function guardarOrdenCompra(e) {
		e.preventDefault();

		try {
			const numeroOrden = document.getElementById('oc_numero_orden').value.trim();
			const fechaEntrega = normalizarFechaEntrega(document.getElementById('oc_fecha_entrega').value);
			const file = document.getElementById('oc_documento').files[0];

			validarNumeroOrden(numeroOrden);
			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson('index.php?module=adquisiciones&action=guardarOrdenCompraAjax', {
				IdCatalogoTecnologico: idTecnologia,
				NumeroOrden: numeroOrden,
				FechaEntrega: fechaEntrega,
				Anio: anioActual,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo guardar la orden de compra.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al guardar orden de compra', error.message || 'Error al guardar la orden de compra.');
		}

		return false;
	}

	async function actualizarOrdenCompra(e) {
		e.preventDefault();

		try {
			const idOrden = parseInt(document.getElementById('oc_id').value, 10);
			const numeroOrden = document.getElementById('oc_numero_orden_upd').value.trim();
			const fechaEntrega = normalizarFechaEntrega(document.getElementById('oc_fecha_entrega_upd').value);
			const file = document.getElementById('oc_documento_upd').files[0];

			if (!idOrden) {
				throw new Error('Faltan datos para actualizar la orden de compra.');
			}
			validarNumeroOrden(numeroOrden);
			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson('index.php?module=adquisiciones&action=actualizarOrdenCompraAjax', {
				Id: idOrden,
				NumeroOrden: numeroOrden,
				FechaEntrega: fechaEntrega,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo actualizar la orden de compra.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al actualizar orden de compra', error.message || 'Error al actualizar la orden de compra.');
		}

		return false;
	}

	async function eliminarOrdenCompra(id) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: '¿Desea eliminar esta orden de compra?',
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=eliminarOrdenCompraAjax', {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || 'No se pudo eliminar la orden de compra.');
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar orden de compra', error.message || 'Error al eliminar la orden de compra.');
		}
	}

	async function guardarDocumentoConObservacion(e, options) {
		e.preventDefault();

		try {
			const observacion = document.getElementById(options.observacionId).value.trim();
			const file = document.getElementById(options.documentoId).files[0];

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson(options.url, {
				IdCatalogoTecnologico: idTecnologia,
				Observacion: observacion,
				Anio: anioActual,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || options.errorGuardar);
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al guardar documento', error.message || options.errorGuardar);
		}

		return false;
	}

	async function actualizarDocumentoConObservacion(e, options) {
		e.preventDefault();

		try {
			const idDocumento = parseInt(document.getElementById(options.idId).value, 10);
			const observacion = document.getElementById(options.observacionId).value.trim();
			const file = document.getElementById(options.documentoId).files[0];

			if (!idDocumento) {
				throw new Error('Faltan datos para actualizar el documento.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson(options.url, {
				Id: idDocumento,
				Observacion: observacion,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || options.errorActualizar);
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al actualizar documento', error.message || options.errorActualizar);
		}

		return false;
	}

	async function eliminarDocumentoSimple(id, options) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: options.confirmacion,
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson(options.url, {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || options.errorEliminar);
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar documento', error.message || options.errorEliminar);
		}
	}

	function guardarVerificacionTecnica(e) {
		return guardarDocumentoConObservacion(e, {
			observacionId: 'vt_observacion',
			documentoId: 'vt_documento',
			url: 'index.php?module=adquisiciones&action=guardarVerificacionTecnicaAjax',
			errorGuardar: 'No se pudo guardar la verificación técnica.'
		});
	}

	function actualizarVerificacionTecnica(e) {
		return actualizarDocumentoConObservacion(e, {
			idId: 'vt_id',
			observacionId: 'vt_observacion_upd',
			documentoId: 'vt_documento_upd',
			url: 'index.php?module=adquisiciones&action=actualizarVerificacionTecnicaAjax',
			errorActualizar: 'No se pudo actualizar la verificación técnica.'
		});
	}

	function eliminarVerificacionTecnica(id) {
		return eliminarDocumentoSimple(id, {
			url: 'index.php?module=adquisiciones&action=eliminarVerificacionTecnicaAjax',
			confirmacion: '¿Desea eliminar esta verificación técnica?',
			errorEliminar: 'No se pudo eliminar la verificación técnica.'
		});
	}

	function guardarConformidad(e) {
		return guardarDocumentoConObservacion(e, {
			observacionId: 'cf_observacion',
			documentoId: 'cf_documento',
			url: 'index.php?module=adquisiciones&action=guardarConformidadAjax',
			errorGuardar: 'No se pudo guardar la conformidad.'
		});
	}

	function actualizarConformidad(e) {
		return actualizarDocumentoConObservacion(e, {
			idId: 'cf_id',
			observacionId: 'cf_observacion_upd',
			documentoId: 'cf_documento_upd',
			url: 'index.php?module=adquisiciones&action=actualizarConformidadAjax',
			errorActualizar: 'No se pudo actualizar la conformidad.'
		});
	}

	function eliminarConformidad(id) {
		return eliminarDocumentoSimple(id, {
			url: 'index.php?module=adquisiciones&action=eliminarConformidadAjax',
			confirmacion: '¿Desea eliminar esta conformidad?',
			errorEliminar: 'No se pudo eliminar la conformidad.'
		});
	}
</script>