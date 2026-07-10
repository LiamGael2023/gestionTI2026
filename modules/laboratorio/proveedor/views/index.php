<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #004d99; border-color: #004d99; color: white;
    }
    .is-invalid { border-color: #dc3545 !important; }
    .invalid-feedback { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Proveedores / Clientes</li>
      </ol>
    </nav>
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">PROVEEDORES / CLIENTES</h2>
        <div class="text-muted mt-1">Gestión de proveedores de equipos y reactivos, y clientes del laboratorio.</div>
      </div>
    </div>
    <div class="row g-2" id="barra-acciones">
      <div class="col-auto" id="btn-nuevo-proveedor-wrap">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-proveedor">
          <i class="ti ti-plus me-2"></i> Nuevo Proveedor
        </button>
      </div>
      <div class="col-auto d-none" id="btn-nuevo-cliente-wrap">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-cliente">
          <i class="ti ti-plus me-2"></i> Nuevo Cliente
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="card">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="prov-cli-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-proveedores-btn" data-bs-toggle="tab" data-bs-target="#tab-proveedores-pane" type="button" role="tab">
              <i class="ti ti-building-store me-1"></i> Proveedores
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-clientes-btn" data-bs-toggle="tab" data-bs-target="#tab-clientes-pane" type="button" role="tab">
              <i class="ti ti-users me-1"></i> Clientes
            </button>
          </li>
        </ul>
      </div>
      <div class="card-body tab-content" id="prov-cli-tabs-content">

        <!-- TAB PROVEEDORES -->
        <div class="tab-pane fade show active" id="tab-proveedores-pane" role="tabpanel">
          <div class="table-responsive">
            <table id="tabla-proveedores" class="table table-vcenter card-table table-striped" style="width:100%">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Razón Social</th>
                  <th>RUC</th>
                  <th>Contacto</th>
                  <th>Teléfono</th>
                  <th>Email</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <!-- TAB CLIENTES -->
        <div class="tab-pane fade" id="tab-clientes-pane" role="tabpanel">
          <div class="table-responsive">
            <table id="tabla-clientes" class="table table-vcenter card-table table-striped" style="width:100%">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nombres</th>
                  <th>Apellidos</th>
                  <th>DNI</th>
                  <th>Teléfono</th>
                  <th>Email</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

      </div><!-- /tab-content -->
    </div>
  </div>
</div>

<!-- Modal Proveedor -->
<div class="modal modal-blur fade" id="modal-proveedor" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-proveedor-titulo">Nuevo Proveedor</h5>
      </div>
      <div class="modal-body">
        <form id="form-proveedor">
          <input type="hidden" id="Id_Proveedor" name="Id_Proveedor">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label required">Razón Social</label>
              <input type="text" class="form-control" id="Razon_Social" name="Razon_Social" placeholder="Nombre o razón social del proveedor" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">RUC</label>
              <input type="text" class="form-control" id="Ruc" name="Ruc" placeholder="20123456789">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nombre de Contacto</label>
              <input type="text" class="form-control" id="Nombre_Contacto" name="Nombre_Contacto" placeholder="Persona de contacto">
            </div>
            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="text" class="form-control" id="Telefono" name="Telefono" placeholder="999 999 999">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="Email" name="Email" placeholder="contacto@empresa.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">Dirección</label>
              <input type="text" class="form-control" id="Direccion" name="Direccion" placeholder="Dirección del proveedor">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-proveedor">
          <i class="ti ti-device-floppy me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Cliente -->
<div class="modal modal-blur fade" id="modal-cliente" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-cliente-titulo">Nuevo Cliente</h5>
      </div>
      <div class="modal-body">
        <form id="form-cliente">
          <input type="hidden" id="Id_Cliente" name="Id_Cliente">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Nombres <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="cli_Nombres" name="Nombres" placeholder="Nombres del cliente" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Apellido Paterno</label>
              <input type="text" class="form-control" id="cli_Apellido_Paterno" name="Apellido_Paterno" placeholder="Apellido paterno">
            </div>
            <div class="col-md-6">
              <label class="form-label">Apellido Materno</label>
              <input type="text" class="form-control" id="cli_Apellido_Materno" name="Apellido_Materno" placeholder="Apellido materno">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-cliente">
          <i class="ti ti-device-floppy me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tablaProveedores;
var tablaClientes;

$(document).ready(function () {
    tablaProveedores = $('#tabla-proveedores').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'modules/laboratorio/proveedor/views/data_listado.php', type: 'POST' },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } },
        order: [[0, 'desc']]
    });

    tablaClientes = $('#tabla-clientes').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'modules/laboratorio/proveedor/views/data_listado_clientes.php', type: 'POST' },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay clientes registrados", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } },
        order: [[0, 'desc']]
    });

    // Toggle action buttons based on active tab
    $('#tab-proveedores-btn').on('shown.bs.tab', function () {
        $('#btn-nuevo-proveedor-wrap').removeClass('d-none');
        $('#btn-nuevo-cliente-wrap').addClass('d-none');
    });
    $('#tab-clientes-btn').on('shown.bs.tab', function () {
        $('#btn-nuevo-proveedor-wrap').addClass('d-none');
        $('#btn-nuevo-cliente-wrap').removeClass('d-none');
    });

    $('#btn-guardar-proveedor').on('click', function () {
        guardarProveedor();
    });

    $('#modal-proveedor').on('hidden.bs.modal', function () {
        $('#form-proveedor')[0].reset();
        $('#Id_Proveedor').val('');
        $('#modal-proveedor-titulo').text('Nuevo Proveedor');
        $('#form-proveedor').find('.is-invalid').removeClass('is-invalid');
        $('#form-proveedor').find('.invalid-feedback').remove();
    });

    $('#btn-guardar-cliente').on('click', function () {
        guardarCliente();
    });

    $('#modal-cliente').on('hidden.bs.modal', function () {
        $('#form-cliente')[0].reset();
        $('#Id_Cliente').val('');
        $('#modal-cliente-titulo').text('Nuevo Cliente');
        $('#form-cliente').find('.is-invalid').removeClass('is-invalid');
        $('#form-cliente').find('.invalid-feedback').remove();
    });
});

function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function mostrarErroresEnFormulario(errores, selector) {
    $(selector).find('.invalid-feedback').remove();
    $(selector).find('.is-invalid').removeClass('is-invalid');
    Object.keys(errores).forEach(campo => {
        const msg = errores[campo];
        if (msg) {
            const input = $('[name="' + campo + '"]', selector);
            if (input.length) {
                input.addClass('is-invalid');
                input.after('<div class="invalid-feedback d-block">' + htmlEscape(msg) + '</div>');
            }
        }
    });
}

function guardarProveedor() {
    const id           = $('#Id_Proveedor').val();
    const razonSocial  = $('#Razon_Social').val().trim();

    if (!razonSocial) {
        $('#Razon_Social').addClass('is-invalid').after('<div class="invalid-feedback d-block">La razón social es obligatoria</div>');
        return;
    }

    const datos = {
        Id_Proveedor:    id || null,
        Razon_Social:    razonSocial,
        Ruc:             $('#Ruc').val().trim(),
        Nombre_Contacto: $('#Nombre_Contacto').val().trim(),
        Telefono:        $('#Telefono').val().trim(),
        Email:           $('#Email').val().trim(),
        Direccion:       $('#Direccion').val().trim()
    };

    const action = id ? 'actualizar' : 'guardar';

    $.ajax({
        url:         'modules/laboratorio/proveedor/controllers/ProveedorAPI.php?action=' + action,
        type:        'POST',
        contentType: 'application/json',
        data:        JSON.stringify(datos),
        dataType:    'json',
        success: function (response) {
            if (response.success) {
                $('#modal-proveedor').modal('hide');
                Swal.fire({ title: '¡Guardado!', text: response.message, icon: 'success', timer: 1500, showConfirmButton: false })
                    .then(() => tablaProveedores.ajax.reload());
            } else {
                if (response.errors) mostrarErroresEnFormulario(response.errors, '#form-proveedor');
                Swal.fire('Error', response.message, 'warning');
            }
        },
        error: function (xhr) {
            const r = xhr.responseJSON || {};
            if (r.errors) mostrarErroresEnFormulario(r.errors, '#form-proveedor');
            Swal.fire('Error', r.message || 'Error al guardar el proveedor', 'error');
        }
    });
}

function editarProveedor(id) {
    $.ajax({
        url:      'modules/laboratorio/proveedor/controllers/ProveedorAPI.php?action=obtener&id=' + id,
        type:     'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const p = response.data;
                $('#Id_Proveedor').val(p.Id_Proveedor);
                $('#Razon_Social').val(p.Razon_Social || '');
                $('#Ruc').val(p.Ruc || '');
                $('#Nombre_Contacto').val(p.Nombre_Contacto || '');
                $('#Telefono').val(p.Telefono || '');
                $('#Email').val(p.Email || '');
                $('#Direccion').val(p.Direccion || '');
                $('#modal-proveedor-titulo').text('Editar Proveedor');
                new bootstrap.Modal(document.getElementById('modal-proveedor')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el proveedor', 'error');
            }
        }
    });
}

function eliminarProveedor(id) {
    Swal.fire({
        title: '¿Confirmar eliminación?',
        text:  'Esta acción desactivará al proveedor',
        icon:  'warning',
        showCancelButton:  true,
        confirmButtonText: 'Eliminar',
        cancelButtonText:  'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url:      'modules/laboratorio/proveedor/controllers/ProveedorAPI.php?action=eliminar&id=' + id,
                type:     'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success').then(() => tablaProveedores.ajax.reload());
                    } else {
                        Swal.fire('No permitido', response.message, 'warning');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al eliminar';
                    Swal.fire('No permitido', msg, 'warning');
                }
            });
        }
    });
}

function reactivarProveedor(id) {
    Swal.fire({
        title: '¿Reactivar proveedor?',
        icon:  'question',
        showCancelButton:  true,
        confirmButtonText: 'Reactivar',
        cancelButtonText:  'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url:      'modules/laboratorio/proveedor/controllers/ProveedorAPI.php?action=reactivar&id=' + id,
                type:     'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success').then(() => tablaProveedores.ajax.reload());
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}

// ==================== CLIENTES ====================

function guardarCliente() {
    const id     = $('#Id_Cliente').val();
    const nombres = $('#cli_Nombres').val().trim();

    if (!nombres) {
        $('#cli_Nombres').addClass('is-invalid').after('<div class="invalid-feedback d-block">El nombre es obligatorio</div>');
        return;
    }

    const datos = {
        Id_Cliente:       id || null,
        Nombres:          nombres,
        Apellido_Paterno: $('#cli_Apellido_Paterno').val().trim(),
        Apellido_Materno: $('#cli_Apellido_Materno').val().trim()
    };

    const action = id ? 'actualizar' : 'guardar';

    $.ajax({
        url:         'modules/laboratorio/proveedor/controllers/ClienteAPI.php?action=' + action,
        type:        'POST',
        contentType: 'application/json',
        data:        JSON.stringify(datos),
        dataType:    'json',
        success: function (response) {
            if (response.success) {
                $('#modal-cliente').modal('hide');
                Swal.fire({ title: '¡Guardado!', text: response.message, icon: 'success', timer: 1500, showConfirmButton: false })
                    .then(() => tablaClientes.ajax.reload());
            } else {
                Swal.fire('Error', response.message, 'warning');
            }
        },
        error: function (xhr) {
            const r = xhr.responseJSON || {};
            Swal.fire('Error', r.message || 'Error al guardar el cliente', 'error');
        }
    });
}

function editarCliente(id) {
    $.ajax({
        url:      'modules/laboratorio/proveedor/controllers/ClienteAPI.php?action=obtener&id=' + id,
        type:     'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const c = response.data;
                $('#Id_Cliente').val(c.Id_Cliente);
                $('#cli_Nombres').val(c.Nombres || '');
                $('#cli_Apellido_Paterno').val(c.Apellido_Paterno || '');
                $('#cli_Apellido_Materno').val(c.Apellido_Materno || '');
                $('#modal-cliente-titulo').text('Editar Cliente');
                new bootstrap.Modal(document.getElementById('modal-cliente')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el cliente', 'error');
            }
        }
    });
}

function eliminarCliente(id) {
    Swal.fire({
        title: '¿Confirmar eliminación?',
        text:  'Esta acción desactivará al cliente',
        icon:  'warning',
        showCancelButton:  true,
        confirmButtonText: 'Eliminar',
        cancelButtonText:  'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url:      'modules/laboratorio/proveedor/controllers/ClienteAPI.php?action=eliminar&id=' + id,
                type:     'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success').then(() => tablaClientes.ajax.reload());
                    } else {
                        Swal.fire('No permitido', response.message, 'warning');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al eliminar';
                    Swal.fire('No permitido', msg, 'warning');
                }
            });
        }
    });
}

function reactivarCliente(id) {
    Swal.fire({
        title: '¿Reactivar cliente?',
        icon:  'question',
        showCancelButton:  true,
        confirmButtonText: 'Reactivar',
        cancelButtonText:  'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url:      'modules/laboratorio/proveedor/controllers/ClienteAPI.php?action=reactivar&id=' + id,
                type:     'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success').then(() => tablaClientes.ajax.reload());
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>
