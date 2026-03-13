<style>
/* ===============================
   GRID DE DOS TABLAS SIN SCROLL
================================*/
.boletas-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

/* MÓVIL: 1 SOLA TABLA */
@media (max-width: 768px) {
    .boletas-grid {
        grid-template-columns: 1fr;
    }
}

.boletas-grid table {
    width: 100%;
    border-collapse: collapse;
}

.boletas-grid th,
.boletas-grid td {
    border: 1px solid #dee2e6;
    padding: 10px;
    text-align: center;
    white-space: nowrap;
}

.boletas-grid thead {
    background: #f8f9fa;
}

/* ===============================
   SELECT2 CENTRADO
================================*/
.select2-selection.select2-selection--single {
    height: 38px !important;
    display: flex !important;
    align-items: center !important;
    padding: 0 0.75rem !important;
    position: relative;
}

.select2-selection__rendered {
    display: flex !important;
    align-items: center !important;
    gap: 6px;
    line-height: normal !important;
    padding-right: 24px !important;
}

.select2-selection__arrow {
    position: absolute !important;
    right: 10px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
}

.select2-selection__clear {
    position: absolute !important;
    right: 28px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
}
</style>

<div class="page-body">
    <div class="container-xl">

        <!-- FILTRO -->
        <div class="row row-cards mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <label class="form-label">Listado de trabajadores</label>
                        <select class="form-select" id="trabajador2" style="width:100%">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD BOLETAS -->
        <div class="card shadow-sm">
            <div id="msgEstado" class="alert alert-info text-center fw-bold d-none"></div>

            <div class="card-header border-bottom-0">
                <ul class="nav nav-tabs card-header-tabs" id="yearTabs"></ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="yearTabsContent"></div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL PDF -->
<?php include __DIR__ . '/../fragments/modals/contenedor-pdf.php'; ?>

<script>
$(document).ready(function () {

    let msg = $("#msgEstado");

    /* ===============================
       SELECT2
    ================================*/
    $("#trabajador2").select2({
        theme: "bootstrap-5",
        placeholder: "Seleccione un trabajador...",
        allowClear: true,
        width: "100%",
        ajax: {
        url: "modules/personal/ajax/colaborador.ajax.php",
            dataType: "json",
            delay: 250,
            data: function (params) {
                return {
                    action: "getTrabajadoresActivos",
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(item => ({
                        id: item.id,
                        text: item.text
                    }))
                };
            }
        }
    });

    /* ===============================
       CAMBIO DE TRABAJADOR
    ================================*/
    $("#trabajador2").on("change", function () {

        let idTrabajador = $(this).val();

        if (!idTrabajador) {
            msg.removeClass("d-none")
               .addClass("alert-info")
               .html("Seleccione un trabajador");
            return;
        }

        msg.removeClass("d-none")
           .removeClass("alert-info")
           .addClass("alert-warning")
           .html("Cargando...");

        $.ajax({
        url: "modules/personal/ajax/planilla.ajax.php",
            type: "POST",
            data: {
                accion: "consultarAniosBoletasPorTrabajador",
                id_Trabajador: idTrabajador
            },
            dataType: "json",
            success: function (res) {

                msg.addClass("d-none");

                if (res.status !== "success") return;

                generarTabsPorTrabajador(res.data, idTrabajador);
            }
        });

    });

});


/* ===============================
   GENERAR TABS Y DOS TABLAS
================================*/
function generarTabsPorTrabajador(anios, idTrabajador) {

    $("#yearTabs").empty();
    $("#yearTabsContent").empty();

    anios.forEach((item, index) => {

        let year = item.Id_Anio;
        let active = index === 0 ? "active" : "";
        let show = index === 0 ? "show active" : "";

        $("#yearTabs").append(`
            <li class="nav-item">
                <button class="nav-link ${active}"
                    data-bs-toggle="tab"
                    data-bs-target="#content-${year}">
                    ${year}
                </button>
            </li>
        `);

        $("#yearTabsContent").append(`
            <div class="tab-pane fade ${show}" id="content-${year}">

                <h6 class="fw-bold mb-3">Boletas ${year}</h6>

                <div class="boletas-grid">

                    <!-- TABLA IZQUIERDA -->
                    <table id="tabla-${year}-1">
                        <thead>
                            <tr>
                                <th>Tipo de Planilla</th>
                                <th>Periodo</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <!-- TABLA DERECHA -->
                    <table id="tabla-${year}-2">
                        <thead>
                            <tr>
                                <th>Tipo de Planilla</th>
                                <th>Periodo</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                </div>

            </div>
        `);
    });

    cargarBoletasPorAnioPorTrabajador(anios[0].Id_Anio, idTrabajador);

    $("#yearTabs button").on("shown.bs.tab", function () {
        let year = $(this).text().trim();
        cargarBoletasPorAnioPorTrabajador(year, idTrabajador);
    });
}


/* ===============================
   CARGAR BOLETAS Y REPARTIR
================================*/
function cargarBoletasPorAnioPorTrabajador(year, idTrabajador) {

    $.ajax({
        url: "modules/personal/ajax/planilla.ajax.php",
        type: "POST",
        data: {
            accion: "listarBoletasPorAnioPorTrabajador",
            anio: year,
            id_Trabajador: idTrabajador
        },
        dataType: "json",
        success: function (res) {

            let t1 = $(`#tabla-${year}-1 tbody`);
            let t2 = $(`#tabla-${year}-2 tbody`);

            t1.empty();
            t2.empty();

            if (!res.data || res.data.length === 0) {
                t1.append(`<tr><td colspan="3">No hay boletas</td></tr>`);
                $(`#tabla-${year}-2`).hide();
                return;
            }

            let mitad = Math.ceil(res.data.length / 2);

            res.data.forEach((b, index) => {

                let fila = `
                    <tr>
                        <td>${b.TipoPlanilla}</td>
                        <td>${b.Periodo}</td>
                        <td>
                            <button class="btn btn-blue"
                                data-bs-toggle="modal"
                                data-bs-target="#pdfModal"
                                data-pdf-url="pdf/pdf/boleta2025.php?anio=${b.Id_Anio}&mes=${b.Id_Mes}&idplanillaauxiliar=${b.Id_Planilla_Auxiliar}&tipotrabajador=${b.Id_Trabajador_Tipo}&idtrabajador=${b.Id_Trabajador}&numeroplanilla=${b.Planilla_Numero}&contrato=${b.Id_Contrato}&dato=${b.Id_Dato}">
                                Descargar
                            </button>
                        </td>
                    </tr>
                `;

                if (index < mitad) {
                    t1.append(fila);
                } else {
                    t2.append(fila);
                }

            });

            /* OCULTAR LA TABLA DERECHA SI NO TIENE DATOS */
            if (res.data.length <= mitad) {
                $(`#tabla-${year}-2`).hide();
            } else {
                $(`#tabla-${year}-2`).show();
            }
        }
    });

}
</script>
