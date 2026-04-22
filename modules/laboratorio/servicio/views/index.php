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
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-servicio">
          <i class="ti ti-plus me-2"></i> Nuevo Servicio
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
<div class="modal modal-blur fade" id="modal-servicio" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-titulo">Nuevo Servicio</h5>
      </div>
      <div class="modal-body">
        <form id="form-servicio">
          <input type="hidden" id="Id_Servicio" name="Id_Servicio">
          
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="Nombre" name="Nombre" placeholder="Nombre del servicio" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="2" placeholder="Descripción del servicio"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipo de Muestra <span class="text-danger">*</span></label>
            <select class="form-control" id="Tipo_Muestra" name="Tipo_Muestra" required>
              <option value="">Seleccionar tipo...</option>
              <option value="AGUA">AGUA</option>
              <option value="SUELO">SUELO</option>
            </select>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="Requiere_Reactivos" name="Requiere_Reactivos" value="1" checked>
              <label class="form-check-label" for="Requiere_Reactivos">
                Este servicio <strong>requiere reactivos</strong>
              </label>
            </div>
            <small class="text-muted d-block mt-2">Desmarque si es un servicio que no consume reactivos (ej: Conductividad Eléctrica)</small>
          </div>

          <!-- EQUIPOS -->
          <div class="mb-3">
            <label class="form-label">Equipos Requeridos</label>
            <select class="form-control" id="select-equipo">
              <option value="">Seleccionar equipo...</option>
            </select>
            <small class="text-muted d-block mt-2">Marque si el equipo es <strong>bloqueante</strong> para el servicio</small>
            <div class="table-responsive mt-2">
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

          <!-- REACTIVOS -->
          <div id="seccion-reactivos" class="mb-3">
            <label class="form-label">Reactivos Necesarios</label>
            <select class="form-control" id="select-reactivo">
              <option value="">Seleccionar reactivo...</option>
            </select>
            <small class="text-muted d-block mt-2">Especifique la cantidad necesaria para cada muestra</small>
            <div class="table-responsive mt-2">
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

          <!-- PARÁMETROS -->
          <div class="mb-3">
            <label class="form-label">Parámetros de Análisis</label>
            <select class="form-control" id="select-parametro">
              <option value="">Seleccionar parámetro...</option>
            </select>
            <small class="text-muted d-block mt-2">Los parámetros se asignan automáticamente al servicio</small>
            <div class="table-responsive mt-2">
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
    
    // Event listener para mostrar/ocultar sección de reactivos
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
        "language": { 
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" 
        },
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
                
                // Cargar Requiere_Reactivos y mostrar/ocultar sección
                const requiereReactivos = servicio.Requiere_Reactivos ? true : false;
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
        Swal.fire('Validación', 'Debe agregar al menos un equipo requerido', 'warning');
        return;
    }

    // Validación 3: Mínimo un reactivo (SOLO si el servicio requiere reactivos)
    const requiereReactivos = $('#Requiere_Reactivos').is(':checked');
    if (requiereReactivos && reactivosSeleccionados.length === 0) {
        Swal.fire('Validación', 'Debe agregar al menos un reactivo necesario, o desmarque "Requiere Reactivos"', 'warning');
        return;
    }

    // Validación 4: Reactivos con cantidad > 0
    const reactivosSinCantidad = reactivosSeleccionados.filter(r => !r.Cantidad_Necesaria || r.Cantidad_Necesaria <= 0);
    if (reactivosSinCantidad.length > 0) {
        Swal.fire('Validación', 'Todos los reactivos deben tener una cantidad mayor a 0', 'warning');
        return;
    }

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

    // Determinar acción: si hay Id_Servicio es actualización, sino es nuevo
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
                // Si no es JSON, mostrar el texto plano
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
</script>
