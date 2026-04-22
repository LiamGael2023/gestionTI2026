<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
    
    /* Estilos para validación */
    .is-invalid {
        border-color: #dc3545 !important;
    }
    
    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
    
    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .kardex-table {
        font-size: 0.85rem;
    }
    
    .kardex-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .entrada {
        color: #31ce36;
        font-weight: 600;
    }
    
    .salida {
        color: #f97316;
        font-weight: 600;
    }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Reactivos</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">REACTIVOS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Gestiona y controla el inventario de reactivos químicos, visualiza existencias y supervisa las fechas de vencimiento</div>
      </div>
    </div>
    <div class="row g-2">
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-reactivo">
          <i class="ti ti-plus me-2"></i> Nuevo Reactivo
        </button>
      </div>
      <div class="col-auto">
        <a href="?module=laboratorio&action=reactivo&subaction=kardex" class="btn btn-primary">
          <i class="ti ti-calendar-stats me-2"></i> Kardex de Reactivos
        </a>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <!-- Alerta informativa -->
    <div class="alert alert-info" role="alert">
      <div class="d-flex">
        <div>
          <i class="ti ti-info-circle me-2"></i>
          <strong>Para añadir un nuevo insumo, haga clic en "Nuevo Reactivo".</strong> Para consultar el historial de movimientos de un producto específico, acceda al <strong>Kardex de Reactivos</strong>
        </div>
      </div>
    </div>

    <!-- Inventario de Reactivos -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Inventario de Reactivos</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tabla-reactivos" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Nombre</th>
                <th>U.M.</th>
                <th>Actual</th>
                <th>Fecha Vencimiento</th>
                <th>Fecha Ingreso</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nuevo Reactivo -->
<div class="modal modal-blur fade" id="modal-reactivo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-titulo">Nuevo Reactivo</h5>
      </div>
      <div class="modal-body">
        <form id="form-reactivo">
          <input type="hidden" id="Id_Reactivo" name="Id_Reactivo">
          
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="Nombre" name="Nombre" placeholder="Ej: Ácido Clorhídrico" required>
            <small class="text-muted">Mínimo 3 caracteres, debe contener letras</small>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Unidad de Medida <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="Unidad_Medida" name="Unidad_Medida" placeholder="Ej: ml, L, g, UND" value="UND">
                <small class="text-muted">Máximo 20 caracteres</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Fecha Vencimiento</label>
                <input type="date" class="form-control" id="Fecha_Vencimiento" name="Fecha_Vencimiento">
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Cantidad Inicial <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="Cantidad_Inicial" name="Cantidad_Inicial" step="0.01" placeholder="Ej: 100" required>
            <small class="text-muted">Esta cantidad aparecerá como ingreso inicial en el kardex</small>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-reactivo">Guardar Reactivo</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Kardex -->
<div class="modal modal-blur fade" id="modal-kardex" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">KARDEX DE REACTIVOS DE LABORATORIO</h5>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="tabla-kardex" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Nombre</th>
                <th>U.M.</th>
                <th>Tipo Movimiento</th>
                <th>Cantidad</th>
                <th>Concepto</th>
                <th>Saldo</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tablaReactivos;

$(document).ready(function() {
    // Inicializar DataTable Reactivos
    tablaReactivos = $('#tabla-reactivos').DataTable({
        "processing": true, 
        "serverSide": true,
        "ajax": { 
            "url": "modules/laboratorio/reactivo/views/data_listado_reactivos.php", 
            "type": "POST"
        },
        "columns": [
            { "data": 0 },  // No
            { "data": 1 },  // Nombre
            { "data": 2 },  // U.M.
            { "data": 3 },  // Actual
            { "data": 4 },  // Fecha Vencimiento
            { "data": 5 },  // Fecha Ingreso
            { "data": 6, "orderable": false }  // Acción
        ],
        "language": { 
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" 
        },
        "order": [[ 0, "asc" ]]
    });

    // Botones
    $('#btn-guardar-reactivo').on('click', function() {
        guardarReactivo();
    });

    // Limpiar modal al cerrar
    $('#modal-reactivo').on('hidden.bs.modal', function() {
        $('#form-reactivo')[0].reset();
        $('#Id_Reactivo').val('');
        $('#Unidad_Medida').val('UND');
        $('#modal-titulo').text('Nuevo Reactivo');
        // Limpiar estilos de error
        $('#form-reactivo').find('.is-invalid').removeClass('is-invalid');
        $('#form-reactivo').find('.invalid-feedback').remove();
    });

    // Cargar kardex cuando se abre el modal
    $('#modal-kardex').on('show.bs.modal', function() {
        cargarKardex();
    });
});

function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Validaciones en cliente
 */

function validarNombreReactivo(nombre) {
    nombre = nombre.trim();
    
    if (!nombre) {
        return 'El nombre del reactivo es obligatorio';
    }
    
    if (nombre.length < 3) {
        return 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (nombre.length > 100) {
        return 'El nombre no puede exceder 100 caracteres';
    }
    
    if (!/[a-zA-ZáéíóúñÁÉÍÓÚÑ]/i.test(nombre)) {
        return 'El nombre debe contener al menos una letra';
    }
    
    return null;
}

function validarUnidadMedida(unidad) {
    unidad = unidad.trim();
    
    if (!unidad) {
        return 'La unidad de medida es obligatoria';
    }
    
    if (unidad.length > 20) {
        return 'La unidad de medida no puede exceder 20 caracteres';
    }
    
    return null;
}

function validarFecha(fecha) {
    if (!fecha) {
        return null;
    }
    
    if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        return 'Formato de fecha inválido (YYYY-MM-DD)';
    }
    
    const d = new Date(fecha + 'T00:00:00');
    if (isNaN(d.getTime())) {
        return 'La fecha no es válida';
    }
    
    return null;
}

function validarCantidadInicial(cantidad) {
    if (cantidad === null || cantidad === undefined || cantidad === '') {
        return 'La cantidad inicial es obligatoria';
    }
    
    if (!parseFloat(cantidad) || parseFloat(cantidad) <= 0) {
        return 'La cantidad debe ser un número mayor a 0';
    }
    
    return null;
}

function mostrarErroresEnFormulario(errores, selector) {
    // Limpiar errores previos
    $(selector).find('.invalid-feedback').remove();
    $(selector).find('.is-invalid').removeClass('is-invalid');
    
    // Mostrar nuevos errores
    Object.keys(errores).forEach(campo => {
        const mensaje = errores[campo];
        if (mensaje) {
            const input = $(`[name="${campo}"]`);
            if (input.length) {
                input.addClass('is-invalid');
                input.after(`<div class="invalid-feedback d-block">${htmlEscape(mensaje)}</div>`);
            }
        }
    });
}

function guardarReactivo() {
    const id = $('#Id_Reactivo').val();
    const nombre = $('#Nombre').val();
    const unidad = $('#Unidad_Medida').val();
    const fechaVencimiento = $('#Fecha_Vencimiento').val();
    const cantidad = $('#Cantidad_Inicial').val();
    
    // Validaciones en cliente
    const errores = {};
    errores['Nombre'] = validarNombreReactivo(nombre);
    errores['Unidad_Medida'] = validarUnidadMedida(unidad);
    errores['Fecha_Vencimiento'] = validarFecha(fechaVencimiento);
    errores['Cantidad_Inicial'] = validarCantidadInicial(cantidad);
    
    // Filtrar errores null
    const erroresFiltrados = Object.keys(errores)
        .filter(key => errores[key] !== null)
        .reduce((obj, key) => {
            obj[key] = errores[key];
            return obj;
        }, {});
    
    // Si hay errores, mostrarlos
    if (Object.keys(erroresFiltrados).length > 0) {
        mostrarErroresEnFormulario(erroresFiltrados, '#form-reactivo');
        Swal.fire('Errores de validación', 'Por favor revise los campos marcados', 'warning');
        return;
    }
    
    const datos = {
        Id_Reactivo: id || null,
        Nombre: nombre,
        Unidad_Medida: unidad,
        Fecha_Vencimiento: fechaVencimiento,
        Cantidad_Inicial: cantidad
    };
    
    const action = id ? 'actualizar' : 'guardar';
    const url = `modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=${action}`;
    
    $.ajax({
        url: url,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#modal-reactivo').modal('hide');
                Swal.fire({
                    title: '¡Guardado!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    tablaReactivos.ajax.reload();
                });
            } else {
                // Mostrar errores del servidor
                if (response.errors) {
                    mostrarErroresEnFormulario(response.errors, '#form-reactivo');
                    Swal.fire('Errores de validación', response.message, 'warning');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        },
        error: function(xhr, status, error) {
            const response = xhr.responseJSON || {};
            if (response.errors) {
                mostrarErroresEnFormulario(response.errors, '#form-reactivo');
                Swal.fire('Errores de validación', response.message || error, 'warning');
            } else {
                Swal.fire('Error', 'Error al guardar el reactivo: ' + (response.message || error), 'error');
            }
        }
    });
}

function editarReactivo(id) {
    $.ajax({
        url: `modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=obtener&id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const reactivo = response.data;
                $('#Id_Reactivo').val(reactivo.Id_Reactivo);
                $('#Nombre').val(reactivo.Nombre);
                $('#Unidad_Medida').val(reactivo.Unidad_Medida);
                $('#Fecha_Vencimiento').val(reactivo.Fecha_Vencimiento || '');
                $('#Cantidad_Inicial').val('');
                $('#Cantidad_Inicial').attr('placeholder', 'Stock actual: ' + reactivo.Cantidad_Stock);
                $('#modal-titulo').text('Editar Reactivo');
                new bootstrap.Modal(document.getElementById('modal-reactivo')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el reactivo', 'error');
            }
        }
    });
}

function eliminarReactivo(id) {
    Swal.fire({
        title: '¿Confirmar eliminación?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=eliminar&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success').then(() => {
                            tablaReactivos.ajax.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'No permitido',
                            text: response.message,
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    let mensaje = 'Error al procesar la solicitud';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        mensaje = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            mensaje = parsed.message || xhr.responseText;
                        } catch (e) {
                            mensaje = xhr.responseText || 'Error desconocido';
                        }
                    }
                    Swal.fire({
                        title: 'No permitido',
                        text: mensaje,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

function reactivarReactivo(id) {
    Swal.fire({
        title: '¿Reactivar reactivo?',
        text: 'Este reactivo volverá a estar disponible',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=reactivar&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success').then(() => {
                            tablaReactivos.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Error al reactivar el reactivo', 'error');
                }
            });
        }
    });
}

function cargarKardex() {
    $.ajax({
        url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=obtener_kardex',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.movimientos) {
                let html = '';
                response.movimientos.forEach((mov, index) => {
                    const tipoClass = mov.Tipo_Movimiento === 'E' ? 'entrada' : 'salida';
                    const tipoTexto = mov.Tipo_Movimiento === 'E' ? 'Entrada' : 'Salida';
                    
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${mov.Reactivo_Nombre || '-'}</td>
                            <td>${mov.U_M || 'UND'}</td>
                            <td><span class="${tipoClass}">${tipoTexto}</span></td>
                            <td>${parseFloat(mov.Cantidad).toFixed(2)}</td>
                            <td>${mov.Concepto || '-'}</td>
                            <td>${parseFloat(mov.Saldo_Resultante).toFixed(2)}</td>
                            <td>${mov.Fecha_Registro ? mov.Fecha_Registro.substring(0, 19) : '-'}</td>
                        </tr>
                    `;
                });
                $('#tabla-kardex tbody').html(html);
                if ($.fn.dataTable.isDataTable('#tabla-kardex')) {
                    $('#tabla-kardex').DataTable().destroy();
                }
                $('#tabla-kardex').DataTable({
                    paging: true,
                    pageLength: 10,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                    },
                    order: [[ 0, "desc" ]]
                });
            }
        },
        error: function() {
            Swal.fire('Error', 'No se pudo cargar el kardex', 'error');
        }
    });
}
</script>
