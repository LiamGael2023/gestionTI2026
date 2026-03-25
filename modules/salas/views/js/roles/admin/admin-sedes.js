/**
 * roles/admin/admin-sedes.js — Administración de Sedes (ADMIN)
 * Funciones: cargarAdminSedes, abrirModalSede, toggleSede
 * Requiere: shared/api.js, shared/alerts.js, shared/utils.js, features/calendario.js
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

// ============================================================
// ADMINISTRACIÓN — SEDES (solo si ES_ADMIN)
// ============================================================
let _tablaAdminSedes = null;

function cargarAdminSedes() {
  if (!ES_ADMIN) return;
  ajax('getAllSedes', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    if (_tablaAdminSedes) { _tablaAdminSedes.destroy(); _tablaAdminSedes = null; }
    let html = '';
    res.data.forEach(s => {
      const est = s.activo==1
        ? '<span class="badge bg-success-lt text-success">Activo</span>'
        : '<span class="badge bg-secondary-lt text-secondary">Inactivo</span>';
      const btn = s.activo==1
        ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleSede(${s.id},0)"><i class="ti ti-toggle-right"></i></button>`
        : `<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleSede(${s.id},1)"><i class="ti ti-toggle-left"></i></button>`;
      html += `<tr><td>${s.id}</td><td><strong>${escHtml(s.nombre)}</strong></td>
        <td>${escHtml(s.direccion||'—')}</td><td>${escHtml(s.telefono||'—')}</td>
        <td>${est}</td>
        <td class="text-center"><button class="btn btn-sm btn-outline-warning" onclick="abrirModalSede(${s.id})"><i class="ti ti-edit"></i></button>${btn}</td></tr>`;
    });
    $('#tbody-admin-sedes').html(html||'<tr><td colspan="6" class="text-center text-muted">Sin sedes.</td></tr>');
    _tablaAdminSedes = $('#tabla-admin-sedes').DataTable({ destroy:true, language:{url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'}, columnDefs:[{targets:5,orderable:false}] });
  });
}

function abrirModalSede(id) {
  $('#form-sede')[0].reset(); $('#sede-id').val(''); $('#modal-sede-titulo').text('Nueva Sede');
  if (id) {
    ajax('getAllSedes',{},'GET').done(function(res) {
      const s = res.data.find(x=>x.id==id); if(!s) return;
      $('#modal-sede-titulo').text('Editar Sede');
      $('#sede-id').val(s.id); $('#sede-nombre').val(s.nombre);
      $('#sede-direccion').val(s.direccion); $('#sede-telefono').val(s.telefono);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-sede')).show();
}

$('#form-sede').on('submit', function(e) {
  e.preventDefault();
  ajax('guardarSede', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-sede')).hide();
    if (res.ok) Alerta.exito(res.msg).then(()=>cargarAdminSedes());
    else Alerta.error(res.msg);
  }).fail(()=>Alerta.error('Error de comunicación.'));
});

function toggleSede(id, activo) {
  Alerta.confirmarPeligro(`¿Confirma que desea ${activo?'activar':'desactivar'} esta sede?`, function() {
    ajax('toggleSede',{id,activo}).done(function(res){ if(res.ok) cargarAdminSedes(); else Alerta.error(res.msg); });
  });
}
