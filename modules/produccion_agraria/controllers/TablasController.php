<?php
class TablasController {
    public function index() {
        include 'modules/produccion_agraria/views/tablas/index.php';
    }
}

$controller = new TablasController();
$controller->index();
?>
