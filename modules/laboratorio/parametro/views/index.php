<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
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
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Parametros</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">PARAMETROS DE ANALISIS</h2>
        <div class="text-muted mt-1">Gestiona los parametros analitcos de laboratorio</div>
      </div>
    </div>
    <div class="row g-2">
      <?php if (!empty($permisos['crear'])): ?>
      <div class="col-auto">
        <button class="btn btn-success" onclick="abrirModalNuevoParametro()">
          <i class="ti ti-plus me-2"></i> Nuevo Parametro
        </button>
      </div>
      <?php endif; ?>
      <?php if (!empty($permisos['editar'])): ?>
      <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-gestionar-normativas">
          <i class="ti ti-book me-2"></i> Normativas
        </button>
      </div>
      <div class="col-auto">
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-gestionar-limites">
          <i class="ti ti-scale me-2"></i> Límites Legales
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table id="tabla-parametros" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Nombre</th>
                <th>Servicio</th>
                <th>Unidad</th>
                <th>Categoria</th>
                <th>Metodo</th>
                <th>Accion</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal modal-blur fade" id="modal-parametro" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Parametro</h5>
      </div>
      <div class="modal-body">
        <form id="form-parametro">
          <input type="hidden" id="Id_Parametro">
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="Nombre" placeholder="Nombre del Parametro" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Unidad de Medida</label>
            <input type="text" class="form-control" id="Unidad_Medida" placeholder="Ej: mg/L, unidades, %">
          </div>
          <div class="mb-3">
            <label class="form-label">Categoria</label>
            <select class="form-control" id="Categoria">
              <option value="">-- Seleccionar --</option>
              <option value="Fisico">Fisico</option>
              <option value="Quimico">Quimico</option>
              <option value="Microbiologico">Microbiologico</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Metodo Utilizado</label>
            <input type="text" class="form-control" id="Metodo_Utilizado" placeholder="Ej: Potenciometria, Nefelometria">
          </div>
          <div class="mb-3">
            <label class="form-label">Servicio del Parametro <span class="text-muted">(Opcional)</span></label>
            <select class="form-control" id="Id_Servicio">
              <option value="">-- Sin servicio especifico --</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarParametro()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal gestionar normativas -->
<div class="modal modal-blur fade" id="modal-gestionar-normativas" tabindex="-1" role="dialog" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Normativas</h5>
        <button type="button" class="btn btn-sm btn-success ms-3" onclick="abrirModalNuevaNormativa()">
          <i class="ti ti-plus me-1"></i> Nueva Normativa
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="tabla-normativas" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Nombre</th>
                <th>Descripcion</th>
                <th>Accion</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal gestionar limites -->
<div class="modal modal-blur fade" id="modal-gestionar-limites" tabindex="-1" role="dialog" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Límites Legales</h5>
        <button type="button" class="btn btn-sm btn-success ms-3" onclick="abrirModalNuevoLimite()">
          <i class="ti ti-plus me-1"></i> Nuevo Límite
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="tabla-limites" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Parametro</th>
                <th>Normativa</th>
                <th>Valor Max</th>
                <th>Valor Min</th>
                <th>Unidad de Medida</th>
                <th>Descripcion</th>
                <th>Accion</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Nuevo/Editar Normativa -->
<div class="modal modal-blur fade" id="modal-normativa" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Nueva Normativa</h5>
      </div>
      <div class="modal-body">
        <form id="form-normativa">
          <input type="hidden" id="Id_Normativa">
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="Nombre_Normativa" placeholder="Nombre de la Normativa" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripcion</label>
            <textarea class="form-control" id="Descripcion_Normativa" placeholder="Descripcion de la Normativa" rows="3"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarNormativa()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Nuevo/Editar Limite -->
<div class="modal modal-blur fade" id="modal-limite" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Limite Legal</h5>
      </div>
      <div class="modal-body">
        <form id="form-limite">
          <input type="hidden" id="Id_Limite_Legal">
          <div class="mb-3">
            <label class="form-label">Parametro <span class="text-danger">*</span></label>
            <select class="form-control" id="Id_Parametro_Limite" required>
              <option value="">-- Seleccionar Parametro --</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Normativa <span class="text-danger">*</span></label>
            <select class="form-control" id="Id_Normativa_Limite" required>
              <option value="">-- Seleccionar Normativa --</option>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Valor Maximo</label>
                <input type="number" class="form-control" id="Valor_Max" placeholder="8.5" step="0.0001">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Valor Minimo</label>
                <input type="number" class="form-control" id="Valor_Min" placeholder="6.5" step="0.0001">
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Unidad de Medida</label>
            <input type="text" class="form-control" id="Unidad_Medida_Limite" placeholder="Ej: mg/L, pH, µS/cm">
          </div>
          <div class="mb-3">
            <label class="form-label">Descripcion</label>
            <textarea class="form-control" id="Descripcion_Limite" placeholder="Ej: Riego de Vegetales, Consumo Humano" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarLimite()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tablaParametros, tablaNormativas, tablaLimites;

$(document).ready(function() {
    tablaParametros = $('#tabla-parametros').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "modules/laboratorio/parametro/views/data_listado_parametros.php",
            "type": "POST"
        },
        "columns": [
            { "data": 0 },  // No
            { "data": 1 },  // Nombre
            { "data": 2 },  // Servicio
            { "data": 3 },  // Unidad
            { "data": 4 },  // Categoria
            { "data": 5 },  // Metodo
            { "data": 6, "orderable": false }  // Accion
        ],
        "language": {
            "decimal": ",",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros",
            "paginate": {
                "first": "Primera",
                "last": "Ultima",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "order": [[ 0, "asc" ]]
    });
    
    tablaNormativas = $('#tabla-normativas').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "modules/laboratorio/parametro/views/data_listado_normativas.php",
            "type": "POST"
        },
        "columns": [
            { "data": 0 },  // No
            { "data": 1 },  // Nombre
            { "data": 2 },  // Descripcion
            { "data": 3, "orderable": false }  // Accion
        ],
        "language": {
            "decimal": ",",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros",
            "paginate": {
                "first": "Primera",
                "last": "Ultima",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "order": [[ 0, "asc" ]]
    });

    tablaLimites = $('#tabla-limites').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "modules/laboratorio/parametro/views/data_listado_limites.php",
            "type": "POST"
        },
        "columns": [
            { "data": 0 },  // No
            { "data": 1 },  // Parametro
            { "data": 2 },  // Normativa
            { "data": 3 },  // Valor Max
            { "data": 4 },  // Valor Min
            { "data": 5 },  // Unidad de Medida
            { "data": 6 },  // Descripcion
            { "data": 7, "orderable": false }  // Accion
        ],
        "language": {
            "decimal": ",",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros",
            "paginate": {
                "first": "Primera",
                "last": "Ultima",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "order": [[ 0, "asc" ]]
    });
    
    cargarServicios();
    cargarParametros();
    cargarNormativas();

    // Recalcular anchos cuando se abren los modales de gestión
    $('#modal-gestionar-normativas').on('shown.bs.modal', function () {
        if (tablaNormativas) tablaNormativas.columns.adjust().draw(false);
    });
    $('#modal-gestionar-limites').on('shown.bs.modal', function () {
        if (tablaLimites) tablaLimites.columns.adjust().draw(false);
    });
});

function cargarServicios() {
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php',
        type: 'GET',
        data: { action: 'listar_servicios' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">-- Sin servicio especifico --</option>';
                response.data.forEach(s => {
                    options += '<option value="' + s.Id_Servicio + '">' + s.Nombre + '</option>';
                });
                $('#Id_Servicio').html(options);
            }
        }
    });
}

function abrirModalNuevoParametro() {
    document.getElementById('form-parametro').reset();
    document.getElementById('Id_Parametro').value = '';
    document.querySelector('#modal-parametro .modal-title').textContent = 'Nuevo Parametro';
    new bootstrap.Modal(document.getElementById('modal-parametro')).show();
}

function cargarParametros() {
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php',
        type: 'GET',
        data: { action: 'listar' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">-- Seleccionar Parametro --</option>';
                response.data.forEach(p => {
                    options += '<option value="' + p.Id_Parametro + '">' + p.Nombre + '</option>';
                });
                $('#Id_Parametro_Limite').html(options);
            }
        }
    });
}

function cargarNormativas() {
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php',
        type: 'GET',
        data: { action: 'listar_normativas' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">-- Seleccionar Normativa --</option>';
                response.data.forEach(n => {
                    options += '<option value="' + n.Id_Normativa + '">' + n.Nombre + '</option>';
                });
                $('#Id_Normativa_Limite').html(options);
            }
        }
    });
}

function abrirModalNuevaNormativa() {
    document.getElementById('form-normativa').reset();
    document.getElementById('Id_Normativa').value = '';
    document.querySelector('#modal-normativa .modal-title').textContent = 'Nueva Normativa';
    new bootstrap.Modal(document.getElementById('modal-normativa')).show();
}

function guardarNormativa() {
    const id = document.getElementById('Id_Normativa').value;
    const datos = {
        Id_Normativa: id || null,
        Nombre: document.getElementById('Nombre_Normativa').value,
        Descripcion: document.getElementById('Descripcion_Normativa').value
    };
    
    const action = id ? 'actualizar_normativa' : 'guardar_normativa';
    
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=' + action,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                Swal.fire('Exito', response.message, 'success').then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modal-normativa')).hide();
                    tablaNormativas.ajax.reload();
                    cargarNormativas();
                });
            } else {
                Swal.fire('Error', response ? response.message : 'Error desconocido', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire('Error', 'Error al guardar: ' + error, 'error');
        }
    });
}

function editarNormativa(id) {
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=obtener_normativa&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                const n = response.data;
                document.getElementById('Id_Normativa').value = n.Id_Normativa;
                document.getElementById('Nombre_Normativa').value = n.Nombre;
                document.getElementById('Descripcion_Normativa').value = n.Descripcion || '';
                document.querySelector('#modal-normativa .modal-title').textContent = 'Editar Normativa';
                new bootstrap.Modal(document.getElementById('modal-normativa')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar la normativa', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire('Error', 'Error al cargar: ' + error, 'error');
        }
    });
}

function eliminarNormativa(id) {
    Swal.fire({
        title: 'Confirmar',
        text: 'Desea eliminar esta normativa?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=eliminar_normativa&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        Swal.fire('Exito', 'Normativa eliminada correctamente', 'success').then(() => {
                            tablaNormativas.ajax.reload();
                            cargarNormativas();
                        });
                    } else {
                        let mensaje = response.message || 'Error al eliminar';
                        if (mensaje.includes('limite')) {
                            Swal.fire({
                                title: 'No se puede eliminar',
                                html: '<i class="ti ti-alert-circle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 10px;"></i><p style="margin-top: 15px;">' + mensaje + '</p>',
                                icon: 'warning',
                                confirmButtonText: 'Entendido'
                            });
                        } else {
                            Swal.fire('Error', mensaje, 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    try {
                        let response = JSON.parse(xhr.responseText);
                        let mensaje = response.message || 'Error al eliminar';
                        if (mensaje.includes('limite')) {
                            Swal.fire({
                                title: 'No se puede eliminar',
                                html: '<i class="ti ti-alert-circle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 10px;"></i><p style="margin-top: 15px;">' + mensaje + '</p>',
                                icon: 'warning',
                                confirmButtonText: 'Entendido'
                            });
                        } else {
                            Swal.fire('Error', mensaje, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Error al eliminar la normativa', 'error');
                    }
                }
            });
        }
    });
}

function abrirModalNuevoLimite() {
    document.getElementById('form-limite').reset();
    document.getElementById('Id_Limite_Legal').value = '';
    document.querySelector('#modal-limite .modal-title').textContent = 'Nuevo Limite Legal';
    new bootstrap.Modal(document.getElementById('modal-limite')).show();
}

function guardarLimite() {
    const id = document.getElementById('Id_Limite_Legal').value;
    const datos = {
        Id_Limite_Legal: id || null,
        Id_Parametro: document.getElementById('Id_Parametro_Limite').value,
        Id_Normativa: document.getElementById('Id_Normativa_Limite').value,
        Valor_Max: document.getElementById('Valor_Max').value || null,
        Valor_Min: document.getElementById('Valor_Min').value || null,
      Unidad_Medida: document.getElementById('Unidad_Medida_Limite').value || null,
      Descripcion: document.getElementById('Descripcion_Limite').value || null
    };
    
    const action = id ? 'actualizar_limite' : 'guardar_limite';
    
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=' + action,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                Swal.fire('Exito', response.message, 'success').then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modal-limite')).hide();
                    tablaLimites.ajax.reload();
                });
            } else {
                Swal.fire('Error', response ? response.message : 'Error desconocido', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire('Error', 'Error al guardar: ' + error, 'error');
        }
    });
}

function editarLimite(id) {
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=obtener_limite&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                const l = response.data;
                document.getElementById('Id_Limite_Legal').value = l.Id_Limite_Legal;
                document.getElementById('Id_Parametro_Limite').value = l.Id_Parametro;
                document.getElementById('Id_Normativa_Limite').value = l.Id_Normativa;
                document.getElementById('Valor_Max').value = l.Valor_Max || '';
                document.getElementById('Valor_Min').value = l.Valor_Min || '';
                document.getElementById('Unidad_Medida_Limite').value = l.Unidad_Medida || '';
                document.getElementById('Descripcion_Limite').value = l.Descripcion || '';
                document.querySelector('#modal-limite .modal-title').textContent = 'Editar Limite Legal';
                new bootstrap.Modal(document.getElementById('modal-limite')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el limite', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire('Error', 'Error al cargar: ' + error, 'error');
        }
    });
}

function eliminarLimite(id) {
    Swal.fire({
        title: 'Confirmar',
        text: 'Desea eliminar este limite?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=eliminar_limite&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        Swal.fire('Exito', 'Limite eliminado correctamente', 'success').then(() => {
                            tablaLimites.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Error al eliminar', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    try {
                        let response = JSON.parse(xhr.responseText);
                        Swal.fire('Error', response.message || 'Error al eliminar', 'error');
                    } catch (e) {
                        Swal.fire('Error', 'Error al eliminar el limite', 'error');
                    }
                }
            });
        }
    });
}

function guardarParametro() {
    const id = document.getElementById('Id_Parametro').value;
    const datos = {
        Id_Parametro: id || null,
        Nombre: document.getElementById('Nombre').value,
        Id_Servicio: document.getElementById('Id_Servicio').value,
        Unidad_Medida: document.getElementById('Unidad_Medida').value,
        Categoria: document.getElementById('Categoria').value,
        Metodo_Utilizado: document.getElementById('Metodo_Utilizado').value
    };
    
    const action = id ? 'actualizar' : 'guardar';
    
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=' + action,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                Swal.fire('Exito', response.message, 'success').then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modal-parametro')).hide();
                    tablaParametros.ajax.reload();
                });
            } else {
                Swal.fire('Error', response ? response.message : 'Error desconocido', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire('Error', 'Error al guardar: ' + error, 'error');
        }
    });
}

function editarParametro(id) {
    $.ajax({
        url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=obtener&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                const p = response.data;
                document.getElementById('Id_Parametro').value = p.Id_Parametro;
                document.getElementById('Nombre').value = p.Nombre;
                document.getElementById('Id_Servicio').value = p.Id_Servicio || '';
                document.getElementById('Unidad_Medida').value = p.Unidad_Medida || '';
                document.getElementById('Categoria').value = p.Categoria || '';
                document.getElementById('Metodo_Utilizado').value = p.Metodo_Utilizado || '';
                document.querySelector('#modal-parametro .modal-title').textContent = 'Editar Parametro';
                new bootstrap.Modal(document.getElementById('modal-parametro')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el parametro', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire('Error', 'Error al cargar: ' + error, 'error');
        }
    });
}

function eliminarParametro(id) {
    Swal.fire({
        title: 'Confirmar',
        text: 'Desea eliminar este parametro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/parametro/controllers/ParametroAPI.php?action=eliminar&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        Swal.fire('Exito', 'Parametro eliminado correctamente', 'success').then(() => {
                            tablaParametros.ajax.reload();
                        });
                    } else {
                        let mensaje = response.message || 'Error al eliminar';
                        if (mensaje.includes('limite') || mensaje.includes('servicio')) {
                            Swal.fire({
                                title: 'No se puede eliminar',
                                html: '<i class="ti ti-alert-circle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 10px;"></i><p style="margin-top: 15px;">' + mensaje + '</p>',
                                icon: 'warning',
                                confirmButtonText: 'Entendido'
                            });
                        } else {
                            Swal.fire('Error', mensaje, 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    try {
                        let response = JSON.parse(xhr.responseText);
                        let mensaje = response.message || 'Error al eliminar';
                        if (mensaje.includes('limite') || mensaje.includes('servicio')) {
                            Swal.fire({
                                title: 'No se puede eliminar',
                                html: '<i class="ti ti-alert-circle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 10px;"></i><p style="margin-top: 15px;">' + mensaje + '</p>',
                                icon: 'warning',
                                confirmButtonText: 'Entendido'
                            });
                        } else {
                            Swal.fire('Error', mensaje, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Error al eliminar el parametro', 'error');
                    }
                }
            });
        }
    });
}
</script>


