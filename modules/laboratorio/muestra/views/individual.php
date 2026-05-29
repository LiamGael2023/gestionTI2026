<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item"><a href="?module=laboratorio&action=muestra">Muestras</a></li>
        <li class="breadcrumb-item active" aria-current="page">Creacion Individual</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">CREACION INDIVIDUAL DE MUESTRA COMUN</h2>
        <div class="text-muted mt-1">Registre una muestra común para que siga el flujo normal: recepcion, analisis y validacion.</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="alert alert-info" role="alert">
      Defina si la muestra es <strong>Interna</strong> o <strong>Externa</strong>. Esta opción crea una muestra normal, sin punto de toma ni ubicacion de toma, en estado <strong>Pendiente</strong>.
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Formulario de muestra individual</h3>
      </div>
      <div class="card-body">
        <div id="alerta-error" class="alert alert-danger d-none"></div>

        <form id="form-muestra-individual" class="row g-3" onsubmit="return false;">
          <div class="col-md-4">
            <label class="form-label">Tipo de servicio <span class="text-danger">*</span></label>
            <select id="tipo_servicio" class="form-select" required>
              <option value="Interno">Interno</option>
              <option value="Externo">Externo</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Agricultor / Cliente <span class="text-danger">*</span></label>
            <select id="id_cliente" class="form-select" required>
              <option value="">Seleccione...</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Valle <span class="text-danger">*</span></label>
            <select id="valle" class="form-select" required>
              <option value="">Seleccione...</option>
            </select>
            <input type="text" class="form-control mt-2" id="valle_otro" placeholder="Especificar valle" style="display:none;">
          </div>

          <div class="col-md-4">
            <label class="form-label">Fecha de toma <span class="text-danger">*</span></label>
            <input id="fecha_toma" type="date" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Eje X</label>
            <input id="eje_x" type="text" class="form-control" placeholder="Opcional">
          </div>

          <div class="col-md-6">
            <label class="form-label">Eje Y</label>
            <input id="eje_y" type="text" class="form-control" placeholder="Opcional">
          </div>

          <div class="col-md-6">
            <label class="form-label">Producto / paquete <span class="text-danger">*</span></label>
            <select id="id_producto_venta" class="form-select" required>
              <option value="">Seleccione...</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Observacion</label>
            <input id="observacion" type="text" class="form-control" placeholder="Opcional">
          </div>

          <div class="col-md-12">
            <label class="form-label d-block mb-2">Tipo de muestra <span class="text-danger">*</span></label>
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
              <label class="form-label">Uso de agua</label>
              <select id="uso_agua" class="form-select">
                <option value="">Seleccionar</option>
                <option value="Consumo Humano">Consumo Humano</option>
                <option value="Riego">Riego</option>
                <option value="Industrial">Industrial</option>
                <option value="Otro">Otro</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Fuente de agua</label>
              <select id="fuente_agua" class="form-select">
                <option value="">Seleccionar</option>
                <option value="Rio">Rio</option>
                <option value="Pozo">Pozo</option>
                <option value="Canal">Canal</option>
                <option value="Reservorio">Reservorio</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nivel de agua</label>
              <select id="nivel_agua" class="form-select">
                <option value="">Seleccionar</option>
                <option value="Superficial">Superficial</option>
                <option value="Subterranea">Subterranea</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cantidad</label>
              <input id="cantidad_agua" type="text" class="form-control" value="1 Litro">
            </div>
          </div>

          <div id="bloque-suelo" class="row g-3 d-none">
            <div class="col-md-3">
              <label class="form-label">Fuente de riego</label>
              <input id="fuente_riego" type="text" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-md-3">
              <label class="form-label">Profundidad</label>
              <input id="profundidad" type="text" class="form-control" placeholder="Ej: 0-30 cm">
            </div>
            <div class="col-md-3">
              <label class="form-label">Nro submuestras</label>
              <input id="numero_submuestras" type="number" min="1" class="form-control" placeholder="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Cantidad</label>
              <input id="cantidad_suelo" type="text" class="form-control" value="1 Kg">
            </div>
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="button" class="btn btn-primary" id="btn-guardar-muestra-individual">
              <i class="ti ti-device-floppy me-1"></i> Guardar muestra individual
            </button>
            <a href="?module=laboratorio&action=muestra" class="btn btn-outline-secondary">Volver</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const apiUrl = 'modules/laboratorio/muestra/controllers/MuestraAPI.php';

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

  const cargarCatalogos = function () {
    fetch(apiUrl + '?action=obtener_catalogos_por_defecto', { method: 'POST' })
      .then(function (resp) { return resp.json(); })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'No se pudieron cargar catalogos');
        }

        const selectCliente = document.getElementById('id_cliente');
        const selectValle = document.getElementById('valle');
        const selectProducto = document.getElementById('id_producto_venta');

        (data.agricultores || []).forEach(function (item) {
          const option = document.createElement('option');
          option.value = item.id;
          option.textContent = item.nombre;
          selectCliente.appendChild(option);
        });

        (data.valles || []).forEach(function (item) {
          const option = document.createElement('option');
          option.value = item;
          option.textContent = item;
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
          selectProducto.appendChild(option);
        });
      })
      .catch(function (err) {
        mostrarError(err.message || 'Error de red al cargar catalogos.');
      });
  };

  const obtenerPayload = function () {
    return {
      tipo_servicio: document.getElementById('tipo_servicio').value,
      id_cliente: document.getElementById('id_cliente').value,
      valle: (document.getElementById('valle').value === 'Otros'
        ? document.getElementById('valle_otro').value.trim()
        : document.getElementById('valle').value),
      fecha_toma: document.getElementById('fecha_toma').value,
      eje_x: document.getElementById('eje_x').value.trim(),
      eje_y: document.getElementById('eje_y').value.trim(),
      tipo_muestra: document.querySelector('input[name="tipo_muestra"]:checked').value,
      id_producto_venta: document.getElementById('id_producto_venta').value,
      observacion: document.getElementById('observacion').value.trim(),
      uso_agua: document.getElementById('uso_agua').value,
      fuente_agua: document.getElementById('fuente_agua').value,
      nivel_agua: document.getElementById('nivel_agua').value,
      cantidad_agua: document.getElementById('cantidad_agua').value.trim(),
      fuente_riego: document.getElementById('fuente_riego').value.trim(),
      profundidad: document.getElementById('profundidad').value.trim(),
      numero_submuestras: document.getElementById('numero_submuestras').value,
      cantidad_suelo: document.getElementById('cantidad_suelo').value.trim()
    };
  };

  const validar = function (payload) {
    if (!payload.id_cliente || !payload.valle || !payload.fecha_toma || !payload.id_producto_venta) {
      return 'Complete todos los campos obligatorios.';
    }
    if (payload.tipo_servicio !== 'Interno' && payload.tipo_servicio !== 'Externo') {
      return 'Seleccione un tipo de servicio valido.';
    }
    return '';
  };

  const guardar = function () {
    limpiarError();
    const payload = obtenerPayload();
    const error = validar(payload);
    if (error) {
      Swal.fire('Validacion', error, 'warning');
      return;
    }

    const btn = document.getElementById('btn-guardar-muestra-individual');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader me-1"></i> Guardando...';

    fetch(apiUrl + '?action=guardar_muestra_individual', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (resp) { return resp.json(); })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'No se pudo guardar la muestra individual.');
        }

        Swal.fire('Exito', 'Muestra individual creada correctamente. ID: ' + (data.id_muestra || 0), 'success')
          .then(function () {
            window.location.href = '?module=laboratorio&action=muestra';
          });
      })
      .catch(function (err) {
        Swal.fire('Error', err.message || 'Error de red al guardar muestra individual.', 'error');
      })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Guardar muestra individual';
      });
  };

  document.addEventListener('DOMContentLoaded', function () {
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_toma').value = hoy;

    document.querySelectorAll('input[name="tipo_muestra"]').forEach(function (radio) {
      radio.addEventListener('change', toggleTipoMuestra);
    });

    document.getElementById('btn-guardar-muestra-individual').addEventListener('click', guardar);

    toggleTipoMuestra();
    cargarCatalogos();
  });
})();
</script>
