<?php
// Wrapper temporal de prueba — genera el Excel de Calidad Superficial
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['usuario_id'] = 1019;
$_SESSION['usuario_nombre'] = 'Prueba';
$_SESSION['usuario_rol'] = 'admin';
$_GET['id_proyecto'] = 2498;   // CALIDAD SUPERFICIAL 2026 - MARZO
$_SERVER['HTTP_HOST'] = 'localhost';
include 'ExportarCalidadAgua.php';
