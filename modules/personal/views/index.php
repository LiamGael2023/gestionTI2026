<div class="page-wrapper">
    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <!-- Tabler JS (OBLIGATORIO para modal-blur) -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">

                        <li class="nav-item">
                            <a href="#tabs-papeletas" class="nav-link active" data-bs-toggle="tab">
                                Papeletas por Trabajador
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#tabs-boletas" class="nav-link" data-bs-toggle="tab">
                                Boletas por Trabajador
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#tabs-uper" class="nav-link" data-bs-toggle="tab">
                               Migracion
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <div class="tab-pane active show" id="tabs-papeletas">
                            <?php include "papeletas.php"; ?>
                        </div>

                        <div class="tab-pane" id="tabs-boletas">
                            <?php include "boletas.php"; ?>
                        </div>


                        <div class="tab-pane" id="tabs-uper">
                            <?php include "uper.php"; ?>
                        </div>

                        
                    </div>
                </div>
            </div>





        </div>
    </div>

</div>