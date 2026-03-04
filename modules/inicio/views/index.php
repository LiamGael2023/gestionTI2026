  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<div class='page-header'><div class='container-xl'><h2 class='page-title'>inicio</h2></div></div>
<div class='page-body'><div class='container-xl'><div class='card'><div class='card-body'>Bienvenido al sistema de inicio.</div></div></div></div>
<style>
    /* Evitar que el card se rompa cuando haya tablas grandes */
    .card-body {
        overflow-x: hidden;
    }

    /* Asegurar scroll horizontal SOLO en tablas */
    .card-body .table-responsive {
        width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    /* Evitar que la tabla crezca más que el card */
    .card-body table {
        width: 100%;
        min-width: 650px;
        /* puedes ajustar este valor */
    }

    /* Evitar que botones y badges fuercen el ancho */
    .table td,
    .table th {
        white-space: nowrap;
        /* evita saltos que rompen diseño */
    }
</style>

<body class="bg-light">

    <div class="container-xl py-4">
        <!-- Info del trabajador -->
        <div class="row g-4">

            <!-- Col izquierda -->
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm h-100 text-center">
                    <div class="card-body">
                        <?php
                        // Verifica si la sesión tiene foto y no está vacía
                        if (!empty($_SESSION['foto_personal'])) {
                            $foto = 'https://app.chavimochic.gob.pe/personal/repositorio/perfil/' . $_SESSION['foto_personal'];
                        } else {
                            $foto = '../personal/vistas/img/default/sinfoto.jpg';
                        }
                        ?>

                        <div class="mx-auto mb-3 rounded"
                            style="width:128px; height:128px;
            background-image:url('<?php echo $foto; ?>');
            background-size:cover; background-position:center;">
                        </div>
                        <h5 class="card-title fw-bold mb-1">
                            <?php
                            $nombreCompleto =
                                (isset($_SESSION["Trab_Paterno"]) ? $_SESSION["Trab_Paterno"] : '') . ' ' .
                                (isset($_SESSION["Trab_Materno"]) ? $_SESSION["Trab_Materno"] : '') . ' ' .
                                (isset($_SESSION["Trab_Nombres"]) ? $_SESSION["Trab_Nombres"] : '');

                            echo ($nombreCompleto);
                            ?>

                        </h5>
                        <p class="text mb-1">
                            <?php echo (isset($_SESSION["Oficina"]) ? $_SESSION["Oficina"] : null); ?>
                        </p>
                        <p class="text-muted mb-1">
                            <?php echo (isset($_SESSION["Gerencia"]) ? $_SESSION["Gerencia"] : null); ?>
                        </p>


                    </div>
                </div>
            </div>

            <!-- Col derecha -->
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">Información Personal</h5>
                        <div class="row g-3">

                            <!-- Documento -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/perfil/documento.svg" width="20" height="20" alt="">
                                    <div>
                                        <small class="text-muted">Documento</small>
                                        <div><?php echo ($_SESSION["Trab_Documento"] ?? null); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fecha de Nacimiento -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/perfil/fecha-nacimiento.svg" width="20" height="20" alt="">
                                    <div>
                                        <small class="text-muted">Fecha de Nacimiento</small>
                                        <div><?php echo ($_SESSION["Fecha_Nacimiento"] ?? null); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Correo Electrónico -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/perfil/correo.svg" width="20" height="20" alt="">
                                    <div>
                                        <small class="text-muted">Correo Electrónico</small>
                                        <div><?php echo ($_SESSION["Correo"] ?? null); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Celular -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/perfil/celular.svg" width="20" height="20" alt="">
                                    <div>
                                        <small class="text-muted">Celular</small>
                                        <div><?php echo ($_SESSION["Celular"] ?? null); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo Trabajador -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/perfil/tipo-trabajador.svg" width="20" height="20" alt="">
                                    <div>
                                        <small class="text-muted">Tipo Trabajador</small>
                                        <div><?php echo ($_SESSION["TrabTipo_Descripcion"] ?? null); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Jefe Inmediato -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <img src="vistas/img/iconos/perfil/jefe_inmediato.svg" width="20" height="20" alt="">
                                    <div>
                                        <small class="text-muted">Jefe Inmediato</small>
                                        <div><?php echo ($_SESSION["JefeInmediato"] ?? null); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div><!-- /row -->

        <!-- Boletas -->
        <div class="card shadow-sm mt-4">
            <div class="card-header border-bottom-0">
                <ul class="nav nav-tabs card-header-tabs" id="yearTabs" role="tablist"></ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="yearTabsContent"></div>
            </div>
        </div>



    </div><!-- /container-xl -->


    <!-- Bootstrap JS -->

</body>

<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel">Reporte PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <iframe id="pdfViewer" src="" frameborder="0" style="width: 100%; height: 80vh;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pdfModal = document.getElementById('pdfModal');

        pdfModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const pdfUrl = button.getAttribute('data-pdf-url');
            const iframe = pdfModal.querySelector('#pdfViewer');
            iframe.src = pdfUrl;
        });

        pdfModal.addEventListener('hidden.bs.modal', function() {
            const iframe = pdfModal.querySelector('#pdfViewer');
            iframe.src = '';
        });
    });
    // 1) Consultar años y generarlos dinámicamente
    $.ajax({
        url: "modules/inicio/ajax/planilla.ajax.php",
        type: "POST",
        data: {
            accion: "consultarAniosBoletas"
        },
        dataType: "json",
        success: function(res) {
            if (res.status !== "success") return;

            generarTabs(res.data);
        }
    });

    function generarTabs(anios) {

        let tabs = $("#yearTabs");
        let content = $("#yearTabsContent");

        tabs.empty();
        content.empty();

        anios.forEach((item, index) => {

            let year = item.Id_Anio;
            let active = index === 0 ? "active" : "";
            let show = index === 0 ? "show active" : "";

            // ---------------- TABS ------------------
            tabs.append(`
            <li class="nav-item">
                <button class="nav-link ${active}" 
                    id="tab-${year}" 
                    data-bs-toggle="tab" 
                    data-bs-target="#content-${year}" 
                    type="button" 
                    role="tab">
                    ${year}
                </button>
            </li>
        `);

            // ---------------- TAB CONTENT ------------------
            content.append(`
            <div class="tab-pane fade ${show}" id="content-${year}" role="tabpanel">

                <h6 class="fw-bold mb-3">Boletas ${year}</h6>

                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="tabla-${year}">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo de planilla</th>
                                <th>Periodo</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>
        `);
        });

        // ✅ Cargar automáticamente el año más reciente
        cargarBoletasPorAnio(anios[0].Id_Anio);

        // ✅ Solo cargar cuando se hace click en un tab
        $("#yearTabs button").on("shown.bs.tab", function() {
            let year = $(this).text().trim().replace(/\s+/g, ""); // ✅ enviar año
            let tbody = $(`#tabla-${year} tbody`);

            // Si ya se cargó antes → no volver a cargar
            if (tbody.attr("data-loaded") === "1") return;

            cargarBoletasPorAnio(year);
        });
    }

    function cargarBoletasPorAnio(year) {

        $.ajax({
            url: "../ajax/planilla.ajax.php",
            type: "POST",
            data: {
                accion: "listarBoletasPorAnio",
                anio: year
            },
            dataType: "json",
            success: function(res) {

                let tbody = $(`#tabla-${year} tbody`);
                tbody.empty();

                res.data.forEach(b => {

                    // badge
                    const estado = b.Estado == 1 ?
                        `<span class="badge bg-success text-white" style="min-width:110px; padding: 10px 15px; display:inline-block; text-align:center;">Descargado</span>` : // <- Añadido padding
                        `<span
        class="badge bg-warning text-white badge-descargar"
        style="min-width:110px; padding: 10px 15px; display:inline-block; text-align:center; cursor:pointer;" // <- Añadido padding
        data-trabajador="${b.Id_Trabajador}"
        data-anio="${b.Id_Anio}"
        data-mes="${b.Id_Mes}"
        data-planilla="${b.Id_Planilla_Auxiliar}"
            >Pendiente</span>`;

                    // ✅ deshabilitar botón si está pendiente
                    const disabled = b.Estado == 1 ? "" : "disabled";

                    tbody.append(`
                <tr>
                        <td>${b.TipoPlanilla}</td>
                        <td>${b.Periodo}</td>
                        <td>${estado}</td>

                        <td class="text-center">
                            <button class="btn btn-blue"
                                ${disabled}

                            

                                data-bs-toggle="modal"
                                data-bs-target="#pdfModal"

                                data-pdf-url="repositorio/pdf/boleta2025.php?anio=${b.Id_Anio}&mes=${b.Id_Mes}&idplanillaauxiliar=${b.Id_Planilla_Auxiliar}&tipotrabajador=${b.Id_Trabajador_Tipo}&idtrabajador=${b.Id_Trabajador}&numeroplanilla=${b.Planilla_Numero}&contrato=${b.Id_Contrato}&dato=${b.Id_Dato}"
                            >
                                Descargar
                            </button>
                        </td>
                    </tr>

                                `);
                                    });



                // evento delegado para los badges "Pendiente"
                $(document).on("click", ".badge-descargar", function() {
                    const $badge = $(this);
                    const $row = $badge.closest("tr");

                    // debug: ver si encontró la fila y el botón
                    console.log("fila encontrada:", $row.length);
                    console.log("botones en la última celda:", $row.find("td:last-child button").length);
                    console.log("botones con clase .btn-blue:", $row.find("button.btn-blue").length);

                    // obtener referencia al botón (intenta por td:last-child, si no existe usa la clase)
                    let $downloadBtn = $row.find("td:last-child button");
                    if ($downloadBtn.length === 0) {
                        $downloadBtn = $row.find("button.btn-blue");
                    }

                    // bloqueo visual mientras procesa
                    $badge.css({
                        "pointer-events": "none",
                        opacity: 0.6
                    });

                    $.post("ajax/ajax/planilla.ajax.php", {
                        accion: "actualizar-descargado",
                        id_trabajador: $badge.data("trabajador"),
                        anio: $badge.data("anio"),
                        mes: $badge.data("mes"),
                        planilla_auxiliar: $badge.data("planilla")
                    }, function(res) {
                        const r = (typeof res === "string") ? JSON.parse(res) : res;

                        if (r.status === "success") {
                            // 1) habilitar el botón primero (antes de reemplazar el badge)
                            if ($downloadBtn.length) {
                                $downloadBtn.prop("disabled", false);
                                console.log("Botón habilitado.");
                            } else {
                                console.warn("No se encontró botón para habilitar en la fila.");
                            }

                                        // 2) luego reemplazar el badge
                                                        $badge.replaceWith(`
                                        <span class="badge bg-success text-white"
                                                    style="min-width:110px; padding: 10px 15px; display:inline-block; text-align:center;">
                                                    Descargado
                                                </span>
                                                `);
                        } else {
                            // restaurar badge si error y avisar
                            $badge.css({
                                "pointer-events": "",
                                opacity: 1
                            });
                            alert("Error: " + (r.message || "No se pudo actualizar"));
                        }
                    }).fail(function() {
                        $badge.css({
                            "pointer-events": "",
                            opacity: 1
                        });
                        alert("Error de conexión");
                    });
                });

                // ✅ Marcar como cargado
                tbody.attr("data-loaded", "1");
            }
        });

    }
</script>

<!-- <div class="modal fade" id="inicioModal" tabindex="-1" aria-labelledby="inicioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm mt-4" style="max-width: 360px;"> 
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

            <div class="modal-header bg-primary text-white border-0 py-2">
                <h6 class="modal-title fw-semibold" id="inicioModalLabel">Bienvenido</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body text-center p-3">
                <p class="mb-2 fw-semibold">
                    ¡Hola <?php echo conversionUTF(isset($_SESSION["Trab_Nombres"]) ? $_SESSION["Trab_Nombres"] : ''); ?>! 👋
                </p>

                <a href="https://app.chavimochic.gob.pe/Webservice/chaviton/" target="_blank" class="d-inline-block">
                    <img src="https://app.chavimochic.gob.pe/webservice/aplicativoPECH/chaviton2.png"
                        alt="Chavitón"
                        class="img-fluid"
                        style="width: 100%; max-width: 380px; height: auto; cursor: pointer; transition: transform 0.3s ease;">
                </a>
            </div>

        </div>
    </div>
</div> -->
<?php include __DIR__ . '/fragments/modals/popup-inicio.php'; ?>

</html>