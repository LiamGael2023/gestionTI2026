/* ===========================================================
    tipoCaracteristicas.js
   =========================================================== */

/* --- 0. UTILIDADES GLOBALES --- */

function ensureToastContainer() {
    let container = document.getElementById("toastContainerTipoCaracteristica");
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainerTipoCaracteristica';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '10800';
        document.body.appendChild(container);
    }
    return container;
}

function mostrarToast(tipo, mensaje) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = ensureToastContainer();
    const colorClass = colores[tipo] || colores.info;

    const html = `
    <div class="toast align-items-center text-white ${colorClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>`;

    container.insertAdjacentHTML("beforeend", html);
    const elementoToast = container.lastElementChild;

    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const toast = new bootstrap.Toast(elementoToast, { delay: 3500 });
        elementoToast.addEventListener('hidden.bs.toast', () => elementoToast.remove());
        toast.show();
    }
}

function normalizarRespuesta(raw) {
    if (raw === null || raw === undefined) return null;
    if (typeof raw === 'string') return raw.trim();
    if (typeof raw === 'object') {
        if (raw.resultado) return String(raw.resultado).trim();
        if (raw.status)    return String(raw.status).trim();
        return JSON.stringify(raw);
    }
    return String(raw).trim();
}

/* --- FIN UTILIDADES --- */

(function () {
    'use strict';

    /* =====================================
        1. DATATABLES
    ===================================== */
    document.addEventListener('DOMContentLoaded', function () {

        /* --- MAYÚSCULAS en tiempo real --- */
        ["nuevaDescripcion", "editarDescripcion"].forEach(function (id) {
            const input = document.getElementById(id);
            if (!input) return;
            input.addEventListener("input", function () {
                const pos = this.selectionStart;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(pos, pos);
            });
        });

        if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
            try {
                $('#tablaTipoCaracteristicas').DataTable({
                    responsive: true,
                    autoWidth: false,
                    pageLength: 10,
                    order: [[0, "asc"]],
                    columnDefs: [
                        { responsivePriority: 1, targets: 0 },
                        { responsivePriority: 2, targets: -1 }
                    ],
                    dom: `
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
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: '<i class="ti ti-file-spreadsheet"></i>',
                            className: 'btn btn-sm btn-icon btn-outline-success',
                            titleAttr: 'Exportar a Excel',
                            exportOptions: { columns: [0, 1] }
                        },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="ti ti-file-description"></i>',
                            className: 'btn btn-sm btn-icon btn-outline-danger ms-2',
                            titleAttr: 'Exportar a PDF',
                            exportOptions: { columns: [0, 1] }
                        }
                    ],
                    initComplete: function () {
                        $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar...');
                        $('.dataTables_length select').addClass('form-select form-select-sm');
                        $('.dataTables_paginate .pagination').addClass('pagination-sm m-0');
                    }
                });
            } catch (err) {
                console.error('Error inicializando DataTable tipos:', err);
            }
        }
    });

    /* =====================================
        2. CREAR NUEVO TIPO CARACTERÍSTICA
    ===================================== */
    const formAgregarTipo = document.getElementById("formNuevoTipoCaracteristica");

    if (formAgregarTipo) {
        formAgregarTipo.addEventListener("submit", function (e) {
            e.preventDefault();

            fetch("modules/inventario/ajax/tipoCaracteristicas.ajax.php", {
                method: "POST",
                body: new FormData(this)
            })
                .then(res => res.json())
                .then(res => {
                    const r = normalizarRespuesta(res);

                    if (r === "ok") {
                        bootstrap.Modal
                            .getInstance(document.getElementById("modalAgregarTipoCaracteristica"))
                            .hide();
                        mostrarToast("success", "Tipo de característica guardado correctamente.");
                        setTimeout(() => location.reload(), 1500);
                    } else if (r === "error_duplicado") {
                        mostrarToast("warning", "¡Atención! La descripción ya existe.");
                    } else {
                        mostrarToast("error", "Error: " + r);
                    }
                })
                .catch(() => mostrarToast("error", "Error de servidor."));
        });
    }

    /* =====================================
        3. CARGAR DATOS Y EDITAR
    ===================================== */
    (function initEditar() {
        const modalEl = document.getElementById('modalEditarTipoCaracteristica');
        if (modalEl && modalEl.parentElement !== document.body) document.body.appendChild(modalEl);

        // Cargar datos en el modal
        document.addEventListener('click', function (e) {
            const boton = e.target.closest('.btnEditarTipoCaracteristica');
            if (!boton) return;

            const id = String(boton.dataset.id || boton.getAttribute('data-id')).replace(/['"]/g, '').trim();
            const datos = new FormData();
            datos.append('idTipoCaracteristica', id);

            fetch('modules/inventario/ajax/tipoCaracteristicas.ajax.php', { method: 'POST', body: datos })
                .then(res => res.json())
                .then(json => {
                    if (json.status === "error") {
                        return mostrarToast("error", json.message || "No se encontró el registro.");
                    }

                    document.getElementById('editarIdTipoCaracteristica').value  = json.idTipoCaracteristica ?? '';
                    document.getElementById('editarDescripcion').value           = json.descripcion ?? '';
                    document.getElementById('editarUsuarioCreacion').textContent = json.idUsuarioRegistro ?? 'N/A';
                    document.getElementById('editarFechaCreacion').textContent   = json.fechaCreacion ?? 'N/A';

                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                })
                .catch(() => mostrarToast("error", "Error al cargar los datos."));
        });

        // Guardar cambios
        const formEditar = document.getElementById('formEditarTipoCaracteristica');
        if (formEditar) {
            formEditar.addEventListener('submit', function (e) {
                e.preventDefault();

                fetch('modules/inventario/ajax/tipoCaracteristicas.ajax.php', { method: 'POST', body: new FormData(formEditar) })
                    .then(res => res.json())
                    .then(res => {
                        const r = normalizarRespuesta(res);

                        if (r === 'ok') {
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                            mostrarToast('success', 'Tipo de característica actualizado correctamente.');
                            setTimeout(() => location.reload(), 1200);
                        } else if (r === 'error_duplicado') {
                            mostrarToast('warning', '¡Atención! Esta descripción ya existe en otro registro.');
                        } else {
                            mostrarToast('error', 'Error al actualizar: ' + r);
                        }
                    })
                    .catch(() => mostrarToast("error", "Error al comunicarse con el servidor."));
            });
        }
    })();

    /* =====================================
        4. ELIMINAR TIPO CARACTERÍSTICA (lógico)
    ===================================== */
    document.addEventListener("click", function (e) {
        const boton = e.target.closest(".btnEliminarTipoCaracteristica");
        if (!boton) return;

        const id          = boton.getAttribute("data-id");
        const descripcion = boton.getAttribute("data-descripcion") || "este registro";

        document.getElementById("eliminarNombreTipo").textContent = descripcion;
        document.getElementById("confirmarEliminarTipo").setAttribute("data-id", id);

        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalConfirmarEliminarTipo")).show();
    });

    const btnConfirmar = document.getElementById("confirmarEliminarTipo");
    if (btnConfirmar) {
        btnConfirmar.addEventListener("click", function () {
            const id    = this.getAttribute("data-id");
            const datos = new FormData();
            datos.append("eliminarIdTipoCaracteristica", id);

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';

            fetch("modules/inventario/ajax/tipoCaracteristicas.ajax.php", { method: "POST", body: datos })
                .then(res => res.json())
                .then(json => {
                    bootstrap.Modal.getInstance(document.getElementById("modalConfirmarEliminarTipo")).hide();

                    if (json.resultado === "ok") {
                        mostrarToast("success", json.mensaje || "Eliminado correctamente.");
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
    }

    window.tipoCaracteristicasUtils = { mostrarToast, normalizarRespuesta };

})();
