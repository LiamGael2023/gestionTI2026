<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Usuario logueado
$usuario_actual = $_SESSION['nombre_completo'] ?? 'Usuario no identificado';
$id_usuario = $_SESSION['usuario_id'] ?? ($_SESSION['id_usuario'] ?? '');

// Lista de personas desde el controlador
$personas = $personas ?? [];
?>
<div class="container-xl mt-3">

    <div id="certificado-alerta" class="alert d-none" role="alert"></div>


    <form id="form-crear-certificado" method="POST" enctype="multipart/form-data" action="index.php?module=certificados&action=crear" data-ajax-url="<?= BASE_URL ?>/modules/certificados/controllers/CertificadosController.php?module=certificados&action=crear">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">

                <!-- Sección: Usuario -->
                <div class="mb-4">
                    <label for="usuario_registro" class="form-label fw-semibold text-secondary">
                        Usuario que crea el certificado
                    </label>
                    <input type="text" id="usuario_registro"
                        class="form-control bg-light border-0 shadow-sm"
                        value="<?= htmlspecialchars($usuario_actual) ?>" readonly>
                    <input type="hidden" name="id_usuario_registro"
                        value="<?= htmlspecialchars($id_usuario) ?>">
                </div>

              <div class="mb-4">
    <label for="id_persona" class="form-label fw-semibold text-secondary">
        Seleccionar Persona
    </label>
    <select name="id_persona" id="id_persona"
        class="form-select shadow-sm border-0" required>
        <option value="">--Seleccione--</option>
        <?php foreach($personas as $p): ?>
            <option value="<?= htmlspecialchars($p['id_persona']) ?>">
                <?= htmlspecialchars($p['nombres'] . " " . $p['apellidos']) ?> 
                (DNI: <?= htmlspecialchars($p['dni']) ?>, Gerencia: <?= htmlspecialchars($p['gerencia_laboral']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

                <!-- Sección: Código y Fecha en fila -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="codigo_reloj" class="form-label fw-semibold text-secondary">
                            Código Patrimonial
                        </label>
                        <input type="text" name="codigo_reloj" id="codigo_reloj"
                            class="form-control shadow-sm border-0" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_emision" class="form-label fw-semibold text-secondary">
                            Fecha de emisión
                        </label>
                        <input type="date" name="fecha_emision" id="fecha_emision"
                            class="form-control shadow-sm border-0" required>
                    </div>
                </div>

                <!-- Duración y Tipo de certificado en fila -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="duracion_anios" class="form-label fw-semibold text-secondary">
                            Duración (años)
                        </label>
                        <input type="number" name="duracion_anios" id="duracion_anios"
                            value="1" min="1" max="10"
                            class="form-control shadow-sm border-0" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo_certificado" class="form-label fw-semibold text-secondary">
                            Tipo de certificado
                        </label>
                        <select name="tipo_certificado" id="tipo_certificado"
                            class="form-select shadow-sm border-0" required>
                            <option value="">--Seleccione--</option>
                            <option value="TOKEN_SOFTWARE">TOKEN_SOFTWARE</option>
                            <option value="TOKEN_HARDWARE">TOKEN_HARDWARE</option>
                            <option value="CLOUD">CLOUD</option>
                        </select>
                    </div>
                </div>

                <!-- Archivo de evidencia -->
                <div class="mb-4">
                    <label for="archivo" class="form-label fw-semibold text-secondary">
                        Archivo de evidencia
                    </label>
                    <input type="file" name="archivo" id="archivo"
                        class="form-control shadow-sm border-0"
                        accept="image/*,application/x-pkcs12,application/pkcs12" required>
                    <small class="text-muted">Se permiten imágenes JPG, PNG, GIF o archivos PFX</small>
                </div>

                <!-- Estado y Tipo de trámite -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="estado" class="form-label fw-semibold text-secondary">
                            Estado del certificado
                        </label>
                        <select name="estado" id="estado" class="form-select shadow-sm border-0" required>
                            <option value="activo">Activo</option>
                            <option value="vencido">Vencido</option>
                            <option value="revocado">Revocado</option>
                            <option value="suspendido">Suspendido</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo_tramite" class="form-label fw-semibold text-secondary">
                            Tipo de trámite
                        </label>
                        <select name="tipo_tramite" id="tipo_tramite" class="form-select shadow-sm border-0" required>
                            <option value="Entidad" selected>Entidad</option>
                            <option value="Personal">Personal</option>
                        </select>
                    </div>
                </div>

                <!-- Estado de trámite -->
                <div class="mb-4">
                    <label for="estado_tramite" class="form-label fw-semibold text-secondary">
                        Estado de trámite
                    </label>
                    <select name="estado_tramite" id="estado_tramite" class="form-select shadow-sm border-0" required>
                        <option value="No Tramitado" selected>No Tramitado</option>
                        <option value="Tramitado">Tramitado</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="index.php?module=certificados" class="btn btn-light border shadow-sm px-4">
                        Cancelar
                    </a>
                    <button type="submit" id="btn-crear-certificado" class="btn btn-success shadow-sm px-4">
                        Crear Certificado
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/gestionTI/public/footer.php'; ?>
<!-- jQuery (requerido por Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- CSS de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- JS de Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#id_persona').select2({
        placeholder: "--Seleccione--",
        allowClear: true,
        width: '100%', // Para que ocupe todo el ancho del select
        dropdownParent: $('#id_persona').parent() // Opcional, para modales
    });

    const $form = $('#form-crear-certificado');
    const $alerta = $('#certificado-alerta');
    const $boton = $('#btn-crear-certificado');
    const textoBoton = $boton.text();
    const ajaxUrl = $form.data('ajaxUrl') || $form.attr('action');

    function mostrarAlerta(tipo, mensaje) {
        $alerta
            .removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + tipo)
            .text(mensaje);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    $form.on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $boton.prop('disabled', true).text('Guardando...');
        $alerta.addClass('d-none').text('');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).done(function(response) {
            if (response && response.success) {
                const enIframe = window.self !== window.top;

                if (enIframe && response.row && window.parent && typeof window.parent.agregarFilaCertificadoDesdeAjax === 'function') {
                    console.log('Llamando a agregarFilaCertificadoDesdeAjax desde iframe', response.row);
                    window.parent.agregarFilaCertificadoDesdeAjax(response.row);
                } else if (enIframe) {
                    console.warn('No se pudo acceder a agregarFilaCertificadoDesdeAjax. enIframe:', enIframe, 'response.row:', !!response.row, 'window.parent:', !!window.parent);
                }

                mostrarAlerta('success', response.message || 'Certificado creado correctamente');
                $form[0].reset();
                $('#id_persona').val(null).trigger('change');
                $('#estado').val('activo');
                $('#tipo_tramite').val('Entidad');
                $('#estado_tramite').val('No Tramitado');
                $('#duracion_anios').val('1');
            } else {
                mostrarAlerta('danger', (response && response.message) || 'No se pudo crear el certificado');
            }
        }).fail(function(xhr) {
            const response = xhr.responseJSON;
            const textoRespuesta = (xhr.responseText || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            mostrarAlerta('danger', (response && response.message) || textoRespuesta || 'Ocurrió un error al crear el certificado');
        }).always(function() {
            $boton.prop('disabled', false).text(textoBoton);
        });
    });
});
</script>
