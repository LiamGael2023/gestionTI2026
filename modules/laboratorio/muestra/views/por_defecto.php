<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
  .dataTables_wrapper .pagination .page-link { color: #1d273b; }
  .dataTables_wrapper .pagination .page-item.active .page-link {
    background-color: #1f9d55;
    border-color: #1f9d55;
    color: #fff;
  }
  .form-section {
    background: #f8faf8;
    border: 1px solid #d7e8d8;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 14px;
  }
  .summary-label {
    color: #607080;
    font-size: 12px;
    margin-bottom: 2px;
  }
  .summary-value {
    font-weight: 600;
    margin-bottom: 10px;
  }
  .nota-prototipo {
    background: #dff0df;
    border: 1px solid #c5e2c6;
    border-radius: 12px;
    padding: 16px 18px;
    color: #1d273b;
    margin-bottom: 18px;
  }
  .nota-prototipo p {
    margin-bottom: 0;
  }
  .nota-prototipo strong {
    font-weight: 600;
  }
  .seccion-guia {
    margin-bottom: 12px;
  }
  .recordatorio-seccion {
    background: #eef7ff;
    border: 1px dashed #b8d4f0;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 12px;
    color: #4a6786;
    margin-bottom: 12px;
  }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Por Defecto</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">MUESTRAS POR DEFECTO</h2>
        <div class="text-muted mt-1">Creacion de muestras originales para duplicados diarios en bitacora (manana/tarde)</div>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-crear-muestra">
          <i class="ti ti-plus me-2"></i> Crear Muestra Por Defecto
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div id="alerta-error" class="alert alert-danger d-none" role="alert"></div>

    <div class="nota-prototipo">
      <p>
        La creacion de una muestra por defecto genera una <strong>plantilla preconfigurada</strong>.
        Este registro permite agilizar la recepcion y duplicar la informacion tecnica en bitacora sin volver a ingresarla manualmente.
      </p>
    </div>

    <p class="text-muted small seccion-guia">Seleccione una muestra original para reutilizar su configuracion en los duplicados diarios.</p>

    <div class="card">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <a href="#tab-originales-defecto" class="nav-link active" data-bs-toggle="tab" role="tab" aria-selected="true">Originales</a>
          </li>
          <li class="nav-item" role="presentation">
            <a href="#tab-analisis-defecto" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">En Análisis</a>
          </li>
          <li class="nav-item" role="presentation">
            <a href="#tab-bitacoras-defecto" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">Bitácoras</a>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">
          <div class="tab-pane active show" id="tab-originales-defecto" role="tabpanel">
            <p class="text-muted small mb-3">Estas muestras incluyen activas e inactivas para permitir su reactivacion cuando sea necesario.</p>
            <div class="mb-3">
              <button type="button" class="btn btn-success" id="btn-realizar-analisis">
                <i class="ti ti-file-analytics me-1"></i> Realizar Analisis
              </button>
            </div>
            <div class="table-responsive">
              <table id="tabla-muestras-defecto" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th style="width: 36px;">
                      <input type="checkbox" class="form-check-input" id="chk-todos-muestras" title="Seleccionar todos">
                    </th>
                    <th>No</th>
                    <th>Ubicación del punto</th>
                    <th>Punto de toma</th>
                    <th>Coordenadas</th>
                    <th>Valle</th>
                    <th>Fecha Creacion</th>
                    <th>Tipo de Muestra</th>
                    <th>Turno</th>
                    <th>Estado</th>
                    <th>Accion</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <div class="tab-pane" id="tab-analisis-defecto" role="tabpanel">
            <p class="text-muted small mb-3">Muestras duplicadas con turno asignado y muestra original vinculada.</p>
            <div class="table-responsive">
              <table id="tabla-muestras-defecto-analisis" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Bitácora</th>
                    <th>ID Original</th>
                    <th>Ubicación del punto</th>
                    <th>Punto de toma</th>
                    <th>Coordenadas</th>
                    <th>Valle</th>
                    <th>Fecha Creacion</th>
                    <th>Tipo de Muestra</th>
                    <th>Turno</th>
                    <th>Accion</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <div class="tab-pane" id="tab-bitacoras-defecto" role="tabpanel">
            <p class="text-muted small mb-3">Visualiza por fecha las bitácoras de mañana/tarde, gestiona observaciones y exporta por rango.</p>

            <div class="row g-2 mb-3">
              <div class="col-md-3">
                <label class="form-label" for="filtro_fecha_desde_bitacora">Fecha desde</label>
                <input type="date" id="filtro_fecha_desde_bitacora" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="filtro_fecha_hasta_bitacora">Fecha hasta</label>
                <input type="date" id="filtro_fecha_hasta_bitacora" class="form-control">
              </div>
              <div class="col-md-6 d-flex align-items-end gap-2">
                <button type="button" class="btn btn-primary" id="btn-filtrar-bitacoras">
                  <i class="ti ti-filter me-1"></i> Filtrar
                </button>
                <button type="button" class="btn btn-success" id="btn-exportar-bitacoras-rango">
                  <i class="ti ti-file-spreadsheet me-1"></i> Exportar rango de fechas
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table id="tabla-bitacoras-defecto" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Mañana</th>
                    <th>Obs. Mañana</th>
                    <th>Tarde</th>
                    <th>Obs. Tarde</th>
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

<div class="modal modal-blur fade" id="modal-crear-muestra" tabindex="-1" role="dialog" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crear Muestra Original Por Defecto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-muestra-defecto">
          <input type="hidden" id="id_muestra_edicion" value="">

          <div class="form-section">
            <h4 class="mb-3">Datos generales</h4>
            <div class="recordatorio-seccion">
              Recordatorio: complete los datos base de la muestra para reutilizarla como plantilla de duplicacion.
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="id_cliente">Agricultor / Cliente</label>
                <div class="d-flex gap-1 align-items-center">
                  <select id="id_cliente" class="form-select">
                    <option value="">Sin agricultor (opcional)</option>
                  </select>
                  <button type="button" class="btn btn-outline-success btn-sm flex-shrink-0" onclick="abrirCrearClienteRapido()" title="Nuevo Cliente"><i class="ti ti-plus"></i></button>
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="valle">Valle <span class="text-danger">*</span></label>
                <select id="valle" class="form-select" required>
                  <option value="">Seleccione valle</option>
                </select>
                <input type="text" class="form-control mt-2" id="valle_otro" placeholder="Especificar valle" style="display:none;">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="fecha_registro">Fecha <span class="text-danger">*</span></label>
                <input id="fecha_registro" type="date" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="ubicacion_punto">Ubicación del punto de muestra <span class="text-danger">*</span></label>
                <input id="ubicacion_punto" type="text" class="form-control" placeholder="Ej: PTA del Campamento San José" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="punto_toma">Punto de toma de la muestra <span class="text-danger">*</span></label>
                <input id="punto_toma" type="text" class="form-control" placeholder="Ej: Poza clorada" required>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="eje_x">Coordenada X</label>
                <input id="eje_x" type="text" class="form-control" placeholder="x:">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="eje_y">Coordenada Y</label>
                <input id="eje_y" type="text" class="form-control" placeholder="y:">
              </div>

              <div class="col-md-6">
                <label class="form-label d-block">Turno</label>
                <div class="form-text mt-0">El turno se define cuando realice la duplicación desde el botón "Realizar Analisis".</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="observacion">Observacion</label>
                <input id="observacion" type="text" class="form-control" placeholder="Opcional">
              </div>
            </div>
          </div>

          <div class="form-section">
            <h4 class="mb-3">Tipo de muestra</h4>
            <div class="recordatorio-seccion">
              Recordatorio: seleccione Agua o Suelo para mostrar solo los campos tecnicos que correspondan.
            </div>
            <div class="mb-3">
              <label class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tipo_muestra" value="Agua" checked>
                <span class="form-check-label">Agua</span>
              </label>
              <label class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tipo_muestra" value="Suelo">
                <span class="form-check-label">Suelo</span>
              </label>
            </div>

            <div id="bloque-agua" class="row g-3">
              <div class="col-md-3">
                <label class="form-label" for="uso_agua">Uso de agua</label>
                <select id="uso_agua" class="form-select">
                  <option value="">Seleccionar</option>
                  <option value="Consumo Humano">Consumo Humano</option>
                  <option value="Riego">Riego</option>
                  <option value="Industrial">Industrial</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="fuente_agua">Tipo</label>
                <select id="fuente_agua" class="form-select">
                  <option value="">Seleccionar</option>
                  <option value="Subterráneo">Subterráneo</option>
                  <option value="Superficial">Superficial</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="nivel_agua">Fuente</label>
                <select id="nivel_agua" class="form-select" onchange="toggleNivelAguaOtro('nivel_agua','nivel_agua_otro')">
                  <option value="">Seleccionar</option>
                  <option value="Rio">Rio</option>
                  <option value="Pozo">Pozo</option>
                  <option value="Canal">Canal</option>
                  <option value="Reservorio">Reservorio</option>
                  <option value="Otros">Otros</option>
                </select>
                <input type="text" id="nivel_agua_otro" class="form-control mt-1" placeholder="Especificar fuente" style="display:none;">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="cantidad_agua">Cantidad</label>
                <input id="cantidad_agua" type="text" class="form-control" value="1 Litro">
              </div>
            </div>

            <div id="bloque-suelo" class="row g-3 d-none">
              <div class="col-md-4">
                <label class="form-label" for="fuente_riego">Fuente de riego</label>
                <input id="fuente_riego" type="text" class="form-control" placeholder="Ej: Canal principal">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="profundidad">Profundidad</label>
                <select id="profundidad" class="form-select">
                  <option value="">Seleccionar</option>
                  <option>30 CM</option>
                  <option>60 CM</option>
                  <option>90 CM</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label" for="numero_submuestras">Nro Submuestras</label>
                <input id="numero_submuestras" type="number" min="1" class="form-control" placeholder="0">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="cantidad_suelo">Cantidad</label>
                <input id="cantidad_suelo" type="text" class="form-control" value="1 Kg">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="cultivo_anterior">Cultivo Anterior</label>
                <input id="cultivo_anterior" type="text" class="form-control" placeholder="Opcional">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="cultivo_implementado">Cultivo Implementado</label>
                <input id="cultivo_implementado" type="text" class="form-control" placeholder="Opcional">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="cultivo_por_implementar">Cultivo Por Implementar</label>
                <input id="cultivo_por_implementar" type="text" class="form-control" placeholder="Opcional">
              </div>
            </div>
          </div>

          <div class="form-section mb-0">
            <h4 class="mb-3">Paquete de servicios</h4>
            <div class="recordatorio-seccion">
              Recordatorio: este producto/paquete se usara cuando se duplique la muestra por defecto.
            </div>
            <div class="row g-2 align-items-end">
              <div class="col-md-12">
                <label class="form-label" for="select-servicio">Producto / paquete para duplicación <span class="text-danger">*</span></label>
                <select id="select-servicio" class="form-select" required>
                  <option value="">Seleccione servicio</option>
                </select>
              </div>
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
      <div class="modal-header">
        <h5 class="modal-title">Confirmar guardado de muestra original</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="summary-label">Agricultor</div>
        <div class="summary-value" id="resumen-agricultor">-</div>

        <div class="row">
          <div class="col-6">
            <div class="summary-label">Valle</div>
            <div class="summary-value" id="resumen-valle">-</div>
          </div>
          <div class="col-6">
            <div class="summary-label">Turno de duplicación</div>
            <div class="summary-value" id="resumen-turno">Se define al duplicar</div>
          </div>
        </div>

        <div class="row">
          <div class="col-6">
            <div class="summary-label">Tipo de muestra</div>
            <div class="summary-value" id="resumen-tipo">-</div>
          </div>
          <div class="col-6">
            <div class="summary-label">Punto de muestra</div>
            <div class="summary-value" id="resumen-punto">-</div>
          </div>
        </div>

        <div class="summary-label">Producto / paquete de duplicación</div>
        <div class="summary-value" id="resumen-servicios">-</div>
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
      <div class="modal-header">
        <h5 class="modal-title">Generar Duplicados Por Turno</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="recordatorio-seccion">
          Se creará una bitácora con la fecha y turno elegidos, y se generarán duplicados de las muestras seleccionadas.
        </div>
        <div class="mb-3">
          <label class="form-label" for="fecha_duplicacion">Fecha de registro <span class="text-danger">*</span></label>
          <input type="date" id="fecha_duplicacion" class="form-control">
        </div>
        <div>
          <label class="form-label d-block">Turno <span class="text-danger">*</span></label>
          <label class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="turno_duplicacion" value="Mañana" checked>
            <span class="form-check-label">Mañana</span>
          </label>
          <label class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="turno_duplicacion" value="Tarde">
            <span class="form-check-label">Tarde</span>
          </label>
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
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="contenido-detalle-bitacora-fecha"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  (function () {
    const apiUrl = 'modules/laboratorio/muestra/controllers/MuestraAPI.php';
    const muestrasSeleccionadas = new Set();
    const infoMuestrasDuplicar = {};
    const modalCrearElement = document.getElementById('modal-crear-muestra');
    const modalDuplicarElement = document.getElementById('modal-duplicar-muestras');
    const modalDetalleFechaElement = document.getElementById('modal-detalle-bitacora-fecha');
    let tablaBitacoras = null;

    const mostrarError = function (mensaje) {
      const el = document.getElementById('alerta-error');
      el.textContent = mensaje;
      el.classList.remove('d-none');
    };

    const limpiarError = function () {
      const el = document.getElementById('alerta-error');
      el.textContent = '';
      el.classList.add('d-none');
    };

    const toggleTipoMuestra = function () {
      const tipo = document.querySelector('input[name="tipo_muestra"]:checked').value;
      document.getElementById('bloque-agua').classList.toggle('d-none', tipo !== 'Agua');
      document.getElementById('bloque-suelo').classList.toggle('d-none', tipo !== 'Suelo');
    };

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
        fetch('modules/laboratorio/proveedor/controllers/ClienteAPI.php?action=guardar', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(result.value)
        }).then(r => r.json()).then(resp => {
          if (resp.success) {
            const id = resp.id;
            const nombre = [result.value.Nombres, result.value.Apellido_Paterno, result.value.Apellido_Materno]
              .filter(Boolean).join(' ');
            const sel = document.getElementById('id_cliente');
            const opt = new Option(nombre, id, true, true);
            sel.appendChild(opt);
            Swal.fire({ title: 'Creado', text: nombre + ' registrado', icon: 'success', timer: 1200, showConfirmButton: false });
          } else {
            Swal.fire('Error', resp.message || 'No se pudo crear el cliente', 'error');
          }
        }).catch(err => Swal.fire('Error', err.message || 'Error al crear el cliente', 'error'));
      });
    }

    const cargarCatalogos = function () {
      fetch(apiUrl + '?action=obtener_catalogos_por_defecto', { method: 'POST' })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.message || 'No se pudieron cargar los catalogos.');
          }

          const selectAgricultor = document.getElementById('id_cliente');
          const selectValle = document.getElementById('valle');
          const selectServicio = document.getElementById('select-servicio');

          (data.agricultores || []).forEach(function (item) {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.nombre;
            selectAgricultor.appendChild(option);
          });

          (data.valles || []).forEach(function (valle) {
            const option = document.createElement('option');
            option.value = valle;
            option.textContent = valle;
            selectValle.appendChild(option);
          });
          const optOtro = document.createElement('option');
          optOtro.value = 'Otros';
          optOtro.textContent = 'Otros (Especificar)';
          selectValle.appendChild(optOtro);
          selectValle.addEventListener('change', function () {
            const otro = document.getElementById('valle_otro');
            if (selectValle.value === 'Otros') { otro.style.display = ''; otro.required = true; }
            else { otro.style.display = 'none'; otro.required = false; otro.value = ''; }
          });

          (data.servicios || []).forEach(function (item) {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.nombre;
            selectServicio.appendChild(option);
          });
        })
        .catch(function (err) {
          mostrarError(err.message || 'Error de red al cargar catalogos.');
        });
    };

    const obtenerPayloadFormulario = function () {
      const tipoMuestra = document.querySelector('input[name="tipo_muestra"]:checked').value;
      return {
        id_cliente: document.getElementById('id_cliente').value,
        valle: (document.getElementById('valle').value === 'Otros'
          ? document.getElementById('valle_otro').value.trim()
          : document.getElementById('valle').value),
        fecha_registro: document.getElementById('fecha_registro').value,
        ubicacion_punto: document.getElementById('ubicacion_punto').value.trim(),
        punto_toma: document.getElementById('punto_toma').value.trim(),
        eje_x: document.getElementById('eje_x').value.trim(),
        eje_y: document.getElementById('eje_y').value.trim(),
        turno: '',
        tipo_muestra: tipoMuestra,
        observacion: document.getElementById('observacion').value.trim(),
        id_producto_venta: document.getElementById('select-servicio').value,
        uso_agua: document.getElementById('uso_agua').value,
        fuente_agua: document.getElementById('fuente_agua').value,
        nivel_agua: (document.getElementById('nivel_agua').value === 'Otros'
          ? (document.getElementById('nivel_agua_otro').value.trim() || 'Otros')
          : document.getElementById('nivel_agua').value),
        cantidad_agua: document.getElementById('cantidad_agua').value.trim(),
        fuente_riego: document.getElementById('fuente_riego').value.trim(),
        profundidad: document.getElementById('profundidad').value.trim(),
        numero_submuestras: document.getElementById('numero_submuestras').value,
        cantidad_suelo: document.getElementById('cantidad_suelo').value.trim(),
        cultivo_anterior: document.getElementById('cultivo_anterior').value.trim(),
        cultivo_implementado: document.getElementById('cultivo_implementado').value.trim(),
        cultivo_por_implementar: document.getElementById('cultivo_por_implementar').value.trim()
      };
    };

    const validarFormulario = function (payload) {
      if (!payload.valle) {
        return 'Por favor complete todos los campos obligatorios';
      }
      if (!payload.fecha_registro) {
        return 'Por favor complete todos los campos obligatorios';
      }
      if (!payload.ubicacion_punto) {
        return 'Por favor complete todos los campos obligatorios';
      }
      if (!payload.punto_toma) {
        return 'Por favor complete todos los campos obligatorios';
      }
      if (!payload.id_producto_venta) {
        return 'Por favor complete todos los campos obligatorios';
      }
      return '';
    };

    const mostrarModalErrorValidacion = function (mensaje) {
      Swal.fire('Error', mensaje || 'Por favor complete todos los campos obligatorios', 'error');
    };

    const limpiarFormularioModal = function () {
      document.getElementById('form-muestra-defecto').reset();
      document.getElementById('id_muestra_edicion').value = '';
      document.getElementById('btn-abrir-confirmacion').textContent = 'Guardar';
      document.querySelector('#modal-crear-muestra .modal-title').textContent = 'Crear Muestra Original Por Defecto';
      toggleTipoMuestra();

      const hoy = new Date().toISOString().split('T')[0];
      document.getElementById('fecha_registro').value = hoy;
      document.getElementById('id_cliente').value = '';
      document.getElementById('select-servicio').value = '';
    };

    const abrirConfirmacion = function () {
      limpiarError();
      const payload = obtenerPayloadFormulario();
      const error = validarFormulario(payload);
      if (error) {
        mostrarModalErrorValidacion(error);
        return;
      }

      const agricultorSel = document.getElementById('id_cliente');
      const agricultorTexto = payload.id_cliente
        ? agricultorSel.options[agricultorSel.selectedIndex].text
        : 'Sin agricultor';
      document.getElementById('resumen-agricultor').textContent = agricultorTexto;
      document.getElementById('resumen-valle').textContent = payload.valle;
      document.getElementById('resumen-turno').textContent = 'Se define al duplicar';
      document.getElementById('resumen-tipo').textContent = payload.tipo_muestra;
      document.getElementById('resumen-ubicacion').textContent = payload.ubicacion_punto;
      document.getElementById('resumen-punto').textContent = payload.punto_toma;
      const servicioSel = document.getElementById('select-servicio');
      document.getElementById('resumen-servicios').textContent = servicioSel.options[servicioSel.selectedIndex].text;

      const modal = new bootstrap.Modal(document.getElementById('modal-confirmar-guardado'));
      modal.show();
    };

    const guardarMuestra = function () {
      limpiarError();
      const payload = obtenerPayloadFormulario();
      const idMuestraEdicion = parseInt(document.getElementById('id_muestra_edicion').value || '0', 10);
      const error = validarFormulario(payload);
      if (error) {
        mostrarModalErrorValidacion(error);
        return;
      }

      if (idMuestraEdicion > 0) {
        payload.id_muestra = idMuestraEdicion;
      }

      const btnGuardar = document.getElementById('btn-confirmar-guardado');
      btnGuardar.disabled = true;
      btnGuardar.textContent = 'Guardando...';

      const accionGuardar = idMuestraEdicion > 0 ? 'actualizar_muestra_por_defecto' : 'guardar_muestra_por_defecto';

      fetch(apiUrl + '?action=' + accionGuardar, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.message || 'No se pudo guardar la muestra.');
          }

          const modalConfirm = bootstrap.Modal.getInstance(document.getElementById('modal-confirmar-guardado'));
          if (modalConfirm) {
            modalConfirm.hide();
          }

          const modalCrear = bootstrap.Modal.getInstance(document.getElementById('modal-crear-muestra'));
          if (modalCrear) {
            modalCrear.hide();
          }

          limpiarFormularioModal();
          muestrasSeleccionadas.clear();
          document.getElementById('chk-todos-muestras').checked = false;
          document.getElementById('chk-todos-muestras').indeterminate = false;

          $('#tabla-muestras-defecto').DataTable().ajax.reload(null, false);

          Swal.fire('Exito', idMuestraEdicion > 0 ? 'Muestra por defecto actualizada correctamente' : 'Muestra por defecto creada correctamente', 'success');
        })
        .catch(function (err) {
          Swal.fire('Error', err.message || 'Error de red al guardar muestra por defecto.', 'error');
        })
        .finally(function () {
          btnGuardar.disabled = false;
          btnGuardar.textContent = 'Confirmar y Guardar';
        });
    };

    const actualizarCheckboxCabecera = function () {
      const checksVisibles = Array.from(document.querySelectorAll('#tabla-muestras-defecto .chk-muestra-select'));
      const cabecera = document.getElementById('chk-todos-muestras');
      if (!cabecera) {
        return;
      }
      if (checksVisibles.length === 0) {
        cabecera.checked = false;
        cabecera.indeterminate = false;
        return;
      }

      const marcados = checksVisibles.filter(function (el) { return el.checked; }).length;
      cabecera.checked = marcados === checksVisibles.length;
      cabecera.indeterminate = marcados > 0 && marcados < checksVisibles.length;
    };

    const renderListaMuestrasDuplicar = function () {
      const tbody = document.getElementById('lista-muestras-duplicar');
      tbody.innerHTML = '';
      const ids = Array.from(muestrasSeleccionadas).sort(function (a, b) { return parseInt(a, 10) - parseInt(b, 10); });
      ids.forEach(function (id) {
        const info = infoMuestrasDuplicar[id] || {};
        const tr = document.createElement('tr');
        tr.dataset.idOriginal = id;
        const punto = (info.punto_toma || '').trim() !== '' ? (info.punto_toma) : '-';
        tr.innerHTML = '<td>' + id + '</td>'
          + '<td>' + punto + '</td>'
          + '<td><input type="checkbox" class="form-check-input chk-no-analizada"></td>'
          + '<td><input type="text" class="form-control form-control-sm comentario-muestra" placeholder="Ej: en mantenimiento" style="min-width:200px;"></td>';
        tbody.appendChild(tr);
      });
    };

    const registrarInfoMuestra = function (checkboxEl) {
      const id = checkboxEl.value;
      const row = $('#tabla-muestras-defecto').DataTable().row(checkboxEl.closest('tr')).data();
      if (row) {
        infoMuestrasDuplicar[id] = {
          punto_toma: row.punto_toma || '',
          ubicacion: row.ubicacion_punto || ''
        };
      }
    };

    const manejarRealizarAnalisis = function () {
      if (muestrasSeleccionadas.size === 0) {
        Swal.fire('Error', 'Seleccione al menos una muestra por defecto de la lista.', 'error');
        return;
      }

      document.getElementById('fecha_duplicacion').value = new Date().toISOString().split('T')[0];
      renderListaMuestrasDuplicar();
      const modal = new bootstrap.Modal(modalDuplicarElement);
      modal.show();
    };

    const ejecutarDuplicacion = function () {
      const fecha = document.getElementById('fecha_duplicacion').value;
      const turnoSel = document.querySelector('input[name="turno_duplicacion"]:checked');
      const turno = turnoSel ? turnoSel.value : '';

      if (!fecha || !turno) {
        Swal.fire('Error', 'Por favor complete todos los campos obligatorios', 'error');
        return;
      }

      const idsSeleccionados = Array.from(muestrasSeleccionadas).map(function (id) { return parseInt(id, 10); });

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

      const btnDuplicar = document.getElementById('btn-confirmar-duplicacion');
      btnDuplicar.disabled = true;
      btnDuplicar.textContent = 'Procesando...';

      fetch(apiUrl + '?action=duplicar_muestras_por_defecto', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ids_muestras: idsSeleccionados,
          fecha_registro: fecha,
          turno: turno,
          observaciones: observaciones
        })
      })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.message || 'No se pudo ejecutar la duplicación.');
          }

          const modalDup = bootstrap.Modal.getInstance(modalDuplicarElement);
          if (modalDup) {
            modalDup.hide();
          }

          muestrasSeleccionadas.clear();
          document.getElementById('chk-todos-muestras').checked = false;
          document.getElementById('chk-todos-muestras').indeterminate = false;
          $('#tabla-muestras-defecto').DataTable().ajax.reload(null, false);
          $('#tabla-muestras-defecto-analisis').DataTable().ajax.reload(null, false);
          recargarTablaBitacoras();

          Swal.fire('Exito', 'Se crearon ' + (data.total || 0) + ' muestra(s) duplicada(s). Bitácora #' + (data.id_bitacora || 0), 'success')
            .then(function () {
              const idMuestra = parseInt(data.id_muestra_inicial || 0, 10);
              const idBitacora = parseInt(data.id_bitacora || 0, 10);
              if (idMuestra > 0) {
                window.location.href = '?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_muestra=' + idMuestra + '&id_bitacora=' + idBitacora + '&agricultor=' + encodeURIComponent('Muestra por defecto');
              }
            });
        })
        .catch(function (err) {
          Swal.fire('Error', err.message || 'Error al generar duplicados.', 'error');
        })
        .finally(function () {
          btnDuplicar.disabled = false;
          btnDuplicar.textContent = 'Crear Duplicados';
        });
    };

    const cargarMuestraParaEdicion = function (idMuestra) {
      fetch(apiUrl + '?action=obtener_muestra_por_defecto&id_muestra=' + encodeURIComponent(idMuestra), { method: 'GET' })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
          if (!data.success || !data.data) {
            throw new Error(data.message || 'No se pudo cargar la muestra por defecto.');
          }

          const d = data.data;
          document.getElementById('id_muestra_edicion').value = d.Id_Muestra || '';
          document.getElementById('id_cliente').value = d.Id_Cliente || '';
          document.getElementById('valle').value = d.Valle || '';
          // Si el valor cargado no está en el select (valle personalizado), usar opción Otros
          const sValle = document.getElementById('valle');
          const valleOtro = document.getElementById('valle_otro');
          if (d.Valle && sValle.value === '') {
            sValle.value = 'Otros';
            valleOtro.style.display = '';
            valleOtro.required = true;
            valleOtro.value = d.Valle;
          } else {
            valleOtro.style.display = 'none';
            valleOtro.required = false;
            valleOtro.value = '';
          }
          document.getElementById('fecha_registro').value = d.Fecha_Registro || '';
          document.getElementById('ubicacion_punto').value = d.Ubicacion_Punto || '';
          document.getElementById('punto_toma').value = d.Punto_Toma || '';
          document.getElementById('eje_x').value = d.Eje_X || '';
          document.getElementById('eje_y').value = d.Eje_Y || '';
          document.getElementById('observacion').value = d.Observacion_Muestra || '';
          document.getElementById('select-servicio').value = d.Id_Producto_Venta || '';

          const tipo = d.Tipo_Muestra || 'Agua';
          document.querySelectorAll('input[name="tipo_muestra"]').forEach(function (radio) {
            radio.checked = radio.value === tipo;
          });
          toggleTipoMuestra();

          document.getElementById('uso_agua').value = d.Uso_Agua || '';
          document.getElementById('fuente_agua').value = d.Fuente_Agua || '';
          const nivelStd = ['Rio','Pozo','Canal','Reservorio','Otros',''];
          const nivelVal = d.Nivel_Agua || '';
          if (nivelVal && !nivelStd.includes(nivelVal)) {
            document.getElementById('nivel_agua').value = 'Otros';
            document.getElementById('nivel_agua_otro').value = nivelVal;
            document.getElementById('nivel_agua_otro').style.display = 'block';
          } else {
            document.getElementById('nivel_agua').value = nivelVal;
            document.getElementById('nivel_agua_otro').style.display = 'none';
          }
          document.getElementById('cantidad_agua').value = d.Cantidad_Muestra_Agua || '1 Litro';

          document.getElementById('fuente_riego').value = d.Fuente_Riego || '';
          document.getElementById('profundidad').value = d.Profundidad || '';
          document.getElementById('numero_submuestras').value = d.Numero_Submuestras || '';
          document.getElementById('cantidad_suelo').value = d.Cantidad_Muestra_Suelo || '1 Kg';
          document.getElementById('cultivo_anterior').value = d.Cultivo_Anterior || '';
          document.getElementById('cultivo_implementado').value = d.Cultivo_Implementado || '';
          document.getElementById('cultivo_por_implementar').value = d.Cultivo_Por_Implementar || '';

          document.getElementById('btn-abrir-confirmacion').textContent = 'Actualizar';
          document.querySelector('#modal-crear-muestra .modal-title').textContent = 'Editar Muestra Por Defecto';

          const modal = new bootstrap.Modal(modalCrearElement);
          modal.show();
        })
        .catch(function (err) {
          Swal.fire('Error', err.message || 'No se pudo cargar la muestra por defecto.', 'error');
        });
    };

    const desactivarMuestra = function (idMuestra) {
      Swal.fire({
        title: '¿Desactivar muestra por defecto?',
        text: 'La muestra quedará inactiva y podrá reactivarse después.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Desactivar',
        cancelButtonText: 'Cancelar'
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }

        fetch(apiUrl + '?action=desactivar_muestra_por_defecto', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id_muestra: idMuestra })
        })
          .then(function (resp) { return resp.json(); })
          .then(function (data) {
            if (!data.success) {
              throw new Error(data.message || 'No se pudo desactivar la muestra.');
            }
            muestrasSeleccionadas.delete(String(idMuestra));
            $('#tabla-muestras-defecto').DataTable().ajax.reload(null, false);
            Swal.fire('Exito', 'Muestra por defecto desactivada correctamente', 'success');
          })
          .catch(function (err) {
            Swal.fire('Error', err.message || 'Error al desactivar la muestra.', 'error');
          });
      });
    };

    const reactivarMuestra = function (idMuestra) {
      Swal.fire({
        title: '¿Reactivar muestra por defecto?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reactivar',
        cancelButtonText: 'Cancelar'
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }

        fetch(apiUrl + '?action=reactivar_muestra_por_defecto', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id_muestra: idMuestra })
        })
          .then(function (resp) { return resp.json(); })
          .then(function (data) {
            if (!data.success) {
              throw new Error(data.message || 'No se pudo reactivar la muestra.');
            }
            $('#tabla-muestras-defecto').DataTable().ajax.reload(null, false);
            Swal.fire('Exito', 'Muestra por defecto reactivada correctamente', 'success');
          })
          .catch(function (err) {
            Swal.fire('Error', err.message || 'Error al reactivar la muestra.', 'error');
          });
      });
    };

    window.editarMuestraPorDefecto = cargarMuestraParaEdicion;
    window.desactivarMuestraPorDefecto = desactivarMuestra;
    window.reactivarMuestraPorDefecto = reactivarMuestra;

    const obtenerFiltrosBitacora = function () {
      return {
        fecha_desde: document.getElementById('filtro_fecha_desde_bitacora').value || '',
        fecha_hasta: document.getElementById('filtro_fecha_hasta_bitacora').value || ''
      };
    };

    const recargarTablaBitacoras = function () {
      if (tablaBitacoras) {
        tablaBitacoras.ajax.reload(null, false);
      }
    };

    const crearBitacoraTurno = function (fecha, turno) {
      Swal.fire({
        title: 'Crear bitácora ' + turno,
        html: '<div class="text-start"><div><strong>Fecha:</strong> ' + fecha + '</div><div class="mt-2">Opcional: observación inicial</div></div>',
        input: 'text',
        inputPlaceholder: 'Observación (opcional)',
        showCancelButton: true,
        confirmButtonText: 'Crear bitácora',
        cancelButtonText: 'Cancelar'
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }

        fetch(apiUrl + '?action=crear_bitacora_por_defecto_turno', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            fecha_registro: fecha,
            turno: turno,
            observacion: (result.value || '').trim()
          })
        })
          .then(function (resp) { return resp.json(); })
          .then(function (data) {
            if (!data.success) {
              throw new Error(data.message || 'No se pudo crear la bitácora.');
            }
            recargarTablaBitacoras();
            Swal.fire('Éxito', 'Bitácora creada correctamente.', 'success');
          })
          .catch(function (err) {
            Swal.fire('Error', err.message || 'Error al crear bitácora.', 'error');
          });
      });
    };

    const abrirObservacionBitacora = function (idBitacora, observacionActual, fecha, turno) {
      Swal.fire({
        title: 'Observación bitácora #' + idBitacora,
        html: '<div class="text-start mb-2"><strong>Fecha:</strong> ' + fecha + ' | <strong>Turno:</strong> ' + turno + '</div>',
        input: 'text',
        inputValue: observacionActual || '',
        inputPlaceholder: 'Ingrese observación',
        showCancelButton: true,
        confirmButtonText: 'Guardar observación',
        cancelButtonText: 'Cancelar'
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }

        fetch(apiUrl + '?action=actualizar_observacion_bitacora_por_defecto', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id_bitacora: idBitacora,
            observacion: (result.value || '').trim()
          })
        })
          .then(function (resp) { return resp.json(); })
          .then(function (data) {
            if (!data.success) {
              throw new Error(data.message || 'No se pudo actualizar la observación.');
            }
            recargarTablaBitacoras();
            Swal.fire('Éxito', 'Observación actualizada correctamente.', 'success');
          })
          .catch(function (err) {
            Swal.fire('Error', err.message || 'Error al actualizar observación.', 'error');
          });
      });
    };

    const escapeHtml = function (value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    };

    const renderTurnoDetalle = function (titulo, turnoData) {
      const idBitacora = parseInt(turnoData.id_bitacora || 0, 10);
      const totalMuestras = parseInt(turnoData.total_muestras || 0, 10);
      const observacion = (turnoData.observacion || '').trim();
      const tienePendientes = !!turnoData.tiene_pendientes;
      const resultados = Array.isArray(turnoData.resultados) ? turnoData.resultados : [];

      if (idBitacora <= 0) {
        return '' +
          '<div class="card h-100">' +
            '<div class="card-header"><h3 class="card-title mb-0">Turno ' + escapeHtml(titulo) + '</h3></div>' +
            '<div class="card-body">' +
              '<div class="alert alert-warning mb-0">No existe bitácora para este turno en la fecha seleccionada.</div>' +
            '</div>' +
          '</div>';
      }

      const btnContinuar = tienePendientes
        ? '<a class="btn btn-sm btn-primary" href="?module=laboratorio&action=muestra&subaction=analisis_agricultor&id_bitacora=' + idBitacora + '&agricultor=' + encodeURIComponent('Muestra por defecto') + '"><i class="ti ti-clipboard-data me-1"></i> Continuar análisis</a>'
        : '';

      if (totalMuestras === 0 && observacion !== '') {
        return '' +
          '<div class="card h-100">' +
            '<div class="card-header d-flex justify-content-between align-items-center">' +
              '<h3 class="card-title mb-0">Turno ' + escapeHtml(titulo) + ' <span class="text-muted">#' + idBitacora + '</span></h3>' +
              btnContinuar +
            '</div>' +
            '<div class="card-body">' +
              '<div class="mb-3"><strong>Muestras:</strong> 0</div>' +
              '<div class="alert alert-warning mb-0" style="font-size:1.05rem; line-height:1.5;">' +
                '<div class="fw-bold mb-2">No se realizó análisis en este turno</div>' +
                '<div>' + escapeHtml(observacion) + '</div>' +
              '</div>' +
            '</div>' +
          '</div>';
      }

      let filas = '';
            let ultimoIdMuestra = null;
            if (resultados.length === 0) {
              filas = '<tr><td colspan="8" class="text-center text-muted py-4">No hay resultados registrados para este turno.</td></tr>';
            } else {
              resultados.forEach(function (r) {
                const valor = (r.valor_hallado || '').trim();
                const esPrimeraFilaMuestra = String(r.id_muestra) !== String(ultimoIdMuestra);
                ultimoIdMuestra = r.id_muestra;
                const noAnalizada = parseInt(r.no_analizada || 0, 10) === 1;
                const obsMuestra = (r.observacion_muestra || '').trim();
                const estadoHtml = noAnalizada
                  ? '<span class="badge bg-danger">NO ANALIZADA</span>'
                  : escapeHtml(r.estado || '-');
                const obsHtml = (esPrimeraFilaMuestra && obsMuestra !== '')
                  ? escapeHtml(obsMuestra)
                  : '<span class="text-muted">-</span>';
                filas += '<tr>' +
                  '<td>' + escapeHtml(r.id_muestra) + '</td>' +
                  '<td>' + escapeHtml(r.ubicacion_punto || '-') + '</td>' +
                  '<td>' + escapeHtml(r.punto_toma || '-') + '</td>' +
                  '<td>' + escapeHtml(r.parametro || '-') + '</td>' +
                  '<td>' + escapeHtml(r.unidad || '') + '</td>' +
                  '<td>' + escapeHtml(valor !== '' ? valor : '(pendiente)') + '</td>' +
                  '<td>' + estadoHtml + '</td>' +
                  '<td>' + obsHtml + '</td>' +
                '</tr>';
              });
            }

      const obsInfo = observacion !== ''
        ? '<div class="mb-2"><strong>Observación:</strong> ' + escapeHtml(observacion) + '</div>'
        : '<div class="mb-2 text-muted"><strong>Observación:</strong> (sin observación)</div>';

      return '' +
        '<div class="card h-100">' +
          '<div class="card-header d-flex justify-content-between align-items-center">' +
            '<h3 class="card-title mb-0">Turno ' + escapeHtml(titulo) + ' <span class="text-muted">#' + idBitacora + '</span></h3>' +
            btnContinuar +
          '</div>' +
          '<div class="card-body">' +
            '<div class="mb-2"><strong>Muestras:</strong> ' + totalMuestras + '</div>' +
            obsInfo +
            '<div class="table-responsive">' +
              '<table class="table table-vcenter card-table table-striped">' +
                '<thead><tr><th>ID Muestra</th><th>Ubicación del punto</th><th>Punto de toma</th><th>Parámetro</th><th>Unidad</th><th>Valor hallado</th><th>Estado</th><th>Observación</th></tr></thead>' +
                '<tbody>' + filas + '</tbody>' +
              '</table>' +
            '</div>' +
          '</div>' +
        '</div>';
    };

    const abrirDetalleBitacoraFecha = function (fecha) {
      if (!fecha) {
        Swal.fire('Error', 'Fecha inválida para ver detalle.', 'error');
        return;
      }

      document.getElementById('titulo-detalle-bitacora-fecha').textContent = 'Detalle de bitácoras - ' + fecha;
      document.getElementById('contenido-detalle-bitacora-fecha').innerHTML = '<div class="text-center text-muted py-4">Cargando detalle...</div>';

      const modal = new bootstrap.Modal(modalDetalleFechaElement);
      modal.show();

      fetch(apiUrl + '?action=obtener_detalle_bitacora_por_fecha&fecha=' + encodeURIComponent(fecha), { method: 'GET' })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.message || 'No se pudo cargar el detalle de bitácora.');
          }

          const html = '' +
            '<div class="row g-3">' +
              '<div class="col-12 col-xl-6">' + renderTurnoDetalle('Mañana', data.manana || {}) + '</div>' +
              '<div class="col-12 col-xl-6">' + renderTurnoDetalle('Tarde', data.tarde || {}) + '</div>' +
            '</div>';

          document.getElementById('contenido-detalle-bitacora-fecha').innerHTML = html;
        })
        .catch(function (err) {
          document.getElementById('contenido-detalle-bitacora-fecha').innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(err.message || 'Error al cargar detalle.') + '</div>';
        });
    };

    window.crearBitacoraTurno = crearBitacoraTurno;
    window.abrirObservacionBitacora = abrirObservacionBitacora;
    window.abrirDetalleBitacoraFecha = abrirDetalleBitacoraFecha;

    $(document).ready(function () {
      const hoy = new Date().toISOString().split('T')[0];
      document.getElementById('fecha_registro').value = hoy;
      document.getElementById('filtro_fecha_hasta_bitacora').value = hoy;
      const inicioMes = new Date();
      inicioMes.setDate(1);
      document.getElementById('filtro_fecha_desde_bitacora').value = inicioMes.toISOString().split('T')[0];

      const tabla = $('#tabla-muestras-defecto').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: apiUrl + '?action=obtener_muestras_por_defecto',
          type: 'POST'
        },
        columns: [
          {
            data: null,
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
              if (type !== 'display') {
                return row.id;
              }
              const disabled = parseInt(row.activo || 1, 10) === 1 ? '' : ' disabled';
              return '<input type="checkbox" class="form-check-input chk-muestra-select" value="' + row.id + '"' + disabled + '>';
            }
          },
          { data: 'id' },
          { data: 'ubicacion_punto' },
          { data: 'punto_toma' },
          { data: 'coordenadas' },
          { data: 'valle' },
          { data: 'fecha_creacion' },
          { data: 'tipo_muestra' },
          { data: 'turno' },
          { data: 'estado', orderable: false, searchable: false },
          { data: 'accion', orderable: false, searchable: false }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
      });

      $('#tabla-muestras-defecto-analisis').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: apiUrl + '?action=obtener_muestras_por_defecto_en_analisis',
          type: 'POST'
        },
        columns: [
          { data: 'id' },
          {
            data: null,
            render: function (data, type, row) {
              const idBit = parseInt(row.id_bitacora || 0, 10);
              const fechaBit = row.fecha_bitacora || '-';
              if (idBit <= 0) {
                return '<span class="text-muted">Sin bitácora</span>';
              }
              return '<span class="badge bg-blue-lt me-1">#' + idBit + '</span><span class="text-muted">' + fechaBit + '</span>';
            }
          },
          { data: 'id_original' },
          { data: 'ubicacion_punto' },
          { data: 'punto_toma' },
          { data: 'coordenadas' },
          { data: 'valle' },
          { data: 'fecha_creacion' },
          { data: 'tipo_muestra' },
          { data: 'turno' },
          { data: 'accion', orderable: false, searchable: false }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
      });

      tablaBitacoras = $('#tabla-bitacoras-defecto').DataTable({
        processing: true,
        serverSide: false,
        searching: false,
        ajax: {
          url: apiUrl + '?action=obtener_resumen_bitacoras_por_defecto',
          type: 'POST',
          data: function (d) {
            const filtros = obtenerFiltrosBitacora();
            d.fecha_desde = filtros.fecha_desde;
            d.fecha_hasta = filtros.fecha_hasta;
          },
          dataSrc: function (json) {
            if (!json || !json.success) {
              Swal.fire('Error', (json && json.message) ? json.message : 'No se pudo cargar bitácoras.', 'error');
              return [];
            }
            return json.data || [];
          }
        },
        columns: [
          { data: 'fecha' },
          { data: 'manana', orderable: false, searchable: false },
          { data: 'observacion_manana', orderable: false, searchable: false },
          { data: 'tarde', orderable: false, searchable: false },
          { data: 'observacion_tarde', orderable: false, searchable: false },
          { data: 'accion', orderable: false, searchable: false }
        ],
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } }
      });

      $('#tabla-muestras-defecto').on('draw.dt', function () {
        document.querySelectorAll('#tabla-muestras-defecto .chk-muestra-select').forEach(function (check) {
          if (check.disabled) {
            check.checked = false;
            return;
          }
          check.checked = muestrasSeleccionadas.has(check.value);
        });
        actualizarCheckboxCabecera();
      });

      document.getElementById('tabla-muestras-defecto').addEventListener('change', function (event) {
        if (!event.target.classList.contains('chk-muestra-select')) {
          return;
        }

        const id = event.target.value;
        if (event.target.checked) {
          muestrasSeleccionadas.add(id);
          registrarInfoMuestra(event.target);
        } else {
          muestrasSeleccionadas.delete(id);
          delete infoMuestrasDuplicar[id];
        }
        actualizarCheckboxCabecera();
      });

      document.getElementById('chk-todos-muestras').addEventListener('change', function (event) {
        const marcar = event.target.checked;
        document.querySelectorAll('#tabla-muestras-defecto .chk-muestra-select').forEach(function (check) {
          if (check.disabled) {
            return;
          }
          check.checked = marcar;
          if (marcar) {
            muestrasSeleccionadas.add(check.value);
            registrarInfoMuestra(check);
          } else {
            muestrasSeleccionadas.delete(check.value);
            delete infoMuestrasDuplicar[check.value];
          }
        });
        actualizarCheckboxCabecera();
      });

      cargarCatalogos();
      toggleTipoMuestra();

      document.querySelectorAll('input[name="tipo_muestra"]').forEach(function (radio) {
        radio.addEventListener('change', toggleTipoMuestra);
      });

      // Mover modales a <body> para evitar conflictos de stacking context con page-wrapper de Tabler
      document.querySelectorAll('.modal').forEach(function (modal) {
        document.body.appendChild(modal);
      });

      // Handler explícito para abrir el modal de creación
      document.querySelectorAll('[data-bs-target="#modal-crear-muestra"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          bootstrap.Modal.getOrCreateInstance(modalCrearElement).show();
        });
      });

      document.getElementById('btn-abrir-confirmacion').addEventListener('click', abrirConfirmacion);
      document.getElementById('btn-confirmar-guardado').addEventListener('click', guardarMuestra);
      document.getElementById('btn-realizar-analisis').addEventListener('click', manejarRealizarAnalisis);
      document.getElementById('btn-confirmar-duplicacion').addEventListener('click', ejecutarDuplicacion);
      document.getElementById('btn-filtrar-bitacoras').addEventListener('click', function () {
        recargarTablaBitacoras();
      });
      document.getElementById('btn-exportar-bitacoras-rango').addEventListener('click', function () {
        const filtros = obtenerFiltrosBitacora();
        if (!filtros.fecha_desde || !filtros.fecha_hasta) {
          Swal.fire('Error', 'Seleccione fecha desde y fecha hasta para exportar.', 'error');
          return;
        }
        window.location.href = 'modules/laboratorio/muestra/controllers/ExportarBitacorasPorDefecto.php?fecha_desde=' + encodeURIComponent(filtros.fecha_desde) + '&fecha_hasta=' + encodeURIComponent(filtros.fecha_hasta);
      });

      modalCrearElement.addEventListener('hidden.bs.modal', function () {
        limpiarFormularioModal();
      });

      modalDuplicarElement.addEventListener('hidden.bs.modal', function () {
        document.getElementById('fecha_duplicacion').value = '';
        document.getElementById('lista-muestras-duplicar').innerHTML = '';
      });
    });
  })();
</script>
