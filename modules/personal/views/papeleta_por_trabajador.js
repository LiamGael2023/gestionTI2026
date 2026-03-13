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



  let idTrabajador = null; // <<--- variable global

  if ($(".tablaPapeletaPorTrabajador").length) {
    $(".tablaPapeletaPorTrabajador").DataTable().destroy();

    var tablaAdmin = $(".tablaPapeletaPorTrabajador").DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: "modules/personal/ajax/datatable-PapeletasPorTrabajador.ajax.php",
        type: "POST",
        data: function (d) {
          d.id_trabajador = idTrabajador; // <<--- enviamos el ID
        },
      },
      pageLength: 10,
      lengthMenu: [10, 20, 30, 40],
      deferRender: true,
      language: idioma_espanol,
      searchDelay: 500,
    });

    // 🔥 CUANDO SELECCIONAN UN TRABAJADOR → recargar tabla
    $("#trabajador").on("change", function () {
      idTrabajador = $(this).val(); // obtenemos el ID
      tablaAdmin.ajax.reload(); // recargamos tabla
    });
  }
});
