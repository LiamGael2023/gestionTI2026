<?php

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../models/CertificadosModel.php';

$conn = Conexion::conectar();
$model = new CertificadosModel($conn);

/* CONSULTA CERTIFICADOS */

$certificados = $model->listar(null);

/* NOMBRE ARCHIVO */

$filename = "backup_certificados_" . date("Y-m-d") . ".csv";

/* HEADERS DESCARGA */

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');

/* CREAR ARCHIVO */

$output = fopen("php://output", "w");

/* ENCABEZADOS */

fputcsv($output, [
'ID',
'PERSONA',
'DNI',
'TIPO CERTIFICADO',
'F_INST',
'F_EXP'
]);

/* DATOS */

foreach($certificados as $row){
    fputcsv($output, $row);
}

fclose($output);
exit;