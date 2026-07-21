<?php
$catastroModel = new CatastroPozoModel($conn);
$valles = $catastroModel->obtenerValles();
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .dataTables_wrapper .pagination .page-link { color: #1d273b; padding: 0.375rem 0.75rem; }
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #004d99; border-color: #004d99; color: white;
    }
    .dataTables_wrapper .pagination .page-item.disabled .page-link {
        color: #adb5bd; pointer-events: none; background-color: #fff; border-color: #dee2e6;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0 !important; margin: 0 !important; border: none !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: none !important; border: none !important; color: inherit !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: none !important; border: none !important; color: inherit !important;
    }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active">Pozos</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">CATASTRO DE POZOS</h2>
        <div class="text-muted mt-1">Sincroniza y visualiza el catastro de pozos desde PostgreSQL</div>
      </div>
    </div>
    <div class="row g-2">
      <div class="col-auto">
        <select class="form-select" id="filtro-valle-geoportal" style="width:180px;">
          <option value="">Todos los valles</option>
          <?php foreach ($valles as $v): ?>
          <option value="<?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="button" class="btn btn-outline-primary" onclick="irGeoportal()">
          <i class="ti ti-map-2 me-1"></i> Ver Mapa
        </button>
      </div>
      <div class="col-auto">
        <button type="button" class="btn btn-success" id="btn-sincronizar-pozos" onclick="sincronizarPozos()">
          <i class="ti ti-database-import me-1"></i> Sincronizar Pozos
        </button>
      </div>
      <div class="col-auto">
        <button type="button" class="btn btn-warning" id="btn-sincronizar-monitoreos" onclick="sincronizarMonitoreos()">
          <i class="ti ti-refresh me-1"></i> Sincronizar Monitoreos
        </button>
      </div>
      <div class="col-auto">
        <button type="button" class="btn btn-info" id="btn-importar-historicos" onclick="importarHistoricos()">
          <i class="ti ti-history me-1"></i> Importar Históricos Lab
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table id="tabla-pozos" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>No</th>
                <th>ID Pozo</th>
                <th>Codigo</th>
                <th>Valle</th>
                <th>Ubicacion</th>
                <th>Propietario</th>
                <th>Tipo</th>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var tablaPozos;

$(document).ready(function () {
    tablaPozos = $('#tabla-pozos').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/pozos/views/data_listado_pozos.php',
            type: 'POST'
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7, orderable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[0, 'asc']]
    });
});

function irGeoportal() {
    var valle = document.getElementById('filtro-valle-geoportal').value;
    var url = '?module=laboratorio&action=pozos&subaction=geoportal';
    if (valle) url += '&valle=' + encodeURIComponent(valle);
    window.location.href = url;
}

function sincronizarPozos() {
    Swal.fire({
        title: 'Sincronizar Pozos',
        text: 'Se conectara a PostgreSQL (hidrologia.pozos_catastro) y sincronizara todos los pozos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sincronizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#009540',
        reverseButtons: true
    }).then(function (result) {
        if (!result.isConfirmed) return;

        var btn = document.getElementById('btn-sincronizar-pozos');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sincronizando...';

        fetch('modules/laboratorio/pozos/controllers/PozoAPI.php?action=sincronizar_pozos', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-database-import me-1"></i> Sincronizar Pozos';
            if (resp.success) {
                var sinCambios = resp.sin_cambios || 0;
                var html = '<div class="text-start">'
                    + '<p class="mb-2"><span class="badge bg-green me-2">' + (resp.insertados || 0) + '</span> <b>Pozos insertados</b> (nuevos)</p>'
                    + '<p class="mb-2"><span class="badge bg-orange me-2">' + (resp.actualizados || 0) + '</span> <b>Pozos actualizados</b> (con cambios)</p>'
                    + (sinCambios > 0 ? '<p class="mb-2"><span class="badge bg-secondary me-2">' + sinCambios + '</span> Pozos sin cambios (omitidos)</p>' : '')
                    + '<p class="mb-0 mt-3 text-muted small">Total en PostgreSQL: <b>' + (resp.total_pg || 0) + '</b> pozos</p>'
                    + '</div>';
                Swal.fire({ icon: 'success', title: 'Sincronizacion Completa', html: html, confirmButtonColor: '#009540' });
                if (tablaPozos) tablaPozos.ajax.reload(null, false);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: resp.message || 'Error desconocido.', confirmButtonColor: '#009540' });
            }
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-database-import me-1"></i> Sincronizar Pozos';
            console.error('Sync fetch error:', err);
            Swal.fire({ icon: 'error', title: 'Error de Conexion', text: 'No se pudo conectar al servidor.\n' + err.message, confirmButtonColor: '#009540' });
        });
    });
}

function sincronizarMonitoreos() {
    Swal.fire({
        title: '¿Sincronizar Monitoreos desde PostgreSQL?',
        text: 'Esto creará proyectos masivos para los monitoreos no existentes.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, sincronizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = document.getElementById('btn-sincronizar-monitoreos');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sincronizando...';

            $.ajax({
                url: 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=sincronizar_monitoreos',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-refresh me-1"></i> Sincronizar Monitoreos';
                    if (res.success) {
                        let msj = `Proyectos creados: ${res.stats.proyectos_creados}<br>Proyectos actualizados: ${res.stats.proyectos_actualizados}`;
                        if (res.stats.errores.length > 0) {
                            msj += `<br><br><b>Errores:</b><br>${res.stats.errores.join('<br>')}`;
                        }
                        Swal.fire('Completado', msj, 'success');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-refresh me-1"></i> Sincronizar Monitoreos';
                    Swal.fire('Error', xhr.responseJSON?.message || 'Ocurrió un error en la sincronización', 'error');
                }
            });
        }
    });
}

function importarHistoricos() {
    Swal.fire({
        title: '¿Importar datos históricos de laboratorio?',
        html: 'Esto leerá los resultados existentes en <b>calidad_agua_laboratorio</b> de PostgreSQL y creará proyectos, muestras, solicitudes y resultados en SQL Server.<br><br><b>ATENCIÓN: Se reiniciarán todos los históricos de pozos antes de importar.</b>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, importar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = document.getElementById('btn-importar-historicos');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Importando...';

            Swal.fire({
                title: 'Preparando importación...',
                html: 'Obteniendo lotes de PostgreSQL. Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'modules/laboratorio/pozos/controllers/PozoImportAPI.php?action=importar_historicos_init',
                type: 'GET',
                dataType: 'json',
                success: function(resInit) {
                    if (resInit.success && resInit.lotes) {
                        const lotes = resInit.lotes;
                        const total = lotes.length;
                        
                        if (total === 0) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti ti-history me-1"></i> Importar Históricos Lab';
                            Swal.fire('Información', 'No hay datos históricos para importar.', 'info');
                            return;
                        }

                        let procesados = 0;
                        let resultados_totales = 0;
                        let errores_lotes = 0;

                        Swal.fire({
                            title: 'Importando Históricos',
                            html: `
                                <div class="mb-3"><b>Total lotes:</b> ${total} | Muestras procesadas: <span id="progreso-texto">0</span></div>
                                <div class="progress">
                                    <div id="progreso-barra" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                </div>
                                <div class="mt-2 small"><span class="text-muted">Resultados importados: <span id="progreso-resultados">0</span></span> | <span class="text-danger">Errores: <span id="progreso-errores">0</span></span></div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false
                        });

                        function procesarSiguienteLote() {
                            if (procesados >= total) {
                                btn.disabled = false;
                                btn.innerHTML = '<i class="ti ti-history me-1"></i> Importar Históricos Lab';
                                const msgErrores = errores_lotes > 0 ? `<br><span class="text-danger">⚠ ${errores_lotes} lotes con error</span>` : '';
                                Swal.fire('Importación Completada', `Se importaron exitosamente ${procesados} muestras y ${resultados_totales} resultados.${msgErrores}`, 'success');
                                if (typeof tablaMonitoreos !== 'undefined') tablaMonitoreos.ajax.reload();
                                if (tablaPozos) tablaPozos.ajax.reload(null, false);
                                return;
                            }

                            const lote = lotes[procesados];
                            
                            $.ajax({
                                url: 'modules/laboratorio/pozos/controllers/PozoImportAPI.php',
                                type: 'POST',
                                data: {
                                    action: 'importar_historicos_batch',
                                    id_medicion: lote.id_medicion,
                                    monitoreo: lote.monitoreo,
                                    valle: lote.valle,
                                    fechamonitoreo: lote.fechamonitoreo,
                                    id_pozo: lote.id_pozo,
                                    orden: lote.orden,
                                    numero_muestra: lote.numero_muestra
                                },
                                dataType: 'json',
                                success: function(resBatch) {
                                    if (resBatch.success) {
                                        procesados++;
                                        resultados_totales += (resBatch.resultados || 0);
                                        
                                        const porcentaje = Math.round((procesados / total) * 100);
                                        document.getElementById('progreso-texto').innerText = procesados;
                                        document.getElementById('progreso-resultados').innerText = resultados_totales;
                                        const barra = document.getElementById('progreso-barra');
                                        barra.style.width = porcentaje + '%';
                                        barra.innerText = porcentaje + '%';
                                        barra.setAttribute('aria-valuenow', porcentaje);

                                        procesarSiguienteLote();
                                    } else {
                                        errores_lotes++;
                                        procesados++;
                                        document.getElementById('progreso-texto').innerText = procesados;
                                        document.getElementById('progreso-errores').innerText = errores_lotes;
                                        const porcentaje = Math.round((procesados / total) * 100);
                                        const barra = document.getElementById('progreso-barra');
                                        barra.style.width = porcentaje + '%';
                                        barra.innerText = porcentaje + '%';
                                        procesarSiguienteLote(); // Continuar a pesar del error
                                    }
                                },
                                error: function(xhr) {
                                    errores_lotes++;
                                    procesados++;
                                    document.getElementById('progreso-texto').innerText = procesados;
                                    document.getElementById('progreso-errores').innerText = errores_lotes;
                                    const porcentaje = Math.round((procesados / total) * 100);
                                    const barra = document.getElementById('progreso-barra');
                                    barra.style.width = porcentaje + '%';
                                    barra.innerText = porcentaje + '%';
                                    procesarSiguienteLote(); // Continuar a pesar del error
                                }
                            });
                        }

                        // Iniciar la recursión
                        procesarSiguienteLote();

                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-history me-1"></i> Importar Históricos Lab';
                        Swal.fire('Error', resInit.message || 'Error al inicializar la importación.', 'error');
                    }
                },
                error: function(xhr) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-history me-1"></i> Importar Históricos Lab';
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error al contactar con el servidor.', 'error');
                }
            });
        }
    });
}
</script>
