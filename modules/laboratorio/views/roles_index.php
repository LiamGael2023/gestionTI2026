<?php
/**
 * roles_index.php — Vista de gestión de roles de laboratorio.
 * Solo accesible para administradores.
 * Variables disponibles: $usuarioData, $roles, $submodulos, $usuarios, $esAdmin
 */
?>

<!-- Scripts necesarios (jQuery, DataTables, SweetAlert2) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Laboratorio</div>
                <h2 class="page-title">
                    <i class="ti ti-shield-lock me-2"></i> Gestión de Roles
                </h2>
            </div>
            <div class="col-auto">
                <a href="?module=laboratorio" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Volver al Módulo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <!-- Tabs principales -->
        <ul class="nav nav-tabs mb-4" id="tabs-roles" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-usuarios" type="button">
                    <i class="ti ti-users me-2"></i> Usuarios
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-permisos" type="button">
                    <i class="ti ti-lock me-2"></i> Permisos por Rol
                </button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ============================================================ -->
            <!-- TAB 1: Asignación de roles a usuarios                        -->
            <!-- ============================================================ -->
            <div class="tab-pane fade show active" id="pane-usuarios">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-users me-2"></i> Usuarios del Laboratorio
                        </h3>
                        <span class="text-muted small">
                            <?php echo count($usuarios); ?> usuario(s) con acceso al módulo
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($usuarios)): ?>
                            <div class="alert alert-warning m-3">
                                <i class="ti ti-alert-circle me-2"></i>
                                No se encontraron usuarios con acceso al módulo de Laboratorio.
                                Verifique los permisos en <code>comun.Permisos</code>.
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table id="tabla-usuarios-roles" class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Usuario</th>
                                        <th>Rol Comun</th>
                                        <th>Rol Laboratorio</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                    <?php
                                        $nombreCompleto = htmlspecialchars(trim($u['nombres'] . ' ' . $u['apellidos']), ENT_QUOTES, 'UTF-8');
                                        $rolComun  = htmlspecialchars($u['Rol_Comun'] ?? '—', ENT_QUOTES, 'UTF-8');
                                        $idRolLab  = (int)($u['Id_Rol_Lab'] ?? 0);
                                        $rolLab    = htmlspecialchars($u['Rol_Lab'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $fechaAsig = '';
                                        if (!empty($u['Fecha_Asignacion'])) {
                                            $fa = $u['Fecha_Asignacion'];
                                            $fechaAsig = is_object($fa) ? $fa->format('d/m/Y') : substr((string)$fa, 0, 10);
                                        }
                                        $rolComunLower = strtolower($rolComun);
                                        $badgeComun = in_array($rolComunLower, ['admin','administrador','superadmin','super admin']) ? 'bg-danger' : 'bg-secondary';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $nombreCompleto; ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($u['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="badge <?php echo $badgeComun; ?>"><?php echo $rolComun; ?></span></td>
                                        <td>
                                            <?php if ($idRolLab): ?>
                                                <span class="badge bg-teal"><?php echo $rolLab; ?></span>
                                                <?php if ($fechaAsig): ?>
                                                    <br><small class="text-muted"><?php echo $fechaAsig; ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin rol</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-asignar-rol"
                                                data-id="<?php echo (int)$u['id_usuario']; ?>"
                                                data-nombre="<?php echo $nombreCompleto; ?>"
                                                data-rol="<?php echo $idRolLab; ?>">
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

            <!-- ============================================================ -->
            <!-- TAB 2: Roles y Permisos                                      -->
            <!-- ============================================================ -->
            <div class="tab-pane fade" id="pane-permisos">

                <!-- Cabecera con botón crear -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-0"><i class="ti ti-lock me-2"></i>Roles de Laboratorio</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-crear-rol">
                        <i class="ti ti-plus me-1"></i> Nuevo Rol
                    </button>
                </div>

                <!-- Cards de roles existentes -->
                <div class="row row-cards mb-4">
                    <?php foreach ($roles as $r): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100" style="border-top: 3px solid #206bc4;">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="avatar avatar-sm bg-blue-lt me-2">
                                            <i class="ti ti-lock text-primary"></i>
                                        </span>
                                        <strong><?php echo htmlspecialchars($r['Nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                    <span class="badge bg-blue-lt text-blue"><?php echo (int)$r['Total_Usuarios']; ?> usuario(s)</span>
                                </div>
                                <p class="text-muted small mb-3 mt-2">
                                    <?php echo htmlspecialchars($r['Descripcion'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <button type="button"
                                    class="btn btn-primary btn-sm btn-editar-permisos w-100"
                                    data-id="<?php echo (int)$r['Id_Rol']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($r['Nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="ti ti-table-options me-1"></i> Editar Permisos
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($roles)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">No hay roles creados aún. Crea el primero.</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Matriz de permisos (se muestra al clickar "Editar Permisos") -->
                <div id="panel-editar-permisos" class="d-none">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="ti ti-lock me-2"></i>
                                Permisos de: <span id="label-rol-editando" class="text-primary ms-1">—</span>
                            </h3>
                            <button type="button" class="btn btn-sm btn-ghost-secondary" id="btn-cerrar-permisos">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-vcenter text-center mb-0" id="tabla-permisos">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-start ps-3" style="min-width:160px;">Submódulo</th>
                                            <th><i class="ti ti-eye" title="Ver"></i><br><small>Ver</small></th>
                                            <th><i class="ti ti-plus" title="Crear"></i><br><small>Crear</small></th>
                                            <th><i class="ti ti-pencil" title="Editar"></i><br><small>Editar</small></th>
                                            <th><i class="ti ti-trash" title="Eliminar"></i><br><small>Eliminar</small></th>
                                            <th><i class="ti ti-file-export" title="Exportar"></i><br><small>Exportar</small></th>
                                            <th><i class="ti ti-writing-sign" title="Firmar"></i><br><small>Firmar</small></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-permisos">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-toggle-all">
                                <i class="ti ti-checkbox me-1"></i> Marcar / Desmarcar todo
                            </button>
                            <button type="button" class="btn btn-success" id="btn-guardar-permisos">
                                <i class="ti ti-device-floppy me-1"></i> Guardar Permisos
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div><!-- /tab-content -->

    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: Crear nuevo rol                                        -->
<!-- ============================================================ -->
<div class="modal fade" id="modal-crear-rol" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-plus me-2"></i> Nuevo Rol de Laboratorio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="input-rol-nombre" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="input-rol-nombre" class="form-control"
                           placeholder="Ej: Técnico de Análisis" maxlength="100">
                </div>
                <div class="mb-0">
                    <label for="input-rol-descripcion" class="form-label fw-semibold">Descripción</label>
                    <textarea id="input-rol-descripcion" class="form-control" rows="3"
                              placeholder="Describe las responsabilidades de este rol..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-crear-rol">
                    <i class="ti ti-check me-1"></i> Crear Rol
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: Asignar / cambiar rol de un usuario                   -->
<!-- ============================================================ -->
<div class="modal fade" id="modal-asignar-rol" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user-check me-2"></i>
                    <span id="modal-asignar-titulo">Asignar Rol</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-id-usuario">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuario</label>
                    <p class="text-secondary mb-0" id="modal-nombre-usuario">—</p>
                </div>
                <div class="mb-3">
                    <label for="modal-select-rol" class="form-label fw-semibold">Rol de Laboratorio</label>
                    <select id="modal-select-rol" class="form-select">
                        <option value="0">— Sin rol (quitar acceso) —</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo (int)$r['Id_Rol']; ?>">
                                <?php echo htmlspecialchars($r['Nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="modal-rol-descripcion" class="alert alert-info d-none small py-2">
                    <!-- Descripción del rol seleccionado -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-asignar-rol">
                    <i class="ti ti-check me-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.chk-permiso { width: 1.1rem; height: 1.1rem; cursor: pointer; }
.tabla-permisos-header th { font-size: 0.78rem; vertical-align: middle; }
</style>

<script>
$(document).ready(function () {
    'use strict';

    const API = 'modules/laboratorio/controllers/RolesAPI.php';

    /* ── Descripción de roles (para modal) ─────────────────────────────── */
    const rolesData = <?php
        $rolesJs = [];
        foreach ($roles as $r) {
            $rolesJs[(int)$r['Id_Rol']] = htmlspecialchars((string)($r['Descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
        }
        echo json_encode($rolesJs);
    ?>;

    /* ── Tabla de usuarios (datos ya en el HTML, solo inicializar DataTables) ── */
    if (document.getElementById('tabla-usuarios-roles')) {
        $('#tabla-usuarios-roles').DataTable({
            language: {
                search:      'Buscar:',
                lengthMenu:  'Mostrar _MENU_ registros',
                info:        'Mostrando _START_ a _END_ de _TOTAL_ usuarios',
                infoEmpty:   'Sin resultados',
                zeroRecords: 'No se encontraron usuarios',
                paginate: { first: 'Primera', last: 'Última', next: '›', previous: '‹' },
            },
            order: [[0, 'asc']],
            pageLength: 15,
            columnDefs: [{ orderable: false, targets: 4 }],
        });
    }

    /* ── Modal: asignar rol ─────────────────────────────────────────────── */
    const modalEl    = document.getElementById('modal-asignar-rol');
    const bsModal    = new bootstrap.Modal(modalEl);
    const selRol     = document.getElementById('modal-select-rol');
    const descDiv    = document.getElementById('modal-rol-descripcion');

    $(document).on('click', '.btn-asignar-rol', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre');
        const rolAct = parseInt($(this).data('rol')) || 0;

        document.getElementById('modal-id-usuario').value = id;
        document.getElementById('modal-nombre-usuario').textContent = nombre;
        selRol.value = rolAct;
        actualizarDescripcionRol();

        document.getElementById('modal-asignar-titulo').textContent =
            rolAct ? 'Cambiar Rol' : 'Asignar Rol';
        bsModal.show();
    });

    selRol.addEventListener('change', actualizarDescripcionRol);

    function actualizarDescripcionRol() {
        const id   = parseInt(selRol.value) || 0;
        const desc = rolesData[id] || '';
        if (id && desc) {
            descDiv.textContent = desc;
            descDiv.classList.remove('d-none');
        } else {
            descDiv.classList.add('d-none');
        }
    }

    document.getElementById('btn-confirmar-asignar-rol').addEventListener('click', function () {
        const id_usuario = document.getElementById('modal-id-usuario').value;
        const id_rol     = parseInt(selRol.value) || 0;

        const btnConfirm = this;
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

        $.ajax({
            url: API + '?op=asignar_rol',
            method: 'POST',
            data: { id_usuario: id_usuario, id_rol: id_rol },
        }).done(function (res) {
            if (res.success) {
                bsModal.hide();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                    .then(function () { window.location.reload(); });
            } else {
                Swal.fire('Error', res.message || 'Error al asignar rol.', 'error');
            }
        }).fail(function () {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
        }).always(function () {
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = '<i class="ti ti-check me-1"></i> Confirmar';
        });
    });

    /* ── Tab 2: Roles y Permisos ────────────────────────────────────────── */
    const submodulos = <?php
        $subArr = [];
        foreach ($submodulos as $s) {
            $subArr[] = [
                'id'     => (int)$s['Id_Submodulo'],
                'nombre' => htmlspecialchars($s['Nombre'], ENT_QUOTES, 'UTF-8'),
            ];
        }
        echo json_encode($subArr);
    ?>;

    let rolEditandoId = null;
    const panelPermisos   = document.getElementById('panel-editar-permisos');
    const labelRolEditar  = document.getElementById('label-rol-editando');
    const tbodyPermisos   = document.getElementById('tbody-permisos');

    // Abrir panel de edición de permisos
    $(document).on('click', '.btn-editar-permisos', function () {
        rolEditandoId = parseInt($(this).data('id'));
        const nombre  = $(this).data('nombre');
        labelRolEditar.textContent = nombre;
        panelPermisos.classList.remove('d-none');
        panelPermisos.scrollIntoView({ behavior: 'smooth', block: 'start' });
        cargarPermisosRol(rolEditandoId);
    });

    document.getElementById('btn-cerrar-permisos').addEventListener('click', function () {
        panelPermisos.classList.add('d-none');
        rolEditandoId = null;
    });

    function cargarPermisosRol(id_rol) {
        tbodyPermisos.innerHTML = '<tr><td colspan="7" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>';
        $.getJSON(API + '?op=permisos_rol&id_rol=' + id_rol, function (res) {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo cargar.', 'error');
                return;
            }
            const permMap = {};
            (res.permisos || []).forEach(function (p) { permMap[p.Id_Submodulo] = p; });

            tbodyPermisos.innerHTML = '';
            submodulos.forEach(function (s) {
                const p   = permMap[s.id] || {};
                const row = document.createElement('tr');
                row.innerHTML = '<td class="text-start fw-semibold ps-3">' + s.nombre + '</td>'
                    + chkCell('ver',      s.id, p.Pueden_Ver)
                    + chkCell('crear',    s.id, p.Pueden_Crear)
                    + chkCell('editar',   s.id, p.Pueden_Editar)
                    + chkCell('eliminar', s.id, p.Pueden_Eliminar)
                    + chkCell('exportar', s.id, p.Pueden_Exportar)
                    + chkCell('firmar',   s.id, p.Pueden_Firmar);
                tbodyPermisos.appendChild(row);
            });
        });
    }

    function chkCell(perm, idSub, valor) {
        const checked = parseInt(valor) ? 'checked' : '';
        return '<td><input type="checkbox" class="chk-permiso form-check-input" '
             + 'data-perm="' + perm + '" data-sub="' + idSub + '" '
             + checked + '></td>';
    }

    // Marcar / desmarcar todo
    document.getElementById('btn-toggle-all').addEventListener('click', function () {
        const checkboxes = document.querySelectorAll('#tbody-permisos .chk-permiso');
        const hayDesmarcado = Array.from(checkboxes).some(c => !c.checked);
        checkboxes.forEach(c => { c.checked = hayDesmarcado; });
    });

    // Guardar permisos
    document.getElementById('btn-guardar-permisos').addEventListener('click', function () {
        if (!rolEditandoId) return;
        const filasSub = {};
        document.querySelectorAll('#tbody-permisos .chk-permiso').forEach(function (chk) {
            const sub  = chk.dataset.sub;
            const perm = chk.dataset.perm;
            if (!filasSub[sub]) filasSub[sub] = { id_submodulo: sub };
            filasSub[sub][perm] = chk.checked ? 1 : 0;
        });
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        $.ajax({
            url: API + '?op=guardar_permisos',
            method: 'POST',
            data: { id_rol: rolEditandoId, permisos: JSON.stringify(Object.values(filasSub)) },
        }).done(function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false });
            } else {
                Swal.fire('Error', res.message || 'Error al guardar.', 'error');
            }
        }).fail(function () {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
        }).always(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Guardar Permisos';
        });
    });

    /* ── Modal: Crear nuevo rol ─────────────────────────────────────────── */
    document.getElementById('btn-confirmar-crear-rol').addEventListener('click', function () {
        const nombre      = document.getElementById('input-rol-nombre').value.trim();
        const descripcion = document.getElementById('input-rol-descripcion').value.trim();
        if (!nombre) {
            document.getElementById('input-rol-nombre').classList.add('is-invalid');
            return;
        }
        document.getElementById('input-rol-nombre').classList.remove('is-invalid');
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creando...';
        $.ajax({
            url: API + '?op=crear_rol',
            method: 'POST',
            data: { nombre: nombre, descripcion: descripcion },
        }).done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modal-crear-rol')).hide();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false })
                    .then(function () { window.location.reload(); });
            } else {
                Swal.fire('Error', res.message || 'Error al crear rol.', 'error');
            }
        }).fail(function () {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
        }).always(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i> Crear Rol';
        });
    });

    // Limpiar modal al cerrar
    document.getElementById('modal-crear-rol').addEventListener('hidden.bs.modal', function () {
        document.getElementById('input-rol-nombre').value = '';
        document.getElementById('input-rol-descripcion').value = '';
        document.getElementById('input-rol-nombre').classList.remove('is-invalid');
    });

});
</script>
