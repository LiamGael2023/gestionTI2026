<div class="row g-2 align-items-center mb-3">
	<div class="col-auto">
		<label for="filtroAnioTec" class="form-label mb-0">Filtrar por año:</label>
	</div>
	<div class="col-auto">
		<select id="filtroAnioTec" class="form-select" onchange="filtrarTecnologiasPorAnio()" <?php echo empty($aniosTecnologias) ? 'disabled' : ''; ?>>
			<?php if (empty($aniosTecnologias)): ?>
				<option value="">Sin registros</option>
			<?php else: ?>
				<?php foreach ($aniosTecnologias as $a): ?>
					<option value="<?php echo (int) $a; ?>" <?php echo ($anioTecnologias == $a) ? 'selected' : ''; ?>>
						<?php echo (int) $a; ?>
					</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
	</div>
	<div class="col-auto ms-auto">
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
				<th class="text-end">Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($tecnologias)): ?>
				<?php foreach ($tecnologias as $tec): ?>
					<?php $tieneCodigosDiferentes = isset($tec['TotalCodigosSiga']) && (int) $tec['TotalCodigosSiga'] > 1; ?>
					<?php $tienePresupuestoAsignado = isset($tec['TienePresupuesto']) && (int) $tec['TienePresupuesto'] === 1; ?>
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
					<td class="text-end align-middle">
						<div class="btn-group" role="group">
							<!-- Detalles -->
							<button type="button"
								class="btn btn-icon btn-lg"
								title="Detalles"
								onclick="editarTecnologia(<?= (int)$tec['IdCatalogoTecnologico'] ?>)">
								<i class="ti ti-list-details fs-2"></i>
							</button>
							<!-- Presupuesto -->
							<button type="button"
								class="btn btn-icon btn-lg btn-presupuesto-tecnologia <?= $tienePresupuestoAsignado ? 'text-success' : '' ?>"
								title="Presupuesto"
								data-id-catalogo="<?= (int)$tec['IdCatalogoTecnologico'] ?>"
								onclick="abrirModalPresupuesto(
									<?= (int)$tec['IdCatalogoTecnologico'] ?>, 
									<?= htmlspecialchars(json_encode($tec['NombreGenerico']), ENT_QUOTES); ?>
								)">
								<i class="ti ti-calendar-dollar fs-2"></i>
							</button>
						</div>
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

<div class="modal fade" id="modalPresupuestoTecnologia" tabindex="-1" aria-labelledby="modalPresupuestoTecnologiaLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalPresupuestoTecnologiaLabel">Presupuesto anual</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="formPresupuestoTecnologia">
				<input type="hidden" id="presupuestoIdCatalogo" name="IdCatalogoTecnologico">
				<input type="hidden" id="presupuestoAnio" name="Anio">
				<div class="modal-body">
					<div class="mb-2">
						<span class="text-secondary small" id="presupuestoNombreTec"></span>
					</div>
					<div class="mb-0">
						<label for="presupuestoMonto" class="form-label">Monto (S/)</label>
						<input type="number" class="form-control" id="presupuestoMonto" name="Monto"
							min="0" step="0.01" placeholder="0.00">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" id="btn-guardar-presupuesto">Guardar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="modalNuevaTecnologia" tabindex="-1" aria-labelledby="modalNuevaTecnologiaLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalNuevaTecnologiaLabel">Agregar nueva tecnología</h5>
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
					window.adqNotifySafe(
						'success',
						'Sincronizacion completada',
						'Nuevos: ' + response.nuevos + '\nActualizados: ' + response.actualizados
					);
				} else {
					window.adqNotifySafe('danger', 'Error al sincronizar', response.message || 'No se pudo sincronizar.');
				}
			},
			error: function() {
				btn.disabled = false;
				btn.innerHTML = 'Sincronizar de SIGA';
				window.adqNotifySafe('danger', 'Error de conexion', 'Ocurrio un error al conectar con el servidor.');
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
			window.adqNotifySafe('warning', 'Campos obligatorios', 'Debe completar codigo y nombre generico.');
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
					window.adqNotifySafe('success', 'Registro exitoso', response.message || 'Tecnologia registrada correctamente.');

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
					window.adqNotifySafe(
						'warning',
						'Tecnologia duplicada',
						'Codigo existente: ' + (response.existente.Codigo || '') + '\nNombre existente: ' + (response.existente.NombreGenerico || '')
					);
					return;
				}

				window.adqNotifySafe('danger', 'No se pudo registrar', (response && response.message) ? response.message : 'No se pudo registrar la tecnologia.');
			},
			error: function() {
				btnGuardar.disabled = false;
				btnGuardar.innerHTML = 'Guardar';
				window.adqNotifySafe('danger', 'Error de conexion', 'Ocurrio un error al conectar con el servidor.');
			}
		});
	});

	document.getElementById('modalNuevaTecnologia').addEventListener('hidden.bs.modal', function() {
		document.getElementById('formNuevaTecnologia').reset();
		document.getElementById('btn-guardar-tecnologia').disabled = false;
		document.getElementById('btn-guardar-tecnologia').innerHTML = 'Guardar';
	});

	function abrirModalPresupuesto(idCatalogo, nombreTec) {
		const anio = document.getElementById('filtroAnioTec').value;
		document.getElementById('presupuestoIdCatalogo').value = idCatalogo;
		document.getElementById('presupuestoAnio').value = anio;
		document.getElementById('modalPresupuestoTecnologia').dataset.idCatalogo = String(idCatalogo);
		document.getElementById('presupuestoNombreTec').textContent = nombreTec + ' — ' + anio;
		document.getElementById('presupuestoMonto').value = '';
		document.getElementById('btn-guardar-presupuesto').disabled = false;
		document.getElementById('btn-guardar-presupuesto').innerHTML = 'Guardar';

		$.ajax({
			url: 'index.php?module=adquisiciones&action=obtenerPresupuestoTecnologiaAjax&id=' + idCatalogo + '&anio=' + anio,
			type: 'GET',
			dataType: 'json',
			success: function(res) {
				if (res && res.ok && res.datos) {
					document.getElementById('presupuestoMonto').value = res.datos.Monto !== null ? res.datos.Monto : '';
				}
			}
		});

		const modal = new bootstrap.Modal(document.getElementById('modalPresupuestoTecnologia'));
		modal.show();
	}

	const formPresupuestoTecnologia = document.getElementById('formPresupuestoTecnologia');
	if (formPresupuestoTecnologia && formPresupuestoTecnologia.dataset.boundSubmit !== '1') {
		formPresupuestoTecnologia.dataset.boundSubmit = '1';
		formPresupuestoTecnologia.addEventListener('submit', function(event) {
			event.preventDefault();

		const btnGuardar = document.getElementById('btn-guardar-presupuesto');
		const idCatalogo = parseInt(document.getElementById('presupuestoIdCatalogo').value, 10);
		const anio       = parseInt(document.getElementById('presupuestoAnio').value, 10);
		const montoRaw   = document.getElementById('presupuestoMonto').value.trim();
		const monto      = montoRaw !== '' ? parseFloat(montoRaw) : null;

		if (monto !== null && (isNaN(monto) || monto < 0)) {
			window.adqNotifySafe('warning', 'Monto inválido', 'Ingrese un monto positivo o déjelo vacío.');
			return;
		}

		btnGuardar.disabled = true;
		btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

			$.ajax({
			url: 'index.php?module=adquisiciones&action=guardarPresupuestoTecnologiaAjax',
			type: 'POST',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify({ IdCatalogoTecnologico: idCatalogo, Anio: anio, Monto: monto }),
			success: function(res) {
				btnGuardar.disabled = false;
				btnGuardar.innerHTML = 'Guardar';
				if (res && res.ok) {
					const botonPresupuesto = document.querySelector('.btn-presupuesto-tecnologia[data-id-catalogo="' + idCatalogo + '"]');
					if (botonPresupuesto) {
						if (monto !== null) {
							botonPresupuesto.classList.add('text-success');
						} else {
							botonPresupuesto.classList.remove('text-success');
						}
					}
					window.adqNotifySafe('success', 'Presupuesto guardado', 'El presupuesto fue registrado correctamente.');
					bootstrap.Modal.getInstance(document.getElementById('modalPresupuestoTecnologia')).hide();
				} else {
					window.adqNotifySafe('danger', 'Error', (res && res.error) ? res.error : 'No se pudo guardar el presupuesto.');
				}
			},
			error: function() {
				btnGuardar.disabled = false;
				btnGuardar.innerHTML = 'Guardar';
				window.adqNotifySafe('danger', 'Error de conexión', 'Ocurrió un error al conectar con el servidor.');
			}
			});
		});
	}

	const modalPresupuestoTecnologia = document.getElementById('modalPresupuestoTecnologia');
	if (modalPresupuestoTecnologia && modalPresupuestoTecnologia.dataset.boundHidden !== '1') {
		modalPresupuestoTecnologia.dataset.boundHidden = '1';
		modalPresupuestoTecnologia.addEventListener('hidden.bs.modal', function() {
			document.getElementById('formPresupuestoTecnologia').reset();
		});
	}
</script>