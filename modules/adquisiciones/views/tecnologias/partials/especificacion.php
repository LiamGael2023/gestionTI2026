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
							<td class="text-end">
								<div class="acciones-iconos">
									<?php if (!empty($especificacionTecnica['Documento'])): ?>
										<a href="index.php?module=adquisiciones&action=verEspecificacionTecnicaAjax&id=<?php echo (int) $especificacionTecnica['Id']; ?>"
											onclick="return abrirPdfEnModal(this.href);"
											class="text-decoration-none text-reset"
											title="Ver PDF">
											<i class="ti ti-file-text icon-action"></i>
										</a>
									<?php endif; ?>
									<i class="ti ti-trash icon-action"
										title="Eliminar"
										onclick="eliminarEspecificacionTecnica(<?php echo (int) $especificacionTecnica['Id']; ?>)"></i>
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
					Actualizar Especificación
				</button>
			<?php else: ?>
				<button type="button"
					class="btn btn-primary"
					data-bs-toggle="modal"
					data-bs-target="#modalEspecificacionTecnica"
					data-toggle="modal"
					data-target="#modalEspecificacionTecnica"
					onclick="return abrirModalEspecificacionTecnica('crear');">
					Agregar Especificación Técnica
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
