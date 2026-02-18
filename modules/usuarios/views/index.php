<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    .select2-container { z-index: 9999; }
    .dataTables_wrapper .pagination .page-link { color: #1d273b; }
    .dataTables_wrapper .pagination .page-item.active .page-link { 
        background-color: #004d99; border-color: #004d99; color: white; 
    }
</style>

<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">Gestión de Usuarios</h2>
      </div>
      <div class="col-auto ms-auto">
        <button class="btn btn-primary" onclick="nuevoUsuario()">
          <i class="ti ti-plus me-2"></i> Nuevo Usuario
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="card">
      <div class="card-body"> 
        <div class="table-responsive">
            <table id="tabla-usuarios" class="table table-vcenter card-table table-striped" style="width:100%">
              <thead>
                <tr>
                  <th>Nombre</th><th>Usuario/Correo</th><th>Rol</th><th>Documento</th><th>Estado</th><th>Acciones</th> 
                </tr>
              </thead>
              <tbody></tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="modal-usuario" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Gestión de Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="form-usuario" autocomplete="off">
        <div class="modal-body">
            <input type="hidden" id="id_usuario" name="id_usuario">

            <div class="mb-3">
                <label class="form-label required">Buscar Personal (API)</label>
                <select id="buscar_personal" class="form-select" style="width: 100%;"></select>
            </div>
            
            <div class="row">
                <div class="col-lg-4"><div class="mb-3"><label class="form-label">DNI</label><input type="text" id="documento" name="documento" class="form-control" readonly></div></div>
                <div class="col-lg-8"><div class="mb-3"><label class="form-label">Nombres</label><div class="row g-2"><div class="col-6"><input type="text" id="nombres" name="nombres" class="form-control" readonly></div><div class="col-6"><input type="text" id="apellidos" name="apellidos" class="form-control" readonly></div></div></div></div>
            </div>
            <div class="row">
                <div class="col-lg-6"><div class="mb-3"><label class="form-label required">Usuario</label><input type="text" id="usuario" name="usuario" class="form-control" required></div></div>
                <div class="col-lg-6"><div class="mb-3"><label class="form-label required">Correo</label><input type="email" id="email" name="correo" class="form-control" required></div></div>
            </div>
            <div class="row mb-3">
                <div class="col-lg-6"><div class="mb-3"><label class="form-label">Contraseña</label><input type="password" id="contrasenia" name="contrasenia" class="form-control" placeholder="Contraseña"></div></div>
                <div class="col-lg-3"><div class="mb-3"><label class="form-label">Rol</label><select name="rol" id="rol" class="form-select"><option value="USUARIO">Usuario</option><option value="IT">Soporte TI</option><option value="ADMIN">Administrador</option></select></div></div>
                <div class="col-lg-3"><div class="mb-3"><label class="form-label">Sede</label><select name="sede_id" id="sede_id" class="form-select"><option value="1">Sede Central</option><option value="2">Campamento San José</option><option value="3">Bocatoma</option><option value="4">Planta Tratamiento</option></select></div></div>
            </div>

            <hr class="my-3">
            <label class="form-label mb-3">Permisos de Acceso a Sistemas (3 por fila)</label>
            <div class="row g-2">
                <?php
                $conn = Conexion::conectar();
                $res_mod = sqlsrv_query($conn, "SELECT * FROM comun.Modulos ORDER BY orden ASC");
                while($m = sqlsrv_fetch_array($res_mod, SQLSRV_FETCH_ASSOC)): ?>
                    <div class="col-4">
                        <label class="form-selectgroup-item w-100">
                            <input type="checkbox" name="permisos[]" value="<?php echo $m['id_modulo']; ?>" class="form-selectgroup-input permiso-check">
                            <div class="form-selectgroup-label d-flex align-items-center p-2" style="height: 60px;">
                                <div class="me-2"><span class="form-selectgroup-check"></span></div>
                                <div class="avatar avatar-xs me-2 bg-blue-lt"><i class="ti ti-<?php echo $m['icono']; ?> fs-3"></i></div>
                                <div class="font-weight-medium small text-truncate"><?php echo $m['etiqueta']; ?></div>
                            </div>
                        </label>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary ms-auto">Guardar Usuario</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<script>
var tabla;

$(document).ready(function() {
    tabla = $('#tabla-usuarios').DataTable({
        "processing": true, "serverSide": true,
        "ajax": { "url": "modules/usuarios/controllers/data_listado.php", "type": "POST" },
        "columns": [ { "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 }, { "data": 5, "orderable": false } ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" },
        "order": [[ 0, "desc" ]]
    });

    $('#form-usuario').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'modules/usuarios/controllers/ajax_handler.php?action=guardar_proceso',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#modal-usuario').modal('hide');
                    Swal.fire({
                        title: '¡Guardado!',
                        text: 'Actualizando sistemas...',
                        icon: 'success',
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        // RECARGAMOS LA PÁGINA PARA QUE EL MENÚ SE ACTUALICE DINÁMICAMENTE
                        location.reload(); 
                    });
                } else {
                    Swal.fire('Error', 'No se pudo guardar', 'error');
                }
            }
        });
    });

    $('#buscar_personal').select2({
        theme: 'bootstrap-5', dropdownParent: $('#modal-usuario'),
        ajax: {
            url: 'modules/usuarios/controllers/api_proxy.php', dataType: 'json', delay: 250,
            processResults: function (data) {
                return { results: data.data.map(i => ({ id: i.Documento, text: i.Documento + ' - ' + i.Nombres, datos: i })) };
            }
        }
    }).on('select2:select', function (e) {
        var d = e.params.data.datos;
        $('#documento').val(d.Documento); $('#nombres').val(d.Nombres); $('#apellidos').val(d.Trab_Paterno + ' ' + d.Trab_Materno);
        $('#usuario').val(d.usuario); $('#email').val(d.email || d.Correo);
    });
});

function nuevoUsuario() {
    $('#form-usuario')[0].reset();
    $('#id_usuario').val('');
    $('.permiso-check').prop('checked', false);
    $('.modal-title').text('Nuevo Usuario');
    new bootstrap.Modal(document.getElementById('modal-usuario')).show();
}

function editarUsuario(id) {
    $('.permiso-check').prop('checked', false);
    $.get('modules/usuarios/controllers/ajax_handler.php?action=obtener_json&id=' + id, function(data) {
        if(data) {
            $('#id_usuario').val(data.id_usuario);
            $('#documento').val(data.documento); $('#nombres').val(data.nombres); $('#apellidos').val(data.apellidos);
            $('#usuario').val(data.usuario); $('#email').val(data.correo); $('#sede_id').val(data.sede_id);
            $('#rol').val(data.rol);
            $.get('modules/usuarios/controllers/ajax_handler.php?action=obtener_permisos&id=' + id, function(permisos) {
                permisos.forEach(p => { $(`.permiso-check[value="${p.id_modulo}"]`).prop('checked', true); });
                $('.modal-title').text('Editar Usuario');
                new bootstrap.Modal(document.getElementById('modal-usuario')).show();
            });
        }
    });
}

function eliminarUsuario(id) {
    Swal.fire({ title: '¿Confirmar?', icon: 'warning', showCancelButton: true }).then((result) => {
        if (result.isConfirmed) {
            fetch('modules/usuarios/controllers/ajax_handler.php?action=eliminar', { 
                method: 'POST', body: JSON.stringify({ id: id }), headers: { 'Content-Type': 'application/json' } 
            }).then(() => { tabla.ajax.reload(); Swal.fire('Hecho', '', 'success'); });
        }
    });
}
</script>