/**
 * shared/utils.js — Funciones utilitarias compartidas
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

/**
 * Escapa caracteres HTML para evitar inyecciones
 */
function escHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Badge de estado para reservas (color según estado)
 */
function badgeEstado(estado) {
  const map = {
    'PENDIENTE': 'warning',
    'APROBADA': 'success',
    'RECHAZADA': 'danger',
    'CANCELADA': 'secondary'
  };
  const color = map[estado] || 'secondary';
  return `<span class="badge bg-${color}-lt text-${color}">${escHtml(estado)}</span>`;
}

/**
 * Obtiene el texto de una opción seleccionada en un select
 */
function obtenerTextoSeleccionado(selector, textoDefault = '') {
  const texto = $(selector).find('option:selected').text();
  return (texto || textoDefault || '').trim();
}

/**
 * Abre un offcanvas de Bootstrap
 */
function abrirOffcanvas(id, callback) {
  const oc = new bootstrap.Offcanvas(document.getElementById(id));
  oc.show();
  if (typeof callback === 'function') callback();
}

/**
 * Formatea fecha en formato es-PE
 */
function formatearFecha(fecha) {
  if (!fecha) return '';
  const date = new Date(fecha);
  return date.toLocaleDateString('es-PE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
}

/**
 * Formatea hora en HH:MM
 */
function formatearHora(hora) {
  if (!hora) return '';
  return hora.substring(0, 5);
}

/**
 * Obtiene fecha y hora actual formateada
 */
function obtenerFechaHoraActual() {
  const ahora = new Date();
  return ahora.toLocaleString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
