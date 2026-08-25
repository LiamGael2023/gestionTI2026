<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Servicios</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col flex-grow-1">
        <h2 class="page-title">SERVICIOS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Servicios de análisis y ensayos disponibles para clientes y proyectos internos.</div>
      </div>
    </div>
    <div class="row g-2">
      <?php if (!empty($permisos['crear'])): ?>
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-servicio">
          <i class="ti ti-plus me-2"></i> Nuevo Servicio
        </button>
      </div>
      <?php endif; ?>
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
          <strong>Gestione aquí</strong> la oferta de servicios del laboratorio. Para activar un servicio, asegúrese de que el parámetro y su normativa estén configurados previamente.
        </div>
      </div>
    </div>

    <!-- Lista de Servicios -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Lista de los Servicios</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tabla-servicios" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Descripción</th>
                                <th>Capacidad Lab</th>
                <th>Estado</th>
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

<!-- Modal Nuevo Servicio -->
<div class="modal modal-blur fade" id="modal-servicio" tabindex="-1" role="dialog" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-titulo">Nuevo Servicio</h5>
      </div>
      <div class="modal-body">
        <form id="form-servicio">
          <input type="hidden" id="Id_Servicio" name="Id_Servicio">

          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label">Nombre <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="Nombre" name="Nombre" placeholder="Nombre del servicio" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipo de Muestra <span class="text-danger">*</span></label>
              <select class="form-control" id="Tipo_Muestra" name="Tipo_Muestra" required>
                <option value="">Seleccionar tipo...</option>
                <option value="AGUA">AGUA</option>
                <option value="SUELO">SUELO</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" id="Descripcion" name="Descripcion" rows="2" placeholder="Descripción del servicio"></textarea>
            </div>
          </div>

          <!-- Tabs: Equipos / Reactivos / Parámetros -->
          <ul class="nav nav-tabs" id="servicio-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-equipos-btn" data-bs-toggle="tab" data-bs-target="#tab-equipos" type="button" role="tab">
                <i class="ti ti-tool me-1"></i> Equipos
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-reactivos-btn" data-bs-toggle="tab" data-bs-target="#tab-reactivos" type="button" role="tab">
                <i class="ti ti-flask me-1"></i> Reactivos
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-parametros-btn" data-bs-toggle="tab" data-bs-target="#tab-parametros" type="button" role="tab">
                <i class="ti ti-chart-bar me-1"></i> Parámetros de Análisis
                <span id="badge-parametros" class="badge bg-danger ms-1 d-none">!</span>
              </button>
            </li>
          </ul>

          <div class="tab-content border border-top-0 rounded-bottom p-3" id="servicio-tabs-content">

            <!-- TAB EQUIPOS -->
            <div class="tab-pane fade show active" id="tab-equipos" role="tabpanel">
              <div class="d-flex gap-2 align-items-end mb-2">
                <div class="flex-grow-1">
                  <label class="form-label mb-1">Seleccionar Equipo</label>
                  <select class="form-control" id="select-equipo">
                    <option value="">Seleccionar equipo...</option>
                  </select>
                </div>
                <button type="button" class="btn btn-outline-success btn-sm" style="white-space:nowrap;" onclick="abrirCrearEquipoRapido()">
                  <i class="ti ti-plus me-1"></i> Nuevo
                </button>
              </div>
              <small class="text-muted d-block mb-2">Marque si el equipo es <strong>bloqueante</strong> para el servicio</small>
              <div class="table-responsive">
                <table class="table table-sm table-bordered" id="tabla-equipos">
                  <thead class="bg-light">
                    <tr>
                      <th style="width:60%;">Equipo</th>
                      <th style="width:25%;" class="text-center">¿Bloqueante?</th>
                      <th style="width:15%;" class="text-center">Acción</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>

            <!-- TAB REACTIVOS -->
            <div class="tab-pane fade" id="tab-reactivos" role="tabpanel">
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="Requiere_Reactivos" name="Requiere_Reactivos" value="1" checked>
                <label class="form-check-label" for="Requiere_Reactivos">
                  Este servicio <strong>requiere reactivos</strong>
                </label>
                <small class="text-muted d-block mt-1">Desmarque si es un servicio que no consume reactivos (ej: Conductividad Eléctrica)</small>
              </div>
              <div id="seccion-reactivos">
                <div class="d-flex gap-2 align-items-end mb-2">
                  <div class="flex-grow-1">
                    <label class="form-label mb-1">Seleccionar Reactivo</label>
                    <select class="form-control" id="select-reactivo">
                      <option value="">Seleccionar reactivo...</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-outline-success btn-sm" style="white-space:nowrap;" onclick="abrirCrearReactivoRapido()">
                    <i class="ti ti-plus me-1"></i> Nuevo
                  </button>
                </div>
                <small class="text-muted d-block mb-2">Especifique la cantidad necesaria para cada muestra</small>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered" id="tabla-reactivos">
                    <thead class="bg-light">
                      <tr>
                        <th style="width:60%;">Reactivo</th>
                        <th style="width:25%;" class="text-center">Cantidad</th>
                        <th style="width:15%;" class="text-center">Acción</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- TAB PARÁMETROS -->
            <div class="tab-pane fade" id="tab-parametros" role="tabpanel">
              <div id="alerta-sin-parametros" class="alert alert-warning py-2 px-3 mb-3 d-none">
                <i class="ti ti-alert-triangle me-1"></i>
                <strong>Atención:</strong> No se ha asignado ningún parámetro. Al menos un parámetro es necesario para generar resultados de análisis.
              </div>
              <div class="d-flex gap-2 align-items-end mb-2">
                <div class="flex-grow-1">
                  <label class="form-label mb-1">Seleccionar Parámetro</label>
                  <select class="form-control" id="select-parametro">
                    <option value="">Seleccionar parámetro...</option>
                  </select>
                </div>
                <button type="button" class="btn btn-outline-success btn-sm" style="white-space:nowrap;" onclick="abrirCrearParametroRapido()">
                  <i class="ti ti-plus me-1"></i> Nuevo
                </button>
              </div>
              <small class="text-muted d-block mb-2">Los parámetros se asignan automáticamente al servicio</small>
              <div class="table-responsive">
                <table class="table table-sm table-bordered" id="tabla-parametros">
                  <thead class="bg-light">
                    <tr>
                      <th style="width:85%;">Parámetro</th>
                      <th style="width:15%;" class="text-center">Acción</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>

          </div><!-- /tab-content -->
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-servicio">Crear Servicio</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tablaServicios;
var equiposSeleccionados = [];
var reactivosSeleccionados = [];
var parametrosSeleccionados = [];

$(document).ready(function() {
    inicializarDataTable();
    cargarSelectores();

    $('#btn-guardar-servicio').click(guardarServicio);
    $('#modal-servicio').on('hidden.bs.modal', limpiarFormulario);
    
    // Requiere_Reactivos: toggle seccion-reactivos
    $('#Requiere_Reactivos').change(function() {
        if ($(this).is(':checked')) {
            $('#seccion-reactivos').show();
        } else {
            $('#seccion-reactivos').hide();
            reactivosSeleccionados = [];
            $('#tabla-reactivos tbody').html('');
        }
    });
    
    // Event listeners para agregar items a las tablas
    $('#select-equipo').change(agregarEquipo);
    $('#select-reactivo').change(agregarReactivo);
    $('#select-parametro').change(agregarParametro);
});

function inicializarDataTable() {
    tablaServicios = $('#tabla-servicios').DataTable({
        "processing": true, 
        "serverSide": true,
        "ajax": { 
            "url": "modules/laboratorio/servicio/views/data_listado.php", 
            "type": "POST" 
        },
        "columns": [ 
            { "data": 0 },  // No
            { "data": 1 },  // Nombre
            { "data": 2 },  // Tipo
            { "data": 3 },  // Descripción
            { "data": 4 },  // Capacidad Lab
            { "data": 5 },  // Estado
            { "data": 6, "orderable": false }  // Acción
        ],
        "language": { "sProcessing": "Procesando...", "sLengthMenu": "Mostrar _MENU_ registros", "sZeroRecords": "No se encontraron resultados", "sEmptyTable": "No hay datos disponibles", "sInfo": "Mostrando del _START_ al _END_ de _TOTAL_ registros", "sInfoEmpty": "Mostrando 0 registros", "sInfoFiltered": "(filtrado de _MAX_ total)", "sSearch": "Buscar:", "sLoadingRecords": "Cargando...", "oPaginate": { "sFirst": "Primero", "sLast": "\u00DAltimo", "sNext": "Siguiente", "sPrevious": "Anterior" } },
        "order": [[ 0, "desc" ]]
    });
}

function cargarSelectores() {
    // Cargar equipos
    $.ajax({
        url: 'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=listar',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#select-equipo').html('<option value="">Seleccionar equipo...</option>');
                response.data.forEach(equipo => {
                    $('#select-equipo').append(`<option value="${equipo.Id_Equipo}">${equipo.Nombre}</option>`);
                });
            }
        }
    });

    // Cargar reactivos
    $.ajax({
        url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=listar',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#select-reactivo').html('<option value="">Seleccionar reactivo...</option>');
                response.data.forEach(reactivo => {
                    $('#select-reactivo').append(`<option value="${reactivo.Id_Reactivo}">${reactivo.Nombre}</option>`);
                });
            }
        }
    });

    // Cargar parámetros
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=listar',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#select-parametro').html('<option value="">Seleccionar parámetro...</option>');
                response.data.forEach(parametro => {
                    // Aceptar diferentes nombres de campo para el nombre del parámetro
                    const nombreParam = parametro.Nombre || parametro.Descripcion || `Parámetro ${parametro.Id_Parametro}`;
                    $('#select-parametro').append(`<option value="${parametro.Id_Parametro}">${nombreParam}</option>`);
                });
            }
        }
    });
}

function cargarParametrosDisponibles(idServicio) {
    // Cargar parámetros disponibles para un servicio específico
    // Incluye parámetros genéricos + los ya asignados a este servicio
    $.ajax({
        url: `modules/laboratorio/parametro/controllers/ParametroAPI.php?action=listarDisponibles&idServicio=${idServicio}`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#select-parametro').html('<option value="">Seleccionar parámetro...</option>');
                response.data.forEach(parametro => {
                    // Aceptar diferentes nombres de campo para el nombre del parámetro
                    const nombreParam = parametro.Nombre || parametro.Descripcion || `Parámetro ${parametro.Id_Parametro}`;
                    // Deshabilitar parámetros que ya están en la tabla actual
                    const yaSeleccionado = parametrosSeleccionados.some(p => p.Id_Parametro == parametro.Id_Parametro);
                    const disabled = !yaSeleccionado && parametro.Id_Servicio && parametro.Id_Servicio > 0 ? 'disabled' : '';
                    $('#select-parametro').append(`<option value="${parametro.Id_Parametro}" ${disabled}>${nombreParam}${disabled ? ' (No disponible)' : ''}</option>`);
                });
            }
        }
    });
}

function limpiarFormulario() {
    $('#form-servicio')[0].reset();
    $('#Id_Servicio').val('');
    $('#modal-titulo').text('Nuevo Servicio');
    $('#btn-guardar-servicio').text('Crear Servicio');
    equiposSeleccionados = [];
    reactivosSeleccionados = [];
    parametrosSeleccionados = [];
    $('#tabla-equipos tbody').html('');
    $('#tabla-reactivos tbody').html('');
    $('#tabla-parametros tbody').html('');
    
    // Recargar selectores para mostrar parámetros genéricos disponibles
    cargarSelectores();
}

function editarServicio(id) {
    $.ajax({
        url: `modules/laboratorio/servicio/controllers/ServicioAPI.php?action=obtener&id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const servicio = response.data;
                const equipos = response.equipos || [];
                const reactivos = response.reactivos || [];
                const parametros = response.parametros || [];
                
                // Llenar formulario
                $('#Id_Servicio').val(servicio.Id_Servicio);
                $('#Nombre').val(servicio.Nombre);
                $('#Descripcion').val(servicio.Descripcion || '');
                $('#Tipo_Muestra').val(servicio.Tipo_Muestra);
                
                // Cargar Requiere_Reactivos y mostrar/ocultar sección.
                // El check se marca si la bandera viene en true O si el servicio
                // tiene reactivos ligados (Receta_Servicio), aunque la bandera
                // venga en 0/falta (columna ausente en BD).
                const requiereReactivos = (servicio.Requiere_Reactivos ? true : false) || reactivos.length > 0;
                $('#Requiere_Reactivos').prop('checked', requiereReactivos);
                if (requiereReactivos) {
                    $('#seccion-reactivos').show();
                } else {
                    $('#seccion-reactivos').hide();
                }
                
                // Llenar equipos
                equiposSeleccionados = [];
                equipos.forEach(equipo => {
                    equiposSeleccionados.push({
                        Id_Equipo: equipo.Id_Equipo,
                        Nombre: equipo.Equipo_Nombre,
                        Es_Bloqueante: equipo.Es_Bloqueante
                    });
                });
                actualizarTablaEquipos();
                
                // Llenar reactivos
                reactivosSeleccionados = [];
                reactivos.forEach(reactivo => {
                    reactivosSeleccionados.push({
                        Id_Reactivo: reactivo.Id_Reactivo,
                        Nombre: reactivo.Reactivo_Nombre,
                        Cantidad_Necesaria: reactivo.Cantidad_Necesaria
                    });
                });
                actualizarTablaReactivos();
                
                // Llenar parámetros
                parametrosSeleccionados = [];
                parametros.forEach(parametro => {
                    // Aceptar diferentes nombres de campo para el nombre del parámetro
                    const nombreParam = parametro.Nombre || parametro.Descripcion || parametro.Nombre_Parametro || `Parámetro ${parametro.Id_Parametro}`;
                    parametrosSeleccionados.push({
                        Id_Parametro: parametro.Id_Parametro,
                        Nombre: nombreParam
                    });
                });
                actualizarTablaParametros();
                
                // Cargar parámetros disponibles específicos para este servicio
                cargarParametrosDisponibles(servicio.Id_Servicio);
                
                // Cambiar título y botón, y mostrar modal
                $('#modal-titulo').text('Editar Servicio');
                $('#btn-guardar-servicio').text('Actualizar Servicio');
                $('#modal-servicio').modal('show');
            } else {
                Swal.fire('Error', response.message || 'No se pudo cargar el servicio', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error al cargar el servicio', 'error');
        }
    });
}

function eliminarServicio(id) {
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
                url: `modules/laboratorio/servicio/controllers/ServicioAPI.php?action=eliminar&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success').then(() => {
                            tablaServicios.ajax.reload();
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

function reactivarServicio(id) {
    Swal.fire({
        title: '¿Reactivar servicio?',
        text: 'Este servicio volverá a estar disponible',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/servicio/controllers/ServicioAPI.php?action=reactivar&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success').then(() => {
                            tablaServicios.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error al reactivar el servicio', 'error');
                }
            });
        }
    });
}

// ==================== FUNCIONES PARA EQUIPOS ====================
function agregarEquipo() {
    const idEquipo = $('#select-equipo').val();
    if (!idEquipo) return;
    
    const nombreEquipo = $('#select-equipo option:selected').text();
    
    // Verificar si ya existe
    if (equiposSeleccionados.some(e => e.Id_Equipo == idEquipo)) {
        Swal.fire('Aviso', 'Este equipo ya fue agregado', 'info');
        $('#select-equipo').val('');
        return;
    }
    
    // Agregar a array
    equiposSeleccionados.push({
        Id_Equipo: idEquipo,
        Nombre: nombreEquipo,
        Es_Bloqueante: 1
    });
    
    actualizarTablaEquipos();
    $('#select-equipo').val('');
}

function actualizarTablaEquipos() {
    const tbody = $('#tabla-equipos tbody');
    tbody.html('');
    
    equiposSeleccionados.forEach((equipo, index) => {
        const checked = equipo.Es_Bloqueante ? 'checked' : '';
        tbody.append(`
            <tr>
                <td>${equipo.Nombre}</td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" ${checked} 
                           onchange="cambiarBloqueante(${index}, this.checked)">
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-ghost-danger" onclick="eliminarEquipo(${index})" title="Eliminar">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

function cambiarBloqueante(index, checked) {
    equiposSeleccionados[index].Es_Bloqueante = checked ? 1 : 0;
}

function eliminarEquipo(index) {
    equiposSeleccionados.splice(index, 1);
    actualizarTablaEquipos();
}

// ==================== FUNCIONES PARA REACTIVOS ====================
function agregarReactivo() {
    const idReactivo = $('#select-reactivo').val();
    if (!idReactivo) return;
    
    const nombreReactivo = $('#select-reactivo option:selected').text();
    
    // Verificar si ya existe
    if (reactivosSeleccionados.some(r => r.Id_Reactivo == idReactivo)) {
        Swal.fire('Aviso', 'Este reactivo ya fue agregado', 'info');
        $('#select-reactivo').val('');
        return;
    }
    
    // Agregar a array
    reactivosSeleccionados.push({
        Id_Reactivo: idReactivo,
        Nombre: nombreReactivo,
        Cantidad_Necesaria: 0
    });
    
    actualizarTablaReactivos();
    $('#select-reactivo').val('');
}

function actualizarTablaReactivos() {
    const tbody = $('#tabla-reactivos tbody');
    tbody.html('');
    
    reactivosSeleccionados.forEach((reactivo, index) => {
        tbody.append(`
            <tr>
                <td>${reactivo.Nombre}</td>
                <td>
                    <input type="number" id="cantidad-reactivo-${index}" class="form-control form-control-sm" min="0.01" step="0.01" 
                           placeholder="Cantidad" value="${reactivo.Cantidad_Necesaria}" 
                           onchange="cambiarCantidadReactivo(${index}, this.value)">
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-ghost-danger" onclick="eliminarReactivo(${index})" title="Eliminar">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

function cambiarCantidadReactivo(index, cantidad) {
    const cantidadNum = parseFloat(cantidad);
    
    if (isNaN(cantidadNum) || cantidadNum <= 0) {
        Swal.fire('Validación', 'La cantidad debe ser mayor a 0', 'warning');
        // Limpiar el campo
        $(`#cantidad-reactivo-${index}`).val('');
        reactivosSeleccionados[index].Cantidad_Necesaria = 0;
        return;
    }
    
    reactivosSeleccionados[index].Cantidad_Necesaria = cantidadNum;
}

function eliminarReactivo(index) {
    reactivosSeleccionados.splice(index, 1);
    actualizarTablaReactivos();
}

// ==================== FUNCIONES PARA PARÁMETROS ====================
function agregarParametro() {
    const idParametro = $('#select-parametro').val();
    if (!idParametro) return;
    
    let nombreParametro = $('#select-parametro option:selected').text();
    // Limpiar texto "(No disponible)" si existe
    nombreParametro = nombreParametro.replace(' (No disponible)', '');
    
    // Verificar si ya existe
    if (parametrosSeleccionados.some(p => p.Id_Parametro == idParametro)) {
        Swal.fire('Aviso', 'Este parámetro ya fue agregado', 'info');
        $('#select-parametro').val('');
        return;
    }
    
    // Agregar a array
    parametrosSeleccionados.push({
        Id_Parametro: idParametro,
        Nombre: nombreParametro
    });
    
    actualizarTablaParametros();
    $('#select-parametro').val('');
}

function actualizarTablaParametros() {
    const tbody = $('#tabla-parametros tbody');
    tbody.html('');
    
    parametrosSeleccionados.forEach((parametro, index) => {
        tbody.append(`
            <tr>
                <td>${parametro.Nombre}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-ghost-danger" onclick="eliminarParametro(${index})" title="Eliminar">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });

    // Show/hide badge and warning alert
    const sinParametros = parametrosSeleccionados.length === 0;
    $('#badge-parametros').toggleClass('d-none', !sinParametros);
    $('#alerta-sin-parametros').toggleClass('d-none', !sinParametros);
}

function eliminarParametro(index) {
    parametrosSeleccionados.splice(index, 1);
    actualizarTablaParametros();
}

function guardarServicio() {
    const nombre = $('#Nombre').val();
    const tipoMuestra = $('#Tipo_Muestra').val();
    const idServicio = $('#Id_Servicio').val();

    // Validación 1: Campos obligatorios
    if (!nombre || !tipoMuestra) {
        Swal.fire('Validación', 'Por favor complete los campos: Nombre y Tipo de Muestra', 'warning');
        return;
    }

    // Validación 2: Mínimo un equipo
    if (equiposSeleccionados.length === 0) {
        $('#tab-equipos-btn').tab('show');
        Swal.fire('Validación', 'Debe agregar al menos un equipo requerido', 'warning');
        return;
    }

    // Validación 3: Mínimo un reactivo (SOLO si el servicio requiere reactivos)
    const requiereReactivos = $('#Requiere_Reactivos').is(':checked');
    if (requiereReactivos && reactivosSeleccionados.length === 0) {
        $('#tab-reactivos-btn').tab('show');
        Swal.fire('Validación', 'Debe agregar al menos un reactivo necesario, o desmarque "Requiere Reactivos"', 'warning');
        return;
    }

    // Validación 4: Reactivos con cantidad > 0
    const reactivosSinCantidad = reactivosSeleccionados.filter(r => !r.Cantidad_Necesaria || r.Cantidad_Necesaria <= 0);
    if (reactivosSinCantidad.length > 0) {
        $('#tab-reactivos-btn').tab('show');
        Swal.fire('Validación', 'Todos los reactivos deben tener una cantidad mayor a 0', 'warning');
        return;
    }

    // Advertencia (no bloqueante): sin parámetros
    if (parametrosSeleccionados.length === 0) {
        $('#tab-parametros-btn').tab('show');
        Swal.fire({
            title: 'Sin parámetros asignados',
            text: 'Este servicio no tiene parámetros de análisis. Sin parámetros no se generarán resultados. ¿Desea continuar de todas formas?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Guardar igual',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) _ejecutarGuardarServicio();
        });
        return;
    }

    _ejecutarGuardarServicio();
}

function _ejecutarGuardarServicio() {
    const nombre = $('#Nombre').val();
    const tipoMuestra = $('#Tipo_Muestra').val();
    const idServicio = $('#Id_Servicio').val();
    const requiereReactivos = $('#Requiere_Reactivos').is(':checked');

    const datos = {
        Id_Servicio: idServicio || '',
        Nombre: nombre,
        Descripcion: $('#Descripcion').val(),
        Tipo_Muestra: tipoMuestra,
        Requiere_Reactivos: requiereReactivos ? 1 : 0,
        Equipos: equiposSeleccionados,
        Reactivos: reactivosSeleccionados,
        Parametros: parametrosSeleccionados
    };

    const action = idServicio ? 'actualizar' : 'guardar';
    const mensaje = idServicio ? 'Servicio actualizado correctamente' : 'Servicio creado correctamente';

    $.ajax({
        url: `modules/laboratorio/servicio/controllers/ServicioAPI.php?action=${action}`,
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(datos),
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', mensaje, 'success').then(() => {
                    $('#modal-servicio').modal('hide');
                    limpiarFormulario();
                    tablaServicios.ajax.reload();
                });
            } else {
                Swal.fire('Error', response.message || 'Error al guardar el servicio', 'error');
            }
        },
        error: function(xhr, status, error) {
            let mensaje = 'Error en la conexión';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    mensaje = parsed.message || xhr.responseText;
                } catch (e) {
                    mensaje = xhr.responseText;
                }
            }
            Swal.fire('Error', mensaje, 'error');
        }
    });
}

// ==================== CREACIÓN RÁPIDA INLINE ====================

function abrirCrearEquipoRapido() {
    Swal.fire({
        title: 'Nuevo Equipo',
        html: `
            <div class="text-start">
                <div class="mb-2">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input id="swal-eq-nombre" class="form-control" placeholder="Nombre del equipo">
                </div>
                <div class="mb-2">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select id="swal-eq-estado" class="form-select">
                        <option value="">-- Seleccione --</option>
                    </select>
                    <small class="text-muted">El estado es obligatorio (validación de la API)</small>
                </div>
                <div class="mb-2">
                    <label class="form-label">Descripción</label>
                    <textarea id="swal-eq-desc" class="form-control" rows="2" placeholder="Descripción (opcional)"></textarea>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Crear Equipo',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            // Cargar estados de equipo desde la BD
            $.ajax({
                url: 'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=listar_estados',
                method: 'GET', dataType: 'json',
                success: function(resp) {
                    if (resp.success && resp.data) {
                        var sel = document.getElementById('swal-eq-estado');
                        sel.innerHTML = '<option value="">-- Seleccione --</option>';
                        resp.data.forEach(function(es) {
                            var opt = document.createElement('option');
                            opt.value = es.Id_Estado;
                            opt.textContent = es.Nombre;
                            sel.appendChild(opt);
                        });
                    }
                }
            });
        },
        preConfirm: () => {
            const nombre = document.getElementById('swal-eq-nombre').value.trim();
            const estado = document.getElementById('swal-eq-estado').value;
            if (!nombre) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }
            if (!estado) { Swal.showValidationMessage('Seleccione el estado del equipo'); return false; }
            return { Nombre: nombre, Descripcion: document.getElementById('swal-eq-desc').value.trim(), Id_Estado: estado };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=guardar',
            method: 'POST', contentType: 'application/json', dataType: 'json',
            data: JSON.stringify(result.value),
            success: function(resp) {
                if (resp.success) {
                    const id = resp.id || resp.data?.Id_Equipo;
                    const nombre = result.value.Nombre;
                    $('#select-equipo').append(`<option value="${id}">${nombre}</option>`).val(id);
                    agregarEquipo();
                    Swal.fire({ title: 'Creado', text: nombre + ' agregado', icon: 'success', timer: 1200, showConfirmButton: false });
                } else { Swal.fire('Error', resp.message || 'No se pudo crear el equipo', 'error'); }
            },
            error: function() { Swal.fire('Error', 'Error al crear el equipo', 'error'); }
        });
    });
}

function abrirCrearReactivoRapido() {
    Swal.fire({
        title: 'Nuevo Reactivo',
        html: `
            <div class="text-start">
                <div class="mb-2">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input id="swal-re-nombre" class="form-control" placeholder="Nombre del reactivo">
                </div>
                <div class="mb-2">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select id="swal-re-tipo" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <option value="Agua">Agua</option>
                        <option value="Suelo">Suelo</option>
                    </select>
                    <small class="text-muted">Solo Agua o Suelo (validación de la API)</small>
                </div>
                <div class="mb-2">
                    <label class="form-label">Unidad de Medida</label>
                    <select id="swal-re-unidad" class="form-select">
                        <option value="">-- Seleccione --</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Cantidad Inicial <span class="text-danger">*</span></label>
                    <input id="swal-re-cantidad" type="number" step="0.01" min="0.01" class="form-control" placeholder="Ej: 100">
                    <small class="text-muted">Aparecerá como ingreso inicial en el kardex</small>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Crear Reactivo',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            // Cargar unidades de medida desde la BD (igual que el modal de Reactivos)
            $.ajax({
                url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=listar_unidades',
                method: 'GET', dataType: 'json',
                success: function(resp) {
                    if (resp.success && resp.data) {
                        var sel = document.getElementById('swal-re-unidad');
                        sel.innerHTML = '<option value="">-- Seleccione --</option>';
                        resp.data.forEach(function(u) {
                            var opt = document.createElement('option');
                            opt.value = u.Id_Unidad_Medida;
                            opt.textContent = u.Nombre + ' (' + u.Abreviatura + ')';
                            sel.appendChild(opt);
                        });
                    }
                }
            });
        },
        preConfirm: () => {
            const nombre = document.getElementById('swal-re-nombre').value.trim();
            const tipo = document.getElementById('swal-re-tipo').value;
            const cantidad = document.getElementById('swal-re-cantidad').value;
            const unidad = document.getElementById('swal-re-unidad').value;
            if (!nombre) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }
            if (!tipo) { Swal.showValidationMessage('Seleccione el tipo (Agua o Suelo)'); return false; }
            if (!cantidad || parseFloat(cantidad) <= 0) { Swal.showValidationMessage('La cantidad inicial es obligatoria y debe ser mayor a 0'); return false; }
            const data = { Nombre: nombre, Tipo: tipo, Cantidad_Inicial: cantidad };
            if (unidad) data.Id_Unidad_Medida = unidad;
            return data;
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=guardar',
            method: 'POST', contentType: 'application/json', dataType: 'json',
            data: JSON.stringify(result.value),
            success: function(resp) {
                if (resp.success) {
                    const id = resp.id || resp.data?.Id_Reactivo;
                    const nombre = result.value.Nombre;
                    $('#select-reactivo').append(`<option value="${id}">${nombre}</option>`).val(id);
                    agregarReactivo();
                    Swal.fire({ title: 'Creado', text: nombre + ' agregado', icon: 'success', timer: 1200, showConfirmButton: false });
                } else { Swal.fire('Error', resp.message || 'No se pudo crear el reactivo', 'error'); }
            },
            error: function() { Swal.fire('Error', 'Error al crear el reactivo', 'error'); }
        });
    });
}

function abrirCrearParametroRapido(preValues) {
    preValues = preValues || {};

    Swal.fire({
        title: 'Nuevo Parámetro',
        html: `
            <div class="text-start">
                <div class="mb-2">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input id="swal-pa-nombre" class="form-control" placeholder="Nombre del parámetro" value="${preValues.Nombre || ''}">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label">Unidad de Medida</label>
                        <div class="input-group input-group-sm">
                            <select id="swal-pa-unidad" class="form-select">
                                <option value="">Cargando...</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="swal-pa-nueva-unidad" title="Nueva unidad de medida"><i class="ti ti-plus"></i></button>
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Categoría</label>
                        <select id="swal-pa-categoria" class="form-select">
                            <option value="">-- Seleccionar --</option>
                            <option value="Fisico" ${preValues.Categoria === 'Fisico' ? 'selected' : ''}>Fisico</option>
                            <option value="Quimico" ${preValues.Categoria === 'Quimico' ? 'selected' : ''}>Quimico</option>
                            <option value="Microbiologico" ${preValues.Categoria === 'Microbiologico' ? 'selected' : ''}>Microbiologico</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Descripción</label>
                    <textarea id="swal-pa-desc" class="form-control" rows="2" placeholder="Descripción (opcional)">${preValues.Descripcion || ''}</textarea>
                </div>
            </div>`,
        didOpen: () => {
            // Cargar unidades desde la BD
            $.ajax({
                url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=listar_unidades',
                method: 'GET', dataType: 'json',
                success: function(resp) {
                    if (resp.success) {
                        var sel = document.getElementById('swal-pa-unidad');
                        sel.innerHTML = '<option value="">-- Seleccionar --</option>';
                        resp.data.forEach(function(u) {
                            var opt = document.createElement('option');
                            opt.value = u.Abreviatura;
                            opt.textContent = u.Nombre + ' (' + u.Abreviatura + ')';
                            if (preValues.Unidad_Medida && u.Abreviatura === preValues.Unidad_Medida) {
                                opt.selected = true;
                            }
                            sel.appendChild(opt);
                        });
                    }
                },
                error: function() {
                    var sel = document.getElementById('swal-pa-unidad');
                    sel.innerHTML = '<option value="">Error al cargar</option>';
                }
            });
            // Botón nueva unidad
            document.getElementById('swal-pa-nueva-unidad').addEventListener('click', function() {
                var savedValues = {
                    Nombre:      document.getElementById('swal-pa-nombre').value,
                    Categoria:   document.getElementById('swal-pa-categoria').value,
                    Descripcion: document.getElementById('swal-pa-desc').value
                };
                Swal.close();
                setTimeout(function() {
                    Swal.fire({
                        title: 'Nueva Unidad de Medida',
                        html: `
                            <div class="text-start">
                                <div class="mb-2">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input id="swal-um-nombre" class="form-control" placeholder="Ej: Mililitro">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Abreviatura <span class="text-danger">*</span></label>
                                    <input id="swal-um-abrev" class="form-control" placeholder="Ej: mL">
                                </div>
                            </div>`,
                        showCancelButton: true,
                        confirmButtonText: 'Crear Unidad',
                        cancelButtonText: 'Volver',
                        preConfirm: () => {
                            const n = document.getElementById('swal-um-nombre').value.trim();
                            const a = document.getElementById('swal-um-abrev').value.trim();
                            if (!n) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }
                            if (!a) { Swal.showValidationMessage('La abreviatura es obligatoria'); return false; }
                            return { Nombre: n, Abreviatura: a };
                        }
                    }).then(function(res) {
                        if (!res.isConfirmed) {
                            abrirCrearParametroRapido(savedValues);
                            return;
                        }
                        $.ajax({
                            url: 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php?action=guardar_unidad',
                            method: 'POST', contentType: 'application/json', dataType: 'json',
                            data: JSON.stringify(res.value),
                            success: function(r) {
                                if (r.success) {
                                    savedValues.Unidad_Medida = r.unidad.Abreviatura;
                                    abrirCrearParametroRapido(savedValues);
                                } else {
                                    Swal.fire('Error', r.message, 'error').then(function() {
                                        abrirCrearParametroRapido(savedValues);
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Error al crear la unidad', 'error').then(function() {
                                    abrirCrearParametroRapido(savedValues);
                                });
                            }
                        });
                    });
                }, 300);
            });
        },
        showCancelButton: true,
        confirmButtonText: 'Crear Parámetro',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nombre = document.getElementById('swal-pa-nombre').value.trim();
            if (!nombre) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }
            return {
                Nombre: nombre,
                Unidad_Medida: document.getElementById('swal-pa-unidad').value || null,
                Categoria: document.getElementById('swal-pa-categoria').value || null,
                Descripcion: document.getElementById('swal-pa-desc').value.trim()
            };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=guardar',
            method: 'POST', contentType: 'application/json', dataType: 'json',
            data: JSON.stringify(result.value),
            success: function(resp) {
                if (resp.success) {
                    const id = resp.id || resp.data?.Id_Parametro;
                    const nombre = result.value.Nombre;
                    $('#select-parametro').append(`<option value="${id}">${nombre}</option>`).val(id);
                    agregarParametro();
                    Swal.fire({ title: 'Creado', text: nombre + ' agregado', icon: 'success', timer: 1200, showConfirmButton: false });
                } else { Swal.fire('Error', resp.message || 'No se pudo crear el parámetro', 'error'); }
            },
            error: function() { Swal.fire('Error', 'Error al crear el parámetro', 'error'); }
        });
    });
}
</script>
