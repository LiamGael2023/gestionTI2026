<script src="https://cdn.jsdelivr.net/npm/fslightbox/index.js"></script>

<style>
  /* === Select2 con Bootstrap 5 — Altura y alineación perfectas === */

  /* Asegura que el contenedor ocupe todo el ancho */
  .select2-container {
    width: 100% !important;
  }

  /* Ajusta la altura del select como un form-select de Bootstrap 5 */
  .select2-container--bootstrap-5 .select2-selection {
    height: calc(2.5rem + 2px) !important;
    padding: 0 0.75rem !important;
    font-size: 1rem !important;
    display: flex !important;
    align-items: center !important;
    /* Centrado vertical */
    border-radius: 0.375rem !important;
  }

  /* Corrige el alto y alineación del texto seleccionado */
  .select2-container--bootstrap-5 .select2-selection__rendered {
    margin: 0 !important;
    padding: 0 !important;
    line-height: normal !important;
    display: flex !important;
    align-items: center !important;
    white-space: nowrap;
  }

  /* Flecha del dropdown bien alineada */
  .select2-container--bootstrap-5 .select2-selection__arrow {
    height: 100% !important;
    top: 0 !important;
    right: 0.75rem !important;
    display: flex !important;
    align-items: center !important;
  }

  /* Popup de resultados más limpio */
  .select2-container--bootstrap-5 .select2-results__option {
    padding: 6px 10px !important;
    font-size: 0.95rem !important;
  }

  /* Oculta la flecha (dropdown arrow) */
  .select2-container--bootstrap-5 .select2-selection__arrow {
    display: none !important;
    visibility: hidden !important;
    width: 0 !important;
    height: 0 !important;
    pointer-events: none !important;
  }

  /* Acomoda el texto para que no quede espacio vacío */
  .select2-container--bootstrap-5 .select2-selection__rendered {
    padding-right: 0 !important;
    /* elimina el espacio reservado para la flecha */
  }

  /* ==== ARREGLA QUE EL BOTÓN "X" NO SE SALGA ==== */

  /* Mueve la X dentro del select */
  .select2-container--bootstrap-5 .select2-selection__clear {
    position: absolute !important;
    right: 0.75rem !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    margin: 0 !important;
    font-size: 1.2rem !important;
    z-index: 10 !important;
  }

  /* Evita que Select2 reserve espacio excesivo */
  .select2-container--bootstrap-5 .select2-selection__rendered {
    padding-right: 1.8rem !important;
    /* Espacio exacto para la X */
  }

  /* Por si la flecha aparece, ocultarla siempre */
  .select2-container--bootstrap-5 .select2-selection__arrow {
    display: none !important;
  }
</style>

<div class="page-body">
  <div class="container-xl">
    <div class="row row-cards">

      <!-- Card del filtro encima de la tabla -->
      <div class="col-12 mb-3">
        <div class="card">
          <div class="card-body">
            <div class="mb-3" id="listado-trabajadores">
              <label class="form-label">Listado de trabajadores: </label>
              <select class="form-select" id="trabajador" name="trabajador" style="width:100%;">
                <option value="">Seleccione un trabajador...</option>
              </select>
            </div>

          </div>
        </div>
      </div>

      <!-- Card de la tabla -->
      <div class="col-12">
        <div class="card">
          <div class="card-table">
            <div class="table-responsive">
              <table id="new-cons"
                class="display table table-striped table-hover dt-responsive nowrap tablaPapeletaPorTrabajador"
                style="width: 100%">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Mig.</th>
                    <th>QR</th>
                    <th>Foto</th>
                    <th>Nombres</th>
                    <th>Firmas</th>
                    <th>Concepto/Motivo</th>
                    <th>Jefe <br> Inmediato</th>
                    <th>Fechas</th>
                    <th>Horas</th>
                    <th>Retorn.</th>
                    <th>Acciones</th>
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

<!-- Modal para mostrar el código QR -->
<?php include __DIR__ . '/../fragments/modals/papeleta-qr.php'; ?>
<!-- Modal para mostrar el PDF -->
<?php include __DIR__ . '/../fragments/modals/contenedor-pdf.php'; ?>
<!-- Modal para mostrar las evidencias -->
<?php include __DIR__ . '/../fragments/modals/mostrar-evidencias.php'; ?>

<script>
  $(document).ready(function() {

    $('#trabajador').select2({
      theme: 'bootstrap-5',
      placeholder: 'Seleccione un trabajador...',
      allowClear: true,
      width: '100%',
      ajax: {
        url: 'ajax/ajax/colaborador.ajax.php', // tu archivo PHP
        dataType: 'json',
        delay: 250,
        data: function(params) {
          return {
            action: 'getTrabajadoresActivos', // acción que devuelve JSON
            q: params.term // texto buscado
          };
        },
        processResults: function(data) {
          return {
            results: data.map(function(item) {
              return {
                id: item.id,
                text: item.text,
                oficina: item.oficina,
                foto: item.foto
              };
            })
          };
        },
        cache: true
      },
      templateResult: function(item) {
        if (!item.id) return item.text;

        const sinFoto = '../personal/vistas/static/avatars/sinfoto.jpg';
        const fotoUrl = item.foto ? item.foto : sinFoto;

        return $(
          '<div class="d-flex align-items-center">' +
          '<img src="' + fotoUrl + '" onerror="this.src=\'' + sinFoto + '\'" ' +
          'class="rounded me-3" style="width:48px;height:48px;object-fit:cover;" />' +
          '<div>' +
          '<div class="fw-bold small text-muted">' + (item.oficina || '') + '</div>' +
          '<div>' + item.text + '</div>' +
          '</div>' +
          '</div>'
        );
      },
      templateSelection: function(item) {
        if (!item.id) return item.text;
        return item.text + (item.oficina ? ' - ' + item.oficina : '');
      },
      escapeMarkup: function(markup) {
        return markup;
      }
    });

  });
  document.addEventListener('lazybeforeunveil', function(e) {
    var bg = e.target.getAttribute('data-bg');
    if (bg) {
      e.target.style.backgroundImage = 'url(' + bg + ')';
    }
  });

  // Después de agregar los avatares al DOM
  if (window.refreshFsLightbox) {
    window.refreshFsLightbox();
  }

  $(document).on('click', '.avatar-lightbox', function(e) {
    e.preventDefault(); // evita redirección
    const src = $(this).attr('href'); // URL de la imagen
    const caption = $(this).data('caption') || '';

    // Crear un lightbox temporal
    const tempFsLightbox = document.createElement('a');
    tempFsLightbox.href = src;
    tempFsLightbox.setAttribute('data-fslightbox', 'single');
    tempFsLightbox.setAttribute('data-caption', caption);
    document.body.appendChild(tempFsLightbox);

    // Abrir lightbox
    if (window.refreshFsLightbox) window.refreshFsLightbox();
    tempFsLightbox.click();

    // Limpiar elemento temporal
    setTimeout(() => document.body.removeChild(tempFsLightbox), 100);
  });
</script>