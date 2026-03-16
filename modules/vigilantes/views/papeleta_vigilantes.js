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
      filtroFecha = $(this).data("filtro");
      tablaAdmin.ajax.reload();
    });

    $(".btn-filtro-cerrar").on("click", function () {
      filtroCerrar = $(this).data("filtro");
      tablaAdmin.ajax.reload();
    });

    // 🔹 BOTÓN RESTABLECER
    $("#btn-restablecer-filtros").on("click", function () {
      filtroFecha = null;
      filtroCerrar = null;
      tablaAdmin.ajax.reload();

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
