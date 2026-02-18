<?php
require_once 'modules/certificados/models/CertificadosModel.php';

$model = new CertificadosModel($conn);
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/certificados/views/index.php';
        break;
}