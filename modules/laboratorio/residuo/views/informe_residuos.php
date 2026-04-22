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
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=residuo">Residuos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Informe de Residuos</li>
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
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-crear-informe">
          <i class="ti ti-file-text me-2"></i> Crear Informe
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info" role="alert">
      <div>
        "Crear Informe de Residuos" e ingrese el mes, año, ubicación, código SST y versión; el sistema filtrará
        automáticamente todos los registros del inventario correspondientes a ese período y sede para consolidar la información en la
        columna adecuada.
      </div>
    </div>

    <!-- Informes de Residuos -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Informes de Residuos</h3>
      </div>
      <div class="card-body">
        <p class="text-muted small">Listado consolidado de todos los informes de residuos creados</p>

        <div class="table-responsive">
          <table id="tabla-informes" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>Código SST</th>
                <th>Ubicación</th>
                <th>Año</th>
                <th>Mes</th>
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

<!-- Modal: Crear Informe de Residuos -->
<div class="modal modal-blur fade" id="modal-crear-informe" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Crear Informe de Residuos</h5>
      </div>
      <div class="modal-body">
        <form id="form-informe">
          <div class="mb-3">
            <label class="form-label">Año</label>
            <input type="number" class="form-control" id="anio_informe" placeholder="Año del informe" min="2020" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Mes</label>
            <input type="number" class="form-control" id="mes_informe" placeholder="Mes del informe" min="1" max="12" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Ubicación</label>
            <input type="text" class="form-control" id="ubicacion_informe" placeholder="Ejem: Compartimento San Jose" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Código SST</label>
            <input type="text" class="form-control" id="codigo_sst_informe" placeholder="SST-16" value="SST-16" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Normativas aplicables (seleccione una o más)</label>
            <div id="normativas-informe-list" style="border: 1px solid #dee2e6; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">
              <div style="text-align: center; color: #999;">Cargando normativas...</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea class="form-control" id="observacion_informe" rows="3" placeholder="Observación del informe"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" form="form-informe" class="btn btn-success">Crear Informe</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Editar Informe de Residuos -->
<div class="modal modal-blur fade" id="modal-editar-informe" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title">Editar Informe de Residuos</h5>
      </div>
      <div class="modal-body">
        <form id="form-editar-informe">
          <input type="hidden" id="editar_id_informe">

          <div class="mb-3">
            <label class="form-label">Año</label>
            <input type="number" class="form-control" id="editar_anio_informe" min="2020" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Mes</label>
            <input type="number" class="form-control" id="editar_mes_informe" min="1" max="12" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Ubicación</label>
            <input type="text" class="form-control" id="editar_ubicacion_informe" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Código SST</label>
            <input type="text" class="form-control" id="editar_codigo_sst_informe" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Normativas aplicables (seleccione una o más)</label>
            <div id="normativas-editar-list" style="border: 1px solid #dee2e6; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">
              <div style="text-align: center; color: #999;">Cargando normativas...</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea class="form-control" id="editar_observacion_informe" rows="3" placeholder="Observación del informe"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" form="form-editar-informe" class="btn btn-warning">Guardar cambios</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const usuarioId = <?php echo intval($usuario_id); ?>;
let normativasInformeCache = [];

$(document).ready(function() {
    inicializarTablaInformes();
  cargarNormativasParaInforme();
});

function renderNormativasInforme(containerSelector, selectedIds) {
  const seleccion = new Set((selectedIds || []).map(function(id) { return parseInt(id, 10); }));

  if (!Array.isArray(normativasInformeCache) || normativasInformeCache.length === 0) {
    $(containerSelector).html('<div style="text-align:center;color:#999;">No hay normativas activas</div>');
    return;
  }

  let html = '';
  normativasInformeCache.forEach(function(normativa) {
    const id = parseInt(normativa.Id_Normativa_SST || 0, 10);
    const nombre = (normativa.Nombre_Ley || '').toString();
    const desc = (normativa.Descripcion || '').toString();
    const checked = seleccion.has(id) ? ' checked' : '';

    html += '<label class="form-check mb-1">';
    html += '<input class="form-check-input normativa-check" type="checkbox" value="' + id + '"' + checked + '>';
    html += '<span class="form-check-label"><strong>' + nombre + '</strong>';
    if (desc) {
      html += '<br><small class="text-muted">' + desc + '</small>';
    }
    html += '</span></label>';
  });

  $(containerSelector).html(html);
}

function inicializarTablaInformes() {
    $('#tabla-informes').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/residuo/views/data_informes.php',
            type: 'POST'
        },
        columnDefs: [
            { orderable: false, targets: [5] }
        ],
        columns: [
            { data: 0, title: 'No' },
            { data: 1, title: 'Código SST' },
            { data: 2, title: 'Ubicación' },
            { data: 3, title: 'Año' },
            { data: 4, title: 'Mes' },
            { data: 5, title: 'Acción', orderable: false, searchable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
}

function cargarNormativasParaInforme() {
    $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=obtener_normativas',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && Array.isArray(response.data)) {
        normativasInformeCache = response.data;
        renderNormativasInforme('#normativas-informe-list', []);
        renderNormativasInforme('#normativas-editar-list', []);
            }
        }
    });
}

// Formulario Informe
$('#form-informe').on('submit', function(e) {
    e.preventDefault();

  const idsNormativas = [];
  $('#normativas-informe-list .normativa-check:checked').each(function() {
    const idN = parseInt($(this).val() || '0', 10);
    if (idN > 0) {
      idsNormativas.push(idN);
    }
  });

  if (idsNormativas.length === 0) {
    Swal.fire('Advertencia', 'Seleccione al menos una normativa para el informe', 'warning');
    return;
  }
    
    let datos = {
        Mes: $('#mes_informe').val(),
        Anio: $('#anio_informe').val(),
        Ubicacion: $('#ubicacion_informe').val(),
        Codigo_SST: $('#codigo_sst_informe').val(),
        Observacion: $('#observacion_informe').val(),
    Ids_Normativas: idsNormativas,
        Usuario_Creacion: usuarioId
    };

    $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=crear_informe',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(datos),
        dataType: 'json',
        success: function(response) {
            Swal.fire('Éxito', response.message, 'success').then(() => {
                $('#modal-crear-informe').modal('hide');
                $('#form-informe')[0].reset();
              renderNormativasInforme('#normativas-informe-list', []);
                $('#tabla-informes').DataTable().ajax.reload();
            });
        },
        error: function(xhr) {
            let mensaje = 'Error al crear informe';
            try {
                let resp = JSON.parse(xhr.responseText);
                mensaje = resp.message || mensaje;
            } catch(e) {}
            Swal.fire('Error', mensaje, 'error');
        }
    });
});

// ===== SIMULACIÓN CIERRE DIARIO =====

function simularCierreDiario() {
    // Buscar una solicitud pendiente para simular
    Swal.fire({
        title: 'Simular Cierre Diario',
        input: 'number',
        inputLabel: 'ID de Solicitud_Analisis a Finalizar',
        inputPlaceholder: 'Ej: 1',
        showCancelButton: true,
        confirmButtonText: 'Simular',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value || value <= 0) {
                return 'Ingresa un ID válido'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let id_solicitud = parseInt(result.value);
            
            $.ajax({
                url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=simular_cierre_diario',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ Id_Solicitud_Analisis: id_solicitud }),
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        title: '✅ Cierre Simulado',
                        html: `
                            <div style="text-align: left; padding: 10px;">
                                <p><strong>Solicitud:</strong> #${response.datos.solicitud_id}</p>
                                <p><strong>Servicio:</strong> #${response.datos.servicio_id}</p>
                                <p><strong>Estado:</strong> ${response.datos.estado_nuevo}</p>
                                <p><strong>Residuos registrados hoy:</strong> ${response.datos.residuos_registrados_hoy}</p>
                                <p style="color: #666; font-size: 0.9em; margin-top: 15px;">
                                    ✓ El TRIGGER fue ejecutado automáticamente<br>
                                    ✓ Se creó/reutilizó la cabecera del mes<br>
                                    ✓ Se insertaron detalles de residuos
                                </p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    }).then(() => {
                        $('#tabla-informes').DataTable().ajax.reload();
                    });
                },
                error: function(xhr) {
                    let mensaje = 'Error al simular cierre';
                    try {
                        let resp = JSON.parse(xhr.responseText);
                        mensaje = resp.message || mensaje;
                    } catch(e) {}
                    Swal.fire('Error', mensaje, 'error');
                }
            });
        }
    });
}

// ===== VER INFORME DETALLADO =====
function verInforme(id_registro) {
    window.location.href = '?module=laboratorio&action=residuo&view=ver_informe&id=' + id_registro;
}

// ===== EDITAR INFORME =====
function editarInforme(id_registro) {
  $.ajax({
    url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=obtener_informe&id=' + encodeURIComponent(id_registro),
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      if (!response.success || !response.data) {
        Swal.fire('Error', response.message || 'No se pudo obtener el informe', 'error');
        return;
      }

      const data = response.data;
      $('#editar_id_informe').val(data.Id_Registro_Res || '');
      $('#editar_mes_informe').val(data.Mes || '');
      $('#editar_anio_informe').val(data.Anio || '');
      $('#editar_ubicacion_informe').val(data.Ubicacion || '');
      $('#editar_codigo_sst_informe').val(data.Codigo_SST || 'SST-16');
      $('#editar_observacion_informe').val(data.Observacion || '');

      renderNormativasInforme('#normativas-editar-list', data.Ids_Normativas || []);
      $('#modal-editar-informe').modal('show');
    },
    error: function(xhr) {
      let mensaje = 'Error al obtener el informe';
      try {
        const resp = JSON.parse(xhr.responseText);
        mensaje = resp.message || mensaje;
      } catch (e) {}
      Swal.fire('Error', mensaje, 'error');
    }
  });
}

$('#form-editar-informe').on('submit', function(e) {
  e.preventDefault();

  const idsNormativas = [];
  $('#normativas-editar-list .normativa-check:checked').each(function() {
    const idN = parseInt($(this).val() || '0', 10);
    if (idN > 0) {
      idsNormativas.push(idN);
    }
  });

  if (idsNormativas.length === 0) {
    Swal.fire('Advertencia', 'Seleccione al menos una normativa para el informe', 'warning');
    return;
  }

  const datos = {
    Id_Registro_Res: parseInt($('#editar_id_informe').val() || '0', 10),
    Mes: $('#editar_mes_informe').val(),
    Anio: $('#editar_anio_informe').val(),
    Ubicacion: $('#editar_ubicacion_informe').val(),
    Codigo_SST: $('#editar_codigo_sst_informe').val(),
    Observacion: $('#editar_observacion_informe').val(),
    Ids_Normativas: idsNormativas
  };

  $.ajax({
    url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=editar_informe',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(datos),
    dataType: 'json',
    success: function(response) {
      Swal.fire('Éxito', response.message, 'success').then(() => {
        $('#modal-editar-informe').modal('hide');
        $('#tabla-informes').DataTable().ajax.reload();
      });
    },
    error: function(xhr) {
      let mensaje = 'Error al actualizar informe';
      try {
        const resp = JSON.parse(xhr.responseText);
        mensaje = resp.message || mensaje;
      } catch (e) {}
      Swal.fire('Error', mensaje, 'error');
    }
  });
});

$('#modal-editar-informe').on('hidden.bs.modal', function() {
  $('#form-editar-informe')[0].reset();
  $('#editar_id_informe').val('');
  renderNormativasInforme('#normativas-editar-list', []);
});

// ===== ELIMINAR INFORME =====
function eliminarInforme(id_registro) {
    Swal.fire({
        title: '¿Eliminar informe?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=eliminar_informe',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ Id_Registro_Res: id_registro }),
                dataType: 'json',
                success: function(response) {
                    Swal.fire('Éxito', response.message, 'success').then(() => {
                        $('#tabla-informes').DataTable().ajax.reload();
                    });
                },
                error: function(xhr) {
                    let mensaje = 'Error al eliminar informe';
                    try {
                        let resp = JSON.parse(xhr.responseText);
                        mensaje = resp.message || mensaje;
                    } catch(e) {}
                    Swal.fire('Error', mensaje, 'error');
                }
            });
        }
    });
}

function reactivarInforme(id_registro) {
  Swal.fire({
    title: '¿Reactivar informe?',
    text: 'Este informe volverá a estar disponible en el sistema',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Reactivar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: 'modules/laboratorio/residuo/controllers/ResiduoAPI.php?action=reactivar_informe',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ Id_Registro_Res: id_registro }),
        dataType: 'json',
        success: function(response) {
          Swal.fire('Reactivado', response.message, 'success').then(() => {
            $('#tabla-informes').DataTable().ajax.reload();
          });
        },
        error: function(xhr) {
          let mensaje = 'Error al reactivar informe';
          try {
            const resp = JSON.parse(xhr.responseText);
            mensaje = resp.message || mensaje;
          } catch (e) {}
          Swal.fire('Error', mensaje, 'error');
        }
      });
    }
  });
}
</script>

</body>
</html>
