/* ===========================================================
    tipoCaracteristicas.js - CORREGIDO
   =========================================================== */

/* --- 0. UTILIDADES GLOBALES (Fuera para evitar ReferenceError) --- */

function ensureToastContainer() {
  let container = document.getElementById("toastContainerTipoCaracteristica");
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainerTipoCaracteristica';
    container.className = 'position-fixed bottom-0 end-0 p-3';
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
    // Detecta tanto "status" como "resultado"
    if (raw.status) return String(raw.status).trim();
    if (raw.resultado) return String(raw.resultado).trim();
    return JSON.stringify(raw);
  }
  return String(raw).trim();
}

/* --- FIN DE UTILIDADES --- */

(function () {
  'use strict';

  /* =====================================
      1. INICIALIZACIÓN DE DATATABLES
  ===================================== */
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
      try {
        $('#tablaTipoCaracteristicas').DataTable({
          responsive: true,
          autoWidth: false,
          pageLength: 10,
          order: [[0, "asc"]],
          columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }],
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
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-sm btn-icon btn-outline-success', titleAttr: 'Exportar a Excel', exportOptions: { columns: [0, 1] } },
            { extend: 'pdfHtml5', text: '<i class="ti ti-file-description"></i>', className: 'btn btn-sm btn-icon btn-outline-danger ms-2', titleAttr: 'Exportar a PDF', exportOptions: { columns: [0, 1] } }
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

          const r = res.toString().trim();

          if (r === "ok") {

            bootstrap.Modal
              .getInstance(document.getElementById("modalAgregarTipoCaracteristica"))
              .hide();

            mostrarToast("success", "Tipo de característica guardado correctamente");

            setTimeout(() => location.reload(), 1500);

          }
          else if (r === "error_duplicado") {

            mostrarToast("warning", "¡Atención! La descripción ya existe.");

          }
          else {

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

    document.addEventListener('click', function (e) {
      const boton = e.target.closest('.btnEditarTipoCaracteristica');
      if (!boton) return;

      let id = boton.dataset.id || boton.getAttribute('data-id');
      id = id.replace(/['"]/g, '').trim();

      const datos = new FormData();
      datos.append('idTipoCaracteristica', id);

      fetch('modules/inventario/ajax/tipoCaracteristicas.ajax.php', { method: 'POST', body: datos })
        .then(res => res.text())
        .then(text => {
          let parsed;
          try {
            const match = text.match(/\{.*\}/s);
            parsed = JSON.parse(match ? match[0] : text);
          } catch (err) {
            parsed = text.toString().trim();
          }

          const inputId = document.getElementById('editarIdTipoCaracteristica');
          const inputDesc = document.getElementById('editarDescripcion');
          const viewUser = document.getElementById('editarUsuarioCreacion');
          const viewFecha = document.getElementById('editarFechaCreacion');

          if (inputId && inputDesc) {
            inputId.value = parsed.idTipoCaracteristica ?? '';
            inputDesc.value = parsed.descripcion ?? '';

            // CORREGIDO: Nombres de campos según tu respuesta RAW
            if (viewUser) viewUser.textContent = parsed.idUsuarioRegistro ?? 'N/A';
            if (viewFecha) viewFecha.textContent = parsed.fechaCreacion ?? 'N/A';

            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
          }
        });
    });

    const formEditar = document.getElementById('formEditarTipoCaracteristica');
    if (formEditar) {
      formEditar.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch('modules/inventario/ajax/tipoCaracteristicas.ajax.php', { method: 'POST', body: new FormData(formEditar) })
          .then(res => res.text())
          .then(text => {
            let parsed;
            try {
              const match = text.match(/\{.*\}/s);
              parsed = JSON.parse(match ? match[0] : text);
            } catch (err) {
              parsed = text.toString().trim();
            }

            const r = normalizarRespuesta(parsed);

            if (r === 'ok') {
              const instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
              instance.hide();
              mostrarToast('success', 'Actualizado correctamente');
              setTimeout(() => location.reload(), 1200);
            } else if (r === 'error_duplicado') {
              mostrarToast('warning', '¡Atención! Este registro ya existe.');
            } else {
              mostrarToast('error', 'Error al actualizar');
            }
          });
      });
    }
  })();

  window.tipoCaracteristicasUtils = { mostrarToast, normalizarRespuesta };

})();