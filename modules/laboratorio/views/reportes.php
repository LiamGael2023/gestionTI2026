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
            <!-- PASO 1: Tipo de reporte -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Tipo de reporte</label>
              <select id="tipo-reporte" class="form-select">
                <option value="">Seleccione un tipo...</option>
                <option value="residuos">Residuos</option>
                <option value="muestra">Muestra</option>
                <option value="kardex">Kardex de Reactivos</option>
              </select>
            </div>

            <!-- PASO 2a: Si es Muestra → Origen (Proyecto / Cliente) -->
            <div id="bloque-origen-muestra" class="mb-3 d-none">
              <label class="form-label fw-semibold">Origen de la muestra</label>
              <select id="origen-muestra" class="form-select">
                <option value="">Seleccione el origen...</option>
                <option value="proyecto">Proyecto</option>
                <option value="cliente">Cliente</option>
              </select>
            </div>

            <!-- PASO 3a: Si origen = Proyecto → Tipo de proyecto -->
            <div id="bloque-tipo-proyecto" class="mb-3 d-none">
              <label class="form-label fw-semibold">Tipo de proyecto</label>
              <select id="tipo-proyecto" class="form-select">
                <option value="">Seleccione el tipo de proyecto...</option>
                <option value="monitoreo">Monitoreo</option>
                <option value="calidad_agua">Calidad de Agua</option>
              </select>
            </div>

            <!-- PASO 4a: Proyecto de Monitoreo -->
            <div id="bloque-proyecto-monitoreo" class="mb-3 d-none">
              <label class="form-label fw-semibold">Proyecto de Monitoreo</label>
              <select id="proyecto-monitoreo" class="form-select">
                <option value="">Seleccione un proyecto...</option>
              </select>
            </div>

            <!-- PASO 4b: Proyecto de Calidad de Agua -->
            <div id="bloque-proyecto-calidad" class="mb-3 d-none">
              <label class="form-label fw-semibold">Proyecto de Calidad de Agua</label>
              <select id="proyecto-calidad" class="form-select">
                <option value="">Seleccione un proyecto...</option>
              </select>
            </div>

            <!-- PASO 3b: Si origen = Cliente -->
            <div id="bloque-muestra-cliente" class="d-none">
              <div class="mb-3">
                <label class="form-label fw-semibold">Cliente</label>
                <select id="cliente-muestra" class="form-select">
                  <option value="">Seleccione un cliente...</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Muestra finalizada</label>
                <select id="muestra-cliente" class="form-select">
                  <option value="">Seleccione una muestra...</option>
                </select>
              </div>
            </div>

            <!-- PASO 2b: Si es Residuos -->
            <div id="filtro-registro-residuo" class="mb-3 d-none">
              <label class="form-label fw-semibold">Registro de residuos</label>
              <select id="registro-residuo" class="form-select">
                <option value="">Seleccione un registro...</option>
              </select>
            </div>

            <!-- PASO 2c: Si es Kardex -->
            <div id="filtros-kardex" class="row d-none">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Mes</label>
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
                <label class="form-label fw-semibold">Año</label>
                <input type="number" id="anio-kardex" class="form-control" min="2020" max="2100" value="2026">
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-2">
              <button id="btn-previsualizar" class="btn btn-primary" disabled>
                <i class="ti ti-eye me-2"></i>Previsualizar Excel
              </button>
              <button id="btn-descargar-excel" class="btn btn-success" disabled>
                <i class="ti ti-file-spreadsheet me-2"></i>Exportar Excel
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
    font-family: Calibri, Arial, sans-serif;
    font-size: 11pt;
  }

  #preview-excel-wrap td,
  #preview-excel-wrap th {
    vertical-align: middle;
    white-space: nowrap;
    padding: 3px 6px;
    border: 1px solid #d0d0d0;
    min-width: 40px;
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

    // Selectores del formulario
    const tipoReporte           = document.getElementById('tipo-reporte');
    const bloqueOrigenMuestra   = document.getElementById('bloque-origen-muestra');
    const origenMuestra         = document.getElementById('origen-muestra');
    const bloqueTipoProyecto    = document.getElementById('bloque-tipo-proyecto');
    const tipoProyecto          = document.getElementById('tipo-proyecto');
    const bloqueProyectoMonitoreo = document.getElementById('bloque-proyecto-monitoreo');
    const proyectoMonitoreo     = document.getElementById('proyecto-monitoreo');
    const bloqueProyectoCalidad = document.getElementById('bloque-proyecto-calidad');
    const proyectoCalidad       = document.getElementById('proyecto-calidad');
    const bloqueMuestraCliente  = document.getElementById('bloque-muestra-cliente');
    const clienteMuestra        = document.getElementById('cliente-muestra');
    const muestraCliente        = document.getElementById('muestra-cliente');
    const filtroRegistroResiduo = document.getElementById('filtro-registro-residuo');
    const registroResiduo       = document.getElementById('registro-residuo');
    const filtrosKardex         = document.getElementById('filtros-kardex');
    const mesKardex             = document.getElementById('mes-kardex');
    const anioKardex            = document.getElementById('anio-kardex');
    const btnPrevisualizar      = document.getElementById('btn-previsualizar');
    const btnDescargarExcel     = document.getElementById('btn-descargar-excel');
    const panelPreview          = document.getElementById('panel-preview');
    const previewFrame          = document.getElementById('preview-frame');
    const previewWeb            = document.getElementById('preview-web');
    const previewExcel          = document.getElementById('preview-excel');
    const previewExcelWrap      = document.getElementById('preview-excel-wrap');
    const previewLabel          = document.getElementById('preview-label');
    const previewDescripcion    = document.getElementById('preview-descripcion');

    // ── helpers ─────────────────────────────────────────────────────────────
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
      if (!res.ok || !data.success) throw new Error(data.message || 'Error consultando datos');
      return data;
    }

    function ocultarTodo() {
      bloqueOrigenMuestra.classList.add('d-none');
      bloqueTipoProyecto.classList.add('d-none');
      bloqueProyectoMonitoreo.classList.add('d-none');
      bloqueProyectoCalidad.classList.add('d-none');
      bloqueMuestraCliente.classList.add('d-none');
      filtroRegistroResiduo.classList.add('d-none');
      filtrosKardex.classList.add('d-none');
      origenMuestra.value = '';
      tipoProyecto.value = '';
      btnPrevisualizar.disabled = true;
      btnDescargarExcel.disabled = true;
      panelPreview.classList.add('d-none');
      previewExcelWrap.innerHTML = '';
      previewWeb.classList.add('d-none');
      previewExcel.classList.add('d-none');
    }

    // ── estado de botones ────────────────────────────────────────────────────
    function actualizarBotones() {
      const tipo   = tipoReporte.value;
      const origen = origenMuestra.value;
      const tproy  = tipoProyecto.value;
      let ok = false;

      if (tipo === 'residuos')       ok = !!registroResiduo.value;
      if (tipo === 'kardex')         ok = !!(mesKardex.value && anioKardex.value);
      if (tipo === 'muestra') {
        if (origen === 'cliente')    ok = !!muestraCliente.value;
        if (origen === 'proyecto') {
          if (tproy === 'monitoreo')    ok = !!proyectoMonitoreo.value;
          if (tproy === 'calidad_agua') ok = !!proyectoCalidad.value;
        }
      }

      btnPrevisualizar.disabled  = !ok;
      btnDescargarExcel.disabled = !ok;
    }

    // ── URL de exportación ───────────────────────────────────────────────────
    function construirUrlExcel() {
      const tipo   = tipoReporte.value;
      const origen = origenMuestra.value;
      const tproy  = tipoProyecto.value;

      if (tipo === 'residuos') {
        return 'modules/laboratorio/residuo/controllers/ExportarReporteAPI.php?id_registro=' + encodeURIComponent(registroResiduo.value);
      }
      if (tipo === 'kardex') {
        return 'modules/laboratorio/reactivo/controllers/ExportarKardex.php?mes=' + encodeURIComponent(mesKardex.value) + '&anio=' + encodeURIComponent(anioKardex.value);
      }
      if (tipo === 'muestra') {
        if (origen === 'cliente') {
          const idMuestra = muestraCliente.value;
          const txt = ((muestraCliente.options[muestraCliente.selectedIndex] || {}).textContent || '').toLowerCase();
          const tipoM = txt.indexOf('agua') !== -1 ? 'agua' : (txt.indexOf('suelo') !== -1 ? 'suelo' : '');
          let url = 'modules/laboratorio/muestra/controllers/ExportarResultadosPasados.php?id_muestra=' + encodeURIComponent(idMuestra);
          if (tipoM) url += '&tipo_muestra=' + encodeURIComponent(tipoM);
          return url;
        }
        if (origen === 'proyecto') {
          if (tproy === 'monitoreo') {
            return 'modules/laboratorio/muestra/controllers/ExportarProyectoMonitoreo.php?id_proyecto=' + encodeURIComponent(proyectoMonitoreo.value);
          }
          if (tproy === 'calidad_agua') {
            return 'modules/laboratorio/muestra/controllers/ExportarCalidadAgua.php?id_proyecto=' + encodeURIComponent(proyectoCalidad.value);
          }
        }
      }
      return '';
    }

    // ── eventos: PASO 1 ──────────────────────────────────────────────────────
    tipoReporte.addEventListener('change', function () {
      ocultarTodo();
      const tipo = this.value;
      if (!tipo) return;

      if (tipo === 'residuos') {
        filtroRegistroResiduo.classList.remove('d-none');
        getJson(apiBase + '?action=listar_registros_residuos')
          .then(function (d) { popularSelect(registroResiduo, d.data || [], 'Seleccione un registro...'); })
          .catch(function ()  { popularSelect(registroResiduo, [], 'Sin registros disponibles'); });
      }
      if (tipo === 'muestra') {
        bloqueOrigenMuestra.classList.remove('d-none');
      }
      if (tipo === 'kardex') {
        filtrosKardex.classList.remove('d-none');
      }
    });

    // ── eventos: PASO 2 (origen muestra) ─────────────────────────────────────
    origenMuestra.addEventListener('change', function () {
      // Ocultar subbloques de muestra
      bloqueTipoProyecto.classList.add('d-none');
      bloqueProyectoMonitoreo.classList.add('d-none');
      bloqueProyectoCalidad.classList.add('d-none');
      bloqueMuestraCliente.classList.add('d-none');
      tipoProyecto.value = '';
      btnPrevisualizar.disabled = true;
      btnDescargarExcel.disabled = true;

      if (this.value === 'proyecto') {
        bloqueTipoProyecto.classList.remove('d-none');
      }
      if (this.value === 'cliente') {
        bloqueMuestraCliente.classList.remove('d-none');
        getJson(apiBase + '?action=listar_clientes_muestras')
          .then(function (d) {
            popularSelect(clienteMuestra, d.data || [], 'Seleccione un cliente...');
            popularSelect(muestraCliente, [], 'Seleccione una muestra...');
          })
          .catch(function () {
            popularSelect(clienteMuestra, [], 'Sin clientes disponibles');
            popularSelect(muestraCliente, [], 'Seleccione una muestra...');
          });
      }
    });

    // ── eventos: PASO 3 (tipo de proyecto) ───────────────────────────────────
    tipoProyecto.addEventListener('change', function () {
      bloqueProyectoMonitoreo.classList.add('d-none');
      bloqueProyectoCalidad.classList.add('d-none');
      btnPrevisualizar.disabled = true;
      btnDescargarExcel.disabled = true;

      if (this.value === 'monitoreo') {
        bloqueProyectoMonitoreo.classList.remove('d-none');
        getJson(apiBase + '?action=listar_proyectos_monitoreo')
          .then(function (d) { popularSelect(proyectoMonitoreo, d.data || [], 'Seleccione un proyecto...'); })
          .catch(function ()  { popularSelect(proyectoMonitoreo, [], 'Sin proyectos disponibles'); });
      }
      if (this.value === 'calidad_agua') {
        bloqueProyectoCalidad.classList.remove('d-none');
        getJson(apiBase + '?action=listar_proyectos_calidad_agua')
          .then(function (d) { popularSelect(proyectoCalidad, d.data || [], 'Seleccione un proyecto...'); })
          .catch(function ()  { popularSelect(proyectoCalidad, [], 'Sin proyectos disponibles'); });
      }
    });

    // ── eventos: selectores terminales ───────────────────────────────────────
    registroResiduo.addEventListener('change', actualizarBotones);
    mesKardex.addEventListener('change', actualizarBotones);
    anioKardex.addEventListener('input', actualizarBotones);
    proyectoMonitoreo.addEventListener('change', actualizarBotones);
    proyectoCalidad.addEventListener('change', actualizarBotones);
    muestraCliente.addEventListener('change', actualizarBotones);
    clienteMuestra.addEventListener('change', function () {
      popularSelect(muestraCliente, [], 'Seleccione una muestra...');
      btnPrevisualizar.disabled = true;
      btnDescargarExcel.disabled = true;
      if (!this.value) return;
      getJson(apiBase + '?action=listar_muestras_cliente&id_cliente=' + encodeURIComponent(this.value))
        .then(function (d) { popularSelect(muestraCliente, d.data || [], 'Seleccione una muestra...'); })
        .catch(function ()  { popularSelect(muestraCliente, [], 'Sin muestras disponibles'); });
    });

    // ── renderer XLSX con estilos ─────────────────────────────────────────────
    function argbToHex(argb) {
      if (!argb) return null;
      var s = String(argb).replace(/^#/, '');
      // ARGB: primeros 2 dígitos son alpha → ignorar
      if (s.length === 8) s = s.substring(2);
      if (s.length === 6) return '#' + s;
      return null;
    }

    function borderCss(borderObj) {
      if (!borderObj || !borderObj.style) return null;
      var w = borderObj.style === 'thin' ? '1px' : borderObj.style === 'medium' ? '2px' : borderObj.style === 'thick' ? '3px' : '1px';
      var c = argbToHex(borderObj.color && borderObj.color.rgb) || '#000';
      return w + ' solid ' + c;
    }

    function sheetToStyledHtml(sheet) {
      if (!sheet || !sheet['!ref']) return '<p class="text-muted p-3">Hoja vacía</p>';
      var range  = XLSX.utils.decode_range(sheet['!ref']);
      var merges = sheet['!merges'] || [];
      var cols   = sheet['!cols']   || [];

      // Mapa de merges
      var mergeMap = {};
      var skipMap  = {};
      merges.forEach(function(m) {
        if (!m || !m.s || !m.e) return;
        var rs = m.e.r - m.s.r + 1;
        var cs = m.e.c - m.s.c + 1;
        mergeMap[m.s.r + ',' + m.s.c] = { rs: rs, cs: cs };
        for (var r = m.s.r; r <= m.e.r; r++) {
          for (var c = m.s.c; c <= m.e.c; c++) {
            if (r === m.s.r && c === m.s.c) continue;
            skipMap[r + ',' + c] = true;
          }
        }
      });

      var html = '<table class="excel-like">';

      // colgroup para anchos
      html += '<colgroup>';
      for (var ci = range.s.c; ci <= range.e.c; ci++) {
        var cinfo = cols[ci];
        var wpx = cinfo && cinfo.wpx ? cinfo.wpx : (cinfo && cinfo.wch ? Math.round(cinfo.wch * 7) : 64);
        html += '<col style="width:' + wpx + 'px">';
      }
      html += '</colgroup>';

      for (var ri = range.s.r; ri <= range.e.r; ri++) {
        var rowInfo = (sheet['!rows'] || [])[ri];
        var rh = rowInfo && rowInfo.hpx ? rowInfo.hpx : (rowInfo && rowInfo.hpt ? Math.round(rowInfo.hpt * 1.333) : null);
        html += '<tr' + (rh ? ' style="height:' + rh + 'px"' : '') + '>';

        for (var cj = range.s.c; cj <= range.e.c; cj++) {
          var k = ri + ',' + cj;
          if (skipMap[k]) continue;

          var addr = XLSX.utils.encode_cell({ r: ri, c: cj });
          var cell = sheet[addr];
          var merge = mergeMap[k];
          var rsAttr = (merge && merge.rs > 1) ? ' rowspan="' + merge.rs + '"' : '';
          var csAttr = (merge && merge.cs > 1) ? ' colspan="' + merge.cs + '"' : '';

          var styles = [];

          if (cell && cell.s) {
            var s = cell.s;
            // Fill
            if (s.fgColor && s.fgColor.rgb) {
              var bg = argbToHex(s.fgColor.rgb);
              if (bg && bg.toLowerCase() !== '#ffffff') styles.push('background-color:' + bg);
            } else if (s.fill) {
              var fg = (s.fill.fgColor && s.fill.fgColor.rgb) ? s.fill.fgColor.rgb
                     : (s.fill.bgColor && s.fill.bgColor.rgb) ? s.fill.bgColor.rgb : null;
              if (fg) {
                var bgc = argbToHex(fg);
                if (bgc && bgc.toLowerCase() !== '#ffffff') styles.push('background-color:' + bgc);
              }
            }
            // Font
            if (s.font) {
              if (s.font.bold)   styles.push('font-weight:bold');
              if (s.font.italic) styles.push('font-style:italic');
              if (s.font.sz)     styles.push('font-size:' + s.font.sz + 'pt');
              if (s.font.color && s.font.color.rgb) {
                var fc = argbToHex(s.font.color.rgb);
                if (fc && fc.toLowerCase() !== '#000000') styles.push('color:' + fc);
              }
            }
            // Alignment
            if (s.alignment) {
              if (s.alignment.horizontal) styles.push('text-align:' + s.alignment.horizontal);
              if (s.alignment.vertical)   styles.push('vertical-align:' + s.alignment.vertical);
              if (s.alignment.wrapText)   styles.push('white-space:pre-wrap');
            }
            // Borders
            if (s.border) {
              ['top','right','bottom','left'].forEach(function(side) {
                var bc = borderCss(s.border[side]);
                if (bc) styles.push('border-' + side + ':' + bc);
              });
            }
          }
          styles.push('padding:2px 6px');

          var display = '';
          if (cell) {
            if (cell.t === 'n' && cell.w) display = cell.w;
            else if (cell.w !== undefined) display = String(cell.w);
            else if (cell.v !== undefined && cell.v !== null) display = String(cell.v);
          }
          // escape HTML
          display = display.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

          html += '<td' + rsAttr + csAttr + (styles.length ? ' style="' + styles.join(';') + '"' : '') + '>' + display + '</td>';
        }
        html += '</tr>';
      }
      html += '</table>';
      return html;
    }

    function normalizeSheetRange(sheet) {
      if (!sheet || !sheet['!ref']) return;
      let minR = Number.MAX_SAFE_INTEGER, minC = Number.MAX_SAFE_INTEGER, maxR = -1, maxC = -1;
      Object.keys(sheet).forEach(function (addr) {
        if (!addr || addr[0] === '!') return;
        const cell = sheet[addr];
        if (!cell || cell.v === undefined || cell.v === null || String(cell.v).trim() === '') return;
        const p = XLSX.utils.decode_cell(addr);
        minR = Math.min(minR, p.r); minC = Math.min(minC, p.c);
        maxR = Math.max(maxR, p.r); maxC = Math.max(maxC, p.c);
      });
      if (maxR < 0 || maxC < 0) return;
      maxR += 2; maxC += 2;
      (sheet['!merges'] || []).forEach(function (m) {
        if (!m || !m.s || !m.e) return;
        minR = Math.min(minR, m.s.r); minC = Math.min(minC, m.s.c);
        maxR = Math.max(maxR, m.e.r); maxC = Math.max(maxC, m.e.c);
      });
      sheet['!ref'] = XLSX.utils.encode_range({ s: { r: Math.max(0, minR), c: Math.max(0, minC) }, e: { r: maxR, c: maxC } });
    }

    btnPrevisualizar.addEventListener('click', async function () {
      const url = construirUrlExcel();
      if (!url) return;

      panelPreview.classList.remove('d-none');
      previewLabel.textContent = tipoReporte.options[tipoReporte.selectedIndex].textContent;
      previewDescripcion.textContent = 'Previsualización del Excel generado.';

      const tipo   = tipoReporte.value;
      const origen = origenMuestra.value;
      // Para muestra individual y residuos usamos el renderizador HTML del servidor (más fiel al template)
      const usarIframe = (tipo === 'residuos') || (tipo === 'muestra' && origen === 'cliente');

      if (usarIframe) {
        previewExcel.classList.add('d-none');
        previewWeb.classList.remove('d-none');
        previewFrame.src = 'about:blank';
        previewFrame.src = url + (url.indexOf('?') !== -1 ? '&' : '?') + 'preview_html=1';
      } else {
        previewWeb.classList.add('d-none');
        previewExcel.classList.remove('d-none');
        previewExcelWrap.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Generando previsualización...</div>';

        try {
          const res = await fetch(url, { credentials: 'same-origin' });
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const buffer = await res.arrayBuffer();
          const wb = XLSX.read(buffer, { type: 'array', cellStyles: true, cellNF: true, cellDates: true });
          const sheet = wb.Sheets[wb.SheetNames[0]];
          normalizeSheetRange(sheet);
          const html = sheetToStyledHtml(sheet);
          previewExcelWrap.innerHTML = html;
        } catch (err) {
          previewExcelWrap.innerHTML = '<div class="p-3 text-danger"><strong>Error en previsualización:</strong> ' + String(err.message || err) + '</div>';
        }
      }
    });

    btnDescargarExcel.addEventListener('click', function () {
      const url = construirUrlExcel();
      if (url) window.open(url, '_blank');
    });
  })();
</script>
