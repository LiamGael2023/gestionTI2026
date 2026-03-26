<script src="/gestionTI/modules/vigilantes/views/papeleta_vigilantes.js"></script>
<div class="page-wrapper">


    <style>
        .avatar {
            
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


        /* Aplica a todas las celdas th y td dentro de la tabla */
        .tablaPapeletaVigilantes th,
        .tablaPapeletaVigilantes td {
            padding: 8px 8px;
            /* Puedes ajustar a tu gusto */
        }

        td h6[title]:hover::after {
            content: attr(title);
            position: absolute;
            background-color: #fff;
            color: #000;
            border: 1px solid #ccc;
            padding: 5px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 9999;
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
    </style>

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
    <div class="page-wrapper">
        <div class="page-body">


            <div class="container-xl">
                <div class="row row-cards">
                    <div class="col-sm-12 col-lg-2">
                        <div class="card">

                            <div class="card-box">
                                <h6 class="text-center mb-4">Leer QR</h6>
                                <div class="container-fluid px-0">
                                    <div class="row justify-content-center gx-4 gy-4">
                                        <button id="btnOpenScanner" class="btn btn-primary justify-content-center">
                                            <!-- SVG de cámara/QR -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-scan">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M4 7v-1a2 2 0 0 1 2 -2h2" />
                                                <path d="M4 17v1a2 2 0 0 0 2 2h2" />
                                                <path d="M16 4h2a2 2 0 0 1 2 2v1" />
                                                <path d="M16 20h2a2 2 0 0 0 2 -2v-1" />
                                                <path d="M5 12l14 0" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>


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
                            <div class="accordion" id="accordionFirmas">
                                <div class="accordion-item">

                                    <!-- ENCABEZADO -->
                                    <h4 class="accordion-header" id="headingFirma">
                                        <button class="accordion-button collapsed"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapseEstados"
                                            aria-expanded="false"
                                            aria-controls="collapseEstados">
                                            Filtros de Estados Pendientes </button>
                                    </h4>

                                    <!-- CONTENIDO (colapsado por defecto) -->
                                    <div id="collapseEstados"
                                        class="accordion-collapse collapse"
                                        aria-labelledby="headingEstados"
                                        data-bs-parent="#accordionEstados">

                                        <div class="accordion-body">

                                            <div class="row justify-content-center gx-2 gy-2">

                                                <div class="col-auto">
                                                    <button class="btn btn-filtro-cerrar" data-filtro=null>Todos</button>
                                                </div>

                                                <div class="col-auto">
                                                    <button class="btn btn-filtro-cerrar" data-filtro="1">Cerradas</button>
                                                </div>

                                                <div class="col-auto">
                                                    <button class="btn btn-filtro-cerrar" data-filtro="0">Sin cerrar</button>
                                                </div>


                                            </div>

                                        </div>

                                    </div>

                                </div>
                            </div>
                            <div class="card-box">
                                <div class="container-fluid px-0">
                                    <div class="row justify-content-center gx-2 gy-2">
                                        <button id="btn-restablecer-filtros" class="btn btn-dark">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restablecer
                                        </button>

                                    </div>
                                </div>
                            </div>

                            <!-- <div class="card-box">
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
              </div> -->
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
                                                    class="display table table-striped table-hover dt-responsive nowrap tablaPapeletaVigilantes"
                                                    style="width: 100%">
                                                    <thead>
                                                        <tr>
                                                            <th style="padding:1px 1px; max-width:10px; text-align:center;">ID</th>
                                                            <th style="padding:4px 4px; width:20px; text-align:center;">
                                                                <h6>QR</h6>
                                                            </th>
                                                            <th style="padding:4px 4px; width:20px; text-align:center;">Foto</th>
                                                            <th style="padding:4px 4px; max-width:30px; text-align:center;">
                                                                <h6>Nombres</h6>
                                                            </th>
                                                            <th style="max-width:180px ; text-align:center;">
                                                                <h6 style="max-width:180px">Concepto / Motivo</h6>
                                                            </th>
                                                            <th style="padding:4px 4px; max-width:30px; text-align:center;">
                                                                <h6 style="max-width:40px">Lugar</h6>
                                                            </th>
                                                            <th style="padding:4px 4px; max-width:30px; text-align:center;">
                                                                <h6>Jefe <br> Inmediato</h6>
                                                            </th>
                                                            <th style="padding:4px 4px; width:20px; text-align:center;">
                                                                <h6>Fechas</h6>
                                                            </th>
                                                            <th style="padding:4px 4px; width:20px; text-align:center;">
                                                                <h6>Horas </h6>
                                                            </th>
                                                            <th style="padding:4px 4px; width:20px; text-align:center;">
                                                                <h6>Retorn.</h6>
                                                            </th>
                                                            <th style="padding:2px 4px; max-width:60px ; text-align:center">
                                                                <h6>Acciones</h6>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>
                                    </div> <!-- fin custom-tables-side-by-side -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- Modal para mostrar el código QR -->
    <?php include __DIR__ . '/../../../fragments/modals/papeletas/papeleta-qr.php'; ?>
    <?php include __DIR__ . '/../../../fragments/modals/papeletas/camara-qr.php'; ?>



    <script>
        document.addEventListener('lazybeforeunveil', function(e) {
            var bg = e.target.getAttribute('data-bg');
            if (bg) {
                e.target.style.backgroundImage = 'url(' + bg + ')';
            }
        });
    </script>


    <!-- MODAL PARA EL ID -->
    <div id="modalId" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 400px; margin: auto; padding: 20px; text-align: center;">
            <h5>ID de Papeleta</h5>
            <p id="idPapeletaText" style="font-weight: bold; font-size: 18px;"></p>

            <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
                <button id="btnSalida" class="btn btn-success" style="flex:1; padding: 10px; font-weight: bold;">SALIDA</button>
                <button id="btnRetorno" class="btn btn-primary" style="flex:1; padding: 10px; font-weight: bold;">RETORNO</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {

            $("#btnSalida").on("click", function() {
                var idPapeleta = $("#idPapeletaText").text().trim();

                $.ajax({
                    url: "modules/vigilantes/ajax/papeleta.ajax.php",
                    type: "POST",
                    data: {
                        accion: "marcar_salida",
                        id_papeleta: idPapeleta
                    },
                    success: function(resp) {
                        console.log(resp);

                        if (resp.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Salida registrada',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function() {
                                $('.tablaPapeletaVigilantes').DataTable().ajax.reload(null, false);
                                $("#modalId").hide();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: resp.message || 'No se pudo registrar la salida.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error AJAX',
                            text: error
                        });
                    }
                });
            });

            $("#btnRetorno").on("click", function() {
                var idPapeleta = $("#idPapeletaText").text().trim();

                $.ajax({
                    url: "modules/vigilantes/ajax/papeleta.ajax.php",
                    type: "POST",
                    data: {
                        accion: "marcar_retorno",
                        id_papeleta: idPapeleta
                    },
                    success: function(resp) {
                        console.log(resp);

                        if (resp.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Retorno registrado',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function() {
                                $('.tablaPapeletaVigilantes').DataTable().ajax.reload(null, false);
                                $("#modalId").hide();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: resp.message || 'No se pudo registrar el retorno.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error AJAX',
                            text: error
                        });
                    }
                });
            });


        });


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