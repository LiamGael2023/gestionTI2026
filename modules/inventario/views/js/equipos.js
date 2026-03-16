/* =====================================
    DOM READY - EQUIPOS
===================================== */
document.addEventListener("DOMContentLoaded", function () {

    /* --- 0. INICIALIZAR COMBOS --- */
    async function llenarSelectActivos() {
        const sel = document.getElementById('nuevoIdActivo');
        sel.innerHTML = '<option value="">Cargando...</option>';
        try {
            const res = await fetch('modules/inventario/ajax/activosTabla.ajax.php');
            const activos = await res.json();
            sel.innerHTML = '<option value="">Seleccionar activo...</option>';
            activos.forEach(a => {
                const o = document.createElement('option');
                o.value = a.idActivos;
                o.textContent = a.descripcion;
                sel.appendChild(o);
            });
        } catch (e) { console.error(e); }
    }

    async function llenarSelectTipos() {
        const sel = document.getElementById('nuevoTipoCaracteristica');
        sel.innerHTML = '<option value="">Cargando...</option>';
        try {
            const res = await fetch('modules/inventario/ajax/tipoCaracteristicasTabla.ajax.php');
            const tipos = await res.json();
            sel.innerHTML = '<option value="">Seleccionar tipo...</option>';
            tipos.forEach(t => {
                const o = document.createElement('option');
                o.value = t.idTipoCaracteristica;
                o.textContent = t.descripcion;
                sel.appendChild(o);
            });
        } catch (e) { console.error(e); }
    }

    document.getElementById('nuevoTipoCaracteristica').addEventListener('change', async function () {
        const idTipo = this.value;
        const sel = document.getElementById('nuevoValorCaracteristica');
        sel.innerHTML = '<option value="">Cargando...</option>';
        try {
            const res = await fetch('modules/inventario/ajax/caracteristicasTabla.ajax.php?idTipoCaracteristica=' + idTipoCaracteristica);
            const valores = await res.json();
            sel.innerHTML = '<option value="">Seleccionar valor...</option>';
            valores.forEach(v => {
                const o = document.createElement('option');
                o.value = v.idCaracteristica;
                o.textContent = v.valor;
                sel.appendChild(o);
            });
        } catch (e) { console.error(e); }
    });

    // Llamar al abrir modal
    llenarSelectActivos();
    llenarSelectTipos();

    /* --- 1. LOGICA DE LA TABLA TEMPORAL DE CARACTERÍSTICAS --- */
    const btnAgregar = document.getElementById("btnAgregarNuevaCaracteristica");
    const tablaCaract = document.querySelector("#tablaNuevoEquipoCaracteristicas tbody");
    const inputHiddenIds = document.getElementById("nuevoCaracteristicasIds");

    const caracteristicasEquipo = [];

    if (btnAgregar) {
        btnAgregar.addEventListener("click", function () {
            const tipoSel = document.getElementById('nuevoTipoCaracteristica');
            const valSel = document.getElementById('nuevoValorCaracteristica');
            if (!tipoSel.value || !valSel.value) return;

            caracteristicasEquipo.push({
                idCaracteristica: valSel.value,
                tipo: tipoSel.options[tipoSel.selectedIndex].text,
                valor: valSel.options[valSel.selectedIndex].text
            });

            renderTablaCaracteristicas();
        });
    }

    function renderTablaCaracteristicas() {
        tablaCaract.innerHTML = '';
        caracteristicasEquipo.forEach((c, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${c.tipo}</td>
                <td>${c.valor}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-danger" data-idx="${idx}">Eliminar</button>
                </td>`;
            tablaCaract.appendChild(tr);
        });
        inputHiddenIds.value = caracteristicasEquipo.map(c => c.idCaracteristica).join(',');
    }

    tablaCaract.addEventListener('click', function (e) {
        if (e.target.tagName === 'BUTTON') {
            const idx = e.target.dataset.idx;
            caracteristicasEquipo.splice(idx, 1);
            renderTablaCaracteristicas();
        }
    });

    /* --- 2. FORMULARIO GUARDAR EQUIPO --- */
    const formEquipo = document.getElementById("formNuevoEquipo");
    if (formEquipo) {
        formEquipo.addEventListener("submit", async function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('crearEquipo', '1');
            try {
                const resp = await fetch('modules/inventario/ajax/equipo.ajax.php', { method: 'POST', body: fd });
                const data = await resp.json();
                if (data.resultado === 'ok') {
                    bootstrap.Modal.getInstance(document.getElementById("modalAgregarEquipo")).hide();
                    mostrarToast("success", data.mensaje);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarToast("error", data.mensaje);
                }
            } catch (err) { console.error(err); }
        });
    }

    /* --- 3. CONFIGURACIÓN DATATABLE EQUIPOS --- */
    if ($.fn.DataTable.isDataTable('#tablaEquipos')) {
        $('#tablaEquipos').DataTable().destroy();
    }

    $('#tablaEquipos').DataTable({
        responsive: true,
        pageLength: 10,
        autoWidth: false,
        dom: `<'card-body border-bottom py-3'<'row g-3'<'col-md-auto'l><'col-md-auto ms-auto'<'d-flex'Bf>>>>tr<'card-footer d-flex'ip>`,
        buttons: [
            { extend: 'excelHtml5', text: '<i class="ti ti-file-spreadsheet"></i>', className: 'btn btn-outline-success btn-sm' },
            { extend: 'pdfHtml5', text: '<i class="ti ti-file-description"></i>', className: 'btn btn-outline-danger btn-sm' }
        ],
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm').attr('placeholder', 'Buscar equipo...');
        }
    });
});
