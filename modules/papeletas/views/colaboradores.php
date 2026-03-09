<style>
  /* Contenedor tabla que ocupa resto del espacio */
  .table-column {
    flex: 1 1 auto;
    overflow-x: auto;
  }

  .table-responsive {
    overflow-x: auto;
    /* Permite scroll horizontal si la tabla es más ancha */
  }

  .table.dataTable {
    width: 100% !important;
    /* Asegura que DataTables use todo el ancho disponible */
    table-layout: auto !important;
    /* Permite ajustar el ancho dinámico */
    white-space: nowrap;
    /* Evita que las celdas se rompan */
  }

  .table.dataTable th,
  .table.dataTable td {
    text-align: left;
    vertical-align: middle;
    padding: 10px 6px;
  }
</style>

<div class="page-wrapper">
  <div class="page-body">
    <div class="container-xl">
      <div class="row row-cards">
        <div class="col-12">
          <div class="card">
            <div class="card-table">
              <div class="card-header">
                <div class="row w-full">
                  <div class="col-12 d-flex justify-content-between align-items-center">

                    <h3 class="card-title mb-0">
                      Colaboradores de <?php echo mb_strtoupper(($_SESSION["Oficina"]), 'UTF-8'); ?>


                    </h3>

                    <!-- Datepicker -->
                    <div class="d-flex align-items-center">
                      <span class="form-label me-2">
                        Fecha de búsqueda: <font color="red">(*)</font>
                      </span>

                      <input class="form-control w-auto"
                        id="fechaBusquedaColaborador"
                        placeholder="DD/MM/YYYY"
                        style="min-width: 160px;">
                    </div>

                  </div>
                </div>
              </div>

              <div class="table-responsive">
                <table class="display table table-striped table-hover dt-responsive nowrap tablaColaborador" style="width: 100%">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Imagen</th>
                      <th>Trabajador</th>
                      <th>Gerencia/Oficina</th>
                      <th>Unidad/División</th>
                      <th>Papeleta del Día</th>
                    </tr>
                  </thead>
                </table>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

          <!-- Modal para mostrar el código QR -->
        <?php include __DIR__ . '/../fragments/modals/papeleta-qr.php'; ?>
        <!-- Modal para mostrar el PDF -->
        <?php include __DIR__ . '/../fragments/modals/contenedor-pdf.php'; ?>

<script>
   // Después de agregar los avatares al DOM
    if (window.refreshFsLightbox) {
      window.refreshFsLightbox();
    }

    $(document).on('click', '.avatar-lightbox', function(e) {
    e.preventDefault(); // evita redirección
    const src = $(this).attr('href'); // URL de la imagen
    const caption = $(this).data('caption') || '';

    // Crear un lightbox temporal
    const tempFsLightbox = document.createElement('a');
    tempFsLightbox.href = src;
    tempFsLightbox.setAttribute('data-fslightbox', 'single');
    tempFsLightbox.setAttribute('data-caption', caption);
    document.body.appendChild(tempFsLightbox);

    // Abrir lightbox
    if (window.refreshFsLightbox) window.refreshFsLightbox();
    tempFsLightbox.click();

    // Limpiar elemento temporal
    setTimeout(() => document.body.removeChild(tempFsLightbox), 100);
});
</script>