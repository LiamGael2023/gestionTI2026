<!-- jQuery y DataTables JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<!-- Agregar JS de Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/plugins/bgset/ls.bgset.min.js" async></script>
<script src="https://cdn.jsdelivr.net/npm/fslightbox/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrious/dist/qrious.min.js"></script>
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

    .btn-filtro {
        color: white;
        border: none;
        font-weight: 500;
        border-radius: 6px;
        padding: 6px 14px;
    }

    .btn-filtro-1 {
        background-color: #1e3a8a;
    }

    /* azul oscuro */
    .btn-filtro-2 {
        background-color: #2563eb;
    }

    /* azul medio */
    .btn-filtro-3 {
        background-color: #3b82f6;
    }

    /* azul */
    .btn-filtro-4 {
        background-color: #60a5fa;
    }

    /* azul claro */
    .btn-filtro-5 {
        background-color: #93c5fd;
        color: #111;
    }

    /* muy claro */
    .btn-filtro-6 {
        background-color: #bfdbfe;
        color: #111;
    }

    /* casi pastel */
    .btn-filtro-7 {
        background-color: #dbeafe;
        color: #111;
    }

    /* extra suave */

    .btn-filtro:hover {
        opacity: 0.9;
    }

    .btn-filtro {
        color: white;
        border: none;
        font-weight: 500;
        border-radius: 6px;
        padding: 6px 14px;
    }

    .btn-filtro-1 {
        background-color: #1d4ed8;
    }

    /* azul fuerte */
    .btn-filtro-2 {
        background-color: #2563eb;
    }

    /* azul medio */
    .btn-filtro-3 {
        background-color: #3b82f6;
    }

    /* azul estándar */
    .btn-filtro-4 {
        background-color: #60a5fa;
        color: #111;
    }

    /* azul claro */
    .btn-filtro-5 {
        background-color: #7dd3fc;
        color: #111;
    }

    /* azul suave */
    .btn-filtro-6 {
        background-color: #38bdf8;
        color: #111;
    }

    /* azul brillante */
    .btn-filtro-7 {
        background-color: #0ea5e9;
    }

    /* azul celeste */
    /* extra suave */

    .btn-filtro:hover {
        opacity: 0.9;
    }

    .btn-filtro {
        border: none;
        font-weight: 500;
        border-radius: 6px;
        padding: 6px 14px;
        color: white;
    }

    .btn-filtro-verde-1 {
        background-color: #059669;
    }

    /* verde esmeralda */
    .btn-filtro-verde-2 {
        background-color: #10b981;
    }

    /* verde medio */
    .btn-filtro-verde-3 {
        background-color: #34d399;
        color: #111;
    }

    /* verde claro */
    .btn-filtro-verde-4 {
        background-color: #6ee7b7;
        color: #111;
    }

    /* verde pastel */
    .btn-filtro-verde-5 {
        background-color: #a7f3d0;
        color: #111;
    }

    /* verde muy suave */

    .btn-filtro:hover {
        opacity: 0.9;
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
    .tablaAdminPapeleta th,
    .tablaAdminPapeleta td {
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

    .table-responsive {
        overflow-x: auto;
        /* Permite scroll horizontal si la tabla es más ancha */
    }

    .table.dataTable {
        width: 100% !important;
        /* Asegura que DataTables use todo el ancho disponible */
        table-layout: auto !important;
        /* Permite ajustar el ancho dinámico */
        white-space: nowrap;
        /* Evita que las celdas se rompan */
    }

    .table.dataTable th,
    .table.dataTable td {
        text-align: left;
        vertical-align: middle;
        padding: 10px 6px;
    }
</style>
<div class="page-wrapper">
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-sm-12 col-lg-2">
                    <div class="card">


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
                                                class="display table table-striped table-hover dt-responsive nowrap tablaPapeletasVehiculares"
                                                style="width: 100%">
                                                <thead>
                                                    <tr>
                                                        <th style="padding:1px 1px; max-width:10px; text-align:center;">ID</th>
                                                        <th style="padding:4px 4px; width:20px; text-align:center;">Foto</th>
                                                        <th style="padding:4px 4px; width:20px; text-align:center;">
                                                            <h6>QR</h6>
                                                        </th>
                                                        <th style="padding:4px 4px; max-width:30px; text-align:center;">
                                                            <h6>Nombres</h6>
                                                        </th>
                                                        <th style="padding:4px 4px; width:20px; text-align:center;">
                                                            <h6>FIRMAS</h6>
                                                        </th>
                                                        <th style="padding:4px 4px; width:20px; text-align:center;">
                                                            <h6>Fechas</h6>
                                                        </th>
                                                        <th style="padding:4px 4px; width:20px; text-align:center;">
                                                            <h6>Horas </h6>
                                                        </th>
                                                        <th style="padding:4px 4px; width:10px; text-align:center;">
                                                            <h6>Retorn.</h6>
                                                        </th>
                                                        <th style="max-width:100px ; text-align:center;">
                                                            <h6 style="max-width:100px">Concepto / Motivo</h6>
                                                        </th>
                                                        <th style="padding:4px 4px; width:10px; text-align:center;">
                                                            <h6>Sede</h6>
                                                        </th>
                                                        <th style="padding:2px 4px; max-width:40px ; text-align:center">
                                                            <h6>Acciones</h6>
                                                        </th>

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


        document.addEventListener('lazybeforeunveil', function(e) {
            var bg = e.target.getAttribute('data-bg');
            if (bg) {
                e.target.style.backgroundImage = 'url(' + bg + ')';
            }
        });
    </script>