<?php
$comunicados = isset($comunicados) && is_array($comunicados) ? $comunicados : [];
$totalComunicadosVista = count($comunicados);
?>

<style>
	#comunicados-table .table > :not(caption) > * > * {
		padding-top: 1rem;
		padding-bottom: 1rem;
	}

	#comunicados-table .btn-group .btn-icon {
		min-width: 2.25rem;
		min-height: 2.25rem;
	}

	#comunicados-table .table-sort {
		display: inline-flex !important;
		align-items: center;
		width: auto;
		gap: 0.35rem;
	}

	#comunicados-table .table-sort::after {
		margin-left: 0;
	}

	#comunicados-table td {
		user-select: text;
	}
</style>

<div class="col-12">
	<div class="card">
		<div class="card-table">
			<div class="card-header">
				<div class="row w-full g-2 align-items-center">
					<div class="col">
						<h3 class="card-title mb-0">Comunicados</h3>
					</div>
					<div class="col-md-auto col-sm-12">
						<div class="ms-auto d-flex flex-wrap btn-list">
							<div class="input-group input-group-flat w-auto">
								<span class="input-group-text">
									<i class="ti ti-search"></i>
								</span>
								<input id="comunicados-table-search" type="text" class="form-control" placeholder="Buscar" autocomplete="off">
							</div>
							<a class="btn btn-primary" href="index.php?module=comunicados&action=editor">
								<i class="ti ti-plus me-1"></i>Nuevo comunicado
							</a>
						</div>
					</div>
				</div>
			</div>
			<div id="comunicados-table">
				<div class="table-responsive">
					<table class="table table-vcenter">
						<thead>
							<tr>
								<th>
									<button class="table-sort" data-sort="sort-titulo">Titulo</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-estado">Estado</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-activo">Activo</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-creado-por">Creado por</button>
								</th>
								<th class="text-end">Acciones</th>
							</tr>
						</thead>
						<tbody class="table-tbody">
							<?php if (empty($comunicados)): ?>
								<tr data-empty-row="true">
									<td colspan="5" class="text-center text-secondary py-4">No hay comunicados registrados.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($comunicados as $item): ?>
									<?php $activo = (int) ($item['Activo'] ?? 0) === 1; ?>
									<?php $estadoComunicado = (string) ($item['EstadoComunicado'] ?? 'BORRADOR'); ?>
									<?php $puedeGestionar = !empty($esAdministrador) || (int) ($item['IdUsuarioRegistro'] ?? 0) === (int) ($idUsuarioSesion ?? 0); ?>
									<?php $puedeEditar = $puedeGestionar || $estadoComunicado !== 'LISTO'; ?>
									<tr>
										<td class="sort-titulo fw-semibold"><?php echo htmlspecialchars((string) $item['TituloComunicado'], ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="sort-estado">
											<span class="badge <?php echo $estadoComunicado === 'LISTO' ? 'bg-success-lt' : 'bg-warning-lt text-dark'; ?>">
												<?php echo $estadoComunicado === 'LISTO' ? 'Listo' : 'Borrador'; ?>
											</span>
										</td>
										<td class="sort-activo">
											<span class="badge <?php echo $activo ? 'bg-success-lt' : 'bg-secondary-lt'; ?>">
												<?php echo $activo ? 'Activo' : 'Inactivo'; ?>
											</span>
										</td>
										<td class="sort-creado-por">
											<?php echo htmlspecialchars((string) ($item['UsuarioRegistro'] ?? ('Usuario #' . (int) ($item['IdUsuarioRegistro'] ?? 0))), ENT_QUOTES, 'UTF-8'); ?>
										</td>
										<td class="py-0 align-middle text-end">
											<div class="btn-group" role="group">
												<a class="btn btn-icon btn-lg" title="Visualizar" href="modules/comunicados/comunicado.php/<?php echo (int) $item['IdComunicado']; ?>" target="_blank" rel="noopener">
													<i class="ti ti-eye-share fs-2"></i>
												</a>
												<?php if ($puedeEditar && $activo): ?>
													<a class="btn btn-icon btn-lg js-editar-comunicado" title="Editar" href="index.php?module=comunicados&action=editor&id=<?php echo (int) $item['IdComunicado']; ?>">
														<i class="ti ti-edit fs-2"></i>
													</a>
												<?php endif; ?>
												<?php if ($activo): ?>
													<button type="button"
														class="btn btn-icon btn-lg js-convertir-plantilla"
														title="Convertir en plantilla"
														data-id="<?php echo (int) $item['IdComunicado']; ?>"
														data-titulo="<?php echo htmlspecialchars((string) $item['TituloComunicado'], ENT_QUOTES, 'UTF-8'); ?>">
														<i class="ti ti-template fs-2"></i>
													</button>
												<?php endif; ?>
												<?php if ($puedeGestionar): ?>
													<?php if ($activo): ?>
														<button type="button" class="btn btn-icon btn-lg text-danger js-eliminar-comunicado" title="Inactivar" data-id="<?php echo (int) $item['IdComunicado']; ?>">
															<i class="ti ti-eye-x fs-2"></i>
														</button>
													<?php else: ?>
														<button type="button" class="btn btn-icon btn-lg text-success js-activar-comunicado" title="Activar" data-id="<?php echo (int) $item['IdComunicado']; ?>">
															<i class="ti ti-eye-check fs-2"></i>
														</button>
													<?php endif; ?>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				<div id="comunicados-table-footer" class="card-footer d-flex align-items-center" <?php echo $totalComunicadosVista === 0 ? 'style="display:none !important;"' : ''; ?>>
					<div class="dropdown">
						<a class="btn dropdown-toggle" data-bs-toggle="dropdown">
							<span id="comunicados-page-count" class="me-1">10</span>
							<span>registros</span>
						</a>
						<div class="dropdown-menu">
							<a class="dropdown-item" onclick="setComunicadosPageListItems(event)" data-value="10">10 registros</a>
							<a class="dropdown-item" onclick="setComunicadosPageListItems(event)" data-value="20">20 registros</a>
							<a class="dropdown-item" onclick="setComunicadosPageListItems(event)" data-value="50">50 registros</a>
							<a class="dropdown-item" onclick="setComunicadosPageListItems(event)" data-value="100">100 registros</a>
						</div>
					</div>
					<ul class="pagination m-0 ms-auto"></ul>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	(function() {
		if (typeof window.comInitAdvancedTable === 'function') {
			window.comInitAdvancedTable({
				tableId: 'comunicados-table',
				footerId: 'comunicados-table-footer',
				searchId: 'comunicados-table-search',
				pageCountId: 'comunicados-page-count',
				setPageFunctionName: 'setComunicadosPageListItems',
				valueNames: ['sort-titulo', 'sort-estado', 'sort-activo', 'sort-creado-por']
			});
		}

		function postEstado(action, id) {
			const formData = new FormData();
			formData.append('id', id);
			return fetch('modules/comunicados/ajax.php?action=' + action, {
				method: 'POST',
				body: formData,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			}).then(function(response) {
				return response.json();
			});
		}

		function refrescarListaComunicados() {
			const list = window.tabler_list && window.tabler_list['comunicados-table'];
			if (!list) {
				return;
			}

			if (typeof list.reIndex === 'function') {
				list.reIndex();
			}
			list.update();
		}

		function crearBotonEditarComunicado(id) {
			const link = document.createElement('a');
			link.className = 'btn btn-icon btn-lg js-editar-comunicado';
			link.title = 'Editar';
			link.href = 'index.php?module=comunicados&action=editor&id=' + encodeURIComponent(id);
			link.innerHTML = '<i class="ti ti-edit fs-2"></i>';
			return link;
		}

		function actualizarEstadoComunicado(btn, activo) {
			const row = btn.closest('tr');
			if (!row) {
				return;
			}

			const estadoCell = row.querySelector('.sort-activo');
			if (estadoCell) {
				estadoCell.innerHTML = '<span class="badge ' + (activo ? 'bg-success-lt' : 'bg-secondary-lt') + '">' + (activo ? 'Activo' : 'Inactivo') + '</span>';
			}

			const group = btn.closest('.btn-group');
			if (group) {
				const editar = group.querySelector('.js-editar-comunicado');
				const visualizar = group.querySelector('a[title="Visualizar"]');
				if (activo && !editar) {
					const nuevoEditar = crearBotonEditarComunicado(btn.dataset.id);
					if (visualizar && visualizar.nextSibling) {
						group.insertBefore(nuevoEditar, visualizar.nextSibling);
					} else {
						group.insertBefore(nuevoEditar, group.firstChild);
					}
				}
				if (!activo && editar) {
					editar.remove();
				}
			}

			btn.className = 'btn btn-icon btn-lg ' + (activo ? 'text-danger js-eliminar-comunicado' : 'text-success js-activar-comunicado');
			btn.title = activo ? 'Inactivar' : 'Activar';
			btn.innerHTML = '<i class="ti ' + (activo ? 'ti-eye-x' : 'ti-eye-check') + ' fs-2"></i>';

			refrescarListaComunicados();
		}

		function inactivarComunicado(btn) {
			const id = btn.dataset.id;
			window.comConfirmSafe({
				titulo: 'Inactivar comunicado',
				mensaje: 'Desea inactivar este comunicado?',
				textoAceptar: 'Inactivar'
			}).then(function(ok) {
				if (!ok) {
					return;
				}
				postEstado('eliminarComunicadoAjax', id).then(function(res) {
					window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo completar', res.message || '');
					if (res.success) {
						actualizarEstadoComunicado(btn, false);
					}
				});
			});
		}

		function activarComunicado(btn) {
			window.comConfirmSafe({
				titulo: 'Activar comunicado',
				mensaje: 'Desea activar este comunicado?',
				textoAceptar: 'Activar',
				colorAceptar: '#206bc4'
			}).then(function(ok) {
				if (!ok) {
					return;
				}
				postEstado('activarComunicadoAjax', btn.dataset.id).then(function(res) {
					window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Operacion correcta' : 'No se pudo completar', res.message || '');
					if (res.success) {
						actualizarEstadoComunicado(btn, true);
					}
				});
			});
		}

		document.getElementById('comunicados-table').addEventListener('click', function(event) {
			const btnInactivar = event.target.closest('.js-eliminar-comunicado');
			if (btnInactivar) {
				inactivarComunicado(btnInactivar);
				return;
			}

			const btnActivar = event.target.closest('.js-activar-comunicado');
			if (btnActivar) {
				activarComunicado(btnActivar);
			}
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

					fetch('modules/comunicados/ajax.php?action=convertirComunicadoPlantillaAjax', {
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
