<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
.dataTables_wrapper .pagination .page-link { color: #1d273b; }
.dataTables_wrapper .pagination .page-item.active .page-link { background-color: #004d99; border-color: #004d99; color: white; }
.alert-info { background-color: #e8f4f8; border-left: 4px solid #17a2b8; }
.form-section { background: #f8faf8; border: 1px solid #d7e8d8; border-radius: 10px; padding: 16px; margin-bottom: 14px; }
.summary-label { color: #607080; font-size: 12px; margin-bottom: 2px; }
.summary-value { font-weight: 600; margin-bottom: 10px; }
.nota-prototipo { background: #dff0df; border: 1px solid #c5e2c6; border-radius: 12px; padding: 16px 18px; color: #1d273b; margin-bottom: 18px; }
.nota-prototipo p { margin-bottom: 0; }
.recordatorio-seccion { background: #eef7ff; border: 1px dashed #b8d4f0; border-radius: 8px; padding: 8px 10px; font-size: 12px; color: #4a6786; margin-bottom: 12px; }
#main-nav-muestras .nav-link { font-weight: 600; padding: 0.6rem 1.2rem; }
#main-nav-muestras .nav-link.active { color: #004d99; border-bottom-color: #004d99; }
.main-tab-wrapper { border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 8px 8px; background: #fff; padding: 20px; }

/* -- Estado tabs coloring -- */
#tabs-estados-muestras .nav-link[data-bs-target="#pane-recepcionar"]        { color: #e67700; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-recepcionar"].active { background: #fff3cd; color: #e67700; border-bottom-color: #e67700; font-weight: 700; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-pendiente"]          { color: #206bc4; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-pendiente"].active   { background: #dce8fb; color: #206bc4; border-bottom-color: #206bc4; font-weight: 700; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-en-analisis"]        { color: #0ca678; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-en-analisis"].active { background: #d3f9ee; color: #0ca678; border-bottom-color: #0ca678; font-weight: 700; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-por-firmar"]         { color: #9b59b6; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-por-firmar"].active  { background: #f0e6f9; color: #9b59b6; border-bottom-color: #9b59b6; font-weight: 700; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-pasadas"]            { color: #2fb344; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-pasadas"].active     { background: #d3f9e1; color: #2fb344; border-bottom-color: #2fb344; font-weight: 700; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-rechazadas"]         { color: #d63939; }
#tabs-estados-muestras .nav-link[data-bs-target="#pane-rechazadas"].active  { background: #ffe0e0; color: #d63939; border-bottom-color: #d63939; font-weight: 700; }

/* -- Service type pills coloring -- */
#tabs-tipo-servicio .nav-link                                   { border: 1px solid #ced4da; border-radius: 20px; }
#tabs-tipo-servicio .nav-link[data-tipo-servicio="todos"]       { color: #495057; }
#tabs-tipo-servicio .nav-link[data-tipo-servicio="todos"].active { background: #6c757d; color: #fff; border-color: #6c757d; }
#tabs-tipo-servicio .nav-link[data-tipo-servicio="interno"]     { color: #206bc4; border-color: #206bc4; }
#tabs-tipo-servicio .nav-link[data-tipo-servicio="interno"].active { background: #206bc4; color: #fff; }
#tabs-tipo-servicio .nav-link[data-tipo-servicio="externo"]     { color: #e67700; border-color: #e67700; }
#tabs-tipo-servicio .nav-link[data-tipo-servicio="externo"].active { background: #e67700; color: #fff; }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Muestras</li>
      </ol>
    </nav>
    <div class="row g-2 align-items-center mb-2">
      <div class="col">
        <h2 class="page-title">MUESTRAS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Gestión integral de muestras: recepción, análisis, monitoreo masivo y puntos por defecto.</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">

    <ul class="nav nav-tabs" id="main-nav-muestras" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#main-tab-gestion" type="button">
          <i class="ti ti-test-pipe me-1"></i> Gestión de Muestras
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#main-tab-masiva" type="button">
          <i class="ti ti-layout-grid me-1"></i> Creación Masiva
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#main-tab-defecto" type="button">
          <i class="ti ti-flash me-1"></i> Por Defecto
        </button>
      </li>
    </ul>

    <div class="main-tab-wrapper">
      <div class="tab-content" id="main-tab-content">

        <!-- ===== TAB 1: GESTIÓN DE MUESTRAS ===== -->
        <div class="tab-pane fade show active" id="main-tab-gestion" role="tabpanel">

          <div class="d-flex gap-2 mb-3">
            <?php if (!empty($permisos['crear'])): ?>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal-crear-muestra-comun">
              <i class="ti ti-plus me-2"></i> Creación Individual
            </button>
            <?php endif; ?>
          </div>

          <div class="alert alert-info mb-3">
            Para ingresos ordinarios diarios use <strong>Creación Individual</strong>. Para monitoreos extensos use <strong>Creación Masiva</strong>. Asegúrese de verificar la integridad del envase antes de confirmar la recepción.
          </div>

          <div id="tabla-error-global" class="alert alert-danger d-none"></div>

          <div class="mb-3">
            <ul class="nav nav-pills" id="tabs-tipo-servicio">
              <li class="nav-item"><button class="nav-link active" type="button" data-tipo-servicio="todos">Todos</button></li>
              <li class="nav-item"><button class="nav-link" type="button" data-tipo-servicio="interno">Interno</button></li>
              <li class="nav-item"><button class="nav-link" type="button" data-tipo-servicio="externo">Externo</button></li>
            </ul>
          </div>

          <ul class="nav nav-tabs mb-3" id="tabs-estados-muestras">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-recepcionar">Por Recepcionar</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-pendiente">Pendiente a Análisis</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-en-analisis">En Análisis</button></li>
            <?php if (!empty($permisos['firmar'])): ?>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-por-firmar">Por Firmar</button></li>
            <?php endif; ?>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-pasadas">Muestras Pasadas</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-rechazadas">Muestras Rechazadas</button></li>
          </ul>

          <div class="tab-content">
            <div class="tab-pane fade show active" id="pane-recepcionar">
              <p class="text-muted small">Registros que requieren revisión previa al inicio del análisis.</p>
              <div class="table-responsive">
                <table id="tabla-recepcionar" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>No</th><th>Agricultor</th><th>Coordenadas</th><th>Valle</th><th>Fecha de Toma</th><th>Tipo de Servicio</th><th>Tipo de Muestra</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="pane-pendiente">
              <p class="text-muted small">Muestras recepcionadas pendientes de pasar a análisis.</p>
              <div class="table-responsive">
                <table id="tabla-pendientes" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>No</th><th>Agricultor</th><th>Coordenadas</th><th>Valle</th><th>Fecha de Recepción</th><th>Tipo de Servicio</th><th>Estado</th><th>Tipo de Muestra</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="pane-en-analisis">
              <p class="text-muted small">Muestras en ejecución de análisis.</p>
              <div class="table-responsive">
                <table id="tabla-en-analisis" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>ID</th><th>Agricultor</th><th>Valle</th><th>Fecha Recepción</th><th>Tipo de Servicio</th><th>Tipo de Muestra</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <?php if (!empty($permisos['firmar'])): ?>
            <div class="tab-pane fade" id="pane-por-firmar">
              <p class="text-muted small">Muestras listas para firma técnica.</p>
              <div class="table-responsive">
                <table id="tabla-por-firmar" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>ID</th><th>Agricultor</th><th>Valle</th><th>Tipo de Servicio</th><th>Fecha de Análisis</th><th>Estado</th><th>Tipo de Muestra</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <?php endif; ?>
            <div class="tab-pane fade" id="pane-pasadas">
              <p class="text-muted small">Muestras finalizadas para revisión histórica.</p>
              <div class="table-responsive">
                <table id="tabla-muestras-pasadas" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>ID</th><th>Agricultor</th><th>Valle</th><th>Tipo de Servicio</th><th>Fecha de Análisis</th><th>Fecha de Firma</th><th>Estado</th><th>Tipo de Muestra</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="pane-rechazadas">
              <p class="text-muted small">Muestras rechazadas. Los reactivos consumidos han sido restaurados al inventario.</p>
              <div class="table-responsive">
                <table id="tabla-rechazadas" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>ID</th><th>Agricultor</th><th>Valle</th><th>Tipo de Servicio</th><th>Fecha Análisis</th><th>Fecha Rechazo</th><th>Motivo</th><th>Tipo de Muestra</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB 2: CREACIÓN MASIVA ===== -->
        <div class="tab-pane fade" id="main-tab-masiva" role="tabpanel">

          <div class="d-flex gap-2 mb-3">
            <?php if (!empty($permisos['crear'])): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-nuevo-periodo">
              <i class="ti ti-plus me-2"></i> Crear Período
            </button>
            <button type="button" class="btn btn-primary" id="btn-sincronizar-pg" onclick="sincronizarDesdePostgreSQL()">
              <i class="ti ti-database-import me-2"></i> Extraer desde PostgreSQL
            </button>
            <button type="button" class="btn btn-secondary" id="btn-extraer-calidad" onclick="extraerCalidad()">
              <i class="ti ti-droplet me-2"></i> Extraer Calidad
            </button>
            <button type="button" class="btn btn-danger" id="btn-historial-calidad" onclick="importarHistorialCalidad()">
              <i class="ti ti-history me-2"></i> Importar Historial Calidad
            </button>
            <button type="button" class="btn btn-outline-dark" id="btn-mapeo-calidad" onclick="abrirMapeoCalidad()">
              <i class="ti ti-settings me-2"></i> Mapeo de Calidad
            </button>
            <?php endif; ?>
          </div>

          <div class="alert alert-info mb-3">
            Para la creación de las muestras (hasta +50 registros simultáneos), solo es necesario ingresar el <strong>Valle</strong> y el nombre del <strong>Agricultor</strong>. El sistema habilitará un <strong>período único</strong> donde se ingresarán posteriormente todos los resultados analíticos de forma centralizada.
          </div>

          <ul class="nav nav-tabs mb-3" id="tabs-periodos-tipo">
            <li class="nav-item"><button class="nav-link active" id="tab-btn-monitoreo" data-bs-toggle="tab" data-bs-target="#pane-periodos-monitoreo">Monitoreo</button></li>
            <li class="nav-item"><button class="nav-link" id="tab-btn-calidad" data-bs-toggle="tab" data-bs-target="#pane-periodos-calidad">Calidad Superficial</button></li>
            <li class="nav-item"><button class="nav-link" id="tab-btn-drenes" data-bs-toggle="tab" data-bs-target="#pane-periodos-drenes">Calidad Drenes</button></li>
          </ul>

          <div class="tab-content">
            <div class="tab-pane fade show active" id="pane-periodos-monitoreo">
              <div class="table-responsive">
                <table id="tabla-periodos-monitoreo" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>No</th><th>Nombre Proyecto</th><th>Valle</th><th>Temporada</th><th>Fecha de Inicio</th><th>Estado</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="pane-periodos-calidad">
              <div class="table-responsive">
                <table id="tabla-periodos-calidad" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>No</th><th>Nombre Proyecto</th><th>Valle</th><th>Temporada</th><th>Fecha de Inicio</th><th>Estado</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="pane-periodos-drenes">
              <div class="table-responsive">
                <table id="tabla-periodos-drenes" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>No</th><th>Nombre Proyecto</th><th>Valle</th><th>Temporada</th><th>Fecha de Inicio</th><th>Estado</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB 3: POR DEFECTO ===== -->
        <div class="tab-pane fade" id="main-tab-defecto" role="tabpanel">

          <div class="d-flex gap-2 mb-3">
            <?php if (!empty($permisos['crear'])): ?>
            <button id="btn-crear-muestra-def" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-crear-muestra">
              <i class="ti ti-plus me-2"></i> Crear Muestra Por Defecto
            </button>
            <?php endif; ?>
            <?php if (!empty($permisos['editar']) || !empty($permisos['firmar'])): ?>
            <button type="button" class="btn btn-success" id="btn-realizar-analisis">
              <i class="ti ti-file-analytics me-1"></i> Realizar Análisis
            </button>
            <?php endif; ?>
          </div>

          <div class="nota-prototipo mb-3">
            <p>La creación de una muestra por defecto genera una <strong>plantilla preconfigurada</strong>. Permite agilizar la recepción y duplicar la información técnica en bitácora sin volver a ingresarla manualmente.</p>
          </div>

          <ul class="nav nav-tabs mb-3" id="tabs-defecto-tipo">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-defecto-originales">Originales</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-defecto-analisis">En Análisis</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-defecto-bitacoras">Bitácoras</button></li>
          </ul>

          <div class="tab-content">
            <div class="tab-pane fade show active" id="pane-defecto-originales">
              <p class="text-muted small mb-3">Muestras originales activas e inactivas para permitir su reactivación.</p>
              <div class="table-responsive">
                <table id="tabla-muestras-defecto" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr>
                    <th style="width:36px;"><input type="checkbox" class="form-check-input" id="chk-todos-muestras"></th>
                    <th>No</th><th>Ubicación del punto</th><th>Punto de toma</th><th>Coordenadas</th><th>Valle</th><th>Fecha Creación</th><th>Tipo de Muestra</th><th>Turno</th><th>Estado</th><th>Acción</th>
                  </tr></thead>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="pane-defecto-analisis">
              <p class="text-muted small mb-3">Muestras duplicadas con turno asignado y muestra original vinculada.</p>
              <div class="table-responsive">
                <table id="tabla-muestras-defecto-analisis" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>No</th><th>Bitácora</th><th>ID Original</th><th>Ubicación del punto</th><th>Punto de toma</th><th>Coordenadas</th><th>Valle</th><th>Fecha Creación</th><th>Tipo de Muestra</th><th>Turno</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="pane-defecto-bitacoras">
              <p class="text-muted small mb-3">Visualiza por fecha las bitácoras de mañana/tarde.</p>
              <div class="row g-2 mb-3">
                <div class="col-md-3"><label class="form-label">Fecha desde</label><input type="date" id="filtro_fecha_desde_bitacora" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Fecha hasta</label><input type="date" id="filtro_fecha_hasta_bitacora" class="form-control"></div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                  <button type="button" class="btn btn-primary" id="btn-filtrar-bitacoras"><i class="ti ti-filter me-1"></i> Filtrar</button>
                  <?php if (!empty($permisos['exportar'])): ?>
                  <button type="button" class="btn btn-success" id="btn-exportar-bitacoras-rango"><i class="ti ti-file-spreadsheet me-1"></i> Exportar rango</button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="table-responsive">
                <table id="tabla-bitacoras-defecto" class="table table-vcenter card-table table-striped" style="width:100%">
                  <thead><tr><th>Fecha</th><th>Mañana</th><th>Obs. Mañana</th><th>Tarde</th><th>Obs. Tarde</th><th>Acción</th></tr></thead>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div><!-- end tab-content -->
    </div><!-- end main-tab-wrapper -->

  </div>
</div>

<!-- ===== MODALES GESTIÓN ===== -->
<div class="modal modal-blur fade" id="modal-crear-muestra-comun" tabindex="-1" role="dialog" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Creación Individual de Muestra Común</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 px-3 mb-3">Estado inicial: <strong>Pendiente</strong>. Sin punto de toma ni ubicación.</div>
        <div id="alerta-crear-individual" class="alert alert-danger d-none"></div>
        <form id="form-crear-muestra-individual" class="row g-3" onsubmit="return false;">
          <div class="col-md-4">
            <label class="form-label">Tipo de servicio <span class="text-danger">*</span></label>
            <select id="ci_tipo_servicio" class="form-select" required>
              <option value="Interno">Interno</option><option value="Externo">Externo</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Agricultor / Cliente <span class="text-danger">*</span></label>
            <div class="d-flex gap-1 align-items-center">
              <select id="ci_id_cliente" class="form-select" required><option value="">Seleccione...</option></select>
              <button type="button" class="btn btn-outline-success btn-sm flex-shrink-0" onclick="abrirCrearClienteRapido()" title="Nuevo Cliente"><i class="ti ti-plus"></i></button>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Valle <span class="text-danger">*</span></label>
            <select id="ci_valle" class="form-select" required><option value="">Seleccione...</option></select>
            <input type="text" class="form-control mt-2" id="ci_valle_otro" placeholder="Especificar valle" style="display:none;">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha de toma <span class="text-danger">*</span></label>
            <input id="ci_fecha_toma" type="date" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Eje X</label>
            <input id="ci_eje_x" type="text" class="form-control" placeholder="Opcional">
          </div>
          <div class="col-md-4">
            <label class="form-label">Eje Y</label>
            <input id="ci_eje_y" type="text" class="form-control" placeholder="Opcional">
          </div>
          <div class="col-md-6">
            <label class="form-label">Producto / paquete <span class="text-danger">*</span></label>
            <select id="ci_id_producto_venta" class="form-select" required><option value="">Seleccione...</option></select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Observación</label>
            <input id="ci_observacion" type="text" class="form-control" placeholder="Opcional">
          </div>
          <div class="col-md-12">
            <label class="form-label">Archivo adjunto <span class="text-muted small">(recibo imagen JPG/PNG o PDF — máx. 5 MB)</span></label>
            <input type="file" class="form-control" id="ci_adjunto" accept="image/jpeg,image/png,image/jpg,application/pdf">
            <div id="ci_adjunto_preview" class="mt-2 d-none"></div>
          </div>
          <div class="col-md-12">
            <label class="form-label d-block mb-2">Tipo de muestra <span class="text-danger">*</span></label>
            <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="ci_tipo_muestra" value="Agua" checked><span class="form-check-label">Agua</span></label>
            <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="ci_tipo_muestra" value="Suelo"><span class="form-check-label">Suelo</span></label>
          </div>
          <div id="ci_bloque_agua" class="row g-3">
            <div class="col-md-3"><label class="form-label">Uso de agua</label><select id="ci_uso_agua" class="form-select"><option value="">Seleccionar</option><option>Consumo Humano</option><option>Riego</option><option>Industrial</option><option>Otro</option></select></div>
            <div class="col-md-3"><label class="form-label">Tipo</label><select id="ci_fuente_agua" class="form-select"><option value="">Seleccionar</option><option value="Subterráneo">Subterráneo</option><option value="Superficial">Superficial</option></select></div>
            <div class="col-md-3"><label class="form-label">Fuente</label><select id="ci_nivel_agua" class="form-select" onchange="toggleNivelAguaOtro('ci_nivel_agua','ci_nivel_agua_otro')"><option value="">Seleccionar</option><option value="Rio">Rio</option><option value="Pozo">Pozo</option><option value="Canal">Canal</option><option value="Reservorio">Reservorio</option><option value="Otros">Otros</option></select><input type="text" id="ci_nivel_agua_otro" class="form-control mt-1" placeholder="Especificar fuente" style="display:none;"></div>
            <div class="col-md-3"><label class="form-label">Cantidad</label><input id="ci_cantidad_agua" type="text" class="form-control" value="1 Litro"></div>
          </div>
          <div id="ci_bloque_suelo" class="row g-3 d-none">
            <div class="col-md-3"><label class="form-label">Fuente de riego</label><input id="ci_fuente_riego" type="text" class="form-control" placeholder="Opcional"></div>
            <div class="col-md-3"><label class="form-label">Profundidad</label><select id="ci_profundidad" class="form-select"><option value="">Seleccionar</option><option>30 CM</option><option>60 CM</option><option>90 CM</option></select></div>
            <div class="col-md-3"><label class="form-label">Nro submuestras</label><input id="ci_numero_submuestras" type="number" min="1" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Cantidad</label><input id="ci_cantidad_suelo" type="text" class="form-control" value="1 Kg"></div>
            <div class="col-md-4"><label class="form-label">Cultivo Anterior</label><input id="ci_cultivo_anterior" type="text" class="form-control" placeholder="Opcional"></div>
            <div class="col-md-4"><label class="form-label">Cultivo Implementado</label><input id="ci_cultivo_implementado" type="text" class="form-control" placeholder="Opcional"></div>
            <div class="col-md-4"><label class="form-label">Cultivo Por Implementar</label><input id="ci_cultivo_por_implementar" type="text" class="form-control" placeholder="Opcional"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-individual-modal"><i class="ti ti-device-floppy me-1"></i> Guardar muestra</button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-iniciar-analisis" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-inicio-titulo">Confirmación para Análisis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1" id="modal-inicio-mensaje">¿Está seguro de que desea comenzar el análisis?</p>
        <div class="text-danger small mb-2" id="modal-inicio-detalle">* Se registrará al analista.</div>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" id="chk-iniciar-todos" checked>
          <span class="form-check-label text-success" id="lbl-iniciar-todos">Comenzar todos los análisis pendientes del agricultor</span>
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-inicio-analisis">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-firmar-muestra" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Firma de Análisis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">¿Registrar firma para la muestra <strong id="firma-id-muestra">-</strong>?</p>
        <label class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="chk-firmar-todos" checked>
          <span class="form-check-label text-success">Firmar todas las muestras del agricultor en estado Por Firmar</span>
        </label>
        <div class="alert alert-info py-2 px-3 small mb-2" id="firma-resumen">Cargando detalle...</div>
        <div class="table-responsive" style="max-height: 220px; overflow:auto;">
          <table class="table table-sm table-striped mb-0">
            <thead><tr><th>ID Muestra</th><th>Servicio</th><th>Parámetro</th><th>Valor</th></tr></thead>
            <tbody id="tbody-detalle-firma"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <?php if (!empty($permisos['firmar'])): ?>
        <button type="button" class="btn btn-success" id="btn-confirmar-firma">Firmar y Finalizar</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODALES CREACIÓN MASIVA ===== -->
<div class="modal modal-blur fade" id="modal-nuevo-periodo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      <div class="modal-header"><h5 class="modal-title">Nuevo Período de Monitoreo</h5></div>
      <div class="modal-body">
        <form id="form-nuevo-periodo">
          <div class="mb-3">
            <label class="form-label">Nombre del Proyecto <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nombre-proyecto" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Valle <span class="text-danger">*</span></label>
              <select class="form-select" id="select-valle" required>
                <option value="">Seleccionar...</option>
                <option value="Virú">Virú</option><option value="Moche">Moche</option>
                <option value="Chicama">Chicama</option><option value="Chao">Chao</option>
                <option value="Otros">Otros (Especificar)</option>
              </select>
              <input type="text" class="form-control mt-2" id="valle-otro" placeholder="Especificar valle" style="display:none;">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="fecha-inicio" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Temporada <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="temporada" placeholder="2026-I" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo de Muestra <span class="text-danger">*</span></label>
              <select class="form-select" id="tipo-muestra" required>
                <option value="">Seleccionar...</option>
                <option value="Agua" selected>Agua</option><option value="Suelo">Suelo</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="check-control-calidad">
              <span class="form-check-label">Proyecto de calidad de agua</span>
            </label>
            <small id="info-control-calidad" class="text-muted d-block mt-1">Si se activa, el sistema exige al menos 10 muestras planificadas por servicio.</small>
          </div>
          <div class="mb-3">
            <label class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="check-drene">
              <span class="form-check-label">Proyecto de Drenes</span>
            </label>
            <small id="info-drene" class="text-muted d-block mt-1">Si se activa, se usarán fuentes tipo dren y el campo Es_Drene se marcará en cada muestra creada.</small>
          </div>
          <div id="campos-agua">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Uso de Agua <span class="text-danger">*</span></label>
                <select class="form-select" id="select-uso-agua">
                  <option value="">Seleccionar...</option>
                  <option value="Riego">Riego</option><option value="Consumo humano">Consumo humano</option><option value="Animal">Animal</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                <select class="form-select" id="select-fuente">
                  <option value="">Seleccionar...</option>
                  <option value="Subterráneo">Subterráneo</option><option value="Superficial">Superficial</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Fuente <span class="text-danger">*</span></label>
              <select class="form-select" id="select-nivel-agua" onchange="$('#nivel-agua-otra').toggle($(this).val()==='Otros'); if($(this).val()!=='Otros') $('#nivel-agua-otra').val('');">
                <option value="">Seleccionar...</option>
                <option value="Rio">Río</option><option value="Pozo">Pozo</option><option value="Canal">Canal</option><option value="Reservorio">Reservorio</option><option value="Otros">Otros</option>
              </select>
              <input type="text" class="form-control mt-2" id="nivel-agua-otra" placeholder="Especificar fuente" style="display:none;">
          </div>
          <div class="mb-3">
            <label class="form-label">Productos/Servicios de Monitoreo <span class="text-danger">*</span></label>
            <select class="form-control" id="select-servicios"><option value="">Seleccionar servicio...</option></select>
            <small class="text-muted d-block mt-2">Especifique la cantidad de muestras planificadas para cada servicio</small>
            <div class="table-responsive mt-2">
              <table class="table table-sm table-bordered">
                <thead class="bg-light"><tr><th style="width:60%;">Servicio</th><th style="width:25%;" class="text-center">Cantidad Planificada</th><th style="width:15%;" class="text-center">Acción</th></tr></thead>
                <tbody id="tabla-servicios-tbody"></tbody>
              </table>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-crear-periodo">Crear Período</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODALES POR DEFECTO ===== -->
<div class="modal modal-blur fade" id="modal-crear-muestra" tabindex="-1" role="dialog" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-crear-muestra-titulo">Crear Muestra Original Por Defecto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-muestra-defecto">
          <input type="hidden" id="id_muestra_edicion" value="">
          <div class="form-section">
            <h4 class="mb-3">Datos generales</h4>
            <div class="recordatorio-seccion">Complete los datos base de la muestra para reutilizarla como plantilla de duplicación.</div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Agricultor</label><select id="id_cliente_def" class="form-select"><option value="">Sin agricultor (opcional)</option></select></div>
              <div class="col-md-3"><label class="form-label">Valle <span class="text-danger">*</span></label>
                <select id="valle_def" class="form-select" required><option value="">Seleccione valle</option></select>
                <input type="text" class="form-control mt-2" id="valle_def_otro" placeholder="Especificar valle" style="display:none;">
              </div>
              <div class="col-md-3"><label class="form-label">Fecha <span class="text-danger">*</span></label><input id="fecha_registro_def" type="date" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Ubicación del punto <span class="text-danger">*</span></label><input id="ubicacion_punto_def" type="text" class="form-control" placeholder="Ej: PTA del Campamento San José" required></div>
              <div class="col-md-6"><label class="form-label">Punto de toma <span class="text-danger">*</span></label><input id="punto_toma_def" type="text" class="form-control" placeholder="Ej: Poza clorada" required></div>
              <div class="col-md-3"><label class="form-label">Coordenada X</label><input id="eje_x_def" type="text" class="form-control" placeholder="x:"></div>
              <div class="col-md-3"><label class="form-label">Coordenada Y</label><input id="eje_y_def" type="text" class="form-control" placeholder="y:"></div>
              <div class="col-md-6"><label class="form-label">Observacion</label><input id="observacion_def" type="text" class="form-control" placeholder="Opcional"></div>
            </div>
          </div>
          <div class="form-section">
            <h4 class="mb-3">Tipo de muestra</h4>
            <div class="mb-3">
              <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tipo_muestra_def" value="Agua" checked><span class="form-check-label">Agua</span></label>
              <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tipo_muestra_def" value="Suelo"><span class="form-check-label">Suelo</span></label>
            </div>
            <div id="bloque-agua-def" class="row g-3">
              <div class="col-md-3"><label class="form-label">Uso de agua</label><select id="uso_agua_def" class="form-select"><option value="">Seleccionar</option><option>Consumo Humano</option><option>Riego</option><option>Industrial</option><option>Otro</option></select></div>
              <div class="col-md-3"><label class="form-label">Tipo</label><select id="fuente_agua_def" class="form-select"><option value="">Seleccionar</option><option value="Subterráneo">Subterráneo</option><option value="Superficial">Superficial</option></select></div>
              <div class="col-md-3"><label class="form-label">Fuente</label><select id="nivel_agua_def" class="form-select" onchange="toggleNivelAguaOtro('nivel_agua_def','nivel_agua_def_otro')"><option value="">Seleccionar</option><option value="Rio">Rio</option><option value="Pozo">Pozo</option><option value="Canal">Canal</option><option value="Reservorio">Reservorio</option><option value="Otros">Otros</option></select><input type="text" id="nivel_agua_def_otro" class="form-control mt-1" placeholder="Especificar fuente" style="display:none;"></div>
              <div class="col-md-3"><label class="form-label">Cantidad</label><input id="cantidad_agua_def" type="text" class="form-control" value="1 Litro"></div>
            </div>
            <div id="bloque-suelo-def" class="row g-3 d-none">
              <div class="col-md-4"><label class="form-label">Fuente de riego</label><input id="fuente_riego_def" type="text" class="form-control"></div>
              <div class="col-md-3"><label class="form-label">Profundidad</label><select id="profundidad_def" class="form-select"><option value="">Seleccionar</option><option>30 CM</option><option>60 CM</option><option>90 CM</option></select></div>
              <div class="col-md-2"><label class="form-label">Nro Submuestras</label><input id="numero_submuestras_def" type="number" min="1" class="form-control"></div>
              <div class="col-md-3"><label class="form-label">Cantidad</label><input id="cantidad_suelo_def" type="text" class="form-control" value="1 Kg"></div>
              <div class="col-md-4"><label class="form-label">Cultivo Anterior</label><input id="cultivo_anterior_def" type="text" class="form-control" placeholder="Opcional"></div>
              <div class="col-md-4"><label class="form-label">Cultivo Implementado</label><input id="cultivo_implementado_def" type="text" class="form-control" placeholder="Opcional"></div>
              <div class="col-md-4"><label class="form-label">Cultivo Por Implementar</label><input id="cultivo_por_implementar_def" type="text" class="form-control" placeholder="Opcional"></div>
            </div>
          </div>
          <div class="form-section mb-0">
            <h4 class="mb-3">Paquete de servicios</h4>
            <div class="recordatorio-seccion">Este producto/paquete se usará cuando se duplique la muestra por defecto.</div>
            <div class="col-md-12">
              <label class="form-label">Producto / paquete para duplicación <span class="text-danger">*</span></label>
              <select id="select-servicio-def" class="form-select" required><option value="">Seleccione servicio</option></select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-abrir-confirmacion">Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-confirmar-guardado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Confirmar guardado</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="summary-label">Agricultor</div><div class="summary-value" id="resumen-agricultor">-</div>
        <div class="row">
          <div class="col-6"><div class="summary-label">Valle</div><div class="summary-value" id="resumen-valle">-</div></div>
          <div class="col-6"><div class="summary-label">Tipo de muestra</div><div class="summary-value" id="resumen-tipo">-</div></div>
        </div>
        <div class="summary-label">Ubicación del punto</div><div class="summary-value" id="resumen-ubicacion">-</div>
        <div class="summary-label">Punto de toma</div><div class="summary-value" id="resumen-punto">-</div>
        <div class="summary-label">Producto / paquete</div><div class="summary-value" id="resumen-servicios">-</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-guardado">Confirmar y Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-duplicar-muestras" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Generar Duplicados Por Turno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="recordatorio-seccion">Se creará una bitácora con la fecha y turno elegidos, y se generarán duplicados de las muestras seleccionadas.</div>
        <div class="mb-3">
          <label class="form-label">Fecha de registro <span class="text-danger">*</span></label>
          <input type="date" id="fecha_duplicacion" class="form-control">
        </div>
        <div>
          <label class="form-label d-block">Turno <span class="text-danger">*</span></label>
          <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="turno_duplicacion" value="Mañana" checked><span class="form-check-label">Mañana</span></label>
          <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="turno_duplicacion" value="Tarde"><span class="form-check-label">Tarde</span></label>
        </div>

        <div class="mt-3">
          <label class="form-label d-block">Muestras a duplicar <span class="text-danger">*</span></label>
          <div class="table-responsive" style="max-height:260px; overflow:auto;">
            <table class="table table-sm table-vcenter card-table">
              <thead>
                <tr>
                  <th style="width:70px;">N°</th>
                  <th>Punto de toma</th>
                  <th style="width:130px;">No analizada</th>
                  <th>Comentario (si no se analiza)</th>
                </tr>
              </thead>
              <tbody id="lista-muestras-duplicar"></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-duplicacion">Crear Duplicados</button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-detalle-bitacora-fecha" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="titulo-detalle-bitacora-fecha">Detalle de bitácora por fecha</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"><div id="contenido-detalle-bitacora-fecha"></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ========== ESTADO GLOBAL ==========
const API_MUESTRA = 'modules/laboratorio/muestra/controllers/MuestraAPI.php';
const API_MASIVA = 'modules/laboratorio/muestra/views/creacion_masiva_api.php';

let tablasGestionInit = false;
let tablasMasivaInit = false;
let tablasDefectoInit = false;
let tablaBitacorasDefecto = null;
const muestrasSeleccionadas = new Set();
const infoMuestrasDuplicar = {};

let tipoServicioFiltro = 'todos';
let muestraInicioAnalisis = { idMuestra: 0, idCliente: 0, agricultor: '', origen: 'pendiente' };
let firmaContexto = { idMuestra: 0, idCliente: 0, agricultor: '' };
let catalogosIndividualCargados = false;
let serviciosDisponibles = [];

function toggleNivelAguaOtro(selectId, inputId) {
  const sel = document.getElementById(selectId);
  const inp = document.getElementById(inputId);
  if (!sel || !inp) return;
  inp.style.display = sel.value === 'Otros' ? 'block' : 'none';
  if (sel.value !== 'Otros') inp.value = '';
}

function abrirCrearClienteRapido() {
  Swal.fire({
    title: 'Nuevo Cliente',
    html: `
      <div class="text-start">
        <div class="mb-2">
          <label class="form-label">DNI</label>
          <input id="swal-cli-dni" class="form-control" placeholder="DNI del cliente" maxlength="12">
        </div>
        <div class="mb-2">
          <label class="form-label">Nombres <span class="text-danger">*</span></label>
          <input id="swal-cli-nombres" class="form-control" placeholder="Nombres del cliente">
        </div>
        <div class="mb-2">
          <label class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
          <input id="swal-cli-apep" class="form-control" placeholder="Apellido paterno">
        </div>
        <div class="mb-2">
          <label class="form-label">Apellido Materno</label>
          <input id="swal-cli-apem" class="form-control" placeholder="Apellido materno">
        </div>
      </div>`,
    showCancelButton: true,
    confirmButtonText: 'Crear Cliente',
    cancelButtonText: 'Cancelar',
    preConfirm: () => {
      const dni = document.getElementById('swal-cli-dni').value.trim();
      const nombres = document.getElementById('swal-cli-nombres').value.trim();
      const apep = document.getElementById('swal-cli-apep').value.trim();
      const apem = document.getElementById('swal-cli-apem').value.trim();
      if (!nombres) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }
      if (!apep) { Swal.showValidationMessage('El apellido paterno es obligatorio'); return false; }
      return {
        Dni: dni,
        Nombres: nombres,
        Apellido_Paterno: apep,
        Apellido_Materno: apem
      };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    $.ajax({
      url: 'modules/laboratorio/proveedor/controllers/ClienteAPI.php?action=guardar',
      method: 'POST', contentType: 'application/json', dataType: 'json',
      data: JSON.stringify(result.value),
      success: function(resp) {
        if (resp.success) {
          const id = resp.id;
          const nombre = [result.value.Nombres, result.value.Apellido_Paterno, result.value.Apellido_Materno]
            .filter(Boolean).join(' ');
          // Append to all ci_id_cliente selects on page
          $('select[id$="id_cliente"]').each(function() {
            $(this).append(`<option value="${id}">${nombre}</option>`);
          });
          $('select[id$="id_cliente"]').val(id);
          Swal.fire({ title: 'Creado', text: nombre + ' registrado', icon: 'success', timer: 1200, showConfirmButton: false });
        } else { Swal.fire('Error', resp.message || 'No se pudo crear el cliente', 'error'); }
      },
      error: function(xhr) {
        const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al crear el cliente';
        Swal.fire('Error', msg, 'error');
      }
    });
  });
}

// ========== INICIALIZACIÓN ==========
$(document).ready(function () {
  // Manejo global de sesión expirada (401): redirige al login en vez de mostrar error 500
  $(document).ajaxError(function (event, jqXHR) {
    if (jqXHR && jqXHR.status === 401) {
      Swal.fire({
        icon: 'warning',
        title: 'Sesión expirada',
        text: 'Tu sesión ha caducado. Vuelve a iniciar sesión.',
        confirmButtonText: 'Ir al login'
      }).then(function () {
        window.location.href = 'index.php';
      });
    }
  });

  // Mover modales a <body> para evitar conflictos de stacking context con page-wrapper de Tabler
  document.querySelectorAll('.modal').forEach(function (modal) {
    document.body.appendChild(modal);
  });

  var urlTabParam = new URLSearchParams(window.location.search).get('tab');

  // Registrar listeners PRIMERO (antes de activar cualquier tab)
  $('[data-bs-target="#main-tab-gestion"]').on('shown.bs.tab', function () {
    if (!tablasGestionInit) initTabGestion();
  });
  $('[data-bs-target="#main-tab-masiva"]').on('shown.bs.tab', function () {
    if (!tablasMasivaInit) initTabMasiva();
  });
  $('[data-bs-target="#main-tab-defecto"]').on('shown.bs.tab', function () {
    if (!tablasDefectoInit) initTabDefecto();
  });

  // Ajustar tablas al cambiar de tab
  $('[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
  });

  // Inicializar el tab correcto según parámetro URL
  if (urlTabParam === 'masiva') {
    var btnMasiva = document.querySelector('[data-bs-target="#main-tab-masiva"]');
    if (btnMasiva) { bootstrap.Tab.getOrCreateInstance(btnMasiva).show(); }
    try { initTabMasiva(); } catch(e) { console.error('initTabMasiva:', e); }
  } else {
    try { initTabGestion(); } catch(e) { console.error('initTabGestion:', e); }
  }

  try { setupGestionEvents(); } catch(e) { console.error('setupGestionEvents:', e); }
  try { setupMasivaEvents(); } catch(e) { console.error('setupMasivaEvents:', e); }
  try { setupDefectoEvents(); } catch(e) { console.error('setupDefectoEvents:', e); }
});

// ====================================
// TAB 1: GESTIÓN
// ====================================
function initTabGestion() {
  if (tablasGestionInit) return;
  tablasGestionInit = true;

  const ajaxCfg = function (url, nombre) {
    return {
      url: url, type: 'POST',
      data: function (d) { d.tipo_servicio = tipoServicioFiltro; },
      error: function (xhr) { mostrarErrorTabla('Error cargando ' + nombre + '.'); }
    };
  };

  $('#tabla-recepcionar').DataTable({
    processing: true, serverSide: true,
    ajax: ajaxCfg('modules/laboratorio/muestra/views/data_recepcionar.php', 'Por Recepcionar'),
    columns: [
      {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},
      {data:7, orderable:false, searchable:false}
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  $('#tabla-pendientes').DataTable({
    processing: true, serverSide: true,
    ajax: ajaxCfg('modules/laboratorio/muestra/views/data_pendientes.php', 'Pendientes'),
    columns: [
      {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},{data:7},
      {data:8, orderable:false, searchable:false}
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  $('#tabla-en-analisis').DataTable({
    processing: true, serverSide: true,
    ajax: ajaxCfg('modules/laboratorio/muestra/views/data_progreso.php', 'En Análisis'),
    columns: [
      {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},
      {data:6, orderable:false, searchable:false}
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  <?php if (!empty($permisos['firmar'])): ?>
  $('#tabla-por-firmar').DataTable({
    processing: true, serverSide: true,
    ajax: ajaxCfg('modules/laboratorio/muestra/views/data_firmar.php', 'Por Firmar'),
    columns: [
      {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},
      {data:7, orderable:false, searchable:false}
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });
  <?php endif; ?>

  $('#tabla-muestras-pasadas').DataTable({
    processing: true, serverSide: true,
    ajax: ajaxCfg('modules/laboratorio/muestra/views/data_pasadas.php', 'Pasadas'),
    columns: [
      {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},{data:7},
      {data:8, orderable:false, searchable:false}
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  $('#tabla-rechazadas').DataTable({
    processing: true, serverSide: true,
    ajax: ajaxCfg('modules/laboratorio/muestra/views/data_rechazadas.php', 'Rechazadas'),
    columns: [
      {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},{data:7},
      {data:8, orderable:false, searchable:false}
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });
}

function recargarTablasGestion() {
  if (!tablasGestionInit) return;
  ['#tabla-recepcionar','#tabla-pendientes','#tabla-en-analisis','#tabla-por-firmar','#tabla-muestras-pasadas','#tabla-rechazadas'].forEach(function (id) {
    const dt = $.fn.dataTable.isDataTable(id) ? $(id).DataTable() : null;
    if (dt) dt.ajax.reload(null, false);
  });
}

function rechazarMuestra(id) {
  Swal.fire({
    title: 'Rechazar Muestra',
    html: '<p class="text-muted mb-2">Ingrese el motivo del rechazo. Los reactivos consumidos serán restaurados al inventario.</p>' +
          '<textarea id="swal-motivo-rechazo" class="form-control" rows="3" placeholder="Motivo del rechazo (obligatorio)"></textarea>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Rechazar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#d63939',
    preConfirm: function () {
      var motivo = document.getElementById('swal-motivo-rechazo').value.trim();
      if (!motivo) {
        Swal.showValidationMessage('Debe ingresar el motivo del rechazo');
        return false;
      }
      return motivo;
    }
  }).then(function (result) {
    if (!result.isConfirmed) return;
    var motivo = result.value;
    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
    $.ajax({
      url: 'modules/laboratorio/muestra/controllers/MuestraAPI.php?action=rechazar_muestra',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ id_muestra: id, motivo: motivo }),
      success: function (resp) {
        if (resp.success) {
          Swal.fire({ icon: 'success', title: 'Muestra rechazada', text: resp.message, timer: 2500, showConfirmButton: false });
          recargarTablasGestion();
        } else {
          Swal.fire('Error', resp.message || 'No se pudo rechazar la muestra', 'error');
        }
      },
      error: function (xhr) {
        var msg = 'Error al procesar la solicitud';
        try { var r = JSON.parse(xhr.responseText); msg = r.message || msg; } catch(e) {}
        Swal.fire('Error', msg, 'error');
      }
    });
  });
}

function verDetallesRechazada(id) {
  Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
  $.ajax({
    url: 'modules/laboratorio/muestra/controllers/MuestraAPI.php?action=obtener_rechazada',
    type: 'GET',
    data: { id_muestra: id },
    success: function (resp) {
      Swal.close();
      if (!resp || !resp.success) {
        Swal.fire('Error', (resp && resp.message) || 'No se pudo cargar la muestra', 'error');
        return;
      }
      var d = resp.data;
      var obs = d.Observacion_Muestra || '';
      var posRec = obs.indexOf('[RECEPCION]');
      var motivoTexto = posRec !== -1 ? obs.substring(0, posRec).trim() : obs.trim();
      var checklistHtml = '';
      if (posRec !== -1) {
        var jsonStr = obs.substring(posRec + '[RECEPCION]'.length).trim();
        try {
          var recData = JSON.parse(jsonStr);
          if (recData.items && recData.items.length) {
            checklistHtml += '<hr class="my-2"><p class="mb-1"><b>Checklist de recepción:</b></p><ul class="list-unstyled mb-0">';
            recData.items.forEach(function(item) {
              var icon = item.cumple
                ? '<span class="text-success me-1">&#10003;</span>'
                : '<span class="text-danger me-1">&#10007;</span>';
              checklistHtml += '<li>' + icon + item.item + '</li>';
            });
            checklistHtml += '</ul>';
            if (recData.fecha) {
              checklistHtml += '<p class="text-muted mt-1 mb-0" style="font-size:0.82em">Registrado: ' + recData.fecha + '</p>';
            }
          }
        } catch(e) {}
      }
      var html =
        '<table class="table table-sm table-borderless text-start mb-0">' +
        '<tr><td class="fw-bold text-nowrap" style="width:140px">ID Muestra</td><td>' + (d.Id_Muestra || '-') + '</td></tr>' +
        '<tr><td class="fw-bold">Cliente</td><td>' + (d.Agricultor || '-') + '</td></tr>' +
        '<tr><td class="fw-bold">Valle</td><td>' + (d.Valle || '-') + '</td></tr>' +
        '<tr><td class="fw-bold">Tipo</td><td>' + (d.TipoMuestra || '-') + '</td></tr>' +
        '<tr><td class="fw-bold">Servicio</td><td>' + (d.Tipo_Servicio || '-') + '</td></tr>' +
        '<tr><td class="fw-bold">Fecha rechazo</td><td>' + (d.Fecha_Rechazo || '-') + '</td></tr>' +
        '<tr><td class="fw-bold">Estado</td><td><span class="badge bg-danger">Rechazado</span></td></tr>' +
        '<tr><td class="fw-bold">Motivo</td><td>' + (motivoTexto || '<em class="text-muted">Sin motivo registrado</em>') + '</td></tr>' +
        '</table>' + checklistHtml;
      Swal.fire({
        title: 'Muestra #' + id + ' — Rechazada',
        html: html,
        width: 560,
        icon: 'info',
        confirmButtonText: 'Cerrar'
      });
    },
    error: function () { Swal.fire('Error', 'No se pudo cargar el detalle de la muestra', 'error'); }
  });
}

function modificarResultados(id, agricultor) {
  Swal.fire({
    title: 'Modificar Resultados',
    html: '<p class="text-muted mb-0">Esta muestra regresará al estado <strong>En Análisis</strong> para que pueda corregir los resultados ingresados incorrectamente.</p>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, modificar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#f59f00'
  }).then(function (result) {
    if (!result.isConfirmed) return;
    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
    $.ajax({
      url: 'modules/laboratorio/muestra/controllers/MuestraAPI.php?action=retornar_a_analisis',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ id_muestra: id }),
      success: function (resp) {
        if (resp.success) {
          window.location.href = '?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_muestra=' + id + '&agricultor=' + encodeURIComponent(agricultor || '');
        } else {
          Swal.fire('Error', resp.message || 'No se pudo retornar la muestra', 'error');
        }
      },
      error: function (xhr) {
        var msg = 'Error al procesar la solicitud';
        try { var r = JSON.parse(xhr.responseText); msg = r.message || msg; } catch(e) {}
        Swal.fire('Error', msg, 'error');
      }
    });
  });
}

function mostrarErrorTabla(msg) {
  $('#tabla-error-global').text(msg).removeClass('d-none');
}

function setupGestionEvents() {
  // Filtro tipo servicio
  $('#tabs-tipo-servicio').on('click', '[data-tipo-servicio]', function () {
    $('#tabs-tipo-servicio [data-tipo-servicio]').removeClass('active');
    $(this).addClass('active');
    tipoServicioFiltro = $(this).data('tipo-servicio') || 'todos';
    recargarTablasGestion();
  });

  // Botón guardar muestra individual
  document.getElementById('btn-guardar-individual-modal').addEventListener('click', guardarMuestraIndividual);

  // Preview archivo adjunto
  document.getElementById('ci_adjunto').addEventListener('change', function () {
    var preview = document.getElementById('ci_adjunto_preview');
    var file = this.files[0];
    preview.innerHTML = '';
    preview.classList.add('d-none');
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire('Validación', 'El archivo no debe superar los 5 MB.', 'warning');
      this.value = '';
      return;
    }
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.classList.remove('d-none');
      if (file.type === 'application/pdf') {
        preview.innerHTML = '<span class="badge bg-green text-white"><i class="ti ti-file-type-pdf me-1"></i>' + file.name + ' listo</span>';
      } else {
        preview.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="max-height:140px;" alt="preview">';
      }
    };
    reader.readAsDataURL(file);
  });

  // Limpiar adjunto al cerrar el modal
  document.getElementById('modal-crear-muestra-comun').addEventListener('hidden.bs.modal', function () {
    var fi = document.getElementById('ci_adjunto');
    var pr = document.getElementById('ci_adjunto_preview');
    if (fi) fi.value = '';
    if (pr) { pr.innerHTML = ''; pr.classList.add('d-none'); }
  });

  // Modal individual - on show load catalogs
  document.getElementById('modal-crear-muestra-comun').addEventListener('shown.bs.modal', function () {
    document.getElementById('alerta-crear-individual').classList.add('d-none');
    if (!document.getElementById('ci_fecha_toma').value) {
      document.getElementById('ci_fecha_toma').value = new Date().toISOString().split('T')[0];
    }
    toggleTipoMuestraIndividual();
    cargarCatalogosIndividual();
  });

  document.querySelectorAll('input[name="ci_tipo_muestra"]').forEach(function (r) {
    r.addEventListener('change', toggleTipoMuestraIndividual);
  });

  // Modal iniciar análisis
  document.getElementById('btn-confirmar-inicio-analisis').addEventListener('click', confirmarInicioAnalisis);

  // Modal firmar
  document.getElementById('chk-firmar-todos').addEventListener('change', cargarDetalleFirma);
  const btnConfirmarFirma = document.getElementById('btn-confirmar-firma');
  if (btnConfirmarFirma) btnConfirmarFirma.addEventListener('click', confirmarFirma);
}

function toggleTipoMuestraIndividual() {
  const tipo = (document.querySelector('input[name="ci_tipo_muestra"]:checked') || {}).value || 'Agua';
  document.getElementById('ci_bloque_agua').classList.toggle('d-none', tipo !== 'Agua');
  document.getElementById('ci_bloque_suelo').classList.toggle('d-none', tipo !== 'Suelo');
}

function cargarCatalogosIndividual() {
  if (catalogosIndividualCargados) return;
  fetch(API_MUESTRA + '?action=obtener_catalogos_por_defecto', { method: 'POST' })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) return;
      const sC = document.getElementById('ci_id_cliente');
      const sV = document.getElementById('ci_valle');
      const sP = document.getElementById('ci_id_producto_venta');
      (data.agricultores || []).forEach(function (i) {
        sC.insertAdjacentHTML('beforeend', '<option value="' + i.id + '">' + i.nombre + '</option>');
      });
      ['Chao','Virú','Moche','Chicama'].forEach(function (v) {
        sV.insertAdjacentHTML('beforeend', '<option value="' + v + '">' + v + '</option>');
      });
      sV.insertAdjacentHTML('beforeend', '<option value="Otros">Otros (Especificar)</option>');
      sV.addEventListener('change', function () {
        const otro = document.getElementById('ci_valle_otro');
        if (sV.value === 'Otros') { otro.style.display = ''; otro.required = true; }
        else { otro.style.display = 'none'; otro.required = false; otro.value = ''; }
      });
      (data.servicios || []).forEach(function (i) {
        sP.insertAdjacentHTML('beforeend', '<option value="' + i.id + '">' + i.nombre + '</option>');
      });
      catalogosIndividualCargados = true;
    });
}

async function guardarMuestraIndividual() {
  const tipo = (document.querySelector('input[name="ci_tipo_muestra"]:checked') || {}).value || 'Agua';
  const payload = {
    tipo_servicio: document.getElementById('ci_tipo_servicio').value,
    id_cliente: document.getElementById('ci_id_cliente').value,
    valle: (document.getElementById('ci_valle').value === 'Otros'
      ? document.getElementById('ci_valle_otro').value.trim()
      : document.getElementById('ci_valle').value),
    fecha_toma: document.getElementById('ci_fecha_toma').value,
    eje_x: document.getElementById('ci_eje_x').value.trim(),
    eje_y: document.getElementById('ci_eje_y').value.trim(),
    tipo_muestra: tipo,
    id_producto_venta: document.getElementById('ci_id_producto_venta').value,
    observacion: document.getElementById('ci_observacion').value.trim(),
    uso_agua: document.getElementById('ci_uso_agua').value,
    fuente_agua: document.getElementById('ci_fuente_agua').value,
    nivel_agua: (document.getElementById('ci_nivel_agua').value === 'Otros'
      ? (document.getElementById('ci_nivel_agua_otro').value.trim() || 'Otros')
      : document.getElementById('ci_nivel_agua').value),
    cantidad_agua: document.getElementById('ci_cantidad_agua').value.trim(),
    fuente_riego: document.getElementById('ci_fuente_riego').value.trim(),
    profundidad: document.getElementById('ci_profundidad').value.trim(),
    numero_submuestras: document.getElementById('ci_numero_submuestras').value,
    cantidad_suelo: document.getElementById('ci_cantidad_suelo').value.trim(),
    cultivo_anterior: document.getElementById('ci_cultivo_anterior').value.trim(),
    cultivo_implementado: document.getElementById('ci_cultivo_implementado').value.trim(),
    cultivo_por_implementar: document.getElementById('ci_cultivo_por_implementar').value.trim(),
    ruta_imagen: null
  };

  if (!payload.id_cliente || !payload.valle || !payload.fecha_toma || !payload.id_producto_venta) {
    Swal.fire('Validación', 'Complete todos los campos obligatorios.', 'warning');
    return;
  }

  // Leer archivo adjunto como base64 si fue seleccionado
  var fileInput = document.getElementById('ci_adjunto');
  if (fileInput && fileInput.files.length > 0) {
    var file = fileInput.files[0];
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire('Validación', 'El archivo adjunto no debe superar los 5 MB.', 'warning');
      return;
    }
    try {
      payload.ruta_imagen = await new Promise(function (resolve, reject) {
        var reader = new FileReader();
        reader.onload = function (e) { resolve(e.target.result); };
        reader.onerror = function () { reject(new Error('No se pudo leer el archivo')); };
        reader.readAsDataURL(file);
      });
    } catch (readErr) {
      Swal.fire('Error', readErr.message, 'error');
      return;
    }
  }

  const btn = document.getElementById('btn-guardar-individual-modal');
  btn.disabled = true;
  btn.innerHTML = '<i class="ti ti-loader me-1"></i> Guardando...';

  fetch(API_MUESTRA + '?action=guardar_muestra_individual', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
  }).then(function (r) { return r.json(); }).then(function (data) {
    if (!data.success) throw new Error(data.message || 'Error al guardar');
    bootstrap.Modal.getInstance(document.getElementById('modal-crear-muestra-comun')).hide();
    Swal.fire('Éxito', 'Muestra creada. ID: ' + (data.id_muestra || 0), 'success');
    recargarTablasGestion();
  }).catch(function (err) {
    Swal.fire('Error', err.message, 'error');
  }).finally(function () {
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Guardar muestra';
  });
}

window.abrirModalComenzarAnalisis = function (idMuestra, idCliente, agricultor) {
  muestraInicioAnalisis = { idMuestra: parseInt(idMuestra), idCliente: parseInt(idCliente), agricultor: agricultor, origen: 'pendiente' };
  document.getElementById('modal-inicio-titulo').textContent = 'Confirmación para Análisis';
  document.getElementById('modal-inicio-mensaje').textContent = '¿Desea comenzar el análisis para esta muestra?';
  document.getElementById('lbl-iniciar-todos').textContent = 'Comenzar todos los análisis pendientes del agricultor';
  document.getElementById('chk-iniciar-todos').checked = true;
  document.getElementById('chk-iniciar-todos').disabled = false;
  new bootstrap.Modal(document.getElementById('modal-iniciar-analisis')).show();
};

window.abrirModalContinuarAnalisis = function (idMuestra, idCliente, agricultor) {
  muestraInicioAnalisis = { idMuestra: parseInt(idMuestra), idCliente: parseInt(idCliente), agricultor: agricultor, origen: 'en_analisis' };
  document.getElementById('modal-inicio-titulo').textContent = 'Continuar análisis';
  document.getElementById('modal-inicio-mensaje').textContent = 'Se continuará con todos los análisis pendientes del agricultor.';
  document.getElementById('lbl-iniciar-todos').textContent = 'Continuar todos los análisis pendientes del agricultor';
  document.getElementById('chk-iniciar-todos').checked = true;
  document.getElementById('chk-iniciar-todos').disabled = true;
  new bootstrap.Modal(document.getElementById('modal-iniciar-analisis')).show();
};

function confirmarInicioAnalisis() {
  const payload = {
    id_muestra: muestraInicioAnalisis.idMuestra,
    iniciar_todos: document.getElementById('chk-iniciar-todos').checked
  };
  fetch(API_MUESTRA + '?action=iniciar_analisis_agricultor', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
  }).then(function (r) { return r.json(); }).then(function (data) {
    if (!data.success) { mostrarErrorTabla(data.message); return; }
    bootstrap.Modal.getInstance(document.getElementById('modal-iniciar-analisis')).hide();
    window.location.href = '?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_cliente=' + data.id_cliente + '&id_muestra=' + muestraInicioAnalisis.idMuestra + '&agricultor=' + encodeURIComponent(muestraInicioAnalisis.agricultor);
  }).catch(function () { mostrarErrorTabla('Error de red al iniciar análisis.'); });
}

window.abrirModalFirmar = function (idMuestra, idCliente, agricultor) {
  firmaContexto = { idMuestra: parseInt(idMuestra), idCliente: parseInt(idCliente), agricultor: agricultor };
  document.getElementById('firma-id-muestra').textContent = idMuestra;
  document.getElementById('chk-firmar-todos').checked = true;
  new bootstrap.Modal(document.getElementById('modal-firmar-muestra')).show();
  cargarDetalleFirma();
};

function cargarDetalleFirma() {
  if (!firmaContexto.idMuestra) return;
  const firmarTodos = document.getElementById('chk-firmar-todos').checked;
  fetch(API_MUESTRA + '?action=obtener_detalle_firma', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_muestra: firmaContexto.idMuestra, firmar_todos: firmarTodos })
  }).then(function (r) { return r.json(); }).then(function (data) {
    if (!data.success) return;
    const tbody = document.getElementById('tbody-detalle-firma');
    const resumen = document.getElementById('firma-resumen');
    const resultados = Array.isArray(data.resultados) ? data.resultados : [];
    resumen.textContent = 'Agricultor: ' + (data.agricultor || firmaContexto.agricultor) + ' | Muestras: ' + (data.muestras || []).length + ' | Resultados: ' + resultados.length;
    tbody.innerHTML = '';
    if (!resultados.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted">No hay resultados.</td></tr>';
      return;
    }
    resultados.forEach(function (r) {
      tbody.insertAdjacentHTML('beforeend', '<tr><td>' + (r.id_muestra||'-') + '</td><td>' + (r.servicio||'-') + '</td><td>' + (r.parametro||'-') + '</td><td>' + (r.valor_hallado !== null && r.valor_hallado !== '' ? r.valor_hallado : '-') + '</td></tr>');
    });
  });
}

function confirmarFirma() {
  const firmarTodos = document.getElementById('chk-firmar-todos').checked;
  fetch(API_MUESTRA + '?action=firmar_muestra', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_muestra: firmaContexto.idMuestra, firmar_todos: firmarTodos })
  }).then(function (r) { return r.json(); }).then(function (data) {
    if (!data.success) { mostrarErrorTabla(data.message); return; }
    bootstrap.Modal.getInstance(document.getElementById('modal-firmar-muestra')).hide();
    recargarTablasGestion();
  });
}

// ====================================
// TAB 2: CREACIÓN MASIVA
// ====================================
function initTabMasiva() {
  if (tablasMasivaInit) return;
  tablasMasivaInit = true;

  cargarServiciosMasiva();

  window.tablaPerodosMonitoreo = $('#tabla-periodos-monitoreo').DataTable({
    processing: true, serverSide: true,
    ajax: {
      url: 'modules/laboratorio/muestra/views/data_periodos.php', type: 'POST',
      data: function (d) { d.es_control_calidad = 0; d.es_drene = 0; }
    },
    columns: [{data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6, orderable:false, searchable:false}],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  window.tablaPerodosCalidad = $('#tabla-periodos-calidad').DataTable({
    processing: true, serverSide: true,
    ajax: {
      url: 'modules/laboratorio/muestra/views/data_periodos.php', type: 'POST',
      data: function (d) { d.es_control_calidad = 1; }
    },
    columns: [{data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6, orderable:false, searchable:false}],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  window.tablaPeriodosDrenes = $('#tabla-periodos-drenes').DataTable({
    processing: true, serverSide: true,
    ajax: {
      url: 'modules/laboratorio/muestra/views/data_periodos.php', type: 'POST',
      data: function (d) { d.es_drene = 1; }
    },
    columns: [{data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6, orderable:false, searchable:false}],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  $('#tab-btn-monitoreo, #tab-btn-calidad, #tab-btn-drenes').on('shown.bs.tab', function () {
    if (window.tablaPerodosMonitoreo) window.tablaPerodosMonitoreo.columns.adjust();
    if (window.tablaPerodosCalidad) window.tablaPerodosCalidad.columns.adjust();
    if (window.tablaPeriodosDrenes) window.tablaPeriodosDrenes.columns.adjust();
  });
}

function recargarTablasMasiva(reset) {
  if (!tablasMasivaInit) return;
  if (window.tablaPerodosMonitoreo) window.tablaPerodosMonitoreo.ajax.reload(null, !!reset);
  if (window.tablaPerodosCalidad) window.tablaPerodosCalidad.ajax.reload(null, !!reset);
  if (window.tablaPeriodosDrenes) window.tablaPeriodosDrenes.ajax.reload(null, !!reset);
}

// -------------------------------------------------------
// Extraer celdas y pozos recién creados desde PostgreSQL
// -------------------------------------------------------
// -------------------------------------------------------
// Calidad Superficial y Calidad Drenes — Extraer / Historial / Mapeo
// -------------------------------------------------------

// Carga el modal para extraer calidad (tipo + año + mes)
window.extraerCalidad = function() {
    $.ajax({
        url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=listar_esquemas',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (!resp.success || !resp.esquemas) {
                Swal.fire('Error', 'No se pudieron obtener los esquemas de calidad.', 'error');
                return;
            }
            const tipos = { superficial: resp.esquemas.superficial || [], drene: resp.esquemas.drene || [] };

            function opcionesMeses(tipo) {
                const anios = tipos[tipo];
                let html = '<select id="cal-anio" class="form-select mb-2"><option value="">Año...</option>';
                anios.forEach(a => { html += `<option value="${a.anio}">${a.anio}</option>`; });
                html += '</select><select id="cal-mes" class="form-select"><option value="">Mes...</option></select>';
                return html;
            }

            Swal.fire({
                title: '<i class="ti ti-droplet me-2"></i>Extraer Calidad',
                html: `
                    <div class="text-start">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select id="cal-tipo" class="form-select mb-2">
                            <option value="superficial">Calidad Superficial</option>
                            <option value="drene">Calidad Drenes</option>
                        </select>
                        <div id="cal-año-mes"></div>
                        <p class="small text-muted mt-2 mb-0">Se crearán las muestras con sus resultados VACÍOS para llenar.</p>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-cloud-download me-1"></i>Extraer',
                confirmButtonColor: '#6c757d',
                didOpen: () => {
                    function cargarMeses() {
                        const tipo = document.getElementById('cal-tipo').value;
                        const anio = document.getElementById('cal-anio').value;
                        const anios = tipos[tipo] || [];
                        const a = anios.find(x => String(x.anio) === String(anio));
                        const selMes = document.getElementById('cal-mes');
                        selMes.innerHTML = '<option value="">Mes...</option>';
                        if (a && a.tablas) {
                            a.tablas.forEach(t => {
                                selMes.innerHTML += `<option value="${t.mes.toLowerCase()}">${t.mes}</option>`;
                            });
                        }
                    }
                    document.getElementById('cal-tipo').addEventListener('change', () => {
                        document.getElementById('cal-año-mes').innerHTML = opcionesMeses(document.getElementById('cal-tipo').value);
                        document.getElementById('cal-anio').addEventListener('change', cargarMeses);
                    });
                    document.getElementById('cal-año-mes').innerHTML = opcionesMeses('superficial');
                    document.getElementById('cal-anio').addEventListener('change', cargarMeses);
                },
                preConfirm: () => {
                    const tipo = document.getElementById('cal-tipo').value;
                    const anio = document.getElementById('cal-anio').value;
                    const mes = document.getElementById('cal-mes').value;
                    if (!anio || !mes) {
                        Swal.showValidationMessage('Selecciona año y mes.');
                        return false;
                    }
                    return { tipo, anio, mes };
                }
            }).then(result => {
                if (!result.isConfirmed) return;
                const { tipo, anio, mes } = result.value;
                const btn = document.getElementById('btn-extraer-calidad');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Extrayendo...';

                $.ajax({
                    url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=importar_calidad_init&tipo=' + tipo + '&anio=' + anio + '&mes=' + mes + '&llenar_resultados=0',
                    type: 'GET',
                    dataType: 'json',
                    success: function(resInit) {
                        if (!resInit.success || !resInit.lotes || resInit.lotes.length === 0) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti ti-droplet me-2"></i> Extraer Calidad';
                            Swal.fire('Información', 'No hay muestras para ' + tipo + ' ' + anio + '-' + mes + '.', 'info');
                            return;
                        }
                        procesarLotesCalidad(resInit, anio + ' ' + mes, tipo, 0, btn, function() {
                            recargarTablasMasiva(false);
                        });
                    },
                    error: function(xhr) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-droplet me-2"></i> Extraer Calidad';
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error al extraer calidad.', 'error');
                    }
                });
            });
        },
        error: function() {
            Swal.fire('Error', 'No se pudo contactar con CalidadAPI.', 'error');
        }
    });
};

// Importa TODO el historial de Calidad Superficial y Calidad Drenes (todos los años, con valores)
// ⚠️ Decisión del usuario: ANTES de importar se borra TODO lo de calidad (proyectos, muestras,
//    resultados y tablas intermediarias) para que la importación NUNCA duplique.
window.importarHistorialCalidad = function() {
    Swal.fire({
        title: '¿Importar historial de calidad?',
        html: 'Se <b>borrará TODO lo importado antes</b> de <b>Calidad Superficial</b> y <b>Calidad Drenes</b> (proyectos, muestras, resultados y tablas intermediarias) y se volverá a importar <b>todo el historial</b> desde PostgreSQL, <b>sin duplicar nada</b>.<br><br><span class="text-danger"><i class="ti ti-alert-triangle me-1"></i>Esta acción no se puede deshacer.</span>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, borrar e importar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63939'
    }).then(result => {
        if (!result.isConfirmed) return;
        const btn = document.getElementById('btn-historial-calidad');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Limpiando importación anterior...';

        $.ajax({
            url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=limpiar_calidad',
            type: 'POST',
            dataType: 'json',
            success: function(resLimpia) {
                if (!resLimpia.success) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-history me-2"></i> Importar Historial Calidad';
                    Swal.fire('Error', resLimpia.message || 'Error al limpiar los datos de calidad.', 'error');
                    return;
                }
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Importando...';

                $.ajax({
                    url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=importar_calidad_historial_init&llenar_resultados=1',
                    type: 'GET',
                    dataType: 'json',
                    success: function(resInit) {
                        if (!resInit.success || !resInit.lotes || resInit.lotes.length === 0) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti ti-history me-2"></i> Importar Historial Calidad';
                            Swal.fire('Información', 'No hay datos de calidad para importar.', 'info');
                            return;
                        }
                        procesarLotesCalidad(resInit, 'HISTORIAL', 'todos', 1, btn, function() {
                            recargarTablasMasiva(false);
                        });
                    },
                    error: function(xhr) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-history me-2"></i> Importar Historial Calidad';
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error al obtener historial de calidad.', 'error');
                    }
                });
            },
            error: function(xhr) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-history me-2"></i> Importar Historial Calidad';
                Swal.fire('Error', xhr.responseJSON?.message || 'Error al limpiar los datos de calidad.', 'error');
            }
        });
    });
};

// Procesa lotes de calidad con barra de progreso
function procesarLotesCalidad(resInit, titulo, tipo, llenar, btn, alFinalizar) {
    const lotes = resInit.lotes;
    const total = lotes.length;
    let procesados = 0;
    let errores = 0;
    let resultadosTotales = 0;

    Swal.fire({
        title: 'Importando Calidad ' + titulo,
        html: `
            <div class="mb-3"><b>Total muestras:</b> ${total} | Procesadas: <span id="cal-prog-texto">0</span></div>
            <div class="progress">
                <div id="cal-prog-barra" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div class="mt-2 small"><span class="text-muted">Resultados: <span id="cal-prog-res">0</span></span> | <span class="text-danger">Errores: <span id="cal-prog-err">0</span></span></div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false
    });

    function siguiente() {
        if (procesados >= total) {
            btn.disabled = false;
            btn.innerHTML = (tipo === 'todos') ? '<i class="ti ti-history me-2"></i> Importar Historial Calidad' : '<i class="ti ti-droplet me-2"></i> Extraer Calidad';
            Swal.fire({
                icon: errores > 0 ? 'warning' : 'success',
                title: 'Importación Completada',
                html: `Se procesaron <b>${procesados}</b> muestras.<br>Resultados: <b>${resultadosTotales}</b>${errores > 0 ? `<br><span class="text-danger">⚠ ${errores} errores</span>` : ''}`,
                confirmButtonText: 'Entendido'
            }).then(() => { if (alFinalizar) alFinalizar(); });
            return;
        }

        const lote = lotes[procesados];
        $.ajax({
            url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php',
            type: 'POST',
            data: {
                action: 'importar_calidad_batch',
                tipo: lote.tipo,
                anio: lote.anio,
                mes: lote.mes,
                esquema: lote.esquema,
                tabla: lote.tabla,
                id_fila: lote.id_fila,
                descripcion: lote.descripcion,
                fechamonitoreo: lote.fechamonitoreo,
                proyecto: lote.proyecto,
                llenar_resultados: llenar
            },
            dataType: 'json',
            success: function(res) {
                procesados++;
                if (res.success) {
                    resultadosTotales += (res.resultados || 0);
                } else {
                    errores++;
                }
                actualizarProgresoCalidad(total, procesados, errores, resultadosTotales);
                siguiente();
            },
            error: function() {
                procesados++;
                errores++;
                actualizarProgresoCalidad(total, procesados, errores, resultadosTotales);
                siguiente();
            }
        });
    }
    siguiente();
}

function actualizarProgresoCalidad(total, procesados, errores, resultados) {
    const pct = Math.round((procesados / total) * 100);
    document.getElementById('cal-prog-texto').innerText = procesados;
    document.getElementById('cal-prog-res').innerText = resultados;
    document.getElementById('cal-prog-err').innerText = errores;
    const barra = document.getElementById('cal-prog-barra');
    barra.style.width = pct + '%';
    barra.innerText = pct + '%';
}

// Pantalla de Mapeo de Calidad (parámetro → columna PG por tipo)
window.abrirMapeoCalidad = function() {
    $.ajax({
        url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=obtener_mapeo',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (!resp.success) { Swal.fire('Error', 'No se pudo obtener el mapeo.', 'error'); return; }
            const mapeo = resp.mapeo || [];
            const params = resp.parametros || [];

            let filas = mapeo.map(m => `
                <tr data-id="${m.id_mapeo}" data-param="${m.id_parametro}" data-tipo="${m.tipo}">
                    <td>${escapeHtml(m.parametro)} <small class="text-muted d-block">${escapeHtml(m.categoria||'')}</small></td>
                    <td><span class="badge ${m.tipo==='superficial'?'bg-info':'bg-secondary'}">${m.tipo==='superficial'?'Superficial':'Drenes'}</span></td>
                    <td><input type="text" class="form-control form-control-sm mapeo-col" value="${escapeHtml(m.columna)}" style="font-family:monospace;"></td>
                </tr>`).join('');

            let optsParams = '<option value="">-- Seleccionar parámetro --</option>';
            params.forEach(p => {
                optsParams += `<option value="${p.id_parametro}">${escapeHtml(p.nombre)} (${escapeHtml(p.categoria||'')})</option>`;
            });

            Swal.fire({
                title: '<i class="ti ti-settings me-2"></i>Mapeo de Calidad',
                html: `
                    <p class="small text-muted text-start">Asigna cada parámetro a su columna en las tablas de calidad. Las columnas nuevas se guardan al final.</p>
                    <div class="text-start mb-2 p-2 border rounded" style="background:#f8f9fa;">
                        <div class="row g-2">
                            <div class="col-5"><select id="map-param" class="form-select form-select-sm">${optsParams}</select></div>
                            <div class="col-4">
                                <select id="map-tipo" class="form-select form-select-sm">
                                    <option value="superficial">Superficial</option>
                                    <option value="drene">Drenes</option>
                                </select>
                            </div>
                            <div class="col-3"><input type="text" id="map-col" class="form-control form-control-sm" placeholder="columna_pg"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary mt-2" onclick="agregarMapeoCalidad()"><i class="ti ti-plus me-1"></i>Agregar</button>
                    </div>
                    <div class="text-start" style="max-height:320px; overflow:auto;">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Parámetro</th><th>Tipo</th><th style="width:40%;">Columna PG</th></tr></thead>
                            <tbody id="tabla-mapeo-body">${filas || '<tr><td colspan="3" class="text-muted">Sin mapeos</td></tr>'}</tbody>
                        </table>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Guardar Cambios',
                cancelButtonText: 'Cerrar',
                didOpen: () => { window.agregarMapeoCalidad = function() {
                    const p = document.getElementById('map-param').value;
                    const t = document.getElementById('map-tipo').value;
                    const c = document.getElementById('map-col').value.trim();
                    if (!p || !c) { Swal.showValidationMessage('Selecciona parámetro y columna.'); return; }
                    $.ajax({
                        url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=guardar_mapeo',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify([{ id_parametro: p, tipo: t, columna: c }]),
                        success: function() { abrirMapeoCalidad(); },
                        error: function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Error al guardar', 'error'); }
                    });
                }; },
                preConfirm: function() {
                    const cambios = [];
                    $('#tabla-mapeo-body tr').each(function() {
                        const id = $(this).data('id');
                        const tipo = $(this).data('tipo');
                        const col = $(this).find('.mapeo-col').val().trim();
                        if (id && col) cambios.push({ id_mapeo: id, tipo: tipo, columna: col });
                    });
                    return cambios;
                }
            }).then(result => {
                if (!result.isConfirmed) return;
                const cambios = result.value;
                if (!cambios.length) return;
                $.ajax({
                    url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=guardar_mapeo_edicion',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(cambios),
                    success: function() { Swal.fire('Guardado', 'Mapeo actualizado.', 'success'); },
                    error: function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Error al guardar', 'error'); }
                });
            });
        },
        error: function() { Swal.fire('Error', 'No se pudo contactar con CalidadAPI.', 'error'); }
    });
};

// -------------------------------------------------------
// Extraer celdas y pozos recién creados desde PostgreSQL
// -------------------------------------------------------
window.sincronizarDesdePostgreSQL = function() {
    const anioActual = new Date().getFullYear();
    const mesActual  = new Date().getMonth() + 1;
    const periodoDefecto = mesActual <= 6 ? '1' : '2';

    const opcionesAnio = [anioActual - 1, anioActual, anioActual + 1]
        .map(a => `<option value="${a}" ${a === anioActual ? 'selected' : ''}>${a}</option>`).join('');

    let esquemasCalidad = null;

    Swal.fire({
        title: '<i class="ti ti-database-import me-2"></i>Extraer desde PostgreSQL',
        html: `
            <p class="text-muted small mb-3">Selecciona el tipo de monitoreo y el rango a extraer.</p>
            <div class="row g-3 text-start">
                <div class="col-12">
                    <label class="form-label fw-semibold">Tipo de monitoreo</label>
                    <select id="swal-tipo" class="form-select">
                        <option value="pozos">Monitoreo de Pozos (subterráneo)</option>
                        <option value="superficial">Calidad Superficial</option>
                        <option value="drene">Calidad Drenes</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Año</label>
                    <select id="swal-anio" class="form-select">${opcionesAnio}</select>
                </div>
                <div class="col-6" id="swal-periodo-wrap">
                    <label class="form-label fw-semibold">Período</label>
                    <select id="swal-periodo" class="form-select">
                        <option value="1" ${periodoDefecto==='1'?'selected':''}>Período 1 — Avenida (Ene–Jun)</option>
                        <option value="2" ${periodoDefecto==='2'?'selected':''}>Período 2 — Estiaje (Jul–Dic)</option>
                    </select>
                </div>
                <div class="col-6" id="swal-mes-wrap" style="display:none;">
                    <label class="form-label fw-semibold">Mes</label>
                    <select id="swal-mes" class="form-select"><option value="">Mes...</option></select>
                </div>
            </div>
            <div id="swal-resumen" class="alert alert-info mt-3 mb-0 small text-start py-2 px-3"></div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-cloud-download me-1"></i>Extraer',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#004d99',
        didOpen: () => {
            $.ajax({
                url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=listar_esquemas',
                type: 'GET',
                dataType: 'json',
                success: function(resp) { esquemasCalidad = (resp && resp.success) ? resp.esquemas : null; },
                error: function() { esquemasCalidad = null; }
            });

            function cargarMesesCalidad() {
                const tipo = document.getElementById('swal-tipo').value;
                const anio = document.getElementById('swal-anio').value;
                const selMes = document.getElementById('swal-mes');
                selMes.innerHTML = '<option value="">Mes...</option>';
                if (!esquemasCalidad || !esquemasCalidad[tipo]) return;
                const anios = esquemasCalidad[tipo];
                const a = anios.find(x => String(x.anio) === String(anio));
                if (a && a.tablas) {
                    a.tablas.forEach(t => { selMes.innerHTML += `<option value="${t.mes.toLowerCase()}">${t.mes}</option>`; });
                }
            }

            function actualizarResumen() {
                const tipo = document.getElementById('swal-tipo').value;
                const a = document.getElementById('swal-anio').value;
                if (tipo === 'pozos') {
                    const p = document.getElementById('swal-periodo').value;
                    const meses = p === '1' ? 'Enero – Junio' : 'Julio – Diciembre';
                    const ttipo = p === '1' ? 'Avenida' : 'Estiaje';
                    document.getElementById('swal-resumen').innerHTML =
                        `Se extraerá: <strong>MONITOREO POZOS {VALLE} – ${a}-0${p}</strong><br>Meses: ${meses} (${ttipo})`;
                } else {
                    const m = document.getElementById('swal-mes').value;
                    const nombreTipo = tipo === 'superficial' ? 'CALIDAD SUPERFICIAL' : 'CALIDAD DRENES';
                    document.getElementById('swal-resumen').innerHTML =
                        m ? `Se extraerá: <strong>${nombreTipo} ${a} - ${m.toUpperCase()}</strong><br>Muestras con resultados vacíos para llenar.`
                          : `Selecciona el mes de <strong>${nombreTipo} ${a}</strong>.`;
                }
            }

            function toggleTipo() {
                const tipo = document.getElementById('swal-tipo').value;
                document.getElementById('swal-periodo-wrap').style.display = (tipo === 'pozos') ? '' : 'none';
                document.getElementById('swal-mes-wrap').style.display = (tipo === 'pozos') ? 'none' : '';
                actualizarResumen();
            }

            actualizarResumen();
            document.getElementById('swal-tipo').addEventListener('change', toggleTipo);
            document.getElementById('swal-anio').addEventListener('change', () => { cargarMesesCalidad(); actualizarResumen(); });
            document.getElementById('swal-periodo').addEventListener('change', actualizarResumen);
            document.getElementById('swal-mes').addEventListener('change', actualizarResumen);
        },
        preConfirm: () => {
            const tipo = document.getElementById('swal-tipo').value;
            const anio = document.getElementById('swal-anio').value;
            if (tipo !== 'pozos') {
                const mes = document.getElementById('swal-mes').value;
                if (!mes) {
                    Swal.showValidationMessage('Selecciona el mes a extraer.');
                    return false;
                }
                return { tipo: tipo, anio: anio, periodo: '', mes: mes };
            }
            return {
                tipo: 'pozos',
                anio: anio,
                periodo: document.getElementById('swal-periodo').value,
                mes: ''
            };
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        const { tipo, anio, periodo, mes } = result.value;

        // ===== Calidad Superficial / Calidad Drenes (mismo botón) =====
        if (tipo !== 'pozos') {
            const btn = document.getElementById('btn-sincronizar-pg');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Extrayendo...';

            $.ajax({
                url: 'modules/laboratorio/muestra/controllers/CalidadAPI.php?action=importar_calidad_init&tipo=' + tipo + '&anio=' + anio + '&mes=' + mes + '&llenar_resultados=0',
                type: 'GET',
                dataType: 'json',
                success: function(resInit) {
                    if (!resInit.success || !resInit.lotes || resInit.lotes.length === 0) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-database-import me-2"></i> Extraer desde PostgreSQL';
                        Swal.fire('Información', 'No hay muestras de ' + (tipo === 'superficial' ? 'Calidad Superficial' : 'Calidad Drenes') + ' para ' + anio + '-' + mes + '.', 'info');
                        return;
                    }
                    procesarLotesCalidad(resInit, anio + ' ' + mes, tipo, 0, btn, function() {
                        recargarTablasMasiva(false);
                    });
                },
                error: function(xhr) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-database-import me-2"></i> Extraer desde PostgreSQL';
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error al extraer calidad.', 'error');
                }
            });
            return;
        }

        Swal.fire({
            title: 'Extrayendo datos de PostgreSQL...',
            html: `<div class="text-center py-2"><div class="spinner-border text-primary" role="status"></div>
                   <p class="mt-3 text-muted">Filtrando monitoreos de <strong>${anio}-0${periodo}</strong>...<br>
                   Sincronizando pozos y generando celdas en blanco.</p></div>`,
            allowOutsideClick: false,
            showConfirmButton: false
        });

        $.ajax({
            url: 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=sincronizar_pozos',
            type: 'POST',
            dataType: 'json',
            success: function(respPozos) {
                $.ajax({
                    url: 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=sincronizar_monitoreos',
                    type: 'POST',
                    data: { anio: anio, periodo: periodo },
                    dataType: 'json',
                    success: function(respMon) {
                        Swal.close();
                        if (respMon && respMon.success) {
                            const stats = respMon.stats || {};
                            const creados      = stats.proyectos_creados   || 0;
                            const actualizados = stats.proyectos_actualizados || 0;
                            const pozosNuevos  = respPozos.insertados || 0;
                            const muestrasCreadas = stats.muestras_creadas || 0;
                            const label        = periodo === '1' ? 'Avenida (Ene–Jun)' : 'Estiaje (Jul–Dic)';
                            const erroresArr   = Array.isArray(stats.errores) ? stats.errores : [];
                            const hayErrores   = erroresArr.length > 0;

                            let htmlErrores = '';
                            if (hayErrores) {
                                htmlErrores = '<div class="alert alert-danger mt-2 mb-0 small text-start py-2 px-3" style="max-height:140px;overflow:auto;">' +
                                    '<b>Errores (' + erroresArr.length + '):</b><ul class="mb-0">' +
                                    erroresArr.slice(0, 10).map(e => '<li>' + escapeHtml(String(e)) + '</li>').join('') +
                                    (erroresArr.length > 10 ? '<li>… y ' + (erroresArr.length - 10) + ' más</li>' : '') +
                                    '</ul></div>';
                            }

                            Swal.fire({
                                icon: hayErrores ? 'warning' : 'success',
                                title: hayErrores ? 'Extracción con errores' : 'Extracción Completada',
                                html: `<div class="text-start">
                                         <p class="mb-2">Período extraído: <strong>${anio}-0${periodo} ${label}</strong></p>
                                         <ul class="mb-0">
                                           <li><strong>Nuevos proyectos/períodos creados:</strong> ${creados}</li>
                                           <li><strong>Proyectos/celdas actualizadas:</strong> ${actualizados}</li>
                                           <li><strong>Pozos nuevos sincronizados:</strong> ${pozosNuevos}</li>
                                           <li><strong>Muestras creadas:</strong> ${muestrasCreadas}</li>
                                         </ul>
                                         ${htmlErrores}
                                         <p class="small text-muted mt-2 mb-0">Las celdas en blanco para parámetros in-situ y de laboratorio han sido generadas y están listas para su llenado.</p>
                                       </div>`,
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#004d99'
                            }).then(() => {
                                if (typeof recargarTablasMasiva === 'function') {
                                    recargarTablasMasiva(false);
                                }
                            });
                        } else {
                            Swal.fire('Error', respMon.message || 'Error al sincronizar monitoreos desde PostgreSQL', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.error('Error AJAX Monitoreos:', xhr.responseText);
                        Swal.fire('Error', 'Error al conectar con PostgreSQL para monitoreos: ' + error, 'error');
                    }
                });
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error AJAX Pozos:', xhr.responseText);
                Swal.fire('Error', 'Error al conectar con PostgreSQL para pozos: ' + error, 'error');
            }
        });
    });
};

function cargarServiciosMasiva() {
  $.getJSON(API_MASIVA + '?action=obtenerServicios', function (resp) {
    if (!resp.success) return;
    serviciosDisponibles = resp.servicios || [];
    let opts = '<option value="">Seleccionar servicio...</option>';
    serviciosDisponibles.forEach(function (s) {
      opts += '<option value="' + s.id + '">' + s.nombre + '</option>';
    });
    $('#select-servicios').html(opts);
  });
}

function setupMasivaEvents() {
  $('#select-servicios').on('change', agregarServicioMasiva);
  $('#btn-crear-periodo').on('click', guardarPeriodoMasiva);
  $('#tipo-muestra').on('change', mostrarCamposTipoMuestra);
  $('#select-valle').on('change', function () {
    $('#valle-otro').toggle($(this).val() === 'Otros').prop('required', $(this).val() === 'Otros');
  });
  $('#select-fuente').on('change', function () {
    // Tipo de Fuente has no "Otros" option — no toggle needed
  });
  $('#check-control-calidad').on('change', function () {
    if (this.checked) $('#check-drene').prop('checked', false);
    const min = this.checked ? 10 : 1;
    $('#tabla-servicios-tbody input[data-id]').attr('min', min).each(function () {
      if (parseInt($(this).val()) < min) $(this).val(min);
    });
    $('#info-control-calidad').text(this.checked
      ? 'Calidad de agua activo: mínimo 10 muestras por servicio.'
      : 'Si se activa, el sistema exige al menos 10 muestras planificadas por servicio.');
  });
  $('#check-drene').on('change', function () {
    if (this.checked) $('#check-control-calidad').prop('checked', false);
    const min = this.checked ? 10 : 1;
    $('#tabla-servicios-tbody input[data-id]').attr('min', min).each(function () {
      if (parseInt($(this).val()) < min) $(this).val(min);
    });
    $('#info-drene').text(this.checked
      ? 'Drenes activo: mínimo 10 muestras por servicio.'
      : 'Si se activa, se usarán fuentes tipo dren y el campo Es_Drene se marcará en cada muestra creada.');
  });
  $('#modal-nuevo-periodo').on('hidden.bs.modal', function () {
    $('#form-nuevo-periodo')[0].reset();
    $('#tabla-servicios-tbody').html('');
    mostrarCamposTipoMuestra();
  });
}

function mostrarCamposTipoMuestra() {
  $('#campos-agua').toggle($('#tipo-muestra').val() === 'Agua');
}

function agregarServicioMasiva() {
  const id = $('#select-servicios').val();
  const nombre = $('#select-servicios option:selected').text();
  if (!id) return;
  if ($('#tabla-servicios-tbody input[data-id="' + id + '"]').length) {
    Swal.fire('Advertencia', 'Este servicio ya fue agregado', 'warning'); return;
  }
  const min = ($('#check-control-calidad').is(':checked') || $('#check-drene').is(':checked')) ? 10 : 1;
  $('#tabla-servicios-tbody').append('<tr><td>' + nombre + '</td><td class="text-center"><input type="number" class="form-control form-control-sm" value="' + min + '" min="' + min + '" data-id="' + id + '" style="width:100%;"></td><td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest(\'tr\').remove()"><i class="ti ti-trash"></i></button></td></tr>');
  $('#select-servicios').val('');
}

function guardarPeriodoMasiva() {
  let valle = $('#select-valle').val();
  if (valle === 'Otros') { valle = $('#valle-otro').val().trim(); if (!valle) { Swal.fire('Error', 'Especifique el valle', 'error'); return; } }
  const fuente = $('#select-fuente').val();

  const nombre = $('#nombre-proyecto').val().trim();
  const fecha = $('#fecha-inicio').val();
  const temporada = $('#temporada').val().trim();
  const tipoMuestra = $('#tipo-muestra').val();
  const esControlCalidad = $('#check-control-calidad').is(':checked') ? 1 : 0;
  const esDrene = $('#check-drene').is(':checked') ? 1 : 0;
  const esEspecial = esControlCalidad || esDrene; // either requires min 10

  if (!nombre || !valle || !fecha || !temporada || !tipoMuestra) { Swal.fire('Error', 'Complete todos los campos obligatorios', 'error'); return; }

  const servicios = [];
  $('#tabla-servicios-tbody input[data-id]').each(function () {
    let cantidad = parseInt($(this).val()) || 0;
    if (esEspecial && cantidad < 10) cantidad = 10;
    servicios.push({ id: $(this).data('id'), cantidad: cantidad });
  });
  if (!servicios.length) { Swal.fire('Error', 'Agregue al menos un servicio', 'error'); return; }

  const datos = {
    action: 'guardarProyecto', nombre_proyecto: nombre, valle: valle,
    fecha_inicio: fecha, temporada: temporada, tipo_muestra: tipoMuestra,
    es_control_calidad: esControlCalidad, es_drene: esDrene, servicios: servicios
  };
  if (tipoMuestra === 'Agua') {
    const uso = $('#select-uso-agua').val();
    let nivel = $('#select-nivel-agua').val();
    if (nivel === 'Otros') { nivel = $('#nivel-agua-otra').val().trim(); if (!nivel) { Swal.fire('Error', 'Especifique la fuente', 'error'); return; } }
    if (!uso || !fuente || !nivel) { Swal.fire('Error', 'Complete todos los datos de agua', 'error'); return; }
    datos.uso_agua = uso; datos.fuente_agua = fuente; datos.nivel_agua = nivel;
  }

  $.ajax({
    url: API_MASIVA, type: 'POST', data: JSON.stringify(datos), contentType: 'application/json', dataType: 'json',
    success: function (resp) {
      if (resp.success) {
        Swal.fire('Éxito', resp.mensaje || 'Proyecto creado', 'success');
        bootstrap.Modal.getInstance(document.getElementById('modal-nuevo-periodo')).hide();
        recargarTablasMasiva(true);
      } else {
        Swal.fire('Error', resp.error || 'Error desconocido', 'error');
      }
    },
    error: function (err) { Swal.fire('Error', (err.responseJSON && err.responseJSON.error) || 'Error al guardar', 'error'); }
  });
}

// Funciones globales usadas desde data_periodos.php HTML
function escapeHtml(t) {
  return String(t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function normalizarFechaInput(v) {
  if (!v) return '';
  if (typeof v === 'string') { const t = v.trim(); if (/^\d{4}-\d{2}-\d{2}/.test(t)) return t.substring(0,10); }
  if (typeof v === 'object' && v.date) return String(v.date).substring(0,10);
  return '';
}
function esProyectoControlCalidad(v) { return String(v)==='1'||v===1||v===true; }
function obtenerFuentesControlCalidadDefault(t) {
  const b=['RIO TABLACHACA','RIO SANTA','ENTRADA DESARENADOR','SALIDA DESARENADOR','CANAL EVACUADOR','RIO VIRU','RIO MOCHE','RIO CHICAMA','CANAL MADRE','CENTRAL HIDROELECTRICA VIRU SAN JOSE'];
  const f=[]; for(let i=0;i<Math.max(0,parseInt(t)||0);i++) f.push(b[i%b.length]); return f;
}
function obtenerFuentesDreneDefault(t) {
  const b=[
    'DREN DV-4.3: ANTES DE LA ENTREGA DE SUS AGUAS AL DV-4.0',
    'DREN BITÍN: ANTES DE LA ENTREGA DE SUS AGUAS AL DV-4.0',
    'DREN DV-4.0 (1): A 150 M AGUAS ABAJO DESDE LA PANAMERICANA',
    'DREN DV-4.0 (2): EN EL SECTOR DENOMINADO HUANCAQUITO ALTO',
    'DREN DV-4.0 (3): ANTES DE LA ENTREGA DE SUS AGUAS DRENADAS AL RÍO VIRÚ EN EL SECTOR HUANCAQUITO PUEBLO',
    'DREN DV-3.0','DREN DV-2.00','DREN EVACUADOR','DREN DV-1.0','DREN FRONTON BAJO','DREN CHANQUIN'
  ];
  const f=[]; for(let i=0;i<Math.max(0,parseInt(t)||0);i++) f.push(b[i%b.length]); return f;
}

function verDetalles(id) { $.getJSON(API_MASIVA+'?action=obtenerDetalles&id='+id, function(r){ let m='<h5>'+r.proyecto.Nombre_Proyecto+'</h5><p><b>Valle:</b> '+r.proyecto.Valle+'</p><p><b>Temporada:</b> '+r.proyecto.Temporada+'</p><p><b>Estado:</b> '+r.proyecto.Estado+'</p><ul>'; (r.detalles||[]).forEach(function(d){ m+='<li>'+d.Nombre_Producto+': <b>'+d.Cantidad_Planificada+'</b></li>'; }); m+='</ul>'; Swal.fire('Detalles',m,'info'); }); }

function iniciarEjecucion(id) {
  $.getJSON(API_MASIVA+'?action=obtenerDetalles&id='+id, function(resp) {
    const detalles = resp.detalles||[];
    const esCC = esProyectoControlCalidad(resp.proyecto && resp.proyecto.Es_Control_Calidad);
    const esDrene = String(resp.proyecto && resp.proyecto.Es_Drene)==='1' || (resp.proyecto && resp.proyecto.Es_Drene)===1;
    let total=0, tabla='<table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Servicio</th><th class="text-center">Cantidad</th></tr></thead><tbody>';
    detalles.forEach(function(d){ const c=parseInt(d.Cantidad_Planificada)||0; total+=c; tabla+='<tr><td>'+d.Nombre_Producto+'</td><td class="text-center"><span class="badge bg-info">'+c+'</span></td></tr>'; });
    tabla+='</tbody></table>';
    let bloqueFuentes='';
    if(esCC && total>0){
      const ff=obtenerFuentesControlCalidadDefault(total);
      let filasFuentes='';
      for(let i=0;i<total;i++) filasFuentes+='<tr><td class="text-center"><span class="badge bg-secondary">M'+(i+1)+'</span></td><td><input type="text" class="form-control form-control-sm fuente-calidad-input" data-index="'+i+'" value="'+escapeHtml(ff[i])+'"></td></tr>';
      bloqueFuentes='<hr><div class="alert alert-info py-2 px-3">Calidad de agua: edite las fuentes antes de crear.</div><div class="table-responsive" style="max-height:280px;overflow:auto;"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th style="width:90px;" class="text-center">Muestra</th><th>Fuente de Agua</th></tr></thead><tbody>'+filasFuentes+'</tbody></table></div>';
    } else if(esDrene && total>0){
      const fd=obtenerFuentesDreneDefault(total);
      let filasDrene='';
      for(let i=0;i<total;i++) filasDrene+='<tr><td class="text-center"><span class="badge bg-secondary">M'+(i+1)+'</span></td><td><input type="text" class="form-control form-control-sm fuente-drene-input" data-index="'+i+'" value="'+escapeHtml(fd[i])+'"></td></tr>';
      bloqueFuentes='<hr><div class="alert alert-warning py-2 px-3">Drenes: edite las fuentes de dren antes de crear.</div><div class="table-responsive" style="max-height:280px;overflow:auto;"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th style="width:90px;" class="text-center">Muestra</th><th>Fuente de Dren</th></tr></thead><tbody>'+filasDrene+'</tbody></table></div>';
    }
    Swal.fire({
      title:'Iniciar Ejecución', icon:'question', showCancelButton:true, confirmButtonText:'Crear muestras', cancelButtonText:'Cancelar',
      html:'<div style="text-align:left;"><h6><b>'+resp.proyecto.Nombre_Proyecto+'</b></h6><p><b>Valle:</b> '+resp.proyecto.Valle+'</p><hr><h6>Se crearán <b style="color:#d32f2f;">'+total+' muestras</b>:</h6>'+tabla+bloqueFuentes+'</div>'
    }).then(function(r){
      if(!r.isConfirmed) return;
      const dp={action:'generarMuestras',id_proyecto:id};
      if(esCC){ const ff=[]; $('.swal2-container .fuente-calidad-input').each(function(){ ff.push($(this).val().trim()); }); if(ff.some(function(f){return !f;})){ Swal.fire('Error','Todas las fuentes deben tener valor.','error'); return; } dp.fuentes_calidad=ff; }
      if(esDrene){ const fd=[]; $('.swal2-container .fuente-drene-input').each(function(){ fd.push($(this).val().trim()); }); if(fd.some(function(f){return !f;})){ Swal.fire('Error','Todas las fuentes de dren deben tener valor.','error'); return; } dp.fuentes_drene=fd; }
      Swal.fire({title:'Creando muestras...',allowOutsideClick:false,showConfirmButton:false,didOpen:function(){Swal.showLoading();}});
      $.ajax({url:API_MASIVA,type:'POST',data:dp,dataType:'json',success:function(resp){ Swal.close(); if(resp.success){ Swal.fire('Éxito',resp.mensaje||'Muestras creadas','success'); recargarTablasMasiva(true); } else { Swal.fire('Error',resp.error||'Error','error'); } },error:function(){ Swal.close(); Swal.fire('Error','Error al iniciar ejecución','error'); }});
    });
  });
}

function abrirAnalisis(id) { Swal.fire({title:'Registrar Análisis',text:'¿Continuar con el análisis de las muestras?',icon:'question',showCancelButton:true,confirmButtonText:'Sí, continuar',cancelButtonText:'Cancelar'}).then(function(r){ if(r.isConfirmed) window.location.href='?module=laboratorio&action=muestra&subaction=analisis_proyecto&id_proyecto='+id; }); }
function verResultados(id) { window.location.href='?module=laboratorio&action=muestra&subaction=analisis_proyecto&id_proyecto='+id; }
function exportarCalidadAgua(id) {
  Swal.fire({
    title: 'Exportar Calidad de Agua',
    text: '¿Desea exportar el informe de resultados de Calidad de Agua de este proyecto?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Exportar Excel',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#198754'
  }).then(function(result) {
    if (!result.isConfirmed) return;
    const params = new URLSearchParams();
    params.set('id_proyecto', String(id));
    window.location.href = 'modules/laboratorio/muestra/controllers/ExportarCalidadAgua.php?' + params.toString();
  });
}
function exportarProyectoMonitoreo(id) {
  $.getJSON(API_MASIVA+'?action=obtenerCategoriasLimite&id_proyecto='+id, function(resp){
    const cats=(resp.success && Array.isArray(resp.categorias))?resp.categorias:[];
    if(!cats.length){ Swal.fire('Aviso','No se encontraron categorías de límites.','warning'); return; }
    let html='<div style="text-align:left;max-height:260px;overflow:auto;">';
    cats.forEach(function(c,i){ const d=String(c&&c.descripcion?c.descripcion:'').trim(); if(!d) return; html+='<div class="form-check mb-2"><input class="form-check-input chk-cat-lim" type="checkbox" id="cl'+i+'" value="'+escapeHtml(d)+'" checked><label class="form-check-label" for="cl'+i+'">'+escapeHtml(d)+'</label></div>'; });
    html+='</div>';
    Swal.fire({title:'Seleccionar límites',html:'<p class="text-muted" style="text-align:left;">Selecciona las categorías para marcar resultados en rojo.</p>'+html,icon:'question',showCancelButton:true,confirmButtonText:'Exportar',cancelButtonText:'Cancelar',focusConfirm:false,preConfirm:function(){ const s=[]; $('.swal2-container .chk-cat-lim:checked').each(function(){ const v=String($(this).val()||'').trim(); if(v) s.push(v); }); if(!s.length){ Swal.showValidationMessage('Selecciona al menos una categoría.'); return false; } return s; }}).then(function(r){ if(!r.isConfirmed||!Array.isArray(r.value)) return; const p=new URLSearchParams(); p.set('id_proyecto',String(id)); r.value.forEach(function(c){ p.append('categorias[]',c); }); window.location.href='modules/laboratorio/muestra/controllers/ExportarProyectoMonitoreo.php?'+p.toString(); });
  });
}
function exportarDrenes(id) {
  $.getJSON(API_MASIVA + '?action=obtenerNormativasConLimites&id_proyecto=' + id, function(resp) {
    if (!resp || !resp.success || !resp.normativas || !resp.normativas.length) {
      Swal.fire('Aviso', 'No hay normativas con límites configurados para este proyecto.', 'warning');
      return;
    }
    const normativas = resp.normativas;
    function buildCatsHtml(cats) {
      let h = '';
      cats.forEach(function(c, i) {
        h += '<div class="form-check"><input class="form-check-input chk-dren-lim" type="checkbox" id="swal-dren-c' + i + '" value="' + escapeHtml(c.descripcion) + '" checked>' +
          '<label class="form-check-label" for="swal-dren-c' + i + '">' + escapeHtml(c.descripcion) + '</label></div>';
      });
      return h;
    }
    let htmlNorm = '<div class="mb-3"><label class="form-label fw-semibold">Normativa a mostrar en el informe:</label>';
    if (normativas.length === 1) {
      htmlNorm += '<div class="alert alert-info py-2 mb-0">' + escapeHtml(normativas[0].nombre) + '</div>';
    } else {
      normativas.forEach(function(n, i) {
        htmlNorm += '<div class="form-check"><input class="form-check-input" type="radio" name="swal-dren-norm" id="swal-dren-n' + i + '" value="' + i + '"' + (i === 0 ? ' checked' : '') + '>' +
          '<label class="form-check-label" for="swal-dren-n' + i + '">' + escapeHtml(n.nombre) + '</label></div>';
      });
    }
    htmlNorm += '</div>';
    const htmlCats = '<div class="mb-2"><label class="form-label fw-semibold">Límites para marcar en rojo:</label><div id="swal-dren-cats">' + buildCatsHtml(normativas[0].categorias) + '</div></div>';
    Swal.fire({
      title: 'Exportar Análisis de Drenes',
      html: '<div style="text-align:left;">' + htmlNorm + htmlCats + '</div>',
      showCancelButton: true,
      confirmButtonText: 'Exportar Excel',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#198754',
      width: 540,
      focusConfirm: false,
      didOpen: function() {
        if (normativas.length > 1) {
          document.querySelectorAll('.swal2-container input[name="swal-dren-norm"]').forEach(function(r) {
            r.addEventListener('change', function() {
              const el = document.getElementById('swal-dren-cats');
              if (el) el.innerHTML = buildCatsHtml(normativas[parseInt(this.value)].categorias);
            });
          });
        }
      },
      preConfirm: function() {
        let normIdx = 0;
        if (normativas.length > 1) {
          const r = document.querySelector('.swal2-container input[name="swal-dren-norm"]:checked');
          if (!r) { Swal.showValidationMessage('Selecciona una normativa'); return false; }
          normIdx = parseInt(r.value);
        }
        const cats = [];
        document.querySelectorAll('.swal2-container .chk-dren-lim:checked').forEach(function(cb) {
          const v = String(cb.value || '').trim();
          if (v) cats.push(v);
        });
        if (!cats.length) { Swal.showValidationMessage('Selecciona al menos un límite'); return false; }
        return { normativa_nombre: normativas[normIdx].nombre, categorias: cats };
      }
    }).then(function(result) {
      if (!result.isConfirmed) return;
      const p = new URLSearchParams();
      p.set('id_proyecto', String(id));
      p.set('normativa_nombre', result.value.normativa_nombre);
      result.value.categorias.forEach(function(c) { p.append('categorias[]', c); });
      window.location.href = 'modules/laboratorio/muestra/controllers/ExportarDrenes.php?' + p.toString();
    });
  }).fail(function(jqXHR) {
    var msg = 'No se pudieron cargar las normativas del proyecto.';
    try { var d = JSON.parse(jqXHR.responseText); if (d && d.error) msg = d.error; } catch(e) {}
    Swal.fire('Error', msg, 'error');
  });
}
function editarProyecto(id) {
  $.ajax({ url: API_MASIVA+'?action=obtenerDetalles&id='+id, type:'GET', dataType:'json',
    success: function(resp) {
      const p=resp.proyecto||{}, detalles=resp.detalles||[], puedeEditar=!!resp.puede_editar_cantidades, esCC=esProyectoControlCalidad(p.Es_Control_Calidad), esDrene=String(p.Es_Drene)==='1'||p.Es_Drene===1, fi=normalizarFechaInput(p.Fecha_Inicio);
      const idsAct={};
      let filas='';
      detalles.forEach(function(d){ const ip=parseInt(d.Id_Producto_Venta)||0, c=parseInt(d.Cantidad_Planificada)||0; if(ip>0) idsAct[ip]=true; filas+='<tr><td class="edit-nombre-servicio">'+escapeHtml(d.Nombre_Producto)+'</td><td class="text-center"><input type="number" class="form-control form-control-sm edit-cantidad" min="'+(esCC?10:1)+'" value="'+c+'" data-id="'+ip+'"'+(puedeEditar?'':' disabled')+'></td><td class="text-center">'+(puedeEditar?'<button type="button" class="btn btn-sm btn-danger edit-remove-servicio"><i class="ti ti-trash"></i></button>':'<span class="text-muted">-</span>')+'</td></tr>'; });
      if(!filas) filas='<tr class="edit-sin-servicios"><td colspan="3" class="text-center text-muted">No hay servicios</td></tr>';
      let opsPaq='<option value="">Seleccionar paquete...</option>';
      serviciosDisponibles.forEach(function(s){ if(!idsAct[s.id]) opsPaq+='<option value="'+s.id+'">'+escapeHtml(s.nombre)+'</option>'; });
      const bloqueAgregar=puedeEditar?'<div class="row g-2 mb-2"><div class="col-md-8"><select id="edit-select-paquete" class="form-select">'+opsPaq+'</select></div><div class="col-md-2"><input id="edit-cantidad-paquete" type="number" min="'+(esCC?10:1)+'" class="form-control" value="'+(esCC?10:1)+'"></div><div class="col-md-2 d-grid"><button type="button" id="edit-btn-agregar-paquete" class="btn btn-outline-primary">Agregar</button></div></div>':'';
      const html='<div style="text-align:left;">'+(puedeEditar?'<div class="alert alert-info py-2 px-3 mb-3">Puedes editar cantidades ya que el análisis aún no inició.</div>':'<div class="alert alert-warning py-2 px-3 mb-3">El análisis ya inició. Solo puedes editar datos generales.</div>')+'<div class="row g-2"><div class="col-md-6 mb-2"><label class="form-label">Nombre del Proyecto</label><input id="edit-nombre-proyecto" class="form-control" value="'+escapeHtml(p.Nombre_Proyecto)+'"></div><div class="col-md-6 mb-2"><label class="form-label">Valle</label><input id="edit-valle" class="form-control" value="'+escapeHtml(p.Valle)+'"></div><div class="col-md-4 mb-2"><label class="form-label">Temporada</label><input id="edit-temporada" class="form-control" value="'+escapeHtml(p.Temporada)+'"></div><div class="col-md-4 mb-2"><label class="form-label">Fecha de Inicio</label><input id="edit-fecha-inicio" type="date" class="form-control" value="'+escapeHtml(fi)+'"></div><div class="col-md-4 mb-2"><label class="form-label">Tipo de Muestra</label><select id="edit-tipo-muestra" class="form-select"><option value="Agua"'+(String(p.Tipo_Muestra||'')==='Agua'?' selected':'')+'>Agua</option><option value="Suelo"'+(String(p.Tipo_Muestra||'')==='Suelo'?' selected':'')+'>Suelo</option></select></div><div class="col-md-4 mb-2"><label class="form-label">Uso de Agua</label><input id="edit-uso-agua" class="form-control" value="'+escapeHtml(p.Uso_Agua)+'"></div><div class="col-md-4 mb-2"><label class="form-label">Fuente de Agua</label><input id="edit-fuente-agua" class="form-control" value="'+escapeHtml(p.Fuente_Agua)+'"></div><div class="col-md-4 mb-2"><label class="form-label">Nivel de Agua</label><input id="edit-nivel-agua" class="form-control" value="'+escapeHtml(p.Nivel_Agua)+'"></div><div class="col-md-6 mb-2"><label class="form-check form-switch mt-2 mb-0"><input id="edit-es-control-calidad" class="form-check-input" type="checkbox"'+(esCC?' checked':'')+'>  <span class="form-check-label">Calidad de agua (mín. 10 muestras)</span></label></div><div class="col-md-6 mb-2"><label class="form-check form-switch mt-2 mb-0"><input id="edit-es-drene" class="form-check-input" type="checkbox"'+(esDrene?' checked':'')+'>  <span class="form-check-label">Drenes (mín. 10 muestras)</span></label></div></div><hr><h6>Cantidades por Venta/Servicio</h6>'+bloqueAgregar+'<div class="table-responsive" style="max-height:260px;overflow:auto;"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Servicio</th><th style="width:180px;" class="text-center">Cantidad</th><th style="width:90px;" class="text-center">Acción</th></tr></thead><tbody id="edit-tbody-servicios">'+filas+'</tbody></table></div></div>';
      Swal.fire({ title:'Editar Proyecto #'+id, html:html, width:'920px', showCancelButton:true, confirmButtonText:'Guardar', cancelButtonText:'Cancelar', focusConfirm:false,
        didOpen: function() {
          const $p=$(Swal.getPopup());
          if(puedeEditar){
            $p.on('click','#edit-btn-agregar-paquete',function(){ const ip=parseInt($p.find('#edit-select-paquete').val())||0, c=parseInt($p.find('#edit-cantidad-paquete').val())||0; if(!ip){Swal.showValidationMessage('Seleccione paquete');return;} if(!c){Swal.showValidationMessage('Ingrese cantidad');return;} const nombre=$p.find('#edit-select-paquete option:selected').text(); $p.find('#edit-tbody-servicios .edit-sin-servicios').remove(); $p.find('#edit-tbody-servicios').append('<tr><td class="edit-nombre-servicio">'+escapeHtml(nombre)+'</td><td class="text-center"><input type="number" class="form-control form-control-sm edit-cantidad" min="1" value="'+c+'" data-id="'+ip+'"></td><td class="text-center"><button type="button" class="btn btn-sm btn-danger edit-remove-servicio"><i class="ti ti-trash"></i></button></td></tr>'); $p.find('#edit-select-paquete option[value="'+ip+'"]').remove(); $p.find('#edit-select-paquete').val(''); });
            $p.on('click','.edit-remove-servicio',function(){ const $r=$(this).closest('tr'); const ip=parseInt($r.find('.edit-cantidad').data('id'))||0; const nm=$r.find('.edit-nombre-servicio').text().trim(); if(ip>0&&nm) $p.find('#edit-select-paquete').append('<option value="'+ip+'">'+escapeHtml(nm)+'</option>'); $r.remove(); if(!$p.find('#edit-tbody-servicios tr').length) $p.find('#edit-tbody-servicios').append('<tr class="edit-sin-servicios"><td colspan="3" class="text-center text-muted">Sin servicios</td></tr>'); });
          }
        },
        preConfirm: function() {
          const n=$('#edit-nombre-proyecto').val().trim(), v=$('#edit-valle').val().trim(), t=$('#edit-temporada').val().trim(), f=$('#edit-fecha-inicio').val(), tm=$('#edit-tipo-muestra').val(), esC=$('#edit-es-control-calidad').is(':checked')?1:0, esD=$('#edit-es-drene').is(':checked')?1:0;
          if(!n||!v||!t||!f||!tm){ Swal.showValidationMessage('Complete los campos obligatorios.'); return false; }
          const svs=[]; $('.edit-cantidad').each(function(){ const ip=parseInt($(this).data('id'))||0, c=parseInt($(this).val())||0; if(ip>0) svs.push({id:ip,cantidad:c}); });
          return $.ajax({ url:API_MASIVA, type:'POST', data:JSON.stringify({action:'editarProyecto',id_proyecto:id,nombre_proyecto:n,valle:v,temporada:t,fecha_inicio:f,tipo_muestra:tm,uso_agua:$('#edit-uso-agua').val().trim(),fuente_agua:$('#edit-fuente-agua').val().trim(),nivel_agua:$('#edit-nivel-agua').val().trim(),es_control_calidad:esC,es_drene:esD,servicios:svs}), contentType:'application/json', dataType:'json' })
          .then(function(r){ if(!r.success) throw new Error(r.error||'Error'); return r; })
          .catch(function(e){ Swal.showValidationMessage((e.responseJSON&&e.responseJSON.error)||e.message||'Error'); return false; });
        }
      }).then(function(r){ if(r.isConfirmed&&r.value&&r.value.success){ Swal.fire('Actualizado',r.value.mensaje||'Actualizado','success'); recargarTablasMasiva(false); } });
    }, error: function(){ Swal.fire('Error','No se pudieron cargar los datos','error'); }
  });
}
function eliminarProyecto(id) {
  Swal.fire({title:'¿Eliminar Proyecto?',text:'Esta acción no se puede deshacer',icon:'warning',showCancelButton:true,confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar'}).then(function(r){ if(!r.isConfirmed) return; $.ajax({url:API_MASIVA,type:'POST',data:{action:'eliminarProyecto',id:id},dataType:'json',success:function(resp){ if(resp.success){Swal.fire('Éxito','Proyecto eliminado','success'); recargarTablasMasiva(true);} else {Swal.fire('Error',resp.error,'error');} },error:function(){ Swal.fire('Error','Error al eliminar','error'); }}); });
}
function agregarMuestrasProyectoAnalisis(id) {
  $.getJSON(API_MASIVA+'?action=obtenerDetalles&id='+id, function(resp){
    const detalles=resp.detalles||[];
    if(!detalles.length){ Swal.fire('Aviso','No hay servicios configurados.','warning'); return; }
    let filas='';
    detalles.forEach(function(d){ const ip=parseInt(d.Id_Producto_Venta)||0, n=d.Nombre_Producto||('Servicio #'+ip), act=parseInt(d.Cantidad_Planificada)||0; filas+='<tr><td>'+escapeHtml(n)+'</td><td class="text-center"><span class="badge bg-secondary">'+act+'</span></td><td class="text-center"><input type="number" min="0" step="1" value="0" class="form-control form-control-sm extra-cantidad-input" data-id="'+ip+'" style="max-width:110px;margin:0 auto;"></td></tr>'; });
    Swal.fire({ title:'Agregar muestras', width:820, showCancelButton:true, confirmButtonText:'Crear muestras adicionales', cancelButtonText:'Cancelar', focusConfirm:false,
      html:'<div style="text-align:left;"><p class="text-muted mb-2">Ingrese cuántas muestras adicionales crear por servicio.</p><div class="table-responsive" style="max-height:320px;overflow:auto;"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Servicio</th><th class="text-center" style="width:140px;">Planificadas</th><th class="text-center" style="width:160px;">Agregar</th></tr></thead><tbody>'+filas+'</tbody></table></div></div>',
      preConfirm: function() { const ex=[]; $('.swal2-container .extra-cantidad-input').each(function(){ const ip=parseInt($(this).data('id'))||0, c=parseInt($(this).val())||0; if(ip>0&&c>0) ex.push({id:ip,cantidad_extra:c}); }); if(!ex.length){ Swal.showValidationMessage('Ingrese al menos una cantidad mayor a 0.'); return false; } return ex; }
    }).then(function(r){ if(!r.isConfirmed||!Array.isArray(r.value)||!r.value.length) return;
      Swal.fire({title:'Creando...',allowOutsideClick:false,showConfirmButton:false,didOpen:function(){Swal.showLoading();}});
      $.ajax({ url:API_MASIVA, type:'POST', data:JSON.stringify({action:'agregarMuestrasAdicionales',id_proyecto:id,extras:r.value}), contentType:'application/json', dataType:'json',
        success:function(resp){ Swal.close(); if(resp&&resp.success){ Swal.fire('Éxito',(resp.mensaje||'Muestras adicionales creadas')+(resp.muestras_creadas!==undefined?' ('+resp.muestras_creadas+' nuevas)':''),'success').then(function(){ recargarTablasMasiva(false); window.location.href='?module=laboratorio&action=muestra&subaction=analisis_proyecto&id_proyecto='+id; }); } else { Swal.fire('Error',(resp&&resp.error)||'Error','error'); } },
        error:function(err){ Swal.close(); Swal.fire('Error',(err.responseJSON&&err.responseJSON.error)||'Error','error'); }
      });
    });
  });
}

// ====================================
// TAB 3: POR DEFECTO
// ====================================
function initTabDefecto() {
  if (tablasDefectoInit) return;
  tablasDefectoInit = true;

  cargarCatalogosDefecto();

  const hoy = new Date().toISOString().split('T')[0];
  document.getElementById('filtro_fecha_hasta_bitacora').value = hoy;
  const ini = new Date(); ini.setDate(1);
  document.getElementById('filtro_fecha_desde_bitacora').value = ini.toISOString().split('T')[0];

  const tablaDefecto = $('#tabla-muestras-defecto').DataTable({
    processing: true, serverSide: true,
    ajax: { url: API_MUESTRA + '?action=obtener_muestras_por_defecto', type: 'POST' },
    columns: [
      { data: null, orderable: false, searchable: false, render: function (d, t, row) { if (t !== 'display') return row.id; const dis = parseInt(row.activo || 1) === 1 ? '' : ' disabled'; return '<input type="checkbox" class="form-check-input chk-muestra-select" value="' + row.id + '"' + dis + '>'; } },
      { data: 'id' }, { data: 'ubicacion_punto' }, { data: 'punto_toma' }, { data: 'coordenadas' },
      { data: 'valle' }, { data: 'fecha_creacion' }, { data: 'tipo_muestra' }, { data: 'turno' },
      { data: 'estado', orderable: false, searchable: false },
      { data: 'accion', orderable: false, searchable: false }
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  $('#tabla-muestras-defecto-analisis').DataTable({
    processing: true, serverSide: true,
    ajax: { url: API_MUESTRA + '?action=obtener_muestras_por_defecto_en_analisis', type: 'POST' },
    columns: [
      { data: 'id' },
      { data: null, render: function (d, t, row) { const ib = parseInt(row.id_bitacora || 0); if (ib <= 0) return '<span class="text-muted">Sin bitácora</span>'; return '<span class="badge bg-blue-lt me-1">#' + ib + '</span><span class="text-muted">' + (row.fecha_bitacora || '-') + '</span>'; } },
      { data: 'id_original' }, { data: 'ubicacion_punto' }, { data: 'punto_toma' }, { data: 'coordenadas' },
      { data: 'valle' }, { data: 'fecha_creacion' }, { data: 'tipo_muestra' }, { data: 'turno' },
      { data: 'accion', orderable: false, searchable: false }
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  tablaBitacorasDefecto = $('#tabla-bitacoras-defecto').DataTable({
    processing: true, serverSide: false, searching: false,
    ajax: {
      url: API_MUESTRA + '?action=obtener_resumen_bitacoras_por_defecto', type: 'POST',
      data: function (d) {
        d.fecha_desde = document.getElementById('filtro_fecha_desde_bitacora').value || '';
        d.fecha_hasta = document.getElementById('filtro_fecha_hasta_bitacora').value || '';
      },
      dataSrc: function (json) {
        if (!json || !json.success) { Swal.fire('Error', (json && json.message) || 'Error cargando bitácoras.', 'error'); return []; }
        return json.data || [];
      }
    },
    columns: [
      { data: 'fecha' }, { data: 'manana', orderable: false, searchable: false },
      { data: 'observacion_manana', orderable: false, searchable: false },
      { data: 'tarde', orderable: false, searchable: false },
      { data: 'observacion_tarde', orderable: false, searchable: false },
      { data: 'accion', orderable: false, searchable: false }
    ],
    language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
  });

  // Checkbox events
  $('#tabla-muestras-defecto').on('draw.dt', function () {
    document.querySelectorAll('#tabla-muestras-defecto .chk-muestra-select').forEach(function (ch) {
      if (!ch.disabled) ch.checked = muestrasSeleccionadas.has(ch.value);
    });
    actualizarCheckboxDefecto();
  });
  document.getElementById('tabla-muestras-defecto').addEventListener('change', function (e) {
    if (!e.target.classList.contains('chk-muestra-select')) return;
    if (e.target.checked) {
      muestrasSeleccionadas.add(e.target.value);
      const row = $('#tabla-muestras-defecto').DataTable().row(e.target.closest('tr')).data();
      if (row) infoMuestrasDuplicar[e.target.value] = { punto_toma: row.punto_toma || '', ubicacion: row.ubicacion_punto || '' };
    } else {
      muestrasSeleccionadas.delete(e.target.value);
      delete infoMuestrasDuplicar[e.target.value];
    }
    actualizarCheckboxDefecto();
  });
  document.getElementById('chk-todos-muestras').addEventListener('change', function (e) {
    document.querySelectorAll('#tabla-muestras-defecto .chk-muestra-select').forEach(function (ch) {
      if (ch.disabled) return;
      ch.checked = e.target.checked;
      if (e.target.checked) {
        muestrasSeleccionadas.add(ch.value);
        const row = $('#tabla-muestras-defecto').DataTable().row(ch.closest('tr')).data();
        if (row) infoMuestrasDuplicar[ch.value] = { punto_toma: row.punto_toma || '', ubicacion: row.ubicacion_punto || '' };
      } else {
        muestrasSeleccionadas.delete(ch.value);
        delete infoMuestrasDuplicar[ch.value];
      }
    });
    actualizarCheckboxDefecto();
  });
}

function actualizarCheckboxDefecto() {
  const checks = Array.from(document.querySelectorAll('#tabla-muestras-defecto .chk-muestra-select'));
  const cab = document.getElementById('chk-todos-muestras');
  if (!cab || !checks.length) { if (cab) { cab.checked = false; cab.indeterminate = false; } return; }
  const m = checks.filter(function (c) { return c.checked; }).length;
  cab.checked = m === checks.length;
  cab.indeterminate = m > 0 && m < checks.length;
}

function recargarTablasDefecto() {
  if (!tablasDefectoInit) return;
  if ($.fn.dataTable.isDataTable('#tabla-muestras-defecto')) $('#tabla-muestras-defecto').DataTable().ajax.reload(null, false);
  if ($.fn.dataTable.isDataTable('#tabla-muestras-defecto-analisis')) $('#tabla-muestras-defecto-analisis').DataTable().ajax.reload(null, false);
  if (tablaBitacorasDefecto) tablaBitacorasDefecto.ajax.reload(null, false);
}

function cargarCatalogosDefecto() {
  fetch(API_MUESTRA + '?action=obtener_catalogos_por_defecto', { method: 'POST' })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) return;
      const sA = document.getElementById('id_cliente_def');
      const sV = document.getElementById('valle_def');
      const sS = document.getElementById('select-servicio-def');
      (data.agricultores || []).forEach(function (i) { sA.insertAdjacentHTML('beforeend', '<option value="' + i.id + '">' + i.nombre + '</option>'); });
      ['Chao','Virú','Moche','Chicama'].forEach(function (v) { sV.insertAdjacentHTML('beforeend', '<option value="' + v + '">' + v + '</option>'); });
      sV.insertAdjacentHTML('beforeend', '<option value="Otros">Otros (Especificar)</option>');
      sV.addEventListener('change', function () {
        const otro = document.getElementById('valle_def_otro');
        if (sV.value === 'Otros') { otro.style.display = ''; otro.required = true; }
        else { otro.style.display = 'none'; otro.required = false; otro.value = ''; }
      });
      (data.servicios || []).forEach(function (i) { sS.insertAdjacentHTML('beforeend', '<option value="' + i.id + '">' + i.nombre + '</option>'); });
    });
}

function setupDefectoEvents() {
  document.querySelectorAll('input[name="tipo_muestra_def"]').forEach(function (r) {
    r.addEventListener('change', toggleTipoMuestraDefecto);
  });

  const btnCrearMuestraDef = document.getElementById('btn-crear-muestra-def');
  if (btnCrearMuestraDef) {
    btnCrearMuestraDef.addEventListener('click', function () {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-crear-muestra')).show();
    });
  }
  document.getElementById('btn-abrir-confirmacion').addEventListener('click', abrirConfirmacionDefecto);
  document.getElementById('btn-confirmar-guardado').addEventListener('click', guardarMuestraDefecto);
  const btnRealizarAnalisis = document.getElementById('btn-realizar-analisis');
  if (btnRealizarAnalisis) btnRealizarAnalisis.addEventListener('click', realizarAnalisisDefecto);
  document.getElementById('btn-confirmar-duplicacion').addEventListener('click', ejecutarDuplicacion);
  document.getElementById('btn-filtrar-bitacoras').addEventListener('click', function () { if (tablaBitacorasDefecto) tablaBitacorasDefecto.ajax.reload(null, false); });
  const btnExportarBitacoras = document.getElementById('btn-exportar-bitacoras-rango');
  if (btnExportarBitacoras) btnExportarBitacoras.addEventListener('click', function () {
    const fd = document.getElementById('filtro_fecha_desde_bitacora').value;
    const fh = document.getElementById('filtro_fecha_hasta_bitacora').value;
    if (!fd || !fh) { Swal.fire('Error', 'Seleccione fecha desde y hasta.', 'error'); return; }
    window.location.href = 'modules/laboratorio/muestra/controllers/ExportarBitacorasPorDefecto.php?fecha_desde=' + encodeURIComponent(fd) + '&fecha_hasta=' + encodeURIComponent(fh);
  });
  document.getElementById('modal-crear-muestra').addEventListener('hidden.bs.modal', function () { limpiarFormularioDefecto(); });
}

function toggleTipoMuestraDefecto() {
  const tipo = (document.querySelector('input[name="tipo_muestra_def"]:checked') || {}).value || 'Agua';
  document.getElementById('bloque-agua-def').classList.toggle('d-none', tipo !== 'Agua');
  document.getElementById('bloque-suelo-def').classList.toggle('d-none', tipo !== 'Suelo');
}

function obtenerPayloadDefecto() {
  const tipo = (document.querySelector('input[name="tipo_muestra_def"]:checked') || {}).value || 'Agua';
  return {
    id_cliente: document.getElementById('id_cliente_def').value,
    valle: (document.getElementById('valle_def').value === 'Otros'
      ? document.getElementById('valle_def_otro').value.trim()
      : document.getElementById('valle_def').value),
    fecha_registro: document.getElementById('fecha_registro_def').value,
    ubicacion_punto: document.getElementById('ubicacion_punto_def').value.trim(),
    punto_toma: document.getElementById('punto_toma_def').value.trim(),
    eje_x: document.getElementById('eje_x_def').value.trim(),
    eje_y: document.getElementById('eje_y_def').value.trim(),
    turno: '',
    tipo_muestra: tipo,
    observacion: document.getElementById('observacion_def').value.trim(),
    id_producto_venta: document.getElementById('select-servicio-def').value,
    uso_agua: document.getElementById('uso_agua_def').value,
    fuente_agua: document.getElementById('fuente_agua_def').value,
    nivel_agua: (document.getElementById('nivel_agua_def').value === 'Otros'
      ? (document.getElementById('nivel_agua_def_otro').value.trim() || 'Otros')
      : document.getElementById('nivel_agua_def').value),
    cantidad_agua: document.getElementById('cantidad_agua_def').value.trim(),
    fuente_riego: document.getElementById('fuente_riego_def').value.trim(),
    profundidad: document.getElementById('profundidad_def').value.trim(),
    numero_submuestras: document.getElementById('numero_submuestras_def').value,
    cantidad_suelo: document.getElementById('cantidad_suelo_def').value.trim(),
    cultivo_anterior: document.getElementById('cultivo_anterior_def').value.trim(),
    cultivo_implementado: document.getElementById('cultivo_implementado_def').value.trim(),
    cultivo_por_implementar: document.getElementById('cultivo_por_implementar_def').value.trim()
  };
}

function abrirConfirmacionDefecto() {
  const p = obtenerPayloadDefecto();
  if (!p.valle || !p.fecha_registro || !p.ubicacion_punto || !p.punto_toma || !p.id_producto_venta) {
    Swal.fire('Error', 'Por favor complete todos los campos obligatorios', 'error'); return;
  }
  const sA = document.getElementById('id_cliente_def');
  const sS = document.getElementById('select-servicio-def');
  document.getElementById('resumen-agricultor').textContent = p.id_cliente ? sA.options[sA.selectedIndex].text : 'Sin agricultor';
  document.getElementById('resumen-valle').textContent = p.valle;
  document.getElementById('resumen-tipo').textContent = p.tipo_muestra;
  document.getElementById('resumen-ubicacion').textContent = p.ubicacion_punto;
  document.getElementById('resumen-punto').textContent = p.punto_toma;
  document.getElementById('resumen-servicios').textContent = sS.options[sS.selectedIndex].text;
  new bootstrap.Modal(document.getElementById('modal-confirmar-guardado')).show();
}

function guardarMuestraDefecto() {
  const p = obtenerPayloadDefecto();
  const idEdicion = parseInt(document.getElementById('id_muestra_edicion').value || '0');
  if (idEdicion > 0) p.id_muestra = idEdicion;
  const accion = idEdicion > 0 ? 'actualizar_muestra_por_defecto' : 'guardar_muestra_por_defecto';
  const btn = document.getElementById('btn-confirmar-guardado');
  btn.disabled = true; btn.textContent = 'Guardando...';
  fetch(API_MUESTRA + '?action=' + accion, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(p) })
    .then(function (r) { return r.json(); }).then(function (data) {
      if (!data.success) throw new Error(data.message || 'Error');
      bootstrap.Modal.getInstance(document.getElementById('modal-confirmar-guardado')).hide();
      bootstrap.Modal.getInstance(document.getElementById('modal-crear-muestra')).hide();
      limpiarFormularioDefecto();
      muestrasSeleccionadas.clear();
      document.getElementById('chk-todos-muestras').checked = false;
      document.getElementById('chk-todos-muestras').indeterminate = false;
      recargarTablasDefecto();
      Swal.fire('Éxito', idEdicion > 0 ? 'Muestra actualizada' : 'Muestra creada', 'success');
    }).catch(function (err) { Swal.fire('Error', err.message, 'error'); })
    .finally(function () { btn.disabled = false; btn.textContent = 'Confirmar y Guardar'; });
}

function limpiarFormularioDefecto() {
  document.getElementById('form-muestra-defecto').reset();
  document.getElementById('id_muestra_edicion').value = '';
  document.getElementById('btn-abrir-confirmacion').textContent = 'Guardar';
  document.getElementById('modal-crear-muestra-titulo').textContent = 'Crear Muestra Original Por Defecto';
  toggleTipoMuestraDefecto();
  document.getElementById('fecha_registro_def').value = new Date().toISOString().split('T')[0];
}

function renderListaMuestrasDuplicarDefecto() {
  const tbody = document.getElementById('lista-muestras-duplicar');
  tbody.innerHTML = '';
  Array.from(muestrasSeleccionadas).sort(function (a, b) { return parseInt(a, 10) - parseInt(b, 10); }).forEach(function (id) {
    const info = infoMuestrasDuplicar[id] || {};
    const tr = document.createElement('tr');
    tr.dataset.idOriginal = id;
    const punto = (info.punto_toma || '').trim() !== '' ? info.punto_toma : '-';
    tr.innerHTML = '<td>' + id + '</td>'
      + '<td>' + punto + '</td>'
      + '<td><input type="checkbox" class="form-check-input chk-no-analizada"></td>'
      + '<td><input type="text" class="form-control form-control-sm comentario-muestra" placeholder="Ej: en mantenimiento" style="min-width:200px;"></td>';
    tbody.appendChild(tr);
  });
}

function realizarAnalisisDefecto() {
  if (muestrasSeleccionadas.size === 0) { Swal.fire('Error', 'Seleccione al menos una muestra.', 'error'); return; }
  document.getElementById('fecha_duplicacion').value = new Date().toISOString().split('T')[0];
  renderListaMuestrasDuplicarDefecto();
  new bootstrap.Modal(document.getElementById('modal-duplicar-muestras')).show();
}

function ejecutarDuplicacion() {
  const fecha = document.getElementById('fecha_duplicacion').value;
  const turnoSel = document.querySelector('input[name="turno_duplicacion"]:checked');
  const turno = turnoSel ? turnoSel.value : '';
  if (!fecha || !turno) { Swal.fire('Error', 'Complete todos los campos.', 'error'); return; }
  const ids = Array.from(muestrasSeleccionadas).map(function (i) { return parseInt(i); });
  const observaciones = {};
  document.querySelectorAll('#lista-muestras-duplicar tr').forEach(function (tr) {
    const idOrig = parseInt(tr.dataset.idOriginal, 10);
    if (!idOrig) return;
    const noAnalizada = tr.querySelector('.chk-no-analizada').checked;
    const comentario = tr.querySelector('.comentario-muestra').value.trim();
    if (noAnalizada || comentario !== '') {
      observaciones[idOrig] = { no_analizada: noAnalizada, observacion: comentario };
    }
  });
  const btn = document.getElementById('btn-confirmar-duplicacion');
  btn.disabled = true; btn.textContent = 'Procesando...';
  fetch(API_MUESTRA + '?action=duplicar_muestras_por_defecto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids_muestras: ids, fecha_registro: fecha, turno: turno, observaciones: observaciones }) })
    .then(function (r) { return r.json(); }).then(function (data) {
      if (!data.success) throw new Error(data.message || 'Error');
      bootstrap.Modal.getInstance(document.getElementById('modal-duplicar-muestras')).hide();
      muestrasSeleccionadas.clear();
      document.getElementById('chk-todos-muestras').checked = false;
      document.getElementById('chk-todos-muestras').indeterminate = false;
      recargarTablasDefecto();
      Swal.fire('Éxito', 'Se crearon ' + (data.total || 0) + ' muestra(s). Bitácora #' + (data.id_bitacora || 0), 'success').then(function () {
        const idM = parseInt(data.id_muestra_inicial || 0), idB = parseInt(data.id_bitacora || 0);
        if (idM > 0) window.location.href = '?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_muestra=' + idM + '&id_bitacora=' + idB + '&agricultor=' + encodeURIComponent('Muestra por defecto');
      });
    }).catch(function (err) { Swal.fire('Error', err.message, 'error'); })
    .finally(function () { btn.disabled = false; btn.textContent = 'Crear Duplicados'; });
}

window.editarMuestraPorDefecto = function (idMuestra) {
  fetch(API_MUESTRA + '?action=obtener_muestra_por_defecto&id_muestra=' + idMuestra, { method: 'GET' })
    .then(function (r) { return r.json(); }).then(function (data) {
      if (!data.success || !data.data) throw new Error(data.message || 'No se pudo cargar');
      const d = data.data;
      document.getElementById('id_muestra_edicion').value = d.Id_Muestra || '';
      document.getElementById('id_cliente_def').value = d.Id_Cliente || '';
      document.getElementById('valle_def').value = d.Valle || '';
      // Si el valor cargado no está en el select (valle personalizado), usar opción Otros
      const sVD = document.getElementById('valle_def');
      const valleOtroD = document.getElementById('valle_def_otro');
      if (d.Valle && sVD.value === '') {
        sVD.value = 'Otros';
        valleOtroD.style.display = '';
        valleOtroD.required = true;
        valleOtroD.value = d.Valle;
      } else {
        valleOtroD.style.display = 'none';
        valleOtroD.required = false;
        valleOtroD.value = '';
      }
      document.getElementById('fecha_registro_def').value = d.Fecha_Registro || '';
      document.getElementById('ubicacion_punto_def').value = d.Ubicacion_Punto || '';
      document.getElementById('punto_toma_def').value = d.Punto_Toma || '';
      document.getElementById('eje_x_def').value = d.Eje_X || '';
      document.getElementById('eje_y_def').value = d.Eje_Y || '';
      document.getElementById('observacion_def').value = d.Observacion_Muestra || '';
      document.getElementById('select-servicio-def').value = d.Id_Producto_Venta || '';
      const tipo = d.Tipo_Muestra || 'Agua';
      document.querySelectorAll('input[name="tipo_muestra_def"]').forEach(function (r) { r.checked = r.value === tipo; });
      toggleTipoMuestraDefecto();
      document.getElementById('uso_agua_def').value = d.Uso_Agua || '';
      document.getElementById('fuente_agua_def').value = d.Fuente_Agua || '';
      const _nivelStdDef = ['Rio','Pozo','Canal','Reservorio','Otros',''];
      const _nivelValDef = d.Nivel_Agua || '';
      if (_nivelValDef && !_nivelStdDef.includes(_nivelValDef)) {
        document.getElementById('nivel_agua_def').value = 'Otros';
        document.getElementById('nivel_agua_def_otro').value = _nivelValDef;
        document.getElementById('nivel_agua_def_otro').style.display = 'block';
      } else {
        document.getElementById('nivel_agua_def').value = _nivelValDef;
        document.getElementById('nivel_agua_def_otro').style.display = 'none';
      }
      document.getElementById('cantidad_agua_def').value = d.Cantidad_Muestra_Agua || '1 Litro';
      document.getElementById('fuente_riego_def').value = d.Fuente_Riego || '';
      document.getElementById('profundidad_def').value = d.Profundidad || '';
      document.getElementById('numero_submuestras_def').value = d.Numero_Submuestras || '';
      document.getElementById('cantidad_suelo_def').value = d.Cantidad_Muestra_Suelo || '1 Kg';
      document.getElementById('cultivo_anterior_def').value = d.Cultivo_Anterior || '';
      document.getElementById('cultivo_implementado_def').value = d.Cultivo_Implementado || '';
      document.getElementById('cultivo_por_implementar_def').value = d.Cultivo_Por_Implementar || '';
      document.getElementById('btn-abrir-confirmacion').textContent = 'Actualizar';
      document.getElementById('modal-crear-muestra-titulo').textContent = 'Editar Muestra Por Defecto';
      new bootstrap.Modal(document.getElementById('modal-crear-muestra')).show();
    }).catch(function (err) { Swal.fire('Error', err.message, 'error'); });
};

window.desactivarMuestraPorDefecto = function (idMuestra) {
  Swal.fire({ title: '¿Desactivar?', text: 'Podrá reactivarse después.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Desactivar', cancelButtonText: 'Cancelar' }).then(function (r) {
    if (!r.isConfirmed) return;
    fetch(API_MUESTRA + '?action=desactivar_muestra_por_defecto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_muestra: idMuestra }) })
      .then(function (res) { return res.json(); }).then(function (data) {
        if (!data.success) throw new Error(data.message);
        muestrasSeleccionadas.delete(String(idMuestra));
        recargarTablasDefecto();
        Swal.fire('Éxito', 'Muestra desactivada', 'success');
      }).catch(function (err) { Swal.fire('Error', err.message, 'error'); });
  });
};

window.reactivarMuestraPorDefecto = function (idMuestra) {
  Swal.fire({ title: '¿Reactivar?', icon: 'question', showCancelButton: true, confirmButtonText: 'Reactivar', cancelButtonText: 'Cancelar' }).then(function (r) {
    if (!r.isConfirmed) return;
    fetch(API_MUESTRA + '?action=reactivar_muestra_por_defecto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_muestra: idMuestra }) })
      .then(function (res) { return res.json(); }).then(function (data) {
        if (!data.success) throw new Error(data.message);
        recargarTablasDefecto();
        Swal.fire('Éxito', 'Muestra reactivada', 'success');
      }).catch(function (err) { Swal.fire('Error', err.message, 'error'); });
  });
};

window.crearBitacoraTurno = function (fecha, turno) {
  Swal.fire({ title: 'Crear bitácora ' + turno, html: '<div class="text-start"><strong>Fecha:</strong> ' + fecha + '</div>', input: 'text', inputPlaceholder: 'Observación (opcional)', showCancelButton: true, confirmButtonText: 'Crear', cancelButtonText: 'Cancelar' }).then(function (r) {
    if (!r.isConfirmed) return;
    fetch(API_MUESTRA + '?action=crear_bitacora_por_defecto_turno', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ fecha_registro: fecha, turno: turno, observacion: (r.value || '').trim() }) })
      .then(function (res) { return res.json(); }).then(function (data) {
        if (!data.success) throw new Error(data.message);
        if (tablaBitacorasDefecto) tablaBitacorasDefecto.ajax.reload(null, false);
        Swal.fire('Éxito', 'Bitácora creada', 'success');
      }).catch(function (err) { Swal.fire('Error', err.message, 'error'); });
  });
};

window.abrirObservacionBitacora = function (idBitacora, observacion, fecha, turno) {
  Swal.fire({ title: 'Observación bitácora #' + idBitacora, html: '<div class="text-start mb-2"><strong>Fecha:</strong> ' + fecha + ' | <strong>Turno:</strong> ' + turno + '</div>', input: 'text', inputValue: observacion || '', inputPlaceholder: 'Ingrese observación', showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar' }).then(function (r) {
    if (!r.isConfirmed) return;
    fetch(API_MUESTRA + '?action=actualizar_observacion_bitacora_por_defecto', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_bitacora: idBitacora, observacion: (r.value || '').trim() }) })
      .then(function (res) { return res.json(); }).then(function (data) {
        if (!data.success) throw new Error(data.message);
        if (tablaBitacorasDefecto) tablaBitacorasDefecto.ajax.reload(null, false);
        Swal.fire('Éxito', 'Observación actualizada', 'success');
      }).catch(function (err) { Swal.fire('Error', err.message, 'error'); });
  });
};

window.abrirDetalleBitacoraFecha = function (fecha) {
  document.getElementById('titulo-detalle-bitacora-fecha').textContent = 'Bitácoras - ' + fecha;
  document.getElementById('contenido-detalle-bitacora-fecha').innerHTML = '<div class="text-center text-muted py-4">Cargando...</div>';
  new bootstrap.Modal(document.getElementById('modal-detalle-bitacora-fecha')).show();
  fetch(API_MUESTRA + '?action=obtener_detalle_bitacora_por_fecha&fecha=' + encodeURIComponent(fecha))
    .then(function (r) { return r.json(); }).then(function (data) {
      if (!data.success) throw new Error(data.message);
      const renderTurno = function (titulo, td) {
        const idB = parseInt(td.id_bitacora || 0), total = parseInt(td.total_muestras || 0), obs = (td.observacion || '').trim();
        if (idB <= 0) return '<div class="card h-100"><div class="card-header"><h3 class="card-title mb-0">Turno ' + titulo + '</h3></div><div class="card-body"><div class="alert alert-warning mb-0">Sin bitácora para este turno.</div></div></div>';
        const btnC = td.tiene_pendientes ? '<a class="btn btn-sm btn-primary" href="?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_bitacora=' + idB + '&agricultor=' + encodeURIComponent('Muestra por defecto') + '">Continuar análisis</a>' : '';
        const resultados = Array.isArray(td.resultados) ? td.resultados : [];
        let ultimoIdMuestra = null;
        let filas = resultados.length ? resultados.map(function (r) {
          const esPrimeraFilaMuestra = String(r.id_muestra) !== String(ultimoIdMuestra);
          ultimoIdMuestra = r.id_muestra;
          const noAnalizada = parseInt(r.no_analizada || 0, 10) === 1;
          const obsMuestra = (r.observacion_muestra || '').trim();
          const estadoHtml = noAnalizada ? '<span class="badge bg-danger">NO ANALIZADA</span>' : escapeHtml(r.estado || '-');
          const obsHtml = (esPrimeraFilaMuestra && obsMuestra !== '') ? escapeHtml(obsMuestra) : '<span class="text-muted">-</span>';
          return '<tr><td>' + r.id_muestra + '</td><td>' + escapeHtml(r.ubicacion_punto||'-') + '</td><td>' + escapeHtml(r.punto_toma||'-') + '</td><td>' + escapeHtml(r.parametro||'-') + '</td><td>' + escapeHtml(r.unidad||'') + '</td><td>' + escapeHtml(r.valor_hallado||'(pendiente)') + '</td><td>' + estadoHtml + '</td><td>' + obsHtml + '</td></tr>';
        }).join('') : '<tr><td colspan="8" class="text-muted">Sin resultados.</td></tr>';
        return '<div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Turno ' + titulo + ' <span class="text-muted">#' + idB + '</span></h3>' + btnC + '</div><div class="card-body"><div class="mb-2"><strong>Muestras:</strong> ' + total + '</div><div class="mb-2 text-muted">' + (obs || '(sin observación)') + '</div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>ID</th><th>Ubicación</th><th>Punto de toma</th><th>Parámetro</th><th>Unidad</th><th>Valor</th><th>Estado</th><th>Observación</th></tr></thead><tbody>' + filas + '</tbody></table></div></div></div>';
      };
      document.getElementById('contenido-detalle-bitacora-fecha').innerHTML = '<div class="row g-3"><div class="col-12 col-xl-6">' + renderTurno('Mañana', data.manana || {}) + '</div><div class="col-12 col-xl-6">' + renderTurno('Tarde', data.tarde || {}) + '</div></div>';
    }).catch(function (err) { document.getElementById('contenido-detalle-bitacora-fecha').innerHTML = '<div class="alert alert-danger">' + escapeHtml(err.message || 'Error') + '</div>'; });
};
</script>
