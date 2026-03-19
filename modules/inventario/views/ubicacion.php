<body>
  <?php
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }
  ?>

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
   MODAL LAYOUT
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

/* Card header de tablas con ícono */
.card-title-icon {
    display: flex;
    align-items: center;
    gap: .5rem;
}

@media (max-width: 991px) {
    .modal-body-scroll { max-height: calc(100vh - 180px); }
}
</style>

  <div class="page">
<?php include __DIR__ . '/_submenu.php'; ?>


    <div class="page-wrapper">
      <div class="container-xl">

        <!-- PAGE HEADER -->
        <div class="page-header d-print-none">
          <div class="row align-items-center">
            <div class="col">
              <h2 class="page-title">Configuraciones</h2>
              <p class="text-muted mb-0">Gestión de datos base del sistema de inventario.</p>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
              <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarUbicacion">
                <i class="ti ti-plus me-1"></i>Nueva Ubicación
              </button>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarAmbiente">
                <i class="ti ti-plus me-1"></i>Nuevo Ambiente
              </button>
            </div>
          </div>
        </div>

        <!-- TABS + TABLA UBICACIONES -->
        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=activos">
                  <i class="ti ti-devices me-1"></i>Activos
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=tipoCaracteristicas">
                  <i class="ti ti-category me-1"></i>Tipo Características
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=caracteristicas">
                  <i class="ti ti-adjustments me-1"></i>Características
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" href="?module=inventario&action=ubicaciones">
                  <i class="ti ti-map-pin me-1"></i>Ubicaciones
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=ips">
                  <i class="ti ti-network me-1"></i>IPs
                </a>
              </li>
            </ul>
          </div>

          <!-- Sub-header de la tabla -->
          <div class="card-header border-top-0 pt-2 pb-2" style="background:var(--tblr-bg-surface,#fff);">
            <h3 class="card-title mb-0">
              <i class="ti ti-map-pin me-2 text-primary"></i>Listado de Ubicaciones
            </h3>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table id="tablaUbicaciones" class="table table-vcenter table-mobile-md card-table table-sm">
                <thead>
                  <tr>
                    <th>Descripción</th>
                    <th>Depende de</th>
                    <th>Fecha de Creación</th>
                    <th class="d-none d-sm-table-cell">Registrado Por</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $ubicaciones = UbicacionController::ctrMostrarUbicacion(null, null);
                  if ($ubicaciones && $ubicaciones !== "error") {
                      foreach ($ubicaciones as $u) {
                          $fecha = isset($u["fechaCreacion"])
                              ? ($u["fechaCreacion"] instanceof DateTime
                                  ? $u["fechaCreacion"]->format("d/m/Y")
                                  : date("d/m/Y", strtotime($u["fechaCreacion"])))
                              : "Sin fecha";
                          echo '
                          <tr>
                            <td data-label="Descripción">
                              <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-map-pin text-primary fs-3"></i>
                                <span class="fw-medium">' . htmlspecialchars($u["descripcion"] ?? '') . '</span>
                              </div>
                            </td>
                            <td data-label="Depende de" class="text-muted small">
                              ' . (!empty($u["descripcionPadre"])
                                  ? '<span class="badge badge-outline text-muted">' . htmlspecialchars($u["descripcionPadre"]) . '</span>'
                                  : '—') . '
                            </td>
                            <td data-label="Fecha" class="small text-muted">' . $fecha . '</td>
                            <td data-label="Usuario" class="d-none d-sm-table-cell">
                              <span class="badge badge-outline text-muted fw-normal">ID: ' . $u["idUsuarioRegistro"] . '</span>
                            </td>
                            <td class="text-end">
                              <button class="btn btn-sm btn-icon btn-outline-primary btnEditarUbicacion"
                                      data-id="' . $u["idUbicacion"] . '" title="Editar">
                                <i class="ti ti-edit"></i>
                              </button>
                            </td>
                          </tr>';
                      }
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- TABLA AMBIENTES -->
        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <h3 class="card-title">
              <i class="ti ti-building me-2 text-primary"></i>Ambientes
            </h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table id="tablaAmbientes" class="table table-vcenter table-mobile-md card-table table-sm">
                <thead>
                  <tr>
                    <th>Descripción</th>
                    <th>Ubicación</th>
                    <th>Fecha de Creación</th>
                    <th class="d-none d-sm-table-cell">Registrado Por</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $ambientes = AmbienteController::ctrMostrarAmbiente(null, null);
                  if ($ambientes && $ambientes !== "error") {
                      foreach ($ambientes as $a) {
                          $fecha = isset($a["fechaCreacion"])
                              ? ($a["fechaCreacion"] instanceof DateTime
                                  ? $a["fechaCreacion"]->format("d/m/Y")
                                  : date("d/m/Y", strtotime($a["fechaCreacion"])))
                              : "Sin fecha";
                          echo '
                          <tr>
                            <td data-label="Descripción">
                              <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-building text-primary fs-3"></i>
                                <span class="fw-medium">' . htmlspecialchars($a["descripcion"] ?? '') . '</span>
                              </div>
                            </td>
                            <td data-label="Ubicación">
                              <span class="badge bg-primary-lt text-primary">
                                <i class="ti ti-map-pin me-1"></i>' . htmlspecialchars($a["nombreUbicacion"] ?? '') . '
                              </span>
                            </td>
                            <td data-label="Fecha" class="small text-muted">' . $fecha . '</td>
                            <td data-label="Usuario" class="d-none d-sm-table-cell">
                              <span class="badge badge-outline text-muted fw-normal">ID: ' . $a["idUsuarioRegistro"] . '</span>
                            </td>
                            <td class="text-end">
                              <button class="btn btn-sm btn-icon btn-outline-primary btnEditarAmbiente"
                                      data-id="' . $a["idAmbiente"] . '" title="Editar">
                                <i class="ti ti-edit"></i>
                              </button>
                            </td>
                          </tr>';
                      }
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

</body>


<!-- ════════════════════════ MODAL AGREGAR UBICACIÓN ════════════════════════ -->
<div class="modal modal-blur fade" id="modalAgregarUbicacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-map-pin fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Agregar Ubicación</h5>
            <small class="text-muted">Complete los datos de la nueva ubicación</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formNuevaUbicacion" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="d-flex flex-column gap-3">

            <div class="seccion-card">
              <div class="seccion-header">
                <i class="ti ti-info-circle text-primary"></i>
                <span class="seccion-titulo text-primary">Información General</span>
              </div>
              <div class="seccion-body">
                <div class="row g-3">

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Descripción <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" placeholder="Ej: GERENCIA GENERAL"
                           name="nuevaDescripcionUbicacion" id="nuevaDescripcionUbicacion" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Depende de
                      <span class="text-muted fw-normal">(opcional — dejar vacío si es nivel raíz)</span>
                    </label>
                    <select id="nuevoIdUbicacionPadre" name="nuevoIdUbicacionPadre" style="display:none">
                      <option value="">Ninguna (raíz)</option>
                    </select>
                  </div>

                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer px-4 pb-4 pt-2"
             style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>Guardar
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- ════════════════════════ MODAL EDITAR UBICACIÓN ════════════════════════ -->
<div class="modal modal-blur fade" id="modalEditarUbicacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-edit fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Editar Ubicación</h5>
            <small class="text-muted">Modificación de información de la ubicación</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formEditarUbicacion" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="d-flex flex-column gap-3">

            <div class="seccion-card">
              <div class="seccion-header">
                <i class="ti ti-info-circle text-primary"></i>
                <span class="seccion-titulo text-primary">Información General</span>
              </div>
              <div class="seccion-body">
                <div class="row g-3">

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Descripción <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control"
                           name="editarDescripcionUbicacion" id="editarDescripcionUbicacion" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Depende de
                      <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <select id="editarIdUbicacionPadre" name="editarIdUbicacionPadre" style="display:none">
                      <option value="">Ninguna (raíz)</option>
                    </select>
                  </div>

                </div>
              </div>
            </div>

            <div class="auditoria-box">
              <div class="small fw-bold text-muted text-uppercase mb-2">
                <i class="ti ti-shield-check me-1"></i>Auditoría
              </div>
              <div class="row g-2 small">
                <div class="col-6">
                  <div class="text-muted">Usuario Creación</div>
                  <div class="fw-semibold" id="editarUbicUsuarioCreacion">--</div>
                </div>
                <div class="col-6">
                  <div class="text-muted">Fecha Creación</div>
                  <div class="fw-semibold" id="editarUbicFechaCreacion">--</div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer px-4 pb-4 pt-2"
             style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <input type="hidden" id="editarIdUbicacion" name="editarIdUbicacion">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Guardar Cambios
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- ════════════════════════ MODAL AGREGAR AMBIENTE ════════════════════════ -->
<div class="modal modal-blur fade" id="modalAgregarAmbiente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-building fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Agregar Ambiente</h5>
            <small class="text-muted">Complete los datos del nuevo ambiente</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formNuevoAmbiente" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="d-flex flex-column gap-3">

            <div class="seccion-card">
              <div class="seccion-header">
                <i class="ti ti-info-circle text-primary"></i>
                <span class="seccion-titulo text-primary">Información General</span>
              </div>
              <div class="seccion-body">
                <div class="row g-3">

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Descripción <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" placeholder="Ej: SALA DE SERVIDORES"
                           name="nuevaDescripcionAmbiente" id="nuevaDescripcionAmbiente" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Ubicación <span class="text-danger">*</span>
                    </label>
                    <select id="nuevoIdUbicacionAmbiente" name="nuevoIdUbicacionAmbiente"
                            style="display:none" required>
                      <option value="">Seleccionar ubicación...</option>
                    </select>
                  </div>

                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer px-4 pb-4 pt-2"
             style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>Guardar
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- ════════════════════════ MODAL EDITAR AMBIENTE ════════════════════════ -->
<div class="modal modal-blur fade" id="modalEditarAmbiente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-edit fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Editar Ambiente</h5>
            <small class="text-muted">Modificación de información del ambiente</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formEditarAmbiente" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="d-flex flex-column gap-3">

            <div class="seccion-card">
              <div class="seccion-header">
                <i class="ti ti-info-circle text-primary"></i>
                <span class="seccion-titulo text-primary">Información General</span>
              </div>
              <div class="seccion-body">
                <div class="row g-3">

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Descripción <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control"
                           name="editarDescripcionAmbiente" id="editarDescripcionAmbiente" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold">
                      Ubicación <span class="text-danger">*</span>
                    </label>
                    <select id="editarIdUbicacionAmbiente" name="editarIdUbicacionAmbiente"
                            style="display:none" required>
                      <option value="">Seleccionar ubicación...</option>
                    </select>
                  </div>

                </div>
              </div>
            </div>

            <div class="auditoria-box">
              <div class="small fw-bold text-muted text-uppercase mb-2">
                <i class="ti ti-shield-check me-1"></i>Auditoría
              </div>
              <div class="row g-2 small">
                <div class="col-6">
                  <div class="text-muted">Usuario Creación</div>
                  <div class="fw-semibold" id="editarAmbUsuarioCreacion">--</div>
                </div>
                <div class="col-6">
                  <div class="text-muted">Fecha Creación</div>
                  <div class="fw-semibold" id="editarAmbFechaCreacion">--</div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer px-4 pb-4 pt-2"
             style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <input type="hidden" id="editarIdAmbiente" name="editarIdAmbiente">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Guardar Cambios
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<link rel="stylesheet" href="modules/inventario/views/css/ubicaciones.css">
<script src="modules/inventario/views/js/ubicaciones.js"></script>