/* =====================================
   CONFIGURACIÓN GLOBAL DE ICONOS
===================================== */
const iconosConfig = {
    equipos:     ["ti-device-desktop", "ti-device-laptop", "ti-device-tablet", "ti-server"],
    componentes: ["ti-cpu", "ti-device-hdd", "ti-device-ssd", "ti-device-usb", "ti-device-sd-card"],
    perifericos: ["ti-mouse", "ti-keyboard", "ti-headphones", "ti-microphone", "ti-speaker"],
    pantallas:   ["ti-device-desktop", "ti-presentation", "ti-projector"],
    impresion:   ["ti-printer", "ti-printer-3d", "ti-copy"],
    red:         ["ti-router", "ti-network", "ti-wifi", "ti-antenna"]
};

/* =====================================
   FUNCIONES AUXILIARES
===================================== */
function mostrarToast(tipo, mensaje) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = document.getElementById("toastContainer");
    if (!container) return;

    const html = `
    <div class="toast align-items-center text-white ${colores[tipo] || 'bg-secondary'} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;

    container.insertAdjacentHTML("beforeend", html);
    const toast = new bootstrap.Toast(container.lastElementChild, { delay: 3500 });
    container.lastElementChild.addEventListener('hidden.bs.toast', function () { this.remove(); });
    toast.show();
}

function getResultado(res) {
    if (res === null || res === undefined) return "error";
    if (typeof res === "string") return res.trim();
    if (typeof res === "object") return String(res.resultado ?? "error").trim();
    return String(res).trim();
}

function getMensaje(res) {
    if (typeof res === "object" && res !== null) return res.mensaje || "";
    return "";
}

function generarIconos(tipo, contenedor, preview, inputHidden, iconoActual = null) {
    contenedor.innerHTML = "";
    if (!iconosConfig[tipo]) return;

    iconosConfig[tipo].forEach(icono => {
        const seleccionado = (icono === iconoActual) ? "border-primary bg-primary-lt" : "";
        const html = `
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

    /* --- MAYÚSCULAS en tiempo real --- */
    ["nuevaDescripcion", "editarDescripcion"].forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener("input", function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    });

    /* --- Iconos: Cambio de Categoría --- */
    const tipoIcono = document.getElementById("tipoIcono");
    if (tipoIcono) {
        tipoIcono.addEventListener("change", function () {
            generarIconos(this.value,
                document.getElementById("listaIconos"),
                document.getElementById("previewIcon"),
                document.getElementById("iconoActivo")
            );
        });
    }

    const editarTipoIcono = document.getElementById("editarTipoIcono");
    if (editarTipoIcono) {
        editarTipoIcono.addEventListener("change", function () {
            const inputIcono = document.getElementById("editarIconoActivo");
            generarIconos(this.value,
                document.getElementById("editarListaIconos"),
                document.getElementById("editarPreviewIcon"),
                inputIcono,
                inputIcono.value
            );
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
        const datos    = new FormData();
        datos.append("idActivo", idActivo);

        fetch("modules/inventario/ajax/tipoActivos.ajax.php", { method: "POST", body: datos })
            .then(res => res.json())
            .then(json => {
                if (json.resultado === "error") return mostrarToast("error", json.mensaje || "Error al cargar datos.");

                document.getElementById("editarIdActivo").value              = json.idTipoActivo;
                document.getElementById("editarDescripcion").value           = json.descripcion;
                document.getElementById("editarIconoActivo").value           = json.icono;
                document.getElementById("editarEsCompuesto").checked         = (json.esCompuesto  == 1);
                document.getElementById("editarEsComponente").checked        = (json.esComponente == 1);
                document.getElementById("editarEsPeriferico").checked        = (json.esPeriferico == 1);
                document.getElementById("editarUsuarioCreacion").textContent = json.idUsuarioRegistro;
                document.getElementById("editarFechaCreacion").textContent   = json.fechaCreacion;
                document.getElementById("editarPreviewIcon").innerHTML       = `<i class="ti ${json.icono}"></i>`;

                bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEditarActivo")).show();
            })
            .catch(() => mostrarToast("error", "Error al cargar datos."));
    });

    /* --- 2. FORMULARIO GUARDAR NUEVO --- */
    const formAgregar = document.getElementById("formNuevoActivo");
    if (formAgregar) {
        formAgregar.addEventListener("submit", function (e) {
            e.preventDefault();

            if (!document.getElementById("iconoActivo").value) {
                return mostrarToast("warning", "Selecciona un icono válido.");
            }

            const btn = this.querySelector('[type="submit"]');
            btn.disabled = true;

            fetch("modules/inventario/ajax/tipoActivos.ajax.php", { method: "POST", body: new FormData(this) })
                .then(res => res.json())
                .then(res => {
                    const r = getResultado(res);
                    const m = getMensaje(res);

                    if (r === "ok") {
                        bootstrap.Modal.getInstance(document.getElementById("modalAgregarActivo")).hide();
                        mostrarToast("success", "Tipo de activo guardado correctamente.");
                        setTimeout(() => location.reload(), 1500);
                    } else if (r === "error_duplicado") {
                        mostrarToast("warning", "¡Atención! Ya existe un tipo de activo con este nombre.");
                        btn.disabled = false;
                    } else {
                        mostrarToast("error", m || "Error al guardar: " + r);
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    mostrarToast("error", "Error de servidor.");
                    btn.disabled = false;
                });
        });
    }

    /* --- 3. FORMULARIO ACTUALIZAR (EDITAR) --- */
    const formEditar = document.getElementById("formEditarActivo");
    if (formEditar) {
        formEditar.addEventListener("submit", function (e) {
            e.preventDefault();

            const btn = this.querySelector('[type="submit"]');
            btn.disabled = true;

            fetch("modules/inventario/ajax/tipoActivos.ajax.php", { method: "POST", body: new FormData(this) })
                .then(res => res.json())
                .then(res => {
                    const r = getResultado(res);
                    const m = getMensaje(res);

                    if (r === "ok") {
                        bootstrap.Modal.getInstance(document.getElementById("modalEditarActivo")).hide();
                        mostrarToast("success", "Tipo de activo actualizado correctamente.");
                        setTimeout(() => location.reload(), 1500);
                    } else if (r === "error_duplicado") {
                        mostrarToast("warning", "¡Atención! El nombre ya existe en otro registro.");
                        btn.disabled = false;
                    } else {
                        mostrarToast("error", m || "No se pudo actualizar: " + r);
                        btn.disabled = false;
                    }
                })
                .catch(() => {
                    mostrarToast("error", "Error al comunicarse con el servidor.");
                    btn.disabled = false;
                });
        });
    }

    /* --- 4. ELIMINAR TIPO ACTIVO (lógico) --- */
    document.addEventListener("click", function (e) {
        const boton = e.target.closest(".btnEliminarActivo");
        if (!boton) return;

        const idActivo    = boton.getAttribute("data-id");
        const descripcion = boton.getAttribute("data-descripcion") || "este tipo de activo";

        document.getElementById("eliminarNombreActivo").textContent = descripcion;
        document.getElementById("confirmarEliminarActivo").setAttribute("data-id", idActivo);

        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalConfirmarEliminar")).show();
    });

    const btnConfirmar = document.getElementById("confirmarEliminarActivo");
    if (btnConfirmar) {
        btnConfirmar.addEventListener("click", function () {
            const idActivo = this.getAttribute("data-id");
            const datos    = new FormData();
            datos.append("eliminarIdActivo", idActivo);

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';

            fetch("modules/inventario/ajax/tipoActivos.ajax.php", { method: "POST", body: datos })
                .then(res => res.json())
                .then(json => {
                    bootstrap.Modal.getInstance(document.getElementById("modalConfirmarEliminar")).hide();
                    if (json.resultado === "ok") {
                        mostrarToast("success", json.mensaje || "Tipo de activo eliminado correctamente.");
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarToast("error", json.mensaje || "No se pudo eliminar el tipo de activo.");
                    }
                })
                .catch(() => mostrarToast("error", "Error al comunicarse con el servidor."))
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-trash me-1"></i>Sí, eliminar';
                });
        });
    }

    /* --- 5. DATATABLE --- */
    if ($.fn.DataTable.isDataTable('#tablaActivos')) {
        $('#tablaActivos').DataTable().destroy();
    }

    $('#tablaActivos').DataTable({
        "responsive": false, // Apagamos el responsive nativo de DT porque usaremos nuestro CSS de Tarjetas
        "pageLength": 10,
        "autoWidth": false,
        "language": {
            "search": "", // Quitamos el texto "Buscar:" para dejar solo el placeholder
            "lengthMenu": "Mostrar _MENU_",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ activos",
            "paginate": {
                "previous": "<i class='ti ti-chevron-left'></i>",
                "next": "<i class='ti ti-chevron-right'></i>"
            }
        },
        "dom": `
        <'card-body border-bottom py-3'
            <'row d-flex align-items-center gap-3 gap-md-0'
                <'col-12 col-md-4 d-flex justify-content-center justify-content-md-start'l>
                <'col-12 col-md-8 d-flex flex-column flex-md-row align-items-center justify-content-md-end gap-2'Bf>
            >
        >
        <'table-responsive'tr>
        <'card-footer d-flex flex-column flex-md-row align-items-center justify-content-between py-2'
            <'text-muted small mb-2 mb-md-0'i>
            <'m-0'p>
        >
        `,
        "buttons": [
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet me-1"></i> Excel', className: 'btn btn-outline-success btn-sm' },
            { extend: 'pdfHtml5',   text: '<i class="ti ti-file-description me-1"></i> PDF',  className: 'btn btn-outline-danger btn-sm' }
        ],
        "initComplete": function () {
            // Estilizar el input de búsqueda
            let searchInput = $('.dataTables_filter input');
            searchInput.addClass('form-control form-control-sm').attr('placeholder', 'Buscar activo...');
            $('.dataTables_filter').css('margin', '0'); // Resetear margen
            
            // Estilizar el select de cantidad
            $('.dataTables_length select').addClass('form-select form-select-sm w-auto');
        }
    });
});
