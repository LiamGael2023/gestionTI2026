<div class="container-xl mt-4">

    <!-- 🔷 HEADER -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Crear Backup</h4>
                <small class="text-muted">Gestión de certificados digitales</small>
            </div>
            <a href="index.php?module=certificados" class="btn btn-primary btn-sm">
                ← Volver
            </a>
        </div>
    </div>

    <?php if(empty($certificados)): ?>
        <div class="alert alert-info shadow-sm">
            Todos los certificados ya tienen backup.
        </div>
    <?php else: ?>

    <form action="" method="POST" enctype="multipart/form-data">

        <div class="row g-3">

            <!-- 🔹 CARD SELECCIÓN -->
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">
                        Seleccionar Certificado
                    </div>
                    <div class="card-body">

                        <label class="form-label small text-muted">
                            Buscar por nombre, DNI o tipo
                        </label>

                        <select name="id_certificado" id="id_certificado" 
                                class="form-select select-buscador" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach($certificados as $c): ?>
                                <option value="<?= htmlspecialchars($c['id_certificado']) ?>">
                                    <?= htmlspecialchars($c['nombres'] . " " . $c['apellidos'] . " | " . $c['dni'] . " | " . $c['tipo_certificado']." | " . $c['fecha_emision']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>
            </div>

            <!-- 🔹 CARD ARCHIVO -->
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">
                        Archivo del Certificado
                    </div>
                    <div class="card-body">

                        <label for="archivo" class="form-label small text-muted">
                            Subir archivo (.pfx)
                        </label>

                        <input type="file" name="archivo" id="archivo" 
                               class="form-control" accept=".pfx" required>

                        <div class="form-text">
                            Solo se permiten archivos de tipo .pfx
                        </div>

                    </div>
                </div>
            </div>

            <!-- 🔹 CARD ACCIONES -->
            <div class="col-lg-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex justify-content-end gap-2">

                        <a href="index.php?module=certificados" 
                           class="btn btn-outline-secondary">
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Guardar Backup
                        </button>

                    </div>
                </div>
            </div>

        </div>

    </form>

    <?php endif; ?>

</div>

<!-- 🔹 SELECT2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select-buscador').select2({
        placeholder: "Buscar certificado...",
        allowClear: true,
        width: '100%'
    });
});
</script>

<!-- 🔹 ESTILO LIMPIO -->
<style>
.card {
    border-radius: 10px;
}

.card-header {
    font-size: 14px;
}

.select2-container--default .select2-selection--single {
    height: 38px;
    padding: 5px;
    border-radius: 6px;
}
</style>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/gestionTI/public/footer.php'; ?>