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
        $valor = (int)$this->idTipo;
        $tipo = TipoCaracteristicasController::ctrMostrarTipoCaracteristicas($item, $valor);
        if (!$tipo) {
            echo json_encode(["status" => "error", "message" => "No se encontró el registro"]);
            return;
        }
        $fechaFormateada = "";
        if (!empty($tipo["fechaCreacion"])) {
            if ($tipo["fechaCreacion"] instanceof DateTime) $fechaFormateada = $tipo["fechaCreacion"]->format("d/m/Y");
            else $fechaFormateada = date("d/m/Y", strtotime($tipo["fechaCreacion"]));
        }
        $respuesta = [
            "idTipoCaracteristica" => intval($tipo["idTipoCaracteristica"]),
            "descripcion" => $tipo["descripcion"] ?? "",
            "idUsuarioRegistro" => $tipo["idUsuarioRegistro"] ?? "N/A",
            "fechaCreacion" => $fechaFormateada
        ];
        echo json_encode($respuesta);
    }

    /*=============================================
    AGREGAR TIPO CARACTERISTICA
    =============================================*/
    public function ajaxCrearTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrCrearTipoCaracteristica();
        echo json_encode($respuesta);
    }

    public function ajaxEditarTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrEditarTipoCaracteristica();
        // Limpieza de buffer para evitar espacios en blanco antes del "ok"
        if (ob_get_length()) ob_clean();
        echo $respuesta; // <--- Lo mismo aquí
    }
}

/* --- DISPARADORES --- */

// 1. CARGAR (Si solo viene el ID)
if (isset($_POST["idTipoCaracteristica"]) && !isset($_POST["editarDescripcion"])) {
    $mostrar = new AjaxTipoCaracteristicas();
    $mostrar->idTipo = $_POST["idTipoCaracteristica"];
    $mostrar->ajaxMostrarEditarTipoCaracteristica();
}

// 2. EDITAR (Si viene el ID oculto del formulario de edición)
else if (isset($_POST["editarIdTipoCaracteristica"])) {
    $editar = new AjaxTipoCaracteristicas();
    $editar->ajaxEditarTipoCaracteristica();
}

// 3. CREAR (Si viene la nueva descripción)
else if (isset($_POST["nuevaDescripcion"])) {
    $crear = new AjaxTipoCaracteristicas();
    $crear->ajaxCrearTipoCaracteristica();
}
