<body>
  <div class="page">

<?php include __DIR__ . '/_submenu.php'; ?>

    <div class="page-wrapper">
      <div class="container-xl">

        <!-- PAGE HEADER -->
        <div class="page-header d-print-none">
          <div class="row align-items-center">
            <div class="col">
              <h2 class="page-title">Configuraciones</h2>
              <div class="text-muted mt-1">Gestión de datos base del sistema de inventario.</div>
            </div>
            <div class="col-auto ms-auto">
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarCaracteristica">
                <i class="ti ti-plus me-1"></i> Agregar Caracteristica
              </button>
            </div>
          </div>
        </div>

        <!-- TABS -->
        <div class="card mb-4">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=activos"><i class="ti ti-devices me-1"></i> Activos</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=tipoCaracteristicas"><i class="ti ti-category me-1"></i> Tipo Caracteristicas</a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" href="?module=inventario&action=caracteristicas"><i class="ti ti-adjustments me-1"></i> Caracteristicas</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=ubicaciones"><i class="ti ti-map-pin me-1"></i> Ubicaciones</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="?module=inventario&action=ips"><i class="ti ti-network me-1"></i> IPs</a>
              </li>
            </ul>
          </div>

          <!-- TABLE -->
          <div class="card-body p-0">
            <div class="table-responsive">
              <table id="tablaCaracteristicas" class="table table-vcenter table-mobile-md card-table table-sm">
                <thead>
                  <tr>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Fecha de Creación</th>
                    <th class="d-none d-sm-table-cell">Registrado Por</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $caracteristicas = CaracteristicasController::ctrMostrarCaracteristicas(null, null);

                  // Protección: si no es array (error de BD) se muestra tabla vacía
                  if (!is_array($caracteristicas)) $caracteristicas = [];

                  foreach ($caracteristicas as $value) {
                      $tipo  = htmlspecialchars($value["tipoDescripcion"] ?? '', ENT_QUOTES, 'UTF-8');
                      $val   = htmlspecialchars($value["valor"] ?? '', ENT_QUOTES, 'UTF-8');
                      $idCar = (int)($value["idCaracteristica"] ?? 0);

                      $fechaRaw = $value["fechaCreacion"] ?? null;
                      if ($fechaRaw instanceof DateTime) {
                          $fecha = $fechaRaw->format('d/m/Y');
                      } elseif (is_string($fechaRaw) && $fechaRaw !== '') {
                          $fecha = date('d/m/Y', strtotime($fechaRaw));
                      } else {
                          $fecha = 'Sin fecha';
                      }

                      echo '
                      <tr>
                        <td data-label="Tipo" class="text-muted small">' . $tipo . '</td>
                        <td data-label="Valor" class="text-muted small">' . $val . '</td>
                        <td data-label="Fecha" class="text-muted small">' . $fecha . '</td>
                        <td data-label="Usuario" class="d-none d-sm-table-cell">
                          <span class="badge badge-outline text-muted fw-normal">ID: ' . htmlspecialchars($value["idUsuarioCreacion"] ?? 'N/A', ENT_QUOTES) . '</span>
                        </td>
                        <td class="text-end">
                          <div class="btn-list justify-content-end">
                            <button class="btn btn-sm btn-icon btn-outline-primary btnEditarCaracteristica"
                                    data-id="' . $idCar . '" title="Editar">
                              <i class="ti ti-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-outline-danger btnEliminarCaracteristica"
                                    data-id="' . $idCar . '"
                                    data-descripcion="' . $val . '"
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
                <p class="text-muted mt-2 mb-0">Las categorías afectan a todos los equipos vinculados.</p>
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
     Modal Agregar Característica
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalAgregarCaracteristica" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formNuevoCaracteristica" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title d-flex align-items-center gap-3">
            <div class="avatar avatar-sm bg-primary-lt text-primary"><i class="ti ti-settings"></i></div>
            Agregar Característica
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label">Tipo de Característica</label>
            <select name="idTipoCaracteristica" id="nuevoSelectTipo" class="form-select" required>
              <option value="" selected disabled>Seleccionar tipo...</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label">Valor</label>
            <input name="nuevoValor" id="nuevoValor" type="text" class="form-control"
                   placeholder='Ej: DELL; LATITUDE 5420; LED; 14"'
                   style="text-transform:uppercase" required>
            <div class="form-text small">Incluye unidad si aplica (ej. 24 PULGADAS, 256 GB).</div>
          </div>
          <div class="d-flex align-items-center gap-2 text-muted mb-3">
            <i class="ti ti-history"></i>
            <span class="text-uppercase small fw-bold">Información de Auditoría</span>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">Usuario Creación</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-user text-primary"></i>
                    <span class="fw-medium" id="nuevoUsuarioCreacion">--</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">Fecha Creación</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-calendar text-primary"></i>
                    <span class="fw-medium" id="nuevoFechaCreacion">--</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════
     Modal Editar Característica
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalEditarCaracteristica" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditarCaracteristica" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title d-flex align-items-center gap-3">
            <div class="avatar avatar-sm bg-primary-lt text-primary"><i class="ti ti-edit"></i></div>
            Editar Característica
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="editarIdCaracteristica" id="editarIdCaracteristica" value="">
          <div class="mb-4">
            <label class="form-label">Tipo de Característica</label>
            <select name="editarIdTipoCaracteristica" id="editarSelectTipo" class="form-select" required>
              <option value="" disabled>Seleccionar tipo...</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label">Valor</label>
            <input name="editarValor" id="editarValor" type="text" class="form-control"
                   placeholder="Ingrese el valor"
                   style="text-transform:uppercase" required>
          </div>
          <div class="d-flex align-items-center gap-2 text-muted mb-3">
            <i class="ti ti-history"></i>
            <span class="text-uppercase small fw-bold">Información de Auditoría</span>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">Usuario Creación</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-user text-primary"></i>
                    <span class="fw-medium" id="editarUsuarioCreacion">--</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">Fecha Creación</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-calendar text-primary"></i>
                    <span class="fw-medium" id="editarFechaCreacion">--</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">Usuario Modificación</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-edit text-primary"></i>
                    <span class="fw-medium" id="editarUsuarioModifica">--</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card card-sm">
                <div class="card-body">
                  <div class="text-muted small text-uppercase mb-1">Fecha Modificación</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-refresh text-primary"></i>
                    <span class="fw-medium" id="editarFechaModificacion">--</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════
     Modal Confirmar Eliminación
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalConfirmarEliminarCaracteristica" tabindex="-1">
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
        <p class="fw-bold mb-0" id="eliminarNombreCaracteristica"></p>
        <p class="text-muted small mt-2 mb-0">Esta acción es reversible solo desde la base de datos.</p>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminarCaracteristica">
          <i class="ti ti-trash me-1"></i>Sí, eliminar
        </button>
      </div>
    </div>
  </div>
</div>


<div id="toastContainerCaracteristicas" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="modules/inventario/views/js/caracteristicas.js"></script>
