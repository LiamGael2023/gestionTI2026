<?php
class InventarioController {
    public function index() {
        include 'modules/produccion_agraria/views/inventario/index.php';
    }
}

$controller = new InventarioController();
$controller->index();
?>
