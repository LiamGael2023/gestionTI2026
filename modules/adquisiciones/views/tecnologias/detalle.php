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
$puedeRegistrarEspecificacion = $tieneFichas;
$puedeRegistrarOrdenCompra = $tieneFichas && $tieneEspecificacion;
$puedeRegistrarVerificacion = $tieneFichas && $tieneEspecificacion && $tieneOrdenCompra;
$estaFinalizada = !empty($cierreAdquisicion) && (int) ($cierreAdquisicion['Estado'] ?? 0) === 1;
$fechaCierre = $estaFinalizada && !empty($cierreAdquisicion['FechaFinalizacion'])
	? htmlspecialchars((string) $cierreAdquisicion['FechaFinalizacion'])
	: null;
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

	<!-- Tarjeta con tabla de pedidos de compra y filtro de año, con alerta de diferencias de código SIGA -->
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

	<!-- Contenedor para incluir partials de ficha técnica, especificación, orden y verificación -->
	<?php require __DIR__ . '/partials/ficha.php'; ?>
	<?php require __DIR__ . '/partials/especificacion.php'; ?>
	<?php require __DIR__ . '/partials/orden.php'; ?>
	<?php require __DIR__ . '/partials/verificacion.php'; ?>

	<!-- Tarjeta con estado de adquisición y botón para finalizar o aperturar -->
	<?php if ($tieneVerificacion): ?>
	<div class="card card-body mb-3">
		<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

			<div>
				<h4 class="fw-bold mb-1">Estado de Adquisición</h4>
				<span id="badge-cierre"
					title="<?php echo $estaFinalizada ? ('Finalizado el ' . $fechaCierre) : 'Adquisición en proceso'; ?>"
					class="badge <?php echo $estaFinalizada ? 'bg-success-lt text-success' : 'bg-warning-lt text-dark'; ?>">
					<?php echo $estaFinalizada
						? ('&#10003; Finalizado' . ($fechaCierre ? ' &mdash; ' . $fechaCierre : ''))
					: 'En proceso'; ?>
				</span>
			</div>

			<button id="btn-cierre"
				type="button"
				class="btn <?php echo $estaFinalizada ? 'btn-warning' : 'btn-success'; ?>"
				onclick="cambiarCierreTecnologia(<?php echo $idTec; ?>, <?php echo $anioActual; ?>, <?php echo $estaFinalizada ? 'false' : 'true'; ?>)">
				<?php echo $estaFinalizada ? 'Aperturar' : 'Finalizar'; ?>
			</button>
		</div>
	</div>
	<?php endif; ?>

	<!-- Sección de navegación con botón para volver a la lista de tecnologías -->
	<div class="d-flex justify-content-end mb-3">
		<a href="index.php?module=adquisiciones&action=tecnologias&anio=<?php echo $anioActual; ?>"
			class="btn btn-secondary js-adq-link">Volver</a>
	</div>

</div>

<!-- Incluye el modal para visualizar PDF -->
<?php require __DIR__ . '/partials/documento.php'; ?>

<script>
	const idTecnologia = <?php echo $idTec; ?>;
	const anioActual = <?php echo $anioActual; ?>;
	const codigoTecnologia = <?php echo json_encode($codigoTecRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const nombreTecnologia = <?php echo json_encode($nombreTecRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	let adqPdfBackdropFallback = null;

	// Normaliza texto eliminando acentos y convirtiendo a mayúsculas
	function normalizarTextoAscii(valor) {
		return String(valor || '')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.toUpperCase()
			.trim();
	}

	// Extrae el token de código de tecnología (ej: T123) de la cadena normalizada
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

	// Extrae la primera palabra de la descripción normalizada de la tecnología
	function obtenerPrimeraPalabraDescripcion() {
		const descripcionLimpia = normalizarTextoAscii(nombreTecnologia);
		const token = descripcionLimpia.split(/[^A-Z0-9]+/).filter(Boolean)[0];
		return token || 'TECNOLOGIA';
	}

	// Genera el código de especificación técnica usando token, palabra y año
	function generarCodigoEspecificacion() {
		const tokenTecnologia = obtenerTokenCodigoTecnologia();
		const primeraPalabra = obtenerPrimeraPalabraDescripcion();
		return 'ET_' + tokenTecnologia + '_' + primeraPalabra + '_' + anioActual;
	}

	// Genera el número de orden de compra respetando longitud máxima
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

	// Convierte código de especificación con guiónes a espacios para mostrar visualmente
	function formatearCodigoEspecificacionVisual(codigo) {
		return String(codigo || '').replace(/_/g, ' ').trim();
	}

	// Obtiene la instancia de Bootstrap Modal si está disponible en el navegador
	function obtenerBootstrapModal() {
		if (typeof window !== 'undefined' && window.bootstrap && window.bootstrap.Modal) {
			return window.bootstrap.Modal;
		}

		if (typeof bootstrap !== 'undefined' && bootstrap && bootstrap.Modal) {
			return bootstrap.Modal;
		}

		return null;
	}

	// Abre un modal manualmente sin Bootstrap (fallback)
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

	// Cierra un modal manualmente sin Bootstrap (fallback) y elimina el backdrop
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

	// Recarga la vista actual de tecnología o la página completa
	function recargarVistaTecnologia() {
		if (typeof window.recargarVistaActualAdquisiciones === 'function') {
			return window.recargarVistaActualAdquisiciones();
		}
		window.location.reload();
		return Promise.resolve();
	}

	// Cambia el año en el filtro y recarga la página con ese año
	function cambiarAnioDetalle() {
		const anio = document.getElementById('filtroAnioPedidos').value;
		const url = 'index.php?module=adquisiciones&action=tecnologia&id=' + idTecnologia + '&anio=' + anio;
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	// Convierte un archivo a cadena base64 para envíos al servidor
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

	// Envía datos JSON al servidor mediante petición POST
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

	// Valida que el archivo sea un PDF válido
	function validarPdf(file) {
		if (!file) {
			throw new Error('Debe seleccionar un archivo PDF.');
		}

		if (!file.name.toLowerCase().endsWith('.pdf')) {
			throw new Error('Solo se permiten archivos PDF.');
		}
	}

	// Abre un modal con soporte para Bootstrap, jQuery o fallback manual
	function mostrarModal(modalElement) {
		if (!modalElement) {
			return false;
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).show();
			return true;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('show');
			return true;
		}

		abrirModalFallback(modalElement);
		return true;
	}

	// Cierra un modal con soporte para Bootstrap, jQuery o fallback manual
	function ocultarModal(modalElement) {
		if (!modalElement) {
			return false;
		}

		const BootstrapModal = obtenerBootstrapModal();
		if (BootstrapModal) {
			BootstrapModal.getOrCreateInstance(modalElement).hide();
			return true;
		}

		if (typeof $ !== 'undefined' && $.fn.modal) {
			$(modalElement).modal('hide');
			return true;
		}

		cerrarModalFallback(modalElement);
		return true;
	}

	// Configura un modal para limpiar formulario al cerrarse con soporte para múltiples eventos
	function inicializarModalConLimpieza(config) {
		const modalElement = document.getElementById(config.modalId);
		if (!modalElement) {
			return;
		}

		if (modalElement.dataset[config.datasetKey] === '1') {
			return;
		}
		modalElement.dataset[config.datasetKey] = '1';

		if (typeof config.extraInitFn === 'function') {
			config.extraInitFn(modalElement);
		}

		if (typeof config.limpiarFn === 'function') {
			config.limpiarFn();
			modalElement.addEventListener('hidden.bs.modal', config.limpiarFn);

			if (typeof $ !== 'undefined' && $.fn.modal) {
				$(modalElement).on('hidden.bs.modal', config.limpiarFn);
			}
		}
	}

	inicializarModalVisorPdf();
	inicializarModalAgregarFichaTecnica();
	inicializarModalEspecificacionTecnica();
	inicializarModalOrdenCompra();

	async function cambiarCierreTecnologia(idTec, anio, finalizar) {
		const btn = document.getElementById('btn-cierre');
		const badge = document.getElementById('badge-cierre');
		const accion = finalizar ? 'finalizar' : 'aperturar';

		const confirmado = await window.adqConfirmSafe({
			titulo: finalizar ? 'Finalizar adquisición' : 'Aperturar adquisición',
			mensaje: finalizar
				? '¿Desea finalizar la adquisición para el año ' + anio + '? Ya no se podrán registrar nuevos documentos.'
				: '¿Desea aperturar nuevamente la adquisición para el año ' + anio + '?',
			textoAceptar: finalizar ? 'Finalizar' : 'Aperturar',
			textoCancelar: 'Cancelar',
			claseAceptar: finalizar ? 'btn-success' : 'btn-warning'
		});

		if (!confirmado) return;

		if (btn) btn.disabled = true;

		try {
			const res = await enviarJson(
				'index.php?module=adquisiciones&action=cambiarCierreTecnologiaAjax',
				{ IdCatalogoTecnologico: idTec, Anio: anio, Accion: accion }
			);

			if (!res.ok) {
				alert('Error: ' + (res.error || 'No se pudo cambiar el estado.'));
				return;
			}

			const esFinalizado = res.finalizado;

			// Actualizar badge
			if (badge) {
						badge.innerHTML = esFinalizado
							? ('\u2713 Finalizado' + (res.fecha ? ' \u2014 ' + res.fecha : ''))
							: 'En proceso';
						badge.className = 'badge '
							+ (esFinalizado ? 'bg-success-lt text-success' : 'bg-warning-lt text-dark');
						badge.title = esFinalizado ? ('Finalizado el ' + (res.fecha || '')) : 'Adquisición en proceso';
			}

			// Actualizar botón
			if (btn) {
				btn.textContent = esFinalizado ? 'Aperturar' : 'Finalizar';
				btn.className = 'btn ' + (esFinalizado ? 'btn-warning' : 'btn-success');
				btn.setAttribute('onclick',
					'cambiarCierreTecnologia(' + idTec + ', ' + anio + ', ' + (esFinalizado ? 'false' : 'true') + ')'
				);
			}
		} catch (err) {
			alert('Error de conexión: ' + err.message);
		} finally {
			if (btn) btn.disabled = false;
		}
	}
	inicializarModalVerificacionTecnica();

	// Guarda un documento con observación (especificación, orden, verificación) al servidor
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

	// Actualiza un documento existente con nueva observación y/o documento PDF
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

	// Solicita confirmación y elimina un documento (especificación, orden, verificación)
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

</script>