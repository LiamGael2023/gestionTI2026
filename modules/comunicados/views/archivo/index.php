<?php
$archivos = isset($archivos) && is_array($archivos) ? $archivos : [];
$totalArchivosVista = count($archivos);
?>

<style>
	#archivos-table .table > :not(caption) > * > * {
		padding-top: 1rem;
		padding-bottom: 1rem;
	}

	#archivos-table .btn-group .btn-icon {
		min-width: 2.25rem;
		min-height: 2.25rem;
	}

	#archivos-table .table-sort {
		display: inline-flex !important;
		align-items: center;
		width: auto;
		gap: 0.35rem;
	}

	#archivos-table .table-sort::after {
		margin-left: 0;
	}

	#archivos-table td {
		user-select: text;
	}
</style>

<div class="col-12">
	<div class="card">
		<div class="card-table">
			<div class="card-header">
				<div class="row w-full g-2 align-items-center">
					<div class="col">
						<h3 class="card-title mb-0">Archivos</h3>
					</div>
					<div class="col-md-auto col-sm-12">
						<div class="ms-auto d-flex flex-wrap btn-list">
							<div class="input-group input-group-flat w-auto">
								<span class="input-group-text">
									<i class="ti ti-search"></i>
								</span>
								<input id="archivos-table-search" type="text" class="form-control" placeholder="Buscar" autocomplete="off">
							</div>
							<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalArchivo">
								<i class="ti ti-upload me-1"></i>Subir archivo
							</button>
						</div>
					</div>
				</div>
			</div>
			<div id="archivos-table">
				<div class="table-responsive">
					<table class="table table-vcenter">
						<thead>
							<tr>
								<th>
									<button class="table-sort" data-sort="sort-archivo">Archivo</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-tipo">Tipo</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-tamano">Tamano</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-estado">Estado</button>
								</th>
								<th class="text-end">Acciones</th>
							</tr>
						</thead>
						<tbody class="table-tbody">
							<?php if (empty($archivos)): ?>
								<tr data-empty-row="true">
									<td colspan="5" class="text-center text-secondary py-4">No hay archivos cargados.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($archivos as $item): ?>
									<?php
									$activo = (int) ($item['Activo'] ?? 0) === 1;
									$tamanoKb = number_format(((int) ($item['TamanoBytes'] ?? 0)) / 1024, 1);
									$urlPublica = (string) ($item['UrlPublica'] ?? '');
									$tipoArchivo = strtoupper(trim((string) ($item['TipoArchivo'] ?? 'DOCUMENTO')));
									$tipoArchivoLabel = $tipoArchivo === 'IMAGEN' ? 'Imagen' : 'Documento';
									?>
									<tr>
										<td class="sort-archivo fw-semibold"><?php echo htmlspecialchars((string) $item['NombreOriginal'], ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="sort-tipo">
											<span class="badge bg-azure-lt"><?php echo htmlspecialchars($tipoArchivoLabel, ENT_QUOTES, 'UTF-8'); ?></span>
										</td>
										<td class="sort-tamano" data-sort="<?php echo (int) ($item['TamanoBytes'] ?? 0); ?>"><?php echo $tamanoKb; ?> KB</td>
										<td class="sort-estado">
											<span class="badge <?php echo $activo ? 'bg-success-lt' : 'bg-secondary-lt'; ?>">
												<?php echo $activo ? 'Activo' : 'Inactivo'; ?>
											</span>
										</td>
										<td class="py-0 align-middle text-end">
											<div class="btn-group" role="group">
												<a class="btn btn-icon btn-lg" title="Abrir" href="<?php echo htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
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
				<div id="archivos-table-footer" class="card-footer d-flex align-items-center" <?php echo $totalArchivosVista === 0 ? 'style="display:none !important;"' : ''; ?>>
					<div class="dropdown">
						<a class="btn dropdown-toggle" data-bs-toggle="dropdown">
							<span id="archivos-page-count" class="me-1">10</span>
							<span>registros</span>
						</a>
						<div class="dropdown-menu">
							<a class="dropdown-item" onclick="setArchivosPageListItems(event)" data-value="10">10 registros</a>
							<a class="dropdown-item" onclick="setArchivosPageListItems(event)" data-value="20">20 registros</a>
							<a class="dropdown-item" onclick="setArchivosPageListItems(event)" data-value="50">50 registros</a>
							<a class="dropdown-item" onclick="setArchivosPageListItems(event)" data-value="100">100 registros</a>
						</div>
					</div>
					<ul class="pagination m-0 ms-auto"></ul>
				</div>
			</div>
		</div>
	</div>
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
					<input type="file" class="form-control" id="archivoInput" name="archivo" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" required>
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
		function inicializarArchivos() {
			if (typeof window.comInitAdvancedTable === 'function') {
				window.comInitAdvancedTable({
					tableId: 'archivos-table',
					footerId: 'archivos-table-footer',
					searchId: 'archivos-table-search',
					pageCountId: 'archivos-page-count',
					setPageFunctionName: 'setArchivosPageListItems',
					valueNames: ['sort-archivo', 'sort-tipo', 'sort-tamano', 'sort-estado']
				});
			}

			const form = document.getElementById('formArchivo');
			const modalEl = document.getElementById('modalArchivo');
			const btnAbrirModal = document.querySelector('[data-bs-target="#modalArchivo"]');

			if (btnAbrirModal && form && btnAbrirModal.dataset.boundClick !== '1') {
				btnAbrirModal.dataset.boundClick = '1';
				btnAbrirModal.addEventListener('click', function() {
					form.reset();
				});
			}

			if (form && form.dataset.boundSubmit !== '1') {
				form.dataset.boundSubmit = '1';
				form.addEventListener('submit', function(event) {
					event.preventDefault();
					const btn = document.getElementById('btnSubirArchivo');
					const data = new FormData(form);
					btn.disabled = true;
					btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Subiendo...';

					fetch('modules/comunicados/ajax.php?action=subirArchivoAjax', {
						method: 'POST',
						body: data,
						headers: { 'X-Requested-With': 'XMLHttpRequest' }
					}).then(function(response) {
						return response.json();
					}).then(function(res) {
						window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Archivo cargado' : 'No se pudo subir', res.message || '');
						if (res.success) {
							if (typeof window.comHideModalSafe === 'function') {
								window.comHideModalSafe(modalEl);
							}
							window.recargarVistaActualComunicados();
						}
					}).catch(function() {
						window.comNotifySafe('danger', 'Error de conexion', 'No se pudo conectar con el servidor.');
					}).finally(function() {
						btn.disabled = false;
						btn.innerHTML = 'Subir';
					});
				});
			}

			document.querySelectorAll('.js-eliminar-archivo').forEach(function(btn) {
				if (btn.dataset.boundClick === '1') {
					return;
				}
				btn.dataset.boundClick = '1';
				btn.addEventListener('click', function() {
					const id = this.dataset.id;
					window.comConfirmSafe({
						titulo: 'Inactivar archivo',
						mensaje: 'Desea inactivar este archivo?',
						textoAceptar: 'Inactivar'
					}).then(function(ok) {
						if (!ok) {
							return;
						}
						const data = new FormData();
						data.append('id', id);
						fetch('modules/comunicados/ajax.php?action=eliminarArchivoAjax', {
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
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', function() {
				inicializarArchivos();
			}, { once: true });
		} else {
			inicializarArchivos();
		}
	})();
</script>
