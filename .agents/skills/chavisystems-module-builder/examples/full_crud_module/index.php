<?php
// Obtencion dinamica de permisos
$permisos = Auth::permisosModulo('ejemplo');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title text-primary">
                    <i class="ti ti-box me-2"></i> Modulo de Ejemplo
                </h2>
                <div class="text-muted mt-1">Gestion de registros de prueba</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <?php if ($permisos['pueden_exportar'] == 1): ?>
                <button type="button" class="btn btn-outline-success me-2" id="btnExportarExcel">
                    <i class="ti ti-file-spreadsheet me-1"></i> Exportar Excel
                </button>
                <button type="button" class="btn btn-outline-danger me-2" id="btnExportarPDF">
                    <i class="ti ti-file-text me-1"></i> Exportar PDF
                </button>
                <?php endif; ?>
                <?php if ($permisos['pueden_crear'] == 1): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistro" id="btnNuevo">
                    <i class="ti ti-plus me-1"></i> Nuevo Registro
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaEjemplo" class="table table-vcenter card-table table-striped w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Codigo</th>
                                <th>Descripcion</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO / EDICION -->
<div class="modal modal-blur fade" id="modalRegistro" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="formRegistro" autocomplete="off">
                <div class="modal-header bg-pech">
                    <h5 class="modal-title" id="modalTitulo">Formulario de Registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id" id="id">

                    <div class="mb-3">
                        <label class="form-label required">Codigo</label>
                        <input type="text" class="form-control" name="codigo" id="codigo" required placeholder="EJ-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Descripcion</label>
                        <textarea class="form-control" name="descripcion" id="descripcion" rows="3" required placeholder="Ingrese una descripcion"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary ms-auto" id="btnGuardar">
                        <i class="ti ti-device-floppy me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabla = $('#tablaEjemplo').DataTable({
        "ajax": "modules/ejemplo/ajax/datatable-example.ajax.php",
        "deferRender": true,
        "retrieve": true,
        "processing": true,
        "responsive": true,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    // ============================================================
    // ABRIR MODAL PARA NUEVO REGISTRO
    // ============================================================
    $('#btnNuevo').on('click', function () {
        $('#modalTitulo').text('Formulario de Registro');
        $('#accion').val('crear');
        $('#id').val('');
        $('#codigo').val('');
        $('#descripcion').val('');
    });

    // ============================================================
    // ABRIR MODAL PARA EDICION (cargar datos existentes)
    // ============================================================
    $(document).on('click', '.btnEditar', function () {
        const id = $(this).attr('idItem');

        $.ajax({
            url: "modules/ejemplo/ajax/example.ajax.php",
            method: "POST",
            data: { accion: "obtener", id: id },
            dataType: "json",
            success: function (item) {
                if (item) {
                    $('#modalTitulo').text('Editar Registro');
                    $('#accion').val('editar');
                    $('#id').val(item.id);
                    $('#codigo').val(item.codigo);
                    $('#descripcion').val(item.descripcion);
                    $('#modalRegistro').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el registro.'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrio un error al obtener los datos.'
                });
            }
        });
    });

    // ============================================================
    // ELIMINAR REGISTRO CON CONFIRMACION
    // ============================================================
    $(document).on('click', '.btnEliminar', function () {
        const id = $(this).attr('idItem');

        Swal.fire({
            title: 'Esta seguro de eliminar este registro?',
            text: "Esta accion no se puede deshacer!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "modules/ejemplo/ajax/example.ajax.php",
                    method: "POST",
                    data: { accion: "eliminar", id: id },
                    dataType: "json",
                    success: function (respuesta) {
                        if (respuesta.status === 'success') {
                            tabla.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado!',
                                text: respuesta.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: respuesta.message
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrio un error en la solicitud.'
                        });
                    }
                });
            }
        });
    });

    // ============================================================
    // ENVIO DE FORMULARIO (CREAR / EDITAR)
    // ============================================================
    $('#formRegistro').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: "modules/ejemplo/ajax/example.ajax.php",
            method: "POST",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend: function () {
                $('#btnGuardar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');
            },
            success: function (respuesta) {
                $('#btnGuardar').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Guardar');
                if (respuesta.status === 'success') {
                    $('#modalRegistro').modal('hide');
                    $('#formRegistro')[0].reset();
                    tabla.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Exito!',
                        text: respuesta.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message
                    });
                }
            },
            error: function () {
                $('#btnGuardar').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Guardar');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrio un error en la solicitud.'
                });
            }
        });
    });

    // ============================================================
    // EXPORTAR A EXCEL
    // ============================================================
    $('#btnExportarExcel').on('click', function () {
        window.open('modules/ejemplo/reportes/exportar_excel.php', '_blank');
    });

    // ============================================================
    // EXPORTAR A PDF
    // ============================================================
    $('#btnExportarPDF').on('click', function () {
        window.open('modules/ejemplo/reportes/reporte_pdf.php', '_blank');
    });
});
</script>
