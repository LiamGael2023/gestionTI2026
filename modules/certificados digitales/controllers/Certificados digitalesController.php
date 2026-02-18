<?php
require_once 'modules/certificados digitales/models/Certificados digitalesModel.php';

$model = new Certificados digitalesModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/certificados digitales/views/index.php';
        break;
}