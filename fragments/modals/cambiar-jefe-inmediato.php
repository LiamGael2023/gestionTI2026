  <div class="modal fade" id="modal-cambiar_jefe" aria-labelledby="modal-cambiar_jefeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-cambiar_jefeLabel">Asignación de Incidencia</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <form id="cambiarJefeForm" method="post">
          <div class="modal-body">

            <input type="hidden" id="id_papeleta_cambio" name="id_papeleta_cambio">

            <input type="hidden" id="id_trabajador_admin" name="id_trabajador_admin"
              value="<?php echo $_SESSION['id_Trabajador']; ?>">

            <div class="mb-3">
              <label class="form-label">Jefe Inmediato actual:</label>
              <input type="text" class="form-control" id="jefe_inmediato" name="jefe_inmediato" disabled>
            </div>

            <div class="mb-3" id="cambiar_jefe">
              <label class="form-label">Cambiar a: <font color="red">(*)</font></label>
              <select class="form-select" id="jefe_papeleta" name="jefe_papeleta" style="width:100%;">
                <option value="">Seleccione una opción...</option>
              </select>
            </div>

          </div>

          <div class="modal-footer">
            <a href="#" class="btn btn-link link-danger" data-bs-dismiss="modal">Cancelar</a>
            <button type="submit" class="btn btn-success">Cambiar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    $('#modal-cambiar_jefe').on('show.bs.modal', function(event) {
      console.log("🟢 Modal de cambiar jefe abierto");

      const button = $(event.relatedTarget);
      const modal = $(this);

      // ✅ Capturar correctamente el ID de la papeleta
      const idPapeleta = button.data('id');
      if (!idPapeleta) return;
      const JefeInmediato = button.data('jefeinmediato');

      modal.find('#id_papeleta_cambio').val(idPapeleta);
      modal.find('#jefe_inmediato').val(JefeInmediato);

      // ✅ Limpiar y preparar el select
      const $select = $('#jefe_papeleta');
      $select.empty().append('<option value="">Seleccione un nuevo personal</option>');

      // ✅ Inicializar Select2
      $select.select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modal-cambiar_jefe'),
        placeholder: 'Seleccione un personal...',
        ajax: {
          url: 'ajax/ajax/papeleta.ajax.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              action: 'getJefes',
              q: params.term
            };
          },
          processResults: function(data) {
            return {
              results: data.map(function(item) {
                return {
                  id: item.id,
                  text: item.text, // Nombre
                  oficina: item.oficina, // Oficina o gerencia
                  foto: item.foto // Fotocheck
                };
              })
            };
          },
          cache: true
        },
        templateResult: function(item) {
          if (!item.id) return item.text;

          const sinFoto = '../personal/vistas/static/avatars/sinfoto.jpg';
          const fotoUrl = item.foto ?
            '../personal/fotosIndividuales/' + item.foto :
            sinFoto;

          return $(
            '<div class="d-flex align-items-center">' +
            '<img src="' + fotoUrl + '" onerror="this.src=\'' + sinFoto + '\'" class="rounded me-3" style="width:48px;height:48px;object-fit:cover;" />' +
            '<div>' +
            '<div class="fw-bold small text-muted">' + (item.oficina || '') + '</div>' +
            '<div>' + item.text + '</div>' +
            '</div>' +
            '</div>'
          );
        },
        templateSelection: function(item) {
          if (!item.id) return item.text;
          return item.text + (item.oficina ? ' - ' + item.oficina : '');
        },
        escapeMarkup: function(markup) {
          return markup;
        }
      });
    });

    $("#cambiarJefeForm").on("submit", function(e) {
      e.preventDefault();

      const id_papeleta = $("#id_papeleta_cambio").val();
      const cod_jefe = $("#jefe_papeleta").val();

      $.ajax({
        url: "ajax/ajax/papeleta.ajax.php",
        type: "POST",
        data: {
          accion: "actualizar_jefe",
          id_papeleta: id_papeleta,
          jefe_papeleta: cod_jefe
        },
        dataType: "json",
        success: function(response) {
          if (response.status === "success") {
            Swal.fire("Éxito", response.message, "success");
            $("#modal-cambiar_jefe").modal("hide");
            $(".tablaAdminPapeleta").DataTable().ajax.reload(null, false);
          } else {
            Swal.fire("Error", response.message, "error");
          }
        },
        error: function(err) {
          Swal.fire("Error", "No se pudo conectar al servidor", "error");
        }
      });
    });
  </script>