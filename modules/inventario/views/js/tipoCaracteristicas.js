/* ===========================================================
   tipoCaracteristicas.js
   Archivo completo: DataTables, Toasts, Crear, Editar, Carga
   Reemplaza/pega este archivo después de cargar bootstrap.bundle.min.js
   y de las librerías DataTables/jQuery si las usas.
   =========================================================== */

/* =====================================
   0. CONFIGURACIÓN Y UTILIDADES GLOBALES
===================================== */
(function () {
  'use strict';

  // Asegura que exista el contenedor de toasts
  function ensureToastContainer() {
    let container = document.getElementById("toastContainer");
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'position-fixed bottom-0 end-0 p-3';
      container.style.zIndex = '10800';
      document.body.appendChild(container);
      console.log('Se creó #toastContainer automáticamente.');
    }
    return container;
  }

  // Mostrar toast (usa Bootstrap Toast)
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

    if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
      console.error('Bootstrap Toast no disponible');
      return;
    }

    const toast = new bootstrap.Toast(elementoToast, { delay: 3500 });
    elementoToast.addEventListener('hidden.bs.toast', () => elementoToast.remove());
    toast.show();
  }

  // Normalizar respuesta del servidor: acepta "ok", "error_duplicado", o {status:..., message:...}
  function normalizarRespuesta(raw) {
    if (raw === null || raw === undefined) return null;
    if (typeof raw === 'string') return raw.trim();
    if (typeof raw === 'object') {
      // Si viene {status: "ok", message: "ok"} o similar
      if (raw.status) return String(raw.status).trim();
      // Si viene {resultado: "ok"} (por SP antiguo)
      if (raw.resultado) return String(raw.resultado).trim();
      // Si el objeto no tiene status, devolver JSON string
      return JSON.stringify(raw);
    }
    return String(raw).trim();
  }

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
     - Form: #formNuevoTipoCaracteristica
     - Input: #nuevaDescripcion (name="nuevaDescripcion")
  ===================================== */
  (function initCrear() {
    const form = document.getElementById('formNuevoTipoCaracteristica');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const descripcionEl = document.getElementById('nuevaDescripcion');
      const descripcion = descripcionEl ? descripcionEl.value.trim() : '';

      if (descripcion === '') {
        mostrarToast('warning', 'La descripción es obligatoria');
        return;
      }

      // Enviar FormData y leer texto crudo para normalizar
      fetch('modules/inventario/ajax/tipoCaracteristicas.ajax.php', {
        method: 'POST',
        body: new FormData(form)
      })
      .then(resp => resp.text())
      .then(text => {
        console.log('RAW crear response:', text);
        let parsed;
        try { parsed = JSON.parse(text); } catch (err) { parsed = text.toString().trim(); }

        const respuesta = normalizarRespuesta(parsed);

        const modalEl = document.getElementById('modalAgregarTipoCaracteristica');
        const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? (bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl)) : null;

        if (respuesta === 'ok') {
          if (modalInstance) modalInstance.hide();
          mostrarToast('success', 'Tipo de característica guardado correctamente');
          setTimeout(() => location.reload(), 1200);
        } else if (respuesta === 'error_duplicado') {
          mostrarToast('warning', '¡Atención! La descripción ya existe.');
        } else if (parsed && typeof parsed === 'object' && (parsed.status === 'error' || parsed.resultado === 'error')) {
          const msg = parsed.message || parsed.mensaje || 'Error desconocido';
          mostrarToast('error', 'No se pudo guardar: ' + msg);
          console.error('Crear servidor:', parsed);
        } else {
          mostrarToast('error', 'No se pudo guardar: ' + JSON.stringify(parsed));
          console.error('Crear inesperado:', parsed);
        }
      })
      .catch(err => {
        console.error('Fetch crear error:', err);
        mostrarToast('error', 'Error al comunicarse con el servidor.');
      });
    });
  })();

  /* =====================================
     3. CARGAR DATOS EN EL MODAL EDITAR Y ENVIAR EDICIÓN
     - Botón editar: .btnEditarTipoCaracteristica (data-id)
     - Modal: #modalEditarTipoCaracteristica
     - Form editar: #formEditarTipoCaracteristica
  ===================================== */
  (function initEditar() {
    // Asegurar modal en body
    (function ensureModalInBody() {
      const modalEl = document.getElementById('modalEditarTipoCaracteristica');
      if (modalEl && modalEl.parentElement !== document.body) document.body.appendChild(modalEl);
    })();

    // Delegación click para cargar datos
    document.addEventListener('click', function (e) {
      const boton = e.target.closest('.btnEditarTipoCaracteristica');
      if (!boton) return;

      let id = boton.dataset.id || boton.getAttribute('data-id');
      if (!id) {
        mostrarToast('error', 'ID no encontrado en el botón.');
        return;
      }
      id = id.replace(/['"]/g, '').trim();

      const datos = new FormData();
      datos.append('idTipoCaracteristica', id);

      fetch('modules/inventario/ajax/tipoCaracteristicas.ajax.php', { method: 'POST', body: datos })
        .then(res => res.text())
        .then(text => {
          console.log('RAW cargar editar response:', text);
          let parsed;
          try { parsed = JSON.parse(text); } catch (err) { parsed = text.toString().trim(); }

          if (!parsed || typeof parsed === 'string') {
            mostrarToast('error', 'Respuesta inválida al cargar datos.');
            console.error('Cargar editar inválido:', parsed);
            return;
          }

          const modalEl = document.getElementById('modalEditarTipoCaracteristica');
          const inputId = document.getElementById('editarIdTipoCaracteristica');
          const inputDesc = document.getElementById('editarDescripcion');
          const viewUser = document.getElementById('editarUsuarioCreacion');
          const viewFecha = document.getElementById('editarFechaCreacion');

          if (!modalEl || !inputId || !inputDesc) {
            mostrarToast('error', 'Elementos del modal no encontrados.');
            return;
          }

          inputId.value = parsed.idTipoCaracteristica ?? '';
          inputDesc.value = parsed.descripcion ?? '';
          if (viewUser) viewUser.textContent = parsed.idUsuarioRegistro ?? 'N/A';
          if (viewFecha) viewFecha.textContent = parsed.fechaCreacion ?? 'N/A';

          if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            mostrarToast('error', 'Bootstrap no cargado.');
            return;
          }
          const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
          modalInstance.show();
        })
        .catch(err => {
          console.error('Fetch cargar editar error:', err);
          mostrarToast('error', 'Error al comunicarse con el servidor.');
        });
    });

    // Envío del formulario de edición
    const formEditar = document.getElementById('formEditarTipoCaracteristica');
    if (!formEditar) return;

    formEditar.addEventListener('submit', function (e) {
      e.preventDefault();

      fetch('modules/inventario/ajax/tipoCaracteristicas.ajax.php', { method: 'POST', body: new FormData(formEditar) })
        .then(res => res.text())
        .then(text => {
          console.log('RAW editar response:', text);
          let parsed;
          try { parsed = JSON.parse(text); } catch (err) { parsed = text.toString().trim(); }

          const r = normalizarRespuesta(parsed);

          const modalEl = document.getElementById('modalEditarTipoCaracteristica');
          const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? (bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl)) : null;

          if (r === 'ok') {
            if (modalInstance) modalInstance.hide();
            mostrarToast('success', 'Tipo de característica actualizado correctamente');
            setTimeout(() => location.reload(), 1200);
          } else if (r === 'error_duplicado') {
            mostrarToast('warning', '¡Atención! Este tipo de característica ya existe.');
          } else if (parsed && typeof parsed === 'object' && (parsed.status === 'error' || parsed.resultado === 'error')) {
            const msg = parsed.message || parsed.mensaje || 'Error desconocido';
            mostrarToast('error', 'No se pudo actualizar: ' + msg);
            console.error('Editar servidor:', parsed);
          } else {
            mostrarToast('error', 'No se pudo actualizar: ' + JSON.stringify(parsed));
            console.error('Editar inesperado:', parsed);
          }
        })
        .catch(err => {
          console.error('Fetch editar error:', err);
          mostrarToast('error', 'Error al comunicarse con el servidor.');
        });
    });
  })();

  

  /* =====================================
     5. Exportar funciones (si se usa en otros módulos)
  ===================================== */
  window.tipoCaracteristicasUtils = {
    mostrarToast: mostrarToast,
    ensureToastContainer: ensureToastContainer,
    normalizarRespuesta: normalizarRespuesta
  };

})(); // fin IIFE
