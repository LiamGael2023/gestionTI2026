/**
 * roles/admin/admin-equipos.js — Administración de Equipos AV (ADMIN)
 * Funciones: cargarAdminEquipos, abrirModalEquipo, toggleEquipo
 * Requiere: shared/api.js, shared/alerts.js, shared/utils.js, features/calendario.js
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

// ============================================================
// ADMINISTRACIÓN — EQUIPOS AV (solo si ES_ADMIN)
// ============================================================
let _tablaAdminEquipos = null;

function cargarAdminEquipos() {
  if (!ES_ADMIN) return;
  ajax('getAllEquipos', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    if (_tablaAdminEquipos) { _tablaAdminEquipos.destroy(); _tablaAdminEquipos = null; }
    let html = '';
    res.data.forEach(e => {
      const est = e.activo==1 ? '<span class="badge bg-success-lt text-success">Activo</span>' : '<span class="badge bg-secondary-lt text-secondary">Inactivo</span>';
      const btn = e.activo==1
        ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleEquipo(${e.id_equipo},0)"><i class="ti ti-toggle-right"></i></button>`
        : `<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleEquipo(${e.id_equipo},1)"><i class="ti ti-toggle-left"></i></button>`;
      html += `<tr><td>${e.id_equipo}</td><td><strong>${escHtml(e.sede_nombre)}</strong><br><small>${escHtml(e.sala_nombre)}</small></td>
        <td><strong>${escHtml(e.nombre)}</strong></td><td><span class="badge bg-blue-lt text-blue">${escHtml(e.tipo)}</span></td>
        <td><small>${escHtml(e.descripcion||'—')}</small></td><td>${est}</td>
        <td class="text-center"><button class="btn btn-sm btn-outline-warning" onclick="abrirModalEquipo(${e.id_equipo})"><i class="ti ti-edit"></i></button>${btn}</td></tr>`;
    });
    $('#tbody-admin-equipos').html(html||'<tr><td colspan="7" class="text-center text-muted">Sin equipos.</td></tr>');
    _tablaAdminEquipos = $('#tabla-admin-equipos').DataTable({ destroy:true, language:{url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'}, columnDefs:[{targets:6,orderable:false}] });
  });
}

function abrirModalEquipo(id_equipo) {
  $('#form-equipo')[0].reset(); $('#equipo-id').val(''); $('#modal-equipo-titulo').text('Nuevo Equipo AV');
  cargarSalasEnSelectAdminEquipo();
  if (id_equipo) {
    ajax('getAllEquipos',{},'GET').done(function(res) {
      const e = res.data.find(x=>x.id_equipo==id_equipo); if(!e) return;
      $('#modal-equipo-titulo').text('Editar Equipo AV'); $('#equipo-id').val(e.id_equipo);
      setTimeout(()=>{ $('#equipo-sala').val(e.id_sala); $('#equipo-nombre').val(e.nombre); $('#equipo-tipo').val(e.tipo); $('#equipo-descripcion').val(e.descripcion); }, 300);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-equipo')).show();
}

$('#form-equipo').on('submit', function(e) {
  e.preventDefault();
  ajax('guardarEquipo', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-equipo')).hide();
    if (res.ok) Alerta.exito(res.msg).then(()=>cargarAdminEquipos()); else Alerta.error(res.msg);
  }).fail(()=>Alerta.error('Error de comunicación.'));
});

function toggleEquipo(id_equipo, activo) {
  Alerta.confirmarPeligro(`¿Confirma que desea ${activo?'activar':'desactivar'} este equipo?`, function() {
    ajax('toggleEquipo',{id_equipo,activo}).done(function(res){ if(res.ok) cargarAdminEquipos(); else Alerta.error(res.msg); });
  });
}
