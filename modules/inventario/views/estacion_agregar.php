<body>
<?php if (session_status() == PHP_SESSION_NONE) { session_start(); } ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

:root {
  --blue:       #2563eb;
  --blue-lt:    #eff6ff;
  --blue-md:    #bfdbfe;
  --green:      #16a34a;
  --green-lt:   #f0fdf4;
  --green-md:   #bbf7d0;
  --purple:     #7c3aed;
  --purple-lt:  #f5f3ff;
  --purple-md:  #ddd6fe;
  --gray-50:    #f8fafc;
  --gray-100:   #f1f5f9;
  --gray-200:   #e2e8f0;
  --gray-300:   #cbd5e1;
  --gray-400:   #94a3b8;
  --gray-600:   #475569;
  --gray-800:   #1e293b;
  --radius:     8px;
  --shadow-sm:  0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.05);
  --shadow-md:  0 4px 16px rgba(0,0,0,.08), 0 2px 6px rgba(0,0,0,.04);
  --shadow-lg:  0 10px 32px rgba(0,0,0,.1), 0 4px 12px rgba(0,0,0,.06);
}

/* Custom Select */
.cs-wrap{position:relative;width:100%}
.cs-display{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;min-height:40px;background:#fff;border:1.5px solid var(--gray-200);border-radius:var(--radius);cursor:pointer;outline:none;transition:all .15s;font-family:'DM Sans',sans-serif}
.cs-display:hover{border-color:var(--blue)}
.cs-wrap.cs-open .cs-display{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.cs-text{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--gray-800);font-size:.855rem}
.cs-text.placeholder-text{color:var(--gray-400)}
.cs-arrow{color:var(--gray-400);transition:transform .2s;flex-shrink:0}
.cs-wrap.cs-open .cs-arrow{transform:rotate(180deg);color:var(--blue)}
.cs-panel{display:none;position:absolute;left:0;right:0;z-index:9999;background:#fff;border:1.5px solid var(--gray-200);border-radius:10px;box-shadow:var(--shadow-lg);overflow:hidden;min-width:200px}
.cs-wrap.cs-open .cs-panel{display:block;animation:csIn .12s ease}
@keyframes csIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.cs-search-row{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;border-bottom:1.5px solid var(--gray-100);background:var(--gray-50)}
.cs-search-row i{color:var(--gray-400);font-size:.9rem}
.cs-search{border:none;outline:none;background:transparent;font-size:.82rem;width:100%;color:var(--gray-800);font-family:'DM Sans',sans-serif}
.cs-list{list-style:none;margin:0;padding:.3rem 0;max-height:200px;overflow-y:auto}
.cs-list li{padding:.42rem .75rem;cursor:pointer;font-size:.845rem;color:var(--gray-800);transition:background .1s;font-family:'DM Sans',sans-serif}
.cs-list li:hover,.cs-list li.cs-selected{background:var(--blue-lt);color:var(--blue)}
.cs-list li.cs-selected{font-weight:600}
.cs-list li.cs-placeholder-item,.cs-list li.cs-empty{color:var(--gray-400);font-style:italic;cursor:default}
.cs-list li.cs-empty:hover,.cs-list li.cs-placeholder-item:hover{background:transparent}

/* Layout principal */
.est-layout{
  display:grid;
  grid-template-columns:300px 1fr;
  min-height:calc(100vh - 56px - 64px);
  background:var(--gray-50);
  font-family:'DM Sans',sans-serif;
}

/* Sidebar */
.est-sidebar{
  background:#fff;
  border-right:1.5px solid var(--gray-200);
  padding:1.5rem 1.25rem;
  display:flex;
  flex-direction:column;
  gap:0;
}
.sidebar-section-title{
  font-size:.65rem;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.1em;
  color:var(--gray-400);
  margin:0 0 .65rem;
  padding-bottom:.4rem;
  border-bottom:1.5px solid var(--gray-100);
  display:flex;align-items:center;gap:.4rem;
}
.sidebar-section-title i{font-size:.8rem}
.sidebar-block{margin-bottom:1.25rem}

.fld-label{
  display:block;
  font-size:.78rem;
  font-weight:600;
  color:var(--gray-600);
  margin-bottom:.3rem;
  letter-spacing:.01em;
}
.fld-label .req{color:#ef4444;margin-left:2px}
.fld-input{
  width:100%;
  padding:.5rem .75rem;
  border:1.5px solid var(--gray-200);
  border-radius:var(--radius);
  font-size:.855rem;
  color:var(--gray-800);
  background:#fff;
  transition:all .15s;
  font-family:'DM Sans',sans-serif;
  outline:none;
}
.fld-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.fld-input::placeholder{color:var(--gray-400)}
.pass-wrap{display:flex;gap:0}
.pass-wrap .fld-input{border-radius:var(--radius) 0 0 var(--radius)}
.pass-toggle{
  padding:0 .75rem;
  border:1.5px solid var(--gray-200);
  border-left:none;
  border-radius:0 var(--radius) var(--radius) 0;
  background:#fff;
  color:var(--gray-400);
  cursor:pointer;
  transition:all .15s;
  display:flex;align-items:center;
  font-size:.95rem;
}
.pass-toggle:hover{background:var(--blue-lt);color:var(--blue);border-color:var(--blue)}

/* Main area */
.est-main{
  padding:1.5rem;
  display:flex;
  flex-direction:column;
  gap:1rem;
  overflow-y:auto;
}

/* Secciones de equipos */
.eq-card{
  background:#fff;
  border-radius:12px;
  border:1.5px solid var(--gray-200);
  overflow:hidden;
  box-shadow:var(--shadow-sm);
  transition:box-shadow .2s;
}
.eq-card:hover{box-shadow:var(--shadow-md)}

.eq-card-header{
  display:flex;
  align-items:center;
  gap:.75rem;
  padding:.85rem 1.1rem;
  border-bottom:1.5px solid var(--gray-100);
  position:relative;
  overflow:hidden;
}
.eq-card-header::before{
  content:'';
  position:absolute;
  inset:0;
  opacity:.06;
  pointer-events:none;
}
.eq-icon-wrap{
  width:36px;height:36px;
  border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.05rem;
  flex-shrink:0;
  position:relative;z-index:1;
}
.eq-header-text{flex:1;position:relative;z-index:1}
.eq-header-title{font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;line-height:1.2}
.eq-header-sub{font-size:.7rem;color:var(--gray-400);margin-top:.1rem}
.eq-badge{
  font-size:.72rem;font-weight:700;
  padding:.2rem .6rem;
  border-radius:20px;
  position:relative;z-index:1;
}
.eq-card-body{padding:.9rem 1.1rem}
.eq-search-bar{display:flex;gap:.5rem;align-items:flex-end;margin-bottom:.75rem}
.eq-search-label{font-size:.73rem;font-weight:600;color:var(--gray-600);margin-bottom:.28rem;display:block}

/* Botón agregar */
.btn-eq-add{
  width:40px;height:40px;
  border:none;border-radius:var(--radius);
  display:flex;align-items:center;justify-content:center;
  font-size:1.05rem;
  cursor:pointer;
  transition:all .15s;
  flex-shrink:0;
  align-self:flex-end;
  box-shadow:var(--shadow-sm);
}
.btn-eq-add:disabled{opacity:.35;cursor:not-allowed;box-shadow:none}
.btn-eq-add:not(:disabled):hover{transform:translateY(-1px);box-shadow:var(--shadow-md)}

/* Items */
.eq-items{min-height:44px}
.eq-empty{
  display:flex;align-items:center;justify-content:center;gap:.5rem;
  padding:.75rem;
  color:var(--gray-400);
  font-size:.8rem;
  font-style:italic;
  background:var(--gray-50);
  border-radius:var(--radius);
  border:1.5px dashed var(--gray-200);
}
.eq-item{
  display:flex;align-items:center;gap:.6rem;
  padding:.42rem .65rem;
  background:var(--gray-50);
  border:1.5px solid var(--gray-200);
  border-radius:var(--radius);
  margin-bottom:.3rem;
  transition:all .15s;
}
.eq-item:hover{background:#fff;box-shadow:var(--shadow-sm)}
.eq-item-ico{
  width:30px;height:30px;
  border-radius:7px;
  display:flex;align-items:center;justify-content:center;
  font-size:.85rem;flex-shrink:0;
}
.eq-item-body{flex:1;overflow:hidden}
.eq-item-name{font-size:.82rem;font-weight:600;color:var(--gray-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.eq-item-meta{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.1rem}
.eq-item-cp{font-size:.7rem;font-weight:600;padding:.05rem .35rem;border-radius:4px}
.eq-item-sn{font-size:.7rem;color:var(--gray-400)}
.btn-eq-rm{
  width:26px;height:26px;
  border-radius:6px;
  border:1.5px solid #fecaca;
  background:#fff5f5;
  color:#ef4444;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;
  font-size:.75rem;
  flex-shrink:0;
  transition:all .15s;
}
.btn-eq-rm:hover{background:#ef4444;color:#fff;border-color:#ef4444}

/* Colores principal */
.eq-principal .eq-card-header{background:linear-gradient(135deg,var(--blue-lt) 0%,#dbeafe 100%)}
.eq-principal .eq-card-header::before{background:var(--blue)}
.eq-principal .eq-icon-wrap{background:var(--blue);color:#fff}
.eq-principal .eq-header-title{color:var(--blue)}
.eq-principal .eq-badge{background:var(--blue-md);color:var(--blue)}
.eq-principal .btn-eq-add{background:var(--blue);color:#fff}
.eq-principal .btn-eq-add:not(:disabled):hover{background:#1d4ed8}
.eq-principal .eq-item-ico{background:var(--blue-lt);color:var(--blue)}
.eq-principal .eq-item-cp{background:var(--blue-lt);color:var(--blue)}

/* Colores periférico */
.eq-periferico .eq-card-header{background:linear-gradient(135deg,var(--green-lt) 0%,#dcfce7 100%)}
.eq-periferico .eq-card-header::before{background:var(--green)}
.eq-periferico .eq-icon-wrap{background:var(--green);color:#fff}
.eq-periferico .eq-header-title{color:var(--green)}
.eq-periferico .eq-badge{background:var(--green-md);color:var(--green)}
.eq-periferico .btn-eq-add{background:var(--green);color:#fff}
.eq-periferico .btn-eq-add:not(:disabled):hover{background:#15803d}
.eq-periferico .eq-item-ico{background:var(--green-lt);color:var(--green)}
.eq-periferico .eq-item-cp{background:var(--green-lt);color:var(--green)}

/* Colores software */
.eq-software .eq-card-header{background:linear-gradient(135deg,var(--purple-lt) 0%,#ede9fe 100%)}
.eq-software .eq-card-header::before{background:var(--purple)}
.eq-software .eq-icon-wrap{background:var(--purple);color:#fff}
.eq-software .eq-header-title{color:var(--purple)}
.eq-software .eq-badge{background:var(--purple-md);color:var(--purple)}
.eq-software .btn-eq-add{background:var(--purple);color:#fff}
.eq-software .btn-eq-add:not(:disabled):hover{background:#6d28d9}
.eq-software .eq-item-ico{background:var(--purple-lt);color:var(--purple)}
.eq-software .eq-item-cp{background:var(--purple-lt);color:var(--purple)}

/* Footer sticky */
.est-footer{
  background:#fff;
  border-top:1.5px solid var(--gray-200);
  padding:.9rem 1.5rem;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;bottom:0;z-index:100;
  box-shadow:0 -4px 16px rgba(0,0,0,.06);
  font-family:'DM Sans',sans-serif;
}
.footer-left{display:flex;align-items:center;gap:.5rem}
.footer-hint{font-size:.75rem;color:var(--gray-400)}
.footer-hint i{color:var(--blue);opacity:.7}
.btn-cancel{
  display:inline-flex;align-items:center;gap:.4rem;
  padding:.52rem 1rem;
  border-radius:var(--radius);
  border:1.5px solid var(--gray-200);
  background:#fff;
  color:var(--gray-600);
  font-size:.855rem;font-weight:500;
  cursor:pointer;transition:all .15s;
  text-decoration:none;
  font-family:'DM Sans',sans-serif;
}
.btn-cancel:hover{border-color:var(--gray-300);background:var(--gray-50);color:var(--gray-800)}
.btn-save{
  display:inline-flex;align-items:center;gap:.45rem;
  padding:.52rem 1.4rem;
  border-radius:var(--radius);
  border:none;
  background:var(--blue);
  color:#fff;
  font-size:.875rem;font-weight:600;
  cursor:pointer;transition:all .15s;
  box-shadow:0 2px 8px rgba(37,99,235,.35);
  font-family:'DM Sans',sans-serif;
}
.btn-save:hover:not(:disabled){background:#1d4ed8;box-shadow:0 4px 14px rgba(37,99,235,.45);transform:translateY(-1px)}
.btn-save:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;transform:none}
.btn-save i,.btn-cancel i{font-size:.9rem}

/* Breadcrumb */
.est-breadcrumb{
  display:flex;align-items:center;gap:.35rem;
  font-size:.75rem;color:var(--gray-400);
  margin-bottom:.35rem;
  font-family:'DM Sans',sans-serif;
}
.est-breadcrumb a{color:var(--gray-400);text-decoration:none;transition:color .15s;display:flex;align-items:center;gap:.25rem}
.est-breadcrumb a:hover{color:var(--blue)}
.est-breadcrumb span{color:var(--gray-300)}
.est-title{font-size:1.35rem;font-weight:700;color:var(--gray-800);font-family:'DM Sans',sans-serif;margin-bottom:1rem}

/* Responsive */
@media(max-width:900px){
  .est-layout{grid-template-columns:1fr}
  .est-sidebar{border-right:none;border-bottom:1.5px solid var(--gray-200)}
}
</style>

<div class="page">
<?php include __DIR__ . '/_submenu.php'; ?>

<div style="background:var(--gray-50,#f8fafc);font-family:'DM Sans',sans-serif;padding:1.25rem 1.5rem 0">
  <div class="container-xl">
    <div class="est-breadcrumb">
      <a href="?module=inventario&action=estaciones"><i class="ti ti-arrow-left"></i>Estaciones</a>
      <span>/</span>
      <span style="color:#64748b">Nueva Estación</span>
    </div>
    <div class="est-title">Nueva Estación</div>
  </div>
</div>

<div style="background:var(--gray-50,#f8fafc)">
  <div class="container-xl">
    <form id="formNuevaEstacion" novalidate>

      <div class="est-layout" style="border-radius:14px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07);margin-bottom:0">

        <!-- SIDEBAR -->
        <div class="est-sidebar">

          <div class="sidebar-block">
            <div class="sidebar-section-title"><i class="ti ti-info-circle"></i> Información</div>
            <div class="fld-group" style="margin-bottom:.75rem">
              <label class="fld-label">Nombre <span class="req">*</span></label>
              <input class="fld-input" type="text" placeholder="Ej: ESTACION-CONTA-01"
                     name="nuevoNombreEstacion" id="nuevoNombreEstacion" required>
            </div>
            <div class="fld-group" style="margin-bottom:0">
              <label class="fld-label">IP Asignada</label>
              <select id="nuevoIdIp" name="nuevoIdIp" style="display:none">
                <option value="">Sin IP asignada</option>
              </select>
            </div>
          </div>

          <div class="sidebar-block">
            <div class="sidebar-section-title"><i class="ti ti-device-desktop"></i> Acceso Remoto</div>
            <div class="fld-group" style="margin-bottom:.75rem">
              <label class="fld-label">Código Anydesk</label>
              <input class="fld-input font-monospace" type="text" placeholder="123 456 789"
                     name="nuevoCodigoAnydesk" id="nuevoCodigoAnydesk">
            </div>
            <div class="fld-group" style="margin-bottom:0">
              <label class="fld-label">Contraseña Anydesk</label>
              <div class="pass-wrap">
                <input class="fld-input font-monospace" type="password"
                       name="nuevoContrasenaAnydesk" id="nuevoContrasenaAnydesk">
                <button type="button" class="pass-toggle btnTogglePass" data-target="nuevoContrasenaAnydesk">
                  <i class="ti ti-eye"></i>
                </button>
              </div>
            </div>
          </div>

        </div>

        <!-- MAIN -->
        <div class="est-main">

          <!-- Equipo principal -->
          <div class="eq-card eq-principal">
            <div class="eq-card-header">
              <div class="eq-icon-wrap"><i class="ti ti-cpu"></i></div>
              <div class="eq-header-text">
                <div class="eq-header-title">Equipo Principal</div>
                <div class="eq-header-sub">CPU, Laptop, Servidor — máximo 1 equipo</div>
              </div>
            </div>
            <div class="eq-card-body">
              <div class="eq-search-bar">
                <div style="flex:1">
                  <span class="eq-search-label">Buscar por código patrimonial</span>
                  <select id="nuevoEquipoPrincipalSelect" style="display:none"><option value="">Seleccionar...</option></select>
                </div>
                <button type="button" id="btnAgregarNuevoPrincipal" class="btn-eq-add" disabled title="Agregar equipo">
                  <i class="ti ti-plus"></i>
                </button>
              </div>
              <div id="nuevoEquipoPrincipalLista" class="eq-items"></div>
              <input type="hidden" id="nuevoEquipoPrincipalId" name="nuevoEquipoPrincipalId">
            </div>
          </div>

          <!-- Periféricos + Software en row -->
          <div class="row g-3">
            <div class="col-md-6">
              <div class="eq-card eq-periferico h-100">
                <div class="eq-card-header">
                  <div class="eq-icon-wrap"><i class="ti ti-devices"></i></div>
                  <div class="eq-header-text">
                    <div class="eq-header-title">Periféricos</div>
                    <div class="eq-header-sub">Monitor, mouse, teclado, UPS…</div>
                  </div>
                  <span class="eq-badge" id="nuevoPerifericosContador">0</span>
                </div>
                <div class="eq-card-body">
                  <div class="eq-search-bar">
                    <div style="flex:1">
                      <span class="eq-search-label">Buscar por código patrimonial</span>
                      <select id="nuevoPerifericoSelect" style="display:none"><option value="">Seleccionar...</option></select>
                    </div>
                    <button type="button" id="btnAgregarNuevoPeriferico" class="btn-eq-add" disabled>
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <div id="nuevoPerifericosLista" class="eq-items"></div>
                  <input type="hidden" id="nuevoPerifericosIds" name="nuevoPerifericosIds">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="eq-card eq-software h-100">
                <div class="eq-card-header">
                  <div class="eq-icon-wrap"><i class="ti ti-brand-windows"></i></div>
                  <div class="eq-header-text">
                    <div class="eq-header-title">Software</div>
                    <div class="eq-header-sub">Licencias y aplicaciones instaladas</div>
                  </div>
                  <span class="eq-badge" id="nuevoSoftwareContador">0</span>
                </div>
                <div class="eq-card-body">
                  <div class="eq-search-bar">
                    <div style="flex:1">
                      <span class="eq-search-label">Buscar por nombre</span>
                      <select id="nuevoSoftwareSelect" style="display:none"><option value="">Seleccionar software...</option></select>
                    </div>
                    <button type="button" id="btnAgregarNuevoSoftware" class="btn-eq-add" disabled>
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <div id="nuevoSoftwareLista" class="eq-items"></div>
                  <input type="hidden" id="nuevoSoftwareIds" name="nuevoSoftwareIds">
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- FOOTER STICKY -->
      <div class="est-footer" style="margin:0 0 0">
        <div class="footer-left">
          <i class="ti ti-info-circle" style="color:#2563eb;font-size:.9rem"></i>
          <span class="footer-hint">Los campos marcados con <strong style="color:#ef4444">*</strong> son obligatorios</span>
        </div>
        <div style="display:flex;gap:.6rem;align-items:center">
          <a href="?module=inventario&action=estaciones" class="btn-cancel">
            <i class="ti ti-x"></i> Cancelar
          </a>
          <button type="submit" class="btn-save" id="btnGuardar">
            <i class="ti ti-device-floppy"></i> Guardar Estación
          </button>
        </div>
      </div>

    </form>
  </div>
</div>
</div>

<div id="toastContainerEstaciones" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div>
<script src="modules/inventario/views/js/estacion_form.js"></script>
<script>
function renderItemNuevo(eq, colorClass, onQuitar) {
    const d = document.createElement('div');
    d.className = 'eq-item';
    const ico = escHtml(eq.iconoActivo ?? 'ti-package');
    const nombre = escHtml(eq.nombreActivo ?? eq.label ?? '');
    const cp = escHtml(eq.codigoPatrimonial ?? '');
    const sn = escHtml(eq.numeroSerie ?? '');
    d.innerHTML = `
        <div class="eq-item-ico"><i class="ti ${ico}"></i></div>
        <div class="eq-item-body">
            <div class="eq-item-name">${nombre}</div>
            <div class="eq-item-meta">
                ${cp ? `<span class="eq-item-cp">${cp}</span>` : ''}
                ${sn ? `<span class="eq-item-sn">S/N: ${sn}</span>` : ''}
            </div>
        </div>
        <button type="button" class="btn-eq-rm" title="Quitar"><i class="ti ti-x"></i></button>`;
    d.querySelector('.btn-eq-rm').addEventListener('click', onQuitar);
    return d;
}

function renderListaNuevo(listaId, contadorId, arr, colorClass, onAfterQuitar) {
    const lista = document.getElementById(listaId);
    const cont  = document.getElementById(contadorId);
    if (!lista) return;
    if (cont) cont.textContent = arr.length;
    lista.innerHTML = '';
    if (!arr.length) {
        lista.innerHTML = `<div class="eq-empty"><i class="ti ti-inbox-off" style="font-size:1rem"></i> Sin ítems asignados</div>`;
        return;
    }
    arr.forEach((eq, idx) => {
        const el = renderItemNuevo(eq, colorClass, () => {
            arr.splice(idx, 1);
            renderListaNuevo(listaId, contadorId, arr, colorClass, onAfterQuitar);
            if (onAfterQuitar) onAfterQuitar();
        });
        lista.appendChild(el);
    });
}

document.addEventListener('DOMContentLoaded', async function () {
    initTogglePass();
    const csIp         = crearCustomSelect('nuevoIdIp');
    const csPrincipal  = crearCustomSelect('nuevoEquipoPrincipalSelect');
    const csPeriferico = crearCustomSelect('nuevoPerifericoSelect');
    const csSoftware   = crearCustomSelect('nuevoSoftwareSelect');

    let principal=[], perifericos=[], software=[];

    function sync(){ sincronizarHiddens('nuevo', principal, perifericos, software); }

    function lockPrincipal(lock) {
        const wP = document.getElementById('cswrap_nuevoEquipoPrincipalSelect');
        if (wP) { wP.style.opacity = lock ? '.4' : '1'; wP.style.pointerEvents = lock ? 'none' : 'auto'; }
        document.getElementById('btnAgregarNuevoPrincipal').disabled = lock;
    }

    function renderAll() {
        renderListaNuevo('nuevoEquipoPrincipalLista', null, principal, 'eq-principal', () => { sync(); lockPrincipal(false); recargarCombos(); });
        renderListaNuevo('nuevoPerifericosLista', 'nuevoPerifericosContador', perifericos, 'eq-periferico', () => { sync(); recargarCombos(); });
        renderListaNuevo('nuevoSoftwareLista', 'nuevoSoftwareContador', software, 'eq-software', () => { sync(); recargarCombos(); });
        sync();
        lockPrincipal(principal.length > 0);
    }

    async function recargarCombos() {
        const excl = idsExcluir(principal, perifericos, software);
        await Promise.all([
            cargarEquiposTipo(csPrincipal,  'principal',  0, excl),
            cargarEquiposTipo(csPeriferico, 'periferico', 0, excl),
            cargarEquiposTipo(csSoftware,   'software',   0, excl),
        ]);
    }

    await Promise.all([cargarIps(csIp), recargarCombos()]);
    renderAll();

    document.getElementById('nuevoEquipoPrincipalSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarNuevoPrincipal').disabled = !this.value || principal.length > 0;
    });
    document.getElementById('nuevoPerifericoSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarNuevoPeriferico').disabled = !this.value;
    });
    document.getElementById('nuevoSoftwareSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarNuevoSoftware').disabled = !this.value;
    });

    document.getElementById('btnAgregarNuevoPrincipal')?.addEventListener('click', () => {
        const val = csPrincipal.getValue(); if (!val || principal.length) return;
        const eq = csPrincipal._data?.[val]; if (!eq) return;
        principal = [{idEquipo:val,...eq}]; csPrincipal.reset();
        renderAll(); recargarCombos();
    });
    document.getElementById('btnAgregarNuevoPeriferico')?.addEventListener('click', () => {
        const val = csPeriferico.getValue(); if (!val) return;
        if (perifericos.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq = csPeriferico._data?.[val]; if (!eq) return;
        perifericos.push({idEquipo:val,...eq}); csPeriferico.reset();
        document.getElementById('btnAgregarNuevoPeriferico').disabled = true;
        renderAll(); recargarCombos();
    });
    document.getElementById('btnAgregarNuevoSoftware')?.addEventListener('click', () => {
        const val = csSoftware.getValue(); if (!val) return;
        if (software.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq = csSoftware._data?.[val]; if (!eq) return;
        software.push({idEquipo:val,...eq}); csSoftware.reset();
        document.getElementById('btnAgregarNuevoSoftware').disabled = true;
        renderAll(); recargarCombos();
    });

    document.getElementById('formNuevaEstacion')?.addEventListener('submit', async function(e) {
        e.preventDefault(); sync();
        const btn = document.getElementById('btnGuardar'); btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:14px;height:14px;margin-right:.4rem"></span>Guardando...';
        try {
            const resp = await fetch(AJAX_EST, {method:'POST', body:new FormData(this)});
            const data = await resp.json();
            const r = (data.resultado??'').trim(), m = (data.mensaje??'').trim();
            if (r==='ok') {
                mostrarToast('success', m || 'Estación creada correctamente.');
                setTimeout(() => { window.location.href = URL_LISTA; }, 1200);
            } else if (r==='error_duplicado') {
                mostrarToast('warning', m);
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar Estación';
            } else {
                mostrarToast('error', m || 'Ocurrió un error.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar Estación';
            }
        } catch {
            mostrarToast('error','Error de servidor.');
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy"></i> Guardar Estación';
        }
    });
});
</script>
