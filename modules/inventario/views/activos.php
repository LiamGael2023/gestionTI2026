<body>
  <?php
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }
  ?>
  <div class="page">
<?php include __DIR__ . '/_submenu.php'; ?>

    <div class="page-wrapper">
      <div class="container-xl">

        <!-- PAGE HEADER -->
        <div class="page-header d-print-none">
          <div class="row align-items-center">
            <div class="col">
              <h2 class="page-title">Configuraciones</h2>
              <div class="text-muted mt-1">
                Gestión de datos base del sistema de inventario.
              </div>
            </div>
            <div class="col-auto ms-auto">
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarActivo">
                <i class="ti ti-plus me-1"></i>
                Agregar Activos
              </button>
            </div>
          </div>
        </div>

        <!-- TABS -->
        <div class="card mb-4">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
              <li class="nav-item">
                <a class="nav-link active" href="?module=inventario&action=activos">
                  <i class="ti ti-devices me-1"></i> Activos
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=tipoCaracteristicas">
                  <i class="ti ti-category me-1"></i> Tipo Caracteristicas
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=caracteristicas">
                  <i class="ti ti-adjustments me-1"></i> Caracteristicas
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=ubicaciones">
                  <i class="ti ti-map-pin me-1"></i> Ubicaciones
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=ips">
                  <i class="ti ti-network me-1"></i> IPs
                </a>
              </li>
            </ul>
          </div>

          <!-- TABLE -->
          <div class="card-body p-0">
            <div class="table-responsive">
              <table id="tablaActivos" class="table table-vcenter table-mobile-md card-table table-sm">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Fecha de Creación</th>
                    <th class="d-none d-sm-table-cell">Registrado Por</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $item   = null;
                  $valor  = null;
                  $activos = ActivosController::ctrMostrarActivos($item, $valor);

                  foreach ($activos as $key => $value) {
                    $descripcion = htmlspecialchars($value["descripcion"], ENT_QUOTES, 'UTF-8');
                    $icono       = $value["icono"] ?: 'ti-package';
                    $fecha       = $value["fechaCreacion"] instanceof DateTime
                                    ? $value["fechaCreacion"]->format('d/m/Y')
                                    : "Sin fecha";
                    echo '
                    <tr>
                      <td data-label="Nombre">
                        <div class="d-flex align-items-center">
                          <i class="ti ' . $icono . ' text-primary me-2 fs-3"></i>
                          <div class="font-weight-medium">' . $descripcion . '</div>
                        </div>
                      </td>
                      <td data-label="Fecha" class="text-muted small">' . $fecha . '</td>
                      <td data-label="Usuario" class="d-none d-sm-table-cell">
                        <span class="badge badge-outline text-muted fw-normal">ID: ' . $value["idUsuarioRegistro"] . '</span>
                      </td>
                      <td class="text-end">
                        <div class="btn-list justify-content-end">
                          <button class="btn btn-sm btn-icon btn-outline-primary btnEditarActivo"
                                  data-id="' . $value["idActivos"] . '"
                                  title="Editar">
                            <i class="ti ti-edit"></i>
                          </button>
                          <button class="btn btn-sm btn-icon btn-outline-danger btnEliminarActivo"
                                  data-id="' . $value["idActivos"] . '"
                                  data-descripcion="' . $descripcion . '"
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

        <!-- INFO CARDS -->
        <div class="row row-deck row-cards">
          <div class="col-md-4">
            <div class="card bg-primary-lt">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <i class="ti ti-info-circle text-primary me-2"></i>
                  <strong>Información del Sistema</strong>
                </div>
                <p class="text-muted mt-2 mb-0">
                  Las categorías afectan a todos los equipos vinculados.
                </p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-body">
                <strong>Últimos Cambios</strong>
                <ul class="mt-2">
                  <li>Nueva ubicación agregada</li>
                  <li>Categoría editada hace 2 horas</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-body">
                <strong>Resumen</strong>
                <div class="mt-2">
                  <div>Total Categorías: <strong>12</strong></div>
                  <div>Bajo Uso: <strong class="text-warning">3</strong></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</body>


<!-- ════════════════════════════════════════
     Modal Agregar Activo
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalAgregarActivo" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formNuevoActivo" method="POST">

        <div class="modal-header py-3">
          <h5 class="modal-title d-flex align-items-center gap-2">
            <div class="avatar avatar-sm bg-primary-lt text-primary">
              <i class="ti ti-package"></i>
            </div>
            Agregar Activo
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body pt-3">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <input type="text" class="form-control" id="nuevaDescripcion" name="nuevaDescripcion"
                     maxlength="150" placeholder="Describa el activo..." required
                     style="text-transform:uppercase">
            </div>

            <div class="col-md-7">
              <label class="form-label">Tipo de Activo</label>
              <select class="form-select mb-2" id="tipoIcono">
                <option value="">Seleccione</option>
                <option value="equipos">Equipos</option>
                <option value="componentes">Componentes</option>
                <option value="perifericos">Perifericos</option>
                <option value="pantallas">Pantallas</option>
                <option value="impresion">Impresion</option>
                <option value="red">Red</option>
              </select>
              <input type="hidden" name="iconoActivo" id="iconoActivo" value="">
              <div id="listaIconos" class="row g-2 border rounded-3 p-2" style="max-height:160px; overflow-y:auto;"></div>
            </div>

            <div class="col-md-5">
              <label class="form-label">Vista previa</label>
              <div class="card card-sm text-center">
                <div class="card-body">
                  <div class="avatar avatar-xl bg-primary-lt text-primary mb-2" id="previewIcon">
                    <i class="ti ti-help"></i>
                  </div>
                  <div class="text-muted small">Icono seleccionado</div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="card card-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-components text-primary"></i>
                    <div>
                      <div class="fw-semibold small">Equipo compuesto</div>
                      <div class="text-muted small">Contiene sub-componentes</div>
                    </div>
                  </div>
                  <label class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="nuevoCompuesto" value="1">
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer py-2">
          <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>Guardar
          </button>
        </div>

      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════
     Modal Editar Activo
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalEditarActivo" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditarActivo">

        <div class="modal-header py-3">
          <h5 class="modal-title d-flex align-items-center gap-2">
            <div class="avatar avatar-sm bg-primary-lt text-primary">
              <i class="ti ti-edit"></i>
            </div>
            Editar Activo
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body pt-3">
          <input type="hidden" id="editarIdActivo" name="editarIdActivo">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <input type="text" class="form-control" id="editarDescripcion" name="editarDescripcion"
                     maxlength="150" required style="text-transform:uppercase">
            </div>

            <div class="col-md-7">
              <label class="form-label">Tipo de Activo</label>
              <select class="form-select mb-2" id="editarTipoIcono">
                <option value="">Seleccione</option>
                <option value="equipos">Equipos</option>
                <option value="componentes">Componentes</option>
                <option value="perifericos">Periféricos</option>
                <option value="pantallas">Pantallas</option>
                <option value="impresion">Impresión</option>
                <option value="red">Red</option>
              </select>
              <input type="hidden" id="editarIconoActivo" name="editarIconoActivo">
              <div id="editarListaIconos" class="row g-2 border rounded-3 p-2" style="max-height:160px; overflow-y:auto;"></div>
            </div>

            <div class="col-md-5">
              <label class="form-label">Vista previa</label>
              <div class="card card-sm text-center">
                <div class="card-body">
                  <div class="avatar avatar-xl bg-primary-lt text-primary mb-2" id="editarPreviewIcon">
                    <i class="ti ti-help"></i>
                  </div>
                  <div class="text-muted small">Icono seleccionado</div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="card card-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-components text-primary"></i>
                    <div>
                      <div class="fw-semibold small">Equipo compuesto</div>
                      <div class="text-muted small">Contiene sub-componentes</div>
                    </div>
                  </div>
                  <label class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" id="editarCompuesto" name="editarCompuesto" value="1">
                  </label>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="row g-2">
                <div class="col-md-6">
                  <div class="border rounded-3 p-2">
                    <div class="text-muted small">Usuario creación</div>
                    <div class="d-flex align-items-center gap-1">
                      <i class="ti ti-user text-primary"></i>
                      <span class="small fw-medium" id="editarUsuarioCreacion"></span>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="border rounded-3 p-2">
                    <div class="text-muted small">Fecha creación</div>
                    <div class="d-flex align-items-center gap-1">
                      <i class="ti ti-calendar text-primary"></i>
                      <span class="small fw-medium" id="editarFechaCreacion"></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer py-2">
          <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>Guardar Cambios
          </button>
        </div>

      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════
     Modal Confirmar Eliminación
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalConfirmarEliminar" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2 text-danger">
          <i class="ti ti-alert-triangle"></i>
          Confirmar eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="text-muted mb-1">¿Estás seguro de que deseas eliminar el activo:</p>
        <p class="fw-bold mb-0" id="eliminarNombreActivo"></p>
        <p class="text-muted small mt-2 mb-0">
          Esta acción es reversible solo desde la base de datos.
          Si tiene equipos asociados, no podrá eliminarse.
        </p>
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


<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="modules/inventario/views/js/activos.js"></script>
