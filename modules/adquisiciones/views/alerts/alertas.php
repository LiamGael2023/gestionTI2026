<script>
	(function() {
		if (window.adqNotify && window.adqNotifySafe && window.adqAlertSafe) {
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

		function getDefaultHeading(type) {
			switch (type) {
				case 'success':
					return 'Operacion completada';
				case 'warning':
					return 'Atencion';
				case 'danger':
					return 'Ocurrio un problema';
				default:
					return 'Informacion';
			}
		}

		function swalOptions(options) {
			return Object.assign({
				width: '24rem',
				padding: '0 0 1rem'
			}, options || {});
		}

		window.adqNotify = function(type, heading, description, options) {
			const opts = Object.assign({}, options || {});
			const icon = swalIcon(type);
			const alertHeading = heading || getDefaultHeading(type);
			const alertDescription = description || '';

			if (typeof Swal !== 'undefined') {
				const swalConfig = {
					icon: icon,
					title: alertHeading,
					text: alertDescription,
					confirmButtonText: 'OK',
					confirmButtonColor: '#206bc4'
				};

				if (type === 'success') {
					swalConfig.timer = opts.delay || 2000;
					swalConfig.timerProgressBar = true;
				}

				return Swal.fire(swalOptions(swalConfig));
			}

			console.warn((alertHeading || '') + '\n' + (alertDescription || ''));
			return Promise.resolve();
		};

		window.adqNotifySafe = function(type, heading, description, options) {
			if (typeof window.adqNotify === 'function') {
				return window.adqNotify(type, heading, description, options);
			}
			console.warn(description || heading || 'Ocurrio un evento.');
			return null;
		};

		window.adqAlertSafe = function(type, heading, html, options) {
			if (typeof Swal !== 'undefined') {
				return Swal.fire(swalOptions(Object.assign({
					icon: swalIcon(type),
					title: heading || getDefaultHeading(type),
					html: html || '',
					confirmButtonText: 'OK',
					confirmButtonColor: '#206bc4'
				}, options || {})));
			}
			console.warn((heading || '') + '\n' + String(html || '').replace(/<[^>]*>/g, ''));
			return Promise.resolve();
		};
	})();
</script>
