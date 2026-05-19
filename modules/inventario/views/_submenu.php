<?php
/* ═══════════════════════════════════════
   _submenu.php — Submenú horizontal sticky
   Incluir en cada vista después del navbar
   Uso: include 'modules/inventario/views/_submenu.php';
═══════════════════════════════════════ */
$currentAction = $_GET['action'] ?? 'index';

$menuItems = [
    ['action' => 'index',        'icon' => 'ti-layout-dashboard', 'label' => 'Dashboard',       'group' => ['index']],
    ['action' => 'activos',      'icon' => 'ti-settings',         'label' => 'Configuraciones', 'group' => ['activos','tipoCaracteristicas','caracteristicas','ubicaciones','ips']],
    ['action' => 'equipos',      'icon' => 'ti-devices',          'label' => 'Equipos',         'group' => ['equipos']],
    ['action' => 'estaciones',   'icon' => 'ti-desktop',          'label' => 'Estaciones',      'group' => ['estaciones','agregarEstacion','editarEstacion']],
    ['action' => 'asignaciones', 'icon' => 'ti-user-check',       'label' => 'Asignaciones',    'group' => ['asignaciones']],
];
?>
<div class="navbar-expand-md" style="
    background:#fff;
    border-bottom:1px solid var(--tblr-border-color,#e6ebf1);
    position:sticky;top:0;z-index:1020;
    box-shadow:0 1px 4px rgba(0,0,0,.06)">
  <div class="container-xl">
    <ul class="navbar-nav d-flex flex-row gap-1 py-1 overflow-auto" style="flex-wrap:nowrap;scrollbar-width:none">
      <?php foreach ($menuItems as $item):
        $active = in_array($currentAction, $item['group']);
      ?>
      <li class="nav-item">
        <a href="?module=inventario&action=<?= $item['action'] ?>"
           class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-2"
           style="
             font-size:.82rem;font-weight:600;white-space:nowrap;
             <?= $active
               ? 'background:var(--tblr-primary-lt,#e7f0ff);color:var(--tblr-primary,#0054a6);'
               : 'color:#475569;' ?>
             transition:all .15s">
          <i class="ti <?= $item['icon'] ?>" style="font-size:.95rem"></i>
          <?= $item['label'] ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<style>
/* Hover en items no activos */
.navbar-nav .nav-link:hover {
    background: var(--tblr-bg-surface-secondary, #f8fafc) !important;
    color: var(--tblr-primary, #0054a6) !important;
}
/* Ocultar scrollbar horizontal en mobile */
.navbar-nav::-webkit-scrollbar { display: none; }
</style>
