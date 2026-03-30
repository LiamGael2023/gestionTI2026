/* =============================================================
   ESTACION_EDITAR.JS  — Lógica exclusiva de la vista Editar
   Requiere: estaciones_form.js cargado antes
   Requiere: constantes globales declaradas en estacion_editar.php
       const AJAX_EST            = '...';
       const URL_LISTA           = '...';
       const ESTACION_ID         = <int>;
       const GRUPOS_INICIAL      = { principal:[], perifericos:[], software:[] };
       const TIPO_PRINCIPAL_INICIAL = '...';
       const IPS_INICIAL         = [{idIp, ipAddress}, ...];
============================================================= */

document.addEventListener('DOMContentLoaded', async function () {

    initTogglePass();

    /* ── Custom Selects ── */
    const csPrincipal  = crearCustomSelect('editarEquipoPrincipalSelect');
    const csPeriferico = crearCustomSelect('editarPerifericoSelect');
    const csSoftware   = crearCustomSelect('editarSoftwareSelect');
    const csIp         = crearCustomSelect('editarIpSelect');

    /* ── Estado reactivo (pre-poblado desde PHP vía constantes) ── */
    let principal   = GRUPOS_INICIAL.principal.map(e   => ({ ...e, idActivo: String(e.idActivo) }));
    let perifericos = GRUPOS_INICIAL.perifericos.map(e  => ({ ...e, idActivo: String(e.idActivo) }));
    let software    = GRUPOS_INICIAL.software.map(e    => ({ ...e, idActivo: String(e.idActivo) }));
    let ips         = IPS_INICIAL.map(ip => ({ idIp: ip.idIp, ipAddress: ip.ipAddress }));

    /* Pre-rellenar hidden de IPs para que no se vacíe si el usuario
       guarda sin tocar las IPs */
    (function preRellenarIpsHidden() {
        const h = document.getElementById('editarIpsIds');
        if (h) h.value = ips.map(ip => ip.idIp).join(',');
    })();

    let _tipoPrincipalEdit             = '';
    let _filtroPerifericoImpresoraEdit = false;

    /* ── Sync hiddens de equipos ── */
    function sync() {
        sincronizarHiddens('editar', principal, perifericos, software);
    }

    /* ── Lock/unlock selector de principal ── */
    function lockPrincipal(lock) {
        const wP = document.getElementById('cswrap_editarEquipoPrincipalSelect');
        if (wP) {
            wP.style.opacity       = lock ? '.4' : '1';
            wP.style.pointerEvents = lock ? 'none' : 'auto';
        }
        document.getElementById('btnAgregarEditarPrincipal').disabled = lock;
    }

    /* ── Adaptar sidebar según tipo de equipo principal ── */
    function adaptarSidebar(tipo) {
        _tipoPrincipalEdit = (tipo || '').toUpperCase();
        const esServidor  = _tipoPrincipalEdit.includes('SERVIDOR');
        const esImpresora = _tipoPrincipalEdit.includes('IMPRESORA');
        const esLaptopCpu = _tipoPrincipalEdit.includes('LAPTOP') || _tipoPrincipalEdit.includes('CPU');

        document.getElementById('editarBloqueDireccionFisica').style.display = esLaptopCpu ? '' : 'none';

        const tituloEl = document.getElementById('editarTituloAcceso');
        const iconoEl  = document.getElementById('editarIconoAcceso');
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

        const bloqueCodigoEl = document.getElementById('editarBloqueCodigo');
        const labelCodigoEl  = document.getElementById('editarLabelCodigo');
        if (esServidor) {
            bloqueCodigoEl.style.display = 'none';
        } else if (esImpresora) {
            bloqueCodigoEl.style.display = '';
            labelCodigoEl.textContent    = 'Usuario';
        } else {
            bloqueCodigoEl.style.display = '';
            labelCodigoEl.textContent    = 'Código Anydesk';
        }

        const labelPassEl = document.getElementById('editarLabelContrasena');
        if (labelPassEl) labelPassEl.textContent = (esServidor || esImpresora) ? 'Contraseña' : 'Contraseña Anydesk';

        _filtroPerifericoImpresoraEdit = esImpresora;
    }

    /* ── Recargar combo IPs (excluye las ya asignadas) ── */
    async function recargarComboIp() {
        const idsEnUso = ips.map(ip => ip.idIp);
        await cargarIps(csIp, ESTACION_ID, idsEnUso);
        document.getElementById('btnAgregarEditarIp').disabled = true;
    }

    /* ── Recargar combos de equipos ── */
    async function recargarCombos() {
        const excl      = idsExcluir(principal, perifericos, software);
        const tipoPerif = _filtroPerifericoImpresoraEdit ? 'ups_estabilizador' : 'periferico';
        await Promise.all([
            cargarEquiposTipo(csPrincipal,  'principal', ESTACION_ID, excl),
            cargarEquiposTipo(csPeriferico, tipoPerif,   ESTACION_ID, excl),
            cargarEquiposTipo(csSoftware,   'software',  ESTACION_ID, excl),
        ]);
    }

    /* ── Sync chips de IPs ── */
    function syncIps() {
        renderIpChips('editarIpChips', 'editarIpsIds', ips, recargarComboIp);
    }

    /* ── Renderizar todas las listas ── */
    function renderAll() {
        renderListaEquipos('editarEquipoPrincipalLista', null, principal, 'eq-principal', () => {
            sync();
            lockPrincipal(false);
            adaptarSidebar('');
            recargarCombos();
        });
        renderListaEquipos('editarPerifericosLista', 'editarPerifericosContador', perifericos, 'eq-periferico', () => {
            sync();
            recargarCombos();
        });
        renderListaEquipos('editarSoftwareLista', 'editarSoftwareContador', software, 'eq-software', () => {
            sync();
            recargarCombos();
        });
        syncIps();
        sync();
        lockPrincipal(principal.length > 0);
    }

    /* ════ CARGA INICIAL ════ */
    await Promise.all([recargarCombos(), recargarComboIp()]);
    adaptarSidebar(TIPO_PRINCIPAL_INICIAL || '');
    renderAll();
    document.getElementById('btnAgregarEditarPeriferico').disabled = true;
    document.getElementById('btnAgregarEditarSoftware').disabled   = true;

    /* ── Listeners: cambio en selects de equipos ── */
    document.getElementById('editarEquipoPrincipalSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarEditarPrincipal').disabled = !this.value || principal.length > 0;
    });
    document.getElementById('editarPerifericoSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarEditarPeriferico').disabled = !this.value;
    });
    document.getElementById('editarSoftwareSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarEditarSoftware').disabled = !this.value;
    });

    /* ── Listener: cambio en select de IP ── */
    document.getElementById('editarIpSelect')?.addEventListener('change', function () {
        document.getElementById('btnAgregarEditarIp').disabled = !this.value;
    });

    /* ── Agregar IP ── */
    document.getElementById('btnAgregarEditarIp')?.addEventListener('click', () => {
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
        document.getElementById('btnAgregarEditarIp').disabled = true;
        syncIps();
        recargarComboIp();
    });

    /* ── Agregar equipo principal ── */
    document.getElementById('btnAgregarEditarPrincipal')?.addEventListener('click', () => {
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
    document.getElementById('btnAgregarEditarPeriferico')?.addEventListener('click', () => {
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
        document.getElementById('btnAgregarEditarPeriferico').disabled = true;
        renderAll();
        recargarCombos();
    });

    /* ── Agregar software ── */
    document.getElementById('btnAgregarEditarSoftware')?.addEventListener('click', () => {
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
        document.getElementById('btnAgregarEditarSoftware').disabled = true;
        renderAll();
        recargarCombos();
    });

    /* ════ SUBMIT FORMULARIO ════ */
    document.getElementById('formEditarEstacion')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        sync();
        syncIps();
        const btn = document.getElementById('btnActualizar');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:14px;height:14px;margin-right:.4rem"></span>Actualizando...';
        try {
            const resp = await fetch(AJAX_EST, { method: 'POST', body: new FormData(this) });
            const data = await resp.json();
            const r    = (data.resultado ?? '').trim();
            const m    = (data.mensaje   ?? '').trim();
            if (r === 'ok') {
                mostrarToast('success', m || 'Estación actualizada.');
                setTimeout(() => { window.location.href = URL_LISTA; }, 1200);
            } else if (r === 'error_duplicado') {
                mostrarToast('warning', m);
                btn.disabled  = false;
                btn.innerHTML = '<i class="ti ti-check"></i> Actualizar Estación';
            } else {
                mostrarToast('error', m || 'Error.');
                btn.disabled  = false;
                btn.innerHTML = '<i class="ti ti-check"></i> Actualizar Estación';
            }
        } catch {
            mostrarToast('error', 'Error de servidor.');
            btn.disabled  = false;
            btn.innerHTML = '<i class="ti ti-check"></i> Actualizar Estación';
        }
    });

}); // fin DOMContentLoaded
