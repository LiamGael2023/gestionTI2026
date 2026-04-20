<!-- NOMBRE EN CARD -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
        <h6 class="mb-0">
            <?= $persona['nombres']." ".$persona['apellidos'] ?>
        </h6>
    </div>
</div>

<?php if(empty($certificados)){ ?>

<div class="alert alert-black border">
    No tiene certificados registrados
</div>

<?php } else { ?>

<div class="row g-2">

<?php foreach($certificados as $c){ ?>

    <div class="col-12">

        <div class="card border-0 shadow-sm">

            <div class="card-body py-2">

                <div class="row align-items-center">

                    <!-- ID -->
                    <div class="col-md-1 text-muted small">
                        #<?= $c['id_certificado'] ?>
                    </div>

                    <!-- TIPO -->
                    <div class="col-md-2">
                        <span class="text-dark">
                            <?= $c['tipo_certificado'] ?>
                        </span>
                    </div>

                    <!-- FECHA EMISIÓN -->
                    <div class="col-md-2 text-muted small">
                        <?= $c['fecha_emision']->format('d-m-Y') ?>
                    </div>

                    <!-- VENCIMIENTO -->
                    <div class="col-md-2 text-muted small">
                        <?= $c['fecha_vencimiento']->format('d-m-Y') ?>
                    </div>

                    <!-- ESTADO -->
                    <div class="col-md-2">
                        <span class="text-muted">
                            <?= $c['estado'] ?>
                        </span>
                    </div>

                    <!-- EVIDENCIA -->
<div class="col-md-3 text-end">

    <?php if(!empty($c['evidencia'])): ?>

        <?php 
            $file = $c['evidencia'];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $url = "modules/uploads/certificados/".$file;
        ?>

        <?php if(in_array($ext, ['jpg','jpeg','png','webp'])): ?>

            <a href="<?= $url ?>" target="_blank">
                <img src="<?= $url ?>" 
                     alt="evidencia"
                     class="img-thumbnail"
                     style="max-height: 50px;">
            </a>

        <?php elseif($ext === 'pdf'): ?>

            <a href="<?= $url ?>" 
               target="_blank"
               class="btn btn-sm btn-outline-danger">
                📄 Ver PDF
            </a>

        <?php else: ?>

            <a href="<?= $url ?>" 
               target="_blank"
               class="btn btn-sm btn-outline-secondary">
                Ver evidencia
            </a>

        <?php endif; ?>

    <?php else: ?>

        <span class="text-muted small">Sin evidencia</span>

    <?php endif; ?>

</div>

                </div>

            </div>

        </div>

    </div>

<?php } ?>

</div>

<?php } ?>