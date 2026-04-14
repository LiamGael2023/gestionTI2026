<div class="card card-body mb-3">
	<h4 class="fw-bold mb-3">Verificación Técnica / Coformidad</h4>

	<?php if ($tieneVerificacion): ?>
		<div class="mt-3">
			<div class="border rounded p-3">
				<div class="row mb-2">
					<div class="col-auto">
						<strong>Fecha y Hora:</strong> <?php echo htmlspecialchars($formatearFecha($verificacionTecnica['FechaRegistro'])); ?>
					</div>
					<div class="col text-end">
						<div class="btn-group justify-content-end" role="group">
							<?php if (!empty($verificacionTecnica['Documento'])): ?>
								<!-- Ver PDF -->
								<button type="button"
									class="btn btn-icon btn-lg"
									title="Ver PDF"
									onclick="abrirPdfEnModal('index.php?module=adquisiciones&action=verVerificacionTecnicaAjax&id=<?= (int)$verificacionTecnica['Id'] ?>')">
									<i class="ti ti-file-text fs-2"></i>
								</button>
							<?php endif; ?>
							<!-- Eliminar -->
							<button type="button"
								class="btn btn-icon btn-lg text-danger"
								title="Eliminar"
								onclick="eliminarVerificacionTecnica(<?= (int)$verificacionTecnica['Id'] ?>)">
								<i class="ti ti-trash fs-2"></i>
							</button>
						</div>
					</div>
				</div>
				<div class="mt-2">
					<strong>Observación:</strong><br>
					<?php echo htmlspecialchars($verificacionTecnica['Observacion'] ?? ''); ?>
				</div>
			</div>
		</div>

		<div class="d-flex justify-content-end mt-3">
			<button type="button"
				class="btn btn-primary"
				data-bs-toggle="modal"
				data-bs-target="#modalVerificacionTecnica"
				data-toggle="modal"
				data-target="#modalVerificacionTecnica"
				onclick="return abrirModalVerificacionTecnica('editar', <?php echo (int) $verificacionTecnica['Id']; ?>, <?php echo htmlspecialchars(json_encode((string) ($verificacionTecnica['Observacion'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>);">
				Actualizar
			</button>
		</div>
	<?php else: ?>
		<p class="text-secondary mb-0">No hay verificación técnica registrada para este año.</p>

		<?php if ($puedeRegistrarVerificacion): ?>
			<div class="d-flex justify-content-end mt-3">
				<button type="button"
					class="btn btn-primary"
					data-bs-toggle="modal"
					data-bs-target="#modalVerificacionTecnica"
					data-toggle="modal"
					data-target="#modalVerificacionTecnica"
					onclick="return abrirModalVerificacionTecnica('crear', 0, '');">
					Agregar
				</button>
			</div>
		<?php else: ?>
			<div class="alert alert-warning mt-3 mb-0" role="alert">
				Debe registrar primero la orden de compra antes de cargar la verificación técnica.
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($puedeRegistrarVerificacion || $tieneVerificacion): ?>
		<div class="modal modal-blur fade" id="modalVerificacionTecnica" tabindex="-1" aria-labelledby="modalVerificacionTecnicaLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="modalVerificacionTecnicaLabel">Agregar Verificación Técnica</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" onclick="return cerrarModalVerificacionTecnica();"></button>
					</div>
					<div class="modal-body">
						<form id="form-verificacion-modal" enctype="multipart/form-data" onsubmit="return submitVerificacionTecnicaModal(event)">
							<input type="hidden" id="vt_modal_modo" value="crear">
							<input type="hidden" id="vt_modal_id" value="0">
							<div class="row g-3">
								<div class="col-12">
									<label class="form-label">Observación</label>
									<textarea class="form-control" id="vt_modal_observacion" name="Observacion" maxlength="500" rows="2" style="resize:none; overflow:hidden;"></textarea>
								</div>
								<div class="col-12">
									<label class="form-label" id="vt_modal_documento_label">Documento PDF</label>
									<input type="file" class="form-control" id="vt_modal_documento" name="DocumentoPDF" accept=".pdf" required>
									<small class="text-secondary" id="vt_modal_documento_hint"></small>
								</div>
								<div class="col-12 d-flex justify-content-end gap-2 mt-2">
									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" onclick="return cerrarModalVerificacionTecnica();">Cancelar</button>
									<button type="submit" class="btn btn-primary" id="vt_modal_btn_submit">Guardar</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>

<script>
	function autoAjustarAlturaTextarea(textarea) {
		if (!textarea) {
			return;
		}

		textarea.style.height = 'auto';
		textarea.style.height = textarea.scrollHeight + 'px';
	}

	function ajustarTextareaObservacionVerificacion() {
		const inputObservacion = document.getElementById('vt_modal_observacion');
		autoAjustarAlturaTextarea(inputObservacion);
	}

	function abrirModalVerificacionTecnica(modo, idVerificacion, observacion) {
		const modalElement = document.getElementById('modalVerificacionTecnica');
		if (!modalElement) {
			return false;
		}

		const modoFormulario = modo === 'editar' ? 'editar' : 'crear';
		const inputModo = document.getElementById('vt_modal_modo');
		const inputId = document.getElementById('vt_modal_id');
		const inputObservacion = document.getElementById('vt_modal_observacion');
		const inputDocumento = document.getElementById('vt_modal_documento');
		const titulo = document.getElementById('modalVerificacionTecnicaLabel');
		const labelDocumento = document.getElementById('vt_modal_documento_label');
		const hintDocumento = document.getElementById('vt_modal_documento_hint');
		const botonSubmit = document.getElementById('vt_modal_btn_submit');

		if (inputModo) {
			inputModo.value = modoFormulario;
		}
		if (inputId) {
			inputId.value = modoFormulario === 'editar' ? String(parseInt(idVerificacion, 10) || 0) : '0';
		}
		if (inputObservacion) {
			inputObservacion.value = String(observacion || '');
			ajustarTextareaObservacionVerificacion();
		}
		if (inputDocumento) {
			inputDocumento.required = modoFormulario !== 'editar';
		}

		if (titulo) {
			titulo.textContent = modoFormulario === 'editar' ? 'Actualizar Verificación Técnica' : 'Agregar Verificación Técnica';
		}
		if (labelDocumento) {
			labelDocumento.textContent = modoFormulario === 'editar' ? 'Nuevo Documento PDF' : 'Documento PDF';
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
		setTimeout(ajustarTextareaObservacionVerificacion, 50);
		return false;
	}

	function cerrarModalVerificacionTecnica() {
		const modalElement = document.getElementById('modalVerificacionTecnica');
		ocultarModal(modalElement);
		return false;
	}

	function limpiarFormularioVerificacionTecnica() {
		const form = document.getElementById('form-verificacion-modal');
		if (form) {
			form.reset();
		}

		const inputModo = document.getElementById('vt_modal_modo');
		const inputId = document.getElementById('vt_modal_id');
		const inputObservacion = document.getElementById('vt_modal_observacion');
		const inputDocumento = document.getElementById('vt_modal_documento');
		const titulo = document.getElementById('modalVerificacionTecnicaLabel');
		const labelDocumento = document.getElementById('vt_modal_documento_label');
		const hintDocumento = document.getElementById('vt_modal_documento_hint');
		const botonSubmit = document.getElementById('vt_modal_btn_submit');

		if (inputModo) {
			inputModo.value = 'crear';
		}
		if (inputId) {
			inputId.value = '0';
		}
		if (inputObservacion) {
			ajustarTextareaObservacionVerificacion();
		}
		if (inputDocumento) {
			inputDocumento.required = true;
		}
		if (titulo) {
			titulo.textContent = 'Agregar Verificación Técnica';
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

	function inicializarModalVerificacionTecnica() {
		inicializarModalConLimpieza({
			modalId: 'modalVerificacionTecnica',
			datasetKey: 'adqVtInit',
			limpiarFn: limpiarFormularioVerificacionTecnica,
			extraInitFn: function(modalElement) {
				const inputObservacion = document.getElementById('vt_modal_observacion');
				if (inputObservacion) {
					inputObservacion.addEventListener('input', function() {
						ajustarTextareaObservacionVerificacion();
					});
				}

				modalElement.addEventListener('shown.bs.modal', ajustarTextareaObservacionVerificacion);

				if (typeof $ !== 'undefined' && $.fn.modal) {
					$(modalElement).on('shown.bs.modal', ajustarTextareaObservacionVerificacion);
				}
			}
		});
	}

	async function submitVerificacionTecnicaModal(e) {
		e.preventDefault();

		try {
			const modo = document.getElementById('vt_modal_modo').value;
			const idVerificacion = parseInt(document.getElementById('vt_modal_id').value, 10);
			const observacion = document.getElementById('vt_modal_observacion').value.trim();
			const file = document.getElementById('vt_modal_documento').files[0];
			let documentoBase64 = '';

			if (modo === 'editar') {
				if (file) {
					validarPdf(file);
					documentoBase64 = await fileToBase64(file);
				}
			} else {
				validarPdf(file);
				documentoBase64 = await fileToBase64(file);
			}
			let data = null;

			if (modo === 'editar') {
				if (!idVerificacion) {
					throw new Error('Faltan datos para actualizar la verificación técnica.');
				}

				data = await enviarJson('index.php?module=adquisiciones&action=actualizarVerificacionTecnicaAjax', {
					Id: idVerificacion,
					Observacion: observacion,
					Documento: documentoBase64
				});
			} else {
				data = await enviarJson('index.php?module=adquisiciones&action=guardarVerificacionTecnicaAjax', {
					IdCatalogoTecnologico: idTecnologia,
					Observacion: observacion,
					Anio: anioActual,
					Documento: documentoBase64
				});
			}

			if (!data.ok) {
				throw new Error(data.error || (modo === 'editar' ?
					'No se pudo actualizar la verificación técnica.' :
					'No se pudo guardar la verificación técnica.'));
			}

			cerrarModalVerificacionTecnica();
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error en verificacion tecnica', error.message || 'No se pudo procesar la verificación técnica.');
		}

		return false;
	}

	function eliminarVerificacionTecnica(id) {
		return eliminarDocumentoSimple(id, {
			url: 'index.php?module=adquisiciones&action=eliminarVerificacionTecnicaAjax',
			confirmacion: '¿Desea eliminar esta verificación técnica?',
			errorEliminar: 'No se pudo eliminar la verificación técnica.'
		});
	}
</script>