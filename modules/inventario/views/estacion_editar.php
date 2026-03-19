<body>
<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
$idEstacion = intval($_GET['id'] ?? 0);
if (!$idEstacion) { header('Location: ?module=inventario&action=estaciones'); exit; }
$estacion = EstacionController::ctrMostrarEstacion('idEstacion', $idEstacion);
if (!$estacion) { header('Location: ?module=inventario&action=estaciones'); exit; }
$grupos = EstacionController::ctrEquiposDeEstacionAgrupados($idEstacion);

function fmtF($f, $fmt="d/m/Y H:i") {
    if (!$f) return "—";
    if ($f instanceof DateTime) return $f->format($fmt);
    $ts = strtotime($f); return $ts ? date($fmt,$ts) : "—";
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

:root {
  --blue:#2563eb;--blue-lt:#eff6ff;--blue-md:#bfdbfe;
  --green:#16a34a;--green-lt:#f0fdf4;--green-md:#bbf7d0;
  --purple:#7c3aed;--purple-lt:#f5f3ff;--purple-md:#ddd6fe;
  --gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;
  --gray-300:#cbd5e1;--gray-400:#94a3b8;--gray-600:#475569;--gray-800:#1e293b;
  --radius:8px;
  --shadow-sm:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.05);
  --shadow-md:0 4px 16px rgba(0,0,0,.08),0 2px 6px rgba(0,0,0,.04);
  --shadow-lg:0 10px 32px rgba(0,0,0,.1),0 4px 12px rgba(0,0,0,.06);
}
.cs-wrap{position:relative;width:100%}
.cs-display{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;min-height:40px;background:#fff;border:1.5px solid var(--gray-200);border-radius:var(--radius);cursor:pointer;outline:none;transition:all .15s;font-family:'DM Sans',sans-serif}
.cs-display:hover,.cs-wrap.cs-open .cs-display{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
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

.est-layout{display:grid;grid-template-columns:300px 1fr;min-height:calc(100vh - 56px - 68px);background:var(--gray-50);font-family:'DM Sans',sans-serif}
.est-sidebar{background:#fff;border-right:1.5px solid var(--gray-200);padding:1.25rem;display:flex;flex-direction:column;gap:0}
.sidebar-block{margin-bottom:1.1rem}
.sidebar-section-title{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--gray-400);margin:0 0 .6rem;padding-bottom:.35rem;border-bottom:1.5px solid var(--gray-100);display:flex;align-items:center;gap:.4rem}
.sidebar-section-title i{font-size:.8rem}
.fld-label{display:block;font-size:.78rem;font-weight:600;color:var(--gray-600);margin-bottom:.28rem;letter-spacing:.01em}
.fld-label .req{color:#ef4444;margin-left:2px}
.fld-input{width:100%;padding:.48rem .72rem;border:1.5px solid var(--gray-200);border-radius:var(--radius);font-size:.855rem;color:var(--gray-800);background:#fff;transition:all .15s;font-family:'DM Sans',sans-serif;outline:none}
.fld-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.fld-input::placeholder{color:var(--gray-400)}
.pass-wrap{display:flex;gap:0}
.pass-wrap .fld-input{border-radius:var(--radius) 0 0 var(--radius)}
.pass-toggle{padding:0 .72rem;border:1.5px solid var(--gray-200);border-left:none;border-radius:0 var(--radius) var(--radius) 0;background:#fff;color:var(--gray-400);cursor:pointer;transition:all .15s;display:flex;align-items:center;font-size:.95rem}
.pass-toggle:hover{background:var(--blue-lt);color:var(--blue);border-color:var(--blue)}

/* Auditoría — diseño premium */
.audit-card{
  background:linear-gradient(135deg,var(--gray-50) 0%,#fff 100%);
  border:1.5px solid var(--gray-200);
  border-radius:10px;
  padding:.75rem;
  margin-top:auto;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:.5rem .75rem;
}
.audit-row{}
.audit-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);display:flex;align-items:center;gap:.25rem;margin-bottom:.15rem}
.audit-label i{font-size:.75rem}
.audit-val{font-size:.8rem;font-weight:600;color:var(--gray-800);line-height:1.3}
.audit-val.muted{color:var(--gray-400);font-weight:400;font-style:italic}

.est-main{padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:.9rem;overflow-y:auto}

.eq-card{background:#fff;border-radius:12px;border:1.5px solid var(--gray-200);overflow:hidden;box-shadow:var(--shadow-sm);transition:box-shadow .2s}
.eq-card:hover{box-shadow:var(--shadow-md)}
.eq-card-header{display:flex;align-items:center;gap:.75rem;padding:.8rem 1.1rem;border-bottom:1.5px solid var(--gray-100)}
.eq-icon-wrap{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.eq-header-text{flex:1}
.eq-header-title{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
.eq-header-sub{font-size:.68rem;color:var(--gray-400);margin-top:.1rem}
.eq-badge{font-size:.72rem;font-weight:700;padding:.18rem .55rem;border-radius:20px}
.eq-card-body{padding:.85rem 1.1rem}
.eq-search-bar{display:flex;gap:.5rem;align-items:flex-end;margin-bottom:.7rem}
.eq-search-label{font-size:.73rem;font-weight:600;color:var(--gray-600);margin-bottom:.26rem;display:block}
.btn-eq-add{width:40px;height:40px;border:none;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:1.05rem;cursor:pointer;transition:all .15s;flex-shrink:0;align-self:flex-end;box-shadow:var(--shadow-sm)}
.btn-eq-add:disabled{opacity:.35;cursor:not-allowed;box-shadow:none}
.btn-eq-add:not(:disabled):hover{transform:translateY(-1px);box-shadow:var(--shadow-md)}
.eq-items{min-height:44px}
.eq-empty{display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.7rem;color:var(--gray-400);font-size:.78rem;font-style:italic;background:var(--gray-50);border-radius:var(--radius);border:1.5px dashed var(--gray-200)}
.eq-item{display:flex;align-items:center;gap:.6rem;padding:.4rem .65rem;background:var(--gray-50);border:1.5px solid var(--gray-200);border-radius:var(--radius);margin-bottom:.3rem;transition:all .15s}
.eq-item:hover{background:#fff;box-shadow:var(--shadow-sm)}
.eq-item-ico{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.82rem;flex-shrink:0}
.eq-item-body{flex:1;overflow:hidden}
.eq-item-name{font-size:.81rem;font-weight:600;color:var(--gray-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.eq-item-meta{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.08rem}
.eq-item-cp{font-size:.69rem;font-weight:600;padding:.04rem .32rem;border-radius:4px}
.eq-item-sn{font-size:.69rem;color:var(--gray-400)}
.btn-eq-rm{width:24px;height:24px;border-radius:6px;border:1.5px solid #fecaca;background:#fff5f5;color:#ef4444;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.74rem;flex-shrink:0;transition:all .15s}
.btn-eq-rm:hover{background:#ef4444;color:#fff;border-color:#ef4444}

.eq-principal .eq-card-header{background:linear-gradient(135deg,var(--blue-lt),#dbeafe)}
.eq-principal .eq-icon-wrap{background:var(--blue);color:#fff}
.eq-principal .eq-header-title{color:var(--blue)}
.eq-principal .eq-badge{background:var(--blue-md);color:var(--blue)}
.eq-principal .btn-eq-add{background:var(--blue);color:#fff}
.eq-principal .btn-eq-add:not(:disabled):hover{background:#1d4ed8}
.eq-principal .eq-item-ico{background:var(--blue-lt);color:var(--blue)}
.eq-principal .eq-item-cp{background:var(--blue-lt);color:var(--blue)}

.eq-periferico .eq-card-header{background:linear-gradient(135deg,var(--green-lt),#dcfce7)}
.eq-periferico .eq-icon-wrap{background:var(--green);color:#fff}
.eq-periferico .eq-header-title{color:var(--green)}
.eq-periferico .eq-badge{background:var(--green-md);color:var(--green)}
.eq-periferico .btn-eq-add{background:var(--green);color:#fff}
.eq-periferico .btn-eq-add:not(:disabled):hover{background:#15803d}
.eq-periferico .eq-item-ico{background:var(--green-lt);color:var(--green)}
.eq-periferico .eq-item-cp{background:var(--green-lt);color:var(--green)}

.eq-software .eq-card-header{background:linear-gradient(135deg,var(--purple-lt),#ede9fe)}
.eq-software .eq-icon-wrap{background:var(--purple);color:#fff}
.eq-software .eq-header-title{color:var(--purple)}
.eq-software .eq-badge{background:var(--purple-md);color:var(--purple)}
.eq-software .btn-eq-add{background:var(--purple);color:#fff}
.eq-software .btn-eq-add:not(:disabled):hover{background:#6d28d9}
.eq-software .eq-item-ico{background:var(--purple-lt);color:var(--purple)}
.eq-software .eq-item-cp{background:var(--purple-lt);color:var(--purple)}

.est-footer{background:#fff;border-top:1.5px solid var(--gray-200);padding:.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;position:sticky;bottom:0;z-index:100;box-shadow:0 -4px 16px rgba(0,0,0,.06);font-family:'DM Sans',sans-serif}
.footer-hint{font-size:.75rem;color:var(--gray-400);display:flex;align-items:center;gap:.35rem}
.btn-cancel{display:inline-flex;align-items:center;gap:.4rem;padding:.52rem 1rem;border-radius:var(--radius);border:1.5px solid var(--gray-200);background:#fff;color:var(--gray-600);font-size:.855rem;font-weight:500;cursor:pointer;transition:all .15s;text-decoration:none;font-family:'DM Sans',sans-serif}
.btn-cancel:hover{border-color:var(--gray-300);background:var(--gray-50);color:var(--gray-800)}
.btn-save{display:inline-flex;align-items:center;gap:.45rem;padding:.52rem 1.35rem;border-radius:var(--radius);border:none;background:var(--blue);color:#fff;font-size:.875rem;font-weight:600;cursor:pointer;transition:all .15s;box-shadow:0 2px 8px rgba(37,99,235,.35);font-family:'DM Sans',sans-serif}
.btn-save:hover:not(:disabled){background:#1d4ed8;box-shadow:0 4px 14px rgba(37,99,235,.45);transform:translateY(-1px)}
.btn-save:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;transform:none}

.est-breadcrumb{display:flex;align-items:center;gap:.35rem;font-size:.75rem;color:var(--gray-400);margin-bottom:.3rem;font-family:'DM Sans',sans-serif}
.est-breadcrumb a{color:var(--gray-400);text-decoration:none;transition:color .15s;display:flex;align-items:center;gap:.25rem}
.est-breadcrumb a:hover{color:var(--blue)}
.est-title{font-size:1.3rem;font-weight:700;color:var(--gray-800);font-family:'DM Sans',sans-serif;margin-bottom:1rem}

@media(max-width:900px){.est-layout{grid-template-columns:1fr}.est-sidebar{border-right:none;border-bottom:1.5px solid var(--gray-200)}}
</style>

<div class="page">
<?php include __DIR__ . '/_submenu.php'; ?>

<div style="background:var(--gray-50);font-family:'DM Sans',sans-serif;padding:1.25rem 1.5rem 0">
  <div class="container-xl">
    <div class="est-breadcrumb">
      <a href="?module=inventario&action=estaciones"><i class="ti ti-arrow-left"></i>Estaciones</a>
      <span style="color:#cbd5e1">/</span>
      <span style="color:#64748b">Editar</span>
    </div>
    <div class="est-title">Editar: <?= htmlspecialchars($estacion['nombreEstacion'] ?? '') ?></div>
  </div>
</div>

<div style="background:var(--gray-50)">
  <div class="container-xl">
    <form id="formEditarEstacion" novalidate>
      <input type="hidden" name="editarIdEstacion" value="<?= $idEstacion ?>">

      <div class="est-layout" style="border-radius:14px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07)">

        <!-- SIDEBAR -->
        <div class="est-sidebar">

          <div class="sidebar-block">
            <div class="sidebar-section-title"><i class="ti ti-info-circle"></i> Información</div>
            <div style="margin-bottom:.7rem">
              <label class="fld-label">Nombre <span class="req">*</span></label>
              <input class="fld-input" type="text" name="editarNombreEstacion" id="editarNombreEstacion"
                     value="<?= htmlspecialchars($estacion['nombreEstacion'] ?? '') ?>" required>
            </div>
            <div>
              <label class="fld-label">IP Asignada</label>
              <select id="editarIdIp" name="editarIdIp" style="display:none">
                <option value="">Sin IP asignada</option>
              </select>
            </div>
          </div>

          <div class="sidebar-block">
            <div class="sidebar-section-title"><i class="ti ti-device-desktop"></i> Acceso Remoto</div>
            <div style="margin-bottom:.7rem">
              <label class="fld-label">Código Anydesk</label>
              <input class="fld-input font-monospace" type="text" name="editarCodigoAnydesk" id="editarCodigoAnydesk"
                     value="<?= htmlspecialchars($estacion['codigoAnydesk'] ?? '') ?>">
            </div>
            <div>
              <label class="fld-label">Contraseña Anydesk</label>
              <div class="pass-wrap">
                <input class="fld-input font-monospace" type="password" name="editarContrasenaAnydesk" id="editarContrasenaAnydesk"
                       value="<?= htmlspecialchars($estacion['contrasenaAnydesk'] ?? '') ?>">
                <button type="button" class="pass-toggle btnTogglePass" data-target="editarContrasenaAnydesk">
                  <i class="ti ti-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Auditoría premium -->
          <div class="sidebar-block">
            <div class="sidebar-section-title"><i class="ti ti-shield-check"></i> Auditoría</div>
            <div class="audit-card">
              <div class="audit-row">
                <div class="audit-label"><i class="ti ti-user"></i> Creado por</div>
                <div class="audit-val"><?= htmlspecialchars($estacion['idUsuarioRegistro'] ?? '') ?: '<span class="muted">—</span>' ?></div>
              </div>
              <div class="audit-row">
                <div class="audit-label"><i class="ti ti-calendar"></i> Fecha creación</div>
                <div class="audit-val"><?= fmtF($estacion['fechaCreacion'] ?? null) ?></div>
              </div>
              <div class="audit-row">
                <div class="audit-label"><i class="ti ti-user-edit"></i> Modificado por</div>
                <div class="audit-val <?= empty($estacion['idUsuarioModifica']) ? 'muted' : '' ?>">
                  <?= $estacion['idUsuarioModifica'] ? htmlspecialchars($estacion['idUsuarioModifica']) : '—' ?>
                </div>
              </div>
              <div class="audit-row">
                <div class="audit-label"><i class="ti ti-clock"></i> Fecha modif.</div>
                <div class="audit-val <?= empty($estacion['fechaModificacion']) ? 'muted' : '' ?>">
                  <?= fmtF($estacion['fechaModificacion'] ?? null) ?>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- MAIN -->
        <div class="est-main">

          <div class="eq-card eq-principal">
            <div class="eq-card-header">
              <div class="eq-icon-wrap"><i class="ti ti-cpu"></i></div>
              <div class="eq-header-text">
                <div class="eq-header-title">Equipo Principal</div>
                <div class="eq-header-sub">CPU, Laptop, Servidor — máximo 1</div>
              </div>
            </div>
            <div class="eq-card-body">
              <div class="eq-search-bar">
                <div style="flex:1">
                  <span class="eq-search-label">Buscar por código patrimonial</span>
                  <select id="editarEquipoPrincipalSelect" style="display:none"><option value="">Seleccionar...</option></select>
                </div>
                <button type="button" id="btnAgregarEditarPrincipal" class="btn-eq-add" disabled>
                  <i class="ti ti-plus"></i>
                </button>
              </div>
              <div id="editarEquipoPrincipalLista" class="eq-items"></div>
              <input type="hidden" id="editarEquipoPrincipalId" name="editarEquipoPrincipalId">
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="eq-card eq-periferico h-100">
                <div class="eq-card-header">
                  <div class="eq-icon-wrap"><i class="ti ti-devices"></i></div>
                  <div class="eq-header-text">
                    <div class="eq-header-title">Periféricos</div>
                    <div class="eq-header-sub">Monitor, mouse, teclado, UPS…</div>
                  </div>
                  <span class="eq-badge" id="editarPerifericosContador">0</span>
                </div>
                <div class="eq-card-body">
                  <div class="eq-search-bar">
                    <div style="flex:1">
                      <span class="eq-search-label">Buscar por código patrimonial</span>
                      <select id="editarPerifericoSelect" style="display:none"><option value="">Seleccionar...</option></select>
                    </div>
                    <button type="button" id="btnAgregarEditarPeriferico" class="btn-eq-add" disabled>
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <div id="editarPerifericosLista" class="eq-items"></div>
                  <input type="hidden" id="editarPerifericosIds" name="editarPerifericosIds">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="eq-card eq-software h-100">
                <div class="eq-card-header">
                  <div class="eq-icon-wrap"><i class="ti ti-brand-windows"></i></div>
                  <div class="eq-header-text">
                    <div class="eq-header-title">Software</div>
                    <div class="eq-header-sub">Licencias y aplicaciones</div>
                  </div>
                  <span class="eq-badge" id="editarSoftwareContador">0</span>
                </div>
                <div class="eq-card-body">
                  <div class="eq-search-bar">
                    <div style="flex:1">
                      <span class="eq-search-label">Buscar por nombre</span>
                      <select id="editarSoftwareSelect" style="display:none"><option value="">Seleccionar software...</option></select>
                    </div>
                    <button type="button" id="btnAgregarEditarSoftware" class="btn-eq-add" disabled>
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <div id="editarSoftwareLista" class="eq-items"></div>
                  <input type="hidden" id="editarSoftwareIds" name="editarSoftwareIds">
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- FOOTER -->
      <div class="est-footer">
        <div class="footer-hint">
          <i class="ti ti-info-circle" style="color:var(--blue)"></i>
          Los cambios se guardarán en el sistema de inventario
        </div>
        <div style="display:flex;gap:.6rem">
          <a href="?module=inventario&action=estaciones" class="btn-cancel">
            <i class="ti ti-x"></i> Cancelar
          </a>
          <button type="submit" class="btn-save" id="btnActualizar">
            <i class="ti ti-check"></i> Actualizar Estación
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
const ESTACION_ID  = <?= $idEstacion ?>;
const ID_IP_ACTUAL = <?= intval($estacion['idIp'] ?? 0) ?>;
const GRUPOS_INICIAL = <?= json_encode([
    'principal'   => array_values($grupos['principal']   ?? []),
    'perifericos' => array_values($grupos['perifericos'] ?? []),
    'software'    => array_values($grupos['software']    ?? []),
]) ?>;

function renderItemEdit(eq, onQuitar) {
    const d = document.createElement('div');
    d.className = 'eq-item';
    const ico = escHtml(eq.iconoActivo ?? 'ti-package');
    d.innerHTML = `
        <div class="eq-item-ico"><i class="ti ${ico}"></i></div>
        <div class="eq-item-body">
            <div class="eq-item-name">${escHtml(eq.nombreActivo ?? '')}</div>
            <div class="eq-item-meta">
                ${eq.codigoPatrimonial ? `<span class="eq-item-cp">${escHtml(eq.codigoPatrimonial)}</span>` : ''}
                ${eq.numeroSerie ? `<span class="eq-item-sn">S/N: ${escHtml(eq.numeroSerie)}</span>` : ''}
            </div>
        </div>
        <button type="button" class="btn-eq-rm" title="Quitar"><i class="ti ti-x"></i></button>`;
    d.querySelector('.btn-eq-rm').addEventListener('click', onQuitar);
    return d;
}

function renderListaEdit(listaId, contadorId, arr, onAfterQuitar) {
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
        const el = renderItemEdit(eq, () => {
            arr.splice(idx, 1);
            renderListaEdit(listaId, contadorId, arr, onAfterQuitar);
            if (onAfterQuitar) onAfterQuitar();
        });
        lista.appendChild(el);
    });
}

document.addEventListener('DOMContentLoaded', async function () {
    initTogglePass();
    const csIp         = crearCustomSelect('editarIdIp');
    const csPrincipal  = crearCustomSelect('editarEquipoPrincipalSelect');
    const csPeriferico = crearCustomSelect('editarPerifericoSelect');
    const csSoftware   = crearCustomSelect('editarSoftwareSelect');

    let principal   = GRUPOS_INICIAL.principal.map(e  => ({...e, idEquipo:String(e.idEquipo)}));
    let perifericos = GRUPOS_INICIAL.perifericos.map(e => ({...e, idEquipo:String(e.idEquipo)}));
    let software    = GRUPOS_INICIAL.software.map(e   => ({...e, idEquipo:String(e.idEquipo)}));

    function sync() { sincronizarHiddens('editar', principal, perifericos, software); }

    function lockPrincipal(lock) {
        const wP = document.getElementById('cswrap_editarEquipoPrincipalSelect');
        if (wP) { wP.style.opacity = lock ? '.4' : '1'; wP.style.pointerEvents = lock ? 'none' : 'auto'; }
        document.getElementById('btnAgregarEditarPrincipal').disabled = lock;
    }

    function renderAll() {
        renderListaEdit('editarEquipoPrincipalLista', null, principal, () => { sync(); lockPrincipal(false); recargarCombos(); });
        renderListaEdit('editarPerifericosLista', 'editarPerifericosContador', perifericos, () => { sync(); recargarCombos(); });
        renderListaEdit('editarSoftwareLista', 'editarSoftwareContador', software, () => { sync(); recargarCombos(); });
        sync();
        lockPrincipal(principal.length > 0);
    }

    async function recargarCombos() {
        const excl = idsExcluir(principal, perifericos, software);
        await Promise.all([
            cargarEquiposTipo(csPrincipal,  'principal',  ESTACION_ID, excl),
            cargarEquiposTipo(csPeriferico, 'periferico', ESTACION_ID, excl),
            cargarEquiposTipo(csSoftware,   'software',   ESTACION_ID, excl),
        ]);
    }

    await Promise.all([cargarIps(csIp, ID_IP_ACTUAL || '', ESTACION_ID), recargarCombos()]);
    renderAll();
    document.getElementById('btnAgregarEditarPeriferico').disabled = true;
    document.getElementById('btnAgregarEditarSoftware').disabled   = true;

    document.getElementById('editarEquipoPrincipalSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarEditarPrincipal').disabled = !this.value || principal.length > 0;
    });
    document.getElementById('editarPerifericoSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarEditarPeriferico').disabled = !this.value;
    });
    document.getElementById('editarSoftwareSelect')?.addEventListener('change', function() {
        document.getElementById('btnAgregarEditarSoftware').disabled = !this.value;
    });

    document.getElementById('btnAgregarEditarPrincipal')?.addEventListener('click', () => {
        const val = csPrincipal.getValue(); if (!val || principal.length) return;
        const eq = csPrincipal._data?.[val]; if (!eq) return;
        principal = [{idEquipo:val,...eq}]; csPrincipal.reset();
        renderAll(); recargarCombos();
    });
    document.getElementById('btnAgregarEditarPeriferico')?.addEventListener('click', () => {
        const val = csPeriferico.getValue(); if (!val) return;
        if (perifericos.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq = csPeriferico._data?.[val]; if (!eq) return;
        perifericos.push({idEquipo:val,...eq}); csPeriferico.reset();
        document.getElementById('btnAgregarEditarPeriferico').disabled = true;
        renderAll(); recargarCombos();
    });
    document.getElementById('btnAgregarEditarSoftware')?.addEventListener('click', () => {
        const val = csSoftware.getValue(); if (!val) return;
        if (software.some(e=>e.idEquipo===val)) { mostrarToast('warning','Ya está en la lista.'); return; }
        const eq = csSoftware._data?.[val]; if (!eq) return;
        software.push({idEquipo:val,...eq}); csSoftware.reset();
        document.getElementById('btnAgregarEditarSoftware').disabled = true;
        renderAll(); recargarCombos();
    });

    document.getElementById('formEditarEstacion')?.addEventListener('submit', async function(e) {
        e.preventDefault(); sync();
        const btn = document.getElementById('btnActualizar'); btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:14px;height:14px;margin-right:.4rem"></span>Actualizando...';
        try {
            const resp = await fetch(AJAX_EST, {method:'POST', body:new FormData(this)});
            const data = await resp.json();
            const r = (data.resultado??'').trim(), m = (data.mensaje??'').trim();
            if (r==='ok') {
                mostrarToast('success', m || 'Estación actualizada.');
                setTimeout(() => { window.location.href = URL_LISTA; }, 1200);
            } else if (r==='error_duplicado') {
                mostrarToast('warning', m);
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-check"></i> Actualizar Estación';
            } else {
                mostrarToast('error', m || 'Error.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-check"></i> Actualizar Estación';
            }
        } catch {
            mostrarToast('error','Error de servidor.');
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check"></i> Actualizar Estación';
        }
    });
});
</script>
