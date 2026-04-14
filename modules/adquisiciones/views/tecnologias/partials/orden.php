<div class="card card-body mb-3">
	<h4 class="fw-bold mb-3">Orden de Compra</h4>

	<?php if ($tieneOrdenCompra): ?>
		<div class="mt-3">
			<div class="table-responsive">
				<table class="table table-vcenter card-table table-striped">
					<thead>
						<tr>
							<th>Fecha y Hora</th>
							<th>Número de Orden</th>
							<th>Fecha de Entrega</th>
							<th class="text-end">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php echo htmlspecialchars($formatearFecha($ordenCompra['FechaRegistro'])); ?></td>
							<td><?php echo htmlspecialchars(str_replace('_', ' ', $ordenCompra['NumeroOrden'] ?? '')); ?></td>
							<td><?php echo htmlspecialchars($formatearFecha($ordenCompra['FechaEntrega'] ?? '')); ?></td>
							<td class="text-end align-middle">
								<div class="btn-group" role="group">
									<?php if (!empty($ordenCompra['Documento'])): ?>
										<!-- Ver PDF -->
										<button type="button"
											class="btn btn-icon btn-lg"
											title="Ver PDF"
											onclick="abrirPdfEnModal('index.php?module=adquisiciones&action=verOrdenCompraAjax&id=<?= (int)$ordenCompra['Id'] ?>')">
											<i class="ti ti-file-text fs-2"></i>
										</button>
									<?php endif; ?>
									<!-- Eliminar -->
									<button type="button"
										class="btn btn-icon btn-lg text-danger"
										title="Eliminar"
										onclick="eliminarOrdenCompra(<?= (int)$ordenCompra['Id'] ?>)">
										<i class="ti ti-trash fs-2"></i>
									</button>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	<?php else: ?>
		<p class="text-secondary mb-0">No hay orden de compra registrada para este año.</p>
		<?php if (!$puedeRegistrarOrdenCompra): ?>
			<div class="alert alert-warning mt-3 mb-0" role="alert">
				Debe registrar primero la especificación técnica antes de cargar la orden de compra.
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($tieneOrdenCompra || $puedeRegistrarOrdenCompra): ?>
		<div class="d-flex justify-content-end mt-3">
			<?php if ($tieneOrdenCompra): ?>
				<button type="button"
					class="btn btn-primary"
					data-bs-toggle="modal"
					data-bs-target="#modalOrdenCompra"
					data-toggle="modal"
					data-target="#modalOrdenCompra"
					onclick="return abrirModalOrdenCompra('editar', <?php echo (int) $ordenCompra['Id']; ?>, '<?php echo htmlspecialchars((string) ($ordenCompra['FechaEntrega'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>');">
					Actualizar
				</button>
			<?php else: ?>
				<button type="button"
					class="btn btn-primary"
					data-bs-toggle="modal"
					data-bs-target="#modalOrdenCompra"
					data-toggle="modal"
					data-target="#modalOrdenCompra"
					onclick="return abrirModalOrdenCompra('crear');">
					Agregar
				</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<div class="modal modal-blur fade" id="modalOrdenCompra" tabindex="-1" aria-labelledby="modalOrdenCompraLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalOrdenCompraLabel">Agregar Orden de Compra</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" onclick="return cerrarModalOrdenCompra();"></button>
			</div>
			<div class="modal-body">
				<form id="form-orden-compra-modal" enctype="multipart/form-data" onsubmit="return submitOrdenCompraModal(event)">
					<input type="hidden" id="oc_modal_modo" value="crear">
					<input type="hidden" id="oc_modal_id" value="0">

					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label">Número de Orden</label>
							<input type="text" class="form-control" id="oc_numero_orden_modal" name="NumeroOrden" maxlength="25" readonly>
						</div>
						<div class="col-md-6">
							<label class="form-label">Fecha de Entrega</label>
							<input type="date" class="form-control" id="oc_fecha_entrega_modal" name="FechaEntrega">
						</div>
						<div class="col-md-12">
							<label class="form-label" id="oc_documento_label">Documento PDF</label>
							<input type="file" class="form-control" id="oc_documento_modal" name="DocumentoPDF" accept=".pdf" required>
							<small class="form-text text-muted" id="oc_documento_hint"></small>
						</div>
						<div class="col-12 d-flex justify-content-end gap-2 mt-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" onclick="return cerrarModalOrdenCompra();">Cancelar</button>
							<button type="submit" class="btn btn-primary" id="oc_btn_submit">Guardar</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
	function abrirModalOrdenCompra(modo, idOrdenCompra, fechaEntrega) {
		const modalElement = document.getElementById('modalOrdenCompra');
		if (!modalElement) {
			return false;
		}

		const modoFormulario = modo === 'editar' ? 'editar' : 'crear';
		const inputModo = document.getElementById('oc_modal_modo');
		const inputId = document.getElementById('oc_modal_id');
		const inputNumero = document.getElementById('oc_numero_orden_modal');
		const inputFecha = document.getElementById('oc_fecha_entrega_modal');
		const inputDocumento = document.getElementById('oc_documento_modal');
		const labelDocumento = document.getElementById('oc_documento_label');
		const hintDocumento = document.getElementById('oc_documento_hint');
		const titulo = document.getElementById('modalOrdenCompraLabel');
		const botonSubmit = document.getElementById('oc_btn_submit');

		if (inputModo) {
			inputModo.value = modoFormulario;
		}
		if (inputId) {
			inputId.value = modoFormulario === 'editar' ? String(parseInt(idOrdenCompra, 10) || 0) : '0';
		}
		if (inputNumero) {
			inputNumero.value = generarNumeroOrdenCompra(anioActual).replaceAll('_', ' ');
		}
		if (inputFecha) {
			inputFecha.value = modoFormulario === 'editar' ? String(fechaEntrega || '') : '';
		}
		if (inputDocumento) {
			inputDocumento.required = modoFormulario !== 'editar';
		}

		if (titulo) {
			titulo.textContent = modoFormulario === 'editar' ? 'Actualizar Orden de Compra' : 'Agregar Orden de Compra';
		}
		if (labelDocumento) {
			labelDocumento.textContent = modoFormulario === 'editar' ? 'Nuevo Documento PDF (opcional)' : 'Documento PDF';
		}
		if (hintDocumento) {
			hintDocumento.textContent = modoFormulario === 'editar' ?
				'Si no selecciona un archivo, se conservará el PDF actual.' :
				'';
		}
		if (botonSubmit) {
			botonSubmit.textContent = modoFormulario === 'editar' ? 'Actualizar' : 'Guardar';
		}

		mostrarModal(modalElement);
		return false;
	}

	function cerrarModalOrdenCompra() {
		const modalElement = document.getElementById('modalOrdenCompra');
		ocultarModal(modalElement);
		return false;
	}

	function limpiarFormularioOrdenCompra() {
		const form = document.getElementById('form-orden-compra-modal');
		if (form) {
			form.reset();
		}

		const inputModo = document.getElementById('oc_modal_modo');
		const inputId = document.getElementById('oc_modal_id');
		const inputNumero = document.getElementById('oc_numero_orden_modal');
		const inputDocumento = document.getElementById('oc_documento_modal');
		const labelDocumento = document.getElementById('oc_documento_label');
		const hintDocumento = document.getElementById('oc_documento_hint');
		const titulo = document.getElementById('modalOrdenCompraLabel');
		const botonSubmit = document.getElementById('oc_btn_submit');

		if (inputModo) {
			inputModo.value = 'crear';
		}
		if (inputId) {
			inputId.value = '0';
		}
		if (inputNumero) {
			inputNumero.value = generarNumeroOrdenCompra(anioActual).replaceAll('_', ' ');
		}
		if (inputDocumento) {
			inputDocumento.required = true;
		}
		if (titulo) {
			titulo.textContent = 'Agregar Orden de Compra';
		}
		if (labelDocumento) {
			labelDocumento.textContent = 'Documento PDF';
		}
		if (hintDocumento) {
			hintDocumento.textContent = '';
		}
		if (botonSubmit) {
			botonSubmit.textContent = 'Guardar';
		}
	}

	function inicializarModalOrdenCompra() {
		inicializarModalConLimpieza({
			modalId: 'modalOrdenCompra',
			datasetKey: 'adqOcInit',
			limpiarFn: limpiarFormularioOrdenCompra
		});
	}

	function normalizarFechaEntrega(valor) {
		const fecha = (valor || '').trim();
		return fecha !== '' ? fecha : null;
	}

	async function submitOrdenCompraModal(e) {
		e.preventDefault();

		try {
			const modo = document.getElementById('oc_modal_modo').value;
			const idOrden = parseInt(document.getElementById('oc_modal_id').value, 10);
			const numeroOrden = generarNumeroOrdenCompra(anioActual);
			const fechaEntrega = normalizarFechaEntrega(document.getElementById('oc_fecha_entrega_modal').value);
			const file = document.getElementById('oc_documento_modal').files[0];

			if (!numeroOrden) {
				throw new Error('No se pudo generar el número de orden.');
			}
			if (numeroOrden.length > 25) {
				throw new Error('El número de orden generado excede 25 caracteres.');
			}

			let data = null;
			let documentoBase64 = null;

			if (modo === 'editar') {
				if (!idOrden) {
					throw new Error('Faltan datos para actualizar la orden de compra.');
				}

				if (file) {
					validarPdf(file);
					documentoBase64 = await fileToBase64(file);
				}

				data = await enviarJson('index.php?module=adquisiciones&action=actualizarOrdenCompraAjax', {
					Id: idOrden,
					NumeroOrden: numeroOrden,
					FechaEntrega: fechaEntrega,
					Documento: documentoBase64
				});
			} else {
				validarPdf(file);
				documentoBase64 = await fileToBase64(file);
				data = await enviarJson('index.php?module=adquisiciones&action=guardarOrdenCompraAjax', {
					IdCatalogoTecnologico: idTecnologia,
					NumeroOrden: numeroOrden,
					FechaEntrega: fechaEntrega,
					Anio: anioActual,
					Documento: documentoBase64
				});
			}

			if (!data.ok) {
				throw new Error(data.error || (modo === 'editar' ?
					'No se pudo actualizar la orden de compra.' :
					'No se pudo guardar la orden de compra.'));
			}

			cerrarModalOrdenCompra();
			await recargarVistaTecnologia();
		} catch (error) {
			const modo = (document.getElementById('oc_modal_modo') || {}).value || 'crear';
			window.adqNotifySafe(
				'danger',
				modo === 'editar' ? 'Error al actualizar orden de compra' : 'Error al guardar orden de compra',
				error.message || (modo === 'editar' ?
					'Error al actualizar la orden de compra.' :
					'Error al guardar la orden de compra.')
			);
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
</script>