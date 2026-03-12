/* =====================================
   TOAST TABLER
===================================== */
function mostrarToast(tipo, mensaje) {

    const colores = {
        success: "bg-success",
        error: "bg-danger",
        warning: "bg-warning",
        info: "bg-info"
    };

    const html = `
    <div class="toast align-items-center text-white ${colores[tipo]} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;

    const container = document.getElementById("toastContainer");

    if (!container) {
        console.error("No existe #toastContainer en el HTML");
        return;
    }

    container.insertAdjacentHTML("beforeend", html);

    const toast = new bootstrap.Toast(container.lastElementChild, {
        delay: 3500
    });

    toast.show();
}


/* =====================================
   DOM READY
===================================== */
document.addEventListener("DOMContentLoaded", function () {

    const iconos = {
        equipos: ["ti-device-desktop", "ti-device-laptop", "ti-device-tablet", "ti-server"],
        componentes: ["ti-cpu", "ti-device-hdd", "ti-device-ssd", "ti-device-usb", "ti-device-sd-card"],
        perifericos: ["ti-mouse", "ti-keyboard", "ti-headphones", "ti-microphone", "ti-speaker"],
        pantallas: ["ti-device-desktop", "ti-presentation", "ti-projector"],
        impresion: ["ti-printer", "ti-printer-3d", "ti-copy"],
        red: ["ti-router", "ti-network", "ti-wifi", "ti-antenna"]
    };


    /* =====================================
       GENERAR ICONOS
    ===================================== */
    function generarIconos(tipo, contenedor, preview, inputHidden, iconoActual = null) {

        contenedor.innerHTML = "";
        if (!iconos[tipo]) return;

        iconos[tipo].forEach(icono => {

            let seleccionado = (icono === iconoActual)
                ? "border-primary bg-primary-lt"
                : "";

            let html = `
            <div class="col-4 col-sm-3">
                <div class="card card-sm text-center icono-item ${seleccionado}" 
                     data-icon="${icono}" 
                     style="cursor:pointer">

                    <div class="card-body p-2">

                        <i class="ti ${icono} fs-1 text-primary"></i>

                        <div class="text-muted" style="font-size:0.65rem">
                            ${icono.replace("ti-", "")}
                        </div>

                    </div>

                </div>
            </div>`;

            contenedor.insertAdjacentHTML("beforeend", html);
        });


        if (iconoActual) {

            preview.innerHTML = `<i class="ti ${iconoActual}"></i>`;
            inputHidden.value = iconoActual;

        }

    }


    /* =====================================
       ELEMENTOS AGREGAR
    ===================================== */

    const tipoIcono = document.getElementById("tipoIcono");
    const listaIconos = document.getElementById("listaIconos");
    const previewIcon = document.getElementById("previewIcon");
    const iconoActivo = document.getElementById("iconoActivo");


    if (tipoIcono) {

        tipoIcono.addEventListener("change", function () {

            generarIconos(
                this.value,
                listaIconos,
                previewIcon,
                iconoActivo
            );

        });

    }


    /* =====================================
       ELEMENTOS EDITAR
    ===================================== */

    const editarTipoIcono = document.getElementById("editarTipoIcono");
    const editarListaIconos = document.getElementById("editarListaIconos");
    const editarPreviewIcon = document.getElementById("editarPreviewIcon");
    const editarIconoActivo = document.getElementById("editarIconoActivo");


    if (editarTipoIcono) {

        editarTipoIcono.addEventListener("change", function () {

            generarIconos(
                this.value,
                editarListaIconos,
                editarPreviewIcon,
                editarIconoActivo,
                editarIconoActivo.value
            );

        });

    }


    /* =====================================
       SELECCIONAR ICONO
    ===================================== */

    document.addEventListener("click", function (e) {

        let item = e.target.closest(".icono-item");

        if (!item) return;

        let contenedor = item.closest(".row");

        contenedor.querySelectorAll(".icono-item").forEach(el => {

            el.classList.remove("border-primary", "bg-primary-lt");

        });

        item.classList.add("border-primary", "bg-primary-lt");

        let icono = item.getAttribute("data-icon");

        let modal = item.closest(".modal");


        if (modal && modal.id === "modalAgregarActivo") {

            previewIcon.innerHTML = `<i class="ti ${icono}"></i>`;
            iconoActivo.value = icono;

        }


        if (modal && modal.id === "modalEditarActivo") {

            editarPreviewIcon.innerHTML = `<i class="ti ${icono}"></i>`;
            editarIconoActivo.value = icono;

        }

    });


    /*==================================================
    EDITAR ACTIVO
    ==================================================*/
    // Usamos delegación de eventos con JavaScript puro
    document.addEventListener("click", function (e) {

        // Verificamos si el elemento clickeado es el botón o está dentro del botón
        const boton = e.target.closest(".btnEditarActivo");

        if (boton) {
            // Capturamos el ID usando getAttribute o dataset
            var idActivo = boton.getAttribute("data-id");
            console.log("1. ID enviado al servidor:", idActivo);

            let datos = new FormData();
            datos.append("idActivo", idActivo);

            fetch("modules/inventario/ajax/activos.ajax.php", {
                method: "POST",
                body: datos
            })
                .then(res => res.text())
                .then(respuesta => {
                    console.log("2. Respuesta cruda del servidor:", respuesta);

                    try {
                        let json = JSON.parse(respuesta.trim());

                        if (json.error) {
                            mostrarToast("error", json.error);
                            return;
                        }

                        // Llenamos los inputs
                        document.getElementById("editarIdActivo").value = json.idActivos;
                        document.getElementById("editarDescripcion").value = json.descripcion;
                        document.getElementById("editarIconoActivo").value = json.icono;
                        document.getElementById("editarCompuesto").checked = (json.compuesto == 1);
                        document.getElementById("editarUsuarioCreacion").textContent = json.idUsuarioRegistro;
                        document.getElementById("editarFechaCreacion").textContent = json.fechaCreacion;
                        document.getElementById("editarPreviewIcon").innerHTML = `<i class="ti ${json.icono}"></i>`;

                        // Abrimos el modal
                        let modalElement = document.getElementById('modalEditarActivo');
                        let modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                        modalInstance.show();

                    } catch (e) {
                        console.error("Error al parsear el JSON:", e);
                        mostrarToast("error", "Error interno en el servidor.");
                    }
                })
                .catch(error => {
                    console.error("Error en la petición Fetch:", error);
                    mostrarToast("error", "Error de conexión.");
                });
        }
    });

    /* =====================================
       ENVÍO AJAX FORMULARIO AGREGAR
    ===================================== */

    const form = document.getElementById("formNuevoActivo");

    if (form) {

        form.addEventListener("submit", function (e) {

            e.preventDefault();

            if (iconoActivo.value === "ti ti-help") {

                mostrarToast("warning", "Por favor selecciona un icono para el activo");
                return;

            }

            let datos = new FormData(form);


            fetch("modules/inventario/ajax/activos.ajax.php", {

                method: "POST",
                body: datos

            })

                .then(res => res.text())

                .then(respuesta => {

                    console.log("Respuesta servidor:", respuesta);

                    let res = JSON.parse(respuesta.trim());


                    if (res === "ok") {

                        let modal = bootstrap.Modal.getInstance(
                            document.getElementById("modalAgregarActivo")
                        );

                        modal.hide();

                        mostrarToast("success", "Activo guardado correctamente");

                        setTimeout(() => {

                            location.reload();

                        }, 1500);

                    } else {

                        mostrarToast("error", "No se pudo guardar: " + res);

                    }

                })

                .catch(error => {

                    console.error("Error AJAX:", error);

                    mostrarToast(
                        "error",
                        "No se pudo contactar con el servidor"
                    );

                });

        });

    }

    /* =====================================
       ENVÍO AJAX FORMULARIO EDITAR
    ===================================== */

    const formEditar = document.getElementById("formEditarActivo");

    if (formEditar) {

        formEditar.addEventListener("submit", function (e) {

            e.preventDefault();

            let datos = new FormData(formEditar);

            fetch("modules/inventario/ajax/activos.ajax.php", {

                method: "POST",
                body: datos

            })

                .then(res => res.json())

                .then(res => {

                    if (res === "ok") {

                        mostrarToast("success", "Activo actualizado correctamente");

                        setTimeout(() => {

                            location.reload();

                        }, 1500);

                    } else {

                        mostrarToast("error", "No se pudo actualizar");

                    }

                })

                .catch(error => {

                    mostrarToast("error", "Error en el servidor");

                });

        });

    }

    $(document).ready(function () {
        $('#tablaActivos').DataTable({
            "responsive": true,
            "pageLength": 10,
            // "language": {
            //     "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" // Traducción completa
            // },
            "dom": `
            <'card-body border-bottom py-2'
                <'d-flex align-items-center'
                    <'text-muted small'l>
                    <'ms-auto d-flex align-items-center gap-2'Bf>
                >
            >
            <'table-responsive'tr>
            <'card-footer d-flex align-items-center py-2'
                <'m-0 text-muted small'i>
                <'pagination m-0 ms-auto'p>
            >
        `,
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="ti ti-file-spreadsheet"></i>',
                    className: 'btn btn-sm btn-icon btn-outline-success',
                    titleAttr: 'Excel',
                    exportOptions: { columns: [0, 1, 2] }
                }, // <-- Esta coma es obligatoria
                {
                    extend: 'pdfHtml5',
                    text: '<i class="ti ti-file-description"></i>',
                    className: 'btn btn-sm btn-icon btn-outline-danger ms-2', // ms-2 añade margen a la izquierda
                    titleAttr: 'PDF',
                    exportOptions: { columns: [0, 1, 2] }
                }
            ],
            "initComplete": function () {

                $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar...');
                $('.dataTables_length select').addClass('form-select form-select-sm');

                $('.dataTables_paginate .pagination').addClass('pagination-sm');
            }
        });
    });
});