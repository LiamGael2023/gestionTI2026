var tabla = null;
var tablaAdmin = null;

$(document).ready(function () {

  var idioma_espanol = {
    sProcessing: "Procesando...",
    sLengthMenu: "Mostrar _MENU_ registros",
    sZeroRecords: "No se encontraron resultados",
    sEmptyTable: "Ningún dato disponible en esta tabla",
    sInfo:
      "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
    sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
    sSearch: "Buscar:",
    sLoadingRecords: "Cargando...",
    oPaginate: {
      sFirst: "Primero",
      sLast: "Último",
      sNext: "Siguiente",
      sPrevious: "Anterior",
    },
    oAria: {
      sSortAscending: ": Activar para ordenar ascendente",
      sSortDescending: ": Activar para ordenar descendente",
    },
  };

  /* =========================================
  TABLA REGISTRO PAPELETA
  ========================================= */

  if ($(".tablaRegistroPapeleta").length) {

    tabla = $(".tablaRegistroPapeleta").DataTable({

      serverSide: true,
      processing: true,
      language: idioma_espanol,

      ajax: {
        url: "modules/papeletas/ajax/datatable-RegistroPapeleta.ajax.php",
        type: "POST",
      },

      columnDefs: [
        {
          targets: 2,
          className: "dt-center",
        },
      ],

      pageLength: 10,
      lengthMenu: [10, 20, 30, 40],
      deferRender: true,
      searchDelay: 1000,

      initComplete: function () {

        var contenedor = $(".tablaRegistroPapeleta").closest(".dataTables_wrapper");

        contenedor.find(".dataTables_filter")
          .addClass("d-flex align-items-center")
          .css({
            "justify-content": "flex-end",
            gap: "0",
          });

        const labelFiltro = contenedor.find(".dataTables_filter label");

        labelFiltro.css({
          display: "flex",
          "align-items": "center",
          gap: "0",
          margin: 0,
        });

        window.botonRegistrar = $(`
          <a type="button"
            style="background-color:#285289; border-color:#285289;"
            class="btn btn-primary px-5 mx-2 d-flex align-items-center gap-2"
            data-bs-toggle="modal"
            data-bs-target="#modal-report">
            REGISTRAR
          </a>
        `);

        labelFiltro.append(window.botonRegistrar);

        labelFiltro.find("input").css({
          flex: "0 0 200px",
          margin: 0,
        });

        checkPapeletaPendiente();
      },

      drawCallback: function () {

        checkPapeletaPendiente();

        if (window.lazySizes) {
          lazySizes.loader.checkElems();
        }

      }

    });

    /* =========================================
    VERIFICAR PAPELETA PENDIENTE
    ========================================= */

    function checkPapeletaPendiente() {

      $.ajax({

        url: "modules/papeletas/ajax/papeleta.ajax.php",
        type: "POST",
        dataType: "json",

        data: {
          accion: "tiene_papeleta_pendiente",
          id_trabajador: 206,
        },

        success: function (response) {

          if (response.status === "success" && response.tienePendiente === 1) {

            window.botonRegistrar
              .addClass("disabled")
              .attr("aria-disabled", "true");

          } else {

            window.botonRegistrar
              .removeClass("disabled")
              .removeAttr("aria-disabled");

          }

        },

        error: function () {

          console.error("Error al consultar papeletas pendientes");

        }

      });

    }

  }

  /* =========================================
  TABLA ADMIN PAPELETA
  ========================================= */

  if ($(".tablaAdminPapeleta").length) {

    let filtroFecha = null;
    let filtroFirma = null;

    $(".btn-filtro-fecha").on("click", function () {

      $(".btn-filtro-fecha").removeClass("active");
      $(this).addClass("active");

      filtroFecha = $(this).data("filtro");

      tablaAdmin.ajax.reload();

      setTimeout(() => {
        $("#collapseFecha").collapse("hide");
      }, 1000);

    });

    $(".btn-filtro-firma").on("click", function () {

      $(".btn-filtro-firma").removeClass("active");
      $(this).addClass("active");

      filtroFirma = $(this).data("filtro");

      tablaAdmin.ajax.reload();

      setTimeout(() => {
        $("#collapseFirma").collapse("hide");
      }, 1000);

    });

    $("#btn-restablecer-filtros2").on("click", function () {

      filtroFecha = null;
      filtroFirma = null;

      tablaAdmin.ajax.reload();

      $(".btn-filtro-fecha").removeClass("active");
      $(".btn-filtro-firma").removeClass("active");

    });

    tablaAdmin = $(".tablaAdminPapeleta").DataTable({

      ajax: {

        url: "modules/papeletas/ajax/datatable-AdminPapeleta.ajax.php",
        type: "POST",

        data: function (d) {

          d.filtroFecha = filtroFecha;
          d.filtroFirma = filtroFirma;

        }

      },

      language: idioma_espanol,

      processing: true,
      serverSide: true,
      paging: true,

      pageLength: 10,
      lengthMenu: [10, 20, 30, 40],

      deferRender: true,
      searchDelay: 1000,

      autoWidth: false,
      responsive: true,

      drawCallback: function () {

        if (window.refreshFsLightbox) {
          refreshFsLightbox();
        }

      }

    });

  }

});

/* =========================================
ANULAR PAPELETA
========================================= */

$(document).on("click", ".btn-anular", function () {

  var id = $(this).data("id");

  Swal.fire({

    title: "¿Estás seguro?",
    text: "La papeleta será marcada como ANULADA",
    icon: "warning",

    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",

    confirmButtonText: "Sí, anular",

  }).then((result) => {

    if (result.isConfirmed) {

      $.ajax({

        url: "modules/papeletas/ajax/papeleta.ajax.php",
        type: "POST",
        dataType: "json",

        data: {
          action: "anularPapeleta",
          id_papeleta: id,
        },

        success: function (response) {

          if (response.status === "ok") {

            Swal.fire("ANULADA", response.message, "success");

            if (tabla) {
              tabla.ajax.reload(null, false);
            }

            if (tablaAdmin) {
              tablaAdmin.ajax.reload(null, false);
            }

          } else {

            Swal.fire("Error", response.message, "error");

          }

        },

        error: function (xhr) {

          console.error(xhr.responseText);

          Swal.fire(
            "Error AJAX",
            "No se pudo ejecutar la solicitud",
            "error"
          );

        }

      });

    }

  });

});
