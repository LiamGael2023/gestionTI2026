/**
 * dashboard.js - Lógica de Dashboard
 * Carga indicadores y dibuja gráficas con Chart.js
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

'use strict';

let chartEstados = null;
let chartTendencias = null;
let chartEquipos = null;
let chartMiniAprobacion = null;
const DASHBOARD_TIMEOUT_MS = 15000;

function verCalendarioDesdeDashboard(openModal) {
  const panel = document.getElementById('dashboard-main-panel');
  if (panel) panel.classList.add('is-leaving');

  let url = '?module=salas&action=calendario';
  if (openModal === 'nueva') {
    url += '&open=nueva';
  }

  setTimeout(function() {
    window.location.href = url;
  }, 260);
}

window.verCalendarioDesdeDashboard = verCalendarioDesdeDashboard;

$(document).ready(function() {
  const panel = document.getElementById('dashboard-main-panel');
  if (panel) {
    requestAnimationFrame(function() {
      panel.classList.add('is-ready');
    });
  }

  cargarDashboard();
});

/**
 * Cargar todos los datos del dashboard
 */
function cargarDashboard(reintento = false) {
  const $btn = $('button[onclick="cargarDashboard()"]');
  $btn.prop('disabled', true);

  ajax('getDashboardData', { tipo: 'all' }, 'GET', { timeout: DASHBOARD_TIMEOUT_MS })
    .done(function(res) {
      if (!res.ok) {
        Alerta.error('Error al cargar dashboard: ' + res.msg);
        return;
      }
      
      // Procesar cada indicador con validación
      if (res.data && res.data.utilizacion) procesarUtilizacion(res.data.utilizacion);
      if (res.data && res.data.estado) procesarEstado(res.data.estado);
      if (res.data && res.data.estado) procesarTiempoAprobacion(res.data.estado);
      if (res.data && res.data.equipos) procesarEquipos(res.data.equipos);
      if (res.data && res.data.tendencias) procesarTendencias(res.data.tendencias);
      if (res.data && res.data.tendencias) procesarMiniTendenciaAprobacion(res.data.tendencias);
      if (res.data && res.data.gerencia) procesarGerencia(res.data.gerencia);
      if (res.data && res.data.gerencia) procesarCoberturaOrganizacional(res.data.gerencia);

      actualizarMarcaTiempo();
    })
    .fail(function(xhr, status) {
      if (!reintento && status === 'timeout') {
        cargarDashboard(true);
        return;
      }
      Alerta.error('Error de conexión al cargar dashboard: ' + status);
    })
    .always(function() {
      $btn.prop('disabled', false);
    });
}

function actualizarMarcaTiempo() {
  const $el = $('#dashboard-last-update');
  if (!$el.length) return;

  const ahora = new Date();
  const ts = ahora.toLocaleString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });

  $el.text('Última actualización: ' + ts);
}

/**
 * INDICADOR 1: Procesamiento de Utilización de Salas
 */
function procesarUtilizacion(data) {
  if (!data) return;
  
  const general = data.ocupacion_general || {};
  const topSalas = data.top_salas || [];
  const menosSalas = data.menos_salas || [];

  const salasUtilizadas = parseInt(general.salas_utilizadas) || 0;
  const totalSalasActivas = parseInt(general.total_salas_activas) || 0;
  const textoSalasUtilizadas = totalSalasActivas > 0
    ? (salasUtilizadas + '/' + totalSalasActivas)
    : String(salasUtilizadas);

  // Llenar estadísticas generales
  $('#ocupacion-salas-utilizadas').text(textoSalasUtilizadas);
  $('#ocupacion-total-reservas').text(general.total_reservas || '0');
  $('#ocupacion-horas-totales').text((general.horas_totales || '0') + 'h');
  $('#ocupacion-promedio-horas').text((general.promedio_horas_por_reserva || '0') + 'h');

  // Top 3 salas más utilizadas
  let htmlTop = '<ul class="list-unstyled">';
  if (topSalas && topSalas.length > 0) {
    topSalas.forEach(sala => {
      const porcentaje = parseFloat(sala.porcentaje) || 0;
      const horas = parseFloat(sala.horas_utilizadas) || 0;
      const reservas = parseInt(sala.total_reservas) || 0;
      
      htmlTop += `
        <li class="mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong>${escHtml(sala.sala_nombre)}</strong>
              <br><small class="text-muted">${escHtml(sala.sede_nombre)}</small>
            </div>
            <span class="badge bg-success">${reservas} reservas</span>
          </div>
          <div class="progress mt-1" style="height: 6px;">
            <div class="progress-bar bg-success" style="width: ${porcentaje}%"></div>
          </div>
          <small class="text-muted">${horas.toFixed(1)}h | ${porcentaje}%</small>
        </li>
      `;
    });
  } else {
    htmlTop += '<li class="text-muted text-center py-3">Sin datos</li>';
  }
  htmlTop += '</ul>';
  $('#container-top-salas').html(htmlTop);

  // Top 3 salas menos utilizadas
  let htmlMenos = '<ul class="list-unstyled">';
  if (menosSalas && menosSalas.length > 0) {
    menosSalas.forEach(sala => {
      const reservas = parseInt(sala.total_reservas) || 0;
      const horas = parseFloat(sala.horas_utilizadas) || 0;
      
      htmlMenos += `
        <li class="mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong>${escHtml(sala.sala_nombre)}</strong>
              <br><small class="text-muted">${escHtml(sala.sede_nombre)}</small>
            </div>
            <span class="badge bg-secondary">${reservas} reservas</span>
          </div>
          <small class="text-muted">${horas.toFixed(1)}h disponible</small>
        </li>
      `;
    });
  } else {
    htmlMenos += '<li class="text-muted text-center py-3">Sin datos</li>';
  }
  htmlMenos += '</ul>';
  $('#container-menos-salas').html(htmlMenos);
}

/**
 * INDICADOR 2: Procesamiento de Estado de Solicitudes
 */
function procesarEstado(data) {
  const estados = data.estados || [];

  if (!estados || estados.length === 0) {
    if (chartEstados !== null) {
      chartEstados.destroy();
      chartEstados = null;
    }
    return;
  }

  // Gráfica de estados (pie/doughnut)
  const labels = estados.map(e => e.estado || 'Desconocido');
  const valores = estados.map(e => parseInt(e.total) || 0);
  const colores = {
    'PENDIENTE': '#ffc107',
    'APROBADA': '#28a745',
    'RECHAZADA': '#dc3545',
    'CANCELADA': '#6c757d'
  };
  const colors = labels.map(l => colores[l] || '#17a2b8');

  if (chartEstados !== null) chartEstados.destroy();

  const ctxEstados = document.getElementById('chart-estados');
  if (!ctxEstados) {
    return;
  }

  chartEstados = new Chart(ctxEstados.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: valores,
        backgroundColor: colors,
        borderColor: '#fff',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
}

/**
 * INDICADOR 3.5: Procesamiento de Tiempo de Aprobación por Gerencia (Barras Horizontales)
 */
/**
 * INDICADOR 3 (Actualizado): Procesamiento de Tiempo Promedio de Aprobación (Simple)
 * Recibe datos de estado que incluye tiempo_aprobacion general
 */
function procesarTiempoAprobacion(data) {
  if (!data || !data.tiempo_aprobacion) {
    $('#promedio-aprobacion-horas').text('0');
    $('#promedio-aprobacion-total').text('0');
    return;
  }

  const tiempoData = data.tiempo_aprobacion;
  const promedio = parseFloat(tiempoData.promedio_horas) || 0;
  const total = parseInt(tiempoData.total_aprobadas) || 0;

  // Mostrar números simples
  $('#promedio-aprobacion-horas').text(promedio.toFixed(1) + ' h');
  $('#promedio-aprobacion-total').text(total);
}

function procesarMiniTendenciaAprobacion(data) {
  const tendencias = (data && data.tendencias) ? data.tendencias : [];
  const canvas = document.getElementById('chart-mini-aprobacion');
  const empty = document.getElementById('mini-aprobacion-empty');

  if (!canvas) return;

  if (!tendencias.length) {
    if (chartMiniAprobacion !== null) {
      chartMiniAprobacion.destroy();
      chartMiniAprobacion = null;
    }
    if (empty) empty.classList.remove('d-none');
    return;
  }

  if (empty) empty.classList.add('d-none');

  const labels = tendencias.map(function(t) {
    if (!t.fecha) return 'N/A';
    const fecha = new Date(t.fecha);
    return fecha.toLocaleDateString('es-PE', { month: '2-digit', day: '2-digit' });
  });

  const valores = tendencias.map(function(t) {
    return parseInt(t.total_reservas) || 0;
  });

  if (chartMiniAprobacion !== null) chartMiniAprobacion.destroy();

  chartMiniAprobacion = new Chart(canvas.getContext('2d'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        data: valores,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.12)',
        borderWidth: 2,
        fill: true,
        tension: 0.35,
        pointRadius: 2,
        pointHoverRadius: 4,
        pointBackgroundColor: '#3b82f6'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              return 'Solicitudes: ' + (ctx.parsed.y || 0);
            }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 6 }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(148, 163, 184, .15)' },
          ticks: { precision: 0, maxTicksLimit: 4 }
        }
      }
    }
  });
}

function procesarCoberturaOrganizacional(data) {
  const lista = Array.isArray(data) ? data : [];

  const gerenciasMap = {};
  const unidadesMap = {};

  lista.forEach(function(usuario) {
    const gerencia = (usuario.gerencia_laboral || 'Sin gerencia').trim();
    const unidad = (usuario.unidad_laboral || 'Sin unidad').trim();
    const totalSolicitudes = parseInt(usuario.total_solicitudes) || 0;
    const horas = parseFloat(usuario.horas_utilizadas) || 0;

    if (!gerenciasMap[gerencia]) {
      gerenciasMap[gerencia] = { nombre: gerencia, solicitudes: 0, horas: 0 };
    }
    gerenciasMap[gerencia].solicitudes += totalSolicitudes;
    gerenciasMap[gerencia].horas += horas;

    const unidadKey = gerencia + '||' + unidad;
    if (!unidadesMap[unidadKey]) {
      unidadesMap[unidadKey] = { nombre: unidad, gerencia: gerencia, solicitudes: 0, horas: 0 };
    }
    unidadesMap[unidadKey].solicitudes += totalSolicitudes;
    unidadesMap[unidadKey].horas += horas;
  });

  const gerencias = Object.values(gerenciasMap)
    .sort(function(a, b) { return b.solicitudes - a.solicitudes; })
    .slice(0, 5);

  const unidades = Object.values(unidadesMap)
    .sort(function(a, b) { return b.horas - a.horas; })
    .slice(0, 5);

  $('#org-total-usuarios').text(lista.length);
  $('#org-total-gerencias').text(Object.keys(gerenciasMap).length);
  $('#org-total-unidades').text(Object.keys(unidadesMap).length);

  if (!gerencias.length) {
    $('#org-top-gerencias').html('<div class="text-muted small">Sin datos de gerencias.</div>');
  } else {
    let htmlGer = '';
    gerencias.forEach(function(g) {
      htmlGer += `
        <div class="org-item">
          <div class="org-item-name" title="${escHtml(g.nombre)}">${escHtml(g.nombre)}</div>
          <div class="org-item-metrics">
            <span class="badge bg-primary">${g.solicitudes} sol.</span>
            <span class="ms-1">${g.horas.toFixed(1)}h</span>
          </div>
        </div>
      `;
    });
    $('#org-top-gerencias').html(htmlGer);
  }

  if (!unidades.length) {
    $('#org-top-unidades').html('<div class="text-muted small">Sin datos de unidades.</div>');
  } else {
    let htmlUni = '';
    unidades.forEach(function(u) {
      htmlUni += `
        <div class="org-item">
          <div class="org-item-name" title="${escHtml(u.nombre)}">${escHtml(u.nombre)}</div>
          <div class="org-item-metrics">
            <span class="badge bg-primary text-white">${u.solicitudes} sol.</span>
            <span class="ms-1">${u.horas.toFixed(1)}h</span>
          </div>
        </div>
      `;
    });
    $('#org-top-unidades').html(htmlUni);
  }
}

/**
 * INDICADOR 4: Procesamiento de Top 3 Equipos por Sala (Gráfico Agrupado)
 */
function procesarEquipos(data) {
  const canvas = document.getElementById('chart-equipos');
  if (!canvas) return;

  if (!data || data.length === 0) {
    if (chartEquipos !== null) chartEquipos.destroy();
    chartEquipos = null;
    return;
  }

  // Preparar datos para gráfico agrupado por sala
  const salaLabels = data.map(s => escHtml(s.sala_nombre));

  // Crear datasets Top 1, Top 2 y Top 3 por cada sala
  const colores = ['#28a745', '#ffc107', '#0066cc'];
  const labels = ['Top 1', 'Top 2', 'Top 3'];
  const datasets = labels.map((label, idx) => {
    const valores = [];
    const nombresEquipos = [];

    data.forEach(sala => {
      const equipo = (sala.equipos || [])[idx] || null;
      valores.push(equipo ? (parseInt(equipo.uso_count) || 0) : 0);
      nombresEquipos.push(equipo && equipo.nombre ? equipo.nombre : 'Sin equipo');
    });

    const coloresArray = valores.map(valor => valor === 0 ? '#e9ecef' : colores[idx]);

    return {
      label: label,
      data: valores,
      _nombresEquipos: nombresEquipos,
      backgroundColor: coloresArray,
      borderColor: '#fff',
      borderWidth: 1,
      borderRadius: 4
    };
  });

  if (chartEquipos !== null) chartEquipos.destroy();

  const ctxEquipos = canvas.getContext('2d');
  chartEquipos = new Chart(ctxEquipos, {
    type: 'bar',
    data: {
      labels: salaLabels,
      datasets: datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: {
            usePointStyle: true,
            pointStyle: 'rectRounded',
            boxWidth: 14,
            color: '#475569',
            padding: 14,
            font: {
              size: 12,
              weight: 600,
            }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(15, 23, 42, 0.92)',
          padding: 10,
          titleFont: { size: 12, weight: 700 },
          bodyFont: { size: 12 },
          callbacks: {
            title: (ctx) => ctx[0].label,
            label: (ctx) => {
              const nombres = ctx.dataset._nombresEquipos || [];
              const equipoNombre = nombres[ctx.dataIndex] || 'Sin equipo';
              const usos = ctx.parsed.y;
              return ctx.dataset.label + ' - ' + equipoNombre + ': ' + (usos > 0 ? usos + ' usos' : 'sin datos');
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Numero de Usos' },
          grid: {
            color: 'rgba(148, 163, 184, 0.18)'
          },
          ticks: {
            precision: 0
          }
        },
        x: {
          title: { display: true, text: 'Salas' },
          grid: {
            display: false
          },
          ticks: {
            maxRotation: 0,
            callback: function(value) {
              const label = this.getLabelForValue(value);
              return label.length > 24 ? (label.substring(0, 24) + '...') : label;
            }
          }
        }
      }
    }
  });
}

/**
 * INDICADOR 5: Procesamiento de Tendencias Temporales
 */
function procesarTendencias(data) {
  const tendencias = data.tendencias || [];
  const diaMasUtilizado = data.dia_mas_utilizado || {};
  const diaMenosUtilizado = data.dia_menos_utilizado || {};

  // Gráfica de línea: tendencias
  if (tendencias && tendencias.length > 0) {
    const fechas = tendencias.map(t => {
      // Formatear fecha para mostrar solo la fecha sin hora
      if (t.fecha) {
        const fecha = new Date(t.fecha);
        return fecha.toLocaleDateString('es-PE', { year: 'numeric', month: '2-digit', day: '2-digit' });
      }
      return 'N/A';
    });
    const reservas = tendencias.map(t => parseInt(t.total_reservas) || 0);

    if (chartTendencias !== null) chartTendencias.destroy();

    const canvasTendencias = document.getElementById('chart-tendencias');
    if (canvasTendencias) {
      const ctxTendencias = canvasTendencias.getContext('2d');
      chartTendencias = new Chart(ctxTendencias, {
        type: 'line',
        data: {
          labels: fechas,
          datasets: [{
            label: 'Reservas',
            data: reservas,
            borderColor: '#0066cc',
            backgroundColor: 'rgba(0, 102, 204, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#0066cc'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    }
  }

  // Día más utilizado
  if (diaMasUtilizado && diaMasUtilizado.fecha) {
    const reservasMax = parseInt(diaMasUtilizado.total_reservas) || 0;
    const fechaFormato = new Date(diaMasUtilizado.fecha).toLocaleDateString('es-PE', { year: 'numeric', month: '2-digit', day: '2-digit' });
    $('#dia-mas-utilizado').html(`
      <strong>${diaMasUtilizado.dia_semana || 'N/A'}</strong>
      <br><small>${fechaFormato}</small>
      <br><span class="badge bg-success mt-1">${reservasMax} reservas</span>
    `);
  }

  if (!diaMasUtilizado || !diaMasUtilizado.fecha) {
    $('#dia-mas-utilizado').html('<small class="text-muted">Sin datos</small>');
  }

  // Día menos utilizado
  if (diaMenosUtilizado && diaMenosUtilizado.fecha) {
    const reservasMin = parseInt(diaMenosUtilizado.total_reservas) || 0;
    const fechaFormato = new Date(diaMenosUtilizado.fecha).toLocaleDateString('es-PE', { year: 'numeric', month: '2-digit', day: '2-digit' });
    $('#dia-menos-utilizado').html(`
      <strong>${diaMenosUtilizado.dia_semana || 'N/A'}</strong>
      <br><small>${fechaFormato}</small>
      <br><span class="badge bg-danger mt-1">${reservasMin} reservas</span>
    `);
  }

  if (!diaMenosUtilizado || !diaMenosUtilizado.fecha) {
    $('#dia-menos-utilizado').html('<small class="text-muted">Sin datos</small>');
  }
}

/**
 * INDICADOR EXTRA: Procesamiento de Análisis por Gerencia/Unidad
 */
function procesarGerencia(data) {
  let html = '';

  if (!data || data.length === 0) {
    html = '<tr><td colspan="8" class="text-center text-muted">Sin datos de gerencias</td></tr>';
  } else {
    data.forEach(usuario => {
      const aprobadas = parseInt(usuario.solicitudes_aprobadas) || 0;
      const pendientes = parseInt(usuario.solicitudes_pendientes) || 0;
      const rechazadas = parseInt(usuario.solicitudes_rechazadas) || 0;
      const horas = (parseFloat(usuario.horas_utilizadas) || 0).toFixed(1);

      html += `
        <tr>
          <td>
            <strong>${escHtml(usuario.usuario_nombre)}</strong>
            <br><small class="text-muted">${escHtml(usuario.usuario)}</small>
          </td>
          <td><small>${escHtml(usuario.correo)}</small></td>
          <td><small>${escHtml(usuario.gerencia_laboral || 'Sincronizando...')}</small></td>
          <td><small>${escHtml(usuario.unidad_laboral || 'Sincronizando...')}</small></td>
          <td><span class="badge bg-success">${aprobadas}</span></td>
          <td><span class="badge bg-warning">${pendientes}</span></td>
          <td><span class="badge bg-danger">${rechazadas}</span></td>
          <td><strong>${horas}h</strong></td>
        </tr>
      `;
    });
  }

  $('#tbody-gerencia').html(html);
}
