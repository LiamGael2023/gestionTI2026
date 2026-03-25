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
                <div class="col-12">
                    <div class="card">
                        <div class="card-table">
                            <div class="card-header">
                                <div class="row w-full">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <h3 class="card-title mb-0">Conductores</h3>
                                        <!-- Datepicker -->
                                        <div class="d-flex align-items-center">
                                            <span class="form-label me-2">
                                                Fecha de búsqueda: <font color="red">(*)</font>
                                            </span>

                                            <input class="form-control w-auto"
                                                id="fechaBusqueda"
                                                placeholder="DD/MM/YYYY"
                                                style="min-width: 160px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="advanced-table">
                                <div class="table-responsive">
                                    <table id="new-cons" class="display table table-striped table-hover dt-responsive nowrap tablaConductor" style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th class="align-left">ID</th>
                                                <th>Foto</th>
                                                <th>Trabajador</th>
                                                <th>Gerencia/Oficina</th>
                                                <th>Unidad/División</th>
                                                <th>Placa</th>
                                                <th>Papeleta del Dia</th>
                                                <th>Acciones</th>
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
    </div>
    <!-- Modal para mostrar el código QR -->
    <?php include __DIR__ . '/../fragments/modals/papeleta-qr.php'; ?>
    <!-- Modal para mostrar el PDF -->
    <?php include __DIR__ . '/../fragments/modals/contenedor-pdf.php'; ?>
</div>

<script>
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