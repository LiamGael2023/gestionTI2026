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
    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.75em;
    }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Creación Masiva</li>
      </ol>
    </nav>
    
    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">CREACIÓN MASIVA DE LABORATORIO</h2>
        <div class="text-muted mt-1">Esta herramienta facilita el registro rápido de muestras para monitoreos extensos. Permite generar múltiples registros de una sola vez</div>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-auto">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-nuevo-periodo">
          <i class="ti ti-plus me-2"></i> Crear Período
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info" role="alert">
      <div>
        Para la creación de las muestras (hasta +50 registros simultáneos), solo es necesario ingresar el <strong>Valle</strong> correspondiente y el 
        nombre del <strong>Agricultor</strong>. No se requieren coordenadas ni datos técnicos adicionales en este paso, ya que el sistema habilitará un
        <strong>período único</strong> donde se ingresarán posteriormente todos los resultados analíticos de forma centralizada
      </div>
    </div>

    <div class="alert alert-warning" role="alert">
      <strong>TODAS LAS MUESTRAS SON DE AGUA</strong>
    </div>

    <!-- Lista de Períodos -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Lista de Períodos</h3>
      </div>
      <div class="card-body">
        <p class="text-muted small">En esta tabla podrá visualizar los períodos creados para cada valle y acceder a la carga masiva de resultados analíticos una vez que las
muestras hayan sido procesadas</p>

        <ul class="nav nav-tabs mb-3" id="tabs-periodos" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-monitoreo" data-bs-toggle="tab" data-bs-target="#panel-monitoreo" type="button" role="tab" aria-controls="panel-monitoreo" aria-selected="true">
              Monitoreo
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-calidad-agua" data-bs-toggle="tab" data-bs-target="#panel-calidad-agua" type="button" role="tab" aria-controls="panel-calidad-agua" aria-selected="false">
              Calidad de Agua
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="panel-monitoreo" role="tabpanel" aria-labelledby="tab-monitoreo">
            <div class="table-responsive">
              <table id="tabla-periodos-monitoreo" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nombre Proyecto</th>
                    <th>Valle</th>
                    <th>Temporada</th>
                    <th>Fecha de Inicio</th>
                    <th>Estado</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="panel-calidad-agua" role="tabpanel" aria-labelledby="tab-calidad-agua">
            <div class="table-responsive">
              <table id="tabla-periodos-calidad" class="table table-vcenter card-table table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nombre Proyecto</th>
                    <th>Valle</th>
                    <th>Temporada</th>
                    <th>Fecha de Inicio</th>
                    <th>Estado</th>
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

  </div>
</div>

<!-- Modal: Nuevo Período -->
<div class="modal modal-blur fade" id="modal-nuevo-periodo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Período de Monitoreo</h5>
      </div>
      
      <div class="modal-body">
        <form id="form-nuevo-periodo">
          
          <div class="mb-3">
            <label class="form-label">Nombre del Proyecto <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nombre-proyecto" placeholder="Nombre del Proyecto" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Valle <span class="text-danger">*</span></label>
              <select class="form-select" id="select-valle" required>
                <option value="">Seleccionar valle...</option>
                <option value="Virú">Virú</option>
                <option value="Moche">Moche</option>
                <option value="Chicama">Chicama</option>
                <option value="Chao">Chao</option>
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
                <option value="">Seleccionar tipo...</option>
                <option value="Agua" selected>Agua</option>
                <option value="Suelo">Suelo</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="check-control-calidad">
              <span class="form-check-label">Proyecto de calidad de agua</span>
            </label>
            <small id="info-control-calidad" class="text-muted d-block mt-1">Si se activa, el sistema exige al menos 10 muestras planificadas por servicio y permitirá definir fuentes de agua por muestra al iniciar análisis.</small>
          </div>

          <!-- Campos para AGUA -->
          <div id="campos-agua">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Uso de Agua <span class="text-danger">*</span></label>
                <select class="form-select" id="select-uso-agua">
                  <option value="">Seleccionar...</option>
                  <option value="Riego">Riego</option>
                  <option value="Consumo humano">Consumo humano</option>
                  <option value="Animal">Animal</option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Fuente de Agua <span class="text-danger">*</span></label>
                <select class="form-select" id="select-fuente">
                  <option value="">Seleccionar...</option>
                  <option value="Río">Río</option>
                  <option value="Pozo">Pozo</option>
                  <option value="Otros">Otros (Especificar)</option>
                </select>
                <input type="text" class="form-control mt-2" id="fuente-otra" placeholder="Especificar fuente" style="display:none;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Nivel del Agua <span class="text-danger">*</span></label>
              <select class="form-select" id="select-nivel-agua">
                <option value="">Seleccionar...</option>
                <option value="Subterraneo">Subterráneo</option>
                <option value="Superficial">Superficial</option>
              </select>
            </div>
          </div>

          <!-- Agregar Servicios/Productos -->
          <div class="mb-3">
            <label class="form-label">Productos/Servicios de Monitoreo <span class="text-danger">*</span></label>
            <select class="form-control" id="select-servicios">
              <option value="">Seleccionar servicio...</option>
            </select>
            <small class="text-muted d-block mt-2">Especifique la cantidad de muestras planificadas para cada servicio</small>
            <div class="table-responsive mt-2">
              <table class="table table-sm table-bordered">
                <thead class="bg-light">
                  <tr>
                    <th style="width:60%;">Servicio</th>
                    <th style="width:25%;" class="text-center">Cantidad Planificada</th>
                    <th style="width:15%;" class="text-center">Acción</th>
                  </tr>
                </thead>
                <tbody id="tabla-servicios-tbody">
                </tbody>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tablaPerodosMonitoreo;
var tablaPerodosCalidad;
var serviciosDisponibles = [];

$(document).ready(function() {
    // Cargar servicios disponibles
    cargarServicios();
    
    // Tablas de períodos por tipo
    tablaPerodosMonitoreo = configurarTablaPeriodos('#tabla-periodos-monitoreo', 0);
    tablaPerodosCalidad = configurarTablaPeriodos('#tabla-periodos-calidad', 1);

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
      if (tablaPerodosMonitoreo) {
        tablaPerodosMonitoreo.columns.adjust();
      }
      if (tablaPerodosCalidad) {
        tablaPerodosCalidad.columns.adjust();
      }
    });

    // Eventos del modal
    $('#select-servicios').change(agregarServicio);
    $('#btn-crear-periodo').click(guardarPeriodo);
    
    $('#tipo-muestra').change(mostrarCamposSegunTipo);
    $('#select-valle').change(mostrarValleOtro);
    $('#select-fuente').change(mostrarFuenteOtra);
    $('#check-control-calidad').change(actualizarReglasControlCalidad);

    // Limpiar modal al cerrar
    $('#modal-nuevo-periodo').on('hidden.bs.modal', function() {
        limpiarFormulario();
    });
});

function configurarTablaPeriodos(selectorTabla, esControlCalidad) {
    return $(selectorTabla).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/muestra/views/data_periodos.php',
            type: 'POST',
            data: function(d) {
                d.es_control_calidad = esControlCalidad;
            }
        },
        columnDefs: [
            { orderable: false, targets: [6] }
        ],
        columns: [
            { data: 0, title: 'No' },
            { data: 1, title: 'Nombre Proyecto' },
            { data: 2, title: 'Valle' },
            { data: 3, title: 'Temporada' },
            { data: 4, title: 'Fecha de Inicio' },
            { data: 5, title: 'Estado' },
            { data: 6, title: 'Acción', orderable: false, searchable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
}

function recargarTablasPeriodos(resetPaginacion) {
    const reset = !!resetPaginacion;
    if (tablaPerodosMonitoreo) {
      tablaPerodosMonitoreo.ajax.reload(null, reset);
    }
    if (tablaPerodosCalidad) {
      tablaPerodosCalidad.ajax.reload(null, reset);
    }
}

function cargarServicios() {
    $.ajax({
        url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php?action=obtenerServicios',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                serviciosDisponibles = response.servicios || [];
                let options = '<option value="">Seleccionar servicio...</option>';
                serviciosDisponibles.forEach(function(servicio) {
                    options += '<option value="' + servicio.id + '">' + servicio.nombre + '</option>';
                });
                $('#select-servicios').html(options);
            }
        },
        error: function(err) {
            console.error('Error al cargar servicios:', err);
        }
    });
}

function mostrarCamposSegunTipo() {
    let tipo = $('#tipo-muestra').val();
    if (tipo === 'Agua') {
        $('#campos-agua').show();
    } else {
        $('#campos-agua').hide();
    }
}

function mostrarValleOtro() {
    let valle = $('#select-valle').val();
    if (valle === 'Otros') {
        $('#valle-otro').show().prop('required', true);
    } else {
        $('#valle-otro').hide().prop('required', false);
    }
}

function mostrarFuenteOtra() {
    let fuente = $('#select-fuente').val();
    if (fuente === 'Otros') {
        $('#fuente-otra').show().prop('required', true);
    } else {
        $('#fuente-otra').hide().prop('required', false);
    }
}

function agregarServicio() {
    let id = $('#select-servicios').val();
    let nombre = $('#select-servicios').find('option:selected').text();
    
    if (!id || id === '') {
        return; // No hacer nada si no hay selección
    }
    
    // Verificar si ya existe
    let existe = false;
    $('#tabla-servicios-tbody tr').each(function() {
        if ($(this).find('input[data-id]').data('id') == id) {
            existe = true;
            return false;
        }
    });
    
    if (existe) {
        Swal.fire('Advertencia', 'Este servicio ya ha sido agregado', 'warning');
        return;
    }
    
    // Agregar fila con input para cantidad
    const esControlCalidad = $('#check-control-calidad').is(':checked');
    const minCantidad = esControlCalidad ? 10 : 1;
    const cantidadInicial = 10;

    const fila = `<tr>
        <td>${nombre}</td>
      <td class="text-center"><input type="number" class="form-control form-control-sm" value="${cantidadInicial}" min="${minCantidad}" data-id="${id}" style="width:100%;"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove()"><i class="ti ti-trash"></i></button></td>
    </tr>`;
    
    $('#tabla-servicios-tbody').append(fila);
    $('#select-servicios').val('');  // Resetear select
}

function guardarPeriodo() {
    let nombre = $('#nombre-proyecto').val().trim();
    let valle = $('#select-valle').val();
    let fecha = $('#fecha-inicio').val();
    let temporada = $('#temporada').val().trim();
    let tipoMuestra = $('#tipo-muestra').val();
    let esControlCalidad = $('#check-control-calidad').is(':checked') ? 1 : 0;

    // Obtener valle custom si es "Otros"
    if (valle === 'Otros') {
        let valleOtro = $('#valle-otro').val().trim();
        if (!valleOtro) {
            Swal.fire('Error', 'Especifique el nombre del valle', 'error');
            return;
        }
        valle = valleOtro;
    }

    // Validar campos obligatorios
    if (!nombre || !valle || !fecha || !temporada || !tipoMuestra) {
        Swal.fire('Error', 'Por favor complete todos los campos obligatorios', 'error');
        return;
    }

    // Validar servicios agregados
    let servicios = [];
    $('#tabla-servicios-tbody tr').each(function() {
        let id = $(this).find('input[data-id]').data('id');
      let cantidad = parseInt($(this).find('input[data-id]').val(), 10) || 0;
      if (esControlCalidad && cantidad < 10) {
        cantidad = 10;
        $(this).find('input[data-id]').val('10');
      }
        servicios.push({
            id: id,
            cantidad: cantidad
        });
    });

    if (servicios.length === 0) {
        Swal.fire('Error', 'Agregue al menos un servicio', 'error');
        return;
    }

    // Recopilar datos
    let datos = {
        action: 'guardarProyecto',
        nombre_proyecto: nombre,
        valle: valle,
        fecha_inicio: fecha,
        temporada: temporada,
        tipo_muestra: tipoMuestra,
        es_control_calidad: esControlCalidad,
        servicios: servicios
    };

    if (tipoMuestra === 'Agua') {
        let uso = $('#select-uso-agua').val();
        let fuente = $('#select-fuente').val();
        let nivel = $('#select-nivel-agua').val();

        if (fuente === 'Otros') {
            fuente = $('#fuente-otra').val().trim();
            if (!fuente) {
                Swal.fire('Error', 'Especifique la fuente de agua', 'error');
                return;
            }
        }

        if (!uso || !fuente || !nivel) {
            Swal.fire('Error', 'Complete todos los datos de agua', 'error');
            return;
        }

        datos.uso_agua = uso;
        datos.fuente_agua = fuente;
        datos.nivel_agua = nivel;
    }

    // Guardar proyecto
    $.ajax({
        url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php',
        type: 'POST',
        data: JSON.stringify(datos),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('¡Éxito!', response.mensaje || 'Proyecto creado correctamente', 'success');
                $('#modal-nuevo-periodo').modal('hide');
              recargarTablasPeriodos(true);
            } else {
                Swal.fire('Error', response.error || 'Error desconocido', 'error');
            }
        },
        error: function(err) {
            let errorMsg = 'Error al guardar el período';
            if (err.responseJSON && err.responseJSON.error) {
                errorMsg = err.responseJSON.error;
            }
            Swal.fire('Error', errorMsg, 'error');
            console.error('Error AJAX:', err);
        }
    });
}

function limpiarFormulario() {
    $('#form-nuevo-periodo')[0].reset();
    $('#tabla-servicios-tbody').html('');
    $('#tipo-muestra').val('Agua').trigger('change');
    $('#select-valle').val('').trigger('change');
    $('#select-fuente').val('').trigger('change');
    $('#check-control-calidad').prop('checked', false).trigger('change');
}

  function esProyectoControlCalidad(valor) {
    return String(valor) === '1' || valor === 1 || valor === true || String(valor).toLowerCase() === 'true';
  }

  function obtenerFuentesControlCalidadDefault(totalMuestras) {
    const fuentesBase = [
      'RIO TABLACHACA',
      'RIO SANTA',
      'ENTRADA DESARENADOR',
      'SALIDA DESARENADOR',
      'CANAL EVACUADOR',
      'RIO VIRU',
      'RIO MOCHE',
      'RIO CHICAMA',
      'CANAL MADRE',
      'CENTRAL HIDROELECTRICA VIRU SAN JOSE'
    ];

    const fuentes = [];
    const total = Math.max(0, parseInt(totalMuestras, 10) || 0);
    for (let i = 0; i < total; i++) {
      fuentes.push(fuentesBase[i % fuentesBase.length]);
    }

    return fuentes;
  }

  function actualizarReglasControlCalidad() {
    const esControlCalidad = $('#check-control-calidad').is(':checked');
    const minCantidad = esControlCalidad ? 10 : 1;

    $('#tabla-servicios-tbody input[data-id]').each(function() {
      $(this).attr('min', minCantidad);
      const valorActual = parseInt($(this).val(), 10) || 0;
      if (esControlCalidad && valorActual < 10) {
        $(this).val('10');
      }
    });

    if (esControlCalidad) {
      $('#info-control-calidad').text('Calidad de agua activo: mínimo 10 muestras por servicio. Antes de generar, podrás editar la fuente de agua de cada muestra en un modal.');
    } else {
      $('#info-control-calidad').text('Si se activa, el sistema exige al menos 10 muestras planificadas por servicio y permitirá definir fuentes de agua por muestra al iniciar análisis.');
    }
  }

function verDetalles(id) {
    $.ajax({
        url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php?action=obtenerDetalles&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let detalles = response.detalles || [];
            let mensaje = '<h5>' + response.proyecto.Nombre_Proyecto + '</h5>';
            mensaje += '<p><strong>Valle:</strong> ' + response.proyecto.Valle + '</p>';
            mensaje += '<p><strong>Temporada:</strong> ' + response.proyecto.Temporada + '</p>';
            mensaje += '<p><strong>Estado:</strong> ' + response.proyecto.Estado + '</p>';
            mensaje += '<h6 class="mt-3">Servicios Planificados:</h6><ul>';
            
            detalles.forEach(function(det) {
                mensaje += '<li>' + det.Nombre_Producto + ': <strong>' + det.Cantidad_Planificada + ' muestras</strong></li>';
            });
            
            mensaje += '</ul>';
            
            Swal.fire('Detalles del Proyecto', mensaje, 'info');
        },
        error: function(err) {
            Swal.fire('Error', 'No se pudieron cargar los detalles', 'error');
        }
    });
}

function iniciarEjecucion(id) {
    // Obtener detalles del proyecto para mostrar cantidad de muestras
    $.ajax({
        url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php?action=obtenerDetalles&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let detalles = response.detalles || [];
            let totalMuestras = 0;
      const esControlCalidad = esProyectoControlCalidad(response.proyecto && response.proyecto.Es_Control_Calidad);
            let tablaHtml = '<table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Servicio</th><th style="text-align:center;">Cantidad Muestras</th></tr></thead><tbody>';
            
            detalles.forEach(function(det) {
                let cantidad = parseInt(det.Cantidad_Planificada) || 0;
                totalMuestras += cantidad;
                tablaHtml += '<tr><td>' + det.Nombre_Producto + '</td><td style="text-align:center;"><span class="badge bg-info">' + cantidad + '</span></td></tr>';
            });
            
            tablaHtml += '</tbody></table>';

      let bloqueFuentes = '';
      if (esControlCalidad && totalMuestras > 0) {
        const fuentesDefault = obtenerFuentesControlCalidadDefault(totalMuestras);
        let filasFuentes = '';

        for (let i = 0; i < totalMuestras; i++) {
          const nro = i + 1;
          filasFuentes += '<tr>' +
            '<td style="width:90px;" class="text-center"><span class="badge bg-secondary">M' + nro + '</span></td>' +
            '<td><input type="text" class="form-control form-control-sm fuente-calidad-input" data-index="' + i + '" value="' + escapeHtml(fuentesDefault[i]) + '"></td>' +
          '</tr>';
        }

        bloqueFuentes =
          '<hr>' +
          '<div class="alert alert-info py-2 px-3">Proyecto de calidad de agua: se cargaron fuentes por defecto para cada muestra. Puedes editarlas antes de crear.</div>' +
          '<div class="table-responsive" style="max-height:280px; overflow:auto;">' +
          '<table class="table table-sm table-bordered mb-0">' +
            '<thead class="table-light"><tr><th style="width:90px;" class="text-center">Muestra</th><th>Fuente de Agua</th></tr></thead>' +
            '<tbody>' + filasFuentes + '</tbody>' +
          '</table>' +
          '</div>';
      }
            
            // Mostrar modal con detalles
            Swal.fire({
                title: 'Iniciar Ejecución',
                html: '<div style="text-align:left;">' +
                      '<h6><strong>' + response.proyecto.Nombre_Proyecto + '</strong></h6>' +
                      '<p><strong>Valle:</strong> ' + response.proyecto.Valle + '</p>' +
                      '<p><strong>Temporada:</strong> ' + response.proyecto.Temporada + '</p>' +
                      '<hr>' +
                      '<h6>Se crearán <strong style="color:#d32f2f;">' + totalMuestras + ' muestras</strong> en total:</h6>' +
                      tablaHtml +
                        bloqueFuentes +
                      '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear muestras',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                      const dataPost = {
                        action: 'generarMuestras',
                        id_proyecto: id
                      };

                      if (esControlCalidad) {
                        const fuentes = [];
                        $('.swal2-container .fuente-calidad-input').each(function() {
                          fuentes.push($(this).val().trim());
                        });

                        const vacias = fuentes.some(function(f) { return !f; });
                        if (vacias) {
                          Swal.fire('Error', 'Todas las fuentes de agua deben tener un valor.', 'error');
                          return;
                        }

                        dataPost.fuentes_calidad = fuentes;
                      }

          Swal.fire({
            title: 'Creando muestras...',
            html: 'El sistema está generando las muestras. Por favor espere.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

                    // Proceder a crear las muestras
                    $.ajax({
                        url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php',
                        type: 'POST',
                        data: dataPost,
                        dataType: 'json',
                        success: function(response) {
                          Swal.close();
                            if (response.success) {
                                let msg = response.mensaje || 'Muestras creadas correctamente';
                                if (response.detalles_encontrados) {
                                    msg += ` (${response.detalles_encontrados} detalles procesados)`;
                                }
                                Swal.fire('¡Éxito!', msg, 'success');
                                recargarTablasPeriodos(true);
                            } else {
                                Swal.fire('Error', response.error || 'Error desconocido', 'error');
                            }
                        },
                        error: function(err) {
                            Swal.close();
                            let errorMsg = 'Error al iniciar ejecución';
                            if (err.responseJSON && err.responseJSON.error) {
                                errorMsg = err.responseJSON.error;
                            }
                            Swal.fire('Error', errorMsg, 'error');
                            console.error('Error AJAX:', err);
                        }
                    });
                }
            });
        },
        error: function(err) {
            Swal.fire('Error', 'No se pudieron cargar los detalles del proyecto', 'error');
        }
    });
}

function verMuestrasProy(id) {
    Swal.fire({
        title: 'Muestras del Proyecto',
        html: '<p>Funcionalidad en desarrollo...</p>',
        icon: 'info'
    });
}

function escapeHtml(text) {
  return String(text || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function normalizarFechaInput(fechaRaw) {
  if (!fechaRaw) return '';

  if (typeof fechaRaw === 'string') {
    const txt = fechaRaw.trim();
    if (/^\d{4}-\d{2}-\d{2}/.test(txt)) {
      return txt.substring(0, 10);
    }
    if (/^\d{2}-\d{2}-\d{4}$/.test(txt)) {
      const partes = txt.split('-');
      return partes[2] + '-' + partes[1] + '-' + partes[0];
    }
    return '';
  }

  if (typeof fechaRaw === 'object' && fechaRaw.date) {
    return String(fechaRaw.date).substring(0, 10);
  }

  return '';
}

function editarProyecto(id) {
  $.ajax({
    url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php?action=obtenerDetalles&id=' + id,
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      const proyecto = response.proyecto || {};
      const detalles = response.detalles || [];
      const puedeEditarCantidades = !!response.puede_editar_cantidades;
      const esControlCalidad = esProyectoControlCalidad(proyecto.Es_Control_Calidad);
      const fechaInicio = normalizarFechaInput(proyecto.Fecha_Inicio);
      const idsActuales = {};

      let filasServicios = '';
      detalles.forEach(function(det) {
        const idProducto = parseInt(det.Id_Producto_Venta, 10) || 0;
        const cantidad = parseInt(det.Cantidad_Planificada, 10) || 0;
        if (idProducto > 0) {
          idsActuales[idProducto] = true;
        }
        filasServicios += '<tr>' +
          '<td class="edit-nombre-servicio">' + escapeHtml(det.Nombre_Producto) + '</td>' +
          '<td class="text-center">' +
            '<input type="number" class="form-control form-control-sm edit-cantidad" min="' + (esControlCalidad ? 10 : 1) + '" value="' + cantidad + '" data-id="' + idProducto + '" ' + (puedeEditarCantidades ? '' : 'disabled') + '>' +
          '</td>' +
          '<td class="text-center">' +
            (puedeEditarCantidades ? '<button type="button" class="btn btn-sm btn-danger edit-remove-servicio"><i class="ti ti-trash"></i></button>' : '<span class="text-muted">-</span>') +
          '</td>' +
          '</tr>';
      });

      if (filasServicios === '') {
        filasServicios = '<tr class="edit-sin-servicios"><td colspan="3" class="text-center text-muted">No hay ventas/servicios configurados</td></tr>';
      }

      let opcionesPaquetes = '<option value="">Seleccionar paquete/venta...</option>';
      serviciosDisponibles.forEach(function(servicio) {
        if (!idsActuales[servicio.id]) {
          opcionesPaquetes += '<option value="' + servicio.id + '">' + escapeHtml(servicio.nombre) + '</option>';
        }
      });

      const bloqueAgregar = puedeEditarCantidades
        ? '<div class="row g-2 mb-2">' +
            '<div class="col-md-8">' +
              '<select id="edit-select-paquete" class="form-select">' + opcionesPaquetes + '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
              '<input id="edit-cantidad-paquete" type="number" min="' + (esControlCalidad ? 10 : 1) + '" class="form-control" value="' + (esControlCalidad ? 10 : 1) + '" placeholder="Cantidad">' +
            '</div>' +
            '<div class="col-md-2 d-grid">' +
              '<button type="button" id="edit-btn-agregar-paquete" class="btn btn-outline-primary">Agregar</button>' +
            '</div>' +
          '</div>'
        : '';

      const html =
        '<div style="text-align:left;">' +
          (!puedeEditarCantidades
            ? '<div class="alert alert-warning py-2 px-3 mb-3">El análisis ya inició. Solo puedes editar datos generales; la cantidad de muestras por venta está bloqueada.</div>'
            : '<div class="alert alert-info py-2 px-3 mb-3">Puedes editar cantidades porque el análisis aún no ha iniciado.</div>') +
          '<div class="row g-2">' +
            '<div class="col-md-6 mb-2">' +
              '<label class="form-label">Nombre del Proyecto</label>' +
              '<input id="edit-nombre-proyecto" class="form-control" value="' + escapeHtml(proyecto.Nombre_Proyecto) + '">' +
            '</div>' +
            '<div class="col-md-6 mb-2">' +
              '<label class="form-label">Valle</label>' +
              '<input id="edit-valle" class="form-control" value="' + escapeHtml(proyecto.Valle) + '">' +
            '</div>' +
            '<div class="col-md-4 mb-2">' +
              '<label class="form-label">Temporada</label>' +
              '<input id="edit-temporada" class="form-control" value="' + escapeHtml(proyecto.Temporada) + '">' +
            '</div>' +
            '<div class="col-md-4 mb-2">' +
              '<label class="form-label">Fecha de Inicio</label>' +
              '<input id="edit-fecha-inicio" type="date" class="form-control" value="' + escapeHtml(fechaInicio) + '">' +
            '</div>' +
            '<div class="col-md-4 mb-2">' +
              '<label class="form-label">Tipo de Muestra</label>' +
              '<select id="edit-tipo-muestra" class="form-select">' +
                '<option value="Agua" ' + (String(proyecto.Tipo_Muestra || '') === 'Agua' ? 'selected' : '') + '>Agua</option>' +
                '<option value="Suelo" ' + (String(proyecto.Tipo_Muestra || '') === 'Suelo' ? 'selected' : '') + '>Suelo</option>' +
              '</select>' +
            '</div>' +
            '<div class="col-md-4 mb-2">' +
              '<label class="form-label">Uso de Agua</label>' +
              '<input id="edit-uso-agua" class="form-control" value="' + escapeHtml(proyecto.Uso_Agua) + '">' +
            '</div>' +
            '<div class="col-md-4 mb-2">' +
              '<label class="form-label">Fuente de Agua</label>' +
              '<input id="edit-fuente-agua" class="form-control" value="' + escapeHtml(proyecto.Fuente_Agua) + '">' +
            '</div>' +
            '<div class="col-md-4 mb-2">' +
              '<label class="form-label">Nivel de Agua</label>' +
              '<input id="edit-nivel-agua" class="form-control" value="' + escapeHtml(proyecto.Nivel_Agua) + '">' +
            '</div>' +
            '<div class="col-md-12 mb-2">' +
              '<label class="form-check form-switch mt-2 mb-0">' +
                '<input id="edit-es-control-calidad" class="form-check-input" type="checkbox" ' + (esControlCalidad ? 'checked' : '') + '>' +
                '<span class="form-check-label">Proyecto de calidad de agua</span>' +
              '</label>' +
              '<small class="text-muted">Si está activo, cada servicio requiere al menos 10 muestras planificadas.</small>' +
            '</div>' +
          '</div>' +
          '<hr>' +
          '<h6 class="mb-2">Cantidades por Venta/Servicio</h6>' +
          bloqueAgregar +
          '<div class="table-responsive" style="max-height:260px; overflow:auto;">' +
            '<table class="table table-sm table-bordered mb-0" id="edit-tabla-servicios">' +
              '<thead class="table-light">' +
                '<tr><th>Venta/Servicio</th><th style="width:180px;" class="text-center">Cantidad Muestras</th><th style="width:90px;" class="text-center">Acción</th></tr>' +
              '</thead>' +
              '<tbody id="edit-tbody-servicios">' + filasServicios + '</tbody>' +
            '</table>' +
          '</div>' +
        '</div>';

      Swal.fire({
        title: 'Editar Proyecto #' + id,
        html: html,
        width: '920px',
        showCancelButton: true,
        confirmButtonText: 'Guardar Cambios',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        didOpen: function() {
          if (!puedeEditarCantidades) {
            return;
          }

          const $popup = $(Swal.getPopup());

          const sincronizarMinimosControl = function() {
            const esControl = $popup.find('#edit-es-control-calidad').is(':checked');
            const min = esControl ? 10 : 1;
            $popup.find('.edit-cantidad').attr('min', min).each(function() {
              const val = parseInt($(this).val(), 10) || 0;
              if (esControl && val < 10) {
                $(this).val(10);
              }
            });

            $popup.find('#edit-cantidad-paquete').attr('min', min);
            const valPaquete = parseInt($popup.find('#edit-cantidad-paquete').val(), 10) || 0;
            if (esControl && valPaquete < 10) {
              $popup.find('#edit-cantidad-paquete').val(10);
            }
          };

          sincronizarMinimosControl();
          $popup.on('change', '#edit-es-control-calidad', sincronizarMinimosControl);

          $popup.on('click', '#edit-btn-agregar-paquete', function() {
            const idPaquete = parseInt($popup.find('#edit-select-paquete').val(), 10) || 0;
            const cantidadPaquete = parseInt($popup.find('#edit-cantidad-paquete').val(), 10) || 0;
            const esControlModal = $popup.find('#edit-es-control-calidad').is(':checked');

            if (idPaquete <= 0) {
              Swal.showValidationMessage('Seleccione un paquete/venta para agregar.');
              return;
            }
            if (cantidadPaquete <= 0) {
              Swal.showValidationMessage('La cantidad del paquete debe ser mayor a 0.');
              return;
            }
            if (esControlModal && cantidadPaquete < 10) {
              Swal.showValidationMessage('Para calidad de agua, la cantidad mínima por servicio es 10.');
              return;
            }

            const yaExiste = $popup.find('.edit-cantidad[data-id="' + idPaquete + '"]').length > 0;
            if (yaExiste) {
              Swal.showValidationMessage('Ese paquete ya está agregado en el proyecto.');
              return;
            }

            const nombrePaquete = $popup.find('#edit-select-paquete option:selected').text();
            const nuevaFila = '<tr>' +
              '<td class="edit-nombre-servicio">' + escapeHtml(nombrePaquete) + '</td>' +
              '<td class="text-center">' +
                '<input type="number" class="form-control form-control-sm edit-cantidad" min="' + (esControlModal ? 10 : 1) + '" value="' + cantidadPaquete + '" data-id="' + idPaquete + '">' +
              '</td>' +
              '<td class="text-center"><button type="button" class="btn btn-sm btn-danger edit-remove-servicio"><i class="ti ti-trash"></i></button></td>' +
            '</tr>';

            $popup.find('#edit-tbody-servicios .edit-sin-servicios').remove();
            $popup.find('#edit-tbody-servicios').append(nuevaFila);
            $popup.find('#edit-select-paquete option:selected').remove();
            $popup.find('#edit-select-paquete').val('');
            Swal.resetValidationMessage();
          });

          $popup.on('click', '.edit-remove-servicio', function() {
            const $row = $(this).closest('tr');
            const idPaquete = parseInt($row.find('.edit-cantidad').data('id'), 10) || 0;
            const nombrePaquete = $row.find('.edit-nombre-servicio').text().trim();

            if (idPaquete > 0 && nombrePaquete) {
              $popup.find('#edit-select-paquete').append('<option value="' + idPaquete + '">' + escapeHtml(nombrePaquete) + '</option>');
            }

            $row.remove();
            if ($popup.find('#edit-tbody-servicios tr').length === 0) {
              $popup.find('#edit-tbody-servicios').append('<tr class="edit-sin-servicios"><td colspan="3" class="text-center text-muted">No hay ventas/servicios configurados</td></tr>');
            }
          });
        },
        preConfirm: function() {
          const nombre = $('#edit-nombre-proyecto').val().trim();
          const valle = $('#edit-valle').val().trim();
          const temporada = $('#edit-temporada').val().trim();
          const fechaInicioEdit = $('#edit-fecha-inicio').val();
          const tipoMuestra = $('#edit-tipo-muestra').val();
          const usoAgua = $('#edit-uso-agua').val().trim();
          const fuenteAgua = $('#edit-fuente-agua').val().trim();
          const nivelAgua = $('#edit-nivel-agua').val().trim();
          const esControlCalidadEdit = $('#edit-es-control-calidad').is(':checked') ? 1 : 0;

          if (!nombre || !valle || !temporada || !fechaInicioEdit || !tipoMuestra) {
            Swal.showValidationMessage('Complete los campos obligatorios del proyecto.');
            return false;
          }

          const servicios = [];
          $('.edit-cantidad').each(function() {
            const idProd = parseInt($(this).data('id'), 10) || 0;
            const cant = parseInt($(this).val(), 10) || 0;
            if (idProd > 0) {
              servicios.push({ id: idProd, cantidad: cant });
            }
          });

          if (servicios.some(function(s) { return s.cantidad <= 0; })) {
            Swal.showValidationMessage('Cada cantidad de muestra debe ser mayor a 0.');
            return false;
          }

          if (esControlCalidadEdit && servicios.some(function(s) { return s.cantidad < 10; })) {
            Swal.showValidationMessage('En calidad de agua, cada servicio debe tener al menos 10 muestras.');
            return false;
          }

          if (puedeEditarCantidades && servicios.length === 0) {
            Swal.showValidationMessage('Debe configurar al menos un paquete/venta con cantidad.');
            return false;
          }

          const payload = {
            action: 'editarProyecto',
            id_proyecto: id,
            nombre_proyecto: nombre,
            valle: valle,
            temporada: temporada,
            fecha_inicio: fechaInicioEdit,
            tipo_muestra: tipoMuestra,
            uso_agua: usoAgua,
            fuente_agua: fuenteAgua,
            nivel_agua: nivelAgua,
            es_control_calidad: esControlCalidadEdit,
            servicios: servicios
          };

          return $.ajax({
            url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php',
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            dataType: 'json'
          }).then(function(resp) {
            if (!resp.success) {
              throw new Error(resp.error || 'No se pudieron guardar los cambios');
            }
            return resp;
          }).catch(function(err) {
            const msg = (err.responseJSON && err.responseJSON.error) ? err.responseJSON.error : (err.message || 'No se pudieron guardar los cambios');
            Swal.showValidationMessage(msg);
            return false;
          });
        }
      }).then(function(result) {
        if (result.isConfirmed && result.value && result.value.success) {
          Swal.fire('Actualizado', result.value.mensaje || 'Proyecto actualizado correctamente', 'success');
          recargarTablasPeriodos(false);
        }
      });
    },
    error: function(err) {
      const msg = (err.responseJSON && err.responseJSON.error) ? err.responseJSON.error : 'No se pudieron cargar los datos del proyecto';
      Swal.fire('Error', msg, 'error');
    }
  });
}

function eliminarProyecto(id) {
    Swal.fire({
        title: '¿Eliminar Proyecto?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/muestra/views/creacion_masiva_api.php',
                type: 'POST',
                data: {
                    action: 'eliminarProyecto',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Éxito!', 'Proyecto eliminado correctamente', 'success');
                      recargarTablasPeriodos(true);
                    } else {
                        Swal.fire('Error', response.error, 'error');
                    }
                },
                error: function(err) {
                    Swal.fire('Error', 'Error al eliminar', 'error');
                }
            });
        }
    });
}

  function exportarProyectoMonitoreo(idProyecto) {
    const apiCategorias = 'modules/laboratorio/muestra/views/creacion_masiva_api.php?action=obtenerCategoriasLimite&id_proyecto=' + encodeURIComponent(idProyecto);

    $.ajax({
      url: apiCategorias,
      type: 'GET',
      dataType: 'json',
      success: function(response) {
        const categorias = (response && response.success && Array.isArray(response.categorias)) ? response.categorias : [];

        if (!categorias.length) {
          Swal.fire('Aviso', 'No se encontraron categorías de límites para este proyecto.', 'warning');
          return;
        }

        let htmlChecks = '<div style="text-align:left; max-height:260px; overflow:auto;">';
        categorias.forEach(function(item, idx) {
          const desc = String((item && item.descripcion) ? item.descripcion : '').trim();
          if (!desc) {
            return;
          }
          const idChk = 'cat_lim_' + idx;
          htmlChecks += '' +
            '<div class="form-check mb-2">' +
            '  <input class="form-check-input chk-cat-lim" type="checkbox" id="' + idChk + '" value="' + escapeHtml(desc) + '" checked>' +
            '  <label class="form-check-label" for="' + idChk + '">' + escapeHtml(desc) + '</label>' +
            '</div>';
        });
        htmlChecks += '</div>';

        Swal.fire({
          title: 'Seleccionar límites de comparación',
          html: '<p class="text-muted" style="text-align:left;">Selecciona las categorías (Descripción) que se usarán para marcar resultados en rojo.</p>' + htmlChecks,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Exportar',
          cancelButtonText: 'Cancelar',
          focusConfirm: false,
          preConfirm: function() {
            const seleccionadas = [];
            $('.swal2-container .chk-cat-lim:checked').each(function() {
              const val = String($(this).val() || '').trim();
              if (val) {
                seleccionadas.push(val);
              }
            });

            if (!seleccionadas.length) {
              Swal.showValidationMessage('Debes seleccionar al menos una categoría de límites.');
              return false;
            }

            return seleccionadas;
          }
        }).then(function(result) {
          if (!result.isConfirmed || !Array.isArray(result.value)) {
            return;
          }

          const params = new URLSearchParams();
          params.set('id_proyecto', String(idProyecto));
          result.value.forEach(function(cat) {
            params.append('categorias[]', cat);
          });

          window.location.href = 'modules/laboratorio/muestra/controllers/ExportarProyectoMonitoreo.php?' + params.toString();
        });
      },
      error: function(err) {
        const msg = (err.responseJSON && err.responseJSON.error)
          ? err.responseJSON.error
          : 'No se pudieron cargar las categorías de límites para exportar.';
        Swal.fire('Error', msg, 'error');
      }
    });
  }

function abrirAnalisis(id) {
    Swal.fire({
        title: '¿Iniciar Análisis?',
        text: '¿Desea continuar con el análisis de las muestras de este proyecto?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Los registros en blanco ya fueron creados automáticamente
            // Navegar a la página de análisis
            window.location.href = '?module=laboratorio&action=muestra&subaction=analisis_proyecto&id_proyecto=' + id;
        }
    });
}

function verResultados(id) {
    window.location.href = '?module=laboratorio&action=muestra&subaction=analisis_proyecto&id_proyecto=' + id;
}
</script>

