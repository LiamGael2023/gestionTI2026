<!-- ============================================================
     ACTIVOS.PHP — Gestión de Activos (actualizado)
============================================================ -->
<style>
/* =============================================================
   CUSTOM SELECT  (.cs-*)
============================================================= */
.cs-wrap {
    position: relative;
    width: 100%;
    font-size: 0.875rem;
}
.cs-display {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    padding: .375rem .75rem;
    min-height: 36px;
    background: #fff;
    border: 1px solid var(--tblr-border-color, #d0d5dd);
    border-radius: var(--tblr-border-radius, .375rem);
    cursor: pointer;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.cs-display:hover { border-color: var(--tblr-primary, #0054a6); }
.cs-wrap.cs-open .cs-display,
.cs-display:focus {
    border-color: var(--tblr-primary, #0054a6);
    box-shadow: 0 0 0 .2rem rgba(var(--tblr-primary-rgb,0,84,166),.15);
}
.cs-text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.4;
}
.cs-text.placeholder-text { color: #9ca3af; }
.cs-arrow {
    flex-shrink: 0;
    color: #6c757d;
    transition: transform .2s;
}
.cs-wrap.cs-open .cs-arrow { transform: rotate(180deg); }
.cs-panel {
    display: none;
    position: absolute;
    left: 0;
    right: 0;
    z-index: 1060;
    background: #fff;
    border: 1px solid var(--tblr-border-color, #d0d5dd);
    border-radius: var(--tblr-border-radius, .375rem);
    box-shadow: 0 4px 20px rgba(0,0,0,.12);
    overflow: hidden;
}
.cs-wrap.cs-open .cs-panel { display: block; }
.cs-search-row {
    display: flex;
    align-items: center;
    gap: .4rem;
    padding: .4rem .65rem;
    border-bottom: 1px solid var(--tblr-border-color, #e6ebf1);
    background: var(--tblr-bg-surface-secondary, #f8fafc);
}
.cs-search {
    border: none;
    outline: none;
    background: transparent;
    font-size: .8rem;
    width: 100%;
    padding: 0;
    color: #374151;
}
.cs-list {
    list-style: none;
    margin: 0;
    padding: .2rem 0;
    max-height: 190px;
    overflow-y: auto;
}
.cs-list li {
    padding: .38rem .75rem;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background .1s;
}
.cs-list li:hover,
.cs-list li.cs-selected {
    background: var(--tblr-primary-lt, #e7f0ff);
    color: var(--tblr-primary, #0054a6);
}
.cs-list li.cs-selected { font-weight: 600; }
.cs-list li.cs-placeholder-item { color: #9ca3af; font-style: italic; }
.cs-list li.cs-empty { color: #9ca3af; font-style: italic; cursor: default; }
.cs-list li.cs-empty:hover { background: none; }

/* =============================================================
   LAYOUT MODAL
============================================================= */
.modal-body-scroll {
    overflow-y: auto;
    max-height: calc(100vh - 240px);
}
.seccion-card {
    border: 1px solid var(--tblr-border-color, #e6ebf1);
    border-left: 4px solid var(--tblr-primary, #0054a6);
    border-radius: .5rem;
    background: #fff;
}
.seccion-header {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .65rem 1.1rem .45rem;
    border-bottom: 1px solid var(--tblr-border-color-light, #f0f3f8);
}
.seccion-titulo {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.seccion-body { padding: .9rem 1.1rem 1rem; }

.auditoria-box {
    background: var(--tblr-bg-surface-secondary, #f8fafc);
    border: 1px dashed var(--tblr-border-color, #d0d5dd);
    border-radius: .5rem;
    padding: .8rem 1rem;
}

.panel-caract {
    background: var(--tblr-bg-surface-secondary, #f8fafc);
    border: 1px solid var(--tblr-border-color, #d0d5dd);
    border-radius: .5rem;
    padding: .8rem .9rem;
}

.tabla-caract-wrap {
    border: 1px solid var(--tblr-border-color, #d0d5dd);
    border-radius: .5rem;
    overflow: hidden;
    max-height: 260px;
    overflow-y: auto;
}

/* ── MODAL ARMAR EQUIPO ── */
.componente-card {
    border: 1px solid var(--tblr-border-color, #e6ebf1);
    border-radius: .5rem;
    background: #fff;
    padding: .6rem .9rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    transition: box-shadow .15s;
}
.componente-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.componente-icon {
    width: 36px;
    height: 36px;
    border-radius: .35rem;
    background: var(--tblr-primary-lt, #e7f0ff);
    color: var(--tblr-primary, #0054a6);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}
.componentes-lista {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    max-height: 320px;
    overflow-y: auto;
}
.componentes-vacio {
    text-align: center;
    color: #9ca3af;
    font-style: italic;
    padding: 2rem 1rem;
    font-size: .85rem;
}

/* ── TABS TIPO ACTIVO ── */
.tipo-tab-nav {
    display: flex;
    gap: .3rem;
    flex-wrap: wrap;
    padding: .75rem 1rem .5rem;
    border-bottom: 1px solid var(--tblr-border-color);
    background: #f8fafc;
}
.tipo-tab-btn {
    font-size: .75rem;
    font-weight: 600;
    padding: .3rem .75rem;
    border-radius: 20px;
    border: 1px solid var(--tblr-border-color);
    background: #fff;
    color: #64748b;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.tipo-tab-btn:hover { border-color: var(--tblr-primary); color: var(--tblr-primary); }
.tipo-tab-btn.active {
    background: var(--tblr-primary);
    color: #fff;
    border-color: var(--tblr-primary);
}

@media (max-width: 991px) {
    .modal-body-scroll { max-height: calc(100vh - 180px); }
}
</style>

<body>
<div class="page">
  <?php include __DIR__ . '/_submenu.php'; ?>
  <div class="page-wrapper">
    <div class="container-xl">

      <div class="page-header d-print-none">
        <div class="row align-items-center">
          <div class="col">
            <div class="page-pretitle">Inventario</div>
            <h2 class="page-title">Gestión de Activos</h2>
          </div>
          <div class="col-auto ms-auto">
            <button class="btn btn-primary d-flex align-items-center gap-2"
                    data-bs-toggle="modal" data-bs-target="#modalAgregarActivo">
              <i class="ti ti-plus"></i>
              <span>Nuevo Activo</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ── TABS navegación (card independiente) ── -->
      <style>
        .tabs-scroll-wrap { overflow-x:auto; overflow-y:hidden; -webkit-overflow-scrolling:touch; scrollbar-width:none; -ms-overflow-style:none; }
        .tabs-scroll-wrap::-webkit-scrollbar { display:none; }
        .tabs-scroll-wrap .nav-tabs { flex-wrap:nowrap; min-width:max-content; border-bottom:none; }
        .tabs-scroll-wrap .nav-link { display:flex; align-items:center; gap:.35rem; white-space:nowrap; padding:.6rem 1rem; }
        @media (max-width:575.98px) {
          .tabs-scroll-wrap .nav-link { padding:.55rem .75rem; }
          .tabs-scroll-wrap .tab-txt  { display:none; }
        }
      </style>

      

      <!-- ── CARD CONTENIDO (separado del de tabs) ── -->
      <div class="card mb-4" style="border-top-left-radius:0;border-top-right-radius:0;">

        <!-- Filtro tipo (desktop) -->
        <div class="d-none d-md-flex align-items-center gap-2 flex-wrap px-3 py-2 border-bottom" id="tipoTabNav">
          <span class="text-muted small fw-semibold me-1">Filtrar:</span>
          <button class="btn btn-sm btn-primary tipo-tab-btn" data-tipo="todos">Todos</button>
          <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="compuesto"><i class="ti ti-cpu me-1"></i>Compuestos</button>
          <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="periferico"><i class="ti ti-keyboard me-1"></i>Periféricos</button>
          <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="componente"><i class="ti ti-puzzle me-1"></i>Componentes</button>
          <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="software"><i class="ti ti-code me-1"></i>Software</button>
          <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="otros"><i class="ti ti-package me-1"></i>Otros</button>
          <div class="ms-auto d-flex align-items-center gap-2">
            <span class="text-muted small">Mostrar</span>
            <select id="dtPageLength" class="form-select form-select-sm" style="width:auto">
              <option value="5">5</option><option value="10" selected>10</option>
              <option value="25">25</option><option value="50">50</option>
            </select>
            <span class="text-muted small">registros</span>
            <button class="btn btn-sm btn-outline-success" id="dtBtnExcel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</button>
            <button class="btn btn-sm btn-outline-danger"  id="dtBtnPdf"><i class="ti ti-file-description me-1"></i>PDF</button>
            <div class="input-group input-group-sm" style="width:210px">
              <span class="input-group-text"><i class="ti ti-search"></i></span>
              <input type="text" id="dtSearch" class="form-control" placeholder="Buscar activo...">
            </div>
          </div>
        </div>

        <!-- Buscador móvil -->
        <div class="d-md-none px-3 pt-3 pb-2">
          <div class="d-flex gap-2 mb-2 flex-wrap" id="tipoTabNavMobile">
            <button class="btn btn-sm btn-primary tipo-tab-btn" data-tipo="todos">Todos</button>
            <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="compuesto"><i class="ti ti-cpu"></i></button>
            <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="periferico"><i class="ti ti-keyboard"></i></button>
            <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="componente"><i class="ti ti-puzzle"></i></button>
            <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="software"><i class="ti ti-code"></i></button>
            <button class="btn btn-sm btn-outline-secondary tipo-tab-btn" data-tipo="otros"><i class="ti ti-package"></i></button>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
            <input type="text" id="mobileSearch" class="form-control border-start-0 ps-0" placeholder="Buscar activo...">
          </div>
        </div>

        <!-- Tabla desktop -->
        <div class="d-none d-md-block">
          <div class="table-responsive">
            <table id="tablaActivos" class="table table-vcenter table-hover card-table mb-0">
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Categoría</th>
                  <th>N° Serie / Licencia</th>
                  <th>Cód. Patrimonial</th>
                  <th>Características</th>
                  <th>Estado</th>
                  <th class="d-none d-md-table-cell">Registrado Por</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $activos = ActivosController::ctrMostrarActivo(null, null);
                if (!is_array($activos)) $activos = [];

                // Mapa de estado → badge color
                $estadoBadge = [
                    'disponible'  => ['bg-success-lt text-success',  'Disponible'],
                    'asignado'    => ['bg-primary-lt text-primary',   'Asignado'],
                    'inoperativo' => ['bg-danger-lt text-danger',     'Inoperativo'],
                    'reparacion'  => ['bg-warning-lt text-warning',   'Reparación'],
                    'baja'        => ['bg-dark-lt text-secondary',    'Baja'],
                    'expirado'    => ['bg-purple-lt text-purple',     'Expirado'],
                ];

                foreach ($activos as $value) {
                    $icono        = !empty($value["iconoActivo"]) ? $value["iconoActivo"] : 'ti-package';
                    $esCompuesto  = intval($value["esCompuesto"]  ?? 0);
                    $esPeriferico = intval($value["esPeriferico"] ?? 0);
                    $esComponente = intval($value["esComponente"] ?? 0);
                    $estado       = strtolower(trim($value["estado"] ?? 'disponible'));
                    // Software: sin ninguno de los 3 flags
                    $esSoftware   = !$esCompuesto && !$esPeriferico && !$esComponente;

                    // Determinar tipo visual
                    if ($esCompuesto) {
                        $tipoKey   = 'compuesto';
                        $tipoBadge = '<span class="badge bg-azure-lt text-azure"><i class="ti ti-cpu me-1"></i>Compuesto</span>';
                    } elseif ($esPeriferico) {
                        $tipoKey   = 'periferico';
                        $tipoBadge = '<span class="badge bg-teal-lt text-teal"><i class="ti ti-keyboard me-1"></i>Periférico</span>';
                    } elseif ($esComponente) {
                        $tipoKey   = 'componente';
                        $tipoBadge = '<span class="badge bg-orange-lt text-orange"><i class="ti ti-puzzle me-1"></i>Componente</span>';
                    } elseif ($esSoftware) {
                        $tipoKey   = 'software';
                        $tipoBadge = '<span class="badge bg-purple-lt text-purple"><i class="ti ti-code me-1"></i>'
                                   . htmlspecialchars($value["nombreActivo"] ?? 'Software') . '</span>';
                    } else {
                        $tipoKey   = 'otros';
                        $tipoBadge = '<span class="badge bg-secondary-lt text-secondary"><i class="ti ti-package me-1"></i>Otros</span>';
                    }

                    // Badge estado
                    [$estadoClass, $estadoLabel] = $estadoBadge[$estado] ?? ['bg-secondary-lt text-secondary', ucfirst($estado)];

                    // Columna serie / licencia
                    $serieCol = '';
                    if (!empty($value["numeroSerie"])) {
                        $serieCol = htmlspecialchars($value["numeroSerie"]);
                    }
                    if (!empty($value["codigoLicencia"])) {
                        $serieCol .= ($serieCol ? '<br>' : '') .
                            '<span class="badge badge-outline text-purple small" title="Cód. Licencia">' .
                            '<i class="ti ti-key me-1"></i>' .
                            htmlspecialchars($value["codigoLicencia"]) . '</span>';
                    }

                    // Botón "Armar Activo" solo si compuesto
                    $btnArmar = '';
                    if ($esCompuesto) {
                        $btnArmar = '
                        <button type="button"
                          class="btn btn-sm btn-icon btn-outline-success btnArmarActivo"
                          data-id="' . $value["idActivo"] . '"
                          data-nombre="' . htmlspecialchars($value["nombreActivo"] ?? '', ENT_QUOTES) . '"
                          data-icono="' . $icono . '"
                          title="Armar equipo / componentes">
                          <i class="ti ti-tools"></i>
                        </button>';
                    }

                    echo '
                    <tr data-tipo="' . $tipoKey . '">
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ' . $icono . ' text-primary fs-3"></i>
                          <div>
                            <span class="fw-medium">' . htmlspecialchars($value["nombreActivo"] ?? '') . '</span>
                          </div>
                        </div>
                      </td>
                      <td>' . $tipoBadge . '</td>
                      <td class="small">' . ($serieCol ?: '<span class="text-muted">—</span>') . '</td>
                      <td>' . htmlspecialchars($value["codigoPatrimonial"] ?? '') . '</td>
                      <td class="small text-muted">' . htmlspecialchars($value["caracteristicas"] ?? '') . '</td>
                      <td><span class="badge ' . $estadoClass . '">' . $estadoLabel . '</span></td>
                      <td class="d-none d-md-table-cell">
                        <span class="badge bg-blue-lt px-2 py-1 fw-medium">' . '<i class="ti ti-user me-1"></i>' . htmlspecialchars(trim($value["nombreUsuarioRegistro"] ?? '') ?: ('ID '.($value["idUsuarioRegistro"] ?? '—')), ENT_QUOTES, 'UTF-8') . '</span>
                      </td>
                      
                      <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                          ' . $btnArmar . '
                          <button type="button"
                            class="btn btn-sm btn-icon btn-outline-primary btnEditarActivo"
                            data-id="' . $value["idActivo"] . '" title="Editar">
                            <i class="ti ti-edit"></i>
                          </button>
                          <button type="button"
                            class="btn btn-sm btn-icon btn-outline-danger btnEliminarActivo"
                            data-id="' . $value["idActivo"] . '"
                            data-nombre="' . htmlspecialchars($value["nombreActivo"] ?? '', ENT_QUOTES) . '"
                            data-es-padre="' . ($esCompuesto ? '1' : '0') . '"
                            title="Eliminar">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div><!-- /tabla desktop -->

        <!-- ── CARDS MÓVIL (< md) ── -->
        <div class="d-md-none" id="mobileListActivos">
          <?php if (!empty($activos)): ?>
            <?php foreach ($activos as $value):
              $icono        = !empty($value['iconoActivo']) ? $value['iconoActivo'] : 'ti-package';
              $esCompuesto  = intval($value['esCompuesto']  ?? 0);
              $esPeriferico = intval($value['esPeriferico'] ?? 0);
              $esComponente = intval($value['esComponente'] ?? 0);
              $estado       = strtolower(trim($value['estado'] ?? 'disponible'));
              $desc         = htmlspecialchars($value['nombreActivo'] ?? '', ENT_QUOTES, 'UTF-8');
              $nomReg       = trim($value['nombreUsuarioRegistro'] ?? '');
              if (!$nomReg) $nomReg = 'ID ' . ($value['idUsuarioRegistro'] ?? '—');

              $estadoBadge = [
                'disponible'  => ['bg-success-lt text-success', 'Disponible'],
                'asignado'    => ['bg-primary-lt text-primary',  'Asignado'],
                'inoperativo' => ['bg-danger-lt text-danger',    'Inoperativo'],
                'reparacion'  => ['bg-warning-lt text-warning',  'Reparación'],
                'baja'        => ['bg-dark-lt text-secondary',   'Baja'],
                'expirado'    => ['bg-purple-lt text-purple',    'Expirado'],
              ];
              [$estClass, $estLabel] = $estadoBadge[$estado] ?? ['bg-secondary-lt text-secondary', ucfirst($estado)];

              if ($esCompuesto)       { $tipoKey='compuesto';  $tipoBadge='<span class="badge bg-azure-lt text-azure"><i class="ti ti-cpu me-1"></i>Compuesto</span>'; }
              elseif ($esPeriferico)  { $tipoKey='periferico'; $tipoBadge='<span class="badge bg-teal-lt text-teal"><i class="ti ti-keyboard me-1"></i>Periférico</span>'; }
              elseif ($esComponente)  { $tipoKey='componente'; $tipoBadge='<span class="badge bg-orange-lt text-orange"><i class="ti ti-puzzle me-1"></i>Componente</span>'; }
              else                    { $tipoKey='otros';       $tipoBadge='<span class="badge bg-secondary-lt text-secondary"><i class="ti ti-package me-1"></i>Otros</span>'; }
            ?>
            <div class="mobile-item border-bottom" data-tipo="<?= $tipoKey ?>"
                 data-nombre="<?= strtolower($desc) ?>">
              <div class="d-flex gap-3 px-3 py-3">
                <div class="flex-shrink-0">
                  <span class="avatar avatar-md bg-primary-lt text-primary">
                    <i class="ti <?= $icono ?> fs-4"></i>
                  </span>
                </div>
                <div style="min-width:0;flex:1">
                  <!-- Nombre + estado -->
                  <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                    <div class="fw-semibold lh-sm" style="word-break:break-word"><?= $desc ?></div>
                    <span class="flex-shrink-0 badge <?= $estClass ?>"><?= $estLabel ?></span>
                  </div>
                  <!-- Tipo + serie -->
                  <div class="d-flex flex-wrap gap-1 mb-1">
                    <?= $tipoBadge ?>
                    <?php if (!empty($value['numeroSerie'])): ?>
                      <span class="badge badge-outline text-muted small">
                        <?= htmlspecialchars($value['numeroSerie'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    <?php endif; ?>
                    <?php if (!empty($value['codigoPatrimonial'])): ?>
                      <span class="badge badge-outline text-muted small">
                        <?= htmlspecialchars($value['codigoPatrimonial'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <!-- Características -->
                  <?php if (!empty($value['caracteristicas'])): ?>
                  <div class="text-muted mb-1" style="font-size:.78rem">
                    <?= htmlspecialchars($value['caracteristicas'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <?php endif; ?>
                  <!-- Usuario + botones -->
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted" style="font-size:.78rem">
                      <i class="ti ti-user me-1"></i><?= htmlspecialchars($nomReg, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                      <?php if ($esCompuesto): ?>
                      <button type="button" class="btn btn-sm btn-ghost-success btnArmarActivo"
                              data-id="<?= $value['idActivo'] ?>"
                              data-nombre="<?= $desc ?>" data-icono="<?= $icono ?>"
                              title="Armar equipo">
                        <i class="ti ti-tools"></i>
                      </button>
                      <?php endif; ?>
                      <button type="button" class="btn btn-sm btn-ghost-primary btnEditarActivo"
                              data-id="<?= $value['idActivo'] ?>">
                        <i class="ti ti-edit"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-ghost-danger btnEliminarActivo"
                              data-id="<?= $value['idActivo'] ?>"
                              data-nombre="<?= $desc ?>"
                              data-es-padre="<?= $esCompuesto ? '1' : '0' ?>">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>

            <div id="mobileNoResultsActivos" class="text-center py-5 text-muted d-none">
              <i class="ti ti-search-off fs-1 d-block mb-2 opacity-50"></i>
              <p class="mb-0">No se encontraron resultados.</p>
            </div>

            <!-- Paginación móvil -->
            <div id="mobilePaginationActivos"
                 class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
              <span id="mobilePageInfoActivos" class="text-muted small"></span>
              <div class="d-flex gap-1">
                <button id="mobilePrevBtnActivos"
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" disabled>
                  <i class="ti ti-chevron-left"></i> Anterior
                </button>
                <button id="mobileNextBtnActivos"
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" disabled>
                  Siguiente <i class="ti ti-chevron-right"></i>
                </button>
              </div>
            </div>

          <?php else: ?>
            <div class="text-center py-5 text-muted">
              <i class="ti ti-inbox fs-1 d-block mb-2 opacity-50"></i>
              <p class="mb-0">No hay activos registrados.</p>
            </div>
          <?php endif; ?>
        </div><!-- /mobileListActivos -->

      </div><!-- /card contenido -->

    </div>
  </div>
</div>
</body>


<!-- ============================================================
     MODAL AGREGAR ACTIVO
============================================================ -->
<div class="modal modal-blur fade" id="modalAgregarActivo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-plus fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Nuevo Activo</h5>
            <small class="text-muted">Complete los datos del nuevo activo</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formNuevoActivo" novalidate>
        <div class="modal-body-scroll px-4 py-3">
          <div class="row g-3">

            <!-- ══════ IZQUIERDA ══════ -->
            <div class="col-lg-6 d-flex flex-column gap-3">
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-info-circle text-primary"></i>
                  <span class="seccion-titulo text-primary">Información General</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">

                    <!-- 1. Tipo Activo (select principal que dispara adaptarModal) -->
                    <div class="col-12">
                      <label class="form-label small fw-semibold">
                        Tipo Activo <span class="text-danger">*</span>
                      </label>
                      <select id="nuevoIdTipoActivo" name="nuevoIdTipoActivo" required style="display:none">
                        <div id="nuevoTipoActivoHint" style="display:none; transition: all 0.3s ease;"></div>
                        <option value="">Seleccionar tipo de activo...</option>
                      </select>
                    </div>

                    <!-- 2. Estado (las opciones se adaptan según el tipo elegido) -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Estado <span class="text-danger">*</span></label>
                      <select id="nuevoEstado" name="nuevoEstado" class="form-select">
                        <option value="disponible">Disponible</option>
                        <option value="asignado">Asignado</option>
                        <option value="inoperativo">Inoperativo</option>
                        <option value="reparacion">Reparación</option>
                        <option value="baja">Baja</option>
                        <!-- Visible solo para Software -->
                        <option value="expirado" class="opt-estado-expirado" style="display:none">Expirado</option>
                      </select>
                    </div>
                    <div class="col-md-6"><!-- espacio --></div>

                    <!-- 3. Código Patrimonial: visible Compuesto/Periférico/Otros | oculto Componente/Software -->
                    <div class="col-md-6 campo-codigoPatrimonial">
                      <label class="form-label small fw-semibold">
                        Código Patrimonial <span class="text-danger">*</span>
                      </label>
                      <input id="nuevoCodigoPatrimonial" name="nuevoCodigoPatrimonial"
                             type="text" class="form-control" placeholder="CP-2024-001">
                    </div>

                    <!-- 4. Número de Serie: visible todos excepto Software -->
                    <div class="col-md-6 campo-numeroSerie">
                      <label class="form-label small fw-semibold">N° Serie</label>
                      <input id="nuevoNumeroSerie" name="nuevoNumeroSerie"
                             type="text" class="form-control" placeholder="SN-XJK9201LH">
                    </div>

                    <!-- 5. Código Licencia: visible SOLO Software (oculto por defecto) -->
                    <div class="col-12 campo-codigoLicencia" style="display:none">
                      <label class="form-label small fw-semibold">
                        Código de Licencia <span class="text-danger">*</span>
                      </label>
                      <input id="nuevoCodigoLicencia" name="nuevoCodigoLicencia"
                             type="text" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXX">
                    </div>

                  </div>
                </div>
              </div>

              <!-- 6. Fechas: etiquetas cambian para Software (Garantía → Licencia) -->
              <div class="seccion-card" id="nuevoSeccionFechas">
                <div class="seccion-header">
                  <i class="ti ti-calendar text-primary"></i>
                  <span class="seccion-titulo text-primary" id="nuevoTituloFechas">Fechas y Garantía</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">
                    <div class="col-md-4 campo-fechaAdquisicion">
                      <label class="form-label small fw-semibold">Fecha Adquisición</label>
                      <input id="nuevoFechaAdquisicion" name="nuevoFechaAdquisicion"
                             type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold" id="nuevoLabelInicioGarantia">Inicio Garantía</label>
                      <input id="nuevoFechaInicioGarantia" name="nuevoFechaInicioGarantia"
                             type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold" id="nuevoLabelFinGarantia">Fin Garantía</label>
                      <input id="nuevoFechaFinGarantia" name="nuevoFechaFinGarantia"
                             type="date" class="form-control">
                    </div>
                  </div>
                </div>
              </div>

              <!-- 7. Auditoría -->
              <div class="auditoria-box">
                <div class="small fw-bold text-muted text-uppercase mb-2">
                  <i class="ti ti-shield-check me-1"></i>Auditoría
                </div>
                <div class="row g-2 small">
                  <div class="col-6"><div class="text-muted">Usuario Creación</div><div class="fw-semibold" id="nuevoUsuarioCreacion">--</div></div>
                  <div class="col-6"><div class="text-muted">Fecha Creación</div><div class="fw-semibold" id="nuevoFechaCreacion">--</div></div>
                  <div class="col-6 mt-1"><div class="text-muted">Últ. Modificación</div><div class="fw-semibold" id="nuevoUsuarioModificacion">--</div></div>
                  <div class="col-6 mt-1"><div class="text-muted">Fecha Modificación</div><div class="fw-semibold" id="nuevoFechaModificacion">--</div></div>
                </div>
              </div>
            </div>

            <!-- ══════ DERECHA ══════ -->
            <div class="col-lg-6 d-flex flex-column">
              <div class="seccion-card flex-grow-1 d-flex flex-column">
                <div class="seccion-header">
                  <i class="ti ti-settings text-primary"></i>
                  <span class="seccion-titulo text-primary">Características Técnicas</span>
                </div>
                <div class="seccion-body d-flex flex-column gap-3 flex-grow-1">
                  <div class="panel-caract">
                    <div class="row g-2 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Tipo</label>
                        <select id="nuevoTipoCaracteristica" style="display:none">
                          <option value="">Seleccionar tipo...</option>
                        </select>
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Valor</label>
                        <select id="nuevoValorCaracteristica" style="display:none">
                          <option value="">Seleccionar valor...</option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <button id="btnAgregarNuevaCaracteristica" type="button" class="btn btn-primary w-100">
                          <i class="ti ti-plus me-1"></i>Agregar
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="tabla-caract-wrap flex-grow-1">
                    <table id="tablaNuevoEquipoCaracteristicas" class="table table-hover align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th class="small text-uppercase fw-semibold">Tipo</th>
                          <th class="small text-uppercase fw-semibold">Valor</th>
                          <th class="text-end" width="60">Acción</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <input type="hidden" id="nuevoCaracteristicasIds" name="nuevoCaracteristicasIds">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>Guardar Activo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ============================================================
     MODAL EDITAR ACTIVO
============================================================ -->
<div class="modal modal-blur fade" id="modalEditarActivo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-edit fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Editar Activo</h5>
            <small class="text-muted">Modificación de información del activo</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formEditarActivo" novalidate>
        <div class="modal-body-scroll px-4 py-3">
          <div class="row g-3">

            <!-- ══════ IZQUIERDA ══════ -->
            <div class="col-lg-6 d-flex flex-column gap-3">
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-info-circle text-primary"></i>
                  <span class="seccion-titulo text-primary">Información General</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">

                    <!-- 1. Tipo Activo -->
                    <div class="col-12">
                      <label class="form-label small fw-semibold">
                        Tipo Activo <span class="text-danger">*</span>
                      </label>
                      <select id="editarIdTipoActivo" name="editarIdTipoActivo" required style="display:none">
                        
                        <option value="">Seleccionar tipo de activo...</option>
                      </select>
                    </div>

                    <!-- 2. Estado (las opciones se adaptan según el tipo) -->
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Estado <span class="text-danger">*</span></label>
                      <select id="editarEstado" name="editarEstado" class="form-select">
                        <option value="disponible">Disponible</option>
                        <option value="asignado">Asignado</option>
                        <option value="inoperativo">Inoperativo</option>
                        <option value="reparacion">Reparación</option>
                        <option value="baja">Baja</option>
                        <!-- Visible solo para Software -->
                        <option value="expirado" class="opt-estado-expirado-editar" style="display:none">Expirado</option>
                      </select>
                    </div>
                    <div class="col-md-6"><!-- espacio --></div>

                    <!-- 3. Código Patrimonial: visible Compuesto/Periférico/Otros | oculto Componente/Software -->
                    <div class="col-md-6 campo-codigoPatrimonial-editar">
                      <label class="form-label small fw-semibold">
                        Código Patrimonial <span class="text-danger">*</span>
                      </label>
                      <input id="editarCodigoPatrimonial" name="editarCodigoPatrimonial"
                             type="text" class="form-control">
                    </div>

                    <!-- 4. Número de Serie: visible todos excepto Software -->
                    <div class="col-md-6 campo-numeroSerie-editar">
                      <label class="form-label small fw-semibold">N° Serie</label>
                      <input id="editarNumeroSerie" name="editarNumeroSerie"
                             type="text" class="form-control">
                    </div>

                    <!-- 5. Código Licencia: visible SOLO Software -->
                    <div class="col-12 campo-codigoLicencia-editar" style="display:none">
                      <label class="form-label small fw-semibold">
                        Código de Licencia <span class="text-danger">*</span>
                      </label>
                      <input id="editarCodigoLicencia" name="editarCodigoLicencia"
                             type="text" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXX">
                    </div>

                  </div>
                </div>
              </div>

              <!-- 6. Fechas -->
              <div class="seccion-card" id="editarSeccionFechas">
                <div class="seccion-header">
                  <i class="ti ti-calendar text-primary"></i>
                  <span class="seccion-titulo text-primary" id="editarTituloFechas">Fechas y Garantía</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">
                    <div class="col-md-4 campo-fechaAdquisicion-editar">
                      <label class="form-label small fw-semibold">Fecha Adquisición</label>
                      <input id="editarFechaAdquisicion" name="editarFechaAdquisicion"
                             type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold" id="editarLabelInicioGarantia">Inicio Garantía</label>
                      <input id="editarFechaInicioGarantia" name="editarFechaInicioGarantia"
                             type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold" id="editarLabelFinGarantia">Fin Garantía</label>
                      <input id="editarFechaFinGarantia" name="editarFechaFinGarantia"
                             type="date" class="form-control">
                    </div>
                  </div>
                </div>
              </div>

              <!-- 7. Auditoría -->
              <div class="auditoria-box">
                <div class="small fw-bold text-muted text-uppercase mb-2">
                  <i class="ti ti-shield-check me-1"></i>Auditoría
                </div>
                <div class="row g-2 small">
                  <div class="col-6"><div class="text-muted">Usuario Creación</div><div class="fw-semibold" id="editarUsuarioCreacion">--</div></div>
                  <div class="col-6"><div class="text-muted">Fecha Creación</div><div class="fw-semibold" id="editarFechaCreacion">--</div></div>
                  <div class="col-6 mt-1"><div class="text-muted">Últ. Modificación</div><div class="fw-semibold" id="editarUsuarioModificacion">--</div></div>
                  <div class="col-6 mt-1"><div class="text-muted">Fecha Modificación</div><div class="fw-semibold" id="editarFechaModificacion">--</div></div>
                </div>
              </div>
            </div>

            <!-- ══════ DERECHA ══════ -->
            <div class="col-lg-6 d-flex flex-column">
              <div class="seccion-card flex-grow-1 d-flex flex-column">
                <div class="seccion-header">
                  <i class="ti ti-settings text-primary"></i>
                  <span class="seccion-titulo text-primary">Características Técnicas</span>
                </div>
                <div class="seccion-body d-flex flex-column gap-3 flex-grow-1">
                  <div class="panel-caract">
                    <div class="row g-2 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Tipo</label>
                        <select id="editarTipoCaracteristica" style="display:none">
                          <option value="">Seleccionar tipo...</option>
                        </select>
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Valor</label>
                        <select id="editarValorCaracteristica" style="display:none">
                          <option value="">Seleccionar valor...</option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <button id="btnAgregarEditarCaracteristica" type="button" class="btn btn-primary w-100">
                          <i class="ti ti-plus me-1"></i>Agregar
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="tabla-caract-wrap flex-grow-1">
                    <table id="tablaEditarEquipoCaracteristicas" class="table table-hover align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th class="small text-uppercase fw-semibold">Tipo</th>
                          <th class="small text-uppercase fw-semibold">Valor</th>
                          <th class="text-end" width="60">Acción</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <input type="hidden" id="editarIdActivo" name="editarIdActivo">
          <input type="hidden" id="editarCaracteristicasIds" name="editarCaracteristicasIds">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Actualizar Activo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- ==================================================
  MODAL ARMAR EQUIPO (agregar / quitar componentes)
     Solo se abre desde activos con esCompuesto = 1
============================================================ -->
<div class="modal modal-blur fade" id="modalArmarActivo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i id="armarIconoPadre" class="ti ti-tools fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Armar Activo</h5>
            <small class="text-muted">
              <span id="armarNombrePadre" class="fw-semibold text-primary"></span>
              — Gestión de componentes
            </small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body px-4 py-3">
        <div class="row g-3">

          <!-- IZQUIERDA: agregar componente -->
          <div class="col-lg-5">
            <div class="seccion-card h-100">
              <div class="seccion-header">
                <i class="ti ti-search text-primary"></i>
                <span class="seccion-titulo text-primary">Agregar Componente</span>
              </div>
              <div class="seccion-body d-flex flex-column gap-3">
                <div>
                  <label class="form-label small fw-semibold">
                    Buscar activo disponible
                    <span class="text-muted fw-normal">(sin padre asignado)</span>
                  </label>
                  <select id="armarComponenteSelect" style="display:none">
                    <option value="">Seleccionar componente...</option>
                  </select>
                </div>
                <button type="button" id="btnAgregarComponente" class="btn btn-primary w-100" disabled>
                  <i class="ti ti-plus me-1"></i>Agregar al equipo
                </button>

                <div id="armarComponenteInfo" style="display:none">
                  <div class="auditoria-box">
                    <div class="small fw-bold text-muted text-uppercase mb-2">
                      <i class="ti ti-info-circle me-1"></i>Componente seleccionado
                    </div>
                    <div class="small">
                      <div class="text-muted">Serie</div>
                      <div class="fw-semibold" id="armarInfoSerie">—</div>
                    </div>
                    <div class="small mt-1">
                      <div class="text-muted">Código Patrimonial</div>
                      <div class="fw-semibold" id="armarInfoCodigo">—</div>
                    </div>
                    <div class="small mt-1">
                      <div class="text-muted">Características</div>
                      <div class="fw-semibold" id="armarInfoCaract">—</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- DERECHA: componentes actuales -->
          <div class="col-lg-7">
            <div class="seccion-card h-100">
              <div class="seccion-header">
                <i class="ti ti-list text-primary"></i>
                <span class="seccion-titulo text-primary">Componentes actuales</span>
                <span class="badge bg-primary-lt text-primary ms-auto" id="armarContador">0</span>
              </div>
              <div class="seccion-body">
                <div id="armarListaComponentes" class="componentes-lista">
                  <div class="componentes-vacio">
                    <i class="ti ti-inbox fs-2 d-block mb-1"></i>
                    Sin componentes asignados
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer px-4 pb-4 pt-2"
           style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
        <input type="hidden" id="armarIdActivoPadre">
        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
      </div>

    </div>
  </div>
</div>


<!-- ════════ MODAL CONFIRMAR ELIMINACIÓN ════════ -->
<div class="modal modal-blur fade" id="modalConfirmarEliminarActivo" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2 text-danger">
          <i class="ti ti-alert-triangle"></i> Confirmar eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-1">¿Estás seguro de que deseas eliminar:</p>
        <p class="fw-bold mb-0" id="eliminarNombreActivo"></p>
        <p class="text-muted small mt-2 mb-0">Esta acción es reversible solo desde la base de datos.<br>
        No se puede eliminar si tiene componentes o activos asignados o está en una estación.</p>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminarActivo">
          <i class="ti ti-trash me-1"></i>Sí, eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<div id="toastContainerActivos" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="modules/inventario/views/js/activos.js"></script>