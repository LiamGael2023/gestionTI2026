<?php

require_once __DIR__.'/../../../config/db.php';
require_once __DIR__.'/../models/CertificadosModel.php';

/* CONEXION */

$conn = Conexion::conectar(); // PDO
$model = new CertificadosModel($conn);

/* ID CERTIFICADO */

$id_certificado = $_GET['id'] ?? null;

if(!$id_certificado){
die("Certificado no especificado");
}

/* OBTENER CERTIFICADO */

$certificado = $model->obtenerPorId($id_certificado);

if(!$certificado){
die("Certificado no encontrado");
}

/* ARCHIVO ORIGINAL */

$archivo = $certificado['ruta_archivo'];

/* CARPETA ORIGEN */

$origen = "certificados/archivos_subidos/".$archivo;

/* CARPETA BACKUPS */

$destinoCarpeta = "modules/certificados/backups/";

/* CREAR IDENTIFICADOR */

$identificador = $certificado['nombres']." ".$certificado['apellidos']."-".$certificado['fecha_emision'];

/* NOMBRE BACKUP */

$nombreBackup = time()."_".$archivo;

$destino = $destinoCarpeta.$nombreBackup;

/* COPIAR ARCHIVO */

if(!file_exists($origen)){
die("Archivo original no encontrado");
}

copy($origen,$destino);

/* USUARIO */

$id_usuario = 4; // luego usar SESSION

/* INSERT BD */

$sql = "
INSERT INTO certificados.BackupsCertificados
(codigo_reloj,identificador,ruta_archivo,fecha_backup,id_certificado,id_usuario_backup)
VALUES (:codigo,:identificador,:ruta,:fecha,:certificado,:usuario)
";

$stmt = $conn->prepare($sql);

$stmt->execute([

':codigo' => $certificado['codigo_reloj'],
':identificador' => $identificador,
':ruta' => $nombreBackup,
':fecha' => date("Y-m-d H:i:s"),
':certificado' => $id_certificado,
':usuario' => $id_usuario

]);

/* REDIRECCION */

header("Location: index.php?module=certificados&msg=backup_ok");

exit;