<?php

header('Content-Type: application/json');

require_once __DIR__ . "/../controllers/PerfilController.php";

PerfilController::ObtenerPerfilUsuario();