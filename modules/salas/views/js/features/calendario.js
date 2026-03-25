/**
 * features/calendario.js — Lógica del Calendario Principal (TODOS LOS ROLES)
 * Requiere: jQuery, FullCalendar, shared/api.js, shared/alerts.js, shared/utils.js
 * Variables PHP inyectadas: AJAX, ROL, ES_AUTORIZADOR_O_ADMIN, ES_ADMIN, USUARIO_IMPRESION, SALAS_ASSETS_URL
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

function navegarConDesvanecimiento(url) {
  const panel = document.getElementById('salas-cal-panel');
  if (panel) panel.classList.add('is-leaving');
  setTimeout(function() {
    window.location.href = url;
  }, 260);
}

function irDashboardConFade() {
  navegarConDesvanecimiento('?module=salas&action=dashboard');
}

window.irDashboardConFade = irDashboardConFade;

// Impresión del calendario
let _impresionCalendarioActiva = false;

function actualizarResumenImpresionCalendario() {
  const periodo = $('#salas-main-calendar .fc-toolbar-title').first().text().trim() || 'Calendario de Reservas';
  const sede = obtenerTextoSeleccionado('#cal-filter-sede', 'Todas las Sedes') || 'Todas las Sedes';
  const sala = obtenerTextoSeleccionado('#cal-filter-sala', 'Todas las Salas') || 'Todas las Salas';
  $('#print-periodo').text(periodo);
  $('#print-sede').text(sede);
  $('#print-sala').text(sala);
  $('#print-usuario').text(USUARIO_IMPRESION || 'Usuario no disponible');
  $('#print-fecha-hora').text(obtenerFechaHoraActual());
}

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

function actualizarEstadisticasLateral() {
  ajax('getEstadisticas', {}, 'GET').done(function(res) {
    if (!res || !res.ok || !res.data) return;

    const data = res.data;
    const pendientes = parseInt(data.pendientes) || 0;
    const aprobadas = parseInt(data.aprobadas) || 0;
    const rechazadas = parseInt(data.rechazadas) || 0;
    const canceladas = parseInt(data.canceladas) || 0;

    $('#stat-pendientes').text(pendientes);
    $('#stat-aprobadas').text(aprobadas);
    $('#stat-rechazadas').text(rechazadas);
    $('#stat-canceladas').text(canceladas);
    $('#badge-pendientes-side').text(pendientes);
  });
}

window.actualizarEstadisticasLateral = actualizarEstadisticasLateral;

// Altura del calendario
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

// Inicializar calendario principal
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
          console.log('Eventos del calendario:', data.data);
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
      
      // Formatear fecha con día de la semana (sin año)
      const fecha = new Date(info.event.start);
      const opciones = { weekday: 'long', month: 'long', day: 'numeric' };
      const fechaFormato = fecha.toLocaleDateString('es-ES', opciones);
      // Capitalizar primera letra
      const fechaCapitalizada = fechaFormato.charAt(0).toUpperCase() + fechaFormato.slice(1);
      
      // Determinar si mostrar botones de acción
      const esPendiente = p.estado === 'PENDIENTE';
      const puedeAutorizar = typeof ES_AUTORIZADOR_O_ADMIN !== 'undefined' && (ES_AUTORIZADOR_O_ADMIN === true || ES_AUTORIZADOR_O_ADMIN === 1);
      const mostrarBotones = esPendiente && puedeAutorizar;
      
      // Clases CSS según estado
      const estadoClass = p.estado.toLowerCase();
      const bgHeaderClass = `evento-header ${estadoClass}`;
      
      // HTML de botones
      const botones = mostrarBotones ? `
        <div class="evento-acciones">
          <button type="button" id="btn-rechazar-solicitud">✕ Rechazar</button>
          <button type="button" id="btn-aprobar-solicitud">✓ Aprobar</button>
        </div>
      ` : '';
      
      const equiposHTML = p.equipos_av ? `
        <div class="evento-equipos">
          <div class="evento-equipos-label">📹 Equipos AV</div>
          <ul class="evento-equipos-list">
            ${p.equipos_av.split(',').map(equipo => `<li>${escHtml(equipo.trim())}</li>`).join('')}
          </ul>
        </div>
      ` : '';
      
      const html = `
        <div class="swal-evento-content">
          <div class="${bgHeaderClass}">
            <div class="evento-label">📅 Reserva</div>
            <div class="evento-motivo">${escHtml(p.motivo || 'Sin motivo')}</div>
          </div>
          
          <div class="evento-grid">
            <div class="evento-box">
              <div class="evento-box-label">🏢 Sede</div>
              <div class="evento-box-value">${escHtml(p.sede || '-')}</div>
            </div>
            <div class="evento-box">
              <div class="evento-box-label">🚪 Sala</div>
              <div class="evento-box-value">${escHtml(p.sala || '-')}</div>
            </div>
          </div>
          
          <div class="evento-horario">
            <div class="evento-horario-label">⏰ Horario</div>
            <div class="evento-horario-value">${escHtml(fechaCapitalizada)}, ${escHtml(p.hora_inicio)} - ${escHtml(p.hora_fin)}</div>
          </div>
          
          ${equiposHTML}
          
          <div class="evento-estado-container">
            <span class="evento-estado-label">Estado:</span>
            <span class="evento-estado-badge ${estadoClass}">${escHtml(p.estado || 'APROBADA')}</span>
          </div>
          
          ${botones}
        </div>
      `;
      
      Swal.fire({
        html: html,
        showConfirmButton: false,
        showCloseButton  : false,
        allowOutsideClick: true,
        width: '420px',
        padding: '20px',
        customClass: {
          container: 'swal-evento-container',
          popup: 'swal-evento-popup'
        },
        didOpen: function(popup) {
          if (mostrarBotones) {
            setTimeout(function() {
              const btnAprobar = popup.querySelector('#btn-aprobar-solicitud');
              const btnRechazar = popup.querySelector('#btn-rechazar-solicitud');
              
              if (btnAprobar) {
                btnAprobar.addEventListener('click', function() {
                  aprobarSolicitud(p.id_reserva);
                });
              }
              
              if (btnRechazar) {
                btnRechazar.addEventListener('click', function() {
                  rechazarSolicitud(p.id_reserva);
                });
              }
            }, 100);
          }
        }
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

// Filtros de sede y sala
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

// Funciones para autorización de solicitudes
function aprobarSolicitud(id_reserva) {
  Swal.fire({
    title: '¿Aprobar solicitud?',
    text: 'La solicitud será aprobada y el solicitante será notificado.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, aprobar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#2fb344',
  }).then(result => {
    if (result.isConfirmed) {
      // Mostrar alerta de carga
      Swal.fire({
        title: 'Procesando...',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: (popup) => {
          Swal.showLoading();
        }
      });

      $.ajax({
        url: AJAX,
        type: 'POST',
        data: {
          action: 'aprobarReserva',
          id_reserva: id_reserva
        },
        success: function(res) {
          if (res.ok) {
            Swal.fire({
              title: 'Aprobado',
              text: 'La solicitud ha sido aprobada correctamente.',
              icon: 'success',
              confirmButtonText: 'OK'
            }).then(() => {
              Swal.close();
              if (window._calPrincipal) window._calPrincipal.refetchEvents();
              if (typeof window.actualizarEstadisticasLateral === 'function') {
                window.actualizarEstadisticasLateral();
              }
            });
          } else {
            Swal.fire('Error', res.msg || 'No se pudo aprobar la solicitud', 'error');
          }
        },
        error: function() {
          Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
        }
      });
    }
  });
}

function rechazarSolicitud(id_reserva) {
  Swal.fire({
    title: '¿Rechazar solicitud?',
    input: 'textarea',
    inputLabel: 'Observaciones (opcional)',
    inputPlaceholder: 'Ingresa el motivo del rechazo...',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, rechazar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
    inputValidator: (value) => {
      return false;
    }
  }).then(result => {
    if (result.isConfirmed) {
      // Mostrar alerta de carga
      Swal.fire({
        title: 'Procesando...',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: (popup) => {
          Swal.showLoading();
        }
      });

      $.ajax({
        url: AJAX,
        type: 'POST',
        data: {
          action: 'rechazarReserva',
          id_reserva: id_reserva,
          observacion: result.value || ''
        },
        success: function(res) {
          if (res.ok) {
            Swal.fire({
              title: 'Rechazado',
              text: 'La solicitud ha sido rechazada correctamente.',
              icon: 'success',
              confirmButtonText: 'OK'
            }).then(() => {
              Swal.close();
              if (window._calPrincipal) window._calPrincipal.refetchEvents();
              if (typeof window.actualizarEstadisticasLateral === 'function') {
                window.actualizarEstadisticasLateral();
              }
            });
          } else {
            Swal.fire('Error', res.msg || 'No se pudo rechazar la solicitud', 'error');
          }
        },
        error: function() {
          Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
        }
      });
    }
  });
}

// Inicialización al cargar la página
$(document).ready(function () {
  const panel = document.getElementById('salas-cal-panel');
  if (panel) {
    requestAnimationFrame(function() {
      panel.classList.add('is-ready');
    });
  }

  iniciarCalendarioPrincipal();
  cargarFiltrosSede();
  cargarSedes();
  if (typeof window.cargarSedesEnSelectores === 'function') {
    window.cargarSedesEnSelectores();
  }
  if (ES_ADMIN) {
    if (typeof window.cargarSedesEnSelectAdminSala === 'function') {
      window.cargarSedesEnSelectAdminSala();
    }
    if (typeof window.cargarSalasEnSelectAdminEquipo === 'function') {
      window.cargarSalasEnSelectAdminEquipo();
    }
  }
  ajustarAlturaCalendario();
  actualizarEstadisticasLateral();
  $(window).on('resize', ajustarAlturaCalendario);
  window.addEventListener('beforeprint', prepararCalendarioParaImpresion);
  window.addEventListener('afterprint', restaurarCalendarioDespuesDeImpresion);

  const params = new URLSearchParams(window.location.search);
  if (params.get('open') === 'nueva') {
    let intentos = 0;
    const maxIntentos = 12;

    const intentarAbrirModal = function() {
      if (typeof window.abrirNuevaSolicitud === 'function') {
        window.abrirNuevaSolicitud();
        return;
      }

      intentos += 1;
      if (intentos < maxIntentos) {
        setTimeout(intentarAbrirModal, 120);
      }
    };

    setTimeout(intentarAbrirModal, 220);
  }
});
