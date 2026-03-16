<body>
  <div class="page">

    <!-- NAVBAR -->
    <header class="navbar navbar-expand-md navbar-light d-print-none shadow-sm">
      <div class="container-xl">
        <h1 class="navbar-brand">
          <i class="ti ti-package me-2 text-primary"></i>
          Inventario TI
        </h1>
      </div>
    </header>

    <div class="page-wrapper">
      <div class="container-xl">

        <!-- PAGE HEADER -->
        <div class="page-header d-print-none">
          <div class="row align-items-center">
            <div class="col">
              <h2 class="page-title">Gestión de Equipos</h2>
              <div class="text-muted mt-1">
                Administración y control de los equipos registrados.
              </div>
            </div>

            <div class="col-auto ms-auto d-flex gap-2">
              <div class="input-icon">
                <span class="input-icon-addon">
                  <i class="ti ti-search"></i>
                </span>
                <input type="text" class="form-control" placeholder="Buscar equipo...">
              </div>

              <select class="form-select w-auto">
                <option>Todos</option>
                <option>Activo</option>
                <option>Mantenimiento</option>
              </select>

              <button class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalAgregarEquipo">
                <i class="ti ti-plus me-1"></i>
                Nuevo Equipo
              </button>
            </div>
          </div>
        </div>

        <!-- CARD PRINCIPAL -->
        <div class="card mb-4 shadow-sm">
          <div class="card-header">
            <h3 class="card-title">
              <i class="ti ti-devices me-2 text-primary"></i>
              Listado de Equipos
            </h3>
          </div>

          <!-- TABLE -->
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-vcenter table-hover">
                <thead class="bg-light">
                  <tr>
                    <th>Equipo</th>
                    <th>N° Serie</th>
                    <th>Código Patrimonial</th>
                    <th>Estado</th>
                    <th>Registrado Por</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>

                  <tr>
                    <td class="fw-semibold">
                      <i class="ti ti-device-laptop text-primary me-2"></i>
                      Laptop Dell XPS 15
                    </td>
                    <td>SN-48293-XPS</td>
                    <td>PAT-2023-0042</td>
                    <td>
                      <span class="badge bg-success-lt text-success">
                        <i class="ti ti-check me-1"></i> Activo
                      </span>
                    </td>
                    <td>admin_user</td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-icon btn-outline-primary" title="Editar">
                        <i class="ti ti-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-icon btn-outline-danger" title="Eliminar">
                        <i class="ti ti-trash"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td class="fw-semibold">
                      <i class="ti ti-device-desktop text-warning me-2"></i>
                      Workstation HP Z4
                    </td>
                    <td>SN-99120-HPW</td>
                    <td>PAT-2023-0155</td>
                    <td>
                      <span class="badge bg-warning-lt text-warning">
                        <i class="ti ti-tool me-1"></i> Mantenimiento
                      </span>
                    </td>
                    <td>editor_pro</td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-icon btn-outline-primary" title="Editar"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditarEquipo">
                        <i class="ti ti-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-icon btn-outline-danger" title="Eliminar">
                        <i class="ti ti-trash"></i>
                      </button>
                    </td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- INFO CARDS -->
        <div class="row row-deck row-cards">

          <div class="col-md-4">
            <div class="card bg-primary-lt shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                  <i class="ti ti-info-circle text-primary me-2 fs-4"></i>
                  <strong>Información General</strong>
                </div>
                <p class="text-muted mb-0">
                  Los estados determinan la disponibilidad del equipo dentro del inventario.
                </p>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card shadow-sm">
              <div class="card-body">
                <strong>Últimos Movimientos</strong>
                <ul class="mt-3 small">
                  <li>Equipo agregado hoy</li>
                  <li>Equipo enviado a mantenimiento</li>
                  <li>Equipo actualizado hace 1 hora</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card shadow-sm">
              <div class="card-body">
                <strong>Resumen</strong>
                <div class="mt-3">
                  <div class="mb-2">
                    Total Equipos: <strong>1,240</strong>
                  </div>
                  <div>
                    En Mantenimiento:
                    <strong class="text-warning">42</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>
</body>


<!-- MODAL AGREGAR / EDITAR EQUIPO -->
<div class="modal modal-blur fade" id="modalAgregarEquipo" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header border-0 px-4 pt-4 pb-3">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary text-white rounded-4 d-flex align-items-center justify-content-center shadow-sm"
               style="width:50px;height:50px;">
            <i class="ti ti-device-laptop fs-3"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1" id="modalEquipoTitle">Agregar Equipo</h5>
            <small class="text-muted" id="modalEquipoSubtitle">Registro del equipo en inventario</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="formEquipo" class="needs-validation" novalidate>
        <div class="modal-body px-4 pb-4 pt-2">
          <div class="row g-3">
            <div class="col-lg-6 d-flex flex-column gap-3">
              <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                  <h6 class="fw-bold text-primary mb-3"><i class="ti ti-info-circle me-2"></i>Información General</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Activo</label>
                      <select id="equipoSelectActivo" name="idActivo" class="form-select" required>
                        <option value="">Seleccionar activo...</option>
                      </select>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Código Patrimonial</label>
                      <input id="equipoCodigo" name="codigoPatrimonial" type="text" class="form-control" placeholder="CP-2024-001">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Número de Serie</label>
                      <input id="equipoSerie" name="numeroSerie" type="text" class="form-control" placeholder="SN-XJK9201LH">
                    </div>
                  </div>
                </div>
              </div>

              <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                  <h6 class="fw-bold text-primary mb-3"><i class="ti ti-calendar me-2"></i>Fechas y Garantía</h6>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Fecha Adquisición</label>
                      <input id="equipoFechaAdq" name="fechaAdquisicion" type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Inicio Garantía</label>
                      <input id="equipoFechaInicioG" name="fechaInicioGarantia" type="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">Fin Garantía</label>
                      <input id="equipoFechaFinG" name="fechaFinGarantia" type="date" class="form-control">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Auditoría -->
              <div class="card border-0 bg-light rounded-4">
                <div class="card-body p-3 small">
                  <div class="fw-bold text-muted text-uppercase mb-2">Auditoría</div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="text-muted">Usuario Creación</div>
                      <div class="fw-semibold" id="equipoUsuarioCreacion">--</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted">Fecha Creación</div>
                      <div class="fw-semibold" id="equipoFechaCreacion">--</div>
                    </div>
                    <div class="col-md-6 mt-2">
                      <div class="text-muted">Usuario Modificación</div>
                      <div class="fw-semibold" id="equipoUsuarioModificacion">--</div>
                    </div>
                    <div class="col-md-6 mt-2">
                      <div class="text-muted">Fecha Modificación</div>
                      <div class="fw-semibold" id="equipoFechaModificacion">--</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>

            <!-- Columna derecha: características -->
            <div class="col-lg-6">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                  <h6 class="fw-bold text-primary mb-3"><i class="ti ti-settings me-2"></i>Características Técnicas</h6>

                  <div class="bg-light rounded-4 p-3 border mb-3">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Tipo de Característica</label>
                        <select id="nuevoSelectTipo" class="form-select"></select>
                      </div>

                      <div class="col-md-5">
                        <label class="form-label small fw-semibold text-muted">Valor Disponible</label>
                        <select id="nuevoSelectValor" class="form-select"></select>
                      </div>

                      <div class="col-md-3">
                        <button id="btnAgregarCaracteristica" type="button" class="btn btn-primary w-100 rounded-3 d-flex align-items-center justify-content-center gap-2">
                          <i class="ti ti-plus"></i> Agregar
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="table-responsive border rounded-4" style="max-height:350px; overflow-y:auto;">
                    <table class="table table-hover align-middle mb-0" id="tablaCaracteristicasEquipo">
                      <thead class="table-light sticky-top">
                        <tr>
                          <th class="small text-uppercase fw-semibold">Tipo</th>
                          <th class="small text-uppercase fw-semibold">Valor</th>
                          <th class="text-end small text-uppercase fw-semibold" width="80">Acción</th>
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

        <div class="modal-footer border-0 px-4 pb-4 pt-3">
          <input type="hidden" id="equipoId" name="idEquipo" value="">
          <input type="hidden" id="equipoCaracteristicasIds" name="idCaracteristicas" value="">
          <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnGuardarEquipo" class="btn btn-primary rounded-3 px-4 shadow-sm">
            <i class="ti ti-device-floppy me-1"></i> Guardar Equipo
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- MODAL EDITAR EQUIPO -->
<div class="modal modal-blur fade" id="modalEditarEquipo" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

      <!-- ================= HEADER ================= -->
      <div class="modal-header border-0 px-4 pt-4 pb-3">

        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary text-white rounded-4 d-flex align-items-center justify-content-center shadow-sm"
            style="width:50px;height:50px;">
            <i class="ti ti-edit fs-3"></i>
          </div>

          <div>
            <h5 class="fw-bold mb-1">Editar Equipo</h5>
            <small class="text-muted">
              Modificación de información del equipo
            </small>
          </div>
        </div>

        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form>
        <div class="modal-body px-4 pb-4 pt-2">

          <div class="row g-3">

            <!-- ================= COLUMNA IZQUIERDA ================= -->
            <div class="col-lg-6 d-flex flex-column gap-3">

              <!-- INFORMACIÓN GENERAL -->
              <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                  <h6 class="fw-bold text-primary mb-3">
                    <i class="ti ti-info-circle me-2"></i>
                    Información General
                  </h6>

                  <div class="row g-3">

                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Activo</label>
                      <select class="form-select">
                        <option selected>Laptop Dell Latitude 5420</option>
                      </select>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">
                        Código Patrimonial
                      </label>
                      <input type="text"
                        class="form-control"
                        value="CP-2024-001"
                        readonly>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">
                        Número de Serie
                      </label>
                      <input type="text"
                        class="form-control"
                        value="SN-XJK9201LH">
                    </div>

                  </div>
                </div>
              </div>

              <!-- FECHAS -->
              <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                  <h6 class="fw-bold text-primary mb-3">
                    <i class="ti ti-calendar me-2"></i>
                    Fechas y Garantía
                  </h6>

                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">
                        Fecha Adquisición
                      </label>
                      <input type="date"
                        class="form-control"
                        value="2024-05-20">
                    </div>

                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">
                        Inicio Garantía
                      </label>
                      <input type="date"
                        class="form-control"
                        value="2024-05-21">
                    </div>

                    <div class="col-md-4">
                      <label class="form-label small fw-semibold">
                        Fin Garantía
                      </label>
                      <input type="date"
                        class="form-control"
                        value="2026-05-21">
                    </div>
                  </div>

                </div>
              </div>

              <!-- AUDITORÍA COMPLETA -->
              <div class="card border-0 bg-light rounded-4">
                <div class="card-body p-3 small">

                  <div class="fw-bold text-muted text-uppercase mb-2">
                    Auditoría
                  </div>

                  <div class="row g-2">

                    <div class="col-md-6">
                      <div class="text-muted">Creado por</div>
                      <div class="fw-semibold">admin_sistemas</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-muted">Fecha Creación</div>
                      <div class="fw-semibold">24/05/2024 14:30</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-muted">Modificado por</div>
                      <div class="fw-semibold">soporte_tecnico</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-muted">Fecha Modificación</div>
                      <div class="fw-semibold">28/05/2024 10:12</div>
                    </div>

                  </div>

                </div>
              </div>

            </div>

            <!-- ================= COLUMNA DERECHA ================= -->
            <div class="col-lg-6">

              <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">

                  <h6 class="fw-bold text-primary mb-3">
                    <i class="ti ti-settings me-2"></i>
                    Características Técnicas
                  </h6>

                  <!-- FORM AGREGAR -->
                  <div class="bg-light rounded-4 p-3 border mb-3">

                    <div class="row g-3 align-items-end">

                      <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">
                          Tipo
                        </label>
                        <select class="form-select">
                          <option>Seleccionar tipo...</option>
                        </select>
                      </div>

                      <div class="col-md-5">
                        <label class="form-label small fw-semibold text-muted">
                          Valor
                        </label>
                        <select class="form-select">
                          <option>Seleccionar valor...</option>
                        </select>
                      </div>

                      <div class="col-md-3">
                        <button type="button"
                          class="btn btn-primary w-100 rounded-3 d-flex align-items-center justify-content-center gap-2">
                          <i class="ti ti-plus"></i>
                          Agregar
                        </button>
                      </div>

                    </div>

                  </div>

                  <!-- TABLA -->
                  <div class="table-responsive border rounded-4"
                    style="max-height:350px; overflow-y:auto;">

                    <table class="table table-hover align-middle mb-0">

                      <thead class="table-light sticky-top">
                        <tr>
                          <th class="small text-uppercase fw-semibold">Tipo</th>
                          <th class="small text-uppercase fw-semibold">Valor</th>
                          <th class="text-end small text-uppercase fw-semibold" width="80">
                            Acción
                          </th>
                        </tr>
                      </thead>

                      <tbody>

                        <tr>
                          <td class="fw-semibold">Marca</td>
                          <td>Dell</td>
                          <td class="text-end">
                            <button class="btn btn-sm btn-icon btn-outline-danger">
                              <i class="ti ti-trash"></i>
                            </button>
                          </td>
                        </tr>

                        <tr>
                          <td class="fw-semibold">RAM</td>
                          <td>32GB DDR4</td>
                          <td class="text-end">
                            <button class="btn btn-sm btn-icon btn-outline-danger">
                              <i class="ti ti-trash"></i>
                            </button>
                          </td>
                        </tr>

                      </tbody>

                    </table>

                  </div>

                </div>
              </div>

            </div>

          </div>

        </div>

        <!-- ================= FOOTER ================= -->
        <div class="modal-footer border-0 px-4 pb-4 pt-3">

          <button type="button"
            class="btn btn-light rounded-3 px-4"
            data-bs-dismiss="modal">
            Cancelar
          </button>

          <button type="submit"
            class="btn btn-primary rounded-3 px-4 shadow-sm">
            <i class="ti ti-check me-1"></i>
            Actualizar Equipo
          </button>

        </div>

      </form>

    </div>
  </div>
</div>

<script src="modules/inventario/views/js/caracteristicas.js"></script>