<div class="card card-body mb-3">
	<h4 class="fw-bold mb-3">Especificación Técnica</h4>

	<?php if ($tieneEspecificacion): ?>
		<div class="mt-3">
			<div class="table-responsive">
				<table class="table table-vcenter card-table table-striped">
					<thead>
						<tr>
							<th>Fecha y Hora</th>
							<th>Nombre</th>
							<th class="text-end">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php echo htmlspecialchars($formatearFecha($especificacionTecnica['FechaRegistro'])); ?></td>
							<td><?php echo htmlspecialchars(str_replace('_', ' ', (string) ($especificacionTecnica['Codigo'] ?? ''))); ?></td>
							<td class="text-end align-middle">
								<div class="btn-group" role="group">
									<?php if (!empty($especificacionTecnica['Documento'])): ?>
										<!-- Ver PDF -->
										<button type="button"
											class="btn btn-icon btn-lg"
											title="Ver PDF"
											onclick="abrirPdfEnModal('index.php?module=adquisiciones&action=verEspecificacionTecnicaAjax&id=<?= (int)$especificacionTecnica['Id'] ?>')">
											<i class="ti ti-file-text fs-2"></i>
										</button>
									<?php endif; ?>
									<!-- Eliminar -->
									<button type="button"
										class="btn btn-icon btn-lg text-danger"
										title="Eliminar"
										onclick="eliminarEspecificacionTecnica(<?= (int)$especificacionTecnica['Id'] ?>)">
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
		<p class="text-secondary mb-0">No hay especificación técnica registrada para este año.</p>

		<?php if (!$puedeRegistrarEspecificacion): ?>
			<div class="alert alert-warning mt-3 mb-0" role="alert">
				Debe registrar al menos <?php echo $minimoFichasRequeridas; ?> fichas técnicas antes de cargar la especificación técnica.
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($tieneEspecificacion || $puedeRegistrarEspecificacion): ?>
		<div class="d-flex justify-content-end mt-3">
			<?php if ($tieneEspecificacion): ?>
				<button type="button"
					class="btn btn-primary"
					data-bs-toggle="modal"
					data-bs-target="#modalEspecificacionTecnica"
					data-toggle="modal"
					data-target="#modalEspecificacionTecnica"
					onclick="return abrirModalEspecificacionTecnica('editar', <?php echo (int) $especificacionTecnica['Id']; ?>);">
					Actualizar
				</button>
			<?php else: ?>
				<button type="button"
					class="btn btn-primary"
					data-bs-toggle="modal"
					data-bs-target="#modalEspecificacionTecnica"
					data-toggle="modal"
					data-target="#modalEspecificacionTecnica"
					onclick="return abrirModalEspecificacionTecnica('crear');">
					Agregar
				</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<div class="modal modal-blur fade" id="modalEspecificacionTecnica" tabindex="-1" aria-labelledby="modalEspecificacionTecnicaLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalEspecificacionTecnicaLabel">Agregar Especificación Técnica</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" onclick="return cerrarModalEspecificacionTecnica();"></button>
			</div>
			<div class="modal-body">
				<form id="form-especificacion-modal" enctype="multipart/form-data" onsubmit="return submitEspecificacionTecnica(event)">
					<input type="hidden" id="et_modal_modo" value="crear">
					<input type="hidden" id="et_modal_id" value="0">

					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label">Código</label>
							<input type="hidden" id="et_codigo_modal" name="Codigo" required>
							<input type="text" class="form-control" id="et_codigo_modal_visual" readonly>
						</div>
						<div class="col-md-6">
							<label class="form-label" id="et_documento_label">Documento PDF</label>
							<input type="file" class="form-control" id="et_documento_modal" name="DocumentoPDF" accept=".pdf" required>
						</div>
						<div class="col-12 d-flex justify-content-end gap-2 mt-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" onclick="return cerrarModalEspecificacionTecnica();">Cancelar</button>
							<button type="submit" class="btn btn-primary" id="et_btn_submit">Guardar</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
	function abrirModalEspecificacionTecnica(modo, idEspecificacion) {
		const modalElement = document.getElementById('modalEspecificacionTecnica');
		if (!modalElement) {
			return false;
		}

		const modoFormulario = modo === 'editar' ? 'editar' : 'crear';
		const inputModo = document.getElementById('et_modal_modo');
		const inputId = document.getElementById('et_modal_id');
		const inputCodigo = document.getElementById('et_codigo_modal');
		const inputCodigoVisual = document.getElementById('et_codigo_modal_visual');
		const labelDocumento = document.getElementById('et_documento_label');
		const titulo = document.getElementById('modalEspecificacionTecnicaLabel');
		const botonSubmit = document.getElementById('et_btn_submit');

		if (inputModo) {
			inputModo.value = modoFormulario;
		}
		if (inputId) {
			inputId.value = modoFormulario === 'editar' ? String(parseInt(idEspecificacion, 10) || 0) : '0';
		}
		const codigoGenerado = generarCodigoEspecificacion();
		if (inputCodigo) {
			inputCodigo.value = codigoGenerado;
		}
		if (inputCodigoVisual) {
			inputCodigoVisual.value = formatearCodigoEspecificacionVisual(codigoGenerado);
		}

		if (titulo) {
			titulo.textContent = modoFormulario === 'editar' ? 'Actualizar Especificación Técnica' : 'Agregar Especificación Técnica';
		}
		if (labelDocumento) {
			labelDocumento.textContent = modoFormulario === 'editar' ? 'Nuevo Documento PDF (opcional)' : 'Documento PDF';
		}
		if (botonSubmit) {
			botonSubmit.textContent = modoFormulario === 'editar' ? 'Actualizar' : 'Guardar';
		}

		mostrarModal(modalElement);
		return false;
	}

	function cerrarModalEspecificacionTecnica() {
		const modalElement = document.getElementById('modalEspecificacionTecnica');
		ocultarModal(modalElement);
		return false;
	}

	function limpiarFormularioEspecificacionTecnica() {
		const form = document.getElementById('form-especificacion-modal');
		if (form) {
			form.reset();
		}

		const inputModo = document.getElementById('et_modal_modo');
		const inputId = document.getElementById('et_modal_id');
		const inputCodigo = document.getElementById('et_codigo_modal');
		const inputCodigoVisual = document.getElementById('et_codigo_modal_visual');
		const titulo = document.getElementById('modalEspecificacionTecnicaLabel');
		const labelDocumento = document.getElementById('et_documento_label');
		const botonSubmit = document.getElementById('et_btn_submit');

		if (inputModo) {
			inputModo.value = 'crear';
		}
		if (inputId) {
			inputId.value = '0';
		}
		const codigoGenerado = generarCodigoEspecificacion();
		if (inputCodigo) {
			inputCodigo.value = codigoGenerado;
		}
		if (inputCodigoVisual) {
			inputCodigoVisual.value = formatearCodigoEspecificacionVisual(codigoGenerado);
		}
		if (titulo) {
			titulo.textContent = 'Agregar Especificación Técnica';
		}
		if (labelDocumento) {
			labelDocumento.textContent = 'Documento PDF';
		}
		if (botonSubmit) {
			botonSubmit.textContent = 'Guardar';
		}
	}

	function inicializarModalEspecificacionTecnica() {
		inicializarModalConLimpieza({
			modalId: 'modalEspecificacionTecnica',
			datasetKey: 'adqEtInit',
			limpiarFn: limpiarFormularioEspecificacionTecnica
		});
	}

	async function submitEspecificacionTecnica(e) {
		e.preventDefault();

		try {
			const modo = document.getElementById('et_modal_modo').value;
			const idEspecificacion = parseInt(document.getElementById('et_modal_id').value, 10);
			const codigo = document.getElementById('et_codigo_modal').value.trim();
			const file = document.getElementById('et_documento_modal').files[0];

			if (!codigo) {
				throw new Error('El código de especificación técnica es obligatorio.');
			}
			if (codigo.length > 50) {
				throw new Error('El código de especificación técnica no puede exceder 50 caracteres.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			let data = null;

			if (modo === 'editar') {
				if (!idEspecificacion) {
					throw new Error('Faltan datos para actualizar la especificación técnica.');
				}

				data = await enviarJson('index.php?module=adquisiciones&action=actualizarEspecificacionTecnicaAjax', {
					Id: idEspecificacion,
					Codigo: codigo,
					Documento: documentoBase64
				});
			} else {
				data = await enviarJson('index.php?module=adquisiciones&action=guardarEspecificacionTecnicaAjax', {
					IdCatalogoTecnologico: idTecnologia,
					Codigo: codigo,
					Anio: anioActual,
					Documento: documentoBase64
				});
			}

			if (!data.ok) {
				throw new Error(data.error || (modo === 'editar' ?
					'No se pudo actualizar la especificación técnica.' :
					'No se pudo guardar la especificación técnica.'));
			}

			cerrarModalEspecificacionTecnica();
			await recargarVistaTecnologia();
		} catch (error) {
			const modo = (document.getElementById('et_modal_modo') || {}).value || 'crear';
			window.adqNotifySafe(
				'danger',
				modo === 'editar' ? 'Error al actualizar especificacion' : 'Error al guardar especificacion',
				error.message || (modo === 'editar' ?
					'Error al actualizar la especificacion tecnica.' :
					'Error al guardar la especificacion tecnica.')
			);
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
</script>