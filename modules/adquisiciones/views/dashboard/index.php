<?php
$anioFiltro = isset($anioFiltro) ? (int) $anioFiltro : (int) date('Y');
$aniosDisponibles = isset($aniosDisponibles) && is_array($aniosDisponibles) ? $aniosDisponibles : [];
if (empty($aniosDisponibles)) {
	$aniosDisponibles = [$anioFiltro];
}

$resumen = isset($dashboardResumenGeneral) && is_array($dashboardResumenGeneral)
	? $dashboardResumenGeneral
	: [];

$itemsPorTipo = isset($dashboardItemsPorTipo) && is_array($dashboardItemsPorTipo)
	? $dashboardItemsPorTipo
	: [];

$resumenCentroCosto = isset($dashboardCentroCosto) && is_array($dashboardCentroCosto)
	? $dashboardCentroCosto
	: [];

$estadoDocumental = isset($dashboardEstadoDocumental) && is_array($dashboardEstadoDocumental)
	? $dashboardEstadoDocumental
	: [];

$ordenesProximas = isset($dashboardOrdenesProximas) && is_array($dashboardOrdenesProximas)
	? $dashboardOrdenesProximas
	: [];

$totalRequerimientos = (int) ($resumen['TotalRequerimientos'] ?? 0);
$requerimientosCompletos = (int) ($resumen['Completos'] ?? 0);
$requerimientosPendientes = (int) ($resumen['Pendientes'] ?? 0);
$totalItems = (int) ($resumen['TotalItems'] ?? 0);
$sinHomologar = (int) ($resumen['SinHomologar'] ?? 0);

$totalTecnologias = (int) ($estadoDocumental['TotalTecnologias'] ?? 0);
$conFichas = (int) ($estadoDocumental['ConFichas'] ?? 0);
$conEspecificacion = (int) ($estadoDocumental['ConEspecificacion'] ?? 0);
$conOrdenCompra = (int) ($estadoDocumental['ConOrdenCompra'] ?? 0);
$conVerificacion = (int) ($estadoDocumental['ConVerificacion'] ?? 0);
$tecnologiasCompletas = (int) ($estadoDocumental['Completas'] ?? 0);

$totalOrdenesProximas = (int) ($ordenesProximas['total'] ?? 0);
$diasVentanaEntrega = (int) ($ordenesProximas['diasVentana'] ?? 30);
$listaOrdenesProximas = isset($ordenesProximas['ordenes']) && is_array($ordenesProximas['ordenes'])
	? $ordenesProximas['ordenes']
	: [];

$porcentajeCompletos = $totalRequerimientos > 0
	? round(($requerimientosCompletos / $totalRequerimientos) * 100)
	: 0;

$porcentajeSinHomologar = $totalItems > 0
	? round(($sinHomologar / $totalItems) * 100)
	: 0;

$porcentajeTecnologiasCompletas = $totalTecnologias > 0
	? round(($tecnologiasCompletas / $totalTecnologias) * 100)
	: 0;

$totalCentrosConRequerimientos = 0;
foreach ($resumenCentroCosto as $filaCentroCosto) {
	if ((int) ($filaCentroCosto['TotalRequerimientos'] ?? 0) > 0) {
		$totalCentrosConRequerimientos++;
	}
}
$totalCentrosCosto = count($resumenCentroCosto);

function formatearFechaEntregaDashboard($fecha)
{
	if ($fecha instanceof DateTime) {
		return $fecha->format('d/m/Y');
	}

	$fechaTexto = trim((string) $fecha);
	if ($fechaTexto === '') {
		return '-';
	}

	$timestamp = strtotime($fechaTexto);
	return $timestamp ? date('d/m/Y', $timestamp) : $fechaTexto;
}
?>
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

	.adq-dashboard .adq-card-clickable {
		cursor: pointer;
	}

	.adq-dashboard .adq-card-clickable:focus-visible {
		outline: 2px solid rgba(32, 107, 196, 0.35);
		outline-offset: 2px;
	}

	.adq-dashboard .adq-card-arrow {
		font-size: 1.05rem;
		opacity: 0.72;
	}

	.adq-dashboard .adq-btn-action {
		font-weight: 600;
		min-width: 84px;
		border-radius: 0.375rem !important;
	}
</style>
<div class="adq-dashboard">
	<form method="GET" action="index.php" class="mb-3">
		<input type="hidden" name="module" value="adquisiciones">
		<input type="hidden" name="action" value="dashboard">
		<div class="row g-2 align-items-center">
			<div class="col-auto">
				<label for="filtroAnioDashboard" class="form-label mb-0">Filtrar por año:</label>
			</div>
			<div class="col-auto">
				<select id="filtroAnioDashboard" name="anio" class="form-select" onchange="this.form.submit()">
					<?php foreach ($aniosDisponibles as $anio): ?>
						<option value="<?php echo (int) $anio; ?>" <?php echo (int) $anio === $anioFiltro ? 'selected' : ''; ?>>
							<?php echo (int) $anio; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</form>

	<div class="row g-3 mb-3">
		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-blue-lt text-blue"><i class="ti ti-clipboard-list"></i></span>
						<div class="h1 mb-0"><?php echo number_format($totalRequerimientos); ?></div>
					</div>
					<div class="fw-bold">Requerimientos</div>
					<div class="text-secondary small">Completos: <?php echo number_format($requerimientosCompletos); ?> · Pendientes: <?php echo number_format($requerimientosPendientes); ?></div>
					<div class="progress progress-sm mt-3">
						<div class="progress-bar bg-blue" style="width: <?php echo $porcentajeCompletos; ?>%"></div>
					</div>
					<div class="d-flex justify-content-between align-items-center mt-2 small text-secondary">
						<span><?php echo $porcentajeCompletos; ?>% completados</span>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-orange-lt text-orange"><i class="ti ti-package"></i></span>
						<div class="h1 mb-0"><?php echo number_format($totalItems); ?></div>
					</div>
					<div class="fw-bold">Items Cargados</div>
					<div class="text-secondary small">Sin homologar: <?php echo number_format($sinHomologar); ?> registros.</div>
					<div class="progress progress-sm mt-3">
						<div class="progress-bar bg-orange" style="width: <?php echo $porcentajeSinHomologar; ?>%"></div>
					</div>
					<div class="d-flex justify-content-between align-items-center mt-2 small text-secondary">
						<span><?php echo $porcentajeSinHomologar; ?>% sin homologación</span>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100 adq-card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalGestionTecnologias" aria-label="Abrir gestión de tecnologías">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-green-lt text-green"><i class="ti ti-device-desktop"></i></span>
						<div class="d-flex align-items-center gap-2">
							<div class="h1 mb-0" id="cardTotalTecnologias"><?php echo number_format($totalTecnologias); ?></div>
							<i class="ti ti-arrow-up-right adq-card-arrow text-secondary" aria-hidden="true"></i>
						</div>
					</div>
					<div class="fw-bold">Tecnologías</div>
					<div class="text-secondary small">Catálogo activo. Clic para gestionar.</div>
					<div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
						<span><?php echo number_format($tecnologiasCompletas); ?> completas en <?php echo $anioFiltro; ?></span>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100 adq-card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalGestionCentrosCosto" aria-label="Abrir gestión de centros de costo">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-azure-lt text-azure"><i class="ti ti-building"></i></span>
						<div class="d-flex align-items-center gap-2">
							<div class="h1 mb-0" id="cardTotalCentrosCosto"><?php echo number_format($totalCentrosCosto); ?></div>
							<i class="ti ti-arrow-up-right adq-card-arrow text-secondary" aria-hidden="true"></i>
						</div>
					</div>
					<div class="fw-bold">Centros de Costo</div>
					<div class="text-secondary small">Catálogo activo. Clic para gestionar.</div>
					<div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
						<span><?php echo number_format($totalCentrosConRequerimientos); ?> con requerimientos en <?php echo $anioFiltro; ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3 mb-3">
		<div class="col-6 col-md-4 col-xl-2">
			<div class="card h-100">
				<div class="card-body py-3">
					<div class="text-secondary text-uppercase fw-bold small">Con Fichas</div>
					<div class="h1 mb-1"><?php echo number_format($conFichas); ?></div>
					<div class="text-secondary small">Mínimo 2 fichas</div>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="card h-100">
				<div class="card-body py-3">
					<div class="text-secondary text-uppercase fw-bold small">Con Especificación</div>
					<div class="h1 mb-1"><?php echo number_format($conEspecificacion); ?></div>
					<div class="text-secondary small">Documento técnico</div>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="card h-100">
				<div class="card-body py-3">
					<div class="text-secondary text-uppercase fw-bold small">Con Orden Compra</div>
					<div class="h1 mb-1"><?php echo number_format($conOrdenCompra); ?></div>
					<div class="text-secondary small">Sustento de adquisición</div>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="card h-100">
				<div class="card-body py-3">
					<div class="text-secondary text-uppercase fw-bold small">Con Verificación</div>
					<div class="h1 mb-1"><?php echo number_format($conVerificacion); ?></div>
					<div class="text-secondary small">Validación técnica</div>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="card h-100">
				<div class="card-body py-3">
					<div class="text-secondary text-uppercase fw-bold small">Completas</div>
					<div class="h1 mb-1 text-green"><?php echo number_format($tecnologiasCompletas); ?></div>
					<div class="text-secondary small">Flujo documental total</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3 mb-3">
		<div class="col-12">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h3 class="card-title mb-0">Órdenes próximas a entregar</h3>
					<div class="text-secondary small">
						<?php echo number_format($totalOrdenesProximas); ?> en próximos <?php echo $diasVentanaEntrega; ?> días
					</div>
				</div>
				<div class="card-body py-3">
					<?php if (empty($listaOrdenesProximas)): ?>
						<div class="text-secondary">No hay entregas programadas para la ventana seleccionada.</div>
					<?php else: ?>
						<?php $filasMinimasProximas = 2; ?>
						<div class="list-group list-group-flush">
							<?php foreach ($listaOrdenesProximas as $orden): ?>
								<?php
								$diasRestantes = (int) ($orden['DiasRestantes'] ?? 0);
								$claseBadge = $diasRestantes <= 7 ? 'bg-red-lt text-red' : 'bg-yellow-lt text-yellow';
								?>
								<div class="list-group-item px-0">
									<div class="d-flex justify-content-between align-items-start gap-2">
										<div>
											<div class="fw-semibold">
												OC <?php echo htmlspecialchars((string) ($orden['NumeroOrden'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
											</div>
											<div class="text-secondary small">
												<?php echo htmlspecialchars((string) ($orden['Codigo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
												<?php if (!empty($orden['NombreGenerico'])): ?>
													- <?php echo htmlspecialchars((string) $orden['NombreGenerico'], ENT_QUOTES, 'UTF-8'); ?>
												<?php endif; ?>
											</div>
										</div>
										<div class="text-end">
											<div class="text-secondary small">Entrega: <?php echo formatearFechaEntregaDashboard($orden['FechaEntrega'] ?? ''); ?></div>
											<span class="badge <?php echo $claseBadge; ?> mt-1"><?php echo $diasRestantes; ?> día(s)</span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
							<?php if (count($listaOrdenesProximas) < $filasMinimasProximas): ?>
								<?php for ($i = count($listaOrdenesProximas); $i < $filasMinimasProximas; $i++): ?>
									<div class="list-group-item px-0 text-secondary small">
										Sin más órdenes próximas dentro de <?php echo $diasVentanaEntrega; ?> días.
									</div>
								<?php endfor; ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3">
		<div class="col-12 col-xl-6">
			<div class="card">
				<div class="card-header">
					<h3 class="card-title">Items por Tipo de Equipo</h3>
				</div>
				<div class="table-responsive">
					<table class="table table-vcenter card-table mb-0">
						<thead>
							<tr>
								<th class="text-secondary text-uppercase small">Código</th>
								<th class="text-secondary text-uppercase small">Nombre Genérico</th>
								<th class="text-secondary text-uppercase small text-end">Cantidad</th>
								<th class="text-secondary text-uppercase small text-end">Items</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($itemsPorTipo)): ?>
								<tr>
									<td colspan="4" class="text-center text-secondary py-4">No hay datos para el año seleccionado.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($itemsPorTipo as $fila): ?>
									<tr>
										<td class="text-secondary"><?php echo htmlspecialchars((string) ($fila['Tipo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars((string) ($fila['NombreGenerico'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="text-end fw-semibold"><?php echo number_format((int) ($fila['TotalCantidad'] ?? 0)); ?></td>
										<td class="text-end"><?php echo number_format((int) ($fila['TotalItems'] ?? 0)); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="col-12 col-xl-6">
			<div class="card">
				<div class="card-header">
					<h3 class="card-title">Requerimientos por Centro de Costo</h3>
				</div>
				<div class="table-responsive">
					<table class="table table-vcenter card-table mb-0">
						<thead>
							<tr>
								<th class="text-secondary text-uppercase small">Siglas</th>
								<th class="text-secondary text-uppercase small">Centro de Costo</th>
								<th class="text-secondary text-uppercase small text-end">Requerimientos</th>
								<th class="text-secondary text-uppercase small text-end">Items</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($resumenCentroCosto)): ?>
								<tr>
									<td colspan="4" class="text-center text-secondary py-4">No hay centros de costo disponibles.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($resumenCentroCosto as $fila): ?>
									<tr>
										<td class="text-secondary"><?php echo htmlspecialchars((string) ($fila['Siglas'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars((string) ($fila['NombreCentroCosto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="text-end fw-semibold"><?php echo number_format((int) ($fila['TotalRequerimientos'] ?? 0)); ?></td>
										<td class="text-end"><?php echo number_format((int) ($fila['TotalItems'] ?? 0)); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="modal modal-blur fade" id="modalGestionCentrosCosto" tabindex="-1" aria-labelledby="modalGestionCentrosCostoLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modalGestionCentrosCostoLabel">Gestión de Centros de Costo</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row g-3 mb-3">
						<div class="col-12 col-md-3">
							<label class="form-label" for="ccSiglas">Siglas</label>
							<input type="text" class="form-control" id="ccSiglas" maxlength="20" placeholder="Ej: OTI">
						</div>
						<div class="col-12 col-md-7">
							<label class="form-label" for="ccNombre">Nombre Centro de Costo</label>
							<input type="text" class="form-control" id="ccNombre" maxlength="255" placeholder="Nombre del centro de costo">
						</div>
						<div class="col-12 col-md-2 d-flex align-items-end">
							<button type="button" class="btn btn-primary w-100" id="btnGuardarCentroCosto">Agregar</button>
						</div>
					</div>
					<input type="hidden" id="ccIdEditar" value="">
					<div class="table-responsive">
						<table class="table table-vcenter card-table table-striped" id="tablaCentrosCostoGestion">
							<thead>
								<tr>
									<th>Siglas</th>
									<th>Centro de Costo</th>
									<th>Estado</th>
									<th class="text-end">Acciones</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="4" class="text-center text-secondary py-4">Cargando...</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal modal-blur fade" id="modalGestionTecnologias" tabindex="-1" aria-labelledby="modalGestionTecnologiasLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modalGestionTecnologiasLabel">Gestión de Tecnologías</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row g-3 mb-3">
						<div class="col-12 col-md-3">
							<label class="form-label" for="tecCodigo">Código</label>
							<input type="text" class="form-control" id="tecCodigo" maxlength="50" placeholder="Código">
						</div>
						<div class="col-12 col-md-7">
							<label class="form-label" for="tecNombre">Nombre Genérico</label>
							<input type="text" class="form-control" id="tecNombre" maxlength="255" placeholder="Nombre genérico">
						</div>
						<div class="col-12 col-md-2 d-flex align-items-end">
							<button type="button" class="btn btn-primary w-100" id="btnGuardarTecnologiaCatalogo">Agregar</button>
						</div>
					</div>
					<input type="hidden" id="tecIdEditar" value="">
					<div class="table-responsive">
						<table class="table table-vcenter card-table table-striped" id="tablaTecnologiasGestion">
							<thead>
								<tr>
									<th>Código</th>
									<th>Nombre Genérico</th>
									<th>Estado</th>
									<th class="text-end">Acciones</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="4" class="text-center text-secondary py-4">Cargando...</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	(function() {
		var modalCentros = document.getElementById('modalGestionCentrosCosto');
		var modalTecnologias = document.getElementById('modalGestionTecnologias');
		if (!modalCentros || !modalTecnologias) {
			return;
		}

		document.querySelectorAll('.adq-card-clickable').forEach(function(card) {
			card.addEventListener('keydown', function(event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					card.click();
				}
			});
		});

		function notificar(tipo, titulo, mensaje) {
			if (window.adqNotifySafe) {
				window.adqNotifySafe(tipo, titulo, mensaje);
				return;
			}
			alert(mensaje || titulo);
		}

		function escaparHtml(valor) {
			return String(valor || '')
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		}

		function limpiarFormularioCentro() {
			document.getElementById('ccIdEditar').value = '';
			document.getElementById('ccSiglas').value = '';
			document.getElementById('ccNombre').value = '';
			document.getElementById('btnGuardarCentroCosto').textContent = 'Agregar';
		}

		function limpiarFormularioTecnologia() {
			document.getElementById('tecIdEditar').value = '';
			document.getElementById('tecCodigo').value = '';
			document.getElementById('tecNombre').value = '';
			document.getElementById('btnGuardarTecnologiaCatalogo').textContent = 'Agregar';
		}

		function cargarCentrosCosto() {
			$.ajax({
				url: 'index.php?module=adquisiciones&action=listarCentrosCostoAjax',
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					var tbody = $('#tablaCentrosCostoGestion tbody');
					if (!response || !response.success) {
						tbody.html('<tr><td colspan="4" class="text-center text-danger py-4">No se pudo cargar la lista.</td></tr>');
						return;
					}

					var data = Array.isArray(response.data) ? response.data : [];
					if (data.length === 0) {
						tbody.html('<tr><td colspan="4" class="text-center text-secondary py-4">No hay centros de costo registrados.</td></tr>');
						return;
					}

					var filas = data.map(function(item) {
						var activo = Number(item.Activo) === 1;
						var badgeEstado = activo ?
							'<span class="badge bg-success-lt">Activo</span>' :
							'<span class="badge bg-secondary-lt">Inactivo</span>';
						var acciones = activo ?
							'<button type="button" class="btn btn-primary adq-btn-action js-editar-cc" data-id="' + Number(item.Id) + '" data-siglas="' + escaparHtml(item.Siglas) + '" data-nombre="' + escaparHtml(item.NombreCentroCosto) + '">Editar</button>' +
							'<button type="button" class="btn btn-danger adq-btn-action js-eliminar-cc" data-id="' + Number(item.Id) + '">Inactivar</button>' :
							'<button type="button" class="btn btn-success adq-btn-action js-activar-cc" data-id="' + Number(item.Id) + '">Activar</button>';

						return '<tr>' +
							'<td>' + escaparHtml(item.Siglas) + '</td>' +
							'<td>' + escaparHtml(item.NombreCentroCosto) + '</td>' +
							'<td>' + badgeEstado + '</td>' +
							'<td class="text-end d-flex justify-content-end flex-wrap gap-1">' +
							acciones +
							'</td>' +
							'</tr>';
					}).join('');

					tbody.html(filas);
				},
				error: function() {
					$('#tablaCentrosCostoGestion tbody').html('<tr><td colspan="4" class="text-center text-danger py-4">Error de conexión.</td></tr>');
				}
			});
		}

		function cargarTecnologias() {
			$.ajax({
				url: 'index.php?module=adquisiciones&action=listarTecnologiasCatalogoAjax',
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					var tbody = $('#tablaTecnologiasGestion tbody');
					if (!response || !response.success) {
						tbody.html('<tr><td colspan="4" class="text-center text-danger py-4">No se pudo cargar la lista.</td></tr>');
						return;
					}

					var data = Array.isArray(response.data) ? response.data : [];
					if (data.length === 0) {
						tbody.html('<tr><td colspan="4" class="text-center text-secondary py-4">No hay tecnologías registradas.</td></tr>');
						return;
					}

					var filas = data.map(function(item) {
						var activo = Number(item.Activo) === 1;
						var badgeEstado = activo ?
							'<span class="badge bg-success-lt">Activo</span>' :
							'<span class="badge bg-secondary-lt">Inactivo</span>';
						var acciones = activo ?
							'<button type="button" class="btn btn-primary adq-btn-action js-editar-tec" data-id="' + Number(item.Id) + '" data-codigo="' + escaparHtml(item.Codigo) + '" data-nombre="' + escaparHtml(item.NombreGenerico) + '">Editar</button>' +
							'<button type="button" class="btn btn-danger adq-btn-action js-eliminar-tec" data-id="' + Number(item.Id) + '">Inactivar</button>' :
							'<button type="button" class="btn btn-success adq-btn-action js-activar-tec" data-id="' + Number(item.Id) + '">Activar</button>';

						return '<tr>' +
							'<td>' + escaparHtml(item.Codigo) + '</td>' +
							'<td>' + escaparHtml(item.NombreGenerico) + '</td>' +
							'<td>' + badgeEstado + '</td>' +
							'<td class="text-end d-flex justify-content-end flex-wrap gap-1">' +
							acciones +
							'</td>' +
							'</tr>';
					}).join('');

					tbody.html(filas);
				},
				error: function() {
					$('#tablaTecnologiasGestion tbody').html('<tr><td colspan="4" class="text-center text-danger py-4">Error de conexión.</td></tr>');
				}
			});
		}

		$('#btnGuardarCentroCosto').on('click', function() {
			var id = $('#ccIdEditar').val();
			var siglas = $('#ccSiglas').val().trim();
			var nombreCentroCosto = $('#ccNombre').val().trim();

			if (!siglas || !nombreCentroCosto) {
				notificar('warning', 'Campos obligatorios', 'Debe completar siglas y nombre del centro de costo.');
				return;
			}

			$.ajax({
				url: 'index.php?module=adquisiciones&action=' + (id ? 'actualizarCentroCostoAjax' : 'agregarCentroCostoAjax'),
				type: 'POST',
				dataType: 'json',
				data: {
					id: id,
					siglas: siglas,
					nombreCentroCosto: nombreCentroCosto
				},
				success: function(response) {
					if (response && response.success) {
						notificar('success', 'Operación correcta', response.message || 'Centro de costo guardado.');
						limpiarFormularioCentro();
						cargarCentrosCosto();
						return;
					}
					notificar('danger', 'No se pudo guardar', response && response.message ? response.message : 'Error al guardar centro de costo.');
				}
			});
		});

		$('#tablaCentrosCostoGestion').on('click', '.js-editar-cc', function() {
			var btn = $(this);
			$('#ccIdEditar').val(btn.data('id'));
			$('#ccSiglas').val(btn.data('siglas'));
			$('#ccNombre').val(btn.data('nombre'));
			$('#btnGuardarCentroCosto').text('Actualizar');
		});

		$('#tablaCentrosCostoGestion').on('click', '.js-eliminar-cc', function() {
			var id = $(this).data('id');
			window.adqConfirmSafe({
				titulo: 'Inactivar centro de costo',
				mensaje: '¿Desea inactivar este centro de costo?',
				textoAceptar: 'Inactivar',
				claseAceptar: 'btn-danger'
			}).then(function(confirmado) {
				if (!confirmado) {
					return;
				}

				$.ajax({
					url: 'index.php?module=adquisiciones&action=eliminarCentroCostoAjax',
					type: 'POST',
					dataType: 'json',
					data: {
						id: id
					},
					success: function(response) {
						if (response && response.success) {
							notificar('success', 'Centro inactivado', response.message || 'Centro de costo inactivado.');
							limpiarFormularioCentro();
							cargarCentrosCosto();
							return;
						}
						notificar('danger', 'No se pudo inactivar', response && response.message ? response.message : 'Error al inactivar centro de costo.');
					}
				});
			});
		});

		$('#tablaCentrosCostoGestion').on('click', '.js-activar-cc', function() {
			var id = $(this).data('id');
			window.adqConfirmSafe({
				titulo: 'Activar centro de costo',
				mensaje: '¿Desea activar este centro de costo?',
				textoAceptar: 'Activar',
				claseAceptar: 'btn-success'
			}).then(function(confirmado) {
				if (!confirmado) {
					return;
				}

				$.ajax({
					url: 'index.php?module=adquisiciones&action=activarCentroCostoAjax',
					type: 'POST',
					dataType: 'json',
					data: {
						id: id
					},
					success: function(response) {
						if (response && response.success) {
							notificar('success', 'Centro activado', response.message || 'Centro de costo activado.');
							cargarCentrosCosto();
							return;
						}
						notificar('danger', 'No se pudo activar', response && response.message ? response.message : 'Error al activar centro de costo.');
					}
				});
			});
		});

		$('#btnGuardarTecnologiaCatalogo').on('click', function() {
			var id = $('#tecIdEditar').val();
			var codigo = $('#tecCodigo').val().trim();
			var nombreGenerico = $('#tecNombre').val().trim();

			if (!codigo || !nombreGenerico) {
				notificar('warning', 'Campos obligatorios', 'Debe completar código y nombre genérico.');
				return;
			}

			$.ajax({
				url: 'index.php?module=adquisiciones&action=' + (id ? 'actualizarTecnologiaCatalogoAjax' : 'agregarTecnologiaAjax'),
				type: 'POST',
				dataType: 'json',
				data: {
					id: id,
					codigo: codigo,
					nombreGenerico: nombreGenerico
				},
				success: function(response) {
					if (response && response.success) {
						notificar('success', 'Operación correcta', response.message || 'Tecnología guardada.');
						limpiarFormularioTecnologia();
						cargarTecnologias();
						return;
					}
					notificar('danger', 'No se pudo guardar', response && response.message ? response.message : 'Error al guardar tecnología.');
				}
			});
		});

		$('#tablaTecnologiasGestion').on('click', '.js-editar-tec', function() {
			var btn = $(this);
			$('#tecIdEditar').val(btn.data('id'));
			$('#tecCodigo').val(btn.data('codigo'));
			$('#tecNombre').val(btn.data('nombre'));
			$('#btnGuardarTecnologiaCatalogo').text('Actualizar');
		});

		$('#tablaTecnologiasGestion').on('click', '.js-eliminar-tec', function() {
			var id = $(this).data('id');
			window.adqConfirmSafe({
				titulo: 'Inactivar tecnología',
				mensaje: '¿Desea inactivar esta tecnología?',
				textoAceptar: 'Inactivar',
				claseAceptar: 'btn-danger'
			}).then(function(confirmado) {
				if (!confirmado) {
					return;
				}

				$.ajax({
					url: 'index.php?module=adquisiciones&action=eliminarTecnologiaCatalogoAjax',
					type: 'POST',
					dataType: 'json',
					data: {
						id: id
					},
					success: function(response) {
						if (response && response.success) {
							notificar('success', 'Tecnología inactivada', response.message || 'Tecnología inactivada correctamente.');
							limpiarFormularioTecnologia();
							cargarTecnologias();
							return;
						}
						notificar('danger', 'No se pudo inactivar', response && response.message ? response.message : 'Error al inactivar tecnología.');
					}
				});
			});
		});

		$('#tablaTecnologiasGestion').on('click', '.js-activar-tec', function() {
			var id = $(this).data('id');
			window.adqConfirmSafe({
				titulo: 'Activar tecnología',
				mensaje: '¿Desea activar esta tecnología?',
				textoAceptar: 'Activar',
				claseAceptar: 'btn-success'
			}).then(function(confirmado) {
				if (!confirmado) {
					return;
				}

				$.ajax({
					url: 'index.php?module=adquisiciones&action=activarTecnologiaCatalogoAjax',
					type: 'POST',
					dataType: 'json',
					data: {
						id: id
					},
					success: function(response) {
						if (response && response.success) {
							notificar('success', 'Tecnología activada', response.message || 'Tecnología activada correctamente.');
							cargarTecnologias();
							return;
						}
						notificar('danger', 'No se pudo activar', response && response.message ? response.message : 'Error al activar tecnología.');
					}
				});
			});
		});

		modalCentros.addEventListener('shown.bs.modal', function() {
			limpiarFormularioCentro();
			cargarCentrosCosto();
		});

		modalTecnologias.addEventListener('shown.bs.modal', function() {
			limpiarFormularioTecnologia();
			cargarTecnologias();
		});
	})();
</script>