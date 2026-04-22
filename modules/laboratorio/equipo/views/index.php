<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
    .badge-disponible { background-color: #31ce36; }
    .badge-correctivo { background-color: #f97316; }
    .badge-preventivo { background-color: #ffc107; }
    .badge-predictivo { background-color: #0d6efd; }
    
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
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Equipos</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">EQUIPOS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Para registrar un nuevo estado o equipo, haga clic en el botón de abajo.</div>
      </div>
    </div>
    <div class="row g-2">
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-equipo">
          <i class="ti ti-plus me-2"></i> Nuevo Equipo
        </button>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-estado">
          <i class="ti ti-plus me-2"></i> Nuevo Estado
        </button>
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
          <strong>Nota importante:</strong> Solo está permitido el uso y reserva de los equipos que figuren con el estado "Disponible". Cualquier equipo en estado diferente al indicado no podrá ser seleccionado hasta que se valide su operatividad.
        </div>
      </div>
    </div>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tab-equipos" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">Inventario de Equipos</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-estados" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab">Lista de Estados</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active show" id="tab-equipos" role="tabpanel">
                        <div class="table-responsive">
                            <table id="tabla-equipos" class="table table-vcenter card-table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nombre</th>
                                        <th>Proveedor</th>
                                        <th>Próxima Calibración</th>
                                        <th>Fecha Disponible</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="tab-estados" role="tabpanel">
                        <div class="table-responsive">
                            <table id="tabla-estados" class="table table-vcenter card-table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
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
  </div>
</div>

<!-- Modal Nuevo Equipo -->
<div class="modal modal-blur fade" id="modal-equipo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-titulo">Nuevo Equipo</h5>
      </div>
      <div class="modal-body">
        <form id="form-equipo">
          <input type="hidden" id="Id_Equipo" name="Id_Equipo">
          
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" id="Nombre" name="Nombre" placeholder="Nombre del equipo" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Proveedor</label>
            <input type="text" class="form-control" id="Proveedor" name="Proveedor" placeholder="Proveedor del equipo">
          </div>

          <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-control" id="Id_Estado" name="Id_Estado" required>
              <option value="">Seleccionar estado...</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Fecha Última Calibración</label>
            <input type="date" class="form-control" id="Fecha_Ultima_Calibracion" name="Fecha_Ultima_Calibracion">
          </div>

          <div class="mb-3">
            <label class="form-label">Fecha Próxima Calibración</label>
            <input type="date" class="form-control" id="Fecha_Proxima_Calibracion" name="Fecha_Proxima_Calibracion">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-equipo">Guardar Equipo</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nuevo Estado -->
<div class="modal modal-blur fade" id="modal-estado" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-estado-titulo">Nuevo Estado</h5>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <strong>Estados:</strong>
          <ul>
            <li>Disponible</li>
            <li>Correctivo</li>
            <li>Preventivo</li>
            <li>Predictivo</li>
          </ul>
        </div>
        <form id="form-estado">
          <input type="hidden" id="Id_Estado_Edit" name="Id_Estado_Edit">
          
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" id="Nombre_Estado" name="Nombre" placeholder="Disponible" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <input type="text" class="form-control" id="Descripcion_Estado" name="Descripcion" placeholder="Descripción del estado">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-estado">Crear Estado</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tablaEquipos, tablaEstados;

$(document).ready(function() {
    // Inicializar DataTable Equipos con serverSide
    tablaEquipos = $('#tabla-equipos').DataTable({
        "processing": true, 
        "serverSide": true,
        "ajax": { 
            "url": "modules/laboratorio/equipo/views/data_listado.php", 
            "type": "POST" 
        },
        "columns": [ 
            { "data": 0 },  // No
            { "data": 1 },  // Nombre
            { "data": 2 },  // Proveedor
            { "data": 3 },  // Próxima Calibración
            { "data": 4 },  // Fecha Disponible
            { "data": 5 },  // Estado
            { "data": 6, "orderable": false }  // Acción
        ],
        "language": { 
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" 
        },
        "order": [[ 0, "desc" ]]
    });

    // Inicializar DataTable Estados con serverSide
    tablaEstados = $('#tabla-estados').DataTable({
        "processing": true, 
        "serverSide": true,
        "ajax": { 
            "url": "modules/laboratorio/equipo/views/data_listado_estados.php", 
            "type": "POST" 
        },
        "columns": [ 
            { "data": 0 },  // No
            { "data": 1 },  // Nombre
            { "data": 2 },  // Descripción
            { "data": 3, "orderable": false }  // Acción
        ],
        "language": { 
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" 
        },
        "order": [[ 0, "desc" ]]
    });

    // Cargar opciones para select
    cargarSelectEstados();

    // Botón guardar equipo
    $('#btn-guardar-equipo').on('click', function() {
        guardarEquipo();
    });

    // Botón guardar estado
    $('#btn-guardar-estado').on('click', function() {
        guardarEstado();
    });

    // Limpiar modal al cerrar
    $('#modal-equipo').on('hidden.bs.modal', function() {
        $('#form-equipo')[0].reset();
        $('#Id_Equipo').val('');
        $('#modal-titulo').text('Nuevo Equipo');
        // Limpiar estilos de error
        $('#form-equipo').find('.is-invalid').removeClass('is-invalid');
        $('#form-equipo').find('.invalid-feedback').remove();
    });

    $('#modal-estado').on('hidden.bs.modal', function() {
        $('#form-estado')[0].reset();
        $('#Id_Estado_Edit').val('');
        $('#modal-estado-titulo').text('Nuevo Estado');
        // Limpiar estilos de error
        $('#form-estado').find('.is-invalid').removeClass('is-invalid');
        $('#form-estado').find('.invalid-feedback').remove();
    });

    // Recalcular anchos al cambiar de tab
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (el) {
        el.addEventListener('shown.bs.tab', function () {
            if (tablaEquipos) {
                tablaEquipos.columns.adjust();
            }
            if (tablaEstados) {
                tablaEstados.columns.adjust();
            }
        });
    });
});

function cargarSelectEstados() {
    $.ajax({
        url: 'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=listar_estados',
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(response) {
            if (response && response.success && Array.isArray(response.data)) {
                let options = '<option value="">Seleccionar estado...</option>';
                response.data.forEach(estado => {
                    options += `<option value="${estado.Id_Estado}">${htmlEscape(estado.Nombre)}</option>`;
                });
                $('#Id_Estado').html(options);
            } else {
                console.warn('Respuesta inválida de listar_estados:', response);
                $('#Id_Estado').html('<option value="">Error cargando estados</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando estados:', error, xhr.responseText);
            $('#Id_Estado').html('<option value="">Error de conexión</option>');
        }
    });
}

function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Valida un nombre (debe tener al menos una letra, 3-100 caracteres)
 */
function validarNombreEquipo(nombre) {
    nombre = nombre.trim();
    
    if (!nombre) {
        return 'El nombre del equipo es obligatorio';
    }
    
    if (nombre.length < 3) {
        return 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (nombre.length > 100) {
        return 'El nombre no puede exceder 100 caracteres';
    }
    
    // Debe contener al menos una letra
    if (!/[a-zA-ZáéíóúñÁÉÍÓÚÑ]/i.test(nombre)) {
        return 'El nombre debe contener al menos una letra (no puede ser solo números)';
    }
    
    return null;
}

/**
 * Valida el estado (debe seleccionar uno)
 */
function validarEstado(idEstado) {
    if (!idEstado || idEstado === '') {
        return 'Debe seleccionar un estado';
    }
    return null;
}

/**
 * Valida el proveedor (opcional, máximo 100 caracteres)
 */
function validarProveedor(proveedor) {
    if (proveedor && proveedor.length > 100) {
        return 'El proveedor no puede exceder 100 caracteres';
    }
    return null;
}

/**
 * Valida fecha en formato YYYY-MM-DD
 */
function validarFecha(fecha) {
    if (!fecha) {
        return null; // La fecha es opcional
    }
    
    // Validar formato YYYY-MM-DD
    if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        return 'La fecha debe estar en formato YYYY-MM-DD';
    }
    
    // Validar que sea una fecha válida
    const d = new Date(fecha + 'T00:00:00');
    if (isNaN(d.getTime())) {
        return 'La fecha no es válida';
    }
    
    return null;
}

/**
 * Valida rango de fechas (próxima >= última)
 */
function validarRangoFechas(fechaUltima, fechaProxima) {
    if (!fechaUltima && !fechaProxima) {
        return null; // Ambas vacías es válido
    }
    
    if (!fechaUltima || !fechaProxima) {
        return null; // Solo una vacía es válido
    }
    
    // Ambas están llenas - validar orden
    if (new Date(fechaProxima) < new Date(fechaUltima)) {
        return 'La Fecha Próxima Calibración no puede ser anterior a la Fecha Última Calibración';
    }
    
    return null;
}

/**
 * Valida un nombre de estado (2-50 caracteres, debe tener letras)
 */
function validarNombreEstado(nombre) {
    nombre = nombre.trim();
    
    if (!nombre) {
        return 'El nombre del estado es obligatorio';
    }
    
    if (nombre.length < 2) {
        return 'El nombre debe tener al menos 2 caracteres';
    }
    
    if (nombre.length > 50) {
        return 'El nombre no puede exceder 50 caracteres';
    }
    
    // Debe contener al menos una letra
    if (!/[a-zA-ZáéíóúñÁÉÍÓÚÑ]/i.test(nombre)) {
        return 'El nombre debe contener al menos una letra (no puede ser solo números)';
    }
    
    return null;
}

/**
 * Valida descripción del estado (opcional, máx 250 caracteres)
 */
function validarDescripcionEstado(descripcion) {
    if (descripcion && descripcion.length > 250) {
        return 'La descripción no puede exceder 250 caracteres';
    }
    return null;
}

/**
 * Muestra errores en el formulario
 */
function mostrarErroresEnFormulario(datos, errores, selector) {
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

function guardarEquipo() {
    const id = $('#Id_Equipo').val();
    const nombre = $('#Nombre').val();
    const proveedor = $('#Proveedor').val();
    const idEstado = $('#Id_Estado').val();
    const fechaUltima = $('#Fecha_Ultima_Calibracion').val();
    const fechaProxima = $('#Fecha_Proxima_Calibracion').val();
    
    // Validaciones en cliente
    const errores = {};
    errores['Nombre'] = validarNombreEquipo(nombre);
    errores['Id_Estado'] = validarEstado(idEstado);
    errores['Proveedor'] = validarProveedor(proveedor);
    errores['Fecha_Ultima_Calibracion'] = validarFecha(fechaUltima);
    errores['Fecha_Proxima_Calibracion'] = validarFecha(fechaProxima);
    errores['fechas'] = validarRangoFechas(fechaUltima, fechaProxima);
    
    // Filtrar errores null
    const erroresFiltrrados = Object.keys(errores)
        .filter(key => errores[key] !== null)
        .reduce((obj, key) => {
            obj[key] = errores[key];
            return obj;
        }, {});
    
    // Si hay errores, mostrarlos
    if (Object.keys(erroresFiltrrados).length > 0) {
        mostrarErroresEnFormulario({}, erroresFiltrrados, '#form-equipo');
        Swal.fire('Errores de validación', 'Por favor revise los campos marcados', 'warning');
        return;
    }
    
    const datos = {
        Id_Equipo: id || null,
        Nombre: nombre,
        Proveedor: proveedor,
        Id_Estado: idEstado,
        Fecha_Ultima_Calibracion: fechaUltima,
        Fecha_Proxima_Calibracion: fechaProxima
    };

    const action = id ? 'actualizar' : 'guardar';
    const url = `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=${action}`;

    $.ajax({
        url: url,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#modal-equipo').modal('hide');
                Swal.fire({
                    title: '¡Guardado!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    tablaEquipos.ajax.reload();
                });
            } else {
                // Mostrar errores del servidor
                if (response.errors) {
                    mostrarErroresEnFormulario(datos, response.errors, '#form-equipo');
                    Swal.fire('Errores de validación', response.message, 'warning');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        },
        error: function(xhr, status, error) {
            const response = xhr.responseJSON || {};
            if (response.errors) {
                mostrarErroresEnFormulario(datos, response.errors, '#form-equipo');
                Swal.fire('Errores de validación', response.message || error, 'warning');
            } else {
                Swal.fire('Error', 'Error al guardar el equipo: ' + error, 'error');
            }
        }
    });
}

function editarEquipo(id) {
    $.ajax({
        url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=obtener&id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const equipo = response.data;
                $('#Id_Equipo').val(equipo.Id_Equipo);
                $('#Nombre').val(equipo.Nombre);
                $('#Proveedor').val(equipo.Proveedor || '');
                $('#Id_Estado').val(equipo.Id_Estado);
                $('#Fecha_Ultima_Calibracion').val(equipo.Fecha_Ultima_Calibracion || '');
                $('#Fecha_Proxima_Calibracion').val(equipo.Fecha_Proxima_Calibracion || '');
                $('#modal-titulo').text('Editar Equipo');
                new bootstrap.Modal(document.getElementById('modal-equipo')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el equipo', 'error');
            }
        }
    });
}

function eliminarEquipo(id) {
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
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=eliminar&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success').then(() => {
                            tablaEquipos.ajax.reload();
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

function reactivarEquipo(id) {
    Swal.fire({
        title: '¿Reactivar equipo?',
        text: 'Este equipo volverá a estar disponible',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=reactivar&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success').then(() => {
                            tablaEquipos.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Error al reactivar el equipo', 'error');
                }
            });
        }
    });
}

function guardarEstado() {
    const id = $('#Id_Estado_Edit').val();
    const nombre = $('#Nombre_Estado').val();
    const descripcion = $('#Descripcion_Estado').val();
    
    // Validaciones en cliente
    const errores = {};
    errores['Nombre'] = validarNombreEstado(nombre);
    errores['Descripcion'] = validarDescripcionEstado(descripcion);
    
    // Filtrar errores null
    const erroresFiltrrados = Object.keys(errores)
        .filter(key => errores[key] !== null)
        .reduce((obj, key) => {
            obj[key] = errores[key];
            return obj;
        }, {});
    
    // Si hay errores, mostrarlos
    if (Object.keys(erroresFiltrrados).length > 0) {
        mostrarErroresEnFormulario({}, erroresFiltrrados, '#form-estado');
        Swal.fire('Errores de validación', 'Por favor revise los campos marcados', 'warning');
        return;
    }
    
    const datos = {
        Id_Estado: id || null,
        Nombre: nombre,
        Descripcion: descripcion
    };

    const action = id ? 'actualizar_estado' : 'guardar_estado';
    
    $.ajax({
        url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=${action}`,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#modal-estado').modal('hide');
                Swal.fire({
                    title: '¡Guardado!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    tablaEstados.ajax.reload();
                    cargarSelectEstados();
                });
            } else {
                // Mostrar errores del servidor
                if (response.errors) {
                    mostrarErroresEnFormulario(datos, response.errors, '#form-estado');
                    Swal.fire('Errores de validación', response.message, 'warning');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        },
        error: function(xhr, status, error) {
            const response = xhr.responseJSON || {};
            if (response.errors) {
                mostrarErroresEnFormulario(datos, response.errors, '#form-estado');
                Swal.fire('Errores de validación', response.message || error, 'warning');
            } else {
                console.error('Error en guardarEstado:', error);
                console.error('Response:', xhr.responseText);
                Swal.fire('Error', 'Error al guardar el estado: ' + error, 'error');
            }
        }
    });
}

function editarEstado(id) {
    $.ajax({
        url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=obtener_estado&id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const estado = response.data;
                $('#Id_Estado_Edit').val(estado.Id_Estado);
                $('#Nombre_Estado').val(estado.Nombre);
                $('#Descripcion_Estado').val(estado.Descripcion || '');
                $('#modal-estado-titulo').text('Editar Estado');
                new bootstrap.Modal(document.getElementById('modal-estado')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el estado', 'error');
            }
        }
    });
}

function eliminarEstado(id) {
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
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=eliminar_estado&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success').then(() => {
                            tablaEstados.ajax.reload();
                            cargarSelectEstados();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    let mensaje = 'Error al eliminar';
                    if (xhr.status === 409) {
                        mensaje = xhr.responseJSON?.message || 'No se puede desactivar este estado porque está en uso por equipos activos.';
                        Swal.fire('No permitido', mensaje, 'warning');
                    } else {
                        Swal.fire('Error', mensaje, 'error');
                    }
                }
            });
        }
    });
}

function reactivarEstado(id) {
    Swal.fire({
        title: '¿Reactivar estado?',
        text: 'Este estado volverá a estar disponible',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=reactivar_estado&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success').then(() => {
                            tablaEstados.ajax.reload();
                            cargarSelectEstados();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Error al reactivar el estado', 'error');
                }
            });
        }
    });
}
</script>
