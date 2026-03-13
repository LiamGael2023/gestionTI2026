<?php
require_once 'modules/appsisger/models/AppsisgerModel.php';

$model = new AppsisgerModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/appsisger/views/index.php';
        break;
}