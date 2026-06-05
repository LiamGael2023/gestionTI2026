<?php
$rootPath = dirname(__DIR__, 2);
chdir($rootPath);

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'core/Auth.php';

$_GET['module'] = 'adquisiciones';
Auth::check();

require_once 'modules/adquisiciones/controllers/AdquisicionesController.php';
