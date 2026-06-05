<?php
$vistaActual = $vistaActual ?? 'dashboard';

$vistas = [
	'dashboard' => 'modules/comunicados/views/dashboard/index.php',
	'comunicado' => 'modules/comunicados/views/comunicado/index.php',
	'comunicado_editor' => 'modules/comunicados/views/comunicado/editor.php',
	'plantilla' => 'modules/comunicados/views/plantilla/index.php',
	'archivo' => 'modules/comunicados/views/archivo/index.php',
];

$vistaPath = isset($vistas[$vistaActual]) ? $vistas[$vistaActual] : $vistas['dashboard'];
?>

<div class="page-header d-print-none">
	<div class="container-xl">
		<div class="row g-2 align-items-center">
			<div class="col">
				<h2 class="page-title">Comunicados</h2>
				<div class="text-secondary">Gestion de comunicados HTML, plantillas y archivos para correo institucional</div>
			</div>
		</div>
	</div>
</div>

<div class="page-body">
	<div class="container-xl">
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script>
			(function() {
				if (window.comNotifySafe && window.comConfirmSafe && window.comPromptSafe && window.comShowModalSafe && window.comHideModalSafe) {
					return;
				}

				function swalIcon(type) {
					if (type === 'danger') {
						return 'error';
					}
					if (['success', 'warning', 'info', 'question', 'error'].indexOf(type) >= 0) {
						return type;
					}
					return 'info';
				}

				function comSwalOptions(options) {
					return Object.assign({
						width: '24rem',
						padding: '0 0 1rem',
						heightAuto: false
					}, options || {});
				}

				window.comNotifySafe = function(type, title, text) {
					if (typeof Swal !== 'undefined') {
						var notifyOptions = {
							icon: swalIcon(type),
							title: title || 'Informacion',
							text: text || '',
							confirmButtonText: 'OK',
							confirmButtonColor: '#206bc4'
						};

						if (type === 'success') {
							notifyOptions.timer = 2000;
							notifyOptions.timerProgressBar = true;
						}

						return Swal.fire(comSwalOptions(notifyOptions));
					}
					return alert((title || '') + '\n' + (text || ''));
				};

				window.comConfirmSafe = function(options) {
					var opts = Object.assign({
						titulo: 'Confirmar accion',
						mensaje: 'Desea continuar?',
						textoAceptar: 'Aceptar',
						textoCancelar: 'Cancelar',
						icono: 'warning',
						colorAceptar: '#d63939'
					}, options || {});

					if (typeof Swal !== 'undefined') {
						return Swal.fire(comSwalOptions({
							icon: opts.icono,
							title: opts.titulo,
							text: opts.mensaje,
							showCancelButton: true,
							confirmButtonText: opts.textoAceptar,
							cancelButtonText: opts.textoCancelar,
							confirmButtonColor: opts.colorAceptar,
							cancelButtonColor: '#667085'
						})).then(function(result) {
							return result.isConfirmed;
						});
					}

					return Promise.resolve(confirm(opts.mensaje));
				};

				window.comPromptSafe = function(options) {
					var opts = Object.assign({
						titulo: 'Ingrese un valor',
						mensaje: '',
						valor: '',
						placeholder: '',
						textoAceptar: 'Guardar',
						textoCancelar: 'Cancelar'
					}, options || {});

					if (typeof Swal !== 'undefined') {
						return Swal.fire(comSwalOptions({
							title: opts.titulo,
							text: opts.mensaje,
							input: 'text',
							inputValue: opts.valor,
							inputPlaceholder: opts.placeholder,
							showCancelButton: true,
							confirmButtonText: opts.textoAceptar,
							cancelButtonText: opts.textoCancelar,
							confirmButtonColor: '#206bc4',
							cancelButtonColor: '#667085',
							inputValidator: function(value) {
								if (opts.requerido && !String(value || '').trim()) {
									return 'Debe ingresar un valor.';
								}
								return null;
							}
						})).then(function(result) {
							return result.isConfirmed ? result.value : null;
						});
					}

					return Promise.resolve(window.prompt(opts.titulo, opts.valor));
				};

				window.comHideModalSafe = function(modalEl) {
					if (!modalEl) {
						return;
					}

					if (window.bootstrap && bootstrap.Modal) {
						var instancia = bootstrap.Modal.getInstance(modalEl);
						if (!instancia && modalEl.classList.contains('show')) {
							instancia = bootstrap.Modal.getOrCreateInstance(modalEl);
						}
						if (instancia) {
							instancia.hide();
							return;
						}
					}

					modalEl.classList.remove('show');
					modalEl.style.display = 'none';
					modalEl.setAttribute('aria-hidden', 'true');
					modalEl.removeAttribute('aria-modal');
					modalEl.removeAttribute('role');
					document.body.classList.remove('modal-open');

					document.querySelectorAll('.modal-backdrop[data-comunicados-fallback="true"]').forEach(function(backdrop) {
						backdrop.remove();
					});
				};

				window.comShowModalSafe = function(modalEl) {
					if (!modalEl) {
						return;
					}

					if (window.bootstrap && bootstrap.Modal) {
						bootstrap.Modal.getOrCreateInstance(modalEl).show();
						return;
					}

					modalEl.style.display = 'block';
					modalEl.removeAttribute('aria-hidden');
					modalEl.setAttribute('aria-modal', 'true');
					modalEl.setAttribute('role', 'dialog');
					modalEl.classList.add('show');
					document.body.classList.add('modal-open');

					if (!document.querySelector('.modal-backdrop[data-comunicados-fallback="true"]')) {
						var backdrop = document.createElement('div');
						backdrop.className = 'modal-backdrop fade show';
						backdrop.dataset.comunicadosFallback = 'true';
						backdrop.addEventListener('click', function() {
							window.comHideModalSafe(modalEl);
						});
						document.body.appendChild(backdrop);
					}
				};

				window.comInitAdvancedTable = function(config) {
					var opts = Object.assign({
						page: 10,
						searchShortcut: true
					}, config || {});
					var tableId = opts.tableId;
					var listKey = opts.listKey || tableId;
					var footerId = opts.footerId;
					var searchId = opts.searchId;
					var pageCountId = opts.pageCountId;
					var setPageFunctionName = opts.setPageFunctionName;

					if (!tableId || !Array.isArray(opts.valueNames)) {
						return;
					}

					function rowCount() {
						var contenedor = document.getElementById(tableId);
						return contenedor ? contenedor.querySelectorAll('.table-tbody tr:not([data-empty-row])').length : 0;
					}

					function updateFooter() {
						var footer = footerId ? document.getElementById(footerId) : null;
						if (!footer) return;

						if (rowCount() === 0) {
							footer.style.setProperty('display', 'none', 'important');
							return;
						}

						footer.style.removeProperty('display');
					}

					function loadList(callback) {
						if (typeof List === 'function') {
							callback();
							return;
						}

						var existing = document.querySelector('script[data-com-listjs="true"]');
						if (existing) {
							existing.addEventListener('load', callback, { once: true });
							return;
						}

						var script = document.createElement('script');
						script.src = 'https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js';
						script.defer = true;
						script.dataset.comListjs = 'true';
						script.addEventListener('load', callback, { once: true });
						document.head.appendChild(script);
					}

					function init() {
						var contenedor = document.getElementById(tableId);
						if (!contenedor) return;

						updateFooter();

						window.tabler_list = window.tabler_list || {};

						if (rowCount() === 0) {
							window.tabler_list[listKey] = null;
							return;
						}

						if (contenedor.dataset.listInitialized === '1' && window.tabler_list[listKey]) {
							window.tabler_list[listKey].update();
							return;
						}

						if (typeof List !== 'function') return;

						try {
							window.tabler_list[listKey] = new List(tableId, {
								sortClass: 'table-sort',
								listClass: 'table-tbody',
								page: opts.page,
								pagination: {
									item: function(value) {
										return '<li class="page-item"><a class="page-link cursor-pointer">' + value.page + '</a></li>';
									},
									innerWindow: 1,
									outerWindow: 1,
									left: 0,
									right: 0
								},
								valueNames: opts.valueNames
							});
						} catch (error) {
							console.warn('No se pudo inicializar la tabla.', error);
							return;
						}

						contenedor.dataset.listInitialized = '1';

						var searchInput = searchId ? document.getElementById(searchId) : null;
						if (searchInput && searchInput.dataset.searchBound !== '1') {
							searchInput.addEventListener('input', function() {
								var list = window.tabler_list && window.tabler_list[listKey];
								if (list) {
									list.search(searchInput.value);
								}
							});
							searchInput.dataset.searchBound = '1';
						}
					}

					if (setPageFunctionName) {
						window[setPageFunctionName] = function(event) {
							event.preventDefault();
							var list = window.tabler_list && window.tabler_list[listKey];
							if (!list) return;

							list.page = parseInt(event.target.dataset.value, 10);
							list.update();

							var pageCount = pageCountId ? document.getElementById(pageCountId) : null;
							if (pageCount) {
								pageCount.innerHTML = event.target.dataset.value;
							}
						};
					}

					if (opts.searchShortcut && !window.comAdvancedTableShortcutBound) {
						document.addEventListener('keydown', function(event) {
							if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'k') {
								return;
							}

							var focusedSearch = document.querySelector('#comunicados-contenido input[id$="-table-search"]');
							if (!focusedSearch) return;

							event.preventDefault();
							focusedSearch.focus();
						});
						window.comAdvancedTableShortcutBound = true;
					}

					loadList(init);
				};
			})();
		</script>

		<div class="card">
			<div class="card-header" id="comunicados-nav-container">
				<ul class="nav nav-pills card-header-pills">
					<li class="nav-item">
						<a class="nav-link <?php echo $vistaActual === 'dashboard' ? 'active' : ''; ?>" href="index.php?module=comunicados&action=dashboard">
							Dashboard
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo strpos($vistaActual, 'comunicado') === 0 ? 'active' : ''; ?>" href="index.php?module=comunicados&action=comunicados">
							Comunicados
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo $vistaActual === 'plantilla' ? 'active' : ''; ?>" href="index.php?module=comunicados&action=plantillas">
							Plantillas
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo $vistaActual === 'archivo' ? 'active' : ''; ?>" href="index.php?module=comunicados&action=archivos">
							Archivos
						</a>
					</li>
				</ul>
			</div>

			<div class="card-body" id="comunicados-contenido">
				<?php if (file_exists($vistaPath)): ?>
					<?php include $vistaPath; ?>
				<?php else: ?>
					<div class="alert alert-warning mb-0">
						No se encontro la vista solicitada.
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<script>
	(function() {
		if (window.recargarVistaActualComunicados) {
			return;
		}

		window.recargarVistaActualComunicados = function() {
			window.location.reload();
		};

		function obtenerModalVisibleComunicados() {
			return document.querySelector('#comunicados-contenido .modal.show');
		}

		document.addEventListener('click', function(event) {
			const modalButton = event.target.closest('[data-bs-target="#modalArchivo"]');
			if (modalButton) {
				event.preventDefault();
				event.stopPropagation();

				const targetSelector = modalButton.getAttribute('data-bs-target');
				const modalEl = document.querySelector(targetSelector);
				const form = document.getElementById('formArchivo');

				if (form) {
					form.reset();
				}

				if (typeof window.comShowModalSafe === 'function') {
					window.comShowModalSafe(modalEl);
				}
				return;
			}

			const closeButton = event.target.closest('#comunicados-contenido .modal [data-bs-dismiss="modal"]');
			if (closeButton) {
				event.preventDefault();
				if (typeof window.comHideModalSafe === 'function') {
					window.comHideModalSafe(closeButton.closest('.modal'));
				}
			}
		});

		document.addEventListener('keydown', function(event) {
			if (event.key !== 'Escape' || document.querySelector('.swal2-container')) {
				return;
			}

			const modalEl = obtenerModalVisibleComunicados();
			if (!modalEl || typeof window.comHideModalSafe !== 'function') {
				return;
			}

			event.preventDefault();
			window.comHideModalSafe(modalEl);
		});
	})();
</script>
