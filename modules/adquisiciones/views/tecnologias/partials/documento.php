<!-- Modal para visualizar archivos PDF -->
<div class="modal modal-blur fade" id="modalVisorPdf" tabindex="-1" aria-labelledby="modalVisorPdfLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalVisorPdfLabel">Vista previa de PDF</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar"></button>
			</div>
			<div class="modal-body p-0" style="height: 80vh;">
				<iframe id="iframeVisorPdf" src="" title="Visor PDF" style="width: 100%; height: 100%; border: 0;"></iframe>
			</div>
		</div>
	</div>
</div>

<script>
	// Abre un archivo PDF en el modal asignando la URL al iframe y mostrando el modal
	function abrirPdfEnModal(url) {
		const iframe = document.getElementById('iframeVisorPdf');
		const modalElement = document.getElementById('modalVisorPdf');

		if (!iframe || !modalElement) {
			window.adqNotifySafe('danger', 'Error', 'El modal no esta disponible.');
			return false;
		}

		iframe.src = url;
		const BootstrapModal = obtenerBootstrapModal();

		if (BootstrapModal) {
			const modalInstance = BootstrapModal.getOrCreateInstance(modalElement);
			modalInstance.show();
		} else if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('show');
		} else {
			abrirModalFallback(modalElement);
		}

		return false;
	}

	// Inicializa los eventos del modal (cierre, limpieza de iframe y manejo de teclas)
	function inicializarModalVisorPdf() {
		const modalElement = document.getElementById('modalVisorPdf');
		if (!modalElement) {
			return;
		}

		if (modalElement.dataset.adqPdfInit === '1') {
			return;
		}
		modalElement.dataset.adqPdfInit = '1';

		const limpiarIframe = function() {
			const iframe = document.getElementById('iframeVisorPdf');
			if (iframe) {
				iframe.src = '';
			}
		};

		modalElement.addEventListener('hidden.bs.modal', limpiarIframe);

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).on('hidden.bs.modal', limpiarIframe);
		}

		const botonCerrar = modalElement.querySelector('.btn-close');
		if (botonCerrar) {
			botonCerrar.addEventListener('click', function() {
				const BootstrapModal = obtenerBootstrapModal();
				if (!BootstrapModal && !(typeof $ !== 'undefined' && $.fn.modal)) {
					cerrarModalFallback(modalElement);
				}
			});
		}

		modalElement.addEventListener('click', function(event) {
			if (event.target !== modalElement) {
				return;
			}

			const BootstrapModal = obtenerBootstrapModal();
			if (!BootstrapModal && !(typeof $ !== 'undefined' && $.fn.modal)) {
				cerrarModalFallback(modalElement);
			}
		});

		document.addEventListener('keydown', function(event) {
			if (event.key !== 'Escape') {
				return;
			}

			if (!modalElement.classList.contains('show')) {
				return;
			}

			const BootstrapModal = obtenerBootstrapModal();
			if (BootstrapModal) {
				BootstrapModal.getOrCreateInstance(modalElement).hide();
				return;
			}

			if (typeof $ !== 'undefined' && $.fn.modal) {
				$(modalElement).modal('hide');
				return;
			}

			cerrarModalFallback(modalElement);
		});
	}
</script>
