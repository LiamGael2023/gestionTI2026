<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Configuración Core</div>
                <h2 class="page-title">Gestión de Sistemas y Módulos</h2>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" onclick="nuevoSistema()">
                    <i class="ti ti-plus me-2"></i> Nuevo Sistema
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-check icon alert-icon me-2"></i></div>
                        <div>Sistema creado y carpetas generadas con éxito.</div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert"></a>
                </div>
            <?php elseif($_GET['msg'] == 'updated'): ?>
                <div class="alert alert-info alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-info-circle icon alert-icon me-2"></i></div>
                        <div>Configuración del sistema actualizada correctamente.</div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert"></a>
                </div>
            <?php elseif($_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-trash icon alert-icon me-2"></i></div>
                        <div>Sistema eliminado de la base de datos.</div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th class="w-1">Icono</th>
                            <th>Etiqueta del Menú</th>
                            <th>Carpeta / Ruta Física</th>
                            <th>Orden</th>
                            <th class="w-1">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conn = Conexion::conectar();
                        $sql = "SELECT * FROM comun.Modulos ORDER BY orden ASC";
                        $res = sqlsrv_query($conn, $sql);
                        
                        while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)): 
                        ?>
                        <tr>
                            <td>
                                <span class="avatar avatar-sm bg-blue-lt">
                                    <i class="ti ti-<?= $row['icono'] ?> fs-2"></i>
                                </span>
                            </td>
                            <td class="font-weight-medium"><?= htmlspecialchars($row['etiqueta']) ?></td>
                            <td class="text-muted small">modules/<?= htmlspecialchars($row['nombre']) ?>/</td>
                            <td><?= $row['orden'] ?></td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <button class="btn btn-icon btn-white" onclick='editarSistema(<?= json_encode($row) ?>)' title="Editar">
                                        <i class="ti ti-pencil text-primary"></i>
                                    </button>
                                    <button class="btn btn-icon btn-white" onclick="eliminarSistema(<?= $row['id_modulo'] ?>)" title="Eliminar">
                                        <i class="ti ti-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-sistema" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Configurar Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-sistema" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_modulo" id="id_modulo">
                    
                    <div class="mb-3" id="div-nombre-carpeta">
                        <label class="form-label required">Nombre de la carpeta (Slug)</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" placeholder="ej: inventario_ti">
                        <small class="form-hint">Este nombre define la ruta física. Solo letras minúsculas y guiones bajos.</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label class="form-label required">Etiqueta del Menú</label>
                                <input type="text" class="form-control" name="etiqueta" id="etiqueta" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label required">Orden</label>
                                <input type="number" class="form-control" name="orden" id="orden" value="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Icono (Nombre de Tabler Icons)</label>
                        <div class="input-group">
                            <span class="input-group-text">ti ti-</span>
                            <input type="text" class="form-control" name="icono" id="icono" placeholder="box" required>
                        </div>
                        <small class="form-hint">Busque el nombre en <a href="https://tabler-icons.io/" target="_blank">tabler-icons.io</a></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="ti ti-device-floppy me-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/**
 * Prepara el modal para crear un nuevo sistema
 */
function nuevoSistema() {
    $('#modal-title').text('Crear Nuevo Sistema');
    $('#form-sistema').attr('action', 'index.php?module=sistemas&action=guardar');
    $('#form-sistema')[0].reset();
    
    $('#div-nombre-carpeta').show(); // Mostrar campo de carpeta
    $('#nombre').attr('required', true);
    $('#id_modulo').val('');
    
    var myModal = new bootstrap.Modal(document.getElementById('modal-sistema'));
    myModal.show();
}

/**
 * Prepara el modal para editar un sistema existente
 */
function editarSistema(data) {
    $('#modal-title').text('Editar Sistema: ' + data.etiqueta);
    $('#form-sistema').attr('action', 'index.php?module=sistemas&action=actualizar');
    
    $('#id_modulo').val(data.id_modulo);
    $('#etiqueta').val(data.etiqueta);
    $('#icono').val(data.icono);
    $('#orden').val(data.orden);
    
    // Ocultamos el nombre de carpeta porque cambiarlo físicamente requiere otros procesos
    $('#div-nombre-carpeta').hide(); 
    $('#nombre').attr('required', false);
    
    var myModal = new bootstrap.Modal(document.getElementById('modal-sistema'));
    myModal.show();
}

/**
 * Confirmación antes de eliminar
 */
function eliminarSistema(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se eliminará el acceso de la base de datos y los permisos de los usuarios. Las carpetas físicas permanecerán en el servidor.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d63939',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'index.php?module=sistemas&action=eliminar&id=' + id;
        }
    });
}
</script>