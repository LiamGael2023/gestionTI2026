<?php
session_start();
require_once __DIR__ . "/../models/ActivosModel.php";
require_once __DIR__ . "/../controllers/ActivosController.php";

class AjaxActivos
{

    /*=============================================
    AGREGAR ACTIVO
    =============================================*/
    public function ajaxCrearActivo()
    {
        $respuesta = ActivosController::ctrCrearActivo();
        echo json_encode($respuesta);
    }
    /*=============================================
    EDITAR ACTIVO
    =============================================*/

    public function ajaxEditarActivo()
    {
        $respuesta = ActivosController::ctrEditarActivo();
        echo json_encode($respuesta);
    }

    /*=============================================
    MOSTRAR PARA EDITAR ACTIVO
    =============================================*/
    public $idActivo;

    public function ajaxMostrarEditarActivo()
    {

        $item = "idActivos";
        $valor = $this->idActivo;

        $activo = ActivosController::ctrMostrarActivos($item, $valor);

        if (!$activo) {

            echo json_encode(["error" => "No se encontró el activo"]);
            return;
        }

        $respuesta = [

            "idActivos" => intval($activo["idActivos"]),
            "descripcion" => $activo["descripcion"] ?? "",
            "icono" => $activo["icono"] ?? "",
            "compuesto" => $activo["compuesto"] ?? 0,
            "idUsuarioRegistro" => $activo["idUsuarioRegistro"] ?? "",
            "fechaCreacion" => isset($activo["fechaCreacion"])
                ? ($activo["fechaCreacion"] instanceof DateTime ? $activo["fechaCreacion"]->format("d/m/Y") : date("d/m/Y", strtotime($activo["fechaCreacion"])))
                : ""

        ];

        echo json_encode($respuesta);
    }
}

if (isset($_POST["nuevaDescripcion"])) {
    $crear = new AjaxActivos();
    $crear->ajaxCrearActivo();
}

if (isset($_POST["editarDescripcion"])) {
    $crear = new AjaxActivos();
    $crear->ajaxEditarActivo();
}

/*=============================================
MOSTRAR PARA EDITAR ACTIVO
=============================================*/

if (isset($_POST["idActivo"])) {

    $editar = new AjaxActivos();
    $editar->idActivo = $_POST["idActivo"];
    $editar->ajaxMostrarEditarActivo();
}
