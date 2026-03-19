/* =============================================================
   IPS.JS  — DataTables Server-Side + IP Individual + Rango CIDR
   Ruta ajax: modules/inventario/ajax/ips.ajax.php
============================================================= */

const AJAX_URL = 'modules/inventario/ajax/ips.ajax.php';

/* ─── Toast ─── */
function mostrarToast(tipo, mensaje) {
    const colores = { success:'bg-success', error:'bg-danger', warning:'bg-warning', info:'bg-info' };
    const container = document.getElementById('toastContainerIps');
    if (!container) return;
    container.insertAdjacentHTML('beforeend', `
    <div class="toast align-items-center text-white ${colores[tipo]??'bg-secondary'} border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body">${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`);
    const toastEl = container.lastElementChild;
    const t = new bootstrap.Toast(toastEl, { delay: 5000 });
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    t.show();
}

function manejarRespuesta(data, onSuccess) {
    if (!data || typeof data !== 'object') { mostrarToast('error','Respuesta inesperada.'); return; }
    const r = (data.resultado ?? '').toString().trim();
    const m = (data.mensaje   ?? '').toString().trim();
    if      (r === 'ok')              { mostrarToast('success', m || 'Operación realizada.'); if (onSuccess) onSuccess(); }
    else if (r === 'error_duplicado') { mostrarToast('warning', m || 'IP duplicada.'); }
    else                              { mostrarToast('error',   m || 'Error inesperado.'); }
}

/* ─── Custom Select ─── */
function crearCustomSelect(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return null;
    sel.style.display = 'none';
    const wrapId = 'cswrap_' + selectId;
    const viejo  = document.getElementById(wrapId);
    if (viejo) viejo.remove();
    const wrap = document.createElement('div');
    wrap.className = 'cs-wrap'; wrap.id = wrapId;
    wrap.innerHTML = `
        <div class="cs-display" tabindex="0">
            <span class="cs-text placeholder-text">Seleccionar...</span>
            <svg class="cs-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="cs-panel">
            <div class="cs-search-row">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <circle cx="5.5" cy="5.5" r="4" stroke="#9ca3af" stroke-width="1.4"/>
                    <path d="M9 9l2.5 2.5" stroke="#9ca3af" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                <input class="cs-search" type="text" placeholder="Buscar..." autocomplete="off">
            </div>
            <ul class="cs-list"></ul>
        </div>`;
    sel.parentNode.insertBefore(wrap, sel);
    const display  = wrap.querySelector('.cs-display');
    const panel    = wrap.querySelector('.cs-panel');
    const searchIn = wrap.querySelector('.cs-search');
    const list     = wrap.querySelector('.cs-list');
    let opciones   = [];

    function abrir() {
        document.querySelectorAll('.cs-wrap.cs-open').forEach(w => { if (w!==wrap) w.classList.remove('cs-open'); });
        wrap.classList.add('cs-open');
        searchIn.value = '';
        renderLista(opciones);
        requestAnimationFrame(() => {
            const rect = wrap.getBoundingClientRect();
            if ((window.innerHeight - rect.bottom) < 230 && rect.top > 230) {
                panel.style.top='auto'; panel.style.bottom='100%'; panel.style.marginTop='0'; panel.style.marginBottom='3px';
            } else {
                panel.style.top='100%'; panel.style.bottom='auto'; panel.style.marginTop='3px'; panel.style.marginBottom='0';
            }
            searchIn.focus();
        });
    }
    function cerrar() { wrap.classList.remove('cs-open'); }
    display.addEventListener('mousedown', e => { e.preventDefault(); wrap.classList.contains('cs-open') ? cerrar() : abrir(); });
    display.addEventListener('keydown', e => { if (e.key==='Enter'||e.key===' '){e.preventDefault();abrir();} if(e.key==='Escape')cerrar(); });
    document.addEventListener('mousedown', e => { if (!wrap.contains(e.target)) cerrar(); });
    searchIn.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        renderLista(q ? opciones.filter(o => o.label.toLowerCase().includes(q)) : opciones);
    });
    searchIn.addEventListener('keydown', e => { if (e.key==='Enter'){e.preventDefault();e.stopPropagation();} });

    function renderLista(items) {
        list.innerHTML = '';
        if (!items.length) { list.innerHTML='<li class="cs-empty">Sin resultados</li>'; return; }
        const val = sel.value;
        items.forEach(o => {
            const li = document.createElement('li');
            li.textContent = o.label; li.dataset.value = o.value;
            if (!o.value)           li.classList.add('cs-placeholder-item');
            if (o.value === val)    li.classList.add('cs-selected');
            li.addEventListener('mousedown', e => { e.preventDefault(); seleccionar(o.value, o.label); cerrar(); });
            list.appendChild(li);
        });
    }
    function seleccionar(value, label) {
        sel.value = value;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        const t = display.querySelector('.cs-text');
        t.textContent = label || 'Seleccionar...';
        t.classList.toggle('placeholder-text', !value);
    }
    return {
        setOptions(arr) {
            opciones = arr; sel.innerHTML = '';
            arr.forEach(o => { const opt=document.createElement('option'); opt.value=o.value; opt.textContent=o.label; sel.appendChild(opt); });
            const t = display.querySelector('.cs-text');
            t.textContent = arr[0]?.label ?? 'Seleccionar...'; t.classList.add('placeholder-text');
            sel.value = arr[0]?.value ?? '';
        },
        setValue(v) { const f=opciones.find(o=>String(o.value)===String(v)); if(f) seleccionar(f.value,f.label); },
        getValue()  { return sel.value; },
        reset()     { if (opciones.length) seleccionar(opciones[0].value, opciones[0].label); }
    };
}

/* ─── Ubicaciones ─── */
async function cargarUbicacionesCombo(cs, placeholder) {
    try {
        const res  = await fetch('modules/inventario/ajax/ubicaciones.ajax.php?listarUbicaciones=1');
        const data = await res.json();
        const ops  = [{ value:'', label:placeholder }];
        data.forEach(u => ops.push({ value:String(u.idUbicacion), label:u.descripcion }));
        cs.setOptions(ops);
    } catch(e) { console.error('[cargarUbicaciones]', e); }
}

const ESTADOS_IP = [
    { value:'disponible', label:'Disponible' },
    { value:'asignada',   label:'Asignada'   },
    { value:'reservada',  label:'Reservada'  },
];

/* ─── CIDR utils ─── */
function ipToInt(ip)     { return ip.split('.').reduce((a,o)=>(a<<8)+parseInt(o,10),0)>>>0; }
function intToIp(n)      { return [(n>>>24)&255,(n>>>16)&255,(n>>>8)&255,n&255].join('.'); }
function prefixToMask(p) { return intToIp(p===0?0:(0xFFFFFFFF<<(32-p))>>>0); }

function parseCidr(cidr) {
    const m = cidr.trim().match(/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})$/);
    if (!m) return null;
    const prefix = parseInt(m[2],10);
    if (prefix<8||prefix>30) return null;
    if (m[1].split('.').some(o=>parseInt(o)>255)) return null;
    const mask  = prefixToMask(prefix);
    const net   = (ipToInt(m[1])&ipToInt(mask))>>>0;
    const broad = (net|(~ipToInt(mask)>>>0))>>>0;
    const total = broad-net-1;
    if (total<=0) return null;
    return {
        red:intToIp(net), mascara:mask, prefix,
        inicio:intToIp(net+1), fin:intToIp(broad-1), broadcast:intToIp(broad),
        total, cidr:intToIp(net)+'/'+prefix,
        ips(){ const a=[]; for(let i=net+1;i<broad;i++) a.push(intToIp(i)); return a; }
    };
}

/* ═══════════════════════════════════════
   DOM READY
═══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {

    const csNuevoUbic    = crearCustomSelect('nuevoIdUbicacionIp');
    const csNuevoEstado  = crearCustomSelect('nuevoEstadoIp');
    const csCidrUbic     = crearCustomSelect('nuevoIdUbicacionCidr');
    const csCidrEstado   = crearCustomSelect('nuevoEstadoCidr');
    const csEditarUbic   = crearCustomSelect('editarIdUbicacionIp');
    const csEditarEstado = crearCustomSelect('editarEstadoIp');

    function cargarEstados(cs, val='disponible') { cs.setOptions(ESTADOS_IP); cs.setValue(val); }

    /* ════════════════════════════════
       DATATABLES — SERVER-SIDE
    ════════════════════════════════ */
    let tabla;

    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#tablaIps')) $('#tablaIps').DataTable().destroy();

        tabla = $('#tablaIps').DataTable({
            processing:  true,
            serverSide:  true,
            responsive:  true,
            pageLength:  25,
            autoWidth:   false,
            ajax: {
                url:   AJAX_URL + '?serverSide=1',
                type:  'GET',
                error: function (xhr) {
                    mostrarToast('error', 'Error al cargar los datos de la tabla.');
                    console.error('DataTables Ajax error:', xhr.responseText);
                }
            },
            columns: [
                { data:'ip',        orderable:true  },
                { data:'rango',     orderable:false },
                { data:'ubicacion', orderable:true  },
                { data:'estado',    orderable:true  },
                { data:'fecha',     orderable:true  },
                { data:'usuario',   orderable:false },
                { data:'acciones',  orderable:false, className:'text-end' },
            ],
            order: [[0, 'asc']],
            language: {
                processing:       '<div class="d-flex align-items-center gap-2 text-muted small"><span class="spinner-border spinner-border-sm"></span> Cargando...</div>',
                emptyTable:       'No hay direcciones IP registradas.',
                zeroRecords:      'No se encontraron resultados para la búsqueda.',
                info:             'Mostrando _START_ a _END_ de _TOTAL_ IPs',
                infoEmpty:        'Sin registros disponibles',
                infoFiltered:     '(filtrado de _MAX_ IPs en total)',
                search:           '',
                searchPlaceholder:'Buscar IP, ubicación...',
                lengthMenu:       'Mostrar _MENU_ registros',
                paginate:         { first:'«', last:'»', next:'›', previous:'‹' }
            },
            dom: `<'card-body border-bottom py-3'<'row g-3 align-items-center'<'col-12 col-md-auto'l><'col-12 col-md-auto ms-auto'<'d-flex gap-2'Bf>>>>tr<'card-footer d-flex align-items-center py-2'<'m-0 text-muted small'i><'pagination m-0 ms-auto'p>>`,
            buttons: [
                { extend:'excelHtml5', text:'<i class="ti ti-file-spreadsheet"></i>', className:'btn btn-outline-success btn-sm m-0', title:'IPs - Inventario TI' },
                { extend:'pdfHtml5',   text:'<i class="ti ti-file-description"></i>',  className:'btn btn-outline-danger btn-sm m-0',  title:'IPs - Inventario TI' }
            ],
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm m-0').attr('placeholder','Buscar IP, ubicación...');
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dt-buttons').addClass('d-flex gap-2 m-0');
            }
        });
    }

    initDataTable();

    /* ════════════════════════════════
       IP INDIVIDUAL
    ════════════════════════════════ */
    document.getElementById('modalAgregarIp')
        ?.addEventListener('show.bs.modal', async () => {
            document.getElementById('formNuevaIp')?.reset();
            await cargarUbicacionesCombo(csNuevoUbic, 'Seleccionar ubicación...');
            cargarEstados(csNuevoEstado, 'disponible');
        });

    document.getElementById('formNuevaIp')
        ?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            try {
                const resp = await fetch(AJAX_URL, { method:'POST', body:new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarIp')).hide();
                    tabla.ajax.reload(null, false);
                });
            } catch { mostrarToast('error','Error de servidor.'); }
            finally { btn.disabled = false; }
        });

    /* ════════════════════════════════
       RANGO CIDR
    ════════════════════════════════ */
    document.getElementById('modalAgregarRangoCidr')
        ?.addEventListener('show.bs.modal', async () => {
            document.getElementById('formNuevoRangoCidr')?.reset();
            document.getElementById('panelPreviewCidr').style.display = 'none';
            document.getElementById('btnGuardarRango').disabled        = true;
            document.getElementById('btnRangoCount').textContent       = '';
            await cargarUbicacionesCombo(csCidrUbic, 'Seleccionar ubicación...');
            cargarEstados(csCidrEstado, 'disponible');
        });

    document.getElementById('btnPreviewCidr')?.addEventListener('click', async function() {
        const cidrVal = document.getElementById('nuevoCidr').value.trim();
        const info    = parseCidr(cidrVal);
        const panel   = document.getElementById('panelPreviewCidr');
        const btnG    = document.getElementById('btnGuardarRango');
        const grande  = document.getElementById('pvAdvertenciaGrande');

        if (!info) { mostrarToast('error','CIDR no válido. Ej: 192.168.1.0/24'); panel.style.display='none'; btnG.disabled=true; return; }

        document.getElementById('pvRed').textContent       = info.red;
        document.getElementById('pvMascara').textContent   = info.mascara;
        document.getElementById('pvInicio').textContent    = info.inicio;
        document.getElementById('pvFin').textContent       = info.fin;
        document.getElementById('pvBroadcast').textContent = info.broadcast;
        panel.style.display = 'block';

        if (info.total > 1022) {
            grande.style.display = 'block';
            document.getElementById('pvTotal').textContent = info.total.toLocaleString();
            btnG.disabled = true; return;
        }
        grande.style.display = 'none';

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Calculando...';
        try {
            const fd = new FormData();
            fd.append('verificarDuplicadosCidr','1');
            fd.append('cidr', cidrVal);
            const res  = await fetch(AJAX_URL, { method:'POST', body:fd });
            const data = await res.json();
            const dups = data.duplicados ?? [];
            const nuevas = info.total - dups.length;

            document.getElementById('pvTotal').textContent = nuevas > 0 ? nuevas : '0';
            const dupWrap = document.getElementById('pvDuplicadosWrap');
            if (dups.length > 0) {
                document.getElementById('pvDuplicadosMsg').textContent   = `${dups.length} IP(s) ya registradas serán omitidas.`;
                document.getElementById('pvDuplicadosList').innerHTML    = dups.map(ip=>`<span class="badge badge-outline text-warning font-monospace">${ip}</span>`).join('');
                dupWrap.style.display = 'block';
            } else {
                dupWrap.style.display = 'none';
            }
            btnG.disabled = nuevas <= 0;
            document.getElementById('btnRangoCount').textContent = nuevas > 0 ? nuevas : '';
            if (nuevas <= 0) mostrarToast('warning','Todas las IPs ya están registradas.');
        } catch { mostrarToast('error','Error al verificar duplicados.'); }
        finally { this.disabled=false; this.innerHTML='<i class="ti ti-eye me-1"></i>Previsualizar rango'; }
    });

    document.getElementById('formNuevoRangoCidr')
        ?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarRango');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Registrando...';
            try {
                const resp = await fetch(AJAX_URL, { method:'POST', body:new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarRangoCidr')).hide();
                    tabla.ajax.reload(null, false);
                });
            } catch { mostrarToast('error','Error de servidor.'); }
            finally { btn.disabled=false; btn.innerHTML='<i class="ti ti-device-floppy me-1"></i>Registrar <span id="btnRangoCount"></span> IPs'; }
        });

    /* ════════════════════════════════
       EDITAR IP — delegado en tbody
    ════════════════════════════════ */
    document.getElementById('tablaIps').addEventListener('click', async function(e) {
        const btn = e.target.closest('.btnEditarIp');
        if (!btn) return;
        const fd = new FormData();
        fd.append('idIp', btn.getAttribute('data-id'));
        try {
            const res  = await fetch(AJAX_URL, { method:'POST', body:fd });
            const json = await res.json();
            if (json.error) { mostrarToast('error', json.error); return; }

            await cargarUbicacionesCombo(csEditarUbic, 'Seleccionar ubicación...');
            cargarEstados(csEditarEstado, json.estado ?? 'disponible');
            csEditarUbic.setValue(String(json.idUbicacion));
            csEditarEstado.setValue(json.estado ?? 'disponible');

            document.getElementById('editarIdIp').value                     = json.idIp;
            document.getElementById('editarIpAddress').value                = json.ipAddress;
            document.getElementById('editarMascara').value                  = json.mascara;
            document.getElementById('editarIpUsuarioCreacion').textContent  = json.idUsuarioRegistro ?? '--';
            document.getElementById('editarIpFechaCreacion').textContent    = json.fechaCreacion     ?? '--';
            document.getElementById('editarIpUsuarioModificacion').textContent = json.idUsuarioModifica ?? '--';
            document.getElementById('editarIpFechaModificacion').textContent = json.fechaModificacion ?? '--';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarIp')).show();
        } catch { mostrarToast('error','Error al cargar la IP.'); }
    });

    document.getElementById('formEditarIp')
        ?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]'); btn.disabled = true;
            try {
                const resp = await fetch(AJAX_URL, { method:'POST', body:new FormData(this) });
                manejarRespuesta(await resp.json(), () => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarIp')).hide();
                    tabla.ajax.reload(null, false);
                });
            } catch { mostrarToast('error','Error de servidor.'); }
            finally { btn.disabled = false; }
        });

}); // fin DOMContentLoaded
