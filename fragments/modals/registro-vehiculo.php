<!-- registro vehiculo -->
<div class="modal modal-blur fade" id="modal-report" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="registroVehiculoForm" role="form" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo Vehículo <font color="red">(*)</font></label>
                                <input name="Id_Trabajador" id="Id_Trabajador" value="<?php echo $_SESSION["id_Trabajador"]; ?>" hidden>
                                <input name="trabajador" id="trabajador"
                                    value="<?php echo utf8_encode(utf8_decode($_SESSION["Trab_Paterno"])) . " " . utf8_encode(utf8_decode($_SESSION["Trab_Materno"])) . " " . utf8_encode(utf8_decode($_SESSION["Trab_Nombres"])); ?>"
                                    hidden>
                                <input name="nuevoOfi" id="nuevoOfi"
                                    value="<?php echo utf8_encode(utf8_decode($_SESSION["Oficina"])); ?>" hidden>
                                <input name="nuevoGerencia" id="nuevoGerencia" value="<?php echo utf8_encode($_SESSION["Gerencia"]); ?>"
                                    hidden>
                                <input name="Jefe" id="Jefe" value="<?php echo $_SESSION["cod_jefe"]; ?>" hidden>
                                <input name="JefeInmediato" id="JefeInmediato" value="<?php echo $_SESSION["JefeInmediato"]; ?>" hidden>
                                <input name="Cerrar" id="Cerrar" value="0" hidden>
                                <select class="form-select" id="id_tipo_vehiculo" name="id_tipo_vehiculo">
                                    <option value="">Seleccionar Tipo Veh.</option>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $tipoVehiculos = ControladorVehiculo::ctrMostrarTipoVehiculo($item, $valor);
                                    foreach ($tipoVehiculos as $tipo) {
                                        echo '<option  value="' . $tipo['id_tipo_vehiculo'] . '">' . utf8_encode($tipo['nombre_tipo']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <script>
                                    $(document).ready(function() {
                                        $('#modal-report').on('shown.bs.modal', function() {
                                            $('#id_tipo_vehiculo').select2({
                                                theme: 'bootstrap-5',
                                                dropdownParent: $('#modal-report .modal-body'),
                                                placeholder: 'Seleccionar Tipo Vehículo...'
                                            });

                                        });
                                    });
                                </script>

                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label mb-0">Cod. Patrimonial<font color="red"> (*)</font></label>
                                </div>
                                <input type="text" class="form-control mt-2" id="codigo_patrimonial" name="codigo_patrimonial">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-0">Placa<font color="red"> (*)</font></label>
                                <input type="text" class="form-control" id="placa" name="placa">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-0">Estado<font color="red"> (*)</font></label>
                                <select class="form-select" id="id_estado_vehiculo" name="id_estado_vehiculo">
                                    <option value="">Seleccionar Estado Veh.</option>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $estadoVehiculos = ControladorVehiculo::ctrMostrarEstadoVehiculo($item, $valor);
                                    foreach ($estadoVehiculos as $estado) {
                                        echo '<option  value="' . $estado['id_estado_vehiculo'] . '">' . utf8_encode($estado['nombre_estado']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <script>
                                    $(document).ready(function() {
                                        $('#modal-report').on('shown.bs.modal', function() {
                                            $('#id_estado_vehiculo').select2({
                                                theme: 'bootstrap-5',
                                                dropdownParent: $('#modal-report .modal-body'),
                                                placeholder: 'Seleccionar Estado de Vehículo...'
                                            });

                                        });
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-0">Num. Chasis<font color="red"> (*)</font></label>
                                <input type="text" class="form-control" id="numero_chasis" name="numero_chasis">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-0">Marca<font color="red"> (*)</font></label>
                                <select class="form-select" id="marca" name="marca">
                                    <option value="">Seleccionar Marca Veh.</option>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $marcaVehiculos = ControladorVehiculo::ctrMostrarMarcaVehiculo($item, $valor);
                                    foreach ($marcaVehiculos as $marca) {
                                        echo '<option  value="' . $marca['marca'] . '">' . utf8_encode($marca['nombre_marca']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <script>
                                    $(document).ready(function() {
                                        $('#modal-report').on('shown.bs.modal', function() {
                                            $('#marca').select2({
                                                theme: 'bootstrap-5',
                                                dropdownParent: $('#modal-report .modal-body'),
                                                placeholder: 'Seleccionar Marca de Vehículo...'
                                            });

                                        });
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label mb-0">Modelo<font color="red"> (*)</font></label>
                                <input type="text" class="form-control" id="modelo" name="modelo">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="anioFabricacion" class="form-label">
                                    Año Fabricación <font color="red">(*)</font>
                                </label>
                                <div class="input-group" style="max-width: 200px;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="cambiarAnio(-1)">−</button>
                                    <input type="number" class="form-control text-center" id="anioFabricacion" name="anioFabricacion"
                                        value="2000" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="cambiarAnio(1)">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3" style="overflow: visible;">
                                <label for="color" class="form-label mb-0">Color <font color="red">(*)</font></label>
                                <select class="form-select" id="color" name="color" required>
                                    <option value="" selected disabled>Seleccione un color</option>
                                    <?php
                                    $colores = ControladorVehiculo::ctrMostrarColorVehiculo();
                                    if (!empty($colores)) {
                                        foreach ($colores as $color) {
                                            echo '<option value="' . htmlspecialchars($color["color"]) . '">' .
                                                ucfirst(utf8_encode($color["color"])) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>

                                <script>
                                    $(document).ready(function() {

                                        // Función para inicializar el Select2
                                        function inicializarSelectColor() {
                                            const $colorSelect = $('#color');

                                            // Destruye instancia previa si existe
                                            if ($colorSelect.data('select2')) {
                                                $colorSelect.select2('destroy');
                                            }

                                            $colorSelect.select2({
                                                theme: 'bootstrap-5',
                                                dropdownParent: $('#modal-report .modal-body'),
                                                placeholder: 'Seleccione o escriba un color...',
                                                tags: true,
                                                allowClear: true
                                            });
                                        }

                                        // Inicializa al abrir el modal
                                        $('#modal-report').on('shown.bs.modal', function() {
                                            inicializarSelectColor();
                                        });

                                        // 🔁 Recarga los colores al cerrar el modal
                                        $('#modal-report').on('hidden.bs.modal', function() {
                                            $.ajax({
                                                url: 'ajax/ajax/vehiculo.ajax.php', // ruta al archivo PHP que llama al controlador
                                                method: 'POST',
                                                data: {
                                                    accion: 'obtenerColores' // parámetro para distinguir la acción
                                                },
                                                dataType: 'json',
                                                success: function(respuesta) {
                                                    const $colorSelect = $('#color');
                                                    $colorSelect.empty(); // limpia opciones
                                                    $colorSelect.append('<option value="" disabled selected>Seleccione un color</option>');

                                                    // Rellena con los colores del controlador
                                                    respuesta.forEach(function(color) {
                                                        $colorSelect.append(
                                                            $('<option>', {
                                                                value: color.color,
                                                                text: color.color.charAt(0).toUpperCase() + color.color.slice(1)
                                                            })
                                                        );
                                                    });

                                                    // Re-inicializa Select2
                                                    inicializarSelectColor();
                                                },
                                                error: function() {
                                                    console.error('Error al recargar los colores');
                                                }
                                            });
                                        });
                                    });
                                </script>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-link link-danger btn-3" data-bs-dismiss="modal"> Cancelar </a>
                    <button type="submit" class="btn btn-success btn-5 ms-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                            <path d="M12 5l0 14"></path>
                            <path d="M5 12l14 0"></path>
                        </svg>
                        Registrar Vehículo
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
<script>
    const inputAnio = document.getElementById('anioFabricacion');

    function cambiarAnio(cambio) {
        let anio = parseInt(inputAnio.value) || 2000;
        anio += cambio;
        inputAnio.value = anio;
    }
    if (!inputAnio.value) inputAnio.value = 2000;
    $(document).ready(function() {

        $('#modal-report').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
        });

        $("#registroVehiculoForm").on("submit", function(e) {
            e.preventDefault();
            $.ajax({
                url: "ajax/ajax/vehiculo.ajax.php",
                type: "POST",
                data: $(this).serialize() + "&accion=crearVehiculo",
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        $("#modal-report").modal("hide");
                        Swal.fire({
                                icon: "success",
                                title: "Vehículo registrado",
                                text: response.message
                            })
                            .then(() => $(".tablaRegistroVehiculo").DataTable().ajax.reload(null, false));
                        $("#registroVehiculoForm")[0].reset();
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error de servidor",
                        text: error
                    });
                }
            });
        });
    });
</script>