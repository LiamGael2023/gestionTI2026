<?php
session_start();
require_once __DIR__ . "/../models/TipoCaracteristicasModel.php";
require_once __DIR__ . "/../controllers/TipoCaracteristicasController.php";

class AjaxTipoCaracteristicas
{
    public $idTipo;

    public function ajaxMostrarEditarTipoCaracteristica()
    {
        $item = "idTipoCaracteristica";
        $valor = $this->idTipo;

        $tipo = TipoCaracteristicasController::ctrMostrarTipoCaracteristicas($item, $valor);

        if (!$tipo) {
            echo json_encode(["error" => "No se encontró el registro"]);
            return;
        }

        // SOLUCIÓN: Validar si la fecha es un objeto de SQL Server antes de formatearla
        $fechaFormateada = "";
        if (isset($tipo["fechaCreacion"])) {
            if ($tipo["fechaCreacion"] instanceof DateTime) {
                $fechaFormateada = $tipo["fechaCreacion"]->format("d/m/Y");
            } else {
                $fechaFormateada = date("d/m/Y", strtotime($tipo["fechaCreacion"]));
            }
        }

        // Formateo consistente para tu JS
        $respuesta = [
            "idTipoCaracteristica" => intval($tipo["idTipoCaracteristica"]),
            "descripcion"          => $tipo["descripcion"] ?? "",
            "idUsuarioRegistro"    => $tipo["idUsuarioRegistro"] ?? "N/A",
            "fechaCreacion"        => $fechaFormateada
        ];

        echo json_encode($respuesta);
    }

    // Guardar Cambios (Update)
    public function ajaxEditarTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrEditarTipoCaracteristica();
        echo json_encode($respuesta);
    }

    // Crear Nuevo
    public function ajaxCrearTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrCrearTipoCaracteristica();
        echo json_encode($respuesta);
    }
}

/* --- DISPARADORES --- */

// 1. Prioridad: Cargar datos para el modal (cuando solo envías el ID)
if (isset($_POST["idTipoCaracteristica"]) && !isset($_POST["editarDescripcion"])) {
    $mostrar = new AjaxTipoCaracteristicas();
    $mostrar->idTipo = $_POST["idTipoCaracteristica"];
    $mostrar->ajaxMostrarEditarTipoCaracteristica();
}

// 2. Guardar edición (cuando envías el ID y la descripción editada)
else if (isset($_POST["editarIdTipoCaracteristica"])) {
    $editar = new AjaxTipoCaracteristicas();
    $editar->ajaxEditarTipoCaracteristica();
}

// 3. Crear nuevo
else if (isset($_POST["nuevaDescripcion"])) {
    $crear = new AjaxTipoCaracteristicas();
    $crear->ajaxCrearTipoCaracteristica();
}
