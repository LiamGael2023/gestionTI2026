<style>
    .disabled-group {
        pointer-events: none;
        opacity: 0.4;
    }
</style>
<!-- Bootstrap bundle (obligatorio) -->

<script src="/gestionTI/modules/papeletas/views/papeleta.js"></script>
<script src="/gestionTI/modules/papeletas/views/colaborador.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fslightbox/index.js"></script>
<div class="page-wrapper">
    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">



            <!-- Tabler JS (OBLIGATORIO para modal-blur) -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">

                        <li class="nav-item">
                            <a href="#tabs-usuario" class="nav-link active" data-bs-toggle="tab">
                                Registro de Papeletas
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#tabs-pendientes" class="nav-link" data-bs-toggle="tab">
                                Papeletas Pendientes
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#tabs-colaboradores" class="nav-link" data-bs-toggle="tab">
                                Colaboradores
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#tabs-firmas" class="nav-link" data-bs-toggle="tab">
                                Firmas
                            </a>
                        </li>

                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <div class="tab-pane active show" id="tabs-usuario">
                            <?php include "papeletas-usuario.php"; ?>
                        </div>

                        <div class="tab-pane" id="tabs-pendientes">
                            <?php include "papeletas-admin.php"; ?>
                        </div>


                        <div class="tab-pane" id="tabs-colaboradores">
                            <?php include "colaboradores.php"; ?>
                        </div>

                        <div class="tab-pane" id="tabs-firmas">
                            <?php include "firmas.php"; ?>
                        </div>

                    </div>
                </div>
            </div>





        </div>
    </div>
    <!-- END PAGE BODY -->
</div>


<!-- Modal de registro de Papeleta -->


<?php require_once __DIR__ . "/../controllers/PapeletasController.php"; ?>
<?php require_once __DIR__ . "/../../transportes/controllers/PapeletaVehicularController.php";

?>
<?php
// Usa __DIR__ para que PHP sepa exactamente dónde empezar a buscar
// Retrocedemos dos niveles para salir de 'views' y 'papeletas' 
// y entrar a la carpeta general de fragments/modals
include __DIR__ . '/../../../fragments/modals/papeletas/papeleta-qr.php';
include __DIR__ . '/../../../fragments/modals/contenedor-pdf.php';
include __DIR__ . '/../../../fragments/modals/papeletas/registro-evidencias.php';
include __DIR__ . '/../../../fragments/modals/papeletas/registro-bitacora-vehicular.php';
include __DIR__ . '/../../../fragments/modals/papeletas/registro-papeleta.php';
include __DIR__ . '/../../../fragments/modals/papeletas/cambiar-jefe-inmediato.php';

?>