<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
	<div class="d-flex gap-2 align-items-center flex-wrap">
		<label class="form-label mb-0 text-nowrap">Filtrar por año:</label>
		<select id="filtroAnio" class="form-select w-auto" onchange="filtrarPorAnio()">
			<?php foreach ($aniosDisponibles as $anio): ?>
				<option value="<?php echo $anio; ?>" <?php echo ($anioFiltro == $anio) ? 'selected' : ''; ?>>
					<?php echo $anio; ?>
				</option>
			<?php endforeach; ?>
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
				<th>Centro de Costo</th>
				<th>Año</th>
				<th>Estado</th>
				<th class="text-end">Acciones</th>
			</tr>
		</thead>
		<tbody id="tabla-requerimientos-body">
			<?php if (!empty($requerimientos)): ?>
				<?php foreach ($requerimientos as $req): ?>
					<tr data-id="<?php echo (int) $req['Id']; ?>">
						<td><?php echo htmlspecialchars($req['NroPedidoCompra']); ?></td>
						<td><?php echo htmlspecialchars($req['NombreCentroCosto']); ?></td>
						<td><?php echo (int) $req['Anio']; ?></td>
						<td>
							<?php if ((int) $req['Estado'] === 1): ?>
								<span class="badge bg-success-lt">Completo</span>
							<?php else: ?>
								<span class="badge bg-warning-lt text-dark">Pendiente</span>
							<?php endif; ?>
						</td>
						<td class="text-end">
							<div class="d-inline-flex gap-2 align-items-center justify-content-end w-100">
								<button class="btn btn-azure-lt" type="button" onclick="detalleRequerimiento(<?php echo (int) $req['Id']; ?>)">
									Detalles
								</button>
								<button class="btn btn-red-lt" type="button" onclick="eliminarRequerimiento(<?php echo (int) $req['Id']; ?>)">
									Eliminar
								</button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="5" class="text-center text-secondary">No hay requerimientos registrados.</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<!-- Modal Confirmar Eliminacion -->
<div class="modal modal-blur fade" id="modal-eliminar-requerimiento" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-body text-center py-4">
				<h3>Confirmar eliminacion</h3>
				<div class="text-secondary">Se eliminara el requerimiento y todos sus detalles.</div>
			</div>
			<div class="modal-footer">
				<div class="w-100">
					<div class="row">
						<div class="col">
							<button type="button" class="btn btn-ghost-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
						</div>
						<div class="col">
							<button type="button" id="btn-confirmar-eliminar" class="btn btn-danger w-100">Eliminar</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal Nuevo Requerimiento -->
<div class="modal modal-blur fade" id="modal-requerimiento" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Nuevo Requerimiento</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="form-requerimiento">
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
						<label class="form-label">Nro. de Pedido de Compra</label>
						<input type="text" name="NroPedidoCompra" id="NroPedidoCompra" class="form-control" placeholder="000000" required maxlength="10">
					</div>
					<div class="mb-3">
						<label class="form-label">Año</label>
						<input type="text" name="Anio" id="Anio" class="form-control" value="<?php echo date('Y'); ?>" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" placeholder="Año" autocomplete="off" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary">Guardar Requerimiento</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Importar de SIGA -->
<div class="modal modal-blur fade" id="modal-importar-siga" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Importar desde SIGA</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<style>
					#siga-busqueda {
						display: flex;
						gap: 0.5rem;
						width: 100%;
					}

					#anio-importar {
						flex: 1 1 auto;
						min-width: 0;
					}

					#btn-buscar-siga {
						white-space: nowrap;
					}
				</style>

				<!-- Buscador -->
				<div id="siga-busqueda" class="mb-3">
					<input type="text" id="anio-importar" class="form-control"
						value="<?php echo date('Y'); ?>" inputmode="numeric" pattern="[0-9]*"
						maxlength="4" placeholder="Año" autocomplete="off">
					<button type="button" class="btn btn-primary" id="btn-buscar-siga">
						Buscar
					</button>
				</div>

				<!-- Tabla de resultados -->
				<div id="siga-resultados" style="display:none;">
					<table class="table table-vcenter table-striped">
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
	let idRequerimientoAEliminar = null;

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
			tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary">No hay requerimientos registrados.</td></tr>';
		}
	}

	function agregarFilaRequerimiento(id, nroPedido, centroCosto, anio, estado) {
		const tbody = document.getElementById('tabla-requerimientos-body');
		if (!tbody) return;

		const filaVacia = tbody.querySelector('tr td[colspan="5"]');
		if (filaVacia) {
			tbody.innerHTML = '';
		}

		const badgeEstado = parseInt(estado, 10) === 1 ?
			'<span class="badge bg-success-lt">Completo</span>' :
			'<span class="badge bg-warning-lt text-dark">Pendiente</span>';

		const rowHtml = [
			'<tr data-id="' + id + '">',
				'<td>' + escapeHtml(nroPedido) + '</td>',
				'<td>' + escapeHtml(centroCosto) + '</td>',
				'<td>' + parseInt(anio, 10) + '</td>',
				'<td>' + badgeEstado + '</td>',
				'<td class="text-end">',
					'<div class="d-inline-flex gap-2 align-items-center justify-content-end w-100">',
						'<button class="btn btn-azure-lt" type="button" onclick="detalleRequerimiento(' + id + ')">Detalles</button>',
						'<button class="btn btn-red-lt" type="button" onclick="eliminarRequerimiento(' + id + ')">Eliminar</button>',
					'</div>',
				'</td>',
			'</tr>'
		].join('');

		tbody.insertAdjacentHTML('beforeend', rowHtml);
	}

	function nuevoRequerimiento() {
		const form = document.getElementById('form-requerimiento');
		if (form) {
			form.reset();
		}

		const inputAnio = document.getElementById('Anio');
		if (inputAnio) {
			inputAnio.value = String(new Date().getFullYear());
		}

		const modalEl = document.getElementById('modal-requerimiento');
		if (modalEl) {
			new bootstrap.Modal(modalEl).show();
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

	const inputAnioImportar = document.getElementById('anio-importar');
	if (inputAnioImportar) {
		inputAnioImportar.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '').slice(0, 4);
		});
	}

	const inputAnioRequerimiento = document.getElementById('Anio');
	if (inputAnioRequerimiento) {
		inputAnioRequerimiento.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '').slice(0, 4);
		});
	}

	// Buscar pedidos en SIGA
	const btnBuscarSiga = document.getElementById('btn-buscar-siga');
	if (btnBuscarSiga) {
		btnBuscarSiga.addEventListener('click', function() {
		const anio = document.getElementById('anio-importar').value.trim();
		const btn = this;
		const resultados = document.getElementById('siga-resultados');
		const sinResultados = document.getElementById('siga-sin-resultados');
		const loading = document.getElementById('siga-loading');
		const tbody = document.getElementById('siga-tbody');

		if (!/^\d{1,4}$/.test(anio)) {
			alert('Ingrese solo números (máximo 4 dígitos).');
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
			body: new URLSearchParams({ anio: anio }).toString()
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
					alert('Error: ' + (response.message || 'No se pudo consultar SIGA.'));
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
				alert('Ocurrió un error al conectar con el servidor.');
			},
		);
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
			body: new URLSearchParams({ nro_pedido: nroPedido, anio: anio }).toString()
		})
			.then(function(resp) {
				if (!resp.ok) {
					throw new Error('Error HTTP');
				}
				return resp.json();
			})
			.then(function(response) {
				if (response.success) {
					// Reemplazar botón por badge
					const fila = document.getElementById('fila-' + nroPedido);
					if (fila && fila.lastElementChild) {
						fila.lastElementChild.innerHTML = '<span class="badge bg-success-lt">Importado</span>';
					}
				} else {
					btn.disabled = false;
					btn.innerHTML = 'Importar';
					alert('Error: ' + (response.message || 'No se pudo importar el pedido.'));
				}
			})
			.catch(function() {
				btn.disabled = false;
				btn.innerHTML = 'Importar';
				alert('Ocurrió un error al conectar con el servidor.');
			});
	}

	const formRequerimiento = document.getElementById('form-requerimiento');
	if (formRequerimiento) {
		formRequerimiento.addEventListener('submit', function(e) {
			e.preventDefault();

			const form = this;
			const idCentroCostoEl = document.getElementById('IdCentroCosto');
			const nroPedidoEl = document.getElementById('NroPedidoCompra');
			const anioEl = document.getElementById('Anio');

			const idCentroCosto = idCentroCostoEl ? idCentroCostoEl.value : '';
			const centroCostoTexto = idCentroCostoEl && idCentroCostoEl.selectedOptions.length > 0 ? idCentroCostoEl.selectedOptions[0].text.trim() : '';
			const nroPedido = nroPedidoEl ? nroPedidoEl.value : '';
			const anio = anioEl ? anioEl.value : '';

			fetch('index.php?module=adquisiciones&action=guardarAjax', {
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

					agregarFilaRequerimiento(response.id, nroPedido, centroCostoTexto, anio, 0);
					form.reset();
					if (anioEl) anioEl.value = String(new Date().getFullYear());
					if (idCentroCostoEl) idCentroCostoEl.value = idCentroCosto;
				} else {
					alert(response.message || 'No se pudo guardar el requerimiento.');
				}
				})
				.catch(function() {
				alert('Ocurrio un error al procesar la solicitud.');
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

	function eliminarRequerimiento(id) {
		idRequerimientoAEliminar = id;
		bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-eliminar-requerimiento')).show();
	}

	const btnConfirmarEliminar = document.getElementById('btn-confirmar-eliminar');
	if (btnConfirmarEliminar) {
		btnConfirmarEliminar.addEventListener('click', function() {
			if (!idRequerimientoAEliminar) return;

			fetch('index.php?module=adquisiciones&action=eliminarAjax', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: new URLSearchParams({ id: idRequerimientoAEliminar }).toString()
			})
				.then(function(resp) {
					if (!resp.ok) {
						throw new Error('Error HTTP');
					}
					return resp.json();
				})
				.then(function(response) {
				if (response.success) {
					const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-eliminar-requerimiento'));
					modal.hide();

					const fila = document.querySelector('tr[data-id="' + idRequerimientoAEliminar + '"]');
					if (fila) {
						fila.remove();
						asegurarEstadoVacioTablaRequerimientos();
					}

					idRequerimientoAEliminar = null;
				} else {
					alert(response.message || 'No se pudo eliminar el requerimiento.');
				}
				})
				.catch(function() {
				alert('Ocurrio un error al procesar la solicitud.');
				});
		});
	}
</script>