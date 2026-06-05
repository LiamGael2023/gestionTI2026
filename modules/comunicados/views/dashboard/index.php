<?php
$resumenComunicados = isset($resumenComunicados) && is_array($resumenComunicados) ? $resumenComunicados : [];
$comunicadosRecientes = isset($comunicadosRecientes) && is_array($comunicadosRecientes) ? $comunicadosRecientes : [];
$plantillasActivas = isset($plantillasActivas) && is_array($plantillasActivas) ? $plantillasActivas : [];
$archivosRecientes = isset($archivosRecientes) && is_array($archivosRecientes) ? $archivosRecientes : [];

$totalComunicados = (int) ($resumenComunicados['TotalComunicados'] ?? 0);
$borradores = (int) ($resumenComunicados['Borradores'] ?? 0);
$listos = (int) ($resumenComunicados['Listos'] ?? 0);
$inactivos = (int) ($resumenComunicados['Inactivos'] ?? 0);
$porcentajeListos = $totalComunicados > 0 ? round(($listos / $totalComunicados) * 100) : 0;
?>
<style>
	.com-dashboard .card {
		border: 1px solid rgba(98, 105, 118, 0.12);
		border-radius: 0.9rem;
		box-shadow: 0 1px 2px rgba(31, 41, 55, 0.04), 0 8px 24px -14px rgba(31, 41, 55, 0.22);
	}

	.com-dashboard .com-card-clickable {
		cursor: pointer;
	}
</style>

<div class="com-dashboard">
	<div class="row g-3 mb-3">
		<div class="col-12 col-md-6 col-xl-3">
			<a class="card h-100 com-card-clickable text-reset text-decoration-none" href="index.php?module=comunicados&action=comunicados">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-blue-lt text-blue"><i class="ti ti-speakerphone"></i></span>
						<div class="h1 mb-0"><?php echo number_format($totalComunicados); ?></div>
					</div>
					<div class="fw-bold">Comunicados</div>
					<div class="text-secondary small">Borradores: <?php echo number_format($borradores); ?> - Listos: <?php echo number_format($listos); ?></div>
					<div class="progress progress-sm mt-3">
						<div class="progress-bar bg-blue" style="width: <?php echo $porcentajeListos; ?>%"></div>
					</div>
				</div>
			</a>
		</div>

		<div class="col-12 col-md-6 col-xl-3">
			<a class="card h-100 com-card-clickable text-reset text-decoration-none" href="index.php?module=comunicados&action=plantillas">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-green-lt text-green"><i class="ti ti-template"></i></span>
						<div class="h1 mb-0"><?php echo number_format(count($plantillasActivas)); ?></div>
					</div>
					<div class="fw-bold">Plantillas activas</div>
					<div class="text-secondary small">Estructuras base reutilizables.</div>
					<div class="progress progress-sm mt-3"><div class="progress-bar bg-green" style="width: 100%"></div></div>
				</div>
			</a>
		</div>

		<div class="col-12 col-md-6 col-xl-3">
			<a class="card h-100 com-card-clickable text-reset text-decoration-none" href="index.php?module=comunicados&action=archivos">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-azure-lt text-azure"><i class="ti ti-cloud-upload"></i></span>
						<div class="h1 mb-0"><?php echo number_format(count($archivosRecientes)); ?></div>
					</div>
					<div class="fw-bold">Archivos recientes</div>
					<div class="text-secondary small">Imagenes y documentos cargados.</div>
					<div class="progress progress-sm mt-3"><div class="progress-bar bg-azure" style="width: 100%"></div></div>
				</div>
			</a>
		</div>

		<div class="col-12 col-md-6 col-xl-3">
			<div class="card h-100">
				<div class="card-body">
					<div class="d-flex align-items-start justify-content-between mb-2">
						<span class="avatar avatar-md bg-orange-lt text-orange"><i class="ti ti-eye-x"></i></span>
						<div class="h1 mb-0"><?php echo number_format($inactivos); ?></div>
					</div>
					<div class="fw-bold">Inactivos</div>
					<div class="text-secondary small">Registros retirados del flujo activo.</div>
					<div class="progress progress-sm mt-3"><div class="progress-bar bg-orange" style="width: 100%"></div></div>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3">
		<div class="col-12">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h3 class="card-title mb-0">Comunicados recientes</h3>
					<a class="btn btn-primary" href="index.php?module=comunicados&action=editor">
						<i class="ti ti-plus me-1"></i>Nuevo
					</a>
				</div>
				<div class="table-responsive">
					<table class="table table-vcenter card-table table-striped">
						<thead>
							<tr>
								<th>Titulo</th>
								<th>Estado</th>
								<th>Plantilla</th>
								<th class="text-end">Acciones</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($comunicadosRecientes)): ?>
								<tr><td colspan="4" class="text-center text-secondary py-4">No hay comunicados registrados.</td></tr>
							<?php else: ?>
								<?php foreach ($comunicadosRecientes as $item): ?>
									<tr>
										<td><?php echo htmlspecialchars((string) $item['TituloComunicado'], ENT_QUOTES, 'UTF-8'); ?></td>
										<td><span class="badge <?php echo $item['EstadoComunicado'] === 'LISTO' ? 'bg-success-lt' : 'bg-warning-lt text-dark'; ?>"><?php echo htmlspecialchars((string) $item['EstadoComunicado']); ?></span></td>
										<td><?php echo htmlspecialchars((string) ($item['NombrePlantilla'] ?? 'Sin plantilla'), ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="text-end">
											<a class="btn btn-icon btn-lg" title="Editar" href="index.php?module=comunicados&action=editor&id=<?php echo (int) $item['IdComunicado']; ?>">
												<i class="ti ti-edit fs-2"></i>
											</a>
										</td>
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
