<div class="page-wrapper">
  <!-- BEGIN PAGE HEADER -->

  <!-- END PAGE HEADER -->
  <!-- BEGIN PAGE BODY -->
  <div class="page-body">
    <div class="container-xl">
      <div class="row row-cards">

        <div class="col-12">

          <div class="card">
            <div class="card-table">
              <div class="card-header">
                <div class="row w-full">
                  <div class="col">
                    <h3 class="card-title mb-0">Firma de <?php echo $_SESSION["Trab_Paterno"] . " " . $_SESSION["Trab_Materno"] . " " . $_SESSION["Trab_Nombres"]; ?></h3>
                  </div>
                </div>
              </div>
              <div class="page-body">
                <div class="container-xl">
                  <div class="row row-cards">
                    <div class="col-md-6 col-lg-3">
                      <div class="card">
                        <div class="card-body">
                          <h3 class="card-title">Firma Trabajador</h3>

                        </div>
                        <!-- Photo -->
                        <?php
                        if ($_SESSION['FirmaPersonal'] == "") {
                          echo '<div class="img-responsive img-responsive card-img-bottom" style="background-image: url(../personal/repositorio/perfil/sinfirma.jpg"></div>';
                        } else {
                          echo '<div class="img-responsive img-responsive card-img-bottom" style="background-image: url(../personal/repositorio/perfil/' . $_SESSION['FirmaPersonal'] . '"></div>';
                        }
                        ?>

                      </div>
                    </div>
                    <?php
                    if ($_SESSION["JefeArea"] == 0) {
                      echo '';
                    } else {
                      echo '
                                     <div class="col-md-6 col-lg-3">
                <div class="card">
                  <div class="card-body">
                    <h3 class="card-title">Firma Jefe de Oficina</h3>
                    
                  </div>
                                                            
                                ';

                      if ($_SESSION['FirmaJefe'] == "") {
                        echo '<div class="img-responsive img-responsive card-img-bottom" style="background-image: url(../personal/repositorio/perfil/sinfirma.jpg"></div>';
                      } else {
                        echo '<div class="img-responsive img-responsive card-img-bottom" style="background-image: url(../personal/repositorio/perfil/' . $_SESSION['FirmaJefe'] . '"></div>';
                      }

                      echo '   
                                                    </div>
                                                </div>';
                    }
                    ?>

                    <?php
                    if ($_SESSION["esJefeSede"] == 0) {
                      echo '';
                    } else {
                      echo '
                                     <div class="col-md-6 col-lg-3">
                <div class="card">
                  <div class="card-body">
                    <h3 class="card-title">Firma Jefe de Sede</h3>
                    
                  </div>
                                                            
                                ';

                      if ($_SESSION['FirmaJefeSede'] == "") {
                        echo '<div class="img-responsive img-responsive card-img-bottom" style="background-image: url(../personal/repositorio/perfil/sinfirma.jpg"></div>';
                      } else {
                        echo '<div class="img-responsive img-responsive card-img-bottom" style="background-image: url(../personal/repositorio/perfil/' . $_SESSION['FirmaJefeSede'] . '"></div>';
                      }

                      echo '   
                                                    </div>
                                                </div>';
                    }
                    ?>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- END PAGE BODY -->
</div>  