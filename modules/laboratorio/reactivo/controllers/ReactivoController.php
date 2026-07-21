<?php
/**
 * ReactivoController.php
 * MVC Controller - Carga la vista de reactivos
 * Ruta: modules/laboratorio/reactivo/controllers/ReactivoController.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once 'config/db.php';
require_once 'core/Auth.php';

Auth::check();

// El tab (inventario|kardex) se maneja dentro de index.php via $_GET['tab']
// Por compatibilidad, ?subaction=kardex redirige a ?tab=kardex
$subaction = $_GET['subaction'] ?? '';
if ($subaction === 'kardex' && !isset($_GET['tab'])) {
    $qs = http_build_query(array_merge($_GET, ['tab' => 'kardex', 'subaction' => '']));
    header('Location: ?' . $qs);
    exit;
}

include __DIR__ . '/../views/index.php';
