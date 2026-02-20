<?php
require_once 'modules/agricola/models/AgricolaModel.php';

$model = new AgricolaModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/agricola/views/index.php';
        break;
}