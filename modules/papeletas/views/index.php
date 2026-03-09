<style>
    .disabled-group {
        pointer-events: none;
        opacity: 0.4;
    }
</style>
<!-- Bootstrap bundle (obligatorio) -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<style>
    @media (min-width: 1000px) {

        .tablaRegistroPapeleta {
            width: 100% !important;
            table-layout: fixed !important;
            /* obliga a respetar porcentajes */
        }

        .col-id {
            width: 1% !important;
        }

        .col-qr {
            width: 3% !important;
        }

        .col-firmas {
            width: 14% !important;
        }

        .col-concepto {
            width: 23% !important;
        }

        .col-fecha {
            width: 10% !important;
        }

        .col-hora {
            width: 8% !important;
        }

        .col-lugar {
            width: 9% !important;
        }

        .col-retorno {
            width: 4% !important;
        }


        .col-jefe {
            width: 12% !important;
        }


        .col-acciones {
            width: 16% !important;
        }


        /* Garantiza que las imágenes, SVG o avatares no rompan el ancho */
        .tablaRegistroPapeleta td img,
        .tablaRegistroPapeleta td svg,
        .tablaRegistroPapeleta td .avatar-lightbox {
            max-width: 100%;
            height: auto;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        td {
            max-width: 180px;
            /* Ajusta según tu diseño */
            white-space: nowrap;
            /* No permite salto de línea */
            overflow: hidden;
            /* Oculta lo que se desborda */
            text-overflow: ellipsis;
            /* Muestra los ... */
        }

        .tablaRegistroPapeleta td:nth-child(3),
        .tablaRegistroPapeleta th:nth-child(3) {
            text-align: center !important;
            vertical-align: middle !important;

        }

        .tablaRegistroPapeleta td:nth-child(2),
        .tablaRegistroPapeleta td:nth-child(3),
        .tablaRegistroPapeleta td:nth-child(8),
        .tablaRegistroPapeleta td:nth-child(10) {
            text-overflow: clip !important;
            /* No muestra los “…” */
        }



    }
</style>
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


<?php require_once __DIR__ . "/../controllers/PapeletasController.php"; ?>
<?php require_once __DIR__ . "/../../transportes/controllers/PapeletaVehicularController.php";

?>