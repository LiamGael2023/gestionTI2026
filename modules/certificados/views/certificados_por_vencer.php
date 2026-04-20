<?php
// Obtener los datos de la API
$apiUrl = "https://www.chavimochic.gob.pe/api_incidencias/api_personal.php";
$apiResponse = file_get_contents($apiUrl);
$apiData = json_decode($apiResponse, true);

// Crear un arreglo asociativo por DNI para acceso rápido
$personalPorDNI = [];
if(isset($apiData['data'])){
    foreach($apiData['data'] as $p){
        $personalPorDNI[$p['Documento']] = $p;
    }
}
?>
<div class="container-xl mt-4">

    <!-- 🔷 HEADER PRINCIPAL -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">

            <div class="d-flex align-items-center gap-3">
                <a href="index.php?module=certificados" class="btn btn-outline-primary btn-sm">
                    ← Panel
                </a>

                <div>
                    <h5 class="mb-0 fw-semibold">Certificados por Vencer</h5>
                    <small class="text-muted">Listado actualizado de certificados</small>
                </div>
            </div>

            <!-- ACCIONES -->
            <div class="d-flex gap-2 flex-wrap">
<?php $tipo = $_GET['tipo'] ?? 'PERSONAL'; ?>
<a href="index.php?module=certificados&action=restablecerEstados&tipo=<?= $tipo ?>" 
   class="btn btn-outline-warning btn-sm">
    Restablecer
</a>
                <a href="index.php?module=certificados&action=exportarExcel&tipo=<?= $tipo ?>" 
   class="btn btn-outline-success btn-sm">
    Excel
</a>

<a href="index.php?module=certificados&action=exportarPDF&tipo=<?= $tipo ?>" 
   class="btn btn-outline-danger btn-sm">
    PDF
</a>

                <button class="btn btn-outline-secondary btn-sm" 
                        data-bs-toggle="modal" data-bs-target="#modalPDFs">
                    Archivos
                </button>
            </div>

        </div>
    </div>
<?php $tipo = $_GET['tipo'] ?? 'PERSONAL'; ?>

<ul class="nav nav-tabs mb-3">

    <li class="nav-item">
        <a class="nav-link <?= $tipo=='PERSONAL'?'active':'' ?>" 
           href="index.php?module=certificados&action=certificadosPorVencer1&tipo=PERSONAL">
            👤 Personal
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link <?= $tipo=='ENTIDAD'?'active':'' ?>" 
           href="index.php?module=certificados&action=certificadosPorVencer1&tipo=ENTIDAD">
            🏢 Entidad
        </a>
    </li>

</ul>
    <!-- 🔷 TABLA -->
    <div class="card shadow-sm border-0">

        <div class="card-body p-0">

            <div class="table-responsive" style="max-height: 500px; overflow-y:auto;">
                <table class="table table-sm table-hover align-middle mb-0">

                    <thead class="table-light sticky-top">
                        <tr>
                            <th>DNI</th>
                            <th>Persona</th>
                            <th>Cargo</th>
                            <th>Área</th>
                            <th>Contacto</th>
                            <th>Certificado</th>
                            <th>Modo</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($certificados as $c): 
                            $cargo = isset($personalPorDNI[$c['dni']]) ? $personalPorDNI[$c['dni']]['Carg_Descripcion'] : '-';
                        ?>
                        <tr>

                            <!-- DNI -->
                            <td class="fw-semibold"><?= $c['dni'] ?></td>

                            <!-- PERSONA -->
                            <td>
                                <div class="fw-semibold">
                                    <?= $c['apellidos']." ".$c['nombres'] ?>
                                </div>
                                <small class="text-muted">
                                    AV. FÁTIMA N° 431 – TRUJILLO
                                </small>
                            </td>

                            <!-- CARGO -->
                            <td><?= $cargo ?></td>

                            <!-- AREA -->
                            <td><?= $c['gerencia_laboral'] ?></td>

                            <!-- CONTACTO -->
                            <td>
                                <div><?= $c['telefono'] ?></div>
                                <small class="text-muted"><?= $c['correo'] ?></small>
                            </td>

                            <!-- TIPO CERTIFICADO -->
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= $c['tipo_certificado'] ?>
                                </span>
                            </td>

                            <!-- MODO -->
                            <td>
                                <?php 
                                if($c['tipo_certificado'] === 'TOKEN_SOFTWARE' || $c['tipo_certificado'] === 'SOFTWARE') {
                                    echo '<span class="badge bg-success-subtle text-success">Software</span>';
                                } elseif($c['tipo_certificado'] === 'TOKEN_HARDWARE' || $c['tipo_certificado'] === 'HARDWARE') {
                                    echo '<span class="badge bg-info-subtle text-info">Hardware</span>';
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>

                        </tr>
                        <?php endforeach ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<!-- Modal PDFs y Excels -->
<div class="modal fade" id="modalPDFs" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">📄 Archivos Generados</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <ul class="list-group list-group-flush">
          <?php
          $dir = __DIR__ . '/../controllers/tmp/'; 
          if (file_exists($dir)) {
              $files = array_diff(scandir($dir), array('.', '..'));
              if (count($files) > 0) {
                  // Ordenar por fecha de modificación descendente
                  usort($files, function($a, $b) use ($dir) {
                      return filemtime($dir . $b) - filemtime($dir . $a);
                  });

                  foreach ($files as $file) {
                      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                      $url = "modules/certificados/controllers/tmp/" . $file;

                      if ($ext === 'pdf') {
                          $icon = "<i class='bi bi-file-earmark-pdf'></i>";
                          $btnClass = "btn-primary";
                          $btnText = "Ver PDF";
                      } elseif ($ext === 'xlsx') {
                          $icon = "<i class='bi bi-file-earmark-excel'></i>";
                          $btnClass = "btn-success";
                          $btnText = "Abrir Excel";
                      } else {
                          continue; // ignorar otros tipos
                      }

                      echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                              $icon $file
                              <a href='$url' target='_blank' class='btn btn-sm $btnClass'>$btnText</a>
                            </li>";
                  }
              } else {
                  echo "<li class='list-group-item text-muted'>No hay archivos disponibles</li>";
              }
          } else {
              echo "<li class='list-group-item text-danger'>Carpeta no encontrada</li>";
          }
          ?>
        </ul>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-danger" id="btnEliminarTmp">🗑️ Eliminar Todos</button>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('btnEliminarTmp').addEventListener('click', function() {
    adqConfirmSafe({
        mensaje: '¿Está seguro que desea eliminar TODOS los archivos temporales?',
        textoAceptar: 'Eliminar',
        claseAceptar: 'btn-danger'
    }).then(function(ok) {
        if (ok) {
            window.location.href = 'index.php?module=certificados&action=eliminarArchivosTmp';
        }
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