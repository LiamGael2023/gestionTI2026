<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once 'config/db.php';
require_once 'core/Auth.php';
require_once 'modules/laboratorio/models/LaboratorioModel.php';
require_once 'modules/laboratorio/pozos/models/CatastroPozoModel.php';
require_once 'modules/laboratorio/pozos/models/MonitoreoPozoAsignacionModel.php';

Auth::check();

$subaction = $_GET['subaction'] ?? 'index';

switch ($subaction) {
    case 'index':
    default:
        include 'modules/laboratorio/pozos/views/index.php';
        break;

    case 'geoportal':
        include 'modules/laboratorio/pozos/views/geoportal.php';
        break;

    case 'historial_pozo':
        include 'modules/laboratorio/pozos/views/historial_pozo.php';
        break;

    case 'asignacion_pozos':
        include 'modules/laboratorio/pozos/views/asignacion_pozos.php';
        break;
}
