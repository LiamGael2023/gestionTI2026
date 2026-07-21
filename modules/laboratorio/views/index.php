<div class="page-header d-print-none">
    <div class="container-xl">
        
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    Módulo Principal
                </div>
                <h2 class="page-title">
                    <i class="ti ti-microscope me-2"></i> Laboratorio
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <div class="row mb-4">
            <!-- Columna izquierda: Información Personal + Firma Digital -->
            <div class="col-md-6 d-flex flex-column gap-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-user me-2"></i> Información Personal
                        </h3>
                        <button class="btn btn-sm btn-ghost-primary" title="Editar perfil">
                            <i class="ti ti-edit"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if ($usuarioData): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <strong>Nombre:</strong>
                                <p class="text-secondary mb-0">
                                    <?php echo htmlspecialchars($usuarioData['nombres'] . ' ' . $usuarioData['apellidos'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>
                            <div class="mb-0">
                                <strong>Rol:</strong>
                                <p class="text-secondary mb-0">
                                    <?php echo htmlspecialchars($usuarioData['rol'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mb-0">
                                <i class="ti ti-alert-circle me-2"></i> Error al cargar datos del usuario
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($puedeSubirFirma): ?>
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-writing-sign me-2"></i> Mi Firma Digital
                        </h3>
                        <?php if ($firmaActual): ?>
                            <span class="badge bg-success"><i class="ti ti-check me-1"></i>Registrada</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="ti ti-alert-triangle me-1"></i>Sin firma</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($firmaActual && !empty($firmaActual['Img_Firma'])): ?>
                            <div id="firma-preview-container" class="mb-3 border rounded p-2 bg-light" style="min-height:90px;">
                                <img id="firma-preview-img"
                                     src="<?php echo htmlspecialchars($firmaActual['Img_Firma'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="Mi firma digital"
                                     style="max-height:80px;max-width:100%;object-fit:contain;">
                            </div>
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('input-firma-file').click()">
                                    <i class="ti ti-refresh me-1"></i> Cambiar firma
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarFirma()">
                                    <i class="ti ti-trash me-1"></i> Eliminar
                                </button>
                            </div>
                        <?php else: ?>
                            <div id="firma-preview-container" class="mb-3 border rounded p-3 bg-light d-flex align-items-center justify-content-center" style="min-height:90px;">
                                <span class="text-muted small"><i class="ti ti-writing-sign me-1"></i>Sin firma registrada</span>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('input-firma-file').click()">
                                <i class="ti ti-upload me-1"></i> Subir firma
                            </button>
                        <?php endif; ?>
                        <input type="file" id="input-firma-file" accept="image/png,image/jpeg,image/jpg" class="d-none">
                        <p class="text-muted small mt-2 mb-0">
                            PNG o JPG, fondo blanco o transparente, máx. 2 MB.<br>
                            Esta firma aparecerá en los reportes Excel de muestras.
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Columna derecha: Responsabilidades -->
            <div class="col-md-6">
                <div class="card h-100 d-none d-md-flex" id="card-responsabilidades">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-briefcase me-2"></i> Responsabilidades
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($responsabilidades)): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($responsabilidades as $resp): ?>
                                    <li class="mb-2 pb-2 border-bottom">
                                        <i class="ti ti-<?php echo htmlspecialchars($resp['icono'], ENT_QUOTES, 'UTF-8'); ?> text-primary me-2"></i>
                                        <strong><?php echo htmlspecialchars($resp['descripcion'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="ti ti-alert-circle me-2"></i> No tienes responsabilidades asignadas
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal EDITOR DE FIRMA con preview simplificado -->
        <div class="modal fade" id="modal-confirmar-firma" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:95vw;">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="ti ti-writing-sign me-2"></i>Posiciona tu firma en los reportes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-2">
                            <i class="ti ti-hand-move me-1"></i> <strong>Arrastra</strong> para mover. 
                            <i class="ti ti-arrows-diagonal me-1"></i> <strong>Esquina ▣</strong> para redimensionar.
                            <a href="modules/laboratorio/muestra/plantilla/CSJ-DRDYCS-LAYS%20%E2%80%93%20R%20-%202-%20RESULTADOS%20ANALISIS%20DE%20AGUAS.xlsx" target="_blank" class="ms-3" style="font-size:11px;">
                                <i class="ti ti-external-link me-1"></i> Ver plantilla Agua (R-2)
                            </a>
                            <a href="modules/laboratorio/muestra/plantilla/CSJ-DRDYCS-LAYS%20%E2%80%93%20R%20-%201-RESULTADOS%20ANALISIS%20DE%20%20SUELOS.xlsx" target="_blank" style="font-size:11px;margin-left:8px;">
                                <i class="ti ti-external-link me-1"></i> Ver plantilla Suelo (R-1)
                            </a>
                        </p>

                        <!-- Tabs -->
                        <ul class="nav nav-tabs mb-3" id="firma-editor-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#editor-agua" type="button">
                                    <i class="ti ti-drop me-1"></i> Informe de Agua (R-2)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editor-suelo" type="button">
                                    <i class="ti ti-plant me-1"></i> Informe de Suelo (R-1)
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- AGUA -->
                            <div class="tab-pane fade show active" id="editor-agua">
                                <div style="position:relative;background:white;border:2px solid #e0e0e0;padding:15px;">
                                    <div style="text-align:center;border-bottom:2px solid #004d99;padding-bottom:8px;margin-bottom:10px;">
                                        <div style="font-weight:700;color:#004d99;font-size:12px;">CHAVIMOCHIC</div>
                                        <div style="font-weight:600;font-size:10px;color:#333;">INFORME DE ENSAYO — RESULTADOS DE ANÁLISIS DE AGUAS</div>
                                        <div style="font-size:7px;color:#888;">Formato R-2</div>
                                    </div>
                                    <table style="width:100%;border-collapse:collapse;font-size:8px;margin-bottom:10px;">
                                        <tr style="background:#f0f4f8;"><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Solicitante</td><td style="padding:4px;border:1px solid #ddd;">_________________</td><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Valle</td><td style="padding:4px;border:1px solid #ddd;">Chao</td></tr>
                                        <tr style="background:#f0f4f8;"><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Fecha Toma</td><td style="padding:4px;border:1px solid #ddd;">__/__/____</td><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Servicio</td><td style="padding:4px;border:1px solid #ddd;">INTERNO ☑</td></tr>
                                    </table>
                                    <table style="width:100%;border-collapse:collapse;font-size:7px;margin-bottom:15px;">
                                        <tr style="background:#004d99;color:white;"><th style="padding:3px;border:1px solid #004d99;">Parámetro</th><th style="padding:3px;border:1px solid #004d99;">U.M.</th><th style="padding:3px;border:1px solid #004d99;">Resultado</th><th style="padding:3px;border:1px solid #004d99;">ECA</th></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">pH</td><td style="padding:2px;border:1px solid #eee;text-align:center;">pH</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">6.5-8.5</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Conductividad Eléctrica</td><td style="padding:2px;border:1px solid #eee;text-align:center;">µS/cm</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">1500</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Turbidez</td><td style="padding:2px;border:1px solid #eee;text-align:center;">NTU</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">5</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Cloruros</td><td style="padding:2px;border:1px solid #eee;text-align:center;">mg/L</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">250</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Nitratos</td><td style="padding:2px;border:1px solid #eee;text-align:center;">mg/L</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">50</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Dureza Total</td><td style="padding:2px;border:1px solid #eee;text-align:center;">CaCO3 mg/L</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">500</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Sulfatos</td><td style="padding:2px;border:1px solid #eee;text-align:center;">mg/L</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">250</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Coliformes Totales</td><td style="padding:2px;border:1px solid #eee;text-align:center;">UFC/100mL</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">0</td></tr>
                                    </table>
                                    <!-- Zona de firmas: replica exacta de la plantilla R-2 -->
                                    <div style="border-top:1px solid #999;padding-top:12px;margin-top:8px;">
                                        <div style="display:flex;">
                                            <div style="flex:1;text-align:center;position:relative;">
                                                <div style="font-size:8px;font-weight:600;margin-bottom:8px;">ENCARGADO DE LABORATORIO</div>
                                                <div id="firma-zone-encargado-agua" style="height:90px;width:90%;margin:0 auto;border:2px dashed #c0c0c0;border-radius:4px;position:relative;background:#fff;">
                                                    <div id="firma-draggable-encargado-agua" data-zone="encargado_agua"
                                                         style="position:absolute;top:15px;left:15px;width:130px;height:35px;cursor:move;display:none;z-index:10;">
                                                        <img id="firma-img-encargado-agua" src="" style="width:100%;height:100%;object-fit:contain;pointer-events:none;">
                                                        <div style="position:absolute;right:-4px;bottom:-4px;width:12px;height:12px;background:#004d99;border:2px solid white;border-radius:2px;cursor:se-resize;z-index:11;"></div>
                                                    </div>
                                                    <span class="firma-placeholder" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#aaa;font-size:9px;pointer-events:none;">Arrastra la firma aquí</span>
                                                </div>
                                                <div style="font-size:7px;color:#666;margin-top:4px;">Nombre y Apellidos</div>
                                                <div style="font-size:7px;color:#666;">CIP N° ________</div>
                                            </div>
                                            <div style="flex:1;text-align:center;position:relative;">
                                                <div style="font-size:8px;font-weight:600;margin-bottom:8px;">ANALISTA RESPONSABLE</div>
                                                <div id="firma-zone-analista-agua" style="height:90px;width:90%;margin:0 auto;border:2px dashed #c0c0c0;border-radius:4px;position:relative;background:#fff;">
                                                    <div id="firma-draggable-analista-agua" data-zone="analista_agua"
                                                         style="position:absolute;top:15px;right:15px;width:130px;height:35px;cursor:move;display:none;z-index:10;">
                                                        <img id="firma-img-analista-agua" src="" style="width:100%;height:100%;object-fit:contain;pointer-events:none;">
                                                        <div style="position:absolute;right:-4px;bottom:-4px;width:12px;height:12px;background:#004d99;border:2px solid white;border-radius:2px;cursor:se-resize;z-index:11;"></div>
                                                    </div>
                                                    <span class="firma-placeholder" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#aaa;font-size:9px;pointer-events:none;">Arrastra la firma aquí</span>
                                                </div>
                                                <div style="font-size:7px;color:#666;margin-top:4px;">Nombre y Apellidos</div>
                                                <div style="font-size:7px;color:#666;">CIP N° ________</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align:center;margin-top:8px;font-size:7px;color:#aaa;">CHAVIMOCHIC · Laboratorio de Aguas y Suelos · Formato R-2</div>
                                </div>
                            </div>

                            <!-- SUELO -->
                            <div class="tab-pane fade" id="editor-suelo">
                                <div style="position:relative;background:white;border:2px solid #e0e0e0;padding:15px;">
                                    <div style="text-align:center;border-bottom:2px solid #8B4513;padding-bottom:8px;margin-bottom:10px;">
                                        <div style="font-weight:700;color:#8B4513;font-size:12px;">CHAVIMOCHIC</div>
                                        <div style="font-weight:600;font-size:10px;color:#333;">INFORME DE ANÁLISIS DE SUELOS</div>
                                        <div style="font-size:7px;color:#888;">Formato R-1</div>
                                    </div>
                                    <table style="width:100%;border-collapse:collapse;font-size:8px;margin-bottom:10px;">
                                        <tr style="background:#fdf5e6;"><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Solicitante</td><td style="padding:4px;border:1px solid #ddd;">_________________</td><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Valle</td><td style="padding:4px;border:1px solid #ddd;">Virú</td></tr>
                                        <tr style="background:#fdf5e6;"><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Profundidad</td><td style="padding:4px;border:1px solid #ddd;">0-30 cm</td><td style="padding:4px;border:1px solid #ddd;font-weight:600;">Cultivo</td><td style="padding:4px;border:1px solid #ddd;">Palto</td></tr>
                                    </table>
                                    <table style="width:100%;border-collapse:collapse;font-size:7px;margin-bottom:15px;">
                                        <tr style="background:#8B4513;color:white;"><th style="padding:3px;border:1px solid #8B4513;">Parámetro</th><th style="padding:3px;border:1px solid #8B4513;">U.M.</th><th style="padding:3px;border:1px solid #8B4513;">Resultado</th><th style="padding:3px;border:1px solid #8B4513;">Interpretación</th></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">pH</td><td style="padding:2px;border:1px solid #eee;text-align:center;">pH</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Conductividad Eléctrica</td><td style="padding:2px;border:1px solid #eee;text-align:center;">mS/cm</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Materia Orgánica</td><td style="padding:2px;border:1px solid #eee;text-align:center;">%</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Nitrógeno</td><td style="padding:2px;border:1px solid #eee;text-align:center;">mg/kg PS</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Fósforo</td><td style="padding:2px;border:1px solid #eee;text-align:center;">mg/kg PS</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Potasio</td><td style="padding:2px;border:1px solid #eee;text-align:center;">mg/kg PS</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td></tr>
                                        <tr><td style="padding:2px;border:1px solid #eee;">Textura</td><td style="padding:2px;border:1px solid #eee;text-align:center;">%</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td><td style="padding:2px;border:1px solid #eee;text-align:center;">___</td></tr>
                                    </table>
                                    <!-- Zona de firmas: replica de la plantilla R-1 -->
                                    <div style="border-top:1px solid #999;padding-top:12px;margin-top:8px;">
                                        <div style="display:flex;">
                                            <div style="flex:1;text-align:center;position:relative;">
                                                <div style="font-size:8px;font-weight:600;margin-bottom:8px;">ENCARGADO DE LABORATORIO</div>
                                                <div id="firma-zone-encargado-suelo" style="height:90px;width:90%;margin:0 auto;border:2px dashed #c0c0c0;border-radius:4px;position:relative;background:#fff;">
                                                    <div id="firma-draggable-encargado-suelo" data-zone="encargado_suelo"
                                                         style="position:absolute;top:15px;left:15px;width:130px;height:35px;cursor:move;display:none;z-index:10;">
                                                        <img id="firma-img-encargado-suelo" src="" style="width:100%;height:100%;object-fit:contain;pointer-events:none;">
                                                        <div style="position:absolute;right:-4px;bottom:-4px;width:12px;height:12px;background:#8B4513;border:2px solid white;border-radius:2px;cursor:se-resize;z-index:11;"></div>
                                                    </div>
                                                    <span class="firma-placeholder" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#aaa;font-size:9px;pointer-events:none;">Arrastra la firma aquí</span>
                                                </div>
                                                <div style="font-size:7px;color:#666;margin-top:4px;">Nombre y Apellidos</div>
                                                <div style="font-size:7px;color:#666;">CIP N° ________</div>
                                            </div>
                                            <div style="flex:1;text-align:center;position:relative;">
                                                <div style="font-size:8px;font-weight:600;margin-bottom:8px;">ANALISTA RESPONSABLE</div>
                                                <div id="firma-zone-analista-suelo" style="height:90px;width:90%;margin:0 auto;border:2px dashed #c0c0c0;border-radius:4px;position:relative;background:#fff;">
                                                    <div id="firma-draggable-analista-suelo" data-zone="analista_suelo"
                                                         style="position:absolute;top:15px;right:15px;width:130px;height:35px;cursor:move;display:none;z-index:10;">
                                                        <img id="firma-img-analista-suelo" src="" style="width:100%;height:100%;object-fit:contain;pointer-events:none;">
                                                        <div style="position:absolute;right:-4px;bottom:-4px;width:12px;height:12px;background:#8B4513;border:2px solid white;border-radius:2px;cursor:se-resize;z-index:11;"></div>
                                                    </div>
                                                    <span class="firma-placeholder" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#aaa;font-size:9px;pointer-events:none;">Arrastra la firma aquí</span>
                                                </div>
                                                <div style="font-size:7px;color:#666;margin-top:4px;">Nombre y Apellidos</div>
                                                <div style="font-size:7px;color:#666;">CIP N° ________</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align:center;margin-top:8px;font-size:7px;color:#aaa;">CHAVIMOCHIC · Laboratorio de Aguas y Suelos · Formato R-1</div>
                                </div>
                            </div>
                        </div>

                        <!-- Controles -->
                        <div class="mt-3 p-2 border rounded bg-white">
                            <div class="d-flex gap-3 flex-wrap align-items-center">
                                <label class="form-label mb-0 fw-semibold" style="font-size:12px;">Rol:</label>
                                <label class="form-check mb-0"><input class="form-check-input" type="radio" name="rol-firma-editor" value="encargado" checked><span style="font-size:12px;">Encargado</span></label>
                                <label class="form-check mb-0"><input class="form-check-input" type="radio" name="rol-firma-editor" value="analista"><span style="font-size:12px;">Analista</span></label>
                                <label class="form-check mb-0"><input class="form-check-input" type="radio" name="rol-firma-editor" value="ambos"><span style="font-size:12px;">Ambos</span></label>
                                <span class="ms-auto" style="font-size:10px;color:#888;">
                                    Tamaño: <strong id="firma-size-label">130×35</strong> — Pos: <strong id="firma-pos-label">0%, 0%</strong>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btn-guardar-firma">
                            <i class="ti ti-device-floppy me-1"></i> Guardar firma y posición
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECCIÓN 3: MENÚ DE MÓDULOS ===== -->
        <div class="row mb-3">
            <div class="col-12">
                <h3 class="mb-3">
                    <i class="ti ti-apps me-2"></i> Menú
                </h3>
            </div>
        </div>

        <!-- Grid de módulos con tarjetas clicables -->
        <div class="row row-deck row-cards">
            <?php if (!empty($responsabilidades)): ?>
                <?php foreach ($responsabilidades as $resp): ?>
                    <div class="col-sm-6 col-lg-4">
                        <a href="<?php echo htmlspecialchars($resp['url'], ENT_QUOTES, 'UTF-8'); ?>" 
                           class="card card-link border-0 shadow-sm bg-gradient-<?php echo htmlspecialchars($resp['color'], ENT_QUOTES, 'UTF-8'); ?> text-white">
                            <div class="card-body text-center py-5">
                                <div class="mb-3">
                                    <i class="ti ti-<?php echo htmlspecialchars($resp['icono'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 2.5rem;"></i>
                                </div>
                                <h4 class="card-title text-white mb-1">
                                    <?php echo htmlspecialchars($resp['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </h4>
                                <p class="text-white-50 font-small">
                                    <?php echo htmlspecialchars($resp['descripcion'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-circle me-2"></i>
                        <strong>Sin acceso</strong> - No tienes responsabilidades asignadas en este módulo.
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ===== ESTILOS PERSONALIZADOS ===== -->
<style>
    /* Gradientes para las tarjetas de módulos */
    .bg-gradient-primary {
        background: linear-gradient(135deg, var(--pech-verde, #009540) 0%, #00c851 100%) !important;
    }
    .bg-gradient-danger {
        background: linear-gradient(135deg, #d63939 0%, #e8534c 100%) !important;
    }
    
    /* Tarjeta de módulo */
    .card-link {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        cursor: pointer;
        border-radius: 8px;
        overflow: hidden;
    }

    .card-link:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
        color: inherit;
        text-decoration: none;
    }

    .card-link .card-title {
        font-weight: 600;
    }

    /* Estilos para listas */
    .list-unstyled li:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    /* Font small */
    .font-small {
        font-size: 0.875rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var inputFile    = document.getElementById('input-firma-file');
    var previewCont  = document.getElementById('firma-preview-container');
    var modalEl      = document.getElementById('modal-confirmar-firma');
    var btnGuardar   = document.getElementById('btn-guardar-firma');
    var firmaApiBase = 'modules/laboratorio/controllers/FirmaAPI.php';

    var pendingBase64 = null;
    var bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

    // === SISTEMA DE ARRASTRE Y REDIMENSIONAMIENTO ===
    var dragState = { active: null, startX: 0, startY: 0, startLeft: 0, startTop: 0, startW: 0, startH: 0, mode: 'move' };
    
    // IDs de los 4 draggables: encargado_agua, analista_agua, encargado_suelo, analista_suelo
    var draggableIds = [
        'firma-draggable-encargado-agua', 'firma-draggable-analista-agua',
        'firma-draggable-encargado-suelo', 'firma-draggable-analista-suelo'
    ];

    function initDrag(el) {
        if (!el || el._dragInit) return;
        el._dragInit = true;

        el.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return;
            // Check if clicking on resize handle
            var target = e.target;
            var isResize = (target.style.cursor === 'se-resize' || 
                           (target.parentElement === el && target.style.position === 'absolute' && target.style.right === '-4px'));
            
            e.preventDefault();
            var rect = el.getBoundingClientRect();
            
            dragState.active = el;
            dragState.mode = isResize ? 'resize' : 'move';
            dragState.startX = e.clientX;
            dragState.startY = e.clientY;
            dragState.startLeft = el.offsetLeft;
            dragState.startTop = el.offsetTop;
            dragState.startW = rect.width;
            dragState.startH = rect.height;
        });
    }

    document.addEventListener('mousemove', function(e) {
        if (!dragState.active) return;
        var dx = e.clientX - dragState.startX;
        var dy = e.clientY - dragState.startY;
        var el = dragState.active;
        var zone = el.parentElement;
        var zoneW = zone.clientWidth;
        var zoneH = zone.clientHeight;

        if (dragState.mode === 'move') {
            var newLeft = dragState.startLeft + dx;
            var newTop = dragState.startTop + dy;
            newLeft = Math.max(0, Math.min(newLeft, zoneW - 30));
            newTop = Math.max(0, Math.min(newTop, zoneH - 10));
            el.style.left = newLeft + 'px';
            el.style.top = newTop + 'px';
        } else {
            var newW = Math.max(40, Math.min(dragState.startW + dx, zoneW - dragState.startLeft));
            var newH = Math.max(15, Math.min(dragState.startH + dy, zoneH - dragState.startTop));
            el.style.width = newW + 'px';
            el.style.height = newH + 'px';
        }
        updateSizeLabel();
    });

    document.addEventListener('mouseup', function() {
        if (dragState.active) {
            updateSizeLabel();
            dragState.active = null;
        }
    });

    function updateSizeLabel() {
        var role = document.querySelector('input[name="rol-firma-editor"]:checked').value;
        var tabActive = document.querySelector('#editor-agua.show, #editor-agua.active') ? 'agua' : 'suelo';
        var draggables = getActiveDraggables(role, tabActive);
        if (draggables.length > 0) {
            var el = draggables[0];
            var w = Math.round(el.clientWidth || 140);
            var h = Math.round(el.clientHeight || 40);
            var zone = el.parentElement;
            var leftPct = zone.clientWidth > 0 ? Math.round(el.offsetLeft / zone.clientWidth * 100) : 0;
            var topPct = zone.clientHeight > 0 ? Math.round(el.offsetTop / zone.clientHeight * 100) : 0;
            document.getElementById('firma-size-label').textContent = w + '\u00d7' + h;
            document.getElementById('firma-pos-label').textContent = leftPct + '%, ' + topPct + '%';
        }
    }

    function getActiveDraggables(role, tipo) {
        var result = [];
        var suffix = tipo === 'agua' ? 'agua' : 'suelo';
        if (role === 'encargado' || role === 'ambos') {
            result.push(document.getElementById('firma-draggable-encargado-' + suffix));
        }
        if (role === 'analista' || role === 'ambos') {
            result.push(document.getElementById('firma-draggable-analista-' + suffix));
        }
        return result.filter(Boolean);
    }

    function showFirmaInZones(b64, role) {
        // Reset ALL first
        draggableIds.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) { 
                el.style.display = 'none'; 
                el.querySelector('img').src = '';
            }
        });
        // Show placeholders
        document.querySelectorAll('.firma-placeholder').forEach(function(sp) { sp.style.display = ''; });

        // Set in active zones for BOTH tabs
        ['agua', 'suelo'].forEach(function(tipo) {
            var draggables = getActiveDraggables(role, tipo);
            draggables.forEach(function(el) {
                var img = el.querySelector('img');
                if (img) img.src = b64;
                el.style.display = 'block';
                el.style.left = '10px';
                el.style.width = '140px';
                el.style.height = '40px';
                if (role === 'analista' && tipo === 'agua') el.style.top = '10px';
                else el.style.top = '10px';
                initDrag(el);
            });
            // Hide placeholders in used zones
            var usedIds = draggables.map(function(d) { return d.parentElement.id; });
            usedIds.forEach(function(zoneId) {
                var zone = document.getElementById(zoneId);
                if (zone) {
                    var ph = zone.querySelector('.firma-placeholder');
                    if (ph && draggables.some(function(d) { return d.style.display !== 'none'; })) {
                        ph.style.display = 'none';
                    }
                }
            });
        });
        updateSizeLabel();
    }

    if (inputFile) {
        inputFile.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            if (!file.type.match(/image\/(png|jpeg)/)) {
                Swal.fire('Formato inválido', 'Solo se aceptan imágenes PNG o JPG.', 'warning');
                this.value = ''; return;
            }
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Archivo muy grande', 'La imagen no debe superar 2 MB.', 'warning');
                this.value = ''; return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                pendingBase64 = e.target.result;
                document.querySelector('input[name="rol-firma-editor"][value="encargado"]').checked = true;
                showFirmaInZones(pendingBase64, 'encargado');
                if (bsModal) bsModal.show();
            };
            reader.readAsDataURL(file);
            this.value = '';
        });
    }

    // Radio buttons change
    document.querySelectorAll('input[name="rol-firma-editor"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (pendingBase64) showFirmaInZones(pendingBase64, this.value);
        });
    });

    // Tab change: re-show firma in correct zones
    document.querySelectorAll('#firma-editor-tabs button').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function() {
            if (pendingBase64) {
                var role = document.querySelector('input[name="rol-firma-editor"]:checked').value;
                showFirmaInZones(pendingBase64, role);
            }
            updateSizeLabel();
        });
    });

    // Collect position data on save
    function collectPositions() {
        var role = document.querySelector('input[name="rol-firma-editor"]:checked').value;
        var positions = {};
        ['agua', 'suelo'].forEach(function(tipo) {
            ['encargado', 'analista'].forEach(function(rol) {
                var el = document.getElementById('firma-draggable-' + rol + '-' + tipo);
                if (el && el.style.display !== 'none') {
                    var zone = el.parentElement;
                    positions[rol + '_' + tipo] = {
                        left: zone.clientWidth > 0 ? Math.round(el.offsetLeft / zone.clientWidth * 100) : 10,
                        top: zone.clientHeight > 0 ? Math.round(el.offsetTop / zone.clientHeight * 100) : 10,
                        width: Math.round(el.clientWidth || 140),
                        height: Math.round(el.clientHeight || 40),
                        widthPct: zone.clientWidth > 0 ? Math.round(el.clientWidth / zone.clientWidth * 100) : 30
                    };
                }
            });
        });
        return { role: role, positions: positions };
    }

    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {
            if (!pendingBase64) return;
            btnGuardar.disabled = true;
            var posData = collectPositions();
            fetch(firmaApiBase + '?action=guardar_firma', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ img_firma: pendingBase64, positions: posData })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (bsModal) bsModal.hide();
                if (d.success) {
                    if (previewCont) {
                        previewCont.innerHTML = '<img src="' + pendingBase64 + '" alt="Firma" style="max-height:80px;max-width:100%;object-fit:contain;">';
                    }
                    Swal.fire({ icon: 'success', title: 'Firma guardada', text: 'Posición y tamaño guardados.', timer: 2200, showConfirmButton: false })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Error', d.message || 'No se pudo guardar la firma.', 'error');
                }
            })
            .catch(function () {
                if (bsModal) bsModal.hide();
                Swal.fire('Error', 'Error de red al guardar la firma.', 'error');
            })
            .finally(function () { btnGuardar.disabled = false; });
        });
    }
});

function eliminarFirma() {
    Swal.fire({
        title: '¿Eliminar firma?',
        text: 'No podrás firmar muestras hasta registrar una nueva.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d63939',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        fetch('modules/laboratorio/controllers/FirmaAPI.php?action=eliminar_firma', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                Swal.fire({ icon: 'success', title: 'Firma eliminada', timer: 1800, showConfirmButton: false })
                    .then(function () { location.reload(); });
            } else {
                Swal.fire('Error', d.message || 'No se pudo eliminar.', 'error');
            }
        })
        .catch(function () { Swal.fire('Error', 'Error de red.', 'error'); });
    });
}
</script>
