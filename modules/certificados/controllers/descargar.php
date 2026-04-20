<?php

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../models/CertificadosModel.php';

/* CONEXION */

$conn = Conexion::conectar();

/* MODELO */

$model = new CertificadosModel($conn);

/* CONSULTA */

$certificados = $model->certificadosPorVencer();

/* NOMBRE DEL ARCHIVO */

$filename = "certificados_por_vencer_" . date("Y-m-d") . ".csv";

/* HEADERS */

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');

/* SALIDA */

$output = fopen("php://output", "w");

/* CABECERA */

fputcsv($output, [
'ID',
'PERSONA',
'DNI',
'TIPO_CERTIFICADO',
'F_INST',
'F_EXP',
'F_GESTION'
]);

/* DATOS */

foreach($certificados as $row){
    fputcsv($output, $row);
}

fclose($output);
exit;