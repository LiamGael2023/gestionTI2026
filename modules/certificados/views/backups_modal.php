<div class="page-wrapper">
<div class="card">

    <!-- HEADER -->
    <div class="card-header d-flex justify-content-between align-items-center">

        <div>
            <strong>Certificado:</strong>
            <?php echo $certificado['nombres']." ".$certificado['apellidos']; ?>
            (DNI: <?php echo $certificado['dni']; ?>)
        </div>

        <!-- BOTÓN FORM -->
        <button class="btn btn-primary btn-sm"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#formBackup"
                aria-expanded="false">
            + Generar Backup
        </button>

    </div>

    <!-- FORMULARIO COLAPSABLE -->
    <div class="collapse" id="formBackup">

        <div class="card-body border-bottom">

            <form action="index.php?module=certificados&action=guardarBackup3"
                  method="POST"
                enctype="multipart/form-data"
                class="js-backup-form">

                <!-- ID CERTIFICADO -->
                <input type="hidden"
                       name="id_certificado"
                       value="<?php echo $certificado['id_certificado']; ?>">

                <div class="row g-3">

                    <!-- ARCHIVO -->
                    <div class="col-md-8">
                        <label class="form-label">Archivo Backup (.pfx)</label>

                        <input type="file"
                               name="archivo"
                               class="form-control"
                               accept=".pfx"
                               required>

                        <small class="text-muted">
                            Suba el archivo .pfx del certificado
                        </small>
                    </div>

                    <!-- BOTÓN -->
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            Guardar Backup
                        </button>
                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- TABLA BACKUPS -->
    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>
                <tr>
                    <th>ID Backup</th>
                    <th>Código Patrimonial</th>
                    <th>Identificador</th>
                    <th>Archivo</th>
                    <th>Fecha Backup</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($backups)): ?>

                    <?php foreach ($backups as $b): ?>
                        <tr>

                            <td><?php echo $b['id_backup']; ?></td>

                            <td><?php echo $b['codigo_reloj']; ?></td>

                            <td><?php echo $b['identificador']; ?></td>

                            <td><?php echo $b['ruta_archivo']; ?></td>

                            <td>
                                <?php echo date('d/m/Y H:i:s', strtotime($b['fecha_backup'])); ?>
                            </td>

                            <td class="text-center">

                                <!-- DESCARGAR (DESDE SERVIDOR) -->
                               <a href="modules/uploads/backups/<?php echo $b['ruta_archivo']; ?>" 
                                   class="btn btn-sm btn-success"
                                   download>
                                   Descargar
                                </a>

                                <!-- ELIMINAR (BD + ARCHIVO) -->
                                <a href="index.php?module=certificados&action=eliminarBackup3&id=<?php echo $b['id_backup']; ?>&cert=<?php echo $certificado['id_certificado']; ?>"
                                              class="btn btn-sm btn-danger js-backup-delete"
                                              data-confirm="¿Eliminar este backup?">
                                    Eliminar
                                </a>

                            </td>

                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center">
                            No hay backups disponibles
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</div>