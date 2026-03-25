/**
 * shared/api.js — Cliente AJAX centralizado
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

const API = {
  /**
   * Llamada AJAX genérica (calendario)
   */
  call: function(action, data = {}, method = 'POST', options = {}) {
    return $.ajax(Object.assign({
      url: AJAX + '?action=' + action,
      method: method,
      data: data,
      dataType: 'json',
      cache: false
    }, options));
  },

  /**
   * Llamada AJAX para pendientes
   */
  callPendientes: function(action, data = {}, method = 'POST', options = {}) {
    return $.ajax(Object.assign({
      url: AJAX_PEND + '?action=' + action,
      method: method || 'POST',
      data: data || {},
      dataType: 'json',
      cache: false
    }, options));
  },

  /**
   * Llamada AJAX para historial
   */
  callHistorial: function(action, data = {}, method = 'POST', options = {}) {
    return $.ajax(Object.assign({
      url: AJAX_HIST + '?action=' + action,
      method: method || 'POST',
      data: data || {},
      dataType: 'json',
      cache: false
    }, options));
  },

  /**
   * Llamada AJAX para mis-reservas
   */
  callMisReservas: function(action, data = {}, method = 'POST', options = {}) {
    return $.ajax(Object.assign({
      url: AJAX + '?action=' + action,
      method: method,
      data: data,
      dataType: 'json',
      cache: false
    }, options));
  }
};

// Compatibilidad hacia atrás (función ajax global)
function ajax(action, data = {}, method = 'POST', options = {}) {
  // Devolver jqXHR de jQuery que es thenable Y tiene .done()/.fail()
  return API.call(action, data, method, options);
}

function reqPend(action, data = {}, method = 'POST', options = {}) {
  return API.callPendientes(action, data, method, options);
}

function reqHist(action, data = {}, method = 'POST', options = {}) {
  return API.callHistorial(action, data, method, options);
}
