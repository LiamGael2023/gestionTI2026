<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once 'config/db.php';
require_once 'core/Auth.php';

Auth::check();

// Cargar la vista
include __DIR__ . '/../views/index.php';
