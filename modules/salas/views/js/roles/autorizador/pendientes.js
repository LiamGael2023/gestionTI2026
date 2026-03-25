/**
 * roles/autorizador/pendientes.js — Pendientes para Autorizadores (AUTORIZADOR, ADMIN)
 * Funciones: cargarPendientes, aprobarReserva, confirmarRechazo, abrirModalRechazar, actualizarBadgePendientes
 * Requiere: shared/api.js, shared/alerts.js, shared/utils.js, features/calendario.js
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

// ============================================================
// PENDIENTES (solo si ES_AUTORIZADOR_O_ADMIN)
// ============================================================
let _tablaPendientes = null;

function cargarPendientes() {
  if (!ES_AUTORIZADOR_O_ADMIN) return;
  ajax('getPendientes', {}, 'GET').done(function(res) {
    if (!res.ok) { Alerta.error('Error al cargar pendientes.'); return; }
    if (_tablaPendientes) { _tablaPendientes.destroy(); _tablaPendientes = null; }
    let html = '';
    if (!res.data.length) {
      html = `<tr><td colspan="8" class="text-center text-muted py-4">
                <i class="ti ti-checks me-1 text-success"></i>No hay solicitudes pendientes.
              </td></tr>`;
    } else {
      res.data.forEach(r => {
        html += `<tr>
          <td>${r.id_reserva}</td>
          <td><strong>${escHtml(r.solicitante_nombre)}</strong><br><small class="text-muted">${escHtml(r.solicitante_correo)}</small></td>
          <td><strong>${escHtml(r.sede_nombre)}</strong><br><small>${escHtml(r.sala_nombre)}</small></td>
          <td>${r.fecha}</td>
          <td>${r.hora_inicio} – ${r.hora_fin}</td>
          <td><span title="${escHtml(r.motivo)}">${escHtml(r.motivo.length>40?r.motivo.substring(0,40)+'…':r.motivo)}</span></td>
          <td><small class="text-muted">${r.created_at}</small></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary me-1" onclick="verDetalleReserva(${r.id_reserva})"><i class="ti ti-eye"></i></button>
            <button class="btn btn-sm btn-success me-1" onclick="aprobarReserva(${r.id_reserva})"><i class="ti ti-circle-check"></i></button>
            <button class="btn btn-sm btn-danger" onclick="abrirModalRechazar(${r.id_reserva})"><i class="ti ti-circle-x"></i></button>
          </td>
        </tr>`;
      });
    }
    $('#tbody-pendientes').html(html);
    _tablaPendientes = $('#tabla-pendientes').DataTable({
      destroy   : true,
      language  : { url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
      order     : [[3,'asc'],[4,'asc']],
      columnDefs: [{ targets:7, orderable:false }],
    });
    const n = res.data.length;
    $('#badge-pendientes').text(n);
    $('#badge-pendientes-side').text(n);
  }).fail(() => Alerta.error('Error de conexión al cargar pendientes.'));
}

function aprobarReserva(id_reserva) {
  Alerta.confirmar('¿Confirma que desea APROBAR esta solicitud?', function() {
    ajax('aprobarReserva', { id_reserva }).done(function(res) {
      if (res.ok) { Alerta.exito(res.msg).then(() => { cargarPendientes(); actualizarBadgePendientes(); actualizarEstadisticas(); if(window._calPrincipal) window._calPrincipal.refetchEvents(); });
      } else { Alerta.error(res.msg || 'No se pudo aprobar.'); }
    }).fail(() => Alerta.error('Error de comunicación.'));
  });
}

function abrirModalRechazar(id_reserva) {
  $('#rechazar-id-reserva').val(id_reserva);
  $('#rechazar-observacion').val('');
  new bootstrap.Modal(document.getElementById('modal-rechazar')).show();
}

function confirmarRechazo() {
  const id_reserva  = $('#rechazar-id-reserva').val();
  const observacion = $('#rechazar-observacion').val().trim();
  ajax('rechazarReserva', { id_reserva, observacion }).done(function(res) {
    bootstrap.Modal.getInstance(document.getElementById('modal-rechazar')).hide();
    if (res.ok) { Alerta.exito(res.msg).then(() => { cargarPendientes(); actualizarBadgePendientes(); actualizarEstadisticas(); if(window._calPrincipal) window._calPrincipal.refetchEvents(); });
    } else { Alerta.error(res.msg || 'No se pudo rechazar.'); }
  }).fail(() => Alerta.error('Error de comunicación.'));
}

function actualizarBadgePendientes() {
  if (!ES_AUTORIZADOR_O_ADMIN) return;
  ajax('getPendientes', {}, 'GET').done(function(res) {
    if (res.ok) {
      const n = res.data.length;
      $('#badge-pendientes').text(n);
      $('#badge-pendientes-side').text(n);
    }
  });
}
