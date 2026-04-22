<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Reportes</li>
      </ol>
    </nav>

    <div class="row g-2 align-items-center mb-3">
      <div class="col">
        <h2 class="page-title">REPORTES DE LABORATORIO</h2>
        <div class="text-muted mt-1">Seleccione el tipo de reporte que desea generar o consultar.</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header">
            <h3 class="card-title mb-0">Generador de Reportes</h3>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Tipo de reporte</label>
              <select id="tipo-reporte" class="form-select">
                <option value="">Seleccione un tipo...</option>
                <option value="residuos">Residuos</option>
                <option value="muestras">Muestras</option>
                <option value="kardex">Kardex</option>
              </select>
            </div>

            <div id="bloque-opciones" class="d-none">
              <div class="mb-3">
                <label class="form-label">Opción de reporte</label>
                <select id="opcion-reporte" class="form-select">
                  <option value="">Seleccione una opción...</option>
                </select>
              </div>

              <div id="filtro-registro-residuo" class="mb-3 d-none">
                <label class="form-label">Reporte de residuos disponible</label>
                <select id="registro-residuo" class="form-select">
                  <option value="">Seleccione un registro...</option>
                </select>
              </div>

              <div id="bloque-muestra-cliente" class="d-none">
                <div class="mb-3">
                  <label class="form-label">Cliente</label>
                  <select id="cliente-muestra" class="form-select">
                    <option value="">Seleccione un cliente...</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Muestra finalizada del cliente</label>
                  <select id="muestra-cliente" class="form-select">
                    <option value="">Seleccione una muestra...</option>
                  </select>
                </div>
              </div>

              <div id="bloque-muestra-proyecto" class="d-none">
                <div class="mb-3">
                  <label class="form-label">Proyecto</label>
                  <select id="proyecto-muestra" class="form-select">
                    <option value="">Seleccione un proyecto...</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Muestra finalizada del proyecto</label>
                  <select id="muestra-proyecto" class="form-select">
                    <option value="">Seleccione una muestra...</option>
                  </select>
                </div>
              </div>

              <div id="filtros-kardex" class="row d-none">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Mes</label>
                  <select id="mes-kardex" class="form-select">
                    <option value="">Seleccione mes...</option>
                    <option value="1">Enero</option>
                    <option value="2">Febrero</option>
                    <option value="3">Marzo</option>
                    <option value="4">Abril</option>
                    <option value="5">Mayo</option>
                    <option value="6">Junio</option>
                    <option value="7">Julio</option>
                    <option value="8">Agosto</option>
                    <option value="9">Setiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Año</label>
                  <input type="number" id="anio-kardex" class="form-control" min="2020" max="2100" value="2026">
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <button id="btn-previsualizar" class="btn btn-primary" disabled>
                <i class="ti ti-eye me-2"></i>Previsualizar
              </button>
              <button id="btn-descargar-excel" class="btn btn-success d-none" disabled>
                <i class="ti ti-file-spreadsheet me-2"></i>Descargar Excel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="panel-preview" class="row mt-4 d-none">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Previsualización del Reporte</h3>
            <span id="preview-label" class="badge bg-primary-lt text-primary"></span>
          </div>
          <div class="card-body">
            <p id="preview-descripcion" class="text-muted small mb-3"></p>

            <div id="preview-web" class="d-none">
              <iframe id="preview-frame" title="Previsualización de reporte" style="width:100%; height:70vh; border:1px solid #e6e7e9; border-radius:8px;"></iframe>
            </div>

            <div id="preview-excel" class="d-none">
              <div id="preview-excel-wrap" class="table-responsive" style="max-height:70vh; overflow:auto; border:1px solid #e6e7e9; border-radius:8px; background:#fff; padding:8px;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .card.shadow-sm {
    border-radius: 10px;
  }

  #preview-web {
    background: #f3f4f6;
    border: 1px solid #e6e7e9;
    border-radius: 8px;
    padding: 10px;
    display: flex;
    justify-content: center;
  }

  #preview-frame {
    max-width: 1280px;
    background: #fff;
  }

  #preview-excel-wrap {
    background: #f3f4f6;
  }

  #preview-excel-wrap table {
    border-collapse: collapse;
    background: #fff;
  }

  #preview-excel-wrap td,
  #preview-excel-wrap th {
    vertical-align: top;
  }

  #preview-excel-wrap .excel-like {
    width: max-content;
    min-width: 100%;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
  (function () {
    const apiBase = 'modules/laboratorio/views/reportes_api.php';

    const tipoReporte = document.getElementById('tipo-reporte');
    const opcionReporte = document.getElementById('opcion-reporte');
    const bloqueOpciones = document.getElementById('bloque-opciones');
    const filtroRegistroResiduo = document.getElementById('filtro-registro-residuo');
    const registroResiduo = document.getElementById('registro-residuo');
    const bloqueMuestraCliente = document.getElementById('bloque-muestra-cliente');
    const clienteMuestra = document.getElementById('cliente-muestra');
    const muestraCliente = document.getElementById('muestra-cliente');
    const bloqueMuestraProyecto = document.getElementById('bloque-muestra-proyecto');
    const proyectoMuestra = document.getElementById('proyecto-muestra');
    const muestraProyecto = document.getElementById('muestra-proyecto');
    const filtrosKardex = document.getElementById('filtros-kardex');
    const btnPrevisualizar = document.getElementById('btn-previsualizar');
    const btnDescargarExcel = document.getElementById('btn-descargar-excel');
    const mesKardex = document.getElementById('mes-kardex');
    const anioKardex = document.getElementById('anio-kardex');
    const panelPreview = document.getElementById('panel-preview');
    const previewFrame = document.getElementById('preview-frame');
    const previewWeb = document.getElementById('preview-web');
    const previewExcel = document.getElementById('preview-excel');
    const previewExcelWrap = document.getElementById('preview-excel-wrap');
    const previewLabel = document.getElementById('preview-label');
    const previewDescripcion = document.getElementById('preview-descripcion');

    const opcionesPorTipo = {
      residuos: [
        { value: 'residuos_excel', label: 'Reporte de residuos (Excel)' },
        { value: 'residuos_informe', label: 'Informe de residuos (pantalla)' },
        { value: 'residuos_inventario', label: 'Inventario de residuos' }
      ],
      muestras: [
        { value: 'muestras_excel_cliente', label: 'Resultados de muestra por cliente (Excel)' },
        { value: 'muestras_excel_proyecto', label: 'Resultados de muestra por proyecto (Excel)' },
        { value: 'muestras_general', label: 'Reporte general de muestras (pantalla)' }
      ],
      kardex: [
        { value: 'kardex_mensual', label: 'Kardex mensual de reactivos' }
      ]
    };

    function popularSelect(select, items, placeholder) {
      select.innerHTML = '';
      const base = document.createElement('option');
      base.value = '';
      base.textContent = placeholder;
      select.appendChild(base);

      items.forEach(function (item) {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.label;
        select.appendChild(opt);
      });
    }

    async function getJson(url) {
      const res = await fetch(url, { credentials: 'same-origin' });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Error consultando datos');
      }
      return data;
    }

    async function cargarRegistrosResiduos() {
      const data = await getJson(apiBase + '?action=listar_registros_residuos');
      popularSelect(registroResiduo, data.data || [], 'Seleccione un registro...');
    }

    async function cargarClientesMuestras() {
      const data = await getJson(apiBase + '?action=listar_clientes_muestras');
      popularSelect(clienteMuestra, data.data || [], 'Seleccione un cliente...');
      popularSelect(muestraCliente, [], 'Seleccione una muestra...');
    }

    async function cargarMuestrasCliente(idCliente) {
      if (!idCliente) {
        popularSelect(muestraCliente, [], 'Seleccione una muestra...');
        return;
      }
      const data = await getJson(apiBase + '?action=listar_muestras_cliente&id_cliente=' + encodeURIComponent(idCliente));
      popularSelect(muestraCliente, data.data || [], 'Seleccione una muestra...');
    }

    async function cargarProyectosMuestras() {
      const data = await getJson(apiBase + '?action=listar_proyectos_muestras');
      popularSelect(proyectoMuestra, data.data || [], 'Seleccione un proyecto...');
      popularSelect(muestraProyecto, [], 'Seleccione una muestra...');
    }

    async function cargarMuestrasProyecto(idProyecto) {
      if (!idProyecto) {
        popularSelect(muestraProyecto, [], 'Seleccione una muestra...');
        return;
      }
      const data = await getJson(apiBase + '?action=listar_muestras_proyecto&id_proyecto=' + encodeURIComponent(idProyecto));
      popularSelect(muestraProyecto, data.data || [], 'Seleccione una muestra...');
    }

    function getMuestraSeleccionada() {
      if (opcionReporte.value === 'muestras_excel_cliente') {
        return muestraCliente.value;
      }
      if (opcionReporte.value === 'muestras_excel_proyecto') {
        return muestraProyecto.value;
      }
      return '';
    }

    function getTipoMuestraSeleccionada() {
      let select = null;
      if (opcionReporte.value === 'muestras_excel_cliente') {
        select = muestraCliente;
      } else if (opcionReporte.value === 'muestras_excel_proyecto') {
        select = muestraProyecto;
      }

      if (!select || !select.value) {
        return '';
      }

      const txt = (select.options[select.selectedIndex] && select.options[select.selectedIndex].textContent
        ? select.options[select.selectedIndex].textContent
        : '').toLowerCase();

      if (txt.indexOf('agua') !== -1) return 'agua';
      if (txt.indexOf('suelo') !== -1) return 'suelo';
      return '';
    }

    function resetOpciones() {
      opcionReporte.innerHTML = '<option value="">Seleccione una opción...</option>';
      filtroRegistroResiduo.classList.add('d-none');
      bloqueMuestraCliente.classList.add('d-none');
      bloqueMuestraProyecto.classList.add('d-none');
      filtrosKardex.classList.add('d-none');
      btnPrevisualizar.disabled = true;
      btnDescargarExcel.classList.add('d-none');
      btnDescargarExcel.disabled = true;
      panelPreview.classList.add('d-none');
      previewFrame.src = 'about:blank';
      previewExcelWrap.innerHTML = '';
      previewWeb.classList.add('d-none');
      previewExcel.classList.add('d-none');
    }

    function actualizarEstadoBoton() {
      const tieneOpcion = opcionReporte.value !== '';
      if (!tieneOpcion) {
        btnPrevisualizar.disabled = true;
        btnDescargarExcel.disabled = true;
        return;
      }

      if (opcionReporte.value === 'residuos_excel') {
        const ok = !!registroResiduo.value;
        btnPrevisualizar.disabled = !ok;
        btnDescargarExcel.disabled = !ok;
        return;
      }

      if (opcionReporte.value === 'muestras_excel_cliente' || opcionReporte.value === 'muestras_excel_proyecto') {
        const ok = !!getMuestraSeleccionada();
        btnPrevisualizar.disabled = !ok;
        btnDescargarExcel.disabled = !ok;
        return;
      }

      if (tipoReporte.value === 'kardex') {
        btnPrevisualizar.disabled = !(mesKardex.value && anioKardex.value);
        btnDescargarExcel.disabled = true;
        return;
      }

      btnPrevisualizar.disabled = false;
      btnDescargarExcel.disabled = true;
    }

    function construirDestino() {
      const tipo = tipoReporte.value;
      const opcion = opcionReporte.value;
      let destino = '';

      if (tipo === 'residuos' && opcion === 'residuos_excel') {
        destino = 'modules/laboratorio/residuo/controllers/ExportarReporteAPI.php?id_registro=' + encodeURIComponent(registroResiduo.value || '');
      }
      if (tipo === 'residuos' && opcion === 'residuos_informe') {
        destino = '?module=laboratorio&action=residuo&view=informe_residuos';
      }
      if (tipo === 'residuos' && opcion === 'residuos_inventario') {
        destino = '?module=laboratorio&action=residuo';
      }
      if (tipo === 'muestras' && opcion === 'muestras_general') {
        destino = '?module=laboratorio&action=muestra';
      }
      if (tipo === 'muestras' && (opcion === 'muestras_excel_cliente' || opcion === 'muestras_excel_proyecto')) {
        destino = 'modules/laboratorio/muestra/controllers/ExportarResultadosPasados.php?id_muestra=' + encodeURIComponent(getMuestraSeleccionada());
        const tipoMuestra = getTipoMuestraSeleccionada();
        if (tipoMuestra) {
          destino += '&tipo_muestra=' + encodeURIComponent(tipoMuestra);
        }
      }
      if (tipo === 'kardex' && opcion === 'kardex_mensual') {
        const mes = encodeURIComponent(mesKardex.value);
        const anio = encodeURIComponent(anioKardex.value);
        destino = '?module=laboratorio&action=reactivo&subaction=kardex&mes=' + mes + '&anio=' + anio;
      }

      return destino;
    }

    function descripcionPreview(tipo, opcion) {
      if (tipo === 'residuos' && opcion === 'residuos_excel') {
        return 'Previsualización del Excel del registro de residuos seleccionado.';
      }
      if (tipo === 'residuos' && opcion === 'residuos_informe') {
        return 'Previsualizando el formulario/listado de informes de residuos para preparar la generación mensual.';
      }
      if (tipo === 'residuos' && opcion === 'residuos_inventario') {
        return 'Previsualizando el inventario general de residuos y normativas asociadas.';
      }
      if (tipo === 'muestras' && opcion === 'muestras_general') {
        return 'Previsualizando el módulo general de muestras con los filtros seleccionados.';
      }
      if (tipo === 'muestras' && (opcion === 'muestras_excel_cliente' || opcion === 'muestras_excel_proyecto')) {
        return 'Previsualizacion fiel de la plantilla Excel de la muestra seleccionada.';
      }
      if (tipo === 'kardex' && opcion === 'kardex_mensual') {
        return 'Previsualizando el kardex mensual según mes y año seleccionados.';
      }
      return '';
    }

    function normalizeSheetRangeForPreview(sheet) {
      if (!sheet || !sheet['!ref']) {
        return;
      }

      let minR = Number.MAX_SAFE_INTEGER;
      let minC = Number.MAX_SAFE_INTEGER;
      let maxR = -1;
      let maxC = -1;

      Object.keys(sheet).forEach(function (addr) {
        if (!addr || addr[0] === '!') {
          return;
        }
        const cell = sheet[addr];
        const hasValue = cell && cell.v !== undefined && cell.v !== null && String(cell.v).trim() !== '';
        if (!hasValue) {
          return;
        }
        const p = XLSX.utils.decode_cell(addr);
        minR = Math.min(minR, p.r);
        minC = Math.min(minC, p.c);
        maxR = Math.max(maxR, p.r);
        maxC = Math.max(maxC, p.c);
      });

      // Si no hay celdas con valor, dejar el rango original
      if (maxR < 0 || maxC < 0) {
        return;
      }

      // Expandir un poco para no cortar bordes de formato cercano
      maxR += 2;
      maxC += 2;

      const merges = Array.isArray(sheet['!merges']) ? sheet['!merges'] : [];
      merges.forEach(function (m) {
        if (!m || !m.s || !m.e) {
          return;
        }
        minR = Math.min(minR, m.s.r);
        minC = Math.min(minC, m.s.c);
        maxR = Math.max(maxR, m.e.r);
        maxC = Math.max(maxC, m.e.c);
      });

      if (minR < 0) minR = 0;
      if (minC < 0) minC = 0;

      sheet['!ref'] = XLSX.utils.encode_range({
        s: { r: minR, c: minC },
        e: { r: maxR, c: maxC }
      });
    }

    function renderExcelFromWorkbook(workbook) {
      const firstSheetName = workbook.SheetNames[0];
      const sheet = workbook.Sheets[firstSheetName];
      normalizeSheetRangeForPreview(sheet);
      const html = XLSX.utils.sheet_to_html(sheet, { id: 'excel-preview-table', editable: false });
      previewExcelWrap.innerHTML = html;

      const table = previewExcelWrap.querySelector('table');
      if (table) {
        table.classList.add('excel-like');
      }
    }

    async function previsualizarExcel(url, postData) {
      const options = {
        method: postData ? 'POST' : 'GET',
        credentials: 'same-origin'
      };

      if (postData) {
        const formData = new URLSearchParams();
        Object.keys(postData).forEach(function (k) {
          formData.append(k, postData[k]);
        });
        options.headers = { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' };
        options.body = formData.toString();
      }

      const res = await fetch(url, options);
      if (!res.ok) {
        throw new Error('No se pudo generar el Excel (' + res.status + ')');
      }

      const buffer = await res.arrayBuffer();
      const workbook = XLSX.read(buffer, { type: 'array', cellStyles: true, cellNF: true, cellDates: true });
      renderExcelFromWorkbook(workbook);
    }

    async function construirWorkbookResiduosDesdeApi(idRegistro) {
      const data = await getJson(apiBase + '?action=obtener_residuo_reporte_data&id_registro=' + encodeURIComponent(idRegistro));
      const payload = data.data || {};
      const meta = payload.meta || {};
      const headers = payload.headers || [];
      const rows = payload.rows || [];

      const aoa = [];
      aoa.push(['REGISTRO DE RESIDUOS SÓLIDOS']);
      aoa.push(['Código SST', meta.codigo_sst || 'SST-16']);
      aoa.push(['Ubicación', meta.ubicacion || '-']);
      aoa.push(['Periodo', String(meta.mes || '-') + '/' + String(meta.anio || '-')]);
      aoa.push([]);
      aoa.push(headers);
      rows.forEach(function (r) { aoa.push(r); });

      const ws = XLSX.utils.aoa_to_sheet(aoa);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'RESIDUOS');
      return { workbook: wb, meta: meta };
    }

    tipoReporte.addEventListener('change', function () {
      const tipo = this.value;
      resetOpciones();

      if (!tipo || !opcionesPorTipo[tipo]) {
        bloqueOpciones.classList.add('d-none');
        return;
      }

      bloqueOpciones.classList.remove('d-none');

      opcionesPorTipo[tipo].forEach(function (op) {
        const option = document.createElement('option');
        option.value = op.value;
        option.textContent = op.label;
        opcionReporte.appendChild(option);
      });

      if (tipo === 'kardex') {
        filtrosKardex.classList.remove('d-none');
      }

      if (tipo === 'muestras') {
        // Se habilitan en el cambio de opción
      }
    });

    opcionReporte.addEventListener('change', actualizarEstadoBoton);
    registroResiduo.addEventListener('change', actualizarEstadoBoton);
    clienteMuestra.addEventListener('change', function () {
      cargarMuestrasCliente(this.value).then(actualizarEstadoBoton).catch(function () {
        popularSelect(muestraCliente, [], 'Seleccione una muestra...');
        actualizarEstadoBoton();
      });
    });
    proyectoMuestra.addEventListener('change', function () {
      cargarMuestrasProyecto(this.value).then(actualizarEstadoBoton).catch(function () {
        popularSelect(muestraProyecto, [], 'Seleccione una muestra...');
        actualizarEstadoBoton();
      });
    });
    muestraCliente.addEventListener('change', actualizarEstadoBoton);
    muestraProyecto.addEventListener('change', actualizarEstadoBoton);
    mesKardex.addEventListener('change', actualizarEstadoBoton);
    anioKardex.addEventListener('input', actualizarEstadoBoton);

    opcionReporte.addEventListener('change', async function () {
      filtroRegistroResiduo.classList.add('d-none');
      bloqueMuestraCliente.classList.add('d-none');
      bloqueMuestraProyecto.classList.add('d-none');
      btnDescargarExcel.classList.add('d-none');
      btnDescargarExcel.disabled = true;

      if (this.value === 'residuos_excel') {
        filtroRegistroResiduo.classList.remove('d-none');
        btnDescargarExcel.classList.remove('d-none');
        try {
          await cargarRegistrosResiduos();
        } catch (e) {
          popularSelect(registroResiduo, [], 'Sin registros disponibles');
        }
      }

      if (this.value === 'muestras_excel_cliente') {
        bloqueMuestraCliente.classList.remove('d-none');
        btnDescargarExcel.classList.remove('d-none');
        try {
          await cargarClientesMuestras();
        } catch (e) {
          popularSelect(clienteMuestra, [], 'Sin clientes disponibles');
          popularSelect(muestraCliente, [], 'Seleccione una muestra...');
        }
      }

      if (this.value === 'muestras_excel_proyecto') {
        bloqueMuestraProyecto.classList.remove('d-none');
        btnDescargarExcel.classList.remove('d-none');
        try {
          await cargarProyectosMuestras();
        } catch (e) {
          popularSelect(proyectoMuestra, [], 'Sin proyectos disponibles');
          popularSelect(muestraProyecto, [], 'Seleccione una muestra...');
        }
      }

      actualizarEstadoBoton();
    });

    btnPrevisualizar.addEventListener('click', async function () {
      const tipo = tipoReporte.value;
      const opcion = opcionReporte.value;
      const destino = construirDestino();

      if (destino) {
        panelPreview.classList.remove('d-none');
        previewLabel.textContent = opcionReporte.options[opcionReporte.selectedIndex].textContent;
        previewDescripcion.textContent = descripcionPreview(tipo, opcion);

        try {
          if (opcion === 'muestras_excel_cliente' || opcion === 'muestras_excel_proyecto' || opcion === 'residuos_excel') {
            previewExcel.classList.add('d-none');
            previewWeb.classList.remove('d-none');
            const sep = destino.indexOf('?') === -1 ? '?' : '&';
            previewFrame.src = destino + sep + 'preview_html=1&_pv=' + Date.now();
            return;
          }

          previewExcel.classList.add('d-none');
          previewWeb.classList.remove('d-none');
          previewFrame.src = destino;
        } catch (err) {
          previewExcel.classList.remove('d-none');
          previewWeb.classList.add('d-none');
          previewExcelWrap.innerHTML = '<div class="p-3 text-danger">Error en previsualizacion: ' + String(err.message || err) + '</div>';
        }
      }
    });

    btnDescargarExcel.addEventListener('click', function () {
      const opcion = opcionReporte.value;

      if (opcion === 'residuos_excel') {
        if (!registroResiduo.value) return;
        window.open('modules/laboratorio/residuo/controllers/ExportarReporteAPI.php?id_registro=' + encodeURIComponent(registroResiduo.value), '_blank');
        return;
      }

      if (opcion === 'muestras_excel_cliente' || opcion === 'muestras_excel_proyecto') {
        const idMuestra = getMuestraSeleccionada();
        if (!idMuestra) return;
        let url = 'modules/laboratorio/muestra/controllers/ExportarResultadosPasados.php?id_muestra=' + encodeURIComponent(idMuestra);
        const tipoMuestra = getTipoMuestraSeleccionada();
        if (tipoMuestra) {
          url += '&tipo_muestra=' + encodeURIComponent(tipoMuestra);
        }
        window.open(url, '_blank');
      }
    });
  })();
</script>
