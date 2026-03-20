<?php
// Inicializar variables si no existen
$consolidado = $consolidado ?? ['equipos' => [], 'centrosCosto' => [], 'matriz' => []];
$aniosDisponibles = $aniosDisponibles ?? [];
$anioFiltro = $anioFiltro ?? null;

$equipos = $consolidado['equipos'];
$centrosCosto = $consolidado['centrosCosto'];
$matriz = $consolidado['matriz'];

// Calcular totales por columna
$totalesPorCentroCosto = [];
foreach ($centrosCosto as $cc) {
	$totalesPorCentroCosto[$cc] = 0;
	foreach ($equipos as $equipo) {
		$totalesPorCentroCosto[$cc] += $matriz[$equipo][$cc] ?? 0;
	}
}

// Calcular total general
$totalGeneral = array_sum($totalesPorCentroCosto);
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
	<div class="d-flex gap-2 align-items-center flex-wrap">
		<label class="form-label mb-0 text-nowrap">Filtrar por año:</label>
		<select id="filtroAnioConsolidado" class="form-select w-auto" onchange="filtrarConsolidadoPorAnio()">
			<?php foreach ($aniosDisponibles as $anio): ?>
				<option value="<?php echo $anio; ?>" <?php echo ($anioFiltro == $anio) ? 'selected' : ''; ?>>
					<?php echo $anio; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	
	<button class="btn btn-success" onclick="exportarConsolidado()">
		Exportar a Excel
	</button>
</div>

<?php if (empty($equipos)): ?>
	<div class="alert alert-info mb-0">
		<div>
			<h4 class="alert-title">Sin datos</h4>
			<div class="text-secondary">No hay requerimientos registrados para el año seleccionado.</div>
		</div>
	</div>
<?php else: ?>
	<div class="table-responsive">
		<table class="table table-vcenter card-table table-striped" id="tabla-consolidado">
			<thead>
				<tr>
					<th>Equipo</th>
					<?php foreach ($centrosCosto as $cc): ?>
						<th><?php echo htmlspecialchars($cc); ?></th>
					<?php endforeach; ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($equipos as $equipo): ?>
					<?php
					$totalFila = 0;
					foreach ($centrosCosto as $cc) {
						$totalFila += $matriz[$equipo][$cc] ?? 0;
					}
					?>
					<tr>
						<td><?php echo htmlspecialchars($equipo); ?></td>
						<?php foreach ($centrosCosto as $cc): ?>
							<?php 
							$cantidad = $matriz[$equipo][$cc] ?? 0;
						?>
						<td <?php echo $cantidad == 0 ? 'class="text-muted"' : ''; ?>>
								<?php echo $cantidad > 0 ? $cantidad : ''; ?>
							</td>
						<?php endforeach; ?>
					<td class="fw-semibold">
							<?php echo $totalFila; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td>Total</td>
					<?php foreach ($centrosCosto as $cc): ?>
						<td><?php echo $totalesPorCentroCosto[$cc]; ?></td>
					<?php endforeach; ?>
					<td><?php echo $totalGeneral; ?></td>
				</tr>
			</tfoot>
		</table>
	</div>
<?php endif; ?>

<script>
function filtrarConsolidadoPorAnio() {
	const anio = document.getElementById('filtroAnioConsolidado').value;
	let url = 'index.php?module=adquisiciones&action=consolidado';
	if (anio) {
		url += '&anio=' + anio;
	}
	if (typeof window.cargarVistaAdquisiciones === 'function') {
		window.cargarVistaAdquisiciones(url);
		return;
	}
	window.location.href = url;
}
</script>
<script src="modules/adquisiciones/views/consolidado/exportar.js"></script>
