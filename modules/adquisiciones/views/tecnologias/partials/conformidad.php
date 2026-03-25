<div class="card card-body mb-3">
	<h4 class="fw-bold mb-3">Conformidad</h4>

	<?php if ($tieneConformidad): ?>
		<div class="mt-3">
			<div class="border rounded p-3">
				<div class="row mb-2">
					<div class="col-auto">
						<strong>Fecha y Hora:</strong> <?php echo htmlspecialchars($formatearFecha($conformidad['FechaRegistro'])); ?>
					</div>
					<div class="col text-end">
						<div class="acciones-iconos">
							<?php if (!empty($conformidad['Documento'])): ?>
								<a href="index.php?module=adquisiciones&action=verConformidadAjax&id=<?php echo (int) $conformidad['Id']; ?>"
									onclick="return abrirPdfEnModal(this.href);"
									class="text-decoration-none text-reset"
									title="Ver PDF">
									<i class="ti ti-file-text icon-action"></i>
								</a>
							<?php endif; ?>
							<i class="ti ti-trash icon-action"
								title="Eliminar"
								onclick="eliminarConformidad(<?php echo (int) $conformidad['Id']; ?>)"></i>
						</div>
					</div>
				</div>
				<div class="mt-2">
					<strong>Observación:</strong><br>
					<?php echo htmlspecialchars($conformidad['Observacion'] ?? ''); ?>
				</div>
			</div>
		</div>

		<div class="bg-light rounded p-3 mt-3">
			<h5 class="fw-bold mb-2">Reemplazar Conformidad</h5>
			<form id="form-conformidad-actualizar" enctype="multipart/form-data" onsubmit="return actualizarConformidad(event)">
				<input type="hidden" id="cf_id" value="<?php echo (int) $conformidad['Id']; ?>">
				<div class="row g-3 align-items-end">
					<div class="col-md-6">
						<label class="form-label">Observación</label>
						<input type="text" class="form-control" id="cf_observacion_upd" name="Observacion" maxlength="500">
					</div>
					<div class="col-md-4">
						<label class="form-label">Nuevo Documento PDF</label>
						<input type="file" class="form-control" id="cf_documento_upd" name="DocumentoPDF" accept=".pdf" required>
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-primary w-100">Actualizar</button>
					</div>
				</div>
			</form>
		</div>
	<?php else: ?>
		<p class="text-secondary mb-0">No hay conformidad registrada para este año.</p>

		<?php if ($puedeRegistrarConformidad): ?>
			<div class="bg-light rounded p-3 mt-3">
				<h5 class="fw-bold mb-2">Agregar Conformidad</h5>
				<form id="form-conformidad" enctype="multipart/form-data" onsubmit="return guardarConformidad(event)">
					<div class="row g-3 align-items-end">
						<div class="col-md-6">
							<label class="form-label">Observación</label>
							<input type="text" class="form-control" id="cf_observacion" name="Observacion" maxlength="500">
						</div>
						<div class="col-md-4">
							<label class="form-label">Documento PDF</label>
							<input type="file" class="form-control" id="cf_documento" name="DocumentoPDF" accept=".pdf" required>
						</div>
						<div class="col-md-2">
							<button type="submit" class="btn btn-primary w-100">Guardar</button>
						</div>
					</div>
				</form>
			</div>
		<?php else: ?>
			<div class="alert alert-warning mt-3 mb-0" role="alert">
				Debe registrar primero la verificación técnica antes de cargar la conformidad.
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
