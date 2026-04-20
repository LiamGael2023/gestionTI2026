<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">Detalle del Certificado</h3>
    </div>
    <div class="card-body">

        <!-- Datos de la Persona -->
        <h5 class="mb-3">Datos de la Persona</h5>
        <div class="row mb-3">
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>DNI:</strong> <?= $certificado['dni'] ?></li>
                    <li class="list-group-item"><strong>Nombre:</strong> <?= $certificado['nombres']." ".$certificado['apellidos'] ?></li>
                    <li class="list-group-item"><strong>Correo:</strong> <?= $certificado['correo'] ?: '-' ?></li>
                    <li class="list-group-item"><strong>Teléfono:</strong> <?= $certificado['telefono'] ?: '-' ?></li>
                    <li class="list-group-item"><strong>Gerencia:</strong> <?= $certificado['gerencia_laboral'] ?: '-' ?></li>
                </ul>
            </div>
        </div>

        <hr>

        <!-- Datos del Certificado -->
        <h5 class="mb-3">Datos del Certificado</h5>
        <div class="row mb-3">
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Código Patrimonial:</strong> <?= $certificado['codigo_reloj'] ?></li>
                    <li class="list-group-item"><strong>Tipo de certificado:</strong> <?= $certificado['tipo_certificado'] ?></li>
                    <li class="list-group-item"><strong>Duración:</strong> <?= $certificado['duracion_anios'] ?> años</li>
                    <li class="list-group-item"><strong>Estado:</strong>
                        <?php if($certificado['estado'] == 'activo'): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Vencido</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item"><strong>Fecha de emisión:</strong> <?= $certificado['fecha_emision']->format('Y-m-d') ?></li>
                    <li class="list-group-item"><strong>Fecha de vencimiento:</strong> <?= $certificado['fecha_vencimiento']->format('Y-m-d') ?></li>
                    <li class="list-group-item"><strong>Fecha de registro:</strong> <?= $certificado['fecha_creacion']->format('Y-m-d') ?></li>
                </ul>
            </div>
        </div>

        <hr>

        <!-- Archivo Evidencia -->
        <h5 class="mb-3">Archivo Evidencia</h5>
        <?php if(!empty($certificado['evidencia'])): ?>
        <div class="mb-3 text-center">
            <a href="modules/uploads/certificados/<?= $certificado['evidencia'] ?>" target="_blank">
                <img src="modules/uploads/certificados/<?= $certificado['evidencia'] ?>" 
                     class="img-fluid rounded border p-2" 
                     style="max-width: 350px;">
            </a>
        </div>
        <?php else: ?>
        <p class="text-muted">No se ha subido evidencia.</p>
        <?php endif; ?>

        <hr>

        <!-- Backups -->
        <h5 class="mb-3">Backups del Certificado</h5>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Identificador</th>
                        <th>Archivo</th>
                        <th>Fecha Backup</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($backups)): ?>
                        <?php foreach($backups as $b): ?>
                        <tr>
                            <td><?= $b['id_backup'] ?></td>
                            <td><?= $b['identificador'] ?></td>
                            <td>
                                <a href="modules/uploads/backups/<?= $b['ruta_archivo'] ?>" target="_blank">
                                    <?= $b['ruta_archivo'] ?>
                                </a>
                            </td>
                            <td><?= $b['fecha_backup']->format('Y-m-d H:i') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No hay backups disponibles</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>