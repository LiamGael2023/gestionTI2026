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

  // Tabla Registro Papeleta
  if ($(".tablaRegistroPapeleta").length) {
    var tabla = $(".tablaRegistroPapeleta").DataTable({
      serverSide: true,
      processing: true,
      language: idioma_espanol,
      ajax: {
        url: "modules/papeletas/ajax/datatable-RegistroPapeleta.ajax.php",
        type: "POST",
      },
          columnDefs: [
        {
            targets: 2,   // número de columna que quieres centrar
            className: 'dt-center'
        }
    ],

      pageLength: 10,
      lengthMenu: [10, 20, 30, 40],
      deferRender: true,
      searchDelay: 1000,

      initComplete: function () {
        // Contenedor filtro: flex y contenido alineado a la derecha
        $(".dataTables_filter").addClass("d-flex align-items-center").css({
          "justify-content": "flex-end",
          gap: "0",
        });

        const labelFiltro = $(".dataTables_filter label");
        labelFiltro.css({
          display: "flex",
          "align-items": "center",
          gap: "0",
          margin: 0,
        });

        // Crear botón REGISTRAR
        window.botonRegistrar = $(`
        <a type="button" 
          style="background-color:#285289; border-color:#285289;"
          class="btn btn-primary px-5 mx-2 d-flex align-items-center gap-2"
          data-bs-toggle="modal" 
          data-bs-target="#modal-report">
          <svg xmlns="http://www.w3.org/2000/svg" 
            width="20" height="20" viewBox="0 0 24 24" fill="currentColor" 
            class="icon-tabler icon-tabler-library-plus">
            <path d="M18.333 2a3.667 3.667 0 0 1 3.667 3.667v8.666a3.667 3.667 0 0 1 -3.667 3.667h-8.666a3.667 3.667 0 0 1 -3.667 -3.667v-8.666a3.667 3.667 0 0 1 3.667 -3.667zm-4.333 4a1 1 0 0 0 -1 1v2h-2a1 1 0 0 0 0 2h2v2a1 1 0 0 0 2 0v-2h2a1 1 0 0 0 0 -2h-2v-2a1 1 0 0 0 -1 -1" />
            <path d="M3.517 6.391a1 1 0 0 1 .99 1.738c-.313 .178 -.506 .51 -.507 .868v10c0 .548 .452 1 1 1h10c.284 0 .405 -.088 .626 -.486a1 1 0 0 1 1.748 .972c-.546 .98 -1.28 1.514 -2.374 1.514h-10c-1.652 0 -3 -1.348 -3 -3v-10.002a3 3 0 0 1 1.517 -2.605" />
          </svg>
          REGISTRAR
        </a>
      `);

        labelFiltro.append(window.botonRegistrar);
        labelFiltro.find("input").css({ flex: "0 0 200px", margin: 0 });

        // Comprobar si el trabajador tiene papeleta pendiente al cargar
        checkPapeletaPendiente();
      },

      drawCallback: function () {
        // Cada vez que se dibuja/reload de la tabla
        checkPapeletaPendiente();

        if (window.lazySizes) lazySizes.loader.checkElems();
      },
    });

    // Función para verificar si el trabajador tiene papeleta pendiente
    function checkPapeletaPendiente() {
      $.ajax({
        url: "modules/papeletas/ajax/papeleta.ajax.php",
        type: "POST",
        dataType: "json",
        data: {
          accion: "tiene_papeleta_pendiente",
          id_trabajador: 206, // reemplaza por el id dinámico
        },
        success: function (response) {
          if (response.status === "success" && response.tienePendiente === 1) {
            // Deshabilitar botón
            window.botonRegistrar
              .addClass("disabled")
              .attr("aria-disabled", "true");
          } else {
            // Habilitar botón si no tiene pendiente
            window.botonRegistrar
              .removeClass("disabled")
              .removeAttr("aria-disabled");
          }
        },
        error: function (xhr, status, error) {
          console.error("Error al consultar papeletas pendientes:", error);
        },
      });
    }
  }

  // Tabla Admin Papeleta
  // Tabla Admin Papeleta
  if ($(".tablaAdminPapeleta").length) {
    $(".tablaAdminPapeleta").DataTable().destroy();

    let filtroFecha = null;
    let filtroFirma = null;

    $(".btn-filtro-fecha").on("click", function () {
      // Remover la clase del resto
      $(".btn-filtro-fecha").removeClass("active");

      // Agregar la clase al seleccionado
      $(this).addClass("active");

      // Guardar filtro
      filtroFecha = $(this).data("filtro");

      // Recargar DataTable
      tablaAdmin.ajax.reload();

      // 🕒 Esperar 1 segundo antes de cerrar
      setTimeout(() => {
        $("#collapseFecha").collapse("hide");
      }, 1000);
    });

    $(".btn-filtro-firma").on("click", function () {
      // Remover la clase del resto
      $(".btn-filtro-firma").removeClass("active");

      // Agregar la clase al seleccionado
      $(this).addClass("active");

      // Guardar filtro
      filtroFirma = $(this).data("filtro");

      // Recargar DataTable
      tablaAdmin.ajax.reload();

      // 🕒 Esperar 1 segundo antes de cerrar
      setTimeout(() => {
        $("#collapseFirma").collapse("hide");
      }, 1000);
    });

    $("#btn-restablecer-filtros2").on("click", function () {
      filtroFecha = null;
      filtroFirma = null;
      tablaAdmin.ajax.reload();
      console.log("Restablecer:", filtroFirma);
      $(".btn-filtro-fecha").removeClass("active");
      $(".btn-filtro-firma").removeClass("active");
      // 🔹 Opcional: remover estilos activos si los usas
      //$(".btn-filtro-fecha, .btn-filtro-cerrar").removeClass("active");
    });

    var tablaAdmin = $(".tablaAdminPapeleta").DataTable({
      ajax: {
        url: "ajax/datatables/datatable-AdminPapeleta.ajax.php",
        type: "POST",
        data: function (d) {
          d.filtroFecha = filtroFecha;
          d.filtroFirma = filtroFirma;
        },
      },
      columnDefs: [
        {
          targets: 0,
          width: "3%",
          createdCell: (td) => $(td).css("width", "3%"),
        }, // col-id
        {
          targets: 1,
          width: "5%",
          createdCell: (td) => $(td).css("width", "5%"),
        }, // col-foto
        {
          targets: 2,
          width: "3%",
          createdCell: (td) => $(td).css("width", "3%"),
        }, // col-qr
        {
          targets: 3,
          width: "15%",
          createdCell: (td) => $(td).css("width", "15%"),
        }, // col-nombres
        {
          targets: 4,
          width: "3%",
          createdCell: (td) => $(td).css("width", "3%"),
        }, // col-f1
        {
          targets: 5,
          width: "3%",
          createdCell: (td) => $(td).css("width", "3%"),
        }, // col-f2
        {
          targets: 6,
          width: "3%",
          createdCell: (td) => $(td).css("width", "3%"),
        }, // col-f3
        {
          targets: 7,
          width: "3%",
          createdCell: (td) => $(td).css("width", "3%"),
        }, // col-f4
        {
          targets: 8,
          width: "6%",
          createdCell: (td) => $(td).css("width", "6%"),
        }, // col-fechas
        {
          targets: 9,
          width: "6%",
          createdCell: (td) => $(td).css("width", "6%"),
        }, // col-horas
        {
          targets: 10,
          width: "3%",
          createdCell: (td) => $(td).css("width", "3%"),
        }, // col-retorno
        {
          targets: 11,
          width: "25%",
          createdCell: (td) => $(td).css("width", "25%"),
        }, // col-concepto
        {
          targets: 12,
          width: "22%",
          createdCell: (td) => $(td).css("width", "22%"),
        }, // col-acciones
      ],

      initComplete: function () {},
      language: idioma_espanol,
      processing: true,
      autoWidth: false, // permite respetar ancho definido en CSS
      responsive: true,
      scrollX: false,
      retrieve: true,
      responsive: true,

      serverSide: true,
      paging: true,
      pageLength: 10,
      lengthMenu: [10, 20, 30, 40],
      deferRender: true,
      searchDelay: 1000, // 🔹 aquí defines el delay en milisegundos

      drawCallback: function () {
        if (window.refreshFsLightbox) {
          window.refreshFsLightbox();
        }
      },
    });
  }
});

  $(document).on("click", ".btn-anular", function() {

        console.log("CLICK DETECTADO");

        var id = $(this).data("id");

        Swal.fire({
            title: '¿Estás seguro?',
            text: "La papeleta será marcada como ANULADA",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "modules/papeletas/ajax/papeleta.ajax.php",
                    type: "POST",
                    dataType: "json", // 👈 indica que la respuesta será JSON
                    data: {
                        action: "anularPapeleta",
                        id_papeleta: id
                    },
                    success: function(response) {
                        console.log("✅ SUCCESS:", response);

                        if (response.status === "ok") {
                            Swal.fire("ANULADA", response.message, "success");

                            // 🔄 Recargar tabla server-side SIN mover página
                            const table = $(".tablaRegistroPapeleta").DataTable();
                            table.ajax.reload(null, false);

                            // Mensaje opcional

                        } else {
                            console.warn("⚠️ Error:", response.message);
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("❌ ERROR AJAX:", error);
                        console.log("Estado:", status);
                        console.log("Respuesta del servidor:", xhr.responseText);

                        Swal.fire("Error AJAX", "No se pudo ejecutar la solicitud.\n" + error, "error");
                    },
                    complete: function(xhr, status) {
                        console.log("ℹCOMPLETE:", status);
                        console.log("Respuesta completa del servidor:", xhr.responseText);
                    }
                });

            }
        });

    });


    