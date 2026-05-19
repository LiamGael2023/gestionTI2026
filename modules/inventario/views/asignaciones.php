<!-- ============================================================
     ASIGNACIONES.PHP — Mismo patrón que equipos.php
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
   LAYOUT MODAL — igual que equipos.php
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

/* Worker card — dentro del auditoria-box */
.worker-info-box {
    background: var(--tblr-bg-surface-secondary, #f8fafc);
    border: 1px solid var(--tblr-border-color, #d0d5dd);
    border-radius: .5rem;
    padding: .75rem 1rem;
}

/* Equipos preview tabla */
.equipos-preview-wrap {
    border: 1px solid var(--tblr-border-color, #d0d5dd);
    border-radius: .5rem;
    overflow: hidden;
    max-height: 220px;
    overflow-y: auto;
}

/* Historial */
.hist-item {
    display: flex;
    gap: .65rem;
    padding: .6rem 0;
    border-bottom: 1px solid var(--tblr-border-color-light, #f0f3f8);
}
.hist-item:last-child { border-bottom: none; }
.hist-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: .4rem;
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
            <h2 class="page-title">Asignaciones</h2>
            <p class="text-muted mb-0">Control de asignación de estaciones a trabajadores.</p>
          </div>
          <div class="col-auto ms-auto">
            <button class="btn btn-primary" id="btnNuevaAsignacion">
              <i class="ti ti-plus me-1"></i>Nueva Asignación
            </button>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-header">
          <h3 class="card-title">
            <i class="ti ti-user-check me-2 text-primary"></i>Asignaciones Activas
          </h3>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tablaAsignaciones" class="table table-vcenter table-mobile-md card-table table-sm">
              <thead>
                <tr>
                  <th>Estación</th>
                  <th>IP</th>
                  <th>Ambiente</th>
                  <th>Responsable</th>
                  <th>Trabajador Asignado</th>
                  <th>Fecha Asignación</th>
                  <th class="d-none d-sm-table-cell">Registrado Por</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $asignaciones = AsignacionController::ctrListarActivas();
                if ($asignaciones) {
                    foreach ($asignaciones as $a) {
                        $fa = isset($a['fechaAsignacion'])
                            ? ($a['fechaAsignacion'] instanceof DateTime
                                ? $a['fechaAsignacion']->format('d/m/Y')
                                : date('d/m/Y', strtotime($a['fechaAsignacion'])))
                            : '—';
                        echo '
                        <tr>
                          <td>
                            <div class="d-flex align-items-center gap-2">
                              <i class="ti ti-desktop text-primary fs-3"></i>
                              <span class="fw-medium">' . htmlspecialchars($a['nombreEstacion'] ?? '') . '</span>
                            </div>
                          </td>
                          <td>
                            ' . (!empty($a['ipAddress'])
                                ? '<span class="badge bg-primary-lt text-primary font-monospace">' . htmlspecialchars($a['ipAddress']) . '</span>'
                                : '<span class="text-muted small">—</span>') . '
                          </td>
                          <td class="small text-muted">' . htmlspecialchars($a['nombreAmbiente'] ?? '—') . '</td>
                          <td>
                            <div class="fw-medium small">' . htmlspecialchars($a['trabajadorResponsable'] ?? '') . '</div>
                            <div class="text-primary small">' . htmlspecialchars($a['dniTrabajadorResponsable'] ?? '') . '</div>
                          </td>
                          <td class="small text-muted">' . htmlspecialchars($a['trabajadorAsignado'] ?? '—') . '</td>
                          <td class="small text-muted">' . $fa . '</td>
                          <td class="d-none d-sm-table-cell">
                            <span class="badge badge-outline text-muted">ID: ' . $a['idUsuarioRegistro'] . '</span>
                          </td>
                          <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                                            <a href="modules/inventario/ajax/generar_acta.php?idAsignacion=' . $a['idAsignacion'] . '"
                                 class="btn btn-sm btn-icon btn-outline-success"
                                 title="Descargar Acta de Entrega" target="_blank">
                                <i class="ti ti-file-description"></i>
                              </a>
<button type="button"
                                class="btn btn-sm btn-icon btn-outline-info btnVerHistorial"
                                data-id="' . $a['idEstacion'] . '"
                                data-nombre="' . htmlspecialchars($a['nombreEstacion'] ?? '') . '"
                                title="Ver historial">
                                <i class="ti ti-history"></i>
                              </button>
                              <button type="button"
                                class="btn btn-sm btn-icon btn-outline-warning btnReasignar"
                                data-id="' . $a['idEstacion'] . '"
                                data-nombre="' . htmlspecialchars($a['nombreEstacion'] ?? '') . '"
                                title="Reasignar">
                                <i class="ti ti-refresh"></i>
                              </button>
                              <button type="button"
                                class="btn btn-sm btn-icon btn-outline-danger btnLiberar"
                                data-idAsignacion="' . $a['idAsignacion'] . '"
                                data-nombre="' . htmlspecialchars($a['nombreEstacion'] ?? '') . '"
                                title="Liberar asignación">
                                <i class="ti ti-x"></i>
                              </button>
                            </div>
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


<!-- ============================================================
     MODAL NUEVA ASIGNACIÓN / REASIGNAR
============================================================ -->
<div class="modal modal-blur fade" id="modalAsignar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-user-check fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold" id="modalAsignarTitulo">Nueva Asignación</h5>
            <small class="text-muted">Asignación de estación a trabajador</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formAsignar" novalidate>
        <input type="hidden" name="nuevoIdEstacion" id="hdnIdEstacion">

        <div class="modal-body-scroll px-4 py-3">
          <div class="row g-3">

            <!-- IZQUIERDA -->
            <div class="col-lg-5 d-flex flex-column gap-3">

              <!-- Estación -->
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-desktop text-primary"></i>
                  <span class="seccion-titulo text-primary">Estación</span>
                </div>
                <div class="seccion-body">
                  <!-- Combo (nueva) -->
                  <div id="wrapComboEstacion">
                    <label class="form-label small fw-semibold">Seleccionar estación <span class="text-danger">*</span></label>
                    <select id="nuevoIdEstacionSelect" style="display:none">
                      <option value="">Seleccionar...</option>
                    </select>
                  </div>
                  <!-- Fija (reasignar) -->
                  <div id="wrapFijaEstacion" style="display:none">
                    <label class="form-label small fw-semibold">Estación</label>
                    <div class="form-control-plaintext fw-semibold" id="textoFijaEstacion"></div>
                  </div>
                </div>
              </div>

              <!-- Equipos preview -->
              <div id="equiposPreviewWrap" style="display:none">
                <div class="small fw-bold text-muted text-uppercase mb-2">
                  <i class="ti ti-devices me-1"></i>Equipos que se asignan
                </div>
                <div class="equipos-preview-wrap">
                  <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="small text-uppercase fw-semibold">Tipo</th>
                        <th class="small text-uppercase fw-semibold">Activo</th>
                        <th class="small text-uppercase fw-semibold">Cód. Patrimonial</th>
                      </tr>
                    </thead>
                    <tbody id="equiposPreviewBody"></tbody>
                  </table>
                </div>
              </div>

              <!-- Detalles -->
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-calendar text-primary"></i>
                  <span class="seccion-titulo text-primary">Detalles</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label small fw-semibold">Ambiente</label>
                      <select id="nuevoIdAmbiente" name="nuevoIdAmbiente" style="display:none">
                        <option value="">Sin ambiente</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label small fw-semibold">Fecha de Asignación <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" name="nuevoFechaAsignacion" id="nuevoFechaAsignacion"
                             value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                      <label class="form-label small fw-semibold">Observaciones</label>
                      <textarea class="form-control" name="nuevoObservaciones" rows="2"
                                placeholder="Opcional..." style="resize:none"></textarea>
                    </div>
                  </div>
                </div>
              </div>

            </div>

            <!-- DERECHA -->
            <div class="col-lg-7 d-flex flex-column gap-3">

              <!-- Responsable -->
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-user-shield text-primary"></i>
                  <span class="seccion-titulo text-primary">Trabajador Responsable</span>
                </div>
                <div class="seccion-body d-flex flex-column gap-3">
                  <div>
                    <label class="form-label small fw-semibold">DNI <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="text" class="form-control font-monospace" id="inputDniResponsable"
                             placeholder="Ej: 18004039" maxlength="20">
                      <button type="button" id="btnBuscarResponsable" class="btn btn-primary">
                        <i class="ti ti-search me-1"></i>Buscar
                      </button>
                    </div>
                    <div id="dniError" class="text-danger small mt-1" style="display:none"></div>
                  </div>

                  <!-- Card trabajador encontrado -->
                  <div id="workerCard" style="display:none">
                    <div class="worker-info-box">
                      <div class="small fw-bold text-muted text-uppercase mb-2">
                        <i class="ti ti-user-check me-1"></i>Trabajador encontrado
                      </div>
                      <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle bg-primary text-white fw-bold"
                             style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0"
                             id="workerInitials">?</div>
                        <div>
                          <div class="fw-semibold" id="workerNombre"></div>
                          <div class="text-primary small" id="workerDni"></div>
                          <div class="text-muted small" id="workerCargo"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <input type="hidden" name="nuevoDniResponsable"        id="nuevoDniResponsable">
                  <input type="hidden" name="nuevoTrabajadorResponsable" id="nuevoTrabajadorResponsable">
                </div>
              </div>

              <!-- Asignado -->
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-user text-primary"></i>
                  <span class="seccion-titulo text-primary">Trabajador Asignado</span>
                </div>
                <div class="seccion-body">
                  <label class="form-label small fw-semibold">Nombre completo</label>
                  <input type="text" class="form-control" name="nuevoTrabajadorAsignado" id="nuevoTrabajadorAsignado"
                         placeholder="Se rellena automáticamente, puedes editarlo...">
                  <div class="text-muted small mt-1">
                    <i class="ti ti-info-circle me-1"></i>Quien usa la estación (puede ser diferente al responsable)
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="btnGuardarAsignacion" disabled>
            <i class="ti ti-user-check me-1"></i>Guardar Asignación
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- ============================================================
     MODAL HISTORIAL
============================================================ -->
<div class="modal modal-blur fade" id="modalHistorial" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-history fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Historial de Asignaciones</h5>
            <small class="text-muted fw-semibold text-primary" id="historialNombreEst"></small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body px-4 py-3" style="max-height:calc(100vh - 240px);overflow-y:auto">
        <div id="historialContenido">
          <div class="text-center py-4 text-muted">
            <span class="spinner-border spinner-border-sm me-2"></span>Cargando...
          </div>
        </div>
      </div>

      <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
      </div>

    </div>
  </div>
</div>


<!-- ============================================================
     MODAL LIBERAR
============================================================ -->
<div class="modal modal-blur fade" id="modalLiberar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:#fff5f5;border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:#d63939;flex-shrink:0">
            <i class="ti ti-alert-triangle fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Liberar Asignación</h5>
            <small class="text-muted fw-semibold" id="liberarNombreEst"></small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formLiberar">
        <input type="hidden" name="accion" value="liberar">
        <input type="hidden" name="idAsignacion" id="liberarIdAsignacion">
        <div class="modal-body px-4 py-3">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold">Fecha de Liberación <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="fechaLiberacion" id="liberarFecha"
                     value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Motivo</label>
              <input type="text" class="form-control" name="motivoCambio"
                     placeholder="Ej: Traslado, Baja, Cambio de área...">
            </div>
          </div>
        </div>
        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger" id="btnConfirmarLiberar">
            <i class="ti ti-x me-1"></i>Liberar
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<div id="toastContainerAsig" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="modules/inventario/views/js/asignaciones.js"></script>
