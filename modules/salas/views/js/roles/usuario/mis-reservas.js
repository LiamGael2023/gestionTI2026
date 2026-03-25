'use strict';

// Wrappers para compatibilidad hacia atrás (funciones ahora en shared)
// Todos los helpers ya están definidos en shared/

// ── Tabla principal ───────────────────────────────────────────
let _dt = null;

function cargarMisReservas() {
  $('#tbody-mis-reservas').html(
    '<tr><td colspan="8" class="text-center py-4">' +
    '<div class="spinner-border spinner-border-sm me-2 text-primary"></div>Cargando...</td></tr>'
  );
  if (_dt) { _dt.destroy(); _dt = null; }

  ajax('getMisReservas', {}, 'GET').done(function(res) {
    if (!res.ok) { Alerta.error('Error al cargar sus reservas.'); return; }

    if (!res.data.length) {
      $('#tbody-mis-reservas').html(
        '<tr><td colspan="8" class="text-center text-muted py-5">' +
        '<i class="ti ti-calendar-off fs-1 d-block mb-2 opacity-50"></i>' +
        'No tiene solicitudes registradas aún.</td></tr>'
      );
      return;
    }

    let html = '';
    res.data.forEach(function(r) {
      const esPendiente = r.estado === 'PENDIENTE';
      const estadoLabel = r.estado.charAt(0) + r.estado.slice(1).toLowerCase();
      html += '<tr>'
        + '<td>' + r.id_reserva + '</td>'
        + '<td>' + escHtml(r.fecha) + '</td>'
        + '<td class="text-nowrap">' + escHtml(r.hora_inicio) + ' – ' + escHtml(r.hora_fin) + '</td>'
        + '<td><strong>' + escHtml(r.sala_nombre) + '</strong></td>'
        + '<td>' + escHtml(r.sede_nombre) + '</td>'
        + '<td><span title="' + escHtml(r.motivo) + '">'
          + escHtml(r.motivo.length > 50 ? r.motivo.substring(0, 50) + '…' : r.motivo)
          + '</span></td>'
        + '<td>' + badgeEstado(r.estado) + '</td>'
        + '<td class="text-center text-nowrap">'
        +   '<button class="btn btn-sm btn-info text-white me-1" onclick="verDetalle(' + r.id_reserva + ')" title="Ver detalle"><i class="ti ti-eye"></i></button>'
        + (esPendiente ? '<button class="btn btn-sm btn-warning me-1" onclick="abrirEditar(' + r.id_reserva + ')" title="Editar"><i class="ti ti-pencil"></i></button>' : '')
        + (esPendiente ? '<button class="btn btn-sm btn-danger" onclick="cancelar(' + r.id_reserva + ')" title="Cancelar"><i class="ti ti-x"></i></button>' : '')
        + '</td>'
        + '</tr>';
    });

    $('#tbody-mis-reservas').html(html);
    _dt = $('#tabla-mis-reservas').DataTable({
      destroy   : true,
      autoWidth : false,
      language  : { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
      order     : [[0, 'desc']],
      columnDefs: [{ targets: 7, orderable: false }],
    });
  }).fail(function() { Alerta.error('Error de conexión al cargar sus reservas.'); });
}

// ── Cancelar ─────────────────────────────────────────────────
function cancelar(id_reserva) {
  Alerta.confirmarPeligro('¿Está seguro de cancelar esta reserva? Esta acción no se puede deshacer.', function() {
    Swal.fire({ title: 'Cancelando…', text: 'Por favor espere.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    ajax('cancelarReserva', { id_reserva }).done(function(res) {
      if (res.ok) {
        Swal.fire({ icon:'success', title:'Cancelada', text:res.msg, timer:2000, showConfirmButton:false })
          .then(function() { cargarMisReservas(); });
      } else {
        Alerta.error(res.msg || 'Error al cancelar.');
      }
    }).fail(function() { Alerta.error('Error de comunicación.'); });
  });
}

// ── Ver Detalle ───────────────────────────────────────────────
function verDetalle(id_reserva) {
  $('#mr-detalle-id').text('#' + id_reserva);
  $('#mr-detalle-body').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
  new bootstrap.Modal(document.getElementById('modal-detalle-mr')).show();

  ajax('getReservaDetalle', { id_reserva: id_reserva }, 'GET').done(function(res) {
    if (!res.ok) {
      $('#mr-detalle-body').html('<div class="alert alert-danger">Reserva no encontrada.</div>'); return;
    }
    const d = res.data;
    const equiposHtml = d.equipos && d.equipos.length
      ? d.equipos.map(function(e) {
          return '<span class="badge bg-blue-lt me-1">' + escHtml(e.tipo) + ': ' + escHtml(e.nombre) + '</span>';
        }).join('')
      : '<span class="text-muted small">Ninguno</span>';

    const obsHtml = d.observacion_rechazo
      ? '<div class="col-12"><strong>Observación de rechazo:</strong><br><span class="text-danger">' + escHtml(d.observacion_rechazo) + '</span></div>'
      : '';

    $('#mr-detalle-body').html(
      '<div class="row g-3">'
      + '<div class="col-sm-6"><strong>Sede:</strong><br>' + escHtml(d.sede_nombre) + '</div>'
      + '<div class="col-sm-6"><strong>Sala:</strong><br>' + escHtml(d.sala_nombre) + ' <small class="text-muted">(cap: ' + d.capacidad + ')</small></div>'
      + '<div class="col-sm-4"><strong>Fecha:</strong><br>' + escHtml(d.fecha) + '</div>'
      + '<div class="col-sm-4"><strong>Hora Inicio:</strong><br>' + escHtml(d.hora_inicio) + '</div>'
      + '<div class="col-sm-4"><strong>Hora Fin:</strong><br>' + escHtml(d.hora_fin) + '</div>'
      + '<div class="col-12"><strong>Motivo:</strong><br>' + escHtml(d.motivo) + '</div>'
      + '<div class="col-12"><strong>Equipos AV:</strong><br>' + equiposHtml + '</div>'
      + '<div class="col-sm-6"><strong>Estado:</strong><br>' + badgeEstado(d.estado) + '</div>'
      + (d.autorizador_nombre && d.autorizador_nombre !== '—'
          ? '<div class="col-sm-6"><strong>Autorizador:</strong><br>' + escHtml(d.autorizador_nombre) + '</div>' : '')
      + (d.fecha_aprobacion
          ? '<div class="col-sm-6"><strong>F. Autorización:</strong><br><small class="text-muted">' + escHtml(d.fecha_aprobacion) + '</small></div>' : '')
      + obsHtml
      + '<div class="col-12"><strong>Registrado:</strong><br><small class="text-muted">' + escHtml(d.created_at) + '</small></div>'
      + '</div>'
      + '<hr class="my-3">'
      + '<h6><i class="ti ti-history me-1"></i>Historial de Estado</h6>'
      + '<div id="mr-hist-body"><div class="text-center"><div class="spinner-border spinner-border-sm text-secondary"></div></div></div>'
    );

    ajax('getHistorialReserva', { id_reserva: id_reserva }, 'GET').done(function(hr) {
      if (!hr.ok || !hr.data.length) {
        $('#mr-hist-body').html('<small class="text-muted">Sin cambios de estado registrados.</small>'); return;
      }
      let hh = '<ul class="timeline">';
      hr.data.forEach(function(h) {
        hh += '<li class="timeline-event">'
          + '<div class="timeline-event-icon bg-blue-lt"><i class="ti ti-git-commit text-blue"></i></div>'
          + '<div class="card timeline-event-card"><div class="card-body py-2 px-3">'
          + '<div class="small text-muted">' + escHtml(h.fecha_accion) + ' — <strong>' + escHtml(h.usuario_accion) + '</strong></div>'
          + '<div>' + badgeEstado(h.estado_anterior) + ' → ' + badgeEstado(h.estado_nuevo) + '</div>'
          + (h.observacion ? '<div class="small text-danger mt-1">' + escHtml(h.observacion) + '</div>' : '')
          + '</div></div></li>';
      });
      hh += '</ul>';
      $('#mr-hist-body').html(hh);
    });
  }).fail(function() {
    $('#mr-detalle-body').html('<div class="alert alert-danger">Error al cargar el detalle.</div>');
  });
}

// ── Editar ───────────────────────────────────────────────────
function abrirEditar(id_reserva) {
  ajax('getReservaDetalle', { id_reserva: id_reserva }, 'GET').done(function(res) {
    if (!res.ok) { Alerta.error('Reserva no encontrada.'); return; }
    const d = res.data;
    if (d.estado !== 'PENDIENTE') { Alerta.advertencia('Solo se pueden editar reservas en estado PENDIENTE.'); return; }

    $('#edit-disponibilidad').hide().html('');
    $('#edit-equipos-section').hide();
    $('#edit-equipos-lista').html('');
    $('#edit-sala').prop('disabled', true).html('<option value="">— Seleccione sala —</option>');

    $('#edit-id-reserva').val(id_reserva);
    $('#edit-fecha').val(d.fecha);
    $('#edit-hora-inicio').val(d.hora_inicio);
    $('#edit-hora-fin').val(d.hora_fin);
    $('#edit-motivo').val(d.motivo);
    $('#btn-guardar-editar').prop('disabled', false);

    new bootstrap.Modal(document.getElementById('modal-editar-mr')).show();

    ajax('getSedes', {}, 'GET').done(function(resSedes) {
      if (!resSedes.ok) return;
      let opts = '<option value="">— Seleccione sede —</option>';
      resSedes.data.forEach(function(s) { opts += '<option value="' + s.id + '">' + escHtml(s.nombre) + '</option>'; });
      $('#edit-sede').html(opts).val(d.id_sede);

      ajax('getSalasBySede', { id_sede: d.id_sede }, 'GET').done(function(resSalas) {
        if (!resSalas.ok || !resSalas.data.length) {
          $('#edit-sala').html('<option value="">Sin salas disponibles</option>'); return;
        }
        let optsSala = '<option value="">— Seleccione sala —</option>';
        resSalas.data.forEach(function(s) { optsSala += '<option value="' + s.id_sala + '">' + escHtml(s.nombre) + '</option>'; });
        $('#edit-sala').html(optsSala).prop('disabled', false).val(String(d.id_sala));
        editCargarEquipos(d.id_sala, d.equipos);
      });
    });
  }).fail(function() { Alerta.error('Error al cargar la reserva.'); });
}

function editCargarEquipos(id_sala, equipos_preselect) {
  $('#edit-equipos-section').hide();
  $('#edit-equipos-lista').html('');
  if (!id_sala) return;

  ajax('getEquiposBySala', { id_sala: id_sala }, 'GET').done(function(res) {
    if (!res.ok || !res.data.length) {
      $('#edit-equipos-section').show();
      $('#edit-equipos-lista').html('<p class="text-muted small mb-0">Esta sala no tiene equipos AV registrados.</p>');
      return;
    }
    let html = '';
    res.data.forEach(function(eq) {
      const checked = equipos_preselect && equipos_preselect.some(function(ep) { return ep.id_equipo == eq.id_equipo; });
      html += '<div class="col-6 col-sm-4">'
        + '<label class="form-check">'
        + '<input class="form-check-input edit-equipo" type="checkbox" name="equipos[]" value="' + eq.id_equipo + '"' + (checked ? ' checked' : '') + '>'
        + '<span class="form-check-label small">'
        + '<i class="ti ti-device-projector me-1 text-secondary"></i>' + escHtml(eq.nombre)
        + '<small class="text-muted d-block">' + escHtml(eq.tipo) + '</small>'
        + '</span></label></div>';
    });
    $('#edit-equipos-lista').html(html);
    $('#edit-equipos-section').show();
  });
}

function editVerificarDisponibilidad() {
  const id_sala    = $('#edit-sala').val();
  const fecha      = $('#edit-fecha').val();
  const h_inicio   = $('#edit-hora-inicio').val();
  const h_fin      = $('#edit-hora-fin').val();
  const id_reserva = $('#edit-id-reserva').val();
  const $disp = $('#edit-disponibilidad');
  const $btn  = $('#btn-guardar-editar');

  if (!id_sala || !fecha || !h_inicio || !h_fin) { $disp.hide(); return; }
  if (h_fin <= h_inicio) {
    $disp.show().html('<div class="alert alert-danger py-2 mb-0 small"><i class="ti ti-alert-triangle me-1"></i>Hora fin debe ser mayor a hora inicio.</div>');
    $btn.prop('disabled', true); return;
  }

  $disp.show().html('<div class="text-muted small"><i class="ti ti-loader me-1"></i>Verificando disponibilidad...</div>');
  $btn.prop('disabled', true);

  $.post(AJAX + '?action=verificarDisponibilidad', {
    id_sala: id_sala, fecha: fecha, hora_inicio: h_inicio, hora_fin: h_fin, excluir_id: id_reserva
  }).done(function(res) {
    if (res.ok && res.data.disponible) {
      $disp.html('<div class="alert alert-success py-2 mb-0 small"><i class="ti ti-circle-check me-1"></i>Horario disponible.</div>');
      $btn.prop('disabled', false);
    } else {
      const msg = (res.data && res.data.mensaje) ? res.data.mensaje : 'Horario no disponible.';
      $disp.html('<div class="alert alert-danger py-2 mb-0 small"><i class="ti ti-alert-circle me-1"></i>' + escHtml(msg) + '</div>');
      $btn.prop('disabled', true);
    }
  }).fail(function() { $disp.hide(); $btn.prop('disabled', false); });
}

$('#edit-sede').on('change', function() {
  const id_sede = $(this).val();
  $('#edit-sala').prop('disabled', true).html('<option value="">Cargando...</option>');
  $('#edit-equipos-section').hide();
  $('#edit-disponibilidad').hide();
  if (!id_sede) { $('#edit-sala').html('<option value="">— Seleccione sala —</option>'); return; }
  ajax('getSalasBySede', { id_sede: id_sede }, 'GET').done(function(res) {
    if (!res.ok || !res.data.length) { $('#edit-sala').html('<option value="">Sin salas</option>'); return; }
    let opts = '<option value="">— Seleccione sala —</option>';
    res.data.forEach(function(s) { opts += '<option value="' + s.id_sala + '">' + escHtml(s.nombre) + '</option>'; });
    $('#edit-sala').html(opts).prop('disabled', false);
    if (res.data.length === 1) $('#edit-sala').val(res.data[0].id_sala).trigger('change');
  });
});

$('#edit-sala').on('change', function() {
  editCargarEquipos($(this).val(), null);
  editVerificarDisponibilidad();
});

$('#edit-fecha, #edit-hora-inicio, #edit-hora-fin').on('change', editVerificarDisponibilidad);

$('#form-editar-mr').on('submit', function(e) {
  e.preventDefault();
  const id_reserva  = $('#edit-id-reserva').val();
  const id_sala     = $('#edit-sala').val();
  const fecha       = $('#edit-fecha').val();
  const hora_inicio = $('#edit-hora-inicio').val();
  const hora_fin    = $('#edit-hora-fin').val();
  const motivo      = $('#edit-motivo').val().trim();

  if (!id_sala || !fecha || !hora_inicio || !hora_fin || !motivo) { Alerta.advertencia('Complete todos los campos obligatorios.'); return; }
  if (hora_fin <= hora_inicio) { Alerta.error('La hora de fin debe ser posterior a la hora de inicio.'); return; }

  const equipos = [];
  $('.edit-equipo:checked').each(function() { equipos.push($(this).val()); });

  var modalEditarEl = document.getElementById('modal-editar-mr');
  bootstrap.Modal.getInstance(modalEditarEl).hide();
  $(modalEditarEl).one('hidden.bs.modal', function() {
    Swal.fire({ title: 'Guardando cambios…', text: 'Por favor espere.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    ajax('editarReserva', { id_reserva: id_reserva, id_sala: id_sala, fecha: fecha, hora_inicio: hora_inicio, hora_fin: hora_fin, motivo: motivo, 'equipos[]': equipos })
      .done(function(res) {
        if (res.ok) {
          Swal.fire({ icon:'success', title:'Actualizado', text:res.msg, timer:2000, showConfirmButton:false })
            .then(function() { cargarMisReservas(); });
        } else {
          Alerta.error(res.msg || 'Error al actualizar.');
        }
      }).fail(function() { Alerta.error('Error de comunicación.'); });
  });
});

// ── Auto-carga ────────────────────────────────────────────────
$(document).ready(function() { cargarMisReservas(); });
