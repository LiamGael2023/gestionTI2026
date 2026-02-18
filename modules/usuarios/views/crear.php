<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="page-header">
    <div class="container-xl">
        <h2 class="page-title">Sincronizar / Crear Usuario</h2>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <form action="index.php?module=usuarios&action=guardar" method="POST">
                    
                    <div class="mb-4">
                        <label class="form-label required">Buscar Personal (API Chavimochic)</label>
                        <select id="buscar_personal" class="form-select" style="width: 100%;">
                            </select>
                        <small class="form-hint">Busque por Nombre, Apellidos o DNI.</small>
                    </div>

                    <div class="hr-text">Datos del Usuario</div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">DNI / Documento</label>
                            <input type="text" id="documento" name="documento" class="form-control" readonly required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" id="nombres" name="nombres" class="form-control" readonly required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" id="apellidos" name="apellidos" class="form-control" readonly required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Usuario (Login)</label>
                            <input type="text" id="usuario" name="usuario" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Correo Institucional</label>
                            <input type="email" id="email" name="correo" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Contraseña</label>
                            <input type="password" name="contrasenia" class="form-control" placeholder="Ingrese contraseña nueva" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="rol" class="form-select">
                                <option value="USUARIO">Usuario Normal</option>
                                <option value="IT">Soporte TI</option>
                                <option value="ADMIN">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sede</label>
                            <select name="sede_id" id="sede_id" class="form-select">
                                <option value="1">Sede Central (1)</option>
                                <option value="2">Campamento San José (2)</option>
                                <option value="3">Bocatoma (3)</option>
                                <option value="4">Planta de Tratamiento (4)</option>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" id="hash_original" name="hash_original">

                    <div class="form-footer text-end">
                        <a href="usuarios" class="btn btn-link link-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    $('#buscar_personal').select2({
        theme: 'bootstrap-5',
        placeholder: 'Escribe para buscar...',
        allowClear: true,
        ajax: {
            url: 'modules/usuarios/controllers/api_proxy.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // Aunque la API devuelve todo, Select2 necesita enviar algo
                };
            },
            processResults: function (respuestaApi) {
                // La API devuelve: { success: true, data: [ ... ] }
                
                var listaFormateada = [];
                
                if (respuestaApi.success && respuestaApi.data) {
                    // Recorremos el array "data"
                    listaFormateada = respuestaApi.data.map(function(item) {
                        return {
                            id: item.Documento, // Usamos el DNI como ID único
                            text: item.Documento + ' - ' + item.Nombres + ' ' + item.Trab_Paterno + ' ' + item.Trab_Materno,
                            
                            // GUARDAMOS TODO EL OBJETO PARA USARLO LUEGO
                            datos_completos: item 
                        };
                    });
                }
                
                // Filtro local (opcional si la API devuelve todo de golpe)
                // Select2 tiene su propio buscador interno si cargamos todos los datos,
                // pero como usamos AJAX, a veces es mejor filtrar aquí si la API no filtra.
                // En este caso, asumimos que Select2 muestra lo que recibe.
                
                return {
                    results: listaFormateada
                };
            },
            cache: true
        }
    });

    // CUANDO SELECCIONAS A ALGUIEN
    $('#buscar_personal').on('select2:select', function (e) {
        var data = e.params.data.datos_completos;
        
        console.log("Datos recibidos:", data); // Para depurar en consola

        // 1. Llenar Nombres y Apellidos
        $('#documento').val(data.Documento);
        $('#nombres').val(data.Nombres);
        $('#apellidos').val(data.Trab_Paterno + ' ' + data.Trab_Materno);

        // 2. Llenar Usuario y Correo (Tal cual vienen de la API)
        $('#usuario').val(data.usuario);
        $('#email').val(data.email || data.Correo); // La API tiene ambos campos a veces

        // 3. Seleccionar la Sede automáticamente
        // La API devuelve "Id_Establecimiento": 1 o 2.
        if(data.Id_Establecimiento) {
            $('#sede_id').val(data.Id_Establecimiento).change();
        }

        // 4. Hash de contraseña (Opcional)
        // La API devuelve: "$2a$07$asxx..."
        // No podemos poner esto en el campo visible "password", pero lo guardamos en oculto
        $('#hash_original').val(data.contrasenia);
    });
});
</script>