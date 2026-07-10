<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once 'config/db.php';
require_once 'core/Auth.php';
require_once 'modules/laboratorio/muestra/models/MuestraModel.php';
require_once 'modules/laboratorio/models/LaboratorioModel.php';

Auth::check();

// Obtener subaction
$subaction = $_GET['subaction'] ?? 'index';

// Cargar vista según subaction
switch($subaction) {
    case 'creacion_masiva':
        header('Location: ?module=laboratorio&action=muestra&tab=masiva');
        exit;
        break;
    
    case 'por_defecto':
        include 'modules/laboratorio/muestra/views/por_defecto.php';
        break;

    case 'individual':
        include 'modules/laboratorio/muestra/views/individual.php';
        break;
    
    case 'ver_progreso':
        include 'modules/laboratorio/muestra/views/ver_progreso.php';
        break;
    
    case 'ver_firmar': {
        $labModelFirma = new LaboratorioModel($conn);
        $permFirmar = $labModelFirma->obtenerPermisosSubmodulo($_SESSION['usuario_id'], '?module=laboratorio&action=muestra');
        if (!$permFirmar || empty($permFirmar['firmar'])) {
            http_response_code(403);
            include 'modules/laboratorio/views/sin_acceso.php';
            break;
        }
        include 'modules/laboratorio/muestra/views/ver_firmar.php';
        break;
    }
    
    case 'analisis_proyecto':
        include 'modules/laboratorio/muestra/views/analisis_proyecto.php';
        break;

    case 'analisis_agricultor':
        include 'modules/laboratorio/muestra/views/analisis_agricultor.php';
        break;

    case 'bitacora_detalle':
        include 'modules/laboratorio/muestra/views/bitacora_detalle.php';
        break;

    case 'bitacora_fecha_detalle':
        include 'modules/laboratorio/muestra/views/bitacora_fecha_detalle.php';
        break;

    case 'firma_agricultor': {
        $labModelFirma = new LaboratorioModel($conn);
        $permFirmar = $labModelFirma->obtenerPermisosSubmodulo($_SESSION['usuario_id'], '?module=laboratorio&action=muestra');
        if (!$permFirmar || empty($permFirmar['firmar'])) {
            http_response_code(403);
            include 'modules/laboratorio/views/sin_acceso.php';
            break;
        }
        include 'modules/laboratorio/muestra/views/firma_agricultor.php';
        break;
    }

    case 'resultados_pasados':
        include 'modules/laboratorio/muestra/views/resultados_pasados.php';
        break;

    case 'recepcion_formulario':
        include 'modules/laboratorio/muestra/views/recepcion_formulario.php';
        break;
    
    default:
        include 'modules/laboratorio/muestra/views/index.php';
        break;
}

// API endpoints no se usan en esta versión
// Los datos se obtienen directamente desde data_*.php files en views/
?>
