<?php
$comunicados = isset($comunicados) && is_array($comunicados) ? $comunicados : [];
?>

<div class="row g-2 align-items-center mb-3">
	<div class="col">
		<div class="text-secondary">Listado general de comunicados HTML.</div>
	</div>
	<div class="col-auto">
		<a class="btn btn-primary js-com-link" href="index.php?module=comunicados&action=editor">
			<i class="ti ti-plus me-1"></i>Nuevo comunicado
		</a>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-vcenter card-table table-striped">
		<thead>
			<tr>
				<th>Titulo</th>
				<th>Estado</th>
				<th>Activo</th>
				<th class="text-end">Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($comunicados)): ?>
				<tr>
					<td colspan="6" class="text-center text-secondary py-4">No hay comunicados registrados.</td>
				</tr>
			<?php else: ?>
				<?php foreach ($comunicados as $item): ?>
					<?php $activo = (int) ($item['Activo'] ?? 0) === 1; ?>
					<tr>
						<td class="fw-semibold"><?php echo htmlspecialchars((string) $item['TituloComunicado'], ENT_QUOTES, 'UTF-8'); ?></td>
						<td>
							<span class="badge <?php echo $item['EstadoComunicado'] === 'LISTO' ? 'bg-success-lt' : 'bg-warning-lt text-dark'; ?>">
								<?php echo htmlspecialchars((string) $item['EstadoComunicado'], ENT_QUOTES, 'UTF-8'); ?>
							</span>
						</td>
						<td>
							<span class="badge <?php echo $activo ? 'bg-success-lt' : 'bg-secondary-lt'; ?>">
								<?php echo $activo ? 'Activo' : 'Inactivo'; ?>
							</span>
						</td>
						<td class="text-end align-middle">
							<div class="btn-group" role="group">
								<a class="btn btn-icon btn-lg" title="Visualizar" href="index.php?module=comunicados&action=visualizar&id=<?php echo (int) $item['IdComunicado']; ?>" target="_blank" rel="noopener">
									<i class="ti ti-eye fs-2"></i>
								</a>
								<a class="btn btn-icon btn-lg js-com-link" title="Editar" href="index.php?module=comunicados&action=editor&id=<?php echo (int) $item['IdComunicado']; ?>">
									<i class="ti ti-edit fs-2"></i>
								</a>
								<button type="button"
									class="btn btn-icon btn-lg js-convertir-plantilla"
									title="Convertir en plantilla"
									data-id="<?php echo (int) $item['IdComunicado']; ?>"
									data-titulo="<?php echo htmlspecialchars((string) $item['TituloComunicado'], ENT_QUOTES, 'UTF-8'); ?>">
									<i class="ti ti-template fs-2"></i>
								</button>
								<?php if ($activo): ?>
									<button type="button" class="btn btn-icon btn-lg text-danger js-eliminar-comunicado" title="Inactivar" data-id="<?php echo (int) $item['IdComunicado']; ?>">
										<i class="ti ti-eye-x fs-2"></i>
									</button>
								<?php else: ?>
									<button type="button" class="btn btn-icon btn-lg text-success js-activar-comunicado" title="Activar" data-id="<?php echo (int) $item['IdComunicado']; ?>">
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

<script>
	(function() {
		function postEstado(action, id) {
			const formData = new FormData();
			formData.append('id', id);
			return fetch('index.php?module=comunicados&action=' + action, {
				method: 'POST',
				body: formData,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			}).then(function(response) {
				return response.json();
			});
		}

		document.querySelectorAll('.js-eliminar-comunicado').forEach(function(btn) {
			btn.addEventListener('click', function() {
				const id = this.dataset.id;
				window.comConfirmSafe({
					titulo: 'Inactivar comunicado',
					mensaje: 'Desea inactivar este comunicado?',
					textoAceptar: 'Inactivar',
					claseAceptar: 'btn-danger'
				}).then(function(ok) {
					if (!ok) {
						return;
					}
					postEstado('eliminarComunicadoAjax', id).then(function(res) {
						window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo completar', res.message || '');
						if (res.success) {
							window.recargarVistaActualComunicados();
						}
					});
				});
			});
		});

		document.querySelectorAll('.js-activar-comunicado').forEach(function(btn) {
			btn.addEventListener('click', function() {
				postEstado('activarComunicadoAjax', this.dataset.id).then(function(res) {
					window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo completar', res.message || '');
					if (res.success) {
						window.recargarVistaActualComunicados();
					}
				});
			});
		});

		document.querySelectorAll('.js-convertir-plantilla').forEach(function(btn) {
			btn.addEventListener('click', function() {
				const id = this.dataset.id;
				const titulo = this.dataset.titulo || 'Comunicado';
				window.comPromptSafe({
					titulo: 'Convertir en plantilla',
					mensaje: 'Ingrese el nombre de la nueva plantilla.',
					valor: 'Plantilla - ' + titulo,
					textoAceptar: 'Crear plantilla',
					requerido: true
				}).then(function(nombre) {
					if (nombre === null) {
						return;
					}

					const formData = new FormData();
					formData.append('id', id);
					formData.append('nombre', nombre.trim());
					formData.append('descripcion', 'Generada desde comunicado');

					fetch('index.php?module=comunicados&action=convertirComunicadoPlantillaAjax', {
						method: 'POST',
						body: formData,
						headers: { 'X-Requested-With': 'XMLHttpRequest' }
					}).then(function(response) {
						return response.json();
					}).then(function(res) {
						window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Plantilla creada' : 'No se pudo crear', res.message || '');
					}).catch(function() {
						window.comNotifySafe('danger', 'Error de conexion', 'No se pudo conectar con el servidor.');
					});
				});
			});
		});
	})();
</script>

