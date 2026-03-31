<?php
class BandejaController {
    public function index() {
        include 'modules/produccion_agraria/views/bandeja/index.php';
    }
}

$controller = new BandejaController();
$controller->index();
?>
