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

  // Tabla Admin Papeleta
  // Tabla Admin Papeleta
  if ($(".tablaPapeletaVigilantes").length) {
    $(".tablaPapeletaVigilantes").DataTable().destroy();

    let filtroFecha = null;
    let filtroCerrar = null;

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

    $(".btn-filtro-cerrar").on("click", function () {
      // Remover la clase del resto
      $(".btn-filtro-cerrar").removeClass("active");

      // Agregar la clase al seleccionado
      $(this).addClass("active");

      // Guardar filtro
      filtroCerrar = $(this).data("filtro");

      // Recargar DataTable
      tablaAdmin.ajax.reload();

      // 🕒 Esperar 1 segundo antes de cerrar
      setTimeout(() => {
        $("#collapseEstados").collapse("hide");
      }, 1000);
    });

    // 🔹 BOTÓN RESTABLECER
    $("#btn-restablecer-filtros2").on("click", function () {
      filtroFecha = null;
      filtroCerrar = null;
      tablaAdmin.ajax.reload();
      console.log("Restablecer:", filtroCerrar);
      $(".btn-filtro-fecha").removeClass("active");
      $(".btn-filtro-cerrar").removeClass("active");
      // 🔹 Opcional: remover estilos activos si los usas
      //$(".btn-filtro-fecha, .btn-filtro-cerrar").removeClass("active");
    });

    var tablaAdmin = $(".tablaPapeletaVigilantes").DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: "modules/vigilantes/ajax/datatable-PapeletaVigilantes.ajax.php",
        type: "POST",
        data: function (d) {
          d.filtroFecha = filtroFecha;
          d.filtroCerrar = filtroCerrar;
        },
      },
      pageLength: 10,
      lengthMenu: [10, 20, 30, 40],
      deferRender: true,
      language: idioma_espanol,
      searchDelay: 1000,
    });
  }
});
