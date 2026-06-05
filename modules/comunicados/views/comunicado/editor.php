<?php
$comunicado = isset($comunicado) && is_array($comunicado) ? $comunicado : null;
$plantillaBase = isset($plantillaBase) && is_array($plantillaBase) ? $plantillaBase : null;
$plantillas = isset($plantillas) && is_array($plantillas) ? $plantillas : [];
$archivos = isset($archivos) && is_array($archivos) ? $archivos : [];

$initialBlocks = $comunicado && !empty($comunicado['ContenidoJson']) ? (string) $comunicado['ContenidoJson'] : ($plantillaBase && !empty($plantillaBase['ContenidoJson']) ? (string) $plantillaBase['ContenidoJson'] : '[]');
$initialBlocksScript = str_ireplace('</script', '<\/script', $initialBlocks ?: '[]');
$initialId = $comunicado ? (int) $comunicado['IdComunicado'] : 0;
$initialPlantilla = $comunicado ? (int) ($comunicado['IdPlantilla'] ?? 0) : ($plantillaBase ? (int) ($plantillaBase['IdPlantilla'] ?? 0) : 0);
$initialEstado = $comunicado ? (string) ($comunicado['EstadoComunicado'] ?? 'BORRADOR') : 'BORRADOR';
$initialTitulo = $comunicado ? (string) ($comunicado['TituloComunicado'] ?? '') : ($plantillaBase ? 'Nuevo comunicado - ' . (string) ($plantillaBase['NombrePlantilla'] ?? 'Plantilla') : '');
?>

<style>
	.com-editor-shell .com-toolbox {
		position: sticky;
		top: 1rem;
	}

	.com-editor-shell .card {
		border: 1px solid rgba(98, 105, 118, 0.12);
		border-radius: 0.9rem;
		box-shadow: 0 1px 2px rgba(31, 41, 55, 0.04), 0 10px 24px -18px rgba(31, 41, 55, 0.24);
	}

	.com-editor-shell .card-header {
		background: #ffffff;
		border-bottom-color: rgba(98, 105, 118, 0.12);
		border-radius: 0.9rem 0.9rem 0 0;
	}

	.com-editor-shell .com-block-button {
		justify-content: flex-start;
		border-radius: 0.55rem;
		font-weight: 600;
		background: #ffffff;
		border-color: rgba(32, 107, 196, 0.28);
		box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
		transition: transform 0.12s ease, box-shadow 0.12s ease, background-color 0.12s ease;
	}

	.com-editor-shell .com-block-button:hover {
		background: #f5f9ff;
		box-shadow: 0 8px 18px -16px rgba(32, 107, 196, 0.55);
		transform: translateY(-1px);
	}

	.com-editor-shell .com-canvas {
		min-height: 620px;
		background:
			linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(241, 245, 249, 0.96));
		border: 1px dashed rgba(98, 105, 118, 0.32);
		border-radius: 0.9rem;
		padding: 1.2rem;
	}

	.com-editor-shell .com-email {
		max-width: 760px;
		margin: 0 auto;
		background: #ffffff;
		border: 1px solid rgba(98, 105, 118, 0.14);
		border-radius: 0.9rem;
		box-shadow: 0 18px 36px -30px rgba(15, 23, 42, 0.45);
		overflow: hidden;
		padding: 0.8rem;
	}

	.com-editor-shell .com-block {
		position: relative;
		margin: 0 0 0.8rem;
		padding: 0.85rem;
		border: 1px solid rgba(203, 213, 225, 0.9);
		border-radius: 0.85rem;
		background: #ffffff;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
		transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
	}

	.com-editor-shell .com-block:last-child {
		margin-bottom: 0;
	}

	.com-editor-shell .com-block:hover,
	.com-editor-shell .com-block.is-selected {
		border-color: rgba(32, 107, 196, 0.42);
		background: #fbfdff;
		box-shadow: 0 10px 24px -20px rgba(32, 107, 196, 0.45);
	}

	.com-editor-shell .com-block-actions {
		position: absolute;
		right: 0.65rem;
		top: 0.6rem;
		display: none;
		gap: 0.25rem;
	}

	.com-editor-shell .com-block:hover .com-block-actions,
	.com-editor-shell .com-block.is-selected .com-block-actions {
		display: flex;
	}

	.com-editor-shell .com-block-actions .btn {
		border-radius: 0.45rem;
		background: #ffffff;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
	}

	.com-editor-shell .com-block-head {
		display: flex;
		align-items: center;
		gap: 0.45rem;
		margin-bottom: 0.7rem;
		padding-right: 6rem;
		color: #475569;
		font-size: 0.78rem;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0;
	}

	.com-editor-shell .com-block-head i {
		color: #206bc4;
		font-size: 1rem;
	}

	.com-editor-shell .com-control-panel {
		background: #f8fafc;
		border: 1px solid rgba(203, 213, 225, 0.82);
		border-radius: 0.7rem;
		padding: 0.75rem;
		margin-top: 0.75rem;
	}

	.com-editor-shell .com-field-label {
		color: #475569;
		font-weight: 650;
	}

	.com-editor-shell .com-block .form-control,
	.com-editor-shell .com-block .form-select {
		border-radius: 0.5rem;
		border-color: rgba(203, 213, 225, 0.95);
		background-color: #ffffff;
	}

	.com-editor-shell .com-block textarea.form-control {
		resize: vertical;
	}

	.com-editor-shell .com-drop-empty {
		min-height: 240px;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #667085;
		text-align: center;
		border: 1px dashed rgba(98, 105, 118, 0.32);
		border-radius: 0.8rem;
		background: #f8fafc;
	}

</style>

<div class="com-editor-shell"
	data-id="<?php echo $initialId; ?>"
	data-plantilla="<?php echo $initialPlantilla; ?>"
	data-estado="<?php echo htmlspecialchars($initialEstado, ENT_QUOTES, 'UTF-8'); ?>">

	<div class="row g-2 align-items-center mb-3">
		<div class="col">
			<a class="btn btn-link px-0" href="index.php?module=comunicados&action=comunicados">
				<i class="ti ti-arrow-left me-1"></i>Volver
			</a>
		</div>
		<div class="col-auto">
			<div class="btn-group">
				<button type="button" class="btn btn-outline-primary" id="btnGuardarComunicado">
					<i class="ti ti-device-floppy me-1"></i>Guardar
				</button>
				<button type="button" class="btn btn-outline-primary" id="btnGuardarComoPlantilla">
					<i class="ti ti-template me-1"></i>Plantilla
				</button>
			</div>
		</div>
	</div>

	<div class="row g-3">
		<div class="col-12 col-xl-3">
			<div class="card com-toolbox">
				<div class="card-header"><h3 class="card-title mb-0">Bloques</h3></div>
				<div class="card-body">
					<div class="d-grid gap-2 mb-3">
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="cabecera"><i class="ti ti-layout-navbar me-2"></i>Cabecera PECH</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="titulo"><i class="ti ti-heading me-2"></i>Titulo</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="meta"><i class="ti ti-calendar-time me-2"></i>Metadatos</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="imagen"><i class="ti ti-photo me-2"></i>Imagen</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="parrafo"><i class="ti ti-align-left me-2"></i>Parrafo</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="tarjetas"><i class="ti ti-layout-grid me-2"></i>Tarjetas</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="panel"><i class="ti ti-box me-2"></i>Panel</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="lista"><i class="ti ti-list-check me-2"></i>Lista</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="adjunto"><i class="ti ti-paperclip me-2"></i>Archivo adjunto</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="boton"><i class="ti ti-click me-2"></i>Boton</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="firma"><i class="ti ti-signature me-2"></i>Firma</button>
						<button type="button" class="btn btn-outline-primary com-block-button" draggable="true" data-block-type="pie"><i class="ti ti-layout-bottombar me-2"></i>Pie</button>
					</div>

					<label class="form-label" for="selectorPlantilla">Plantilla</label>
					<select class="form-select mb-3" id="selectorPlantilla">
						<option value="">Seleccione una plantilla</option>
						<?php foreach ($plantillas as $plantilla): ?>
							<option value="db_<?php echo (int) $plantilla['IdPlantilla']; ?>" <?php echo $initialPlantilla === (int) $plantilla['IdPlantilla'] ? 'selected' : ''; ?> data-json="<?php echo htmlspecialchars((string) $plantilla['ContenidoJson'], ENT_QUOTES, 'UTF-8'); ?>">
								<?php echo htmlspecialchars((string) $plantilla['NombrePlantilla'], ENT_QUOTES, 'UTF-8'); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<div class="row g-2">
						<div class="col-12">
							<label class="form-label" for="tituloComunicado">Titulo</label>
							<input type="text" class="form-control" id="tituloComunicado" maxlength="200" value="<?php echo htmlspecialchars($initialTitulo, ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div class="col-12">
							<label class="form-label" for="estadoComunicado">Estado</label>
							<select class="form-select" id="estadoComunicado">
								<option value="BORRADOR" <?php echo $initialEstado === 'BORRADOR' ? 'selected' : ''; ?>>Borrador</option>
								<option value="LISTO" <?php echo $initialEstado === 'LISTO' ? 'selected' : ''; ?>>Listo</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12 col-xl-6">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h3 class="card-title mb-0">Constructor</h3>
					<span class="badge bg-blue-lt">Drag & Drop</span>
				</div>
				<div class="card-body">
					<div class="com-canvas" id="comCanvas">
						<div class="com-email" id="comEmail"></div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12 col-xl-3">
			<div class="card mb-3">
				<div class="card-header"><h3 class="card-title mb-0">Archivos</h3></div>
				<div class="card-body">
					<form id="formUploadEditor" class="mb-3">
						<div class="input-group">
							<input type="file" class="form-control" name="archivo" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
							<button type="submit" class="btn btn-primary" title="Subir"><i class="ti ti-upload"></i></button>
						</div>
					</form>
					<div class="list-group list-group-flush" id="listaArchivosEditor">
						<?php if (empty($archivos)): ?>
							<div class="list-group-item text-secondary px-0">Sin archivos cargados.</div>
						<?php else: ?>
							<?php foreach (array_slice($archivos, 0, 10) as $archivo): ?>
								<button type="button" class="list-group-item list-group-item-action px-0 js-insertar-archivo"
									data-url="<?php echo htmlspecialchars((string) $archivo['UrlPublica'], ENT_QUOTES, 'UTF-8'); ?>"
									data-tipo="<?php echo htmlspecialchars((string) $archivo['TipoArchivo'], ENT_QUOTES, 'UTF-8'); ?>">
									<div class="d-flex align-items-center gap-2">
										<i class="ti <?php echo $archivo['TipoArchivo'] === 'IMAGEN' ? 'ti-photo' : 'ti-file'; ?>"></i>
										<span class="text-truncate"><?php echo htmlspecialchars((string) $archivo['NombreOriginal'], ENT_QUOTES, 'UTF-8'); ?></span>
									</div>
								</button>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="application/json" id="comInitialBlocks"><?php echo $initialBlocksScript; ?></script>
</div>

<script src="modules/comunicados/views/comunicado/editor.js"></script>
