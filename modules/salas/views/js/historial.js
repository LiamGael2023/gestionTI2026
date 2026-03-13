/**
 * historial.js — Lógica JavaScript de la vista Historial de Reservas
 * Requiere: jQuery, DataTables, SweetAlert2
 * Variable PHP inyectada desde html/historial.php: AJAX_HIST
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

function reqHist(action, data, method) {
  return $.ajax({ url: AJAX_HIST + '?action=' + action, method: method || 'POST', data: data || {}, dataType: 'json', cache: false });
}

function escH(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function badgeEstadoH(estado) {
  var map = {
    'APROBADA' : '<span class="badge bg-success-lt text-success">Aprobada</span>',
    'RECHAZADA': '<span class="badge bg-danger-lt text-danger">Rechazada</span>',
    'CANCELADA': '<span class="badge bg-secondary-lt text-secondary">Cancelada</span>',
    'PENDIENTE': '<span class="badge bg-warning-lt text-warning">Pendiente</span>',
  };
  return map[estado] || '<span class="badge bg-secondary">' + escH(estado) + '</span>';
}

var _dtHistorial = null;

function cargarSedesHist() {
  reqHist('getSedes', {}, 'GET').done(function(res) {
    if (!res.ok || !res.data) return;
    var opt = '';
    $.each(res.data, function(i, s) {
      opt += '<option value="' + s.id + '">' + escH(s.nombre) + '</option>';
    });
    $('#hist-sede').append(opt);
  });
}

function cargarHistorial() {
  var desde = $('#hist-desde').val();
  var hasta = $('#hist-hasta').val();
  if (desde && hasta && desde > hasta) {
    Swal.fire({ icon: 'warning', title: 'Fechas inválidas', text: 'La fecha "Desde" no puede ser mayor que la fecha "Hasta".', confirmButtonColor: '#1a2940' });
    return;
  }

  var filtros = {
    fecha_desde: desde || '',
    fecha_hasta: hasta || '',
    id_sede    : $('#hist-sede').val()   || '',
    estado     : $('#hist-estado').val() || ''
  };

  $('#tbody-historial').html('<tr><td colspan="10" class="text-center py-4"><div class="spinner-border spinner-border-sm me-2 text-secondary"></div>Cargando...</td></tr>');
  if (_dtHistorial) { _dtHistorial.destroy(); _dtHistorial = null; }

  reqHist('getHistorial', filtros, 'GET').done(function(res) {
    if (!res.ok) {
      $('#tbody-historial').html('<tr><td colspan="10" class="text-center text-danger py-4">' + escH(res.msg || 'Error al cargar el historial.') + '</td></tr>');
      return;
    }
    var html = '';
    if (!res.data || !res.data.length) {
      html = '<tr><td colspan="10" class="text-center text-muted py-4">No se encontraron registros con los filtros aplicados.</td></tr>';
      $('#tbody-historial').html(html);
      return;
    }
    $.each(res.data, function(i, r) {
      html += '<tr>'
        + '<td>' + r.id_reserva + '</td>'
        + '<td>' + escH(r.solicitante_nombre) + '</td>'
        + '<td><strong>' + escH(r.sede_nombre) + '</strong><br><small class="text-muted">' + escH(r.sala_nombre) + '</small></td>'
        + '<td>' + escH(r.fecha) + '</td>'
        + '<td class="text-nowrap">' + escH(r.hora_inicio) + ' – ' + escH(r.hora_fin) + '</td>'
        + '<td><small class="text-truncate d-inline-block" style="max-width:140px;" title="' + escH(r.motivo) + '">' + escH(r.motivo) + '</small></td>'
        + '<td>' + badgeEstadoH(r.estado) + '</td>'
        + '<td><small>' + escH(r.autorizador_nombre || '—') + '</small></td>'
        + '<td><small class="text-muted">' + escH(r.fecha_aprobacion || '—') + '</small></td>'
        + '<td class="text-center"><button class="btn btn-sm btn-outline-primary" onclick="verDetalle(' + r.id_reserva + ')"><i class="ti ti-eye"></i></button></td>'
        + '</tr>';
    });
    $('#tbody-historial').html(html);
    _dtHistorial = $('#tabla-historial').DataTable({
      destroy   : true,
      autoWidth : false,
      language  : { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
      order     : [[3, 'desc']],
      columnDefs: [{ targets: 9, orderable: false }]
    });
  }).fail(function(xhr) {
    $('#tbody-historial').html('<tr><td colspan="10" class="text-center text-danger py-4">Error HTTP ' + xhr.status + ' al conectar con el servidor.</td></tr>');
  });
}

function verDetalle(id_reserva) {
  $('#detalle-hist-body').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
  new bootstrap.Modal(document.getElementById('modal-detalle-hist')).show();

  reqHist('getReservaDetalle', { id_reserva: id_reserva }, 'GET').done(function(res) {
    if (!res.ok) { $('#detalle-hist-body').html('<div class="alert alert-danger">' + escH(res.msg) + '</div>'); return; }
    var d = res.data;
    var equipos = '';
    if (d.equipos && d.equipos.length) {
      $.each(d.equipos, function(i, e) {
        equipos += '<span class="badge bg-blue-lt text-blue me-1">' + escH(e.nombre) + '</span>';
      });
    } else {
      equipos = '<span class="text-muted">Ninguno</span>';
    }
    var obs = d.observacion_rechazo
      ? '<div class="alert alert-danger py-2 mt-2"><small><strong>Observación:</strong> ' + escH(d.observacion_rechazo) + '</small></div>'
      : '';
    $('#detalle-hist-body').html(
      '<div class="row g-3">'
      + '<div class="col-sm-6"><div class="mb-2"><span class="text-secondary small">Solicitante</span><div class="fw-bold">' + escH(d.solicitante_nombre) + '</div></div></div>'
      + '<div class="col-sm-6"><div class="mb-2"><span class="text-secondary small">Estado</span><div>' + badgeEstadoH(d.estado) + '</div></div></div>'
      + '<div class="col-sm-6"><div class="mb-2"><span class="text-secondary small">Sede</span><div>' + escH(d.sede_nombre) + '</div></div></div>'
      + '<div class="col-sm-6"><div class="mb-2"><span class="text-secondary small">Sala</span><div>' + escH(d.sala_nombre) + '</div></div></div>'
      + '<div class="col-sm-4"><div class="mb-2"><span class="text-secondary small">Fecha</span><div class="fw-bold">' + escH(d.fecha) + '</div></div></div>'
      + '<div class="col-sm-4"><div class="mb-2"><span class="text-secondary small">Hora inicio</span><div>' + escH(d.hora_inicio) + '</div></div></div>'
      + '<div class="col-sm-4"><div class="mb-2"><span class="text-secondary small">Hora fin</span><div>' + escH(d.hora_fin) + '</div></div></div>'
      + '<div class="col-12"><div class="mb-2"><span class="text-secondary small">Motivo</span><div>' + escH(d.motivo) + '</div></div></div>'
      + '<div class="col-sm-6"><div class="mb-2"><span class="text-secondary small">Autorizador</span><div>' + escH(d.autorizador_nombre || '—') + '</div></div></div>'
      + '<div class="col-sm-6"><div class="mb-2"><span class="text-secondary small">F. Autorización</span><div class="text-muted small">' + escH(d.fecha_aprobacion || '—') + '</div></div></div>'
      + '<div class="col-12"><div class="mb-2"><span class="text-secondary small">Equipos AV</span><div class="mt-1">' + equipos + '</div></div></div>'
      + '</div>'
      + obs
    );
  }).fail(function() { $('#detalle-hist-body').html('<div class="alert alert-danger">Error de comunicación.</div>'); });
}

// ── Imprimir tabla de historial ───────────────────────────────
function imprimirHistorial() {
  if (!_dtHistorial) {
    alert('La tabla aún no ha cargado. Espere y vuelva a intentarlo.');
    return;
  }

  // Construir filas con los datos visibles (respetando filtros activos)
  var rows = '';
  var conteo = { Aprobada: 0, Rechazada: 0, Cancelada: 0, Pendiente: 0 };
  _dtHistorial.rows({ search: 'applied' }).nodes().each(function(tr) {
    var cols  = $(tr).find('td');
    var cells = '';
    for (var i = 0; i < cols.length - 1; i++) {   // excluir última col (botón Detalle)
      var $cell = $(cols[i]);
      var txt;
      if ($cell.find('.badge').length) {
        txt = $cell.find('.badge').text().trim();
        // Contar por estado (columna 6)
        if (i === 6 && conteo.hasOwnProperty(txt)) { conteo[txt]++; }
      } else if (i === 2 && $cell.find('strong').length) {
        // Columna Sede / Sala: separar sede y sala con " - "
        var sedeNombre = $cell.find('strong').text().trim();
        var salaNombre = $cell.find('small').text().trim();
        txt = sedeNombre + (salaNombre ? ' - ' + salaNombre : '');
      } else {
        txt = $cell.text().trim();
      }
      cells += '<td>' + $('<div>').text(txt).html() + '</td>';
    }
    rows += '<tr>' + cells + '</tr>';
  });

  if (!rows) {
    alert('No hay datos visibles en la tabla para imprimir.');
    return;
  }

  // Encabezado con filtros aplicados
  var desde  = $('#hist-desde').val()  || '—';
  var hasta  = $('#hist-hasta').val()  || '—';
  var sedeOpt = $('#hist-sede option:selected').text().trim();
  var sede   = (sedeOpt && sedeOpt !== '— Todas las sedes —') ? sedeOpt : 'Todas';
  var estado = $('#hist-estado').val() || 'Todos';
  var total  = _dtHistorial.rows({ search: 'applied' }).count();
  var fecha  = new Date().toLocaleDateString('es-PE', { year:'numeric', month:'long', day:'numeric' });

  var html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
    + '<title>Historial de Reservas \u2014 PECH GestionTI</title>'
    + '<style>'
    + 'body{font-family:Arial,sans-serif;font-size:11px;margin:20px;}'
    + 'h4{margin:0 0 4px;font-size:14px;}'
    + 'p{margin:0 0 12px;font-size:11px;color:#555;}'
    + 'table{width:100%;border-collapse:collapse;}'
    + 'th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}'
    + 'th{background:#1a2940;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
    + 'tr:nth-child(even) td{background:#f5f7fa;-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
    + '</style></head><body>'
    + '<h4>Historial de Reservas de Salas \u2014 PECH GestionTI</h4>'
    + '<p>Impreso el: ' + fecha
    + ' &nbsp;|&nbsp; Desde: ' + desde
    + ' &nbsp;|&nbsp; Hasta: ' + hasta
    + ' &nbsp;|&nbsp; Sede: ' + sede
    + ' &nbsp;|&nbsp; Estado: ' + estado
    + ' &nbsp;|&nbsp; Total: ' + total + ' registro(s)</p>'
    + '<table><thead><tr>'
    + '<th>#</th><th>Solicitante</th><th>Sede / Sala</th><th>Fecha</th>'
    + '<th>Horario</th><th>Motivo</th><th>Estado</th><th>Autorizador</th><th>F. Autorizaci\u00f3n</th>'
    + '</tr></thead><tbody>' + rows + '</tbody></table>'
    + '<br>'
    + '<table style="width:auto;min-width:340px;margin-top:8px;">'
    + '<thead><tr><th colspan="2" style="background:#1a2940;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;text-align:center;">Resumen de reservas</th></tr></thead>'
    + '<tbody>'
    + '<tr><td style="padding:4px 12px;">Aprobadas</td><td style="padding:4px 16px;font-weight:bold;color:#2c7a3f;">' + conteo.Aprobada + '</td></tr>'
    + '<tr style="background:#f5f7fa;-webkit-print-color-adjust:exact;print-color-adjust:exact;"><td style="padding:4px 12px;">Rechazadas</td><td style="padding:4px 16px;font-weight:bold;color:#a32020;">' + conteo.Rechazada + '</td></tr>'
    + '<tr><td style="padding:4px 12px;">Canceladas</td><td style="padding:4px 16px;font-weight:bold;color:#555;">' + conteo.Cancelada + '</td></tr>'
    + '<tr style="background:#f5f7fa;-webkit-print-color-adjust:exact;print-color-adjust:exact;border-top:2px solid #1a2940;"><td style="padding:4px 12px;font-weight:bold;">Total</td><td style="padding:4px 16px;font-weight:bold;">' + total + '</td></tr>'
    + '</tbody></table>'
    + '<script>window.onload=function(){window.print();window.close();}<\/script>'
    + '</body></html>';

  var win = window.open('', '_blank', 'width=950,height=700');
  if (!win) {
    alert('El navegador bloqueó la ventana emergente. Permita ventanas emergentes para este sitio e intente nuevamente.');
    return;
  }
  win.document.open();
  win.document.write(html);
  win.document.close();
}

// Carga automática al abrir la página
cargarSedesHist();
cargarHistorial();
