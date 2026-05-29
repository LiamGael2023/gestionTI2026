<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #004d99; border-color: #004d99; color: white;
    }
    /* Validación */
    .is-invalid { border-color: #dc3545 !important; }
    .invalid-feedback { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; }
    .form-control.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25); }
    /* Filas coloreadas por estado */
    .row-eq-inactivo  td { background-color: #fff0f0 !important; }
    .row-eq-correctivo td { background-color: #fff7ed !important; }
    .row-eq-preventivo td { background-color: #fefce8 !important; }
    .row-eq-predictivo td { background-color: #eff6ff !important; }
    .row-eq-otro       td { background-color: #f5f3ff !important; }
    /* Leyenda de colores */
    .leyenda-dot { display:inline-block; width:12px; height:12px; border-radius:50%; margin-right:5px; }
    /* Dot indicador de estado en selects */
    .estado-dot-wrap { display:flex; align-items:center; gap:8px; }
    .estado-dot-ind  { width:13px; height:13px; border-radius:50%; flex-shrink:0; background:#adb5bd; transition:background .2s; }
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
      <?php if (!empty($permisos['crear'])): ?>
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-equipo">
          <i class="ti ti-plus me-2"></i> Nuevo Equipo
        </button>
      </div>
      <?php endif; ?>
      <?php if (!empty($permisos['editar'])): ?>
      <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-gestionar-estados">
          <i class="ti ti-list-check me-2"></i> Estados
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
          <strong>Nota importante:</strong> Solo está permitido el uso y reserva de los equipos que figuren con el estado "Disponible". Cualquier equipo en estado diferente al indicado no podrá ser seleccionado hasta que se valide su operatividad.
        </div>
      </div>
    </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <small class="text-muted">
                        <span class="leyenda-dot" style="background:#fff0f0;border:1px solid #fca5a5"></span>Inactivo &nbsp;
                        <span class="leyenda-dot" style="background:#fff7ed;border:1px solid #fdba74"></span>Correctivo &nbsp;
                        <span class="leyenda-dot" style="background:#fefce8;border:1px solid #fde047"></span>Preventivo &nbsp;
                        <span class="leyenda-dot" style="background:#eff6ff;border:1px solid #93c5fd"></span>Predictivo &nbsp;
                        <span class="leyenda-dot" style="background:#ede9fe;border:1px solid #a78bfa"></span>Calibración próxima (celda)
                    </small>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla-equipos" class="table table-vcenter card-table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nombre</th>
                                <th>Proveedor</th>
                                <th>Antigüedad</th>
                                <th>Próxima Calibración</th>
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

<!-- ============================================================
     MODAL NUEVO / EDITAR EQUIPO
============================================================ -->
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

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label required">Nombre del Equipo</label>
              <input type="text" class="form-control" id="Nombre" name="Nombre" placeholder="Nombre del equipo" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha de Adquisición</label>
              <input type="date" class="form-control" id="Fecha_Adquisicion" name="Fecha_Adquisicion">
            </div>

            <div class="col-md-10">
              <label class="form-label">Proveedor</label>
              <select class="form-control" id="Id_Proveedor" name="Id_Proveedor">
                <option value="">Sin proveedor registrado</option>
              </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="button" class="btn btn-outline-secondary w-100" onclick="abrirModalProveedorRapido()" title="Crear nuevo proveedor">
                <i class="ti ti-plus"></i>
              </button>
            </div>

            <div class="col-12">
              <label class="form-label required">Estado</label>
              <div class="estado-dot-wrap">
                <span class="estado-dot-ind" id="dot-Id_Estado"></span>
                <select class="form-control" id="Id_Estado" name="Id_Estado" required onchange="actualizarDotEstado('Id_Estado','dot-Id_Estado')">
                  <option value="">Seleccionar estado...</option>
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Fecha Última Calibración</label>
              <input type="date" class="form-control" id="Fecha_Ultima_Calibracion" name="Fecha_Ultima_Calibracion">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Próxima Calibración</label>
              <input type="date" class="form-control" id="Fecha_Proxima_Calibracion" name="Fecha_Proxima_Calibracion">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-equipo">
          <i class="ti ti-device-floppy me-1"></i> Guardar Equipo
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     MODAL GESTIONAR ESTADOS
============================================================ -->
<div class="modal modal-blur fade" id="modal-gestionar-estados" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Estados de Equipos</h5>
        <button type="button" class="btn btn-sm btn-success ms-3" onclick="mostrarFormEstado(null)">
          <i class="ti ti-plus me-1"></i> Agregar Estado
        </button>
      </div>
      <div class="modal-body">
        <!-- Tabla de estados -->
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

<!-- ============================================================
     MODAL CREAR / EDITAR ESTADO
============================================================ -->
<div class="modal modal-blur fade" id="modal-estado" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-estado-titulo">Nuevo Estado</h5>
      </div>
      <div class="modal-body">
        <form id="form-estado">
          <input type="hidden" id="Id_Estado_Edit" name="Id_Estado_Edit">
          <div class="mb-3">
            <label class="form-label required">Nombre</label>
            <input type="text" class="form-control" id="Nombre_Estado" name="Nombre" placeholder="Ej: Disponible" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <input type="text" class="form-control" id="Descripcion_Estado" name="Descripcion" placeholder="Descripción breve">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-estado">
          <i class="ti ti-device-floppy me-1"></i> Guardar Estado
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     MODAL INICIAR CALIBRACIÓN
============================================================ -->
<div class="modal modal-blur fade" id="modal-calibrar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-tool me-2 text-warning"></i>Iniciar Calibración</h5>
      </div>
      <div class="modal-body">
        <input type="hidden" id="calibrar-id-equipo">
        <p class="text-muted mb-3">Equipo: <strong id="calibrar-nombre-equipo"></strong></p>
        <div class="mb-3">
          <label class="form-label required">Estado durante la calibración</label>
          <div class="estado-dot-wrap">
            <span class="estado-dot-ind" id="dot-calibrar-id-estado"></span>
            <select class="form-control" id="calibrar-id-estado" onchange="actualizarDotEstado('calibrar-id-estado','dot-calibrar-id-estado')">
              <option value="">Seleccionar estado...</option>
            </select>
          </div>
          <div class="form-text text-muted">Solo se muestran estados diferentes a "Disponible".</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btn-confirmar-calibracion">
          <i class="ti ti-tool me-1"></i> Iniciar Calibración
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     MODAL FINALIZAR CALIBRACIÓN
============================================================ -->
<div class="modal modal-blur fade" id="modal-finalizar-calibracion" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-checks me-2 text-teal"></i>Finalizar Calibración</h5>
      </div>
      <div class="modal-body">
        <input type="hidden" id="finalizar-id-equipo">
        <p class="text-muted mb-3">Equipo: <strong id="finalizar-nombre-equipo"></strong></p>
        <div class="mb-3">
          <label class="form-label required">Observación de la calibración</label>
          <textarea class="form-control" id="finalizar-observacion" rows="4"
                    placeholder="Describa los resultados y observaciones de la calibración..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Fecha Próxima Calibración</label>
          <input type="date" class="form-control" id="finalizar-fecha-proxima">
          <div class="form-text text-muted">Opcional. Establece cuándo se debe realizar la siguiente calibración.</div>
        </div>
        <div class="alert alert-info py-2 mb-0">
          <i class="ti ti-info-circle me-1"></i> Al confirmar, el equipo volverá al estado <strong>Disponible</strong> y se actualizará la fecha de última calibración.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-finalizar">
          <i class="ti ti-checks me-1"></i> Finalizar y marcar Disponible
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     MODAL PROVEEDOR RÁPIDO (desde equipo)
============================================================ -->
<div class="modal modal-blur fade" id="modal-proveedor-rapido" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-building me-2"></i>Nuevo Proveedor</h5>
      </div>
      <div class="modal-body">
        <form id="form-proveedor-rapido">
          <div class="mb-3">
            <label class="form-label required">Razón Social</label>
            <input type="text" class="form-control" id="prov-rapido-razon" placeholder="Nombre o razón social" required>
          </div>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">RUC</label>
              <input type="text" class="form-control" id="prov-rapido-ruc" placeholder="20123456789">
            </div>
            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="text" class="form-control" id="prov-rapido-telefono" placeholder="999 999 999">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-prov-rapido">
          <i class="ti ti-device-floppy me-1"></i> Guardar Proveedor
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     MODAL HISTORIAL DE CALIBRACIONES
============================================================ -->
<div class="modal modal-blur fade" id="modal-historial" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-history me-2 text-blue"></i>Historial de Calibraciones</h5>
      </div>
      <div class="modal-body p-0">
        <div class="px-3 pt-3 pb-2">
          <p class="text-muted mb-0">Equipo: <strong id="historial-nombre-equipo"></strong></p>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter table-striped mb-0">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th style="width:140px">Fecha</th>
                <th>Observación</th>
                <th style="width:120px">Registrado por</th>
              </tr>
            </thead>
            <tbody id="historial-tbody">
              <tr><td colspan="4" class="text-center text-muted py-4">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php
// Emitir permisos como variable JS para uso en botones dinámicos
$_p = $permisos ?? ['ver'=>true,'crear'=>true,'editar'=>true,'eliminar'=>true,'exportar'=>true,'firmar'=>true];
?>
<script>
const LAB_PERMISOS = <?php echo json_encode(['editar'=>(bool)$_p['editar'],'eliminar'=>(bool)$_p['eliminar']]); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tablaEquipos, tablaEstados;

// Traducción inline de DataTables al español (evita petición CORS al CDN)
var dtSpanish = {
    sProcessing:     "Procesando...",
    sLengthMenu:     "Mostrar _MENU_ registros",
    sZeroRecords:    "No se encontraron resultados",
    sEmptyTable:     "No hay datos disponibles en la tabla",
    sInfo:           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    sInfoEmpty:      "Mostrando registros del 0 al 0 de un total de 0 registros",
    sInfoFiltered:   "(filtrado de un total de _MAX_ registros)",
    sSearch:         "Buscar:",
    sUrl:            "",
    sInfoThousands:  ",",
    sLoadingRecords: "Cargando...",
    oPaginate: {
        sFirst:    "Primero",
        sLast:     "Último",
        sNext:     "Siguiente",
        sPrevious: "Anterior"
    },
    oAria: {
        sSortAscending:  ": Activar para ordenar la columna de manera ascendente",
        sSortDescending: ": Activar para ordenar la columna de manera descendente"
    }
};

$(document).ready(function () {

    // ---- DataTable Equipos ----
    tablaEquipos = $('#tabla-equipos').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'modules/laboratorio/equipo/views/data_listado.php', type: 'POST' },
        columns: [
            { data: 0 },                         // No
            { data: 1 },                         // Nombre
            { data: 2 },                         // Proveedor
            { data: 3, orderable: false },        // Antigüedad
            { data: 4 },                         // Próxima Calibración
            { data: 5, orderable: false },        // Estado (badge)
            { data: 6, orderable: false },        // Acciones
            { data: 7, visible: false, searchable: false }  // rowClass (oculta)
        ],
        createdRow: function (row, data) {
            if (data[7]) $(row).addClass(data[7]);
            // Pintar toda la celda de calibración próxima en morado suave
            if (data[4] && data[4].indexOf('badge-cal-proxima') !== -1) {
                $(row).find('td').eq(4).css({ 'background-color': '#ede9fe', 'border-left': '3px solid #a78bfa' });
            }
        },
        language: dtSpanish,
        order: [[0, 'desc']]
    });

    // ---- DataTable Estados (modal) ----
    tablaEstados = $('#tabla-estados').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'modules/laboratorio/equipo/views/data_listado_estados.php', type: 'POST' },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3, orderable: false }
        ],
        language: dtSpanish,
        order: [[0, 'desc']]
    });

    // Cargar selects al iniciar
    cargarSelectEstados();
    cargarSelectProveedores();

    // ---- Guardar Equipo ----
    $('#btn-guardar-equipo').on('click', function () { guardarEquipo(); });

    // ---- Guardar Estado (inline en modal) ----
    $('#btn-guardar-estado').on('click', function () { guardarEstado(); });

    // ---- Limpiar modal equipo al cerrar ----
    $('#modal-equipo').on('hidden.bs.modal', function () {
        $('#form-equipo')[0].reset();
        $('#Id_Equipo').val('');
        $('#modal-titulo').text('Nuevo Equipo');
        limpiarErrores('#form-equipo');
        actualizarDotEstado('Id_Estado', 'dot-Id_Estado');
    });

    // ---- Limpiar modal estado al cerrar ----
    $('#modal-estado').on('hidden.bs.modal', function () {
        $('#form-estado')[0].reset();
        $('#Id_Estado_Edit').val('');
        $('#modal-estado-titulo').text('Nuevo Estado');
        limpiarErrores('#form-estado');
    });

    // ---- Ajustar tabla estados cuando se abre el modal ----
    $('#modal-gestionar-estados').on('shown.bs.modal', function () {
        if (tablaEstados) tablaEstados.columns.adjust();
    });

    // ---- Calibrar: confirmar inicio ----
    $('#btn-confirmar-calibracion').on('click', function () {
        const idEquipo  = $('#calibrar-id-equipo').val();
        const idEstado  = $('#calibrar-id-estado').val();
        if (!idEstado) {
            Swal.fire('Atención', 'Debe seleccionar un estado para la calibración', 'warning');
            return;
        }
        $.ajax({
            url:         'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=registrar_calibracion',
            type:        'POST',
            contentType: 'application/json',
            data:        JSON.stringify({ Id_Equipo: idEquipo, Id_Estado_Nuevo: idEstado }),
            dataType:    'json',
            success: function (r) {
                if (r.success) {
                    $('#modal-calibrar').modal('hide');
                    Swal.fire({ title: 'Calibración iniciada', text: r.message, icon: 'success', timer: 1500, showConfirmButton: false })
                        .then(() => tablaEquipos.ajax.reload());
                } else {
                    Swal.fire('Error', r.message, 'error');
                }
            },
            error: function (xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Error', 'error'); }
        });
    });

    // ---- Calibrar: confirmar finalización ----
    $('#btn-confirmar-finalizar').on('click', function () {
        const idEquipo      = $('#finalizar-id-equipo').val();
        const observacion   = $('#finalizar-observacion').val().trim();
        const fechaProxima  = $('#finalizar-fecha-proxima').val();
        if (!observacion) {
            $('#finalizar-observacion').addClass('is-invalid');
            return;
        }
        $('#finalizar-observacion').removeClass('is-invalid');
        $.ajax({
            url:         'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=finalizar_calibracion',
            type:        'POST',
            contentType: 'application/json',
            data:        JSON.stringify({ Id_Equipo: idEquipo, Observacion: observacion, Fecha_Proxima_Calibracion: fechaProxima || null }),
            dataType:    'json',
            success: function (r) {
                if (r.success) {
                    $('#modal-finalizar-calibracion').modal('hide');
                    Swal.fire({ title: '¡Calibración completada!', text: r.message, icon: 'success', timer: 2000, showConfirmButton: false })
                        .then(() => tablaEquipos.ajax.reload());
                } else {
                    Swal.fire('Error', r.message, 'error');
                }
            },
            error: function (xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Error', 'error'); }
        });
    });

    // ---- Proveedor rápido: guardar ----
    $('#btn-guardar-prov-rapido').on('click', function () {
        const razon = $('#prov-rapido-razon').val().trim();
        if (!razon) {
            $('#prov-rapido-razon').addClass('is-invalid');
            return;
        }
        $('#prov-rapido-razon').removeClass('is-invalid');
        const datos = {
            Razon_Social: razon,
            Ruc:          $('#prov-rapido-ruc').val().trim(),
            Telefono:     $('#prov-rapido-telefono').val().trim()
        };
        $.ajax({
            url:         'modules/laboratorio/proveedor/controllers/ProveedorAPI.php?action=guardar',
            type:        'POST',
            contentType: 'application/json',
            data:        JSON.stringify(datos),
            dataType:    'json',
            success: function (r) {
                if (r.success) {
                    $('#modal-proveedor-rapido').modal('hide');
                    $('#form-proveedor-rapido')[0].reset();
                    // Agregar nuevo proveedor al select y seleccionarlo
                    const opt = new Option(htmlEscape(r.proveedor.Razon_Social), r.id, true, true);
                    $('#Id_Proveedor').append(opt);
                    Swal.fire({ title: '¡Creado!', text: 'Proveedor creado y seleccionado', icon: 'success', timer: 1200, showConfirmButton: false });
                    // Volver a abrir el modal de equipo si estaba abierto
                    if (!$('#modal-equipo').hasClass('show')) {
                        new bootstrap.Modal(document.getElementById('modal-equipo')).show();
                    }
                } else {
                    Swal.fire('Error', r.message, 'error');
                }
            },
            error: function (xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Error al guardar proveedor', 'error'); }
        });
    });

    // Limpiar campo observación al cerrar modal finalizar
    $('#modal-finalizar-calibracion').on('hidden.bs.modal', function () {
        $('#finalizar-observacion').val('').removeClass('is-invalid');
        $('#finalizar-fecha-proxima').val('');
        $('#finalizar-id-equipo').val('');
        $('#finalizar-nombre-equipo').text('');
    });

    // Limpiar modal calibrar al cerrar
    $('#modal-calibrar').on('hidden.bs.modal', function () {
        $('#calibrar-id-equipo').val('');
        $('#calibrar-nombre-equipo').text('');
        $('#calibrar-id-estado').val('');
        actualizarDotEstado('calibrar-id-estado', 'dot-calibrar-id-estado');
    });

    // Limpiar modal gestionar estados al cerrar
    $('#modal-gestionar-estados').on('hidden.bs.modal', function () {
        ocultarFormEstado();
    });
});

// ============================================================
// HELPERS
// ============================================================

function htmlEscape(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function limpiarErrores(selector) {
    $(selector).find('.is-invalid').removeClass('is-invalid');
    $(selector).find('.invalid-feedback').remove();
}

function mostrarErroresEnFormulario(errores, selector) {
    limpiarErrores(selector);
    Object.keys(errores).forEach(campo => {
        const msg = errores[campo];
        if (msg) {
            const input = $(selector).find(`[name="${campo}"]`);
            if (input.length) {
                input.addClass('is-invalid');
                input.after(`<div class="invalid-feedback d-block">${htmlEscape(msg)}</div>`);
            }
        }
    });
}

// ============================================================
// CARGAR SELECTS
// ============================================================

// Mapa de colores por nombre de estado (normalizado)
var _estadoColorMap = {
    'disponible': '#2fb344',
    'correctivo': '#f76707',
    'preventivo': '#f59f00',
    'predictivo': '#206bc4'
};
function _estadoColor(nombre) {
    return _estadoColorMap[(nombre || '').toLowerCase()] || '#adb5bd';
}
function actualizarDotEstado(selectId, dotId) {
    const sel = document.getElementById(selectId);
    const dot = document.getElementById(dotId);
    if (!sel || !dot) return;
    const opt = sel.options[sel.selectedIndex];
    dot.style.background = (opt && opt.dataset.color) ? opt.dataset.color : '#adb5bd';
}

function cargarSelectEstados() {
    $.ajax({
        url: 'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=listar_estados',
        type: 'GET', dataType: 'json', timeout: 5000,
        success: function (r) {
            if (r.success && Array.isArray(r.data)) {
                let opts = '<option value="" data-color="">Seleccionar estado...</option>';
                r.data.forEach(e => {
                    const c = _estadoColor(e.Nombre);
                    opts += `<option value="${e.Id_Estado}" data-color="${c}">${htmlEscape(e.Nombre)}</option>`;
                });
                $('#Id_Estado').html(opts);
                actualizarDotEstado('Id_Estado', 'dot-Id_Estado');
            }
        }
    });
}

function cargarSelectProveedores(seleccionarId) {
    $.ajax({
        url: 'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=listar_proveedores',
        type: 'GET', dataType: 'json', timeout: 5000,
        success: function (r) {
            if (r.success && Array.isArray(r.data)) {
                let opts = '<option value="">Sin proveedor registrado</option>';
                r.data.forEach(p => { opts += `<option value="${p.Id_Proveedor}">${htmlEscape(p.Razon_Social)}</option>`; });
                $('#Id_Proveedor').html(opts);
                if (seleccionarId) $('#Id_Proveedor').val(seleccionarId);
            }
        }
    });
}

function cargarSelectEstadosCalib() {
    $.ajax({
        url: 'modules/laboratorio/equipo/controllers/EquipoAPI.php?action=listar_estados',
        type: 'GET', dataType: 'json', timeout: 5000,
        success: function (r) {
            if (r.success && Array.isArray(r.data)) {
                let opts = '<option value="" data-color="">Seleccionar estado...</option>';
                r.data.forEach(e => {
                    if (e.Nombre.toLowerCase() !== 'disponible') {
                        const c = _estadoColor(e.Nombre);
                        opts += `<option value="${e.Id_Estado}" data-color="${c}">${htmlEscape(e.Nombre)}</option>`;
                    }
                });
                $('#calibrar-id-estado').html(opts);
                actualizarDotEstado('calibrar-id-estado', 'dot-calibrar-id-estado');
            }
        }
    });
}

// ============================================================
// EQUIPOS — CRUD
// ============================================================

function validarNombreEquipo(nombre) {
    nombre = nombre.trim();
    if (!nombre)           return 'El nombre del equipo es obligatorio';
    if (nombre.length < 3) return 'El nombre debe tener al menos 3 caracteres';
    if (nombre.length > 100) return 'El nombre no puede exceder 100 caracteres';
    if (!/[a-zA-ZáéíóúñÁÉÍÓÚÑ]/i.test(nombre)) return 'El nombre debe contener al menos una letra';
    return null;
}

function validarFecha(fecha) {
    if (!fecha) return null;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return 'Formato de fecha inválido';
    if (isNaN(new Date(fecha + 'T00:00:00').getTime())) return 'Fecha no válida';
    return null;
}

function validarRangoFechas(fechaUltima, fechaProxima) {
    if (!fechaUltima || !fechaProxima) return null;
    if (new Date(fechaProxima) < new Date(fechaUltima)) {
        return 'La Fecha Próxima Calibración no puede ser anterior a la Fecha Última Calibración';
    }
    return null;
}

function guardarEquipo() {
    const id           = $('#Id_Equipo').val();
    const nombre       = $('#Nombre').val();
    const idEstado     = $('#Id_Estado').val();
    const idProveedor  = $('#Id_Proveedor').val();
    const fechaAdq     = $('#Fecha_Adquisicion').val();
    const fechaUltima  = $('#Fecha_Ultima_Calibracion').val();
    const fechaProxima = $('#Fecha_Proxima_Calibracion').val();

    const errores = {};
    errores['Nombre']      = validarNombreEquipo(nombre);
    errores['Id_Estado']   = (!idEstado || idEstado === '') ? 'Debe seleccionar un estado' : null;
    errores['Fecha_Adquisicion']          = validarFecha(fechaAdq);
    errores['Fecha_Ultima_Calibracion']   = validarFecha(fechaUltima);
    errores['Fecha_Proxima_Calibracion']  = validarFecha(fechaProxima);
    errores['fechas']      = validarRangoFechas(fechaUltima, fechaProxima);

    const errFiltrados = Object.fromEntries(Object.entries(errores).filter(([, v]) => v !== null));
    if (Object.keys(errFiltrados).length > 0) {
        mostrarErroresEnFormulario(errFiltrados, '#form-equipo');
        Swal.fire('Campos inválidos', 'Revise los campos marcados', 'warning');
        return;
    }

    const datos = {
        Id_Equipo:                id || null,
        Nombre:                   nombre,
        Id_Estado:                idEstado,
        Id_Proveedor:             idProveedor || null,
        Fecha_Adquisicion:        fechaAdq,
        Fecha_Ultima_Calibracion: fechaUltima,
        Fecha_Proxima_Calibracion: fechaProxima
    };

    const action = id ? 'actualizar' : 'guardar';
    $.ajax({
        url:         `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=${action}`,
        type:        'POST',
        contentType: 'application/json',
        data:        JSON.stringify(datos),
        dataType:    'json',
        success: function (r) {
            if (r.success) {
                $('#modal-equipo').modal('hide');
                Swal.fire({ title: '¡Guardado!', text: r.message, icon: 'success', timer: 1500, showConfirmButton: false })
                    .then(() => tablaEquipos.ajax.reload());
            } else {
                if (r.errors) mostrarErroresEnFormulario(r.errors, '#form-equipo');
                Swal.fire('Error', r.message, 'warning');
            }
        },
        error: function (xhr) {
            const r = xhr.responseJSON || {};
            if (r.errors) mostrarErroresEnFormulario(r.errors, '#form-equipo');
            Swal.fire('Error', r.message || 'Error al guardar', 'error');
        }
    });
}

function editarEquipo(id) {
    $.ajax({
        url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=obtener&id=${id}`,
        type: 'GET', dataType: 'json',
        success: function (r) {
            if (r.success) {
                const e = r.data;
                $('#Id_Equipo').val(e.Id_Equipo);
                $('#Nombre').val(e.Nombre || '');
                $('#Fecha_Adquisicion').val(e.Fecha_Adquisicion || '');
                $('#Fecha_Ultima_Calibracion').val(e.Fecha_Ultima_Calibracion || '');
                $('#Fecha_Proxima_Calibracion').val(e.Fecha_Proxima_Calibracion || '');
                $('#Id_Estado').val(e.Id_Estado);
                cargarSelectProveedores(e.Id_Proveedor);
                // Seleccionar estado y actualizar dot
                $('#Id_Estado').val(e.Id_Estado);
                actualizarDotEstado('Id_Estado', 'dot-Id_Estado');
                $('#modal-titulo').text('Editar Equipo');
                new bootstrap.Modal(document.getElementById('modal-equipo')).show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el equipo', 'error');
            }
        }
    });
}

function eliminarEquipo(id) {
    Swal.fire({ title: '¿Eliminar equipo?', text: 'Esta acción lo desactivará', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=eliminar&id=${id}`,
                type: 'GET', dataType: 'json',
                success: function (r) {
                    if (r.success) {
                        Swal.fire('Eliminado', r.message, 'success').then(() => tablaEquipos.ajax.reload());
                    } else {
                        Swal.fire('No permitido', r.message, 'warning');
                    }
                },
                error: function (xhr) {
                    Swal.fire('No permitido', xhr.responseJSON?.message || 'Error', 'warning');
                }
            });
        }
    });
}

function reactivarEquipo(id) {
    Swal.fire({ title: '¿Reactivar equipo?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Reactivar', cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=reactivar&id=${id}`,
                type: 'GET', dataType: 'json',
                success: function (r) {
                    if (r.success) {
                        Swal.fire('Reactivado', r.message, 'success').then(() => tablaEquipos.ajax.reload());
                    } else {
                        Swal.fire('Error', r.message, 'error');
                    }
                }
            });
        }
    });
}

// ============================================================
// CALIBRACIÓN
// ============================================================

function iniciarCalibracion(idEquipo, nombreEquipo) {
    $('#calibrar-id-equipo').val(idEquipo);
    $('#calibrar-nombre-equipo').text(nombreEquipo);
    cargarSelectEstadosCalib();
    new bootstrap.Modal(document.getElementById('modal-calibrar')).show();
}

function finalizarCalibracion(idEquipo, nombreEquipo) {
    $('#finalizar-id-equipo').val(idEquipo);
    $('#finalizar-nombre-equipo').text(nombreEquipo);
    new bootstrap.Modal(document.getElementById('modal-finalizar-calibracion')).show();
}

function verHistorial(idEquipo, nombreEquipo) {
    $('#historial-nombre-equipo').text(nombreEquipo);
    $('#historial-tbody').html('<tr><td colspan="4" class="text-center text-muted py-4"><i class="ti ti-loader-2 me-1"></i>Cargando...</td></tr>');
    new bootstrap.Modal(document.getElementById('modal-historial')).show();
    $.ajax({
        url:      `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=historial_calibracion&id=${idEquipo}`,
        type:     'GET',
        dataType: 'json',
        success: function (r) {
            if (r.success && r.data.length > 0) {
                let html = '';
                r.data.forEach((obs, i) => {
                    html += `<tr>
                        <td class="text-muted">${i + 1}</td>
                        <td style="white-space:nowrap">${htmlEscape(obs.Fecha_Observacion)}</td>
                        <td style="white-space:pre-wrap">${htmlEscape(obs.Observacion)}</td>
                        <td class="text-muted">${htmlEscape(obs.Usuario || '-')}</td>
                    </tr>`;
                });
                $('#historial-tbody').html(html);
            } else {
                $('#historial-tbody').html('<tr><td colspan="4" class="text-center text-muted py-4">No hay observaciones registradas para este equipo.</td></tr>');
            }
        },
        error: function () {
            $('#historial-tbody').html('<tr><td colspan="4" class="text-center text-danger py-4">Error al cargar el historial.</td></tr>');
        }
    });
}

// ============================================================
// PROVEEDORES (desde equipo)
// ============================================================

function abrirModalProveedorRapido() {
    $('#form-proveedor-rapido')[0].reset();
    $('#prov-rapido-razon').removeClass('is-invalid');
    new bootstrap.Modal(document.getElementById('modal-proveedor-rapido')).show();
}

// ============================================================
// ESTADOS (desde modal gestionar)
// ============================================================

function mostrarFormEstado(id) {
    $('#form-estado')[0].reset();
    $('#Id_Estado_Edit').val('');
    limpiarErrores('#form-estado');
    if (id) {
        $.ajax({
            url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=obtener_estado&id=${id}`,
            type: 'GET', dataType: 'json',
            success: function (r) {
                if (r.success) {
                    const e = r.data;
                    $('#Id_Estado_Edit').val(e.Id_Estado);
                    $('#Nombre_Estado').val(e.Nombre || '');
                    $('#Descripcion_Estado').val(e.Descripcion || '');
                    $('#modal-estado-titulo').text('Editar Estado');
                    new bootstrap.Modal(document.getElementById('modal-estado')).show();
                } else {
                    Swal.fire('Error', 'No se pudo cargar el estado', 'error');
                }
            }
        });
    } else {
        $('#modal-estado-titulo').text('Nuevo Estado');
        new bootstrap.Modal(document.getElementById('modal-estado')).show();
    }
}

function ocultarFormEstado() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modal-estado'));
    if (modal) modal.hide();
}

function guardarEstado() {
    const id          = $('#Id_Estado_Edit').val();
    const nombre      = $('#Nombre_Estado').val().trim();
    const descripcion = $('#Descripcion_Estado').val().trim();

    if (!nombre || nombre.length < 2) {
        $('#Nombre_Estado').addClass('is-invalid');
        return;
    }
    $('#Nombre_Estado').removeClass('is-invalid');

    const datos  = { Id_Estado: id || null, Nombre: nombre, Descripcion: descripcion };
    const action = id ? 'actualizar_estado' : 'guardar_estado';

    $.ajax({
        url:         `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=${action}`,
        type:        'POST',
        contentType: 'application/json',
        data:        JSON.stringify(datos),
        dataType:    'json',
        success: function (r) {
            if (r.success) {
                ocultarFormEstado();
                tablaEstados.ajax.reload();
                cargarSelectEstados();
                Swal.fire({ title: '¡Guardado!', text: r.message, icon: 'success', timer: 1200, showConfirmButton: false });
            } else {
                if (r.errors) mostrarErroresEnFormulario(r.errors, '#form-estado');
                Swal.fire('Error', r.message, 'warning');
            }
        },
        error: function (xhr) {
            const r = xhr.responseJSON || {};
            if (r.errors) mostrarErroresEnFormulario(r.errors, '#form-estado');
            Swal.fire('Error', r.message || 'Error al guardar estado', 'error');
        }
    });
}

function editarEstado(id) {
    mostrarFormEstado(id);
}

function eliminarEstado(id) {
    Swal.fire({ title: '¿Eliminar estado?', text: 'Se desactivará si no tiene equipos asignados', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=eliminar_estado&id=${id}`,
                type: 'GET', dataType: 'json',
                success: function (r) {
                    if (r.success) {
                        Swal.fire('Eliminado', r.message, 'success').then(() => { tablaEstados.ajax.reload(); cargarSelectEstados(); });
                    } else {
                        Swal.fire('No permitido', r.message, 'warning');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.status === 409
                        ? (xhr.responseJSON?.message || 'Estado en uso por equipos activos')
                        : 'Error al eliminar';
                    Swal.fire('No permitido', msg, 'warning');
                }
            });
        }
    });
}

function reactivarEstado(id) {
    Swal.fire({ title: '¿Reactivar estado?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Reactivar', cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: `modules/laboratorio/equipo/controllers/EquipoAPI.php?action=reactivar_estado&id=${id}`,
                type: 'GET', dataType: 'json',
                success: function (r) {
                    if (r.success) {
                        Swal.fire('Reactivado', r.message, 'success').then(() => { tablaEstados.ajax.reload(); cargarSelectEstados(); });
                    } else {
                        Swal.fire('Error', r.message, 'error');
                    }
                }
            });
        }
    });
}
</script>
