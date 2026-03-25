/**
 * shared/debug.js — Depuración global
 * Captura errores globales y rechazos de promesas no manejadas
 */

// Capturar errores globales no manejados
window.addEventListener('error', function(event) {
  console.error('❌ ERROR GLOBAL:', {
    message: event.message,
    filename: event.filename,
    lineno: event.lineno,
    colno: event.colno,
    error: event.error?.stack || event.error
  });
});

// Capturar rechazos de promesas no manejados
window.addEventListener('unhandledrejection', function(event) {
  console.error('❌ UNHANDLED REJECTION:', {
    reason: event.reason,
    promise: event.promise,
    stack: event.reason?.stack
  });
});
