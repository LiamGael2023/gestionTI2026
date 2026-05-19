<body>
  <?php
  if (session_status() == PHP_SESSION_NONE) { session_start(); }
  ?>
<style>
.cs-wrap{position:relative;width:100%;font-size:.875rem}
.cs-display{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.375rem .75rem;min-height:36px;background:#fff;border:1px solid var(--tblr-border-color,#d0d5dd);border-radius:var(--tblr-border-radius,.375rem);cursor:pointer;outline:none;transition:border-color .15s,box-shadow .15s}
.cs-display:hover{border-color:var(--tblr-primary,#0054a6)}
.cs-wrap.cs-open .cs-display,.cs-display:focus{border-color:var(--tblr-primary,#0054a6);box-shadow:0 0 0 .2rem rgba(var(--tblr-primary-rgb,0,84,166),.15)}
.cs-text{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.4}
.cs-text.placeholder-text{color:#9ca3af}
.cs-arrow{flex-shrink:0;color:#6c757d;transition:transform .2s}
.cs-wrap.cs-open .cs-arrow{transform:rotate(180deg)}
.cs-panel{display:none;position:absolute;left:0;right:0;z-index:1060;background:#fff;border:1px solid var(--tblr-border-color,#d0d5dd);border-radius:var(--tblr-border-radius,.375rem);box-shadow:0 4px 20px rgba(0,0,0,.12);overflow:hidden}
.cs-wrap.cs-open .cs-panel{display:block}
.cs-search-row{display:flex;align-items:center;gap:.4rem;padding:.4rem .65rem;border-bottom:1px solid var(--tblr-border-color,#e6ebf1);background:var(--tblr-bg-surface-secondary,#f8fafc)}
.cs-search{border:none;outline:none;background:transparent;font-size:.8rem;width:100%;padding:0;color:#374151}
.cs-list{list-style:none;margin:0;padding:.2rem 0;max-height:190px;overflow-y:auto}
.cs-list li{padding:.38rem .75rem;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:background .1s}
.cs-list li:hover,.cs-list li.cs-selected{background:var(--tblr-primary-lt,#e7f0ff);color:var(--tblr-primary,#0054a6)}
.cs-list li.cs-selected{font-weight:600}
.cs-list li.cs-placeholder-item{color:#9ca3af;font-style:italic}
.cs-list li.cs-empty{color:#9ca3af;font-style:italic;cursor:default}
.cs-list li.cs-empty:hover{background:none}
.seccion-card{border:1px solid var(--tblr-border-color,#e6ebf1);border-left:4px solid var(--tblr-primary,#0054a6);border-radius:.5rem;background:#fff}
.seccion-header{display:flex;align-items:center;gap:.5rem;padding:.65rem 1.1rem .45rem;border-bottom:1px solid var(--tblr-border-color-light,#f0f3f8)}
.seccion-titulo{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
.seccion-body{padding:.9rem 1.1rem 1rem}
.auditoria-box{background:var(--tblr-bg-surface-secondary,#f8fafc);border:1px dashed var(--tblr-border-color,#d0d5dd);border-radius:.5rem;padding:.8rem 1rem}
.cidr-preview{background:var(--tblr-bg-surface-secondary,#f8fafc);border:1px solid var(--tblr-border-color,#d0d5dd);border-radius:.5rem;padding:.75rem 1rem;font-size:.82rem}
.cidr-preview .preview-row{display:flex;justify-content:space-between;align-items:center;padding:.18rem 0;border-bottom:1px solid var(--tblr-border-color-light,#f0f3f8)}
.cidr-preview .preview-row:last-child{border-bottom:none}
.cidr-preview .preview-label{color:#6c757d}
.cidr-preview .preview-value{font-family:monospace;font-weight:600}
.preview-count{font-size:2rem;font-weight:700;color:var(--tblr-primary,#0054a6);line-height:1}
.badge-disponible{background:#d1fae5;color:#065f46}
.badge-asignada{background:#dbeafe;color:#1e40af}
.badge-reservada{background:#fef3c7;color:#92400e}
.badge-rango{background:#ede9fe;color:#5b21b6;font-family:monospace;font-size:.75rem}
</style>

  <div class="page">
    <div class="page-wrapper">
      <div class="container-xl">
        <div class="page-header d-print-none">
          <div class="row align-items-center">
            <div class="col">
              <h2 class="page-title">Gestión de IPs</h2>
              <p class="text-muted mb-0">Administración y control de direcciones IP registradas.</p>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
              <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarIp">
                <i class="ti ti-plus me-1"></i>Nueva IP
              </button>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarRangoCidr">
                <i class="ti ti-sitemap me-1"></i>Registrar Rango CIDR
              </button>
            </div>
          </div>
        </div>

        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
              <li class="nav-item"><a class="nav-link" href="?module=inventario&action=activos"><i class="ti ti-devices me-1"></i>Activos</a></li>
              <li class="nav-item"><a class="nav-link" href="?module=inventario&action=tipoCaracteristicas"><i class="ti ti-category me-1"></i>Tipo Características</a></li>
              <li class="nav-item"><a class="nav-link" href="?module=inventario&action=caracteristicas"><i class="ti ti-adjustments me-1"></i>Características</a></li>
              <li class="nav-item"><a class="nav-link" href="?module=inventario&action=ubicaciones"><i class="ti ti-map-pin me-1"></i>Ubicaciones</a></li>
              <li class="nav-item"><a class="nav-link active" href="?module=inventario&action=ips"><i class="ti ti-network me-1"></i>IPs</a></li>
            </ul>
          </div>
          <div class="card-header border-top-0 pt-2 pb-2" style="background:var(--tblr-bg-surface,#fff);">
            <h3 class="card-title mb-0"><i class="ti ti-network me-2 text-primary"></i>Listado de Direcciones IP</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table id="tablaIps" class="table table-vcenter table-mobile-md card-table table-sm">
                <thead>
                  <tr>
                    <th>Dirección IP</th>
                    <th>Rango / Máscara</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th>Registrado Por</th>
                    <th class="text-end">Acciones</th>
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
</body>

<!-- MODAL IP INDIVIDUAL -->
<div class="modal modal-blur fade" id="modalAgregarIp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header px-4 pt-4 pb-3" style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0"><i class="ti ti-network fs-2"></i></div>
          <div><h5 class="mb-0 fw-bold">Agregar IP Individual</h5><small class="text-muted">Registre una dirección IP única</small></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formNuevaIp" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="d-flex flex-column gap-3">
            <div class="seccion-card">
              <div class="seccion-header"><i class="ti ti-wifi text-primary"></i><span class="seccion-titulo text-primary">Información de Red</span></div>
              <div class="seccion-body">
                <div class="row g-3">
                  <div class="col-md-7">
                    <label class="form-label small fw-semibold">Dirección IP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" placeholder="Ej: 192.168.1.100" name="nuevaIpAddress" id="nuevaIpAddress" required>
                    <div class="form-hint">Formato IPv4 válido</div>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label small fw-semibold">Máscara <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" placeholder="Ej: 255.255.255.0" name="nuevaMascara" id="nuevaMascara" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-semibold">Ubicación <span class="text-danger">*</span></label>
                    <select id="nuevoIdUbicacionIp" name="nuevoIdUbicacionIp" style="display:none" required><option value="">Seleccionar ubicación...</option></select>
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-semibold">Estado <span class="text-danger">*</span></label>
                    <select id="nuevoEstadoIp" name="nuevoEstadoIp" style="display:none" required>
                      <option value="disponible">Disponible</option>
                      <option value="asignada">Asignada</option>
                      <option value="reservada">Reservada</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL RANGO CIDR -->
<div class="modal modal-blur fade" id="modalAgregarRangoCidr" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header px-4 pt-4 pb-3" style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0"><i class="ti ti-sitemap fs-2"></i></div>
          <div><h5 class="mb-0 fw-bold">Registrar Rango CIDR</h5><small class="text-muted">Se generarán todas las IPs del rango automáticamente</small></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formNuevoRangoCidr" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="d-flex flex-column gap-3">
            <div class="seccion-card">
              <div class="seccion-header"><i class="ti ti-wifi text-primary"></i><span class="seccion-titulo text-primary">Definición del Rango</span></div>
              <div class="seccion-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Notación CIDR <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" placeholder="Ej: 192.168.1.0/24" name="nuevoCidr" id="nuevoCidr" required autocomplete="off">
                    <div class="form-hint">Prefijo /8 hasta /30 permitido</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Ubicación <span class="text-danger">*</span></label>
                    <select id="nuevoIdUbicacionCidr" name="nuevoIdUbicacionCidr" style="display:none" required><option value="">Seleccionar ubicación...</option></select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Estado inicial <span class="text-danger">*</span></label>
                    <select id="nuevoEstadoCidr" name="nuevoEstadoCidr" style="display:none" required>
                      <option value="disponible">Disponible</option>
                      <option value="asignada">Asignada</option>
                      <option value="reservada">Reservada</option>
                    </select>
                  </div>
                  <div class="col-md-6 d-flex align-items-end">
                    <button type="button" id="btnPreviewCidr" class="btn btn-outline-primary w-100"><i class="ti ti-eye me-1"></i>Previsualizar rango</button>
                  </div>
                </div>
              </div>
            </div>
            <div id="panelPreviewCidr" style="display:none">
              <div class="seccion-card">
                <div class="seccion-header"><i class="ti ti-list-details text-primary"></i><span class="seccion-titulo text-primary">Resumen del Rango</span></div>
                <div class="seccion-body">
                  <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                      <div class="cidr-preview">
                        <div class="preview-row"><span class="preview-label">Red</span><span class="preview-value" id="pvRed">—</span></div>
                        <div class="preview-row"><span class="preview-label">Máscara</span><span class="preview-value" id="pvMascara">—</span></div>
                        <div class="preview-row"><span class="preview-label">IP inicio</span><span class="preview-value" id="pvInicio">—</span></div>
                        <div class="preview-row"><span class="preview-label">IP fin</span><span class="preview-value" id="pvFin">—</span></div>
                        <div class="preview-row"><span class="preview-label">Broadcast</span><span class="preview-value" id="pvBroadcast">—</span></div>
                      </div>
                    </div>
                    <div class="col-md-5 text-center">
                      <div class="text-muted small mb-1">IPs a registrar</div>
                      <div class="preview-count" id="pvTotal">0</div>
                      <div class="text-muted small mt-1">hosts utilizables</div>
                    </div>
                  </div>
                  <div id="pvDuplicadosWrap" class="mt-3" style="display:none">
                    <div class="alert alert-warning py-2 px-3 mb-2 small"><i class="ti ti-alert-triangle me-1"></i><span id="pvDuplicadosMsg"></span></div>
                    <div id="pvDuplicadosList" class="d-flex flex-wrap gap-1"></div>
                  </div>
                  <div id="pvAdvertenciaGrande" class="alert alert-danger py-2 px-3 mt-3 mb-0 small" style="display:none">
                    <i class="ti ti-alert-circle me-1"></i>El rango supera 1,022 IPs. Use prefijos /22 o mayores (/23, /24 … /30).
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancelar</button>
          <button type="submit" id="btnGuardarRango" class="btn btn-primary" disabled>
            <i class="ti ti-device-floppy me-1"></i>Registrar <span id="btnRangoCount"></span> IPs
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDITAR IP -->
<div class="modal modal-blur fade" id="modalEditarIp" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header px-4 pt-4 pb-3" style="background:var(--tblr-primary-lt);border-bottom:1px solid var(--tblr-border-color);flex-shrink:0">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width:46px;height:46px;background:var(--tblr-primary);flex-shrink:0"><i class="ti ti-edit fs-2"></i></div>
          <div><h5 class="mb-0 fw-bold">Editar Dirección IP</h5><small class="text-muted">Modificación de información de la IP</small></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditarIp" novalidate>
        <div class="modal-body px-4 py-3">
          <div class="d-flex flex-column gap-3">
            <div class="seccion-card">
              <div class="seccion-header"><i class="ti ti-wifi text-primary"></i><span class="seccion-titulo text-primary">Información de Red</span></div>
              <div class="seccion-body">
                <div class="row g-3">
                  <div class="col-md-7">
                    <label class="form-label small fw-semibold">Dirección IP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" name="editarIpAddress" id="editarIpAddress" required>
                    <div class="form-hint">Formato IPv4 válido</div>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label small fw-semibold">Máscara <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" name="editarMascara" id="editarMascara" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-semibold">Ubicación <span class="text-danger">*</span></label>
                    <select id="editarIdUbicacionIp" name="editarIdUbicacionIp" style="display:none" required><option value="">Seleccionar ubicación...</option></select>
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-semibold">Estado <span class="text-danger">*</span></label>
                    <select id="editarEstadoIp" name="editarEstadoIp" style="display:none" required>
                      <option value="disponible">Disponible</option>
                      <option value="asignada">Asignada</option>
                      <option value="reservada">Reservada</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="auditoria-box">
              <div class="small fw-bold text-muted text-uppercase mb-2"><i class="ti ti-shield-check me-1"></i>Auditoría</div>
              <div class="row g-2 small">
                <div class="col-6"><div class="text-muted">Usuario Creación</div><div class="fw-semibold" id="editarIpUsuarioCreacion">--</div></div>
                <div class="col-6"><div class="text-muted">Fecha Creación</div><div class="fw-semibold" id="editarIpFechaCreacion">--</div></div>
                <div class="col-6 mt-1"><div class="text-muted">Últ. Modificación</div><div class="fw-semibold" id="editarIpUsuarioModificacion">--</div></div>
                <div class="col-6 mt-1"><div class="text-muted">Fecha Modificación</div><div class="fw-semibold" id="editarIpFechaModificacion">--</div></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer px-4 pb-4 pt-2" style="border-top:1px solid var(--tblr-border-color);flex-shrink:0">
          <input type="hidden" id="editarIdIp" name="editarIdIp">
          <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="toastContainerIps" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="modules/inventario/views/js/ips.js"></script>
