<?php



?>

<style>
    .chip-retorno {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }

    /* SIN RETORNO (rosa pálido) */
    .chip-rosado {
        background-color: #9e093bff;
    }

    /* CON RETORNO (celeste pálido) */
    .chip-celeste {
        background-color: #48aad8ff;
    }
</style>
<style>
    /* === Select2 con Bootstrap 5 — Altura y alineación perfectas === */

    /* Asegura que el contenedor ocupe todo el ancho */
    .select2-container {
        width: 100% !important;
    }

    /* Ajusta la altura del select como un form-select de Bootstrap 5 */
    .select2-container--bootstrap-5 .select2-selection {
        height: calc(2.5rem + 2px) !important;
        padding: 0 0.75rem !important;
        font-size: 1rem !important;
        display: flex !important;
        align-items: center !important;
        /* Centrado vertical */
        border-radius: 0.375rem !important;
    }

    /* Corrige el alto y alineación del texto seleccionado */
    .select2-container--bootstrap-5 .select2-selection__rendered {
        margin: 0 !important;
        padding: 0 !important;
        line-height: normal !important;
        display: flex !important;
        align-items: center !important;
        white-space: nowrap;
    }

    /* Flecha del dropdown bien alineada */
    .select2-container--bootstrap-5 .select2-selection__arrow {
        height: 100% !important;
        top: 0 !important;
        right: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
    }

    /* Popup de resultados más limpio */
    .select2-container--bootstrap-5 .select2-results__option {
        padding: 6px 10px !important;
        font-size: 0.95rem !important;
    }

    /* Oculta la flecha (dropdown arrow) */
    .select2-container--bootstrap-5 .select2-selection__arrow {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        height: 0 !important;
        pointer-events: none !important;
    }

    /* Acomoda el texto para que no quede espacio vacío */
    .select2-container--bootstrap-5 .select2-selection__rendered {
        padding-right: 0 !important;
        /* elimina el espacio reservado para la flecha */
    }

    /* ==== ARREGLA QUE EL BOTÓN "X" NO SE SALGA ==== */

    /* Mueve la X dentro del select */
    .select2-container--bootstrap-5 .select2-selection__clear {
        position: absolute !important;
        right: 0.75rem !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin: 0 !important;
        font-size: 1.2rem !important;
        z-index: 10 !important;
    }

    /* Evita que Select2 reserve espacio excesivo */
    .select2-container--bootstrap-5 .select2-selection__rendered {
        padding-right: 1.8rem !important;
        /* Espacio exacto para la X */
    }

    /* Por si la flecha aparece, ocultarla siempre */
    .select2-container--bootstrap-5 .select2-selection__arrow {
        display: none !important;
    }
</style>
<div class="modal modal-blur fade" id="modal-report" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Papeleta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="registroPapeletaForm" role="form" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Concepto <font color="red">(*)</font></label>
                                <input name="Id_Trabajador" id="Id_Trabajador"
                                    value="<?php echo $_SESSION["id_Trabajador"]; ?>" hidden>
                                <input name="EsConductor" id="EsConductor"
                                    value="<?php echo $_SESSION["EsConductor"]; ?>" hidden>

                                <input name="trabajador" id="trabajador"
                                    value="<?php echo utf8_encode(utf8_decode($_SESSION["Trab_Paterno"])) . " " . utf8_encode(utf8_decode($_SESSION["Trab_Materno"])) . " " . utf8_encode(utf8_decode($_SESSION["Trab_Nombres"])); ?>"
                                    hidden>
                                <input name="nuevoOfi" id="nuevoOfi"
                                    value="<?php echo utf8_encode(utf8_decode($_SESSION["Oficina"])); ?>" hidden>
                                <input name="nuevoGerencia" id="nuevoGerencia"
                                    value="<?php echo utf8_encode($_SESSION["Gerencia"]); ?>"
                                    hidden>
                                <input name="Jefe" id="Jefe" value="<?php echo $_SESSION["cod_jefe"]; ?>" hidden>
                                <input name="FirmaPersonal" id="FirmaPersonal"
                                    value="<?php echo $_SESSION["FirmaPersonal"]; ?>" hidden>
                                <input name="FirmaJefe" id="FirmaJefe" value="<?php echo $_SESSION["FirmaJefe"]; ?>"
                                    hidden>
                                <input name="FirmaJefeSede" id="FirmaJefeSede"
                                    value="<?php echo $_SESSION["FirmaJefeSede"]; ?>" hidden>
                                <input name="Id_Establecimiento" id="Id_Establecimiento"
                                    value="<?php echo $_SESSION["Id_Establecimiento"]; ?>" hidden>
                                <input name="JefeInmediato" id="JefeInmediato"
                                    value="<?php echo $_SESSION["JefeInmediato"]; ?>" hidden>
                                <input name="Cerrar" id="Cerrar" value="0" hidden>
                                <select class="form-select" id="concepto" name="concepto">
                                    <option value="">Seleccionar Concepto</option>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $concepto = ControladorPapeleta::ctrMostrarConceptos($item, $valor);

                                    foreach ($concepto as $conceptos) {
                                        echo '<option value="' . $conceptos['Concepto'] . '" 
                                         data-cod="' . $conceptos['Cod_Concepto'] . '">'
                                            . utf8_encode($conceptos['Concepto']) .
                                            '</option>';
                                    }
                                    ?>
                                </select>
                                <label id="labelAtencionMedica" style="display: none;  padding-left: 20px;">
                                    <font color="red"><br>*Recuerda, los descansos medicos <br>se realizarán por SGD.</font>
                                </label>
                                <script>
                                    $(document).ready(function() {
                                        $('#concepto').on('change', function() {
                                            if ($(this).val() === 'ATENCION MEDICA (ADJUNTAR CONSTANCIA)') {
                                                $('#labelAtencionMedica').show();
                                            } else {
                                                $('#labelAtencionMedica').hide();
                                            }
                                        });
                                    });
                                </script>
                                <script>
                                    $(document).ready(function() {
                                        $('#modal-report').on('shown.bs.modal', function() {
                                            $('#concepto').select2({
                                                theme: 'bootstrap-5',
                                                dropdownParent: $('#modal-report .modal-body'),
                                                placeholder: 'Seleccionar Concepto...'
                                            });

                                        });
                                    });
                                </script>

                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label mb-0">Lugar</label>
                                    <label class="form-check form-switch mb-0">
                                        <input type="hidden" name="conRetornoCheckbox" value="0">

                                        <input class="form-check-input" type="checkbox" checked id="conRetornoCheckbox" value="1">


                                        <span class="form-check-label chip-retorno" id="labelConRetorno">Con Retorno</span>

                                    </label>
                                </div>
                                <input type="text" class="form-control mt-2" id="lugar" name="lugar">
                            </div>

                            <script>
                                // Obtener el checkbox y el label
                                const checkbox = document.getElementById('conRetornoCheckbox');
                                const retornoLabel = document.getElementById('retornoLabel');

                                // Función para cambiar el texto del label según el estado del checkbox
                                checkbox.addEventListener('change', function() {
                                    if (checkbox.checked) {
                                        retornoLabel.textContent = 'Con Retorno';
                                    } else {
                                        retornoLabel.textContent = 'Sin Retorno';
                                    }
                                });
                            </script>
                        </div>
                        <div class="col-lg-12">
                            <div>
                                <label class="form-label">Motivo</label>
                                <textarea class="form-control" rows="3" id="motivo" name="motivo"></textarea><br>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha Inicio <font color="red">(*)</font></label>
                                <input type="text" class="form-control" id="fechaini" name="fechaini"
                                    placeholder="dd/mm/yyyy">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha Fin <font color="red">(*)</font></label>
                                <input type="text" class="form-control" id="fechafin" name="fechafin"
                                    placeholder="dd/mm/yyyy">
                            </div>
                        </div>


                        <script>
                            // Guardamos referencias a los Flatpickr
                            var fpInicio = flatpickr("#fechaini", {
                                dateFormat: "d/m/Y",
                                locale: "es",
                                allowInput: true
                            });

                            var fpFin = flatpickr("#fechafin", {
                                dateFormat: "d/m/Y",
                                locale: "es",
                                allowInput: true
                            });
                        </script>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre Funcionario Destino:</label>
                                <input type="text" class="form-control" id="funcionario" name="funcionario">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Observaciones:</label>
                                <input type="text" class="form-control" id="observaciones" name="observaciones">
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION["EsConductor"] == 1): ?>
                        <div>
                            <label class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="salidaConVehiculoCheckbox" value="1"
                                    name="salidaConVehiculoCheckbox">
                                <span class="form-check-label" id="retornoLabel">Salida con Vehiculo</span>
                            </label><br>
                        </div>
                    <?php endif; ?>

                    <!-- Este bloque de campos está inicialmente oculto -->
                    <div id="vehiculoDetails" class="row" style="display: none;">
                        <!-- Columna de Placa y Kilometraje Inicial en la misma fila -->
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Placa</label>
                                <input type="text" class="form-control" id="placa" name="placa" readonly>
                            </div>
                        </div>

                        <script>
                            $("#salidaConVehiculoCheckbox").on("change", function() {
                                if ($(this).is(":checked")) {


                                    $.post("modules/transportes/ajax/vehiculo.ajax.php", {
                                        accion: "getPlaca"
                                    }, function(respuesta) {
                                        $("#placa").val(respuesta); // Muestra la placa en el input
                                    });

                                } else {

                                    $("#placa").val(""); // limpia si desmarcan
                                    $("#kminicial").val("");
                                }
                            });
                        </script>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Placa Seleccionada</label>

                                <select class="form-select" id="placaseleccionada" name="placaseleccionada">
                                    <option value="">Seleccionar Placa</option>
                                    <?php
                                    $placaseleccionada = ControladorPapeletaVehicular::ctrMostrarPlacaVehicular();

                                    if (!empty($placaseleccionada)) {
                                        foreach ($placaseleccionada as $placas) {
                                            echo '<option value="' . $placas['id'] . '">'
                                                . utf8_encode($placas['placaseleccionada']) .
                                                '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <script>
                                    $(document).ready(function() {
                                        $('#modal-report').on('shown.bs.modal', function() {
                                            $('#placaseleccionada').select2({
                                                theme: 'bootstrap-5',
                                                dropdownParent: $('#modal-report .modal-body'),
                                                placeholder: 'Seleccionar Placa de Vehículo...'
                                            });

                                        });
                                    });
                                </script>
                            </div>

                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Kilometraje Inicial</label>
                                <input type="text" class="form-control" id="kminicial" name="kminicial">
                            </div>

                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">

                                <label for="sede" class="form-label">Seleccionar Sede</label>

                                <select class="form-select" id="sede" name="sede">
                                    <option value="">Seleccionar Sede</option>
                                    <?php
                                    $sede = ControladorPapeletaVehicular::ctrMostrarSedeSalidaVehicular();

                                    if (!empty($sede)) {
                                        foreach ($sede as $sedes) {
                                            echo '<option value="' . $sedes['id'] . '">'
                                                . utf8_encode($sedes['sede']) .
                                                '</option>';
                                        }
                                    }
                                    ?>
                                </select>


                            </div>

                            <!-- Columna de Salida desde Sede Trujillo en la misma fila -->
                            <div class="col-lg-6" hidden>
                                <div class="mb-3">
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="salidaSedeTrujilloCheckbox"
                                            name="salidaSedeTrujilloCheckbox" value="1">
                                        <span class="form-check-label" id="retornoLabel">¿Salida desde Sede Trujillo?</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Columna del botón para Solicitar Combustible -->
                            <div hidden>
                                <label class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="solicitarCombustibleCheckBox"
                                        name="solicitarCombustibleCheckBox">
                                    <span class="form-check-label" id="retornoLabel">¿Solicitar Combustible?</span>

                                </label><br>
                            </div>

                        </div>
                    </div>


                    <script>
                        $(document).ready(function() {
                            $('#sede').select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Seleccionar Sede...',
                                allowClear: true,
                                width: '100%' // <--- clave para que ocupe todo el ancho del contenedor

                            });
                        });
                    </script>
                    <script>
                        // Obtener el checkbox y el div que contiene los detalles del vehículo
                        const salidaConVehiculoCheckbox = document.getElementById('salidaConVehiculoCheckbox');
                        const vehiculoDetails = document.getElementById('vehiculoDetails');

                        // Función para mostrar u ocultar el bloque según el estado del checkbox
                        salidaConVehiculoCheckbox.addEventListener('change', function() {
                            if (salidaConVehiculoCheckbox.checked) {
                                vehiculoDetails.style.display = 'flex'; // Mostrar el bloque y mantenerlo en una fila
                            } else {
                                vehiculoDetails.style.display = 'none'; // Ocultar el bloque
                            }
                        });
                    </script>


                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-link link-danger btn-3" data-bs-dismiss="modal"> Cancelar </a>
                    <button type="submit" class="btn btn-success btn-5 ms-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-2">
                            <path d="M12 5l0 14"></path>
                            <path d="M5 12l14 0"></path>
                        </svg>
                        Registrar Papeleta
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {

        /* =========================================================
           FUNCIÓN: LIMPIAR BLOQUE VEHICULAR COMPLETO
        ========================================================= */
        function limpiarVehiculo() {

            $('#salidaConVehiculoCheckbox').prop('checked', false);
            $('#vehiculoDetails').hide();

            $('#placa').val('');
            $('#kminicial').val('');

            $('#placaseleccionada').val(null).trigger('change');
            $('#sede').val(null).trigger('change');

            $('#salidaSedeTrujilloCheckbox').prop('checked', false);
            $('#solicitarCombustibleCheckBox').prop('checked', false);
        }


        /* =========================================================
           TOGGLE: SALIDA CON VEHÍCULO
        ========================================================= */
        $('#salidaConVehiculoCheckbox').on('change', function() {

            if ($(this).is(':checked')) {

                $('#vehiculoDetails').css('display', 'flex');

                $.post("modules/papeletas/ajax/papeleta.ajax.php", {
                    accion: "getPlaca"
                }, function(respuesta) {
                    $('#placa').val(respuesta);
                });

            } else {
                limpiarVehiculo();
            }
        });


        /* =========================================================
           SELECT2 DENTRO DEL MODAL
        ========================================================= */
        $('#modal-report').on('shown.bs.modal', function() {

            $('#concepto').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modal-report .modal-body'),
                placeholder: 'Seleccionar Concepto...'
            });

            $('#placaseleccionada').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modal-report .modal-body'),
                placeholder: 'Seleccionar Placa de Vehículo...'
            });

            $('#sede').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modal-report .modal-body'),
                placeholder: 'Seleccionar Sede...',
                allowClear: true
            });

            // Estado inicial retorno
            $('#conRetornoCheckbox').prop('checked', false);
            $('#labelConRetorno')
                .text('Sin Retorno')
                .removeClass('chip-celeste')
                .addClass('chip-rosado');
        });


        /* =========================================================
           LIMPIAR TODO AL CERRAR MODAL
        ========================================================= */
        $('#modal-report').on('hidden.bs.modal', function() {

            // Reset HTML
            this.querySelector('form').reset();

            // Limpieza JS
            limpiarVehiculo();

            $('#concepto').val(null).trigger('change');
            $('#placaseleccionada').val(null).trigger('change');
            $('#sede').val(null).trigger('change');
        });


        /* =========================================================
           CHIP CON RETORNO / SIN RETORNO
        ========================================================= */
        $('#conRetornoCheckbox').on('change', function() {

            if ($(this).is(':checked')) {
                $('#labelConRetorno')
                    .text('Con Retorno')
                    .removeClass('chip-rosado')
                    .addClass('chip-celeste');
            } else {
                $('#labelConRetorno')
                    .text('Sin Retorno')
                    .removeClass('chip-celeste')
                    .addClass('chip-rosado');
            }
        });


        /* =========================================================
           ENVÍO FORMULARIO
        ========================================================= */
        $('#registroPapeletaForm').on('submit', function(e) {
            e.preventDefault();

            let concepto = $('#concepto').val();
            let lugar = $('#lugar').val().trim();
            let motivo = $('#motivo').val().trim();
            let fechaini = $('#fechaini').val();
            let fechafin = $('#fechafin').val();

            if (!concepto || !lugar || !motivo || !fechaini || !fechafin) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos obligatorios incompletos',
                    text: 'Debe completar Concepto, Lugar, Motivo, Fecha Inicio y Fecha Fin.'
                });
                return;
            }

            $('input[name="conRetornoCheckbox"]').val(
                $('#conRetornoCheckbox').is(':checked') ? 'SI' : 'NO'
            );

            $.ajax({
                type: 'POST',
                url: 'modules/papeletas/ajax/papeleta.ajax.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {

                    if (response.status === "success") {

                        $('#modal-report').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.message
                        }).then(() => {
                            tabla.ajax.reload(null, false);
                            tablaAdmin.ajax.reload(null, false);

                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '¡Error!',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error!',
                        text: 'Hubo un error al guardar la papeleta.'
                    });
                }
            });
        });

    });



    $(document).ready(function() {

        /* -------------------------------------------------------------
           ACTIVAR SELECT2 EN EL MODAL
        ------------------------------------------------------------- */
        $('#modal-report').on('shown.bs.modal', function() {
            $('#concepto').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modal-report .modal-body'),
                placeholder: 'Seleccionar Concepto...'
            });
        });

        /* -------------------------------------------------------------
           FUNCION PARA OBTENER SIGUIENTE DÍA LABORABLE
        ------------------------------------------------------------- */
        // Convierte dd/mm/yyyy → objeto Date
        function toDate(str) {
            if (!str) return null;
            let p = str.split('/');
            return new Date(p[2], p[1] - 1, p[0]);
        }

        // Devuelve siguiente día laborable
        function siguienteDiaLaborable(date) {
            let d = new Date(date);
            d.setDate(d.getDate() + 1);
            if (d.getDay() === 6) d.setDate(d.getDate() + 2); // sábado
            if (d.getDay() === 0) d.setDate(d.getDate() + 1); // domingo
            return d;
        }

        // Hoy y hace 15 días
        const hoy = new Date();
        const hace15 = new Date();
        hace15.setDate(hoy.getDate() - 15);


        /* -------------------------------------------------------------
           CAMBIO DE CONCEPTO
        ------------------------------------------------------------- */
        $("#concepto").on("change", function() {

            let cod = $("#concepto option:selected").data("cod");

            // Reiniciar ambos datepicker
            fpInicio.clear();
            fpFin.clear();

            // Siempre aplicar límite inferior general de 15 días hacia atrás
            fpInicio.set("minDate", hace15);
            fpFin.set("minDate", hace15);

            // Caso especial: COD = 02 → Fecha inicio = siguiente día laborable
            if (cod == "02") {

                let sigLab = siguienteDiaLaborable(hoy);

                fpInicio.set("minDate", sigLab);
                fpInicio.setDate(sigLab); // Lo deja fijo pero editable

                // Fin mínimo a partir de esa fecha
                fpFin.set("minDate", sigLab);
            }
        });


        /* -------------------------------------------------------------
           CONTROL: FECHA FIN ≥ FECHA INICIO
           Y BLOQUEO DE FECHAS MENORES
        ------------------------------------------------------------- */
        $("#fechaini").on("change", function() {

            let ini = toDate($("#fechaini").val());
            if (!ini) return;

            // Fecha fin no puede ser menor a fecha inicio
            fpFin.set("minDate", ini);

            // Si la fecha fin ya es inválida, limpiar
            let fin = toDate($("#fechafin").val());
            if (fin && fin < ini) {
                fpFin.clear();
            }
        });


        $("#fechafin").on("change", function() {

            let ini = toDate($("#fechaini").val());
            let fin = toDate($("#fechafin").val());

            if (ini && fin && fin < ini) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha incorrecta',
                    text: 'La fecha fin no puede ser menor que la fecha inicio.'
                });
                fpFin.clear();
            }
        });


    });
</script>