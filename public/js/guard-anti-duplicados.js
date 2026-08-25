/* ============================================================
 * GUARD GLOBAL ANTI-DUPLICADOS (doble clic / doble submit)
 * Inyectado via public/footer.php en TODAS las paginas del sistema.
 *
 * 1) Dedupe de peticiones AJAX identicas EN VUELO: si un POST/PUT
 *    con la misma URL y el mismo payload ya esta corriendo, el
 *    segundo intento NO vuelve a enviarse (recibe la misma promesa).
 * 2) Cooldown fisico + disabled en botones de persistencia.
 *    NO toca botones que solo abren modales (data-bs-toggle).
 * ============================================================ */
(function () {
    if (typeof window.jQuery === 'undefined') return;

    // ---- 1) Proxy $.ajax con dedupe en vuelo ----
    (function ($) {
        var ajaxOriginal = $.ajax.bind($);
        var enVuelo = {};

        function hashKey(str) {
            var h = 5381;
            for (var i = 0; i < str.length; i++) { h = ((h << 5) + h + str.charCodeAt(i)) >>> 0; }
            return h.toString(36);
        }

        function clavePeticion(opts) {
            var method = String(opts.type || 'GET').toUpperCase();
            var data = opts.data;
            var s = method + '|' + (opts.url || '') + '|';
            if (data == null) { s += '0'; }
            else if (typeof data === 'string') { s += data; }
            else { try { s += JSON.stringify(data); } catch (e) { s += String(data); } }
            return method + '|' + hashKey(s);
        }

        $.ajax = function (url, settings) {
            var args = arguments;
            var opts = (typeof url === 'object') ? (url || {}) : (settings || {});
            if (typeof url === 'string') { opts.url = url; }
            var method = String(opts.type || 'GET').toUpperCase();
            var esEscritura = (method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE');
            var data = opts.data;
            var esFormData = (typeof FormData !== 'undefined' && data instanceof FormData);

            // Solo dedupe de escrituras con payload (los GET de carga/listado no se tocan;
            // FormData no se dedupea: puede llevar archivos/imagenes).
            if (esEscritura && !esFormData && data != null) {
                var key = clavePeticion(opts);
                if (enVuelo[key]) { return enVuelo[key]; }
                var xhr = ajaxOriginal.apply($, args);
                enVuelo[key] = xhr;
                var liberar = function () { if (enVuelo[key] === xhr) { delete enVuelo[key]; } };
                if (xhr && xhr.always) { xhr.always(liberar); } else { setTimeout(liberar, 30000); }
                return xhr;
            }
            return ajaxOriginal.apply($, args);
        };
    })(jQuery);

    // ---- 2) Cooldown fisico de botones de persistencia ----
    function esBotonPersistencia(btn) {
        if (btn.disabled || btn.dataset.guardActivo === '1') return false;
        // Botones que solo abren un modal (data-bs-toggle) no persisten -> no bloquear
        if (btn.hasAttribute('data-bs-toggle')) return false;
        if (btn.type === 'submit' && btn.form) return true;
        var id = btn.id || '';
        // btn-guardar-* / btn-confirmar-* / btn-crear-* / btn-registrar-*
        return /^btn-(guardar|confirmar|registrar|crear)-/.test(id);
    }

    function liberarBoton(btn) {
        btn.disabled = false;
        delete btn.dataset.guardActivo;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('button') : null;
        if (!btn) return;
        if (btn.disabled) { e.preventDefault(); e.stopImmediatePropagation(); return; }

        if (esBotonPersistencia(btn)) {
            btn.dataset.guardActivo = '1';
            // ⚠️ SUBMIT: deshabilitar en fase captura CANCELA el submit del PRIMER
            // clic (el navegador omite la accion por defecto si el boton queda
            // disabled al terminar la propagacion). Por eso para type="submit" el
            // disabled se DIFIERE a setTimeout(0): el 1er clic envia el formulario
            // y un 2º clic (doble clic) ya encuentra el boton disabled -> bloqueado.
            var aplicarDisabled = function () { btn.disabled = true; };
            if (btn.type === 'submit') { setTimeout(aplicarDisabled, 0); } else { aplicarDisabled(); }
            // Liberar al apagarse el ultimo $.ajax en vuelo, o a lo mas en 2s
            var intentos = 0;
            var iv = setInterval(function () {
                intentos++;
                var activo = (typeof jQuery !== 'undefined') ? jQuery.active : 0;
                if (activo === 0 || intentos >= 20) { // 20 x 100ms = 2s tope
                    clearInterval(iv);
                    liberarBoton(btn);
                }
            }, 100);
        }
    }, true); // fase captura: corre ANTES que los handlers jQuery de cada vista
})();