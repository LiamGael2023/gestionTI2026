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

if (!isset($_GET['id'])) {
	$pathInfo = $_SERVER['PATH_INFO'] ?? '';
	if ($pathInfo === '' && isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
		$pathInfo = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', strlen($_SERVER['SCRIPT_NAME']));
	}

	$idDesdeRuta = trim($pathInfo, '/');
	if (ctype_digit($idDesdeRuta)) {
		$_GET['id'] = (int) $idDesdeRuta;
	}
}

Auth::check();

require 'modules/comunicados/controllers/ComunicadoController.php';
