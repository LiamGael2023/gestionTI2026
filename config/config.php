<?php
$host = $_SERVER['HTTP_HOST'];

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    ? "https://" 
    : "http://";

define('BASE_URL', $protocol . $host . '/gestionTI');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>