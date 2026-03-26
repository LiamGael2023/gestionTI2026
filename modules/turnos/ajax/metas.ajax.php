<?php

require_once 'modules/turnos/models/trabajador.php';
require_once 'modules/turnos/controllers/trabajadorController.php';

$anio = $_POST["anio"];
$componente = $_POST["componente"];

$metas = TrabajadorController::ctrMostrarMetas($anio, $componente);

foreach ($metas as $m) {

echo '<option value="'.$m["Meta_Descripcion"].'">'.$m["Meta_Descripcion"].'</option>';

}