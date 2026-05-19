<!-- ============================================================
     EQUIPOS.PHP  — Custom select sin librerías
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
            <h2 class="page-title">Gestión de Equipos</h2>
            <p class="text-muted mb-0">Administración y control de los equipos registrados.</p>
          </div>
          <div class="col-auto ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo">
              <i class="ti ti-plus me-1"></i>Nuevo Equipo
            </button>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-4">
        <div class="card-header">
          <h3 class="card-title">
            <i class="ti ti-devices me-2 text-primary"></i>Listado de Equipos
          </h3>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tablaEquipos" class="table table-vcenter table-mobile-md card-table table-sm">
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>N° Serie</th>
                  <th>Código Patrimonial</th>
                  <th>Características</th>
                  <th>Fecha Creación</th>
                  <th class="d-none d-sm-table-cell">Registrado Por</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $equipos = EquipoController::ctrMostrarEquipo(null, null);
                if (!is_array($equipos)) $equipos = [];
                foreach ($equipos as $value) {
                        $icono = !empty($value["iconoActivo"]) ? $value["iconoActivo"] : 'ti-package';
                        $fecha = isset($value["fechaCreacion"])
                            ? ($value["fechaCreacion"] instanceof DateTime
                                ? $value["fechaCreacion"]->format("d/m/Y")
                                : date("d/m/Y", strtotime($value["fechaCreacion"])))
                            : "Sin fecha";

                        // Botón "Armar Equipo" solo si compuesto = 1
                        $btnArmar = '';
                        if (!empty($value["compuesto"]) && intval($value["compuesto"]) === 1) {
                            $btnArmar = '
                            <button type="button"
                              class="btn btn-sm btn-icon btn-outline-success btnArmarEquipo"
                              data-id="' . $value["idEquipo"] . '"
                              data-nombre="' . htmlspecialchars($value["nombreActivo"] ?? '') . '"
                              data-icono="' . $icono . '"
                              title="Armar equipo / componentes">
                              <i class="ti ti-tools"></i>
                            </button>';
                        }

                        echo '
                        <tr>
                          <td>
                            <div class="d-flex align-items-center gap-2">
                              <i class="ti ' . $icono . ' text-primary fs-3"></i>
                              <span class="fw-medium">' . htmlspecialchars($value["nombreActivo"] ?? '') . '</span>
                            </div>
                          </td>
                          <td>' . htmlspecialchars($value["numeroSerie"] ?? '') . '</td>
                          <td>' . htmlspecialchars($value["codigoPatrimonial"] ?? '') . '</td>
                          <td class="small text-muted">' . htmlspecialchars($value["caracteristicas"] ?? '') . '</td>
                          <td class="small text-muted">' . $fecha . '</td>
                          <td class="d-none d-sm-table-cell">
                            <span class="badge badge-outline text-muted">ID: ' . $value["idUsuarioRegistro"] . '</span>
                          </td>
                          <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                              ' . $btnArmar . '
                              <button type="button"
                                class="btn btn-sm btn-icon btn-outline-primary btnEditarEquipo"
                                data-id="' . $value["idEquipo"] . '" title="Editar">
                                <i class="ti ti-edit"></i>
                              </button>
                              <button type="button"
                                class="btn btn-sm btn-icon btn-outline-danger btnEliminarEquipo"
                                data-id="' . $value["idEquipo"] . '"
                                data-nombre="' . htmlspecialchars($value["nombreActivo"] ?? '', ENT_QUOTES) . '"
                                data-es-padre="' . (!empty($value["compuesto"]) && intval($value["compuesto"]) === 1 ? '1' : '0') . '"
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
        </div>
      </div>

    </div>
  </div>
</div>
</body>


<!-- ============================================================
     MODAL AGREGAR EQUIPO
============================================================ -->
<div class="modal modal-blur fade" id="modalAgregarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i class="ti ti-device-laptop fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Agregar Equipo</h5>
            <small class="text-muted">Complete los datos del nuevo equipo</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formNuevoEquipo" novalidate>
        <div class="modal-body-scroll px-4 py-3">
          <div class="row g-3">

            <!-- IZQUIERDA -->
            <div class="col-lg-6 d-flex flex-column gap-3">
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-info-circle text-primary"></i>
                  <span class="seccion-titulo text-primary">Información General</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label small fw-semibold">Activo <span class="text-danger">*</span></label>
                      <select id="nuevoIdActivo" name="nuevoIdActivo" required style="display:none">
                        <option value="">Seleccionar activo...</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Código Patrimonial</label>
                      <input id="nuevoCodigoPatrimonial" name="nuevoCodigoPatrimonial" type="text" class="form-control" placeholder="CP-2024-001">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Número de Serie</label>
                      <input id="nuevoNumeroSerie" name="nuevoNumeroSerie" type="text" class="form-control" placeholder="SN-XJK9201LH">
                    </div>
                  </div>
                </div>
              </div>

              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-calendar text-primary"></i>
                  <span class="seccion-titulo text-primary">Fechas y Garantía</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Fecha Adquisición</label>
                      <input id="nuevoFechaAdquisicion" name="nuevoFechaAdquisicion" type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Inicio Garantía</label>
                      <input id="nuevoFechaInicioGarantia" name="nuevoFechaInicioGarantia" type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Fin Garantía</label>
                      <input id="nuevoFechaFinGarantia" name="nuevoFechaFinGarantia" type="date" class="form-control">
                    </div>
                  </div>
                </div>
              </div>

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

            <!-- DERECHA -->
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
                        <select id="nuevoTipoCaracteristica" style="display:none"><option value="">Seleccionar tipo...</option></select>
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Valor</label>
                        <select id="nuevoValorCaracteristica" style="display:none"><option value="">Seleccionar valor...</option></select>
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
            <i class="ti ti-device-floppy me-1"></i>Guardar Equipo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ============================================================
     MODAL EDITAR EQUIPO
============================================================ -->
<div class="modal modal-blur fade" id="modalEditarEquipo" tabindex="-1" aria-hidden="true">
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
            <h5 class="mb-0 fw-bold">Editar Equipo</h5>
            <small class="text-muted">Modificación de información del equipo</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formEditarEquipo" novalidate>
        <div class="modal-body-scroll px-4 py-3">
          <div class="row g-3">

            <div class="col-lg-6 d-flex flex-column gap-3">
              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-info-circle text-primary"></i>
                  <span class="seccion-titulo text-primary">Información General</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label small fw-semibold">Activo <span class="text-danger">*</span></label>
                      <select id="editarIdActivo" name="editarIdActivo" required style="display:none">
                        <option value="">Seleccionar activo...</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Código Patrimonial</label>
                      <input id="editarCodigoPatrimonial" name="editarCodigoPatrimonial" type="text" class="form-control">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Número de Serie</label>
                      <input id="editarNumeroSerie" name="editarNumeroSerie" type="text" class="form-control">
                    </div>
                  </div>
                </div>
              </div>

              <div class="seccion-card">
                <div class="seccion-header">
                  <i class="ti ti-calendar text-primary"></i>
                  <span class="seccion-titulo text-primary">Fechas y Garantía</span>
                </div>
                <div class="seccion-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Fecha Adquisición</label>
                      <input id="editarFechaAdquisicion" name="editarFechaAdquisicion" type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Inicio Garantía</label>
                      <input id="editarFechaInicioGarantia" name="editarFechaInicioGarantia" type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Fin Garantía</label>
                      <input id="editarFechaFinGarantia" name="editarFechaFinGarantia" type="date" class="form-control">
                    </div>
                  </div>
                </div>
              </div>

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
                        <select id="editarTipoCaracteristica" style="display:none"><option value="">Seleccionar tipo...</option></select>
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Valor</label>
                        <select id="editarValorCaracteristica" style="display:none"><option value="">Seleccionar valor...</option></select>
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
          <input type="hidden" id="editarIdEquipo" name="editarIdEquipo">
          <input type="hidden" id="editarCaracteristicasIds" name="editarCaracteristicasIds">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Actualizar Equipo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ============================================================
     MODAL ARMAR EQUIPO (agregar / quitar componentes)
============================================================ -->
<div class="modal modal-blur fade" id="modalArmarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <!-- Header dinámico — se llena por JS con nombre e ícono del equipo padre -->
      <div class="modal-header px-4 pt-4 pb-3"
           style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
               style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0">
            <i id="armarIconoPadre" class="ti ti-tools fs-2"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Armar Equipo</h5>
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
                    Buscar equipo disponible
                    <span class="text-muted fw-normal">(sin padre asignado)</span>
                  </label>
                  <select id="armarComponenteSelect" style="display:none">
                    <option value="">Seleccionar componente...</option>
                  </select>
                </div>
                <button type="button" id="btnAgregarComponente" class="btn btn-primary w-100" disabled>
                  <i class="ti ti-plus me-1"></i>Agregar al equipo
                </button>

                <!-- info del componente seleccionado -->
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
        <input type="hidden" id="armarIdEquipoPadre">
        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>Cerrar
        </button>
      </div>

    </div>
  </div>
</div>


<!-- ════════ MODAL CONFIRMAR ELIMINACIÓN EQUIPO ════════ -->
<div class="modal modal-blur fade" id="modalConfirmarEliminarEquipo" tabindex="-1">
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
        <p class="fw-bold mb-0" id="eliminarNombreEquipo"></p>
        <p class="text-muted small mt-2 mb-0">Esta acción es reversible solo desde la base de datos.<br>
        No se puede eliminar si tiene componentes asignados o está en una estación.</p>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminarEquipo">
          <i class="ti ti-trash me-1"></i>Sí, eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<div id="toastContainerEquipos" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="modules/inventario/views/js/equipos.js"></script>
