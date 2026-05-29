<?php
require_once 'config/db.php';
require_once 'modules/laboratorio/muestra/models/MuestraModel.php';

$conn = Conexion::conectar();
$model = new MuestraModel($conn);

$id_muestra = intval($_GET['id_muestra'] ?? 0);
$muestra = $id_muestra > 0 ? $model->obtenerPorId($id_muestra) : null;

if (!$muestra) {
    echo '<div class="container-xl mt-4"><div class="alert alert-danger">No se encontró la muestra solicitada.</div></div>';
    return;
}

$detalleAgua = $model->obtenerDetalleAgua($id_muestra);
$detalleSuelo = $model->obtenerDetalleSuelo($id_muestra);

$esAgua = !empty($detalleAgua);
$fuenteAgua = $detalleAgua['Fuente_Agua'] ?? '-';
$usoAgua = $detalleAgua['Uso_Agua'] ?? '-';
$cantidad = $esAgua ? ($detalleAgua['Cantidad_Muestra'] ?? '-') : ($detalleSuelo['Cantidad_Muestra'] ?? '-');

$fuenteRiego = $detalleSuelo['Fuente_Riego'] ?? '-';
$profundidadSuelo = $detalleSuelo['Profundidad'] ?? '-';
$numeroSubmuestras = $detalleSuelo['Numero_Submuestras'] ?? '-';
$cultivoAnterior = $detalleSuelo['Cultivo_Anterior'] ?? '-';
$cultivoImplementado = $detalleSuelo['Cultivo_Implementado'] ?? '-';
$cultivoPorImplementar = $detalleSuelo['Cultivo_Por_Implementar'] ?? '-';

$tipoServicio = trim((string)($muestra['Tipo_Servicio'] ?? 'No definido'));
$tipoServicioLower = strtolower($tipoServicio);
$tipoServicioUI = ($tipoServicioLower === 'interno' || $tipoServicioLower === 'externo') ? ucfirst($tipoServicioLower) : $tipoServicio;
$tipoServicioBadge = $tipoServicioLower === 'interno' ? 'bg-blue-lt text-blue' : 'bg-orange-lt text-orange';

$fechaCreacion = $muestra['Fecha_Creacion'] instanceof DateTime ? $muestra['Fecha_Creacion']->format('d-m-Y') : '-';
$fechaRecepcion = $muestra['Fecha_Recepcion'] instanceof DateTime ? $muestra['Fecha_Recepcion']->format('d-m-Y') : '-';

$rutaImagen = trim((string)($muestra['Ruta_Imagen'] ?? ''));
?>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=dashboard">Inicio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Formulario Recepción</li>
      </ol>
    </nav>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <h2 class="page-title mb-0">Formulario Recepción</h2>
      <span class="badge bg-green text-white px-3 py-2">MUESTRA <?php echo str_pad((string)$id_muestra, 3, '0', STR_PAD_LEFT); ?></span>
      <span class="badge <?php echo $tipoServicioBadge; ?> px-3 py-2">Servicio: <?php echo htmlspecialchars($tipoServicioUI); ?></span>
    </div>
    <div class="text-muted mt-2">Verifique los datos generales y las condiciones físicas antes de iniciar el análisis.</div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="card mb-4" style="background:#e9f7e9; border:none;">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 border-end">
            <h3 class="h4 mb-3">Datos Generales</h3>
            <div class="mb-2"><strong>Agricultor:</strong> <?php echo htmlspecialchars($muestra['Agricultor'] ?? '-'); ?></div>
            <div class="mb-2"><strong>Valle:</strong> <?php echo htmlspecialchars($muestra['Valle'] ?? '-'); ?></div>
            <div class="mb-2"><strong>Coordenadas:</strong> <?php echo htmlspecialchars($muestra['Ubicacion'] ?? '-'); ?></div>
            <div class="mb-2"><strong>Creación:</strong> <?php echo $fechaCreacion; ?></div>
            <div class="mb-2"><strong>Recepción:</strong> <?php echo $fechaRecepcion; ?></div>
          </div>
          <div class="col-md-6">
            <h3 class="h4 mb-3">Información Específica</h3>
            <div class="mb-2"><strong>Tipo de Muestra:</strong> <?php echo $esAgua ? 'Agua' : 'Suelo'; ?></div>
            <?php if ($esAgua): ?>
              <div class="mb-2"><strong>Uso de Agua:</strong> <?php echo htmlspecialchars((string)$usoAgua); ?></div>
              <div class="mb-2"><strong>Fuente de Agua:</strong> <?php echo htmlspecialchars((string)$fuenteAgua); ?></div>
              <div class="mb-2"><strong>Cantidad Muestra:</strong> <?php echo htmlspecialchars((string)$cantidad); ?></div>
            <?php else: ?>
              <div class="mb-2"><strong>Fuente de Riego:</strong> <?php echo htmlspecialchars((string)$fuenteRiego); ?></div>
              <div class="mb-2"><strong>Profundidad:</strong> <?php echo htmlspecialchars((string)$profundidadSuelo); ?></div>
              <div class="mb-2"><strong>Número de Submuestras:</strong> <?php echo htmlspecialchars((string)$numeroSubmuestras); ?></div>
              <div class="mb-2"><strong>Cultivo Anterior:</strong> <?php echo htmlspecialchars((string)$cultivoAnterior); ?></div>
              <div class="mb-2"><strong>Cultivo Implementado:</strong> <?php echo htmlspecialchars((string)$cultivoImplementado); ?></div>
              <div class="mb-2"><strong>Cultivo Por Implementar:</strong> <?php echo htmlspecialchars((string)$cultivoPorImplementar); ?></div>
              <div class="mb-2"><strong>Cantidad Muestra:</strong> <?php echo htmlspecialchars((string)$cantidad); ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h3 class="card-title">Verificación de las Condiciones de la Muestra</h3>
      </div>
      <div class="card-body">
        <p class="text-muted">Inspeccione visualmente el estado del contenedor y la integridad de los sellos antes de proceder con la aceptación técnica.</p>
        <div class="table-responsive">
          <table class="table table-striped table-vcenter">
            <thead>
              <tr>
                <th>Parámetro de Evaluación</th>
                <th class="text-center">Cumple</th>
                <th class="text-center">No Cumple</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $checks = [
                'Muestra correctamente rotulada',
                'Envase limpio y adecuado',
                'Cantidad suficiente de muestra',
                'Muestra sin contaminación visible',
                'Ficha de muestreo completa',
                'Información legible y coherente'
              ];
              foreach ($checks as $idx => $texto):
              ?>
              <tr>
                <td><?php echo htmlspecialchars($texto); ?></td>
                <td class="text-center"><input class="form-check-input" type="radio" name="check_<?php echo $idx; ?>" value="1" checked></td>
                <td class="text-center"><input class="form-check-input" type="radio" name="check_<?php echo $idx; ?>" value="0"></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h3 class="card-title">Archivo Adjunto</h3>
      </div>
      <div class="card-body">
        <p class="text-muted">Revise la validez del documento cargado antes de proceder con la aprobación de la muestra.</p>
        <?php if ($rutaImagen !== ''): ?>
          <?php
            $isPdf   = strpos($rutaImagen, 'data:application/pdf') === 0;
            $isImage = strpos($rutaImagen, 'data:image/') === 0;
          ?>
          <?php if ($isImage): ?>
            <div class="mb-2">
              <img src="<?php echo $rutaImagen; ?>"
                   class="img-thumbnail"
                   style="max-height:300px; cursor:zoom-in;"
                   onclick="document.getElementById('adjunto-overlay').style.display='flex';"
                   title="Click para ampliar">
            </div>
            <div id="adjunto-overlay"
                 onclick="this.style.display='none';"
                 style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                        background:rgba(0,0,0,0.87); z-index:9999; align-items:center;
                        justify-content:center; cursor:zoom-out;">
              <img src="<?php echo $rutaImagen; ?>" style="max-width:92%; max-height:92%; border-radius:4px;">
            </div>
          <?php elseif ($isPdf): ?>
            <div>
              <button class="btn btn-outline-primary btn-sm mb-3" id="btn-toggle-pdf"
                      onclick="var f=document.getElementById('adjunto-pdf-frame');
                               f.classList.toggle('d-none');
                               this.textContent = f.classList.contains('d-none') ? 'Ver PDF' : 'Ocultar PDF';">
                <i class="ti ti-file-type-pdf me-1"></i> Ver PDF
              </button>
              <iframe id="adjunto-pdf-frame"
                      src="<?php echo $rutaImagen; ?>"
                      class="d-none"
                      style="width:100%; height:520px; border:1px solid #ddd; border-radius:4px;"></iframe>
            </div>
          <?php else: ?>
            <a class="btn btn-outline-success btn-sm" href="<?php echo htmlspecialchars($rutaImagen); ?>" target="_blank">Ver adjunto</a>
          <?php endif; ?>
        <?php else: ?>
          <span class="badge bg-secondary">Sin adjunto</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex justify-content-center gap-4 mb-5">
      <a href="?module=laboratorio&action=muestra" class="btn btn-outline-secondary px-5">Cancelar</a>
      <button type="button" class="btn btn-success px-5" id="btn-confirmar-recepcion">Confirmar</button>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-confirmar-recepcion" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmación de Recepción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">¿La muestra se encuentra en condiciones para su análisis?</p>
        <div class="d-flex gap-3 mb-3">
          <label class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="recepcion-pasa" value="1" checked>
            <span class="form-check-label">Si</span>
          </label>
          <label class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="recepcion-pasa" value="0">
            <span class="form-check-label">No</span>
          </label>
        </div>

        <div class="mb-3">
          <label class="form-label" for="recepcion-tipo-servicio">Tipo de Servicio <span class="text-danger">*</span></label>
          <select class="form-select" id="recepcion-tipo-servicio" required>
            <option value="Interno" <?php echo $tipoServicioLower === 'interno' ? 'selected' : ''; ?>>Interno</option>
            <option value="Externo" <?php echo $tipoServicioLower === 'externo' ? 'selected' : ''; ?>>Externo</option>
          </select>
        </div>

        <label class="form-label" for="recepcion-observacion">Observaciones</label>
        <textarea class="form-control" id="recepcion-observacion" rows="4" placeholder="Observaciones de la muestra (opcional)"></textarea>
        <div class="form-hint">Si marca No, la observación es obligatoria para indicar motivo de rechazo.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-guardar-confirmacion">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<script>
const idMuestra = <?php echo intval($id_muestra); ?>;
const apiRecepcion = 'modules/laboratorio/muestra/controllers/MuestraAPI.php?action=confirmar_recepcion';

function obtenerChecklist() {
  const filas = document.querySelectorAll('tbody tr');
  const checklist = [];
  filas.forEach((fila, idx) => {
    const texto = (fila.querySelector('td') ? fila.querySelector('td').textContent : '').trim();
    const cumple = fila.querySelector('input[type="radio"][name="check_' + idx + '"][value="1"]:checked') !== null;
    checklist.push({
      item: texto,
      cumple: cumple
    });
  });
  return checklist;
}

document.getElementById('btn-confirmar-recepcion').addEventListener('click', function () {
  const modalEl = document.getElementById('modal-confirmar-recepcion');
  const modal = new bootstrap.Modal(modalEl);
  modal.show();
});

document.getElementById('btn-guardar-confirmacion').addEventListener('click', function () {
  const pasa = document.querySelector('input[name="recepcion-pasa"]:checked').value === '1';
  const tipoServicio = (document.getElementById('recepcion-tipo-servicio').value || '').trim();
  const observacion = (document.getElementById('recepcion-observacion').value || '').trim();
  const checklist = obtenerChecklist();
  const totalNoCumple = checklist.filter(c => !c.cumple).length;

  if (tipoServicio !== 'Interno' && tipoServicio !== 'Externo') {
    alert('Seleccione un tipo de servicio válido.');
    return;
  }

  if (!pasa && observacion === '') {
    alert('Debe registrar la observación cuando la muestra no cumple.');
    return;
  }

  const payload = {
    id_muestra: idMuestra,
    pasa: pasa,
    tipo_servicio: tipoServicio,
    observacion: observacion,
    checklist: checklist,
    total_no_cumple: totalNoCumple
  };

  fetch(apiRecepcion, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(resp => resp.json())
  .then(data => {
    if (!data.success) {
      alert(data.message || 'No se pudo guardar la confirmación de recepción.');
      return;
    }
    window.location.href = '?module=laboratorio&action=muestra';
  })
  .catch(() => {
    alert('Error de red al guardar la confirmación de recepción.');
  });
});
</script>
