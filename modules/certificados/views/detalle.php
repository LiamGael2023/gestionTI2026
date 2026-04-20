<div class="card">

<div class="card-header">
<h3 class="card-title">Detalle del Certificado</h3>
</div>

<div class="card-body">

<h5>Datos de la Persona</h5>

<p><strong>DNI:</strong> <?= $certificado['dni'] ?></p>

<p><strong>Nombre:</strong>
<?= $certificado['nombres']." ".$certificado['apellidos'] ?>
</p>

<p><strong>Correo:</strong> <?= $certificado['correo'] ?></p>

<p><strong>Teléfono:</strong> <?= $certificado['telefono'] ?></p>

<p><strong>Gerencia:</strong> <?= $certificado['gerencia_laboral'] ?></p>

<hr>

<h5>Datos del Certificado</h5>

<p><strong>Código Patrimonial:</strong> <?= $certificado['codigo_reloj'] ?></p>

<p><strong>Tipo certificado:</strong> <?= $certificado['tipo_certificado'] ?></p>

<p><strong>Duración:</strong> <?= $certificado['duracion_anios'] ?> años</p>

<p><strong>Estado:</strong> <?= $certificado['estado'] ?></p>

<p><strong>Fecha emisión:</strong> <?= $certificado['fecha_emision']->format('Y-m-d') ?></p>

<p><strong>Fecha vencimiento:</strong> <?= $certificado['fecha_vencimiento']->format('Y-m-d') ?></p>

<p><strong>Fecha registro:</strong> <?= $certificado['fecha_creacion']->format('Y-m-d') ?></p>

<hr>

<h5>Archivo Evidencia</h5>
<?php if(!empty($certificado['evidencia'])){ ?>

<a href="modules/uploads/certificados/<?php echo $certificado['evidencia']; ?>" target="_blank">

<img src="modules/uploads/certificados/<?php echo $certificado['evidencia']; ?>" 
style="max-width:350px;border:1px solid #ccc;padding:5px">

</a>

<?php } ?>
<hr>

<h5>Backups del Certificado</h5>

<table class="table table-bordered">

<tr>
<th>ID</th>
<th>Identificador</th>
<th>Archivo</th>
<th>Fecha Backup</th>
</tr>

<?php foreach($backups as $b){ ?>

<tr>

<td><?= $b['id_backup'] ?></td>

<td><?= $b['identificador'] ?></td>

<td>

<a href="uploads/backups/<?= $b['ruta_archivo'] ?>" target="_blank">
<?= $b['ruta_archivo'] ?>
</a>

</td>

<td><?= $b['fecha_backup']->format('Y-m-d H:i') ?></td>

</tr>

<?php } ?>

</table>

</div>

</div>