<div id="adq-alert-stack" class="position-fixed bottom-0 end-0 p-3 d-flex flex-column gap-2" style="z-index: 1100;" aria-live="polite" aria-atomic="false"></div>

<script>
	(function() {
		if (window.adqNotify && window.adqNotifySafe) {
			return;
		}

		function ensureContainer() {
			let container = document.getElementById('adq-alert-stack');
			if (!container) {
				container = document.createElement('div');
				container.id = 'adq-alert-stack';
				container.className = 'position-fixed bottom-0 end-0 p-3 d-flex flex-column gap-2';
				container.style.zIndex = '1100';
				container.setAttribute('aria-live', 'polite');
				container.setAttribute('aria-atomic', 'false');
				document.body.appendChild(container);
			}
			return container;
		}

		function getAlertType(type) {
			const allowed = ['success', 'info', 'warning', 'danger'];
			return allowed.indexOf(type) >= 0 ? type : 'info';
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

		window.adqNotify = function(type, heading, description, options) {
			const opts = Object.assign({ delay: 5200, autohide: true }, options || {});
			const alertType = getAlertType(type);
			const alertHeading = heading || getDefaultHeading(alertType);
			const alertDescription = description || '';
			const stack = ensureContainer();

			const alertEl = document.createElement('div');
			alertEl.className = 'alert alert-' + alertType;
			alertEl.style.margin = '0';
			alertEl.setAttribute('role', 'alert');

			const contentWrap = document.createElement('div');
			const headingEl = document.createElement('h4');
			headingEl.className = 'alert-heading';
			headingEl.textContent = alertHeading;

			const descriptionEl = document.createElement('div');
			descriptionEl.className = 'alert-description';
			descriptionEl.textContent = alertDescription;
			descriptionEl.style.whiteSpace = 'pre-line';

			contentWrap.appendChild(headingEl);
			if (alertDescription !== '') {
				contentWrap.appendChild(descriptionEl);
			}

			alertEl.appendChild(contentWrap);
			stack.appendChild(alertEl);

			function closeAlert() {
				if (!alertEl.parentNode) {
					return;
				}
				alertEl.parentNode.removeChild(alertEl);
			}

			alertEl.addEventListener('click', closeAlert);

			if (opts.autohide) {
				window.setTimeout(closeAlert, opts.delay);
			}

			return alertEl;
		};

		window.adqNotifySafe = function(type, heading, description, options) {
			if (typeof window.adqNotify === 'function') {
				return window.adqNotify(type, heading, description, options);
			}
			console.warn(description || heading || 'Ocurrio un evento.');
			return null;
		};
	})();
</script>