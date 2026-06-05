<?php
$metasSiafActivasLista = (isset($metasSiafActivas) && is_array($metasSiafActivas)) ? $metasSiafActivas : [];
$totalRequerimientosVista = (isset($requerimientos) && is_array($requerimientos)) ? count($requerimientos) : 0;
?>

<style>
	#requerimientos-table .table > :not(caption) > * > * {
		padding-top: 1rem;
		padding-bottom: 1rem;
	}

	#requerimientos-table .btn-group .btn-icon {
		min-width: 2.25rem;
		min-height: 2.25rem;
	}

	#requerimientos-table .table-sort {
		display: inline-flex !important;
		align-items: center;
		width: auto;
		gap: 0.35rem;
	}

	#requerimientos-table .table-sort::after {
		margin-left: 0;
	}
</style>

<!-- Tabla avanzada de requerimientos con busqueda, ordenamiento y paginacion -->
<div class="col-12">
	<div class="card">
		<div class="card-table">
			<div class="card-header">
				<div class="row w-full g-2 align-items-center">
					<div class="col">
						<h3 class="card-title mb-0">Requerimientos</h3>
					</div>
					<div class="col-md-auto col-sm-12">
						<div class="ms-auto d-flex flex-wrap btn-list">
							<div class="input-group input-group-flat w-auto">
								<span class="input-group-text">
									<i class="ti ti-search"></i>
								</span>
								<input id="requerimientos-table-search" type="text" class="form-control" placeholder="Buscar" autocomplete="off">
							</div>
							<div class="d-flex gap-2 align-items-center">
								<label class="form-label mb-0 text-nowrap">Año:</label>
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
							<!--button type="button" class="btn btn-success" onclick="abrirModalImportar()">
								Importar de SIGA
							</button-->
							<button type="button" class="btn btn-primary" onclick="nuevoRequerimiento()">
								Agregar Requerimiento
							</button>
						</div>
					</div>
				</div>
			</div>
			<div id="requerimientos-table">
				<div class="table-responsive">
					<table class="table table-vcenter table-selectable">
						<thead>
							<tr>
								<th>
									<button class="table-sort" data-sort="sort-pedido">Nro. de Pedido</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-meta">Código Meta</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-centro">Centro de Costo</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-anio">Año</button>
								</th>
								<th>
									<button class="table-sort" data-sort="sort-estado">Estado</button>
								</th>
								<th class="text-end">Acciones</th>
							</tr>
						</thead>
						<tbody id="tabla-requerimientos-body" class="table-tbody">
							<?php if (!empty($requerimientos)): ?>
								<?php foreach ($requerimientos as $req): ?>
									<?php
									$textoCentroCosto = trim((string) ($req['Siglas'] ?? ''));
									$nombreCentroCosto = trim((string) ($req['NombreCentroCosto'] ?? ''));
									$centroCostoCompleto = $textoCentroCosto !== '' ? ($textoCentroCosto . ' - ' . $nombreCentroCosto) : $nombreCentroCosto;
									?>
									<tr
										data-id="<?php echo (int) $req['Id']; ?>"
										data-id-centro-costo="<?php echo (int) $req['IdCentroCosto']; ?>"
										data-id-meta-siaf="<?php echo isset($req['IdMetaSIAF']) && (int) $req['IdMetaSIAF'] > 0 ? (int) $req['IdMetaSIAF'] : ''; ?>"
										data-nro-pedido="<?php echo htmlspecialchars((string) $req['NroPedidoCompra'], ENT_QUOTES, 'UTF-8'); ?>"
										data-codigo-meta="<?php echo htmlspecialchars((string) ($req['CodigoMeta'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
										data-anio="<?php echo (int) $req['Anio']; ?>">
										<td class="sort-pedido"><?php echo htmlspecialchars($req['NroPedidoCompra']); ?></td>
										<td class="sort-meta"><?php echo htmlspecialchars((string) ($req['CodigoMeta'] ?? '')); ?></td>
										<td class="sort-centro"><?php echo htmlspecialchars($centroCostoCompleto); ?></td>
										<td class="sort-anio"><?php echo (int) $req['Anio']; ?></td>
										<td class="sort-estado">
											<?php if ((int) $req['Estado'] === 1): ?>
												<span class="badge bg-success-lt">Completo</span>
											<?php else: ?>
												<span class="badge bg-warning-lt text-dark">Pendiente</span>
											<?php endif; ?>
										</td>
										<td class="py-0 text-end align-middle">
											<div class="btn-group" role="group">
												<button type="button"
													class="btn btn-icon btn-lg"
													title="Editar"
													onclick="editarRequerimiento(<?= (int)$req['Id'] ?>)">
													<i class="ti ti-edit fs-2"></i>
												</button>
												<button type="button"
													class="btn btn-icon btn-lg"
													title="Detalles"
													onclick="detalleRequerimiento(<?= (int)$req['Id'] ?>)">
													<i class="ti ti-list-details fs-2"></i>
												</button>
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
								<tr data-empty-row="true">
									<td colspan="6" class="text-center text-secondary">No hay requerimientos registrados.</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				<div id="requerimientos-table-footer" class="card-footer d-flex align-items-center" <?php echo $totalRequerimientosVista === 0 ? 'style="display:none !important;"' : ''; ?>>
					<div class="dropdown">
						<a class="btn dropdown-toggle" data-bs-toggle="dropdown">
							<span id="requerimientos-page-count" class="me-1">10</span>
							<span>registros</span>
						</a>
						<div class="dropdown-menu">
							<a class="dropdown-item" onclick="setRequerimientosPageListItems(event)" data-value="10">10 registros</a>
							<a class="dropdown-item" onclick="setRequerimientosPageListItems(event)" data-value="20">20 registros</a>
							<a class="dropdown-item" onclick="setRequerimientosPageListItems(event)" data-value="50">50 registros</a>
						</div>
					</div>
					<ul class="pagination m-0 ms-auto"></ul>
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
			<form id="form-requerimiento" method="post" action="<?php echo htmlspecialchars(($_SERVER['SCRIPT_NAME'] ?? 'index.php') . '?module=adquisiciones&action=guardarForm', ENT_QUOTES, 'UTF-8'); ?>">
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
						<label class="form-label">Meta SIGA</label>
						<input type="text" name="CodigoMeta" id="CodigoMeta" class="form-control" placeholder="0000" maxlength="4" pattern="[A-Za-z0-9]{0,4}">
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
	const ADQ_INDEX_URL = <?php echo json_encode($_SERVER['SCRIPT_NAME'] ?? 'index.php'); ?>;
	const ADQ_AJAX_URL = <?php
		$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
		$scriptDir = $scriptDir === '/' || $scriptDir === '.' ? '' : rtrim($scriptDir, '/');
		echo json_encode($scriptDir . '/modules/adquisiciones/ajax.php');
	?>;

	function urlAdquisiciones(action, params) {
		const url = new URL(ADQ_INDEX_URL, window.location.origin);
		url.searchParams.set('module', 'adquisiciones');
		url.searchParams.set('action', action);

		Object.keys(params || {}).forEach(function(key) {
			const value = params[key];
			if (value !== undefined && value !== null && value !== '') {
				url.searchParams.set(key, value);
			}
		});

		return url.pathname + url.search;
	}

	function urlAdquisicionesAjax(action, params) {
		const url = new URL(ADQ_AJAX_URL, window.location.origin);
		url.searchParams.set('action', action);

		Object.keys(params || {}).forEach(function(key) {
			const value = params[key];
			if (value !== undefined && value !== null && value !== '') {
				url.searchParams.set(key, value);
			}
		});

		return url.pathname + url.search;
	}

	// Lee respuestas AJAX y reporta cuando el servidor no devuelve JSON valido.
	function leerRespuestaJson(resp) {
		return resp.text().then(function(texto) {
			if (!resp.ok) {
				throw new Error('Error HTTP ' + resp.status);
			}

			try {
				return JSON.parse(texto);
			} catch (error) {
				const detalle = texto.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
				throw new Error(detalle ? detalle.slice(0, 220) : 'Respuesta no valida del servidor.');
			}
		});
	}

	// Codifica caracteres especiales de HTML para prevenir inyecciones XSS
	function escapeHtml(texto) {
		return String(texto)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function obtenerCantidadFilasRequerimientos() {
		const tbody = document.getElementById('tabla-requerimientos-body');
		return tbody ? tbody.querySelectorAll('tr[data-id]').length : 0;
	}

	function actualizarFooterTablaRequerimientos() {
		const footer = document.getElementById('requerimientos-table-footer');
		if (!footer) return;

		if (obtenerCantidadFilasRequerimientos() === 0) {
			footer.style.setProperty('display', 'none', 'important');
			return;
		}

		footer.style.removeProperty('display');
	}

	function cargarListJsRequerimientos(callback) {
		if (typeof List === 'function') {
			callback();
			return;
		}

		const scriptExistente = document.querySelector('script[data-requerimientos-listjs="true"]');
		if (scriptExistente) {
			scriptExistente.addEventListener('load', callback, { once: true });
			return;
		}

		const script = document.createElement('script');
		script.src = 'https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js';
		script.defer = true;
		script.dataset.requerimientosListjs = 'true';
		script.addEventListener('load', callback, { once: true });
		document.head.appendChild(script);
	}

	function inicializarTablaRequerimientos() {
		const contenedor = document.getElementById('requerimientos-table');
		if (!contenedor) return;

		actualizarFooterTablaRequerimientos();

		if (obtenerCantidadFilasRequerimientos() === 0) {
			contenedor.dataset.listInitialized = '';
			if (window.tabler_list) {
				window.tabler_list['requerimientos-table'] = null;
			}
			return;
		}

		if (contenedor.dataset.listInitialized === '1' && window.tabler_list && window.tabler_list['requerimientos-table']) {
			actualizarTablaRequerimientosList();
			return;
		}

		if (typeof List !== 'function') return;

		window.tabler_list = window.tabler_list || {};
		try {
			window.tabler_list['requerimientos-table'] = new List('requerimientos-table', {
				sortClass: 'table-sort',
				listClass: 'table-tbody',
				page: 10,
				pagination: {
					item: function(value) {
						return '<li class="page-item"><a class="page-link cursor-pointer">' + value.page + '</a></li>';
					},
					innerWindow: 1,
					outerWindow: 1,
					left: 0,
					right: 0
				},
				valueNames: ['sort-pedido', 'sort-meta', 'sort-centro', 'sort-anio', 'sort-estado']
			});
		} catch (error) {
			console.warn('No se pudo inicializar la tabla de requerimientos.', error);
			return;
		}
		contenedor.dataset.listInitialized = '1';

		const searchInput = document.getElementById('requerimientos-table-search');
		if (searchInput && searchInput.dataset.searchBound !== '1') {
			searchInput.addEventListener('input', function() {
				const list = window.tabler_list && window.tabler_list['requerimientos-table'];
				if (list) {
					list.search(searchInput.value);
				}
			});
			searchInput.dataset.searchBound = '1';
		}
	}

	function actualizarTablaRequerimientosList() {
		actualizarFooterTablaRequerimientos();

		if (obtenerCantidadFilasRequerimientos() === 0) {
			const contenedor = document.getElementById('requerimientos-table');
			if (contenedor) {
				contenedor.dataset.listInitialized = '';
			}
			if (window.tabler_list) {
				window.tabler_list['requerimientos-table'] = null;
			}
			return;
		}

		const list = window.tabler_list && window.tabler_list['requerimientos-table'];
		if (!list) {
			cargarListJsRequerimientos(inicializarTablaRequerimientos);
			return;
		}

		if (typeof list.reIndex === 'function') {
			list.reIndex();
		}

		const searchInput = document.getElementById('requerimientos-table-search');
		if (searchInput && typeof list.search === 'function') {
			list.search(searchInput.value);
		}

		if (typeof list.update === 'function') {
			list.update();
		}
	}

	function setRequerimientosPageListItems(e) {
		e.preventDefault();
		const list = window.tabler_list && window.tabler_list['requerimientos-table'];
		if (!list) return;

		list.page = parseInt(e.target.dataset.value, 10);
		list.update();

		const pageCount = document.getElementById('requerimientos-page-count');
		if (pageCount) {
			pageCount.innerHTML = e.target.dataset.value;
		}
	}

	function enfocarBusquedaRequerimientos(event) {
		if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'k') {
			return;
		}

		const searchInput = document.getElementById('requerimientos-table-search');
		if (!searchInput) return;

		event.preventDefault();
		searchInput.focus();
	}

	if (!window.requerimientosTableShortcutBound) {
		document.addEventListener('keydown', enfocarBusquedaRequerimientos);
		window.requerimientosTableShortcutBound = true;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			cargarListJsRequerimientos(inicializarTablaRequerimientos);
		}, { once: true });
	} else {
		cargarListJsRequerimientos(inicializarTablaRequerimientos);
	}

	// Verifica si la tabla está vacía y muestra mensaje correspondiente
	function asegurarEstadoVacioTablaRequerimientos() {
		const tbody = document.getElementById('tabla-requerimientos-body');
		if (!tbody) return;

		const filasDatos = tbody.querySelectorAll('tr[data-id]');
		if (filasDatos.length === 0) {
			tbody.innerHTML = '<tr data-empty-row="true"><td colspan="6" class="text-center text-secondary">No hay requerimientos registrados.</td></tr>';
		}

		actualizarTablaRequerimientosList();
	}

	// Genera el HTML de una fila de tabla con todos los datos del requerimiento
	function construirFilaRequerimiento(id, idCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, anio, estado) {
		const badgeEstado = parseInt(estado, 10) === 1 ?
			'<span class="badge bg-success-lt">Completo</span>' :
			'<span class="badge bg-warning-lt text-dark">Pendiente</span>';
		const centroCostoTexto = String(centroCosto || '').trim();

		return [
			'<tr data-id="' + id + '" data-id-centro-costo="' + (idCentroCosto || '') + '" data-id-meta-siaf="' + (idMetaSiaf || '') + '" data-nro-pedido="' + escapeHtml(nroPedido) + '" data-codigo-meta="' + escapeHtml(codigoMeta || '') + '" data-anio="' + parseInt(anio, 10) + '">',
				'<td class="sort-pedido">' + escapeHtml(nroPedido) + '</td>',
				'<td class="sort-meta">' + escapeHtml(codigoMeta || '') + '</td>',
				'<td class="sort-centro">' + escapeHtml(centroCostoTexto) + '</td>',
				'<td class="sort-anio">' + parseInt(anio, 10) + '</td>',
				'<td class="sort-estado">' + badgeEstado + '</td>',
				'<td class="py-0 text-end align-middle">',
				'<div class="btn-group" role="group">',
				'<button type="button" class="btn btn-icon btn-lg" title="Editar" onclick="editarRequerimiento(' + id + ')">',
				'<i class="ti ti-edit fs-2"></i>',
				'</button>',
				'<button type="button" class="btn btn-icon btn-lg" title="Detalles" onclick="detalleRequerimiento(' + id + ')">',
				'<i class="ti ti-list-details fs-2"></i>',
				'</button>',
				'<button type="button" class="btn btn-icon btn-lg text-danger" title="Eliminar" onclick="eliminarRequerimiento(' + id + ')">',
				'<i class="ti ti-trash fs-2"></i>',
				'</button>',
				'</div>',
				'</td>',
			'</tr>'
		].join('');
	}

	// Agrega una nueva fila a la tabla de requerimientos
	function agregarFilaRequerimiento(id, idCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, anio, estado) {
		const tbody = document.getElementById('tabla-requerimientos-body');
		if (!tbody) return;

		const filaVacia = tbody.querySelector('tr[data-empty-row="true"], tr td[colspan="6"], tr td[colspan="7"]');
		if (filaVacia) {
			tbody.innerHTML = '';
		}

		tbody.insertAdjacentHTML('beforeend', construirFilaRequerimiento(id, idCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, anio, estado));
		actualizarTablaRequerimientosList();
	}

	// Actualiza una fila existente o la agrega si no existe
	function actualizarFilaRequerimiento(id, idCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, anio, estado) {
		const tbody = document.getElementById('tabla-requerimientos-body');
		if (!tbody) return;

		const fila = tbody.querySelector('tr[data-id="' + id + '"]');
		const htmlFila = construirFilaRequerimiento(id, idCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCosto, anio, estado);

		if (fila) {
			fila.outerHTML = htmlFila;
			actualizarTablaRequerimientosList();
			return;
		}

		tbody.insertAdjacentHTML('beforeend', htmlFila);
		actualizarTablaRequerimientosList();
	}

	// Inicializa el formulario modal para crear un nuevo requerimiento
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
		const modalEl = document.getElementById('modal-requerimiento');
		if (modalEl) {
			new bootstrap.Modal(modalEl).show();
		}
	}

	// Carga los datos de un requerimiento en el formulario para edición
	function editarRequerimiento(id) {
		const fila = document.querySelector('tr[data-id="' + id + '"]');
		if (!fila) {
			return;
		}

		const idRequerimientoEl = document.getElementById('IdRequerimiento');
		const idCentroCostoEl = document.getElementById('IdCentroCosto');
		const idMetaSiafEl = document.getElementById('IdMetaSIAF');
		const nroPedidoEl = document.getElementById('NroPedidoCompra');
		const codigoMetaEl = document.getElementById('CodigoMeta');
		const anioEl = document.getElementById('Anio');

		if (idRequerimientoEl) idRequerimientoEl.value = String(id);
		if (idCentroCostoEl) idCentroCostoEl.value = fila.dataset.idCentroCosto || '';
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

	// Filtra los requerimientos mostrados según el año seleccionado
	function filtrarPorAnio() {
		const filtro = document.getElementById('filtroAnio');
		const anio = filtro ? filtro.value : '';
		const url = urlAdquisiciones('requerimientos', { anio: anio });
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	// Abre el modal de importación de pedidos desde SIGA
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

	// Bandera para indicar si se debe recargar la lista después de importar
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

	// Event listener para validar entrada numérica en el campo de año en el modal de importación
	var inputAnioImportar = document.getElementById('anio-importar');
	if (inputAnioImportar) {
		inputAnioImportar.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '').slice(0, 4);
		});
	}

	// Event listener para validar entrada numérica en el campo de año en el formulario de requerimiento
	var inputAnioRequerimiento = document.getElementById('Anio');
	if (inputAnioRequerimiento) {
		inputAnioRequerimiento.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '').slice(0, 4);
		});
	}

	// Event listener para validar entrada alfanumérica en el código meta
	var inputCodigoMeta = document.getElementById('CodigoMeta');
	if (inputCodigoMeta) {
		inputCodigoMeta.addEventListener('input', function() {
			this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 4);
		});
	}

	// Event listener para buscar pedidos en SIGA por año
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

			fetch(urlAdquisicionesAjax('buscarPedidosSigaAjax'), {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: new URLSearchParams({
						anio: anio
					}).toString()
				})
				.then(function(resp) {
					return leerRespuestaJson(resp);
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

	// Importa un pedido individual desde SIGA y actualiza su estado en la tabla
	function importarPedido(nroPedido, anio, btn) {
		btn.disabled = true;
		btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

		fetch(urlAdquisicionesAjax('importarPedidoSigaAjax'), {
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

	// Event listener para guardar o actualizar un requerimiento desde el formulario
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
			const idMetaSiaf = idMetaSiafEl ? idMetaSiafEl.value : '';
			const centroCostoTexto = idCentroCostoEl && idCentroCostoEl.selectedOptions.length > 0 ? idCentroCostoEl.selectedOptions[0].text.trim() : '';
			const nroPedido = nroPedidoEl ? nroPedidoEl.value : '';
			const codigoMeta = codigoMetaEl ? codigoMetaEl.value : '';
			const anio = anioEl ? anioEl.value : '';
			const action = idRequerimiento ? 'actualizarAjax' : 'guardarAjax';

			fetch(urlAdquisicionesAjax(action), {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: new URLSearchParams(new FormData(form)).toString()
				})
				.then(function(resp) {
					return leerRespuestaJson(resp);
				})
				.then(function(response) {
					if (response.success) {
						const modal = bootstrap.Modal.getInstance(document.getElementById('modal-requerimiento'));
						if (modal) modal.hide();

						if (idRequerimiento) {
							const filaActual = document.querySelector('tr[data-id="' + idRequerimiento + '"]');
							const estadoActual = filaActual ? (filaActual.querySelector('.bg-success-lt') ? 1 : 0) : 0;
							actualizarFilaRequerimiento(idRequerimiento, idCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCostoTexto, anio, estadoActual);
							window.adqNotifySafe('success', 'Requerimiento actualizado', response.message || 'Requerimiento actualizado correctamente.');
						} else {
							agregarFilaRequerimiento(response.id, idCentroCosto, idMetaSiaf, nroPedido, codigoMeta, centroCostoTexto, anio, 0);
							window.adqNotifySafe('success', 'Requerimiento creado', response.message || 'Requerimiento registrado correctamente.');
						}

						form.reset();
						if (idRequerimientoEl) idRequerimientoEl.value = '';
						if (anioEl) anioEl.value = String(new Date().getFullYear());
						if (idCentroCostoEl) idCentroCostoEl.value = idCentroCosto;
						const titulo = document.querySelector('#modal-requerimiento .modal-title');
						if (titulo) titulo.textContent = 'Nuevo Requerimiento';
						const btnGuardar = document.getElementById('btnGuardarRequerimiento');
						if (btnGuardar) btnGuardar.textContent = 'Guardar Requerimiento';
					} else {
						window.adqNotifySafe('danger', 'No se pudo guardar', response.message || 'No se pudo guardar el requerimiento.');
					}
				})
				.catch(function(error) {
					window.adqNotifySafe('danger', 'Error de solicitud', error.message || 'Ocurrio un error al procesar la solicitud.');
				});
		});
	}

	// Navega a la página de detalles de un requerimiento específico
	function detalleRequerimiento(id) {
		const url = urlAdquisiciones('requerimiento', { id: id });
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	// Solicita confirmación y elimina un requerimiento y sus detalles
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

		fetch(urlAdquisicionesAjax('eliminarAjax'), {
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
					window.adqNotifySafe('success', 'Requerimiento eliminado', response.message || 'Requerimiento eliminado correctamente.');
				} else {
					window.adqNotifySafe('danger', 'No se pudo eliminar', response.message || 'No se pudo eliminar el requerimiento.');
				}
			})
			.catch(function() {
				window.adqNotifySafe('danger', 'Error de solicitud', 'Ocurrio un error al procesar la solicitud.');
			});
	}
</script>
