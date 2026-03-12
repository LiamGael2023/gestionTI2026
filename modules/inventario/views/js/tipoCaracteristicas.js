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

    /*==================================================
    EDITAR ACTIVO
    ==================================================*/
    // Usamos delegación de eventos con JavaScript puro
    document.addEventListener("click", function (e) {

        // Verificamos si el elemento clickeado es el botón o está dentro del botón
        const boton = e.target.closest(".btnEditarTipoCaracteristica");

        if (boton) {
            // Capturamos el ID usando getAttribute o dataset
            var idActivo = boton.getAttribute("data-id");
            console.log("1. ID enviado al servidor:", idActivo);

            let datos = new FormData();
            datos.append("idActivo", idActivo);

            fetch("modules/inventario/ajax/tipoCaracteristicas.ajax.php", {
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
                        document.getElementById("editarUsuarioCreacion").textContent = json.idUsuarioRegistro;

                        // Abrimos el modal
                        let modalElement = document.getElementById('modalEditarTipoCaracteristica');
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
   ENVÍO AJAX FORMULARIO AGREGAR TIPO
===================================== */
    const formTipo = document.getElementById("formNuevoTipoCaracteristica");

    if (formTipo) {
        formTipo.addEventListener("submit", function (e) {
            e.preventDefault();

            // Validamos que la descripción no esté vacía
            const descripcion = document.getElementById("nuevaDescripcion").value;
            if (descripcion.trim() === "") {
                mostrarToast("warning", "La descripción es obligatoria");
                return;
            }

            let datos = new FormData(formTipo);

            // Apuntamos al archivo AJAX de Tipo Característica
            fetch("modules/inventario/ajax/tipoCaracteristicas.ajax.php", {
                method: "POST",
                body: datos
            })
                .then(res => res.text())
                .then(respuesta => {
                    console.log("Respuesta servidor:", respuesta);

                    // Limpiamos la respuesta de comillas extras o espacios
                    let res = respuesta.trim().replace(/"/g, "");

                    if (res === "ok") {
                        let modalElement = document.getElementById("modalAgregarTipoCaracteristica");
                        let modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();

                        mostrarToast("success", "Tipo de característica guardado correctamente");

                        setTimeout(() => {
                            location.reload();
                        }, 1500);

                    } else {
                        let mensaje = (res === "error_duplicado") ? "La descripción ya existe" : res;
                        mostrarToast("error", "No se pudo guardar: " + mensaje);
                    }
                })
                .catch(error => {
                    console.error("Error AJAX:", error);
                    mostrarToast("error", "No se pudo contactar con el servidor");
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

    /* =====================================
       ENVÍO AJAX FORMULARIO EDITAR
    ===================================== */

    // $(document).ready(function () {
    //     $('#tablaTipoCaracteristicas').DataTable({
    //         "responsive": true,
    //         "pageLength": 10,
    //         "language": {
    //             "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" // Traducción completa
    //         },
    //         "dom": `
    //         <'card-body border-bottom py-2'
    //             <'d-flex align-items-center'
    //                 <'text-muted small'l>
    //                 <'ms-auto d-flex align-items-center gap-2'Bf>
    //             >
    //         >
    //         <'table-responsive'tr>
    //         <'card-footer d-flex align-items-center py-2'
    //             <'m-0 text-muted small'i>
    //             <'pagination m-0 ms-auto'p>
    //         >
    //     `,
    //         "buttons": [
    //             {
    //                 extend: 'excelHtml5',
    //                 text: '<i class="ti ti-file-spreadsheet"></i>',
    //                 className: 'btn btn-sm btn-icon btn-outline-success',
    //                 titleAttr: 'Excel',
    //                 exportOptions: { columns: [0, 1, 2] }
    //             }, // <-- Esta coma es obligatoria
    //             {
    //                 extend: 'pdfHtml5',
    //                 text: '<i class="ti ti-file-description"></i>',
    //                 className: 'btn btn-sm btn-icon btn-outline-danger ms-2', // ms-2 añade margen a la izquierda
    //                 titleAttr: 'PDF',
    //                 exportOptions: { columns: [0, 1, 2] }
    //             }
    //         ],
    //         "initComplete": function () {

    //             $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar...');
    //             $('.dataTables_length select').addClass('form-select form-select-sm');

    //             $('.dataTables_paginate .pagination').addClass('pagination-sm');
    //         }
    //     });
    // });
});