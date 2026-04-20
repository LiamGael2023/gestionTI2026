<div class="card">

<div class="card-header">
<h3><?php echo isset($persona) ? "Editar Persona" : "Nueva Persona"; ?></h3>
</div>

<div class="card-body">

<form id="form-persona" method="POST" action="index.php?module=certificados&action=<?php echo isset($persona)?'actualizar':'guardar'; ?>">

<?php if(isset($persona)){ ?>
<input type="hidden" name="id_persona" value="<?= $persona['id_persona'] ?>">
<?php } ?>

<label>DNI</label>
<input type="text" name="dni" class="form-control" value="<?= $persona['dni'] ?? '' ?>">

<label>Nombres</label>
<input type="text" name="nombres" class="form-control" value="<?= $persona['nombres'] ?? '' ?>">

<label>Apellidos</label>
<input type="text" name="apellidos" class="form-control" value="<?= $persona['apellidos'] ?? '' ?>">

<label>Correo</label>
<input type="email" name="correo" class="form-control" value="<?= $persona['correo'] ?? '' ?>">

<label>Teléfono</label>
<input type="text" name="telefono" class="form-control" value="<?= $persona['telefono'] ?? '' ?>">

<label>Gerencia</label>
<input type="text" name="gerencia_laboral" class="form-control" value="<?= $persona['gerencia_laboral'] ?? '' ?>">

<label>Código Reloj</label>
<input type="number" name="codigo_reloj" class="form-control" value="<?= $persona['codigo_reloj'] ?? '' ?>">

<br>

<button class="btn btn-success">Guardar</button>

<a href="index.php?module=certificados" class="btn btn-secondary">Volver</a>

</form>

</div>
</div>

<script>
(function() {
	function ensureNotifyHelper() {
		if (window.adqNotify && window.adqNotifySafe) {
			return;
		}

		var container = document.getElementById('adq-alert-stack');
		if (!container) {
			container = document.createElement('div');
			container.id = 'adq-alert-stack';
			container.className = 'position-fixed bottom-0 end-0 p-3 d-flex flex-column gap-2';
			container.style.zIndex = '1100';
			container.setAttribute('aria-live', 'polite');
			container.setAttribute('aria-atomic', 'false');
			document.body.appendChild(container);
		}

		window.adqNotify = function(type, heading, description, options) {
			var opts = Object.assign({ delay: 3200, autohide: true }, options || {});
			var alertType = ['success', 'info', 'warning', 'danger'].indexOf(type) >= 0 ? type : 'info';

			var alertEl = document.createElement('div');
			alertEl.className = 'alert alert-' + alertType;
			alertEl.style.margin = '0';
			alertEl.setAttribute('role', 'alert');

			var headingEl = document.createElement('h4');
			headingEl.className = 'alert-heading';
			headingEl.textContent = heading || 'Informacion';
			alertEl.appendChild(headingEl);

			if (description) {
				var descriptionEl = document.createElement('div');
				descriptionEl.style.whiteSpace = 'pre-line';
				descriptionEl.textContent = description;
				alertEl.appendChild(descriptionEl);
			}

			container.appendChild(alertEl);

			function closeAlert() {
				if (alertEl.parentNode) {
					alertEl.parentNode.removeChild(alertEl);
				}
			}

			alertEl.addEventListener('click', closeAlert);
			if (opts.autohide) {
				window.setTimeout(closeAlert, opts.delay);
			}
		};

		window.adqNotifySafe = function(type, heading, description, options) {
			if (typeof window.adqNotify === 'function') {
				return window.adqNotify(type, heading, description, options);
			}
			return null;
		};
	}

	ensureNotifyHelper();

	var form = document.getElementById('form-persona');
	if (!form) {
		return;
	}

	form.addEventListener('submit', async function(e) {
		e.preventDefault();
		var boton = form.querySelector('button[type="submit"]');
		var textoOriginal = boton ? boton.textContent : 'Guardar';
		if (boton) {
			boton.disabled = true;
			boton.textContent = 'Guardando...';
		}

		try {
			var response = await fetch(form.action, {
				method: 'POST',
				body: new FormData(form),
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json'
				}
			});

			var text = await response.text();
			var data = JSON.parse(text);

			if (!response.ok || (data && data.success === false)) {
				throw new Error((data && data.message) ? data.message : 'No se pudo guardar.');
			}

			adqNotifySafe(data.type || 'success', data.title || 'Operacion completada', data.message || 'Registro guardado correctamente');

			if (!form.querySelector('input[name="id_persona"]')) {
				form.reset();
			}
		} catch (error) {
			adqNotifySafe('danger', 'Ocurrio un problema', error.message || 'No se pudo guardar el registro');
		} finally {
			if (boton) {
				boton.disabled = false;
				boton.textContent = textoOriginal;
			}
		}
	});
})();
</script>