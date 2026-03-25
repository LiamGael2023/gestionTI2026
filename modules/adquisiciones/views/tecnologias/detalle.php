<style>
	.adq-dashboard .card {
		border: 1px solid rgba(98, 105, 118, 0.12);
		border-radius: 0.9rem;
		box-shadow: 0 1px 2px rgba(31, 41, 55, 0.04), 0 8px 24px -14px rgba(31, 41, 55, 0.22);
		transition: box-shadow 0.2s ease, border-color 0.2s ease;
	}

	.adq-dashboard .card:hover {
		border-color: rgba(98, 105, 118, 0.2);
		box-shadow: 0 2px 6px rgba(31, 41, 55, 0.08), 0 10px 24px -14px rgba(31, 41, 55, 0.28);
	}

	.adq-dashboard .card .card-header {
		border-bottom-color: rgba(98, 105, 118, 0.12);
	}

	.icon-action {
		cursor: pointer;
		font-size: 20px;
		padding: 6px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		line-height: 1;
		vertical-align: middle;
		transition: 0.2s ease;
	}

	.acciones-iconos {
		display: inline-flex;
		align-items: center;
		justify-content: flex-end;
		gap: 0.5rem;
	}

	.adq-pdf-modal-backdrop {
		background-color: rgba(24, 36, 51, 0.45);
		backdrop-filter: blur(4px);
		-webkit-backdrop-filter: blur(4px);
	}
</style>


<?php
$idTec = (int) $tecnologia['Id'];
$codigoTecRaw = (string) ($tecnologia['Codigo'] ?? '');
$nombreTecRaw = (string) ($tecnologia['NombreGenerico'] ?? '');
$codigoTec = htmlspecialchars($codigoTecRaw);
$nombreTec = htmlspecialchars($nombreTecRaw);
$anioActual = isset($anioFiltro) ? (int) $anioFiltro : (int) date('Y');
$minimoFichasRequeridas = 2;
$totalFichas = count($fichasTecnicas);
$tieneFichas = $totalFichas >= $minimoFichasRequeridas;
$tieneEspecificacion = !empty($especificacionTecnica);
$tieneOrdenCompra = !empty($ordenCompra);
$tieneVerificacion = !empty($verificacionTecnica);
$tieneConformidad = !empty($conformidad);
$puedeRegistrarEspecificacion = $tieneFichas;
$puedeRegistrarOrdenCompra = $tieneFichas && $tieneEspecificacion;
$puedeRegistrarVerificacion = $tieneFichas && $tieneEspecificacion && $tieneOrdenCompra;
$puedeRegistrarConformidad = $tieneFichas && $tieneEspecificacion && $tieneVerificacion;
$formatearFecha = static function ($fecha) {
	if (empty($fecha)) {
		return '';
	}

	$timestamp = strtotime((string) $fecha);

	return $timestamp ? date('d-m-Y', $timestamp) : (string) $fecha;
};
$formatearHora = static function ($fecha) {
	if (empty($fecha)) {
		return '';
	}

	$timestamp = strtotime((string) $fecha);

	return $timestamp ? date('H:i', $timestamp) : '';
};
$codigosSigaDetectados = [];
foreach ($pedidos as $pedido) {
	$codigoSigaPedido = isset($pedido['CodigoSiga']) ? trim((string) $pedido['CodigoSiga']) : '';
	if ($codigoSigaPedido !== '' && !in_array($codigoSigaPedido, $codigosSigaDetectados, true)) {
		$codigosSigaDetectados[] = $codigoSigaPedido;
	}
}
$hayDiferenciaCodigoSiga = count($codigosSigaDetectados) > 1;
?>

<div class="adq-dashboard">

	<div class="bg-primary text-white p-3 rounded mb-3">
		<h3 class="mb-0 fw-bold fs-8">
			<?php echo $codigoTec . " : " . $nombreTec; ?>
		</h3>
	</div>

	<div class="card card-body mb-3">
		<h4 class="fw-bold mb-3">Pedidos de Compra donde aparece</h4>

		<?php if ($hayDiferenciaCodigoSiga): ?>
			<div class="alert alert-warning" role="alert">
				Se detectaron diferencias de Código SIGA para esta tecnología en el año <?php echo $anioActual; ?>.
				Códigos encontrados: <strong><?php echo htmlspecialchars(implode(', ', $codigosSigaDetectados)); ?></strong>.
				Revise la tabla para identificar qué pedido debe ser corregido.
			</div>
		<?php endif; ?>

		<div class="d-flex gap-2 align-items-center mb-3">
			<label class="form-label mb-0">Año:</label>
			<select id="filtroAnioPedidos" class="form-select" style="width: 120px;" onchange="cambiarAnioDetalle()">
				<?php foreach ($aniosDisponiblesTec as $a): ?>
					<option value="<?php echo (int) $a; ?>" <?php echo ($anioActual == $a) ? 'selected' : ''; ?>>
						<?php echo (int) $a; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="table-responsive">
			<table class="table table-vcenter card-table table-striped">
				<thead>
					<tr>
						<th>Nro. de Pedido</th>
						<th>Dirección Solicitante</th>
						<th>Código SIGA</th>
						<th>Descripción</th>
						<th>Cantidad</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($pedidos)): ?>
						<?php foreach ($pedidos as $p): ?>
							<tr>
								<td><?php echo htmlspecialchars($p['NroPedidoCompra']); ?></td>
								<td><?php echo htmlspecialchars($p['DireccionSolicitante']); ?></td>
								<td>
									<span class="badge bg-azure-lt"><?php echo htmlspecialchars($p['CodigoSiga']); ?></span>
								</td>
								<td><?php echo htmlspecialchars($p['DescripcionDetallada']); ?></td>
								<td><?php echo (float) $p['Cantidad']; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr>
							<td colspan="5" class="text-center text-secondary">No hay pedidos de compra registrados para este año.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php require __DIR__ . '/partials/ficha.php'; ?>
	<?php require __DIR__ . '/partials/especificacion.php'; ?>
	<?php require __DIR__ . '/partials/orden.php'; ?>
	<?php require __DIR__ . '/partials/verificacion.php'; ?>
	<?php require __DIR__ . '/partials/conformidad.php'; ?>

	<!-- ===================== BARRA DE ACCIONES ===================== -->
	<div class="d-flex justify-content-end gap-2 mt-2 mb-3">
		<a href="index.php?module=adquisiciones&action=tecnologias&anio=<?php echo $anioActual; ?>" class="btn btn-secondary js-adq-link">Volver</a>
	</div>

</div>

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
	const idTecnologia = <?php echo $idTec; ?>;
	const anioActual = <?php echo $anioActual; ?>;
	const codigoTecnologia = <?php echo json_encode($codigoTecRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const nombreTecnologia = <?php echo json_encode($nombreTecRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	let adqPdfBackdropFallback = null;

	function normalizarTextoAscii(valor) {
		return String(valor || '')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.toUpperCase()
			.trim();
	}

	function obtenerTokenCodigoTecnologia() {
		const codigoLimpio = normalizarTextoAscii(codigoTecnologia);
		const match = codigoLimpio.match(/T\d+/);
		if (match && match[0]) {
			return match[0];
		}

		const primerToken = codigoLimpio.split(/[^A-Z0-9]+/).filter(Boolean)[0] || '';
		if (!primerToken) {
			return 'T' + idTecnologia;
		}

		return primerToken.startsWith('T') ? primerToken : ('T' + primerToken);
	}

	function obtenerPrimeraPalabraDescripcion() {
		const descripcionLimpia = normalizarTextoAscii(nombreTecnologia);
		const token = descripcionLimpia.split(/[^A-Z0-9]+/).filter(Boolean)[0];
		return token || 'TECNOLOGIA';
	}

	function generarCodigoEspecificacion() {
		const tokenTecnologia = obtenerTokenCodigoTecnologia();
		const primeraPalabra = obtenerPrimeraPalabraDescripcion();
		return 'ET_' + tokenTecnologia + '_' + primeraPalabra + '_' + anioActual;
	}

	function generarNumeroOrdenCompra(anio) {
		const tokenTecnologia = obtenerTokenCodigoTecnologia();
		const primeraPalabra = obtenerPrimeraPalabraDescripcion();
		const anioNumerico = parseInt(anio, 10) || anioActual;
		const maxLength = 25;
		const prefijo = 'OC_' + tokenTecnologia + '_';
		const sufijo = '_' + anioNumerico;
		const maxPalabra = Math.max(1, maxLength - prefijo.length - sufijo.length);
		const palabraAjustada = primeraPalabra.slice(0, maxPalabra);

		return prefijo + palabraAjustada + sufijo;
	}

	function formatearCodigoEspecificacionVisual(codigo) {
		return String(codigo || '').replace(/_/g, ' ').trim();
	}

	function obtenerBootstrapModal() {
		if (typeof window !== 'undefined' && window.bootstrap && window.bootstrap.Modal) {
			return window.bootstrap.Modal;
		}

		if (typeof bootstrap !== 'undefined' && bootstrap && bootstrap.Modal) {
			return bootstrap.Modal;
		}

		return null;
	}

	function abrirModalFallback(modalElement) {
		if (!modalElement) {
			return;
		}

		modalElement.style.display = 'block';
		modalElement.classList.add('show');
		modalElement.removeAttribute('aria-hidden');
		document.body.classList.add('modal-open');

		if (!adqPdfBackdropFallback) {
			const backdrop = document.createElement('div');
			backdrop.className = 'modal-backdrop fade show adq-pdf-modal-backdrop';
			document.body.appendChild(backdrop);
			adqPdfBackdropFallback = backdrop;
		}
	}

	function cerrarModalFallback(modalElement) {
		if (!modalElement) {
			return;
		}

		modalElement.classList.remove('show');
		modalElement.style.display = 'none';
		modalElement.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('modal-open');

		if (adqPdfBackdropFallback) {
			adqPdfBackdropFallback.remove();
			adqPdfBackdropFallback = null;
		}

		modalElement.dispatchEvent(new Event('hidden.bs.modal'));
	}

	function recargarVistaTecnologia() {
		if (typeof window.recargarVistaActualAdquisiciones === 'function') {
			return window.recargarVistaActualAdquisiciones();
		}
		window.location.reload();
		return Promise.resolve();
	}

	function cambiarAnioDetalle() {
		const anio = document.getElementById('filtroAnioPedidos').value;
		const url = 'index.php?module=adquisiciones&action=tecnologia&id=' + idTecnologia + '&anio=' + anio;
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	function fileToBase64(file) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onload = () => {
				const resultado = String(reader.result || '');
				const partes = resultado.split(',');
				resolve(partes.length > 1 ? partes[1] : '');
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}

	async function enviarJson(url, payload) {
		const respuesta = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(payload)
		});

		if (!respuesta.ok) {
			const texto = await respuesta.text();
			throw new Error('Error del servidor (' + respuesta.status + '): ' + texto.substring(0, 250));
		}

		return respuesta.json();
	}

	function validarPdf(file) {
		if (!file) {
			throw new Error('Debe seleccionar un archivo PDF.');
		}

		if (!file.name.toLowerCase().endsWith('.pdf')) {
			throw new Error('Solo se permiten archivos PDF.');
		}
	}

	function abrirPdfEnModal(url) {
		const iframe = document.getElementById('iframeVisorPdf');
		const modalElement = document.getElementById('modalVisorPdf');

		if (!iframe || !modalElement) {
			window.adqNotifySafe('danger', 'Error', 'El modal no está disponible.');
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
			if (!BootstrapModal && !(typeof $ !== 'undefined' && $.fn.modal)) {
				cerrarModalFallback(modalElement);
			}
		});
	}

	function abrirModalAgregarFichaTecnica() {
		const modalElement = document.getElementById('modalAgregarFichaTecnica');
		if (!modalElement) {
			return false;
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).show();
			return false;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('show');
			return false;
		}

		abrirModalFallback(modalElement);
		return false;
	}

	function cerrarModalAgregarFichaTecnica() {
		const modalElement = document.getElementById('modalAgregarFichaTecnica');
		if (!modalElement) {
			return false;
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).hide();
			return false;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('hide');
			return false;
		}

		cerrarModalFallback(modalElement);
		return false;
	}

	function limpiarFormularioFichaTecnica() {
		const form = document.getElementById('form-ficha-tecnica');
		if (form) {
			form.reset();
		}
	}

	function inicializarModalAgregarFichaTecnica() {
		const modalElement = document.getElementById('modalAgregarFichaTecnica');
		if (!modalElement) {
			return;
		}

		if (modalElement.dataset.adqFichaInit === '1') {
			return;
		}
		modalElement.dataset.adqFichaInit = '1';

		modalElement.addEventListener('hidden.bs.modal', limpiarFormularioFichaTecnica);

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).on('hidden.bs.modal', limpiarFormularioFichaTecnica);
		}
	}

	function abrirModalEspecificacionTecnica(modo, idEspecificacion) {
		const modalElement = document.getElementById('modalEspecificacionTecnica');
		if (!modalElement) {
			return false;
		}

		const modoFormulario = modo === 'editar' ? 'editar' : 'crear';
		const inputModo = document.getElementById('et_modal_modo');
		const inputId = document.getElementById('et_modal_id');
		const inputCodigo = document.getElementById('et_codigo_modal');
		const inputCodigoVisual = document.getElementById('et_codigo_modal_visual');
		const labelDocumento = document.getElementById('et_documento_label');
		const titulo = document.getElementById('modalEspecificacionTecnicaLabel');
		const botonSubmit = document.getElementById('et_btn_submit');

		if (inputModo) {
			inputModo.value = modoFormulario;
		}
		if (inputId) {
			inputId.value = modoFormulario === 'editar' ? String(parseInt(idEspecificacion, 10) || 0) : '0';
		}
		const codigoGenerado = generarCodigoEspecificacion();
		if (inputCodigo) {
			inputCodigo.value = codigoGenerado;
		}
		if (inputCodigoVisual) {
			inputCodigoVisual.value = formatearCodigoEspecificacionVisual(codigoGenerado);
		}

		if (titulo) {
			titulo.textContent = modoFormulario === 'editar' ? 'Actualizar Especificación Técnica' : 'Agregar Especificación Técnica';
		}
		if (labelDocumento) {
			labelDocumento.textContent = modoFormulario === 'editar' ? 'Nuevo Documento PDF' : 'Documento PDF';
		}
		if (botonSubmit) {
			botonSubmit.textContent = modoFormulario === 'editar' ? 'Actualizar' : 'Guardar';
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).show();
			return false;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('show');
			return false;
		}

		abrirModalFallback(modalElement);
		return false;
	}

	function cerrarModalEspecificacionTecnica() {
		const modalElement = document.getElementById('modalEspecificacionTecnica');
		if (!modalElement) {
			return false;
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).hide();
			return false;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('hide');
			return false;
		}

		cerrarModalFallback(modalElement);
		return false;
	}

	function limpiarFormularioEspecificacionTecnica() {
		const form = document.getElementById('form-especificacion-modal');
		if (form) {
			form.reset();
		}

		const inputModo = document.getElementById('et_modal_modo');
		const inputId = document.getElementById('et_modal_id');
		const inputCodigo = document.getElementById('et_codigo_modal');
		const inputCodigoVisual = document.getElementById('et_codigo_modal_visual');
		const titulo = document.getElementById('modalEspecificacionTecnicaLabel');
		const labelDocumento = document.getElementById('et_documento_label');
		const botonSubmit = document.getElementById('et_btn_submit');

		if (inputModo) {
			inputModo.value = 'crear';
		}
		if (inputId) {
			inputId.value = '0';
		}
		const codigoGenerado = generarCodigoEspecificacion();
		if (inputCodigo) {
			inputCodigo.value = codigoGenerado;
		}
		if (inputCodigoVisual) {
			inputCodigoVisual.value = formatearCodigoEspecificacionVisual(codigoGenerado);
		}
		if (titulo) {
			titulo.textContent = 'Agregar Especificación Técnica';
		}
		if (labelDocumento) {
			labelDocumento.textContent = 'Documento PDF';
		}
		if (botonSubmit) {
			botonSubmit.textContent = 'Guardar';
		}
	}

	function inicializarModalEspecificacionTecnica() {
		const modalElement = document.getElementById('modalEspecificacionTecnica');
		if (!modalElement) {
			return;
		}

		if (modalElement.dataset.adqEtInit === '1') {
			return;
		}
		modalElement.dataset.adqEtInit = '1';

		limpiarFormularioEspecificacionTecnica();
		modalElement.addEventListener('hidden.bs.modal', limpiarFormularioEspecificacionTecnica);

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).on('hidden.bs.modal', limpiarFormularioEspecificacionTecnica);
		}
	}

	function abrirModalOrdenCompra(modo, idOrdenCompra, fechaEntrega) {
		const modalElement = document.getElementById('modalOrdenCompra');
		if (!modalElement) {
			return false;
		}

		const modoFormulario = modo === 'editar' ? 'editar' : 'crear';
		const inputModo = document.getElementById('oc_modal_modo');
		const inputId = document.getElementById('oc_modal_id');
		const inputNumero = document.getElementById('oc_numero_orden_modal');
		const inputFecha = document.getElementById('oc_fecha_entrega_modal');
		const inputDocumento = document.getElementById('oc_documento_modal');
		const labelDocumento = document.getElementById('oc_documento_label');
		const hintDocumento = document.getElementById('oc_documento_hint');
		const titulo = document.getElementById('modalOrdenCompraLabel');
		const botonSubmit = document.getElementById('oc_btn_submit');

		if (inputModo) {
			inputModo.value = modoFormulario;
		}
		if (inputId) {
			inputId.value = modoFormulario === 'editar' ? String(parseInt(idOrdenCompra, 10) || 0) : '0';
		}
		if (inputNumero) {
			inputNumero.value = generarNumeroOrdenCompra(anioActual);
		}
		if (inputFecha) {
			inputFecha.value = modoFormulario === 'editar' ? String(fechaEntrega || '') : '';
		}
		if (inputDocumento) {
			inputDocumento.required = modoFormulario !== 'editar';
		}

		if (titulo) {
			titulo.textContent = modoFormulario === 'editar' ? 'Actualizar Orden de Compra' : 'Agregar Orden de Compra';
		}
		if (labelDocumento) {
			labelDocumento.textContent = modoFormulario === 'editar' ? 'Nuevo Documento PDF (opcional)' : 'Documento PDF';
		}
		if (hintDocumento) {
			hintDocumento.textContent = modoFormulario === 'editar'
				? 'Si no selecciona un archivo, se conservará el PDF actual.'
				: '';
		}
		if (botonSubmit) {
			botonSubmit.textContent = modoFormulario === 'editar' ? 'Actualizar' : 'Guardar';
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).show();
			return false;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('show');
			return false;
		}

		abrirModalFallback(modalElement);
		return false;
	}

	function cerrarModalOrdenCompra() {
		const modalElement = document.getElementById('modalOrdenCompra');
		if (!modalElement) {
			return false;
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).hide();
			return false;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('hide');
			return false;
		}

		cerrarModalFallback(modalElement);
		return false;
	}

	function limpiarFormularioOrdenCompra() {
		const form = document.getElementById('form-orden-compra-modal');
		if (form) {
			form.reset();
		}

		const inputModo = document.getElementById('oc_modal_modo');
		const inputId = document.getElementById('oc_modal_id');
		const inputNumero = document.getElementById('oc_numero_orden_modal');
		const inputDocumento = document.getElementById('oc_documento_modal');
		const labelDocumento = document.getElementById('oc_documento_label');
		const hintDocumento = document.getElementById('oc_documento_hint');
		const titulo = document.getElementById('modalOrdenCompraLabel');
		const botonSubmit = document.getElementById('oc_btn_submit');

		if (inputModo) {
			inputModo.value = 'crear';
		}
		if (inputId) {
			inputId.value = '0';
		}
		if (inputNumero) {
			inputNumero.value = generarNumeroOrdenCompra(anioActual);
		}
		if (inputDocumento) {
			inputDocumento.required = true;
		}
		if (titulo) {
			titulo.textContent = 'Agregar Orden de Compra';
		}
		if (labelDocumento) {
			labelDocumento.textContent = 'Documento PDF';
		}
		if (hintDocumento) {
			hintDocumento.textContent = '';
		}
		if (botonSubmit) {
			botonSubmit.textContent = 'Guardar';
		}
	}

	function inicializarModalOrdenCompra() {
		const modalElement = document.getElementById('modalOrdenCompra');
		if (!modalElement) {
			return;
		}

		if (modalElement.dataset.adqOcInit === '1') {
			return;
		}
		modalElement.dataset.adqOcInit = '1';

		limpiarFormularioOrdenCompra();
		modalElement.addEventListener('hidden.bs.modal', limpiarFormularioOrdenCompra);

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).on('hidden.bs.modal', limpiarFormularioOrdenCompra);
		}
	}

	inicializarModalVisorPdf();
	inicializarModalAgregarFichaTecnica();
	inicializarModalEspecificacionTecnica();
	inicializarModalOrdenCompra();

	async function guardarFichaTecnica(e) {
		e.preventDefault();

		try {
			const marca = document.getElementById('ficha_marca').value.trim();
			const modelo = document.getElementById('ficha_modelo').value.trim();
			const file = document.getElementById('ficha_documento').files[0];

			if (!marca || !modelo) {
				throw new Error('Marca y modelo son obligatorios.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson('index.php?module=adquisiciones&action=guardarFichaTecnicaAjax', {
				IdCatalogoTecnologico: idTecnologia,
				Marca: marca,
				Modelo: modelo,
				Anio: anioActual,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo guardar la ficha técnica.');
			}

			cerrarModalAgregarFichaTecnica();
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al guardar ficha tecnica', error.message || 'Error al guardar la ficha tecnica.');
		}

		return false;
	}

	async function eliminarFichaTecnica(id) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: '¿Desea eliminar esta ficha técnica?',
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=eliminarFichaTecnicaAjax', {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || 'No se pudo eliminar la ficha técnica.');
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar ficha tecnica', error.message || 'Error al eliminar la ficha tecnica.');
		}
	}

	async function cambiarEstadoFichaTecnica(id, estado) {
		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=cambiarEstadoFichaTecnicaAjax', {
				Id: id,
				Estado: estado
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo cambiar el estado.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al cambiar estado', error.message || 'Error al cambiar el estado.');
		}
	}

	async function moverFichaTecnicaRango(id, direccion) {
		if (!['up', 'down'].includes(direccion)) {
			window.adqNotifySafe('danger', 'Error', 'Dirección inválida para mover la ficha técnica.');
			return;
		}

		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=moverFichaTecnicaRangoAjax', {
				Id: id,
				Direccion: direccion
			});

			if (!data.ok) {
				throw new Error(data.error || 'No se pudo cambiar el rango de la ficha técnica.');
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al mover ficha tecnica', error.message || 'Error al cambiar el rango de la ficha técnica.');
		}
	}

	async function submitEspecificacionTecnica(e) {
		e.preventDefault();

		try {
			const modo = document.getElementById('et_modal_modo').value;
			const idEspecificacion = parseInt(document.getElementById('et_modal_id').value, 10);
			const codigo = document.getElementById('et_codigo_modal').value.trim();
			const file = document.getElementById('et_documento_modal').files[0];

			if (!codigo) {
				throw new Error('El código de especificación técnica es obligatorio.');
			}
			if (codigo.length > 50) {
				throw new Error('El código de especificación técnica no puede exceder 50 caracteres.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			let data = null;

			if (modo === 'editar') {
				if (!idEspecificacion) {
					throw new Error('Faltan datos para actualizar la especificación técnica.');
				}

				data = await enviarJson('index.php?module=adquisiciones&action=actualizarEspecificacionTecnicaAjax', {
					Id: idEspecificacion,
					Codigo: codigo,
					Documento: documentoBase64
				});
			} else {
				data = await enviarJson('index.php?module=adquisiciones&action=guardarEspecificacionTecnicaAjax', {
					IdCatalogoTecnologico: idTecnologia,
					Codigo: codigo,
					Anio: anioActual,
					Documento: documentoBase64
				});
			}

			if (!data.ok) {
				throw new Error(data.error || (modo === 'editar'
					? 'No se pudo actualizar la especificación técnica.'
					: 'No se pudo guardar la especificación técnica.'));
			}

			cerrarModalEspecificacionTecnica();
			await recargarVistaTecnologia();
		} catch (error) {
			const modo = (document.getElementById('et_modal_modo') || {}).value || 'crear';
			window.adqNotifySafe(
				'danger',
				modo === 'editar' ? 'Error al actualizar especificacion' : 'Error al guardar especificacion',
				error.message || (modo === 'editar'
					? 'Error al actualizar la especificacion tecnica.'
					: 'Error al guardar la especificacion tecnica.')
			);
		}

		return false;
	}

	async function eliminarEspecificacionTecnica(id) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: '¿Desea eliminar esta especificación técnica?',
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=eliminarEspecificacionTecnicaAjax', {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || 'No se pudo eliminar la especificación técnica.');
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar especificacion', error.message || 'Error al eliminar la especificacion tecnica.');
		}
	}

	function normalizarFechaEntrega(valor) {
		const fecha = (valor || '').trim();
		return fecha !== '' ? fecha : null;
	}

	async function submitOrdenCompraModal(e) {
		e.preventDefault();

		try {
			const modo = document.getElementById('oc_modal_modo').value;
			const idOrden = parseInt(document.getElementById('oc_modal_id').value, 10);
			const numeroOrden = generarNumeroOrdenCompra(anioActual);
			const fechaEntrega = normalizarFechaEntrega(document.getElementById('oc_fecha_entrega_modal').value);
			const file = document.getElementById('oc_documento_modal').files[0];

			if (!numeroOrden) {
				throw new Error('No se pudo generar el número de orden.');
			}
			if (numeroOrden.length > 25) {
				throw new Error('El número de orden generado excede 25 caracteres.');
			}

			let data = null;
			let documentoBase64 = null;

			if (modo === 'editar') {
				if (!idOrden) {
					throw new Error('Faltan datos para actualizar la orden de compra.');
				}

				if (file) {
					validarPdf(file);
					documentoBase64 = await fileToBase64(file);
				}

				data = await enviarJson('index.php?module=adquisiciones&action=actualizarOrdenCompraAjax', {
					Id: idOrden,
					NumeroOrden: numeroOrden,
					FechaEntrega: fechaEntrega,
					Documento: documentoBase64
				});
			} else {
				validarPdf(file);
				documentoBase64 = await fileToBase64(file);

				data = await enviarJson('index.php?module=adquisiciones&action=guardarOrdenCompraAjax', {
					IdCatalogoTecnologico: idTecnologia,
					NumeroOrden: numeroOrden,
					FechaEntrega: fechaEntrega,
					Anio: anioActual,
					Documento: documentoBase64
				});
			}

			if (!data.ok) {
				throw new Error(data.error || (modo === 'editar'
					? 'No se pudo actualizar la orden de compra.'
					: 'No se pudo guardar la orden de compra.'));
			}

			cerrarModalOrdenCompra();
			await recargarVistaTecnologia();
		} catch (error) {
			const modo = (document.getElementById('oc_modal_modo') || {}).value || 'crear';
			window.adqNotifySafe(
				'danger',
				modo === 'editar' ? 'Error al actualizar orden de compra' : 'Error al guardar orden de compra',
				error.message || (modo === 'editar'
					? 'Error al actualizar la orden de compra.'
					: 'Error al guardar la orden de compra.')
			);
		}

		return false;
	}

	async function eliminarOrdenCompra(id) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: '¿Desea eliminar esta orden de compra?',
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson('index.php?module=adquisiciones&action=eliminarOrdenCompraAjax', {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || 'No se pudo eliminar la orden de compra.');
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar orden de compra', error.message || 'Error al eliminar la orden de compra.');
		}
	}

	async function guardarDocumentoConObservacion(e, options) {
		e.preventDefault();

		try {
			const observacion = document.getElementById(options.observacionId).value.trim();
			const file = document.getElementById(options.documentoId).files[0];

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson(options.url, {
				IdCatalogoTecnologico: idTecnologia,
				Observacion: observacion,
				Anio: anioActual,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || options.errorGuardar);
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al guardar documento', error.message || options.errorGuardar);
		}

		return false;
	}

	async function actualizarDocumentoConObservacion(e, options) {
		e.preventDefault();

		try {
			const idDocumento = parseInt(document.getElementById(options.idId).value, 10);
			const observacion = document.getElementById(options.observacionId).value.trim();
			const file = document.getElementById(options.documentoId).files[0];

			if (!idDocumento) {
				throw new Error('Faltan datos para actualizar el documento.');
			}

			validarPdf(file);
			const documentoBase64 = await fileToBase64(file);
			const data = await enviarJson(options.url, {
				Id: idDocumento,
				Observacion: observacion,
				Documento: documentoBase64
			});

			if (!data.ok) {
				throw new Error(data.error || options.errorActualizar);
			}

			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al actualizar documento', error.message || options.errorActualizar);
		}

		return false;
	}

	async function eliminarDocumentoSimple(id, options) {
		const confirmado = await window.adqConfirmSafe({
			titulo: 'Confirmar eliminacion',
			mensaje: options.confirmacion,
			textoAceptar: 'Eliminar',
			textoCancelar: 'Cancelar',
			claseAceptar: 'btn-danger'
		});

		if (!confirmado) {
			return;
		}

		try {
			const data = await enviarJson(options.url, {
				Id: id
			});
			if (!data.ok) {
				throw new Error(data.error || options.errorEliminar);
			}
			await recargarVistaTecnologia();
		} catch (error) {
			window.adqNotifySafe('danger', 'Error al eliminar documento', error.message || options.errorEliminar);
		}
	}

	function guardarVerificacionTecnica(e) {
		return guardarDocumentoConObservacion(e, {
			observacionId: 'vt_observacion',
			documentoId: 'vt_documento',
			url: 'index.php?module=adquisiciones&action=guardarVerificacionTecnicaAjax',
			errorGuardar: 'No se pudo guardar la verificación técnica.'
		});
	}

	function actualizarVerificacionTecnica(e) {
		return actualizarDocumentoConObservacion(e, {
			idId: 'vt_id',
			observacionId: 'vt_observacion_upd',
			documentoId: 'vt_documento_upd',
			url: 'index.php?module=adquisiciones&action=actualizarVerificacionTecnicaAjax',
			errorActualizar: 'No se pudo actualizar la verificación técnica.'
		});
	}

	function eliminarVerificacionTecnica(id) {
		return eliminarDocumentoSimple(id, {
			url: 'index.php?module=adquisiciones&action=eliminarVerificacionTecnicaAjax',
			confirmacion: '¿Desea eliminar esta verificación técnica?',
			errorEliminar: 'No se pudo eliminar la verificación técnica.'
		});
	}

	function guardarConformidad(e) {
		return guardarDocumentoConObservacion(e, {
			observacionId: 'cf_observacion',
			documentoId: 'cf_documento',
			url: 'index.php?module=adquisiciones&action=guardarConformidadAjax',
			errorGuardar: 'No se pudo guardar la conformidad.'
		});
	}

	function actualizarConformidad(e) {
		return actualizarDocumentoConObservacion(e, {
			idId: 'cf_id',
			observacionId: 'cf_observacion_upd',
			documentoId: 'cf_documento_upd',
			url: 'index.php?module=adquisiciones&action=actualizarConformidadAjax',
			errorActualizar: 'No se pudo actualizar la conformidad.'
		});
	}

	function eliminarConformidad(id) {
		return eliminarDocumentoSimple(id, {
			url: 'index.php?module=adquisiciones&action=eliminarConformidadAjax',
			confirmacion: '¿Desea eliminar esta conformidad?',
			errorEliminar: 'No se pudo eliminar la conformidad.'
		});
	}
</script>