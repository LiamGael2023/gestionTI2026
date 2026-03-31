<?php
class PuntoVentaController {
    public function index() {
        include 'modules/produccion_agraria/views/punto_venta/index.php';
    }
}

$controller = new PuntoVentaController();
$controller->index();
?>
