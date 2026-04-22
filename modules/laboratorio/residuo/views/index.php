<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';

Auth::check();

$conn = Conexion::conectar();
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
    body { background-color: #f5f7fb; font-size: 14px; }
    .text-muted { color: #6c757d; }
    .text-muted.mt-1 { margin-top: 0.5rem; font-size: 0.95em; }
    .alert-info {
        background-color: #e8f4f8;
        border-left: 4px solid #17a2b8;
    }
    .alert-warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }
    .badge { font-size: 0.85em; padding: 0.5em 0.75em; }
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
</style>
</head>
<body>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Residuos</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">RESIDUOS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Módulo central para el control, clasificación y reporte de desechos</div>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-residuo">
          <i class="ti ti-plus me-2"></i> Crear Residuo
        </button>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-normativa">
          <i class="ti ti-certificate me-2"></i> Crear Normativa SST
        </button>
      </div>
      <div class="col-auto">
        <a href="?module=laboratorio&action=residuo&view=informe_residuos" class="btn btn-danger">
          <i class="ti ti-file-text me-2"></i> Crear Informe de Residuos
        </a>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info" role="alert">
      <div>
        Para iniciar, registre sus desechos en <strong>"Crear Residuo"</strong> vinculándolos a su Servicio específico. Para generar el reporte mensual,
        haga clic en <strong>"Crear Informe de Residuos"</strong> e ingrese el mes, año, ubicación, código SST y versión; el sistema filtrará
        automáticamente todos los registros del inventario correspondientes a ese período y sede para consolidar la información en la
        columna adecuada.
      </div>
    </div>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tab-residuos" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">Inventario de Residuos</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-normativas" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab">Normativas</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active show" id="tab-residuos" role="tabpanel">
                        <p class="text-muted small">Listado consolidado de registros actuales. Utilice el buscador para localizar insumos específicos por nombre o código</p>

                        <div class="table-responsive">
                            <table id="tabla-residuos" class="table table-vcenter card-table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nombre Residuo</th>
                                        <th>Tipo</th>
                                        <th>SubCategoría</th>
                                        <th>U.M.</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="tab-normativas" role="tabpanel">
                        <p class="text-muted small">Listado detallado de las normativas.</p>

                        <div class="table-responsive">
                            <table id="tabla-normativas" class="table table-vcenter card-table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Normativas</th>
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

<!-- Modal: Nuevo Residuo -->
<div class="modal modal-blur fade" id="modal-residuo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Residuo</h5>
      </div>
      <div class="modal-body">
        <form id="form-residuo">
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nombre_residuo" placeholder="Nombre del residuo" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Código <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="codigo_item" placeholder="Ingrese el código" required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Unidad de Medida <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="unidad_referencia" placeholder="U.M." required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo de Residuo <span class="text-danger">*</span></label>
              <select class="form-select" id="tipo_residuo" required onchange="actualizarSubcategorias()">
                <option value="">Seleccionar el residuo</option>
                <option value="PELIGROSO">RESIDUO PELIGROSO</option>
                <option value="NO PELIGROSO">RESIDUO NO PELIGROSO</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">SubCategoría <span class="text-danger">*</span></label>
              <select class="form-select" id="subcategoria" required>
                <option value="">Seleccionar la subcategoría</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Cantidad de Residuo por Servicios</label>
            <select class="form-select" id="select-servicios">
              <option value="">Seleccionar el servicio</option>
            </select>
            <small class="text-muted d-block mt-2">Especifique la cantidad de residuos generados por cada servicio</small>
            <div class="table-responsive mt-2">
              <table class="table table-sm table-bordered" id="tabla-servicios">
                <thead class="bg-light">
                  <tr>
                    <th style="width:60%;">Servicio</th>
                    <th style="width:25%;" class="text-center">Cantidad Aprox</th>
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
        <button type="submit" form="form-residuo" class="btn btn-primary">Crear Residuo</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Nuevo Normativa SST -->
<div class="modal modal-blur fade" id="modal-normativa" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Nueva Normativa SST</h5>
      </div>
      <div class="modal-body">
        <form id="form-normativa">
          <div class="mb-3">
            <label class="form-label">Nombre Ley <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nombre_ley" placeholder="Nombre de la normativa" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Descripción <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="descripcion_ley" placeholder="Ingrese la descripción" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" form="form-normativa" class="btn btn-primary">Crear Normativa</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const apiUrl = '?module=laboratorio&action=residuo';
const usuarioId = <?php echo intval($usuario_id); ?>;

$(document).ready(function() {
    // Inicializar tablas
    inicializarTablaResiduos();
    inicializarTablaNormativas();
    cargarServicios();
    cargarNormativas();

    // Recalcular ancho de DataTables al cambiar tabs
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (el) {
        el.addEventListener('shown.bs.tab', function () {
            if ($.fn.DataTable.isDataTable('#tabla-residuos')) {
                $('#tabla-residuos').DataTable().columns.adjust();
            }
            if ($.fn.DataTable.isDataTable('#tabla-normativas')) {
                $('#tabla-normativas').DataTable().columns.adjust();
            }
        });
    });
});

function inicializarTablaResiduos() {
    $('#tabla-residuos').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/residuo/views/data_residuos.php',
            type: 'POST'
        },
        columnDefs: [
            { orderable: false, targets: [5] }
        ],
        columns: [
            { data: 0, title: 'No' },
            { data: 1, title: 'Nombre Residuo' },
            { data: 2, title: 'Tipo' },
            { data: 3, title: 'SubCategoría' },
            { data: 4, title: 'U.M.' },
            { data: 5, title: 'Acción', orderable: false, searchable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
}

function inicializarTablaNormativas() {
    $('#tabla-normativas').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/residuo/views/data_normativas.php',
            type: 'POST'
        },
        columnDefs: [
            { orderable: false, targets: [3] }
        ],
        columns: [
            { data: 0, title: 'No' },
            { data: 1, title: 'Normativas' },
            { data: 2, title: 'Descripción' },
            { data: 3, title: 'Acción', orderable: false, searchable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
}

function cargarServicios() {
    $.ajax({
        url: 'modules/laboratorio/servicio/controllers/ServicioAPI.php?action=listar',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && Array.isArray(response.data)) {
                let options = '<option value="">Seleccionar el servicio</option>';
                response.data.forEach(function(servicio) {
                    options += '<option value="' + servicio.Id_Servicio + '" data-nombre="' + htmlEscape(servicio.Nombre) + '">' + servicio.Nombre + '</option>';
                });
                $('#select-servicios').html(options);
            }
        },
        error: function() {
            console.log('Error al cargar servicios');
        }
    });
}

function htmlEscape(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function cargarNormativas() {
    $.ajax({
        url: '?module=laboratorio&action=residuo&subaction=normativas',
        type: 'GET',
        success: function(data) {
            let options = '<option value="">Seleccionar normativa</option>';
            // Populate normativas dropdown
            $('#normativa_informe').html(options);
        }
    });
}

// ==================== ACTUALIZAR SUBCATEGORÍAS DINÁMICAMENTE ====================

function actualizarSubcategorias() {
    const tipoResiduo = $('#tipo_residuo').val();
    const subcategorySelect = $('#subcategoria');
    
    let opciones = '<option value="">Seleccionar la subcategoría</option>';
    
    if (tipoResiduo === 'PELIGROSO') {
        opciones += `
            <option value="Químicos">Químicos</option>
            <option value="Biológicos">Biológicos</option>
            <option value="Metales Pesados">Metales Pesados</option>
            <option value="Reactivos">Reactivos</option>
            <option value="Material Contaminado">Material Contaminado</option>
        `;
    } else if (tipoResiduo === 'NO PELIGROSO') {
        opciones += `
            <option value="Orgánicos">Orgánicos</option>
            <option value="Aprovechables">Aprovechables</option>
            <option value="No Aprovechables">No Aprovechables</option>
        `;
    }
    
    subcategorySelect.html(opciones);
}

// ==================== GESTIÓN DE SERVICIOS EN FORMULARIO ====================

let serviciosAgregados = [];

// Event listener para cambio en dropdown
$('#select-servicios').on('change', function() {
    agregarServicio();
});

function agregarServicio() {
    const idServicio = $('#select-servicios').val();
    
    // Si no hay selección, salir silenciosamente
    if (!idServicio || idServicio === '') {
        return;
    }
    
    const nombreServicio = $('#select-servicios option:selected').text();
    
    // Verificar si ya existe (comparar como números)
    if (serviciosAgregados.some(s => parseInt(s.id) === parseInt(idServicio))) {
        Swal.fire('Aviso', 'Este servicio ya fue agregado', 'info');
        $('#select-servicios').val('');
        return;
    }
    
    // Validar que se seleccionó algo válido
    if (!nombreServicio || nombreServicio === 'Seleccionar el servicio') {
        Swal.fire('Validación', 'Seleccione un servicio válido', 'warning');
        return;
    }
    
    // Agregar a array
    serviciosAgregados.push({
        id: parseInt(idServicio),
        nombre: nombreServicio,
        cantidad: 0
    });
    
    actualizarTablaServicios();
    $('#select-servicios').val('');
}

function actualizarTablaServicios() {
    const tbody = $('#tabla-servicios tbody');
    tbody.html('');
    
    serviciosAgregados.forEach((servicio, index) => {
        tbody.append(`
            <tr>
                <td>${htmlEscape(servicio.nombre)}</td>
                <td>
                    <input type="number" id="cantidad-servicio-${index}" class="form-control form-control-sm" min="0.01" step="0.01" 
                           placeholder="Cantidad" value="${servicio.cantidad}" 
                           onchange="cambiarCantidadServicio(${index}, this.value)">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-ghost-danger" onclick="eliminarServicio(${index})" title="Eliminar">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

function cambiarCantidadServicio(index, cantidad) {
    serviciosAgregados[index].cantidad = parseFloat(cantidad) || 0;
}

function eliminarServicio(index) {
    serviciosAgregados.splice(index, 1);
    actualizarTablaServicios();
}

// Limpiar servicios al abrir modal
$('#modal-residuo').on('show.bs.modal', function() {
    // Si no estamos en modo edición, limpiar
    if (!$(this).find('#form-residuo').attr('data-modo') || $(this).find('#form-residuo').attr('data-modo') === 'crear') {
        serviciosAgregados = [];
        actualizarTablaServicios();
    }
});

// Resetear formulario cuando se cierra el modal
$('#modal-residuo').on('hide.bs.modal', function() {
    $('#form-residuo')[0].reset();
    $('#form-residuo').attr('data-modo', 'crear').removeAttr('data-id');
    $('#modal-residuo .modal-title').text('Nuevo Residuo');
    $('#modal-residuo button[type="submit"]').text('Crear Residuo');
    serviciosAgregados = [];
    actualizarTablaServicios();
    $('#tipo_residuo').val('');
    $('#subcategoria').html('<option value="">Seleccionar la subcategoría</option>');
});

// Resetear formulario de normativa cuando se cierra el modal
$('#modal-normativa').on('hide.bs.modal', function() {
    $('#form-normativa')[0].reset();
    $('#form-normativa').attr('data-modo', 'crear').removeAttr('data-id');
    $('#modal-normativa .modal-title').text('Nueva Normativa SST');
    $('#modal-normativa button[type="submit"]').text('Crear Normativa');
});

// Formulario Residuo
$('#form-residuo').on('submit', function(e) {
    e.preventDefault();
    
    const modo = $(this).attr('data-modo') || 'crear';
    const idResiduo = $(this).attr('data-id');
    
    // Validaciones básicas
    const nombre = $('#nombre_residuo').val().trim();
    const codigo = $('#codigo_item').val().trim();
    const tipo = $('#tipo_residuo').val();
    const subcategoria = $('#subcategoria').val();
    const unidad = $('#unidad_referencia').val().trim();
    
    if (!nombre) {
        Swal.fire('Validación', 'El nombre del residuo es requerido', 'warning');
        return;
    }
    
    if (!tipo) {
        Swal.fire('Validación', 'Debe seleccionar un tipo de residuo', 'warning');
        return;
    }
    
    if (!subcategoria) {
        Swal.fire('Validación', 'Debe seleccionar una subcategoría', 'warning');
        return;
    }
    
    if (!unidad) {
        Swal.fire('Validación', 'La unidad de medida es requerida', 'warning');
        return;
    }
    
    let datos = {
        Nombre_Item: nombre,
        Codigo_Item: codigo,
        Tipo_Principal: tipo,
        Subcategoria: subcategoria,
        Unidad_Referencia: unidad,
        Servicios: serviciosAgregados
    };
    
    if (modo === 'editar' && idResiduo) {
        datos.Id_Residuo_Cat = idResiduo;
    }

    const action = modo === 'editar' ? 'editar_residuo' : 'crear_residuo';
    
    console.log('Enviando datos:', datos);
    console.log('Servicios array:', serviciosAgregados);
    console.log('Modo:', modo, 'ID:', idResiduo, 'Action:', action);

    $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=' + action,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            Swal.fire('Éxito', response.message, 'success').then(() => {
                $('#modal-residuo').modal('hide');
                $('#form-residuo')[0].reset();
                $('#form-residuo').attr('data-modo', 'crear').removeAttr('data-id');
                $('#modal-residuo .modal-title').text('Nuevo Residuo');
                $('#modal-residuo button[type="submit"]').text('Crear Residuo');
                $('#tabla-residuos').DataTable().ajax.reload();
            });
        },
        error: function(xhr) {
            let titulo = 'Error';
            let mensaje = 'Ocurrió un error al procesar el residuo';
            let icono = 'error';
            
            console.log('Error response:', xhr.status, xhr.responseText);
            
            try {
                let resp = JSON.parse(xhr.responseText);
                mensaje = resp.message || mensaje;
                
                // Detectar tipo de error
                if (xhr.status === 409 || xhr.status === 400) {
                    icono = 'warning';
                    titulo = xhr.status === 409 ? 'Conflicto' : 'Validación';
                }
            } catch(e) {
                if (xhr.status === 400) {
                    mensaje = 'Datos incompletos o inválidos';
                    icono = 'warning';
                    titulo = 'Validación';
                } else if (xhr.status === 409) {
                    mensaje = 'Este código ya en uso. Por favor, use uno diferente';
                    icono = 'warning';
                    titulo = 'Código Duplicado';
                }
            }
            
            Swal.fire(titulo, mensaje, icono);
        }
    });
});

// Formulario Normativa
$('#form-normativa').on('submit', function(e) {
    e.preventDefault();
    
    const modo = $(this).attr('data-modo') || 'crear';
    const idNormativa = $(this).attr('data-id');
    
    // Validaciones básicas
    const nombre = $('#nombre_ley').val().trim();
    const descripcion = $('#descripcion_ley').val().trim();
    
    if (!nombre) {
        Swal.fire('Validación', 'El nombre de la ley es requerido', 'warning');
        return;
    }
    
    if (!descripcion) {
        Swal.fire('Validación', 'La descripción es requerida', 'warning');
        return;
    }
    
    let datos = {
        Nombre_Ley: nombre,
        Descripcion: descripcion,
        Usuario_Creacion: usuarioId
    };
    
    if (modo === 'editar' && idNormativa) {
        datos.Id_Normativa_SST = idNormativa;
    }

    const action = modo === 'editar' ? 'editar_normativa' : 'crear_normativa';

    $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=' + action,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            Swal.fire('Éxito', response.message, 'success').then(() => {
                $('#modal-normativa').modal('hide');
                $('#form-normativa')[0].reset();
                $('#form-normativa').attr('data-modo', 'crear').removeAttr('data-id');
                $('#modal-normativa .modal-title').text('Nueva Normativa SST');
                $('#modal-normativa button[type="submit"]').text('Crear Normativa');
                $('#tabla-normativas').DataTable().ajax.reload();
                cargarNormativasParaInforme();
            });
        },
        error: function(xhr) {
            let titulo = 'Error';
            let mensaje = 'Ocurrió un error al procesar la normativa';
            let icono = 'error';
            
            try {
                let resp = JSON.parse(xhr.responseText);
                mensaje = resp.message || mensaje;
                
                if (xhr.status === 409 || xhr.status === 400) {
                    icono = 'warning';
                    titulo = xhr.status === 409 ? 'Conflicto' : 'Validación';
                }
            } catch(e) {
                if (xhr.status === 400) {
                    mensaje = 'Datos incompletos o inválidos';
                    icono = 'warning';
                    titulo = 'Validación';
                }
            }
            
            Swal.fire(titulo, mensaje, icono);
        }
    });
});

function editarResiduo(id) {
    // Obtener datos del residuo
    $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=obtener_residuo&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const residuo = response.data;
                
                // Llenar formulario
                $('#nombre_residuo').val(residuo.Nombre_Item);
                $('#codigo_item').val(residuo.Codigo_Item);
                $('#tipo_residuo').val(residuo.Tipo_Principal).trigger('change');
                setTimeout(() => $('#subcategoria').val(residuo.Subcategoria), 100);
                $('#unidad_referencia').val(residuo.Unidad_Referencia);
                
                // Cambiar título y botón
                $('#modal-residuo .modal-title').text('Editar Residuo');
                $('#form-residuo').attr('data-modo', 'editar').attr('data-id', id);
                $('#modal-residuo button[type="submit"]').text('Actualizar Residuo');
                
                // Cargar servicios ligados
                cargarServiciosResiduo(id);
                
                // Mostrar modal
                $('#modal-residuo').modal('show');
            }
        },
        error: function() {
            Swal.fire('Error', 'No se pudo cargar el residuo', 'error');
        }
    });
}

function cargarServiciosResiduo(id) {
    $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=obtener_servicios_residuo&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Servicios cargados:', response);
            if (response.success && Array.isArray(response.data)) {
                // Limpiar array y poblar con servicios obtenidos
                serviciosAgregados = [];
                response.data.forEach(function(servicio) {
                    serviciosAgregados.push({
                        id: parseInt(servicio.id) || 0,
                        nombre: String(servicio.nombre || ''),
                        cantidad: parseFloat(servicio.cantidad) || 0
                    });
                });
                console.log('serviciosAgregados después de cargar:', serviciosAgregados);
                actualizarTablaServicios();
            } else {
                serviciosAgregados = [];
                actualizarTablaServicios();
            }
            
            // Resetear el select para que pueda agregar más servicios
            $('#select-servicios').val('');
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar servicios:', xhr, status, error);
            if (xhr.responseText) {
                console.error('Respuesta del servidor:', xhr.responseText);
            }
            serviciosAgregados = [];
            actualizarTablaServicios();
            $('#select-servicios').val('');
        }
    });
}

function eliminarResiduo(id) {
    Swal.fire({
        title: '¿Eliminar residuo?',
        text: 'El residuo será marcado como inactivo',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=eliminar_residuo',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ Id_Residuo_Cat: id }),
                dataType: 'json',
                success: function(response) {
                    Swal.fire('Eliminado', response.message, 'success').then(() => {
                        $('#tabla-residuos').DataTable().ajax.reload();
                    });
                },
                error: function(xhr) {
                    let mensaje = 'No se pudo eliminar el residuo';
                    let icon = 'error';
                    
                    // Si es error 409, está ligado a un informe
                    if (xhr.status === 409) {
                        try {
                            let resp = JSON.parse(xhr.responseText);
                            mensaje = resp.message;
                            icon = 'warning';
                        } catch(e) {}
                    }
                    
                    Swal.fire(icon === 'warning' ? 'No se puede eliminar' : 'Error', mensaje, icon);
                }
            });
        }
    });
}

function reactivarResiduo(id) {
    Swal.fire({
        title: '¿Reactivar residuo?',
        text: 'Este residuo volverá a estar disponible',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=reactivar_residuo',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Reactivado', response.message, 'success').then(() => {
                            $('#tabla-residuos').DataTable().ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo reactivar el residuo', 'error');
                }
            });
        }
    });
}

// ==================== FUNCIONES PARA NORMATIVAS ====================

function editarNormativa(id) {
    // Obtener datos de la normativa
    $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=obtener_normativa&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const normativa = response.data;
                
                // Llenar formulario
                $('#nombre_ley').val(normativa.Nombre_Ley);
                $('#descripcion_ley').val(normativa.Descripcion);
                
                // Cambiar título y botón
                $('#modal-normativa .modal-title').text('Editar Normativa SST');
                $('#form-normativa').attr('data-modo', 'editar').attr('data-id', id);
                $('#modal-normativa button[type="submit"]').text('Actualizar Normativa');
                
                // Mostrar modal
                $('#modal-normativa').modal('show');
            }
        },
        error: function() {
            Swal.fire('Error', 'No se pudo cargar la normativa', 'error');
        }
    });
}

function eliminarNormativa(id) {
    Swal.fire({
        title: '¿Eliminar normativa?',
        text: 'La normativa será marcada como inactiva',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=eliminar_normativa',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ Id_Normativa_SST: id }),
                dataType: 'json',
                success: function(response) {
                    Swal.fire('Eliminada', response.message, 'success').then(() => {
                        $('#tabla-normativas').DataTable().ajax.reload();
                        cargarNormativasParaInforme();
                    });
                },
                error: function(xhr) {
                    let mensaje = 'No se pudo eliminar la normativa';
                    let icon = 'error';
                    
                    // Si es error 409, está ligada a un informe
                    if (xhr.status === 409) {
                        try {
                            let resp = JSON.parse(xhr.responseText);
                            mensaje = resp.message;
                            icon = 'warning';
                        } catch(e) {}
                    }
                    
                    Swal.fire(icon === 'warning' ? 'No se puede eliminar' : 'Error', mensaje, icon);
                }
            });
        }
    });
}

function reactivarNormativa(id) {
    Swal.fire({
        title: '¿Reactivar normativa?',
        text: 'Esta normativa volverá a estar disponible',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=reactivar_normativa',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Reactivada', response.message, 'success').then(() => {
                            $('#tabla-normativas').DataTable().ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo reactivar la normativa', 'error');
                }
            });
        }
    });
}
</script>

</body>
</html>
