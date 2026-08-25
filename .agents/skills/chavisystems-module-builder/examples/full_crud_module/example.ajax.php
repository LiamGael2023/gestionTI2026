<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/ExampleController.php";
require_once __DIR__ . "/ExampleModel.php";

class AjaxEjemplo
{
    public function ajaxCrear()
    {
        ExampleController::ctrCrear();
    }

    public function ajaxEditar()
    {
        ExampleController::ctrEditar();
    }

    public function ajaxEliminar()
    {
        ExampleController::ctrEliminar();
    }

    public function ajaxObtenerPorId($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $item = ExampleController::ctrObtenerPorId($id);
        echo json_encode($item, JSON_UNESCAPED_UNICODE);
    }
}

// Router de acciones AJAX
if (isset($_POST["accion"]) && $_POST["accion"] === "crear") {
    $ajax = new AjaxEjemplo();
    $ajax->ajaxCrear();
    exit;
}

if (isset($_POST["accion"]) && $_POST["accion"] === "editar") {
    $ajax = new AjaxEjemplo();
    $ajax->ajaxEditar();
    exit;
}

if (isset($_POST["accion"]) && $_POST["accion"] === "eliminar") {
    $ajax = new AjaxEjemplo();
    $ajax->ajaxEliminar();
    exit;
}

if (isset($_POST["accion"]) && $_POST["accion"] === "obtener") {
    $id = (int)($_POST["id"] ?? 0);
    $ajax = new AjaxEjemplo();
    $ajax->ajaxObtenerPorId($id);
    exit;
}

// Soporte para obtener por GET (util en Select2 y otras integraciones)
if (isset($_GET["accion"]) && $_GET["accion"] === "obtener") {
    $id = (int)($_GET["id"] ?? 0);
    $ajax = new AjaxEjemplo();
    $ajax->ajaxObtenerPorId($id);
    exit;
}
