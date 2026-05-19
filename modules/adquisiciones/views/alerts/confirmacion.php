<div class="modal modal-blur fade" id="adq-modal-confirmacion" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-body text-center py-4">
				<h3 id="adq-confirmacion-titulo">Confirmar eliminacion</h3>
				<div id="adq-confirmacion-mensaje" class="text-secondary">¿Desea continuar?</div>
			</div>
			<div class="modal-footer">
				<div class="w-100">
					<div class="row">
						<div class="col">
							<button type="button" id="adq-confirmacion-cancelar" class="btn btn-primary w-100" data-bs-dismiss="modal">Cancelar</button>
						</div>
						<div class="col">
							<button type="button" id="adq-confirmacion-aceptar" class="btn btn-danger w-100">Eliminar</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	#adq-modal-confirmacion {
		z-index: 1085;
	}
</style>

<script>
	(function() {
		if (window.adqConfirm && window.adqConfirmSafe) {
			return;
		}

		const modalEl = document.getElementById('adq-modal-confirmacion');
		const tituloEl = document.getElementById('adq-confirmacion-titulo');
		const mensajeEl = document.getElementById('adq-confirmacion-mensaje');
		const btnAceptar = document.getElementById('adq-confirmacion-aceptar');
		const btnCancelar = document.getElementById('adq-confirmacion-cancelar');

		function prepararZIndexConfirmacion() {
			const modalesAbiertos = Array.prototype.slice.call(document.querySelectorAll('.modal.show'))
				.filter(function(el) {
					return el !== modalEl;
				});

			let zBase = 1055;
			modalesAbiertos.forEach(function(el) {
				const z = parseInt(window.getComputedStyle(el).zIndex, 10);
				if (!Number.isNaN(z) && z > zBase) {
					zBase = z;
				}
			});

			modalEl.style.zIndex = String(zBase + 30);

			// Bootstrap inserta el backdrop al final del body; elevamos el último.
			setTimeout(function() {
				const backdrops = document.querySelectorAll('.modal-backdrop');
				if (!backdrops.length) {
					return;
				}
				const ultimoBackdrop = backdrops[backdrops.length - 1];
				ultimoBackdrop.style.zIndex = String(zBase + 20);
			}, 0);
		}

		window.adqConfirm = function(options) {
			const opts = Object.assign({
				titulo: 'Confirmar eliminacion',
				mensaje: '¿Desea continuar?',
				textoAceptar: 'Eliminar',
				textoCancelar: 'Cancelar',
				claseAceptar: 'btn-danger'
			}, options || {});

			if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
				return Promise.resolve(window.confirm(opts.mensaje || '¿Desea continuar?'));
			}

			tituloEl.textContent = opts.titulo;
			mensajeEl.textContent = opts.mensaje;
			btnAceptar.textContent = opts.textoAceptar;
			btnCancelar.textContent = opts.textoCancelar;
			btnAceptar.className = 'btn w-100 ' + opts.claseAceptar;

			const instancia = bootstrap.Modal.getOrCreateInstance(modalEl);
			prepararZIndexConfirmacion();

			return new Promise(function(resolve) {
				let resulto = false;

				function limpiar() {
					btnAceptar.removeEventListener('click', onAceptar);
					modalEl.removeEventListener('hidden.bs.modal', onOculto);
				}

				function onAceptar() {
					resulto = true;
					limpiar();
					instancia.hide();
					resolve(true);
				}

				function onOculto() {
					if (resulto) {
						modalEl.style.removeProperty('z-index');
						return;
					}
					limpiar();
					modalEl.style.removeProperty('z-index');
					resolve(false);
				}

				btnAceptar.addEventListener('click', onAceptar);
				modalEl.addEventListener('hidden.bs.modal', onOculto);
				instancia.show();
			});
		};

		window.adqConfirmSafe = function(options) {
			if (typeof window.adqConfirm === 'function') {
				return window.adqConfirm(options);
			}
			const mensaje = options && options.mensaje ? options.mensaje : '¿Desea continuar?';
			return Promise.resolve(window.confirm(mensaje));
		};
	})();
</script>
