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


    /* =====================================
       CARGAR MODAL EDITAR
    ===================================== */

    window.cargarEditarActivo = function (data) {

        if (document.getElementById("editarIdActivo"))
            document.getElementById("editarIdActivo").value = data.idActivos;

        if (document.getElementById("editarDescripcion"))
            document.getElementById("editarDescripcion").value = data.descripcion;

        if (document.getElementById("editarIconoActivo"))
            document.getElementById("editarIconoActivo").value = data.icono;


        if (document.getElementById("editarUsuarioCreacion"))
            document.getElementById("editarUsuarioCreacion").textContent = data.usuarioCreacion;

        if (document.getElementById("editarFechaCreacion"))
            document.getElementById("editarFechaCreacion").textContent = data.fechaCreacion;


        if (document.getElementById("editarCompuesto"))
            document.getElementById("editarCompuesto").checked = (data.compuesto == 1);


        if (document.getElementById("editarPreviewIcon"))
            document.getElementById("editarPreviewIcon").innerHTML = `<i class="ti ${data.icono}"></i>`;


        if (editarListaIconos) editarListaIconos.innerHTML = "";
        if (editarTipoIcono) editarTipoIcono.value = "";

    };


    /* =====================================
       ENVÍO AJAX FORMULARIO
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

});