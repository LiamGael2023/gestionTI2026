<div class="container-xl mt-4">

    <!-- 🔷 HEADER -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Crear Backup de Certificado</h4>
                <small class="text-muted">Solo certificados activos del sistema</small>
            </div>

            <a href="index.php?module=certificados" class="btn btn-outline-primary btn-sm">
                ← Volver
            </a>
        </div>
    </div>

    <?php if (empty($certificados)): ?>
        <div class="alert alert-info shadow-sm">
            No hay certificados disponibles para backup.
        </div>
    <?php else: ?>

    <!-- FORM BACKUP -->
    <form action="index.php?module=certificados&action=guardarBackup" method="POST" enctype="multipart/form-data">

        <div class="row g-3">

            <!-- CERTIFICADO -->
            <div class="col-lg-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">
                        Seleccionar Certificado
                    </div>
                    <div class="card-body">

                        <select name="id_certificado"
                                id="id_certificado"
                                class="form-select select-buscador"
                                required>
                            <option value="">-- Seleccione certificado --</option>

                            <?php foreach($certificados as $c): ?>
                                <option value="<?= (int)$c['id_certificado'] ?>">
                                    <?= htmlspecialchars(
                                        $c['nombres'] . " " . $c['apellidos']
                                        . " | DNI: " . $c['dni']
                                        . " | " . $c['tipo_certificado']
                                        . " | Emisión: " . $c['fecha_emision']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                    </div>
                </div>
            </div>

            <!-- ARCHIVO -->
            <div class="col-lg-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">
                        Archivo del Backup (.pfx)
                    </div>
                    <div class="card-body">

                        <input type="file"
                               name="archivo"
                               class="form-control"
                               accept=".pfx"
                               required>

                        <small class="text-muted">
                            Solo archivos .pfx del certificado seleccionado
                        </small>

                    </div>
                </div>
            </div>

            <!-- BOTONES -->
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