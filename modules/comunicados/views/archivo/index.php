<?php
$archivos = isset($archivos) && is_array($archivos) ? $archivos : [];
?>

<div class="row g-2 align-items-center mb-3">
	<div class="col">
		<div class="text-secondary">Carga centralizada de imagenes y documentos para usar en comunicados.</div>
	</div>
	<div class="col-auto">
		<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalArchivo">
			<i class="ti ti-upload me-1"></i>Subir archivo
		</button>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-vcenter card-table table-striped">
		<thead>
			<tr>
				<th>Archivo</th>
				<th>Tipo</th>
				<th>Tamano</th>
				<th>URL</th>
				<th>Estado</th>
				<th class="text-end">Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($archivos)): ?>
				<tr><td colspan="6" class="text-center text-secondary py-4">No hay archivos cargados.</td></tr>
			<?php else: ?>
				<?php foreach ($archivos as $item): ?>
					<?php $activo = (int) ($item['Activo'] ?? 0) === 1; ?>
					<tr>
						<td class="fw-semibold"><?php echo htmlspecialchars((string) $item['NombreOriginal'], ENT_QUOTES, 'UTF-8'); ?></td>
						<td><span class="badge bg-azure-lt"><?php echo htmlspecialchars((string) $item['TipoArchivo'], ENT_QUOTES, 'UTF-8'); ?></span></td>
						<td><?php echo number_format(((int) ($item['TamanoBytes'] ?? 0)) / 1024, 1); ?> KB</td>
						<td>
							<input type="text" class="form-control form-control-sm font-monospace" readonly value="<?php echo htmlspecialchars((string) $item['UrlPublica'], ENT_QUOTES, 'UTF-8'); ?>">
						</td>
						<td><span class="badge <?php echo $activo ? 'bg-success-lt' : 'bg-secondary-lt'; ?>"><?php echo $activo ? 'Activo' : 'Inactivo'; ?></span></td>
						<td class="text-end align-middle">
							<div class="btn-group" role="group">
								<a class="btn btn-icon btn-lg" title="Abrir" href="<?php echo htmlspecialchars((string) $item['UrlPublica'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
									<i class="ti ti-external-link fs-2"></i>
								</a>
								<?php if ($activo): ?>
									<button type="button" class="btn btn-icon btn-lg text-danger js-eliminar-archivo" title="Inactivar" data-id="<?php echo (int) $item['IdArchivo']; ?>">
										<i class="ti ti-eye-x fs-2"></i>
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

<div class="modal fade" id="modalArchivo" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Subir archivo</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="formArchivo">
				<div class="modal-body">
					<label class="form-label" for="archivoInput">Archivo</label>
					<input type="file" class="form-control" id="archivoInput" name="archivo" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" required>
					<div class="form-hint">Maximo 12 MB. La URL generada puede pegarse en el editor.</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" id="btnSubirArchivo">Subir</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
	(function() {
		const form = document.getElementById('formArchivo');
		const modalEl = document.getElementById('modalArchivo');
		const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

		form.addEventListener('submit', function(event) {
			event.preventDefault();
			const btn = document.getElementById('btnSubirArchivo');
			const data = new FormData(form);
			btn.disabled = true;
			btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Subiendo...';

			fetch('index.php?module=comunicados&action=subirArchivoAjax', {
				method: 'POST',
				body: data,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			}).then(function(response) {
				return response.json();
			}).then(function(res) {
				window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Archivo cargado' : 'No se pudo subir', res.message || '');
				if (res.success) {
					modal.hide();
					window.recargarVistaActualComunicados();
				}
			}).catch(function() {
				window.comNotifySafe('danger', 'Error de conexion', 'No se pudo conectar con el servidor.');
			}).finally(function() {
				btn.disabled = false;
				btn.innerHTML = 'Subir';
			});
		});

		document.querySelectorAll('.js-eliminar-archivo').forEach(function(btn) {
			btn.addEventListener('click', function() {
				const id = this.dataset.id;
				window.comConfirmSafe({
					titulo: 'Inactivar archivo',
					mensaje: 'Desea inactivar este archivo?',
					textoAceptar: 'Inactivar',
					claseAceptar: 'btn-danger'
				}).then(function(ok) {
					if (!ok) {
						return;
					}
					const data = new FormData();
					data.append('id', id);
					fetch('index.php?module=comunicados&action=eliminarArchivoAjax', {
						method: 'POST',
						body: data,
						headers: { 'X-Requested-With': 'XMLHttpRequest' }
					}).then(function(response) {
						return response.json();
					}).then(function(res) {
						window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo completar', res.message || '');
						if (res.success) {
							window.recargarVistaActualComunicados();
						}
					});
				});
			});
		});
	})();
</script>

