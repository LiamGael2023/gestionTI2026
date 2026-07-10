<?php
// ===== DATOS PARA EL KARDEX =====
require_once 'config/db.php';

$tab    = $_GET['tab'] ?? (isset($_GET['subaction']) && $_GET['subaction'] === 'kardex' ? 'kardex' : 'inventario');
$mes    = intval($_GET['mes']  ?? date('m'));
$anio   = intval($_GET['anio'] ?? date('Y'));
if ($mes < 1 || $mes > 12)        $mes  = intval(date('m'));
if ($anio < 2000 || $anio > 2100) $anio = intval(date('Y'));

$dias_mes  = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
$meses_nombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mes_nombre = $meses_nombres[$mes - 1];

$conn_r = Conexion::conectar();

// Lista de reactivos activos
$reactivos_lista = [];
$stmt_rl = sqlsrv_query($conn_r,
    "SELECT r.Id_Reactivo, r.Nombre,
            ISNULL(um.Abreviatura, '') AS Unidad_Medida,
            r.Cantidad_Stock, ISNULL(r.Cantidad_Inicial,0) AS Cantidad_Inicial
     FROM laboratorio.Reactivo_Lab r
     LEFT JOIN laboratorio.Unidad_Medida um ON r.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
     WHERE r.Activo = 1 ORDER BY r.Nombre"
);
if ($stmt_rl) {
    while ($r = sqlsrv_fetch_array($stmt_rl, SQLSRV_FETCH_ASSOC)) { $reactivos_lista[] = $r; }
}

// Movimientos kardex (solo si tab=kardex)
$movimientos = [];
if ($tab === 'kardex') {
    $stmt_mov = sqlsrv_query($conn_r,
        "SELECT mk.Id_Reactivo, mk.Tipo_Movimiento, mk.Cantidad, DAY(mk.Fecha_Registro) as Dia
         FROM laboratorio.Movimiento_Kardex mk
         WHERE mk.Activo=1 AND YEAR(mk.Fecha_Registro)=? AND MONTH(mk.Fecha_Registro)=?
         ORDER BY mk.Id_Reactivo, mk.Fecha_Registro",
        [$anio, $mes]
    );
    if ($stmt_mov) {
        while ($row = sqlsrv_fetch_array($stmt_mov, SQLSRV_FETCH_ASSOC)) {
            $ir   = $row['Id_Reactivo'];
            $dia  = $row['Dia'];
            $tipo = strtoupper($row['Tipo_Movimiento'][0] ?? 'E');
            if (!isset($movimientos[$ir]))       $movimientos[$ir] = [];
            if (!isset($movimientos[$ir][$dia])) $movimientos[$ir][$dia] = ['E'=>0,'S'=>0];
            $movimientos[$ir][$dia][$tipo] += intval($row['Cantidad']);
        }
    }
}
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
.nav-tabs .nav-link        { color:#6b7280; font-weight:500; }
.nav-tabs .nav-link.active { color:#1d273b; font-weight:600; border-bottom:2px solid #004d99; }
.tab-content               { padding-top:1.25rem; }
.is-invalid { border-color:#dc3545!important; }
.invalid-feedback { color:#dc3545; font-size:.875em; margin-top:.25rem; }
.entrada  { color:#31ce36; font-weight:600; }
.salida   { color:#f97316; font-weight:600; }
.value-empty { color:#d4d4d8; font-size:9px; }
.movimiento-click { cursor:pointer; text-decoration:underline; text-underline-offset:2px; }
.table-wrapper { overflow-x:auto; border-radius:4px; }
.table-wrapper::-webkit-scrollbar { height:8px; }
.table-wrapper::-webkit-scrollbar-track { background:#f1f1f1; border-radius:10px; }
.table-wrapper::-webkit-scrollbar-thumb { background:#888; border-radius:10px; }
.kardex-table { font-size:.85rem; width:100%; border-collapse:collapse; background:#fff; min-width:1400px; }
.kardex-table thead { background:#f8f9fa; position:sticky; top:0; }
.kardex-table th { font-weight:600; border-bottom:2px solid #dee2e6; padding:12px 8px; text-align:center; white-space:nowrap; font-size:11px; color:#1d273b; }
.kardex-table th:nth-child(1){width:45px;} .kardex-table th:nth-child(2){width:160px;text-align:left;} .kardex-table th:nth-child(3){width:55px;} .kardex-table th:nth-child(4){width:55px;} .kardex-table th:nth-child(5){width:65px;}
.kardex-table td { padding:10px 8px; border-bottom:1px solid #dee2e6; text-align:center; font-size:11px; }
.kardex-table td:nth-child(1){font-weight:600;} .kardex-table td:nth-child(2){text-align:left;font-weight:600;}
.kardex-table tbody tr:hover { background:#f8f9fa; }
.kardex-toolbar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
/* Vencimiento coloring */
#tabla-reactivos tbody tr.row-reac-vencido { background:#fff0f0 !important; }
#tabla-reactivos tbody tr.row-reac-proximo { background:#fff8ed !important; }

@media print {
  .page-header, .nav-tabs, #tab-inventario, .kardex-toolbar, .modal, .btn,
  nav, .breadcrumb { display: none !important; }
  #tab-kardex { display: block !important; }
  .table-wrapper { overflow: visible; }
  .kardex-table { font-size: 7pt; min-width: 0; }
  .kardex-table th, .kardex-table td { padding: 3px 4px; }
}
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?module=laboratorio">Laboratorio</a></li>
        <li class="breadcrumb-item active">Reactivos</li>
      </ol>
    </nav>
    <div class="row g-2 align-items-center mb-2">
      <div class="col">
        <h2 class="page-title">REACTIVOS DE LABORATORIO</h2>
        <div class="text-muted mt-1">Gestiona y controla el inventario de reactivos quimicos, visualiza existencias y supervisa las fechas de vencimiento</div>
      </div>
      <div class="col-auto d-flex gap-2">
        <?php if (!empty($permisos['editar'])): ?>
        <button class="btn btn-outline-secondary" id="btn-gestionar-unidades-header" title="Gestionar unidades de medida">
          <i class="ti ti-ruler me-1"></i> Unidades de Medida
        </button>
        <?php endif; ?>
        <?php if (!empty($permisos['crear'])): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-reactivo">
          <i class="ti ti-plus me-2"></i> Nuevo Reactivo
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <ul class="nav nav-tabs mb-0" id="reactivo-tabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $tab !== 'kardex' ? 'active' : ''; ?>"
                id="tab-inventario-btn" data-bs-toggle="tab" data-bs-target="#tab-inventario" type="button" role="tab">
          <i class="ti ti-package me-1"></i> Inventario
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $tab === 'kardex' ? 'active' : ''; ?>"
                id="tab-kardex-btn" data-bs-toggle="tab" data-bs-target="#tab-kardex" type="button" role="tab">
          <i class="ti ti-calendar-stats me-1"></i> Kardex de Reactivos
        </button>
      </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white" id="reactivo-tabs-content">

      <!-- TAB INVENTARIO -->
      <div class="tab-pane fade <?php echo $tab !== 'kardex' ? 'show active' : ''; ?>" id="tab-inventario" role="tabpanel">
        <div class="alert alert-info mb-3" role="alert">
          <i class="ti ti-info-circle me-2"></i>
          <strong>Para anadir un nuevo insumo, haga clic en "Nuevo Reactivo".</strong>
          Consulte el historial de movimientos en el tab <strong>Kardex de Reactivos</strong>.
        </div>
        <div class="table-responsive">
          <table id="tabla-reactivos" class="table table-vcenter card-table table-striped" style="width:100%">
            <thead>
              <tr><th>No</th><th>Nombre</th><th>Tipo</th><th>Proveedor</th><th>U.M.</th><th>Stock</th><th>Vencimiento</th><th>Accion</th><th style="display:none"></th></tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <!-- TAB KARDEX -->
      <div class="tab-pane fade <?php echo $tab === 'kardex' ? 'show active' : ''; ?>" id="tab-kardex" role="tabpanel">
        <div class="alert alert-info mb-3" role="alert">
          <i class="ti ti-info-circle me-2"></i>
          <strong>Formula:</strong> <code>Inicial + Entradas - Salidas</code>.
          Haga clic en <span class="entrada">+E</span> o <span class="salida">-S</span> para ver el detalle del dia.
        </div>

        <!-- Toolbar -->
        <div class="card mb-3">
          <div class="card-body py-2">
            <div class="kardex-toolbar">
              <label class="mb-0 fw-semibold">Mes:</label>
              <select id="kardex-mes" class="form-select form-select-sm" style="width:140px;">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                  <option value="<?php echo $m; ?>" <?php echo $m == $mes ? 'selected' : ''; ?>><?php echo $meses_nombres[$m-1]; ?></option>
                <?php endfor; ?>
              </select>
              <label class="mb-0 fw-semibold">Ano:</label>
              <input type="number" id="kardex-anio" value="<?php echo $anio; ?>" min="2000" max="2100" class="form-control form-control-sm" style="width:90px;">
              <button onclick="actualizarKardex()" class="btn btn-sm btn-primary">
                <i class="ti ti-refresh me-1"></i> Actualizar
              </button>
              <button class="btn btn-sm btn-secondary" onclick="previsualizarKardex()">
                <i class="ti ti-printer me-1"></i> Vista Previa
              </button>
              <?php if (!empty($permisos['exportar'])): ?>
              <a id="btn-exportar-kardex" href="modules/laboratorio/reactivo/controllers/ExportarKardex.php?mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>" class="btn btn-sm btn-outline-success" target="_blank">
                <i class="ti ti-file-spreadsheet me-1"></i> Excel
              </a>
              <a id="btn-exportar-pdf" href="modules/laboratorio/reactivo/controllers/ExportarKardexPDF.php?mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>" class="btn btn-sm btn-outline-danger" target="_blank">
                <i class="ti ti-file-type-pdf me-1"></i> PDF
              </a>
              <?php endif; ?>
              <div class="ms-auto d-flex gap-2">
                <?php if (!empty($permisos['crear'])): ?>
                <button class="btn btn-sm btn-success" onclick="abrirModalIngreso()">
                  <i class="ti ti-arrow-down-circle me-1"></i> Realizar Ingreso
                </button>
                <?php endif; ?>
                <?php if (!empty($permisos['editar'])): ?>
                <button class="btn btn-sm btn-danger" onclick="abrirModalSalida()">
                  <i class="ti ti-arrow-up-circle me-1"></i> Realizar Salida
                </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla Kardex -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><?php echo $mes_nombre; ?> de <?php echo $anio; ?></h3>
          </div>
          <div class="card-body p-0">
            <div class="table-wrapper">
              <table class="kardex-table">
                <thead>
                  <tr>
                    <th>No</th><th>Nombre</th><th>U.M.</th><th>Inicial</th><th>Actual</th>
                    <?php for ($dia = 1; $dia <= $dias_mes; $dia++): ?>
                      <th title="Dia <?php echo $dia; ?>">
                        <div><?php echo str_pad($dia,2,'0',STR_PAD_LEFT); ?></div>
                        <div style="font-size:9px;font-weight:normal;">E / S</div>
                      </th>
                    <?php endfor; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($tab === 'kardex' && count($reactivos_lista) > 0): ?>
                    <?php foreach ($reactivos_lista as $idx => $reactivo):
                          $id_react     = $reactivo['Id_Reactivo'];
                          $stock_actual  = floatval($reactivo['Cantidad_Stock']);
                          $stock_inicial = floatval($reactivo['Cantidad_Inicial']);
                    ?>
                    <tr>
                      <td><?php echo $idx+1; ?></td>
                      <td><?php echo htmlspecialchars($reactivo['Nombre']); ?></td>
                      <td><?php echo htmlspecialchars($reactivo['Unidad_Medida']); ?></td>
                      <td><?php echo number_format($stock_inicial,2,'.',''); ?></td>
                      <td><strong><?php echo number_format($stock_actual,2,'.',''); ?></strong></td>
                      <?php for ($dia = 1; $dia <= $dias_mes; $dia++):
                            $entrada = $movimientos[$id_react][$dia]['E'] ?? 0;
                            $salida  = $movimientos[$id_react][$dia]['S'] ?? 0;
                            $fc = sprintf('%04d-%02d-%02d',$anio,$mes,$dia);
                      ?>
                      <td>
                        <div style="line-height:1.4;">
                          <?php if ($entrada > 0): ?>
                            <div class="entrada movimiento-click" onclick='mostrarDetallesIngreso("<?php echo $fc; ?>",<?php echo intval($id_react); ?>,<?php echo json_encode((string)$reactivo["Nombre"]); ?>)' title="Ver ingresos del dia">+<?php echo $entrada; ?></div>
                          <?php else: ?><div class="value-empty">-</div><?php endif; ?>
                          <?php if ($salida > 0): ?>
                            <div class="salida movimiento-click" onclick='mostrarDetallesSalida("<?php echo $fc; ?>",<?php echo intval($id_react); ?>,<?php echo json_encode((string)$reactivo["Nombre"]); ?>)' title="Ver salidas del dia">-<?php echo $salida; ?></div>
                          <?php else: ?><div class="value-empty">-</div><?php endif; ?>
                        </div>
                      </td>
                      <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                  <?php elseif ($tab !== 'kardex'): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Abra el tab Kardex para cargar los datos</td></tr>
                  <?php else: ?>
                    <tr><td colspan="<?php echo 5+$dias_mes; ?>" class="text-center text-muted py-4">
                      <i class="ti ti-alert-circle me-2"></i>No hay reactivos registrados
                    </td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div><!-- /tab-kardex -->

    </div><!-- /tab-content -->
  </div>
</div>

<!-- MODAL NUEVO/EDITAR REACTIVO -->
<div class="modal modal-blur fade" id="modal-reactivo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      <div class="modal-header"><h5 class="modal-title" id="modal-titulo">Nuevo Reactivo</h5></div>
      <div class="modal-body">
        <form id="form-reactivo">
          <input type="hidden" id="Id_Reactivo" name="Id_Reactivo">
          <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="Nombre" name="Nombre" placeholder="Ej: Acido Clorhidrico" required>
            <small class="text-muted">Minimo 3 caracteres, debe contener letras</small>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo <span class="text-danger">*</span></label>
              <select class="form-select" id="Tipo" name="Tipo">
                <option value="">-- Seleccione --</option>
                <option value="Agua">Agua</option>
                <option value="Suelo">Suelo</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Proveedor</label>
              <div class="input-group">
                <select class="form-select" id="Id_Proveedor" name="Id_Proveedor">
                  <option value="">-- Sin proveedor --</option>
                </select>
                <button type="button" class="btn btn-outline-secondary" id="btn-nuevo-prov-reactivo" title="Agregar proveedor rapido"><i class="ti ti-plus"></i></button>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Unidad de Medida <span class="text-danger">*</span></label>
              <div class="input-group">
                <select class="form-select" id="Id_Unidad_Medida" name="Id_Unidad_Medida">
                  <option value="">-- Seleccione --</option>
                </select>
                <button type="button" class="btn btn-outline-secondary" id="btn-gestionar-unidades" title="Gestionar unidades de medida"><i class="ti ti-ruler"></i></button>
              </div>
              <small class="text-muted">Si no existe la unidad, créela con <i class="ti ti-ruler"></i></small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Fecha Vencimiento</label>
              <input type="date" class="form-control" id="Fecha_Vencimiento" name="Fecha_Vencimiento">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Cantidad Inicial <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="Cantidad_Inicial" name="Cantidad_Inicial" step="0.01" placeholder="Ej: 100" required>
            <small class="text-muted">Esta cantidad aparecera como ingreso inicial en el kardex</small>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-reactivo">Guardar Reactivo</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL PROVEEDOR RAPIDO -->
<div class="modal fade" id="modal-prov-rapido-reac" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Razon Social <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="prov-razon-social-reac" placeholder="Nombre o razon social">
        </div>
        <div class="row">
          <div class="col mb-3">
            <label class="form-label">RUC</label>
            <input type="text" class="form-control" id="prov-ruc-reac" placeholder="20xxxxxxxxx">
          </div>
          <div class="col mb-3">
            <label class="form-label">Telefono</label>
            <input type="text" class="form-control" id="prov-telefono-reac" placeholder="+51 ...">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Correo</label>
          <input type="email" class="form-control" id="prov-email-reac" placeholder="proveedor@empresa.com">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-prov-rapido-reac">Guardar Proveedor</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL UNIDADES DE MEDIDA -->
<div class="modal fade" id="modal-unidades" tabindex="-1" aria-hidden="true" data-bs-focus="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Unidades de Medida</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-end mb-3">
          <button class="btn btn-sm btn-success" id="btn-nueva-unidad"><i class="ti ti-plus me-1"></i> Nueva Unidad</button>
        </div>
        <div id="tabla-unidades-wrap">
          <table class="table table-sm table-vcenter" id="tabla-unidades">
            <thead><tr><th>Nombre</th><th>Abrev.</th><th style="width:80px">Acc.</th></tr></thead>
            <tbody id="tbody-unidades"><tr><td colspan="3" class="text-center text-muted py-3">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CREAR / EDITAR UNIDAD DE MEDIDA -->
<div class="modal fade" id="modal-crear-unidad" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-header">
        <h5 class="modal-title" id="modal-crear-unidad-titulo">Nueva Unidad de Medida</h5>
      </div>
      <div class="modal-body">
        <input type="hidden" id="unidad-id-edit">
        <div class="mb-3">
          <label class="form-label">Nombre <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="unidad-nombre-edit" placeholder="Ej: Mililitro">
        </div>
        <div class="mb-3">
          <label class="form-label">Abreviatura <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="unidad-abrev-edit" placeholder="Ej: mL">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-unidad">
          <i class="ti ti-device-floppy me-1"></i> Guardar Unidad
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL INGRESO -->
<div class="modal fade" id="modal-ingreso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Realizar Ingreso de Reactivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Reactivo <span class="text-danger">*</span></label>
          <select id="ingreso-reactivo" class="form-select">
            <option value="">Seleccione un reactivo...</option>
            <?php foreach ($reactivos_lista as $r): ?>
              <option value="<?php echo $r['Id_Reactivo']; ?>"><?php echo htmlspecialchars($r['Nombre']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Cantidad <span class="text-danger">*</span></label>
          <input type="number" id="ingreso-cantidad" class="form-control" min="0.01" step="0.01" placeholder="Cantidad a ingresar">
        </div>
        <div class="mb-3">
          <label class="form-label">Factura / Referencia</label>
          <input type="text" id="ingreso-factura" class="form-control" placeholder="FAC-2024-001">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-guardar-ingreso">Guardar Ingreso</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL SALIDA -->
<div class="modal fade" id="modal-salida" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Realizar Salida de Reactivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Reactivo <span class="text-danger">*</span></label>
          <select id="salida-reactivo" class="form-select">
            <option value="">Seleccione un reactivo...</option>
            <?php foreach ($reactivos_lista as $r): ?>
              <option value="<?php echo $r['Id_Reactivo']; ?>"><?php echo htmlspecialchars($r['Nombre']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Cantidad <span class="text-danger">*</span></label>
          <input type="number" id="salida-cantidad" class="form-control" min="0.01" step="0.01" placeholder="Cantidad a retirar">
        </div>
        <div class="mb-3">
          <label class="form-label">Concepto</label>
          <input type="text" id="salida-concepto" class="form-control" placeholder="Ej: Consumo en analisis">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btn-guardar-salida">Guardar Salida</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var tablaReactivos;
var API_REACTIVO = 'modules/laboratorio/reactivo/controllers/ReactivoAPI.php';
var API_PROVEEDOR = 'modules/laboratorio/proveedor/controllers/ProveedorAPI.php';

$(document).ready(function () {
    tablaReactivos = $('#tabla-reactivos').DataTable({
        processing: true, serverSide: true,
        ajax: { url: 'modules/laboratorio/reactivo/views/data_listado_reactivos.php', type: 'POST' },
        columns: [
            { data:0 },{ data:1 },{ data:2 },{ data:3 },{ data:4 },{ data:5 },{ data:6 },
            { data:7, orderable:false },
            { data:8, visible:false }
        ],
        createdRow: function(row, data) {
            if (data[8]) $(row).addClass(data[8]);
        },
        language: { sProcessing: "Procesando...", sLengthMenu: "Mostrar _MENU_ registros", sZeroRecords: "No se encontraron resultados", sEmptyTable: "No hay datos disponibles", sInfo: "Mostrando del _START_ al _END_ de _TOTAL_ registros", sInfoEmpty: "Mostrando 0 registros", sInfoFiltered: "(filtrado de _MAX_ total)", sSearch: "Buscar:", sLoadingRecords: "Cargando...", oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" } },
        order: [[ 0, 'asc' ]]
    });

    cargarSelectProveedoresReac();
    cargarSelectUnidades();

    $('#btn-guardar-reactivo').on('click', function () { guardarReactivo(); });
    $('#modal-reactivo').on('hidden.bs.modal', function () {
        $('#form-reactivo')[0].reset();
        $('#Id_Reactivo').val('');
        $('#Id_Proveedor').val('');
        $('#Id_Unidad_Medida').val('');
        $('#Tipo').val('');
        $('#modal-titulo').text('Nuevo Reactivo');
        $('#form-reactivo').find('.is-invalid').removeClass('is-invalid');
        $('#form-reactivo').find('.invalid-feedback').remove();
    });
    $('#btn-guardar-ingreso').on('click', function () { guardarIngreso(this); });
    $('#btn-guardar-salida').on('click',  function () { guardarSalida(this);  });

    // Proveedor rápido
    $('#btn-nuevo-prov-reactivo').on('click', function () {
        $('#prov-razon-social-reac,#prov-ruc-reac,#prov-telefono-reac,#prov-email-reac').val('');
        new bootstrap.Modal(document.getElementById('modal-prov-rapido-reac')).show();
    });
    $('#btn-guardar-prov-rapido-reac').on('click', function () {
        var razon = $('#prov-razon-social-reac').val().trim();
        if (!razon) { Swal.fire('Error','La razón social es obligatoria','error'); return; }
        var btn = this; btn.disabled = true;
        $.ajax({
            url: API_PROVEEDOR + '?action=guardar', method: 'POST', contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({ Razon_Social: razon, Ruc: $('#prov-ruc-reac').val(), Telefono: $('#prov-telefono-reac').val(), Email: $('#prov-email-reac').val() }),
            success: function(r) {
                btn.disabled = false;
                if (r.success) {
                    var opt = new Option(htmlEscape(r.proveedor.Razon_Social), r.id, true, true);
                    $('#Id_Proveedor').append(opt);
                    bootstrap.Modal.getInstance(document.getElementById('modal-prov-rapido-reac')).hide();
                    Swal.fire({ title:'Proveedor creado', icon:'success', timer:1200, showConfirmButton:false });
                } else { Swal.fire('Error', r.message, 'error'); }
            },
            error: function() { btn.disabled = false; Swal.fire('Error', 'Error de conexion', 'error'); }
        });
    });

    // Unidades de medida — header button + modal button both open same modal
    function _abrirModalUnidades() {
        $('#unidades-form-wrap').hide();
        cargarTablaUnidades();
        new bootstrap.Modal(document.getElementById('modal-unidades')).show();
    }
    $('#btn-gestionar-unidades-header').on('click', _abrirModalUnidades);
    $('#btn-gestionar-unidades').on('click', _abrirModalUnidades);
    $('#btn-nueva-unidad').on('click', function () {
        $('#unidad-id-edit').val('');
        $('#unidad-nombre-edit,#unidad-abrev-edit').val('');
        $('#modal-crear-unidad-titulo').text('Nueva Unidad de Medida');
        new bootstrap.Modal(document.getElementById('modal-crear-unidad')).show();
    });
    $('#btn-guardar-unidad').on('click', function () {
        var nombre = $('#unidad-nombre-edit').val().trim();
        var abrev  = $('#unidad-abrev-edit').val().trim();
        var id     = $('#unidad-id-edit').val();
        if (!nombre || !abrev) { Swal.fire('Error', 'Nombre y abreviatura son obligatorios', 'error'); return; }
        var btn = this; btn.disabled = true;
        $.ajax({
            url: API_REACTIVO + '?action=guardar_unidad', method: 'POST', contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({ Id_Unidad_Medida: id||null, Nombre: nombre, Abreviatura: abrev }),
            success: function(r) {
                btn.disabled = false;
                if (r.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modal-crear-unidad')).hide();
                    cargarTablaUnidades();
                    cargarSelectUnidades(r.id);
                    Swal.fire({ title:'Guardado', icon:'success', timer:1000, showConfirmButton:false });
                } else { Swal.fire('Error', r.message, 'error'); }
            },
            error: function() { btn.disabled = false; Swal.fire('Error', 'Error de conexion', 'error'); }
        });
    });

    var tabActivo = '<?php echo $tab === "kardex" ? "tab-kardex" : "tab-inventario"; ?>';
    var btnTab = document.getElementById(tabActivo + '-btn');
    if (btnTab) { new bootstrap.Tab(btnTab).show(); }
});

function htmlEscape(text) { var d=document.createElement('div'); d.textContent=text; return d.innerHTML; }

function cargarSelectProveedoresReac(selId) {
    $.ajax({ url: API_REACTIVO + '?action=listar_proveedores', type: 'GET', dataType: 'json',
        success: function(r) {
            var sel = $('#Id_Proveedor');
            var cur = sel.val();
            sel.find('option:not(:first)').remove();
            if (r.success && r.data) {
                r.data.forEach(function(p) {
                    sel.append(new Option(p.Razon_Social, p.Id_Proveedor));
                });
            }
            sel.val(selId || cur || '');
        }
    });
}

function cargarSelectUnidades(selId) {
    $.ajax({ url: API_REACTIVO + '?action=listar_unidades', type: 'GET', dataType: 'json',
        success: function(r) {
            var sel = $('#Id_Unidad_Medida');
            var cur = selId || sel.val();
            sel.find('option:not(:first)').remove();
            if (r.success && r.data) {
                r.data.forEach(function(u) {
                    sel.append(new Option(u.Nombre + ' (' + u.Abreviatura + ')', u.Id_Unidad_Medida));
                });
            }
            if (cur) sel.val(cur);
        }
    });
}

function cargarTablaUnidades() {
    var tbody = document.getElementById('tbody-unidades');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center">Cargando...</td></tr>';
    $.ajax({ url: API_REACTIVO + '?action=listar_unidades', type: 'GET', dataType: 'json',
        success: function(r) {
            if (!r.success || !r.data || r.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin unidades registradas</td></tr>';
                return;
            }
            tbody.innerHTML = r.data.map(function(u) {
                return '<tr><td>' + htmlEscape(u.Nombre) + '</td><td><code>' + htmlEscape(u.Abreviatura) + '</code></td>'
                     + '<td><button class="btn btn-xs btn-ghost-primary me-1" onclick="editarUnidad(' + u.Id_Unidad_Medida + ',\'' + htmlEscape(u.Nombre) + '\',\'' + htmlEscape(u.Abreviatura) + '\')" title="Editar"><i class="ti ti-pencil"></i></button>'
                     + '<button class="btn btn-xs btn-ghost-danger" onclick="eliminarUnidad(' + u.Id_Unidad_Medida + ')" title="Eliminar"><i class="ti ti-trash"></i></button></td></tr>';
            }).join('');
        },
        error: function() { tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar</td></tr>'; }
    });
}

function editarUnidad(id, nombre, abrev) {
    $('#unidad-id-edit').val(id);
    $('#unidad-nombre-edit').val(nombre);
    $('#unidad-abrev-edit').val(abrev);
    $('#modal-crear-unidad-titulo').text('Editar Unidad de Medida');
    new bootstrap.Modal(document.getElementById('modal-crear-unidad')).show();
}

function eliminarUnidad(id) {
    Swal.fire({ title:'Eliminar unidad?', icon:'warning', showCancelButton:true, confirmButtonText:'Eliminar', cancelButtonText:'Cancelar' }).then(function(r) {
        if (r.isConfirmed) {
            $.ajax({ url: API_REACTIVO + '?action=eliminar_unidad&id=' + id, type: 'GET', dataType: 'json',
                success: function(resp) {
                    if (resp.success) { cargarTablaUnidades(); cargarSelectUnidades(); Swal.fire({ title:'Eliminado', icon:'success', timer:1000, showConfirmButton:false }); }
                    else { Swal.fire('No se puede eliminar', resp.message, 'warning'); }
                }
            });
        }
    });
}

function actualizarKardex() {
    var mes  = document.getElementById('kardex-mes').value;
    var anio = document.getElementById('kardex-anio').value;
    // Update export links
    var linkExcel = document.getElementById('btn-exportar-kardex');
    if (linkExcel) linkExcel.href = 'modules/laboratorio/reactivo/controllers/ExportarKardex.php?mes=' + mes + '&anio=' + anio;
    var linkPDF = document.getElementById('btn-exportar-pdf');
    if (linkPDF) linkPDF.href = 'modules/laboratorio/reactivo/controllers/ExportarKardexPDF.php?mes=' + mes + '&anio=' + anio;
    window.location.href = '?module=laboratorio&action=reactivo&tab=kardex&mes=' + mes + '&anio=' + anio;
}

function previsualizarKardex() {
    window.print();
}

function abrirModalIngreso() {
    document.getElementById('ingreso-reactivo').value='';
    document.getElementById('ingreso-cantidad').value='';
    document.getElementById('ingreso-factura').value='';
    new bootstrap.Modal(document.getElementById('modal-ingreso')).show();
}

function abrirModalSalida() {
    document.getElementById('salida-reactivo').value='';
    document.getElementById('salida-cantidad').value='';
    document.getElementById('salida-concepto').value='';
    new bootstrap.Modal(document.getElementById('modal-salida')).show();
}

function guardarIngreso(btn) {
    var idReactivo=document.getElementById('ingreso-reactivo').value;
    var cantidad=document.getElementById('ingreso-cantidad').value;
    var factura=document.getElementById('ingreso-factura').value;
    if (!idReactivo) { Swal.fire('Error','Seleccione un reactivo','error'); return; }
    if (!cantidad||parseFloat(cantidad)<=0) { Swal.fire('Error','Ingrese una cantidad valida','error'); return; }
    btn.disabled=true; btn.innerHTML='<i class="ti ti-loader me-1"></i> Guardando...';
    $.ajax({
        url: API_REACTIVO+'?action=registrar_ingreso', method:'POST', contentType:'application/json', dataType:'json',
        data: JSON.stringify({Id_Reactivo:parseInt(idReactivo),Cantidad:parseFloat(cantidad),Factura_Referencia:factura||'S/N'}),
        success: function(resp){
            btn.disabled=false; btn.innerHTML='Guardar Ingreso';
            if(resp.success){Swal.fire('Exito','Ingreso registrado correctamente','success').then(function(){bootstrap.Modal.getInstance(document.getElementById('modal-ingreso')).hide();setTimeout(function(){location.reload();},800);});}
            else{Swal.fire('Error',resp.message||'Error al registrar','error');}
        },
        error: function(xhr){btn.disabled=false;btn.innerHTML='Guardar Ingreso';Swal.fire('Error',(xhr.responseJSON&&xhr.responseJSON.message)||'Error de conexion','error');}
    });
}

function guardarSalida(btn) {
    var idReactivo=document.getElementById('salida-reactivo').value;
    var cantidad=document.getElementById('salida-cantidad').value;
    var concepto=document.getElementById('salida-concepto').value;
    if (!idReactivo) { Swal.fire('Error','Seleccione un reactivo','error'); return; }
    if (!cantidad||parseFloat(cantidad)<=0) { Swal.fire('Error','Ingrese una cantidad valida','error'); return; }
    btn.disabled=true; btn.innerHTML='<i class="ti ti-loader me-1"></i> Guardando...';
    $.ajax({
        url: API_REACTIVO+'?action=registrar_salida', method:'POST', contentType:'application/json', dataType:'json',
        data: JSON.stringify({Id_Reactivo:parseInt(idReactivo),Cantidad:parseFloat(cantidad),Concepto:concepto||'S/N'}),
        success: function(resp){
            btn.disabled=false; btn.innerHTML='Guardar Salida';
            if(resp.success){Swal.fire('Exito','Salida registrada correctamente','success').then(function(){bootstrap.Modal.getInstance(document.getElementById('modal-salida')).hide();setTimeout(function(){location.reload();},800);});}
            else{Swal.fire('Error',resp.message||'Error al registrar','error');}
        },
        error: function(xhr){btn.disabled=false;btn.innerHTML='Guardar Salida';Swal.fire('Error',(xhr.responseJSON&&xhr.responseJSON.message)||'Error de conexion','error');}
    });
}

function guardarReactivo() {
    var id=$('#Id_Reactivo').val(); var nombre=$('#Nombre').val(); var tipo=$('#Tipo').val();
    var idUnidad=$('#Id_Unidad_Medida').val(); var idProveedor=$('#Id_Proveedor').val();
    var fechaV=$('#Fecha_Vencimiento').val(); var cantidad=$('#Cantidad_Inicial').val();
    var errores={};
    nombre=nombre.trim();
    if(!nombre)errores['Nombre']='El nombre es obligatorio'; else if(nombre.length<3)errores['Nombre']='Minimo 3 caracteres'; else if(!/[a-zA-ZáéíóúñÁÉÍÓÚÑ]/i.test(nombre))errores['Nombre']='Debe contener al menos una letra';
    if(!id&&(!cantidad||parseFloat(cantidad)<=0))errores['Cantidad_Inicial']='La cantidad debe ser mayor a 0';
    var hayError=Object.keys(errores).some(function(k){return errores[k];});
    if(hayError){
        $('#form-reactivo').find('.invalid-feedback').remove(); $('#form-reactivo').find('.is-invalid').removeClass('is-invalid');
        Object.keys(errores).forEach(function(campo){if(errores[campo]){var inp=$('[name="'+campo+'"]');if(inp.length){inp.addClass('is-invalid');inp.after('<div class="invalid-feedback d-block">'+htmlEscape(errores[campo])+'</div>');}}});
        Swal.fire('Validacion','Revise los campos marcados','warning'); return;
    }
    var action=id?'actualizar':'guardar';
    $.ajax({
        url:API_REACTIVO+'?action='+action, type:'POST', contentType:'application/json', dataType:'json',
        data:JSON.stringify({Id_Reactivo:id||null,Nombre:nombre,Tipo:tipo||null,Id_Proveedor:idProveedor?parseInt(idProveedor):null,Id_Unidad_Medida:idUnidad?parseInt(idUnidad):null,Fecha_Vencimiento:fechaV,Cantidad_Inicial:cantidad}),
        success:function(resp){if(resp.success){$('#modal-reactivo').modal('hide');Swal.fire({title:'Guardado!',text:resp.message,icon:'success',timer:1500,showConfirmButton:false}).then(function(){tablaReactivos.ajax.reload();});}else{if(resp.errors){$('#form-reactivo').find('.invalid-feedback').remove();$('#form-reactivo').find('.is-invalid').removeClass('is-invalid');Object.keys(resp.errors).forEach(function(k){var m=resp.errors[k];if(m){var i=$('[name="'+k+'"]');if(i.length){i.addClass('is-invalid');i.after('<div class="invalid-feedback d-block">'+htmlEscape(m)+'</div>');}}});Swal.fire('Validacion',resp.message,'warning');}else{Swal.fire('Error',resp.message,'error');}}},
        error:function(xhr){var r=xhr.responseJSON||{};if(r.errors){$('#form-reactivo').find('.invalid-feedback').remove();$('#form-reactivo').find('.is-invalid').removeClass('is-invalid');Object.keys(r.errors).forEach(function(k){var m=r.errors[k];if(m){var i=$('[name="'+k+'"]');if(i.length){i.addClass('is-invalid');i.after('<div class="invalid-feedback d-block">'+htmlEscape(m)+'</div>');}}});Swal.fire('Validacion',r.message||'Error','warning');}else{Swal.fire('Error',r.message||'Error al guardar','error');}}
    });
}

function editarReactivo(id) {
    $.ajax({url:API_REACTIVO+'?action=obtener&id='+id,type:'GET',dataType:'json',success:function(resp){
        if(resp.success){
            var r=resp.data;
            $('#Id_Reactivo').val(r.Id_Reactivo);
            $('#Nombre').val(r.Nombre);
            $('#Tipo').val(r.Tipo||'');
            $('#Fecha_Vencimiento').val(r.Fecha_Vencimiento||'');
            $('#Cantidad_Inicial').val('').attr('placeholder','Stock actual: '+r.Cantidad_Stock);
            // Proveedor
            if(r.Id_Proveedor){
                if($('#Id_Proveedor option[value="'+r.Id_Proveedor+'"]').length){
                    $('#Id_Proveedor').val(r.Id_Proveedor);
                } else {
                    // Cargar de nuevo y seleccionar
                    $.ajax({url:API_REACTIVO+'?action=listar_proveedores',type:'GET',dataType:'json',success:function(pr){
                        if(pr.success){var sel=$('#Id_Proveedor');sel.find('option:not(:first)').remove();pr.data.forEach(function(p){sel.append(new Option(p.Razon_Social,p.Id_Proveedor));});sel.val(r.Id_Proveedor);}
                    }});
                }
            } else { $('#Id_Proveedor').val(''); }
            // Unidad
            if(r.Id_Unidad_Medida){
                if($('#Id_Unidad_Medida option[value="'+r.Id_Unidad_Medida+'"]').length){
                    $('#Id_Unidad_Medida').val(r.Id_Unidad_Medida);
                } else {
                    cargarSelectUnidades(r.Id_Unidad_Medida);
                }
            } else { $('#Id_Unidad_Medida').val(''); }
            $('#modal-titulo').text('Editar Reactivo');
            new bootstrap.Modal(document.getElementById('modal-reactivo')).show();
        }else{Swal.fire('Error','No se pudo cargar el reactivo','error');}
    }});
}

function eliminarReactivo(id) {
    Swal.fire({title:'Confirmar eliminacion?',text:'Esta accion no se puede deshacer',icon:'warning',showCancelButton:true,confirmButtonText:'Eliminar',cancelButtonText:'Cancelar'}).then(function(r){if(r.isConfirmed){$.ajax({url:API_REACTIVO+'?action=eliminar&id='+id,type:'GET',dataType:'json',success:function(resp){if(resp.success){Swal.fire('Eliminado',resp.message,'success').then(function(){tablaReactivos.ajax.reload();});}else{Swal.fire({title:'No permitido',text:resp.message,icon:'warning'});}},error:function(xhr){var r=xhr.responseJSON||{};Swal.fire({title:'No permitido',text:r.message||'Error',icon:'warning'});}});}});
}

function reactivarReactivo(id) {
    Swal.fire({title:'Reactivar reactivo?',icon:'question',showCancelButton:true,confirmButtonText:'Reactivar',cancelButtonText:'Cancelar'}).then(function(r){if(r.isConfirmed){$.ajax({url:API_REACTIVO+'?action=reactivar&id='+id,type:'GET',dataType:'json',success:function(resp){if(resp.success){Swal.fire('Reactivado',resp.message,'success').then(function(){tablaReactivos.ajax.reload();});}else{Swal.fire('Error',resp.message,'error');}}});}});
}

function fmtFecha(str){
    // Converts YYYY-MM-DD or YYYY-MM-DD HH:mm:ss to DD/MM/YYYY or DD/MM/YYYY HH:mm
    if(!str)return '-';
    var m=str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/);
    if(!m)return str;
    return m[3]+'/'+m[2]+'/'+m[1]+(m[4]?' '+m[4]+':'+m[5]:'');
}

function mostrarDetallesIngreso(fecha,idReactivo,nombreReactivo){
    $.ajax({url:API_REACTIVO+'?action=obtener_detalles_ingreso&fecha='+encodeURIComponent(fecha)+'&id_reactivo='+encodeURIComponent(idReactivo||0),method:'GET',dataType:'json',success:function(resp){
        var fechaDisplay=fmtFecha(fecha);
        if(resp.success&&resp.ingresos&&resp.ingresos.length>0){
            var html='<div style="text-align:left;font-size:13px;"><p style="color:#6b7280;margin-bottom:15px;font-size:12px;">Reactivos ingresados en esta fecha:</p><table style="width:100%;border-collapse:collapse;" id="tbl-ing-edit"><thead style="background:#f8f9fa;"><tr><th style="padding:10px;text-align:left;border-bottom:2px solid #dee2e6;">Reactivo</th><th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;">Cantidad</th><th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;">Unidad</th><th style="padding:10px;text-align:left;border-bottom:2px solid #dee2e6;">Factura</th><th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;width:70px">Acc.</th></tr></thead><tbody>';
            resp.ingresos.forEach(function(ing){
                var iid=ing.Id_Ingreso;
                html+='<tr id="ing-row-'+iid+'" style="border-bottom:1px solid #f0f0f0;">'
                    +'<td style="padding:10px;">'+htmlEscape(ing.Reactivo_Nombre||'-')+'</td>'
                    +'<td id="ing-cant-disp-'+iid+'" style="padding:10px;text-align:center;font-weight:600;color:#31ce36;">'+(ing.Cantidad||0)+'</td>'
                    +'<td style="padding:10px;text-align:center;">'+htmlEscape(ing.Unidad_Medida||'N/A')+'</td>'
                    +'<td style="padding:10px;">'+htmlEscape(ing.Factura_Referencia||'S/N')+'</td>'
                    +'<td style="padding:10px;text-align:center;"><button type="button" class="btn btn-xs btn-ghost-warning" onclick="editarCantIngreso('+iid+','+parseFloat(ing.Cantidad||0)+')" title="Editar cantidad"><i class="ti ti-edit"></i></button></td>'
                    +'</tr>';
            });
            html+='</tbody></table></div>';
            Swal.fire({title:'Ingresos - '+fechaDisplay,html:html,icon:'success',confirmButtonText:'Cerrar',width:'750px'});
        }else{Swal.fire({title:'Ingresos - '+fechaDisplay,html:'<p style="color:#9ca3af;">No hay ingresos registrados</p>',icon:'info',confirmButtonText:'Cerrar'});}
    }});
}

function editarCantIngreso(idIngreso, cantActual) {
    Swal.fire({
        title: 'Editar ingreso #'+idIngreso,
        input: 'number',
        inputValue: cantActual,
        inputAttributes: { min: 0.01, step: 0.01 },
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        customClass: { title: 'fs-6 fw-semibold', popup: 'p-3' },
        inputValidator: function(v) { if (!v||parseFloat(v)<=0) return 'Ingrese una cantidad mayor a 0'; }
    }).then(function(r) {
        if (r.isConfirmed) {
            $.ajax({
                url: API_REACTIVO + '?action=editar_ingreso', method: 'POST', contentType: 'application/json', dataType: 'json',
                data: JSON.stringify({ Id_Ingreso: idIngreso, Cantidad: parseFloat(r.value) }),
                success: function(resp) {
                    if (resp.success) {
                        var cell = document.getElementById('ing-cant-disp-'+idIngreso);
                        if (cell) cell.textContent = parseFloat(r.value);
                        Swal.fire({ title:'Actualizado', icon:'success', timer:1200, showConfirmButton:false });
                    } else { Swal.fire('Error', resp.message, 'error'); }
                },
                error: function(xhr) { Swal.fire('Error', (xhr.responseJSON&&xhr.responseJSON.message)||'Error de conexion', 'error'); }
            });
        }
    });
}

function mostrarDetallesSalida(fecha,idReactivo,nombreReactivo){
    $.ajax({url:API_REACTIVO+'?action=obtener_detalles_salida&fecha='+encodeURIComponent(fecha)+'&id_reactivo='+encodeURIComponent(idReactivo||0),method:'GET',dataType:'json',success:function(resp){
        var fechaDisplay=fmtFecha(fecha);
        if(resp.success&&resp.salidas&&resp.salidas.length>0){
            var escHtml=function(t){return String(t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');};
            var grupos={},ordenGrupos=[];
            resp.salidas.forEach(function(sal){
                var label=String(sal.Segmento_Principal||'').trim()||'Otros consumos';
                var gKey=label.toLowerCase();
                var mKey=String(sal.Segmento_Secundario||'').trim()||'Movimiento #'+(sal.Id_Movimiento||0);
                var cant=Number(sal.Cantidad||0);
                var unidad=sal.Unidad_Medida||'UND';
                if(!grupos[gKey]){grupos[gKey]={label:label,unidad:unidad,total:0,children:{},orderChildren:[]};ordenGrupos.push(gKey);}
                grupos[gKey].total+=cant;
                if(!grupos[gKey].children[mKey]){grupos[gKey].children[mKey]={total:0,detalles:[],idMovimiento:sal.Id_Movimiento,concepto:sal.Concepto||'',esManual:(sal.Id_Muestra_Producto==null||sal.Id_Muestra_Producto===undefined||sal.Id_Muestra_Producto==='')};grupos[gKey].orderChildren.push(mKey);}
                grupos[gKey].children[mKey].total+=cant;
                var parts=[];
                if(sal.Tipo_Detalle)parts.push('Tipo: '+sal.Tipo_Detalle);
                if(sal.Producto_Nombre)parts.push('Producto: '+sal.Producto_Nombre);
                if(sal.Concepto)parts.push('Concepto: '+sal.Concepto);
                grupos[gKey].children[mKey].detalles.push(parts.join(' | '));
            });
            var html='<div style="text-align:left;font-size:13px;"><div style="max-height:420px;overflow:auto;border:1px solid #e5e7eb;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:600px;"><thead style="background:#f8f9fa;position:sticky;top:0;z-index:1;"><tr><th style="padding:10px;width:40px;text-align:center;border-bottom:2px solid #dee2e6;">+</th><th style="padding:10px;text-align:left;border-bottom:2px solid #dee2e6;">Nivel</th><th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;">Cantidad</th><th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;">Unidad</th><th style="padding:10px;text-align:left;border-bottom:2px solid #dee2e6;">Detalle</th><th style="padding:10px;width:75px;text-align:center;border-bottom:2px solid #dee2e6;">Acc.</th></tr></thead><tbody>';
            ordenGrupos.forEach(function(gKey,idx){
                var g=grupos[gKey];var gid='grp_'+idx;
                html+='<tr style="background:#fff8f1;border-bottom:1px solid #f0f0f0;"><td style="padding:8px;text-align:center;"><button type="button" class="toggle-group" data-group="'+gid+'" style="border:1px solid #f97316;color:#f97316;background:#fff;border-radius:4px;width:24px;height:24px;line-height:20px;font-weight:700;">+</button></td><td style="padding:10px;font-weight:600;">'+escHtml(g.label)+'</td><td style="padding:10px;text-align:center;font-weight:700;color:#f97316;">-'+g.total.toFixed(2)+'</td><td style="padding:10px;text-align:center;">'+escHtml(g.unidad)+'</td><td style="padding:10px;color:#6b7280;">Total por nivel</td><td></td></tr>';
                g.orderChildren.forEach(function(mKey){
                    var c=g.children[mKey];var idMov=c.idMovimiento;
                    var accBtns='';
                    if(idMov){
                        accBtns+='<button type="button" class="btn btn-xs btn-ghost-warning" onclick="editarCantSalida('+idMov+','+c.total+',\''+escHtml(c.concepto||'')+'\')"><i class="ti ti-edit"></i></button>';
                        if(c.esManual) accBtns+=' <button type="button" class="btn btn-xs btn-ghost-danger" onclick="eliminarSalida('+idMov+')" title="Eliminar"><i class="ti ti-trash"></i></button>';
                    }
                    html+='<tr class="child-row '+gid+'" id="sal-row-'+idMov+'" style="display:none;border-bottom:1px solid #f3f4f6;"><td style="padding:8px;text-align:center;color:#9ca3af;">-</td><td style="padding:10px 10px 10px 24px;">'+escHtml(mKey)+'</td><td id="sal-cant-disp-'+idMov+'" style="padding:10px;text-align:center;font-weight:600;color:#f97316;">-'+c.total.toFixed(2)+'</td><td style="padding:10px;text-align:center;">'+escHtml(g.unidad)+'</td><td style="padding:10px;color:#6b7280;">'+escHtml(c.detalles[0]||'')+'</td><td style="padding:8px;text-align:center;">'+accBtns+'</td></tr>';
                });
            });
            html+='</tbody></table></div></div>';
            Swal.fire({title:'Salidas - '+fechaDisplay+(nombreReactivo?' ('+htmlEscape(nombreReactivo)+')':''),html:html,icon:'info',confirmButtonText:'Cerrar',width:'900px',didOpen:function(){Swal.getPopup().querySelectorAll('.toggle-group').forEach(function(btn){btn.addEventListener('click',function(){var grp=this.getAttribute('data-group');var rows=Swal.getPopup().querySelectorAll('.child-row.'+grp);var open=this.textContent.trim()==='-';rows.forEach(function(r){r.style.display=open?'none':'table-row';});this.textContent=open?'+':'-';});});}});
        }else{Swal.fire({title:'Salidas - '+fechaDisplay,html:'<p style="color:#9ca3af;">No hay salidas registradas</p>',icon:'info',confirmButtonText:'Cerrar'});}
    }});
}

function eliminarSalida(idMovimiento) {
    Swal.fire({
        title: '¿Eliminar movimiento?',
        text: 'Se eliminará la salida #'+idMovimiento+' y el stock será restaurado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        customClass: { title: 'fs-6 fw-semibold', popup: 'p-3' }
    }).then(function(r) {
        if (r.isConfirmed) {
            $.ajax({
                url: API_REACTIVO + '?action=eliminar_salida&id=' + idMovimiento,
                method: 'GET', dataType: 'json',
                success: function(resp) {
                    if (resp.success) {
                        var row = document.getElementById('sal-row-'+idMovimiento);
                        if (row) row.remove();
                        Swal.fire({ title:'Eliminado', icon:'success', timer:1200, showConfirmButton:false });
                    } else { Swal.fire('Error', resp.message, 'error'); }
                },
                error: function(xhr) { Swal.fire('Error', (xhr.responseJSON&&xhr.responseJSON.message)||'Error de conexion', 'error'); }
            });
        }
    });
}

function editarCantSalida(idMovimiento, cantActual, conceptoActual) {
    Swal.fire({
        title: 'Editar salida #'+idMovimiento,
        html: '<div class="mb-2"><label style="font-size:12px;font-weight:600;">Nueva cantidad</label>'
            + '<input id="sal-edit-cant" type="number" min="0.01" step="0.01" value="'+cantActual+'" class="swal2-input" style="width:100%"></div>'
            + '<div><label style="font-size:12px;font-weight:600;">Concepto</label>'
            + '<input id="sal-edit-concepto" type="text" value="'+htmlEscape(conceptoActual)+'" class="swal2-input" style="width:100%"></div>',
        showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar',
        customClass: { title: 'fs-6 fw-semibold', popup: 'p-3' },
        preConfirm: function() {
            var cant = parseFloat(document.getElementById('sal-edit-cant').value);
            var conc = document.getElementById('sal-edit-concepto').value;
            if (!cant || cant <= 0) { Swal.showValidationMessage('Cantidad debe ser mayor a 0'); return false; }
            return { cantidad: cant, concepto: conc };
        }
    }).then(function(r) {
        if (r.isConfirmed) {
            $.ajax({
                url: API_REACTIVO + '?action=editar_salida', method: 'POST', contentType: 'application/json', dataType: 'json',
                data: JSON.stringify({ Id_Movimiento: idMovimiento, Cantidad: r.value.cantidad, Concepto: r.value.concepto }),
                success: function(resp) {
                    if (resp.success) {
                        var cell = document.getElementById('sal-cant-disp-'+idMovimiento);
                        if (cell) cell.textContent = '-'+r.value.cantidad.toFixed(2);
                        Swal.fire({ title:'Actualizado', icon:'success', timer:1200, showConfirmButton:false });
                    } else { Swal.fire('Error', resp.message, 'error'); }
                },
                error: function(xhr) { Swal.fire('Error', (xhr.responseJSON&&xhr.responseJSON.message)||'Error de conexion', 'error'); }
            });
        }
    });
}
</script>
