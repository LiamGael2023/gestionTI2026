<body>
  <?php
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }
  $activos = TipoActivosController::ctrMostrarActivos(null, null);
  if (!is_array($activos))
    $activos = [];
  ?>
  <div class="page">
    <?php include __DIR__ . '/_submenu.php'; ?>

    <div class="page-wrapper">
      <div class="container-xl">

        <!-- ══════════════════════════════════════
           PAGE HEADER
      ══════════════════════════════════════ -->
        <div class="page-header d-print-none">
          <div class="row align-items-center">
            <div class="col">
              <div class="page-pretitle">Inventario</div>
              <h2 class="page-title">Configuraciones</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
              <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#modalAgregarActivo">
                <i class="ti ti-plus"></i>
                <span>Agregar Tipo Activo</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ══════════════════════════════════════
           TABS  — card PROPIO, jamás tocado por DataTables
      ══════════════════════════════════════ -->
        <style>
          .tabs-scroll-wrap {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
          }

          .tabs-scroll-wrap::-webkit-scrollbar {
            display: none;
          }

          .tabs-scroll-wrap .nav-tabs {
            flex-wrap: nowrap;
            min-width: max-content;
            border-bottom: none;
          }

          .tabs-scroll-wrap .nav-link {
            display: flex;
            align-items: center;
            gap: .35rem;
            white-space: nowrap;
            padding: .6rem 1rem;
          }

          /* Móvil: oculta el texto, solo ícono */
          @media (max-width: 575.98px) {
            .tabs-scroll-wrap .nav-link {
              padding: .55rem .75rem;
            }

            .tabs-scroll-wrap .tab-txt {
              display: none;
            }
          }
        </style>

        <div class="card mb-0" style="border-bottom-left-radius:0;border-bottom-right-radius:0;border-bottom:none;">
          <div class="card-body p-0 tabs-scroll-wrap">
            <ul class="nav nav-tabs px-2">
              <li class="nav-item">
                <a class="nav-link active" href="?module=inventario&action=tipoActivos" title="Tipo Activos">
                  <i class="ti ti-devices fs-4"></i>
                  <span class="tab-txt">Tipo Activos</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=tipoCaracteristicas" title="Características">
                  <i class="ti ti-category fs-4"></i>
                  <span class="tab-txt">Características</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=caracteristicas" title="Configuración">
                  <i class="ti ti-adjustments fs-4"></i>
                  <span class="tab-txt">Config.</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=ubicaciones" title="Ubicaciones">
                  <i class="ti ti-map-pin fs-4"></i>
                  <span class="tab-txt">Ubicaciones</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=ips" title="IPs">
                  <i class="ti ti-network fs-4"></i>
                  <span class="tab-txt">IPs</span>
                </a>
              </li>
            </ul>
          </div>
        </div>

        <!-- ══════════════════════════════════════
           CARD CONTENIDO — card SEPARADO del de tabs
           DataTables opera aquí y no puede afectar los tabs
      ══════════════════════════════════════ -->
        <div class="card mb-4" style="border-top-left-radius:0;border-top-right-radius:0;">

          <!-- ── TOOLBAR DESKTOP (md+) ──────────── -->
          <div class="d-none d-md-flex align-items-center gap-2 flex-wrap px-3 py-2 border-bottom" id="dtToolbar">
            <div class="d-flex align-items-center gap-2">
              <span class="text-muted small">Mostrar</span>
              <select id="dtPageLength" class="form-select form-select-sm" style="width:auto">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              <span class="text-muted small">registros</span>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                  data-bs-auto-close="outside">
                  <i class="ti ti-columns me-1"></i>Columnas
                </button>
                <div class="dropdown-menu p-2" style="min-width:160px" id="dtColMenu"></div>
              </div>
              <button class="btn btn-sm btn-outline-success" id="dtBtnExcel">
                <i class="ti ti-file-spreadsheet me-1"></i>Excel
              </button>
              <button class="btn btn-sm btn-outline-danger" id="dtBtnPdf">
                <i class="ti ti-file-description me-1"></i>PDF
              </button>
              <div class="input-group input-group-sm" style="width:210px">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" id="dtSearch" class="form-control" placeholder="Buscar...">
              </div>
            </div>
          </div>

          <!-- ── BUSCADOR MÓVIL (< md) ────────────── -->
          <div class="d-md-none px-3 pt-3 pb-2">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0">
                <i class="ti ti-search text-muted"></i>
              </span>
              <input type="text" id="mobileSearch" class="form-control border-start-0 ps-0"
                placeholder="Buscar tipo de activo...">
            </div>
          </div>

          <!-- ── TABLA DESKTOP (md+) ────────────── -->
          <div class="d-none d-md-block">
            <div class="table-responsive">
              <table id="tablaActivos" class="table table-vcenter table-hover card-table mb-0">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Compuesto</th>
                    <th>Componente</th>
                    <th>Registro</th>
                    <th class="text-end" style="width:140px">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($activos as $v):
                    $desc = htmlspecialchars($v['descripcion'], ENT_QUOTES, 'UTF-8');
                    $icono = $v['icono'] ?: 'ti-package';
                    $fecha = $v['fechaCreacion'] instanceof DateTime
                      ? $v['fechaCreacion']->format('d/m/Y') : '—';
                    $esCom = !empty($v['esCompuesto']) && intval($v['esCompuesto']) === 1;
                    $esCon = !empty($v['esComponente']) && intval($v['esComponente']) === 1;
                    $esPer = !empty($v['esPeriferico']) && intval($v['esPeriferico']) === 1;

                    if ($esPer)
                      $badge = '<span class="badge bg-green-lt text-green"><i class="ti ti-mouse me-1"></i>Periférico</span>';
                    elseif ($esCom)
                      $badge = '<span class="badge bg-blue-lt text-blue"><i class="ti ti-cpu me-1"></i>Compuesto</span>';
                    else
                      $badge = '<span class="badge bg-azure-lt text-azure"><i class="ti ti-package me-1"></i>Simple</span>';
                    ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <span class="avatar avatar-sm bg-primary-lt text-primary">
                            <i class="ti <?= $icono ?>"></i>
                          </span>
                          <div>
                            <div class="fw-medium"><?= $desc ?></div>
                            <div class="text-muted small"><?= $fecha ?></div>
                          </div>
                        </div>
                      </td>
                      <td><?= $badge ?></td>
                      <td>
                        <?= $esCom
                          ? '<span class="badge bg-blue-lt text-blue"><i class="ti ti-check"></i></span>'
                          : '<span class="text-muted">—</span>' ?>
                      </td>
                      <td>
                        <?= $esCon
                          ? '<span class="badge bg-purple-lt text-purple"><i class="ti ti-check"></i></span>'
                          : '<span class="text-muted">—</span>' ?>
                      </td>
                      <td>
                        <?php $nombreU = trim($v['nombreUsuario'] ?? ''); ?>
                        <span class="badge bg-blue-lt px-2 py-1 fw-medium" data-bs-toggle="tooltip">
                          <i class="ti ti-user-circle me-1"></i>
                          <?= $nombreU !== '' ? htmlspecialchars($nombreU, ENT_QUOTES, 'UTF-8') : 'ID: ' . $v['idUsuarioRegistro'] ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                          <!-- Botón Editar -->
                          <button type="button" class="btn btn-sm btn-icon btn-outline-primary btnEditarActivo"
                            data-id="<?= $v['idTipoActivo'] ?>" title="Editar">
                            <i class="ti ti-edit"></i>
                          </button>

                          <!-- Botón Eliminar -->
                          <button type="button" class="btn btn-sm btn-icon btn-outline-danger btnEliminarActivo"
                            data-id="<?= $v['idTipoActivo'] ?>" data-descripcion="<?= $desc ?>" title="Eliminar">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div><!-- /tabla desktop -->

          <!-- ── CARDS MÓVIL (< md) ────────────────
             Renderizadas en PHP puro.
             El buscador JS las filtra nativamente.
             DataTables NO interfiere aquí.
        ────────────────────────────────────────── -->
          <div class="d-md-none" id="mobileList">
            <?php if (!empty($activos)): ?>
              <?php foreach ($activos as $v):
                $desc = htmlspecialchars($v['descripcion'], ENT_QUOTES, 'UTF-8');
                $icono = $v['icono'] ?: 'ti-package';
                $fecha = $v['fechaCreacion'] instanceof DateTime
                  ? $v['fechaCreacion']->format('d/m/Y') : '—';
                $esCom = !empty($v['esCompuesto']) && intval($v['esCompuesto']) === 1;
                $esCon = !empty($v['esComponente']) && intval($v['esComponente']) === 1;
                $esPer = !empty($v['esPeriferico']) && intval($v['esPeriferico']) === 1;

                if ($esPer)
                  $badge = '<span class="badge bg-green-lt text-green"><i class="ti ti-mouse me-1"></i>Periférico</span>';
                elseif ($esCom)
                  $badge = '<span class="badge bg-blue-lt text-blue"><i class="ti ti-cpu me-1"></i>Compuesto</span>';
                else
                  $badge = '<span class="badge bg-azure-lt text-azure"><i class="ti ti-package me-1"></i>Simple</span>';
                ?>
                <div class="mobile-item border-bottom" data-nombre="<?= strtolower($desc) ?>">
                  <div class="d-flex gap-3 px-3 py-3">

                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                      <span class="avatar avatar-md bg-primary-lt text-primary">
                        <i class="ti <?= $icono ?> fs-4"></i>
                      </span>
                    </div>

                    <!-- Body -->
                    <div style="min-width:0;flex:1">

                      <!-- Fila 1: nombre + badge principal -->
                      <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                        <div class="fw-semibold lh-sm" style="word-break:break-word">
                          <?= $desc ?>
                        </div>
                        <div class="flex-shrink-0 pt-1"><?= $badge ?></div>
                      </div>

                      <!-- Fila 2: sub-badges -->
                      <?php if ($esCom || $esCon || $esPer): ?>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                          <?php if ($esCom): ?>
                            <span class="badge bg-blue-lt text-blue">
                              <i class="ti ti-components me-1"></i>Compuesto
                            </span>
                          <?php endif; ?>
                          <?php if ($esCon): ?>
                            <span class="badge bg-purple-lt text-purple">
                              <i class="ti ti-puzzle me-1"></i>Componente
                            </span>
                          <?php endif; ?>
                          <?php if ($esPer): ?>
                            <span class="badge bg-green-lt text-green">
                              <i class="ti ti-mouse me-1"></i>Periférico
                            </span>
                          <?php endif; ?>
                        </div>
                      <?php else: ?>
                        <div class="mb-2"></div>
                      <?php endif; ?>

                      <!-- Fila 3: fecha/id + acciones -->
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted" style="font-size:.8rem">
                          <i class="ti ti-calendar me-1"></i><?= $fecha ?>
                          <span class="mx-1 opacity-50">·</span>
                          <?php $nombreU = trim($v['nombreUsuario'] ?? ''); ?>
                          <i class="ti ti-user me-1"></i>
                          <?= $nombreU !== '' ? htmlspecialchars($nombreU, ENT_QUOTES, 'UTF-8') : 'ID ' . $v['idUsuarioRegistro'] ?>
                        </div>
                        <div class="d-flex gap-1 ms-2 flex-shrink-0">
                          <button class="btn btn-sm btn-ghost-primary btnEditarActivo" data-id="<?= $v['idTipoActivo'] ?>">
                            <i class="ti ti-edit"></i>
                          </button>
                          <button class="btn btn-sm btn-ghost-danger btnEliminarActivo" data-id="<?= $v['idTipoActivo'] ?>"
                            data-descripcion="<?= $desc ?>">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              <?php endforeach; ?>

              <!-- Sin resultados de búsqueda -->
              <div id="mobileNoResults" class="text-center py-5 text-muted d-none">
                <i class="ti ti-search-off fs-1 d-block mb-2 opacity-50"></i>
                <p class="mb-0">No se encontraron resultados.</p>
              </div>

              <!-- Paginacion movil -->
              <div id="mobilePagination" class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
                <span id="mobilePageInfo" class="text-muted small"></span>
                <div class="d-flex gap-1">
                  <button id="mobilePrevBtn" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                    disabled>
                    <i class="ti ti-chevron-left"></i> Anterior
                  </button>
                  <button id="mobileNextBtn" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                    disabled>
                    Siguiente <i class="ti ti-chevron-right"></i>
                  </button>
                </div>
              </div>

            <?php else: ?>
              <div class="text-center py-5 text-muted">
                <i class="ti ti-inbox fs-1 d-block mb-2 opacity-50"></i>
                <p class="mb-0">No hay tipos de activo registrados.</p>
              </div>
            <?php endif; ?>
          </div><!-- /mobileList -->

        </div><!-- /card contenido -->

        <!-- ══════════════════════════════════════
           INFO CARDS
      ══════════════════════════════════════ -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <div class="card h-100 bg-primary-lt border-0">
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="ti ti-info-circle text-primary fs-3"></i>
                  <strong>Información del Sistema</strong>
                </div>
                <p class="text-muted mb-0 small">
                  Las categorías afectan a todos los activos vinculados al sistema.
                </p>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="ti ti-clock text-secondary fs-3"></i>
                  <strong>Últimos Cambios</strong>
                </div>
                <ul class="list-unstyled mb-0 small text-muted">
                  <li class="d-flex align-items-center gap-2 mb-1">
                    <i class="ti ti-point-filled text-green" style="font-size:.5rem"></i>
                    Nueva ubicación agregada
                  </li>
                  <li class="d-flex align-items-center gap-2">
                    <i class="ti ti-point-filled text-yellow" style="font-size:.5rem"></i>
                    Categoría editada hace 2 horas
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="ti ti-chart-bar text-secondary fs-3"></i>
                  <strong>Resumen</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                  <span class="text-muted small">Total Tipo Activos</span>
                  <span class="badge bg-primary-lt text-primary fs-5 fw-bold">
                    <?= count($activos) ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /container -->
    </div><!-- /page-wrapper -->
  </div><!-- /page -->


  <!-- ════════════════════════════════════════
     MODAL — Agregar Tipo Activo
════════════════════════════════════════ -->
  <div class="modal modal-blur fade" id="modalAgregarActivo" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <form id="formNuevoActivo" method="POST">

          <div class="modal-header py-3">
            <h5 class="modal-title d-flex align-items-center gap-2">
              <div class="avatar avatar-sm bg-primary-lt text-primary">
                <i class="ti ti-package"></i>
              </div>
              Agregar Tipo Activo
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3">

              <div class="col-12">
                <label class="form-label fw-semibold">
                  Descripción <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="nuevaDescripcion" name="nuevaDescripcion" maxlength="150"
                  placeholder="Describa el tipo de activo..." style="text-transform:uppercase" required>
              </div>

              <div class="col-md-7">
                <label class="form-label fw-semibold">Categoría de Icono</label>
                <select class="form-select mb-2" id="tipoIcono">
                  <option value="">— Seleccione una categoría —</option>
                  <option value="equipos">Equipos</option>
                  <option value="componentes">Componentes</option>
                  <option value="perifericos">Periféricos</option>
                  <option value="pantallas">Pantallas</option>
                  <option value="impresion">Impresión</option>
                  <option value="red">Red</option>
                </select>
                <input type="hidden" name="iconoActivo" id="iconoActivo" value="">
                <div id="listaIconos" class="row g-2 border rounded-3 p-2"
                  style="max-height:160px;overflow-y:auto;min-height:58px">
                  <div class="col-12 text-center text-muted small py-2">
                    <i class="ti ti-arrow-up me-1"></i> Seleccione una categoría
                  </div>
                </div>
              </div>

              <div class="col-md-5">
                <label class="form-label fw-semibold">Vista previa</label>
                <div class="card card-sm text-center">
                  <div class="card-body py-3">
                    <div class="avatar avatar-xl bg-primary-lt text-primary mb-2" id="previewIcon">
                      <i class="ti ti-help"></i>
                    </div>
                    <div class="text-muted small">Icono seleccionado</div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Clasificación</label>
                <div class="row g-2">
                  <div class="col-12 col-md-4">
                    <label class="card card-sm mb-0" style="cursor:pointer" for="nuevoEsCompuesto">
                      <div class="card-body d-flex align-items-center
                                justify-content-between gap-2 p-3">
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-components text-primary fs-4"></i>
                          <div>
                            <div class="fw-semibold small">Es compuesto</div>
                            <div class="text-muted" style="font-size:.75rem">
                              Contiene sub-componentes
                            </div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0">
                          <input class="form-check-input" type="checkbox" id="nuevoEsCompuesto" name="nuevoEsCompuesto"
                            value="1">
                        </div>
                      </div>
                    </label>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="card card-sm mb-0" style="cursor:pointer" for="nuevoEsComponente">
                      <div class="card-body d-flex align-items-center
                                justify-content-between gap-2 p-3">
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-puzzle text-purple fs-4"></i>
                          <div>
                            <div class="fw-semibold small">Es componente</div>
                            <div class="text-muted" style="font-size:.75rem">
                              Parte interna de un equipo
                            </div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0">
                          <input class="form-check-input" type="checkbox" id="nuevoEsComponente"
                            name="nuevoEsComponente" value="1">
                        </div>
                      </div>
                    </label>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="card card-sm mb-0" style="cursor:pointer" for="nuevoEsPeriferico">
                      <div class="card-body d-flex align-items-center
                                justify-content-between gap-2 p-3">
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-mouse text-green fs-4"></i>
                          <div>
                            <div class="fw-semibold small">Es periférico</div>
                            <div class="text-muted" style="font-size:.75rem">
                              Mouse, teclado, monitor…
                            </div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0">
                          <input class="form-check-input" type="checkbox" id="nuevoEsPeriferico"
                            name="nuevoEsPeriferico" value="1">
                        </div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="modal-footer py-2">
            <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-device-floppy me-1"></i>Guardar Tipo
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>


  <!-- ════════════════════════════════════════
     MODAL — Editar Tipo Activo
════════════════════════════════════════ -->
  <div class="modal modal-blur fade" id="modalEditarActivo" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <form id="formEditarActivo">
          <input type="hidden" id="editarIdActivo" name="editarIdActivo">

          <div class="modal-header py-3">
            <h5 class="modal-title d-flex align-items-center gap-2">
              <div class="avatar avatar-sm bg-primary-lt text-primary">
                <i class="ti ti-edit"></i>
              </div>
              Editar Tipo Activo
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3">

              <div class="col-12">
                <label class="form-label fw-semibold">
                  Descripción <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="editarDescripcion" name="editarDescripcion" maxlength="150"
                  style="text-transform:uppercase" required>
              </div>

              <div class="col-md-7">
                <label class="form-label fw-semibold">Categoría de Icono</label>
                <select class="form-select mb-2" id="editarTipoIcono">
                  <option value="">— Seleccione una categoría —</option>
                  <option value="equipos">Equipos</option>
                  <option value="componentes">Componentes</option>
                  <option value="perifericos">Periféricos</option>
                  <option value="pantallas">Pantallas</option>
                  <option value="impresion">Impresión</option>
                  <option value="red">Red</option>
                </select>
                <input type="hidden" id="editarIconoActivo" name="editarIconoActivo">
                <div id="editarListaIconos" class="row g-2 border rounded-3 p-2"
                  style="max-height:160px;overflow-y:auto;min-height:58px"></div>
              </div>

              <div class="col-md-5">
                <label class="form-label fw-semibold">Vista previa</label>
                <div class="card card-sm text-center">
                  <div class="card-body py-3">
                    <div class="avatar avatar-xl bg-primary-lt text-primary mb-2" id="editarPreviewIcon">
                      <i class="ti ti-help"></i>
                    </div>
                    <div class="text-muted small">Icono seleccionado</div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Clasificación</label>
                <div class="row g-2">
                  <div class="col-12 col-md-4">
                    <label class="card card-sm mb-0" style="cursor:pointer" for="editarEsCompuesto">
                      <div class="card-body d-flex align-items-center
                                justify-content-between gap-2 p-3">
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-components text-primary fs-4"></i>
                          <div>
                            <div class="fw-semibold small">Es compuesto</div>
                            <div class="text-muted" style="font-size:.75rem">
                              Contiene sub-componentes
                            </div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0">
                          <input class="form-check-input" type="checkbox" id="editarEsCompuesto"
                            name="editarEsCompuesto" value="1">
                        </div>
                      </div>
                    </label>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="card card-sm mb-0" style="cursor:pointer" for="editarEsComponente">
                      <div class="card-body d-flex align-items-center
                                justify-content-between gap-2 p-3">
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-puzzle text-purple fs-4"></i>
                          <div>
                            <div class="fw-semibold small">Es componente</div>
                            <div class="text-muted" style="font-size:.75rem">
                              Parte interna de un equipo
                            </div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0">
                          <input class="form-check-input" type="checkbox" id="editarEsComponente"
                            name="editarEsComponente" value="1">
                        </div>
                      </div>
                    </label>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="card card-sm mb-0" style="cursor:pointer" for="editarEsPeriferico">
                      <div class="card-body d-flex align-items-center
                                justify-content-between gap-2 p-3">
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-mouse text-green fs-4"></i>
                          <div>
                            <div class="fw-semibold small">Es periférico</div>
                            <div class="text-muted" style="font-size:.75rem">
                              Mouse, teclado, monitor…
                            </div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0">
                          <input class="form-check-input" type="checkbox" id="editarEsPeriferico"
                            name="editarEsPeriferico" value="1">
                        </div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Auditoría -->
              <div class="col-12">
                <div class="row g-2">
                  <div class="col-6">
                    <div class="bg-light rounded-3 p-3">
                      <div class="text-muted small mb-1">Usuario creación</div>
                      <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-user text-primary"></i>
                        <span class="small fw-semibold" id="editarUsuarioCreacion">—</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="bg-light rounded-3 p-3">
                      <div class="text-muted small mb-1">Fecha creación</div>
                      <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-calendar text-primary"></i>
                        <span class="small fw-semibold" id="editarFechaCreacion">—</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="modal-footer py-2">
            <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-device-floppy me-1"></i>Guardar Cambios
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>


  <!-- ════════════════════════════════════════
     MODAL — Confirmar Eliminación
════════════════════════════════════════ -->
  <div class="modal modal-blur fade" id="modalConfirmarEliminar" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title d-flex align-items-center gap-2 text-danger">
            <i class="ti ti-alert-triangle"></i> Confirmar eliminación
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body pt-2">
          <p class="text-muted mb-2 small">
            ¿Estás seguro de eliminar el tipo de activo:
          </p>
          <p class="fw-bold mb-3" id="eliminarNombreActivo"></p>
          <div class="alert alert-warning py-2 mb-0 small">
            <i class="ti ti-info-circle me-1"></i>
            Si tiene activos asociados, no podrá eliminarse.
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="confirmarEliminarActivo">
            <i class="ti ti-trash me-1"></i>Sí, eliminar
          </button>
        </div>
      </div>
    </div>
  </div>


  <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div>

  <script src="modules/inventario/views/js/tipoActivos.js"></script>