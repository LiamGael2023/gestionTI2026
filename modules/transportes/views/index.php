<script src="modules/transportes/views/vehiculo.js"></script>
<script src="modules/transportes/views/conductor.js"></script>

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
                            <a href="#tabs-vehiculos" class="nav-link active" data-bs-toggle="tab">
                                Vehiculos
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#tabs-conductores" class="nav-link" data-bs-toggle="tab">
                                Conductores
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#tabs-papeletas-vehiculares" class="nav-link" data-bs-toggle="tab">
                                Papeletas Vehiculares
                            </a>
                        </li>



                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <div class="tab-pane active show" id="tabs-vehiculos">
                            <?php include "vehiculos.php"; ?>
                        </div>

                        <div class="tab-pane" id="tabs-conductores">
                            <?php include "conductores.php"; ?>
                        </div>


                        <div class="tab-pane" id="tabs-papeletas-vehiculares">
                            <?php include "papeletas-vehiculares.php"; ?>
                        </div>

                        <div class="tab-pane" id="tabs-firmas">
                            <?php include "firmas.php"; ?>
                        </div>

                    </div>
                </div>
            </div>





        </div>
    </div>

</div>
    <?php require_once __DIR__ . "/../controllers/VehiculoController.php"; ?>


    <?php
    include __DIR__ . '/../../../fragments/transportes/registro-vehiculo.php';
    include __DIR__ . '/../../../fragments/modals/contenedor-pdf.php';

    ?>
    
    <?php
    include __DIR__ . '/../../../fragments/transportes/asignacion-vehicular.php';
    ?>