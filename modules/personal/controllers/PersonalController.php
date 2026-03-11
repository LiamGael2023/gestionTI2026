<?php
require_once 'modules/personal/models/PersonalModel.php';

$model = new PersonalModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/personal/views/index.php';
        break;
}