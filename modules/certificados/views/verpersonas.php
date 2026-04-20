<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<div class="container mt-4" style="max-width: 1200px;">

<!-- ================= TABS ================= -->
<ul class="nav nav-tabs mb-3" id="tabsPersonas">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personasTab">
            👥 Personas
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#gerenciasTab">
            🏢 Gerencias
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ================= PERSONAS ================= -->
<div class="tab-pane fade show active" id="personasTab">

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">

        <div class="d-flex gap-2">
            <a href="index.php?module=certificados" class="btn btn-outline-secondary btn-sm">
                ← Panel
            </a>

            <a href="index.php?module=certificados&action=nuevo" class="btn btn-primary btn-sm">
                + Nueva Persona
            </a>
        </div>

        <strong>Total: <?= $total ?></strong>
    </div>

    <div class="card-body">

        <!-- BUSCADOR -->
        <form method="GET" class="row g-2 mb-3">
            <input type="hidden" name="module" value="certificados">
            <input type="hidden" name="action" value="verPersonas1">

            <div class="col-md-6">
                <input type="text" name="buscar1" class="form-control form-control-sm"
                placeholder="Buscar persona..." value="<?= $buscar1 ?? '' ?>">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100">Buscar</button>
            </div>
        </form>

        <!-- TABLA -->
        <div class="table-responsive">
            <table id="tablaPersonas" class="table table-hover table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>DNI</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Gerencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($personas as $p){ ?>
                    <tr>
                        <td><?= $p['id_persona'] ?></td>
                        <td><?= $p['dni'] ?></td>
                        <td><?= $p['nombres'] ?></td>
                        <td><?= $p['apellidos'] ?></td>
                        <td><?= $p['correo'] ?></td>
                        <td><?= $p['gerencia_laboral'] ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-info btn-sm btn-ver-certificados" data-id="<?= $p['id_persona'] ?>">
                                👁
                            </button>
                            <a href="index.php?module=certificados&action=editar&id=<?=$p['id_persona']?>" class="btn btn-warning btn-sm">✏</a>
                            <a href="index.php?module=certificados&action=eliminar1&id=<?=$p['id_persona']?>" class="btn btn-danger btn-sm js-eliminar-persona" data-confirm="Eliminar persona?">✕</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</div>

<!-- ================= GERENCIAS ================= -->
<div class="tab-pane fade" id="gerenciasTab">

<div class="card shadow-sm">
    <div class="card-header">
        <strong>Personas por Gerencia</strong>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table id="tablaGerencias" class="table table-striped table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th>Gerencia</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($gerencias as $g){ ?>
                    <tr>
                        <td><?= $g['gerencia_laboral'] ?></td>
                        <td><?= $g['total'] ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</div>

</div>
</div>

<!-- ================= MODAL ================= -->
<div class="modal fade" id="modalCertificados" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Certificados</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="contenidoCertificados">
        <div class="text-center p-4">
            <div class="spinner-border text-primary"></div>
            <div>Cargando...</div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ================= ESTILOS ================= -->
<style>
.card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.card-header {
    background: #f8fafc;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
}

.card-body {
    padding: 20px;
}

.nav-tabs {
    border-bottom: 2px solid #e2e8f0;
}

.nav-tabs .nav-link {
    font-size: 14px;
    border: none;
    color: #64748b;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom: 3px solid #0d6efd;
    background: transparent;
}

.tab-content {
    margin-top: 10px;
}
</style>

<!-- ================= JS ================= -->
<script>
$(function(){

$(document).ready(function(){

    const tablaPersonas = $('#tablaPersonas').DataTable({
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
        }
    });

    $('#tablaGerencias').DataTable({
        paging: false,
        searching: false,
        info: false,
        responsive: true
    });

    function ensureNotifyHelper() {
        if (window.adqNotify && window.adqNotifySafe) {
            return;
        }

        let container = document.getElementById('adq-alert-stack');
        if (!container) {
            container = document.createElement('div');
            container.id = 'adq-alert-stack';
            container.className = 'position-fixed bottom-0 end-0 p-3 d-flex flex-column gap-2';
            container.style.zIndex = '1100';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'false');
            document.body.appendChild(container);
        }

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

    $(document).on('click', '.js-eliminar-persona', function(e) {
        e.preventDefault();
        const enlace = this;
        const url = enlace.getAttribute('href');
        const mensaje = enlace.getAttribute('data-confirm') || '¿Eliminar persona?';

        adqConfirmSafe({ mensaje: mensaje, textoAceptar: 'Eliminar', claseAceptar: 'btn-danger' }).then(async function(ok) {
            if (!ok) {
                return;
            }

            try {
                const data = await fetchJson(url, { method: 'GET' });
                const $row = $(enlace).closest('tr');
                const $realRow = $row.hasClass('child') ? $row.prev() : $row;
                tablaPersonas.row($realRow).remove().draw(false);
                adqNotifySafe(data.type || 'success', data.title || 'Operacion completada', data.message || 'Persona eliminada correctamente');
            } catch (error) {
                adqNotifySafe('danger', 'Ocurrio un problema', error.message || 'No se pudo eliminar la persona');
            }
        });
    });

    // MODAL
    $('.btn-ver-certificados').click(function(e){
        e.preventDefault();

        let id = $(this).data('id');

        let modal = new bootstrap.Modal(document.getElementById('modalCertificados'));
        modal.show();

        $('#contenidoCertificados').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary"></div>
                <div>Cargando...</div>
            </div>
        `);

        fetch('index.php?module=certificados&action=verCertificadosPersona&id=' + id)
        .then(res => res.text())
        .then(html => {
            $('#contenidoCertificados').html(html);
        });
    });

});

});

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