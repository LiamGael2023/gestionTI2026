<?php
// modules/inventario/views/reporte_asignacion.php
require_once __DIR__ . "/../controllers/ReporteAsignacionController.php";
?>
<?php include __DIR__ . '/_submenu.php'; ?>
<!-- Toast -->
<div id="toastContainerReporte" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999"></div>

<!-- ═══════════════ CABECERA ═══════════════ -->
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title"><i class="ti ti-chart-bar me-2 text-primary"></i>Reporte de Activos por Trabajador</h2>
        <div class="text-muted mt-1">Distribución de equipos según jerarquía organizacional</div>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="d-flex gap-2">
          <button id="btnExportExcel" class="btn btn-outline-success btn-sm" disabled>
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
          </button>
          <button id="btnExportPdf" class="btn btn-outline-danger btn-sm" disabled>
            <i class="ti ti-file-description me-1"></i>PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
<div class="container-xl">

<!-- ═══════════════ TABS ═══════════════ -->
<ul class="nav nav-tabs mb-3" id="tabsReporte" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="tab-trabajadores-link" data-bs-toggle="tab"
       href="#tab-trabajadores" role="tab">
      <i class="ti ti-users me-1"></i>Por Trabajador
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="tab-patrimonial-link" data-bs-toggle="tab"
       href="#tab-patrimonial" role="tab">
      <i class="ti ti-barcode me-1"></i>Por Código Patrimonial
    </a>
  </li>
</ul>

<div class="tab-content">

<!-- ═══════════════ TAB 1: POR TRABAJADOR ═══════════════ -->
<div class="tab-pane fade show active" id="tab-trabajadores" role="tabpanel">

  <!-- Filtros -->
  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="ti ti-filter me-2 text-primary"></i>Filtros</h3>
      <div class="card-options">
        <button class="btn btn-sm btn-ghost-secondary" id="btnToggleFiltros">
          <i class="ti ti-chevron-up" id="iconToggleFiltros"></i>
        </button>
      </div>
    </div>
    <div class="card-body" id="panelFiltros">
      <form id="formReporte">
        <input type="hidden" name="generarReporte" value="1">
        <div class="row g-3">
          <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label">Gerencia</label>
            <select name="gerencia" id="filtroGerencia" class="form-select form-select-sm">
              <option value="">— Todas —</option>
            </select>
          </div>
          <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label">Unidad</label>
            <select name="unidad" id="filtroUnidad" class="form-select form-select-sm">
              <option value="">— Todas —</option>
            </select>
          </div>
          <div class="col-12 col-md-4 col-lg-2">
            <label class="form-label">Sede</label>
            <select name="sede" id="filtroSede" class="form-select form-select-sm">
              <option value="">— Todas —</option>
            </select>
          </div>
          <div class="col-12 col-md-4 col-lg-2">
            <label class="form-label">Tipo Contrato</label>
            <select name="tipoContrato" id="filtroTipoContrato" class="form-select form-select-sm">
              <option value="">— Todos —</option>
            </select>
          </div>
          <div class="col-12 col-md-4 col-lg-2">
            <label class="form-label">Jefe Inmediato</label>
            <select name="idJefe" id="filtroJefe" class="form-select form-select-sm">
              <option value="">— Todos —</option>
            </select>
          </div>
          <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label">DNI</label>
            <input type="text" name="dni" id="filtroDni" class="form-control form-control-sm"
                   placeholder="Buscar por DNI..." maxlength="20">
          </div>
          <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label">Nombre trabajador</label>
            <input type="text" name="nombre" id="filtroNombre" class="form-control form-control-sm"
                   placeholder="Buscar por nombre...">
          </div>
          <div class="col-12 col-md-4 col-lg-2">
            <label class="form-label">Tipo de Activo</label>
            <select name="idTipoActivo" id="filtroTipoActivo" class="form-select form-select-sm">
              <option value="">— Todos —</option>
            </select>
          </div>
          <div class="col-12 col-md-4 col-lg-2">
            <label class="form-label">Fecha desde</label>
            <input type="date" name="fechaDesde" class="form-control form-control-sm">
          </div>
          <div class="col-12 col-md-4 col-lg-2">
            <label class="form-label">Fecha hasta</label>
            <input type="date" name="fechaHasta" class="form-control form-control-sm">
          </div>
          <div class="col-12 col-md-4 col-lg-2 d-flex align-items-end">
            <label class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="soloConActivos" checked>
              <span class="form-check-label">Solo con activos</span>
            </label>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-primary btn-sm px-4">
            <i class="ti ti-search me-1"></i>Generar Reporte
          </button>
          <button type="button" id="btnLimpiarFiltros" class="btn btn-secondary btn-sm">
            <i class="ti ti-x me-1"></i>Limpiar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tarjetas resumen -->
  <div id="seccionResumen" class="d-none">
    <div class="row g-3 mb-3" id="tarjetasResumen"></div>
  </div>

  <!-- Gráficos -->
  <div id="seccionGraficos" class="d-none">
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-7">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="ti ti-chart-bar me-2 text-primary"></i>Activos por Tipo</h3>
            <div class="card-options">
              <select id="tipoGrafico" class="form-select form-select-sm">
                <option value="bar">Barras</option>
                <option value="horizontalBar">Barras Horizontales</option>
                <option value="pie">Torta</option>
                <option value="doughnut">Dona</option>
              </select>
            </div>
          </div>
          <div class="card-body"><canvas id="graficoTipos" height="280"></canvas></div>
        </div>
      </div>
      <div class="col-12 col-md-5">
        <div class="card h-100">
          <div class="card-header">
            <h3 class="card-title"><i class="ti ti-building me-2 text-success"></i>Por Gerencia</h3>
          </div>
          <div class="card-body d-flex align-items-center justify-content-center">
            <canvas id="graficoGerencias" height="270"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="seccionTabla" class="d-none">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title" id="tituloTabla">
          <i class="ti ti-table me-2 text-primary"></i>Resultado
        </h3>
        <div class="card-options d-flex gap-2">
          <input type="text" id="buscadorTabla" class="form-control form-control-sm"
                 placeholder="Buscar en tabla..." style="width:180px">
          <select id="vistaTabla" class="form-select form-select-sm">
            <option value="completa">Vista completa</option>
            <option value="resumen">Solo resumen</option>
          </select>
        </div>
      </div>
      <div class="card-body p-0">
        <div id="spinnerTabla" class="d-none text-center py-5">
          <div class="spinner-border text-primary"></div>
          <div class="text-muted mt-2">Cargando reporte...</div>
        </div>
        <div class="table-responsive" id="wrapTabla">
          <table id="tablaReporte" class="table table-sm table-vcenter table-striped mb-0">
            <thead id="theadReporte"></thead>
            <tbody id="tbodyReporte"></tbody>
            <tfoot id="tfootReporte"></tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

</div><!-- /tab-trabajadores -->

<!-- ═══════════════ TAB 2: POR CÓDIGO PATRIMONIAL ═══════════════ -->
<div class="tab-pane fade" id="tab-patrimonial" role="tabpanel">

  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title">
        <i class="ti ti-barcode me-2 text-primary"></i>Buscar por Código Patrimonial
      </h3>
    </div>
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
          <label class="form-label fw-semibold">Código Patrimonial</label>
          <div class="input-group">
            <span class="input-group-text"><i class="ti ti-barcode"></i></span>
            <input type="text" id="inputCodigoPatrimonial" class="form-control"
                   placeholder="Ej: 12345 (búsqueda parcial)" maxlength="50"
                   autocomplete="off">
            <button class="btn btn-primary" id="btnBuscarCodigo">
              <i class="ti ti-search me-1"></i>Buscar
            </button>
          </div>
          <div class="form-text">Ingresa al menos 2 caracteres. Admite búsqueda parcial.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Resultado búsqueda patrimonial -->
  <div id="resultadoPatrimonial" class="d-none">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title" id="tituloPatrimonial">
          <i class="ti ti-package me-2 text-primary"></i>Activos encontrados
        </h3>
        <div class="card-options">
          <button class="btn btn-sm btn-outline-success" id="btnExportExcelPatrimonial">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
          </button>
          <button class="btn btn-sm btn-outline-danger ms-1" id="btnExportPdfPatrimonial">
            <i class="ti ti-file-description me-1"></i>PDF
          </button>
        </div>
      </div>
      <div class="card-body p-0" id="cuerpoPatrimonial"></div>
    </div>
  </div>

  <!-- Spinner búsqueda -->
  <div id="spinnerPatrimonial" class="d-none text-center py-5">
    <div class="spinner-border text-primary"></div>
    <div class="text-muted mt-2">Buscando activo...</div>
  </div>

  <!-- Sin resultados -->
  <div id="sinResultadosPatrimonial" class="d-none">
    <div class="empty">
      <div class="empty-icon"><i class="ti ti-search-off text-muted" style="font-size:3rem"></i></div>
      <p class="empty-title">Sin resultados</p>
      <p class="empty-subtitle text-muted">No se encontraron activos con ese código patrimonial.</p>
    </div>
  </div>

</div><!-- /tab-patrimonial -->

</div><!-- /tab-content -->

</div><!-- /container -->
</div><!-- /page-body -->

<!-- Modal detalle activos (Tab 1) -->
<div class="modal fade" id="modalDetalleActivos" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ti ti-package me-2 text-primary"></i>
          <span id="detalleModalTitulo">Detalle de Activos</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detalleModalCuerpo">
        <div class="text-center py-4">
          <span class="spinner-border spinner-border-sm me-2"></span>Cargando...
        </div>
      </div>
    </div>
  </div>
</div>

<script src="modules/inventario/views/js/reporte_asignacion.js"></script>
