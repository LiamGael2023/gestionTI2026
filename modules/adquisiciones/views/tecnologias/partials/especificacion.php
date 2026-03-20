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
							<td><?php echo htmlspecialchars($especificacionTecnica['Codigo']); ?></td>
							<td class="text-end">
								<div class="acciones-iconos">
									<?php if (!empty($especificacionTecnica['Documento'])): ?>
										<a href="index.php?module=adquisiciones&action=verEspecificacionTecnicaAjax&id=<?php echo (int) $especificacionTecnica['Id']; ?>"
											target="_blank"
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

		<div class="bg-light rounded p-3 mt-3">
			<h5 class="fw-bold mb-2">Reemplazar Especificación Técnica</h5>
			<form id="form-especificacion-actualizar" enctype="multipart/form-data" onsubmit="return actualizarEspecificacionTecnica(event)">
				<input type="hidden" id="et_id" value="<?php echo (int) $especificacionTecnica['Id']; ?>">
				<div class="row g-3 align-items-end">
					<div class="col-md-6">
						<label class="form-label">Código Especificación Técnica</label>
						<input type="text" class="form-control" id="et_codigo_upd" name="Codigo" required>
					</div>
					<div class="col-md-4">
						<label class="form-label">Nuevo Documento PDF</label>
						<input type="file" class="form-control" id="et_documento_upd" name="DocumentoPDF" accept=".pdf" required>
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-primary w-100">Actualizar</button>
					</div>
				</div>
			</form>
		</div>
	<?php else: ?>
		<p class="text-secondary mb-0">No hay especificación técnica registrada para este año.</p>

		<?php if ($puedeRegistrarEspecificacion): ?>
			<div class="bg-light rounded p-3 mt-3">
				<h5 class="fw-bold mb-2">Agregar Especificación Técnica</h5>
				<form id="form-especificacion" enctype="multipart/form-data" onsubmit="return guardarEspecificacionTecnica(event)">
					<div class="row g-3 align-items-end">
						<div class="col-md-6">
							<label class="form-label">Código</label>
							<input type="text" class="form-control" id="et_codigo" name="Codigo"
								value="ET <?php echo $codigoTec; ?> <?php echo $nombreTec; ?> <?php echo $anioActual; ?>" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Documento PDF</label>
							<input type="file" class="form-control" id="et_documento" name="DocumentoPDF" accept=".pdf" required>
						</div>
						<div class="col-md-2">
							<button type="submit" class="btn btn-primary w-100">Guardar</button>
						</div>
					</div>
				</form>
			</div>
		<?php else: ?>
			<div class="alert alert-warning mt-3 mb-0" role="alert">
				Debe registrar al menos <?php echo $minimoFichasRequeridas; ?> fichas técnicas antes de cargar la especificación técnica.
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
