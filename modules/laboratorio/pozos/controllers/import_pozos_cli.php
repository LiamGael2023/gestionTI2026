<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['DOCUMENT_ROOT'] = 'd:\SISTEMAS\gestionTI2026';
$_SERVER['REQUEST_URI'] = '/gestionTI/modules/laboratorio/pozos/controllers/PozoAPI.php';
$_GET['action'] = 'importar_historicos';

require_once '../../../../core/Auth.php';
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'ADMIN';

echo "Empezando la importacion de pozos. Por favor espere...\n";
require 'PozoAPI.php';
echo "\nFin de la importacion.\n";
