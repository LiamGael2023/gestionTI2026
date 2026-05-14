<?php
// Endpoint AJAX propio del modulo para evitar que el router global devuelva el layout HTML.
chdir(dirname(__DIR__, 2));

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'core/Auth.php';

$_GET['module'] = 'adquisiciones';
$_GET['action'] = $_GET['action'] ?? 'requerimientos';

Auth::check();

include 'modules/adquisiciones/controllers/AdquisicionesController.php';
