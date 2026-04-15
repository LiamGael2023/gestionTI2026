<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . "/../controllers/DispositivosController.php";
require_once __DIR__ . "/../models/DispositivoModel.php";

DispositivoController::guardar();