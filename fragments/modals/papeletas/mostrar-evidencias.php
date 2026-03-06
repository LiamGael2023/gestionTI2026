  <div class="modal modal-blur fade" id="modal-evidencias" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">

        <!-- Spinner de carga (invisible por defecto) -->
        <!-- Spinner de carga (invisible por defecto) -->



        <form id="formEvidencias"  enctype="multipart/form-data">


          <div class="modal-header">
            <h5 class="modal-title">Evidencias</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div id="spinnerEvidencias" class="text-center my-3" style="display:none;">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
              <p>Cargando evidencias...</p>
            </div>
            <!-- input oculto para saber a qué papeleta pertenece -->
            <input id="id_papeleta_modal" name="id_papeleta_modal" hidden>

            <div class="row" id="galeria_evidencias">
              <!-- Aquí se insertarán las imágenes cargadas -->
            </div>



          </div>
          <!-- Script para previsaualizacion -->




          <div class="modal-footer">
            <a href="#" class="btn btn-link link-danger" data-bs-dismiss="modal">Cerrar</a>
          </div>

        </form>
      </div>
    </div>
  </div>
<script>
     // Función para cargar evidencias en la galería
    $(document).ready(function() {

        // Función para cargar evidencias de una papeleta
        function cargarEvidencias(idPapeleta) {
            const gallery = $("#galeria_evidencias");
            gallery.empty();
            $("#spinnerEvidencias").show();

            $.ajax({
                url: "modules/papeletas/ajax/papeleta.ajax.php",
                type: "POST",
                data: {
                    accion: "mostrar",
                    id_papeleta: idPapeleta
                },
                dataType: "json",
                success: function(response) {
                    gallery.empty();

                    if (response.status === "success" && response.data.length > 0) {
                        response.data.forEach((img, index) => {
                            // Convertir base64 a Blob y Object URL
                            console.log("Cargando imagen:", img.id, img.nombre


                            );
                            const byteString = atob(img.base64);
                            const ab = new ArrayBuffer(byteString.length);
                            const ia = new Uint8Array(ab);
                            for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
                            const blob = new Blob([ab], {
                                type: img.tipo
                            });
                            const url = URL.createObjectURL(blob);

                            let col = $(`
                        <div class="col-6 col-md-6 mb-2 position-relative">
                            <a href="${url}" data-fslightbox="gallery-${idPapeleta}" data-caption="${img.nombre}">
                                <img src="${url}" class="img-fluid rounded border w-100" 
                                     style="max-height:200px; object-fit:cover;" title="${img.nombre}" />
                            </a>
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 btn-eliminar" 
                                    data-id="${img.id}" style="opacity:0.7;">
                                &times;
                            </button>
                        </div>
                    `);

                            // Botón eliminar
                            col.find(".btn-eliminar").on("click", function(e) {
                                e.stopPropagation();
                                const idImagen = $(this).data("id");

                                Swal.fire({
                                    title: '¿Eliminar esta imagen?',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, eliminar',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {

                                        $.post("ajax/ajax/papeleta.ajax.php", {
                                            accion: "eliminar_evidencia",
                                            id_evidencia: idImagen
                                        }, function(res) {
                                            if (res.status === "success") {
                                                Swal.fire('Eliminado', res.message, 'success');
                                                cargarEvidencias(idPapeleta);
                                            } else {
                                                Swal.fire('Error', res.message, 'error');
                                            }
                                        }, "json");
                                    }
                                });
                            });


                            gallery.append(col);
                        });

                        // Inicializar FSLightbox después de agregar las imágenes
                        if (window.refreshFsLightbox) window.refreshFsLightbox();
                    } else {
                        gallery.html("<p class='text-muted'>No hay evidencias.</p>");
                    }
                },
                error: function() {
                    gallery.html("<p class='text-danger'>Error al cargar evidencias.</p>");
                },
                complete: function() {
                    $("#spinnerEvidencias").hide();
                }
            });
        }


        // Abrir modal de evidencias y cargar imágenes
        $('#modal-evidencias').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var idPapeleta = button.data('id');
            $("#id_papeleta_modal").val(idPapeleta);
            cargarEvidencias(idPapeleta);

            // Limpiar previsualización y input de archivos
            $("#previewEvidencias").empty();
            $("#evidencia").val('');
        });

        // Previsualización de imágenes
        $('#evidencia').off('change').on('change', function(event) {
            const files = event.target.files;
            const preview = $("#previewEvidencias");
            preview.empty();

            Array.from(files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = $(`
                <div class="col-4 col-md-3 mb-2">
                    <img src="${e.target.result}" class="img-fluid rounded border" 
                         style="max-height:100px; object-fit:cover;" alt="preview">
                </div>
            `);
                    preview.append(col);
                };
                reader.readAsDataURL(file);
            });
        });


        $('#modal-evidencias').on('hidden.bs.modal', function() {
            $("#galeria_evidencias").empty();
            $("#previewEvidencias").empty();
            $("#evidencia").val('');
        });

    });
</script>