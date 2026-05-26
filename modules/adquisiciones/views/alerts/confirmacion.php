<script>
	(function() {
		if (window.adqConfirm && window.adqConfirmSafe) {
			return;
		}

		function colorAceptar(claseAceptar) {
			if (claseAceptar && claseAceptar.indexOf('btn-success') >= 0) {
				return '#2fb344';
			}
			if (claseAceptar && claseAceptar.indexOf('btn-warning') >= 0) {
				return '#f59f00';
			}
			if (claseAceptar && claseAceptar.indexOf('btn-primary') >= 0) {
				return '#206bc4';
			}
			return '#d63939';
		}

		window.adqConfirm = function(options) {
			const opts = Object.assign({
				titulo: 'Confirmar eliminacion',
				mensaje: 'Desea continuar?',
				textoAceptar: 'Eliminar',
				textoCancelar: 'Cancelar',
				claseAceptar: 'btn-danger',
				icono: 'warning'
			}, options || {});

			if (typeof Swal !== 'undefined') {
				return Swal.fire({
					icon: opts.icono,
					title: opts.titulo,
					text: opts.mensaje,
					width: '24rem',
					padding: '0 0 1rem',
					showCancelButton: true,
					confirmButtonText: opts.textoAceptar,
					cancelButtonText: opts.textoCancelar,
					confirmButtonColor: colorAceptar(opts.claseAceptar),
					cancelButtonColor: '#667085'
				}).then(function(result) {
					return result.isConfirmed;
				});
			}

			console.warn(opts.mensaje || 'Desea continuar?');
			return Promise.resolve(false);
		};

		window.adqConfirmSafe = function(options) {
			if (typeof window.adqConfirm === 'function') {
				return window.adqConfirm(options);
			}
			const mensaje = options && options.mensaje ? options.mensaje : 'Desea continuar?';
			console.warn(mensaje);
			return Promise.resolve(false);
		};
	})();
</script>
