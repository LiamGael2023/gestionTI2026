<!-- desasignar -->
<div class="modal fade" id="modal-desasignar" tabindex="-1" aria-labelledby="modal-desasignarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-desasignarLabel">Desasignar Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="desasignarVehiculoForm" role="form" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="placa_desasignar" class="form-label">Placa</label>
                            <input type="text" class="form-control" id="placa_desasignar" name="placa_desasignar" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="marca_desasignar" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="marca_desasignar" name="marca_desasignar" readonly>
                        </div>
                    </div>
                    <input type="hidden" id="id_desasignar" name="id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">❌ Desasignar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- estado asignado -->
<div class="modal fade" id="modal-estado_asignado" tabindex="-1" aria-labelledby="modal-estado_asignadoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-estado_asignadoLabel">Asignación de conductor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="estadoVehiculoForm" role="form" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label for="placa_estado" class="form-label">Placa</label>
                            <input type="text" class="form-control" id="placa_estado" name="placa_estado" disabled>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="marca_estado" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="marca_estado" name="marca_estado" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Buscar Conductor <font color="red">(*)</font></label>
                            <select class="form-select" id="conductor_estado" name="conductor_estado" style="width:100%;">
                                <option value="">Seleccione un conductor</option>
                            </select>
                        </div>
                        <input type="hidden" name="id" id="id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-link link-danger" data-bs-dismiss="modal">Cancelar</a>
                        <button type="submit" class="btn btn-success">Asignar Vehículo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
    $('#modal-estado_asignado').on('show.bs.modal', function(event) {
        // Cierra otros modales abiertos
        $('.modal.show').each(function() {
            if ($(this).attr('id') !== $(event.target).attr('id')) {
                bootstrap.Modal.getInstance(this)?.hide();
            }
        });

        var button = $(event.relatedTarget);
        var id = button.data('id') || '0';
        var placa = button.data('placa');
        var marca = button.data('marca');

        var modal = $(this);
        modal.find('#placa_estado').val(placa);
        modal.find('#marca_estado').val(marca);
        modal.find('#id').val(id);

        // Llenar conductores
        $.ajax({
            url: 'ajax/ajax/vehiculo.ajax.php',
            data: {
                accion: 'getConductores'
            },
            dataType: 'json',
            success: function(data) {
                console.log('Datos recibidos para conductores:', data);

                const $select = $('#conductor_estado');

                // Destruye solo si ya existe Select2
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy').empty();
                }

                $('#conductor_estado').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Buscar conductor disponible...',
                    allowClear: true,
                    dropdownParent: $('#modal-estado_asignado'), // ✅ punto clave

                    data: data, // tus datos locales (si ya los tienes)

                    templateResult: function(item) {
                        if (!item.id) return item.text;
                        var fotoUrl = '../personal/fotosIndividuales/' + (item.foto || 'default.jpg');
                        return $(
                            '<div class="d-flex align-items-center py-1">' +
                            '<span class="avatar me-2" style="width:36px; height:36px; border-radius:50%; background-image:url(' + fotoUrl + '); background-size:cover; background-position:center;"></span>' +
                            '<div><strong>' + item.text + '</strong><br>' +
                            (item.gerencia ? '<small class="text-muted">' + item.gerencia + '</small>' : '') +
                            '</div>' +
                            '</div>'
                        );
                    },

                    templateSelection: function(item) {
                        if (!item.id) return item.text;
                        var fotoUrl = '../personal/fotosIndividuales/' + (item.foto || 'default.jpg');
                        return $(
                            '<div class="d-flex align-items-center">' +
                            '<span class="avatar me-2" ' +
                            'style="width:28px; height:28px; border-radius:50%; background-image:url(' + fotoUrl + '); background-size:cover; background-position:center;">' +
                            '</span>' +
                            '<span class="text-truncate">' + item.text + '</span>' +
                            '</div>'
                        );
                    },


                    escapeMarkup: function(markup) {
                        return markup;
                    }
                });


            }
        });

    });




     $('#estadoVehiculoForm').on('submit', function(e) {
      e.preventDefault();
      var placa = $('#placa_estado').val();
      var idConductor = $('#conductor_estado').val();
      if (!idConductor) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe seleccionar un conductor'
        });
        return;
      }

      $.ajax({
        type: 'POST',
        url: 'ajax/ajax/vehiculo.ajax.php',
        data: {
          placa,
          idConductor,
          accion: 'asignarConductor'
        },
        dataType: 'json',
        success: function(response) {
          if (response.status === 'success') {
            $('#modal-estado_asignado').modal('hide');
            $('#conductor_estado').val(null).trigger('change');
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response.message
              })
              .then(() => $('.tablaRegistroVehiculo').DataTable().ajax.reload(null, false));
          } else {
            Swal.fire({
              icon: 'error',
              title: '¡Error!',
              text: response.message
            });
          }
        },
        error: function() {
          Swal.fire({
            icon: 'error',
            title: '¡Error!',
            text: 'Hubo un error al asignar el conductor.'
          });
        }
      });
    });

    // Modal Desasignar
    $('#modal-desasignar').on('show.bs.modal', function(event) {
      // Cierra otros modales abiertos
      $('.modal.show').each(function() {
        if ($(this).attr('id') !== $(event.target).attr('id')) {
          bootstrap.Modal.getInstance(this)?.hide();
        }
      });

      var button = $(event.relatedTarget);
      var id = button.data('id') || '0';
      var placa = button.data('placa');
      var marca = button.data('marca');

      var modal = $(this);
      modal.find('#placa_desasignar').val(placa);
      modal.find('#marca_desasignar').val(marca);
      modal.find('#id_desasignar').val(id);
    });

    $('#desasignarVehiculoForm').on('submit', function(e) {
      e.preventDefault();
      var placa = $('#placa_desasignar').val();
      if (!placa) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'No se encontró la placa del vehículo'
        });
        return;
      }

      $.ajax({
        type: 'POST',
        url: 'ajax/ajax/vehiculo.ajax.php',
        data: {
          placa,
          accion: 'desasignarVehiculo'
        },
        dataType: 'json',
        success: function(response) {
          if (response.status === 'success') {
            $('#modal-desasignar').modal('hide');
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response.message
              })
              .then(() => $('.tablaRegistroVehiculo').DataTable().ajax.reload(null, false));
          } else {
            Swal.fire({
              icon: 'error',
              title: '¡Error!',
              text: response.message
            });
          }
        },
        error: function() {
          Swal.fire({
            icon: 'error',
            title: '¡Error!',
            text: 'Hubo un error al desasignar el vehículo.'
          });
        }
      });
    });
});
</script>