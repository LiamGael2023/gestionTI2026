/* =====================================
   1. INICIALIZACIÓN DE DATATABLES
===================================== */
$(document).ready(function () {
    $('#tablaTipoCaracteristicas').DataTable({
        "responsive": true,
        "autoWidth": false, // CRUCIAL: Evita que DataTables rompa el diseño en móviles
        "pageLength": 10,
        "order": [[0, "asc"]], // Ordenar por la columna 0 (Descripción)
        "columnDefs": [
            // El Nombre (0) y las Acciones (-1) siempre serán visibles en celular
            { "responsivePriority": 1, "targets": 0 },
            { "responsivePriority": 2, "targets": -1 }
        ],
        // Estructura DOM adaptada para Tabler y Bootstrap 5 (con Row/Col para evitar desbordes)
        "dom": `
            <'card-body border-bottom py-2'
                <'row align-items-center'
                    <'col-md-6 col-12 text-muted small mb-2 mb-md-0'l>
                    <'col-md-6 col-12 d-flex align-items-center justify-content-md-end justify-content-between gap-2'Bf>
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
                titleAttr: 'Exportar a Excel',
                exportOptions: { columns: [0, 1] } // Exporta solo Nombre y Fecha
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="ti ti-file-description"></i>',
                className: 'btn btn-sm btn-icon btn-outline-danger ms-2',
                titleAttr: 'Exportar a PDF',
                exportOptions: { columns: [0, 1] } // Exporta solo Nombre y Fecha
            }
        ],
        "initComplete": function () {
            // Clases de Tabler para los inputs generados por DataTables
            $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_paginate .pagination').addClass('pagination-sm m-0');
        }
    });
});

/* =====================================
   2. FUNCIONALIDAD DE ALERTAS (TOAST)
===================================== */
function mostrarToast(tipo, mensaje) {
    const colores = {
        success: "bg-success",
        error: "bg-danger",
        warning: "bg-warning",
        info: "bg-info"
    };

    const html = `
    <div class="toast align-items-center text-white ${colores[tipo]} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>`;

    const container = document.getElementById("toastContainer");

    if (!container) {
        console.error("No existe #toastContainer en el HTML. Agrégalo en el footer.");
        return;
    }

    container.insertAdjacentHTML("beforeend", html);
    const elementoToast = container.lastElementChild;
    const toast = new bootstrap.Toast(elementoToast, { delay: 3500 });

    elementoToast.addEventListener('hidden.bs.toast', () => {
        elementoToast.remove(); // Limpia el HTML para no saturar el DOM
    });

    toast.show();
}

/* =====================================
   3. GUARDAR NUEVO TIPO CARACTERÍSTICA
===================================== */
const formTipo = document.getElementById("formNuevoTipoCaracteristica");

if (formTipo) {
    formTipo.addEventListener("submit", function (e) {
        e.preventDefault();

        // 1. Validación básica de cliente
        const descripcion = document.getElementById("nuevaDescripcion").value;
        if (descripcion.trim() === "") {
            return mostrarToast("warning", "La descripción es obligatoria");
        }

        let datos = new FormData(formTipo);

        // 2. Envío al servidor
        fetch("modules/inventario/ajax/tipoCaracteristicas.ajax.php", {
            method: "POST",
            body: datos
        })
            .then(res => res.json()) // Usamos .json() para consistencia con activos
            .then(res => {
                // Limpiamos la respuesta (quitamos comillas y espacios)
                const respuesta = res.toString().trim();

                if (respuesta === "ok") {
                    // ÉXITO (Verde)
                    const modalElement = document.getElementById("modalAgregarTipoCaracteristica");
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();

                    mostrarToast("success", "Guardado correctamente");
                    setTimeout(() => { location.reload(); }, 1500);

                } else if (respuesta === "error_duplicado") {
                    // DUPLICADO (Amarillo/Naranja - Mismo que en Activos)
                    mostrarToast("warning", "¡Atención! La descripción ya existe.");

                } else {
                    // ERROR DE SISTEMA (Rojo)
                    mostrarToast("error", "No se pudo guardar: " + respuesta);
                }
            })
            .catch(error => {
                console.error("Error AJAX:", error);
                mostrarToast("error", "No se pudo contactar con el servidor");
            });
    });
}

/* --- CARGAR DATOS EN EL MODAL --- */
document.addEventListener("click", function (e) {
    const boton = e.target.closest(".btnEditarTipoCaracteristica");
    if (!boton) return;

    const idTipoCaracteristica = boton.getAttribute("data-id");
    let datos = new FormData();
    datos.append("idTipoCaracteristica", idTipoCaracteristica);

    console.log("1. Enviando ID al servidor:", idTipoCaracteristica);

    fetch("modules/inventario/ajax/tipoCaracteristicas.ajax.php", { method: "POST", body: datos })
        .then(res => res.text()) // Lo pedimos como texto para poder ver los errores de PHP
        .then(texto => {
            
            console.log("2. Respuesta cruda del servidor:", texto); // AQUÍ VEREMOS EL PROBLEMA REAL

            try {
                // Intentamos convertirlo a JSON
                const json = JSON.parse(texto);
                
                if (json.error) return mostrarToast("error", json.error);

                // Llenar campos
                document.getElementById("editarIdTipoCaracteristica").value = json.idTipoCaracteristica;
                document.getElementById("editarDescripcion").value = json.descripcion;
                
                // Llenar Auditoría
                document.getElementById("viewUserCrea").textContent = json.idUsuarioRegistro || 'N/A';
                document.getElementById("viewFechaCrea").textContent = json.fechaCreacion || 'N/A';

                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarTipoCaracteristica')).show();
            } catch (error) {
                console.error("3. Error de JS: El servidor no devolvió un JSON válido. Mira la respuesta cruda arriba.");
                mostrarToast("error", "Error en el servidor. Revisa la consola (F12).");
            }
        })
        .catch((err) => {
            console.error("Error de Fetch:", err);
            mostrarToast("error", "Error de red al cargar datos.");
        });
});

/* --- ENVIAR FORMULARIO DE EDICIÓN --- */
const formEditarTipo = document.getElementById("formEditarTipoCaracteristica");
if (formEditarTipo) {
    formEditarTipo.addEventListener("submit", function (e) {
        e.preventDefault();

        fetch("modules/inventario/ajax/tipoCaracteristicas.ajax.php", {
            method: "POST",
            body: new FormData(this)
        })
            .then(res => res.json())
            .then(res => {
                const r = res.toString().trim();
                if (r === "ok") {
                    bootstrap.Modal.getInstance(document.getElementById("modalEditarTipoCaracteristica")).hide();
                    mostrarToast("success", "Actualizado correctamente");
                    setTimeout(() => location.reload(), 1500);
                } else if (r === "error_duplicado") {
                    // Color warning para duplicados, igual que en Activos
                    mostrarToast("warning", "¡Atención! Este tipo de característica ya existe.");
                } else {
                    mostrarToast("error", "Error: " + r);
                }
            })
            .catch(() => mostrarToast("error", "Error en el servidor."));
    });
}