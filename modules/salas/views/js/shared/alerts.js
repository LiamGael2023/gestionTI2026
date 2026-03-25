/**
 * shared/alerts.js — Sistema de alertas centralizado (SweetAlert2)
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

const Alerta = {
  exito: function(msg) {
    return Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: msg,
      timer: 2500,
      showConfirmButton: false
    });
  },

  error: function(msg) {
    return Swal.fire({
      icon: 'error',
      title: 'Error',
      text: msg
    });
  },

  info: function(msg) {
    return Swal.fire({
      icon: 'info',
      title: 'Información',
      text: msg
    });
  },

  advertencia: function(msg) {
    return Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: msg
    });
  },

  confirmar: function(msg, callback) {
    return Swal.fire({
      icon: 'question',
      title: '¿Confirmar?',
      text: msg,
      showCancelButton: true,
      confirmButtonText: 'Sí, confirmar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#2fb344'
    }).then(r => {
      if (r.isConfirmed) callback();
    });
  },

  confirmarPeligro: function(msg, callback) {
    return Swal.fire({
      icon: 'warning',
      title: '¿Está seguro?',
      text: msg,
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d63939'
    }).then(r => {
      if (r.isConfirmed) callback();
    });
  },

  procesando: function(titulo = 'Procesando…', texto = 'Por favor espere.') {
    Swal.fire({
      title: titulo,
      text: texto,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading()
    });
  },

  cerrar: function() {
    Swal.close();
  }
};
