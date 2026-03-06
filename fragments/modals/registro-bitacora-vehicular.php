<div class="modal fade" id="modal-bitacora" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-3 modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <h3 class="card-title">Registrar bitacora</h3>
                <p class="card-subtitle">A continuación detalle la bitacorra correspondiente a la salida vehicular.</p>
                <form action="">
                    <div class="mb-3">
                        <label class="form-label">Bitacora Vehicular <span class="form-label-description">350</span></label>
                        <textarea class="form-control" name="descripcion_bitacora" id="descripcion_bitacora" rows="6" placeholder="Detalle a continuacion la bitacora de la salida vehicular..."></textarea>
                        <input name="id_papeleta_vehicular_bitacora" id="id_papeleta_vehicular_bitacora" value="">
                    </div>

                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary ms-auto btn-registrar-bitacora">
                    Registrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    //Captura el id_papeleta_vehicular al abrir el modal
    $(document).on("click", ".btn-bitacora", function() {
        var id = $(this).data("id");
        $("#id_papeleta_vehicular_bitacora").val(id);
    });




    //Boton modal registrar bitacora
    $(document).on("click", ".btn-registrar-bitacora", function() {
        var id = $("#id_papeleta_vehicular_bitacora").val();
        var descripcion = $("#descripcion_bitacora").val();

        if (!descripcion.trim()) {
            Swal.fire('Advertencia', 'Debe ingresar una descripción.', 'warning');
            return;
        }

        $.ajax({
            url: "ajax/ajax/papeleta-vehicular.ajax.php",
            type: "POST",
            dataType: "json",
            data: {
                accion: "registrarBitacora",
                id_papeleta_vehicular: id,
                descripcion_bitacora: descripcion
            },
            success: function(response) {
                console.log("✅ SUCCESS:", response);
                if (response.status === "success") {
                    Swal.fire('Registrado', 'La bitácora ha sido registrada.', 'success');
                    $("#modal-bitacora").modal('hide');
                    $("#descripcion_bitacora").val("");
                } else {
                    Swal.fire('Error', 'No se pudo registrar la bitácora.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("❌ Error AJAX:", error);
                Swal.fire('Error', 'Ocurrió un error al registrar.', 'error');
            }
        });
    });
</script>