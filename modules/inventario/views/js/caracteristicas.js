/* caracteristicas.js
   Versión final integrada: rellena combo, selecciona por id y actualiza texto con tipoDescripcion,
   maneja auditoría y muestra modal solo después de tener datos.
   Incluir este script al final del body.
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

  function sanitizeMessage(msg) {
    if (msg === null || msg === undefined) return '';
    if (typeof msg === 'object') {
      return String(msg.mensaje ?? msg.message ?? JSON.stringify(msg)).replace(/["{}]/g, '');
    }
    const s = String(msg).trim();
    if (/^[0-9]+$/.test(s)) return 'Operación completada';
    if ((s.startsWith('{') && s.endsWith('}')) || (s.startsWith('[') && s.endsWith(']'))) {
      try {
        const p = JSON.parse(s);
        return String(p.mensaje ?? p.message ?? JSON.stringify(p)).replace(/["{}]/g, '');
      } catch (e) {
        return s;
      }
    }
    return s.length > 240 ? s.slice(0, 237) + '...' : s;
  }

  function mostrarToast(tipo, mensaje, delay = 3500) {
    const colores = { success: "bg-success", error: "bg-danger", warning: "bg-warning", info: "bg-info" };
    const container = ensureToastContainer();
    const colorClass = colores[tipo] || colores.info;
    const safeMsg = sanitizeMessage(mensaje) || (tipo === 'success' ? 'Operación exitosa' : 'Ocurrió un error');

    const html = `
      <div class="toast align-items-center text-white ${colorClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">${safeMsg}</div>
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
    if (raw === null || raw === undefined) return { code: null, message: null, raw: raw };
    if (typeof raw === 'string') {
      const s = raw.trim();
      if ((s.startsWith('{') && s.endsWith('}')) || (s.startsWith('[') && s.endsWith(']'))) {
        try {
          const parsed = JSON.parse(s);
          if (typeof parsed === 'object' && !Array.isArray(parsed)) {
            return {
              code: parsed.resultado ?? parsed.status ?? parsed.code ?? null,
              message: parsed.mensaje ?? parsed.message ?? parsed.msg ?? null,
              raw: parsed
            };
          }
          return { code: s, message: null, raw: parsed };
        } catch (e) {
          return { code: s, message: null, raw: s };
        }
      }
      return { code: s, message: null, raw: s };
    }
    if (typeof raw === 'object') {
      return {
        code: raw.resultado ?? raw.status ?? raw.code ?? null,
        message: raw.mensaje ?? raw.message ?? raw.msg ?? null,
        raw: raw
      };
    }
    return { code: String(raw).trim(), message: null, raw: raw };
  }

  /* ---------------------------
     Endpoints
     --------------------------- */
  const tiposEndpoint = 'modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php';
  const ajaxCaracteristicas = 'modules/inventario/ajax/caracteristicas.ajax.php';

  /* ---------------------------
     DataTable (inicialización)
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
        mostrarToast('error', 'Error inicializando tabla: ' + (err.message || err));
      }
    }
  });

  /* ---------------------------
     Fetch y llenado de tipos
     --------------------------- */
  async function fetchTipos() {
    try {
      const res = await fetch(tiposEndpoint, { cache: 'no-store' });
      if (!res.ok) {
        const text = await res.text().catch(() => '');
        console.error('fetchTipos error body:', text);
        mostrarToast('error', `Error al obtener tipos (HTTP ${res.status})`);
        return [];
      }
      const data = await res.json();
      if (!Array.isArray(data)) {
        console.error('fetchTipos: data no es array', data);
        mostrarToast('error', 'Respuesta inválida al obtener tipos');
        return [];
      }
      return data;
    } catch (err) {
      console.error('Error al obtener tipos:', err);
      mostrarToast('error', 'Error de red al cargar tipos: ' + (err.message || err));
      return [];
    }
  }

  async function llenarSelectTipo(selectRef, placeholderText = 'Seleccionar tipo...') {
    let selectEl = null;
    if (!selectRef) return;
    if (typeof selectRef === 'string') {
      selectEl = document.getElementById(selectRef) || document.querySelector(`select[name="${selectRef}"]`);
    } else {
      selectEl = selectRef;
    }
    if (!selectEl) {
      console.warn('llenarSelectTipo: select no encontrado para', selectRef);
      return;
    }

    selectEl.innerHTML = '';
    const loading = document.createElement('option');
    loading.value = '';
    loading.disabled = true;
    loading.selected = true;
    loading.textContent = 'Cargando tipos...';
    selectEl.appendChild(loading);

    const tipos = await fetchTipos();

    selectEl.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.disabled = true;
    placeholder.selected = true;
    placeholder.textContent = placeholderText;
    selectEl.appendChild(placeholder);

    if (!tipos || tipos.length === 0) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.disabled = true;
      opt.textContent = 'No hay tipos disponibles';
      selectEl.appendChild(opt);
      return;
    }

    tipos.forEach(t => {
      const opt = document.createElement('option');
      opt.value = String(t.idTipoCaracteristica ?? t.id ?? '');
      opt.textContent = t.descripcion ?? t.descripcionCorta ?? ('Tipo ' + opt.value);
      opt.setAttribute('data-descripcion', opt.textContent);
      selectEl.appendChild(opt);
    });
  }

  /* ---------------------------
     Selección robusta del tipo (acepta descripcion)
     --------------------------- */
  async function seleccionarTipoCuandoListo(selectEl, idTipo, descripcionTipo) {
    if (!selectEl) return;
    const target = String(idTipo ?? '');
    if (!target) return;

    // 1) si ya existe la opción, actualizar texto si viene descripcionTipo y seleccionar
    let opt = selectEl.querySelector(`option[value="${target}"]`);
    if (opt) {
      if (descripcionTipo) {
        opt.textContent = descripcionTipo;
        opt.setAttribute('data-descripcion', descripcionTipo);
      }
      selectEl.value = target;
      return;
    }

    // 2) intentar coincidencia por índice/valor
    for (let i = 0; i < selectEl.options.length; i++) {
      const o = selectEl.options[i];
      if (String(o.value).trim() === target.trim()) {
        if (descripcionTipo) o.textContent = descripcionTipo;
        selectEl.selectedIndex = i;
        return;
      }
      if (!isNaN(o.value) && !isNaN(target) && Number(o.value) === Number(target)) {
        if (descripcionTipo) o.textContent = descripcionTipo;
        selectEl.selectedIndex = i;
        return;
      }
    }

    // 3) esperar hasta 2s a que la opción aparezca (por si se está llenando en paralelo)
    const start = Date.now();
    while (Date.now() - start < 2000) {
      opt = selectEl.querySelector(`option[value="${target}"]`);
      if (opt) {
        if (descripcionTipo) {
          opt.textContent = descripcionTipo;
          opt.setAttribute('data-descripcion', descripcionTipo);
        }
        selectEl.value = target;
        return;
      }
      await new Promise(r => setTimeout(r, 80));
    }

    // 4) si no existe, crear opción temporal con la descripción correcta
    const newOpt = document.createElement('option');
    newOpt.value = target;
    newOpt.textContent = descripcionTipo ?? ('Tipo ' + target);
    newOpt.setAttribute('data-descripcion', descripcionTipo ?? '');
    newOpt.selected = true;
    selectEl.appendChild(newOpt);
    selectEl.value = target;
  }

  /* ---------------------------
     Formulario: Crear Característica
     --------------------------- */
  const formNuevo = document.getElementById('formNuevoCaracteristica');
  if (formNuevo) {
    const modalAgregar = document.getElementById('modalAgregarCaracteristica');
    if (modalAgregar) {
      modalAgregar.addEventListener('show.bs.modal', function () {
        llenarSelectTipo('nuevoSelectTipo', 'Seleccionar tipo...');
        const usuarioSpan = modalAgregar.querySelector('#nuevoUsuarioCreacion');
        const fechaSpan = modalAgregar.querySelector('#nuevoFechaCreacion');
        if (usuarioSpan && window.USUARIO_NOMBRE) usuarioSpan.textContent = window.USUARIO_NOMBRE;
        if (fechaSpan) fechaSpan.textContent = new Date().toLocaleString();
      });
    }

    formNuevo.addEventListener('submit', function (e) {
      e.preventDefault();

      const modalEl = document.getElementById('modalAgregarCaracteristica');
      const selectTipo = formNuevo.querySelector('select[name="idTipoCaracteristica"]') || modalEl.querySelector('#nuevoSelectTipo');
      const inputValor = formNuevo.querySelector('input[name="nuevoValor"]') || modalEl.querySelector('input[type="text"]');

      const idTipo = selectTipo ? selectTipo.value : null;
      const valor = inputValor ? inputValor.value.trim() : '';

      if (!idTipo || idTipo === '' || valor === '') {
        mostrarToast('warning', 'Completa el tipo y el valor antes de guardar.');
        return;
      }

      const formData = new FormData();
      formData.append('nuevoValor', valor);
      formData.append('nuevoIdTipoCaracteristica', idTipo);

      fetch(ajaxCaracteristicas, { method: 'POST', body: formData })
        .then(res => res.text())
        .then(raw => {
          const parsed = normalizarRespuesta(raw);
          const code = parsed.code ? String(parsed.code).toLowerCase() : null;
          const msg = parsed.message ?? null;

          if (code === 'ok') {

            const toastEl = mostrarToast('success', msg || 'Característica guardada correctamente');

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();

            setTimeout(() => location.reload(), 500);

          } else if (code === 'error_duplicado') {
            mostrarToast('warning', msg || '¡Atención! Ya existe esa característica.');
          } else {
            mostrarToast('error', msg || 'Error al crear característica.');
            console.error('Crear caracteristica - respuesta raw:', parsed.raw);
          }
        })
        .catch(err => {
          console.error('Error técnico al crear caracteristica:', err);
          mostrarToast('error', 'Error de servidor al crear característica.');
        });
    });
  }

  /* ---------------------------
   Formulario: Editar Característica
   --------------------------- */
  const formEditar = document.getElementById('formEditarCaracteristica');

  if (formEditar) {

    formEditar.addEventListener('submit', function (e) {

      e.preventDefault();

      const modalEl = document.getElementById('modalEditarCaracteristica');

      const inputId = formEditar.querySelector('input[name="editarIdCaracteristica"]')
        || modalEl.querySelector('#editarIdCaracteristica');

      const selectTipo = formEditar.querySelector('select[name="editarIdTipoCaracteristica"]')
        || modalEl.querySelector('#editarSelectTipo');

      const inputValor = formEditar.querySelector('input[name="editarValor"]')
        || modalEl.querySelector('#editarValor');

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

      if (idTipo) {
        formData.append('editarIdTipoCaracteristica', idTipo);
      }

      fetch(ajaxCaracteristicas, {
        method: 'POST',
        body: formData
      })
        .then(res => res.text())
        .then(raw => {

          const parsed = normalizarRespuesta(raw);
          const code = parsed.code ? String(parsed.code).toLowerCase() : null;
          const msg = parsed.message ?? null;

          if (code === 'ok') {

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();

            mostrarToast('success', msg || 'Característica actualizada correctamente');

            setTimeout(() => location.reload(), 500);

          }
          else if (code === 'error_duplicado') {

            mostrarToast('warning', msg || '¡Atención! Ya existe esa característica.');

          }
          else {

            mostrarToast('error', msg || 'Error al actualizar característica.');
            console.error('Editar caracteristica - respuesta raw:', parsed.raw);

          }

        })
        .catch(err => {

          console.error('Error técnico al editar caracteristica:', err);
          mostrarToast('error', 'Error de servidor al actualizar característica.');

        });

    });

  }

  /* ---------------------------
     Cargar datos para editar (flujo robusto)
     --------------------------- */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btnEditarCaracteristica');
    if (!btn) return;

    const id = btn.dataset.id || btn.getAttribute('data-id');
    if (!id) {
      mostrarToast('error', 'ID no encontrado para editar.');
      return;
    }

    (async function () {
      try {
        const datos = new FormData();
        datos.append('idCaracteristica', id);

        const resp = await fetch(ajaxCaracteristicas, { method: 'POST', body: datos });
        const rawText = await resp.text();
        let parsed;
        try { parsed = JSON.parse(rawText); } catch (err) { parsed = rawText; }

        if (!parsed) {
          mostrarToast('error', 'Respuesta inválida del servidor');
          console.error('Respuesta cruda inválida:', rawText);
          return;
        }

        const row = parsed.data ?? parsed;

        const modalEl = document.getElementById('modalEditarCaracteristica');
        if (!modalEl) {
          console.error('Modal editar no encontrado en DOM');
          mostrarToast('error', 'Modal de edición no disponible');
          return;
        }

        const inputId = modalEl.querySelector('input[name="editarIdCaracteristica"]') || modalEl.querySelector('#editarIdCaracteristica');
        const selectTipo = modalEl.querySelector('#editarSelectTipo') || modalEl.querySelector('select[name="editarIdTipoCaracteristica"]');
        const inputValor = modalEl.querySelector('input[name="editarValor"]') || modalEl.querySelector('#editarValor');

        // FORZAR recarga del select siempre antes de seleccionar
        if (selectTipo) {
          await llenarSelectTipo(selectTipo, 'Seleccionar tipo...');
        }

        // asignar valores básicos (id y valor)
        if (inputId) inputId.value = row.idCaracteristica ?? row.id ?? id;
        if (inputValor) inputValor.value = row.valor ?? '';

        // seleccionar tipo pasando la descripcion que vino del servidor
        if (selectTipo) {
          await seleccionarTipoCuandoListo(selectTipo, row.idTipoCaracteristica ?? row.idTipo ?? '', row.tipoDescripcion ?? null);
        }

        // asignar auditoría (mostrar nombres si vienen, si no mostrar ids o '--')
        const viewUser = modalEl.querySelector('#editarUsuarioCreacion');
        const viewFecha = modalEl.querySelector('#editarFechaCreacion');
        const viewUserMod = modalEl.querySelector('#editarUsuarioModifica');
        const viewFechaMod = modalEl.querySelector('#editarFechaModificacion');

        if (viewUser) viewUser.textContent = row.usuarioCreacionNombre ?? row.idUsuarioCreacion ?? 'N/A';
        if (viewFecha) viewFecha.textContent = row.fechaCreacion ?? '--';
        if (viewUserMod) viewUserMod.textContent = row.usuarioModificaNombre ?? row.idUsuarioModifica ?? '--';
        if (viewFechaMod) viewFechaMod.textContent = row.fechaModificacion ?? '--';

        // mostrar modal al final
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();

      } catch (err) {
        console.error('Error en flujo editar:', err);
        mostrarToast('error', 'Error al cargar datos de la característica.');
      }
    })();
  });

  /* ---------------------------
     Exportar utilidades al window
     --------------------------- */
  window.caracteristicasUtils = {
    mostrarToast,
    normalizarRespuesta
  };

  window.caracteristicasTipos = {
    fetchTipos,
    llenarSelectTipo,
    seleccionarTipoCuandoListo
  };

})();
