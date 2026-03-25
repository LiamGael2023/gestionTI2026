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
						<div class="acciones-iconos">
							<?php if (!empty($verificacionTecnica['Documento'])): ?>
								<a href="index.php?module=adquisiciones&action=verVerificacionTecnicaAjax&id=<?php echo (int) $verificacionTecnica['Id']; ?>"
									onclick="return abrirPdfEnModal(this.href);"
									class="text-decoration-none text-reset"
									title="Ver PDF">
									<i class="ti ti-file-text icon-action"></i>
								</a>
							<?php endif; ?>
							<i class="ti ti-trash icon-action"
								title="Eliminar"
								onclick="eliminarVerificacionTecnica(<?php echo (int) $verificacionTecnica['Id']; ?>)"></i>
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