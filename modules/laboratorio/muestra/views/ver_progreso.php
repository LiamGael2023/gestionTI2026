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
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Seguimiento</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">SEGUIMIENTO DE MUESTRAS</h2>
        <div class="text-muted mt-1">Monitoreo del estado y progreso de análisis en laboratorio</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    
    <!-- Muestras en Progreso -->
    <div class="card mb-4">
      <div class="card-header">
        <h3 class="card-title">Muestras en Progreso</h3>
      </div>
      <div class="card-body">
        <p class="text-muted small">Muestras que se encuentran en proceso de análisis en laboratorio</p>

        <div class="table-responsive">
          <table id="tabla-progress" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Agricultor</th>
                <th>Valle</th>
                <th>Fecha Recepción</th>
                <th>Servicios</th>
                <th>Avance</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Muestras Completadas -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Muestras Completadas</h3>
      </div>
      <div class="card-body">
        <p class="text-muted small">Muestras que han completado su análisis y han sido firmadas por el responsable técnico</p>

        <div class="table-responsive">
          <table id="tabla-passed" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Agricultor</th>
                <th>Valle</th>
                <th>Fecha Recepción</th>
                <th>Estado</th>
                <th>Fecha Finalización</th>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Tabla En Progreso
    // Columnas: 0=No | 1=Agricultor | 2=Valle | 3=FechaRecepción | 4=TipoServicio | 5=TipoMuestra | 6=Acción
    $('#tabla-progress').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/muestra/views/data_progreso.php',
            type: 'POST'
        },
        columnDefs: [
            { orderable: false, targets: [6] }
        ],
        columns: [
            { data: 0, title: 'ID' },
            { data: 1, title: 'Agricultor' },
            { data: 2, title: 'Valle' },
            { data: 3, title: 'Fecha Recepción' },
            { data: 4, title: 'Servicios' },
            { data: 5, title: 'Tipo de Muestra' },
            { data: 6, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
    });

    // Tabla Completadas
    // Columnas: 0=No | 1=Agricultor | 2=Valle | 3=FechaRecepción | 4=Estado | 5=FechaValidación | 6=TipoMuestra | 7=Acción
    $('#tabla-passed').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/muestra/views/data_completadas.php',
            type: 'POST'
        },
        columnDefs: [
            { orderable: false, targets: [7] }
        ],
        columns: [
            { data: 0, title: 'ID' },
            { data: 1, title: 'Agricultor' },
            { data: 2, title: 'Valle' },
            { data: 3, title: 'Fecha Recepción' },
            { data: 4, title: 'Estado' },
            { data: 5, title: 'Fecha Finalización' },
            { data: 6, title: 'Tipo de Muestra' },
            { data: 7, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
    });
});
</script>
