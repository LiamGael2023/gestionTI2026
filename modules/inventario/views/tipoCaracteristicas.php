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
                            <div class="text-muted mt-1">
                                Gestión de datos base del sistema de inventario.
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                            <button class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalAgregarTipoCaracteristica">
                                <i class="ti ti-plus me-1"></i>
                                Agregar Tipo Caracteristica
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TABS -->
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link" href="?module=inventario&action=activos">
                                    <i class="ti ti-devices me-1"></i> Activos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="?module=inventario&action=tipoCaracteristicas">
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
                            <table id="tablaTipoCaracteristicas" class="table table-vcenter table-mobile-md card-table table-sm">
                                <thead>
                                    <tr>
                                        <th>Descripción</th>
                                        <th>Fecha de Creación</th>
                                        <th class="d-none d-sm-table-cell">Registrado Por</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $item  = null;
                                    $valor = null;
                                    $tipoCaracteristicas = TipoCaracteristicasController::ctrMostrarTipoCaracteristicas($item, $valor);

                                    foreach ($tipoCaracteristicas as $key => $value) {
                                        $descripcion = htmlspecialchars($value["descripcion"], ENT_QUOTES, 'UTF-8');
                                        $fecha       = $value["fechaCreacion"] instanceof DateTime
                                                        ? $value["fechaCreacion"]->format('d/m/Y')
                                                        : "Sin fecha";
                                        echo '
                                        <tr>
                                            <td data-label="Descripción">
                                                <div class="font-weight-medium">' . $descripcion . '</div>
                                            </td>
                                            <td data-label="Fecha" class="text-muted small">' . $fecha . '</td>
                                            <td data-label="Usuario" class="d-none d-sm-table-cell">
                                                <span class="badge badge-outline text-muted fw-normal">ID: ' . htmlspecialchars($value["idUsuarioRegistro"], ENT_QUOTES) . '</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-list justify-content-end">
                                                    <button class="btn btn-sm btn-icon btn-outline-primary btnEditarTipoCaracteristica"
                                                            data-id="' . (int)$value["idTipoCaracteristica"] . '"
                                                            title="Editar">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-outline-danger btnEliminarTipoCaracteristica"
                                                            data-id="' . (int)$value["idTipoCaracteristica"] . '"
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
     Modal Agregar Tipo de Característica
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalAgregarTipoCaracteristica" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formNuevoTipoCaracteristica" method="POST">

                <div class="modal-header py-3">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ti ti-list-details text-primary"></i>
                        Agregar Tipo de Característica
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">
                            Descripción
                            <span class="text-muted">(máx. 100 caracteres)</span>
                        </label>
                        <div class="position-relative">
                            <input type="text"
                                maxlength="100"
                                class="form-control pe-5"
                                name="nuevaDescripcion"
                                id="nuevaDescripcion"
                                placeholder="Ej: Marca, Modelo, Tipo Pantalla..."
                                style="text-transform:uppercase"
                                required>
                            <small class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted">0 / 100</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
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
     Modal Editar Tipo de Característica
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalEditarTipoCaracteristica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditarTipoCaracteristica" method="POST">

                <div class="modal-header py-3">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ti ti-list-details text-primary"></i>
                        Editar Tipo de Característica
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="editarIdTipoCaracteristica" name="editarIdTipoCaracteristica" value="">

                    <div class="mb-4">
                        <label class="form-label">
                            Descripción
                            <span class="text-muted">(máx. 100 caracteres)</span>
                        </label>
                        <div class="position-relative">
                            <input type="text"
                                maxlength="100"
                                class="form-control pe-5"
                                name="editarDescripcion"
                                id="editarDescripcion"
                                placeholder="Ej: Marca, Modelo, Tipo Pantalla..."
                                style="text-transform:uppercase"
                                required>
                            <small class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted">0 / 100</small>
                        </div>
                    </div>

                    <!-- AUDITORÍA -->
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-2">
                                <div class="text-muted small">Usuario creación</div>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="ti ti-user text-primary"></i>
                                    <span class="small fw-medium" id="editarUsuarioCreacion">N/A</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-2">
                                <div class="text-muted small">Fecha creación</div>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="ti ti-calendar text-primary"></i>
                                    <span class="small fw-medium" id="editarFechaCreacion">N/A</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Guardar cambios
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════
     Modal Confirmar Eliminación
════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalConfirmarEliminarTipo" tabindex="-1">
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
                <p class="text-muted mb-1">¿Estás seguro de que deseas eliminar:</p>
                <p class="fw-bold mb-0" id="eliminarNombreTipo"></p>
                <p class="text-muted small mt-2 mb-0">
                    Esta acción es reversible solo desde la base de datos.
                    Si tiene características asociadas, no podrá eliminarse.
                </p>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminarTipo">
                    <i class="ti ti-trash me-1"></i>Sí, eliminar
                </button>
            </div>

        </div>
    </div>
</div>


<div id="toastContainerTipoCaracteristica" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
<script src="modules/inventario/views/js/tipoCaracteristicas.js"></script>
