<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        <li class="breadcrumb-item active" aria-current="page">Ventas de Servicios</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col flex-grow-1">
        <h2 class="page-title">VENTA DE SERVICIOS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Configuración del catálogo comercial: Gestión de servicios y paquetes disponibles para la oferta externa.</div>
      </div>
    </div>
    <div class="row g-2">
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-venta">
          <i class="ti ti-plus me-2"></i> Vender Servicios
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
          <strong>Para poner un producto en venta</strong>, defina el costo según su modalidad: si es un Servicio Individual, asigne el precio por ensayo unitario; si es un Paquete, indique el precio total que cubre el conjunto de servicios incluidos.
        </div>
      </div>
    </div>

    <!-- Lista de Ventas -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Lista de Venta de Servicios</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tabla-ventas" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Vista</th>
                <th>Descripción</th>
                <th>Precio</th>
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

<!-- Modal Nuevo Producto de Venta -->
<div class="modal modal-blur fade" id="modal-venta" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-titulo">Nuevo Producto de Venta</h5>
      </div>
      <div class="modal-body">
        <form id="form-venta">
          <input type="hidden" id="Id_Producto" name="Id_Producto">
          
          <div class="mb-3">
            <label class="form-label">Nombre del Servicio <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="Nombre_Comercial" name="Nombre_Comercial" placeholder="Ej: Análisis de pH y Conductividad" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="2" placeholder="Descripción del servicio/paquete"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipo de Vista <span class="text-danger">*</span></label>
            <select class="form-control" id="Tipo_Vista" name="Tipo_Vista" required>
              <option value="GENERAL">GENERAL (clientes externos + usuarios)</option>
              <option value="INTERNO">INTERNO (solo usuarios internos)</option>
            </select>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                <select class="form-control" id="Tipo" name="Tipo" required>
                  <option value="">Seleccionar tipo...</option>
                  <option value="Individual">Individual</option>
                  <option value="Paquete">Paquete</option>
                </select>
                <small class="text-muted">Individual: 1 servicio | Paquete: 2+ servicios</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Precio S/. <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="Precio_Venta" name="Precio_Venta" step="0.01" placeholder="0.00" required>
              </div>
            </div>
          </div>

          <!-- SERVICIOS -->
          <div class="mb-3">
            <label class="form-label">Servicios Incluidos <span class="text-danger">*</span></label>
            <select class="form-control" id="select-servicio">
              <option value="">Seleccionar servicio...</option>
            </select>
            <small class="text-muted d-block mt-2">
              <strong>Individual:</strong> Agregue exactamente 1 servicio<br>
              <strong>Paquete:</strong> Agregue mínimo 2 servicios
            </small>
            <div class="table-responsive mt-2">
              <table class="table table-sm table-bordered" id="tabla-servicios">
                <thead class="bg-light">
                  <tr>
                    <th style="width:85%;">Servicio</th>
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
        <button type="button" class="btn btn-primary" id="btn-guardar-venta">Crear Venta</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
var tablaVentas;
var serviciosSeleccionados = [];

$(document).ready(function() {
    inicializarDataTable();
    cargarSelectores();

    $('#btn-guardar-venta').click(guardarVenta);
    $('#modal-venta').on('hidden.bs.modal', limpiarFormulario);
    
    // Event listeners para agregar items a las tablas
    $('#select-servicio').change(agregarServicio);
});

function inicializarDataTable() {
    tablaVentas = $('#tabla-ventas').DataTable({
        "processing": true, 
        "serverSide": true,
        "ajax": { 
            "url": "modules/laboratorio/venta/views/data_listado.php", 
            "type": "POST" 
        },
        "columns": [ 
            { "data": 0 },  // No
            { "data": 1 },  // Nombre
            { "data": 2 },  // Tipo
          { "data": 3 },  // Vista
          { "data": 4 },  // Descripción
          { "data": 5 },  // Precio
          { "data": 6 },  // Capacidad Lab
          { "data": 7 },  // Estado
          { "data": 8, "orderable": false }  // Acción
        ],
        "language": { 
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" 
        },
        "order": [[ 0, "desc" ]]
    });
}

function cargarSelectores() {
    // Cargar servicios disponibles
    $.ajax({
        url: 'modules/laboratorio/servicio/controllers/ServicioAPI.php?action=listar',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#select-servicio').html('<option value="">Seleccionar servicio...</option>');
                response.data.forEach(servicio => {
                    $('#select-servicio').append(`<option value="${servicio.Id_Servicio}">${servicio.Nombre}</option>`);
                });
            }
        }
    });
}

function limpiarFormulario() {
    $('#form-venta')[0].reset();
    $('#Id_Producto').val('');
    $('#modal-titulo').text('Nuevo Producto de Venta');
    $('#btn-guardar-venta').text('Crear Venta');
    serviciosSeleccionados = [];
    $('#tabla-servicios tbody').html('');
}

function agregarServicio() {
    const idServicio = $('#select-servicio').val();
    if (!idServicio) return;
    
    const nombreServicio = $('#select-servicio option:selected').text();
    
    // Verificar si ya existe
    if (serviciosSeleccionados.some(s => s.Id_Servicio == idServicio)) {
        Swal.fire('Aviso', 'Este servicio ya fue agregado', 'info');
        $('#select-servicio').val('');
        return;
    }
    
    // Agregar a array
    serviciosSeleccionados.push({
        Id_Servicio: idServicio,
        Nombre: nombreServicio
    });
    
    actualizarTablaServicios();
    $('#select-servicio').val('');
}

function actualizarTablaServicios() {
    const tbody = $('#tabla-servicios tbody');
    tbody.html('');
    
    serviciosSeleccionados.forEach((servicio, index) => {
        tbody.append(`
            <tr>
                <td>${servicio.Nombre}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-ghost-danger" onclick="eliminarServicio(${index})" title="Eliminar">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

function eliminarServicio(index) {
    serviciosSeleccionados.splice(index, 1);
    actualizarTablaServicios();
}

function guardarVenta() {
    const nombre = $('#Nombre_Comercial').val();
    const tipo = $('#Tipo').val();
  const tipoVista = $('#Tipo_Vista').val();
    const precio = parseFloat($('#Precio_Venta').val());
    const idProducto = $('#Id_Producto').val();

    // Validación 1: Campos obligatorios
    if (!nombre || !tipo || !tipoVista) {
      Swal.fire('Validación', 'Por favor complete los campos: Nombre, Tipo y Tipo de Vista', 'warning');
        return;
    }

    // Validación 2: Precio válido
    if (!precio || precio <= 0) {
        Swal.fire('Validación', 'El precio debe ser mayor a 0', 'warning');
        return;
    }

    // Validación 3: Validar cantidad de servicios según tipo
    if (tipo === 'Individual' && serviciosSeleccionados.length !== 1) {
        Swal.fire('Validación', 'Un producto Individual debe contener exactamente 1 servicio', 'warning');
        return;
    }

    if (tipo === 'Paquete' && serviciosSeleccionados.length < 2) {
        Swal.fire('Validación', 'Un paquete debe contener al menos 2 servicios', 'warning');
        return;
    }

    const datos = {
        Id_Producto: idProducto || '',
        Nombre_Comercial: nombre,
        Descripcion: $('#Descripcion').val(),
        Tipo: tipo,
        Tipo_Vista: tipoVista,
        Precio_Venta: precio,
        Servicios: serviciosSeleccionados
    };

    // Determinar acción: si hay Id_Producto es actualización, sino es nuevo
    const action = idProducto ? 'actualizar' : 'guardar';
    const mensaje = idProducto ? 'Producto actualizado correctamente' : 'Producto guardado correctamente';

    $.ajax({
        url: `modules/laboratorio/venta/controllers/VentaAPI.php?action=${action}`,
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(datos),
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', mensaje, 'success').then(() => {
                    $('#modal-venta').modal('hide');
                    limpiarFormulario();
                    tablaVentas.ajax.reload();
                });
            } else {
                Swal.fire('Error', response.message || 'Error al guardar', 'error');
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

function editarVenta(id) {
    $.ajax({
        url: `modules/laboratorio/venta/controllers/VentaAPI.php?action=obtener&id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const venta = response.data;
                const servicios = response.servicios || [];
                
                // Llenar formulario
                $('#Id_Producto').val(venta.Id_Producto);
                $('#Nombre_Comercial').val(venta.Nombre_Comercial);
                $('#Descripcion').val(venta.Descripcion || '');
                $('#Tipo').val(venta.Tipo);
                $('#Tipo_Vista').val(venta.Tipo_Vista || 'GENERAL');
                $('#Precio_Venta').val(venta.Precio_Venta);
                
                // Llenar servicios seleccionados
                serviciosSeleccionados = [];
                servicios.forEach(servicio => {
                    serviciosSeleccionados.push({
                        Id_Servicio: servicio.Id_Servicio,
                        Nombre: servicio.Nombre
                    });
                });
                actualizarTablaServicios();
                
                // Cambiar título y botón, y mostrar modal
                $('#modal-titulo').text('Editar Producto de Venta');
                $('#btn-guardar-venta').text('Actualizar Venta');
                $('#modal-venta').modal('show');
            } else {
                Swal.fire('Error', response.message || 'No se pudo cargar el producto', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error al cargar el producto', 'error');
        }
    });
}

function eliminarVenta(id) {
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
                url: 'modules/laboratorio/venta/controllers/VentaAPI.php?action=eliminar&id=' + id,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Éxito', response.message, 'success');
                        tablaVentas.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}

function reactivarVenta(id) {
    Swal.fire({
        title: '¿Reactivar venta?',
        text: 'El servicio volverá a estar disponible para venta',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/venta/controllers/VentaAPI.php?action=reactivar&id=' + id,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Éxito', response.message, 'success');
                        tablaVentas.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>
