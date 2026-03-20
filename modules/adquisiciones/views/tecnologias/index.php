<div class="row g-2 align-items-center mb-3">
	<div class="col-auto">
		<label for="filtroAnioTec" class="form-label mb-0">Filtrar por año:</label>
	</div>
	<div class="col-auto">
		<select id="filtroAnioTec" class="form-select" onchange="filtrarTecnologiasPorAnio()">
			<?php foreach ($aniosTecnologias as $a): ?>
				<option value="<?php echo (int) $a; ?>" <?php echo ($anioTecnologias == $a) ? 'selected' : ''; ?>>
					<?php echo (int) $a; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-vcenter card-table table-striped">
		<thead>
			<tr>
				<th>Código</th>
				<th>Tecnología</th>
				<th>Nombre Genérico</th>
				<th>Estado</th>
				<th class="text-center">Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($tecnologias)): ?>
				<?php foreach ($tecnologias as $tec): ?>
					<?php $tieneCodigosDiferentes = isset($tec['TotalCodigosSiga']) && (int) $tec['TotalCodigosSiga'] > 1; ?>
					<tr>
						<td>
							<?php if ($tieneCodigosDiferentes): ?>
								<span class="badge bg-warning-lt text-dark"><?php echo (int) $tec['TotalCodigosSiga']; ?> códigos SIGA</span>
								<div class="small text-secondary mt-1"><?php echo htmlspecialchars((string) $tec['CodigosSiga']); ?></div>
							<?php else: ?>
								<span class="badge bg-azure-lt"><?php echo htmlspecialchars($tec['CodigoSiga']); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo htmlspecialchars($tec['Tecnologia']); ?></td>
						<td>
							<?php echo htmlspecialchars($tec['NombreGenerico']); ?>
							<?php if ($tieneCodigosDiferentes): ?>
								<div class="text-danger small">Diferencias de Código SIGA</div>
							<?php endif; ?>
						</td>
						<td>
							<?php if ((int) $tec['EstadoCompleto'] === 1): ?>
								<span class="badge bg-success-lt">Completo</span>
							<?php else: ?>
								<span class="badge bg-warning-lt text-dark">Pendiente</span>
							<?php endif; ?>
						</td>
						<td class="text-center">
							<button class="btn btn-azure-lt btn-accion" type="button" onclick="editarTecnologia(<?php echo (int) $tec['IdCatalogoTecnologico']; ?>)">
								Editar
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="5" class="text-center text-secondary">No hay tecnologías registradas para este año.</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<script>
	function filtrarTecnologiasPorAnio() {
		const anio = document.getElementById('filtroAnioTec').value;
		const url = 'index.php?module=adquisiciones&action=tecnologias&anio=' + anio;
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}

	function editarTecnologia(id) {
		const anio = document.getElementById('filtroAnioTec').value;
		const url = 'index.php?module=adquisiciones&action=tecnologia&id=' + id + '&anio=' + anio;
		if (typeof window.cargarVistaAdquisiciones === 'function') {
			window.cargarVistaAdquisiciones(url);
			return;
		}
		window.location.href = url;
	}
</script>