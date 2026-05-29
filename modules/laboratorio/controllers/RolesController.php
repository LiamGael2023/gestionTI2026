<?php
/**
 * RolesController.php — Renderiza la vista de gestión de roles de laboratorio.
 * Solo accesible para administradores (ya verificado en LaboratorioController).
 */

// $labModel, $conn, $usuarioData, $esAdmin ya están disponibles desde LaboratorioController

$roles      = $labModel->listarRoles();
$submodulos = $labModel->listarSubmodulos();
$usuarios   = $labModel->listarUsuariosLaboratorio();  // cargado en PHP, sin AJAX

include 'modules/laboratorio/views/roles_index.php';
