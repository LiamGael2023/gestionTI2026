<?php
session_start();
$_SESSION['usuario_id'] = 1020;
$_SESSION['usuario_nombre'] = 'ALEM SEBASTIAN';
$_SESSION['usuario_rol'] = 'USUARIO';
$_SESSION['autenticado'] = true;
session_write_close();
header('Content-Type: text/plain');
echo "session set for " . session_id() . "\n";
?>
