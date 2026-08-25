<?php
/**
 * permisos/index.php — Gestión de Roles y Permisos de Producción Agraria.
 * Variables: $permisosModel, $conn (inyectadas por PermisosController)
 */
$rolesPA      = $permisosModel->listarRoles();
$usuariosPA   = $permisosModel->listarUsuariosProduccion();
$submodulosPA = $permisosModel->listarSubmodulos();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.chk-permiso { width: 1.1rem; height: 1.1rem; cursor: pointer; }
.tabla-permisos-header th { font-size: 0.78rem; vertical-align: middle; }
.badge-rol-admin { background: #fee2e2; color: #991b1b; }
</style>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Producción Agraria</div>
                <h2 class="page-title">
                    <i class="ti ti-shield-lock me-2"></i> Roles y Permisos
                </h2>
            </div>
            <div class="col-auto">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Volver al Módulo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <ul class="nav nav-tabs mb-4" id="tabs-permisos-pa" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-usuarios-pa" type="button">
                    <i class="ti ti-users me-2"></i> Usuarios
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-permisos-pa" type="button">
                    <i class="ti ti-lock me-2"></i> Permisos por Rol
                </button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ============ TAB 1: Asignación de rol a usuarios ============ -->
            <div class="tab-pane fade show active" id="pane-usuarios-pa">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0"><i class="ti ti-users me-2"></i> Usuarios de Producción Agraria</h3>
                        <span class="text-muted small"><?php echo count($usuariosPA); ?> usuario(s) con acceso al módulo</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($usuariosPA)): ?>
                            <div class="alert alert-warning m-3">
                                <i class="ti ti-alert-circle me-2"></i>
                                No se encontraron usuarios con acceso al módulo de Producción Agraria.
                                Verifique los permisos en <code>comun.Permisos</code>.
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table id="tabla-usuarios-roles-pa" class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Usuario</th>
                                        <th>Rol Comun</th>
                                        <th>Rol Producción</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuariosPA as $u): ?>
                                    <?php
                                        $nombreCompleto = htmlspecialchars(trim($u['nombres'] . ' ' . $u['apellidos']), ENT_QUOTES, 'UTF-8');
                                        $rolComun  = htmlspecialchars($u['Rol_Comun'] ?? '—', ENT_QUOTES, 'UTF-8');
                                        $idRolPA   = (int)($u['Id_Rol_PA'] ?? 0);
                                        $rolPA     = htmlspecialchars($u['Rol_PA'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $fechaAsig = '';
                                        if (!empty($u['Fecha_Asignacion'])) {
                                            $fa = $u['Fecha_Asignacion'];
                                            $fechaAsig = is_object($fa) ? $fa->format('d/m/Y') : substr((string)$fa, 0, 10);
                                        }
                                        $rolComunLower = strtolower($rolComun);
                                        $badgeComun = in_array($rolComunLower, ['admin','administrador','superadmin','super admin']) ? 'badge-rol-admin' : 'bg-secondary';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $nombreCompleto; ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($u['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="badge <?php echo $badgeComun; ?>"><?php echo $rolComun; ?></span></td>
                                        <td>
                                            <?php if ($idRolPA): ?>
                                                <span class="badge bg-teal"><?php echo $rolPA; ?></span>
                                                <?php if ($fechaAsig): ?>
                                                    <br><small class="text-muted"><?php echo $fechaAsig; ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin rol</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-asignar-rol-pa"
                                                data-id="<?php echo (int)$u['id_usuario']; ?>"
                                                data-nombre="<?php echo $nombreCompleto; ?>"
                                                data-rol="<?php echo $idRolPA; ?>">
                                                <i class="ti ti-user-check me-1"></i>Asignar Rol
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ============ TAB 2: Roles y Permisos ============ -->
            <div class="tab-pane fade" id="pane-permisos-pa">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-0"><i class="ti ti-lock me-2"></i>Roles de Producción Agraria</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-crear-rol-pa">
                        <i class="ti ti-plus me-1"></i> Nuevo Rol
                    </button>
                </div>

                <div class="row row-cards mb-4">
                    <?php foreach ($rolesPA as $r): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100" style="border-top: 3px solid #009540;">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="avatar avatar-sm bg-green-lt me-2">
                                            <i class="ti ti-lock text-green"></i>
                                        </span>
                                        <strong><?php echo htmlspecialchars($r['Nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                    <span class="badge bg-green-lt text-green"><?php echo (int)$r['Total_Usuarios']; ?> usuario(s)</span>
                                </div>
                                <p class="text-muted small mb-3 mt-2">
                                    <?php echo htmlspecialchars($r['Descripcion'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-primary btn-sm btn-editar-permisos-pa flex-fill"
                                        data-id="<?php echo (int)$r['Id_Rol_PA']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($r['Nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="ti ti-table-options me-1"></i> Editar Permisos
                                    </button>
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm btn-eliminar-rol-pa"
                                        data-id="<?php echo (int)$r['Id_Rol_PA']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($r['Nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($rolesPA)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">No hay roles creados aún. Crea el primero.</div>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="panel-editar-permisos-pa" class="d-none">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="ti ti-lock me-2"></i>
                                Permisos de: <span id="label-rol-editando-pa" class="text-primary ms-1">—</span>
                            </h3>
                            <button type="button" class="btn btn-sm btn-ghost-secondary" id="btn-cerrar-permisos-pa">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-vcenter text-center mb-0" id="tabla-permisos-pa">
                                    <thead class="table-dark">
                                        <tr class="tabla-permisos-header">
                                            <th class="text-start ps-3" style="min-width:220px;">Submódulo</th>
                                            <th style="min-width:120px;"><i class="ti ti-eye mb-1" title="Visible"></i><br><small>Visible</small></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-permisos-pa"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-toggle-all-pa">
                                <i class="ti ti-checkbox me-1"></i> Marcar / Desmarcar todo
                            </button>
                            <button type="button" class="btn btn-success" id="btn-guardar-permisos-pa">
                                <i class="ti ti-device-floppy me-1"></i> Guardar Permisos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /tab-content -->
    </div>
</div>

<!-- ============ MODAL: Crear rol ============ -->
<div class="modal fade" id="modal-crear-rol-pa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-plus me-2"></i> Nuevo Rol de Producción Agraria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="input-rol-pa-nombre" class="form-control" placeholder="Ej: Operador de Punto de Venta" maxlength="100">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea id="input-rol-pa-descripcion" class="form-control" rows="3" placeholder="Describe las responsabilidades..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-crear-rol-pa">
                    <i class="ti ti-check me-1"></i> Crear Rol
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL: Asignar rol ============ -->
<div class="modal fade" id="modal-asignar-rol-pa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-user-check me-2"></i><span id="modal-asignar-pa-titulo">Asignar Rol</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-pa-id-usuario">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuario</label>
                    <p class="text-secondary mb-0" id="modal-pa-nombre-usuario">—</p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rol de Producción</label>
                    <select id="modal-pa-select-rol" class="form-select">
                        <option value="0">— Sin rol (quitar acceso) —</option>
                        <?php foreach ($rolesPA as $r): ?>
                            <option value="<?php echo (int)$r['Id_Rol_PA']; ?>">
                                <?php echo htmlspecialchars($r['Nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="modal-pa-rol-descripcion" class="alert alert-info d-none small py-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-asignar-rol-pa">
                    <i class="ti ti-check me-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    'use strict';

    const API = '<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=';

    // Descripciones de roles (para el modal de asignación)
    const rolesData = <?php
        $rolesJs = [];
        foreach ($rolesPA as $r) {
            $rolesJs[(int)$r['Id_Rol_PA']] = htmlspecialchars((string)($r['Descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
        }
        echo json_encode($rolesJs);
    ?>;

    // Tabla de usuarios con DataTables
    if (document.getElementById('tabla-usuarios-roles-pa')) {
        $('#tabla-usuarios-roles-pa').DataTable({
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ usuarios',
                infoEmpty: 'Sin resultados',
                zeroRecords: 'No se encontraron usuarios',
                paginate: { first: 'Primera', last: 'Última', next: '›', previous: '‹' }
            },
            order: [[0, 'asc']],
            pageLength: 15,
            columnDefs: [{ orderable: false, targets: 4 }]
        });
    }

    // ── Modal asignar rol ──
    const modalAsig = new bootstrap.Modal(document.getElementById('modal-asignar-rol-pa'));
    const selRol = document.getElementById('modal-pa-select-rol');
    const descDiv = document.getElementById('modal-pa-rol-descripcion');

    $(document).on('click', '.btn-asignar-rol-pa', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const rolAct = parseInt($(this).data('rol')) || 0;
        document.getElementById('modal-pa-id-usuario').value = id;
        document.getElementById('modal-pa-nombre-usuario').textContent = nombre;
        selRol.value = rolAct;
        actualizarDescripcionPa();
        document.getElementById('modal-asignar-pa-titulo').textContent = rolAct ? 'Cambiar Rol' : 'Asignar Rol';
        modalAsig.show();
    });

    selRol.addEventListener('change', actualizarDescripcionPa);

    function actualizarDescripcionPa() {
        const id = parseInt(selRol.value) || 0;
        const desc = rolesData[id] || '';
        if (id && desc) { descDiv.textContent = desc; descDiv.classList.remove('d-none'); }
        else { descDiv.classList.add('d-none'); }
    }

    document.getElementById('btn-confirmar-asignar-rol-pa').addEventListener('click', function () {
        const id_usuario = document.getElementById('modal-pa-id-usuario').value;
        const id_rol = parseInt(selRol.value) || 0;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        $.post(API + 'asignar_rol_pa', { id_usuario: id_usuario, id_rol: id_rol }, function (res) {
            if (res.success) {
                modalAsig.hide();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                    .then(function () { window.location.reload(); });
            } else {
                Swal.fire('Error', res.message || 'Error al asignar rol.', 'error');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
        }).always(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i> Confirmar';
        });
    });

    // ── Tab 2: matriz de permisos ──
    const submodulos = <?php
        $subArr = [];
        foreach ($submodulosPA as $s) {
            $subArr[] = ['id' => (int)$s['Id_Submodulo_PA'], 'nombre' => htmlspecialchars($s['Nombre'], ENT_QUOTES, 'UTF-8')];
        }
        echo json_encode($subArr);
    ?>;

    let rolEditandoId = null;
    const panelPermisos = document.getElementById('panel-editar-permisos-pa');
    const labelRolEditar = document.getElementById('label-rol-editando-pa');
    const tbodyPermisos = document.getElementById('tbody-permisos-pa');

    $(document).on('click', '.btn-editar-permisos-pa', function () {
        rolEditandoId = parseInt($(this).data('id'));
        labelRolEditar.textContent = $(this).data('nombre');
        panelPermisos.classList.remove('d-none');
        panelPermisos.scrollIntoView({ behavior: 'smooth', block: 'start' });
        cargarPermisosRolPa(rolEditandoId);
    });

    document.getElementById('btn-cerrar-permisos-pa').addEventListener('click', function () {
        panelPermisos.classList.add('d-none');
        rolEditandoId = null;
    });

    function cargarPermisosRolPa(id_rol) {
        tbodyPermisos.innerHTML = '<tr><td colspan="2" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>';
        $.getJSON(API + 'permisos_rol_pa', { id_rol: id_rol }, function (res) {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo cargar.', 'error');
                return;
            }
            const permMap = {};
            (res.permisos || []).forEach(function (p) { permMap[p.Id_Submodulo_PA] = p; });
            tbodyPermisos.innerHTML = '';
            submodulos.forEach(function (s) {
                const p = permMap[s.id] || {};
                const row = document.createElement('tr');
                row.innerHTML = '<td class="text-start fw-semibold ps-3">' + s.nombre + '</td>'
                    + chkCell('ver', s.id, p.Pueden_Ver);
                tbodyPermisos.appendChild(row);
            });
        });
    }

    function chkCell(perm, idSub, valor) {
        const checked = parseInt(valor) ? 'checked' : '';
        return '<td class="text-center"><input type="checkbox" class="chk-permiso form-check-input" data-perm="' + perm + '" data-sub="' + idSub + '" ' + checked + '></td>';
    }

    document.getElementById('btn-toggle-all-pa').addEventListener('click', function () {
        const cbs = document.querySelectorAll('#tbody-permisos-pa .chk-permiso');
        const hayDesmarcado = Array.from(cbs).some(c => !c.checked);
        cbs.forEach(c => { c.checked = hayDesmarcado; });
    });

    document.getElementById('btn-guardar-permisos-pa').addEventListener('click', function () {
        if (!rolEditandoId) return;
        const filasSub = {};
        document.querySelectorAll('#tbody-permisos-pa .chk-permiso').forEach(function (chk) {
            const sub = chk.dataset.sub;
            const perm = chk.dataset.perm;
            if (!filasSub[sub]) filasSub[sub] = { id_submodulo: sub };
            filasSub[sub][perm] = chk.checked ? 1 : 0;
        });
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        $.post(API + 'guardar_permisos_pa', { id_rol: rolEditandoId, permisos: JSON.stringify(Object.values(filasSub)) }, function (res) {
            if (res.success) Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false });
            else Swal.fire('Error', res.message || 'Error al guardar.', 'error');
        }, 'json').fail(function () {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
        }).always(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Guardar Permisos';
        });
    });

    // ── Crear rol ──
    document.getElementById('btn-confirmar-crear-rol-pa').addEventListener('click', function () {
        const nombre = document.getElementById('input-rol-pa-nombre').value.trim();
        const descripcion = document.getElementById('input-rol-pa-descripcion').value.trim();
        const inputNombre = document.getElementById('input-rol-pa-nombre');
        if (!nombre) { inputNombre.classList.add('is-invalid'); return; }
        inputNombre.classList.remove('is-invalid');
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creando...';
        $.post(API + 'crear_rol_pa', { nombre: nombre, descripcion: descripcion }, function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modal-crear-rol-pa')).hide();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                    .then(function () { window.location.reload(); });
            } else {
                Swal.fire('Error', res.message || 'Error al crear rol.', 'error');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
        }).always(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i> Crear Rol';
        });
    });

    document.getElementById('modal-crear-rol-pa').addEventListener('hidden.bs.modal', function () {
        document.getElementById('input-rol-pa-nombre').value = '';
        document.getElementById('input-rol-pa-descripcion').value = '';
        document.getElementById('input-rol-pa-nombre').classList.remove('is-invalid');
    });

    // ── Eliminar rol ──
    $(document).on('click', '.btn-eliminar-rol-pa', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        Swal.fire({
            title: '¿Eliminar rol?',
            text: 'Se quitará el rol "' + nombre + '" y sus permisos asignados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.post(API + 'eliminar_rol_pa', { id_rol: id }, function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                        .then(function () { window.location.reload(); });
                } else {
                    Swal.fire('Error', res.message || 'Error al eliminar rol.', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Error de conexión al servidor.', 'error');
            });
        });
    });
});
</script>