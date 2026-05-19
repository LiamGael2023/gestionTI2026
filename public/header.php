<?php 
require_once 'config/config.php'; 
require_once 'config/db.php'; 

$mod_actual = isset($module) ? $module : 'dashboard';
$conn = Conexion::conectar();

// --- CONSULTA DINÁMICA DE MÓDULOS ---
$menu_items = [];
if(isset($_SESSION['usuario_id'])){
    // La consulta une Módulos con Permisos del usuario actual
    $sql_menu = "SELECT m.nombre, m.etiqueta, m.icono 
                 FROM comun.Modulos m
                 INNER JOIN comun.Permisos p ON m.id_modulo = p.id_modulo
                 WHERE p.id_usuario = ? AND p.pueden_ver = 1
                 ORDER BY m.orden ASC";
    $stmt_menu = sqlsrv_query($conn, $sql_menu, array($_SESSION['usuario_id']));
    
    if ($stmt_menu !== false) {
        while($item = sqlsrv_fetch_array($stmt_menu, SQLSRV_FETCH_ASSOC)){
            $menu_items[] = $item;
        }
    }
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>Sistema de Gestión TI - GRHI</title>
    <base href="<?php echo BASE_URL; ?>/">
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">



    <!-- PARA SISTEMA DE INVENTARIO  -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        --pech-verde: #009540; 
        --pech-azul:  #004d99;
      }
      body { font-feature-settings: "cv03", "cv04", "cv11"; }
      .bg-pech { background-color: var(--pech-verde) !important; color: #ffffff !important; }
      
      .navbar-pech-blue { 
        background-color: var(--pech-azul) !important; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
      }
      .navbar-pech-blue .nav-link { color: rgba(255, 255, 255, 0.9) !important; }
      .navbar-pech-blue .nav-link-icon i { color: #ffffff !important; font-size: 1.25rem; }
      
      .navbar-pech-blue .nav-item.active .nav-link { 
        background-color: rgba(255, 255, 255, 0.2); 
        font-weight: bold; 
        border-radius: 4px; 
      }
    </style>
  </head>
  <body>
    <div class="page">
      
      <?php if(isset($_SESSION['usuario_id'])): ?>
      
      <header class="navbar navbar-expand-md d-print-none bg-white border-bottom">
        <div class="container-xl">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
          </button>
          
          <h1 class="navbar-brand d-none-navbar-horizontal pe-md-3">
            <a href="dashboard">
              <img src="https://app.chavimochic.gob.pe/Webservice/contador/LogoChavimochicFINAL.png" width="110" height="32" alt="PECH" class="navbar-brand-image">
            </a>
          </h1>

          <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item dropdown">
              <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                <span class="avatar avatar-sm"><?php echo substr($_SESSION['usuario_nombre'], 0, 1); ?></span>
                <div class="d-none d-xl-block ps-2">
                  <div><?php echo $_SESSION['usuario_nombre']; ?></div>
                  <div class="mt-1 small text-secondary"><?php echo $_SESSION['usuario_rol']; ?></div>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <a href="logout" class="dropdown-item text-danger">Cerrar Sesión</a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <header class="navbar-expand-md">
        <div class="collapse navbar-collapse" id="navbar-menu">
          <div class="navbar navbar-pech-blue"> 
            <div class="container-xl">
              <ul class="navbar-nav">
                
                <?php foreach($menu_items as $item): ?>
                <li class="nav-item <?php echo ($mod_actual == $item['nombre']) ? 'active' : ''; ?>">
                  <a class="nav-link" href="<?php echo $item['nombre']; ?>" >
                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                        <i class="ti ti-<?php echo $item['icono']; ?>"></i>
                    </span>
                    <span class="nav-link-title"><?php echo $item['etiqueta']; ?></span>
                  </a>
                </li>
                <?php endforeach; ?>

              </ul>
            </div>
          </div>
        </div>
      </header>
      <?php endif; ?>

      <div class="page-wrapper">