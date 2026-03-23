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
$conConformidad = (int) ($estadoDocumental['ConConformidad'] ?? 0);
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
			<div class="card h-100">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-green-lt text-green"><i class="ti ti-file-check"></i></span>
						<div class="h1 mb-0"><?php echo number_format($tecnologiasCompletas); ?></div>
					</div>
					<div class="fw-bold">Tecnologías Completas</div>
					<div class="text-secondary small">Total activas: <?php echo number_format($totalTecnologias); ?>.</div>
					<div class="progress progress-sm mt-3">
						<div class="progress-bar bg-green" style="width: <?php echo $porcentajeTecnologiasCompletas; ?>%"></div>
					</div>
					<div class="d-flex justify-content-between align-items-center mt-2 small text-secondary">
						<span><?php echo $porcentajeTecnologiasCompletas; ?>% cobertura integral</span>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-azure-lt text-azure"><i class="ti ti-building"></i></span>
						<div class="h1 mb-0"><?php echo number_format($totalCentrosConRequerimientos); ?></div>
					</div>
					<div class="fw-bold">Centros con Requerimientos</div>
					<div class="text-secondary small">Distribución del año <?php echo $anioFiltro; ?> por centro de costo.</div>
					<div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
						<span><?php echo number_format(count($resumenCentroCosto)); ?> centros evaluados</span>
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
					<div class="text-secondary small">Mínimo 4 fichas</div>
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
					<div class="text-secondary text-uppercase fw-bold small">Con Conformidad</div>
					<div class="h1 mb-1"><?php echo number_format($conConformidad); ?></div>
					<div class="text-secondary small">Acta de conformidad</div>
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
						<?php $filasMinimasProximas = 3; ?>
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
</div>

