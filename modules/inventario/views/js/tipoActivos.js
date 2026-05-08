/* =====================================================
   CONFIGURACIÓN DE ICONOS
===================================================== */
const iconosConfig = {
    equipos:     ["ti-device-desktop","ti-device-laptop","ti-device-tablet","ti-server"],
    componentes: ["ti-cpu","ti-device-hdd","ti-device-ssd","ti-device-usb","ti-device-sd-card"],
    perifericos: ["ti-mouse","ti-keyboard","ti-headphones","ti-microphone","ti-speaker"],
    pantallas:   ["ti-device-desktop","ti-presentation","ti-projector"],
    impresion:   ["ti-printer","ti-printer-3d","ti-copy"],
    red:         ["ti-router","ti-network","ti-wifi","ti-antenna"]
};

/* =====================================================
   HELPERS
===================================================== */
function mostrarToast(tipo, mensaje) {
    const colores = {
        success: "bg-success",
        error:   "bg-danger",
        warning: "bg-warning",
        info:    "bg-info"
    };
    const icono = tipo === "success" ? "ti-circle-check"
                : tipo === "error"   ? "ti-circle-x"
                : "ti-alert-circle";

    const container = document.getElementById("toastContainer");
    if (!container) return;

    container.insertAdjacentHTML("beforeend", `
      <div class="toast align-items-center text-white ${colores[tipo] || "bg-secondary"} border-0 mb-2" role="alert">
        <div class="d-flex">
          <div class="toast-body d-flex align-items-center gap-2">
            <i class="ti ${icono}"></i> ${mensaje}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto"
                  data-bs-dismiss="toast"></button>
        </div>
      </div>`);

    const el   = container.lastElementChild;
    const toast = new bootstrap.Toast(el, { delay: 3500 });
    el.addEventListener("hidden.bs.toast", () => el.remove());
    toast.show();
}

function getResultado(res) {
    if (res === null || res === undefined) return "error";
    if (typeof res === "string")           return res.trim();
    if (typeof res === "object")           return String(res.resultado ?? "error").trim();
    return String(res).trim();
}

function getMensaje(res) {
    return (typeof res === "object" && res !== null) ? (res.mensaje || "") : "";
}

function generarIconos(tipo, contenedor, preview, inputHidden, iconoActual = null) {
    contenedor.innerHTML = "";
    if (!iconosConfig[tipo]) return;

    iconosConfig[tipo].forEach(icono => {
        const sel = icono === iconoActual ? "border-primary bg-primary-lt" : "";
        contenedor.insertAdjacentHTML("beforeend", `
          <div class="col-4 col-sm-3">
            <div class="card card-sm text-center icono-item ${sel}" data-icon="${icono}"
                 style="cursor:pointer;transition:.15s;border-width:2px;">
              <div class="card-body p-2">
                <i class="ti ${icono} fs-2 text-primary d-block mb-1"></i>
                <div class="text-muted" style="font-size:.62rem;line-height:1.2">
                  ${icono.replace("ti-","")}
                </div>
              </div>
            </div>
          </div>`);
    });

    if (iconoActual) {
        preview.innerHTML  = `<i class="ti ${iconoActual}"></i>`;
        inputHidden.value  = iconoActual;
    }
}

/* =====================================================
   DOM READY
===================================================== */
document.addEventListener("DOMContentLoaded", function () {

    /* ── Mayúsculas en tiempo real ─────────────────── */
    ["nuevaDescripcion", "editarDescripcion"].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener("input", function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    });

    /* ── Iconos: selección de categoría (Agregar) ──── */
    document.getElementById("tipoIcono")?.addEventListener("change", function () {
        generarIconos(
            this.value,
            document.getElementById("listaIconos"),
            document.getElementById("previewIcon"),
            document.getElementById("iconoActivo")
        );
    });

    /* ── Iconos: selección de categoría (Editar) ───── */
    document.getElementById("editarTipoIcono")?.addEventListener("change", function () {
        const inp = document.getElementById("editarIconoActivo");
        generarIconos(
            this.value,
            document.getElementById("editarListaIconos"),
            document.getElementById("editarPreviewIcon"),
            inp,
            inp.value
        );
    });

    /* ── Click en icono ─────────────────────────────── */
    document.addEventListener("click", function (e) {
        const item = e.target.closest(".icono-item");
        if (!item) return;

        const row = item.closest(".row");
        row.querySelectorAll(".icono-item").forEach(el => {
            el.classList.remove("border-primary", "bg-primary-lt");
            el.style.transform = "";
        });
        item.classList.add("border-primary", "bg-primary-lt");
        item.style.transform = "scale(1.05)";
        setTimeout(() => { item.style.transform = ""; }, 200);

        const icono = item.getAttribute("data-icon");
        const modal = item.closest(".modal");

        if (modal?.id === "modalAgregarActivo") {
            document.getElementById("previewIcon").innerHTML = `<i class="ti ${icono}"></i>`;
            document.getElementById("iconoActivo").value     = icono;
        } else if (modal?.id === "modalEditarActivo") {
            document.getElementById("editarPreviewIcon").innerHTML = `<i class="ti ${icono}"></i>`;
            document.getElementById("editarIconoActivo").value     = icono;
        }
    });

    /* ── Reset modal Agregar ────────────────────────── */
    document.getElementById("modalAgregarActivo")
        ?.addEventListener("hidden.bs.modal", function () {
            document.getElementById("formNuevoActivo").reset();
            document.getElementById("listaIconos").innerHTML =
                '<div class="col-12 text-center text-muted small py-2">' +
                '<i class="ti ti-arrow-up me-1"></i> Seleccione una categoría</div>';
            document.getElementById("previewIcon").innerHTML = '<i class="ti ti-help"></i>';
            document.getElementById("iconoActivo").value = "";
        });

    /* ══════════════════════════════════════════════════
       1. CARGAR DATOS EN MODAL EDITAR
    ══════════════════════════════════════════════════ */
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btnEditarActivo");
        if (!btn) return;

        const datos = new FormData();
        datos.append("idActivo", btn.getAttribute("data-id"));

        fetch("modules/inventario/ajax/tipoActivos.ajax.php", { method: "POST", body: datos })
            .then(r => r.json())
            .then(json => {
                if (json.resultado === "error")
                    return mostrarToast("error", json.mensaje || "Error al cargar datos.");

                document.getElementById("editarIdActivo").value              = json.idTipoActivo;
                document.getElementById("editarDescripcion").value           = json.descripcion;
                document.getElementById("editarIconoActivo").value           = json.icono;
                document.getElementById("editarEsCompuesto").checked         = json.esCompuesto  == 1;
                document.getElementById("editarEsComponente").checked        = json.esComponente == 1;
                document.getElementById("editarEsPeriferico").checked        = json.esPeriferico == 1;
                document.getElementById("editarUsuarioCreacion").textContent = json.nombreUsuario || json.idUsuarioRegistro;
                document.getElementById("editarFechaCreacion").textContent   = json.fechaCreacion;
                document.getElementById("editarPreviewIcon").innerHTML       = `<i class="ti ${json.icono}"></i>`;

                bootstrap.Modal.getOrCreateInstance(
                    document.getElementById("modalEditarActivo")
                ).show();
            })
            .catch(() => mostrarToast("error", "Error al cargar datos."));
    });

    /* ══════════════════════════════════════════════════
       2. GUARDAR NUEVO
    ══════════════════════════════════════════════════ */
    document.getElementById("formNuevoActivo")
        ?.addEventListener("submit", function (e) {
            e.preventDefault();

            if (!document.getElementById("iconoActivo").value)
                return mostrarToast("warning", "Selecciona un icono.");

            const btn = this.querySelector('[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

            fetch("modules/inventario/ajax/tipoActivos.ajax.php",
                  { method: "POST", body: new FormData(this) })
                .then(r => r.json())
                .then(res => {
                    const r = getResultado(res);
                    if (r === "ok") {
                        bootstrap.Modal.getInstance(
                            document.getElementById("modalAgregarActivo")
                        ).hide();
                        mostrarToast("success", "Tipo de activo guardado correctamente.");
                        setTimeout(() => location.reload(), 1500);
                    } else if (r === "error_duplicado") {
                        mostrarToast("warning", "Ya existe un tipo de activo con este nombre.");
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Tipo';
                    } else {
                        mostrarToast("error", getMensaje(res) || "Error al guardar.");
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Tipo';
                    }
                })
                .catch(() => {
                    mostrarToast("error", "Error de servidor.");
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Tipo';
                });
        });

    /* ══════════════════════════════════════════════════
       3. GUARDAR EDICIÓN
    ══════════════════════════════════════════════════ */
    document.getElementById("formEditarActivo")
        ?.addEventListener("submit", function (e) {
            e.preventDefault();

            const btn = this.querySelector('[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

            fetch("modules/inventario/ajax/tipoActivos.ajax.php",
                  { method: "POST", body: new FormData(this) })
                .then(r => r.json())
                .then(res => {
                    const r = getResultado(res);
                    if (r === "ok") {
                        bootstrap.Modal.getInstance(
                            document.getElementById("modalEditarActivo")
                        ).hide();
                        mostrarToast("success", "Tipo de activo actualizado correctamente.");
                        setTimeout(() => location.reload(), 1500);
                    } else if (r === "error_duplicado") {
                        mostrarToast("warning", "¡Atención! El nombre ya existe en otro registro.");
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Cambios';
                    } else {
                        mostrarToast("error", getMensaje(res) || "No se pudo actualizar.");
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Cambios';
                    }
                })
                .catch(() => {
                    mostrarToast("error", "Error al comunicarse con el servidor.");
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>Guardar Cambios';
                });
        });

    /* ══════════════════════════════════════════════════
       4. ELIMINAR
    ══════════════════════════════════════════════════ */
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btnEliminarActivo");
        if (!btn) return;
        document.getElementById("eliminarNombreActivo").textContent =
            btn.getAttribute("data-descripcion") || "este tipo de activo";
        document.getElementById("confirmarEliminarActivo")
            .setAttribute("data-id", btn.getAttribute("data-id"));
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById("modalConfirmarEliminar")
        ).show();
    });

    document.getElementById("confirmarEliminarActivo")
        ?.addEventListener("click", function () {
            const datos = new FormData();
            datos.append("eliminarIdActivo", this.getAttribute("data-id"));

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';

            fetch("modules/inventario/ajax/tipoActivos.ajax.php",
                  { method: "POST", body: datos })
                .then(r => r.json())
                .then(json => {
                    bootstrap.Modal.getInstance(
                        document.getElementById("modalConfirmarEliminar")
                    ).hide();
                    if (json.resultado === "ok") {
                        mostrarToast("success", json.mensaje || "Tipo de activo eliminado.");
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarToast("error", json.mensaje || "No se pudo eliminar.");
                    }
                })
                .catch(() => mostrarToast("error", "Error al comunicarse con el servidor."))
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="ti ti-trash me-1"></i>Sí, eliminar';
                });
        });

    /* ══════════════════════════════════════════════════
       5. BUSCADOR + PAGINACION MOVIL
          5 items por pagina, filtra por nombre,
          botones Anterior / Siguiente con contador.
    ══════════════════════════════════════════════════ */
    (function () {
        const PER_PAGE = 5;
        let currentPage = 1;
        let filtered    = [];   // items visibles segun busqueda

        const allItems  = () => Array.from(document.querySelectorAll("#mobileList .mobile-item"));
        const noRes     = document.getElementById("mobileNoResults");
        const pageInfo  = document.getElementById("mobilePageInfo");
        const prevBtn   = document.getElementById("mobilePrevBtn");
        const nextBtn   = document.getElementById("mobileNextBtn");
        const pagination= document.getElementById("mobilePagination");

        function render() {
            const total     = filtered.length;
            const totalPages= Math.max(1, Math.ceil(total / PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * PER_PAGE;
            const end   = start + PER_PAGE;

            // Ocultar todos, mostrar solo los de la pagina actual
            allItems().forEach(item => { item.style.display = "none"; });
            filtered.forEach((item, i) => {
                item.style.display = (i >= start && i < end) ? "" : "none";
            });

            // Info
            if (total === 0) {
                if (pageInfo)   pageInfo.textContent = "";
                if (noRes)      noRes.classList.remove("d-none");
                if (pagination) pagination.style.display = "none";
            } else {
                const from = start + 1;
                const to   = Math.min(end, total);
                if (pageInfo)   pageInfo.textContent = "Mostrando " + from + "-" + to + " de " + total;
                if (noRes)      noRes.classList.add("d-none");
                if (pagination) pagination.style.display = "";
            }

            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
        }

        function applyFilter(q) {
            currentPage = 1;
            filtered = allItems().filter(item => {
                const nombre = item.getAttribute("data-nombre") || "";
                return !q || nombre.includes(q.toLowerCase().trim());
            });
            render();
        }

        // Busqueda
        document.getElementById("mobileSearch")
            ?.addEventListener("input", function () { applyFilter(this.value); });

        // Botones
        prevBtn?.addEventListener("click", function () {
            if (currentPage > 1) { currentPage--; render(); }
        });
        nextBtn?.addEventListener("click", function () {
            const totalPages = Math.ceil(filtered.length / PER_PAGE);
            if (currentPage < totalPages) { currentPage++; render(); }
        });

        // Inicializar con todos los items visibles
        applyFilter("");
    }());

    /* ══════════════════════════════════════════════════
       6. DATATABLE  (solo desktop, dentro del div d-md-block)
          La tabla está en un card SEPARADO de los tabs,
          así que el wrapper que DataTables genera nunca
          sube sobre los tabs.
    ══════════════════════════════════════════════════ */
    if (!document.getElementById("tablaActivos")) return;

    if ($.fn.DataTable.isDataTable("#tablaActivos"))
        $("#tablaActivos").DataTable().destroy();

    const colNames = ["Nombre", "Tipo", "Compuesto", "Componente", "Registro"];

    const dt = $("#tablaActivos").DataTable({
        responsive: false,
        pageLength: 10,
        autoWidth:  false,
        dom:
            `<'d-none'lBf>` +
            `<'table-responsive'tr>` +
            `<'card-footer d-flex align-items-center py-2'` +
                `<'text-muted small'i>` +
                `<'pagination m-0 ms-auto'p>>`,
        language: {
            info:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty:    "Sin registros disponibles",
            infoFiltered: "(filtrado de _MAX_ registros)",
            lengthMenu:   "Mostrar _MENU_ registros",
            zeroRecords:  "No se encontraron resultados",
            search:       "Buscar:",
            paginate: {
                first:    "«",
                last:     "»",
                next:     "Siguiente →",
                previous: "← Anterior"
            }
        },
        buttons: [
            { extend: "excelHtml5", text: "Excel" },
            { extend: "pdfHtml5",   text: "PDF"   }
        ],
        columnDefs: [
            { targets: 5, orderable: false }
        ]
    });

    /* — Buscador desktop — */
    document.getElementById("dtSearch")
        ?.addEventListener("input", function () {
            dt.search(this.value).draw();
        });

    /* — Page length — */
    document.getElementById("dtPageLength")
        ?.addEventListener("change", function () {
            dt.page.len(parseInt(this.value)).draw();
        });

    /* — Excel / PDF — */
    document.getElementById("dtBtnExcel")
        ?.addEventListener("click", () => dt.button(".buttons-excel").trigger());
    document.getElementById("dtBtnPdf")
        ?.addEventListener("click", () => dt.button(".buttons-pdf").trigger());

    /* — Visibilidad de columnas — */
    const colMenu = document.getElementById("dtColMenu");
    if (colMenu) {
        colNames.forEach((name, idx) => {
            const wrap = document.createElement("div");
            wrap.className = "form-check mb-1";
            wrap.innerHTML = `
              <input class="form-check-input" type="checkbox" id="col_${idx}" checked>
              <label class="form-check-label small" for="col_${idx}">${name}</label>`;
            colMenu.appendChild(wrap);
            wrap.querySelector("input").addEventListener("change", function () {
                dt.column(idx).visible(this.checked);
            }); //Modificación
        });
    }

});