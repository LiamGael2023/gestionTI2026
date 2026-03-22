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
	<div class="col-auto ms-auto">
		<button class="btn btn-primary me-2" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaTecnologia">
			Nueva tecnologia
		</button>
		<button class="btn btn-success" id="btn-sincronizar-homologacion">
			Sincronizar de SIGA
		</button>
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

<div class="modal fade" id="modalNuevaTecnologia" tabindex="-1" aria-labelledby="modalNuevaTecnologiaLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalNuevaTecnologiaLabel">Agregar nueva tecnologia</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="formNuevaTecnologia">
				<div class="modal-body">
					<div class="mb-3">
						<label for="nuevaTecnologiaCodigo" class="form-label">Codigo</label>
						<input type="text" class="form-control" id="nuevaTecnologiaCodigo" name="codigo" maxlength="50" required>
					</div>
					<div class="mb-0">
						<label for="nuevaTecnologiaNombre" class="form-label">Nombre generico</label>
						<input type="text" class="form-control" id="nuevaTecnologiaNombre" name="nombreGenerico" maxlength="255" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" id="btn-guardar-tecnologia">Guardar</button>
				</div>
			</form>
		</div>
	</div>
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

	document.getElementById('btn-sincronizar-homologacion').addEventListener('click', function() {
		const btn = this;
		btn.disabled = true;
		btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sincronizando...';

		$.ajax({
			url: 'index.php?module=adquisiciones&action=sincronizarHomologacionAjax',
			type: 'POST',
			dataType: 'json',
			success: function(response) {
				btn.disabled = false;
				btn.innerHTML = 'Sincronizar de SIGA';

				if (response.success) {
					alert(
						'Sincronización completada.\n' +
						'Nuevos: ' + response.nuevos + '\n' +
						'Actualizados: ' + response.actualizados
					);
				} else {
					alert('Error: ' + (response.message || 'No se pudo sincronizar.'));
				}
			},
			error: function() {
				btn.disabled = false;
				btn.innerHTML = 'Sincronizar de SIGA';
				alert('Ocurrió un error al conectar con el servidor.');
			}
		});
	});

	document.getElementById('formNuevaTecnologia').addEventListener('submit', function(event) {
		event.preventDefault();

		const form = this;
		const btnGuardar = document.getElementById('btn-guardar-tecnologia');
		const codigoInput = document.getElementById('nuevaTecnologiaCodigo');
		const nombreInput = document.getElementById('nuevaTecnologiaNombre');
		const codigo = codigoInput.value.trim();
		const nombreGenerico = nombreInput.value.trim();

		if (!codigo || !nombreGenerico) {
			alert('Debe completar codigo y nombre generico.');
			return;
		}

		btnGuardar.disabled = true;
		btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

		$.ajax({
			url: 'index.php?module=adquisiciones&action=agregarTecnologiaAjax',
			type: 'POST',
			dataType: 'json',
			data: {
				codigo: codigo,
				nombreGenerico: nombreGenerico
			},
			success: function(response) {
				btnGuardar.disabled = false;
				btnGuardar.innerHTML = 'Guardar';

				if (response && response.success) {
					alert(response.message || 'Tecnologia registrada correctamente.');

					const modalElement = document.getElementById('modalNuevaTecnologia');
					const modalInstance = bootstrap.Modal.getInstance(modalElement);
					if (modalInstance) {
						modalInstance.hide();
					}

					form.reset();
					filtrarTecnologiasPorAnio();
					return;
				}

				if (response && response.duplicado && response.existente) {
					alert(
						'Tecnologia duplicada.\n' +
						'Codigo existente: ' + (response.existente.Codigo || '') + '\n' +
						'Nombre existente: ' + (response.existente.NombreGenerico || '')
					);
					return;
				}

				alert((response && response.message) ? response.message : 'No se pudo registrar la tecnologia.');
			},
			error: function() {
				btnGuardar.disabled = false;
				btnGuardar.innerHTML = 'Guardar';
				alert('Ocurrió un error al conectar con el servidor.');
			}
		});
	});

	document.getElementById('modalNuevaTecnologia').addEventListener('hidden.bs.modal', function() {
		document.getElementById('formNuevaTecnologia').reset();
		document.getElementById('btn-guardar-tecnologia').disabled = false;
		document.getElementById('btn-guardar-tecnologia').innerHTML = 'Guardar';
	});
</script>