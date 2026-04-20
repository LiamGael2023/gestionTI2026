<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Trámite de Certificados</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background-color: #f5f7fb;
}

/* Cards más limpias */
.card {
    border-radius: 10px;
}

/* Inputs más uniformes */
.form-control, .form-select {
    border-radius: 6px;
}

/* Botones */
.btn {
    border-radius: 6px;
}

/* Header elegante */
.card-header {
    font-weight: 600;
}

/* Lista de PDFs */
.list-group-item {
    border: none;
    border-bottom: 1px solid #eee;
}
</style>

</head>

<body>

<div class="container-xl mt-4">

    <!-- HEADER -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-0 fw-bold">📄 Trámite de Certificados</h4>
                <small class="text-muted">Gestión de envío y documentación</small>
            </div>

            <div class="d-flex gap-2">
                <a href="index.php?module=certificados" class="btn btn-outline-secondary btn-sm">
                    ← Panel
                </a>

                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBoletas">
                    📂 Boletas
                </button>
            </div>

        </div>
    </div>

    <!-- FORMULARIO -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <form id="form-tramite" action="index.php?module=certificados&action=enviar" method="post" enctype="multipart/form-data">

                <div class="row g-3">

                    <!-- Región -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Región</label>
                        <select name="region" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option>La Libertad</option>
                            <option>Lima</option>
                            <option>Arequipa</option>
                            <option>Cusco</option>
                        </select>
                    </div>

                    <!-- Archivos -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Archivo Excel</label>
                        <input type="file" name="excel" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Recibo / Boleta</label>
                        <input type="file" name="recibo" class="form-control" required>
                    </div>

                    <!-- Correos -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Correos destino</label>

                        <div id="listaCorreos">
                            <div class="input-group mb-2">
                                <input type="email" name="correos[]" class="form-control" placeholder="correo@region.gob.pe" required>
                                <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">✕</button>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="agregarCorreo()">
                            ➕ Agregar correo
                        </button>
                    </div>

                    <!-- Mensaje -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Mensaje</label>
                        <textarea name="mensaje" class="form-control" rows="5" required placeholder="Escriba el mensaje..."></textarea>
                    </div>

                </div>

                <!-- BOTÓN -->
                <div class="mt-4">
                    <button class="btn btn-primary w-100">
                        📤 Enviar trámite
                    </button>
                </div>

            </form>

        </div>

        <div class="card-footer text-center text-muted small">
            Sistema de Certificados © <?= date('Y') ?>
        </div>
    </div>

</div>

<!-- MODAL BOLETAS -->
<div class="modal fade" id="modalBoletas" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title fw-semibold">📄 Gestión de Boletas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- SUBIR -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">

                <form id="form-subir-boleta" action="index.php?module=certificados&action=subirBoleta" method="post" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="file" name="boletaPDF" class="form-control" accept="application/pdf" required>
                        <button class="btn btn-primary">Subir</button>
                    </div>
                </form>

            </div>
        </div>

        <!-- LISTA -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">

                <ul class="list-group list-group-flush">

                <?php
                $boletasDir = __DIR__ . '/../controllers/boletas/';
                if(file_exists($boletasDir)){
                    $files = array_diff(scandir($boletasDir), ['.', '..']);

                    if(count($files) > 0){
                        foreach($files as $file){
                            $fileUrl = "modules/certificados/controllers/boletas/" . $file;

                            echo "
                            <li class='list-group-item d-flex justify-content-between align-items-center'>

                                <div>
                                    <strong>$file</strong>
                                </div>

                                <div class='btn-group btn-group-sm'>

                                    <a href='$fileUrl' target='_blank' class='btn btn-outline-primary'>
                                        Ver
                                    </a>

                                    <form action='index.php?module=certificados&action=eliminarBoleta' method='post'>
                                        <input type='hidden' name='archivo' value='$file'>
                                        <button class='btn btn-outline-danger js-eliminar-boleta' data-confirm='¿Eliminar archivo?'>
                                            Eliminar
                                        </button>
                                    </form>

                                </div>

                            </li>";
                        }
                    } else {
                        echo "<li class='list-group-item text-muted text-center'>Sin archivos</li>";
                    }
                } else {
                    echo "<li class='list-group-item text-danger text-center'>Carpeta no encontrada</li>";
                }
                ?>

                </ul>

            </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<script>
function agregarCorreo(){
    let contenedor = document.getElementById('listaCorreos');

    let div = document.createElement('div');
    div.className = "input-group mb-2";

    div.innerHTML = `
        <input type="email" name="correos[]" class="form-control" placeholder="correo@region.gob.pe" required>
        <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">✕</button>
    `;

    contenedor.appendChild(div);
}
</script>

<script>
(function() {
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

    function notificarDesdeRespuesta(data, fallbackMessage) {
        const tipo = (data && data.type) ? data.type : 'info';
        const titulo = (data && data.title) ? data.title : (tipo === 'danger' ? 'Ocurrio un problema' : 'Operacion completada');
        const mensaje = (data && data.message) ? data.message : fallbackMessage;
        adqNotifySafe(tipo, titulo, mensaje);
    }

    const formTramite = document.getElementById('form-tramite');
    if (formTramite) {
        formTramite.addEventListener('submit', async function(e) {
            e.preventDefault();
            const boton = formTramite.querySelector('button[type="submit"]');
            const textoOriginal = boton ? boton.textContent : 'Enviar';
            if (boton) {
                boton.disabled = true;
                boton.textContent = 'Enviando...';
            }

            try {
                const data = await fetchJson(formTramite.action, {
                    method: 'POST',
                    body: new FormData(formTramite)
                });
                notificarDesdeRespuesta(data, 'Tramite enviado correctamente.');
                formTramite.reset();
                document.getElementById('listaCorreos').innerHTML = '';
                agregarCorreo();
            } catch (error) {
                notificarDesdeRespuesta({ type: 'danger', title: 'Ocurrio un problema', message: error.message }, error.message);
            } finally {
                if (boton) {
                    boton.disabled = false;
                    boton.textContent = textoOriginal;
                }
            }
        });
    }

    const formSubirBoleta = document.getElementById('form-subir-boleta');
    if (formSubirBoleta) {
        formSubirBoleta.addEventListener('submit', async function(e) {
            e.preventDefault();
            const boton = formSubirBoleta.querySelector('button[type="submit"]');
            const textoOriginal = boton ? boton.textContent : 'Subir';
            if (boton) {
                boton.disabled = true;
                boton.textContent = 'Subiendo...';
            }

            try {
                const data = await fetchJson(formSubirBoleta.action, {
                    method: 'POST',
                    body: new FormData(formSubirBoleta)
                });
                notificarDesdeRespuesta(data, 'PDF subido correctamente.');
                window.setTimeout(function() {
                    window.location.reload();
                }, 700);
            } catch (error) {
                notificarDesdeRespuesta({ type: 'danger', title: 'Ocurrio un problema', message: error.message }, error.message);
            } finally {
                if (boton) {
                    boton.disabled = false;
                    boton.textContent = textoOriginal;
                }
            }
        });
    }

    document.addEventListener('click', function(e) {
        const boton = e.target.closest('.js-eliminar-boleta');
        if (!boton) {
            return;
        }

        e.preventDefault();
        const form = boton.closest('form');
        if (!form) {
            return;
        }

        const mensaje = boton.getAttribute('data-confirm') || '¿Eliminar archivo?';
        adqConfirmSafe({ mensaje: mensaje, textoAceptar: 'Eliminar', claseAceptar: 'btn-danger' }).then(async function(ok) {
            if (!ok) {
                return;
            }

            try {
                const data = await fetchJson(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                notificarDesdeRespuesta(data, 'PDF eliminado correctamente.');
                const item = boton.closest('li');
                if (item && item.parentNode) {
                    item.parentNode.removeChild(item);
                }
            } catch (error) {
                notificarDesdeRespuesta({ type: 'danger', title: 'Ocurrio un problema', message: error.message }, error.message);
            }
        });
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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

</body>
</html>