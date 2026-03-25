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
							<td><?php echo htmlspecialchars($ordenCompra['NumeroOrden'] ?? ''); ?></td>
							<td><?php echo htmlspecialchars($formatearFecha($ordenCompra['FechaEntrega'] ?? '')); ?></td>
							<td class="text-end">
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
					Actualizar Orden de Compra
				</button>
			<?php else: ?>
				<button type="button"
					class="btn btn-primary"
					data-bs-toggle="modal"
					data-bs-target="#modalOrdenCompra"
					data-toggle="modal"
					data-target="#modalOrdenCompra"
					onclick="return abrirModalOrdenCompra('crear');">
					Agregar Orden de Compra
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