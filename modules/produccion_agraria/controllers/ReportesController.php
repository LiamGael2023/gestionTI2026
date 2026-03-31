<?php
class ReportesController {
    public function index() {
        include 'modules/produccion_agraria/views/reportes/index.php';
    }
}

$controller = new ReportesController();
$controller->index();
?>
