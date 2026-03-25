/**
 * catalogo.js — Lógica JavaScript de la vista Administración Salas (Catálogo)
 * Requiere: jQuery, DataTables, SweetAlert2
 * Dependencias: shared/api.js, shared/alerts.js, shared/utils.js
 * Variable PHP inyectada desde html/catalogo.php: AJAX
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

// Los helpers (ajax, escHtml, Alerta) están definidos en shared/

// ─── Variables de DataTables ─────────────────────────────────
var _tablaAdminSedes   = null;
var _tablaAdminSalas   = null;
var _tablaAdminEquipos = null;

// ============================================================
// SEDES
// ============================================================
function cargarAdminSedes() {
  ajax('getAllSedes', {}, 'GET').done(function(res) {
    if (!res.ok) { $('#tbody-admin-sedes').html(`<tr><td colspan="5" class="text-center text-danger">${res.msg || 'Error al cargar.'}</td></tr>`); return; }
    if (_tablaAdminSedes) { _tablaAdminSedes.destroy(); _tablaAdminSedes = null; }
    let html = '';
    res.data.forEach(s => {
      const est = s.activo == 1
        ? '<span class="badge bg-success-lt text-success">Activo</span>'
        : '<span class="badge bg-secondary-lt text-secondary">Inactivo</span>';
      const btn = s.activo == 1
        ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleSede(${s.id},0)"><i class="ti ti-toggle-right"></i></button>`
        : `<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleSede(${s.id},1)"><i class="ti ti-toggle-left"></i></button>`;
      html += `<tr>
        <td>${s.id}</td>
        <td><strong>${escHtml(s.nombre)}</strong></td>
        <td>${escHtml(s.direccion || '—')}</td>
        <td>${est}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-warning" onclick="abrirModalSede(${s.id})"><i class="ti ti-edit"></i></button>
          ${btn}
        </td></tr>`;
    });
    $('#tbody-admin-sedes').html(html || '<tr><td colspan="5" class="text-center text-muted">Sin sedes registradas.</td></tr>');
    if (html) {
      _tablaAdminSedes = $('#tabla-admin-sedes').DataTable({
        destroy: true,
        autoWidth: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        columnDefs: [{ targets: 4, orderable: false }]
      });
    }
  }).fail(function(xhr) { $('#tbody-admin-sedes').html('<tr><td colspan="5" class="text-center text-danger">Error HTTP ' + xhr.status + '</td></tr>'); });
}

function abrirModalSede(id) {
  $('#form-sede')[0].reset(); $('#sede-id').val(''); $('#modal-sede-titulo').text('Nueva Sede');
  if (id) {
    ajax('getAllSedes', {}, 'GET').done(function(res) {
      const s = res.data.find(x => x.id == id); if (!s) return;
      $('#modal-sede-titulo').text('Editar Sede');
      $('#sede-id').val(s.id); $('#sede-nombre').val(s.nombre);
      $('#sede-direccion').val(s.direccion);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-sede')).show();
}

$('#form-sede').on('submit', function(e) {
  e.preventDefault();
  ajax('guardarSede', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-sede')).hide();
    if (res.ok) Alerta.exito(res.msg).then(() => cargarAdminSedes());
    else Alerta.error(res.msg);
  }).fail(() => Alerta.error('Error de comunicación.'));
});

function toggleSede(id, activo) {
  Alerta.confirmarPeligro(`¿Confirma que desea ${activo ? 'activar' : 'desactivar'} esta sede?`, function() {
    Swal.fire({ title: 'Procesando…', text: 'Por favor espere.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    ajax('toggleSede', { id, activo }).done(function(res) {
      if (res.ok) { Swal.close(); cargarAdminSedes(); } else Alerta.error(res.msg);
    }).fail(() => Alerta.error('Error de comunicación.'));
  });
}

// ============================================================
// SALAS
// ============================================================
function cargarAdminSalas() {
  ajax('getAllSalas', {}, 'GET').done(function(res) {
    if (!res.ok) { $('#tbody-admin-salas').html(`<tr><td colspan="7" class="text-center text-danger">${res.msg || 'Error al cargar.'}</td></tr>`); return; }
    if (_tablaAdminSalas) { _tablaAdminSalas.destroy(); _tablaAdminSalas = null; }
    let html = '';
    res.data.forEach(s => {
      const est = s.activo == 1
        ? '<span class="badge bg-success-lt text-success">Activo</span>'
        : '<span class="badge bg-secondary-lt text-secondary">Inactivo</span>';
      const btn = s.activo == 1
        ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleSala(${s.id_sala},0)"><i class="ti ti-toggle-right"></i></button>`
        : `<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleSala(${s.id_sala},1)"><i class="ti ti-toggle-left"></i></button>`;
      html += `<tr>
        <td>${s.id_sala}</td>
        <td>${escHtml(s.sede_nombre)}</td>
        <td>
          <strong>${escHtml(s.nombre)}</strong>
          ${s.foto_ruta ? `<br><img src="${SALAS_ASSETS_URL + escHtml(s.foto_ruta)}" alt="foto" style="height:36px;width:54px;object-fit:cover;border-radius:4px;margin-top:3px;border:1px solid #dee2e6;">` : ''}
        </td>
        <td class="text-center">${s.capacidad}</td>
        <td><small>${escHtml(s.descripcion || '—')}</small></td>
        <td>${est}</td>
        <td class="text-center">
          <div class="btn-list flex-nowrap justify-content-center">
            <button class="btn btn-sm btn-outline-info" onclick="verEquiposSala(${s.id_sala},'${escHtml(s.nombre)}')" title="Ver equipos asignados"><i class="ti ti-device-tv"></i></button>
            <button class="btn btn-sm btn-outline-warning" onclick="abrirModalSala(${s.id_sala})"><i class="ti ti-edit"></i></button>
            ${btn}
          </div>
        </td></tr>`;
    });
    $('#tbody-admin-salas').html(html || '<tr><td colspan="7" class="text-center text-muted">Sin salas registradas.</td></tr>');
    if (html) {
      _tablaAdminSalas = $('#tabla-admin-salas').DataTable({
        destroy: true,
        autoWidth: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        columnDefs: [{ targets: 6, orderable: false }]
      });
    }
  }).fail(function(xhr) { $('#tbody-admin-salas').html(`<tr><td colspan="7" class="text-center text-danger">Error HTTP ${xhr.status}: ${xhr.responseText.substring(0,200)}</td></tr>`); });
}

function verEquiposSala(id_sala, nombre_sala) {
  $('#modal-equipos-sala-nombre').text(nombre_sala);
  $('#modal-equipos-sala-body').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
  new bootstrap.Modal(document.getElementById('modal-equipos-sala')).show();
  ajax('getEquiposBySala', { id_sala: id_sala }, 'GET').done(function(res) {
    if (!res.ok) {
      $('#modal-equipos-sala-body').html('<div class="alert alert-danger">' + escHtml(res.msg || 'Error al cargar equipos.') + '</div>');
      return;
    }
    if (!res.data || !res.data.length) {
      $('#modal-equipos-sala-body').html('<div class="text-center text-muted py-3"><i class="ti ti-mood-empty me-2"></i>Esta sala no tiene equipos AV asignados.</div>');
      return;
    }
    let html = '<div class="table-responsive"><table class="table table-vcenter table-sm table-striped">';
    html += '<thead><tr><th>#</th><th>Nombre</th><th>Tipo</th><th>Descripción</th></tr></thead><tbody>';
    res.data.forEach(function(e, i) {
      html += '<tr>'
        + '<td>' + (i + 1) + '</td>'
        + '<td><strong>' + escHtml(e.nombre) + '</strong></td>'
        + '<td><span class="badge bg-blue-lt text-blue">' + escHtml(e.tipo) + '</span></td>'
        + '<td><small class="text-muted">' + escHtml(e.descripcion || '—') + '</small></td>'
        + '</tr>';
    });
    html += '</tbody></table></div>';
    html += '<div class="text-end text-muted small mt-1">' + res.data.length + ' equipo(s) activo(s)</div>';
    $('#modal-equipos-sala-body').html(html);
  }).fail(function(xhr) {
    $('#modal-equipos-sala-body').html('<div class="alert alert-danger">Error HTTP ' + xhr.status + ' al conectar con el servidor.</div>');
  });
}

function cargarSedesEnSelectAdminSala() {
  ajax('getAllSedes', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    let opts = '<option value="">— Seleccione sede —</option>';
    res.data.forEach(s => opts += `<option value="${s.id}">${escHtml(s.nombre)}</option>`);
    $('#sala-sede').html(opts);
  });
}

function abrirModalSala(id_sala) {
  $('#form-sala')[0].reset();
  $('#sala-id').val('');
  $('#modal-sala-titulo').text('Nueva Sala');
  $('#sala-foto-file').val('');
  $('#sala-foto-actual').attr('src', '');
  $('#sala-foto-actual-wrap').addClass('d-none');
  cargarSedesEnSelectAdminSala();
  if (id_sala) {
    ajax('getAllSalas', {}, 'GET').done(function(res) {
      const s = res.data.find(x => x.id_sala == id_sala); if (!s) return;
      $('#modal-sala-titulo').text('Editar Sala'); $('#sala-id').val(s.id_sala);
      setTimeout(() => {
        $('#sala-sede').val(s.id_sede); $('#sala-nombre').val(s.nombre);
        $('#sala-capacidad').val(s.capacidad); $('#sala-descripcion').val(s.descripcion);
      }, 300);
      if (s.foto_ruta) {
        $('#sala-foto-actual').attr('src', SALAS_ASSETS_URL + s.foto_ruta);
        $('#sala-foto-actual-wrap').removeClass('d-none');
      }
    });
  }
  new bootstrap.Modal(document.getElementById('modal-sala')).show();
}

$('#form-sala').on('submit', function(e) {
  e.preventDefault();
  const fileInput = document.getElementById('sala-foto-file');
  const file = fileInput && fileInput.files ? fileInput.files[0] : null;
  if (file) {
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    const permitidos = { jpg:1, jpeg:1, png:1, webp:1 };
    if (!permitidos[ext]) {
      Alerta.error('Formato no permitido. Solo JPG, PNG o WebP.');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      Alerta.error('La imagen no puede superar 5 MB.');
      return;
    }
  }
  const fd = new FormData(this);
  $.ajax({
    url: AJAX + '?action=guardarSala',
    method: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    dataType: 'json'
  }).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-sala')).hide();
    if (res.ok) Alerta.exito(res.msg).then(() => { cargarAdminSalas(); cargarSalasEnSelectAdminEquipo(); });
    else Alerta.error(res.msg);
  }).fail(() => Alerta.error('Error de comunicación.'));
});

function toggleSala(id_sala, activo) {
  Alerta.confirmarPeligro(`¿Confirma que desea ${activo ? 'activar' : 'desactivar'} esta sala?`, function() {
    Swal.fire({ title: 'Procesando…', text: 'Por favor espere.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    ajax('toggleSala', { id_sala, activo }).done(function(res) {
      if (res.ok) { Swal.close(); cargarAdminSalas(); } else Alerta.error(res.msg);
    }).fail(() => Alerta.error('Error de comunicación.'));
  });
}

// ============================================================
// EQUIPOS AV
// ============================================================
function cargarAdminEquipos() {
  ajax('getAllEquipos', {}, 'GET').done(function(res) {
    if (!res.ok) { $('#tbody-admin-equipos').html(`<tr><td colspan="7" class="text-center text-danger">${res.msg || 'Error al cargar.'}</td></tr>`); return; }
    if (_tablaAdminEquipos) { _tablaAdminEquipos.destroy(); _tablaAdminEquipos = null; }
    let html = '';
    res.data.forEach(e => {
      const est = e.activo == 1
        ? '<span class="badge bg-success-lt text-success">Activo</span>'
        : '<span class="badge bg-secondary-lt text-secondary">Inactivo</span>';
      const btn = e.activo == 1
        ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="toggleEquipo(${e.id_equipo},0)"><i class="ti ti-toggle-right"></i></button>`
        : `<button class="btn btn-sm btn-outline-success ms-1" onclick="toggleEquipo(${e.id_equipo},1)"><i class="ti ti-toggle-left"></i></button>`;
      html += `<tr>
        <td>${e.id_equipo}</td>
        <td><strong>${escHtml(e.sede_nombre)}</strong><br><small>${escHtml(e.sala_nombre)}</small></td>
        <td><strong>${escHtml(e.nombre)}</strong></td>
        <td><span class="badge bg-blue-lt text-blue">${escHtml(e.tipo)}</span></td>
        <td><small>${escHtml(e.descripcion || '—')}</small></td>
        <td>${est}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-warning" onclick="abrirModalEquipo(${e.id_equipo})"><i class="ti ti-edit"></i></button>
          ${btn}
        </td></tr>`;
    });
    $('#tbody-admin-equipos').html(html || '<tr><td colspan="7" class="text-center text-muted">Sin equipos registrados.</td></tr>');
    if (html) {
      _tablaAdminEquipos = $('#tabla-admin-equipos').DataTable({
        destroy: true,
        autoWidth: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        columnDefs: [{ targets: 6, orderable: false }]
      });
    }
  }).fail(function(xhr) { $('#tbody-admin-equipos').html(`<tr><td colspan="7" class="text-center text-danger">Error HTTP ${xhr.status}: ${xhr.responseText.substring(0,200)}</td></tr>`); });
}

function cargarSalasEnSelectAdminEquipo() {
  ajax('getAllSalas', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    let opts = '<option value="">— Seleccione sala —</option>';
    res.data.forEach(s => opts += `<option value="${s.id_sala}">[${escHtml(s.sede_nombre)}] ${escHtml(s.nombre)}</option>`);
    $('#equipo-sala').html(opts);
  });
}

function abrirModalEquipo(id_equipo) {
  $('#form-equipo')[0].reset(); $('#equipo-id').val(''); $('#modal-equipo-titulo').text('Nuevo Equipo AV');
  cargarSalasEnSelectAdminEquipo();
  if (id_equipo) {
    ajax('getAllEquipos', {}, 'GET').done(function(res) {
      const e = res.data.find(x => x.id_equipo == id_equipo); if (!e) return;
      $('#modal-equipo-titulo').text('Editar Equipo AV'); $('#equipo-id').val(e.id_equipo);
      setTimeout(() => {
        $('#equipo-sala').val(e.id_sala); $('#equipo-nombre').val(e.nombre);
        $('#equipo-tipo').val(e.tipo); $('#equipo-descripcion').val(e.descripcion);
      }, 300);
    });
  }
  new bootstrap.Modal(document.getElementById('modal-equipo')).show();
}

$('#form-equipo').on('submit', function(e) {
  e.preventDefault();
  ajax('guardarEquipo', $(this).serialize()).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-equipo')).hide();
    if (res.ok) Alerta.exito(res.msg).then(() => cargarAdminEquipos());
    else Alerta.error(res.msg);
  }).fail(() => Alerta.error('Error de comunicación.'));
});

function toggleEquipo(id_equipo, activo) {
  Alerta.confirmarPeligro(`¿Confirma que desea ${activo ? 'activar' : 'desactivar'} este equipo?`, function() {
    Swal.fire({ title: 'Procesando…', text: 'Por favor espere.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    ajax('toggleEquipo', { id_equipo, activo }).done(function(res) {
      if (res.ok) { Swal.close(); cargarAdminEquipos(); } else Alerta.error(res.msg);
    }).fail(() => Alerta.error('Error de comunicación.'));
  });
}

// ─── Cargar tab activo al cambiar ────────────────────────────
$(document).on('shown.bs.tab', '#admin-tabs a[data-bs-toggle="tab"]', function(e) {
  const target = $(e.target).attr('href');
  if (target === '#tab-sedes')   cargarAdminSedes();
  if (target === '#tab-salas')   cargarAdminSalas();
  if (target === '#tab-equipos') cargarAdminEquipos();
});

// Cargar sedes al iniciar (esperar a que DataTables esté disponible)
function initAdmin() {
  if (typeof $.fn.DataTable === 'undefined') {
    setTimeout(initAdmin, 100);
    return;
  }
  cargarAdminSedes();
}
$(document).ready(function() { initAdmin(); });
