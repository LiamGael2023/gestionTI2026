<?php
// Esto detecta automáticamente si entras por localhost o por la IP 10.0.100.252
$host = $_SERVER['HTTP_HOST']; 
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

define('BASE_URL', $protocol . $host . '/gestionTI');
// Esto ayuda a que las sesiones se mantengan en el dominio correcto
ini_set('session.cookie_domain', $_SERVER['HTTP_HOST']); 
?>