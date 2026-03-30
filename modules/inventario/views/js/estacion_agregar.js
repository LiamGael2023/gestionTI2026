/* =============================================================
   ESTACION_AGREGAR.JS  — Lógica exclusiva de la vista Agregar
   Requiere: estaciones_form.js cargado antes
   Requiere: constantes globales declaradas en estacion_agregar.php
       const AJAX_EST  = '...';
       const URL_LISTA = '...';
============================================================= */



document.addEventListener('DOMContentLoaded', async function () {

    initTogglePass();

    /* ── Custom Selects ── */
    const csPrincipal  = crearCustomSelect('nuevoEquipoPrincipalSelect');
    const csPeriferico = crearCustomSelect('nuevoPerifericoSelect');
    const csSoftware   = crearCustomSelect('nuevoSoftwareSelect');
    const csIp         = crearCustomSelect('nuevoIpSelect');

    /* ── Estado reactivo ── */
    let principal   = [];
    let perifericos = [];
    let software    = [];
    let ips         = [];

    let _tipoPrincipal              = '';
    let _filtroPerifericoImpresora  = false;

    /* ── Sync hiddens de equipos ── */
    function sync() {
        sincronizarHiddens('nuevo', principal, perifericos, software);
    }

    /* ── Lock/unlock selector de principal ── */
    function lockPrincipal(lock) {
        const wP = document.getElementById('cswrap_nuevoEquipoPrincipalSelect');
        if (wP) {
            wP.style.opacity       = lock ? '.4' : '1';
            wP.style.pointerEvents = lock ? 'none' : 'auto';
        }
        document.getElementById('btnAgregarNuevoPrincipal').disabled = lock;
    }

    /* ── Adaptar sidebar según tipo de equipo principal ── */
    function adaptarSidebar(tipo) {
        _tipoPrincipal = (tipo || '').toUpperCase();
        const esServidor  = _tipoPrincipal.includes('SERVIDOR');
        const esImpresora = _tipoPrincipal.includes('IMPRESORA');
        const esLaptopCpu = _tipoPrincipal.includes('LAPTOP') || _tipoPrincipal.includes('CPU');

        document.getElementById('nuevoBloqueDireccionFisica').style.display = esLaptopCpu ? '' : 'none';

        const tituloEl = document.getElementById('nuevoTituloAcceso');
        const iconoEl  = document.getElementById('nuevoIconoAcceso');
        if (esServidor) {
            tituloEl.textContent = 'Acceso al Servidor';
            iconoEl.className    = 'ti ti-server';
        } else if (esImpresora) {
            tituloEl.textContent = 'Acceso a Impresora';
            iconoEl.className    = 'ti ti-printer';
        } else {
            tituloEl.textContent = 'Acceso Remoto';
            iconoEl.className    = 'ti ti-device-desktop';
        }

        const bloqueCodigoEl = document.getElementById('nuevoBloqueCodigo');
        const labelCodigoEl  = document.getElementById('nuevoLabelCodigo');
        const inputCodigoEl  = document.getElementById('nuevoCodigoAnydesk');
        if (esServidor) {
            bloqueCodigoEl.style.display = 'none';
            if (inputCodigoEl) inputCodigoEl.value = '';
        } else if (esImpresora) {
            bloqueCodigoEl.style.display = '';
            labelCodigoEl.textContent    = 'Usuario';
            if (inputCodigoEl) inputCodigoEl.placeholder = 'Nombre de usuario';
        } else {
            bloqueCodigoEl.style.display = '';
            labelCodigoEl.textContent    = 'Código Anydesk';
            if (inputCodigoEl) inputCodigoEl.placeholder = '123 456 789';
        }

        const labelPassEl = document.getElementById('nuevoLabelContrasena');
        if (labelPassEl) labelPassEl.textContent = (esServidor || esImpresora) ? 'Contraseña' : 'Contraseña Anydesk';

        _filtroPerifericoImpresora = esImpresora;
    }

    /* ── Recargar combo IPs (excluye las ya asignadas) ── */
    async function recargarComboIp() {
        const idsEnUso = ips.map(ip => ip.idIp);
        await cargarIps(csIp, 0, idsEnUso);
        document.getElementById('btnAgregarNuevoIp').disabled = true;
    }

    /* ── Recargar combos de equipos ── */
    async function recargarCombos() {
        const excl       = idsExcluir(principal, perifericos, software);
        const tipoPerif  = _filtroPerifericoImpresora ? 'ups_estabilizador' : 'periferico';
        await Promise.all([
            cargarEquiposTipo(csPrincipal,  'principal', 0, excl),
            cargarEquiposTipo(csPeriferico, tipoPerif,   0, excl),
            cargarEquiposTipo(csSoftware,   'software',  0, excl),
        ]);
    }

    /* ── Sync chips de IPs ── */
    function syncIps() {
        renderIpChips('nuevoIpChips', 'nuevoIpsIds', ips, recargarComboIp);
    }

    /* ── Renderizar todas las listas ── */
    function renderAll() {
        renderListaEquipos('nuevoEquipoPrincipalLista', null, principal, 'eq-principal', () => {
            sync();
            lockPrincipal(false);
            adaptarSidebar('');
            recargarCombos();
        });
        renderListaEquipos('nuevoPerifericosLista', 'nuevoPerifericosContador', perifericos, 'eq-periferico', () => {
            sync();
            recargarCombos();
        });
        renderListaEquipos('nuevoSoftwareLista', 'nuevoSoftwareContador', software, 'eq-software', () => {
            sync();
            recargarCombos();
        });
        syncIps();
        sync();
        lockPrincipal(principal.length > 0);
    }

    /* ════ CARGA INICIAL ════ */
    await Promise.all([recargarCombos(), recargarComboIp()]);
    renderAll();

    /* ── Listeners: cambio en selects de equipos ── */
    document.getElementById('nuevoEquipoPrincipalSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarNuevoPrincipal').disabled = !this.value || principal.length > 0;
    });
    document.getElementById('nuevoPerifericoSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarNuevoPeriferico').disabled = !this.value;
    });
    document.getElementById('nuevoSoftwareSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarNuevoSoftware').disabled = !this.value;
    });

    /* ── Listener: cambio en select de IP ── */
    document.getElementById('nuevoIpSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarNuevoIp').disabled = !this.value;
    });

    /* ── Agregar IP ── */
    document.getElementById('btnAgregarNuevoIp')?.addEventListener('click', () => {
        const val    = csIp.getValue();
        if (!val) return;
        const ipData = csIp._ipData?.[val];
        if (!ipData) return;
        if (ips.some(ip => String(ip.idIp) === val)) {
            mostrarToast('warning', 'Esta IP ya está en la lista.');
            return;
        }
        ips.push({ idIp: parseInt(val), ipAddress: ipData.ipAddress });
        csIp.reset();
        document.getElementById('btnAgregarNuevoIp').disabled = true;
        syncIps();
        recargarComboIp();
    });

    /* ── Agregar equipo principal ── */
    document.getElementById('btnAgregarNuevoPrincipal')?.addEventListener('click', () => {
        const val = csPrincipal.getValue();
        if (!val || principal.length) return;
        const eq = csPrincipal._data?.[val];
        if (!eq) return;
        principal = [{ idActivo: val, ...eq }];
        csPrincipal.reset();
        adaptarSidebar(eq.nombreActivo || eq.label || '');
        renderAll();
        recargarCombos();
    });

    /* ── Agregar periférico ── */
    document.getElementById('btnAgregarNuevoPeriferico')?.addEventListener('click', () => {
        const val = csPeriferico.getValue();
        if (!val) return;
        if (perifericos.some(e => e.idActivo === val)) {
            mostrarToast('warning', 'Ya está en la lista.');
            return;
        }
        const eq = csPeriferico._data?.[val];
        if (!eq) return;
        perifericos.push({ idActivo: val, ...eq });
        csPeriferico.reset();
        document.getElementById('btnAgregarNuevoPeriferico').disabled = true;
        renderAll();
        recargarCombos();
    });

    /* ── Agregar software ── */
    document.getElementById('btnAgregarNuevoSoftware')?.addEventListener('click', () => {
        const val = csSoftware.getValue();
        if (!val) return;
        if (software.some(e => e.idActivo === val)) {
            mostrarToast('warning', 'Ya está en la lista.');
            return;
        }
        const eq = csSoftware._data?.[val];
        if (!eq) return;
        software.push({ idActivo: val, ...eq });
        csSoftware.reset();
        document.getElementById('btnAgregarNuevoSoftware').disabled = true;
        renderAll();
        recargarCombos();
    });

    /* ════ SUBMIT FORMULARIO ════ */
    document.getElementById('formNuevaEstacion')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        sync();
        syncIps();
        const btn = document.getElementById('btnGuardar');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:14px;height:14px;margin-right:.4rem"></span>Guardando...';
        try {
            const resp = await fetch(AJAX_EST, { method: 'POST', body: new FormData(this) });
            const data = await resp.json();
            const r    = (data.resultado ?? '').trim();
            const m    = (data.mensaje   ?? '').trim();
            if (r === 'ok') {
                mostrarToast('success', m || 'Estación creada correctamente.');
                setTimeout(() => { window.location.href = URL_LISTA; }, 1200);
            } else if (r === 'error_duplicado') {
                mostrarToast('warning', m);
                btn.disabled  = false;
                btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar Estación';
            } else {
                mostrarToast('error', m || 'Ocurrió un error.');
                btn.disabled  = false;
                btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar Estación';
            }
        } catch {
            mostrarToast('error', 'Error de servidor.');
            btn.disabled  = false;
            btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar Estación';
        }
    });

}); // fin DOMContentLoaded
