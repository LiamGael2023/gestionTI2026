<?php if (!defined('ABSPATH')) define('ABSPATH', true); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login - Proyecto Especial Chavimochic</title>
  <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
  </script>
</head>
<body class="d-flex flex-column bg-white">
<div class="row g-0 flex-fill">
  <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
    <div class="container container-tight my-5 px-lg-5">
      <div class="text-center mb-4">
        <img src="https://app.chavimochic.gob.pe/Webservice/contador/LogoChavimochicFINAL.png" style="height:90px">
      </div>

      <form id="form-login" method="POST" autocomplete="off">
        <div class="mb-3">
          <label>Usuario</label>
          <input type="text" name="usuario" class="form-control" required>
        </div>
        <div class="mb-2">
          <label>Contraseña</label>
          <input type="password" name="contrasenia" class="form-control" required>
        </div>
        <button type="submit" id="btn-ingresar" class="btn btn-primary w-100">Ingresar al Sistema</button>
      </form>

      <div class="text-center text-muted mt-3 small">
        Proyecto Especial Chavimochic &copy; 2026<br>Área de Informática
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
$("#form-login").submit(function(e){
    e.preventDefault();
    const btn = $("#btn-ingresar");
    btn.prop("disabled", true).html('Cargando...');
    $.ajax({
        url: BASE_URL + "/index.php?module=auth&action=autenticar",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res){
            if(res.success) window.location.href = res.redirect;
            else {
                Swal.fire({icon:'error', title:'Error', text: res.message});
                btn.prop("disabled", false).text("Ingresar al Sistema");
            }
        },
        error: function(xhr, status, error){
            console.error(xhr, status, error);
            Swal.fire('Error','No se pudo conectar con el servidor','error');
            btn.prop("disabled", false).text("Ingresar al Sistema");
        }
    });
});
</script>
</body>
</html>