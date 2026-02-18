<?php
// Evitar acceso directo
if (!defined('ABSPATH')) define('ABSPATH', true);
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>Login - Proyecto Especial Chavimochic</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root { --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; }
      body { font-feature-settings: "cv03", "cv04", "cv11"; }
      .bg-cover { background-size: cover; background-position: center; background-repeat: no-repeat; }
    </style>
  </head>
  <body class="d-flex flex-column bg-white">
    <div class="row g-0 flex-fill">
      
      <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
        <div class="container container-tight my-5 px-lg-5">
          
          <div class="text-center mb-4">
            <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="https://app.chavimochic.gob.pe/Webservice/contador/LogoChavimochicFINAL.png" 
                     style="height: 90px; width: auto;" 
                     alt="PECH Logo">
            </a>
          </div>

          <form id="form-login" method="POST" autocomplete="off">
            
            <div class="mb-3">
              <label class="form-label">Usuario</label>
              <div class="input-icon mb-3">
                <span class="input-icon-addon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                </span>
                <input type="text" name="usuario" class="form-control" placeholder="Ej: jperalta" required>
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label">Contraseña</label>
              <div class="input-group input-group-flat">
                <span class="input-group-text">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="5" y="11" width="14" height="10" rx="2" /><circle cx="12" cy="16" r="1" /><path d="M8 11v-4a4 4 0 0 1 8 0v4" /></svg>
                </span>
                <input type="password" name="contrasenia" class="form-control" placeholder="••••••••" required>
              </div>
            </div>

            <div class="form-footer">
              <button type="submit" id="btn-ingresar" class="btn btn-primary w-100 py-2">
                Ingresar al Sistema
              </button>
            </div>
          </form>
          
          <div class="text-center text-secondary mt-3 text-muted small">
            Proyecto Especial Chavimochic &copy; 2026<br>
            Área de Informática
          </div>
        </div>
      </div>
      
      <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
        <div class="bg-cover h-100 min-vh-100" style="background-image: url('https://app.chavimochic.gob.pe/webservice/loginasistencia/fondoPECH.jpg');"></div>
      </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>

    <script>
      $(document).ready(function() {
        $("#form-login").on("submit", function(e) {
          e.preventDefault();
          
          const btn = $("#btn-ingresar");
          btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span> Cargando...');

          $.ajax({
            url: "index.php?module=auth&action=autenticar",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
              if (response.success) {
                window.location.href = response.redirect;
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Error de acceso',
                  text: response.message,
                  confirmButtonColor: '#d63939'
                });
                btn.prop("disabled", false).text("Ingresar al Sistema");
              }
            },
            error: function() {
              Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
              btn.prop("disabled", false).text("Ingresar al Sistema");
            }
          });
        });
      });
    </script>
  </body>
</html>