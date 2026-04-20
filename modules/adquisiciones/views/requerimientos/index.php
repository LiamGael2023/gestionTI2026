<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
	<?php $metasSiafActivasLista = (isset($metasSiafActivas) && is_array($metasSiafActivas)) ? $metasSiafActivas : []; ?>
	<?php $subCentrosCostoLista = (isset($subCentrosCosto) && is_array($subCentrosCosto)) ? $subCentrosCosto : []; ?>
	<div class="d-flex gap-2 align-items-center flex-wrap">
		<label class="form-label mb-0 text-nowrap">Filtrar por año:</label>
		<select id="filtroAnio" class="form-select w-auto" onchange="filtrarPorAnio()" <?php echo empty($aniosDisponibles) ? 'disabled' : ''; ?>>
			<?php if (empty($aniosDisponibles)): ?>
				<option value="">Sin registros</option>
			<?php else: ?>
				<?php foreach ($aniosDisponibles as $anio): ?>
					<option value="<?php echo $anio; ?>" <?php echo ($anioFiltro == $anio) ? 'selected' : ''; ?>>
						<?php echo $anio; ?>
					</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
	</div>
	<div class="d-flex gap-2">
		<button class="btn btn-success" onclick="abrirModalImportar()">
			Importar de SIGA
		</button>
		<button class="btn btn-primary" onclick="nuevoRequerimiento()">
			Agregar Requerimiento
		</button>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-vcenter card-table table-striped">
		<thead>
			<tr>
				<th>Nro. de Pedido</th>
				<th>Código Meta</th>
				<th>Centro de Costo</th>
				<th>Año</th>
				<th>Estado</th>
				<th class="text-end">Acciones</th>
			</tr>
		</thead>
		<tbody id="tabla-requerimientos-body">
			<?php if (!empty($requerimientos)): ?>
				<?php foreach ($requerimientos as $req): ?>
					<tr
						data-id="<?php echo (int) $req['Id']; ?>"
						data-id-centro-costo="<?php echo (int) $req['IdCentroCosto']; ?>"
						data-id-sub-centro-costo="<?php echo (int) ($req['IdSubCentroCosto'] ?? 0); ?>"
						data-id-meta-siaf="<?php echo isset($req['IdMetaSIAF']) && (int) $req['IdMetaSIAF'] > 0 ? (int) $req['IdMetaSIAF'] : ''; ?>"
						data-nro-pedido="<?php echo htmlspecialchars((string) $req['NroPedidoCompra'], ENT_QUOTES, 'UTF-8'); ?>"
						data-codigo-meta="<?php echo htmlspecialchars((string) ($req['CodigoMeta'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
						data-anio="<?php echo (int) $req['Anio']; ?>">
						<td><?php echo htmlspecialchars($req['NroPedidoCompra']); ?></td>
						<td><?php echo htmlspecialchars((string) ($req['CodigoMeta'] ?? '')); ?></td>
						<td>
							<div>
								<?php
								$textoCentroCosto = trim((string) ($req['Siglas'] ?? ''));
								$nombreCentroCosto = trim((string) ($req['NombreCentroCosto'] ?? ''));
								echo htmlspecialchars($textoCentroCosto !== '' ? ($textoCentroCosto . ' - ' . $nombreCentroCosto) : $nombreCentroCosto);
								?>
							</div>
							<?php if (!empty($req['NombreSubCentroCosto'])): ?>
								<div class="text-secondary small">
									<?php
									$textoSubCentroCosto = trim((string) ($req['SiglasSubCentroCosto'] ?? ''));
									$nombreSubCentroCosto = trim((string) ($req['NombreSubCentroCosto'] ?? ''));
									echo htmlspecialchars($textoSubCentroCosto !== '' ? ($textoSubCentroCosto . ' - ' . $nombreSubCentroCosto) : $nombreSubCentroCosto);
									?>
								</div>
							<?php endif; ?>
						</td>
						<td><?php echo (int) $req['Anio']; ?></td>
						<td>
							<?php if ((int) $req['Estado'] === 1): ?>
								<span class="badge bg-success-lt">Completo</span>
							<?php else: ?>
								<span class="badge bg-warning-lt text-dark">Pendiente</span>
							<?php endif; ?>
						</td>
						<td class="text-end align-middle">
							<div class="btn-group" role="group">
								<!-- Editar -->
								<button type="button"
									class="btn btn-icon btn-lg"
									title="Editar"
									onclick="editarRequerimiento(<?= (int)$req['Id'] ?>)">
									<i class="ti ti-edit fs-2"></i>
								</button>
								<!-- Detalles -->
								<button type="button"
									class="btn btn-icon btn-lg"
									title="Detalles"
									onclick="detalleRequerimiento(<?= (int)$req['Id'] ?>)">
									<i class="ti ti-list-details fs-2"></i>
								</button>
								<!-- Eliminar -->
								<button type="button"
									class="btn btn-icon btn-lg text-danger"
									title="Eliminar"
									onclick="eliminarRequerimiento(<?= (int)$req['Id'] ?>)">
									<i class="ti ti-trash fs-2"></i>
								</button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="6" class="text-center text-secondary">No hay requerimientos registrados.</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<!-- Modal Nuevo Requerimiento -->
<div class="modal modal-blur fade" id="modal-requerimiento" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Nuevo Requerimiento</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="form-requerimiento" method="post" action="index.php?module=adquisiciones&action=guardarForm">
				<input type="hidden" name="Id" id="IdRequerimiento" value="">
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Centro de Costo</label>
						<select name="IdCentroCosto" id="IdCentroCosto" class="form-select" required>
							<option value="">Seleccione...</option>
							<?php foreach ($centrosCosto as $cc): ?>
								<option value="<?php echo $cc['Id']; ?>">
									<?php echo htmlspecialchars($cc['Siglas'] . ' - ' . $cc['NombreCentroCosto']); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label">Sub-Centro de Costo</label>
						<select name="IdSubCentroCosto" id="IdSubCentroCosto" class="form-select">
							<option value="">Seleccione un centro de costo primero...</option>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label">Meta SIAF</label>
						<select name="IdMetaSIAF" id="IdMetaSIAF" class="form-select">
							<option value="">Seleccione...</option>
							<?php foreach ($metasSiafActivasLista as $meta): ?>
								<option value="<?php echo (int) $meta['Id']; ?>">
									<?php echo htmlspecialchars((string) $meta['CodigoMeta'] . ' - ' . (string) $meta['Descripcion']); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label">Nro. de Pedido de Compra</label>
						<input type="text" name="NroPedidoCompra" id="NroPedidoCompra" class="form-control" placeholder="000000" required maxlength="10">
					</div>
					<div class="mb-3">
						<label class="form-label">Código Meta</label>
						<input type="text" name="CodigoMeta" id="CodigoMeta" class="form-control" placeholder="0000" maxlength="4" pattern="[A-Za-z0-9]{0,4}" autocomplete="off">
					</div>
					<div class="mb-3">
						<label class="form-label">Año</label>
						<input type="text" name="Anio" id="Anio" class="form-control" value="<?php echo date('Y'); ?>" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" placeholder="Año" autocomplete="off" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" id="btnGuardarRequerimiento">Guardar Requerimiento</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Importar de SIGA -->
<div class="modal modal-blur fade" id="modal-importar-siga" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Importar desde SIGA</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<!-- Buscador -->
				<div id="siga-busqueda" class="row g-2 mb-3">
					<div class="col-12 col-sm">
						<input type="text" id="anio-importar" class="form-control"
							value="<?php echo date('Y'); ?>" inputmode="numeric" pattern="[0-9]*"
							maxlength="4" placeholder="Año" autocomplete="off">
					</div>
					<div class="col-12 col-sm-auto d-grid">
						<button type="button" class="btn btn-primary" id="btn-buscar-siga">
							Buscar
						</button>
					</div>
				</div>

				<!-- Tabla de resultados -->
				<div id="siga-resultados" class="table-responsive" style="display:none;">
					<table class="table table-vcenter table-striped text-nowrap mb-0">
						<thead>
							<tr>
								<th>Nro. Pedido</th>
								<th>Centro de Costo</th>
								<th>Fecha</th>
								<th class="text-center">Ítems</th>
								<th class="text-end">Estado</th>
							</tr>
						</thead>
						<tbody id="siga-tbody"></tbody>
					</table>
				</div>

				<!-- Sin resultados -->
				<div id="siga-sin-resultados" class="text-center text-secondary py-3" style="display:none;">
					No se encontraron pedidos para el año seleccionado.
				</div>

				<!-- Loading -->
				<div id="siga-loading" class="text-center py-3" style="display:none;">
					<span class="spinner-border spinner-border-sm me-2"></span> Buscando pedidos...
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
					Cerrar
				</button>
			</div>
		</div>
	</div>
</div>

<script>
	window.adqSubCentrosCostoData = <?php echo json_encode($subCentrosCostoLista, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

	function escapeHtml(texto) {
		return String(texto)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function asegurarEstadoVacioTablaRequerimientos() {
		const tbody = document.getElementById('tabla-requerimientos-body');
		if (!tbody) return;

		const filasDatos = tbody.querySelectorAll('tr[data-id]');
		if (filasDatos.length === 0) {
			tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary">No hay requerimientos registrados.</td></tr>';
		}
	}

	function construirNombreCentroCosto(centroCosto, subCentroCosto) {
		if (subCentroCosto) {
			return '<div>' + escapeHtml(centroCosto) + '</div><div class="text-secondary small">' + escapeHtml(subCentroCosto) + '</div>';
		}

		return escapeHtml(centroCosto);
	}

	function construirFilaRequerimiento(id, idCentroCosto, idSubCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, subCentroCosto, anio, estado) {
		const badgeEstado = parseInt(estado, 10) === 1 ?
			'<span class="badge bg-success-lt">Completo</span>' :
			'<span class="badge bg-warning-lt text-dark">Pendiente</span>';

		return [
			'<tr data-id="' + id + '" data-id-centro-costo="' + (idCentroCosto || '') + '" data-id-sub-centro-costo="' + (idSubCentroCosto || '') + '" data-id-meta-siaf="' + (idMetaSiaf || '') + '" data-nro-pedido="' + escapeHtml(nroPedido) + '" data-codigo-meta="' + escapeHtml(codigoMeta || '') + '" data-anio="' + parseInt(anio, 10) + '">',
				'<td>' + escapeHtml(nroPedido) + '</td>',
				'<td>' + escapeHtml(codigoMeta || '') + '</td>',
				'<td>' + construirNombreCentroCosto(centroCosto, subCentroCosto) + '</td>',
				'<td>' + parseInt(anio, 10) + '</td>',
				'<td>' + badgeEstado + '</td>',
				'<td class="text-end align-middle">',
				'<div class="btn-group" role="group">',
				// Editar
				'<button type="button" class="btn btn-icon btn-lg" title="Editar" onclick="editarRequerimiento(' + id + ')">',
				'<i class="ti ti-edit fs-2"></i>',
				'</button>',
				// Detalles
				'<button type="button" class="btn btn-icon btn-lg" title="Detalles" onclick="detalleRequerimiento(' + id + ')">',
				'<i class="ti ti-list-details fs-2"></i>',
				'</button>',
				// Eliminar
				'<button type="button" class="btn btn-icon btn-lg text-danger" title="Eliminar" onclick="eliminarRequerimiento(' + id + ')">',
				'<i class="ti ti-trash fs-2"></i>',
				'</button>',
				'</div>',
				'</td>',
			'</tr>'
		].join('');
	}

	function agregarFilaRequerimiento(id, idCentroCosto, idSubCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, subCentroCosto, anio, estado) {
		const tbody = document.getElementById('tabla-requerimientos-body');
		if (!tbody) return;

		const filaVacia = tbody.querySelector('tr td[colspan="6"]');
		if (filaVacia) {
			tbody.innerHTML = '';
		}

		tbody.insertAdjacentHTML('beforeend', construirFilaRequerimiento(id, idCentroCosto, idSubCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, subCentroCosto, anio, estado));
	}

	function actualizarFilaRequerimiento(id, idCentroCosto, idSubCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, subCentroCosto, anio, estado) {
		const tbody = document.getElementById('tabla-requerimientos-body');
		if (!tbody) return;

		const fila = tbody.querySelector('tr[data-id="' + id + '"]');
		const htmlFila = construirFilaRequerimiento(id, idCentroCosto, idSubCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, subCentroCosto, anio, estado);

		if (fila) {
			fila.outerHTML = htmlFila;
			return;
		}

		tbody.insertAdjacentHTML('beforeend', htmlFila);
	}

	function obtenerSubCentrosPorCentro(idCentroCosto) {
		const idCentro = parseInt(idCentroCosto, 10) || 0;
		const subCentrosCostoData = Array.isArray(window.adqSubCentrosCostoData) ? window.adqSubCentrosCostoData : [];
		return subCentrosCostoData.filter(function(item) {
			return parseInt(item.IdCentroCosto, 10) === idCentro;
		});
	}

	function poblarSubCentrosCosto(idCentroCosto, idSeleccionado) {
		const selectSubCentro = document.getElementById('IdSubCentroCosto');
		if (!selectSubCentro) return;

		const subCentros = obtenerSubCentrosPorCentro(idCentroCosto);
		selectSubCentro.innerHTML = '';

		if (!idCentroCosto) {
			selectSubCentro.innerHTML = '<option value="">Seleccione un centro de costo primero...</option>';
			selectSubCentro.value = '';
			return;
		}

		selectSubCentro.insertAdjacentHTML('beforeend', '<option value="">Seleccione...</option>');

		subCentros.forEach(function(item) {
			const option = document.createElement('option');
			option.value = item.Id;
			option.textContent = [item.Siglas, item.NombreSubCentroCosto].filter(Boolean).join(' - ');
			if (String(item.Id) === String(idSeleccionado || '')) {
				option.selected = true;
			}
			selectSubCentro.appendChild(option);
		});
	}

	function nuevoRequerimiento() {
		const form = document.getElementById('form-requerimiento');
		if (form) {
			form.reset();
		}

		const idRequerimientoEl = document.getElementById('IdRequerimiento');
		if (idRequerimientoEl) {
			idRequerimientoEl.value = '';
		}

		const titulo = document.querySelector('#modal-requerimiento .modal-title');
		if (titulo) {
			titulo.textContent = 'Nuevo Requerimiento';
		}

		const btnGuardar = document.getElementById('btnGuardarRequerimiento');
		if (btnGuardar) {
			btnGuardar.textContent = 'Guardar Requerimiento';
		}

		const inputAnio = document.getElementById('Anio');
		const idCentroCostoEl = document.getElementById('IdCentroCosto');
		if (inputAnio) {
			inputAnio.value = String(new Date().getFullYear());
		}
		if (idCentroCostoEl) {
			idCentroCostoEl.value = '';
		}
		poblarSubCentrosCosto('', '');

		const modalEl = document.getElementById('modal-requerimiento');
		if (modalEl) {
			new bootstrap.Modal(modalEl).show();
		}
	}

	function editarRequerimiento(id) {
		const fila = document.querySelector('tr[data-id="' + id + '"]');
		if (!fila) {
			return;
		}

		const idRequerimientoEl = document.getElementById('IdRequerimiento');
		const idCentroCostoEl = document.getElementById('IdCentroCosto');
		const idSubCentroCostoEl = document.getElementById('IdSubCentroCosto');
		const idMetaSiafEl = document.getElementById('IdMetaSIAF');
		const nroPedidoEl = document.getElementById('NroPedidoCompra');
		const codigoMetaEl = document.getElementById('CodigoMeta');
		const anioEl = document.getElementById('Anio');

		if (idRequerimientoEl) idRequerimientoEl.value = String(id);
		if (idCentroCostoEl) idCentroCostoEl.value = fila.dataset.idCentroCosto || '';
		poblarSubCentrosCosto(fila.dataset.idCentroCosto || '', fila.dataset.idSubCentroCosto || '');
		if (idSubCentroCostoEl) idSubCentroCostoEl.value = fila.dataset.idSubCentroCosto || '';
		if (idMetaSiafEl) idMetaSiafEl.value = fila.dataset.idMetaSiaf || '';
		if (nroPedidoEl) nroPedidoEl.value = fila.dataset.nroPedido || '';
		if (codigoMetaEl) codigoMetaEl.value = fila.dataset.codigoMeta || '';
		if (anioEl) anioEl.value = fila.dataset.anio || '';

		const titulo = document.querySelector('#modal-requerimiento .modal-title');
		if (titulo) {
			titulo.textContent = 'Editar Requerimiento';
		}

		const btnGuardar = document.getElementById('btnGuardarRequerimiento');
		if (btnGuardar) {
			btnGuardar.textContent = 'Actualizar Requerimiento';
		}

		const modalEl = document.getElementById('modal-requerimiento');
		if (modalEl) {
			bootstrap.Modal.getOrCreateInstance(modalEl).show();
		}
	}

	function filtrarPorAnio() {
		const filtro = document.getElementById('filtroAnio');
		const anio = filtro ? filtro.value : '';
		const url = 'index.php?module=adquisiciones&action=requerimientos' + (anio ? '&anio=' + anio : '');
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	function abrirModalImportar() {
		const resultados = document.getElementById('siga-resultados');
		const sinResultados = document.getElementById('siga-sin-resultados');
		const loading = document.getElementById('siga-loading');
		const tbody = document.getElementById('siga-tbody');
		if (resultados) resultados.style.display = 'none';
		if (sinResultados) sinResultados.style.display = 'none';
		if (loading) loading.style.display = 'none';
		if (tbody) tbody.innerHTML = '';

		const modalEl = document.getElementById('modal-importar-siga');
		if (modalEl) {
			bootstrap.Modal.getOrCreateInstance(modalEl).show();
		}
	}

	var debeRecargarRequerimientos = false;

	var modalImportarSiga = document.getElementById('modal-importar-siga');
	if (modalImportarSiga) {
		modalImportarSiga.addEventListener('hidden.bs.modal', function() {
			if (!debeRecargarRequerimientos) {
				return;
			}

			debeRecargarRequerimientos = false;
			filtrarPorAnio();
		});
	}

	var inputAnioImportar = document.getElementById('anio-importar');
	if (inputAnioImportar) {
		inputAnioImportar.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '').slice(0, 4);
		});
	}

	var inputAnioRequerimiento = document.getElementById('Anio');
	if (inputAnioRequerimiento) {
		inputAnioRequerimiento.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '').slice(0, 4);
		});
	}

	var inputCodigoMeta = document.getElementById('CodigoMeta');
	if (inputCodigoMeta) {
		inputCodigoMeta.addEventListener('input', function() {
			this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 4);
		});
	}

	var selectCentroCostoRequerimiento = document.getElementById('IdCentroCosto');
	if (selectCentroCostoRequerimiento) {
		selectCentroCostoRequerimiento.addEventListener('change', function() {
			poblarSubCentrosCosto(this.value, '');
		});
	}

	// Buscar pedidos en SIGA
	var btnBuscarSiga = document.getElementById('btn-buscar-siga');
	if (btnBuscarSiga) {
		btnBuscarSiga.addEventListener('click', function() {
			const anio = document.getElementById('anio-importar').value.trim();
			const btn = this;
			const resultados = document.getElementById('siga-resultados');
			const sinResultados = document.getElementById('siga-sin-resultados');
			const loading = document.getElementById('siga-loading');
			const tbody = document.getElementById('siga-tbody');

			if (!/^\d{1,4}$/.test(anio)) {
				window.adqNotifySafe('warning', 'Validacion', 'Ingrese solo numeros (maximo 4 digitos).');
				return;
			}

			btn.disabled = true;
			btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Buscando...';
			if (resultados) resultados.style.display = 'none';
			if (sinResultados) sinResultados.style.display = 'none';
			if (loading) loading.style.display = 'block';

			fetch('index.php?module=adquisiciones&action=buscarPedidosSigaAjax', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: new URLSearchParams({
						anio: anio
					}).toString()
				})
				.then(function(resp) {
					if (!resp.ok) {
						throw new Error('Error HTTP');
					}
					return resp.json();
				})
				.then(function(response) {
					btn.disabled = false;
					btn.innerHTML = 'Buscar';
					if (loading) loading.style.display = 'none';

					if (!response.success) {
						window.adqNotifySafe('danger', 'Error al consultar SIGA', response.message || 'No se pudo consultar SIGA.');
						return;
					}

					const pedidos = response.pedidos;

					if (pedidos.length === 0) {
						if (sinResultados) sinResultados.style.display = 'block';
						return;
					}

					if (!tbody) {
						return;
					}
					tbody.innerHTML = '';

					pedidos.forEach(function(p) {
						let accion = '';
						if (p.YA_IMPORTADO == 1) {
							accion = '<span class="badge bg-success-lt">Importado</span>';
						} else {
							accion = `<button class="badge bg-azure-lt" 
						onclick="importarPedido('${p.NRO_PEDIDO}', ${anio}, this)">
						Importar
					</button>`;
						}

						tbody.insertAdjacentHTML('beforeend', `
					<tr id="fila-${p.NRO_PEDIDO}">
						<td>${p.NRO_PEDIDO}</td>
						<td>${p.CENTRO_COSTO}</td>
						<td>${p.FECHA_PEDIDO}</td>
						<td class="text-center">${p.TOTAL_ITEMS}</td>
						<td class="text-end">${accion}</td>
					</tr>
				`);
					});

					if (resultados) resultados.style.display = 'block';
				})
				.catch(function() {
					btn.disabled = false;
					btn.innerHTML = 'Buscar';
					if (loading) loading.style.display = 'none';
					window.adqNotifySafe('danger', 'Error de conexion', 'Ocurrio un error al conectar con el servidor.');
				});
		});
	}

	// Importar un pedido individual
	function importarPedido(nroPedido, anio, btn) {
		btn.disabled = true;
		btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

		fetch('index.php?module=adquisiciones&action=importarPedidoSigaAjax', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: new URLSearchParams({
					nro_pedido: nroPedido,
					anio: anio
				}).toString()
			})
			.then(function(resp) {
				if (!resp.ok) {
					throw new Error('Error HTTP');
				}
				return resp.json();
			})
			.then(function(response) {
				if (response.success) {
					debeRecargarRequerimientos = true;
					// Reemplazar botón por badge
					const fila = document.getElementById('fila-' + nroPedido);
					if (fila && fila.lastElementChild) {
						fila.lastElementChild.innerHTML = '<span class="badge bg-success-lt">Importado</span>';
					}
				} else {
					btn.disabled = false;
					btn.innerHTML = 'Importar';
					window.adqNotifySafe('danger', 'Error al importar pedido', response.message || 'No se pudo importar el pedido.');
				}
			})
			.catch(function() {
				btn.disabled = false;
				btn.innerHTML = 'Importar';
				window.adqNotifySafe('danger', 'Error de conexion', 'Ocurrio un error al conectar con el servidor.');
			});
	}

	var formRequerimiento = document.getElementById('form-requerimiento');
	if (formRequerimiento) {
		formRequerimiento.addEventListener('submit', function(e) {
			e.preventDefault();

			const form = this;
			const idRequerimientoEl = document.getElementById('IdRequerimiento');
			const idCentroCostoEl = document.getElementById('IdCentroCosto');
			const idMetaSiafEl = document.getElementById('IdMetaSIAF');
			const nroPedidoEl = document.getElementById('NroPedidoCompra');
			const codigoMetaEl = document.getElementById('CodigoMeta');
			const anioEl = document.getElementById('Anio');

			const idRequerimiento = idRequerimientoEl ? idRequerimientoEl.value : '';
			const idCentroCosto = idCentroCostoEl ? idCentroCostoEl.value : '';
			const idSubCentroCostoEl = document.getElementById('IdSubCentroCosto');
			const idSubCentroCosto = idSubCentroCostoEl ? idSubCentroCostoEl.value : '';
			const idMetaSiaf = idMetaSiafEl ? idMetaSiafEl.value : '';
			const centroCostoTexto = idCentroCostoEl && idCentroCostoEl.selectedOptions.length > 0 ? idCentroCostoEl.selectedOptions[0].text.trim() : '';
			const subCentroCostoTexto = idSubCentroCosto ?
				(idSubCentroCostoEl && idSubCentroCostoEl.selectedOptions.length > 0 ? idSubCentroCostoEl.selectedOptions[0].text.trim() : '') :
				'';
			const nroPedido = nroPedidoEl ? nroPedidoEl.value : '';
			const codigoMeta = codigoMetaEl ? codigoMetaEl.value : '';
			const anio = anioEl ? anioEl.value : '';
			const action = idRequerimiento ? 'actualizarAjax' : 'guardarAjax';

			fetch('index.php?module=adquisiciones&action=' + action, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: new URLSearchParams(new FormData(form)).toString()
				})
				.then(function(resp) {
					if (!resp.ok) {
						throw new Error('Error HTTP');
					}
					return resp.json();
				})
				.then(function(response) {
					if (response.success) {
						const modal = bootstrap.Modal.getInstance(document.getElementById('modal-requerimiento'));
						if (modal) modal.hide();

						if (idRequerimiento) {
							const filaActual = document.querySelector('tr[data-id="' + idRequerimiento + '"]');
							const estadoActual = filaActual ? (filaActual.querySelector('.bg-success-lt') ? 1 : 0) : 0;
							actualizarFilaRequerimiento(idRequerimiento, idCentroCosto, idSubCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCostoTexto, subCentroCostoTexto, anio, estadoActual);
						} else {
							agregarFilaRequerimiento(response.id, idCentroCosto, idSubCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCostoTexto, subCentroCostoTexto, anio, 0);
						}

						form.reset();
						if (idRequerimientoEl) idRequerimientoEl.value = '';
						if (anioEl) anioEl.value = String(new Date().getFullYear());
						if (idCentroCostoEl) idCentroCostoEl.value = idCentroCosto;
						poblarSubCentrosCosto(idCentroCosto, '');

						const titulo = document.querySelector('#modal-requerimiento .modal-title');
						if (titulo) titulo.textContent = 'Nuevo Requerimiento';
						const btnGuardar = document.getElementById('btnGuardarRequerimiento');
						if (btnGuardar) btnGuardar.textContent = 'Guardar Requerimiento';
					} else {
						window.adqNotifySafe('danger', 'No se pudo guardar', response.message || 'No se pudo guardar el requerimiento.');
					}
				})
				.catch(function() {
					window.adqNotifySafe('danger', 'Error de solicitud', 'Ocurrio un error al procesar la solicitud.');
				});
		});
	}

	function detalleRequerimiento(id) {
		const url = 'index.php?module=adquisiciones&action=requerimiento&id=' + id;
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	async function eliminarRequerimiento(id) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: 'Se eliminara el requerimiento y todos sus detalles.',
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		fetch('index.php?module=adquisiciones&action=eliminarAjax', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: new URLSearchParams({
					id: id
				}).toString()
			})
			.then(function(resp) {
				if (!resp.ok) {
					throw new Error('Error HTTP');
				}
				return resp.json();
			})
			.then(function(response) {
				if (response.success) {
					const fila = document.querySelector('tr[data-id="' + id + '"]');
					if (fila) {
						fila.remove();
						asegurarEstadoVacioTablaRequerimientos();
					}
				} else {
					window.adqNotifySafe('danger', 'No se pudo eliminar', response.message || 'No se pudo eliminar el requerimiento.');
				}
			})
			.catch(function() {
				window.adqNotifySafe('danger', 'Error de solicitud', 'Ocurrio un error al procesar la solicitud.');
			});
	}
</script>