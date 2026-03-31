<?php
class DashboardController {
    public function index() {
        include 'modules/produccion_agraria/views/dashboard/index.php';
    }
}

$controller = new DashboardController();
$controller->index();
?>
