/* caracteristicas.js
   Manejo: DataTable, toasts, crear/editar características via AJAX
   Versión adaptada: mantiene tu lógica original y añade carga dinámica de tipos
*/

(function () {
  'use strict';

  /* ---------------------------
     Utilidades de Toast
     --------------------------- */
  function ensureToastContainer() {
    let container = document.getElementById("toastContainerCaracteristicas");
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainerCaracteristicas';
      container.className = 'position-fixed bottom-0 end-0 p-3';
      container.style.zIndex = '10800';
      document.body.appendChild(container);
    }
    return container;
  }

  // Devuelve el elemento toast creado para poder enganchar eventos
  function mostrarToast(tipo, mensaje, delay = 3500) {
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
      const toast = new bootstrap.Toast(elementoToast, { delay });
      elementoToast.addEventListener('hidden.bs.toast', () => elementoToast.remove());
      toast.show();
    }

    return elementoToast;
  }

  function normalizarRespuesta(raw) {
    if (raw === null || raw === undefined) return null;
    if (typeof raw === 'string') return raw.trim();
    if (typeof raw === 'object') {
      if (raw.status) return String(raw.status).trim();
      if (raw.resultado) return String(raw.resultado).trim();
      return JSON.stringify(raw);
    }
    return String(raw).trim();
  }

  /* ---------------------------
     DataTable (inicialización original)
     --------------------------- */
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
      try {
        $('#tablaCaracteristicas').DataTable({
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
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-sm btn-icon btn-outline-success', titleAttr: 'Exportar a Excel', exportOptions: { columns: [0, 1, 2] } },
            { extend: 'pdfHtml5', text: '<i class="ti ti-file-description"></i>', className: 'btn btn-sm btn-icon btn-outline-danger ms-2', titleAttr: 'Exportar a PDF', exportOptions: { columns: [0, 1, 2] } }
          ],
          initComplete: function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_paginate .pagination').addClass('pagination-sm m-0');
          }
        });
      } catch (err) {
        console.error('Error inicializando DataTable caracteristicas:', err);
      }
    }
  });

  /* ---------------------------
     Endpoints y utilidades para tipos dinámicos
     --------------------------- */
  const tiposEndpoint = 'modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php';
  const ajaxCaracteristicas = 'modules/inventario/ajax/caracteristicas.ajax.php';

  async function fetchTipos() {
    try {
      const res = await fetch(tiposEndpoint, { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      if (!Array.isArray(data)) return [];
      return data;
    } catch (err) {
      console.error('Error al obtener tipos:', err);
      return [];
    }
  }

  async function llenarSelectTipo(selectEl, placeholderText = 'Seleccionar tipo...') {
    if (!selectEl) return;
    selectEl.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.disabled = true;
    ph.selected = true;
    ph.textContent = 'Cargando tipos...';
    selectEl.appendChild(ph);

    const tipos = await fetchTipos();
    selectEl.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.disabled = true;
    placeholder.selected = true;
    placeholder.textContent = placeholderText;
    selectEl.appendChild(placeholder);

    tipos.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.idTipoCaracteristica ?? t.id ?? '';
      opt.textContent = t.descripcion ?? t.descripcionTipo ?? ('Tipo ' + opt.value);
      selectEl.appendChild(opt);
    });
  }

  async function seleccionarTipoCuandoListo(selectEl, idTipo) {
    if (!selectEl) return;
    if (!idTipo) return;
    if (selectEl.querySelector(`option[value="${idTipo}"]`)) {
      selectEl.value = idTipo;
      return;
    }
    const start = Date.now();
    while (Date.now() - start < 1000) {
      if (selectEl.querySelector(`option[value="${idTipo}"]`)) {
        selectEl.value = idTipo;
        return;
      }
      await new Promise(r => setTimeout(r, 120));
    }
    const newOpt = document.createElement('option');
    newOpt.value = idTipo;
    newOpt.textContent = 'Tipo ' + idTipo;
    newOpt.selected = true;
    selectEl.appendChild(newOpt);
    selectEl.value = idTipo;
  }

  /* ---------------------------
     Formulario: Crear Característica (original + dinámico)
     --------------------------- */
  const formNuevo = document.getElementById('formNuevoCaracteristica');
  if (formNuevo) {
    // Asegurar que el select se llene si el modal se abre sin recargar
    const modalAgregar = document.getElementById('modalAgregarCaracteristica');
    if (modalAgregar) {
      modalAgregar.addEventListener('show.bs.modal', function () {
        const selectNuevo = modalAgregar.querySelector('select[name="idTipoCaracteristica"]') || modalAgregar.querySelector('#nuevoSelectTipo');
        llenarSelectTipo(selectNuevo, 'Seleccionar tipo...');
        // Auditoría informativa si existe variable global
        const usuarioSpan = modalAgregar.querySelector('#nuevoUsuarioCreacion');
        const fechaSpan = modalAgregar.querySelector('#nuevoFechaCreacion');
        if (usuarioSpan && window.USUARIO_NOMBRE) usuarioSpan.textContent = window.USUARIO_NOMBRE;
        if (fechaSpan) fechaSpan.textContent = new Date().toLocaleString();
      });
    }

    formNuevo.addEventListener('submit', function (e) {
      e.preventDefault();

      const modalEl = document.getElementById('modalAgregarCaracteristica');
      const selectTipo = formNuevo.querySelector('select[name="idTipoCaracteristica"]') || modalEl.querySelector('select');
      const inputValor = formNuevo.querySelector('input[name="nuevoValor"]') || modalEl.querySelector('input[type="text"]');

      const idTipo = selectTipo ? selectTipo.value : null;
      const valor = inputValor ? inputValor.value.trim() : '';

      if (!idTipo || idTipo === '' || idTipo === 'Seleccionar tipo...' || valor === '') {
        mostrarToast('warning', 'Completa el tipo y el valor antes de guardar.');
        return;
      }

      const formData = new FormData();
      formData.append('nuevoValor', valor);
      formData.append('nuevoIdTipoCaracteristica', idTipo);

      fetch(ajaxCaracteristicas, { method: 'POST', body: formData })
        .then(res => res.text())
        .then(raw => {
          const r = normalizarRespuesta(raw);
          if (r === 'ok') {
            const toastEl = mostrarToast('success', 'Característica guardada correctamente');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();
            if (toastEl) toastEl.addEventListener('hidden.bs.toast', () => location.reload());
            else setTimeout(() => location.reload(), 1500);
          } else if (r === 'error_duplicado') {
            mostrarToast('warning', '¡Atención! Ya existe esa característica.');
          } else {
            mostrarToast('error', 'Error: ' + r);
          }
        })
        .catch(err => {
          console.error('Error técnico:', err);
          mostrarToast('error', 'Error de servidor.');
        });
    });
  }

  /* ---------------------------
     Formulario: Editar Característica (original + dinámico)
     --------------------------- */
  const formEditar = document.getElementById('formEditarCaracteristica');
  if (formEditar) {
    // Asegurar que el select se llene al abrir el modal
    const modalEditar = document.getElementById('modalEditarCaracteristica');
    if (modalEditar) {
      modalEditar.addEventListener('show.bs.modal', function () {
        const selectEditar = modalEditar.querySelector('select[name="editarIdTipoCaracteristica"]') || modalEditar.querySelector('#editarSelectTipo');
        llenarSelectTipo(selectEditar, 'Seleccionar tipo...');
      });
    }

    formEditar.addEventListener('submit', function (e) {
      e.preventDefault();

      const modalEl = document.getElementById('modalEditarCaracteristica');
      const inputId = formEditar.querySelector('input[name="editarIdCaracteristica"]') || modalEl.querySelector('input[type="hidden"]');
      const selectTipo = formEditar.querySelector('select[name="editarIdTipoCaracteristica"]') || modalEl.querySelector('select');
      const inputValor = formEditar.querySelector('input[name="editarValor"]') || modalEl.querySelector('input[type="text"]');

      const id = inputId ? inputId.value : null;
      const idTipo = selectTipo ? selectTipo.value : null;
      const valor = inputValor ? inputValor.value.trim() : '';

      if (!id || valor === '') {
        mostrarToast('warning', 'Faltan datos obligatorios para editar.');
        return;
      }

      const formData = new FormData();
      formData.append('editarIdCaracteristica', id);
      formData.append('editarValor', valor);
      if (idTipo) formData.append('editarIdTipoCaracteristica', idTipo);

      fetch(ajaxCaracteristicas, { method: 'POST', body: formData })
        .then(res => res.text())
        .then(raw => {
          const r = normalizarRespuesta(raw);
          if (r === 'ok') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();
            const toastEl = mostrarToast('success', 'Característica actualizada correctamente');
            if (toastEl) toastEl.addEventListener('hidden.bs.toast', () => location.reload());
            else setTimeout(() => location.reload(), 1200);
          } else if (r === 'error_duplicado') {
            mostrarToast('warning', '¡Atención! Ya existe esa característica.');
          } else {
            mostrarToast('error', 'Error al actualizar: ' + r);
          }
        })
        .catch(err => {
          console.error('Error técnico:', err);
          mostrarToast('error', 'Error de servidor.');
        });
    });
  }

  /* ---------------------------
     Cargar datos para editar (delegación de eventos) — adaptado
     --------------------------- */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btnEditarCaracteristica');
    if (!btn) return;

    const id = btn.dataset.id || btn.getAttribute('data-id');
    if (!id) {
      mostrarToast('error', 'ID no encontrado para editar.');
      return;
    }

    const datos = new FormData();
    datos.append('idCaracteristica', id);

    fetch(ajaxCaracteristicas, { method: 'POST', body: datos })
      .then(res => res.json().catch(() => res.text()))
      .then(async raw => {
        // raw puede ser objeto o string
        let parsed = raw;
        if (typeof raw === 'string') {
          try { parsed = JSON.parse(raw); } catch (err) { parsed = raw; }
        }

        // Si la respuesta viene con status error
        if (parsed && parsed.status === 'error') {
          mostrarToast('error', parsed.message || 'No se encontró el registro');
          return;
        }

        const modalEl = document.getElementById('modalEditarCaracteristica');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl) || new bootstrap.Modal(modalEl);

        // Rellenar campos del modal (buscar por name o por estructura)
        const inputId = modalEl.querySelector('input[name="editarIdCaracteristica"]') || modalEl.querySelector('input[type="hidden"]');
        const selectTipo = modalEl.querySelector('select[name="editarIdTipoCaracteristica"]') || modalEl.querySelector('select');
        const inputValor = modalEl.querySelector('input[name="editarValor"]') || modalEl.querySelector('input[type="text"]');

        if (inputId) inputId.value = parsed.idCaracteristica ?? parsed.id ?? id;
        if (inputValor) inputValor.value = parsed.valor ?? parsed.valor ?? '';

        // Esperar a que el select tenga opciones y seleccionar el tipo
        if (selectTipo) {
          await seleccionarTipoCuandoListo(selectTipo, parsed.idTipoCaracteristica ?? parsed.idTipo ?? '');
        }

        // Mostrar datos de auditoría si existen
        const viewUser = modalEl.querySelector('#editarUsuarioCreacion');
        const viewFecha = modalEl.querySelector('#editarFechaCreacion');
        const viewUserMod = modalEl.querySelector('#editarUsuarioModifica');
        const viewFechaMod = modalEl.querySelector('#editarFechaModificacion');

        if (viewUser) viewUser.textContent = parsed.idUsuarioCreacion ?? parsed.idUsuarioRegistro ?? 'N/A';
        if (viewFecha) viewFecha.textContent = parsed.fechaCreacion ?? '';
        if (viewUserMod) viewUserMod.textContent = parsed.idUsuarioModifica ?? parsed.usuario ?? '--';
        if (viewFechaMod) viewFechaMod.textContent = parsed.fechaModificacion ?? '';

        modalInstance.show();
      })
      .catch(err => {
        console.error('Error cargando caracteristica:', err);
        mostrarToast('error', 'Error al cargar datos.');
      });
  });

  /* ---------------------------
     Exportar utilidades al window (opcional)
     --------------------------- */
  window.caracteristicasUtils = {
    mostrarToast,
    normalizarRespuesta
  };

  // Exportar funciones de tipos por si se necesitan externamente
  window.caracteristicasTipos = {
    fetchTipos,
    llenarSelectTipo,
    seleccionarTipoCuandoListo
  };

})();
