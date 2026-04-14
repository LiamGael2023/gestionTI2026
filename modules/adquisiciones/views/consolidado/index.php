<?php
// Inicializar variables si no existen
$consolidado = $consolidado ?? ['equipos' => [], 'centrosCosto' => [], 'cabeceraCentros' => [], 'matriz' => []];
$consolidadoCabeceraExportacion = $consolidadoCabeceraExportacion ?? ['cabeceraCentros' => [], 'centrosCosto' => []];
$aniosDisponibles = $aniosDisponibles ?? [];
$anioFiltro = $anioFiltro ?? null;

$equipos = $consolidado['equipos'];
$centrosCosto = $consolidado['centrosCosto'];
$cabeceraCentros = $consolidado['cabeceraCentros'] ?? [];
$cabeceraCentrosExportacion = $consolidadoCabeceraExportacion['cabeceraCentros'] ?? [];
$matriz = $consolidado['matriz'];
$tiposSolicitudPorEquipo = $consolidado['tiposSolicitudPorEquipo'] ?? [];

if (empty($cabeceraCentrosExportacion)) {
	$cabeceraCentrosExportacion = $cabeceraCentros;
}

$columnasPadreWeb = [];
foreach ($cabeceraCentros as $grupoCentro) {
	$columnasGrupo = $grupoCentro['columnas'] ?? [];
	$keys = [];
	foreach ($columnasGrupo as $columna) {
		if (!empty($columna['key'])) {
			$keys[] = $columna['key'];
		}
	}

	if (empty($keys)) {
		continue;
	}

	$columnasPadreWeb[] = [
		'label' => (string) ($grupoCentro['label'] ?? ''),
		'keys' => $keys,
	];
}

$columnasPlano = [];
foreach ($cabeceraCentros as $grupoCentro) {
	$columnasGrupo = $grupoCentro['columnas'] ?? [];
	if (!empty($columnasGrupo)) {
		foreach ($columnasGrupo as $columna) {
			$columnasPlano[] = $columna;
		}
		continue;
	}
	$columnasPlano[] = [
		'key' => '',
		'label' => (string) ($grupoCentro['label'] ?? ''),
	];
}

$columnasPlanoExportacion = [];
foreach ($cabeceraCentrosExportacion as $grupoCentro) {
	$columnasGrupo = $grupoCentro['columnas'] ?? [];
	if (!empty($columnasGrupo)) {
		foreach ($columnasGrupo as $columna) {
			$columnasPlanoExportacion[] = $columna;
		}
		continue;
	}
	$columnasPlanoExportacion[] = [
		'key' => '',
		'label' => (string) ($grupoCentro['label'] ?? ''),
	];
}

// Calcular totales por columna
$totalesPorCentroCosto = [];
foreach ($centrosCosto as $cc) {
	$totalesPorCentroCosto[$cc] = 0;
	foreach ($equipos as $equipo) {
		$totalesPorCentroCosto[$cc] += $matriz[$equipo][$cc] ?? 0;
	}
}

$totalesPadreWeb = [];
foreach ($columnasPadreWeb as $columnaPadre) {
	$label = (string) ($columnaPadre['label'] ?? '');
	$totalesPadreWeb[$label] = 0;
	foreach ($columnaPadre['keys'] as $keyColumna) {
		$totalesPadreWeb[$label] += (int) ($totalesPorCentroCosto[$keyColumna] ?? 0);
	}
}

// Calcular total general
$totalGeneral = array_sum($totalesPorCentroCosto);
?>

<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
	<div class="d-flex gap-2 align-items-center flex-wrap">
		<label class="form-label mb-0 text-nowrap">Filtrar por año:</label>
		<select id="filtroAnioConsolidado" class="form-select w-auto" onchange="filtrarConsolidadoPorAnio()" <?php echo empty($aniosDisponibles) ? 'disabled' : ''; ?>>
			<?php if (empty($aniosDisponibles)): ?>
				<option value="">Sin registros</option>
			<?php else: ?>
				<?php foreach ($aniosDisponibles as $anio): ?>
					<option value="<?php echo $anio; ?>" <?php echo ($anioFiltro == $anio) ? 'selected' : ''; ?>>
						<?php echo $anio; ?>
					</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
	</div>

	<div class="d-flex gap-2 ms-auto">
		<button class="btn btn-success" onclick="exportarConsolidado()">
			Consolidado
		</button>
		<button class="btn btn-success" onclick="exportarConsolidadoOficial()">
			Consolidado Oficial
		</button>
	</div>
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
					<?php foreach ($columnasPadreWeb as $columnaPadre): ?>
						<th><?php echo htmlspecialchars((string) ($columnaPadre['label'] ?? '')); ?></th>
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
						<?php foreach ($columnasPadreWeb as $columnaPadre): ?>
							<?php
							$cantidadPadre = 0;
							foreach ($columnaPadre['keys'] as $keyColumna) {
								$cantidadPadre += (int) ($matriz[$equipo][$keyColumna] ?? 0);
							}
							?>
							<td <?php echo $cantidadPadre == 0 ? 'class="text-muted"' : ''; ?>>
								<?php echo $cantidadPadre > 0 ? $cantidadPadre : ''; ?>
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
					<?php foreach ($columnasPadreWeb as $columnaPadre): ?>
						<td><?php echo (int) ($totalesPadreWeb[(string) ($columnaPadre['label'] ?? '')] ?? 0); ?></td>
					<?php endforeach; ?>
					<td><?php echo $totalGeneral; ?></td>
				</tr>
			</tfoot>
		</table>
	</div>
<?php endif; ?>

<script>
window.adqConsolidadoCabeceraCentros = <?php echo json_encode($cabeceraCentros, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.adqConsolidadoColumnasPlano = <?php echo json_encode($columnasPlano, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.adqConsolidadoCabeceraCentrosExportacion = <?php echo json_encode($cabeceraCentrosExportacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.adqConsolidadoColumnasPlanoExportacion = <?php echo json_encode($columnasPlanoExportacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.adqConsolidadoTotalesPorColumna = <?php echo json_encode($totalesPorCentroCosto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.adqConsolidadoEquipos = <?php echo json_encode($equipos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.adqConsolidadoMatriz = <?php echo json_encode($matriz, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.adqConsolidadoTiposSolicitudPorEquipo = <?php echo json_encode($tiposSolicitudPorEquipo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

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
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="modules/adquisiciones/views/consolidado/consolidado.js?v=<?php echo filemtime(__DIR__ . '/consolidado.js'); ?>"></script>
