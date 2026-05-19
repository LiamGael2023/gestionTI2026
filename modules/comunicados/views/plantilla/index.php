<?php
$plantillas = isset($plantillas) && is_array($plantillas) ? $plantillas : [];
$totalPlantillasVista = count($plantillas);
?>

<style>
	#plantillas-table .table > :not(caption) > * > * {
		padding-top: 1rem;
		padding-bottom: 1rem;
	}

	#plantillas-table .btn-group .btn-icon {
		min-width: 2.25rem;
		min-height: 2.25rem;
	}

	#plantillas-table .table-sort {
		display: inline-flex !important;
		align-items: center;
		width: auto;
		gap: 0.35rem;
	}

	#plantillas-table .table-sort::after {
		margin-left: 0;
	}

	#plantillas-table td {
		user-select: text;
	}

	#plantillas-table th:last-child,
	#plantillas-table td:last-child {
		text-align: center !important;
	}

	#plantillas-table td:last-child .btn-group {
		justify-content: center;
	}
</style>

<div class="col-12">
	<div class="card">
		<div class="card-table">
			<div class="card-header">
				<div class="row w-full g-2 align-items-center">
					<div class="col">
						<h3 class="card-title mb-0">Plantillas</h3>
					</div>
					<div class="col-md-auto col-sm-12">
						<div class="ms-auto d-flex flex-wrap btn-list">
							<div class="input-group input-group-flat w-auto">
								<span class="input-group-text">
									<i class="ti ti-search"></i>
								</span>
								<input id="plantillas-table-search" type="text" class="form-control" placeholder="Buscar" autocomplete="off">
							</div>
							<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPlantilla">
								<i class="ti ti-plus me-1"></i>Nueva plantilla
							</button>
						</div>
					</div>
				</div>
			</div>
			<div id="plantillas-table">
				<div class="table-responsive">
					<table class="table table-vcenter">
						<thead>
							<tr>
								<th>
									<button class="table-sort" data-sort="sort-nombre">Nombre</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-descripcion">Descripcion</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-estado">Estado</button>
								</th>
								<th>Acciones</th>
							</tr>
						</thead>
						<tbody class="table-tbody">
							<?php if (empty($plantillas)): ?>
								<tr data-empty-row="true">
									<td colspan="4" class="text-center text-secondary py-4">No hay plantillas registradas.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($plantillas as $item): ?>
									<?php $activo = (int) ($item['Activo'] ?? 0) === 1; ?>
									<tr>
										<td class="sort-nombre fw-semibold"><?php echo htmlspecialchars((string) $item['NombrePlantilla'], ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="sort-descripcion"><?php echo htmlspecialchars((string) ($item['DescripcionPlantilla'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="sort-estado">
											<span class="badge <?php echo $activo ? 'bg-success-lt' : 'bg-secondary-lt'; ?>">
												<?php echo $activo ? 'Activa' : 'Inactiva'; ?>
											</span>
										</td>
										<td class="py-0 align-middle">
											<div class="btn-group" role="group">
												<?php if ($activo): ?>
													<a class="btn btn-icon btn-lg js-com-link"
														title="Usar en comunicado nuevo"
														href="index.php?module=comunicados&action=editor&plantilla=<?php echo (int) $item['IdPlantilla']; ?>">
														<i class="ti ti-copy-plus fs-2"></i>
													</a>
												<?php endif; ?>
												<button type="button"
													class="btn btn-icon btn-lg js-preview-plantilla"
													title="Previsualizar"
													data-nombre="<?php echo htmlspecialchars((string) $item['NombrePlantilla'], ENT_QUOTES, 'UTF-8'); ?>"
													data-html="<?php echo htmlspecialchars((string) ($item['HtmlBase'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
													<i class="ti ti-eye fs-2"></i>
												</button>
												<button type="button"
													class="btn btn-icon btn-lg js-editar-plantilla"
													title="Editar"
													data-id="<?php echo (int) $item['IdPlantilla']; ?>"
													data-nombre="<?php echo htmlspecialchars((string) $item['NombrePlantilla'], ENT_QUOTES, 'UTF-8'); ?>"
													data-descripcion="<?php echo htmlspecialchars((string) ($item['DescripcionPlantilla'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
													data-json="<?php echo htmlspecialchars((string) $item['ContenidoJson'], ENT_QUOTES, 'UTF-8'); ?>"
													data-html="<?php echo htmlspecialchars((string) ($item['HtmlBase'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
													<i class="ti ti-edit fs-2"></i>
												</button>
												<?php if ($activo): ?>
													<button type="button" class="btn btn-icon btn-lg text-danger js-eliminar-plantilla" title="Inactivar" data-id="<?php echo (int) $item['IdPlantilla']; ?>">
														<i class="ti ti-eye-x fs-2"></i>
													</button>
												<?php else: ?>
													<button type="button" class="btn btn-icon btn-lg text-success js-activar-plantilla" title="Activar" data-id="<?php echo (int) $item['IdPlantilla']; ?>">
														<i class="ti ti-eye-check fs-2"></i>
													</button>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				<div id="plantillas-table-footer" class="card-footer d-flex align-items-center" <?php echo $totalPlantillasVista === 0 ? 'style="display:none !important;"' : ''; ?>>
					<div class="dropdown">
						<a class="btn dropdown-toggle" data-bs-toggle="dropdown">
							<span id="plantillas-page-count" class="me-1">10</span>
							<span>registros</span>
						</a>
						<div class="dropdown-menu">
							<a class="dropdown-item" onclick="setPlantillasPageListItems(event)" data-value="10">10 registros</a>
							<a class="dropdown-item" onclick="setPlantillasPageListItems(event)" data-value="20">20 registros</a>
							<a class="dropdown-item" onclick="setPlantillasPageListItems(event)" data-value="50">50 registros</a>
							<a class="dropdown-item" onclick="setPlantillasPageListItems(event)" data-value="100">100 registros</a>
						</div>
					</div>
					<ul class="pagination m-0 ms-auto"></ul>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalPlantilla" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalPlantillaTitulo">Nueva plantilla</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="formPlantilla">
				<input type="hidden" id="plantillaId" name="IdPlantilla">
				<input type="hidden" id="plantillaJson" value="[]">
				<input type="hidden" id="plantillaHtml" value="">
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-12">
							<label class="form-label" for="plantillaNombre">Nombre</label>
							<input type="text" class="form-control" id="plantillaNombre" maxlength="150" required>
						</div>
						<div class="col-12">
							<label class="form-label" for="plantillaDescripcion">Descripcion</label>
							<input type="text" class="form-control" id="plantillaDescripcion" maxlength="500">
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" id="btnGuardarPlantilla">Guardar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="modalPreviewPlantilla" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalPreviewPlantillaTitulo">Previsualizar plantilla</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body bg-light">
				<iframe id="plantillaPreviewFrame" title="Previsualizacion de plantilla" sandbox="" style="width:100%;height:70vh;border:1px solid #d9dee3;border-radius:8px;background:#ffffff;"></iframe>
			</div>
		</div>
	</div>
</div>

<script>
	(function() {
		if (typeof window.comInitAdvancedTable === 'function') {
			window.comInitAdvancedTable({
				tableId: 'plantillas-table',
				footerId: 'plantillas-table-footer',
				searchId: 'plantillas-table-search',
				pageCountId: 'plantillas-page-count',
				setPageFunctionName: 'setPlantillasPageListItems',
				valueNames: ['sort-nombre', 'sort-descripcion', 'sort-estado']
			});
		}

		const modalEl = document.getElementById('modalPlantilla');
		const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
		const previewModalEl = document.getElementById('modalPreviewPlantilla');
		const previewModal = previewModalEl ? bootstrap.Modal.getOrCreateInstance(previewModalEl) : null;
		const form = document.getElementById('formPlantilla');

		function limpiar() {
			form.reset();
			document.getElementById('plantillaId').value = '';
			document.getElementById('plantillaJson').value = '[]';
			document.getElementById('plantillaHtml').value = '';
			document.getElementById('modalPlantillaTitulo').textContent = 'Nueva plantilla';
		}

		function guardar(payload) {
			return fetch('modules/comunicados/ajax.php?action=guardarPlantillaAjax', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(payload)
			}).then(function(response) {
				return response.json();
			});
		}

		function previewHtml(nombre, html) {
			if (!html) {
				window.comNotifySafe('warning', 'Sin vista previa', 'Esta plantilla no tiene HTML base guardado.');
				return;
			}
			document.getElementById('modalPreviewPlantillaTitulo').textContent = nombre || 'Previsualizar plantilla';
			document.getElementById('plantillaPreviewFrame').srcdoc = html;
			previewModal.show();
		}

		document.querySelector('[data-bs-target="#modalPlantilla"]').addEventListener('click', limpiar);

		document.querySelectorAll('.js-editar-plantilla').forEach(function(btn) {
			btn.addEventListener('click', function() {
				document.getElementById('plantillaId').value = this.dataset.id;
				document.getElementById('plantillaNombre').value = this.dataset.nombre || '';
				document.getElementById('plantillaDescripcion').value = this.dataset.descripcion || '';
				document.getElementById('plantillaJson').value = this.dataset.json || '[]';
				document.getElementById('plantillaHtml').value = this.dataset.html || '';
				document.getElementById('modalPlantillaTitulo').textContent = 'Editar plantilla';
				modal.show();
			});
		});

		document.querySelectorAll('.js-preview-plantilla').forEach(function(btn) {
			btn.addEventListener('click', function() {
				previewHtml(this.dataset.nombre || '', this.dataset.html || '');
			});
		});

		form.addEventListener('submit', function(event) {
			event.preventDefault();
			const json = document.getElementById('plantillaJson').value.trim() || '[]';
			try {
				JSON.parse(json);
			} catch (e) {
				window.comNotifySafe('warning', 'JSON invalido', 'Revise la estructura de bloques.');
				return;
			}
			guardar({
				IdPlantilla: document.getElementById('plantillaId').value,
				NombrePlantilla: document.getElementById('plantillaNombre').value.trim(),
				DescripcionPlantilla: document.getElementById('plantillaDescripcion').value.trim(),
				ContenidoJson: json,
				HtmlBase: document.getElementById('plantillaHtml').value
			}).then(function(res) {
				window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo guardar', res.message || '');
				if (res.success) {
					modal.hide();
					window.recargarVistaActualComunicados();
				}
			});
		});

		function cambiarEstado(action, id) {
			const data = new FormData();
			data.append('id', id);
			return fetch('modules/comunicados/ajax.php?action=' + action, {
				method: 'POST',
				body: data,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			}).then(function(response) {
				return response.json();
			});
		}

		document.querySelectorAll('.js-eliminar-plantilla').forEach(function(btn) {
			btn.addEventListener('click', function() {
				const id = this.dataset.id;
				window.comConfirmSafe({
					titulo: 'Inactivar plantilla',
					mensaje: 'Desea inactivar esta plantilla?',
					textoAceptar: 'Inactivar',
					claseAceptar: 'btn-danger'
				}).then(function(ok) {
					if (!ok) {
						return;
					}
					cambiarEstado('eliminarPlantillaAjax', id).then(function(res) {
						window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo completar', res.message || '');
						if (res.success) {
							window.recargarVistaActualComunicados();
						}
					});
				});
			});
		});

		document.querySelectorAll('.js-activar-plantilla').forEach(function(btn) {
			btn.addEventListener('click', function() {
				cambiarEstado('activarPlantillaAjax', this.dataset.id).then(function(res) {
					window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo completar', res.message || '');
					if (res.success) {
						window.recargarVistaActualComunicados();
					}
				});
			});
		});
	})();
</script>
