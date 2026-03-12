<?php
session_start();
require_once __DIR__ . "/../models/TipoCaracteristicasModel.php";
require_once __DIR__ . "/../controllers/TipoCaracteristicasController.php";

class AjaxTipoCaracteristicas
{
    /*=============================================
    AGREGAR TIPO CARACTERÍSTICA
    =============================================*/
    public function ajaxCrearTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrCrearTipoCaracteristica();
        echo json_encode($respuesta);
    }

    /*=============================================
    EDITAR TIPO CARACTERÍSTICA
    =============================================*/
    public function ajaxEditarTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrEditarTipoCaracteristica();
        echo json_encode($respuesta);
    }

    /*=============================================
    MOSTRAR PARA EDITAR
    =============================================*/
    public $idTipo;

    public function ajaxMostrarEditarTipoCaracteristica()
    {
        $item = "idTipoCaracteristica";
        $valor = $this->idTipo;

        $tipo = TipoCaracteristicasController::ctrMostrarTipoCaracteristicas($item, $valor);

        if (!$tipo) {
            echo json_encode(["error" => "No se encontró el tipo de característica"]);
            return;
        }

        // Formateamos la respuesta para el JS
        $respuesta = [
            "idTipoCaracteristica" => intval($tipo["idTipoCaracteristica"]),
            "descripcion"          => $tipo["descripcion"] ?? "",
            "idUsuarioRegistro"    => $tipo["idUsuarioRegistro"] ?? "",
            "fechaCreacion"        => isset($tipo["fechaCreacion"]) 
                ? ($tipo["fechaCreacion"] instanceof DateTime 
                    ? $tipo["fechaCreacion"]->format("d/m/Y") 
                    : date("d/m/Y", strtotime($tipo["fechaCreacion"])))
                : ""
        ];

        echo json_encode($respuesta);
    }
}

/*=============================================
DISPARADORES (TRIGGERS)
=============================================*/

// Guardar nuevo
if (isset($_POST["nuevaDescripcion"])) {
    $crear = new AjaxTipoCaracteristicas();
    $crear->ajaxCrearTipoCaracteristica();
}

// Editar existente
if (isset($_POST["editarDescripcion"])) {
    $editar = new AjaxTipoCaracteristicas();
    $editar->ajaxEditarTipoCaracteristica();
}

// Traer datos para el modal de edición
if (isset($_POST["idTipoCaracteristica"])) {
    $mostrar = new AjaxTipoCaracteristicas();
    $mostrar->idTipo = $_POST["idTipoCaracteristica"];
    $mostrar->ajaxMostrarEditarTipoCaracteristica();
}