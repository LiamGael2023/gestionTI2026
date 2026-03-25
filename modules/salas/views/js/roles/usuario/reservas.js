/**
 * roles/usuario/reservas.js — Reservas de Usuario (USUARIO, AUTORIZADOR, ADMIN)
 * Funciones: abrirNuevaSolicitud, cargarSedes, verFotoSala, iniciarCalendarioNuevaReserva
 * Requiere: shared/api.js, shared/alerts.js, shared/utils.js, features/calendario.js
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

// ============================================================
// NUEVA SOLICITUD (Modal)
// ============================================================
function abrirNuevaSolicitud() {
  // Resetear siempre antes de abrir para no mostrar datos de una apertura anterior
  $('#form-nueva-reserva')[0].reset();
  $('#nr-sala').prop('disabled', true).html('<option value="">— Seleccione primero la sede —</option>');
  $('#nr-sala-capacidad').text('');
  $('#nr-equipos-section').hide();
  $('#nr-equipos-lista').html('');
  $('#nr-disponibilidad-result').addClass('d-none');
  $('#btn-guardar-reserva').prop('disabled', true);
  $('#btn-verificar').prop('disabled', true);
  $('#btn-ver-sala').prop('disabled', true);
  window._salaFoto = ''; window._salaNombre = ''; window._salaCap = '';
  resetCalendarioNuevaReserva();
  cargarSedes();
  new bootstrap.Modal(document.getElementById('modal-nueva-solicitud')).show();
}

// ============================================================
// SEDES — cargar en dropdowns de formularios
// ============================================================
function cargarSedes(callback) {
  ajax('getSedes', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    let opts  = '<option value="">— Seleccione una sede —</option>';
    let opts2 = '<option value="">— Seleccione sede —</option>';
    res.data.forEach(s => {
      opts  += `<option value="${s.id}">${escHtml(s.nombre)}</option>`;
      opts2 += `<option value="${s.id}">${escHtml(s.nombre)}</option>`;
    });
    $('#nr-sede').html(opts);
    $('#edit-sede').html(opts2);
    if (typeof callback === 'function') callback();
  });
}

// ============================================================
// NUEVA RESERVA — onChange de Sede
// ============================================================
$('#nr-sede').on('change', function() {
  const id_sede = $(this).val();
  $('#nr-sala').prop('disabled', true).html('<option value="">— Cargando salas —</option>');
  $('#nr-sala-capacidad').text('');
  $('#nr-equipos-section').hide();
  $('#nr-equipos-lista').html('');
  $('#btn-verificar').prop('disabled', true);
  $('#btn-guardar-reserva').prop('disabled', true);
  $('#btn-ver-sala').prop('disabled', true);
  window._salaFoto = ''; window._salaNombre = ''; window._salaCap = '';
  resetCalendarioNuevaReserva();
  if (!id_sede) return;

  ajax('getSalasBySede', { id_sede }, 'GET').done(function(res) {
    if (!res.ok || !res.data.length) {
      $('#nr-sala').html('<option value="">— Sin salas disponibles —</option>');
      return;
    }
    let opts = '<option value="">— Seleccione sala —</option>';
    res.data.forEach(s => opts += `<option value="${s.id_sala}" data-cap="${s.capacidad}" data-foto="${s.foto_ruta || ''}">${escHtml(s.nombre)}</option>`);
    $('#nr-sala').html(opts).prop('disabled', false);
    if (res.data.length === 1) $('#nr-sala').val(res.data[0].id_sala).trigger('change');
  });
});

// ============================================================
// NUEVA RESERVA — onChange de Sala
// ============================================================
$('#nr-sala').on('change', function() {
  const id_sala = $(this).val();
  const cap     = $(this).find(':selected').attr('data-cap');
  const foto    = $(this).find(':selected').attr('data-foto');
  const nombre  = $(this).find(':selected').text();
  $('#nr-sala-capacidad').text(id_sala ? `Capacidad: ${cap} personas` : '');
  // Guardar foto y nombre para el modal
  window._salaNombre = id_sala ? nombre : '';
  window._salaFoto   = id_sala && foto ? foto : '';
  window._salaCap    = cap || '';
  // Habilitar/deshabilitar botón
  $('#btn-ver-sala').prop('disabled', !(id_sala && foto));
  $('#btn-verificar').prop('disabled', !id_sala);
  $('#btn-guardar-reserva').prop('disabled', true);
  $('#nr-equipos-section').hide();
  $('#nr-equipos-lista').html('');
  resetCalendarioNuevaReserva();
  if (!id_sala) {
    $('#btn-ver-sala').prop('disabled', true);
    return;
  }

  if (window._calNuevaReserva) {
    window._calNuevaReserva.setOption('events', {
      url   : AJAX + '?action=getEventosCalendar&id_sala=' + id_sala,
      method: 'GET',
      failure: () => Alerta.error('Error al cargar el calendario.'),
    });
    window._calNuevaReserva.refetchEvents();
    $('#nr-calendario-aviso').hide();
    $('#calendar').show();
  } else {
    iniciarCalendarioNuevaReserva(id_sala);
  }

  $('#nr-equipos-lista').html('<div class="col-12 text-muted small"><div class="spinner-border spinner-border-sm me-1"></div>Cargando equipos...</div>');
  $('#nr-equipos-section').show();

  ajax('getEquiposBySala', { id_sala }, 'GET').done(function(res) {
    if (!res.ok || !res.data.length) {
      $('#nr-equipos-lista').html(
        '<div class="col-12"><div class="alert alert-light border py-2 mb-0 small text-muted">' +
        '<i class="ti ti-device-projector me-1 opacity-50"></i>Esta sala no tiene equipos audiovisuales registrados.</div></div>'
      );
      return;
    }
    let html = '';
    res.data.forEach(e => {
      const icono = {
        'Proyector':'ti-device-projector', 'Televisor':'ti-device-tv',
        'Pantalla':'ti-photo', 'Micrófono':'ti-microphone',
        'Videoconferencia':'ti-video', 'Pizarra Digital':'ti-writing',
        'Sistema de Sonido':'ti-volume', 'Puntero Láser':'ti-pointer'
      }[e.tipo] || 'ti-device-audio';
      html += `<div class="col-6 col-sm-4">
        <div class="equipo-card border rounded p-2 small d-flex align-items-start gap-2"
             style="cursor:pointer;user-select:none;transition:border-color .15s,background .15s;"
             data-id="${e.id_equipo}">
          <i class="ti ${icono} text-blue mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
          <span>
            <strong>${escHtml(e.nombre)}</strong>
            <small class="text-muted d-block">${escHtml(e.tipo)}</small>
          </span>
        </div>
      </div>`;
    });
    $('#nr-equipos-lista').html(html);

    // Toggle selección al hacer clic en la tarjeta
    $('#nr-equipos-lista').off('click.equipo').on('click.equipo', '.equipo-card', function() {
      $(this).toggleClass('equipo-seleccionado');
      if ($(this).hasClass('equipo-seleccionado')) {
        $(this).css({ 'border-color': '#1a56a8', 'background': '#c8d9f5', 'border-width': '2px', 'color': '#1a3a6b' });
      } else {
        $(this).css({ 'border-color': '', 'background': '', 'border-width': '', 'color': '' });
      }
    });
  });
});

// ============================================================
// NUEVA RESERVA — Verificar disponibilidad
// ============================================================
$('#btn-verificar').on('click', function() {
  const id_sala     = $('#nr-sala').val();
  const hora_inicio = $('#nr-hora-inicio').val();
  const hora_fin    = $('#nr-hora-fin').val();
  const fecha       = $('#nr-fecha').val();
  if (!id_sala || !fecha || !hora_inicio || !hora_fin) {
    Alerta.advertencia('Complete sala, fecha y horario antes de verificar.'); return;
  }
  ajax('verificarDisponibilidad', { id_sala, fecha, hora_inicio, hora_fin })
    .done(function(res) {
      const div = $('#nr-disponibilidad-result').removeClass('d-none');
      if (res.data.disponible) {
        div.html(`<div class="alert alert-success py-2 small"><i class="ti ti-circle-check me-1"></i>${escHtml(res.data.mensaje)}</div>`);
        $('#btn-guardar-reserva').prop('disabled', false);
      } else {
        div.html(`<div class="alert alert-danger py-2 small"><i class="ti ti-circle-x me-1"></i>${escHtml(res.data.mensaje)}</div>`);
        $('#btn-guardar-reserva').prop('disabled', true);
      }
    })
    .fail(() => Alerta.error('Error al verificar disponibilidad.'));
});

$('#nr-motivo').on('input', function() { $('#nr-motivo-count').text($(this).val().length); });

// ============================================================
// NUEVA RESERVA — Enviar formulario
// ============================================================
$('#form-nueva-reserva').on('submit', async function(e) {
  e.preventDefault();
  const id_sala     = $('#nr-sala').val();
  const id_sede     = $('#nr-sede').val();
  const fecha       = $('#nr-fecha').val();
  const hora_inicio = $('#nr-hora-inicio').val();
  const hora_fin    = $('#nr-hora-fin').val();
  const motivo      = $('#nr-motivo').val().trim();

  if (!id_sede || !id_sala || !fecha || !hora_inicio || !hora_fin || !motivo) {
    Alerta.advertencia('Complete todos los campos obligatorios.'); return;
  }
  if (hora_fin <= hora_inicio) { Alerta.error('La hora de fin debe ser posterior a la hora de inicio.'); return; }

  const equipos = [];
  $('.equipo-card.equipo-seleccionado').each(function() { equipos.push($(this).data('id')); });

  // 1. Preguntar confirmación
  const confirmResult = await Swal.fire({
    icon: 'question', title: '¿Confirmar?',
    text: '¿Confirma el envío de la solicitud de reserva?',
    showCancelButton: true,
    confirmButtonText: 'Sí, confirmar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#2fb344'
  });
  if (!confirmResult.isConfirmed) return;

  // 2. Cerrar el modal Bootstrap y esperar a que termine su animación
  const modalEl = document.getElementById('modal-nueva-solicitud');
  const bsModal = bootstrap.Modal.getInstance(modalEl);
  if (bsModal) {
    bsModal.hide();
    await new Promise(resolve => modalEl.addEventListener('hidden.bs.modal', resolve, { once: true }));
  }

  // 3. Mostrar loader (ya sin conflicto con el modal)
  Swal.fire({
    title: 'Enviando solicitud…',
    text: 'Por favor espere.',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading()
  });

  // 4. Ejecutar la petición AJAX
  try {
    const res = await ajax('crearReserva', { id_sala, fecha, hora_inicio, hora_fin, motivo, 'equipos[]': equipos });
    
    if (res.ok) {
      // Reset del formulario
      $('#form-nueva-reserva')[0].reset();
      $('#nr-sala').prop('disabled', true).html('<option value="">— Seleccione primero la sede —</option>');
      $('#nr-sala-capacidad').text('');
      $('#nr-equipos-section').hide();
      $('#nr-disponibilidad-result').addClass('d-none');
      $('#btn-guardar-reserva').prop('disabled', true);
      $('#btn-verificar').prop('disabled', true);
      
      try {
        resetCalendarioNuevaReserva();
      } catch (err) {
        console.warn('Advertencia al resetear calendario:', err);
      }
      
      // Mostrar éxito
      Swal.fire({ 
        icon: 'success', 
        title: 'Éxito', 
        text: res.msg || 'Solicitud registrada correctamente.', 
        timer: 3000, 
        showConfirmButton: false 
      });
      
      // Refrescar conteos y calendario al instante, con reintentos cortos de sincronización.
      try {
        if (typeof window.actualizarEstadisticasLateral === 'function') {
          window.actualizarEstadisticasLateral();
          setTimeout(window.actualizarEstadisticasLateral, 350);
          setTimeout(window.actualizarEstadisticasLateral, 1200);
        }
        if (window._calPrincipal && typeof window._calPrincipal.refetchEvents === 'function') {
          window._calPrincipal.refetchEvents();
        }
      } catch (err) {
        console.warn('Advertencia al refrescar calendario/estadísticas:', err);
      }
      
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.msg || 'Error al registrar la solicitud.' });
    }
  } catch (err) {
    console.error('Error en crearReserva:', err);
  }
});

// ============================================================
// CALENDARIO — Mini (Nueva Reserva)
// ============================================================
function iniciarCalendarioNuevaReserva(id_sala) {
  const el = document.getElementById('calendar');
  if (!el) return;
  window._calNuevaReserva = new FullCalendar.Calendar(el, {
    locale        : 'es',
    initialView   : 'timeGridWeek',
    headerToolbar : { left:'prev,next today', center:'title', right:'timeGridWeek,timeGridDay' },
    allDaySlot    : false,
    slotMinTime   : '07:00:00',
    slotMaxTime   : '21:00:00',
    height        : 260,
    nowIndicator  : true,
    events: {
      url   : AJAX + '?action=getEventosCalendar&id_sala=' + id_sala,
      method: 'GET',
    },
    eventClick: function(info) {
      const p = info.event.extendedProps;
      Alerta.info(`${p.solicitante || ''}\n${p.motivo || ''}\nEstado: ${p.estado || ''}`);
    },
  });
  window._calNuevaReserva.render();
}

function resetCalendarioNuevaReserva() {
  try {
    if (window._calNuevaReserva) {
      window._calNuevaReserva.destroy();
      window._calNuevaReserva = null;
    }
  } catch (err) {
    console.warn('Advertencia al destruir calendario:', err);
  }
  
  try {
    $('#nr-calendario-aviso').show();
    $('#calendar').hide().empty(); // Use empty() en lugar de html('')
  } catch (err) {
    console.warn('Advertencia al resetear calendario DOM:', err);
  }
}

// ============================================================
// VER FOTO DE LA SALA
// ============================================================
function verFotoSala() {
  const foto   = window._salaFoto   || '';
  const nombre = window._salaNombre || 'Sala';
  const cap    = window._salaCap    || '';

  $('#modal-ver-sala-nombre').html('<i class="ti ti-door me-1"></i>' + escHtml(nombre));
  $('#modal-ver-sala-cap').text(cap ? 'Capacidad: ' + cap + ' personas' : '');

  if (foto) {
    $('#modal-ver-sala-body').html(
      `<img src="${SALAS_ASSETS_URL + escHtml(foto)}" alt="${escHtml(nombre)}"
            style="width:100%;max-height:320px;object-fit:cover;display:block;">`
    );
  } else {
    $('#modal-ver-sala-body').html(
      '<div class="text-center text-muted py-4"><i class="ti ti-photo-off fs-1 opacity-50"></i><p class="mt-2 mb-0">Esta sala no tiene fotografía.</p></div>'
    );
  }

  const modalEl = document.getElementById('modal-ver-sala');
  // Oscurecer el modal de solicitud al abrir
  modalEl.addEventListener('show.bs.modal', function() {
    $('#nr-foto-overlay').fadeIn(150);
  }, { once: true });
  // Restaurar al cerrar
  modalEl.addEventListener('hidden.bs.modal', function() {
    $('#nr-foto-overlay').fadeOut(150);
  }, { once: true });

  new bootstrap.Modal(modalEl).show();
}
