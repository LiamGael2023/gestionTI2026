<?php
$plantillas = isset($plantillas) && is_array($plantillas) ? $plantillas : [];
?>

<div class="row g-2 align-items-center mb-3">
	<div class="col">
		<div class="text-secondary">Gestion de estructuras reutilizables para el editor.</div>
	</div>
	<div class="col-auto">
		<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPlantilla">
			<i class="ti ti-plus me-1"></i>Nueva plantilla
		</button>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-vcenter card-table table-striped">
		<thead>
			<tr>
				<th>Nombre</th>
				<th>Descripcion</th>
				<th>Estado</th>
				<th class="text-end">Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($plantillas)): ?>
				<tr><td colspan="4" class="text-center text-secondary py-4">No hay plantillas registradas.</td></tr>
			<?php else: ?>
				<?php foreach ($plantillas as $item): ?>
					<?php $activo = (int) ($item['Activo'] ?? 0) === 1; ?>
					<tr>
						<td class="fw-semibold"><?php echo htmlspecialchars((string) $item['NombrePlantilla'], ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo htmlspecialchars((string) ($item['DescripcionPlantilla'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
						<td><span class="badge <?php echo $activo ? 'bg-success-lt' : 'bg-secondary-lt'; ?>"><?php echo $activo ? 'Activa' : 'Inactiva'; ?></span></td>
						<td class="text-end align-middle">
							<div class="btn-group" role="group">
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

<div class="modal fade" id="modalPlantilla" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalPlantillaTitulo">Nueva plantilla</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="formPlantilla">
				<input type="hidden" id="plantillaId" name="IdPlantilla">
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-12 col-md-6">
							<label class="form-label" for="plantillaNombre">Nombre</label>
							<input type="text" class="form-control" id="plantillaNombre" maxlength="150" required>
						</div>
						<div class="col-12 col-md-6">
							<label class="form-label" for="plantillaDescripcion">Descripcion</label>
							<input type="text" class="form-control" id="plantillaDescripcion" maxlength="500">
						</div>
						<div class="col-12">
							<label class="form-label" for="plantillaJson">Bloques JSON</label>
							<textarea class="form-control font-monospace" id="plantillaJson" rows="5">[]</textarea>
							<div class="form-hint">El editor tambien puede guardar plantillas desde el constructor.</div>
						</div>
						<div class="col-12">
							<label class="form-label" for="plantillaHtml">HTML base</label>
							<textarea class="form-control font-monospace" id="plantillaHtml" rows="5"></textarea>
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

<script>
	(function() {
		const modalEl = document.getElementById('modalPlantilla');
		const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
		const form = document.getElementById('formPlantilla');

		function limpiar() {
			form.reset();
			document.getElementById('plantillaId').value = '';
			document.getElementById('plantillaJson').value = '[]';
			document.getElementById('modalPlantillaTitulo').textContent = 'Nueva plantilla';
		}

		function guardar(payload) {
			return fetch('index.php?module=comunicados&action=guardarPlantillaAjax', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(payload)
			}).then(function(response) {
				return response.json();
			});
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

		form.addEventListener('submit', function(event) {
			event.preventDefault();
			let json = document.getElementById('plantillaJson').value.trim() || '[]';
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
			return fetch('index.php?module=comunicados&action=' + action, {
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

