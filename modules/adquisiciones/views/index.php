<?php
$vistaActual = $vistaActual ?? 'dashboard';

$vistas = [
	'dashboard' => 'modules/adquisiciones/views/dashboard/index.php',
	'requerimientos' => 'modules/adquisiciones/views/requerimientos/index.php',
	'detalle' => 'modules/adquisiciones/views/requerimientos/detalle.php',
	'tecnologias' => 'modules/adquisiciones/views/tecnologias/index.php',
	'tecnologia' => 'modules/adquisiciones/views/tecnologias/detalle.php',
	'consolidado' => 'modules/adquisiciones/views/consolidado/index.php',
];

$vistaPath = isset($vistas[$vistaActual]) ? $vistas[$vistaActual] : $vistas['requerimientos'];
?>

<div class="page-header d-print-none">
	<div class="container-xl">
		<div class="row g-2 align-items-center">
			<div class="col">
				<h2 class="page-title">Adquisiciones</h2>
				<div class="text-secondary">Gestion de requerimientos y fichas tecnicas para compras de bienes informaticos</div>
			</div>
		</div>
	</div>
</div>

<div class="page-body">
	<div class="container-xl">
		<?php include __DIR__ . '/alerts/alertas.php'; ?>
		<?php include __DIR__ . '/alerts/confirmacion.php'; ?>

		<div class="card">
			<div class="card-header" id="adquisiciones-nav-container">
				<ul class="nav nav-pills card-header-pills">
					<li class="nav-item">
						<a class="nav-link js-adq-nav <?php echo $vistaActual === 'dashboard' ? 'active' : ''; ?>" href="index.php?module=adquisiciones&action=dashboard">
							Dashboard
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link js-adq-nav <?php echo $vistaActual === 'requerimientos' ? 'active' : ''; ?>" href="index.php?module=adquisiciones&action=requerimientos">
							Requerimientos
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link js-adq-nav <?php echo $vistaActual === 'tecnologias' ? 'active' : ''; ?>" href="index.php?module=adquisiciones&action=tecnologias">
							Tecnologias
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link js-adq-nav <?php echo $vistaActual === 'consolidado' ? 'active' : ''; ?>" href="index.php?module=adquisiciones&action=consolidado">
							Consolidado
						</a>
					</li>
				</ul>
			</div>

			<div class="card-body" id="adquisiciones-contenido">
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
		if (window.cargarVistaAdquisiciones) {
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

		function esUrlInternaAdquisiciones(url) {
			try {
				const parsed = new URL(url, window.location.origin);
				return parsed.searchParams.get('module') === 'adquisiciones';
			} catch (e) {
				return false;
			}
		}

		window.cargarVistaAdquisiciones = function(url, options) {
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

					const nuevoContenido = doc.getElementById('adquisiciones-contenido');
					const nuevaNav = doc.getElementById('adquisiciones-nav-container');

					const contenidoActual = document.getElementById('adquisiciones-contenido');
					const navActual = document.getElementById('adquisiciones-nav-container');

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
						window.history.pushState({ module: 'adquisiciones' }, '', url);
					}
				})
				.catch(function() {
					window.location.href = url;
				});
		};

		window.recargarVistaActualAdquisiciones = function() {
			return window.cargarVistaAdquisiciones(window.location.pathname + window.location.search, {
				pushState: false
			});
		};

		document.addEventListener('click', function(event) {
			const link = event.target.closest('a.js-adq-link');
			if (!link) {
				return;
			}

			const href = link.getAttribute('href');
			if (!href || !esUrlInternaAdquisiciones(href)) {
				return;
			}

			event.preventDefault();
			window.cargarVistaAdquisiciones(href);
		});

		window.addEventListener('popstate', function() {
			if (new URL(window.location.href).searchParams.get('module') === 'adquisiciones') {
				window.cargarVistaAdquisiciones(window.location.pathname + window.location.search, {
					pushState: false
				});
			}
		});
	})();
</script>