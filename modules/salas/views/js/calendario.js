/**
 * calendario.js — Lógica JavaScript del módulo Reserva de Salas (Vista Calendario)
 * Requiere: jQuery, FullCalendar, DataTables, SweetAlert2
 * Variables PHP inyectadas desde html/calendario.php:
 *   AJAX, ROL, ES_AUTORIZADOR_O_ADMIN, ES_ADMIN
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

// ============================================================
// HELPER — SweetAlert2 centralizado
// ============================================================
const Alerta = {
  exito   : (msg) => Swal.fire({ icon:'success', title:'Éxito',      text:msg, timer:2500, showConfirmButton:false }),
  error   : (msg) => Swal.fire({ icon:'error',   title:'Error',      text:msg }),
  info    : (msg) => Swal.fire({ icon:'info',    title:'Información',text:msg }),
  advertencia: (msg) => Swal.fire({ icon:'warning', title:'Atención', text:msg }),
  confirmar: (msg, cb) => Swal.fire({
    icon:'question', title:'¿Confirmar?', text:msg,
    showCancelButton:true, confirmButtonText:'Sí, confirmar',
    cancelButtonText:'Cancelar', confirmButtonColor:'#2fb344'
  }).then(r => { if (r.isConfirmed) cb(); }),
  confirmarPeligro: (msg, cb) => Swal.fire({
    icon:'warning', title:'¿Está seguro?', text:msg,
    showCancelButton:true, confirmButtonText:'Sí, continuar',
    cancelButtonText:'Cancelar', confirmButtonColor:'#d63939'
  }).then(r => { if (r.isConfirmed) cb(); }),
};

// ============================================================
// HELPER — AJAX genérico
// ============================================================
function ajax(action, data, method='POST') {
  return $.ajax({ url: AJAX + '?action=' + action, method, data, dataType: 'json' });
}

function obtenerTextoSeleccionado(selector, textoDefault) {
  const texto = $(selector).find('option:selected').text();
  return (texto || textoDefault || '').trim();
}

function actualizarResumenImpresionCalendario() {
  const periodo = $('#salas-main-calendar .fc-toolbar-title').first().text().trim() || 'Calendario de Reservas';
  const sede = obtenerTextoSeleccionado('#cal-filter-sede', 'Todas las Sedes') || 'Todas las Sedes';
  const sala = obtenerTextoSeleccionado('#cal-filter-sala', 'Todas las Salas') || 'Todas las Salas';
  const ahora = new Date();
  const fechaHora = ahora.toLocaleString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  $('#print-periodo').text(periodo);
  $('#print-sede').text(sede);
  $('#print-sala').text(sala);
  $('#print-usuario').text(USUARIO_IMPRESION || 'Usuario no disponible');
  $('#print-fecha-hora').text(fechaHora);
}

let _impresionCalendarioActiva = false;

function prepararCalendarioParaImpresion() {
  actualizarResumenImpresionCalendario();
  if (_impresionCalendarioActiva) return;
  _impresionCalendarioActiva = true;
  document.body.classList.add('printing-calendario');
  if (!window._calPrincipal) return;
  const calEl = document.getElementById('salas-main-calendar');
  if (calEl) calEl.style.height = 'auto';
  window._calPrincipal.setOption('height', 'auto');
  window._calPrincipal.updateSize();
  window._calPrincipal.render();
}

function restaurarCalendarioDespuesDeImpresion() {
  document.body.classList.remove('printing-calendario');
  if (!window._calPrincipal) {
    _impresionCalendarioActiva = false;
    return;
  }
  const calEl = document.getElementById('salas-main-calendar');
  if (calEl) calEl.style.height = '';
  ajustarAlturaCalendario();
  window._calPrincipal.updateSize();
  _impresionCalendarioActiva = false;
}

function imprimirCalendarioActual() {
  prepararCalendarioParaImpresion();
  setTimeout(function() {
    window.print();
  }, 180);
}

window.imprimirCalendarioActual = imprimirCalendarioActual;

// ============================================================
// HELPER — Badge de estado
// ============================================================
function badgeEstado(estado) {
  const map = { PENDIENTE:'warning', APROBADA:'success', RECHAZADA:'danger', CANCELADA:'secondary' };
  const color = map[estado] || 'secondary';
  return `<span class="badge bg-${color}-lt text-${color}">${estado}</span>`;
}

function claseEstadoTarjeta(estado) {
  const est = String(estado || '').toUpperCase();
  const map = {
    PENDIENTE: 'estado-pendiente',
    APROBADA : 'estado-aprobada',
    RECHAZADA: 'estado-rechazada',
    CANCELADA: 'estado-cancelada',
  };
  return map[est] || 'estado-cancelada';
}

// ============================================================
// HELPER — Offcanvas
// ============================================================
function abrirOffcanvas(id, callback) {
  const oc = new bootstrap.Offcanvas(document.getElementById(id));
  oc.show();
  if (callback) callback();
}

// ============================================================
// INICIALIZACIÓN
// ============================================================
$(document).ready(function () {
  iniciarCalendarioPrincipal();
  cargarFiltrosSede();
  cargarSedes();
  cargarSedesEnSelectores();
  if (ES_ADMIN) {
    cargarSedesEnSelectAdminSala();
    cargarSalasEnSelectAdminEquipo();
  }
  ajustarAlturaCalendario();
  $(window).on('resize', ajustarAlturaCalendario);
  window.addEventListener('beforeprint', prepararCalendarioParaImpresion);
  window.addEventListener('afterprint', restaurarCalendarioDespuesDeImpresion);
});

function ajustarAlturaCalendario() {
  const topbarH = document.getElementById('salas-topbar').offsetHeight;
  const bodyEl  = document.getElementById('salas-body');
  const rootEl  = document.getElementById('salas-root');
  const available = window.innerHeight - rootEl.getBoundingClientRect().top - topbarH;
  bodyEl.style.height = available + 'px';
  if (window._calPrincipal) {
    window._calPrincipal.setOption('height', available - 2);
  }
}

// ============================================================
// CALENDARIO PRINCIPAL
// ============================================================
function iniciarCalendarioPrincipal() {
  const el = document.getElementById('salas-main-calendar');
  if (!el) return;

  window._calFiltroSede = '';
  window._calFiltroSala = '';

  window._calPrincipal = new FullCalendar.Calendar(el, {
    locale        : 'es',
    initialView   : 'timeGridWeek',
    firstDay      : 1,
    weekends      : false,
    headerToolbar : {
      left   : 'prev,next today',
      center : 'title',
      right  : 'timeGridWeek,timeGridDay',
    },
    buttonText    : { today:'Hoy', week:'Semana', day:'Día' },
    datesSet      : function() {
      actualizarResumenImpresionCalendario();
    },
    allDaySlot    : false,
    slotMinTime   : '07:00:00',
    slotMaxTime   : '21:00:00',
    nowIndicator  : true,
    height        : window.innerHeight - 56 - 50,
    selectable    : true,
    selectMirror  : true,
    selectAllow   : function(selectInfo) {
      const hoy = new Date(); hoy.setHours(0,0,0,0);
      return selectInfo.start >= hoy;
    },
    select: function(info) {
      const fecha      = info.startStr.substring(0, 10);
      const horaInicio = info.startStr.substring(11, 16);
      const horaFin    = info.endStr.substring(11, 16);

      abrirNuevaSolicitud();

      setTimeout(function() {
        $('#nr-fecha').val(fecha);
        $('#nr-hora-inicio').val(horaInicio);
        $('#nr-hora-fin').val(horaFin);

        if (window._calFiltroSede) {
          $('#nr-sede').val(window._calFiltroSede).trigger('change');
          if (window._calFiltroSala) {
            setTimeout(function() {
              $('#nr-sala').val(window._calFiltroSala).trigger('change');
            }, 450);
          }
        }
      }, 320);

      window._calPrincipal.unselect();
    },
    events: function(info, success, failure) {
      $.get(AJAX, {
        action   : 'getEventosCronograma',
        start    : info.startStr ? info.startStr.substring(0, 10) : '',
        end      : info.endStr   ? info.endStr.substring(0, 10)   : '',
        id_sede  : window._calFiltroSede || '',
        id_sala  : window._calFiltroSala || '',
      }).done(function(data) {
        if (data && data.ok && data.data) {
          const eventos = data.data.map(ev => ({
            ...ev,
            classNames: ['ev-' + (ev.estado || 'APROBADA')],
          }));
          success(eventos);
        } else {
          success([]);
        }
      }).fail(failure);
    },
    eventClick: function(info) {
      const p = info.event.extendedProps;
      const estado = (p.estado || 'APROBADA').toUpperCase();
      const sede = escHtml(p.sede || 'No definida');
      const sala = escHtml(p.sala || 'No definida');
      const motivo = escHtml(p.motivo || 'Sin detalle');

      const inicio = info.event.start
        ? info.event.start.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })
        : '';
      const fin = info.event.end
        ? info.event.end.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })
        : '';
      const horario = (inicio && fin) ? `${inicio} - ${fin}` : (inicio || 'No definido');

      Swal.fire({
        html: `<div class="cal-event-card">
          <div class="cal-event-card__header">
            <h3 class="cal-event-card__title">${escHtml(info.event.title || 'Reserva de sala')}</h3>
            <span class="cal-event-card__status ${claseEstadoTarjeta(estado)}">${escHtml(estado)}</span>
          </div>

          <div class="cal-event-card__meta">
            <div class="cal-event-card__row">
              <span class="cal-event-card__label">Sede</span>
              <span class="cal-event-card__value">${sede}</span>
            </div>
            <div class="cal-event-card__row">
              <span class="cal-event-card__label">Sala</span>
              <span class="cal-event-card__value">${sala}</span>
            </div>
            <div class="cal-event-card__row">
              <span class="cal-event-card__label">Horario</span>
              <span class="cal-event-card__value">${escHtml(horario)}</span>
            </div>
          </div>

          <div class="cal-event-card__motivo">
            <span class="cal-event-card__label">Motivo</span>
            <p>${motivo}</p>
          </div>
        </div>`,
        customClass: {
          popup: 'cal-event-popup',
          closeButton: 'cal-event-close',
        },
        width: 560,
        padding: 0,
        showConfirmButton: false,
        showCloseButton  : false,
        allowOutsideClick: true,
        allowEscapeKey   : true,
      });
    },
    eventDidMount: function(info) {
      const estado = info.event.extendedProps.estado;
      if (estado) $(info.el).addClass('ev-' + estado);
    },
  });
  window._calPrincipal.render();
  actualizarResumenImpresionCalendario();
}

// ============================================================
// FILTROS DE CABECERA — Sede / Sala
// ============================================================
function cargarFiltrosSede() {
  ajax('getSedes', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    let opts = '<option value="">Todas las Sedes</option>';
    res.data.forEach(s => {
      opts += `<option value="${s.id}">${escHtml(s.nombre)}</option>`;
    });
    $('#cal-filter-sede').html(opts);
    actualizarResumenImpresionCalendario();
  });
}

$('#cal-filter-sede').on('change', function() {
  window._calFiltroSede = $(this).val();
  window._calFiltroSala = '';
  $('#cal-filter-sala').html('<option value="">Todas las Salas</option>');
  actualizarResumenImpresionCalendario();

  if (window._calFiltroSede) {
    ajax('getSalasBySede', { id_sede: window._calFiltroSede }, 'GET').done(function(res) {
      if (!res.ok) return;
      let opts = '<option value="">Todas las Salas</option>';
      res.data.forEach(s => opts += `<option value="${s.id_sala}">${escHtml(s.nombre)}</option>`);
      $('#cal-filter-sala').html(opts);
      actualizarResumenImpresionCalendario();
      if (res.data.length === 1) $('#cal-filter-sala').val(res.data[0].id_sala).trigger('change');
    });
  }

  if (window._calPrincipal) window._calPrincipal.refetchEvents();
});

$('#cal-filter-sala').on('change', function() {
  window._calFiltroSala = $(this).val();
  actualizarResumenImpresionCalendario();
  if (window._calPrincipal) window._calPrincipal.refetchEvents();
});

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

function cargarSedesEnSelectores() {
  ajax('getSedes', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    let opts = '<option value="">— Seleccione sede —</option>';
    res.data.forEach(s => opts += `<option value="${s.id}">${escHtml(s.nombre)}</option>`);
    $('#sala-sede').html(opts);
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

function cargarSalasEnSelectAdminEquipo() {
  ajax('getAllSalas', {}, 'GET').done(function(res) {
    if (!res.ok) return;
    let opts = '<option value="">— Seleccione sala —</option>';
    res.data.forEach(s => opts += `<option value="${s.id_sala}">[${escHtml(s.sede_nombre)}] ${escHtml(s.nombre)}</option>`);
    $('#equipo-sala').html(opts);
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
// NUEVA RESERVA \u2014 onChange de Sala
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
      await Swal.fire({ icon: 'success', title: 'Éxito', text: res.msg, timer: 2500, showConfirmButton: false });
      $('#form-nueva-reserva')[0].reset();
      $('#nr-sala').prop('disabled', true).html('<option value="">— Seleccione primero la sede —</option>');
      $('#nr-sala-capacidad').text('');
      $('#nr-equipos-section').hide();
      $('#nr-disponibilidad-result').addClass('d-none');
      $('#btn-guardar-reserva').prop('disabled', true);
      $('#btn-verificar').prop('disabled', true);
      resetCalendarioNuevaReserva();
      if (window._calPrincipal) window._calPrincipal.refetchEvents();
      actualizarEstadisticas();
      actualizarBadgePendientes();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.msg || 'Error al registrar la solicitud.' });
    }
  } catch {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
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
  if (window._calNuevaReserva) {
    window._calNuevaReserva.removeAllEvents();
    window._calNuevaReserva = null;
  }
  $('#nr-calendario-aviso').show();
  $('#calendar').hide().html('');
  $('#calendar').replaceWith('<div id="calendar" class="mb-3" style="display:none; max-height:260px; overflow:hidden;"></div>');
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

// ============================================================
// ESTADÍSTICAS — Actualizar panel lateral
// ============================================================
function actualizarEstadisticas() {
  ajax('getEstadisticas', {}, 'GET').done(function(res) {
    if (!res.ok || !res.data) return;
    const d = res.data;
    $('#stat-pendientes').text(d.pendientes || 0);
    $('#stat-aprobadas').text(d.aprobadas  || 0);
    $('#stat-rechazadas').text(d.rechazadas || 0);
    $('#stat-canceladas').text(d.canceladas || 0);
  });
}

// ============================================================
// VER DETALLE DE RESERVA
// ============================================================
function verDetalleReserva(id_reserva) {
  $('#modal-detalle-id').text('#' + id_reserva);
  $('#modal-detalle-body').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
  $('#modal-detalle-footer').html('');
  new bootstrap.Modal(document.getElementById('modal-detalle-reserva')).show();

  ajax('getReservaDetalle', { id_reserva }, 'GET').done(function(res) {
    if (!res.ok) { $('#modal-detalle-body').html('<div class="alert alert-danger">Reserva no encontrada.</div>'); return; }
    const d = res.data;
    const equiposHtml = d.equipos.length
      ? d.equipos.map(e => `<span class="badge bg-blue-lt me-1">${escHtml(e.tipo)}: ${escHtml(e.nombre)}</span>`).join('')
      : '<span class="text-muted small">Ninguno</span>';
    $('#modal-detalle-body').html(`
      <div class="row g-3">
        <div class="col-sm-6"><strong>Sede:</strong><br>${escHtml(d.sede_nombre)}</div>
        <div class="col-sm-6"><strong>Sala:</strong><br>${escHtml(d.sala_nombre)} (cap: ${d.capacidad})</div>
        <div class="col-sm-4"><strong>Fecha:</strong><br>${d.fecha}</div>
        <div class="col-sm-4"><strong>Hora Inicio:</strong><br>${d.hora_inicio}</div>
        <div class="col-sm-4"><strong>Hora Fin:</strong><br>${d.hora_fin}</div>
        <div class="col-12"><strong>Motivo:</strong><br>${escHtml(d.motivo)}</div>
        <div class="col-12"><strong>Equipos AV:</strong><br>${equiposHtml}</div>
        <div class="col-sm-6"><strong>Solicitante:</strong><br>${escHtml(d.solicitante_nombre)}</div>
        <div class="col-sm-6"><strong>Estado:</strong><br>${badgeEstado(d.estado)}</div>
        ${d.autorizador_nombre !== '—' ? `<div class="col-sm-6"><strong>Autorizador:</strong><br>${escHtml(d.autorizador_nombre)}</div>` : ''}
        ${d.fecha_aprobacion ? `<div class="col-sm-6"><strong>F. Autorización:</strong><br>${escHtml(d.fecha_aprobacion)}</div>` : ''}
        ${d.observacion_rechazo ? `<div class="col-12"><strong>Observación:</strong><br><span class="text-danger">${escHtml(d.observacion_rechazo)}</span></div>` : ''}
        <div class="col-12"><strong>Registrado:</strong><br><small class="text-muted">${d.created_at}</small></div>
      </div>
      <hr class="my-3">
      <h6><i class="ti ti-history me-1"></i>Historial de Estado</h6>
      <div id="historial-reserva-body"><div class="text-center"><div class="spinner-border spinner-border-sm text-secondary"></div></div></div>
    `);

    ajax('getHistorialReserva', { id_reserva }, 'GET').done(function(hr) {
      if (!hr.ok || !hr.data.length) {
        $('#historial-reserva-body').html('<small class="text-muted">Sin cambios de estado registrados.</small>'); return;
      }
      let hh = '<ul class="timeline">';
      hr.data.forEach(h => {
        hh += `<li class="timeline-event">
          <div class="timeline-event-icon bg-blue-lt"><i class="ti ti-git-commit text-blue"></i></div>
          <div class="card timeline-event-card">
            <div class="card-body py-2 px-3">
              <div class="small text-muted">${h.fecha_accion} — <strong>${escHtml(h.usuario_accion)}</strong></div>
              <div>${badgeEstado(h.estado_anterior)} → ${badgeEstado(h.estado_nuevo)}</div>
              ${h.observacion ? `<div class="small text-danger mt-1">${escHtml(h.observacion)}</div>` : ''}
            </div>
          </div>
        </li>`;
      });
      hh += '</ul>';
      $('#historial-reserva-body').html(hh);
    });
  }).fail(() => $('#modal-detalle-body').html('<div class="alert alert-danger">Error al cargar el detalle.</div>'));
}

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

// ============================================================
// HISTORIAL (solo si ES_AUTORIZADOR_O_ADMIN)
// ============================================================
let _tablaHistorial = null;

function cargarHistorial() {
  if (!ES_AUTORIZADOR_O_ADMIN) return;
  const filtros = {
    fecha_desde: $('#hist-desde').val() || '',
    fecha_hasta: $('#hist-hasta').val() || '',
    estado     : $('#hist-estado').val() || '',
  };
  ajax('getHistorial', filtros, 'GET').done(function(res) {
    if (!res.ok) { Alerta.error('Error al cargar el historial.'); return; }
    if (_tablaHistorial) { _tablaHistorial.destroy(); _tablaHistorial = null; }
    let html = '';
    if (!res.data.length) {
      html = `<tr><td colspan="9" class="text-center text-muted py-4">No se encontraron registros.</td></tr>`;
    } else {
      res.data.forEach(r => {
        html += `<tr>
          <td>${r.id_reserva}</td>
          <td>${escHtml(r.solicitante_nombre)}</td>
          <td><strong>${escHtml(r.sede_nombre)}</strong><br><small>${escHtml(r.sala_nombre)}</small></td>
          <td>${r.fecha}</td>
          <td>${r.hora_inicio} – ${r.hora_fin}</td>
          <td>${badgeEstado(r.estado)}</td>
          <td><small>${escHtml(r.autorizador_nombre)}</small></td>
          <td><small class="text-muted">${r.fecha_aprobacion || '—'}</small></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="verDetalleReserva(${r.id_reserva})"><i class="ti ti-eye"></i></button>
          </td>
        </tr>`;
      });
    }
    $('#tbody-historial').html(html);
    _tablaHistorial = $('#tabla-historial').DataTable({
      destroy   : true,
      language  : { url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
      order     : [[3,'desc']],
      columnDefs: [{ targets:8, orderable:false }],
    });
  }).fail(() => Alerta.error('Error de conexión al cargar el historial.'));
}

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

// ============================================================
// UTILIDADES
// ============================================================
function escHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
