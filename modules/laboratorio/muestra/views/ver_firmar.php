<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
    .badge-pendiente {
        background-color: #ffc107;
        color: #000;
        font-weight: 600;
    }
    .badge-procesada {
        background-color: #28a745;
        color: #fff;
        font-weight: 600;
    }
    .badge-lista-firma {
        background-color: #17a2b8;
        color: #fff;
        font-weight: 600;
    }
    .status-icon {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
    }
    .status-success {
        background-color: #e8f5e9;
        color: #2e7d32;
    }
    .status-warning {
        background-color: #fff3e0;
        color: #e65100;
    }
    .status-info {
        background-color: #e1f5fe;
        color: #01579b;
    }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Por Firmar</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">MUESTRAS POR FIRMAR</h2>
        <div class="text-muted mt-1">Muestras que han completado su análisis y están listas para aprobación técnica y firma del responsable</div>
      </div>
      <div class="col-auto">
        <span class="badge badge-lista-firma ms-2">
          <i class="ti ti-file-text me-1"></i> Listas para Firma
        </span>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    
    <!-- Tabla de Muestras por Firmar -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Lista de Muestras Por Firmar</h3>
      </div>
      <div class="card-body">
        <p class="text-muted small">Muestras con análisis completados, listas para revisión técnica y firma de autorización</p>

        <div class="table-responsive">
          <table id="tabla-por-firmar" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Agricultor</th>
                <th>Valle</th>
                <th>Tipo de Servicio</th>
                <th>Fecha de Análisis</th>
                <th>Estado</th>
                <th>Responsable</th>
                <th>Tipo de Muestra</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal: Ver Detalle y Firmar -->
<div class="modal modal-blur fade" id="modal-firmar-muestra" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      
      <div class="modal-header">
        <h5 class="modal-title">Firma de Muestra</h5>
      </div>
      
      <div class="modal-body">
        
        <!-- DATOS BÁSICOS DE MUESTRA -->
        <div class="mb-3">
          <h6 class="card-title">Datos de Muestra</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label text-muted small">ID Muestra</label>
                <p class="mb-0" id="detalle-id-muestra">-</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label text-muted small">Agricultor</label>
                <p class="mb-0" id="detalle-agricultor">-</p>
              </div>
            </div>
          </div>
          <div class="row g-2 mt-2">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label text-muted small">Valle</label>
                <p class="mb-0" id="detalle-valle">-</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label text-muted small">Fecha Recepción</label>
                <p class="mb-0" id="detalle-fecha">-</p>
              </div>
            </div>
          </div>
        </div>

        <hr>

        <!-- RESULTADOS ANALÍTICOS -->
        <div class="mb-3">
          <h6 class="card-title">Resultados de Análisis</h6>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Servicio</th>
                  <th>Parámetro</th>
                  <th>Resultado</th>
                  <th>Unidad</th>
                  <th>LMD</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody id="tabla-resultados-tbody">
                <!-- Se cargará dinámicamente -->
              </tbody>
            </table>
          </div>
        </div>

        <hr>

        <!-- FIRMA -->
        <div class="mb-3">
          <h6 class="card-title">Firma Digital</h6>
          <form>
            <div class="mb-3">
              <label class="form-label">Responsable Técnico <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="responsable-firma" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label">Contraseña de Confirmación <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password-firma" placeholder="Ingrese su contraseña">
            </div>

            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="confirma-resultados">
              <label class="form-check-label" for="confirma-resultados">
                Confirmo que he revisado todos los resultados y son correctos
              </label>
            </div>
          </form>
        </div>

      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-firmar-muestra">
          <i class="ti ti-check me-2"></i> Firmar Muestra
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Tabla de Muestras Por Firmar
    // Columnas: 0=Id | 1=Agricultor | 2=Valle | 3=TipoServicio | 4=FechaAnalisis | 5=Estado | 6=Responsable | 7=TipoMuestra | 8=Acción
    $('#tabla-por-firmar').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/muestra/views/data_firmar.php',
            type: 'POST'
        },
        columnDefs: [
            { orderable: false, targets: [8] }
        ],
        columns: [
            { data: 0, title: 'ID' },
            { data: 1, title: 'Agricultor' },
            { data: 2, title: 'Valle' },
            { data: 3, title: 'Tipo de Servicio' },
            { data: 4, title: 'Fecha de Análisis' },
            { data: 5, title: 'Estado' },
            { data: 6, title: 'Responsable' },
            { data: 7, title: 'Tipo de Muestra' },
            { data: 8, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
    });
});
</script>
