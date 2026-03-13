/**
 * autorizaciones.js — Lógica JavaScript de la vista Solicitudes Pendientes
 * Requiere: jQuery, DataTables, SweetAlert2
 * Variable PHP inyectada desde html/autorizaciones.php: AJAX_PEND
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

function reqPend(action, data, method) {
  return $.ajax({ url: AJAX_PEND + '?action=' + action, method: method || 'POST', data: data || {}, dataType: 'json', cache: false });
}

function escP(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function badgeP(estado) {
  var m = {
    'APROBADA' : '<span class="badge bg-success-lt text-success">Aprobada</span>',
    'RECHAZADA': '<span class="badge bg-danger-lt text-danger">Rechazada</span>',
    'CANCELADA': '<span class="badge bg-secondary-lt text-secondary">Cancelada</span>',
    'PENDIENTE': '<span class="badge bg-warning-lt text-warning">Pendiente</span>',
  };
  return m[estado] || '<span class="badge bg-secondary">' + escP(estado) + '</span>';
}

var _dtPend = null;

function cargarPendientes() {
  $('#tbody-pendientes-pag').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm me-2 text-warning"></div>Cargando...</td></tr>');
  if (_dtPend) { _dtPend.destroy(); _dtPend = null; }

  reqPend('getPendientes', {}, 'GET').done(function(res) {
    if (!res.ok) {
      $('#tbody-pendientes-pag').html('<tr><td colspan="8" class="text-center text-danger py-4">' + escP(res.msg || 'Error al cargar.') + '</td></tr>');
      return;
    }
    var n = res.data ? res.data.length : 0;
    $('#badge-total-pend').text(n);

    if (!n) {
      $('#tbody-pendientes-pag').html(
        '<tr><td colspan="8" class="text-center text-muted py-5">' +
        '<i class="ti ti-checks fs-1 text-success d-block mb-2"></i>' +
        'No hay solicitudes pendientes de autorización.</td></tr>'
      );
      return;
    }

    var html = '';
    $.each(res.data, function(i, r) {
      var motivo = r.motivo && r.motivo.length > 45 ? r.motivo.substring(0, 45) + '…' : r.motivo;
      html += '<tr>'
        + '<td>' + r.id_reserva + '</td>'
        + '<td><strong>' + escP(r.solicitante_nombre) + '</strong><br><small class="text-muted">' + escP(r.solicitante_correo) + '</small></td>'
        + '<td><strong>' + escP(r.sede_nombre) + '</strong><br><small>' + escP(r.sala_nombre) + '</small></td>'
        + '<td class="text-nowrap">' + escP(r.fecha) + '</td>'
        + '<td class="text-nowrap">' + escP(r.hora_inicio) + ' – ' + escP(r.hora_fin) + '</td>'
        + '<td><span title="' + escP(r.motivo) + '">' + escP(motivo) + '</span></td>'
        + '<td><small class="text-muted">' + escP(r.created_at) + '</small></td>'
        + '<td class="text-center text-nowrap">'
        +   '<button class="btn btn-sm btn-outline-primary me-1" onclick="verDetallePend(' + r.id_reserva + ')" title="Ver detalle"><i class="ti ti-eye"></i></button>'
        +   '<button class="btn btn-sm btn-success me-1" onclick="aprobarReserva(' + r.id_reserva + ')" title="Aprobar"><i class="ti ti-circle-check"></i></button>'
        +   '<button class="btn btn-sm btn-danger" onclick="abrirRechazar(' + r.id_reserva + ')" title="Rechazar"><i class="ti ti-circle-x"></i></button>'
        + '</td>'
        + '</tr>';
    });

    $('#tbody-pendientes-pag').html(html);
    _dtPend = $('#tabla-pendientes-pag').DataTable({
      destroy   : true,
      autoWidth : false,
      language  : { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
      order     : [[3, 'asc'], [4, 'asc']],
      columnDefs: [{ targets: 7, orderable: false }]
    });
  }).fail(function(xhr) {
    $('#tbody-pendientes-pag').html('<tr><td colspan="8" class="text-center text-danger py-4">Error HTTP ' + xhr.status + '</td></tr>');
  });
}

/* ── Detalle ──────────────────────────────────────────────── */
function verDetallePend(id_reserva) {
  $('#det-pend-id').text('#' + id_reserva);
  $('#det-pend-body').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
  $('#det-pend-footer').html('');
  new bootstrap.Modal(document.getElementById('modal-det-pend')).show();

  reqPend('getReservaDetalle', { id_reserva: id_reserva }, 'GET').done(function(res) {
    if (!res.ok) { $('#det-pend-body').html('<div class="alert alert-danger">Reserva no encontrada.</div>'); return; }
    var d = res.data;
    var equipos = '';
    if (d.equipos && d.equipos.length) {
      $.each(d.equipos, function(i, e) {
        equipos += '<span class="badge bg-blue-lt text-blue me-1">' + escP(e.tipo) + ': ' + escP(e.nombre) + '</span>';
      });
    } else { equipos = '<span class="text-muted small">Ninguno</span>'; }

    $('#det-pend-body').html(
      '<div class="row g-3">'
      + '<div class="col-sm-6"><strong>Sede:</strong><br>' + escP(d.sede_nombre) + '</div>'
      + '<div class="col-sm-6"><strong>Sala:</strong><br>' + escP(d.sala_nombre) + ' <small class="text-muted">(cap: ' + d.capacidad + ')</small></div>'
      + '<div class="col-sm-4"><strong>Fecha:</strong><br>' + escP(d.fecha) + '</div>'
      + '<div class="col-sm-4"><strong>Hora inicio:</strong><br>' + escP(d.hora_inicio) + '</div>'
      + '<div class="col-sm-4"><strong>Hora fin:</strong><br>' + escP(d.hora_fin) + '</div>'
      + '<div class="col-12"><strong>Motivo:</strong><br>' + escP(d.motivo) + '</div>'
      + '<div class="col-12"><strong>Equipos AV:</strong><br>' + equipos + '</div>'
      + '<div class="col-sm-6"><strong>Solicitante:</strong><br>' + escP(d.solicitante_nombre) + '</div>'
      + '<div class="col-sm-6"><strong>Estado:</strong><br>' + badgeP(d.estado) + '</div>'
      + '<div class="col-12"><strong>Registrado:</strong><br><small class="text-muted">' + escP(d.created_at) + '</small></div>'
      + '</div>'
      + '<hr class="my-3">'
      + '<h6><i class="ti ti-history me-1"></i>Historial de Estado</h6>'
      + '<div id="hist-det-pend"><div class="text-center"><div class="spinner-border spinner-border-sm text-secondary"></div></div></div>'
    );

    if (d.estado === 'PENDIENTE') {
      $('#det-pend-footer').html(
        '<button class="btn btn-success me-2" onclick="aprobarReserva(' + d.id_reserva + ', true)"><i class="ti ti-circle-check me-1"></i>Aprobar</button>'
        + '<button class="btn btn-danger" onclick="abrirRechazar(' + d.id_reserva + ', true)"><i class="ti ti-circle-x me-1"></i>Rechazar</button>'
        + '<button class="btn btn-secondary ms-auto" data-bs-dismiss="modal">Cerrar</button>'
      );
    } else {
      $('#det-pend-footer').html('<button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>');
    }

    reqPend('getHistorialReserva', { id_reserva: id_reserva }, 'GET').done(function(hr) {
      if (!hr.ok || !hr.data.length) {
        $('#hist-det-pend').html('<small class="text-muted">Sin cambios de estado registrados.</small>'); return;
      }
      var hh = '<ul class="timeline">';
      $.each(hr.data, function(i, h) {
        hh += '<li class="timeline-event">'
          + '<div class="timeline-event-icon bg-blue-lt"><i class="ti ti-git-commit text-blue"></i></div>'
          + '<div class="card timeline-event-card"><div class="card-body py-2 px-3">'
          + '<div class="small text-muted">' + escP(h.fecha_accion) + ' — <strong>' + escP(h.usuario_accion) + '</strong></div>'
          + '<div>' + badgeP(h.estado_anterior) + ' → ' + badgeP(h.estado_nuevo) + '</div>'
          + (h.observacion ? '<div class="small text-danger mt-1">' + escP(h.observacion) + '</div>' : '')
          + '</div></div></li>';
      });
      hh += '</ul>';
      $('#hist-det-pend').html(hh);
    });
  }).fail(function() { $('#det-pend-body').html('<div class="alert alert-danger">Error de comunicación.</div>'); });
}

/* ── Aprobar ──────────────────────────────────────────────── */
function aprobarReserva(id_reserva, cerrarModal) {
  Swal.fire({
    icon: 'question', title: '¿Aprobar solicitud?',
    text: 'La reserva #' + id_reserva + ' será aprobada.',
    showCancelButton: true, confirmButtonText: 'Sí, aprobar',
    cancelButtonText: 'Cancelar', confirmButtonColor: '#2fb344'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    if (cerrarModal) bootstrap.Modal.getInstance(document.getElementById('modal-det-pend')).hide();
    Swal.fire({ title: 'Aprobando…', text: 'Por favor espere.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: function() { Swal.showLoading(); } });
    reqPend('aprobarReserva', { id_reserva: id_reserva }).done(function(res) {
      if (res.ok) {
        Swal.fire({ icon:'success', title:'Aprobada', text: res.msg, timer: 2000, showConfirmButton: false });
        cargarPendientes();
      } else {
        Swal.fire({ icon:'error', title:'Error', text: res.msg || 'No se pudo aprobar.' });
      }
    }).fail(function() { Swal.fire({ icon:'error', title:'Error', text:'Error de comunicación.' }); });
  });
}

/* ── Rechazar ─────────────────────────────────────────────── */
function abrirRechazar(id_reserva, cerrarDetalle) {
  if (cerrarDetalle) bootstrap.Modal.getInstance(document.getElementById('modal-det-pend')).hide();
  $('#rechazar-pend-id').val(id_reserva);
  $('#rechazar-pend-obs').val('');
  setTimeout(function() {
    new bootstrap.Modal(document.getElementById('modal-rechazar-pend')).show();
  }, cerrarDetalle ? 350 : 0);
}

function confirmarRechazo() {
  var id_reserva  = $('#rechazar-pend-id').val();
  var observacion = $('#rechazar-pend-obs').val().trim();
  var modalEl = document.getElementById('modal-rechazar-pend');
  bootstrap.Modal.getInstance(modalEl).hide();
  $(modalEl).one('hidden.bs.modal', function() {
    Swal.fire({ title: 'Procesando…', text: 'Por favor espere.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: function() { Swal.showLoading(); } });
    reqPend('rechazarReserva', { id_reserva: id_reserva, observacion: observacion }).done(function(res) {
      if (res.ok) {
        Swal.fire({ icon:'success', title:'Rechazada', text: res.msg, timer: 2000, showConfirmButton: false });
        cargarPendientes();
      } else {
        Swal.fire({ icon:'error', title:'Error', text: res.msg || 'No se pudo rechazar.' });
      }
    }).fail(function() { Swal.fire({ icon:'error', title:'Error', text:'Error de comunicación.' }); });
  });
}

// Carga automática al abrir
cargarPendientes();
