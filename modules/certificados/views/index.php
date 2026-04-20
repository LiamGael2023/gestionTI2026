<?php if($porVencer > 0): ?>

<div class="container-xl px-4 mt-3">
    <div class="alert alert-warning shadow-sm small d-flex align-items-center gap-2">
        ⚠ Existen <strong><?= $porVencer ?></strong> certificados por vencer en los próximos 15 días.
    </div>
</div>
<?php endif; ?>

<!-- ================= LIBRERÍAS ================= -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.0/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<!-- ================= ESTILOS ================= -->
<style>
.badge-activo{background:#dcfce7;color:#15803d;padding:3px 8px;border-radius:20px}
.badge-vencer{background:#fef9c3;color:#92400e;padding:3px 8px;border-radius:20px}
.badge-vencido{background:#fee2e2;color:#b91c1c;padding:3px 8px;border-radius:20px}

.cert-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
.cert-topbar{background:#f8fafc;padding:12px;display:flex;gap:8px;flex-wrap:wrap}
.cert-tabs .nav-link{font-size:.85rem}
.cert-filters{background:#f8fafc;padding:12px;border-bottom:1px solid #e2e8f0}

/* ===== RESPONSIVE REAL ===== */
@media (max-width: 768px){

    .cert-card{
        margin: 10px;
        border-radius: 10px;
    }

    table.dataTable{
        width: 100% !important;
    }

    table.dataTable td{
        white-space: normal !important;
        font-size: 12px;
    }

    .btn{
        font-size: 11px;
        padding: 4px 6px;
    }

    .cert-topbar{
        flex-direction: column;
    }

    .nav-tabs{
        font-size: 12px;
        overflow-x: auto;
        flex-wrap: nowrap;
    }
}
table.dataTable tbody tr.dtrg-group td {
    background: #e7f1ff !important;
    border-top: 1px solid #cfe2ff;
    border-bottom: 1px solid #cfe2ff;
}
</style>

<div class="container-xl px-4 mt-3">

<!-- ================= TOP ================= -->
<div class="cert-card mb-3">
    <div class="cert-topbar">
       <a href="index.php?module=certificados&action=crearBackup1" class="btn btn-outline-primary btn-sm">Backup</a>
            <a href="index.php?module=certificados&action=verPersonas1" class="btn btn-outline-secondary btn-sm">Personas</a>
            <a href="index.php?module=certificados&action=certificadosPorVencer1" class="btn btn-outline-danger btn-sm">Reportes</a>
            <a href="index.php?module=certificados&action=tramite" class="btn btn-outline-primary btn-sm">Trámite</a>
            <a href="index.php?module=certificados&action=dashboard" class="btn btn-dark btn-sm">Dashboard</a>
    </div>
</div>

<!-- ================= TABS ================= -->
<!-- ================= TABS ================= -->
<ul class="nav nav-tabs">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#principal">Certificados</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#alertas">Por vencer</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#fechas">Fechas</button></li>
</ul>

<div class="tab-content mt-3">

<!-- ================= PRINCIPAL ================= -->
<div class="tab-pane fade show active" id="principal">

<div class="cert-card">

  <!-- FILTROS -->
                <div class="card-body border-bottom">
                    <form method="GET" class="row g-2 align-items-center">
                        <input type="hidden" name="module" value="certificados">
                        <div class="col-md-3">
                            <input type="text" name="buscar" value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>" class="form-control form-control-sm" placeholder="Buscar...">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="fecha_inicio" value="<?= $_GET['fecha_inicio'] ?? '' ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="fecha_fin" value="<?= $_GET['fecha_fin'] ?? '' ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <select name="tipo_tramite" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="PERSONAL" <?= (($_GET['tipo_tramite'] ?? '') == 'PERSONAL') ? 'selected' : '' ?>>PERSONAL</option>
                                <option value="ENTIDAD" <?= (($_GET['tipo_tramite'] ?? '') == 'ENTIDAD') ? 'selected' : '' ?>>ENTIDAD</option>
                            </select>
                        </div>
<div class="col-md-2 d-flex gap-2">
<button class="btn btn-primary btn-sm w-100">Buscar</button>
<button type="button"
    id="btn-abrir-crear-certificado"
    class="btn btn-outline-primary btn-sm w-100"
    title="Nuevo certificado"
    data-bs-toggle="modal"
    data-bs-target="#modalCrearCertificado">
   +
</button>
</div>
                    </form>
                </div>
<!-- TABLA PRINCIPAL -->
 <!--  -->
<table id="tablaPrincipal" class="table table-hover w-100">
<thead>
<tr>
<th>#</th>
<th>DNI</th>
<th>Persona</th>
<th>Gerencia</th>
<th>Usuario</th>
<th>Emisión</th>
<th>Vencimiento</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>
<?php
$i=1; $last=null;
foreach($certificados as $c):

$dias=(strtotime($c['fecha_vencimiento'])-time())/86400;

if($dias<0){$estado="Vencido";$cls="badge-vencido";}
elseif($dias<=15){$estado="Por vencer";$cls="badge-vencer";}
else{$estado="Activo";$cls="badge-activo";}

?>
<tr>
<td><?= $i++ ?></td>
<td><?= $c['dni'] ?></td>
<td><?= $c['nombres'].' '.$c['apellidos'] ?></td>
<td><?= $c['gerencia_laboral'] ?></td>
<td><?= $c['usuario_nombre'] ?></td>
<td><?= date('d/m/Y',strtotime($c['fecha_emision'])) ?></td>
<td><?= date('d/m/Y',strtotime($c['fecha_vencimiento'])) ?></td>
<td><span class="<?= $cls ?>"><?= $estado ?></span></td>
<td>
<div class="d-flex justify-content-center gap-1 flex-wrap">

  <button
      class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center"
      onclick="detalle(<?= $c['id_certificado'] ?>)"
      title="Ver detalle"
      data-bs-toggle="tooltip">
      <i class="bi bi-eye"></i>
  </button>

  <button
      class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center"
      onclick="backups(<?= $c['id_certificado'] ?>)"
      title="Ver backups"
      data-bs-toggle="tooltip">
      <i class="bi bi-folder2-open"></i>
  </button>

<a href="index.php?module=certificados&action=eliminar&id=<?= $c['id_certificado'] ?>"
    class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center js-eliminar-certificado"
    data-confirm="¿Eliminar?"
   title="Eliminar"
   data-bs-toggle="tooltip"
   style="text-decoration:none;">
   <i class="bi bi-x-lg"></i>
</a>

</div>
</td>


</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
</div>

<!-- ================= ALERTAS ================= -->
<div class="tab-pane fade" id="alertas">
<table id="tablaAlertas" class="table table-hover w-100">
<thead><tr><th>#</th><th>DNI</th><th>Persona</th><th>Fecha</th></tr></thead>
<tbody>
<?php $i=1; foreach($proximos as $p): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $p['dni'] ?></td>
<td><?= $p['nombres'].' '.$p['apellidos'] ?></td>
<td><?= date('d/m/Y',strtotime($p['fecha_vencimiento'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- ================= FECHAS ================= -->
<div class="tab-pane fade" id="fechas">
<table id="tablaFechas" class="table table-hover w-100">
    <div class="col-md-4">
                            <select id="filtroAnio" class="form-select form-select-sm">
                                <option value="">Año</option>
                                <?php for($i = date('Y')-5; $i <= date('Y')+5; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filtroMes" class="form-select form-select-sm">
                                <option value="">Mes</option>
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?= $m ?>"><?= $m ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filtroSemana" class="form-select form-select-sm">
                                <option value="">Semana</option>
                                <?php for($s=1; $s<=5; $s++): ?>
                                    <option value="<?= $s ?>">Semana <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
<thead><tr><th>#</th><th>Persona</th><th>Fecha</th></tr></thead>
<tbody>
<?php foreach($certificados as $c): ?>
<tr data-fecha="<?= $c['fecha_vencimiento'] ?>">
<td></td>
<td><?= $c['nombres'].' '.$c['apellidos'] ?></td>
<td><?= date('d/m/Y',strtotime($c['fecha_vencimiento'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
</div>

<!-- ================= MODALES ================= -->
<div class="modal fade" id="modalBackups">
<div class="modal-dialog modal-xl">
<div class="modal-content">
<div class="modal-header">
<h5>Backups</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body" id="contenidoBackups"></div>
</div>
</div>
</div>

<div class="modal fade" id="modalDetalle">
<div class="modal-dialog modal-xl">
<div class="modal-content">
<div class="modal-header">
<h5>Detalle</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body" id="contenidoDetalle"></div>
</div>
</div>
</div>

<div class="modal fade" id="modalCrearCertificado" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header">
<h5>Nuevo Certificado</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-0" style="height: 80vh;">
<iframe id="iframeCrearCertificado" title="Crear certificado" src="about:blank" style="width:100%;height:100%;border:0;"></iframe>
</div>
</div>
</div>
</div>

<!-- ================= JS ================= -->
<script>

$(function(){

/* ================= TABLA PRINCIPAL ================= */
window.tablaPrincipal = $('#tablaPrincipal').DataTable({
    responsive: true,
    pageLength: 10,
    order: [[0, 'asc']],
    autoWidth: false,
    scrollX: true,

    language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
    },

    rowGroup: {
        dataSrc: function (row) {
            return row[1] + ' - ' + row[2] + ' - ' + row[3];
        },
        startRender: function (rows, group) {

            return $('<tr/>')
                .addClass('table-primary fw-bold')
                .append(
                    $('<td/>', {
                        colspan: 9,
                        class: 'py-2 px-3 align-middle'
                    }).html(`
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="fw-semibold">
                                👤 ${group}
                            </div>
                            <span class="badge bg-dark">
                                ${rows.count()} certificados
                            </span>
                        </div>
                    `)
                );
        }
    },

    columnDefs: [
        { targets: [1,2,3], visible: false },
        { targets: [8], orderable: false }
    ]
});


/* ================= ALERTAS ================= */
$('#tablaAlertas').DataTable({
    responsive: true,
    autoWidth: false,
    language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
    }
});
/* ================= TABLA FECHAS ================= */
/* ================= FECHAS ================= */
const dt = $('#tablaFechas').DataTable({
    responsive: true,
    pageLength: 10,
    autoWidth: false,
    language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
    }
});


/* ================= FILTRO FECHAS (SOLO UNA VEZ) ================= */
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex){

    if (settings.nTable.id !== 'tablaFechas') return true;

    let anio = $('#filtroAnio').val();
    let mes = $('#filtroMes').val();
    let semana = $('#filtroSemana').val();

    let fecha = data[2]; // dd/mm/yyyy

    if (!fecha) return true;

    let partes = fecha.split('/');
    let d = new Date(partes[2], partes[1]-1, partes[0]);

    if (anio && d.getFullYear() != anio) return false;
    if (mes && (d.getMonth()+1) != mes) return false;
    if (semana && Math.ceil(d.getDate()/7) != semana) return false;

    return true;
});

/* ================= EVENTOS FILTRO ================= */
$('#filtroAnio, #filtroMes, #filtroSemana').on('change', function(){
    dt.draw();
});

/* ================= NUMERACIÓN CORRECTA ================= */
dt.on('draw', function(){
    let i = 1;

    dt.rows({ search: 'applied' }).every(function(){
        $(this.node()).find('td:first').text(i++);
    });
});

function ensureNotifyHelper() {
    if (window.adqNotify && window.adqNotifySafe) {
        return;
    }

    const container = document.createElement('div');
    container.id = 'adq-alert-stack';
    container.className = 'position-fixed bottom-0 end-0 p-3 d-flex flex-column gap-2';
    container.style.zIndex = '1100';
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'false');
    document.body.appendChild(container);

    window.adqNotify = function(type, heading, description, options) {
        const opts = Object.assign({ delay: 3200, autohide: true }, options || {});
        const alertType = ['success', 'info', 'warning', 'danger'].indexOf(type) >= 0 ? type : 'info';

        const alertEl = document.createElement('div');
        alertEl.className = 'alert alert-' + alertType;
        alertEl.style.margin = '0';
        alertEl.setAttribute('role', 'alert');

        const headingEl = document.createElement('h4');
        headingEl.className = 'alert-heading';
        headingEl.textContent = heading || 'Informacion';

        alertEl.appendChild(headingEl);
        if (description) {
            const descriptionEl = document.createElement('div');
            descriptionEl.style.whiteSpace = 'pre-line';
            descriptionEl.textContent = description;
            alertEl.appendChild(descriptionEl);
        }

        container.appendChild(alertEl);

        function closeAlert() {
            if (alertEl.parentNode) {
                alertEl.parentNode.removeChild(alertEl);
            }
        }

        alertEl.addEventListener('click', closeAlert);
        if (opts.autohide) {
            window.setTimeout(closeAlert, opts.delay);
        }
    };

    window.adqNotifySafe = function(type, heading, description, options) {
        if (typeof window.adqNotify === 'function') {
            return window.adqNotify(type, heading, description, options);
        }
        return null;
    };
}

ensureNotifyHelper();

function notificarDesdeRespuesta(data, fallbackMessage) {
    const tipo = (data && data.type) ? data.type : 'info';
    const titulo = (data && data.title) ? data.title : (tipo === 'danger' ? 'Ocurrio un problema' : 'Operacion completada');
    const mensaje = (data && data.message) ? data.message : fallbackMessage;
    if (typeof window.adqNotifySafe === 'function') {
        window.adqNotifySafe(tipo, titulo, mensaje);
    }
}

async function fetchJson(url, options) {
    const response = await fetch(url, Object.assign({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    }, options || {}));

    const text = await response.text();
    let data = null;
    try {
        data = JSON.parse(text);
    } catch (e) {
        throw new Error(text || 'Respuesta invalida del servidor.');
    }

    if (!response.ok || (data && data.success === false)) {
        throw new Error((data && data.message) ? data.message : 'No se pudo completar la operación.');
    }

    return data;
}

let backupCertificadoActual = null;

function calcularEstadoVisual(fechaVencimiento) {
    const venc = new Date(String(fechaVencimiento).replace(' ', 'T'));
    const ahora = new Date();
    const dias = (venc.getTime() - ahora.getTime()) / 86400000;

    if (dias < 0) {
        return { texto: 'Vencido', clase: 'badge-vencido' };
    }
    if (dias <= 15) {
        return { texto: 'Por vencer', clase: 'badge-vencer' };
    }
    return { texto: 'Activo', clase: 'badge-activo' };
}

function formatearFecha(fecha) {
    const d = new Date(String(fecha).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) {
        return '';
    }

    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yy = d.getFullYear();
    return dd + '/' + mm + '/' + yy;
}

window.agregarFilaCertificadoDesdeAjax = function(row) {
    if (!row || !row.id_certificado) {
        console.warn('agregarFilaCertificadoDesdeAjax: row o id_certificado faltante', row);
        return;
    }

    console.log('agregarFilaCertificadoDesdeAjax: agregando fila', row);

    // Verificar que tablaPrincipal existe en window
    if (typeof window.tablaPrincipal === 'undefined') {
        console.error('agregarFilaCertificadoDesdeAjax: window.tablaPrincipal no está definida');
        return;
    }

    const estadoVisual = calcularEstadoVisual(row.fecha_vencimiento);

    const accionesHtml = `
<div class="d-flex justify-content-center gap-1 flex-wrap">
  <button
      class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center"
      onclick="detalle(${row.id_certificado})"
      title="Ver detalle"
      data-bs-toggle="tooltip">
      <i class="bi bi-eye"></i>
  </button>

  <button
      class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center"
      onclick="backups(${row.id_certificado})"
      title="Ver backups"
      data-bs-toggle="tooltip">
      <i class="bi bi-folder2-open"></i>
  </button>

  <a href="index.php?module=certificados&action=eliminar&id=${row.id_certificado}"
     class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center js-eliminar-certificado"
     data-confirm="¿Eliminar?"
     title="Eliminar"
     data-bs-toggle="tooltip"
     style="text-decoration:none;">
     <i class="bi bi-x-lg"></i>
  </a>
</div>`;

    try {
        // Reindexa para que el nuevo registro quede como Nro 1.
        window.tablaPrincipal.rows().every(function() {
            const dataFila = this.data();
            const nroActual = parseInt(dataFila[0], 10);
            if (!Number.isNaN(nroActual)) {
                dataFila[0] = nroActual + 1;
                this.data(dataFila);
            }
        });

        const nuevaFila = window.tablaPrincipal.row.add([
            1,
            row.dni || '',
            ((row.nombres || '') + ' ' + (row.apellidos || '')).trim(),
            row.gerencia_laboral || '',
            row.usuario_nombre || '',
            formatearFecha(row.fecha_emision),
            formatearFecha(row.fecha_vencimiento),
            `<span class="${estadoVisual.clase}">${estadoVisual.texto}</span>`,
            accionesHtml
        ]);

        window.tablaPrincipal.draw(false);
        // Muestra inmediatamente el nuevo registro en la primera pagina.
        window.tablaPrincipal.page('first').draw('page');

        const nodoNuevaFila = nuevaFila.node();
        if (nodoNuevaFila) {
            $(nodoNuevaFila).addClass('table-success');
            window.setTimeout(function() {
                $(nodoNuevaFila).removeClass('table-success');
            }, 1800);
        }

        console.log('agregarFilaCertificadoDesdeAjax: fila agregada exitosamente');

        $('[data-bs-toggle="tooltip"]').tooltip();

        const modalCrearLocalEl = document.getElementById('modalCrearCertificado');
        if (modalCrearLocalEl) {
            const modalCrear = bootstrap.Modal.getOrCreateInstance(modalCrearLocalEl);
            modalCrear.hide();
        }

        notificarDesdeRespuesta({
            type: 'success',
            title: 'Operacion completada',
            message: 'Certificado creado correctamente'
        }, 'Certificado creado correctamente');
    } catch (e) {
        console.error('agregarFilaCertificadoDesdeAjax: error al agregar fila', e);
    }
};

const modalCrearEl = document.getElementById('modalCrearCertificado');
if (modalCrearEl) {
    modalCrearEl.addEventListener('show.bs.modal', function() {
        const iframe = document.getElementById('iframeCrearCertificado');
        if (iframe) {
            iframe.src = 'index.php?module=certificados&action=crear&embedded=1&t=' + Date.now();
        }
    });
}

$(document).on('click', '.js-eliminar-certificado', function(e) {
    e.preventDefault();
    const enlace = this;
    const url = enlace.getAttribute('href');
    const mensaje = enlace.getAttribute('data-confirm') || '¿Eliminar este registro?';

    adqConfirmSafe({ mensaje: mensaje, textoAceptar: 'Eliminar', claseAceptar: 'btn-danger' }).then(async function(ok) {
        if (!ok) {
            return;
        }

        try {
            const data = await fetchJson(url, { method: 'GET' });
            const $row = $(enlace).closest('tr');
            const $realRow = $row.hasClass('child') ? $row.prev() : $row;
            tablaPrincipal.row($realRow).remove().draw(false);
            notificarDesdeRespuesta(data, 'Registro eliminado.');
        } catch (error) {
            notificarDesdeRespuesta({ type: 'danger', title: 'Ocurrio un problema', message: error.message }, error.message);
        }
    });
});

$('#contenidoBackups').on('submit', '.js-backup-form', async function(e) {
    e.preventDefault();
    const form = this;
    const submit = form.querySelector('button[type="submit"]');
    if (submit) {
        submit.disabled = true;
    }

    try {
        const data = await fetchJson(form.action, {
            method: 'POST',
            body: new FormData(form)
        });
        notificarDesdeRespuesta(data, 'Backup guardado.');
        if (backupCertificadoActual) {
            backups(backupCertificadoActual);
        }
    } catch (error) {
        notificarDesdeRespuesta({ type: 'danger', title: 'Ocurrio un problema', message: error.message }, error.message);
    } finally {
        if (submit) {
            submit.disabled = false;
        }
    }
});

$('#contenidoBackups').on('click', '.js-backup-delete', function(e) {
    e.preventDefault();
    const enlace = this;
    const mensaje = enlace.getAttribute('data-confirm') || '¿Eliminar este backup?';

    adqConfirmSafe({ mensaje: mensaje, textoAceptar: 'Eliminar', claseAceptar: 'btn-danger' }).then(async function(ok) {
        if (!ok) {
            return;
        }

        try {
            const data = await fetchJson(enlace.href, { method: 'GET' });
            notificarDesdeRespuesta(data, 'Backup eliminado.');
            if (backupCertificadoActual) {
                backups(backupCertificadoActual);
            }
        } catch (error) {
            notificarDesdeRespuesta({ type: 'danger', title: 'Ocurrio un problema', message: error.message }, error.message);
        }
    });
});

/* ================= TOOLTIPS ================= */
$('[data-bs-toggle="tooltip"]').tooltip();

});

/* ================= MODALES ================= */
function backups(id){
    backupCertificadoActual = id;
    new bootstrap.Modal(document.getElementById('modalBackups')).show();
    fetch('?module=certificados&action=verBackups&id='+id)
    .then(r=>r.text())
    .then(d=>$('#contenidoBackups').html(d));
}

function detalle(id){
    new bootstrap.Modal(document.getElementById('modalDetalle')).show();
    fetch('?module=certificados&action=detalleModal&id='+id)
    .then(r=>r.text())
    .then(d=>$('#contenidoDetalle').html(d));
}

</script>
<script>
(function() {
    if (window.adqConfirm && window.adqConfirmSafe) {
        return;
    }

    function asegurarModal() {
        var modalExistente = document.getElementById('adq-modal-confirmacion');
        if (modalExistente) {
            return modalExistente;
        }

        var wrapper = document.createElement('div');
        wrapper.innerHTML = '' +
            '<div class="modal modal-blur fade" id="adq-modal-confirmacion" tabindex="-1" role="dialog" aria-hidden="true">' +
            '  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">' +
            '    <div class="modal-content">' +
            '      <div class="modal-body text-center py-4">' +
            '        <h3 id="adq-confirmacion-titulo">Confirmar eliminacion</h3>' +
            '        <div id="adq-confirmacion-mensaje" class="text-secondary">¿Desea continuar?</div>' +
            '      </div>' +
            '      <div class="modal-footer">' +
            '        <div class="w-100">' +
            '          <div class="row">' +
            '            <div class="col">' +
            '              <button type="button" id="adq-confirmacion-cancelar" class="btn btn-primary w-100" data-bs-dismiss="modal">Cancelar</button>' +
            '            </div>' +
            '            <div class="col">' +
            '              <button type="button" id="adq-confirmacion-aceptar" class="btn btn-danger w-100">Eliminar</button>' +
            '            </div>' +
            '          </div>' +
            '        </div>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        var estilo = document.createElement('style');
        estilo.textContent = '#adq-modal-confirmacion{z-index:1085;}';
        document.head.appendChild(estilo);

        document.body.appendChild(wrapper.firstElementChild);
        return document.getElementById('adq-modal-confirmacion');
    }

    function prepararZIndexConfirmacion(modalEl) {
        var modalesAbiertos = Array.prototype.slice.call(document.querySelectorAll('.modal.show')).filter(function(el) {
            return el !== modalEl;
        });

        var zBase = 1055;
        modalesAbiertos.forEach(function(el) {
            var z = parseInt(window.getComputedStyle(el).zIndex, 10);
            if (!Number.isNaN(z) && z > zBase) {
                zBase = z;
            }
        });

        modalEl.style.zIndex = String(zBase + 30);

        setTimeout(function() {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            if (!backdrops.length) {
                return;
            }
            backdrops[backdrops.length - 1].style.zIndex = String(zBase + 20);
        }, 0);
    }

    window.adqConfirm = function(options) {
        var opts = Object.assign({
            titulo: 'Confirmar eliminacion',
            mensaje: '¿Desea continuar?',
            textoAceptar: 'Eliminar',
            textoCancelar: 'Cancelar',
            claseAceptar: 'btn-danger'
        }, options || {});

        var modalEl = asegurarModal();
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return Promise.resolve(window.confirm(opts.mensaje || '¿Desea continuar?'));
        }

        var tituloEl = document.getElementById('adq-confirmacion-titulo');
        var mensajeEl = document.getElementById('adq-confirmacion-mensaje');
        var btnAceptar = document.getElementById('adq-confirmacion-aceptar');
        var btnCancelar = document.getElementById('adq-confirmacion-cancelar');

        tituloEl.textContent = opts.titulo;
        mensajeEl.textContent = opts.mensaje;
        btnAceptar.textContent = opts.textoAceptar;
        btnCancelar.textContent = opts.textoCancelar;
        btnAceptar.className = 'btn w-100 ' + opts.claseAceptar;

        var instancia = bootstrap.Modal.getOrCreateInstance(modalEl);
        prepararZIndexConfirmacion(modalEl);

        return new Promise(function(resolve) {
            var resulto = false;

            function limpiar() {
                btnAceptar.removeEventListener('click', onAceptar);
                modalEl.removeEventListener('hidden.bs.modal', onOculto);
            }

            function onAceptar() {
                resulto = true;
                limpiar();
                instancia.hide();
                resolve(true);
            }

            function onOculto() {
                if (resulto) {
                    modalEl.style.removeProperty('z-index');
                    return;
                }
                limpiar();
                modalEl.style.removeProperty('z-index');
                resolve(false);
            }

            btnAceptar.addEventListener('click', onAceptar);
            modalEl.addEventListener('hidden.bs.modal', onOculto);
            instancia.show();
        });
    };

    window.adqConfirmSafe = function(options) {
        if (typeof window.adqConfirm === 'function') {
            return window.adqConfirm(options);
        }
        var mensaje = options && options.mensaje ? options.mensaje : '¿Desea continuar?';
        return Promise.resolve(window.confirm(mensaje));
    };
})();
</script>