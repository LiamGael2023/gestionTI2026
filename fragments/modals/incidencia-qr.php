<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<style>
    /* Contenedor del botón cerrar */
    .lb-closeContainer {
        position: fixed !important;
        top: 20px !important;
        right: 20px !important;
        z-index: 99999 !important;
    }

    /* Botón de cerrar */
    .lb-close {
        position: fixed !important;
        top: 20px !important;
        right: 20px !important;
        z-index: 99999 !important;
    }

    /* Para versiones que usan solo SVG dentro */
    .lb-data .lb-close,
    .lb-nav .lb-close {
        position: fixed !important;
        top: 20px !important;
        right: 20px !important;
    }


    #modalQR .row .col {
        min-width: 0;
    }

    #modalQR span,
    #modalQR p {
        word-wrap: break-word;
        white-space: normal;
    }
</style>
<!-- =================== MODAL DETALLE DE INCIDENCIA =================== -->
<div class="modal fade" id="modalQR" tabindex="-1" aria-labelledby="modalQRLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalQRLabel">Detalle de Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body bg-light">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">

                        <!-- Fila principal -->
                        <div class="row g-4 align-items-center mb-4">

                            <!-- QR -->
                            <div class="col-12 col-sm-3 text-center">
                                <canvas id="qrCanvas" width="150" height="150" style="width:150px; height:150px; margin:auto;"></canvas>
                            </div>

                            <!-- Datos principales -->
                            <div class="col-12 col-sm-9 text-center text-sm-start">
                                <h4 class="fw-bold" id="incidenciaNombre_Trabajador"></h4>

                                <div class="row g-2 mt-2 small text-muted">

                                    <!-- Título -->
                                    <div class="col-12 col-md-8">
                                        <div class="row g-2 align-items-start">
                                            <div class="col-auto">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                                    <path d="M9 7l1 0" />
                                                    <path d="M9 13l6 0" />
                                                    <path d="M13 17l2 0" />
                                                </svg>
                                            </div>
                                            <div class="col">
                                                <strong>Título:</strong><br>
                                                <span id="incidenciatitulo"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Riesgo -->
                                    <div class="col-12 col-md-4">
                                        <div class="row g-2 align-items-start">
                                            <div class="col-auto">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 9v4" />
                                                    <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                                    <path d="M12 16h.01" />
                                                </svg>
                                            </div>
                                            <div class="col">
                                                <strong>Nivel de Riesgo:</strong><br>
                                                <span id="incidenciariesgo"></span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Separador -->
                        <hr class="my-4">

                        <!-- Descripción -->
                        <div class="mb-4">
                            <div class="row g-2 align-items-start">
                                <div class="col-auto">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                        <path d="M9 17h6" />
                                        <path d="M9 13h6" />
                                    </svg>
                                </div>
                                <div class="col">
                                    <strong>Descripción:</strong>
                                    <p class="mb-0" id="incidenciadescripcion"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Causa / Acción Correctiva -->
                        <div class="row g-4 mb-4">

                            <div class="col-12 col-sm-6">
                                <div class="row g-2 align-items-start">
                                    <div class="col-auto">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 21a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3c.539 1.832 .627 3.747 .283 5.588" />
                                            <circle cx="18" cy="18" r="3" />
                                            <path d="M20.2 20.2l1.8 1.8" />
                                        </svg>
                                    </div>
                                    <div class="col">
                                        <strong>Causa:</strong>
                                        <p class="mb-0" id="incidenciacausa"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <div class="row g-2 align-items-start">
                                    <div class="col-auto">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11.512 17.023l-1.512 -3.023l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5l-4.45 12.324" />
                                            <path d="M15 19l2 2l4 -4" />
                                        </svg>
                                    </div>
                                    <div class="col">
                                        <strong>Acción Correctiva:</strong>
                                        <p class="mb-0" id="incidenciaaccion_correctiva"></p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Fecha - Estado - Plazo -->
                        <div class="row g-4">

                            <div class="col-12 col-sm-4">
                                <div class="row g-2 align-items-start">
                                    <div class="col-auto">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                            <path d="M16 3v4" />
                                            <path d="M8 3v4" />
                                            <path d="M4 11h16" />
                                        </svg>
                                    </div>
                                    <div class="col">
                                        <strong>Fecha Registro:</strong>
                                        <p class="mb-0" id="incidenciafecha_registro"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-4">
                                <div class="row g-2 align-items-start">
                                    <div class="col-auto">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
                                            <path d="M12 8v4" />
                                            <path d="M12 16h.01" />
                                        </svg>
                                    </div>
                                    <div class="col">
                                        <strong>Estado:</strong>
                                        <span id="incidenciaestado"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-4">
                                <div class="row g-2 align-items-start">
                                    <div class="col-auto">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </div>
                                    <div class="col">
                                        <strong>Plazo de Atención:</strong>
                                        <p class="mb-0" id="incidenciaplazo"></p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Evidencia -->
                        <div class="mt-3 text-center">
                            <a id="linkEvidencia" href="" data-lightbox="galeriaIncidencia">
                                <img id="incidenciaImagenEvidencia" src="" alt="Evidencia"
                                    style="max-width:120px; max-height:120px; border:1px solid #ccc; padding:3px; border-radius:6px; display:none; cursor:pointer;">
                            </a>
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
    document.addEventListener("DOMContentLoaded", function() {

        var modal = document.getElementById('modalQR');

        modal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var id_detalle_inci = button.getAttribute('data-id');
            console.log("🔍 DEBUG: Modal QR abierto, ID:", id_detalle_inci);



            var numero = button.getAttribute('data-numero') || '';

            // Texto del QR: prioriza el correlativo 'numero', si no, usa 'id'
            var etiquetaQR = numero ? numero : id_detalle_inci;

            // Inicializar/limpiar QR dentro del modal
            var qrCanvas = document.getElementById('qrCanvas');
            if (qrCanvas) {
                var ctx = qrCanvas.getContext('2d');
                if (ctx) ctx.clearRect(0, 0, qrCanvas.width, qrCanvas.height);

                new QRious({
                    element: qrCanvas,
                    size: 150,
                    value: etiquetaQR
                });
            }

            var formData = new FormData();
            formData.append("accion", "mostrar_detalle");
            formData.append("id_incidencia", id_detalle_inci);



            fetch("ajax/ajax/incidencia.ajax.php", {
                    method: "POST",
                    body: formData
                })

                .then(response => response.json())
                .then(resp => {
                    console.log("Respuesta del servidor:", resp);



                    if (resp.status === "success") {
                        console.log("✅ DEBUG: Datos recibidos exitosamente:", resp.data);
                        var data = resp.data;
                        let fecha_registro = data.fecha_registro;
                        if (fecha_registro && typeof fecha_registro === "object" && fecha_registro.date) {
                            fecha_registro = new Date(fecha_registro.date).toLocaleDateString("es-ES");
                        }

                        console.log("📝 DEBUG: Actualizando elementos del DOM...");
                        document.getElementById('incidenciaNombre_Trabajador').textContent = data.trabajador || '';
                        document.getElementById('incidenciatitulo').textContent = data.titulo || '';
                        document.getElementById("incidenciafecha_registro").textContent = fecha_registro || "";
                        document.getElementById('incidenciadescripcion').textContent = data.descripcion || '';
                        document.getElementById('incidenciacausa').textContent = data.causa_detectada || 'En proceso';
                        document.getElementById('incidenciaplazo').textContent = data.plazo_atencion || 'En proceso';
                        document.getElementById('incidenciariesgo').textContent = data.nombre_Riesgo || '';
                        document.getElementById('incidenciaaccion_correctiva').textContent = data.accion_correctiva || 'En proceso';
                        document.getElementById('incidenciaestado').textContent = data.estado || "";
                        // ===========================
                        // Mostrar evidencia en imagen
                        // ===========================
                        let imgTag = document.getElementById("incidenciaImagenEvidencia");
                        let linkTag = document.getElementById("linkEvidencia");

                        if (data.imagen_evidencia && data.tipo_imagen) {

                            let base64Image = `data:${data.tipo_imagen};base64,${data.imagen_evidencia}`;

                            imgTag.src = base64Image; // miniatura
                            linkTag.href = base64Image; // imagen grande para Lightbox

                            imgTag.style.display = "block";
                            linkTag.style.display = "inline-block";

                        } else {
                            imgTag.src = "";
                            linkTag.href = "";
                            imgTag.style.display = "none";
                            linkTag.style.display = "none";
                        }


                    } else if (resp.status === "empty") {
                        console.warn("No se encontraron datos de la incidencia");
                    } else {
                        console.error("Error del servidor:", resp.message);
                    }
                    a
                })
                .catch(error => console.error("Error AJAX:", error));
        });

    });
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove(); // elimina el modal HTML
    });
</script>