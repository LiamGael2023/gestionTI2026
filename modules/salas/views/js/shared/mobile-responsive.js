/**
 * shared/mobile-responsive.js — Lógica de responsividad para móvil
 * Maneja: panel lateral, topbar adaptativo, eventos táctiles
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
'use strict';

(function() {
  // Detectar si estamos en pantalla móvil
  function esMobile() {
    return window.innerWidth <= 768;
  }

  function esMovilPequeno() {
    return window.innerWidth <= 480;
  }

  // Crear botón de toggle para panel lateral en móvil
  function crearBotonesMoviles() {
    const topbar = document.getElementById('salas-topbar');
    if (!topbar) return;

    // No crear si ya existen
    if (document.getElementById('btn-toggle-panel-lateral')) return;

    const btnToggle = document.createElement('button');
    btnToggle.id = 'btn-toggle-panel-lateral';
    btnToggle.type = 'button';
    btnToggle.className = 'btn btn-sm btn-light ms-auto';
    btnToggle.style.display = 'none';
    btnToggle.innerHTML = '<i class="ti ti-menu-2"></i>';
    btnToggle.setAttribute('title', 'Mostrar panel de acciones');

    topbar.appendChild(btnToggle);

    btnToggle.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      togglePanelLateral();
    });

    // Mostrar/ocultar botón según resolución
    ajustarBotonesMobiles();
  }

  // Mostrar/ocultar el botón según resolución
  function ajustarBotonesMobiles() {
    const btnToggle = document.getElementById('btn-toggle-panel-lateral');
    const sidePanel = document.getElementById('salas-side-panel');

    if (!btnToggle || !sidePanel) return;

    if (esMobile()) {
      btnToggle.style.display = 'block';
      // Cerrar panel si estamos en móvil
      sidePanel.classList.remove('mobile-panel-open');
    } else {
      btnToggle.style.display = 'none';
      // Panel siempre visible en escritorio
      sidePanel.classList.remove('mobile-panel-open');
    }
  }

  // Toggle del panel lateral
  window.togglePanelLateral = function() {
    const sidePanel = document.getElementById('salas-side-panel');
    if (!sidePanel) return;

    sidePanel.classList.toggle('mobile-panel-open');
    
    // Debug: verificar que el panel tiene los botones
    console.log('Panel abierto:', sidePanel.classList.contains('mobile-panel-open'));
    console.log('Botones en panel:', sidePanel.querySelectorAll('.btn-side-secondary').length);
    
    const btnToggle = document.getElementById('btn-toggle-panel-lateral');
    if (btnToggle) {
      if (sidePanel.classList.contains('mobile-panel-open')) {
        btnToggle.innerHTML = '<i class="ti ti-x"></i>';
        btnToggle.setAttribute('title', 'Cerrar panel de acciones');
        // Scroll para mostrar el panel
        setTimeout(() => {
          sidePanel.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 100);
      } else {
        btnToggle.innerHTML = '<i class="ti ti-menu-2"></i>';
        btnToggle.setAttribute('title', 'Mostrar panel de acciones');
      }
    }
  };

  // Cerrar panel lateral cuando se hace clic fuera
  function cerrarPanelAlHacerClick(e) {
    const sidePanel = document.getElementById('salas-side-panel');
    const btnToggle = document.getElementById('btn-toggle-panel-lateral');

    if (!sidePanel || !esMobile()) return;

    const esClickEnPanel = sidePanel.contains(e.target);
    const esClickEnBoton = btnToggle && btnToggle.contains(e.target);

    if (!esClickEnPanel && !esClickEnBoton && sidePanel.classList.contains('mobile-panel-open')) {
      sidePanel.classList.remove('mobile-panel-open');
      if (btnToggle) {
        btnToggle.innerHTML = '<i class="ti ti-menu-2"></i>';
        btnToggle.setAttribute('title', 'Mostrar panel de acciones');
      }
    }
  }

  // Ajustar tamaños de fuente para móvil pequeño
  function ajustarTamanosFuente() {
    if (esMovilPequeno()) {
      document.documentElement.style.fontSize = '13px';
    } else if (esMobile()) {
      document.documentElement.style.fontSize = '14px';
    } else {
      document.documentElement.style.fontSize = '16px';
    }
  }

  // Manejar viewport
  function manejarViewport() {
    ajustarBotonesMobiles();
    ajustarTamanosFuente();

    // Recalcular altura del calendario
    if (typeof ajustarAlturaCalendario === 'function') {
      setTimeout(ajustarAlturaCalendario, 100);
    }
  }

  // Inicializar
  function init() {
    crearBotonesMoviles();
    manejarViewport();

    // Escuchar clicks en el documento
    document.addEventListener('click', cerrarPanelAlHacerClick);

    // Escuchar cambios de orientación y resize
    window.addEventListener('resize', manejarViewport);
    window.addEventListener('orientationchange', function() {
      setTimeout(manejarViewport, 200);
    });
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
