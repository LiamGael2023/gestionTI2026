$(document).ready(function () {

    var idioma_espanol = {
        "sProcessing": "Procesando...",
        "sLengthMenu": "Mostrar _MENU_ registros",
        "sZeroRecords": "No se encontraron resultados",
        "sEmptyTable": "Ningún dato disponible en esta tabla",
        "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
        "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
        "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
        "sSearch": "Buscar:",
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

    // Inicializar DataTables SOLO si no existe
    $('.tablaRegistroVehiculo').each(function(){

        if (!$.fn.DataTable.isDataTable(this)) {

            $(this).DataTable({
                language: idioma_espanol,
                ajax:{
                    url: "modules/transportes/ajax/datatable-RegistroVehiculo.ajax.php",
                    type: "POST",
                    dataSrc: "data"
                },
                responsive: true,
                processing: true
            });

        }

    });

});


/* ===============================
   DAR DE BAJA VEHICULO
================================ */

$(document).on("click", ".btn-baja", function() {

    var id = $(this).data("id");

    Swal.fire({
        title: '¿Estás seguro?',
        text: "El vehiculo será dado de BAJA",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, Dar de Baja'
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({

                url: "modules/transportes/ajax/vehiculo.ajax.php",
                type: "POST",

                data: {
                    action: "bajaVehiculo",
                    id_papeleta: id
                },

                success: function(response) {

                    if (response.trim() === "ok") {

                        Swal.fire(
                            'EXITO!',
                            'El vehiculo fue dado de BAJA.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });

                    } else {

                        Swal.fire("Error", "No se pudo dar de baja", "error");

                    }

                }

            });

        }

    });

});


/* ===============================
   ANULAR VEHICULO
================================ */

$(document).on("click", ".btn-anular", function() {

    var id = $(this).data("id");

    Swal.fire({
        title: '¿Estás seguro?',
        text: "El vehiculo será ANULADO",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular'
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({

                url: "modules/transportes/ajax/vehiculo.ajax.php",
                type: "POST",
                dataType: "json",

                data: {
                    action: "anularVehiculo",
                    placa: id
                },

                success: function(response) {

                    if (response.status === "ok") {

                        Swal.fire('Anulado', 'El vehiculo ha sido anulado.', 'success');

                        let $fila = $('button[data-id="' + id + '"]').closest('tr');

                        if ($fila.hasClass('child')) {
                            $fila = $fila.prev();
                        }

                        const table = $('.tablaRegistroVehiculo').DataTable();

                        let rowData = table.row($fila).data();

                        rowData[2] = `
                        <div class="btn-group">
                            <button class="btn btn-danger btn-cuadrado" title="Anulado">
                                ❌
                            </button>
                        </div>
                        `;

                        rowData[3] = `
                        <div class="btn-group">
                            <button class="btn btn-danger btn-cuadrado" title="Anulado">
                                ❌
                            </button>
                        </div>
                        `;

                        rowData[rowData.length - 1] = `
                        <div class="btn-group">
                            <span class="btn btn-danger">ANULADO</span>
                        </div>
                        `;

                        table.row($fila).data(rowData).invalidate().draw(false);

                        $fila.addClass('table-danger');

                    } else {

                        Swal.fire('Error', 'No se pudo anular el vehiculo.', 'error');

                    }

                },

                error: function(xhr, status, error) {

                    console.error("ERROR AJAX:", error);
                    console.log(xhr.responseText);

                    Swal.fire("Error AJAX", "No se pudo ejecutar la solicitud", "error");

                }

            });

        }

    });

});


/* ===============================
   FIX PARA DATATABLES EN TABS
================================ */

$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {

    $.fn.dataTable
        .tables({ visible: true, api: true })
        .columns.adjust();

});
