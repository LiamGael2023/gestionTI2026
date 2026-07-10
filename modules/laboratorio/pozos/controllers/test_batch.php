<?php
// Mock session and POST
session_start();
$_SESSION['usuario_id'] = 1;
$_POST['action'] = 'importar_historicos_batch';
$_POST['id_medicion'] = 10368;
$_POST['monitoreo'] = 'HISTORICO 2022-04';
$_POST['valle'] = 'CHAO';
$_POST['fechamonitoreo'] = '2022-04-13';
$_POST['id_pozo'] = 'CHAVI-00020';
$_POST['orden'] = 1;
$_POST['numero_muestra'] = 1;

try {
    require_once 'PozoImportAPI.php';
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
