/* caracteristicas-equipo.js */
(function(){
  'use strict';

  const endpointAjaxEquipo = 'ajax/equipo.ajax.php';
  const endpointTipos = 'modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php';
  const endpointValores = 'modules/inventario/ajax/valoresPorTipo.ajax.php'; // opcional, si tienes endpoint para valores

  // Toast simple (usa tu función existente si la tienes)
  function mostrarToast(tipo, msg) {
    console.log(tipo, msg);
    // puedes reutilizar mostrarToast existente
    if (window.mostrarToast) return window.mostrarToast(tipo, msg);
    alert(msg);
  }

  // llenar select activos (implementa endpoint real)
  async function fetchActivos() {
    try {
      const res = await fetch('modules/inventario/ajax/activosTabla.ajax.php');
      if (!res.ok) return [];
      return await res.json();
    } catch(e) { console.error(e); return []; }
  }

  async function llenarSelectActivos() {
    const sel = document.getElementById('equipoSelectActivo');
    if (!sel) return;
    sel.innerHTML = '<option value="">Cargando...</option>';
    const activos = await fetchActivos();
    sel.innerHTML = '<option value="">Seleccionar activo...</option>';
    activos.forEach(a => {
      const o = document.createElement('option');
      o.value = a.idActivos ?? a.id ?? '';
      o.textContent = a.descripcion ?? a.nombre ?? ('Activo ' + o.value);
      sel.appendChild(o);
    });
  }

  // llenar tipos
  async function fetchTipos() {
    try {
      const res = await fetch(endpointTipos);
      if (!res.ok) return [];
      return await res.json();
    } catch(e) { console.error(e); return []; }
  }

  async function llenarSelectTipo(idOrEl) {
    const sel = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
    if (!sel) return;
    sel.innerHTML = '<option value="">Cargando tipos...</option>';
    const tipos = await fetchTipos();
    sel.innerHTML = '<option value="">Seleccionar tipo...</option>';
    tipos.forEach(t => {
      const o = document.createElement('option');
      o.value = t.idTipoCaracteristica ?? t.id ?? '';
      o.textContent = t.descripcion ?? t.descripcionCorta ?? ('Tipo ' + o.value);
      sel.appendChild(o);
    });
  }

  // llenar valores por tipo (si tienes endpoint)
  async function fetchValoresPorTipo(idTipo) {
    try {
      const res = await fetch(endpointValores + '?idTipo=' + encodeURIComponent(idTipo));
      if (!res.ok) return [];
      return await res.json();
    } catch(e) { console.error(e); return []; }
  }

  async function llenarSelectValores(idTipo) {
    const sel = document.getElementById('nuevoSelectValor');
    if (!sel) return;
    sel.innerHTML = '<option value="">Cargando valores...</option>';
    const vals = await fetchValoresPorTipo(idTipo);
    sel.innerHTML = '<option value="">Seleccionar valor...</option>';
    vals.forEach(v => {
      const o = document.createElement('option');
      o.value = v.idCaracteristica ?? v.id ?? '';
      o.textContent = v.valor ?? v.descripcion ?? ('Valor ' + o.value);
      sel.appendChild(o);
    });
  }

  // tabla de características del equipo (array de objetos {idTipo, tipoDesc, idCaracteristica, valor})
  const caracteristicasEquipo = [];

  function renderTablaCaracteristicas() {
    const tbody = document.querySelector('#tablaCaracteristicasEquipo tbody');
    tbody.innerHTML = '';
    caracteristicasEquipo.forEach((c, idx) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td class="fw-semibold">${c.tipoDesc}</td>
                      <td>${c.valor}</td>
                      <td class="text-end">
                        <button data-idx="${idx}" class="btn btn-sm btn-icon btn-outline-danger btn-eliminar-carac" title="Eliminar">
                          <i class="ti ti-trash"></i>
                        </button>
                      </td>`;
      tbody.appendChild(tr);
    });
    // actualizar hidden con ids
    const ids = caracteristicasEquipo.map(c => c.idCaracteristica).filter(Boolean).join(',');
    document.getElementById('equipoCaracteristicasIds').value = ids;
  }

  // agregar característica desde selects
  document.addEventListener('click', async function(e){
    if (e.target.closest('#btnAgregarCaracteristica')) {
      const selTipo = document.getElementById('nuevoSelectTipo');
      const selValor = document.getElementById('nuevoSelectValor');
      const idTipo = selTipo?.value;
      const idValor = selValor?.value;
      const tipoDesc = selTipo?.selectedOptions[0]?.textContent ?? '';
      const valorText = selValor?.selectedOptions[0]?.textContent ?? '';

      if (!idTipo || !idValor) { mostrarToast('warning','Selecciona tipo y valor'); return; }

      // evitar duplicados por idCaracteristica
      if (caracteristicasEquipo.some(c => String(c.idCaracteristica) === String(idValor))) {
        mostrarToast('warning','La característica ya fue agregada');
        return;
      }

      caracteristicasEquipo.push({
        idTipo: idTipo,
        tipoDesc: tipoDesc,
        idCaracteristica: idValor,
        valor: valorText
      });
      renderTablaCaracteristicas();
    }

    if (e.target.closest('.btn-eliminar-carac')) {
      const idx = parseInt(e.target.closest('.btn-eliminar-carac').dataset.idx,10);
      if (!isNaN(idx)) {
        caracteristicasEquipo.splice(idx,1);
        renderTablaCaracteristicas();
      }
    }
  });

  // abrir modal para nuevo equipo
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btnAgregarEquipo') || e.target.closest('#abrirModalAgregarEquipo');
    if (!btn) return;
    // reset form
    document.getElementById('formEquipo').reset();
    document.getElementById('equipoId').value = '';
    caracteristicasEquipo.length = 0;
    renderTablaCaracteristicas();
    llenarSelectActivos();
    llenarSelectTipo('nuevoSelectTipo');
    // limpiar auditoría
    document.getElementById('equipoUsuarioCreacion').textContent = '--';
    document.getElementById('equipoFechaCreacion').textContent = '--';
    document.getElementById('equipoUsuarioModificacion').textContent = '--';
    document.getElementById('equipoFechaModificacion').textContent = '--';
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAgregarEquipo'));
    document.getElementById('modalEquipoTitle').textContent = 'Agregar Equipo';
    modal.show();
  });

  // abrir modal para editar (botones .btnEditarEquipo con data-id)
  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.btnEditarEquipo');
    if (!btn) return;
    const id = btn.dataset.id || btn.getAttribute('data-id');
    if (!id) { mostrarToast('error','ID no encontrado'); return; }

    // cargar selects base
    await llenarSelectActivos();
    await llenarSelectTipo('nuevoSelectTipo');

    // pedir registro
    const fd = new FormData();
    fd.append('idEquipo', id);
    const resp = await fetch(endpointAjaxEquipo, { method:'POST', body: fd });
    const parsed = await resp.json().catch(()=>null);
    if (!parsed || parsed.resultado !== 'ok') {
      mostrarToast('error', parsed?.mensaje ?? 'Error al cargar registro');
      return;
    }
    const row = parsed.data;

    // asignar campos
    document.getElementById('equipoId').value = row.idEquipo ?? '';
    document.getElementById('equipoCodigo').value = row.codigoPatrimonial ?? '';
    document.getElementById('equipoSerie').value = row.numeroSerie ?? '';
    document.getElementById('equipoFechaAdq').value = row.fechaAdquisicion ?? '';
    document.getElementById('equipoFechaInicioG').value = row.fechaInicioGarantia ?? '';
    document.getElementById('equipoFechaFinG').value = row.fechaFinGarantia ?? '';
    if (row.idActivo) document.getElementById('equipoSelectActivo').value = row.idActivo;

    // auditoría
    document.getElementById('equipoUsuarioCreacion').textContent = row.usuarioCreacion ?? '--';
    document.getElementById('equipoFechaCreacion').textContent = row.fechaCreacion ?? '--';
    document.getElementById('equipoUsuarioModificacion').textContent = row.usuarioModificacion ?? '--';
    document.getElementById('equipoFechaModificacion').textContent = row.fechaModificacion ?? '--';

    // cargar caracteristicas asociadas si vienen (ej: '1,2,3' o array)
    caracteristicasEquipo.length = 0;
    if (row.caracteristicas) {
      const ids = Array.isArray(row.caracteristicas) ? row.caracteristicas : String(row.caracteristicas).split(',').filter(Boolean);
      // para cada id pedir info mínima (tipoDesc y valor) o usar endpoint que devuelva lista
      for (const idc of ids) {
        // intentar obtener info del endpoint de caracteristicas (si existe)
        try {
          const r = await fetch('modules/inventario/ajax/caracteristicaPorId.ajax.php', { method:'POST', body: (()=>{const f=new FormData(); f.append('idCaracteristica', idc); return f; })() });
          const p = await r.json().catch(()=>null);
          const d = p?.data ?? p;
          caracteristicasEquipo.push({
            idTipo: d?.idTipoCaracteristica ?? '',
            tipoDesc: d?.tipoDescripcion ?? d?.tipo ?? '',
            idCaracteristica: d?.idCaracteristica ?? idc,
            valor: d?.valor ?? d?.descripcion ?? ''
          });
        } catch(e) {
          caracteristicasEquipo.push({ idTipo:'', tipoDesc:'', idCaracteristica: idc, valor: '' });
        }
      }
    }
    renderTablaCaracteristicas();

    // mostrar modal
    document.getElementById('modalEquipoTitle').textContent = 'Editar Equipo';
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAgregarEquipo'));
    modal.show();
  });

  // cuando cambia tipo en la sección agregar característica, cargar valores
  document.getElementById('nuevoSelectTipo')?.addEventListener('change', function(){
    const idTipo = this.value;
    if (idTipo) llenarSelectValores(idTipo);
  });

  // submit del formulario (guardar)
  document.getElementById('formEquipo').addEventListener('submit', async function(e){
    e.preventDefault();
    const idEquipo = document.getElementById('equipoId').value || '';
    const fd = new FormData(this);
    // añadir caracteristicas (hidden ya actualizado)
    fd.append('guardarEquipo', '1'); // indicador para el ajax
    // enviar
    try {
      const resp = await fetch(endpointAjaxEquipo, { method:'POST', body: fd });
      const parsed = await resp.json().catch(()=>null);
      if (!parsed) { mostrarToast('error','Respuesta inválida del servidor'); return; }
      if (parsed.resultado === 'ok') {
        mostrarToast('success', parsed.mensaje ?? 'Equipo guardado');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAgregarEquipo')).hide();
        setTimeout(()=> location.reload(), 800);
      } else {
        mostrarToast('error', parsed.mensaje ?? 'Error al guardar');
      }
    } catch(err) {
      console.error(err);
      mostrarToast('error','Error de red al guardar');
    }
  });

  // inicializar (llenar selects básicos)
  document.addEventListener('DOMContentLoaded', function(){
    llenarSelectTipo('nuevoSelectTipo');
    llenarSelectActivos();
  });

})();
