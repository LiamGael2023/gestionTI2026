<?php

while (ob_get_level() > 0) {
	ob_end_clean();
}

// El endpoint vive en modules/comunicados/, subimos 2 niveles hasta la raiz.
chdir(dirname(__DIR__, 2));

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'core/Auth.php';

// Auth::check usa $_GET['module'] para validar permisos del modulo actual.
$_GET['module'] = 'comunicados';
$_GET['action'] = 'visualizar';

Auth::check();

require 'modules/comunicados/controllers/ComunicadoController.php';
