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

// Detectar subacción (si es kardex u otra vista)
$subaction = $_GET['subaction'] ?? 'index';

switch($subaction) {
    case 'kardex':
        include __DIR__ . '/../views/kardex.php';
        break;
    
    case 'index':
    default:
        include __DIR__ . '/../views/index.php';
        break;
}
