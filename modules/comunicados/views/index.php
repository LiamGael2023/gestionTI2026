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
		<?php include 'modules/adquisiciones/views/alerts/alertas.php'; ?>
		<?php include 'modules/adquisiciones/views/alerts/confirmacion.php'; ?>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script>
			(function() {
				if (window.comNotifySafe && window.comConfirmSafe && window.comPromptSafe) {
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
						padding: '0 0 1rem'
					}, options || {});
				}

				window.comNotifySafe = function(type, title, text) {
					if (typeof Swal !== 'undefined') {
						return Swal.fire(comSwalOptions({
							icon: swalIcon(type),
							title: title || 'Informacion',
							text: text || '',
							confirmButtonText: 'OK',
							confirmButtonColor: '#206bc4'
						}));
					}
					return window.adqNotifySafe ? window.adqNotifySafe(type, title, text) : alert((title || '') + '\n' + (text || ''));
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

					return window.adqConfirmSafe ? window.adqConfirmSafe(opts) : Promise.resolve(confirm(opts.mensaje));
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
			})();
		</script>

		<div class="card">
			<div class="card-header" id="comunicados-nav-container">
				<ul class="nav nav-pills card-header-pills">
					<li class="nav-item">
						<a class="nav-link js-com-nav <?php echo $vistaActual === 'dashboard' ? 'active' : ''; ?>" href="index.php?module=comunicados&action=dashboard">
							Dashboard
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link js-com-nav <?php echo strpos($vistaActual, 'comunicado') === 0 ? 'active' : ''; ?>" href="index.php?module=comunicados&action=comunicados">
							Comunicados
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link js-com-nav <?php echo $vistaActual === 'plantilla' ? 'active' : ''; ?>" href="index.php?module=comunicados&action=plantillas">
							Plantillas
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link js-com-nav <?php echo $vistaActual === 'archivo' ? 'active' : ''; ?>" href="index.php?module=comunicados&action=archivos">
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
		if (window.cargarVistaComunicados) {
			return;
		}

		function ejecutarScripts(contenedor) {
			const scripts = contenedor.querySelectorAll('script');
			scripts.forEach(function(scriptViejo) {
				const scriptNuevo = document.createElement('script');
				Array.from(scriptViejo.attributes).forEach(function(attr) {
					scriptNuevo.setAttribute(attr.name, attr.value);
				});

				if (!scriptViejo.src) {
					scriptNuevo.textContent = scriptViejo.textContent;
				}

				scriptViejo.parentNode.replaceChild(scriptNuevo, scriptViejo);
			});
		}

		function esUrlInternaComunicados(url) {
			try {
				const parsed = new URL(url, window.location.origin);
				return parsed.searchParams.get('module') === 'comunicados';
			} catch (e) {
				return false;
			}
		}

		window.cargarVistaComunicados = function(url, options) {
			const opts = Object.assign({ pushState: true }, options || {});

			return fetch(url, {
				method: 'GET',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
				.then(function(response) {
					if (!response.ok) {
						throw new Error('No se pudo cargar la vista.');
					}
					return response.text();
				})
				.then(function(html) {
					const parser = new DOMParser();
					const doc = parser.parseFromString(html, 'text/html');
					const nuevoContenido = doc.getElementById('comunicados-contenido');
					const nuevaNav = doc.getElementById('comunicados-nav-container');
					const contenidoActual = document.getElementById('comunicados-contenido');
					const navActual = document.getElementById('comunicados-nav-container');

					if (!nuevoContenido || !contenidoActual) {
						window.location.href = url;
						return;
					}

					if (nuevaNav && navActual) {
						navActual.innerHTML = nuevaNav.innerHTML;
					}

					contenidoActual.innerHTML = nuevoContenido.innerHTML;
					ejecutarScripts(contenidoActual);

					if (opts.pushState) {
						window.history.pushState({ module: 'comunicados' }, '', url);
					}
				})
				.catch(function() {
					window.location.href = url;
				});
		};

		window.recargarVistaActualComunicados = function() {
			return window.cargarVistaComunicados(window.location.pathname + window.location.search, {
				pushState: false
			});
		};

		document.addEventListener('click', function(event) {
			const link = event.target.closest('a.js-com-link, a.js-com-nav');
			if (!link) {
				return;
			}

			const href = link.getAttribute('href');
			if (!href || !esUrlInternaComunicados(href)) {
				return;
			}

			event.preventDefault();
			window.cargarVistaComunicados(href);
		});

		window.addEventListener('popstate', function() {
			if (new URL(window.location.href).searchParams.get('module') === 'comunicados') {
				window.cargarVistaComunicados(window.location.pathname + window.location.search, {
					pushState: false
				});
			}
		});
	})();
</script>
