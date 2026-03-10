var idioma_espanol = {
  "sProcessing": "Procesando...",
  "sLengthMenu": "Mostrar _MENU_ registros",
  "sZeroRecords": "No se encontraron resultados",
  "sEmptyTable": "Ningún dato disponible en esta tabla",
  "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
  "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
  "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
  "sInfoPostFix": "",
  "sSearch": "Buscar:",
  "sUrl": "",
  "sInfoThousands": ",",
  "sLoadingRecords": "Cargando...",
  "oPaginate": {
    "sFirst": "Primero",
    "sLast": "Último",
    "sNext": "Siguiente",
    "sPrevious": "Anterior"
  },
  "oAria": {
    "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
  }
};
document.addEventListener("DOMContentLoaded", function () {



  var tabla = $(".tablaConductor").DataTable({
    language: idioma_espanol,
    ajax: {
      url: "modules/transportes/ajax/datatable-conductor.ajax.php",
      type: "POST",
      data: function (d) {
        d.fecha = $("#fechaBusqueda").val();
      },
    }
  });

  // ⬅️ Se ejecuta cuando DataTable recibe la data del servidor (carga inicial + reloads)


  // Inicializa flatpickr
  flatpickr("#fechaBusqueda", {
    dateFormat: "d/m/Y",
    defaultDate: "today",
    locale: "es",
    allowInput: true,
    disableMobile: true,
    onChange: function () {
      tabla.ajax.reload(null, false);
    },
  });

});
