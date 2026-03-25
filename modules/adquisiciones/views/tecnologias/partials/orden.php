<div class="card card-body mb-3">
	<h4 class="fw-bold mb-3">Orden de Compra</h4>

	<?php if ($tieneOrdenCompra): ?>
		<div class="mt-3">
			<div class="border rounded p-3">
				<div class="row mb-2">
					<div class="col-auto">
						<strong>Fecha y Hora:</strong> <?php echo htmlspecialchars($formatearFecha($ordenCompra['FechaRegistro'])); ?>
					</div>
					<div class="col text-end">
						<div class="acciones-iconos">
							<?php if (!empty($ordenCompra['Documento'])): ?>
								<a href="index.php?module=adquisiciones&action=verOrdenCompraAjax&id=<?php echo (int) $ordenCompra['Id']; ?>"
									onclick="return abrirPdfEnModal(this.href);"
									class="text-decoration-none text-reset"
									title="Ver PDF">
									<i class="ti ti-file-text icon-action"></i>
								</a>
							<?php endif; ?>
							<i class="ti ti-trash icon-action"
								title="Eliminar"
								onclick="eliminarOrdenCompra(<?php echo (int) $ordenCompra['Id']; ?>)"></i>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6 mb-2 mb-md-0">
						<strong>Número de Orden:</strong><br>
						<?php echo htmlspecialchars($ordenCompra['NumeroOrden'] ?? ''); ?>
					</div>
					<div class="col-md-6">
						<strong>Fecha de Entrega:</strong><br>
						<?php echo htmlspecialchars($formatearFecha($ordenCompra['FechaEntrega'] ?? '')); ?>
						<div class="mt-2">
							<button type="button" class="btn btn-outline-primary btn-sm" onclick="mostrarFormularioFechaOrdenCompra()">Modificar fecha</button>
						</div>
					</div>
				</div>

				<div id="oc-form-fecha" class="row g-2 align-items-end mt-3" style="display: none;">
					<div class="col-md-4">
						<label class="form-label mb-1">Nueva fecha de entrega</label>
						<input type="date" class="form-control" id="oc_fecha_entrega_only" value="<?php echo htmlspecialchars($ordenCompra['FechaEntrega'] ?? ''); ?>">
					</div>
					<div class="col-md-3 d-flex gap-2">
						<button type="button" class="btn btn-primary" onclick="actualizarFechaOrdenCompra()">Guardar fecha</button>
						<button type="button" class="btn btn-outline-secondary" onclick="ocultarFormularioFechaOrdenCompra()">Cancelar</button>
					</div>
				</div>
			</div>
		</div>

		<div class="bg-light rounded p-3 mt-3">
			<h5 class="fw-bold mb-2">Reemplazar Orden de Compra</h5>
			<form id="form-orden-compra-actualizar" enctype="multipart/form-data" onsubmit="return actualizarOrdenCompra(event)">
				<input type="hidden" id="oc_id" value="<?php echo (int) $ordenCompra['Id']; ?>">
				<div class="row g-3 align-items-end">
					<div class="col-md-4">
						<label class="form-label">Número de Orden</label>
						<input type="text" class="form-control" id="oc_numero_orden_upd" name="NumeroOrden" maxlength="25" required>
					</div>
					<div class="col-md-3">
						<label class="form-label">Fecha de Entrega</label>
						<input type="date" class="form-control" id="oc_fecha_entrega_upd" name="FechaEntrega">
					</div>
					<div class="col-md-3">
						<label class="form-label">Nuevo Documento PDF</label>
						<input type="file" class="form-control" id="oc_documento_upd" name="DocumentoPDF" accept=".pdf" required>
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-primary w-100">Actualizar</button>
					</div>
				</div>
			</form>
		</div>
	<?php else: ?>
		<p class="text-secondary mb-0">No hay orden de compra registrada para este año.</p>

		<?php if ($puedeRegistrarOrdenCompra): ?>
			<div class="bg-light rounded p-3 mt-3">
				<h5 class="fw-bold mb-2">Agregar Orden de Compra</h5>
				<form id="form-orden-compra" enctype="multipart/form-data" onsubmit="return guardarOrdenCompra(event)">
					<div class="row g-3 align-items-end">
						<div class="col-md-4">
							<label class="form-label">Número de Orden</label>
							<input type="text" class="form-control" id="oc_numero_orden" name="NumeroOrden" maxlength="25" required>
						</div>
						<div class="col-md-3">
							<label class="form-label">Fecha de Entrega</label>
							<input type="date" class="form-control" id="oc_fecha_entrega" name="FechaEntrega">
						</div>
						<div class="col-md-3">
							<label class="form-label">Documento PDF</label>
							<input type="file" class="form-control" id="oc_documento" name="DocumentoPDF" accept=".pdf" required>
						</div>
						<div class="col-md-2">
							<button type="submit" class="btn btn-primary w-100">Guardar</button>
						</div>
					</div>
				</form>
			</div>
		<?php else: ?>
			<div class="alert alert-warning mt-3 mb-0" role="alert">
				Debe registrar primero la especificación técnica antes de cargar la orden de compra.
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>