<?php
// Script para ejecutar la importación masiva por CLI (terminal)
$_SERVER["REQUEST_METHOD"] = "GET";
$_GET["action"] = "importar_historicos";
session_start();
$_SESSION["usuario_id"] = 1; // ID de administrador o el tuyo
$_SESSION["usuario_rol"] = "ADMIN";

echo "Iniciando importacion masiva de pozos...\n";
require "modules/laboratorio/pozos/controllers/PozoAPI.php";
echo "\nImportacion finalizada.\n";

