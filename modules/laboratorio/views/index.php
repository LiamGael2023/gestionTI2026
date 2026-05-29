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

        <!-- Modal de previsualización / confirmación de firma -->
        <div class="modal fade" id="modal-confirmar-firma" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ti ti-writing-sign me-2"></i>Confirmar firma digital</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-2 text-muted small">Esta imagen se usará como tu firma en los reportes.</p>
                        <div class="border rounded p-2 bg-light mb-3 d-inline-block" style="max-width:100%;">
                            <img id="modal-firma-preview" src="" alt="Preview" style="max-height:120px;max-width:100%;object-fit:contain;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btn-guardar-firma">
                            <i class="ti ti-device-floppy me-1"></i>Guardar firma
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
    var modalPreview = document.getElementById('modal-firma-preview');
    var btnGuardar   = document.getElementById('btn-guardar-firma');
    var firmaApiBase = 'modules/laboratorio/controllers/FirmaAPI.php';

    var pendingBase64 = null;
    var bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

    if (inputFile) {
        inputFile.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;

            if (!file.type.match(/image\/(png|jpeg)/)) {
                Swal.fire('Formato inválido', 'Solo se aceptan imágenes PNG o JPG.', 'warning');
                this.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Archivo muy grande', 'La imagen no debe superar 2 MB.', 'warning');
                this.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                pendingBase64 = e.target.result;
                if (modalPreview) modalPreview.src = pendingBase64;
                if (bsModal) bsModal.show();
            };
            reader.readAsDataURL(file);
            this.value = '';
        });
    }

    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {
            if (!pendingBase64) return;
            btnGuardar.disabled = true;
            fetch(firmaApiBase + '?action=guardar_firma', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ img_firma: pendingBase64 })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (bsModal) bsModal.hide();
                if (d.success) {
                    if (previewCont) {
                        previewCont.innerHTML = '<img src="' + pendingBase64 + '" alt="Firma" style="max-height:80px;max-width:100%;object-fit:contain;">';
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Firma guardada',
                        text: 'Tu firma digital ha sido registrada correctamente.',
                        timer: 2200,
                        showConfirmButton: false
                    }).then(function () { location.reload(); });
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
