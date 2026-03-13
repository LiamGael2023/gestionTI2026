/* =====================================
   CONFIGURACIÓN GLOBAL DE ICONOS
===================================== */
const iconosConfig = {
    equipos: ["ti-device-desktop", "ti-device-laptop", "ti-device-tablet", "ti-server"],
    componentes: ["ti-cpu", "ti-device-hdd", "ti-device-ssd", "ti-device-usb", "ti-device-sd-card"],
    perifericos: ["ti-mouse", "ti-keyboard", "ti-headphones", "ti-microphone", "ti-speaker"],
    pantallas: ["ti-device-desktop", "ti-presentation", "ti-projector"],
    impresion: ["ti-printer", "ti-printer-3d", "ti-copy"],
    red: ["ti-router", "ti-network", "ti-wifi", "ti-antenna"]
};

/* =====================================
   FUNCIONES AUXILIARES
===================================== */
function mostrarToast(tipo, mensaje) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = document.getElementById("toastContainer");
    if (!container) return;

    const html = `
    <div class="toast align-items-center text-white ${colores[tipo]} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;

    container.insertAdjacentHTML("beforeend", html);
    const toast = new bootstrap.Toast(container.lastElementChild, { delay: 3500 });
    container.lastElementChild.addEventListener('hidden.bs.toast', function() { this.remove(); });
    toast.show();
}

function generarIconos(tipo, contenedor, preview, inputHidden, iconoActual = null) {
    contenedor.innerHTML = "";
    if (!iconosConfig[tipo]) return;

    iconosConfig[tipo].forEach(icono => {
        let seleccionado = (icono === iconoActual) ? "border-primary bg-primary-lt" : "";
        let html = `
        <div class="col-4 col-sm-3">
            <div class="card card-sm text-center icono-item ${seleccionado}" data-icon="${icono}" style="cursor:pointer">
                <div class="card-body p-2">
                    <i class="ti ${icono} fs-1 text-primary"></i>
                    <div class="text-muted" style="font-size:0.65rem">${icono.replace("ti-", "")}</div>
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
   DOM READY
===================================== */
document.addEventListener("DOMContentLoaded", function () {

    /* --- Lógica de Iconos (Cambio de Categoría) --- */
    const tipoIcono = document.getElementById("tipoIcono");
    if (tipoIcono) {
        tipoIcono.addEventListener("change", function () {
            generarIconos(this.value, document.getElementById("listaIconos"), document.getElementById("previewIcon"), document.getElementById("iconoActivo"));
        });
    }

    const editarTipoIcono = document.getElementById("editarTipoIcono");
    if (editarTipoIcono) {
        editarTipoIcono.addEventListener("change", function () {
            const inputIcono = document.getElementById("editarIconoActivo");
            generarIconos(this.value, document.getElementById("editarListaIconos"), document.getElementById("editarPreviewIcon"), inputIcono, inputIcono.value);
        });
    }

    /* --- Selección de Iconos (Click en Card) --- */
    document.addEventListener("click", function (e) {
        const item = e.target.closest(".icono-item");
        if (!item) return;

        const contenedor = item.closest(".row");
        contenedor.querySelectorAll(".icono-item").forEach(el => el.classList.remove("border-primary", "bg-primary-lt"));
        item.classList.add("border-primary", "bg-primary-lt");

        const icono = item.getAttribute("data-icon");
        const modal = item.closest(".modal");

        if (modal?.id === "modalAgregarActivo") {
            document.getElementById("previewIcon").innerHTML = `<i class="ti ${icono}"></i>`;
            document.getElementById("iconoActivo").value = icono;
        } else if (modal?.id === "modalEditarActivo") {
            document.getElementById("editarPreviewIcon").innerHTML = `<i class="ti ${icono}"></i>`;
            document.getElementById("editarIconoActivo").value = icono;
        }
    });

    /* --- 1. CARGAR DATOS EN EL MODAL EDITAR --- */
    document.addEventListener("click", function (e) {
        const boton = e.target.closest(".btnEditarActivo");
        if (!boton) return;

        const idActivo = boton.getAttribute("data-id");
        let datos = new FormData();
        datos.append("idActivo", idActivo);

        fetch("modules/inventario/ajax/activos.ajax.php", { method: "POST", body: datos })
            .then(res => res.json())
            .then(json => {
                if (json.error) return mostrarToast("error", json.error);

                document.getElementById("editarIdActivo").value = json.idActivos;
                document.getElementById("editarDescripcion").value = json.descripcion;
                document.getElementById("editarIconoActivo").value = json.icono;
                document.getElementById("editarCompuesto").checked = (json.compuesto == 1);
                document.getElementById("editarUsuarioCreacion").textContent = json.idUsuarioRegistro;
                document.getElementById("editarFechaCreacion").textContent = json.fechaCreacion;
                document.getElementById("editarPreviewIcon").innerHTML = `<i class="ti ${json.icono}"></i>`;

                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarActivo')).show();
            })
            .catch(() => mostrarToast("error", "Error al cargar datos."));
    });

    /* --- 2. FORMULARIO GUARDAR NUEVO --- */
    const formAgregar = document.getElementById("formNuevoActivo");
    if (formAgregar) {
        formAgregar.addEventListener("submit", function (e) {
            e.preventDefault();
            if (document.getElementById("iconoActivo").value === "ti ti-help") {
                return mostrarToast("warning", "Selecciona un icono válido.");
            }

            fetch("modules/inventario/ajax/activos.ajax.php", { method: "POST", body: new FormData(this) })
                .then(res => res.json())
                .then(res => {
                    const r = res.toString().trim();
                    if (r === "ok") {
                        bootstrap.Modal.getInstance(document.getElementById("modalAgregarActivo")).hide();
                        mostrarToast("success", "Activo guardado correctamente");
                        setTimeout(() => location.reload(), 1500);
                    } else if (r === "error_duplicado") {
                        mostrarToast("warning", "¡Atención! Ya existe un activo con este nombre.");
                    } else {
                        mostrarToast("error", "Error: " + r);
                    }
                })
                .catch(() => mostrarToast("error", "Error de servidor."));
        });
    }

    /* --- 3. FORMULARIO ACTUALIZAR (EDITAR) --- */
    const formEditar = document.getElementById("formEditarActivo");
    if (formEditar) {
        formEditar.addEventListener("submit", function (e) {
            e.preventDefault();

            fetch("modules/inventario/ajax/activos.ajax.php", { method: "POST", body: new FormData(this) })
                .then(res => res.json())
                .then(res => {
                    const r = res.toString().trim();
                    if (r === "ok") {
                        bootstrap.Modal.getInstance(document.getElementById("modalEditarActivo")).hide();
                        mostrarToast("success", "Activo actualizado correctamente");
                        setTimeout(() => location.reload(), 1500);
                    } else if (r === "error_duplicado") {
                        // Colores consistentes: warning para duplicados
                        mostrarToast("warning", "¡Atención! El nombre ya existe en otro registro.");
                    } else {
                        mostrarToast("error", "No se pudo actualizar: " + r);
                    }
                })
                .catch(() => mostrarToast("error", "Error al comunicarse con el servidor."));
        });
    }

    /* --- 4. CONFIGURACIÓN DATATABLE --- */
    if ($.fn.DataTable.isDataTable('#tablaActivos')) {
        $('#tablaActivos').DataTable().destroy();
    }

    $('#tablaActivos').DataTable({
        "responsive": true,
        "pageLength": 10,
        "autoWidth": false,
        "dom": `
        <'card-body border-bottom py-3'
            <'row g-3 align-items-center'
                <'col-12 col-md-auto d-flex justify-content-center justify-content-md-start'l>
                <'col-12 col-md-auto ms-auto'
                    <'d-flex flex-row flex-nowrap align-items-center justify-content-center justify-content-md-end gap-2'Bf>
                >
            >
        >
        <'table-responsive'tr>
        <'card-footer d-flex align-items-center py-2'
            <'m-0 text-muted small'i>
            <'pagination m-0 ms-auto'p>
        >
    `,
        "buttons": [
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-outline-success btn-sm m-0' },
            { extend: 'pdfHtml5', text: '<i class="ti ti-file-description"></i>', className: 'btn btn-outline-danger btn-sm m-0' }
        ],
        "initComplete": function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm m-0').attr('placeholder', 'Buscar...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dt-buttons').addClass('d-flex gap-2 m-0');
            $('#tablaActivos').addClass('text-nowrap');
        }
    });
});