<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

<style>
    .qr-container {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        padding: 8px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,.12);
        max-width: 100%;
    }

    .qr-box {
        width: 150px;
        height: 150px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* CLAVE: el canvas nunca supera su contenedor */
    .qr-box canvas {
        max-width: 100% !important;
        max-height: 100% !important;
        display: block;
    }

    @media (max-width: 576px) {
        .qr-container { width: 100%; }
        .qr-box { width: 200px; height: 200px; }

        #papeletaAvatar {
            width: 100% !important;
            height: auto !important;
            padding-top: 100%;
            background-size: cover !important;
            background-position: center !important;
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

                        
                        <div class="row g-4 align-items-center">

                            <!-- Foto -->
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
                                            <div><strong>Gerencia:</strong><br><span id="papeletaSubgerencia"></span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start gap-2">
                                            <img src="vistas/img/iconos/papeleta/oficina.svg" width="18" alt="">
                                            <div><strong>Oficina:</strong><br><span id="papeletaOficina"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QR -->
                            <div class="col-12 col-sm-3 text-center">
                                <div class="qr-container">
                                    <div class="qr-box" id="qrBox">
                                        <canvas id="qrCanvas"></canvas>
                                    </div>
                                    <div class="mt-2">
                                        <span id="papeletaID" style="color:black; font-weight:bold; display:inline-block;"></span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <hr class="my-4">
                        <div class="small">

                            <div class="mb-3 d-flex align-items-start gap-2">
                                <img src="vistas/img/iconos/papeleta/concepto.svg" width="18" alt="">
                                <div><strong>Concepto/Motivo:</strong><p class="mb-0" id="papeletaConceptoMotivo"></p></div>
                            </div>

                            <div class="mb-3 d-flex align-items-start gap-2">
                                <img src="vistas/img/iconos/papeleta/lugar.svg" width="18" alt="">
                                <div><strong>Lugar:</strong><p class="mb-0" id="papeletaLugar"></p></div>
                            </div>

                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-start gap-2">
                                        <img src="vistas/img/iconos/papeleta/fecha.svg" width="18" alt="">
                                        <div>
                                            <strong>Fechas</strong>
                                            <div class="d-flex flex-column flex-md-row gap-3 mt-1">
                                                <div><small class="text-muted">Inicio</small><p class="mb-0" id="papeletaFechaInicio"></p></div>
                                                <div><small class="text-muted">Fin</small><p class="mb-0" id="papeletaFechaFin"></p></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-start gap-2">
                                        <img src="vistas/img/iconos/papeleta/hora.svg" width="18" alt="">
                                        <div>
                                            <strong>Horas</strong>
                                            <div class="d-flex flex-column flex-md-row gap-3 mt-1">
                                                <div><small class="text-muted">Inicio</small><p class="mb-0" id="papeletaHoraInicio"></p></div>
                                                <div><small class="text-muted">Fin</small><p class="mb-0" id="papeletaHoraFin"></p></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr id="rowVehiculo" class="my-4 d-none">
                        <div class="row g-4 small d-none" id="vehiculoInfo">
                            <div class="col-sm-4">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/papeleta/placa.svg" width="18" alt="">
                                    <div><strong>Placa:</strong><p class="mb-0" id="papeletaPlaca"></p></div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/papeleta/km-inicial.svg" width="18" alt="">
                                    <div><strong>Kilom. Inicial:</strong><p class="mb-0" id="papeletaKMInicial"></p></div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/papeleta/km-final.svg" width="18" alt="">
                                    <div><strong>Kilom. Final:</strong><p class="mb-0" id="papeletaKMFinal"></p></div>
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
$(document).ready(function () {

    var canvasEl = document.getElementById("qrCanvas");
    var qrBox    = document.getElementById("qrBox");

    function getQrSize() {
        var boxW = qrBox ? qrBox.offsetWidth : 0;
        if (boxW > 20) return boxW;
        return window.innerWidth <= 576 ? 200 : 150;
    }

    var qr = new QRious({
        element : canvasEl,
        size    : getQrSize(),
        value   : "INIT"
    });

    console.log("QRious init size:", qr.size);
    $("#debugQR").text("QR init size=" + qr.size + " canvas=" + canvasEl.width + "x" + canvasEl.height);

    var modal = $("#modalQR");

    /* Redimensionar QR cuando el modal ya es visible (shown con 'n') */
    modal.on("shown.bs.modal", function () {
        var s = getQrSize();
        if (qr.size !== s) {
            qr.size = s;
            console.log("QR redimensionado a:", s);
        }
    });

    modal.on("show.bs.modal", function (event) {

        var id = $(event.relatedTarget).data("id");
        console.log("data-id:", id, typeof id);
        $("#debugID").text("ID: " + (id !== undefined ? id : "UNDEFINED — revisa data-id en el boton"));

        if (!id) {
            $("#debugAjax").text("AJAX: no ejecutado — ID vacio");
            return;
        }

        var idStr = String(id);
        qr.value  = idStr;
        $("#papeletaID").text(idStr);
        $("#debugQR").text("QR value=" + idStr + " | canvas " + canvasEl.width + "x" + canvasEl.height);
        $("#debugAjax").text("AJAX: enviando...");

        $.ajax({
            url      : "modules/papeletas/ajax/papeleta.ajax.php",
            type     : "POST",
            data     : { accion: "mostrar_detalle", id_papeleta: id },
            dataType : "json",

            success: function (resp) {
                console.log("AJAX resp:", resp);
                $("#debugAjax").text("AJAX: status=" + resp.status);

                if (resp.status !== "success") {
                    console.warn("status:", resp.status, resp.message || "");
                    return;
                }

                var d = resp.data;

                function fmtFecha(f) {
                    if (!f) return "";
                    var src = (typeof f === "object" && f.date) ? f.date : f;
                    var dt  = new Date(src);
                    return isNaN(dt) ? src : dt.toLocaleDateString("es-ES");
                }

                function fmtHora(h) {
                    if (!h) return "";
                    var src = (typeof h === "object" && h.date) ? h.date : h;
                    var dt  = new Date(src);
                    if (isNaN(dt)) return String(h);
                    return dt.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });
                }

                $("#papeletaNombres").text(d.nombres);
                $("#papeletaSubgerencia").text(d.gerencia);
                $("#papeletaOficina").text(d.oficina);
                $("#papeletaFechaInicio").text(fmtFecha(d.fecha_inicio));
                $("#papeletaFechaFin").text(fmtFecha(d.fecha_fin));
                $("#papeletaHoraInicio").text(fmtHora(d.hora_salida));
                $("#papeletaHoraFin").text(fmtHora(d.hora_llegada));
                $("#papeletaConceptoMotivo").html((d.Id_Trabajador_Concepto_APP||"") + "<br>" + (d.Id_Trabajador_Motivo_APP||""));
                $("#papeletaLugar").text(d.Id_Trabajador_Lugar_APP || "");

                var urlFoto = "/gestionti/public/fotos-trabajador/" + d.Trab_Fotocheck + ".jpg";
                console.log("Foto:", urlFoto);
                $("#debugFoto").text("Foto: " + urlFoto);
                $("#papeletaAvatar").css("background-image", "url('" + urlFoto + "')");

                var esVeh = parseInt(d.es_salida_vehicular) === 1;
                $("#rowVehiculo").toggleClass("d-none", !esVeh);
                $("#vehiculoInfo").toggleClass("d-none", !esVeh);
                if (esVeh) {
                    $("#papeletaPlaca").text(d.placa || "No disponible");
                    $("#papeletaKMInicial").text(d.kilometraje_inicial || "No disponible");
                    $("#papeletaKMFinal").text(d.kilometraje_final || "No disponible");
                }
            },

            error: function (xhr, status, error) {
                console.error("AJAX error:", xhr.status, error, xhr.responseText);
                $("#debugAjax").text("AJAX: ERROR HTTP " + xhr.status + " — " + error);
            }
        });

    });

    modal.on("hidden.bs.modal", function () {
        qr.value = "";
        ["#papeletaNombres","#papeletaSubgerencia","#papeletaOficina",
         "#papeletaFechaInicio","#papeletaFechaFin","#papeletaHoraInicio",
         "#papeletaHoraFin","#papeletaLugar","#papeletaID",
         "#papeletaPlaca","#papeletaKMInicial","#papeletaKMFinal"
        ].forEach(function(s){ $(s).text(""); });
        $("#papeletaConceptoMotivo").html("");
        $("#papeletaAvatar").css("background-image","");
        $("#debugID,#debugQR,#debugAjax,#debugFoto").text("(esperando...)");
    });

});
</script>