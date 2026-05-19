/* caracteristicas.js — versión corregida */

(function () {
  'use strict';

  /* ══════════════════════════════════════
     UTILIDADES DE TOAST
  ══════════════════════════════════════ */
  function ensureToastContainer() {
    let c = document.getElementById("toastContainerCaracteristicas");
    if (!c) {
      c = document.createElement('div');
      c.id = 'toastContainerCaracteristicas';
      c.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      c.style.zIndex = '10800';
      document.body.appendChild(c);
    }
    return c;
  }

  function mostrarToast(tipo, mensaje, delay = 3500) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = ensureToastContainer();
    const msg = (typeof mensaje === 'object')
      ? (mensaje.mensaje ?? mensaje.message ?? JSON.stringify(mensaje))
      : String(mensaje ?? '');

    const html = `
      <div class="toast align-items-center text-white ${colores[tipo] || 'bg-info'} border-0 mb-2" role="alert">
        <div class="d-flex">
          <div class="toast-body">${msg}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>`;

    container.insertAdjacentHTML("beforeend", html);
    const el = container.lastElementChild;
    const toast = new bootstrap.Toast(el, { delay });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    toast.show();
  }

  /* ══════════════════════════════════════
     ENDPOINTS
  ══════════════════════════════════════ */
  const tiposEndpoint      = 'modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php';
  const ajaxCaracteristicas = 'modules/inventario/ajax/caracteristicas.ajax.php';

  /* ══════════════════════════════════════
     MAYÚSCULAS en tiempo real
  ══════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function () {
    ["nuevoValor", "editarValor"].forEach(function (id) {
      const input = document.getElementById(id);
      if (!input) return;
      input.addEventListener("input", function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
      });
    });
  });

  /* ══════════════════════════════════════
     DATATABLE
  ══════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
      try {
        $('#tablaCaracteristicas').DataTable({
          responsive: true,
          autoWidth: false,
          pageLength: 10,
          order: [[0, "asc"]],
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
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-sm btn-icon btn-outline-success', exportOptions: { columns: [0, 1, 2] } },
            { extend: 'pdfHtml5',   text: '<i class="ti ti-file-description"></i>',  className: 'btn btn-sm btn-icon btn-outline-danger ms-2', exportOptions: { columns: [0, 1, 2] } }
          ],
          initComplete: function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar...');
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_paginate .pagination').addClass('pagination-sm m-0');
          }
        });
      } catch (err) {
        console.error('DataTable error:', err);
      }
    }
  });

  /* ══════════════════════════════════════
     FETCH Y LLENADO DE TIPOS
  ══════════════════════════════════════ */
  async function fetchTipos() {
    try {
      const res = await fetch(tiposEndpoint, { cache: 'no-store' });
      if (!res.ok) return [];
      const data = await res.json();
      return Array.isArray(data) ? data : [];
    } catch (err) {
      console.error('fetchTipos error:', err);
      return [];
    }
  }

  async function llenarSelectTipo(selectEl, placeholder = 'Seleccionar tipo...') {
    if (!selectEl) return;

    selectEl.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;

    const tipos = await fetchTipos();

    if (!tipos.length) {
      selectEl.innerHTML += '<option value="" disabled>No hay tipos disponibles</option>';
      return;
    }

    tipos.forEach(t => {
      const opt = document.createElement('option');
      opt.value = String(t.idTipoCaracteristica ?? '');
      opt.textContent = t.descripcion ?? ('Tipo ' + opt.value);
      selectEl.appendChild(opt);
    });
  }

  async function seleccionarTipo(selectEl, idTipo) {
    if (!selectEl || !idTipo) return;
    const target = String(idTipo);

    // Buscar la opción directamente
    for (let i = 0; i < selectEl.options.length; i++) {
      if (String(selectEl.options[i].value) === target) {
        selectEl.selectedIndex = i;
        return;
      }
    }

    // Si no existe todavía, esperar hasta 1.5s (mientras carga el fetch)
    const start = Date.now();
    while (Date.now() - start < 1500) {
      await new Promise(r => setTimeout(r, 80));
      for (let i = 0; i < selectEl.options.length; i++) {
        if (String(selectEl.options[i].value) === target) {
          selectEl.selectedIndex = i;
          return;
        }
      }
    }
    console.warn('No se encontró la opción con idTipo:', target);
  }

  /* ══════════════════════════════════════
     MODAL AGREGAR — llenar select al abrir
  ══════════════════════════════════════ */
  const modalAgregarEl = document.getElementById('modalAgregarCaracteristica');
  if (modalAgregarEl) {
    modalAgregarEl.addEventListener('show.bs.modal', function () {
      const sel = document.getElementById('nuevoSelectTipo');
      llenarSelectTipo(sel, 'Seleccionar tipo...');

      const fechaSpan = document.getElementById('nuevoFechaCreacion');
      if (fechaSpan) fechaSpan.textContent = new Date().toLocaleString('es-PE');
    });
  }

  /* ══════════════════════════════════════
     FORMULARIO CREAR
  ══════════════════════════════════════ */
  const formNuevo = document.getElementById('formNuevoCaracteristica');
  if (formNuevo) {
    formNuevo.addEventListener('submit', function (e) {
      e.preventDefault();

      const sel   = document.getElementById('nuevoSelectTipo');
      const input = document.getElementById('nuevoValor');
      const idTipo = sel ? sel.value : '';
      const valor  = input ? input.value.trim() : '';

      if (!idTipo || !valor) {
        mostrarToast('warning', 'Completa el tipo y el valor antes de guardar.');
        return;
      }

      const fd = new FormData();
      fd.append('nuevoValor', valor);
      fd.append('nuevoIdTipoCaracteristica', idTipo);

      fetch(ajaxCaracteristicas, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          const code = String(res.resultado ?? '').trim();
          if (code === 'ok') {
            bootstrap.Modal.getInstance(modalAgregarEl).hide();
            mostrarToast('success', 'Característica guardada correctamente.');
            setTimeout(() => location.reload(), 1200);
          } else if (code === 'error_duplicado') {
            mostrarToast('warning', '¡Atención! Ya existe esa característica.');
          } else {
            mostrarToast('error', res.mensaje || 'Error al crear característica.');
          }
        })
        .catch(() => mostrarToast('error', 'Error de servidor.'));
    });
  }

  /* ══════════════════════════════════════
     CARGAR DATOS PARA MODAL EDITAR
  ══════════════════════════════════════ */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btnEditarCaracteristica');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    if (!id) return mostrarToast('error', 'ID no encontrado.');

    const modalEl = document.getElementById('modalEditarCaracteristica');

    (async function () {
      try {
        // 1. Llenar el select PRIMERO (en paralelo con el fetch de datos)
        const selPromise = llenarSelectTipo(
          document.getElementById('editarSelectTipo'),
          'Seleccionar tipo...'
        );

        // 2. Fetch de datos del registro
        const fd = new FormData();
        fd.append('idCaracteristica', id);

        const resp    = await fetch(ajaxCaracteristicas, { method: 'POST', body: fd });
        const json    = await resp.json();

        // Esperar a que el select termine de cargarse
        await selPromise;

        if (!json || json.resultado === 'error') {
          mostrarToast('error', json?.mensaje || 'No se encontró el registro.');
          return;
        }

        const row = json.data;

        // 3. Rellenar campos
        document.getElementById('editarIdCaracteristica').value = row.idCaracteristica ?? '';
        document.getElementById('editarValor').value            = row.valor ?? '';

        // 4. Seleccionar el tipo en el combo
        await seleccionarTipo(
          document.getElementById('editarSelectTipo'),
          row.idTipoCaracteristica
        );

        // 5. Auditoría
        document.getElementById('editarUsuarioCreacion').textContent  = row.idUsuarioCreacion  ?? '--';
        document.getElementById('editarFechaCreacion').textContent    = row.fechaCreacion       ?? '--';
        document.getElementById('editarUsuarioModifica').textContent  = row.idUsuarioModifica   ?? '--';
        document.getElementById('editarFechaModificacion').textContent = row.fechaModificacion  ?? '--';

        // 6. Mostrar modal
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

      } catch (err) {
        console.error('Error cargando datos para editar:', err);
        mostrarToast('error', 'Error al cargar datos de la característica.');
      }
    })();
  });

  /* ══════════════════════════════════════
     FORMULARIO EDITAR
  ══════════════════════════════════════ */
  const formEditar = document.getElementById('formEditarCaracteristica');
  if (formEditar) {
    formEditar.addEventListener('submit', function (e) {
      e.preventDefault();

      const modalEl = document.getElementById('modalEditarCaracteristica');
      const fd = new FormData(formEditar);

      fetch(ajaxCaracteristicas, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          const code = String(res.resultado ?? '').trim();
          if (code === 'ok') {
            bootstrap.Modal.getInstance(modalEl).hide();
            mostrarToast('success', 'Característica actualizada correctamente.');
            setTimeout(() => location.reload(), 1200);
          } else if (code === 'error_duplicado') {
            mostrarToast('warning', '¡Atención! Ya existe esa característica.');
          } else {
            mostrarToast('error', res.mensaje || 'Error al actualizar.');
          }
        })
        .catch(() => mostrarToast('error', 'Error al comunicarse con el servidor.'));
    });
  }

  /* ══════════════════════════════════════
     ELIMINAR (lógico)
  ══════════════════════════════════════ */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btnEliminarCaracteristica');
    if (!btn) return;

    const id          = btn.getAttribute('data-id');
    const descripcion = btn.getAttribute('data-descripcion') || 'este registro';

    document.getElementById('eliminarNombreCaracteristica').textContent = descripcion;
    document.getElementById('confirmarEliminarCaracteristica').setAttribute('data-id', id);

    bootstrap.Modal.getOrCreateInstance(
      document.getElementById('modalConfirmarEliminarCaracteristica')
    ).show();
  });

  const btnConfirmar = document.getElementById('confirmarEliminarCaracteristica');
  if (btnConfirmar) {
    btnConfirmar.addEventListener('click', function () {
      const id   = this.getAttribute('data-id');
      const fd   = new FormData();
      fd.append('eliminarIdCaracteristica', id);

      this.disabled = true;
      this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...';

      fetch(ajaxCaracteristicas, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          bootstrap.Modal.getInstance(
            document.getElementById('modalConfirmarEliminarCaracteristica')
          ).hide();

          if (json.resultado === 'ok') {
            mostrarToast('success', json.mensaje || 'Eliminado correctamente.');
            setTimeout(() => location.reload(), 1500);
          } else {
            mostrarToast('error', json.mensaje || 'No se pudo eliminar.');
          }
        })
        .catch(() => mostrarToast('error', 'Error al comunicarse con el servidor.'))
        .finally(() => {
          this.disabled = false;
          this.innerHTML = '<i class="ti ti-trash me-1"></i>Sí, eliminar';
        });
    });
  }

  /* ══════════════════════════════════════
     EXPORTS
  ══════════════════════════════════════ */
  window.caracteristicasUtils = { mostrarToast, fetchTipos, llenarSelectTipo };

})();
