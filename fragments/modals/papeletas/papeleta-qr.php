<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ... resto de tu código
?>

<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

<style>
    /* FOTO móvil full width */
    @media (max-width: 576px) {
        #papeletaAvatar {
            width: 100% !important;
            height: auto !important;
            padding-top: 100%;
            /* mantiene cuadrado */
            background-size: cover !important;
            background-position: center !important;
        }

        /* QR full width
        #qrCanvas {
            width: 100% !important;
            height: auto !important;
            max-width: none !important;
        } */

        /* Contenedor del QR full width */
        .qr-container {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="modal fade" id="modalQR" tabindex="-1" aria-labelledby="modalQRLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalQRLabel">Detalle de Papeleta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body bg-light">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">

                        <!-- Fila principal: foto, datos, QR -->
                        <div class="row g-4 align-items-center">

                            <!-- Foto (SIN ICONO) -->
                            <div class="col-12 col-sm-3 text-center">
                                <div id="papeletaAvatar" class="avatar rounded border"
                                    style="width:150px; height:150px; background-size:cover; background-position:center; margin:auto;">
                                </div>
                            </div>

                            <!-- Datos -->
                            <div class="col-12 col-sm-6 text-center text-sm-start">
                                <h4 class="fw-bold" id="papeletaNombres"></h4>

                                <div class="row g-2 mt-2 small text-muted">

                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start gap-2">
                                            <img src="vistas/img/iconos/papeleta/gerencia.svg" width="18" alt="">
                                            <div>
                                                <strong>Gerencia:</strong><br>
                                                <span id="papeletaSubgerencia"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start gap-2">
                                            <img src="vistas/img/iconos/papeleta/oficina.svg" width="18" alt="">
                                            <div>
                                                <strong>Oficina:</strong><br>
                                                <span id="papeletaOficina"></span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- QR (SIN ICONO) -->
                            <div class="col-12 col-sm-3 text-center">
                                <div class="border rounded p-2 bg-white shadow-sm d-inline-block qr-container">
                                    <canvas id="qrCanvas" width="200" height="200"></canvas>

                                    <div class="mt-2">
                                        <span id="papeletaID"
                                            class="px-3 py-1 rounded"
                                            style="background-color:white; color:black; font-weight:bold; display:inline-block;">
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /row -->

                        <!-- Info adicional -->
                        <hr class="my-4">
                        <div class="small">

                            <!-- Concepto / Motivo -->
                            <div class="mb-3 d-flex align-items-start gap-2">
                                <img src="vistas/img/iconos/papeleta/concepto.svg" width="18" alt="">
                                <div>
                                    <strong>Concepto/Motivo:</strong>
                                    <p class="mb-0" id="papeletaConceptoMotivo"></p>
                                </div>
                            </div>

                            <!-- Lugar -->
                            <div class="mb-3 d-flex align-items-start gap-2">
                                <img src="vistas/img/iconos/papeleta/lugar.svg" width="18" alt="">
                                <div>
                                    <strong>Lugar:</strong>
                                    <p class="mb-0" id="papeletaLugar"></p>
                                </div>
                            </div>

                            <!-- Fechas -->
                            <div class="row g-4">

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-start gap-2">
                                        <img src="vistas/img/iconos/papeleta/fecha.svg" width="18" alt="">
                                        <div>
                                            <strong>Fechas</strong>

                                            <div class="d-flex flex-column flex-md-row gap-3 mt-1">

                                                <div>
                                                    <small class="text-muted">Inicio</small>
                                                    <p class="mb-0" id="papeletaFechaInicio"></p>
                                                </div>

                                                <div>
                                                    <small class="text-muted">Fin</small>
                                                    <p class="mb-0" id="papeletaFechaFin"></p>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Horas -->
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-start gap-2">
                                        <img src="vistas/img/iconos/papeleta/hora.svg" width="18" alt="">
                                        <div>
                                            <strong>Horas</strong>
                                            <div class="d-flex flex-column flex-md-row gap-3 mt-1">
                                                <div>
                                                    <small class="text-muted">Inicio</small>
                                                    <p class="mb-0" id="papeletaHoraInicio"></p>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Fin</small>
                                                    <p class="mb-0" id="papeletaHoraFin"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /row -->
                        </div>

                        <!-- Sección vehículo -->
                        <hr id="rowVehiculo" class="my-4 d-none">

                        <div class="row g-4 small d-none" id="vehiculoInfo">

                            <div class="col-sm-4">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/papeleta/placa.svg" width="18" alt="">
                                    <div>
                                        <strong>Placa:</strong>
                                        <p class="mb-0" id="papeletaPlaca"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/papeleta/km-inicial.svg" width="18" alt="">
                                    <div>
                                        <strong>Kilom. Inicial:</strong>
                                        <p class="mb-0" id="papeletaKMInicial"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/papeleta/km-final.svg" width="18" alt="">
                                    <div>
                                        <strong>Kilom. Final:</strong>
                                        <p class="mb-0" id="papeletaKMFinal"></p>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        var modal = $("#modalQR");

        /* ==========================
           INICIALIZAR QR
        ========================== */

        var qr = new QRious({
            element: document.getElementById("qrCanvas"),
            size: 110,
            value: ""
        });

        /* ==========================
           CUANDO SE ABRE EL MODAL
        ========================== */

        modal.on("show.bs.modal", function(event) {

            var button = $(event.relatedTarget);
            var id = button.data("id");

            /* ==========================
               ACTUALIZAR QR
            ========================== */

            qr.value = "";
            qr.value = id;

            $("#papeletaID").text(id);

            /* ==========================
               AJAX DETALLE PAPELETA
            ========================== */

            var formData = new FormData();
            formData.append("accion", "mostrar_detalle");
            formData.append("id_papeleta", id);

            $.ajax({
                url: "modules/papeletas/ajax/papeleta.ajax.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",

                success: function(resp) {

                    console.log("Respuesta del servidor:", resp);

                    if (resp.status === "success") {

                        var data = resp.data;

                        /* ==========================
                           FORMATEO FECHAS
                        ========================== */

                        let fechaInicio = data.fecha_inicio;
                        if (fechaInicio && typeof fechaInicio === "object" && fechaInicio.date) {
                            fechaInicio = new Date(fechaInicio.date).toLocaleDateString("es-ES");
                        }

                        let fechaFin = data.fecha_fin;
                        if (fechaFin && typeof fechaFin === "object" && fechaFin.date) {
                            fechaFin = new Date(fechaFin.date).toLocaleDateString("es-ES");
                        }

                        /* ==========================
                           FORMATEO HORAS
                        ========================== */

                        let horaSalida = data.hora_salida;

                        if (horaSalida && typeof horaSalida === "object" && horaSalida.date) {
                            horaSalida = new Date(horaSalida.date).toLocaleTimeString("es-ES", {
                                hour: "2-digit",
                                minute: "2-digit"
                            });
                        } else if (typeof horaSalida === "string") {
                            horaSalida = new Date(horaSalida).toLocaleTimeString("es-ES", {
                                hour: "2-digit",
                                minute: "2-digit"
                            });
                        }

                        let horaLlegada = data.hora_llegada;

                        if (horaLlegada && typeof horaLlegada === "object" && horaLlegada.date) {
                            horaLlegada = new Date(horaLlegada.date).toLocaleTimeString("es-ES", {
                                hour: "2-digit",
                                minute: "2-digit"
                            });
                        } else if (typeof horaLlegada === "string") {
                            horaLlegada = new Date(horaLlegada).toLocaleTimeString("es-ES", {
                                hour: "2-digit",
                                minute: "2-digit"
                            });
                        }

                        /* ==========================
                           CARGAR DATOS
                        ========================== */

                        $("#papeletaNombres").text(data.nombres);
                        $("#papeletaSubgerencia").text(data.gerencia);
                        $("#papeletaOficina").text(data.oficina);

                        $("#papeletaFechaInicio").text(fechaInicio || "");
                        $("#papeletaFechaFin").text(fechaFin || "");

                        $("#papeletaHoraInicio").text(horaSalida || "");
                        $("#papeletaHoraFin").text(horaLlegada || "");

                        $("#papeletaConceptoMotivo").html(
                            data.Id_Trabajador_Concepto_APP + "<br>" +
                            data.Id_Trabajador_Motivo_APP
                        );

                        $("#papeletaLugar").text(data.Id_Trabajador_Lugar_APP);

                        /* ==========================
                           FOTO TRABAJADOR
                        ========================== */

                        $("#papeletaAvatar").css(
                            "background-image",
                            "url('/gestionti/public/fotos-trabajador/" + data.Trab_Fotocheck + ".jpg')"
                        );

                        /* ==========================
                           DATOS VEHICULO
                        ========================== */

                        if (parseInt(data.es_salida_vehicular) === 1) {

                            $("#rowVehiculo").removeClass("d-none");
                            $("#vehiculoInfo").removeClass("d-none");

                            $("#papeletaPlaca").text(data.placa || "No disponible");
                            $("#papeletaKMInicial").text(data.kilometraje_inicial || "No disponible");
                            $("#papeletaKMFinal").text(data.kilometraje_final || "No disponible");

                        } else {

                            $("#rowVehiculo").addClass("d-none");
                            $("#vehiculoInfo").addClass("d-none");

                            $("#papeletaPlaca").text("");
                            $("#papeletaKMInicial").text("");
                            $("#papeletaKMFinal").text("");

                        }

                    } else if (resp.status === "empty") {

                        console.warn("No se encontraron datos de la papeleta");

                    } else {

                        console.error("Error del servidor:", resp.message);

                    }

                },

                error: function(xhr, status, error) {

                    console.error("Error AJAX:", error);

                }

            });

        });

    });
</script>