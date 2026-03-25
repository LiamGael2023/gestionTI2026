<style>
    .avatar {
        width: 40px;
        height: 40px;

        background-size: cover;
        background-position: center;
    }

    .dataTable thead .sorting:after,
    .dataTable thead .sorting:before,
    .dataTable thead .sorting_asc:after,
    .dataTable thead .sorting_asc:before,
    .dataTable thead .sorting_desc:after,
    .dataTable thead .sorting_desc:before {
        display: none !important;
    }



    /* Asegurarse de que el select tiene el borde correcto y la flecha se muestre */
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: .375rem;
        /* Bordes redondeados */
        border: 1px solid #ced4da;
        /* Borde gris de Bootstrap */
        padding: .375rem .75rem;
        /* Espaciado interno */
        height: calc(2.25rem + 2px);
        /* Ajuste de altura */
    }

    /* La flecha dentro del select */
    .select2-container--bootstrap-5 .select2-selection__arrow {
        top: 50%;
        /* Centrar la flecha verticalmente */
        right: 10px;
        /* Ajuste de espacio desde el borde derecho */
        transform: translateY(-50%);
        /* Centrar la flecha */
        width: 1.5em;
        /* Ajustar el tamaño de la flecha */
        height: 1.5em;
        /* Ajustar el tamaño de la flecha */
        border-left: 1px solid #ced4da;
        /* Borde de la flecha */
        background-color: #fff;
        /* Fondo blanco para la flecha */
    }

    @media (min-width: 768px) {

        .tablaAdminPapeleta {
            width: 100% !important;
            table-layout: fixed !important;
            /* obliga a respetar porcentajes */
        }

        /* Coinciden con tus targets en DataTables */
        #col-id {
            width: 3% !important;
        }

        #col-foto {
            width: 5% !important;
        }

        #col-qr {
            width: 3% !important;
        }

        #col-nombres {
            width: 15% !important;
        }

        #col-f1 {
            width: 3% !important;
        }

        #col-f2 {
            width: 3% !important;
        }

        #col-f3 {
            width: 3% !important;
        }

        #col-f4 {
            width: 3% !important;
        }

        #col-fechas {
            width: 6% !important;
        }

        #col-horas {
            width: 6% !important;
        }

        #col-retorno {
            width: 3% !important;
        }

        #col-concepto {
            width: 25% !important;
        }

        #col-acciones {
            width: 22% !important;
        }

        /* Garantiza que las imágenes, SVG o avatares no rompan el ancho */
        .tablaAdminPapeleta td img,
        .tablaAdminPapeleta td svg,
        .tablaAdminPapeleta td .avatar-lightbox {
            max-width: 100%;
            height: auto;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

    }

    td {
        max-width: 180px;
        /* Ajusta según tu diseño */
        white-space: nowrap;
        /* No permite salto de línea */
        overflow: hidden;
        /* Oculta lo que se desborda */
        text-overflow: ellipsis;
        /* Muestra los ... */
    }

    .tablaAdminPapeleta td:nth-child(3),
    .tablaAdminPapeleta th:nth-child(3) {
        text-align: center !important;
        vertical-align: middle !important;


    }



    .tablaAdminPapeleta td:nth-child(2),
    .tablaAdminPapeleta td:nth-child(3),
    .tablaAdminPapeleta td:nth-child(8),
    .tablaAdminPapeleta td:nth-child(10) {
        text-overflow: clip !important;
        /* No muestra los “…” */
    }

    .btn-tabler {
        font-size: 12px;
        /* Reduce el tamaño del texto si es necesario */
        padding: 5px 10px;
        /* Reduce el espaciado dentro del botón */
    }

    .btn-tabler svg {
        width: 14px;
        /* Ajusta el tamaño del icono */
        height: 14px;
        /* Ajusta el tamaño del icono */
    }

    .custom-tables-side-by-side {
        display: flex;
        gap: 20px;
        padding: 20px 15px 0 15px;
        align-items: flex-start;
    }

    /* Contenedor de filtros vertical */
    .filters-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
        width: 300px;
        /* ancho fijo para filtros */
        flex-shrink: 0;
    }

    /* Mantener estilos de las cajas */
    .card-box {
        background-color: #fff;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        text-align: center;
        width: 100%;
    }

    .card-box .btn {
        min-width: 80px;
    }

    /* Contenedor tabla que ocupa resto del espacio */
    .table-column {
        flex: 1 1 auto;
        overflow-x: auto;
    }

    .chip {
        display: inline-block;
        padding: 0px 6px;
        border-radius: 6px;
        font-size: inherit;
        /* mantiene h6 */
        line-height: 1.2;
        white-space: nowrap;
    }

    .chip-green {
        background-color: #28a745;
        color: #fff;
    }

    .chip-white {
        background-color: #ffffff;
        /* fondo blanco */
        color: #000;
        /* texto negro */
    }


    .disabled-group {
        pointer-events: none;
        opacity: 0.4;
    }
</style>
<style>
    .filtro-opciones {
        padding: 10px 0;
    }


    .collapse {
        transition: height 1.2s ease !important;
    }
</style>
<div class="page-wrapper">
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-sm-12 col-lg-2">
                    <div class="card">

                        <!-- =======================
                                    ESTILOS
                                =========================== -->
                        <style>
                            /* Normal (AZUL SOLIDO) */
                            .btn-filtro-fecha {
                                background: #0d6efd;
                                /* azul bootstrap */
                                color: white;
                                border: 1px solid #0d6efd;
                                padding: 6px 14px;
                                border-radius: 6px;
                                transition: s ease;
                                font-weight: 500;
                            }

                            /* Hover del normal */
                            .btn-filtro-fecha:hover {
                                background: #0b5ed7;
                                border-color: #0b5ed7;
                                color: white;
                            }

                            /* ACTIVO (OUTLINE AZUL) */
                            .btn-filtro-fecha.active {
                                background: white !important;
                                color: #0d6efd !important;
                                border: 2px solid #0d6efd !important;
                                font-weight: 600;
                            }
                        </style>


                        <style>
                            /* Normal: verde sólido */
                            .btn-filtro-firma {
                                background: #06c669;
                                /* Verde */
                                color: white;
                                border: 1px solid #06c669;
                                padding: 6px 14px;
                                border-radius: 6px;
                                transition: .25s ease;
                                font-weight: 500;
                            }

                            /* Hover → blanco + borde verde + texto verde */
                            .btn-filtro-firma:hover {
                                background: white !important;
                                color: #06c669 !important;
                                border: 2px solid #06c669 !important;
                            }

                            /* ACTIVO → igual que hover pero más marcado */
                            .btn-filtro-firma.active {
                                background: white !important;
                                color: #06c669 !important;
                                border: 2px solid #06c669 !important;
                                font-weight: 600;
                            }
                        </style>
                        <!-- =======================
                                ACORDEÓN DE FILTROS
                            =========================== -->
                        <div class="accordion" id="accordionFecha">
                            <div class="accordion-item">

                                <!-- ENCABEZADO -->
                                <h4 class="accordion-header" id="headingFecha">
                                    <button class="accordion-button collapsed"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapseFecha"
                                        aria-expanded="false"
                                        aria-controls="collapseFecha">
                                        Filtro por Fecha
                                    </button>
                                </h4>

                                <!-- CONTENIDO (colapsado por defecto) -->
                                <div id="collapseFecha"
                                    class="accordion-collapse collapse"
                                    aria-labelledby="headingFecha"
                                    data-bs-parent="#accordionFecha">

                                    <div class="accordion-body">

                                        <div class="row justify-content-center gx-2 gy-2">

                                            <div class="col-auto">
                                                <button class="btn-filtro-fecha" data-filtro="TODOS">Todos</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-fecha" data-filtro="HOY">Hoy</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-fecha" data-filtro="AYER">Ayer</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-fecha" data-filtro="MANANA">Mañana</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-fecha" data-filtro="ESTE MES">Este Mes</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-fecha" data-filtro="MES ANTERIOR">Mes Anterior</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-fecha" data-filtro="ESTE ANIO">Este Año</button>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>


                        <!-- =======================
                     SCRIPT
                       =========================== -->



                        <div class="accordion" id="accordionFirmas">
                            <div class="accordion-item">

                                <!-- ENCABEZADO -->
                                <h4 class="accordion-header" id="headingFirma">
                                    <button class="accordion-button collapsed"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapseFirma"
                                        aria-expanded="false"
                                        aria-controls="collapseFirma">
                                        Filtro por Firma
                                    </button>
                                </h4>

                                <!-- CONTENIDO (colapsado por defecto) -->
                                <div id="collapseFirma"
                                    class="accordion-collapse collapse"
                                    aria-labelledby="headingFirma"
                                    data-bs-parent="#accordionFirma">

                                    <div class="accordion-body">

                                        <div class="row justify-content-center gx-2 gy-2">

                                            <div class="col-auto">
                                                <button class="btn-filtro-firma" data-filtro=null>Todos</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-firma" data-filtro="estadoJI">J. Inmediato</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-firma" data-filtro="estadoJP">J. Personal</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-firma" data-filtro="estado_subgerencia">Subgerencia</button>
                                            </div>

                                            <div class="col-auto">
                                                <button class="btn-filtro-firma" data-filtro="estado_transportes">Transportes</button>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>


                        <!-- =======================
                                SCRIPT
                            =========================== -->
                        <div class="card-box">
                            <div class="container-fluid px-0">
                                <div class="row justify-content-center gx-2 gy-2">
                                    <button id="btn-restablecer-filtros2" class="btn btn-dark">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restablecer
                                    </button>

                                </div>
                            </div>
                        </div>
                        <div class="card-box">
                            <h6 class="text-center mb-3">Reportes</h6>
                            <div class="container-fluid px-0">
                                <div class="row justify-content-center gx-2 gy-2">
                                    <div class="col-auto">
                                        <button class="btn btn-red">PDF</button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-teal">Excel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-lg-10">
                    <div class="card">
                        <div class="card-table">
                            <div id="advanced-table">
                                <div class="custom-tables-side-by-side">
                                    <div class="table-column">
                                        <div class="table-responsive">
                                            <table id="new-cons"
                                                class="display table table-striped table-hover dt-responsive nowrap tablaAdminPapeleta"
                                                style="width: 100%">
                                                <thead>
                                                    <tr>
                                                        <th id="col-id">ID</th>
                                                        <th id="col-foto">Foto</th>
                                                        <th id="col-qr">QR</th>
                                                        <th id="col-nombres">Nombres</th>
                                                        <th id="col-f1">Jefe<br>Inme.</th>
                                                        <th id="col-f2">Jefe<br>Pers.</th>
                                                        <th id="col-f3">SubGer.</th>
                                                        <th id="col-f4">Jefe<br>Transp.</th>
                                                        <th id="col-fechas">Fechas</th>
                                                        <th id="col-horas">Horas</th>
                                                        <th id="col-retorno">Retorn.</th>
                                                        <th id="col-concepto">Concepto / Motivo</th>
                                                        <th id="col-acciones">Acciones</th>
                                                    </tr>
                                                </thead>

                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
    // Lista de acordeones que deben cerrarse al hacer clic fuera
    const acordeones = ["collapseFecha", "collapseFirma"];

    $(document).on("click", function(e) {

        acordeones.forEach(id => {
            const $contenido = $("#" + id);
            const selectorBoton = `[data-bs-target='#${id}']`;

            // Si el clic NO fue dentro del acordeón NI en su botón
            if (
                !$contenido.is(e.target) &&
                $contenido.has(e.target).length === 0 &&
                !$(e.target).closest(selectorBoton).length
            ) {
                $contenido.collapse("hide");
            }
        });

    });

    // Boton No Permitido
    $(document).on("click", ".btn-nopermitido", function() {
        const id = $(this).data("id");
        Swal.fire({
            title: '¿Estás seguro?',
            text: "La papeleta será marcada como NO PERMITIDA",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, marcar como NO PERMITIDA',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "modules/papeletas/ajax/papeleta.ajax.php",
                type: "POST",
                dataType: "json", // 👈 Espera JSON válido del servidor
                data: {
                    accion: "no_autorizado",
                    id_papeleta: id
                },
                success: function(response) {
                    console.log("✅ SUCCESS:", response);

                    if (response.status === "success") {

                        Swal.fire('Anulado', 'La papeleta se marcó como NO PERMITIDA.', 'success');

                        // 👉 Recargar DataTable desde servidor
                        $('.tablaAdminPapeleta').DataTable().ajax.reload(null, false);

                    } else {
                        Swal.fire('Error', 'No se pudo marcar como no permitida.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("❌ ERROR AJAX:", error);
                    console.log("Estado:", status);
                    console.log("Respuesta del servidor:", xhr.responseText);
                    Swal.fire("Error AJAX", "No se pudo ejecutar la solicitud.\n" + error, "error");
                },
                complete: function(xhr, status) {
                    console.log("ℹ️ COMPLETE:", status);
                    console.log("Respuesta completa del servidor:", xhr.responseText);
                }
            });
        });
    });
    // Cambiar Estado
    $(document).ready(function() {
        function generarBotonEstado(campo, valor) {
            const titulo = valor === 1 ? "Aprobado" : "Anulado";
            const color = valor === 1 ? "success" : "danger";
            const icono =
                valor === 1 ?
                '<i class="fas fa-check"></i>' :
                `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
          viewBox="0 0 24 24" fill="currentColor" class="estado-icon">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10 -10 10
            s-10 -4.477 -10 -10s4.477 -10 10 -10m3.6 5.2a1 1 0 0 0
            -1.4 .2l-2.2 2.933l-2.2 -2.933a1 1 0 1 0 -1.6 1.2l2.55 3.4
            l-2.55 3.4a1 1 0 1 0 1.6 1.2l2.2 -2.933l2.2 2.933a1 1 0
            0 0 1.6 -1.2l-2.55 -3.4l2.55 -3.4a1 1 0 0 0 -.2 -1.4"/>
        </svg>`;
            return `
    <div class="btn-group" role="group">
      <button class="btn btn-${color} btn-cambiar-estado btn-cuadrado"
              title="${titulo}"
              data-campo="${campo}"
              data-id="${idPapeleta}">
        ${icono}
      </button>
    </div>`;
        }

        $(document).on("click", ".btn-cambiar-estado", function() {

            console.log("🔹 [DEBUG] Click en botón de cambio de estado");

            const idPapeleta = $(this).data("id");
            const campo = $(this).data("campo");
            const idJefe = <?= $_SESSION["id_Trabajador"] ?>;

            console.log("🟢 ID Papeleta:", idPapeleta);
            console.log("🟢 Campo recibido:", campo);
            console.log("🟢 ID Jefe:", idJefe);

            // Evitar acción si ya está anulado
            if ($(this).attr("title") === "Anulado") {
                console.warn("⛔ [DEBUG] Estado 'Anulado': sin acción.");
                return;
            }

            console.log("⏳ Enviando petición AJAX...");

            $.ajax({
                url: "modules/papeletas/ajax/papeleta.ajax.php",
                type: "POST",
                dataType: "json",
                data: {
                    accion: "intercambiar_estado",
                    id_papeleta: idPapeleta,
                    campo: campo,
                    id_jefe: idJefe
                },

                success: function(res) {
                    console.log("✅ [AJAX] Respuesta recibida:", res);

                    if (res.status === "success") {

                        // 🔄 Recargar tabla server-side SIN mover página
                        const table = $(".tablaAdminPapeleta").DataTable();
                        table.ajax.reload(null, false);

                        // Mensaje opcional
                        // Swal.fire("Listo", res.message, "success");

                    } else {
                        console.warn("⚠️ Error:", res.message);
                        //Swal.fire("Error", res.message, "error");
                    }
                },

                error: function(xhr, status, error) {
                    console.error("❌ [AJAX ERROR]", error);
                    console.log("📄 Respuesta:", xhr.responseText);
                    Swal.fire("Error", "No se pudo actualizar el estado", "error");
                }
            });
        });

        //Cambiar Estado Fin
    });

    document.addEventListener('lazybeforeunveil', function(e) {
        var bg = e.target.getAttribute('data-bg');
        if (bg) {
            e.target.style.backgroundImage = 'url(' + bg + ')';
        }
    });
    // Después de agregar los avatares al DOM
    if (window.refreshFsLightbox) {
        window.refreshFsLightbox();
    }

    $(document).on('click', '.avatar-lightbox', function(e) {
        e.preventDefault(); // evita redirección
        const src = $(this).attr('href'); // URL de la imagen
        const caption = $(this).data('caption') || '';

        // Crear un lightbox temporal
        const tempFsLightbox = document.createElement('a');
        tempFsLightbox.href = src;
        tempFsLightbox.setAttribute('data-fslightbox', 'single');
        tempFsLightbox.setAttribute('data-caption', caption);
        document.body.appendChild(tempFsLightbox);

        // Abrir lightbox
        if (window.refreshFsLightbox) window.refreshFsLightbox();
        tempFsLightbox.click();

        // Limpiar elemento temporal
        setTimeout(() => document.body.removeChild(tempFsLightbox), 100);
    });
</script>