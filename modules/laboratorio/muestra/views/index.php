<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
    .alert-info {
        background-color: #e8f4f8;
        border-left: 4px solid #17a2b8;
    }
  .nav-tabs .nav-link {
    font-weight: 600;
  }
  .table-error {
    display: none;
  }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Muestras</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">MUESTRAS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Gestión y registro de ingresos de muestras para análisis físico, químicos y microbiológicos</div>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal-crear-muestra-comun">
          <i class="ti ti-plus me-2"></i> Creación Individual
        </button>
      </div>
      <div class="col-auto">
        <a href="?module=laboratorio&action=muestra&subaction=creacion_masiva" class="btn btn-success">
          <i class="ti ti-plus me-2"></i> Creación Masiva
        </a>
      </div>
      <div class="col-auto">
        <a href="?module=laboratorio&action=muestra&subaction=por_defecto" class="btn btn-primary">
          <i class="ti ti-flash me-2"></i> Por Defecto
        </a>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info" role="alert">
      <div>
        Utilice la <strong>Creación Masiva</strong> para generar los registros de muestras correspondientes al monitoreo programado de todos los valles.
        Para los ingresos ordinarios que se procesen diariamente en el laboratorio, utilice la opción <strong>Creación Individual</strong>. Asegúrese de verificar la 
        integridad del envase antes de confirmar la recepción.
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Gestión de Muestras por Estado</h3>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <ul class="nav nav-pills" id="tabs-tipo-servicio" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" type="button" data-tipo-servicio="todos">Todos</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" type="button" data-tipo-servicio="interno">Interno</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" type="button" data-tipo-servicio="externo">Externo</button>
            </li>
          </ul>
        </div>

        <ul class="nav nav-tabs mb-3" id="tabs-muestras" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-recepcionar" data-bs-toggle="tab" data-bs-target="#panel-recepcionar" type="button" role="tab" aria-controls="panel-recepcionar" aria-selected="true">
              Por Recepcionar
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-pendiente-analisis" data-bs-toggle="tab" data-bs-target="#panel-pendiente-analisis" type="button" role="tab" aria-controls="panel-pendiente-analisis" aria-selected="false">
              Pendiente a Análisis
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-en-analisis" data-bs-toggle="tab" data-bs-target="#panel-en-analisis" type="button" role="tab" aria-controls="panel-en-analisis" aria-selected="false">
              En Análisis
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-por-firmar" data-bs-toggle="tab" data-bs-target="#panel-por-firmar" type="button" role="tab" aria-controls="panel-por-firmar" aria-selected="false">
              Por Firmar
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-muestras-pasadas" data-bs-toggle="tab" data-bs-target="#panel-muestras-pasadas" type="button" role="tab" aria-controls="panel-muestras-pasadas" aria-selected="false">
              Muestras Pasadas
            </button>
          </li>
        </ul>

        <div id="tabla-error-global" class="alert alert-danger table-error" role="alert"></div>

        <div class="tab-content" id="tabs-muestras-content">
          <div class="tab-pane fade show active" id="panel-recepcionar" role="tabpanel" aria-labelledby="tab-recepcionar">
            <p class="text-muted small">Registros que requieren revisión previa al inicio del análisis. Use la acción para ir al formulario de recepción.</p>
            <div class="table-responsive">
              <table id="tabla-recepcionar" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Agricultor</th>
                    <th>Coordenadas</th>
                    <th>Valle</th>
                    <th>Fecha de Toma</th>
                    <th>Tipo de Servicio</th>
                    <th>Tipo de Muestra</th>
                    <th>Acción</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="panel-pendiente-analisis" role="tabpanel" aria-labelledby="tab-pendiente-analisis">
            <p class="text-muted small">Muestras registradas y pendientes de pasar a análisis (estado Pendiente o Recepcionado).</p>
            <div class="table-responsive">
              <table id="tabla-pendientes" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Agricultor</th>
                    <th>Coordenadas</th>
                    <th>Valle</th>
                    <th>Fecha de Recepción</th>
                    <th>Tipo de Servicio</th>
                    <th>Estado</th>
                    <th>Tipo de Muestra</th>
                    <th>Acción</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="panel-en-analisis" role="tabpanel" aria-labelledby="tab-en-analisis">
            <p class="text-muted small">Muestras que están actualmente en ejecución de análisis.</p>
            <div class="table-responsive">
              <table id="tabla-en-analisis" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Agricultor</th>
                    <th>Valle</th>
                    <th>Fecha Recepción</th>
                    <th>Tipo de Servicio</th>
                    <th>Tipo de Muestra</th>
                    <th>Acción</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="panel-por-firmar" role="tabpanel" aria-labelledby="tab-por-firmar">
            <p class="text-muted small">Muestras analizadas y listas para firma técnica.</p>
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
                    <th>Tipo de Muestra</th>
                    <th>Acción</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="panel-muestras-pasadas" role="tabpanel" aria-labelledby="tab-muestras-pasadas">
            <p class="text-muted small">Muestras finalizadas para revisión histórica.</p>
            <div class="table-responsive">
              <table id="tabla-muestras-pasadas" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Agricultor</th>
                    <th>Valle</th>
                    <th>Tipo de Servicio</th>
                    <th>Fecha de Análisis</th>
                    <th>Fecha de Firma</th>
                    <th>Estado</th>
                    <th>Tipo de Muestra</th>
                    <th>Acción</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<div class="modal modal-blur fade" id="modal-crear-muestra-comun" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Creación Individual de Muestra Común</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 px-3 mb-3">
          Esta opción registra una muestra normal (sin punto de toma ni ubicación de toma) y la deja en estado <strong>Pendiente</strong>.
        </div>

        <div id="alerta-crear-individual" class="alert alert-danger d-none"></div>

        <form id="form-crear-muestra-individual" class="row g-3" onsubmit="return false;">
          <div class="col-md-4">
            <label class="form-label">Tipo de servicio <span class="text-danger">*</span></label>
            <select id="ci_tipo_servicio" class="form-select" required>
              <option value="Interno">Interno</option>
              <option value="Externo">Externo</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Agricultor / Cliente <span class="text-danger">*</span></label>
            <select id="ci_id_cliente" class="form-select" required>
              <option value="">Seleccione...</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Valle <span class="text-danger">*</span></label>
            <select id="ci_valle" class="form-select" required>
              <option value="">Seleccione...</option>
            </select>
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
            <select id="ci_id_producto_venta" class="form-select" required>
              <option value="">Seleccione...</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Observación</label>
            <input id="ci_observacion" type="text" class="form-control" placeholder="Opcional">
          </div>

          <div class="col-md-12">
            <label class="form-label d-block mb-2">Tipo de muestra <span class="text-danger">*</span></label>
            <label class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="ci_tipo_muestra" value="Agua" checked>
              <span class="form-check-label">Agua</span>
            </label>
            <label class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="ci_tipo_muestra" value="Suelo">
              <span class="form-check-label">Suelo</span>
            </label>
          </div>

          <div id="ci_bloque_agua" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Uso de agua</label>
              <select id="ci_uso_agua" class="form-select">
                <option value="">Seleccionar</option>
                <option value="Consumo Humano">Consumo Humano</option>
                <option value="Riego">Riego</option>
                <option value="Industrial">Industrial</option>
                <option value="Otro">Otro</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Fuente de agua</label>
              <select id="ci_fuente_agua" class="form-select">
                <option value="">Seleccionar</option>
                <option value="Rio">Rio</option>
                <option value="Pozo">Pozo</option>
                <option value="Canal">Canal</option>
                <option value="Reservorio">Reservorio</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nivel de agua</label>
              <select id="ci_nivel_agua" class="form-select">
                <option value="">Seleccionar</option>
                <option value="Superficial">Superficial</option>
                <option value="Subterranea">Subterranea</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cantidad</label>
              <input id="ci_cantidad_agua" type="text" class="form-control" value="1 Litro">
            </div>
          </div>

          <div id="ci_bloque_suelo" class="row g-3 d-none">
            <div class="col-md-3">
              <label class="form-label">Fuente de riego</label>
              <input id="ci_fuente_riego" type="text" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-md-3">
              <label class="form-label">Profundidad</label>
              <input id="ci_profundidad" type="text" class="form-control" placeholder="Ej: 0-30 cm">
            </div>
            <div class="col-md-3">
              <label class="form-label">Nro submuestras</label>
              <input id="ci_numero_submuestras" type="number" min="1" class="form-control" placeholder="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Cantidad</label>
              <input id="ci_cantidad_suelo" type="text" class="form-control" value="1 Kg">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-individual-modal">
          <i class="ti ti-device-floppy me-1"></i> Guardar muestra
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-iniciar-analisis" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-inicio-titulo">Confirmación para Análisis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1" id="modal-inicio-mensaje">¿Está seguro de que desea comenzar el análisis para la muestra?</p>
        <div class="text-danger small mb-2" id="modal-inicio-detalle">* Se registrará al analista y no se permitirá que otro ingrese.</div>
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
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">¿Registrar firma de jefatura para la muestra <strong id="firma-id-muestra">-</strong>?</p>
        <div class="text-muted small mb-2">Al confirmar, la muestra pasará de <strong>Por Firmar</strong> a <strong>Finalizado</strong>.</div>

        <label class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="chk-firmar-todos" checked>
          <span class="form-check-label text-success" id="lbl-firmar-todos">Firmar todas las muestras del agricultor en estado Por Firmar</span>
        </label>

        <div class="alert alert-info py-2 px-3 small mb-2" id="firma-resumen">Cargando detalle...</div>
        <div class="table-responsive" style="max-height: 220px; overflow:auto;">
          <table class="table table-sm table-striped mb-0">
            <thead>
              <tr>
                <th>ID Muestra</th>
                <th>Servicio</th>
                <th>Parámetro</th>
                <th>Valor</th>
              </tr>
            </thead>
            <tbody id="tbody-detalle-firma"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-firma">Firmar y Finalizar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    console.log('Módulo de Muestras cargado');

  let muestraInicioAnalisis = {
    idMuestra: 0,
    idCliente: 0,
    agricultor: '',
    origen: 'pendiente'
  };
  let firmaContexto = {
    idMuestra: 0,
    idCliente: 0,
    agricultor: ''
  };
  let tipoServicioFiltroActual = 'todos';
  let catalogosCreacionIndividualCargados = false;

  function mostrarErrorCreacionIndividual(mensaje) {
    const el = document.getElementById('alerta-crear-individual');
    if (!el) return;
    el.textContent = mensaje;
    el.classList.remove('d-none');
  }

  function limpiarErrorCreacionIndividual() {
    const el = document.getElementById('alerta-crear-individual');
    if (!el) return;
    el.textContent = '';
    el.classList.add('d-none');
  }

  function toggleTipoMuestraCreacionIndividual() {
    const tipo = document.querySelector('input[name="ci_tipo_muestra"]:checked');
    const valor = tipo ? tipo.value : 'Agua';
    document.getElementById('ci_bloque_agua').classList.toggle('d-none', valor !== 'Agua');
    document.getElementById('ci_bloque_suelo').classList.toggle('d-none', valor !== 'Suelo');
  }

  function cargarCatalogosCreacionIndividual() {
    if (catalogosCreacionIndividualCargados) {
      return Promise.resolve();
    }

    return fetch('modules/laboratorio/muestra/controllers/MuestraAPI.php?action=obtener_catalogos_por_defecto', {
      method: 'POST'
    })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (!data.success) {
          throw new Error(data.message || 'No se pudieron cargar los catálogos.');
        }

        const selectCliente = document.getElementById('ci_id_cliente');
        const selectValle = document.getElementById('ci_valle');
        const selectProducto = document.getElementById('ci_id_producto_venta');

        (data.agricultores || []).forEach(function(item) {
          const option = document.createElement('option');
          option.value = item.id;
          option.textContent = item.nombre;
          selectCliente.appendChild(option);
        });

        (data.valles || []).forEach(function(item) {
          const option = document.createElement('option');
          option.value = item;
          option.textContent = item;
          selectValle.appendChild(option);
        });

        (data.servicios || []).forEach(function(item) {
          const option = document.createElement('option');
          option.value = item.id;
          option.textContent = item.nombre;
          selectProducto.appendChild(option);
        });

        catalogosCreacionIndividualCargados = true;
      });
  }

  function obtenerPayloadCreacionIndividual() {
    const tipo = document.querySelector('input[name="ci_tipo_muestra"]:checked');
    return {
      tipo_servicio: document.getElementById('ci_tipo_servicio').value,
      id_cliente: document.getElementById('ci_id_cliente').value,
      valle: document.getElementById('ci_valle').value,
      fecha_toma: document.getElementById('ci_fecha_toma').value,
      eje_x: document.getElementById('ci_eje_x').value.trim(),
      eje_y: document.getElementById('ci_eje_y').value.trim(),
      tipo_muestra: tipo ? tipo.value : 'Agua',
      id_producto_venta: document.getElementById('ci_id_producto_venta').value,
      observacion: document.getElementById('ci_observacion').value.trim(),
      uso_agua: document.getElementById('ci_uso_agua').value,
      fuente_agua: document.getElementById('ci_fuente_agua').value,
      nivel_agua: document.getElementById('ci_nivel_agua').value,
      cantidad_agua: document.getElementById('ci_cantidad_agua').value.trim(),
      fuente_riego: document.getElementById('ci_fuente_riego').value.trim(),
      profundidad: document.getElementById('ci_profundidad').value.trim(),
      numero_submuestras: document.getElementById('ci_numero_submuestras').value,
      cantidad_suelo: document.getElementById('ci_cantidad_suelo').value.trim()
    };
  }

  function validarCreacionIndividual(payload) {
    if (!payload.id_cliente || !payload.valle || !payload.fecha_toma || !payload.id_producto_venta) {
      return 'Complete todos los campos obligatorios.';
    }
    if (payload.tipo_servicio !== 'Interno' && payload.tipo_servicio !== 'Externo') {
      return 'Seleccione un tipo de servicio válido.';
    }
    return '';
  }

  function guardarCreacionIndividual() {
    limpiarErrorCreacionIndividual();
    const payload = obtenerPayloadCreacionIndividual();
    const error = validarCreacionIndividual(payload);
    if (error) {
      Swal.fire('Validación', error, 'warning');
      return;
    }

    const btn = document.getElementById('btn-guardar-individual-modal');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader me-1"></i> Guardando...';

    fetch('modules/laboratorio/muestra/controllers/MuestraAPI.php?action=guardar_muestra_individual', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (!data.success) {
          throw new Error(data.message || 'No se pudo guardar la muestra.');
        }

        const modalEl = document.getElementById('modal-crear-muestra-comun');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
          modal.hide();
        }

        Swal.fire('Éxito', 'Muestra creada correctamente. ID: ' + (data.id_muestra || 0), 'success');
        $('#tabla-pendientes').DataTable().ajax.reload(null, false);
        $('#tabla-recepcionar').DataTable().ajax.reload(null, false);
      })
      .catch(function(err) {
        const mensaje = err.message || 'Error de red al guardar la muestra.';
        mostrarErrorCreacionIndividual(mensaje);
        Swal.fire('Error', mensaje, 'error');
      })
      .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Guardar muestra';
      });
  }

  function mostrarErrorTabla(mensaje) {
    $('#tabla-error-global').text(mensaje).show();
  }

  function ajaxConfig(urlTabla, nombreTabla) {
    return {
      url: urlTabla,
      type: 'POST',
      data: function(d) {
        d.tipo_servicio = tipoServicioFiltroActual;
      },
      error: function(xhr) {
        var detalle = xhr && xhr.responseText ? xhr.responseText.substring(0, 300) : 'Sin detalle';
        mostrarErrorTabla('Error cargando ' + nombreTabla + '. ' + detalle);
      }
    };
  }

  function recargarTablasPorTipoServicio() {
    $('#tabla-pendientes').DataTable().ajax.reload(null, false);
    $('#tabla-recepcionar').DataTable().ajax.reload(null, false);
    $('#tabla-en-analisis').DataTable().ajax.reload(null, false);
    $('#tabla-por-firmar').DataTable().ajax.reload(null, false);
    $('#tabla-muestras-pasadas').DataTable().ajax.reload(null, false);
  }

    // Tabla de Muestras Pendientes
    // Columnas: 0=No | 1=Agricultor | 2=Coordenadas | 3=Valle | 4=FechaRecepcion | 5=TipoServicio | 6=Estado | 7=TipoMuestra | 8=Acción
    $('#tabla-pendientes').DataTable({
        processing: true,
        serverSide: true,
    ajax: ajaxConfig('modules/laboratorio/muestra/views/data_pendientes.php', 'Pendiente a Análisis'),
        columnDefs: [
            { orderable: false, targets: [8] }
        ],
        columns: [
            { data: 0, title: 'No' },
            { data: 1, title: 'Agricultor' },
            { data: 2, title: 'Coordenadas' },
            { data: 3, title: 'Valle' },
            { data: 4, title: 'Fecha de Recepción' },
            { data: 5, title: 'Tipo de Servicio' },
            { data: 6, title: 'Estado' },
            { data: 7, title: 'Tipo de Muestra' },
            { data: 8, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Tabla de Muestras por Recepcionar
    // Columnas: 0=No | 1=Agricultor | 2=Coordenadas | 3=Valle | 4=FechaToma | 5=TipoServicio | 6=TipoMuestra | 7=Acción
    $('#tabla-recepcionar').DataTable({
        processing: true,
        serverSide: true,
        ajax: ajaxConfig('modules/laboratorio/muestra/views/data_recepcionar.php', 'Por Recepcionar'),
        columnDefs: [
            { orderable: false, targets: [7] }
        ],
        columns: [
            { data: 0, title: 'No' },
            { data: 1, title: 'Agricultor' },
            { data: 2, title: 'Coordenadas' },
            { data: 3, title: 'Valle' },
            { data: 4, title: 'Fecha de Toma' },
            { data: 5, title: 'Tipo de Servicio' },
            { data: 6, title: 'Tipo de Muestra' },
            { data: 7, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

      $('#tabla-en-analisis').DataTable({
        processing: true,
        serverSide: true,
        ajax: ajaxConfig('modules/laboratorio/muestra/views/data_progreso.php', 'En Análisis'),
        columnDefs: [
          { orderable: false, targets: [6] }
        ],
        columns: [
          { data: 0, title: 'ID' },
          { data: 1, title: 'Agricultor' },
          { data: 2, title: 'Valle' },
          { data: 3, title: 'Fecha Recepción' },
          { data: 4, title: 'Tipo de Servicio' },
          { data: 5, title: 'Tipo de Muestra' },
          { data: 6, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
      });

      $('#tabla-por-firmar').DataTable({
        processing: true,
        serverSide: true,
        ajax: ajaxConfig('modules/laboratorio/muestra/views/data_firmar.php', 'Por Firmar'),
        columnDefs: [
          { orderable: false, targets: [7] }
        ],
        columns: [
          { data: 0, title: 'ID' },
          { data: 1, title: 'Agricultor' },
          { data: 2, title: 'Valle' },
          { data: 3, title: 'Tipo de Servicio' },
          { data: 4, title: 'Fecha de Análisis' },
          { data: 5, title: 'Estado' },
          { data: 6, title: 'Tipo de Muestra' },
          { data: 7, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
      });

      $('#tabla-muestras-pasadas').DataTable({
        processing: true,
        serverSide: true,
        ajax: ajaxConfig('modules/laboratorio/muestra/views/data_pasadas.php', 'Muestras Pasadas'),
        columnDefs: [
          { orderable: false, targets: [8] }
        ],
        columns: [
          { data: 0, title: 'ID' },
          { data: 1, title: 'Agricultor' },
          { data: 2, title: 'Valle' },
          { data: 3, title: 'Tipo de Servicio' },
          { data: 4, title: 'Fecha de Análisis' },
          { data: 5, title: 'Fecha de Firma' },
          { data: 6, title: 'Estado' },
          { data: 7, title: 'Tipo de Muestra' },
          { data: 8, orderable: false, searchable: false, title: 'Acción' }
        ],
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
      });

      window.abrirModalComenzarAnalisis = function(idMuestra, idCliente, agricultor) {
        muestraInicioAnalisis.idMuestra = parseInt(idMuestra || 0, 10);
        muestraInicioAnalisis.idCliente = parseInt(idCliente || 0, 10);
        muestraInicioAnalisis.agricultor = agricultor || '';
        muestraInicioAnalisis.origen = 'pendiente';

        document.getElementById('modal-inicio-titulo').textContent = 'Confirmación para Análisis';
        document.getElementById('modal-inicio-mensaje').textContent = '¿Está seguro de que desea comenzar el análisis para la muestra?';
        document.getElementById('modal-inicio-detalle').textContent = '* Se registrará al analista y no se permitirá que otro ingrese.';
        document.getElementById('lbl-iniciar-todos').textContent = 'Comenzar todos los análisis pendientes del agricultor';

        const chk = document.getElementById('chk-iniciar-todos');
        chk.checked = true;
        chk.disabled = false;

        const modal = new bootstrap.Modal(document.getElementById('modal-iniciar-analisis'));
        modal.show();
      };

      window.abrirModalContinuarAnalisis = function(idMuestra, idCliente, agricultor) {
        muestraInicioAnalisis.idMuestra = parseInt(idMuestra || 0, 10);
        muestraInicioAnalisis.idCliente = parseInt(idCliente || 0, 10);
        muestraInicioAnalisis.agricultor = agricultor || '';
        muestraInicioAnalisis.origen = 'en_analisis';

        document.getElementById('modal-inicio-titulo').textContent = 'Continuar análisis del agricultor';
        document.getElementById('modal-inicio-mensaje').textContent = 'Se continuará con todos los análisis pendientes del agricultor seleccionado. ¿Desea continuar?';
        document.getElementById('modal-inicio-detalle').textContent = '* Solo se incluirán muestras en estado Recepcionado. Las muestras Pendiente no se modificarán.';
        document.getElementById('lbl-iniciar-todos').textContent = 'Continuar todos los análisis pendientes del agricultor';

        const chk = document.getElementById('chk-iniciar-todos');
        chk.checked = true;
        chk.disabled = true;

        const modal = new bootstrap.Modal(document.getElementById('modal-iniciar-analisis'));
        modal.show();
      };

      function renderDetalleFirma(detalle) {
        const tbody = document.getElementById('tbody-detalle-firma');
        const resumen = document.getElementById('firma-resumen');
        tbody.innerHTML = '';

        const muestras = Array.isArray(detalle.muestras) ? detalle.muestras : [];
        const resultados = Array.isArray(detalle.resultados) ? detalle.resultados : [];

        const agricultor = detalle.agricultor || firmaContexto.agricultor || '-';
        resumen.textContent = 'Agricultor: ' + agricultor + ' | Muestras por firmar: ' + muestras.length + ' | Resultados: ' + resultados.length;

        if (!resultados.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-muted">No hay resultados para mostrar.</td></tr>';
          return;
        }

        resultados.forEach(function(r) {
          const tr = document.createElement('tr');
          const valor = (r.valor_hallado === null || r.valor_hallado === '') ? '-' : r.valor_hallado;
          tr.innerHTML = '<td>' + (r.id_muestra || '-') + '</td>' +
                         '<td>' + (r.servicio || '-') + '</td>' +
                         '<td>' + (r.parametro || '-') + '</td>' +
                         '<td>' + valor + '</td>';
          tbody.appendChild(tr);
        });
      }

      function cargarDetalleFirma() {
        if (!firmaContexto.idMuestra) {
          return;
        }

        const firmarTodos = document.getElementById('chk-firmar-todos').checked;
        fetch('modules/laboratorio/muestra/controllers/MuestraAPI.php?action=obtener_detalle_firma', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id_muestra: firmaContexto.idMuestra,
            firmar_todos: firmarTodos
          })
        })
          .then(function(resp) { return resp.json(); })
          .then(function(data) {
            if (!data.success) {
              mostrarErrorTabla(data.message || 'No se pudo cargar detalle de firma.');
              return;
            }
            renderDetalleFirma(data);
          })
          .catch(function() {
            mostrarErrorTabla('Error de red al cargar detalle de firma.');
          });
      }

      window.abrirModalFirmar = function(idMuestra, idCliente, agricultor) {
        firmaContexto.idMuestra = parseInt(idMuestra || 0, 10);
        firmaContexto.idCliente = parseInt(idCliente || 0, 10);
        firmaContexto.agricultor = agricultor || '';

        if (!firmaContexto.idMuestra) {
          mostrarErrorTabla('No se encontró la muestra seleccionada para firma.');
          return;
        }

        document.getElementById('firma-id-muestra').textContent = firmaContexto.idMuestra;
        document.getElementById('chk-firmar-todos').checked = true;
        document.getElementById('lbl-firmar-todos').textContent = 'Firmar todas las muestras del agricultor en estado Por Firmar';

        const modal = new bootstrap.Modal(document.getElementById('modal-firmar-muestra'));
        modal.show();
        cargarDetalleFirma();
      };

      document.getElementById('chk-firmar-todos').addEventListener('change', function() {
        cargarDetalleFirma();
      });

      document.getElementById('btn-confirmar-inicio-analisis').addEventListener('click', function() {
        const idMuestra = muestraInicioAnalisis.idMuestra;
        if (!idMuestra) {
          mostrarErrorTabla('No se encontró la muestra seleccionada.');
          return;
        }

        const iniciarTodos = document.getElementById('chk-iniciar-todos').checked;
        const payload = {
          id_muestra: idMuestra,
          iniciar_todos: iniciarTodos
        };

        fetch('modules/laboratorio/muestra/controllers/MuestraAPI.php?action=iniciar_analisis_agricultor', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
          .then(function(resp) { return resp.json(); })
          .then(function(data) {
            if (!data.success) {
              mostrarErrorTabla(data.message || 'No se pudo iniciar el análisis.');
              return;
            }

            const modalEl = document.getElementById('modal-iniciar-analisis');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
              modal.hide();
            }

            const agricultorNombre = encodeURIComponent(muestraInicioAnalisis.agricultor || '');
            window.location.href = '?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_cliente=' + data.id_cliente + '&id_muestra=' + idMuestra + '&agricultor=' + agricultorNombre;
          })
          .catch(function() {
            mostrarErrorTabla('Error de red al iniciar análisis.');
          });
      });

      document.getElementById('btn-confirmar-firma').addEventListener('click', function() {
        if (!firmaContexto.idMuestra) {
          mostrarErrorTabla('No se encontró la muestra para firmar.');
          return;
        }

        const firmarTodos = document.getElementById('chk-firmar-todos').checked;

        fetch('modules/laboratorio/muestra/controllers/MuestraAPI.php?action=firmar_muestra', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id_muestra: firmaContexto.idMuestra,
            firmar_todos: firmarTodos
          })
        })
          .then(function(resp) { return resp.json(); })
          .then(function(data) {
            if (!data.success) {
              mostrarErrorTabla(data.message || 'No se pudo registrar la firma.');
              return;
            }

            const modalEl = document.getElementById('modal-firmar-muestra');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
              modal.hide();
            }

            $('#tabla-por-firmar').DataTable().ajax.reload(null, false);
            $('#tabla-en-analisis').DataTable().ajax.reload(null, false);
          })
          .catch(function() {
            mostrarErrorTabla('Error de red al registrar la firma.');
          });
      });

      document.querySelectorAll('#tabs-tipo-servicio [data-tipo-servicio]').forEach(function(btn) {
        btn.addEventListener('click', function() {
          document.querySelectorAll('#tabs-tipo-servicio [data-tipo-servicio]').forEach(function(other) {
            other.classList.remove('active');
          });
          btn.classList.add('active');
          tipoServicioFiltroActual = (btn.getAttribute('data-tipo-servicio') || 'todos').toLowerCase();
          recargarTablasPorTipoServicio();
        });
      });

      document.querySelectorAll('input[name="ci_tipo_muestra"]').forEach(function(radio) {
        radio.addEventListener('change', toggleTipoMuestraCreacionIndividual);
      });

      document.getElementById('btn-guardar-individual-modal').addEventListener('click', guardarCreacionIndividual);

      const modalCrear = document.getElementById('modal-crear-muestra-comun');
      modalCrear.addEventListener('shown.bs.modal', function() {
        limpiarErrorCreacionIndividual();
        if (!document.getElementById('ci_fecha_toma').value) {
          document.getElementById('ci_fecha_toma').value = new Date().toISOString().split('T')[0];
        }
        toggleTipoMuestraCreacionIndividual();
        cargarCatalogosCreacionIndividual().catch(function(err) {
          mostrarErrorCreacionIndividual(err.message || 'No se pudieron cargar los catálogos.');
        });
      });
});
</script>
