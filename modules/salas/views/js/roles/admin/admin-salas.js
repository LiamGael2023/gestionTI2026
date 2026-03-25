/**
 * roles/admin/admin-salas.js — Administración de Salas (ADMIN)
 * Funciones: cargarAdminSalas, abrirModalSala, toggleSala
 * Requiere: shared/api.js, shared/alerts.js, shared/utils.js, features/calendario.js
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

// ============================================================
// ADMINISTRACIÓN — SALAS (solo si ES_ADMIN)
// ============================================================
let _tablaAdminSalas = null;

function cargarAdminSalas() {
  if (!ES_ADMIN) return;
  ajax('getAllSalas', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    if (_tablaAdminSalas) { _tablaAdminSalas.destroy(); _tablaAdminSalas = null; }
    let html = '';
    res.data.forEach(s => {
      const est = s.activo==1 ? '<span class="badge bg-success-lt text-success">Activo</span>' : '<span class="badge bg-secondary-lt text-secondary">Inactivo</span>';
      const btn = s.activo==1
        ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleSala(${s.id_sala},0)"><i class="ti ti-toggle-right"></i></button>`
        : `<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleSala(${s.id_sala},1)"><i class="ti ti-toggle-left"></i></button>`;
      html += `<tr><td>${s.id_sala}</td><td>${escHtml(s.sede_nombre)}</td><td><strong>${escHtml(s.nombre)}</strong></td>
        <td class="text-center">${s.capacidad}</td><td><small>${escHtml(s.descripcion||'—')}</small></td><td>${est}</td>
        <td class="text-center"><button class="btn btn-sm btn-outline-warning" onclick="abrirModalSala(${s.id_sala})"><i class="ti ti-edit"></i></button>${btn}</td></tr>`;
    });
    $('#tbody-admin-salas').html(html||'<tr><td colspan="7" class="text-center text-muted">Sin salas.</td></tr>');
    _tablaAdminSalas = $('#tabla-admin-salas').DataTable({ destroy:true, language:{url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'}, columnDefs:[{targets:6,orderable:false}] });
  });
}

function abrirModalSala(id_sala) {
  $('#form-sala')[0].reset(); $('#sala-id').val(''); $('#modal-sala-titulo').text('Nueva Sala');
  cargarSedesEnSelectAdminSala();
  if (id_sala) {
    ajax('getAllSalas',{},'GET').done(function(res) {
      const s = res.data.find(x=>x.id_sala==id_sala); if(!s) return;
      $('#modal-sala-titulo').text('Editar Sala'); $('#sala-id').val(s.id_sala);
      setTimeout(()=>{ $('#sala-sede').val(s.id_sede); $('#sala-nombre').val(s.nombre); $('#sala-capacidad').val(s.capacidad); $('#sala-descripcion').val(s.descripcion); }, 300);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-sala')).show();
}

$('#form-sala').on('submit', function(e) {
  e.preventDefault();
  ajax('guardarSala', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-sala')).hide();
    if (res.ok) { Alerta.exito(res.msg).then(()=>{ cargarAdminSalas(); cargarSalasEnSelectAdminEquipo(); cargarFiltrosSede(); }); } else Alerta.error(res.msg);
  }).fail(()=>Alerta.error('Error de comunicación.'));
});

function toggleSala(id_sala, activo) {
  Alerta.confirmarPeligro(`¿Confirma que desea ${activo?'activar':'desactivar'} esta sala?`, function() {
    ajax('toggleSala',{id_sala,activo}).done(function(res){ if(res.ok) cargarAdminSalas(); else Alerta.error(res.msg); });
  });
}
